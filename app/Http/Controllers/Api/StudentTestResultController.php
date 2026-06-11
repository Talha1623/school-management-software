<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StudentMark;
use App\Models\Test;
use App\Models\Exam;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class StudentTestResultController extends Controller
{
    /**
     * Only marks linked to an existing announced test/exam — deleted announcements (and unpublished results when result_status=false) disappear from the API.
     * Keeps remarks-only synthetic rows used by the web (`COMBINED_RESULT`).
     */
    private function visibleStudentMarksQuery(int $studentId): EloquentBuilder
    {
        $markModel = new StudentMark;

        return StudentMark::query()
            ->where($markModel->qualifyColumn('student_id'), $studentId)
            ->where(function (EloquentBuilder $outer) use ($markModel) {
                $outer->where($markModel->qualifyColumn('test_name'), 'COMBINED_RESULT')
                    ->orWhereExists(function (QueryBuilder $sub) use ($markModel) {
                        $tests = new Test;
                        $testsTable = $tests->getTable();
                        $sub->selectRaw('1')
                            ->from($testsTable)
                            ->whereRaw(
                                'LOWER(TRIM(' . $tests->qualifyColumn('test_name') . ')) = LOWER(TRIM(' . $markModel->qualifyColumn('test_name') . '))'
                            );

                    if (Schema::hasColumn($testsTable, 'deleted_at')) {
                        $sub->whereNull($tests->qualifyColumn('deleted_at'));
                    }

                        if (Schema::hasColumn($testsTable, 'result_status')) {
                            $sub->where(function (QueryBuilder $w) use ($tests) {
                                $w->whereNull($tests->qualifyColumn('result_status'))
                                    ->orWhere($tests->qualifyColumn('result_status'), true);
                            });
                        }
                    })
                    ->orWhereExists(function (QueryBuilder $sub) use ($markModel) {
                        $exams = new Exam;
                        $examsTable = $exams->getTable();
                        $sub->selectRaw('1')
                            ->from($examsTable)
                            ->whereRaw(
                                'LOWER(TRIM(' . $exams->qualifyColumn('exam_name') . ')) = LOWER(TRIM(' . $markModel->qualifyColumn('test_name') . '))'
                            );

                    if (Schema::hasColumn($examsTable, 'deleted_at')) {
                        $sub->whereNull($exams->qualifyColumn('deleted_at'));
                    }

                        if (Schema::hasColumn($examsTable, 'result_status')) {
                            $sub->where(function (QueryBuilder $w) use ($exams) {
                                $w->whereNull($exams->qualifyColumn('result_status'))
                                    ->orWhere($exams->qualifyColumn('result_status'), true);
                            });
                        }
                    });
            });
    }

    /** Teacher remarks sheet often saves one row per test with subject=null; subject marks rows may have remarks=null. */
    private function markSubjectBlank(mixed $subject): bool
    {
        return $subject === null || trim((string) $subject) === '';
    }

    private function normalizedRemark(?string $value): ?string
    {
        $s = trim((string) ($value ?? ''));

        return $s !== '' ? $s : null;
    }

    /**
     * @return array<string, string|null> lowercase trimmed test_name => remark (nullable)
     */
    private function aggregatedTeacherRemarksByTestName(Collection $marks): array
    {
        $out = [];

        foreach ($marks->groupBy(fn ($m) => strtolower(trim((string) ($m->test_name ?? '')))) as $key => $group) {
            if ($key === '') {
                continue;
            }

            $particular = $group->first(fn ($mark) => $this->markSubjectBlank($mark->subject ?? null));
            $remark = $particular
                ? $this->normalizedRemark($particular->teacher_remarks ?? null)
                : null;

            if ($remark === null) {
                foreach ($group as $mark) {
                    $candidate = $this->normalizedRemark($mark->teacher_remarks ?? null);
                    if ($candidate !== null) {
                        $remark = $candidate;
                        break;
                    }
                }
            }

            $out[$key] = $remark;
        }

        return $out;
    }
    /**
     * Get Test Results for Logged-in Student
     * Returns: subject_name (test_name), session, subject, total, pass, obtained, teacher_remarks, remarks (same text)
     * Remarks: prefers row-level teacher_remarks; else same test_name row with blank subject (web teacher remarks)
     *
     * GET /api/student/test-results
     * Optional: test_name, session
     * Note: subject parameter is no longer accepted
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getTestResults(Request $request): JsonResponse
    {
        try {
            $student = $request->user();

            if (!$student) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found',
                    'token' => null,
                ], 404);
            }

            $query = $this->visibleStudentMarksQuery((int) $student->id);

            // Filter by test_name (optional)
            if ($request->filled('test_name')) {
                $query->where((new StudentMark)->qualifyColumn('test_name'), $request->test_name);
            }

            // Subject filter removed - no longer accepting subject parameter

            // Get marks
            $marks = $query->orderBy('test_name', 'asc')
                ->orderBy('subject', 'asc')
                ->get();

            // Get unique test names to fetch sessions from Test model
            $testNames = $marks->pluck('test_name')->unique()->filter()->values();

            // Fetch sessions from Test model
            $testSessions = [];
            $testTypeByName = [];
            if ($testNames->isNotEmpty()) {
                $tests = Test::whereIn('test_name', $testNames->toArray())
                    ->select('test_name', 'session', 'test_type')
                    ->get()
                    ->keyBy('test_name');
                
                foreach ($tests as $test) {
                    $testSessions[$test->test_name] = $test->session;
                    $testTypeByName[strtolower(trim((string) $test->test_name))] = strtolower(trim((string) ($test->test_type ?? '')));
                }
            }

            // If a name exists in Exam table, treat it as exam and exclude from test-results endpoint.
            $examNameSet = [];
            if ($testNames->isNotEmpty()) {
                $examRows = Exam::query()
                    ->where(function ($q) use ($testNames) {
                        foreach ($testNames as $testName) {
                            $q->orWhereRaw('LOWER(TRIM(exam_name)) = ?', [strtolower(trim((string) $testName))]);
                        }
                    })
                    ->select('exam_name')
                    ->get();

                foreach ($examRows as $exam) {
                    $examNameSet[strtolower(trim((string) ($exam->exam_name ?? '')))] = true;
                }
            }

            // test-results should return only non-exam entries (web behavior).
            $marks = $marks->filter(function ($mark) use ($testTypeByName, $examNameSet) {
                $name = strtolower(trim((string) ($mark->test_name ?? '')));
                if ($name === '') {
                    return false;
                }

                if (($examNameSet[$name] ?? false) === true) {
                    return false;
                }

                $testType = $testTypeByName[$name] ?? '';
                return $testType !== 'exam';
            })->values();

            // Filter by session if provided
            if ($request->filled('session')) {
                $sessionFilter = $request->session;
                $marks = $marks->filter(function ($mark) use ($testSessions, $sessionFilter) {
                    $session = $testSessions[$mark->test_name] ?? null;
                    return $session === $sessionFilter;
                })->values();
            }

            // Subject rows often have null remarks while web saves remarks on subject=null rows for same test_name
            $remarkFallbackByTest = $this->aggregatedTeacherRemarksByTestName($marks);

            // Format response with all required fields
            $results = $marks->map(function ($mark) use ($testSessions, $remarkFallbackByTest) {
                $tkey = strtolower(trim((string) ($mark->test_name ?? '')));
                $remark = $this->normalizedRemark($mark->teacher_remarks ?? null)
                    ?? ($remarkFallbackByTest[$tkey] ?? null);

                return [
                    'subject_name' => $mark->test_name ?? null, // Test name (subject_name)
                    'session' => $testSessions[$mark->test_name] ?? null, // Session from Test model
                    'subject' => $mark->subject ?? null, // Subject name
                    'total' => $mark->total_marks ? (float) $mark->total_marks : null,
                    'pass' => $mark->passing_marks ? (float) $mark->passing_marks : null,
                    'obtained' => $mark->marks_obtained ? (float) $mark->marks_obtained : null,
                    'teacher_remarks' => $remark,
                    'remarks' => $remark,
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Test results retrieved successfully',
                'data' => [
                    'student' => [
                        'id' => $student->id,
                        'student_name' => $student->student_name,
                        'student_code' => $student->student_code,
                        'class' => $student->class,
                        'section' => $student->section,
                    ],
                    'results' => $results,
                    'total_records' => $results->count(),
                ],
                'token' => $request->user()->currentAccessToken()->token ?? null,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving test results: ' . $e->getMessage(),
                'token' => null,
            ], 500);
        }
    }

    /**
     * Get All Tests List for Logged-in Student
     * Returns all unique tests for the student (based on marks entry)
     * 
     * GET /api/student/test-list
     * Optional: session
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getTestList(Request $request): JsonResponse
    {
        try {
            $student = $request->user();

            if (!$student) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found',
                    'token' => null,
                ], 404);
            }

            // Get unique test names from marks tied to announcements that still exist
            $markModel = new StudentMark;
            $marks = $this->visibleStudentMarksQuery((int) $student->id)
                ->select($markModel->qualifyColumn('test_name'))
                ->distinct()
                ->whereNotNull($markModel->qualifyColumn('test_name'))
                ->get();

            // Get unique test names
            $testNames = $marks->pluck('test_name')->unique()->filter()->values();

            if ($testNames->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'message' => 'No tests found for this student',
                    'data' => [
                        'student' => [
                            'id' => $student->id,
                            'student_name' => $student->student_name,
                            'student_code' => $student->student_code,
                            'class' => $student->class,
                            'section' => $student->section,
                        ],
                        'tests' => [],
                        'total_tests' => 0,
                    ],
                    'token' => $request->user()->currentAccessToken()->token ?? null,
                ], 200);
            }

            // Fetch test details from Test model
            $tests = Test::whereIn('test_name', $testNames->toArray())
                ->select('test_name', 'session', 'date', 'campus', 'for_class', 'section')
                ->get()
                ->keyBy('test_name');

            // Filter by session if provided
            if ($request->filled('session')) {
                $sessionFilter = $request->session;
                $tests = $tests->filter(function ($test) use ($sessionFilter) {
                    return $test->session === $sessionFilter;
                });
            }

            // Format test list
            $testList = $tests->map(function ($test) {
                return [
                    'test_name' => $test->test_name ?? null,
                    'session' => $test->session ?? null,
                    'date' => $test->date ? $test->date->format('Y-m-d') : null,
                    'campus' => $test->campus ?? null,
                    'class' => $test->for_class ?? null,
                    'section' => $test->section ?? null,
                ];
            })->values();

            // Sort by date (newest first)
            $testList = $testList->sortByDesc('date')->values();

            return response()->json([
                'success' => true,
                'message' => 'Test list retrieved successfully',
                'data' => [
                    'student' => [
                        'id' => $student->id,
                        'student_name' => $student->student_name,
                        'student_code' => $student->student_code,
                        'class' => $student->class,
                        'section' => $student->section,
                    ],
                    'tests' => $testList,
                    'total_tests' => $testList->count(),
                ],
                'token' => $request->user()->currentAccessToken()->token ?? null,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving test list: ' . $e->getMessage(),
                'token' => null,
            ], 500);
        }
    }

    /**
     * Get Exam Results for Logged-in Student
     * Returns: subject_name (test_name), session, subject, total, pass, obtained
     * Only returns results where test_type IS "Exam"
     * Returns all subjects for the student's exams
     * 
     * GET /api/student/exam-results?student_id=9
     * Optional: test_name, session
     * NOTE: student_id is ignored; students can only access their own results (based on token)
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getExamResults(Request $request): JsonResponse
    {
        try {
            $authenticatedStudent = $request->user();

            if (!$authenticatedStudent) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found',
                    'token' => null,
                ], 404);
            }

            // Students can only access their own results - ignore any provided student_id
            $studentId = (int) $authenticatedStudent->id;

            // Get student record
            $student = \App\Models\Student::find($studentId);
            
            if (!$student) {
                return response()->json([
                    'success' => false,
                    'message' => 'Student not found',
                    'token' => null,
                ], 404);
            }

            $query = $this->visibleStudentMarksQuery($studentId);

            // Filter by test_name (optional) - case-insensitive and trimmed
            if ($request->filled('test_name')) {
                $tnCol = (new StudentMark)->qualifyColumn('test_name');
                $query->whereRaw('LOWER(TRIM(' . $tnCol . ')) = ?', [strtolower(trim((string) $request->test_name))]);
            }

            // Get marks
            $marks = $query->orderBy('test_name', 'asc')
                ->orderBy('subject', 'asc')
                ->get();

            // Get unique test names to fetch sessions and test_type from Test and Exam models
            $testNames = $marks->pluck('test_name')->unique()->filter()->values();

            // Fetch sessions and test_type from Test model (case-insensitive matching)
            $testSessions = [];
            $testTypes = [];
            if ($testNames->isNotEmpty()) {
                // Check Test table
                $testsQuery = Test::query();
                $testsQuery->where(function($q) use ($testNames) {
                    foreach ($testNames as $testName) {
                        $q->orWhereRaw('LOWER(TRIM(test_name)) = ?', [strtolower(trim($testName))]);
                    }
                });
                
                $tests = $testsQuery->select('test_name', 'session', 'test_type')->get();
                
                // Create maps with case-insensitive keys from Test table
                foreach ($tests as $test) {
                    $key = strtolower(trim($test->test_name));
                    $testSessions[$key] = $test->session;
                    $testTypes[$key] = $test->test_type;
                }
                
                // Check Exam table - if test_name exists in exams table, it's an exam
                $examsQuery = Exam::query();
                $examsQuery->where(function($q) use ($testNames) {
                    foreach ($testNames as $testName) {
                        $q->orWhereRaw('LOWER(TRIM(exam_name)) = ?', [strtolower(trim($testName))]);
                    }
                });
                
                $exams = $examsQuery->select('exam_name', 'session')->get();
                
                // Mark exams as "Exam" type and get their sessions
                foreach ($exams as $exam) {
                    $key = strtolower(trim($exam->exam_name));
                    // If exam exists in Exam table, it's definitely an exam
                    $testTypes[$key] = 'Exam';
                    // Set session from Exam table if not already set from Test table
                    if (!isset($testSessions[$key])) {
                        $testSessions[$key] = $exam->session;
                    }
                }
                
                // Also map original test names to sessions and types
                foreach ($testNames as $testName) {
                    $key = strtolower(trim($testName));
                    if (!isset($testSessions[$testName]) && isset($testSessions[$key])) {
                        $testSessions[$testName] = $testSessions[$key];
                    }
                    if (!isset($testTypes[$testName]) && isset($testTypes[$key])) {
                        $testTypes[$testName] = $testTypes[$key];
                    }
                }
            }

            // Filter by test type - Only return results where test_type is "Exam"
            $marks = $marks->filter(function ($mark) use ($testTypes) {
                $testNameKey = strtolower(trim($mark->test_name ?? ''));
                $testType = $testTypes[$mark->test_name] ?? $testTypes[$testNameKey] ?? null;
                // Only include if test_type is explicitly "Exam"
                return $testType !== null && strtolower(trim($testType)) === 'exam';
            })->values();

            // Filter by session if provided
            if ($request->filled('session')) {
                $sessionFilter = $request->session;
                $marks = $marks->filter(function ($mark) use ($testSessions, $sessionFilter) {
                    $testNameKey = strtolower(trim($mark->test_name ?? ''));
                    $session = $testSessions[$mark->test_name] ?? $testSessions[$testNameKey] ?? null;
                    return $session === $sessionFilter;
                })->values();
            }

            $remarkFallbackByTest = $this->aggregatedTeacherRemarksByTestName($marks);

            // Format response with all required fields
            $results = $marks->map(function ($mark) use ($testSessions, $testTypes, $remarkFallbackByTest) {
                // Get session and test_type using case-insensitive key
                $testNameKey = strtolower(trim($mark->test_name ?? ''));
                $session = $testSessions[$mark->test_name] ?? $testSessions[$testNameKey] ?? null;
                $testType = $testTypes[$mark->test_name] ?? $testTypes[$testNameKey] ?? null;

                $remark = $this->normalizedRemark($mark->teacher_remarks ?? null)
                    ?? ($remarkFallbackByTest[$testNameKey] ?? null);
                
                return [
                    'subject_name' => $mark->test_name ?? null, // Test name (subject_name)
                    'session' => $session, // Session from Test/Exam model
                    'subject' => $mark->subject ?? null, // Subject name
                    'test_type' => $testType, // Test type (should be "Exam")
                    'total' => $mark->total_marks ? (float) $mark->total_marks : null,
                    'pass' => $mark->passing_marks ? (float) $mark->passing_marks : null,
                    'obtained' => $mark->marks_obtained ? (float) $mark->marks_obtained : null,
                    'teacher_remarks' => $remark,
                    'remarks' => $remark,
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Exam results retrieved successfully',
                'data' => [
                    'student' => [
                        'id' => $student->id,
                        'student_name' => $student->student_name,
                        'student_code' => $student->student_code,
                        'class' => $student->class,
                        'section' => $student->section,
                    ],
                    'results' => $results,
                    'total_records' => $results->count(),
                ],
                'token' => $authenticatedStudent->currentAccessToken()->token ?? null,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving exam results: ' . $e->getMessage(),
                'token' => null,
            ], 500);
        }
    }
}

