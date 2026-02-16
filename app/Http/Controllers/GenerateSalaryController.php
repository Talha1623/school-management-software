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
use App\Models\Timetable;
use App\Models\Subject;
use App\Models\Student;
use App\Models\ParentAccount;
use App\Models\StudentPayment;
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
                    
                    // Calculate loan repayment from approved loans
                    $loanRepayment = $this->calculateLoanRepayment($salary->staff_id);
                    
                    // Calculate fee discount from student payments
                    $feeDiscount = $this->calculateFeeDiscount($salary->staff, (int) $salary->year, (int) $monthNumber);
                    // Subtract discount and loan repayment from salary generated
                    $finalSalaryGenerated = max(0, $salaryGenerated - $feeDiscount - $loanRepayment);

                    if ($salary->basic != $basicRate) {
                        $salary->update(['basic' => $basicRate]);
                    }
                    if ($salary->discount != $feeDiscount) {
                        $salary->update(['discount' => $feeDiscount]);
                    }
                    if (abs($salary->loan_repayment - $loanRepayment) > 0.01) {
                        $salary->update(['loan_repayment' => $loanRepayment]);
                    }
                    if ($salary->status === 'Pending' && abs($salary->salary_generated - $finalSalaryGenerated) > 0.01) {
                        $salary->update(['salary_generated' => $finalSalaryGenerated]);
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
                'selected_staff' => ['nullable', 'array'],
                'selected_staff.*' => ['nullable', 'exists:staff,id'],
            ]);

            $campus = $validated['campus'];
            $month = $validated['month'];
            $year = $validated['year'];
            $deductionPerLateArrival = 0;
            
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
                    $earlyExitCount = $attendanceSummary['early_exit'] ?? 0;
                    $basicRate = (float) ($salary->staff->salary ?? 0);
                    $salaryGenerated = $this->calculateSalaryGenerated($salary->staff, $attendanceSummary, (float) $deductionPerLateArrival, (int) $year, (int) $month);
                    
                    // Calculate fee discount from student payments
                    $feeDiscount = $this->calculateFeeDiscount($salary->staff, $year, $month);
                    // Subtract discount and loan repayment from salary generated
                    $finalSalaryGenerated = max(0, $salaryGenerated - $feeDiscount - $loanRepayment);

                    $updates = [];
                    if ($salary->loan_repayment != $loanRepayment) {
                        $updates['loan_repayment'] = $loanRepayment;
                    }
                    if ($salary->present != $presentCount || $salary->absent != $absentCount || $salary->late != $lateCount || $salary->early_exit != $earlyExitCount) {
                        $updates['present'] = $presentCount;
                        $updates['absent'] = $absentCount;
                        $updates['late'] = $lateCount;
                        $updates['early_exit'] = $earlyExitCount;
                    }
                    if ($salary->basic != $basicRate) {
                        $updates['basic'] = $basicRate;
                    }
                    if ($salary->discount != $feeDiscount) {
                        $updates['discount'] = $feeDiscount;
                    }
                    // Update salary_generated if status is Pending and it differs (loan repayment is already deducted in finalSalaryGenerated)
                    // Also update if loan repayment changed, as it affects salary_generated
                    // Use abs() comparison to handle floating point precision issues
                    if ($salary->status === 'Pending' && (abs($salary->salary_generated - $finalSalaryGenerated) > 0.01 || abs($salary->loan_repayment - $loanRepayment) > 0.01)) {
                        $updates['salary_generated'] = $finalSalaryGenerated;
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
                $basicRate = (float) ($staff->salary ?? 0);
                $salaryType = strtolower(trim($staff->salary_type ?? ''));
                
                // For per lecture salary type, don't count absent/late/early exit
                if ($salaryType === 'lecture') {
                    $absentCount = 0;
                    $lateCount = 0;
                    $earlyExitCount = 0;
                } else {
                    $absentCount = $attendanceSummary['absent'];
                    $lateCount = $attendanceSummary['late'];
                    $earlyExitCount = $attendanceSummary['early_exit'] ?? 0;
                }
                
                $salaryGenerated = $this->calculateSalaryGenerated($staff, $attendanceSummary, (float) $deductionPerLateArrival, (int) $year, (int) $month);

                // Calculate fee discount from student payments
                $feeDiscount = $this->calculateFeeDiscount($staff, $year, $month);
                
                // Subtract discount and loan repayment from salary generated
                $finalSalaryGenerated = max(0, $salaryGenerated - $feeDiscount - $loanRepayment);

                // Create salary record
                Salary::create([
                    'staff_id' => $staff->id,
                    'salary_month' => $monthName,
                    'year' => (string)$year,
                    'present' => $presentCount,
                    'absent' => $absentCount,
                    'late' => $lateCount,
                    'early_exit' => $earlyExitCount,
                    'basic' => $basicRate,
                    'salary_generated' => $finalSalaryGenerated,
                    'amount_paid' => 0,
                    'loan_repayment' => $loanRepayment,
                    'discount' => $feeDiscount,
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
                $basicRate = (float) ($salary->staff->salary ?? 0);
                $salaryType = strtolower(trim($salary->staff->salary_type ?? ''));
                
                // For per lecture salary type, don't count absent/late/early exit
                if ($salaryType === 'lecture') {
                    $absentCount = 0;
                    $lateCount = 0;
                    $earlyExitCount = 0;
                } else {
                    $absentCount = $attendanceSummary['absent'];
                    $lateCount = $attendanceSummary['late'];
                    $earlyExitCount = $attendanceSummary['early_exit'] ?? 0;
                }
                
                $salaryGenerated = $this->calculateSalaryGenerated($salary->staff, $attendanceSummary, (float) $deductionPerLateArrival, (int) $year, (int) $month);
                
                // Calculate fee discount from student payments
                $feeDiscount = $this->calculateFeeDiscount($salary->staff, $year, $month);
                
                // Subtract discount and loan repayment from salary generated
                $finalSalaryGenerated = max(0, $salaryGenerated - $feeDiscount - $loanRepayment);
                
                // Prepare updates array
                $updates = [];
                
                // Update loan repayment if changed
                if (abs($salary->loan_repayment - $loanRepayment) > 0.01) {
                    $updates['loan_repayment'] = $loanRepayment;
                }
                if ($salary->present != $presentCount || $salary->absent != $absentCount || $salary->late != $lateCount || $salary->early_exit != $earlyExitCount) {
                    $updates['present'] = $presentCount;
                    $updates['absent'] = $absentCount;
                    $updates['late'] = $lateCount;
                    $updates['early_exit'] = $earlyExitCount;
                }
                if ($salary->basic != $basicRate) {
                    $updates['basic'] = $basicRate;
                }
                if (abs($salary->discount - $feeDiscount) > 0.01) {
                    $updates['discount'] = $feeDiscount;
                }
                // Always update salary_generated if status is Pending (loan repayment is already deducted in finalSalaryGenerated)
                // This ensures old records are updated with correct loan deduction
                if ($salary->status === 'Pending') {
                    // Always update to ensure loan repayment is properly deducted
                    $updates['salary_generated'] = $finalSalaryGenerated;
                }
                
                // Apply all updates at once
                if (!empty($updates)) {
                    $salary->update($updates);
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

        // Calculate loan repayment from approved loans (if not manually set)
        $loanRepayment = $validated['loan_repayment'] ?? 0;
        if ($loanRepayment == 0) {
            $loanRepayment = $this->calculateLoanRepayment($salary->staff_id);
        }

        // Calculate new salary generated (base + bonus - deduction - discount - loan repayment)
        $bonusAmount = $validated['bonus_amount'] ?? 0;
        $deductionAmount = $validated['deduction_amount'] ?? 0;
        $monthNumber = $this->getMonthNumber($salary->salary_month);
        $attendanceSummary = $this->calculateAttendanceSummary($salary->staff_id, (int) $salary->year, $monthNumber);
        $baseSalaryGenerated = $this->calculateSalaryGenerated($salary->staff, $attendanceSummary, 0, (int) $salary->year, (int) $monthNumber);
        
        // Calculate fee discount from student payments
        $feeDiscount = $this->calculateFeeDiscount($salary->staff, (int) $salary->year, (int) $monthNumber);
        // Subtract discount and loan repayment from salary generated
        $newSalaryGenerated = max(0, $baseSalaryGenerated + $bonusAmount - $deductionAmount - $feeDiscount - $loanRepayment);

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

        // Deduct loan repayment from amount_paid automatically
        // amount_paid entered by user is the gross amount, we deduct loan from it
        $enteredAmountPaid = (float) ($validated['amount_paid'] ?? 0);
        $finalAmountPaid = max(0, $enteredAmountPaid - $loanRepayment);

        // Update salary
        $salary->update([
            'amount_paid' => $finalAmountPaid,
            'loan_repayment' => $loanRepayment,
            'salary_generated' => $newSalaryGenerated,
            'discount' => $feeDiscount,
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
     * Only includes loans with status 'Approved' (excludes 'Completed', 'Rejected', 'Pending')
     */
    private function calculateLoanRepayment($staffId): float
    {
        // Get all approved loans for this staff member (exclude Completed, Rejected, Pending)
        $approvedLoans = Loan::where('staff_id', $staffId)
            ->where('status', 'Approved')
            ->get();
        
        $totalLoanRepayment = 0;
        
        foreach ($approvedLoans as $loan) {
            // Only calculate if loan has approved amount and repayment instalments
            if ($loan->approved_amount && $loan->approved_amount > 0 && $loan->repayment_instalments > 0) {
                // Calculate monthly installment: approved_amount / repayment_instalments
                $monthlyInstallment = (float) $loan->approved_amount / (int) $loan->repayment_instalments;
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
        $earlyExitTime = $settings->early_exit_time ?? null;

        $present = 0;
        $absent = 0;
        $leave = 0;
        $late = 0;
        $earlyExit = 0;
        $totalLectures = 0;
        $totalMinutes = 0;

        // Get staff member to check salary type
        $staff = Staff::find($staffId);
        $isPerLecture = $staff && strtolower(trim($staff->salary_type ?? '')) === 'lecture';
        $isPerHour = $staff && strtolower(trim($staff->salary_type ?? '')) === 'per hour';

        // For per hour salary type, don't count leave
        if ($isPerHour) {
            $leave = 0; // Reset leave count for per hour type
        }

        // For per lecture salary type, use actual conducted_lectures from attendance records
        // If conducted_lectures is not entered, use present days (1 day = 1 lecture)
        if ($isPerLecture) {
            $lecturesFromAttendance = 0;
            // Use conducted_lectures from attendance records
            foreach ($records as $record) {
                if ($record->status === 'Present') {
                    $lecturesFromAttendance += (int) ($record->conducted_lectures ?? 0);
                }
            }
            
            // If no conducted_lectures entered, use present days as lectures
            // This ensures: 1 present day = 1 lecture = 1 × basic_salary
            if ($lecturesFromAttendance > 0) {
                $totalLectures = $lecturesFromAttendance;
            } else {
                // Count present days
                $presentDaysForLectures = 0;
                foreach ($records as $record) {
                    if ($record->status === 'Present') {
                        $presentDaysForLectures++;
                    }
                }
                $totalLectures = $presentDaysForLectures;
            }
        }

        foreach ($records as $record) {
            if ($record->status === 'Present') {
                $present++;
            } elseif ($record->status === 'Absent') {
                $absent++;
            } elseif ($record->status === 'Leave' && !$isPerHour) {
                // Don't count leave for per hour salary type
                $leave++;
            }

            // For per hour staff, don't count late/early exit (as per requirements)
            // For other staff, check late arrival and early exit
            if (!$isPerHour) {
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

                // Check for early exit
                $earlyExitFlag = false;
                if (!empty($record->end_time) && $earlyExitTime) {
                    try {
                        $date = $record->attendance_date ? $record->attendance_date->format('Y-m-d') : Carbon::now()->format('Y-m-d');
                        $endTime = Carbon::parse($date . ' ' . $record->end_time);
                        $standardExitTime = Carbon::parse($date . ' ' . $earlyExitTime);
                        if ($endTime->lessThan($standardExitTime)) {
                            $earlyExitFlag = true;
                        }
                    } catch (\Exception $e) {
                        // Skip invalid times
                    }
                }

                // Also check remarks for early exit
                if (!$earlyExitFlag && !empty($record->remarks) && stripos($record->remarks, 'Early Exit') !== false) {
                    $earlyExitFlag = true;
                }

                if ($earlyExitFlag) {
                    $earlyExit++;
                }
            }

            // For non-per-lecture salary types, use conducted_lectures from attendance
            if (!$isPerLecture) {
                $totalLectures += (int) ($record->conducted_lectures ?? 0);
            }

            // Calculate total minutes for per hour salary calculation
            // For per hour staff, ALWAYS use timetable time, not attendance time
            if ($isPerHour && $record->status === 'Present') {
                $staffName = trim($staff->name ?? '');
                if (!empty($staffName)) {
                    // Get staff's assigned subjects from Subject table
                    $assignedSubjects = Subject::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower($staffName)])
                        ->whereNotNull('subject_name')
                        ->whereNotNull('class')
                        ->whereNotNull('section')
                        ->get();

                    if ($assignedSubjects->isNotEmpty()) {
                        $dayName = Carbon::parse($record->attendance_date)->format('l'); // Monday, Tuesday, etc.
                        
                        // Calculate expected hours from timetable for this day
                        $dayMinutes = 0;
                        foreach ($assignedSubjects as $subject) {
                            $subjectName = trim($subject->subject_name ?? '');
                            $subjectClass = trim($subject->class ?? '');
                            $subjectSection = trim($subject->section ?? '');
                            $subjectCampus = trim($subject->campus ?? '');
                            
                            if (empty($subjectName) || empty($subjectClass) || empty($subjectSection)) {
                                continue;
                            }
                            
                            // Use flexible matching for subject and class names
                            $subjectNameLower = strtolower($subjectName);
                            $timetableQuery = Timetable::where(function($query) use ($subjectNameLower) {
                                $query->whereRaw('LOWER(TRIM(subject)) = ?', [$subjectNameLower]);
                                // Flexible matching for common subject name variations
                                $subjectNameMap = [
                                    'maths' => ['maths', 'mathematics', 'math'],
                                    'mathematics' => ['maths', 'mathematics', 'math'],
                                    'english' => ['english', 'eng'],
                                    'urdu' => ['urdu'],
                                    'science' => ['science', 'sci'],
                                    'islamiat' => ['islamiat', 'islamic studies', 'islamic'],
                                    'social studies' => ['social studies', 'social', 'sst'],
                                ];
                                if (isset($subjectNameMap[$subjectNameLower])) {
                                    foreach ($subjectNameMap[$subjectNameLower] as $variant) {
                                        $query->orWhereRaw('LOWER(TRIM(subject)) = ?', [$variant]);
                                    }
                                }
                            })
                            ->whereRaw('LOWER(TRIM(day)) = ?', [strtolower($dayName)])
                            ->where(function($query) use ($subjectClass) {
                                // Match class name (handle variations like "Four" = "4" = "four")
                                $query->whereRaw('LOWER(TRIM(class)) = ?', [strtolower($subjectClass)]);
                                // Also try numeric matching if class is numeric
                                if (is_numeric($subjectClass)) {
                                    $wordMap = [1 => 'one', 2 => 'two', 3 => 'three', 4 => 'four', 5 => 'five',
                                                6 => 'six', 7 => 'seven', 8 => 'eight', 9 => 'nine', 10 => 'ten'];
                                    if (isset($wordMap[(int)$subjectClass])) {
                                        $query->orWhereRaw('LOWER(TRIM(class)) = ?', [strtolower($wordMap[(int)$subjectClass])]);
                                    }
                                } else {
                                    $wordToNumber = ['one' => 1, 'two' => 2, 'three' => 3, 'four' => 4, 'five' => 5,
                                                     'six' => 6, 'seven' => 7, 'eight' => 8, 'nine' => 9, 'ten' => 10];
                                    $classLower = strtolower(trim($subjectClass));
                                    if (isset($wordToNumber[$classLower])) {
                                        $query->orWhereRaw('LOWER(TRIM(class)) = ?', [strtolower((string)$wordToNumber[$classLower])]);
                                    }
                                }
                            })
                            ->whereRaw('LOWER(TRIM(section)) = ?', [strtolower($subjectSection)]);
                            
                            if (!empty($subjectCampus)) {
                                $timetableQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower($subjectCampus)]);
                            }
                            
                            $timetableEntries = $timetableQuery->get();
                            
                            // Calculate minutes from timetable entries
                            foreach ($timetableEntries as $entry) {
                                if (!empty($entry->starting_time) && !empty($entry->ending_time)) {
                                    try {
                                        $startTime = Carbon::parse($entry->starting_time);
                                        $endTime = Carbon::parse($entry->ending_time);
                                        if ($endTime->greaterThan($startTime)) {
                                            $dayMinutes += $startTime->diffInMinutes($endTime);
                                        }
                                    } catch (\Exception $e) {
                                        // Skip invalid times
                                    }
                                }
                            }
                        }
                        
                        // Add day minutes to total (from timetable, not attendance)
                        $totalMinutes += $dayMinutes;
                    }
                }
            } elseif (!$isPerHour && !empty($record->start_time) && !empty($record->end_time)) {
                // For non-per-hour staff, use attendance time
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
            'early_exit' => $earlyExit,
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
            $presentDays = $attendanceSummary['present'];
            
            // For per hour staff, basic salary field represents daily rate
            // Calculate: present_days * daily_rate
            // This ensures 1 day attendance = 1 * basic_salary
            if ($presentDays > 0) {
                return round($presentDays * $rate, 2);
            }
            
            return 0;
        }

        if ($salaryType === 'lecture') {
            return round($attendanceSummary['total_lectures'] * $rate, 2);
        }

        $settings = SalarySetting::getSettings();
        
        // Use staff's individual free_absent if set, otherwise use SalarySetting's free_absents
        $staffFreeAbsents = (int) ($staff->free_absent ?? null);
        $freeAbsents = $staffFreeAbsents !== null && $staffFreeAbsents >= 0 ? $staffFreeAbsents : (int) ($settings->free_absents ?? 0);
        
        $leaveDeduction = strtolower(trim($settings->leave_deduction ?? 'no')) === 'yes';
        $absentCount = (int) ($attendanceSummary['absent'] ?? 0);
        $leaveCount = (int) ($attendanceSummary['leave'] ?? 0);
        $lateCount = (int) ($attendanceSummary['late'] ?? 0);
        $earlyExitCount = (int) ($attendanceSummary['early_exit'] ?? 0);

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
        
        // Late arrival deduction: Use staff's individual late_fees if set, otherwise default 500
        $staffLateFees = (float) ($staff->late_fees ?? null);
        $lateFeePerLate = $staffLateFees !== null && $staffLateFees >= 0 ? $staffLateFees : 500;
        $lateDeduction = $lateFeePerLate * $lateCount;
        
        // Early exit deduction: Use staff's individual early_exit_fees if set, otherwise default 1000
        $staffEarlyExitFees = (float) ($staff->early_exit_fees ?? null);
        $earlyExitFeePerExit = $staffEarlyExitFees !== null && $staffEarlyExitFees >= 0 ? $staffEarlyExitFees : 1000;
        $earlyExitDeduction = $earlyExitFeePerExit * $earlyExitCount;
        
        // Absent deduction: Use staff's individual absent_fees if set, otherwise use daily rate
        $staffAbsentFees = (float) ($staff->absent_fees ?? null);
        if ($staffAbsentFees !== null && $staffAbsentFees >= 0) {
            // Use fixed absent fees per absent day
            $absentDeduction = $staffAbsentFees * $deductibleAbsents;
        } else {
            // Use daily rate calculation
            $absentDeduction = $dailyRate * $deductibleAbsents;
        }

        return round(max(0, $rate - $absentDeduction - $lateDeduction - $earlyExitDeduction), 2);
    }

    /**
     * Calculate fee discount from student payments for staff's children
     */
    private function calculateFeeDiscount(Staff $staff, int $year, int $month): float
    {
        $discount = 0.0;
        
        // Find students linked to this staff member
        // Match by: email, phone, or name with parent account
        $students = collect();
        
        // Find by staff email matching student's father_email or parent account email
        if ($staff->email) {
            $studentsByEmail = Student::where('father_email', $staff->email)->get();
            $students = $students->merge($studentsByEmail);
            
            // Also check parent accounts
            $parentAccounts = ParentAccount::where('email', $staff->email)->get();
            foreach ($parentAccounts as $parentAccount) {
                $studentsByParent = Student::where('parent_account_id', $parentAccount->id)->get();
                $students = $students->merge($studentsByParent);
            }
        }
        
        // Find by staff phone matching student's father_phone or parent account phone
        if ($staff->phone) {
            $studentsByPhone = Student::where('father_phone', $staff->phone)->get();
            $students = $students->merge($studentsByPhone);
            
            // Also check parent accounts
            $parentAccounts = ParentAccount::where('phone', $staff->phone)->get();
            foreach ($parentAccounts as $parentAccount) {
                $studentsByParent = Student::where('parent_account_id', $parentAccount->id)->get();
                $students = $students->merge($studentsByParent);
            }
        }
        
        // Find by staff name matching student's father_name or parent account name
        if ($staff->name) {
            $studentsByName = Student::where('father_name', $staff->name)->get();
            $students = $students->merge($studentsByName);
            
            // Also check parent accounts
            $parentAccounts = ParentAccount::where('name', $staff->name)->get();
            foreach ($parentAccounts as $parentAccount) {
                $studentsByParent = Student::where('parent_account_id', $parentAccount->id)->get();
                $students = $students->merge($studentsByParent);
            }
        }
        
        // Remove duplicates by student ID
        $students = $students->unique('id');
        
        if ($students->isEmpty()) {
            return 0.0;
        }
        
        // Get student codes
        $studentCodes = $students->pluck('student_code')->filter()->toArray();
        
        if (empty($studentCodes)) {
            return 0.0;
        }
        
        // Calculate total discount from student payments for the salary month/year
        $totalDiscount = StudentPayment::whereIn('student_code', $studentCodes)
            ->whereYear('payment_date', $year)
            ->whereMonth('payment_date', $month)
            ->sum('discount');
        
        return (float) $totalDiscount;
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

