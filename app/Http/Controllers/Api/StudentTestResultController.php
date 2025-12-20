<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StudentMark;
use App\Models\Test;
use App\Models\Exam;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudentTestResultController extends Controller
{
    /**
     * Get Test Results for Logged-in Student
     * Returns: subject_name (test_name), session, subject, total, pass, obtained
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

            // Build query for student's marks
            $query = StudentMark::where('student_id', $student->id);

            // Filter by test_name (optional)
            if ($request->filled('test_name')) {
                $query->where('test_name', $request->test_name);
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
            if ($testNames->isNotEmpty()) {
                $tests = Test::whereIn('test_name', $testNames->toArray())
                    ->select('test_name', 'session')
                    ->get()
                    ->keyBy('test_name');
                
                foreach ($tests as $test) {
                    $testSessions[$test->test_name] = $test->session;
                }
            }

            // Filter by session if provided
            if ($request->filled('session')) {
                $sessionFilter = $request->session;
                $marks = $marks->filter(function ($mark) use ($testSessions, $sessionFilter) {
                    $session = $testSessions[$mark->test_name] ?? null;
                    return $session === $sessionFilter;
                })->values();
            }

            // Format response with all required fields
            $results = $marks->map(function ($mark) use ($testSessions) {
                return [
                    'subject_name' => $mark->test_name ?? null, // Test name (subject_name)
                    'session' => $testSessions[$mark->test_name] ?? null, // Session from Test model
                    'subject' => $mark->subject ?? null, // Subject name
                    'total' => $mark->total_marks ? (float) $mark->total_marks : null,
                    'pass' => $mark->passing_marks ? (float) $mark->passing_marks : null,
                    'obtained' => $mark->marks_obtained ? (float) $mark->marks_obtained : null,
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

            // Get all unique test names for this student from marks
            $marks = StudentMark::where('student_id', $student->id)
                ->select('test_name')
                ->distinct()
                ->whereNotNull('test_name')
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
     * Optional: student_id, test_name, session
     * If student_id is not provided, uses authenticated student's ID
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

            // Determine which student ID to use
            $studentId = $request->filled('student_id') ? (int) $request->student_id : $authenticatedStudent->id;
            
            // If student_id is provided, verify it matches authenticated student (security check)
            if ($request->filled('student_id') && $studentId != $authenticatedStudent->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'You can only access your own exam results',
                    'token' => null,
                ], 403);
            }

            // Get student record
            $student = \App\Models\Student::find($studentId);
            
            if (!$student) {
                return response()->json([
                    'success' => false,
                    'message' => 'Student not found',
                    'token' => null,
                ], 404);
            }

            // Build query for student's marks
            $query = StudentMark::where('student_id', $studentId);

            // Filter by test_name (optional) - case-insensitive and trimmed
            if ($request->filled('test_name')) {
                $query->whereRaw('LOWER(TRIM(test_name)) = ?', [strtolower(trim($request->test_name))]);
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

            // Format response with all required fields
            $results = $marks->map(function ($mark) use ($testSessions, $testTypes) {
                // Get session and test_type using case-insensitive key
                $testNameKey = strtolower(trim($mark->test_name ?? ''));
                $session = $testSessions[$mark->test_name] ?? $testSessions[$testNameKey] ?? null;
                $testType = $testTypes[$mark->test_name] ?? $testTypes[$testNameKey] ?? null;
                
                return [
                    'subject_name' => $mark->test_name ?? null, // Test name (subject_name)
                    'session' => $session, // Session from Test/Exam model
                    'subject' => $mark->subject ?? null, // Subject name
                    'test_type' => $testType, // Test type (should be "Exam")
                    'total' => $mark->total_marks ? (float) $mark->total_marks : null,
                    'pass' => $mark->passing_marks ? (float) $mark->passing_marks : null,
                    'obtained' => $mark->marks_obtained ? (float) $mark->marks_obtained : null,
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

