<?php

namespace App\Http\Controllers;

use App\Models\StudentPayment;
use App\Models\Student;
use App\Models\ClassModel;
use App\Models\Section;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Carbon\Carbon;

class FeeDiscountController extends Controller
{
    /**
     * Display the fee discount report with filters.
     */
    public function index(Request $request): View
    {
        // Get filter values
        $filterCampus = $request->get('filter_campus');
        $filterMonth = $request->get('filter_month');
        $filterYear = $request->get('filter_year');

        // Get campuses from student payments
        $campuses = StudentPayment::whereNotNull('campus')->distinct()->pluck('campus')->sort()->values();
        
        if ($campuses->isEmpty()) {
            $campusesFromClasses = ClassModel::whereNotNull('campus')->distinct()->pluck('campus');
            $campusesFromSections = Section::whereNotNull('campus')->distinct()->pluck('campus');
            $campuses = $campusesFromClasses->merge($campusesFromSections)->unique()->sort()->values();
            
            if ($campuses->isEmpty()) {
                $campuses = collect(['Main Campus', 'Branch Campus 1', 'Branch Campus 2']);
            }
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

        // Query student payments with discounts
        $query = StudentPayment::where('discount', '>', 0);

        if ($filterCampus) {
            $query->where('campus', $filterCampus);
        }
        if ($filterMonth) {
            $query->whereMonth('payment_date', $filterMonth);
        }
        if ($filterYear) {
            $query->whereYear('payment_date', $filterYear);
        }

        $payments = $query->orderBy('payment_date', 'desc')->get();

        // Prepare discount records with student information
        $discountRecords = collect();

        foreach ($payments as $payment) {
            $student = Student::where('student_code', $payment->student_code)->first();
            
            $discountRecords->push([
                'student_code' => $payment->student_code,
                'student_name' => $student ? $student->student_name : 'N/A',
                'campus' => $payment->campus ?? ($student ? $student->campus : 'N/A'),
                'class' => $student ? $student->class : 'N/A',
                'section' => $student ? $student->section : 'N/A',
                'payment_title' => $payment->payment_title,
                'payment_amount' => $payment->payment_amount,
                'discount' => $payment->discount,
                'payment_date' => $payment->payment_date,
                'method' => $payment->method,
            ]);
        }

        return view('reports.fee-discount', compact(
            'campuses',
            'months',
            'years',
            'discountRecords',
            'filterCampus',
            'filterMonth',
            'filterYear'
        ));
    }
}

