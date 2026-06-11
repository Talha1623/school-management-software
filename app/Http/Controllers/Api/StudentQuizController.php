<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\QuizSubmission;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class StudentQuizController extends Controller
{
    private function quizTimezone(): string
    {
        return 'Asia/Karachi';
    }

    private function quizDateTime(Quiz $quiz, string $column): ?Carbon
    {
        $value = $quiz->getRawOriginal($column) ?? $quiz->{$column};
        if (!$value) {
            return null;
        }

        return Carbon::createFromFormat('Y-m-d H:i:s', Carbon::parse($value)->format('Y-m-d H:i:s'), $this->quizTimezone());
    }

    private function quizTiming(Quiz $quiz): array
    {
        $start = $this->quizDateTime($quiz, 'start_date_time');
        $durationMinutes = (int) ($quiz->duration_minutes ?? 0);
        $end = ($start && $durationMinutes > 0) ? $start->copy()->addMinutes($durationMinutes) : null;
        $now = Carbon::now($this->quizTimezone());

        $isUpcoming = $start ? $now->lt($start) : false;
        $isExpired = $end ? $now->gte($end) : ($start ? $now->gt($start) : false);
        $isActive = ($start && $end) ? ($now->gte($start) && $now->lt($end)) : false;

        $timeRemainingSeconds = ($isActive && $end) ? max(0, $now->diffInSeconds($end, false)) : 0;

        return [
            'start' => $start,
            'end' => $end,
            'now' => $now,
            'is_upcoming' => $isUpcoming,
            'is_expired' => $isExpired,
            'is_active' => $isActive,
            'time_remaining_seconds' => $timeRemainingSeconds,
        ];
    }

    /**
     * Get quizzes for logged-in student
     *
     * GET /api/student/quizzes
     * Optional query params: per_page, start_date, end_date
     *
     * Note: student_id/class/section/campus are NOT accepted here.
     * Student can only see quizzes for their own class/section/campus (from token).
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            if (!$user || !($user instanceof Student)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid user type. Student authentication required.',
                    'token' => null,
                ], 403);
            }

            $student = $user;

            if (!$student->class || !$student->section) {
                return response()->json([
                    'success' => false,
                    'message' => 'Student information incomplete. Cannot fetch quizzes.',
                    'token' => null,
                ], 400);
            }

            $query = Quiz::query()
                ->whereRaw('LOWER(TRIM(for_class)) = ?', [strtolower(trim($student->class))])
                ->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($student->section))]);

            // Campus filter (optional on student record)
            if (!empty($student->campus)) {
                $query->where(function ($q) use ($student) {
                    $q->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($student->campus))])
                      ->orWhereNull('campus')
                      ->orWhere('campus', '');
                });
            }

            // Optional date filters
            if ($request->filled('start_date')) {
                $query->whereDate('start_date_time', '>=', $request->get('start_date'));
            }
            if ($request->filled('end_date')) {
                $query->whereDate('start_date_time', '<=', $request->get('end_date'));
            }

            $perPage = $request->get('per_page', 10);
            $perPage = in_array((int) $perPage, [10, 25, 50, 100], true) ? (int) $perPage : 10;

            $quizzes = $query->orderBy('start_date_time', 'desc')->paginate($perPage);

            $quizIds = $quizzes->pluck('id')->all();
            $submissionsByQuizId = $this->quizSubmissionQueryForStudent($student)
                ->whereIn('quiz_id', $quizIds)
                ->get()
                ->keyBy('quiz_id');
            $submissionsByQuizName = $this->quizSubmissionQueryForStudent($student)
                ->join('quizzes', 'quizzes.id', '=', 'quiz_submissions.quiz_id')
                ->select('quiz_submissions.*', 'quizzes.quiz_name as _quiz_name')
                ->get()
                ->mapWithKeys(function (QuizSubmission $submission) {
                    $name = $this->normalizedQuizName((string) ($submission->_quiz_name ?? ''));
                    return $name === '' ? [] : [$name => $submission];
                });

            $quizzesData = $quizzes->map(function (Quiz $quiz) use ($submissionsByQuizId, $submissionsByQuizName) {
                $timing = $this->quizTiming($quiz);
                $submission = $submissionsByQuizId->get($quiz->id);
                $quizNameKey = $this->normalizedQuizName((string) ($quiz->quiz_name ?? ''));
                if (!$submission && $quizNameKey !== '') {
                    $submission = $submissionsByQuizName->get($quizNameKey);
                }
                $hasSubmitted = $submission !== null;
                $submissionPayload = $submission ? [
                    'id' => $submission->id,
                    'obtained_marks' => (int) $submission->obtained_marks,
                    'total_marks' => (int) $submission->total_marks,
                    'submitted_at' => $submission->submitted_at ? $submission->submitted_at->format('Y-m-d H:i:s') : null,
                ] : null;

                return [
                    'id' => $quiz->id,
                    'campus' => $quiz->campus,
                    'quiz_name' => $quiz->quiz_name,
                    'description' => $quiz->description,
                    'for_class' => $quiz->for_class,
                    'section' => $quiz->section,
                    'total_questions' => $quiz->total_questions,
                    'duration_minutes' => $quiz->duration_minutes,
                    'start_date_time' => $quiz->start_date_time ? $quiz->start_date_time->format('Y-m-d H:i:s') : null,
                    'start_date_time_formatted' => $quiz->start_date_time ? $quiz->start_date_time->format('d M Y h:i A') : null,
                    'end_date_time' => $timing['end'] ? $timing['end']->format('Y-m-d H:i:s') : null,
                    'end_date_time_formatted' => $timing['end'] ? $timing['end']->format('d M Y h:i A') : null,
                    'is_upcoming' => $timing['is_upcoming'],
                    'is_expired' => $timing['is_expired'],
                    'is_active' => $timing['is_active'],
                    'time_remaining_seconds' => $timing['time_remaining_seconds'],
                    'has_submitted' => $hasSubmitted,
                    // Explicit status for clients: once uploaded, do not allow second upload.
                    'upload_status' => $hasSubmitted
                        ? 'completed'
                        : ($timing['is_upcoming'] ? 'upcoming' : ($timing['is_expired'] ? 'missed' : 'pending')),
                    'can_upload' => (!$hasSubmitted && $timing['is_active']),
                    'submission' => $submissionPayload,
                ];
            })->values();

            return $this->withPrivateNoStoreCache(response()->json([
                'success' => true,
                'message' => 'Quizzes retrieved successfully.',
                'data' => [
                    'student' => [
                        'id' => $student->id,
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
                'token' => $request->user()->currentAccessToken()->token ?? null,
            ], 200));
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving quizzes: ' . $e->getMessage(),
                'token' => null,
            ], 500);
        }
    }

    /**
     * Get a single quiz by ID (if student has access)
     *
     * GET /api/student/quizzes/{id}
     */
    public function show(Request $request, int $id): JsonResponse
    {
        try {
            $user = $request->user();

            if (!$user || !($user instanceof Student)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid user type. Student authentication required.',
                    'token' => null,
                ], 403);
            }

            $student = $user;

            if (!$student->class || !$student->section) {
                return response()->json([
                    'success' => false,
                    'message' => 'Student information incomplete. Cannot fetch quiz.',
                    'token' => null,
                ], 400);
            }

            $quiz = Quiz::find($id);
            if (!$quiz) {
                return response()->json([
                    'success' => false,
                    'message' => 'Quiz not found.',
                    'token' => null,
                ], 404);
            }

            $classMatch = strtolower(trim($quiz->for_class)) === strtolower(trim($student->class));
            $sectionMatch = strtolower(trim($quiz->section)) === strtolower(trim($student->section));
            $campusOk = empty($student->campus) || empty($quiz->campus) || strtolower(trim($quiz->campus)) === strtolower(trim($student->campus));

            if (!$classMatch || !$sectionMatch || !$campusOk) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have access to this quiz.',
                    'token' => null,
                ], 403);
            }

            $timing = $this->quizTiming($quiz);

            $submission = $this->quizSubmissionQueryForStudent($student)
                ->where('quiz_id', $quiz->id)
                ->first();
            if (!$submission) {
                $submission = $this->studentQuizSubmissionByQuizNameFor($student, $quiz);
            }
            $submissionPayload = $submission ? [
                'id' => $submission->id,
                'obtained_marks' => (int) $submission->obtained_marks,
                'total_marks' => (int) $submission->total_marks,
                'submitted_at' => $submission->submitted_at ? $submission->submitted_at->format('Y-m-d H:i:s') : null,
            ] : null;
            $hasSubmitted = $submission !== null;

            return $this->withPrivateNoStoreCache(response()->json([
                'success' => true,
                'message' => 'Quiz retrieved successfully.',
                'data' => [
                    'quiz' => [
                        'id' => $quiz->id,
                        'campus' => $quiz->campus,
                        'quiz_name' => $quiz->quiz_name,
                        'description' => $quiz->description,
                        'for_class' => $quiz->for_class,
                        'section' => $quiz->section,
                        'total_questions' => $quiz->total_questions,
                        'duration_minutes' => $quiz->duration_minutes,
                        'start_date_time' => $quiz->start_date_time ? $quiz->start_date_time->format('Y-m-d H:i:s') : null,
                        'start_date_time_formatted' => $quiz->start_date_time ? $quiz->start_date_time->format('d M Y h:i A') : null,
                        'end_date_time' => $timing['end'] ? $timing['end']->format('Y-m-d H:i:s') : null,
                        'end_date_time_formatted' => $timing['end'] ? $timing['end']->format('d M Y h:i A') : null,
                        'is_upcoming' => $timing['is_upcoming'],
                        'is_expired' => $timing['is_expired'],
                        'is_active' => $timing['is_active'],
                        'time_remaining_seconds' => $timing['time_remaining_seconds'],
                        'has_submitted' => $hasSubmitted,
                        'upload_status' => $hasSubmitted
                            ? 'completed'
                            : ($timing['is_upcoming'] ? 'upcoming' : ($timing['is_expired'] ? 'missed' : 'pending')),
                        'can_upload' => (!$hasSubmitted && $timing['is_active']),
                        'submission' => $submissionPayload,
                    ],
                ],
                'token' => $request->user()->currentAccessToken()->token ?? null,
            ], 200));
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving quiz: ' . $e->getMessage(),
                'token' => null,
            ], 500);
        }
    }

    /**
     * Same response shape as GET /api/parent/quizzes/{id}/questions — full quiz stub + keyed options.
     * No active-window restriction (parity with parent API).
     *
     * GET /api/student/quizzes/{id}/questions/full
     */
    public function questionsFull(Request $request, int $id): JsonResponse
    {
        try {
            $quizId = (int) $id;
            $user = $request->user();

            if (!$user || !($user instanceof Student)) {
                return $this->withPrivateNoStoreCache(response()->json([
                    'success' => false,
                    'message' => 'Invalid user type. Student authentication required.',
                    'token' => null,
                ], 403));
            }

            $student = $user;

            if (!$student->class || !$student->section) {
                return $this->withPrivateNoStoreCache(response()->json([
                    'success' => false,
                    'message' => 'Student information incomplete. Cannot fetch quiz questions.',
                    'token' => null,
                ], 400));
            }

            $quiz = Quiz::with('questions')->find($quizId);
            if (!$quiz) {
                return $this->withPrivateNoStoreCache(response()->json([
                    'success' => false,
                    'message' => 'Quiz not found.',
                    'token' => null,
                ], 404));
            }

            $classMatch = strtolower(trim((string) $quiz->for_class)) === strtolower(trim((string) $student->class));
            $sectionMatch = strtolower(trim((string) $quiz->section)) === strtolower(trim((string) $student->section));
            $campusOk = !$student->campus
                || !$quiz->campus
                || strtolower(trim((string) $quiz->campus)) === strtolower(trim((string) $student->campus));

            if (!$classMatch || !$sectionMatch || !$campusOk) {
                return $this->withPrivateNoStoreCache(response()->json([
                    'success' => false,
                    'message' => 'You do not have access to this quiz.',
                    'token' => null,
                ], 403));
            }

            $submissionRow = $this->existingSubmissionForStudentQuiz($student, $quiz);
            if ($submissionRow !== null) {
                return $this->withPrivateNoStoreCache($this->duplicateQuizSubmissionResponse(
                    $request,
                    $submissionRow,
                    'Quiz already uploaded.',
                    'Quiz already uploaded. Use GET /api/student/quizzes/{id}/result to view your submission.'
                ));
            }

            $questions = $this->mapQuizQuestionsParentApiShape(
                $quiz->questions->sortBy('question_number')->values()
            );

            return $this->withPrivateNoStoreCache(response()->json([
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
                'token' => $request->user()->currentAccessToken()->token ?? null,
            ], 200));
        } catch (\Exception $e) {
            return $this->withPrivateNoStoreCache(response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving quiz questions: ' . $e->getMessage(),
                'token' => null,
            ], 500));
        }
    }

    /**
     * Question list fields aligned with ParentQuizController::questions (for shared clients).
     *
     * @param  Collection<int, QuizQuestion>  $sortedQuestions
     * @return Collection<int, array<string, mixed>>
     */
    private function mapQuizQuestionsParentApiShape(Collection $sortedQuestions): Collection
    {
        return $sortedQuestions->map(function (QuizQuestion $q) {
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
                'answer1' => $a1 !== '' ? $a1 : null,
                'answer2' => $a2 !== '' ? $a2 : null,
                'answer3' => $a3 !== '' ? $a3 : null,
                'options' => $optionsKeyed,
                'options_text' => $optionsTextOnly,
            ];
        })->values();
    }

    /**
     * Get quiz questions for logged-in student (only during active time window)
     *
     * GET /api/student/quizzes/{id}/questions
     */
    public function questions(Request $request, int $id): JsonResponse
    {
        try {
            $user = $request->user();

            if (!$user || !($user instanceof Student)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid user type. Student authentication required.',
                    'token' => null,
                ], 403);
            }

            $student = $user;
            $quizId = (int) $id;

            $quiz = Quiz::find($quizId);
            if (!$quiz) {
                return response()->json([
                    'success' => false,
                    'message' => 'Quiz not found.',
                    'token' => null,
                ], 404);
            }

            $classMatch = strtolower(trim($quiz->for_class)) === strtolower(trim($student->class ?? ''));
            $sectionMatch = strtolower(trim($quiz->section)) === strtolower(trim($student->section ?? ''));
            $campusOk = empty($student->campus) || empty($quiz->campus) || strtolower(trim($quiz->campus)) === strtolower(trim($student->campus));

            if (!$classMatch || !$sectionMatch || !$campusOk) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have access to this quiz.',
                    'token' => null,
                ], 403);
            }

            $submissionRowTimed = $this->existingSubmissionForStudentQuiz($student, $quiz);
            if ($submissionRowTimed !== null) {
                return $this->withPrivateNoStoreCache($this->duplicateQuizSubmissionResponse(
                    $request,
                    $submissionRowTimed,
                    'Quiz already uploaded.',
                    'Quiz already uploaded. Use GET /api/student/quizzes/{id}/result for your result.'
                ));
            }

            $timing = $this->quizTiming($quiz);

            if ($timing['is_upcoming']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Quiz has not started yet.',
                    'data' => [
                        'start_date_time' => $timing['start'] ? $timing['start']->format('Y-m-d H:i:s') : null,
                        'start_date_time_formatted' => $timing['start'] ? $timing['start']->format('d M Y h:i A') : null,
                    ],
                    'token' => $request->user()->currentAccessToken()->token ?? null,
                ], 200);
            }

            if ($timing['is_expired']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Quiz time is over (expired).',
                    'data' => [
                        'end_date_time' => $timing['end'] ? $timing['end']->format('Y-m-d H:i:s') : null,
                        'end_date_time_formatted' => $timing['end'] ? $timing['end']->format('d M Y h:i A') : null,
                    ],
                    'token' => $request->user()->currentAccessToken()->token ?? null,
                ], 200);
            }

            if (!$timing['is_active']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Quiz is not active.',
                    'token' => $request->user()->currentAccessToken()->token ?? null,
                ], 200);
            }

            // Return questions (no marks)
            $questions = $quiz->questions()->orderBy('question_number')->get();

            $questionsData = $questions->map(function (QuizQuestion $q) {
                return [
                    'question_number' => $q->question_number,
                    'question' => $q->question,
                    'options' => array_values(array_filter([
                        $q->answer1,
                        $q->answer2,
                        $q->answer3,
                    ], fn ($v) => $v !== null && trim((string) $v) !== '')),
                ];
            })->values();

            return $this->withPrivateNoStoreCache(response()->json([
                'success' => true,
                'message' => 'Quiz questions retrieved successfully.',
                'data' => [
                    'quiz_id' => $quiz->id,
                    'time_remaining_seconds' => $timing['time_remaining_seconds'],
                    'questions' => $questionsData,
                ],
                'token' => $request->user()->currentAccessToken()->token ?? null,
            ], 200));
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving quiz questions: ' . $e->getMessage(),
                'token' => null,
            ], 500);
        }
    }

    /**
     * Get quiz marks schema (answers + marks) for logged-in student
     * Only available AFTER quiz is expired.
     *
     * GET /api/student/quizzes/{id}/marks
     */
    public function marks(Request $request, int $id): JsonResponse
    {
        try {
            $user = $request->user();

            if (!$user || !($user instanceof Student)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid user type. Student authentication required.',
                    'token' => null,
                ], 403);
            }

            $student = $user;

            $quiz = Quiz::find($id);
            if (!$quiz) {
                return response()->json([
                    'success' => false,
                    'message' => 'Quiz not found.',
                    'token' => null,
                ], 404);
            }

            $classMatch = strtolower(trim($quiz->for_class)) === strtolower(trim($student->class ?? ''));
            $sectionMatch = strtolower(trim($quiz->section)) === strtolower(trim($student->section ?? ''));
            $campusOk = empty($student->campus) || empty($quiz->campus) || strtolower(trim($quiz->campus)) === strtolower(trim($student->campus));

            if (!$classMatch || !$sectionMatch || !$campusOk) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have access to this quiz.',
                    'token' => null,
                ], 403);
            }

            $timing = $this->quizTiming($quiz);

            // Only allow marks schema after quiz is expired
            if (!$timing['is_expired']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Marks will be available after the quiz is expired.',
                    'data' => [
                        'is_upcoming' => $timing['is_upcoming'],
                        'is_active' => $timing['is_active'],
                        'is_expired' => $timing['is_expired'],
                        'end_date_time' => $timing['end'] ? $timing['end']->format('Y-m-d H:i:s') : null,
                        'end_date_time_formatted' => $timing['end'] ? $timing['end']->format('d M Y h:i A') : null,
                    ],
                    'token' => $request->user()->currentAccessToken()->token ?? null,
                ], 200);
            }

            $questions = $quiz->questions()->orderBy('question_number')->get();

            $marksData = $questions->map(function (QuizQuestion $q) {
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

            return response()->json([
                'success' => true,
                'message' => 'Quiz marks retrieved successfully.',
                'data' => [
                    'quiz_id' => $quiz->id,
                    'marks' => $marksData,
                ],
                'token' => $request->user()->currentAccessToken()->token ?? null,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving quiz marks: ' . $e->getMessage(),
                'token' => null,
            ], 500);
        }
    }

    /**
     * Submit quiz answers (student attempt).
     *
     * POST /api/student/quizzes/{id}/submit
     * Body:
     * {
     *   "answers": [
     *     {"question_number": 1, "selected_option": 2},
     *     {"question_number": 2, "selected_option": 1}
     *   ]
     * }
     */
    public function submit(Request $request, int $id): JsonResponse
    {
        try {
            $gate = $this->requireStudentQuizForSubmitWindow($request, $id);
            if ($gate instanceof JsonResponse) {
                return $gate;
            }
            [$student, $quiz] = $gate;

            $validated = $request->validate([
                'answers' => ['required', 'array', 'min:1'],
                'answers.*.question_number' => ['required', 'integer', 'min:1', 'distinct'],
                'answers.*.selected_option' => ['required', 'integer', 'min:1', 'max:3'],
            ]);

            $normalized = [];
            foreach ($validated['answers'] as $a) {
                $normalized[] = [
                    'question_number' => (int) $a['question_number'],
                    'selected_option' => (int) $a['selected_option'],
                ];
            }

            return $this->performQuizSubmission($request, $student, $quiz, $normalized);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
                'token' => $request->user()?->currentAccessToken()?->token ?? null,
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while submitting quiz: ' . $e->getMessage(),
                'token' => $request->user()?->currentAccessToken()?->token ?? null,
            ], 500);
        }
    }

    /**
     * Same scoring + same success payload as POST /submit, accepts parent-style answers from GET .../questions/full.
     *
     * POST /api/student/quizzes/{id}/submit/full
     * Body:
     * {
     *   "answers": [
     *     {"question_id": 1, "selected_option": "option_1"},
     *     {"question_id": 2, "selected_option": "option_2"}
     *   ]
     * }
     */
    public function submitFull(Request $request, int $id): JsonResponse
    {
        try {
            $gate = $this->requireStudentQuizForSubmitWindow($request, $id);
            if ($gate instanceof JsonResponse) {
                return $gate;
            }
            [$student, $quiz] = $gate;

            $validated = $request->validate([
                'answers' => ['required', 'array', 'min:1'],
                'answers.*.question_id' => ['required', 'integer', 'distinct', 'exists:quiz_questions,id'],
                'answers.*.selected_option' => ['required', 'string', Rule::in(['option_1', 'option_2', 'option_3'])],
            ]);

            $byId = QuizQuestion::where('quiz_id', $quiz->id)
                ->whereIn('id', array_column($validated['answers'], 'question_id'))
                ->get()
                ->keyBy('id');

            $normalized = [];
            foreach ($validated['answers'] as $a) {
                $qid = (int) $a['question_id'];
                $q = $byId->get($qid);
                if (!$q || (int) $q->quiz_id !== (int) $quiz->id) {
                    return response()->json([
                        'success' => false,
                        'message' => 'One or more questions do not belong to this quiz.',
                        'errors' => ['answers' => ['Invalid question_id for this quiz.']],
                        'token' => $request->user()?->currentAccessToken()?->token ?? null,
                    ], 422);
                }
                $opt = $this->selectedOptionLabelToIndex((string) $a['selected_option']);
                $normalized[] = [
                    'question_number' => (int) $q->question_number,
                    'selected_option' => $opt,
                ];
            }

            return $this->performQuizSubmission($request, $student, $quiz, $normalized);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
                'token' => $request->user()?->currentAccessToken()?->token ?? null,
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while submitting quiz: ' . $e->getMessage(),
                'token' => $request->user()?->currentAccessToken()?->token ?? null,
            ], 500);
        }
    }

    /**
     * Shared gate: auth, quiz exists, access, timing (same rules as legacy submit).
     *
     * @return JsonResponse|array{Student, Quiz}
     */
    private function requireStudentQuizForSubmitWindow(Request $request, int $id): JsonResponse|array
    {
        $user = $request->user();

        if (!$user || !($user instanceof Student)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid user type. Student authentication required.',
                'token' => null,
            ], 403);
        }

        $student = $user;

        $quiz = Quiz::find($id);
        if (!$quiz) {
            return response()->json([
                'success' => false,
                'message' => 'Quiz not found.',
                'token' => null,
            ], 404);
        }

        $classMatch = strtolower(trim((string) $quiz->for_class)) === strtolower(trim((string) ($student->class ?? '')));
        $sectionMatch = strtolower(trim((string) $quiz->section)) === strtolower(trim((string) ($student->section ?? '')));
        $campusOk = empty($student->campus) || empty($quiz->campus)
            || strtolower(trim((string) $quiz->campus)) === strtolower(trim((string) $student->campus));

        if (!$classMatch || !$sectionMatch || !$campusOk) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have access to this quiz.',
                'token' => null,
            ], 403);
        }

        $alreadySubmitted = $this->existingSubmissionForStudentQuiz($student, $quiz);
        if ($alreadySubmitted !== null) {
            return $this->duplicateQuizSubmissionResponse(
                $request,
                $alreadySubmitted,
                'Question already uploaded.',
                'Question already uploaded. You cannot upload again.'
            );
        }

        $timing = $this->quizTiming($quiz);

        if ($timing['start']) {
            $now = $timing['now'];
            $start = $timing['start'];

            if ($now->lt($start)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Quiz has not started yet.',
                    'data' => [
                        'start_date_time' => $start->format('Y-m-d H:i:s'),
                        'start_date_time_formatted' => $start->format('d M Y h:i A'),
                        'current_time' => $now->format('Y-m-d H:i:s'),
                    ],
                    'token' => $request->user()->currentAccessToken()->token ?? null,
                ], 200);
            }

            if ($timing['end'] && $now->gte($timing['end'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Quiz time is over (expired). Submission not allowed.',
                    'data' => [
                        'end_date_time' => $timing['end']->format('Y-m-d H:i:s'),
                        'end_date_time_formatted' => $timing['end']->format('d M Y h:i A'),
                        'current_time' => $now->format('Y-m-d H:i:s'),
                    ],
                    'token' => $request->user()->currentAccessToken()->token ?? null,
                ], 200);
            }
        }

        return [$student, $quiz];
    }

    private function selectedOptionLabelToIndex(string $label): int
    {
        return match ($label) {
            'option_1' => 1,
            'option_2' => 2,
            'option_3' => 3,
            default => 0,
        };
    }

    /**
     * Persist submission — one row per student+quiz enforced (transaction + DB unique index).
     *
     * @param  array<int, array{question_number: int, selected_option: int}>  $normalizedRows
     */
    private function performQuizSubmission(Request $request, Student $student, Quiz $quiz, array $normalizedRows): JsonResponse
    {
        try {
            return DB::transaction(function () use ($request, $student, $quiz, $normalizedRows) {
                Student::query()->whereKey($student->id)->lockForUpdate()->first();

                $existing = $this->existingSubmissionForStudentQuiz($student, $quiz);
                if ($existing) {
                    return $this->duplicateQuizSubmissionResponse(
                        $request,
                        $existing,
                        'Question already uploaded.',
                        'Question already uploaded. You cannot upload again.'
                    );
                }

                $questions = $quiz->questions()->get()->keyBy('question_number');

                $total = 0;
                foreach ($questions as $q) {
                    $total += max((int) ($q->marks1 ?? 0), (int) ($q->marks2 ?? 0), (int) ($q->marks3 ?? 0));
                }

                $obtained = 0;
                $storedAnswers = [];
                foreach ($normalizedRows as $a) {
                    $qn = (int) $a['question_number'];
                    $opt = (int) $a['selected_option'];
                    $q = $questions->get($qn);
                    if (!$q || $opt < 1 || $opt > 3) {
                        continue;
                    }

                    $selectedAnswer = null;
                    $marksAwarded = 0;
                    if ($opt === 1) {
                        $selectedAnswer = $q->answer1;
                        $marksAwarded = (int) ($q->marks1 ?? 0);
                    } elseif ($opt === 2) {
                        $selectedAnswer = $q->answer2;
                        $marksAwarded = (int) ($q->marks2 ?? 0);
                    } elseif ($opt === 3) {
                        $selectedAnswer = $q->answer3;
                        $marksAwarded = (int) ($q->marks3 ?? 0);
                    }

                    $obtained += $marksAwarded;

                    $storedAnswers[] = [
                        'question_id' => $q->id,
                        'question_number' => $qn,
                        'selected_option' => $opt,
                        'selected_answer' => $selectedAnswer,
                        'marks_awarded' => $marksAwarded,
                    ];
                }

                $submission = QuizSubmission::create([
                    'quiz_id' => $quiz->id,
                    'student_id' => $student->id,
                    'answers' => $storedAnswers,
                    'obtained_marks' => $obtained,
                    'total_marks' => $total,
                    'submitted_at' => now(),
                ]);

                if ($this->quizSubmissionsHaveColumn('student_code') && !empty($student->student_code)) {
                    QuizSubmission::query()
                        ->whereKey($submission->id)
                        ->update(['student_code' => (string) $student->student_code]);
                    $submission->student_code = (string) $student->student_code;
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Quiz submitted successfully.',
                    'data' => [
                        'submission_id' => $submission->id,
                        'quiz_id' => $quiz->id,
                        'obtained_marks' => $obtained,
                        'total_marks' => $total,
                        'has_submitted' => true,
                        'upload_status' => 'completed',
                        'can_upload' => false,
                        'submitted_at' => $submission->submitted_at ? $submission->submitted_at->format('Y-m-d H:i:s') : null,
                    ],
                    'token' => $request->user()->currentAccessToken()->token ?? null,
                ], 200);
            });
        } catch (QueryException $e) {
            if (!$this->isUniqueConstraintViolation($e)) {
                throw $e;
            }

            $existing = $this->quizSubmissionQueryForStudent($student)
                ->where('quiz_id', $quiz->id)
                ->first();

            return $existing
                ? $this->duplicateQuizSubmissionResponse(
                    $request,
                    $existing,
                    'Question already uploaded.',
                    'Question already uploaded. You cannot upload again.'
                )
                : response()->json([
                    'success' => false,
                    'message' => 'This quiz submission could not be saved (duplicate conflict). Please refresh and try again.',
                    'token' => $request->user()?->currentAccessToken()?->token ?? null,
                ], 409);
        }
    }

    private function duplicateQuizSubmissionResponse(
        Request $request,
        QuizSubmission $existing,
        string $message,
        string $errorDetail,
    ): JsonResponse {
        return response()->json([
            'success' => false,
            'message' => $message,
            'status' => 'already_uploaded',
            'legacy_status' => 'already_submitted',
            'errors' => [
                'quiz' => [$errorDetail],
            ],
            'data' => [
                'submission_id' => $existing->id,
                'obtained_marks' => (int) $existing->obtained_marks,
                'total_marks' => (int) $existing->total_marks,
                'has_submitted' => true,
                'upload_status' => 'completed',
                'can_upload' => false,
                'submitted_at' => $existing->submitted_at ? $existing->submitted_at->format('Y-m-d H:i:s') : null,
            ],
            'token' => $request->user()->currentAccessToken()->token ?? null,
        ], 409);
    }

    private function isUniqueConstraintViolation(QueryException $e): bool
    {
        $msg = strtolower($e->getMessage());

        return str_contains($msg, 'duplicate entry')
            || str_contains($msg, 'duplicate key')
            || str_contains($msg, 'unique constraint failed')
            || str_contains($msg, 'unique violation');
    }

    /**
     * Per-student quiz GETs must not be served from shared/CDN cache (otherwise "already submitted"
     * logic is bypassed and stale question JSON reappears).
     */
    private function withPrivateNoStoreCache(JsonResponse $response): JsonResponse
    {
        return $response->withHeaders([
            'Cache-Control' => 'private, no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'Expires' => 'Sat, 01 Jan 2000 00:00:00 GMT',
            'Vary' => 'Authorization',
        ]);
    }

    private function quizSubmissionsHaveColumn(string $column): bool
    {
        static $columns = [];

        if (!array_key_exists($column, $columns)) {
            $columns[$column] = Schema::hasColumn('quiz_submissions', $column);
        }

        return $columns[$column];
    }

    private function quizSubmissionQueryForStudent(Student $student)
    {
        $hasStudentId = $this->quizSubmissionsHaveColumn('student_id');
        $hasStudentCode = $this->quizSubmissionsHaveColumn('student_code') && !empty($student->student_code);

        return QuizSubmission::query()->where(function ($query) use ($student, $hasStudentId, $hasStudentCode) {
            if ($hasStudentId) {
                $query->where('quiz_submissions.student_id', (int) $student->getKey());
            }

            if ($hasStudentCode) {
                $method = $hasStudentId ? 'orWhere' : 'where';
                $query->{$method}('quiz_submissions.student_code', (string) $student->student_code);
            }

            if (!$hasStudentId && !$hasStudentCode) {
                $query->whereRaw('1 = 0');
            }
        });
    }

    private function studentQuizSubmissionFor(Student $student, int $quizId): ?QuizSubmission
    {
        return $this->quizSubmissionQueryForStudent($student)
            ->where('quiz_id', $quizId)
            ->first();
    }

    /**
     * One-attempt guard by quiz name (covers accidental duplicate quiz IDs with same name).
     */
    private function studentQuizSubmissionByQuizNameFor(Student $student, Quiz $quiz): ?QuizSubmission
    {
        $quizName = $this->normalizedQuizName((string) ($quiz->quiz_name ?? ''));
        if ($quizName === '') {
            return null;
        }
        $rows = $this->quizSubmissionQueryForStudent($student)
            ->join('quizzes', 'quizzes.id', '=', 'quiz_submissions.quiz_id')
            ->where('quiz_submissions.quiz_id', '!=', (int) $quiz->id)
            ->orderByDesc('quiz_submissions.id')
            ->select('quiz_submissions.*', 'quizzes.quiz_name as _quiz_name')
            ->get();

        foreach ($rows as $row) {
            if ($this->normalizedQuizName((string) ($row->_quiz_name ?? '')) === $quizName) {
                $submission = QuizSubmission::find($row->id);
                if ($submission) {
                    return $submission;
                }
            }
        }

        return null;
    }

    private function normalizedQuizName(string $name): string
    {
        $name = strtolower(trim($name));
        $name = preg_replace('/\s+/', ' ', $name) ?? $name;
        return trim($name);
    }

    /**
     * Single source of truth: a student can submit once per quiz id OR same normalized quiz name.
     */
    private function existingSubmissionForStudentQuiz(Student $student, Quiz $quiz): ?QuizSubmission
    {
        $byId = $this->studentQuizSubmissionFor($student, (int) $quiz->id);
        if ($byId) {
            return $byId;
        }

        return $this->studentQuizSubmissionByQuizNameFor($student, $quiz);
    }

    /**
     * Get submitted result for a quiz (if submitted).
     *
     * GET /api/student/quizzes/{id}/result
     */
    public function result(Request $request, int $id): JsonResponse
    {
        try {
            $user = $request->user();

            if (!$user || !($user instanceof Student)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid user type. Student authentication required.',
                    'token' => null,
                ], 403);
            }

            $student = $user;

            $quiz = Quiz::find($id);
            $submission = $quiz
                ? $this->existingSubmissionForStudentQuiz($student, $quiz)
                : $this->studentQuizSubmissionFor($student, $id);

            if (!$submission) {
                return response()->json([
                    'success' => false,
                    'message' => 'No submission found for this quiz.',
                    'token' => $request->user()->currentAccessToken()->token ?? null,
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Quiz result retrieved successfully.',
                'data' => [
                    'submission_id' => $submission->id,
                    'quiz_id' => (int) $submission->quiz_id,
                    'obtained_marks' => (int) $submission->obtained_marks,
                    'total_marks' => (int) $submission->total_marks,
                    'answers' => $submission->answers ?? [],
                    'submitted_at' => $submission->submitted_at ? $submission->submitted_at->format('Y-m-d H:i:s') : null,
                ],
                'token' => $request->user()->currentAccessToken()->token ?? null,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving quiz result: ' . $e->getMessage(),
                'token' => $request->user()?->currentAccessToken()?->token ?? null,
            ], 500);
        }
    }
}

