<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ParentAccount;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\QuizSubmission;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class ParentQuizController extends Controller
{
    /**
     * Parent-facing base query for quizzes.
     * Exclude soft-deleted rows when deleted_at exists.
     */
    private function visibleQuizQuery(): Builder
    {
        $query = Quiz::query();
        if (Schema::hasColumn('quizzes', 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        return $query;
    }

    /**
     * Get quiz questions with options for parent.
     *
     * GET /api/parent/quizzes/{id}/questions
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function questions(Request $request, int $id): JsonResponse
    {
        try {
            $parent = $this->resolveAuthenticatedParent($request);

            if (!$parent) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found',
                    'token' => null,
                ], 404);
            }

            $students = $parent->students()->get();
            if ($students->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No students found for this parent account.',
                    'token' => null,
                ], 404);
            }

            $quiz = $this->visibleQuizQuery()->with('questions')->find($id);
            if (!$quiz) {
                return response()->json([
                    'success' => false,
                    'message' => 'Quiz not found.',
                    'token' => null,
                ], 404);
            }

            // Parent can only access quiz for connected student's class/section/campus
            $hasAccess = $students->contains(function($student) use ($quiz) {
                return strtolower(trim((string) $student->class)) === strtolower(trim((string) $quiz->for_class))
                    && strtolower(trim((string) $student->section)) === strtolower(trim((string) $quiz->section))
                    && (!$student->campus || strtolower(trim((string) $student->campus)) === strtolower(trim((string) $quiz->campus)));
            });

            if (!$hasAccess) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have access to this quiz.',
                    'token' => null,
                ], 403);
            }

            $questions = $quiz->questions
                ->sortBy('question_number')
                ->values()
                ->map(function (QuizQuestion $q) {
                    $a1 = isset($q->answer1) ? trim((string) $q->answer1) : '';
                    $a2 = isset($q->answer2) ? trim((string) $q->answer2) : '';
                    $a3 = isset($q->answer3) ? trim((string) $q->answer3) : '';

                    $optionsKeyed = collect([
                        ['key' => 'option_1', 'label' => 'A', 'text' => $a1 !== '' ? $a1 : null],
                        ['key' => 'option_2', 'label' => 'B', 'text' => $a2 !== '' ? $a2 : null],
                        ['key' => 'option_3', 'label' => 'C', 'text' => $a3 !== '' ? $a3 : null],
                    ])->filter(fn ($o) => $o['text'] !== null)->values()->all();

                    $optionsTextOnly = array_values(array_filter([$a1, $a2, $a3], fn ($v) => $v !== ''));

                    return [
                        'id' => $q->id,
                        'question_number' => (int) ($q->question_number ?? 0),
                        'question' => $q->question,
                        // Same keys as DB / teacher entry (use with `question` on one screen)
                        'answer1' => $a1 !== '' ? $a1 : null,
                        'answer2' => $a2 !== '' ? $a2 : null,
                        'answer3' => $a3 !== '' ? $a3 : null,
                        // Structured choices (non-empty only)
                        'options' => $optionsKeyed,
                        // Same shape as GET /api/student/quizzes/{id}/questions (plain strings)
                        'options_text' => $optionsTextOnly,
                    ];
                });

            return response()->json([
                'success' => true,
                'message' => 'Quiz questions retrieved successfully.',
                'data' => [
                    'quiz' => [
                        'id' => $quiz->id,
                        'quiz_name' => $quiz->quiz_name,
                        'description' => $quiz->description,
                        'campus' => $quiz->campus,
                        'for_class' => $quiz->for_class,
                        'section' => $quiz->section,
                        'total_questions' => (int) ($quiz->total_questions ?? $questions->count()),
                        'duration_minutes' => $quiz->duration_minutes,
                        'start_date_time' => $quiz->start_date_time ? $quiz->start_date_time->format('Y-m-d H:i:s') : null,
                    ],
                    'questions' => $questions,
                ],
                'token' => $this->parentSanctumToken($parent),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving quiz questions: ' . $e->getMessage(),
                'token' => null,
            ], 500);
        }
    }

    /**
     * Get quizzes for one child (class/section/campus taken from student record).
     *
     * GET /api/parent/quizzes?student_id=1
     * GET /api/parent/quizzes?student_id=1&per_page=25
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $parent = $this->resolveAuthenticatedParent($request);

            if (!$parent) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found',
                    'token' => null,
                ], 404);
            }

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
                    'token' => $this->parentSanctumToken($parent),
                ], 200);
            }

            if (!$request->filled('student_id')) {
                return response()->json([
                    'success' => false,
                    'message' => 'student_id is required.',
                    'token' => $this->parentSanctumToken($parent),
                ], 422);
            }

            $studentId = (int) $request->get('student_id');
            $student = $students->firstWhere('id', $studentId);

            if (!$student) {
                return response()->json([
                    'success' => false,
                    'message' => 'Student not found or does not belong to this parent.',
                    'token' => null,
                ], 404);
            }

            $query = $this->visibleQuizQuery();
            $this->scopeQuizQueryForStudent($query, $student);

            // Pagination
            $perPage = $request->get('per_page', 10);
            $perPage = in_array((int) $perPage, [10, 25, 50, 100], true) ? (int) $perPage : 10;

            // Order by start date time (upcoming first, then past)
            $quizzes = $query->orderBy('start_date_time', 'desc')->paginate($perPage);
            
            // Debug: Log query for troubleshooting (remove in production if needed)
            // \Log::info('Parent Quiz Query', [
            //     'sql' => $query->toSql(),
            //     'bindings' => $query->getBindings(),
            //     'total_quizzes' => $quizzes->total(),
            // ]);

            $quizIds = $quizzes->pluck('id')->all();
            $submissionsByQuizId = collect();
            if (Schema::hasTable('quiz_submissions') && !empty($quizIds)) {
                $submissionsQuery = QuizSubmission::query()->whereIn('quiz_id', $quizIds);
                $hasSubmissionStudentId = Schema::hasColumn('quiz_submissions', 'student_id');
                $hasSubmissionStudentCode = Schema::hasColumn('quiz_submissions', 'student_code') && !empty($student->student_code);

                if ($hasSubmissionStudentId || $hasSubmissionStudentCode) {
                    $submissionsQuery->where(function ($query) use ($student, $hasSubmissionStudentId, $hasSubmissionStudentCode) {
                        if ($hasSubmissionStudentId) {
                            $query->where('student_id', $student->id);
                        }

                        if ($hasSubmissionStudentCode) {
                            $method = $hasSubmissionStudentId ? 'orWhere' : 'where';
                            $query->{$method}('student_code', $student->student_code);
                        }
                    });
                } else {
                    $submissionsQuery->whereRaw('1 = 0');
                }

                $submissionsByQuizId = $submissionsQuery
                    ->latest('id')
                    ->get()
                    ->unique('quiz_id')
                    ->keyBy('quiz_id');
            }

            // Format quiz data
            $quizzesData = $quizzes->map(function(Quiz $quiz) use ($submissionsByQuizId) {
                $startTime = $quiz->start_date_time;
                $endTime = $startTime && $quiz->duration_minutes ? $startTime->copy()->addMinutes($quiz->duration_minutes) : null;
                $submission = $submissionsByQuizId->get($quiz->id);
                $hasSubmitted = $submission !== null;
                
                $isUpcoming = $startTime ? $startTime->isFuture() : false;
                $isExpired = $endTime ? $endTime->isPast() : false;
                $isActive = !$isUpcoming && !$isExpired;
                $uploadStatus = $hasSubmitted
                    ? 'completed'
                    : ($isUpcoming ? 'upcoming' : ($isExpired ? 'missed' : 'pending'));
                
                $quizData = [
                    'id' => $quiz->id,
                    'campus' => $quiz->campus,
                    'quiz_name' => $quiz->quiz_name,
                    'description' => $quiz->description,
                    'for_class' => $quiz->for_class,
                    'section' => $quiz->section,
                    'total_questions' => $quiz->total_questions,
                    'start_date_time' => $startTime ? $startTime->format('Y-m-d H:i:s') : null,
                    'start_date' => $startTime ? $startTime->format('Y-m-d') : null,
                    'start_time' => $startTime ? $startTime->format('H:i:s') : null,
                    'start_date_formatted' => $startTime ? $startTime->format('d M Y') : null,
                    'start_time_formatted' => $startTime ? $startTime->format('h:i A') : null,
                    'start_date_time_formatted' => $startTime ? $startTime->format('d M Y h:i A') : null,
                    'end_date_time' => $endTime ? $endTime->format('Y-m-d H:i:s') : null,
                    'end_date_time_formatted' => $endTime ? $endTime->format('d M Y h:i A') : null,
                    'duration_minutes' => $quiz->duration_minutes ?? null,
                    'is_upcoming' => $hasSubmitted ? false : $isUpcoming,
                    'is_active' => $hasSubmitted ? false : $isActive,
                    'is_expired' => $hasSubmitted ? true : $isExpired,
                    'is_past' => $hasSubmitted ? true : ($startTime ? $startTime->isPast() : false),
                    'has_submitted' => $hasSubmitted,
                    'already_uploaded' => $hasSubmitted,
                    'upload_status' => $uploadStatus,
                    'status' => $uploadStatus,
                    'can_upload' => !$hasSubmitted && $isActive,
                    'submission' => $submission ? [
                        'id' => $submission->id,
                        'obtained_marks' => (int) ($submission->obtained_marks ?? 0),
                        'total_marks' => (int) ($submission->total_marks ?? 0),
                        'submitted_at' => $submission->submitted_at ? $submission->submitted_at->format('Y-m-d H:i:s') : null,
                    ] : null,
                    'created_at' => $quiz->created_at ? $quiz->created_at->format('Y-m-d H:i:s') : null,
                    'updated_at' => $quiz->updated_at ? $quiz->updated_at->format('Y-m-d H:i:s') : null,
                ];
                
                // Add marks if quiz is expired
                if ($isExpired) {
                    $questions = $quiz->questions()->orderBy('question_number')->get();
                    $quizData['marks'] = $questions->map(function (QuizQuestion $q) {
                        return [
                            'question_number' => $q->question_number,
                            'question' => $q->question,
                            'answers' => [
                                ['answer' => $q->answer1, 'marks' => (int) ($q->marks1 ?? 0)],
                                ['answer' => $q->answer2, 'marks' => (int) ($q->marks2 ?? 0)],
                                ['answer' => $q->answer3, 'marks' => (int) ($q->marks3 ?? 0)],
                            ],
                        ];
                    })->values();
                }
                
                return $quizData;
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
                'token' => $this->parentSanctumToken($parent),
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
     * Quiz list with same response shape as GET /api/parent/tests (students + tests + pagination).
     *
     * GET /api/parent/quizzes/tests-format
     */
    public function indexLikeTests(Request $request): JsonResponse
    {
        try {
            $parent = $this->resolveAuthenticatedParent($request);

            if (!$parent) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found',
                    'token' => null,
                ], 404);
            }

            $students = $parent->students()->get();

            if ($students->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Tests retrieved successfully',
                    'data' => [
                        'students' => [],
                        'tests' => [],
                        'pagination' => [
                            'current_page' => 1,
                            'last_page' => 1,
                            'per_page' => 30,
                            'total' => 0,
                            'from' => 0,
                            'to' => 0,
                        ],
                    ],
                    'token' => $this->parentSanctumToken($parent),
                ], 200);
            }

            if ($request->filled('student_id')) {
                $studentId = (int) $request->get('student_id');
                $student = $students->firstWhere('id', $studentId);
                if (!$student) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Student not found or does not belong to this parent',
                        'token' => null,
                    ], 404);
                }
                $students = collect([$student]);
            }

            $query = $this->visibleQuizQuery();

            $filterResponse = $this->applyParentQuizScopeFilters($request, $query, $students, $parent);
            if ($filterResponse !== null) {
                if ($filterResponse->getStatusCode() === 200) {
                    $payload = $filterResponse->getData(true);
                    if (!is_array($payload)) {
                        return $filterResponse;
                    }
                    if (($payload['data']['quizzes'] ?? null) === []) {
                        return response()->json([
                            'success' => true,
                            'message' => 'Tests retrieved successfully',
                            'data' => [
                                'students' => $this->formatParentStudentsForTestsList($students),
                                'tests' => [],
                                'pagination' => [
                                    'current_page' => 1,
                                    'last_page' => 1,
                                    'per_page' => 30,
                                    'total' => 0,
                                    'from' => 0,
                                    'to' => 0,
                                ],
                            ],
                            'token' => $this->parentSanctumToken($parent),
                        ], 200);
                    }
                }
                return $filterResponse;
            }

            if ($request->filled('date')) {
                try {
                    $query->whereDate('start_date_time', Carbon::parse($request->date)->format('Y-m-d'));
                } catch (\Exception $e) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid date format. Please use Y-m-d format (e.g., 2024-12-20)',
                        'token' => null,
                    ], 400);
                }
            }

            if ($request->filled('date_from')) {
                $query->whereDate('start_date_time', '>=', $request->date_from);
            }

            if ($request->filled('date_to')) {
                $query->whereDate('start_date_time', '<=', $request->date_to);
            }

            if ($request->filled('type')) {
                $today = now()->format('Y-m-d');
                if ($request->type === 'upcoming') {
                    $query->whereDate('start_date_time', '>=', $today);
                } elseif ($request->type === 'past') {
                    $query->whereDate('start_date_time', '<', $today);
                }
            }

            if ($request->filled('start_date')) {
                if ($request->filled('end_date')) {
                    $query->whereDate('start_date_time', '>=', $request->get('start_date'));
                } else {
                    $query->whereDate('start_date_time', '=', $request->get('start_date'));
                }
            }

            if ($request->filled('end_date')) {
                $query->whereDate('start_date_time', '<=', $request->get('end_date'));
            }

            $perPage = $request->get('per_page', 30);
            $perPage = in_array((int) $perPage, [10, 25, 30, 50, 100], true) ? (int) $perPage : 30;

            $quizzes = $query->orderBy('start_date_time', 'desc')->paginate($perPage);

            $testsData = collect($quizzes->items())->map(function (Quiz $quiz) {
                return $this->quizToTestListRow($quiz);
            })->values();

            $studentsData = $this->formatParentStudentsForTestsList($students);

            $total = $quizzes->total();
            $offset = ($quizzes->currentPage() - 1) * $quizzes->perPage();

            return response()->json([
                'success' => true,
                'message' => 'Tests retrieved successfully',
                'data' => [
                    'students' => $studentsData,
                    'tests' => $testsData,
                    'pagination' => [
                        'current_page' => $quizzes->currentPage(),
                        'last_page' => $quizzes->lastPage(),
                        'per_page' => $quizzes->perPage(),
                        'total' => $total,
                        'from' => $total > 0 ? $offset + 1 : 0,
                        'to' => min($offset + $quizzes->perPage(), $total),
                    ],
                ],
                'token' => $this->parentSanctumToken($parent),
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
     * Limit quizzes to the student's class, section, and campus (when set).
     */
    private function scopeQuizQueryForStudent(Builder $query, Student $student): void
    {
        $query->where(function ($q) use ($student) {
            $q->whereRaw('LOWER(TRIM(for_class)) = ?', [strtolower(trim((string) $student->class))])
                ->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim((string) $student->section))]);

            if ($student->campus) {
                $q->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim((string) $student->campus))]);
            }
        });
    }

    /**
     * Shared class/section/campus scope for parent quiz list.
     *
     * @return JsonResponse|null Return early JSON or null to continue.
     */
    private function applyParentQuizScopeFilters(Request $request, Builder $query, Collection $students, ParentAccount $parent): ?JsonResponse
    {
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

            $this->scopeQuizQueryForStudent($query, $student);
        } elseif ($request->filled('class') && $request->filled('section')) {
            // If class and section are provided, verify parent has a student in that class/section
            $requestedClass = strtolower(trim($request->get('class')));
            $requestedSection = strtolower(trim($request->get('section')));

            // More flexible class matching: "one" should match "Class one", "one", "Class One", etc.
            $hasStudentInClassSection = $students->contains(function ($student) use ($requestedClass, $requestedSection) {
                $studentClass = strtolower(trim($student->class ?? ''));
                $studentSection = strtolower(trim($student->section ?? ''));

                // Exact match
                if ($studentClass === $requestedClass && $studentSection === $requestedSection) {
                    return true;
                }

                // Flexible match: "one" matches "Class one" or "one" matches "Class One"
                $studentClassNormalized = preg_replace('/^(class\s+)?/i', '', $studentClass);
                $requestedClassNormalized = preg_replace('/^(class\s+)?/i', '', $requestedClass);

                return $studentClassNormalized === $requestedClassNormalized
                    && $studentSection === $requestedSection;
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
                    'token' => $this->parentSanctumToken($parent),
                ], 200);
            }

            // Filter quizzes for the requested class and section (flexible matching)
            // Match "one" with "Class one", "one", "Class One", etc.
            $query->where(function ($q) use ($requestedClass, $requestedSection) {
                // Exact match
                $q->whereRaw('LOWER(TRIM(for_class)) = ?', [$requestedClass])
                    ->whereRaw('LOWER(TRIM(section)) = ?', [$requestedSection]);

                // Flexible match: remove "class " prefix if present and match
                $classWithoutPrefix = preg_replace('/^(class\s+)?/i', '', $requestedClass);
                if ($classWithoutPrefix !== $requestedClass) {
                    $q->orWhere(function ($subQ) use ($classWithoutPrefix, $requestedSection) {
                        $subQ->whereRaw('LOWER(TRIM(for_class)) = ?', [$classWithoutPrefix])
                            ->whereRaw('LOWER(TRIM(section)) = ?', [$requestedSection]);
                    });
                    // Also try with "Class " prefix
                    $classWithPrefix = 'class ' . $classWithoutPrefix;
                    $q->orWhere(function ($subQ) use ($classWithPrefix, $requestedSection) {
                        $subQ->whereRaw('LOWER(TRIM(for_class)) = ?', [$classWithPrefix])
                            ->whereRaw('LOWER(TRIM(section)) = ?', [$requestedSection]);
                    });
                }
            });

            // Apply campus filter if provided
            if ($request->filled('campus')) {
                $campus = strtolower(trim($request->get('campus')));
                $query->whereRaw('LOWER(TRIM(campus)) = ?', [$campus]);
            }
        } else {
            // Get all unique class-section combinations from parent's students
            $classSectionPairs = $students->map(function ($student) {
                return [
                    'class' => $student->class,
                    'section' => $student->section,
                    'campus' => $student->campus,
                ];
            })->unique(function ($item) {
                return $item['class'] . '|' . $item['section'] . '|' . ($item['campus'] ?? '');
            });

            // Filter quizzes for any of these class-section combinations
            $query->where(function ($q) use ($classSectionPairs) {
                $first = true;
                foreach ($classSectionPairs as $pair) {
                    if ($first) {
                        $q->where(function ($subQ) use ($pair) {
                            $subQ->whereRaw('LOWER(TRIM(for_class)) = ?', [strtolower(trim($pair['class']))])
                                ->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($pair['section']))]);
                            if ($pair['campus']) {
                                $subQ->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($pair['campus']))]);
                            }
                        });
                        $first = false;
                    } else {
                        $q->orWhere(function ($subQ) use ($pair) {
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

        return null;
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
            $parent = $this->resolveAuthenticatedParent($request);

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
            $quiz = $this->visibleQuizQuery()->find($id);

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

            // Calculate quiz timing
            $startTime = $quiz->start_date_time;
            $endTime = $startTime && $quiz->duration_minutes ? $startTime->copy()->addMinutes($quiz->duration_minutes) : null;
            
            $isUpcoming = $startTime ? $startTime->isFuture() : false;
            $isExpired = $endTime ? $endTime->isPast() : false;
            $isActive = !$isUpcoming && !$isExpired;
            
            // Format quiz data
            $quizData = [
                'id' => $quiz->id,
                'campus' => $quiz->campus,
                'quiz_name' => $quiz->quiz_name,
                'description' => $quiz->description,
                'for_class' => $quiz->for_class,
                'section' => $quiz->section,
                'total_questions' => $quiz->total_questions,
                'start_date_time' => $startTime ? $startTime->format('Y-m-d H:i:s') : null,
                'start_date' => $startTime ? $startTime->format('Y-m-d') : null,
                'start_time' => $startTime ? $startTime->format('H:i:s') : null,
                'start_date_formatted' => $startTime ? $startTime->format('d M Y') : null,
                'start_time_formatted' => $startTime ? $startTime->format('h:i A') : null,
                'start_date_time_formatted' => $startTime ? $startTime->format('d M Y h:i A') : null,
                'end_date_time' => $endTime ? $endTime->format('Y-m-d H:i:s') : null,
                'end_date_time_formatted' => $endTime ? $endTime->format('d M Y h:i A') : null,
                'duration_minutes' => $quiz->duration_minutes ?? null,
                'is_upcoming' => $isUpcoming,
                'is_active' => $isActive,
                'is_expired' => $isExpired,
                'is_past' => $startTime ? $startTime->isPast() : false,
                'created_at' => $quiz->created_at ? $quiz->created_at->format('Y-m-d H:i:s') : null,
                'updated_at' => $quiz->updated_at ? $quiz->updated_at->format('Y-m-d H:i:s') : null,
            ];
            
            // Add marks if quiz is expired
            if ($isExpired) {
                $questions = $quiz->questions()->orderBy('question_number')->get();
                $quizData['marks'] = $questions->map(function (QuizQuestion $q) {
                    return [
                        'question_number' => $q->question_number,
                        'question' => $q->question,
                        'answers' => [
                            ['answer' => $q->answer1, 'marks' => (int) ($q->marks1 ?? 0)],
                            ['answer' => $q->answer2, 'marks' => (int) ($q->marks2 ?? 0)],
                            ['answer' => $q->answer3, 'marks' => (int) ($q->marks3 ?? 0)],
                        ],
                    ];
                })->values();
            }

            return response()->json([
                'success' => true,
                'message' => 'Quiz retrieved successfully.',
                'data' => [
                    'quiz' => $quizData,
                ],
                'token' => $this->parentSanctumToken($parent),
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
            $parent = $this->resolveAuthenticatedParent($request);

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
                    'token' => $this->parentSanctumToken($parent),
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
            $query = $this->visibleQuizQuery();
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
                $startTime = $quiz->start_date_time;
                $endTime = $startTime && $quiz->duration_minutes ? $startTime->copy()->addMinutes($quiz->duration_minutes) : null;
                
                $isUpcoming = $startTime ? $startTime->isFuture() : false;
                $isExpired = $endTime ? $endTime->isPast() : false;
                $isActive = !$isUpcoming && !$isExpired;
                
                $quizData = [
                    'id' => $quiz->id,
                    'campus' => $quiz->campus,
                    'quiz_name' => $quiz->quiz_name,
                    'description' => $quiz->description,
                    'for_class' => $quiz->for_class,
                    'section' => $quiz->section,
                    'total_questions' => $quiz->total_questions,
                    'start_date_time' => $startTime ? $startTime->format('Y-m-d H:i:s') : null,
                    'start_date' => $startTime ? $startTime->format('Y-m-d') : null,
                    'start_time' => $startTime ? $startTime->format('H:i:s') : null,
                    'start_date_formatted' => $startTime ? $startTime->format('d M Y') : null,
                    'start_time_formatted' => $startTime ? $startTime->format('h:i A') : null,
                    'start_date_time_formatted' => $startTime ? $startTime->format('d M Y h:i A') : null,
                    'end_date_time' => $endTime ? $endTime->format('Y-m-d H:i:s') : null,
                    'end_date_time_formatted' => $endTime ? $endTime->format('d M Y h:i A') : null,
                    'duration_minutes' => $quiz->duration_minutes ?? null,
                    'is_upcoming' => $isUpcoming,
                    'is_active' => $isActive,
                    'is_expired' => $isExpired,
                    'is_past' => $startTime ? $startTime->isPast() : false,
                    'created_at' => $quiz->created_at ? $quiz->created_at->format('Y-m-d H:i:s') : null,
                    'updated_at' => $quiz->updated_at ? $quiz->updated_at->format('Y-m-d H:i:s') : null,
                ];
                
                // Add marks if quiz is expired
                if ($isExpired) {
                    $questions = $quiz->questions()->orderBy('question_number')->get();
                    $quizData['marks'] = $questions->map(function (QuizQuestion $q) {
                        return [
                            'question_number' => $q->question_number,
                            'question' => $q->question,
                            'answers' => [
                                ['answer' => $q->answer1, 'marks' => (int) ($q->marks1 ?? 0)],
                                ['answer' => $q->answer2, 'marks' => (int) ($q->marks2 ?? 0)],
                                ['answer' => $q->answer3, 'marks' => (int) ($q->marks3 ?? 0)],
                            ],
                        ];
                    })->values();
                }
                
                return $quizData;
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
                'token' => $this->parentSanctumToken($parent),
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
     * Map a Quiz row to the same keys as parent test list items.
     */
    private function quizToTestListRow(Quiz $quiz): array
    {
        $testDate = $quiz->start_date_time ? Carbon::parse($quiz->start_date_time) : null;
        $endTime = $testDate && $quiz->duration_minutes
            ? $testDate->copy()->addMinutes((int) $quiz->duration_minutes)
            : null;

        $isUpcoming = $testDate && $testDate->format('Y-m-d') >= now()->format('Y-m-d');

        $desc = trim((string) ($quiz->description ?? ''));
        $descriptionDisplay = $desc !== '' ? $desc : 'No';

        $resultStatus = false;
        if ($endTime && $endTime->isPast()) {
            $resultStatus = true;
        } elseif ($testDate && $testDate->isPast() && !$quiz->duration_minutes) {
            $resultStatus = true;
        }

        return [
            'id' => $quiz->id,
            'test_name' => $quiz->quiz_name,
            'subject' => 'Quiz',
            'test_type' => 'quiz',
            'description' => $descriptionDisplay,
            'date' => $testDate ? $testDate->format('Y-m-d') : null,
            'date_formatted' => $testDate ? $testDate->format('d M Y') : null,
            'session' => $this->academicSessionFromQuizDate($testDate),
            'campus' => $quiz->campus,
            'class' => $quiz->for_class,
            'section' => $quiz->section,
            'is_upcoming' => $isUpcoming,
            'result_status' => $resultStatus,
        ];
    }

    private function formatParentStudentsForTestsList(Collection $students): array
    {
        return $students->map(function ($student) {
            return [
                'id' => $student->id,
                'student_name' => $student->student_name,
                'student_code' => $student->student_code,
                'class' => $student->class,
                'section' => $student->section,
                'campus' => $student->campus,
            ];
        })->values()->all();
    }

    private function academicSessionFromQuizDate(?Carbon $date): ?string
    {
        if (!$date) {
            return null;
        }
        $y = (int) $date->format('Y');
        $m = (int) $date->format('n');
        if ($m >= 4) {
            return $y . '/' . ($y + 1);
        }

        return ($y - 1) . '/' . $y;
    }

    /**
     * Plain Sanctum token string for JSON responses (avoids generic Authenticatable + IDE stubs).
     */
    private function parentSanctumToken(ParentAccount $parent): ?string
    {
        return $parent->currentAccessToken()?->token ?? null;
    }

    /**
     * Ensure authenticated user is a ParentAccount model.
     */
    private function resolveAuthenticatedParent(Request $request): ?ParentAccount
    {
        $user = $request->user();

        return $user instanceof ParentAccount ? $user : null;
    }
}

