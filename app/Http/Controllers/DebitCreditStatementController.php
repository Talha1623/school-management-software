<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\ClassModel;
use App\Models\Section;
use App\Models\StudentPayment;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DebitCreditStatementController extends Controller
{
    /**
     * Display the debit & credit statement with filters.
     */
    public function index(Request $request): View
    {
        // Get filter values
        $filterCampus = $request->get('filter_campus');
        $filterClass = $request->get('filter_class');
        $filterSection = $request->get('filter_section');
        $filterFromDate = $request->get('filter_from_date');
        $filterToDate = $request->get('filter_to_date');

        // Get campuses from classes or sections
        $campusesFromClasses = ClassModel::whereNotNull('campus')->distinct()->pluck('campus');
        $campusesFromSections = Section::whereNotNull('campus')->distinct()->pluck('campus');
        $campuses = $campusesFromClasses->merge($campusesFromSections)->unique()->sort()->values();
        
        if ($campuses->isEmpty()) {
            $campuses = collect(['Main Campus', 'Branch Campus 1', 'Branch Campus 2']);
        }

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

        // Prepare debit and credit records
        $statements = collect();

        // Query students based on filters
        $studentsQuery = Student::query();
        
        if ($filterCampus) {
            $studentsQuery->where('campus', $filterCampus);
        }
        if ($filterClass) {
            $studentsQuery->where('class', $filterClass);
        }
        if ($filterSection) {
            $studentsQuery->where('section', $filterSection);
        }

        $students = $studentsQuery->get();
        $studentCodes = $students->pluck('student_code')->filter();

        // Get student payments (Credits)
        if ($studentCodes->isNotEmpty()) {
            $paymentsQuery = StudentPayment::whereIn('student_code', $studentCodes);
            
            if ($filterCampus) {
                $paymentsQuery->where('campus', $filterCampus);
            }
            if ($filterFromDate) {
                $paymentsQuery->where('payment_date', '>=', $filterFromDate);
            }
            if ($filterToDate) {
                $paymentsQuery->where('payment_date', '<=', $filterToDate);
            }
            
            $payments = $paymentsQuery->orderBy('payment_date', 'desc')->get();
            
            foreach ($payments as $payment) {
                $student = $students->firstWhere('student_code', $payment->student_code);
                
                // Credit entry (payment received)
                $statements->push([
                    'date' => $payment->payment_date,
                    'student_code' => $payment->student_code,
                    'student_name' => $student ? $student->student_name : 'N/A',
                    'class' => $student ? $student->class : 'N/A',
                    'section' => $student ? $student->section : 'N/A',
                    'campus' => $payment->campus ?? ($student ? $student->campus : 'N/A'),
                    'description' => $payment->payment_title,
                    'type' => 'Credit',
                    'amount' => $payment->payment_amount,
                    'discount' => $payment->discount ?? 0,
                    'method' => $payment->method,
                ]);
            }
        }

        // Sort by date
        $statements = $statements->sortByDesc('date')->values();

        return view('reports.debit-credit', compact(
            'campuses',
            'classes',
            'sections',
            'statements',
            'filterCampus',
            'filterClass',
            'filterSection',
            'filterFromDate',
            'filterToDate'
        ));
    }
}

