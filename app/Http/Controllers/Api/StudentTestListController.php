<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Test;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudentTestListController extends Controller
{
    /**
     * Get Test List for Student
     * Returns all tests for student's class (all sections)
     * 
     * GET /api/student/tests
     * Optional filters: date, date_from, date_to, type (upcoming/past), per_page
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getTests(Request $request): JsonResponse
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

            // Check if student has class information
            if (!$student->class) {
                return response()->json([
                    'success' => false,
                    'message' => 'Student class information not found',
                    'token' => null,
                ], 400);
            }

            // Use student's class (all sections - jitne bhi tests assign kiye hain wo saare show honge)
            $studentClass = trim($student->class);

            // Build query for student's class
            $query = Test::whereRaw('LOWER(TRIM(for_class)) = ?', [strtolower($studentClass)]);

            // Filter by campus if student has campus
            if ($student->campus) {
                $query->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($student->campus))]);
            }

            // Optional date filters
            if ($request->filled('date')) {
                try {
                    $testDate = \Carbon\Carbon::parse($request->date);
                    $query->whereDate('date', $testDate->format('Y-m-d'));
                } catch (\Exception $e) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid date format. Please use Y-m-d format (e.g., 2024-12-20)',
                        'token' => null,
                    ], 400);
                }
            }

            if ($request->filled('date_from')) {
                $query->whereDate('date', '>=', $request->date_from);
            }

            if ($request->filled('date_to')) {
                $query->whereDate('date', '<=', $request->date_to);
            }

            // Filter by upcoming/past tests
            if ($request->filled('type')) {
                $today = now()->format('Y-m-d');
                if ($request->type === 'upcoming') {
                    $query->whereDate('date', '>=', $today);
                } elseif ($request->type === 'past') {
                    $query->whereDate('date', '<', $today);
                }
            }

            // Pagination
            $perPage = $request->get('per_page', 30);
            $perPage = in_array((int)$perPage, [10, 25, 30, 50, 100], true) ? (int)$perPage : 30;

            // Get tests with pagination
            $tests = $query->orderBy('date', 'desc')
                ->orderBy('test_name', 'asc')
                ->paginate($perPage);

            // Format test data
            $testsData = $tests->map(function ($test) {
                $testDate = $test->date;
                $isUpcoming = $testDate && $testDate->format('Y-m-d') >= now()->format('Y-m-d');
                
                return [
                    'id' => $test->id,
                    'test_name' => $test->test_name,
                    'subject' => $test->subject,
                    'test_type' => $test->test_type,
                    'description' => $test->description,
                    'date' => $testDate ? $testDate->format('Y-m-d') : null,
                    'date_formatted' => $testDate ? $testDate->format('d M Y') : null,
                    'session' => $test->session,
                    'campus' => $test->campus,
                    'class' => $test->for_class,
                    'section' => $test->section,
                    'is_upcoming' => $isUpcoming,
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Tests retrieved successfully',
                'data' => [
                    'student' => [
                        'id' => $student->id,
                        'student_name' => $student->student_name,
                        'student_code' => $student->student_code,
                        'class' => $student->class,
                        'section' => $student->section,
                        'campus' => $student->campus,
                    ],
                    'tests' => $testsData,
                    'pagination' => [
                        'current_page' => $tests->currentPage(),
                        'last_page' => $tests->lastPage(),
                        'per_page' => $tests->perPage(),
                        'total' => $tests->total(),
                        'from' => $tests->firstItem(),
                        'to' => $tests->lastItem(),
                    ],
                ],
                'token' => $request->user()->currentAccessToken()->token ?? null,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving tests: ' . $e->getMessage(),
                'token' => null,
            ], 500);
        }
    }
}

