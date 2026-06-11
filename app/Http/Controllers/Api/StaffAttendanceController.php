<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Leave;
use App\Models\Staff;
use App\Models\StaffAttendance;
use App\Models\SalarySetting;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class StaffAttendanceController extends Controller
{
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
                'classes_count' => ['required', 'integer', 'min:0'],
                'date' => ['nullable', 'date_format:Y-m-d'],
                'remarks' => ['nullable', 'string', 'max:500'],
            ]);

            $date = isset($validated['date']) ? Carbon::parse($validated['date']) : Carbon::today();
            $classesCount = (int) $validated['classes_count'];
            $remarks = $validated['remarks'] ?? null;

            // Find or create attendance row for that date
            $attendance = StaffAttendance::firstOrNew([
                'staff_id' => $teacher->id,
                'attendance_date' => $date->format('Y-m-d'),
            ]);

            // If status empty, default to Present when recording lectures
            if (empty($attendance->status)) {
                $attendance->status = 'Present';
            }

            $attendance->conducted_lectures = $classesCount;
            if ($remarks !== null) {
                $attendance->remarks = $remarks;
            }
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
                ];
            }

            $todayAttendance = StaffAttendance::where('staff_id', $teacher->id)
                ->whereDate('attendance_date', $today->format('Y-m-d'))
                ->first();
            $todayLectures = 0;
            if ($todayAttendance) {
                $todayStatus = strtolower(trim((string) ($todayAttendance->status ?? '')));
                if (in_array($todayStatus, ['present', 'half day'], true)) {
                    $todayConducted = (int) ($todayAttendance->conducted_lectures ?? 0);
                    $todayLectures = $todayConducted > 0 ? $todayConducted : 1;
                }
            }

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
                    ],
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
            // Keep API values non-null and allow status detection when settings exist.
            $lateArrivalTime = trim((string) ($salarySettings->late_arrival_time ?? '08:00:00'));
            $earlyExitTime = trim((string) ($salarySettings->early_exit_time ?? ''));

            $totalPresent = 0;
            $totalAbsent = 0;
            $totalLeave = 0;
            $totalHoliday = 0;
            $totalSunday = 0;
            $totalMinutes = 0;
            $lectureCount = 0;

            foreach ($monthlyAttendances as $attendance) {
                $statusNormalized = strtolower(trim((string) ($attendance->status ?? '')));
                $isPresentLike = in_array($statusNormalized, ['present', 'half day'], true);

                if ($isPresentLike) {
                    $totalPresent++;

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
                $attendanceByDate = $monthlyAttendances->map(function($att) use ($isFullTime, $lateArrivalTime, $earlyExitTime) {
                    $statusRaw = $this->resolveAttendanceStatus($att, $isFullTime, $lateArrivalTime, $earlyExitTime);
                    $statusNormalized = strtolower(trim($statusRaw));
                    $shortStatus = '--';
                    if (in_array($statusNormalized, ['present', 'half day'], true)) {
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
                        'date' => Carbon::parse($att->attendance_date)->format('Y-m-d'),
                        'date_formatted' => Carbon::parse($att->attendance_date)->format('d M Y'),
                        'status' => $statusRaw,
                        'short_status' => $shortStatus,
                        'is_late_in' => in_array($statusNormalized, ['late in', 'late in, early out'], true),
                        'is_early_out' => in_array($statusNormalized, ['early out', 'late in, early out'], true),
                        'start_time' => $att->start_time,
                        'end_time' => $att->end_time,
                        'remarks' => $att->remarks,
                    ];
                })->values();

                // Get all dates in the month and fill missing dates with 'N/A'
                $allDatesInMonth = [];
                $currentDate = $monthStart->copy();
                while ($currentDate->lte($monthEnd)) {
                    $dateStr = $currentDate->format('Y-m-d');
                    $existingAttendance = $monthlyAttendances->first(function($att) use ($dateStr) {
                        return Carbon::parse($att->attendance_date)->format('Y-m-d') === $dateStr;
                    });
                    
                    $statusRaw = $existingAttendance
                        ? $this->resolveAttendanceStatus($existingAttendance, $isFullTime, $lateArrivalTime, $earlyExitTime)
                        : (isset($approvedLeaveDates[$dateStr]) ? 'Leave' : 'N/A');
                    $statusNormalized = strtolower(trim($statusRaw));
                    $shortStatus = '--';
                    if (in_array($statusNormalized, ['present', 'half day'], true)) {
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

                    $allDatesInMonth[] = [
                        'date' => $dateStr,
                        'date_formatted' => $currentDate->format('d M Y'),
                        'status' => $statusRaw,
                        'short_status' => $shortStatus,
                        'is_late_in' => in_array($statusNormalized, ['late in', 'late in, early out'], true),
                        'is_early_out' => in_array($statusNormalized, ['early out', 'late in, early out'], true),
                        'start_time' => $existingAttendance ? $existingAttendance->start_time : null,
                        'end_time' => $existingAttendance ? $existingAttendance->end_time : null,
                        'remarks' => $existingAttendance
                            ? $existingAttendance->remarks
                            : ($approvedLeaveRemarksByDate[$dateStr] ?? null),
                    ];
                    
                    $currentDate->addDay();
                }

                $staffData = [
                    'id' => $targetStaff->id,
                    'name' => $targetStaff->name,
                    'emp_id' => $targetStaff->emp_id ?? null,
                    'designation' => $targetStaff->designation,
                    'campus' => $targetStaff->campus,
                    'status' => $attendance
                        ? $this->resolveAttendanceStatus($attendance, $isFullTime, $lateArrivalTime, $earlyExitTime)
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
                    ],
                    'status_rules' => [
                        'late_arrival_time' => $lateArrivalTime,
                        'early_exit_time' => $earlyExitTime,
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
     * Derive API display status from attendance row and salary type.
     */
    private function resolveAttendanceStatus(StaffAttendance $attendance, bool $isFullTime, ?string $lateArrivalTime, ?string $earlyExitTime): string
    {
        $statusRaw = trim((string) ($attendance->status ?? ''));
        $statusNormalized = strtolower($statusRaw);

        if (!in_array($statusNormalized, ['present', 'half day'], true)) {
            return $statusRaw !== '' ? $statusRaw : 'N/A';
        }

        $isLateIn = $this->isTimeAfter($attendance->start_time, $lateArrivalTime);
        $isEarlyOut = $this->isTimeBefore($attendance->end_time, $earlyExitTime);

        if ($isLateIn && $isEarlyOut) {
            return 'Late In, Early Out';
        }
        if ($isLateIn) {
            return 'Late In';
        }
        if ($isEarlyOut) {
            return 'Early Out';
        }

        return $isFullTime ? 'Full Time' : 'Present';
    }

    private function isTimeAfter(?string $actualTime, ?string $limitTime): bool
    {
        if (empty($actualTime) || empty($limitTime)) {
            return false;
        }

        try {
            return Carbon::parse($actualTime)->gt(Carbon::parse($limitTime));
        } catch (\Exception $e) {
            return false;
        }
    }

    private function isTimeBefore(?string $actualTime, ?string $limitTime): bool
    {
        if (empty($actualTime) || empty($limitTime)) {
            return false;
        }

        try {
            return Carbon::parse($actualTime)->lt(Carbon::parse($limitTime));
        } catch (\Exception $e) {
            return false;
        }
    }
}
