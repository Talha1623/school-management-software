<?php

namespace App\Http\Controllers;

use App\Models\Test;
use App\Models\Staff;
use App\Models\ClassModel;
use App\Models\Section;
use App\Models\Subject;
use App\Models\Campus;
use App\Models\GeneralSetting;
use App\Models\StudentMark;
use App\Models\Message;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;

class TestController extends Controller
{
    private function staffCampusName(?Staff $staff): ?string
    {
        if (! $staff) {
            return null;
        }

        $campus = trim((string) ($staff->campus ?? ''));

        return $campus !== '' ? $campus : null;
    }

    private function staffCampusMatches(?Staff $staff, ?string $campus): bool
    {
        $staffCampus = $this->staffCampusName($staff);
        if ($staffCampus === null) {
            return false;
        }

        return strtolower(trim((string) $campus)) === strtolower($staffCampus);
    }

    private function applyStaffTestListScope($query, Staff $staff): void
    {
        $staffCampus = $this->staffCampusName($staff);
        if ($staffCampus === null) {
            $query->whereRaw('1 = 0');

            return;
        }

        $query->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower($staffCampus)]);

        $allowedClasses = $this->staffAssignableClasses($staff, $staffCampus);
        if ($allowedClasses->isEmpty()) {
            $query->whereRaw('1 = 0');

            return;
        }

        $query->where(function ($q) use ($allowedClasses) {
            foreach ($allowedClasses as $className) {
                $classKey = Staff::normalizeClassKey((string) $className);
                $q->orWhereRaw('LOWER(TRIM(for_class)) = ?', [strtolower(trim((string) $className))])
                    ->orWhereRaw('LOWER(TRIM(for_class)) = ?', [$classKey])
                    ->orWhereRaw("LOWER(TRIM(REPLACE(REPLACE(for_class, 'Class ', ''), 'class ', ''))) = ?", [$classKey]);
            }
        });
    }

    private function campusesForStaffViewer(Staff $staff): \Illuminate\Support\Collection
    {
        $names = collect();

        if ($staffCampus = $this->staffCampusName($staff)) {
            $names->push($staffCampus);
        }

        $assignedCampusesQuery = Subject::query();
        $staff->scopeQueryToTeacherAssignments($assignedCampusesQuery);
        $names = $names->merge(
            $assignedCampusesQuery
                ->whereNotNull('campus')
                ->whereRaw("TRIM(campus) != ''")
                ->distinct()
                ->pluck('campus')
        );

        $unique = $names
            ->map(fn ($campus) => trim((string) $campus))
            ->filter()
            ->unique(fn ($campus) => strtolower($campus))
            ->values();

        return $unique->map(function (string $campus) {
            $record = Campus::query()
                ->whereRaw('LOWER(TRIM(campus_name)) = ?', [strtolower($campus)])
                ->first();

            return (object) ['campus_name' => $record?->campus_name ?? $campus];
        });
    }

    private function staffCanUseCampus(Staff $staff, ?string $campus): bool
    {
        if ($campus === null || trim($campus) === '') {
            return false;
        }

        $campusKey = strtolower(trim($campus));

        return $this->campusesForStaffViewer($staff)
            ->pluck('campus_name')
            ->map(fn ($name) => strtolower(trim((string) $name)))
            ->contains($campusKey);
    }

    private function staffAssignableClasses(Staff $staff, ?string $campus): \Illuminate\Support\Collection
    {
        if ($campus === null || trim($campus) === '') {
            return collect();
        }

        $classes = $staff->assignedSubjectClassNames($campus);
        if ($classes->isNotEmpty()) {
            return $classes;
        }

        $classes = $staff->assignedTeachingClassNames($campus);
        if ($classes->isNotEmpty()) {
            return $classes;
        }

        return $staff->assignedAttendanceClassNames($campus);
    }

    private function classesForCampusSelection(Staff $staff, string $campus): \Illuminate\Support\Collection
    {
        $classes = $this->staffAssignableClasses($staff, $campus);
        if ($classes->isNotEmpty()) {
            return $classes;
        }

        return ClassModel::whereNotNull('class_name')
            ->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))])
            ->distinct()
            ->pluck('class_name')
            ->sort()
            ->values();
    }

    private function sectionsForClassAtCampus(string $class, ?string $campus): \Illuminate\Support\Collection
    {
        $classKey = Staff::normalizeClassKey($class);

        $applyClassFilter = function ($query) use ($class, $classKey) {
            $query->where(function ($q) use ($class, $classKey) {
                $q->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))])
                    ->orWhereRaw('LOWER(TRIM(class)) = ?', [$classKey])
                    ->orWhereRaw("LOWER(TRIM(REPLACE(REPLACE(class, 'Class ', ''), 'class ', ''))) = ?", [$classKey]);
            });
        };

        $sectionsQuery = Section::query();
        $applyClassFilter($sectionsQuery);
        $sectionsQuery->whereNotNull('name');

        if ($campus !== null && trim($campus) !== '') {
            $campusKey = strtolower(trim($campus));
            $sectionsQuery->where(function ($q) use ($campusKey) {
                $q->whereRaw('LOWER(TRIM(COALESCE(campus, ""))) = ?', [$campusKey])
                    ->orWhereRaw('TRIM(COALESCE(campus, "")) = ?', ['']);
            });
        }

        $sections = $sectionsQuery
            ->distinct()
            ->pluck('name')
            ->map(fn ($section) => trim((string) $section))
            ->filter()
            ->unique()
            ->sort()
            ->values();

        if ($sections->isNotEmpty()) {
            return $sections;
        }

        $subjectsQuery = Subject::query();
        $applyClassFilter($subjectsQuery);
        $subjectsQuery->whereNotNull('section');

        if ($campus !== null && trim($campus) !== '') {
            $campusKey = strtolower(trim($campus));
            $subjectsQuery->where(function ($q) use ($campusKey) {
                $q->whereRaw('LOWER(TRIM(COALESCE(campus, ""))) = ?', [$campusKey])
                    ->orWhereRaw('TRIM(COALESCE(campus, "")) = ?', ['']);
            });
        }

        return $subjectsQuery
            ->distinct()
            ->pluck('section')
            ->map(fn ($section) => trim((string) $section))
            ->filter()
            ->unique()
            ->sort()
            ->values();
    }

    private function sectionsForClassSelection(Staff $staff, string $class, ?string $campus): \Illuminate\Support\Collection
    {
        $sections = $staff->assignedTeachingSectionsForClass($class, $campus);
        if ($sections->isNotEmpty()) {
            return $sections;
        }

        return $this->sectionsForClassAtCampus($class, $campus);
    }

    private function notifyStaffAboutCreatedTest(array $validated): void
    {
        $admin = Auth::guard('admin')->user();
        if (!$admin) {
            return;
        }

        $text = sprintf(
            '%s created test "%s" for %s - %s. Subject: %s. Campus: %s. Date: %s.',
            $admin->name ?? 'Admin',
            $validated['test_name'],
            $validated['for_class'],
            $validated['section'],
            $validated['subject'],
            $validated['campus'],
            $validated['date']
        );

        Staff::query()
            ->orderBy('name')
            ->get()
            ->filter(function (Staff $staff) use ($validated) {
                return $staff->isTeacher()
                    && method_exists($staff, 'canUploadMarksForSubject')
                    && $staff->canUploadMarksForSubject(
                        $validated['campus'],
                        $validated['for_class'],
                        $validated['section'],
                        $validated['subject']
                    );
            })
            ->each(function (Staff $staff) use ($admin, $text) {
                Message::create([
                    'from_type' => 'admin',
                    'from_id' => $admin->id,
                    'to_type' => 'teacher',
                    'to_id' => $staff->id,
                    'text' => $text,
                    'attachment_path' => null,
                    'attachment_type' => null,
                    'read_at' => null,
                ]);
            });
    }

    /**
     * Display a listing of tests.
     */
    public function index(Request $request): View
    {
        $query = Test::query();
        
        $staff = Auth::guard('staff')->user();
        $isStaffTestUser = Auth::guard('staff')->check();
        $staffDefaultCampus = $staff ? $this->staffCampusName($staff) : null;

        if ($isStaffTestUser && $staff) {
            $this->applyStaffTestListScope($query, $staff);
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

        $classes = collect();

        if ($isStaffTestUser && $staff) {
            $campuses = $this->campusesForStaffViewer($staff);
            $classes = collect();
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
        
        if ($isStaffTestUser && $staff) {
            $assignedSubjectsQuery = Subject::query();
            $staff->scopeQueryToTeacherAssignments($assignedSubjectsQuery);
            if ($staffDefaultCampus) {
                $staff->scopeQueryToFlexibleCampus($assignedSubjectsQuery, $staffDefaultCampus);
            }
            $assignedSubjectsQuery->whereNotNull('subject_name');

            if (!empty($existingClassNames)) {
                $assignedSubjectsQuery->where(function ($q) use ($existingClassNames) {
                    foreach ($existingClassNames as $className) {
                        $q->orWhereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($className))]);
                    }
                });
            } else {
                $assignedSubjectsQuery->whereRaw('1 = 0');
            }

            $subjectsMap = [];
            foreach ($assignedSubjectsQuery->get() as $subject) {
                $subjectName = trim($subject->subject_name ?? '');
                if ($subjectName !== '') {
                    $key = strtolower($subjectName);
                    $subjectsMap[$key] = $subjectName;
                }
            }

            if (!empty($subjectsMap)) {
                $subjects = collect(array_values($subjectsMap))->sort()->values();
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

        // Get sessions: Running Session from General Settings first, then sessions from tests
        $settings = GeneralSetting::getSettings();
        $runningSession = $settings->running_session ? trim($settings->running_session) : null;
        $sessionsFromTests = Test::whereNotNull('session')->distinct()->pluck('session')->sort()->values()->filter();
        $sessions = collect();
        if ($runningSession) {
            $sessions = $sessions->push($runningSession);
        }
        $sessions = $sessions->merge($sessionsFromTests)->unique()->values();
        if ($sessions->isEmpty() && $runningSession) {
            $sessions = collect([$runningSession]);
        }
        
        return view('test.list', compact(
            'tests',
            'campuses',
            'classes',
            'sections',
            'subjects',
            'testTypes',
            'sessions',
            'isStaffTestUser',
            'staff',
            'staffDefaultCampus'
        ));
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

        $staff = Auth::guard('staff')->user();
        if (Auth::guard('staff')->check() && $staff) {
            if (! $this->staffCanUseCampus($staff, $validated['campus'])) {
                return redirect()
                    ->route('test.list')
                    ->with('error', 'You can only add tests for your assigned campus.');
            }

            $allowed = $this->staffAssignableClasses($staff, $validated['campus']);
            $classKey = Staff::normalizeClassKey($validated['for_class']);
            $isAllowed = $allowed->contains(
                fn ($class) => Staff::normalizeClassKey((string) $class) === $classKey
            );
            if (!$isAllowed) {
                return redirect()
                    ->route('test.list')
                    ->with('error', 'You can only add tests for classes where you teach a subject (Manage Subjects).');
            }

            if (method_exists($staff, 'uploadableSubjectNamesForMarks')) {
                $allowedSubjects = $staff->uploadableSubjectNamesForMarks(
                    $validated['campus'],
                    $validated['for_class'],
                    $validated['section']
                );
                $subjectKey = strtolower(trim($validated['subject']));
                if (!$allowedSubjects->contains(fn ($name) => strtolower(trim((string) $name)) === $subjectKey)) {
                    return redirect()
                        ->route('test.list')
                        ->with('error', 'You can only add tests for subjects assigned to you in Manage Subjects.');
                }
            }
        }

        Test::create($validated);
        $this->notifyStaffAboutCreatedTest($validated);

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

        $staff = Auth::guard('staff')->user();
        if (Auth::guard('staff')->check() && $staff && ! $this->staffCanUseCampus($staff, $validated['campus'])) {
            return redirect()
                ->route('test.list')
                ->with('error', 'You can only update tests for your assigned campus.');
        }

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
     * Staff may only declare/reset results for subjects assigned to them (Manage Subjects).
     */
    public function toggleResultStatus(Request $request, Test $test): JsonResponse
    {
        $staff = Auth::guard('staff')->user();
        if (Auth::guard('staff')->check() && $staff && method_exists($staff, 'canDeclareResultForTest')) {
            if (!$staff->canDeclareResultForTest(
                $test->campus,
                $test->for_class,
                $test->section,
                $test->subject
            )) {
                return response()->json([
                    'success' => false,
                    'message' => 'You can only declare results for subjects assigned to you in Manage Subjects (your own subject only).',
                ], 403);
            }
        }

        $test->result_status = !$test->result_status;
        $test->save();

        return response()->json([
            'success' => true,
            'result_status' => $test->result_status,
            'message' => $test->result_status ? 'Result declared successfully!' : 'Result status reset successfully!'
        ]);
    }

    /**
     * Get classes by campus (AJAX). Only classes that exist in classes table for that campus (no deleted).
     */
    public function getClassesByCampus(Request $request): JsonResponse
    {
        $campus = $request->get('campus');
        if (!$campus || !is_string($campus)) {
            return response()->json(['classes' => []]);
        }
        $campus = trim($campus);

        $staff = Auth::guard('staff')->user();
        if ($staff) {
            if (! $this->staffCanUseCampus($staff, $campus)) {
                return response()->json(['classes' => []]);
            }

            $classes = $this->classesForCampusSelection($staff, $campus);

            return response()->json(['classes' => $classes->values()->all()]);
        }

        $classes = ClassModel::whereNotNull('class_name')
            ->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower($campus)])
            ->distinct()
            ->pluck('class_name')
            ->sort()
            ->values();

        return response()->json(['classes' => $classes]);
    }

    /**
     * Get sections for a class (AJAX).
     */
    public function getSections(Request $request): JsonResponse
    {
        $class = $request->get('class');
        $campus = $request->get('campus');

        if (!$class) {
            return response()->json(['sections' => []]);
        }

        $staff = Auth::guard('staff')->user();
        if ($staff) {
            if ($campus && ! $this->staffCanUseCampus($staff, $campus)) {
                return response()->json(['sections' => []]);
            }

            $sections = $this->sectionsForClassSelection($staff, $class, $campus);

            return response()->json(['sections' => $sections->values()->all()]);
        }

        $sections = $this->sectionsForClassAtCampus($class, $campus);
        
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

        $staff = Auth::guard('staff')->user();
        if ($staff && $campus && ! $this->staffCanUseCampus($staff, $campus)) {
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
        
        if ($staff && method_exists($staff, 'uploadableSubjectNamesForMarks')) {
            $subjects = $staff->uploadableSubjectNamesForMarks($campus, $class, $section);

            return response()->json(['subjects' => $subjects->values()->all()]);
        }

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

