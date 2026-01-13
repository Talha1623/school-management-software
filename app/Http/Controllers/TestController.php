<?php

namespace App\Http\Controllers;

use App\Models\Test;
use App\Models\ClassModel;
use App\Models\Section;
use App\Models\Subject;
use App\Models\Campus;
use App\Models\StudentMark;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;

class TestController extends Controller
{
    /**
     * Display a listing of tests.
     */
    public function index(Request $request): View
    {
        $query = Test::query();
        
        // Filter tests for teachers based on their assigned subjects
        $staff = Auth::guard('staff')->user();
        if ($staff && $staff->isTeacher()) {
            // Get teacher's assigned subjects
            $teacherSubjects = Subject::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($staff->name ?? ''))])
                ->whereNotNull('subject_name')
                ->whereNotNull('class')
                ->get();

            if ($teacherSubjects->isNotEmpty()) {
                // Get teacher's subject names
                $teacherSubjectNames = $teacherSubjects->pluck('subject_name')->unique()->filter()->toArray();
                
                // Get all test names from StudentMark where teacher has entered marks
                $teacherTestNames = [];
                if (!empty($teacherSubjectNames)) {
                    $teacherTestNames = StudentMark::query()
                        ->whereIn('subject', $teacherSubjectNames)
                        ->distinct()
                        ->pluck('test_name')
                        ->filter()
                        ->unique()
                        ->toArray();
                }

                // Filter tests: Method 1 - Tests from StudentMark (where teacher entered marks)
                // Method 2 - Match by subject and class from Subject table
                $query->where(function($q) use ($teacherTestNames, $teacherSubjects, $teacherSubjectNames) {
                    // Primary: Tests from StudentMark (where teacher entered marks)
                    if (!empty($teacherTestNames)) {
                        $q->whereIn('test_name', $teacherTestNames);
                    }
                    
                    // Fallback: Match by subject and class (section is optional)
                    // Match any test where subject matches teacher's assigned subjects
                    if (!empty($teacherSubjectNames)) {
                        $q->orWhere(function($subjectQ) use ($teacherSubjectNames) {
                            foreach ($teacherSubjectNames as $subjectName) {
                                $subjectQ->orWhereRaw('LOWER(TRIM(subject)) = ?', [strtolower(trim($subjectName))]);
                            }
                        });
                    }
                    
                    // Also match by subject + class combination
                    if ($teacherSubjects->isNotEmpty()) {
                        $q->orWhere(function($subQ) use ($teacherSubjects) {
                            foreach ($teacherSubjects as $subject) {
                                $subQ->orWhere(function($subSubQ) use ($subject) {
                                    $subSubQ->whereRaw('LOWER(TRIM(subject)) = ?', [strtolower(trim($subject->subject_name ?? ''))])
                                         ->whereRaw('LOWER(TRIM(for_class)) = ?', [strtolower(trim($subject->class ?? ''))]);
                                });
                            }
                        });
                    }
                });
                
                // Remove duplicates
                $query->distinct();
            } else {
                // Teacher has no assigned subjects, show no tests
                $query->whereRaw('1 = 0');
            }
        }
        
        // Search functionality
        if ($request->filled('search')) {
            $search = trim($request->search);
            if (!empty($search)) {
                $searchLower = strtolower($search);
                $query->where(function($q) use ($search, $searchLower) {
                    $q->whereRaw('LOWER(test_name) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(for_class) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(section) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(subject) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(test_type) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(campus) LIKE ?', ["%{$searchLower}%"]);
                });
            }
        }
        
        $perPage = $request->get('per_page', 10);
        $perPage = in_array($perPage, [10, 25, 50, 100]) ? $perPage : 10;
        
        $tests = $query->orderBy('date', 'desc')->paginate($perPage)->withQueryString();

        // Get classes - filter by teacher's assigned classes if teacher
        $classes = collect();
        $staff = Auth::guard('staff')->user();
        
        // Get campuses for dropdown - filter by teacher's assigned campuses if teacher
        if ($staff && $staff->isTeacher()) {
            $teacherName = strtolower(trim($staff->name ?? ''));
            
            if (!empty($teacherName)) {
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
                // If teacher name is empty, show no campuses
                $campuses = collect();
            }
        } else {
            // For non-teachers (admin, staff, etc.), get all campuses
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
            // For non-teachers, get all classes
            $classes = ClassModel::whereNotNull('class_name')->distinct()->pluck('class_name')->sort()->values();
            
            if ($classes->isEmpty()) {
                $classes = collect(['Nursery', 'KG', '1st', '2nd', '3rd', '4th', '5th', '6th', '7th', '8th', '9th', '10th', '11th', '12th']);
            }
        }

        // Get sections (will be filtered dynamically based on class selection)
        $sections = collect();

        // Get subjects - filter by teacher's assigned subjects if teacher
        // Also filter out subjects with deleted classes
        $subjects = collect();
        
        // Get active (non-deleted) class names
        $existingClassNames = ClassModel::whereNotNull('class_name')->pluck('class_name')->map(function($name) {
            return strtolower(trim($name));
        })->toArray();
        
        if ($staff && $staff->isTeacher()) {
            // Teacher: Only show subjects assigned to this teacher
            $teacherName = strtolower(trim($staff->name ?? ''));
            
            if (!empty($teacherName)) {
                // Get subjects assigned to this teacher, filtered by active classes
                $assignedSubjectsQuery = Subject::whereRaw('LOWER(TRIM(teacher)) = ?', [$teacherName])
                    ->whereNotNull('subject_name');
                
                // Filter out subjects with deleted classes
                if (!empty($existingClassNames)) {
                    $assignedSubjectsQuery->where(function($q) use ($existingClassNames) {
                        foreach ($existingClassNames as $className) {
                            $q->orWhereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($className))]);
                        }
                    });
                } else {
                    // If no classes exist, show no subjects
                    $assignedSubjectsQuery->whereRaw('1 = 0');
                }
                
                $assignedSubjects = $assignedSubjectsQuery->get();
                
                // Get unique subject names (case-insensitive, keep original case)
                $subjectsMap = [];
                foreach ($assignedSubjects as $subject) {
                    $subjectName = trim($subject->subject_name ?? '');
                    if (!empty($subjectName)) {
                        $key = strtolower($subjectName);
                        if (!isset($subjectsMap[$key])) {
                            $subjectsMap[$key] = $subjectName;
                        }
                    }
                }
                
                if (!empty($subjectsMap)) {
                    $subjects = collect(array_values($subjectsMap))->sort()->values();
                }
            }
        } else {
            // For non-teachers (admin, staff, etc.), get all subjects filtered by active classes
            $subjectsQuery = Subject::whereNotNull('subject_name');
            
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
            
            $subjects = $subjectsQuery->distinct()->pluck('subject_name')->sort()->values();
            
            if ($subjects->isEmpty()) {
                $subjects = collect(['Mathematics', 'English', 'Science', 'Urdu', 'Islamiat', 'Social Studies']);
            }
        }

        // Get test types
        $testTypes = Test::whereNotNull('test_type')->distinct()->pluck('test_type')->sort()->values();
        
        if ($testTypes->isEmpty()) {
            $testTypes = collect(['Quiz', 'Mid Term', 'Final Term', 'Assignment', 'Project', 'Oral Test']);
        }

        // Get sessions
        $sessions = Test::whereNotNull('session')->distinct()->pluck('session')->sort()->values();
        
        if ($sessions->isEmpty()) {
            $sessions = collect(['2024-2025', '2025-2026', '2026-2027']);
        }
        
        return view('test.list', compact('tests', 'campuses', 'classes', 'sections', 'subjects', 'testTypes', 'sessions'));
    }

    /**
     * Store a newly created test.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'campus' => ['required', 'string', 'max:255'],
            'test_name' => ['required', 'string', 'max:255'],
            'for_class' => ['required', 'string', 'max:255'],
            'section' => ['required', 'string', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'test_type' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'date' => ['required', 'date'],
            'session' => ['required', 'string', 'max:255'],
        ]);

        Test::create($validated);

        return redirect()
            ->route('test.list')
            ->with('success', 'Test created successfully!');
    }

    /**
     * Update the specified test.
     */
    public function update(Request $request, Test $test): RedirectResponse
    {
        $validated = $request->validate([
            'campus' => ['required', 'string', 'max:255'],
            'test_name' => ['required', 'string', 'max:255'],
            'for_class' => ['required', 'string', 'max:255'],
            'section' => ['required', 'string', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'test_type' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'date' => ['required', 'date'],
            'session' => ['required', 'string', 'max:255'],
        ]);

        $test->update($validated);

        return redirect()
            ->route('test.list')
            ->with('success', 'Test updated successfully!');
    }

    /**
     * Remove the specified test.
     */
    public function destroy(Test $test): RedirectResponse
    {
        $test->delete();

        return redirect()
            ->route('test.list')
            ->with('success', 'Test deleted successfully!');
    }

    /**
     * Toggle result status for a test.
     */
    public function toggleResultStatus(Request $request, Test $test): JsonResponse
    {
        $test->result_status = !$test->result_status;
        $test->save();

        return response()->json([
            'success' => true,
            'result_status' => $test->result_status,
            'message' => $test->result_status ? 'Result declared successfully!' : 'Result status reset successfully!'
        ]);
    }

    /**
     * Get sections for a class (AJAX).
     */
    public function getSections(Request $request): JsonResponse
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
        
        // Filter by teacher if teacher
        $staff = Auth::guard('staff')->user();
        if ($staff && $staff->isTeacher()) {
            $teacherName = strtolower(trim($staff->name ?? ''));
            if (!empty($teacherName)) {
                $subjectsQuery->whereRaw('LOWER(TRIM(teacher)) = ?', [$teacherName]);
            }
        }
        
        // Get unique subject names
        $subjects = $subjectsQuery->distinct()->pluck('subject_name')->sort()->values();
        
        return response()->json(['subjects' => $subjects]);
    }

    /**
     * Export tests to Excel, CSV, or PDF
     */
    public function export(Request $request, string $format)
    {
        $query = Test::query();
        
        // Apply search filter if present
        if ($request->has('search') && $request->search) {
            $search = trim($request->search);
            if (!empty($search)) {
                $searchLower = strtolower($search);
                $query->where(function($q) use ($search, $searchLower) {
                    $q->whereRaw('LOWER(test_name) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(for_class) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(section) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(subject) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(test_type) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(campus) LIKE ?', ["%{$searchLower}%"]);
                });
            }
        }
        
        $tests = $query->orderBy('date', 'desc')->get();
        
        switch ($format) {
            case 'excel':
                return $this->exportExcel($tests);
            case 'csv':
                return $this->exportCSV($tests);
            case 'pdf':
                return $this->exportPDF($tests);
            default:
                return redirect()->route('test.list')
                    ->with('error', 'Invalid export format!');
        }
    }

    /**
     * Export to Excel (CSV format for Excel compatibility)
     */
    private function exportExcel($tests)
    {
        $filename = 'tests_' . date('Y-m-d_His') . '.csv';
        
        $headers = [
            'Content-Type' => 'application/vnd.ms-excel',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($tests) {
            $file = fopen('php://output', 'w');
            
            // Add BOM for UTF-8
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            fputcsv($file, ['ID', 'Campus', 'Test Name', 'For Class', 'Section', 'Subject', 'Test Type', 'Description', 'Date', 'Session', 'Created At']);
            
            foreach ($tests as $test) {
                fputcsv($file, [
                    $test->id,
                    $test->campus,
                    $test->test_name,
                    $test->for_class,
                    $test->section,
                    $test->subject,
                    $test->test_type,
                    $test->description ?? '',
                    $test->date->format('Y-m-d'),
                    $test->session,
                    $test->created_at->format('Y-m-d H:i:s'),
                ]);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export to CSV
     */
    private function exportCSV($tests)
    {
        $filename = 'tests_' . date('Y-m-d_His') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($tests) {
            $file = fopen('php://output', 'w');
            
            fputcsv($file, ['ID', 'Campus', 'Test Name', 'For Class', 'Section', 'Subject', 'Test Type', 'Description', 'Date', 'Session', 'Created At']);
            
            foreach ($tests as $test) {
                fputcsv($file, [
                    $test->id,
                    $test->campus,
                    $test->test_name,
                    $test->for_class,
                    $test->section,
                    $test->subject,
                    $test->test_type,
                    $test->description ?? '',
                    $test->date->format('Y-m-d'),
                    $test->session,
                    $test->created_at->format('Y-m-d H:i:s'),
                ]);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export to PDF
     */
    private function exportPDF($tests)
    {
        $html = view('test.list-pdf', compact('tests'))->render();
        
        return response($html)
            ->header('Content-Type', 'text/html');
    }
}

