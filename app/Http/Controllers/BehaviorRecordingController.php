<?php

namespace App\Http\Controllers;

use App\Models\BehaviorRecord;
use App\Models\BehaviorCategory;
use App\Models\Campus;
use App\Models\ClassModel;
use App\Models\Section;
use App\Models\Student;
use App\Models\Subject;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class BehaviorRecordingController extends Controller
{
    /**
     * Display the behavior records with filters.
     */
    public function index(Request $request): View
    {
        // Get filter values
        $filterType = $request->get('filter_type');
        $filterClass = $request->get('filter_class');
        $filterSection = $request->get('filter_section');
        $filterDate = $request->get('filter_date', date('Y-m-d'));
        $filterCampus = $request->get('filter_campus');

        $staff = Auth::guard('staff')->user();
        $defaultCampus = null;
        if ($staff && $staff->isTeacher()) {
            $defaultCampus = $staff->campus ?? null;
            if ($defaultCampus && !$filterCampus) {
                $filterCampus = $defaultCampus;
                $request->merge(['filter_campus' => $defaultCampus]);
            }
        }

        // Get campuses for dropdown - first from Campus model, then fallback
        $campuses = Campus::orderBy('campus_name', 'asc')->get();
        if ($campuses->isEmpty()) {
            $campusesFromClasses = ClassModel::whereNotNull('campus')->distinct()->pluck('campus');
            $campusesFromSections = Section::whereNotNull('campus')->distinct()->pluck('campus');
            $allCampuses = $campusesFromClasses->merge($campusesFromSections)->unique()->sort()->values();
            $campuses = $allCampuses->map(function($campus) {
                return (object)['campus_name' => $campus];
            });
        }
        if ($defaultCampus) {
            $campuses = $campuses->filter(function ($campus) use ($defaultCampus) {
                return ($campus->campus_name ?? $campus) === $defaultCampus;
            })->values();
        }

        // Get classes - filter by teacher's assigned classes if teacher
        $classes = collect();
        
        if ($staff && $staff->isTeacher()) {
            // Get classes from teacher's assigned subjects
            $assignedSubjects = Subject::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($staff->name ?? ''))]);
            if ($filterCampus) {
                $assignedSubjects->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($filterCampus))]);
            }
            $assignedSubjects = $assignedSubjects->get();
            
            // Get classes from teacher's assigned sections
            $assignedSections = Section::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($staff->name ?? ''))]);
            if ($filterCampus) {
                $assignedSections->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($filterCampus))]);
            }
            $assignedSections = $assignedSections->get();
            
            // Merge classes from both sources
            $classes = $assignedSubjects->pluck('class')
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
        } else {
            $classes = $this->distinctClassNames($filterCampus);

            if ($classes->isEmpty()) {
                $classesFromSubjects = Subject::whereNotNull('class');
                if ($filterCampus) {
                    $classesFromSubjects->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($filterCampus))]);
                }
                $classesFromSubjects = $classesFromSubjects->pluck('class')
                    ->map(fn ($class) => trim((string) $class))
                    ->filter(fn ($class) => $class !== '')
                    ->unique(fn ($class) => strtolower($class))
                    ->sort()
                    ->values();
                $classes = $classesFromSubjects;
            }
        }

        // Get sections based on selected class - filter by teacher's assigned subjects if teacher
        $sections = collect();
        if ($filterClass) {
            if ($staff && $staff->isTeacher()) {
                // Get sections from teacher's assigned subjects for this class
                $assignedSubjects = Subject::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($staff->name ?? ''))])
                    ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($filterClass))]);
                if ($filterCampus) {
                    $assignedSubjects->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($filterCampus))]);
                }
                $assignedSubjects = $assignedSubjects->get();
                
                // Get sections from teacher's assigned sections for this class
                $assignedSections = Section::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($staff->name ?? ''))])
                    ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($filterClass))]);
                if ($filterCampus) {
                    $assignedSections->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($filterCampus))]);
                }
                $assignedSections = $assignedSections->get();
                
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
                $sectionsQuery = Section::whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($filterClass))]);
                if ($filterCampus) {
                    $sectionsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($filterCampus))]);
                }
                $sections = $sectionsQuery
                    ->whereNotNull('name')
                    ->distinct()
                    ->pluck('name')
                    ->sort()
                    ->values();
                
                if ($sections->isEmpty()) {
                    $sectionsFromSubjects = Subject::whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($filterClass))])
                        ->whereNotNull('section');
                    if ($filterCampus) {
                        $sectionsFromSubjects->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($filterCampus))]);
                    }
                    $sectionsFromSubjects = $sectionsFromSubjects
                        ->distinct()
                        ->pluck('section')
                        ->sort()
                        ->values();
                    $sections = $sectionsFromSubjects;
                }
            }
        }

        // Get students based on selected filters.
        $students = collect();
        $campusName = null;
        
        if ($filterClass) {
            $studentsQuery = Student::query();
            
            $studentsQuery->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($filterClass))]);
            
            if ($filterSection) {
                // If section filter is selected, only show students with that specific section
                // This ensures transferred students don't appear with wrong section
                $studentsQuery->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($filterSection))]);
            } else {
                // If no section filter, show all students in the class (including those without sections)
                // This is fine - students without sections can still have behavior records
            }
            
            if ($filterCampus) {
                // Filter by campus - use case-insensitive comparison to match transferred students
                // Normalize campus name first (same way transfer does)
                $normalizedFilterCampus = trim($filterCampus);
                $campusRecord = \App\Models\Campus::whereRaw('LOWER(TRIM(campus_name)) = ?', [strtolower($normalizedFilterCampus)])->first();
                if ($campusRecord) {
                    $normalizedFilterCampus = $campusRecord->campus_name;
                }
                // Use both normalized and original campus name for matching
                $studentsQuery->where(function($q) use ($normalizedFilterCampus, $filterCampus) {
                    $q->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($normalizedFilterCampus))])
                      ->orWhereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($filterCampus))]);
                });
            }
            
            $students = $studentsQuery->orderBy('student_code', 'asc')
                ->orderBy('student_name', 'asc')
                ->get();
            
            // Get campus from first student or use default
            if ($filterCampus) {
                $campusName = $filterCampus;
            } elseif ($students->count() > 0) {
                $campusName = $students->first()->campus ?? 'Main Campus';
            }
        }

        $types = collect(['daily behavior' => 'Daily Behavior']);

        // Get behavior categories for the selected campus
        $categories = $this->categoriesForCampus($filterCampus);
        $usingDefaultCategory = false;
        if ($categories->isEmpty()) {
            $categories = collect(['General Behavior']);
            $usingDefaultCategory = true;
        }

        $existingRecords = collect();
        if ($filterType && $filterClass && $students->isNotEmpty()) {
            $existingRecords = BehaviorRecord::whereIn('student_id', $students->pluck('id'))
                ->whereRaw('LOWER(TRIM(type)) = ?', [strtolower(trim((string) $filterType))])
                ->whereDate('date', $filterDate)
                ->get()
                ->map(function (BehaviorRecord $record) {
                    return [
                        'student_id' => $record->student_id,
                        'category' => $record->category,
                        'points' => (int) $record->points,
                        'type' => $record->type,
                        'class' => $record->class,
                        'section' => $record->section,
                        'campus' => $record->campus,
                        'date' => $record->date?->format('Y-m-d'),
                    ];
                })
                ->values();
        }

        return view('student-behavior.recording', compact(
            'types',
            'campuses',
            'classes',
            'sections',
            'students',
            'campusName',
            'categories',
            'usingDefaultCategory',
            'existingRecords',
            'filterType',
            'filterClass',
            'filterSection',
            'filterDate',
            'filterCampus'
        ));
    }

    private function distinctClassNames(?string $campus = null): \Illuminate\Support\Collection
    {
        $classesQuery = ClassModel::whereNotNull('class_name');
        if ($campus) {
            $classesQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
        }

        return $classesQuery->pluck('class_name')
            ->map(fn ($class) => trim((string) $class))
            ->filter(fn ($class) => $class !== '')
            ->unique(fn ($class) => strtolower($class))
            ->sort()
            ->values();
    }

    private function categoriesForCampus(?string $campus): \Illuminate\Support\Collection
    {
        if ($campus) {
            return BehaviorCategory::whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))])
                ->orderBy('category_name', 'asc')
                ->pluck('category_name')
                ->map(fn ($name) => trim((string) $name))
                ->filter(fn ($name) => $name !== '')
                ->unique(fn ($name) => strtolower($name))
                ->values();
        }

        return BehaviorCategory::orderBy('category_name', 'asc')
            ->pluck('category_name')
            ->map(fn ($name) => trim((string) $name))
            ->filter(fn ($name) => $name !== '')
            ->unique(fn ($name) => strtolower($name))
            ->values();
    }

    private function resolveRecordedByName(): string
    {
        foreach (['staff', 'admin', 'web'] as $guard) {
            $user = Auth::guard($guard)->user();
            if ($user) {
                return trim((string) ($user->name ?? $user->email ?? 'System')) ?: 'System';
            }
        }

        return 'System';
    }

    /**
     * Get sections by class (AJAX endpoint)
     */
    public function getSectionsByClass(Request $request)
    {
        $class = $request->get('class');
        $campus = $request->get('campus');
        
        if (!$class) {
            return response()->json(['sections' => []]);
        }

        $staff = Auth::guard('staff')->user();
        $sections = collect();
        
        // Filter by teacher's assigned subjects and sections if teacher
        if ($staff && $staff->isTeacher()) {
            // Get sections from teacher's assigned subjects for this class
            $assignedSubjects = Subject::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($staff->name ?? ''))])
                ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))]);
            if ($campus) {
                $assignedSubjects->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
            }
            $assignedSubjects = $assignedSubjects->get();
            
            // Get sections from teacher's assigned sections for this class
            $assignedSections = Section::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($staff->name ?? ''))])
                ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))]);
            if ($campus) {
                $assignedSections->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
            }
            $assignedSections = $assignedSections->get();
            
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
            $sectionsQuery = Section::whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))]);
            if ($campus) {
                $sectionsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
            }
            $sections = $sectionsQuery
                ->whereNotNull('name')
                ->distinct()
                ->pluck('name')
                ->sort()
                ->values();
            
            if ($sections->isEmpty()) {
                $sections = Subject::whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))])
                    ->whereNotNull('section');
                if ($campus) {
                    $sections->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
                }
                $sections = $sections
                    ->distinct()
                    ->pluck('section')
                    ->sort()
                    ->values();
            }
        }

        return response()->json(['sections' => $sections]);
    }

    /**
     * Get classes by campus (AJAX endpoint)
     */
    public function getClassesByCampus(Request $request): JsonResponse
    {
        $campus = $request->get('campus');
        $staff = Auth::guard('staff')->user();
        $classes = collect();

        if ($staff && $staff->isTeacher()) {
            // Get classes from teacher's assigned subjects
            $assignedSubjects = Subject::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($staff->name ?? ''))]);
            if ($campus) {
                $assignedSubjects->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
            }
            $assignedSubjects = $assignedSubjects->get();

            // Get classes from teacher's assigned sections
            $assignedSections = Section::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($staff->name ?? ''))]);
            if ($campus) {
                $assignedSections->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
            }
            $assignedSections = $assignedSections->get();

            $classes = $assignedSubjects->pluck('class')
                ->merge($assignedSections->pluck('class'))
                ->map(fn ($class) => trim((string) $class))
                ->filter(fn ($class) => $class !== '')
                ->unique(fn ($class) => strtolower($class))
                ->sort()
                ->values();
        } else {
            $classes = $this->distinctClassNames($campus);

            if ($classes->isEmpty()) {
                $classesFromSubjects = Subject::whereNotNull('class');
                if ($campus) {
                    $classesFromSubjects->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
                }
                $classes = $classesFromSubjects->pluck('class')
                    ->map(fn ($class) => trim((string) $class))
                    ->filter(fn ($class) => $class !== '')
                    ->unique(fn ($class) => strtolower($class))
                    ->sort()
                    ->values();
            }
        }

        return response()->json(['classes' => $classes->values()]);
    }

    /**
     * Get categories by campus (AJAX endpoint)
     */
    public function getCategoriesByCampus(Request $request): JsonResponse
    {
        $campus = $request->get('campus');
        $categories = $this->categoriesForCampus($campus);

        if ($categories->isEmpty()) {
            $categories = collect(['General Behavior']);
        }

        return response()->json(['categories' => $categories]);
    }

    /**
     * Store behavior record
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'student_id' => ['required', 'exists:students,id'],
                'type' => ['required', 'string'],
                'category' => ['nullable', 'string'],
                'points' => ['required', 'integer'],
                'class' => ['required', 'string'],
                'section' => ['nullable', 'string'],
                'campus' => ['required', 'string'],
                'date' => ['required', 'date'],
            ]);

            // Get student details
            $student = Student::findOrFail($validated['student_id']);

            // Normalize section - convert empty string to null
            $section = !empty($validated['section']) ? $validated['section'] : null;
            $category = $validated['category'] ?? null;

            // Update existing record for same student/category/date, otherwise create one
            $newRecord = BehaviorRecord::updateOrCreate(
                [
                    'student_id' => $validated['student_id'],
                    'type' => $validated['type'],
                    'category' => $category,
                    'date' => $validated['date'],
                ],
                [
                    'student_name' => $student->student_name,
                    'points' => $validated['points'],
                    'class' => $validated['class'],
                    'section' => $section,
                    'campus' => $validated['campus'],
                    'description' => $validated['points'] > 0 ? '+' . $validated['points'] . ' Points' : $validated['points'] . ' Points',
                    'recorded_by' => $this->resolveRecordedByName(),
                ]
            );
            
            $savedCategory = $newRecord->category;

            return response()->json([
                'success' => true,
                'message' => 'Behavior record saved successfully',
                'category' => $savedCategory
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error saving behavior record: ' . $e->getMessage()
            ], 500);
        }
    }
}

