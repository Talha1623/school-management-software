<?php

namespace App\Http\Controllers;

use App\Models\Test;
use App\Models\ClassModel;
use App\Models\Section;
use App\Models\Subject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TestController extends Controller
{
    /**
     * Display a listing of tests.
     */
    public function index(Request $request): View
    {
        $query = Test::query();
        
        // Search functionality
        if ($request->filled('search')) {
            $search = trim($request->search);
            if (!empty($search)) {
                $searchLower = strtolower($search);
                $query->where(function($q) use ($search, $searchLower) {
                    $q->whereRaw('LOWER(test_name) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(for_class) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(section) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(subject) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(test_type) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(campus) LIKE ?', ["%{$searchLower}%"]);
                });
            }
        }
        
        $perPage = $request->get('per_page', 10);
        $perPage = in_array($perPage, [10, 25, 50, 100]) ? $perPage : 10;
        
        $tests = $query->orderBy('date', 'desc')->paginate($perPage)->withQueryString();

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

        // Get sections
        $sections = Section::whereNotNull('name')->distinct()->pluck('name')->sort()->values();
        
        if ($sections->isEmpty()) {
            $sections = collect(['A', 'B', 'C', 'D']);
        }

        // Get subjects
        $subjects = Subject::whereNotNull('subject_name')->distinct()->pluck('subject_name')->sort()->values();
        
        if ($subjects->isEmpty()) {
            $subjects = collect(['Mathematics', 'English', 'Science', 'Urdu', 'Islamiat', 'Social Studies']);
        }

        // Get test types
        $testTypes = Test::whereNotNull('test_type')->distinct()->pluck('test_type')->sort()->values();
        
        if ($testTypes->isEmpty()) {
            $testTypes = collect(['Quiz', 'Mid Term', 'Final Term', 'Assignment', 'Project', 'Oral Test']);
        }

        // Get sessions
        $sessions = Test::whereNotNull('session')->distinct()->pluck('session')->sort()->values();
        
        if ($sessions->isEmpty()) {
            $sessions = collect(['2024-2025', '2025-2026', '2026-2027']);
        }
        
        return view('test.list', compact('tests', 'campuses', 'classes', 'sections', 'subjects', 'testTypes', 'sessions'));
    }

    /**
     * Store a newly created test.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'campus' => ['required', 'string', 'max:255'],
            'test_name' => ['required', 'string', 'max:255'],
            'for_class' => ['required', 'string', 'max:255'],
            'section' => ['required', 'string', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'test_type' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'date' => ['required', 'date'],
            'session' => ['required', 'string', 'max:255'],
        ]);

        Test::create($validated);

        return redirect()
            ->route('test.list')
            ->with('success', 'Test created successfully!');
    }

    /**
     * Update the specified test.
     */
    public function update(Request $request, Test $test): RedirectResponse
    {
        $validated = $request->validate([
            'campus' => ['required', 'string', 'max:255'],
            'test_name' => ['required', 'string', 'max:255'],
            'for_class' => ['required', 'string', 'max:255'],
            'section' => ['required', 'string', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'test_type' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'date' => ['required', 'date'],
            'session' => ['required', 'string', 'max:255'],
        ]);

        $test->update($validated);

        return redirect()
            ->route('test.list')
            ->with('success', 'Test updated successfully!');
    }

    /**
     * Remove the specified test.
     */
    public function destroy(Test $test): RedirectResponse
    {
        $test->delete();

        return redirect()
            ->route('test.list')
            ->with('success', 'Test deleted successfully!');
    }

    /**
     * Export tests to Excel, CSV, or PDF
     */
    public function export(Request $request, string $format)
    {
        $query = Test::query();
        
        // Apply search filter if present
        if ($request->has('search') && $request->search) {
            $search = trim($request->search);
            if (!empty($search)) {
                $searchLower = strtolower($search);
                $query->where(function($q) use ($search, $searchLower) {
                    $q->whereRaw('LOWER(test_name) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(for_class) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(section) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(subject) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(test_type) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(campus) LIKE ?', ["%{$searchLower}%"]);
                });
            }
        }
        
        $tests = $query->orderBy('date', 'desc')->get();
        
        switch ($format) {
            case 'excel':
                return $this->exportExcel($tests);
            case 'csv':
                return $this->exportCSV($tests);
            case 'pdf':
                return $this->exportPDF($tests);
            default:
                return redirect()->route('test.list')
                    ->with('error', 'Invalid export format!');
        }
    }

    /**
     * Export to Excel (CSV format for Excel compatibility)
     */
    private function exportExcel($tests)
    {
        $filename = 'tests_' . date('Y-m-d_His') . '.csv';
        
        $headers = [
            'Content-Type' => 'application/vnd.ms-excel',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($tests) {
            $file = fopen('php://output', 'w');
            
            // Add BOM for UTF-8
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            fputcsv($file, ['ID', 'Campus', 'Test Name', 'For Class', 'Section', 'Subject', 'Test Type', 'Description', 'Date', 'Session', 'Created At']);
            
            foreach ($tests as $test) {
                fputcsv($file, [
                    $test->id,
                    $test->campus,
                    $test->test_name,
                    $test->for_class,
                    $test->section,
                    $test->subject,
                    $test->test_type,
                    $test->description ?? '',
                    $test->date->format('Y-m-d'),
                    $test->session,
                    $test->created_at->format('Y-m-d H:i:s'),
                ]);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export to CSV
     */
    private function exportCSV($tests)
    {
        $filename = 'tests_' . date('Y-m-d_His') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($tests) {
            $file = fopen('php://output', 'w');
            
            fputcsv($file, ['ID', 'Campus', 'Test Name', 'For Class', 'Section', 'Subject', 'Test Type', 'Description', 'Date', 'Session', 'Created At']);
            
            foreach ($tests as $test) {
                fputcsv($file, [
                    $test->id,
                    $test->campus,
                    $test->test_name,
                    $test->for_class,
                    $test->section,
                    $test->subject,
                    $test->test_type,
                    $test->description ?? '',
                    $test->date->format('Y-m-d'),
                    $test->session,
                    $test->created_at->format('Y-m-d H:i:s'),
                ]);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export to PDF
     */
    private function exportPDF($tests)
    {
        $html = view('test.list-pdf', compact('tests'))->render();
        
        return response($html)
            ->header('Content-Type', 'text/html');
    }
}

