<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use App\Models\ClassModel;
use App\Models\Section;
use App\Models\Campus;
use App\Models\Staff;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ManageSubjectsController extends Controller
{
    /**
     * Display a listing of subjects.
     */
    public function index(Request $request): View
    {
        // Get campuses from Campus model
        $campuses = Campus::orderBy('campus_name', 'asc')->get();
        
        // If no campuses found, get from sections or classes
        if ($campuses->isEmpty()) {
            $campusesFromSections = Section::whereNotNull('campus')
                ->distinct()
                ->pluck('campus')
                ->sort()
                ->values();
            
            $campusesFromClasses = ClassModel::whereNotNull('campus')
                ->distinct()
                ->pluck('campus')
                ->sort()
                ->values();
            
            $allCampuses = $campusesFromSections->merge($campusesFromClasses)->unique()->sort()->values();
            
            // Convert to collection of objects with campus_name property
            $campuses = collect();
            foreach ($allCampuses as $campusName) {
                $campuses->push((object)['campus_name' => $campusName]);
            }
        }
        
        // Get classes from ClassModel
        $classes = ClassModel::orderBy('class_name', 'asc')->get();
        
        // If no classes found, get from sections
        if ($classes->isEmpty()) {
            $classesFromSections = Section::whereNotNull('class')
                ->distinct()
                ->pluck('class')
                ->sort()
                ->values();
            
            // Convert to collection of objects with class_name property
            $classes = collect();
            foreach ($classesFromSections as $className) {
                $classes->push((object)['class_name' => $className]);
            }
        }
        
        // Get sections from Section model
        $sections = Section::whereNotNull('name')
            ->distinct()
            ->orderBy('name', 'asc')
            ->pluck('name')
            ->sort()
            ->values();
        
        // Get teachers from Staff model
        $teachers = Staff::whereNotNull('name')->orderBy('name')->pluck('name', 'id');
        
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
            
            // Apply search filter if present
            if ($request->filled('search')) {
                $search = trim($request->search);
                if (!empty($search)) {
                    $searchLower = strtolower($search);
                    $query->where(function($q) use ($search, $searchLower) {
                        $q->whereRaw('LOWER(campus) LIKE ?', ["%{$searchLower}%"])
                          ->orWhereRaw('LOWER(class) LIKE ?', ["%{$searchLower}%"])
                          ->orWhereRaw('LOWER(section) LIKE ?', ["%{$searchLower}%"])
                          ->orWhereRaw('LOWER(subject_name) LIKE ?', ["%{$searchLower}%"])
                          ->orWhereRaw('LOWER(teacher) LIKE ?', ["%{$searchLower}%"])
                          ->orWhereRaw('LOWER(session) LIKE ?', ["%{$searchLower}%"]);
                    });
                }
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
        
        return view('manage-subjects', compact('subjects', 'campuses', 'classes', 'sections', 'teachers'));
    }

    /**
     * Store a newly created subject.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'campus' => ['required', 'string', 'max:255'],
            'subject_name' => ['required', 'string', 'max:255'],
            'class' => ['required', 'string', 'max:255'],
            'section' => ['required', 'string', 'max:255'],
            'teacher' => ['nullable', 'string', 'max:255'],
            'session' => ['nullable', 'string', 'max:255'],
        ]);

        Subject::create($validated);

        return redirect()
            ->route('manage-subjects')
            ->with('success', 'Subject created successfully!');
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

        // Get sections for the selected class
        $sections = Section::where('class', $className)
            ->orderBy('name', 'asc')
            ->get(['id', 'name'])
            ->map(function($section) {
                return [
                    'id' => $section->id,
                    'name' => $section->name
                ];
            });

        return response()->json(['sections' => $sections]);
    }

    /**
     * Export subjects to Excel, CSV, or PDF
     */
    public function export(Request $request, string $format)
    {
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
        
        // Apply search filter if present
        if ($request->filled('search')) {
            $search = trim($request->search);
            if (!empty($search)) {
                $searchLower = strtolower($search);
                $query->where(function($q) use ($search, $searchLower) {
                    $q->whereRaw('LOWER(campus) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(class) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(section) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(subject_name) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(teacher) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(session) LIKE ?', ["%{$searchLower}%"]);
                });
            }
        }
        
        $subjects = $query->latest()->get();
        
        switch ($format) {
            case 'excel':
                return $this->exportExcel($subjects);
            case 'csv':
                return $this->exportCSV($subjects);
            case 'pdf':
                return $this->exportPDF($subjects);
            default:
                return redirect()->route('manage-subjects')
                    ->with('error', 'Invalid export format!');
        }
    }

    /**
     * Export to Excel (CSV format for Excel compatibility)
     */
    private function exportExcel($subjects)
    {
        $filename = 'subjects_' . date('Y-m-d_His') . '.csv';
        
        $headers = [
            'Content-Type' => 'application/vnd.ms-excel',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($subjects) {
            $file = fopen('php://output', 'w');
            
            // Add BOM for UTF-8
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            fputcsv($file, ['ID', 'Campus', 'Class', 'Section', 'Subject Name', 'Teacher', 'Session', 'Created At']);
            
            foreach ($subjects as $subject) {
                fputcsv($file, [
                    $subject->id,
                    $subject->campus,
                    $subject->class,
                    $subject->section,
                    $subject->subject_name,
                    $subject->teacher ?? 'N/A',
                    $subject->session ?? 'N/A',
                    $subject->created_at->format('Y-m-d H:i:s'),
                ]);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export to CSV
     */
    private function exportCSV($subjects)
    {
        $filename = 'subjects_' . date('Y-m-d_His') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($subjects) {
            $file = fopen('php://output', 'w');
            
            fputcsv($file, ['ID', 'Campus', 'Class', 'Section', 'Subject Name', 'Teacher', 'Session', 'Created At']);
            
            foreach ($subjects as $subject) {
                fputcsv($file, [
                    $subject->id,
                    $subject->campus,
                    $subject->class,
                    $subject->section,
                    $subject->subject_name,
                    $subject->teacher ?? 'N/A',
                    $subject->session ?? 'N/A',
                    $subject->created_at->format('Y-m-d H:i:s'),
                ]);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export to PDF
     */
    private function exportPDF($subjects)
    {
        $html = view('manage-subjects-pdf', compact('subjects'))->render();
        
        return response($html)
            ->header('Content-Type', 'text/html');
    }
}

