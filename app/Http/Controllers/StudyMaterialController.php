<?php

namespace App\Http\Controllers;

use App\Models\ClassModel;
use App\Models\Section;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudyMaterialController extends Controller
{
    /**
     * Display the study material LMS page.
     */
    public function lms(Request $request): View
    {
        // Get filter values
        $filterCampus = $request->get('filter_campus');
        $filterClass = $request->get('filter_class');
        $filterSection = $request->get('filter_section');
        $filterType = $request->get('filter_type');

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

        // Get material types
        $materialTypes = collect(['Video', 'Document', 'PDF', 'Image', 'Audio', 'Link', 'Assignment', 'Quiz']);

        return view('study-material.lms', compact(
            'campuses',
            'classes',
            'sections',
            'materialTypes',
            'filterCampus',
            'filterClass',
            'filterSection',
            'filterType'
        ));
    }
}

