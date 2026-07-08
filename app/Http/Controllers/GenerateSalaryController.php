<?php

namespace App\Http\Controllers;

use App\Models\Campus;
use App\Models\ClassModel;
use App\Models\Section;
use App\Models\Staff;
use App\Models\Salary;
use App\Models\StaffAttendance;
use App\Models\SalarySetting;
use App\Models\Timetable;
use App\Models\Subject;
use App\Models\Student;
use App\Models\ParentAccount;
use App\Models\StudentPayment;
use App\Services\MobilePushNotificationService;
use App\Services\StaffLoanRepaymentService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GenerateSalaryController extends Controller
{
    private const MONTH_NAMES = [
        'January' => '01', 'February' => '02', 'March' => '03', 'April' => '04',
        'May' => '05', 'June' => '06', 'July' => '07', 'August' => '08',
        'September' => '09', 'October' => '10', 'November' => '11', 'December' => '12',
    ];

    private const MONTH_NUMBERS = [
        '01' => 'January', '02' => 'February', '03' => 'March', '04' => 'April',
        '05' => 'May', '06' => 'June', '07' => 'July', '08' => 'August',
        '09' => 'September', '10' => 'October', '11' => 'November', '12' => 'December',
    ];
    public function __construct(
        private readonly MobilePushNotificationService $pushNotifications,
        private readonly StaffLoanRepaymentService $loanRepaymentService,
    ) {
    }

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
                $this->syncGeneratedSalariesCollection($generatedSalaries);
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
        $monthName = self::MONTH_NUMBERS[$month] ?? $month;

        $existingSalaries = Salary::with('staff')
            ->whereHas('staff', function ($q) use ($campus) {
                $q->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
            })
            ->where('salary_month', $monthName)
            ->where('year', (string) $year)
            ->get();

        $this->syncGeneratedSalariesCollection($existingSalaries, $month, (int) $year);

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

            $loanInstallment = $this->loanRepaymentService->calculate(
                $staff->id,
                $existingSalary?->id
            );

            $staffList[] = [
                'id' => $staff->id,
                'emp_id' => $staff->emp_id ?? 'N/A',
                'name' => $staff->name,
                'designation' => $staff->designation ?? 'N/A',
                'is_generated' => $existingSalary ? true : false,
                'loan_installment' => $loanInstallment,
                'has_loan' => $loanInstallment > 0,
            ];
        }

        return response()->json(['staff' => $staffList]);
    }

    /**
     * Load generated salaries for campus/month/year with fresh loan sync (AJAX).
     */
    public function getGeneratedSalaries(Request $request)
    {
        $campus = $request->get('campus');
        $month = $request->get('month');
        $year = $request->get('year');

        if (!$campus || !$month || !$year) {
            return response()->json(['html' => '', 'count' => 0]);
        }

        $monthName = self::MONTH_NUMBERS[$month] ?? $month;

        $generatedSalaries = Salary::with('staff')
            ->whereHas('staff', function ($q) use ($campus) {
                $q->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
            })
            ->where('salary_month', $monthName)
            ->where('year', (string) $year)
            ->orderBy('created_at', 'desc')
            ->get();

        $this->syncGeneratedSalariesCollection($generatedSalaries, $month, (int) $year);

        $html = view('salary-loan.partials.generated-salaries-table', [
            'generatedSalaries' => $generatedSalaries,
            'generatedCampus' => $campus,
            'generatedMonth' => $month,
            'generatedYear' => $year,
        ])->render();

        return response()->json([
            'html' => $html,
            'count' => $generatedSalaries->count(),
        ]);
    }

    /**
     * Preview net payable and loan repayment for payment modal (AJAX).
     */
    public function netPayablePreview(Salary $salary)
    {
        $salary->load('staff');

        if ($salary->status === 'Pending' && (float) ($salary->amount_paid ?? 0) <= 0) {
            $this->syncGeneratedSalaryRecord($salary);
            $salary->refresh();
        }

        $loanRepayment = $this->loanRepaymentService->calculate($salary->staff_id, $salary->id);
        $grossSalary = $salary->grossSalaryGenerated();
        $bonusAmount = (float) ($salary->bonus_amount ?? 0);
        $deductionAmount = (float) ($salary->deduction_amount ?? 0);

        return response()->json([
            'loan_repayment' => $loanRepayment,
            'salary_generated' => $grossSalary,
            'gross_salary_generated' => $grossSalary,
            'amount_paid' => (float) ($salary->amount_paid ?? 0),
            'bonus_amount' => $bonusAmount,
            'deduction_amount' => $deductionAmount,
            'net_payable' => max(0, $grossSalary - $loanRepayment + $bonusAmount - $deductionAmount),
        ]);
    }

    /**
     * Sync pending salaries for a staff member after loan create/update.
     */
    public function syncPendingSalariesForStaff(int $staffId): void
    {
        $salaries = Salary::with('staff')
            ->where('staff_id', $staffId)
            ->where('status', 'Pending')
            ->where(function ($query) {
                $query->whereNull('amount_paid')
                    ->orWhere('amount_paid', '<=', 0);
            })
            ->get();

        if ($salaries->isEmpty()) {
            return;
        }

        $this->syncGeneratedSalariesCollection($salaries);
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
            $monthName = self::MONTH_NUMBERS[$month] ?? $month;
            
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
                
                $this->syncGeneratedSalariesCollection($generatedSalaries, $month, $year);
                
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
                    $this->syncGeneratedSalaryRecord($existingSalary, $month, $year);
                    $skippedCount++;
                    continue;
                }

                // Resolve calendar month number (1–12) so days-in-month is correct (28/29/30/31).
                $monthNumber = $this->resolveMonthNumber($month);

                // Calculate attendance counts for selected month/year
                $attendanceSummary = $this->calculateAttendanceSummary($staff->id, (int) $year, $monthNumber);
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
                
                // salary_generated = attendance amount only (not reduced by loan).
                $grossSalary = $this->calculateSalaryGenerated($staff, $attendanceSummary, (float) $deductionPerLateArrival, (int) $year, $monthNumber);

                // loan_repayment = full monthly installment (separate column).
                $loanRepayment = $this->loanRepaymentService->calculate((int) $staff->id);

                // Create salary record
                $salary = Salary::create([
                    'staff_id' => $staff->id,
                    'salary_month' => $monthName,
                    'year' => (string) $year,
                    'present' => $presentCount,
                    'absent' => $absentCount,
                    'late' => $lateCount,
                    'early_exit' => $earlyExitCount,
                    'basic' => $basicRate,
                    'salary_generated' => $grossSalary,
                    'amount_paid' => 0,
                    'loan_repayment' => $loanRepayment,
                    'discount' => 0,
                    'status' => 'Pending',
                ]);

                // Re-apply loan after insert (uses salary id for pending allocation order).
                if ($loanRepayment <= 0) {
                    $loanRepayment = $this->loanRepaymentService->calculate((int) $staff->id, (int) $salary->id);
                    if ($loanRepayment > 0) {
                        $salary->update(['loan_repayment' => $loanRepayment]);
                    }
                }

                try {
                    $this->pushNotifications->notifyStaffSalaryGenerated($staff, $salary);
                } catch (\Throwable $e) {
                    // Push/device-token issues must not block salary generation.
                    \Illuminate\Support\Facades\Log::warning('Salary push notification failed', [
                        'staff_id' => $staff->id,
                        'salary_id' => $salary->id,
                        'error' => $e->getMessage(),
                    ]);
                }

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
            
            $this->syncGeneratedSalariesCollection($generatedSalaries, $month, $year);

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
                'payment_method' => ['required', 'string', 'in:Bank,Wallet,Transfer,Card,Check,Cheque,Deposit,Cash'],
            'fully_paid' => ['nullable', 'string', 'in:0,1'],
            'payment_date' => ['required', 'date'],
            'notify_employee' => ['nullable', 'string', 'in:0,1'],
        ]);

        $validated['payment_date'] = Carbon::parse(
            $validated['payment_date'],
            config('app.timezone')
        )->toDateString();

        // salary_generated stays as attendance gross; loan is only in loan_repayment.
        $grossSalary = max(0, (float) ($salary->salary_generated ?? 0));
        $loanRepayment = (float) ($salary->loan_repayment ?? 0);
        if ($loanRepayment <= 0) {
            $loanRepayment = $this->loanRepaymentService->calculate((int) $salary->staff_id, (int) $salary->id);
        }
        if ($loanRepayment <= 0 && isset($validated['loan_repayment'])) {
            $loanRepayment = (float) $validated['loan_repayment'];
        }

        $bonusAmount = (float) ($validated['bonus_amount'] ?? 0);
        $deductionAmount = (float) ($validated['deduction_amount'] ?? 0);
        $netPayable = max(0, $grossSalary - $loanRepayment + $bonusAmount - $deductionAmount);

        $amountPaid = (float) ($validated['amount_paid'] ?? 0);
        $fullyPaid = isset($validated['fully_paid']) && ($validated['fully_paid'] == '1' || $validated['fully_paid'] === true);

        $status = 'Pending';
        if ($fullyPaid || $amountPaid >= $netPayable) {
            $status = 'Paid';
        } elseif ($amountPaid > 0) {
            $status = 'Issued';
        }
        if ($amountPaid > 0 && $status == 'Pending') {
            $status = 'Issued';
        }

        $updates = [
            'amount_paid' => $amountPaid,
            'loan_repayment' => $loanRepayment,
            'salary_generated' => $grossSalary,
            'discount' => 0,
            'bonus_amount' => $bonusAmount,
            'deduction_amount' => $deductionAmount,
            'payment_method' => Salary::normalizePaymentMethod($validated['payment_method']),
            'payment_date' => $validated['payment_date'],
            'status' => $status,
        ];

        if ($amountPaid > 0 || $status === 'Paid') {
            $updates = array_merge($updates, Salary::metadataForPaidAction($salary));
            $updates['payment_method'] = Salary::normalizePaymentMethod($validated['payment_method']);
            $updates['payment_date'] = $validated['payment_date'] ?? ($updates['payment_date'] ?? null);
        }

        $previousAmountPaid = (float) ($salary->amount_paid ?? 0);
        $salary->update($updates);
        $this->loanRepaymentService->applyRepaymentFromSalary($salary, $previousAmountPaid);
        $this->syncPendingSalariesForStaff((int) $salary->staff_id);

        if (($validated['notify_employee'] ?? '0') === '1' && $salary->staff) {
            $this->pushNotifications->notifyStaffSalaryPaid($salary->staff, $salary);
        }

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

        // If status is Paid, redirect back to generate-salary page with updated data and print receipt flag
        if ($status === 'Paid') {
            return redirect()
                ->route('salary-loan.generate-salary')
                ->with('success', 'Payment updated successfully. Status changed to Paid.')
                ->with('generated_salaries', $generatedSalaries)
                ->with('generated_campus', $salary->staff->campus ?? '')
                ->with('generated_month', $this->getMonthNumber($salary->salary_month))
                ->with('generated_year', $salary->year)
                ->with('print_receipt_id', $salary->id);
        }

        return redirect()
            ->route('salary-loan.generate-salary')
            ->with('success', 'Payment updated successfully. Status changed to ' . $status . '.')
            ->with('generated_salaries', $generatedSalaries)
            ->with('generated_campus', $salary->staff->campus ?? '')
            ->with('generated_month', $this->getMonthNumber($salary->salary_month))
            ->with('generated_year', $salary->year);
    }
    
    private function calculateAttendanceSummary(int $staffId, int $year, int|string $month): array
    {
        $monthNumber = $this->resolveMonthNumber($month);
        if ($monthNumber < 1) {
            $monthNumber = (int) date('n');
        }

        $records = StaffAttendance::where('staff_id', $staffId)
            ->whereYear('attendance_date', $year)
            ->whereMonth('attendance_date', $monthNumber)
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
        $isFullTime = $staff && (empty($staff->salary_type) || strtolower(trim($staff->salary_type)) === 'full time');

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

        $holidayCount = 0;
        $sundayCount = 0;
        
        foreach ($records as $record) {
            if ($record->status === 'Present') {
                $present++;
            } elseif ($record->status === 'Absent') {
                $absent++;
            } elseif ($record->status === 'Leave' && !$isPerHour) {
                // Don't count leave for per hour salary type
                $leave++;
            } elseif ($record->status === 'Holiday') {
                $holidayCount++;
            } elseif ($record->status === 'Sunday') {
                $sundayCount++;
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
            // For per hour staff, use ACTUAL hours taught from attendance (start_time and end_time)
            if ($isPerHour && $record->status === 'Present' && !empty($record->start_time) && !empty($record->end_time)) {
                // Use actual attendance times (actual hours taught)
                try {
                    $date = $record->attendance_date ? $record->attendance_date->format('Y-m-d') : Carbon::now()->format('Y-m-d');
                    $startTime = Carbon::parse($date . ' ' . $record->start_time);
                    $endTime = Carbon::parse($date . ' ' . $record->end_time);
                    if ($endTime->greaterThan($startTime)) {
                        $dayMinutes = $startTime->diffInMinutes($endTime);
                        $totalMinutes += $dayMinutes;
                    }
                } catch (\Exception $e) {
                    // If attendance times not available, fallback to timetable time
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
                            
                            // Add day minutes to total (from timetable as fallback)
                            $totalMinutes += $dayMinutes;
                        }
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
        
        // For full-time staff, count Sunday and Holiday as present
        if ($isFullTime) {
            $present += $holidayCount + $sundayCount;
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
            $totalMinutes = $attendanceSummary['total_minutes'] ?? 0;
            $totalHours = $totalMinutes / 60; // Convert minutes to hours
            
            // For per hour staff, calculate: Basic Salary × Hours worked
            // IMPORTANT: Use actual hours worked, NOT present days
            // Example: 5000 × 4.50 = 22,500
            if ($totalHours > 0) {
                $calculatedSalary = $rate * $totalHours;
                return round($calculatedSalary, 2);
            }
            
            return 0;
        }

        if ($salaryType === 'lecture') {
            return round($attendanceSummary['total_lectures'] * $rate, 2);
        }

        $settings = SalarySetting::getSettings();

        // Staff free_absent when set; otherwise Salary Setting. Do not cast null→0 before check.
        $freeAbsents = $staff->free_absent !== null && $staff->free_absent !== ''
            ? max(0, (int) $staff->free_absent)
            : max(0, (int) ($settings->free_absents ?? 0));

        $leaveDeduction = strtolower(trim($settings->leave_deduction ?? 'no')) === 'yes';
        $presentCount = (int) ($attendanceSummary['present'] ?? 0);
        $absentCount = (int) ($attendanceSummary['absent'] ?? 0);
        $leaveCount = (int) ($attendanceSummary['leave'] ?? 0);
        $lateCount = (int) ($attendanceSummary['late'] ?? 0);
        $earlyExitCount = (int) ($attendanceSummary['early_exit'] ?? 0);

        $totalAbsents = $absentCount + ($leaveDeduction ? $leaveCount : 0);
        $deductibleAbsents = max(0, $totalAbsents - $freeAbsents);
        $freeAbsentsUsed = min($freeAbsents, $absentCount);

        // Real calendar days: Feb 28/29, Apr/Jun/Sep/Nov 30, others 31.
        $daysInMonth = $this->daysInSalaryMonth($year, $month);
        $dailyRate = $daysInMonth > 0 ? ($rate / $daysInMonth) : 0;

        // Pay present + free absents used + leave. Extra absents are unpaid (not in base).
        // July (31): present=5, free_absent=2, late=1, early=1, fees=500
        //   daily=5000/31, paidDays=7, salary=7*daily-500-500=129.03
        $baseSalary = $dailyRate * ($presentCount + $freeAbsentsUsed + $leaveCount);

        $lateFeePerLate = $staff->late_fees !== null && $staff->late_fees !== ''
            ? max(0, (float) $staff->late_fees)
            : 500.0;
        $lateDeduction = $lateFeePerLate * $lateCount;

        $earlyExitFeePerExit = $staff->early_exit_fees !== null && $staff->early_exit_fees !== ''
            ? max(0, (float) $staff->early_exit_fees)
            : 500.0;
        $earlyExitDeduction = $earlyExitFeePerExit * $earlyExitCount;

        // Fixed absent fee only when staff has explicit absent_fees > 0.
        $absentDeduction = 0.0;
        if ($staff->absent_fees !== null && $staff->absent_fees !== '' && (float) $staff->absent_fees > 0) {
            $absentDeduction = (float) $staff->absent_fees * $deductibleAbsents;
        }

        return round(max(0, $baseSalary - $absentDeduction - $lateDeduction - $earlyExitDeduction), 2);
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
     * Sync loan + attendance for a collection of generated salaries (chronological per staff).
     */
    public function syncGeneratedSalariesCollection($salaries, ?string $monthNumeric = null, ?int $year = null): void
    {
        $pending = $salaries
            ->filter(fn (Salary $salary) => $salary->staff && $salary->status === 'Pending' && (float) ($salary->amount_paid ?? 0) <= 0)
            ->groupBy('staff_id');

        foreach ($pending as $staffSalaries) {
            $ordered = $staffSalaries->sortBy(function (Salary $salary) {
                $month = self::MONTH_NAMES[$salary->salary_month] ?? '99';

                return sprintf('%04d-%s', (int) $salary->year, $month);
            });

            foreach ($ordered as $salary) {
                $this->syncGeneratedSalaryRecord($salary, $monthNumeric, $year);
            }
        }

        foreach ($salaries as $salary) {
            if ($salary->status !== 'Pending' || (float) ($salary->amount_paid ?? 0) > 0) {
                $this->syncGeneratedSalaryRecord($salary, $monthNumeric, $year, attendanceOnly: true);
            }
        }
    }

    /**
     * Apply approved loan installment and refresh generated salary for one record.
     */
    public function syncGeneratedSalaryRecord(
        Salary $salary,
        ?string $monthNumeric = null,
        ?int $year = null,
        bool $attendanceOnly = false,
    ): void {
        if (!$salary->staff) {
            return;
        }

        $monthNumber = $this->resolveMonthNumber($monthNumeric ?? $salary->salary_month);
        $yearValue = $year ?? (int) $salary->year;
        $deductionPerLateArrival = 0.0;

        $attendanceSummary = $this->calculateAttendanceSummary($salary->staff_id, $yearValue, $monthNumber);
        $presentCount = $attendanceSummary['present'];
        $basicRate = (float) ($salary->staff->salary ?? 0);
        $salaryType = strtolower(trim($salary->staff->salary_type ?? ''));

        if ($salaryType === 'lecture') {
            $absentCount = 0;
            $lateCount = 0;
            $earlyExitCount = 0;
        } else {
            $absentCount = $attendanceSummary['absent'];
            $lateCount = $attendanceSummary['late'];
            $earlyExitCount = $attendanceSummary['early_exit'] ?? 0;
        }

        $updates = [];

        if ($salary->present != $presentCount || $salary->absent != $absentCount || $salary->late != $lateCount || $salary->early_exit != $earlyExitCount) {
            $updates['present'] = $presentCount;
            $updates['absent'] = $absentCount;
            $updates['late'] = $lateCount;
            $updates['early_exit'] = $earlyExitCount;
        }

        if ($salary->basic != $basicRate) {
            $updates['basic'] = $basicRate;
        }

        if ($salary->discount != 0) {
            $updates['discount'] = 0;
        }

        if (!$attendanceOnly && $salary->status === 'Pending' && (float) ($salary->amount_paid ?? 0) <= 0) {
            // Keep salary_generated as attendance gross; loan stays in loan_repayment only.
            $grossSalary = $this->calculateSalaryGenerated(
                $salary->staff,
                $attendanceSummary,
                $deductionPerLateArrival,
                $yearValue,
                $monthNumber
            );
            $loanRepayment = $this->loanRepaymentService->calculate((int) $salary->staff_id, (int) $salary->id);

            if (abs((float) ($salary->loan_repayment ?? 0) - $loanRepayment) > 0.01) {
                $updates['loan_repayment'] = $loanRepayment;
            }

            if (abs((float) ($salary->salary_generated ?? 0) - $grossSalary) > 0.01) {
                $updates['salary_generated'] = $grossSalary;
            }
        }

        if (!empty($updates)) {
            $salary->update($updates);
            $salary->refresh();
        }
    }

    /**
     * Get month number from month name (zero-padded string, e.g. "07").
     */
    private function getMonthNumber($monthName)
    {
        $resolved = $this->resolveMonthNumber($monthName);

        return $resolved > 0 ? sprintf('%02d', $resolved) : date('m');
    }

    /**
     * Resolve month to 1–12 from "07", 7, or "July".
     */
    private function resolveMonthNumber(int|string|null $month): int
    {
        if ($month === null || $month === '') {
            return 0;
        }

        if (is_numeric($month)) {
            $num = (int) $month;

            return ($num >= 1 && $num <= 12) ? $num : 0;
        }

        $name = trim((string) $month);
        foreach (self::MONTH_NAMES as $monthName => $num) {
            if (strcasecmp($monthName, $name) === 0) {
                return (int) $num;
            }
        }

        return 0;
    }

    /**
     * Calendar days in the salary month (28/29/30/31, leap years included).
     */
    private function daysInSalaryMonth(int|string|null $year, int|string|null $month): int
    {
        $yearNum = (int) $year;
        $monthNum = $this->resolveMonthNumber($month);

        if ($yearNum >= 2000 && $yearNum <= 2100 && $monthNum >= 1 && $monthNum <= 12) {
            return (int) Carbon::createFromDate($yearNum, $monthNum, 1)->daysInMonth;
        }

        // Last resort only — prefer real calendar length over a fixed 30.
        return (int) Carbon::now(config('app.timezone'))->daysInMonth;
    }

    /**
     * Print salary slip.
     */
    public function printSlip(Salary $salary): View
    {
        $salary->load('staff');
        
        // Calculate attendance summary for displaying hours/lectures
        $monthNumber = $this->getMonthNumber($salary->salary_month);
        $attendanceSummary = $this->calculateAttendanceSummary($salary->staff_id, (int) $salary->year, $monthNumber);
        
        return view('salary-loan.print-slip', compact('salary', 'attendanceSummary'));
    }

    /**
     * Print thermal receipt for salary payment.
     */
    public function printReceiptThermal(Salary $salary): View
    {
        $salary->load('staff');
        
        // Calculate attendance summary for displaying hours/lectures
        $monthNumber = $this->getMonthNumber($salary->salary_month);
        $attendanceSummary = $this->calculateAttendanceSummary($salary->staff_id, (int) $salary->year, $monthNumber);
        
        return view('salary-loan.print-receipt-thermal', compact('salary', 'attendanceSummary'));
    }

    /**
     * Update salary status.
     */
    public function updateStatus(Request $request, Salary $salary): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:Pending,Paid,Issued'],
        ]);

        $updates = ['status' => $validated['status']];
        if ($validated['status'] === 'Paid') {
            if ((float) ($salary->amount_paid ?? 0) <= 0) {
                $updates['amount_paid'] = $salary->netPayableAmount();
            }

            $finalAmount = (float) ($updates['amount_paid'] ?? $salary->amount_paid ?? 0);
            if ($finalAmount > 0 || (float) ($salary->loan_repayment ?? 0) > 0) {
                $updates = array_merge($updates, Salary::metadataForPaidAction($salary));
            }
        }

        $previousAmountPaid = (float) ($salary->amount_paid ?? 0);
        $salary->update($updates);
        $salary->refresh();
        if ($validated['status'] === 'Paid') {
            $this->loanRepaymentService->applyRepaymentFromSalary($salary, $previousAmountPaid);
            $this->syncPendingSalariesForStaff((int) $salary->staff_id);
        }

        // If status is changed to Paid, redirect to thermal receipt print page
        if ($validated['status'] === 'Paid') {
            return redirect()
                ->route('salary-loan.generate-salary.print-receipt-thermal', $salary->id)
                ->with('success', 'Status updated to Paid. Receipt will be printed.');
        }

        // Get all generated salaries for the same campus, month, and year to show in table
        $salary->load('staff');
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
            ->with('success', 'Salary status updated successfully.')
            ->with('generated_salaries', $generatedSalaries)
            ->with('generated_campus', $salary->staff->campus ?? '')
            ->with('generated_month', $this->getMonthNumber($salary->salary_month))
            ->with('generated_year', $salary->year);
    }

    /**
     * Delete salary record.
     */
    public function destroy(Salary $salary): RedirectResponse
    {
        $staffId = (int) $salary->staff_id;

        $salary->delete();

        $this->loanRepaymentService->syncStaffLoanBalances($staffId);
        $this->syncPendingSalariesForStaff($staffId);

        return redirect()
            ->route('salary-loan.generate-salary')
            ->with('success', 'Salary record deleted successfully.');
    }
}

