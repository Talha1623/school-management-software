<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\ClassModel;
use App\Models\Section;
use App\Models\FeeType;
use App\Models\Campus;
use App\Services\FeePaymentWebTables;
use App\Models\GeneralSetting;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FeeDefaultReportController extends Controller
{
    /** @var list<string> */
    private const CORE_FEE_TYPES = [
        'Monthly Fee',
        'Transport Fee',
        'Admission Fee',
        'Custom Fee',
    ];

    /**
     * Display the fee default reports with filters.
     */
    public function index(Request $request): View
    {
        $reportData = $this->buildReportData($request);
        return view('reports.fee-default', $reportData);
    }

    /**
     * Export fee default report in Excel, CSV, or PDF format.
     */
    public function export(Request $request, string $format)
    {
        $reportData = $this->buildReportData($request);
        $rows = $reportData['reportRows'];
        $settings = GeneralSetting::getSettings();

        if (!in_array($format, ['excel', 'csv', 'pdf'], true)) {
            return redirect()->route($this->feeDefaultReportRouteName())->with('error', 'Invalid export format.');
        }

        if ($format === 'pdf') {
            $pdfData = [
                'rows' => $rows,
                'settings' => $settings,
                'filters' => [
                    'campus' => $reportData['filterCampus'],
                    'class' => $reportData['filterClass'],
                    'section' => $reportData['filterSection'],
                    'type' => $reportData['filterType'],
                    'status' => $reportData['filterStatus'],
                ],
                'generatedAt' => now()->format('d M Y, h:i A'),
            ];

            if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
                $pdf = Pdf::loadView('reports.fee-default-pdf', $pdfData);
                return $pdf->stream('fee_default_report_' . now()->format('Ymd_His') . '.pdf');
            }

            return response()->view('reports.fee-default-pdf', $pdfData);
        }

        return $this->exportSpreadsheet($rows, $format);
    }

    public function print(Request $request): View
    {
        $reportData = $this->buildReportData($request);
        $settings = GeneralSetting::getSettings();

        return view('reports.fee-default-print', [
            'rows' => $reportData['reportRows'],
            'settings' => $settings,
            'filters' => [
                'campus' => $reportData['filterCampus'],
                'class' => $reportData['filterClass'],
                'section' => $reportData['filterSection'],
                'type' => $reportData['filterType'],
                'status' => $reportData['filterStatus'],
            ],
            'printedAt' => now()->format('d M Y, h:i A'),
        ]);
    }

    private function exportSpreadsheet($rows, string $format): StreamedResponse
    {
        $isExcel = $format === 'excel';
        $filename = 'fee_default_report_' . now()->format('Ymd_His') . ($isExcel ? '.xls' : '.csv');

        $headers = [
            'Content-Type' => $isExcel ? 'application/vnd.ms-excel' : 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($rows, $isExcel): void {
            $file = fopen('php://output', 'w');
            if ($isExcel) {
                fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));
            }

            fputcsv($file, ['Student Code', 'Student', 'Parent', 'Class', 'Last Payment', 'Due Invoices', 'Total Amount', 'Late Fee', 'Total Dues', 'Phone', 'Whatsapp']);

            foreach ($rows as $row) {
                fputcsv($file, [
                    $row['student_code'] ?? 'N/A',
                    $row['student_name'] ?? 'N/A',
                    $row['parent_name'] ?? 'N/A',
                    $row['class'] ?? 'N/A',
                    $row['last_payment'] ?? 'N/A',
                    $row['due_invoices'] ?? 0,
                    (float) ($row['total_amount'] ?? 0),
                    (float) ($row['late'] ?? 0),
                    (float) ($row['total_dues'] ?? 0),
                    $row['phone'] ?? 'N/A',
                    $row['whatsapp'] ?? 'N/A',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function feeDefaultReportRouteName(): string
    {
        return auth()->guard('accountant')->check()
            ? 'accountant.fee-defaulters'
            : 'reports.fee-default';
    }

    private function buildReportData(Request $request): array
    {
        $filterCampus = $request->get('filter_campus');

        if (auth()->guard('accountant')->check()) {
            $accountantCampus = auth()->guard('accountant')->user()->campus ?? null;
            if ($accountantCampus) {
                $filterCampus = $accountantCampus;
                $request->merge(['filter_campus' => $accountantCampus]);
            }
        }
        $filterClass = $request->get('filter_class');
        $filterSection = $request->get('filter_section');
        $filterType = $request->get('filter_type');
        $filterStatus = $request->get('filter_status');

        $campuses = Campus::orderBy('campus_name', 'asc')->get();
        if (auth()->guard('accountant')->check()) {
            $accountantCampus = auth()->guard('accountant')->user()->campus ?? null;
            if ($accountantCampus) {
                $campuses = $campuses->filter(function ($campus) use ($accountantCampus) {
                    $name = $campus->campus_name ?? $campus;

                    return $name === $accountantCampus;
                })->values();
            }
        }
        if ($campuses->isEmpty()) {
            $campusesFromClasses = ClassModel::whereNotNull('campus')->distinct()->pluck('campus');
            $campusesFromSections = Section::whereNotNull('campus')->distinct()->pluck('campus');
            $allCampuses = $campusesFromClasses->merge($campusesFromSections)->unique()->sort()->values();

            $campuses = $allCampuses->map(function ($campusName) {
                return (object) ['campus_name' => $campusName, 'id' => null];
            });
        }

        $classesQuery = ClassModel::whereNotNull('class_name');
        if ($filterCampus) {
            $classesQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($filterCampus))]);
        }
        $classes = $classesQuery->distinct()->pluck('class_name')->sort()->values();
        if ($classes->isEmpty()) {
            $classesFromSubjects = \App\Models\Subject::whereNotNull('class');
            if ($filterCampus) {
                $classesFromSubjects->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($filterCampus))]);
            }
            $classesFromSubjects = $classesFromSubjects->distinct()->pluck('class')->sort()->values();
            $classes = $classesFromSubjects->isEmpty()
                ? collect(['Nursery', 'KG', '1st', '2nd', '3rd', '4th', '5th', '6th', '7th', '8th', '9th', '10th', '11th', '12th'])
                : $classesFromSubjects;
        }

        $sectionsQuery = Section::whereNotNull('name');
        if ($filterCampus) {
            $sectionsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($filterCampus))]);
        }
        if ($filterClass) {
            $sectionsQuery->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($filterClass))]);
        }
        $sections = $sectionsQuery->distinct()->pluck('name')->sort()->values();
        if ($sections->isEmpty()) {
            $sectionsFromSubjects = \App\Models\Subject::whereNotNull('section');
            if ($filterCampus) {
                $sectionsFromSubjects->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($filterCampus))]);
            }
            if ($filterClass) {
                $sectionsFromSubjects->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($filterClass))]);
            }
            $sectionsFromSubjects = $sectionsFromSubjects->distinct()->pluck('section')->sort()->values();
            $sections = $sectionsFromSubjects->isEmpty() ? collect(['A', 'B', 'C', 'D', 'E']) : $sectionsFromSubjects;
        }

        $typeOptions = $this->buildTypeOptions($filterCampus);

        $statusOptions = collect([
            'active' => 'Active Student',
            'deactive' => 'Deactive Student',
        ]);

        $query = Student::query();
        if ($filterCampus) {
            $query->where('campus', $filterCampus);
        }
        if ($filterClass) {
            $query->where('class', $filterClass);
        }
        if ($filterSection) {
            $query->where('section', $filterSection);
        }
        if ($filterStatus === 'active') {
            $query->whereNotNull('admission_date');
        } elseif ($filterStatus === 'deactive') {
            $query->whereNull('admission_date');
        }

        $students = $query->orderBy('student_name')->get();

        $reportRows = $students->map(function ($student) use ($filterType) {
            return $this->buildStudentReportRow($student, $filterType);
        })->values();

        $reportRows = $reportRows->filter(function ($row) {
            return ((float) ($row['total_dues'] ?? 0)) > 0 || ((int) ($row['due_invoices'] ?? 0)) > 0;
        })->values();

        return compact(
            'campuses',
            'classes',
            'sections',
            'typeOptions',
            'statusOptions',
            'students',
            'filterCampus',
            'filterClass',
            'filterSection',
            'filterType',
            'filterStatus',
            'reportRows'
        );
    }

    /**
     * Type dropdown: standard fee types + fee heads from Fee Type / Fee Head module.
     */
    private function buildTypeOptions(?string $filterCampus): \Illuminate\Support\Collection
    {
        $seen = [];
        $ordered = [];

        foreach (self::CORE_FEE_TYPES as $feeName) {
            $key = strtolower($feeName);
            if (!isset($seen[$key])) {
                $seen[$key] = true;
                $ordered[] = $feeName;
            }
        }

        $query = FeeType::query()
            ->whereNotNull('fee_name')
            ->whereRaw('TRIM(fee_name) != ?', ['']);

        if ($filterCampus) {
            $query->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($filterCampus))]);
        }

        foreach ($query->orderBy('fee_name')->pluck('fee_name') as $feeName) {
            $feeName = $this->baseFeeTypeName((string) $feeName);
            if ($feeName === '') {
                continue;
            }
            $key = strtolower($feeName);
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $ordered[] = $feeName;
        }

        return collect($ordered)->mapWithKeys(fn (string $name) => [$name => $name]);
    }

    /**
     * Per-student totals from Fee Payment Search Results (FeePaymentWebTables).
     */
    private function buildStudentReportRow(Student $student, ?string $filterType): array
    {
        $rows = collect(FeePaymentWebTables::searchResultsForStudent($student)['rows'] ?? [])
            ->filter(fn (array $row) => $this->paymentTitleMatchesFilter((string) ($row['fee_type'] ?? ''), $filterType))
            ->values();

        $lastPayment = $rows
            ->pluck('last_payment_date')
            ->filter()
            ->sortByDesc(fn ($date) => $date)
            ->first();

        return [
            'student_code' => $student->student_code,
            'student_name' => $student->student_name,
            'parent_name' => $student->father_name,
            'class' => $student->class,
            'last_payment' => $lastPayment,
            'due_invoices' => $rows->count(),
            'total_amount' => round((float) $rows->sum('total'), 2),
            'late' => round((float) $rows->sum('late_fee'), 2),
            'total_dues' => round((float) $rows->sum('due'), 2),
            'phone' => $student->father_phone,
            'whatsapp' => $student->whatsapp_number,
            'student_id' => $student->id,
        ];
    }

    private function isSpecificFeeTypeFilter(?string $filterType): bool
    {
        $filterType = trim((string) ($filterType ?? ''));

        return $filterType !== '' && $filterType !== 'all_detailed';
    }

    private function paymentTitleMatchesFilter(string $paymentTitle, ?string $filterType): bool
    {
        if (!$this->isSpecificFeeTypeFilter($filterType)) {
            return true;
        }

        $titleBase = strtolower($this->baseFeeTypeName($paymentTitle));
        $filterBase = strtolower($this->baseFeeTypeName((string) $filterType));

        if ($titleBase === '' || $filterBase === '') {
            return false;
        }

        return $titleBase === $filterBase;
    }

    /**
     * Parent fee name only — "Card Fees - June 2026" becomes "Card Fees" for dropdown and filters.
     */
    private function baseFeeTypeName(string $name): string
    {
        $name = trim($name);
        if ($name === '') {
            return '';
        }

        if (preg_match('/^(.+)\/\d+$/', $name, $matches)) {
            $name = trim((string) $matches[1]);
        }

        $months = 'January|February|March|April|May|June|July|August|September|October|November|December';
        $stripped = preg_replace('/\s+-\s+(' . $months . ')\s+\d{4}$/i', '', $name);

        return trim((string) ($stripped !== null && $stripped !== '' ? $stripped : $name));
    }

    /**
     * Get fee types by campus (AJAX endpoint).
     */
    public function getFeeTypesByCampus(Request $request): JsonResponse
    {
        $campus = $request->get('campus');
        $types = $this->buildTypeOptions($campus)->map(function ($label, $value) {
            return ['value' => (string) $value, 'label' => (string) $label];
        })->values();

        return response()->json(['types' => $types]);
    }

    /**
     * Store a newly created campus (AJAX).
     */
    public function storeCampus(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'campus_name' => ['required', 'string', 'max:255', 'unique:campuses,campus_name'],
        ]);

        $campus = Campus::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Campus added successfully!',
            'campus' => $campus
        ]);
    }

    /**
     * Remove the specified campus (AJAX).
     */
    public function destroyCampus(Campus $campus): JsonResponse
    {
        $campus->delete();

        return response()->json([
            'success' => true,
            'message' => 'Campus deleted successfully!'
        ]);
    }

    /**
     * Get classes by campus (AJAX endpoint)
     */
    public function getClassesByCampus(Request $request): JsonResponse
    {
        $campus = $request->get('campus');

        $classesQuery = ClassModel::whereNotNull('class_name');
        if ($campus) {
            $classesQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
        }
        $classes = $classesQuery->distinct()->pluck('class_name')->sort()->values();

        if ($classes->isEmpty()) {
            $classesFromSubjects = \App\Models\Subject::whereNotNull('class');
            if ($campus) {
                $classesFromSubjects->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
            }
            $classesFromSubjects = $classesFromSubjects->distinct()->pluck('class')->sort()->values();
            $classes = $classesFromSubjects->isEmpty()
                ? collect(['Nursery', 'KG', '1st', '2nd', '3rd', '4th', '5th', '6th', '7th', '8th', '9th', '10th', '11th', '12th'])
                : $classesFromSubjects;
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
    public function getSectionsByClass(Request $request): JsonResponse
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
            $sectionsFromSubjects = \App\Models\Subject::whereNotNull('section')
                ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))]);
            if ($campus) {
                $sectionsFromSubjects->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
            }
            $sections = $sectionsFromSubjects->distinct()->pluck('section')->sort()->values();
        }

        return response()->json(['sections' => $sections]);
    }
}

