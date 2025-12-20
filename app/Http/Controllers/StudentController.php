<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Campus;
use App\Models\ClassModel;
use App\Models\Section;
use App\Models\Subject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;

class StudentController extends Controller
{
    /**
     * Display a listing of students.
     */
    public function index(Request $request): View
    {
        $query = Student::query();
        
        // Filter by Campus
        if ($request->filled('filter_campus')) {
            $query->where('campus', $request->filter_campus);
        }
        
        // Filter by Class
        if ($request->filled('filter_class')) {
            $query->where('class', $request->filter_class);
        }
        
        // Filter by Section
        if ($request->filled('filter_section')) {
            $query->where('section', $request->filter_section);
        }
        
        // Filter by Type (Gender only)
        if ($request->filled('filter_type')) {
            $filterType = $request->filter_type;
            if (in_array($filterType, ['male', 'female', 'other'])) {
                $query->where('gender', $filterType);
            }
        }
        
        // Search functionality - case insensitive and trim whitespace
        if ($request->filled('search')) {
            $search = trim($request->search);
            if (!empty($search)) {
                $searchLower = strtolower($search);
                $query->where(function($q) use ($search, $searchLower) {
                    $q->whereRaw('LOWER(student_name) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(father_name) LIKE ?', ["%{$searchLower}%"])
                      ->orWhere('father_phone', 'like', "%{$search}%")
                      ->orWhere('whatsapp_number', 'like', "%{$search}%")
                      ->orWhere('student_code', 'like', "%{$search}%")
                      ->orWhere('gr_number', 'like', "%{$search}%")
                      ->orWhereRaw('LOWER(class) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(section) LIKE ?', ["%{$searchLower}%"]);
                });
            }
        }
        
        $perPage = $request->get('per_page', 10);
        // Validate per_page to prevent invalid values
        $perPage = in_array($perPage, [10, 25, 50, 100]) ? $perPage : 10;
        
        $students = $query->latest('admission_date')->paginate($perPage)->withQueryString();
        
        // Get filter options
        $campuses = Campus::orderBy('campus_name', 'asc')->pluck('campus_name');
        if ($campuses->isEmpty()) {
            $campusesFromStudents = Student::whereNotNull('campus')->distinct()->pluck('campus')->sort();
            $campuses = $campusesFromStudents;
        }
        
        $classes = ClassModel::whereNotNull('class_name')->distinct()->pluck('class_name')->sort()->values();
        if ($classes->isEmpty()) {
            $classesFromStudents = Student::whereNotNull('class')->distinct()->pluck('class')->sort();
            $classes = $classesFromStudents;
        }
        
        $sections = collect();
        if ($request->filled('filter_class')) {
            $sections = Section::where('class', $request->filter_class)
                ->whereNotNull('name')
                ->distinct()
                ->pluck('name')
                ->sort()
                ->values();
            if ($sections->isEmpty()) {
                $sectionsFromStudents = Student::where('class', $request->filter_class)
                    ->whereNotNull('section')
                    ->distinct()
                    ->pluck('section')
                    ->sort();
                $sections = $sectionsFromStudents;
            }
        }
        
        $types = collect(['male', 'female', 'other']);
        
        return view('student.information', compact('students', 'campuses', 'classes', 'sections', 'types'));
    }

    /**
     * Export students to Excel or PDF
     */
    public function export(Request $request, string $format)
    {
        $query = Student::query();
        
        // Apply same filters as index method
        if ($request->filled('filter_campus')) {
            $query->where('campus', $request->filter_campus);
        }
        
        if ($request->filled('filter_class')) {
            $query->where('class', $request->filter_class);
        }
        
        if ($request->filled('filter_section')) {
            $query->where('section', $request->filter_section);
        }
        
        if ($request->filled('filter_type')) {
            $filterType = $request->filter_type;
            if (in_array($filterType, ['male', 'female', 'other'])) {
                $query->where('gender', $filterType);
            }
        }
        
        // Apply search if present
        if ($request->filled('search')) {
            $search = trim($request->search);
            if (!empty($search)) {
                $searchLower = strtolower($search);
                $query->where(function($q) use ($search, $searchLower) {
                    $q->whereRaw('LOWER(student_name) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(father_name) LIKE ?', ["%{$searchLower}%"])
                      ->orWhere('father_phone', 'like', "%{$search}%")
                      ->orWhere('whatsapp_number', 'like', "%{$search}%")
                      ->orWhere('student_code', 'like', "%{$search}%")
                      ->orWhere('gr_number', 'like', "%{$search}%")
                      ->orWhereRaw('LOWER(class) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(section) LIKE ?', ["%{$searchLower}%"]);
                });
            }
        }
        
        $students = $query->latest('admission_date')->get();
        
        switch ($format) {
            case 'excel':
                return $this->exportExcel($students);
            case 'pdf':
                return $this->exportPDF($students);
            default:
                return redirect()->route('student.information')
                    ->with('error', 'Invalid export format!');
        }
    }

