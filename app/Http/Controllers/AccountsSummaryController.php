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
        $filterType = $request->get('filter_type'); // day_by_day or month_by_month
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

        // Type options (day by day or month by month)
        $typeOptions = collect([
            'day_by_day' => 'Day by Day',
            'month_by_month' => 'Month by Month'
        ]);

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
        $groupByDay = ($filterType == 'day_by_day');

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
        
        if ($groupByDay) {
            // Group by day
            $studentPaymentsByDay = $studentPayments->groupBy(function($payment) {
                return \Carbon\Carbon::parse($payment->payment_date)->format('Y-m-d');
            });
            foreach ($studentPaymentsByDay as $date => $payments) {
                $total = $payments->sum('payment_amount');
                $discount = $payments->sum('discount');
                if ($total > 0) {
                    $summaryRecords->push([
                        'type' => 'Income',
                        'category' => 'Student Payments',
                        'campus' => $filterCampus ?: 'All Campuses',
                        'date' => \Carbon\Carbon::parse($date)->format('d M Y'),
                        'month' => \Carbon\Carbon::parse($date)->format('F'),
                        'year' => \Carbon\Carbon::parse($date)->format('Y'),
                        'total_amount' => $total,
                        'discount' => $discount,
                        'net_amount' => $total - $discount,
                        'count' => $payments->count(),
                    ]);
                }
            }
        } else {
            // Group by month
            $studentPaymentsByMonth = $studentPayments->groupBy(function($payment) {
                return \Carbon\Carbon::parse($payment->payment_date)->format('Y-m');
            });
            foreach ($studentPaymentsByMonth as $monthKey => $payments) {
                $total = $payments->sum('payment_amount');
                $discount = $payments->sum('discount');
                if ($total > 0) {
                    $date = \Carbon\Carbon::parse($monthKey . '-01');
                    $summaryRecords->push([
                        'type' => 'Income',
                        'category' => 'Student Payments',
                        'campus' => $filterCampus ?: 'All Campuses',
                        'date' => null,
                        'month' => $date->format('F'),
                        'year' => $date->format('Y'),
                        'total_amount' => $total,
                        'discount' => $discount,
                        'net_amount' => $total - $discount,
                        'count' => $payments->count(),
                    ]);
                }
            }
        }

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
        
        if ($groupByDay) {
            $customPaymentsByDay = $customPayments->groupBy(function($payment) {
                return \Carbon\Carbon::parse($payment->payment_date)->format('Y-m-d');
            });
            foreach ($customPaymentsByDay as $date => $payments) {
                $total = $payments->sum('payment_amount');
                if ($total > 0) {
                    $summaryRecords->push([
                        'type' => 'Income',
                        'category' => 'Custom Payments',
                        'campus' => $filterCampus ?: 'All Campuses',
                        'date' => \Carbon\Carbon::parse($date)->format('d M Y'),
                        'month' => \Carbon\Carbon::parse($date)->format('F'),
                        'year' => \Carbon\Carbon::parse($date)->format('Y'),
                        'total_amount' => $total,
                        'discount' => 0,
                        'net_amount' => $total,
                        'count' => $payments->count(),
                    ]);
                }
            }
        } else {
            $customPaymentsByMonth = $customPayments->groupBy(function($payment) {
                return \Carbon\Carbon::parse($payment->payment_date)->format('Y-m');
            });
            foreach ($customPaymentsByMonth as $monthKey => $payments) {
                $total = $payments->sum('payment_amount');
                if ($total > 0) {
                    $date = \Carbon\Carbon::parse($monthKey . '-01');
                    $summaryRecords->push([
                        'type' => 'Income',
                        'category' => 'Custom Payments',
                        'campus' => $filterCampus ?: 'All Campuses',
                        'date' => null,
                        'month' => $date->format('F'),
                        'year' => $date->format('Y'),
                        'total_amount' => $total,
                        'discount' => 0,
                        'net_amount' => $total,
                        'count' => $payments->count(),
                    ]);
                }
            }
        }

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
        
        if ($groupByDay) {
            $expensesByDay = $expenses->groupBy(function($expense) {
                return \Carbon\Carbon::parse($expense->date)->format('Y-m-d');
            });
            foreach ($expensesByDay as $date => $expenseGroup) {
                $total = $expenseGroup->sum('amount');
                if ($total > 0) {
                    $summaryRecords->push([
                        'type' => 'Expense',
                        'category' => 'Management Expenses',
                        'campus' => $filterCampus ?: 'All Campuses',
                        'date' => \Carbon\Carbon::parse($date)->format('d M Y'),
                        'month' => \Carbon\Carbon::parse($date)->format('F'),
                        'year' => \Carbon\Carbon::parse($date)->format('Y'),
                        'total_amount' => $total,
                        'discount' => 0,
                        'net_amount' => $total,
                        'count' => $expenseGroup->count(),
                    ]);
                }
            }
        } else {
            $expensesByMonth = $expenses->groupBy(function($expense) {
                return \Carbon\Carbon::parse($expense->date)->format('Y-m');
            });
            foreach ($expensesByMonth as $monthKey => $expenseGroup) {
                $total = $expenseGroup->sum('amount');
                if ($total > 0) {
                    $date = \Carbon\Carbon::parse($monthKey . '-01');
                    $summaryRecords->push([
                        'type' => 'Expense',
                        'category' => 'Management Expenses',
                        'campus' => $filterCampus ?: 'All Campuses',
                        'date' => null,
                        'month' => $date->format('F'),
                        'year' => $date->format('Y'),
                        'total_amount' => $total,
                        'discount' => 0,
                        'net_amount' => $total,
                        'count' => $expenseGroup->count(),
                    ]);
                }
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

