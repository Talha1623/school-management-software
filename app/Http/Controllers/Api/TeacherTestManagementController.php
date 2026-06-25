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
     * Resolve saved final/combined remark for a student in class/section context.
     */
    private function resolveFinalRemarkForStudent(
        Student $student,
        ?string $session,
        string $class,
        ?string $section,
        ?string $campus
    ): ?string {
        $baseRemarkQuery = function () use ($student, $session, $class, $section) {
            $query = StudentMark::query()
                ->where('student_id', $student->id)
                ->where(function ($sub) use ($session) {
                    $sub->whereRaw('LOWER(TRIM(test_name)) = ?', ['final_result'])
                        ->orWhereRaw('LOWER(TRIM(test_name)) = ?', ['combined_result'])
                        ->orWhereRaw('LOWER(TRIM(test_name)) LIKE ?', ['%final%']);

                    if (!empty($session)) {
                        $sub->orWhereRaw(
                            'LOWER(TRIM(test_name)) = ?',
                            [strtolower(trim($session . '_FINAL'))]
                        );
                    }
                })
                ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))]);

            if (!empty($section)) {
                $query->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($section))]);
            }

            return $query;
        };

        $finalRemarkRow = $baseRemarkQuery()
            ->when(!empty($campus), function ($query) use ($campus) {
                return $query->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
            })
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->first();

        if (!$finalRemarkRow && !empty($campus)) {
            $finalRemarkRow = $baseRemarkQuery()
                ->orderByDesc('updated_at')
                ->orderByDesc('id')
                ->first();
        }

        return $finalRemarkRow?->teacher_remarks;
    }

    /**
     * Get Subjects by Test Name for Marks/Remarks entry
     *
     * Params:
     * - class (required)
     * - section (required)
     * - test_name (required) [alias: test]
     *
     * Campus is auto-filtered from teacher token (if available).
     */
    public function getSubjectsByTestName(Request $request): JsonResponse
    {
        try {
            $teacher = $request->user();

            if (!$teacher || !$teacher->isTeacher()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Only teachers can access marks entry.',
                ], 403);
            }

            $validated = $request->validate([
                'class' => ['required', 'string'],
                'section' => ['required', 'string'],
                'test_name' => ['nullable', 'string'],
                'test' => ['nullable', 'string'],
            ]);

            $class = $validated['class'];
            $section = $validated['section'];
            $testName = $validated['test_name'] ?? ($validated['test'] ?? null);
            if (empty($testName)) {
                return response()->json([
                    'success' => false,
                    'message' => 'test_name (or test) is required.',
                ], 422);
            }

            $campus = $teacher->campus ?? null;

            $subjectsQuery = Test::query()
                ->whereNotNull('subject')
                ->whereRaw('LOWER(TRIM(test_name)) = ?', [strtolower(trim($testName))])
                ->whereRaw('LOWER(TRIM(for_class)) = ?', [strtolower(trim($class))])
                ->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($section))]);

            if (!empty($campus)) {
                $subjectsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
            }

            $subjects = $subjectsQuery
                ->distinct()
                ->pluck('subject')
                ->map(fn ($s) => trim((string) $s))
                ->filter(fn ($s) => $s !== '')
                ->unique()
                ->sort()
                ->values();

            return response()->json([
                'success' => true,
                'message' => 'Subjects retrieved successfully',
                'data' => [
                    'test_name' => $testName,
                    'class' => $class,
                    'section' => $section,
                    'campus' => $campus,
                    'subjects' => $subjects,
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

            // Campus is taken from authenticated teacher token (not from request)
            $campus = $teacher->campus ?? null;
            $class = $request->get('class');
            $section = $request->get('section');
            $subject = $request->get('subject');
            
            // Only show tests where result_status = 1 (declared)
            $testsQuery = Test::where('result_status', 1);
            
            if (!empty($campus)) {
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

            // Campus is taken from authenticated teacher token (not from request)
            $campus = $teacher->campus ?? null;
            $class = $request->get('class');
            $section = $request->get('section');
            $testName = $request->get('test_name') ?: $request->get('test');
            if (empty($testName)) {
                // Backward compatibility: some clients send exam_name for this endpoint.
                $testName = $request->get('exam_name');
            }
            
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

            $subjectsQuery = Subject::query()
                ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))]);

            if ($section) {
                $subjectsQuery->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($section))]);
            }
            if (!empty($campus)) {
                $subjectsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
            }

            $subjects = $subjectsQuery->whereNotNull('subject_name')
                ->distinct()
                ->pluck('subject_name')
                ->map(fn ($s) => trim((string) $s))
                ->filter(fn ($s) => $s !== '')
                ->sort()
                ->values();

            $allTestSubjectsQuery = Test::query()
                ->whereNotNull('subject')
                ->whereRaw('LOWER(TRIM(for_class)) = ?', [strtolower(trim((string) $class))]);

            if ($section) {
                $allTestSubjectsQuery->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim((string) $section))]);
            }
            if (!empty($campus)) {
                $allTestSubjectsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim((string) $campus))]);
            }

            $allTestSubjects = $allTestSubjectsQuery
                ->distinct()
                ->pluck('subject')
                ->map(fn ($s) => trim((string) $s))
                ->filter(fn ($s) => $s !== '')
                ->unique()
                ->values();

            // If test is selected, return all subjects that are part of that test,
            // not only the logged-in teacher's assigned subject.
            $selectedTestSubjects = collect();
            if (!empty($testName)) {
                $testSubjectsQuery = Test::query()
                    ->whereNotNull('subject')
                    ->whereRaw('LOWER(TRIM(test_name)) = ?', [strtolower(trim((string) $testName))])
                    ->whereRaw('LOWER(TRIM(for_class)) = ?', [strtolower(trim((string) $class))]);

                if ($section) {
                    $testSubjectsQuery->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim((string) $section))]);
                }
                if (!empty($campus)) {
                    $testSubjectsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim((string) $campus))]);
                }

                $selectedTestSubjects = $testSubjectsQuery
                    ->distinct()
                    ->pluck('subject')
                    ->map(fn ($s) => trim((string) $s))
                    ->filter(fn ($s) => $s !== '')
                    ->unique()
                    ->values();
            }

            $subjects = !empty($testName) && $selectedTestSubjects->isNotEmpty()
                ? $selectedTestSubjects->sort()->values()
                : $subjects->merge($allTestSubjects)->unique()->sort()->values();

            return response()->json([
                'success' => true,
                'message' => 'Subjects retrieved successfully',
                'data' => [
                    'test_name' => $testName,
                    'subjects' => $subjects,
                    'selected_test_subjects' => $selectedTestSubjects,
                    'all_test_subjects' => $allTestSubjects,
                    'subject_options' => $subjects->map(fn ($subject) => [
                        'subject' => $subject,
                        'subject_name' => $subject,
                        'name' => $subject,
                    ])->values(),
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
                'session' => ['nullable', 'string'],
                'class' => ['required', 'string'],
                'section' => ['required', 'string'],
                'subject' => ['nullable', 'string'],
                'test' => ['nullable', 'string'],
                'test_name' => ['nullable', 'string'],
                'exam_name' => ['nullable', 'string'],
                'campus' => ['nullable', 'string'],
            ]);

            // Default campus to teacher's campus to avoid cross-campus old/deleted data mixing
            $campus = $request->get('campus');
            if (empty($campus) && !empty($teacher->campus)) {
                $campus = $teacher->campus;
            }
            $session = $request->get('session');
            $class = $request->get('class');
            $section = $request->get('section');
            $subject = $request->get('subject');
            $testName = $request->get('test_name') ?: $request->get('test');
            if (empty($testName)) {
                $testName = $request->get('exam_name');
            }

            // New flexible mode:
            // If subject/test are not provided, allow class+section(+session) student list
            // and return aggregated obtained/total marks from available test rows.
            if (empty($subject) || empty($testName)) {
                $studentsQuery = Student::query()
                    ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))])
                    ->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($section))]);

                if (!empty($campus)) {
                    $studentsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
                }

                $students = $studentsQuery->orderBy('student_name')->get();

                $marksQuery = StudentMark::query()
                    ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))])
                    ->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($section))]);

                if (!empty($campus)) {
                    $marksQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
                }

                if (!empty($session)) {
                    $sessionTestNames = Test::query()
                        ->whereRaw('LOWER(TRIM(session)) = ?', [strtolower(trim($session))])
                        ->whereNotNull('test_name')
                        ->pluck('test_name')
                        ->map(fn($name) => strtolower(trim((string) $name)))
                        ->filter(fn($name) => !empty($name))
                        ->unique()
                        ->values()
                        ->toArray();

                    if (!empty($sessionTestNames)) {
                        $marksQuery->whereIn(\DB::raw('LOWER(TRIM(test_name))'), $sessionTestNames);
                    }
                }

                $marksByStudent = $marksQuery->get()->groupBy('student_id');

                $studentsData = $students->map(function ($student) use ($marksByStudent, $session, $class, $section, $campus) {
                    $studentMarks = $marksByStudent->get($student->id, collect());
                    $roll = $student->student_code ?? ($student->gr_number ?? null);
                    return [
                        'student_id' => $student->id,
                        'student_code' => $student->student_code ?? null,
                        'roll' => $roll,
                        'roll_number' => $roll,
                        'name' => $student->student_name,
                        'parent' => $student->father_name ?? null,
                        'final_remark' => $this->resolveFinalRemarkForStudent(
                            $student,
                            $session,
                            (string) $class,
                            $section ? (string) $section : null,
                            $campus ? (string) $campus : null
                        ),
                        'total' => (float) ($studentMarks->sum('total_marks') ?? 0),
                        'obtained' => (float) ($studentMarks->sum('marks_obtained') ?? 0),
                    ];
                });

                return response()->json([
                    'success' => true,
                    'message' => 'Students retrieved successfully',
                    'data' => [
                        'filters' => [
                            'campus' => $campus ?: null,
                            'session' => $session ?: null,
                            'class' => $class,
                            'section' => $section,
                        ],
                        'total_students' => $studentsData->count(),
                        'students' => $studentsData->values(),
                    ],
                ], 200);
            }

            // Verify subject currently exists for this class/section/campus (prevents showing deleted subjects)
            $subjectFound = Subject::whereRaw('LOWER(TRIM(subject_name)) = ?', [strtolower(trim($subject))])
                ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))])
                ->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($section))])
                ->when(!empty($campus), function ($q) use ($campus) {
                    return $q->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
                })
                ->first();

            if (!$subjectFound) {
                return response()->json([
                    'success' => true,
                    'message' => 'Subject not found for the given filters (possibly deleted).',
                    'data' => [
                        'test' => [
                            'mode' => 'subject_test',
                            'test_name' => $testName,
                            'subject' => $subject,
                            'class' => $class,
                            'section' => $section,
                            'campus' => $campus ?: null,
                        ],
                        'students' => [],
                    ],
                ], 200);
            }

            // Prefer campus-scoped test, but fallback to non-campus rows (legacy data).
            $baseTestQuery = Test::whereRaw('LOWER(TRIM(test_name)) = ?', [strtolower(trim($testName))])
                ->whereRaw('LOWER(TRIM(for_class)) = ?', [strtolower(trim($class))])
                ->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($section))])
                ->whereRaw('LOWER(TRIM(subject)) = ?', [strtolower(trim($subject))]);

            $test = (clone $baseTestQuery)
                ->when(!empty($campus), function ($q) use ($campus) {
                    return $q->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
                })
                ->orderByDesc('date')
                ->orderByDesc('id')
                ->first();

            if (!$test && !empty($campus)) {
                $test = (clone $baseTestQuery)
                    ->orderByDesc('date')
                    ->orderByDesc('id')
                    ->first();
            }

            if (!$test) {
                return response()->json([
                    'success' => false,
                    'message' => 'Test not found for the given class/section/subject.',
                ], 404);
            }

            // Query students based on filters
            $studentsQuery = Student::query();
            
            $studentsQuery->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))]);
            $studentsQuery->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($section))]);
            if (!empty($campus)) {
                $studentsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
            }
            
            $students = $studentsQuery->orderBy('student_name')->get();

            // Get existing marks for these students
            $existingMarksQuery = StudentMark::whereRaw('LOWER(TRIM(test_name)) = ?', [strtolower(trim($testName))])
                ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))])
                ->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($section))])
                ->whereRaw('LOWER(TRIM(subject)) = ?', [strtolower(trim($subject))])
                ->when(!empty($campus), function ($q) use ($campus) {
                    return $q->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
                });

            $existingMarks = (clone $existingMarksQuery)->get()->keyBy('student_id');
            if ($existingMarks->isEmpty() && !empty($campus)) {
                $existingMarks = StudentMark::whereRaw('LOWER(TRIM(test_name)) = ?', [strtolower(trim($testName))])
                    ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))])
                    ->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($section))])
                    ->whereRaw('LOWER(TRIM(subject)) = ?', [strtolower(trim($subject))])
                    ->get()
                    ->keyBy('student_id');
            }

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
                        'remark' => $mark->teacher_remarks,
                        'teacher_remarks' => $mark->teacher_remarks,
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
                        'campus' => $test->campus,
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
     * Get uploaded marks with full student detail for a test.
     *
     * Required query params:
     * - class
     * - section
     * - test_name (alias: test)
     *
     * Optional:
     * - subject
     * - campus (defaults to teacher campus)
     */
    public function getUploadedTestMarksDetail(Request $request): JsonResponse
    {
        try {
            $teacher = $request->user();

            if (!$teacher || !$teacher->isTeacher()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Only teachers can access marks entry.',
                ], 403);
            }

            $validated = $request->validate([
                'class' => ['required', 'string'],
                'section' => ['required', 'string'],
                'test_name' => ['nullable', 'string'],
                'test' => ['nullable', 'string'],
                'subject' => ['nullable', 'string'],
                'campus' => ['nullable', 'string'],
            ]);

            $class = trim((string) $validated['class']);
            $section = trim((string) $validated['section']);
            $testName = trim((string) ($validated['test_name'] ?? ($validated['test'] ?? '')));
            $subject = trim((string) ($validated['subject'] ?? ''));
            $campus = trim((string) ($validated['campus'] ?? ''));

            if ($testName === '') {
                return response()->json([
                    'success' => false,
                    'message' => 'test_name (or test) is required.',
                ], 422);
            }

            if ($campus === '' && !empty($teacher->campus)) {
                $campus = trim((string) $teacher->campus);
            }

            $testStatusQuery = Test::query()
                ->whereRaw('LOWER(TRIM(test_name)) = ?', [strtolower($testName)])
                ->whereRaw('LOWER(TRIM(for_class)) = ?', [strtolower($class)])
                ->whereRaw('LOWER(TRIM(section)) = ?', [strtolower($section)]);

            if ($subject !== '') {
                $testStatusQuery->whereRaw('LOWER(TRIM(subject)) = ?', [strtolower($subject)]);
            }
            if ($campus !== '') {
                $testStatusQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower($campus)]);
            }

            $matchingTests = $testStatusQuery->get();
            $isDeclared = $matchingTests->contains(fn ($test) => (int) ($test->result_status ?? 0) === 1);

            // Verify teacher access for requested class/section (subject teacher OR class teacher).
            $identityKeys = collect([
                strtolower(trim((string) ($teacher->name ?? ''))),
                strtolower(trim((string) ($teacher->emp_id ?? ''))),
            ])->filter()->unique()->values();

            $isClassTeacher = Section::query()
                ->where(function ($query) use ($identityKeys) {
                    foreach ($identityKeys as $key) {
                        $query->orWhereRaw('LOWER(TRIM(teacher)) = ?', [$key]);
                    }
                })
                ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower($class)])
                ->whereRaw('LOWER(TRIM(name)) = ?', [strtolower($section)])
                ->when($campus !== '', function ($q) use ($campus) {
                    return $q->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower($campus)]);
                })
                ->exists();

            $hasAccess = Subject::query()
                ->where(function ($query) use ($identityKeys) {
                    foreach ($identityKeys as $key) {
                        $query->orWhereRaw('LOWER(TRIM(teacher)) = ?', [$key]);
                    }
                })
                ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower($class)])
                ->whereRaw('LOWER(TRIM(section)) = ?', [strtolower($section)])
                ->when($subject !== '', function ($q) use ($subject) {
                    return $q->whereRaw('LOWER(TRIM(subject_name)) = ?', [strtolower($subject)]);
                })
                ->when($campus !== '', function ($q) use ($campus) {
                    return $q->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower($campus)]);
                })
                ->exists() || $isClassTeacher;

            if (!$hasAccess) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. You do not have permission for this class/section.',
                ], 403);
            }

            $studentsQuery = Student::query()
                ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower($class)])
                ->whereRaw('LOWER(TRIM(section)) = ?', [strtolower($section)]);

            if ($campus !== '') {
                $studentsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower($campus)]);
            }

            $students = $studentsQuery
                ->orderBy('student_code', 'asc')
                ->orderBy('student_name', 'asc')
                ->get();

            if ($students->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'message' => 'No students found for given class/section.',
                    'data' => [
                        'filters' => [
                            'campus' => $campus !== '' ? $campus : null,
                            'class' => $class,
                            'section' => $section,
                            'test_name' => $testName,
                            'subject' => $subject !== '' ? $subject : null,
                        ],
                        'students' => [],
                        'total_students' => 0,
                        'students_with_uploaded_marks' => 0,
                    ],
                ], 200);
            }

            $studentIds = $students->pluck('id')->values()->all();

            $marksQuery = StudentMark::query()
                ->whereRaw('LOWER(TRIM(test_name)) = ?', [strtolower($testName)])
                ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower($class)])
                ->whereRaw('LOWER(TRIM(section)) = ?', [strtolower($section)])
                ->whereIn('student_id', $studentIds);

            if ($subject !== '') {
                $marksQuery->whereRaw('LOWER(TRIM(subject)) = ?', [strtolower($subject)]);
            }

            if ($campus !== '') {
                $marksQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower($campus)]);
            }

            $uploadedMarks = $marksQuery
                ->get()
                ->groupBy('student_id')
                ->map(function ($rows) {
                    return $rows
                        ->sortByDesc(function ($row) {
                            return strtotime((string) ($row->updated_at ?? $row->created_at ?? '1970-01-01 00:00:00'));
                        })
                        ->first();
                });

            $studentsData = $students->map(function ($student) use ($uploadedMarks) {
                $mark = $uploadedMarks->get($student->id);
                $obtained = $mark->marks_obtained ?? null;
                $passing = $mark->passing_marks ?? null;

                return [
                    'student_id' => $student->id,
                    'student_code' => $student->student_code ?? null,
                    'gr_number' => $student->gr_number ?? null,
                    'student_name' => $student->student_name,
                    'father_name' => $student->father_name ?? null,
                    'class' => $student->class,
                    'section' => $student->section,
                    'campus' => $student->campus,
                    'gender' => $student->gender ?? null,
                    'photo' => $student->photo,
                    'marks_uploaded' => $mark !== null,
                    'marks' => $mark ? [
                        'obtained' => $mark->marks_obtained,
                        'total' => $mark->total_marks,
                        'passing' => $mark->passing_marks,
                        'teacher_remarks' => $mark->teacher_remarks,
                        'subject' => $mark->subject,
                        'test_name' => $mark->test_name,
                        'uploaded_at' => $mark->updated_at ? $mark->updated_at->format('Y-m-d H:i:s') : ($mark->created_at ? $mark->created_at->format('Y-m-d H:i:s') : null),
                        'is_passed' => ($obtained !== null && $passing !== null && $passing !== '')
                            ? ((float) $obtained >= (float) $passing)
                            : null,
                    ] : null,
                ];
            })->values();

            return response()->json([
                'success' => true,
                'message' => 'Uploaded test marks retrieved successfully',
                'data' => [
                    'teacher' => [
                        'id' => $teacher->id,
                        'name' => $teacher->name,
                        'emp_id' => $teacher->emp_id ?? null,
                        'is_class_teacher' => $isClassTeacher,
                    ],
                    'filters' => [
                        'campus' => $campus !== '' ? $campus : null,
                        'class' => $class,
                        'section' => $section,
                        'test_name' => $testName,
                        'subject' => $subject !== '' ? $subject : null,
                    ],
                    'students' => $studentsData,
                    'total_students' => $studentsData->count(),
                    'students_with_uploaded_marks' => $studentsData->where('marks_uploaded', true)->count(),
                    'test_status' => [
                        'is_declared' => $isDeclared,
                        'can_upload_marks' => !$isDeclared,
                        'message' => $isDeclared
                            ? 'This test result has been declared from web. Marks upload/update is locked.'
                            : 'Marks can be uploaded or updated.',
                    ],
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
                'message' => 'An error occurred while retrieving uploaded test marks: ' . $e->getMessage(),
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
                'marks.*.remark' => ['nullable', 'string', 'max:1000'],
                'marks.*.teacher_remarks' => ['nullable', 'string', 'max:1000'],
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

            $declaredTestExists = Test::query()
                ->whereRaw('LOWER(TRIM(test_name)) = ?', [strtolower(trim($validated['test_name']))])
                ->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($validated['campus']))])
                ->whereRaw('LOWER(TRIM(for_class)) = ?', [strtolower(trim($validated['class']))])
                ->when($validated['section'], function ($query) use ($validated) {
                    $query->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($validated['section']))]);
                })
                ->when($validated['subject'], function ($query) use ($validated) {
                    $query->whereRaw('LOWER(TRIM(subject)) = ?', [strtolower(trim($validated['subject']))]);
                })
                ->where('result_status', 1)
                ->exists();

            if ($declaredTestExists) {
                return response()->json([
                    'success' => false,
                    'message' => 'This test result has been declared from web. Marks upload/update is locked.',
                    'data' => [
                        'test_name' => $validated['test_name'],
                        'class' => $validated['class'],
                        'section' => $validated['section'] ?? null,
                        'subject' => $validated['subject'] ?? null,
                        'is_declared' => true,
                        'can_upload_marks' => false,
                    ],
                ], 409);
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
                    // Keep pending by default; web "declare result" should control this.
                    'result_status' => 0,
                ]);
                $testCreated = true;
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

                    // Prepare upsert values
                    $upsertValues = [
                        'marks_obtained' => $markData['obtained'] ?? null,
                        'total_marks' => $markData['total'] ?? null,
                        'passing_marks' => $markData['passing'] ?? null,
                    ];
                    if (array_key_exists('remark', $markData) && $markData['remark'] !== null) {
                        $upsertValues['teacher_remarks'] = $markData['remark'];
                    } elseif (array_key_exists('teacher_remarks', $markData) && $markData['teacher_remarks'] !== null) {
                        $upsertValues['teacher_remarks'] = $markData['teacher_remarks'];
                    }

                    StudentMark::updateOrCreate(
                        [
                            'student_id' => $studentId,
                            'test_name' => $validated['test_name'],
                            'campus' => $campus,
                            'class' => $validated['class'],
                            'section' => $validated['section'] ?? null,
                            'subject' => $validated['subject'] ?? null,
                        ],
                        $upsertValues
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
            'test_name' => ['nullable', 'string'],
            'test' => ['nullable', 'string'],
            'class' => ['nullable', 'string'],
            'section' => ['nullable', 'string'],
            'subject' => ['nullable', 'string'],
                // Accept DB id OR student_code OR gr_number
                'student_id' => ['nullable'],
                'remark' => ['nullable', 'string'],
                'remarks' => ['nullable', 'array'],
                'remarks.*' => ['nullable', 'string'],
            ]);

            $testName = $validated['test_name'] ?? ($validated['test'] ?? null);
            $isCombinedFlow = empty($testName); // When no test provided, treat as combined result remarks

            // Resolve class/section from request or test record (fallback)
            $testRecord = null;
            if (!$isCombinedFlow) {
                $testRecord = Test::whereRaw('LOWER(TRIM(test_name)) = ?', [strtolower(trim($testName))])
                    ->when(!empty($validated['subject']), function ($q) use ($validated) {
                        return $q->whereRaw('LOWER(TRIM(subject)) = ?', [strtolower(trim($validated['subject']))]);
                    })
                    ->first();
            }

            $className = $validated['class'] ?? ($testRecord->for_class ?? null);
            $sectionName = $validated['section'] ?? ($testRecord->section ?? null);

            if (empty($className)) {
                return response()->json([
                    'success' => false,
                    'message' => 'class is required (or must exist on matching test record).',
                ], 422);
            }

            // Support both single and bulk payloads
            if (!empty($validated['student_id'])) {
                if (!array_key_exists('remark', $validated)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'remark is required when student_id is provided.',
                    ], 422);
                }
                $remarksPayload = [(string) $validated['student_id'] => $validated['remark']];
            } else {
                $remarksPayload = $validated['remarks'] ?? [];
                if (empty($remarksPayload)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Please provide either student_id + remark OR remarks object.',
                    ], 422);
                }
            }

            $resolveStudent = function ($studentIdentifier) {
                $value = trim((string) $studentIdentifier);
                if ($value === '') {
                    return null;
                }

                if (ctype_digit($value)) {
                    $byId = Student::find((int) $value);
                    if ($byId) {
                        return $byId;
                    }
                }

                return Student::where('student_code', $value)
                    ->orWhere('gr_number', $value)
                    ->first();
            };

            $savedCount = 0;
            $errors = [];
            foreach ($remarksPayload as $studentId => $remark) {
                if (empty($studentId)) {
                    continue;
                }

                $student = $resolveStudent($studentId);
                if (!$student) {
                    $errors[] = "Student with ID {$studentId} not found.";
                    continue;
                }

                if (strtolower(trim((string) ($student->class ?? ''))) !== strtolower(trim((string) $className))) {
                    $errors[] = "Student {$student->student_name} (ID: {$studentId}) does not belong to class {$className}.";
                    continue;
                }

                if (!empty($sectionName) && strtolower(trim((string) ($student->section ?? ''))) !== strtolower(trim((string) $sectionName))) {
                    $errors[] = "Student {$student->student_name} (ID: {$studentId}) does not belong to section {$sectionName}.";
                    continue;
                }

                $campus = $student->campus ?? ($teacher->campus ?? null);

                StudentMark::updateOrCreate(
                    [
                        'student_id' => $student->id,
                        // For combined flow, persist under COMBINED_RESULT (subject = null)
                        'test_name' => $isCombinedFlow ? 'COMBINED_RESULT' : $testName,
                        'campus' => $campus,
                        'class' => $className,
                        'section' => $sectionName,
                        'subject' => $isCombinedFlow ? null : ($validated['subject'] ?? null),
                    ],
                    [
                        'teacher_remarks' => $remark,
                    ]
                );
                $savedCount++;
            }

            return response()->json([
                'success' => true,
                'message' => 'Remarks saved successfully',
                'data' => [
                    'saved_count' => $savedCount,
                    'test_name' => $isCombinedFlow ? 'COMBINED_RESULT' : $testName,
                    'class' => $className,
                    'section' => $sectionName,
                    'subject' => $isCombinedFlow ? null : ($validated['subject'] ?? null),
                    'errors' => !empty($errors) ? $errors : null,
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

            $teacherName = strtolower(trim((string) ($teacher->name ?? '')));
            $requestedClass = trim((string) $request->get('class', ''));
            $requestedSection = trim((string) $request->get('section', ''));
            $requestedCampus = trim((string) $request->get('campus', ''));
            $effectiveCampus = $requestedCampus !== '' ? $requestedCampus : (string) ($teacher->campus ?? '');

            // Get teacher's assigned subjects (strictly scoped to requested filters)
            $teacherSubjects = Subject::whereRaw('LOWER(TRIM(teacher)) = ?', [$teacherName])
                ->whereNotNull('subject_name')
                ->whereNotNull('class')
                ->when($requestedClass !== '', function($q) use ($requestedClass) {
                    $q->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($requestedClass))]);
                })
                ->when($requestedSection !== '', function($q) use ($requestedSection) {
                    $q->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($requestedSection))]);
                })
                ->when($effectiveCampus !== '', function($q) use ($effectiveCampus) {
                    $q->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($effectiveCampus))]);
                })
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
                $marksQuery = StudentMark::query()
                    ->whereIn('subject', $teacherSubjectNames)
                    ->when($requestedClass !== '', function($q) use ($requestedClass) {
                        $q->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($requestedClass))]);
                    })
                    ->when($requestedSection !== '', function($q) use ($requestedSection) {
                        $q->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($requestedSection))]);
                    })
                    ->when($effectiveCampus !== '', function($q) use ($effectiveCampus) {
                        $q->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($effectiveCampus))]);
                    });

                $teacherTestNames = $marksQuery
                    ->distinct()
                    ->pluck('test_name')
                    ->filter()
                    ->unique()
                    ->toArray();
            }

            // Build query to get tests
            $testsQuery = Test::query();

            // STRICT teacher scope:
            // only tests that match the teacher's assigned subject+class+section(+campus) combinations.
            if ($teacherSubjects->isNotEmpty()) {
                $testsQuery->where(function ($q) use ($teacherSubjects) {
                    foreach ($teacherSubjects as $subject) {
                        $q->orWhere(function ($subQ) use ($subject) {
                            $subQ->whereRaw('LOWER(TRIM(subject)) = ?', [strtolower(trim($subject->subject_name ?? ''))])
                                ->whereRaw('LOWER(TRIM(for_class)) = ?', [strtolower(trim($subject->class ?? ''))])
                                ->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($subject->section ?? ''))]);

                            if (!empty($subject->campus)) {
                                $subQ->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($subject->campus ?? ''))]);
                            }
                        });
                    }
                });
            }
            
            // Remove duplicates
            $testsQuery->distinct();

            // Optional filters
            // Campus must always be scoped to teacher campus (or requested campus if provided)
            if (!empty($effectiveCampus)) {
                $testsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($effectiveCampus))]);
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

            // Fallback: if no Test rows match but teacher has uploaded marks,
            // build list from StudentMark so uploaded tests still appear.
            $marksFallbackRows = collect();
            if ($tests->total() == 0 && !empty($teacherSubjectNames)) {
                // Only include marks for tests that still exist in Test table.
                // This prevents deleted tests from reappearing via StudentMark fallback.
                $activeTestNameKeys = Test::query()
                    ->when($requestedClass !== '', function($q) use ($requestedClass) {
                        $q->whereRaw('LOWER(TRIM(for_class)) = ?', [strtolower(trim($requestedClass))]);
                    })
                    ->when($requestedSection !== '', function($q) use ($requestedSection) {
                        $q->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($requestedSection))]);
                    })
                    ->when($effectiveCampus !== '', function($q) use ($effectiveCampus) {
                        $q->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($effectiveCampus))]);
                    })
                    ->whereNotNull('test_name')
                    ->pluck('test_name')
                    ->map(fn($name) => strtolower(trim((string) $name)))
                    ->filter(fn($name) => $name !== '')
                    ->unique()
                    ->values()
                    ->toArray();

                if (empty($activeTestNameKeys)) {
                    $marksFallbackRows = collect();
                } else {
                $marksFallbackQuery = StudentMark::query()
                    ->whereNotNull('test_name')
                    ->whereIn(\DB::raw('LOWER(TRIM(test_name))'), $activeTestNameKeys)
                    ->when(!empty($teacherSubjectNames), function ($q) use ($teacherSubjectNames) {
                        $q->where(function ($sq) use ($teacherSubjectNames) {
                            foreach ($teacherSubjectNames as $subjectName) {
                                $sq->orWhereRaw('LOWER(TRIM(subject)) = ?', [strtolower(trim((string) $subjectName))]);
                            }
                        });
                    })
                    ->when($requestedClass !== '', function($q) use ($requestedClass) {
                        $q->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($requestedClass))]);
                    })
                    ->when($requestedSection !== '', function($q) use ($requestedSection) {
                        $q->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($requestedSection))]);
                    })
                    ->when($effectiveCampus !== '', function($q) use ($effectiveCampus) {
                        $q->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($effectiveCampus))]);
                    });

                $marksFallbackRows = $marksFallbackQuery
                    ->select([
                        \DB::raw('MAX(id) as id'),
                        \DB::raw('MAX(campus) as campus'),
                        \DB::raw('MAX(test_name) as test_name'),
                        \DB::raw('MAX(class) as for_class'),
                        \DB::raw('MAX(section) as section'),
                        \DB::raw('MAX(subject) as subject'),
                        \DB::raw('MAX(created_at) as created_at'),
                        \DB::raw('MAX(updated_at) as updated_at'),
                    ])
                    ->groupBy(\DB::raw('LOWER(TRIM(test_name))'), \DB::raw('LOWER(TRIM(class))'), \DB::raw('LOWER(TRIM(section))'), \DB::raw('LOWER(TRIM(subject))'))
                    ->orderByDesc('created_at')
                    ->get();
                }
            }

            // Format tests data
            $testsData = $tests->map(function($test) use ($teacher) {
                // Get teacher name from Subject table for this test
                $testTeacher = Subject::whereRaw('LOWER(TRIM(subject_name)) = ?', [strtolower(trim($test->subject ?? ''))])
                    ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($test->for_class ?? ''))])
                    ->whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($teacher->name ?? ''))])
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

            if ($tests->total() == 0 && $marksFallbackRows->isNotEmpty()) {
                $testsData = $marksFallbackRows->map(function ($test) use ($teacher) {
                    return [
                        'id' => (int) ($test->id ?? 0),
                        'campus' => $test->campus,
                        'test_name' => $test->test_name,
                        'for_class' => $test->for_class,
                        'section' => $test->section,
                        'subject' => $test->subject,
                        'test_type' => null,
                        'description' => null,
                        'date' => null,
                        'date_formatted' => null,
                        'session' => null,
                        'result_status' => false,
                        'teacher_name' => $teacher->name,
                        'created_at' => $test->created_at ? \Carbon\Carbon::parse($test->created_at)->format('Y-m-d H:i:s') : null,
                        'updated_at' => $test->updated_at ? \Carbon\Carbon::parse($test->updated_at)->format('Y-m-d H:i:s') : null,
                    ];
                })->values();
            }

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
                    'pagination' => $tests->total() > 0 ? [
                        'current_page' => $tests->currentPage(),
                        'last_page' => $tests->lastPage(),
                        'per_page' => $tests->perPage(),
                        'total' => $tests->total(),
                        'from' => $tests->firstItem(),
                        'to' => $tests->lastItem(),
                    ] : [
                        'current_page' => 1,
                        'last_page' => 1,
                        'per_page' => $perPage,
                        'total' => $testsData->count(),
                        'from' => $testsData->isNotEmpty() ? 1 : null,
                        'to' => $testsData->isNotEmpty() ? $testsData->count() : null,
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
     * Get all tests for a class teacher's assigned class/sections.
     *
     * Query params:
     * - class (required)
     * - section (optional): if omitted, all sections where this teacher is class teacher are included
     * - campus, test_type, session, per_page (optional)
     */
    public function getClassTeacherTestList(Request $request): JsonResponse
    {
        try {
            $teacher = $request->user();

            if (!$teacher || !$teacher->isTeacher()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Only teachers can access class teacher test list.',
                    'token' => null,
                ], 403);
            }

            $validated = $request->validate([
                'class' => ['required', 'string', 'max:255'],
                'section' => ['nullable', 'string', 'max:255'],
                'campus' => ['nullable', 'string', 'max:255'],
                'test_type' => ['nullable', 'string', 'max:255'],
                'session' => ['nullable', 'string', 'max:255'],
                'per_page' => ['nullable', 'integer'],
            ]);

            $className = trim((string) $validated['class']);
            $sectionName = isset($validated['section']) ? trim((string) $validated['section']) : '';
            $requestedCampus = isset($validated['campus']) ? trim((string) $validated['campus']) : '';
            $effectiveCampus = $requestedCampus !== '' ? $requestedCampus : trim((string) ($teacher->campus ?? ''));

            $identityKeys = collect([
                strtolower(trim((string) ($teacher->name ?? ''))),
                strtolower(trim((string) ($teacher->emp_id ?? ''))),
            ])->filter()->unique()->values();

            $classTeacherSectionsQuery = Section::query()
                ->where(function ($query) use ($identityKeys) {
                    foreach ($identityKeys as $key) {
                        $query->orWhereRaw('LOWER(TRIM(teacher)) = ?', [$key]);
                    }
                })
                ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower($className)]);

            if ($sectionName !== '') {
                $classTeacherSectionsQuery->whereRaw('LOWER(TRIM(name)) = ?', [strtolower($sectionName)]);
            }
            if ($effectiveCampus !== '') {
                $classTeacherSectionsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower($effectiveCampus)]);
            }

            $classTeacherSections = $classTeacherSectionsQuery
                ->pluck('name')
                ->map(fn ($section) => trim((string) $section))
                ->filter(fn ($section) => $section !== '')
                ->unique()
                ->values();

            if ($classTeacherSections->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'message' => 'No tests found. This teacher is not class teacher for selected class/section.',
                    'data' => [
                        'teacher' => [
                            'id' => $teacher->id,
                            'name' => $teacher->name,
                            'email' => $teacher->email,
                        ],
                        'class' => $className,
                        'section' => $sectionName !== '' ? $sectionName : null,
                        'class_section' => $sectionName !== '' ? "{$className} / {$sectionName}" : null,
                        'class_sections' => [],
                        'sections' => [],
                        'tests' => [],
                        'total' => 0,
                    ],
                    'token' => $request->user()->currentAccessToken()->token ?? null,
                ], 200);
            }

            $testsQuery = Test::query()
                ->whereRaw('LOWER(TRIM(for_class)) = ?', [strtolower($className)])
                ->where(function ($q) use ($classTeacherSections) {
                    foreach ($classTeacherSections as $section) {
                        $q->orWhereRaw('LOWER(TRIM(section)) = ?', [strtolower($section)]);
                    }
                });

            if ($effectiveCampus !== '') {
                $testsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower($effectiveCampus)]);
            }
            if (!empty($validated['test_type'])) {
                $testsQuery->whereRaw('LOWER(TRIM(test_type)) = ?', [strtolower(trim((string) $validated['test_type']))]);
            }
            if (!empty($validated['session'])) {
                $testsQuery->where('session', $validated['session']);
            }

            $testsQuery->orderBy('date', 'desc')
                ->orderBy('created_at', 'desc');

            $perPage = (int) ($validated['per_page'] ?? 30);
            $perPage = in_array($perPage, [10, 25, 30, 50, 100], true) ? $perPage : 30;
            $tests = $testsQuery->paginate($perPage);

            $testsData = $tests->map(function ($test) use ($teacher) {
                return [
                    'id' => $test->id,
                    'campus' => $test->campus,
                    'test_name' => $test->test_name,
                    'for_class' => $test->for_class,
                    'section' => $test->section,
                    'class_section' => trim((string) $test->for_class) . ($test->section ? ' / ' . trim((string) $test->section) : ''),
                    'subject' => $test->subject,
                    'test_type' => $test->test_type,
                    'description' => $test->description,
                    'date' => $test->date ? $test->date->format('Y-m-d') : null,
                    'date_formatted' => $test->date ? $test->date->format('d M Y') : null,
                    'session' => $test->session,
                    'result_status' => (bool) $test->result_status,
                    'teacher_name' => $teacher->name,
                    'created_at' => $test->created_at ? $test->created_at->format('Y-m-d H:i:s') : null,
                    'updated_at' => $test->updated_at ? $test->updated_at->format('Y-m-d H:i:s') : null,
                ];
            })->values();

            return response()->json([
                'success' => true,
                'message' => 'Class teacher test list retrieved successfully',
                'data' => [
                    'teacher' => [
                        'id' => $teacher->id,
                        'name' => $teacher->name,
                        'email' => $teacher->email,
                    ],
                    'class' => $className,
                    'section' => $sectionName !== '' ? $sectionName : null,
                    'class_section' => $sectionName !== '' ? "{$className} / {$sectionName}" : null,
                    'class_sections' => $classTeacherSections->map(fn ($section) => "{$className} / {$section}")->values(),
                    'sections' => $classTeacherSections,
                    'tests' => $testsData,
                    'pagination' => [
                        'current_page' => $tests->currentPage(),
                        'last_page' => $tests->lastPage(),
                        'per_page' => $tests->perPage(),
                        'total' => $tests->total(),
                        'from' => $tests->firstItem(),
                        'to' => $tests->lastItem(),
                    ],
                ],
                'token' => $request->user()->currentAccessToken()->token ?? null,
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
                'token' => null,
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving class teacher test list: ' . $e->getMessage(),
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

