<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StudentMark;
use App\Models\Test;
use App\Models\ParentAccount;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ParentTestResultController extends Controller
{
    /**
     * Get Test Results for Parent's Student
     * Returns: subject_name (test_name), session, subject, total, pass, obtained
     * 
     * GET /api/parent/test-results?student_id=3
     * Optional: test_name, subject, session
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getTestResults(Request $request): JsonResponse
    {
        return $this->getResults($request, 'Test results');
    }

    /**
     * Get Exam Results for Parent's Student (same as test results)
     * Returns: subject_name (test_name), session, subject, total, pass, obtained
     * 
     * GET /api/parent/exam-results?student_id=3
     * Optional: test_name, subject, session
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getExamResults(Request $request): JsonResponse
    {
        return $this->getResults($request, 'Exam results');
    }

    /**
     * Common method to get test/exam results
     * 
     * @param Request $request
     * @param string $messageType
     * @return JsonResponse
     */
    private function getResults(Request $request, string $messageType = 'Test results'): JsonResponse
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

            // Filter by test_name (optional)
            if ($request->filled('test_name')) {
                $query->where('test_name', $request->test_name);
            }

            // Filter by subject (optional)
            if ($request->filled('subject')) {
                $query->whereRaw('LOWER(TRIM(subject)) = ?', [strtolower(trim($request->subject))]);
            }

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

            // Format response with all required fields (same format as student API)
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

