<?php

namespace App\Http\Controllers;

use App\Models\OnlineClass;
use App\Models\ClassModel;
use App\Models\Section;
use App\Models\Campus;
use App\Models\Subject;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class OnlineClassesController extends Controller
{
    /**
     * Display a listing of online classes.
     */
    public function index(Request $request): View
    {
        $query = OnlineClass::query();
        
        // If logged-in user is a teacher (staff guard), restrict to their assigned classes
        $staff = Auth::guard('staff')->user();
        $assignedClasses = collect();
        if ($staff && $staff->isTeacher()) {
            $assignedSubjects = Subject::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($staff->name ?? ''))])
                ->get();

            $assignedSections = Section::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($staff->name ?? ''))])
                ->get();

            // Merge classes from both sources
            $assignedClasses = $assignedSubjects->pluck('class')
                ->merge($assignedSections->pluck('class'))
                ->map(function ($class) {
                    return trim($class);
                })
                ->filter(function ($class) {
                    return !empty($class);
                })
                ->unique()
                ->sort()
                ->values();

            if ($assignedClasses->isNotEmpty()) {
                $query->where(function ($q) use ($assignedClasses) {
                    foreach ($assignedClasses as $class) {
                        $q->orWhereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))]);
                    }
                });
            } else {
                // If teacher has no assigned classes, show nothing
                $query->whereRaw('1 = 0');
            }
        }
        
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
        
        // Get campuses for dropdown - filter by teacher's assigned campuses if teacher
        if ($staff && $staff->isTeacher()) {
            $teacherName = strtolower(trim($staff->name ?? ''));
            // Get campuses from teacher's assigned subjects
            $teacherCampuses = Subject::whereRaw('LOWER(TRIM(teacher)) = ?', [$teacherName])
                ->whereNotNull('campus')
                ->distinct()
                ->pluck('campus')
                ->merge(
                    Section::whereRaw('LOWER(TRIM(teacher)) = ?', [$teacherName])
                        ->whereNotNull('campus')
                        ->distinct()
                        ->pluck('campus')
                )
                ->map(fn($c) => trim($c))
                ->filter(fn($c) => !empty($c))
                ->unique()
                ->sort()
                ->values();
            
            // Filter Campus model results to only show assigned campuses
            if ($teacherCampuses->isNotEmpty()) {
                $campuses = Campus::orderBy('campus_name', 'asc')
                    ->get()
                    ->filter(function($campus) use ($teacherCampuses) {
                        return $teacherCampuses->contains(strtolower(trim($campus->campus_name ?? '')));
                    });
                
                // If no campuses found in Campus model, create objects from teacher campuses
                if ($campuses->isEmpty()) {
                    $campuses = $teacherCampuses->map(function($campus) {
                        return (object)['campus_name' => $campus];
                    });
                }
            } else {
                // If teacher has no assigned campuses, show empty
                $campuses = collect();
            }
        } else {
            // For non-teachers (admin, staff, etc.), get all campuses
            $campuses = Campus::orderBy('campus_name', 'asc')->get();
            
            // If no campuses found, get from classes or online classes
            if ($campuses->isEmpty()) {
                $campusesFromClasses = ClassModel::whereNotNull('campus')
                    ->distinct()
                    ->pluck('campus')
                    ->sort()
                    ->values();
                
                $campusesFromOnlineClasses = OnlineClass::whereNotNull('campus')
                    ->distinct()
                    ->pluck('campus')
                    ->sort()
                    ->values();
                
                $allCampuses = $campusesFromClasses->merge($campusesFromOnlineClasses)->unique()->sort()->values();
                
                // Convert to collection of objects with campus_name property
                $campuses = collect();
                foreach ($allCampuses as $campusName) {
                    $campuses->push((object)['campus_name' => $campusName]);
                }
            }
        }
        
        // Build classes list for dropdown
        if ($staff && $staff->isTeacher()) {
            // For teachers, show only their assigned classes
            $classes = collect();
            foreach ($assignedClasses as $className) {
                $classes->push((object)['class_name' => $className]);
            }
        } else {
            // For non-teachers, get all classes
        $classes = ClassModel::orderBy('class_name', 'asc')->get();
        
        // If no classes found, get from online classes
        if ($classes->isEmpty()) {
            $classesFromOnlineClasses = OnlineClass::whereNotNull('class')
                ->distinct()
                ->pluck('class')
                ->sort()
                ->values();
            
            // Convert to collection of objects with class_name property
            $classes = collect();
            foreach ($classesFromOnlineClasses as $className) {
                $classes->push((object)['class_name' => $className]);
                }
            }
        }
        
        // Get sections from Section model
        $sections = Section::whereNotNull('name')
            ->distinct()
            ->orderBy('name', 'asc')
            ->pluck('name')
            ->sort()
            ->values();
        
        // If no sections found, get from online classes
        if ($sections->isEmpty()) {
            $sectionsFromOnlineClasses = OnlineClass::whereNotNull('section')
                ->distinct()
                ->pluck('section')
                ->sort()
                ->values();
            
            $sections = $sectionsFromOnlineClasses;
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
            'link' => ['nullable', 'url', 'max:500'],
        ]);

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
            'link' => ['nullable', 'url', 'max:500'],
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
     * Get sections based on class (AJAX).
     * Filter by teacher's assigned sections if teacher.
     */
    public function getSections(Request $request): JsonResponse
    {
        $class = $request->get('class');
        
        $staff = Auth::guard('staff')->user();
        $sections = collect();
        
        // Filter by teacher's assigned sections if teacher
        if ($staff && $staff->isTeacher() && $class) {
            $teacherName = strtolower(trim($staff->name ?? ''));
            
            // Get sections from teacher's assigned subjects for this class
            $assignedSubjects = Subject::whereRaw('LOWER(TRIM(teacher)) = ?', [$teacherName])
                ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))])
                ->get();
            
            // Get sections from teacher's assigned sections for this class
            $assignedSections = Section::whereRaw('LOWER(TRIM(teacher)) = ?', [$teacherName])
                ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))])
                ->get();
            
            // Merge sections from both sources
            $sections = $assignedSubjects->pluck('section')
                ->merge($assignedSections->pluck('name'))
                ->map(function($section) {
                    return trim($section);
                })
                ->filter(function($section) {
                    return !empty($section);
                })
                ->unique()
                ->sort()
                ->values();
        } else {
            // For non-teachers, get all sections
            $sectionsQuery = Section::query();
            if ($class) {
                $sectionsQuery->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))]);
            }
            
            $sections = $sectionsQuery->whereNotNull('name')
                ->distinct()
                ->pluck('name')
                ->sort()
                ->values();
            
            // If no sections found, try fallback
            if ($sections->isEmpty() && $class) {
                $sectionsFromOnlineClasses = OnlineClass::whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))])
                    ->whereNotNull('section')
                    ->distinct()
                    ->pluck('section')
                    ->sort()
                    ->values();
                
                $sections = $sectionsFromOnlineClasses;
            }
        }
        
        return response()->json(['sections' => $sections]);
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
            
            fputcsv($file, ['ID', 'Campus', 'Class', 'Section', 'Class Topic', 'Start Date', 'Timing', 'Password', 'Link', 'Created At']);
            
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
                    $class->link ?? 'N/A',
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
            
            fputcsv($file, ['ID', 'Campus', 'Class', 'Section', 'Class Topic', 'Start Date', 'Timing', 'Password', 'Link', 'Created At']);
            
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
                    $class->link ?? 'N/A',
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
