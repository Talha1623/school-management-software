<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HomeworkDiary;
use App\Models\Subject;
use App\Models\Campus;
use App\Models\ClassModel;
use App\Models\Section;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TeacherHomeworkDiaryController extends Controller
{
    /**
     * Get Filter Options (Campuses, Classes, Sections)
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getFilterOptions(Request $request): JsonResponse
    {
        try {
            // Get logged-in teacher
            $teacher = $request->user();
            
            if (!$teacher || strtolower(trim($teacher->designation ?? '')) !== 'teacher') {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Only teachers can access homework diary.',
                ], 403);
            }

            // Get campuses from teacher's subjects
            $campuses = Subject::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($teacher->name ?? ''))])
                ->whereNotNull('campus')
                ->distinct()
                ->pluck('campus')
                ->sort()
                ->values();
            
            if ($campuses->isEmpty()) {
                $campuses = Campus::orderBy('campus_name', 'asc')->pluck('campus_name');
            }

            // Get classes from teacher's subjects
            $classes = Subject::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($teacher->name ?? ''))])
                ->whereNotNull('class')
                ->distinct()
                ->pluck('class')
                ->sort()
                ->values();
            
            if ($classes->isEmpty()) {
                $classes = ClassModel::whereNotNull('class_name')
                    ->distinct()
                    ->pluck('class_name')
                    ->sort()
                    ->values();
            }

            return response()->json([
                'success' => true,
                'message' => 'Filter options retrieved successfully',
                'data' => [
                    'campuses' => $campuses->values(),
                    'classes' => $classes->values(),
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving filter options: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get Sections by Class
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getSections(Request $request): JsonResponse
    {
        try {
            $teacher = $request->user();
            
            if (!$teacher || strtolower(trim($teacher->designation ?? '')) !== 'teacher') {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Only teachers can access homework diary.',
                ], 403);
            }

            $class = $request->get('class');
            
            if (!$class) {
                return response()->json([
                    'success' => true,
                    'message' => 'Sections retrieved successfully',
                    'data' => [
                        'sections' => [],
                    ],
                ], 200);
            }

            // Get sections from teacher's subjects for this class
            $sections = Subject::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($teacher->name ?? ''))])
                ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))])
                ->whereNotNull('section')
                ->distinct()
                ->pluck('section')
                ->sort()
                ->values();
            
            if ($sections->isEmpty()) {
                $sections = Section::whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))])
                    ->whereNotNull('name')
                    ->distinct()
                    ->pluck('name')
                    ->sort()
                    ->values();
            }

            return response()->json([
                'success' => true,
                'message' => 'Sections retrieved successfully',
                'data' => [
                    'sections' => $sections->values(),
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving sections: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get Teacher's Subjects (Only assigned subjects)
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getMySubjects(Request $request): JsonResponse
    {
        try {
            $teacher = $request->user();
            
            if (!$teacher || strtolower(trim($teacher->designation ?? '')) !== 'teacher') {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Only teachers can access homework diary.',
                ], 403);
            }

            $campus = $request->get('campus');
            $class = $request->get('class');
            $section = $request->get('section');

            // Get only teacher's assigned subjects
            $subjectsQuery = Subject::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($teacher->name ?? ''))]);

            if ($campus) {
                $subjectsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
            }

            if ($class) {
                $subjectsQuery->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))]);
            }

            if ($section) {
                $subjectsQuery->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($section))]);
            }

            $subjects = $subjectsQuery->orderBy('subject_name', 'asc')->get();

            $subjectsData = $subjects->map(function($subject) {
                return [
                    'id' => $subject->id,
                    'subject_name' => $subject->subject_name,
                    'campus' => $subject->campus,
                    'class' => $subject->class,
                    'section' => $subject->section,
                    'teacher' => $subject->teacher,
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Subjects retrieved successfully',
                'data' => [
                    'teacher' => [
                        'name' => $teacher->name,
                        'emp_id' => $teacher->emp_id,
                    ],
                    'subjects' => $subjectsData,
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving subjects: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get Homework Entries (Only for teacher's subjects)
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getEntries(Request $request): JsonResponse
    {
        try {
            $teacher = $request->user();
            
            if (!$teacher || strtolower(trim($teacher->designation ?? '')) !== 'teacher') {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Only teachers can access homework diary.',
                ], 403);
            }

            $request->validate([
                'campus' => ['required', 'string'],
                'class' => ['required', 'string'],
                'section' => ['required', 'string'],
                'date' => ['required', 'date'],
            ]);

            // Get teacher's subjects for this class/section
            $teacherSubjects = Subject::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($teacher->name ?? ''))])
                ->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($request->campus))])
                ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($request->class))])
                ->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($request->section))])
                ->pluck('id');

            if ($teacherSubjects->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'message' => 'No subjects assigned to you for this class/section',
                    'data' => [
                        'entries' => [],
                        'subjects' => [],
                    ],
                ], 200);
            }

            // Get existing homework entries for teacher's subjects
            $entries = HomeworkDiary::whereIn('subject_id', $teacherSubjects)
                ->whereDate('date', $request->date)
                ->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($request->campus))])
                ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($request->class))])
                ->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($request->section))])
                ->with('subject')
                ->get()
                ->keyBy('subject_id');

            // Get all teacher's subjects (even if no homework entry exists)
            $allSubjects = Subject::whereIn('id', $teacherSubjects)->get();

            $entriesData = $allSubjects->map(function($subject) use ($entries) {
                $entry = $entries->get($subject->id);
                return [
                    'id' => $entry ? $entry->id : null,
                    'subject_id' => $subject->id,
                    'subject_name' => $subject->subject_name,
                    'homework_content' => $entry ? $entry->homework_content : null,
                    'date' => $entry ? $entry->date->format('Y-m-d') : null,
                    'has_homework' => $entry !== null,
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Homework entries retrieved successfully',
                'data' => [
                    'campus' => $request->campus,
                    'class' => $request->class,
                    'section' => $request->section,
                    'date' => $request->date,
                    'entries' => $entriesData->values(),
                ],
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving entries: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create/Update Single Homework Entry
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function create(Request $request): JsonResponse
    {
        try {
            $teacher = $request->user();
            
            if (!$teacher || strtolower(trim($teacher->designation ?? '')) !== 'teacher') {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Only teachers can create homework.',
                ], 403);
            }

            $validated = $request->validate([
                'campus' => ['required', 'string'],
                'class' => ['required', 'string'],
                'section' => ['required', 'string'],
                'date' => ['required', 'date'],
                'subject_id' => ['required', 'exists:subjects,id'],
                'homework_content' => ['nullable', 'string'],
            ]);

            // Validate that subject belongs to logged-in teacher
            $subject = Subject::findOrFail($validated['subject_id']);
            
            if (strtolower(trim($subject->teacher ?? '')) !== strtolower(trim($teacher->name ?? ''))) {
                return response()->json([
                    'success' => false,
                    'message' => 'You can only add homework for your assigned subjects.',
                ], 403);
            }

            // Skip if homework content is empty
            if (empty($validated['homework_content'])) {
                // Delete existing entry if content is empty
                HomeworkDiary::where('subject_id', $validated['subject_id'])
                    ->whereDate('date', $validated['date'])
                    ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($validated['class']))])
                    ->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($validated['section']))])
                    ->delete();

                return response()->json([
                    'success' => true,
                    'message' => 'Homework entry removed (empty content)',
                ], 200);
            }

            // Create or update homework entry
            $homeworkDiary = HomeworkDiary::updateOrCreate(
                [
                    'subject_id' => $validated['subject_id'],
                    'date' => $validated['date'],
                    'class' => $validated['class'],
                    'section' => $validated['section'],
                ],
                [
                    'campus' => $validated['campus'],
                    'homework_content' => $validated['homework_content'],
                ]
            );

            return response()->json([
                'success' => true,
                'message' => $homeworkDiary->wasRecentlyCreated ? 'Homework created successfully' : 'Homework updated successfully',
                'data' => [
                    'homework' => [
                        'id' => $homeworkDiary->id,
                        'subject_id' => $homeworkDiary->subject_id,
                        'subject_name' => $subject->subject_name,
                        'homework_content' => $homeworkDiary->homework_content,
                        'campus' => $homeworkDiary->campus,
                        'class' => $homeworkDiary->class,
                        'section' => $homeworkDiary->section,
                        'date' => $homeworkDiary->date->format('Y-m-d'),
                    ],
                ],
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Subject not found',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while creating homework: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create/Update Bulk Homework Entries
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function createBulk(Request $request): JsonResponse
    {
        try {
            $teacher = $request->user();
            
            if (!$teacher || strtolower(trim($teacher->designation ?? '')) !== 'teacher') {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Only teachers can create homework.',
                ], 403);
            }

            $validated = $request->validate([
                'campus' => ['required', 'string'],
                'class' => ['required', 'string'],
                'section' => ['required', 'string'],
                'date' => ['required', 'date'],
                'diaries' => ['required', 'array'],
                'diaries.*.subject_id' => ['required', 'exists:subjects,id'],
                'diaries.*.homework_content' => ['nullable', 'string'],
            ]);

            $savedCount = 0;
            $updatedCount = 0;
            $deletedCount = 0;
            $errors = [];

            foreach ($validated['diaries'] as $index => $diaryData) {
                try {
                    // Validate that subject belongs to logged-in teacher
                    $subject = Subject::findOrFail($diaryData['subject_id']);
                    
                    if (strtolower(trim($subject->teacher ?? '')) !== strtolower(trim($teacher->name ?? ''))) {
                        $errors[] = "Subject ID {$diaryData['subject_id']}: You can only add homework for your assigned subjects.";
                        continue;
                    }

                    // Skip if homework content is empty
                    if (empty($diaryData['homework_content'])) {
                        // Delete existing entry if content is empty
                        $deleted = HomeworkDiary::where('subject_id', $diaryData['subject_id'])
                            ->whereDate('date', $validated['date'])
                            ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($validated['class']))])
                            ->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($validated['section']))])
                            ->delete();
                        
                        if ($deleted) {
                            $deletedCount++;
                        }
                        continue;
                    }

                    // Create or update homework entry
                    $homeworkDiary = HomeworkDiary::updateOrCreate(
                        [
                            'subject_id' => $diaryData['subject_id'],
                            'date' => $validated['date'],
                            'class' => $validated['class'],
                            'section' => $validated['section'],
                        ],
                        [
                            'campus' => $validated['campus'],
                            'homework_content' => $diaryData['homework_content'],
                        ]
                    );

                    if ($homeworkDiary->wasRecentlyCreated) {
                        $savedCount++;
                    } else {
                        $updatedCount++;
                    }
                } catch (\Exception $e) {
                    $errors[] = "Subject ID {$diaryData['subject_id']}: " . $e->getMessage();
                }
            }

            $message = 'Homework diary processed successfully.';
            if ($savedCount > 0) {
                $message .= " {$savedCount} new " . ($savedCount == 1 ? 'entry' : 'entries') . " created.";
            }
            if ($updatedCount > 0) {
                $message .= " {$updatedCount} " . ($updatedCount == 1 ? 'entry' : 'entries') . " updated.";
            }
            if ($deletedCount > 0) {
                $message .= " {$deletedCount} " . ($deletedCount == 1 ? 'entry' : 'entries') . " deleted (empty content).";
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'saved' => $savedCount,
                    'updated' => $updatedCount,
                    'deleted' => $deletedCount,
                    'errors' => $errors,
                ],
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while creating homework: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get Homework List (with filters)
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function list(Request $request): JsonResponse
    {
        try {
            $teacher = $request->user();
            
            if (!$teacher || strtolower(trim($teacher->designation ?? '')) !== 'teacher') {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Only teachers can view homework.',
                ], 403);
            }

            // Get teacher's subject IDs
            $teacherSubjectIds = Subject::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($teacher->name ?? ''))])
                ->pluck('id');

            if ($teacherSubjectIds->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'message' => 'No subjects assigned to you',
                    'data' => [
                        'homework' => [],
                        'pagination' => [
                            'current_page' => 1,
                            'last_page' => 1,
                            'per_page' => 30,
                            'total' => 0,
                        ],
                    ],
                ], 200);
            }

            $query = HomeworkDiary::whereIn('subject_id', $teacherSubjectIds)
                ->with('subject');

            // Filter by campus
            if ($request->filled('campus')) {
                $query->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($request->campus))]);
            }

            // Filter by class
            if ($request->filled('class')) {
                $query->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($request->class))]);
            }

            // Filter by section
            if ($request->filled('section')) {
                $query->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($request->section))]);
            }

            // Filter by date
            if ($request->filled('date')) {
                $query->whereDate('date', $request->date);
            }

            // Filter by date range
            if ($request->filled('start_date')) {
                $query->whereDate('date', '>=', $request->start_date);
            }

            if ($request->filled('end_date')) {
                $query->whereDate('date', '<=', $request->end_date);
            }

            // Filter by subject_id
            if ($request->filled('subject_id')) {
                $query->where('subject_id', $request->subject_id);
            }

            // Pagination
            $perPage = $request->get('per_page', 30);
            $perPage = in_array($perPage, [10, 25, 30, 50, 100]) ? $perPage : 30;

            $homework = $query->orderBy('date', 'desc')
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);

            $homeworkData = $homework->map(function($entry) {
                return [
                    'id' => $entry->id,
                    'subject_id' => $entry->subject_id,
                    'subject_name' => $entry->subject->subject_name ?? null,
                    'homework_content' => $entry->homework_content,
                    'campus' => $entry->campus,
                    'class' => $entry->class,
                    'section' => $entry->section,
                    'date' => $entry->date->format('Y-m-d'),
                    'created_at' => $entry->created_at->format('Y-m-d H:i:s'),
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Homework list retrieved successfully',
                'data' => [
                    'homework' => $homeworkData,
                    'pagination' => [
                        'current_page' => $homework->currentPage(),
                        'last_page' => $homework->lastPage(),
                        'per_page' => $homework->perPage(),
                        'total' => $homework->total(),
                        'from' => $homework->firstItem(),
                        'to' => $homework->lastItem(),
                    ],
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving homework list: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update Homework Entry
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $teacher = $request->user();
            
            if (!$teacher || strtolower(trim($teacher->designation ?? '')) !== 'teacher') {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Only teachers can update homework.',
                ], 403);
            }

            $homeworkDiary = HomeworkDiary::with('subject')->findOrFail($id);

            // Validate that homework subject belongs to logged-in teacher
            if (strtolower(trim($homeworkDiary->subject->teacher ?? '')) !== strtolower(trim($teacher->name ?? ''))) {
                return response()->json([
                    'success' => false,
                    'message' => 'You can only update homework for your assigned subjects.',
                ], 403);
            }

            $validated = $request->validate([
                'homework_content' => ['nullable', 'string'],
            ]);

            // If content is empty, delete the entry
            if (empty($validated['homework_content'])) {
                $homeworkDiary->delete();
                return response()->json([
                    'success' => true,
                    'message' => 'Homework entry deleted (empty content)',
                ], 200);
            }

            $homeworkDiary->update([
                'homework_content' => $validated['homework_content'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Homework updated successfully',
                'data' => [
                    'homework' => [
                        'id' => $homeworkDiary->id,
                        'subject_id' => $homeworkDiary->subject_id,
                        'subject_name' => $homeworkDiary->subject->subject_name ?? null,
                        'homework_content' => $homeworkDiary->homework_content,
                        'date' => $homeworkDiary->date->format('Y-m-d'),
                    ],
                ],
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Homework entry not found',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating homework: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete Homework Entry
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function delete(Request $request, int $id): JsonResponse
    {
        try {
            $teacher = $request->user();
            
            if (!$teacher || strtolower(trim($teacher->designation ?? '')) !== 'teacher') {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Only teachers can delete homework.',
                ], 403);
            }

            $homeworkDiary = HomeworkDiary::with('subject')->findOrFail($id);

            // Validate that homework subject belongs to logged-in teacher
            if (strtolower(trim($homeworkDiary->subject->teacher ?? '')) !== strtolower(trim($teacher->name ?? ''))) {
                return response()->json([
                    'success' => false,
                    'message' => 'You can only delete homework for your assigned subjects.',
                ], 403);
            }

            $homeworkDiary->delete();

            return response()->json([
                'success' => true,
                'message' => 'Homework deleted successfully',
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Homework entry not found',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while deleting homework: ' . $e->getMessage(),
            ], 500);
        }
    }
}

