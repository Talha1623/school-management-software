<?php

namespace App\Http\Controllers;

use App\Models\Test;
use App\Models\Student;
use App\Models\ClassModel;
use App\Models\Section;
use App\Models\Subject;
use App\Models\Campus;
use App\Models\CombinedResultGrade;
use App\Models\ParticularTestGrade;
use App\Models\StudentMark;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class PositionHolderController extends Controller
{
    /**
     * Display the position holder for practical test page with filters.
     */
    public function practical(Request $request): View
    {
        // Get filter values
        $filterCampus = $request->get('filter_campus');
        $filterClass = $request->get('filter_class');
        $filterSection = $request->get('filter_section');
        $filterSubject = $request->get('filter_subject');
        $filterTest = $request->get('filter_test');

        // Get campuses for dropdown - First from Campus model, then fallback
        $campuses = Campus::orderBy('campus_name', 'asc')->get();
        if ($campuses->isEmpty()) {
            $campusesFromClasses = ClassModel::whereNotNull('campus')->distinct()->pluck('campus');
            $campusesFromSections = Section::whereNotNull('campus')->distinct()->pluck('campus');
            $campusesFromSubjects = Subject::whereNotNull('campus')->distinct()->pluck('campus');
            $allCampuses = $campusesFromClasses->merge($campusesFromSections)->merge($campusesFromSubjects)->unique()->sort()->values();
            $campuses = $allCampuses->map(function($campus) {
                return (object)['campus_name' => $campus];
            });
        }
        
        // Convert to simple array for dropdown
        $campusesList = $campuses->map(function($campus) {
            return is_object($campus) ? ($campus->campus_name ?? '') : $campus;
        })->filter()->unique()->sort()->values();

        // Get classes
        $classes = ClassModel::whereNotNull('class_name')->distinct()->pluck('class_name')->sort()->values();
        
        if ($classes->isEmpty()) {
            $classes = collect(['Nursery', 'KG', '1st', '2nd', '3rd', '4th', '5th', '6th', '7th', '8th', '9th', '10th', '11th', '12th']);
        }

        // Get sections - will be loaded dynamically based on class selection
        $sections = collect();
        if ($filterClass) {
            // Load sections for the selected class
            $sections = Section::whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($filterClass))])
                ->whereNotNull('name')
                ->distinct()
                ->pluck('name')
                ->sort()
                ->values();
                
            if ($sections->isEmpty()) {
                // Try from subjects table with case-insensitive matching
                $sections = Subject::whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($filterClass))])
                    ->whereNotNull('section')
                    ->distinct()
                    ->pluck('section')
                    ->sort()
                    ->values();
            }
        }

        // Get subjects - filter out subjects with deleted classes (only show active subjects)
        $subjectsQuery = Subject::query();
        
        // Get active (non-deleted) class names
        $existingClassNames = ClassModel::whereNotNull('class_name')->pluck('class_name')->map(function($name) {
            return strtolower(trim($name));
        })->toArray();
        
        // Filter out subjects with deleted classes
        if (!empty($existingClassNames)) {
            $subjectsQuery->where(function($q) use ($existingClassNames) {
                foreach ($existingClassNames as $className) {
                    $q->orWhereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($className))]);
                }
            });
        } else {
            // If no classes exist, show no subjects
            $subjectsQuery->whereRaw('1 = 0');
        }
        
        if ($filterCampus) {
            $subjectsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($filterCampus))]);
        }
        if ($filterClass) {
            $subjectsQuery->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($filterClass))]);
        }
        if ($filterSection) {
            $subjectsQuery->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($filterSection))]);
        }
        $subjects = $subjectsQuery->whereNotNull('subject_name')->distinct()->pluck('subject_name')->sort()->values();
        
        if ($subjects->isEmpty()) {
            $subjects = collect(['Mathematics', 'English', 'Science', 'Urdu', 'Islamiat', 'Social Studies']);
        }

        // Get tests (filtered by other criteria if provided)
        $testsQuery = Test::query();
        if ($filterCampus) {
            $testsQuery->where('campus', $filterCampus);
        }
        if ($filterClass) {
            $testsQuery->where('for_class', $filterClass);
        }
        if ($filterSection) {
            $testsQuery->where('section', $filterSection);
        }
        if ($filterSubject) {
            $testsQuery->where('subject', $filterSubject);
        }
        $tests = $testsQuery->whereNotNull('test_name')->distinct()->pluck('test_name')->sort()->values();
        
        if ($tests->isEmpty()) {
            $tests = collect(['Quiz 1', 'Mid Term', 'Final Term', 'Assignment 1']);
        }

        // Get grades from CombinedResultGrade model (dynamic grades)
        $grades = CombinedResultGrade::orderBy('from_percentage', 'desc')
            ->get()
            ->pluck('name')
            ->unique()
            ->sort()
            ->values();
        
        // If no grades found, use default grades
        if ($grades->isEmpty()) {
            $grades = collect(['A+', 'A', 'B+', 'B', 'C+', 'C', 'D', 'F']);
        }

        // Query students based on filters
        $students = collect();
        $studentMarks = collect();
        
        if ($filterCampus || $filterClass || $filterSection) {
            $studentsQuery = Student::query();
            
            if ($filterCampus) {
                $studentsQuery->where('campus', $filterCampus);
            }
            if ($filterClass) {
                $studentsQuery->where('class', $filterClass);
            }
            if ($filterSection) {
                $studentsQuery->where('section', $filterSection);
            }
            
            $students = $studentsQuery->orderBy('student_name')->get();
            
            // Load existing marks if test is selected
            if ($filterTest && $students->isNotEmpty()) {
                $studentIds = $students->pluck('id');
                
                $marksQuery = StudentMark::where('test_name', $filterTest)
                    ->whereIn('student_id', $studentIds);
                
                if ($filterCampus) {
                    $marksQuery->where('campus', $filterCampus);
                }
                if ($filterClass) {
                    $marksQuery->where('class', $filterClass);
                }
                if ($filterSection) {
                    $marksQuery->where('section', $filterSection);
                }
                if ($filterSubject) {
                    $marksQuery->where('subject', $filterSubject);
                }
                
                $studentMarks = $marksQuery->get()->keyBy('student_id');
                
                // Sort students by marks (descending) for position holders
                $students = $students->map(function($student) use ($studentMarks) {
                    $mark = $studentMarks->get($student->id);
                    $student->marks_obtained = $mark ? $mark->marks_obtained : 0;
                    $student->total_marks = $mark ? $mark->total_marks : 100;
                    return $student;
                })->sortByDesc('marks_obtained')->values();
            }
        }

        // Get grade definitions for calculating grades
        $gradeDefinitions = collect();
        if ($filterCampus && $filterTest) {
            // Try to get from ParticularTestGrade first (more specific)
            $particularGrades = ParticularTestGrade::where('campus', $filterCampus)
                ->where('for_test', $filterTest);
            
            if ($filterClass) {
                $particularGrades->where('class', $filterClass);
            }
            if ($filterSection) {
                $particularGrades->where('section', $filterSection);
            }
            if ($filterSubject) {
                $particularGrades->where('subject', $filterSubject);
            }
            
            $gradeDefinitions = $particularGrades->orderBy('from_percentage', 'desc')->get();
        }
        
        // If no particular test grades found, use CombinedResultGrade
        if ($gradeDefinitions->isEmpty() && $filterCampus) {
            $gradeDefinitions = CombinedResultGrade::where('campus', $filterCampus)
                ->orderBy('from_percentage', 'desc')
                ->get();
        }

        return view('test.position-holder.practical', compact(
            'campuses',
            'campusesList',
            'classes',
            'sections',
            'subjects',
            'tests',
            'grades',
            'students',
            'studentMarks',
            'gradeDefinitions',
            'filterCampus',
            'filterClass',
            'filterSection',
            'filterSubject',
            'filterTest'
        ));
    }

    /**
     * Get sections by class (AJAX)
     */
    public function getSectionsByClass(Request $request): JsonResponse
    {
        $class = $request->get('class');
        $campus = $request->get('campus');
        
        if (!$class) {
            return response()->json(['sections' => []]);
        }
        
        // Use case-insensitive matching for class
        $sectionsQuery = Section::whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))]);
        
        if ($campus) {
            $sectionsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
        }
        
        $sections = $sectionsQuery->whereNotNull('name')
            ->distinct()
            ->pluck('name')
            ->sort()
            ->values();
            
        if ($sections->isEmpty()) {
            // Try from subjects table with case-insensitive matching
            $subjectsQuery = Subject::whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))]);
            
            if ($campus) {
                $subjectsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
            }
            
            $sections = $subjectsQuery->whereNotNull('section')
                ->distinct()
                ->pluck('section')
                ->sort()
                ->values();
        }
        
        return response()->json(['sections' => $sections]);
    }

    /**
     * Get subjects by class and section (AJAX) - only active subjects (non-deleted classes).
     */
    public function getSubjectsByClass(Request $request): JsonResponse
    {
        $class = $request->get('class');
        $section = $request->get('section');
        $campus = $request->get('campus');
        
        if (!$class) {
            return response()->json(['subjects' => []]);
        }
        
        // Get active (non-deleted) class names
        $existingClassNames = ClassModel::whereNotNull('class_name')->pluck('class_name')->map(function($name) {
            return strtolower(trim($name));
        })->toArray();
        
        // Build query for subjects
        $subjectsQuery = Subject::whereNotNull('subject_name');
        
        // Filter by class (case-insensitive)
        $subjectsQuery->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))]);
        
        // Filter out subjects with deleted classes
        if (!empty($existingClassNames)) {
            $subjectsQuery->where(function($q) use ($existingClassNames) {
                foreach ($existingClassNames as $className) {
                    $q->orWhereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($className))]);
                }
            });
        } else {
            // If no classes exist, return empty
            return response()->json(['subjects' => []]);
        }
        
        // Filter by section if provided
        if ($section) {
            $subjectsQuery->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($section))]);
        }
        
        // Filter by campus if provided
        if ($campus) {
            $subjectsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
        }
        
        // Get unique subject names
        $subjects = $subjectsQuery->distinct()
            ->pluck('subject_name')
            ->sort()
            ->values();
        
        return response()->json(['subjects' => $subjects]);
    }

    /**
     * Get tests by filters (AJAX)
     */
    public function getTestsByFilters(Request $request): JsonResponse
    {
        $campus = $request->get('campus');
        $class = $request->get('class');
        $section = $request->get('section');
        $subject = $request->get('subject');
        
        $testsQuery = Test::query();
        
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
     * Get campuses (AJAX) - dynamically fetch from Campus model and other sources.
     */
    public function getCampuses(Request $request): JsonResponse
    {
        // Get campuses from Campus model first
        $campuses = Campus::orderBy('campus_name', 'asc')->get();
        
        if ($campuses->isEmpty()) {
            // Fallback to other sources
            $campusesFromClasses = ClassModel::whereNotNull('campus')->distinct()->pluck('campus');
            $campusesFromSections = Section::whereNotNull('campus')->distinct()->pluck('campus');
            $campusesFromSubjects = Subject::whereNotNull('campus')->distinct()->pluck('campus');
            $allCampuses = $campusesFromClasses->merge($campusesFromSections)->merge($campusesFromSubjects)->unique()->sort()->values();
            
            $campuses = $allCampuses->map(function($campus) {
                return (object)['campus_name' => $campus];
            });
        }
        
        // Convert to simple array
        $campusesList = $campuses->map(function($campus) {
            return is_object($campus) ? ($campus->campus_name ?? '') : $campus;
        })->filter()->unique()->sort()->values();
        
        return response()->json(['campuses' => $campusesList]);
    }

    /**
     * Display the position holder for combine test page with filters.
     */
    public function combine(Request $request): View
    {
        // Get filter values
        $filterCampus = $request->get('filter_campus');
        $filterClass = $request->get('filter_class');
        $filterSection = $request->get('filter_section');
        $filterTestType = $request->get('filter_test_type');
        $filterFromDate = $request->get('filter_from_date');
        $filterToDate = $request->get('filter_to_date');

        // Get campuses
        $campuses = Campus::orderBy('campus_name', 'asc')->get();
        if ($campuses->isEmpty()) {
            $campusesFromClasses = ClassModel::whereNotNull('campus')->distinct()->pluck('campus');
            $campusesFromSections = Section::whereNotNull('campus')->distinct()->pluck('campus');
            $allCampuses = $campusesFromClasses->merge($campusesFromSections)->unique()->sort();
            $campuses = $allCampuses->map(function($campus) {
                return (object)['campus_name' => $campus];
            });
        }

        // Get classes
        $classes = ClassModel::whereNotNull('class_name')->distinct()->pluck('class_name')->sort()->values();
        
        if ($classes->isEmpty()) {
            $classes = collect(['Nursery', 'KG', '1st', '2nd', '3rd', '4th', '5th', '6th', '7th', '8th', '9th', '10th', '11th', '12th']);
        }

        // Get sections - will be loaded dynamically based on class selection
        $sections = collect();
        if ($filterClass) {
            // Load sections for the selected class
            $sections = Section::whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($filterClass))])
                ->whereNotNull('name')
                ->distinct()
                ->pluck('name')
                ->sort()
                ->values();
                
            if ($sections->isEmpty()) {
                // Try from subjects table with case-insensitive matching
                $sections = Subject::whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($filterClass))])
                    ->whereNotNull('section')
                    ->distinct()
                    ->pluck('section')
                    ->sort()
                    ->values();
            }
        }

        // Get test types - include default types and merge with database types
        $testTypes = Test::whereNotNull('test_type')->distinct()->pluck('test_type')->sort()->values();
        
        // Merge with default test types to ensure daily, weekly, monthly are always available
        $defaultTypes = ['Daily Test', 'Weekly Test', 'Monthly Test'];
        $testTypes = $testTypes->merge($defaultTypes)->unique()->sort()->values();
        
        if ($testTypes->isEmpty()) {
            $testTypes = collect(['Quiz', 'Mid Term', 'Final Term', 'Assignment', 'Project', 'Oral Test']);
        }

        // Get grades from CombinedResultGrade model (dynamic grades)
        $grades = CombinedResultGrade::orderBy('from_percentage', 'desc')
            ->get()
            ->pluck('name')
            ->unique()
            ->sort()
            ->values();
        
        // If no grades found, use default grades
        if ($grades->isEmpty()) {
            $grades = collect(['A+', 'A', 'B+', 'B', 'C+', 'C', 'D', 'F']);
        }

        // Query students based on filters
        $students = collect();
        $studentMarks = collect();
        $gradeDefinitions = collect();
        
        if ($filterClass || $filterSection) {
            $studentsQuery = Student::query();
            
            if ($filterCampus) {
                $studentsQuery->where('campus', $filterCampus);
            }
            if ($filterClass) {
                $studentsQuery->where('class', $filterClass);
            }
            if ($filterSection) {
                $studentsQuery->where('section', $filterSection);
            }
            
            $students = $studentsQuery->orderBy('student_name')->get();
            
            if ($students->isNotEmpty()) {
                $studentIds = $students->pluck('id');
                
                // Get student marks for combine tests
                $marksQuery = StudentMark::whereIn('student_id', $studentIds);
                
                if ($filterCampus) {
                    $marksQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($filterCampus))]);
                }
                if ($filterClass) {
                    $marksQuery->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($filterClass))]);
                }
                if ($filterSection) {
                    $marksQuery->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($filterSection))]);
                }
                
                // Filter by test_type if provided - need to join with tests table
                if ($filterTestType) {
                    $testNames = Test::where('test_type', $filterTestType)
                        ->when($filterCampus, function($q) use ($filterCampus) {
                            return $q->where('campus', $filterCampus);
                        })
                        ->when($filterClass, function($q) use ($filterClass) {
                            return $q->whereRaw('LOWER(TRIM(for_class)) = ?', [strtolower(trim($filterClass))]);
                        })
                        ->when($filterSection, function($q) use ($filterSection) {
                            return $q->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($filterSection))]);
                        })
                        ->distinct()
                        ->pluck('test_name');
                    
                    if ($testNames->isNotEmpty()) {
                        $marksQuery->whereIn('test_name', $testNames);
                    } else {
                        $marksQuery->whereRaw('1 = 0');
                    }
                }
                
                if ($filterFromDate) {
                    $marksQuery->whereDate('created_at', '>=', $filterFromDate);
                }
                if ($filterToDate) {
                    $marksQuery->whereDate('created_at', '<=', $filterToDate);
                }
                
                $marks = $marksQuery->get();
                $studentMarks = $marks->groupBy('student_id');
                
                // Get grade definitions for combined results
                if ($filterCampus) {
                    $gradeDefinitions = CombinedResultGrade::where('campus', $filterCampus)
                        ->orderBy('from_percentage', 'desc')
                        ->get();
                } else {
                    $gradeDefinitions = CombinedResultGrade::orderBy('from_percentage', 'desc')->get();
                }
                
                // Calculate totals, percentage, and grade for each student
                $studentSummaries = collect();
                foreach ($students as $student) {
                    $studentMarkList = $studentMarks->get($student->id, collect());
                    $totalMarks = $studentMarkList->sum(function($m) { return (float)($m->total_marks ?? 0); });
                    $totalObtained = $studentMarkList->sum(function($m) { return (float)($m->marks_obtained ?? 0); });
                    $percentage = $totalMarks > 0 ? round(($totalObtained / $totalMarks) * 100, 2) : 0;
                    
                    // Calculate grade
                    $calculatedGrade = null;
                    if ($percentage > 0 && $gradeDefinitions->isNotEmpty()) {
                        foreach ($gradeDefinitions as $gradeDef) {
                            if ($percentage >= $gradeDef->from_percentage && $percentage <= $gradeDef->to_percentage) {
                                $calculatedGrade = $gradeDef->name;
                                break;
                            }
                        }
                    }
                    
                    $studentSummaries->put($student->id, [
                        'total_marks' => $totalMarks,
                        'total_obtained' => $totalObtained,
                        'percentage' => $percentage,
                        'grade' => $calculatedGrade,
                    ]);
                }
                
                // Sort students by total obtained marks (descending) for position
                $students = $students->map(function($student) use ($studentSummaries) {
                    $summary = $studentSummaries->get($student->id, []);
                    $student->totalMarks = $summary['total_marks'] ?? 0;
                    $student->totalObtained = $summary['total_obtained'] ?? 0;
                    $student->percentage = $summary['percentage'] ?? 0;
                    $student->grade = $summary['grade'] ?? null;
                    return $student;
                })->sortByDesc('totalObtained')->values();
            }
        }

        return view('test.position-holder.combine', compact(
            'campuses',
            'classes',
            'sections',
            'testTypes',
            'grades',
            'students',
            'studentMarks',
            'gradeDefinitions',
            'filterCampus',
            'filterClass',
            'filterSection',
            'filterTestType',
            'filterFromDate',
            'filterToDate'
        ));
    }

    /**
     * Get grades (AJAX) - dynamically fetch from CombinedResultGrade model.
     */
    public function getGrades(Request $request): JsonResponse
    {
        // Get grades from CombinedResultGrade model
        $grades = CombinedResultGrade::orderBy('from_percentage', 'desc')
            ->get()
            ->pluck('name')
            ->unique()
            ->sort()
            ->values();
        
        // If no grades found, use default grades
        if ($grades->isEmpty()) {
            $grades = collect(['A+', 'A', 'B+', 'B', 'C+', 'C', 'D', 'F']);
        }
        
        return response()->json(['grades' => $grades]);
    }
}

