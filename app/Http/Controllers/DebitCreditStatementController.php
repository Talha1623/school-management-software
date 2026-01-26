<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\ClassModel;
use App\Models\Section;
use App\Models\StudentPayment;
use App\Models\Campus;
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

        // Get campuses from Campus model first, then fallback to classes/sections
        $campuses = Campus::orderBy('campus_name', 'asc')->pluck('campus_name');
        if ($campuses->isEmpty()) {
            $campusesFromClasses = ClassModel::whereNotNull('campus')->distinct()->pluck('campus');
            $campusesFromSections = Section::whereNotNull('campus')->distinct()->pluck('campus');
            $campuses = $campusesFromClasses->merge($campusesFromSections)->unique()->sort()->values();
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

    /**
     * Get classes by campus (AJAX endpoint)
     */
    public function getClassesByCampus(Request $request): \Illuminate\Http\JsonResponse
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
    public function getSectionsByClass(Request $request): \Illuminate\Http\JsonResponse
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

