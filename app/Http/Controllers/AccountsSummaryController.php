<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\StudentPayment;
use App\Models\CustomPayment;
use App\Models\ManagementExpense;
use App\Models\ExpenseCategory;
use App\Models\Salary;
use App\Models\ClassModel;
use App\Models\Section;
use App\Models\Campus;
use App\Models\GeneralSetting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AccountsSummaryController extends Controller
{
    /**
     * Display the accounts summary reports with filters.
     */
    public function index(Request $request): View
    {
        return view('reports.accounts-summary', $this->buildReportData($request));
    }

    public function export(Request $request, string $format)
    {
        $data = $this->buildReportData($request);
        $records = $data['summaryRecords'];

        if ($format === 'csv') {
            return $this->exportCsv($records, $data['filterType']);
        }

        if ($format === 'pdf') {
            $pdfData = array_merge($data, [
                'settings' => GeneralSetting::getSettings(),
                'printedAt' => now()->format('d M Y, h:i A'),
            ]);

            if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
                $pdf = Pdf::loadView('reports.accounts-summary-pdf', $pdfData);
                return $pdf->stream('accounts_summary_' . now()->format('Ymd_His') . '.pdf');
            }

            return response()->view('reports.accounts-summary-pdf', $pdfData);
        }

        return redirect()->route($this->accountsSummaryRouteName())->with('error', 'Invalid export format.');
    }

    private function accountsSummaryRouteName(): string
    {
        return Auth::guard('accountant')->check()
            ? 'accountant.accounts-summary'
            : 'reports.accounts-summary';
    }

    public function print(Request $request): View
    {
        return view('reports.accounts-summary-print', array_merge($this->buildReportData($request), [
            'settings' => GeneralSetting::getSettings(),
            'printedAt' => now()->format('d M Y, h:i A'),
        ]));
    }

    private function exportCsv($records, string $filterType): StreamedResponse
    {
        $filename = 'accounts_summary_' . now()->format('Ymd_His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($records, $filterType): void {
            $file = fopen('php://output', 'w');
            $isDayByDay = ($filterType ?? 'day_by_day') === 'day_by_day';

            $header = ['#', 'Campus', 'Month'];
            if ($isDayByDay) {
                $header[] = 'Date';
            }
            $header = array_merge($header, ['Cash Income', 'Discount', 'Total Expense', 'Profit/Lose', 'Year']);
            fputcsv($file, $header);

            foreach ($records as $index => $record) {
                $row = [
                    $index + 1,
                    $record['campus'] ?? '',
                    $record['month'] ?? '',
                ];
                if ($isDayByDay) {
                    $row[] = $record['date'] ?? '';
                }
                $row[] = (float) ($record['total_income'] ?? 0);
                $row[] = (float) ($record['total_discount'] ?? 0);
                $row[] = (float) ($record['total_expense'] ?? 0);
                $row[] = (float) ($record['profit_loss'] ?? 0);
                $row[] = $record['year'] ?? '';
                fputcsv($file, $row);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function buildReportData(Request $request): array
    {
        // Get filter values
        $filterType = $request->get('filter_type', 'day_by_day');
        $filterCampus = $request->get('filter_campus');
        $filterMonth = $request->get('filter_month');
        $filterYear = $request->get('filter_year');

        if (Auth::guard('accountant')->check()) {
            $accountantCampus = Auth::guard('accountant')->user()->campus ?? null;
            if ($accountantCampus) {
                $filterCampus = $accountantCampus;
                $request->merge(['filter_campus' => $accountantCampus]);
            }
        }

        // Get campuses from Campus model
        $campuses = Campus::orderBy('campus_name', 'asc')->get();
        if (Auth::guard('accountant')->check()) {
            $accountantCampus = Auth::guard('accountant')->user()->campus ?? null;
            if ($accountantCampus) {
                $campuses = $campuses->filter(function ($campus) use ($accountantCampus) {
                    $name = $campus->campus_name ?? $campus;

                    return $name === $accountantCampus;
                })->values();
            }
        }
        if ($campuses->isEmpty()) {
            // Fallback: get from other sources
            $campusesFromPayments = StudentPayment::whereNotNull('campus')->distinct()->pluck('campus');
            $campusesFromCustom = CustomPayment::whereNotNull('campus')->distinct()->pluck('campus');
            $campusesFromExpenses = ManagementExpense::whereNotNull('campus')->distinct()->pluck('campus');
            $campusesFromClasses = ClassModel::whereNotNull('campus')->distinct()->pluck('campus');
            $campusesFromSections = Section::whereNotNull('campus')->distinct()->pluck('campus');
            $allCampuses = $campusesFromPayments->merge($campusesFromCustom)->merge($campusesFromExpenses)
                ->merge($campusesFromClasses)->merge($campusesFromSections)->unique()->sort()->values();
            
            $campuses = $allCampuses->map(function($campusName) {
                return (object)['campus_name' => $campusName, 'id' => null];
            });
        }

        // Month options
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

        // Year options (current year and previous 5 years)
        $currentYear = date('Y');
        $years = collect();
        for ($i = 0; $i < 6; $i++) {
            $years->push($currentYear - $i);
        }

        // Prepare summary records
        $summaryRecords = collect();

        // Income — paid fees (partial payments included; cash + discount separate)
        $studentPaymentsQuery = StudentPayment::query()
            ->ledgerActive()
            ->where('method', '!=', 'Generated');
        $this->scopeStudentPaymentsCampus($studentPaymentsQuery, $filterCampus);
        if ($filterMonth) {
            $studentPaymentsQuery->whereMonth('payment_date', $filterMonth);
        }
        if ($filterYear) {
            $studentPaymentsQuery->whereYear('payment_date', $filterYear);
        }

        $cashByDate = collect();
        $discountByDate = collect();

        foreach ($studentPaymentsQuery->get() as $payment) {
            if (empty($payment->payment_date)) {
                continue;
            }

            $date = \Carbon\Carbon::parse($payment->payment_date)->format('Y-m-d');
            $cash = round(max(0, (float) ($payment->payment_amount ?? 0)), 2);
            $discount = round($this->effectivePaymentDiscount($payment), 2);

            if ($cash <= 0 && $discount <= 0) {
                continue;
            }

            $cashByDate[$date] = ($cashByDate[$date] ?? 0) + $cash;
            $discountByDate[$date] = ($discountByDate[$date] ?? 0) + $discount;
        }

        $incomeByDate = $cashByDate;

        // Income Summary - Custom Payments
        $customPaymentsQuery = CustomPayment::query();
        if ($filterCampus) {
            $customPaymentsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($filterCampus))]);
        }
        if ($filterMonth) {
            $customPaymentsQuery->whereMonth('payment_date', $filterMonth);
        }
        if ($filterYear) {
            $customPaymentsQuery->whereYear('payment_date', $filterYear);
        }
        $customPayments = $customPaymentsQuery->get()
            ->filter(fn (CustomPayment $payment) => ! $payment->isMirroredOnStudentLedger());
        
        $customIncomeByDate = $customPayments->groupBy(function($payment) {
            return \Carbon\Carbon::parse($payment->payment_date)->format('Y-m-d');
        })->map(function($payments) {
            return $payments->sum('payment_amount');
        });

        $customIncomeByDate->each(function($amount, $date) use (&$incomeByDate) {
            $incomeByDate[$date] = ($incomeByDate[$date] ?? 0) + $amount;
        });

        // Expense Summary — categories from Expense Management module
        $expenseCategories = $this->buildExpenseCategoriesFromModule($filterCampus);
        $expensesQuery = ManagementExpense::query();
        if ($filterCampus) {
            $expensesQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim((string) $filterCampus))]);
        }
        if ($filterMonth) {
            $expensesQuery->whereMonth('date', $filterMonth);
        }
        if ($filterYear) {
            $expensesQuery->whereYear('date', $filterYear);
        }

        $expenseByDate = collect();
        foreach ($expensesQuery->get() as $expense) {
            if (empty($expense->date)) {
                continue;
            }
            if ($expenseCategories->isNotEmpty()) {
                $matched = $this->resolveExpenseCategoryForExpense(
                    (string) ($expense->category ?? ''),
                    $expenseCategories
                );
                if ($matched === null) {
                    continue;
                }
            }

            $amount = (float) ($expense->amount ?? 0);
            if ($amount <= 0) {
                continue;
            }

            $date = \Carbon\Carbon::parse($expense->date)->format('Y-m-d');
            $expenseByDate[$date] = ($expenseByDate[$date] ?? 0) + $amount;
        }

        // Expense Summary - Paid Salaries (from salary module)
        $salaryQuery = Salary::query()
            ->with('staff')
            ->where('status', 'Paid')
            ->where('amount_paid', '>', 0);

        $salaryConnection = (new Salary())->getConnectionName() ?: config('database.default');
        $salaryDateColumn = Schema::connection($salaryConnection)->hasColumn('salaries', 'payment_date')
            ? 'payment_date'
            : 'updated_at';

        if ($filterCampus) {
            $salaryQuery->whereHas('staff', function ($q) use ($filterCampus) {
                $q->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($filterCampus))]);
            });
        }
        if ($filterMonth) {
            $salaryQuery->whereMonth($salaryDateColumn, $filterMonth);
        }
        if ($filterYear) {
            $salaryQuery->whereYear($salaryDateColumn, $filterYear);
        }

        $paidSalaries = $salaryQuery->get();

        $salaryExpenseByDate = $paidSalaries->groupBy(function ($salary) {
            $date = $salary->payment_date ?? $salary->updated_at ?? $salary->created_at;
            return \Carbon\Carbon::parse($date)->format('Y-m-d');
        })->map(function ($salaryGroup) {
            return $salaryGroup->sum('amount_paid');
        });

        $salaryExpenseByDate->each(function ($amount, $date) use (&$expenseByDate) {
            $expenseByDate[$date] = ($expenseByDate[$date] ?? 0) + $amount;
        });

        if ($filterType === 'month_by_month') {
            $incomeByMonth = collect();
            $discountByMonth = collect();
            $expenseByMonth = collect();

            $incomeByDate->each(function ($amount, $date) use (&$incomeByMonth) {
                $monthKey = \Carbon\Carbon::parse($date)->format('Y-m');
                $incomeByMonth[$monthKey] = ($incomeByMonth[$monthKey] ?? 0) + $amount;
            });

            $discountByDate->each(function ($amount, $date) use (&$discountByMonth) {
                $monthKey = \Carbon\Carbon::parse($date)->format('Y-m');
                $discountByMonth[$monthKey] = ($discountByMonth[$monthKey] ?? 0) + $amount;
            });

            $expenseByDate->each(function ($amount, $date) use (&$expenseByMonth) {
                $monthKey = \Carbon\Carbon::parse($date)->format('Y-m');
                $expenseByMonth[$monthKey] = ($expenseByMonth[$monthKey] ?? 0) + $amount;
            });

            $allMonths = $incomeByMonth->keys()
                ->merge($discountByMonth->keys())
                ->merge($expenseByMonth->keys())
                ->unique()
                ->sortDesc()
                ->values();

            foreach ($allMonths as $monthKey) {
                $dateObj = \Carbon\Carbon::createFromFormat('Y-m', $monthKey);
                $totalIncome = (float) ($incomeByMonth[$monthKey] ?? 0);
                $totalDiscount = (float) ($discountByMonth[$monthKey] ?? 0);
                $totalExpense = (float) ($expenseByMonth[$monthKey] ?? 0);
                $summaryRecords->push([
                    'campus' => $filterCampus ?: 'All Campuses',
                    'month' => $dateObj->format('F'),
                    'date' => null,
                    'total_income' => round($totalIncome, 2),
                    'total_discount' => round($totalDiscount, 2),
                    'total_expense' => round($totalExpense, 2),
                    'profit_loss' => round($totalIncome - $totalExpense, 2),
                    'year' => $dateObj->format('Y'),
                ]);
            }
        } else {
            $allDates = $incomeByDate->keys()
                ->merge($discountByDate->keys())
                ->merge($expenseByDate->keys())
                ->unique()
                ->sortDesc()
                ->values();

            foreach ($allDates as $date) {
                $dateObj = \Carbon\Carbon::parse($date);
                $totalIncome = (float) ($incomeByDate[$date] ?? 0);
                $totalDiscount = (float) ($discountByDate[$date] ?? 0);
                $totalExpense = (float) ($expenseByDate[$date] ?? 0);
                $summaryRecords->push([
                    'campus' => $filterCampus ?: 'All Campuses',
                    'month' => $dateObj->format('F'),
                    'date' => $dateObj->format('d M Y'),
                    'total_income' => round($totalIncome, 2),
                    'total_discount' => round($totalDiscount, 2),
                    'total_expense' => round($totalExpense, 2),
                    'profit_loss' => round($totalIncome - $totalExpense, 2),
                    'year' => $dateObj->format('Y'),
                ]);
            }
        }

        $summaryTotals = [
            'income' => round((float) $summaryRecords->sum('total_income'), 2),
            'discount' => round((float) $summaryRecords->sum('total_discount'), 2),
            'expense' => round((float) $summaryRecords->sum('total_expense'), 2),
            'profit' => round((float) $summaryRecords->sum('profit_loss'), 2),
        ];

        return compact(
            'campuses',
            'months',
            'years',
            'summaryRecords',
            'summaryTotals',
            'filterType',
            'filterCampus',
            'filterMonth',
            'filterYear'
        ) + ['defaultCampus' => null];
    }

    /**
     * Include payments for campus even when payment.campus is empty (partial pay rows).
     */
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
}

