<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentAttendance;
use App\Models\ClassModel;
use App\Models\Exam;
use App\Models\ExamTimetable;
use App\Models\Test;
use App\Models\StudentMark;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;

class TeacherController extends Controller
{
    /**
     * Get Teacher Dashboard Stats
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function dashboard(Request $request): JsonResponse
    {
        try {
            $teacher = $request->user();

            if (!$teacher || !$teacher->isTeacher()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Only teachers can access dashboard.',
                    'token' => null,
                ], 403);
            }

            // Step 1: Get teacher's assigned subjects
            $assignedSubjects = Subject::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($teacher->name ?? ''))])
                ->get();

            // Get teacher's assigned sections
            $assignedSections = Section::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($teacher->name ?? ''))])
                ->get();

            // Step 2: Get unique classes from both assigned subjects and sections
            $assignedClasses = $assignedSubjects->pluck('class')
                ->merge($assignedSections->pluck('class'))
                ->map(function($class) {
                    return trim($class);
                })
                ->filter(function($class) {
                    return !empty($class);
                })
                ->unique()
                ->values();

            // Step 3: Get students from assigned classes
            $studentsQuery = Student::query();

            // Filter by teacher's campus
            if ($teacher->campus) {
                $studentsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($teacher->campus))]);
            }

            // Filter by assigned classes ONLY
            if ($assignedClasses->isNotEmpty()) {
                $studentsQuery->where(function($q) use ($assignedClasses) {
                    foreach ($assignedClasses as $class) {
                        $q->orWhereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))]);
                    }
                });
            } else {
                // If no classes assigned, return empty result
                $studentsQuery->whereRaw('1 = 0');
            }

            $allStudents = $studentsQuery->get();

            // Step 4: Calculate statistics
            $totalStudents = $allStudents->count();

            // Count boys and girls
            $boys = $allStudents->filter(function($student) {
                $gender = strtolower($student->gender ?? '');
                return $gender === 'male' || $gender === 'm';
            })->count();

            $girls = $allStudents->filter(function($student) {
                $gender = strtolower($student->gender ?? '');
                return $gender === 'female' || $gender === 'f';
            })->count();

            // Step 5: Calculate today's attendance
            $attendancePercentage = 0;
            $presentToday = 0;
            $absentToday = 0;

            if ($totalStudents > 0) {
                $today = Carbon::today()->format('Y-m-d');
                $studentIds = $allStudents->pluck('id');

                // Get today's attendance
                $todayAttendance = StudentAttendance::whereIn('student_id', $studentIds)
                    ->whereDate('attendance_date', $today)
                    ->get();

                // Count present and absent
                $presentToday = $todayAttendance->where('status', 'Present')->count();
                $absentToday = $todayAttendance->where('status', 'Absent')->count();

                // Calculate percentage
                $totalMarked = $todayAttendance->whereIn('status', ['Present', 'Absent'])->count();
                if ($totalMarked > 0) {
                    $attendancePercentage = round(($presentToday / $totalMarked) * 100, 1);
                }
            }

            // Step 6: Get latest admissions (from assigned classes only)
            $latestAdmissions = $allStudents
                ->whereNotNull('admission_date')
                ->sortByDesc('admission_date')
                ->sortByDesc('created_at')
                ->take(12)
                ->map(function($student) {
                    return [
                        'id' => $student->id,
                        'student_name' => $student->student_name,
                        'admission_date' => $student->admission_date ? Carbon::parse($student->admission_date)->format('Y-m-d') : null,
                    ];
                })
                ->values();

            return response()->json([
                'success' => true,
                'message' => 'Dashboard data retrieved successfully',
                'data' => [
                    'assigned_classes' => $assignedClasses,
                    'statistics' => [
                        'total_students' => $totalStudents,
                        'boys' => $boys,
                        'girls' => $girls,
                        'attendance_percentage' => $attendancePercentage,
                        'present_today' => $presentToday,
                        'absent_today' => $absentToday,
                    ],
                    'latest_admissions' => $latestAdmissions,
                ],
                'token' => $request->user()->currentAccessToken()->token ?? null,
            ], 200);

        } catch (\Exception $e) {
            \Log::error('Dashboard API Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'teacher_id' => $request->user()->id ?? null,
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving dashboard data: ' . $e->getMessage(),
                'token' => null,
            ], 200);
        }
    }

    /**
     * Get Teacher's Assigned Classes and Sections
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function assignedClasses(Request $request): JsonResponse
    {
        try {
            $teacher = $request->user();

            if (!$teacher || !$teacher->isTeacher()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Only teachers can access this endpoint.',
                    'token' => null,
                ], 403);
            }

            // Match teacher assignments by both name and EMP ID to avoid false negatives.
            $teacherIdentityKeys = collect([
                strtolower(trim((string) ($teacher->name ?? ''))),
                strtolower(trim((string) ($teacher->emp_id ?? ''))),
            ])->filter()->unique()->values();

            // Get classes and sections from teacher's assigned subjects
            // IMPORTANT: Only get records where teacher is CURRENTLY assigned (not null/empty)
            $assignedSubjects = Subject::query()
                ->whereNotNull('teacher')
                ->where('teacher', '!=', '')
                ->where(function ($q) use ($teacherIdentityKeys) {
                    foreach ($teacherIdentityKeys as $key) {
                        $q->orWhereRaw('LOWER(TRIM(teacher)) = ?', [$key]);
                    }
                })
                ->get();

            // Get classes and sections from teacher's assigned sections
            // IMPORTANT: Only get records where teacher is CURRENTLY assigned (not null/empty)
            $assignedSections = Section::query()
                ->whereNotNull('teacher')
                ->where('teacher', '!=', '')
                ->where(function ($q) use ($teacherIdentityKeys) {
                    foreach ($teacherIdentityKeys as $key) {
                        $q->orWhereRaw('LOWER(TRIM(teacher)) = ?', [$key]);
                    }
                })
                ->get();

            // Get unique classes from both sources
            $allClasses = $assignedSubjects->pluck('class')
                ->merge($assignedSections->pluck('class'))
                ->map(function($class) {
                    return trim($class);
                })
                ->filter(function($class) {
                    return !empty($class);
                })
                ->unique()
                ->values();
            
            // Verify classes exist in ClassModel (filter out deleted classes)
            $existingClasses = ClassModel::whereNotNull('class_name')
                ->pluck('class_name')
                ->map(function($class) {
                    return trim($class);
                })
                ->toArray();
            
            // Filter out classes that don't exist in ClassModel (deleted classes)
            $allClasses = $allClasses->filter(function($className) use ($existingClasses) {
                // Case-insensitive check if class exists in ClassModel
                foreach ($existingClasses as $existingClass) {
                    if (strtolower(trim($className)) === strtolower(trim($existingClass))) {
                        return true;
                    }
                }
                return false;
            })->values();
            
            // CRITICAL FIX: Double-check that teacher is STILL assigned to each class
            // This filters out old assignments that may still exist in Subject/Section tables
            $allClasses = $allClasses->filter(function($className) use ($teacher, $assignedSubjects, $assignedSections) {
                // Check if teacher is assigned to this class in Subject table
                $hasSubjectAssignment = $assignedSubjects->contains(function($subject) use ($className) {
                    return strtolower(trim($subject->class ?? '')) === strtolower(trim($className));
                });
                
                // Check if teacher is assigned to this class in Section table
                $hasSectionAssignment = $assignedSections->contains(function($section) use ($className) {
                    return strtolower(trim($section->class ?? '')) === strtolower(trim($className));
                });
                
                // Only include if teacher has current assignment (either Subject or Section)
                return $hasSubjectAssignment || $hasSectionAssignment;
            })->values();

            // Build classes with their sections - each section as separate entry
            $classesData = [];
            
            // Get all existing sections from Section model to verify
            $existingSections = Section::whereNotNull('name')
                ->whereNotNull('class')
                ->get()
                ->map(function($section) {
                    return [
                        'class' => trim($section->class),
                        'name' => trim($section->name),
                    ];
                });
            
            foreach ($allClasses as $className) {
                // Get sections from subjects for this class
                // Note: assignedSubjects already filtered to only include current teacher assignments
                $sectionsFromSubjects = $assignedSubjects
                    ->filter(function($subject) use ($className) {
                        return strtolower(trim($subject->class ?? '')) === strtolower(trim($className));
                    })
                    ->pluck('section')
                    ->map(function($section) {
                        return trim($section);
                    })
                    ->filter(function($section) {
                        return !empty($section);
                    })
                    ->unique()
                    ->values();

                // Get sections from sections table for this class
                // Note: assignedSections already filtered to only include current teacher assignments
                $sectionsFromSections = $assignedSections
                    ->filter(function($section) use ($className) {
                        return strtolower(trim($section->class ?? '')) === strtolower(trim($className));
                    })
                    ->pluck('name')
                    ->map(function($section) {
                        return trim($section);
                    })
                    ->filter(function($section) {
                        return !empty($section);
                    })
                    ->unique()
                    ->values();

                // Merge sections from both sources
                $allSections = $sectionsFromSubjects
                    ->merge($sectionsFromSections)
                    ->unique()
                    ->sort()
                    ->values();
                
                // Verify sections exist in Section model for this class
                $allSections = $allSections->filter(function($sectionName) use ($className, $existingSections) {
                    // Check if section exists in Section model for this class
                    return $existingSections->contains(function($existingSection) use ($className, $sectionName) {
                        return strtolower(trim($existingSection['class'])) === strtolower(trim($className)) &&
                               strtolower(trim($existingSection['name'])) === strtolower(trim($sectionName));
                    });
                })->values();

                // Create separate entry for each section
                foreach ($allSections as $section) {
                    $formattedSection = strtolower(trim($className)) . ' ' . strtolower(trim($section));
                    
                    // Check class-teacher status from already teacher-filtered section assignments.
                    // This avoids false negatives when multiple campuses contain same class+section labels.
                    $isClassTeacher = $assignedSections->contains(function ($assignedSection) use ($className, $section) {
                        return strtolower(trim((string) ($assignedSection->class ?? ''))) === strtolower(trim((string) $className))
                            && strtolower(trim((string) ($assignedSection->name ?? ''))) === strtolower(trim((string) $section));
                    });

                    // Check if teacher is subject teacher for this class+section
                    // Subject teacher is identified by Subject model teacher field.
                    $subjectAssignmentsForSection = $assignedSubjects->filter(function ($subject) use ($className, $section) {
                        return strtolower(trim((string) ($subject->class ?? ''))) === strtolower(trim((string) $className))
                            && strtolower(trim((string) ($subject->section ?? ''))) === strtolower(trim((string) $section));
                    })->values();
                    $isSubjectTeacher = $subjectAssignmentsForSection->isNotEmpty();
                    $assignedSubjectsList = $subjectAssignmentsForSection
                        ->map(function ($subject) {
                            // subjects table uses subject_name; keep legacy fallback for older data shapes.
                            return trim((string) ($subject->subject_name ?? $subject->name ?? ''));
                        })
                        ->filter(function ($subjectName) {
                            return $subjectName !== '';
                        })
                        ->unique()
                        ->sort()
                        ->values();

                    $teacherRole = 'none';
                    if ($isClassTeacher && $isSubjectTeacher) {
                        $teacherRole = 'class_and_subject_teacher';
                    } elseif ($isClassTeacher) {
                        $teacherRole = 'class_teacher';
                    } elseif ($isSubjectTeacher) {
                        $teacherRole = 'subject_teacher';
                    }
                    
                    $classesData[] = [
                        'class' => $className,
                        'section' => trim($section),
                        'formatted_sections' => $formattedSection,
                        'is_class_teacher' => $isClassTeacher,
                        'is_subject_teacher' => $isSubjectTeacher,
                        'teacher_role' => $teacherRole,
                        'assigned_subjects' => $assignedSubjectsList->values()->all(),
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Assigned classes and sections retrieved successfully',
                'data' => [
                    'teacher' => [
                        'id' => $teacher->id,
                        'name' => $teacher->name,
                        'emp_id' => $teacher->emp_id,
                        'campus' => $teacher->campus,
                    ],
                    'total_classes' => count($classesData),
                    'classes' => $classesData,
                ],
                'token' => $request->user()->currentAccessToken()->token ?? null,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving assigned classes: ' . $e->getMessage(),
                'token' => null,
            ], 500);
        }
    }

    /**
     * Get Exam List
     * 
     * Returns all exams that have been announced/created by super admin
     * NOTE: This API returns EXAMS only (from Exam model), NOT Tests (Test model)
     * Exams and Tests are separate entities in the system
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function examList(Request $request): JsonResponse
    {
        try {
            $teacher = $request->user();

            if (!$teacher || !$teacher->isTeacher()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Only teachers can access this endpoint.',
                    'token' => null,
                ], 403);
            }

            // IMPORTANT: Using Exam model, NOT Test model
            // Exams are created by super admin in Exam List page
            // Tests are separate and created in Test Management
            $query = Exam::query();

            // Restrict exams to teacher-assigned subjects only.
            $teacherNameKey = strtolower(trim((string) ($teacher->name ?? '')));
            $teacherEmpIdKey = strtolower(trim((string) ($teacher->emp_id ?? '')));
            $teacherFirstNameKey = strtolower(trim((string) explode(' ', $teacherNameKey)[0] ?? ''));
            $teacherTokens = collect([$teacherNameKey, $teacherEmpIdKey, $teacherFirstNameKey])
                ->filter(function ($token) {
                    return $token !== '';
                })
                ->unique()
                ->values();
            $assignedSubjects = Subject::query()
                ->where(function ($q) use ($teacherTokens) {
                    foreach ($teacherTokens as $token) {
                        $q->orWhereRaw('LOWER(TRIM(teacher)) = ?', [$token])
                            ->orWhereRaw('LOWER(TRIM(teacher)) LIKE ?', ["%{$token}%"]);
                    }
                })
                ->whereNotNull('subject_name')
                ->when(!empty($teacher->campus), function ($q) use ($teacher) {
                    return $q->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim((string) $teacher->campus))]);
                })
                ->get(['campus', 'class', 'section', 'subject_name']);

            $assignedSubjectKeys = $assignedSubjects
                ->pluck('subject_name')
                ->map(function ($subject) {
                    return strtolower(trim((string) $subject));
                })
                ->filter(function ($subject) {
                    return $subject !== '';
                })
                ->unique()
                ->values();

            $assignedSections = Section::query()
                ->where(function ($q) use ($teacherTokens) {
                    foreach ($teacherTokens as $token) {
                        $q->orWhereRaw('LOWER(TRIM(teacher)) = ?', [$token])
                            ->orWhereRaw('LOWER(TRIM(teacher)) LIKE ?', ["%{$token}%"]);
                    }
                })
                ->whereNotNull('class')
                ->when(!empty($teacher->campus), function ($q) use ($teacher) {
                    return $q->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim((string) $teacher->campus))]);
                })
                ->get(['campus', 'class', 'name']);

            $classSectionPairs = $assignedSubjects
                ->map(function ($row) {
                    return [
                        'campus' => strtolower(trim((string) ($row->campus ?? ''))),
                        'class' => strtolower(trim((string) ($row->class ?? ''))),
                        'section' => strtolower(trim((string) ($row->section ?? ''))),
                    ];
                })
                ->merge($assignedSections->map(function ($row) {
                    return [
                        'campus' => strtolower(trim((string) ($row->campus ?? ''))),
                        'class' => strtolower(trim((string) ($row->class ?? ''))),
                        'section' => strtolower(trim((string) ($row->name ?? ''))),
                    ];
                }))
                ->filter(function ($pair) {
                    return $pair['class'] !== '' && $pair['section'] !== '';
                })
                ->unique(fn ($pair) => implode('|', [$pair['campus'], $pair['class'], $pair['section']]))
                ->values();

            // Fallback: derive subject keys from class/section mappings when direct subject rows are missing.
            if ($assignedSubjectKeys->isEmpty() && $classSectionPairs->isNotEmpty()) {
                $derivedSubjectKeys = collect();
                foreach ($classSectionPairs as $pair) {
                    $derivedSubjectKeys = $derivedSubjectKeys->merge(
                        Subject::query()
                            ->whereRaw('LOWER(TRIM(class)) = ?', [$pair['class']])
                            ->whereRaw('LOWER(TRIM(section)) = ?', [$pair['section']])
                            ->when($pair['campus'] !== '', function ($q) use ($pair) {
                                return $q->whereRaw('LOWER(TRIM(campus)) = ?', [$pair['campus']]);
                            })
                            ->whereNotNull('subject_name')
                            ->pluck('subject_name')
                            ->map(fn ($subject) => strtolower(trim((string) $subject)))
                    );
                }

                $assignedSubjectKeys = $derivedSubjectKeys
                    ->filter(fn ($subject) => $subject !== '')
                    ->unique()
                    ->values();
            }

            if ($assignedSubjectKeys->isEmpty() && $classSectionPairs->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Exam list retrieved successfully',
                    'data' => [],
                    'pagination' => [
                        'current_page' => 1,
                        'last_page' => 1,
                        'per_page' => (int) $request->get('per_page', 15),
                        'total' => 0,
                        'from' => null,
                        'to' => null,
                    ],
                    'filter_options' => [
                        'campuses' => collect(),
                        'sessions' => collect(),
                        'exam_names' => collect(),
                    ],
                    'token' => $request->user()->currentAccessToken()->token ?? null,
                ], 200);
            }

            $teacherCampusKey = strtolower(trim((string) ($teacher->campus ?? '')));

            $allowedExamNameKeys = collect();

            if ($assignedSubjectKeys->isNotEmpty()) {
                $allowedExamNameKeys = $allowedExamNameKeys->merge(
                    ExamTimetable::query()
                        ->where(function ($q) use ($assignedSubjectKeys) {
                            foreach ($assignedSubjectKeys as $subjectKey) {
                                $q->orWhereRaw('LOWER(TRIM(subject)) = ?', [$subjectKey]);
                            }
                        })
                        ->when($teacherCampusKey !== '', function ($q) use ($teacherCampusKey) {
                            return $q->whereRaw('LOWER(TRIM(campus)) = ?', [$teacherCampusKey]);
                        })
                        ->whereNotNull('exam_name')
                        ->selectRaw('LOWER(TRIM(exam_name)) as exam_name_key')
                        ->distinct()
                        ->pluck('exam_name_key')
                );
            }

            if ($classSectionPairs->isNotEmpty()) {
                $allowedExamNameKeys = $allowedExamNameKeys->merge(
                    ExamTimetable::query()
                        ->where(function ($q) use ($classSectionPairs) {
                            foreach ($classSectionPairs as $pair) {
                                $q->orWhere(function ($inner) use ($pair) {
                                    $inner->whereRaw('LOWER(TRIM(class)) = ?', [$pair['class']])
                                        ->whereRaw('LOWER(TRIM(section)) = ?', [$pair['section']]);
                                    if ($pair['campus'] !== '') {
                                        $inner->whereRaw('LOWER(TRIM(campus)) = ?', [$pair['campus']]);
                                    }
                                });
                            }
                        })
                        ->when($teacherCampusKey !== '', function ($q) use ($teacherCampusKey) {
                            return $q->whereRaw('LOWER(TRIM(campus)) = ?', [$teacherCampusKey]);
                        })
                        ->whereNotNull('exam_name')
                        ->selectRaw('LOWER(TRIM(exam_name)) as exam_name_key')
                        ->distinct()
                        ->pluck('exam_name_key')
                );
            }

            // Include exams uploaded in Exam list even before timetable rows exist.
            $allowedExamNameKeys = $allowedExamNameKeys->merge(
                Exam::query()
                    ->when($teacherCampusKey !== '', function ($q) use ($teacherCampusKey) {
                        return $q->where(function ($campusScope) use ($teacherCampusKey) {
                            $campusScope->whereRaw('LOWER(TRIM(campus)) = ?', [$teacherCampusKey])
                                ->orWhereNull('campus')
                                ->orWhereRaw('TRIM(campus) = ?', ['']);
                        });
                    })
                    ->whereNotNull('exam_name')
                    ->selectRaw('LOWER(TRIM(exam_name)) as exam_name_key')
                    ->distinct()
                    ->pluck('exam_name_key')
            );

            $allowedExamNameKeys = $allowedExamNameKeys
                ->filter(fn ($examNameKey) => !empty($examNameKey))
                ->unique()
                ->values();

            if ($allowedExamNameKeys->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Exam list retrieved successfully',
                    'data' => [],
                    'pagination' => [
                        'current_page' => 1,
                        'last_page' => 1,
                        'per_page' => (int) $request->get('per_page', 15),
                        'total' => 0,
                        'from' => null,
                        'to' => null,
                    ],
                    'filter_options' => [
                        'campuses' => collect(),
                        'sessions' => collect(),
                        'exam_names' => collect(),
                    ],
                    'token' => $request->user()->currentAccessToken()->token ?? null,
                ], 200);
            }

            $query->where(function ($q) use ($allowedExamNameKeys) {
                foreach ($allowedExamNameKeys as $examNameKey) {
                    $q->orWhereRaw('LOWER(TRIM(exam_name)) = ?', [$examNameKey]);
                }
            });

            // Filter by campus (teacher campus + allow blank/null campus rows)
            if ($teacherCampusKey !== '') {
                $query->where(function ($campusScope) use ($teacherCampusKey) {
                    $campusScope->whereRaw('LOWER(TRIM(campus)) = ?', [$teacherCampusKey])
                        ->orWhereNull('campus')
                        ->orWhereRaw('TRIM(campus) = ?', ['']);
                });
            }

            // Search functionality
            if ($request->filled('search')) {
                $search = trim($request->search);
                if (!empty($search)) {
                    $searchLower = strtolower($search);
                    $query->where(function($q) use ($searchLower) {
                        $q->whereRaw('LOWER(exam_name) LIKE ?', ["%{$searchLower}%"])
                          ->orWhereRaw('LOWER(campus) LIKE ?', ["%{$searchLower}%"])
                          ->orWhereRaw('LOWER(session) LIKE ?', ["%{$searchLower}%"]);
                    });
                }
            }

            // Filter by campus
            if ($request->filled('campus')) {
                $campus = trim($request->campus);
                if (!empty($campus)) {
                    $query->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower($campus)]);
                }
            }

            // Filter by session
            if ($request->filled('session')) {
                $session = trim($request->session);
                if (!empty($session)) {
                    $query->whereRaw('LOWER(TRIM(session)) = ?', [strtolower($session)]);
                }
            }

            // Filter by exam name
            if ($request->filled('exam_name')) {
                $examName = trim($request->exam_name);
                if (!empty($examName)) {
                    $query->whereRaw('LOWER(TRIM(exam_name)) = ?', [strtolower($examName)]);
                }
            }

            // Filter by date range
            if ($request->filled('start_date')) {
                $startDate = Carbon::parse($request->start_date)->format('Y-m-d');
                $query->whereDate('exam_date', '>=', $startDate);
            }

            if ($request->filled('end_date')) {
                $endDate = Carbon::parse($request->end_date)->format('Y-m-d');
                $query->whereDate('exam_date', '<=', $endDate);
            }

            // Filter by upcoming exams only
            $upcomingOnly = filter_var($request->get('upcoming_only', false), FILTER_VALIDATE_BOOLEAN);
            if ($upcomingOnly) {
                $query->whereDate('exam_date', '>=', Carbon::today()->format('Y-m-d'));
            }

            // Filter by past exams only
            $pastOnly = filter_var($request->get('past_only', false), FILTER_VALIDATE_BOOLEAN);
            if ($pastOnly) {
                $query->whereDate('exam_date', '<', Carbon::today()->format('Y-m-d'));
            }

            // Pagination
            $perPage = $request->get('per_page', 15);
            $perPage = in_array($perPage, [10, 15, 25, 50, 100]) ? $perPage : 15;

            // Order by exam date (descending - newest first)
            $exams = $query->orderBy('exam_date', 'desc')
                ->orderBy('created_at', 'desc')
                ->paginate($perPage)
                ->withQueryString();

            // Build expected-vs-marked counters from ExamTimetable so status can be pending/partial/completed accurately.
            $coverageByExamId = [];
            foreach ($exams as $examItem) {
                $examNameKey = strtolower(trim((string) ($examItem->exam_name ?? '')));
                if ($examNameKey === '') {
                    continue;
                }

                $ttQuery = ExamTimetable::query()
                    ->whereRaw('LOWER(TRIM(exam_name)) = ?', [$examNameKey])
                    ->where(function ($q) use ($assignedSubjectKeys, $classSectionPairs) {
                        if ($assignedSubjectKeys->isNotEmpty()) {
                            $q->where(function ($subjectScope) use ($assignedSubjectKeys) {
                                foreach ($assignedSubjectKeys as $subjectKey) {
                                    $subjectScope->orWhereRaw('LOWER(TRIM(subject)) = ?', [$subjectKey]);
                                }
                            });
                        }

                        if ($classSectionPairs->isNotEmpty()) {
                            $method = $assignedSubjectKeys->isNotEmpty() ? 'orWhere' : 'where';
                            $q->{$method}(function ($classSectionScope) use ($classSectionPairs) {
                                foreach ($classSectionPairs as $pair) {
                                    $classSectionScope->orWhere(function ($inner) use ($pair) {
                                        $inner->whereRaw('LOWER(TRIM(class)) = ?', [$pair['class']])
                                            ->whereRaw('LOWER(TRIM(section)) = ?', [$pair['section']]);
                                        if ($pair['campus'] !== '') {
                                            $inner->whereRaw('LOWER(TRIM(campus)) = ?', [$pair['campus']]);
                                        }
                                    });
                                }
                            });
                        }
                    });
                if ($teacherCampusKey !== '') {
                    $ttQuery->whereRaw('LOWER(TRIM(campus)) = ?', [$teacherCampusKey]);
                }
                $examRows = $ttQuery->get(['campus', 'class', 'section', 'subject']);

                $expectedTotal = 0;
                $markedTotal = 0;

                if ($examRows->isNotEmpty()) {
                    foreach ($examRows as $row) {
                        $rowCampus = trim((string) ($row->campus ?? ''));
                        $rowClass = trim((string) ($row->class ?? ''));
                        $rowSection = trim((string) ($row->section ?? ''));
                        $rowSubject = trim((string) ($row->subject ?? ''));
                        if ($rowClass === '' || $rowSection === '' || $rowSubject === '') {
                            continue;
                        }

                        $studentIds = Student::query()
                            ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower($rowClass)])
                            ->whereRaw('LOWER(TRIM(section)) = ?', [strtolower($rowSection)])
                            ->when($rowCampus !== '', function ($q) use ($rowCampus) {
                                return $q->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower($rowCampus)]);
                            })
                            ->pluck('id')
                            ->toArray();
                        $studentsCount = count($studentIds);
                        $expectedTotal += $studentsCount;

                        $markedCount = 0;
                        if (!empty($studentIds)) {
                            $markedCount = StudentMark::query()
                                ->whereIn('student_id', $studentIds)
                                ->whereRaw('LOWER(TRIM(test_name)) = ?', [$examNameKey])
                                ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower($rowClass)])
                                ->whereRaw('LOWER(TRIM(section)) = ?', [strtolower($rowSection)])
                                ->whereRaw('LOWER(TRIM(subject)) = ?', [strtolower($rowSubject)])
                                ->when($rowCampus !== '', function ($q) use ($rowCampus) {
                                    return $q->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower($rowCampus)]);
                                })
                                ->when(!empty($examItem->created_at), function ($q) use ($examItem) {
                                    // Ignore stale/old marks from previous exams with same name.
                                    return $q->where('created_at', '>=', $examItem->created_at);
                                })
                                ->where(function ($q) {
                                    // Count as uploaded only when actual numeric marks are entered.
                                    $q->where(function ($q2) {
                                        $q2->whereNotNull('marks_obtained')
                                            ->whereRaw('TRIM(CAST(marks_obtained AS CHAR)) <> ""');
                                    })->orWhere(function ($q2) {
                                        $q2->whereNotNull('total_marks')
                                            ->whereRaw('TRIM(CAST(total_marks AS CHAR)) <> ""');
                                    })->orWhere(function ($q2) {
                                        $q2->whereNotNull('passing_marks')
                                            ->whereRaw('TRIM(CAST(passing_marks AS CHAR)) <> ""');
                                    });
                                })
                                ->distinct('student_id')
                                ->count('student_id');
                        }
                        $markedTotal += $markedCount;
                    }
                }

                $coverageByExamId[(int) $examItem->id] = [
                    'expected_total' => $expectedTotal,
                    'marked_total' => $markedTotal,
                    'has_exam_timetable' => $examRows->isNotEmpty(),
                ];
            }

            // Format exam data
            $examsData = $exams->map(function($exam) use ($coverageByExamId) {
                $examDate = $exam->exam_date ? Carbon::parse($exam->exam_date) : null;
                $isUpcoming = $examDate ? $examDate->isFuture() : false;
                $isPast = $examDate ? $examDate->isPast() : false;
                $isToday = $examDate ? $examDate->isToday() : false;
                $resultDeclared = (bool) ($exam->result_status ?? false);
                $examNameKey = strtolower(trim((string) ($exam->exam_name ?? '')));
                $expectedTotal = (int) ($coverageByExamId[(int) $exam->id]['expected_total'] ?? 0);
                $markedTotal = (int) ($coverageByExamId[(int) $exam->id]['marked_total'] ?? 0);
                $markedStudentsCount = $markedTotal;
                // Do not mark uploaded/locked before web declare-result.
                $marksUploaded = $resultDeclared;
                $alreadyUploaded = $resultDeclared;
                $hasExamTimetable = (bool) ($coverageByExamId[(int) $exam->id]['has_exam_timetable'] ?? false);
                $canUploadMarks = $hasExamTimetable && !$resultDeclared;
                $uploadStatus = $resultDeclared
                    ? 'result_declared'
                    : ($hasExamTimetable ? 'not_uploaded' : 'timetable_missing');

                // Status should only reflect marks upload state.
                // completed => marks uploaded
                // pending   => marks not uploaded yet
                $status = $resultDeclared ? 'completed' : 'pending';

                return [
                    'id' => $exam->id,
                    'exam_name' => $exam->exam_name,
                    'campus' => $exam->campus,
                    'description' => $exam->description,
                    'exam_date' => $exam->exam_date ? Carbon::parse($exam->exam_date)->format('Y-m-d') : null,
                    'exam_date_formatted' => $exam->exam_date ? Carbon::parse($exam->exam_date)->format('d M Y') : null,
                    'session' => $exam->session,
                    'result_status' => $resultDeclared,
                    'marks_uploaded' => $marksUploaded,
                    'already_uploaded' => $alreadyUploaded,
                    'upload_status' => $uploadStatus,
                    'has_exam_timetable' => $hasExamTimetable,
                    'marks_entry_enabled' => $canUploadMarks,
                    'can_upload_marks' => $canUploadMarks,
                    'marked_students_count' => $markedStudentsCount,
                    'expected_marks_count' => $expectedTotal,
                    'uploaded_marks_count' => $markedTotal,
                    'status' => $status,
                    'created_at' => $exam->created_at ? Carbon::parse($exam->created_at)->format('Y-m-d H:i:s') : null,
                    'created_at_formatted' => $exam->created_at ? Carbon::parse($exam->created_at)->format('d M Y, h:i A') : null,
                    'is_upcoming' => $isUpcoming,
                    'is_past' => $isPast,
                    'is_today' => $isToday,
                ];
            });

            // Get filter options for frontend
            $teacherExamFilterBase = Exam::query()
                ->where(function ($q) use ($allowedExamNameKeys) {
                    foreach ($allowedExamNameKeys as $examNameKey) {
                        $q->orWhereRaw('LOWER(TRIM(exam_name)) = ?', [$examNameKey]);
                    }
                });
            if ($teacherCampusKey !== '') {
                $teacherExamFilterBase->where(function ($campusScope) use ($teacherCampusKey) {
                    $campusScope->whereRaw('LOWER(TRIM(campus)) = ?', [$teacherCampusKey])
                        ->orWhereNull('campus')
                        ->orWhereRaw('TRIM(campus) = ?', ['']);
                });
            }

            $filterOptions = [
                'campuses' => (clone $teacherExamFilterBase)
                    ->whereNotNull('campus')
                    ->distinct()
                    ->pluck('campus')
                    ->sort()
                    ->values(),
                'sessions' => (clone $teacherExamFilterBase)
                    ->whereNotNull('session')
                    ->distinct()
                    ->pluck('session')
                    ->sort()
                    ->values(),
                'exam_names' => (clone $teacherExamFilterBase)
                    ->whereNotNull('exam_name')
                    ->distinct()
                    ->pluck('exam_name')
                    ->sort()
                    ->values(),
            ];

            return response()->json([
                'success' => true,
                'message' => 'Exam list retrieved successfully',
                'data' => [
                    'teacher' => [
                        'name' => $teacher->name,
                        'emp_id' => $teacher->emp_id,
                        'campus' => $teacher->campus,
                    ],
                    'exams' => $examsData,
                    'pagination' => [
                        'current_page' => $exams->currentPage(),
                        'last_page' => $exams->lastPage(),
                        'per_page' => $exams->perPage(),
                        'total' => $exams->total(),
                        'from' => $exams->firstItem(),
                        'to' => $exams->lastItem(),
                    ],
                    'filters' => [
                        'search' => $request->get('search'),
                        'campus' => $request->get('campus'),
                        'session' => $request->get('session'),
                        'exam_name' => $request->get('exam_name'),
                        'start_date' => $request->get('start_date'),
                        'end_date' => $request->get('end_date'),
                        'upcoming_only' => $upcomingOnly,
                        'past_only' => $pastOnly,
                    ],
                    'filter_options' => $filterOptions,
                ],
                'token' => $request->user()->currentAccessToken()->token ?? null,
            ], 200);

        } catch (\Exception $e) {
            \Log::error('Exam List API Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'teacher_id' => $request->user()->id ?? null,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving exam list: ' . $e->getMessage(),
                'token' => null,
            ], 500);
        }
    }

    /**
     * Get Students List for Exam Management
     * 
     * Returns students list based on class and section selection
     * Used specifically for EXAM management (not test management)
     * 
     * NOTE: This API is for EXAMS created by super admin in "Exam List" page
     * For tests, use the test management API endpoints
     * 
     * Query Parameters:
     * - class (required): Class name (e.g., "ten", "nine")
     * - section (optional): Section name (e.g., "a", "b", "t")
     * - exam_name (optional): Exam name to load existing marks (e.g., "Mid Term Exam")
     * - subject (optional): Subject name to narrow marks within selected exam (e.g., "English")
     * - search (optional): Search by student name or code
     * 
     * Example: GET /api/teacher/exam/students?class=ten&section=t
     * Example: GET /api/teacher/exam/students?class=ten&section=t&exam_name=Mid Term Exam&subject=English
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function examStudents(Request $request): JsonResponse
    {
        try {
            $teacher = $request->user();

            if (!$teacher || !$teacher->isTeacher()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Only teachers can access this endpoint.',
                    'token' => null,
                ], 403);
            }

            // Get class and section from request (required for exam marks entry)
            $className = $request->input('class') ?? $request->query('class');
            $sectionName = $request->input('section') ?? $request->query('section');
            $examName = $request->input('exam_name') ?? $request->query('exam_name'); // Exam name from Exam List
            $subjectName = $request->input('subject') ?? $request->query('subject');
            $unmarkedOnly = filter_var($request->query('unmarked_only', 'false'), FILTER_VALIDATE_BOOLEAN);

            // Validate required parameters
            if (!$className) {
                return response()->json([
                    'success' => false,
                    'message' => 'Class parameter is required.',
                    'token' => null,
                ], 400);
            }

            // Get teacher's assigned subjects and sections
            $assignedSubjects = Subject::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($teacher->name ?? ''))])
                ->get();

            $assignedSections = Section::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($teacher->name ?? ''))])
                ->get();

            // Get unique classes from both sources
            $assignedClasses = $assignedSubjects->pluck('class')
                ->merge($assignedSections->pluck('class'))
                ->map(function($class) {
                    return trim($class);
                })
                ->filter(function($class) {
                    return !empty($class);
                })
                ->unique()
                ->values();

            // Verify class is assigned to teacher
            $className = trim($className);
            $isClassAssigned = $assignedClasses->contains(function($class) use ($className) {
                return strtolower(trim($class)) === strtolower($className);
            });

            if (!$isClassAssigned && $assignedClasses->isNotEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not assigned to this class.',
                    'token' => null,
                ], 403);
            }

            $sectionName = $sectionName ? trim((string) $sectionName) : null;
            $examName = $examName ? trim((string) $examName) : null;
            $subjectName = $subjectName ? trim((string) $subjectName) : null;

            if ($examName) {
                $timetableQuery = ExamTimetable::query()
                    ->whereRaw('LOWER(TRIM(exam_name)) = ?', [strtolower($examName)])
                    ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower($className)]);

                if ($sectionName) {
                    $timetableQuery->whereRaw('LOWER(TRIM(section)) = ?', [strtolower($sectionName)]);
                }
                if (!empty($teacher->campus)) {
                    $timetableQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim((string) $teacher->campus))]);
                }
                if ($subjectName) {
                    $timetableQuery->whereRaw('LOWER(TRIM(subject)) = ?', [strtolower($subjectName)]);
                }

                if (!$timetableQuery->exists()) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Exam timetable not found for selected class/section. Students list is not available until timetable is added.',
                        'data' => [
                            'teacher' => [
                                'name' => $teacher->name,
                                'emp_id' => $teacher->emp_id,
                                'campus' => $teacher->campus,
                            ],
                            'filters' => [
                                'class' => $className,
                                'section' => $sectionName,
                                'exam_name' => $examName,
                                'subject' => $subjectName,
                            ],
                            'timetable_exists' => false,
                            'available_subjects' => [],
                            'students' => [],
                            'total_students' => 0,
                            'available_exam_names' => [],
                            'marks_upload_status' => [
                                'exam_name' => $examName,
                                'subject' => $subjectName,
                                'uploaded_students' => 0,
                                'unuploaded_students' => 0,
                                'is_fully_uploaded' => false,
                            ],
                        ],
                        'token' => $request->user()->currentAccessToken()->token ?? null,
                    ], 200);
                }
            }

            // Resolve subject from exam timetable (not generic teacher assignment).
            $teacherTokens = collect([
                strtolower(trim((string) ($teacher->name ?? ''))),
                strtolower(trim((string) ($teacher->emp_id ?? ''))),
            ])->filter()->unique()->values();
            $teacherCampusKey = strtolower(trim((string) ($teacher->campus ?? '')));
            $examTimetableSubjects = collect();
            $availableSubjects = collect();
            $isClassTeacher = false;

            if ($examName) {
                $examTimetableSubjects = ExamTimetable::query()
                    ->whereRaw('LOWER(TRIM(exam_name)) = ?', [strtolower($examName)])
                    ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower($className)])
                    ->when($sectionName, function ($query) use ($sectionName) {
                        $query->whereRaw('LOWER(TRIM(section)) = ?', [strtolower($sectionName)]);
                    })
                    ->when($teacherCampusKey !== '', function ($query) use ($teacherCampusKey) {
                        $query->whereRaw('LOWER(TRIM(campus)) = ?', [$teacherCampusKey]);
                    })
                    ->pluck('subject')
                    ->map(fn ($subject) => trim((string) $subject))
                    ->filter(fn ($subject) => $subject !== '')
                    ->unique()
                    ->values();

                $teacherAssignedSubjectKeys = Subject::query()
                    ->where(function ($query) use ($teacherTokens) {
                        foreach ($teacherTokens as $token) {
                            $query->orWhereRaw('LOWER(TRIM(teacher)) = ?', [$token]);
                        }
                    })
                    ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower($className)])
                    ->when($sectionName, function ($query) use ($sectionName) {
                        $query->whereRaw('LOWER(TRIM(section)) = ?', [strtolower($sectionName)]);
                    })
                    ->when($teacherCampusKey !== '', function ($query) use ($teacherCampusKey) {
                        $query->whereRaw('LOWER(TRIM(campus)) = ?', [$teacherCampusKey]);
                    })
                    ->pluck('subject_name')
                    ->map(fn ($subject) => strtolower(trim((string) $subject)))
                    ->filter()
                    ->unique()
                    ->values();

                $isClassTeacher = Section::query()
                    ->where(function ($query) use ($teacherTokens) {
                        foreach ($teacherTokens as $token) {
                            $query->orWhereRaw('LOWER(TRIM(teacher)) = ?', [$token]);
                        }
                    })
                    ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower($className)])
                    ->when($sectionName, function ($query) use ($sectionName) {
                        $query->whereRaw('LOWER(TRIM(name)) = ?', [strtolower($sectionName)]);
                    })
                    ->when($teacherCampusKey !== '', function ($query) use ($teacherCampusKey) {
                        $query->whereRaw('LOWER(TRIM(campus)) = ?', [$teacherCampusKey]);
                    })
                    ->exists();

                $availableSubjects = $examTimetableSubjects
                    ->filter(function ($subject) use ($teacherAssignedSubjectKeys, $isClassTeacher) {
                        if ($isClassTeacher) {
                            return true;
                        }

                        return $teacherAssignedSubjectKeys->contains(strtolower(trim($subject)));
                    })
                    ->values();
            }

            $resolvedSubjectName = $subjectName ? trim((string) $subjectName) : null;
            if ($examName) {
                if ($resolvedSubjectName) {
                    $subjectAllowed = $availableSubjects->contains(function ($subject) use ($resolvedSubjectName) {
                        return strtolower(trim($subject)) === strtolower(trim((string) $resolvedSubjectName));
                    });

                    if (!$subjectAllowed) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Selected subject is not part of this exam timetable for the given class/section.',
                            'data' => [
                                'filters' => [
                                    'class' => $className,
                                    'section' => $sectionName,
                                    'exam_name' => $examName,
                                    'subject' => $resolvedSubjectName,
                                ],
                                'available_subjects' => $availableSubjects->values(),
                                'timetable_subjects' => $examTimetableSubjects->values(),
                            ],
                            'token' => $request->user()->currentAccessToken()->token ?? null,
                        ], 422);
                    }
                } elseif ($availableSubjects->count() === 1) {
                    $resolvedSubjectName = $availableSubjects->first();
                } else {
                    $resolvedSubjectName = null;
                }
            } elseif (empty($resolvedSubjectName)) {
                $subjectCandidatesQuery = Subject::query()
                    ->where(function ($query) use ($teacherTokens) {
                        foreach ($teacherTokens as $token) {
                            $query->orWhereRaw('LOWER(TRIM(teacher)) = ?', [$token]);
                        }
                    })
                    ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim((string) $className))]);

                if (!empty($sectionName)) {
                    $subjectCandidatesQuery->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim((string) $sectionName))]);
                }

                $subjectCandidates = $subjectCandidatesQuery
                    ->whereNotNull('subject_name')
                    ->pluck('subject_name')
                    ->map(fn ($subject) => trim((string) $subject))
                    ->filter(fn ($subject) => $subject !== '')
                    ->unique()
                    ->values();

                if ($subjectCandidates->count() === 1) {
                    $resolvedSubjectName = $subjectCandidates->first();
                }
            }

            // Build query
            $query = Student::query();

            // Filter by teacher's campus
            if ($teacher->campus) {
                $query->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($teacher->campus))]);
            }

            // Filter by class (required)
            $query->whereRaw('LOWER(TRIM(class)) = ?', [strtolower($className)]);

            // Filter by section (if provided)
            if ($sectionName) {
                $query->whereRaw('LOWER(TRIM(section)) = ?', [strtolower($sectionName)]);
            }

            // Search functionality
            if ($request->filled('search')) {
                $search = trim($request->search);
                if (!empty($search)) {
                    $searchLower = strtolower($search);
                    $query->where(function($q) use ($search, $searchLower) {
                        $q->whereRaw('LOWER(student_name) LIKE ?', ["%{$searchLower}%"])
                          ->orWhere('student_code', 'like', "%{$search}%");
                    });
                }
            }

            // Get students
            $students = $query->orderBy('student_code', 'asc')
                ->orderBy('student_name', 'asc')
                ->get();

            // Load existing exam marks.
            // - If exam_name + subject provided: marks for that exact exam+subject only.
            // - If exam_name provided but subject missing: load marks for selected exam
            //   (latest row per student) so app can show upload status dynamically.
            // - Otherwise: latest uploaded marks per student for class/section.
            $marksData = [];
            if ($students->count() > 0) {
                $studentIds = $students->pluck('id')->values()->all();
                $marksQuery = StudentMark::query()
                    ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower($className)])
                    ->when($sectionName, function($q) use ($sectionName) {
                        return $q->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($sectionName))]);
                    })
                    ->whereIn('student_id', $studentIds);

                if ($examName && $resolvedSubjectName) {
                    $marksQuery
                        ->whereRaw('LOWER(TRIM(test_name)) = ?', [strtolower(trim($examName))])
                        ->whereRaw('LOWER(TRIM(subject)) = ?', [strtolower(trim($resolvedSubjectName))]);
                } elseif ($examName && !$resolvedSubjectName) {
                    // No subject selected: still load exam-level marks (any subject)
                    // to show uploaded state and latest uploaded values per student.
                    $marksQuery->whereRaw('LOWER(TRIM(test_name)) = ?', [strtolower(trim($examName))]);
                } else {
                    $marksQuery->where(function ($q) {
                        $q->whereNotNull('marks_obtained')
                            ->orWhereNotNull('total_marks')
                            ->orWhereNotNull('passing_marks')
                            ->orWhereNotNull('teacher_remarks');
                    });
                }

                $marks = $marksQuery
                    ->get()
                    ->groupBy('student_id')
                    ->map(function ($rows) use ($examName, $resolvedSubjectName) {
                        if ($examName && $resolvedSubjectName) {
                            // For selected exam (with or without subject), keep latest row.
                            return $rows->sortByDesc('updated_at')->first();
                        }
                        // No selected exam context: use most recent uploaded row.
                        return $rows->sortByDesc('updated_at')->first();
                    });

                $marksData = $marks->map(function($mark) {
                    return [
                        'student_id' => $mark->student_id,
                        'exam_name' => $mark->test_name,
                        'subject' => $mark->subject,
                        'marks_obtained' => $mark->marks_obtained,
                        'total_marks' => $mark->total_marks,
                        'passing_marks' => $mark->passing_marks,
                        'remarks' => $mark->teacher_remarks,
                    ];
                })->toArray();
            }

            // Format students data with exam marks if available
            $studentsData = $students->map(function($student) use ($marksData) {
                $studentData = [
                    'id' => $student->id,
                    'student_code' => $student->student_code,
                    'student_name' => $student->student_name,
                    'class' => $student->class,
                    'section' => $student->section,
                    'campus' => $student->campus,
                    'gender' => $student->gender,
                    'admission_date' => $student->admission_date?->format('Y-m-d'),
                    'photo' => $student->photo ? asset('storage/' . $student->photo) : null,
                ];

                $hasMarkRow = isset($marksData[$student->id]);
                $hasUploadedMarks = false;

                // Add existing exam marks if available
                if ($hasMarkRow) {
                    $marksObtained = $marksData[$student->id]['marks_obtained'] ?? null;
                    $totalMarks = $marksData[$student->id]['total_marks'] ?? null;
                    $passingMarks = $marksData[$student->id]['passing_marks'] ?? null;

                    // Consider marks uploaded only when obtained marks are actually entered.
                    $hasUploadedMarks = $marksObtained !== null && trim((string) $marksObtained) !== '';
                    $studentData['marks_uploaded'] = $hasUploadedMarks;

                    $studentData['marks_obtained'] = $marksObtained;
                    $studentData['total_marks'] = $totalMarks;
                    $studentData['passing_marks'] = $passingMarks;
                    $studentData['remarks'] = $marksData[$student->id]['remarks'] ?? null;
                    $studentData['uploaded_exam_name'] = $marksData[$student->id]['exam_name'] ?? null;
                    $studentData['uploaded_subject'] = $marksData[$student->id]['subject'] ?? null;

                    // Boolean pass based on obtained >= passing
                    if ($marksObtained !== null && $passingMarks !== null && $passingMarks !== '') {
                        $studentData['is_passed'] = (float)$marksObtained >= (float)$passingMarks;
                    } else {
                        $studentData['is_passed'] = null;
                    }
                } else {
                    $studentData['marks_uploaded'] = false;
                }

                return $studentData;
            })->filter(function ($studentData) use ($marksData, $unmarkedOnly) {
                if (!$unmarkedOnly) {
                    return true;
                }
                // If marks exist for this student -> hide it
                $id = $studentData['id'] ?? null;
                return $id ? !isset($marksData[$id]) : true;
            })->values();

            $uploadedStudentsCount = $studentsData->where('marks_uploaded', true)->count();

            $message = 'Students list retrieved successfully for class: ' . $className;
            if ($sectionName) {
                $message .= ', section: ' . $sectionName;
            }

            $availableExamNames = ExamTimetable::query()
                ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower($className)])
                ->when($sectionName, function ($q) use ($sectionName) {
                    $q->whereRaw('LOWER(TRIM(section)) = ?', [strtolower($sectionName)]);
                })
                ->when($teacher->campus, function ($q) use ($teacher) {
                    $q->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($teacher->campus))]);
                })
                ->orderBy('exam_name', 'asc')
                ->pluck('exam_name')
                ->filter()
                ->unique()
                ->values();

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'teacher' => [
                        'name' => $teacher->name,
                        'emp_id' => $teacher->emp_id,
                        'campus' => $teacher->campus,
                    ],
                    'filters' => [
                        'class' => $className,
                        'section' => $sectionName,
                        'exam_name' => $examName,
                        'subject' => $resolvedSubjectName,
                    ],
                    'available_subjects' => $availableSubjects->values(),
                    'timetable_subjects' => $examTimetableSubjects->values(),
                    'is_class_teacher' => $isClassTeacher,
                    'available_exam_names' => $availableExamNames,
                    'marks_upload_status' => [
                        'exam_name' => $examName,
                        'subject' => $resolvedSubjectName,
                        'uploaded_students' => $uploadedStudentsCount,
                        'unuploaded_students' => max(0, $studentsData->count() - $uploadedStudentsCount),
                        'is_fully_uploaded' => $studentsData->count() > 0 && $uploadedStudentsCount === $studentsData->count(),
                    ],
                    'total_students' => $studentsData->count(),
                    'students' => $studentsData,
                ],
                'token' => $request->user()->currentAccessToken()->token ?? null,
            ], 200);

        } catch (\Exception $e) {
            \Log::error('Exam Students API Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'teacher_id' => $request->user()->id ?? null,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving students: ' . $e->getMessage(),
                'token' => null,
            ], 500);
        }
    }

    /**
     * Get all students for exam marks entry by class/section/exam/subject.
     *
     * Required query params:
     * - class
     * - section
     * - exam_name
     * - subject
     *
     * Example:
     * GET /api/teacher/exam/students/by-subject?class=One&section=A&exam_name=Mid%20Term&subject=English
     */
    public function examStudentsBySubject(Request $request): JsonResponse
    {
        try {
            $teacher = $request->user();

            if (!$teacher || !$teacher->isTeacher()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Only teachers can access this endpoint.',
                    'token' => null,
                ], 403);
            }

