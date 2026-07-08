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

            $timing = $this->quizTiming($quiz);
            $includeMarks = $timing['is_expired'];
            $questions = $this->formatQuestionsWebShape(collect($quiz->questions), $includeMarks);

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
                        'total_questions' => (int) ($quiz->total_questions ?? count($questions)),
                        'duration_minutes' => $quiz->duration_minutes,
                        'start_date_time' => $timing['start'] ? $timing['start']->format('Y-m-d H:i:s') : null,
                        'is_expired' => $timing['is_expired'],
                        'timezone' => Quiz::schoolTimezone(),
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

            $query = $this->visibleQuizQuery()->with('questions');
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
            $quizzesData = $quizzes->map(function (Quiz $quiz) use ($submissionsByQuizId, $student) {
                $submission = $submissionsByQuizId->get($quiz->id);

                return $this->formatQuizRowForParent($quiz, $submission, $student);
            });

            return response()->json([
                'success' => true,
                'message' => 'Quizzes retrieved successfully.',
                'data' => [
                    'student' => [
                        'id' => $student->id,
                        'student_name' => $student->student_name,
                        'student_code' => $student->student_code,
                        'class' => $student->class,
                        'section' => $student->section,
                        'campus' => $student->campus,
                    ],
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
        $studentClass = strtolower(trim((string) $student->class));
        $studentSection = strtolower(trim((string) $student->section));
        $classNormalized = preg_replace('/^(class\s+)?/i', '', $studentClass) ?? $studentClass;
        $studentClassCompact = str_replace(' ', '', $studentClass);

        $query->where(function ($outer) use ($studentClass, $studentSection, $classNormalized, $studentClassCompact) {
            $outer->where(function ($q) use ($studentClass, $studentSection) {
                $q->whereRaw('LOWER(TRIM(for_class)) = ?', [$studentClass])
                    ->whereRaw('LOWER(TRIM(section)) = ?', [$studentSection]);
            })->orWhere(function ($q) use ($classNormalized, $studentSection) {
                $q->whereRaw('LOWER(TRIM(for_class)) = ?', [$classNormalized])
                    ->whereRaw('LOWER(TRIM(section)) = ?', [$studentSection]);
            })->orWhere(function ($q) use ($studentClassCompact, $studentSection) {
                $q->whereRaw('LOWER(REPLACE(for_class, " ", "")) = ?', [$studentClassCompact])
                    ->whereRaw('LOWER(TRIM(section)) = ?', [$studentSection]);
            })->orWhere(function ($q) use ($classNormalized, $studentSection) {
                $q->whereRaw('LOWER(TRIM(for_class)) LIKE ?', ['%' . $classNormalized . '%'])
                    ->whereRaw('LOWER(TRIM(section)) = ?', [$studentSection]);
            });
        });

        if ($student->campus) {
            $campus = strtolower(trim((string) $student->campus));
            $query->where(function ($q) use ($campus) {
                $q->whereRaw('LOWER(TRIM(campus)) = ?', [$campus])
                    ->orWhereNull('campus')
                    ->orWhere('campus', '');
            });
        }
    }

    /**
     * @return array{start: ?Carbon, end: ?Carbon, is_upcoming: bool, is_expired: bool, is_active: bool}
     */
    private function quizTiming(Quiz $quiz): array
    {
        $start = $quiz->startAtLocal();
        $durationMinutes = (int) ($quiz->duration_minutes ?? 0);
        $end = ($start && $durationMinutes > 0) ? $start->copy()->addMinutes($durationMinutes) : null;
        $now = $quiz->nowLocal();

        $isUpcoming = $start ? $now->lt($start) : false;
        $isExpired = $end ? $now->gte($end) : ($start ? $now->gt($start) : false);
        $isActive = ($start && $end) ? ($now->gte($start) && $now->lt($end)) : false;

        return [
            'start' => $start,
            'end' => $end,
            'is_upcoming' => $isUpcoming,
            'is_expired' => $isExpired,
            'is_active' => $isActive,
        ];
    }

    /**
     * Same question shape as web quiz result (/quiz/result/{quiz}).
     *
     * @return array<int, array<string, mixed>>
     */
    private function formatQuestionsWebShape(Collection $questions, bool $includeMarks): array
    {
        return $questions->sortBy('question_number')->values()->map(function (QuizQuestion $q) use ($includeMarks) {
            $marks1 = (int) ($q->marks1 ?? 0);
            $marks2 = (int) ($q->marks2 ?? 0);
            $marks3 = (int) ($q->marks3 ?? 0);

            return [
                'question_number' => (int) ($q->question_number ?? 0),
                'question' => $q->question,
                'answer1' => $q->answer1,
                'answer2' => $q->answer2,
                'answer3' => $q->answer3,
                'marks1' => $includeMarks ? $marks1 : null,
                'marks2' => $includeMarks ? $marks2 : null,
                'marks3' => $includeMarks ? $marks3 : null,
                'total_marks' => $includeMarks ? ($marks1 + $marks2 + $marks3) : null,
            ];
        })->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function formatQuizRowForParent(Quiz $quiz, ?QuizSubmission $submission, ?Student $student): array
    {
        $timing = $this->quizTiming($quiz);
        $startTime = $timing['start'];
        $endTime = $timing['end'];
        $hasSubmitted = $submission !== null;
        $isUpcoming = $hasSubmitted ? false : $timing['is_upcoming'];
        $isExpired = $hasSubmitted ? true : $timing['is_expired'];
        $isActive = $hasSubmitted ? false : $timing['is_active'];
        $uploadStatus = $hasSubmitted
            ? 'completed'
            : ($isUpcoming ? 'upcoming' : ($isExpired ? 'missed' : 'pending'));

        $questions = $quiz->relationLoaded('questions')
            ? $quiz->questions
            : $quiz->questions()->orderBy('question_number')->get();

        $showMarks = $isExpired || $hasSubmitted;
        $questionsWeb = $this->formatQuestionsWebShape($questions, $showMarks);
        $maximumMarks = $questions->sum(
            fn (QuizQuestion $q) => (int) ($q->marks1 ?? 0) + (int) ($q->marks2 ?? 0) + (int) ($q->marks3 ?? 0)
        );

        $quizData = [
            'id' => $quiz->id,
            'campus' => $quiz->campus,
            'quiz_name' => $quiz->quiz_name,
            'description' => $quiz->description,
            'for_class' => $quiz->for_class,
            'section' => $quiz->section,
            'total_questions' => (int) ($quiz->total_questions ?? $questions->count()),
            'start_date_time' => $startTime ? $startTime->format('Y-m-d H:i:s') : null,
            'start_date' => $startTime ? $startTime->format('Y-m-d') : null,
            'start_time' => $startTime ? $startTime->format('H:i:s') : null,
            'start_date_formatted' => $startTime ? $startTime->format('d M Y') : null,
            'start_time_formatted' => $startTime ? $startTime->format('h:i A') : null,
            'start_date_time_formatted' => $startTime ? $startTime->format('d M Y h:i A') : null,
            'end_date_time' => $endTime ? $endTime->format('Y-m-d H:i:s') : null,
            'end_date_time_formatted' => $endTime ? $endTime->format('d M Y h:i A') : null,
            'duration_minutes' => $quiz->duration_minutes ?? null,
            'timezone' => Quiz::schoolTimezone(),
            'is_upcoming' => $isUpcoming,
            'is_active' => $isActive,
            'is_expired' => $isExpired,
            'is_past' => $hasSubmitted ? true : ($startTime ? $quiz->nowLocal()->gte($startTime) : false),
            'has_submitted' => $hasSubmitted,
            'already_uploaded' => $hasSubmitted,
            'upload_status' => $uploadStatus,
            'status' => $uploadStatus,
            'can_upload' => !$hasSubmitted && $isActive,
            'submission' => $submission ? [
                'id' => $submission->id,
                'obtained_marks' => (int) ($submission->obtained_marks ?? 0),
                'total_marks' => (int) ($submission->total_marks ?? 0),
                'answers' => $submission->answers ?? [],
                'submitted_at' => $submission->submitted_at ? $submission->submitted_at->format('Y-m-d H:i:s') : null,
            ] : null,
            'questions' => $questionsWeb,
            'maximum_marks' => $showMarks ? $maximumMarks : null,
            'created_at' => $quiz->created_at ? $quiz->created_at->format('Y-m-d H:i:s') : null,
            'updated_at' => $quiz->updated_at ? $quiz->updated_at->format('Y-m-d H:i:s') : null,
        ];

        if ($showMarks) {
            $quizData['marks'] = collect($questionsWeb)->map(function (array $q) {
                return [
                    'question_number' => $q['question_number'],
                    'question' => $q['question'],
                    'answers' => [
                        ['answer' => $q['answer1'], 'marks' => (int) ($q['marks1'] ?? 0)],
                        ['answer' => $q['answer2'], 'marks' => (int) ($q['marks2'] ?? 0)],
                        ['answer' => $q['answer3'], 'marks' => (int) ($q['marks3'] ?? 0)],
                    ],
                ];
            })->values()->all();
        }

        return $quizData;
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
            $quiz = $this->visibleQuizQuery()->with('questions')->find($id);

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

            $studentForSubmission = $students->first();
            if ($request->filled('student_id')) {
                $studentForSubmission = $students->firstWhere('id', (int) $request->get('student_id')) ?? $studentForSubmission;
            }

            $submission = null;
            if ($studentForSubmission && Schema::hasTable('quiz_submissions')) {
                $submissionQuery = QuizSubmission::query()->where('quiz_id', $quiz->id);
                if (Schema::hasColumn('quiz_submissions', 'student_id')) {
                    $submissionQuery->where('student_id', $studentForSubmission->id);
                } elseif (Schema::hasColumn('quiz_submissions', 'student_code') && $studentForSubmission->student_code) {
                    $submissionQuery->where('student_code', $studentForSubmission->student_code);
                }
                $submission = $submissionQuery->latest('id')->first();
            }

            $quizData = $this->formatQuizRowForParent($quiz, $submission, $studentForSubmission);

            return response()->json([
                'success' => true,
                'message' => 'Quiz retrieved successfully.',
                'data' => [
                    'quiz' => $quizData,
                    'student' => $studentForSubmission ? [
                        'id' => $studentForSubmission->id,
                        'student_name' => $studentForSubmission->student_name,
                        'class' => $studentForSubmission->class,
                        'section' => $studentForSubmission->section,
                        'campus' => $studentForSubmission->campus,
                    ] : null,
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
            $quizzes = $query->with('questions')->orderBy('start_date_time', 'desc')->paginate(1000);

            $quizIds = $quizzes->pluck('id')->all();
            $submissionsByQuizId = collect();
            $firstStudent = $students->first();
            if ($firstStudent && Schema::hasTable('quiz_submissions') && !empty($quizIds)) {
                $submissionsQuery = QuizSubmission::query()->whereIn('quiz_id', $quizIds);
                if (Schema::hasColumn('quiz_submissions', 'student_id')) {
                    $submissionsQuery->where('student_id', $firstStudent->id);
                } elseif (Schema::hasColumn('quiz_submissions', 'student_code') && $firstStudent->student_code) {
                    $submissionsQuery->where('student_code', $firstStudent->student_code);
                }
                $submissionsByQuizId = $submissionsQuery->latest('id')->get()->unique('quiz_id')->keyBy('quiz_id');
            }

            // Format quiz data
            $quizzesData = $quizzes->map(function (Quiz $quiz) use ($submissionsByQuizId, $firstStudent) {
                $submission = $submissionsByQuizId->get($quiz->id);

                return $this->formatQuizRowForParent($quiz, $submission, $firstStudent ?: null);
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

