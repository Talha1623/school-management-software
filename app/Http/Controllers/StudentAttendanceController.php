<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\ClassModel;
use App\Models\Section;
use App\Models\StudentAttendance;
use App\Models\StudentPayment;
use App\Models\Subject;
use App\Models\Campus;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class StudentAttendanceController extends Controller
{
    /**
     * Display student attendance page.
     */
    public function index(Request $request): View
    {
        // Get filter values
        $filterCampus = $request->get('filter_campus');
        $filterType = $request->get('filter_type');
        $filterClass = $request->get('filter_class');
        $filterSection = $request->get('filter_section');
        $filterDate = $request->get('filter_date', date('Y-m-d'));

        // Get campuses
        $campuses = Campus::orderBy('campus_name', 'asc')->get();
        if ($campuses->isEmpty()) {
            $campusesFromClasses = ClassModel::whereNotNull('campus')->distinct()->pluck('campus');
            $campusesFromSections = Section::whereNotNull('campus')->distinct()->pluck('campus');
            $campusesFromStudents = Student::whereNotNull('campus')->distinct()->pluck('campus');
            $allCampuses = $campusesFromClasses
                ->merge($campusesFromSections)
                ->merge($campusesFromStudents)
                ->unique()
                ->sort()
                ->values();
            $campuses = $allCampuses->map(function ($campus) {
                return (object)['campus_name' => $campus];
            });
        }
        
        // Get classes - filter by teacher's assigned classes if teacher
        $classes = collect();
        $staff = Auth::guard('staff')->user();
        
        if ($staff && $staff->isTeacher()) {
            // Get classes from teacher's assigned subjects
            $assignedSubjects = Subject::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($staff->name ?? ''))]);
            if ($filterCampus) {
                $assignedSubjects->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($filterCampus))]);
            }
            $assignedSubjects = $assignedSubjects
                ->get();
            
            // Get classes from teacher's assigned sections
            $assignedSections = Section::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($staff->name ?? ''))]);
            if ($filterCampus) {
                $assignedSections->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($filterCampus))]);
            }
            $assignedSections = $assignedSections
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
            $classesQuery = ClassModel::whereNotNull('class_name');
            if ($filterCampus) {
                $classesQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($filterCampus))]);
            }
            $classes = $classesQuery
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
                    ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($filterClass))]);
                if ($filterCampus) {
                    $assignedSubjects->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($filterCampus))]);
                }
                $assignedSubjects = $assignedSubjects->get();
                
                // Get sections from teacher's assigned sections for this class
                $assignedSections = Section::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($staff->name ?? ''))])
                    ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($filterClass))]);
                if ($filterCampus) {
                    $assignedSections->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($filterCampus))]);
                }
                $assignedSections = $assignedSections->get();
                
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
                $sectionsQuery = Section::whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($filterClass))]);
                if ($filterCampus) {
                    $sectionsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($filterCampus))]);
                }
                $sections = $sectionsQuery
                    ->whereNotNull('name')
                    ->distinct()
                    ->pluck('name')
                    ->sort()
                    ->values();
                
                if ($sections->isEmpty()) {
                    $sectionsFromStudents = Student::whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($filterClass))]);
                    if ($filterCampus) {
                        $sectionsFromStudents->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($filterCampus))]);
                    }
                    $sectionsFromStudents = $sectionsFromStudents
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

            if ($filterCampus) {
                $studentsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($filterCampus))]);
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
            'campuses', 'classes', 'sections', 'types', 'statusOptions',
            'filterCampus', 'filterType', 'filterClass', 'filterSection', 'filterDate',
            'students', 'attendanceData'
        ));
    }

    /**
     * Print present students for today.
     */
    public function printPresentToday(Request $request): View
    {
        $today = Carbon::today();

        $presentAttendances = StudentAttendance::with('student')
            ->whereDate('attendance_date', $today)
            ->whereRaw('LOWER(status) = ?', ['present'])
            ->orderBy('campus')
            ->orderBy('class')
            ->orderBy('section')
            ->orderBy('student_id')
            ->get();

        return view('attendance.present-today-print', [
            'presentAttendances' => $presentAttendances,
            'printedAt' => Carbon::now()->format('d-m-Y H:i'),
            'dateLabel' => $today->format('d M Y'),
        ]);
    }

    /**
     * Get sections by class name (AJAX).
     */
    public function getSectionsByClass(Request $request)
    {
        $className = $request->get('class');
        $campus = $request->get('campus');
        
        if (!$className) {
            return response()->json(['sections' => []]);
        }

        $staff = Auth::guard('staff')->user();
        $sections = collect();
        
        // Filter by teacher's assigned subjects and sections if teacher
        if ($staff && $staff->isTeacher()) {
            // Get sections from teacher's assigned subjects for this class
            $assignedSubjects = Subject::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($staff->name ?? ''))])
                ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($className))]);
            if ($campus) {
                $assignedSubjects->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
            }
            $assignedSubjects = $assignedSubjects->get();
            
            // Get sections from teacher's assigned sections for this class
            $assignedSections = Section::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($staff->name ?? ''))])
                ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($className))]);
            if ($campus) {
                $assignedSections->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
            }
            $assignedSections = $assignedSections->get();
            
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
            $sectionsQuery = Section::whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($className))]);
            if ($campus) {
                $sectionsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
            }
            $sections = $sectionsQuery
                ->whereNotNull('name')
                ->orderBy('name', 'asc')
                ->distinct()
                ->pluck('name')
                ->values();
            
            // If no sections found in Section model, get from students
            if ($sections->isEmpty()) {
                $sectionsQuery = Student::whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($className))]);
                if ($campus) {
                    $sectionsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
                }
                $sections = $sectionsQuery
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
     * Get classes by campus (AJAX).
     */
    public function getClassesByCampus(Request $request)
    {
        $campus = $request->get('campus');
        $staff = Auth::guard('staff')->user();

        if ($staff && $staff->isTeacher()) {
            $assignedSubjects = Subject::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($staff->name ?? ''))]);
            if ($campus) {
                $assignedSubjects->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
            }
            $assignedSubjects = $assignedSubjects->get();

            $assignedSections = Section::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($staff->name ?? ''))]);
            if ($campus) {
                $assignedSections->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
            }
            $assignedSections = $assignedSections->get();

            $classes = $assignedSubjects->pluck('class')
                ->merge($assignedSections->pluck('class'))
                ->map(fn($class) => trim((string) $class))
                ->filter(fn($class) => $class !== '')
                ->unique()
                ->sort()
                ->values();
        } else {
            $classesQuery = ClassModel::whereNotNull('class_name');
            if ($campus) {
                $classesQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
            }
            $classes = $classesQuery
                ->orderBy('numeric_no', 'asc')
                ->orderBy('class_name', 'asc')
                ->distinct()
                ->pluck('class_name')
                ->map(fn($class) => trim((string) $class))
                ->filter(fn($class) => $class !== '')
                ->unique()
                ->values();

            if ($classes->isEmpty()) {
                $fallback = Student::whereNotNull('class');
                if ($campus) {
                    $fallback->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
                }
                $classes = $fallback->distinct()
                    ->pluck('class')
                    ->map(fn($class) => trim((string) $class))
                    ->filter(fn($class) => $class !== '')
                    ->unique()
                    ->sort()
                    ->values();
            }
        }

        return response()->json(['classes' => $classes]);
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

    /**
     * Scan barcode and mark attendance for today.
     */
    public function scanBarcode(Request $request)
    {
        $validated = $request->validate([
            'barcode' => ['required', 'string', 'max:255'],
        ]);

        $barcode = trim($validated['barcode']);
        $barcodeLower = strtolower($barcode);

        $studentQuery = Student::query()
            ->whereRaw('LOWER(TRIM(student_code)) = ?', [$barcodeLower])
            ->orWhereRaw('LOWER(TRIM(gr_number)) = ?', [$barcodeLower]);

        if (is_numeric($barcode)) {
            $studentQuery->orWhere('id', (int) $barcode);
        }

        $student = $studentQuery->first();

        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'Student not found for this barcode.',
            ], 404);
        }

        $today = Carbon::today()->format('Y-m-d');
        $attendance = StudentAttendance::where('student_id', $student->id)
            ->whereDate('attendance_date', $today)
            ->first();

        $alreadyMarked = $attendance !== null;
        $status = $alreadyMarked ? $attendance->status : 'Present';

        if (!$alreadyMarked) {
            StudentAttendance::create([
                'student_id' => $student->id,
                'attendance_date' => $today,
                'status' => 'Present',
                'campus' => $student->campus,
                'class' => $student->class,
                'section' => $student->section,
                'remarks' => 'Barcode scan',
            ]);
        }

        $duesFee = $this->calculateStudentDues($student);

        return response()->json([
            'success' => true,
            'message' => $alreadyMarked ? 'Good bye' : 'Attendance marked as Present.',
            'data' => [
                'student' => [
                    'id' => $student->id,
                    'name' => $student->student_name,
                    'roll' => $student->student_code ?: ($student->gr_number ?: $student->id),
                    'parent' => $student->father_name ?: 'N/A',
                    'class_section' => trim(($student->class ?? 'N/A') . ' / ' . ($student->section ?? 'N/A')),
                    'campus' => $student->campus ?? 'N/A',
                    'dues' => $duesFee,
                ],
                'attendance' => [
                    'date' => $today,
                    'status' => $status,
                    'already_marked' => $alreadyMarked,
                    'time' => Carbon::now()->format('h:i A'),
                ],
            ],
        ]);
    }

    /**
     * Calculate student dues for current year.
     */
    private function calculateStudentDues(Student $student): float
    {
        $currentYear = Carbon::now()->year;
        $totalFee = $student->monthly_fee ? (float) $student->monthly_fee * 12 : 0.0;

        if (!$student->student_code) {
            return 0.0;
        }

        $payments = StudentPayment::where('student_code', $student->student_code)
            ->whereYear('payment_date', $currentYear)
            ->get();

        $paidFee = (float) $payments->sum('payment_amount');
        $discount = (float) $payments->sum('discount');
        $lateFee = (float) $payments->sum('late_fee');

        return max($totalFee - $paidFee - $discount + $lateFee, 0.0);
    }

}

