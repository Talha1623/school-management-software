<?php

namespace App\Http\Controllers;

use App\Models\BehaviorRecord;
use App\Models\Campus;
use App\Models\ClassModel;
use App\Models\Section;
use App\Models\Student;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class ReportingAnalysisController extends Controller
{
    /**
     * Display the reporting and analysis page with filters only.
     */
    public function index(Request $request): View
    {
        $filterCampus = $request->get('campus');

        // Get campuses for dropdown - first from Campus model, then fallback
        $campuses = Campus::orderBy('campus_name', 'asc')->get();
        if ($campuses->isEmpty()) {
            $campusesFromClasses = ClassModel::whereNotNull('campus')->distinct()->pluck('campus');
            $campusesFromSections = Section::whereNotNull('campus')->distinct()->pluck('campus');
            $allCampuses = $campusesFromClasses->merge($campusesFromSections)->unique()->sort()->values();
            $campuses = $allCampuses->map(function($campus) {
                return (object)['campus_name' => $campus];
            });
        }

        // Get classes for filter form
        $classes = collect();
        $staff = Auth::guard('staff')->user();
        
        if ($staff && $staff->isTeacher()) {
            $assignedSubjects = Subject::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($staff->name ?? ''))]);
            if ($filterCampus) {
                $assignedSubjects->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($filterCampus))]);
            }
            $assignedSubjects = $assignedSubjects->get();

            $assignedSections = Section::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($staff->name ?? ''))]);
            if ($filterCampus) {
                $assignedSections->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($filterCampus))]);
            }
            $assignedSections = $assignedSections->get();
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
            $classesQuery = ClassModel::whereNotNull('class_name');
            if ($filterCampus) {
                $classesQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($filterCampus))]);
            }
            $classes = $classesQuery->distinct()->pluck('class_name')->sort()->values();
            if ($classes->isEmpty()) {
                $classesFromSubjects = Subject::whereNotNull('class');
                if ($filterCampus) {
                    $classesFromSubjects->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($filterCampus))]);
                }
                $classesFromSubjects = $classesFromSubjects->distinct()->pluck('class')->sort();
                $classes = $classesFromSubjects->isEmpty() ? collect(['Nursery', 'KG', '1st', '2nd', '3rd', '4th', '5th', '6th', '7th', '8th', '9th', '10th', '11th', '12th']) : $classesFromSubjects;
            }
        }

        // Get available years
        $years = BehaviorRecord::selectRaw('YEAR(date) as year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year')
            ->filter()
            ->values();
        
        if ($years->isEmpty()) {
            $years = collect([Carbon::now()->year]);
        }

        // Report types
        $reportTypes = [
            'summary' => 'Summary Report',
            'detailed' => 'Detailed Report',
            'monthly' => 'Monthly Report',
            'yearly' => 'Yearly Report',
            'type-wise' => 'Type-wise Report',
        ];

        return view('student-behavior.reporting-analysis', compact(
            'campuses',
            'classes',
            'years',
            'reportTypes',
            'filterCampus'
        ));
    }

    /**
     * Display filtered report on printable page.
     */
    public function report(Request $request): View
    {
        $campus = $request->get('campus');
        $class = $request->get('class');
        $section = $request->get('section');
        $reportType = $request->get('report_type', 'summary');
        $year = $request->get('year', Carbon::now()->year);

        // Build query for behavior records
        $query = BehaviorRecord::query();

        // Filter by class
        if ($class) {
            $query->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))]);
        }

        // Filter by section
        if ($section) {
            $query->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($section))]);
        }
        
        // Filter by campus
        if ($campus) {
            $query->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
        }

        // Filter by year
        if ($year) {
            $query->whereYear('date', $year);
        }

        // Get students based on class and section
        $studentsQuery = Student::query();
        if ($class) {
            $studentsQuery->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))]);
        }
        if ($section) {
            $studentsQuery->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($section))]);
        }
        if ($campus) {
            $studentsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
        }
        $students = $studentsQuery->get();

        $reportData = [];

        switch ($reportType) {
            case 'summary':
                $records = $query->get();
                $reportData = [
                    'total_records' => $records->count(),
                    'total_students' => $students->count(),
                    'total_points' => $records->sum('points'),
                    'positive_points' => $records->where('points', '>', 0)->sum('points'),
                    'negative_points' => $records->where('points', '<', 0)->sum('points'),
                    'type_wise_summary' => $records->groupBy('type')->map(function($group) {
                        return [
                            'type' => $group->first()->type,
                            'count' => $group->count(),
                            'total_points' => $group->sum('points'),
                        ];
                    })->values(),
                    'student_summary' => $students->map(function($student) use ($records) {
                        $studentRecords = $records->where('student_id', $student->id);
                        return [
                            'student_id' => $student->id,
                            'student_name' => $student->student_name,
                            'student_code' => $student->student_code,
                            'total_records' => $studentRecords->count(),
                            'total_points' => $studentRecords->sum('points'),
                        ];
                    })->sortByDesc('total_points')->values(),
                ];
                break;

            case 'detailed':
                $records = $query->with('student')
                    ->orderBy('date', 'desc')
                    ->orderBy('student_name', 'asc')
                    ->get();
                $reportData = [
                    'total_records' => $records->count(),
                    'records' => $records,
                ];
                break;

            case 'monthly':
                $records = $query->get();
                $monthlyData = $records->groupBy(function($record) {
                    return Carbon::parse($record->date)->format('Y-m');
                })->map(function($monthRecords, $month) {
                    return [
                        'month' => $month,
                        'month_formatted' => Carbon::parse($month . '-01')->format('F Y'),
                        'total_records' => $monthRecords->count(),
                        'total_points' => $monthRecords->sum('points'),
                        'positive_points' => $monthRecords->where('points', '>', 0)->sum('points'),
                        'negative_points' => $monthRecords->where('points', '<', 0)->sum('points'),
                    ];
                })->sortKeys()->values();
                $reportData = [
                    'total_records' => $records->count(),
                    'monthly_data' => $monthlyData,
                ];
                break;

            case 'yearly':
                $currentYearRecords = $query->whereYear('date', $year)->get();
                $previousYearRecords = BehaviorRecord::query()
                    ->whereYear('date', $year - 1);
                if ($class) {
                    $previousYearRecords->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))]);
                }
                if ($section) {
                    $previousYearRecords->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($section))]);
                }
                if ($campus) {
                    $previousYearRecords->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
                }
                $previousYearRecords = $previousYearRecords->get();
                $reportData = [
                    'current_year' => $year,
                    'previous_year' => $year - 1,
                    'current_year_data' => [
                        'total_records' => $currentYearRecords->count(),
                        'total_points' => $currentYearRecords->sum('points'),
                        'positive_points' => $currentYearRecords->where('points', '>', 0)->sum('points'),
                        'negative_points' => $currentYearRecords->where('points', '<', 0)->sum('points'),
                    ],
                    'previous_year_data' => [
                        'total_records' => $previousYearRecords->count(),
                        'total_points' => $previousYearRecords->sum('points'),
                        'positive_points' => $previousYearRecords->where('points', '>', 0)->sum('points'),
                        'negative_points' => $previousYearRecords->where('points', '<', 0)->sum('points'),
                    ],
                ];
                break;

            case 'type-wise':
                $records = $query->get();
                $typeWiseData = $records->groupBy('type')->map(function($typeRecords, $type) {
                    return [
                        'type' => $type,
                        'total_records' => $typeRecords->count(),
                        'total_points' => $typeRecords->sum('points'),
                        'average_points' => $typeRecords->avg('points'),
                        'students_count' => $typeRecords->pluck('student_id')->unique()->count(),
                    ];
                })->values();
                $reportData = [
                    'total_records' => $records->count(),
                    'type_wise_data' => $typeWiseData,
                ];
                break;

            default:
                $reportData = [];
        }

        return view('student-behavior.report-print', compact(
            'reportData',
            'reportType',
            'campus',
            'class',
            'section',
            'year'
        ));
    }

    /**
     * Get sections by class (AJAX endpoint)
     */
    public function getSectionsByClass(Request $request): JsonResponse
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
            $assignedSubjects = Subject::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($staff->name ?? ''))])
                ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))]);
            if ($campus) {
                $assignedSubjects->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
            }
            $assignedSubjects = $assignedSubjects->get();
            
            // Get sections from teacher's assigned sections for this class
            $assignedSections = Section::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($staff->name ?? ''))])
                ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))]);
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
            $sectionsQuery = Section::whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))]);
            if ($campus) {
                $sectionsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
            }
            $sections = $sectionsQuery
                ->whereNotNull('name')
                ->distinct()
                ->pluck('name')
                ->sort()
                ->values();
            
            if ($sections->isEmpty()) {
                $sections = Subject::whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))])
                    ->whereNotNull('section');
                if ($campus) {
                    $sections->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
                }
                $sections = $sections
                    ->distinct()
                    ->pluck('section')
                    ->sort()
                    ->values();
            }
        }

        return response()->json(['sections' => $sections]);
    }

    /**
     * Get report data based on filters (AJAX endpoint)
     */
    public function getReportData(Request $request): JsonResponse
    {
        try {
            $campus = $request->get('campus');
            $class = $request->get('class');
            $section = $request->get('section');
            $reportType = $request->get('report_type', 'summary');
            $year = $request->get('year', Carbon::now()->year);

            // Build query for behavior records
            $query = BehaviorRecord::query();

            // Filter by class
            if ($class) {
                $query->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))]);
            }

            // Filter by section
            if ($section) {
                $query->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($section))]);
            }
            if ($campus) {
                $query->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
            }

            // Filter by year
            if ($year) {
                $query->whereYear('date', $year);
            }

            // Get students based on class and section
            $studentsQuery = Student::query();
            if ($class) {
                $studentsQuery->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))]);
            }
            if ($section) {
                $studentsQuery->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($section))]);
            }
            if ($campus) {
                $studentsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
            }
            $students = $studentsQuery->get();

            $reportData = [];

            switch ($reportType) {
                case 'summary':
                    // Summary Report: Overall behavior summary for all students
                    $records = $query->get();
                    
                    $reportData = [
                        'total_records' => $records->count(),
                        'total_students' => $students->count(),
                        'total_points' => $records->sum('points'),
                        'positive_points' => $records->where('points', '>', 0)->sum('points'),
                        'negative_points' => $records->where('points', '<', 0)->sum('points'),
                        'type_wise_summary' => $records->groupBy('type')->map(function($group) {
                            return [
                                'type' => $group->first()->type,
                                'count' => $group->count(),
                                'total_points' => $group->sum('points'),
                            ];
                        })->values(),
                        'student_summary' => $students->map(function($student) use ($records) {
                            $studentRecords = $records->where('student_id', $student->id);
                            return [
                                'student_id' => $student->id,
                                'student_name' => $student->student_name,
                                'student_code' => $student->student_code,
                                'total_records' => $studentRecords->count(),
                                'total_points' => $studentRecords->sum('points'),
                            ];
                        })->sortByDesc('total_points')->values(),
                    ];
                    break;

                case 'detailed':
                    // Detailed Report: All behavior records
                    $records = $query->with('student')
                        ->orderBy('date', 'desc')
                        ->orderBy('student_name', 'asc')
                        ->get();
                    
                    $reportData = [
                        'total_records' => $records->count(),
                        'records' => $records->map(function($record) {
                            return [
                                'id' => $record->id,
                                'student_name' => $record->student_name,
                                'student_code' => $record->student ? $record->student->student_code : 'N/A',
                                'type' => $record->type,
                                'points' => $record->points,
                                'class' => $record->class,
                                'section' => $record->section,
                                'campus' => $record->campus,
                                'date' => $record->date->format('Y-m-d'),
                                'date_formatted' => $record->date->format('d M Y'),
                                'description' => $record->description,
                                'recorded_by' => $record->recorded_by,
                            ];
                        }),
                    ];
                    break;

                case 'monthly':
                    // Monthly Report: Behavior records grouped by month
                    $records = $query->get();
                    
                    $monthlyData = $records->groupBy(function($record) {
                        return Carbon::parse($record->date)->format('Y-m');
                    })->map(function($monthRecords, $month) {
                        return [
                            'month' => $month,
                            'month_formatted' => Carbon::parse($month . '-01')->format('F Y'),
                            'total_records' => $monthRecords->count(),
                            'total_points' => $monthRecords->sum('points'),
                            'positive_points' => $monthRecords->where('points', '>', 0)->sum('points'),
                            'negative_points' => $monthRecords->where('points', '<', 0)->sum('points'),
                        ];
                    })->sortKeys()->values();
                    
                    $reportData = [
                        'total_records' => $records->count(),
                        'monthly_data' => $monthlyData,
                    ];
                    break;

                case 'yearly':
                    // Yearly Report: Compare current year with previous year
                    $currentYearRecords = $query->whereYear('date', $year)->get();
                    $previousYearRecords = BehaviorRecord::query()
                        ->whereYear('date', $year - 1);
                    
                    if ($class) {
                        $previousYearRecords->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))]);
                    }
                    if ($section) {
                        $previousYearRecords->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($section))]);
                    }
                    if ($campus) {
                        $previousYearRecords->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
                    }
                    $previousYearRecords = $previousYearRecords->get();
                    
                    $reportData = [
                        'current_year' => $year,
                        'previous_year' => $year - 1,
                        'current_year_data' => [
                            'total_records' => $currentYearRecords->count(),
                            'total_points' => $currentYearRecords->sum('points'),
                            'positive_points' => $currentYearRecords->where('points', '>', 0)->sum('points'),
                            'negative_points' => $currentYearRecords->where('points', '<', 0)->sum('points'),
                        ],
                        'previous_year_data' => [
                            'total_records' => $previousYearRecords->count(),
                            'total_points' => $previousYearRecords->sum('points'),
                            'positive_points' => $previousYearRecords->where('points', '>', 0)->sum('points'),
                            'negative_points' => $previousYearRecords->where('points', '<', 0)->sum('points'),
                        ],
                    ];
                    break;

                case 'type-wise':
                    // Type-wise Report: Behavior records grouped by type
                    $records = $query->get();
                    
                    $typeWiseData = $records->groupBy('type')->map(function($typeRecords, $type) {
                        return [
                            'type' => $type,
                            'total_records' => $typeRecords->count(),
                            'total_points' => $typeRecords->sum('points'),
                            'average_points' => $typeRecords->avg('points'),
                            'students_count' => $typeRecords->pluck('student_id')->unique()->count(),
                        ];
                    })->values();
                    
                    $reportData = [
                        'total_records' => $records->count(),
                        'type_wise_data' => $typeWiseData,
                    ];
                    break;

                default:
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid report type'
                    ], 400);
            }

            return response()->json([
                'success' => true,
                'data' => $reportData,
                'filters' => [
                    'campus' => $campus,
                    'class' => $class,
                    'section' => $section,
                    'report_type' => $reportType,
                    'year' => $year,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error generating report: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get classes by campus (AJAX endpoint)
     */
    public function getClassesByCampus(Request $request): JsonResponse
    {
        $campus = $request->get('campus');
        $staff = Auth::guard('staff')->user();
        $classes = collect();

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
            $classesQuery = ClassModel::whereNotNull('class_name');
            if ($campus) {
                $classesQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
            }
            $classes = $classesQuery->distinct()->pluck('class_name')->sort()->values();

            if ($classes->isEmpty()) {
                $classesFromSubjects = Subject::whereNotNull('class');
                if ($campus) {
                    $classesFromSubjects->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
                }
                $classesFromSubjects = $classesFromSubjects->distinct()->pluck('class')->sort();
                $classes = $classesFromSubjects->isEmpty()
                    ? collect(['Nursery', 'KG', '1st', '2nd', '3rd', '4th', '5th', '6th', '7th', '8th', '9th', '10th', '11th', '12th'])
                    : $classesFromSubjects;
            }
        }

        $classes = $classes->map(function($class) {
            return trim((string) $class);
        })->filter(function($class) {
            return $class !== '';
        })->unique()->sort()->values();

        return response()->json(['classes' => $classes]);
    }
}

