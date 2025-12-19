<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HomeworkDiary;
use App\Models\ParentAccount;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ParentHomeworkController extends Controller
{
    /**
     * Get Homework List for Parent's Students
     * Returns homework for all students connected to this parent
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function list(Request $request): JsonResponse
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

            // Get parent's students
            $students = $parent->students()->get();

            if ($students->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'message' => 'No students found for this parent',
                    'data' => [],
                    'token' => $request->user()->currentAccessToken()->token ?? null,
                ], 200);
            }

            // Build query to get homework for all parent's students
            // Only show homework where homework_content is not empty
            $query = HomeworkDiary::whereNotNull('homework_content')
                ->where('homework_content', '!=', '')
                ->whereRaw('TRIM(homework_content) != ?', ['']);

            // Filter by student_id (optional - specific student)
            if ($request->filled('student_id')) {
                // If student_id is provided, date is also required
                if (!$request->filled('date')) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Date is required when student_id is provided',
                        'token' => null,
                    ], 400);
                }
                // Verify student belongs to this parent
                $student = $students->firstWhere('id', $request->student_id);
                
                if (!$student) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Student not found or does not belong to this parent',
                        'token' => null,
                    ], 404);
                }

                if (!$student->campus || !$student->class || !$student->section) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Student information incomplete. Cannot fetch homework.',
                        'token' => null,
                    ], 400);
                }

                // Filter homework only for this specific student
                $query->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($student->campus))])
                      ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($student->class))])
                      ->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($student->section))]);
            } else {
                // Get unique combinations of campus, class, section from all students
                $studentFilters = [];
                foreach ($students as $student) {
                    if ($student->campus && $student->class && $student->section) {
                        $key = strtolower(trim($student->campus)) . '|' . 
                               strtolower(trim($student->class)) . '|' . 
                               strtolower(trim($student->section));
                        
                        if (!isset($studentFilters[$key])) {
                            $studentFilters[$key] = [
                                'campus' => $student->campus,
                                'class' => $student->class,
                                'section' => $student->section,
                            ];
                        }
                    }
                }

                // Filter homework by students' campus, class, section
                if (!empty($studentFilters)) {
                    $query->where(function($q) use ($studentFilters) {
                        foreach ($studentFilters as $filter) {
                            $q->orWhere(function($subQ) use ($filter) {
                                $subQ->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($filter['campus']))])
                                    ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($filter['class']))])
                                    ->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($filter['section']))]);
                            });
                        }
                    });
                } else {
                    // If no valid filters, return empty
                    return response()->json([
                        'success' => true,
                        'message' => 'No valid student information found',
                        'data' => [],
                        'token' => $request->user()->currentAccessToken()->token ?? null,
                    ], 200);
                }
            }

            // Filter by date
            if ($request->filled('date')) {
                // Validate date format
                try {
                    $homeworkDate = \Carbon\Carbon::parse($request->date);
                    $query->whereDate('date', $homeworkDate->format('Y-m-d'));
                } catch (\Exception $e) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid date format. Please use Y-m-d format (e.g., 2024-01-15)',
                        'token' => null,
                    ], 400);
                }
            }

            // Filter by date range (optional)
            if ($request->filled('start_date')) {
                $query->whereDate('date', '>=', $request->start_date);
            }

            if ($request->filled('end_date')) {
                $query->whereDate('date', '<=', $request->end_date);
            }

            // Filter by subject (optional)
            if ($request->filled('subject_id')) {
                $query->where('subject_id', $request->subject_id);
            }

            // Pagination
            $perPage = $request->get('per_page', 30);
            $perPage = in_array($perPage, [10, 25, 30, 50, 100]) ? $perPage : 30;

            $homeworkList = $query->with('subject')
                ->orderBy('date', 'desc')
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);

            // Format homework data - simple list only
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
                    'homework_content' => $homework->homework_content,
                    'created_at' => $homework->created_at->format('Y-m-d H:i:s'),
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Homework list retrieved successfully',
                'data' => $homeworkData,
                'token' => $request->user()->currentAccessToken()->token ?? null,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving homework: ' . $e->getMessage(),
                'token' => null,
            ], 200);
        }
    }

    /**
     * Get Homework by Date (similar to student API)
     * Returns homework for a specific student on a specific date
     * 
     * GET /api/parent/homework/date/{date}?student_id=3
     * 
     * @param Request $request
     * @param string $date (YYYY-MM-DD format)
     * @return JsonResponse
     */
    public function getByDate(Request $request, string $date): JsonResponse
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

            // Format homework data (same format as student API)
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
     * Get Homework by Student ID
     * Returns homework for a specific student
     * 
     * @param Request $request
     * @param int $studentId
     * @return JsonResponse
     */
    public function getByStudent(Request $request, int $studentId): JsonResponse
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

            // Verify student belongs to this parent
            $student = $parent->students()->findOrFail($studentId);

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

            // Filter by date (optional)
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

            // Pagination
            $perPage = $request->get('per_page', 30);
            $perPage = in_array($perPage, [10, 25, 30, 50, 100]) ? $perPage : 30;

            $homeworkList = $query->with('subject')
                ->orderBy('date', 'desc')
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);

            // Format homework data
            $homeworkData = $homeworkList->map(function($homework) use ($student) {
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
                    'homework_content' => $homework->homework_content,
                    'created_at' => $homework->created_at->format('Y-m-d H:i:s'),
                    'created_at_formatted' => $homework->created_at->format('d M Y, h:i A'),
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Homework retrieved successfully',
                'data' => [
                    'student' => [
                        'id' => $student->id,
                        'student_name' => $student->student_name,
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

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Student not found or does not belong to this parent',
                'token' => null,
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving homework: ' . $e->getMessage(),
                'token' => null,
            ], 200);
        }
    }

    /**
     * Get Subjects List for Parent's Students
     * Returns all subjects for which homework exists
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getSubjects(Request $request): JsonResponse
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

            // Get parent's students
            $students = $parent->students()->get();

            if ($students->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'message' => 'No students found',
                    'data' => [
                        'subjects' => [],
                    ],
                    'token' => $request->user()->currentAccessToken()->token ?? null,
                ], 200);
            }

            // Get unique combinations of campus, class, section
            $studentFilters = [];
            foreach ($students as $student) {
                if ($student->campus && $student->class && $student->section) {
                    $key = strtolower(trim($student->campus)) . '|' . 
                           strtolower(trim($student->class)) . '|' . 
                           strtolower(trim($student->section));
                    
                    if (!isset($studentFilters[$key])) {
                        $studentFilters[$key] = [
                            'campus' => $student->campus,
                            'class' => $student->class,
                            'section' => $student->section,
                        ];
                    }
                }
            }

            // Get subjects from homework diaries
            $subjectIds = [];
            foreach ($studentFilters as $filter) {
                $homeworkSubjectIds = HomeworkDiary::whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($filter['campus']))])
                    ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($filter['class']))])
                    ->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($filter['section']))])
                    ->pluck('subject_id')
                    ->toArray();
                
                $subjectIds = array_merge($subjectIds, $homeworkSubjectIds);
            }

            $subjectIds = array_unique($subjectIds);

            $subjects = Subject::whereIn('id', $subjectIds)
                ->orderBy('subject_name', 'asc')
                ->get();

            $subjectsData = $subjects->map(function($subject) {
                return [
                    'id' => $subject->id,
                    'subject_name' => $subject->subject_name,
                    'class' => $subject->class,
                    'section' => $subject->section,
                    'campus' => $subject->campus,
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Subjects retrieved successfully',
                'data' => [
                    'subjects' => $subjectsData,
                    'total_subjects' => $subjects->count(),
                ],
                'token' => $request->user()->currentAccessToken()->token ?? null,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving subjects: ' . $e->getMessage(),
                'token' => null,
            ], 200);
        }
    }
}

