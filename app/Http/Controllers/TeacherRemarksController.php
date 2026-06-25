<?php

namespace App\Http\Controllers;

use App\Models\Test;
use App\Models\Student;
use App\Models\ClassModel;
use App\Models\Section;
use App\Models\Subject;
use App\Models\Campus;
use App\Models\GeneralSetting;
use App\Models\Staff;
use App\Models\StudentMark;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Collection;
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

        $staff = Auth::guard('staff')->user();
        $isStaffTeacher = $staff && $staff->isTeacher();
        $campusName = trim((string) (is_object($filterCampus) ? ($filterCampus->campus_name ?? '') : ($filterCampus ?? '')));

        $classes = collect();

        if ($isStaffTeacher && $staff) {
            $campuses = $this->campusesForStaffViewer($staff);
            if ($campusName !== '') {
                $classes = $this->classesForCampusSelection($staff, $campusName);
            }
        } else {
            $campuses = Campus::orderBy('campus_name', 'asc')->get();
            if ($campuses->isEmpty()) {
                $campusesFromClasses = ClassModel::whereNotNull('campus')->distinct()->pluck('campus');
                $campusesFromSections = Section::whereNotNull('campus')->distinct()->pluck('campus');
                $campusesFromSubjects = Subject::whereNotNull('campus')->distinct()->pluck('campus');
                $allCampuses = $campusesFromClasses->merge($campusesFromSections)->merge($campusesFromSubjects)->unique()->sort();
                $campuses = $allCampuses->map(fn ($campus) => (object) ['campus_name' => $campus]);
            }

            if ($campusName !== '') {
                $classes = ClassModel::whereNotNull('class_name')
                    ->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower($campusName)])
                    ->distinct()
                    ->pluck('class_name')
                    ->sort()
                    ->values();
            }
        }

        $sections = collect();
        if ($filterClass) {
            if ($isStaffTeacher && $staff) {
                $sections = $this->sectionsForClassSelection($staff, $filterClass, $campusName ?: null);
            } else {
                $sections = $this->sectionsForClassAtCampus($filterClass, $campusName ?: null);
            }
        }

        // Get subjects (filtered by class and section)
        $subjectsQuery = Subject::query();

        if ($filterClass) {
            $subjectsQuery->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($filterClass))]);

            if ($isStaffTeacher && $staff) {
                $staff->scopeQueryToTeacherAssignments($subjectsQuery);
                $staff->scopeQueryToFlexibleCampus($subjectsQuery, $staff->campusForTeachingAssignments($campusName ?: null));
            }

            if ($campusName !== '') {
                $subjectsQuery->where(function ($q) use ($campusName) {
                    $campusKey = strtolower(trim($campusName));
                    $q->whereRaw('LOWER(TRIM(COALESCE(campus, ""))) = ?', [$campusKey])
                        ->orWhereRaw('TRIM(COALESCE(campus, "")) = ?', ['']);
                });
            }

            if ($filterSection) {
                $subjectsQuery->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($filterSection))]);
            }

            $subjects = $subjectsQuery->whereNotNull('subject_name')
                ->distinct()
                ->pluck('subject_name')
                ->sort()
                ->values();
        } else {
            $subjects = collect();
        }

        // Get tests (show before/after declaration).
        $testsQuery = Test::query();

        if ($isStaffTeacher && $staff && $filterSubject) {
            $allowedSubjects = $staff->uploadableSubjectNamesForMarks(
                $campusName ?: null,
                $filterClass,
                $filterSection
            );
            $subjectKey = strtolower(trim((string) $filterSubject));
            if (! $allowedSubjects->contains(fn ($name) => strtolower(trim((string) $name)) === $subjectKey)) {
                $testsQuery->whereRaw('1 = 0');
            }
        } elseif ($isStaffTeacher && $staff && $filterClass && ! $filterSubject) {
            $assignedSubjectNames = $staff->uploadableSubjectNamesForMarks(
                $campusName ?: null,
                $filterClass,
                $filterSection
            )->map(fn ($s) => strtolower(trim((string) $s)))->filter()->values();

            if ($assignedSubjectNames->isNotEmpty()) {
                $testsQuery->where(function ($q) use ($assignedSubjectNames) {
                    foreach ($assignedSubjectNames as $subjectName) {
                        $q->orWhereRaw('LOWER(TRIM(subject)) = ?', [$subjectName]);
                    }
                });
            } else {
                $testsQuery->whereRaw('1 = 0');
            }
        }
        if ($campusName) {
            $testsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campusName))]);
        }
        if ($filterClass) {
            $testsQuery->whereRaw('LOWER(TRIM(for_class)) = ?', [strtolower(trim($filterClass))]);
        }
        if ($filterSection) {
            $sectionLower = strtolower(trim($filterSection));
            $testsQuery->where(function ($q) use ($sectionLower) {
                $q->whereNull('section')
                    ->orWhereRaw('TRIM(section) = ?', [''])
                    ->orWhereRaw('LOWER(TRIM(section)) = ?', [$sectionLower]);
            });
        }
        if ($filterSubject) {
            $subjectLower = strtolower(trim($filterSubject));
            $testsQuery->where(function ($q) use ($subjectLower) {
                $q->whereNull('subject')
                    ->orWhereRaw('TRIM(subject) = ?', [''])
                    ->orWhereRaw('LOWER(TRIM(subject)) = ?', [$subjectLower]);
            });
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
            'filterTest',
            'isStaffTeacher'
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

        // Get sessions (from tests + Running Session from General Settings, no static list)
        $settings = GeneralSetting::getSettings();
        $runningSession = $settings->running_session ? trim($settings->running_session) : null;
        $sessions = Test::whereNotNull('session')->distinct()->pluck('session')->sort()->values();
        if ($sessions->isEmpty() && $runningSession) {
            $sessions = collect([$runningSession]);
        } elseif ($runningSession && !$sessions->contains($runningSession)) {
            $sessions = $sessions->prepend($runningSession)->values();
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
                $testsQuery = Test::where('session', $filterSession);
                
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

        $staff = Auth::guard('staff')->user();
        $isStaffCombinedUser = Auth::guard('staff')->check();
        $canEditCombinedRemarks = !$isStaffCombinedUser;
        if ($isStaffCombinedUser && $staff && $filterClass) {
            $campusForPermission = is_object($filterCampus)
                ? ($filterCampus->campus_name ?? '')
                : (string) ($filterCampus ?? '');
            $canEditCombinedRemarks = $staff->canEditCombinedResultRemarks(
                $campusForPermission,
                $filterClass,
                $filterSection
            );
        }

        return view('test.teacher-remarks.combined', compact(
            'campuses',
            'sessions',
            'classSectionOptions',
            'students',
            'filterCampus',
            'filterSession',
            'filterClassSection',
            'canEditCombinedRemarks',
            'isStaffCombinedUser'
        ));
    }

    /**
     * Get classes by campus for teacher remarks (AJAX).
     */
    public function getClassesByCampus(Request $request): JsonResponse
    {
        $campus = $request->get('campus');
        if (! $campus || ! is_string($campus) || trim($campus) === '') {
            return response()->json(['classes' => []]);
        }

        $campus = trim($campus);
        $staff = Auth::guard('staff')->user();

        if ($staff && $staff->isTeacher()) {
            if (! $this->staffCanUseCampus($staff, $campus)) {
                return response()->json(['classes' => []]);
            }

            $classes = $this->classesForCampusSelection($staff, $campus);

            return response()->json(['classes' => $classes->values()->all()]);
        }

        $classes = ClassModel::whereNotNull('class_name')
            ->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower($campus)])
            ->distinct()
            ->pluck('class_name')
            ->sort()
            ->values();

        return response()->json(['classes' => $classes]);
    }

    /**
     * Get sections for teacher remarks (AJAX).
     */
    public function getSections(Request $request): JsonResponse
    {
        $class = $request->get('class');
        $campus = $request->get('campus');

        if (! $class) {
            return response()->json(['sections' => []]);
        }

        $staff = Auth::guard('staff')->user();

        if ($staff && $staff->isTeacher()) {
            if ($campus && ! $this->staffCanUseCampus($staff, $campus)) {
                return response()->json(['sections' => []]);
            }

            $sections = $this->sectionsForClassSelection($staff, $class, $campus);

            return response()->json(['sections' => $sections->values()->all()]);
        }

        $sections = $this->sectionsForClassAtCampus($class, $campus);

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

        $staff = Auth::guard('staff')->user();

        if ($staff && $staff->isTeacher()) {
            if ($campus && ! $this->staffCanUseCampus($staff, $campus)) {
                return response()->json(['subjects' => []]);
            }

            $subjects = $staff->uploadableSubjectNamesForMarks($campus, $class, $section);

            return response()->json(['subjects' => $subjects->values()->all()]);
        }

        $subjectsQuery = Subject::query();
        $subjectsQuery->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))]);

        if ($campus) {
            $campusKey = strtolower(trim($campus));
            $subjectsQuery->where(function ($q) use ($campusKey) {
                $q->whereRaw('LOWER(TRIM(COALESCE(campus, ""))) = ?', [$campusKey])
                    ->orWhereRaw('TRIM(COALESCE(campus, "")) = ?', ['']);
            });
        }

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
     * Get tests for teacher remarks (AJAX).
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

        $staff = Auth::guard('staff')->user();
        $isStaffTeacher = $staff && $staff->isTeacher();

        $testsQuery = Test::query();

        if ($isStaffTeacher && $subject) {
            $allowedSubjects = $staff->uploadableSubjectNamesForMarks($campus, $class, $section);
            $subjectKey = strtolower(trim((string) $subject));
            if (! $allowedSubjects->contains(fn ($name) => strtolower(trim((string) $name)) === $subjectKey)) {
                $testsQuery->whereRaw('1 = 0');
            }
        } elseif ($isStaffTeacher && ! $subject) {
            $assignedSubjectNames = $staff->uploadableSubjectNamesForMarks($campus, $class, $section)
                ->map(fn ($s) => strtolower(trim((string) $s)))
                ->filter()
                ->values();

            if ($assignedSubjectNames->isNotEmpty()) {
                $testsQuery->where(function ($q) use ($assignedSubjectNames) {
                    foreach ($assignedSubjectNames as $subjectName) {
                        $q->orWhereRaw('LOWER(TRIM(subject)) = ?', [$subjectName]);
                    }
                });
            } else {
                $testsQuery->whereRaw('1 = 0');
            }
        }
        
        // Always filter by class
        $testsQuery->whereRaw('LOWER(TRIM(for_class)) = ?', [strtolower(trim($class))]);
        
        if ($campus) {
            $testsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
        }
        if ($section) {
            $sectionLower = strtolower(trim($section));
            $testsQuery->where(function ($q) use ($sectionLower) {
                $q->whereNull('section')
                    ->orWhereRaw('TRIM(section) = ?', [''])
                    ->orWhereRaw('LOWER(TRIM(section)) = ?', [$sectionLower]);
            });
        }
        if ($subject) {
            $subjectLower = strtolower(trim($subject));
            $testsQuery->where(function ($q) use ($subjectLower) {
                $q->whereNull('subject')
                    ->orWhereRaw('TRIM(subject) = ?', [''])
                    ->orWhereRaw('LOWER(TRIM(subject)) = ?', [$subjectLower]);
            });
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

        $staff = Auth::guard('staff')->user();
        if (Auth::guard('staff')->check() && $staff && method_exists($staff, 'canEditCombinedResultRemarks')) {
            if (!$staff->canEditCombinedResultRemarks($validated['campus'], $class, $section)) {
                return redirect()
                    ->route('test.teacher-remarks.combined', [
                        'filter_campus' => $validated['campus'],
                        'filter_session' => $validated['session'],
                        'filter_class_section' => $validated['class_section'],
                    ])
                    ->with('error', 'Only the class teacher can save combined result remarks.');
            }
        }

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

    private function staffCampusName(?Staff $staff): ?string
    {
        if (! $staff) {
            return null;
        }

        $campus = trim((string) ($staff->campus ?? ''));

        return $campus !== '' ? $campus : null;
    }

    private function campusesForStaffViewer(Staff $staff): Collection
    {
        $names = collect();

        if ($staffCampus = $this->staffCampusName($staff)) {
            $names->push($staffCampus);
        }

        $assignedCampusesQuery = Subject::query();
        $staff->scopeQueryToTeacherAssignments($assignedCampusesQuery);
        $names = $names->merge(
            $assignedCampusesQuery
                ->whereNotNull('campus')
                ->whereRaw("TRIM(campus) != ''")
                ->distinct()
                ->pluck('campus')
        );

        $unique = $names
            ->map(fn ($campus) => trim((string) $campus))
            ->filter()
            ->unique(fn ($campus) => strtolower($campus))
            ->values();

        return $unique->map(function (string $campus) {
            $record = Campus::query()
                ->whereRaw('LOWER(TRIM(campus_name)) = ?', [strtolower($campus)])
                ->first();

            return (object) ['campus_name' => $record?->campus_name ?? $campus];
        });
    }

    private function staffCanUseCampus(Staff $staff, ?string $campus): bool
    {
        if ($campus === null || trim($campus) === '') {
            return false;
        }

        $campusKey = strtolower(trim($campus));

        return $this->campusesForStaffViewer($staff)
            ->pluck('campus_name')
            ->map(fn ($name) => strtolower(trim((string) $name)))
            ->contains($campusKey);
    }

    private function staffAssignableClasses(Staff $staff, ?string $campus): Collection
    {
        if ($campus === null || trim($campus) === '') {
            return collect();
        }

        $classes = $staff->assignedSubjectClassNames($campus);
        if ($classes->isNotEmpty()) {
            return $classes;
        }

        $classes = $staff->assignedTeachingClassNames($campus);
        if ($classes->isNotEmpty()) {
            return $classes;
        }

        return $staff->assignedAttendanceClassNames($campus);
    }

    private function classesForCampusSelection(Staff $staff, string $campus): Collection
    {
        $classes = $this->staffAssignableClasses($staff, $campus);
        if ($classes->isNotEmpty()) {
            return $classes;
        }

        return ClassModel::whereNotNull('class_name')
            ->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))])
            ->distinct()
            ->pluck('class_name')
            ->sort()
            ->values();
    }

    private function sectionsForClassAtCampus(string $class, ?string $campus): Collection
    {
        $classKey = Staff::normalizeClassKey($class);

        $applyClassFilter = function ($query) use ($class, $classKey) {
            $query->where(function ($q) use ($class, $classKey) {
                $q->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))])
                    ->orWhereRaw('LOWER(TRIM(class)) = ?', [$classKey])
                    ->orWhereRaw("LOWER(TRIM(REPLACE(REPLACE(class, 'Class ', ''), 'class ', ''))) = ?", [$classKey]);
            });
        };

        $sectionsQuery = Section::query();
        $applyClassFilter($sectionsQuery);
        $sectionsQuery->whereNotNull('name');

        if ($campus !== null && trim($campus) !== '') {
            $campusKey = strtolower(trim($campus));
            $sectionsQuery->where(function ($q) use ($campusKey) {
                $q->whereRaw('LOWER(TRIM(COALESCE(campus, ""))) = ?', [$campusKey])
                    ->orWhereRaw('TRIM(COALESCE(campus, "")) = ?', ['']);
            });
        }

        $sections = $sectionsQuery
            ->distinct()
            ->pluck('name')
            ->map(fn ($section) => trim((string) $section))
            ->filter()
            ->unique()
            ->sort()
            ->values();

        if ($sections->isNotEmpty()) {
            return $sections;
        }

        $subjectsQuery = Subject::query();
        $applyClassFilter($subjectsQuery);
        $subjectsQuery->whereNotNull('section');

        if ($campus !== null && trim($campus) !== '') {
            $campusKey = strtolower(trim($campus));
            $subjectsQuery->where(function ($q) use ($campusKey) {
                $q->whereRaw('LOWER(TRIM(COALESCE(campus, ""))) = ?', [$campusKey])
                    ->orWhereRaw('TRIM(COALESCE(campus, "")) = ?', ['']);
            });
        }

        return $subjectsQuery
            ->distinct()
            ->pluck('section')
            ->map(fn ($section) => trim((string) $section))
            ->filter()
            ->unique()
            ->sort()
            ->values();
    }

    private function sectionsForClassSelection(Staff $staff, string $class, ?string $campus): Collection
    {
        $sections = $staff->assignedTeachingSectionsForClass($class, $campus);
        if ($sections->isNotEmpty()) {
            return $sections;
        }

        return $this->sectionsForClassAtCampus($class, $campus);
    }
}

