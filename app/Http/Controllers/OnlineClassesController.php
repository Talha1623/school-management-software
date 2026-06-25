<?php

namespace App\Http\Controllers;

use App\Models\OnlineClass;
use App\Models\ClassModel;
use App\Models\Section;
use App\Models\Campus;
use App\Models\Subject;
use App\Models\GeneralSetting;
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
                    return trim((string) $class);
                })
                ->filter(function ($class) {
                    return $class !== '';
                })
                ->unique(fn ($class) => strtolower($class))
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
        
        // Build classes list for dropdown (loaded dynamically by campus in the modal)
        if ($staff && $staff->isTeacher()) {
            $classes = $assignedClasses->map(fn ($className) => (object) ['class_name' => $className])->values();
        } else {
            $classes = collect();
        }
        
        // Sections are loaded dynamically when class is selected
        $sections = collect();
        
        return view('online-classes', compact('onlineClasses', 'campuses', 'classes', 'sections'));
    }

    /**
     * Print online classes (dedicated print page)
     */
    public function print(Request $request): View
    {
        $query = OnlineClass::query();

        // Keep teacher restriction same as index
        $staff = Auth::guard('staff')->user();
        if ($staff && $staff->isTeacher()) {
            $assignedSubjects = Subject::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($staff->name ?? ''))])->get();
            $assignedSections = Section::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($staff->name ?? ''))])->get();

            $assignedClasses = $assignedSubjects->pluck('class')
                ->merge($assignedSections->pluck('class'))
                ->map(fn ($class) => trim((string) $class))
                ->filter(fn ($class) => $class !== '')
                ->unique(fn ($class) => strtolower($class))
                ->sort()
                ->values();

            if ($assignedClasses->isNotEmpty()) {
                $query->where(function ($q) use ($assignedClasses) {
                    foreach ($assignedClasses as $class) {
                        $q->orWhereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))]);
                    }
                });
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        // Search filter same as index
        if ($request->filled('search')) {
            $search = trim((string) $request->get('search'));
            if ($search !== '') {
                $searchLower = strtolower($search);
                $query->where(function ($q) use ($searchLower) {
                    $q->whereRaw('LOWER(campus) LIKE ?', ["%{$searchLower}%"])
                        ->orWhereRaw('LOWER(class) LIKE ?', ["%{$searchLower}%"])
                        ->orWhereRaw('LOWER(section) LIKE ?', ["%{$searchLower}%"])
                        ->orWhereRaw('LOWER(class_topic) LIKE ?', ["%{$searchLower}%"]);
                });
            }
        }

        $onlineClasses = $query->orderBy('start_date', 'desc')->get();
        $settings = GeneralSetting::getSettings();

        return view('online-classes-print', [
            'onlineClasses' => $onlineClasses,
            'settings' => $settings,
            'printedAt' => now()->format('d M Y, h:i A'),
        ]);
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
     * Get classes based on campus (AJAX).
     */
    public function getClassesByCampus(Request $request): JsonResponse
    {
        $campus = trim((string) $request->get('campus', ''));
        $staff = Auth::guard('staff')->user();

        if ($campus === '') {
            return response()->json(['classes' => []]);
        }

        if ($staff && $staff->isTeacher()) {
            $teacherName = strtolower(trim($staff->name ?? ''));
            $campusKey = strtolower($campus);

            $classes = Subject::whereRaw('LOWER(TRIM(teacher)) = ?', [$teacherName])
                ->whereRaw('LOWER(TRIM(campus)) = ?', [$campusKey])
                ->pluck('class')
                ->merge(
                    Section::whereRaw('LOWER(TRIM(teacher)) = ?', [$teacherName])
                        ->whereRaw('LOWER(TRIM(campus)) = ?', [$campusKey])
                        ->pluck('class')
                )
                ->map(fn ($class) => trim((string) $class))
                ->filter(fn ($class) => $class !== '')
                ->unique(fn ($class) => strtolower($class))
                ->sort()
                ->values();
        } else {
            $classes = $this->getDistinctClassesForDropdown($campus)
                ->pluck('class_name')
                ->values();
        }

        return response()->json(['classes' => $classes]);
    }

    /**
     * Get sections based on class (AJAX).
     * Filter by teacher's assigned sections if teacher.
     */
    public function getSections(Request $request): JsonResponse
    {
        $class = trim((string) $request->get('class', ''));
        $campus = trim((string) $request->get('campus', ''));
        
        $staff = Auth::guard('staff')->user();
        $sections = collect();
        
        // Filter by teacher's assigned sections if teacher
        if ($staff && $staff->isTeacher() && $class !== '') {
            $teacherName = strtolower(trim($staff->name ?? ''));
            
            $assignedSubjects = Subject::whereRaw('LOWER(TRIM(teacher)) = ?', [$teacherName])
                ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower($class)]);
            if ($campus !== '') {
                $assignedSubjects->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower($campus)]);
            }
            $assignedSubjects = $assignedSubjects->get();
            
            $assignedSections = Section::whereRaw('LOWER(TRIM(teacher)) = ?', [$teacherName])
                ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower($class)]);
            if ($campus !== '') {
                $assignedSections->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower($campus)]);
            }
            $assignedSections = $assignedSections->get();
            
            $sections = $assignedSubjects->pluck('section')
                ->merge($assignedSections->pluck('name'))
                ->map(fn ($section) => trim((string) $section))
                ->filter(fn ($section) => $section !== '')
                ->unique(fn ($section) => strtolower($section))
                ->sort()
                ->values();
        } elseif ($class !== '') {
            $sectionsQuery = Section::query()
                ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower($class)]);
            if ($campus !== '') {
                $sectionsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower($campus)]);
            }
            
            $sections = $sectionsQuery->whereNotNull('name')
                ->distinct()
                ->pluck('name')
                ->map(fn ($name) => trim((string) $name))
                ->filter(fn ($name) => $name !== '')
                ->unique(fn ($name) => strtolower($name))
                ->sort()
                ->values();
            
            if ($sections->isEmpty()) {
                $subjectsQuery = Subject::query()
                    ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower($class)]);
                if ($campus !== '') {
                    $subjectsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower($campus)]);
                }
                $sections = $subjectsQuery->whereNotNull('section')
                    ->distinct()
                    ->pluck('section')
                    ->map(fn ($name) => trim((string) $name))
                    ->filter(fn ($name) => $name !== '')
                    ->unique(fn ($name) => strtolower($name))
                    ->sort()
                    ->values();
            }
            
            if ($sections->isEmpty()) {
                $onlineQuery = OnlineClass::whereRaw('LOWER(TRIM(class)) = ?', [strtolower($class)])
                    ->whereNotNull('section');
                if ($campus !== '') {
                    $onlineQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower($campus)]);
                }
                $sections = $onlineQuery->distinct()
                    ->pluck('section')
                    ->map(fn ($name) => trim((string) $name))
                    ->filter(fn ($name) => $name !== '')
                    ->unique(fn ($name) => strtolower($name))
                    ->sort()
                    ->values();
            }
        }
        
        return response()->json(['sections' => $sections]);
    }

    /**
     * Build distinct class options for dropdowns.
     */
    private function getDistinctClassesForDropdown(?string $campus = null): \Illuminate\Support\Collection
    {
        $classesQuery = ClassModel::whereNotNull('class_name');
        if ($campus !== null && trim($campus) !== '') {
            $classesQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
        }

        $classNames = $classesQuery->pluck('class_name')
            ->map(fn ($name) => trim((string) $name))
            ->filter(fn ($name) => $name !== '')
            ->unique(fn ($name) => strtolower($name))
            ->sort()
            ->values();

        if ($classNames->isEmpty()) {
            $onlineQuery = OnlineClass::whereNotNull('class');
            if ($campus !== null && trim($campus) !== '') {
                $onlineQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
            }
            $classNames = $onlineQuery->pluck('class')
                ->map(fn ($name) => trim((string) $name))
                ->filter(fn ($name) => $name !== '')
                ->unique(fn ($name) => strtolower($name))
                ->sort()
                ->values();
        }

        return $classNames->map(fn ($className) => (object) ['class_name' => $className])->values();
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