    /**
     * Export to Excel (CSV format)
     */
    private function exportExcel($students)
    {
        $filename = 'students_' . date('Y-m-d_His') . '.csv';
        
        $headers = [
            'Content-Type' => 'application/vnd.ms-excel',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($students) {
            $file = fopen('php://output', 'w');
            
            // Add BOM for UTF-8
            fputs($file, "\xEF\xBB\xBF");
            
            // Headers
            fputcsv($file, ['#', 'Student Name', 'Student Code', 'Father Name', 'Phone', 'Class', 'Section', 'Gender', 'Date of Birth', 'Admission Date', 'Campus']);
            
            // Data
            foreach ($students as $index => $student) {
                fputcsv($file, [
                    $index + 1,
                    $student->student_name . ($student->surname_caste ? ' (' . $student->surname_caste . ')' : ''),
                    $student->student_code ?? 'N/A',
                    $student->father_name ?? 'N/A',
                    $student->father_phone ?? $student->whatsapp_number ?? 'N/A',
                    $student->class ?? 'N/A',
                    $student->section ?? 'N/A',
                    ucfirst($student->gender ?? 'N/A'),
                    $student->date_of_birth ? $student->date_of_birth->format('Y-m-d') : 'N/A',
                    $student->admission_date ? $student->admission_date->format('Y-m-d') : 'N/A',
                    $student->campus ?? 'N/A',
                ]);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export to PDF
     */
    private function exportPDF($students)
    {
        $html = view('student.information-pdf', compact('students'))->render();
        
        return response($html)
            ->header('Content-Type', 'text/html');
    }

    /**
     * Delete all students based on filters
     */
    public function deleteAll(Request $request): RedirectResponse
    {
        $query = Student::query();
        
        // Apply same filters as index method
        if ($request->filled('filter_campus')) {
            $query->where('campus', $request->filter_campus);
        }
        
        if ($request->filled('filter_class')) {
            $query->where('class', $request->filter_class);
        }
        
        if ($request->filled('filter_section')) {
            $query->where('section', $request->filter_section);
        }
        
        if ($request->filled('filter_type')) {
            $filterType = $request->filter_type;
            if (in_array($filterType, ['male', 'female', 'other'])) {
                $query->where('gender', $filterType);
            }
        }
        
        // Apply search if present
        if ($request->filled('search')) {
            $search = trim($request->search);
            if (!empty($search)) {
                $searchLower = strtolower($search);
                $query->where(function($q) use ($search, $searchLower) {
                    $q->whereRaw('LOWER(student_name) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(father_name) LIKE ?', ["%{$searchLower}%"])
                      ->orWhere('father_phone', 'like', "%{$search}%")
                      ->orWhere('whatsapp_number', 'like', "%{$search}%")
                      ->orWhere('student_code', 'like', "%{$search}%")
                      ->orWhere('gr_number', 'like', "%{$search}%")
                      ->orWhereRaw('LOWER(class) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(section) LIKE ?', ["%{$searchLower}%"]);
                });
            }
        }
        
        $count = $query->count();
        
        if ($count > 0) {
            $query->delete();
            return redirect()->route('student.information')
                ->with('success', "Successfully deleted {$count} " . Str::plural('student', $count) . ".");
        }
        
        return redirect()->route('student.information')
            ->with('info', 'No students found to delete.');
    }

    /**
     * Show student details
     */
    public function show(Student $student): View
    {
        return view('student.view', compact('student'));
    }

    /**
     * Delete a student
     */
    public function destroy(Student $student): JsonResponse
    {
        try {
            $studentCode = $student->student_code;
            $studentName = $student->student_name;
            
            $student->delete();
            
            return response()->json([
                'success' => true,
                'message' => "Student {$studentName} ({$studentCode}) deleted successfully."
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while deleting the student: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display student list for staff/teachers.
     */
    public function studentList(Request $request): View
    {
        $query = Student::query();
        
        // Get logged-in staff/teacher
        $staff = Auth::guard('staff')->user();
        
        // Ensure we have staff user
        if (!$staff) {
            abort(403, 'Unauthorized access');
        }
        
        // Check if user is a teacher (case-insensitive check)
        // IMPORTANT: Designation must be exactly "teacher" (case-insensitive) for filtering to work
        $designation = strtolower(trim($staff->designation ?? ''));
        $isTeacher = ($designation === 'teacher');
        
        // Ensure isTeacher is boolean
        $isTeacher = (bool) $isTeacher;
        
        // Debug logging (temporary - remove after fixing)
        \Log::info('Student List - Teacher Check Debug', [
            'staff_id' => $staff->id ?? null,
            'staff_name' => $staff->name ?? null,
            'staff_email' => $staff->email ?? null,
            'designation_raw' => $staff->designation ?? null,
            'designation_lower' => $designation,
            'isTeacher' => $isTeacher,
        ]);
        
        // If teacher, filter by assigned classes and sections only
        if ($isTeacher) {
            // Get teacher's assigned subjects with class and section
            $assignedSubjects = Subject::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($staff->name ?? ''))])
                ->whereNotNull('class')
                ->get();
            
            // Get teacher's assigned sections with class
            $assignedSections = Section::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($staff->name ?? ''))])
                ->whereNotNull('class')
                ->get();
            
            // Build class-section combinations from subjects
            $classSectionCombinations = collect();
            foreach ($assignedSubjects as $subject) {
                if (!empty($subject->class) && !empty($subject->section)) {
                    $classSectionCombinations->push([
                        'class' => trim($subject->class),
                        'section' => trim($subject->section)
                    ]);
                }
            }
            
            // Build class-section combinations from sections
            foreach ($assignedSections as $section) {
                if (!empty($section->class) && !empty($section->name)) {
                    $classSectionCombinations->push([
                        'class' => trim($section->class),
                        'section' => trim($section->name)
                    ]);
                }
            }
            
            // Remove duplicates (case-insensitive)
            $classSectionCombinations = $classSectionCombinations->unique(function ($item) {
                return strtolower($item['class']) . '|' . strtolower($item['section']);
            })->values();
            
            // Filter students by exact class-section combinations
            if ($classSectionCombinations->isNotEmpty()) {
                $query->where(function($q) use ($classSectionCombinations) {
                    foreach ($classSectionCombinations as $combination) {
                        $q->orWhere(function($subQuery) use ($combination) {
                            $subQuery->whereRaw('LOWER(TRIM(class)) = ?', [strtolower($combination['class'])])
                                     ->whereRaw('LOWER(TRIM(section)) = ?', [strtolower($combination['section'])]);
                        });
                    }
                });
            } else {
                // If teacher has no assigned classes/sections, return empty
                $query->whereRaw('1 = 0');
            }
        }
        
        // Filter by Campus
        if ($request->filled('filter_campus')) {
            $query->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($request->filter_campus))]);
        }
        
