<?php

namespace App\Http\Controllers;

use App\Models\Section;
use App\Models\ClassModel;
use App\Models\Staff;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ManageSectionController extends Controller
{
    /**
     * Display a listing of sections.
     */
    public function index(Request $request): View
    {
        $query = Section::query();
        
        // Filter functionality
        if ($request->filled('filter_campus')) {
            $query->where('campus', $request->filter_campus);
        }
        
        if ($request->filled('filter_class')) {
            $query->where('class', $request->filter_class);
        }
        
        if ($request->filled('filter_session')) {
            $query->where('session', $request->filter_session);
        }
        
        // Search functionality
        if ($request->filled('search')) {
            $search = trim($request->search);
            if (!empty($search)) {
                $searchLower = strtolower($search);
                $query->where(function($q) use ($search, $searchLower) {
                    $q->whereRaw('LOWER(campus) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(name) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(nick_name) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(class) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(teacher) LIKE ?', ["%{$searchLower}%"]);
                });
            }
        }
        
        $perPage = $request->get('per_page', 10);
        $perPage = in_array($perPage, [10, 25, 50, 100]) ? $perPage : 10;
        
        $sections = $query->latest()->paginate($perPage)->withQueryString();
        
        // Get unique values for dropdowns
        $campuses = Section::whereNotNull('campus')->distinct()->pluck('campus')->sort()->values();
        $classes = ClassModel::distinct()->pluck('class_name')->sort()->values();
        $allSessions = Section::whereNotNull('session')->distinct()->pluck('session')->sort()->values();
        $teachers = Staff::whereNotNull('name')->orderBy('name')->pluck('name', 'id');
        
        // If no data exists, provide defaults
        if ($campuses->isEmpty()) {
            $campuses = collect(['Main Campus', 'Branch Campus 1', 'Branch Campus 2']);
        }
        if ($classes->isEmpty()) {
            $classes = collect(['Nursery', 'KG', '1st', '2nd', '3rd', '4th', '5th', '6th', '7th', '8th', '9th', '10th', '11th', '12th']);
        }
        if ($allSessions->isEmpty()) {
            $allSessions = collect([date('Y') . '-' . (date('Y') + 1), (date('Y') - 1) . '-' . date('Y')]);
        }
        
        return view('classes.manage-section', compact('sections', 'campuses', 'classes', 'allSessions', 'teachers'));
    }

    /**
     * Store a newly created section.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'campus' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'nick_name' => ['nullable', 'string', 'max:255'],
            'class' => ['required', 'string', 'max:255'],
            'teacher' => ['nullable', 'string', 'max:255'],
            'session' => ['nullable', 'string', 'max:255'],
        ]);

        Section::create($validated);

        return redirect()
            ->route('classes.manage-section')
            ->with('success', 'Section created successfully!');
    }

    /**
     * Update the specified section.
     */
    public function update(Request $request, Section $section): RedirectResponse
    {
        $validated = $request->validate([
            'campus' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'nick_name' => ['nullable', 'string', 'max:255'],
            'class' => ['required', 'string', 'max:255'],
            'teacher' => ['nullable', 'string', 'max:255'],
            'session' => ['nullable', 'string', 'max:255'],
        ]);

        $section->update($validated);

        return redirect()
            ->route('classes.manage-section')
            ->with('success', 'Section updated successfully!');
    }

    /**
     * Remove the specified section.
     */
    public function destroy(Section $section): RedirectResponse
    {
        $section->delete();

        return redirect()
            ->route('classes.manage-section')
            ->with('success', 'Section deleted successfully!');
    }

    /**
     * Export sections to Excel, CSV, or PDF
     */
    public function export(Request $request, string $format)
    {
        $query = Section::query();
        
        // Apply filters if present
        if ($request->has('filter_campus') && $request->filter_campus) {
            $query->where('campus', $request->filter_campus);
        }
        if ($request->has('filter_class') && $request->filter_class) {
            $query->where('class', $request->filter_class);
        }
        if ($request->has('filter_session') && $request->filter_session) {
            $query->where('session', $request->filter_session);
        }
        
        // Apply search filter if present
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('campus', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%")
                  ->orWhere('nick_name', 'like', "%{$search}%")
                  ->orWhere('class', 'like', "%{$search}%")
                  ->orWhere('teacher', 'like', "%{$search}%");
            });
        }
        
        $sections = $query->latest()->get();
        
        switch ($format) {
            case 'excel':
                return $this->exportExcel($sections);
            case 'csv':
                return $this->exportCSV($sections);
            case 'pdf':
                return $this->exportPDF($sections);
            default:
                return redirect()->route('classes.manage-section')
                    ->with('error', 'Invalid export format!');
        }
    }

    /**
     * Export to Excel (CSV format for Excel compatibility)
     */
    private function exportExcel($sections)
    {
        $filename = 'sections_' . date('Y-m-d_His') . '.csv';
        
        $headers = [
            'Content-Type' => 'application/vnd.ms-excel',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($sections) {
            $file = fopen('php://output', 'w');
            
            // Add BOM for UTF-8
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            fputcsv($file, ['ID', 'Campus', 'Name', 'Nick Name', 'Class', 'Teacher', 'Session', 'Created At']);
            
            foreach ($sections as $section) {
                fputcsv($file, [
                    $section->id,
                    $section->campus,
                    $section->name,
                    $section->nick_name ?? 'N/A',
                    $section->class,
                    $section->teacher ?? 'N/A',
                    $section->session ?? 'N/A',
                    $section->created_at->format('Y-m-d H:i:s'),
                ]);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export to CSV
     */
    private function exportCSV($sections)
    {
        $filename = 'sections_' . date('Y-m-d_His') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($sections) {
            $file = fopen('php://output', 'w');
            
            fputcsv($file, ['ID', 'Campus', 'Name', 'Nick Name', 'Class', 'Teacher', 'Session', 'Created At']);
            
            foreach ($sections as $section) {
                fputcsv($file, [
                    $section->id,
                    $section->campus,
                    $section->name,
                    $section->nick_name ?? 'N/A',
                    $section->class,
                    $section->teacher ?? 'N/A',
                    $section->session ?? 'N/A',
                    $section->created_at->format('Y-m-d H:i:s'),
                ]);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export to PDF
     */
    private function exportPDF($sections)
    {
        $html = view('classes.manage-section-pdf', compact('sections'))->render();
        
        return response($html)
            ->header('Content-Type', 'text/html');
    }
}

