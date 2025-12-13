<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StudyMaterial;
use App\Models\ParentAccount;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class ParentStudyMaterialController extends Controller
{
    /**
     * Get Study Materials List for Parent's Students
     * Returns study materials for all students connected to this parent
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
                        'study_materials' => [],
                        'students' => [],
                    ],
                    'token' => $request->user()->currentAccessToken()->token ?? null,
                ], 200);
            }

            // Build query to get study materials for all parent's students
            $query = StudyMaterial::query();

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

            // Filter study materials by students' campus, class, section
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
                        'study_materials' => [],
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

            // Filter by class (optional)
            if ($request->filled('class')) {
                $query->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($request->class))]);
            }

            // Filter by section (optional)
            if ($request->filled('section')) {
                $query->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($request->section))]);
            }

            // Filter by subject (optional)
            if ($request->filled('subject')) {
                $query->whereRaw('LOWER(TRIM(subject)) = ?', [strtolower(trim($request->subject))]);
            }

            // Filter by file_type (optional)
            if ($request->filled('file_type')) {
                $query->where('file_type', $request->file_type);
            }

            // Search functionality
            if ($request->filled('search')) {
                $search = trim($request->search);
                if (!empty($search)) {
                    $searchLower = strtolower($search);
                    $query->where(function($q) use ($searchLower) {
                        $q->whereRaw('LOWER(title) LIKE ?', ["%{$searchLower}%"])
                          ->orWhereRaw('LOWER(description) LIKE ?', ["%{$searchLower}%"])
                          ->orWhereRaw('LOWER(subject) LIKE ?', ["%{$searchLower}%"]);
                    });
                }
            }

            // Pagination
            $perPage = $request->get('per_page', 30);
            $perPage = in_array($perPage, [10, 25, 30, 50, 100]) ? $perPage : 30;

            $studyMaterials = $query->orderBy('created_at', 'desc')
                ->orderBy('id', 'desc')
                ->paginate($perPage);

            // Format study materials data with student information
            $studyMaterialsData = $studyMaterials->map(function($material) use ($students) {
                // Find which students this material applies to
                $applicableStudents = $students->filter(function($student) use ($material) {
                    return strtolower(trim($student->campus ?? '')) === strtolower(trim($material->campus ?? '')) &&
                           strtolower(trim($student->class ?? '')) === strtolower(trim($material->class ?? '')) &&
                           strtolower(trim($student->section ?? '')) === strtolower(trim($material->section ?? ''));
                })->map(function($student) {
                    return [
                        'id' => $student->id,
                        'student_name' => $student->student_name,
                        'student_code' => $student->student_code,
                        'class' => $student->class,
                        'section' => $student->section,
                    ];
                })->values();

                // Get file URL or YouTube URL
                $fileUrl = null;
                if ($material->file_type === 'video' && $material->youtube_url) {
                    $fileUrl = $material->youtube_url;
                } elseif ($material->file_path) {
                    $fileUrl = asset('storage/' . $material->file_path);
                    // Convert to full URL if needed
                    if (!filter_var($fileUrl, FILTER_VALIDATE_URL)) {
                        $fileUrl = url($fileUrl);
                    }
                }

                return [
                    'id' => $material->id,
                    'title' => $material->title,
                    'description' => $material->description ?? null,
                    'campus' => $material->campus,
                    'class' => $material->class,
                    'section' => $material->section ?? null,
                    'subject' => $material->subject ?? null,
                    'file_type' => $material->file_type,
                    'file_url' => $fileUrl,
                    'youtube_url' => $material->youtube_url ?? null,
                    'file_path' => $material->file_path ?? null,
                    'applicable_students' => $applicableStudents,
                    'applicable_students_count' => $applicableStudents->count(),
                    'created_at' => $material->created_at->format('Y-m-d H:i:s'),
                    'created_at_formatted' => $material->created_at->format('d M Y, h:i A'),
                    'updated_at' => $material->updated_at->format('Y-m-d H:i:s'),
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
                'message' => 'Study materials retrieved successfully',
                'data' => [
                    'parent_id' => $parent->id,
                    'total_students' => $students->count(),
                    'students' => $studentsData,
                    'study_materials' => $studyMaterialsData,
                    'pagination' => [
                        'current_page' => $studyMaterials->currentPage(),
                        'last_page' => $studyMaterials->lastPage(),
                        'per_page' => $studyMaterials->perPage(),
                        'total' => $studyMaterials->total(),
                        'from' => $studyMaterials->firstItem(),
                        'to' => $studyMaterials->lastItem(),
                    ],
                ],
                'token' => $request->user()->currentAccessToken()->token ?? null,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving study materials: ' . $e->getMessage(),
                'token' => null,
            ], 200);
        }
    }

    /**
     * Get Study Materials by Student ID
     * Returns study materials for a specific student
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
                    'message' => 'Student information incomplete. Cannot fetch study materials.',
                    'token' => null,
                ], 400);
            }

            // Get study materials for this student's class, section, campus
            $query = StudyMaterial::whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($student->campus))])
                ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($student->class))])
                ->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($student->section))]);

            // Filter by subject (optional)
            if ($request->filled('subject')) {
                $query->whereRaw('LOWER(TRIM(subject)) = ?', [strtolower(trim($request->subject))]);
            }

            // Filter by file_type (optional)
            if ($request->filled('file_type')) {
                $query->where('file_type', $request->file_type);
            }

            // Search functionality
            if ($request->filled('search')) {
                $search = trim($request->search);
                if (!empty($search)) {
                    $searchLower = strtolower($search);
                    $query->where(function($q) use ($searchLower) {
                        $q->whereRaw('LOWER(title) LIKE ?', ["%{$searchLower}%"])
                          ->orWhereRaw('LOWER(description) LIKE ?', ["%{$searchLower}%"])
                          ->orWhereRaw('LOWER(subject) LIKE ?', ["%{$searchLower}%"]);
                    });
                }
            }

            // Pagination
            $perPage = $request->get('per_page', 30);
            $perPage = in_array($perPage, [10, 25, 30, 50, 100]) ? $perPage : 30;

            $studyMaterials = $query->orderBy('created_at', 'desc')
                ->orderBy('id', 'desc')
                ->paginate($perPage);

            // Format study materials data
            $studyMaterialsData = $studyMaterials->map(function($material) {
                // Get file URL or YouTube URL
                $fileUrl = null;
                if ($material->file_type === 'video' && $material->youtube_url) {
                    $fileUrl = $material->youtube_url;
                } elseif ($material->file_path) {
                    $fileUrl = asset('storage/' . $material->file_path);
                    if (!filter_var($fileUrl, FILTER_VALIDATE_URL)) {
                        $fileUrl = url($fileUrl);
                    }
                }

                return [
                    'id' => $material->id,
                    'title' => $material->title,
                    'description' => $material->description ?? null,
                    'campus' => $material->campus,
                    'class' => $material->class,
                    'section' => $material->section ?? null,
                    'subject' => $material->subject ?? null,
                    'file_type' => $material->file_type,
                    'file_url' => $fileUrl,
                    'youtube_url' => $material->youtube_url ?? null,
                    'file_path' => $material->file_path ?? null,
                    'created_at' => $material->created_at->format('Y-m-d H:i:s'),
                    'created_at_formatted' => $material->created_at->format('d M Y, h:i A'),
                    'updated_at' => $material->updated_at->format('Y-m-d H:i:s'),
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Study materials retrieved successfully',
                'data' => [
                    'student' => [
                        'id' => $student->id,
                        'student_name' => $student->student_name,
                        'student_code' => $student->student_code,
                        'class' => $student->class,
                        'section' => $student->section,
                        'campus' => $student->campus,
                    ],
                    'study_materials' => $studyMaterialsData,
                    'pagination' => [
                        'current_page' => $studyMaterials->currentPage(),
                        'last_page' => $studyMaterials->lastPage(),
                        'per_page' => $studyMaterials->perPage(),
                        'total' => $studyMaterials->total(),
                        'from' => $studyMaterials->firstItem(),
                        'to' => $studyMaterials->lastItem(),
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
                'message' => 'An error occurred while retrieving study materials: ' . $e->getMessage(),
                'token' => null,
            ], 200);
        }
    }

    /**
     * Get Subjects List for Parent's Students
     * Returns all subjects for which study materials exist
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

            // Get subjects from study materials
            $subjects = [];
            foreach ($studentFilters as $filter) {
                $materialSubjects = StudyMaterial::whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($filter['campus']))])
                    ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($filter['class']))])
                    ->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($filter['section']))])
                    ->whereNotNull('subject')
                    ->distinct()
                    ->pluck('subject')
                    ->toArray();
                
                $subjects = array_merge($subjects, $materialSubjects);
            }

            $subjects = array_unique($subjects);
            sort($subjects);

            return response()->json([
                'success' => true,
                'message' => 'Subjects retrieved successfully',
                'data' => [
                    'subjects' => array_values($subjects),
                    'total_subjects' => count($subjects),
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

