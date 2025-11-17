<?php

namespace App\Http\Controllers;

use App\Models\Campus;
use App\Models\ClassModel;
use App\Models\Section;
use App\Models\Staff;
use App\Models\Salary;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GenerateSalaryController extends Controller
{
    /**
     * Display the generate salary form.
     */
    public function index(): View
    {
        // Get campuses from Campus model
        $campuses = Campus::orderBy('campus_name', 'asc')->get();
        
        // If no campuses found, get from classes or sections
        if ($campuses->isEmpty()) {
            $campusesFromClasses = ClassModel::whereNotNull('campus')
                ->distinct()
                ->pluck('campus')
                ->sort()
                ->values();
            
            $campusesFromSections = Section::whereNotNull('campus')
                ->distinct()
                ->pluck('campus')
                ->sort()
                ->values();
            
            $allCampuses = $campusesFromClasses->merge($campusesFromSections)->unique()->sort()->values();
            
            // Convert to collection of objects with campus_name property
            $campuses = collect();
            foreach ($allCampuses as $campusName) {
                $campuses->push((object)['campus_name' => $campusName]);
            }
        }

        // Get current month and year as defaults
        $currentMonth = date('m');
        $currentYear = date('Y');

        // Get generated salaries from session (if any)
        $generatedSalaries = session('generated_salaries', collect());
        $generatedCampus = session('generated_campus');
        $generatedMonth = session('generated_month');
        $generatedYear = session('generated_year');

        return view('salary-loan.generate-salary', compact('campuses', 'currentMonth', 'currentYear', 'generatedSalaries', 'generatedCampus', 'generatedMonth', 'generatedYear'));
    }

    /**
     * Process the salary generation.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'campus' => ['required', 'string', 'max:255'],
            'month' => ['required', 'string', 'max:255'],
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'deduction_per_late_arrival' => ['nullable', 'numeric', 'min:0'],
        ]);

        $campus = $validated['campus'];
        $month = $validated['month'];
        $year = $validated['year'];
        $deductionPerLateArrival = $validated['deduction_per_late_arrival'] ?? 0;

        // Get all staff members from the selected campus
        $staffMembers = Staff::where('campus', $campus)->get();

        if ($staffMembers->isEmpty()) {
            return redirect()
                ->route('salary-loan.generate-salary')
                ->with('error', 'No staff members found for the selected campus.');
        }

        // Get month name
        $monthNames = [
            '01' => 'January', '02' => 'February', '03' => 'March', '04' => 'April',
            '05' => 'May', '06' => 'June', '07' => 'July', '08' => 'August',
            '09' => 'September', '10' => 'October', '11' => 'November', '12' => 'December'
        ];
        $monthName = $monthNames[$month] ?? $month;

        $generatedCount = 0;
        $skippedCount = 0;

        // Generate salary for each staff member
        foreach ($staffMembers as $staff) {
            // Check if salary already exists for this staff, month, and year
            $existingSalary = Salary::where('staff_id', $staff->id)
                ->where('salary_month', $monthName)
                ->where('year', (string)$year)
                ->first();

            if ($existingSalary) {
                $skippedCount++;
                continue;
            }

            // Create salary record
            Salary::create([
                'staff_id' => $staff->id,
                'salary_month' => $monthName,
                'year' => (string)$year,
                'present' => 0,
                'absent' => 0,
                'late' => 0,
                'basic' => $staff->salary ?? 0,
                'salary_generated' => $staff->salary ?? 0,
                'amount_paid' => 0,
                'loan_repayment' => 0,
                'status' => 'Pending',
            ]);

            $generatedCount++;
        }

        $message = "Salary generated successfully for {$generatedCount} staff member(s).";
        if ($skippedCount > 0) {
            $message .= " {$skippedCount} salary record(s) already existed and were skipped.";
        }

        // Get generated salaries for the selected campus, month, and year
        $generatedSalaries = Salary::with('staff')
            ->whereHas('staff', function($q) use ($campus) {
                $q->where('campus', $campus);
            })
            ->where('salary_month', $monthName)
            ->where('year', (string)$year)
            ->orderBy('created_at', 'desc')
            ->get();

        return redirect()
            ->route('salary-loan.generate-salary')
            ->with('success', $message)
            ->with('generated_salaries', $generatedSalaries)
            ->with('generated_campus', $campus)
            ->with('generated_month', $month)
            ->with('generated_year', $year);
    }
}

