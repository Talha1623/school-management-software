<?php

namespace App\Http\Controllers;

use App\Models\Staff;
use App\Models\StaffAttendance;
use App\Models\Campus;
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

        $staffList = $staffQuery->orderBy('name', 'asc')->get();

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

        return view('attendance.staff', [
            'staffList' => $staffList,
            'attendanceData' => $attendanceData,
            'campuses' => $campuses,
            'campus' => $campus,
            'staffCategory' => $staffCategory,
            'type' => $type,
            'date' => $date,
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
            'attendance.*.status' => 'nullable|in:Present,Absent,Leave,Half Day',
            'attendance.*.start_time' => 'nullable|date_format:H:i',
            'attendance.*.end_time' => 'nullable|date_format:H:i',
            'attendance.*.leave_deduction' => 'nullable|in:Yes,No',
        ]);

        $date = $request->input('date');
        $attendanceData = $request->input('attendance', []);

        // Filter out entries without status (only save entries with status selected)
        $attendanceData = array_filter($attendanceData, function($data) {
            return !empty($data['status']);
        });

        if (empty($attendanceData)) {
            return redirect()->back()->with('error', 'Please select at least one attendance status before saving.')->withInput();
        }

        DB::beginTransaction();
        try {
            foreach ($attendanceData as $data) {
                if (empty($data['status'])) {
                    continue;
                }

                $staff = Staff::find($data['staff_id']);
                if (!$staff) {
                    continue;
                }

                StaffAttendance::updateOrCreate(
                    [
                        'staff_id' => $data['staff_id'],
                        'attendance_date' => $date,
                    ],
                    [
                        'status' => $data['status'],
                        'start_time' => $data['start_time'] ?? null,
                        'end_time' => $data['end_time'] ?? null,
                        'campus' => $staff->campus,
                        'designation' => $staff->designation,
                        'class' => null,
                        'section' => null,
                        'remarks' => $data['remarks'] ?? (isset($data['leave_deduction']) ? 'Leave Deduction: ' . $data['leave_deduction'] : null),
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
            $standardTime = '09:00:00';
            
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

