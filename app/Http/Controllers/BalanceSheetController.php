<?php

namespace App\Http\Controllers;

use App\Models\StudentPayment;
use App\Models\CustomPayment;
use App\Models\ManagementExpense;
use App\Models\Salary;
use App\Models\Accountant;
use App\Models\AdminRole;
use App\Models\Campus;
use App\Models\BalanceSheetSettlement;
use App\Models\Message;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Auth;

class BalanceSheetController extends Controller
{
    /**
     * Display the balance sheet with filters.
     */
    public function index(Request $request): View
    {
        $this->ensureSettlementTableExists();

        // Get filter values
        $filterCampus = $request->get('filter_campus');
        $filterUserType = strtolower(trim((string) $request->get('filter_user_type'))); // accountant, admin, or super_admin
        $filterUser = $request->get('filter_user'); // Accountant/Admin name
        $filterDay = strtolower(trim((string) $request->get('filter_day', 'current_day')));
        $isParentWalletBalanceSheet = $request->routeIs('accounting.parent-wallet.print-balance-sheet');
        $isAccountantOwnLedger = $request->routeIs('accountant.print-balance-sheet');
        $includeCustomPayments = !$isParentWalletBalanceSheet;

        if ($isAccountantOwnLedger) {
            $portalAccountant = Auth::guard('accountant')->user();
            if ($portalAccountant) {
                $filterCampus = $filterCampus ?: ($portalAccountant->campus ? trim((string) $portalAccountant->campus) : null);
                $filterUserType = 'accountant';
                $filterUser = trim((string) ($portalAccountant->name ?? '')) ?: $filterUser;
            }
        }

        [$startDate, $endDate] = $this->resolveDayRange($filterDay);

        // Get campuses from Campus model first, then fallback to transactions
        $campuses = Campus::orderBy('campus_name', 'asc')->pluck('campus_name');
        if ($campuses->isEmpty()) {
            $campusesFromPayments = StudentPayment::whereNotNull('campus')->distinct()->pluck('campus');
            $campusesFromCustom = CustomPayment::whereNotNull('campus')->distinct()->pluck('campus');
            $campusesFromExpenses = ManagementExpense::whereNotNull('campus')->distinct()->pluck('campus');
            $campuses = $campusesFromPayments->merge($campusesFromCustom)->merge($campusesFromExpenses)->unique()->sort()->values();
        }

        $normalizedType = $filterUserType;
        $userTypeOptions = collect([
            ['value' => 'accountant', 'label' => 'Accountant'],
            ['value' => 'admin', 'label' => 'Admin'],
            ['value' => 'super_admin', 'label' => 'Super Admin'],
        ]);
        $users = $isAccountantOwnLedger && $filterUser
            ? collect([$filterUser])
            : $this->getUsersForType($normalizedType, $filterCampus);

        // Prepare balance sheet data
        $balanceRecords = collect();

        // Calculate Income (Credits)
        $totalIncome = 0;
        $incomeBreakdown = collect();

        // Student Payments (Income)
        $studentPaymentsQuery = StudentPayment::query()
            ->ledgerActive()
            ->whereNotIn('method', ['Generated', 'Installment']);
        if ($filterCampus) {
            $studentPaymentsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($filterCampus))]);
        }
        $this->applyUserScopeFilter($studentPaymentsQuery, $filterUserType, $filterUser, $filterCampus, 'accountant');
        $this->applyStudentPaymentDateRangeFilter($studentPaymentsQuery, $startDate, $endDate);
        $studentPayments = $studentPaymentsQuery->get();
        $studentPaymentsTotal = $studentPayments->sum('payment_amount');
        $totalIncome += $studentPaymentsTotal;

        // Detailed payment rows for tabular balance sheet (only paid transactions).
        $paymentEntriesQuery = StudentPayment::query()
            ->ledgerActive()
            ->leftJoin('students', function ($join) {
                $join->on(DB::raw('LOWER(TRIM(student_payments.student_code))'), '=', DB::raw('LOWER(TRIM(students.student_code))'));
            })
            ->where('student_payments.method', '!=', 'Generated')
            ->where('student_payments.method', '!=', 'Installment')
            ->select(
                'student_payments.student_code',
                'students.student_name',
                'students.class',
                'student_payments.payment_title',
                'student_payments.payment_amount'
            );
        if ($filterCampus) {
            $paymentEntriesQuery->whereRaw('LOWER(TRIM(student_payments.campus)) = ?', [strtolower(trim($filterCampus))]);
        }
        $this->applyUserScopeFilter($paymentEntriesQuery, $filterUserType, $filterUser, $filterCampus, 'student_payments.accountant');
        $this->applyStudentPaymentDateRangeFilter($paymentEntriesQuery, $startDate, $endDate, 'student_payments');
        $paymentEntries = $paymentEntriesQuery
            ->orderByRaw('COALESCE(student_payments.payment_date, student_payments.created_at) DESC')
            ->orderBy('student_payments.id', 'desc')
            ->get();
        
        if ($studentPaymentsTotal > 0) {
            $incomeBreakdown->push([
                'source' => 'Student Payments',
                'amount' => $studentPaymentsTotal,
            ]);
        }

        // Custom Payments are excluded on parent-wallet print balance sheet so totals match the Payments table.
        if ($includeCustomPayments) {
            $customPaymentsQuery = CustomPayment::query();
            if ($filterCampus) {
                $customPaymentsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($filterCampus))]);
            }
            $this->applyUserScopeFilter($customPaymentsQuery, $filterUserType, $filterUser, $filterCampus, 'accountant');
            $this->applyDateRangeFilter($customPaymentsQuery, 'created_at', $startDate, $endDate);
            $customPayments = $customPaymentsQuery->get()
                ->filter(fn (CustomPayment $payment) => ! $payment->isMirroredOnStudentLedger());
            $customPaymentsTotal = $customPayments->sum('payment_amount');
            $totalIncome += $customPaymentsTotal;

            if ($customPaymentsTotal > 0) {
                $incomeBreakdown->push([
                    'source' => 'Custom Payments',
                    'amount' => $customPaymentsTotal,
                ]);
            }
        }

        // Calculate Expenses (Debits) — only Teacher Salary + Add Management Expense
        $totalExpense = 0;
        $expenseBreakdown = collect();
        $expenseEntries = collect();

        $salaryExpenseRows = collect();
        if (! $isAccountantOwnLedger) {
            // 1) Teacher / staff salaries (school-wide; hidden on accountant portal)
            $salariesQuery = Salary::query()
                ->with('staff')
                ->where('amount_paid', '>', 0)
                ->whereHas('staff');

            if ($filterCampus) {
                $campusKey = strtolower(trim($filterCampus));
                $salariesQuery->whereHas('staff', function ($q) use ($campusKey) {
                    $q->whereRaw('LOWER(TRIM(campus)) = ?', [$campusKey]);
                });
            }

            $this->applySalaryUserScopeFilter($salariesQuery, $filterUserType, $filterUser, $filterCampus);
            $this->applySalaryDateRangeFilter($salariesQuery, $startDate, $endDate);
            $paidSalaries = $salariesQuery->orderByDesc('updated_at')->get();
            $salariesTotal = (float) $paidSalaries->sum('amount_paid');
            $totalExpense += $salariesTotal;

            $salaryExpenseRows = $paidSalaries->toBase()->map(function (Salary $salary) {
                $staff = $salary->staff;
                $staffName = trim((string) ($staff->name ?? 'Staff'));
                $period = trim(trim((string) $salary->salary_month) . ' ' . trim((string) $salary->year));
                $title = 'Teacher Salary - ' . $staffName;
                if ($period !== '') {
                    $title .= ' (' . $period . ')';
                }

                $paidAt = $salary->payment_date ?? $salary->updated_at;

                return [
                    'id' => 'SAL-' . $salary->id,
                    'title' => $title,
                    'amount' => (float) $salary->amount_paid,
                    'sort_timestamp' => $paidAt instanceof Carbon ? $paidAt->timestamp : (int) strtotime((string) $paidAt),
                ];
            })->values();

            if ($salariesTotal > 0) {
                $expenseBreakdown->push([
                    'source' => 'Teacher Salary',
                    'amount' => $salariesTotal,
                ]);
            }
        }

        // 2) Management expenses (Add Management Expense)
        $managementQuery = ManagementExpense::query();
        if ($filterCampus) {
            $managementQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($filterCampus))]);
        }
        $this->applyExpenseUserScopeFilter(
            $managementQuery,
            $filterUserType,
            $filterUser,
            $filterCampus,
            $isAccountantOwnLedger
        );

        $this->applyDateRangeFilter($managementQuery, 'date', $startDate, $endDate);
        $managementExpenses = $managementQuery->orderByDesc('date')->orderByDesc('id')->get();
        $managementTotal = (float) $managementExpenses->sum('amount');
        $totalExpense += $managementTotal;

        $managementExpenseRows = $managementExpenses->toBase()->map(function (ManagementExpense $expense) {
            $detail = trim((string) ($expense->title ?? ''));
            if ($detail === '') {
                $detail = trim((string) ($expense->category ?? 'Expense'));
            }

            $expenseDate = $expense->date ?? $expense->created_at;

            return [
                'id' => 'EXP-' . $expense->id,
                'title' => 'Management Expense - ' . $detail,
                'amount' => (float) $expense->amount,
                'sort_timestamp' => $expenseDate instanceof Carbon
                    ? $expenseDate->timestamp
                    : (int) strtotime((string) $expenseDate),
            ];
        })->values();

        if ($managementTotal > 0) {
            $expenseBreakdown->push([
                'source' => 'Management Expense',
                'amount' => $managementTotal,
            ]);
        }

        $expenseEntries = collect()
            ->concat($salaryExpenseRows)
            ->concat($managementExpenseRows)
            ->sortByDesc('sort_timestamp')
            ->values();

        $previousUnsettledAmount = $this->computePreviousUnsettledAmount(
            $startDate,
            $filterCampus,
            $filterUserType,
            $filterUser,
            $includeCustomPayments,
            $isAccountantOwnLedger
        );
        $cashInHand = $totalIncome - $totalExpense + $previousUnsettledAmount;

        // Prepare balance sheet summary
        $balanceSheet = [
            'total_income' => $totalIncome,
            'total_expense' => $totalExpense,
            'previous_unsettled' => $previousUnsettledAmount,
            'cash_in_hand' => $cashInHand,
            'net_balance' => $cashInHand,
            'income_breakdown' => $incomeBreakdown,
            'expense_breakdown' => $expenseBreakdown,
        ];

        $settlementRecords = $this->fetchSettlementsForFilters($startDate, $filterCampus, $filterUserType, $filterUser);
        $isFullySettled = $this->isScopeSettled(
            $startDate,
            $filterCampus,
            $filterUserType,
            $filterUser,
            $includeCustomPayments,
            $isAccountantOwnLedger
        );

        return view('reports.balance-sheet', array_merge(
            compact(
                'campuses',
                'userTypeOptions',
                'users',
                'balanceSheet',
                'isFullySettled',
                'settlementRecords',
                'paymentEntries',
                'expenseEntries',
                'filterCampus',
                'filterUserType',
                'filterUser',
                'filterDay'
            ),
            $this->balanceSheetViewOptions($request, $isAccountantOwnLedger ?? false)
        ));
    }

    /**
     * Accountant portal — own ledger only (campus + logged-in accountant name).
     */
    public function accountantIndex(Request $request): View
    {
        $accountant = Auth::guard('accountant')->user();
        $defaultCampus = $accountant?->campus ? trim((string) $accountant->campus) : null;
        $accountantName = trim((string) ($accountant->name ?? ''));

        $request->merge(array_filter([
            'filter_campus' => $defaultCampus ?: $request->get('filter_campus'),
            'filter_user_type' => 'accountant',
            'filter_user' => $accountantName ?: $request->get('filter_user'),
            'filter_day' => $request->get('filter_day', 'current_day'),
        ]));

        $view = $this->index($request);
        $viewData = $view->getData();

        if ($defaultCampus && isset($viewData['campuses'])) {
            $viewData['campuses'] = collect($viewData['campuses'])
                ->filter(fn ($campus) => strcasecmp(trim((string) $campus), $defaultCampus) === 0)
                ->values();
        }

        $viewData['layout'] = 'layouts.accountant';
        $viewData['balanceSheetRoute'] = 'accountant.print-balance-sheet';
        // Reuse reports API routes (already on server); same controller methods.
        $viewData['balanceSheetUsersRoute'] = 'reports.balance-sheet.get-users-by-campus-and-type';
        $viewData['balanceSheetSettlementRoute'] = 'reports.balance-sheet.settlement.store';
        $viewData['lockCampus'] = $defaultCampus !== null && $defaultCampus !== '';
        $viewData['defaultCampus'] = $defaultCampus;
        $viewData['lockAccountantUser'] = $accountantName !== '';
        $viewData['defaultAccountantName'] = $accountantName;
        $viewData['showBalanceSheetResults'] = true;

        return view('reports.balance-sheet', $viewData);
    }

    /**
     * @return array<string, mixed>
     */
    private function balanceSheetViewOptions(Request $request, bool $isAccountantOwnLedger = false): array
    {
        if ($request->routeIs('accounting.parent-wallet.print-balance-sheet')) {
            return [
                'layout' => 'layouts.app',
                'balanceSheetRoute' => 'accounting.parent-wallet.print-balance-sheet',
                'balanceSheetUsersRoute' => 'reports.balance-sheet.get-users-by-campus-and-type',
                'balanceSheetSettlementRoute' => 'reports.balance-sheet.settlement.store',
                'lockCampus' => false,
                'defaultCampus' => null,
                'showBalanceSheetResults' => false,
            ];
        }

        return [
            'layout' => 'layouts.app',
            'balanceSheetRoute' => 'reports.balance-sheet',
            'balanceSheetUsersRoute' => 'reports.balance-sheet.get-users-by-campus-and-type',
            'balanceSheetSettlementRoute' => 'reports.balance-sheet.settlement.store',
            'lockCampus' => false,
            'defaultCampus' => null,
            'lockAccountantUser' => $isAccountantOwnLedger,
            'defaultAccountantName' => $isAccountantOwnLedger
                ? trim((string) (Auth::guard('accountant')->user()->name ?? ''))
                : null,
            'showBalanceSheetResults' => false,
        ];
    }

    public function storeSettlement(Request $request)
    {
        $this->ensureSettlementTableExists();

        $validated = $request->validate([
            'filter_campus' => 'nullable|string|max:255',
            'filter_user_type' => 'nullable|string|max:50',
            'filter_user' => 'nullable|string|max:255',
            'filter_day' => 'nullable|string|max:50',
            'total_payment' => 'required|numeric|min:0',
            'method' => 'required|string|max:100',
            'transaction_id' => 'nullable|string|max:255',
            'remarks' => 'nullable|string|max:1000',
            'receipt' => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:4096',
        ]);

        $filterDay = strtolower(trim((string) ($validated['filter_day'] ?? 'current_day')));
        [$startDate] = $this->resolveDayRange($filterDay);

        if ($this->settlementScopeAlreadyTaken(
            $startDate,
            $validated['filter_campus'] ?? null,
            $validated['filter_user_type'] ?? null,
            $validated['filter_user'] ?? null
        )) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'This balance sheet is already settled for the selected day and filter.');
        }

        $receiptPath = null;
        if ($request->hasFile('receipt')) {
            $receiptPath = $request->file('receipt')->store('balance-sheet-settlements', 'public');
        }
        [$createdByType, $createdByName] = $this->resolveSettlementActor();

        try {
            $settlement = BalanceSheetSettlement::create([
                'settlement_date' => $startDate->toDateString(),
                'campus' => $this->normalizeFilterValue($validated['filter_campus'] ?? null),
                'user_type' => $this->normalizeFilterValue($validated['filter_user_type'] ?? null),
                'user_name' => $this->normalizeFilterValue($validated['filter_user'] ?? null),
                'created_by_type' => $createdByType,
                'created_by_name' => $createdByName,
                'total_payment' => (float) $validated['total_payment'],
                'method' => $validated['method'],
                'transaction_id' => $validated['transaction_id'] ?? null,
                'remarks' => $validated['remarks'] ?? null,
                'receipt_path' => $receiptPath,
            ]);
        } catch (QueryException $e) {
            if ((int) $e->getCode() === 1146 || str_contains(strtolower($e->getMessage()), 'doesn\'t exist')) {
                return redirect()
                    ->back()
                    ->withInput()
                    ->with('error', 'Settlement table missing on current tenant database.');
            }
            throw $e;
        }

        $this->notifyAdminsAboutAccountantSettlement($settlement);

        return redirect()
            ->back()
            ->with('success', 'Settlement completed successfully.');
    }

    private function notifyAdminsAboutAccountantSettlement(BalanceSheetSettlement $settlement): void
    {
        $accountant = Auth::guard('accountant')->user();
        if (!$accountant) {
            return;
        }

        $text = sprintf(
            '%s completed balance sheet settlement of %s. Campus: %s. Method: %s.',
            $accountant->name ?? 'Accountant',
            number_format((float) $settlement->total_payment, 2),
            $settlement->campus ?: 'All',
            $settlement->method ?: 'N/A'
        );

        if ($settlement->transaction_id) {
            $text .= ' Transaction ID: ' . $settlement->transaction_id . '.';
        }

        AdminRole::query()
            ->select('id')
            ->orderBy('id')
            ->get()
            ->each(function (AdminRole $admin) use ($accountant, $text) {
                Message::create([
                    'from_type' => 'accountant_notification',
                    'from_id' => $accountant->id,
                    'to_type' => 'admin',
                    'to_id' => $admin->id,
                    'text' => $text,
                    'attachment_path' => null,
                    'attachment_type' => null,
                    'read_at' => null,
                ]);
            });
    }

    private function fetchSettlementsForFilters(
        Carbon $startDate,
        ?string $filterCampus,
        ?string $filterUserType,
        ?string $filterUser
    ) {
        try {
            $query = BalanceSheetSettlement::query()
                ->whereDate('settlement_date', $startDate->toDateString())
                ->where('campus', $this->normalizeFilterValue($filterCampus));

            if (!$this->isViewingAllTypesAllUsers($filterUserType, $filterUser)) {
                $scopes = $this->getSettlementScopes($filterCampus, $filterUserType, $filterUser);
                $query->where(function ($scopeBuilder) use ($scopes) {
                    foreach ($scopes as [$campus, $userType, $userName]) {
                        $scopeBuilder->orWhere(function ($scopeQuery) use ($campus, $userType, $userName) {
                            $scopeQuery
                                ->where('campus', $campus)
                                ->where('user_type', $userType)
                                ->where('user_name', $userName);
                        });
                    }
                });
            }

            return $query->orderByDesc('id')->get();
        } catch (QueryException $e) {
            if ((int) $e->getCode() === 1146 || str_contains(strtolower($e->getMessage()), 'doesn\'t exist')) {
                return collect();
            }

            throw $e;
        }
    }

    /**
     * Get users by campus and user type.
     */
    public function getUsersByCampusAndType(Request $request)
    {
        $campus = $request->get('campus');
        if (Auth::guard('accountant')->check()) {
            $portalAccountant = Auth::guard('accountant')->user();
            $assignedCampus = $portalAccountant->campus ?? null;
            if ($assignedCampus) {
                $campus = $assignedCampus;
            }

            $name = trim((string) ($portalAccountant->name ?? ''));
            if ($name !== '') {
                return response()->json([$name]);
            }
        }

        $userType = strtolower(trim((string) $request->get('user_type')));
        return response()->json($this->getUsersForType($userType, $campus));
    }

    private function getUsersForType(string $userType, ?string $campus)
    {
        $users = collect();

        if ($userType === '' || $userType === 'accountant') {
            $accountants = Accountant::whereNotNull('name');
            if ($campus) {
                $accountants->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim((string) $campus))]);
            }
            $users = $users->merge($accountants->distinct()->pluck('name'));
        }

        if ($userType === '' || $userType === 'admin') {
            $admins = AdminRole::query()
                ->whereNotNull('name')
                ->where(function ($query) {
                    $query->where('super_admin', false)->orWhereNull('super_admin');
                });
            if ($campus) {
                $admins->whereRaw('LOWER(TRIM(admin_of)) = ?', [strtolower(trim((string) $campus))]);
            }
            $users = $users->merge($admins->distinct()->pluck('name'));
        }

        if ($userType === '' || $userType === 'super_admin') {
            $users = $users->merge($this->getSuperAdminAccountNames());
        }

        if ($userType === 'super_admin') {
            $users = $users->merge($this->getSuperAdminNamesFromRecords($campus));
        }

        return $users
            ->map(fn($name) => trim((string) $name))
            ->filter(fn($name) => $name !== '')
            ->unique()
            ->sort()
            ->values();
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

        if (Schema::hasColumn('salaries', 'paid_by_name')) {
            $salaryQuery = Salary::query()
                ->whereNotNull('paid_by_name')
                ->whereRaw("TRIM(paid_by_name) != ''");
            if ($campusKey) {
                $salaryQuery->whereHas('staff', function ($q) use ($campusKey) {
                    $q->whereRaw('LOWER(TRIM(campus)) = ?', [$campusKey]);
                });
            }
            $recordNames = $recordNames->merge($salaryQuery->distinct()->pluck('paid_by_name'));
        }

        return $recordNames
            ->map(fn ($name) => trim((string) $name))
            ->filter(fn ($name) => $name !== '' && $superAdminKeys->contains(strtolower($name)))
            ->unique()
            ->values();
    }

    private function resolveDayRange(string $filterDay): array
    {
        $timezone = config('app.timezone', 'Asia/Karachi');
        $today = Carbon::now($timezone)->startOfDay();
        $dayMap = [
            'current_day' => 0,
            'yesterday' => 1,
            'two_days_ago' => 2,
            'three_days_ago' => 3,
            'four_days_ago' => 4,
            'five_days_ago' => 5,
            'six_days_ago' => 6,
        ];

        if (!array_key_exists($filterDay, $dayMap)) {
            $filterDay = 'current_day';
        }

        $date = $today->copy()->subDays($dayMap[$filterDay]);

        return [$date->copy()->startOfDay(), $date->copy()->endOfDay()];
    }

    private function applyDateRangeFilter($query, string $column, Carbon $startDate, Carbon $endDate): void
    {
        $start = $startDate->toDateString();
        $end = $endDate->toDateString();

        $query->whereDate($column, '>=', $start)
            ->whereDate($column, '<=', $end);
    }

    private function applyStudentPaymentDateRangeFilter($query, Carbon $startDate, Carbon $endDate, string $table = ''): void
    {
        $qualifiedTable = trim($table) !== '' ? trim($table) : 'student_payments';
        $start = $startDate->toDateString();
        $end = $endDate->toDateString();

        $query->where(function ($dateQuery) use ($qualifiedTable, $start, $end) {
            $dateQuery->where(function ($datedPaymentQuery) use ($qualifiedTable, $start, $end) {
                $datedPaymentQuery
                    ->whereNotNull("{$qualifiedTable}.payment_date")
                    ->whereDate("{$qualifiedTable}.payment_date", '>=', $start)
                    ->whereDate("{$qualifiedTable}.payment_date", '<=', $end);
            })->orWhere(function ($createdAtQuery) use ($qualifiedTable, $start, $end) {
                $createdAtQuery
                    ->whereNull("{$qualifiedTable}.payment_date")
                    ->whereBetween("{$qualifiedTable}.created_at", [
                        Carbon::parse($start)->startOfDay(),
                        Carbon::parse($end)->endOfDay(),
                    ]);
            });
        });
    }

    private function computeIncomeForRange(
        Carbon $startDate,
        Carbon $endDate,
        ?string $filterCampus,
        ?string $filterUser,
        bool $includeCustomPayments = true,
        ?string $filterUserType = null
    ): float {
        $studentPaymentsQuery = StudentPayment::query()
            ->ledgerActive()
            ->whereNotIn('method', ['Generated', 'Installment']);
        if ($filterCampus) {
            $studentPaymentsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($filterCampus))]);
        }
        $this->applyUserScopeFilter($studentPaymentsQuery, $filterUserType, $filterUser, $filterCampus, 'accountant');
        $this->applyStudentPaymentDateRangeFilter($studentPaymentsQuery, $startDate, $endDate);
        $total = (float) $studentPaymentsQuery->sum('payment_amount');

        if (!$includeCustomPayments) {
            return $total;
        }

        $customPaymentsQuery = CustomPayment::query();
        if ($filterCampus) {
            $customPaymentsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($filterCampus))]);
        }
        $this->applyUserScopeFilter($customPaymentsQuery, $filterUserType, $filterUser, $filterCampus, 'accountant');
        $this->applyDateRangeFilter($customPaymentsQuery, 'created_at', $startDate, $endDate);
        $customTotal = $customPaymentsQuery->get()
            ->filter(fn (CustomPayment $payment) => ! $payment->isMirroredOnStudentLedger())
            ->sum('payment_amount');

        return $total + (float) $customTotal;
    }

    private function computeExpenseForRange(
        Carbon $startDate,
        Carbon $endDate,
        ?string $filterCampus,
        ?string $filterUser,
        bool $excludeCampusSalaries = false,
        ?string $filterUserType = null
    ): float {
        $salariesTotal = 0.0;
        if (! $excludeCampusSalaries) {
            $salariesQuery = Salary::query()
                ->where('amount_paid', '>', 0)
                ->whereHas('staff');

            if ($filterCampus) {
                $campusKey = strtolower(trim($filterCampus));
                $salariesQuery->whereHas('staff', function ($q) use ($campusKey) {
                    $q->whereRaw('LOWER(TRIM(campus)) = ?', [$campusKey]);
                });
            }

            $this->applySalaryUserScopeFilter($salariesQuery, $filterUserType, $filterUser, $filterCampus);
            $this->applySalaryDateRangeFilter($salariesQuery, $startDate, $endDate);
            $salariesTotal = (float) $salariesQuery->sum('amount_paid');
        }

        $managementQuery = ManagementExpense::query();
        if ($filterCampus) {
            $managementQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($filterCampus))]);
        }
        $this->applyExpenseUserScopeFilter(
            $managementQuery,
            $filterUserType,
            $filterUser,
            $filterCampus,
            $excludeCampusSalaries
        );

        $this->applyDateRangeFilter($managementQuery, 'date', $startDate, $endDate);

        return $salariesTotal + (float) $managementQuery->sum('amount');
    }

    private function shouldFilterExpenseByCreatedBy(?string $filterUserType): bool
    {
        return strtolower(trim((string) $filterUserType)) === 'super_admin';
    }

    private function isScopeSettled(
        Carbon $date,
        ?string $filterCampus,
        ?string $filterUserType,
        ?string $filterUser,
        bool $includeCustomPayments = true,
        bool $excludeCampusSalaries = false
    ): bool {
        if ($this->isViewingAllTypesAllUsers($filterUserType, $filterUser)) {
            return $this->isAllTypesScopeFullySettled(
                $date,
                $filterCampus,
                $includeCustomPayments,
                $excludeCampusSalaries
            );
        }

        try {
            $scopes = $this->getSettlementScopes($filterCampus, $filterUserType, $filterUser);

            $settled = BalanceSheetSettlement::query()
                ->whereDate('settlement_date', $date->toDateString())
                ->where(function ($query) use ($scopes) {
                    foreach ($scopes as [$campus, $userType, $userName]) {
                        $query->orWhere(function ($scopeQuery) use ($campus, $userType, $userName) {
                            $scopeQuery
                                ->where('campus', $campus)
                                ->where('user_type', $userType)
                                ->where('user_name', $userName);
                        });
                    }
                })
                ->exists();

            if ($settled) {
                return true;
            }

            $userType = $this->normalizeFilterValue($filterUserType);
            $userName = $this->normalizeFilterValue($filterUser);

            if ($userType !== 'all' && $userName === 'all') {
                return $this->hasAnyTypeSettlement($date, $filterCampus, $filterUserType);
            }

            return false;
        } catch (QueryException $e) {
            if ((int) $e->getCode() === 1146 || str_contains(strtolower($e->getMessage()), 'doesn\'t exist')) {
                return false;
            }

            throw $e;
        }
    }

    private function settlementScopeAlreadyTaken(
        Carbon $date,
        ?string $filterCampus,
        ?string $filterUserType,
        ?string $filterUser
    ): bool {
        if ($this->isScopeSettled($date, $filterCampus, $filterUserType, $filterUser)) {
            return true;
        }

        if ($this->hasExactSettlement($date, $filterCampus, $filterUserType, $filterUser)) {
            return true;
        }

        $userType = $this->normalizeFilterValue($filterUserType);
        $userName = $this->normalizeFilterValue($filterUser);

        if ($userType !== 'all' && $userName === 'all') {
            return $this->hasAnyTypeSettlement($date, $filterCampus, $filterUserType);
        }

        if ($userType !== 'all' && $userName !== 'all') {
            return $this->hasExactSettlement($date, $filterCampus, $filterUserType, '')
                || $this->hasAnyTypeSettlement($date, $filterCampus, $filterUserType);
        }

        return false;
    }

    private function hasAnyTypeSettlement(
        Carbon $date,
        ?string $filterCampus,
        ?string $filterUserType
    ): bool {
        try {
            return BalanceSheetSettlement::query()
                ->whereDate('settlement_date', $date->toDateString())
                ->where('campus', $this->normalizeFilterValue($filterCampus))
                ->where('user_type', $this->normalizeFilterValue($filterUserType))
                ->exists();
        } catch (QueryException $e) {
            if ((int) $e->getCode() === 1146 || str_contains(strtolower($e->getMessage()), 'doesn\'t exist')) {
                return false;
            }

            throw $e;
        }
    }

    /**
     * Settlement scopes that satisfy the current balance sheet filter.
     *
     * @return list<array{0: string, 1: string, 2: string}>
     */
    private function getSettlementScopes(?string $filterCampus, ?string $filterUserType, ?string $filterUser): array
    {
        $campus = $this->normalizeFilterValue($filterCampus);
        $userType = $this->normalizeFilterValue($filterUserType);
        $userName = $this->normalizeFilterValue($filterUser);

        $scopes = [[$campus, $userType, $userName]];

        if ($userName !== 'all' && $userType !== 'all') {
            $scopes[] = [$campus, $userType, 'all'];
        }

        if ($userType !== 'all' || $userName !== 'all') {
            $scopes[] = [$campus, 'all', 'all'];
        }

        $unique = [];
        foreach ($scopes as $scope) {
            $key = implode('|', $scope);
            $unique[$key] = $scope;
        }

        return array_values($unique);
    }

    /**
     * @return list<string>|null Null means no user scope filter (all types/users).
     */
    private function resolveScopedUserNames(?string $filterUserType, ?string $filterUser, ?string $filterCampus): ?array
    {
        if ($filterUser) {
            $name = strtolower(trim((string) $filterUser));

            return $name === '' ? [] : [$name];
        }

        $userType = strtolower(trim((string) $filterUserType));
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

    private function applyUserScopeFilter(
        $query,
        ?string $filterUserType,
        ?string $filterUser,
        ?string $filterCampus,
        string $nameColumn
    ): void {
        $scopedNames = $this->resolveScopedUserNames($filterUserType, $filterUser, $filterCampus);
        if ($scopedNames === null) {
            return;
        }

        if ($scopedNames === []) {
            $query->whereRaw('1 = 0');

            return;
        }

        $query->where(function ($scopeQuery) use ($scopedNames, $nameColumn) {
            foreach ($scopedNames as $name) {
                $scopeQuery->orWhereRaw("LOWER(TRIM({$nameColumn})) = ?", [$name]);
            }
        });
    }

    private function applySalaryUserScopeFilter(
        $query,
        ?string $filterUserType,
        ?string $filterUser,
        ?string $filterCampus
    ): void {
        $scopedNames = $this->resolveScopedUserNames($filterUserType, $filterUser, $filterCampus);
        if ($scopedNames === null) {
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

    private function applySalaryDateRangeFilter($query, Carbon $startDate, Carbon $endDate): void
    {
        $start = $startDate->toDateString();
        $end = $endDate->toDateString();

        if (! Schema::hasColumn('salaries', 'payment_date')) {
            $query->whereBetween('updated_at', [$startDate, $endDate]);

            return;
        }

        $query->where(function ($dateQuery) use ($start, $end, $startDate, $endDate) {
            $dateQuery->where(function ($datedPaymentQuery) use ($start, $end) {
                $datedPaymentQuery
                    ->whereNotNull('payment_date')
                    ->where('payment_date', '!=', '0000-00-00')
                    ->whereDate('payment_date', '>=', $start)
                    ->whereDate('payment_date', '<=', $end);
            })->orWhere(function ($updatedAtQuery) use ($start, $end, $startDate, $endDate) {
                $updatedAtQuery->where(function ($missingDateQuery) {
                    $missingDateQuery
                        ->whereNull('payment_date')
                        ->orWhere('payment_date', '=', '0000-00-00');
                })->whereDate('updated_at', '>=', $start)
                    ->whereDate('updated_at', '<=', $end);
            });
        });
    }

    private function applyExpenseUserScopeFilter(
        $query,
        ?string $filterUserType,
        ?string $filterUser,
        ?string $filterCampus,
        bool $preferCreatedBy = false
    ): void {
        $scopedNames = $this->resolveScopedUserNames($filterUserType, $filterUser, $filterCampus);
        if ($scopedNames === null) {
            return;
        }

        if ($scopedNames === []) {
            $query->whereRaw('1 = 0');

            return;
        }

        $hasExpenseAccountantColumn = Schema::hasColumn('management_expenses', 'accountant');
        $useCreatedBy = $preferCreatedBy
            || $this->shouldFilterExpenseByCreatedBy($filterUserType)
            || strtolower(trim((string) $filterUserType)) === 'admin';

        if ($filterUser && ! $useCreatedBy && $hasExpenseAccountantColumn && strtolower(trim((string) $filterUserType)) === 'accountant') {
            $userKey = strtolower(trim((string) $filterUser));
            $query->whereRaw('LOWER(TRIM(accountant)) = ?', [$userKey]);

            return;
        }

        if ($filterUser && $useCreatedBy) {
            $userKey = strtolower(trim((string) $filterUser));
            $query->whereRaw('LOWER(TRIM(created_by)) = ?', [$userKey]);

            return;
        }

        if ($filterUser && ! $hasExpenseAccountantColumn) {
            $userKey = strtolower(trim((string) $filterUser));
            $query->where(function ($scopeQuery) use ($userKey) {
                $scopeQuery->whereRaw('LOWER(TRIM(created_by)) = ?', [$userKey])
                    ->orWhereRaw('LOWER(TRIM(category)) LIKE ?', ['%' . $userKey . '%'])
                    ->orWhereRaw('LOWER(TRIM(title)) LIKE ?', ['%' . $userKey . '%']);
            });

            return;
        }

        $query->where(function ($scopeQuery) use ($scopedNames, $hasExpenseAccountantColumn, $useCreatedBy, $filterUserType) {
            foreach ($scopedNames as $name) {
                if ($useCreatedBy) {
                    $scopeQuery->orWhereRaw('LOWER(TRIM(created_by)) = ?', [$name]);
                    continue;
                }

                if ($hasExpenseAccountantColumn && strtolower(trim((string) $filterUserType)) === 'accountant') {
                    $scopeQuery->orWhereRaw('LOWER(TRIM(accountant)) = ?', [$name]);
                    continue;
                }

                $scopeQuery->orWhere(function ($nameQuery) use ($name, $hasExpenseAccountantColumn) {
                    $nameQuery->whereRaw('LOWER(TRIM(created_by)) = ?', [$name]);
                    if ($hasExpenseAccountantColumn) {
                        $nameQuery->orWhereRaw('LOWER(TRIM(accountant)) = ?', [$name]);
                    }
                });
            }
        });
    }

    private function computePreviousUnsettledAmount(
        Carbon $beforeDate,
        ?string $filterCampus,
        ?string $filterUserType,
        ?string $filterUser,
        bool $includeCustomPayments = true,
        bool $excludeCampusSalaries = false
    ): float {
        $total = 0.0;
        $cursor = $beforeDate->copy()->subDay()->startOfDay();
        $limit = $beforeDate->copy()->subDays(90)->startOfDay();

        while ($cursor->gte($limit)) {
            if ($this->isViewingAllTypesAllUsers($filterUserType, $filterUser)) {
                if (!$this->isAllTypesScopeFullySettled(
                    $cursor,
                    $filterCampus,
                    $includeCustomPayments,
                    $excludeCampusSalaries
                )) {
                    $total += $this->computeUnsettledNetForAllTypesDay(
                        $cursor,
                        $filterCampus,
                        $includeCustomPayments,
                        $excludeCampusSalaries
                    );
                }
            } elseif (!$this->isScopeSettled($cursor, $filterCampus, $filterUserType, $filterUser)) {
                $dayStart = $cursor->copy()->startOfDay();
                $dayEnd = $cursor->copy()->endOfDay();
                $income = $this->computeIncomeForRange(
                    $dayStart,
                    $dayEnd,
                    $filterCampus,
                    $filterUser,
                    $includeCustomPayments,
                    $filterUserType
                );
                $expense = $this->computeExpenseForRange(
                    $dayStart,
                    $dayEnd,
                    $filterCampus,
                    $filterUser,
                    $excludeCampusSalaries,
                    $filterUserType
                );
                $total += ($income - $expense);
            }

            $cursor->subDay();
        }

        return round($total, 2);
    }

    private function isViewingAllTypesAllUsers(?string $filterUserType, ?string $filterUser): bool
    {
        return strtolower(trim((string) $filterUserType)) === ''
            && trim((string) $filterUser) === '';
    }

    private function hasExactSettlement(
        Carbon $date,
        ?string $filterCampus,
        ?string $filterUserType,
        ?string $filterUser
    ): bool {
        try {
            return BalanceSheetSettlement::query()
                ->whereDate('settlement_date', $date->toDateString())
                ->where('campus', $this->normalizeFilterValue($filterCampus))
                ->where('user_type', $this->normalizeFilterValue($filterUserType))
                ->where('user_name', $this->normalizeFilterValue($filterUser))
                ->exists();
        } catch (QueryException $e) {
            if ((int) $e->getCode() === 1146 || str_contains(strtolower($e->getMessage()), 'doesn\'t exist')) {
                return false;
            }

            throw $e;
        }
    }

    private function isAllTypesScopeFullySettled(
        Carbon $date,
        ?string $filterCampus,
        bool $includeCustomPayments = true,
        bool $excludeCampusSalaries = false
    ): bool {
        if ($this->hasExactSettlement($date, $filterCampus, '', '')) {
            return true;
        }

        $dayStart = $date->copy()->startOfDay();
        $dayEnd = $date->copy()->endOfDay();

        foreach (['accountant', 'admin', 'super_admin'] as $type) {
            $income = $this->computeIncomeForRange($dayStart, $dayEnd, $filterCampus, null, $includeCustomPayments, $type);
            $expense = $this->computeExpenseForRange($dayStart, $dayEnd, $filterCampus, null, false, $type);
            $typeNet = $income - $expense;

            if (abs($typeNet) < 0.01) {
                continue;
            }

            if (!$this->isScopeSettled($date, $filterCampus, $type, null, $includeCustomPayments, false)) {
                return false;
            }
        }

        return true;
    }

    private function computeUnsettledNetForAllTypesDay(
        Carbon $day,
        ?string $filterCampus,
        bool $includeCustomPayments = true,
        bool $excludeCampusSalaries = false
    ): float {
        if ($this->hasExactSettlement($day, $filterCampus, '', '')) {
            return 0.0;
        }

        $dayStart = $day->copy()->startOfDay();
        $dayEnd = $day->copy()->endOfDay();
        $total = 0.0;

        foreach (['accountant', 'admin', 'super_admin'] as $type) {
            if ($this->isScopeSettled($day, $filterCampus, $type, null, $includeCustomPayments, true)) {
                continue;
            }

            $income = $this->computeIncomeForRange($dayStart, $dayEnd, $filterCampus, null, $includeCustomPayments, $type);
            $expense = $this->computeExpenseForRange($dayStart, $dayEnd, $filterCampus, null, false, $type);
            $total += ($income - $expense);
        }

        return round($total, 2);
    }

    private function computeSalaryExpenseForRange(
        Carbon $startDate,
        Carbon $endDate,
        ?string $filterCampus
    ): float {
        $salariesQuery = Salary::query()
            ->where('amount_paid', '>', 0)
            ->whereHas('staff');

        if ($filterCampus) {
            $campusKey = strtolower(trim($filterCampus));
            $salariesQuery->whereHas('staff', function ($q) use ($campusKey) {
                $q->whereRaw('LOWER(TRIM(campus)) = ?', [$campusKey]);
            });
        }

        $this->applyDateRangeFilter($salariesQuery, 'updated_at', $startDate, $endDate);

        return (float) $salariesQuery->sum('amount_paid');
    }

    private function normalizeFilterValue(?string $value): string
    {
        $normalized = strtolower(trim((string) $value));
        return $normalized === '' ? 'all' : $normalized;
    }

    private function ensureSettlementTableExists(): void
    {
        if (!Schema::hasTable('balance_sheet_settlements')) {
            Schema::create('balance_sheet_settlements', function (Blueprint $table) {
                $table->id();
                $table->date('settlement_date');
                $table->string('campus')->default('all');
                $table->string('user_type')->default('all');
                $table->string('user_name')->default('all');
                $table->string('created_by_type')->nullable();
                $table->string('created_by_name')->nullable();
                $table->decimal('total_payment', 14, 2)->default(0);
                $table->string('method', 100);
                $table->string('transaction_id')->nullable();
                $table->text('remarks')->nullable();
                $table->string('receipt_path')->nullable();
                $table->timestamps();

                $table->index(['settlement_date', 'campus', 'user_type', 'user_name'], 'bs_settlement_filter_idx');
            });
            return;
        }

        if (!Schema::hasColumn('balance_sheet_settlements', 'created_by_type') || !Schema::hasColumn('balance_sheet_settlements', 'created_by_name')) {
            Schema::table('balance_sheet_settlements', function (Blueprint $table) {
                if (!Schema::hasColumn('balance_sheet_settlements', 'created_by_type')) {
                    $table->string('created_by_type')->nullable()->after('user_name');
                }
                if (!Schema::hasColumn('balance_sheet_settlements', 'created_by_name')) {
                    $table->string('created_by_name')->nullable()->after('created_by_type');
                }
            });
        }
    }

    private function resolveSettlementActor(): array
    {
        $guards = [
            'platform_super_admin' => 'Super Admin',
            'admin' => 'Admin',
            'accountant' => 'Accountant',
            'staff' => 'Staff',
            'web' => 'User',
        ];

        foreach ($guards as $guard => $type) {
            if (Auth::guard($guard)->check()) {
                $actor = Auth::guard($guard)->user();
                $name = trim((string) ($actor->name ?? $actor->student_name ?? $actor->email ?? 'Unknown'));
                return [$type, $name !== '' ? $name : 'Unknown'];
            }
        }

        return ['System', 'System'];
    }
}

