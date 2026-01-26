<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\ClassModel;
use App\Models\Section;
use App\Models\Campus;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdmissionDataReportController extends Controller
{
    /**
     * Display the admission data reports with filters.
     */
    public function index(Request $request): View
    {
        // Get filter values
        $filterCampus = $request->get('filter_campus');
        $filterClass = $request->get('filter_class');
        $filterSection = $request->get('filter_section');
        $filterYears = $request->get('filter_year', []);
        if (!is_array($filterYears)) {
            $filterYears = [$filterYears];
        }
        $filterYears = array_values(array_filter($filterYears));
        // Get campuses from Campus model first, then fallback to students/classes/sections
        $campuses = Campus::orderBy('campus_name', 'asc')->pluck('campus_name');
        if ($campuses->isEmpty()) {
            $campuses = Student::whereNotNull('campus')->distinct()->pluck('campus')->sort()->values();
        }
        if ($campuses->isEmpty()) {
            $campusesFromClasses = ClassModel::whereNotNull('campus')->distinct()->pluck('campus');
            $campusesFromSections = Section::whereNotNull('campus')->distinct()->pluck('campus');
            $campuses = $campusesFromClasses->merge($campusesFromSections)->unique()->sort()->values();
        }

        // Get classes
        $classesQuery = ClassModel::whereNotNull('class_name');
        if ($filterCampus) {
            $classesQuery->where('campus', $filterCampus);
        }
        $classes = $classesQuery->distinct()->pluck('class_name')->sort()->values();

        // Get sections
        $sectionsQuery = Section::whereNotNull('name');
        if ($filterCampus) {
            $sectionsQuery->where('campus', $filterCampus);
        }
        if ($filterClass) {
            $sectionsQuery->where('class', $filterClass);
        }
        $sections = $sectionsQuery->distinct()->pluck('name')->sort()->values();

        // Admission years
        $years = Student::whereNotNull('admission_date')
            ->selectRaw('YEAR(admission_date) as year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year');

        // Query students
        $query = Student::query();

        if ($filterCampus) {
            $query->where('campus', $filterCampus);
        }
        if ($filterClass) {
            $query->where('class', $filterClass);
        }
        if ($filterSection) {
            $query->where('section', $filterSection);
        }
        if (!empty($filterYears)) {
            $query->where(function ($yearQuery) use ($filterYears) {
                foreach ($filterYears as $year) {
                    $yearQuery->orWhereYear('admission_date', $year);
                }
            });
        }

        $students = $query->orderBy('student_name')->get();

        // Prepare admission records
        $admissionRecords = collect();

        foreach ($students as $student) {
            $admissionRecords->push([
                'student_code' => $student->student_code,
                'student_name' => $student->student_name,
                'surname_caste' => $student->surname_caste,
                'campus' => $student->campus,
                'class' => $student->class,
                'section' => $student->section,
                'admission_date' => $student->admission_date,
                'father_name' => $student->father_name,
            ]);
        }

        return view('reports.admission-data', compact(
            'campuses',
            'classes',
            'sections',
            'years',
            'admissionRecords',
            'filterCampus',
            'filterClass',
            'filterSection',
            'filterYears'
        ));
    }

    /**
     * Get classes by campus for admission data report.
     */
    public function getClassesByCampus(Request $request)
    {
        $campus = $request->get('campus');
        $query = ClassModel::whereNotNull('class_name');
        if ($campus) {
            $query->where('campus', $campus);
        }
        $classes = $query->distinct()->pluck('class_name')->sort()->values();
        return response()->json($classes);
    }

    /**
     * Get sections by class and campus for admission data report.
     */
    public function getSectionsByClass(Request $request)
    {
        $class = $request->get('class');
        $campus = $request->get('campus');
        $query = Section::whereNotNull('name');
        if ($campus) {
            $query->where('campus', $campus);
        }
        if ($class) {
            $query->where('class', $class);
        }
        $sections = $query->distinct()->pluck('name')->sort()->values();
        return response()->json($sections);
    }
}

