<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\ClassModel;
use App\Models\Section;
use App\Models\Subject;
use App\Models\Test;
use App\Models\Student;
use App\Models\StudentMark;
use App\Models\Campus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class ExamController extends Controller
{
    /**
     * Display a listing of exams.
     */
    public function index(Request $request): View
    {
        $query = Exam::query();
        
        // Search functionality
        if ($request->filled('search')) {
            $search = trim($request->search);
            if (!empty($search)) {
                $searchLower = strtolower($search);
                $query->where(function($q) use ($search, $searchLower) {
                    $q->whereRaw('LOWER(exam_name) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(campus) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(session) LIKE ?', ["%{$searchLower}%"]);
                });
            }
        }
        
        $perPage = $request->get('per_page', 10);
        $perPage = in_array($perPage, [10, 25, 50, 100]) ? $perPage : 10;
        
        $exams = $query->orderBy('exam_date', 'desc')->paginate($perPage)->withQueryString();

        // Get campuses for dropdown - First from Campus model, then fallback
        $campuses = Campus::orderBy('campus_name', 'asc')->get();
        if ($campuses->isEmpty()) {
            $campusesFromClasses = ClassModel::whereNotNull('campus')->distinct()->pluck('campus');
            $campusesFromSections = Section::whereNotNull('campus')->distinct()->pluck('campus');
            $allCampuses = $campusesFromClasses->merge($campusesFromSections)->unique()->sort();
            $campuses = $allCampuses->map(function($campus) {
                return (object)['campus_name' => $campus];
            });
        }

        // Get sessions
        $sessions = Exam::whereNotNull('session')->distinct()->pluck('session')->sort()->values();
        
        if ($sessions->isEmpty()) {
            $sessions = collect(['2024-2025', '2025-2026', '2026-2027']);
        }
        
        return view('exam.list', compact('exams', 'campuses', 'sessions'));
    }

    /**
     * Store a newly created exam.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'campus' => ['required', 'string', 'max:255'],
            'exam_name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'exam_date' => ['required', 'date'],
            'session' => ['required', 'string', 'max:255'],
        ]);

        Exam::create($validated);

        return redirect()
            ->route('exam.list')
            ->with('success', 'Exam created successfully!');
    }

    /**
     * Update the specified exam.
     */
    public function update(Request $request, Exam $exam): RedirectResponse
    {
        $validated = $request->validate([
            'campus' => ['required', 'string', 'max:255'],
            'exam_name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'exam_date' => ['required', 'date'],
            'session' => ['required', 'string', 'max:255'],
        ]);

        $exam->update($validated);

        return redirect()
            ->route('exam.list')
            ->with('success', 'Exam updated successfully!');
    }

    /**
     * Remove the specified exam.
     */
    public function destroy(Exam $exam): RedirectResponse
    {
        $exam->delete();

        return redirect()
            ->route('exam.list')
            ->with('success', 'Exam deleted successfully!');
    }

    /**
     * Export exams to Excel, CSV, or PDF
     */
    public function export(Request $request, string $format)
    {
        $query = Exam::query();
        
        // Apply search filter if present
        if ($request->has('search') && $request->search) {
            $search = trim($request->search);
            if (!empty($search)) {
                $searchLower = strtolower($search);
                $query->where(function($q) use ($search, $searchLower) {
                    $q->whereRaw('LOWER(exam_name) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(campus) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(session) LIKE ?', ["%{$searchLower}%"]);
                });
            }
        }
        
        $exams = $query->orderBy('exam_date', 'desc')->get();
        
        switch ($format) {
            case 'excel':
                return $this->exportExcel($exams);
            case 'pdf':
                return $this->exportPDF($exams);
            default:
                return redirect()->route('exam.list')
                    ->with('error', 'Invalid export format!');
        }
    }

    /**
     * Export to Excel (CSV format for Excel compatibility)
     */
    private function exportExcel($exams)
    {
        $filename = 'exams_' . date('Y-m-d_His') . '.csv';
        
        $headers = [
            'Content-Type' => 'application/vnd.ms-excel',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($exams) {
            $file = fopen('php://output', 'w');
            
            // Add BOM for UTF-8
            fputs($file, "\xEF\xBB\xBF");
            
            // Headers
            fputcsv($file, ['#', 'Campus', 'Exam Name', 'Description', 'Exam Date', 'Session']);
            
            // Data
            foreach ($exams as $index => $exam) {
                fputcsv($file, [
                    $index + 1,
                    $exam->campus,
                    $exam->exam_name,
                    $exam->description ?? 'N/A',
                    $exam->exam_date->format('d M Y'),
                    $exam->session,
                ]);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export to PDF
     */
    private function exportPDF($exams)
    {
        $html = view('exam.export-pdf', compact('exams'))->render();
        
        // Simple PDF generation (you can use DomPDF or similar package)
        return response($html)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="exams_' . date('Y-m-d_His') . '.pdf"');
    }

    /**
     * Display the exam grades for particular exam page.
     */
    public function gradesParticular(Request $request): View
    {
        // Get campuses for dropdown
        $campusesFromClasses = ClassModel::whereNotNull('campus')->distinct()->pluck('campus');
        $campusesFromSections = Section::whereNotNull('campus')->distinct()->pluck('campus');
        $campuses = $campusesFromClasses->merge($campusesFromSections)->unique()->sort()->values();
        
        if ($campuses->isEmpty()) {
            $campuses = collect(['Main Campus', 'Branch Campus 1', 'Branch Campus 2']);
        }

        // Get sessions
        $sessions = Exam::whereNotNull('session')->distinct()->pluck('session')->sort()->values();
        
        if ($sessions->isEmpty()) {
            $sessions = collect(['2024-2025', '2025-2026', '2026-2027']);
        }

        // Get exams (filtered by campus and session if provided)
        $filterCampus = $request->get('filter_campus');
        $filterSession = $request->get('filter_session');
        
        $examsQuery = Exam::query();
        if ($filterCampus) {
            $examsQuery->where('campus', $filterCampus);
        }
        if ($filterSession) {
            $examsQuery->where('session', $filterSession);
        }
        $exams = $examsQuery->whereNotNull('exam_name')->distinct()->pluck('exam_name')->sort()->values();
        
        if ($exams->isEmpty()) {
            $exams = collect([]);
        }

        return view('exam.grades.particular', compact('campuses', 'sessions', 'exams', 'filterCampus', 'filterSession'));
    }

    /**
     * Get exams based on campus and session (AJAX).
     */
    public function getExams(Request $request): JsonResponse
    {
        $campus = $request->get('campus');
        $session = $request->get('session');
        
        $examsQuery = Exam::query();
        if ($campus) {
            $examsQuery->where('campus', $campus);
        }
        if ($session) {
            $examsQuery->where('session', $session);
        }
        
        $exams = $examsQuery->whereNotNull('exam_name')->distinct()->pluck('exam_name')->sort()->values();
        
        return response()->json($exams);
    }

    /**
     * Display the exam marks entry page.
     */
    public function marksEntry(Request $request): View
    {
        // Get filter values
        $filterExam = $request->get('filter_exam');
        $filterClass = $request->get('filter_class');
        $filterSection = $request->get('filter_section');
        $filterSubject = $request->get('filter_subject');

        // Get exams from Exam List (all distinct exam names, ordered alphabetically)
        $exams = Exam::whereNotNull('exam_name')
            ->orderBy('exam_name', 'asc')
            ->get()
            ->pluck('exam_name')
            ->unique()
            ->values();

        // Get classes
        $classes = ClassModel::whereNotNull('class_name')->distinct()->pluck('class_name')->sort()->values();
        
        if ($classes->isEmpty()) {
            $classes = collect(['Nursery', 'KG', '1st', '2nd', '3rd', '4th', '5th', '6th', '7th', '8th', '9th', '10th', '11th', '12th']);
        }

        // Get sections (will be filtered dynamically based on class selection)
        $sections = collect(); // Initialized as empty, will be filled via AJAX
        if ($filterClass) {
            $sections = Section::whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($filterClass))])
                ->whereNotNull('name')
                ->distinct()
                ->pluck('name')
                ->sort()
                ->values();
            if ($sections->isEmpty()) {
                $sections = Subject::whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($filterClass))])
                    ->whereNotNull('section')
                    ->distinct()
                    ->pluck('section')
                    ->sort()
                    ->values();
            }
        }

        // Get subjects (filtered by class and section if provided)
        $subjectsQuery = Subject::query();
        if ($filterClass) {
            $subjectsQuery->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($filterClass))]);
        }
        if ($filterSection) {
            $subjectsQuery->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($filterSection))]);
        }
        $subjects = $subjectsQuery->whereNotNull('subject_name')->distinct()->pluck('subject_name')->sort()->values();
        
        // If no subjects found and no filters applied, show all subjects
        if ($subjects->isEmpty() && !$filterClass && !$filterSection) {
            $subjects = Subject::whereNotNull('subject_name')->distinct()->pluck('subject_name')->sort()->values();
        }

        // Query students based on filters
        $students = collect();
        if ($filterClass || $filterSection) {
            $studentsQuery = Student::query();
            
            if ($filterClass) {
                $studentsQuery->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($filterClass))]);
            }
            if ($filterSection) {
                $studentsQuery->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($filterSection))]);
            }
            
            $students = $studentsQuery->orderBy('student_name')->get();
            
            // Load existing marks for each student if exam and subject are selected
            if ($filterExam && $filterSubject && $students->count() > 0) {
                $marks = StudentMark::where('test_name', $filterExam) // Using test_name field for exam_name
                    ->whereRaw('LOWER(TRIM(subject)) = ?', [strtolower(trim($filterSubject))])
                    ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($filterClass))])
                    ->when($filterSection, function($query) use ($filterSection) {
                        return $query->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($filterSection))]);
                    })
                    ->get()
                    ->keyBy('student_id');
                
                // Attach marks to students
                $students = $students->map(function($student) use ($marks) {
                    $student->mark = $marks->get($student->id);
                    return $student;
                });
            }
        }

        return view('exam.marks-entry', compact(
            'exams',
            'classes',
            'sections',
            'subjects',
            'students',
            'filterExam',
            'filterClass',
            'filterSection',
            'filterSubject'
        ));
    }

    /**
     * Get sections based on class (AJAX) for marks entry.
     */
    public function getSectionsForMarksEntry(Request $request): JsonResponse
    {
        $class = $request->get('class');
        if (!$class) {
            return response()->json(['sections' => []]);
        }
        
        $sections = Section::whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))])
            ->whereNotNull('name')
            ->distinct()
            ->pluck('name')
            ->sort()
            ->values();
        
        if ($sections->isEmpty()) {
            $sections = Subject::whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))])
                ->whereNotNull('section')
                ->distinct()
                ->pluck('section')
                ->sort()
                ->values();
        }
        
        return response()->json(['sections' => $sections]);
    }

    /**
     * Get subjects based on class and section (AJAX) for marks entry.
     */
    public function getSubjectsForMarksEntry(Request $request): JsonResponse
    {
        $class = $request->get('class');
        $section = $request->get('section');
        
        $subjectsQuery = Subject::query();
        
        if ($class) {
            $subjectsQuery->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))]);
        }
        if ($section) {
            $subjectsQuery->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($section))]);
        }
        
        $subjects = $subjectsQuery->whereNotNull('subject_name')
            ->distinct()
            ->pluck('subject_name')
            ->sort()
            ->values();
        
        return response()->json(['subjects' => $subjects]);
    }

    /**
     * Save exam marks for students.
     */
    public function saveExamMarks(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'exam_name' => ['required', 'string'],
            'class' => ['required', 'string'],
            'section' => ['nullable', 'string'],
            'subject' => ['required', 'string'],
            'marks' => ['required', 'array'],
            'marks.*.obtained' => ['nullable', 'numeric', 'min:0'],
            'marks.*.total' => ['nullable', 'numeric', 'min:0'],
            'marks.*.passing' => ['nullable', 'numeric', 'min:0'],
        ]);

        // Get campus from first student or exam
        $firstStudentId = array_key_first($validated['marks']);
        $student = Student::find($firstStudentId);
        $campus = $student ? $student->campus : '';

        // Save or update marks for each student
        foreach ($validated['marks'] as $studentId => $markData) {
            if ($studentId) {
                $student = Student::find($studentId);
                $campus = $student ? $student->campus : $campus;
                
                StudentMark::updateOrCreate(
                    [
                        'student_id' => $studentId,
                        'test_name' => $validated['exam_name'], // Using test_name field for exam_name
                        'campus' => $campus,
                        'class' => $validated['class'],
                        'section' => $validated['section'] ?? null,
                        'subject' => $validated['subject'],
                    ],
                    [
                        'marks_obtained' => $markData['obtained'] ?? null,
                        'total_marks' => $markData['total'] ?? null,
                        'passing_marks' => $markData['passing'] ?? null,
                    ]
                );
            }
        }
        
        return redirect()
            ->route('exam.marks-entry', [
                'filter_exam' => $validated['exam_name'],
                'filter_class' => $validated['class'],
                'filter_section' => $validated['section'] ?? '',
                'filter_subject' => $validated['subject'],
            ])
            ->with('success', 'Exam marks saved successfully!');
    }

    /**
     * Display the teacher remarks for particular exam page.
     */
    public function teacherRemarksParticular(Request $request): View
    {
        // Get filter values
        $filterCampus = $request->get('filter_campus');
        $filterExam = $request->get('filter_exam');
        $filterClass = $request->get('filter_class');
        $filterSection = $request->get('filter_section');

        // Get campuses for dropdown
        $campusesFromClasses = ClassModel::whereNotNull('campus')->distinct()->pluck('campus');
        $campusesFromSections = Section::whereNotNull('campus')->distinct()->pluck('campus');
        $campuses = $campusesFromClasses->merge($campusesFromSections)->unique()->sort()->values();
        
        if ($campuses->isEmpty()) {
            $campuses = collect(['Main Campus', 'Branch Campus 1', 'Branch Campus 2']);
        }

        // Get exams (filtered by campus if provided)
        $examsQuery = Exam::query();
        if ($filterCampus) {
            $examsQuery->where('campus', $filterCampus);
        }
        $exams = $examsQuery->whereNotNull('exam_name')->distinct()->pluck('exam_name')->sort()->values();
        
        if ($exams->isEmpty()) {
            $exams = collect([]);
        }

        // Get classes
        $classes = ClassModel::whereNotNull('class_name')->distinct()->pluck('class_name')->sort()->values();
        
        if ($classes->isEmpty()) {
            $classes = collect(['Nursery', 'KG', '1st', '2nd', '3rd', '4th', '5th', '6th', '7th', '8th', '9th', '10th', '11th', '12th']);
        }

        // Get sections (filtered by class if provided)
        $sectionsQuery = Section::query();
        if ($filterClass) {
            $sectionsQuery->where('class', $filterClass);
        }
        $sections = $sectionsQuery->whereNotNull('name')->distinct()->pluck('name')->sort()->values();
        
        if ($sections->isEmpty()) {
            $sections = collect(['A', 'B', 'C', 'D']);
        }

        return view('exam.teacher-remarks.particular', compact(
            'campuses',
            'exams',
            'classes',
            'sections',
            'filterCampus',
            'filterExam',
            'filterClass',
            'filterSection'
        ));
    }

    /**
     * Get exams based on campus (AJAX) for teacher remarks.
     */
    public function getExamsForTeacherRemarks(Request $request): JsonResponse
    {
        $campus = $request->get('campus');
        
        $examsQuery = Exam::query();
        if ($campus) {
            $examsQuery->where('campus', $campus);
        }
        
        $exams = $examsQuery->whereNotNull('exam_name')->distinct()->pluck('exam_name')->sort()->values();
        
        return response()->json($exams);
    }

    /**
     * Get sections based on class (AJAX) for teacher remarks.
     */
    public function getSectionsForTeacherRemarks(Request $request): JsonResponse
    {
        $class = $request->get('class');
        $sections = Section::when($class, fn($q) => $q->where('class', $class))
            ->whereNotNull('name')
            ->distinct()
            ->pluck('name')
            ->sort()
            ->values();
        
        return response()->json($sections->isEmpty() ? ['A', 'B', 'C', 'D'] : $sections);
    }

    /**
     * Display the teacher remarks for final result page.
     */
    public function teacherRemarksFinal(Request $request): View
    {
        // Get filter values
        $filterCampus = $request->get('filter_campus');
        $filterSession = $request->get('filter_session');
        $filterClass = $request->get('filter_class');
        $filterSection = $request->get('filter_section');

        // Get campuses for dropdown
        $campusesFromClasses = ClassModel::whereNotNull('campus')->distinct()->pluck('campus');
        $campusesFromSections = Section::whereNotNull('campus')->distinct()->pluck('campus');
        $campuses = $campusesFromClasses->merge($campusesFromSections)->unique()->sort()->values();
        
        if ($campuses->isEmpty()) {
            $campuses = collect(['Main Campus', 'Branch Campus 1', 'Branch Campus 2']);
        }

        // Get sessions
        $sessions = Exam::whereNotNull('session')->distinct()->pluck('session')->sort()->values();
        
        if ($sessions->isEmpty()) {
            $sessions = collect(['2024-2025', '2025-2026', '2026-2027']);
        }

        // Get classes
        $classes = ClassModel::whereNotNull('class_name')->distinct()->pluck('class_name')->sort()->values();
        
        if ($classes->isEmpty()) {
            $classes = collect(['Nursery', 'KG', '1st', '2nd', '3rd', '4th', '5th', '6th', '7th', '8th', '9th', '10th', '11th', '12th']);
        }

        // Get sections (filtered by class if provided)
        $sectionsQuery = Section::query();
        if ($filterClass) {
            $sectionsQuery->where('class', $filterClass);
        }
        $sections = $sectionsQuery->whereNotNull('name')->distinct()->pluck('name')->sort()->values();
        
        if ($sections->isEmpty()) {
            $sections = collect(['A', 'B', 'C', 'D']);
        }

        return view('exam.teacher-remarks.final', compact(
            'campuses',
            'sessions',
            'classes',
            'sections',
            'filterCampus',
            'filterSession',
            'filterClass',
            'filterSection'
        ));
    }

    /**
     * Display the add exam timetable page.
     */
    public function addTimetable(Request $request): View
    {
        // Get classes
        $classes = ClassModel::whereNotNull('class_name')->distinct()->pluck('class_name')->sort()->values();
        
        if ($classes->isEmpty()) {
            $classes = collect(['Nursery', 'KG', '1st', '2nd', '3rd', '4th', '5th', '6th', '7th', '8th', '9th', '10th', '11th', '12th']);
        }

        // Get sections (will be loaded via AJAX based on class)
        $sections = collect();

        // Get subjects (will be loaded via AJAX based on class/section)
        $subjects = collect();

        // Get exams
        $exams = Exam::whereNotNull('exam_name')->distinct()->pluck('exam_name')->sort()->values();
        
        if ($exams->isEmpty()) {
            $exams = collect();
        }

        return view('exam.timetable.add', compact(
            'classes',
            'sections',
            'subjects',
            'exams'
        ));
    }

    /**
     * Get sections based on class (AJAX) for exam timetable.
     */
    public function getSectionsForTimetable(Request $request): JsonResponse
    {
        $class = $request->get('class');
        $sections = Section::when($class, fn($q) => $q->where('class', $class))
            ->whereNotNull('name')
            ->distinct()
            ->pluck('name')
            ->sort()
            ->values();
        
        return response()->json($sections->isEmpty() ? ['A', 'B', 'C', 'D'] : $sections);
    }

    /**
     * Get subjects based on class and section (AJAX) for exam timetable.
     */
    public function getSubjectsForTimetable(Request $request): JsonResponse
    {
        $class = $request->get('class');
        $section = $request->get('section');
        
        $subjectsQuery = Subject::query();
        
        if ($class) {
            $subjectsQuery->where('class', $class);
        }
        
        if ($section) {
            $subjectsQuery->where('section', $section);
        }
        
        $subjects = $subjectsQuery->whereNotNull('subject_name')
            ->distinct()
            ->pluck('subject_name')
            ->sort()
            ->values();
        
        return response()->json($subjects);
    }

    /**
     * Display the manage exam timetable page.
     */
    public function manageTimetable(Request $request): View
    {
        // Get filter values
        $filterCampus = $request->get('filter_campus');
        $filterExam = $request->get('filter_exam');
        $filterClass = $request->get('filter_class');
        $filterSection = $request->get('filter_section');

        // Get campuses for dropdown
        $campusesFromClasses = ClassModel::whereNotNull('campus')->distinct()->pluck('campus');
        $campusesFromSections = Section::whereNotNull('campus')->distinct()->pluck('campus');
        $campuses = $campusesFromClasses->merge($campusesFromSections)->unique()->sort()->values();
        
        if ($campuses->isEmpty()) {
            $campuses = collect(['Main Campus', 'Branch Campus 1', 'Branch Campus 2']);
        }

        // Get exams (filtered by campus if provided)
        $examsQuery = Exam::query();
        if ($filterCampus) {
            $examsQuery->where('campus', $filterCampus);
        }
        $exams = $examsQuery->whereNotNull('exam_name')->distinct()->pluck('exam_name')->sort()->values();

        // Get classes
        $classes = ClassModel::whereNotNull('class_name')->distinct()->pluck('class_name')->sort()->values();
        
        if ($classes->isEmpty()) {
            $classes = collect(['Nursery', 'KG', '1st', '2nd', '3rd', '4th', '5th', '6th', '7th', '8th', '9th', '10th', '11th', '12th']);
        }

        // Get sections (filtered by class if provided)
        $sectionsQuery = Section::query();
        if ($filterClass) {
            $sectionsQuery->where('class', $filterClass);
        }
        $sections = $sectionsQuery->whereNotNull('name')->distinct()->pluck('name')->sort()->values();
        
        if ($sections->isEmpty()) {
            $sections = collect(['A', 'B', 'C', 'D']);
        }

        return view('exam.timetable.manage', compact(
            'campuses',
            'exams',
            'classes',
            'sections',
            'filterCampus',
            'filterExam',
            'filterClass',
            'filterSection'
        ));
    }

    /**
     * Get exams based on campus (AJAX) for manage timetable.
     */
    public function getExamsForManageTimetable(Request $request): JsonResponse
    {
        $campus = $request->get('campus');
        
        $examsQuery = Exam::query();
        if ($campus) {
            $examsQuery->where('campus', $campus);
        }
        
        $exams = $examsQuery->whereNotNull('exam_name')
            ->distinct()
            ->pluck('exam_name')
            ->sort()
            ->values();
        
        return response()->json($exams);
    }

    /**
     * Display the tabulation sheet for particular exam page.
     */
    public function tabulationSheetParticular(Request $request): View
    {
        // Get filter values
        $filterCampus = $request->get('filter_campus');
        $filterClass = $request->get('filter_class');
        $filterSection = $request->get('filter_section');
        $filterExam = $request->get('filter_exam');
        $filterType = $request->get('filter_type');

        // Get campuses for dropdown
        $campusesFromClasses = ClassModel::whereNotNull('campus')->distinct()->pluck('campus');
        $campusesFromSections = Section::whereNotNull('campus')->distinct()->pluck('campus');
        $campuses = $campusesFromClasses->merge($campusesFromSections)->unique()->sort()->values();
        
        if ($campuses->isEmpty()) {
            $campuses = collect(['Main Campus', 'Branch Campus 1', 'Branch Campus 2']);
        }

        // Get classes
        $classes = ClassModel::whereNotNull('class_name')->distinct()->pluck('class_name')->sort()->values();
        
        if ($classes->isEmpty()) {
            $classes = collect(['Nursery', 'KG', '1st', '2nd', '3rd', '4th', '5th', '6th', '7th', '8th', '9th', '10th', '11th', '12th']);
        }

        // Get sections (filtered by class if provided)
        $sectionsQuery = Section::query();
        if ($filterClass) {
            $sectionsQuery->where('class', $filterClass);
        }
        $sections = $sectionsQuery->whereNotNull('name')->distinct()->pluck('name')->sort()->values();
        
        if ($sections->isEmpty()) {
            $sections = collect(['A', 'B', 'C', 'D']);
        }

        // Get exams (filtered by campus if provided)
        $examsQuery = Exam::query();
        if ($filterCampus) {
            $examsQuery->where('campus', $filterCampus);
        }
        $exams = $examsQuery->whereNotNull('exam_name')->distinct()->pluck('exam_name')->sort()->values();

        // Get exam types (using test_type from Test model as reference)
        $examTypes = collect(['Mid Term', 'Final Term', 'Quiz', 'Assignment', 'Project', 'Oral Test', 'Practical']);
        
        // Try to get from Test model if available
        $testTypes = Test::whereNotNull('test_type')->distinct()->pluck('test_type')->sort()->values();
        if ($testTypes->isNotEmpty()) {
            $examTypes = $testTypes;
        }

        return view('exam.tabulation-sheet.particular', compact(
            'campuses',
            'classes',
            'sections',
            'exams',
            'examTypes',
            'filterCampus',
            'filterClass',
            'filterSection',
            'filterExam',
            'filterType'
        ));
    }

    /**
     * Get exams based on campus (AJAX) for tabulation sheet.
     */
    public function getExamsForTabulationSheet(Request $request): JsonResponse
    {
        $campus = $request->get('campus');
        
        $examsQuery = Exam::query();
        if ($campus) {
            $examsQuery->where('campus', $campus);
        }
        
        $exams = $examsQuery->whereNotNull('exam_name')
            ->distinct()
            ->pluck('exam_name')
            ->sort()
            ->values();
        
        return response()->json($exams);
    }

    /**
     * Display the tabulation sheet for final result page.
     */
    public function tabulationSheetFinal(Request $request): View
    {
        // Get filter values
        $filterCampus = $request->get('filter_campus');
        $filterClass = $request->get('filter_class');
        $filterSection = $request->get('filter_section');
        $filterSession = $request->get('filter_session');

        // Get campuses for dropdown
        $campusesFromClasses = ClassModel::whereNotNull('campus')->distinct()->pluck('campus');
        $campusesFromSections = Section::whereNotNull('campus')->distinct()->pluck('campus');
        $campuses = $campusesFromClasses->merge($campusesFromSections)->unique()->sort()->values();
        
        if ($campuses->isEmpty()) {
            $campuses = collect(['Main Campus', 'Branch Campus 1', 'Branch Campus 2']);
        }

        // Get classes
        $classes = ClassModel::whereNotNull('class_name')->distinct()->pluck('class_name')->sort()->values();
        
        if ($classes->isEmpty()) {
            $classes = collect(['Nursery', 'KG', '1st', '2nd', '3rd', '4th', '5th', '6th', '7th', '8th', '9th', '10th', '11th', '12th']);
        }

        // Get sections (filtered by class if provided)
        $sectionsQuery = Section::query();
        if ($filterClass) {
            $sectionsQuery->where('class', $filterClass);
        }
        $sections = $sectionsQuery->whereNotNull('name')->distinct()->pluck('name')->sort()->values();
        
        if ($sections->isEmpty()) {
            $sections = collect(['A', 'B', 'C', 'D']);
        }

        // Get academic sessions
        $sessions = Exam::whereNotNull('session')->distinct()->pluck('session')->sort()->values();
        
        if ($sessions->isEmpty()) {
            $sessions = collect(['2024-2025', '2025-2026', '2026-2027']);
        }

        return view('exam.tabulation-sheet.final', compact(
            'campuses',
            'classes',
            'sections',
            'sessions',
            'filterCampus',
            'filterClass',
            'filterSection',
            'filterSession'
        ));
    }

    /**
     * Display the position holders for particular exam page.
     */
    public function positionHoldersParticular(Request $request): View
    {
        // Get filter values
        $filterCampus = $request->get('filter_campus');
        $filterClass = $request->get('filter_class');
        $filterSection = $request->get('filter_section');
        $filterExam = $request->get('filter_exam');

        // Get campuses for dropdown
        $campusesFromClasses = ClassModel::whereNotNull('campus')->distinct()->pluck('campus');
        $campusesFromSections = Section::whereNotNull('campus')->distinct()->pluck('campus');
        $campuses = $campusesFromClasses->merge($campusesFromSections)->unique()->sort()->values();
        
        if ($campuses->isEmpty()) {
            $campuses = collect(['Main Campus', 'Branch Campus 1', 'Branch Campus 2']);
        }

        // Get classes
        $classes = ClassModel::whereNotNull('class_name')->distinct()->pluck('class_name')->sort()->values();
        
        if ($classes->isEmpty()) {
            $classes = collect(['Nursery', 'KG', '1st', '2nd', '3rd', '4th', '5th', '6th', '7th', '8th', '9th', '10th', '11th', '12th']);
        }

        // Get sections (filtered by class if provided)
        $sectionsQuery = Section::query();
        if ($filterClass) {
            $sectionsQuery->where('class', $filterClass);
        }
        $sections = $sectionsQuery->whereNotNull('name')->distinct()->pluck('name')->sort()->values();
        
        if ($sections->isEmpty()) {
            $sections = collect(['A', 'B', 'C', 'D']);
        }

        // Get exams (filtered by campus if provided)
        $examsQuery = Exam::query();
        if ($filterCampus) {
            $examsQuery->where('campus', $filterCampus);
        }
        $exams = $examsQuery->whereNotNull('exam_name')->distinct()->pluck('exam_name')->sort()->values();

        return view('exam.position-holders.particular', compact(
            'campuses',
            'classes',
            'sections',
            'exams',
            'filterCampus',
            'filterClass',
            'filterSection',
            'filterExam'
        ));
    }

    /**
     * Get exams based on campus (AJAX) for position holders.
     */
    public function getExamsForPositionHolders(Request $request): JsonResponse
    {
        $campus = $request->get('campus');
        
        $examsQuery = Exam::query();
        if ($campus) {
            $examsQuery->where('campus', $campus);
        }
        
        $exams = $examsQuery->whereNotNull('exam_name')
            ->distinct()
            ->pluck('exam_name')
            ->sort()
            ->values();
        
        return response()->json($exams);
    }

    /**
     * Display the position holders for final result page.
     */
    public function positionHoldersFinal(Request $request): View
    {
        // Get filter values
        $filterCampus = $request->get('filter_campus');
        $filterClass = $request->get('filter_class');
        $filterSection = $request->get('filter_section');
        $filterSession = $request->get('filter_session');

        // Get campuses for dropdown
        $campusesFromClasses = ClassModel::whereNotNull('campus')->distinct()->pluck('campus');
        $campusesFromSections = Section::whereNotNull('campus')->distinct()->pluck('campus');
        $campuses = $campusesFromClasses->merge($campusesFromSections)->unique()->sort()->values();
        
        if ($campuses->isEmpty()) {
            $campuses = collect(['Main Campus', 'Branch Campus 1', 'Branch Campus 2']);
        }

        // Get classes
        $classes = ClassModel::whereNotNull('class_name')->distinct()->pluck('class_name')->sort()->values();
        
        if ($classes->isEmpty()) {
            $classes = collect(['Nursery', 'KG', '1st', '2nd', '3rd', '4th', '5th', '6th', '7th', '8th', '9th', '10th', '11th', '12th']);
        }

        // Get sections (filtered by class if provided)
        $sectionsQuery = Section::query();
        if ($filterClass) {
            $sectionsQuery->where('class', $filterClass);
        }
        $sections = $sectionsQuery->whereNotNull('name')->distinct()->pluck('name')->sort()->values();
        
        if ($sections->isEmpty()) {
            $sections = collect(['A', 'B', 'C', 'D']);
        }

        // Get academic sessions
        $sessions = Exam::whereNotNull('session')->distinct()->pluck('session')->sort()->values();
        
        if ($sessions->isEmpty()) {
            $sessions = collect(['2024-2025', '2025-2026', '2026-2027']);
        }

        return view('exam.position-holders.final', compact(
            'campuses',
            'classes',
            'sections',
            'sessions',
            'filterCampus',
            'filterClass',
            'filterSection',
            'filterSession'
        ));
    }

    /**
     * Display the print admit cards page.
     */
    public function printAdmitCards(Request $request): View
    {
        // Get filter values
        $filterCampus = $request->get('filter_campus');
        $filterExam = $request->get('filter_exam');
        $filterClass = $request->get('filter_class');
        $filterSection = $request->get('filter_section');
        $filterType = $request->get('filter_type');

        // Get campuses for dropdown
        $campusesFromClasses = ClassModel::whereNotNull('campus')->distinct()->pluck('campus');
        $campusesFromSections = Section::whereNotNull('campus')->distinct()->pluck('campus');
        $campuses = $campusesFromClasses->merge($campusesFromSections)->unique()->sort()->values();
        
        if ($campuses->isEmpty()) {
            $campuses = collect(['Main Campus', 'Branch Campus 1', 'Branch Campus 2']);
        }

        // Get exams (filtered by campus if provided)
        $examsQuery = Exam::query();
        if ($filterCampus) {
            $examsQuery->where('campus', $filterCampus);
        }
        $exams = $examsQuery->whereNotNull('exam_name')->distinct()->pluck('exam_name')->sort()->values();

        // Get classes
        $classes = ClassModel::whereNotNull('class_name')->distinct()->pluck('class_name')->sort()->values();
        
        if ($classes->isEmpty()) {
            $classes = collect(['Nursery', 'KG', '1st', '2nd', '3rd', '4th', '5th', '6th', '7th', '8th', '9th', '10th', '11th', '12th']);
        }

        // Get sections (filtered by class if provided)
        $sectionsQuery = Section::query();
        if ($filterClass) {
            $sectionsQuery->where('class', $filterClass);
        }
        $sections = $sectionsQuery->whereNotNull('name')->distinct()->pluck('name')->sort()->values();
        
        if ($sections->isEmpty()) {
            $sections = collect(['A', 'B', 'C', 'D']);
        }

        // Get types (using test_type from Test model as reference)
        $types = collect(['Card', 'Slip']);
        
        // Try to get from Test model if available
        $testTypes = Test::whereNotNull('test_type')->distinct()->pluck('test_type')->sort()->values();
        if ($testTypes->isNotEmpty()) {
            $types = $testTypes->merge($types)->unique()->sort()->values();
        }

        return view('exam.print-admit-cards', compact(
            'campuses',
            'exams',
            'classes',
            'sections',
            'types',
            'filterCampus',
            'filterExam',
            'filterClass',
            'filterSection',
            'filterType'
        ));
    }

    /**
     * Get exams based on campus (AJAX) for print admit cards.
     */
    public function getExamsForPrintAdmitCards(Request $request): JsonResponse
    {
        $campus = $request->get('campus');
        
        $examsQuery = Exam::query();
        if ($campus) {
            $examsQuery->where('campus', $campus);
        }
        
        $exams = $examsQuery->whereNotNull('exam_name')
            ->distinct()
            ->pluck('exam_name')
            ->sort()
            ->values();
        
        return response()->json($exams);
    }
}

