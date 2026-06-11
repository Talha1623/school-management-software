<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\ClassModel;
use App\Models\Section;
use App\Models\StudentPayment;
use App\Models\MonthlyFee;
use App\Models\StudentDiscount;
use App\Models\GeneralSetting;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class StudentVoucherController extends Controller
{
    private function availableWalletCreditForStudent(Student $student): float
    {
        $advanceFee = null;

        if (!empty($student->parent_account_id)) {
            $advanceFee = \App\Models\AdvanceFee::where('parent_id', (string) $student->parent_account_id)->first();
        }

        if (!$advanceFee && !empty($student->father_id_card)) {
            $advanceFee = \App\Models\AdvanceFee::where('id_card_number', $student->father_id_card)->first();
        }

        return $advanceFee ? max(0, round((float) ($advanceFee->available_credit ?? 0), 2)) : 0.0;
    }

    /**
     * Show the student vouchers page with filters.
     */
    public function index(Request $request): View
    {
        // Get campuses
        $campuses = \App\Models\Campus::orderBy('campus_name', 'asc')->get();
        if ($campuses->isEmpty()) {
            $campusesFromClasses = ClassModel::whereNotNull('campus')
                ->distinct()
                ->pluck('campus')
                ->sort()
                ->values();
            $campusesFromSections = Section::whereNotNull('campus')
                ->distinct()
                ->pluck('campus')
                ->sort()
                ->values();
            $allCampuses = $campusesFromClasses->merge($campusesFromSections)->unique()->sort()->values();
            $campuses = collect();
            foreach ($allCampuses as $campusName) {
                $campuses->push((object)['campus_name' => $campusName]);
            }
        }

        $filterCampus = $request->get('campus');

        // Get classes (campus-wise)
        $classes = collect();
        if (!empty($filterCampus)) {
            $classes = ClassModel::whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($filterCampus))])
                ->orderBy('class_name', 'asc')
                ->get();
        }
        
        // Get sections (will be filtered by class via AJAX)
        $sections = collect();
        if ($request->filled('class')) {
            $sectionsQuery = Section::whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($request->class))]);
            if (!empty($filterCampus)) {
                $sectionsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($filterCampus))]);
            }
            $sections = $sectionsQuery
                ->orderBy('name', 'asc')
                ->get();
        }
        
        $query = Student::query();
        $settings = GeneralSetting::getSettings();
        $currentYear = (int) date('Y');
        $runningSession = trim((string) ($settings->running_session ?? ''));
        if ($runningSession !== '') {
            preg_match_all('/\d{4}/', $runningSession, $matches);
            if (!empty($matches[0])) {
                // Use the latest 4-digit year from running session for voucher calculations.
                $currentYear = (int) end($matches[0]);
            }
        }
        $displayYear = $runningSession !== '' ? $runningSession : (string) $currentYear;
        $vouchersFor = $request->get('vouchers_for');
        $pendingPaymentsQuery = StudentPayment::where('method', 'Generated')
            ->whereNotNull('student_code')
            ->where('student_code', '!=', '');
        if ($request->filled('student_code')) {
            $pendingPaymentsQuery->where('student_code', $request->student_code);
        }
        if ($vouchersFor) {
            $monthlyTitle = "Monthly Fee - {$vouchersFor} {$currentYear}";
            $transportTitle = "Transport Fee - {$vouchersFor} {$currentYear}";
            $pendingPaymentsQuery->where(function ($query) use ($monthlyTitle, $transportTitle) {
                $query->where('payment_title', $monthlyTitle)
                    ->orWhere('payment_title', $transportTitle)
                    // Always include custom/admission/other fees
                    ->orWhere(function ($subQuery) {
                        $subQuery->where('payment_title', 'not like', 'Monthly Fee - %')
                            ->where('payment_title', 'not like', 'Transport Fee - %');
                    });
            });
        }
        $pendingStudentCodes = $pendingPaymentsQuery->distinct()->pluck('student_code');
        if ($request->filled('student_code')) {
            $query->where('student_code', $request->student_code);
        } elseif ($pendingStudentCodes->isNotEmpty()) {
            $query->whereIn('student_code', $pendingStudentCodes);
        } else {
            $query->whereRaw('1 = 0');
        }
        
        // Apply filters
        if (!empty($filterCampus)) {
            $query->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($filterCampus))]);
        }

        if ($request->filled('class')) {
            $query->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($request->class))]);
        }
        
        if ($request->filled('section')) {
            $query->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($request->section))]);
        }
        
        // Type and vouchers_for are filter options, not stored in Student model
        // They will be used for voucher generation
        
        $students = $query->orderBy('student_name')->paginate(20)->withQueryString();
        
        return view('accounting.fee-voucher.student', compact('students', 'classes', 'sections', 'campuses', 'filterCampus'));
    }
    
    /**
     * Get sections by class name (AJAX).
     */
    public function getSectionsByClass(Request $request): JsonResponse
    {
        $className = $request->get('class');
        $campus = $request->get('campus');
        
        if (!$className) {
            return response()->json(['sections' => []]);
        }

        // Get sections for the selected class
        $sectionsQuery = Section::whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($className))]);
        if ($campus) {
            $sectionsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
        }
        $sections = $sectionsQuery->orderBy('name', 'asc')
            ->get(['id', 'name'])
            ->map(function($section) {
                return [
                    'id' => $section->id,
                    'name' => $section->name
                ];
            });

        return response()->json(['sections' => $sections]);
    }
    
    /**
     * Print vouchers for filtered students.
     */
    public function print(Request $request): View
    {
        $query = Student::query();
        $parentStudentCodes = collect();
        if ($request->filled('parent_id')) {
            $parentStudentCodes = Student::where('parent_account_id', $request->parent_id)
                ->whereNotNull('student_code')
                ->where('student_code', '!=', '')
                ->pluck('student_code');
        }
        
        // Apply filters
        if ($request->filled('campus')) {
            $query->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($request->campus))]);
        }

        if ($request->filled('class')) {
            $query->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($request->class))]);
        }
        
        if ($request->filled('section')) {
            $query->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($request->section))]);
        }
        
        $settings = GeneralSetting::getSettings();
        $currentYear = (int) date('Y');
        $runningSession = trim((string) ($settings->running_session ?? ''));
        if ($runningSession !== '') {
            preg_match_all('/\d{4}/', $runningSession, $matches);
            if (!empty($matches[0])) {
                $currentYear = (int) end($matches[0]);
            }
        }
        $displayYear = $runningSession !== '' ? $runningSession : (string) $currentYear;
        $vouchersFor = $request->get('vouchers_for', date('F'));
        $pendingPaymentsQuery = StudentPayment::where('method', 'Generated')
            ->whereNotNull('student_code')
            ->where('student_code', '!=', '');
        if ($request->filled('parent_id') && $parentStudentCodes->isNotEmpty()) {
            $pendingPaymentsQuery->whereIn('student_code', $parentStudentCodes);
        }
        if ($request->filled('student_code')) {
            $pendingPaymentsQuery->where('student_code', $request->student_code);
        }
        if ($vouchersFor) {
            $monthlyTitle = "Monthly Fee - {$vouchersFor} {$currentYear}";
            $transportTitle = "Transport Fee - {$vouchersFor} {$currentYear}";
            $pendingPaymentsQuery->where(function ($query) use ($monthlyTitle, $transportTitle) {
                $query->where('payment_title', $monthlyTitle)
                    ->orWhere('payment_title', $transportTitle)
                    // Always include custom/admission/other fees
                    ->orWhere(function ($subQuery) {
                        $subQuery->where('payment_title', 'not like', 'Monthly Fee - %')
                            ->where('payment_title', 'not like', 'Transport Fee - %');
                    });
            });
        }
        $pendingStudentCodes = $pendingPaymentsQuery->distinct()->pluck('student_code');
        if ($request->filled('parent_id')) {
            if ($parentStudentCodes->isNotEmpty()) {
                $query->whereIn('student_code', $parentStudentCodes);
            } else {
                $query->whereRaw('1 = 0');
            }
        } elseif ($request->filled('student_code')) {
            $query->where('student_code', $request->student_code);
        } elseif ($pendingStudentCodes->isNotEmpty()) {
            $query->whereIn('student_code', $pendingStudentCodes);
        } else {
            $query->whereRaw('1 = 0');
        }

        $students = $query->orderBy('student_name')->get();
        
        $type = $request->get('type', 'three_copies');
        $vouchersFor = $request->get('vouchers_for', date('F')); // Month name

        $copyMap = [
            'three_copies' => ['Bank Copy', 'Parent Copy', 'School Copy'],
            'two_copies' => ['Bank Copy', 'Parent Copy'],
            'thermal_copies' => ['THERMAL COPY'],
        ];
        $copyLabels = $copyMap[$type] ?? $copyMap['three_copies'];
        
        // Get fee data for each student
        $vouchers = [];
        foreach ($students as $student) {
            $totalStudentDiscount = StudentDiscount::where('student_code', $student->student_code)
                ->get()
                ->sum(function ($discount) {
                    return (float) ($discount->discount_amount ?? 0);
                });

            // Get all pending fees (unpaid fees) for this student
            // Pending fees are those where method = 'Generated' (not yet paid)
            $pendingPayments = StudentPayment::where('student_code', $student->student_code)
                ->where('method', 'Generated')
                ->orderBy('payment_date', 'asc')
                ->get();
            
            // Get fee history for current year
            $feeHistory = [];
            $months = ['January', 'February', 'March', 'April', 'May', 'June', 
                      'July', 'August', 'September', 'October', 'November', 'December'];
            
            foreach ($months as $month) {
                $paymentTitle = "Monthly Fee - {$month} {$currentYear}";
                $payment = StudentPayment::where('student_code', $student->student_code)
                    ->where('payment_title', $paymentTitle)
                    ->first();
                
                $feeHistory[$month] = [
                    'total' => $payment ? (float) $payment->payment_amount : 0,
                    'paid' => $payment && $payment->method !== 'Generated' ? (float) $payment->payment_amount : 0,
                ];
            }
            
            // Line-item amounts below use StudentPayment::remainingDueForTitle() (includes remaining late per fee).
            // Do not also sum late_fee from raw Generated rows or it double-counts vs those line amounts.
            $lateFee = 0;

            // Add late fee dynamically for overdue monthly fees if not already applied on the generated row
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
            
            // Get the latest due date from pending payments or use default
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
                // Get MonthlyFee record for the selected month to get due_date
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
            $formatPeriodicFeeDescription = function ($title) use ($student) {
                if (preg_match('/Monthly Fee - (\w+) (\d+)/', (string) $title, $matches)) {
                    return "Monthly Fee Of {$matches[1]} ({$matches[2]})";
                }

                if (preg_match('/Transport Fee - (\w+) (\d+)/', (string) $title, $matches)) {
                    $routeLabel = !empty($student->transport_route)
                        ? "Transport Route ({$student->transport_route})"
                        : 'Transport Route';

                    return "{$routeLabel} - {$matches[1]} ({$matches[2]})";
                }

                return (string) $title;
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
                // Arrears = fees that are past due date (more than 0 days overdue)
                return $dueDate->lt($today);
            });
            
            $arrearsAmount = 0;
            $arrearsFees = collect();
            $arrearsTitlesSeen = [];
            foreach ($arrearsPayments as $payment) {
                $feeTitle = (string) ($payment->payment_title ?? '');
                $titleKey = strtolower(trim($feeTitle));
                if ($titleKey === '' || isset($arrearsTitlesSeen[$titleKey])) {
                    continue;
                }
                $arrearsTitlesSeen[$titleKey] = true;
                $rem = StudentPayment::remainingDueForTitle($student->student_code, $feeTitle, $totalStudentDiscount);
                if ($rem > 0.0001) {
                    $arrearsAmount += $rem;
                    $arrearsFees->push([
                        'description' => $formatPeriodicFeeDescription($feeTitle),
                        'amount' => $rem,
                        'sort_order' => 0,
                    ]);
                }
            }
            
            // Current fees (not overdue yet) + always include custom/admission fees
            $currentFeesPayments = $pendingPayments->filter(function($payment) use ($today, $isMonthlyOrTransport) {
                if (!$isMonthlyOrTransport($payment->payment_title ?? '')) {
                    return true;
                }
                if (!$payment->payment_date) {
                    return true; // Include fees without due date as current
                }
                $dueDate = Carbon::parse($payment->payment_date);
                // Current = fees that are not yet overdue (due date is today or future)
                return $dueDate->gte($today);
            });
            
            // Format pending fees for display
            // Separate monthly fees and custom fees, then sort them
            $monthlyFees = collect();
            $customFees = collect();
            
            foreach ($currentFeesPayments as $payment) {
                $feeTitle = (string) ($payment->payment_title ?? '');
                $remaining = StudentPayment::remainingDueForTitle($student->student_code, $feeTitle, $totalStudentDiscount);
                if ($remaining <= 0.0001) {
                    continue;
                }

                // Check if it's a monthly fee
                if (preg_match('/Monthly Fee - (\w+) (\d+)/', $payment->payment_title, $matches)) {
                    $monthlyFees->push([
                        'description' => $formatPeriodicFeeDescription($feeTitle),
                        'amount' => $remaining,
                        'sort_order' => 1, // Monthly fees first
                    ]);
                } elseif (preg_match('/Transport Fee - (\w+) (\d+)/', $payment->payment_title, $matches)) {
                    $monthlyFees->push([
                        'description' => $formatPeriodicFeeDescription($feeTitle),
                        'amount' => $remaining,
                        'sort_order' => 1, // Keep transport with monthly fees
                    ]);
                }
            }

            // One-time / custom fees: net remaining per title (e.g. paid Admission Fee no longer appears on next month's voucher)
            $customFeePayments = $pendingPayments->filter(function ($payment) use ($isMonthlyOrTransport) {
                return !$isMonthlyOrTransport($payment->payment_title ?? '');
            });
            $customTitlesSeen = [];
            foreach ($customFeePayments as $payment) {
                $feeTitle = (string) ($payment->payment_title ?? '');
                $titleKey = strtolower(trim($feeTitle));
                if ($titleKey === '' || isset($customTitlesSeen[$titleKey])) {
                    continue;
                }
                $customTitlesSeen[$titleKey] = true;

                $remaining = StudentPayment::remainingDueForTitle($student->student_code, $feeTitle, $totalStudentDiscount);
                if ($remaining <= 0.0001) {
                    continue;
                }

                if (strtolower(trim($feeTitle)) === 'admission fee') {
                    $customFees->push([
                        'description' => 'Generate Admission Fee',
                        'amount' => $remaining,
                        'sort_order' => 2,
                    ]);
                } else {
                    // Custom fee - show fee type name directly as description
                    $customFees->push([
                        'description' => $feeTitle !== '' ? $feeTitle : 'Custom Fee',
                        'amount' => $remaining,
                        'sort_order' => 2, // Custom fees after monthly fees
                    ]);
                }
            }
            
            // Combine and sort: arrears/monthly fees first, then custom fees.
            $pendingFeesList = $arrearsFees->merge($monthlyFees)->merge($customFees)->sortBy('sort_order')->map(function($fee) {
                return [
                    'description' => $fee['description'],
                    'amount' => $fee['amount'],
                ];
            })->values();

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
            $currentFeesSubtotal = $pendingFeesList->sum('amount') - $arrearsAmount;
            $subtotal = max(0, $currentFeesSubtotal + $arrearsAmount);
            $totalBeforeWallet = $subtotal + $lateFee;
            $walletCredit = $this->availableWalletCreditForStudent($student);
            $walletApplied = min($walletCredit, $totalBeforeWallet);
            if ($walletApplied > 0) {
                $pendingFeesList->push([
                    'description' => 'Advance Fee / Wallet Credit',
                    'amount' => -$walletApplied,
                ]);
            }
            $total = max(0, round($totalBeforeWallet - $walletApplied, 2));
            
            $vouchers[] = [
                'student' => $student,
                'pending_fees' => $pendingFeesList,
                'current_fees_subtotal' => $currentFeesSubtotal,
                'arrears_amount' => $arrearsAmount,
                'subtotal' => $subtotal, // Total of all pending fees (current + arrears)
                'late_fee' => $lateFee,
                'wallet_credit' => $walletCredit,
                'wallet_applied' => $walletApplied,
                'total' => $total,
                'after_due_date' => $total,
                'due_date' => $dueDate,
                'voucher_validity' => $voucherValidity,
                'voucher_number' => $voucherNumber,
                'fee_history' => $feeHistory,
                'month' => $vouchersFor,
                'year' => $currentYear,
                'year_label' => $displayYear,
            ];
        }

        return view('accounting.fee-voucher.print', compact('vouchers', 'type', 'vouchersFor', 'currentYear', 'copyLabels', 'settings'));
    }
}

