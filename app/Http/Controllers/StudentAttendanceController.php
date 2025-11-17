<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\ClassModel;
use App\Models\Section;
use App\Models\StudentAttendance;
use Illuminate\Http\Request;
use Illuminate\View\View;

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
        
        // Get classes
        $classes = ClassModel::whereNotNull('class_name')->distinct()->pluck('class_name')->sort()->values();
        if ($classes->isEmpty()) {
            $classesFromStudents = Student::whereNotNull('class')->distinct()->pluck('class')->sort();
            $classes = $classesFromStudents;
        }
        
        // Get sections for selected class (if class is selected)
        $sections = collect();
        if ($filterClass) {
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
        
        // Get students based on filters
        $students = collect();
        $attendanceData = [];
        
        if ($filterClass && $filterDate) {
            $studentsQuery = Student::query();
            
            if ($filterClass) {
                $studentsQuery->where('class', $filterClass);
            }
            
            if ($filterSection) {
                $studentsQuery->where('section', $filterSection);
            }
            
            $allStudents = $studentsQuery->orderBy('student_name', 'asc')->get();
            
            // Get attendance data for the selected date
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

        // Get sections for the selected class
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

