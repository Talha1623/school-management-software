<?php

namespace App\Http\Controllers;

use App\Models\ClassModel;
use App\Models\Campus;
use App\Models\Section;
use App\Models\Student;
use App\Models\Subject;
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
        
        // Load sections for each class (only sections that belong to existing classes)
        // Use case-insensitive matching to ensure accuracy
        foreach ($classes as $class) {
            $sectionsQuery = Section::whereRaw('LOWER(TRIM(COALESCE(class, ""))) = ?', [strtolower(trim($class->class_name))])
                ->whereNotNull('name')
                ->where('name', '!=', '');

            // Ensure sections are only from the same campus (if class campus is set)
            if (!empty($class->campus)) {
                $sectionsQuery->whereRaw('LOWER(TRIM(COALESCE(campus, ""))) = ?', [strtolower(trim($class->campus))]);
            }

            $sections = $sectionsQuery
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

        // Normalize class name and campus for comparison
        $className = strtolower(trim($validated['class_name']));
        $classCampus = $validated['campus'] ? strtolower(trim($validated['campus'])) : null;
        
        // Check if a class with the same name and campus already exists
        $existingClass = ClassModel::whereRaw('LOWER(TRIM(COALESCE(class_name, ""))) = ?', [$className]);
        
        if ($classCampus) {
            $existingClass->where(function($query) use ($classCampus) {
                $query->whereNull('campus')
                    ->orWhere('campus', '')
                    ->orWhereRaw('LOWER(TRIM(COALESCE(campus, ""))) = ?', [$classCampus]);
            });
        } else {
            $existingClass->where(function($query) {
                $query->whereNull('campus')
                    ->orWhere('campus', '');
            });
        }
        
        $existingClass = $existingClass->first();
        
        if ($existingClass) {
            $errorMessage = "Class '{$validated['class_name']}' already exists for campus '{$validated['campus']}'. Please use a different class name or campus.";
            return redirect()
                ->route('classes.manage-classes')
                ->with('error', $errorMessage)
                ->withInput();
        }
        
        // Before creating the class, delete any orphaned sections with this class name
        // Scope by campus to avoid removing sections from other campuses with same class
        $sectionsCleanupQuery = Section::whereRaw('LOWER(TRIM(COALESCE(class, ""))) = ?', [$className]);
        if ($classCampus) {
            $sectionsCleanupQuery->where(function($query) use ($classCampus) {
                $query->whereNull('campus')
                    ->orWhere('campus', '')
                    ->orWhereRaw('LOWER(TRIM(COALESCE(campus, ""))) = ?', [$classCampus]);
            });
        }
        $deletedSectionsCount = $sectionsCleanupQuery->delete();
        
        // Also delete any orphaned subjects with this class name (scoped by campus)
        $subjectsCleanupQuery = Subject::whereRaw('LOWER(TRIM(COALESCE(class, ""))) = ?', [$className]);
        if ($classCampus) {
            $subjectsCleanupQuery->where(function($query) use ($classCampus) {
                $query->whereNull('campus')
                    ->orWhere('campus', '')
                    ->orWhereRaw('LOWER(TRIM(COALESCE(campus, ""))) = ?', [$classCampus]);
            });
        }
        $deletedSubjectsCount = $subjectsCleanupQuery->delete();
        
        // Log cleanup for debugging
        if ($deletedSectionsCount > 0 || $deletedSubjectsCount > 0) {
            \Log::info('Cleaned up orphaned records before creating class', [
                'class_name' => $validated['class_name'],
                'deleted_sections_count' => $deletedSectionsCount,
                'deleted_subjects_count' => $deletedSubjectsCount,
            ]);
        }

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

        // Normalize class name and campus for comparison
        $className = strtolower(trim($validated['class_name']));
        $classCampus = $validated['campus'] ? strtolower(trim($validated['campus'])) : null;
        
        // Check if another class with the same name and campus already exists (excluding current class)
        $existingClass = ClassModel::where('id', '!=', $class_model->id)
            ->whereRaw('LOWER(TRIM(COALESCE(class_name, ""))) = ?', [$className]);
        
        if ($classCampus) {
            $existingClass->where(function($query) use ($classCampus) {
                $query->whereNull('campus')
                    ->orWhere('campus', '')
                    ->orWhereRaw('LOWER(TRIM(COALESCE(campus, ""))) = ?', [$classCampus]);
            });
        } else {
            $existingClass->where(function($query) {
                $query->whereNull('campus')
                    ->orWhere('campus', '');
            });
        }
        
        $existingClass = $existingClass->first();
        
        if ($existingClass) {
            $errorMessage = "Class '{$validated['class_name']}' already exists for campus '{$validated['campus']}'. Please use a different class name or campus.";
            return redirect()
                ->route('classes.manage-classes')
                ->with('error', $errorMessage)
                ->withInput();
        }

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
        // Normalize class name for comparison (trim and lowercase)
        $className = strtolower(trim($class_model->class_name));
        $classCampus = $class_model->campus ? strtolower(trim($class_model->campus)) : null;
        
        // Check if there are any students in this class
        // Use exact matching: class name must match exactly (case-insensitive, trimmed)
        $studentsQuery = Student::whereNotNull('class')
            ->where('class', '!=', '')
            ->whereRaw('LOWER(TRIM(COALESCE(class, ""))) = ?', [$className]);
        
        // If class has a campus, match students from that campus OR students with no campus set
        // This handles cases where students might not have campus set but belong to the class
        if ($classCampus) {
            $studentsQuery->where(function($query) use ($classCampus) {
                $query->whereNull('campus')
                    ->orWhere('campus', '')
                    ->orWhereRaw('LOWER(TRIM(COALESCE(campus, ""))) = ?', [$classCampus]);
            });
        }
        
        // Get actual students to verify
        $actualStudents = $studentsQuery
            ->select('id', 'student_code', 'student_name', 'class', 'section', 'campus')
            ->get();
        
        // Final verification: ensure exact matches
        $validStudents = $actualStudents->filter(function($student) use ($className, $classCampus) {
            $studentClass = strtolower(trim($student->class ?? ''));
            $studentCampus = $student->campus ? strtolower(trim($student->campus)) : '';
            
            // Class must match exactly
            if ($studentClass !== $className || empty($studentClass)) {
                return false;
            }
            
            // If class has campus, student must have the same campus OR no campus set
            // (students without campus can belong to a class with campus)
            if ($classCampus) {
                // Allow if student has no campus OR campus matches
                if (!empty($studentCampus) && $studentCampus !== $classCampus) {
                    return false;
                }
            }
            
            return true;
        });
        
        $validStudentsCount = $validStudents->count();

        // If valid students found, block deletion and show error message
        if ($validStudentsCount > 0) {
            // Build detailed error message with student codes and full details
            $studentDetails = $validStudents->map(function($s) {
                return "{$s->student_code} ({$s->student_name}) - Campus: " . ($s->campus ?: 'Not Set');
            })->implode(', ');
            
            // Log for debugging
            \Log::info('Class deletion blocked - Valid students found', [
                'class_name' => $class_model->class_name,
                'class_campus' => $class_model->campus,
                'normalized_class_name' => $className,
                'normalized_campus' => $classCampus,
                'students_count' => $validStudentsCount,
                'students' => $validStudents->map(function($s) {
                    return [
                        'id' => $s->id,
                        'code' => $s->student_code,
                        'name' => $s->student_name,
                        'class' => $s->class,
                        'section' => $s->section,
                        'campus' => $s->campus,
                    ];
                })->toArray(),
            ]);
            
            $errorMessage = "Cannot delete class '{$class_model->class_name}' because it has {$validStudentsCount} student(s) enrolled. Please transfer all students to another class first.";
            
            return redirect()
                ->route('classes.manage-classes')
                ->with('error', $errorMessage);
        }
        
        // Clear teacher field from all sections of this class before deleting
        // Scope by campus to avoid touching sections from other campuses
        $sectionsQuery = Section::whereRaw('LOWER(TRIM(COALESCE(class, ""))) = ?', [$className]);
        if ($classCampus) {
            $sectionsQuery->where(function($query) use ($classCampus) {
                $query->whereNull('campus')
                    ->orWhere('campus', '')
                    ->orWhereRaw('LOWER(TRIM(COALESCE(campus, ""))) = ?', [$classCampus]);
            });
        }
        $sectionsQuery->update(['teacher' => null]);
        
        // Delete all sections associated with this class (scoped by campus)
        $deletedSectionsCount = $sectionsQuery->delete();
        
        // Delete all subjects associated with this class (scoped by campus)
        $subjectsQuery = Subject::whereRaw('LOWER(TRIM(COALESCE(class, ""))) = ?', [$className]);
        if ($classCampus) {
            $subjectsQuery->where(function($query) use ($classCampus) {
                $query->whereNull('campus')
                    ->orWhere('campus', '')
                    ->orWhereRaw('LOWER(TRIM(COALESCE(campus, ""))) = ?', [$classCampus]);
            });
        }
        $deletedSubjectsCount = $subjectsQuery->delete();
        
        // Log deletion for debugging
        \Log::info('Class deleted with associated records', [
            'class_name' => $class_model->class_name,
            'normalized_class_name' => $className,
            'deleted_sections_count' => $deletedSectionsCount,
            'deleted_subjects_count' => $deletedSubjectsCount,
        ]);

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

