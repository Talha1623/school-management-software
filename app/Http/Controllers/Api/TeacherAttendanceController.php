<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\StudentAttendance;
    use App\Models\ClassModel;
use App\Models\Section;
use App\Models\Campus;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class TeacherAttendanceController extends Controller
{
    /**
     * Mark Single Student Attendance
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function mark(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'student_id' => ['required', 'exists:students,id'],
                'attendance_date' => ['required', 'date'],
                'status' => ['required', 'string'],
                'remarks' => ['nullable', 'string', 'max:500'],
            ]);

            // Normalize status (case-insensitive)
            $status = trim($validated['status']);
            $statusLower = strtolower($status);
            
            // Map common variations to standard status
            $statusMap = [
                'present' => 'Present',
                'absent' => 'Absent',
                'holiday' => 'Holiday',
                'sunday' => 'Sunday',
                'leave' => 'Leave',
                'n/a' => 'N/A',
                'na' => 'N/A',
            ];
            
            if (!isset($statusMap[$statusLower])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid status. Allowed values: Present, Absent, Holiday, Sunday, Leave, N/A',
                    'token' => null,
                ], 200);
            }
            
            $validated['status'] = $statusMap[$statusLower];

            $student = Student::findOrFail($validated['student_id']);

            // Create or update attendance
            $attendance = StudentAttendance::updateOrCreate(
                [
                    'student_id' => $validated['student_id'],
                    'attendance_date' => $validated['attendance_date'],
                ],
                [
                    'status' => $validated['status'],
                    'campus' => $student->campus,
                    'class' => $student->class,
                    'section' => $student->section,
                    'remarks' => $validated['remarks'] ?? null,
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Attendance marked successfully',
                'data' => [
                    'attendance' => [
                        'id' => $attendance->id,
                        'student_id' => $attendance->student_id,
                        'student_name' => $student->student_name,
                        'attendance_date' => $attendance->attendance_date->format('Y-m-d'),
                        'status' => $attendance->status,
                        'remarks' => $attendance->remarks,
                    ],
                ],
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
                'token' => null,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while marking attendance',
                'token' => null,
            ], 200);
        }
    }

    /**
     * Mark Bulk Attendance (Multiple Students)
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function markBulk(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'class' => ['required', 'string'],
                'section' => ['nullable', 'string'],
                'attendance_date' => ['required', 'date'],
                'attendances' => ['required', 'array', 'min:1'],
                'attendances.*.student_id' => ['required', 'exists:students,id'],
                'attendances.*.status' => ['required', 'string'],
                'attendances.*.remarks' => ['nullable', 'string', 'max:500'],
            ]);

            // Status mapping function
            $normalizeStatus = function($status) {
                $statusLower = strtolower(trim($status));
                $statusMap = [
                    'present' => 'Present',
                    'absent' => 'Absent',
                    'holiday' => 'Holiday',
                    'sunday' => 'Sunday',
                    'leave' => 'Leave',
                    'n/a' => 'N/A',
                    'na' => 'N/A',
                ];
                return $statusMap[$statusLower] ?? null;
            };

            $markedCount = 0;
            $errors = [];

            foreach ($validated['attendances'] as $attendanceData) {
                try {
                    // Normalize status
                    $normalizedStatus = $normalizeStatus($attendanceData['status']);
                    if (!$normalizedStatus) {
                        $errors[] = "Student ID {$attendanceData['student_id']}: Invalid status '{$attendanceData['status']}'. Allowed: Present, Absent, Holiday, Sunday, Leave, N/A";
                        continue;
                    }

                    $student = Student::findOrFail($attendanceData['student_id']);

                    // Verify class and section match
                    if (strtolower(trim($student->class ?? '')) !== strtolower(trim($validated['class']))) {
                        $errors[] = "Student ID {$attendanceData['student_id']} does not belong to class '{$validated['class']}'";
                        continue;
                    }

                    if (!empty($validated['section']) && strtolower(trim($student->section ?? '')) !== strtolower(trim($validated['section']))) {
                        $errors[] = "Student ID {$attendanceData['student_id']} does not belong to section '{$validated['section']}'";
                        continue;
                    }

                    StudentAttendance::updateOrCreate(
                        [
                            'student_id' => $attendanceData['student_id'],
                            'attendance_date' => $validated['attendance_date'],
                        ],
                        [
                            'status' => $normalizedStatus,
                            'campus' => $student->campus,
                            'class' => $student->class,
                            'section' => $student->section,
                            'remarks' => $attendanceData['remarks'] ?? null,
                        ]
                    );

                    $markedCount++;
                } catch (\Exception $e) {
                    $errors[] = "Failed to mark attendance for student ID {$attendanceData['student_id']}: " . $e->getMessage();
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Attendance marked for {$markedCount} student(s)",
                'data' => [
                    'marked_count' => $markedCount,
                    'total_count' => count($validated['attendances']),
                    'errors' => $errors,
                ],
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
                'token' => null,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while marking bulk attendance',
                'token' => null,
            ], 200);
        }
    }

    /**
     * Get Attendance List (by date, class, section)
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
                    'message' => 'Access denied. Only teachers can access attendance.',
                    'token' => null,
                ], 403);
            }

            // Get teacher's assigned subjects
            $assignedSubjects = Subject::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($teacher->name ?? ''))])
                ->get();
            
            // Get assigned classes and sections
            $assignedClasses = $assignedSubjects->pluck('class')
                ->unique()
                ->filter()
                ->values();
            
            $assignedSections = $assignedSubjects->pluck('section')
                ->unique()
                ->filter()
                ->values();
            
            $assignedCampuses = $assignedSubjects->pluck('campus')
                ->unique()
                ->filter()
                ->values();

            $query = StudentAttendance::with('student');

            // Filter by teacher's assigned classes ONLY
            if ($assignedClasses->isNotEmpty()) {
                $query->where(function($q) use ($assignedClasses) {
                    foreach ($assignedClasses as $class) {
                        $q->orWhereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))]);
                    }
                });
            } else {
                // If no classes assigned, return empty result
                $query->whereRaw('1 = 0');
            }

            // Filter by Student ID
            if ($request->filled('student_id')) {
                $query->where('student_id', $request->student_id);
            }

            // Filter by Date
            if ($request->filled('date')) {
                $query->whereDate('attendance_date', $request->date);
            }

            // Filter by Class (case-insensitive) - validate it's in assigned classes
            if ($request->filled('class')) {
                $class = trim($request->class);
                // Only filter if class is in assigned classes
                if ($assignedClasses->contains(function($c) use ($class) {
                    return strtolower(trim($c)) === strtolower(trim($class));
                })) {
                    $query->whereRaw('LOWER(TRIM(class)) = ?', [strtolower($class)]);
                }
            }

            // Filter by Section (case-insensitive) - validate it's in assigned sections
            if ($request->filled('section')) {
                $section = trim($request->section);
                // Only filter if section is in assigned sections
                if ($assignedSections->contains(function($s) use ($section) {
                    return strtolower(trim($s)) === strtolower(trim($section));
                })) {
                    $query->whereRaw('LOWER(TRIM(section)) = ?', [strtolower($section)]);
                }
            }

            // Filter by Campus (case-insensitive) - validate it's in assigned campuses
            if ($request->filled('campus')) {
                $campus = trim($request->campus);
                // Only filter if campus is in assigned campuses
                if ($assignedCampuses->contains(function($c) use ($campus) {
                    return strtolower(trim($c)) === strtolower(trim($campus));
                })) {
                    $query->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower($campus)]);
                }
            }

            // Filter by Status
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            // Pagination
            $perPage = $request->get('per_page', 10);
            $perPage = in_array($perPage, [10, 25, 50, 100]) ? $perPage : 10;

            $attendances = $query->latest('attendance_date')->paginate($perPage);

            // Get summary statistics
            $summary = [
                'total_records' => $attendances->total(),
                'present_count' => 0,
                'absent_count' => 0,
                'other_count' => 0,
            ];

            // Format attendance data and calculate summary
            $attendancesData = $attendances->map(function($attendance) use (&$summary) {
                if ($attendance->status === 'Present') {
                    $summary['present_count']++;
                } elseif ($attendance->status === 'Absent') {
                    $summary['absent_count']++;
                } else {
                    $summary['other_count']++;
                }

                return [
                    'id' => $attendance->id,
                    'student_id' => $attendance->student_id,
                    'student_name' => $attendance->student->student_name ?? null,
                    'student_code' => $attendance->student->student_code ?? null,
                    'attendance_date' => $attendance->attendance_date->format('Y-m-d'),
                    'status' => $attendance->status,
                    'class' => $attendance->class,
                    'section' => $attendance->section,
                    'campus' => $attendance->campus,
                    'remarks' => $attendance->remarks,
                ];
            });

            // Get filter info
            $filterInfo = [];
            if ($request->filled('class')) {
                $filterInfo['class'] = $request->class;
            }
            if ($request->filled('section')) {
                $filterInfo['section'] = $request->section;
            }
            if ($request->filled('date')) {
                $filterInfo['date'] = $request->date;
            }
            if ($request->filled('campus')) {
                $filterInfo['campus'] = $request->campus;
            }

            return response()->json([
                'success' => true,
                'message' => 'Attendance list retrieved successfully',
                'data' => [
                    'filter_info' => $filterInfo,
                    'summary' => $summary,
                    'attendances' => $attendancesData,
                    'pagination' => [
                        'current_page' => $attendances->currentPage(),
                        'last_page' => $attendances->lastPage(),
                        'per_page' => $attendances->perPage(),
                        'total' => $attendances->total(),
                        'from' => $attendances->firstItem(),
                        'to' => $attendances->lastItem(),
                    ],
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving attendance list',
                'token' => null,
            ], 200);
        }
    }

    /**
     * Get Student Attendance History
     * 
     * @param Request $request
     * @param int $studentId
     * @return JsonResponse
     */
    public function studentHistory(Request $request, int $studentId): JsonResponse
    {
        try {
            $student = Student::findOrFail($studentId);

            $query = StudentAttendance::where('student_id', $studentId);

            // Filter by Date Range
            if ($request->filled('start_date')) {
                $query->whereDate('attendance_date', '>=', $request->start_date);
            }

            if ($request->filled('end_date')) {
                $query->whereDate('attendance_date', '<=', $request->end_date);
            }

            // Filter by Status
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            // Pagination
            $perPage = $request->get('per_page', 30);
            $perPage = in_array($perPage, [10, 25, 30, 50, 100]) ? $perPage : 30;

            $attendances = $query->orderBy('attendance_date', 'desc')->paginate($perPage);

            // Calculate statistics
            $totalDays = $attendances->total();
            $presentDays = StudentAttendance::where('student_id', $studentId)
                ->where('status', 'Present')
                ->when($request->filled('start_date'), function($q) use ($request) {
                    $q->whereDate('attendance_date', '>=', $request->start_date);
                })
                ->when($request->filled('end_date'), function($q) use ($request) {
                    $q->whereDate('attendance_date', '<=', $request->end_date);
                })
                ->count();

            $absentDays = StudentAttendance::where('student_id', $studentId)
                ->where('status', 'Absent')
                ->when($request->filled('start_date'), function($q) use ($request) {
                    $q->whereDate('attendance_date', '>=', $request->start_date);
                })
                ->when($request->filled('end_date'), function($q) use ($request) {
                    $q->whereDate('attendance_date', '<=', $request->end_date);
                })
                ->count();

            // Format attendance data
            $attendancesData = $attendances->map(function($attendance) {
                return [
                    'id' => $attendance->id,
                    'attendance_date' => $attendance->attendance_date->format('Y-m-d'),
                    'status' => $attendance->status,
                    'remarks' => $attendance->remarks,
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Student attendance history retrieved successfully',
                'data' => [
                    'student' => [
                        'id' => $student->id,
                        'student_code' => $student->student_code,
                        'student_name' => $student->student_name,
                        'class' => $student->class,
                        'section' => $student->section,
                    ],
                    'statistics' => [
                        'total_days' => $totalDays,
                        'present_days' => $presentDays,
                        'absent_days' => $absentDays,
                        'attendance_percentage' => $totalDays > 0 ? round(($presentDays / $totalDays) * 100, 2) : 0,
                    ],
                    'attendances' => $attendancesData,
                    'pagination' => [
                        'current_page' => $attendances->currentPage(),
                        'last_page' => $attendances->lastPage(),
                        'per_page' => $attendances->perPage(),
                        'total' => $attendances->total(),
                        'from' => $attendances->firstItem(),
                        'to' => $attendances->lastItem(),
                    ],
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving student attendance history',
                'token' => null,
            ], 200);
        }
    }

    /**
     * Get Attendance Filter Options (Classes, Sections, Dates)
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getFilterOptions(Request $request): JsonResponse
    {
        try {
            $teacher = $request->user();

            if (!$teacher || strtolower(trim($teacher->designation ?? '')) !== 'teacher') {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Only teachers can access attendance.',
                    'token' => null,
                ], 403);
            }

            // Get classes from teacher's assigned subjects
            $classes = Subject::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($teacher->name ?? ''))])
                ->whereNotNull('class')
                ->distinct()
                ->pluck('class')
                ->sort()
                ->values();
            
            // If no classes assigned, return empty
            if ($classes->isEmpty()) {
                $classes = collect();
            }

            // Get Sections (filtered by class if provided) - from teacher's assigned subjects
            $sections = collect();
            if ($request->filled('class')) {
                $class = trim($request->class);
                
                // Get sections from teacher's assigned subjects for this class
                $assignedSubjects = Subject::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($teacher->name ?? ''))])
                    ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower($class)])
                    ->get();
                
                $sections = $assignedSubjects->pluck('section')
                    ->unique()
                    ->filter()
                    ->sort()
                    ->values();
            }

            // Get Campuses from teacher's assigned subjects
            $campuses = Subject::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($teacher->name ?? ''))])
                ->whereNotNull('campus')
                ->distinct()
                ->pluck('campus')
                ->sort()
                ->values();
            
            if ($campuses->isEmpty()) {
                $campuses = collect();
            }

            // Get available dates (last 30 days)
            $availableDates = StudentAttendance::whereNotNull('attendance_date')
                ->orderBy('attendance_date', 'desc')
                ->distinct()
                ->pluck('attendance_date')
                ->take(30)
                ->map(function($date) {
                    return $date->format('Y-m-d');
                })
                ->values();

            // Status options
            $statusOptions = ['Present', 'Absent', 'Holiday', 'Sunday', 'Leave', 'N/A'];

            return response()->json([
                'success' => true,
                'message' => 'Attendance filter options retrieved successfully',
                'data' => [
                    'classes' => $classes->values(),
                    'sections' => $sections->values(),
                    'campuses' => $campuses->values(),
                    'available_dates' => $availableDates,
                    'status_options' => $statusOptions,
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving filter options',
                'token' => null,
            ], 200);
        }
    }

    /**
     * Get Monthly Attendance Report (Same as Web)
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function monthlyReport(Request $request): JsonResponse
    {
        try {
            $teacher = $request->user();

            if (!$teacher || strtolower(trim($teacher->designation ?? '')) !== 'teacher') {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Only teachers can access attendance reports.',
                    'token' => null,
                ], 403);
            }

            // Validate required parameters
            $validated = $request->validate([
                'campus' => ['required', 'string'],
                'class' => ['required', 'string'],
                'section' => ['nullable', 'string'],
                'month' => ['required', 'string', 'regex:/^(0[1-9]|1[0-2])$/'],
                'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            ]);

            $campus = trim($validated['campus']);
            $class = trim($validated['class']);
            $section = $validated['section'] ? trim($validated['section']) : null;
            $month = (int)$validated['month'];
            $year = (int)$validated['year'];

            // Verify teacher has access to this class/section (from assigned subjects)
            $teacherSubjects = Subject::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($teacher->name ?? ''))])
                ->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower($campus)])
                ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower($class)]);

            if ($section) {
                $teacherSubjects->whereRaw('LOWER(TRIM(section)) = ?', [strtolower($section)]);
            }

            $hasAccess = $teacherSubjects->exists();

            if (!$hasAccess) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have access to this class/section. Only your assigned classes/sections are accessible.',
                    'token' => null,
                ], 403);
            }

            // Get students
            $studentsQuery = Student::query();
            $studentsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower($campus)]);
            $studentsQuery->whereRaw('LOWER(TRIM(class)) = ?', [strtolower($class)]);

            if ($section) {
                $studentsQuery->whereRaw('LOWER(TRIM(section)) = ?', [strtolower($section)]);
            }

            $students = $studentsQuery->orderBy('student_code', 'asc')
                ->orderBy('student_name', 'asc')
                ->get();

            // Get month details
            $date = Carbon::create($year, $month, 1);
            $daysInMonth = $date->daysInMonth;
            $monthName = $date->format('F');

            // Get attendance data for the month
            $studentIds = $students->pluck('id');
            $startDate = Carbon::create($year, $month, 1)->startOfDay();
            $endDate = Carbon::create($year, $month, $daysInMonth)->endOfDay();

            $attendances = StudentAttendance::whereIn('student_id', $studentIds)
                ->whereBetween('attendance_date', [$startDate, $endDate])
                ->get()
                ->groupBy('student_id');

            // Build attendance data for each student
            $studentsData = [];
            foreach ($students as $student) {
                $attendanceData = [];
                $presentDays = 0;
                $absentDays = 0;

                // Initialize all days as empty
                for ($day = 1; $day <= $daysInMonth; $day++) {
                    $attendanceData[$day] = '';
                }

                // Fill in actual attendance data
                if (isset($attendances[$student->id])) {
                    foreach ($attendances[$student->id] as $attendance) {
                        $attendanceDate = Carbon::parse($attendance->attendance_date);
                        $day = $attendanceDate->day;

                        // Convert status to single letter (same as web)
                        $status = strtoupper($attendance->status);
                        if ($status === 'PRESENT') {
                            $attendanceData[$day] = 'P';
                            $presentDays++;
                        } elseif ($status === 'ABSENT') {
                            $attendanceData[$day] = 'A';
                            $absentDays++;
                        } elseif ($status === 'HOLIDAY') {
                            $attendanceData[$day] = 'H';
                        } elseif ($status === 'SUNDAY') {
                            $attendanceData[$day] = 'S';
                        } elseif ($status === 'LEAVE') {
                            $attendanceData[$day] = 'L';
                        } else {
                            $attendanceData[$day] = '';
                        }
                    }
                }

                $studentsData[] = [
                    'roll_number' => $student->student_code ?? $student->gr_number ?? null,
                    'student_id' => $student->id,
                    'student_name' => $student->student_name,
                    'surname_caste' => $student->surname_caste,
                    'father_name' => $student->father_name ?? 'N/A',
                    'present_days' => $presentDays,
                    'absent_days' => $absentDays,
                    'daily_attendance' => $attendanceData,
                ];
            }

            // Calculate summary
            $totalPresentDays = collect($studentsData)->sum('present_days');
            $totalAbsentDays = collect($studentsData)->sum('absent_days');

            return response()->json([
                'success' => true,
                'message' => 'Monthly attendance report retrieved successfully',
                'data' => [
                    'header' => [
                        'campus' => $campus,
                        'class' => $class,
                        'section' => $section,
                        'month' => $monthName,
                        'year' => $year,
                        'month_number' => str_pad($month, 2, '0', STR_PAD_LEFT),
                        'days_in_month' => $daysInMonth,
                    ],
                    'summary' => [
                        'total_students' => $students->count(),
                        'total_present_days' => $totalPresentDays,
                        'total_absent_days' => $totalAbsentDays,
                    ],
                    'students' => $studentsData,
                ],
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
                'token' => null,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving attendance report: ' . $e->getMessage(),
                'token' => null,
            ], 200);
        }
    }

    /**
     * Get Attendance Report Filter Options (for Monthly Report)
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getReportFilterOptions(Request $request): JsonResponse
    {
        try {
            $teacher = $request->user();

            if (!$teacher || strtolower(trim($teacher->designation ?? '')) !== 'teacher') {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Only teachers can access attendance reports.',
                    'token' => null,
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

            // Get sections (if class is provided)
            $sections = collect();
            if ($request->filled('class')) {
                $class = trim($request->class);
                $sections = Subject::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($teacher->name ?? ''))])
                    ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower($class)])
                    ->whereNotNull('section')
                    ->distinct()
                    ->pluck('section')
                    ->sort()
                    ->values();

                if ($sections->isEmpty()) {
                    $sections = Section::whereRaw('LOWER(TRIM(class)) = ?', [strtolower($class)])
                        ->whereNotNull('name')
                        ->distinct()
                        ->pluck('name')
                        ->sort()
                        ->values();
                }
            }

            // Get months
            $months = collect([
                '01' => 'January', '02' => 'February', '03' => 'March', '04' => 'April',
                '05' => 'May', '06' => 'June', '07' => 'July', '08' => 'August',
                '09' => 'September', '10' => 'October', '11' => 'November', '12' => 'December'
            ]);

            // Get years (current year ± 5 years)
            $currentYear = (int)date('Y');
            $years = collect();
            for ($i = $currentYear - 5; $i <= $currentYear + 5; $i++) {
                $years->push($i);
            }

            return response()->json([
                'success' => true,
                'message' => 'Attendance report filter options retrieved successfully',
                'data' => [
                    'campuses' => $campuses->values(),
                    'classes' => $classes->values(),
                    'sections' => $sections->values(),
                    'months' => $months,
                    'years' => $years->values(),
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving filter options: ' . $e->getMessage(),
                'token' => null,
            ], 200);
        }
    }

    /**
     * Get Attendance Report List (Students attendance with present/absent status and time)
     * Returns list of students with their attendance for specified month/year
     * Includes present time (created_at) for each attendance record
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getReportList(Request $request): JsonResponse
    {
        try {
            $teacher = $request->user();

            if (!$teacher || strtolower(trim($teacher->designation ?? '')) !== 'teacher') {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Only teachers can access attendance reports.',
                    'token' => null,
                ], 403);
            }

            // Validate required parameters
            $validated = $request->validate([
                'campus' => ['required', 'string'],
                'class' => ['required', 'string'],
                'section' => ['nullable', 'string'],
                'month' => ['required', 'string', 'regex:/^(0[1-9]|1[0-2])$/'],
                'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            ]);

            $campus = trim($validated['campus']);
            $class = trim($validated['class']);
            $section = $validated['section'] ? trim($validated['section']) : null;
            $month = (int)$validated['month'];
            $year = (int)$validated['year'];

            // Verify teacher has access to this class/section (from assigned subjects)
            // Check from subjects table - don't require exact campus match, just class/section
            $teacherSubjects = Subject::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($teacher->name ?? ''))])
                ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower($class)]);

            if ($section) {
                $teacherSubjects->whereRaw('LOWER(TRIM(section)) = ?', [strtolower($section)]);
            }

            $hasAccessFromSubjects = $teacherSubjects->exists();

            // Also check from sections table
            $hasAccessFromSections = false;
            if (!$hasAccessFromSubjects) {
                $teacherSections = Section::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($teacher->name ?? ''))])
                    ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower($class)]);

                if ($section) {
                    $teacherSections->whereRaw('LOWER(TRIM(name)) = ?', [strtolower($section)]);
                }

                $hasAccessFromSections = $teacherSections->exists();
            }

            $hasAccess = $hasAccessFromSubjects || $hasAccessFromSections;

            if (!$hasAccess) {
                // Debug info - get teacher's assigned classes for better error message
                $assignedClasses = Subject::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($teacher->name ?? ''))])
                    ->whereNotNull('class')
                    ->distinct()
                    ->pluck('class')
                    ->map(function($c) {
                        return trim($c);
                    })
                    ->unique()
                    ->values();

                return response()->json([
                    'success' => false,
                    'message' => 'You do not have access to this class/section. Only your assigned classes/sections are accessible.',
                    'debug' => [
                        'requested_class' => $class,
                        'requested_section' => $section,
                        'requested_campus' => $campus,
                        'teacher_name' => $teacher->name,
                        'assigned_classes' => $assignedClasses,
                    ],
                    'token' => null,
                ], 403);
            }

            // Get students
            $studentsQuery = Student::query();
            $studentsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower($campus)]);
            $studentsQuery->whereRaw('LOWER(TRIM(class)) = ?', [strtolower($class)]);

            if ($section) {
                $studentsQuery->whereRaw('LOWER(TRIM(section)) = ?', [strtolower($section)]);
            }

            $students = $studentsQuery->orderBy('student_code', 'asc')
                ->orderBy('student_name', 'asc')
                ->get();

            // Get month details
            $date = Carbon::create($year, $month, 1);
            $daysInMonth = $date->daysInMonth;
            $monthName = $date->format('F');

            // Get attendance data for the month
            $studentIds = $students->pluck('id');
            $startDate = Carbon::create($year, $month, 1)->startOfDay();
            $endDate = Carbon::create($year, $month, $daysInMonth)->endOfDay();

            $attendances = StudentAttendance::whereIn('student_id', $studentIds)
                ->whereBetween('attendance_date', [$startDate, $endDate])
                ->orderBy('attendance_date', 'asc')
                ->orderBy('created_at', 'asc')
                ->get()
                ->groupBy('student_id');

            // Build attendance list for each student
            $studentsList = [];
            foreach ($students as $student) {
                $attendanceRecords = [];
                $presentDays = 0;
                $absentDays = 0;

                // Get attendance records for this student
                if (isset($attendances[$student->id])) {
                    foreach ($attendances[$student->id] as $attendance) {
                        $attendanceDate = Carbon::parse($attendance->attendance_date);
                        $status = $attendance->status;
                        $statusUpper = strtoupper($status);

                        // Count present and absent days
                        if ($statusUpper === 'PRESENT') {
                            $presentDays++;
                        } elseif ($statusUpper === 'ABSENT') {
                            $absentDays++;
                        }

                        // Get present time (created_at timestamp)
                        $presentTime = null;
                        if ($statusUpper === 'PRESENT' && $attendance->created_at) {
                            $presentTime = Carbon::parse($attendance->created_at)->format('Y-m-d H:i:s');
                        }

                        $attendanceRecords[] = [
                            'date' => $attendanceDate->format('Y-m-d'),
                            'day' => $attendanceDate->day,
                            'status' => $status,
                            'status_code' => $statusUpper === 'PRESENT' ? 'P' : ($statusUpper === 'ABSENT' ? 'A' : ($statusUpper === 'HOLIDAY' ? 'H' : ($statusUpper === 'SUNDAY' ? 'S' : ($statusUpper === 'LEAVE' ? 'L' : '')))),
                            'present_time' => $presentTime,
                            'remarks' => $attendance->remarks ?? null,
                        ];
                    }
                }

                $studentsList[] = [
                    'student_id' => $student->id,
                    'roll_number' => $student->student_code ?? $student->gr_number ?? null,
                    'student_name' => $student->student_name,
                    'surname_caste' => $student->surname_caste ?? null,
                    'father_name' => $student->father_name ?? null,
                    'campus' => $student->campus,
                    'class' => $student->class,
                    'section' => $student->section,
                    'present_days' => $presentDays,
                    'absent_days' => $absentDays,
                    'attendance_records' => $attendanceRecords,
                    'total_records' => count($attendanceRecords),
                ];
            }

            // Calculate summary
            $totalStudents = count($studentsList);
            $totalPresentDays = collect($studentsList)->sum('present_days');
            $totalAbsentDays = collect($studentsList)->sum('absent_days');

            return response()->json([
                'success' => true,
                'message' => 'Attendance report list retrieved successfully',
                'data' => [
                    'header' => [
                        'campus' => $campus,
                        'class' => $class,
                        'section' => $section,
                        'month' => $monthName,
                        'year' => $year,
                        'month_number' => str_pad($month, 2, '0', STR_PAD_LEFT),
                        'days_in_month' => $daysInMonth,
                    ],
                    'summary' => [
                        'total_students' => $totalStudents,
                        'total_present_days' => $totalPresentDays,
                        'total_absent_days' => $totalAbsentDays,
                    ],
                    'students' => $studentsList,
                ],
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
                'message' => 'An error occurred while retrieving attendance report list: ' . $e->getMessage(),
                'token' => null,
            ], 500);
        }
    }

    /**
     * Get Students for Attendance by Class/Section
     * Support both GET (query params) and POST (body)
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getClassStudents(Request $request): JsonResponse
    {
        try {
            $teacher = $request->user();

            if (!$teacher || strtolower(trim($teacher->designation ?? '')) !== 'teacher') {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Only teachers can access this endpoint.',
                    'token' => null,
                ], 403);
            }

            // Support both GET (query params) and POST (body) requests
            $validated = $request->validate([
                'class' => ['required', 'string'],
                'section' => ['nullable', 'string'],
                'date' => ['nullable', 'date'],
            ]);

            $className = trim($validated['class']);
            $sectionName = $validated['section'] ? trim($validated['section']) : null;
            $attendanceDate = $validated['date'] ?? Carbon::today()->format('Y-m-d');

            // Get teacher's assigned subjects
            $assignedSubjects = Subject::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($teacher->name ?? ''))])
                ->get();

            // Get teacher's assigned sections
            $assignedSections = Section::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($teacher->name ?? ''))])
                ->get();

            // Get assigned classes from both sources
            $assignedClasses = $assignedSubjects->pluck('class')
                ->merge($assignedSections->pluck('class'))
                ->map(function($class) {
                    return trim($class);
                })
                ->filter()
                ->unique()
                ->values();

            // Verify that the requested class is assigned to teacher
            $isClassAssigned = $assignedClasses->contains(function($c) use ($className) {
                return strtolower(trim($c)) === strtolower(trim($className));
            });

            if (!$isClassAssigned) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. This class is not assigned to you.',
                    'token' => null,
                ], 403);
            }

            // Get assigned sections for this class
            $assignedSectionsForClass = $assignedSubjects->where('class', $className)
                ->pluck('section')
                ->merge($assignedSections->where('class', $className)->pluck('name'))
                ->map(function($section) {
                    return trim($section);
                })
                ->filter()
                ->unique()
                ->values();

            // If section is provided, verify it's assigned
            if ($sectionName && !$assignedSectionsForClass->contains(function($s) use ($sectionName) {
                return strtolower(trim($s)) === strtolower(trim($sectionName));
            })) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. This section is not assigned to you.',
                    'token' => null,
                ], 403);
            }

            // Get students for the class/section
            $studentsQuery = Student::query();

            // Filter by teacher's campus
            if ($teacher->campus) {
                $studentsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($teacher->campus))]);
            }

            // Filter by class
            $studentsQuery->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($className))]);

            // Filter by section if provided
            if ($sectionName) {
                $studentsQuery->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($sectionName))]);
            }

            $students = $studentsQuery->orderBy('student_name', 'asc')->get();

            // Get attendance data for the selected date
            $studentIds = $students->pluck('id');
            $attendances = StudentAttendance::whereIn('student_id', $studentIds)
                ->whereDate('attendance_date', $attendanceDate)
                ->get()
                ->keyBy('student_id');

            // Get all attendance records for each student to calculate percentage
            $allAttendances = StudentAttendance::whereIn('student_id', $studentIds)
                ->get()
                ->groupBy('student_id');

            // Format students data with attendance status and percentage
            $studentsData = $students->map(function($student) use ($attendances, $allAttendances) {
                $attendance = $attendances->get($student->id);
                
                // Calculate attendance percentage
                $studentAttendances = $allAttendances->get($student->id, collect());
                $totalDays = $studentAttendances->whereIn('status', ['Present', 'Absent'])->count();
                $presentDays = $studentAttendances->where('status', 'Present')->count();
                $attendancePercentage = 0;
                
                if ($totalDays > 0) {
                    $attendancePercentage = round(($presentDays / $totalDays) * 100, 2);
                }
                
                return [
                    'id' => $student->id,
                    'student_code' => $student->student_code,
                    'student_name' => $student->student_name,
                    'surname_caste' => $student->surname_caste,
                    'father_name' => $student->father_name,
                    'class' => $student->class,
                    'section' => $student->section,
                    'gender' => $student->gender,
                    'photo' => $student->photo ? asset('storage/' . $student->photo) : null,
                    'attendance_percentage' => $attendancePercentage,
                    'current_attendance' => [
                        'status' => $attendance ? $attendance->status : null,
                        'marked' => $attendance !== null,
                        'remarks' => $attendance ? $attendance->remarks : null,
                    ],
                ];
            })->values();

            return response()->json([
                'success' => true,
                'message' => 'Students retrieved successfully',
                'data' => [
                    'class' => $className,
                    'section' => $sectionName,
                    'date' => $attendanceDate,
                    'total_students' => $students->count(),
                    'students' => $studentsData,
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
                'message' => 'An error occurred while retrieving students: ' . $e->getMessage(),
                'token' => null,
            ], 500);
        }
    }

    /**
     * Mark Attendance for Class/Section Students
     * Mark attendance for all students in one request
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function markClassAttendance(Request $request): JsonResponse
    {
        try {
            $teacher = $request->user();

            if (!$teacher || strtolower(trim($teacher->designation ?? '')) !== 'teacher') {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Only teachers can access this endpoint.',
                    'token' => null,
                ], 403);
            }

            $validated = $request->validate([
                'class' => ['required', 'string'],
                'section' => ['nullable', 'string'],
                'attendance_date' => ['required', 'date'],
                'attendances' => ['required', 'array', 'min:1'],
                'attendances.*.student_id' => ['required', 'exists:students,id'],
                'attendances.*.status' => ['required', 'string'],
                'attendances.*.remarks' => ['nullable', 'string', 'max:500'],
            ]);

            $className = trim($validated['class']);
            $sectionName = $validated['section'] ? trim($validated['section']) : null;
            $attendanceDate = $validated['attendance_date'];

            // Get teacher's assigned subjects
            $assignedSubjects = Subject::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($teacher->name ?? ''))])
                ->get();

            // Get teacher's assigned sections
            $assignedSections = Section::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($teacher->name ?? ''))])
                ->get();

            // Get assigned classes from both sources
            $assignedClasses = $assignedSubjects->pluck('class')
                ->merge($assignedSections->pluck('class'))
                ->map(function($class) {
                    return trim($class);
                })
                ->filter()
                ->unique()
                ->values();

            // Verify that the requested class is assigned to teacher
            $isClassAssigned = $assignedClasses->contains(function($c) use ($className) {
                return strtolower(trim($c)) === strtolower(trim($className));
            });

            if (!$isClassAssigned) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. This class is not assigned to you.',
                    'token' => null,
                ], 403);
            }

            // Status mapping function
            $normalizeStatus = function($status) {
                $statusLower = strtolower(trim($status));
                $statusMap = [
                    'present' => 'Present',
                    'absent' => 'Absent',
                    'holiday' => 'Holiday',
                    'sunday' => 'Sunday',
                    'leave' => 'Leave',
                    'n/a' => 'N/A',
                    'na' => 'N/A',
                ];
                return $statusMap[$statusLower] ?? null;
            };

            $markedCount = 0;
            $errors = [];

            foreach ($validated['attendances'] as $attendanceData) {
                try {
                    // Normalize status
                    $normalizedStatus = $normalizeStatus($attendanceData['status']);
                    if (!$normalizedStatus) {
                        $errors[] = "Student ID {$attendanceData['student_id']}: Invalid status '{$attendanceData['status']}'. Allowed: Present, Absent, Holiday, Sunday, Leave, N/A";
                        continue;
                    }

                    $student = Student::findOrFail($attendanceData['student_id']);

                    // Verify class matches
                    if (strtolower(trim($student->class ?? '')) !== strtolower(trim($className))) {
                        $errors[] = "Student ID {$attendanceData['student_id']} does not belong to class '{$className}'";
                        continue;
                    }

                    // Verify section matches if provided
                    if ($sectionName && strtolower(trim($student->section ?? '')) !== strtolower(trim($sectionName))) {
                        $errors[] = "Student ID {$attendanceData['student_id']} does not belong to section '{$sectionName}'";
                        continue;
                    }

                    // Verify student is in teacher's assigned classes
                    $studentClass = trim($student->class ?? '');
                    $isStudentClassAssigned = $assignedClasses->contains(function($c) use ($studentClass) {
                        return strtolower(trim($c)) === strtolower(trim($studentClass));
                    });

                    if (!$isStudentClassAssigned) {
                        $errors[] = "Student ID {$attendanceData['student_id']}: Access denied. This student's class is not assigned to you.";
                        continue;
                    }

                    // Create or update attendance
                    StudentAttendance::updateOrCreate(
                        [
                            'student_id' => $attendanceData['student_id'],
                            'attendance_date' => $attendanceDate,
                        ],
                        [
                            'status' => $normalizedStatus,
                            'campus' => $student->campus,
                            'class' => $student->class,
                            'section' => $student->section,
                            'remarks' => $attendanceData['remarks'] ?? null,
                        ]
                    );

                    $markedCount++;
                } catch (\Exception $e) {
                    $errors[] = "Failed to mark attendance for student ID {$attendanceData['student_id']}: " . $e->getMessage();
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Attendance marked for {$markedCount} student(s)",
                'data' => [
                    'class' => $className,
                    'section' => $sectionName,
                    'attendance_date' => $attendanceDate,
                    'marked_count' => $markedCount,
                    'total_count' => count($validated['attendances']),
                    'errors' => $errors,
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
                'message' => 'An error occurred while marking attendance: ' . $e->getMessage(),
                'token' => null,
            ], 500);
        }
    }
}