            $validated = $request->validate([
                'class' => ['required', 'string', 'max:255'],
                'section' => ['required', 'string', 'max:255'],
                'exam_name' => ['required', 'string', 'max:255'],
                'subject' => ['required', 'string', 'max:255'],
                'campus' => ['nullable', 'string', 'max:255'],
            ]);

            $className = trim((string) $validated['class']);
            $sectionName = trim((string) $validated['section']);
            $examName = trim((string) $validated['exam_name']);
            $subjectName = trim((string) $validated['subject']);
            $campusName = isset($validated['campus']) ? trim((string) $validated['campus']) : null;

            // Verify class access for this teacher
            $assignedSubjects = Subject::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($teacher->name ?? ''))])->get();
            $assignedSections = Section::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($teacher->name ?? ''))])->get();

            $assignedClasses = $assignedSubjects->pluck('class')
                ->merge($assignedSections->pluck('class'))
                ->map(fn($class) => trim((string) $class))
                ->filter(fn($class) => !empty($class))
                ->unique()
                ->values();

            $isClassAssigned = $assignedClasses->contains(function ($class) use ($className) {
                return strtolower(trim($class)) === strtolower($className);
            });

            if (!$isClassAssigned && $assignedClasses->isNotEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not assigned to this class.',
                    'token' => null,
                ], 403);
            }

            $studentsQuery = Student::query()
                ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower($className)])
                ->whereRaw('LOWER(TRIM(section)) = ?', [strtolower($sectionName)]);

            if (!empty($campusName)) {
                $studentsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower($campusName)]);
            } elseif (!empty($teacher->campus)) {
                $studentsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim((string) $teacher->campus))]);
            }

            $students = $studentsQuery
                ->orderBy('student_code', 'asc')
                ->orderBy('student_name', 'asc')
                ->get();

            $marks = StudentMark::whereRaw('LOWER(TRIM(test_name)) = ?', [strtolower($examName)])
                ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower($className)])
                ->whereRaw('LOWER(TRIM(section)) = ?', [strtolower($sectionName)])
                ->whereRaw('LOWER(TRIM(subject)) = ?', [strtolower($subjectName)])
                ->get()
                ->keyBy('student_id');

            $studentsData = $students->map(function ($student) use ($marks) {
                $mark = $marks->get($student->id);
                $marksObtained = $mark->marks_obtained ?? null;
                $passingMarks = $mark->passing_marks ?? null;

                return [
                    'id' => $student->id,
                    'student_code' => $student->student_code,
                    'student_name' => $student->student_name,
                    'class' => $student->class,
                    'section' => $student->section,
                    'campus' => $student->campus,
                    'marks_obtained' => $marksObtained,
                    'total_marks' => $mark->total_marks ?? null,
                    'passing_marks' => $passingMarks,
                    'remarks' => $mark->teacher_remarks ?? null,
                    'is_passed' => ($marksObtained !== null && $passingMarks !== null && $passingMarks !== '')
                        ? ((float) $marksObtained >= (float) $passingMarks)
                        : null,
                ];
            })->values();

            return response()->json([
                'success' => true,
                'message' => 'Students list retrieved successfully.',
                'data' => [
                    'filters' => [
                        'campus' => $campusName ?? ($teacher->campus ?? null),
                        'class' => $className,
                        'section' => $sectionName,
                        'exam_name' => $examName,
                        'subject' => $subjectName,
                    ],
                    'total_students' => $studentsData->count(),
                    'students' => $studentsData,
                ],
                'token' => $request->user()->currentAccessToken()->token ?? null,
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
                'token' => null,
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Exam Students By Subject API Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'teacher_id' => $request->user()->id ?? null,
                'request_data' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving students: ' . $e->getMessage(),
                'token' => null,
            ], 500);
        }
    }

    /**
     * List already uploaded exam marks (from StudentMark).
     *
     * This is separate from examStudents (which lists all students).
     * Here we return only marks rows that exist.
     *
     * Query parameters:
     * - exam_name (required): exam name from Exam List (stored in StudentMark.test_name)
     * - class (required)
     * - section (optional)
     * - subject (optional)
     * - campus (optional)
     */
    public function examMarksList(Request $request): JsonResponse
    {
        try {
            $teacher = $request->user();

            if (!$teacher || !$teacher->isTeacher()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Only teachers can access this endpoint.',
                    'token' => null,
                ], 403);
            }

            $validated = $request->validate([
                'exam_name' => ['required', 'string', 'max:255'],
                'class' => ['required', 'string', 'max:255'],
                'section' => ['nullable', 'string', 'max:255'],
                'subject' => ['nullable', 'string', 'max:255'],
                'campus' => ['nullable', 'string', 'max:255'],
            ]);

            $examName = trim((string) $validated['exam_name']);
            $className = trim((string) $validated['class']);
            $sectionName = isset($validated['section']) ? trim((string) $validated['section']) : null;
            $subjectName = isset($validated['subject']) ? trim((string) $validated['subject']) : null;
            $campusName = isset($validated['campus']) ? trim((string) $validated['campus']) : null;

            $teacherName = strtolower(trim($teacher->name ?? ''));

            // Get teacher assigned subjects for requested class/section (optionally campus)
            $assignedSubjectsQuery = \App\Models\Subject::whereRaw('LOWER(TRIM(teacher)) = ?', [$teacherName])
                ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($className))]);

            if (!empty($sectionName)) {
                $assignedSubjectsQuery->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($sectionName))]);
            }
            if (!empty($campusName)) {
                $assignedSubjectsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campusName))]);
            }
                 
            if (!empty($subjectName)) {
                $assignedSubjectsQuery->whereRaw('LOWER(TRIM(subject_name)) = ?', [strtolower(trim($subjectName))]);
            }

            $assignedSubjects = $assignedSubjectsQuery->get();

            if (empty($assignedSubjects) && !empty($subjectName)) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not assigned to this subject/class/section.',
                    'token' => null,
                ], 403);
            }

            $allowedSubjectNames = $assignedSubjects->pluck('subject_name')
                ->filter()
                ->map(fn($s) => trim((string)$s))
                ->unique()
                ->values()
                ->toArray();

            // If subject not provided, but teacher has no assignments for class/section -> no marks
            if (empty($allowedSubjectNames)) {
                return response()->json([
                    'success' => true,
                    'message' => 'No uploaded marks found (teacher has no assigned subjects for this class/section).',
                    'data' => [
                        'exam_name' => $examName,
                        'class' => $className,
                        'section' => $sectionName,
                        'campus' => $campusName,
                        'subject' => $subjectName,
                        'marks' => [],
                        'total_marks_rows' => 0,
                    ],
                    'token' => $request->user()->currentAccessToken()->token ?? null,
                ], 200);
            }

            // Query StudentMark rows
            $marksQuery = \App\Models\StudentMark::query()
                ->whereRaw('LOWER(TRIM(test_name)) = ?', [strtolower(trim($examName))])
                ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($className))]);

            if (!empty($sectionName)) {
                $marksQuery->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($sectionName))]);
            }

            if (!empty($campusName)) {
                $marksQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campusName))]);
            }

            // Case-insensitive allowed subjects match
            $marksQuery->where(function ($q) use ($allowedSubjectNames) {
                foreach ($allowedSubjectNames as $subj) {
                    $q->orWhereRaw('LOWER(TRIM(subject)) = ?', [strtolower(trim($subj))]);
                }
            });

            $marks = $marksQuery->with('student')
                ->get();

            $marksList = $marks->map(function ($mark) {
                $marksObtained = $mark->marks_obtained;
                $passingMarks = $mark->passing_marks;

                $isPassed = null;
                if ($marksObtained !== null && $passingMarks !== null && $passingMarks !== '') {
                    $isPassed = (float)$marksObtained >= (float)$passingMarks;
                }

                return [
                    'student_id' => $mark->student_id,
                    'student_code' => $mark->student->student_code ?? null,
                    'student_name' => $mark->student->student_name ?? null,
                    'class' => $mark->class,
                    'section' => $mark->section,
                    'campus' => $mark->campus,
                    'subject' => $mark->subject,
                    'test_name' => $mark->test_name,
                    'marks_obtained' => $mark->marks_obtained,
                    'total_marks' => $mark->total_marks,
                    'passing_marks' => $mark->passing_marks,
                    'grade' => $mark->grade,
                    'teacher_remarks' => $mark->teacher_remarks,
                    'is_passed' => $isPassed,
                    'marked_at' => $mark->created_at?->format('Y-m-d H:i:s'),
                ];
            })->values();

            return response()->json([
                'success' => true,
                'message' => 'Exam marks list retrieved successfully.',
                'data' => [
                    'exam_name' => $examName,
                    'class' => $className,
                    'section' => $sectionName,
                    'campus' => $campusName,
                    'subject' => $subjectName,
                    'marks' => $marksList,
                    'total_marks_rows' => $marksList->count(),
                ],
                'token' => $request->user()->currentAccessToken()->token ?? null,
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
                'token' => null,
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Exam Marks List API Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'teacher_id' => $request->user()->id ?? null,
                'request_data' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving exam marks: ' . $e->getMessage(),
                'token' => null,
            ], 500);
        }
    }

    /**
     * Save / update exam remarks only (no marks change).
     *
     * Request JSON (bulk):
     * {
     *   "exam_name": "Mid Term Exam",
     *   "class": "one",
     *   "section": "a",
     *   "subject": "English",
     *   "remarks": {
     *      "8": "Very good",
     *      "9": "Needs improvement"
     *   }
     * }
     *
     * Request JSON (single student):
     * {
     *   "exam_name": "Mid Term Exam",
     *   "class": "one",
     *   "section": "a",
     *   "subject": "English",
     *   "student_id": 8,
     *   "remark": "Very good"
     * }
     */
    public function saveExamRemarks(Request $request): JsonResponse
    {
        try {
            $teacher = $request->user();

            if (!$teacher || !$teacher->isTeacher()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Only teachers can access this endpoint.',
                    'token' => null,
                ], 403);
            }

            $validated = $request->validate([
                'exam_name' => ['required', 'string', 'max:255'],
                'class' => ['required', 'string', 'max:255'],
                'section' => ['nullable', 'string', 'max:255'],
                'subject' => ['required', 'string', 'max:255'],
                'campus' => ['nullable', 'string', 'max:255'],
                'student_id' => ['nullable', 'integer', 'exists:students,id'],
                'remark' => ['nullable', 'string', 'max:1000'],
                'remarks' => ['nullable', 'array', 'min:1'],
                'remarks.*' => ['nullable', 'string', 'max:1000'],
            ]);

            $examName = trim((string) $validated['exam_name']);
            $className = trim((string) $validated['class']);
            $sectionName = isset($validated['section']) ? trim((string) $validated['section']) : null;
            $subjectName = trim((string) $validated['subject']);
            $campusName = isset($validated['campus']) ? trim((string) $validated['campus']) : null;

            // Verify class assignment similar to saveExamMarks
            $assignedSubjects = Subject::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($teacher->name ?? ''))])
                ->get();
            $assignedSections = Section::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($teacher->name ?? ''))])
                ->get();

            $assignedClasses = $assignedSubjects->pluck('class')
                ->merge($assignedSections->pluck('class'))
                ->map(function ($class) {
                    return trim($class);
                })
                ->filter(function ($class) {
                    return !empty($class);
                })
                ->unique()
                ->values();

            $isClassAssigned = $assignedClasses->contains(function ($class) use ($className) {
                return strtolower(trim($class)) === strtolower($className);
            });

            if (!$isClassAssigned && $assignedClasses->isNotEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not assigned to this class.',
                    'token' => null,
                ], 403);
            }

            // Accept either single student payload OR old bulk map payload.
            if (!empty($validated['student_id'])) {
                if (!array_key_exists('remark', $validated)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'remark is required when student_id is provided.',
                        'token' => null,
                    ], 422);
                }
                $remarksPayload = [
                    (string) $validated['student_id'] => $validated['remark'],
                ];
            } else {
                $remarksPayload = $validated['remarks'] ?? [];
                if (empty($remarksPayload)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Please provide either student_id + remark or remarks object.',
                        'token' => null,
                    ], 422);
                }
            }

            $updated = 0;
            $created = 0;
            $errors = [];

            foreach ($remarksPayload as $studentId => $remarkText) {
                if (!$studentId) {
                    continue;
                }

                $student = Student::find($studentId);
                if (!$student) {
                    $errors[] = "Student with ID {$studentId} not found.";
                    continue;
                }

                if (strtolower(trim($student->class ?? '')) !== strtolower($className)) {
                    $errors[] = "Student {$student->student_name} (ID: {$studentId}) does not belong to class {$className}.";
                    continue;
                }

                if (!empty($sectionName) && strtolower(trim($student->section ?? '')) !== strtolower($sectionName)) {
                    $errors[] = "Student {$student->student_name} (ID: {$studentId}) does not belong to section {$sectionName}.";
                    continue;
                }
                if (!empty($campusName) && strtolower(trim($student->campus ?? '')) !== strtolower($campusName)) {
                    $errors[] = "Student {$student->student_name} (ID: {$studentId}) does not belong to campus {$campusName}.";
                    continue;
                }

                $campus = $student->campus ?? ($teacher->campus ?? null);

                // Look for existing mark row for this exam/subject
                $mark = StudentMark::where('student_id', $studentId)
                    ->whereRaw('LOWER(TRIM(test_name)) = ?', [strtolower($examName)])
                    ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower($className)])
                    ->whereRaw('LOWER(TRIM(subject)) = ?', [strtolower($subjectName)])
                    ->when(!empty($sectionName), function ($q) use ($sectionName) {
                        return $q->whereRaw('LOWER(TRIM(section)) = ?', [strtolower($sectionName)]);
                    })
                    ->first();

                if ($mark) {
                    $mark->update([
                        'teacher_remarks' => $remarkText,
                    ]);
                    $updated++;
                } else {
                    StudentMark::create([
                        'student_id' => $studentId,
                        'test_name' => $examName,
                        'campus' => $campus,
                        'class' => $className,
                        'section' => $sectionName,
                        'subject' => $subjectName,
                        'marks_obtained' => null,
                        'total_marks' => null,
                        'passing_marks' => null,
                        'teacher_remarks' => $remarkText,
                    ]);
                    $created++;
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Exam remarks saved (updated: {$updated}, created: {$created}).",
                'data' => [
                    'campus' => $campusName ?? ($teacher->campus ?? null),
                    'exam_name' => $examName,
                    'class' => $className,
                    'section' => $sectionName,
                    'subject' => $subjectName,
                    'updated_count' => $updated,
                    'created_count' => $created,
                    'errors' => !empty($errors) ? $errors : null,
                ],
                'token' => $request->user()->currentAccessToken()->token ?? null,
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
                'token' => null,
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Save Exam Remarks API Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'teacher_id' => $request->user()->id ?? null,
                'request_data' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while saving exam remarks: ' . $e->getMessage(),
                'token' => null,
            ], 500);
        }
    }

    /**
     * Get subjects for a given exam + class (+ section) that this teacher can use.
     *
     * Mostly used to build subject dropdown after selecting class/section/exam.
     * By default it returns all subjects for the given class/section.
     * Pass teacher_only=true to restrict to logged-in teacher assigned subjects.
     *
     * Query params:
     * - exam_name (required)  -> currently only echoed back (for context)
     * - class (required)
     * - section (optional)
     * - campus (optional)
     */
    public function examSubjects(Request $request): JsonResponse
    {
        try {
            $teacher = $request->user();

            if (!$teacher || !$teacher->isTeacher()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Only teachers can access this endpoint.',
                    'token' => null,
                ], 403);
            }

            $validated = $request->validate([
                'exam_name' => ['required', 'string', 'max:255'],
                'class' => ['required', 'string', 'max:255'],
                'section' => ['nullable', 'string', 'max:255'],
                'campus' => ['nullable', 'string', 'max:255'],
                'teacher_only' => ['nullable', 'boolean'],
            ]);

            $examName = trim((string) $validated['exam_name']);
            $className = trim((string) $validated['class']);
            $sectionName = isset($validated['section']) ? trim((string) $validated['section']) : null;
            $campusName = isset($validated['campus']) ? trim((string) $validated['campus']) : null;
            // Default campus to teacher's campus to mirror web filtering and avoid cross-campus subjects
            if (empty($campusName) && !empty($teacher->campus)) {
                $campusName = trim((string) $teacher->campus);
            }

            $teacherOnly = filter_var($request->query('teacher_only', false), FILTER_VALIDATE_BOOLEAN);
            $teacherTokens = collect([
                strtolower(trim($teacher->name ?? '')),
                strtolower(trim((string) ($teacher->emp_id ?? ''))),
            ])->filter()->unique()->values();

            $isClassTeacher = Section::query()
                ->where(function ($query) use ($teacherTokens) {
                    foreach ($teacherTokens as $token) {
                        $query->orWhereRaw('LOWER(TRIM(teacher)) = ?', [$token]);
                    }
                })
                ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower($className)])
                ->when(!empty($sectionName), function ($query) use ($sectionName) {
                    $query->whereRaw('LOWER(TRIM(name)) = ?', [strtolower($sectionName)]);
                })
                ->when(!empty($campusName), function ($query) use ($campusName) {
                    $query->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower($campusName)]);
                })
                ->exists();

            // Strict mode: return only subjects that were added for selected test/exam_name.
            // Exams use ExamTimetable, while tests use Test, so support both sources.
            $examSubjectsQuery = ExamTimetable::query()
                ->whereRaw('LOWER(TRIM(exam_name)) = ?', [strtolower($examName)])
                ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower($className)])
                ->whereNotNull('subject');

            if (!empty($sectionName)) {
                $examSubjectsQuery->whereRaw('LOWER(TRIM(section)) = ?', [strtolower($sectionName)]);
            }
            if (!empty($campusName)) {
                $examSubjectsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower($campusName)]);
            }

            $examSubjectNames = $examSubjectsQuery
                ->pluck('subject')
                ->map(fn($s) => trim((string) $s))
                ->filter(fn($s) => $s !== '')
                ->unique()
                ->values();
            $examSubjectKeys = $examSubjectNames
                ->map(fn($s) => strtolower($s))
                ->values();

            $testSubjectsQuery = Test::query()
                ->whereRaw('LOWER(TRIM(test_name)) = ?', [strtolower($examName)])
                ->whereRaw('LOWER(TRIM(for_class)) = ?', [strtolower($className)])
                ->whereNotNull('subject');

            if (!empty($sectionName)) {
                $testSubjectsQuery->whereRaw('LOWER(TRIM(section)) = ?', [strtolower($sectionName)]);
            }
            if (!empty($campusName)) {
                $testSubjectsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower($campusName)]);
            }

            $testSubjectNames = $testSubjectsQuery
                ->pluck('subject')
                ->map(fn($s) => trim((string) $s))
                ->filter(fn($s) => $s !== '')
                ->unique()
                ->values();
            $testSubjectKeys = $testSubjectNames
                ->map(fn($s) => strtolower($s))
                ->values();

            $allowedSubjectKeys = $examSubjectKeys
                ->merge($testSubjectKeys)
                ->unique()
                ->values();
            $allowedSubjectNames = $examSubjectNames
                ->merge($testSubjectNames)
                ->unique()
                ->values();

            // Teacher assignment filter: keep only timetable subjects assigned to logged-in teacher.
            $subjectsQuery = Subject::whereRaw('LOWER(TRIM(class)) = ?', [strtolower($className)])
                ->whereNotNull('subject_name')
                ->whereNotNull('class')
                ->where(function ($query) use ($teacherTokens) {
                    foreach ($teacherTokens as $token) {
                        $query->orWhereRaw('LOWER(TRIM(teacher)) = ?', [$token]);
                    }
                });

            if (!empty($sectionName)) {
                $subjectsQuery->whereRaw('LOWER(TRIM(section)) = ?', [strtolower($sectionName)]);
                $subjectsQuery->whereNotNull('section');
            }
            if (!empty($campusName)) {
                $subjectsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower($campusName)]);
            }

            $subjects = $subjectsQuery
                ->orderBy('subject_name', 'asc')
                ->pluck('subject_name')
                ->map(fn($s) => trim((string) $s))
                ->filter(fn($s) => $s !== '')
                ->unique()
                ->filter(fn($s) => $allowedSubjectKeys->isEmpty() || $allowedSubjectKeys->contains(strtolower($s)))
                ->values()
                ->toArray();

            if ($isClassTeacher && $allowedSubjectNames->isNotEmpty()) {
                $subjects = $allowedSubjectNames->toArray();
            }

            $subjectOptions = collect($subjects)->map(function ($subject) {
                return [
                    'subject' => $subject,
                    'subject_name' => $subject,
                    'name' => $subject,
                ];
            })->values()->toArray();

            $hasStartingTimeColumn = Schema::hasColumn('exam_timetables', 'starting_time');
            $hasEndingTimeColumn = Schema::hasColumn('exam_timetables', 'ending_time');
            $hasStartTimeColumn = Schema::hasColumn('exam_timetables', 'start_time');
            $hasEndTimeColumn = Schema::hasColumn('exam_timetables', 'end_time');
            $hasTotalMarksColumn = Schema::hasColumn('exam_timetables', 'total_marks');
            $hasPassingMarksColumn = Schema::hasColumn('exam_timetables', 'passing_marks');

            $timetableRows = ExamTimetable::query()
                ->whereRaw('LOWER(TRIM(exam_name)) = ?', [strtolower($examName)])
                ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower($className)])
                ->when(!empty($sectionName), function ($q) use ($sectionName) {
                    $q->whereRaw('LOWER(TRIM(section)) = ?', [strtolower($sectionName)]);
                })
                ->when(!empty($campusName), function ($q) use ($campusName) {
                    $q->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower($campusName)]);
                })
                ->orderBy('exam_date', 'asc')
                ->orderBy('id', 'asc')
                ->get()
                ->map(function ($row) use ($hasStartingTimeColumn, $hasEndingTimeColumn, $hasStartTimeColumn, $hasEndTimeColumn, $hasTotalMarksColumn, $hasPassingMarksColumn) {
                    $startTime = $hasStartingTimeColumn ? ($row->starting_time ?? null) : ($hasStartTimeColumn ? ($row->start_time ?? null) : null);
                    $endTime = $hasEndingTimeColumn ? ($row->ending_time ?? null) : ($hasEndTimeColumn ? ($row->end_time ?? null) : null);

                    return [
                        'id' => $row->id,
                        'campus' => $row->campus,
                        'exam_name' => $row->exam_name,
                        'class' => $row->class,
                        'section' => $row->section,
                        'subject' => $row->subject,
                        'exam_date' => $row->exam_date ? Carbon::parse($row->exam_date)->format('Y-m-d') : null,
                        'exam_date_formatted' => $row->exam_date ? Carbon::parse($row->exam_date)->format('d M Y') : null,
                        'start_time' => $startTime,
                        'starting_time' => $startTime,
                        'end_time' => $endTime,
                        'ending_time' => $endTime,
                        'total_marks' => $hasTotalMarksColumn ? ($row->total_marks ?? null) : null,
                        'passing_marks' => $hasPassingMarksColumn ? ($row->passing_marks ?? null) : null,
                    ];
                })
                ->values()
                ->toArray();

            $timetableSubjects = collect($timetableRows)
                ->pluck('subject')
                ->map(fn ($subject) => trim((string) $subject))
                ->filter(fn ($subject) => $subject !== '')
                ->unique()
                ->values()
                ->toArray();

            if (!$teacherOnly) {
                $subjects = $timetableSubjects;
                $subjectOptions = collect($subjects)->map(function ($subject) {
                    return [
                        'subject' => $subject,
                        'subject_name' => $subject,
                        'name' => $subject,
                    ];
                })->values()->toArray();
            }

            return response()->json([
                'success' => true,
                'message' => 'Exam subjects retrieved successfully.',
                'data' => [
                    'exam_name' => $examName,
                    'class' => $className,
                    'section' => $sectionName,
                    'campus' => $campusName,
                    'all_subjects' => false,
                    'subjects' => $subjects,
                    'subject_options' => $subjectOptions,
                    'timetable_subjects' => $timetableSubjects,
                    'timetable_exists' => !empty($timetableRows),
                    'timetable' => $timetableRows,
                ],
                'token' => $request->user()->currentAccessToken()->token ?? null,
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
                'token' => null,
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Exam Subjects API Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'teacher_id' => $request->user()->id ?? null,
                'request_data' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving exam subjects: ' . $e->getMessage(),
                'token' => null,
            ], 500);
        }
    }

    /**
     * Get students list for "Teacher Remarks - Final Result".
     *
     * Required query params:
     * - session
     * - class
     *
     * Optional query params:
     * - section
     * - campus (optional override; otherwise teacher token campus is used)
     */
    public function finalResultStudents(Request $request): JsonResponse
    {
        try {
            $teacher = $request->user();

            if (!$teacher || !$teacher->isTeacher()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Only teachers can access this endpoint.',
                    'token' => null,
                ], 403);
            }

            $validated = $request->validate([
                'session' => ['required', 'string', 'max:255'],
                'class' => ['required', 'string', 'max:255'],
                'section' => ['nullable', 'string', 'max:255'],
                'campus' => ['nullable', 'string', 'max:255'],
            ]);

            $sessionName = trim((string) $validated['session']);
            $className = trim((string) $validated['class']);
            $sectionName = isset($validated['section']) ? trim((string) $validated['section']) : null;
            $campusName = isset($validated['campus']) ? trim((string) $validated['campus']) : null;
            if (empty($campusName)) {
                $campusName = trim((string) ($teacher->campus ?? ''));
            }

            // Verify class assignment (same pattern as other teacher exam APIs)
            $teacherTokens = collect([
                strtolower(trim((string) ($teacher->name ?? ''))),
                strtolower(trim((string) ($teacher->emp_id ?? ''))),
            ])->filter()->unique()->values();

            $assignedSubjects = Subject::query()
                ->where(function ($query) use ($teacherTokens) {
                    foreach ($teacherTokens as $token) {
                        $query->orWhereRaw('LOWER(TRIM(teacher)) = ?', [$token]);
                    }
                })
                ->get();
            $assignedSections = Section::query()
                ->where(function ($query) use ($teacherTokens) {
                    foreach ($teacherTokens as $token) {
                        $query->orWhereRaw('LOWER(TRIM(teacher)) = ?', [$token]);
                    }
                })
                ->get();
            $assignedClasses = $assignedSubjects->pluck('class')
                ->merge($assignedSections->pluck('class'))
                ->map(fn($class) => trim((string) $class))
                ->filter(fn($class) => !empty($class))
                ->unique()
                ->values();

            $isClassAssigned = $assignedClasses->contains(function ($class) use ($className) {
                return strtolower(trim((string) $class)) === strtolower($className);
            });

            if (!$isClassAssigned && $assignedClasses->isNotEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not assigned to this class.',
                    'token' => null,
                ], 403);
            }

            // Match web logic: fetch session exams without campus restriction.
            // Some exam rows may not have campus, and filtering by campus can
            // wrongly drop valid exams which then makes total/obtained incorrect.
            $examNames = Exam::query()
                ->whereRaw('LOWER(TRIM(session)) = ?', [strtolower($sessionName)])
                ->whereNotNull('exam_name')
                ->pluck('exam_name')
                ->map(fn($name) => trim((string) $name))
                ->filter(fn($name) => !empty($name))
                ->unique()
                ->values();
            $examNameKeys = $examNames->map(fn($name) => strtolower(trim((string) $name)))->values();

            // Students in class/section/campus
            $students = Student::query()
                ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower($className)])
                ->when(!empty($sectionName), function ($q) use ($sectionName) {
                    return $q->whereRaw('LOWER(TRIM(section)) = ?', [strtolower($sectionName)]);
                })
                ->when(!empty($campusName), function ($q) use ($campusName) {
                    return $q->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower($campusName)]);
                })
                ->orderBy('student_code', 'asc')
                ->orderBy('student_name', 'asc')
                ->get();

            // Preload marks for these students from session exams, then aggregate in-memory
            $marks = collect();
            if ($students->isNotEmpty() && $examNames->isNotEmpty()) {
                $marks = StudentMark::query()
                    ->whereIn('student_id', $students->pluck('id')->toArray())
                    ->whereIn(\DB::raw('LOWER(TRIM(test_name))'), $examNameKeys->toArray())
                    ->whereRaw('LOWER(TRIM(test_name)) != ?', ['final_result'])
                    ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower($className)])
                    ->when(!empty($sectionName), function ($q) use ($sectionName) {
                        return $q->whereRaw('LOWER(TRIM(section)) = ?', [strtolower($sectionName)]);
                    })
                    ->when(!empty($campusName), function ($q) use ($campusName) {
                        return $q->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower($campusName)]);
                    })
                    ->get();
            }

            // Final result remarks: only dedicated FINAL_RESULT rows (same as web).
            $finalRemarkRows = collect();
            if ($students->isNotEmpty()) {
                $finalRemarkRows = StudentMark::query()
                    ->whereIn('student_id', $students->pluck('id')->toArray())
                    ->whereRaw('LOWER(TRIM(test_name)) = ?', ['final_result'])
                    ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower($className)])
                    ->when(!empty($sectionName), function ($q) use ($sectionName) {
                        return $q->whereRaw('LOWER(TRIM(section)) = ?', [strtolower($sectionName)]);
                    })
                    ->when(!empty($campusName), function ($q) use ($campusName) {
                        return $q->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower($campusName)]);
                    })
                    ->orderByDesc('updated_at')
                    ->orderByDesc('id')
                    ->get()
                    ->groupBy('student_id')
                    ->map(fn ($rows) => $rows->first());
            }

            $studentsData = $students->map(function ($student) use ($marks, $finalRemarkRows) {
                $studentMarks = $marks
                    ->where('student_id', $student->id)
                    ->filter(fn ($mark) => strtolower(trim((string) ($mark->test_name ?? ''))) !== 'final_result');

                $roll = $student->student_code ?? ($student->gr_number ?? null);

                $finalRemarkRow = $finalRemarkRows->get($student->id);
                $finalRemarkText = $finalRemarkRow
                    ? trim((string) ($finalRemarkRow->teacher_remarks ?? ''))
                    : '';
                $finalRemarkText = $finalRemarkText !== '' ? $finalRemarkText : null;

                return [
                    'student_id' => $student->id,
                    'student_code' => $student->student_code ?? null,
                    // For UI columns: Roll / Name / Parent / Total / Obtained
                    'roll' => $roll,
                    'roll_number' => $roll,
                    'name' => $student->student_name,
                    'parent' => $student->father_name ?? null,
                    'final_remark' => $finalRemarkText,
                    'remark' => $finalRemarkText,
                    'has_remark' => $finalRemarkText !== null,
                    'remark_uploaded' => $finalRemarkText !== null,
                    'total' => (float) ($studentMarks->sum('total_marks') ?? 0),
                    'obtained' => (float) ($studentMarks->sum('marks_obtained') ?? 0),
                ];
            })->values();

            return response()->json([
                'success' => true,
                'message' => 'Final result students retrieved successfully.',
                'data' => [
                    'filters' => [
                        'campus' => $campusName ?: null,
                        'session' => $sessionName,
                        'class' => $className,
                        'section' => $sectionName,
                    ],
                    'total_students' => $studentsData->count(),
                    'students' => $studentsData,
                ],
                'token' => $request->user()->currentAccessToken()->token ?? null,
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
                'token' => null,
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Final Result Students API Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'teacher_id' => $request->user()->id ?? null,
                'request_data' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving final result students: ' . $e->getMessage(),
                'token' => null,
            ], 500);
        }
    }

    /**
     * Save teacher remarks for final result (single or bulk).
     *
     * Single payload:
     * {
     *   "session": "2026/2027",
     *   "class": "One",
     *   "section": "A",
     *   "student_id": 8,
     *   "remark": "Good progress"
     * }
     *
     * Bulk payload:
     * {
     *   "session": "2026/2027",
     *   "class": "One",
     *   "section": "A",
     *   "remarks": {
     *      "8": "Good progress",
     *      "9": "Needs improvement"
     *   }
     * }
     */
    public function saveFinalResultRemarks(Request $request): JsonResponse
    {
        try {
            $teacher = $request->user();

            if (!$teacher || !$teacher->isTeacher()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Only teachers can access this endpoint.',
                    'token' => null,
                ], 403);
            }

            $validated = $request->validate([
                'session' => ['required', 'string', 'max:255'],
                'class' => ['required', 'string', 'max:255'],
                'section' => ['nullable', 'string', 'max:255'],
                'campus' => ['nullable', 'string', 'max:255'],
                'student_id' => ['nullable', 'integer', 'exists:students,id'],
                'remark' => ['nullable', 'string', 'max:1000'],
                'remarks' => ['nullable', 'array', 'min:1'],
                'remarks.*' => ['nullable', 'string', 'max:1000'],
            ]);

            $sessionName = trim((string) $validated['session']);
            $className = trim((string) $validated['class']);
            $sectionName = isset($validated['section']) ? trim((string) $validated['section']) : null;
            $campusName = isset($validated['campus']) ? trim((string) $validated['campus']) : null;
            if (empty($campusName)) {
                $campusName = trim((string) ($teacher->campus ?? ''));
            }

            // Verify teacher is assigned to this class.
            $teacherTokens = collect([
                strtolower(trim((string) ($teacher->name ?? ''))),
                strtolower(trim((string) ($teacher->emp_id ?? ''))),
            ])->filter()->unique()->values();

            $assignedSubjects = Subject::query()
                ->where(function ($query) use ($teacherTokens) {
                    foreach ($teacherTokens as $token) {
                        $query->orWhereRaw('LOWER(TRIM(teacher)) = ?', [$token]);
                    }
                })
                ->get();
            $assignedSections = Section::query()
                ->where(function ($query) use ($teacherTokens) {
                    foreach ($teacherTokens as $token) {
                        $query->orWhereRaw('LOWER(TRIM(teacher)) = ?', [$token]);
                    }
                })
                ->get();
            $assignedClasses = $assignedSubjects->pluck('class')
                ->merge($assignedSections->pluck('class'))
                ->map(fn($class) => trim((string) $class))
                ->filter(fn($class) => !empty($class))
                ->unique()
                ->values();

            $isClassAssigned = $assignedClasses->contains(function ($class) use ($className) {
                return strtolower(trim((string) $class)) === strtolower($className);
            });

            if (!$isClassAssigned && $assignedClasses->isNotEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not assigned to this class.',
                    'token' => null,
                ], 403);
            }

            // Accept either single student payload or bulk remarks payload.
            if (!empty($validated['student_id'])) {
                if (!array_key_exists('remark', $validated)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'remark is required when student_id is provided.',
                        'token' => null,
                    ], 422);
                }
                $remarksPayload = [
                    (string) $validated['student_id'] => $validated['remark'],
                ];
            } else {
                $remarksPayload = $validated['remarks'] ?? [];
                if (empty($remarksPayload)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Please provide either student_id + remark or remarks object.',
                        'token' => null,
                    ], 422);
                }
            }

            $updated = 0;
            $created = 0;
            $errors = [];

            foreach ($remarksPayload as $studentId => $remarkText) {
                if (!$studentId) {
                    continue;
                }

                $student = Student::find($studentId);
                if (!$student) {
                    $errors[] = "Student with ID {$studentId} not found.";
                    continue;
                }

                if (strtolower(trim((string) ($student->class ?? ''))) !== strtolower($className)) {
                    $errors[] = "Student {$student->student_name} (ID: {$studentId}) does not belong to class {$className}.";
                    continue;
                }

                if (!empty($sectionName) && strtolower(trim((string) ($student->section ?? ''))) !== strtolower($sectionName)) {
                    $errors[] = "Student {$student->student_name} (ID: {$studentId}) does not belong to section {$sectionName}.";
                    continue;
                }

                if (!empty($campusName) && strtolower(trim((string) ($student->campus ?? ''))) !== strtolower($campusName)) {
                    $errors[] = "Student {$student->student_name} (ID: {$studentId}) does not belong to campus {$campusName}.";
                    continue;
                }

                // Final result remarks are stored in dedicated FINAL_RESULT row.
                $mark = StudentMark::where('student_id', $studentId)
                    ->whereRaw('LOWER(TRIM(test_name)) = ?', ['final_result'])
                    ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower($className)])
                    ->when(!empty($sectionName), function ($q) use ($sectionName) {
                        return $q->whereRaw('LOWER(TRIM(section)) = ?', [strtolower($sectionName)]);
                    })
                    ->when(!empty($campusName), function ($q) use ($campusName) {
                        return $q->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower($campusName)]);
                    })
                    ->first();

                if ($mark) {
                    $mark->update([
                        'teacher_remarks' => $remarkText,
                    ]);
                    $updated++;
                } else {
                    StudentMark::create([
                        'student_id' => $studentId,
                        'test_name' => 'FINAL_RESULT',
                        'campus' => $campusName ?: ($student->campus ?? null),
                        'class' => $className,
                        'section' => $sectionName,
                        'subject' => null,
                        'marks_obtained' => null,
                        'total_marks' => null,
                        'passing_marks' => null,
                        'teacher_remarks' => $remarkText,
                    ]);
                    $created++;
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Final result remarks saved (updated: {$updated}, created: {$created}).",
                'data' => [
                    'session' => $sessionName,
                    'campus' => $campusName ?: null,
                    'class' => $className,
                    'section' => $sectionName,
                    'updated_count' => $updated,
                    'created_count' => $created,
                    'errors' => !empty($errors) ? $errors : null,
                ],
                'token' => $request->user()->currentAccessToken()->token ?? null,
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
                'token' => null,
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Save Final Result Remarks API Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'teacher_id' => $request->user()->id ?? null,
                'request_data' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while saving final result remarks: ' . $e->getMessage(),
                'token' => null,
            ], 500);
        }
    }

    /**
     * Save Exam Marks
     * 
     * Saves exam marks (obtained, total, passing) for students
     * NOTE: This API saves EXAM marks, which are separate from TEST marks
     * - Exam marks: Created by super admin in "Exam List" page (e.g., "Mid Term Exam", "Final Exam")
     * - Test marks: Created by teachers in "Test Management" (e.g., "Unit Test 1", "Quiz 1")
     * Both are stored in StudentMark table, differentiated by test_name field
     * 
     * Request Format:
     * {
     *   "exam_name": "Mid Term Exam",
     *   "class": "ten",
     *   "section": "a",
     *   "subject": "English",
     *   "marks": {
     *     "8": {
     *       "obtained": 85,
     *       "total": 100,
     *       "passing": 33
     *     }
     *   }
     * }
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function saveExamMarks(Request $request): JsonResponse
    {
        try {
            $teacher = $request->user();

            if (!$teacher || !$teacher->isTeacher()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Only teachers can access this endpoint.',
                    'token' => null,
                ], 403);
            }

            // Validate request
            // NOTE: exam_name is the exam name from Exam List (e.g., "Mid Term Exam")
            // This is different from test_name used in Test Management
            $validated = $request->validate([
                'exam_name' => ['required', 'string', 'max:255'],
                'class' => ['required', 'string', 'max:255'],
                'section' => ['nullable', 'string', 'max:255'],
                'subject' => ['required', 'string', 'max:255'],
                // Legacy flag only (ignored); re-upload/update is always allowed.
                'prevent_duplicate' => ['nullable', 'boolean'],
                'marks' => ['required', 'array', 'min:1'],
                'marks.*.obtained' => ['nullable', 'numeric', 'min:0'],
                'marks.*.total' => ['nullable', 'numeric', 'min:0'],
                'marks.*.passing' => ['nullable', 'numeric', 'min:0'],
            ], [
                'exam_name.required' => 'Exam name is required.',
                'class.required' => 'Class is required.',
                'subject.required' => 'Subject is required.',
                'marks.required' => 'Please enter marks for at least one student.',
                'marks.min' => 'Please enter marks for at least one student.',
            ]);

            // Verify teacher is assigned to this class
            $assignedSubjects = Subject::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($teacher->name ?? ''))])
                ->get();

            $assignedSections = Section::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($teacher->name ?? ''))])
                ->get();

            $assignedClasses = $assignedSubjects->pluck('class')
                ->merge($assignedSections->pluck('class'))
                ->map(function($class) {
                    return trim($class);
                })
                ->filter(function($class) {
                    return !empty($class);
                })
                ->unique()
                ->values();

            $examName = trim((string) $validated['exam_name']);
            $className = trim((string) $validated['class']);
            $sectionName = isset($validated['section']) && $validated['section'] !== null
                ? trim((string) $validated['section'])
                : null;
            $subjectName = trim((string) $validated['subject']);
            $isClassAssigned = $assignedClasses->contains(function($class) use ($className) {
                return strtolower(trim($class)) === strtolower($className);
            });

            if (!$isClassAssigned && $assignedClasses->isNotEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not assigned to this class.',
                    'token' => null,
                ], 403);
            }

            // Allow incremental upload:
            // already uploaded students are skipped later in the loop,
            // while new students are still saved.

            // Check if marks array has valid data
            $hasValidMarks = false;
            foreach ($validated['marks'] as $studentId => $markData) {
                if (isset($markData['obtained']) || isset($markData['total']) || isset($markData['passing'])) {
                    $hasValidMarks = true;
                    break;
                }
            }

            if (!$hasValidMarks) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please enter at least one mark (obtained, total, or passing) for at least one student.',
                    'token' => null,
                ], 400);
            }

            // Validate mark relationships per student.
            // Rules:
            // - obtained marks cannot be greater than total marks
            // - passing marks cannot be greater than total marks
            $markRelationErrors = [];
            foreach ($validated['marks'] as $studentId => $markData) {
                $obtained = array_key_exists('obtained', $markData) && $markData['obtained'] !== '' && $markData['obtained'] !== null
                    ? (float) $markData['obtained']
                    : null;
                $total = array_key_exists('total', $markData) && $markData['total'] !== '' && $markData['total'] !== null
                    ? (float) $markData['total']
                    : null;
                $passing = array_key_exists('passing', $markData) && $markData['passing'] !== '' && $markData['passing'] !== null
                    ? (float) $markData['passing']
                    : null;

                if ($total !== null) {
                    if ($obtained !== null && $obtained > $total) {
                        $markRelationErrors["marks.{$studentId}.obtained"][] = 'Obtained marks cannot be greater than total marks.';
                    }
                    if ($passing !== null && $passing > $total) {
                        $markRelationErrors["marks.{$studentId}.passing"][] = 'Passing marks cannot be greater than total marks.';
                    }
                }
            }

            if (!empty($markRelationErrors)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $markRelationErrors,
                    'token' => null,
                ], 422);
            }

            // Get campus from first student
            $firstStudentId = array_key_first($validated['marks']);
            $firstStudent = Student::find($firstStudentId);
            $campus = $firstStudent ? $firstStudent->campus : ($teacher->campus ?? '');

            $savedCount = 0;
            $updatedCount = 0;
            $skippedCount = 0;
            $errors = [];
            $preventDuplicate = false;

            // Save or update marks for each student
            foreach ($validated['marks'] as $studentId => $markData) {
                if (!$studentId) {
                    continue;
                }

                $student = Student::find($studentId);
                if (!$student) {
                    $errors[] = "Student with ID {$studentId} not found.";
                    continue;
                }

                // Verify student belongs to the specified class and section
                if (strtolower(trim($student->class ?? '')) !== strtolower($className)) {
                    $errors[] = "Student {$student->student_name} (ID: {$studentId}) does not belong to class {$className}.";
                    continue;
                }

                if (isset($validated['section']) && !empty($validated['section'])) {
                    if (strtolower(trim($student->section ?? '')) !== strtolower(trim($validated['section']))) {
                        $errors[] = "Student {$student->student_name} (ID: {$studentId}) does not belong to section {$validated['section']}.";
                        continue;
                    }
                }

                $campus = $student->campus ?? $campus;

                // Only save if at least one mark field has a value
                if (isset($markData['obtained']) || isset($markData['total']) || isset($markData['passing'])) {
                    // Check if exam marks record exists for this student
                    // NOTE: test_name field stores exam_name for exams (e.g., "Mid Term Exam")
                    // This is different from test marks which use test names (e.g., "Unit Test 1")
                    $existingMark = StudentMark::where('student_id', $studentId)
                        ->whereRaw('LOWER(TRIM(test_name)) = ?', [strtolower($examName)]) // exam_name stored in test_name field
                        ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower($className)])
                        ->whereRaw('LOWER(TRIM(subject)) = ?', [strtolower($subjectName)])
                        ->when(!empty($sectionName), function($q) use ($sectionName) {
                            return $q->whereRaw('LOWER(TRIM(section)) = ?', [strtolower($sectionName)]);
                        })
                        ->first();

                    $isUpdate = $existingMark !== null;
                    $existingHasNumericMarks = $existingMark
                        && (
                            ($existingMark->marks_obtained !== null && trim((string) $existingMark->marks_obtained) !== '')
                            || ($existingMark->total_marks !== null && trim((string) $existingMark->total_marks) !== '')
                            || ($existingMark->passing_marks !== null && trim((string) $existingMark->passing_marks) !== '')
                        );

                    // Save exam marks in StudentMark table
                    // IMPORTANT: exam_name is stored in test_name field
                    // Exam marks and test marks are differentiated by the value in test_name field
                    StudentMark::updateOrCreate(
                        [
                            'student_id' => $studentId,
                            'test_name' => $examName, // Exam name stored here (e.g., "Mid Term Exam")
                            'campus' => $campus,
                            'class' => $className,
                            'section' => $sectionName,
                            'subject' => $subjectName,
                        ],
                        [
                            'marks_obtained' => isset($markData['obtained']) && $markData['obtained'] !== '' && $markData['obtained'] !== null 
                                ? $markData['obtained'] 
                                : null,
                            'total_marks' => isset($markData['total']) && $markData['total'] !== '' && $markData['total'] !== null 
                                ? $markData['total'] 
                                : null,
                            'passing_marks' => isset($markData['passing']) && $markData['passing'] !== '' && $markData['passing'] !== null 
                                ? $markData['passing'] 
                                : null,
                        ]
                    );

                    if ($isUpdate) {
                        $updatedCount++;
                    } else {
                        $savedCount++;
                    }
                }
            }

            $totalProcessed = $savedCount + $updatedCount;

            if ($totalProcessed > 0) {
                return response()->json([
                    'success' => true,
                    'message' => "Exam marks saved successfully for {$totalProcessed} student(s)!",
                    'data' => [
                        'exam_name' => $examName,
                        'class' => $className,
                        'section' => $sectionName,
                        'subject' => $subjectName,
                        'saved_count' => $savedCount,
                        'updated_count' => $updatedCount,
                        'skipped_count' => 0,
                        'total_processed' => $totalProcessed,
                        'errors' => !empty($errors) ? $errors : null,
                    ],
                    'token' => $request->user()->currentAccessToken()->token ?? null,
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'No marks were saved. Please enter at least one mark value.',
                    'data' => [
                        'errors' => $errors,
                    ],
                    'token' => null,
                ], 400);
            }

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
                'token' => null,
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Save Exam Marks API Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'teacher_id' => $request->user()->id ?? null,
                'request_data' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while saving exam marks: ' . $e->getMessage(),
                'token' => null,
            ], 500);
        }
    }
}
