<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use App\Models\ClassModel;
use App\Models\Section;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ManageSubjectsController extends Controller
{
    /**
     * Display a listing of subjects.
     */
    public function index(Request $request): View
    {
        // Get unique values for dropdowns
        $campuses = collect();
        $classes = collect();
        $sections = collect();
        
        // Get campuses from classes or sections
        $campusesFromClasses = ClassModel::whereNotNull('campus')->distinct()->pluck('campus');
        $campusesFromSections = Section::whereNotNull('campus')->distinct()->pluck('campus');
        $campuses = $campusesFromClasses->merge($campusesFromSections)->unique()->sort()->values();
        
        // Get classes
        $classes = ClassModel::distinct()->pluck('class_name')->sort()->values();
        
        // Get sections
        $sections = Section::whereNotNull('name')->distinct()->pluck('name')->sort()->values();
        
        // If no data exists, provide defaults
        if ($campuses->isEmpty()) {
            $campuses = collect(['Main Campus', 'Branch Campus 1', 'Branch Campus 2']);
        }
        if ($classes->isEmpty()) {
            $classes = collect(['Nursery', 'KG', '1st', '2nd', '3rd', '4th', '5th', '6th', '7th', '8th', '9th', '10th', '11th', '12th']);
        }
        
        // Only query if at least one filter is applied
        if ($request->filled('filter_campus') || $request->filled('filter_class') || $request->filled('filter_section')) {
            $query = Subject::query();
            
            // Apply filters
            if ($request->filled('filter_campus')) {
                $query->where('campus', $request->filter_campus);
            }
            
            if ($request->filled('filter_class')) {
                $query->where('class', $request->filter_class);
            }
            
            if ($request->filled('filter_section')) {
                $query->where('section', $request->filter_section);
            }
            
            $perPage = $request->get('per_page', 10);
            $perPage = in_array($perPage, [10, 25, 50, 100]) ? $perPage : 10;
            
            $subjects = $query->latest()->paginate($perPage)->withQueryString();
        } else {
            // Return empty paginator when no filters are applied
            $subjects = new \Illuminate\Pagination\LengthAwarePaginator(
                collect(),
                0,
                10,
                1,
                ['path' => $request->url(), 'query' => $request->query()]
            );
        }
        
        return view('manage-subjects', compact('subjects', 'campuses', 'classes', 'sections'));
    }
}

