<?php

namespace App\Services;

use App\Models\Loan;
use App\Models\Salary;
use Carbon\Carbon;

class StaffLoanRepaymentService
{
    /** @var array<int, array<int, float>> */
    private array $staffPaidAllocations = [];

    /**
     * Full monthly loan installment due for a staff salary (not capped by salary amount).
     * Example: loan 500, instalments 1 → repayment 500 (full loan cut on generate).
     */
    public function calculate(int $staffId, ?int $forSalaryId = null): float
    {
        Loan::ensureBalanceColumns();

        // Any non-rejected loan with an unpaid balance.
        $loans = Loan::query()
            ->where('staff_id', $staffId)
            ->where(function ($query) {
                $query->whereNull('status')
                    ->orWhereRaw('LOWER(TRIM(status)) NOT IN (?, ?)', ['rejected', 'pending']);
            })
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        if ($loans->isEmpty()) {
            return 0.0;
        }

        $reservedByLoan = $this->reservedPendingByLoan($staffId, $forSalaryId);
        $totalLoanRepayment = 0.0;

        foreach ($loans as $loan) {
            $this->ensureInitialApprovedAmount($loan);

            $remaining = $this->unpaidBalance($loan);
            if ($remaining <= 0) {
                continue;
            }

            // Keep approved_amount in sync when it was wiped but balance still exists.
            if ((float) ($loan->approved_amount ?? 0) <= 0 && $remaining > 0) {
                $loan->approved_amount = $remaining;
                if (strtolower(trim((string) ($loan->status ?? ''))) === 'completed') {
                    $loan->status = 'Approved';
                }
                $loan->saveQuietly();
            }

            $reserved = (float) ($reservedByLoan[$loan->id] ?? 0);
            $available = max(0, round($remaining - $reserved, 2));
            if ($available <= 0) {
                continue;
            }

            $totalLoanRepayment += $this->monthlyInstallmentFromBalance($loan, $available);
        }

        return round($totalLoanRepayment, 2);
    }

    /**
     * Unpaid loan balance (principal − already recovered via paid salaries).
     */
    private function unpaidBalance(Loan $loan): float
    {
        $fromColumn = max(0, (float) ($loan->approved_amount ?? 0));
        $fromHistory = max(0, round($this->principalForLoan($loan) - $this->paidAmountForLoan($loan), 2));

        // Use the higher positive balance so a wiped approved_amount still deducts.
        return max($fromColumn, $fromHistory);
    }

    /**
     * How much each loan already reserved on earlier pending salaries.
     *
     * @return array<int, float>
     */
    private function reservedPendingByLoan(int $staffId, ?int $forSalaryId): array
    {
        $pending = Salary::query()
            ->where('staff_id', $staffId)
            ->where('status', 'Pending')
            ->where(function ($query) {
                $query->whereNull('amount_paid')->orWhere('amount_paid', '<=', 0);
            })
            ->where('loan_repayment', '>', 0)
            ->get()
            ->sortBy(fn (Salary $s) => $this->salarySortKey($s))
            ->values();

        if ($forSalaryId !== null) {
            $current = Salary::find($forSalaryId);
            if ($current) {
                $currentKey = $this->salarySortKey($current);
                $pending = $pending->filter(
                    fn (Salary $s) => (int) $s->id !== (int) $forSalaryId
                        && $this->salarySortKey($s) < $currentKey
                )->values();
            }
        } else {
            // New salary: do not reserve against other pending rows.
            return [];
        }

        $loans = Loan::query()
            ->where('staff_id', $staffId)
            ->where(function ($query) {
                $query->whereNull('status')
                    ->orWhereRaw('LOWER(TRIM(status)) NOT IN (?, ?)', ['rejected', 'pending']);
            })
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        $reserved = [];
        foreach ($loans as $loan) {
            $reserved[$loan->id] = 0.0;
        }

        foreach ($pending as $salary) {
            $left = (float) ($salary->loan_repayment ?? 0);
            foreach ($loans as $loan) {
                if ($left <= 0) {
                    break;
                }
                $balanceLeft = max(0, (float) ($loan->approved_amount ?? 0) - ($reserved[$loan->id] ?? 0));
                if ($balanceLeft <= 0) {
                    continue;
                }
                $take = min($left, $balanceLeft);
                $reserved[$loan->id] = round(($reserved[$loan->id] ?? 0) + $take, 2);
                $left = round($left - $take, 2);
            }
        }

        return $reserved;
    }

