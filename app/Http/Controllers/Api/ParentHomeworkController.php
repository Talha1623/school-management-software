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
                    'data' => [
                        'homework' => [],
                        'students' => [],
                    ],
                    'token' => $request->user()->currentAccessToken()->token ?? null,
                ], 200);
            }

            // Build query to get homework for all parent's students
            // Only show homework where homework_content is not empty
            $query = HomeworkDiary::whereNotNull('homework_content')
                ->where('homework_content', '!=', '')
                ->whereRaw('TRIM(homework_content) != ?', ['']);

            // Get unique combinations of campus, class, section from students
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
                    'data' => [
                        'homework' => [],
                        'students' => [],
                    ],
                    'token' => $request->user()->currentAccessToken()->token ?? null,
                ], 200);
            }

            // Filter by student_id (optional - specific student)
            if ($request->filled('student_id')) {
                $student = $students->firstWhere('id', $request->student_id);
                if ($student && $student->campus && $student->class && $student->section) {
                    $query->where(function($q) use ($student) {
                        $q->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($student->campus))])
                          ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($student->class))])
                          ->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($student->section))]);
                    });
                }
            }

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

            // Format homework data with student information
            $homeworkData = $homeworkList->map(function($homework) use ($students) {
                // Find which students this homework applies to
                $applicableStudents = $students->filter(function($student) use ($homework) {
                    return strtolower(trim($student->campus ?? '')) === strtolower(trim($homework->campus ?? '')) &&
                           strtolower(trim($student->class ?? '')) === strtolower(trim($homework->class ?? '')) &&
                           strtolower(trim($student->section ?? '')) === strtolower(trim($homework->section ?? ''));
                })->map(function($student) {
                    return [
                        'id' => $student->id,
                        'student_name' => $student->student_name,
                        'student_code' => $student->student_code,
                        'class' => $student->class,
                        'section' => $student->section,
                    ];
                })->values();

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
                    'applicable_students' => $applicableStudents,
                    'applicable_students_count' => $applicableStudents->count(),
                    'created_at' => $homework->created_at->format('Y-m-d H:i:s'),
                    'created_at_formatted' => $homework->created_at->format('d M Y, h:i A'),
                ];
            });

            // Format students data
            $studentsData = $students->map(function($student) {
                return [
                    'id' => $student->id,
                    'student_name' => $student->student_name,
                    'student_code' => $student->student_code,
                    'class' => $student->class,
                    'section' => $student->section,
                    'campus' => $student->campus,
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Homework list retrieved successfully',
                'data' => [
                    'parent_id' => $parent->id,
                    'total_students' => $students->count(),
                    'students' => $studentsData,
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
            ], 200);
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

