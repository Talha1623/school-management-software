<?php

namespace App\Http\Controllers;

use App\Models\StudentPayment;
use App\Models\CustomPayment;
use App\Models\ManagementExpense;
use App\Models\FeeType;
use App\Models\ClassModel;
use App\Models\Section;
use App\Models\Campus;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;

class AccountsSummaryController extends Controller
{
    /**
     * Display the accounts summary reports with filters.
     */
    public function index(Request $request): View
    {
        // Get filter values
        $filterCampus = $request->get('filter_campus');
        $filterMonth = $request->get('filter_month');
        $filterYear = $request->get('filter_year');

        // Get campuses from Campus model
        $campuses = Campus::orderBy('campus_name', 'asc')->get();
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

        // Income Summary - Student Payments
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
        
        $incomeByDate = $studentPayments->groupBy(function($payment) {
            return \Carbon\Carbon::parse($payment->payment_date)->format('Y-m-d');
        })->map(function($payments) {
            return $payments->sum('payment_amount');
        });

        // Income Summary - Custom Payments
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
        
        $customIncomeByDate = $customPayments->groupBy(function($payment) {
            return \Carbon\Carbon::parse($payment->payment_date)->format('Y-m-d');
        })->map(function($payments) {
            return $payments->sum('payment_amount');
        });

        $customIncomeByDate->each(function($amount, $date) use (&$incomeByDate) {
            $incomeByDate[$date] = ($incomeByDate[$date] ?? 0) + $amount;
        });

        // Expense Summary
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
        
        $expenseByDate = $expenses->groupBy(function($expense) {
            return \Carbon\Carbon::parse($expense->date)->format('Y-m-d');
        })->map(function($expenseGroup) {
            return $expenseGroup->sum('amount');
        });

        $allDates = $incomeByDate->keys()->merge($expenseByDate->keys())->unique()->sortDesc()->values();
        foreach ($allDates as $date) {
            $dateObj = \Carbon\Carbon::parse($date);
            $totalIncome = $incomeByDate[$date] ?? 0;
            $totalExpense = $expenseByDate[$date] ?? 0;
            $summaryRecords->push([
                'campus' => $filterCampus ?: 'All Campuses',
                'month' => $dateObj->format('F'),
                'date' => $dateObj->format('d M Y'),
                'total_income' => $totalIncome,
                'total_expense' => $totalExpense,
                'profit_loss' => $totalIncome - $totalExpense,
                'year' => $dateObj->format('Y'),
            ]);
        }

        return view('reports.accounts-summary', compact(
            'campuses',
            'months',
            'years',
            'summaryRecords',
            'filterCampus',
            'filterMonth',
            'filterYear'
        ));
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

