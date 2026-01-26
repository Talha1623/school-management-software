<?php

namespace App\Http\Controllers;

use App\Models\Section;
use App\Models\ClassModel;
use App\Models\Staff;
use App\Models\Campus;
use App\Models\Subject;
use App\Models\Student;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ManageSectionController extends Controller
{
    /**     
     * Display a listing of sections.
     */
    public function index(Request $request): View
    {
        // Clean up orphaned teachers (teachers that don't exist in staff table anymore)
        $this->cleanupOrphanedTeachers();
        
        $query = Section::query();
        
        // Filter out sections with deleted classes or null/empty class names
        $query->whereNotNull('class')
              ->where('class', '!=', '');
        
        // Get all existing class names from ClassModel
        $existingClassNames = ClassModel::whereNotNull('class_name')
            ->where('class_name', '!=', '')
            ->pluck('class_name')
            ->map(function($name) {
                return strtolower(trim($name));
            })
            ->unique()
            ->values()
            ->toArray();
        
        if (!empty($existingClassNames)) {
            // Filter sections to only show those whose class exists in ClassModel
            $query->where(function($q) use ($existingClassNames) {
                foreach ($existingClassNames as $className) {
                    $q->orWhereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($className))]);
                }
            });
        } else {
            // If no classes exist, show no sections
            $query->whereRaw('1 = 0');
        }
        
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
        
        // Get campuses from Campus model
        $campuses = Campus::orderBy('campus_name', 'asc')->get();
        
        // If no campuses found, get from sections
        if ($campuses->isEmpty()) {
            $campusesFromSections = Section::whereNotNull('campus')
                ->distinct()
                ->pluck('campus')
                ->sort()
                ->values();
            
            // Convert to collection of objects with campus_name property
            $campuses = collect();
            foreach ($campusesFromSections as $campusName) {
                $campuses->push((object)['campus_name' => $campusName]);
            }
        }
        
        // Get classes from ClassModel (only non-deleted classes)
        $classes = ClassModel::whereNotNull('class_name')->orderBy('class_name', 'asc')->get();
        
        // If no classes found, get from sections (but verify against ClassModel)
        if ($classes->isEmpty()) {
            $classesFromSections = Section::whereNotNull('class')
                ->distinct()
                ->pluck('class')
                ->sort()
                ->values();
            
            // Verify classes exist in ClassModel (filter out deleted classes)
            $existingClassNames = ClassModel::whereNotNull('class_name')->pluck('class_name')->map(function($name) {
                return strtolower(trim($name));
            })->toArray();
            
            // Convert to collection of objects with class_name property
            $classes = collect();
            foreach ($classesFromSections as $className) {
                // Only include if class exists in ClassModel
                if (in_array(strtolower(trim($className)), $existingClassNames)) {
                    $classes->push((object)['class_name' => $className]);
                }
            }
        }
        
        // Get sessions from sections
        $allSessions = Section::whereNotNull('session')->distinct()->pluck('session')->sort()->values();
        
        // If no sessions found, provide defaults
        if ($allSessions->isEmpty()) {
            $currentYear = date('Y');
            $allSessions = collect([
                $currentYear . '-' . ($currentYear + 1),
                ($currentYear - 1) . '-' . $currentYear,
                ($currentYear - 2) . '-' . ($currentYear - 1)
            ]);
        }
        
        // Get only teachers from staff table
        $teachers = Staff::whereRaw('LOWER(TRIM(designation)) LIKE ?', ['%teacher%'])
            ->whereNotNull('name')
            ->orderBy('name')
            ->get(['name', 'campus']);
        
        return view('classes.manage-section', compact('sections', 'campuses', 'classes', 'allSessions', 'teachers'));
    }

    /**
     * Get classes by campus (AJAX).
     */
    public function getClassesByCampus(Request $request): JsonResponse
    {
        $campus = $request->get('campus');

        $classesQuery = ClassModel::whereNotNull('class_name');
        if ($campus) {
            $classesQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
        }
        $classes = $classesQuery->distinct()->pluck('class_name')->sort()->values();

        if ($classes->isEmpty()) {
            $classesFromSections = Section::whereNotNull('class');
            if ($campus) {
                $classesFromSections->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
            }
            $classesFromSections = $classesFromSections->distinct()->pluck('class')->sort()->values();

            $classes = $classesFromSections;
        }

        $classes = $classes->map(function ($class) {
            return trim((string) $class);
        })->filter(function ($class) {
            return $class !== '';
        })->unique()->sort()->values();

        return response()->json(['classes' => $classes]);
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

        // Convert empty string to null for teacher field
        if (isset($validated['teacher']) && trim($validated['teacher']) === '') {
            $validated['teacher'] = null;
        }

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

        // Convert empty string to null for teacher field
        if (isset($validated['teacher']) && trim($validated['teacher']) === '') {
            $validated['teacher'] = null;
        }

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
        // Check if there are any students in this section
        // Match by both class and section name (case-insensitive)
        $studentsCount = Student::where(function($query) use ($section) {
            $query->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($section->class))])
                  ->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($section->name))]);
        })->count();

        if ($studentsCount > 0) {
            return redirect()
                ->route('classes.manage-section')
                ->with('error', "Cannot delete section '{$section->name}' of class '{$section->class}' because it has {$studentsCount} student(s) enrolled. Please transfer all students to another section first.");
        }

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
        
        // Filter out sections with deleted classes or null/empty class names
        $query->whereNotNull('class')
              ->where('class', '!=', '');
        
        // Get all existing class names from ClassModel
        $existingClassNames = ClassModel::whereNotNull('class_name')
            ->where('class_name', '!=', '')
            ->pluck('class_name')
            ->map(function($name) {
                return strtolower(trim($name));
            })
            ->unique()
            ->values()
            ->toArray();
        
        if (!empty($existingClassNames)) {
            // Filter sections to only show those whose class exists in ClassModel
            $query->where(function($q) use ($existingClassNames) {
                foreach ($existingClassNames as $className) {
                    $q->orWhereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($className))]);
                }
            });
        } else {
            // If no classes exist, show no sections
            $query->whereRaw('1 = 0');
        }
        
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

    /**
     * Clean up orphaned teachers from sections and subjects.
     * Removes teachers that no longer exist in the staff table.
     */
    private function cleanupOrphanedTeachers(): void
    {
        // Get all existing teacher names from staff table (only teachers)
        $existingTeachers = Staff::whereRaw('LOWER(TRIM(designation)) LIKE ?', ['%teacher%'])
            ->whereNotNull('name')
            ->pluck('name')
            ->map(function($name) {
                return strtolower(trim($name));
            })
            ->unique()
            ->toArray();

        // Get all sections with teachers assigned
        $sections = Section::whereNotNull('teacher')
            ->where('teacher', '!=', '')
            ->get();

        foreach ($sections as $section) {
            if (!empty($section->teacher)) {
                $teacherName = strtolower(trim($section->teacher));
                // If teacher doesn't exist in staff table, remove them
                if (!in_array($teacherName, $existingTeachers)) {
                    $section->teacher = null;
                    $section->save();
                }
            }
        }

        // Get all subjects with teachers assigned
        $subjects = Subject::whereNotNull('teacher')
            ->where('teacher', '!=', '')
            ->get();

        foreach ($subjects as $subject) {
            if (!empty($subject->teacher)) {
                $teacherName = strtolower(trim($subject->teacher));
                // If teacher doesn't exist in staff table, remove them
                if (!in_array($teacherName, $existingTeachers)) {
                    $subject->teacher = null;
                    $subject->save();
                }
            }
        }
    }
}

