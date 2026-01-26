<?php

namespace App\Http\Controllers;

use App\Models\StudentPayment;
use App\Models\CustomPayment;
use App\Models\Student;
use App\Models\ClassModel;
use App\Models\Section;
use App\Models\Campus;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;

class DetailedIncomeController extends Controller
{
    /**
     * Display the detailed income reports with filters.
     */
    public function index(Request $request): View
    {
        // Get filter values
        $filterCampus = $request->get('filter_campus');
        $filterClass = $request->get('filter_class');
        $filterSection = $request->get('filter_section');
        $filterMonth = $request->get('filter_month');
        $filterDate = $request->get('filter_date');
        $filterYear = $request->get('filter_year');
        $filterMethod = $request->get('filter_method');

        // Get campuses from Campus model first, then fallback
        $campuses = Campus::orderBy('campus_name', 'asc')->pluck('campus_name');
        if ($campuses->isEmpty()) {
            $campusesFromPayments = StudentPayment::whereNotNull('campus')->distinct()->pluck('campus');
            $campusesFromCustom = CustomPayment::whereNotNull('campus')->distinct()->pluck('campus');
            $campuses = $campusesFromPayments->merge($campusesFromCustom)->unique()->sort()->values();
        }

        // Get classes
        $classesQuery = ClassModel::whereNotNull('class_name');
        if ($filterCampus) {
            $classesQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($filterCampus))]);
        }
        $classes = $classesQuery->distinct()->pluck('class_name')->sort()->values();
        if ($classes->isEmpty()) {
            $classesFromStudents = Student::whereNotNull('class');
            if ($filterCampus) {
                $classesFromStudents->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($filterCampus))]);
            }
            $classesFromStudents = $classesFromStudents->distinct()->pluck('class')->sort()->values();
            $classes = $classesFromStudents->isEmpty()
                ? collect(['Nursery', 'KG', '1st', '2nd', '3rd', '4th', '5th', '6th', '7th', '8th', '9th', '10th', '11th', '12th'])
                : $classesFromStudents;
        }

        // Get sections
        $sectionsQuery = Section::whereNotNull('name');
        if ($filterCampus) {
            $sectionsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($filterCampus))]);
        }
        if ($filterClass) {
            $sectionsQuery->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($filterClass))]);
        }
        $sections = $sectionsQuery->distinct()->pluck('name')->sort()->values();
        if ($sections->isEmpty()) {
            $sectionsFromSubjects = \App\Models\Subject::whereNotNull('section');
            if ($filterCampus) {
                $sectionsFromSubjects->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($filterCampus))]);
            }
            if ($filterClass) {
                $sectionsFromSubjects->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($filterClass))]);
            }
            $sectionsFromSubjects = $sectionsFromSubjects->distinct()->pluck('section')->sort()->values();
            $sections = $sectionsFromSubjects->isEmpty()
                ? collect(['A', 'B', 'C', 'D', 'E'])
                : $sectionsFromSubjects;
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
        if ($filterCampus) {
            $studentPaymentsQuery->where('campus', $filterCampus);
        }
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
                'parent_name' => $student ? ($student->father_name ?? 'N/A') : 'N/A',
                'campus' => $payment->campus ?? ($student ? $student->campus : 'N/A'),
                'class' => $student ? $student->class : 'N/A',
                'section' => $student ? $student->section : 'N/A',
                'payment_title' => $payment->payment_title,
                'payment_amount' => $payment->payment_amount,
                'discount' => $payment->discount ?? 0,
                'payment_date' => $payment->payment_date,
                'method' => $payment->method,
                'received_by' => $payment->accountant ?? 'N/A',
            ]);
        }

        // Custom Payments (Income)
        $customPaymentsQuery = CustomPayment::query();
        if ($filterCampus) {
            $customPaymentsQuery->where('campus', $filterCampus);
        }
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
            if ($filterClass || $filterSection) {
                continue;
            }
            $incomeRecords->push([
                'type' => 'Custom Payment',
                'student_code' => 'N/A',
                'student_name' => 'N/A',
                'parent_name' => 'N/A',
                'campus' => $payment->campus ?? 'N/A',
                'class' => 'N/A',
                'section' => 'N/A',
                'payment_title' => $payment->payment_title,
                'payment_amount' => $payment->payment_amount,
                'discount' => 0,
                'payment_date' => $payment->payment_date,
                'method' => $payment->method,
                'received_by' => $payment->accountant ?? 'N/A',
            ]);
        }

        // Sort by date
        $incomeRecords = $incomeRecords->sortByDesc('payment_date')->values();

        return view('reports.detailed-income', compact(
            'campuses',
            'classes',
            'sections',
            'months',
            'years',
            'methods',
            'incomeRecords',
            'filterCampus',
            'filterClass',
            'filterSection',
            'filterMonth',
            'filterDate',
            'filterYear',
            'filterMethod'
        ));
    }

    /**
     * Get classes by campus (AJAX endpoint)
     */
    public function getClassesByCampus(Request $request): JsonResponse
    {
        $campus = $request->get('campus');

        $classesQuery = ClassModel::whereNotNull('class_name');
        if ($campus) {
            $classesQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
        }
        $classes = $classesQuery->distinct()->pluck('class_name')->sort()->values();

        if ($classes->isEmpty()) {
            $classesFromStudents = Student::whereNotNull('class');
            if ($campus) {
                $classesFromStudents->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
            }
            $classesFromStudents = $classesFromStudents->distinct()->pluck('class')->sort()->values();
            $classes = $classesFromStudents->isEmpty()
                ? collect(['Nursery', 'KG', '1st', '2nd', '3rd', '4th', '5th', '6th', '7th', '8th', '9th', '10th', '11th', '12th'])
                : $classesFromStudents;
        }

        $classes = $classes->map(function($class) {
            return trim((string) $class);
        })->filter(function($class) {
            return $class !== '';
        })->unique()->sort()->values();

        return response()->json(['classes' => $classes]);
    }

    /**
     * Get sections by class (AJAX endpoint)
     */
    public function getSectionsByClass(Request $request): JsonResponse
    {
        $class = $request->get('class');
        $campus = $request->get('campus');

        if (!$class) {
            return response()->json(['sections' => []]);
        }

        $sectionsQuery = Section::whereNotNull('name')
            ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))]);
        if ($campus) {
            $sectionsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
        }
        $sections = $sectionsQuery->distinct()->pluck('name')->sort()->values();

        if ($sections->isEmpty()) {
            $sectionsFromSubjects = \App\Models\Subject::whereNotNull('section')
                ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))]);
            if ($campus) {
                $sectionsFromSubjects->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
            }
            $sections = $sectionsFromSubjects->distinct()->pluck('section')->sort()->values();
        }

        return response()->json(['sections' => $sections]);
    }
}

