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

class TeacherRemarksController extends Controller
{
    /**
     * Display the teacher remarks for practical test page with filters.
     */
    public function practical(Request $request): View
    {
        // Get filter values
        $filterCampus = $request->get('filter_campus');
        $filterClass = $request->get('filter_class');
        $filterSection = $request->get('filter_section');
        $filterSubject = $request->get('filter_subject');
        $filterTest = $request->get('filter_test');

        // Check if staff is logged in and is a teacher
        $staff = Auth::guard('staff')->user();
        $isTeacher = $staff && $staff->isTeacher();
        $teacherName = $isTeacher ? trim($staff->name ?? '') : null;

        // Get campuses for dropdown - only from Campus model (no fallback to avoid deleted items)
        $campuses = Campus::orderBy('campus_name', 'asc')->get();
        
        // If teacher, filter campuses by their assigned subjects/sections
        if ($isTeacher && $teacherName) {
            $teacherCampuses = Subject::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower($teacherName)])
                ->whereNotNull('campus')
                ->distinct()
                ->pluck('campus')
                ->merge(
                    Section::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower($teacherName)])
                        ->whereNotNull('campus')
                        ->distinct()
                        ->pluck('campus')
                )
                ->unique()
                ->sort()
                ->values();
            
            // Filter campuses to only show those assigned to teacher
            if ($teacherCampuses->isNotEmpty()) {
                $campuses = $campuses->filter(function($campus) use ($teacherCampuses) {
                    return $teacherCampuses->contains($campus->campus_name);
                });
            } else {
                // If teacher has no assigned campuses, show empty
                $campuses = collect();
            }
        }

        // Get classes - only from ClassModel (no fallback to avoid deleted items)
        $classesQuery = ClassModel::whereNotNull('class_name');
        
        // If teacher, filter classes by their assigned subjects/sections
        if ($isTeacher && $teacherName) {
            $assignedSubjects = Subject::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower($teacherName)])
                ->whereNotNull('class')
                ->distinct()
                ->pluck('class')
                ->map(fn($c) => trim($c))
                ->filter(fn($c) => !empty($c));
            
            $assignedSections = Section::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower($teacherName)])
                ->whereNotNull('class')
                ->distinct()
                ->pluck('class')
                ->map(fn($c) => trim($c))
                ->filter(fn($c) => !empty($c));
            
            $assignedClasses = $assignedSubjects->merge($assignedSections)->unique()->values();
            
            if ($assignedClasses->isNotEmpty()) {
                $classesQuery->where(function($q) use ($assignedClasses) {
                    foreach ($assignedClasses as $className) {
                        $q->orWhereRaw('LOWER(TRIM(class_name)) = ?', [strtolower(trim($className))]);
                    }
                });
            } else {
                // If teacher has no assigned classes, show empty
                $classesQuery->whereRaw('1 = 0');
            }
        }
        
        $classes = $classesQuery->distinct()->pluck('class_name')->sort()->values();

        // Get sections (will be filtered dynamically based on class selection)
        $sections = collect(); // Initialized as empty, will be filled via AJAX
        if ($filterClass) {
            $sectionsQuery = Section::whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($filterClass))])
                ->whereNotNull('name');
            
            // If teacher, filter by assigned sections
            if ($isTeacher && $teacherName) {
                $sectionsQuery->whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower($teacherName)]);
            }
            
            $sections = $sectionsQuery->distinct()->pluck('name')->sort()->values();
            
            // If no sections from Section table and teacher, try from Subject table
            if ($sections->isEmpty() && $isTeacher && $teacherName) {
                $sections = Subject::whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($filterClass))])
                    ->whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower($teacherName)])
                    ->whereNotNull('section')
                    ->distinct()
                    ->pluck('section')
                    ->sort()
                    ->values();
            }
        }

        // Get subjects (filtered by class and section - strict filtering)
        $subjectsQuery = Subject::query();
        
        // Class is required for subjects
        if ($filterClass) {
            $subjectsQuery->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($filterClass))]);
            
            // If teacher, filter by assigned subjects
            if ($isTeacher && $teacherName) {
                $subjectsQuery->whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower($teacherName)]);
            }
            
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

        // Get tests (filtered by other criteria if provided) - Only show tests where result_status = 1 (declared)
        $testsQuery = Test::where('result_status', 1);
        
        // If teacher, filter tests by their assigned subjects
        if ($isTeacher && $teacherName && $filterSubject) {
            // Verify that the subject is assigned to this teacher
            $assignedSubject = Subject::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower($teacherName)])
                ->whereRaw('LOWER(TRIM(subject_name)) = ?', [strtolower(trim($filterSubject))])
                ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($filterClass))])
                ->first();
            
            // If subject is not assigned to teacher, show no tests
            if (!$assignedSubject) {
                $testsQuery->whereRaw('1 = 0');
            }
        } elseif ($isTeacher && $teacherName && !$filterSubject && $filterClass) {
            // If no subject selected but class is selected, only show tests for subjects assigned to teacher
            $assignedSubjectNames = Subject::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower($teacherName)])
                ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($filterClass))])
                ->whereNotNull('subject_name')
                ->distinct()
                ->pluck('subject_name')
                ->map(fn($s) => strtolower(trim($s)))
                ->filter(fn($s) => !empty($s))
                ->values();
            
            if ($assignedSubjectNames->isNotEmpty()) {
                $testsQuery->where(function($q) use ($assignedSubjectNames) {
                    foreach ($assignedSubjectNames as $subjectName) {
                        $q->orWhereRaw('LOWER(TRIM(subject)) = ?', [$subjectName]);
                    }
                });
            } else {
                // If teacher has no assigned subjects for this class, show no tests
                $testsQuery->whereRaw('1 = 0');
            }
        }
        
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
        $tests = $testsQuery->whereNotNull('test_name')->distinct()->pluck('test_name')->sort()->values();

        // Query students based on filters and load their marks
        $students = collect();
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
            
            // Load marks for each student if test is selected
            if ($filterTest && $students->count() > 0) {
                $campusName = is_object($filterCampus) ? ($filterCampus->campus_name ?? '') : $filterCampus;
                $marks = StudentMark::where('test_name', $filterTest)
                    ->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campusName))])
                    ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($filterClass))])
                    ->when($filterSection, function($query) use ($filterSection) {
                        return $query->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($filterSection))]);
                    })
                    ->when($filterSubject, function($query) use ($filterSubject) {
                        return $query->whereRaw('LOWER(TRIM(subject)) = ?', [strtolower(trim($filterSubject))]);
                    })
                    ->get()
                    ->keyBy('student_id');
                
                // Attach marks to students
                $students = $students->map(function($student) use ($marks) {
                    $student->mark = $marks->get($student->id);
                    return $student;
                });
            }
        }

        return view('test.teacher-remarks.practical', compact(
            'campuses',
            'classes',
            'sections',
            'subjects',
            'tests',
            'students',
            'filterCampus',
            'filterClass',
            'filterSection',
            'filterSubject',
            'filterTest'
        ));
    }

    /**
     * Display the teacher remarks for combine result page with filters.
     */
    public function combined(Request $request): View
    {
        // Get filter values
        $filterCampus = $request->get('filter_campus');
        $filterSession = $request->get('filter_session');
        $filterClassSection = $request->get('filter_class_section'); // Combined Class/Section
        
        // Parse Class/Section if provided (format: "Class - Section" or just "Class")
        $filterClass = null;
        $filterSection = null;
        if ($filterClassSection) {
            $parts = explode(' - ', $filterClassSection);
            $filterClass = $parts[0] ?? null;
            $filterSection = $parts[1] ?? null;
        }

        // Check if staff is logged in and is a teacher
        $staff = Auth::guard('staff')->user();
        $isTeacher = $staff && $staff->isTeacher();
        $teacherName = $isTeacher ? strtolower(trim($staff->name ?? '')) : null;
        
        // Get campuses for dropdown - filter by teacher's assigned campuses if teacher
        if ($isTeacher && $teacherName) {
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

        // Get sessions
        $sessions = Test::whereNotNull('session')->distinct()->pluck('session')->sort()->values();
        
        if ($sessions->isEmpty()) {
            $sessions = collect(['2024-2025', '2025-2026', '2026-2027']);
        }

        // Get Class/Section combinations - only from existing classes (not deleted) and filter by teacher if teacher
        $classSectionOptions = collect();
        
        // Get classes - only from ClassModel (not deleted) and filter by teacher if teacher
        if ($isTeacher && $teacherName) {
            // Get classes from teacher's assigned subjects
            $assignedSubjects = Subject::whereRaw('LOWER(TRIM(teacher)) = ?', [$teacherName])
                ->whereNotNull('class')
                ->get();
            
            // Get classes from teacher's assigned sections
            $assignedSections = Section::whereRaw('LOWER(TRIM(teacher)) = ?', [$teacherName])
                ->whereNotNull('class')
                ->get();
            
            // Merge classes from both sources and get unique class names
            $allClasses = $assignedSubjects->pluck('class')
                ->merge($assignedSections->pluck('class'))
                ->map(function($class) {
                    return trim($class);
                })
                ->filter(function($class) {
                    return !empty($class);
                })
                ->unique()
                ->values();
            
            // Verify these classes exist in ClassModel (not deleted)
            $existingClassNames = ClassModel::whereNotNull('class_name')
                ->pluck('class_name')
                ->map(fn($name) => strtolower(trim($name)))
                ->toArray();
            
            // Filter to only include classes that exist in ClassModel
            $classes = $allClasses->filter(function($className) use ($existingClassNames) {
                return in_array(strtolower(trim($className)), $existingClassNames);
            })->sort()->values();
        } else {
            // For non-teachers, get all classes from ClassModel (not deleted)
            $classes = ClassModel::whereNotNull('class_name')->distinct()->pluck('class_name')->sort()->values();
        }
        
        // Build Class/Section combinations
        foreach ($classes as $className) {
            $sectionsQuery = Section::whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($className))])
                ->whereNotNull('name');
            
            // If teacher, filter by assigned sections
            if ($isTeacher && $teacherName) {
                $sectionsQuery->whereRaw('LOWER(TRIM(teacher)) = ?', [$teacherName]);
            }
            
            $sections = $sectionsQuery->distinct()->pluck('name')->sort()->values();
            
            // If no sections from Section table and teacher, try from Subject table
            if ($sections->isEmpty() && $isTeacher && $teacherName) {
                $sections = Subject::whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($className))])
                    ->whereRaw('LOWER(TRIM(teacher)) = ?', [$teacherName])
                    ->whereNotNull('section')
                    ->distinct()
                    ->pluck('section')
                    ->sort()
                    ->values();
            } elseif ($sections->isEmpty() && !$isTeacher) {
                // For non-teachers, try from Subject table if no sections in Section table
                $sections = Subject::whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($className))])
                    ->whereNotNull('section')
                    ->distinct()
                    ->pluck('section')
                    ->sort()
                    ->values();
            }
            
            if ($sections->isEmpty()) {
                // If no sections, just add the class
                $classSectionOptions->push($className);
            } else {
                // Add class with each section
                foreach ($sections as $sectionName) {
                    $classSectionOptions->push($className . ' - ' . $sectionName);
                }
            }
        }

        // Query students based on filters
        $students = collect();
        if ($filterCampus || $filterClass) {
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
            
            // Load combined marks for each student (aggregate from all tests in the session)
            if ($filterSession && $students->count() > 0) {
                $campusName = is_object($filterCampus) ? ($filterCampus->campus_name ?? '') : $filterCampus;
                
                // Get all tests in the session for this campus/class/section
                $testsQuery = Test::where('session', $filterSession)
                    ->where('result_status', 1); // Only declared results
                
                if ($campusName) {
                    $testsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campusName))]);
                }
                if ($filterClass) {
                    $testsQuery->whereRaw('LOWER(TRIM(for_class)) = ?', [strtolower(trim($filterClass))]);
                }
                if ($filterSection) {
                    $testsQuery->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($filterSection))]);
                }
                
                $testNames = $testsQuery->distinct()->pluck('test_name');
                
                // Aggregate marks for each student
                $students = $students->map(function($student) use ($testNames, $campusName, $filterClass, $filterSection) {
                    $marks = StudentMark::where('student_id', $student->id)
                        ->whereIn('test_name', $testNames)
                        ->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campusName))])
                        ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($filterClass))])
                        ->when($filterSection, function($query) use ($filterSection) {
                            return $query->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($filterSection))]);
                        })
                        ->get();
                    
                    // Calculate combined totals
                    $combinedTotal = $marks->sum('total_marks') ?? 0;
                    $combinedObtained = $marks->sum('marks_obtained') ?? 0;
                    
                    // Get teacher remarks for combined result (from the most recent mark or create a combined one)
                    $combinedRemark = StudentMark::where('student_id', $student->id)
                        ->where('test_name', 'COMBINED_RESULT')
                        ->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campusName))])
                        ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($filterClass))])
                        ->when($filterSection, function($query) use ($filterSection) {
                            return $query->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($filterSection))]);
                        })
                        ->first();
                    
                    $student->combinedTotal = $combinedTotal;
                    $student->combinedObtained = $combinedObtained;
                    $student->combinedRemark = $combinedRemark;
                    
                    return $student;
                });
            }
        }

        return view('test.teacher-remarks.combined', compact(
            'campuses',
            'sessions',
            'classSectionOptions',
            'students',
            'filterCampus',
            'filterSession',
            'filterClassSection'
        ));
    }

    /**
     * Get sections for teacher remarks (AJAX).
     */
    public function getSections(Request $request): JsonResponse
    {
        $class = $request->get('class');
        if (!$class) {
            return response()->json(['sections' => []]);
        }
        
        // Check if staff is logged in and is a teacher
        $staff = Auth::guard('staff')->user();
        $isTeacher = $staff && $staff->isTeacher();
        $teacherName = $isTeacher ? trim($staff->name ?? '') : null;
        
        $sectionsQuery = Section::whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))])
            ->whereNotNull('name');
        
        // If teacher, filter by assigned sections
        if ($isTeacher && $teacherName) {
            $sectionsQuery->whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower($teacherName)]);
        }
        
        $sections = $sectionsQuery->distinct()->pluck('name')->sort()->values();
        
        // If no sections from Section table and teacher, try from Subject table
        if ($sections->isEmpty() && $isTeacher && $teacherName) {
            $sections = Subject::whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))])
                ->whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower($teacherName)])
                ->whereNotNull('section')
                ->distinct()
                ->pluck('section')
                ->sort()
                ->values();
        }
        
        return response()->json(['sections' => $sections]);
    }

    /**
     * Get subjects for teacher remarks (AJAX).
     */
    public function getSubjects(Request $request): JsonResponse
    {
        $campus = $request->get('campus');
        $class = $request->get('class');
        $section = $request->get('section');
        
        // Class is required - if not provided, return empty
        if (!$class) {
            return response()->json(['subjects' => []]);
        }
        
        // Check if staff is logged in and is a teacher
        $staff = Auth::guard('staff')->user();
        $isTeacher = $staff && $staff->isTeacher();
        $teacherName = $isTeacher ? trim($staff->name ?? '') : null;
        
        $subjectsQuery = Subject::query();
        
        // Always filter by class
        $subjectsQuery->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))]);
        
        // If teacher, filter by assigned subjects
        if ($isTeacher && $teacherName) {
            $subjectsQuery->whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower($teacherName)]);
        }
        
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
     * Get tests for teacher remarks (AJAX) - Only declared results.
     */
    public function getTests(Request $request): JsonResponse
    {
        $campus = $request->get('campus');
        $class = $request->get('class');
        $section = $request->get('section');
        $subject = $request->get('subject');
        
        // Class is required - if not provided, return empty
        if (!$class) {
            return response()->json(['tests' => []]);
        }
        
        // Check if staff is logged in and is a teacher
        $staff = Auth::guard('staff')->user();
        $isTeacher = $staff && $staff->isTeacher();
        $teacherName = $isTeacher ? trim($staff->name ?? '') : null;
        
        $testsQuery = Test::where('result_status', 1); // Only declared results
        
        // If teacher, filter tests by their assigned subjects
        if ($isTeacher && $teacherName && $subject) {
            // Verify that the subject is assigned to this teacher
            $assignedSubject = Subject::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower($teacherName)])
                ->whereRaw('LOWER(TRIM(subject_name)) = ?', [strtolower(trim($subject))])
                ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))])
                ->first();
            
            // If subject is not assigned to teacher, show no tests
            if (!$assignedSubject) {
                $testsQuery->whereRaw('1 = 0');
            }
        } elseif ($isTeacher && $teacherName && !$subject) {
            // If no subject selected, only show tests for subjects assigned to teacher
            $assignedSubjectNames = Subject::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower($teacherName)])
                ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))])
                ->whereNotNull('subject_name')
                ->distinct()
                ->pluck('subject_name')
                ->map(fn($s) => strtolower(trim($s)))
                ->filter(fn($s) => !empty($s))
                ->values();
            
            if ($assignedSubjectNames->isNotEmpty()) {
                $testsQuery->where(function($q) use ($assignedSubjectNames) {
                    foreach ($assignedSubjectNames as $subjectName) {
                        $q->orWhereRaw('LOWER(TRIM(subject)) = ?', [$subjectName]);
                    }
                });
            } else {
                // If teacher has no assigned subjects for this class, show no tests
                $testsQuery->whereRaw('1 = 0');
            }
        }
        
        // Always filter by class
        $testsQuery->whereRaw('LOWER(TRIM(for_class)) = ?', [strtolower(trim($class))]);
        
        if ($campus) {
            $testsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
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
     * Save teacher remarks for students.
     */
    public function saveRemarks(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'test_name' => ['required', 'string'],
            'campus' => ['required', 'string'],
            'class' => ['required', 'string'],
            'section' => ['nullable', 'string'],
            'subject' => ['nullable', 'string'],
            'remarks' => ['required', 'array'],
            'remarks.*' => ['nullable', 'string'],
        ]);

        // Save or update remarks for each student
        foreach ($validated['remarks'] as $studentId => $remark) {
            if ($remark) {
                StudentMark::updateOrCreate(
                    [
                        'student_id' => $studentId,
                        'test_name' => $validated['test_name'],
                        'campus' => $validated['campus'],
                        'class' => $validated['class'],
                        'section' => $validated['section'] ?? null,
                        'subject' => $validated['subject'] ?? null,
                    ],
                    [
                        'teacher_remarks' => $remark,
                    ]
                );
            }
        }

        return redirect()
            ->route('test.teacher-remarks.practical', [
                'filter_campus' => $validated['campus'],
                'filter_class' => $validated['class'],
                'filter_section' => $validated['section'] ?? '',
                'filter_test' => $validated['test_name'],
                'filter_subject' => $validated['subject'] ?? '',
            ])
            ->with('success', 'Teacher remarks saved successfully!');
    }

    /**
     * Get class/section combinations for combined remarks (AJAX).
     */
    public function getClassSections(Request $request): JsonResponse
    {
        $campus = $request->get('campus');
        
        // Check if staff is logged in and is a teacher
        $staff = Auth::guard('staff')->user();
        $isTeacher = $staff && $staff->isTeacher();
        $teacherName = $isTeacher ? strtolower(trim($staff->name ?? '')) : null;
        
        // Get classes - only from ClassModel (not deleted) and filter by teacher if teacher
        if ($isTeacher && $teacherName) {
            // Get classes from teacher's assigned subjects
            $assignedSubjects = Subject::whereRaw('LOWER(TRIM(teacher)) = ?', [$teacherName])
                ->whereNotNull('class')
                ->get();
            
            // Get classes from teacher's assigned sections
            $assignedSections = Section::whereRaw('LOWER(TRIM(teacher)) = ?', [$teacherName])
                ->whereNotNull('class')
                ->get();
            
            // Merge classes from both sources and get unique class names
            $allClasses = $assignedSubjects->pluck('class')
                ->merge($assignedSections->pluck('class'))
                ->map(function($class) {
                    return trim($class);
                })
                ->filter(function($class) {
                    return !empty($class);
                })
                ->unique()
                ->values();
            
            // Verify these classes exist in ClassModel (not deleted)
            $existingClassNames = ClassModel::whereNotNull('class_name')
                ->pluck('class_name')
                ->map(fn($name) => strtolower(trim($name)))
                ->toArray();
            
            // Filter to only include classes that exist in ClassModel
            $classes = $allClasses->filter(function($className) use ($existingClassNames) {
                return in_array(strtolower(trim($className)), $existingClassNames);
            })->sort()->values();
        } else {
            // For non-teachers, get all classes from ClassModel (not deleted)
            $classes = ClassModel::whereNotNull('class_name')->distinct()->pluck('class_name')->sort()->values();
        }
        
        $classSectionOptions = collect();
        
        foreach ($classes as $className) {
            $sectionsQuery = Section::whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($className))])
                ->whereNotNull('name');
            
            // If teacher, filter by assigned sections
            if ($isTeacher && $teacherName) {
                $sectionsQuery->whereRaw('LOWER(TRIM(teacher)) = ?', [$teacherName]);
            }
            
            $sections = $sectionsQuery->distinct()->pluck('name')->sort()->values();
            
            // If no sections from Section table and teacher, try from Subject table
            if ($sections->isEmpty() && $isTeacher && $teacherName) {
                $sections = Subject::whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($className))])
                    ->whereRaw('LOWER(TRIM(teacher)) = ?', [$teacherName])
                    ->whereNotNull('section')
                    ->distinct()
                    ->pluck('section')
                    ->sort()
                    ->values();
            } elseif ($sections->isEmpty() && !$isTeacher) {
                // For non-teachers, try from Subject table if no sections in Section table
                $sections = Subject::whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($className))])
                    ->whereNotNull('section')
                    ->distinct()
                    ->pluck('section')
                    ->sort()
                    ->values();
            }
            
            if ($sections->isEmpty()) {
                $classSectionOptions->push($className);
            } else {
                foreach ($sections as $sectionName) {
                    $classSectionOptions->push($className . ' - ' . $sectionName);
                }
            }
        }
        
        return response()->json(['classSections' => $classSectionOptions]);
    }

    /**
     * Save teacher remarks for combined result.
     */
    public function saveCombinedRemarks(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'campus' => ['required', 'string'],
            'session' => ['required', 'string'],
            'class_section' => ['required', 'string'],
            'remarks' => ['required', 'array'],
            'remarks.*' => ['nullable', 'string'],
        ]);

        // Parse Class/Section
        $parts = explode(' - ', $validated['class_section']);
        $class = $parts[0] ?? null;
        $section = $parts[1] ?? null;

        // Save or update remarks for each student
        foreach ($validated['remarks'] as $studentId => $remark) {
            if ($remark) {
                StudentMark::updateOrCreate(
                    [
                        'student_id' => $studentId,
                        'test_name' => 'COMBINED_RESULT',
                        'campus' => $validated['campus'],
                        'class' => $class,
                        'section' => $section ?? null,
                        'subject' => null,
                    ],
                    [
                        'teacher_remarks' => $remark,
                    ]
                );
            }
        }

        return redirect()
            ->route('test.teacher-remarks.combined', [
                'filter_campus' => $validated['campus'],
                'filter_session' => $validated['session'],
                'filter_class_section' => $validated['class_section'],
            ])
            ->with('success', 'Teacher remarks saved successfully!');
    }
}

