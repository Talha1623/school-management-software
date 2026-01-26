<?php

namespace App\Http\Controllers;

use App\Models\BehaviorRecord;
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
                ->map(function($class) {
                    return trim($class);
                })
                ->filter(function($class) {
                    return !empty($class);
                })
                ->unique()
                ->sort()
                ->values();
        } else {
            // For non-teachers, get all classes
            $classesQuery = ClassModel::whereNotNull('class_name');
            if ($filterCampus) {
                $classesQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($filterCampus))]);
            }
            $classes = $classesQuery->distinct()->pluck('class_name')->sort()->values();
            
            if ($classes->isEmpty()) {
                $classesFromSubjects = Subject::whereNotNull('class');
                if ($filterCampus) {
                    $classesFromSubjects->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($filterCampus))]);
                }
                $classesFromSubjects = $classesFromSubjects->distinct()->pluck('class')->sort();
                $classes = $classesFromSubjects->isEmpty() ? collect(['Nursery', 'KG', '1st', '2nd', '3rd', '4th', '5th', '6th', '7th', '8th', '9th', '10th', '11th', '12th']) : $classesFromSubjects;
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

        // Get students based on filters - only those connected to parents
        $students = collect();
        $campusName = null;
        
        if ($filterClass) {
            $studentsQuery = Student::query();
            
            // Only get students who have parent connection (parent_account_id or father_name)
            $studentsQuery->where(function($query) {
                $query->whereNotNull('parent_account_id')
                      ->orWhere(function($q) {
                          $q->whereNotNull('father_name')
                            ->where('father_name', '!=', '');
                      });
            });
            
            $studentsQuery->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($filterClass))]);
            
            if ($filterSection) {
                $studentsQuery->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($filterSection))]);
            }
            if ($filterCampus) {
                $studentsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($filterCampus))]);
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

        return view('student-behavior.recording', compact(
            'types',
            'campuses',
            'classes',
            'sections',
            'students',
            'campusName',
            'filterType',
            'filterClass',
            'filterSection',
            'filterDate',
            'filterCampus'
        ));
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
                ->map(function($class) {
                    return trim($class);
                })
                ->filter(function($class) {
                    return !empty($class);
                })
                ->unique()
                ->sort()
                ->values();
        } else {
            $classesQuery = ClassModel::whereNotNull('class_name');
            if ($campus) {
                $classesQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
            }
            $classes = $classesQuery->distinct()->pluck('class_name')->sort()->values();

            if ($classes->isEmpty()) {
                $classesFromSubjects = Subject::whereNotNull('class');
                if ($campus) {
                    $classesFromSubjects->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
                }
                $classesFromSubjects = $classesFromSubjects->distinct()->pluck('class')->sort();
                $classes = $classesFromSubjects->isEmpty()
                    ? collect(['Nursery', 'KG', '1st', '2nd', '3rd', '4th', '5th', '6th', '7th', '8th', '9th', '10th', '11th', '12th'])
                    : $classesFromSubjects;
            }
        }

        $classes = $classes->map(function($class) {
            return trim((string) $class);
        })->filter(function($class) {
            return $class !== '';
        })->unique()->sort()->values();

        return response()->json(['classes' => $classes]);
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

            // Check if record already exists for this student, type, and date
            $existingRecord = BehaviorRecord::where('student_id', $validated['student_id'])
                ->where('type', $validated['type'])
                ->whereDate('date', $validated['date'])
                ->first();

            if ($existingRecord) {
                // Update existing record
                $existingRecord->update([
                    'points' => $validated['points'],
                    'section' => $section,
                    'description' => $validated['points'] > 0 ? '+' . $validated['points'] . ' Points' : $validated['points'] . ' Points',
                ]);
            } else {
                // Create new record
                BehaviorRecord::create([
                    'student_id' => $validated['student_id'],
                    'student_name' => $student->student_name,
                    'type' => $validated['type'],
                    'points' => $validated['points'],
                    'class' => $validated['class'],
                    'section' => $section,
                    'campus' => $validated['campus'],
                    'date' => $validated['date'],
                    'description' => $validated['points'] > 0 ? '+' . $validated['points'] . ' Points' : $validated['points'] . ' Points',
                    'recorded_by' => auth()->user()->name ?? auth()->user()->email ?? 'System',
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Behavior record saved successfully'
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

