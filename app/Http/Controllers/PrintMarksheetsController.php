<?php

namespace App\Http\Controllers;

use App\Models\Test;
use App\Models\ClassModel;
use App\Models\Section;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;

class PrintMarksheetsController extends Controller
{
    /**
     * Display the print marksheets for practical test page.
     */
    public function practical(Request $request): View
    {
        $campuses = $this->getCampuses();
        $classes = $this->getClasses();
        $sections = $this->getSectionsData();
        $subjects = $this->getSubjectsData();
        $tests = $this->getTestsData();

        return view('test.print-marksheets.practical', compact('campuses', 'classes', 'sections', 'subjects', 'tests'));
    }

    /**
     * Get sections based on class (AJAX).
     */
    public function getSections(Request $request): JsonResponse
    {
        $class = $request->get('class');
        $sections = Section::when($class, fn($q) => $q->where('class', $class))
            ->whereNotNull('name')
            ->distinct()
            ->pluck('name')
            ->sort()
            ->values();
        
        return response()->json($sections->isEmpty() ? ['A', 'B', 'C', 'D'] : $sections);
    }

    /**
     * Get subjects based on section (AJAX).
     */
    public function getSubjects(Request $request): JsonResponse
    {
        $section = $request->get('section');
        $class = $request->get('class');
        
        $subjects = Subject::when($class, fn($q) => $q->where('class', $class))
            ->when($section, fn($q) => $q->where('section', $section))
            ->whereNotNull('subject_name')
            ->distinct()
            ->pluck('subject_name')
            ->sort()
            ->values();
        
        return response()->json($subjects->isEmpty() ? ['Mathematics', 'English', 'Science', 'Urdu', 'Islamiat', 'Social Studies'] : $subjects);
    }

    /**
     * Get tests based on subject (AJAX).
     */
    public function getTests(Request $request): JsonResponse
    {
        $subject = $request->get('subject');
        $section = $request->get('section');
        $class = $request->get('class');
        
        $tests = Test::when($class, fn($q) => $q->where('for_class', $class))
            ->when($section, fn($q) => $q->where('section', $section))
            ->when($subject, fn($q) => $q->where('subject', $subject))
            ->whereNotNull('test_name')
            ->distinct()
            ->pluck('test_name')
            ->sort()
            ->values();
        
        return response()->json($tests->isEmpty() ? ['Quiz 1', 'Mid Term', 'Final Term', 'Assignment 1'] : $tests);
    }

    private function getCampuses()
    {
        $campuses = ClassModel::whereNotNull('campus')->distinct()->pluck('campus')
            ->merge(Section::whereNotNull('campus')->distinct()->pluck('campus'))
            ->unique()
            ->sort()
            ->values();
        
        return $campuses->isEmpty() ? collect(['Main Campus', 'Branch Campus 1', 'Branch Campus 2']) : $campuses;
    }

    private function getClasses()
    {
        $classes = ClassModel::whereNotNull('class_name')->distinct()->pluck('class_name')->sort()->values();
        return $classes->isEmpty() ? collect(['Nursery', 'KG', '1st', '2nd', '3rd', '4th', '5th', '6th', '7th', '8th', '9th', '10th', '11th', '12th']) : $classes;
    }

    private function getSectionsData()
    {
        $sections = Section::whereNotNull('name')->distinct()->pluck('name')->sort()->values();
        return $sections->isEmpty() ? collect(['A', 'B', 'C', 'D']) : $sections;
    }

    private function getSubjectsData()
    {
        $subjects = Subject::whereNotNull('subject_name')->distinct()->pluck('subject_name')->sort()->values();
        return $subjects->isEmpty() ? collect(['Mathematics', 'English', 'Science', 'Urdu', 'Islamiat', 'Social Studies']) : $subjects;
    }

    private function getTestsData()
    {
        $tests = Test::whereNotNull('test_name')->distinct()->pluck('test_name')->sort()->values();
        return $tests->isEmpty() ? collect(['Quiz 1', 'Mid Term', 'Final Term', 'Assignment 1']) : $tests;
    }
}

