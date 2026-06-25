<?php

namespace App\Http\Controllers;

use App\Models\Campus;
use App\Models\Test;
use App\Models\ClassModel;
use App\Models\Section;
use App\Models\Subject;
use App\Models\Staff;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;

class TestScheduleController extends Controller
{
    /**
     * Display the test schedule page with filters.
     */
    public function index(Request $request): View
    {
        $campuses = $this->resolveCampuses();
        $classes = collect();

        // Get test types
        $testTypes = Test::whereNotNull('test_type')->distinct()->pluck('test_type')->sort()->values();

        if ($testTypes->isEmpty()) {
            $testTypes = collect(['Quiz', 'Mid Term', 'Final Term', 'Assignment', 'Project', 'Oral Test', 'Daily Test', 'Weekly Test', 'Monthly Test']);
        } else {
            $defaultTypes = ['Daily Test', 'Weekly Test', 'Monthly Test'];
            $testTypes = $testTypes->merge($defaultTypes)->unique()->sort()->values();
        }

        $testsQuery = Test::query()
            ->when($request->filled('filter_campus'), function ($query) use ($request) {
                $this->applyCampusFilter($query, $request->get('filter_campus'));
            });

        $this->applyTeacherScope($testsQuery);

        $tests = $testsQuery->orderBy('date', 'asc')->get();

        return view('test.schedule', compact(
            'campuses',
            'classes',
            'testTypes',
            'tests'
        ));
    }

