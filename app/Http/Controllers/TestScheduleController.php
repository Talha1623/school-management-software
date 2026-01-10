<?php

namespace App\Http\Controllers;

use App\Models\Test;
use App\Models\ClassModel;
use App\Models\Section;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;

class TestScheduleController extends Controller
{
    /**
     * Display the test schedule page with filters.
     */
    public function index(Request $request): View
    {
        // Get classes
        $classes = ClassModel::whereNotNull('class_name')->distinct()->pluck('class_name')->sort()->values();
        
        if ($classes->isEmpty()) {
            $classesFromSubjects = Subject::whereNotNull('class')->distinct()->pluck('class')->sort();
            $classes = $classesFromSubjects->isEmpty() ? collect(['Nursery', 'KG', '1st', '2nd', '3rd', '4th', '5th', '6th', '7th', '8th', '9th', '10th', '11th', '12th']) : $classesFromSubjects;
        }

        // Get test types
        $testTypes = Test::whereNotNull('test_type')->distinct()->pluck('test_type')->sort()->values();
        
        // Add default test types if database is empty, including daily, weekly, monthly tests
        if ($testTypes->isEmpty()) {
            $testTypes = collect(['Quiz', 'Mid Term', 'Final Term', 'Assignment', 'Project', 'Oral Test', 'Daily Test', 'Weekly Test', 'Monthly Test']);
        } else {
            // Merge with default test types to ensure daily, weekly, monthly are always available
            $defaultTypes = ['Daily Test', 'Weekly Test', 'Monthly Test'];
            $testTypes = $testTypes->merge($defaultTypes)->unique()->sort()->values();
        }

        // Get all tests by default
        $tests = Test::orderBy('date', 'asc')->get();

        return view('test.schedule', compact(
            'classes',
            'testTypes',
            'tests'
        ));
    }

    /**
     * Get sections by class (AJAX endpoint)
     */
    public function getSectionsByClass(Request $request): JsonResponse
    {
        $class = $request->get('class');
        
        if (!$class) {
            return response()->json(['sections' => []]);
        }

        $staff = Auth::guard('staff')->user();
        $sections = collect();
        
        // Filter by teacher's assigned subjects and sections if teacher
        if ($staff && $staff->isTeacher()) {
            // Get sections from teacher's assigned subjects for this class
            $assignedSubjects = Subject::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($staff->name ?? ''))])
                ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))])
                ->get();
            
            // Get sections from teacher's assigned sections for this class
            $assignedSections = Section::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($staff->name ?? ''))])
                ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))])
                ->get();
            
            // Merge sections from both sources
            $sections = $assignedSubjects->pluck('section')
                ->merge($assignedSections->pluck('name'))
                ->map(function($section) {
                    return trim($section);
                })
                ->filter(function($section) {
                    return !empty($section);
                })
                ->unique()
                ->sort()
                ->values();
        } else {
            // For non-teachers, get all sections
            $sections = Section::whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))])
                ->whereNotNull('name')
                ->distinct()
                ->pluck('name')
                ->sort()
                ->values();
            
            if ($sections->isEmpty()) {
                $sections = Subject::whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))])
                    ->whereNotNull('section')
                    ->distinct()
                    ->pluck('section')
                    ->sort()
                    ->values();
            }
        }

        return response()->json(['sections' => $sections]);
    }

    /**
     * Get filtered tests (AJAX endpoint)
     */
    public function getFilteredTests(Request $request): JsonResponse
    {
        try {
            $filterClass = $request->get('filter_class');
            $filterSection = $request->get('filter_section');
            $filterTestType = $request->get('filter_test_type');
            $filterFromDate = $request->get('filter_from_date');
            $filterToDate = $request->get('filter_to_date');

            // Query tests based on filters
            $query = Test::query();

            if ($filterClass) {
                $query->whereRaw('LOWER(TRIM(for_class)) = ?', [strtolower(trim($filterClass))]);
            }
            if ($filterSection) {
                $query->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($filterSection))]);
            }
            if ($filterTestType) {
                $query->where('test_type', $filterTestType);
            }
            if ($filterFromDate) {
                $query->whereDate('date', '>=', $filterFromDate);
            }
            if ($filterToDate) {
                $query->whereDate('date', '<=', $filterToDate);
            }

            $tests = $query->orderBy('date', 'asc')->get();

            $testsData = $tests->map(function($test) {
                return [
                    'id' => $test->id,
                    'for_class' => $test->for_class,
                    'section' => $test->section,
                    'subject' => $test->subject,
                    'test_type' => $test->test_type,
                    'date' => $test->date ? date('d M Y', strtotime($test->date)) : 'N/A',
                    'date_raw' => $test->date ? $test->date->format('Y-m-d') : null,
                ];
            });

            return response()->json([
                'success' => true,
                'tests' => $testsData,
                'total' => $tests->count()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching tests: ' . $e->getMessage()
            ], 500);
        }
    }
}

