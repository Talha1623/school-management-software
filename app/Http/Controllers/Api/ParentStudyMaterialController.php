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
     * Get Study Materials List for Parent's Student (same format as student API)
     * Returns study materials for a specific student
     * 
     * GET /api/parent/study-material/list?student_id=3
     * Optional: subject, file_type, search
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

            // Optional student_id parameter - if not provided, use first student (similar to student API)
            if ($request->filled('student_id')) {
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
            } else {
                // Get first student from parent's children (similar to student API using authenticated student)
                $parentStudents = $parent->students()->get();
                
                if ($parentStudents->isEmpty()) {
                    return response()->json([
                        'success' => true,
                        'message' => 'No students found for this parent',
                        'data' => [
                            'student' => null,
                            'study_materials' => [],
                            'pagination' => [
                                'current_page' => 1,
                                'last_page' => 1,
                                'per_page' => 30,
                                'total' => 0,
                                'from' => null,
                                'to' => null,
                            ],
                        ],
                        'token' => $request->user()->currentAccessToken()->token ?? null,
                    ], 200);
                }
                
                $student = $parentStudents->first();
            }

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

            // Format study materials data (same format as student API)
            $studyMaterialsData = $studyMaterials->map(function($material) {
                // Get file URL or YouTube URL based on file type
                $fileUrl = null;
                $youtubeUrl = null;
                
                if ($material->file_type === 'video') {
                    // For video type, use YouTube URL
                    if ($material->youtube_url) {
                        $youtubeUrl = $material->youtube_url;
                        $fileUrl = $material->youtube_url; // Also set in file_url for consistency
                    }
                } elseif ($material->file_type === 'picture' || $material->file_type === 'documents') {
                    // For picture or documents, use file_path
                    if ($material->file_path) {
                        // Check if file exists in storage
                        if (Storage::disk('public')->exists($material->file_path)) {
                            $fileUrl = asset('storage/' . $material->file_path);
                            // Convert to full URL if needed
                            if (!filter_var($fileUrl, FILTER_VALIDATE_URL)) {
                                $fileUrl = url($fileUrl);
                            }
                        }
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
                    'file_type' => $material->file_type, // video, picture, documents
                    'file_url' => $fileUrl, // For picture/documents - direct file URL, For video - YouTube URL
                    'youtube_url' => $youtubeUrl, // Only for video type
                    'file_path' => $material->file_path ?? null, // Only for picture/documents
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