    /**
     * Get classes by campus (AJAX endpoint).
     */
    public function getClassesByCampus(Request $request): JsonResponse
    {
        $campus = trim((string) $request->get('campus', ''));
        if ($campus === '') {
            return response()->json(['classes' => []]);
        }

        $staff = Auth::guard('staff')->user();
        if ($staff && method_exists($staff, 'assignedSubjectClassNames')) {
            $classes = $staff->assignedSubjectClassNames($campus);

            return response()->json(['classes' => $classes->values()->all()]);
        }

        $classes = ClassModel::whereNotNull('class_name')
            ->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower($campus)])
            ->distinct()
            ->pluck('class_name')
            ->sort()
            ->values();

        if ($classes->isEmpty()) {
            $classes = Subject::whereNotNull('class')
                ->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower($campus)])
                ->distinct()
                ->pluck('class')
                ->sort()
                ->values();
        }

        return response()->json(['classes' => $classes]);
    }

    /**
     * Get sections by class (AJAX endpoint)
     */
    public function getSectionsByClass(Request $request): JsonResponse
    {
        $class = $request->get('class');
        $campus = trim((string) $request->get('campus', ''));

        if (!$class) {
            return response()->json(['sections' => []]);
        }

        $staff = Auth::guard('staff')->user();
        $sections = collect();

        if ($staff && $staff->isTeacher()) {
            $assignedSubjects = Subject::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($staff->name ?? ''))])
                ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))]);

            if ($campus !== '') {
                $assignedSubjects->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower($campus)]);
            }

            $assignedSubjects = $assignedSubjects->get();

            $assignedSections = Section::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($staff->name ?? ''))])
                ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))]);

            if ($campus !== '') {
                $assignedSections->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower($campus)]);
            }

            $assignedSections = $assignedSections->get();

            $sections = $assignedSubjects->pluck('section')
                ->merge($assignedSections->pluck('name'))
                ->map(fn ($section) => trim((string) $section))
                ->filter()
                ->unique()
                ->sort()
                ->values();
        } else {
            $sectionsQuery = Section::whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))])
                ->whereNotNull('name');

            if ($campus !== '') {
                $sectionsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower($campus)]);
            }

            $sections = $sectionsQuery->distinct()->pluck('name')->sort()->values();

            if ($sections->isEmpty()) {
                $subjectsQuery = Subject::whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))])
                    ->whereNotNull('section');

                if ($campus !== '') {
                    $subjectsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower($campus)]);
                }

                $sections = $subjectsQuery->distinct()->pluck('section')->sort()->values();
            }
        }

        return response()->json(['sections' => $sections]);
    }

    /**
     * Get filtered tests (AJAX endpoint)
     */
    public function getFilteredTests(Request $request): JsonResponse
    {
        try {
            $filterCampus = trim((string) $request->get('filter_campus', ''));
            $filterClass = $request->get('filter_class');
            $filterSection = $request->get('filter_section');
            $filterTestType = $request->get('filter_test_type');
            $filterFromDate = $request->get('filter_from_date');
            $filterToDate = $request->get('filter_to_date');

            $query = Test::query();

            if ($filterCampus !== '') {
                $this->applyCampusFilter($query, $filterCampus);
            }

            if ($filterClass) {
                $query->whereRaw('LOWER(TRIM(for_class)) = ?', [strtolower(trim($filterClass))]);
            }
            if ($filterSection) {
                $query->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($filterSection))]);
            }
            if ($filterTestType) {
                $query->where('test_type', $filterTestType);
            }
            if ($filterFromDate) {
                $query->whereDate('date', '>=', $filterFromDate);
            }
            if ($filterToDate) {
                $query->whereDate('date', '<=', $filterToDate);
            }

            $this->applyTeacherScope($query);

            $tests = $query->orderBy('date', 'asc')->get();

            $testsData = $tests->map(function ($test) {
                return [
                    'id' => $test->id,
                    'campus' => $test->campus,
                    'for_class' => $test->for_class,
                    'section' => $test->section,
                    'subject' => $test->subject,
                    'test_type' => $test->test_type,
                    'date' => $test->date ? date('d M Y', strtotime($test->date)) : 'N/A',
                    'date_raw' => $test->date ? $test->date->format('Y-m-d') : null,
                ];
            });

            return response()->json([
                'success' => true,
                'tests' => $testsData,
                'total' => $tests->count(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching tests: ' . $e->getMessage(),
            ], 500);
        }
    }

    private function resolveCampuses()
    {
        $staff = Auth::guard('staff')->user();

        if ($staff && $staff->isTeacher()) {
            $teacherCampuses = Subject::query();
            $staff->scopeQueryToTeacherAssignments($teacherCampuses);
            $teacherCampuses = $teacherCampuses
                ->whereNotNull('campus')
                ->distinct()
                ->pluck('campus')
                ->map(fn ($campus) => trim((string) $campus))
                ->filter()
                ->unique()
                ->sort()
                ->values();

            if ($teacherCampuses->isEmpty()) {
                return collect();
            }

            $campuses = Campus::orderBy('campus_name', 'asc')
                ->get()
                ->filter(fn ($campus) => $teacherCampuses->contains(strtolower(trim($campus->campus_name ?? ''))));

            if ($campuses->isEmpty()) {
                return $teacherCampuses->map(fn ($campus) => (object) ['campus_name' => $campus]);
            }

            return $campuses;
        }

        $campuses = Campus::orderBy('campus_name', 'asc')->get();
        if ($campuses->isNotEmpty()) {
            return $campuses;
        }

        $campusesFromClasses = ClassModel::whereNotNull('campus')->distinct()->pluck('campus');
        $campusesFromSections = Section::whereNotNull('campus')->distinct()->pluck('campus');
        $campusesFromSubjects = Subject::whereNotNull('campus')->distinct()->pluck('campus');
        $campusesFromTests = Test::whereNotNull('campus')->distinct()->pluck('campus');

        return $campusesFromClasses
            ->merge($campusesFromSections)
            ->merge($campusesFromSubjects)
            ->merge($campusesFromTests)
            ->unique()
            ->sort()
            ->values()
            ->map(fn ($campus) => (object) ['campus_name' => $campus]);
    }

    private function applyCampusFilter($query, string $campus): void
    {
        $query->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
    }

    private function applyTeacherScope($query): void
    {
        $staff = Auth::guard('staff')->user();
        if (!$staff || !$staff->isTeacher()) {
            return;
        }

        $allowedClasses = $staff->assignedSubjectClassNames();
        $assignedCampusesQuery = Subject::query();
        $staff->scopeQueryToTeacherAssignments($assignedCampusesQuery);
        $teacherCampuses = $assignedCampusesQuery
            ->whereNotNull('campus')
            ->distinct()
            ->pluck('campus')
            ->map(fn ($campus) => strtolower(trim((string) $campus)))
            ->filter()
            ->unique()
            ->values();

        if ($allowedClasses->isEmpty() || $teacherCampuses->isEmpty()) {
            $query->whereRaw('1 = 0');

            return;
        }

        $query->where(function ($classQuery) use ($allowedClasses) {
            foreach ($allowedClasses as $className) {
                $classKey = Staff::normalizeClassKey((string) $className);
                $classQuery->orWhereRaw('LOWER(TRIM(for_class)) = ?', [strtolower(trim((string) $className))])
                    ->orWhereRaw('LOWER(TRIM(for_class)) = ?', [$classKey])
                    ->orWhereRaw("LOWER(TRIM(REPLACE(REPLACE(for_class, 'Class ', ''), 'class ', ''))) = ?", [$classKey]);
            }
        });

        $query->where(function ($campusQuery) use ($teacherCampuses) {
            foreach ($teacherCampuses as $campusKey) {
                $campusQuery->orWhereRaw('LOWER(TRIM(campus)) = ?', [$campusKey]);
            }
        });
    }
}
