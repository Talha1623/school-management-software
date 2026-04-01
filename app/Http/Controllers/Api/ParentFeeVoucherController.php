<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ParentAccount;
use App\Models\Student;
use App\Models\StudentPayment;
use App\Models\MonthlyFee;
use App\Models\StudentDiscount;
use App\Models\GeneralSetting;
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

            $currentYear = date('Y');
            $vouchersFor = $request->get('vouchers_for', date('F')); // Month name

            // Get fee vouchers for each student
            $vouchers = [];
            foreach ($students as $student) {
                $voucher = $this->generateVoucherForStudent($student, $vouchersFor, $currentYear);
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

            $currentYear = date('Y');
            $vouchersFor = $request->get('vouchers_for', date('F'));

            $voucher = $this->generateVoucherForStudent($student, $vouchersFor, $currentYear);

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

            $currentYear = date('Y');
            $vouchersFor = $request->get('vouchers_for', date('F'));
            $voucher = $this->generateVoucherForStudent($student, $vouchersFor, (int) $currentYear);

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
            $copyLabels = ['PARENT COPY', 'SCHOOL COPY', 'STUDENT COPY'];

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

            $html = view('accounting.fee-voucher.print', [
                'vouchers' => [$voucherForPrint],
                'type' => $type,
                'vouchersFor' => $vouchersFor,
                'currentYear' => (int) $currentYear,
                'copyLabels' => $copyLabels,
                'settings' => $settings,
            ])->render();

            // WebView-friendly printable document (same as web print layout)
            return response($html, 200)->header('Content-Type', 'text/html; charset=UTF-8');
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
     * @return array|null
     */
    private function generateVoucherForStudent(Student $student, string $vouchersFor, int $currentYear): ?array
    {
        // Get all pending fees (unpaid fees) for this student
        $pendingPayments = StudentPayment::where('student_code', $student->student_code)
            ->where('method', 'Generated')
            ->orderBy('payment_date', 'asc')
            ->get();

        if ($pendingPayments->isEmpty()) {
            return null; // No pending fees
        }

        // Filter by vouchers_for month if provided
        if ($vouchersFor) {
            $monthlyTitle = "Monthly Fee - {$vouchersFor} {$currentYear}";
            $transportTitle = "Transport Fee - {$vouchersFor} {$currentYear}";
            $pendingPayments = $pendingPayments->filter(function($payment) use ($monthlyTitle, $transportTitle) {
                return $payment->payment_title === $monthlyTitle
                    || $payment->payment_title === $transportTitle
                    || (!str_contains($payment->payment_title ?? '', 'Monthly Fee -')
                        && !str_contains($payment->payment_title ?? '', 'Transport Fee -'));
            });
        }

        if ($pendingPayments->isEmpty()) {
            return null;
        }

        // Get fee history for current year
        $feeHistory = [];
        $months = ['January', 'February', 'March', 'April', 'May', 'June', 
                  'July', 'August', 'September', 'October', 'November', 'December'];
        
        foreach ($months as $month) {
            $paymentTitle = "Monthly Fee - {$month} {$currentYear}";
            $payment = StudentPayment::where('student_code', $student->student_code)
                ->where('payment_title', $paymentTitle)
                ->first();
            
            $feeHistory[] = [
                'month' => $month,
                'total' => $payment ? (float) $payment->payment_amount : 0,
                'paid' => $payment && $payment->method !== 'Generated' ? (float) $payment->payment_amount : 0,
                'pending' => $payment && $payment->method === 'Generated' ? (float) $payment->payment_amount : 0,
            ];
        }
        
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
        
        // Format pending fees for display
        $monthlyFees = collect();
        $customFees = collect();
        
        foreach ($currentFeesPayments as $payment) {
            $amount = (float) ($payment->payment_amount ?? 0) - (float) ($payment->discount ?? 0);

            if (preg_match('/Monthly Fee - (\w+) (\d+)/', $payment->payment_title, $matches)) {
                $month = $matches[1];
                $year = $matches[2];
                $description = "Monthly Fee Of {$month} ({$year})";
                $monthlyFees->push([
                    'description' => $description,
                    'amount' => $amount,
                    'sort_order' => 1,
                ]);
            } elseif (preg_match('/Transport Fee - (\w+) (\d+)/', $payment->payment_title, $matches)) {
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
            $amount = (float) ($payment->payment_amount ?? 0) - (float) ($payment->discount ?? 0);
            $title = (string) ($payment->payment_title ?? '');

            if (preg_match('/Monthly Fee - (\w+) (\d+)/', $title, $matches)) {
                $month = $matches[1];
                $year = $matches[2];
                $monthlyFees->push([
                    'description' => "Arrears - Monthly Fee Of {$month} ({$year})",
                    'amount' => $amount,
                    'sort_order' => 0,
                ]);
            } elseif (preg_match('/Transport Fee - (\w+) (\d+)/', $title, $matches)) {
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
            $amount = (float) ($payment->payment_amount ?? 0) - (float) ($payment->discount ?? 0);
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
            ];
        })->values();

        // Add discounts
        $discounts = StudentDiscount::where('student_code', $student->student_code)->get();
        if ($discounts->isNotEmpty()) {
            foreach ($discounts as $discount) {
                $discountAmount = (float) ($discount->discount_amount ?? 0);
                if ($discountAmount <= 0) {
                    continue;
                }
                $title = trim((string) ($discount->discount_title ?? ''));
                $label = $title !== '' ? "Discount - {$title}" : 'Discount';
                $pendingFeesList->push([
                    'description' => $label,
                    'amount' => -abs($discountAmount),
                ]);
            }
        }
        
        // Calculate totals
        $currentFeesSubtotal = $pendingFeesList->sum('amount');
        $subtotal = max(0, $currentFeesSubtotal + $arrearsAmount);
        $total = $subtotal + $lateFee;
        
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
            'pending_fees' => $pendingFeesList,
            'current_fees_subtotal' => round($currentFeesSubtotal, 2),
            'arrears_amount' => round($arrearsAmount, 2),
            'subtotal' => round($subtotal, 2),
            'late_fee' => round($lateFee, 2),
            'total' => round($total, 2),
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
}
