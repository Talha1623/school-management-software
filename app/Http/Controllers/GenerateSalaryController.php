<?php

namespace App\Http\Controllers;

use App\Models\Campus;
use App\Models\ClassModel;
use App\Models\Section;
use App\Models\Staff;
use App\Models\Salary;
use App\Models\Loan;
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
     * Get staff list for salary generation (AJAX).
     */
    public function getStaffList(Request $request)
    {
        $campus = $request->get('campus');
        $month = $request->get('month');
        $year = $request->get('year');

        if (!$campus || !$month || !$year) {
            return response()->json(['staff' => []]);
        }

        // Get month name
        $monthNames = [
            '01' => 'January', '02' => 'February', '03' => 'March', '04' => 'April',
            '05' => 'May', '06' => 'June', '07' => 'July', '08' => 'August',
            '09' => 'September', '10' => 'October', '11' => 'November', '12' => 'December'
        ];
        $monthName = $monthNames[$month] ?? $month;

        // Get all staff members from the selected campus (case-insensitive, trimmed)
        // This ensures newly added teachers are included
        $staffMembers = Staff::whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))])
            ->orderBy('name', 'asc')
            ->get();

        $staffList = [];
        foreach ($staffMembers as $staff) {
            // Check if salary already exists
            $existingSalary = Salary::where('staff_id', $staff->id)
                ->where('salary_month', $monthName)
                ->where('year', (string)$year)
                ->first();

            $staffList[] = [
                'id' => $staff->id,
                'emp_id' => $staff->emp_id ?? 'N/A',
                'name' => $staff->name,
                'designation' => $staff->designation ?? 'N/A',
                'is_generated' => $existingSalary ? true : false,
            ];
        }

        return response()->json(['staff' => $staffList]);
    }

    /**
     * Process the salary generation.
     */
    public function store(Request $request): RedirectResponse
    {
        try {
            $validated = $request->validate([
                'campus' => ['required', 'string', 'max:255'],
                'month' => ['required', 'string', 'max:255'],
                'year' => ['required', 'integer', 'min:2000', 'max:2100'],
                'deduction_per_late_arrival' => ['nullable', 'numeric', 'min:0'],
                'selected_staff' => ['nullable', 'array'],
                'selected_staff.*' => ['nullable', 'exists:staff,id'],
            ]);

            $campus = $validated['campus'];
            $month = $validated['month'];
            $year = $validated['year'];
            $deductionPerLateArrival = $validated['deduction_per_late_arrival'] ?? 0;
            
            // Get selected staff IDs - handle both array and null cases
            $selectedStaffIds = $request->input('selected_staff', []);
            if (!is_array($selectedStaffIds)) {
                $selectedStaffIds = [];
            }
            // Filter out empty values
            $selectedStaffIds = array_filter($selectedStaffIds, function($id) {
                return !empty($id);
            });

            // Get month name (needed for both new generation and showing existing)
            $monthNames = [
                '01' => 'January', '02' => 'February', '03' => 'March', '04' => 'April',
                '05' => 'May', '06' => 'June', '07' => 'July', '08' => 'August',
                '09' => 'September', '10' => 'October', '11' => 'November', '12' => 'December'
            ];
            $monthName = $monthNames[$month] ?? $month;
            
            // Handle case where selected_staff might be empty array or null
            // But still show existing generated salaries if any exist for this campus/month/year
            if (empty($selectedStaffIds)) {
                // Get existing generated salaries for the selected campus, month, and year
                $generatedSalaries = Salary::with('staff')
                    ->whereHas('staff', function($q) use ($campus) {
                        $q->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
                    })
                    ->where('salary_month', $monthName)
                    ->where('year', (string)$year)
                    ->orderBy('created_at', 'desc')
                    ->get();
                
                // Update loan repayment for all salaries based on current approved loans
                foreach ($generatedSalaries as $salary) {
                    $loanRepayment = $this->calculateLoanRepayment($salary->staff_id);
                    if ($salary->loan_repayment != $loanRepayment) {
                        $salary->update(['loan_repayment' => $loanRepayment]);
                    }
                }
                
                // If there are existing salaries, show them in the table
                if ($generatedSalaries->count() > 0) {
                    return redirect()
                        ->route('salary-loan.generate-salary')
                        ->with('info', 'No new salaries generated. Showing existing generated salaries.')
                        ->with('generated_salaries', $generatedSalaries)
                        ->with('generated_campus', $campus)
                        ->with('generated_month', $month)
                        ->with('generated_year', $year);
                }
                
                return redirect()
                    ->route('salary-loan.generate-salary')
                    ->with('error', 'Please select at least one staff member to generate salary.')
                    ->withInput();
            }


            $generatedCount = 0;
            $skippedCount = 0;

            // Generate salary for selected staff members only
            foreach ($selectedStaffIds as $staffId) {
                $staff = Staff::find($staffId);
                if (!$staff) {
                    continue;
                }

                // Verify staff belongs to selected campus (case-insensitive)
                if (strtolower(trim($staff->campus ?? '')) !== strtolower(trim($campus))) {
                    continue;
                }

                // Check if salary already exists for this staff, month, and year
                $existingSalary = Salary::where('staff_id', $staff->id)
                    ->where('salary_month', $monthName)
                    ->where('year', (string)$year)
                    ->first();

                if ($existingSalary) {
                    // If already exists, skip creating new one but count it
                    $skippedCount++;
                    continue;
                }

                // Calculate loan repayment from approved loans
                $loanRepayment = $this->calculateLoanRepayment($staff->id);

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
                    'loan_repayment' => $loanRepayment,
                    'status' => 'Pending',
                ]);

                $generatedCount++;
            }

            // Get generated salaries for the selected campus, month, and year
            // Include both newly generated and already existing salaries
            // Use case-insensitive matching to include all staff from the campus
            $generatedSalaries = Salary::with('staff')
                ->whereHas('staff', function($q) use ($campus) {
                    $q->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
                })
                ->where('salary_month', $monthName)
                ->where('year', (string)$year)
                ->orderBy('created_at', 'desc')
                ->get();
            
            // Update loan repayment for all salaries based on current approved loans
            foreach ($generatedSalaries as $salary) {
                $loanRepayment = $this->calculateLoanRepayment($salary->staff_id);
                if ($salary->loan_repayment != $loanRepayment) {
                    $salary->update(['loan_repayment' => $loanRepayment]);
                }
            }

            $message = "Salary generated successfully for {$generatedCount} staff member(s).";
            if ($skippedCount > 0) {
                $message .= " {$skippedCount} salary record(s) already existed and were skipped.";
            }
            
            // If no new salaries were generated but existing ones exist, show info message
            if ($generatedCount == 0 && $generatedSalaries->count() > 0) {
                $message = "No new salaries generated. Showing {$generatedSalaries->count()} existing generated salary record(s).";
            }

            return redirect()
                ->route('salary-loan.generate-salary')
                ->with('success', $message)
                ->with('generated_salaries', $generatedSalaries)
                ->with('generated_campus', $campus)
                ->with('generated_month', $month)
                ->with('generated_year', $year);
        } catch (\Exception $e) {
            return redirect()
                ->route('salary-loan.generate-salary')
                ->with('error', 'An error occurred: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Update salary payment.
     */
    public function updatePayment(Request $request, Salary $salary): RedirectResponse
    {
        $validated = $request->validate([
            'amount_paid' => ['required', 'numeric', 'min:0'],
            'loan_repayment' => ['nullable', 'numeric', 'min:0'],
            'bonus_title' => ['nullable', 'string', 'max:255'],
            'bonus_amount' => ['nullable', 'numeric', 'min:0'],
            'deduction_title' => ['nullable', 'string', 'max:255'],
                'deduction_amount' => ['nullable', 'numeric', 'min:0'],
                'payment_method' => ['required', 'string', 'in:Bank,Wallet,Transfer,Card,Check,Deposit,Cash'],
            'fully_paid' => ['nullable', 'string', 'in:0,1'],
            'payment_date' => ['required', 'date'],
            'notify_employee' => ['nullable', 'string', 'in:0,1'],
        ]);

        // Calculate new salary generated (basic + bonus - deduction)
        $bonusAmount = $validated['bonus_amount'] ?? 0;
        $deductionAmount = $validated['deduction_amount'] ?? 0;
        $newSalaryGenerated = $salary->basic + $bonusAmount - $deductionAmount;

        // Determine status based on fully_paid or amount_paid
        $fullyPaid = isset($validated['fully_paid']) && ($validated['fully_paid'] == '1' || $validated['fully_paid'] === true);
        $amountPaid = $validated['amount_paid'] ?? 0;
        
        // If form is submitted with payment data, update status
        $status = 'Pending';
        if ($fullyPaid || $amountPaid >= $newSalaryGenerated) {
            $status = 'Paid';
        } elseif ($amountPaid > 0) {
            $status = 'Partial';
        }
        
        // If amount_paid is greater than 0, status should not be Pending
        if ($amountPaid > 0 && $status == 'Pending') {
            $status = 'Partial';
        }

        // Calculate loan repayment from approved loans (if not manually set)
        $loanRepayment = $validated['loan_repayment'] ?? 0;
        if ($loanRepayment == 0) {
            $loanRepayment = $this->calculateLoanRepayment($salary->staff_id);
        }

        // Update salary
        $salary->update([
            'amount_paid' => $validated['amount_paid'],
            'loan_repayment' => $loanRepayment,
            'salary_generated' => $newSalaryGenerated,
            'status' => $status,
        ]);

        // TODO: Store bonus, deduction, payment method, payment date, and notify_employee details if needed (may require additional table)

        // Get the updated salary with staff relationship
        $salary->refresh();
        $salary->load('staff');
        
        // Get all generated salaries for the same campus, month, and year to show in table
        $generatedSalaries = Salary::with('staff')
            ->whereHas('staff', function($q) use ($salary) {
                $q->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($salary->staff->campus ?? ''))]);
            })
            ->where('salary_month', $salary->salary_month)
            ->where('year', $salary->year)
            ->orderBy('created_at', 'desc')
            ->get();

        return redirect()
            ->route('salary-loan.generate-salary')
            ->with('success', 'Payment updated successfully. Status changed to ' . $status . '.')
            ->with('generated_salaries', $generatedSalaries)
            ->with('generated_campus', $salary->staff->campus ?? '')
            ->with('generated_month', $this->getMonthNumber($salary->salary_month))
            ->with('generated_year', $salary->year);
    }
    
    /**
     * Calculate loan repayment for a staff member based on approved loans
     */
    private function calculateLoanRepayment($staffId): float
    {
        // Get all approved loans for this staff member
        $approvedLoans = Loan::where('staff_id', $staffId)
            ->where('status', 'Approved')
            ->get();
        
        $totalLoanRepayment = 0;
        
        foreach ($approvedLoans as $loan) {
            // Calculate monthly installment: approved_amount / repayment_instalments
            if ($loan->approved_amount && $loan->repayment_instalments > 0) {
                $monthlyInstallment = $loan->approved_amount / $loan->repayment_instalments;
                $totalLoanRepayment += $monthlyInstallment;
            }
        }
        
        return round($totalLoanRepayment, 2);
    }

    /**
     * Get month number from month name
     */
    private function getMonthNumber($monthName)
    {
        $monthNames = [
            'January' => '01', 'February' => '02', 'March' => '03', 'April' => '04',
            'May' => '05', 'June' => '06', 'July' => '07', 'August' => '08',
            'September' => '09', 'October' => '10', 'November' => '11', 'December' => '12'
        ];
        return $monthNames[$monthName] ?? date('m');
    }

    /**
     * Print salary slip.
     */
    public function printSlip(Salary $salary): View
    {
        $salary->load('staff');
        
        return view('salary-loan.print-slip', compact('salary'));
    }

    /**
     * Delete salary record.
     */
    public function destroy(Salary $salary): RedirectResponse
    {
        $salary->delete();

        return redirect()
            ->route('salary-loan.generate-salary')
            ->with('success', 'Salary record deleted successfully.');
    }
}

