<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Staff;
use App\Models\StaffAttendance;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class StaffAttendanceController extends Controller
{
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

            // Calculate monthly totals (based on StaffAttendance status values)
            $totalPresent = $monthlyAttendances->where('status', 'Present')->count();
            $totalAbsent = $monthlyAttendances->where('status', 'Absent')->count();
            $totalLeave = $monthlyAttendances->where('status', 'Leave')->count();
            $totalHoliday = $monthlyAttendances->where('status', 'Holiday')->count();
            $totalSunday = $monthlyAttendances->where('status', 'Sunday')->count();
            
            // Calculate total hours (for present days with start_time and end_time)
            $totalHours = 0;
            foreach ($monthlyAttendances->where('status', 'Present') as $attendance) {
                if ($attendance->start_time && $attendance->end_time) {
                    try {
                        $start = Carbon::parse($attendance->start_time);
                        $end = Carbon::parse($attendance->end_time);
                        $hours = $end->diffInHours($start);
                        $totalHours += $hours;
                    } catch (\Exception $e) {
                        // If time parsing fails, skip
                    }
                }
            }

            // Get attendance for today (if within the month) or first day of month
            $today = Carbon::today();
            $checkDate = ($today->year == $year && $today->month == $month) ? $today : $attendanceDate;
            $attendance = StaffAttendance::where('staff_id', $targetStaffId)
                ->whereDate('attendance_date', $checkDate->format('Y-m-d'))
                ->first();

                // Format date-wise attendance
                $attendanceByDate = $monthlyAttendances->map(function($att) {
                    return [
                        'date' => Carbon::parse($att->attendance_date)->format('Y-m-d'),
                        'date_formatted' => Carbon::parse($att->attendance_date)->format('d M Y'),
                        'status' => $att->status,
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
                    
                    $allDatesInMonth[] = [
                        'date' => $dateStr,
                        'date_formatted' => $currentDate->format('d M Y'),
                        'status' => $existingAttendance ? $existingAttendance->status : 'N/A',
                        'start_time' => $existingAttendance ? $existingAttendance->start_time : null,
                        'end_time' => $existingAttendance ? $existingAttendance->end_time : null,
                        'remarks' => $existingAttendance ? $existingAttendance->remarks : null,
                    ];
                    
                    $currentDate->addDay();
                }

                $staffData = [
                    'id' => $targetStaff->id,
                    'name' => $targetStaff->name,
                    'emp_id' => $targetStaff->emp_id ?? null,
                    'designation' => $targetStaff->designation,
                    'campus' => $targetStaff->campus,
                    'status' => $attendance ? $attendance->status : 'N/A',
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
                        'total_present' => $totalPresent,
                        'total_absent' => $totalAbsent,
                        'total_leave' => $totalLeave,
                        'total_holiday' => $totalHoliday,
                        'total_sunday' => $totalSunday,
                        'total_hours' => $totalHours,
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
}
