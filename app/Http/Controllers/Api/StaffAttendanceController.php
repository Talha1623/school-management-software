<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Leave;
use App\Models\Staff;
use App\Models\StaffAttendance;
use App\Models\SalarySetting;
use App\Models\Subject;
use App\Models\Timetable;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class StaffAttendanceController extends Controller
{
    private const LECTURES_TAKEN_API_MARKER = '[lectures-taken-api]';

    private function hasLecturesTakenApiSubmission(?StaffAttendance $attendance): bool
    {
        if (!$attendance) {
            return false;
        }

        return stripos((string) ($attendance->remarks ?? ''), self::LECTURES_TAKEN_API_MARKER) !== false;
    }

    private function markLecturesTakenApiSubmission(?string $remarks): string
    {
        $remarks = trim((string) $remarks);

        if (stripos($remarks, self::LECTURES_TAKEN_API_MARKER) !== false) {
            return $remarks;
        }

        return $remarks === ''
            ? self::LECTURES_TAKEN_API_MARKER
            : $remarks . ' | ' . self::LECTURES_TAKEN_API_MARKER;
    }

    /**
     * @return array<int, string>
     */
    private function getStaticTimetableSubjects(): array
    {
        return [
            '[Assembly]',
            '[Lunch Break]',
            '[Free Time]',
            '[Lab Active]',
            '[physicial/sports/activity]',
            '[singing class]',
            '[material arts class]',
            '[Library Activity]',
            '[chilligraphy class]',
            '[other fun activities]',
        ];
    }

    private function isBillableLectureSubject(?string $subject): bool
    {
        $subject = trim((string) $subject);

        return $subject !== '' && !in_array($subject, $this->getStaticTimetableSubjects(), true);
    }

    /**
     * @return \Illuminate\Support\Collection<int, string>
     */
    private function getTeacherIdentityKeys(Staff $teacher)
    {
        return collect([
            strtolower(trim((string) ($teacher->name ?? ''))),
            strtolower(trim((string) ($teacher->emp_id ?? ''))),
        ])->filter()->unique()->values();
    }

    /**
     * @param  \Illuminate\Support\Collection<int, array<string, string>>  $assignmentRows
     */
    private function applyTeacherAssignmentScope($query, $assignmentRows): void
    {
        $query->where(function ($outer) use ($assignmentRows) {
            foreach ($assignmentRows as $assignment) {
                $outer->orWhere(function ($inner) use ($assignment) {
                    if ($assignment['campus'] !== '') {
                        $inner->whereRaw('LOWER(TRIM(COALESCE(campus, ""))) = ?', [$assignment['campus']]);
                    }

                    $inner->whereRaw('LOWER(TRIM(class)) = ?', [$assignment['class']]);

                    if ($assignment['section'] !== '') {
                        $inner->whereRaw('LOWER(TRIM(section)) = ?', [$assignment['section']]);
                    }

                    $inner->whereRaw('LOWER(TRIM(subject)) = ?', [$assignment['subject']]);
                });
            }
        });
    }

    /**
     * Billable lecture periods assigned to the teacher on a given date (from timetable).
     *
     * @return array{count: int, day: string, periods: array<int, array<string, mixed>>}
     */
    private function getAssignedLecturesForDate(Staff $teacher, Carbon $date): array
    {
        $dayName = $date->format('l');
        $dayNameLower = strtolower($dayName);
        $teacherIdentityKeys = $this->getTeacherIdentityKeys($teacher);

        $teacherSubjects = Subject::query()
            ->whereNotNull('class')
            ->where(function ($q) use ($teacherIdentityKeys) {
                foreach ($teacherIdentityKeys as $key) {
                    $q->orWhereRaw('LOWER(TRIM(teacher)) = ?', [$key]);
                }
            })
            ->get();

        if ($teacherSubjects->isEmpty()) {
            return ['count' => 0, 'day' => $dayName, 'periods' => []];
        }

        $assignmentRows = $teacherSubjects
            ->map(function ($row) {
                return [
                    'campus' => strtolower(trim((string) ($row->campus ?? ''))),
                    'class' => strtolower(trim((string) ($row->class ?? ''))),
                    'section' => strtolower(trim((string) ($row->section ?? ''))),
                    'subject' => strtolower(trim((string) ($row->subject_name ?? ''))),
                ];
            })
            ->filter(fn ($row) => $row['class'] !== '' && $row['subject'] !== '')
            ->unique(fn ($row) => implode('|', [$row['campus'], $row['class'], $row['section'], $row['subject']]))
            ->values();

        if ($assignmentRows->isEmpty()) {
            return ['count' => 0, 'day' => $dayName, 'periods' => []];
        }

        $query = Timetable::query()
            ->whereRaw('LOWER(TRIM(day)) = ?', [$dayNameLower]);
        $this->applyTeacherAssignmentScope($query, $assignmentRows);

        $periods = $query
            ->orderBy('starting_time')
            ->get()
            ->filter(fn ($timetable) => $this->isBillableLectureSubject($timetable->subject ?? null))
            ->map(function ($timetable) use ($date) {
                return [
                    'id' => $timetable->id,
                    'class' => $timetable->class,
                    'section' => $timetable->section,
                    'subject' => $timetable->subject,
                    'starting_time' => $timetable->starting_time,
                    'ending_time' => $timetable->ending_time,
                    'starting_time_formatted' => date('h:i A', strtotime($timetable->starting_time)),
                    'ending_time_formatted' => date('h:i A', strtotime($timetable->ending_time)),
                    'date' => $date->format('Y-m-d'),
                ];
            })
            ->values()
            ->all();

        return [
            'count' => count($periods),
            'day' => $dayName,
            'periods' => $periods,
        ];
    }

    /**
     * Save/update number of classes (lectures) taken by the authenticated teacher for a date.
     *
     * POST /api/teacher/attendance/lectures-taken
     * Body:
     * - classes_count: integer (required, >= 0)
     * - date: Y-m-d (optional, default today)
     * - remarks: string (optional)
     */
    public function saveLecturesTaken(Request $request): JsonResponse
    {
        try {
            $teacher = $request->user();

            if (!$teacher || !$teacher->isTeacher()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Only teachers can access this endpoint.',
                    'data' => null,
                    'token' => null,
                ], 403);
            }

            $validated = $request->validate([
                'classes_count' => ['required', 'integer', 'min:1'],
                'date' => ['nullable', 'date_format:Y-m-d'],
                'remarks' => ['nullable', 'string', 'max:500'],
            ]);

            $date = isset($validated['date']) ? Carbon::parse($validated['date']) : Carbon::today();
            $classesCount = (int) $validated['classes_count'];
            $remarks = $validated['remarks'] ?? null;
            $dateString = $date->format('Y-m-d');
            $assignedLectures = $this->getAssignedLecturesForDate($teacher, $date);
            $maxAllowed = $assignedLectures['count'];

            if ($maxAllowed <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'No lectures are assigned to you for this date.',
                    'data' => [
                        'date' => $dateString,
                        'date_formatted' => $date->format('d M Y'),
                        'day' => $assignedLectures['day'],
                        'assigned_lectures' => 0,
                        'max_classes_allowed' => 0,
                    ],
                    'token' => $request->bearerToken(),
                ], 422);
            }

            if ($classesCount > $maxAllowed) {
                return response()->json([
                    'success' => false,
                    'message' => 'You cannot submit more than your assigned lectures for this date.',
                    'data' => [
                        'date' => $dateString,
                        'date_formatted' => $date->format('d M Y'),
                        'day' => $assignedLectures['day'],
                        'classes_count_requested' => $classesCount,
                        'assigned_lectures' => $maxAllowed,
                        'max_classes_allowed' => $maxAllowed,
                    ],
                    'token' => $request->bearerToken(),
                ], 422);
            }

            $existing = StaffAttendance::where('staff_id', $teacher->id)
                ->whereDate('attendance_date', $dateString)
                ->first();

            if ($this->hasLecturesTakenApiSubmission($existing)) {
                return response()->json([
                    'success' => false,
                    'message' => 'You have already submitted lecture count for this date. Duplicate submission is not allowed.',
                    'data' => [
                        'date' => $dateString,
                        'date_formatted' => $date->format('d M Y'),
                        'conducted_lectures' => (int) ($existing->conducted_lectures ?? 0),
                        'already_submitted' => true,
                    ],
                    'token' => $request->bearerToken(),
                ], 422);
            }

            // Find or create attendance row for that date
            $attendance = $existing ?? new StaffAttendance([
                'staff_id' => $teacher->id,
                'attendance_date' => $dateString,
            ]);

            // If status empty, default to Present when recording lectures
            if (empty($attendance->status)) {
                $attendance->status = 'Present';
            }

            $attendance->conducted_lectures = $classesCount;
            $attendance->remarks = $this->markLecturesTakenApiSubmission($remarks);
            $attendance->save();

            return response()->json([
                'success' => true,
                'message' => 'Classes count saved successfully.',
                'data' => [
                    'attendance' => [
                        'date' => $attendance->attendance_date ? Carbon::parse($attendance->attendance_date)->format('Y-m-d') : $date->format('Y-m-d'),
                        'date_formatted' => $attendance->attendance_date ? Carbon::parse($attendance->attendance_date)->format('d M Y') : $date->format('d M Y'),
                        'status' => $attendance->status,
                        'conducted_lectures' => (int) ($attendance->conducted_lectures ?? 0),
                        'remarks' => $attendance->remarks,
                        'already_submitted' => true,
                        'assigned_lectures' => $maxAllowed,
                        'max_classes_allowed' => $maxAllowed,
                    ],
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
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while saving classes count: ' . $e->getMessage(),
                'data' => null,
                'token' => null,
            ], 500);
        }
    }

    /**
     * Get lecture/classes taken count for per-lecture teacher.
     *
     * GET /api/teacher/attendance/lectures-taken
     * Optional params:
     * - date=Y-m-d (single day)
     * - month=1..12&year=2026 (monthly)
     * - date_from=Y-m-d&date_to=Y-m-d (custom range)
     */
    public function lecturesTaken(Request $request): JsonResponse
    {
        try {
            $teacher = $request->user();

            if (!$teacher || !$teacher->isTeacher()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Only teachers can access this endpoint.',
                    'data' => null,
                    'token' => null,
                ], 403);
            }

            $salaryTypeRaw = strtolower(trim((string) ($teacher->salary_type ?? '')));
            $isPerLecture = in_array($salaryTypeRaw, ['lecture', 'per lecture', 'per_lecture'], true);

            $today = Carbon::today();
            $rangeType = 'today';
            $rangeFrom = $today->copy();
            $rangeTo = $today->copy();

            if ($request->filled('date')) {
                try {
                    $singleDate = Carbon::parse($request->get('date'));
                    $rangeType = 'date';
                    $rangeFrom = $singleDate->copy()->startOfDay();
                    $rangeTo = $singleDate->copy()->endOfDay();
                } catch (\Exception $e) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid date format. Use Y-m-d.',
                        'data' => null,
                        'token' => null,
                    ], 422);
                }
            } elseif ($request->filled('month') && $request->filled('year')) {
                $month = (int) $request->get('month');
                $year = (int) $request->get('year');

                if ($month < 1 || $month > 12 || $year < 2000 || $year > 2100) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid month/year values.',
                        'data' => null,
                        'token' => null,
                    ], 422);
                }

                $rangeType = 'month';
                $rangeFrom = Carbon::create($year, $month, 1)->startOfDay();
                $rangeTo = Carbon::create($year, $month, 1)->endOfMonth()->endOfDay();
            } elseif ($request->filled('date_from') && $request->filled('date_to')) {
                try {
                    $rangeFrom = Carbon::parse($request->get('date_from'))->startOfDay();
                    $rangeTo = Carbon::parse($request->get('date_to'))->endOfDay();
                    if ($rangeTo->lt($rangeFrom)) {
                        return response()->json([
                            'success' => false,
                            'message' => 'date_to must be greater than or equal to date_from.',
                            'data' => null,
                            'token' => null,
                        ], 422);
                    }
                    $rangeType = 'range';
                } catch (\Exception $e) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid date_from/date_to format. Use Y-m-d.',
                        'data' => null,
                        'token' => null,
                    ], 422);
                }
            }

            $attendanceRows = StaffAttendance::where('staff_id', $teacher->id)
                ->whereBetween('attendance_date', [$rangeFrom->format('Y-m-d'), $rangeTo->format('Y-m-d')])
                ->orderBy('attendance_date', 'asc')
                ->get();

            $lecturesTotal = 0;
            $daysPresent = 0;
            $byDate = [];

            foreach ($attendanceRows as $row) {
                $status = strtolower(trim((string) ($row->status ?? '')));
                $isPresentLike = in_array($status, ['present', 'half day'], true);
                $countForDay = 0;

                if ($isPresentLike) {
                    $daysPresent++;
                    $conducted = (int) ($row->conducted_lectures ?? 0);
                    $countForDay = $conducted > 0 ? $conducted : 1;
                    $lecturesTotal += $countForDay;
                }

                $byDate[] = [
                    'date' => $row->attendance_date ? Carbon::parse($row->attendance_date)->format('Y-m-d') : null,
                    'date_formatted' => $row->attendance_date ? Carbon::parse($row->attendance_date)->format('d M Y') : null,
                    'status' => $row->status,
                    'conducted_lectures' => (int) ($row->conducted_lectures ?? 0),
                    'counted_classes' => $countForDay,
                    'already_submitted' => $this->hasLecturesTakenApiSubmission($row),
                ];
            }

            $todayAttendance = StaffAttendance::where('staff_id', $teacher->id)
                ->whereDate('attendance_date', $today->format('Y-m-d'))
                ->first();
            $todayLectures = 0;
            $alreadySubmittedToday = $this->hasLecturesTakenApiSubmission($todayAttendance);
            if ($todayAttendance) {
                $todayStatus = strtolower(trim((string) ($todayAttendance->status ?? '')));
                if (in_array($todayStatus, ['present', 'half day'], true)) {
                    $todayConducted = (int) ($todayAttendance->conducted_lectures ?? 0);
                    $todayLectures = $todayConducted > 0 ? $todayConducted : 1;
                }
            }

            $queryDateAttendance = null;
            $alreadySubmittedForQueryDate = false;
            if ($rangeType === 'date') {
                $queryDateAttendance = StaffAttendance::where('staff_id', $teacher->id)
                    ->whereDate('attendance_date', $rangeFrom->format('Y-m-d'))
                    ->first();
                $alreadySubmittedForQueryDate = $this->hasLecturesTakenApiSubmission($queryDateAttendance);
            }

            $assignedLecturesToday = $this->getAssignedLecturesForDate($teacher, $today);
            $assignedForQueryDate = $rangeType === 'date'
                ? $this->getAssignedLecturesForDate($teacher, $rangeFrom)
                : null;

            return response()->json([
                'success' => true,
                'message' => 'Teacher lecture count retrieved successfully.',
                'data' => [
                    'teacher' => [
                        'id' => $teacher->id,
                        'name' => $teacher->name,
                        'emp_id' => $teacher->emp_id ?? null,
                        'salary_type' => $teacher->salary_type,
                        'is_per_lecture' => $isPerLecture,
                    ],
                    'range' => [
                        'type' => $rangeType,
                        'from' => $rangeFrom->format('Y-m-d'),
                        'to' => $rangeTo->format('Y-m-d'),
                    ],
                    'summary' => [
                        'classes_taken_in_range' => $isPerLecture ? $lecturesTotal : 0,
                        'present_days_in_range' => $daysPresent,
                        'classes_taken_today' => $isPerLecture ? $todayLectures : 0,
                        'assigned_lectures_today' => $assignedLecturesToday['count'],
                        'max_classes_allowed_today' => $assignedLecturesToday['count'],
                        'assigned_lectures_for_date' => $assignedForQueryDate ? $assignedForQueryDate['count'] : null,
                        'max_classes_allowed_for_date' => $assignedForQueryDate ? $assignedForQueryDate['count'] : null,
                        'already_submitted_today' => $alreadySubmittedToday,
                        'can_submit_today' => !$alreadySubmittedToday && $assignedLecturesToday['count'] > 0,
                        'already_submitted_for_date' => $rangeType === 'date' ? $alreadySubmittedForQueryDate : null,
                        'can_submit_for_date' => $rangeType === 'date'
                            ? (!$alreadySubmittedForQueryDate && ($assignedForQueryDate['count'] ?? 0) > 0)
                            : null,
                    ],
                    'assigned_lectures_today' => $assignedLecturesToday,
                    'assigned_lectures_for_date' => $assignedForQueryDate,
                    'attendance_by_date' => $byDate,
                ],
                'token' => $request->user()->currentAccessToken()->token ?? null,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving teacher lecture count: ' . $e->getMessage(),
                'data' => null,
                'token' => null,
            ], 500);
        }
    }

    /**
     * Get Staff Attendance Report
     * Returns staff attendance with monthly summary (total present, absent, leave, holiday, sunday)
     * Similar to student/parent attendance API
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function attendanceReport(Request $request): JsonResponse
    {
        try {
            $staff = $request->user();
            
            if (!$staff) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found',
                    'data' => null,
                    'token' => null,
                ], 404);
            }

            // Validate required parameters - month and year
            if (!$request->filled('month')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Month is required (1-12)',
                    'data' => null,
                    'token' => null,
                ], 400);
            }

            if (!$request->filled('year')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Year is required (e.g., 2026)',
                    'data' => null,
                    'token' => null,
                ], 400);
            }

            // Get month and year from request
            $month = (int) $request->month;
            $year = (int) $request->year;

            // Validate month (1-12)
            if ($month < 1 || $month > 12) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid month. Month must be between 1 and 12',
                    'data' => null,
                    'token' => null,
                ], 400);
            }

            // Validate year (reasonable range)
            if ($year < 2000 || $year > 2100) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid year. Year must be between 2000 and 2100',
                    'data' => null,
                    'token' => null,
                ], 400);
            }

            // Use authenticated staff (from token)
            $targetStaff = $staff;
            $targetStaffId = $staff->id;
                
            // Get start and end dates of the month
            $monthStart = Carbon::create($year, $month, 1)->startOfDay();
            $monthEnd = Carbon::create($year, $month, 1)->endOfMonth()->endOfDay();
            $attendanceDate = Carbon::create($year, $month, 1); // For date formatting in response

            // Get all attendance records for this staff for the entire month
            $monthlyAttendances = StaffAttendance::where('staff_id', $targetStaffId)
                ->whereBetween('attendance_date', [$monthStart->format('Y-m-d'), $monthEnd->format('Y-m-d')])
                ->orderBy('attendance_date', 'asc')
                ->get();

            // Load approved leave dates for this staff within the requested month.
            $approvedLeaves = Leave::where('staff_id', $targetStaffId)
                ->where('status', 'Approved')
                ->whereDate('from_date', '<=', $monthEnd->format('Y-m-d'))
                ->whereDate('to_date', '>=', $monthStart->format('Y-m-d'))
                ->get();

            $approvedLeaveDates = [];
            $approvedLeaveRemarksByDate = [];
            foreach ($approvedLeaves as $leave) {
                $start = Carbon::parse($leave->from_date)->startOfDay();
                $end = Carbon::parse($leave->to_date)->startOfDay();
                if ($start->lt($monthStart)) {
                    $start = $monthStart->copy()->startOfDay();
                }
                if ($end->gt($monthEnd)) {
                    $end = $monthEnd->copy()->startOfDay();
                }

                $cursor = $start->copy();
                while ($cursor->lte($end)) {
                    $d = $cursor->format('Y-m-d');
                    $approvedLeaveDates[$d] = true;
                    if (!isset($approvedLeaveRemarksByDate[$d])) {
                        $approvedLeaveRemarksByDate[$d] = $leave->leave_reason ?: ($leave->remarks ?: null);
                    }
                    $cursor->addDay();
                }
            }

            // Salary type aware counting (same direction as web salary flow)
            $salaryTypeRaw = strtolower(trim((string) ($targetStaff->salary_type ?? '')));
            $isPerLecture = $salaryTypeRaw === 'lecture';
            $isPerHour = $salaryTypeRaw === 'per hour';
            $isFullTime = empty($salaryTypeRaw) || $salaryTypeRaw === 'full time';
            $normalizedSalaryType = $isPerLecture ? 'lecture' : ($isPerHour ? 'per hour' : 'full time');
            $salarySettings = SalarySetting::getSettings();
            $rawLateArrivalTime = trim((string) ($salarySettings->late_arrival_time ?? '08:00:00'));
            $rawEarlyExitTime = trim((string) ($salarySettings->early_exit_time ?? ''));
            $lateArrivalTime = $this->normalizeLateArrivalThreshold($rawLateArrivalTime);
            $earlyExitTime = $this->normalizeEarlyExitThreshold($rawEarlyExitTime) ?? '';

            $totalPresent = 0;
            $totalAbsent = 0;
            $totalLeave = 0;
            $totalHoliday = 0;
            $totalSunday = 0;
            $totalLateIn = 0;
            $totalEarlyOut = 0;
            $totalMinutes = 0;
            $lectureCount = 0;

            foreach ($monthlyAttendances as $attendance) {
                $statusNormalized = strtolower(trim((string) ($attendance->status ?? '')));
                $isPresentLike = in_array($statusNormalized, ['present', 'half day'], true);

                if ($isPresentLike) {
                    $totalPresent++;

                    $lateEarlyMetrics = $this->resolveLateEarlyMetrics(
                        $attendance,
                        $targetStaff,
                        $isPerHour,
                        $lateArrivalTime,
                        $earlyExitTime
                    );
                    if ($lateEarlyMetrics['is_late_in']) {
                        $totalLateIn++;
                    }
                    if ($lateEarlyMetrics['is_early_out']) {
                        $totalEarlyOut++;
                    }

                    if ($isPerLecture) {
                        $conducted = (int) ($attendance->conducted_lectures ?? 0);
                        $lectureCount += $conducted > 0 ? $conducted : 1;
                    }

                    if ($isPerHour && $attendance->start_time && $attendance->end_time) {
                        try {
                            $dateStr = Carbon::parse($attendance->attendance_date)->format('Y-m-d');
                            $start = Carbon::parse($dateStr . ' ' . $attendance->start_time);
                            $end = Carbon::parse($dateStr . ' ' . $attendance->end_time);
                            if ($end->greaterThan($start)) {
                                $totalMinutes += $start->diffInMinutes($end);
                            }
                        } catch (\Exception $e) {
                            // Ignore invalid time records
                        }
                    }
                } elseif ($statusNormalized === 'absent') {
                    $totalAbsent++;
                } elseif ($statusNormalized === 'leave') {
                    $totalLeave++;
                } elseif ($statusNormalized === 'holiday') {
                    $totalHoliday++;
                } elseif ($statusNormalized === 'sunday') {
                    $totalSunday++;
                }
            }

            // Add approved leave days that don't have attendance rows.
            $attendanceDateKeys = $monthlyAttendances
                ->map(fn ($row) => Carbon::parse($row->attendance_date)->format('Y-m-d'))
                ->unique()
                ->flip()
                ->all();
            foreach (array_keys($approvedLeaveDates) as $leaveDateKey) {
                if (!isset($attendanceDateKeys[$leaveDateKey])) {
                    $totalLeave++;
                }
            }

            // For full-time staff, count Sunday and Holiday as present
            if ($isFullTime) {
                $totalPresent += $totalHoliday + $totalSunday;
            }

            $totalHours = round($totalMinutes / 60, 2);

            // Get attendance for today (if within the month) or first day of month
            $today = Carbon::today();
            $checkDate = ($today->year == $year && $today->month == $month) ? $today : $attendanceDate;
            $attendance = StaffAttendance::where('staff_id', $targetStaffId)
                ->whereDate('attendance_date', $checkDate->format('Y-m-d'))
                ->first();

                // Format date-wise attendance
                $attendanceByDate = $monthlyAttendances->map(function ($att) use ($targetStaff, $isFullTime, $isPerHour, $lateArrivalTime, $earlyExitTime) {
                    return $this->formatAttendanceDayRow(
                        $att,
                        Carbon::parse($att->attendance_date)->format('Y-m-d'),
                        Carbon::parse($att->attendance_date)->format('d M Y'),
                        $targetStaff,
                        $isFullTime,
                        $isPerHour,
                        $lateArrivalTime,
                        $earlyExitTime
                    );
                })->values();

                // Get all dates in the month and fill missing dates with 'N/A'
                $allDatesInMonth = [];
                $currentDate = $monthStart->copy();
                while ($currentDate->lte($monthEnd)) {
                    $dateStr = $currentDate->format('Y-m-d');
                    $existingAttendance = $monthlyAttendances->first(function ($att) use ($dateStr) {
                        return Carbon::parse($att->attendance_date)->format('Y-m-d') === $dateStr;
                    });

                    if ($existingAttendance) {
                        $allDatesInMonth[] = $this->formatAttendanceDayRow(
                            $existingAttendance,
                            $dateStr,
                            $currentDate->format('d M Y'),
                            $targetStaff,
                            $isFullTime,
                            $isPerHour,
                            $lateArrivalTime,
                            $earlyExitTime
                        );
                    } else {
                        $allDatesInMonth[] = $this->formatAttendanceDayRow(
                            null,
                            $dateStr,
                            $currentDate->format('d M Y'),
                            $targetStaff,
                            $isFullTime,
                            $isPerHour,
                            $lateArrivalTime,
                            $earlyExitTime,
                            isset($approvedLeaveDates[$dateStr]) ? 'Leave' : 'N/A',
                            $approvedLeaveRemarksByDate[$dateStr] ?? null
                        );
                    }

                    $currentDate->addDay();
                }

                $staffData = [
                    'id' => $targetStaff->id,
                    'name' => $targetStaff->name,
                    'emp_id' => $targetStaff->emp_id ?? null,
                    'designation' => $targetStaff->designation,
                    'campus' => $targetStaff->campus,
                    'status' => $attendance
                        ? $this->resolveAttendanceStatus($attendance, $targetStaff, $isFullTime, $isPerHour, $lateArrivalTime, $earlyExitTime)
                        : 'N/A',
                    'start_time' => $attendance ? $attendance->start_time : null,
                    'end_time' => $attendance ? $attendance->end_time : null,
                    'remarks' => $attendance ? $attendance->remarks : null,
                ];

            // Return response with monthly summary and date-wise attendance
            return response()->json([
                'success' => true,
                'message' => 'Staff attendance retrieved successfully',
                'data' => [
                    'month' => $month,
                    'year' => $year,
                    'month_formatted' => $attendanceDate->format('F Y'),
                    'campus' => $targetStaff->campus,
                    'designation' => $targetStaff->designation,
                    'monthly_summary' => [
                        'salary_type' => $normalizedSalaryType,
                        'total_present' => $totalPresent,
                        'total_absent' => $totalAbsent,
                        'total_leave' => $totalLeave,
                        'total_holiday' => $totalHoliday,
                        'total_sunday' => $totalSunday,
                        'total_lectures' => $isPerLecture ? $lectureCount : 0,
                        'total_minutes' => $isPerHour ? $totalMinutes : 0,
                        'total_hours' => $totalHours,
                        'total_late_in' => $totalLateIn,
                        'total_early_out' => $totalEarlyOut,
                    ],
                    'status_rules' => [
                        'late_arrival_time' => $lateArrivalTime,
                        'early_exit_time' => $earlyExitTime !== '' ? $earlyExitTime : null,
                    ],
                    'staff' => $staffData,
                    'attendance_by_date' => $allDatesInMonth,
                ],
                'token' => $request->user()->currentAccessToken()->token ?? null,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving attendance: ' . $e->getMessage(),
                'data' => null,
                'token' => null,
            ], 500);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function formatAttendanceDayRow(
        ?StaffAttendance $attendance,
        string $dateStr,
        string $dateFormatted,
        Staff $staff,
        bool $isFullTime,
        bool $isPerHour,
        string $lateArrivalTime,
        string $earlyExitTime,
        ?string $forcedStatus = null,
        ?string $fallbackRemarks = null
    ): array {
        if ($attendance) {
            $statusRaw = $this->resolveAttendanceStatus(
                $attendance,
                $staff,
                $isFullTime,
                $isPerHour,
                $lateArrivalTime,
                $earlyExitTime
            );
            $metrics = $this->resolveLateEarlyMetrics(
                $attendance,
                $staff,
                $isPerHour,
                $lateArrivalTime,
                $earlyExitTime
            );
            $remarks = $attendance->remarks;
            $startTime = $attendance->start_time;
            $endTime = $attendance->end_time;
        } else {
            $statusRaw = $forcedStatus ?? 'N/A';
            $metrics = [
                'is_late_in' => false,
                'is_early_out' => false,
                'late_in_duration' => null,
                'early_out_duration' => null,
                'expected_start_time' => null,
                'expected_end_time' => null,
            ];
            $remarks = $fallbackRemarks;
            $startTime = null;
            $endTime = null;
        }

        $statusNormalized = strtolower(trim($statusRaw));
        $shortStatus = '--';
        if (in_array($statusNormalized, ['present', 'half day', 'full time'], true)) {
            $shortStatus = 'P';
        } elseif ($statusNormalized === 'late in') {
            $shortStatus = 'LI';
        } elseif ($statusNormalized === 'early out') {
            $shortStatus = 'EO';
        } elseif ($statusNormalized === 'late in, early out') {
            $shortStatus = 'LI/EO';
        } elseif ($statusNormalized === 'absent') {
            $shortStatus = 'A';
        } elseif ($statusNormalized === 'leave') {
            $shortStatus = 'L';
        } elseif ($statusNormalized === 'holiday') {
            $shortStatus = 'H';
        } elseif ($statusNormalized === 'sunday') {
            $shortStatus = 'S';
        }

        return [
            'date' => $dateStr,
            'date_formatted' => $dateFormatted,
            'status' => $statusRaw,
            'short_status' => $shortStatus,
            'is_late_in' => $metrics['is_late_in'],
            'is_early_out' => $metrics['is_early_out'],
            'late_in_duration' => $metrics['late_in_duration'],
            'early_out_duration' => $metrics['early_out_duration'],
            'expected_start_time' => $metrics['expected_start_time'],
            'expected_end_time' => $metrics['expected_end_time'],
            'start_time' => $startTime,
            'end_time' => $endTime,
            'remarks' => $remarks,
        ];
    }

    /**
     * Derive API display status from attendance row and salary type.
     */
    private function resolveAttendanceStatus(
        StaffAttendance $attendance,
        Staff $staff,
        bool $isFullTime,
        bool $isPerHour,
        string $lateArrivalTime,
        string $earlyExitTime
    ): string {
        $statusRaw = trim((string) ($attendance->status ?? ''));
        $statusNormalized = strtolower($statusRaw);

        if (!in_array($statusNormalized, ['present', 'half day'], true)) {
            return $statusRaw !== '' ? $statusRaw : 'N/A';
        }

        $metrics = $this->resolveLateEarlyMetrics($attendance, $staff, $isPerHour, $lateArrivalTime, $earlyExitTime);

        if ($metrics['is_late_in'] && $metrics['is_early_out']) {
            return 'Late In, Early Out';
        }
        if ($metrics['is_late_in']) {
            return 'Late In';
        }
        if ($metrics['is_early_out']) {
            return 'Early Out';
        }

        return $isFullTime ? 'Full Time' : 'Present';
    }

    /**
     * @return array{
     *     is_late_in: bool,
     *     is_early_out: bool,
     *     late_in_duration: ?string,
     *     early_out_duration: ?string,
     *     expected_start_time: ?string,
     *     expected_end_time: ?string
     * }
     */
    private function resolveLateEarlyMetrics(
        StaffAttendance $attendance,
        Staff $staff,
        bool $isPerHour,
        string $lateArrivalTime,
        string $earlyExitTime
    ): array {
        $dateStr = Carbon::parse($attendance->attendance_date)->format('Y-m-d');
        $remarks = (string) ($attendance->remarks ?? '');

        // Salary flow does not count late/early for per-hour staff.
        if ($isPerHour) {
            return [
                'is_late_in' => false,
                'is_early_out' => false,
                'late_in_duration' => null,
                'early_out_duration' => null,
                'expected_start_time' => null,
                'expected_end_time' => null,
            ];
        }

        $expectedStart = $lateArrivalTime;
        $expectedEnd = $earlyExitTime;

        if (!$this->hasValidAttendanceSession($dateStr, $attendance->start_time, $attendance->end_time)) {
            return [
                'is_late_in' => false,
                'is_early_out' => false,
                'late_in_duration' => null,
                'early_out_duration' => null,
                'expected_start_time' => $expectedStart !== '' ? $expectedStart : null,
                'expected_end_time' => $expectedEnd !== '' ? $expectedEnd : null,
            ];
        }

        $isLateIn = false;
        $lateInDuration = null;
        if (!empty($attendance->start_time) && $expectedStart !== '') {
            $start = $this->parseComparableTime($dateStr, $attendance->start_time);
            $standardStart = $this->parseComparableTime($dateStr, $expectedStart);
            if ($start && $standardStart && $start->gt($standardStart)) {
                $isLateIn = true;
                $lateInDuration = $this->formatMinutesAsDuration($standardStart->diffInMinutes($start, true));
            }
        }
        if (!$isLateIn && $this->hasRemarksLateArrival($remarks)) {
            $isLateIn = true;
            $lateInDuration = $this->extractDurationFromRemarks($remarks, 'Late Arrival');
        }

        $isEarlyOut = false;
        $earlyOutDuration = null;
        if (!empty($attendance->end_time) && $expectedEnd !== '') {
            $end = $this->parseComparableTime($dateStr, $attendance->end_time);
            $standardEnd = $this->parseComparableTime($dateStr, $expectedEnd);
            if ($end && $standardEnd && $end->lt($standardEnd)) {
                $isEarlyOut = true;
                $earlyOutDuration = $this->formatMinutesAsDuration($end->diffInMinutes($standardEnd, true));
            }
        }
        if (!$isEarlyOut && $this->hasRemarksEarlyExit($remarks)) {
            $isEarlyOut = true;
            $earlyOutDuration = $this->extractDurationFromRemarks($remarks, 'Early Exit');
        }

        return [
            'is_late_in' => $isLateIn,
            'is_early_out' => $isEarlyOut,
            'late_in_duration' => $lateInDuration,
            'early_out_duration' => $earlyOutDuration,
            'expected_start_time' => $expectedStart !== '' ? $expectedStart : null,
            'expected_end_time' => $expectedEnd !== '' ? $expectedEnd : null,
        ];
    }

    private function hasRemarksLateArrival(string $remarks): bool
    {
        return (bool) preg_match('/Late Arrival:\s*\d{1,2}:\d{2}/i', $remarks);
    }

    private function hasRemarksEarlyExit(string $remarks): bool
    {
        return (bool) preg_match('/Early Exit:\s*\d{1,2}:\d{2}/i', $remarks);
    }

    /**
     * Ignore misconfigured salary thresholds (e.g. 20:00 late, 23:59 early exit).
     */
    private function normalizeLateArrivalThreshold(?string $time): string
    {
        $parsed = $this->parseTimeOfDay($time);
        if (!$parsed) {
            return '09:00:00';
        }

        if ($parsed['hour'] >= 17) {
            return '09:00:00';
        }

        return $parsed['formatted'];
    }

    private function normalizeEarlyExitThreshold(?string $time): ?string
    {
        $time = trim((string) $time);
        if ($time === '') {
            return null;
        }

        $parsed = $this->parseTimeOfDay($time);
        if (!$parsed) {
            return null;
        }

        // Evening/night cutoffs (e.g. 23:59) are not valid school closing times.
        if ($parsed['hour'] >= 17) {
            return null;
        }

        return $parsed['formatted'];
    }

    /**
     * @return array{hour: int, minute: int, formatted: string}|null
     */
    private function parseTimeOfDay(?string $time): ?array
    {
        $time = trim((string) $time);
        if ($time === '') {
            return null;
        }

        foreach (['H:i:s', 'H:i', 'h:i A', 'h:i:s A', 'g:i A'] as $format) {
            try {
                $parsed = Carbon::createFromFormat($format, $time);
                if ($parsed !== false) {
                    return [
                        'hour' => (int) $parsed->format('H'),
                        'minute' => (int) $parsed->format('i'),
                        'formatted' => $parsed->format('H:i:s'),
                    ];
                }
            } catch (\Exception $e) {
                // Try next format
            }
        }

        try {
            $parsed = Carbon::parse($time);

            return [
                'hour' => (int) $parsed->format('H'),
                'minute' => (int) $parsed->format('i'),
                'formatted' => $parsed->format('H:i:s'),
            ];
        } catch (\Exception $e) {
            return null;
        }
    }

    private function hasValidAttendanceSession(string $dateStr, ?string $startTime, ?string $endTime): bool
    {
        if (empty($startTime) || empty($endTime)) {
            return true;
        }

        $start = $this->parseComparableTime($dateStr, $startTime);
        $end = $this->parseComparableTime($dateStr, $endTime);
        if (!$start || !$end || $end->lte($start)) {
            return false;
        }

        // Very short early-morning punches are usually invalid scan/API timestamps.
        if ((int) $start->format('H') < 6 && $start->diffInMinutes($end) < 30) {
            return false;
        }

        return true;
    }

    private function parseComparableTime(string $dateStr, ?string $timeStr): ?Carbon
    {
        if ($timeStr === null || trim($timeStr) === '') {
            return null;
        }

        $timeStr = trim($timeStr);

        if (preg_match('/^\d{4}-\d{2}-\d{2}\s+\d{1,2}:\d{2}/', $timeStr)) {
            try {
                return Carbon::parse($timeStr);
            } catch (\Exception $e) {
                return null;
            }
        }

        foreach (['H:i:s', 'H:i', 'h:i A', 'h:i:s A', 'g:i A'] as $format) {
            try {
                $parsed = Carbon::createFromFormat($format, $timeStr);
                if ($parsed !== false) {
                    return Carbon::parse($dateStr . ' ' . $parsed->format('H:i:s'));
                }
            } catch (\Exception $e) {
                // Try next format
            }
        }

        try {
            return Carbon::parse($dateStr . ' ' . $timeStr);
        } catch (\Exception $e) {
            return null;
        }
    }

    private function formatMinutesAsDuration(int $minutes): string
    {
        $minutes = abs($minutes);
        $hours = intdiv($minutes, 60);
        $mins = $minutes % 60;

        return sprintf('%02d:%02d', $hours, $mins);
    }

    private function extractDurationFromRemarks(string $remarks, string $label): ?string
    {
        $pattern = '/' . preg_quote($label, '/') . ':\s*(\d{1,2}:\d{2})/i';
        if (preg_match($pattern, $remarks, $matches)) {
            return $matches[1];
        }

        return null;
    }
}