    private function salarySortKey(Salary $salary): string
    {
        $months = [
            'January' => '01', 'February' => '02', 'March' => '03', 'April' => '04',
            'May' => '05', 'June' => '06', 'July' => '07', 'August' => '08',
            'September' => '09', 'October' => '10', 'November' => '11', 'December' => '12',
        ];
        $month = $months[$salary->salary_month] ?? '99';

        return sprintf('%04d-%s-%010d', (int) $salary->year, $month, (int) $salary->id);
    }

    /**
     * Deduct collected loan repayment from approved loan balances after salary payment.
     */
    public function applyRepaymentFromSalary(Salary $salary, float $previousAmountPaid = 0): void
    {
        $loanRepayment = (float) ($salary->loan_repayment ?? 0);
        if ($loanRepayment <= 0) {
            return;
        }

        if ($previousAmountPaid > 0) {
            return;
        }

        $currentPaid = (float) ($salary->amount_paid ?? 0);
        $hasPayment = $currentPaid > 0 || in_array($salary->status, ['Paid', 'Issued'], true);
        if (! $hasPayment) {
            return;
        }

        $this->applyRepayment((int) $salary->staff_id, $loanRepayment);
    }

    /**
     * Paid amount for one loan, derived from existing salary repayment records.
     */
    public function paidAmountForLoan(Loan $loan): float
    {
        $staffId = (int) $loan->staff_id;
        if ($staffId <= 0) {
            return 0.0;
        }

        if (! isset($this->staffPaidAllocations[$staffId])) {
            $this->staffPaidAllocations[$staffId] = $this->buildStaffPaidAllocations($staffId);
        }

        return round((float) ($this->staffPaidAllocations[$staffId][$loan->id] ?? 0), 2);
    }

    /**
     * Rebuild remaining approved amounts from salary repayment history.
     */
    public function syncAllLoanBalances(): void
    {
        Loan::ensureBalanceColumns();

        $staffIds = Loan::query()
            ->distinct()
            ->pluck('staff_id')
            ->filter();

        foreach ($staffIds as $staffId) {
            $this->syncStaffLoanBalances((int) $staffId);
        }
    }

    /**
     * Rebuild loan balances for one staff member from their salary repayment history.
     */
    public function syncStaffLoanBalances(int $staffId): void
    {
        Loan::ensureBalanceColumns();

        $loans = Loan::where('staff_id', $staffId)->orderBy('created_at')->get();
        if ($loans->isEmpty()) {
            return;
        }

        $allocations = $this->buildStaffPaidAllocations($staffId);
        $this->staffPaidAllocations[$staffId] = $allocations;

        foreach ($loans as $loan) {
            $this->ensureInitialApprovedAmount($loan);
            $this->repairInitialApprovedAmount($loan);

            $principal = $this->principalForLoan($loan);
            $paid = (float) ($allocations[$loan->id] ?? 0);
            $remaining = max(0, round($principal - $paid, 2));

            $loan->approved_amount = $remaining;

            if ($loan->status === 'Rejected') {
                $loan->saveQuietly();

                continue;
            }

            if ($remaining <= 0.009 && $paid > 0) {
                $loan->status = 'Completed';
            } else {
                $loan->status = 'Approved';
            }

            $loan->saveQuietly();
        }
    }

    /**
     * @return array<int, float>
     */
    private function buildStaffPaidAllocations(int $staffId): array
    {
        $loans = Loan::where('staff_id', $staffId)->orderBy('created_at')->get();
        $allocations = [];

        foreach ($loans as $loan) {
            $this->ensureInitialApprovedAmount($loan);
            $this->repairInitialApprovedAmount($loan);
            $allocations[$loan->id] = 0.0;
        }

        $salaries = Salary::where('staff_id', $staffId)
            ->where('loan_repayment', '>', 0)
            ->where('amount_paid', '>', 0)
            ->orderBy('payment_date')
            ->orderBy('updated_at')
            ->orderBy('id')
            ->get();

        foreach ($salaries as $salary) {
            $paymentAt = $this->salaryRepaymentTimestamp($salary);
            $amountLeft = (float) $salary->loan_repayment;

            foreach ($loans as $loan) {
                if ($amountLeft <= 0) {
                    break;
                }

                if ($paymentAt->lt($loan->created_at)) {
                    continue;
                }

                $principal = $this->principalForLoan($loan);
                $alreadyPaid = (float) ($allocations[$loan->id] ?? 0);
                $capacity = max(0, round($principal - $alreadyPaid, 2));

                if ($capacity <= 0) {
                    continue;
                }

                $applied = min($amountLeft, $capacity);
                $allocations[$loan->id] = round($alreadyPaid + $applied, 2);
                $amountLeft = round($amountLeft - $applied, 2);
            }
        }

        return $allocations;
    }

