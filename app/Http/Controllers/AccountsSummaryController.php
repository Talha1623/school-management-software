<?php

namespace App\Http\Controllers;

use App\Models\StudentPayment;
use App\Models\CustomPayment;
use App\Models\ManagementExpense;
use App\Models\FeeType;
use App\Models\ClassModel;
use App\Models\Section;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccountsSummaryController extends Controller
{
    /**
     * Display the accounts summary reports with filters.
     */
    public function index(Request $request): View
    {
        // Get filter values
        $filterCampus = $request->get('filter_campus');
        $filterType = $request->get('filter_type'); // Income or Expense
        $filterMonth = $request->get('filter_month');
        $filterYear = $request->get('filter_year');

        // Get campuses
        $campusesFromPayments = StudentPayment::whereNotNull('campus')->distinct()->pluck('campus');
        $campusesFromCustom = CustomPayment::whereNotNull('campus')->distinct()->pluck('campus');
        $campusesFromExpenses = ManagementExpense::whereNotNull('campus')->distinct()->pluck('campus');
        $campuses = $campusesFromPayments->merge($campusesFromCustom)->merge($campusesFromExpenses)->unique()->sort()->values();
        
        if ($campuses->isEmpty()) {
            $campusesFromClasses = ClassModel::whereNotNull('campus')->distinct()->pluck('campus');
            $campusesFromSections = Section::whereNotNull('campus')->distinct()->pluck('campus');
            $campuses = $campusesFromClasses->merge($campusesFromSections)->unique()->sort()->values();
            
            if ($campuses->isEmpty()) {
                $campuses = collect(['Main Campus', 'Branch Campus 1', 'Branch Campus 2']);
            }
        }

        // Type options (Income or Expense)
        $typeOptions = collect(['Income', 'Expense']);

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

        // Income Summary
        if (!$filterType || $filterType == 'Income') {
            // Student Payments (Income)
            $studentPaymentsQuery = StudentPayment::query();
            
            if ($filterCampus) {
                $studentPaymentsQuery->where('campus', $filterCampus);
            }
            if ($filterMonth) {
                $studentPaymentsQuery->whereMonth('payment_date', $filterMonth);
            }
            if ($filterYear) {
                $studentPaymentsQuery->whereYear('payment_date', $filterYear);
            }
            
            $studentPayments = $studentPaymentsQuery->get();
            $studentPaymentsTotal = $studentPayments->sum('payment_amount');
            $studentPaymentsDiscount = $studentPayments->sum('discount');
            
            if ($studentPaymentsTotal > 0) {
                $summaryRecords->push([
                    'type' => 'Income',
                    'category' => 'Student Payments',
                    'campus' => $filterCampus ?: 'All Campuses',
                    'month' => $filterMonth ? $months->get($filterMonth) : 'All Months',
                    'year' => $filterYear ?: 'All Years',
                    'total_amount' => $studentPaymentsTotal,
                    'discount' => $studentPaymentsDiscount,
                    'net_amount' => $studentPaymentsTotal - $studentPaymentsDiscount,
                    'count' => $studentPayments->count(),
                ]);
            }

            // Custom Payments (Income)
            $customPaymentsQuery = CustomPayment::query();
            
            if ($filterCampus) {
                $customPaymentsQuery->where('campus', $filterCampus);
            }
            if ($filterMonth) {
                $customPaymentsQuery->whereMonth('payment_date', $filterMonth);
            }
            if ($filterYear) {
                $customPaymentsQuery->whereYear('payment_date', $filterYear);
            }
            
            $customPayments = $customPaymentsQuery->get();
            $customPaymentsTotal = $customPayments->sum('payment_amount');
            
            if ($customPaymentsTotal > 0) {
                $summaryRecords->push([
                    'type' => 'Income',
                    'category' => 'Custom Payments',
                    'campus' => $filterCampus ?: 'All Campuses',
                    'month' => $filterMonth ? $months->get($filterMonth) : 'All Months',
                    'year' => $filterYear ?: 'All Years',
                    'total_amount' => $customPaymentsTotal,
                    'discount' => 0,
                    'net_amount' => $customPaymentsTotal,
                    'count' => $customPayments->count(),
                ]);
            }
        }

        // Expense Summary
        if (!$filterType || $filterType == 'Expense') {
            // Management Expenses
            $expensesQuery = ManagementExpense::query();
            
            if ($filterCampus) {
                $expensesQuery->where('campus', $filterCampus);
            }
            if ($filterMonth) {
                $expensesQuery->whereMonth('date', $filterMonth);
            }
            if ($filterYear) {
                $expensesQuery->whereYear('date', $filterYear);
            }
            
            $expenses = $expensesQuery->get();
            $expensesTotal = $expenses->sum('amount');
            
            if ($expensesTotal > 0) {
                $summaryRecords->push([
                    'type' => 'Expense',
                    'category' => 'Management Expenses',
                    'campus' => $filterCampus ?: 'All Campuses',
                    'month' => $filterMonth ? $months->get($filterMonth) : 'All Months',
                    'year' => $filterYear ?: 'All Years',
                    'total_amount' => $expensesTotal,
                    'discount' => 0,
                    'net_amount' => $expensesTotal,
                    'count' => $expenses->count(),
                ]);
            }
        }

        return view('reports.accounts-summary', compact(
            'campuses',
            'typeOptions',
            'months',
            'years',
            'summaryRecords',
            'filterCampus',
            'filterType',
            'filterMonth',
            'filterYear'
        ));
    }
}

