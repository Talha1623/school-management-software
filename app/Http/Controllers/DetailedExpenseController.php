<?php

namespace App\Http\Controllers;

use App\Models\Campus;
use App\Models\ExpenseCategory;
use App\Models\GeneralSetting;
use App\Models\AdminRole;
use App\Models\ManagementExpense;
use App\Models\Salary;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DetailedExpenseController extends Controller
{
    public function index(Request $request): View
    {
        return view('reports.detailed-expense', $this->buildReportData($request));
    }

    public function export(Request $request, string $format)
    {
        $data = $this->buildReportData($request);
        $records = $data['expenseRecords'];

        if (!in_array($format, ['excel', 'csv', 'pdf'], true)) {
            return redirect()->route($this->detailedExpenseRouteName())->with('error', 'Invalid export format.');
        }

        if ($format === 'pdf') {
            $pdfData = array_merge($data, [
                'settings' => GeneralSetting::getSettings(),
                'printedAt' => now()->format('d M Y, h:i A'),
            ]);

            if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
                $pdf = Pdf::loadView('reports.detailed-expense-pdf', $pdfData);

                return $pdf->stream('detailed_expense_' . now()->format('Ymd_His') . '.pdf');
            }

            return response()->view('reports.detailed-expense-pdf', $pdfData);
        }

        return $this->exportSpreadsheet($records, $data, $format);
    }

    public function print(Request $request): View
    {
        return view('reports.detailed-expense-print', array_merge($this->buildReportData($request), [
            'settings' => GeneralSetting::getSettings(),
            'printedAt' => now()->format('d M Y, h:i A'),
        ]));
    }

    public function getMethodsByCampus(Request $request): JsonResponse
    {
        if (Auth::guard('accountant')->check()) {
            $accountantCampus = Auth::guard('accountant')->user()->campus ?? null;
            if ($accountantCampus) {
                $request->merge(['filter_campus' => $accountantCampus]);
            }
        }

        return response()->json([
            'methods' => $this->resolveAvailableMethods($request->get('filter_campus')),
        ]);
    }

    private function detailedExpenseRouteName(): string
    {
        return Auth::guard('accountant')->check()
            ? 'accountant.detailed-expense'
            : 'reports.detailed-expense';
    }

    private function exportSpreadsheet($records, array $data, string $format): StreamedResponse
    {
        $isExcel = $format === 'excel';
        $filename = 'detailed_expense_' . now()->format('Ymd_His') . ($isExcel ? '.xls' : '.csv');
        $headers = [
            'Content-Type' => $isExcel ? 'application/vnd.ms-excel' : 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($records, $data, $isExcel): void {
            $file = fopen('php://output', 'w');
            if ($isExcel) {
                fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));
            }

            fputcsv($file, ['#', 'Title', 'Categories', 'Accountant', 'Amount', 'Date & Time', 'Description', 'Method']);

            foreach ($records as $index => $record) {
                $formattedDate = !empty($record['date'])
                    ? \Carbon\Carbon::parse($record['date'])->format('d-m-Y h:i A')
                    : 'N/A';

                fputcsv($file, [
                    $index + 1,
                    $record['title'] ?? 'N/A',
                    $record['category'] ?? 'N/A',
                    $record['accountant'] ?? 'N/A',
                    (float) ($record['amount'] ?? 0),
                    $formattedDate,
                    $record['description'] ?? 'N/A',
                    $record['method'] ?? 'N/A',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function buildReportData(Request $request): array
    {
        $filterCampus = $request->get('filter_campus');
        $filterMonth = $request->get('filter_month');
        $filterDate = $request->get('filter_date');
        $filterYear = $request->get('filter_year');
        $filterMethod = $request->get('filter_method');

        if (Auth::guard('accountant')->check()) {
            $accountantCampus = Auth::guard('accountant')->user()->campus ?? null;
            if ($accountantCampus) {
                $filterCampus = $accountantCampus;
                $request->merge(['filter_campus' => $accountantCampus]);
            }
        }

        $campuses = Campus::orderBy('campus_name', 'asc')->pluck('campus_name');
        if (Auth::guard('accountant')->check()) {
            $accountantCampus = Auth::guard('accountant')->user()->campus ?? null;
            if ($accountantCampus) {
                $campuses = $campuses->filter(fn ($campus) => $campus === $accountantCampus)->values();
            }
        }
        if ($campuses->isEmpty()) {
            $campuses = ManagementExpense::whereNotNull('campus')->distinct()->pluck('campus')->sort()->values();
        }

        $categories = ExpenseCategory::whereNotNull('category_name')->distinct()->pluck('category_name')->sort()->values();
        if ($categories->isEmpty()) {
            $categories = collect(['Office Supplies', 'Utilities', 'Maintenance', 'Transportation', 'Other']);
        }

        $months = collect([
            '01' => 'January',
            '02' => 'February',
            '03' => 'March',
            '04' => 'April',
            '05' => 'May',
            '06' => 'June',
            '07' => 'July',
            '08' => 'August',
            '09' => 'September',
            '10' => 'October',
            '11' => 'November',
            '12' => 'December',
        ]);

        $currentYear = (int) date('Y');
        $years = collect();
        for ($i = 0; $i < 6; $i++) {
            $years->push($currentYear - $i);
        }

        $methodGroups = $this->expenseMethodGroups();
        $methods = $this->resolveAvailableMethods($filterCampus);

        $expensesQuery = ManagementExpense::query();
        if ($filterCampus) {
            $expensesQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim((string) $filterCampus))]);
        }
        if ($filterMonth) {
            $expensesQuery->whereMonth('date', $filterMonth);
        }
        if ($filterDate) {
            $expensesQuery->whereDate('date', $filterDate);
        }
        if ($filterYear) {
            $expensesQuery->whereYear('date', $filterYear);
        }
        if ($filterMethod) {
            $this->applyExpenseMethodFilter($expensesQuery, (string) $filterMethod, $methodGroups, 'method');
        }

        $expenses = $expensesQuery->orderBy('date', 'desc')->get();

        $expenseRecords = collect();
        foreach ($expenses as $expense) {
            $expenseRecords->push([
                'id' => $expense->id,
                'record_type' => 'expense',
                'expense_id' => $expense->id,
                'salary_id' => null,
                'campus' => $expense->campus,
                'category' => $expense->category,
                'title' => $expense->title,
                'description' => $expense->description,
                'amount' => $expense->amount,
                'method' => $this->canonicalizeExpenseMethod($expense->method, $methodGroups),
                'date' => $expense->date,
                'invoice_receipt' => $expense->invoice_receipt,
                'notify_admin' => $expense->notify_admin,
                'accountant' => $this->resolveExpensePayerName($expense),
            ]);
        }

        // Also include Paid Salaries as expenses (salary module).
        $salaryQuery = Salary::query()
            ->with('staff')
            ->where('status', 'Paid')
            ->where('amount_paid', '>', 0);

        $salaryConnection = (new Salary())->getConnectionName() ?: config('database.default');
        $salaryDateColumn = Schema::connection($salaryConnection)->hasColumn('salaries', 'payment_date')
            ? 'payment_date'
            : 'updated_at';
        $hasSalaryPaymentMethod = Schema::connection($salaryConnection)->hasColumn('salaries', 'payment_method');
        if ($filterCampus) {
            $campusKey = strtolower(trim((string) $filterCampus));
            $salaryQuery->whereHas('staff', function ($q) use ($campusKey) {
                $q->whereRaw('LOWER(TRIM(campus)) = ?', [$campusKey]);
            });
        }
        if ($filterMonth) {
            $salaryQuery->whereMonth($salaryDateColumn, $filterMonth);
        }
        if ($filterDate) {
            $salaryQuery->whereDate($salaryDateColumn, $filterDate);
        }
        if ($filterYear) {
            $salaryQuery->whereYear($salaryDateColumn, $filterYear);
        }
        $includeSalaries = true;
        if ($filterMethod) {
            if ($hasSalaryPaymentMethod) {
                $this->applyExpenseMethodFilter($salaryQuery, (string) $filterMethod, $methodGroups, 'payment_method');
            } else {
                $includeSalaries = false;
            }
        }

        if ($includeSalaries) {
            foreach ($salaryQuery->get() as $salary) {
            $staff = $salary->staff;
            $staffName = $staff?->name ?: 'Staff';
            $period = trim((string) ($salary->salary_month ?? '') . ' ' . (string) ($salary->year ?? ''));
            $title = 'Salary - ' . $staffName . ($period !== '' ? " ({$period})" : '');
            $expenseRecords->push([
                'id' => 'SAL-' . $salary->id,
                'record_type' => 'salary',
                'expense_id' => null,
                'salary_id' => $salary->id,
                'campus' => $staff?->campus ?? ($filterCampus ?: 'N/A'),
                'category' => 'Salary',
                'title' => $title,
                'description' => 'Paid salary',
                'amount' => (float) ($salary->amount_paid ?? 0),
                'method' => $this->canonicalizeExpenseMethod(
                    $hasSalaryPaymentMethod ? ($salary->payment_method ?? '') : '',
                    $methodGroups
                ),
                'date' => $salary->{$salaryDateColumn} ?? $salary->updated_at ?? $salary->created_at,
                'invoice_receipt' => null,
                'notify_admin' => null,
                'accountant' => $this->resolveSalaryPayerName($salary),
            ]);
            }
        }

        $expenseRecords = $expenseRecords->sortByDesc('date')->values();

        return compact(
            'campuses',
            'categories',
            'months',
            'years',
            'methods',
            'expenseRecords',
            'filterCampus',
            'filterMonth',
            'filterDate',
            'filterYear',
            'filterMethod'
        );
    }

    /**
     * @return array<string, list<string>>
     */
    private function expenseMethodGroups(): array
    {
        return [
            'Cash By Hand' => ['cash', 'cash payment', 'cash by hand', 'cash_by_hand'],
            'Bank Transfer' => ['bank', 'bank transfer', 'bank transfer payment', 'banks transfer'],
            'Wallet' => ['wallet', 'wallet payment'],
            'Online Transfer' => ['transfer', 'online transfer', 'online payment', 'online', 'online_transfer'],
            'Card Payment' => ['card', 'card payment', 'card pyment'],
            'Cheque' => ['check', 'cheque', 'cheque payment', 'Cheque', 'Check'],
            'All Deposit' => ['deposit', 'all deposit'],
        ];
    }

    private function canonicalizeExpenseMethod(?string $method, array $methodGroups): string
    {
        $key = strtolower(trim((string) $method));
        if ($key === '') {
            return 'N/A';
        }

        foreach ($methodGroups as $canonical => $variants) {
            if (in_array($key, $variants, true) || $key === strtolower(trim($canonical))) {
                return $canonical;
            }
        }

        return trim((string) $method) ?: 'N/A';
    }

    private function resolveAvailableMethods(?string $filterCampus)
    {
        $methodGroups = $this->expenseMethodGroups();
        $preferredMethodOrder = array_keys($methodGroups);

        $expenseMethodsQuery = ManagementExpense::query()
            ->whereNotNull('method')
            ->whereRaw("TRIM(method) != ''");
        if ($filterCampus) {
            $expenseMethodsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim((string) $filterCampus))]);
        }

        $methods = $expenseMethodsQuery
            ->distinct()
            ->pluck('method');

        $salaryConnection = (new Salary())->getConnectionName() ?: config('database.default');
        if (Schema::connection($salaryConnection)->hasColumn('salaries', 'payment_method')) {
            $salaryMethodsQuery = Salary::query()
                ->whereNotNull('payment_method')
                ->whereRaw("TRIM(payment_method) != ''")
                ->where('amount_paid', '>', 0);

            if ($filterCampus) {
                $campusKey = strtolower(trim((string) $filterCampus));
                $salaryMethodsQuery->whereHas('staff', function ($q) use ($campusKey) {
                    $q->whereRaw('LOWER(TRIM(campus)) = ?', [$campusKey]);
                });
            }

            $methods = $methods->merge($salaryMethodsQuery->distinct()->pluck('payment_method'));
        }

        $extraMethods = $methods
            ->map(fn ($method) => $this->canonicalizeExpenseMethod($method, $methodGroups))
            ->filter(fn ($method) => $method !== 'N/A')
            ->reject(fn ($method) => in_array($method, $preferredMethodOrder, true))
            ->unique(fn ($method) => strtolower(trim((string) $method)))
            ->values();

        return collect($preferredMethodOrder)
            ->merge($extraMethods)
            ->unique(fn ($method) => strtolower(trim((string) $method)))
            ->values();
    }

    private function applyExpenseMethodFilter($query, string $filterMethod, array $methodGroups, string $column = 'method'): void
    {
        $filterKey = strtolower(trim($filterMethod));
        if ($filterKey === '') {
            return;
        }

        $variants = null;
        foreach ($methodGroups as $canonical => $groupVariants) {
            if (strtolower(trim($canonical)) === $filterKey) {
                $variants = $groupVariants;
                break;
            }
        }

        if ($variants) {
            $query->where(function ($scopeQuery) use ($variants, $column) {
                foreach ($variants as $variant) {
                    $scopeQuery->orWhereRaw('LOWER(TRIM(' . $column . ')) = ?', [strtolower(trim($variant))]);
                }
            });

            return;
        }

        $query->whereRaw('LOWER(TRIM(' . $column . ')) = ?', [$filterKey]);
    }

    private function resolveExpensePayerName(ManagementExpense $expense): string
    {
        $createdBy = trim((string) ($expense->created_by ?? ''));
        if ($createdBy !== '') {
            return $createdBy;
        }

        $legacyAccountant = trim((string) ($expense->getAttribute('accountant') ?? ''));
        if ($legacyAccountant !== '') {
            return $legacyAccountant;
        }

        return 'N/A';
    }

    private function resolveSalaryPayerName(Salary $salary): string
    {
        Salary::ensurePaidByColumns();

        $paidByName = trim((string) ($salary->paid_by_name ?? ''));
        if ($paidByName !== '') {
            return $paidByName;
        }

        $paidByType = strtolower(trim((string) ($salary->paid_by_type ?? '')));
        if ($paidByType !== '') {
            return match ($paidByType) {
                'super_admin' => $this->defaultSuperAdminName(),
                'accountant' => 'Accountant',
                'admin' => 'Admin',
                default => ucwords(str_replace('_', ' ', $paidByType)),
            };
        }

        if ((float) ($salary->amount_paid ?? 0) > 0) {
            return $this->defaultSuperAdminName();
        }

        return 'N/A';
    }

    private function defaultSuperAdminName(): string
    {
        static $cachedName = null;

        if ($cachedName !== null) {
            return $cachedName;
        }

        $admin = AdminRole::query()
            ->where('super_admin', true)
            ->orderBy('id')
            ->first();

        if ($admin) {
            $name = trim((string) ($admin->name ?? $admin->email ?? ''));
            if ($name !== '') {
                return $cachedName = $name;
            }
        }

        return $cachedName = 'Super Admin';
    }
}
