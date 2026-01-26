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
        
        // Get sections from Section model (only non-deleted sections)
        $sections = Section::whereNotNull('name')
            ->whereNotNull('class')
            ->distinct()
            ->orderBy('name', 'asc')
            ->pluck('name')
            ->sort()
            ->values();
        
        // Get only teachers from staff table
        $teachers = Staff::whereRaw('LOWER(TRIM(designation)) LIKE ?', ['%teacher%'])
            ->whereNotNull('name')
            ->orderBy('name')
            ->pluck('name', 'id');
        
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
        
        // Only query if at least one filter is applied
        if ($request->filled('filter_campus') || $request->filled('filter_class') || $request->filled('filter_section')) {
            $query = Subject::query();
            
            // Filter out subjects with deleted classes
            $existingClassNames = ClassModel::whereNotNull('class_name')->pluck('class_name')->map(function($name) {
                return strtolower(trim($name));
            })->toArray();
            
            if (!empty($existingClassNames)) {
                $query->where(function($q) use ($existingClassNames) {
                    foreach ($existingClassNames as $className) {
                        $q->orWhereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($className))]);
                    }
                });
            } else {
                // If no classes exist, show no subjects
                $query->whereRaw('1 = 0');
            }
            
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
        
        return view('manage-subjects', compact('subjects', 'campuses', 'classes', 'sections', 'teachers', 'allSessions'));
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

        // Convert empty string to null for teacher field
        if (isset($validated['teacher']) && trim($validated['teacher']) === '') {
            $validated['teacher'] = null;
        }

        // Before creating new subject, permanently delete any existing deleted subjects
        // with the same campus, class, section, and subject_name to prevent restoration
        Subject::whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($validated['campus']))])
            ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($validated['class']))])
            ->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($validated['section']))])
            ->whereRaw('LOWER(TRIM(subject_name)) = ?', [strtolower(trim($validated['subject_name']))])
            ->delete();

        Subject::create($validated);

        return redirect()
            ->route('manage-subjects')
            ->with('success', 'Subject created successfully!');
    }

    /**
     * Update the specified subject.
     */
    public function update(Request $request, Subject $subject): RedirectResponse
    {
        $validated = $request->validate([
            'campus' => ['required', 'string', 'max:255'],
            'subject_name' => ['required', 'string', 'max:255'],
            'class' => ['required', 'string', 'max:255'],
            'section' => ['required', 'string', 'max:255'],
            'teacher' => ['nullable', 'string', 'max:255'],
            'session' => ['nullable', 'string', 'max:255'],
        ]);

        // Convert empty string to null for teacher field
        if (isset($validated['teacher']) && trim($validated['teacher']) === '') {
            $validated['teacher'] = null;
        }

        // Convert empty string to null for session field
        if (isset($validated['session']) && trim($validated['session']) === '') {
            $validated['session'] = null;
        }

        $subject->update($validated);

        return redirect()
            ->route('manage-subjects')
            ->with('success', 'Subject updated successfully!');
    }

    /**
     * Remove the specified subject.
     */
    public function destroy(Subject $subject): RedirectResponse
    {
        $subject->delete();

        return redirect()
            ->route('manage-subjects')
            ->with('success', 'Subject deleted successfully!');
    }

    /**
     * Get sections by class name (AJAX).
     */
    public function getSectionsByClass(Request $request)
    {
        $className = $request->get('class');
        $campus = $request->get('campus');
        
        if (!$className) {
            return response()->json(['sections' => []]);
        }

        // Verify class exists in ClassModel (not deleted)
        $classExists = ClassModel::whereNotNull('class_name')
            ->whereRaw('LOWER(TRIM(class_name)) = ?', [strtolower(trim($className))])
            ->exists();
        
        if (!$classExists) {
            return response()->json(['sections' => []]);
        }

        // Get sections for the selected class (only non-deleted sections)
        $sectionsQuery = Section::whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($className))]);
        if ($campus) {
            $sectionsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
        }
        $sections = $sectionsQuery
            ->whereNotNull('name')
            ->whereNotNull('class')
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
     * Get classes by campus (AJAX).
     */
    public function getClassesByCampus(Request $request)
    {
        $campus = $request->get('campus');

        $classesQuery = ClassModel::whereNotNull('class_name');
        if ($campus) {
            $classesQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
        }

        $classes = $classesQuery->distinct()
            ->orderBy('class_name', 'asc')
            ->pluck('class_name')
            ->map(function ($className) {
                return trim((string) $className);
            })
            ->filter(function ($className) {
                return $className !== '';
            })
            ->unique()
            ->values();

        if ($classes->isEmpty()) {
            $fallbackQuery = Section::whereNotNull('class');
            if ($campus) {
                $fallbackQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
            }
            $classes = $fallbackQuery->distinct()
                ->pluck('class')
                ->map(function ($className) {
                    return trim((string) $className);
                })
                ->filter(function ($className) {
                    return $className !== '';
                })
                ->unique()
                ->sort()
                ->values();
        }

        return response()->json(['classes' => $classes]);
    }

    /**
     * Get teachers by campus (AJAX).
     */
    public function getTeachersByCampus(Request $request)
    {
        $campus = $request->get('campus');

        $teachersQuery = Staff::whereRaw('LOWER(TRIM(designation)) LIKE ?', ['%teacher%'])
            ->whereNotNull('name');
        if ($campus) {
            $teachersQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
        }

        $teachers = $teachersQuery->orderBy('name')
            ->pluck('name')
            ->map(function ($name) {
                return trim((string) $name);
            })
            ->filter(function ($name) {
                return $name !== '';
            })
            ->unique()
            ->values();

        return response()->json(['teachers' => $teachers]);
    }

    /**
     * Export subjects to Excel, CSV, or PDF
     */
    public function export(Request $request, string $format)
    {
        $query = Subject::query();
        
        // Filter out subjects with deleted classes
        $existingClassNames = ClassModel::whereNotNull('class_name')->pluck('class_name')->map(function($name) {
            return strtolower(trim($name));
        })->toArray();
        
        if (!empty($existingClassNames)) {
            $query->where(function($q) use ($existingClassNames) {
                foreach ($existingClassNames as $className) {
                    $q->orWhereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($className))]);
                }
            });
        } else {
            // If no classes exist, show no subjects
            $query->whereRaw('1 = 0');
        }
        
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

