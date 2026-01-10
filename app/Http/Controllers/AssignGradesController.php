<?php

namespace App\Http\Controllers;

use App\Models\Test;
use App\Models\Student;
use App\Models\ClassModel;
use App\Models\Section;
use App\Models\Subject;
use App\Models\CombinedResultGrade;
use App\Models\Campus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AssignGradesController extends Controller
{
    /**
     * Display the assign grades for particular test page with filters.
     */
    public function particular(Request $request): View
    {
        // Get filter values
        $filterCampus = $request->get('filter_campus');
        $filterClass = $request->get('filter_class');
        $filterSection = $request->get('filter_section');
        $filterSubject = $request->get('filter_subject');
        $filterTest = $request->get('filter_test');

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

        // Get classes
        $classes = ClassModel::whereNotNull('class_name')->distinct()->pluck('class_name')->sort()->values();
        
        if ($classes->isEmpty()) {
            $classes = collect(['Nursery', 'KG', '1st', '2nd', '3rd', '4th', '5th', '6th', '7th', '8th', '9th', '10th', '11th', '12th']);
        }

        // Get sections - will be loaded dynamically based on class selection
        $sections = collect();
        if ($filterClass) {
            // Load sections for the selected class
            $sections = Section::whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($filterClass))])
                ->whereNotNull('name')
                ->distinct()
                ->pluck('name')
                ->sort()
                ->values();
                
            if ($sections->isEmpty()) {
                // Try from subjects table with case-insensitive matching
                $sections = Subject::whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($filterClass))])
                    ->whereNotNull('section')
                    ->distinct()
                    ->pluck('section')
                    ->sort()
                    ->values();
            }
        }

        // Get subjects (filtered by other criteria if provided)
        // Filter out subjects with deleted classes - only show active subjects
        $subjectsQuery = Subject::query();
        
        // Get active (non-deleted) class names
        $existingClassNames = ClassModel::whereNotNull('class_name')->pluck('class_name')->map(function($name) {
            return strtolower(trim($name));
        })->toArray();
        
        // Filter out subjects with deleted classes
        if (!empty($existingClassNames)) {
            $subjectsQuery->where(function($q) use ($existingClassNames) {
                foreach ($existingClassNames as $className) {
                    $q->orWhereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($className))]);
                }
            });
        } else {
            // If no classes exist, show no subjects
            $subjectsQuery->whereRaw('1 = 0');
        }
        
        if ($filterCampus) {
            $subjectsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($filterCampus))]);
        }
        if ($filterClass) {
            $subjectsQuery->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($filterClass))]);
        }
        if ($filterSection) {
            $subjectsQuery->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($filterSection))]);
        }
        $subjects = $subjectsQuery->whereNotNull('subject_name')->distinct()->pluck('subject_name')->sort()->values();
        
        if ($subjects->isEmpty()) {
            $subjects = collect(['Mathematics', 'English', 'Science', 'Urdu', 'Islamiat', 'Social Studies']);
        }

        // Get tests (filtered by other criteria if provided)
        $testsQuery = Test::query();
        if ($filterCampus) {
            $testsQuery->where('campus', $filterCampus);
        }
        if ($filterClass) {
            $testsQuery->where('for_class', $filterClass);
        }
        if ($filterSection) {
            $testsQuery->where('section', $filterSection);
        }
        if ($filterSubject) {
            $testsQuery->where('subject', $filterSubject);
        }
        $tests = $testsQuery->whereNotNull('test_name')->distinct()->pluck('test_name')->sort()->values();
        
        if ($tests->isEmpty()) {
            $tests = collect(['Quiz 1', 'Mid Term', 'Final Term', 'Assignment 1']);
        }

        // Query students based on filters
        $students = collect();
        if ($filterCampus || $filterClass || $filterSection) {
            $studentsQuery = Student::query();
            
            if ($filterCampus) {
                $studentsQuery->where('campus', $filterCampus);
            }
            if ($filterClass) {
                $studentsQuery->where('class', $filterClass);
            }
            if ($filterSection) {
                $studentsQuery->where('section', $filterSection);
            }
            
            $students = $studentsQuery->orderBy('student_name')->get();
        }

        return view('test.assign-grades.particular', compact(
            'campuses',
            'classes',
            'sections',
            'subjects',
            'tests',
            'students',
            'filterCampus',
            'filterClass',
            'filterSection',
            'filterSubject',
            'filterTest'
        ));
    }

    /**
     * Display the assign grades for combined result page with filters and CRUD.
     */
    public function combined(Request $request): View
    {
        $query = CombinedResultGrade::query();
        
        // Search functionality
        if ($request->filled('search')) {
            $search = trim($request->search);
            if (!empty($search)) {
                $searchLower = strtolower($search);
                $query->where(function($q) use ($search, $searchLower) {
                    $q->whereRaw('LOWER(name) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(campus) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(session) LIKE ?', ["%{$searchLower}%"]);
                });
            }
        }
        
        $perPage = $request->get('per_page', 10);
        $perPage = in_array($perPage, [10, 25, 50, 100]) ? $perPage : 10;
        
        $grades = $query->orderBy('from_percentage', 'desc')->paginate($perPage)->withQueryString();

        // Get campuses for dropdown - First from Campus model, then fallback
        $campuses = Campus::orderBy('campus_name', 'asc')->get();
        if ($campuses->isEmpty()) {
            $campusesFromClasses = ClassModel::whereNotNull('campus')->distinct()->pluck('campus');
            $campusesFromSections = Section::whereNotNull('campus')->distinct()->pluck('campus');
            $campusesFromSubjects = Subject::whereNotNull('campus')->distinct()->pluck('campus');
            $allCampuses = $campusesFromClasses->merge($campusesFromSections)->merge($campusesFromSubjects)->unique()->sort()->values();
            $campuses = $allCampuses->map(function($campus) {
                return (object)['campus_name' => $campus];
            });
        }
        
        // Convert to simple array for dropdown
        $campusesList = $campuses->map(function($campus) {
            return is_object($campus) ? ($campus->campus_name ?? '') : $campus;
        })->filter()->unique()->sort()->values();

        // Get sessions
        $sessions = Test::whereNotNull('session')->distinct()->pluck('session')->sort()->values();
        
        if ($sessions->isEmpty()) {
            $sessions = collect(['2024-2025', '2025-2026', '2026-2027']);
        }

        return view('test.assign-grades.combined', [
            'grades' => $grades,
            'campuses' => $campuses,
            'campusesList' => $campusesList,
            'sessions' => $sessions,
            'filterFromPercentage' => $request->filter_from_percentage,
            'filterToPercentage' => $request->filter_to_percentage,
            'filterSession' => $request->filter_session
        ]);
    }

    /**
     * Get sections by class (AJAX).
     */
    public function getSectionsByClass(Request $request): JsonResponse
    {
        $class = $request->get('class');
        
        if (!$class) {
            return response()->json(['sections' => []]);
        }
        
        // Use case-insensitive matching for class
        $sections = Section::whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))])
            ->whereNotNull('name')
            ->distinct()
            ->pluck('name')
            ->sort()
            ->values();
            
        if ($sections->isEmpty()) {
            // Try from subjects table with case-insensitive matching
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
     * Get subjects for a class (AJAX) - only active subjects (non-deleted classes).
     */
    public function getSubjectsByClass(Request $request): JsonResponse
    {
        $class = $request->get('class');
        $section = $request->get('section');
        $campus = $request->get('campus');
        
        if (!$class) {
            return response()->json(['subjects' => []]);
        }
        
        // Get active (non-deleted) class names
        $existingClassNames = ClassModel::whereNotNull('class_name')->pluck('class_name')->map(function($name) {
            return strtolower(trim($name));
        })->toArray();
        
        // Build query for subjects
        $subjectsQuery = Subject::whereNotNull('subject_name');
        
        // Filter by class (case-insensitive)
        $subjectsQuery->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))]);
        
        // Filter out subjects with deleted classes
        if (!empty($existingClassNames)) {
            $subjectsQuery->where(function($q) use ($existingClassNames) {
                foreach ($existingClassNames as $className) {
                    $q->orWhereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($className))]);
                }
            });
        } else {
            // If no classes exist, return empty
            return response()->json(['subjects' => []]);
        }
        
        // Filter by section if provided
        if ($section) {
            $subjectsQuery->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($section))]);
        }
        
        // Filter by campus if provided
        if ($campus) {
            $subjectsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
        }
        
        // Get unique subject names
        $subjects = $subjectsQuery->distinct()
            ->pluck('subject_name')
            ->sort()
            ->values();
        
        return response()->json(['subjects' => $subjects]);
    }

    /**
     * Get all campuses (AJAX) - dynamically fetch from Campus model and other sources.
     */
    public function getCampuses(Request $request): JsonResponse
    {
        // Get campuses from Campus model first
        $campuses = Campus::orderBy('campus_name', 'asc')->get();
        
        if ($campuses->isEmpty()) {
            // Fallback to other sources
            $campusesFromClasses = ClassModel::whereNotNull('campus')->distinct()->pluck('campus');
            $campusesFromSections = Section::whereNotNull('campus')->distinct()->pluck('campus');
            $campusesFromSubjects = Subject::whereNotNull('campus')->distinct()->pluck('campus');
            $allCampuses = $campusesFromClasses->merge($campusesFromSections)->merge($campusesFromSubjects)->unique()->sort()->values();
            
            $campuses = $allCampuses->map(function($campus) {
                return (object)['campus_name' => $campus];
            });
        }
        
        // Convert to simple array
        $campusesList = $campuses->map(function($campus) {
            return is_object($campus) ? ($campus->campus_name ?? '') : $campus;
        })->filter()->unique()->sort()->values();
        
        return response()->json(['campuses' => $campusesList]);
    }

    /**
     * Store a newly created combined result grade.
     */
    public function storeCombined(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'campus' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'from_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'to_percentage' => ['required', 'numeric', 'min:0', 'max:100', 'gte:from_percentage'],
            'session' => ['required', 'string', 'max:255'],
        ]);

        CombinedResultGrade::create($validated);

        return redirect()
            ->route('test.assign-grades.combined')
            ->with('success', 'Combined result grade created successfully!');
    }

    /**
     * Update the specified combined result grade.
     */
    public function updateCombined(Request $request, CombinedResultGrade $combinedResultGrade): RedirectResponse
    {
        $validated = $request->validate([
            'campus' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'from_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'to_percentage' => ['required', 'numeric', 'min:0', 'max:100', 'gte:from_percentage'],
            'session' => ['required', 'string', 'max:255'],
        ]);

        $combinedResultGrade->update($validated);

        return redirect()
            ->route('test.assign-grades.combined')
            ->with('success', 'Combined result grade updated successfully!');
    }

    /**
     * Remove the specified combined result grade.
     */
    public function destroyCombined(CombinedResultGrade $combinedResultGrade): RedirectResponse
    {
        $combinedResultGrade->delete();

        return redirect()
            ->route('test.assign-grades.combined')
            ->with('success', 'Combined result grade deleted successfully!');
    }

    /**
     * Export combined result grades to Excel, CSV, or PDF
     */
    public function exportCombined(Request $request, string $format)
    {
        $query = CombinedResultGrade::query();
        
        // Apply search filter if present
        if ($request->has('search') && $request->search) {
            $search = trim($request->search);
            if (!empty($search)) {
                $searchLower = strtolower($search);
                $query->where(function($q) use ($search, $searchLower) {
                    $q->whereRaw('LOWER(name) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(campus) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(session) LIKE ?', ["%{$searchLower}%"]);
                });
            }
        }
        
        $grades = $query->orderBy('from_percentage', 'desc')->get();
        
        switch ($format) {
            case 'excel':
                return $this->exportExcel($grades);
            case 'csv':
                return $this->exportCSV($grades);
            case 'pdf':
                return $this->exportPDF($grades);
            default:
                return redirect()->route('test.assign-grades.combined')
                    ->with('error', 'Invalid export format!');
        }
    }

    /**
     * Export to Excel (CSV format for Excel compatibility)
     */
    private function exportExcel($grades)
    {
        $filename = 'combined_result_grades_' . date('Y-m-d_His') . '.csv';
        
        $headers = [
            'Content-Type' => 'application/vnd.ms-excel',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($grades) {
            $file = fopen('php://output', 'w');
            
            // Add BOM for UTF-8
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            fputcsv($file, ['ID', 'Campus', 'Name', 'From %', 'To %', 'Session', 'Created At']);
            
            foreach ($grades as $grade) {
                fputcsv($file, [
                    $grade->id,
                    $grade->campus,
                    $grade->name,
                    $grade->from_percentage,
                    $grade->to_percentage,
                    $grade->session,
                    $grade->created_at->format('Y-m-d H:i:s'),
                ]);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export to CSV
     */
    private function exportCSV($grades)
    {
        $filename = 'combined_result_grades_' . date('Y-m-d_His') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($grades) {
            $file = fopen('php://output', 'w');
            
            fputcsv($file, ['ID', 'Campus', 'Name', 'From %', 'To %', 'Session', 'Created At']);
            
            foreach ($grades as $grade) {
                fputcsv($file, [
                    $grade->id,
                    $grade->campus,
                    $grade->name,
                    $grade->from_percentage,
                    $grade->to_percentage,
                    $grade->session,
                    $grade->created_at->format('Y-m-d H:i:s'),
                ]);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export to PDF
     */
    private function exportPDF($grades)
    {
        $html = view('test.assign-grades.combined-pdf', compact('grades'))->render();
        
        return response($html)
            ->header('Content-Type', 'text/html');
    }
}

