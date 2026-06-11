<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\ClassModel;
use App\Models\Campus;
use App\Models\Section;
use App\Models\Subject;
use App\Models\FeeType;
use App\Services\FeePaymentWebTables;
use App\Models\GeneralSetting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class HeadWiseDuesController extends Controller
{
    /**
     * Display the head wise dues summary report.
     */
    public function index(Request $request): View
    {
        $filterCampus = $request->get('filter_campus');
        $filterClass = $request->get('filter_class');
        $filterSection = $request->get('filter_section');
        $hasFilters = $request->filled('filter_campus') || $request->filled('filter_class') || $request->filled('filter_section');

        // Get all campuses
        $campuses = Campus::orderBy('campus_name', 'asc')->get();
        if ($campuses->isEmpty()) {
            $campusesFromClasses = ClassModel::whereNotNull('campus')->distinct()->pluck('campus');
            $allCampuses = $campusesFromClasses->unique()->sort();
            $campuses = $allCampuses->map(function($campus) {
                return (object)['campus_name' => $campus];
            });
        }

        // Build class options for filter (scoped to campus if selected)
        $classOptions = ClassModel::whereNotNull('class_name');
        if ($filterCampus) {
            $classOptions->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($filterCampus))]);
        }
        $classOptions = $classOptions->distinct()->pluck('class_name')->sort()->values();
        if ($classOptions->isEmpty()) {
            $classOptionsFromStudents = Student::whereNotNull('class');
            if ($filterCampus) {
                $classOptionsFromStudents->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($filterCampus))]);
            }
            $classOptionsFromStudents = $classOptionsFromStudents->distinct()->pluck('class')->sort()->values();
            $classOptions = $classOptionsFromStudents->isEmpty()
                ? collect(['Nursery', 'KG', '1st', '2nd', '3rd', '4th', '5th', '6th', '7th', '8th', '9th', '10th', '11th', '12th'])
                : $classOptionsFromStudents;
        }

        $sectionOptions = Section::whereNotNull('name');
        if ($filterCampus) {
            $sectionOptions->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($filterCampus))]);
        }
        if ($filterClass) {
            $sectionOptions->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($filterClass))]);
        }
        $sectionOptions = $sectionOptions->distinct()->pluck('name')->sort()->values();
        if ($sectionOptions->isEmpty()) {
            $sectionsFromSubjects = Subject::whereNotNull('section');
            if ($filterCampus) {
                $sectionsFromSubjects->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($filterCampus))]);
            }
            if ($filterClass) {
                $sectionsFromSubjects->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($filterClass))]);
            }
            $sectionOptions = $sectionsFromSubjects->distinct()->pluck('section')->sort()->values();
        }
        
        $report = $this->buildReportData($campuses, $filterCampus, $filterClass, $filterSection, $hasFilters);
        $allCampusData = $report['allCampusData'];
        $feeHeads = $report['feeHeads'];
        $grandTotal = $report['grandTotal'];

        return view('reports.head-wise-dues', compact(
            'allCampusData',
            'grandTotal',
            'campuses',
            'classOptions',
            'sectionOptions',
            'feeHeads',
            'filterCampus',
            'filterClass',
            'filterSection',
            'hasFilters'
        ));
    }

    /**
     * Get classes by campus (AJAX endpoint)
     */
    public function getClassesByCampus(Request $request): \Illuminate\Http\JsonResponse
    {
        $campus = $request->get('campus');

        $classesQuery = ClassModel::whereNotNull('class_name');
        if ($campus) {
            $classesQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
        }
        $classes = $classesQuery->distinct()->pluck('class_name')->sort()->values();

        if ($classes->isEmpty()) {
            $classesFromStudents = Student::whereNotNull('class');
            if ($campus) {
                $classesFromStudents->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
            }
            $classesFromStudents = $classesFromStudents->distinct()->pluck('class')->sort()->values();
            $classes = $classesFromStudents->isEmpty()
                ? collect(['Nursery', 'KG', '1st', '2nd', '3rd', '4th', '5th', '6th', '7th', '8th', '9th', '10th', '11th', '12th'])
                : $classesFromStudents;
        }

        $classes = $classes->map(function($class) {
            return trim((string) $class);
        })->filter(function($class) {
            return $class !== '';
        })->unique()->sort()->values();

        return response()->json(['classes' => $classes]);
    }

    /**
     * Get sections by class (AJAX endpoint)
     */
    public function getSectionsByClass(Request $request): \Illuminate\Http\JsonResponse
    {
        $class = $request->get('class');
        $campus = $request->get('campus');

        if (!$class) {
            return response()->json(['sections' => []]);
        }

        $sectionsQuery = Section::whereNotNull('name')
            ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))]);
        if ($campus) {
            $sectionsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
        }
        $sections = $sectionsQuery->distinct()->pluck('name')->sort()->values();

        if ($sections->isEmpty()) {
            $sectionsFromSubjects = Subject::whereNotNull('section')
                ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))]);
            if ($campus) {
                $sectionsFromSubjects->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
            }
            $sections = $sectionsFromSubjects->distinct()->pluck('section')->sort()->values();
        }

        return response()->json(['sections' => $sections]);
    }

    /**
     * Print head wise dues in dedicated print layout.
     */
    public function print(Request $request): View|RedirectResponse
    {
        $filterCampus = $request->get('filter_campus');
        $filterClass = $request->get('filter_class');
        $filterSection = $request->get('filter_section');
        $hasFilters = $request->filled('filter_campus') || $request->filled('filter_class') || $request->filled('filter_section');

        if (!$hasFilters) {
            return redirect()
                ->route('reports.head-wise-dues')
                ->with('error', 'Please apply at least one filter (Campus, Class, or Section) before printing.');
        }

        $campuses = $this->getCampusesList();
        $report = $this->buildReportData($campuses, $filterCampus, $filterClass, $filterSection, $hasFilters);

        return view('reports.head-wise-dues-print', [
            'allCampusData' => $report['allCampusData'],
            'feeHeads' => $report['feeHeads'],
            'grandTotal' => $report['grandTotal'],
            'filterCampus' => $filterCampus,
            'filterClass' => $filterClass,
            'filterSection' => $filterSection,
            'filterDescription' => $this->buildFilterDescription($filterCampus, $filterClass, $filterSection),
            'settings' => GeneralSetting::getSettings(),
            'printedAt' => now()->format('d M Y, h:i A'),
        ]);
    }

    /**
     * Export head wise dues (csv, excel, pdf).
     */
    public function export(Request $request, string $format): RedirectResponse|StreamedResponse|\Illuminate\Http\Response
    {
        $format = strtolower(trim($format));
        if (!in_array($format, ['csv', 'excel', 'pdf'], true)) {
            abort(404);
        }

        $filterCampus = $request->get('filter_campus');
        $filterClass = $request->get('filter_class');
        $filterSection = $request->get('filter_section');
        $hasFilters = $request->filled('filter_campus') || $request->filled('filter_class') || $request->filled('filter_section');

        if (!$hasFilters) {
            return redirect()
                ->route('reports.head-wise-dues')
                ->with('error', 'Please apply at least one filter (Campus, Class, or Section) before exporting.');
        }

        $campuses = $this->getCampusesList();
        $report = $this->buildReportData($campuses, $filterCampus, $filterClass, $filterSection, $hasFilters);
        $allCampusData = $report['allCampusData'];
        $feeHeads = $report['feeHeads'];
        $grandTotal = $report['grandTotal'];
        $filterDescription = $this->buildFilterDescription($filterCampus, $filterClass, $filterSection);
        $filenameDate = now()->format('Y-m-d_H-i');

        if ($format === 'csv') {
            $headers = [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="head-wise-dues-' . $filenameDate . '.csv"',
            ];

            $callback = function () use ($allCampusData, $feeHeads, $grandTotal, $filterDescription) {
                $stream = fopen('php://output', 'w');
                fprintf($stream, chr(0xEF) . chr(0xBB) . chr(0xBF));
                fputcsv($stream, ['Filters: ' . $filterDescription]);

                $csvHeaders = ['Campus', 'Class'];
                foreach ($feeHeads as $head) {
                    $csvHeaders[] = $head . ' Paid';
                    $csvHeaders[] = $head . ' Due';
                }
                $csvHeaders[] = 'Total Paid';
                $csvHeaders[] = 'Total Due';
                fputcsv($stream, $csvHeaders);

                foreach ($allCampusData as $campusData) {
                    foreach ($campusData['rows'] as $row) {
                        $line = [
                            $campusData['campus'],
                            $row['class'],
                        ];
                        foreach ($feeHeads as $head) {
                            $headData = $row['heads'][$head] ?? ['paid' => 0, 'due' => 0];
                            $line[] = number_format((float) ($headData['paid'] ?? 0), 2, '.', '');
                            $line[] = number_format((float) ($headData['due'] ?? 0), 2, '.', '');
                        }
                        $line[] = number_format((float) ($row['total_paid'] ?? 0), 2, '.', '');
                        $line[] = number_format((float) ($row['total'] ?? 0), 2, '.', '');
                        fputcsv($stream, $line);
                    }
                    $summaryPaid = [$campusData['campus'], 'Total Paid'];
                    $summaryDue = [$campusData['campus'], 'Total Due'];
                    foreach ($feeHeads as $head) {
                        $summaryPaid[] = number_format((float) ($campusData['head_paid_totals'][$head] ?? 0), 2, '.', '');
                        $summaryDue[] = number_format((float) ($campusData['head_totals'][$head] ?? 0), 2, '.', '');
                    }
                    $summaryPaid[] = number_format((float) ($campusData['total_paid'] ?? 0), 2, '.', '');
                    $summaryPaid[] = '';
                    $summaryDue[] = '';
                    $summaryDue[] = number_format((float) ($campusData['total'] ?? 0), 2, '.', '');
                    fputcsv($stream, $summaryPaid);
                    fputcsv($stream, $summaryDue);
                }

                if ($allCampusData->count() > 1) {
                    $grandPaid = ['GRAND TOTAL', 'Total Paid'];
                    $grandDue = ['GRAND TOTAL', 'Total Due'];
                    foreach ($feeHeads as $head) {
                        $grandPaid[] = number_format((float) ($grandTotal['heads_paid'][$head] ?? 0), 2, '.', '');
                        $grandDue[] = number_format((float) ($grandTotal['heads'][$head] ?? 0), 2, '.', '');
                    }
                    $grandPaid[] = number_format((float) ($grandTotal['total_paid'] ?? 0), 2, '.', '');
                    $grandPaid[] = '';
                    $grandDue[] = '';
                    $grandDue[] = number_format((float) ($grandTotal['total'] ?? 0), 2, '.', '');
                    fputcsv($stream, $grandPaid);
                    fputcsv($stream, $grandDue);
                }

                fclose($stream);
            };

            return response()->stream($callback, 200, $headers);
        }

        $rows = $this->buildExportRows($allCampusData, $feeHeads, $grandTotal);
        $html = view('reports.head-wise-dues-export-excel', [
            'rows' => $rows,
            'feeHeads' => $feeHeads,
            'filterDescription' => $filterDescription,
        ])->render();

        if ($format === 'excel') {
            return response($html, 200, [
                'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="head-wise-dues-' . $filenameDate . '.xls"',
            ]);
        }

        $settings = GeneralSetting::getSettings();
        $schoolName = trim((string) ($settings->school_name ?? $settings->system_name ?? config('app.name', 'School Management System')));
        $schoolEmail = trim((string) ($settings->school_email ?? ''));
        $schoolAddress = trim((string) ($settings->address ?? ''));
        $schoolPhone = trim((string) ($settings->school_phone ?? ''));

        $pdf = Pdf::loadView('reports.head-wise-dues-pdf', [
            'allCampusData' => $allCampusData,
            'feeHeads' => $feeHeads,
            'grandTotal' => $grandTotal,
            'filterDescription' => $filterDescription,
            'schoolName' => $schoolName,
            'schoolEmail' => $schoolEmail,
            'schoolAddress' => $schoolAddress,
            'schoolPhone' => $schoolPhone,
            'schoolLogoUrl' => $this->resolveSchoolLogoUrl($settings->logo ?? null),
            'printedAt' => now()->format('d-m-Y H:i'),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('head-wise-dues-' . $filenameDate . '.pdf');
    }

    private function getCampusesList()
    {
        $campuses = Campus::orderBy('campus_name', 'asc')->get();
        if ($campuses->isEmpty()) {
            $campusesFromClasses = ClassModel::whereNotNull('campus')->distinct()->pluck('campus');
            $campuses = $campusesFromClasses->unique()->sort()->map(function ($campus) {
                return (object) ['campus_name' => $campus];
            });
        }

        return $campuses;
    }

    private function buildReportData($campuses, ?string $filterCampus, ?string $filterClass, ?string $filterSection, bool $hasFilters): array
    {
        $allCampusData = collect();
        $feeHeads = collect();
        $grandTotal = [
            'heads' => [],
            'heads_paid' => [],
            'total' => 0,
            'total_paid' => 0,
        ];

        if (!$hasFilters) {
            return compact('allCampusData', 'feeHeads', 'grandTotal');
        }

        $campusesToProcess = $campuses;
        if ($filterCampus) {
            $campusesToProcess = collect([(object) ['campus_name' => trim((string) $filterCampus)]]);
        }

        $feeHeads = collect();
        foreach ($campusesToProcess as $campus) {
            $campusName = is_object($campus) ? ($campus->campus_name ?? '') : $campus;
            if ($campusName !== '') {
                $feeHeads = $feeHeads->merge($this->buildFeeHeads((string) $campusName));
            }
        }
        $feeHeads = $feeHeads
            ->unique(fn ($head) => strtolower(trim((string) $head)))
            ->values();

        foreach ($campusesToProcess as $campus) {
            $campusName = is_object($campus) ? ($campus->campus_name ?? '') : $campus;
            if (empty($campusName)) {
                continue;
            }

            $classes = ClassModel::whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campusName))])
                ->whereNotNull('class_name')
                ->distinct()
                ->pluck('class_name')
                ->sort()
                ->values();

            if ($classes->isEmpty()) {
                $classes = Student::whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campusName))])
                    ->whereNotNull('class')
                    ->distinct()
                    ->pluck('class')
                    ->sort()
                    ->values();
            }

            if ($filterClass) {
                $classes = $classes->filter(function ($className) use ($filterClass) {
                    return strtolower(trim($className)) === strtolower(trim($filterClass));
                })->values();
            }

            $campusFeeHeads = $this->buildFeeHeads($campusName);
            if ($campusFeeHeads->isEmpty()) {
                continue;
            }

            $headWiseData = collect();
            $campusHeadTotals = [];
            $campusHeadPaidTotals = [];
            foreach ($campusFeeHeads as $head) {
                $campusHeadTotals[$head] = 0;
                $campusHeadPaidTotals[$head] = 0;
            }
            $campusTotalDue = 0;
            $campusTotalPaid = 0;

            foreach ($classes as $className) {
                $students = Student::whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campusName))])
                    ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim((string) $className))])
                    ->when($filterSection, function ($query) use ($filterSection) {
                        $query->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim((string) $filterSection))]);
                    })
                    ->get();

                $classHeadTotals = [];
                $classHeadPaidTotals = [];
                foreach ($campusFeeHeads as $head) {
                    $classHeadTotals[$head] = 0;
                    $classHeadPaidTotals[$head] = 0;
                }

                foreach ($students as $student) {
                    $headAmounts = $this->calculateStudentHeadAmounts($student, $campusFeeHeads);

                    foreach ($campusFeeHeads as $head) {
                        $classHeadTotals[$head] += $headAmounts[$head]['due'] ?? 0;
                        $classHeadPaidTotals[$head] += $headAmounts[$head]['paid'] ?? 0;
                    }
                }

                $classTotalDue = collect($classHeadTotals)->sum();
                $classTotalPaid = collect($classHeadPaidTotals)->sum();

                foreach ($campusFeeHeads as $head) {
                    $campusHeadTotals[$head] += $classHeadTotals[$head];
                    $campusHeadPaidTotals[$head] += $classHeadPaidTotals[$head];
                }
                $campusTotalDue += $classTotalDue;
                $campusTotalPaid += $classTotalPaid;

                $classHeads = [];
                foreach ($campusFeeHeads as $head) {
                    $classHeads[$head] = [
                        'paid' => $classHeadPaidTotals[$head],
                        'due' => $classHeadTotals[$head],
                    ];
                }

                $headWiseData->push([
                    'class' => $className,
                    'heads' => $classHeads,
                    'total_paid' => $classTotalPaid,
                    'total' => $classTotalDue,
                ]);
            }

            foreach ($campusFeeHeads as $head) {
                $grandTotal['heads'][$head] = ($grandTotal['heads'][$head] ?? 0) + ($campusHeadTotals[$head] ?? 0);
                $grandTotal['heads_paid'][$head] = ($grandTotal['heads_paid'][$head] ?? 0) + ($campusHeadPaidTotals[$head] ?? 0);
            }
            $grandTotal['total'] += $campusTotalDue;
            $grandTotal['total_paid'] += $campusTotalPaid;

            if ($headWiseData->count() > 0) {
                $allCampusData->push([
                    'campus' => $campusName,
                    'fee_heads' => $campusFeeHeads,
                    'rows' => $headWiseData,
                    'head_totals' => $campusHeadTotals,
                    'head_paid_totals' => $campusHeadPaidTotals,
                    'total_paid' => $campusTotalPaid,
                    'total' => $campusTotalDue,
                ]);
            }
        }

        return compact('allCampusData', 'feeHeads', 'grandTotal');
    }

    /**
     * Columns only from Fee Type / Fee Head module for the selected campus (strict — no global/auto heads).
     */
    private function buildFeeHeads(?string $campus): \Illuminate\Support\Collection
    {
        $campus = trim((string) ($campus ?? ''));
        if ($campus === '') {
            return collect();
        }

        return FeeType::query()
            ->whereNotNull('fee_name')
            ->whereRaw('TRIM(fee_name) != ?', [''])
            ->whereNotNull('campus')
            ->whereRaw('TRIM(campus) != ?', [''])
            ->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower($campus)])
            ->orderBy('fee_name')
            ->pluck('fee_name')
            ->map(fn ($name) => $this->normalizeFeeHead((string) $name))
            ->filter(fn ($name) => $name !== '')
            ->unique(fn ($name) => strtolower($name))
            ->values();
    }

    /**
     * Paid / due per fee head — same ledger math as Fee Payment (outstanding + fully paid titles).
     *
     * @return array<string, array{paid: float, due: float}>
     */
    private function calculateStudentHeadAmounts(Student $student, \Illuminate\Support\Collection $feeHeads): array
    {
        $result = [];
        foreach ($feeHeads as $head) {
            $result[$head] = ['paid' => 0.0, 'due' => 0.0];
        }

        if (empty($student->student_code)) {
            return $result;
        }

        $split = FeePaymentWebTables::feeResultsSplitForStudent($student);
        $rows = array_merge(
            $split['outstanding']['rows'] ?? [],
            $split['paid']['rows'] ?? []
        );

        foreach ($rows as $row) {
            $title = (string) ($row['fee_type'] ?? '');
            $head = $this->resolveFeeHeadForTitle($title, $feeHeads);
            if ($head === null) {
                continue;
            }

            $result[$head]['paid'] += (float) ($row['cash_paid'] ?? 0);
            $result[$head]['due'] += (float) ($row['due'] ?? 0);
        }

        foreach ($feeHeads as $head) {
            $result[$head]['paid'] = round($result[$head]['paid'] ?? 0, 2);
            $result[$head]['due'] = round($result[$head]['due'] ?? 0, 2);
        }

        return $result;
    }

    /**
     * Map ledger title to a fee head column only when that head exists in Fee Type / Fee Head for the campus.
     */
    private function resolveFeeHeadForTitle(string $paymentTitle, \Illuminate\Support\Collection $feeHeads): ?string
    {
        $normalized = $this->normalizeFeeHead($paymentTitle);
        if ($normalized === '') {
            return null;
        }

        $normalizedKey = strtolower($normalized);

        foreach ($feeHeads as $head) {
            if (strtolower(trim((string) $head)) === $normalizedKey) {
                return (string) $head;
            }
        }

        $best = null;
        $bestLen = 0;
        foreach ($feeHeads as $head) {
            $headKey = strtolower(trim((string) $head));
            if ($headKey === '' || ! str_starts_with($normalizedKey, $headKey)) {
                continue;
            }
            if (strlen($headKey) > $bestLen) {
                $best = (string) $head;
                $bestLen = strlen($headKey);
            }
        }

        return $best;
    }

    private function normalizeFeeHead(string $paymentTitle): string
    {
        $title = trim($paymentTitle);
        if ($title === '') {
            return '';
        }

        if (preg_match('/^(.+)\/\d+$/', $title, $matches)) {
            $title = trim((string) $matches[1]);
        }

        $parts = preg_split('/\s+-\s+/u', $title, 2);

        return trim((string) ($parts[0] ?? $title));
    }

    private function buildExportRows($allCampusData, $feeHeads, array $grandTotal): \Illuminate\Support\Collection
    {
        $rows = collect();

        foreach ($allCampusData as $campusData) {
            foreach ($campusData['rows'] as $row) {
                $line = [
                    'Campus' => $campusData['campus'],
                    'Class' => $row['class'],
                ];
                foreach ($feeHeads as $head) {
                    $headData = $row['heads'][$head] ?? ['paid' => 0, 'due' => 0];
                    $line[$head . ' Paid'] = number_format((float) ($headData['paid'] ?? 0), 2);
                    $line[$head . ' Due'] = number_format((float) ($headData['due'] ?? 0), 2);
                }
                $line['Total Paid'] = number_format((float) ($row['total_paid'] ?? 0), 2);
                $line['Total Due'] = number_format((float) ($row['total'] ?? 0), 2);
                $rows->push($line);
            }

            $summaryPaid = [
                'Campus' => $campusData['campus'],
                'Class' => 'Total Paid',
            ];
            $summaryDue = [
                'Campus' => $campusData['campus'],
                'Class' => 'Total Due',
            ];
            foreach ($feeHeads as $head) {
                $summaryPaid[$head . ' Paid'] = number_format((float) ($campusData['head_paid_totals'][$head] ?? 0), 2);
                $summaryPaid[$head . ' Due'] = '';
                $summaryDue[$head . ' Paid'] = '';
                $summaryDue[$head . ' Due'] = number_format((float) ($campusData['head_totals'][$head] ?? 0), 2);
            }
            $summaryPaid['Total Paid'] = number_format((float) ($campusData['total_paid'] ?? 0), 2);
            $summaryPaid['Total Due'] = '';
            $summaryDue['Total Paid'] = '';
            $summaryDue['Total Due'] = number_format((float) ($campusData['total'] ?? 0), 2);
            $rows->push($summaryPaid);
            $rows->push($summaryDue);
        }

        if ($allCampusData->count() > 1) {
            $grandPaid = [
                'Campus' => 'GRAND TOTAL',
                'Class' => 'Total Paid',
            ];
            $grandDue = [
                'Campus' => 'GRAND TOTAL',
                'Class' => 'Total Due',
            ];
            foreach ($feeHeads as $head) {
                $grandPaid[$head . ' Paid'] = number_format((float) ($grandTotal['heads_paid'][$head] ?? 0), 2);
                $grandPaid[$head . ' Due'] = '';
                $grandDue[$head . ' Paid'] = '';
                $grandDue[$head . ' Due'] = number_format((float) ($grandTotal['heads'][$head] ?? 0), 2);
            }
            $grandPaid['Total Paid'] = number_format((float) ($grandTotal['total_paid'] ?? 0), 2);
            $grandPaid['Total Due'] = '';
            $grandDue['Total Paid'] = '';
            $grandDue['Total Due'] = number_format((float) ($grandTotal['total'] ?? 0), 2);
            $rows->push($grandPaid);
            $rows->push($grandDue);
        }

        return $rows;
    }

    private function buildFilterDescription(?string $filterCampus, ?string $filterClass, ?string $filterSection): string
    {
        $parts = [];
        if ($filterCampus) {
            $parts[] = 'Campus: ' . $filterCampus;
        }
        if ($filterClass) {
            $parts[] = 'Class: ' . $filterClass;
        }
        if ($filterSection) {
            $parts[] = 'Section: ' . $filterSection;
        }

        return $parts !== [] ? implode(' | ', $parts) : 'All';
    }

    private function resolveSchoolLogoUrl(?string $logoPath): ?string
    {
        $logoPath = trim((string) $logoPath);
        if ($logoPath === '') {
            return null;
        }

        if (str_starts_with($logoPath, 'http://') || str_starts_with($logoPath, 'https://')) {
            return $logoPath;
        }

        if (str_starts_with($logoPath, 'storage/')) {
            return asset($logoPath);
        }

        return asset('storage/' . ltrim($logoPath, '/'));
    }
}
