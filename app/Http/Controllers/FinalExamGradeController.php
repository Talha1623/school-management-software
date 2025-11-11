<?php

namespace App\Http\Controllers;

use App\Models\FinalExamGrade;
use App\Models\ClassModel;
use App\Models\Section;
use App\Models\Exam;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FinalExamGradeController extends Controller
{
    /**
     * Display a listing of final exam grades.
     */
    public function index(Request $request): View
    {
        $query = FinalExamGrade::query();
        
        // Search functionality
        if ($request->filled('search')) {
            $search = trim($request->search);
            if (!empty($search)) {
                $searchLower = strtolower($search);
                $query->where(function($q) use ($search, $searchLower) {
                    $q->whereRaw('LOWER(name) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(campus) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(session) LIKE ?', ["%{$searchLower}%"]);
                });
            }
        }
        
        $perPage = $request->get('per_page', 10);
        $perPage = in_array($perPage, [10, 25, 50, 100]) ? $perPage : 10;
        
        $grades = $query->orderBy('from_percentage', 'desc')->paginate($perPage)->withQueryString();

        // Get campuses for dropdown
        $campusesFromClasses = ClassModel::whereNotNull('campus')->distinct()->pluck('campus');
        $campusesFromSections = Section::whereNotNull('campus')->distinct()->pluck('campus');
        $campuses = $campusesFromClasses->merge($campusesFromSections)->unique()->sort()->values();
        
        if ($campuses->isEmpty()) {
            $campuses = collect(['Main Campus', 'Branch Campus 1', 'Branch Campus 2']);
        }

        // Get sessions
        $sessions = Exam::whereNotNull('session')->distinct()->pluck('session')->sort()->values();
        
        if ($sessions->isEmpty()) {
            $sessions = collect(['2024-2025', '2025-2026', '2026-2027']);
        }
        
        return view('exam.grades.final', compact('grades', 'campuses', 'sessions'));
    }

    /**
     * Store a newly created final exam grade.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'campus' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'from_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'to_percentage' => ['required', 'numeric', 'min:0', 'max:100', 'gte:from_percentage'],
            'session' => ['required', 'string', 'max:255'],
        ]);

        FinalExamGrade::create($validated);

        return redirect()
            ->route('exam.grades.final')
            ->with('success', 'Final exam grade created successfully!');
    }

    /**
     * Update the specified final exam grade.
     */
    public function update(Request $request, FinalExamGrade $finalExamGrade): RedirectResponse
    {
        $validated = $request->validate([
            'campus' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'from_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'to_percentage' => ['required', 'numeric', 'min:0', 'max:100', 'gte:from_percentage'],
            'session' => ['required', 'string', 'max:255'],
        ]);

        $finalExamGrade->update($validated);

        return redirect()
            ->route('exam.grades.final')
            ->with('success', 'Final exam grade updated successfully!');
    }

    /**
     * Remove the specified final exam grade.
     */
    public function destroy(FinalExamGrade $finalExamGrade): RedirectResponse
    {
        $finalExamGrade->delete();

        return redirect()
            ->route('exam.grades.final')
            ->with('success', 'Final exam grade deleted successfully!');
    }

    /**
     * Export final exam grades to Excel, CSV, or PDF
     */
    public function export(Request $request, string $format)
    {
        $query = FinalExamGrade::query();
        
        // Apply search filter if present
        if ($request->has('search') && $request->search) {
            $search = trim($request->search);
            if (!empty($search)) {
                $searchLower = strtolower($search);
                $query->where(function($q) use ($search, $searchLower) {
                    $q->whereRaw('LOWER(name) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(campus) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(session) LIKE ?', ["%{$searchLower}%"]);
                });
            }
        }
        
        $grades = $query->orderBy('from_percentage', 'desc')->get();
        
        switch ($format) {
            case 'excel':
                return $this->exportExcel($grades);
            case 'pdf':
                return $this->exportPDF($grades);
            default:
                return redirect()->route('exam.grades.final')
                    ->with('error', 'Invalid export format!');
        }
    }

    /**
     * Export to Excel (CSV format for Excel compatibility)
     */
    private function exportExcel($grades)
    {
        $filename = 'final_exam_grades_' . date('Y-m-d_His') . '.csv';
        
        $headers = [
            'Content-Type' => 'application/vnd.ms-excel',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($grades) {
            $file = fopen('php://output', 'w');
            
            // Add BOM for UTF-8
            fputs($file, "\xEF\xBB\xBF");
            
            // Headers
            fputcsv($file, ['#', 'Campus', 'Name', 'From %', 'To %', 'Session']);
            
            // Data
            foreach ($grades as $index => $grade) {
                fputcsv($file, [
                    $index + 1,
                    $grade->campus,
                    $grade->name,
                    $grade->from_percentage,
                    $grade->to_percentage,
                    $grade->session,
                ]);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export to PDF
     */
    private function exportPDF($grades)
    {
        $html = view('exam.grades.final-pdf', compact('grades'))->render();
        
        // Simple PDF generation (you can use DomPDF or similar package)
        return response($html)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="final_exam_grades_' . date('Y-m-d_His') . '.pdf"');
    }
}

