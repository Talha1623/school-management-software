<?php

namespace App\Http\Controllers;

use App\Models\StudentPayment;
use App\Models\MonthlyFee;
use App\Models\Student;
use App\Models\Campus;
use App\Models\ClassModel;
use App\Models\Section;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Carbon\Carbon;

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

        $methods = ['Cash Payment', 'Bank Transfer', 'Cheque', 'Online Payment', 'Card Payment'];
        
        return view('accounting.direct-payment.student', compact('student', 'studentCode', 'campuses', 'methods'));
    }

    public function getStudentByCode(Request $request)
    {
        $studentCode = $request->get('student_code');
        
        if (!$studentCode) {
            return response()->json(['success' => false, 'message' => 'Student code is required']);
        }

        $student = Student::where('student_code', $studentCode)->first();
        
        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'Student not found with this code'
            ]);
        }

        $generatedFees = StudentPayment::where('student_code', $studentCode)
            ->where('method', 'Generated')
            ->orderBy('payment_date', 'asc')
            ->get(['id', 'payment_title', 'payment_amount', 'late_fee', 'payment_date']);

        return response()->json([
            'success' => true,
            'student' => [
                'student_code' => $student->student_code,
                'student_name' => $student->student_name,
                'campus' => $student->campus,
                'class' => $student->class,
                'section' => $student->section,
            ],
            'generated_fees' => $generatedFees
        ]);
    }

    /**
     * Store a newly created student payment.
     */
    public function store(Request $request)
    {
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
            if ($request->ajax() || $request->wantsJson() || $request->header('Accept') === 'application/json') {
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

        // Check if there's an existing generated fee record for this student and title
        $existingFee = null;
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

        if ($existingFee) {
            $totalGenerated = (float) ($existingFee->payment_amount ?? 0)
                - (float) ($existingFee->discount ?? 0)
                + (float) ($existingFee->late_fee ?? 0);
            $totalPaidSoFar = StudentPayment::where('student_code', $validated['student_code'])
                ->where('payment_title', $validated['payment_title'])
                ->where('method', '!=', 'Generated')
                ->sum(\DB::raw('COALESCE(payment_amount,0) - COALESCE(discount,0) + COALESCE(late_fee,0)'));
            $totalPaidNow = (float) ($validated['payment_amount'] ?? 0)
                - (float) ($validated['discount'] ?? 0)
                + (float) $lateFee;

            if ($totalGenerated > 0 && ($totalPaidSoFar + $totalPaidNow) < $totalGenerated) {
                // Partial payment: keep generated fee and add a paid record
                $validated['late_fee'] = $lateFee;
                if (auth()->check()) {
                    $validated['accountant'] = auth()->user()->name ?? null;
                }
                $payment = StudentPayment::create($validated);

                $successMessage = 'Payment recorded successfully!';
                if ($lateFee > 0) {
                    $successMessage .= " Late fee of " . number_format($lateFee, 2) . " has been added.";
                }

                if ($request->ajax() || $request->wantsJson() || $request->header('Accept') === 'application/json') {
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

            if ($request->ajax() || $request->wantsJson() || $request->header('Accept') === 'application/json') {
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

        try {
            $payment = StudentPayment::create($validated);
        } catch (\Exception $e) {
            // If request expects JSON, return JSON response with error
            if ($request->ajax() || $request->wantsJson() || $request->header('Accept') === 'application/json') {
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
        if ($request->ajax() || $request->wantsJson() || $request->header('Accept') === 'application/json') {
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
}

