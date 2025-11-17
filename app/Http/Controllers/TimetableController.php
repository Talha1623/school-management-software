<?php

namespace App\Http\Controllers;

use App\Models\Timetable;
use App\Models\ClassModel;
use App\Models\Section;
use App\Models\Campus;
use App\Models\Subject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TimetableController extends Controller
{
    /**
     * Display the add timetable form.
     */
    public function add(): View
    {
        // Get campuses from Campus model
        $campuses = Campus::orderBy('campus_name', 'asc')->get();
        
        // If no campuses found, get from classes or sections
        if ($campuses->isEmpty()) {
            $campusesFromClasses = ClassModel::whereNotNull('campus')
                ->distinct()
                ->pluck('campus')
                ->sort()
                ->values();
            
            $campusesFromSections = Section::whereNotNull('campus')
                ->distinct()
                ->pluck('campus')
                ->sort()
                ->values();
            
            $allCampuses = $campusesFromClasses->merge($campusesFromSections)->unique()->sort()->values();
            
            // Convert to collection of objects with campus_name property
            $campuses = collect();
            foreach ($allCampuses as $campusName) {
                $campuses->push((object)['campus_name' => $campusName]);
            }
        }
        
        // Get classes from ClassModel
        $classes = ClassModel::orderBy('class_name', 'asc')->get();
        
        // If no classes found, provide empty collection
        if ($classes->isEmpty()) {
            $classes = collect();
        }
        
        // Get sections from Section model
        $sections = Section::whereNotNull('name')
            ->distinct()
            ->orderBy('name', 'asc')
            ->pluck('name')
            ->sort()
            ->values();
        
        // Get subjects from Subject model
        $subjects = Subject::whereNotNull('subject_name')
            ->distinct()
            ->orderBy('subject_name', 'asc')
            ->pluck('subject_name')
            ->sort()
            ->values();
        
        // Days of the week
        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        
        return view('timetable.add', compact('campuses', 'classes', 'sections', 'subjects', 'days'));
    }

    /**
     * Display a listing of timetables with filters.
     */
    public function index(Request $request): View
    {
        // Get campuses from Campus model
        $campuses = Campus::orderBy('campus_name', 'asc')->get();
        
        // If no campuses found, get from classes, sections, or timetables
        if ($campuses->isEmpty()) {
            $campusesFromClasses = ClassModel::whereNotNull('campus')
                ->distinct()
                ->pluck('campus')
                ->sort()
                ->values();
            
            $campusesFromSections = Section::whereNotNull('campus')
                ->distinct()
                ->pluck('campus')
                ->sort()
                ->values();
            
            $campusesFromTimetables = Timetable::whereNotNull('campus')
                ->distinct()
                ->pluck('campus')
                ->sort()
                ->values();
            
            $allCampuses = $campusesFromClasses->merge($campusesFromSections)->merge($campusesFromTimetables)->unique()->sort()->values();
            
            // Convert to collection of objects with campus_name property
            $campuses = collect();
            foreach ($allCampuses as $campusName) {
                $campuses->push((object)['campus_name' => $campusName]);
            }
        }
        
        // Get classes from ClassModel
        $classes = ClassModel::orderBy('class_name', 'asc')->get();
        
        // If no classes found, get from timetables
        if ($classes->isEmpty()) {
            $classesFromTimetables = Timetable::whereNotNull('class')
                ->distinct()
                ->pluck('class')
                ->sort()
                ->values();
            
            // Convert to collection of objects with class_name property
            $classes = collect();
            foreach ($classesFromTimetables as $className) {
                $classes->push((object)['class_name' => $className]);
            }
        }
        
        // Get sections from Section model based on selected class
        $sections = collect();
        if ($request->filled('filter_class')) {
            $sections = Section::where('class', $request->filter_class)
                ->whereNotNull('name')
                ->distinct()
                ->orderBy('name', 'asc')
                ->pluck('name')
                ->sort()
                ->values();
        } else {
            // If no class selected, get all sections
            $sections = Section::whereNotNull('name')
                ->distinct()
                ->orderBy('name', 'asc')
                ->pluck('name')
                ->sort()
                ->values();
            
            // If no sections found, get from timetables
            if ($sections->isEmpty()) {
                $sectionsFromTimetables = Timetable::whereNotNull('section')
                    ->distinct()
                    ->pluck('section')
                    ->sort()
                    ->values();
                
                $sections = $sectionsFromTimetables;
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
        
        // Get subjects from Subject model
        $subjects = Subject::whereNotNull('subject_name')
            ->distinct()
            ->orderBy('subject_name', 'asc')
            ->pluck('subject_name')
            ->sort()
            ->values();
        
        // If no subjects found, get from timetables
        if ($subjects->isEmpty()) {
            $subjectsFromTimetables = Timetable::whereNotNull('subject')
                ->distinct()
                ->pluck('subject')
                ->sort()
                ->values();
            
            $subjects = $subjectsFromTimetables;
        }
        
        // Days of the week
        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        
        return view('timetable.manage', compact('timetables', 'campuses', 'classes', 'sections', 'subjects', 'days'));
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

    /**
     * Show the form for editing the specified timetable.
     */
    public function edit(Timetable $timetable): View
    {
        // Get campuses from Campus model
        $campuses = Campus::orderBy('campus_name', 'asc')->get();
        
        // If no campuses found, get from classes or sections
        if ($campuses->isEmpty()) {
            $campusesFromClasses = ClassModel::whereNotNull('campus')
                ->distinct()
                ->pluck('campus')
                ->sort()
                ->values();
            
            $campusesFromSections = Section::whereNotNull('campus')
                ->distinct()
                ->pluck('campus')
                ->sort()
                ->values();
            
            $allCampuses = $campusesFromClasses->merge($campusesFromSections)->unique()->sort()->values();
            
            // Convert to collection of objects with campus_name property
            $campuses = collect();
            foreach ($allCampuses as $campusName) {
                $campuses->push((object)['campus_name' => $campusName]);
            }
        }
        
        // Get classes from ClassModel
        $classes = ClassModel::orderBy('class_name', 'asc')->get();
        
        // If no classes found, provide empty collection
        if ($classes->isEmpty()) {
            $classes = collect();
        }
        
        // Get sections for the selected class
        $sections = Section::where('class', $timetable->class)
            ->whereNotNull('name')
            ->distinct()
            ->orderBy('name', 'asc')
            ->pluck('name')
            ->sort()
            ->values();
        
        // Get subjects from Subject model
        $subjects = Subject::whereNotNull('subject_name')
            ->distinct()
            ->orderBy('subject_name', 'asc')
            ->pluck('subject_name')
            ->sort()
            ->values();
        
        // Days of the week
        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        
        return view('timetable.edit', compact('timetable', 'campuses', 'classes', 'sections', 'subjects', 'days'));
    }

    /**
     * Update the specified timetable in storage.
     */
    public function update(Request $request, Timetable $timetable): RedirectResponse
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

        $timetable->update($validated);

        return redirect()
            ->route('timetable.manage')
            ->with('success', 'Timetable updated successfully!');
    }

    /**
     * Remove the specified timetable from storage.
     */
    public function destroy(Timetable $timetable): RedirectResponse
    {
        $timetable->delete();

        return redirect()
            ->route('timetable.manage')
            ->with('success', 'Timetable deleted successfully!');
    }

    /**
     * Export timetables to PDF
     */
    public function export(Request $request, string $format)
    {
        $query = Timetable::query();
        
        // Apply filters if present
        if ($request->filled('filter_campus')) {
            $query->where('campus', $request->filter_campus);
        }
        
        if ($request->filled('filter_class')) {
            $query->where('class', $request->filter_class);
        }
        
        if ($request->filled('filter_section')) {
            $query->where('section', $request->filter_section);
        }
        
        $timetables = $query->orderBy('day')->orderBy('starting_time')->get();
        
        switch ($format) {
            case 'pdf':
                return $this->exportPDF($timetables);
            default:
                return redirect()->route('timetable.manage')
                    ->with('error', 'Invalid export format!');
        }
    }

    /**
     * Get sections by class name (AJAX).
     */
    public function getSectionsByClass(Request $request)
    {
        $className = $request->get('class');
        
        if (!$className) {
            return response()->json(['sections' => []]);
        }
        
        $sections = Section::where('class', $className)
            ->whereNotNull('name')
            ->distinct()
            ->orderBy('name', 'asc')
            ->pluck('name')
            ->sort()
            ->values();
        
        return response()->json(['sections' => $sections]);
    }

    /**
     * Export to PDF
     */
    private function exportPDF($timetables)
    {
        $html = view('timetable-pdf', compact('timetables'))->render();
        
        return response($html)
            ->header('Content-Type', 'text/html');
    }
}
