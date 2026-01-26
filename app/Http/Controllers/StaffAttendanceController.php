<?php

namespace App\Http\Controllers;

use App\Models\Staff;
use App\Models\StaffAttendance;
use App\Models\Campus;
use App\Models\Subject;
use App\Models\SalarySetting;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class StaffAttendanceController extends Controller
{
    /**
     * Display staff attendance page with filters.
     */
    public function index(Request $request): View
    {
        $campus = $request->get('campus');
        $staffCategory = $request->get('staff_category');
        $type = $request->get('type');
        $date = $request->get('date', date('Y-m-d'));

        // Get staff based on filters
        $staffQuery = Staff::query();

        if ($campus) {
            $staffQuery->where('campus', $campus);
        }

        if ($staffCategory) {
            $staffQuery->where('designation', $staffCategory);
        }

        if ($type === 'Subject Attendance') {
            $staffQuery->where('salary_type', 'lecture');
        } elseif ($type) {
            $staffQuery->where(function ($query) {
                $query->whereNull('salary_type')
                    ->orWhere('salary_type', '!=', 'lecture');
            });
        }

        $staffList = $staffQuery->orderBy('name', 'asc')->get();
        if (!$type && $staffList->where('salary_type', 'lecture')->isNotEmpty()) {
            $type = 'Subject Attendance';
        }

        // Get existing attendance data for the selected date
        $attendanceData = [];
        if ($date && $staffList->isNotEmpty()) {
            $staffIds = $staffList->pluck('id');
            $attendances = StaffAttendance::whereIn('staff_id', $staffIds)
                ->whereDate('attendance_date', $date)
                ->get()
                ->keyBy('staff_id');

            foreach ($staffList as $staff) {
                $attendance = $attendances->get($staff->id);
                $attendanceData[$staff->id] = [
                    'status' => $attendance ? $attendance->status : null,
                    'start_time' => $attendance ? $attendance->start_time : null,
                    'end_time' => $attendance ? $attendance->end_time : null,
                    'conducted_lectures' => $attendance ? $attendance->conducted_lectures : null,
                    'late_arrival' => $this->calculateLateArrival($attendance ? $attendance->start_time : null),
                ];
            }
        }

        // Get campuses only from Campus model (Manage Campuses page)
        $campuses = Campus::whereNotNull('campus_name')
            ->orderBy('campus_name', 'asc')
            ->pluck('campus_name')
            ->unique()
            ->values();

        $assignedSubjectsByStaff = [];
        if ($staffList->isNotEmpty()) {
            $staffNameToId = $staffList->mapWithKeys(function ($staff) {
                return [strtolower(trim((string) $staff->name)) => $staff->id];
            });

            $subjectsQuery = Subject::whereNotNull('teacher');
            if ($campus) {
                $subjectsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
            }
            $subjects = $subjectsQuery->get(['teacher', 'class', 'section', 'subject_name']);

            foreach ($subjects as $subject) {
                $teacherKey = strtolower(trim((string) $subject->teacher));
                if (!isset($staffNameToId[$teacherKey])) {
                    continue;
                }
                $staffId = $staffNameToId[$teacherKey];
                $labelParts = array_filter([
                    $subject->class ?? null,
                    $subject->section ?? null
                ]);
                $label = implode(' ', $labelParts);
                $subjectLabel = trim(($label ? $label . ': ' : '') . ($subject->subject_name ?? ''));
                $assignedSubjectsByStaff[$staffId][] = $subjectLabel !== '' ? $subjectLabel : 'N/A';
            }
        }

        $dateLabel = Carbon::parse($date)->format('d F Y');

        return view('attendance.staff', [
            'staffList' => $staffList,
            'attendanceData' => $attendanceData,
            'campuses' => $campuses,
            'campus' => $campus,
            'staffCategory' => $staffCategory,
            'type' => $type,
            'date' => $date,
            'dateLabel' => $dateLabel,
            'assignedSubjectsByStaff' => $assignedSubjectsByStaff,
        ]);
    }

    /**
     * Store staff attendance.
     */
    public function store(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'attendance' => 'required|array',
            'attendance.*.staff_id' => 'required|exists:staff,id',
            'attendance.*.status' => 'nullable|in:Present,Absent,Holiday,Sunday,Leave,Half Day',
            'attendance.*.start_time' => 'nullable|date_format:H:i',
            'attendance.*.end_time' => 'nullable|date_format:H:i',
            'attendance.*.leave_deduction' => 'nullable|in:Yes,No',
            'attendance.*.conducted_lectures' => 'nullable|integer|min:0',
            'attendance.*.late_arrival' => 'nullable|in:Auto,Yes,No',
        ]);

        $date = $request->input('date');
        $attendanceData = $request->input('attendance', []);

        // Detect subject attendance even if type is missing
        $attendanceType = strtolower(trim((string) $request->input('type')));
        $hasLecturesField = false;
        $hasLateArrivalField = false;
        $hasStatusField = false;
        foreach ($attendanceData as $row) {
            if (!is_array($row)) {
                continue;
            }
            if (array_key_exists('conducted_lectures', $row)) {
                $hasLecturesField = true;
            }
            if (array_key_exists('late_arrival', $row)) {
                $hasLateArrivalField = true;
            }
            if (array_key_exists('status', $row)) {
                $hasStatusField = true;
            }
        }
        $isSubjectAttendance = ($attendanceType === 'subject attendance')
            || (!$hasStatusField && ($hasLecturesField || $hasLateArrivalField || !empty($attendanceData)));

        // Filter out entries without any data
        if ($isSubjectAttendance) {
            $attendanceData = array_filter($attendanceData, function($data) {
                return is_array($data) && !empty($data['staff_id']);
            });
        } else {
            $attendanceData = array_filter($attendanceData, function($data) {
                return !empty($data['status']);
            });
        }

        if (empty($attendanceData)) {
            if ($isSubjectAttendance) {
                return redirect()->back()->with('info', 'No lectures entered to save.')->withInput();
            }
            return redirect()->back()->with('error', 'Please select at least one attendance status before saving.')->withInput();
        }

        DB::beginTransaction();
        try {
            foreach ($attendanceData as $data) {
                if (!$isSubjectAttendance && empty($data['status'])) {
                    continue;
                }

                $staff = Staff::find($data['staff_id']);
                if (!$staff) {
                    continue;
                }

                $conductedLectures = isset($data['conducted_lectures']) && $data['conducted_lectures'] !== ''
                    ? (int) $data['conducted_lectures']
                    : 0;
                $lateArrival = $data['late_arrival'] ?? null;
                $status = $data['status'] ?? null;
                if ($isSubjectAttendance) {
                    if ($conductedLectures !== null) {
                        $status = ($conductedLectures > 0) ? 'Present' : 'Absent';
                    } elseif (!$status) {
                        $status = 'N/A';
                    }
                }

                $remarks = $data['remarks'] ?? (isset($data['leave_deduction']) ? 'Leave Deduction: ' . $data['leave_deduction'] : null);
                if ($isSubjectAttendance && $lateArrival) {
                    $remarks = trim(($remarks ? $remarks . ' | ' : '') . 'Late Arrival: ' . $lateArrival);
                }

                StaffAttendance::updateOrCreate(
                    [
                        'staff_id' => $data['staff_id'],
                        'attendance_date' => $date,
                    ],
                    [
                        'status' => $status,
                        'start_time' => $data['start_time'] ?? null,
                        'end_time' => $data['end_time'] ?? null,
                        'conducted_lectures' => $conductedLectures,
                        'campus' => $staff->campus,
                        'designation' => $staff->designation,
                        'class' => null,
                        'section' => null,
                        'remarks' => $remarks,
                    ]
                );
            }

            DB::commit();
            return redirect()->route('attendance.staff', [
                'campus' => $request->input('campus'),
                'staff_category' => $request->input('staff_category'),
                'type' => $request->input('type'),
                'date' => $date,
            ])->with('success', 'Staff attendance saved successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Error saving attendance: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Calculate late arrival (assuming 9:00 AM is the standard time).
     */
    private function calculateLateArrival($startTime)
    {
        if (!$startTime) {
            return null;
        }

        try {
            // Handle different time formats
            $time = is_string($startTime) ? $startTime : $startTime->format('H:i:s');
            $settings = SalarySetting::getSettings();
            $standardTime = $settings->late_arrival_time ?? '09:00:00';
            
            $start = strtotime($time);
            $standard = strtotime($standardTime);

            if ($start && $standard && $start > $standard) {
                $diff = $start - $standard;
                $hours = floor($diff / 3600);
                $minutes = floor(($diff % 3600) / 60);
                return sprintf('%02d:%02d', $hours, $minutes);
            }
        } catch (\Exception $e) {
            // If time parsing fails, return null
        }

        return null;
    }

    /**
     * Display staff attendance overview page with all teachers
     */
    public function overview(Request $request): View
    {
        $year = $request->get('year', date('Y'));
        
        // Get all active staff (dynamic - includes newly added, excludes deleted)
        // Only show staff with Active status or no status set (for backward compatibility)
        $allStaff = Staff::where(function($query) {
                $query->where('status', 'Active')
                      ->orWhereNull('status')
                      ->orWhere('status', '');
            })
            ->orderBy('name', 'asc')
            ->get();
        
        // Month names
        $monthNames = [
            '01' => 'January',
            '02' => 'February',
            '03' => 'March',
            '04' => 'April',
            '05' => 'May',
            '06' => 'June',
            '07' => 'July',
            '08' => 'August',
            '09' => 'September',
            '10' => 'October',
            '11' => 'November',
            '12' => 'December',
        ];
        
        // Prepare data for each staff member
        $staffReports = [];
        
        foreach ($allStaff as $staff) {
            // Get all attendance records for this staff for the selected year
            $attendances = StaffAttendance::where('staff_id', $staff->id)
                ->whereYear('attendance_date', $year)
                ->get();
            
            // Key by date string
            $attendancesByDate = [];
            foreach ($attendances as $attendance) {
                $dateKey = $attendance->attendance_date->format('Y-m-d');
                $attendancesByDate[$dateKey] = $attendance;
            }
            
            // Prepare daily attendance data for each month
            $dailyAttendance = [];
            $monthlySummary = [];
            
            foreach ($monthNames as $monthNum => $monthName) {
                // Get days in month
                $daysInMonth = cal_days_in_month(CAL_GREGORIAN, (int)$monthNum, (int)$year);
                
                // Daily attendance for this month
                $monthDailyData = [];
                for ($day = 1; $day <= $daysInMonth; $day++) {
                    $date = sprintf('%s-%02d-%02d', $year, $monthNum, $day);
                    $attendance = $attendancesByDate[$date] ?? null;
                    
                    if ($attendance) {
                        $status = $attendance->status;
                        // Map status to display format
                        if ($status == 'Present') {
                            $monthDailyData[$day] = 'P';
                        } elseif ($status == 'Absent') {
                            $monthDailyData[$day] = 'A';
                        } elseif ($status == 'Leave') {
                            $monthDailyData[$day] = 'L';
                        } elseif ($status == 'Holiday') {
                            $monthDailyData[$day] = 'H';
                        } elseif ($status == 'Sunday') {
                            $monthDailyData[$day] = 'S';
                        } else {
                            $monthDailyData[$day] = '--';
                        }
                    } else {
                        $monthDailyData[$day] = '--';
                    }
                }
                
                $dailyAttendance[$monthNum] = [
                    'month_name' => $monthName,
                    'days' => $monthDailyData,
                ];
                
                // Monthly summary
                $monthAttendances = collect();
                foreach ($attendancesByDate as $dateKey => $attendance) {
                    $attDate = Carbon::parse($dateKey);
                    if ($attDate->year == (int)$year && $attDate->month == (int)$monthNum) {
                        $monthAttendances->push($attendance);
                    }
                }
                
                $monthlySummary[] = [
                    'month' => $monthNum,
                    'month_name' => $monthName,
                    'present' => $monthAttendances->where('status', 'Present')->count(),
                    'absent' => $monthAttendances->where('status', 'Absent')->count(),
                    'leave' => $monthAttendances->where('status', 'Leave')->count(),
                    'holiday' => $monthAttendances->where('status', 'Holiday')->count(),
                    'sunday' => $monthAttendances->where('status', 'Sunday')->count(),
                ];
            }
            
            $staffReports[] = [
                'staff' => $staff,
                'daily_attendance' => $dailyAttendance,
                'monthly_summary' => $monthlySummary,
            ];
        }
        
        // Year options
        $currentYear = date('Y');
        $years = collect();
        for ($i = 0; $i < 6; $i++) {
            $years->push($currentYear - $i);
        }
        
        return view('attendance.staff-overview', compact(
            'staffReports',
            'year',
            'years',
            'monthNames'
        ));
    }
}

