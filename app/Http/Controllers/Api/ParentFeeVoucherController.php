<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ParentAccount;
use App\Models\Student;
use App\Models\StudentPayment;
use App\Models\MonthlyFee;
use App\Models\StudentDiscount;
use App\Models\GeneralSetting;
use App\Services\FeePaymentWebTables;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ParentFeeVoucherController extends Controller
{
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
                    ],
                    'token' => $request->user()->currentAccessToken()->token ?? null,
                ], 200);
            }

            $currentYear = (int) $request->get('year', date('Y'));
            $vouchersFor = $request->get('vouchers_for', date('F')); // Month name

            // Get fee vouchers for each student
            $vouchers = [];
            foreach ($students as $student) {
                $strictMonth = filter_var($request->get('strict_month', false), FILTER_VALIDATE_BOOLEAN);
                $voucher = $this->generateVoucherForStudent($student, $vouchersFor, $currentYear, $strictMonth);
                if ($voucher) {
                    $vouchers[] = $voucher;
                }
            }

            // Get General Settings for school information
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
                    'vouchers_for' => $vouchersFor,
                    'year' => $currentYear,
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

            $currentYear = (int) $request->get('year', date('Y'));
            $vouchersFor = $request->get('vouchers_for', date('F'));

            $strictMonth = filter_var($request->get('strict_month', false), FILTER_VALIDATE_BOOLEAN);
            $voucher = $this->generateVoucherForStudent($student, $vouchersFor, $currentYear, $strictMonth);

            if (!$voucher) {
                return response()->json([
                    'success' => true,
                    'message' => 'No pending fees found for this student.',
                    'data' => [
                        'student_id' => $student->id,
                        'student_name' => $student->student_name,
                        'voucher' => null,
                    ],
                    'token' => $request->user()->currentAccessToken()->token ?? null,
                ], 200);
            }

            // Get General Settings for school information
            $settings = GeneralSetting::getSettings();

            return response()->json([
                'success' => true,
                'message' => 'Fee voucher retrieved successfully.',
                'data' => [
                    'parent_id' => $parent->id,
                    'parent_name' => $parent->name,
                    'school_name' => $settings->school_name ?? 'School',
                    'school_address' => $settings->school_address ?? '',
                    'school_phone' => $settings->school_phone ?? '',
                    'school_email' => $settings->school_email ?? '',
                    'vouchers_for' => $vouchersFor,
                    'year' => $currentYear,
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

            $currentYear = (int) $request->get('year', date('Y'));
            $vouchersFor = $request->get('vouchers_for', date('F'));
            $strictMonth = filter_var($request->get('strict_month', false), FILTER_VALIDATE_BOOLEAN);
            $voucher = $this->generateVoucherForStudent($student, $vouchersFor, (int) $currentYear, $strictMonth);

            if (!$voucher) {
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
                'pending_fees' => collect($voucher['pending_fees'] ?? []),
                'current_fees_subtotal' => (float) ($voucher['current_fees_subtotal'] ?? 0),
                'arrears_amount' => (float) ($voucher['arrears_amount'] ?? 0),
                'subtotal' => (float) ($voucher['subtotal'] ?? 0),
                'late_fee' => (float) ($voucher['late_fee'] ?? 0),
                'total' => (float) ($voucher['total'] ?? 0),
                'after_due_date' => (float) ($voucher['total'] ?? 0),
                'due_date' => Carbon::parse($voucher['due_date']),
                'voucher_validity' => Carbon::parse($voucher['voucher_validity']),
                'voucher_number' => $voucher['voucher_number'] ?? null,
                'fee_history' => collect($voucher['fee_history'] ?? []),
                'month' => $voucher['month'] ?? $vouchersFor,
                'year' => $voucher['year'] ?? (int) $currentYear,
            ];

            $viewData = [
                'vouchers' => [$voucherForPrint],
                'type' => $type,
                'vouchersFor' => $vouchersFor,
                'currentYear' => (int) $currentYear,
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
     * Generate voucher data for a student
     * 
     * @param Student $student
     * @param string $vouchersFor
     * @param int $currentYear
     * @param bool $strictMonth When true, only selected vouchers_for month is included.
     * @return array|null
     */
    private function generateVoucherForStudent(Student $student, string $vouchersFor, int $currentYear, bool $strictMonth = false): ?array
    {
        // Start from generated/installment rows for this student
        $normalizedStudentCode = strtolower(trim((string) ($student->student_code ?? '')));
        $generatedPayments = StudentPayment::whereRaw('LOWER(TRIM(student_code)) = ?', [$normalizedStudentCode])
            ->whereIn('method', ['Generated', 'Installment'])
            ->orderBy('payment_date', 'asc')
            ->get();

        if ($generatedPayments->isEmpty()) {
            return null; // No pending fees
        }

        // Web-aligned unpaid rows (paid rows are already excluded here)
        $searchResults = FeePaymentWebTables::searchResultsForStudent($student);
        $unpaidRows = collect($searchResults['rows'] ?? []);

        // Optional strict month filter.
        // Default false to match Fee Payment > Search Results (all pending generated rows).
        if ($strictMonth && $vouchersFor) {
            $monthlyTitle = "Monthly Fee - {$vouchersFor} {$currentYear}";
            $transportTitle = "Transport Fee - {$vouchersFor} {$currentYear}";
            $unpaidRows = $unpaidRows->filter(function($row) use ($monthlyTitle, $transportTitle) {
                $title = (string) ($row['fee_type'] ?? '');
                return stripos($title, $monthlyTitle) !== false
                    || stripos($title, $transportTitle) !== false;
            })->values();
        }

        if ($unpaidRows->isEmpty()) {
            return null; // No unpaid rows
        }

        // Keep generated rows only for unpaid titles for subsequent date/arrears logic.
        $unpaidTitles = $unpaidRows->pluck('fee_type')
            ->map(fn($t) => strtolower(trim((string) $t)))
            ->filter(fn($t) => $t !== '')
            ->unique()
            ->values();
        $pendingPayments = $generatedPayments->filter(function($payment) use ($unpaidTitles) {
            $title = strtolower(trim((string) ($payment->payment_title ?? '')));
            return $unpaidTitles->contains($title);
        })->values();

        $transportFallbackTitle = "Transport Fee - {$vouchersFor} {$currentYear}";
        $hasTransportConfigured = !empty($student->transport_route) && (float) ($student->transport_fare ?? 0) > 0;
        $hasTransportPending = $pendingPayments->contains(function ($payment) {
            return str_contains(strtolower(trim((string) ($payment->payment_title ?? ''))), 'transport');
        });

        // If transport is configured at admission but no generated transport row exists yet,
        // expose it as pending in voucher API so parent app still shows transport dues.
        if ($hasTransportConfigured && !$hasTransportPending) {
            $pendingPayments->push((object) [
                'payment_title' => $transportFallbackTitle,
                'payment_amount' => (float) ($student->transport_fare ?? 0),
                'discount' => 0,
                'late_fee' => 0,
                'payment_date' => Carbon::now()->toDateString(),
            ]);
        }

        // Build fee-payment rows exactly from web pending rows.
        $feeRows = $unpaidRows->map(function ($row) {
            return [
                'title' => (string) ($row['fee_type'] ?? 'Generated Fee'),
                'total' => round((float) ($row['total'] ?? 0), 2),
                'discount' => round((float) ($row['discount'] ?? 0), 2),
                'late_fee' => round((float) ($row['late_fee'] ?? 0), 2),
                'generated_fee' => round((float) ($row['generated_fee'] ?? 0), 2),
                'paid' => round((float) ($row['paid'] ?? 0), 2),
                'due' => round((float) ($row['due'] ?? 0), 2),
            ];
        })->values();

        // Keep only rows with actual due > 0.
        $feeRows = $feeRows->filter(fn($r) => (float) ($r['due'] ?? 0) > 0)->values();

        if ($hasTransportConfigured) {
            $hasTransportFeeRow = $feeRows->contains(function ($row) {
                return str_contains(strtolower(trim((string) ($row['title'] ?? ''))), 'transport');
            });
            if (!$hasTransportFeeRow) {
                $transportAmount = round((float) ($student->transport_fare ?? 0), 2);
                $feeRows->push([
                    'title' => $transportFallbackTitle,
                    'total' => $transportAmount,
                    'discount' => 0.0,
                    'late_fee' => 0.0,
                    'generated_fee' => $transportAmount,
                    'paid' => 0.0,
                    'due' => $transportAmount,
                ]);
            }
        }

        if ($feeRows->isEmpty()) {
            return null;
        }

        // Use web-calculated due per title so partial payments are reflected correctly.
        $dueByTitle = $feeRows->mapWithKeys(function ($row) {
            $key = strtolower(trim((string) ($row['title'] ?? '')));
            return [$key => round((float) ($row['due'] ?? 0), 2)];
        });

        /*
        Previous strict-month filter was applied directly on generated payments:
            $pendingPayments = $pendingPayments->filter(function($payment) use ($monthlyTitle, $transportTitle) {
                $title = (string) ($payment->payment_title ?? '');
                // Strict month scope to match Fee Payment search-results behavior.
                // Also supports installment titles like "Monthly Fee - July 2026/1".
                return stripos($title, $monthlyTitle) !== false
                    || stripos($title, $transportTitle) !== false;
            });
        */

        // Yearly fee history: aggregate all Monthly + Transport rows per month (installments, multiple rows).
        $feeHistory = $this->buildYearlyFeeHistory($normalizedStudentCode, $currentYear);

        // Calculate late fee from pending payments
        $lateFee = $pendingPayments->sum(function($payment) {
            return (float) ($payment->late_fee ?? 0);
        });

        // Add dynamic late fee for overdue monthly fees
        $dynamicLateFee = 0;
        foreach ($pendingPayments as $payment) {
            if ((float) ($payment->late_fee ?? 0) > 0) {
                continue;
            }
            if (!preg_match('/Monthly Fee - (\w+) (\d{4})/i', $payment->payment_title ?? '', $matches)) {
                continue;
            }
            $feeMonth = $matches[1];
            $feeYear = $matches[2];
            $dueDateForPayment = $payment->payment_date ? Carbon::parse($payment->payment_date) : null;
            if (!$dueDateForPayment || !$dueDateForPayment->lt(Carbon::today())) {
                continue;
            }

            $monthlyFeeRecord = MonthlyFee::where('fee_month', $feeMonth)
                ->where('fee_year', $feeYear)
                ->where(function($q) use ($student) {
                    $q->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($student->campus ?? ''))])
                      ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($student->class ?? ''))])
                      ->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($student->section ?? ''))]);
                })
                ->first();

            if ($monthlyFeeRecord && (float) $monthlyFeeRecord->late_fee > 0) {
                $dynamicLateFee += (float) $monthlyFeeRecord->late_fee;
            }
        }
        $lateFee += $dynamicLateFee;
        
        // Get the latest due date from pending payments
        $latestDueDate = null;
        if ($pendingPayments->isNotEmpty()) {
            $maxDate = $pendingPayments->max(function($payment) {
                return $payment->payment_date ? Carbon::parse($payment->payment_date) : null;
            });
            if ($maxDate) {
                $latestDueDate = $maxDate;
            }
        }
        
        if (!$latestDueDate) {
            $monthlyFeeRecord = MonthlyFee::where('fee_month', $vouchersFor)
                ->where('fee_year', $currentYear)
                ->where(function($q) use ($student) {
                    $q->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($student->campus ?? ''))])
                      ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($student->class ?? ''))])
                      ->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($student->section ?? ''))]);
                })
                ->first();
            
            $latestDueDate = $monthlyFeeRecord ? Carbon::parse($monthlyFeeRecord->due_date) : Carbon::now()->addDays(15);
        }
        
        $dueDate = $latestDueDate;
        $voucherValidity = Carbon::parse($dueDate)->addDays(5);
        
        // Generate voucher number
        $voucherNumber = strtoupper(substr($vouchersFor, 0, 3)) . '-' . str_pad($student->id, 5, '0', STR_PAD_LEFT) . '-' . substr($currentYear, -2);
        
        $isMonthlyOrTransport = function ($title) {
            return preg_match('/^(Monthly Fee|Transport Fee) - /i', (string) $title);
        };

        // Calculate Arrears (Overdue monthly/transport only)
        $today = Carbon::today();
        $arrearsPayments = $pendingPayments->filter(function($payment) use ($today, $isMonthlyOrTransport) {
            if (!$isMonthlyOrTransport($payment->payment_title ?? '')) {
                return false;
            }
            if (!$payment->payment_date) {
                return false;
            }
            $dueDate = Carbon::parse($payment->payment_date);
            return $dueDate->lt($today);
        });
        
        $arrearsAmount = $arrearsPayments->sum(function($payment) {
            return (float) ($payment->payment_amount ?? 0) - (float) ($payment->discount ?? 0);
        });
        
        // Current fees (not overdue yet) + always include custom/admission fees
        $currentFeesPayments = $pendingPayments->filter(function($payment) use ($today, $isMonthlyOrTransport) {
            if (!$isMonthlyOrTransport($payment->payment_title ?? '')) {
                return true;
            }
            if (!$payment->payment_date) {
                return true;
            }
            $dueDate = Carbon::parse($payment->payment_date);
            return $dueDate->gte($today);
        });

        // For explicit month voucher flow, don't relabel selected-month items as "arrears".
        if ($strictMonth && !empty($vouchersFor)) {
            $arrearsPayments = collect();
            $currentFeesPayments = $pendingPayments;
        }
        
        // Format pending fees for display
        $monthlyFees = collect();
        $customFees = collect();
        $addedFeeTitleKeys = [];
        
        foreach ($currentFeesPayments as $payment) {
            $titleKey = strtolower(trim((string) ($payment->payment_title ?? '')));
            if ($titleKey === '') {
                continue;
            }
            $amount = $dueByTitle->get($titleKey, (float) ($payment->payment_amount ?? 0) - (float) ($payment->discount ?? 0));

            if (preg_match('/Monthly Fee - (\w+) (\d+)/', $payment->payment_title, $matches)) {
                if (isset($addedFeeTitleKeys[$titleKey])) {
                    continue;
                }
                $addedFeeTitleKeys[$titleKey] = true;
                $month = $matches[1];
                $year = $matches[2];
                $description = "Monthly Fee Of {$month} ({$year})";
                $monthlyFees->push([
                    'description' => $description,
                    'amount' => $amount,
                    'sort_order' => 1,
                ]);
            } elseif (preg_match('/Transport Fee - (\w+) (\d+)/', $payment->payment_title, $matches)) {
                if (isset($addedFeeTitleKeys[$titleKey])) {
                    continue;
                }
                $addedFeeTitleKeys[$titleKey] = true;
                $month = $matches[1];
                $year = $matches[2];
                $routeLabel = !empty($student->transport_route)
                    ? "Transport Route ({$student->transport_route})"
                    : 'Transport Route';
                $description = "{$routeLabel} - {$month} ({$year})";
                $monthlyFees->push([
                    'description' => $description,
                    'amount' => $amount,
                    'sort_order' => 1,
                ]);
            }
        }

        // Include overdue monthly/transport as explicit arrears rows so app/web can display line items
        foreach ($arrearsPayments as $payment) {
            $titleKey = strtolower(trim((string) ($payment->payment_title ?? '')));
            if ($titleKey === '') {
                continue;
            }
            $amount = $dueByTitle->get($titleKey, (float) ($payment->payment_amount ?? 0) - (float) ($payment->discount ?? 0));
            $title = (string) ($payment->payment_title ?? '');

            if (preg_match('/Monthly Fee - (\w+) (\d+)/', $title, $matches)) {
                if (isset($addedFeeTitleKeys[$titleKey])) {
                    continue;
                }
                $addedFeeTitleKeys[$titleKey] = true;
                $month = $matches[1];
                $year = $matches[2];
                $monthlyFees->push([
                    'description' => "Arrears - Monthly Fee Of {$month} ({$year})",
                    'amount' => $amount,
                    'sort_order' => 0,
                ]);
            } elseif (preg_match('/Transport Fee - (\w+) (\d+)/', $title, $matches)) {
                if (isset($addedFeeTitleKeys[$titleKey])) {
                    continue;
                }
                $addedFeeTitleKeys[$titleKey] = true;
                $month = $matches[1];
                $year = $matches[2];
                $routeLabel = !empty($student->transport_route)
                    ? "Transport Route ({$student->transport_route})"
                    : 'Transport Route';
                $monthlyFees->push([
                    'description' => "Arrears - {$routeLabel} - {$month} ({$year})",
                    'amount' => $amount,
                    'sort_order' => 0,
                ]);
            }
        }

        // Always include custom/admission fees
        $customFeePayments = $pendingPayments->filter(function ($payment) use ($isMonthlyOrTransport) {
            return !$isMonthlyOrTransport($payment->payment_title ?? '');
        });
        foreach ($customFeePayments as $payment) {
            $titleKey = strtolower(trim((string) ($payment->payment_title ?? '')));
            if ($titleKey === '' || isset($addedFeeTitleKeys[$titleKey])) {
                continue;
            }
            $addedFeeTitleKeys[$titleKey] = true;
            $amount = $dueByTitle->get($titleKey, (float) ($payment->payment_amount ?? 0) - (float) ($payment->discount ?? 0));
            if (strtolower(trim($payment->payment_title)) === 'admission fee') {
                $customFees->push([
                    'description' => 'Generate Admission Fee',
                    'amount' => $amount,
                    'sort_order' => 2,
                ]);
            } else {
                $customFees->push([
                    'description' => $payment->payment_title ?? 'Custom Fee',
                    'amount' => $amount,
                    'sort_order' => 2,
                ]);
            }
        }
        
        // Combine and sort: arrears first, then current monthly/transport, then custom fees
        $pendingFeesList = $monthlyFees->merge($customFees)->sortBy('sort_order')->map(function($fee) {
            return [
                'description' => $fee['description'],
                'amount' => $fee['amount'],
                'due_amount' => $fee['amount'],
            ];
        })->values();

        // Calculate totals from fee rows.
        // generated_total = total generated amount
        // total/due_amount  = actual payable due amount (after partial payments/discounts)
        $generatedTotal = (float) $feeRows->sum(function ($row) {
            return (float) ($row['generated_fee'] ?? 0);
        });
        $dueTotal = (float) $feeRows->sum(function ($row) {
            return (float) ($row['due'] ?? 0);
        });
        $currentFeesSubtotal = $pendingFeesList->sum('amount');
        $subtotal = max(0, $dueTotal);
        $total = $dueTotal;

        // For parent voucher API, payable subtotal should reflect due amount.
        $currentFeesSubtotal = $dueTotal;
        $arrearsAmount = 0;
        $lateFee = 0;

        $transportFeeGenerated = round((float) collect($feeRows)->sum(function ($row) {
            $title = strtolower(trim((string) ($row['title'] ?? '')));
            if (!str_contains($title, 'transport')) {
                return 0.0;
            }
            return (float) ($row['generated_fee'] ?? 0);
        }), 2);

        $cardFeeGenerated = round((float) collect($feeRows)->sum(function ($row) {
            $title = strtolower(trim((string) ($row['title'] ?? '')));
            if (!str_contains($title, 'card')) {
                return 0.0;
            }
            return (float) ($row['generated_fee'] ?? 0);
        }), 2);

        $admissionFeeGenerated = round((float) collect($feeRows)->sum(function ($row) {
            $title = strtolower(trim((string) ($row['title'] ?? '')));
            if (!str_contains($title, 'admission')) {
                return 0.0;
            }
            return (float) ($row['generated_fee'] ?? 0);
        }), 2);

        $otherFeeGenerated = round((float) collect($feeRows)->sum(function ($row) {
            $title = strtolower(trim((string) ($row['title'] ?? '')));
            if (str_contains($title, 'monthly fee')
                || str_contains($title, 'transport')
                || str_contains($title, 'card')
                || str_contains($title, 'admission')) {
                return 0.0;
            }
            return (float) ($row['generated_fee'] ?? 0);
        }), 2);

        $generatedTitleKeys = collect($feeRows)
            ->map(fn($row) => strtolower(trim((string) ($row['title'] ?? ''))))
            ->filter(fn($t) => $t !== '')
            ->values();
        
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
            'generate_admission_fee' => $generatedTitleKeys->contains(fn($t) => str_contains($t, 'admission'))
                || (bool) ($student->generate_admission_fee ?? false),
            'generate_other_fee' => $otherFeeGenerated > 0
                || (bool) ($student->generate_other_fee ?? false),
            'other_fee_type' => $student->fee_type ?? null,
            'admission_fee_configured_amount' => round((float) ($student->admission_fee_amount ?? 0), 2),
            'other_fee_configured_amount' => round((float) ($student->other_fee_amount ?? 0), 2),
            'pending_fees' => $pendingFeesList,
            'fee_rows' => $feeRows,
            'current_fees_subtotal' => round($currentFeesSubtotal, 2),
            'arrears_amount' => round($arrearsAmount, 2),
            'subtotal' => round($subtotal, 2),
            'late_fee' => round($lateFee, 2),
            'total' => round($total, 2),
            'due_amount' => round($total, 2),
            'generated_total' => round($generatedTotal, 2),
            'transport_fee_generated' => $transportFeeGenerated,
            'card_fee_generated' => $cardFeeGenerated,
            'admission_fee_generated' => $admissionFeeGenerated,
            'other_fee_generated' => $otherFeeGenerated,
            'due_date' => $dueDate->format('Y-m-d'),
            'due_date_formatted' => $dueDate->format('d M Y'),
            'voucher_validity' => $voucherValidity->format('Y-m-d'),
            'voucher_validity_formatted' => $voucherValidity->format('d M Y'),
            'voucher_number' => $voucherNumber,
            'fee_history' => $feeHistory,
            'month' => $vouchersFor,
            'year' => $currentYear,
        ];
    }

    /**
     * Per-calendar-month fee history for Monthly + Transport (matches web-style rows, incl. installments).
     */
    private function buildYearlyFeeHistory(string $normalizedStudentCode, int $year): array
    {
        $months = [
            'January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December',
        ];

        $feeHistory = [];
        foreach ($months as $month) {
            $feeHistory[] = $this->feeHistoryRowForMonth($normalizedStudentCode, $month, $year);
        }

        return $feeHistory;
    }

    /**
     * @return array{month: string, year: int, total: float, paid: float, pending: float}
     */
    private function feeHistoryRowForMonth(string $normalizedStudentCode, string $monthName, int $year): array
    {
        $rows = $this->paymentsForCalendarMonth($normalizedStudentCode, $monthName, $year);

        $isGeneratedLike = static function ($payment): bool {
            $m = strtolower(trim((string) ($payment->method ?? '')));

            return in_array($m, ['generated', 'installment'], true);
        };

        $netAmount = static function ($payment): float {
            return (float) ($payment->payment_amount ?? 0)
                - (float) ($payment->discount ?? 0)
                + (float) ($payment->late_fee ?? 0);
        };

        $generatedLike = $rows->filter($isGeneratedLike);
        $paidLike = $rows->reject($isGeneratedLike);

        $totalCharged = round((float) $generatedLike->sum(fn ($p) => $netAmount($p)), 2);
        $totalPaid = round((float) $paidLike->sum(fn ($p) => $netAmount($p)), 2);

        // If fee was paid without a separate Generated row (Cash-only), show total = paid so history is not 0/2000/0.
        $effectiveTotal = $totalCharged > 0 ? $totalCharged : $totalPaid;
        $pending = round(max(0.0, $effectiveTotal - $totalPaid), 2);

        return [
            'month' => $monthName,
            'year' => $year,
            'total' => round($effectiveTotal, 2),
            'paid' => $totalPaid,
            'pending' => $pending,
        ];
    }

    /**
     * All student_payments rows for this calendar month (Monthly Fee + Transport Fee), incl. "/1" installments.
     * Also includes same-month Cash rows when payment_date falls in that month (title starts with Monthly/Transport).
     */
    private function paymentsForCalendarMonth(string $normalizedStudentCode, string $monthName, int $year): \Illuminate\Support\Collection
    {
        $prefixes = [
            "Monthly Fee - {$monthName} {$year}",
            "Transport Fee - {$monthName} {$year}",
        ];

        $byTitle = StudentPayment::whereRaw('LOWER(TRIM(student_code)) = ?', [$normalizedStudentCode])
            ->where(function ($q) use ($prefixes) {
                foreach ($prefixes as $p) {
                    $q->orWhereRaw('LOWER(TRIM(payment_title)) LIKE ?', [strtolower($p) . '%']);
                }
            })
            ->get();

        try {
            $monthStart = Carbon::createFromFormat('!F Y', "{$monthName} {$year}")->startOfMonth();
        } catch (\Exception $e) {
            return $byTitle;
        }
        $byDateInMonth = StudentPayment::whereRaw('LOWER(TRIM(student_code)) = ?', [$normalizedStudentCode])
            ->whereYear('payment_date', $year)
            ->whereMonth('payment_date', (int) $monthStart->format('n'))
            ->where(function ($q) use ($year) {
                $q->whereRaw('LOWER(TRIM(payment_title)) LIKE ?', ['monthly fee%'])
                    ->orWhereRaw('LOWER(TRIM(payment_title)) LIKE ?', ['transport fee%']);
            })
            ->whereRaw('LOWER(TRIM(payment_title)) LIKE ?', ['%' . (string) $year . '%'])
            ->get();

        return $byTitle->merge($byDateInMonth)->unique('id')->values();
    }
}