    private function salaryRepaymentTimestamp(Salary $salary): Carbon
    {
        if (! empty($salary->payment_date)) {
            return Carbon::parse($salary->payment_date)->endOfDay();
        }

        return Carbon::parse($salary->updated_at ?? $salary->created_at ?? now());
    }

    private function principalForLoan(Loan $loan): float
    {
        $requested = max(0, (float) ($loan->requested_amount ?? 0));

        if (Loan::hasInitialApprovedColumn()) {
            $initial = (float) ($loan->initial_approved_amount ?? 0);

            return round(max($requested, $initial), 2);
        }

        return round(max($requested, (float) ($loan->approved_amount ?? 0)), 2);
    }

    private function repairInitialApprovedAmount(Loan $loan): void
    {
        if (! Loan::hasInitialApprovedColumn()) {
            return;
        }

        $requested = max(0, (float) ($loan->requested_amount ?? 0));
        $current = (float) ($loan->initial_approved_amount ?? 0);
        $approved = max(0, (float) ($loan->approved_amount ?? 0));
        $repaired = round(max($requested, $current, $approved), 2);

        if ($repaired > 0) {
            $loan->initial_approved_amount = $repaired;
        }
    }

    private function applyRepayment(int $staffId, float $amount): void
    {
        $remaining = round(max(0, $amount), 2);
        if ($remaining <= 0) {
            return;
        }

        $loans = Loan::where('staff_id', $staffId)
            ->where(function ($query) {
                $query->whereNull('status')
                    ->orWhereRaw('LOWER(TRIM(status)) NOT IN (?, ?)', ['rejected', 'pending']);
            })
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        foreach ($loans as $loan) {
            if ($remaining <= 0) {
                break;
            }

            $this->ensureInitialApprovedAmount($loan);

            $balance = max(0, (float) ($loan->approved_amount ?? 0));
            if ($balance <= 0) {
                continue;
            }

            $deduct = min($remaining, $balance);
            $newBalance = max(0, round($balance - $deduct, 2));

            $loan->approved_amount = $newBalance;
            if ($newBalance <= 0) {
                $loan->status = 'Completed';
            }

            $loan->save();
            $remaining = round($remaining - $deduct, 2);
        }
    }

    /**
     * Monthly installment = loan total ÷ instalments (full amount), never more than remaining.
     * Example: 500 / 1 = 500, 500 / 2 = 250 per month.
     */
    private function monthlyInstallment(Loan $loan): float
    {
        return $this->monthlyInstallmentFromBalance(
            $loan,
            max(0, (float) ($loan->approved_amount ?? 0))
        );
    }

    private function monthlyInstallmentFromBalance(Loan $loan, float $remainingBalance): float
    {
        $remaining = max(0, $remainingBalance);
        if ($remaining <= 0) {
            return 0.0;
        }

        $totalInstalments = max(1, (int) ($loan->repayment_instalments ?? 1));
        $initial = $this->initialPrincipal($loan);
        if ($initial <= 0) {
            return round($remaining, 2);
        }

        // Full monthly share of the original loan — not limited by this month's salary.
        $fixedInstallment = round($initial / $totalInstalments, 2);
        if ($fixedInstallment <= 0) {
            return round($remaining, 2);
        }

        // Last month (or single instalment): take whatever balance is left.
        return round(min($remaining, $fixedInstallment), 2);
    }

    private function initialPrincipal(Loan $loan): float
    {
        if (Loan::hasInitialApprovedColumn()) {
            $initial = (float) ($loan->initial_approved_amount ?? 0);
            if ($initial > 0) {
                return $initial;
            }
        }

        $requested = max(0, (float) ($loan->requested_amount ?? 0));
        $approved = max(0, (float) ($loan->approved_amount ?? 0));

        return max($requested, $approved);
    }

    private function ensureInitialApprovedAmount(Loan $loan): void
    {
        if (! Loan::hasInitialApprovedColumn()) {
            return;
        }

        if ($loan->initial_approved_amount !== null && (float) $loan->initial_approved_amount > 0) {
            return;
        }

        $loan->initial_approved_amount = max(
            (float) ($loan->requested_amount ?? 0),
            (float) ($loan->approved_amount ?? 0)
        );

        if ((float) $loan->initial_approved_amount > 0) {
            $loan->saveQuietly();
        }
    }
}
