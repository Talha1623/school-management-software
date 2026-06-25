<?php

namespace App\Services;

use App\Models\MonthlyFee;
use App\Models\ParentAccount;
use App\Models\Student;
use App\Models\StudentDiscount;
use App\Models\StudentPayment;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Fee Payment web screen: same rules as GET /fee-payment/search-student (routes/web.php).
 * Payment queries use the live student_payments table (no ledgerActive) so API matches the web UI.
 */
class FeePaymentWebTables
{
    private static function normalizedStudentCode(string $studentCode): string
    {
        return strtolower(trim($studentCode));
    }

    /**
     * @param Collection<int, StudentPayment> $payments
     * @return array<int, array<string, mixed>>
     */
    private static function mapPaymentTransactions(Collection $payments): array
    {
        return $payments
            ->sortBy(function ($payment) {
                $date = $payment->payment_date ?? $payment->created_at;

                return ($date ? strtotime((string) $date) : 0) * 1000000 + (int) ($payment->id ?? 0);
            })
            ->values()
            ->map(function (StudentPayment $payment) {
                $gross = (float) ($payment->payment_amount ?? 0);
                $late = (float) ($payment->late_fee ?? 0);
                $discount = (float) ($payment->discount ?? 0);
                $cashPaid = max(0, $gross - $late);

                return [
                    'id' => $payment->id,
                    'payment_title' => $payment->payment_title,
                    'payment_amount' => round($gross, 2),
                    'cash_paid_amount' => round($cashPaid + $late, 2),
                    'principal_paid' => round($cashPaid, 2),
                    'discount' => round($discount, 2),
                    'late_fee' => round($late, 2),
                    'paid_with_discount' => round($cashPaid + $late + $discount, 2),
                    'method' => $payment->method,
                    'payment_date' => $payment->payment_date ? $payment->payment_date->format('Y-m-d') : null,
                    'payment_date_formatted' => $payment->payment_date ? $payment->payment_date->format('d M Y') : null,
                    'accountant' => $payment->accountant,
                ];
            })
            ->all();
    }

    /**
     * Web final-payment flow can update the original Generated row into a paid
     * ledger row. In that case there is no Generated row left to group by, so
     * build a cleared row directly from the paid ledger rows for that title.
     *
     * @param Collection<int, StudentPayment> $paidForTitle
     * @return array<string, mixed>
     */
    private static function buildPaidOnlyFeeRow(string $title, Collection $paidForTitle): array
    {
        $principalPaid = (float) $paidForTitle->sum(function ($item) {
            $amount = (float) ($item->payment_amount ?? 0);
            $late = (float) ($item->late_fee ?? 0);

            return max(0, $amount - $late);
        });
        $paidLate = (float) $paidForTitle->sum(fn ($item) => (float) ($item->late_fee ?? 0));
        $paidDiscount = (float) $paidForTitle->sum(fn ($item) => (float) ($item->discount ?? 0));
        $originalAmount = round($principalPaid + $paidDiscount, 2);
        $generatedFee = round(max(0, $originalAmount - $paidDiscount) + $paidLate, 2);
        $totalPaid = round($principalPaid + $paidDiscount + $paidLate, 2);

        $latestPayment = $paidForTitle->sortByDesc(function ($payment) {
            $date = $payment->payment_date ?? $payment->created_at;

            return ($date ? strtotime((string) $date) : 0) * 1000000 + (int) ($payment->id ?? 0);
        })->first();

        return [
            'title' => $title,
            'total' => $originalAmount,
            'discount' => round($paidDiscount, 2),
            'payment_discount' => round($paidDiscount, 2),
            'student_discount' => 0.0,
            'generated_discount' => 0.0,
            'late_fee' => round($paidLate, 2),
            'paid' => $totalPaid,
            'cash_paid' => round($principalPaid + $paidLate, 2),
            'paid_with_discount' => $totalPaid,
            'due' => 0.0,
            'amount' => 0.0,
            'remaining_late' => 0.0,
            'generated_fee' => $generatedFee,
            'generated_id' => null,
            'payment_id' => $latestPayment?->id,
            'last_payment_date' => $latestPayment?->payment_date,
            'last_payment_discount' => round((float) ($latestPayment?->discount ?? 0), 2),
            'payment_transactions' => self::mapPaymentTransactions($paidForTitle),
            'is_installment' => false,
        ];
    }

    /**
     * Same queries as fee-payment/search-student closure in web.php.
     *
     * @return array{generated: Collection<int, StudentPayment>, paid: Collection<int, StudentPayment>, student_discount_total: float}
     */
    private static function loadStudentPaymentBuckets(Student $student): array
    {
        if (empty($student->student_code)) {
            return [
                'generated' => collect(),
                'paid' => collect(),
                'student_discount_total' => 0.0,
            ];
        }

        $norm = self::normalizedStudentCode((string) $student->student_code);

        $generatedFees = StudentPayment::query()
            ->whereRaw('LOWER(TRIM(student_code)) = ?', [$norm])
            ->whereIn('method', ['Generated', 'Installment'])
            ->get();

        $paidFees = StudentPayment::query()
            ->whereRaw('LOWER(TRIM(student_code)) = ?', [$norm])
            ->where('method', '!=', 'Generated')
            ->where('method', '!=', 'Installment')
            ->get();

        $totalStudentDiscount = (float) StudentDiscount::query()
            ->whereRaw('LOWER(TRIM(student_code)) = ?', [$norm])
            ->get()
            ->sum(fn ($discount) => (float) ($discount->discount_amount ?? 0));

        return [
            'generated' => $generatedFees,
            'paid' => $paidFees,
            'student_discount_total' => $totalStudentDiscount,
        ];
    }

