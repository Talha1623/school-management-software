<?php

namespace App\Http\Controllers;

use App\Models\BehaviorCategory;
use App\Models\ClassModel;
use App\Models\Section;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BehaviorCategoryController extends Controller
{
    /**
     * Display a listing of behavior categories.
     */
    public function index(Request $request): View
    {
        $query = BehaviorCategory::query();
        
        // Search functionality
        if ($request->filled('search')) {
            $search = trim($request->search);
            if (!empty($search)) {
                $searchLower = strtolower($search);
                $query->where(function($q) use ($search, $searchLower) {
                    $q->whereRaw('LOWER(category_name) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(campus) LIKE ?', ["%{$searchLower}%"]);
                });
            }
        }
        
        $perPage = $request->get('per_page', 10);
        $perPage = in_array($perPage, [10, 25, 50, 100]) ? $perPage : 10;
        
        $categories = $query->orderBy('category_name')->paginate($perPage)->withQueryString();
        
        // Get campuses for dropdown
        $campusesFromClasses = ClassModel::whereNotNull('campus')->distinct()->pluck('campus');
        $campusesFromSections = Section::whereNotNull('campus')->distinct()->pluck('campus');
        $campuses = $campusesFromClasses->merge($campusesFromSections)->unique()->sort()->values();
        
        if ($campuses->isEmpty()) {
            $campuses = collect(['Main Campus', 'Branch Campus 1', 'Branch Campus 2']);
        }
        
        return view('student-behavior.categories', compact('categories', 'campuses'));
    }

    /**
     * Store a newly created behavior category.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'category_name' => ['required', 'string', 'max:255'],
            'campus' => ['required', 'string', 'max:255'],
        ]);

        BehaviorCategory::create($validated);

        return redirect()
            ->route('student-behavior.categories')
            ->with('success', 'Behavior category created successfully!');
    }

    /**
     * Update the specified behavior category.
     */
    public function update(Request $request, BehaviorCategory $behaviorCategory): RedirectResponse
    {
        $validated = $request->validate([
            'category_name' => ['required', 'string', 'max:255'],
            'campus' => ['required', 'string', 'max:255'],
        ]);

        $behaviorCategory->update($validated);

        return redirect()
            ->route('student-behavior.categories')
            ->with('success', 'Behavior category updated successfully!');
    }

    /**
     * Remove the specified behavior category.
     */
    public function destroy(BehaviorCategory $behaviorCategory): RedirectResponse
    {
        $behaviorCategory->delete();

        return redirect()
            ->route('student-behavior.categories')
            ->with('success', 'Behavior category deleted successfully!');
    }

    /**
     * Export behavior categories to Excel, CSV, or PDF
     */
    public function export(Request $request, string $format)
    {
        $query = BehaviorCategory::query();
        
        // Apply search filter if present
        if ($request->has('search') && $request->search) {
            $search = trim($request->search);
            if (!empty($search)) {
                $searchLower = strtolower($search);
                $query->where(function($q) use ($search, $searchLower) {
                    $q->whereRaw('LOWER(category_name) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(campus) LIKE ?', ["%{$searchLower}%"]);
                });
            }
        }
        
        $categories = $query->orderBy('category_name')->get();
        
        switch ($format) {
            case 'excel':
                return $this->exportExcel($categories);
            case 'csv':
                return $this->exportCSV($categories);
            case 'pdf':
                return $this->exportPDF($categories);
            default:
                return redirect()->route('student-behavior.categories')
                    ->with('error', 'Invalid export format!');
        }
    }

    /**
     * Export to Excel (CSV format for Excel compatibility)
     */
    private function exportExcel($categories)
    {
        $filename = 'behavior_categories_' . date('Y-m-d_His') . '.csv';
        
        $headers = [
            'Content-Type' => 'application/vnd.ms-excel',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($categories) {
            $file = fopen('php://output', 'w');
            
            // Add BOM for UTF-8
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            fputcsv($file, ['ID', 'Category Name', 'Campus', 'Created At']);
            
            foreach ($categories as $category) {
                fputcsv($file, [
                    $category->id,
                    $category->category_name,
                    $category->campus,
                    $category->created_at->format('Y-m-d H:i:s'),
                ]);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export to CSV
     */
    private function exportCSV($categories)
    {
        $filename = 'behavior_categories_' . date('Y-m-d_His') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($categories) {
            $file = fopen('php://output', 'w');
            
            fputcsv($file, ['ID', 'Category Name', 'Campus', 'Created At']);
            
            foreach ($categories as $category) {
                fputcsv($file, [
                    $category->id,
                    $category->category_name,
                    $category->campus,
                    $category->created_at->format('Y-m-d H:i:s'),
                ]);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export to PDF
     */
    private function exportPDF($categories)
    {
        $html = view('student-behavior.categories-pdf', compact('categories'))->render();
        
        return response($html)
            ->header('Content-Type', 'text/html');
    }
}

