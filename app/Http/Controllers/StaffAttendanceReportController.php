<?php

namespace App\Http\Controllers;

use App\Models\Campus;
use App\Models\Staff;
use App\Models\StaffAttendance;
use App\Models\Subject;
use App\Models\Section;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class StaffAttendanceReportController extends Controller
{
    /**
     * Display staff attendance report page.
     */
    public function index(Request $request): View
    {
        // Get filter values
        $filterCampus = $request->get('filter_campus');
        $filterDesignation = $request->get('filter_designation');
        $filterMonth = (int) $request->get('filter_month', date('m'));
        $filterYear = (int) $request->get('filter_year', date('Y'));

        // Check if staff is logged in and is a teacher
        $loggedInStaff = Auth::guard('staff')->user();
        $isTeacher = $loggedInStaff && $loggedInStaff->isTeacher();
        $teacherName = $isTeacher ? trim($loggedInStaff->name ?? '') : null;
        
        // Get campuses for dropdown - filter by teacher's assigned campuses if teacher
        if ($isTeacher && $teacherName) {
            // Get campuses from teacher's assigned subjects
            $teacherCampuses = Subject::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower($teacherName)])
                ->whereNotNull('campus')
                ->distinct()
                ->pluck('campus')
                ->merge(
                    Section::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower($teacherName)])
                        ->whereNotNull('campus')
                        ->distinct()
                        ->pluck('campus')
                )
                ->map(fn($c) => trim($c))
                ->filter(fn($c) => !empty($c))
                ->unique()
                ->sort()
                ->values();
            
            // Filter Campus model results to only show assigned campuses
            if ($teacherCampuses->isNotEmpty()) {
                $campuses = Campus::orderBy('campus_name', 'asc')
                    ->get()
                    ->filter(function($campus) use ($teacherCampuses) {
                        return $teacherCampuses->contains(strtolower(trim($campus->campus_name ?? '')));
                    });
                
                // If no campuses found in Campus model, create objects from teacher campuses
                if ($campuses->isEmpty()) {
                    $campuses = $teacherCampuses->map(function($campus) {
                        return (object)['campus_name' => $campus];
                    });
                }
            } else {
                // If teacher has no assigned campuses, show empty
                $campuses = collect();
            }
        } else {
            // For non-teachers (admin, staff, etc.), get all campuses
            $campuses = Campus::orderBy('campus_name', 'asc')->get();
            if ($campuses->isEmpty()) {
                $campusesFromStaff = Staff::whereNotNull('campus')->distinct()->pluck('campus');
                $allCampuses = $campusesFromStaff->unique()->sort();
                $campuses = $allCampuses->map(function($campus) {
                    return (object)['campus_name' => $campus];
                });
            }
        }

        // Get designations - filter by teacher's designation if teacher
        if ($isTeacher && $teacherName) {
            // Get designation from logged-in teacher
            $teacherDesignation = $loggedInStaff->designation ?? null;
            $designations = $teacherDesignation ? collect([$teacherDesignation]) : collect();
        } else {
            // For non-teachers, get all designations
            $designations = Staff::whereNotNull('designation')
                ->distinct()
                ->pluck('designation')
                ->filter()
                ->sort()
                ->values();
        }

        // Get staff and attendance data
        $staffList = collect();
        $attendanceData = [];
        $daysInMonth = 0;
        $monthName = '';
        
        if ($filterCampus && $filterDesignation) {
            $staffQuery = Staff::query();
            
            $campusName = is_object($filterCampus) ? ($filterCampus->campus_name ?? '') : $filterCampus;
            $staffQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campusName))]);
            $staffQuery->whereRaw('LOWER(TRIM(designation)) = ?', [strtolower(trim($filterDesignation))]);
            
            // If teacher, only show their own attendance
            if ($isTeacher && $teacherName) {
                $staffQuery->whereRaw('LOWER(TRIM(name)) = ?', [strtolower($teacherName)]);
            }
            
            $staffList = $staffQuery->orderBy('name', 'asc')->get();

            // Get days in month
            $date = Carbon::create($filterYear, $filterMonth, 1);
            $daysInMonth = $date->daysInMonth;
            $monthName = $date->format('F');

            // Fetch actual attendance data from attendance table
            $staffIds = $staffList->pluck('id');
            
            // Get attendance for the entire month
            $attendances = StaffAttendance::whereIn('staff_id', $staffIds)
                ->whereYear('attendance_date', $filterYear)
                ->whereMonth('attendance_date', $filterMonth)
                ->get()
                ->groupBy('staff_id');
            
            // Build attendance data array for each staff
            foreach ($staffList as $staff) {
                $attendanceData[$staff->id] = [];
                
                // Initialize all days as empty
                for ($day = 1; $day <= $daysInMonth; $day++) {
                    $attendanceData[$staff->id][$day] = '';
                }
                
                // Fill in actual attendance data
                if (isset($attendances[$staff->id])) {
                    foreach ($attendances[$staff->id] as $attendance) {
                        $attendanceDate = Carbon::parse($attendance->attendance_date);
                        $day = $attendanceDate->day;
                        
                        // Convert status to single letter
                        $status = strtoupper($attendance->status);
                        if ($status === 'PRESENT') {
                            $attendanceData[$staff->id][$day] = 'P';
                        } elseif ($status === 'ABSENT') {
                            $attendanceData[$staff->id][$day] = 'A';
                        } elseif ($status === 'HOLIDAY') {
                            $attendanceData[$staff->id][$day] = 'H';
                        } elseif ($status === 'SUNDAY') {
                            $attendanceData[$staff->id][$day] = 'S';
                        } elseif ($status === 'LEAVE') {
                            $attendanceData[$staff->id][$day] = 'L';
                        } else {
                            $attendanceData[$staff->id][$day] = '';
                        }
                    }
                }
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

        return view('attendance.staff-report', compact(
            'campuses',
            'designations',
            'months',
            'years',
            'staffList',
            'attendanceData',
            'daysInMonth',
            'monthName',
            'filterCampus',
            'filterDesignation',
            'filterMonth',
            'filterYear'
        ));
    }
}
