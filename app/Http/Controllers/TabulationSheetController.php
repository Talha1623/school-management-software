<?php

namespace App\Http\Controllers;

use App\Models\Test;
use App\Models\Student;
use App\Models\ClassModel;
use App\Models\Section;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TabulationSheetController extends Controller
{
    /**
     * Display the tabulation sheet for practical test page with filters.
     */
    public function practical(Request $request): View
    {
        // Get filter values
        $filterClass = $request->get('filter_class');
        $filterSection = $request->get('filter_section');
        $filterSubject = $request->get('filter_subject');
        $filterTest = $request->get('filter_test');

        // Get classes
        $classes = ClassModel::whereNotNull('class_name')->distinct()->pluck('class_name')->sort()->values();
        
        if ($classes->isEmpty()) {
            $classes = collect(['Nursery', 'KG', '1st', '2nd', '3rd', '4th', '5th', '6th', '7th', '8th', '9th', '10th', '11th', '12th']);
        }

        // Get sections
        $sections = Section::whereNotNull('name')->distinct()->pluck('name')->sort()->values();
        
        if ($sections->isEmpty()) {
            $sections = collect(['A', 'B', 'C', 'D']);
        }

        // Get subjects
        $subjectsQuery = Subject::query();
        if ($filterClass) {
            $subjectsQuery->where('class', $filterClass);
        }
        if ($filterSection) {
            $subjectsQuery->where('section', $filterSection);
        }
        $subjects = $subjectsQuery->whereNotNull('subject_name')->distinct()->pluck('subject_name')->sort()->values();
        
        if ($subjects->isEmpty()) {
            $subjects = collect(['Mathematics', 'English', 'Science', 'Urdu', 'Islamiat', 'Social Studies']);
        }

        // Get tests (filtered by other criteria if provided)
        $testsQuery = Test::query();
        if ($filterClass) {
            $testsQuery->where('for_class', $filterClass);
        }
        if ($filterSection) {
            $testsQuery->where('section', $filterSection);
        }
        if ($filterSubject) {
            $testsQuery->where('subject', $filterSubject);
        }
        $tests = $testsQuery->whereNotNull('test_name')->distinct()->pluck('test_name')->sort()->values();
        
        if ($tests->isEmpty()) {
            $tests = collect(['Quiz 1', 'Mid Term', 'Final Term', 'Assignment 1']);
        }

        // Query students based on filters
        $students = collect();
        if ($filterClass || $filterSection) {
            $studentsQuery = Student::query();
            
            if ($filterClass) {
                $studentsQuery->where('class', $filterClass);
            }
            if ($filterSection) {
                $studentsQuery->where('section', $filterSection);
            }
            
            $students = $studentsQuery->orderBy('student_name')->get();
        }

        return view('test.tabulation-sheet.practical', compact(
            'classes',
            'sections',
            'subjects',
            'tests',
            'students',
            'filterClass',
            'filterSection',
            'filterSubject',
            'filterTest'
        ));
    }

    /**
     * Display the tabulation sheet for combine test page with filters.
     */
    public function combine(Request $request): View
    {
        // Get filter values
        $filterClass = $request->get('filter_class');
        $filterSection = $request->get('filter_section');
        $filterTestType = $request->get('filter_test_type');
        $filterFromDate = $request->get('filter_from_date');
        $filterToDate = $request->get('filter_to_date');

        // Get classes
        $classes = ClassModel::whereNotNull('class_name')->distinct()->pluck('class_name')->sort()->values();
        
        if ($classes->isEmpty()) {
            $classes = collect(['Nursery', 'KG', '1st', '2nd', '3rd', '4th', '5th', '6th', '7th', '8th', '9th', '10th', '11th', '12th']);
        }

        // Get sections
        $sections = Section::whereNotNull('name')->distinct()->pluck('name')->sort()->values();
        
        if ($sections->isEmpty()) {
            $sections = collect(['A', 'B', 'C', 'D']);
        }

        // Get test types
        $testTypes = Test::whereNotNull('test_type')->distinct()->pluck('test_type')->sort()->values();
        
        if ($testTypes->isEmpty()) {
            $testTypes = collect(['Quiz', 'Mid Term', 'Final Term', 'Assignment', 'Project', 'Oral Test']);
        }

        // Query students based on filters
        $students = collect();
        if ($filterClass || $filterSection) {
            $studentsQuery = Student::query();
            
            if ($filterClass) {
                $studentsQuery->where('class', $filterClass);
            }
            if ($filterSection) {
                $studentsQuery->where('section', $filterSection);
            }
            
            $students = $studentsQuery->orderBy('student_name')->get();
        }

        return view('test.tabulation-sheet.combine', compact(
            'classes',
            'sections',
            'testTypes',
            'students',
            'filterClass',
            'filterSection',
            'filterTestType',
            'filterFromDate',
            'filterToDate'
        ));
    }
}

