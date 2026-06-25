<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\StudentPayment;
use App\Models\StudentDiscount;
use App\Services\FeePaymentWebTables;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudentFeeController extends Controller
{
    private function normalizedStudentCode(string $studentCode): string
    { 
        return strtolower(trim($studentCode));
    }
            
    /**
     * Paid receipt totals — same rules as Fee Payment web "Latest Payments" table.
     *
     * @return array{total_paid: float, total_principal: float, total_discount: float, total_late_fee: float, rows: array<int, array<string, mixed>>}
     */
    private function latestPaymentsTotalsForStudent(Student $student, ?int $feeYear = null): array
    {
        $latest = FeePaymentWebTables::latestPaymentsForStudentCode((string) $student->student_code, $feeYear);
        $rows = collect($latest['rows'] ?? []);

        return [
            'total_paid' => round((float) ($latest['total_amount_paid'] ?? 0), 2),
            'total_principal' => round((float) ($latest['total_amount_principal'] ?? $rows->sum('amount_paid')), 2),
            'total_net' => round((float) ($latest['total_amount_net'] ?? $rows->sum('fee_net')), 2),
            'total_discount' => round((float) ($latest['total_discount'] ?? $rows->sum('discount')), 2),
            'total_late_fee' => round((float) ($latest['total_late_fee'] ?? $rows->sum('late_fee')), 2),
            'rows' => $rows->values()->all(),
        ];
    }

    /**
     * @param array{
     *   due: float,
     *   total_paid_display: float,
     *   cash_paid?: float,
     *   principal_due?: float,
     *   remaining_late?: float,
     *   is_installment?: bool,
     *   generated_fee: float
     * }|null $bal
     */
    private function feeBalanceStatusFromWeb(?array $bal): string
    {
        if ($bal === null) {
            return 'paid';
        }

        $due = (float) ($bal['due'] ?? 0);
        $isInstallment = (bool) ($bal['is_installment'] ?? false);
        if ($isInstallment && $due > 0.00001) {
            return 'installment';
        }

        $cashPaid = (float) ($bal['cash_paid'] ?? $bal['total_paid_display'] ?? 0);
        $principalDue = (float) ($bal['principal_due'] ?? 0);
        $remainingLate = (float) ($bal['remaining_late'] ?? 0);
        $label = FeePaymentWebTables::displayStatusForFeeRow($cashPaid, $due, $principalDue, $remainingLate);

        return match ($label) {
            'Paid' => 'paid',
            'Unpaid' => 'unpaid',
            'Partial' => 'partial',
            'Late Due' => 'late_due',
            default => 'unpaid',
        };
    }

    private function inferFeeTypeFromTitle(string $paymentTitle): string
    {
        if (stripos($paymentTitle, 'Monthly Fee') !== false) {
            return 'monthly_fee';
        }
        if (stripos($paymentTitle, 'Transport Fee') !== false) {
            return 'transport_fee';
        }
        if (stripos($paymentTitle, 'Admission Fee') !== false) {
            return 'admission_fee';
        }
        if (stripos($paymentTitle, 'Custom Fee') !== false || stripos($paymentTitle, 'Library Fee') !== false || stripos($paymentTitle, 'Lab Fee') !== false) {
            return 'custom_fee';
        }

        return 'other';
    }

    /**
     * Status fields aligned with Fee Payment web Status column (Paid / Unpaid / Partial / Late Due / Installment).
     *
     * @return array{
     *   fee_balance_status: string,
     *   status: string,
     *   payment_status: string,
     *   is_paid: bool,
     *   is_unpaid: bool,
     *   is_partial: bool,
     *   is_installment: bool,
     *   is_outstanding: bool,
     *   is_late_due: bool
     * }
     */
    private function statusFieldsFromWebLabel(string $statusLabel, bool $isInstallment, float $due): array
    {
        if ($isInstallment && $due > 0.00001) {
            return [
                'fee_balance_status' => 'installment',
                'status' => 'installment',
                'payment_status' => 'Installment',
                'is_paid' => false,
                'is_unpaid' => true,
                'is_partial' => false,
                'is_installment' => true,
                'is_outstanding' => true,
                'is_late_due' => false,
            ];
        }

        return match (trim($statusLabel)) {
            'Paid' => [
                'fee_balance_status' => 'paid',
                'status' => 'paid',
                'payment_status' => 'Paid',
                'is_paid' => true,
                'is_unpaid' => false,
                'is_partial' => false,
                'is_installment' => false,
                'is_outstanding' => false,
                'is_late_due' => false,
            ],
            'Late Due' => [
                'fee_balance_status' => 'late_due',
                'status' => 'late_due',
                'payment_status' => 'Late Due',
                'is_paid' => false,
                'is_unpaid' => false,
                'is_partial' => false,
                'is_installment' => false,
                'is_outstanding' => true,
                'is_late_due' => true,
            ],
            'Partial' => [
                'fee_balance_status' => 'partial',
                'status' => 'partial',
                'payment_status' => 'Partial',
                'is_paid' => false,
                'is_unpaid' => false,
                'is_partial' => true,
                'is_installment' => false,
                'is_outstanding' => true,
                'is_late_due' => false,
            ],
            'Unpaid' => [
                'fee_balance_status' => 'unpaid',
                'status' => 'unpaid',
                'payment_status' => 'Unpaid',
                'is_paid' => false,
                'is_unpaid' => true,
                'is_partial' => false,
                'is_installment' => false,
                'is_outstanding' => true,
                'is_late_due' => false,
            ],
            default => [
                'fee_balance_status' => 'unpaid',
                'status' => 'unpaid',
                'payment_status' => 'Unpaid',
                'is_paid' => false,
                'is_unpaid' => true,
                'is_partial' => false,
                'is_installment' => false,
                'is_outstanding' => $due > 0.00001,
                'is_late_due' => false,
            ],
        };
    }

    /**
     * One row in payment_history aligned with Fee Payment web "Search Results" table (not raw ledger lines).
     *
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function mapSearchResultRowToPaymentHistory(array $row, Student $student): array
    {
        $title = (string) ($row['fee_type'] ?? '');
        $total = (float) ($row['total'] ?? 0);
        $discount = (float) ($row['discount'] ?? 0);
        $lateFee = (float) ($row['late_fee'] ?? 0);
        $paid = (float) ($row['paid'] ?? 0);
        $cashPaid = (float) ($row['cash_paid'] ?? $paid);
        $paidWithDiscount = (float) ($row['paid_with_discount'] ?? $paid);
        $due = (float) ($row['due'] ?? 0);
        $principalDue = (float) ($row['amount'] ?? 0);
        $remainingLate = (float) ($row['remaining_late'] ?? 0);
        $generatedFee = (float) ($row['generated_fee'] ?? 0);
        $isInstallment = (bool) ($row['is_installment'] ?? false);
        $statusLabel = trim((string) ($row['status'] ?? ''));
        if ($statusLabel === '') {
            $statusLabel = FeePaymentWebTables::displayStatusForFeeRow($cashPaid, $due, $principalDue, $remainingLate);
        }
        if ($statusLabel === 'Unpaid' && $due > 0.00001 && ($cashPaid > 0.00001 || $paid > 0.00001 || $paidWithDiscount > 0.00001)) {
            $statusLabel = 'Partial';
        }
        $statusFields = $this->statusFieldsFromWebLabel($statusLabel, $isInstallment, $due);
        $paymentTransactions = collect($row['payment_transactions'] ?? [])->values();

        $mapped = array_merge([
            'id' => $row['generated_id'] ?? $row['payment_id'] ?? null,
            'payment_title' => $title,
            'payment_amount' => round($total, 2),
            'discount' => round($discount, 2),
            'payment_discount' => round((float) ($row['payment_discount'] ?? 0), 2),
            'student_discount' => round((float) ($row['student_discount'] ?? 0), 2),
            'generated_discount' => round((float) ($row['generated_discount'] ?? 0), 2),
            'last_payment_discount' => round((float) ($row['last_payment_discount'] ?? 0), 2),
            'late_fee' => round($lateFee, 2),
            // Due column (remaining balance) — same as Fee Payment web table "Due"
            'due' => round($due, 2),
            'due_amount' => round($due, 2),
            'net_amount' => round($due, 2),
            // Generated Fee column (Total - Dis + Late Fee on bill)
            'generated_fee' => round($generatedFee, 2),
            'generated_amount' => round($generatedFee, 2),
            'method' => $isInstallment ? 'Installment' : ($due > 0.00001 ? 'Generated' : 'Paid'),
            'fee_type' => $this->inferFeeTypeFromTitle($title),
            'paid_amount' => round($paid, 2),
            'cash_paid_amount' => round($cashPaid, 2),
            'paid_with_discount' => round($paidWithDiscount, 2),
            'discount_applied' => round($discount, 2),
            'balance_after_payment' => round($due, 2),
            'is_installment' => $isInstallment,
            'generated_id' => $row['generated_id'] ?? null,
            'payment_id' => $row['payment_id'] ?? null,
            'payment_transactions' => $paymentTransactions->all(),
            'payment_transactions_count' => $paymentTransactions->count(),
            'last_payment_date' => isset($row['last_payment_date']) && $row['last_payment_date']
                ? Carbon::parse($row['last_payment_date'])->format('Y-m-d')
                : null,
            'last_payment_date_formatted' => isset($row['last_payment_date']) && $row['last_payment_date']
                ? Carbon::parse($row['last_payment_date'])->format('d M Y')
                : null,
            'student_code' => $student->student_code,
            'student_name' => $student->student_name,
            'parent_name' => $student->father_name,
        ], $statusFields);

        return $this->normalizePaymentHistoryRow($mapped);
    }

    /**
     * Ensure every payment row exposes the fields expected by the student app FeeHistory model.
     *
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function normalizePaymentHistoryRow(array $row): array
    {
        $due = round((float) ($row['due_amount'] ?? $row['due'] ?? $row['net_amount'] ?? 0), 2);
        $generatedAmount = round((float) ($row['generated_amount'] ?? $row['generated_fee'] ?? 0), 2);
        if ($generatedAmount <= 0.00001 && isset($row['payment_amount']) && ($row['is_paid'] ?? null) !== true) {
            $generatedAmount = round((float) $row['payment_amount'], 2);
        }
        $paidAmount = round((float) ($row['paid_amount'] ?? $row['paid_with_discount'] ?? 0), 2);
        $cashPaid = round((float) ($row['cash_paid_amount'] ?? $row['cash_paid'] ?? $paidAmount), 2);
        $principalDue = round((float) ($row['amount'] ?? $row['principal_due'] ?? 0), 2);
        $remainingLate = round((float) ($row['remaining_late'] ?? 0), 2);
        $isInstallment = (bool) ($row['is_installment'] ?? false);

        $existingLabel = trim((string) ($row['payment_status'] ?? ''));
        $statusLabel = $existingLabel !== ''
            ? $existingLabel
            : $this->resolvePaymentStatusLabel(
                $row,
                $cashPaid,
                $paidAmount,
                $due,
                $principalDue,
                $remainingLate,
                $isInstallment
            );
        $statusFields = $this->statusFieldsFromWebLabel($statusLabel, $isInstallment, $due);

        $lastPaymentDate = $row['last_payment_date'] ?? $row['payment_date'] ?? null;
        if ($lastPaymentDate instanceof \DateTimeInterface) {
            $lastPaymentDate = Carbon::parse($lastPaymentDate)->format('Y-m-d');
        } elseif (is_string($lastPaymentDate) && strlen($lastPaymentDate) > 10) {
            try {
                $lastPaymentDate = Carbon::parse($lastPaymentDate)->format('Y-m-d');
            } catch (\Throwable) {
                // keep raw value
            }
        }

        $lastPaymentFormatted = $row['last_payment_date_formatted'] ?? $row['payment_date_formatted'] ?? null;
        if (!$lastPaymentFormatted && $lastPaymentDate) {
            try {
                $lastPaymentFormatted = Carbon::parse($lastPaymentDate)->format('d M Y');
            } catch (\Throwable) {
                $lastPaymentFormatted = null;
            }
        }

        $paymentDate = $row['payment_date'] ?? $lastPaymentDate;
        $paymentDateFormatted = $row['payment_date_formatted'] ?? $lastPaymentFormatted;

        return array_merge($row, [
            'payment_amount' => round((float) ($row['payment_amount'] ?? $generatedAmount), 2),
            'discount' => round((float) ($row['discount'] ?? 0), 2),
            'late_fee' => round((float) ($row['late_fee'] ?? 0), 2),
            'net_amount' => round((float) ($row['net_amount'] ?? $due), 2),
            'due_amount' => $due,
            'due' => $due,
            'generated_amount' => $generatedAmount,
            'generated_fee' => $generatedAmount,
            'paid_amount' => $paidAmount,
            'payment_date' => $paymentDate,
            'payment_date_formatted' => $paymentDateFormatted,
            'last_payment_date' => $lastPaymentDate,
            'last_payment_date_formatted' => $lastPaymentFormatted,
            'fee_type' => $row['fee_type'] ?? $this->inferFeeTypeFromTitle((string) ($row['payment_title'] ?? '')),
            'sms_notification' => $row['sms_notification'] ?? null,
        ], $statusFields);
    }

    /**
     * Paid / Unpaid / Partial / Late Due — same rules as Fee Payment web, with partial fallback.
     *
     * @param  array<string, mixed>  $row
     */
    private function resolvePaymentStatusLabel(
        array $row,
        float $cashPaid,
        float $paidAmount,
        float $due,
        float $principalDue,
        float $remainingLate,
        bool $isInstallment
    ): string {
        if ($isInstallment && $due > 0.00001) {
            return 'Installment';
        }

        if ($due <= 0.00001) {
            $existingStatus = strtolower(trim((string) ($row['fee_balance_status'] ?? $row['status'] ?? '')));
            if ($existingStatus === 'partial') {
                return 'Partial';
            }

            return 'Paid';
        }

        $label = FeePaymentWebTables::displayStatusForFeeRow($cashPaid, $due, $principalDue, $remainingLate);

        // Kuch pay ho chuka ho aur abhi due baki ho → Partial.
        if ($due > 0.00001 && ($cashPaid > 0.00001 || $paidAmount > 0.00001)) {
            if ($label === 'Unpaid' || $label === 'Partial') {
                return 'Partial';
            }

            return $label;
        }

        $existingStatus = strtolower(trim((string) ($row['payment_status'] ?? $row['status'] ?? $row['fee_balance_status'] ?? '')));

        return match ($existingStatus) {
            'paid' => 'Paid',
            'partial' => 'Partial',
            'late due', 'late_due' => 'Late Due',
            'installment' => 'Installment',
            'unpaid', 'generated' => 'Unpaid',
            default => $label !== '' ? $label : 'Unpaid',
        };
    }

    /**
     * Footer totals for fee_rows — same keys as Fee Payment web table_totals.
     *
     * @param  iterable<int, array<string, mixed>>  $feeRows
     * @return array{total: float, discount: float, late_fee: float, paid: float, due: float, generated_fee: float}
     */
    private function sumWebFeeRowTotals(iterable $feeRows): array
    {
        $rows = collect($feeRows);

        return [
            'total' => round((float) $rows->sum(fn ($row) => (float) ($row['total'] ?? 0)), 2),
            'discount' => round((float) $rows->sum(fn ($row) => (float) ($row['discount'] ?? 0)), 2),
            'late_fee' => round((float) $rows->sum(fn ($row) => (float) ($row['late_fee'] ?? 0)), 2),
            'paid' => round((float) $rows->sum(fn ($row) => (float) ($row['paid'] ?? $row['cash_paid'] ?? 0)), 2),
            'due' => round((float) $rows->sum(fn ($row) => (float) ($row['due'] ?? 0)), 2),
            'generated_fee' => round((float) $rows->sum(fn ($row) => (float) ($row['generated_fee'] ?? 0)), 2),
        ];
    }

    /**
     * @param  array<string, mixed>  $summary
     * @return array<string, mixed>
     */
    private function normalizeFeeSummaryForApi(array $summary): array
    {
        $dueAmount = round((float) ($summary['due_amount'] ?? $summary['unpaid_amount'] ?? 0), 2);
        $outstandingTotals = is_array($summary['outstanding_table_totals'] ?? null)
            ? $summary['outstanding_table_totals']
            : (is_array($summary['table_totals'] ?? null) ? $summary['table_totals'] : []);
        $clearedTotals = is_array($summary['cleared_table_totals'] ?? null) ? $summary['cleared_table_totals'] : [];

        return array_merge($summary, [
            'monthly_fee' => round((float) ($summary['monthly_fee'] ?? 0), 2),
            'annual_fee' => round((float) ($summary['annual_fee'] ?? 0), 2),
            'unpaid_amount' => round((float) ($summary['unpaid_amount'] ?? $dueAmount), 2),
            // Live receipt totals (student_payments) — not cleared-bucket snapshot.
            'total_paid' => round((float) ($summary['total_paid'] ?? $summary['paid_gross'] ?? $summary['latest_payments_total'] ?? 0), 2),
            'total_discount' => round((float) ($summary['total_discount'] ?? $summary['latest_payments_discount'] ?? 0), 2),
            'total_late_fee' => round((float) ($summary['total_late_fee'] ?? $summary['latest_payments_late_fee'] ?? 0), 2),
            'due_amount' => $dueAmount,
            'table_total' => round((float) ($summary['table_total'] ?? $outstandingTotals['total'] ?? 0), 2),
            'total' => round((float) ($summary['total'] ?? $dueAmount), 2),
            'yearly_due_amount' => array_key_exists('yearly_due_amount', $summary) && $summary['yearly_due_amount'] !== null
                ? round((float) $summary['yearly_due_amount'], 2)
                : null,
        ]);
    }

    /**
     * @param  iterable<int, array<string, mixed>>  $rows
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    private function normalizePaymentHistoryCollection(iterable $rows): \Illuminate\Support\Collection
    {
        return collect($rows)
            ->map(fn (array $row) => $this->normalizePaymentHistoryRow($row))
            ->values();
    }

    /**
     * Default API response: same rows & footer totals as Fee Payment web Search Results.
     *
     * @return array<string, mixed>
     */
    private function buildWebSearchResultsPaymentHistory(Student $student, ?int $feeYear, int $perPage, int $page): array
    {
        // Same source as GET /fee-payment/search-student (web Search Results table + footer).
        $webPayload = FeePaymentWebTables::feeSearchPayloadForStudent($student);
        $webFeeRows = collect($webPayload['fee_rows'] ?? []);

        $split = FeePaymentWebTables::feeResultsSplitForStudent($student);
        $rows = collect($split['outstanding']['rows']);
        $paidRows = collect($split['paid']['rows']);

        if ($feeYear !== null) {
            $yearStr = (string) $feeYear;
            $webFeeRows = $webFeeRows
                ->filter(fn ($row) => str_contains((string) ($row['title'] ?? ''), $yearStr))
                ->values();
            $rows = $rows->filter(fn ($row) => str_contains((string) ($row['fee_type'] ?? ''), $yearStr))->values();
            $paidRows = $paidRows->filter(fn ($row) => str_contains((string) ($row['fee_type'] ?? ''), $yearStr))->values();
        }

        $webFooter = $feeYear === null
            ? ($webPayload['table_totals'] ?? [])
            : $this->sumWebFeeRowTotals($webFeeRows);
        $unpaidAmount = $feeYear === null
            ? round((float) ($webPayload['unpaid_amount'] ?? $webFooter['due'] ?? 0), 2)
            : round((float) ($webFooter['due'] ?? 0), 2);

        $paidTotals = $feeYear === null
            ? ($split['paid']['totals'] ?? [])
            : [
                'total' => round((float) $paidRows->sum('total'), 2),
                'discount' => round((float) $paidRows->sum('discount'), 2),
                'late_fee' => round((float) $paidRows->sum('late_fee'), 2),
                'paid' => round((float) $paidRows->sum('paid'), 2),
                'due' => 0.0,
                'generated_fee' => round((float) $paidRows->sum('generated_fee'), 2),
            ];

        $paymentHistoryAll = $rows->map(fn ($row) => $this->mapSearchResultRowToPaymentHistory($row, $student))->values();

        $paidFees = $paidRows
            ->sortByDesc(fn ($row) => $row['last_payment_date'] ?? '')
            ->values()
            ->map(fn ($row) => $this->mapSearchResultRowToPaymentHistory($row, $student))
            ->values();

        $statusCounts = [
            'paid' => 0,
            'unpaid' => 0,
            'partial' => 0,
            'installment' => 0,
            'late_due' => 0,
        ];
        foreach ($paymentHistoryAll as $item) {
            $key = (string) ($item['fee_balance_status'] ?? '');
            if (isset($statusCounts[$key])) {
                $statusCounts[$key]++;
            }
        }

        $total = $paymentHistoryAll->count();
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page = max(1, min($page, $lastPage));
        $offset = ($page - 1) * $perPage;
        $slice = $paymentHistoryAll->slice($offset, $perPage)->values();

        $monthlyFee = $student->monthly_fee !== null ? (float) $student->monthly_fee : 0.0;

        $paidStatusCounts = ['paid' => 0, 'unpaid' => 0, 'partial' => 0, 'installment' => 0, 'late_due' => 0];
        foreach ($paidFees as $item) {
            $key = (string) ($item['fee_balance_status'] ?? '');
            if (isset($paidStatusCounts[$key])) {
                $paidStatusCounts[$key]++;
            }
        }

        $outstandingPaid = (float) ($webFooter['paid'] ?? 0);
        $clearedPaid = (float) ($paidTotals['paid'] ?? 0);
        $totalDiscountOutstanding = round((float) ($webFooter['discount'] ?? 0), 2);
        $totalDiscountCleared = round((float) ($paidTotals['discount'] ?? 0), 2);
        $totalLateFeeOutstanding = round((float) ($webFooter['late_fee'] ?? 0), 2);
        $totalLateFeeCleared = round((float) ($paidTotals['late_fee'] ?? 0), 2);
        $tableTotalOutstanding = round((float) ($webFooter['total'] ?? 0), 2);
        $tableTotalCleared = round((float) ($paidTotals['total'] ?? 0), 2);
        $generatedFeeOutstanding = round((float) ($webFooter['generated_fee'] ?? 0), 2);
        $generatedFeeCleared = round((float) ($paidTotals['generated_fee'] ?? 0), 2);

        // Web Latest Payments / Cleared footer: Fee | Late | Dis (e.g. 7250 | 1750 | 1200).
        $clearedFeeNet = round(max(0, $clearedPaid - $totalLateFeeCleared - $totalDiscountCleared), 2);
        $latestPaymentsTotals = $this->latestPaymentsTotalsForStudent($student, $feeYear);
        // Live totals from student_payments receipts (updates on every new payment).
        $receiptPaidGross = round((float) ($latestPaymentsTotals['total_paid'] ?? 0), 2);
        $receiptLateFee = round((float) ($latestPaymentsTotals['total_late_fee'] ?? 0), 2);
        $receiptDiscount = round((float) ($latestPaymentsTotals['total_discount'] ?? 0), 2);

        $statusCountsAll = [
            'paid' => $paidFees->count() + (int) ($statusCounts['paid'] ?? 0),
            'unpaid' => (int) ($statusCounts['unpaid'] ?? 0),
            'partial' => (int) ($statusCounts['partial'] ?? 0),
            'installment' => (int) ($statusCounts['installment'] ?? 0),
            'late_due' => (int) ($statusCounts['late_due'] ?? 0),
        ];

        return [
            'payment_history' => $slice,
            'outstanding_fees' => $paymentHistoryAll,
            'paid_fees' => $paidFees,
            'fee_summary' => [
                'monthly_fee' => $monthlyFee,
                'annual_fee' => $monthlyFee * 12,
                // Kitna abhi dena hai (Search Results Due column).
                'due_amount' => $unpaidAmount,
                'unpaid_amount' => $unpaidAmount,
                'total' => $unpaidAmount,
                'has_unpaid' => $unpaidAmount > 0.00001,
                // Live from DB receipts (Latest Payments) — har nayi payment par update.
                'total_paid' => $receiptPaidGross,
                'total_late_fee' => $receiptLateFee,
                'total_discount' => $receiptDiscount,
                'paid_gross' => $receiptPaidGross,
                'latest_payments_total' => $receiptPaidGross,
                'latest_payments_principal' => (float) ($latestPaymentsTotals['total_principal'] ?? $receiptPaidGross),
                'latest_payments_net' => (float) ($latestPaymentsTotals['total_net'] ?? 0),
                'latest_payments_late_fee' => $receiptLateFee,
                'latest_payments_discount' => $receiptDiscount,
                // Outstanding Search Results footer (pending fees only).
                'outstanding_table_totals' => $webFooter,
                'outstanding_paid' => $outstandingPaid,
                'outstanding_discount' => $totalDiscountOutstanding,
                'outstanding_late_fee' => $totalLateFeeOutstanding,
                'outstanding_generated_fee' => $generatedFeeOutstanding,
                'table_total' => $tableTotalOutstanding,
                // Cleared/paid fee titles (fully settled).
                'cleared_table_totals' => [
                    'total' => $tableTotalCleared,
                    'discount' => $totalDiscountCleared,
                    'late_fee' => $totalLateFeeCleared,
                    'paid' => $clearedPaid,
                    'fee_net' => $clearedFeeNet,
                    'due' => 0.0,
                    'generated_fee' => $generatedFeeCleared,
                ],
                'status_counts' => $statusCountsAll,
                'status_counts_outstanding' => $statusCounts,
                'outstanding_fees_count' => $total,
                'paid_fees_count' => $paidFees->count(),
                'paid_status_counts' => $paidStatusCounts,
            ],
            'pagination' => [
                'current_page' => $page,
                'last_page' => $lastPage,
                'per_page' => $perPage,
                'total' => $total,
                'from' => $total > 0 ? $offset + 1 : null,
                'to' => $total > 0 ? min($offset + $perPage, $total) : null,
            ],
            'view' => 'search_results',
        ];
    }

    /**
     * @param array<string, mixed> $result
     * @return array<string, mixed>
     */
    private function wrapPaymentHistoryResponse(
        Student $student,
        float $monthlyFee,
        array $result,
        bool $includeAllRecords,
        bool $latestOnly,
        ?int $feeYear,
    ): array {
        $paymentHistory = $this->normalizePaymentHistoryCollection($result['payment_history'] ?? []);
        $outstandingFees = isset($result['outstanding_fees'])
            ? $this->normalizePaymentHistoryCollection($result['outstanding_fees'])
            : $paymentHistory;
        $paidFees = $this->normalizePaymentHistoryCollection($result['paid_fees'] ?? []);

        return [
            'student' => [
                'id' => $student->id,
                'student_name' => $student->student_name,
                'student_code' => $student->student_code,
                'class' => $student->class,
                'section' => $student->section,
                'campus' => $student->campus,
                'transport_route' => $student->transport_route ?? null,
                'transport_fare' => (int) round((float) ($student->transport_fare ?? 0)),
            ],
            'monthly_fee' => (int) round($monthlyFee),
            'fee_summary' => $this->normalizeFeeSummaryForApi($result['fee_summary'] ?? []),
            'include_all_records' => $includeAllRecords,
            'latest_only' => $latestOnly,
            'fee_year' => $feeYear,
            'view' => $result['view'] ?? null,
            'payment_history' => $paymentHistory->values()->all(),
            'outstanding_fees' => $outstandingFees->values()->all(),
            'paid_fees' => $paidFees->values()->all(),
            'pagination' => $result['pagination'] ?? null,
        ];
    }

    /**
     * latest_only=1 — same rows as Fee Payment "Latest Payments" table.
     *
     * @return array<string, mixed>
     */
    private function buildLatestPaymentsPaymentHistory(Student $student, ?int $feeYear, int $perPage, int $page): array
    {
        $latest = FeePaymentWebTables::latestPaymentsForStudentCode((string) $student->student_code, $feeYear);
        $paidFeesAll = collect($latest['rows'])->map(function (array $row) {
            $title = (string) ($row['title'] ?? '');
            $amountPaid = (float) ($row['amount_paid'] ?? 0);
            $discount = (float) ($row['discount'] ?? 0);
            $lateFee = (float) ($row['late_fee'] ?? 0);
            $paymentDate = null;
            $paymentDateFormatted = $row['payment_datetime'] ?? null;
            if (!empty($row['payment_date'])) {
                try {
                    $paymentDate = Carbon::createFromFormat('d-m-Y', (string) $row['payment_date'])->format('Y-m-d');
                } catch (\Throwable) {
                    $paymentDate = null;
                }
            }

            return $this->normalizePaymentHistoryRow([
                'id' => $row['id'] ?? null,
                'payment_title' => $title,
                'payment_amount' => round($amountPaid, 2),
                'discount' => round($discount, 2),
                'late_fee' => round($lateFee, 2),
                'net_amount' => 0.0,
                'method' => $row['method'] ?? 'Paid',
                'status' => 'paid',
                'fee_type' => $this->inferFeeTypeFromTitle($title),
                'generated_amount' => round($amountPaid + $discount, 2),
                'paid_amount' => round($amountPaid, 2),
                'due_amount' => 0.0,
                'fee_balance_status' => 'paid',
                'payment_status' => 'Paid',
                'is_paid' => true,
                'is_unpaid' => false,
                'is_partial' => false,
                'is_installment' => false,
                'is_outstanding' => false,
                'payment_date' => $paymentDate,
                'payment_date_formatted' => $paymentDateFormatted,
                'last_payment_date' => $paymentDate,
                'last_payment_date_formatted' => $paymentDateFormatted,
                'accountant' => $row['received_by'] ?? null,
                'student_code' => $row['student_code'] ?? null,
                'student_name' => $row['student_name'] ?? null,
                'parent_name' => $row['parent_name'] ?? null,
            ]);
        })->values();

        $total = $paidFeesAll->count();
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page = max(1, min($page, $lastPage));
        $offset = ($page - 1) * $perPage;
        $paidSlice = $paidFeesAll->slice($offset, $perPage)->values();

        $monthlyFee = $student->monthly_fee !== null ? (float) $student->monthly_fee : 0.0;
        $annualFee = $monthlyFee * 12;
        $totalPaid = (float) ($latest['total_amount_paid'] ?? 0);

        return [
            'payment_history' => collect(),
            'outstanding_fees' => collect(),
            'paid_fees' => $paidSlice,
            'fee_summary' => [
                'monthly_fee' => $monthlyFee,
                'annual_fee' => $annualFee,
                'total_paid' => $totalPaid,
                'total_discount' => round((float) collect($latest['rows'])->sum('discount'), 2),
                'total_late_fee' => round((float) collect($latest['rows'])->sum('late_fee'), 2),
                'due_amount' => 0.0,
                'total' => 0.0,
                'yearly_due_amount' => $feeYear !== null
                    ? max($annualFee - $totalPaid, 0.0)
                    : null,
            ],
            'pagination' => [
                'current_page' => $page,
                'last_page' => $lastPage,
                'per_page' => $perPage,
                'total' => $total,
                'from' => $total > 0 ? $offset + 1 : null,
                'to' => $total > 0 ? min($offset + $perPage, $total) : null,
            ],
            'view' => 'latest_payments',
        ];
    }

    /**
     * True when transport is configured on the student but there is no transport-related payment row yet.
     * Optional calendar year aligns synthetic row with ?fee_year= filter.
     */
    private function shouldInjectSyntheticTransport(Student $student, ?int $feeYear): bool
    {
        $route = trim((string) ($student->transport_route ?? ''));
        $fare = (float) ($student->transport_fare ?? 0);
        if ($route === '' || $fare <= 0.00001) {
            return false;
        }

        $code = $student->student_code;
        if (!$code) {
            return false;
        }

        $norm = $this->normalizedStudentCode((string) $code);

        // Same as /fee-payment/search-student: any generated/paid row whose title starts with "Transport Fee"
        $generatedFees = StudentPayment::query()
            ->whereRaw('LOWER(TRIM(student_code)) = ?', [$norm])
            ->whereIn('method', ['Generated', 'Installment'])
            ->get();
        $paidFees = StudentPayment::query()
            ->whereRaw('LOWER(TRIM(student_code)) = ?', [$norm])
            ->where('method', '!=', 'Generated')
            ->where('method', '!=', 'Installment')
            ->get();

        $hasTransportHistory = $generatedFees->contains(function ($fee) {
            return str_starts_with(trim((string) ($fee->payment_title ?? '')), 'Transport Fee');
        }) || $paidFees->contains(function ($fee) {
            return str_starts_with(trim((string) ($fee->payment_title ?? '')), 'Transport Fee');
        });

        if ($hasTransportHistory) {
            return false;
        }

        // Use system record time only — paper admission_date may be backdated (e.g. April) while admit in May.
        $anchor = Carbon::parse($student->created_at ?? now());
        if ($feeYear !== null && (int) $feeYear !== (int) $anchor->format('Y')) {
            return false;
        }

        return true;
    }

    /**
     * Voucher-aligned virtual row so apps see transport dues before accountant generates transport in Fee Payment.
     *
     * @return array<string, mixed>
     */
    private function buildSyntheticTransportHistoryRow(Student $student): array
    {
        $anchor = Carbon::parse($student->created_at ?? now());
        $title = 'Transport Fee - ' . $anchor->format('F') . ' ' . $anchor->format('Y');
        $fare = round((float) ($student->transport_fare ?? 0), 2);
        $dateStr = $anchor->format('Y-m-d');
        $formatted = $anchor->format('d M Y');
        $createdAt = ($student->created_at ? Carbon::parse($student->created_at) : Carbon::now())->format('Y-m-d H:i:s');
        $createdAtFormatted = ($student->created_at ? Carbon::parse($student->created_at) : Carbon::now())->format('d M Y, h:i A');

        return [
            'id' => null,
            'payment_title' => $title,
            'payment_amount' => $fare,
            'discount' => 0.0,
            'late_fee' => 0.0,
            'net_amount' => $fare,
            'method' => 'Generated',
            'status' => 'generated',
            'fee_type' => 'transport_fee',
            'generated_amount' => $fare,
            'paid_amount' => 0.0,
            'payment_date' => $dateStr,
            'payment_date_formatted' => $formatted,
            'accountant' => null,
            'sms_notification' => 'Yes',
            'created_at' => $createdAt,
            'created_at_formatted' => $createdAtFormatted,
            'is_synthetic_transport' => true,
            'due_amount' => $fare,
            'fee_balance_status' => 'unpaid',
        ];
    }

    /**
     * Get Student Fee Details
     * Returns fee information for the logged-in student
     * 
     * GET /api/student/fees?fee_year=2025
     * 
     * Response:
     * - student_name
     * - initial_amount (base monthly_fee from admit)
     * - fee_year
     * - paid (sum of payments in that year)
     * - discount (sum of discount in that year)
     * - late_fee (sum of late_fee in that year)
     * - due_amount (initial_amount - paid - discount + late_fee, minimum 0)
     * - payment_history (optional, if requested)
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getFees(Request $request): JsonResponse
    {
        try {
            $student = $request->user();

            if (!$student) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found',
                    'token' => null,
                ], 404);
            }

            $feeYear = (int) $request->get('fee_year', date('Y'));
            $includeHistory = $request->get('include_history', false);

            // Get student code
            $studentCode = $student->student_code;

            if (!$studentCode) {
                return response()->json([
                    'success' => false,
                    'message' => 'Student code not found',
                    'token' => null,
                ], 400);
            }

            $latestTotals = $this->latestPaymentsTotalsForStudent($student, $feeYear);
            $paid = $latestTotals['total_paid'];
            $discount = $latestTotals['total_discount'];
            $lateFee = $latestTotals['total_late_fee'];

            // Initial amount is the monthly_fee from student record
            $initialAmount = $student->monthly_fee !== null ? (float) $student->monthly_fee : 0.0;
            
            // Footer "Due" column total from Fee Payment search results table (see FeePaymentWebTables::searchResultsForStudent).
            $dueAmount = FeePaymentWebTables::searchResultsForStudent($student)['totals']['due'];

            // Receipt lines only when embedding history from /fees (no Generated stubs)
            $paymentHistory = null;
            if ($includeHistory) {
                $paymentHistory = collect($latestTotals['rows'])->map(function (array $row) {
                    $paymentDate = null;
                    if (!empty($row['payment_date'])) {
                        try {
                            $paymentDate = Carbon::createFromFormat('d-m-Y', (string) $row['payment_date'])->format('Y-m-d');
                        } catch (\Throwable $e) {
                            $paymentDate = null;
                        }
                    }

                    return [
                        'id' => $row['id'] ?? null,
                        'payment_title' => $row['title'] ?? null,
                        'payment_amount' => (float) ($row['amount_paid'] ?? 0),
                        'discount' => (float) ($row['discount'] ?? 0),
                        'late_fee' => (float) ($row['late_fee'] ?? 0),
                        'method' => $row['method'] ?? null,
                        'payment_date' => $paymentDate,
                        'payment_date_formatted' => $row['payment_datetime'] ?? null,
                        'accountant' => $row['received_by'] ?? null,
                        'created_at' => null,
                    ];
                })->values();
            }

            return response()->json([
                'success' => true,
                'message' => 'Student fee details retrieved successfully',
                'data' => [
                    'student' => [
                        'id' => $student->id,
                        'student_name' => $student->student_name,
                        'student_code' => $student->student_code,
                        'class' => $student->class,
                        'section' => $student->section,
                        'campus' => $student->campus,
                    ],
                    'fee_year' => $feeYear,
                    'fee_summary' => [
                        'initial_amount' => $initialAmount,
                        'paid' => $paid,
                        'discount' => $discount,
                        'late_fee' => $lateFee,
                        'due_amount' => $dueAmount,
                    ],
                    'payment_history' => $paymentHistory,
                    'total_payments' => count($latestTotals['rows']),
                ],
                'token' => $request->user()->currentAccessToken()->token ?? null,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving fee details: ' . $e->getMessage(),
                'token' => null,
            ], 500);
        }
    }

    /**
     * Get Payment History
     * Returns detailed payment history for a student_id (token not required)
     * Each item includes due_amount and fee_balance_status (unpaid | partial | paid) per payment_title, aligned with Fee Payment web.
     * When a title is partially paid, receipt rows use fee_balance_status=paid and due_amount=0; the Generated/Installment stub for that title keeps due_amount=remaining and fee_balance_status=partial (only one partial row per title, like separate installment lines).
     * 
     * GET /api/student/fees/payment-history?student_id=3&fee_year=2025&per_page=30
     * Omit fee_year to return all payment rows for the student (data.fee_year is null).
     * By default: one row per outstanding fee title (Fee Payment web Search Results table). Pass latest_only=1 for Latest Payments receipts only. Pass include_all_records=1 for every raw ledger row.
     * When transport_route + transport_fare are set and no DB row has "transport" in payment_title, a synthetic Generated transport row is prepended on page 1 (is_synthetic_transport=true), and due_amount includes it (parent fee voucher parity).
     * Synthetic transport month/year follows student.created_at (not admission_date). For legacy Generated stubs (except Monthly Fee titles), payment_date in the JSON may be shifted to created_at when the stored date is earlier (display-only).
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getPaymentHistory(Request $request): JsonResponse
    {
        try {
            $studentId = (int) ($request->route('student_id') ?? $request->get('student_id', 0));
            if ($studentId <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'student_id is required',
                ], 422);
            }

            $student = Student::query()->find($studentId);
            if (!$student) {
                return response()->json([
                    'success' => false,
                    'message' => 'Student not found',
                ], 404);
            }

            $studentCode = $student->student_code;

            if (!$studentCode) {
                return response()->json([
                    'success' => false,
                    'message' => 'Student code not found',
                ], 400);
            }

            $feeYearRaw = $request->get('fee_year');
            $filterByYear = $feeYearRaw !== null && $feeYearRaw !== '';
            $feeYear = null;
            if ($filterByYear) {
                $feeYear = (int) $feeYearRaw;
                if ($feeYear < 2000 || $feeYear > 2100) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid fee_year. Year must be between 2000 and 2100.',
                    ], 422);
                }
            }

            $perPage = $request->get('per_page', 30);
            $perPage = in_array((int) $perPage, [10, 25, 30, 50, 100], true) ? (int) $perPage : 30;

            $normalizedStudentCode = $this->normalizedStudentCode($studentCode);

            // Default: fee-type rows like Fee Payment web Search Results (one row per fee title).
            $includeAllRecordsRaw = $request->get('include_all_records');
            if ($includeAllRecordsRaw === null || $includeAllRecordsRaw === '') {
                $includeAllRecords = false;
            } else {
                $includeAllRecords = filter_var($includeAllRecordsRaw, FILTER_VALIDATE_BOOLEAN);
            }

            // latest_only=1 → same as GET /fee-payment/history (receipts only). Otherwise default = receipts + unpaid Generated/Installment titles (outstanding balance).
            $latestOnlyRaw = $request->get('latest_only');
            if ($includeAllRecords) {
                $latestOnly = false;
            } elseif ($latestOnlyRaw === null || $latestOnlyRaw === '') {
                $latestOnly = false;
            } else {
                $latestOnly = filter_var($latestOnlyRaw, FILTER_VALIDATE_BOOLEAN);
            }

            $page = max(1, (int) $request->get('page', 1));
            $monthlyFee = $student->monthly_fee !== null ? (float) $student->monthly_fee : 0.0;

            if ($latestOnly && !$includeAllRecords) {
                $result = $this->buildLatestPaymentsPaymentHistory($student, $feeYear, $perPage, $page);

                return response()->json([
                    'success' => true,
                    'message' => 'Payment history retrieved successfully',
                    'data' => $this->wrapPaymentHistoryResponse($student, $monthlyFee, $result, $includeAllRecords, $latestOnly, $feeYear),
                ], 200);
            }

            if (!$includeAllRecords) {
                $result = $this->buildWebSearchResultsPaymentHistory($student, $feeYear, $perPage, $page);

                return response()->json([
                    'success' => true,
                    'message' => 'Payment history retrieved successfully',
                    'data' => $this->wrapPaymentHistoryResponse($student, $monthlyFee, $result, $includeAllRecords, $latestOnly, $feeYear),
                ], 200);
            }

            // include_all_records=1 → full raw ledger (legacy / audit)
            $feeMath = FeePaymentWebTables::feePaymentSearchStudentMath($student);

            $query = StudentPayment::query()
                ->whereRaw('LOWER(TRIM(student_code)) = ?', [$normalizedStudentCode]);

            if ($latestOnly) {
                // Same as fee-payment.blade.php Latest Payments → GET /fee-payment/history (received receipts only, no Generated stubs).
                $query->whereRaw('LOWER(TRIM(COALESCE(method, \'\'))) <> ?', ['generated']);
            } elseif (!$includeAllRecords) {
                // Web parity: Search Results filter — hide settled Generated/Installment stubs unless title still has balance.
                $outstandingTitles = array_keys(array_filter(
                    $feeMath['remaining_by_title'],
                    static fn ($r) => ((float) $r) > 0.00001
                ));
                $query->where(function ($q) use ($outstandingTitles) {
                    $q->whereRaw('LOWER(TRIM(COALESCE(method, \'\'))) NOT IN (?, ?)', ['generated', 'installment'])
                        ->orWhereIn('payment_title', $outstandingTitles);
                });
            }

            if ($feeYear !== null) {
                $query->whereYear('payment_date', $feeYear);
            }

            $payments = $query->orderBy('payment_date', 'desc')
                ->orderBy('id', 'desc')
                ->paginate($perPage);

            // Raw ledger mode should not inject virtual/synthetic rows.
            $injectSynthTransport = !$includeAllRecords && !$latestOnly && $this->shouldInjectSyntheticTransport($student, $feeYear);
            $syntheticTransportRow = $injectSynthTransport ? $this->buildSyntheticTransportHistoryRow($student) : null;

            $feeTitleBalances = $feeMath['balances'];

            // Format payment history
            $paymentHistory = $payments->map(function ($payment) use ($student, $feeTitleBalances, $includeAllRecords) {
                // Treat both Generated and Installment as unpaid/generated entries
                $method = strtolower(trim((string) $payment->method));
                $status = in_array($method, ['generated', 'installment'], true) ? 'generated' : 'paid';

                // Display-only: old admission/other Generated stubs sometimes carry backdated payment_date (e.g. April)
                // while the student row was created later (May). Leave monthly installments unchanged.
                $paymentTitleRaw = $payment->payment_title ?? '';
                $displayPaymentDate = $payment->payment_date ? Carbon::parse($payment->payment_date) : null;
                $isMonthlyTitle = stripos((string) $paymentTitleRaw, 'Monthly Fee') !== false;
                if (
                    !$includeAllRecords
                    && $displayPaymentDate
                    && !$isMonthlyTitle
                    && $student->created_at
                    && in_array($method, ['generated', 'installment'], true)
                ) {
                    $payDay = $displayPaymentDate->copy()->startOfDay();
                    $createdDay = Carbon::parse($student->created_at)->startOfDay();
                    if ($payDay->lt($createdDay)) {
                        $displayPaymentDate = $createdDay->copy();
                    }
                }

                // Determine fee type from payment title
                $feeType = null;
                $paymentTitle = $paymentTitleRaw;

                if (stripos($paymentTitle, 'Monthly Fee') !== false) {
                    $feeType = 'monthly_fee';
                } elseif (stripos($paymentTitle, 'Transport Fee') !== false) {
                    $feeType = 'transport_fee';
                } elseif (stripos($paymentTitle, 'Admission Fee') !== false) {
                    $feeType = 'admission_fee';
                } elseif (stripos($paymentTitle, 'Custom Fee') !== false || stripos($paymentTitle, 'Library Fee') !== false || stripos($paymentTitle, 'Lab Fee') !== false) {
                    $feeType = 'custom_fee';
                } else {
                    $feeType = 'other';
                }

                // Calculate generated amount (for generated fees) or paid amount (for paid fees)
                $generatedAmount = 0;
                $paidAmount = 0;

                if ($status === 'generated') {
                    // Generated / Installment row: net bill = fee minus line discount + late
                    $generatedAmount = (float) $payment->payment_amount - (float) $payment->discount + (float) $payment->late_fee;
                } else {
                    // Receipt row: payment_amount is cash/credit received; discount is fee-level only (do not subtract from cash again).
                    $paidAmount = (float) $payment->payment_amount;
                }

                $titleKey = (string) ($payment->payment_title ?? '');
                $bal = $feeTitleBalances[$titleKey] ?? null;
                $dueForTitle = round($bal !== null ? (float) $bal['due'] : 0.0, 2);
                $feeBalanceStatus = $this->feeBalanceStatusFromWeb($bal);

                // Split partial / late-due like web: receipt lines show paid + zero due;
                // generated/installment stub carries the remaining bill.
                if (in_array($feeBalanceStatus, ['partial', 'late_due'], true)) {
                    if ($status === 'paid') {
                        $dueForTitle = 0.0;
                        $feeBalanceStatus = 'paid';
                    }
                }

                $netAmount = $status === 'generated'
                    ? (float) $payment->payment_amount - (float) $payment->discount + (float) $payment->late_fee
                    : (float) $payment->payment_amount + (float) $payment->late_fee;

                // Generated stub on a partially paid title: show outstanding net (matches due_amount), not full bill.
                if ($status === 'generated' && in_array($feeBalanceStatus, ['partial', 'late_due'], true)) {
                    $netAmount = (float) $dueForTitle;
                    $generatedAmount = (float) $dueForTitle;
                }

                $statusLabel = match ($feeBalanceStatus) {
                    'paid' => 'Paid',
                    'unpaid' => 'Unpaid',
                    'partial' => 'Partial',
                    'late_due' => 'Late Due',
                    'installment' => 'Installment',
                    default => 'Unpaid',
                };
                $rowIsInstallment = strtolower(trim((string) $payment->method)) === 'installment';
                $statusFields = $this->statusFieldsFromWebLabel($statusLabel, $rowIsInstallment, $dueForTitle);

                $mapped = array_merge([
                    'id' => $payment->id,
                    'payment_title' => $payment->payment_title,
                    'payment_amount' => (float) $payment->payment_amount,
                    'discount' => (float) $payment->discount,
                    'late_fee' => (float) $payment->late_fee,
                    'net_amount' => round($netAmount, 2),
                    'method' => $payment->method,
                    'status' => $status, // "generated" or "paid"
                    'fee_type' => $feeType, // "monthly_fee", "transport_fee", "admission_fee", "custom_fee", or "other"
                    'generated_amount' => $generatedAmount, // Amount generated (only for generated fees)
                    'paid_amount' => $paidAmount, // Amount paid (only for payment rows; per-line, not fee total)
                    'due_amount' => $dueForTitle, // remaining for payment_title — same as Fee Payment search results
                    'fee_balance_status' => $feeBalanceStatus, // unpaid | partial | paid (per fee title, like web)
                    'payment_date' => $displayPaymentDate ? $displayPaymentDate->format('Y-m-d') : null,
                    'payment_date_formatted' => $displayPaymentDate ? $displayPaymentDate->format('d M Y') : null,
                    'accountant' => $payment->accountant,
                    'sms_notification' => $payment->sms_notification,
                    'created_at' => $payment->created_at ? $payment->created_at->format('Y-m-d H:i:s') : null,
                    'created_at_formatted' => $payment->created_at ? $payment->created_at->format('d M Y, h:i A') : null,
                ], $statusFields);

                return $this->normalizePaymentHistoryRow($mapped);
            });

            $paymentHistory = $paymentHistory->values();
            if ($syntheticTransportRow !== null && $payments->currentPage() === 1) {
                $paymentHistory = collect([
                    $this->normalizePaymentHistoryRow(array_merge($syntheticTransportRow, $this->statusFieldsFromWebLabel('Unpaid', false, (float) ($syntheticTransportRow['due_amount'] ?? 0)))),
                ])->concat($paymentHistory)->values();
            }

            $outstandingLedger = $paymentHistory->filter(fn (array $row) => (bool) ($row['is_outstanding'] ?? false) || ((float) ($row['due_amount'] ?? 0) > 0.00001))->values();
            $paidLedger = $paymentHistory->filter(fn (array $row) => (bool) ($row['is_paid'] ?? false))->values();

            // Calculate fee summary
            $monthlyFee = $student->monthly_fee !== null ? (float) $student->monthly_fee : 0.0;
            $annualFee = $monthlyFee * 12;

            // Paid totals must match Fee Payment web "Latest Payments" (ledgerActive + receipts only).
            $latestTotals = $this->latestPaymentsTotalsForStudent($student, $feeYear);
            $totalPaid = $latestTotals['total_paid'];
            $totalDiscount = $latestTotals['total_discount'];
            $totalLateFee = $latestTotals['total_late_fee'];
            
            // Only meaningful when viewing a single year (monthly_fee * 12 is not valid for all-time totals)
            $yearlyDueAmount = null;
            if ($feeYear !== null) {
                $yearlyDueAmount = max($annualFee - $totalPaid - $totalDiscount + $totalLateFee, 0.0);
            }

            $feePaymentDue = (float) ($feeMath['unpaid_amount'] ?? 0);
            if (!$injectSynthTransport && ($feeMath['synthetic_transport_fare_included'] ?? 0) > 0) {
                $feePaymentDue = round($feePaymentDue - (float) $feeMath['synthetic_transport_fare_included'], 2);
            }

            $paginationBonus = ($syntheticTransportRow !== null ? 1 : 0);

            $ledgerResult = [
                'payment_history' => $outstandingLedger,
                'outstanding_fees' => $outstandingLedger,
                'paid_fees' => $paidLedger,
                'fee_summary' => [
                    'monthly_fee' => $monthlyFee,
                    'annual_fee' => $annualFee,
                    'total_paid' => $totalPaid,
                    'latest_payments_total' => $totalPaid,
                    'total_discount' => $totalDiscount,
                    'total_late_fee' => $totalLateFee,
                    'due_amount' => $feePaymentDue,
                    'total' => $feePaymentDue,
                    'yearly_due_amount' => $yearlyDueAmount,
                ],
                'pagination' => [
                    'current_page' => $payments->currentPage(),
                    'last_page' => max(1, (int) ceil(($payments->total() + $paginationBonus) / $payments->perPage())),
                    'per_page' => $payments->perPage(),
                    'total' => $payments->total() + $paginationBonus,
                    'from' => $payments->firstItem(),
                    'to' => $payments->lastItem() !== null ? $payments->lastItem() + (($syntheticTransportRow !== null && $payments->currentPage() === 1) ? 1 : 0) : null,
                ],
                'view' => 'ledger',
            ];

            return response()->json([
                'success' => true,
                'message' => 'Payment history retrieved successfully',
                'data' => $this->wrapPaymentHistoryResponse($student, $monthlyFee, $ledgerResult, $includeAllRecords, $latestOnly, $feeYear),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving payment history: ' . $e->getMessage(),
            ], 500);
        }
    }
}

