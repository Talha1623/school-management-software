<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\ClassModel;
use App\Models\Section;
use App\Models\Subject;
use App\Models\Test;
use App\Models\Student;
use App\Models\StudentMark;
use App\Models\ParticularExamGrade;
use App\Models\FinalExamGrade;
use App\Models\ExamTimetable;
use App\Models\Campus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;

class ExamController extends Controller
{
    /**
     * Display a listing of exams.
     */
    public function index(Request $request): View
    {
        $query = Exam::query();
        $filterCampus = $request->get('filter_campus');
        
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

        if ($filterCampus) {
            $query->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($filterCampus))]);
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
        
        return view('exam.list', compact('exams', 'campuses', 'sessions', 'filterCampus'));
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
        $filterCampus = $request->get('filter_campus');
        $filterExam = $request->get('filter_exam');
        $filterClass = $request->get('filter_class');
        $filterSection = $request->get('filter_section');
        $filterSubject = $request->get('filter_subject');

        // Check if staff is logged in and is a teacher
        $staff = Auth::guard('staff')->user();
        $isTeacher = $staff && $staff->isTeacher();
        $teacherName = $isTeacher ? strtolower(trim($staff->name ?? '')) : null;

        // Get campuses for dropdown - filter by teacher's assigned campuses if teacher
        if ($isTeacher && $teacherName) {
            $teacherCampuses = Subject::whereRaw('LOWER(TRIM(teacher)) = ?', [$teacherName])
                ->whereNotNull('campus')
                ->distinct()
                ->pluck('campus')
                ->merge(
                    Section::whereRaw('LOWER(TRIM(teacher)) = ?', [$teacherName])
                        ->whereNotNull('campus')
                        ->distinct()
                        ->pluck('campus')
                )
                ->map(fn($c) => trim($c))
                ->filter(fn($c) => !empty($c))
                ->unique()
                ->sort()
                ->values();

            if ($teacherCampuses->isNotEmpty()) {
                $campuses = Campus::orderBy('campus_name', 'asc')
                    ->get()
                    ->filter(function($campus) use ($teacherCampuses) {
                        return $teacherCampuses->contains(strtolower(trim($campus->campus_name ?? '')));
                    });

                if ($campuses->isEmpty()) {
                    $campuses = $teacherCampuses->map(function($campus) {
                        return (object)['campus_name' => $campus];
                    });
                }
            } else {
                $campuses = collect();
            }
        } else {
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

        // Get exams from Exam List (distinct exam names, filter by campus if selected)
        $examsQuery = Exam::whereNotNull('exam_name');
        if ($filterCampus) {
            $examsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($filterCampus))]);
        }
        $exams = $examsQuery
            ->orderBy('exam_name', 'asc')
            ->get()
            ->pluck('exam_name')
            ->unique()
            ->values();

        // Get classes - filter by teacher's assigned classes if teacher
        $classes = $this->getClassesForMarksEntryList($filterCampus, $staff);
        $filterClasses = $filterCampus ? $classes : $classes;
        
        // Get sections (will be filtered dynamically based on class selection) - filter by teacher's assigned subjects if teacher
        $sections = collect(); // Initialized as empty, will be filled via AJAX
        if ($filterClass) {
            if ($staff && $staff->isTeacher()) {
                $assignedSubjectsQuery = Subject::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($staff->name ?? ''))])
                    ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($filterClass))]);
                $assignedSectionsQuery = Section::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($staff->name ?? ''))])
                    ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($filterClass))]);
                if ($filterCampus) {
                    $assignedSubjectsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($filterCampus))]);
                    $assignedSectionsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($filterCampus))]);
                }

                $assignedSubjects = $assignedSubjectsQuery->get();
                $assignedSections = $assignedSectionsQuery->get();

                $sections = $assignedSubjects->pluck('section')
                    ->merge($assignedSections->pluck('name'))
                    ->map(fn($section) => trim($section))
                    ->filter(fn($section) => !empty($section))
                    ->unique()
                    ->sort()
                    ->values();
            } else {
                $sectionsQuery = Section::whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($filterClass))])
                    ->whereNotNull('name');
                if ($filterCampus) {
                    $sectionsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($filterCampus))]);
                }
                $sections = $sectionsQuery->distinct()->pluck('name')->sort()->values();
                if ($sections->isEmpty()) {
                    $subjectsQuery = Subject::whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($filterClass))])
                        ->whereNotNull('section');
                    if ($filterCampus) {
                        $subjectsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($filterCampus))]);
                    }
                    $sections = $subjectsQuery->distinct()->pluck('section')->sort()->values();
                }
            }
        }

        // Get subjects (filtered by class and section if provided, and by teacher if teacher)
        $subjectsQuery = Subject::query();
        
        // Filter by teacher's assigned subjects if teacher
        if ($staff && $staff->isTeacher()) {
            $subjectsQuery->whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($staff->name ?? ''))]);
        }
        if ($filterCampus) {
            $subjectsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($filterCampus))]);
        }
        
        if ($filterClass) {
            $subjectsQuery->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($filterClass))]);
        }
        
        // Get subjects for class first (without section filter) - this ensures subjects always show
        $subjectsForClass = clone $subjectsQuery;
        $subjectsForClass = $subjectsForClass->whereNotNull('subject_name')->distinct()->pluck('subject_name')->sort()->values();
        
        // Try filtering by section if provided
        if ($filterSection) {
            $subjectsQuery->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($filterSection))]);
            $subjectsWithSection = $subjectsQuery->whereNotNull('subject_name')->distinct()->pluck('subject_name')->sort()->values();
            
            // If subjects found with section filter, use those; otherwise use class subjects
            if ($subjectsWithSection->isNotEmpty()) {
                $subjects = $subjectsWithSection;
            } else {
                // Fallback to class subjects if section filter doesn't match
                $subjects = $subjectsForClass;
            }
        } else {
            // No section filter, use class subjects
            $subjects = $subjectsForClass;
        }
        
        // If no subjects found and no filters applied, show all subjects (or teacher's subjects if teacher)
        if ($subjects->isEmpty() && !$filterClass && !$filterSection) {
            if ($staff && $staff->isTeacher()) {
                $subjects = Subject::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($staff->name ?? ''))])
                    ->whereNotNull('subject_name')
                    ->distinct()
                    ->pluck('subject_name')
                    ->sort()
                    ->values();
            } else {
                $subjects = Subject::whereNotNull('subject_name')->distinct()->pluck('subject_name')->sort()->values();
            }
        }

        // Query students based on filters
        $students = collect();
        if ($filterClass || $filterSection) {
            $studentsQuery = Student::query();
            
            if ($filterCampus) {
                $studentsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($filterCampus))]);
            }
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
                    ->when($filterCampus, function($query) use ($filterCampus) {
                        return $query->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($filterCampus))]);
                    })
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
            'campuses',
            'exams',
            'classes',
            'filterClasses',
            'sections',
            'subjects',
            'students',
            'filterExam',
            'filterCampus',
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
        $campus = $request->get('campus');
        if (!$class) {
            return response()->json(['sections' => []]);
        }
        
        $staff = Auth::guard('staff')->user();
        $sections = collect();
        
        // Filter by teacher's assigned subjects and sections if teacher
        if ($staff && $staff->isTeacher()) {
            // Get sections from teacher's assigned subjects for this class
            $assignedSubjectsQuery = Subject::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($staff->name ?? ''))])
                ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))]);
            if ($campus) {
                $assignedSubjectsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
            }
            $assignedSubjects = $assignedSubjectsQuery->get();
            
            // Get sections from teacher's assigned sections for this class
            $assignedSectionsQuery = Section::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($staff->name ?? ''))])
                ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))]);
            if ($campus) {
                $assignedSectionsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
            }
            $assignedSections = $assignedSectionsQuery->get();
            
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
            $sectionsQuery = Section::whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))])
                ->whereNotNull('name');
            if ($campus) {
                $sectionsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
            }
            $sections = $sectionsQuery->distinct()->pluck('name')->sort()->values();
            
            if ($sections->isEmpty()) {
                $subjectsQuery = Subject::whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))])
                    ->whereNotNull('section');
                if ($campus) {
                    $subjectsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
                }
                $sections = $subjectsQuery->distinct()->pluck('section')->sort()->values();
            }
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
        $campus = $request->get('campus');
        
        $staff = Auth::guard('staff')->user();
        
        $subjectsQuery = Subject::query();
        
        // Filter by teacher's assigned subjects if teacher
        if ($staff && $staff->isTeacher()) {
            $subjectsQuery->whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($staff->name ?? ''))]);
        }
        if ($campus) {
            $subjectsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
        }
        
        if ($class) {
            $subjectsQuery->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))]);
        }
        
        // Get subjects for class first (without section filter) - this ensures subjects always show
        $subjectsForClass = clone $subjectsQuery;
        $subjectsForClass = $subjectsForClass->whereNotNull('subject_name')->distinct()->pluck('subject_name')->sort()->values();
        
        // Try filtering by section if provided
        if ($section) {
            $subjectsQuery->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($section))]);
            $subjectsWithSection = $subjectsQuery->whereNotNull('subject_name')->distinct()->pluck('subject_name')->sort()->values();
            
            // If subjects found with section filter, use those; otherwise use class subjects
            if ($subjectsWithSection->isNotEmpty()) {
                $subjects = $subjectsWithSection;
            } else {
                // Fallback to class subjects if section filter doesn't match
                $subjects = $subjectsForClass;
            }
        } else {
            // No section filter, use class subjects
            $subjects = $subjectsForClass;
        }
        
        return response()->json(['subjects' => $subjects]);
    }

    /**
     * Get classes based on campus (AJAX) for marks entry.
     */
    public function getClassesForMarksEntry(Request $request): JsonResponse
    {
        $campus = $request->get('campus');
        $staff = Auth::guard('staff')->user();

        $classes = $this->getClassesForMarksEntryList($campus, $staff);

        return response()->json(['classes' => $classes]);
    }

    /**
     * Get exams based on campus (AJAX) for marks entry.
     */
    public function getExamsForMarksEntry(Request $request): JsonResponse
    {
        $campus = $request->get('campus');
        $examsQuery = Exam::whereNotNull('exam_name');
        if ($campus) {
            $examsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
        }
        $exams = $examsQuery->distinct()->orderBy('exam_name', 'asc')->pluck('exam_name')->values();

        return response()->json(['exams' => $exams]);
    }

    private function getClassesForMarksEntryList(?string $campus, $staff)
    {
        $campus = trim((string) $campus);
        $campusLower = strtolower($campus);
        $isTeacher = $staff && $staff->isTeacher();
        $teacherName = $isTeacher ? strtolower(trim($staff->name ?? '')) : null;

        if ($isTeacher && $teacherName) {
            $assignedSubjectsQuery = Subject::whereRaw('LOWER(TRIM(teacher)) = ?', [$teacherName])
                ->whereNotNull('class');
            $assignedSectionsQuery = Section::whereRaw('LOWER(TRIM(teacher)) = ?', [$teacherName])
                ->whereNotNull('class');
            if ($campus !== '') {
                $assignedSubjectsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [$campusLower]);
                $assignedSectionsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [$campusLower]);
            }

            $allClasses = $assignedSubjectsQuery->pluck('class')
                ->merge($assignedSectionsQuery->pluck('class'))
                ->map(fn($class) => trim((string) $class))
                ->filter(fn($class) => $class !== '')
                ->unique()
                ->values();

            $existingClassNames = ClassModel::whereNotNull('class_name');
            if ($campus !== '') {
                $existingClassNames->whereRaw('LOWER(TRIM(campus)) = ?', [$campusLower]);
            }
            $existingClassNames = $existingClassNames
                ->pluck('class_name')
                ->map(fn($name) => strtolower(trim($name)))
                ->toArray();

            return $allClasses->filter(function($className) use ($existingClassNames) {
                return in_array(strtolower(trim($className)), $existingClassNames);
            })->sort()->values();
        }

        $classesQuery = ClassModel::whereNotNull('class_name');
        if ($campus !== '') {
            $classesQuery->whereRaw('LOWER(TRIM(campus)) = ?', [$campusLower]);
        }

        return $classesQuery->distinct()
            ->pluck('class_name')
            ->map(fn($class) => trim((string) $class))
            ->filter(fn($class) => $class !== '')
            ->unique()
            ->sort()
            ->values();
    }

    /**
     * Save exam marks for students.
     */
    public function saveExamMarks(Request $request): RedirectResponse
    {
        try {
            $validated = $request->validate([
                'campus' => ['required', 'string'],
                'exam_name' => ['required', 'string'],
                'class' => ['required', 'string'],
                'section' => ['nullable', 'string'],
                'subject' => ['required', 'string'],
                'marks' => ['required', 'array', 'min:1'],
                'marks.*.obtained' => ['nullable', 'numeric', 'min:0'],
                'marks.*.total' => ['nullable', 'numeric', 'min:0'],
                'marks.*.passing' => ['nullable', 'numeric', 'min:0'],
            ], [
                'marks.required' => 'Please enter marks for at least one student.',
                'marks.min' => 'Please enter marks for at least one student.',
                'exam_name.required' => 'Exam name is required.',
                'subject.required' => 'Subject is required.',
            ]);

            // Check if marks array is empty or has no valid data
            $hasValidMarks = false;
            foreach ($validated['marks'] as $studentId => $markData) {
                if (isset($markData['obtained']) || isset($markData['total']) || isset($markData['passing'])) {
                    $hasValidMarks = true;
                    break;
                }
            }

            if (!$hasValidMarks) {
                return redirect()
                    ->route('exam.marks-entry', [
                        'filter_campus' => $validated['campus'],
                        'filter_exam' => $validated['exam_name'],
                        'filter_class' => $validated['class'],
                        'filter_section' => $validated['section'] ?? '',
                        'filter_subject' => $validated['subject'],
                    ])
                    ->with('error', 'Please enter at least one mark (obtained, total, or passing) for at least one student.');
            }

            $campus = $validated['campus'] ?? '';

            $savedCount = 0;
            // Save or update marks for each student
            foreach ($validated['marks'] as $studentId => $markData) {
                if ($studentId) {
                    $student = Student::find($studentId);
                    if (!$student) {
                        continue; // Skip if student not found
                    }
                    
                    // Only save if at least one mark field has a value
                    if (isset($markData['obtained']) || isset($markData['total']) || isset($markData['passing'])) {
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
                                'marks_obtained' => !empty($markData['obtained']) ? $markData['obtained'] : null,
                                'total_marks' => !empty($markData['total']) ? $markData['total'] : null,
                                'passing_marks' => !empty($markData['passing']) ? $markData['passing'] : null,
                            ]
                        );
                        $savedCount++;
                    }
                }
            }
            
            if ($savedCount > 0) {
                return redirect()
                    ->route('exam.marks-entry', [
                        'filter_campus' => $validated['campus'],
                        'filter_exam' => $validated['exam_name'],
                        'filter_class' => $validated['class'],
                        'filter_section' => $validated['section'] ?? '',
                        'filter_subject' => $validated['subject'],
                    ])
                    ->with('success', "Exam marks saved successfully for {$savedCount} student(s)!");
            } else {
                return redirect()
                    ->route('exam.marks-entry', [
                        'filter_campus' => $validated['campus'],
                        'filter_exam' => $validated['exam_name'],
                        'filter_class' => $validated['class'],
                        'filter_section' => $validated['section'] ?? '',
                        'filter_subject' => $validated['subject'],
                    ])
                    ->with('error', 'No marks were saved. Please enter at least one mark value.');
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()
                ->back()
                ->withErrors($e->errors())
                ->withInput();
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'An error occurred while saving marks: ' . $e->getMessage())
                ->withInput();
        }
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

        // Check if staff is logged in and is a teacher
        $staff = Auth::guard('staff')->user();
        $isTeacher = $staff && $staff->isTeacher();
        $teacherName = $isTeacher ? strtolower(trim($staff->name ?? '')) : null;
        
        // Get campuses for dropdown - filter by teacher's assigned campuses if teacher
        if ($isTeacher && $teacherName) {
            // Get campuses from teacher's assigned subjects
            $teacherCampuses = Subject::whereRaw('LOWER(TRIM(teacher)) = ?', [$teacherName])
                ->whereNotNull('campus')
                ->distinct()
                ->pluck('campus')
                ->merge(
                    Section::whereRaw('LOWER(TRIM(teacher)) = ?', [$teacherName])
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
                $campusesFromClasses = ClassModel::whereNotNull('campus')->distinct()->pluck('campus');
                $campusesFromSections = Section::whereNotNull('campus')->distinct()->pluck('campus');
                $campusesFromSubjects = Subject::whereNotNull('campus')->distinct()->pluck('campus');
                $allCampuses = $campusesFromClasses->merge($campusesFromSections)->merge($campusesFromSubjects)->unique()->sort()->values();
                
                // Convert to collection of objects with campus_name property
                $campuses = collect();
                foreach ($allCampuses as $campusName) {
                    $campuses->push((object)['campus_name' => $campusName]);
                }
            }
        }

        // Get exams - only from Exam model (super admin uploaded), filter by campus
        // Note: Exam model doesn't have class field, so we show all exams for the selected campus
        $examsQuery = Exam::query();
        
        if ($filterCampus) {
            $examsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($filterCampus))]);
        }
        
        // Get distinct exam names from Exam model only (super admin uploaded) - dynamic
        $exams = $examsQuery->whereNotNull('exam_name')
            ->distinct()
            ->pluck('exam_name')
            ->sort()
            ->values();

        // Get classes - filter by teacher's assigned classes if teacher, and only show existing (not deleted) classes
        $classes = $this->getClassesForTeacherRemarksList($filterCampus, $staff);
        $filterClasses = $filterCampus ? $classes : $classes;

        // Get sections (filtered by class if provided)
        $sectionsQuery = Section::query();
        if ($filterClass) {
            $sectionsQuery->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($filterClass))]);
        }
        if ($filterCampus) {
            $sectionsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($filterCampus))]);
        }
        $sections = $sectionsQuery->whereNotNull('name')->distinct()->pluck('name')->sort()->values();
        
        if ($sections->isEmpty()) {
            $sections = collect(['A', 'B', 'C', 'D']);
        }

        // Query students based on filters - Require Campus, Exam, and Class
        $students = collect();
        if ($filterCampus && $filterExam && $filterClass) {
            $studentsQuery = Student::query();
            
            // Always filter by campus and class
            $studentsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($filterCampus))]);
            $studentsQuery->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($filterClass))]);
            
            // Filter by section if provided
            if ($filterSection) {
                $studentsQuery->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($filterSection))]);
            }
            
            $students = $studentsQuery->orderBy('student_code', 'asc')
                ->orderBy('student_name', 'asc')
                ->get();
            
            // Load marks for each student for the selected exam
            if ($students->count() > 0) {
                $marks = StudentMark::where('test_name', $filterExam)
                    ->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($filterCampus))])
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

        return view('exam.teacher-remarks.particular', compact(
            'campuses',
            'exams',
            'classes',
            'filterClasses',
            'sections',
            'students',
            'filterCampus',
            'filterExam',
            'filterClass',
            'filterSection'
        ));
    }

    /**
     * Get exams based on campus (AJAX) for teacher remarks.
     * Only returns exams from Exam model (super admin uploaded) - dynamic.
     * Note: Exam model doesn't have class field, so we show all exams for the selected campus.
     */
    public function getExamsForTeacherRemarks(Request $request): JsonResponse
    {
        $campus = $request->get('campus');
        $class = $request->get('class'); // Class is used for validation but not for filtering exams
        
        // Check if staff is logged in and is a teacher
        $staff = Auth::guard('staff')->user();
        $isTeacher = $staff && $staff->isTeacher();
        $teacherName = $isTeacher ? strtolower(trim($staff->name ?? '')) : null;
        
        // If teacher, verify the class is assigned to the teacher
        if ($isTeacher && $teacherName && $class) {
            $assignedSubjects = Subject::whereRaw('LOWER(TRIM(teacher)) = ?', [$teacherName])
                ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))])
                ->exists();
            
            $assignedSections = Section::whereRaw('LOWER(TRIM(teacher)) = ?', [$teacherName])
                ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))])
                ->exists();
            
            // If teacher is not assigned to this class, return empty
            if (!$assignedSubjects && !$assignedSections) {
                return response()->json([]);
            }
        }
        
        // Get exams ONLY from Exam model (super admin uploaded) - dynamic
        // Filter by campus only (Exam model doesn't have class field)
        $examsQuery = Exam::query();
        
        if ($campus) {
            $examsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
        }
        
        // Get distinct exam names from Exam model only (super admin uploaded)
        $exams = $examsQuery->whereNotNull('exam_name')
            ->distinct()
            ->pluck('exam_name')
            ->sort()
            ->values();
        
        return response()->json($exams);
    }

    /**
     * Get sections based on class (AJAX) for teacher remarks.
     */
    public function getSectionsForTeacherRemarks(Request $request): JsonResponse
    {
        $class = $request->get('class');
        $campus = $request->get('campus');
        
        // Check if staff is logged in and is a teacher
        $staff = Auth::guard('staff')->user();
        $isTeacher = $staff && $staff->isTeacher();
        $teacherName = $isTeacher ? strtolower(trim($staff->name ?? '')) : null;
        
        $sectionsQuery = Section::when($class, fn($q) => $q->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))]))
            ->whereNotNull('name');
        if ($campus) {
            $sectionsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
        }
        
        // If teacher, filter by assigned sections
        if ($isTeacher && $teacherName) {
            $sectionsQuery->whereRaw('LOWER(TRIM(teacher)) = ?', [$teacherName]);
        }
        
        $sections = $sectionsQuery->distinct()->pluck('name')->sort()->values();
        
        // If no sections from Section table and teacher, try from Subject table
        if ($sections->isEmpty() && $isTeacher && $teacherName && $class) {
            $subjectsQuery = Subject::whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))])
                ->whereRaw('LOWER(TRIM(teacher)) = ?', [$teacherName]);
            if ($campus) {
                $subjectsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
            }
            $sections = $subjectsQuery
                ->whereNotNull('section')
                ->distinct()
                ->pluck('section')
                ->sort()
                ->values();
        }
        
        return response()->json($sections);
    }

    /**
     * Get classes for teacher remarks based on campus (AJAX).
     */
    public function getClassesForTeacherRemarks(Request $request): JsonResponse
    {
        $campus = $request->get('campus');
        $staff = Auth::guard('staff')->user();

        $classes = $this->getClassesForTeacherRemarksList($campus, $staff);

        return response()->json(['classes' => $classes]);
    }

    private function getClassesForTeacherRemarksList(?string $campus, $staff)
    {
        $campus = trim((string) $campus);
        $campusLower = strtolower($campus);
        $isTeacher = $staff && $staff->isTeacher();
        $teacherName = $isTeacher ? strtolower(trim($staff->name ?? '')) : null;

        if ($isTeacher && $teacherName) {
            $assignedSubjectsQuery = Subject::whereRaw('LOWER(TRIM(teacher)) = ?', [$teacherName])
                ->whereNotNull('class');
            $assignedSectionsQuery = Section::whereRaw('LOWER(TRIM(teacher)) = ?', [$teacherName])
                ->whereNotNull('class');
            if ($campus !== '') {
                $assignedSubjectsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [$campusLower]);
                $assignedSectionsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [$campusLower]);
            }

            $allClasses = $assignedSubjectsQuery->pluck('class')
                ->merge($assignedSectionsQuery->pluck('class'))
                ->map(fn($class) => trim((string) $class))
                ->filter(fn($class) => $class !== '')
                ->unique()
                ->values();

            $existingClassNames = ClassModel::whereNotNull('class_name');
            if ($campus !== '') {
                $existingClassNames->whereRaw('LOWER(TRIM(campus)) = ?', [$campusLower]);
            }
            $existingClassNames = $existingClassNames
                ->pluck('class_name')
                ->map(fn($name) => strtolower(trim($name)))
                ->toArray();

            return $allClasses->filter(function($className) use ($existingClassNames) {
                return in_array(strtolower(trim($className)), $existingClassNames);
            })->sort()->values();
        }

        $classQuery = ClassModel::whereNotNull('class_name');
        if ($campus !== '') {
            $classQuery->whereRaw('LOWER(TRIM(campus)) = ?', [$campusLower]);
        }

        return $classQuery->distinct()
            ->pluck('class_name')
            ->map(fn($class) => trim((string) $class))
            ->filter(fn($class) => $class !== '')
            ->unique()
            ->sort()
            ->values();
    }

    /**
     * Save teacher remarks for particular exam.
     */
    public function saveTeacherRemarksParticular(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'exam_name' => ['required', 'string'],
            'campus' => ['required', 'string'],
            'class' => ['required', 'string'],
            'section' => ['nullable', 'string'],
            'remarks' => ['required', 'array'],
            'remarks.*' => ['nullable', 'string'],
        ]);

        // Save or update remarks for each student
        foreach ($validated['remarks'] as $studentId => $remark) {
            if ($remark) {
                StudentMark::updateOrCreate(
                    [
                        'student_id' => $studentId,
                        'test_name' => $validated['exam_name'],
                        'campus' => $validated['campus'],
                        'class' => $validated['class'],
                        'section' => $validated['section'] ?? null,
                    ],
                    [
                        'teacher_remarks' => $remark,
                    ]
                );
            }
        }

        return redirect()
            ->route('exam.teacher-remarks.particular', [
                'filter_campus' => $validated['campus'],
                'filter_exam' => $validated['exam_name'],
                'filter_class' => $validated['class'],
                'filter_section' => $validated['section'] ?? '',
            ])
            ->with('success', 'Teacher remarks saved successfully!');
    }

    /**
     * Save teacher remarks for final exam.
     */
    public function saveTeacherRemarksFinal(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'campus' => ['required', 'string'],
            'session' => ['required', 'string'],
            'class' => ['required', 'string'],
            'section' => ['nullable', 'string'],
            'remarks' => ['required', 'array'],
            'remarks.*' => ['nullable', 'string'],
        ]);

        // Save or update remarks for each student
        foreach ($validated['remarks'] as $studentId => $remark) {
            if ($remark) {
                // Use FINAL_RESULT as test_name for final exam remarks
                StudentMark::updateOrCreate(
                    [
                        'student_id' => $studentId,
                        'test_name' => 'FINAL_RESULT',
                        'campus' => $validated['campus'],
                        'class' => $validated['class'],
                        'section' => $validated['section'] ?? null,
                    ],
                    [
                        'teacher_remarks' => $remark,
                    ]
                );
            }
        }

        return redirect()
            ->route('exam.teacher-remarks.final', [
                'filter_campus' => $validated['campus'],
                'filter_session' => $validated['session'],
                'filter_class' => $validated['class'],
                'filter_section' => $validated['section'] ?? '',
            ])
            ->with('success', 'Teacher remarks saved successfully!');
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

        // Check if staff is logged in and is a teacher
        $staff = Auth::guard('staff')->user();
        $isTeacher = $staff && $staff->isTeacher();
        $teacherName = $isTeacher ? strtolower(trim($staff->name ?? '')) : null;
        
        // Get campuses for dropdown - filter by teacher's assigned campuses if teacher
        if ($isTeacher && $teacherName) {
            // Get campuses from teacher's assigned subjects
            $teacherCampuses = Subject::whereRaw('LOWER(TRIM(teacher)) = ?', [$teacherName])
                ->whereNotNull('campus')
                ->distinct()
                ->pluck('campus')
                ->merge(
                    Section::whereRaw('LOWER(TRIM(teacher)) = ?', [$teacherName])
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
                    })
                    ->pluck('campus_name')
                    ->sort()
                    ->values();
                
                // If no campuses found in Campus model, use teacher campuses directly
                if ($campuses->isEmpty()) {
                    $campuses = $teacherCampuses;
                }
            } else {
                // If teacher has no assigned campuses, show empty
                $campuses = collect();
            }
        } else {
            // For non-teachers (admin, staff, etc.), get all campuses
            $campuses = Campus::whereNotNull('campus_name')->distinct()->pluck('campus_name')->sort()->values();
            
            if ($campuses->isEmpty()) {
                $campusesFromClasses = ClassModel::whereNotNull('campus')->distinct()->pluck('campus');
                $campusesFromSections = Section::whereNotNull('campus')->distinct()->pluck('campus');
                $campuses = $campusesFromClasses->merge($campusesFromSections)->unique()->sort()->values();
            }
        }

        // Get sessions
        $sessions = Exam::whereNotNull('session')->distinct()->pluck('session')->sort()->values();
        
        if ($sessions->isEmpty()) {
            $sessions = collect(['2024-2025', '2025-2026', '2026-2027']);
        }

        // Get classes - filter by teacher's assigned classes if teacher, and only show existing (not deleted) classes
        if ($isTeacher && $teacherName) {
            // Get classes from teacher's assigned subjects
            $assignedSubjects = Subject::whereRaw('LOWER(TRIM(teacher)) = ?', [$teacherName])
                ->whereNotNull('class')
                ->get();
            
            // Get classes from teacher's assigned sections
            $assignedSections = Section::whereRaw('LOWER(TRIM(teacher)) = ?', [$teacherName])
                ->whereNotNull('class')
                ->get();
            
            // Merge classes from both sources and get unique class names
            $allClasses = $assignedSubjects->pluck('class')
                ->merge($assignedSections->pluck('class'))
                ->map(function($class) {
                    return trim($class);
                })
                ->filter(function($class) {
                    return !empty($class);
                })
                ->unique()
                ->values();
            
            // Verify these classes exist in ClassModel (not deleted)
            $existingClassNames = ClassModel::whereNotNull('class_name')
                ->pluck('class_name')
                ->map(fn($name) => strtolower(trim($name)))
                ->toArray();
            
            // Filter to only include classes that exist in ClassModel
            $classes = $allClasses->filter(function($className) use ($existingClassNames) {
                return in_array(strtolower(trim($className)), $existingClassNames);
            })->sort()->values();
        } else {
            // For non-teachers, get all classes from ClassModel (not deleted) - no fallback
            $classes = ClassModel::whereNotNull('class_name')->distinct()->pluck('class_name')->sort()->values();
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

        // Query students based on filters
        $students = collect();
        if ($filterCampus && $filterSession && $filterClass) {
            $studentsQuery = Student::query();
            
            // Always filter by campus, session, and class
            $studentsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($filterCampus))]);
            $studentsQuery->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($filterClass))]);
            
            // Filter by section if provided
            if ($filterSection) {
                $studentsQuery->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($filterSection))]);
            }
            
            $students = $studentsQuery->orderBy('student_code', 'asc')
                ->orderBy('student_name', 'asc')
                ->get();
            
            // Load final exam marks for each student (aggregate from all exams in the session)
            if ($students->count() > 0) {
                // Get all exam names for this session
                $examNames = Exam::where('session', $filterSession)
                    ->whereNotNull('exam_name')
                    ->distinct()
                    ->pluck('exam_name');
                
                // Aggregate marks from all exams in the session
                $students = $students->map(function($student) use ($examNames, $filterCampus, $filterClass, $filterSection, $filterSession) {
                    // Get all marks for this student from exams in the session
                    $marksQuery = StudentMark::where('student_id', $student->id)
                        ->whereIn('test_name', $examNames)
                        ->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($filterCampus))])
                        ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($filterClass))]);
                    
                    if ($filterSection) {
                        $marksQuery->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($filterSection))]);
                    }
                    
                    $marks = $marksQuery->get();
                    
                    // Calculate aggregated totals
                    $totalMarks = $marks->sum('total_marks') ?? 0;
                    $obtainedMarks = $marks->sum('marks_obtained') ?? 0;
                    
                    // Get teacher remarks for final exam (stored with test_name = "FINAL_RESULT" or session-based)
                    $finalRemark = StudentMark::where('student_id', $student->id)
                        ->where(function($query) use ($filterSession) {
                            $query->where('test_name', 'FINAL_RESULT')
                                  ->orWhere('test_name', 'LIKE', '%FINAL%')
                                  ->orWhere('test_name', $filterSession . '_FINAL');
                        })
                        ->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($filterCampus))])
                        ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($filterClass))]);
                    
                    if ($filterSection) {
                        $finalRemark->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($filterSection))]);
                    }
                    
                    $finalRemark = $finalRemark->first();
                    
                    $student->finalTotal = $totalMarks;
                    $student->finalObtained = $obtainedMarks;
                    $student->finalRemark = $finalRemark;
                    
                    return $student;
                });
            }
        }

        return view('exam.teacher-remarks.final', compact(
            'campuses',
            'sessions',
            'classes',
            'sections',
            'filterCampus',
            'filterSession',
            'filterClass',
            'filterSection',
            'students'
        ));
    }

    /**
     * Display the add exam timetable page.
     */
    public function addTimetable(Request $request): View
    {
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

        // Classes will be loaded by campus selection
        $classes = collect();

        // Get sections (will be loaded via AJAX based on class)
        $sections = collect();

        // Get subjects (will be loaded via AJAX based on class/section)
        $subjects = collect();

        // Exams will be loaded by campus selection
        $exams = collect();

        return view('exam.timetable.add', compact(
            'campuses',
            'classes',
            'sections',
            'subjects',
            'exams'
        ));
    }

    /**
     * Store a newly created exam timetable entry.
     */
    public function storeTimetable(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'campus' => ['required', 'string', 'max:255'],
            'class' => ['required', 'string', 'max:255'],
            'section' => ['required', 'string', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'exam' => ['required', 'string', 'max:255'],
            'date' => ['required', 'date'],
            'starting_time' => ['required'],
            'ending_time' => ['required'],
            'room_block' => ['nullable', 'string', 'max:255'],
        ]);

        ExamTimetable::create([
            'campus' => $validated['campus'],
            'class' => $validated['class'],
            'section' => $validated['section'],
            'subject' => $validated['subject'],
            'exam_name' => $validated['exam'],
            'exam_date' => $validated['date'],
            'starting_time' => $validated['starting_time'],
            'ending_time' => $validated['ending_time'],
            'room_block' => $validated['room_block'] ?? null,
        ]);

        return redirect()
            ->route('exam.timetable.add')
            ->with('success', 'Exam timetable saved successfully!');
    }

    /**
     * Get sections based on class (AJAX) for exam timetable.
     */
    public function getSectionsForTimetable(Request $request): JsonResponse
    {
        $class = $request->get('class');
        $campus = $request->get('campus');
        $sections = Section::when($class, fn($q) => $q->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))]))
            ->when($campus, fn($q) => $q->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]))
            ->whereNotNull('name')
            ->distinct()
            ->pluck('name')
            ->sort()
            ->values();
        
        return response()->json($sections);
    }

    /**
     * Get subjects based on class and section (AJAX) for exam timetable.
     */
    public function getSubjectsForTimetable(Request $request): JsonResponse
    {
        $class = $request->get('class');
        $section = $request->get('section');
        $campus = $request->get('campus');
        
        $subjectsQuery = Subject::query();
        
        if ($class) {
            $subjectsQuery->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))]);
        }
        
        if ($section) {
            $subjectsQuery->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($section))]);
        }

        if ($campus) {
            $subjectsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
        }
        
        $subjects = $subjectsQuery->whereNotNull('subject_name')
            ->distinct()
            ->pluck('subject_name')
            ->sort()
            ->values();
        
        return response()->json($subjects);
    }

    /**
     * Get classes based on campus (AJAX) for exam timetable.
     */
    public function getClassesForTimetable(Request $request): JsonResponse
    {
        $campus = trim((string) $request->get('campus'));
        $campusLower = strtolower($campus);

        $classesQuery = ClassModel::whereNotNull('class_name');
        if ($campus !== '') {
            $classesQuery->whereRaw('LOWER(TRIM(campus)) = ?', [$campusLower]);
        }

        $classes = $classesQuery->distinct()
            ->orderBy('class_name', 'asc')
            ->pluck('class_name')
            ->map(fn($class) => trim((string) $class))
            ->filter(fn($class) => $class !== '')
            ->unique()
            ->sort()
            ->values();

        return response()->json(['classes' => $classes]);
    }

    /**
     * Get exams based on campus (AJAX) for exam timetable add page.
     */
    public function getExamsForTimetable(Request $request): JsonResponse
    {
        $campus = $request->get('campus');

        $examsQuery = Exam::query();
        if ($campus) {
            $examsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
        }

        $exams = $examsQuery->whereNotNull('exam_name')
            ->distinct()
            ->pluck('exam_name')
            ->sort()
            ->values();

        return response()->json($exams);
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

        // Get classes - dynamic only, filtered by campus when selected
        $classesQuery = ClassModel::whereNotNull('class_name');
        if ($filterCampus) {
            $classesQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($filterCampus))]);
        }
        $classes = $classesQuery->distinct()->pluck('class_name')->sort()->values();
        $filterClasses = $classes;

        // Get sections (filtered by class/campus if provided)
        $sectionsQuery = Section::query();
        if ($filterClass) {
            $sectionsQuery->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($filterClass))]);
        }
        if ($filterCampus) {
            $sectionsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($filterCampus))]);
        }
        $sections = $sectionsQuery->whereNotNull('name')->distinct()->pluck('name')->sort()->values();
        
        if ($sections->isEmpty()) {
            $sections = collect(['A', 'B', 'C', 'D']);
        }

        $timetables = collect();
        if ($filterCampus || $filterExam || $filterClass || $filterSection) {
            $timetablesQuery = ExamTimetable::query();
            if ($filterCampus) {
                $timetablesQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($filterCampus))]);
            }
            if ($filterExam) {
                $timetablesQuery->whereRaw('LOWER(TRIM(exam_name)) = ?', [strtolower(trim($filterExam))]);
            }
            if ($filterClass) {
                $timetablesQuery->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($filterClass))]);
            }
            if ($filterSection) {
                $timetablesQuery->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($filterSection))]);
            }
            $timetables = $timetablesQuery
                ->orderBy('exam_date')
                ->orderBy('starting_time')
                ->get();
        }

        return view('exam.timetable.manage', compact(
            'campuses',
            'exams',
            'classes',
            'filterClasses',
            'sections',
            'filterCampus',
            'filterExam',
            'filterClass',
            'filterSection',
            'timetables'
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
            $examsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
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

        // Get classes (filtered by campus if provided)
        $classesQuery = ClassModel::whereNotNull('class_name');
        if ($filterCampus) {
            $classesQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($filterCampus))]);
        }
        $classes = $classesQuery->distinct()->pluck('class_name')->sort()->values();

        // Get sections (filtered by class if provided)
        $sectionsQuery = Section::query();
        if ($filterClass) {
            $sectionsQuery->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($filterClass))]);
        }
        if ($filterCampus) {
            $sectionsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($filterCampus))]);
        }
        $sections = $sectionsQuery->whereNotNull('name')->distinct()->pluck('name')->sort()->values();

        // Get exams (filtered by campus if provided)
        $examsQuery = Exam::query();
        if ($filterCampus) {
            $examsQuery->where('campus', $filterCampus);
        }
        $exams = $examsQuery->whereNotNull('exam_name')->distinct()->pluck('exam_name')->sort()->values();

        // Get exam types (remove Daily Test and ensure Normal/Editable are present)
        $examTypes = collect(['Normal', 'Editable', 'Mid Term', 'Final Term', 'Quiz', 'Assignment', 'Project', 'Oral Test', 'Practical']);
        $testTypes = Test::whereNotNull('test_type')->distinct()->pluck('test_type')->sort()->values();
        if ($testTypes->isNotEmpty()) {
            $examTypes = $testTypes->filter(function ($type) {
                return strtolower(trim($type)) !== 'daily test';
            })->values();
            if (!$examTypes->contains('Normal')) {
                $examTypes->prepend('Normal');
            }
            if (!$examTypes->contains('Editable')) {
                $examTypes->prepend('Editable');
            }
        }

        $subjects = collect();
        $tabulationRows = collect();
        $examSession = null;

        if ($filterCampus && $filterClass && $filterSection && $filterExam) {
            $examSession = Exam::whereRaw('LOWER(TRIM(exam_name)) = ?', [strtolower(trim($filterExam))])
                ->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($filterCampus))])
                ->value('session');

            $subjectsQuery = Subject::query()
                ->whereNotNull('subject_name')
                ->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($filterCampus))])
                ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($filterClass))])
                ->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($filterSection))]);
            $subjects = $subjectsQuery->distinct()->pluck('subject_name')->sort()->values();

            if ($subjects->isEmpty()) {
                $subjects = StudentMark::whereRaw('LOWER(TRIM(test_name)) = ?', [strtolower(trim($filterExam))])
                    ->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($filterCampus))])
                    ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($filterClass))])
                    ->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($filterSection))])
                    ->whereNotNull('subject')
                    ->distinct()
                    ->pluck('subject')
                    ->map(fn($subject) => trim((string) $subject))
                    ->filter(fn($subject) => $subject !== '')
                    ->unique()
                    ->sort()
                    ->values();
            }

            $students = Student::query()
                ->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($filterCampus))])
                ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($filterClass))])
                ->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($filterSection))])
                ->orderBy('student_name')
                ->get();

            $marks = StudentMark::whereRaw('LOWER(TRIM(test_name)) = ?', [strtolower(trim($filterExam))])
                ->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($filterCampus))])
                ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($filterClass))])
                ->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($filterSection))])
                ->get();

            $marksByStudent = $marks->groupBy('student_id');

            $gradesQuery = ParticularExamGrade::query()
                ->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($filterCampus))])
                ->whereRaw('LOWER(TRIM(for_exam)) = ?', [strtolower(trim($filterExam))]);
            if ($examSession) {
                $gradesQuery->whereRaw('LOWER(TRIM(session)) = ?', [strtolower(trim($examSession))]);
            }
            $grades = $gradesQuery->orderBy('from_percentage', 'desc')->get();
            if ($grades->isEmpty()) {
                $finalGradesQuery = FinalExamGrade::query()
                    ->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($filterCampus))]);
                if ($examSession) {
                    $finalGradesQuery->whereRaw('LOWER(TRIM(session)) = ?', [strtolower(trim($examSession))]);
                }
                $grades = $finalGradesQuery->orderBy('from_percentage', 'desc')->get();
            }

            $tabulationRows = $students->map(function ($student) use ($marksByStudent, $subjects, $grades) {
                $studentMarks = $marksByStudent->get($student->id, collect());
                $subjectScores = [];
                $totalMarks = 0;
                $obtainedMarks = 0;

                foreach ($subjects as $subjectName) {
                    $mark = $studentMarks->first(function ($item) use ($subjectName) {
                        return strtolower(trim((string) $item->subject)) === strtolower(trim((string) $subjectName));
                    });
                    $subjectScores[$subjectName] = $mark?->marks_obtained ?? 0;
                    $totalMarks += $mark?->total_marks ?? 0;
                    $obtainedMarks += $mark?->marks_obtained ?? 0;
                }

                $percentage = $totalMarks > 0 ? ($obtainedMarks / $totalMarks) * 100 : 0;
                $gradeName = '-';
                $gradeGpa = '-';
                if ($grades->isNotEmpty()) {
                    $matchedGrade = $grades->first(function ($grade) use ($percentage) {
                        return $percentage >= $grade->from_percentage && $percentage <= $grade->to_percentage;
                    });
                    if ($matchedGrade) {
                        $gradeName = $matchedGrade->name;
                        $gradeGpa = $matchedGrade->grade_points;
                    }
                }

                return (object) [
                    'student' => $student,
                    'subject_scores' => $subjectScores,
                    'total_marks' => $totalMarks,
                    'obtained_marks' => $obtainedMarks,
                    'percentage' => $percentage,
                    'grade' => $gradeName,
                    'gpa' => $gradeGpa,
                ];
            });

            $tabulationRows = $tabulationRows->sortByDesc('obtained_marks')->values();
            $tabulationRows = $tabulationRows->map(function ($row, $index) {
                $row->rank = $index + 1;
                return $row;
            });
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
            'filterType',
            'subjects',
            'tabulationRows',
            'examSession'
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
     * Get classes based on campus (AJAX) for tabulation sheet.
     */
    public function getClassesForTabulationSheet(Request $request): JsonResponse
    {
        $campus = trim((string) $request->get('campus'));
        $classesQuery = ClassModel::whereNotNull('class_name');
        if ($campus !== '') {
            $classesQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower($campus)]);
        }

        $classes = $classesQuery->distinct()
            ->pluck('class_name')
            ->map(fn($class) => trim((string) $class))
            ->filter(fn($class) => $class !== '')
            ->unique()
            ->sort()
            ->values();

        return response()->json(['classes' => $classes]);
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

        // Get classes (filtered by campus if provided)
        $classesQuery = ClassModel::whereNotNull('class_name');
        if ($filterCampus) {
            $classesQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($filterCampus))]);
        }
        $classes = $classesQuery->distinct()->pluck('class_name')->sort()->values();

        // Get sections (filtered by class if provided)
        $sectionsQuery = Section::query();
        if ($filterClass) {
            $sectionsQuery->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($filterClass))]);
        }
        if ($filterCampus) {
            $sectionsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($filterCampus))]);
        }
        $sections = $sectionsQuery->whereNotNull('name')->distinct()->pluck('name')->sort()->values();

        // Get exams (filtered by campus if provided)
        $examsQuery = Exam::query();
        if ($filterCampus) {
            $examsQuery->where('campus', $filterCampus);
        }
        $exams = $examsQuery->whereNotNull('exam_name')->distinct()->pluck('exam_name')->sort()->values();

        $positionRows = collect();
        if ($filterCampus && $filterClass && $filterExam) {
            $studentsQuery = Student::query()
                ->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($filterCampus))])
                ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($filterClass))]);
            if ($filterSection) {
                $studentsQuery->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($filterSection))]);
            }
            $students = $studentsQuery->orderBy('student_name')->get();

            $marksQuery = StudentMark::whereRaw('LOWER(TRIM(test_name)) = ?', [strtolower(trim($filterExam))])
                ->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($filterCampus))])
                ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($filterClass))]);
            if ($filterSection) {
                $marksQuery->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($filterSection))]);
            }
            $marks = $marksQuery->get()->groupBy('student_id');

            $positionRows = $students->map(function ($student) use ($marks) {
                $studentMarks = $marks->get($student->id, collect());
                $totalObtained = $studentMarks->sum('marks_obtained');
                return (object) [
                    'student' => $student,
                    'total_obtained' => $totalObtained,
                ];
            });

            $positionRows = $positionRows->sortByDesc('total_obtained')->values();
            $positionRows = $positionRows->map(function ($row, $index) {
                $row->position = $index + 1;
                return $row;
            });
        }

        return view('exam.position-holders.particular', compact(
            'campuses',
            'classes',
            'sections',
            'exams',
            'filterCampus',
            'filterClass',
            'filterSection',
            'filterExam',
            'positionRows'
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
     * Get classes based on campus (AJAX) for position holders.
     */
    public function getClassesForPositionHolders(Request $request): JsonResponse
    {
        $campus = trim((string) $request->get('campus'));
        $classesQuery = ClassModel::whereNotNull('class_name');
        if ($campus !== '') {
            $classesQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower($campus)]);
        }

        $classes = $classesQuery->distinct()
            ->pluck('class_name')
            ->map(fn($class) => trim((string) $class))
            ->filter(fn($class) => $class !== '')
            ->unique()
            ->sort()
            ->values();

        return response()->json(['classes' => $classes]);
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

        // Get classes (dynamic only, filtered by campus when selected)
        $classesQuery = ClassModel::whereNotNull('class_name');
        if ($filterCampus) {
            $classesQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($filterCampus))]);
        }
        $classes = $classesQuery->distinct()->pluck('class_name')->sort()->values();
        $filterClasses = $classes;

        // Get sections (filtered by class/campus if provided)
        $sectionsQuery = Section::query();
        if ($filterClass) {
            $sectionsQuery->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($filterClass))]);
        }
        if ($filterCampus) {
            $sectionsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($filterCampus))]);
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

        // Load students for admit cards
        $students = collect();
        if ($filterCampus && $filterClass) {
            $studentsQuery = Student::whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($filterCampus))])
                ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($filterClass))]);
            if ($filterSection) {
                $studentsQuery->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($filterSection))]);
            }
            $students = $studentsQuery->orderBy('student_name', 'asc')->get();
        }

        return view('exam.print-admit-cards', compact(
            'campuses',
            'exams',
            'classes',
            'filterClasses',
            'sections',
            'types',
            'students',
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
            $examsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
        }
        
        $exams = $examsQuery->whereNotNull('exam_name')
            ->distinct()
            ->pluck('exam_name')
            ->sort()
            ->values();
        
        return response()->json($exams);
    }

    /**
     * Display the print marksheet page for particular exam.
     */
    public function printMarksheetParticular(Request $request): View
    {
        $filterCampus = $request->get('filter_campus');
        $filterClass = $request->get('filter_class');
        $filterSection = $request->get('filter_section');
        $filterExam = $request->get('filter_exam');
        $filterSession = $request->get('filter_session');
        $isPrint = $request->boolean('print');

        // Campuses for dropdown
        $campuses = Campus::orderBy('campus_name', 'asc')->get();
        if ($campuses->isEmpty()) {
            $campusesFromClasses = ClassModel::whereNotNull('campus')->distinct()->pluck('campus');
            $campusesFromSections = Section::whereNotNull('campus')->distinct()->pluck('campus');
            $campuses = $campusesFromClasses->merge($campusesFromSections)->unique()->sort()->values();
        }

        // Classes filtered by campus
        $classesQuery = ClassModel::whereNotNull('class_name');
        if ($filterCampus) {
            $classesQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($filterCampus))]);
        }
        $classes = $classesQuery->distinct()->pluck('class_name')->sort()->values();
        $filterClasses = $classes;

        // Sections filtered by class/campus
        $sectionsQuery = Section::query();
        if ($filterClass) {
            $sectionsQuery->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($filterClass))]);
        }
        if ($filterCampus) {
            $sectionsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($filterCampus))]);
        }
        $sections = $sectionsQuery->whereNotNull('name')->distinct()->pluck('name')->sort()->values();

        // Sessions filtered by campus/class
        $sessionsQuery = Section::whereNotNull('session');
        if ($filterCampus) {
            $sessionsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($filterCampus))]);
        }
        if ($filterClass) {
            $sessionsQuery->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($filterClass))]);
        }
        $sessions = $sessionsQuery->distinct()->pluck('session')->sort()->values();

        // Exams filtered by campus
        $examsQuery = Exam::whereNotNull('exam_name');
        if ($filterCampus) {
            $examsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($filterCampus))]);
        }
        $exams = $examsQuery->distinct()->pluck('exam_name')->sort()->values();

        $marksByStudent = collect();
        $teacherRemarksByStudent = collect();
        $students = collect();
        $highestBySubject = collect();
        $studentSummaries = collect();

        $sessionSections = collect();
        if ($filterSession) {
            $sessionSections = Section::whereRaw('LOWER(TRIM(session)) = ?', [strtolower(trim($filterSession))])
                ->when($filterCampus, function($q) use ($filterCampus) {
                    return $q->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($filterCampus))]);
                })
                ->when($filterClass, function($q) use ($filterClass) {
                    return $q->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($filterClass))]);
                })
                ->whereNotNull('name')
                ->distinct()
                ->pluck('name')
                ->values();
        }

        if ($filterCampus && $filterClass && $filterExam) {
            $marksQuery = StudentMark::whereRaw('LOWER(TRIM(test_name)) = ?', [strtolower(trim($filterExam))])
                ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($filterClass))])
                ->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($filterCampus))]);

            if ($filterSection) {
                $marksQuery->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($filterSection))]);
            } elseif ($filterSession && $sessionSections->isNotEmpty()) {
                $marksQuery->whereIn('section', $sessionSections);
            } elseif ($filterSession && $sessionSections->isEmpty()) {
                $marksQuery->whereRaw('1 = 0');
            }

            $marks = $marksQuery->get();
            $teacherRemarksByStudent = $marks
                ->filter(function ($mark) {
                    return $mark->teacher_remarks !== null && trim((string) $mark->teacher_remarks) !== '';
                })
                ->sortByDesc('created_at')
                ->groupBy('student_id')
                ->map(function ($items) {
                    return trim((string) $items->first()->teacher_remarks);
                });
            $highestBySubject = $marks->groupBy('subject')->map(function($items) {
                return $items->max('marks_obtained');
            });

            $studentIds = $marks->pluck('student_id')->filter()->unique()->values();
            if ($studentIds->isNotEmpty()) {
                $studentsQuery = Student::whereIn('id', $studentIds)
                    ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($filterClass))])
                    ->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($filterCampus))]);
                if ($filterSection) {
                    $studentsQuery->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($filterSection))]);
                } elseif ($filterSession && $sessionSections->isNotEmpty()) {
                    $studentsQuery->whereIn('section', $sessionSections);
                } elseif ($filterSession && $sessionSections->isEmpty()) {
                    $studentsQuery->whereRaw('1 = 0');
                }
                $students = $studentsQuery->orderBy('student_name')->get();
            }

            $marksByStudent = $marks->groupBy('student_id');
            $studentSummaries = $marksByStudent->mapWithKeys(function($items, $studentId) {
                $totalMarks = $items->sum(function($m) { return (float) ($m->total_marks ?? 0); });
                $totalPassing = $items->sum(function($m) { return (float) ($m->passing_marks ?? 0); });
                $totalObtained = $items->sum(function($m) { return (float) ($m->marks_obtained ?? 0); });
                $presentCount = $items->filter(function($m) {
                    return $m->marks_obtained !== null;
                })->count();
                $subjectCount = $items->count();

                $percentage = $totalMarks > 0 ? round(($totalObtained / $totalMarks) * 100, 2) : 0;
                $status = $totalObtained >= $totalPassing ? 'PASS' : 'FAIL';

                return [$studentId => [
                    'total_marks' => $totalMarks,
                    'total_passing' => $totalPassing,
                    'total_obtained' => $totalObtained,
                    'percentage' => $percentage,
                    'status' => $status,
                    'present_count' => $presentCount,
                    'subject_count' => $subjectCount,
                ]];
            });

            $ranked = $studentSummaries->sortByDesc('total_obtained')->keys()->values();
            $rankMap = collect();
            $ranked->each(function($studentId, $index) use ($rankMap) {
                $rankMap->put($studentId, $index + 1);
            });
            $studentSummaries = $studentSummaries->map(function($summary, $studentId) use ($rankMap) {
                $summary['rank'] = $rankMap->get($studentId);
                return $summary;
            });
        }

        return view('exam.print-marksheet.particular', compact(
            'campuses',
            'classes',
            'filterClasses',
            'sections',
            'sessions',
            'exams',
            'students',
            'marksByStudent',
            'highestBySubject',
            'teacherRemarksByStudent',
            'studentSummaries',
            'isPrint',
            'filterCampus',
            'filterClass',
            'filterSection',
            'filterSession',
            'filterExam'
        ));
    }

    /**
     * Get exams based on campus (AJAX) for print marksheet.
     */
    public function getExamsForPrintMarksheet(Request $request): JsonResponse
    {
        $campus = $request->get('campus');
        $examsQuery = Exam::whereNotNull('exam_name');
        if ($campus) {
            $examsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
        }
        $exams = $examsQuery->distinct()->pluck('exam_name')->sort()->values();

        return response()->json(['exams' => $exams]);
    }

    /**
     * Display the exam report page.
     */
    public function examReport(): View
    {
        return view('exam.report');
    }

    /**
     * Get classes based on campus (AJAX) for print marksheet.
     */
    public function getClassesForPrintMarksheet(Request $request): JsonResponse
    {
        $campus = trim((string) $request->get('campus'));
        $campusLower = strtolower($campus);

        $classesQuery = ClassModel::whereNotNull('class_name');
        if ($campus !== '') {
            $classesQuery->whereRaw('LOWER(TRIM(campus)) = ?', [$campusLower]);
        }

        $classes = $classesQuery->distinct()
            ->pluck('class_name')
            ->map(fn($class) => trim((string) $class))
            ->filter(fn($class) => $class !== '')
            ->unique()
            ->sort()
            ->values();

        return response()->json(['classes' => $classes]);
    }

    /**
     * Get sections based on class/campus (AJAX) for print marksheet.
     */
    public function getSectionsForPrintMarksheet(Request $request): JsonResponse
    {
        $class = $request->get('class');
        $campus = $request->get('campus');
        if (!$class) {
            return response()->json(['sections' => []]);
        }

        $sectionsQuery = Section::whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))])
            ->whereNotNull('name');
        if ($campus) {
            $sectionsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
        }

        $sections = $sectionsQuery->distinct()->pluck('name')->sort()->values();

        return response()->json(['sections' => $sections]);
    }

    /**
     * Get sessions based on campus/class (AJAX) for print marksheet.
     */
    public function getSessionsForPrintMarksheet(Request $request): JsonResponse
    {
        $campus = $request->get('campus');
        $class = $request->get('class');

        $sessionsQuery = Section::whereNotNull('session');
        if ($campus) {
            $sessionsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
        }
        if ($class) {
            $sessionsQuery->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))]);
        }
        $sessions = $sessionsQuery->distinct()->pluck('session')->sort()->values();

        return response()->json(['sessions' => $sessions]);
    }

    /**
     * Get classes based on campus (AJAX) for print admit cards.
     */
    public function getClassesForPrintAdmitCards(Request $request): JsonResponse
    {
        $campus = trim((string) $request->get('campus'));
        $campusLower = strtolower($campus);

        $classesQuery = ClassModel::whereNotNull('class_name');
        if ($campus !== '') {
            $classesQuery->whereRaw('LOWER(TRIM(campus)) = ?', [$campusLower]);
        }

        $classes = $classesQuery->distinct()
            ->pluck('class_name')
            ->map(fn($class) => trim((string) $class))
            ->filter(fn($class) => $class !== '')
            ->unique()
            ->sort()
            ->values();

        return response()->json(['classes' => $classes]);
    }
}

