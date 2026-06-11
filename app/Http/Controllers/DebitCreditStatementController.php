<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\ClassModel;
use App\Models\Section;
use App\Models\StudentPayment;
use App\Models\Campus;
use App\Models\ManagementExpense;
use App\Models\ExpenseCategory;
use App\Models\GeneralSetting;
use App\Models\Salary;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DebitCreditStatementController extends Controller
{
    /**
     * Display the debit & credit statement with filters.
     */
    public function index(Request $request): View
    {
        return view('reports.debit-credit', $this->buildReportData($request));
    }

    public function export(Request $request, string $format)
    {
        $data = $this->buildReportData($request);
        if ($format === 'csv') {
            return $this->exportCsv($data['feeHeadSummary'], $data['expenseHeadSummary']);
        }
        if ($format === 'pdf') {
            $pdfData = array_merge($data, [
                'settings' => GeneralSetting::getSettings(),
                'printedAt' => now()->format('d M Y, h:i A'),
            ]);

            if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
                $pdf = Pdf::loadView('reports.debit-credit-pdf', $pdfData);

                return $pdf->stream('debit_credit_statement_' . now()->format('Ymd_His') . '.pdf');
            }

            return response()->view('reports.debit-credit-pdf', $pdfData);
        }

        return redirect()->route('reports.debit-credit')->with('error', 'Invalid export format.');
    }

    public function print(Request $request): View
    {
        return view('reports.debit-credit-print', array_merge($this->buildReportData($request), [
            'settings' => GeneralSetting::getSettings(),
            'printedAt' => now()->format('d M Y, h:i A'),
        ]));
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

        $classes = $classes->map(function ($class) {
            return trim((string) $class);
        })->filter(function ($class) {
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
            $sectionsFromSubjects = \App\Models\Subject::whereNotNull('section')
                ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))]);
            if ($campus) {
                $sectionsFromSubjects->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
            }
            $sections = $sectionsFromSubjects->distinct()->pluck('section')->sort()->values();
        }

        return response()->json(['sections' => $sections]);
    }

    private function exportCsv($feeHeadSummary, $expenseHeadSummary): StreamedResponse
    {
        $filename = 'debit_credit_statement_' . now()->format('Ymd_His') . '.csv';
        $headers = ['Content-Type' => 'text/csv', 'Content-Disposition' => 'attachment; filename="' . $filename . '"'];
        $callback = function () use ($feeHeadSummary, $expenseHeadSummary): void {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Fee Type (all paid fees)', 'Paid Transactions', 'Cash Received', 'Discount', 'Paid Amount']);
            foreach ($feeHeadSummary as $row) {
                fputcsv($file, [
                    $row['head'],
                    $row['transactions'],
                    (float) ($row['cash'] ?? 0),
                    (float) ($row['discount'] ?? 0),
                    (float) $row['amount'],
                ]);
            }
            fputcsv($file, []);
            fputcsv($file, ['Expense Category (Expense Management + Staff Salary)', 'Paid Transactions', 'Paid Amount']);
            foreach ($expenseHeadSummary as $row) {
                fputcsv($file, [$row['head'], $row['transactions'], (float) $row['amount']]);
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
        $filterFromDate = $request->get('filter_from_date');
        $filterToDate = $request->get('filter_to_date');

        $campuses = Campus::orderBy('campus_name', 'asc')->pluck('campus_name');
        if ($campuses->isEmpty()) {
            $campuses = ClassModel::whereNotNull('campus')->distinct()->pluck('campus')
                ->merge(Section::whereNotNull('campus')->distinct()->pluck('campus'))
                ->unique()->sort()->values();
        }

        $classesQuery = ClassModel::whereNotNull('class_name');
        if ($filterCampus) {
            $classesQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($filterCampus))]);
        }
        $classes = $classesQuery->distinct()->pluck('class_name')->sort()->values();
        if ($classes->isEmpty()) {
            $classes = Student::whereNotNull('class')
                ->when($filterCampus, fn ($q) => $q->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($filterCampus))]))
                ->distinct()->pluck('class')->sort()->values();
            if ($classes->isEmpty()) {
                $classes = collect(['Nursery', 'KG', '1st', '2nd', '3rd', '4th', '5th', '6th', '7th', '8th', '9th', '10th', '11th', '12th']);
            }
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
            $sections = \App\Models\Subject::whereNotNull('section')
                ->when($filterCampus, fn ($q) => $q->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($filterCampus))]))
                ->when($filterClass, fn ($q) => $q->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($filterClass))]))
                ->distinct()->pluck('section')->sort()->values();
            if ($sections->isEmpty()) {
                $sections = collect(['A', 'B', 'C', 'D', 'E']);
            }
        }

        $expenseCategories = $this->buildExpenseCategoriesFromModule($filterCampus);

        $students = Student::query()
            ->when($filterCampus, fn ($q) => $q->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim((string) $filterCampus))]))
            ->when($filterClass, fn ($q) => $q->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim((string) $filterClass))]))
            ->when($filterSection, fn ($q) => $q->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim((string) $filterSection))]))
            ->get();

        $studentCodes = $students->pluck('student_code')->filter()->values();

        $feeHeadSummary = $this->buildFeeHeadSummary(
            $studentCodes,
            $filterCampus,
            $filterFromDate,
            $filterToDate
        );

        $expenseHeadSummary = $this->buildExpenseHeadSummary(
            $expenseCategories,
            $filterCampus,
            $filterFromDate,
            $filterToDate
        );

        $feeTotals = [
            'transactions' => (int) $feeHeadSummary->sum('transactions'),
            'cash' => round((float) $feeHeadSummary->sum('cash'), 2),
            'discount' => round((float) $feeHeadSummary->sum('discount'), 2),
            'amount' => round((float) $feeHeadSummary->sum('amount'), 2),
        ];

        $expenseTotals = [
            'transactions' => (int) $expenseHeadSummary->sum('transactions'),
            'amount' => round((float) $expenseHeadSummary->sum('amount'), 2),
        ];

        return compact(
            'campuses',
            'classes',
            'sections',
            'feeHeadSummary',
            'expenseHeadSummary',
            'feeTotals',
            'expenseTotals',
            'filterCampus',
            'filterClass',
            'filterSection',
            'filterFromDate',
            'filterToDate'
        );
    }

    /**
     * Expense categories from Expense Management module only.
     */
    private function buildExpenseCategoriesFromModule(?string $filterCampus): \Illuminate\Support\Collection
    {
        $query = ExpenseCategory::query()
            ->whereNotNull('category_name')
            ->whereRaw('TRIM(category_name) != ?', ['']);

        if ($filterCampus) {
            $query->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($filterCampus))]);
        }

        return $query
            ->orderBy('category_name')
            ->pluck('category_name')
            ->map(fn ($name) => trim((string) $name))
            ->filter(fn ($name) => $name !== '')
            ->unique(fn ($name) => strtolower($name))
            ->values();
    }

    /**
     * All paid fee types (Monthly Fee, Transport Fee, etc.) — dynamic from actual payments.
     */
    private function buildFeeHeadSummary(
        \Illuminate\Support\Collection $studentCodes,
        ?string $filterCampus,
        ?string $filterFromDate,
        ?string $filterToDate
    ): \Illuminate\Support\Collection {
        if ($studentCodes->isEmpty()) {
            return collect();
        }

        $payments = StudentPayment::query()
            ->ledgerActive()
            ->whereIn('student_code', $studentCodes)
            ->where('method', '!=', 'Generated')
            ->when($filterFromDate, fn ($q) => $q->whereDate('payment_date', '>=', $filterFromDate))
            ->when($filterToDate, fn ($q) => $q->whereDate('payment_date', '<=', $filterToDate))
            ->get();

        $aggregates = [];

        foreach ($payments as $payment) {
            $head = $this->normalizePaidFeeHead((string) ($payment->payment_title ?? ''));
            if ($head === '') {
                continue;
            }

            $cash = round((float) ($payment->payment_amount ?? 0), 2);
            $discount = round($this->effectivePaymentDiscount($payment), 2);

            if ($cash <= 0 && $discount <= 0) {
                continue;
            }

            if (!isset($aggregates[$head])) {
                $aggregates[$head] = [
                    'head' => $head,
                    'transactions' => 0,
                    'cash' => 0.0,
                    'discount' => 0.0,
                    'amount' => 0.0,
                ];
            }

            $aggregates[$head]['transactions']++;
            $aggregates[$head]['cash'] += $cash;
            $aggregates[$head]['discount'] += $discount;
            // Paid amount = cash received only (discount shown separately)
            $aggregates[$head]['amount'] += $cash;
        }

        return collect($aggregates)
            ->map(fn ($row) => [
                'head' => $row['head'],
                'transactions' => (int) $row['transactions'],
                'cash' => round((float) $row['cash'], 2),
                'discount' => round((float) $row['discount'], 2),
                'amount' => round((float) $row['amount'], 2),
            ])
            ->sortBy('head', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();
    }

    /**
     * Group payment titles under fee head name (e.g. "Monthly Fee - Jan 2026" → "Monthly Fee").
     */
    private function normalizePaidFeeHead(string $paymentTitle): string
    {
        $title = trim($paymentTitle);
        if ($title === '') {
            return '';
        }

        $parts = preg_split('/\s[-–—\/]\s/u', $title, 2);

        return trim((string) ($parts[0] ?? $title));
    }

    private function buildExpenseHeadSummary(
        \Illuminate\Support\Collection $expenseCategories,
        ?string $filterCampus,
        ?string $filterFromDate,
        ?string $filterToDate
    ): \Illuminate\Support\Collection {
        $aggregates = [];
        foreach ($expenseCategories as $category) {
            $aggregates[$category] = ['head' => $category, 'transactions' => 0, 'amount' => 0.0];
        }

        $salaryHead = $this->resolveSalaryExpenseHead($expenseCategories);
        if (! isset($aggregates[$salaryHead])) {
            $aggregates[$salaryHead] = ['head' => $salaryHead, 'transactions' => 0, 'amount' => 0.0];
        }

        if ($expenseCategories->isNotEmpty()) {
            $expenses = ManagementExpense::query()
                ->when($filterCampus, fn ($q) => $q->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim((string) $filterCampus))]))
                ->when($filterFromDate, fn ($q) => $q->whereDate('date', '>=', $filterFromDate))
                ->when($filterToDate, fn ($q) => $q->whereDate('date', '<=', $filterToDate))
                ->get();

            foreach ($expenses as $expense) {
                $matchedCategory = $this->resolveExpenseCategoryForExpense((string) ($expense->category ?? ''), $expenseCategories);
                if ($matchedCategory === null) {
                    continue;
                }

                $amount = (float) ($expense->amount ?? 0);
                if ($amount <= 0) {
                    continue;
                }

                $aggregates[$matchedCategory]['transactions']++;
                $aggregates[$matchedCategory]['amount'] += $amount;
            }
        }

        $salaryDateSql = Salary::effectivePaymentDateExpression();
        $salariesQuery = Salary::query()
            ->where('status', 'Paid')
            ->where('amount_paid', '>', 0)
            ->whereHas('staff');

        if ($filterCampus) {
            $campusKey = strtolower(trim((string) $filterCampus));
            $salariesQuery->whereHas('staff', function ($q) use ($campusKey) {
                $q->whereRaw('LOWER(TRIM(campus)) = ?', [$campusKey]);
            });
        }
        if ($filterFromDate) {
            $salariesQuery->whereRaw("DATE({$salaryDateSql}) >= ?", [$filterFromDate]);
        }
        if ($filterToDate) {
            $salariesQuery->whereRaw("DATE({$salaryDateSql}) <= ?", [$filterToDate]);
        }

        foreach ($salariesQuery->get() as $salary) {
            $amount = (float) ($salary->amount_paid ?? 0);
            if ($amount <= 0) {
                continue;
            }

            $aggregates[$salaryHead]['transactions']++;
            $aggregates[$salaryHead]['amount'] += $amount;
        }

        return collect($aggregates)
            ->map(fn ($row) => [
                'head' => $row['head'],
                'transactions' => (int) $row['transactions'],
                'amount' => round((float) $row['amount'], 2),
            ])
            ->sortBy('head', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();
    }

    private function resolveSalaryExpenseHead(\Illuminate\Support\Collection $expenseCategories): string
    {
        foreach ($expenseCategories as $category) {
            $normalized = strtolower(trim((string) $category));
            if (in_array($normalized, ['salary', 'staff salary', 'teacher salary'], true)) {
                return (string) $category;
            }
        }

        return 'Staff Salary';
    }

    private function salaryPaymentDateColumn(): string
    {
        $connection = (new Salary())->getConnectionName() ?: config('database.default');

        return Schema::connection($connection)->hasColumn('salaries', 'payment_date')
            ? 'payment_date'
            : 'updated_at';
    }

    private function resolveFeeHeadForPayment(string $paymentTitle, \Illuminate\Support\Collection $feeHeads): ?string
    {
        $title = strtolower(trim($paymentTitle));
        if ($title === '') {
            return null;
        }

        foreach ($feeHeads as $head) {
            $headLower = strtolower(trim((string) $head));
            if ($headLower === '') {
                continue;
            }
            if (str_starts_with($title, $headLower) || str_contains($title, $headLower)) {
                return (string) $head;
            }
        }

        return null;
    }

    private function resolveExpenseCategoryForExpense(string $expenseCategory, \Illuminate\Support\Collection $categories): ?string
    {
        $needle = strtolower(trim($expenseCategory));
        if ($needle === '') {
            return null;
        }

        foreach ($categories as $category) {
            if (strtolower(trim((string) $category)) === $needle) {
                return (string) $category;
            }
        }

        return null;
    }

    /**
     * Fee credit for one payment (cash received + discount), same as Fee Payment partial/full pay.
     */
    private function effectivePaymentDiscount(StudentPayment $payment): float
    {
        $discount = (float) ($payment->discount ?? 0);
        if ($discount > 0.00001) {
            return $discount;
        }

        $title = (string) ($payment->payment_title ?? '');
        if ($title === '' || !preg_match('/\/\d+$/', $title)) {
            return 0.0;
        }

        return (float) StudentPayment::query()
            ->ledgerActive()
            ->where('student_code', $payment->student_code)
            ->where('payment_title', $title)
            ->whereIn('method', ['Generated', 'Installment'])
            ->sum('discount');
    }
}
