<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Carbon\Carbon;

class StudentQuizController extends Controller
{
    private function quizTiming(Quiz $quiz): array
    {
        $start = $quiz->start_date_time ? Carbon::parse($quiz->start_date_time) : null;
        $durationMinutes = (int) ($quiz->duration_minutes ?? 0);
        $end = ($start && $durationMinutes > 0) ? $start->copy()->addMinutes($durationMinutes) : null;
        $now = Carbon::now();

        $isUpcoming = $start ? $now->lt($start) : false;
        $isExpired = $end ? $now->gte($end) : ($start ? $now->gt($start) : false);
        $isActive = ($start && $end) ? ($now->gte($start) && $now->lt($end)) : false;

        $timeRemainingSeconds = ($isActive && $end) ? max(0, $now->diffInSeconds($end, false)) : 0;

        return [
            'start' => $start,
            'end' => $end,
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

            $quizzesData = $quizzes->map(function (Quiz $quiz) {
                $timing = $this->quizTiming($quiz);
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
                ];
            })->values();

            return response()->json([
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

            return response()->json([
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
                    ],
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

            return response()->json([
                'success' => true,
                'message' => 'Quiz questions retrieved successfully.',
                'data' => [
                    'quiz_id' => $quiz->id,
                    'time_remaining_seconds' => $timing['time_remaining_seconds'],
                    'questions' => $questionsData,
                ],
                'token' => $request->user()->currentAccessToken()->token ?? null,
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
}

