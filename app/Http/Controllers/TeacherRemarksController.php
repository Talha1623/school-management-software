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

        // Get campuses for dropdown
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

        // Get classes
        $classes = ClassModel::whereNotNull('class_name')->distinct()->pluck('class_name')->sort()->values();
        
        if ($classes->isEmpty()) {
            $classes = collect(['Nursery', 'KG', '1st', '2nd', '3rd', '4th', '5th', '6th', '7th', '8th', '9th', '10th', '11th', '12th']);
        }

        // Get sections (will be filtered dynamically based on class selection)
        $sections = collect(); // Initialized as empty, will be filled via AJAX
        if ($filterClass) {
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

        // Get subjects (filtered by other criteria if provided, otherwise show all)
        $subjectsQuery = Subject::query();
        $campusName = is_object($filterCampus) ? ($filterCampus->campus_name ?? '') : $filterCampus;
        if ($campusName) {
            $subjectsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campusName))]);
        }
        if ($filterClass) {
            $subjectsQuery->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($filterClass))]);
        }
        if ($filterSection) {
            $subjectsQuery->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($filterSection))]);
        }
        $subjects = $subjectsQuery->whereNotNull('subject_name')->distinct()->pluck('subject_name')->sort()->values();
        
        // If no subjects found and no filters applied, show all subjects
        if ($subjects->isEmpty() && !$campusName && !$filterClass && !$filterSection) {
            $subjects = Subject::whereNotNull('subject_name')->distinct()->pluck('subject_name')->sort()->values();
        }

        // Get tests (filtered by other criteria if provided) - Only show tests where result_status = 1 (declared)
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

        // Get campuses for dropdown
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

        // Get sessions
        $sessions = Test::whereNotNull('session')->distinct()->pluck('session')->sort()->values();
        
        if ($sessions->isEmpty()) {
            $sessions = collect(['2024-2025', '2025-2026', '2026-2027']);
        }

        // Get Class/Section combinations
        $classSectionOptions = collect();
        $classes = ClassModel::whereNotNull('class_name')->distinct()->pluck('class_name')->sort()->values();
        
        foreach ($classes as $className) {
            $sections = Section::whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($className))])
                ->whereNotNull('name')
                ->distinct()
                ->pluck('name')
                ->sort()
                ->values();
            
            if ($sections->isEmpty()) {
                // Try from Subject table
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
        
        $subjectsQuery = Subject::query();
        
        if ($campus) {
            $subjectsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
        }
        if ($class) {
            $subjectsQuery->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))]);
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
     * Get tests for teacher remarks (AJAX) - Only declared results.
     */
    public function getTests(Request $request): JsonResponse
    {
        $campus = $request->get('campus');
        $class = $request->get('class');
        $section = $request->get('section');
        $subject = $request->get('subject');
        
        $testsQuery = Test::where('result_status', 1); // Only declared results
        
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
        
        $classes = ClassModel::whereNotNull('class_name')->distinct()->pluck('class_name')->sort()->values();
        
        $classSectionOptions = collect();
        
        foreach ($classes as $className) {
            $sections = Section::whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($className))])
                ->whereNotNull('name')
                ->distinct()
                ->pluck('name')
                ->sort()
                ->values();
            
            if ($sections->isEmpty()) {
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