    /**
     * Core fee-row math — mirrors web.php search-student (installments, student discount, paidLedgerRowsForLatestGeneratedTitle).
     *
     * @return array<int, array<string, mixed>>
     */
    private static function buildFeeRowsFromBuckets(
        Collection $generatedFees,
        Collection $paidFees,
        float $totalStudentDiscount,
        bool $outstandingOnly = true,
    ): array {
        $feeRows = [];
        $generatedByTitle = $generatedFees->groupBy('payment_title');
        $paidByTitle = $paidFees->groupBy('payment_title');

        $installmentBaseTitles = [];
        foreach ($generatedByTitle->keys()->merge($paidByTitle->keys())->unique() as $title) {
            if (preg_match('/^(.+)\/\d+$/', (string) $title, $matches)) {
                $installmentBaseTitles[$matches[1]] = true;
            }
        }

        foreach ($generatedByTitle as $title => $items) {
            $latestGenerated = $items->sortByDesc('id')->first();
            $isInstallment = (bool) preg_match('/\/\d+$/', (string) $title);

            if (!$isInstallment && isset($installmentBaseTitles[(string) $title])) {
                continue;
            }

            $isMonthlyFee = str_starts_with((string) $title, 'Monthly Fee - ');

            $allPaidForTitle = $paidByTitle->get($title, collect());
            $paidForTitle = StudentPayment::paidLedgerRowsForLatestGeneratedTitle(
                $allPaidForTitle,
                $latestGenerated
            );

            $originalAmount = (float) $items->sum(fn ($item) => (float) ($item->payment_amount ?? 0));

            $generatedLate = (float) $items->sum(fn ($item) => (float) ($item->late_fee ?? 0));

            $rowGeneratedDiscount = (float) $items->sum(fn ($item) => (float) ($item->discount ?? 0));
            if ($isInstallment) {
                $generatedDiscount = $rowGeneratedDiscount;
            } elseif ($isMonthlyFee) {
                // Monthly: student discount table; avoid doubling invoice discount on row
                $generatedDiscount = 0.0;
            } else {
                // Admission, Transport, Custom, etc. — discount on Generated row
                $generatedDiscount = $rowGeneratedDiscount;
            }

            // Due / Generated Fee math: scoped receipts only (same as web.php search-student).
            $paidDiscountScoped = (float) $paidForTitle->sum(fn ($item) => (float) ($item->discount ?? 0));
            // Dis column display: all receipts on this title (partial payments may split discount across rows).
            $paidDiscountAll = (float) $allPaidForTitle->sum(fn ($item) => (float) ($item->discount ?? 0));
            $paidDiscount = $paidDiscountScoped;

            $appliedStudentDiscount = 0.0;
            if ($isMonthlyFee && $totalStudentDiscount > 0 && !$isInstallment) {
                $appliedStudentDiscount = round($totalStudentDiscount, 2);
            } elseif ($isMonthlyFee && !$isInstallment && $rowGeneratedDiscount > 0) {
                $generatedDiscount = $rowGeneratedDiscount;
            }

            $totalDiscount = $generatedDiscount + $paidDiscount + $appliedStudentDiscount;
            $generatedFee = max(0, $originalAmount - $totalDiscount) + $generatedLate;

            $paidAmount = (float) $paidForTitle->sum(function ($item) {
                $amount = (float) ($item->payment_amount ?? 0);
                $late = (float) ($item->late_fee ?? 0);
                $principal = max(0, $amount - $late);

                return $principal + (float) ($item->discount ?? 0);
            });
            $paidAmountOnly = (float) $paidForTitle->sum(function ($item) {
                $amount = (float) ($item->payment_amount ?? 0);
                $late = (float) ($item->late_fee ?? 0);

                return max(0, $amount - $late);
            });

            $paidLate = (float) $paidForTitle->sum(fn ($item) => (float) ($item->late_fee ?? 0));
            $totalPaid = $paidAmount + $paidLate;

            $remainingAmount = max(0, round(($originalAmount - $totalDiscount) - $paidAmountOnly, 2));
            $remainingLate = max(0, round($generatedLate - $paidLate, 2));
            $remainingTotal = round($remainingAmount + $remainingLate, 2);

            // Partial payment: discount sometimes clears the bill without discount stored on receipt.discount.
            if ($remainingTotal <= 0.01) {
                $inferredPaidDiscount = max(
                    0,
                    round($originalAmount - $appliedStudentDiscount - $generatedDiscount - $paidAmountOnly, 2)
                );
                if ($inferredPaidDiscount > $paidDiscount + 0.00001) {
                    $paidDiscount = $inferredPaidDiscount;
                    $totalDiscount = $generatedDiscount + $paidDiscount + $appliedStudentDiscount;
                    $generatedFee = max(0, $originalAmount - $totalDiscount) + $generatedLate;
                    $remainingAmount = 0.0;
                    $remainingLate = 0.0;
                    $remainingTotal = 0.0;
                }
            }

            $isOutstanding = $remainingTotal > 0.01;
            if ($outstandingOnly && !$isOutstanding) {
                continue;
            }
            if (!$outstandingOnly && $isOutstanding) {
                continue;
            }

            $displayDiscount = $isInstallment ? $generatedDiscount : $totalDiscount;
            $displayLateFee = $isOutstanding ? $remainingLate : $generatedLate;

            $latestPayment = $paidForTitle->sortByDesc(function ($payment) {
                $date = $payment->payment_date ?? $payment->created_at;

                return ($date ? strtotime((string) $date) : 0) * 1000000 + (int) ($payment->id ?? 0);
            })->first();

            $feeRows[] = [
                'title' => $title,
                'total' => round($originalAmount, 2),
                'discount' => round($displayDiscount, 2),
                'payment_discount' => round($paidDiscountAll, 2),
                'student_discount' => round($appliedStudentDiscount, 2),
                'generated_discount' => round($generatedDiscount, 2),
                'late_fee' => round($displayLateFee, 2),
                'paid' => round($totalPaid, 2),
                'cash_paid' => round($paidAmountOnly + $paidLate, 2),
                'paid_with_discount' => round($totalPaid, 2),
                'due' => round($remainingTotal, 2),
                'amount' => round($remainingAmount, 2),
                'remaining_late' => round($remainingLate, 2),
                'generated_fee' => round($generatedFee, 2),
                'generated_id' => $latestGenerated?->id,
                'payment_id' => $latestPayment?->id,
                'last_payment_date' => $latestPayment?->payment_date,
                'last_payment_discount' => round((float) ($latestPayment?->discount ?? 0), 2),
                'payment_transactions' => self::mapPaymentTransactions($paidForTitle),
                'is_installment' => $isInstallment,
            ];
        }

        return $feeRows;
    }

    /**
     * When monthly fee is overdue and still unpaid, add configured late fee to due (same as voucher).
     *
     * @param array<int, array<string, mixed>> $feeRows
     * @param Collection<int, StudentPayment> $generatedFees
     * @return array<int, array<string, mixed>>
     */
    private static function applyAutoLateFeeToOutstandingRows(Student $student, array $feeRows, Collection $generatedFees): array
    {
        $feeRows = self::applyMonthlyAutoLateFeeToBaseRows($student, $feeRows, $generatedFees);
        self::applyLateFeeToInstallmentRows($student, $feeRows, $generatedFees);

        return $feeRows;
    }

    /**
     * Base fee title from an installment title (e.g. "Admission Fee/2" → "Admission Fee").
     */
    public static function installmentBaseTitle(string $title): ?string
    {
        $title = trim($title);
        if ($title === '' || !preg_match('/^(.+)\/\d+$/', $title, $matches)) {
            return null;
        }

        return $matches[1];
    }

    /**
     * How many installment rows exist for a base fee title.
     *
     * @param Collection<int, StudentPayment> $generatedFees
     */
    public static function installmentCountForBase(Collection $generatedFees, string $baseTitle): int
    {
        $escaped = preg_quote(trim($baseTitle), '/');

        return max(1, (int) $generatedFees
            ->filter(fn ($fee) => preg_match(
                '/^'.$escaped.'\/\d+$/i',
                trim((string) ($fee->payment_title ?? ''))
            ))
            ->pluck('payment_title')
            ->unique()
            ->count());
    }

    /**
     * Split a total amount equally across installments (last installment absorbs rounding).
     */
    public static function divideInstallmentSlice(float $total, int $installmentIndex, int $totalInstallments): float
    {
        if ($total <= 0.0001 || $totalInstallments < 1) {
            return 0.0;
        }

        $perInstallment = round($total / $totalInstallments, 2);
        if ($installmentIndex >= $totalInstallments) {
            return max(0, round($total - ($perInstallment * ($totalInstallments - 1)), 2));
        }

        return $perInstallment;
    }

