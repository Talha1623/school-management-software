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
                    $q->whereRaw('LOWER(TRIM(for_class)) = ?', [strtolower(trim($student->class))])
                      ->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($student->section))]);
                    
                    if ($student->campus) {
                        $q->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($student->campus))]);
                    }
                });
            } elseif ($request->filled('class') && $request->filled('section')) {
                // If class and section are provided, verify parent has a student in that class/section
                $requestedClass = strtolower(trim($request->get('class')));
                $requestedSection = strtolower(trim($request->get('section')));
                
                $hasStudentInClassSection = $students->contains(function($student) use ($requestedClass, $requestedSection) {
                    return strtolower(trim($student->class)) === $requestedClass 
                        && strtolower(trim($student->section)) === $requestedSection;
                });
                
                if (!$hasStudentInClassSection) {
                    return response()->json([
                        'success' => true,
                        'message' => 'No students found in the specified class and section for this parent account.',
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
                
                // Filter quizzes for the requested class and section
                $query->whereRaw('LOWER(TRIM(for_class)) = ?', [$requestedClass])
                      ->whereRaw('LOWER(TRIM(section)) = ?', [$requestedSection]);
                
                // Apply campus filter if provided
                if ($request->filled('campus')) {
                    $campus = strtolower(trim($request->get('campus')));
                    $query->whereRaw('LOWER(TRIM(campus)) = ?', [$campus]);
                }
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
                                $subQ->whereRaw('LOWER(TRIM(for_class)) = ?', [strtolower(trim($pair['class']))])
                                     ->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($pair['section']))]);
                                if ($pair['campus']) {
                                    $subQ->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($pair['campus']))]);
                                }
                            });
                            $first = false;
                        } else {
                            $q->orWhere(function($subQ) use ($pair) {
                                $subQ->whereRaw('LOWER(TRIM(for_class)) = ?', [strtolower(trim($pair['class']))])
                                     ->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($pair['section']))]);
                                if ($pair['campus']) {
                                    $subQ->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($pair['campus']))]);
                                }
                            });
                        }
                    }
                });
                
                // Apply additional filters if provided (but only if they match parent's students)
                if ($request->filled('class')) {
                    $class = strtolower(trim($request->get('class')));
                    $query->whereRaw('LOWER(TRIM(for_class)) = ?', [$class]);
                }

                if ($request->filled('section')) {
                    $section = strtolower(trim($request->get('section')));
                    $query->whereRaw('LOWER(TRIM(section)) = ?', [$section]);
                }

                if ($request->filled('campus')) {
                    $campus = strtolower(trim($request->get('campus')));
                    $query->whereRaw('LOWER(TRIM(campus)) = ?', [$campus]);
                }
            }

            // Filter by date range (optional)
            if ($request->filled('start_date')) {
                if ($request->filled('end_date')) {
                    // If both start_date and end_date are provided, use range
                    $query->whereDate('start_date_time', '>=', $request->get('start_date'));
                } else {
                    // If only start_date is provided, filter for that exact date
                    $query->whereDate('start_date_time', '=', $request->get('start_date'));
                }
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

            // Check if this quiz is for any of parent's students (case-insensitive)
            $hasAccess = $students->contains(function($student) use ($quiz) {
                return strtolower(trim($student->class)) === strtolower(trim($quiz->for_class))
                    && strtolower(trim($student->section)) === strtolower(trim($quiz->section))
                    && (!$student->campus || strtolower(trim($student->campus)) === strtolower(trim($quiz->campus)));
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

    /**
     * Get all quizzes for parent's students (simple list without filters)
     * 
     * GET /api/parent/quizzes/all
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function allQuizzes(Request $request): JsonResponse
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
                            'per_page' => 1000,
                            'total' => 0,
                            'from' => null,
                            'to' => null,
                        ],
                    ],
                    'token' => $request->user()->currentAccessToken()->token ?? null,
                ], 200);
            }

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

            // Build query for quizzes - filter for any of parent's students' class-section combinations
            $query = Quiz::query();
            $query->where(function($q) use ($classSectionPairs) {
                $first = true;
                foreach ($classSectionPairs as $pair) {
                    if ($first) {
                        $q->where(function($subQ) use ($pair) {
                            $subQ->whereRaw('LOWER(TRIM(for_class)) = ?', [strtolower(trim($pair['class']))])
                                 ->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($pair['section']))]);
                            if ($pair['campus']) {
                                $subQ->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($pair['campus']))]);
                            }
                        });
                        $first = false;
                    } else {
                        $q->orWhere(function($subQ) use ($pair) {
                            $subQ->whereRaw('LOWER(TRIM(for_class)) = ?', [strtolower(trim($pair['class']))])
                                 ->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($pair['section']))]);
                            if ($pair['campus']) {
                                $subQ->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($pair['campus']))]);
                            }
                        });
                    }
                }
            });

            // Order by start date time (upcoming first, then past)
            $quizzes = $query->orderBy('start_date_time', 'desc')->paginate(1000);

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
                'message' => 'All quizzes retrieved successfully.',
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
}

