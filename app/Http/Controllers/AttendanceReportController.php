<?php

namespace App\Http\Controllers;

use App\Models\Campus;
use App\Models\ClassModel;
use App\Models\Section;
use App\Models\Student;
use App\Models\Subject;
use App\Models\StudentAttendance;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Carbon\Carbon;

class AttendanceReportController extends Controller
{
    /**
     * Display attendance report page.
     */
    public function index(Request $request): View
    {
        // Get filter values
        $filterCampus = $request->get('filter_campus');
        $filterClassSection = $request->get('filter_class_section');
        $filterMonth = $request->get('filter_month', date('m'));
        $filterYear = $request->get('filter_year', date('Y'));

        // Get campuses
        $campuses = Campus::orderBy('campus_name', 'asc')->get();
        if ($campuses->isEmpty()) {
            $campusesFromClasses = ClassModel::whereNotNull('campus')->distinct()->pluck('campus');
            $campusesFromSections = Section::whereNotNull('campus')->distinct()->pluck('campus');
            $campusesFromSubjects = Subject::whereNotNull('campus')->distinct()->pluck('campus');
            $allCampuses = $campusesFromClasses->merge($campusesFromSections)->merge($campusesFromSubjects)->unique()->sort();
            $campuses = $allCampuses->map(function($campus) {
                return (object)['campus_name' => $campus];
            });
        }

        // Get classes
        $classes = ClassModel::whereNotNull('class_name')->distinct()->pluck('class_name')->sort()->values();
        if ($classes->isEmpty()) {
            $classesFromSubjects = Subject::whereNotNull('class')->distinct()->pluck('class')->sort();
            $classes = $classesFromSubjects->isEmpty() ? collect(['Nursery', 'KG', '1st', '2nd', '3rd', '4th', '5th', '6th', '7th', '8th', '9th', '10th', '11th', '12th']) : $classesFromSubjects;
        }

        // Build combined class/section options
        $classSectionOptions = collect();
        foreach ($classes as $class) {
            // Get sections for this class
            $sections = Section::whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))])
                ->whereNotNull('name')
                ->distinct()
                ->pluck('name')
                ->sort()
                ->values();
            
            if ($sections->isEmpty()) {
                $sectionsFromSubjects = Subject::whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))])
                    ->whereNotNull('section')
                    ->distinct()
                    ->pluck('section')
                    ->sort();
                $sections = $sectionsFromSubjects;
            }
            
            // If no sections found, add just the class
            if ($sections->isEmpty()) {
                $classSectionOptions->push([
                    'value' => $class . '|',
                    'label' => $class
                ]);
            } else {
                // Add class-section combinations
                foreach ($sections as $section) {
                    $classSectionOptions->push([
                        'value' => $class . '|' . $section,
                        'label' => $class . ' / ' . $section
                    ]);
                }
            }
        }

        // Parse class and section from filter
        $filterClass = null;
        $filterSection = null;
        if ($filterClassSection) {
            $parts = explode('|', $filterClassSection);
            $filterClass = $parts[0] ?? null;
            $filterSection = $parts[1] ?? null;
            if ($filterSection === '') {
                $filterSection = null;
            }
        }

        // Get students and attendance data
        $students = collect();
        $attendanceData = [];
        $daysInMonth = 0;
        $monthName = '';
        
        if ($filterCampus && $filterClassSection) {
            $studentsQuery = Student::query();
            
            $campusName = is_object($filterCampus) ? ($filterCampus->campus_name ?? '') : $filterCampus;
            $studentsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campusName))]);
            $studentsQuery->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($filterClass))]);
            
            if ($filterSection) {
                $studentsQuery->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($filterSection))]);
            }
            
            $students = $studentsQuery->orderBy('student_code', 'asc')
                ->orderBy('student_name', 'asc')
                ->get();

            // Get days in month
            $date = Carbon::create($filterYear, $filterMonth, 1);
            $daysInMonth = $date->daysInMonth;
            $monthName = $date->format('F');

            // Fetch actual attendance data from attendance table
            $studentIds = $students->pluck('id');
            
            // Get attendance for the entire month
            $startDate = Carbon::create($filterYear, $filterMonth, 1)->startOfDay();
            $endDate = Carbon::create($filterYear, $filterMonth, $daysInMonth)->endOfDay();
            
            $attendances = StudentAttendance::whereIn('student_id', $studentIds)
                ->whereBetween('attendance_date', [$startDate, $endDate])
                ->get()
                ->groupBy('student_id');
            
            // Build attendance data array for each student
            foreach ($students as $student) {
                $attendanceData[$student->id] = [];
                
                // Initialize all days as empty
                for ($day = 1; $day <= $daysInMonth; $day++) {
                    $attendanceData[$student->id][$day] = '';
                }
                
                // Fill in actual attendance data
                if (isset($attendances[$student->id])) {
                    foreach ($attendances[$student->id] as $attendance) {
                        $attendanceDate = Carbon::parse($attendance->attendance_date);
                        $day = $attendanceDate->day;
                        
                        // Convert status to single letter
                        $status = strtoupper($attendance->status);
                        if ($status === 'PRESENT') {
                            $attendanceData[$student->id][$day] = 'P';
                        } elseif ($status === 'ABSENT') {
                            $attendanceData[$student->id][$day] = 'A';
                        } elseif ($status === 'HOLIDAY') {
                            $attendanceData[$student->id][$day] = 'H';
                        } elseif ($status === 'SUNDAY') {
                            $attendanceData[$student->id][$day] = 'S';
                        } elseif ($status === 'LEAVE') {
                            $attendanceData[$student->id][$day] = 'L';
                        } else {
                            $attendanceData[$student->id][$day] = '';
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

        return view('attendance.report', compact(
            'campuses',
            'classSectionOptions',
            'months',
            'years',
            'students',
            'attendanceData',
            'daysInMonth',
            'monthName',
            'filterCampus',
            'filterClassSection',
            'filterMonth',
            'filterYear',
            'filterClass',
            'filterSection'
        ));
    }
}

