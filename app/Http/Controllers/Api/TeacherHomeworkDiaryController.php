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
use Carbon\Carbon;

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
            
            if (!$teacher || !$teacher->isTeacher()) {
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
            
            if (!$teacher || !$teacher->isTeacher()) {
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
            
            if (!$teacher || !$teacher->isTeacher()) {
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
            
            if (!$teacher || !$teacher->isTeacher()) {
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
            
            if (!$teacher || !$teacher->isTeacher()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Only teachers can create homework.',
                ], 403);
            }

            // Get teacher's assigned subject IDs first
            $teacherSubjectIds = Subject::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($teacher->name ?? ''))])
                ->pluck('id')
                ->toArray();

            if (empty($teacherSubjectIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No subjects assigned to you. Please contact administrator.',
                ], 403);
            }

            $validated = $request->validate([
                'campus' => ['required', 'string'],
                'class' => ['required', 'string'],
                'section' => ['required', 'string'],
                'date' => ['required', 'date'],
                'subject_id' => ['required', 'integer', 'in:' . implode(',', $teacherSubjectIds)],
                'homework_content' => ['nullable', 'string'],
            ], [
                'subject_id.in' => 'The selected subject is not assigned to you. You can only add homework for your assigned subjects.',
                'subject_id.required' => 'Subject ID is required.',
                'subject_id.integer' => 'Subject ID must be a valid number.',
            ]);

            // Get subject for further use
            $subject = Subject::findOrFail($validated['subject_id']);

            // Additional security check: Verify subject is assigned to this teacher
            // and matches the provided class, section, and campus
            if (strtolower(trim($subject->teacher ?? '')) !== strtolower(trim($teacher->name ?? ''))) {
                return response()->json([
                    'success' => false,
                    'message' => 'This subject is not assigned to you. You can only add homework for your assigned subjects.',
                ], 403);
            }

            // Verify subject's class, section, and campus match the request
            if (strtolower(trim($subject->class ?? '')) !== strtolower(trim($validated['class']))) {
                return response()->json([
                    'success' => false,
                    'message' => 'Subject class does not match. This subject is assigned to class: ' . ($subject->class ?? 'N/A'),
                ], 403);
            }

            if (strtolower(trim($subject->section ?? '')) !== strtolower(trim($validated['section']))) {
                return response()->json([
                    'success' => false,
                    'message' => 'Subject section does not match. This subject is assigned to section: ' . ($subject->section ?? 'N/A'),
                ], 403);
            }

            if (strtolower(trim($subject->campus ?? '')) !== strtolower(trim($validated['campus']))) {
                return response()->json([
                    'success' => false,
                    'message' => 'Subject campus does not match. This subject is assigned to campus: ' . ($subject->campus ?? 'N/A'),
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
            
            if (!$teacher || !$teacher->isTeacher()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Only teachers can create homework.',
                ], 403);
            }

            // Get teacher's assigned subject IDs first
            $teacherSubjectIds = Subject::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($teacher->name ?? ''))])
                ->pluck('id')
                ->toArray();

            if (empty($teacherSubjectIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No subjects assigned to you. Please contact administrator.',
                ], 403);
            }

            $validated = $request->validate([
                'campus' => ['required', 'string'],
                'class' => ['required', 'string'],
                'section' => ['required', 'string'],
                'date' => ['required', 'date'],
                'diaries' => ['required', 'array', 'min:1'],
                'diaries.*.subject_id' => ['required', 'integer', 'in:' . implode(',', $teacherSubjectIds)],
                'diaries.*.homework_content' => ['nullable', 'string'],
            ], [
                'diaries.required' => 'The diaries field is required.',
                'diaries.array' => 'The diaries must be an array.',
                'diaries.min' => 'At least one diary entry is required.',
                'diaries.*.subject_id.required' => 'Subject ID is required for each diary entry.',
                'diaries.*.subject_id.integer' => 'Subject ID must be a valid number.',
                'diaries.*.subject_id.in' => 'The selected subject is not assigned to you. You can only add homework for your assigned subjects.',
            ]);

            // Check if diaries array is empty
            if (empty($validated['diaries']) || count($validated['diaries']) === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'At least one diary entry is required. Please provide at least one subject with homework content.',
                    'errors' => [
                        'diaries' => ['The diaries array must contain at least one entry.']
                    ],
                ], 422);
            }

            $savedCount = 0;
            $updatedCount = 0;
            $deletedCount = 0;
            $errors = [];

            foreach ($validated['diaries'] as $index => $diaryData) {
                try {
                    // Subject validation already done in request validation
                    $subject = Subject::findOrFail($diaryData['subject_id']);

                    // Additional security check: Verify subject is assigned to this teacher
                    if (strtolower(trim($subject->teacher ?? '')) !== strtolower(trim($teacher->name ?? ''))) {
                        $errors[] = "Subject ID {$diaryData['subject_id']}: This subject is not assigned to you.";
                        continue;
                    }

                    // Verify subject's class, section, and campus match the request
                    if (strtolower(trim($subject->class ?? '')) !== strtolower(trim($validated['class']))) {
                        $errors[] = "Subject ID {$diaryData['subject_id']}: Subject class does not match. Expected: {$validated['class']}, Found: " . ($subject->class ?? 'N/A');
                        continue;
                    }

                    if (strtolower(trim($subject->section ?? '')) !== strtolower(trim($validated['section']))) {
                        $errors[] = "Subject ID {$diaryData['subject_id']}: Subject section does not match. Expected: {$validated['section']}, Found: " . ($subject->section ?? 'N/A');
                        continue;
                    }

                    if (strtolower(trim($subject->campus ?? '')) !== strtolower(trim($validated['campus']))) {
                        $errors[] = "Subject ID {$diaryData['subject_id']}: Subject campus does not match. Expected: {$validated['campus']}, Found: " . ($subject->campus ?? 'N/A');
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
            
            if (!$teacher || !$teacher->isTeacher()) {
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
            
            if (!$teacher || !$teacher->isTeacher()) {
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
            
            if (!$teacher || !$teacher->isTeacher()) {
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

    /**
     * Get All Teacher's Assigned Subjects (Simple List)
     * Lists all subjects assigned to the logged-in teacher for homework
     * Optional: Filter by class - agar class select kiya to sirf us class ke subjects return honge
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getTeacherSubjects(Request $request): JsonResponse
    {
        try {
            $teacher = $request->user();
            
            if (!$teacher || !$teacher->isTeacher()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Only teachers can access this endpoint.',
                ], 403);
            }

            // Get optional class and section filters
            $classFilter = $request->get('class');
            $sectionFilter = $request->get('section');

            // Get all subjects assigned to this teacher
            $subjectsQuery = Subject::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($teacher->name ?? ''))])
                ->whereNotNull('subject_name');

            // Filter by class if provided
            if ($classFilter) {
                $subjectsQuery->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($classFilter))]);
            }

            // Filter by section if provided
            if ($sectionFilter) {
                $subjectsQuery->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($sectionFilter))]);
            }

            $subjects = $subjectsQuery->get();

            // Sort by class, then by subject name (case-insensitive)
            $subjects = $subjects->sortBy(function($subject) {
                return [
                    strtolower(trim($subject->class ?? '')),
                    strtolower(trim($subject->subject_name ?? '')),
                    strtolower(trim($subject->section ?? '')),
                ];
            })->values();

            // Get all subject IDs to fetch homework
            $subjectIds = $subjects->pluck('id');
            
            // Get homework diaries for these subjects (latest first)
            $homeworkDiaries = HomeworkDiary::whereIn('subject_id', $subjectIds)
                ->orderBy('date', 'desc')
                ->orderBy('created_at', 'desc')
                ->get()
                ->groupBy('subject_id');
            
            $subjectsData = $subjects->map(function($subject) use ($homeworkDiaries) {
                // Get homework for this subject
                $homeworkList = [];
                if (isset($homeworkDiaries[$subject->id])) {
                    $homeworkList = $homeworkDiaries[$subject->id]->map(function($homework) {
                        return [
                            'id' => $homework->id,
                            'date' => $homework->date->format('Y-m-d'),
                            'homework_content' => $homework->homework_content,
                            'created_at' => $homework->created_at ? $homework->created_at->format('Y-m-d H:i:s') : null,
                            'updated_at' => $homework->updated_at ? $homework->updated_at->format('Y-m-d H:i:s') : null,
                        ];
                    })->values()->toArray();
                }
                
                return [
                    'id' => $subject->id,
                    'subject_name' => $subject->subject_name,
                    'campus' => $subject->campus,
                    'class' => $subject->class,
                    'section' => $subject->section,
                    'homework' => $homeworkList,
                    'homework_count' => count($homeworkList),
                ];
            });

            // Group by class for better organization (optional - can be used in frontend)
            $subjectsByClass = $subjectsData->groupBy(function($subject) {
                return strtolower(trim($subject['class'] ?? ''));
            })->map(function($classSubjects) {
                return $classSubjects->values();
            });

            // Build response message
            $message = 'Teacher subjects retrieved successfully';
            $filters = [];
            if ($classFilter) {
                $filters[] = 'class: ' . $classFilter;
            }
            if ($sectionFilter) {
                $filters[] = 'section: ' . $sectionFilter;
            }
            if (!empty($filters)) {
                $message .= ' for ' . implode(', ', $filters);
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'teacher' => [
                        'name' => $teacher->name,
                        'emp_id' => $teacher->emp_id,
                    ],
                    'class_filter' => $classFilter ? $classFilter : null,
                    'section_filter' => $sectionFilter ? $sectionFilter : null,
                    'subjects' => $subjectsData->values(),
                    'subjects_by_class' => $subjectsByClass,
                    'total_subjects' => $subjectsData->count(),
                    'classes' => $subjectsData->pluck('class')->unique()->values(),
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving teacher subjects: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get All Subjects by Class
     * Lists all subjects assigned to a specific class (regardless of teacher/section)
     * Sirf us class ke assigned subjects return karta hai, mix nahi hota
     * Har class ka apna subject hai, jo assign kiya hai wo hi list hota hai
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getSubjectsByClass(Request $request): JsonResponse
    {
        try {
            $teacher = $request->user();
            
            if (!$teacher || !$teacher->isTeacher()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Only teachers can access this endpoint.',
                ], 403);
            }

            $request->validate([
                'class' => ['required', 'string'],
            ]);

            $class = trim($request->get('class'));

            // Get all subjects for this specific class only
            // Strict filtering: sirf us class ke subjects jo assign kiye gaye hain
            // Case-insensitive matching to ensure proper filtering
            $subjects = Subject::whereRaw('LOWER(TRIM(class)) = ?', [strtolower($class)])
                ->whereNotNull('subject_name')
                ->whereNotNull('class')
                ->orderBy('subject_name', 'asc')
                ->orderBy('section', 'asc')
                ->get();

            // Verify that all subjects belong to the requested class (double check)
            $subjects = $subjects->filter(function($subject) use ($class) {
                return strtolower(trim($subject->class ?? '')) === strtolower($class);
            });

            $subjectsData = $subjects->map(function($subject) use ($class) {
                return [
                    'id' => $subject->id,
                    'subject_name' => $subject->subject_name,
                    'campus' => $subject->campus,
                    'class' => $class, // Ensure exact requested class name is returned
                    'section' => $subject->section,
                    'teacher' => $subject->teacher,
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Subjects retrieved successfully for class: ' . $class,
                'data' => [
                    'class' => $class,
                    'subjects' => $subjectsData->values(),
                    'total_subjects' => $subjectsData->count(),
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
                'message' => 'An error occurred while retrieving subjects: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get Subjects with Homework for Class and Section
     * Lists all subjects assigned to a specific class and section with their homework entries
     * Sirf us class aur section ke assigned subjects return karta hai, unke homework ke saath
     * Example: Class 5 Section A ko 3 subjects assign hain, to sirf wo 3 subjects list honge with their homework
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getSubjectsWithHomework(Request $request): JsonResponse
    {
        try {
            $teacher = $request->user();
            
            if (!$teacher || !$teacher->isTeacher()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Only teachers can access this endpoint.',
                ], 403);
            }

            $request->validate([
                'class' => ['required', 'string'],
                'section' => ['required', 'string'],
                'date' => ['nullable', 'date'], // Optional: if provided, filter homework by date
            ]);

            $class = trim($request->get('class'));
            $section = trim($request->get('section'));
            $date = $request->get('date');

            // Get all subjects for this specific class and section only
            // Sirf us class aur section ke subjects jo assign kiye gaye hain
            $subjects = Subject::whereRaw('LOWER(TRIM(class)) = ?', [strtolower($class)])
                ->whereRaw('LOWER(TRIM(section)) = ?', [strtolower($section)])
                ->whereNotNull('subject_name')
                ->whereNotNull('class')
                ->whereNotNull('section')
                ->orderBy('subject_name', 'asc')
                ->get();

            // Verify that all subjects belong to the requested class and section (double check)
            $subjects = $subjects->filter(function($subject) use ($class, $section) {
                return strtolower(trim($subject->class ?? '')) === strtolower($class) 
                    && strtolower(trim($subject->section ?? '')) === strtolower($section);
            });

            if ($subjects->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'message' => 'No subjects found for class: ' . $class . ', section: ' . $section,
                    'data' => [
                        'class' => $class,
                        'section' => $section,
                        'subjects' => [],
                        'total_subjects' => 0,
                    ],
                ], 200);
            }

            // Get subject IDs
            $subjectIds = $subjects->pluck('id');

            // Get homework entries for these subjects
            $homeworkQuery = HomeworkDiary::whereIn('subject_id', $subjectIds)
                ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower($class)])
                ->whereRaw('LOWER(TRIM(section)) = ?', [strtolower($section)]);

            // Filter by date if provided
            if ($date) {
                $homeworkQuery->whereDate('date', $date);
            }

            $homeworkEntries = $homeworkQuery->get()->groupBy('subject_id');

            // Map subjects with their homework
            $subjectsData = $subjects->map(function($subject) use ($homeworkEntries, $date, $class, $section) {
                $homework = $homeworkEntries->get($subject->id, collect());
                
                // If date is provided, return single homework entry for that date
                // Otherwise, return all homework entries for this subject
                if ($date) {
                    $homeworkData = $homework->first(); // Should be only one entry for specific date
                    $homeworkList = $homeworkData ? [[
                        'id' => $homeworkData->id,
                        'homework_content' => $homeworkData->homework_content,
                        'date' => $homeworkData->date->format('Y-m-d'),
                        'created_at' => $homeworkData->created_at->format('Y-m-d H:i:s'),
                    ]] : [];
                } else {
                    // Return all homework entries for this subject
                    $homeworkList = $homework->map(function($entry) {
                        return [
                            'id' => $entry->id,
                            'homework_content' => $entry->homework_content,
                            'date' => $entry->date->format('Y-m-d'),
                            'created_at' => $entry->created_at->format('Y-m-d H:i:s'),
                        ];
                    })->sortByDesc('date')->values()->toArray();
                }

                return [
                    'id' => $subject->id,
                    'subject_name' => $subject->subject_name,
                    'campus' => $subject->campus,
                    'class' => $class, // Ensure exact requested class name is returned
                    'section' => $section, // Ensure exact requested section name is returned
                    'teacher' => $subject->teacher,
                    'homework' => $homeworkList,
                    'homework_count' => count($homeworkList),
                ];
            });

            $message = 'Subjects with homework retrieved successfully for class: ' . $class . ', section: ' . $section;
            if ($date) {
                $message .= ', date: ' . $date;
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'class' => $class,
                    'section' => $section,
                    'date_filter' => $date ? $date : null,
                    'subjects' => $subjectsData->values(),
                    'total_subjects' => $subjectsData->count(),
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
                'message' => 'An error occurred while retrieving subjects with homework: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get Previous Date Homework Diary by Subject and Date
     * Returns homework for a specific subject and date (for teachers)
     * 
     * GET /api/teacher/homework-diary/previous?subject={subject_name}&date={date}
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function previousDate(Request $request): JsonResponse
    {
        try {
            $teacher = $request->user();
            
            if (!$teacher || !$teacher->isTeacher()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Only teachers can access homework diary.',
                ], 403);
            }

            // Validate required parameters
            if (!$request->filled('subject')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Subject name is required',
                ], 400);
            }

            if (!$request->filled('date')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Date is required (format: YYYY-MM-DD)',
                ], 400);
            }

            $subjectName = trim($request->subject);
            $date = trim($request->date);

            // Validate date format
            try {
                $dateObj = Carbon::parse($date);
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid date format. Please use YYYY-MM-DD format.',
                ], 422);
            }

            // Get teacher's assigned subject IDs first
            $teacherSubjectIds = Subject::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($teacher->name ?? ''))])
                ->pluck('id')
                ->toArray();

            if (empty($teacherSubjectIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No subjects assigned to you. Please contact administrator.',
                ], 403);
            }

            // Find subject by name (case-insensitive, partial match) - must be assigned to teacher
            $subject = Subject::whereIn('id', $teacherSubjectIds)
                ->whereRaw('LOWER(TRIM(subject_name)) LIKE ?', ['%' . strtolower($subjectName) . '%'])
                ->first();

            if (!$subject) {
                return response()->json([
                    'success' => false,
                    'message' => 'Subject not found or not assigned to you: ' . $subjectName,
                ], 404);
            }

            // Optional filters (campus, class, section)
            $campus = $request->get('campus');
            $class = $request->get('class');
            $section = $request->get('section');

            // Build query for homework
            $homeworkQuery = HomeworkDiary::where('subject_id', $subject->id)
                ->whereDate('date', $date);

            // Apply optional filters
            if ($campus) {
                $homeworkQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
            }

            if ($class) {
                $homeworkQuery->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))]);
            }

            if ($section) {
                $homeworkQuery->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($section))]);
            }

            // Get homework entries
            $homeworkList = $homeworkQuery->with('subject')
                ->orderBy('campus', 'asc')
                ->orderBy('class', 'asc')
                ->orderBy('section', 'asc')
                ->get();

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
                'message' => 'Homework retrieved successfully',
                'data' => [
                    'teacher' => [
                        'id' => $teacher->id,
                        'name' => $teacher->name,
                        'emp_id' => $teacher->emp_id ?? null,
                        'designation' => $teacher->designation,
                    ],
                    'subject' => [
                        'id' => $subject->id,
                        'subject_name' => $subject->subject_name,
                        'class' => $subject->class,
                        'section' => $subject->section,
                    ],
                    'date' => $date,
                    'date_formatted' => $dateObj->format('d M Y'),
                    'date_formatted_full' => $dateObj->format('l, d F Y'),
                    'filters' => [
                        'campus' => $campus,
                        'class' => $class,
                        'section' => $section,
                    ],
                    'total_homework' => $homeworkList->count(),
                    'homework' => $homeworkData,
                ],
                'token' => $request->user()->currentAccessToken()->token ?? null,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving homework: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update Homework by Subject and Date
     * Updates homework for a specific subject and date (for teachers)
     * 
     * POST /api/teacher/homework-diary/update?subject={subject_name}&date={date}
     * If date is not provided, current date will be used
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function updateBySubjectDate(Request $request): JsonResponse
    {
        try {
            $teacher = $request->user();
            
            if (!$teacher || !$teacher->isTeacher()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Only teachers can update homework.',
                ], 403);
            }

            // Validate required parameters
            if (!$request->filled('subject')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Subject name is required',
                ], 400);
            }

            // If date is not provided, use current date
            $subjectName = trim($request->subject);
            $date = $request->filled('date') ? trim($request->date) : Carbon::today()->format('Y-m-d');

            // Validate date format
            try {
                $dateObj = Carbon::parse($date);
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid date format. Please use YYYY-MM-DD format.',
                ], 422);
            }

            // Get teacher's assigned subject IDs first
            $teacherSubjectIds = Subject::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($teacher->name ?? ''))])
                ->pluck('id')
                ->toArray();

            if (empty($teacherSubjectIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No subjects assigned to you. Please contact administrator.',
                ], 403);
            }

            // Find subject by name (case-insensitive, partial match) - must be assigned to teacher
            $subject = Subject::whereIn('id', $teacherSubjectIds)
                ->whereRaw('LOWER(TRIM(subject_name)) LIKE ?', ['%' . strtolower($subjectName) . '%'])
                ->first();

            if (!$subject) {
                return response()->json([
                    'success' => false,
                    'message' => 'Subject not found or not assigned to you: ' . $subjectName,
                ], 404);
            }

            // Validate required fields for update
            $validated = $request->validate([
                'campus' => ['required', 'string'],
                'class' => ['required', 'string'],
                'section' => ['required', 'string'],
                'homework_content' => ['nullable', 'string'],
            ]);

            // Find homework entry
            $homework = HomeworkDiary::where('subject_id', $subject->id)
                ->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($validated['campus']))])
                ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($validated['class']))])
                ->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($validated['section']))])
                ->whereDate('date', $date)
                ->first();

            // If homework content is empty or not provided, delete the entry if it exists
            if (empty($validated['homework_content']) || !$request->filled('homework_content')) {
                if ($homework) {
                    $homework->delete();
                    return response()->json([
                        'success' => true,
                        'message' => 'Homework entry deleted (empty content)',
                        'data' => [
                            'subject' => [
                                'id' => $subject->id,
                                'subject_name' => $subject->subject_name,
                            ],
                            'date' => $date,
                            'date_formatted' => $dateObj->format('d M Y'),
                            'campus' => $validated['campus'],
                            'class' => $validated['class'],
                            'section' => $validated['section'],
                            'homework' => null,
                        ],
                        'token' => $request->user()->currentAccessToken()->token ?? null,
                    ], 200);
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'Homework entry not found to delete',
                    ], 404);
                }
            }

            // Update or create homework entry
            if ($homework) {
                // Update existing entry
                $homework->update([
                    'homework_content' => $validated['homework_content'],
                ]);
                $homework->refresh();
            } else {
                // Create new entry
                $homework = HomeworkDiary::create([
                    'subject_id' => $subject->id,
                    'campus' => $validated['campus'],
                    'class' => $validated['class'],
                    'section' => $validated['section'],
                    'date' => $date,
                    'homework_content' => $validated['homework_content'],
                ]);
            }

            // Load subject relationship
            $homework->load('subject');

            return response()->json([
                'success' => true,
                'message' => $homework->wasRecentlyCreated ? 'Homework created successfully' : 'Homework updated successfully',
                'data' => [
                    'teacher' => [
                        'id' => $teacher->id,
                        'name' => $teacher->name,
                        'emp_id' => $teacher->emp_id ?? null,
                        'designation' => $teacher->designation,
                    ],
                    'subject' => [
                        'id' => $subject->id,
                        'subject_name' => $subject->subject_name,
                        'class' => $subject->class,
                        'section' => $subject->section,
                    ],
                    'date' => $date,
                    'date_formatted' => $dateObj->format('d M Y'),
                    'date_formatted_full' => $dateObj->format('l, d F Y'),
                    'homework' => [
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
                        'updated_at' => $homework->updated_at->format('Y-m-d H:i:s'),
                        'updated_at_formatted' => $homework->updated_at->format('d M Y, h:i A'),
                    ],
                ],
                'token' => $request->user()->currentAccessToken()->token ?? null,
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
                'message' => 'An error occurred while updating homework: ' . $e->getMessage(),
            ], 500);
        }
    }
}

