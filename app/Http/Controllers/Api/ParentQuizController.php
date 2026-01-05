<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ParentQuizController extends Controller
{
    /**
     * Get quizzes for parent's students
     * 
     * GET /api/parent/quizzes
     * GET /api/parent/quizzes?student_id=1
     * GET /api/parent/quizzes?class=1st&section=A
     * GET /api/parent/quizzes?campus=Main Campus
     * GET /api/parent/quizzes?per_page=20
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
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

            // Get all connected students
            $students = $parent->students()->get();

            if ($students->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'message' => 'No students found for this parent account.',
                    'data' => [
                        'quizzes' => [],
                        'pagination' => [
                            'current_page' => 1,
                            'last_page' => 1,
                            'per_page' => 10,
                            'total' => 0,
                            'from' => null,
                            'to' => null,
                        ],
                    ],
                    'token' => $request->user()->currentAccessToken()->token ?? null,
                ], 200);
            }

            // Build query for quizzes
            $query = Quiz::query();

            // Filter by student_id if provided
            if ($request->filled('student_id')) {
                $studentId = $request->get('student_id');
                $student = $students->firstWhere('id', $studentId);
                
                if (!$student) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Student not found or not associated with this parent account.',
                        'token' => null,
                    ], 404);
                }

                // Filter quizzes for this specific student's class and section
                $query->where(function($q) use ($student) {
                    $q->where('for_class', $student->class)
                      ->where('section', $student->section);
                    
                    if ($student->campus) {
                        $q->where('campus', $student->campus);
                    }
                });
            } else {
                // Get all unique class-section combinations from parent's students
                $classSectionPairs = $students->map(function($student) {
                    return [
                        'class' => $student->class,
                        'section' => $student->section,
                        'campus' => $student->campus,
                    ];
                })->unique(function($item) {
                    return $item['class'] . '|' . $item['section'] . '|' . ($item['campus'] ?? '');
                });

                // Filter quizzes for any of these class-section combinations
                $query->where(function($q) use ($classSectionPairs) {
                    $first = true;
                    foreach ($classSectionPairs as $pair) {
                        if ($first) {
                            $q->where(function($subQ) use ($pair) {
                                $subQ->where('for_class', $pair['class'])
                                     ->where('section', $pair['section']);
                                if ($pair['campus']) {
                                    $subQ->where('campus', $pair['campus']);
                                }
                            });
                            $first = false;
                        } else {
                            $q->orWhere(function($subQ) use ($pair) {
                                $subQ->where('for_class', $pair['class'])
                                     ->where('section', $pair['section']);
                                if ($pair['campus']) {
                                    $subQ->where('campus', $pair['campus']);
                                }
                            });
                        }
                    }
                });
            }

            // Additional filters
            if ($request->filled('class')) {
                $query->where('for_class', $request->get('class'));
            }

            if ($request->filled('section')) {
                $query->where('section', $request->get('section'));
            }

            if ($request->filled('campus')) {
                $query->where('campus', $request->get('campus'));
            }

            // Filter by date range (optional)
            if ($request->filled('start_date')) {
                $query->whereDate('start_date_time', '>=', $request->get('start_date'));
            }

            if ($request->filled('end_date')) {
                $query->whereDate('start_date_time', '<=', $request->get('end_date'));
            }

            // Pagination
            $perPage = $request->get('per_page', 10);
            $perPage = in_array((int) $perPage, [10, 25, 50, 100], true) ? (int) $perPage : 10;

            // Order by start date time (upcoming first, then past)
            $quizzes = $query->orderBy('start_date_time', 'desc')->paginate($perPage);

            // Format quiz data
            $quizzesData = $quizzes->map(function(Quiz $quiz) {
                return [
                    'id' => $quiz->id,
                    'campus' => $quiz->campus,
                    'quiz_name' => $quiz->quiz_name,
                    'description' => $quiz->description,
                    'for_class' => $quiz->for_class,
                    'section' => $quiz->section,
                    'total_questions' => $quiz->total_questions,
                    'start_date_time' => $quiz->start_date_time ? $quiz->start_date_time->format('Y-m-d H:i:s') : null,
                    'start_date' => $quiz->start_date_time ? $quiz->start_date_time->format('Y-m-d') : null,
                    'start_time' => $quiz->start_date_time ? $quiz->start_date_time->format('H:i:s') : null,
                    'start_date_formatted' => $quiz->start_date_time ? $quiz->start_date_time->format('d M Y') : null,
                    'start_time_formatted' => $quiz->start_date_time ? $quiz->start_date_time->format('h:i A') : null,
                    'start_date_time_formatted' => $quiz->start_date_time ? $quiz->start_date_time->format('d M Y h:i A') : null,
                    'is_upcoming' => $quiz->start_date_time ? $quiz->start_date_time->isFuture() : false,
                    'is_past' => $quiz->start_date_time ? $quiz->start_date_time->isPast() : false,
                    'created_at' => $quiz->created_at ? $quiz->created_at->format('Y-m-d H:i:s') : null,
                    'updated_at' => $quiz->updated_at ? $quiz->updated_at->format('Y-m-d H:i:s') : null,
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Quizzes retrieved successfully.',
                'data' => [
                    'quizzes' => $quizzesData,
                    'pagination' => [
                        'current_page' => $quizzes->currentPage(),
                        'last_page' => $quizzes->lastPage(),
                        'per_page' => $quizzes->perPage(),
                        'total' => $quizzes->total(),
                        'from' => $quizzes->firstItem(),
                        'to' => $quizzes->lastItem(),
                    ],
                ],
                'token' => $request->user()->currentAccessToken()->token ?? null,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving quizzes: ' . $e->getMessage(),
                'token' => null,
            ], 500);
        }
    }

    /**
     * Get a single quiz by ID
     * 
     * GET /api/parent/quizzes/{id}
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function show(Request $request, $id): JsonResponse
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

            // Get all connected students
            $students = $parent->students()->get();

            if ($students->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No students found for this parent account.',
                    'token' => null,
                ], 404);
            }

            // Get the quiz
            $quiz = Quiz::find($id);

            if (!$quiz) {
                return response()->json([
                    'success' => false,
                    'message' => 'Quiz not found.',
                    'token' => null,
                ], 404);
            }

            // Check if this quiz is for any of parent's students
            $hasAccess = $students->contains(function($student) use ($quiz) {
                return $student->class === $quiz->for_class 
                    && $student->section === $quiz->section
                    && (!$student->campus || $student->campus === $quiz->campus);
            });

            if (!$hasAccess) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have access to this quiz.',
                    'token' => null,
                ], 403);
            }

            // Format quiz data
            $quizData = [
                'id' => $quiz->id,
                'campus' => $quiz->campus,
                'quiz_name' => $quiz->quiz_name,
                'description' => $quiz->description,
                'for_class' => $quiz->for_class,
                'section' => $quiz->section,
                'total_questions' => $quiz->total_questions,
                'start_date_time' => $quiz->start_date_time ? $quiz->start_date_time->format('Y-m-d H:i:s') : null,
                'start_date' => $quiz->start_date_time ? $quiz->start_date_time->format('Y-m-d') : null,
                'start_time' => $quiz->start_date_time ? $quiz->start_date_time->format('H:i:s') : null,
                'start_date_formatted' => $quiz->start_date_time ? $quiz->start_date_time->format('d M Y') : null,
                'start_time_formatted' => $quiz->start_date_time ? $quiz->start_date_time->format('h:i A') : null,
                'start_date_time_formatted' => $quiz->start_date_time ? $quiz->start_date_time->format('d M Y h:i A') : null,
                'is_upcoming' => $quiz->start_date_time ? $quiz->start_date_time->isFuture() : false,
                'is_past' => $quiz->start_date_time ? $quiz->start_date_time->isPast() : false,
                'created_at' => $quiz->created_at ? $quiz->created_at->format('Y-m-d H:i:s') : null,
                'updated_at' => $quiz->updated_at ? $quiz->updated_at->format('Y-m-d H:i:s') : null,
            ];

            return response()->json([
                'success' => true,
                'message' => 'Quiz retrieved successfully.',
                'data' => [
                    'quiz' => $quizData,
                ],
                'token' => $request->user()->currentAccessToken()->token ?? null,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving quiz: ' . $e->getMessage(),
                'token' => null,
            ], 500);
        }
    }
}

