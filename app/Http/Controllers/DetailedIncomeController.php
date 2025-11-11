<?php

namespace App\Http\Controllers;

use App\Models\StudentPayment;
use App\Models\CustomPayment;
use App\Models\Student;
use App\Models\ClassModel;
use App\Models\Section;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DetailedIncomeController extends Controller
{
    /**
     * Display the detailed income reports with filters.
     */
    public function index(Request $request): View
    {
        // Get filter values
        $filterClass = $request->get('filter_class');
        $filterSection = $request->get('filter_section');
        $filterMonth = $request->get('filter_month');
        $filterDate = $request->get('filter_date');
        $filterYear = $request->get('filter_year');
        $filterMethod = $request->get('filter_method');

        // Get classes
        $classes = ClassModel::whereNotNull('class_name')->distinct()->pluck('class_name')->sort()->values();
        if ($classes->isEmpty()) {
            $classes = collect(['Nursery', 'KG', '1st', '2nd', '3rd', '4th', '5th', '6th', '7th', '8th', '9th', '10th', '11th', '12th']);
        }

        // Get sections
        $sections = Section::whereNotNull('name')->distinct()->pluck('name')->sort()->values();
        if ($sections->isEmpty()) {
            $sections = collect(['A', 'B', 'C', 'D', 'E']);
        }

        // Month options
        $months = collect([
            '01' => 'January',
            '02' => 'February',
            '03' => 'March',
            '04' => 'April',
            '05' => 'May',
            '06' => 'June',
            '07' => 'July',
            '08' => 'August',
            '09' => 'September',
            '10' => 'October',
            '11' => 'November',
            '12' => 'December',
        ]);

        // Year options (current year and previous 5 years)
        $currentYear = date('Y');
        $years = collect();
        for ($i = 0; $i < 6; $i++) {
            $years->push($currentYear - $i);
        }

        // Get payment methods
        $methodsFromPayments = StudentPayment::whereNotNull('method')->distinct()->pluck('method');
        $methodsFromCustom = CustomPayment::whereNotNull('method')->distinct()->pluck('method');
        $methods = $methodsFromPayments->merge($methodsFromCustom)->unique()->sort()->values();
        
        if ($methods->isEmpty()) {
            $methods = collect(['Cash', 'Bank Transfer', 'Cheque', 'Online Payment', 'Card']);
        }

        // Prepare income records
        $incomeRecords = collect();

        // Student Payments (Income)
        $studentPaymentsQuery = StudentPayment::query();
        
        if ($filterMonth) {
            $studentPaymentsQuery->whereMonth('payment_date', $filterMonth);
        }
        if ($filterDate) {
            $studentPaymentsQuery->whereDate('payment_date', $filterDate);
        }
        if ($filterYear) {
            $studentPaymentsQuery->whereYear('payment_date', $filterYear);
        }
        if ($filterMethod) {
            $studentPaymentsQuery->where('method', $filterMethod);
        }
        
        $studentPayments = $studentPaymentsQuery->get();

        foreach ($studentPayments as $payment) {
            $student = Student::where('student_code', $payment->student_code)->first();
            
            // Apply class and section filters
            if ($filterClass && (!$student || $student->class != $filterClass)) {
                continue;
            }
            if ($filterSection && (!$student || $student->section != $filterSection)) {
                continue;
            }
            
            $incomeRecords->push([
                'type' => 'Student Payment',
                'student_code' => $payment->student_code,
                'student_name' => $student ? $student->student_name : 'N/A',
                'campus' => $payment->campus ?? ($student ? $student->campus : 'N/A'),
                'class' => $student ? $student->class : 'N/A',
                'section' => $student ? $student->section : 'N/A',
                'payment_title' => $payment->payment_title,
                'payment_amount' => $payment->payment_amount,
                'discount' => $payment->discount ?? 0,
                'net_amount' => $payment->payment_amount - ($payment->discount ?? 0),
                'payment_date' => $payment->payment_date,
                'method' => $payment->method,
            ]);
        }

        // Custom Payments (Income)
        $customPaymentsQuery = CustomPayment::query();
        
        if ($filterMonth) {
            $customPaymentsQuery->whereMonth('payment_date', $filterMonth);
        }
        if ($filterDate) {
            $customPaymentsQuery->whereDate('payment_date', $filterDate);
        }
        if ($filterYear) {
            $customPaymentsQuery->whereYear('payment_date', $filterYear);
        }
        if ($filterMethod) {
            $customPaymentsQuery->where('method', $filterMethod);
        }
        
        $customPayments = $customPaymentsQuery->get();

        foreach ($customPayments as $payment) {
            $incomeRecords->push([
                'type' => 'Custom Payment',
                'student_code' => 'N/A',
                'student_name' => 'N/A',
                'campus' => $payment->campus ?? 'N/A',
                'class' => 'N/A',
                'section' => 'N/A',
                'payment_title' => $payment->payment_title,
                'payment_amount' => $payment->payment_amount,
                'discount' => 0,
                'net_amount' => $payment->payment_amount,
                'payment_date' => $payment->payment_date,
                'method' => $payment->method,
            ]);
        }

        // Sort by date
        $incomeRecords = $incomeRecords->sortByDesc('payment_date')->values();

        return view('reports.detailed-income', compact(
            'classes',
            'sections',
            'months',
            'years',
            'methods',
            'incomeRecords',
            'filterClass',
            'filterSection',
            'filterMonth',
            'filterDate',
            'filterYear',
            'filterMethod'
        ));
    }
}

