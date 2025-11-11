<?php

namespace App\Http\Controllers;

use App\Models\ManagementExpense;
use App\Models\ExpenseCategory;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DetailedExpenseController extends Controller
{
    /**
     * Display the detailed expense reports with filters.
     */
    public function index(Request $request): View
    {
        // Get filter values
        $filterMonth = $request->get('filter_month');
        $filterDate = $request->get('filter_date');
        $filterCategory = $request->get('filter_category');
        $filterYear = $request->get('filter_year');
        $filterMethod = $request->get('filter_method');

        // Get expense categories
        $categories = ExpenseCategory::whereNotNull('category_name')->distinct()->pluck('category_name')->sort()->values();
        if ($categories->isEmpty()) {
            $categories = collect(['Office Supplies', 'Utilities', 'Maintenance', 'Transportation', 'Other']);
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

        // Get payment methods from expenses
        $methods = ManagementExpense::whereNotNull('method')->distinct()->pluck('method')->sort()->values();
        
        if ($methods->isEmpty()) {
            $methods = collect(['Cash', 'Bank Transfer', 'Cheque', 'Online Payment', 'Card']);
        }

        // Query expenses
        $expensesQuery = ManagementExpense::query();
        
        if ($filterMonth) {
            $expensesQuery->whereMonth('date', $filterMonth);
        }
        if ($filterDate) {
            $expensesQuery->whereDate('date', $filterDate);
        }
        if ($filterCategory) {
            $expensesQuery->where('category', $filterCategory);
        }
        if ($filterYear) {
            $expensesQuery->whereYear('date', $filterYear);
        }
        if ($filterMethod) {
            $expensesQuery->where('method', $filterMethod);
        }
        
        $expenses = $expensesQuery->orderBy('date', 'desc')->get();

        // Prepare expense records
        $expenseRecords = collect();

        foreach ($expenses as $expense) {
            $expenseRecords->push([
                'campus' => $expense->campus,
                'category' => $expense->category,
                'title' => $expense->title,
                'description' => $expense->description,
                'amount' => $expense->amount,
                'method' => $expense->method,
                'date' => $expense->date,
                'invoice_receipt' => $expense->invoice_receipt,
                'notify_admin' => $expense->notify_admin,
            ]);
        }

        return view('reports.detailed-expense', compact(
            'categories',
            'months',
            'years',
            'methods',
            'expenseRecords',
            'filterMonth',
            'filterDate',
            'filterCategory',
            'filterYear',
            'filterMethod'
        ));
    }
}

