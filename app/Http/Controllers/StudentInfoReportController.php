<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Campus;
use App\Models\ClassModel;
use App\Models\Section;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class StudentInfoReportController extends Controller
{
    /**
     * Check if Student model uses soft deletes.
     */
    private function usesSoftDeletes(): bool
    {
        return in_array(
            \Illuminate\Database\Eloquent\SoftDeletes::class,
            class_uses_recursive(Student::class)
        );
    }

    /**
     * @return list<string>
     */
    private function passoutClassNames(): array
    {
        return [
            'passout',
            'pass out',
            'passed out',
            'passedout',
            'graduated',
            'graduate',
            'alumni',
        ];
    }

    private function isPassoutReportType(string $type): bool
    {
        return in_array($type, ['all-passout', 'monthly-passout', 'daily-passout'], true);
    }

    private function baseStudentQuery()
    {
        $query = Student::query();
        if ($this->usesSoftDeletes()) {
            $query->withoutTrashed();
        }

        return $query;
    }

    /**
     * Apply common filters for "current" students.
     */
    private function applyCurrentStudentsFilter($query)
    {
        if ($this->usesSoftDeletes()) {
            $query->withoutTrashed();
        }

        $query->whereNotNull('class')
            ->where('class', '!=', '');

        $passoutClasses = $this->passoutClassNames();
        $query->whereRaw("LOWER(TRIM(COALESCE(class, ''))) NOT IN ('" . implode("', '", array_map('strtolower', $passoutClasses)) . "')");

        return $query;
    }

    private function applyPassoutReportFilters($query, Request $request): void
    {
        if ($request->filled('filter_campus')) {
            $query->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($request->filter_campus))]);
        }

        if ($request->filled('filter_class')) {
            $query->whereRaw('LOWER(TRIM(COALESCE(previous_class, ""))) = ?', [strtolower(trim($request->filter_class))]);
        }

        if ($request->filled('filter_section')) {
            $query->whereRaw('LOWER(TRIM(COALESCE(previous_section, ""))) = ?', [strtolower(trim($request->filter_section))]);
        }
    }

    /**
     * Display the student info report page.
     */
    public function index(): View
    {
        // Calculate statistics for summary cards
        // Total Students (all students with class)
        $totalQuery = Student::query();
        if ($this->usesSoftDeletes()) {
            $totalQuery->withoutTrashed();
        }
        $totalStudents = $totalQuery->whereNotNull('class')
            ->where('class', '!=', '')
            ->count();

        // Male Students
        $maleQuery = Student::query();
        if ($this->usesSoftDeletes()) {
            $maleQuery->withoutTrashed();
        }
        $maleStudents = $maleQuery->whereNotNull('class')
            ->where('class', '!=', '')
            ->whereRaw('LOWER(TRIM(gender)) = ?', ['male'])
            ->count();

        // Female Students
        $femaleQuery = Student::query();
        if ($this->usesSoftDeletes()) {
            $femaleQuery->withoutTrashed();
        }
        $femaleStudents = $femaleQuery->whereNotNull('class')
            ->where('class', '!=', '')
            ->whereRaw('LOWER(TRIM(gender)) = ?', ['female'])
            ->count();

        // Pass-out Students
        $passoutQuery = $this->baseStudentQuery();
        $this->applyPassoutFilter($passoutQuery);
        $passoutStudents = $passoutQuery->count();

        return view('student.info-report', compact(
            'totalStudents',
            'maleStudents',
            'femaleStudents',
            'passoutStudents'
        ));
    }
    /**
     * Print student info report by type.
     */
    public function print(Request $request): View
    {
        $type = $request->get('type', 'all-active');
        $isPassoutReport = $this->isPassoutReportType($type);
        $query = $isPassoutReport
            ? $this->baseStudentQuery()
            : $this->applyCurrentStudentsFilter(Student::query());

        $title = 'Student Info Report';
        $subtitle = '';
        $grouped = false;
        $groupedStudents = collect();

        switch ($type) {
            case 'all-active':
                $title = 'All Active Students';
                $subtitle = 'List of all active students';
                $query->whereNotNull('admission_date');
                
                // Apply filters if provided
                if ($request->filled('filter_campus')) {
                    $query->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($request->filter_campus))]);
                }
                
                if ($request->filled('filter_class')) {
                    $query->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($request->filter_class))]);
                }
                
                if ($request->filled('filter_section')) {
                    $query->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($request->filter_section))]);
                }
                break;
            case 'all-inactive':
                $title = 'All Inactive Students';
                $subtitle = 'List of all inactive students';
                Student::ensureStatusColumn();
                $query->inactive();
                
                // Apply filters if provided
                if ($request->filled('filter_campus')) {
                    $query->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($request->filter_campus))]);
                }
                
                if ($request->filled('filter_class')) {
                    $query->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($request->filter_class))]);
                }
                
                if ($request->filled('filter_section')) {
                    $query->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($request->filter_section))]);
                }
                break;
            case 'class-wise':
                $title = 'Class Wise Student Report';
                $subtitle = 'Students grouped by class';
                $grouped = true;
                
                // Apply filters if provided
                $filterQuery = $this->applyCurrentStudentsFilter(Student::query());
                
                // Filter by campus
                if ($request->filled('filter_campus')) {
                    $filterQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($request->filter_campus))]);
                }
                
                // Filter by class
                if ($request->filled('filter_class')) {
                    $filterQuery->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($request->filter_class))]);
                }
                
                // Filter by section
                if ($request->filled('filter_section')) {
                    $filterQuery->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($request->filter_section))]);
                }
                
                $groupedStudents = $filterQuery
                    ->orderBy('class')
                    ->orderBy('section')
                    ->orderBy('student_name')
                    ->get()
                    ->groupBy(function ($student) {
                        return trim($student->class ?? 'N/A');
                    });
                break;
            case 'all-passout':
                $title = 'All Passout Students';
                $subtitle = 'List of all passout students';
                $this->applyPassoutFilter($query);
                $this->applyPassoutReportFilters($query, $request);
                break;
            case 'free-students':
                $title = 'Free Students Report';
                $subtitle = 'Students with free fees or discounted students';
                $query->where(function ($q) {
                    $q->where('discounted_student', true)
                      ->orWhere('monthly_fee', '<=', 0);
                });
                
                // Apply filters if provided
                if ($request->filled('filter_campus')) {
                    $query->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($request->filter_campus))]);
                }
                
                if ($request->filled('filter_class')) {
                    $query->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($request->filter_class))]);
                }
                
                if ($request->filled('filter_section')) {
                    $query->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($request->filter_section))]);
                }
                break;
            case 'monthly-passout':
                $title = 'Monthly Passout Students Report';
                $subtitle = 'Passout students for current month';
                $this->applyPassoutFilter($query);
                $query->whereMonth('updated_at', Carbon::now()->month)
                    ->whereYear('updated_at', Carbon::now()->year);
                $this->applyPassoutReportFilters($query, $request);
                break;
            case 'daily-passout':
                $title = 'Daily Passout Students Report';
                $subtitle = 'Passout students for today';
                $this->applyPassoutFilter($query);
                $query->whereDate('updated_at', Carbon::today());
                $this->applyPassoutReportFilters($query, $request);
                break;
            case 'gender-wise':
                $title = 'Gender Wise Student Report';
                $subtitle = 'Students grouped by gender';
                $grouped = true;
                
                // Apply filters if provided
                $filterQuery = $this->applyCurrentStudentsFilter(Student::query());
                
                // Filter by campus
                if ($request->filled('filter_campus')) {
                    $filterQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($request->filter_campus))]);
                }
                
                // Filter by class
                if ($request->filled('filter_class')) {
                    $filterQuery->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($request->filter_class))]);
                }
                
                // Filter by section
                if ($request->filled('filter_section')) {
                    $filterQuery->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($request->filter_section))]);
                }
                
                $groupedStudents = $filterQuery
                    ->orderBy('gender')
                    ->orderBy('student_name')
                    ->get()
                    ->groupBy(function ($student) {
                        return ucfirst($student->gender ?? 'N/A');
                    });
                break;
            default:
                $title = 'All Active Students';
                $subtitle = 'List of all active students';
                $query->whereNotNull('admission_date');
                break;
        }

        if (!$grouped) {
            if ($isPassoutReport) {
                $query->orderBy('previous_class')
                    ->orderBy('previous_section');
            } else {
                $query->orderBy('class')
                    ->orderBy('section');
            }
            $query->orderBy('student_name');
        }

        $students = $grouped ? collect() : $query->get();

        return view('student.info-report-print', [
            'type' => $type,
            'title' => $title,
            'subtitle' => $subtitle,
            'students' => $students,
            'grouped' => $grouped,
            'groupedStudents' => $groupedStudents,
            'printedAt' => Carbon::now()->format('d-m-Y H:i'),
        ]);
    }

    /**
     * Apply passout filter based on class value.
     * Adjust this list if your passout marker is stored differently.
     */
    private function applyPassoutFilter($query): void
    {
        $passoutClasses = $this->passoutClassNames();

        if (Schema::hasColumn('students', 'class')) {
            $query->whereIn(DB::raw('LOWER(TRIM(class))'), $passoutClasses);
        } else {
            $query->whereRaw('1 = 0');
        }
    }

    /**
     * Show filter page for Class Wise Student Report.
     */
    public function filterClassWise(Request $request): View
    {
        // Get campuses
        $campuses = Campus::orderBy('campus_name', 'asc')->get();
        if ($campuses->isEmpty()) {
            $campusesFromClasses = ClassModel::whereNotNull('campus')
                ->distinct()
                ->pluck('campus')
                ->sort()
                ->values();
            
            $campusesFromSections = Section::whereNotNull('campus')
                ->distinct()
                ->pluck('campus')
                ->sort()
                ->values();
            
            $allCampuses = $campusesFromClasses->merge($campusesFromSections)->unique()->sort()->values();
            
            $campuses = collect();
            foreach ($allCampuses as $campusName) {
                $campuses->push((object)['campus_name' => $campusName]);
            }
        }

        // Classes and sections will be loaded dynamically via AJAX
        return view('student.class-wise-filter', compact('campuses'));
    }

    /**
     * Show filter page for All Active Students Report.
     */
    public function filterAllActive(Request $request): View
    {
        // Get campuses
        $campuses = Campus::orderBy('campus_name', 'asc')->get();
        if ($campuses->isEmpty()) {
            $campusesFromClasses = ClassModel::whereNotNull('campus')
                ->distinct()
                ->pluck('campus')
                ->sort()
                ->values();
            
            $campusesFromSections = Section::whereNotNull('campus')
                ->distinct()
                ->pluck('campus')
                ->sort()
                ->values();
            
            $allCampuses = $campusesFromClasses->merge($campusesFromSections)->unique()->sort()->values();
            
            $campuses = collect();
            foreach ($allCampuses as $campusName) {
                $campuses->push((object)['campus_name' => $campusName]);
            }
        }

        // Classes and sections will be loaded dynamically via AJAX
        return view('student.all-active-filter', compact('campuses'));
    }

    /**
     * Show filter page for All Inactive Students Report.
     */
    public function filterAllInactive(Request $request): View
    {
        // Get campuses
        $campuses = Campus::orderBy('campus_name', 'asc')->get();
        if ($campuses->isEmpty()) {
            $campusesFromClasses = ClassModel::whereNotNull('campus')
                ->distinct()
                ->pluck('campus')
                ->sort()
                ->values();
            
            $campusesFromSections = Section::whereNotNull('campus')
                ->distinct()
                ->pluck('campus')
                ->sort()
                ->values();
            
            $allCampuses = $campusesFromClasses->merge($campusesFromSections)->unique()->sort()->values();
            
            $campuses = collect();
            foreach ($allCampuses as $campusName) {
                $campuses->push((object)['campus_name' => $campusName]);
            }
        }

        // Classes and sections will be loaded dynamically via AJAX
        return view('student.all-inactive-filter', compact('campuses'));
    }

    /**
     * Show filter page for All Passout Students Report.
     */
    public function filterAllPassout(Request $request): View
    {
        // Get campuses
        $campuses = Campus::orderBy('campus_name', 'asc')->get();
        if ($campuses->isEmpty()) {
            $campusesFromClasses = ClassModel::whereNotNull('campus')
                ->distinct()
                ->pluck('campus')
                ->sort()
                ->values();
            
            $campusesFromSections = Section::whereNotNull('campus')
                ->distinct()
                ->pluck('campus')
                ->sort()
                ->values();
            
            $allCampuses = $campusesFromClasses->merge($campusesFromSections)->unique()->sort()->values();
            
            $campuses = collect();
            foreach ($allCampuses as $campusName) {
                $campuses->push((object)['campus_name' => $campusName]);
            }
        }

        // Classes and sections will be loaded dynamically via AJAX
        return view('student.all-passout-filter', compact('campuses'));
    }

    /**
     * Show filter page for Free Students Report.
     */
    public function filterFreeStudents(Request $request): View
    {
        // Get campuses
        $campuses = Campus::orderBy('campus_name', 'asc')->get();
        if ($campuses->isEmpty()) {
            $campusesFromClasses = ClassModel::whereNotNull('campus')
                ->distinct()
                ->pluck('campus')
                ->sort()
                ->values();
            
            $campusesFromSections = Section::whereNotNull('campus')
                ->distinct()
                ->pluck('campus')
                ->sort()
                ->values();
            
            $allCampuses = $campusesFromClasses->merge($campusesFromSections)->unique()->sort()->values();
            
            $campuses = collect();
            foreach ($allCampuses as $campusName) {
                $campuses->push((object)['campus_name' => $campusName]);
            }
        }

        // Classes and sections will be loaded dynamically via AJAX
        return view('student.free-students-filter', compact('campuses'));
    }

    /**
     * Show filter page for Monthly Passout Students Report.
     */
    public function filterMonthlyPassout(Request $request): View
    {
        // Get campuses
        $campuses = Campus::orderBy('campus_name', 'asc')->get();
        if ($campuses->isEmpty()) {
            $campusesFromClasses = ClassModel::whereNotNull('campus')
                ->distinct()
                ->pluck('campus')
                ->sort()
                ->values();
            
            $campusesFromSections = Section::whereNotNull('campus')
                ->distinct()
                ->pluck('campus')
                ->sort()
                ->values();
            
            $allCampuses = $campusesFromClasses->merge($campusesFromSections)->unique()->sort()->values();
            
            $campuses = collect();
            foreach ($allCampuses as $campusName) {
                $campuses->push((object)['campus_name' => $campusName]);
            }
        }

        // Classes and sections will be loaded dynamically via AJAX
        return view('student.monthly-passout-filter', compact('campuses'));
    }

    /**
     * Show filter page for Daily Passout Students Report.
     */
    public function filterDailyPassout(Request $request): View
    {
        // Get campuses
        $campuses = Campus::orderBy('campus_name', 'asc')->get();
        if ($campuses->isEmpty()) {
            $campusesFromClasses = ClassModel::whereNotNull('campus')
                ->distinct()
                ->pluck('campus')
                ->sort()
                ->values();
            
            $campusesFromSections = Section::whereNotNull('campus')
                ->distinct()
                ->pluck('campus')
                ->sort()
                ->values();
            
            $allCampuses = $campusesFromClasses->merge($campusesFromSections)->unique()->sort()->values();
            
            $campuses = collect();
            foreach ($allCampuses as $campusName) {
                $campuses->push((object)['campus_name' => $campusName]);
            }
        }

        // Classes and sections will be loaded dynamically via AJAX
        return view('student.daily-passout-filter', compact('campuses'));
    }

    /**
     * Show filter page for Gender Wise Student Report.
     */
    public function filterGenderWise(Request $request): View
    {
        // Get campuses
        $campuses = Campus::orderBy('campus_name', 'asc')->get();
        if ($campuses->isEmpty()) {
            $campusesFromClasses = ClassModel::whereNotNull('campus')
                ->distinct()
                ->pluck('campus')
                ->sort()
                ->values();
            
            $campusesFromSections = Section::whereNotNull('campus')
                ->distinct()
                ->pluck('campus')
                ->sort()
                ->values();
            
            $allCampuses = $campusesFromClasses->merge($campusesFromSections)->unique()->sort()->values();
            
            $campuses = collect();
            foreach ($allCampuses as $campusName) {
                $campuses->push((object)['campus_name' => $campusName]);
            }
        }

        // Classes and sections will be loaded dynamically via AJAX
        return view('student.gender-wise-filter', compact('campuses'));
    }

    /**
     * Get classes by campus (AJAX).
     */
    public function getClassesByCampus(Request $request): JsonResponse
    {
        $campus = $request->get('campus');

        if ($request->get('report') === 'passout') {
            $query = $this->baseStudentQuery();
            $this->applyPassoutFilter($query);

            if ($campus) {
                $query->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
            }

            $classes = $query->whereNotNull('previous_class')
                ->where('previous_class', '!=', '')
                ->distinct()
                ->pluck('previous_class')
                ->map(fn ($className) => trim((string) $className))
                ->filter()
                ->unique()
                ->sort()
                ->values();

            return response()->json(['classes' => $classes]);
        }
        
        $classesQuery = ClassModel::whereNotNull('class_name');
        if ($campus) {
            $classesQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
        }
        
        $classes = $classesQuery->distinct()
            ->pluck('class_name')
            ->map(function ($className) {
                return trim((string) $className);
            })
            ->filter(function ($className) {
                return $className !== '';
            })
            ->unique()
            ->sort()
            ->values();

        return response()->json(['classes' => $classes]);
    }

    /**
     * Get sections by class and campus (AJAX).
     */
    public function getSectionsByClass(Request $request): JsonResponse
    {
        $class = $request->get('class');
        $campus = $request->get('campus');
        
        if (!$class) {
            return response()->json(['sections' => []]);
        }

        if ($request->get('report') === 'passout') {
            $query = $this->baseStudentQuery();
            $this->applyPassoutFilter($query);
            $query->whereRaw('LOWER(TRIM(COALESCE(previous_class, ""))) = ?', [strtolower(trim($class))]);

            if ($campus) {
                $query->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
            }

            $sections = $query->whereNotNull('previous_section')
                ->where('previous_section', '!=', '')
                ->distinct()
                ->pluck('previous_section')
                ->map(fn ($sectionName) => trim((string) $sectionName))
                ->filter()
                ->unique()
                ->sort()
                ->values();

            return response()->json(['sections' => $sections]);
        }
        
        $sectionsQuery = Section::whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))])
            ->whereNotNull('name');
        
        if ($campus) {
            $sectionsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
        }
        
        $sections = $sectionsQuery->distinct()
            ->pluck('name')
            ->map(function ($sectionName) {
                return trim((string) $sectionName);
            })
            ->filter(function ($sectionName) {
                return $sectionName !== '';
            })
            ->unique()
            ->sort()
            ->values();

        return response()->json(['sections' => $sections]);
    }
}
