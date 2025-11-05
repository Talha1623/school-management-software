<?php

namespace App\Http\Controllers;

use App\Models\Timetable;
use App\Models\ClassModel;
use App\Models\Section;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TimetableController extends Controller
{
    /**
     * Display a listing of timetables with filters.
     */
    public function index(Request $request): View
    {
        // Get unique values for dropdowns
        $campuses = collect();
        $classes = collect();
        $sections = collect();
        
        // Get campuses from timetables
        $campuses = Timetable::whereNotNull('campus')->distinct()->pluck('campus')->sort()->values();
        
        // Get classes from timetables
        $classes = Timetable::whereNotNull('class')->distinct()->pluck('class')->sort()->values();
        
        // Get sections from timetables
        $sections = Timetable::whereNotNull('section')->distinct()->pluck('section')->sort()->values();
        
        // If no data exists, provide defaults
        if ($campuses->isEmpty()) {
            $campusesFromClasses = ClassModel::whereNotNull('campus')->distinct()->pluck('campus');
            $campusesFromSections = Section::whereNotNull('campus')->distinct()->pluck('campus');
            $campuses = $campusesFromClasses->merge($campusesFromSections)->unique()->sort()->values();
            if ($campuses->isEmpty()) {
                $campuses = collect(['Main Campus', 'Branch Campus 1', 'Branch Campus 2']);
            }
        }
        if ($classes->isEmpty()) {
            $classesFromModel = ClassModel::distinct()->pluck('class_name');
            $classes = $classesFromModel->sort()->values();
            if ($classes->isEmpty()) {
                $classes = collect(['Nursery', 'KG', '1st', '2nd', '3rd', '4th', '5th', '6th', '7th', '8th', '9th', '10th', '11th', '12th']);
            }
        }
        if ($sections->isEmpty()) {
            $sectionsFromModel = Section::whereNotNull('name')->distinct()->pluck('name');
            $sections = $sectionsFromModel->sort()->values();
            if ($sections->isEmpty()) {
                $sections = collect(['A', 'B', 'C', 'D', 'E']);
            }
        }
        
        // Only query if at least one filter is applied
        if ($request->filled('filter_campus') || $request->filled('filter_class') || $request->filled('filter_section')) {
            $query = Timetable::query();
            
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
            
            $timetables = $query->orderBy('day')->orderBy('starting_time')->paginate($perPage)->withQueryString();
        } else {
            // Return empty paginator when no filters are applied
            $timetables = new \Illuminate\Pagination\LengthAwarePaginator(
                collect(),
                0,
                10,
                1,
                ['path' => $request->url(), 'query' => $request->query()]
            );
        }
        
        return view('timetable.manage', compact('timetables', 'campuses', 'classes', 'sections'));
    }

    /**
     * Store a newly created timetable.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'campus' => ['required', 'string', 'max:255'],
            'class' => ['required', 'string', 'max:255'],
            'section' => ['required', 'string', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'day' => ['required', 'string', 'max:255'],
            'starting_time' => ['required', 'string'],
            'ending_time' => ['required', 'string'],
        ]);

        Timetable::create($validated);

        return redirect()
            ->route('timetable.add')
            ->with('success', 'Timetable created successfully!');
    }
}
