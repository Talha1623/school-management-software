<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentAttendance;
use App\Models\ClassModel;
use App\Models\Exam;
use App\Models\StudentMark;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

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

            // Get classes and sections from teacher's assigned subjects
            $assignedSubjects = Subject::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($teacher->name ?? ''))])
                ->get();

            // Get classes and sections from teacher's assigned sections
            $assignedSections = Section::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($teacher->name ?? ''))])
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
                $sectionsFromSubjects = $assignedSubjects
                    ->where('class', $className)
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
                $sectionsFromSections = $assignedSections
                    ->where('class', $className)
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
                    
                    $classesData[] = [
                        'class' => $className,
                        'section' => trim($section),
                        'formatted_sections' => $formattedSection,
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

            $query = Exam::query();

            // Filter by campus (if teacher has campus assigned)
            if ($teacher->campus) {
                $query->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($teacher->campus))]);
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
            if ($request->filled('upcoming_only') && $request->upcoming_only == '1') {
                $query->whereDate('exam_date', '>=', Carbon::today()->format('Y-m-d'));
            }

            // Filter by past exams only
            if ($request->filled('past_only') && $request->past_only == '1') {
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

            // Format exam data
            $examsData = $exams->map(function($exam) {
                return [
                    'id' => $exam->id,
                    'exam_name' => $exam->exam_name,
                    'campus' => $exam->campus,
                    'description' => $exam->description,
                    'exam_date' => $exam->exam_date ? Carbon::parse($exam->exam_date)->format('Y-m-d') : null,
                    'exam_date_formatted' => $exam->exam_date ? Carbon::parse($exam->exam_date)->format('d M Y') : null,
                    'session' => $exam->session,
                    'created_at' => $exam->created_at ? Carbon::parse($exam->created_at)->format('Y-m-d H:i:s') : null,
                    'created_at_formatted' => $exam->created_at ? Carbon::parse($exam->created_at)->format('d M Y, h:i A') : null,
                    'is_upcoming' => $exam->exam_date ? Carbon::parse($exam->exam_date)->isFuture() : false,
                    'is_past' => $exam->exam_date ? Carbon::parse($exam->exam_date)->isPast() : false,
                    'is_today' => $exam->exam_date ? Carbon::parse($exam->exam_date)->isToday() : false,
                ];
            });

            // Get filter options for frontend
            $filterOptions = [
                'campuses' => Exam::whereNotNull('campus')
                    ->distinct()
                    ->pluck('campus')
                    ->sort()
                    ->values(),
                'sessions' => Exam::whereNotNull('session')
                    ->distinct()
                    ->pluck('session')
                    ->sort()
                    ->values(),
                'exam_names' => Exam::whereNotNull('exam_name')
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
                        'upcoming_only' => $request->get('upcoming_only'),
                        'past_only' => $request->get('past_only'),
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
     * Used specifically for exam management
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

            // Get class and section from request (required)
            $className = $request->input('class') ?? $request->query('class');
            $sectionName = $request->input('section') ?? $request->query('section');
            $examName = $request->input('exam_name') ?? $request->query('exam_name');
            $subjectName = $request->input('subject') ?? $request->query('subject');

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
                $sectionName = trim($sectionName);
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

            // If exam_name and subject are provided, load existing marks
            $marksData = [];
            if ($examName && $subjectName && $students->count() > 0) {
                $marks = StudentMark::whereRaw('LOWER(TRIM(test_name)) = ?', [strtolower(trim($examName))])
                    ->whereRaw('LOWER(TRIM(subject)) = ?', [strtolower(trim($subjectName))])
                    ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower($className)])
                    ->when($sectionName, function($q) use ($sectionName) {
                        return $q->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($sectionName))]);
                    })
                    ->get()
                    ->keyBy('student_id');

                $marksData = $marks->map(function($mark) {
                    return [
                        'student_id' => $mark->student_id,
                        'marks_obtained' => $mark->marks_obtained,
                        'total_marks' => $mark->total_marks,
                        'passing_marks' => $mark->passing_marks,
                        'remarks' => $mark->teacher_remarks,
                    ];
                })->keyBy('student_id')->toArray();
            }

            // Format students data
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

                // Add marks if available
                if (isset($marksData[$student->id])) {
                    $studentData['marks_obtained'] = $marksData[$student->id]['marks_obtained'] ?? null;
                    $studentData['total_marks'] = $marksData[$student->id]['total_marks'] ?? null;
                    $studentData['passing_marks'] = $marksData[$student->id]['passing_marks'] ?? null;
                    $studentData['remarks'] = $marksData[$student->id]['remarks'] ?? null;
                }

                return $studentData;
            })->values();

            $message = 'Students list retrieved successfully for class: ' . $className;
            if ($sectionName) {
                $message .= ', section: ' . $sectionName;
            }

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
                        'subject' => $subjectName,
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
     * Save Exam Marks
     * 
     * Saves exam marks (obtained, total, passing) for students
     * Similar to web version exam marks entry
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
            $validated = $request->validate([
                'exam_name' => ['required', 'string', 'max:255'],
                'class' => ['required', 'string', 'max:255'],
                'section' => ['nullable', 'string', 'max:255'],
                'subject' => ['required', 'string', 'max:255'],
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

            $className = trim($validated['class']);
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

            // Get campus from first student
            $firstStudentId = array_key_first($validated['marks']);
            $firstStudent = Student::find($firstStudentId);
            $campus = $firstStudent ? $firstStudent->campus : ($teacher->campus ?? '');

            $savedCount = 0;
            $updatedCount = 0;
            $errors = [];

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
                    // Check if record exists
                    $existingMark = StudentMark::where('student_id', $studentId)
                        ->where('test_name', $validated['exam_name'])
                        ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower($className)])
                        ->whereRaw('LOWER(TRIM(subject)) = ?', [strtolower(trim($validated['subject']))])
                        ->when(isset($validated['section']) && !empty($validated['section']), function($q) use ($validated) {
                            return $q->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($validated['section']))]);
                        })
                        ->first();

                    $isUpdate = $existingMark !== null;

                    StudentMark::updateOrCreate(
                        [
                            'student_id' => $studentId,
                            'test_name' => $validated['exam_name'],
                            'campus' => $campus,
                            'class' => $className,
                            'section' => $validated['section'] ?? null,
                            'subject' => $validated['subject'],
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
                        'exam_name' => $validated['exam_name'],
                        'class' => $className,
                        'section' => $validated['section'] ?? null,
                        'subject' => $validated['subject'],
                        'saved_count' => $savedCount,
                        'updated_count' => $updatedCount,
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
