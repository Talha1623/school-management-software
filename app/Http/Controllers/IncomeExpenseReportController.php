<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\StudentPayment;
use App\Models\CustomPayment;
use App\Models\ManagementExpense;
use App\Models\ExpenseCategory;
use App\Models\AdminRole;
use App\Models\Accountant;
use App\Models\Campus;
use App\Models\GeneralSetting;
use App\Models\Salary;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\StreamedResponse;

class IncomeExpenseReportController extends Controller
{
    /**
     * Display the income & expense reports with filters.
     */
    public function index(Request $request): View
    {
        $data = $this->buildReportData($request);
        return view('reports.income-expense', $data);
    }

    public function export(Request $request, string $format)
    {
        $data = $this->buildReportData($request);
        $records = $data['incomeRecords'];

        if ($format === 'csv') {
            return $this->exportCsv($records);
        }

        if ($format === 'pdf') {
            $pdfData = [
                'incomeRecords' => $records,
                'settings' => GeneralSetting::getSettings(),
                'filters' => [
                    'campus' => $data['filterCampus'],
                    'user_type' => $data['filterUserType'],
                    'user' => $data['filterUser'],
                    'from_date' => $data['filterFromDate'],
                    'to_date' => $data['filterToDate'],
                ],
                'printedAt' => now()->format('d M Y, h:i A'),
            ];

            if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
                $pdf = Pdf::loadView('reports.income-expense-pdf', $pdfData);
                return $pdf->stream('income_expense_report_' . now()->format('Ymd_His') . '.pdf');
            }

            return response()->view('reports.income-expense-pdf', $pdfData);
        }