    /**
     * Total late fee for a base fee title (DB stored, configured monthly overdue, or fee-row math).
     *
     * @param Collection<int, StudentPayment> $generatedFees
     */
    public static function resolveBaseFeeLateTotal(Student $student, string $baseTitle, Collection $generatedFees): float
    {
        $baseTitle = trim($baseTitle);
        if ($baseTitle === '') {
            return 0.0;
        }

        $storedOnBase = (float) $generatedFees
            ->filter(fn ($fee) => strcasecmp(trim((string) ($fee->payment_title ?? '')), $baseTitle) === 0)
            ->sum(fn ($fee) => (float) ($fee->late_fee ?? 0));
        if ($storedOnBase > 0.0001) {
            return round($storedOnBase, 2);
        }

        $configured = self::configuredMonthlyLateFeeForTitle($student, $baseTitle);
        if ($configured !== null) {
            return $configured;
        }

        return self::outstandingLateFromBuiltRows($student, $baseTitle);
    }

    /**
     * Total principal (invoice amount before late) for a base fee title.
     *
     * @param Collection<int, StudentPayment> $generatedFees
     */
    public static function resolveBaseFeePrincipalTotal(Student $student, string $baseTitle, Collection $generatedFees): float
    {
        $baseTitle = trim($baseTitle);
        if ($baseTitle === '') {
            return 0.0;
        }

        $storedOnBase = (float) $generatedFees
            ->filter(fn ($fee) => strcasecmp(trim((string) ($fee->payment_title ?? '')), $baseTitle) === 0)
            ->sum(fn ($fee) => (float) ($fee->payment_amount ?? 0));
        if ($storedOnBase > 0.0001) {
            return round($storedOnBase, 2);
        }

        $escaped = preg_quote($baseTitle, '/');
        $onInstallments = (float) $generatedFees
            ->filter(fn ($fee) => preg_match('/^'.$escaped.'\/\d+$/i', trim((string) ($fee->payment_title ?? ''))))
            ->sum(fn ($fee) => (float) ($fee->payment_amount ?? 0));
        if ($onInstallments > 0.0001) {
            return round($onInstallments, 2);
        }

        return self::outstandingPrincipalFromBuiltRows($student, $baseTitle);
    }

    /**
     * @param Collection<int, StudentPayment> $generatedFees
     * @param array<int, array<string, mixed>> $feeRows
     */
    private static function applyLateFeeToInstallmentRows(Student $student, array &$feeRows, Collection $generatedFees): void
    {
        foreach ($feeRows as &$row) {
            if (empty($row['is_installment'])) {
                continue;
            }

            $title = trim((string) ($row['title'] ?? ''));
            $baseTitle = self::installmentBaseTitle($title);
            if ($baseTitle === null) {
                continue;
            }

            $principalDue = (float) ($row['amount'] ?? 0);
            $remainingLate = (float) ($row['remaining_late'] ?? 0);
            if ($principalDue <= 0.0001 || $remainingLate > 0.0001) {
                continue;
            }

            $storedLate = (float) $generatedFees
                ->filter(fn ($fee) => strcasecmp(trim((string) ($fee->payment_title ?? '')), $title) === 0)
                ->sum(fn ($fee) => (float) ($fee->late_fee ?? 0));
            if ($storedLate > 0.0001) {
                continue;
            }

            $totalLate = self::resolveBaseFeeLateTotal($student, $baseTitle, $generatedFees);
            if ($totalLate <= 0.0001) {
                continue;
            }

            $installmentCount = self::installmentCountForBase($generatedFees, $baseTitle);
            $perInstallmentLate = round($totalLate / $installmentCount, 2);

            $row['remaining_late'] = $perInstallmentLate;
            $row['late_fee'] = $perInstallmentLate;
            $row['due'] = round($principalDue + $perInstallmentLate, 2);
            $row['generated_fee'] = round((float) ($row['generated_fee'] ?? 0) + $perInstallmentLate, 2);
        }
        unset($row);
    }

    private static function outstandingLateFromBuiltRows(Student $student, string $baseTitle): float
    {
        $buckets = self::loadStudentPaymentBuckets($student);
        $rows = self::buildFeeRowsFromBuckets(
            $buckets['generated'],
            $buckets['paid'],
            $buckets['student_discount_total'],
            true
        );
        $rows = self::applyMonthlyAutoLateFeeToBaseRows($student, $rows, $buckets['generated']);

        foreach ($rows as $row) {
            if (strcasecmp(trim((string) ($row['title'] ?? '')), trim($baseTitle)) === 0) {
                return max(0, round((float) ($row['remaining_late'] ?? $row['late_fee'] ?? 0), 2));
            }
        }

        return 0.0;
    }

    private static function outstandingPrincipalFromBuiltRows(Student $student, string $baseTitle): float
    {
        $buckets = self::loadStudentPaymentBuckets($student);
        $rows = self::buildFeeRowsFromBuckets(
            $buckets['generated'],
            $buckets['paid'],
            $buckets['student_discount_total'],
            true
        );
        $rows = self::applyMonthlyAutoLateFeeToBaseRows($student, $rows, $buckets['generated']);

        foreach ($rows as $row) {
            if (strcasecmp(trim((string) ($row['title'] ?? '')), trim($baseTitle)) === 0) {
                $due = (float) ($row['due'] ?? 0);
                $late = (float) ($row['remaining_late'] ?? $row['late_fee'] ?? 0);

                return max(0, round((float) ($row['amount'] ?? max(0, $due - $late)), 2));
            }
        }

        return 0.0;
    }

    /**
     * Auto late fee on overdue monthly fee rows (non-installment titles only).
     *
     * @param array<int, array<string, mixed>> $feeRows
     * @param Collection<int, StudentPayment> $generatedFees
     * @return array<int, array<string, mixed>>
     */
    private static function applyMonthlyAutoLateFeeToBaseRows(Student $student, array $feeRows, Collection $generatedFees): array
    {
        $today = Carbon::today();

        foreach ($feeRows as &$row) {
            $title = (string) ($row['title'] ?? '');
            if (!preg_match('/^Monthly Fee - (\w+) (\d{4})$/i', $title, $matches)) {
                continue;
            }

            $principalDue = (float) ($row['amount'] ?? 0);
            $remainingLate = (float) ($row['remaining_late'] ?? 0);
            if ($principalDue <= 0.0001 || $remainingLate > 0.0001) {
                continue;
            }

            $storedLate = (float) $generatedFees
                ->filter(fn ($fee) => strcasecmp(trim((string) ($fee->payment_title ?? '')), $title) === 0)
                ->sum(fn ($fee) => (float) ($fee->late_fee ?? 0));
            if ($storedLate > 0.0001) {
                continue;
            }

            $configuredLate = self::configuredMonthlyLateFeeAmount($student, $matches[1], $matches[2]);
            if ($configuredLate === null) {
                continue;
            }

            $row['remaining_late'] = $configuredLate;
            $row['late_fee'] = $configuredLate;
            $row['due'] = round($principalDue + $configuredLate, 2);
            $row['generated_fee'] = round((float) ($row['generated_fee'] ?? 0) + $configuredLate, 2);
        }
        unset($row);

        return $feeRows;
    }

