<?php

namespace App\Http\Controllers;

use App\Models\Test;
use App\Models\Student;
use App\Models\ClassModel;
use App\Models\Section;
use App\Models\Subject;
use App\Models\Campus;
use App\Models\StudentMark;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;

class MarksEntryController extends Controller
{
    /**
     * Display the marks entry page with filters.
     */
    public function index(Request $request): View
    {
        // Get filter values
        $filterCampus = $request->get('filter_campus');
        $filterClass = $request->get('filter_class');
        $filterSection = $request->get('filter_section');
        $filterTest = $request->get('filter_test');
        $filterSubject = $request->get('filter_subject');

        // Get classes - filter by teacher's assigned classes if teacher
        $classes = collect();
        $staff = Auth::guard('staff')->user();
        
        // Get campuses for dropdown - filter by teacher's assigned campuses if teacher
        if ($staff && $staff->isTeacher()) {
            $teacherName = strtolower(trim($staff->name ?? ''));
            
            if (!empty($teacherName)) {
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
                // If teacher name is empty, show no campuses
                $campuses = collect();
            }
        } else {
            // For non-teachers (admin, staff, etc.), get all campuses
            $campuses = Campus::orderBy('campus_name', 'asc')->get();
            if ($campuses->isEmpty()) {
                $campusesFromClasses = ClassModel::whereNotNull('campus')->distinct()->pluck('campus');
                $campusesFromSections = Section::whereNotNull('campus')->distinct()->pluck('campus');
                $campusesFromSubjects = Subject::whereNotNull('campus')->distinct()->pluck('campus');
                $allCampuses = $campusesFromClasses->merge($campusesFromSections)->merge($campusesFromSubjects)->unique()->sort();
                $campuses = $allCampuses->map(function($campus) {
                    return (object)['campus_name' => $campus];
                });
            }
        }
        
        if ($staff && $staff->isTeacher()) {
            // Get classes from teacher's assigned subjects
            $assignedSubjects = Subject::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($staff->name ?? ''))])
                ->get();
            
            // Get classes from teacher's assigned sections
            $assignedSections = Section::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($staff->name ?? ''))])
                ->get();
            
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
            $classes = ClassModel::whereNotNull('class_name')->distinct()->pluck('class_name')->sort()->values();
            
            if ($classes->isEmpty()) {
                $classes = collect(['Nursery', 'KG', '1st', '2nd', '3rd', '4th', '5th', '6th', '7th', '8th', '9th', '10th', '11th', '12th']);
            }
        }

        // Get sections (will be filtered dynamically based on class selection) - filter by teacher's assigned subjects if teacher
        $sections = collect();
        if ($filterClass) {
            if ($staff && $staff->isTeacher()) {
                // Get sections from teacher's assigned subjects for this class
                $assignedSubjects = Subject::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($staff->name ?? ''))])
                    ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($filterClass))])
                    ->get();
                
                // Get sections from teacher's assigned sections for this class
                $assignedSections = Section::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($staff->name ?? ''))])
                    ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($filterClass))])
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
                $sections = Section::whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($filterClass))])
                    ->whereNotNull('name')
                    ->distinct()
                    ->pluck('name')
                    ->sort()
                    ->values();
                
                if ($sections->isEmpty()) {
                    $sections = Subject::whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($filterClass))])
                        ->whereNotNull('section')
                        ->distinct()
                        ->pluck('section')
                        ->sort()
                        ->values();
                }
            }
        }

        // Get tests - ONLY show tests where result_status = 1 (declared)
        $testsQuery = Test::where('result_status', 1);
        
        $campusName = is_object($filterCampus) ? ($filterCampus->campus_name ?? '') : $filterCampus;
        if ($campusName) {
            $testsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campusName))]);
        }
        if ($filterClass) {
            $testsQuery->whereRaw('LOWER(TRIM(for_class)) = ?', [strtolower(trim($filterClass))]);
        }
        if ($filterSection) {
            $testsQuery->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($filterSection))]);
        }
        if ($filterSubject) {
            $testsQuery->whereRaw('LOWER(TRIM(subject)) = ?', [strtolower(trim($filterSubject))]);
        }
        
        $tests = $testsQuery->whereNotNull('test_name')
            ->distinct()
            ->pluck('test_name')
            ->sort()
            ->values();

        // Get subjects (filtered by class and section - strict filtering)
        $subjectsQuery = Subject::query();
        
        // Class is required for subjects
        if ($filterClass) {
            $subjectsQuery->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($filterClass))]);
            
            $campusName = is_object($filterCampus) ? ($filterCampus->campus_name ?? '') : $filterCampus;
            if ($campusName) {
                $subjectsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campusName))]);
            }
            
            // If section is provided, MUST filter by section (strict filtering)
            if ($filterSection) {
                $subjectsQuery->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($filterSection))]);
            }
            
            $subjects = $subjectsQuery->whereNotNull('subject_name')
                ->distinct()
                ->pluck('subject_name')
                ->sort()
                ->values();
        } else {
            // If no class selected, show empty subjects
            $subjects = collect();
        }

        // Query students based on filters
        $students = collect();
        $existingMarks = collect();
        
        if ($filterCampus || $filterClass || $filterSection) {
            $studentsQuery = Student::query();
            
            $campusName = is_object($filterCampus) ? ($filterCampus->campus_name ?? '') : $filterCampus;
            if ($campusName) {
                $studentsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campusName))]);
            }
            if ($filterClass) {
                $studentsQuery->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($filterClass))]);
            }
            if ($filterSection) {
                $studentsQuery->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($filterSection))]);
            }
            
            $students = $studentsQuery->orderBy('student_name')->get();
            
            // Load existing marks if test is selected
            if ($filterTest && $students->isNotEmpty()) {
                $studentIds = $students->pluck('id');
                
                $marksQuery = StudentMark::where('test_name', $filterTest)
                    ->whereIn('student_id', $studentIds);
                
                $campusName = is_object($filterCampus) ? ($filterCampus->campus_name ?? '') : $filterCampus;
                if ($campusName) {
                    $marksQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campusName))]);
                }
                if ($filterClass) {
                    $marksQuery->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($filterClass))]);
                }
                if ($filterSection) {
                    $marksQuery->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($filterSection))]);
                }
                if ($filterSubject) {
                    $marksQuery->whereRaw('LOWER(TRIM(subject)) = ?', [strtolower(trim($filterSubject))]);
                }
                
                $existingMarks = $marksQuery->get()->keyBy('student_id');
            }
        }

        return view('test.marks-entry', compact(
            'campuses',
            'classes',
            'sections',
            'tests',
            'subjects',
            'students',
            'existingMarks',
            'filterCampus',
            'filterClass',
            'filterSection',
            'filterTest',
            'filterSubject'
        ));
    }

    /**
     * Get sections for marks entry (AJAX).
     */
    public function getSections(Request $request): JsonResponse
    {
        $class = $request->get('class');
        
        if (!$class) {
            return response()->json(['sections' => []]);
        }
        
        $staff = Auth::guard('staff')->user();
        $sections = collect();
        
        // Filter by teacher's assigned subjects and sections if teacher
        if ($staff && $staff->isTeacher()) {
            // Get sections from teacher's assigned subjects for this class
            $assignedSubjects = Subject::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($staff->name ?? ''))])
                ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))])
                ->get();
            
            // Get sections from teacher's assigned sections for this class
            $assignedSections = Section::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($staff->name ?? ''))])
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
            $sections = Section::whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))])
                ->whereNotNull('name')
                ->distinct()
                ->pluck('name')
                ->sort()
                ->values();
                
            if ($sections->isEmpty()) {
                $sections = Subject::whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))])
                    ->whereNotNull('section')
                    ->distinct()
                    ->pluck('section')
                    ->sort()
                    ->values();
            }
        }
        
        return response()->json(['sections' => $sections]);
    }

    /**
     * Get tests for marks entry (AJAX) - Only declared results.
     */
    public function getTests(Request $request): JsonResponse
    {
        $campus = $request->get('campus');
        $class = $request->get('class');
        $section = $request->get('section');
        $subject = $request->get('subject');
        
        $testsQuery = Test::where('result_status', 1);
        
        if ($campus) {
            $testsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
        }
        if ($class) {
            $testsQuery->whereRaw('LOWER(TRIM(for_class)) = ?', [strtolower(trim($class))]);
        }
        if ($section) {
            $testsQuery->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($section))]);
        }
        if ($subject) {
            $testsQuery->whereRaw('LOWER(TRIM(subject)) = ?', [strtolower(trim($subject))]);
        }
        
        $tests = $testsQuery->whereNotNull('test_name')
            ->distinct()
            ->pluck('test_name')
            ->sort()
            ->values();
        
        return response()->json(['tests' => $tests]);
    }

    /**
     * Get subjects for marks entry (AJAX).
     */
    public function getSubjects(Request $request): JsonResponse
    {
        $campus = $request->get('campus');
        $class = $request->get('class');
        $section = $request->get('section');
        
        $subjectsQuery = Subject::query();
        
        // Class is required - if not provided, return empty
        if (!$class) {
            return response()->json(['subjects' => []]);
        }
        
        // Always filter by class
        $subjectsQuery->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))]);
        
        // If campus is provided, filter by campus
        if ($campus) {
            $subjectsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
        }
        
        // If section is provided, MUST filter by section (strict filtering)
        if ($section) {
            $subjectsQuery->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($section))]);
        }
        
        $subjects = $subjectsQuery->whereNotNull('subject_name')
            ->distinct()
            ->pluck('subject_name')
            ->sort()
            ->values();
        
        return response()->json(['subjects' => $subjects]);
    }

    /**
     * Save marks for students.
     */
    public function save(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'test_id' => ['required', 'string'],
            'campus' => ['required', 'string'],
            'class' => ['required', 'string'],
            'section' => ['nullable', 'string'],
            'subject' => ['nullable', 'string'],
            'marks' => ['required', 'array'],
            'marks.*.obtained' => ['nullable', 'numeric', 'min:0'],
            'marks.*.total' => ['nullable', 'numeric', 'min:0'],
            'marks.*.passing' => ['nullable', 'numeric', 'min:0'],
        ]);

        // Save or update marks for each student
        foreach ($validated['marks'] as $studentId => $markData) {
            StudentMark::updateOrCreate(
                [
                    'student_id' => $studentId,
                    'test_name' => $validated['test_id'],
                    'campus' => $validated['campus'],
                    'class' => $validated['class'],
                    'section' => $validated['section'] ?? null,
                    'subject' => $validated['subject'] ?? null,
                ],
                [
                    'marks_obtained' => $markData['obtained'] ?? null,
                    'total_marks' => $markData['total'] ?? null,
                    'passing_marks' => $markData['passing'] ?? null,
                ]
            );
        }
        
        return redirect()
            ->route('test.marks-entry', [
                'filter_campus' => $validated['campus'],
                'filter_class' => $validated['class'],
                'filter_section' => $validated['section'] ?? '',
                'filter_test' => $validated['test_id'],
                'filter_subject' => $validated['subject'] ?? '',
            ])
            ->with('success', 'Marks saved successfully!');
    }
}

