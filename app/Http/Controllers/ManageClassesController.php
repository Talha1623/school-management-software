<?php

namespace App\Http\Controllers;

use App\Models\ClassModel;
use App\Models\Campus;
use App\Models\Section;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ManageClassesController extends Controller
{
    /**
     * Display a listing of classes.
     */
    public function index(Request $request): View
    {
        $query = ClassModel::query();
        
        // Search functionality
        if ($request->filled('search')) {
            $search = trim($request->search);
            if (!empty($search)) {
                $searchLower = strtolower($search);
                $query->where(function($q) use ($search, $searchLower) {
                    $q->whereRaw('LOWER(campus) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(class_name) LIKE ?', ["%{$searchLower}%"])
                      ->orWhere('numeric_no', 'like', "%{$search}%");
                });
            }
        }
        
        $perPage = $request->get('per_page', 10);
        $perPage = in_array($perPage, [10, 25, 50, 100]) ? $perPage : 10;
        
        $classes = $query->orderBy('numeric_no')->paginate($perPage)->withQueryString();
        
        // Load sections for each class
        foreach ($classes as $class) {
            $sections = Section::where('class', $class->class_name)
                ->orderBy('name', 'asc')
                ->pluck('name')
                ->toArray();
            $class->sections = $sections;
        }
        
        // Get campuses for dropdown
        $campuses = Campus::orderBy('campus_name', 'asc')->get();
        
        // If no campuses found, get from classes
        if ($campuses->isEmpty()) {
            $campusesFromClasses = ClassModel::whereNotNull('campus')
                ->distinct()
                ->pluck('campus')
                ->sort()
                ->values();
            
            // Convert to collection of objects with campus_name property
            $campuses = collect();
            foreach ($campusesFromClasses as $campusName) {
                $campuses->push((object)['campus_name' => $campusName]);
            }
        }
        
        return view('classes.manage-classes', compact('classes', 'campuses'));
    }

    /**
     * Store a newly created class.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'campus' => ['required', 'string', 'max:255'],
            'class_name' => ['required', 'string', 'max:255'],
            'numeric_no' => ['required', 'integer', 'min:1'],
        ]);

        ClassModel::create($validated);

        return redirect()
            ->route('classes.manage-classes')
            ->with('success', 'Class created successfully!');
    }

    /**
     * Update the specified class.
     */
    public function update(Request $request, ClassModel $class_model): RedirectResponse
    {
        $validated = $request->validate([
            'campus' => ['required', 'string', 'max:255'],
            'class_name' => ['required', 'string', 'max:255'],
            'numeric_no' => ['required', 'integer', 'min:1'],
        ]);

        $class_model->update($validated);

        return redirect()
            ->route('classes.manage-classes')
            ->with('success', 'Class updated successfully!');
    }

    /**
     * Remove the specified class.
     */
    public function destroy(ClassModel $class_model): RedirectResponse
    {
        // Check if there are any students in this class
        $studentsCount = Student::whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class_model->class_name))])
            ->count();

        if ($studentsCount > 0) {
            return redirect()
                ->route('classes.manage-classes')
                ->with('error', "Cannot delete class '{$class_model->class_name}' because it has {$studentsCount} student(s) enrolled. Please transfer all students to another class first.");
        }

        // Clear teacher field from all sections of this class before deleting
        // This ensures that when class is re-added, sections won't have teachers automatically assigned
        Section::whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class_model->class_name))])
            ->update(['teacher' => null]);

        $class_model->delete();

        return redirect()
            ->route('classes.manage-classes')
            ->with('success', 'Class deleted successfully!');
    }

    /**
     * Export classes to Excel, CSV, or PDF
     */
    public function export(Request $request, string $format)
    {
        $query = ClassModel::query();
        
        // Apply search filter if present
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('campus', 'like', "%{$search}%")
                  ->orWhere('class_name', 'like', "%{$search}%")
                  ->orWhere('numeric_no', 'like', "%{$search}%");
            });
        }
        
        $classes = $query->orderBy('numeric_no')->get();
        
        switch ($format) {
            case 'excel':
                return $this->exportExcel($classes);
            case 'csv':
                return $this->exportCSV($classes);
            case 'pdf':
                return $this->exportPDF($classes);
            default:
                return redirect()->route('classes.manage-classes')
                    ->with('error', 'Invalid export format!');
        }
    }

    /**
     * Export to Excel (CSV format for Excel compatibility)
     */
    private function exportExcel($classes)
    {
        $filename = 'classes_' . date('Y-m-d_His') . '.csv';
        
        $headers = [
            'Content-Type' => 'application/vnd.ms-excel',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($classes) {
            $file = fopen('php://output', 'w');
            
            // Add BOM for UTF-8
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            fputcsv($file, ['ID', 'Campus', 'Class Name', 'Numeric No', 'Created At']);
            
            foreach ($classes as $class) {
                fputcsv($file, [
                    $class->id,
                    $class->campus,
                    $class->class_name,
                    $class->numeric_no,
                    $class->created_at->format('Y-m-d H:i:s'),
                ]);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export to CSV
     */
    private function exportCSV($classes)
    {
        $filename = 'classes_' . date('Y-m-d_His') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($classes) {
            $file = fopen('php://output', 'w');
            
            fputcsv($file, ['ID', 'Campus', 'Class Name', 'Numeric No', 'Created At']);
            
            foreach ($classes as $class) {
                fputcsv($file, [
                    $class->id,
                    $class->campus,
                    $class->class_name,
                    $class->numeric_no,
                    $class->created_at->format('Y-m-d H:i:s'),
                ]);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export to PDF
     */
    private function exportPDF($classes)
    {
        $html = view('classes.manage-classes-pdf', compact('classes'))->render();
        
        return response($html)
            ->header('Content-Type', 'text/html');
    }
}

