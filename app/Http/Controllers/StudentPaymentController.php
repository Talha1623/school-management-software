<?php

namespace App\Http\Controllers;

use App\Models\StudentPayment;
use App\Models\MonthlyFee;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Carbon\Carbon;

class StudentPaymentController extends Controller
{
    /**
     * Show the student payment form.
     */
    public function create(): View
    {
        return view('accounting.direct-payment.student');
    }

    /**
     * Store a newly created student payment.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'campus' => ['nullable', 'string', 'max:255'],
            'student_code' => ['required', 'string', 'max:255'],
            'payment_title' => ['required', 'string', 'max:255'],
            'payment_amount' => ['required', 'numeric', 'min:0'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'method' => ['required', 'string', 'max:255'],
            'payment_date' => ['required', 'date'],
            'sms_notification' => ['required', 'string', 'in:Yes,No'],
        ]);

        // Initialize late_fee
        $lateFee = 0;
        
        // Check if this is a monthly fee payment and calculate late fee
        if (preg_match('/Monthly Fee - (\w+) (\d+)/', $validated['payment_title'], $matches)) {
            $feeMonth = $matches[1];
            $feeYear = $matches[2];
            
            // Get student to find campus, class, section
            $student = Student::where('student_code', $validated['student_code'])->first();
            
            if ($student) {
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
                
                // Check if there's an existing generated fee record for this student and month/year
                $existingFee = StudentPayment::where('student_code', $validated['student_code'])
                    ->where('payment_title', $validated['payment_title'])
                    ->where('method', 'Generated')
                    ->first();
                
                if ($existingFee) {
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
                    
                    return redirect()
                        ->route('accounting.direct-payment.student')
                        ->with('success', $successMessage);
                }
            }
        }
        
        // Add late_fee to validated data
        $validated['late_fee'] = $lateFee;
        
        // Add accountant if available
        if (auth()->check()) {
            $validated['accountant'] = auth()->user()->name ?? null;
        }

        StudentPayment::create($validated);

        $successMessage = 'Payment recorded successfully!';
        if ($lateFee > 0) {
            $successMessage .= " Late fee of " . number_format($lateFee, 2) . " has been added.";
        }

        return redirect()
            ->route('accounting.direct-payment.student')
            ->with('success', $successMessage);
    }
}

