<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Campus;
use App\Models\ClassModel;
use App\Models\Section;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

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
     * Display student list for staff/teachers.
     */
    public function studentList(Request $request): View
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
        
        return response()->json(['sections' => $sections]);
    }
}

