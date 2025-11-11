<?php

namespace App\Http\Controllers;

use App\Models\Test;
use App\Models\ClassModel;
use App\Models\Section;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TestScheduleController extends Controller
{
    /**
     * Display the test schedule page with filters.
     */
    public function index(Request $request): View
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

        // Query tests based on filters
        $query = Test::query();

        if ($filterClass) {
            $query->where('for_class', $filterClass);
        }
        if ($filterSection) {
            $query->where('section', $filterSection);
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

        return view('test.schedule', compact(
            'classes',
            'sections',
            'testTypes',
            'tests',
            'filterClass',
            'filterSection',
            'filterTestType',
            'filterFromDate',
            'filterToDate'
        ));
    }
}

