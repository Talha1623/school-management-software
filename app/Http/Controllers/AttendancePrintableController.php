<?php

namespace App\Http\Controllers;

use App\Models\Staff;
use App\Models\StaffAttendance;
use App\Models\Student;
use App\Models\StudentAttendance;
use App\Models\Campus;
use App\Models\GeneralSetting;
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
            'lectureCampuses' => $this->campusesForAttendanceFilters(),
        ]);
    }

    /**
     * Campuses for subject/lecture print filter (matches subject-lecture summary page logic).
     */
    private function campusesForAttendanceFilters()
    {
        $campuses = Campus::orderBy('campus_name', 'asc')->get();
        if ($campuses->isEmpty()) {
            $fromStaff = StaffAttendance::whereNotNull('campus')
                ->distinct()
                ->pluck('campus')
                ->filter()
                ->sort()
                ->values();

            return $fromStaff->map(function ($name) {
                return (object) ['campus_name' => $name];
            });
        }

        return $campuses;
    }

    public function absentStudentsToday(Request $request): View
    {
        $dateInput = $request->get('date');
        try {
            $today = $dateInput ? Carbon::parse($dateInput)->startOfDay() : Carbon::today();
        } catch (\Exception $e) {
            $today = Carbon::today();
        }
        if ($today->year < 2000 || $today->year > 2100) {
            $today = Carbon::today();
        }

        $records = StudentAttendance::with('student')
            ->whereDate('attendance_date', $today)
            ->where('status', 'Absent')
            ->get()
            ->sortBy(function ($attendance) {
                $student = $attendance->student;
                $campus = strtolower(trim((string) ($attendance->campus ?? $student->campus ?? '')));
                $class = strtolower(trim((string) ($attendance->class ?? $student->class ?? '')));
                $section = strtolower(trim((string) ($attendance->section ?? $student->section ?? '')));
                $name = strtolower(trim((string) ($student->student_name ?? '')));

                return [$campus, $class, $section, $name];
            })
            ->values();

        return view('attendance.absent-students-today-print', [
            'records' => $records,
            'dateLabel' => $today->format('d F Y'),
            'settings' => GeneralSetting::getSettings(),
            'printedAt' => Carbon::now()->format('d M Y, h:i A'),
        ]);
    }

    public function absentStaffToday(Request $request): View
    {
        $dateInput = $request->get('date');
        try {
            $today = $dateInput ? Carbon::parse($dateInput)->startOfDay() : Carbon::today();
        } catch (\Exception $e) {
            $today = Carbon::today();
        }
        if ($today->year < 2000 || $today->year > 2100) {
            $today = Carbon::today();
        }

        $records = StaffAttendance::with('staff')
            ->whereDate('attendance_date', $today)
            ->where('status', 'Absent')
            ->get()
            ->filter(function ($attendance) {
                return $attendance->staff !== null;
            })
            ->sortBy(function ($attendance) {
                $staff = $attendance->staff;
                $campus = strtolower(trim((string) ($attendance->campus ?? $staff->campus ?? '')));
                $designation = strtolower(trim((string) ($attendance->designation ?? $staff->designation ?? '')));
                $name = strtolower(trim((string) ($staff->name ?? '')));

                return [$campus, $designation, $name];
            })
            ->values();

        return view('attendance.absent-staff-today-print', [
            'records' => $records,
            'dateLabel' => $today->format('d F Y'),
            'settings' => GeneralSetting::getSettings(),
            'printedAt' => Carbon::now()->format('d M Y, h:i A'),
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
    public function subjectLectureSummary(Request $request): View
    {
        $year = (int) $request->get('year', date('Y'));
        $month = (int) $request->get('month', (int) date('n'));
        if ($month < 1 || $month > 12) {
            $month = (int) date('n');
        }
        if ($year < 2000 || $year > 2100) {
            $year = (int) date('Y');
        }

        $filterCampus = $request->get('filter_campus');
        $start = Carbon::createFromDate($year, $month, 1)->startOfDay();
        $end = Carbon::createFromDate($year, $month, 1)->endOfMonth()->endOfDay();

        $query = StaffAttendance::whereBetween('attendance_date', [$start->toDateString(), $end->toDateString()])
            ->whereNotNull('conducted_lectures');

        if ($filterCampus) {
            $query->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim((string) $filterCampus))]);
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
                $staff = $item['staff'] ?? null;
                if (! $staff) {
                    return false;
                }
                $designation = strtolower(trim($staff->designation ?? ''));

                return strpos($designation, 'teacher') !== false;
            })
            ->sortBy(function ($row) {
                return strtolower(trim($row['staff']->name ?? ''));
            })
            ->values();

        return view('attendance.subject-lecture-summary-print', [
            'summary' => $summary,
            'monthLabel' => $start->format('F Y'),
            'filterCampus' => $filterCampus,
            'totalLectures' => $summary->sum('total_lectures'),
            'settings' => GeneralSetting::getSettings(),
            'printedAt' => Carbon::now()->format('d M Y, h:i A'),
        ]);
    }

    public function staffHourlySummary(Request $request): View
    {
        $year = (int) $request->get('year', date('Y'));
        $month = (int) $request->get('month', (int) date('n'));
        if ($month < 1 || $month > 12) {
            $month = (int) date('n');
        }
        if ($year < 2000 || $year > 2100) {
            $year = (int) date('Y');
        }

        $start = Carbon::createFromDate($year, $month, 1)->startOfDay();
        $end = Carbon::createFromDate($year, $month, 1)->endOfMonth()->endOfDay();

        $summary = StaffAttendance::whereBetween('attendance_date', [$start->toDateString(), $end->toDateString()])
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
            ->filter(function ($row) {
                return $row['staff'] !== null;
            })
            ->sortBy(function ($row) {
                return strtolower(trim($row['staff']->name ?? ''));
            })
            ->values();

        $totalMinutesAll = $summary->sum('total_minutes');

        return view('attendance.staff-hourly-summary-print', [
            'summary' => $summary,
            'monthLabel' => $start->format('F Y'),
            'totalMinutesAll' => $totalMinutesAll,
            'settings' => GeneralSetting::getSettings(),
            'printedAt' => Carbon::now()->format('d M Y, h:i A'),
        ]);
    }

    public function classWiseSummary(Request $request): View
    {
        $dateInput = $request->get('date');
        try {
            $today = $dateInput ? Carbon::parse($dateInput)->startOfDay() : Carbon::today();
        } catch (\Exception $e) {
            $today = Carbon::today();
        }
        if ($today->year < 2000 || $today->year > 2100) {
            $today = Carbon::today();
        }

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
                $query->whereNotNull('class')
                    ->where('class', '!=', '')
                    ->whereRaw("LOWER(TRIM(COALESCE(class, ''))) NOT IN ('" . implode("', '", array_map('strtolower', $passoutClasses)) . "')");
            })
            ->get();

        $summary = $attendances->groupBy(function ($attendance) {
            $student = $attendance->student;
            $campus = $student->campus ?? 'N/A';
            $class = $student->class ?? 'N/A';
            $section = $student->section ?? 'N/A';

            return strtolower(trim((string) $campus)) . '|' . strtolower(trim((string) $class)) . '|' . strtolower(trim((string) $section));
        })->map(function ($items) {
            $first = $items->first();
            $student = $first->student;
            $campus = $student->campus ?? 'N/A';
            $class = $student->class ?? 'N/A';
            $section = $student->section ?? 'N/A';
            $present = $items->where('status', 'Present')->count();
            $absent = $items->where('status', 'Absent')->count();

            return [
                'campus' => $campus,
                'class' => $class,
                'section' => $section,
                'present' => $present,
                'absent' => $absent,
                'total' => $present + $absent,
            ];
        })->sortBy(function ($row) {
            return [
                strtolower((string) $row['campus']),
                strtolower((string) $row['class']),
                strtolower((string) $row['section']),
            ];
        })->values();

        return view('attendance.classwise-summary-print', [
            'summary' => $summary,
            'dateLabel' => $today->format('d F Y'),
            'settings' => GeneralSetting::getSettings(),
            'printedAt' => Carbon::now()->format('d M Y, h:i A'),
            'totalPresent' => $summary->sum('present'),
            'totalAbsent' => $summary->sum('absent'),
        ]);
    }

    public function studentSummary(Request $request): View
    {
        $year = (int) $request->get('year', date('Y'));
        $month = (int) $request->get('month', (int) date('n'));
        if ($month < 1 || $month > 12) {
            $month = (int) date('n');
        }
        if ($year < 2000 || $year > 2100) {
            $year = (int) date('Y');
        }

        $start = Carbon::createFromDate($year, $month, 1)->startOfDay();
        $end = Carbon::createFromDate($year, $month, 1)->endOfMonth()->endOfDay();

        $allStudentsQuery = Student::query()->orderBy('student_name', 'asc');
        if ($request->filled('student_id')) {
            $allStudentsQuery->where('id', (int) $request->student_id);
        }
        $allStudents = $allStudentsQuery->get();

        $attendancesQuery = StudentAttendance::query()
            ->whereBetween('attendance_date', [$start->toDateString(), $end->toDateString()]);
        if ($request->filled('student_id')) {
            $attendancesQuery->where('student_id', (int) $request->student_id);
        }
        $attendancesByStudentId = $attendancesQuery->get()->groupBy('student_id');

        $summary = $allStudents->map(function ($student) use ($attendancesByStudentId) {
            $items = $attendancesByStudentId->get($student->id, collect());
            $present = $items->where('status', 'Present')->count();
            $absent = $items->where('status', 'Absent')->count();
            $leave = $items->where('status', 'Leave')->count();
            $totalWorkingDays = $present + $absent;
            $percentage = $totalWorkingDays > 0 ? round(($present / $totalWorkingDays) * 100, 2) : 0;

            return [
                'student' => $student,
                'present' => $present,
                'absent' => $absent,
                'leave' => $leave,
                'percentage' => $percentage,
            ];
        })->values();

        return view('attendance.student-summary-print', [
            'summary' => $summary,
            'monthLabel' => $start->format('F Y'),
            'settings' => GeneralSetting::getSettings(),
            'printedAt' => Carbon::now()->format('d M Y, h:i A'),
        ]);
    }

    public function staffSummary(Request $request): View
    {
        $year = (int) $request->get('year', date('Y'));
        $month = (int) $request->get('month', (int) date('n'));
        if ($month < 1 || $month > 12) {
            $month = (int) date('n');
        }
        if ($year < 2000 || $year > 2100) {
            $year = (int) date('Y');
        }

        $start = Carbon::createFromDate($year, $month, 1)->startOfDay();
        $end = Carbon::createFromDate($year, $month, 1)->endOfMonth()->endOfDay();

        $allStaff = Staff::query()
            ->where(function ($query) {
                $query->where('status', 'Active')
                    ->orWhereNull('status')
                    ->orWhere('status', '');
            })
            ->orderBy('name', 'asc')
            ->get();

        $attendancesByStaffId = StaffAttendance::query()
            ->whereBetween('attendance_date', [$start->toDateString(), $end->toDateString()])
            ->get()
            ->groupBy('staff_id');

        $salarySettings = \App\Models\SalarySetting::getSettings();
        $lateArrivalTime = $salarySettings->late_arrival_time ?? '09:00:00';

        $summary = $allStaff->map(function ($staff) use ($attendancesByStaffId, $lateArrivalTime) {
            $items = $attendancesByStaffId->get($staff->id, collect());
            $present = $items->where('status', 'Present')->count();
            $absent = $items->where('status', 'Absent')->count();
            $leave = $items->where('status', 'Leave')->count();

            $lateCount = 0;
            foreach ($items as $attendance) {
                if (! empty($attendance->remarks) && stripos($attendance->remarks, 'Late Arrival') !== false) {
                    $lateCount++;
                } elseif (! empty($attendance->start_time) && $attendance->status === 'Present') {
                    try {
                        $startTime = is_string($attendance->start_time)
                            ? $attendance->start_time
                            : $attendance->start_time->format('H:i:s');
                        $tStart = strtotime($startTime);
                        $tStandard = strtotime($lateArrivalTime);
                        if ($tStart && $tStandard && $tStart > $tStandard) {
                            $lateCount++;
                        }
                    } catch (\Exception $e) {
                        // Skip invalid times
                    }
                }
            }

            $totalWorkingDays = $present + $absent;
            $percentage = $totalWorkingDays > 0 ? round(($present / $totalWorkingDays) * 100, 2) : 0;

            return [
                'staff' => $staff,
                'present' => $present,
                'absent' => $absent,
                'leave' => $leave,
                'late' => $lateCount,
                'percentage' => $percentage,
            ];
        })->values();

        return view('attendance.staff-summary-print', [
            'summary' => $summary,
            'monthLabel' => $start->format('F Y'),
            'settings' => GeneralSetting::getSettings(),
            'printedAt' => Carbon::now()->format('d M Y, h:i A'),
        ]);
    }
}