        return redirect()->route('reports.income-expense')->with('error', 'Invalid export format.');
    }

    public function print(Request $request): View
    {
        $data = $this->buildReportData($request);
        return view('reports.income-expense-print', array_merge($data, [
            'settings' => GeneralSetting::getSettings(),
            'printedAt' => now()->format('d M Y, h:i A'),
        ]));
    }

    public function getUsersByType(Request $request): \Illuminate\Http\JsonResponse
    {
        $userType = $request->get('user_type');
        $campus = $request->get('campus');

        return response()->json([
            'users' => $this->getUsersForType($userType, $campus),
        ]);
    }

    private function getUsersForType(?string $userType, ?string $campus)
    {
        $users = collect();
        $normalizedType = strtolower(trim((string) $userType));

        if ($normalizedType === '' || $normalizedType === 'accountant') {
            $accountantsQuery = Accountant::whereNotNull('name');
            if ($campus) {
                $accountantsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
            }
            $users = $users->merge($accountantsQuery->distinct()->pluck('name'));
        }

        if ($normalizedType === '' || $normalizedType === 'admin') {
            $adminsQuery = AdminRole::query()
                ->whereNotNull('name')
                ->where(function ($query) {
                    $query->where('super_admin', false)->orWhereNull('super_admin');
                });
            if ($campus) {
                $adminsQuery->whereRaw('LOWER(TRIM(admin_of)) = ?', [strtolower(trim($campus))]);
            }
            $users = $users->merge($adminsQuery->distinct()->pluck('name'));
        }

        if ($normalizedType === '' || $normalizedType === 'super_admin') {
            $users = $users->merge($this->getSuperAdminAccountNames());
        }

        if ($normalizedType === 'super_admin') {
            $users = $users->merge($this->getSuperAdminNamesFromRecords($campus));
        }

        return $users->map(function ($name) {
            return trim((string) $name);
        })->filter(function ($name) {
            return $name !== '';
        })->unique()->sort()->values();
    }

    private function exportCsv($records): StreamedResponse
    {
        $filename = 'income_expense_report_' . now()->format('Ymd_His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($records): void {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['#', 'Type', 'Source', 'User', 'Campus', 'Title', 'Status', 'Paid', 'Discount', 'Remaining', 'Date', 'Method']);
            foreach ($records as $index => $record) {
                fputcsv($file, [
                    $index + 1,
                    $record['type'] ?? '',
                    $record['source'] ?? '',
                    $record['user'] ?? '',
                    $record['campus'] ?? '',
                    $record['title'] ?? '',
                    $record['status'] ?? '',
                    (float) ($record['amount'] ?? 0),
                    (float) ($record['discount'] ?? 0),
                    (float) ($record['remaining'] ?? 0),
                    $record['date'] ?? '',
                    $record['method'] ?? '',
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function buildReportData(Request $request): array
    {
        $filterCampus = $request->get('filter_campus');
        $filterUserType = strtolower(trim((string) $request->get('filter_user_type')));
        $filterUser = $request->get('filter_user');
        $filterFromDate = $request->get('filter_from_date');
        $filterToDate = $request->get('filter_to_date');

        $campuses = Campus::orderBy('campus_name', 'asc')->pluck('campus_name');
        if ($campuses->isEmpty()) {
            $campusesFromPayments = StudentPayment::whereNotNull('campus')->distinct()->pluck('campus');
            $campusesFromCustom = CustomPayment::whereNotNull('campus')->distinct()->pluck('campus');
            $campusesFromExpenses = ManagementExpense::whereNotNull('campus')->distinct()->pluck('campus');
            $campuses = $campusesFromPayments->merge($campusesFromCustom)->merge($campusesFromExpenses)->unique()->sort()->values();
        }

        $userTypeOptions = collect([
            ['value' => 'accountant', 'label' => 'Accountant'],
            ['value' => 'admin', 'label' => 'Admin'],
            ['value' => 'super_admin', 'label' => 'Super Admin'],
        ]);
        $users = $this->getUsersForType($filterUserType, $filterCampus);

        $expenseCategories = $this->buildExpenseCategoriesFromModule($filterCampus);
        $records = collect();

        if (in_array($filterUserType, ['', 'accountant', 'admin', 'super_admin'], true)) {
            $records = $records->merge(
                $this->buildStudentIncomeRecords($filterCampus, $filterUser, $filterFromDate, $filterToDate)
            );
            $records = $records->merge(
                $this->buildCustomIncomeRecords($filterCampus, $filterUser, $filterFromDate, $filterToDate)
            );
            $records = $records->merge(
                $this->buildExpenseRecords($expenseCategories, $filterCampus, $filterUserType, $filterUser, $filterFromDate, $filterToDate)
            );
            $records = $records->merge(
                $this->buildSalaryExpenseRecords(
                    $expenseCategories,
                    $filterCampus,
                    $filterUserType,
                    $filterUser,
                    $filterFromDate,
                    $filterToDate
                )
            );
        }

        $incomeRecords = $records->sortByDesc('date')->values();

        return compact(
            'campuses',
            'userTypeOptions',
            'users',
            'incomeRecords',
            'filterCampus',
            'filterUserType',
            'filterUser',
            'filterFromDate',
            'filterToDate'
        );
    }

    /**
     * Paid student fee collections (excludes Generated / unpaid ledger rows).
     */
    private function buildStudentIncomeRecords(
        ?string $filterCampus,
        ?string $filterUser,
        ?string $filterFromDate,
        ?string $filterToDate
    ): \Illuminate\Support\Collection {
        $query = StudentPayment::query()
            ->ledgerActive()
            ->where('method', '!=', 'Generated')
            ->where('method', '!=', 'Installment');

        $this->scopeStudentPaymentsCampus($query, $filterCampus);

        if ($filterUser) {
            $query->whereRaw('LOWER(TRIM(accountant)) = ?', [strtolower(trim((string) $filterUser))]);
        }
        if ($filterFromDate) {
            $query->whereDate('payment_date', '>=', $filterFromDate);
        }
        if ($filterToDate) {
            $query->whereDate('payment_date', '<=', $filterToDate);
        }

        $rows = collect();
        foreach ($query->orderByDesc('payment_date')->get() as $payment) {
            $cash = round((float) ($payment->payment_amount ?? 0), 2);
            $discount = round($this->effectivePaymentDiscount($payment), 2);

            if ($cash <= 0 && $discount <= 0) {
                continue;
            }

            $title = trim((string) ($payment->payment_title ?? ''));
            $studentCode = trim((string) ($payment->student_code ?? ''));
            $remaining = $studentCode !== '' && $title !== ''
                ? round(StudentPayment::remainingDueForTitle($studentCode, $title), 2)
                : 0.0;
            $status = $remaining > 0.01 ? 'Partial' : 'Paid';

            $feeHead = $this->normalizePaidFeeHead($title);
            $displayTitle = $title !== '' ? $title : $feeHead;
            if ($feeHead !== '' && $feeHead !== $title) {
                $displayTitle = $feeHead . ' - ' . $title;
            }

            $campus = $payment->campus;
            if (empty($campus) && $studentCode !== '') {
                $campus = Student::where('student_code', $studentCode)->value('campus');
            }

            $rows->push([
                'type' => 'Income',
                'source' => 'Fee Collection',
                'user' => trim((string) ($payment->accountant ?? '')) !== ''
                    ? $payment->accountant
                    : ($studentCode !== '' ? $studentCode : 'N/A'),
                'campus' => $campus,
                'title' => $displayTitle,
                'status' => $status,
                'amount' => $cash,
                'discount' => $discount,
                'remaining' => $remaining,
                'date' => $payment->payment_date,
                'method' => $payment->method,
            ]);
        }

        return $rows;
    }

    /**
     * Custom / other income entries (actual payments only).
     */
    private function buildCustomIncomeRecords(
        ?string $filterCampus,
        ?string $filterUser,
        ?string $filterFromDate,
        ?string $filterToDate
    ): \Illuminate\Support\Collection {
        $query = CustomPayment::query()
            ->where('method', '!=', 'Generated');

        if ($filterCampus) {
            $query->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim((string) $filterCampus))]);
        }
        if ($filterUser) {
            $query->whereRaw('LOWER(TRIM(accountant)) = ?', [strtolower(trim((string) $filterUser))]);
        }
        if ($filterFromDate) {
            $query->whereDate('payment_date', '>=', $filterFromDate);
        }
        if ($filterToDate) {
            $query->whereDate('payment_date', '<=', $filterToDate);
        }

        $rows = collect();
        foreach ($query->orderByDesc('payment_date')->get() as $payment) {
            if ($payment->isMirroredOnStudentLedger()) {
                continue;
            }

            $amount = (float) ($payment->payment_amount ?? 0);
            if ($amount <= 0) {
                continue;
            }

            $rows->push([
                'type' => 'Income',
                'source' => 'Custom Payment',
                'user' => $payment->accountant ?? 'N/A',
                'campus' => $payment->campus,
                'title' => $payment->payment_title,
                'status' => 'Paid',
                'amount' => round($amount, 2),
                'discount' => 0.0,
                'remaining' => 0.0,
                'date' => $payment->payment_date,
                'method' => $payment->method,
            ]);
        }

        return $rows;
    }

    /**
     * Expenses from Management Expense, matched to Expense Management categories only.
     */
    private function buildExpenseRecords(
        \Illuminate\Support\Collection $expenseCategories,
        ?string $filterCampus,
        string $filterUserType,
        ?string $filterUser,
        ?string $filterFromDate,
        ?string $filterToDate
    ): \Illuminate\Support\Collection {
        if ($expenseCategories->isEmpty()) {
            return collect();
        }

        $query = ManagementExpense::query();
        $hasExpenseAccountantColumn = Schema::hasColumn('management_expenses', 'accountant');

        if ($filterCampus) {
            $query->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim((string) $filterCampus))]);
        }
        if ($filterUser) {
            $userKey = strtolower(trim((string) $filterUser));
            if ($filterUserType === 'super_admin') {
                $query->whereRaw('LOWER(TRIM(created_by)) = ?', [$userKey]);
            } elseif (($filterUserType === 'accountant' || $filterUserType === 'admin') && $hasExpenseAccountantColumn) {
                $query->whereRaw('LOWER(TRIM(accountant)) = ?', [$userKey]);
            } else {
                $query->where(function ($q) use ($userKey) {
                    $q->whereRaw('LOWER(TRIM(created_by)) = ?', [$userKey])
                        ->orWhereRaw('LOWER(TRIM(category)) LIKE ?', ['%' . $userKey . '%'])
                        ->orWhereRaw('LOWER(TRIM(title)) LIKE ?', ['%' . $userKey . '%']);
                });
            }
        }
        if ($filterFromDate) {
            $query->whereDate('date', '>=', $filterFromDate);
        }
        if ($filterToDate) {
            $query->whereDate('date', '<=', $filterToDate);
        }

        $rows = collect();
        foreach ($query->orderByDesc('date')->get() as $expense) {
            $matchedCategory = $this->resolveExpenseCategoryForExpense(
                (string) ($expense->category ?? ''),
                $expenseCategories
            );
            if ($matchedCategory === null) {
                continue;
            }

            $amount = (float) ($expense->amount ?? 0);
            if ($amount <= 0) {
                continue;
            }

            $title = trim((string) ($expense->title ?? ''));
            $displayTitle = $title !== '' ? $title : $matchedCategory;

            $rows->push([
                'type' => 'Expense',
                'source' => 'Expense - ' . $matchedCategory,
                'user' => $expense->created_by ?? $matchedCategory,
                'campus' => $expense->campus,
                'title' => $displayTitle,
                'amount' => round($amount, 2),
                'date' => $expense->date,
                'method' => $expense->method,
            ]);
        }

        return $rows;
    }

    /**
     * Paid staff salaries as expense rows.
     */
    private function buildSalaryExpenseRecords(
        \Illuminate\Support\Collection $expenseCategories,
        ?string $filterCampus,
        string $filterUserType,
        ?string $filterUser,
        ?string $filterFromDate,
        ?string $filterToDate
    ): \Illuminate\Support\Collection {
        $salaryHead = $this->resolveSalaryExpenseHead($expenseCategories);
        $salaryDateSql = Salary::effectivePaymentDateExpression();
        $hasSalaryPaymentMethod = Schema::hasColumn('salaries', 'payment_method');

        $query = Salary::query()
            ->with('staff')
            ->where('status', 'Paid')
            ->where('amount_paid', '>', 0)
            ->whereHas('staff');

        if ($filterCampus) {
            $campusKey = strtolower(trim((string) $filterCampus));
            $query->whereHas('staff', function ($q) use ($campusKey) {
                $q->whereRaw('LOWER(TRIM(campus)) = ?', [$campusKey]);
            });
        }
        $this->applySalaryPayerScopeFilter($query, $filterUserType, $filterUser, $filterCampus);
        if ($filterFromDate) {
            $query->whereRaw("DATE({$salaryDateSql}) >= ?", [$filterFromDate]);
        }
        if ($filterToDate) {
            $query->whereRaw("DATE({$salaryDateSql}) <= ?", [$filterToDate]);
        }

        $rows = collect();
        foreach ($query->orderByDesc(DB::raw($salaryDateSql))->get() as $salary) {
            $amount = (float) ($salary->amount_paid ?? 0);
            if ($amount <= 0) {
                continue;
            }

            $staff = $salary->staff;
            $staffName = trim((string) ($staff?->name ?? 'Staff'));
            $period = trim((string) ($salary->salary_month ?? '') . ' ' . (string) ($salary->year ?? ''));
            $title = 'Salary - ' . $staffName . ($period !== '' ? " ({$period})" : '');

            $rows->push([
                'type' => 'Expense',
                'source' => 'Expense - ' . $salaryHead,
                'user' => trim((string) ($salary->paid_by_name ?? '')) !== ''
                    ? $salary->paid_by_name
                    : $staffName,
                'campus' => $staff?->campus ?? ($filterCampus ?: 'N/A'),
                'title' => $title,
                'status' => 'Paid',
                'amount' => round($amount, 2),
                'discount' => 0.0,
                'remaining' => 0.0,
                'date' => $salary->payment_date ?? $salary->updated_at ?? $salary->created_at,
                'method' => $hasSalaryPaymentMethod ? ($salary->payment_method ?? 'N/A') : 'N/A',
            ]);
        }

        return $rows;
    }

    private function applySalaryPayerScopeFilter(
        $query,
        string $filterUserType,
        ?string $filterUser,
        ?string $filterCampus
    ): void {
        $scopedNames = $this->resolveSalaryScopedUserNames($filterUserType, $filterUser, $filterCampus);
        if ($scopedNames === null) {
            return;
        }

        if ($scopedNames === []) {
            $query->whereRaw('1 = 0');

            return;
        }

        Salary::applyPayerScopeFilter(
            $query,
            $scopedNames,
            $filterUserType,
            $filterUser,
            $this->superAdminNameKeys()
        );
    }

    /**
     * @return list<string>
     */
    private function superAdminNameKeys(): array
    {
        return $this->getSuperAdminAccountNames()
            ->map(fn ($name) => strtolower(trim((string) $name)))
            ->filter(fn ($name) => $name !== '')
            ->unique()
            ->values()
            ->all();
    }

    private function resolveSalaryScopedUserNames(
        string $filterUserType,
        ?string $filterUser,
        ?string $filterCampus
    ): ?array {
        if ($filterUser) {
            $name = strtolower(trim((string) $filterUser));

            return $name === '' ? [] : [$name];
        }

        $userType = strtolower(trim($filterUserType));
        if ($userType === '') {
            return null;
        }

        return $this->getUsersForType($userType, $filterCampus)
            ->map(fn ($name) => strtolower(trim((string) $name)))
            ->filter(fn ($name) => $name !== '')
            ->unique()
            ->values()
            ->all();
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

    /**
     * Expense categories from Expense Management module only.
     */
    private function buildExpenseCategoriesFromModule(?string $filterCampus): \Illuminate\Support\Collection
    {
        $query = ExpenseCategory::query()
            ->whereNotNull('category_name')
            ->whereRaw('TRIM(category_name) != ?', ['']);

        if ($filterCampus) {
            $query->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim((string) $filterCampus))]);
        }

        return $query
            ->orderBy('category_name')
            ->pluck('category_name')
            ->map(fn ($name) => trim((string) $name))
            ->filter(fn ($name) => $name !== '')
            ->unique(fn ($name) => strtolower($name))
            ->values();
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

    private function scopeStudentPaymentsCampus($query, ?string $filterCampus): void
    {
        if (!$filterCampus) {
            return;
        }

        $campusLower = strtolower(trim($filterCampus));
        $studentCodes = Student::query()
            ->whereRaw('LOWER(TRIM(campus)) = ?', [$campusLower])
            ->whereNotNull('student_code')
            ->pluck('student_code')
            ->filter()
            ->values();

        $query->where(function ($q) use ($campusLower, $studentCodes) {
            $q->whereRaw('LOWER(TRIM(campus)) = ?', [$campusLower]);
            if ($studentCodes->isNotEmpty()) {
                $q->orWhereIn('student_code', $studentCodes);
            }
        });
    }

    private function getSuperAdminAccountNames()
    {
        return AdminRole::query()
            ->whereNotNull('name')
            ->whereRaw('TRIM(name) != ?', [''])
            ->where('super_admin', true)
            ->distinct()
            ->pluck('name');
    }

    /**
     * Super admins who recorded income/expense at the selected campus (for User dropdown).
     */
    private function getSuperAdminNamesFromRecords(?string $campus)
    {
        $superAdminKeys = $this->getSuperAdminAccountNames()
            ->map(fn ($name) => strtolower(trim((string) $name)))
            ->filter(fn ($name) => $name !== '')
            ->unique()
            ->values();

        if ($superAdminKeys->isEmpty()) {
            return collect();
        }

        $campusKey = $campus ? strtolower(trim((string) $campus)) : null;
        $recordNames = collect();

        $paymentQuery = StudentPayment::query()
            ->ledgerActive()
            ->whereNotNull('accountant')
            ->whereRaw("TRIM(accountant) != ''");
        if ($campusKey) {
            $paymentQuery->whereRaw('LOWER(TRIM(campus)) = ?', [$campusKey]);
        }
        $recordNames = $recordNames->merge($paymentQuery->distinct()->pluck('accountant'));

        $customQuery = CustomPayment::query()
            ->whereNotNull('accountant')
            ->whereRaw("TRIM(accountant) != ''");
        if ($campusKey) {
            $customQuery->whereRaw('LOWER(TRIM(campus)) = ?', [$campusKey]);
        }
        $recordNames = $recordNames->merge($customQuery->distinct()->pluck('accountant'));

        $expenseQuery = ManagementExpense::query()
            ->whereNotNull('created_by')
            ->whereRaw("TRIM(created_by) != ''");
        if ($campusKey) {
            $expenseQuery->whereRaw('LOWER(TRIM(campus)) = ?', [$campusKey]);
        }
        $recordNames = $recordNames->merge($expenseQuery->distinct()->pluck('created_by'));

        return $recordNames
            ->map(fn ($name) => trim((string) $name))
            ->filter(fn ($name) => $name !== '' && $superAdminKeys->contains(strtolower($name)))
            ->unique()
            ->values();
    }

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
