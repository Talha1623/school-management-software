<?php

namespace App\Http\Controllers;

use App\Models\StudentPayment;
use App\Models\MonthlyFee;
use App\Models\Student;
use App\Models\Campus;
use App\Models\ClassModel;
use App\Models\Section;
use App\Models\AdvanceFee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class StudentPaymentController extends Controller
{
    /**
     * Show the student payment form.
     */
    public function create(Request $request): View
    {
        $studentCode = $request->get('student_code');
        $student = null;
        
        if ($studentCode) {
            $student = Student::where('student_code', $studentCode)->first();
        }

        $campuses = Campus::orderBy('campus_name', 'asc')->pluck('campus_name');
        if ($campuses->isEmpty()) {
            $campusesFromClasses = ClassModel::whereNotNull('campus')->distinct()->pluck('campus');
            $campusesFromSections = Section::whereNotNull('campus')->distinct()->pluck('campus');
            $campuses = $campusesFromClasses->merge($campusesFromSections)->unique()->sort()->values();
        }

        $methods = ['Cash Payment', 'Bank Transfer', 'Cheque', 'Online Payment', 'Card Payment', 'Wallet'];
        
        return view('accounting.direct-payment.student', compact('student', 'studentCode', 'campuses', 'methods'));
    }

    public function getStudentByCode(Request $request)
    {
        $studentCode = $request->get('student_code');
        $campus = $request->get('campus');
        
        if (!$studentCode) {
            return response()->json(['success' => false, 'message' => 'Student code is required']);
        }

        $studentQuery = Student::where('student_code', $studentCode);
        
        // Filter by campus if provided
        if ($campus && trim($campus) !== '') {
            $studentQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
        }
        
        $student = $studentQuery->first();
        
        if (!$student) {
            $message = $campus && trim($campus) !== '' 
                ? 'Student not found with this code in the selected campus'
                : 'Student not found with this code';
            return response()->json([
                'success' => false,
                'message' => $message
            ]);
        }

        // Use the same logic as Fee Payment search results
        // Include both "Generated" and "Installment" methods as unpaid fees
        $generatedFees = StudentPayment::where('student_code', $studentCode)
            ->whereIn('method', ['Generated', 'Installment'])
            ->get();
        
        // Exclude "Installment" method from paid fees - installments are unpaid fees
        $paidFees = StudentPayment::where('student_code', $studentCode)
            ->where('method', '!=', 'Generated')
            ->where('method', '!=', 'Installment')
            ->get();

        // Get StudentDiscount records for this student
        $studentDiscounts = \App\Models\StudentDiscount::where('student_code', $studentCode)
            ->get();
        $totalStudentDiscount = $studentDiscounts->sum(function($discount) {
            return (float) ($discount->discount_amount ?? 0);
        });

        $unpaidGeneratedFees = [];
        $totalDue = 0;
        $generatedByTitle = $generatedFees->groupBy('payment_title');
        $paidByTitle = $paidFees->groupBy('payment_title');

        // Collect all installment titles and their base fee titles
        // If installments exist for a fee, exclude the original fee title
        $installmentBaseTitles = [];
        foreach ($generatedByTitle as $title => $items) {
            if (preg_match('/^(.+)\/\d+$/', $title, $matches)) {
                $baseTitle = $matches[1];
                $installmentBaseTitles[$baseTitle] = true;
            }
        }

        foreach ($generatedByTitle as $title => $items) {
            $latestGenerated = $items->sortByDesc('id')->first();
            // Check if this is an installment (title ends with /number)
            $isInstallment = preg_match('/\/\d+$/', $title);
            
            // Skip original fee title if installments exist for it
            if (!$isInstallment && isset($installmentBaseTitles[$title])) {
                continue;
            }
            
            // Check if this is a monthly fee (title starts with "Monthly Fee - ")
            $isMonthlyFee = str_starts_with($title, 'Monthly Fee - ');
            
            // Calculate original amount (before discount) from generated records
            $originalAmount = $items->sum(function ($item) {
                return (float) ($item->payment_amount ?? 0);
            });
            
            $generatedLate = $items->sum(function ($item) {
                return (float) ($item->late_fee ?? 0);
            });
            
            // For installments, discount is stored in the generated record itself
            // For regular fees, discount comes from payment records
            $generatedDiscount = 0;
            if ($isInstallment) {
                // Get discount from generated records for installments
                $generatedDiscount = $items->sum(function ($item) {
                    return (float) ($item->discount ?? 0);
                });
            }
            
            // Discount from payment records (for regular fees or additional discounts on installments)
            $paidDiscount = $paidByTitle->get($title, collect())->sum(function ($item) {
                return (float) ($item->discount ?? 0);
            });
            
            // Apply StudentDiscount to monthly fees
            // Student discount should NOT be applied to installments - only to full (non-installment) fees
            $appliedStudentDiscount = 0;
            if ($isMonthlyFee && $totalStudentDiscount > 0 && !$isInstallment) {
                // Only apply student discount to regular (non-installment) monthly fees
                $appliedStudentDiscount = round($totalStudentDiscount, 2);
            }
            
            // Total discount = generated discount (for installments) + payment discount + student discount (only for non-installment fees)
            $totalDiscount = $generatedDiscount + $paidDiscount + $appliedStudentDiscount;
            
            // Generated Fee = Original Amount - Total Discount + Late Fee
            $generatedFee = max(0, $originalAmount - $totalDiscount) + $generatedLate;
            
            // Calculate paid amounts
            $paidAmountOnly = $paidByTitle->get($title, collect())->sum(function ($item) {
                return (float) ($item->payment_amount ?? 0); // Only payment amount, not discount
            });
            $paidLate = $paidByTitle->get($title, collect())->sum(function ($item) {
                return (float) ($item->late_fee ?? 0);
            });
            
            // Calculate remaining amounts
            $remainingAmount = max(0, ($originalAmount - $totalDiscount) - $paidAmountOnly);
            $remainingLate = max(0, $generatedLate - $paidLate);
            $remainingTotal = $remainingAmount + $remainingLate;

            // Only include if there's an unpaid balance (same as Fee Payment search)
            if ($remainingTotal > 0) {
                // Return in format expected by dropdown: id, payment_title, payment_amount, late_fee, payment_date, discount
                // Use the latest generated record's ID and date, but calculate the remaining amount
                $unpaidGeneratedFees[] = [
                    'id' => $latestGenerated->id,
                    'payment_title' => $title,
                    'payment_amount' => round($remainingTotal, 2), // Remaining amount to pay
                    'late_fee' => round($remainingLate, 2),
                    'payment_date' => $latestGenerated->payment_date,
                    'discount' => round($totalDiscount, 2),
                ];
                $totalDue += $remainingTotal;
            }
        }

        // Sort by payment_date ascending (oldest first)
        usort($unpaidGeneratedFees, function($a, $b) {
            return strtotime($a['payment_date'] ?? '1970-01-01') - strtotime($b['payment_date'] ?? '1970-01-01');
        });

        // Get latest fee title if available
        $latestFee = !empty($unpaidGeneratedFees) ? $unpaidGeneratedFees[0] : null;
        $feeTitle = $latestFee ? $latestFee['payment_title'] : '';

        return response()->json([
            'success' => true,
            'student' => [
                'student_code' => $student->student_code,
                'student_name' => $student->student_name,
                'campus' => $student->campus,
                'class' => $student->class,
                'section' => $student->section,
                'monthly_fee' => $student->monthly_fee ?? 0,
            ],
            'generated_fees' => $unpaidGeneratedFees,
            'fee_due' => $totalDue,
            'fee_title' => $feeTitle
        ]);
    }

    /**
     * Get students by campus (AJAX).
     */
    public function getStudentsByCampus(Request $request)
    {
        $campus = $request->get('campus');
        
        if (!$campus) {
            return response()->json(['success' => false, 'students' => []]);
        }

        $students = Student::whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))])
            ->whereNotNull('student_code')
            ->where('student_code', '!=', '')
            ->orderBy('student_name', 'asc')
            ->get(['id', 'student_code', 'student_name', 'campus', 'class', 'section', 'monthly_fee']);

        return response()->json([
            'success' => true,
            'students' => $students
        ]);
    }

    /**
     * Store a newly created student payment.
     */
    public function store(Request $request)
    {
        try {
            try {
                $validated = $request->validate([
                    'campus' => ['nullable', 'string', 'max:255'],
                    'student_code' => ['required', 'string', 'max:255'],
                    'payment_title' => ['required', 'string', 'max:255'],
                    'payment_amount' => ['required', 'numeric', 'min:0'],
                    'discount' => ['nullable', 'numeric', 'min:0'],
                    'method' => ['required', 'string', 'max:255'],
                    'payment_date' => ['required', 'date'],
                    'sms_notification' => ['required', 'string', 'in:Yes,No'],
                    'generated_id' => ['nullable', 'integer'],
                ]);
            } catch (\Illuminate\Validation\ValidationException $e) {
                // If request expects JSON, return JSON response with validation errors
                if ($request->ajax() || $request->wantsJson() || $request->header('Accept') === 'application/json' || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                    return response()->json([
                        'success' => false,
                        'message' => 'Validation failed',
                        'errors' => $e->errors()
                    ], 422);
                }
                throw $e;
            }

        // Initialize late_fee
        $lateFee = 0;

        // Get student to find campus, class, section for fee calculations
        $student = Student::where('student_code', $validated['student_code'])->first();

        // Check if this is a monthly fee payment and calculate late fee
        if (preg_match('/Monthly Fee - (\w+) (\d+)/', $validated['payment_title'], $matches) && $student) {
            $feeMonth = $matches[1];
            $feeYear = $matches[2];

            // Find the MonthlyFee record for this month/year and student's class/section
            $monthlyFee = MonthlyFee::where('fee_month', $feeMonth)
                ->where('fee_year', $feeYear)
                ->where(function($query) use ($student) {
                    $query->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($student->campus ?? ''))])
                          ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($student->class ?? ''))])
                          ->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($student->section ?? ''))]);
                })
                ->first();

            if ($monthlyFee && $monthlyFee->late_fee > 0) {
                $paymentDate = Carbon::parse($validated['payment_date']);
                $dueDate = Carbon::parse($monthlyFee->due_date);

                // If payment is made after due date, add late fee
                if ($paymentDate->gt($dueDate)) {
                    $lateFee = (float) $monthlyFee->late_fee;
                }
            }
        }

        // Check if this is an installment (payment_title contains /number pattern)
        $isInstallment = preg_match('/\/\d+$/', $validated['payment_title']);
        
        // Store original method for wallet deduction
        $originalMethod = $validated['method'] ?? 'Cash Payment';
        
        // For installments, create as "Generated" (unpaid) so they show as new/unpaid in search results
        // The payment method will be used when the installment is actually paid
        if ($isInstallment) {
            $validated['method'] = 'Generated'; // Installments should be unpaid (Generated) initially
        }
        
        // Check if there's an existing generated fee record for this student and title
        $existingFee = null;
        
        // For installments, always create new records - don't check for existing fees
        if (!$isInstallment) {
            if (!empty($validated['generated_id'])) {
                $existingFee = StudentPayment::where('id', $validated['generated_id'])
                    ->where('student_code', $validated['student_code'])
                    ->where('method', 'Generated')
                    ->first();

                if ($existingFee && $existingFee->payment_title) {
                    $validated['payment_title'] = $existingFee->payment_title;
                }
            }

            if (!$existingFee) {
                $existingFee = StudentPayment::where('student_code', $validated['student_code'])
                    ->where('payment_title', $validated['payment_title'])
                    ->where('method', 'Generated')
                    ->first();
            }
        }

        if ($existingFee) {
            $totalGenerated = (float) ($existingFee->payment_amount ?? 0)
                - (float) ($existingFee->discount ?? 0)
                + (float) ($existingFee->late_fee ?? 0);
            $totalPaidSoFar = StudentPayment::where('student_code', $validated['student_code'])
                ->where('payment_title', $validated['payment_title'])
                ->where('method', '!=', 'Generated')
                ->sum(\DB::raw('COALESCE(payment_amount,0) + COALESCE(discount,0) + COALESCE(late_fee,0)'));
            $totalPaidNow = (float) ($validated['payment_amount'] ?? 0)
                + (float) ($validated['discount'] ?? 0)
                + (float) $lateFee;

            if ($totalGenerated > 0 && ($totalPaidSoFar + $totalPaidNow) < $totalGenerated) {
                // Partial payment: keep generated fee and add a paid record
                $validated['late_fee'] = $lateFee;
                if (auth()->check()) {
                    $validated['accountant'] = auth()->user()->name ?? null;
                }
                
                // If payment method is "Wallet", deduct from Advance Fee (use original method for wallet check)
                if (strtolower(trim($originalMethod ?? '')) === 'wallet' && $student) {
                    $paymentAmount = (float) ($validated['payment_amount'] ?? 0);
                    $discountAmount = (float) ($validated['discount'] ?? 0);
                    $lateFeeAmount = (float) $lateFee;
                    $totalAmount = $paymentAmount + $lateFeeAmount; // Total to deduct
                    
                    // Find parent's AdvanceFee record
                    $advanceFee = null;
                    if (!empty($student->parent_account_id)) {
                        $advanceFee = AdvanceFee::where('parent_id', (string) $student->parent_account_id)->first();
                    }
                    if (!$advanceFee && !empty($student->father_id_card)) {
                        $advanceFee = AdvanceFee::where('id_card_number', $student->father_id_card)->first();
                    }
                    
                    if ($advanceFee) {
                        $availableCredit = (float) ($advanceFee->available_credit ?? 0);
                        
                        if ($availableCredit < $totalAmount) {
                            $errorMessage = "Insufficient wallet balance. Available: Rs. " . number_format($availableCredit, 2) . ", Required: Rs. " . number_format($totalAmount, 2);
                            
                            if ($request->ajax() || $request->wantsJson() || $request->header('Accept') === 'application/json' || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                                return response()->json([
                                    'success' => false,
                                    'message' => $errorMessage
                                ], 400);
                            }
                            
                            return redirect()
                                ->back()
                                ->withInput()
                                ->withErrors(['method' => $errorMessage]);
                        }
                        
                        // Deduct from advance fee
                        $advanceFee->available_credit = max(0, $availableCredit - $totalAmount);
                        $advanceFee->decrease = (float) ($advanceFee->decrease ?? 0) + $totalAmount;
                        $advanceFee->save();
                    } else {
                        $errorMessage = "No wallet found for this student's parent. Please use a different payment method.";
                        
                        if ($request->ajax() || $request->wantsJson() || $request->header('Accept') === 'application/json' || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                            return response()->json([
                                'success' => false,
                                'message' => $errorMessage
                            ], 400);
                        }
                        
                        return redirect()
                            ->back()
                            ->withInput()
                            ->withErrors(['method' => $errorMessage]);
                    }
                }
                
                $payment = StudentPayment::create($validated);

                $successMessage = 'Payment recorded successfully!';
                if ($lateFee > 0) {
                    $successMessage .= " Late fee of " . number_format($lateFee, 2) . " has been added.";
                }

                if ($request->ajax() || $request->wantsJson() || $request->header('Accept') === 'application/json' || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                    return response()->json([
                        'success' => true,
                        'message' => $successMessage,
                        'payment' => [
                            'id' => $payment->id,
                            'student_code' => $payment->student_code,
                            'student_name' => $student->student_name ?? null,
                            'father_name' => $student->father_name ?? null,
                            'class' => $student->class ?? null,
                            'section' => $student->section ?? null,
                            'payment_title' => $payment->payment_title,
                            'payment_amount' => (float) ($payment->payment_amount ?? 0),
                            'discount' => (float) ($payment->discount ?? 0),
                            'late_fee' => (float) ($payment->late_fee ?? 0),
                            'payment_date' => $payment->payment_date ? Carbon::parse($payment->payment_date)->format('d-m-Y h:i:s A') : null,
                            'accountant' => $payment->accountant ?? null,
                        ],
                    ]);
                }

                return redirect()
                    ->route('accounting.direct-payment.student')
                    ->with('success', $successMessage);
            }

            // If payment method is "Wallet", deduct from Advance Fee
            if (strtolower(trim($validated['method'] ?? '')) === 'wallet' && $student) {
                $paymentAmount = (float) ($validated['payment_amount'] ?? 0);
                $discountAmount = (float) ($validated['discount'] ?? 0);
                $lateFeeAmount = (float) $lateFee;
                $totalAmount = $paymentAmount + $lateFeeAmount; // Total to deduct
                
                // Find parent's AdvanceFee record
                $advanceFee = null;
                if (!empty($student->parent_account_id)) {
                    $advanceFee = AdvanceFee::where('parent_id', (string) $student->parent_account_id)->first();
                }
                if (!$advanceFee && !empty($student->father_id_card)) {
                    $advanceFee = AdvanceFee::where('id_card_number', $student->father_id_card)->first();
                }
                
                if ($advanceFee) {
                    $availableCredit = (float) ($advanceFee->available_credit ?? 0);
                    
                    if ($availableCredit < $totalAmount) {
                        $errorMessage = "Insufficient wallet balance. Available: Rs. " . number_format($availableCredit, 2) . ", Required: Rs. " . number_format($totalAmount, 2);
                        
                        if ($request->ajax() || $request->wantsJson() || $request->header('Accept') === 'application/json' || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                            return response()->json([
                                'success' => false,
                                'message' => $errorMessage
                            ], 400);
                        }
                        
                        return redirect()
                            ->back()
                            ->withInput()
                            ->withErrors(['method' => $errorMessage]);
                    }
                    
                    // Deduct from advance fee
                    $advanceFee->available_credit = max(0, $availableCredit - $totalAmount);
                    $advanceFee->decrease = (float) ($advanceFee->decrease ?? 0) + $totalAmount;
                    $advanceFee->save();
                } else {
                    $errorMessage = "No wallet found for this student's parent. Please use a different payment method.";
                    
                    if ($request->ajax() || $request->wantsJson() || $request->header('Accept') === 'application/json' || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                        return response()->json([
                            'success' => false,
                            'message' => $errorMessage
                        ], 400);
                    }
                    
                    return redirect()
                        ->back()
                        ->withInput()
                        ->withErrors(['method' => $errorMessage]);
                }
            }
            
            // Update the existing generated fee record with actual payment details
            $existingFee->update([
                'payment_amount' => $validated['payment_amount'],
                'discount' => $validated['discount'] ?? 0,
                'method' => $validated['method'],
                'payment_date' => $validated['payment_date'],
                'sms_notification' => $validated['sms_notification'],
                'late_fee' => $lateFee,
                'accountant' => auth()->check() ? (auth()->user()->name ?? null) : null,
            ]);

            $successMessage = 'Payment recorded successfully!';
            if ($lateFee > 0) {
                $successMessage .= " Late fee of " . number_format($lateFee, 2) . " has been added.";
            }

            $payment = $existingFee->fresh();

            if ($request->ajax() || $request->wantsJson() || $request->header('Accept') === 'application/json' || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json([
                    'success' => true,
                    'message' => $successMessage,
                    'payment' => [
                        'id' => $payment->id,
                        'student_code' => $payment->student_code,
                        'student_name' => $student->student_name ?? null,
                        'father_name' => $student->father_name ?? null,
                        'class' => $student->class ?? null,
                        'section' => $student->section ?? null,
                        'payment_title' => $payment->payment_title,
                        'payment_amount' => (float) ($payment->payment_amount ?? 0),
                        'discount' => (float) ($payment->discount ?? 0),
                        'late_fee' => (float) ($payment->late_fee ?? 0),
                        'payment_date' => $payment->payment_date ? Carbon::parse($payment->payment_date)->format('d-m-Y h:i:s A') : null,
                        'accountant' => $payment->accountant ?? null,
                    ],
                ]);
            }

            return redirect()
                ->route('accounting.direct-payment.student')
                ->with('success', $successMessage);
        }
        
        // Add late_fee to validated data
        $validated['late_fee'] = $lateFee;
        
        // Add accountant if available
        if (auth()->check()) {
            $validated['accountant'] = auth()->user()->name ?? null;
        }

        // If payment method is "Wallet", deduct from Advance Fee
        // Skip wallet deduction for installments (they are unpaid fees, not actual payments)
        if (!$isInstallment && strtolower(trim($originalMethod ?? '')) === 'wallet' && $student) {
            $paymentAmount = (float) ($validated['payment_amount'] ?? 0);
            $discountAmount = (float) ($validated['discount'] ?? 0);
            $lateFeeAmount = (float) $lateFee;
            $totalAmount = $paymentAmount + $lateFeeAmount; // Total to deduct (discount is already subtracted from payment_amount)
            
            // Find parent's AdvanceFee record
            $advanceFee = null;
            if (!empty($student->parent_account_id)) {
                $advanceFee = AdvanceFee::where('parent_id', (string) $student->parent_account_id)->first();
            }
            if (!$advanceFee && !empty($student->father_id_card)) {
                $advanceFee = AdvanceFee::where('id_card_number', $student->father_id_card)->first();
            }
            
            if ($advanceFee) {
                $availableCredit = (float) ($advanceFee->available_credit ?? 0);
                
                if ($availableCredit < $totalAmount) {
                    $errorMessage = "Insufficient wallet balance. Available: Rs. " . number_format($availableCredit, 2) . ", Required: Rs. " . number_format($totalAmount, 2);
                    
                    if ($request->ajax() || $request->wantsJson() || $request->header('Accept') === 'application/json' || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                        return response()->json([
                            'success' => false,
                            'message' => $errorMessage
                        ], 400);
                    }
                    
                    return redirect()
                        ->back()
                        ->withInput()
                        ->withErrors(['method' => $errorMessage]);
                }
                
                // Deduct from advance fee
                $advanceFee->available_credit = max(0, $availableCredit - $totalAmount);
                $advanceFee->decrease = (float) ($advanceFee->decrease ?? 0) + $totalAmount;
                $advanceFee->save();
            } else {
                $errorMessage = "No wallet found for this student's parent. Please use a different payment method.";
                
                if ($request->ajax() || $request->wantsJson() || $request->header('Accept') === 'application/json' || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                    return response()->json([
                        'success' => false,
                        'message' => $errorMessage
                    ], 400);
                }
                
                return redirect()
                    ->back()
                    ->withInput()
                    ->withErrors(['method' => $errorMessage]);
            }
        }

        try {
            $payment = StudentPayment::create($validated);
        } catch (\Exception $e) {
            // If request expects JSON, return JSON response with error
            if ($request->ajax() || $request->wantsJson() || $request->header('Accept') === 'application/json' || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json([
                    'success' => false,
                    'message' => 'Error creating payment record: ' . $e->getMessage()
                ], 500);
            }
            throw $e;
        }

        $successMessage = 'Payment recorded successfully!';
        if ($lateFee > 0) {
            $successMessage .= " Late fee of " . number_format($lateFee, 2) . " has been added.";
        }

        // If request is AJAX or expects JSON, return JSON response
        if ($request->ajax() || $request->wantsJson() || $request->header('Accept') === 'application/json' || $request->header('X-Requested-With') === 'XMLHttpRequest') {
            return response()->json([
                'success' => true,
                'message' => $successMessage,
            'payment' => [
                'id' => $payment->id,
                'student_code' => $payment->student_code,
                'student_name' => $student->student_name ?? null,
                'father_name' => $student->father_name ?? null,
                'class' => $student->class ?? null,
                'section' => $student->section ?? null,
                'payment_title' => $payment->payment_title,
                'payment_amount' => (float) ($payment->payment_amount ?? 0),
                'discount' => (float) ($payment->discount ?? 0),
                'late_fee' => (float) ($payment->late_fee ?? 0),
                'payment_date' => $payment->payment_date ? Carbon::parse($payment->payment_date)->format('d-m-Y h:i:s A') : null,
                'accountant' => $payment->accountant ?? null,
            ],
            ]);
        }

        return redirect()
            ->route('accounting.direct-payment.student')
            ->with('success', $successMessage);
        } catch (\Exception $e) {
            // Catch any unexpected exceptions and return JSON response
            if ($request->ajax() || $request->wantsJson() || $request->header('Accept') === 'application/json' || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                \Log::error('Error in StudentPaymentController@store: ' . $e->getMessage(), [
                    'exception' => $e,
                    'request_data' => $request->all()
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Error creating payment: ' . $e->getMessage()
                ], 500);
            }
            throw $e;
        }
    }
}

