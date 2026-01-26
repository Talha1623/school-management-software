<?php

namespace App\Http\Controllers;

use App\Models\StudentPayment;
use App\Models\CustomPayment;
use App\Models\ManagementExpense;
use App\Models\Student;
use App\Models\Staff;
use App\Models\Campus;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BalanceSheetController extends Controller
{
    /**
     * Display the balance sheet with filters.
     */
    public function index(Request $request): View
    {
        // Get filter values
        $filterCampus = $request->get('filter_campus');
        $filterUserType = $request->get('filter_user_type'); // Student or Staff
        $filterUser = $request->get('filter_user'); // Student code or Staff name

        // Get campuses from Campus model first, then fallback to transactions
        $campuses = Campus::orderBy('campus_name', 'asc')->pluck('campus_name');
        if ($campuses->isEmpty()) {
            $campusesFromPayments = StudentPayment::whereNotNull('campus')->distinct()->pluck('campus');
            $campusesFromCustom = CustomPayment::whereNotNull('campus')->distinct()->pluck('campus');
            $campusesFromExpenses = ManagementExpense::whereNotNull('campus')->distinct()->pluck('campus');
            $campuses = $campusesFromPayments->merge($campusesFromCustom)->merge($campusesFromExpenses)->unique()->sort()->values();
        }

        // User Type options
        $userTypeOptions = collect(['Student', 'Staff']);

        // Get users based on type
        $users = collect();
        
        if (!$filterUserType || $filterUserType == 'Student') {
            $studentsQuery = Student::whereNotNull('student_code');
            if ($filterCampus) {
                $studentsQuery->where('campus', $filterCampus);
            }
            $students = $studentsQuery->distinct()->pluck('student_code')->sort()->values();
            $users = $users->merge($students);
        }
        
        if (!$filterUserType || $filterUserType == 'Staff') {
            $staffQuery = Staff::whereNotNull('name');
            if ($filterCampus) {
                $staffQuery->where('campus', $filterCampus);
            }
            $staff = $staffQuery->distinct()->pluck('name')->sort()->values();
            $users = $users->merge($staff);
        }
        
        $users = $users->unique()->sort()->values();

        // Prepare balance sheet data
        $balanceRecords = collect();

        // Calculate Income (Credits)
        $totalIncome = 0;
        $incomeBreakdown = collect();

        // Student Payments (Income)
        $studentPaymentsQuery = StudentPayment::query();
        if ($filterCampus) {
            $studentPaymentsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($filterCampus))]);
        }
        if ($filterUser && $filterUserType == 'Student') {
            $studentPaymentsQuery->where('student_code', $filterUser);
        }
        $studentPayments = $studentPaymentsQuery->get();
        $studentPaymentsTotal = $studentPayments->sum('payment_amount');
        $totalIncome += $studentPaymentsTotal;
        
        if ($studentPaymentsTotal > 0) {
            $incomeBreakdown->push([
                'source' => 'Student Payments',
                'amount' => $studentPaymentsTotal,
            ]);
        }

        // Custom Payments (Income)
        $customPaymentsQuery = CustomPayment::query();
        if ($filterCampus) {
            $customPaymentsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($filterCampus))]);
        }
        if ($filterUser && $filterUserType == 'Staff') {
            $customPaymentsQuery->where('accountant', $filterUser);
        }
        $customPayments = $customPaymentsQuery->get();
        $customPaymentsTotal = $customPayments->sum('payment_amount');
        $totalIncome += $customPaymentsTotal;
        
        if ($customPaymentsTotal > 0) {
            $incomeBreakdown->push([
                'source' => 'Custom Payments',
                'amount' => $customPaymentsTotal,
            ]);
        }

        // Calculate Expenses (Debits)
        $totalExpense = 0;
        $expenseBreakdown = collect();

        // Management Expenses
        $expensesQuery = ManagementExpense::query();
        if ($filterCampus) {
            $expensesQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($filterCampus))]);
        }
        $expenses = $expensesQuery->get();
        $expensesTotal = $expenses->sum('amount');
        $totalExpense += $expensesTotal;
        
        if ($expensesTotal > 0) {
            $expenseBreakdown->push([
                'source' => 'Management Expenses',
                'amount' => $expensesTotal,
            ]);
        }

        // Calculate Net Balance
        $netBalance = $totalIncome - $totalExpense;

        // Prepare balance sheet summary
        $balanceSheet = [
            'total_income' => $totalIncome,
            'total_expense' => $totalExpense,
            'net_balance' => $netBalance,
            'income_breakdown' => $incomeBreakdown,
            'expense_breakdown' => $expenseBreakdown,
        ];

        return view('reports.balance-sheet', compact(
            'campuses',
            'userTypeOptions',
            'users',
            'balanceSheet',
            'filterCampus',
            'filterUserType',
            'filterUser'
        ));
    }

    /**
     * Get users by campus and user type.
     */
    public function getUsersByCampusAndType(Request $request)
    {
        $campus = $request->get('campus');
        $userType = $request->get('user_type');

        $users = collect();

        if (!$userType || $userType === 'Student') {
            $studentsQuery = Student::whereNotNull('student_code');
            if ($campus) {
                $studentsQuery->where('campus', $campus);
            }
            $users = $users->merge($studentsQuery->distinct()->pluck('student_code')->sort()->values());
        }

        if (!$userType || $userType === 'Staff') {
            $staffQuery = Staff::whereNotNull('name');
            if ($campus) {
                $staffQuery->where('campus', $campus);
            }
            $users = $users->merge($staffQuery->distinct()->pluck('name')->sort()->values());
        }

        return response()->json($users->unique()->sort()->values());
    }
}

