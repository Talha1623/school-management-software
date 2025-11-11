<?php

namespace App\Http\Controllers;

use App\Models\Salary;
use App\Models\Staff;
use Illuminate\Http\Request;
use Illuminate\View\View;

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

        // Get campuses from staff
        $campuses = Staff::whereNotNull('campus')->distinct()->pluck('campus')->sort()->values();
        
        if ($campuses->isEmpty()) {
            $campuses = collect(['Main Campus', 'Branch Campus 1', 'Branch Campus 2']);
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
            'filterYear'
        ));
    }
}