        // Filter by Class (only if teacher is assigned to that class)
        if ($request->filled('filter_class')) {
            if ($isTeacher) {
                // Verify that the teacher is assigned to this class
                $filterClass = trim($request->filter_class);
                $assignedClasses = Subject::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($staff->name ?? ''))])
                    ->whereNotNull('class')
                    ->pluck('class')
                    ->merge(Section::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($staff->name ?? ''))])
                        ->whereNotNull('class')
                        ->pluck('class'))
                    ->map(fn($class) => strtolower(trim($class)))
                    ->unique();
                
                if ($assignedClasses->contains(strtolower($filterClass))) {
                    $query->whereRaw('LOWER(TRIM(class)) = ?', [strtolower($filterClass)]);
                }
            } else {
                $query->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($request->filter_class))]);
            }
        }
        
        // Filter by Section (only if teacher is assigned to that section)
        if ($request->filled('filter_section')) {
            if ($isTeacher) {
                // Verify that the teacher is assigned to this section
                $filterSection = trim($request->filter_section);
                $filterClass = $request->filled('filter_class') ? trim($request->filter_class) : null;
                
                $assignedSections = collect();
                if ($filterClass) {
                    // Get sections for the selected class
                    $assignedSections = Subject::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($staff->name ?? ''))])
                        ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower($filterClass)])
                        ->pluck('section')
                        ->merge(Section::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($staff->name ?? ''))])
                            ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower($filterClass)])
                            ->pluck('name'))
                        ->map(fn($section) => strtolower(trim($section)))
                        ->filter(fn($section) => !empty($section))
                        ->unique();
                } else {
                    // Get all assigned sections
                    $assignedSections = Subject::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($staff->name ?? ''))])
                        ->whereNotNull('section')
                        ->pluck('section')
                        ->merge(Section::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($staff->name ?? ''))])
                            ->whereNotNull('name')
                            ->pluck('name'))
                        ->map(fn($section) => strtolower(trim($section)))
                        ->filter(fn($section) => !empty($section))
                        ->unique();
                }
                
                if ($assignedSections->contains(strtolower($filterSection))) {
                    $query->whereRaw('LOWER(TRIM(section)) = ?', [strtolower($filterSection)]);
                }
            } else {
                $query->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($request->filter_section))]);
            }
        }
        
        // Filter by Type (Active/Deactive Student)
        if ($request->filled('filter_type')) {
            $filterType = $request->filter_type;
            if ($filterType === 'active') {
                // Active students: those with admission_date (all students should have this, but filter for safety)
                $query->whereNotNull('admission_date');
            } elseif ($filterType === 'deactive') {
                // Deactive students: those without admission_date
                // Note: Since admission_date is required, this might return empty, but kept for future use
                $query->whereNull('admission_date');
            }
        }
        
        // Search functionality
        if ($request->filled('search')) {
            $search = trim($request->search);
            if (!empty($search)) {
                $searchLower = strtolower($search);
                $query->where(function($q) use ($search, $searchLower) {
                    $q->whereRaw('LOWER(student_name) LIKE ?', ["%{$searchLower}%"])
                      ->orWhere('student_code', 'like', "%{$search}%")
                      ->orWhereRaw('LOWER(class) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(section) LIKE ?', ["%{$searchLower}%"]);
                });
            }
        }
        
        $perPage = $request->get('per_page', 10);
        $perPage = in_array($perPage, [10, 25, 50, 100]) ? $perPage : 10;
        
        $students = $query->latest('admission_date')->paginate($perPage)->withQueryString();
        
        // Get filter options
        $campuses = Campus::orderBy('campus_name', 'asc')->get();
        if ($campuses->isEmpty()) {
            $campusesFromStudents = Student::whereNotNull('campus')->distinct()->pluck('campus')->sort();
            $campuses = $campusesFromStudents->map(function($campus) {
                return (object)['campus_name' => $campus];
            });
        }
        
        // Get classes - filter by teacher's assigned classes if teacher
        $classes = collect();
        
        // IMPORTANT: Only show assigned classes for teachers
        // Check if user is a teacher
        if ($isTeacher) {
            // Teacher: Only show assigned classes from Subject and Section tables
            $teacherName = strtolower(trim($staff->name ?? ''));
            
            \Log::info('Student List - Teacher Branch Executed', [
                'teacher_name' => $teacherName,
                'staff_name' => $staff->name ?? null,
            ]);
            
            if (empty($teacherName)) {
                // If teacher name is empty, show no classes
                $classes = collect();
                \Log::info('Student List - Teacher name is empty, showing no classes');
            } else {
                // Get classes from teacher's assigned subjects
                $assignedSubjects = Subject::whereRaw('LOWER(TRIM(teacher)) = ?', [$teacherName])
                    ->whereNotNull('class')
                    ->get();
                
                // Get classes from teacher's assigned sections
                $assignedSections = Section::whereRaw('LOWER(TRIM(teacher)) = ?', [$teacherName])
                    ->whereNotNull('class')
                    ->get();
                
                \Log::info('Student List - Teacher Assignments Check', [
                    'assigned_subjects_count' => $assignedSubjects->count(),
                    'assigned_sections_count' => $assignedSections->count(),
                ]);
                
                // Merge classes from both sources and normalize
                $allClasses = $assignedSubjects->pluck('class')
                    ->merge($assignedSections->pluck('class'))
                    ->map(function($class) {
                        return trim($class);
                    })
                    ->filter(function($class) {
                        return !empty($class);
                    });
                
                // Case-insensitive unique (keep original case of first occurrence)
                $classesMap = [];
                foreach ($allClasses as $class) {
                    $key = strtolower($class);
                    if (!isset($classesMap[$key])) {
                        $classesMap[$key] = $class;
                    }
                }
                
                // Only set classes if teacher has assignments, otherwise keep empty collection
                if (!empty($classesMap)) {
                    $classes = collect(array_values($classesMap))->sort()->values();
                    \Log::info('Student List - Teacher has assignments', ['classes' => $classes->toArray()]);
                } else {
                    \Log::info('Student List - Teacher has NO assignments, showing empty dropdown');
                }
                // If teacher has no assignments, $classes remains empty collection
            }
        } else {
            // For non-teachers (admin, staff, etc.), get all classes
            // This block should NOT execute for teachers
            \Log::info('Student List - Non-teacher branch executed - showing all classes');
            $classes = ClassModel::whereNotNull('class_name')->distinct()->pluck('class_name')->sort()->values();
            if ($classes->isEmpty()) {
                $classesFromStudents = Student::whereNotNull('class')->distinct()->pluck('class')->sort();
                $classes = $classesFromStudents;
            }
        }
        
        \Log::info('Student List - Final classes count', ['count' => $classes->count(), 'classes' => $classes->toArray()]);
        
        // Get sections - filter by teacher's assigned subjects if teacher
        $sections = collect();
        if ($request->filled('filter_class')) {
            if ($isTeacher) {
                // Get sections from teacher's assigned subjects for this class
                $assignedSubjects = Subject::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($staff->name ?? ''))])
                    ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($request->filter_class))])
                    ->get();
                
                // Get sections from teacher's assigned sections for this class
                $assignedSections = Section::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($staff->name ?? ''))])
                    ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($request->filter_class))])
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
                $sections = Section::where('class', $request->filter_class)
                    ->whereNotNull('name')
                    ->distinct()
                    ->pluck('name')
                    ->sort()
                    ->values();
                if ($sections->isEmpty()) {
                    $sectionsFromStudents = Student::where('class', $request->filter_class)
                        ->whereNotNull('section')
                        ->distinct()
                        ->pluck('section')
                        ->sort();
                    $sections = $sectionsFromStudents;
                }
            }
        }
        
        $types = collect(['active' => 'Active Student', 'deactive' => 'Deactive Student']);
        
        return view('student.list', compact('students', 'campuses', 'classes', 'sections', 'types'));
    }

    /**
     * Get sections for student list (AJAX).
     */
    public function getSectionsForStudentList(Request $request)
    {
        $class = $request->get('class');
        
        if (!$class) {
            return response()->json(['sections' => []]);
        }
        
        $staff = Auth::guard('staff')->user();
        
        if (!$staff) {
            return response()->json(['sections' => []], 403);
        }
        
        $sections = collect();
        $isTeacher = strtolower(trim($staff->designation ?? '')) === 'teacher';
        
        // Filter by teacher's assigned subjects and sections if teacher
        if ($isTeacher) {
            // First verify teacher is assigned to this class
            $assignedClasses = Subject::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($staff->name ?? ''))])
                ->whereNotNull('class')
                ->pluck('class')
                ->merge(Section::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($staff->name ?? ''))])
                    ->whereNotNull('class')
                    ->pluck('class'))
                ->map(fn($c) => strtolower(trim($c)))
                ->unique();
            
            // Only proceed if teacher is assigned to this class
            if ($assignedClasses->contains(strtolower(trim($class)))) {
                // Get sections from teacher's assigned subjects for this class
                $assignedSubjects = Subject::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($staff->name ?? ''))])
                    ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))])
                    ->get();
                
                // Get sections from teacher's assigned sections for this class
                $assignedSections = Section::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($staff->name ?? ''))])
                    ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))])
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
            }
            // If teacher is not assigned to this class, $sections remains empty
        } else {
            // For non-teachers, get all sections
            $sections = Section::where('class', $class)
                ->whereNotNull('name')
                ->distinct()
                ->pluck('name')
                ->sort()
                ->values();
                
            if ($sections->isEmpty()) {
                $sections = Student::where('class', $class)
                    ->whereNotNull('section')
                    ->distinct()
                    ->pluck('section')
                    ->sort()
                    ->values();
            }
        }
        
        return response()->json(['sections' => $sections]);
    }
}

