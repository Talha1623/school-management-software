<?php

namespace App\Http\Controllers;

use App\Models\Campus;
use App\Models\ClassModel;
use App\Models\Section;
use App\Models\Staff;
use App\Models\Salary;
use App\Models\Loan;
use App\Models\StaffAttendance;
use App\Models\SalarySetting;
use Carbon\Carbon;
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
        $generatedSalaries = collect(session('generated_salaries', []));
        $generatedCampus = session('generated_campus');
        $generatedMonth = session('generated_month');
        $generatedYear = session('generated_year');

        if ($generatedSalaries->isNotEmpty()) {
            $salaryIds = $generatedSalaries->pluck('id')->filter();
            if ($salaryIds->isNotEmpty()) {
                $generatedSalaries = Salary::with('staff')->whereIn('id', $salaryIds)->get();
                foreach ($generatedSalaries as $salary) {
                    if (!$salary->staff) {
                        continue;
                    }
                    $monthNumber = $this->getMonthNumber($salary->salary_month);
                    $attendanceSummary = $this->calculateAttendanceSummary($salary->staff_id, (int) $salary->year, $monthNumber);
                    $basicRate = (float) ($salary->staff->salary ?? 0);
                    $salaryGenerated = $this->calculateSalaryGenerated($salary->staff, $attendanceSummary, 0, (int) $salary->year, (int) $monthNumber);

                    if ($salary->basic != $basicRate) {
                        $salary->update(['basic' => $basicRate]);
                    }
                    if ($salary->status === 'Pending' && $salary->salary_generated != $salaryGenerated) {
                        $salary->update(['salary_generated' => $salaryGenerated]);
                    }
                }
            }
        }

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
                
                // Update loan repayment and attendance-based fields
                foreach ($generatedSalaries as $salary) {
                    $loanRepayment = $this->calculateLoanRepayment($salary->staff_id);
                    $attendanceSummary = $this->calculateAttendanceSummary($salary->staff_id, $year, $month);
                    $presentCount = $attendanceSummary['present'];
                    $absentCount = $attendanceSummary['absent'];
                    $lateCount = $attendanceSummary['late'];
                    $basicRate = (float) ($salary->staff->salary ?? 0);
                    $salaryGenerated = $this->calculateSalaryGenerated($salary->staff, $attendanceSummary, (float) $deductionPerLateArrival, (int) $year, (int) $month);

                    $updates = [];
                    if ($salary->loan_repayment != $loanRepayment) {
                        $updates['loan_repayment'] = $loanRepayment;
                    }
                    if ($salary->present != $presentCount || $salary->absent != $absentCount || $salary->late != $lateCount) {
                        $updates['present'] = $presentCount;
                        $updates['absent'] = $absentCount;
                        $updates['late'] = $lateCount;
                    }
                    if ($salary->basic != $basicRate) {
                        $updates['basic'] = $basicRate;
                    }
                    if ($salary->status === 'Pending' && $salary->salary_generated != $salaryGenerated) {
                        $updates['salary_generated'] = $salaryGenerated;
                    }
                    if (!empty($updates)) {
                        $salary->update($updates);
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

                // Calculate attendance counts for selected month/year
                $attendanceSummary = $this->calculateAttendanceSummary($staff->id, $year, $month);
                $presentCount = $attendanceSummary['present'];
                $absentCount = $attendanceSummary['absent'];
                $lateCount = $attendanceSummary['late'];
                $basicRate = (float) ($staff->salary ?? 0);
                $salaryGenerated = $this->calculateSalaryGenerated($staff, $attendanceSummary, (float) $deductionPerLateArrival, (int) $year, (int) $month);

                // Create salary record
                Salary::create([
                    'staff_id' => $staff->id,
                    'salary_month' => $monthName,
                    'year' => (string)$year,
                    'present' => $presentCount,
                    'absent' => $absentCount,
                    'late' => $lateCount,
                    'basic' => $basicRate,
                    'salary_generated' => $salaryGenerated,
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
                $attendanceSummary = $this->calculateAttendanceSummary($salary->staff_id, $year, $month);
                $presentCount = $attendanceSummary['present'];
                $absentCount = $attendanceSummary['absent'];
                $lateCount = $attendanceSummary['late'];
                $basicRate = (float) ($salary->staff->salary ?? 0);
                $salaryGenerated = $this->calculateSalaryGenerated($salary->staff, $attendanceSummary, (float) $deductionPerLateArrival, (int) $year, (int) $month);
                if ($salary->loan_repayment != $loanRepayment) {
                    $salary->update(['loan_repayment' => $loanRepayment]);
                }
                if ($salary->present != $presentCount || $salary->absent != $absentCount || $salary->late != $lateCount) {
                    $salary->update([
                        'present' => $presentCount,
                        'absent' => $absentCount,
                        'late' => $lateCount,
                    ]);
                }
                if ($salary->basic != $basicRate) {
                    $salary->update(['basic' => $basicRate]);
                }
                if ($salary->status === 'Pending' && $salary->salary_generated != $salaryGenerated) {
                    $salary->update(['salary_generated' => $salaryGenerated]);
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

        // Calculate new salary generated (base + bonus - deduction)
        $bonusAmount = $validated['bonus_amount'] ?? 0;
        $deductionAmount = $validated['deduction_amount'] ?? 0;
        $monthNumber = $this->getMonthNumber($salary->salary_month);
        $attendanceSummary = $this->calculateAttendanceSummary($salary->staff_id, (int) $salary->year, $monthNumber);
        $baseSalaryGenerated = $this->calculateSalaryGenerated($salary->staff, $attendanceSummary, 0, (int) $salary->year, (int) $monthNumber);
        $newSalaryGenerated = $baseSalaryGenerated + $bonusAmount - $deductionAmount;

        // Determine status based on fully_paid or amount_paid
        $fullyPaid = isset($validated['fully_paid']) && ($validated['fully_paid'] == '1' || $validated['fully_paid'] === true);
        $amountPaid = $validated['amount_paid'] ?? 0;
        
        // If form is submitted with payment data, update status
        $status = 'Pending';
        if ($fullyPaid || $amountPaid >= $newSalaryGenerated) {
            $status = 'Paid';
        } elseif ($amountPaid > 0) {
            $status = 'Issued';
        }
        
        // If amount_paid is greater than 0, status should not be Pending
        if ($amountPaid > 0 && $status == 'Pending') {
            $status = 'Issued';
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

    private function calculateAttendanceSummary(int $staffId, int $year, string $month): array
    {
        $records = StaffAttendance::where('staff_id', $staffId)
            ->whereYear('attendance_date', $year)
            ->whereMonth('attendance_date', $month)
            ->get();

        $settings = SalarySetting::getSettings();
        $lateArrivalTime = $settings->late_arrival_time ?? '09:00:00';

        $present = 0;
        $absent = 0;
        $leave = 0;
        $late = 0;
        $totalLectures = 0;
        $totalMinutes = 0;

        foreach ($records as $record) {
            if ($record->status === 'Present') {
                $present++;
            } elseif ($record->status === 'Absent') {
                $absent++;
            } elseif ($record->status === 'Leave') {
                $leave++;
            }

            $lateFlag = false;
            if (!empty($record->start_time)) {
                try {
                    $date = $record->attendance_date ? $record->attendance_date->format('Y-m-d') : Carbon::now()->format('Y-m-d');
                    $startTime = Carbon::parse($date . ' ' . $record->start_time);
                    $standardTime = Carbon::parse($date . ' ' . $lateArrivalTime);
                    if ($startTime->greaterThan($standardTime)) {
                        $lateFlag = true;
                    }
                } catch (\Exception $e) {
                    // Skip invalid times
                }
            }

            if (!$lateFlag && !empty($record->remarks) && stripos($record->remarks, 'Late Arrival') !== false) {
                $lateFlag = true;
            }

            if ($lateFlag) {
                $late++;
            }

            $totalLectures += (int) ($record->conducted_lectures ?? 0);

            if (!empty($record->start_time) && !empty($record->end_time)) {
                try {
                    $date = $record->attendance_date ? $record->attendance_date->format('Y-m-d') : Carbon::now()->format('Y-m-d');
                    $startTime = Carbon::parse($date . ' ' . $record->start_time);
                    $endTime = Carbon::parse($date . ' ' . $record->end_time);
                    if ($endTime->greaterThan($startTime)) {
                        $totalMinutes += $startTime->diffInMinutes($endTime);
                    }
                } catch (\Exception $e) {
                    // Skip invalid times
                }
            }
        }

        return [
            'present' => $present,
            'absent' => $absent,
            'late' => $late,
            'leave' => $leave,
            'total_lectures' => $totalLectures,
            'total_minutes' => $totalMinutes,
        ];
    }

    private function calculateSalaryGenerated(Staff $staff, array $attendanceSummary, float $deductionPerLateArrival = 0, ?int $year = null, ?int $month = null): float
    {
        $rate = (float) ($staff->salary ?? 0);
        $salaryType = strtolower(trim($staff->salary_type ?? ''));

        if ($salaryType === 'per hour') {
            $totalMinutes = $attendanceSummary['total_minutes'];
            if ($totalMinutes <= 0 && $attendanceSummary['present'] > 0) {
                // Fallback: treat each present day as 1 hour if no time data
                $totalMinutes = $attendanceSummary['present'] * 60;
            }
            $hours = $totalMinutes / 60;
            return round($hours * $rate, 2);
        }

        if ($salaryType === 'lecture') {
            return round($attendanceSummary['total_lectures'] * $rate, 2);
        }

        $settings = SalarySetting::getSettings();
        $freeAbsents = (int) ($settings->free_absents ?? 0);
        $leaveDeduction = strtolower(trim($settings->leave_deduction ?? 'no')) === 'yes';
        $absentCount = (int) ($attendanceSummary['absent'] ?? 0);
        $leaveCount = (int) ($attendanceSummary['leave'] ?? 0);
        $lateCount = (int) ($attendanceSummary['late'] ?? 0);

        $deductibleAbsents = $absentCount + ($leaveDeduction ? $leaveCount : 0);
        $deductibleAbsents = max(0, $deductibleAbsents - $freeAbsents);

        $daysInMonth = 30;
        if (!empty($year) && !empty($month)) {
            try {
                $daysInMonth = Carbon::createFromDate($year, $month, 1)->daysInMonth;
            } catch (\Exception $e) {
                $daysInMonth = 30;
            }
        }
        $dailyRate = $daysInMonth > 0 ? ($rate / $daysInMonth) : 0;
        $lateDeduction = max(0, $deductionPerLateArrival) * $lateCount;
        $absentDeduction = $dailyRate * $deductibleAbsents;

        return round(max(0, $rate - $absentDeduction - $lateDeduction), 2);
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

