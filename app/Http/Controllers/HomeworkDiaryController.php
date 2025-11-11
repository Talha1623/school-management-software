<?php

namespace App\Http\Controllers;

use App\Models\ClassModel;
use App\Models\Section;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeworkDiaryController extends Controller
{
    /**
     * Display the add & manage diaries page.
     */
    public function manage(Request $request): View
    {
        // Get filter values
        $filterCampus = $request->get('filter_campus');
        $filterClass = $request->get('filter_class');
        $filterSection = $request->get('filter_section');
        $filterDate = $request->get('filter_date');

        // Get campuses for dropdown
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

        // Get sections (filtered by class if provided)
        $sectionsQuery = Section::query();
        if ($filterClass) {
            $sectionsQuery->where('class', $filterClass);
        }
        $sections = $sectionsQuery->whereNotNull('name')->distinct()->pluck('name')->sort()->values();
        
        if ($sections->isEmpty()) {
            $sections = collect(['A', 'B', 'C', 'D']);
        }

        return view('homework-diary.manage', compact(
            'campuses',
            'classes',
            'sections',
            'filterCampus',
            'filterClass',
            'filterSection',
            'filterDate'
        ));
    }
}

