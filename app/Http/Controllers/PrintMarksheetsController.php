<?php

namespace App\Http\Controllers;

use App\Models\Test;
use App\Models\Student;
use App\Models\ClassModel;
use App\Models\Section;
use App\Models\Subject;
use App\Models\StudentMark;
use App\Models\CombinedResultGrade;
use App\Models\ParticularTestGrade;
use App\Models\Campus;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;

class PrintMarksheetsController extends Controller
{
    /**
     * Display the print marksheets for practical test page.
     */
    public function practical(Request $request): View
    {
        $filterCampus = $request->get('campus');
        $filterClass = $request->get('class');
        $filterSection = $request->get('section');
        $filterSubject = $request->get('subject');
        $filterTest = $request->get('test');
        $isPrint = $request->get('print', false);

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
        
        // Convert to simple array
        $campusesList = $campuses->map(function($campus) {
            return is_object($campus) ? ($campus->campus_name ?? '') : $campus;
        })->filter()->unique()->sort()->values();

        $classes = $this->getClasses();
        $sections = $this->getSectionsData();
        $subjects = $this->getSubjectsData();
        $tests = $this->getTestsData();

        // Load students and marks if filters are applied
        $students = collect();
        $marksByStudent = collect();
        $studentSummaries = collect();
        $highestBySubject = collect();
        $gradeDefinitions = collect();
        $testSession = null;
        $classTeacherRemarks = collect();

        if ($isPrint && $filterCampus && $filterClass && $filterTest) {
            // Get students
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

                // Get marks
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

                $marks = $marksQuery->get();
                $marksByStudent = $marks->groupBy('student_id');

                // Get test session
                $testRecord = Test::where('test_name', $filterTest)
                    ->where('campus', $filterCampus)
                    ->where('for_class', $filterClass)
                    ->first();
                $testSession = $testRecord ? $testRecord->session : null;

                // Calculate highest marks per subject
                $highestBySubject = $marks->groupBy('subject')->map(function($subjectMarks) {
                    return $subjectMarks->max(function($mark) {
                        return (float)($mark->marks_obtained ?? 0);
                    });
                });

                // Calculate summaries for each student
                $studentSummaries = $marksByStudent->mapWithKeys(function($items, $studentId) {
                    $totalMarks = $items->sum(function($m) { return (float)($m->total_marks ?? 0); });
                    $totalPassing = $items->sum(function($m) { return (float)($m->passing_marks ?? 0); });
                    $totalObtained = $items->sum(function($m) { return (float)($m->marks_obtained ?? 0); });
                    $percentage = $totalMarks > 0 ? round(($totalObtained / $totalMarks) * 100, 2) : 0;
                    $status = $totalObtained >= $totalPassing ? 'PASS' : 'FAIL';

                    return [$studentId => [
                        'total_marks' => $totalMarks,
                        'total_passing' => $totalPassing,
                        'total_obtained' => $totalObtained,
                        'percentage' => $percentage,
                        'status' => $status,
                    ]];
                });

                // Calculate ranks
                $ranked = $studentSummaries->sortByDesc('total_obtained')->keys()->values();
                $rankMap = collect();
                $ranked->each(function($studentId, $index) use ($rankMap) {
                    $rankMap->put($studentId, $index + 1);
                });
                $studentSummaries = $studentSummaries->map(function($summary, $studentId) use ($rankMap) {
                    $summary['rank'] = $rankMap->get($studentId);
                    return $summary;
                });

                // Get grade definitions
                try {
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
                } catch (\Illuminate\Database\QueryException $e) {
                    // Table doesn't exist - return empty collection
                    if (str_contains($e->getMessage(), "doesn't exist")) {
                        $gradeDefinitions = collect();
                    } else {
                        throw $e;
                    }
                }
                
                if ($gradeDefinitions->isEmpty()) {
                    $gradeDefinitions = CombinedResultGrade::where('campus', $filterCampus)
                        ->orderBy('from_percentage', 'desc')
                        ->get();
                }

                // Get class teacher remarks (from StudentMark with null subject or from student reference_remarks)
                foreach ($students as $student) {
                    $studentMarkRemarks = StudentMark::where('student_id', $student->id)
                        ->where('test_name', $filterTest)
                        ->whereNull('subject')
                        ->where('campus', $filterCampus)
                        ->where('class', $filterClass)
                        ->value('teacher_remarks');
                    
                    $classTeacherRemarks->put($student->id, $studentMarkRemarks ?? $student->reference_remarks ?? '-');
                }
            }
        }

