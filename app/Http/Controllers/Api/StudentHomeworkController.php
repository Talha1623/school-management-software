<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HomeworkDiary;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class StudentHomeworkController extends Controller
{
    /**
     * Get Homework List for Logged-in Student
     * Returns homework for student's class, section, campus
     * 
     * GET /api/student/homework/list
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function list(Request $request): JsonResponse
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

            // Check if student has complete information
            if (!$student->campus || !$student->class || !$student->section) {
                return response()->json([
                    'success' => false,
                    'message' => 'Student information incomplete. Cannot fetch homework.',
                    'token' => null,
                ], 400);
            }

            // Get homework for this student's class, section, campus
            // Only show homework where homework_content is not empty
            $query = HomeworkDiary::whereNotNull('homework_content')
                ->where('homework_content', '!=', '')
                ->whereRaw('TRIM(homework_content) != ?', [''])
                ->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($student->campus))])
                ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($student->class))])
                ->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($student->section))]);

            // Filter by date (optional - specific date)
            if ($request->filled('date')) {
                $query->whereDate('date', $request->date);
            }

            // Filter by date range (optional)
            if ($request->filled('start_date')) {
                $query->whereDate('date', '>=', $request->start_date);
            }

            if ($request->filled('end_date')) {
                $query->whereDate('date', '<=', $request->end_date);
            }

            // Filter by subject_id (optional)
            if ($request->filled('subject_id')) {
                $query->where('subject_id', $request->subject_id);
            }

            // Filter by subject name (optional)
            if ($request->filled('subject')) {
                $subjectIds = Subject::whereRaw('LOWER(TRIM(subject_name)) LIKE ?', ['%' . strtolower(trim($request->subject)) . '%'])
                    ->pluck('id')
                    ->toArray();
                if (!empty($subjectIds)) {
                    $query->whereIn('subject_id', $subjectIds);
                } else {
                    // If no subject found, return empty result
                    $query->whereRaw('1 = 0');
                }
            }

            // Pagination
            $perPage = $request->get('per_page', 30);
            $perPage = in_array($perPage, [10, 25, 30, 50, 100]) ? $perPage : 30;

            $homeworkList = $query->with('subject')
                ->orderBy('date', 'desc')
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);

            // Format homework data
            $homeworkData = $homeworkList->map(function($homework) {
                return [
                    'id' => $homework->id,
                    'subject_id' => $homework->subject_id,
                    'subject_name' => $homework->subject->subject_name ?? null,
                    'campus' => $homework->campus,
                    'class' => $homework->class,
                    'section' => $homework->section,
                    'date' => $homework->date->format('Y-m-d'),
                    'date_formatted' => $homework->date->format('d M Y'),
                    'date_formatted_full' => $homework->date->format('l, d F Y'),
                    'day_name' => $homework->date->format('l'),
                    'day_short' => $homework->date->format('D'),
                    'homework_content' => $homework->homework_content,
                    'created_at' => $homework->created_at->format('Y-m-d H:i:s'),
                    'created_at_formatted' => $homework->created_at->format('d M Y, h:i A'),
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Homework list retrieved successfully',
                'data' => [
                    'student' => [
                        'id' => $student->id,
                        'name' => $student->student_name,
                        'student_code' => $student->student_code,
                        'class' => $student->class,
                        'section' => $student->section,
                        'campus' => $student->campus,
                    ],
                    'homework' => $homeworkData,
                    'pagination' => [
                        'current_page' => $homeworkList->currentPage(),
                        'last_page' => $homeworkList->lastPage(),
                        'per_page' => $homeworkList->perPage(),
                        'total' => $homeworkList->total(),
                        'from' => $homeworkList->firstItem(),
                        'to' => $homeworkList->lastItem(),
                    ],
                ],
                'token' => $request->user()->currentAccessToken()->token ?? null,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving homework: ' . $e->getMessage(),
                'token' => null,
            ], 500);
        }
    }

    /**
     * Get Today's Homework for Logged-in Student
     * 
     * GET /api/student/homework/today
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function today(Request $request): JsonResponse
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

            // Check if student has complete information
            if (!$student->campus || !$student->class || !$student->section) {
                return response()->json([
                    'success' => false,
                    'message' => 'Student information incomplete. Cannot fetch homework.',
                    'token' => null,
                ], 400);
            }

            $today = now()->format('Y-m-d');

            // Get today's homework
            $homeworkList = HomeworkDiary::whereNotNull('homework_content')
                ->where('homework_content', '!=', '')
                ->whereRaw('TRIM(homework_content) != ?', [''])
                ->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($student->campus))])
                ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($student->class))])
                ->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($student->section))])
                ->whereDate('date', $today)
                ->with('subject')
                ->orderBy('subject_id', 'asc')
                ->get();

            // Format homework data
            $homeworkData = $homeworkList->map(function($homework) {
                return [
                    'id' => $homework->id,
                    'subject_id' => $homework->subject_id,
                    'subject_name' => $homework->subject->subject_name ?? null,
                    'date' => $homework->date->format('Y-m-d'),
                    'date_formatted' => $homework->date->format('d M Y'),
                    'date_formatted_full' => $homework->date->format('l, d F Y'),
                    'homework_content' => $homework->homework_content,
                    'created_at' => $homework->created_at->format('Y-m-d H:i:s'),
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Today\'s homework retrieved successfully',
                'data' => [
                    'student' => [
                        'id' => $student->id,
                        'name' => $student->student_name,
                        'student_code' => $student->student_code,
                        'class' => $student->class,
                        'section' => $student->section,
                    ],
                    'date' => $today,
                    'date_formatted' => now()->format('d M Y'),
                    'total_homework' => $homeworkList->count(),
                    'homework' => $homeworkData,
                ],
                'token' => $request->user()->currentAccessToken()->token ?? null,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving today\'s homework: ' . $e->getMessage(),
                'token' => null,
            ], 500);
        }
    }

    /**
     * Get Homework by Date
     * 
     * GET /api/student/homework/date/{date}
     * 
     * @param Request $request
     * @param string $date (YYYY-MM-DD format)
     * @return JsonResponse
     */
    public function getByDate(Request $request, string $date): JsonResponse
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

            // Check if student has complete information
            if (!$student->campus || !$student->class || !$student->section) {
                return response()->json([
                    'success' => false,
                    'message' => 'Student information incomplete. Cannot fetch homework.',
                    'token' => null,
                ], 400);
            }

            // Validate date format
            try {
                $dateObj = \Carbon\Carbon::parse($date);
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid date format. Please use YYYY-MM-DD format.',
                    'token' => null,
                ], 422);
            }

            // Get homework for specific date
            $homeworkList = HomeworkDiary::whereNotNull('homework_content')
                ->where('homework_content', '!=', '')
                ->whereRaw('TRIM(homework_content) != ?', [''])
                ->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($student->campus))])
                ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($student->class))])
                ->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($student->section))])
                ->whereDate('date', $date)
                ->with('subject')
                ->orderBy('subject_id', 'asc')
                ->get();

            // Format homework data
            $homeworkData = $homeworkList->map(function($homework) {
                return [
                    'id' => $homework->id,
                    'subject_id' => $homework->subject_id,
                    'subject_name' => $homework->subject->subject_name ?? null,
                    'date' => $homework->date->format('Y-m-d'),
                    'date_formatted' => $homework->date->format('d M Y'),
                    'date_formatted_full' => $homework->date->format('l, d F Y'),
                    'day_name' => $homework->date->format('l'),
                    'homework_content' => $homework->homework_content,
                    'created_at' => $homework->created_at->format('Y-m-d H:i:s'),
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Homework retrieved successfully',
                'data' => [
                    'student' => [
                        'id' => $student->id,
                        'name' => $student->student_name,
                        'student_code' => $student->student_code,
                        'class' => $student->class,
                        'section' => $student->section,
                    ],
                    'date' => $date,
                    'date_formatted' => $dateObj->format('d M Y'),
                    'date_formatted_full' => $dateObj->format('l, d F Y'),
                    'total_homework' => $homeworkList->count(),
                    'homework' => $homeworkData,
                ],
                'token' => $request->user()->currentAccessToken()->token ?? null,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving homework: ' . $e->getMessage(),
                'token' => null,
            ], 500);
        }
    }

    /**
     * Get Subjects List for Student's Class
     * Returns all subjects that have homework for this student
     * 
     * GET /api/student/homework/subjects
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getSubjects(Request $request): JsonResponse
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

            // Check if student has complete information
            if (!$student->campus || !$student->class || !$student->section) {
                return response()->json([
                    'success' => false,
                    'message' => 'Student information incomplete. Cannot fetch subjects.',
                    'token' => null,
                ], 400);
            }

            // Get subject IDs that have homework for this student
            $subjectIds = HomeworkDiary::whereNotNull('homework_content')
                ->where('homework_content', '!=', '')
                ->whereRaw('TRIM(homework_content) != ?', [''])
                ->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($student->campus))])
                ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($student->class))])
                ->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($student->section))])
                ->distinct()
                ->pluck('subject_id')
                ->toArray();

            // Get subjects
            $subjects = Subject::whereIn('id', $subjectIds)
                ->orderBy('subject_name', 'asc')
                ->get()
                ->map(function($subject) {
                    return [
                        'id' => $subject->id,
                        'subject_name' => $subject->subject_name,
                        'class' => $subject->class,
                        'section' => $subject->section,
                    ];
                });

            return response()->json([
                'success' => true,
                'message' => 'Subjects retrieved successfully',
                'data' => [
                    'student' => [
                        'id' => $student->id,
                        'name' => $student->student_name,
                        'class' => $student->class,
                        'section' => $student->section,
                    ],
                    'subjects' => $subjects,
                    'total_subjects' => $subjects->count(),
                ],
                'token' => $request->user()->currentAccessToken()->token ?? null,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving subjects: ' . $e->getMessage(),
                'token' => null,
            ], 500);
        }
    }

    /**
     * Get Previous Date Homework Diary by Subject and Date
     * Returns homework for a specific subject and date
     * 
     * GET /api/student/homework/previous?subject={subject_name}&date={date}
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function previousDate(Request $request): JsonResponse
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

            // Check if student has complete information
            if (!$student->campus || !$student->class || !$student->section) {
                return response()->json([
                    'success' => false,
                    'message' => 'Student information incomplete. Cannot fetch homework.',
                    'token' => null,
                ], 400);
            }

            // Validate required parameters
            if (!$request->filled('subject')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Subject name is required',
                    'token' => null,
                ], 400);
            }

            if (!$request->filled('date')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Date is required (format: YYYY-MM-DD)',
                    'token' => null,
                ], 400);
            }

            $subjectName = trim($request->subject);
            $date = trim($request->date);

            // Validate date format
            try {
                $dateObj = \Carbon\Carbon::parse($date);
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid date format. Please use YYYY-MM-DD format.',
                    'token' => null,
                ], 422);
            }

            // Find subject by name (case-insensitive, partial match)
            $subject = Subject::whereRaw('LOWER(TRIM(subject_name)) LIKE ?', ['%' . strtolower($subjectName) . '%'])
                ->first();

            if (!$subject) {
                return response()->json([
                    'success' => false,
                    'message' => 'Subject not found: ' . $subjectName,
                    'token' => null,
                ], 404);
            }

            // Get homework for specific subject and date
            $homework = HomeworkDiary::whereNotNull('homework_content')
                ->where('homework_content', '!=', '')
                ->whereRaw('TRIM(homework_content) != ?', [''])
                ->where('subject_id', $subject->id)
                ->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($student->campus))])
                ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($student->class))])
                ->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($student->section))])
                ->whereDate('date', $date)
                ->with('subject')
                ->first();

            if (!$homework) {
                return response()->json([
                    'success' => true,
                    'message' => 'No homework found for the specified subject and date',
                    'data' => [
                        'student' => [
                            'id' => $student->id,
                            'name' => $student->student_name,
                            'student_code' => $student->student_code,
                            'class' => $student->class,
                            'section' => $student->section,
                            'campus' => $student->campus,
                        ],
                        'subject' => [
                            'id' => $subject->id,
                            'subject_name' => $subject->subject_name,
                        ],
                        'date' => $date,
                        'date_formatted' => $dateObj->format('d M Y'),
                        'date_formatted_full' => $dateObj->format('l, d F Y'),
                        'homework' => null,
                    ],
                    'token' => $request->user()->currentAccessToken()->token ?? null,
                ], 200);
            }

            // Format homework data
            $homeworkData = [
                'id' => $homework->id,
                'subject_id' => $homework->subject_id,
                'subject_name' => $homework->subject->subject_name ?? null,
                'campus' => $homework->campus,
                'class' => $homework->class,
                'section' => $homework->section,
                'date' => $homework->date->format('Y-m-d'),
                'date_formatted' => $homework->date->format('d M Y'),
                'date_formatted_full' => $homework->date->format('l, d F Y'),
                'day_name' => $homework->date->format('l'),
                'day_short' => $homework->date->format('D'),
                'homework_content' => $homework->homework_content,
                'created_at' => $homework->created_at->format('Y-m-d H:i:s'),
                'created_at_formatted' => $homework->created_at->format('d M Y, h:i A'),
            ];

            return response()->json([
                'success' => true,
                'message' => 'Homework retrieved successfully',
                'data' => [
                    'student' => [
                        'id' => $student->id,
                        'name' => $student->student_name,
                        'student_code' => $student->student_code,
                        'class' => $student->class,
                        'section' => $student->section,
                        'campus' => $student->campus,
                    ],
                    'subject' => [
                        'id' => $subject->id,
                        'subject_name' => $subject->subject_name,
                    ],
                    'date' => $date,
                    'date_formatted' => $dateObj->format('d M Y'),
                    'date_formatted_full' => $dateObj->format('l, d F Y'),
                    'homework' => $homeworkData,
                ],
                'token' => $request->user()->currentAccessToken()->token ?? null,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving homework: ' . $e->getMessage(),
                'token' => null,
            ], 500);
        }
    }
}

