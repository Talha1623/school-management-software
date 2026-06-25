<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\ClassModel;
use App\Models\Section;
use App\Models\FeeType;
use App\Models\StudentPayment;
use App\Models\StudentDiscount;
use App\Models\Campus;
use App\Models\GeneralSetting;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UnpaidInvoicesController extends Controller
{
    /** @var list<string> */
    private const CORE_FEE_TYPES = [
        'Monthly Fee',
        'Transport Fee',
        'Admission Fee',
        'Arrears',
        'Custom Fee',
    ];

    /**
     * Display the list of unpaid invoices with filters.
     */
    public function index(Request $request): View
    {
        return view('reports.unpaid-invoices', $this->buildReportData($request));
    }

    public function export(Request $request, string $format)
    {
        $data = $this->buildReportData($request);
        $rows = $data['unpaidInvoices'];
        if ($format === 'csv' || $format === 'excel') {
            return $this->exportSheet($rows, $format === 'excel');
        }
        if ($format === 'pdf') {
            $pdfData = array_merge($data, [
                'settings' => GeneralSetting::getSettings(),
                'printedAt' => now()->format('d M Y, h:i A'),
            ]);
            if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
                $pdf = Pdf::loadView('reports.unpaid-invoices-pdf', $pdfData);

                return $pdf->stream('unpaid_invoices_' . now()->format('Ymd_His') . '.pdf');
            }

            return response()->view('reports.unpaid-invoices-pdf', $pdfData);
        }

        return redirect()->route('reports.unpaid-invoices')->with('error', 'Invalid export format.');
    }

    public function print(Request $request): View
    {
        return view('reports.unpaid-invoices-print', array_merge($this->buildReportData($request), [
            'settings' => GeneralSetting::getSettings(),
            'printedAt' => now()->format('d M Y, h:i A'),
        ]));
    }

    public function getClassesByCampus(Request $request): JsonResponse
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

        $classes = $classes->map(fn ($class) => trim((string) $class))
            ->filter(fn ($class) => $class !== '')
            ->unique()
            ->sort()
            ->values();

        return response()->json(['classes' => $classes]);
    }

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

    public function getFeeTypesByCampus(Request $request): JsonResponse
    {
        $campus = $request->get('campus');
        $types = $this->buildTypeOptions($campus)->map(function ($label, $value) {
            return ['value' => (string) $value, 'label' => (string) $label];
        })->values();

        return response()->json(['types' => $types]);
    }

    private function exportSheet($rows, bool $excel): StreamedResponse
    {
        $filename = 'unpaid_invoices_' . now()->format('Ymd_His') . ($excel ? '.xls' : '.csv');
        $headers = [
            'Content-Type' => $excel ? 'application/vnd.ms-excel' : 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];
        $callback = function () use ($rows, $excel): void {
            $file = fopen('php://output', 'w');
            if ($excel) {
                fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));
            }
            fputcsv($file, ['#', 'Student Code', 'Student Name', 'Campus', 'Class', 'Section', 'Fee Type', 'Expected Amount', 'Paid Amount', 'Unpaid Amount', 'Status']);
            foreach ($rows as $i => $r) {
                fputcsv($file, [
                    $i + 1,
                    $r['student_code'],
                    $r['student_name'],
                    $r['campus'],
                    $r['class'],
                    $r['section'],
                    $r['fee_type'],
                    (float) $r['expected_amount'],
                    (float) $r['paid_amount'],
                    (float) $r['unpaid_amount'],
                    $r['status'],
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function buildReportData(Request $request): array
    {
        $filterCampus = $request->get('filter_campus');
        $filterClass = $request->get('filter_class');
        $filterSection = $request->get('filter_section');
        $filterType = $request->get('filter_type');
        $filterStudentStatus = $request->get('filter_student_status');

        $campuses = Campus::orderBy('campus_name', 'asc')->pluck('campus_name');
        if ($campuses->isEmpty()) {
            $campuses = ClassModel::whereNotNull('campus')->distinct()->pluck('campus')
                ->merge(Section::whereNotNull('campus')->distinct()->pluck('campus'))
                ->unique()->sort()->values();
        }

        $classes = ClassModel::whereNotNull('class_name')
            ->when($filterCampus, fn ($q) => $q->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($filterCampus))]))
            ->distinct()->pluck('class_name')->sort()->values();
        if ($classes->isEmpty()) {
            $classes = Student::whereNotNull('class')
                ->when($filterCampus, fn ($q) => $q->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($filterCampus))]))
                ->distinct()->pluck('class')->sort()->values();
            if ($classes->isEmpty()) {
                $classes = collect(['Nursery', 'KG', '1st', '2nd', '3rd', '4th', '5th', '6th', '7th', '8th', '9th', '10th', '11th', '12th']);
            }
        }

        $sections = Section::whereNotNull('name')
            ->when($filterCampus, fn ($q) => $q->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($filterCampus))]))
            ->when($filterClass, fn ($q) => $q->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($filterClass))]))
            ->distinct()->pluck('name')->sort()->values();
        if ($sections->isEmpty()) {
            $sections = \App\Models\Subject::whereNotNull('section')
                ->when($filterCampus, fn ($q) => $q->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($filterCampus))]))
                ->when($filterClass, fn ($q) => $q->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($filterClass))]))
                ->distinct()->pluck('section')->sort()->values();
            if ($sections->isEmpty()) {
                $sections = collect(['A', 'B', 'C', 'D', 'E']);
            }
        }

        $typeOptions = $this->buildTypeOptions($filterCampus);

        $query = Student::query();
        $hasStudentStatus = Schema::hasColumn('students', 'status');
        if ($filterCampus) {
            $query->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim((string) $filterCampus))]);
        }
        if ($filterClass) {
            $query->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim((string) $filterClass))]);
        }
        if ($filterSection) {
            $query->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim((string) $filterSection))]);
        }
        if ($filterStudentStatus && $hasStudentStatus) {
            $statusValue = strtolower(trim($filterStudentStatus));
            if (in_array($statusValue, ['deactive', 'inactive'], true)) {
                $query->whereRaw("LOWER(TRIM(status)) IN ('deactive', 'inactive')");
            } else {
                $query->whereRaw('LOWER(TRIM(status)) = ?', [$statusValue]);
            }
        }

        $students = $query->orderBy('student_name')->get();
        $studentCodes = $students->pluck('student_code')->filter()->unique()->values();

        $generatedPayments = StudentPayment::query()
            ->ledgerActive()
            ->whereIn('student_code', $studentCodes)
            ->whereIn('method', ['Generated', 'Installment'])
            ->get()
            ->groupBy('student_code');

        $paidPayments = StudentPayment::query()
            ->ledgerActive()
            ->whereIn('student_code', $studentCodes)
            ->whereNotIn('method', ['Generated', 'Installment'])
            ->get()
            ->groupBy('student_code');

        $unpaidInvoices = collect();
        foreach ($students as $student) {
            $generated = $generatedPayments->get($student->student_code, collect());
            $paid = $paidPayments->get($student->student_code, collect());
            foreach ($this->unpaidInvoiceLinesForStudent($student, $generated, $paid, $filterType) as $line) {
                $unpaidInvoices->push($line);
            }
        }

        $unpaidInvoices = $unpaidInvoices->sortBy([
            ['campus', 'asc'],
            ['class', 'asc'],
            ['student_name', 'asc'],
            ['fee_type', 'asc'],
        ])->values();

        return compact(
            'campuses',
            'classes',
            'sections',
            'typeOptions',
            'unpaidInvoices',
            'filterCampus',
            'filterClass',
            'filterSection',
            'filterType',
            'filterStudentStatus'
        );
    }

    /**
     * One row per unpaid fee title — same installment rules as Fee Default / Fee Payment.
     *
     * @return list<array<string, mixed>>
     */
    private function unpaidInvoiceLinesForStudent(Student $student, $generated, $paid, ?string $filterType): array
    {
        $generatedByTitle = $generated->groupBy('payment_title');
        $paidByTitle = $paid->groupBy('payment_title');

        $installmentBaseTitles = StudentPayment::installmentBaseTitlesForStudent((string) $student->student_code);

        $totalStudentDiscount = (float) StudentDiscount::where('student_code', $student->student_code)
            ->sum('discount_amount');

        $lines = [];

        foreach ($generatedByTitle as $title => $items) {
            $titleStr = (string) $title;
            if (!$this->paymentTitleMatchesFilter($titleStr, $filterType)) {
                continue;
            }

            $isInstallment = (bool) preg_match('/\/\d+$/', $titleStr);
            if (!$isInstallment && isset($installmentBaseTitles[$titleStr])) {
                continue;
            }

            $isMonthlyFee = str_starts_with($titleStr, 'Monthly Fee - ');
            $latestGenerated = $items->sortByDesc('id')->first();
            $paidForTitle = StudentPayment::paidLedgerRowsForLatestGeneratedTitle(
                $paidByTitle->get($title, collect()),
                $latestGenerated
            );

            $originalAmount = $items->sum(fn ($item) => (float) ($item->payment_amount ?? 0));
            $generatedLate = $items->sum(fn ($item) => (float) ($item->late_fee ?? 0));

            $generatedDiscount = 0.0;
            if ($isInstallment) {
                $generatedDiscount = $items->sum(fn ($item) => (float) ($item->discount ?? 0));
            }

            $paidDiscount = $paidForTitle->sum(fn ($item) => (float) ($item->discount ?? 0));

            $appliedStudentDiscount = 0.0;
            if ($isMonthlyFee && $totalStudentDiscount > 0 && !$isInstallment) {
                $appliedStudentDiscount = round($totalStudentDiscount, 2);
            }

            $totalDiscount = $generatedDiscount + $paidDiscount + $appliedStudentDiscount;

            $paidAmountOnly = $paidForTitle->sum(function ($item) {
                $amount = (float) ($item->payment_amount ?? 0);
                $late = (float) ($item->late_fee ?? 0);

                return max(0, $amount - $late);
            });
            $paidLate = $paidForTitle->sum(fn ($item) => (float) ($item->late_fee ?? 0));

            $billAmount = max(0, $originalAmount - $totalDiscount);
            $remainingAmount = max(0, $billAmount - $paidAmountOnly);
            $remainingLate = max(0, $generatedLate - $paidLate);
            $remainingTotal = $remainingAmount + $remainingLate;

            if ($remainingTotal <= 0.0001) {
                continue;
            }

            $expectedTotal = $billAmount + $generatedLate;
            $paidTotal = $paidAmountOnly + $paidLate;

            $lines[] = [
                'student_code' => $student->student_code,
                'student_name' => $student->student_name,
                'campus' => $student->campus,
                'class' => $student->class,
                'section' => $student->section,
                'fee_type' => $titleStr,
                'fee_head' => $this->normalizeFeeHead($titleStr),
                'expected_amount' => round($expectedTotal, 2),
                'paid_amount' => round($paidTotal, 2),
                'unpaid_amount' => round($remainingTotal, 2),
                'status' => $paidTotal > 0.0001 ? 'Partial' : 'Unpaid',
                'due_date' => $items->min('payment_date'),
            ];
        }

        // Bulk admit (and similar) may save transport on student without a Generated row — align with Fee Payment.
        $hasTransportHistory = $generated->contains(function ($fee) {
            return str_starts_with(trim((string) ($fee->payment_title ?? '')), 'Transport Fee');
        }) || $paid->contains(function ($fee) {
            return str_starts_with(trim((string) ($fee->payment_title ?? '')), 'Transport Fee');
        });

        if (!$hasTransportHistory) {
            $transportRoute = trim((string) ($student->transport_route ?? ''));
            $transportFare = (float) ($student->transport_fare ?? 0);
            if ($transportRoute !== '' && $transportFare > 0.0001) {
                $billingDate = $student->admission_date
                    ? (string) $student->admission_date
                    : ($student->created_at ? Carbon::parse($student->created_at)->toDateString() : now()->format('Y-m-d'));
                $billing = Carbon::parse($billingDate);
                $transportTitle = 'Transport Fee - ' . $billing->format('F') . ' ' . $billing->format('Y');

                if ($this->paymentTitleMatchesFilter($transportTitle, $filterType)) {
                    $paidTransport = $paid->filter(function ($item) {
                        return str_starts_with(trim((string) ($item->payment_title ?? '')), 'Transport Fee');
                    });
                    $paidAmountOnly = $paidTransport->sum(function ($item) {
                        $amount = (float) ($item->payment_amount ?? 0);
                        $late = (float) ($item->late_fee ?? 0);

                        return max(0, $amount - $late);
                    });
                    $paidLate = $paidTransport->sum(fn ($item) => (float) ($item->late_fee ?? 0));
                    $remainingAmount = max(0, $transportFare - $paidAmountOnly);
                    $remainingTotal = $remainingAmount + max(0, 0 - $paidLate);

                    if ($remainingTotal > 0.0001) {
                        $paidTotal = $paidAmountOnly + $paidLate;
                        $lines[] = [
                            'student_code' => $student->student_code,
                            'student_name' => $student->student_name,
                            'campus' => $student->campus,
                            'class' => $student->class,
                            'section' => $student->section,
                            'fee_type' => $transportTitle,
                            'fee_head' => 'Transport Fee',
                            'expected_amount' => round($transportFare, 2),
                            'paid_amount' => round($paidTotal, 2),
                            'unpaid_amount' => round($remainingTotal, 2),
                            'status' => $paidTotal > 0.0001 ? 'Partial' : 'Unpaid',
                            'due_date' => $billingDate,
                        ];
                    }
                }
            }
        }

        return $lines;
    }

    /**
     * Type dropdown: same as Fee Default Reports (core types + Fee Head module).
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

    private function normalizeFeeHead(string $paymentTitle): string
    {
        return $this->baseFeeTypeName($paymentTitle);
    }
}