        return view('test.print-marksheets.practical', compact(
            'campuses',
            'campusesList',
            'classes',
            'sections',
            'subjects',
            'tests',
            'students',
            'marksByStudent',
            'studentSummaries',
            'highestBySubject',
            'gradeDefinitions',
            'testSession',
            'classTeacherRemarks',
            'filterCampus',
            'filterClass',
            'filterSection',
            'filterSubject',
            'filterTest',
            'isPrint'
        ));
    }

    /**
     * Get sections based on class (AJAX).
     */
    public function getSections(Request $request): JsonResponse
    {
        $class = $request->get('class');
        $campus = $request->get('campus');
        
        $sections = Section::when($campus, fn($q) => $q->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]))
            ->when($class, fn($q) => $q->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))]))
            ->whereNotNull('name')
            ->distinct()
            ->pluck('name')
            ->sort()
            ->values();
        
        if ($sections->isEmpty()) {
            // Try from subjects table
            $sections = Subject::when($campus, fn($q) => $q->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]))
                ->when($class, fn($q) => $q->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))]))
                ->whereNotNull('section')
                ->distinct()
                ->pluck('section')
                ->sort()
                ->values();
        }
        
        return response()->json($sections->isEmpty() ? ['A', 'B', 'C', 'D'] : $sections);
    }

    /**
     * Get subjects based on section (AJAX).
     */
    public function getSubjects(Request $request): JsonResponse
    {
        $section = $request->get('section');
        $class = $request->get('class');
        $campus = $request->get('campus');
        
        $subjects = Subject::when($campus, fn($q) => $q->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]))
            ->when($class, fn($q) => $q->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))]))
            ->when($section, fn($q) => $q->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($section))]))
            ->whereNotNull('subject_name')
            ->distinct()
            ->pluck('subject_name')
            ->sort()
            ->values();
        
        return response()->json($subjects->isEmpty() ? ['Mathematics', 'English', 'Science', 'Urdu', 'Islamiat', 'Social Studies'] : $subjects);
    }

    /**
     * Get tests based on subject (AJAX).
     */
    public function getTests(Request $request): JsonResponse
    {
        $subject = $request->get('subject');
        $section = $request->get('section');
        $class = $request->get('class');
        $campus = $request->get('campus');
        
        $tests = Test::when($campus, fn($q) => $q->where('campus', $campus))
            ->when($class, fn($q) => $q->where('for_class', $class))
            ->when($section, fn($q) => $q->where('section', $section))
            ->when($subject, fn($q) => $q->where('subject', $subject))
            ->whereNotNull('test_name')
            ->distinct()
            ->pluck('test_name')
            ->sort()
            ->values();
        
        return response()->json($tests->isEmpty() ? ['Quiz 1', 'Mid Term', 'Final Term', 'Assignment 1'] : $tests);
    }

    private function getCampuses()
    {
        $campuses = ClassModel::whereNotNull('campus')->distinct()->pluck('campus')
            ->merge(Section::whereNotNull('campus')->distinct()->pluck('campus'))
            ->unique()
            ->sort()
            ->values();
        
        return $campuses->isEmpty() ? collect(['Main Campus', 'Branch Campus 1', 'Branch Campus 2']) : $campuses;
    }

    private function getClasses()
    {
        $classes = ClassModel::whereNotNull('class_name')->distinct()->pluck('class_name')->sort()->values();
        return $classes->isEmpty() ? collect(['Nursery', 'KG', '1st', '2nd', '3rd', '4th', '5th', '6th', '7th', '8th', '9th', '10th', '11th', '12th']) : $classes;
    }

    private function getSectionsData()
    {
        $sections = Section::whereNotNull('name')->distinct()->pluck('name')->sort()->values();
        return $sections->isEmpty() ? collect(['A', 'B', 'C', 'D']) : $sections;
    }

    private function getSubjectsData()
    {
        $subjects = Subject::whereNotNull('subject_name')->distinct()->pluck('subject_name')->sort()->values();
        return $subjects->isEmpty() ? collect(['Mathematics', 'English', 'Science', 'Urdu', 'Islamiat', 'Social Studies']) : $subjects;
    }

    private function getTestsData()
    {
        $tests = Test::whereNotNull('test_name')->distinct()->pluck('test_name')->sort()->values();
        return $tests->isEmpty() ? collect(['Quiz 1', 'Mid Term', 'Final Term', 'Assignment 1']) : $tests;
    }

    /**
     * Display the print marksheets for combine test page.
     */
    public function combine(Request $request): View
    {
        $filterCampus = $request->get('campus');
        $filterClass = $request->get('class');
        $filterSection = $request->get('section');
        $filterTestType = $request->get('test_type');
        $filterFromDate = $request->get('from_date');
        $filterToDate = $request->get('to_date');
        $isPrint = $request->get('print', false);

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
        
        // Convert to simple array
        $campusesList = $campuses->map(function($campus) {
            return is_object($campus) ? ($campus->campus_name ?? '') : $campus;
        })->filter()->unique()->sort()->values();

        $classes = $this->getClasses();
        $sections = $this->getSectionsData();
        
        // Get test types
        $testTypes = Test::whereNotNull('test_type')->distinct()->pluck('test_type')->sort()->values();
        $defaultTypes = ['Daily Test', 'Weekly Test', 'Monthly Test'];
        $testTypes = $testTypes->merge($defaultTypes)->unique()->sort()->values();
        if ($testTypes->isEmpty()) {
            $testTypes = collect(['Quiz', 'Mid Term', 'Final Term', 'Assignment', 'Project', 'Oral Test']);
        }

        // Load students and marks if filters are applied
        $students = collect();
        $marksByStudent = collect();
        $studentSummaries = collect();
        $highestBySubject = collect();
        $gradeDefinitions = collect();
        $classTeacherRemarks = collect();
        $combinedTestName = null;

        if ($isPrint && $filterCampus && $filterClass) {
            // Get students
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

                // Get marks for combine tests
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
                
                // Filter by test_type if provided
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
                        $combinedTestName = $filterTestType;
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
                $marksByStudent = $marks->groupBy('student_id');

                // Calculate highest marks per subject
                $highestBySubject = $marks->groupBy('subject')->map(function($subjectMarks) {
                    return $subjectMarks->max(function($mark) {
                        return (float)($mark->marks_obtained ?? 0);
                    });
                });

                // Calculate summaries for each student
                $studentSummaries = $marksByStudent->mapWithKeys(function($items, $studentId) {
                    $totalMarks = $items->sum(function($m) { return (float)($m->total_marks ?? 0); });
                    $totalPassing = $items->sum(function($m) { return (float)($m->passing_marks ?? 0); });
                    $totalObtained = $items->sum(function($m) { return (float)($m->marks_obtained ?? 0); });
                    $percentage = $totalMarks > 0 ? round(($totalObtained / $totalMarks) * 100, 2) : 0;
                    $status = $totalObtained >= $totalPassing ? 'PASS' : 'FAIL';

                    return [$studentId => [
                        'total_marks' => $totalMarks,
                        'total_passing' => $totalPassing,
                        'total_obtained' => $totalObtained,
                        'percentage' => $percentage,
                        'status' => $status,
                    ]];
                });

                // Calculate ranks
                $ranked = $studentSummaries->sortByDesc('total_obtained')->keys()->values();
                $rankMap = collect();
                $ranked->each(function($studentId, $index) use ($rankMap) {
                    $rankMap->put($studentId, $index + 1);
                });
                $studentSummaries = $studentSummaries->map(function($summary, $studentId) use ($rankMap) {
                    $summary['rank'] = $rankMap->get($studentId);
                    return $summary;
                });

                // Get grade definitions for combined results
                if ($filterCampus) {
                    $gradeDefinitions = CombinedResultGrade::where('campus', $filterCampus)
                        ->orderBy('from_percentage', 'desc')
                        ->get();
                } else {
                    $gradeDefinitions = CombinedResultGrade::orderBy('from_percentage', 'desc')->get();
                }

                // Get class teacher remarks
                foreach ($students as $student) {
                    $studentMarkRemarks = StudentMark::where('student_id', $student->id)
                        ->where('test_name', 'COMBINED_RESULT')
                        ->when($filterCampus, function($q) use ($filterCampus) {
                            return $q->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($filterCampus))]);
                        })
                        ->when($filterClass, function($q) use ($filterClass) {
                            return $q->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($filterClass))]);
                        })
                        ->when($filterSection, function($q) use ($filterSection) {
                            return $q->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($filterSection))]);
                        })
                        ->whereNull('subject')
                        ->value('teacher_remarks');
                    
                    $classTeacherRemarks->put($student->id, $studentMarkRemarks ?? $student->reference_remarks ?? '-');
                }
            }
        }

        return view('test.print-marksheets.combine', compact(
            'campuses',
            'campusesList',
            'classes',
            'sections',
            'testTypes',
            'students',
            'marksByStudent',
            'studentSummaries',
            'highestBySubject',
            'gradeDefinitions',
            'combinedTestName',
            'classTeacherRemarks',
            'filterCampus',
            'filterClass',
            'filterSection',
            'filterTestType',
            'filterFromDate',
            'filterToDate',
            'isPrint'
        ));
    }
}

