<?php

namespace App\Http\Controllers;

use App\Models\OnlineClass;
use App\Models\ClassModel;
use App\Models\Section;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OnlineClassesController extends Controller
{
    /**
     * Display a listing of online classes.
     */
    public function index(Request $request): View
    {
        $query = OnlineClass::query();
        
        // Search functionality
        if ($request->filled('search')) {
            $search = trim($request->search);
            if (!empty($search)) {
                $searchLower = strtolower($search);
                $query->where(function($q) use ($search, $searchLower) {
                    $q->whereRaw('LOWER(campus) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(class) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(section) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(class_topic) LIKE ?', ["%{$searchLower}%"]);
                });
            }
        }
        
        $perPage = $request->get('per_page', 10);
        $perPage = in_array($perPage, [10, 25, 50, 100]) ? $perPage : 10;
        
        $onlineClasses = $query->orderBy('start_date', 'desc')->paginate($perPage)->withQueryString();
        
        // Get unique values for dropdowns
        $campuses = ClassModel::distinct()->pluck('campus')->filter()->sort()->values()->toArray();
        if (empty($campuses)) {
            $campuses = ['Main Campus'];
        }
        
        $classes = ClassModel::distinct()->pluck('class_name')->sort()->values()->toArray();
        if (empty($classes)) {
            $classes = ['Nursery', 'KG', '1st', '2nd', '3rd', '4th', '5th', '6th', '7th', '8th', '9th', '10th', '11th', '12th'];
        }
        
        $sections = Section::distinct()->pluck('name')->filter()->sort()->values()->toArray();
        if (empty($sections)) {
            $sections = ['A', 'B', 'C'];
        }
        
        return view('online-classes', compact('onlineClasses', 'campuses', 'classes', 'sections'));
    }

    /**
     * Store a newly created online class.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'campus' => ['required', 'string', 'max:255'],
            'class' => ['required', 'string', 'max:255'],
            'section' => ['required', 'string', 'max:255'],
            'class_topic' => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'start_time' => ['nullable', 'string'],
            'timing' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:4'],
            'created_by' => ['nullable', 'string', 'max:255'],
        ]);

        // Set created_by if not provided
        if (empty($validated['created_by'])) {
            $validated['created_by'] = auth()->user()->name ?? 'Admin';
        }

        OnlineClass::create($validated);

        return redirect()
            ->route('online-classes')
            ->with('success', 'Online class created successfully!');
    }

    /**
     * Update the specified online class.
     */
    public function update(Request $request, OnlineClass $online_class): RedirectResponse
    {
        $validated = $request->validate([
            'campus' => ['required', 'string', 'max:255'],
            'class' => ['required', 'string', 'max:255'],
            'section' => ['required', 'string', 'max:255'],
            'class_topic' => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'start_time' => ['nullable', 'string'],
            'timing' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:4'],
            'created_by' => ['nullable', 'string', 'max:255'],
        ]);

        $online_class->update($validated);

        return redirect()
            ->route('online-classes')
            ->with('success', 'Online class updated successfully!');
    }

    /**
     * Remove the specified online class.
     */
    public function destroy(OnlineClass $online_class): RedirectResponse
    {
        $online_class->delete();

        return redirect()
            ->route('online-classes')
            ->with('success', 'Online class deleted successfully!');
    }

    /**
     * Export online classes to Excel, CSV, or PDF
     */
    public function export(Request $request, string $format)
    {
        $query = OnlineClass::query();
        
        // Apply search filter if present
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('campus', 'like', "%{$search}%")
                  ->orWhere('class', 'like', "%{$search}%")
                  ->orWhere('section', 'like', "%{$search}%")
                  ->orWhere('class_topic', 'like', "%{$search}%");
            });
        }
        
        $onlineClasses = $query->orderBy('start_date', 'desc')->get();
        
        switch ($format) {
            case 'excel':
                return $this->exportExcel($onlineClasses);
            case 'csv':
                return $this->exportCSV($onlineClasses);
            case 'pdf':
                return $this->exportPDF($onlineClasses);
            default:
                return redirect()->route('online-classes')
                    ->with('error', 'Invalid export format!');
        }
    }

    /**
     * Export to Excel (CSV format for Excel compatibility)
     */
    private function exportExcel($onlineClasses)
    {
        $filename = 'online_classes_' . date('Y-m-d_His') . '.csv';
        
        $headers = [
            'Content-Type' => 'application/vnd.ms-excel',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($onlineClasses) {
            $file = fopen('php://output', 'w');
            
            // Add BOM for UTF-8
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            fputcsv($file, ['ID', 'Campus', 'Class', 'Section', 'Class Topic', 'Start Date', 'Timing', 'Password', 'Created At']);
            
            foreach ($onlineClasses as $class) {
                fputcsv($file, [
                    $class->id,
                    $class->campus,
                    $class->class,
                    $class->section,
                    $class->class_topic,
                    $class->start_date->format('Y-m-d'),
                    $class->timing,
                    $class->password,
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
    private function exportCSV($onlineClasses)
    {
        $filename = 'online_classes_' . date('Y-m-d_His') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($onlineClasses) {
            $file = fopen('php://output', 'w');
            
            fputcsv($file, ['ID', 'Campus', 'Class', 'Section', 'Class Topic', 'Start Date', 'Timing', 'Password', 'Created At']);
            
            foreach ($onlineClasses as $class) {
                fputcsv($file, [
                    $class->id,
                    $class->campus,
                    $class->class,
                    $class->section,
                    $class->class_topic,
                    $class->start_date->format('Y-m-d'),
                    $class->timing,
                    $class->password,
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
    private function exportPDF($onlineClasses)
    {
        $html = view('online-classes-pdf', compact('onlineClasses'))->render();
        
        return response($html)
            ->header('Content-Type', 'text/html');
    }
}
