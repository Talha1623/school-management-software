<?php

namespace App\Http\Controllers;

use App\Models\Salary;
use App\Models\Staff;
use App\Models\Campus;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;

class StaffSalaryReportController extends Controller
{
    /**
     * Display the staff salary reports with filters.
     */
    public function index(Request $request): View
    {
        // Get filter values
        $filterCampus = $request->get('filter_campus');
        $filterMonth = $request->get('filter_month');
        $filterYear = $request->get('filter_year');
        $filterStaffId = $request->get('staff_id');

        // Get campuses from Campus model first, then fallback to staff
        $campuses = Campus::orderBy('campus_name', 'asc')->pluck('campus_name');
        if ($campuses->isEmpty()) {
            $campuses = Staff::whereNotNull('campus')->distinct()->pluck('campus')->sort()->values();
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

        // Query salaries with staff information
        $query = Salary::with('staff');

        if ($filterMonth) {
            $query->where('salary_month', $filterMonth);
        }
        if ($filterYear) {
            $query->where('year', $filterYear);
        }
        if ($filterStaffId) {
            $query->where('staff_id', $filterStaffId);
        }

        $salaries = $query->orderBy('year', 'desc')
                          ->orderBy('salary_month', 'desc')
                          ->get();

        // Filter by campus if specified
        $salaryRecords = collect();
        
        foreach ($salaries as $salary) {
            if ($salary->staff) {
                // Apply campus filter
                if ($filterCampus && $salary->staff->campus != $filterCampus) {
                    continue;
                }
                
                $salaryRecords->push([
                    'staff_id' => $salary->staff_id,
                    'staff_name' => $salary->staff->name,
                    'emp_id' => $salary->staff->emp_id,
                    'campus' => $salary->staff->campus,
                    'designation' => $salary->staff->designation,
                    'photo' => $salary->staff->photo,
                    'salary_month' => $salary->salary_month,
                    'year' => $salary->year,
                    'present' => $salary->present,
                    'absent' => $salary->absent,
                    'late' => $salary->late,
                    'early_exit' => $salary->early_exit ?? 0,
                    'basic' => $salary->basic,
                    'salary_generated' => $salary->salary_generated,
                    'amount_paid' => $salary->amount_paid,
                    'loan_repayment' => $salary->loan_repayment,
                    'status' => $salary->status,
                ]);
            }
        }

        return view('reports.staff-salary', compact(
            'campuses',
            'months',
            'years',
            'salaryRecords',
            'filterCampus',
            'filterMonth',
            'filterYear',
            'filterStaffId'
        ));
    }

    /**
     * Get staff by campus (AJAX).
     */
    public function getStaffByCampus(Request $request): JsonResponse
    {
        $campus = $request->get('campus');
        
        if (!$campus) {
            return response()->json(['staff' => []]);
        }

        $staff = Staff::where('campus', $campus)
            ->orderBy('name')
            ->get(['id', 'name', 'emp_id']);

        return response()->json(['staff' => $staff]);
    }

    /**
     * Get salary records (AJAX).
     */
    public function getSalaryRecords(Request $request): JsonResponse
    {
        $filterCampus = $request->get('filter_campus');
        $filterMonth = $request->get('filter_month');
        $filterYear = $request->get('filter_year');
        $filterStaffId = $request->get('staff_id');

        // Query salaries with staff information
        $query = Salary::with('staff');

        if ($filterMonth) {
            $query->where('salary_month', $filterMonth);
        }
        if ($filterYear) {
            $query->where('year', $filterYear);
        }
        if ($filterStaffId) {
            $query->where('staff_id', $filterStaffId);
        }

        $salaries = $query->orderBy('year', 'desc')
                          ->orderBy('salary_month', 'desc')
                          ->get();

        // Filter by campus if specified
        $salaryRecords = collect();
        
        foreach ($salaries as $salary) {
            if ($salary->staff) {
                // Apply campus filter
                if ($filterCampus && $salary->staff->campus != $filterCampus) {
                    continue;
                }
                
                $monthNames = ['01' => 'January', '02' => 'February', '03' => 'March', '04' => 'April', '05' => 'May', '06' => 'June', '07' => 'July', '08' => 'August', '09' => 'September', '10' => 'October', '11' => 'November', '12' => 'December'];
                
                $salaryRecords->push([
                    'staff_id' => $salary->staff_id,
                    'staff_name' => $salary->staff->name,
                    'emp_id' => $salary->staff->emp_id ?? 'N/A',
                    'campus' => $salary->staff->campus ?? 'N/A',
                    'designation' => $salary->staff->designation ?? 'N/A',
                    'photo' => $salary->staff->photo,
                    'salary_month' => $monthNames[$salary->salary_month] ?? $salary->salary_month,
                    'year' => $salary->year,
                    'present' => $salary->present,
                    'absent' => $salary->absent,
                    'late' => $salary->late,
                    'early_exit' => $salary->early_exit ?? 0,
                    'basic' => number_format($salary->basic, 2),
                    'salary_generated' => number_format($salary->salary_generated, 2),
                    'amount_paid' => number_format($salary->amount_paid, 2),
                    'loan_repayment' => number_format($salary->loan_repayment, 2),
                    'status' => $salary->status,
                ]);
            }
        }

        // Calculate totals from original salary records
        $totalBasic = $salaries->sum('basic');
        $totalSalaryGenerated = $salaries->sum('salary_generated');
        $totalAmountPaid = $salaries->sum('amount_paid');
        $totalLoanRepayment = $salaries->sum('loan_repayment');
        
        // Apply campus filter for totals
        if ($filterCampus) {
            $filteredSalaries = $salaries->filter(function($salary) use ($filterCampus) {
                return $salary->staff && $salary->staff->campus == $filterCampus;
            });
            $totalBasic = $filteredSalaries->sum('basic');
            $totalSalaryGenerated = $filteredSalaries->sum('salary_generated');
            $totalAmountPaid = $filteredSalaries->sum('amount_paid');
            $totalLoanRepayment = $filteredSalaries->sum('loan_repayment');
        }

        return response()->json([
            'success' => true,
            'records' => $salaryRecords,
            'total_basic' => number_format($totalBasic, 2),
            'total_salary_generated' => number_format($totalSalaryGenerated, 2),
            'total_amount_paid' => number_format($totalAmountPaid, 2),
            'total_loan_repayment' => number_format($totalLoanRepayment, 2),
        ]);
    }

    /**
     * Display the summarized salary & attendance report for all staff.
     */
    public function summarized(Request $request): View
    {
        // Get filter values
        $filterCampus = $request->get('filter_campus');
        $filterYear = $request->get('filter_year', date('Y')); // Default to current year

        // Get all staff (dynamic - will include any newly added staff)
        $staffQuery = Staff::query();
        
        if ($filterCampus) {
            $staffQuery->where('campus', $filterCampus);
        }

        // Fetch all staff members - automatically includes newly added staff
        $allStaff = $staffQuery->orderBy('name')->get();

        // Month names
        $monthNames = [
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
        ];

        // Get campuses for filter
        $campuses = Staff::whereNotNull('campus')->distinct()->pluck('campus')->sort()->values();
        
        if ($campuses->isEmpty()) {
            $campuses = collect(['Main Campus', 'Branch Campus 1', 'Branch Campus 2']);
        }

        // Year options (current year and previous 5 years)
        $currentYear = date('Y');
        $years = collect();
        for ($i = 0; $i < 6; $i++) {
            $years->push($currentYear - $i);
        }

        // Prepare staff data with monthly salary records
        $staffReports = collect();
        
        foreach ($allStaff as $staff) {
            // Get all salary records for this staff for the selected year
            $salaries = Salary::where('staff_id', $staff->id)
                ->where('year', $filterYear)
                ->get()
                ->keyBy('salary_month');

            // Prepare monthly data
            $monthlyData = [];
            foreach ($monthNames as $monthNum => $monthName) {
                $salary = $salaries->get($monthNum);
                
                $monthlyData[] = [
                    'month' => $monthName,
                    'month_num' => $monthNum,
                    'present' => $salary ? $salary->present : 0,
                    'absent' => $salary ? $salary->absent : 0,
                    'late' => $salary ? $salary->late : 0,
                    'leaves' => $salary ? $salary->leaves : 0,
                    'holidays' => $salary ? $salary->holidays : 0,
                    'sundays' => $salary ? $salary->sundays : 0,
                    'basic_salary' => $salary ? $salary->basic : ($staff->salary ?? 0),
                    'salary_generated' => $salary ? $salary->salary_generated : 0,
                    'amount_paid' => $salary ? $salary->amount_paid : 0,
                    'loan_repayment' => $salary ? $salary->loan_repayment : 0,
                ];
            }

            $staffReports->push([
                'staff' => $staff,
                'monthly_data' => $monthlyData,
            ]);
        }

        return view('reports.staff-salary-summarized', compact(
            'staffReports',
            'filterCampus',
            'filterYear',
            'campuses',
            'years',
            'monthNames'
        ));
    }
}

