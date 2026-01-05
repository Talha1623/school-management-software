<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Test;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Section;
use App\Models\Campus;
use App\Models\StudentMark;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class TeacherTestManagementController extends Controller
{
    /**
     * Get Filter Options for Marks Entry
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getMarksEntryFilterOptions(Request $request): JsonResponse
    {
        try {
            $teacher = $request->user();
            
            if (!$teacher || !$teacher->isTeacher()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Only teachers can access marks entry.',
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

            // Get classes from teacher's assigned subjects
            $classes = Subject::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($teacher->name ?? ''))])
                ->whereNotNull('class')
                ->distinct()
                ->pluck('class')
                ->sort()
                ->values();

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
     * Get Sections by Class for Marks Entry
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getMarksEntrySections(Request $request): JsonResponse
    {
        try {
            $teacher = $request->user();
            
            if (!$teacher || !$teacher->isTeacher()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Only teachers can access marks entry.',
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

            // Get sections from teacher's assigned subjects for this class
            $assignedSubjects = Subject::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($teacher->name ?? ''))])
                ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))])
                ->get();
            
            // Get sections from teacher's assigned sections for this class
            $assignedSections = Section::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($teacher->name ?? ''))])
                ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))])
                ->get();
            
            // Merge sections from both sources
            $sections = $assignedSubjects->pluck('section')
                ->merge($assignedSections->pluck('name'))
                ->map(function($section) {
                    return trim($section);
                })
                ->filter(function($section) {
                    return !empty($section);
                })
                ->unique()
                ->sort()
                ->values();

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
     * Get Tests for Marks Entry (Only declared results - result_status = 1)
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getMarksEntryTests(Request $request): JsonResponse
    {
        try {
            $teacher = $request->user();
            
            if (!$teacher || !$teacher->isTeacher()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Only teachers can access marks entry.',
                ], 403);
            }

            $campus = $request->get('campus');
            $class = $request->get('class');
            $section = $request->get('section');
            $subject = $request->get('subject');
            
            // Only show tests where result_status = 1 (declared)
            $testsQuery = Test::where('result_status', 1);
            
            if ($campus) {
                $testsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
            }
            if ($class) {
                $testsQuery->whereRaw('LOWER(TRIM(for_class)) = ?', [strtolower(trim($class))]);
            }
            if ($section) {
                $testsQuery->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($section))]);
            }
            if ($subject) {
                $testsQuery->whereRaw('LOWER(TRIM(subject)) = ?', [strtolower(trim($subject))]);
            }
            
            $tests = $testsQuery->whereNotNull('test_name')
                ->distinct()
                ->pluck('test_name')
                ->sort()
                ->values();

            return response()->json([
                'success' => true,
                'message' => 'Tests retrieved successfully',
                'data' => [
                    'tests' => $tests,
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving tests: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get Subjects for Marks Entry
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getMarksEntrySubjects(Request $request): JsonResponse
    {
        try {
            $teacher = $request->user();
            
            if (!$teacher || !$teacher->isTeacher()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Only teachers can access marks entry.',
                ], 403);
            }

            $campus = $request->get('campus');
            $class = $request->get('class');
            $section = $request->get('section');
            
            $subjectsQuery = Subject::query();
            
            // Class is required - if not provided, return empty
            if (!$class) {
                return response()->json([
                    'success' => true,
                    'message' => 'Subjects retrieved successfully',
                    'data' => [
                        'subjects' => [],
                    ],
                ], 200);
            }
            
            // Always filter by class
            $subjectsQuery->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))]);
            
            // If campus is provided, filter by campus
            if ($campus) {
                $subjectsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
            }
            
            // If section is provided, MUST filter by section (strict filtering)
            if ($section) {
                $subjectsQuery->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($section))]);
            }
            
            $subjects = $subjectsQuery->whereNotNull('subject_name')
                ->distinct()
                ->pluck('subject_name')
                ->sort()
                ->values();

            return response()->json([
                'success' => true,
                'message' => 'Subjects retrieved successfully',
                'data' => [
                    'subjects' => $subjects,
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
     * Get Students for Marks Entry
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getMarksEntryStudents(Request $request): JsonResponse
    {
        try {
            $teacher = $request->user();
            
            if (!$teacher || !$teacher->isTeacher()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Only teachers can access marks entry.',
                ], 403);
            }

            $request->validate([
                'campus' => ['required', 'string'],
                'class' => ['required', 'string'],
                'test_name' => ['required', 'string'],
            ]);

            $campus = $request->get('campus');
            $class = $request->get('class');
            $section = $request->get('section');
            $testName = $request->get('test_name');
            $subject = $request->get('subject');

            // Get test details
            $test = Test::where('test_name', $testName)
                ->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))])
                ->whereRaw('LOWER(TRIM(for_class)) = ?', [strtolower(trim($class))])
                ->where('result_status', 1)
                ->first();

            if (!$test) {
                return response()->json([
                    'success' => false,
                    'message' => 'Test not found or result not declared.',
                ], 404);
            }

            // Query students based on filters
            $studentsQuery = Student::query();
            
            if ($campus) {
                $studentsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
            }
            if ($class) {
                $studentsQuery->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))]);
            }
            if ($section) {
                $studentsQuery->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($section))]);
            }
            
            $students = $studentsQuery->orderBy('student_name')->get();

            // Get existing marks for these students
            $existingMarks = StudentMark::where('test_name', $testName)
                ->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))])
                ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))])
                ->when($section, function($q) use ($section) {
                    $q->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($section))]);
                })
                ->when($subject, function($q) use ($subject) {
                    $q->whereRaw('LOWER(TRIM(subject)) = ?', [strtolower(trim($subject))]);
                })
                ->get()
                ->keyBy('student_id');

            $studentsData = $students->map(function($student) use ($existingMarks) {
                $mark = $existingMarks->get($student->id);
                return [
                    'id' => $student->id,
                    'student_name' => $student->student_name,
                    'student_code' => $student->student_code ?? $student->gr_number ?? 'N/A',
                    'gr_number' => $student->gr_number ?? 'N/A',
                    'father_name' => $student->father_name ?? 'N/A',
                    'marks' => $mark ? [
                        'obtained' => $mark->marks_obtained,
                        'total' => $mark->total_marks,
                        'passing' => $mark->passing_marks,
                    ] : null,
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Students retrieved successfully',
                'data' => [
                    'test' => [
                        'id' => $test->id,
                        'test_name' => $test->test_name,
                        'for_class' => $test->for_class,
                        'section' => $test->section,
                        'subject' => $test->subject,
                    ],
                    'students' => $studentsData->values(),
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
                'message' => 'An error occurred while retrieving students: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Save Marks Entry
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function saveMarksEntry(Request $request): JsonResponse
    {
        try {
            $teacher = $request->user();
            
            if (!$teacher || !$teacher->isTeacher()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Only teachers can save marks entry.',
                ], 403);
            }

            $validated = $request->validate([
                'test_name' => ['required', 'string'],
                'campus' => ['required', 'string'],
                'class' => ['required', 'string'],
                'section' => ['nullable', 'string'],
                'subject' => ['nullable', 'string'],
                'marks' => ['required', 'array'],
                'marks.*.obtained' => ['nullable', 'numeric', 'min:0'],
                'marks.*.total' => ['nullable', 'numeric', 'min:0'],
                'marks.*.passing' => ['nullable', 'numeric', 'min:0'],
            ]);

            // Verify teacher has access to this class/section/subject
            $hasAccess = false;
            
            // Check if teacher has access via assigned subjects
            if ($validated['subject']) {
                $hasAccess = Subject::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($teacher->name ?? ''))])
                    ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($validated['class']))])
                    ->whereRaw('LOWER(TRIM(subject_name)) = ?', [strtolower(trim($validated['subject']))])
                    ->when($validated['section'], function ($query) use ($validated) {
                        $query->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($validated['section']))]);
                    })
                    ->exists();
            }
            
            // If no access via subjects, check via assigned sections
            if (!$hasAccess && $validated['section']) {
                $hasAccess = Section::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($teacher->name ?? ''))])
                    ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($validated['class']))])
                    ->whereRaw('LOWER(TRIM(name)) = ?', [strtolower(trim($validated['section']))])
                    ->exists();
            }
            
            // If still no access, check if teacher has any subject for this class
            if (!$hasAccess) {
                $hasAccess = Subject::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($teacher->name ?? ''))])
                    ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($validated['class']))])
                    ->when($validated['section'], function ($query) use ($validated) {
                        $query->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($validated['section']))]);
                    })
                    ->exists();
            }

            if (!$hasAccess) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. You do not have permission to save marks for this class/section/subject. Only your assigned classes/sections/subjects are accessible.',
                ], 403);
            }

            // Verify all students belong to the specified class/section
            $studentIds = array_keys($validated['marks']);
            $students = Student::whereIn('id', $studentIds)->get();
            
            foreach ($students as $student) {
                if (strtolower(trim($student->class ?? '')) !== strtolower(trim($validated['class']))) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Access denied. Student does not belong to the specified class.',
                    ], 403);
                }
                
                if ($validated['section'] && strtolower(trim($student->section ?? '')) !== strtolower(trim($validated['section']))) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Access denied. Student does not belong to the specified section.',
                    ], 403);
                }
            }

            // Create or update Test record if it doesn't exist
            $test = Test::where('test_name', $validated['test_name'])
                ->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($validated['campus']))])
                ->whereRaw('LOWER(TRIM(for_class)) = ?', [strtolower(trim($validated['class']))])
                ->when($validated['section'], function ($query) use ($validated) {
                    $query->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($validated['section']))]);
                })
                ->when($validated['subject'], function ($query) use ($validated) {
                    $query->whereRaw('LOWER(TRIM(subject)) = ?', [strtolower(trim($validated['subject']))]);
                })
                ->first();

            $testCreated = false;
            if (!$test) {
                // Get current session (default to current year)
                $currentYear = \Carbon\Carbon::now()->year;
                $nextYear = $currentYear + 1;
                $session = $currentYear . '-' . $nextYear;
                
                // Create new test record
                $test = Test::create([
                    'campus' => $validated['campus'],
                    'test_name' => $validated['test_name'],
                    'for_class' => $validated['class'],
                    'section' => $validated['section'] ?? '',
                    'subject' => $validated['subject'] ?? '',
                    'test_type' => 'Quiz', // Default test type
                    'description' => null,
                    'date' => \Carbon\Carbon::now()->toDateString(),
                    'session' => $session,
                    'result_status' => 1, // Declared by default when marks are entered
                ]);
                $testCreated = true;
            } else {
                // Update result_status to 1 (declared) if marks are being saved
                if ($test->result_status != 1) {
                    $test->update(['result_status' => 1]);
                }
            }

            // Save or update marks for each student
            $savedCount = 0;
            foreach ($validated['marks'] as $studentId => $markData) {
                if ($studentId) {
                    $student = Student::find($studentId);
                    if (!$student) {
                        continue; // Skip if student not found
                    }
                    
                    $campus = $student->campus ?? $validated['campus'];
                    
                    StudentMark::updateOrCreate(
                        [
                            'student_id' => $studentId,
                            'test_name' => $validated['test_name'],
                            'campus' => $campus,
                            'class' => $validated['class'],
                            'section' => $validated['section'] ?? null,
                            'subject' => $validated['subject'] ?? null,
                        ],
                        [
                            'marks_obtained' => $markData['obtained'] ?? null,
                            'total_marks' => $markData['total'] ?? null,
                            'passing_marks' => $markData['passing'] ?? null,
                        ]
                    );
                    $savedCount++;
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Marks saved successfully',
                'data' => [
                    'saved_count' => $savedCount,
                    'test_id' => $test->id,
                    'test_created' => $testCreated,
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
                'message' => 'An error occurred while saving marks: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get My Test - Teacher's Assigned Subjects
     * Returns list of subjects assigned to the logged-in teacher
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getMyTestSubjects(Request $request): JsonResponse
    {
        try {
            $teacher = $request->user();
            
            if (!$teacher || !$teacher->isTeacher()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Only teachers can access this endpoint.',
                ], 403);
            }

            // Get all subjects assigned to this teacher
            $subjects = Subject::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($teacher->name ?? ''))])
                ->whereNotNull('subject_name')
                ->whereNotNull('class')
                ->whereNotNull('section')
                ->orderBy('subject_name', 'asc')
                ->orderBy('class', 'asc')
                ->orderBy('section', 'asc')
                ->get();

            // Group by subject name and get unique subjects
            $uniqueSubjects = $subjects->groupBy(function($subject) {
                return strtolower(trim($subject->subject_name ?? ''));
            })->map(function($subjectGroup) {
                $firstSubject = $subjectGroup->first();
                return [
                    'subject_name' => $firstSubject->subject_name,
                    'campus' => $firstSubject->campus,
                    'total_classes' => $subjectGroup->count(),
                    'classes' => $subjectGroup->pluck('class')->unique()->sort()->values(),
                    'sections' => $subjectGroup->pluck('section')->unique()->sort()->values(),
                ];
            })->values();

            return response()->json([
                'success' => true,
                'message' => 'My test subjects retrieved successfully',
                'data' => [
                    'teacher' => [
                        'id' => $teacher->id,
                        'name' => $teacher->name,
                        'email' => $teacher->email,
                    ],
                    'subjects' => $uniqueSubjects,
                    'total_subjects' => $uniqueSubjects->count(),
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving my test subjects: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get Students for My Test - By Subject
     * Returns students list with marks for a specific subject assigned to teacher
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getMyTestStudents(Request $request): JsonResponse
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
                'subject' => ['required', 'string'],
                'class' => ['required', 'string'],
                'section' => ['required', 'string'],
                'test_name' => ['nullable', 'string'],
            ]);

            $subjectName = trim($request->get('subject'));
            $class = trim($request->get('class'));
            $section = trim($request->get('section'));
            $testName = $request->get('test_name');

            // Verify teacher has access to this subject/class/section
            $hasAccess = false;
            
            // Check if teacher has access via assigned subjects
            $hasAccess = Subject::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($teacher->name ?? ''))])
                ->whereRaw('LOWER(TRIM(subject_name)) = ?', [strtolower($subjectName)])
                ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower($class)])
                ->whereRaw('LOWER(TRIM(section)) = ?', [strtolower($section)])
                ->exists();
            
            // If no access via subjects, check via assigned sections
            if (!$hasAccess) {
                $hasAccess = Section::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($teacher->name ?? ''))])
                    ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower($class)])
                    ->whereRaw('LOWER(TRIM(name)) = ?', [strtolower($section)])
                    ->exists();
            }
            
            // If still no access, check if teacher has any subject for this class/section (more flexible)
            if (!$hasAccess) {
                $hasAccess = Subject::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($teacher->name ?? ''))])
                    ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower($class)])
                    ->whereRaw('LOWER(TRIM(section)) = ?', [strtolower($section)])
                    ->exists();
            }

            if (!$hasAccess) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have access to this subject/class/section. Only your assigned classes/sections/subjects are accessible.',
                    'debug' => [
                        'teacher_name' => $teacher->name,
                        'requested_subject' => $subjectName,
                        'requested_class' => $class,
                        'requested_section' => $section,
                    ],
                ], 403);
            }

            // Get subject details
            $subject = Subject::whereRaw('LOWER(TRIM(subject_name)) = ?', [strtolower($subjectName)])
                ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower($class)])
                ->whereRaw('LOWER(TRIM(section)) = ?', [strtolower($section)])
                ->first();

            if (!$subject) {
                return response()->json([
                    'success' => false,
                    'message' => 'Subject not found.',
                ], 404);
            }

            // Get students for this class and section
            $students = Student::whereRaw('LOWER(TRIM(class)) = ?', [strtolower($class)])
                ->whereRaw('LOWER(TRIM(section)) = ?', [strtolower($section)])
                ->when($subject->campus, function($query) use ($subject) {
                    $query->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($subject->campus ?? ''))]);
                })
                ->orderBy('student_name', 'asc')
                ->get();

            // Get existing marks if test_name is provided
            $existingMarks = collect();
            if ($testName) {
                $existingMarks = StudentMark::where('test_name', $testName)
                    ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower($class)])
                    ->whereRaw('LOWER(TRIM(section)) = ?', [strtolower($section)])
                    ->whereRaw('LOWER(TRIM(subject)) = ?', [strtolower($subjectName)])
                    ->get()
                    ->keyBy('student_id');
            }

            $studentsData = $students->map(function($student) use ($existingMarks) {
                $mark = $existingMarks->get($student->id);
                return [
                    'student_id' => $student->id,
                    'student_name' => $student->student_name,
                    'student_code' => $student->student_code ?? $student->gr_number ?? null,
                    'gr_number' => $student->gr_number ?? null,
                    'father_name' => $student->father_name ?? 'N/A',
                    'obtained_marks' => $mark && $mark->marks_obtained !== null ? (float) $mark->marks_obtained : null,
                    'minimum_marks' => $mark && $mark->passing_marks !== null ? (float) $mark->passing_marks : null,
                    'maximum_marks' => $mark && $mark->total_marks !== null ? (float) $mark->total_marks : null,
                    'teacher_remarks' => $mark ? ($mark->teacher_remarks ?? null) : null,
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Students retrieved successfully for my test',
                'data' => [
                    'subject' => [
                        'subject_name' => $subject->subject_name,
                        'class' => $class,
                        'section' => $section,
                        'campus' => $subject->campus,
                    ],
                    'test_name' => $testName,
                    'students' => $studentsData->values(),
                    'total_students' => $studentsData->count(),
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
                'message' => 'An error occurred while retrieving students: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get Test Details by ID
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function getTest(Request $request, $id): JsonResponse
    {
        try {
            $teacher = $request->user();
            
            if (!$teacher || !$teacher->isTeacher()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Only teachers can access this endpoint.',
                ], 403);
            }

            $test = Test::find($id);

            if (!$test) {
                return response()->json([
                    'success' => false,
                    'message' => 'Test not found.',
                ], 404);
            }

            // Verify teacher has access to this test
            $hasAccess = false;
            
            // Check if teacher has access via assigned subjects
            if ($test->subject) {
                $hasAccess = Subject::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($teacher->name ?? ''))])
                    ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($test->for_class ?? ''))])
                    ->whereRaw('LOWER(TRIM(subject_name)) = ?', [strtolower(trim($test->subject ?? ''))])
                    ->when($test->section, function ($query) use ($test) {
                        $query->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($test->section ?? ''))]);
                    })
                    ->exists();
            }
            
            // If no access via subjects, check via assigned sections
            if (!$hasAccess && $test->section) {
                $hasAccess = Section::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($teacher->name ?? ''))])
                    ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($test->for_class ?? ''))])
                    ->whereRaw('LOWER(TRIM(name)) = ?', [strtolower(trim($test->section ?? ''))])
                    ->exists();
            }
            
            // If still no access, check if teacher has any subject for this class
            if (!$hasAccess) {
                $hasAccess = Subject::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($teacher->name ?? ''))])
                    ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($test->for_class ?? ''))])
                    ->when($test->section, function ($query) use ($test) {
                        $query->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($test->section ?? ''))]);
                    })
                    ->exists();
            }

            if (!$hasAccess) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. You do not have permission to view this test.',
                ], 403);
            }

            return response()->json([
                'success' => true,
                'message' => 'Test retrieved successfully',
                'data' => [
                    'test' => [
                        'id' => $test->id,
                        'campus' => $test->campus,
                        'test_name' => $test->test_name,
                        'for_class' => $test->for_class,
                        'section' => $test->section,
                        'subject' => $test->subject,
                        'test_type' => $test->test_type,
                        'description' => $test->description,
                        'date' => $test->date ? $test->date->format('Y-m-d') : null,
                        'date_formatted' => $test->date ? $test->date->format('d M Y') : null,
                        'session' => $test->session,
                        'result_status' => $test->result_status ? true : false,
                        'created_at' => $test->created_at ? $test->created_at->format('Y-m-d H:i:s') : null,
                        'updated_at' => $test->updated_at ? $test->updated_at->format('Y-m-d H:i:s') : null,
                    ],
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving test: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Save Remarks Entry
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function saveRemarksEntry(Request $request): JsonResponse
    {
        try {
            $teacher = $request->user();
            
            if (!$teacher || !$teacher->isTeacher()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Only teachers can save remarks entry.',
                ], 403);
            }

            $validated = $request->validate([
                'test_name' => ['required', 'string'],
                'campus' => ['required', 'string'],
                'class' => ['required', 'string'],
                'section' => ['nullable', 'string'],
                'subject' => ['nullable', 'string'],
                'remarks' => ['required', 'array'],
                'remarks.*' => ['nullable', 'string'],
            ]);

            // Save or update remarks for each student
            $savedCount = 0;
            foreach ($validated['remarks'] as $studentId => $remark) {
                if ($studentId && $remark) {
                    $student = Student::find($studentId);
                    $campus = $student ? $student->campus : $validated['campus'];
                    
                    StudentMark::updateOrCreate(
                        [
                            'student_id' => $studentId,
                            'test_name' => $validated['test_name'],
                            'campus' => $campus,
                            'class' => $validated['class'],
                            'section' => $validated['section'] ?? null,
                            'subject' => $validated['subject'] ?? null,
                        ],
                        [
                            'teacher_remarks' => $remark,
                        ]
                    );
                    $savedCount++;
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Remarks saved successfully',
                'data' => [
                    'saved_count' => $savedCount,
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
                'message' => 'An error occurred while saving remarks: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get Test List - Returns all tests created by the current teacher
     * Tests are identified by matching test.subject with teacher's assigned subjects
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getTestList(Request $request): JsonResponse
    {
        try {
            $teacher = $request->user();
            
            if (!$teacher || !$teacher->isTeacher()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Only teachers can access test list.',
                    'token' => null,
                ], 403);
            }

            // Get teacher's assigned subjects
            $teacherSubjects = Subject::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($teacher->name ?? ''))])
                ->whereNotNull('subject_name')
                ->whereNotNull('class')
                ->get();

            if ($teacherSubjects->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'message' => 'No tests found. You have no assigned subjects.',
                    'data' => [
                        'teacher' => [
                            'id' => $teacher->id,
                            'name' => $teacher->name,
                            'email' => $teacher->email,
                        ],
                        'tests' => [],
                        'total' => 0,
                    ],
                ], 200);
            }

            // Get unique test names from StudentMark table where teacher has entered marks
            // This is the most reliable way - if teacher entered marks, they created/managed that test
            $teacherSubjectNames = $teacherSubjects->pluck('subject_name')->unique()->filter()->toArray();
            
            // Get all test names from StudentMark where subject matches teacher's subjects
            $teacherTestNames = [];
            if (!empty($teacherSubjectNames)) {
                $teacherTestNames = StudentMark::query()
                    ->whereIn('subject', $teacherSubjectNames)
                    ->distinct()
                    ->pluck('test_name')
                    ->filter()
                    ->unique()
                    ->toArray();
            }

            // Build query to get tests
            $testsQuery = Test::query();
            
            // Method 1: Get tests from StudentMark (where teacher entered marks) - Most reliable
            // Method 2: Match by subject and class from Subject table (section optional)
            $testsQuery->where(function($query) use ($teacherTestNames, $teacherSubjects, $teacherSubjectNames) {
                // Primary: Tests from StudentMark (where teacher entered marks)
                if (!empty($teacherTestNames)) {
                    $query->whereIn('test_name', $teacherTestNames);
                }
                
                // Fallback: Match by subject and class (section is completely optional)
                // Match any test where subject matches teacher's assigned subjects
                if (!empty($teacherSubjectNames)) {
                    $query->orWhereIn('subject', array_map(function($name) {
                        return strtolower(trim($name));
                    }, $teacherSubjectNames));
                }
                
                // Also match by subject + class combination
                if (!empty($teacherSubjects)) {
                    $query->orWhere(function($q) use ($teacherSubjects) {
                        foreach ($teacherSubjects as $subject) {
                            $q->orWhere(function($subQ) use ($subject) {
                                $subQ->whereRaw('LOWER(TRIM(subject)) = ?', [strtolower(trim($subject->subject_name ?? ''))])
                                     ->whereRaw('LOWER(TRIM(for_class)) = ?', [strtolower(trim($subject->class ?? ''))]);
                            });
                        }
                    });
                }
            });
            
            // Remove duplicates
            $testsQuery->distinct();

            // Optional filters
            if ($request->has('campus') && $request->get('campus')) {
                $testsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($request->get('campus')))]);
            }

            if ($request->has('class') && $request->get('class')) {
                $testsQuery->whereRaw('LOWER(TRIM(for_class)) = ?', [strtolower(trim($request->get('class')))]);
            }

            if ($request->has('section') && $request->get('section')) {
                $testsQuery->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($request->get('section')))]);
            }

            if ($request->has('subject') && $request->get('subject')) {
                $testsQuery->whereRaw('LOWER(TRIM(subject)) = ?', [strtolower(trim($request->get('subject')))]);
            }

            if ($request->has('test_type') && $request->get('test_type')) {
                $testsQuery->whereRaw('LOWER(TRIM(test_type)) = ?', [strtolower(trim($request->get('test_type')))]);
            }

            if ($request->has('session') && $request->get('session')) {
                $testsQuery->where('session', $request->get('session'));
            }

            // Order by date (newest first) or created_at
            $testsQuery->orderBy('date', 'desc')
                      ->orderBy('created_at', 'desc');

            // Get pagination parameters
            $perPage = $request->get('per_page', 30);
            $perPage = in_array($perPage, [10, 25, 30, 50, 100]) ? (int)$perPage : 30;
            
            $tests = $testsQuery->paginate($perPage);

            // Format tests data
            $testsData = $tests->map(function($test) use ($teacher) {
                // Get teacher name from Subject table for this test
                $testTeacher = Subject::whereRaw('LOWER(TRIM(subject_name)) = ?', [strtolower(trim($test->subject ?? ''))])
                    ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($test->for_class ?? ''))])
                    ->when($test->section, function($query) use ($test) {
                        $query->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($test->section ?? ''))]);
                    })
                    ->first();
                
                return [
                    'id' => $test->id,
                    'campus' => $test->campus,
                    'test_name' => $test->test_name,
                    'for_class' => $test->for_class,
                    'section' => $test->section,
                    'subject' => $test->subject,
                    'test_type' => $test->test_type,
                    'description' => $test->description,
                    'date' => $test->date ? $test->date->format('Y-m-d') : null,
                    'date_formatted' => $test->date ? $test->date->format('d M Y') : null,
                    'session' => $test->session,
                    'result_status' => $test->result_status ? true : false,
                    'teacher_name' => $testTeacher ? ($testTeacher->teacher ?? $teacher->name) : $teacher->name,
                    'created_at' => $test->created_at ? $test->created_at->format('Y-m-d H:i:s') : null,
                    'updated_at' => $test->updated_at ? $test->updated_at->format('Y-m-d H:i:s') : null,
                ];
            });

            // Debug info (only if no tests found)
            $debugInfo = null;
            if ($tests->total() == 0) {
                $debugInfo = [
                    'teacher_subjects_count' => $teacherSubjects->count(),
                    'teacher_subject_names' => $teacherSubjectNames,
                    'teacher_test_names_from_marks' => $teacherTestNames,
                    'total_tests_in_db' => Test::count(),
                ];
            }

            return response()->json([
                'success' => true,
                'message' => 'Test list retrieved successfully',
                'data' => [
                    'teacher' => [
                        'id' => $teacher->id,
                        'name' => $teacher->name,
                        'email' => $teacher->email,
                    ],
                    'tests' => $testsData,
                    'pagination' => [
                        'current_page' => $tests->currentPage(),
                        'last_page' => $tests->lastPage(),
                        'per_page' => $tests->perPage(),
                        'total' => $tests->total(),
                        'from' => $tests->firstItem(),
                        'to' => $tests->lastItem(),
                    ],
                    'debug' => $debugInfo,
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving test list: ' . $e->getMessage(),
                'token' => null,
            ], 500);
        }
    }

    /**
     * Get Assigned Subjects - Returns all subjects assigned to the current teacher
     * Detailed list with campus, class, section information
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getAssignedSubjects(Request $request): JsonResponse
    {
        try {
            $teacher = $request->user();
            
            if (!$teacher || !$teacher->isTeacher()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Only teachers can access assigned subjects.',
                    'token' => null,
                ], 403);
            }

            // Get all subjects assigned to this teacher
            $subjects = Subject::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($teacher->name ?? ''))])
                ->whereNotNull('subject_name')
                ->whereNotNull('class')
                ->orderBy('subject_name', 'asc')
                ->orderBy('class', 'asc')
                ->orderBy('section', 'asc')
                ->get();

            // Format subjects data
            $subjectsData = $subjects->map(function($subject) {
                return [
                    'id' => $subject->id,
                    'subject_name' => $subject->subject_name,
                    'campus' => $subject->campus ?? null,
                    'class' => $subject->class ?? null,
                    'section' => $subject->section ?? null,
                    'teacher' => $subject->teacher ?? null,
                    'created_at' => $subject->created_at ? $subject->created_at->format('Y-m-d H:i:s') : null,
                    'updated_at' => $subject->updated_at ? $subject->updated_at->format('Y-m-d H:i:s') : null,
                ];
            });

            // Get unique values for filters
            $uniqueCampuses = $subjects->whereNotNull('campus')->pluck('campus')->unique()->sort()->values();
            $uniqueClasses = $subjects->whereNotNull('class')->pluck('class')->unique()->sort()->values();
            $uniqueSections = $subjects->whereNotNull('section')->pluck('section')->unique()->sort()->values();
            $uniqueSubjectNames = $subjects->whereNotNull('subject_name')->pluck('subject_name')->unique()->sort()->values();

            return response()->json([
                'success' => true,
                'message' => 'Assigned subjects retrieved successfully',
                'data' => [
                    'teacher' => [
                        'id' => $teacher->id,
                        'name' => $teacher->name,
                        'email' => $teacher->email,
                    ],
                    'subjects' => $subjectsData,
                    'summary' => [
                        'total_subjects' => $subjectsData->count(),
                        'unique_subject_names' => $uniqueSubjectNames->count(),
                        'campuses' => $uniqueCampuses,
                        'classes' => $uniqueClasses,
                        'sections' => $uniqueSections,
                        'subject_names' => $uniqueSubjectNames,
                    ],
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving assigned subjects: ' . $e->getMessage(),
                'token' => null,
            ], 500);
        }
    }
}

