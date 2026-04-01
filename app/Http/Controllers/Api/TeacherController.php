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
            // IMPORTANT: Only get records where teacher is CURRENTLY assigned (not null/empty)
            $assignedSubjects = Subject::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($teacher->name ?? ''))])
                ->whereNotNull('teacher')
                ->where('teacher', '!=', '')
                ->get();

            // Get classes and sections from teacher's assigned sections
            // IMPORTANT: Only get records where teacher is CURRENTLY assigned (not null/empty)
            $assignedSections = Section::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($teacher->name ?? ''))])
                ->whereNotNull('teacher')
                ->where('teacher', '!=', '')
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
                    
                    // Check if teacher is class teacher for this section
                    // Class teacher is identified by Section model's teacher field matching teacher's name
                    $sectionRecord = Section::whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($className))])
                        ->whereRaw('LOWER(TRIM(name)) = ?', [strtolower(trim($section))])
                        ->first();
                    
                    $isClassTeacher = false;
                    if ($sectionRecord && $sectionRecord->teacher) {
                        // Check if teacher name matches (case-insensitive)
                        $isClassTeacher = strtolower(trim($sectionRecord->teacher)) === strtolower(trim($teacher->name ?? ''));
                    }
                    
                    $classesData[] = [
                        'class' => $className,
                        'section' => trim($section),
                        'formatted_sections' => $formattedSection,
                        'is_class_teacher' => $isClassTeacher,
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
     * Used specifically for EXAM management (not test management)
     * 
     * NOTE: This API is for EXAMS created by super admin in "Exam List" page
     * For tests, use the test management API endpoints
     * 
     * Query Parameters:
     * - class (required): Class name (e.g., "ten", "nine")
     * - section (optional): Section name (e.g., "a", "b", "t")
     * - exam_name (optional): Exam name to load existing marks (e.g., "Mid Term Exam")
     * - subject (optional): Subject name to load existing marks (e.g., "English")
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

            // If exam_name and subject are provided, load existing exam marks
            // NOTE: Exam marks are stored in StudentMark table with exam_name in test_name field
            // This is different from test marks which use test names
            $marksData = [];
            if ($examName && $subjectName && $students->count() > 0) {
                // Load existing exam marks for this exam, subject, class, and section
                $marks = StudentMark::whereRaw('LOWER(TRIM(test_name)) = ?', [strtolower(trim($examName))]) // exam_name stored in test_name field
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

                // Add existing exam marks if available (when exam_name and subject are provided)
                if (isset($marksData[$student->id])) {
                    $marksObtained = $marksData[$student->id]['marks_obtained'] ?? null;
                    $totalMarks = $marksData[$student->id]['total_marks'] ?? null;
                    $passingMarks = $marksData[$student->id]['passing_marks'] ?? null;

                    $studentData['marks_obtained'] = $marksObtained;
                    $studentData['total_marks'] = $totalMarks;
                    $studentData['passing_marks'] = $passingMarks;
                    $studentData['remarks'] = $marksData[$student->id]['remarks'] ?? null;

                    // Boolean pass based on obtained >= passing
                    if ($marksObtained !== null && $passingMarks !== null && $passingMarks !== '') {
                        $studentData['is_passed'] = (float)$marksObtained >= (float)$passingMarks;
                    } else {
                        $studentData['is_passed'] = null;
                    }
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
            ]);

            $className = trim((string) $validated['class']);
            $sectionName = trim((string) $validated['section']);
            $examName = trim((string) $validated['exam_name']);
            $subjectName = trim((string) $validated['subject']);

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

            if (!empty($teacher->campus)) {
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
                'student_id' => ['nullable', 'integer', 'exists:students,id'],
                'remark' => ['nullable', 'string', 'max:1000'],
                'remarks' => ['nullable', 'array', 'min:1'],
                'remarks.*' => ['nullable', 'string', 'max:1000'],
            ]);

            $examName = trim((string) $validated['exam_name']);
            $className = trim((string) $validated['class']);
            $sectionName = isset($validated['section']) ? trim((string) $validated['section']) : null;
            $subjectName = trim((string) $validated['subject']);

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
     * It returns subjects from Subject assignments for the logged-in teacher.
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
            ]);

            $examName = trim((string) $validated['exam_name']);
            $className = trim((string) $validated['class']);
            $sectionName = isset($validated['section']) ? trim((string) $validated['section']) : null;
            $campusName = isset($validated['campus']) ? trim((string) $validated['campus']) : null;

            $teacherName = strtolower(trim($teacher->name ?? ''));

            // Subjects assigned to this teacher for this class/section/campus
            $subjectsQuery = Subject::whereRaw('LOWER(TRIM(teacher)) = ?', [$teacherName])
                ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower($className)]);

            if (!empty($sectionName)) {
                $subjectsQuery->whereRaw('LOWER(TRIM(section)) = ?', [strtolower($sectionName)]);
            }
            if (!empty($campusName)) {
                $subjectsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower($campusName)]);
            }

            $subjects = $subjectsQuery->pluck('subject_name')
                ->filter()
                ->map(fn($s) => trim((string) $s))
                ->unique()
                ->sort()
                ->values()
                ->toArray();

            return response()->json([
                'success' => true,
                'message' => 'Exam subjects retrieved successfully.',
                'data' => [
                    'exam_name' => $examName,
                    'class' => $className,
                    'section' => $sectionName,
                    'campus' => $campusName,
                    'subjects' => $subjects,
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
                // If true, do not overwrite existing marks for same exam/class/section/subject.
                // Default: true (prevents re-upload).
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
            $skippedCount = 0;
            $errors = [];
            $preventDuplicate = filter_var($request->input('prevent_duplicate', true), FILTER_VALIDATE_BOOLEAN);

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
                        ->where('test_name', $validated['exam_name']) // exam_name stored in test_name field
                        ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower($className)])
                        ->whereRaw('LOWER(TRIM(subject)) = ?', [strtolower(trim($validated['subject']))])
                        ->when(isset($validated['section']) && !empty($validated['section']), function($q) use ($validated) {
                            return $q->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($validated['section']))]);
                        })
                        ->first();

                    $isUpdate = $existingMark !== null;

                    // Save exam marks in StudentMark table
                    // IMPORTANT: exam_name is stored in test_name field
                    // Exam marks and test marks are differentiated by the value in test_name field
                    if ($isUpdate && $preventDuplicate) {
                        // Skip overwriting existing marks
                        $skippedCount++;
                        continue;
                    }

                    StudentMark::updateOrCreate(
                        [
                            'student_id' => $studentId,
                            'test_name' => $validated['exam_name'], // Exam name stored here (e.g., "Mid Term Exam")
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
                    'message' => $preventDuplicate
                        ? "Exam marks saved (new: {$savedCount}, updated: {$updatedCount}, skipped(existing): {$skippedCount})!"
                        : "Exam marks saved successfully for {$totalProcessed} student(s)!",
                    'data' => [
                        'exam_name' => $validated['exam_name'],
                        'class' => $className,
                        'section' => $validated['section'] ?? null,
                        'subject' => $validated['subject'],
                        'saved_count' => $savedCount,
                        'updated_count' => $updatedCount,
                        'skipped_count' => $skippedCount,
                        'total_processed' => $totalProcessed,
                        'errors' => !empty($errors) ? $errors : null,
                    ],
                    'token' => $request->user()->currentAccessToken()->token ?? null,
                ], 200);
            } else {
                // If prevent_duplicate=true and everything is already uploaded, let client know.
                if ($preventDuplicate && $skippedCount > 0) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Marks already uploaded for the selected students/exam/subject. Re-upload prevented.',
                        'data' => [
                            'saved_count' => $savedCount,
                            'updated_count' => $updatedCount,
                            'skipped_count' => $skippedCount,
                            'errors' => !empty($errors) ? $errors : null,
                        ],
                        'token' => null,
                    ], 409);
                }
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
