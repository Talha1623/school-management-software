<?php

namespace App\Services;

use App\Models\Loan;
use App\Models\Salary;

class StaffLoanRepaymentService
{
    /**
     * Calculate total loan installment due for a staff member's next salary.
     * Each approved loan is calculated from its own balance and instalment plan.
     */
    public function calculate(int $staffId, ?int $forSalaryId = null): float
    {
        $approvedLoans = Loan::where('staff_id', $staffId)
            ->where('status', 'Approved')
            ->where('approved_amount', '>', 0)
            ->orderBy('created_at')
            ->get();

        if ($approvedLoans->isEmpty()) {
            return 0.0;
        }

        $totalLoanRepayment = 0.0;

        foreach ($approvedLoans as $loan) {
            $totalLoanRepayment += $this->monthlyInstallment($loan);
        }

        return round($totalLoanRepayment, 2);
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
     * Rebuild remaining approved amounts from salary repayment history.
     */
    public function syncAllLoanBalances(): void
    {
        Loan::ensureBalanceColumns();

        $loansByStaff = Loan::orderBy('created_at')->get()->groupBy('staff_id');

        foreach ($loansByStaff as $staffId => $loans) {
            $totalRepaid = (float) Salary::where('staff_id', $staffId)
                ->where('loan_repayment', '>', 0)
                ->where(function ($query) {
                    $query->where('amount_paid', '>', 0)
                        ->orWhereIn('status', ['Paid', 'Issued']);
                })
                ->sum('loan_repayment');

            foreach ($loans as $loan) {
                $this->ensureInitialApprovedAmount($loan);

                $initial = Loan::hasInitialApprovedColumn()
                    ? (float) $loan->initial_approved_amount
                    : (float) ($loan->approved_amount ?? $loan->requested_amount ?? 0);
                $allocated = min($totalRepaid, $initial);
                $remaining = max(0, round($initial - $allocated, 2));

                $loan->approved_amount = $remaining;
                if ($remaining <= 0 && $loan->status === 'Approved') {
                    $loan->status = 'Completed';
                } elseif ($remaining > 0 && $loan->status === 'Completed') {
                    $loan->status = 'Approved';
                }

                $loan->saveQuietly();
                $totalRepaid = max(0, $totalRepaid - $allocated);
            }
        }
    }

    private function applyRepayment(int $staffId, float $amount): void
    {
        $remaining = round(max(0, $amount), 2);
        if ($remaining <= 0) {
            return;
        }

        $loans = Loan::where('staff_id', $staffId)
            ->where('status', 'Approved')
            ->orderBy('created_at')
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
     * Next instalment for one loan: initial / instalments, capped by remaining balance.
     */
    private function monthlyInstallment(Loan $loan): float
    {
        $remaining = max(0, (float) ($loan->approved_amount ?? 0));
        if ($remaining <= 0) {
            return 0.0;
        }

        $totalInstalments = max(1, (int) $loan->repayment_instalments);
        $initial = $this->initialPrincipal($loan);
        if ($initial <= 0) {
            return 0.0;
        }

        $fixedInstallment = round($initial / $totalInstalments, 2);
        if ($fixedInstallment <= 0) {
            return round($remaining, 2);
        }

        $repaid = round(max(0, $initial - $remaining), 2);
        $installmentsPaid = min(
            $totalInstalments,
            (int) round($repaid / $fixedInstallment)
        );

        if ($installmentsPaid >= $totalInstalments) {
            return 0.0;
        }

        // Last instalment takes whatever balance is left (handles rounding).
        if ($installmentsPaid >= $totalInstalments - 1) {
            return round($remaining, 2);
        }

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
            (float) ($loan->approved_amount ?? 0),
            (float) ($loan->requested_amount ?? 0)
        );
    }
}
