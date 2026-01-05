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
use Illuminate\Support\Facades\Auth;
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
        $filterMonth = (int) $request->get('filter_month', date('m'));
        $filterYear = (int) $request->get('filter_year', date('Y'));

        // Get logged-in staff/teacher
        $staff = Auth::guard('staff')->user();
        $isTeacher = $staff && $staff->isTeacher();

        // Get campuses - filter by teacher's assigned subjects if teacher
        $campuses = collect();
        if ($isTeacher) {
            // Get campuses from teacher's assigned subjects
            $campusesFromSubjects = Subject::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($staff->name ?? ''))])
                ->whereNotNull('campus')
                ->distinct()
                ->pluck('campus');
            
            // Get campuses from teacher's assigned sections
            $campusesFromSections = Section::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($staff->name ?? ''))])
                ->whereNotNull('campus')
                ->distinct()
                ->pluck('campus');
            
            // Merge all campuses and remove duplicates
            $allCampuses = $campusesFromSubjects->merge($campusesFromSections)->unique()->sort()->values();
            
            $campuses = $allCampuses->map(function($campus) {
                return (object)['campus_name' => $campus];
            });
        } else {
            // For non-teachers, get all campuses
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
        }

        // Get classes - filter by teacher's assigned classes if teacher
        $classes = collect();
        if ($isTeacher) {
            // Get classes from teacher's assigned subjects
            $assignedSubjects = Subject::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($staff->name ?? ''))])
                ->whereNotNull('class')
                ->get();
            
            // Get classes from teacher's assigned sections
            $assignedSections = Section::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($staff->name ?? ''))])
                ->whereNotNull('class')
                ->get();
            
            // Merge classes from both sources
            $classes = $assignedSubjects->pluck('class')
                ->merge($assignedSections->pluck('class'))
                ->map(function($class) {
                    return trim($class);
                })
                ->filter(function($class) {
                    return !empty($class);
                })
                ->unique()
                ->sort()
                ->values();
        } else {
            // For non-teachers, get all classes
            $classes = ClassModel::whereNotNull('class_name')->distinct()->pluck('class_name')->sort()->values();
            if ($classes->isEmpty()) {
                $classesFromSubjects = Subject::whereNotNull('class')->distinct()->pluck('class')->sort();
                $classes = $classesFromSubjects->isEmpty() ? collect(['Nursery', 'KG', '1st', '2nd', '3rd', '4th', '5th', '6th', '7th', '8th', '9th', '10th', '11th', '12th']) : $classesFromSubjects;
            }
        }

        // Build combined class/section options
        $classSectionOptions = collect();
        foreach ($classes as $class) {
            // Get sections for this class - filter by teacher's assigned subjects if teacher
            $sections = collect();
            if ($isTeacher) {
                // Get sections from teacher's assigned subjects for this class
                $assignedSubjects = Subject::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($staff->name ?? ''))])
                    ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))])
                    ->whereNotNull('section')
                    ->get();
                
                // Get sections from teacher's assigned sections for this class
                $assignedSections = Section::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($staff->name ?? ''))])
                    ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))])
                    ->whereNotNull('name')
                    ->get();
                
                // Merge sections from both sources
                $sections = $assignedSubjects->pluck('section')
                    ->merge($assignedSections->pluck('name'))
                    ->map(function($section) {
                        return trim($section);
                    })
                    ->filter(function($section) {
                        return !empty($section);
                    })
                    ->unique()
                    ->sort()
                    ->values();
            } else {
                // For non-teachers, get all sections
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
            // Security check: If teacher, verify that the selected class/section is assigned to them
            $hasAccess = true;
            if ($isTeacher && $filterClass) {
                $assignedSubjects = Subject::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($staff->name ?? ''))])
                    ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($filterClass))])
                    ->get();
                
                $assignedSections = Section::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($staff->name ?? ''))])
                    ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($filterClass))])
                    ->get();
                
                // Check if teacher has access to this class
                $hasAccess = $assignedSubjects->isNotEmpty() || $assignedSections->isNotEmpty();
                
                // If section is selected, verify teacher has access to that specific section
                if ($hasAccess && $filterSection) {
                    $hasSectionAccess = $assignedSubjects->where(function($subject) use ($filterSection) {
                        return strtolower(trim($subject->section ?? '')) === strtolower(trim($filterSection));
                    })->isNotEmpty() || $assignedSections->where(function($section) use ($filterSection) {
                        return strtolower(trim($section->name ?? '')) === strtolower(trim($filterSection));
                    })->isNotEmpty();
                    
                    $hasAccess = $hasSectionAccess;
                }
            }
            
            // Only fetch students if teacher has access (or if not a teacher)
            if ($hasAccess) {
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
            }

            // Get days in month
            $date = Carbon::create($filterYear, $filterMonth, 1);
            $daysInMonth = $date->daysInMonth;
            $monthName = $date->format('F');

            // Fetch actual attendance data from attendance table
            $studentIds = $students->pluck('id');
            
            // Get attendance for the entire month - use whereYear and whereMonth for reliable matching
            $attendances = StudentAttendance::whereIn('student_id', $studentIds)
                ->whereYear('attendance_date', $filterYear)
                ->whereMonth('attendance_date', $filterMonth)
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

