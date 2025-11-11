<?php

namespace App\Http\Controllers;

use App\Models\StudentPayment;
use App\Models\CustomPayment;
use App\Models\ManagementExpense;
use App\Models\Student;
use App\Models\Staff;
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
        $filterUserType = $request->get('filter_user_type'); // Income or Expense
        $filterUser = $request->get('filter_user'); // Student code, accountant name, etc.
        $filterFromDate = $request->get('filter_from_date');
        $filterToDate = $request->get('filter_to_date');

        // Get campuses
        $campusesFromPayments = StudentPayment::whereNotNull('campus')->distinct()->pluck('campus');
        $campusesFromCustom = CustomPayment::whereNotNull('campus')->distinct()->pluck('campus');
        $campusesFromExpenses = ManagementExpense::whereNotNull('campus')->distinct()->pluck('campus');
        $campuses = $campusesFromPayments->merge($campusesFromCustom)->merge($campusesFromExpenses)->unique()->sort()->values();
        
        if ($campuses->isEmpty()) {
            $campuses = collect(['Main Campus', 'Branch Campus 1', 'Branch Campus 2']);
        }

        // User Type options
        $userTypeOptions = collect(['Income', 'Expense']);

        // Get users (students and staff/accountants)
        $students = Student::whereNotNull('student_code')->distinct()->pluck('student_code')->sort()->values();
        $accountants = Staff::whereNotNull('name')->distinct()->pluck('name')->sort()->values();
        $users = $students->merge($accountants)->unique()->sort()->values();

        // Prepare income records
        $incomeRecords = collect();
        
        // Student Payments (Income)
        if (!$filterUserType || $filterUserType == 'Income') {
            $studentPaymentsQuery = StudentPayment::query();
            
            if ($filterCampus) {
                $studentPaymentsQuery->where('campus', $filterCampus);
            }
            if ($filterUser) {
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
        if (!$filterUserType || $filterUserType == 'Income') {
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
        if (!$filterUserType || $filterUserType == 'Expense') {
            $expensesQuery = ManagementExpense::query();
            
            if ($filterCampus) {
                $expensesQuery->where('campus', $filterCampus);
            }
            if ($filterUser) {
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
}