    /**
     * Configured monthly late fee when overdue (null if not applicable).
     */
    public static function configuredMonthlyLateFeeForTitle(Student $student, string $baseTitle): ?float
    {
        if (!preg_match('/^Monthly Fee - (\w+) (\d{4})$/i', trim($baseTitle), $matches)) {
            return null;
        }

        return self::configuredMonthlyLateFeeAmount($student, $matches[1], $matches[2]);
    }

    /**
     * @internal
     */
    private static function configuredMonthlyLateFeeAmount(Student $student, string $month, string $year): ?float
    {
        $monthlyFeeRecord = MonthlyFee::where('fee_month', $month)
            ->where('fee_year', $year)
            ->where(function ($q) use ($student) {
                $q->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($student->campus ?? ''))])
                    ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($student->class ?? ''))])
                    ->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($student->section ?? ''))]);
            })
            ->first();

        if (!$monthlyFeeRecord) {
            return null;
        }

        $configuredLate = (float) ($monthlyFeeRecord->late_fee ?? 0);
        if ($configuredLate <= 0.0001) {
            return null;
        }

        $dueDate = $monthlyFeeRecord->due_date
            ? Carbon::parse($monthlyFeeRecord->due_date)
            : null;
        if (!$dueDate || !Carbon::today()->gt($dueDate->copy()->startOfDay())) {
            return null;
        }

        return round($configuredLate, 2);
    }

    /**
     * Outstanding late for a fee title (includes auto late on overdue monthly fees).
     */
    public static function outstandingLateForTitle(Student $student, string $title): float
    {
        $generatedFees = StudentPayment::query()
            ->where('student_code', $student->student_code)
            ->whereIn('method', ['Generated', 'Installment'])
            ->get();

        return self::resolveBaseFeeLateTotal($student, trim($title), $generatedFees);
    }

    /**
     * Outstanding principal for a fee title (before late fee).
     */
    public static function outstandingPrincipalForTitle(Student $student, string $title): float
    {
        $generatedFees = StudentPayment::query()
            ->where('student_code', $student->student_code)
            ->whereIn('method', ['Generated', 'Installment'])
            ->get();

        $principal = self::resolveBaseFeePrincipalTotal($student, trim($title), $generatedFees);
        if ($principal > 0.0001) {
            return $principal;
        }

        foreach (self::searchResultsForStudent($student)['rows'] as $row) {
            $rowTitle = trim((string) ($row['fee_type'] ?? ''));
            if (strcasecmp($rowTitle, trim($title)) === 0) {
                $due = (float) ($row['due'] ?? 0);
                $late = (float) ($row['remaining_late'] ?? $row['late_fee'] ?? 0);

                return max(0, round((float) ($row['amount'] ?? max(0, $due - $late)), 2));
            }
        }

        return 0.0;
    }

    /**
     * Persist divided late fee on installment generated rows when missing (fixes older splits).
     *
     * @param array<int, array<string, mixed>> $feeRows
     * @param Collection<int, StudentPayment> $generatedFees
     */
    private static function syncInstallmentLateFeesInDatabase(
        Student $student,
        array $feeRows,
        Collection $generatedFees
    ): void {
        if (empty($student->student_code)) {
            return;
        }

        foreach ($feeRows as $row) {
            if (empty($row['is_installment'])) {
                continue;
            }

            $title = trim((string) ($row['title'] ?? ''));
            $baseTitle = self::installmentBaseTitle($title);
            if ($baseTitle === null) {
                continue;
            }

            $expectedLate = (float) ($row['remaining_late'] ?? $row['late_fee'] ?? 0);
            if ($expectedLate <= 0.0001) {
                $totalLate = self::resolveBaseFeeLateTotal($student, $baseTitle, $generatedFees);
                if ($totalLate > 0.0001) {
                    $expectedLate = round(
                        $totalLate / self::installmentCountForBase($generatedFees, $baseTitle),
                        2
                    );
                }
            }
            if ($expectedLate <= 0.0001) {
                continue;
            }

            $storedLate = (float) $generatedFees
                ->filter(fn ($fee) => strcasecmp(trim((string) ($fee->payment_title ?? '')), $title) === 0)
                ->sum(fn ($fee) => (float) ($fee->late_fee ?? 0));
            if ($storedLate > 0.0001) {
                continue;
            }

            StudentPayment::query()
                ->where('student_code', $student->student_code)
                ->where('payment_title', $title)
                ->whereIn('method', ['Generated', 'Installment'])
                ->update(['late_fee' => round($expectedLate, 2)]);
        }
    }

    /**
     * @param array<int, array<string, mixed>> $feeRows
     */
    private static function attachLatestPaymentIdsToFeeRows(Student $student, array &$feeRows): void
    {
        foreach ($feeRows as &$feeRow) {
            if (empty($feeRow['title']) || ($feeRow['paid'] ?? 0) <= 0) {
                continue;
            }

            $anchor = null;
            if (!empty($feeRow['generated_id'])) {
                $genRow = StudentPayment::query()->find($feeRow['generated_id']);
                $anchor = $genRow?->created_at;
            }

            if (!empty($feeRow['payment_id']) || !empty($feeRow['last_payment_date'])) {
                continue;
            }

            $title = (string) ($feeRow['title'] ?? '');

            $latestPayment = StudentPayment::query()
                ->where('student_code', $student->student_code)
                ->where('method', '!=', 'Generated')
                ->where('method', '!=', 'Installment')
                ->where('payment_title', $title)
                ->when($anchor, function ($q) use ($anchor) {
                    $q->where(function ($inner) use ($anchor) {
                        $inner->where('created_at', '>=', $anchor)
                            ->orWhere('payment_date', '>=', $anchor);
                    });
                })
                ->orderBy('payment_date', 'desc')
                ->orderBy('id', 'desc')
                ->first();

            if ($latestPayment) {
                $feeRow['payment_id'] = $latestPayment->id;
                $feeRow['last_payment_date'] = $latestPayment->payment_date;
                $feeRow['last_payment_discount'] = round((float) ($latestPayment->discount ?? 0), 2);
            }
        }
        unset($feeRow);
    }

    /**
     * Search Results status badge — Paid, Unpaid, Partial, or Late Due (fee paid, late still pending).
     */
    public static function displayStatusForFeeRow(float $cashPaid, float $due, float $principalDue, float $remainingLate): string
    {
        if ($due <= 0.0001) {
            return 'Paid';
        }
        if ($cashPaid <= 0.0001) {
            return 'Unpaid';
        }
        if ($principalDue <= 0.0001 && $remainingLate > 0.0001) {
            return 'Late Due';
        }

        return 'Partial';
    }

    /**
     * Pay-without-late on a Late Due row: principal is cleared but generated late remains.
     * Reduces late_fee on Generated/Installment rows so the title shows as fully paid.
     *
     * @return array{title: string, waived: float, generated_id: int|null}|null
     */
    public static function waiveRemainingLateForFeeRow(
        Student $student,
        ?string $paymentTitle = null,
        ?int $generatedId = null,
    ): ?array {
        if (empty($student->student_code)) {
            return null;
        }

        $titleFilter = $paymentTitle !== null && trim($paymentTitle) !== ''
            ? trim($paymentTitle)
            : null;

        foreach (self::searchResultsForStudent($student)['rows'] as $row) {
            if ($generatedId !== null && (int) ($row['generated_id'] ?? 0) !== $generatedId) {
                continue;
            }
            if ($titleFilter !== null && (string) ($row['fee_type'] ?? '') !== $titleFilter) {
                continue;
            }

            $principalDue = (float) ($row['amount'] ?? 0);
            $remainingLate = (float) ($row['remaining_late'] ?? 0);

            if ($principalDue > 0.0001 || $remainingLate <= 0.0001) {
                continue;
            }

            $title = (string) ($row['fee_type'] ?? '');
            if ($title === '') {
                return null;
            }

            $waived = self::reduceGeneratedLateFeeByAmount(
                $student,
                $title,
                $generatedId ?? (isset($row['generated_id']) ? (int) $row['generated_id'] : null),
                $remainingLate
            );

            if ($waived <= 0.0001) {
                return null;
            }

            return [
                'title' => $title,
                'waived' => round($waived, 2),
                'generated_id' => isset($row['generated_id']) ? (int) $row['generated_id'] : null,
            ];
        }

        return null;
    }

    /**
     * Generated/Installment row for a fee title (used when converting to a paid ledger row).
     */
    public static function findGeneratedRowForTitle(Student $student, string $title, ?int $generatedId = null): ?StudentPayment
    {
        if (empty($student->student_code) || trim($title) === '') {
            return null;
        }

        $norm = self::normalizedStudentCode((string) $student->student_code);

        if ($generatedId !== null && $generatedId > 0) {
            $byId = StudentPayment::query()
                ->where('id', $generatedId)
                ->whereRaw('LOWER(TRIM(student_code)) = ?', [$norm])
                ->whereIn('method', ['Generated', 'Installment'])
                ->first();

            if ($byId !== null) {
                return $byId;
            }
        }

        return StudentPayment::query()
            ->whereRaw('LOWER(TRIM(student_code)) = ?', [$norm])
            ->whereIn('method', ['Generated', 'Installment'])
            ->where('payment_title', $title)
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Subtract outstanding late from Generated/Installment ledger rows (newest first).
     */
    private static function reduceGeneratedLateFeeByAmount(
        Student $student,
        string $title,
        ?int $generatedId,
        float $amountToWaive,
    ): float {
        $norm = self::normalizedStudentCode((string) $student->student_code);

        $query = StudentPayment::query()
            ->whereRaw('LOWER(TRIM(student_code)) = ?', [$norm])
            ->whereIn('method', ['Generated', 'Installment'])
            ->where('payment_title', $title)
            ->orderByDesc('id');

        if ($generatedId !== null && $generatedId > 0) {
            $query->where('id', $generatedId);
        }

        $remaining = max(0, round($amountToWaive, 2));
        $waived = 0.0;

        foreach ($query->get() as $generated) {
            if ($remaining <= 0.0001) {
                break;
            }

            $late = max(0, (float) ($generated->late_fee ?? 0));
            $cut = min($late, $remaining);

            if ($cut <= 0.0001) {
                continue;
            }

            $generated->late_fee = round($late - $cut, 2);
            $generated->save();

            $waived += $cut;
            $remaining = round($remaining - $cut, 2);
        }

        return round($waived, 2);
    }

    /**
     * @param array<int, array<string, mixed>> $feeRows
     * @return array<int, array<string, mixed>>
     */
    private static function mapFeeRowsToResultRows(Student $student, array $feeRows): array
    {
        $rows = [];
        foreach ($feeRows as $row) {
            $due = (float) ($row['due'] ?? 0);
            $cashPaid = (float) ($row['cash_paid'] ?? 0);
            $principalDue = (float) ($row['amount'] ?? 0);
            $remainingLate = (float) ($row['remaining_late'] ?? 0);

            $rows[] = [
                'student_code' => $student->student_code,
                'student_name' => $student->student_name,
                'parent_name' => $student->father_name,
                'fee_type' => $row['title'],
                'total' => (float) $row['total'],
                'discount' => (float) $row['discount'],
                'payment_discount' => (float) ($row['payment_discount'] ?? 0),
                'student_discount' => (float) ($row['student_discount'] ?? 0),
                'generated_discount' => (float) ($row['generated_discount'] ?? 0),
                'last_payment_discount' => (float) ($row['last_payment_discount'] ?? 0),
                'late_fee' => (float) $row['late_fee'],
                'paid' => round($cashPaid, 2),
                'cash_paid' => round($cashPaid, 2),
                'paid_with_discount' => (float) ($row['paid_with_discount'] ?? $row['paid'] ?? 0),
                'due' => $due,
                'amount' => round($principalDue, 2),
                'remaining_late' => round($remainingLate, 2),
                'generated_fee' => (float) $row['generated_fee'],
                'status' => self::displayStatusForFeeRow($cashPaid, $due, $principalDue, $remainingLate),
                'is_installment' => $row['is_installment'] ?? false,
                'generated_id' => $row['generated_id'] ?? null,
                'payment_id' => $row['payment_id'] ?? null,
                'last_payment_date' => $row['last_payment_date'] ?? null,
                'payment_transactions' => $row['payment_transactions'] ?? [],
            ];
        }

        return $rows;
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array<string, float>
     */
    private static function sumRowTotals(array $rows): array
    {
        return [
            'total' => round(array_sum(array_column($rows, 'total')), 2),
            'discount' => round(array_sum(array_column($rows, 'discount')), 2),
            'late_fee' => round(array_sum(array_column($rows, 'late_fee')), 2),
            'paid' => round(array_sum(array_column($rows, 'paid')), 2),
            'due' => round(array_sum(array_column($rows, 'due')), 2),
            'generated_fee' => round(array_sum(array_column($rows, 'generated_fee')), 2),
        ];
    }

    /**
     * Outstanding (due > 0) and fully paid fee titles for one student.
     *
     * @return array{
     *   outstanding: array{rows: array<int, array<string, mixed>>, totals: array<string, float>},
     *   paid: array{rows: array<int, array<string, mixed>>, totals: array<string, float>}
     * }
     */
    public static function feeResultsSplitForStudent(Student $student): array
    {
        $emptyTotals = [
            'total' => 0.0,
            'discount' => 0.0,
            'late_fee' => 0.0,
            'paid' => 0.0,
            'due' => 0.0,
            'generated_fee' => 0.0,
        ];

        if (empty($student->student_code)) {
            return [
                'outstanding' => ['rows' => [], 'totals' => $emptyTotals],
                'paid' => ['rows' => [], 'totals' => $emptyTotals],
            ];
        }

        $buckets = self::loadStudentPaymentBuckets($student);
        $generatedFees = $buckets['generated'];
        $paidFees = $buckets['paid'];
        $discountTotal = $buckets['student_discount_total'];

        $outstandingFeeRows = self::buildFeeRowsFromBuckets($generatedFees, $paidFees, $discountTotal, true);
        $paidFeeRows = self::buildFeeRowsFromBuckets($generatedFees, $paidFees, $discountTotal, false);

        $generatedTitles = $generatedFees->pluck('payment_title')
            ->map(fn ($title) => (string) $title)
            ->unique()
            ->values();
        $paidTitlesAlreadyMapped = collect($paidFeeRows)
            ->pluck('title')
            ->map(fn ($title) => (string) $title)
            ->unique()
            ->values();

        foreach ($paidFees->groupBy('payment_title') as $title => $paidForTitle) {
            $title = (string) $title;
            if ($title === '' || $generatedTitles->contains($title) || $paidTitlesAlreadyMapped->contains($title)) {
                continue;
            }

            $paidFeeRows[] = self::buildPaidOnlyFeeRow($title, $paidForTitle);
            $paidTitlesAlreadyMapped->push($title);
        }

        $outstandingFeeRows = self::applyAutoLateFeeToOutstandingRows($student, $outstandingFeeRows, $generatedFees);
        self::syncInstallmentLateFeesInDatabase($student, $outstandingFeeRows, $generatedFees);

        self::attachLatestPaymentIdsToFeeRows($student, $outstandingFeeRows);
        self::attachLatestPaymentIdsToFeeRows($student, $paidFeeRows);

        $outstandingRows = self::mapFeeRowsToResultRows($student, $outstandingFeeRows);
        $paidRows = self::mapFeeRowsToResultRows($student, $paidFeeRows);

        return [
            'outstanding' => [
                'rows' => $outstandingRows,
                'totals' => self::sumRowTotals($outstandingRows),
            ],
            'paid' => [
                'rows' => $paidRows,
                'totals' => self::sumRowTotals($paidRows),
            ],
        ];
    }

    /**
     * Pending fee rows (Search Results table) — same as /fee-payment/search-student per student.
     *
     * @return array{rows: array<int, array<string, mixed>>, totals: array<string, float>}
     */
    public static function searchResultsForStudent(Student $student): array
    {
        return self::feeResultsSplitForStudent($student)['outstanding'];
    }

    /**
     * Footer totals for Fee Payment Search Results table (fee-payment.blade.php grandTotals).
     * Sums only outstanding fee_rows — not cleared/paid titles.
     *
     * @return array{total: float, discount: float, late_fee: float, paid: float, due: float, generated_fee: float}
     */
    public static function webSearchFooterTotalsForStudent(Student $student): array
    {
        $totals = self::searchResultsForStudent($student)['totals'] ?? [];

        return [
            'total' => round((float) ($totals['total'] ?? 0), 2),
            'discount' => round((float) ($totals['discount'] ?? 0), 2),
            'late_fee' => round((float) ($totals['late_fee'] ?? 0), 2),
            'paid' => round((float) ($totals['paid'] ?? 0), 2),
            'due' => round((float) ($totals['due'] ?? 0), 2),
            'generated_fee' => round((float) ($totals['generated_fee'] ?? 0), 2),
        ];
    }

    /**
     * Fully settled fee titles (due = 0) — moves here after payment clears the balance.
     *
     * @return array{rows: array<int, array<string, mixed>>, totals: array<string, float>}
     */
    public static function paidResultsForStudent(Student $student): array
    {
        return self::feeResultsSplitForStudent($student)['paid'];
    }

    /**
     * Fee Payment CNIC search payload — same math as GET /fee-payment/search-by-cnic.
     *
     * @return array{
     *   has_unpaid: bool,
     *   unpaid_amount: float,
     *   pending_fees: array<int, array<string, mixed>>,
     *   fee_rows: array<int, array<string, mixed>>
     * }
     */
    public static function feeSearchPayloadCnicRoute(Student $student): array
    {
        if (empty($student->student_code)) {
            return [
                'has_unpaid' => false,
                'unpaid_amount' => 0.0,
                'pending_fees' => [],
                'fee_rows' => [],
            ];
        }

        $generatedFees = StudentPayment::query()
            ->where('student_code', $student->student_code)
            ->whereIn('method', ['Generated', 'Installment'])
            ->get();

        $paidFees = StudentPayment::query()
            ->where('student_code', $student->student_code)
            ->where('method', '!=', 'Generated')
            ->where('method', '!=', 'Installment')
            ->get();

        $totalStudentDiscount = (float) StudentDiscount::query()
            ->where('student_code', $student->student_code)
            ->get()
            ->sum(fn ($discount) => (float) ($discount->discount_amount ?? 0));

        $pendingFees = [];
        $feeRows = [];
        $totalDue = 0.0;
        $generatedByTitle = $generatedFees->groupBy('payment_title');
        $paidByTitle = $paidFees->groupBy('payment_title');

        $installmentBaseTitles = [];
        foreach ($generatedByTitle as $title => $items) {
            if (preg_match('/^(.+)\/\d+$/', (string) $title, $matches)) {
                $installmentBaseTitles[$matches[1]] = true;
            }
        }

        foreach ($generatedByTitle as $title => $items) {
            $latestGenerated = $items->sortByDesc('id')->first();
            $isInstallment = (bool) preg_match('/\/\d+$/', (string) $title);

            if (!$isInstallment && isset($installmentBaseTitles[(string) $title])) {
                continue;
            }

            $isMonthlyFee = str_starts_with((string) $title, 'Monthly Fee - ');

            $paidForTitle = StudentPayment::paidLedgerRowsForLatestGeneratedTitle(
                $paidByTitle->get($title, collect()),
                $latestGenerated
            );

            $originalAmount = (float) $items->sum(fn ($item) => (float) ($item->payment_amount ?? 0));
            $generatedLate = (float) $items->sum(fn ($item) => (float) ($item->late_fee ?? 0));

            $generatedDiscount = 0.0;
            if ($isInstallment) {
                $generatedDiscount = (float) $items->sum(fn ($item) => (float) ($item->discount ?? 0));
            }

            $paidDiscount = (float) $paidForTitle->sum(fn ($item) => (float) ($item->discount ?? 0));

            $appliedStudentDiscount = 0.0;
            if ($isMonthlyFee && $totalStudentDiscount > 0 && !$isInstallment) {
                $appliedStudentDiscount = round($totalStudentDiscount, 2);
            }

            $totalDiscount = $generatedDiscount + $paidDiscount + $appliedStudentDiscount;
            $generatedFee = max(0, $originalAmount - $totalDiscount) + $generatedLate;

            $paidAmount = (float) $paidForTitle->sum(function ($item) {
                $amount = (float) ($item->payment_amount ?? 0);
                $late = (float) ($item->late_fee ?? 0);
                $principal = max(0, $amount - $late);

                return $principal + (float) ($item->discount ?? 0);
            });
            $paidLate = (float) $paidForTitle->sum(fn ($item) => (float) ($item->late_fee ?? 0));
            $totalPaid = $paidAmount + $paidLate;

            $paidAmountOnly = (float) $paidForTitle->sum(function ($item) {
                $amount = (float) ($item->payment_amount ?? 0);
                $late = (float) ($item->late_fee ?? 0);

                return max(0, $amount - $late);
            });

            $remainingAmount = max(0, ($originalAmount - $totalDiscount) - $paidAmountOnly);
            $remainingLate = max(0, $generatedLate - $paidLate);
            $remainingTotal = $remainingAmount + $remainingLate;

            if ($remainingTotal > 0) {
                $displayDiscount = $isInstallment ? $generatedDiscount : $totalDiscount;

                $feeRows[] = [
                    'title' => $title,
                    'total' => round($originalAmount, 2),
                    'discount' => round($displayDiscount, 2),
                    'late_fee' => round($remainingLate, 2),
                    'paid' => round($totalPaid, 2),
                    'due' => round($remainingTotal, 2),
                    'amount' => round($remainingAmount, 2),
                    'remaining_late' => round($remainingLate, 2),
                    'generated_fee' => round($generatedFee, 2),
                    'generated_id' => $latestGenerated?->id,
                    'is_installment' => $isInstallment,
                ];
                $pendingFees[] = [
                    'title' => $title,
                    'amount' => round($remainingAmount, 2),
                    'late_fee' => round($remainingLate, 2),
                    'total' => round($remainingTotal, 2),
                ];
                $totalDue += $remainingTotal;
            }
        }

        foreach ($feeRows as &$feeRow) {
            if (!empty($feeRow['title']) && ($feeRow['paid'] ?? 0) > 0) {
                $anchor = null;
                if (!empty($feeRow['generated_id'])) {
                    $genRow = StudentPayment::query()->find($feeRow['generated_id']);
                    $anchor = $genRow?->created_at;
                }

                $latestPayment = StudentPayment::query()
                    ->where('student_code', $student->student_code)
                    ->where('payment_title', $feeRow['title'])
                    ->where('method', '!=', 'Generated')
                    ->where('method', '!=', 'Installment')
                    ->when($anchor, function ($q) use ($anchor) {
                        $q->where('created_at', '>=', $anchor);
                    })
                    ->orderBy('payment_date', 'desc')
                    ->orderBy('id', 'desc')
                    ->first();

                if ($latestPayment) {
                    $feeRow['payment_id'] = $latestPayment->id;
                }
            }
        }
        unset($feeRow);

        return [
            'has_unpaid' => $totalDue > 0,
            'unpaid_amount' => round($totalDue, 2),
            'pending_fees' => $pendingFees,
            'fee_rows' => $feeRows,
        ];
    }

    /**
     * Parent Dues Report rows/totals — matches Fee Payment Search Results (CNIC route) per child.
     *
     * @param iterable<int, Student> $students
     * @return array{
     *   rows: \Illuminate\Support\Collection<int, array<string, mixed>>,
     *   total_due: float,
     *   totals: array{total: float, discount: float, late_fee: float, paid: float, due: float, generated_fee: float}
     * }
     */
    public static function parentDuesReportPayload(iterable $students): array
    {
        $rows = collect();
        $totals = [
            'total' => 0.0,
            'discount' => 0.0,
            'late_fee' => 0.0,
            'paid' => 0.0,
            'due' => 0.0,
            'generated_fee' => 0.0,
        ];

        foreach ($students as $student) {
            $payload = self::feeSearchPayloadCnicRoute($student);

            foreach ($payload['fee_rows'] as $fee) {
                $rows->push([
                    'student_code' => $student->student_code,
                    'student_name' => $student->student_name,
                    'class' => $student->class,
                    'section' => $student->section,
                    'fee_type' => $fee['title'] ?? '',
                    'total' => (float) ($fee['total'] ?? 0),
                    'discount' => (float) ($fee['discount'] ?? 0),
                    'late_fee' => (float) ($fee['late_fee'] ?? 0),
                    'paid' => (float) ($fee['paid'] ?? 0),
                    'due' => (float) ($fee['due'] ?? 0),
                    'amount' => (float) ($fee['amount'] ?? 0),
                    'generated_fee' => (float) ($fee['generated_fee'] ?? 0),
                ]);

                $totals['total'] += (float) ($fee['total'] ?? 0);
                $totals['discount'] += (float) ($fee['discount'] ?? 0);
                $totals['late_fee'] += (float) ($fee['late_fee'] ?? 0);
                $totals['paid'] += (float) ($fee['paid'] ?? 0);
                $totals['due'] += (float) ($fee['due'] ?? 0);
                $totals['generated_fee'] += (float) ($fee['generated_fee'] ?? 0);
            }
        }

        foreach ($totals as $key => $value) {
            $totals[$key] = round($value, 2);
        }

        return [
            'rows' => $rows,
            'total_due' => $totals['due'],
            'totals' => $totals,
        ];
    }

    /**
     * Students for a parent account — linked students + father ID card match (Fee Payment CNIC coverage).
     */
    public static function studentsForParentAccount(ParentAccount $parent): Collection
    {
        $students = Student::query()
            ->where('parent_account_id', $parent->id)
            ->get();

        $idCard = trim((string) ($parent->id_card_number ?? ''));
        if ($idCard === '') {
            return $students->sortBy('student_name')->values();
        }

        $normalizedIdCard = str_replace(['-', ' ', '_', '.'], '', strtolower($idCard));

        $studentsByFatherIdCard = Student::query()
            ->where(function ($query) use ($normalizedIdCard, $idCard) {
                $query->whereRaw(
                    'LOWER(REPLACE(REPLACE(REPLACE(REPLACE(TRIM(father_id_card), "-", ""), " ", ""), "_", ""), ".", "")) = ?',
                    [$normalizedIdCard]
                )->orWhereRaw('LOWER(TRIM(father_id_card)) = LOWER(TRIM(?))', [$idCard])
                    ->orWhere('father_id_card', $idCard);
            })
            ->get();

        return $students->merge($studentsByFatherIdCard)
            ->unique('id')
            ->sortBy('student_name')
            ->values();
    }

    public static function parentOutstandingDueTotal(ParentAccount $parent): float
    {
        return (float) self::parentDuesReportPayload(self::studentsForParentAccount($parent))['total_due'];
    }

    /**
     * Fee Payment search JSON payload (fee_rows, unpaid_amount) — used by web routes and API.
     *
     * @return array{
     *   has_unpaid: bool,
     *   unpaid_amount: float,
     *   pending_fees: array<int, array<string, mixed>>,
     *   fee_rows: array<int, array<string, mixed>>,
     *   table_totals: array<string, float>
     * }
     */
    public static function feeSearchPayloadForStudent(Student $student): array
    {
        $table = self::searchResultsForStudent($student);
        $feeRows = [];
        $pendingFees = [];

        foreach ($table['rows'] as $row) {
            $title = (string) ($row['fee_type'] ?? '');
            $due = (float) ($row['due'] ?? 0);
            $lateFee = (float) ($row['late_fee'] ?? 0);
            $amount = (float) ($row['amount'] ?? max(0, $due - $lateFee));
            $cashPaid = (float) ($row['cash_paid'] ?? 0);
            $remainingLate = (float) ($row['remaining_late'] ?? $lateFee);

            $feeRows[] = [
                'title' => $title,
                'total' => round((float) ($row['total'] ?? 0), 2),
                'discount' => round((float) ($row['discount'] ?? 0), 2),
                'late_fee' => round($lateFee, 2),
                'paid' => round($cashPaid, 2),
                'cash_paid' => round($cashPaid, 2),
                'paid_with_discount' => round((float) ($row['paid_with_discount'] ?? $row['paid'] ?? 0), 2),
                'due' => round($due, 2),
                'amount' => round($amount, 2),
                'remaining_late' => round($remainingLate, 2),
                'generated_fee' => round((float) ($row['generated_fee'] ?? 0), 2),
                'generated_id' => $row['generated_id'] ?? null,
                'payment_id' => $row['payment_id'] ?? null,
                'is_installment' => (bool) ($row['is_installment'] ?? false),
                'status' => (string) ($row['status'] ?? self::displayStatusForFeeRow($cashPaid, $due, $amount, $remainingLate)),
            ];

            $pendingFees[] = [
                'title' => $title,
                'amount' => round($amount, 2),
                'late_fee' => round($lateFee, 2),
                'total' => round($due, 2),
            ];
        }

        $unpaidAmount = round((float) ($table['totals']['due'] ?? 0), 2);

        return [
            'has_unpaid' => $unpaidAmount > 0.00001,
            'unpaid_amount' => $unpaidAmount,
            'pending_fees' => $pendingFees,
            'fee_rows' => $feeRows,
            'table_totals' => $table['totals'],
        ];
    }

    /**
     * Same fee/due math as GET /fee-payment/search-student (routes/web.php).
     *
     * @return array{
     *   unpaid_amount: float,
     *   remaining_by_title: array<string, float>,
     *   balances: array<string, array{due: float, total_paid_display: float, generated_fee: float}>,
     *   synthetic_transport_fare_included: float
     * }
     */
    public static function feePaymentSearchStudentMath(Student $student): array
    {
        if (empty($student->student_code)) {
            return [
                'unpaid_amount' => 0.0,
                'remaining_by_title' => [],
                'balances' => [],
                'synthetic_transport_fare_included' => 0.0,
            ];
        }

        $buckets = self::loadStudentPaymentBuckets($student);
        $generatedFees = $buckets['generated'];
        $paidFees = $buckets['paid'];

        $feeRows = self::buildFeeRowsFromBuckets(
            $generatedFees,
            $paidFees,
            $buckets['student_discount_total'],
        );

        $remainingByTitle = [];
        $balances = [];
        foreach ($feeRows as $row) {
            $title = (string) ($row['title'] ?? '');
            $due = (float) ($row['due'] ?? 0);
            $remainingByTitle[$title] = $due;
            $balances[$title] = [
                'due' => $due,
                'total_paid_display' => (float) ($row['paid'] ?? 0),
                'cash_paid' => (float) ($row['cash_paid'] ?? $row['paid'] ?? 0),
                'principal_due' => (float) ($row['amount'] ?? 0),
                'remaining_late' => (float) ($row['remaining_late'] ?? 0),
                'is_installment' => (bool) ($row['is_installment'] ?? false),
                'generated_fee' => (float) ($row['generated_fee'] ?? 0),
            ];
        }

        $tableForDue = self::searchResultsForStudent($student);

        return [
            'unpaid_amount' => (float) ($tableForDue['totals']['due'] ?? 0),
            'remaining_by_title' => $remainingByTitle,
            'balances' => $balances,
            'synthetic_transport_fare_included' => 0.0,
        ];
    }

    /**
     * fee-payment.blade.php Latest Payments: skip unpaid installment stubs only.
     */
    public static function isUnpaidInstallmentPaymentRow(?string $paymentTitle, ?string $method): bool
    {
        $title = strtolower(trim((string) $paymentTitle));
        $paymentMethod = strtolower(trim((string) $method));
        $isInstallment = str_contains($title, 'installment') || (bool) preg_match('/\/\d+$/', $title);

        return $isInstallment && in_array($paymentMethod, ['generated', 'installment'], true);
    }

    /**
     * Fee column in Latest Payments (principal only — gross payment_amount minus late portion).
     */
    public static function latestPaymentPrincipalAmount(float $paymentAmount, float $lateFee): float
    {
        return round(max(0, $paymentAmount - $lateFee), 2);
    }

    /**
     * Latest Payments table — same query as GET /fee-payment/history.
     * Optional fee_year filters by calendar year of payment_date (for app ?fee_year=).
     *
     * @return array{rows: array<int, array<string, mixed>>, total_amount_paid: float, total_late_fee: float, total_discount: float}
     */
    public static function latestPaymentsForStudentCode(string $studentCode, ?int $feeYear = null, ?string $campus = null): array
    {
        $code = strtolower(trim($studentCode));

        $q = StudentPayment::query()
            ->ledgerActive()
            ->join('students', function ($join) {
                $join->on(DB::raw('LOWER(TRIM(student_payments.student_code))'), '=', DB::raw('LOWER(TRIM(students.student_code))'));
            })
            ->whereRaw('LOWER(TRIM(COALESCE(student_payments.method, ""))) <> ?', ['generated'])
            ->whereRaw('LOWER(TRIM(student_payments.student_code)) = ?', [$code]);

        if ($campus !== null && trim($campus) !== '') {
            $q->whereRaw('LOWER(TRIM(students.campus)) = ?', [strtolower(trim($campus))]);
        }

        $q
            ->select(
                'student_payments.id',
                'student_payments.payment_title',
                'student_payments.payment_amount',
                'student_payments.discount',
                'student_payments.late_fee',
                'student_payments.method',
                'student_payments.payment_date',
                'student_payments.accountant',
                'students.student_name',
                'students.father_name',
                'students.student_code'
            )
            ->orderBy('student_payments.payment_date', 'desc');

        if ($feeYear !== null) {
            $q->whereYear('student_payments.payment_date', $feeYear);
        }

        $payments = $q->get()
            ->filter(function ($payment) {
                return ! self::isUnpaidInstallmentPaymentRow(
                    (string) ($payment->payment_title ?? ''),
                    (string) ($payment->method ?? '')
                );
            })
            ->values();

        $rows = $payments->map(function ($payment) {
            $dt = $payment->payment_date ? \Carbon\Carbon::parse($payment->payment_date) : null;
            $gross = (float) ($payment->payment_amount ?? 0);
            $late = (float) ($payment->late_fee ?? 0);
            $discount = (float) ($payment->discount ?? 0);
            $principal = self::latestPaymentPrincipalAmount($gross, $late);
            $feeNet = round(max(0, $principal - $discount), 2);

            return [
                'id' => $payment->id,
                'student_code' => $payment->student_code,
                'student_name' => $payment->student_name,
                'parent_name' => $payment->father_name,
                'title' => $payment->payment_title,
                'amount_paid' => $principal,
                'fee_net' => $feeNet,
                'payment_amount_gross' => round($gross, 2),
                'late_fee' => round($late, 2),
                'discount' => round($discount, 2),
                'payment_date' => $dt ? $dt->format('d-m-Y') : null,
                'payment_time' => $dt ? $dt->format('h:i:s A') : null,
                'payment_datetime' => $dt ? $dt->format('d-m-Y h:i:s A') : null,
                'received_by' => $payment->accountant,
                'method' => $payment->method,
                'status' => 'paid',
            ];
        })->values()->all();

        $totalLateFee = round(array_sum(array_column($rows, 'late_fee')), 2);
        $totalDiscount = round(array_sum(array_column($rows, 'discount')), 2);
        $totalPrincipal = round(array_sum(array_column($rows, 'amount_paid')), 2);
        $totalFeeNet = round(array_sum(array_column($rows, 'fee_net')), 2);
        // Latest Payments Fee column footer = sum of principal (payment_amount − late), discount is its own column.
        $totalAmountPaid = $totalPrincipal;

        return [
            'rows' => $rows,
            'total_amount_paid' => $totalAmountPaid,
            'total_amount_principal' => $totalPrincipal,
            'total_amount_net' => $totalFeeNet,
            'total_late_fee' => $totalLateFee,
            'total_discount' => $totalDiscount,
        ];
    }
}
