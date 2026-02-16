<?php

namespace App\Http\Controllers;

use App\Models\Staff;
use App\Models\StaffAttendance;
use App\Models\Student;
use App\Models\StudentAttendance;
use App\Models\Campus;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AttendancePrintableController extends Controller
{
    public function index(): View
    {
        $today = Carbon::today();
        $presentStaffToday = StaffAttendance::whereDate('attendance_date', $today)
            ->where('status', 'Present')
            ->count();
        $absentStaffToday = StaffAttendance::whereDate('attendance_date', $today)
            ->where('status', 'Absent')
            ->count();
        $presentStudentsToday = StudentAttendance::whereDate('attendance_date', $today)
            ->where('status', 'Present')
            ->count();
        $absentStudentsToday = StudentAttendance::whereDate('attendance_date', $today)
            ->where('status', 'Absent')
            ->count();

        return view('attendance.printable-reports', [
            'presentStaffToday' => $presentStaffToday,
            'absentStaffToday' => $absentStaffToday,
            'presentStudentsToday' => $presentStudentsToday,
            'absentStudentsToday' => $absentStudentsToday,
        ]);
    }

    public function absentStudentsToday(): View
    {
        $today = Carbon::today();
        $records = StudentAttendance::with('student')
            ->whereDate('attendance_date', $today)
            ->where('status', 'Absent')
            ->orderBy('campus')
            ->orderBy('class')
            ->orderBy('section')
            ->get();

        return view('attendance.absent-students-today-print', [
            'records' => $records,
            'dateLabel' => $today->format('d F Y'),
            'printedAt' => Carbon::now()->format('d-m-Y H:i'),
        ]);
    }

    public function absentStaffToday(): View
    {
        $today = Carbon::today();
        $records = StaffAttendance::with('staff')
            ->whereDate('attendance_date', $today)
            ->where('status', 'Absent')
            ->orderBy('campus')
            ->orderBy('designation')
            ->get();

        return view('attendance.absent-staff-today-print', [
            'records' => $records,
            'dateLabel' => $today->format('d F Y'),
            'printedAt' => Carbon::now()->format('d-m-Y H:i'),
        ]);
    }

    public function presentStaffToday(): View
    {
        $today = Carbon::today();
        $records = StaffAttendance::with('staff')
            ->whereDate('attendance_date', $today)
            ->where('status', 'Present')
            ->orderBy('campus')
            ->orderBy('designation')
            ->get();

        return view('attendance.present-staff-today-print', [
            'records' => $records,
            'dateLabel' => $today->format('d F Y'),
            'printedAt' => Carbon::now()->format('d-m-Y H:i'),
        ]);
    }

    /**
     * Display Subject/Lecture Attendance Summary page with filters.
     */
    public function subjectLectureSummaryIndex(Request $request): View
    {
        $filterCampus = $request->get('filter_campus');
        $filterMonth = $request->get('filter_month', date('m'));
        $filterYear = $request->get('filter_year', date('Y'));

        // Get campuses
        $campuses = Campus::orderBy('campus_name', 'asc')->get();
        if ($campuses->isEmpty()) {
            $campusesFromStaff = StaffAttendance::whereNotNull('campus')
                ->distinct()
                ->pluck('campus')
                ->sort()
                ->values();
            
            $campuses = $campusesFromStaff->map(function ($campus) {
                return (object)['campus_name' => $campus];
            });
        }

        // Calculate date range
        $start = Carbon::create($filterYear, $filterMonth, 1)->startOfMonth();
        $end = Carbon::create($filterYear, $filterMonth, 1)->endOfMonth();

        // Build query
        $query = StaffAttendance::whereBetween('attendance_date', [$start, $end])
            ->whereNotNull('conducted_lectures');

        if ($filterCampus) {
            $query->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($filterCampus))]);
        }

        $summary = $query->with('staff')
            ->get()
            ->groupBy('staff_id')
            ->map(function ($items) {
                $staff = $items->first()->staff;
                $total = $items->sum(function ($item) {
                    return (int) ($item->conducted_lectures ?? 0);
                });
                return [
                    'staff' => $staff,
                    'total_lectures' => $total,
                ];
            })
            ->filter(function ($item) {
                // Only show teachers (designation contains "teacher")
                $staff = $item['staff'] ?? null;
                if (!$staff) {
                    return false;
                }
                $designation = strtolower(trim($staff->designation ?? ''));
                return strpos($designation, 'teacher') !== false;
            })
            ->values();

        return view('attendance.subject-lecture-summary', [
            'summary' => $summary,
            'campuses' => $campuses,
            'filterCampus' => $filterCampus,
            'filterMonth' => $filterMonth,
            'filterYear' => $filterYear,
            'monthLabel' => $start->format('F Y'),
        ]);
    }

    /**
     * Print Subject/Lecture Attendance Summary.
     */
    public function subjectLectureSummary(): View
    {
        $start = Carbon::now()->startOfMonth();
        $end = Carbon::now()->endOfMonth();

        $summary = StaffAttendance::whereBetween('attendance_date', [$start, $end])
            ->whereNotNull('conducted_lectures')
            ->with('staff')
            ->get()
            ->groupBy('staff_id')
            ->map(function ($items) {
                $staff = $items->first()->staff;
                $total = $items->sum(function ($item) {
                    return (int) ($item->conducted_lectures ?? 0);
                });
                return [
                    'staff' => $staff,
                    'total_lectures' => $total,
                ];
            })
            ->filter(function ($item) {
                // Only show teachers (designation contains "teacher")
                $staff = $item['staff'] ?? null;
                if (!$staff) {
                    return false;
                }
                $designation = strtolower(trim($staff->designation ?? ''));
                return strpos($designation, 'teacher') !== false;
            })
            ->values();

        return view('attendance.subject-lecture-summary-print', [
            'summary' => $summary,
            'monthLabel' => $start->format('F Y'),
            'printedAt' => Carbon::now()->format('d-m-Y H:i'),
        ]);
    }

    public function staffHourlySummary(): View
    {
        $start = Carbon::now()->startOfMonth();
        $end = Carbon::now()->endOfMonth();

        $summary = StaffAttendance::whereBetween('attendance_date', [$start, $end])
            ->whereNotNull('start_time')
            ->whereNotNull('end_time')
            ->with('staff')
            ->get()
            ->groupBy('staff_id')
            ->map(function ($items) {
                $staff = $items->first()->staff;
                $totalMinutes = 0;
                foreach ($items as $item) {
                    $startTime = Carbon::parse($item->attendance_date->format('Y-m-d') . ' ' . $item->start_time);
                    $endTime = Carbon::parse($item->attendance_date->format('Y-m-d') . ' ' . $item->end_time);
                    if ($endTime->greaterThan($startTime)) {
                        $totalMinutes += $startTime->diffInMinutes($endTime);
                    }
                }
                return [
                    'staff' => $staff,
                    'total_minutes' => $totalMinutes,
                ];
            })
            ->values();

        return view('attendance.staff-hourly-summary-print', [
            'summary' => $summary,
            'monthLabel' => $start->format('F Y'),
            'printedAt' => Carbon::now()->format('d-m-Y H:i'),
        ]);
    }

    public function classWiseSummary(): View
    {
        $today = Carbon::today();
        
        // Passout classes to exclude
        $passoutClasses = [
            'passout',
            'pass out',
            'passed out',
            'passedout',
            'graduated',
            'graduate',
            'alumni',
        ];
        
        $attendances = StudentAttendance::with('student')
            ->whereDate('attendance_date', $today)
            ->whereHas('student', function ($query) use ($passoutClasses) {
                // Exclude passout students
                $query->whereNotNull('class')
                    ->where('class', '!=', '')
                    ->whereRaw("LOWER(TRIM(COALESCE(class, ''))) NOT IN ('" . implode("', '", array_map('strtolower', $passoutClasses)) . "')");
            })
            ->get();

        $summary = $attendances->groupBy(function ($attendance) {
            $student = $attendance->student;
            $class = $student->class ?? 'N/A';
            $section = $student->section ?? 'N/A';
            return strtolower(trim($class)) . '|' . strtolower(trim($section));
        })->map(function ($items) {
            $first = $items->first();
            $student = $first->student;
            $class = $student->class ?? 'N/A';
            $section = $student->section ?? 'N/A';
            $present = $items->where('status', 'Present')->count();
            $absent = $items->where('status', 'Absent')->count();
            return [
                'class' => $class,
                'section' => $section,
                'present' => $present,
                'absent' => $absent,
            ];
        })->values();

        return view('attendance.classwise-summary-print', [
            'summary' => $summary,
            'dateLabel' => $today->format('d F Y'),
            'printedAt' => Carbon::now()->format('d-m-Y H:i'),
        ]);
    }

    public function studentSummary(\Illuminate\Http\Request $request): View
    {
        $start = Carbon::now()->startOfMonth();
        $end = Carbon::now()->endOfMonth();

        $attendances = StudentAttendance::with('student')
            ->whereBetween('attendance_date', [$start, $end])
            ->when($request->filled('student_id'), function ($query) use ($request) {
                $query->where('student_id', $request->student_id);
            })
            ->get();

        $summary = $attendances->groupBy('student_id')->map(function ($items) {
            $student = $items->first()->student;
            return [
                'student' => $student,
                'present' => $items->where('status', 'Present')->count(),
                'absent' => $items->where('status', 'Absent')->count(),
                'leave' => $items->where('status', 'Leave')->count(),
            ];
        })->values();

        return view('attendance.student-summary-print', [
            'summary' => $summary,
            'monthLabel' => $start->format('F Y'),
            'printedAt' => Carbon::now()->format('d-m-Y H:i'),
        ]);
    }

    public function staffSummary(): View
    {
        $start = Carbon::now()->startOfMonth();
        $end = Carbon::now()->endOfMonth();

        $attendances = StaffAttendance::with('staff')
            ->whereBetween('attendance_date', [$start, $end])
            ->get();

        // Get salary settings for late arrival calculation
        $salarySettings = \App\Models\SalarySetting::getSettings();
        $lateArrivalTime = $salarySettings->late_arrival_time ?? '09:00:00';

        $summary = $attendances->groupBy('staff_id')->map(function ($items) use ($lateArrivalTime) {
            $staff = $items->first()->staff;
            $present = $items->where('status', 'Present')->count();
            $absent = $items->where('status', 'Absent')->count();
            
            // Calculate late arrivals
            $lateCount = 0;
            foreach ($items as $attendance) {
                // Check if remarks contain "Late Arrival"
                if (!empty($attendance->remarks) && stripos($attendance->remarks, 'Late Arrival') !== false) {
                    $lateCount++;
                } elseif (!empty($attendance->start_time) && $attendance->status === 'Present') {
                    // Calculate late arrival from start_time
                    try {
                        $startTime = is_string($attendance->start_time) ? $attendance->start_time : $attendance->start_time->format('H:i:s');
                        $start = strtotime($startTime);
                        $standard = strtotime($lateArrivalTime);
                        
                        if ($start && $standard && $start > $standard) {
                            $lateCount++;
                        }
                    } catch (\Exception $e) {
                        // Skip invalid times
                    }
                }
            }
            
            // Calculate total working days (present + absent)
            $totalWorkingDays = $present + $absent;
            $percentage = $totalWorkingDays > 0 ? round(($present / $totalWorkingDays) * 100, 2) : 0;
            
            return [
                'staff' => $staff,
                'present' => $present,
                'absent' => $absent,
                'late' => $lateCount,
                'percentage' => $percentage,
            ];
        })->values();

        return view('attendance.staff-summary-print', [
            'summary' => $summary,
            'monthLabel' => $start->format('F Y'),
            'printedAt' => Carbon::now()->format('d-m-Y H:i'),
        ]);
    }
}
