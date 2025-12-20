<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StudentMark;
use App\Models\Test;
use App\Models\Exam;
use App\Models\ParentAccount;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ParentTestResultController extends Controller
{
    /**
     * Get Test Results for Parent's Student
     * Returns: subject_name (test_name), session, subject, total, pass, obtained
     * Only returns results where test_type is NOT "Exam"
     * 
     * GET /api/parent/test-results?student_id=3
     * Optional: test_name, subject, session
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getTestResults(Request $request): JsonResponse
    {
        return $this->getResults($request, 'Test results', false);
    }

    /**
     * Get Exam Results for Parent's Student
     * Returns: subject_name (test_name), session, subject, total, pass, obtained
     * Only returns results where test_type IS "Exam"
     * 
     * GET /api/parent/exam-results?student_id=3
     * Optional: test_name, subject, session
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getExamResults(Request $request): JsonResponse
    {
        return $this->getResults($request, 'Exam results', true);
    }

    /**
     * Common method to get test/exam results
     * 
     * @param Request $request
     * @param string $messageType
     * @param bool $isExam If true, only return exam results. If false, only return test results (not exams)
     * @return JsonResponse
     */
    private function getResults(Request $request, string $messageType = 'Test results', bool $isExam = false): JsonResponse
    {
        try {
            $parent = $request->user();

            if (!$parent) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found',
                    'token' => null,
                ], 404);
            }

            // Validate required student_id parameter
            if (!$request->filled('student_id')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Student ID is required',
                    'token' => null,
                ], 400);
            }

            $studentId = (int) $request->student_id;

            // Verify student belongs to this parent
            $student = $parent->students()->find($studentId);
            
            if (!$student) {
                return response()->json([
                    'success' => false,
                    'message' => 'Student not found or does not belong to this parent',
                    'token' => null,
                ], 404);
            }

            // Build query for student's marks
            $query = StudentMark::where('student_id', $studentId);

            // Filter by test_name (optional) - case-insensitive and trimmed
            if ($request->filled('test_name')) {
                $query->whereRaw('LOWER(TRIM(test_name)) = ?', [strtolower(trim($request->test_name))]);
            }

            // Filter by subject (optional) - case-insensitive and trimmed
            if ($request->filled('subject')) {
                $query->whereRaw('LOWER(TRIM(subject)) = ?', [strtolower(trim($request->subject))]);
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

            // Filter by test type (Exam vs Test)
            if ($isExam) {
                // Only return results where test_type is "Exam"
                // This includes tests from Exam table (exam management) or Test table with test_type = "Exam"
                $marks = $marks->filter(function ($mark) use ($testTypes) {
                    $testNameKey = strtolower(trim($mark->test_name ?? ''));
                    $testType = $testTypes[$mark->test_name] ?? $testTypes[$testNameKey] ?? null;
                    // Only include if test_type is explicitly "Exam"
                    return $testType !== null && strtolower(trim($testType)) === 'exam';
                })->values();
            } else {
                // Only return results where test_type is NOT "Exam"
                // Include all tests that are NOT exams (including tests not in Test/Exam tables - from Marks Entry)
                $marks = $marks->filter(function ($mark) use ($testTypes) {
                    $testNameKey = strtolower(trim($mark->test_name ?? ''));
                    $testType = $testTypes[$mark->test_name] ?? $testTypes[$testNameKey] ?? null;
                    // If test_type is null or not found in Test/Exam tables, include it (from Marks Entry)
                    if ($testType === null) {
                        return true; // Include if test not found in Test/Exam tables (Marks Entry only)
                    }
                    // Include if test_type is NOT "Exam"
                    return strtolower(trim($testType)) !== 'exam';
                })->values();
            }

            // Filter by session if provided
            if ($request->filled('session')) {
                $sessionFilter = $request->session;
                $marks = $marks->filter(function ($mark) use ($testSessions, $sessionFilter) {
                    $testNameKey = strtolower(trim($mark->test_name ?? ''));
                    $session = $testSessions[$mark->test_name] ?? $testSessions[$testNameKey] ?? null;
                    return $session === $sessionFilter;
                })->values();
            }

            // Format response with all required fields (same format as student API)
            $results = $marks->map(function ($mark) use ($testSessions, $testTypes) {
                // Get session and test_type using case-insensitive key
                $testNameKey = strtolower(trim($mark->test_name ?? ''));
                $session = $testSessions[$mark->test_name] ?? $testSessions[$testNameKey] ?? null;
                $testType = $testTypes[$mark->test_name] ?? $testTypes[$testNameKey] ?? null;
                
                return [
                    'subject_name' => $mark->test_name ?? null, // Test name (subject_name)
                    'session' => $session, // Session from Test/Exam model
                    'subject' => $mark->subject ?? null, // Subject name
                    'test_type' => $testType, // Test type (Quiz, Mid Term, Exam, etc.)
                    'total' => $mark->total_marks ? (float) $mark->total_marks : null,
                    'pass' => $mark->passing_marks ? (float) $mark->passing_marks : null,
                    'obtained' => $mark->marks_obtained ? (float) $mark->marks_obtained : null,
                ];
            });

            return response()->json([
                'success' => true,
                'message' => $messageType . ' retrieved successfully',
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
                'message' => 'An error occurred while retrieving results: ' . $e->getMessage(),
                'token' => null,
            ], 500);
        }
    }
}

