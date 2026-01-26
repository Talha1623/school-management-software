<?php

namespace App\Http\Controllers;

use App\Models\StudentPayment;
use App\Models\CustomPayment;
use App\Models\ManagementExpense;
use App\Models\AdminRole;
use App\Models\Accountant;
use App\Models\Campus;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\View\View;

class IncomeExpenseReportController extends Controller
{
    /**
     * Display the income & expense reports with filters.
     */
    public function index(Request $request): View
    {
        // Get filter values
        $filterCampus = $request->get('filter_campus');
        $filterUserType = $request->get('filter_user_type'); // Accountant or Admin
        $filterUser = $request->get('filter_user'); // Student code, accountant name, etc.
        $filterFromDate = $request->get('filter_from_date');
        $filterToDate = $request->get('filter_to_date');

        // Get campuses from Campus model first, then fallback to existing data
        $campuses = Campus::orderBy('campus_name', 'asc')->pluck('campus_name');
        if ($campuses->isEmpty()) {
            $campusesFromPayments = StudentPayment::whereNotNull('campus')->distinct()->pluck('campus');
            $campusesFromCustom = CustomPayment::whereNotNull('campus')->distinct()->pluck('campus');
            $campusesFromExpenses = ManagementExpense::whereNotNull('campus')->distinct()->pluck('campus');
            $campuses = $campusesFromPayments->merge($campusesFromCustom)->merge($campusesFromExpenses)->unique()->sort()->values();
        }

        // User Type options
        $userTypeOptions = collect(['Accountant', 'Admin']);

        // Get users based on type and campus
        $users = $this->getUsersForType($filterUserType, $filterCampus);

        // Prepare income records
        $incomeRecords = collect();
        
        // Student Payments (Income)
        if (!$filterUserType || $filterUserType == 'Accountant' || $filterUserType == 'Admin') {
            $studentPaymentsQuery = StudentPayment::query();
            
            if ($filterCampus) {
                $studentPaymentsQuery->where('campus', $filterCampus);
            }
            if ($filterUser && empty($filterUserType)) {
                $studentPaymentsQuery->where('student_code', $filterUser);
            }
            if ($filterFromDate) {
                $studentPaymentsQuery->where('payment_date', '>=', $filterFromDate);
            }
            if ($filterToDate) {
                $studentPaymentsQuery->where('payment_date', '<=', $filterToDate);
            }
            
            $studentPayments = $studentPaymentsQuery->get();
            foreach ($studentPayments as $payment) {
                $incomeRecords->push([
                    'type' => 'Income',
                    'source' => 'Student Payment',
                    'user' => $payment->student_code,
                    'campus' => $payment->campus,
                    'title' => $payment->payment_title,
                    'amount' => $payment->payment_amount,
                    'date' => $payment->payment_date,
                    'method' => $payment->method,
                ]);
            }
        }

        // Custom Payments (Income)
        if (!$filterUserType || $filterUserType == 'Accountant' || $filterUserType == 'Admin') {
            $customPaymentsQuery = CustomPayment::query();
            
            if ($filterCampus) {
                $customPaymentsQuery->where('campus', $filterCampus);
            }
            if ($filterUser) {
                $customPaymentsQuery->where('accountant', $filterUser);
            }
            if ($filterFromDate) {
                $customPaymentsQuery->where('payment_date', '>=', $filterFromDate);
            }
            if ($filterToDate) {
                $customPaymentsQuery->where('payment_date', '<=', $filterToDate);
            }
            
            $customPayments = $customPaymentsQuery->get();
            foreach ($customPayments as $payment) {
                $incomeRecords->push([
                    'type' => 'Income',
                    'source' => 'Custom Payment',
                    'user' => $payment->accountant ?? 'N/A',
                    'campus' => $payment->campus,
                    'title' => $payment->payment_title,
                    'amount' => $payment->payment_amount,
                    'date' => $payment->payment_date,
                    'method' => $payment->method,
                ]);
            }
        }

        // Management Expenses (Expense)
        if (!$filterUserType || $filterUserType == 'Accountant' || $filterUserType == 'Admin') {
            $expensesQuery = ManagementExpense::query();
            
            if ($filterCampus) {
                $expensesQuery->where('campus', $filterCampus);
            }
            if ($filterUser && empty($filterUserType)) {
                $expensesQuery->where(function($q) use ($filterUser) {
                    $q->where('category', 'like', '%' . $filterUser . '%')
                      ->orWhere('title', 'like', '%' . $filterUser . '%');
                });
            }
            if ($filterFromDate) {
                $expensesQuery->where('date', '>=', $filterFromDate);
            }
            if ($filterToDate) {
                $expensesQuery->where('date', '<=', $filterToDate);
            }
            
            $expenses = $expensesQuery->get();
            foreach ($expenses as $expense) {
                $incomeRecords->push([
                    'type' => 'Expense',
                    'source' => 'Management Expense',
                    'user' => $expense->category,
                    'campus' => $expense->campus,
                    'title' => $expense->title,
                    'amount' => $expense->amount,
                    'date' => $expense->date,
                    'method' => $expense->method,
                ]);
            }
        }

        // Sort by date
        $incomeRecords = $incomeRecords->sortByDesc('date')->values();

        return view('reports.income-expense', compact(
            'campuses',
            'userTypeOptions',
            'users',
            'incomeRecords',
            'filterCampus',
            'filterUserType',
            'filterUser',
            'filterFromDate',
            'filterToDate'
        ));
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

        if (!$userType || $userType === 'Accountant') {
            $accountantsQuery = Accountant::whereNotNull('name');
            if ($campus) {
                $accountantsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
            }
            $users = $users->merge($accountantsQuery->distinct()->pluck('name'));
        }

        if (!$userType || $userType === 'Admin') {
            $adminsQuery = AdminRole::whereNotNull('name');
            if ($campus) {
                $adminsQuery->whereRaw('LOWER(TRIM(admin_of)) = ?', [strtolower(trim($campus))]);
            }
            $users = $users->merge($adminsQuery->distinct()->pluck('name'));
        }

        return $users->map(function($name) {
            return trim((string) $name);
        })->filter(function($name) {
            return $name !== '';
        })->unique()->sort()->values();
    }
}

