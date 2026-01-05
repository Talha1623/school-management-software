<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\ClassModel;
use App\Models\Section;
use App\Models\StudentAttendance;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;

class StudentAttendanceController extends Controller
{
    /**
     * Display student attendance page.
     */
    public function index(Request $request): View
    {
        // Get filter values
        $filterType = $request->get('filter_type');
        $filterClass = $request->get('filter_class');
        $filterSection = $request->get('filter_section');
        $filterDate = $request->get('filter_date', date('Y-m-d'));
        
        // Get classes - filter by teacher's assigned classes if teacher
        $classes = collect();
        $staff = Auth::guard('staff')->user();
        
        if ($staff && $staff->isTeacher()) {
            // Get classes from teacher's assigned subjects
            $assignedSubjects = Subject::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($staff->name ?? ''))])
                ->get();
            
            // Get classes from teacher's assigned sections
            $assignedSections = Section::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($staff->name ?? ''))])
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
            // For non-teachers, get all classes from ClassModel only (dynamic - updates when classes are added/deleted)
            $classes = ClassModel::whereNotNull('class_name')
                ->orderBy('numeric_no', 'asc')
                ->orderBy('class_name', 'asc')
                ->distinct()
                ->pluck('class_name')
                ->unique()
                ->values();
        }
        
        // Get sections for selected class (if class is selected)
        // Filter by teacher's assigned subjects if teacher
        $sections = collect();
        if ($filterClass) {
            if ($staff && $staff->isTeacher()) {
                // Get sections from teacher's assigned subjects for this class
                $assignedSubjects = Subject::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($staff->name ?? ''))])
                    ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($filterClass))])
                    ->get();
                
                // Get sections from teacher's assigned sections for this class
                $assignedSections = Section::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($staff->name ?? ''))])
                    ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($filterClass))])
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
                $sections = Section::where('class', $filterClass)
                    ->whereNotNull('name')
                    ->distinct()
                    ->pluck('name')
                    ->sort()
                    ->values();
                
                if ($sections->isEmpty()) {
                    $sectionsFromStudents = Student::where('class', $filterClass)
                        ->whereNotNull('section')
                        ->distinct()
                        ->pluck('section')
                        ->sort();
                    $sections = $sectionsFromStudents;
                }
            }
        }
        
        // Get students based on filters
        $students = collect();
        $attendanceData = [];
        
        if ($filterClass && $filterDate) {
            $studentsQuery = Student::query();
            
            // Use case-insensitive matching for class (same as API)
            if ($filterClass) {
                $studentsQuery->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($filterClass))]);
            }
            
            // Use case-insensitive matching for section (same as API)
            if ($filterSection) {
                $studentsQuery->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($filterSection))]);
            }
            
            $allStudents = $studentsQuery->orderBy('student_name', 'asc')->get();
            
            // Get attendance data for the selected date
            // Query attendance by student_id and date (case-insensitive matching not needed here as we're using IDs)
            $studentIds = $allStudents->pluck('id');
            $attendances = StudentAttendance::whereIn('student_id', $studentIds)
                ->whereDate('attendance_date', $filterDate)
                ->get()
                ->keyBy('student_id');
            
            // Build attendance data array for all students
            foreach ($allStudents as $student) {
                $attendance = $attendances->get($student->id);
                $attendanceData[$student->id] = $attendance ? $attendance->status : 'N/A';
            }
            
            $students = $allStudents;
        }
        
        $types = collect(['normal students' => 'Normal Students']);
        $statusOptions = ['Present', 'Absent', 'Holiday', 'Sunday', 'Leave', 'N/A'];
        
        return view('attendance.student', compact(
            'classes', 'sections', 'types', 'statusOptions',
            'filterType', 'filterClass', 'filterSection', 'filterDate',
            'students', 'attendanceData'
        ));
    }

    /**
     * Get sections by class name (AJAX).
     */
    public function getSectionsByClass(Request $request)
    {
        $className = $request->get('class');
        
        if (!$className) {
            return response()->json(['sections' => []]);
        }

        $staff = Auth::guard('staff')->user();
        $sections = collect();
        
        // Filter by teacher's assigned subjects and sections if teacher
        if ($staff && $staff->isTeacher()) {
            // Get sections from teacher's assigned subjects for this class
            $assignedSubjects = Subject::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($staff->name ?? ''))])
                ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($className))])
                ->get();
            
            // Get sections from teacher's assigned sections for this class
            $assignedSections = Section::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($staff->name ?? ''))])
                ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($className))])
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
            $sections = Section::where('class', $className)
                ->whereNotNull('name')
                ->orderBy('name', 'asc')
                ->distinct()
                ->pluck('name')
                ->values();
            
            // If no sections found in Section model, get from students
            if ($sections->isEmpty()) {
                $sections = Student::where('class', $className)
                    ->whereNotNull('section')
                    ->distinct()
                    ->pluck('section')
                    ->sort()
                    ->values();
            }
        }

        return response()->json(['sections' => $sections]);
    }

    /**
     * Store or update student attendance.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'student_id' => ['required', 'exists:students,id'],
            'attendance_date' => ['required', 'date'],
            'status' => ['required', 'in:Present,Absent,Holiday,Sunday,Leave,N/A'],
        ]);

        $student = Student::findOrFail($validated['student_id']);

        // Create or update attendance
        StudentAttendance::updateOrCreate(
            [
                'student_id' => $validated['student_id'],
                'attendance_date' => $validated['attendance_date'],
            ],
            [
                'status' => $validated['status'],
                'campus' => $student->campus,
                'class' => $student->class,
                'section' => $student->section,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Attendance updated successfully!',
            'status' => $validated['status']
        ]);
    }

}

