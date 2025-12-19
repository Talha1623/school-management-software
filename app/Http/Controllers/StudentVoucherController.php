<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\ClassModel;
use App\Models\Section;
use App\Models\StudentPayment;
use App\Models\MonthlyFee;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class StudentVoucherController extends Controller
{
    /**
     * Show the student vouchers page with filters.
     */
    public function index(Request $request): View
    {
        // Get classes
        $classes = ClassModel::orderBy('class_name', 'asc')->get();
        if ($classes->isEmpty()) {
            $classes = collect();
        }
        
        // Get sections (will be filtered by class via AJAX)
        $sections = collect();
        if ($request->filled('class')) {
            $sections = Section::where('class', $request->class)
                ->orderBy('name', 'asc')
                ->get();
        }
        
        $query = Student::query();
        
        // Apply filters
        if ($request->filled('class')) {
            $query->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($request->class))]);
        }
        
        if ($request->filled('section')) {
            $query->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($request->section))]);
        }
        
        // Type and vouchers_for are filter options, not stored in Student model
        // They will be used for voucher generation
        
        $students = $query->orderBy('student_name')->paginate(20)->withQueryString();
        
        return view('accounting.fee-voucher.student', compact('students', 'classes', 'sections'));
    }
    
    /**
     * Get sections by class name (AJAX).
     */
    public function getSectionsByClass(Request $request): JsonResponse
    {
        $className = $request->get('class');
        
        if (!$className) {
            return response()->json(['sections' => []]);
        }

        // Get sections for the selected class
        $sections = Section::where('class', $className)
            ->orderBy('name', 'asc')
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
        
        // Apply filters
        if ($request->filled('class')) {
            $query->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($request->class))]);
        }
        
        if ($request->filled('section')) {
            $query->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($request->section))]);
        }
        
        $students = $query->orderBy('student_name')->get();
        
        $type = $request->get('type', 'Monthly Fee');
        $vouchersFor = $request->get('vouchers_for', date('F')); // Month name
        $currentYear = date('Y');
        
        // Get fee data for each student
        $vouchers = [];
        foreach ($students as $student) {
            // Get monthly fee from student record
            $monthlyFee = $student->monthly_fee ?? 0;
            
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
            
            // Get MonthlyFee record for the selected month to get due_date and late_fee
            $monthlyFeeRecord = MonthlyFee::where('fee_month', $vouchersFor)
                ->where('fee_year', $currentYear)
                ->where(function($q) use ($student) {
                    $q->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($student->campus ?? ''))])
                      ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($student->class ?? ''))])
                      ->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($student->section ?? ''))]);
                })
                ->first();
            
            $dueDate = $monthlyFeeRecord ? $monthlyFeeRecord->due_date : Carbon::now()->addDays(15);
            $lateFee = $monthlyFeeRecord ? (float) $monthlyFeeRecord->late_fee : 0;
            $voucherValidity = Carbon::parse($dueDate)->addDays(5);
            
            // Generate voucher number
            $voucherNumber = strtoupper(substr($vouchersFor, 0, 3)) . '-' . str_pad($student->id, 5, '0', STR_PAD_LEFT) . '-' . substr($currentYear, -2);
            
            $vouchers[] = [
                'student' => $student,
                'monthly_fee' => $monthlyFee,
                'late_fee' => $lateFee,
                'subtotal' => $monthlyFee,
                'total' => $monthlyFee + $lateFee,
                'after_due_date' => $monthlyFee + $lateFee,
                'due_date' => $dueDate,
                'voucher_validity' => $voucherValidity,
                'voucher_number' => $voucherNumber,
                'fee_history' => $feeHistory,
                'month' => $vouchersFor,
                'year' => $currentYear,
            ];
        }
        
        return view('accounting.fee-voucher.print', compact('vouchers', 'type', 'vouchersFor', 'currentYear'));
    }
}

