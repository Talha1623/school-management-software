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
     * @param array{due: float, total_paid_display: float, generated_fee: float}|null $bal
     */
    private function feeBalanceStatusFromWeb(?array $bal): string
    {
        if ($bal === null) {
            return 'paid';
        }
        $due = (float) ($bal['due'] ?? 0);
        $paidDisplay = (float) ($bal['total_paid_display'] ?? 0);
        if ($due <= 0.00001) {
            return 'paid';
        }
        if ($paidDisplay > 0.00001) {
            return 'partial';
        }

        return 'unpaid';
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
     * Status fields aligned with Fee Payment web Status column (Partial / Unpaid / Installment / Paid).
     *
     * @return array{
     *   fee_balance_status: string,
     *   status: string,
     *   payment_status: string,
     *   is_paid: bool,
     *   is_unpaid: bool,
     *   is_partial: bool,
     *   is_installment: bool,
     *   is_outstanding: bool
     * }
     */
    private function resolvePaymentStatus(bool $isInstallment, float $paid, float $due): array
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
            ];
        }

        if ($due <= 0.00001) {
            return [
                'fee_balance_status' => 'paid',
                'status' => 'paid',
                'payment_status' => 'Paid',
                'is_paid' => true,
                'is_unpaid' => false,
                'is_partial' => false,
                'is_installment' => false,
                'is_outstanding' => false,
            ];
        }

        if ($paid > 0.00001) {
            return [
                'fee_balance_status' => 'partial',
                'status' => 'partial',
                'payment_status' => 'Partial',
                'is_paid' => false,
                'is_unpaid' => false,
                'is_partial' => true,
                'is_installment' => false,
                'is_outstanding' => true,
            ];
        }

        return [
            'fee_balance_status' => 'unpaid',
            'status' => 'unpaid',
            'payment_status' => 'Unpaid',
            'is_paid' => false,
            'is_unpaid' => true,
            'is_partial' => false,
            'is_installment' => false,
            'is_outstanding' => true,
        ];
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
        $generatedFee = (float) ($row['generated_fee'] ?? 0);
        $isInstallment = (bool) ($row['is_installment'] ?? false);
        $statusFields = $this->resolvePaymentStatus($isInstallment, $paid, $due);
        $paymentTransactions = collect($row['payment_transactions'] ?? [])->values();

        return array_merge([
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
    }

    /**
     * Default API response: same rows & footer totals as Fee Payment web Search Results.
     *
     * @return array<string, mixed>
     */
    private function buildWebSearchResultsPaymentHistory(Student $student, ?int $feeYear, int $perPage, int $page): array
    {
        $split = FeePaymentWebTables::feeResultsSplitForStudent($student);
        $rows = collect($split['outstanding']['rows']);
        $paidRows = collect($split['paid']['rows']);

        if ($feeYear !== null) {
            $yearStr = (string) $feeYear;
            $rows = $rows->filter(fn ($row) => str_contains((string) ($row['fee_type'] ?? ''), $yearStr))->values();
            $paidRows = $paidRows->filter(fn ($row) => str_contains((string) ($row['fee_type'] ?? ''), $yearStr))->values();
        }

        $totals = $feeYear === null
            ? ($split['outstanding']['totals'] ?? [])
            : [
                'total' => round((float) $rows->sum('total'), 2),
                'discount' => round((float) $rows->sum('discount'), 2),
                'late_fee' => round((float) $rows->sum('late_fee'), 2),
                'paid' => round((float) $rows->sum('paid'), 2),
                'due' => round((float) $rows->sum('due'), 2),
                'generated_fee' => round((float) $rows->sum('generated_fee'), 2),
            ];

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

        $paymentHistory = $rows->map(fn ($row) => $this->mapSearchResultRowToPaymentHistory($row, $student))->values();

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
        ];
        foreach ($paymentHistory as $item) {
            $key = (string) ($item['fee_balance_status'] ?? '');
            if (isset($statusCounts[$key])) {
                $statusCounts[$key]++;
            }
        }

        $total = $paymentHistory->count();
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page = max(1, min($page, $lastPage));
        $offset = ($page - 1) * $perPage;
        $slice = $paymentHistory->slice($offset, $perPage)->values();

        $monthlyFee = $student->monthly_fee !== null ? (float) $student->monthly_fee : 0.0;

        $paidStatusCounts = ['paid' => 0, 'unpaid' => 0, 'partial' => 0, 'installment' => 0];
        foreach ($paidFees as $item) {
            $key = (string) ($item['fee_balance_status'] ?? '');
            if (isset($paidStatusCounts[$key])) {
                $paidStatusCounts[$key]++;
            }
        }

        // Web Search Results footer (fee-payment.blade.php grandTotals) — outstanding fee_rows only.
        $webFooter = $feeYear === null
            ? FeePaymentWebTables::webSearchFooterTotalsForStudent($student)
            : [
                'total' => round((float) $rows->sum('total'), 2),
                'discount' => round((float) $rows->sum('discount'), 2),
                'late_fee' => round((float) $rows->sum('late_fee'), 2),
                'paid' => round((float) $rows->sum('paid'), 2),
                'due' => round((float) $rows->sum('due'), 2),
                'generated_fee' => round((float) $rows->sum('generated_fee'), 2),
            ];

        $outstandingPaid = (float) ($webFooter['paid'] ?? 0);
        $clearedPaid = (float) ($paidTotals['paid'] ?? 0);
        $totalPaidAll = round($outstandingPaid + $clearedPaid, 2);
        $totalDiscountOutstanding = round((float) ($webFooter['discount'] ?? 0), 2);
        $totalDiscountCleared = round((float) ($paidTotals['discount'] ?? 0), 2);
        $totalDiscountAll = round($totalDiscountOutstanding + $totalDiscountCleared, 2);
        $totalLateFeeOutstanding = round((float) ($webFooter['late_fee'] ?? 0), 2);
        $totalLateFeeCleared = round((float) ($paidTotals['late_fee'] ?? 0), 2);
        $totalLateFeeAll = round($totalLateFeeOutstanding + $totalLateFeeCleared, 2);
        $tableTotalOutstanding = round((float) ($webFooter['total'] ?? 0), 2);
        $tableTotalCleared = round((float) ($paidTotals['total'] ?? 0), 2);
        $tableTotalAll = round($tableTotalOutstanding + $tableTotalCleared, 2);
        $generatedFeeOutstanding = round((float) ($webFooter['generated_fee'] ?? 0), 2);
        $generatedFeeCleared = round((float) ($paidTotals['generated_fee'] ?? 0), 2);
        $generatedFeeAll = round($generatedFeeOutstanding + $generatedFeeCleared, 2);

        $statusCountsAll = [
            'paid' => $paidFees->count() + (int) ($statusCounts['paid'] ?? 0),
            'unpaid' => (int) ($statusCounts['unpaid'] ?? 0),
            'partial' => (int) ($statusCounts['partial'] ?? 0),
            'installment' => (int) ($statusCounts['installment'] ?? 0),
        ];

        return [
            'payment_history' => $slice,
            'outstanding_fees' => $slice,
            'paid_fees' => $paidFees,
            'fee_summary' => [
                'monthly_fee' => $monthlyFee,
                'annual_fee' => $monthlyFee * 12,
                // Web Search Results footer (outstanding rows only — matches fee-payment.blade.php).
                'table_total' => $tableTotalOutstanding,
                'table_total_all' => $tableTotalAll,
                'total_paid' => $outstandingPaid,
                'total_paid_outstanding' => $outstandingPaid,
                'total_paid_cleared' => $clearedPaid,
                'total_paid_all' => $totalPaidAll,
                'total_discount' => $totalDiscountAll,
                'total_discount_outstanding' => $totalDiscountOutstanding,
                'total_discount_cleared' => $totalDiscountCleared,
                'total_late_fee' => $totalLateFeeAll,
                'total_late_fee_outstanding' => $totalLateFeeOutstanding,
                'total_late_fee_cleared' => $totalLateFeeCleared,
                'generated_fee_total' => $generatedFeeAll,
                'generated_fee_total_outstanding' => $generatedFeeOutstanding,
                'generated_fee_total_cleared' => $generatedFeeCleared,
                'due_amount' => (float) ($webFooter['due'] ?? 0),
                'total' => (float) ($webFooter['due'] ?? 0),
                'yearly_due_amount' => null,
                'table_totals' => $webFooter,
                'table_totals_cleared' => [
                    'total' => $tableTotalCleared,
                    'discount' => $totalDiscountCleared,
                    'late_fee' => round((float) ($paidTotals['late_fee'] ?? 0), 2),
                    'paid' => $clearedPaid,
                    'due' => 0.0,
                    'generated_fee' => $generatedFeeCleared,
                ],
                'status_counts' => $statusCountsAll,
                'status_counts_outstanding' => $statusCounts,
                'outstanding_fees_count' => $total,
                'paid_fees_count' => $paidFees->count(),
                'paid_status_counts' => $paidStatusCounts,
                'paid_totals' => $paidTotals,
                'outstanding_totals' => $totals,
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
        return [
            'student' => [
                'id' => $student->id,
                'student_name' => $student->student_name,
                'student_code' => $student->student_code,
                'class' => $student->class,
                'section' => $student->section,
                'campus' => $student->campus,
                'transport_route' => $student->transport_route ?? null,
                'transport_fare' => round((float) ($student->transport_fare ?? 0), 2),
            ],
            'monthly_fee' => $monthlyFee,
            'fee_summary' => $result['fee_summary'],
            'include_all_records' => $includeAllRecords,
            'latest_only' => $latestOnly,
            'fee_year' => $feeYear,
            'view' => $result['view'] ?? null,
            'payment_history' => $result['payment_history'],
            'outstanding_fees' => $result['outstanding_fees'] ?? $result['payment_history'],
            'paid_fees' => $result['paid_fees'] ?? [],
            'pagination' => $result['pagination'],
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
        $paymentHistory = collect($latest['rows'])->map(function (array $row) {
            $title = (string) ($row['title'] ?? '');
            $amountPaid = (float) ($row['amount_paid'] ?? 0);
            $discount = (float) ($row['discount'] ?? 0);
            $lateFee = (float) ($row['late_fee'] ?? 0);

            return [
                'id' => $row['id'] ?? null,
                'payment_title' => $title,
                'payment_amount' => round($amountPaid, 2),
                'discount' => round($discount, 2),
                'late_fee' => round($lateFee, 2),
                'net_amount' => round($amountPaid + $lateFee, 2),
                'method' => 'Paid',
                'status' => 'paid',
                'fee_type' => $this->inferFeeTypeFromTitle($title),
                'generated_amount' => 0.0,
                'paid_amount' => round($amountPaid, 2),
                'due_amount' => 0.0,
                'fee_balance_status' => 'paid',
                'payment_date' => $row['payment_date'] ?? null,
                'payment_date_formatted' => $row['payment_datetime'] ?? null,
                'accountant' => $row['received_by'] ?? null,
                'student_code' => $row['student_code'] ?? null,
                'student_name' => $row['student_name'] ?? null,
                'parent_name' => $row['parent_name'] ?? null,
            ];
        })->values();

        $total = $paymentHistory->count();
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page = max(1, min($page, $lastPage));
        $offset = ($page - 1) * $perPage;
        $slice = $paymentHistory->slice($offset, $perPage)->values();

        $monthlyFee = $student->monthly_fee !== null ? (float) $student->monthly_fee : 0.0;
        $annualFee = $monthlyFee * 12;
        $totalPaid = (float) ($latest['total_amount_paid'] ?? 0);

        return [
            'payment_history' => $slice,
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

            $norm = $this->normalizedStudentCode($studentCode);

            // Get payments for this student in the selected year (same matching as payment-history / web-aligned due)
            $payments = StudentPayment::query()
                ->whereRaw('LOWER(TRIM(student_code)) = ?', [$norm])
                ->whereYear('payment_date', $feeYear)
                ->orderBy('payment_date', 'desc')
                ->get();

            // Receipt totals only — exclude Generated / Installment (same as payment-history summary)
            $actualPaidPayments = $payments->filter(function ($payment) {
                $method = strtolower(trim((string) ($payment->method ?? '')));
                return !in_array($method, ['generated', 'installment'], true);
            });
            $paid = (float) $actualPaidPayments->sum('payment_amount');
            $discount = (float) $actualPaidPayments->sum('discount');
            $lateFee = (float) $actualPaidPayments->sum('late_fee');

            // Initial amount is the monthly_fee from student record
            $initialAmount = $student->monthly_fee !== null ? (float) $student->monthly_fee : 0.0;
            
            // Footer "Due" column total from Fee Payment search results table (see FeePaymentWebTables::searchResultsForStudent).
            $dueAmount = FeePaymentWebTables::searchResultsForStudent($student)['totals']['due'];

            // Receipt lines only when embedding history from /fees (no Generated stubs)
            $paymentHistory = null;
            if ($includeHistory) {
                $paymentHistory = $actualPaidPayments->map(function ($payment) {
                    return [
                        'id' => $payment->id,
                        'payment_title' => $payment->payment_title,
                        'payment_amount' => (float) $payment->payment_amount,
                        'discount' => (float) $payment->discount,
                        'late_fee' => (float) $payment->late_fee,
                        'method' => $payment->method,
                        'payment_date' => $payment->payment_date ? $payment->payment_date->format('Y-m-d') : null,
                        'payment_date_formatted' => $payment->payment_date ? $payment->payment_date->format('d M Y') : null,
                        'accountant' => $payment->accountant,
                        'created_at' => $payment->created_at ? $payment->created_at->format('Y-m-d H:i:s') : null,
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
                    'total_payments' => $actualPaidPayments->count(),
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

            $includeAllRecords = filter_var($request->get('include_all_records', false), FILTER_VALIDATE_BOOLEAN);

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

            $injectSynthTransport = !$latestOnly && $this->shouldInjectSyntheticTransport($student, $feeYear);
            $syntheticTransportRow = $injectSynthTransport ? $this->buildSyntheticTransportHistoryRow($student) : null;

            $feeTitleBalances = $feeMath['balances'];

            // Format payment history
            $paymentHistory = $payments->map(function ($payment) use ($student, $feeTitleBalances) {
                // Treat both Generated and Installment as unpaid/generated entries
                $method = strtolower(trim((string) $payment->method));
                $status = in_array($method, ['generated', 'installment'], true) ? 'generated' : 'paid';

                // Display-only: old admission/other Generated stubs sometimes carry backdated payment_date (e.g. April)
                // while the student row was created later (May). Leave monthly installments unchanged.
                $paymentTitleRaw = $payment->payment_title ?? '';
                $displayPaymentDate = $payment->payment_date ? Carbon::parse($payment->payment_date) : null;
                $isMonthlyTitle = stripos((string) $paymentTitleRaw, 'Monthly Fee') !== false;
                if (
                    $displayPaymentDate
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

                // Split "partial" like installments: receipt lines show paid + zero due; generated/installment
                // stub carries the remaining bill as unpaid (so apps do not see two identical partial rows).
                if ($feeBalanceStatus === 'partial') {
                    if ($status === 'paid') {
                        $dueForTitle = 0.0;
                        $feeBalanceStatus = 'paid';
                    } elseif (in_array($method, ['generated', 'installment'], true)) {
                        $feeBalanceStatus = 'partial';
                    }
                }

                $netAmount = $status === 'generated'
                    ? (float) $payment->payment_amount - (float) $payment->discount + (float) $payment->late_fee
                    : (float) $payment->payment_amount + (float) $payment->late_fee;

                // Generated stub on a partially paid title: show outstanding net (matches due_amount), not full bill.
                if ($status === 'generated' && $feeBalanceStatus === 'partial') {
                    $netAmount = (float) $dueForTitle;
                    $generatedAmount = (float) $dueForTitle;
                }

                return [
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
                ];
            });

            $paymentHistory = $paymentHistory->values();
            if ($syntheticTransportRow !== null && $payments->currentPage() === 1) {
                $paymentHistory = collect([$syntheticTransportRow])->concat($paymentHistory)->values();
            }

            // Calculate fee summary
            $monthlyFee = $student->monthly_fee !== null ? (float) $student->monthly_fee : 0.0;
            $annualFee = $monthlyFee * 12;

            // Totals over the same scope as the list (all time if no fee_year)
            $allPaymentsQuery = StudentPayment::query()
                ->whereRaw('LOWER(TRIM(student_code)) = ?', [$normalizedStudentCode]);
            if ($feeYear !== null) {
                $allPaymentsQuery->whereYear('payment_date', $feeYear);
            }
            $allPayments = $allPaymentsQuery->get();
            
            // Calculate yearly totals from actually paid records only
            // (Generated/Installment are due entries, not paid payments)
            $actualPaidPayments = $allPayments->filter(function ($payment) {
                $method = strtolower(trim((string) ($payment->method ?? '')));
                return !in_array($method, ['generated', 'installment'], true);
            });

            $totalPaid = (float) $actualPaidPayments->sum('payment_amount');
            $totalDiscount = (float) $actualPaidPayments->sum('discount');
            $totalLateFee = (float) $actualPaidPayments->sum('late_fee');
            
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
                'payment_history' => $paymentHistory,
                'fee_summary' => [
                    'monthly_fee' => $monthlyFee,
                    'annual_fee' => $annualFee,
                    'total_paid' => $totalPaid,
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

