<?php

namespace App\Http\Controllers;

use App\Models\StudentPayment;
use App\Models\Student;
use App\Models\ClassModel;
use App\Models\Section;
use App\Models\Campus;
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
        $filterFromDate = $request->get('filter_from_date');
        $filterToDate = $request->get('filter_to_date');

        // Get campuses from Campus model first, then fallback to payments/classes/sections
        $campuses = Campus::orderBy('campus_name', 'asc')->pluck('campus_name');
        if ($campuses->isEmpty()) {
            $campusesFromPayments = StudentPayment::whereNotNull('campus')->distinct()->pluck('campus');
            $campusesFromClasses = ClassModel::whereNotNull('campus')->distinct()->pluck('campus');
            $campusesFromSections = Section::whereNotNull('campus')->distinct()->pluck('campus');
            $campuses = $campusesFromPayments->merge($campusesFromClasses)->merge($campusesFromSections)->unique()->sort()->values();
        }

        // Query student payments with discounts
        $query = StudentPayment::where('discount', '>', 0);

        if ($filterCampus) {
            $query->where('campus', $filterCampus);
        }
        if ($filterFromDate) {
            $query->whereDate('payment_date', '>=', $filterFromDate);
        }
        if ($filterToDate) {
            $query->whereDate('payment_date', '<=', $filterToDate);
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
            'discountRecords',
            'filterCampus',
            'filterFromDate',
            'filterToDate'
        ));
    }
}

