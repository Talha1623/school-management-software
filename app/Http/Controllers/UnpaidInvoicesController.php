<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\ClassModel;
use App\Models\Section;
use App\Models\FeeType;
use App\Models\StudentPayment;
use App\Models\Campus;
use Illuminate\Support\Facades\Schema;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UnpaidInvoicesController extends Controller
{
    /**
     * Display the list of unpaid invoices with filters.
     */
    public function index(Request $request): View
    {
        // Get filter values
        $filterCampus = $request->get('filter_campus');
        $filterClass = $request->get('filter_class');
        $filterSection = $request->get('filter_section');
        $filterStudentStatus = $request->get('filter_student_status');

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

        // Query students based on filters
        $query = Student::query();
        $hasStudentStatus = Schema::hasColumn('students', 'status');
        
        if ($filterCampus) {
            $query->where('campus', $filterCampus);
        }
        if ($filterClass) {
            $query->where('class', $filterClass);
        }
        if ($filterSection) {
            $query->where('section', $filterSection);
        }
        if ($filterStudentStatus && $hasStudentStatus) {
            $statusValue = strtolower(trim($filterStudentStatus));
            if (in_array($statusValue, ['deactive', 'inactive'], true)) {
                $query->whereRaw("LOWER(TRIM(status)) IN ('deactive', 'inactive')");
            } else {
                $query->whereRaw('LOWER(TRIM(status)) = ?', [$statusValue]);
            }
        }

        $students = $query->orderBy('student_name')->get();

        // Prepare unpaid invoices list
        $unpaidInvoices = collect();

        foreach ($students as $student) {
            // Get all payments made by this student
            $payments = StudentPayment::where('student_code', $student->student_code)->get();
            
            // Group payments by fee type (payment_title)
            $paidByType = $payments->groupBy('payment_title');
            
            // Get expected fees for this student
            $expectedFees = collect();
            
            // Monthly fee
            if ($student->monthly_fee && $student->monthly_fee > 0) {
                $expectedFees->push([
                    'type' => 'Monthly Fee',
                    'amount' => $student->monthly_fee,
                ]);
            }
            
            // Check for unpaid invoices
            foreach ($expectedFees as $expectedFee) {
                $feeType = $expectedFee['type'];
                $expectedAmount = $expectedFee['amount'];
                
                // Calculate paid amount for this fee type
                $paidAmount = $paidByType->get($feeType, collect())->sum('payment_amount');
                $unpaidAmount = $expectedAmount - $paidAmount;
                
                // Only show if there's an unpaid amount
                if ($unpaidAmount > 0) {
                    $unpaidInvoices->push([
                        'student_code' => $student->student_code,
                        'student_name' => $student->student_name,
                        'campus' => $student->campus,
                        'class' => $student->class,
                        'section' => $student->section,
                        'fee_type' => $feeType,
                        'expected_amount' => $expectedAmount,
                        'paid_amount' => $paidAmount,
                        'unpaid_amount' => $unpaidAmount,
                        'status' => $paidAmount > 0 ? 'Partial' : 'Unpaid',
                    ]);
                }
            }
            
        }

        return view('reports.unpaid-invoices', compact(
            'campuses',
            'classes',
            'sections',
            'unpaidInvoices',
            'filterCampus',
            'filterClass',
            'filterSection',
            'filterStudentStatus'
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

