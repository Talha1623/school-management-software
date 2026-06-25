<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ParentAccount;
use App\Models\Student;
use App\Models\GeneralSetting;
use App\Services\FeePaymentWebTables;
use App\Services\FeeVoucherBuilder;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ParentFeeVoucherController extends Controller
{
    private function voucherBuilder(): FeeVoucherBuilder
    {
        return new FeeVoucherBuilder();
    }

    /**
     * @return array{currentYear: int, displayYear: string, vouchersFor: string}
     */
    private function resolveVoucherPeriod(Request $request): array
    {
        $context = $this->voucherBuilder()->resolveContext($request, false);
        $vouchersFor = trim((string) $request->get('vouchers_for', ''));
        if ($vouchersFor === '') {
            $vouchersFor = date('F');
        }

        $yearRaw = $request->get('year');
        $currentYear = $yearRaw !== null && $yearRaw !== ''
            ? (int) $yearRaw
            : (int) $context['currentYear'];

        return [
            'currentYear' => $currentYear,
            'displayYear' => (string) ($context['displayYear'] ?? (string) $currentYear),
            'vouchersFor' => $vouchersFor,
        ];
    }

    /**
     * Get fee vouchers for authenticated parent's students
     * 
     * GET /api/parent/fee-vouchers
     * GET /api/parent/fee-vouchers?student_id=123
     * GET /api/parent/fee-vouchers?vouchers_for=March
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $parent = $request->user();

            if (!$parent || !($parent instanceof ParentAccount)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid user type. Parent authentication required.',
                    'token' => null,
                ], 403);
            }

            // Get parent's students
            $studentsQuery = $parent->students()->whereNotNull('student_code')
                ->where('student_code', '!=', '');

            // Filter by specific student if provided
            if ($request->filled('student_id')) {
                $studentsQuery->where('id', $request->student_id);
            }

            $students = $studentsQuery->orderBy('student_name')->get();

            if ($students->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'message' => 'No students found for this parent account.',
                    'data' => [
                        'vouchers' => [],
                        'total_students' => 0,
                    ],
                    'token' => $request->user()->currentAccessToken()->token ?? null,
                ], 200);
            }

            $period = $this->resolveVoucherPeriod($request);

            // Return every linked child â€” same family list as web; paid titles are omitted from pending lines.
            $vouchers = [];
            foreach ($students as $student) {
                $vouchers[] = $this->generateVoucherForStudent(
                    $student,
                    $period['vouchersFor'],
                    $period['currentYear'],
                    $period['displayYear']
                );
            }

            $settings = GeneralSetting::getSettings();

            return response()->json([
                'success' => true,
                'message' => 'Fee vouchers retrieved successfully.',
                'data' => [
                    'parent_id' => $parent->id,
                    'parent_name' => $parent->name,
                    'school_name' => $settings->school_name ?? 'School',
                    'school_address' => $settings->school_address ?? '',
                    'school_phone' => $settings->school_phone ?? '',
                    'school_email' => $settings->school_email ?? '',
                    'vouchers_for' => $period['vouchersFor'],
                    'year' => $period['currentYear'],
                    'year_label' => $period['displayYear'],
                    'total_students' => $students->count(),
                    'students_with_pending_fees' => collect($vouchers)->where('has_pending_fees', true)->count(),
                    'vouchers' => $vouchers,
                ],
                'token' => $request->user()->currentAccessToken()->token ?? null,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving fee vouchers: ' . $e->getMessage(),
                'token' => null,
            ], 500);
        }
    }

    /**
     * Get fee voucher for a specific student
     * 
     * GET /api/parent/fee-vouchers/{student_id}
     * 
     * @param Request $request
     * @param int $student_id
     * @return JsonResponse
     */
    public function show(Request $request, $student_id): JsonResponse
    {
        try {
            $parent = $request->user();

            if (!$parent || !($parent instanceof ParentAccount)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid user type. Parent authentication required.',
                    'token' => null,
                ], 403);
            }

            // Verify student belongs to this parent
            $student = $parent->students()->where('id', $student_id)->first();

            if (!$student) {
                return response()->json([
                    'success' => false,
                    'message' => 'Student not found or not associated with this parent account.',
                    'token' => null,
                ], 404);
            }

            if (empty($student->student_code)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Student code not found for this student.',
                    'token' => null,
                ], 404);
            }

            $period = $this->resolveVoucherPeriod($request);
            $voucher = $this->generateVoucherForStudent(
                $student,
                $period['vouchersFor'],
                $period['currentYear'],
                $period['displayYear']
            );

            $settings = GeneralSetting::getSettings();

            return response()->json([
                'success' => true,
                'message' => $voucher['has_pending_fees']
                    ? 'Fee voucher retrieved successfully.'
                    : 'No pending fees found for this student.',
                'data' => [
                    'parent_id' => $parent->id,
                    'parent_name' => $parent->name,
                    'school_name' => $settings->school_name ?? 'School',
                    'school_address' => $settings->school_address ?? '',
                    'school_phone' => $settings->school_phone ?? '',
                    'school_email' => $settings->school_email ?? '',
                    'vouchers_for' => $period['vouchersFor'],
                    'year' => $period['currentYear'],
                    'year_label' => $period['displayYear'],
                    'student_id' => $student->id,
                    'student_name' => $student->student_name,
                    'voucher' => $voucher,
                ],
                'token' => $request->user()->currentAccessToken()->token ?? null,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving fee voucher: ' . $e->getMessage(),
                'token' => null,
            ], 500);
        }
    }

    /**
     * Render printable fee voucher (web-like layout) for mobile app/WebView.
     *
     * GET /api/parent/fee-vouchers/{student_id}/pdf?vouchers_for=March
     */
    public function pdf(Request $request, $student_id)
    {
        try {
            $parent = $request->user();

            if (!$parent || !($parent instanceof ParentAccount)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid user type. Parent authentication required.',
                    'token' => null,
                ], 403);
            }

            $student = $parent->students()->where('id', $student_id)->first();
            if (!$student) {
                return response()->json([
                    'success' => false,
                    'message' => 'Student not found or not associated with this parent account.',
                    'token' => null,
                ], 404);
            }

            $period = $this->resolveVoucherPeriod($request);
            $built = $this->voucherBuilder()->buildForStudent(
                $student,
                $period['vouchersFor'],
                $period['currentYear'],
                $period['displayYear']
            );
            $voucher = $this->formatVoucherForApi($built, $student);

            if (!$voucher['has_pending_fees']) {
                return response()->json([
                    'success' => true,
                    'message' => 'No pending fees found for this student.',
                    'data' => null,
                    'token' => $request->user()->currentAccessToken()->token ?? null,
                ], 200);
            }

            $settings = GeneralSetting::getSettings();
            $type = 'three_copies';
            $copyLabels = ['Bank Copy', 'Parent Copy', 'School Copy'];

            $voucherForPrint = [
                'student' => $student,
                'pending_fees' => collect($built['pending_fees'] ?? []),
                'current_fees_subtotal' => (float) ($built['current_fees_subtotal'] ?? 0),
                'arrears_amount' => (float) ($built['arrears_amount'] ?? 0),
                'subtotal' => (float) ($built['subtotal'] ?? 0),
                'late_fee' => (float) ($built['late_fee'] ?? 0),
                'wallet_credit' => (float) ($built['wallet_credit'] ?? 0),
                'wallet_applied' => (float) ($built['wallet_applied'] ?? 0),
                'total' => (float) ($built['total'] ?? 0),
                'after_due_date' => (float) ($built['after_due_date'] ?? $built['total'] ?? 0),
                'due_date' => $built['due_date'] instanceof Carbon
                    ? $built['due_date']
                    : Carbon::parse($built['due_date']),
                'voucher_validity' => $built['voucher_validity'] instanceof Carbon
                    ? $built['voucher_validity']
                    : Carbon::parse($built['voucher_validity']),
                'voucher_number' => $built['voucher_number'] ?? null,
                'fee_history' => collect($built['fee_history'] ?? []),
                'month' => $built['month'] ?? $period['vouchersFor'],
                'year' => $built['year'] ?? $period['currentYear'],
            ];

            $viewData = [
                'vouchers' => [$voucherForPrint],
                'type' => $type,
                'vouchersFor' => $period['vouchersFor'],
                'currentYear' => $period['currentYear'],
                'copyLabels' => $copyLabels,
                'settings' => $settings,
            ];

            // Optional: HTML preview (for WebView/debug)
            if (strtolower((string) $request->query('format', 'pdf')) === 'html') {
                $html = view('accounting.fee-voucher.print', $viewData)->render();
                return response($html, 200)->header('Content-Type', 'text/html; charset=UTF-8');
            }

            // Generate real PDF from the same web voucher template.
            $pdf = Pdf::loadView('accounting.fee-voucher.print', $viewData)
                ->setPaper('a4', 'portrait');

            $fileName = 'fee-voucher-' . ($voucher['voucher_number'] ?? $student->id) . '.pdf';
            return $pdf->stream($fileName);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while generating fee voucher print: ' . $e->getMessage(),
                'token' => null,
            ], 500);
        }
    }

    /**
     * Same voucher math as web Fee Voucher print (FeeVoucherBuilder).
     */
    private function generateVoucherForStudent(
        Student $student,
        string $vouchersFor,
        int $currentYear,
        string $displayYear
    ): array {
        $built = $this->voucherBuilder()->buildForStudent($student, $vouchersFor, $currentYear, $displayYear);

        return $this->formatVoucherForApi($built, $student);
    }

    /**
     * @param array<string, mixed> $built
     */
    private function formatVoucherForApi(array $built, Student $student): array
    {
        $pendingFees = collect($built['pending_fees'] ?? [])->map(function ($fee) {
            $amount = round((float) ($fee['amount'] ?? 0), 2);

            return [
                'description' => (string) ($fee['description'] ?? ''),
                'amount' => $amount,
                'due_amount' => $amount,
            ];
        })->values();

        $searchResults = FeePaymentWebTables::searchResultsForStudent($student);
        $feeRows = collect($searchResults['rows'] ?? [])
            ->filter(fn ($row) => (float) ($row['due'] ?? 0) > 0.0001)
            ->map(function ($row) {
                return [
                    'title' => (string) ($row['fee_type'] ?? ''),
                    'total' => round((float) ($row['total'] ?? 0), 2),
                    'discount' => round((float) ($row['discount'] ?? 0), 2),
                    'late_fee' => round((float) ($row['late_fee'] ?? 0), 2),
                    'generated_fee' => round((float) ($row['generated_fee'] ?? 0), 2),
                    'paid' => round((float) ($row['paid'] ?? 0), 2),
                    'due' => round((float) ($row['due'] ?? 0), 2),
                    'status' => (string) ($row['status'] ?? ''),
                ];
            })
            ->values();

        $feeHistoryRaw = $built['fee_history'] ?? [];
        $year = (int) ($built['year'] ?? date('Y'));
        $feeHistory = [];
        foreach ([
            'January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December',
        ] as $month) {
            $row = $feeHistoryRaw[$month] ?? ['total' => 0, 'paid' => 0];
            $total = round((float) ($row['total'] ?? 0), 2);
            $paid = round((float) ($row['paid'] ?? 0), 2);
            $feeHistory[] = [
                'month' => $month,
                'year' => $year,
                'total' => $total,
                'paid' => $paid,
                'pending' => round(max(0.0, $total - $paid), 2),
            ];
        }

        $dueDate = $built['due_date'] instanceof Carbon
            ? $built['due_date']
            : Carbon::parse($built['due_date']);
        $voucherValidity = $built['voucher_validity'] instanceof Carbon
            ? $built['voucher_validity']
            : Carbon::parse($built['voucher_validity']);

        $total = round((float) ($built['total'] ?? 0), 2);
        $generatedTitleKeys = $feeRows
            ->map(fn ($row) => strtolower(trim((string) ($row['title'] ?? ''))))
            ->filter(fn ($title) => $title !== '')
            ->values();

        $sumGeneratedByKeyword = static function ($rows, string $keyword): float {
            return round((float) $rows->sum(function ($row) use ($keyword) {
                $title = strtolower(trim((string) ($row['title'] ?? '')));
                if (!str_contains($title, $keyword)) {
                    return 0.0;
                }

                return (float) ($row['generated_fee'] ?? 0);
            }), 2);
        };

        return [
            'student_id' => $student->id,
            'student_name' => $student->student_name,
            'student_code' => $student->student_code,
            'gr_number' => $student->gr_number ?? null,
            'class' => $student->class,
            'section' => $student->section,
            'campus' => $student->campus,
            'father_name' => $student->father_name ?? null,
            'father_phone' => $student->father_phone ?? null,
            'discounted_student' => (bool) ($student->discounted_student ?? false),
            'discount_reason' => $student->discount_reason ?? null,
            'transport_route' => $student->transport_route ?? null,
            'transport_fare' => round((float) ($student->transport_fare ?? 0), 2),
            'generate_admission_fee' => $generatedTitleKeys->contains(fn ($title) => str_contains($title, 'admission'))
                || (bool) ($student->generate_admission_fee ?? false),
            'generate_other_fee' => $sumGeneratedByKeyword($feeRows, 'custom') > 0
                || (bool) ($student->generate_other_fee ?? false),
            'other_fee_type' => $student->fee_type ?? null,
            'admission_fee_configured_amount' => round((float) ($student->admission_fee_amount ?? 0), 2),
            'other_fee_configured_amount' => round((float) ($student->other_fee_amount ?? 0), 2),
            'has_pending_fees' => $total > 0.0001,
            'pending_fees' => $pendingFees,
            'fee_rows' => $feeRows,
            'current_fees_subtotal' => round((float) ($built['current_fees_subtotal'] ?? 0), 2),
            'arrears_amount' => round((float) ($built['arrears_amount'] ?? 0), 2),
            'subtotal' => round((float) ($built['subtotal'] ?? 0), 2),
            'late_fee' => round((float) ($built['late_fee'] ?? 0), 2),
            'wallet_credit' => round((float) ($built['wallet_credit'] ?? 0), 2),
            'wallet_applied' => round((float) ($built['wallet_applied'] ?? 0), 2),
            'total' => $total,
            'due_amount' => $total,
            'generated_total' => round((float) $feeRows->sum('generated_fee'), 2),
            'transport_fee_generated' => $sumGeneratedByKeyword($feeRows, 'transport'),
            'card_fee_generated' => $sumGeneratedByKeyword($feeRows, 'card'),
            'admission_fee_generated' => $sumGeneratedByKeyword($feeRows, 'admission'),
            'other_fee_generated' => round((float) $feeRows->sum(function ($row) {
                $title = strtolower(trim((string) ($row['title'] ?? '')));
                if (str_contains($title, 'monthly fee')
                    || str_contains($title, 'transport')
                    || str_contains($title, 'card')
                    || str_contains($title, 'admission')) {
                    return 0.0;
                }

                return (float) ($row['generated_fee'] ?? 0);
            }), 2),
            'due_date' => $dueDate->format('Y-m-d'),
            'due_date_formatted' => $dueDate->format('d M Y'),
            'voucher_validity' => $voucherValidity->format('Y-m-d'),
            'voucher_validity_formatted' => $voucherValidity->format('d M Y'),
            'voucher_number' => (string) ($built['voucher_number'] ?? ''),
            'fee_history' => $feeHistory,
            'month' => (string) ($built['month'] ?? date('F')),
            'year' => $year,
            'year_label' => (string) ($built['year_label'] ?? (string) $year),
        ];
    }
}
