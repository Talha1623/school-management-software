<?php

namespace App\Http\Controllers;

use App\Models\Salary;
use App\Models\Campus;
use App\Models\Staff;
use App\Services\StaffLoanRepaymentService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class ManageSalariesController extends Controller
{
    private const MONTH_NAMES = [
        'January' => '01', 'February' => '02', 'March' => '03', 'April' => '04',
        'May' => '05', 'June' => '06', 'July' => '07', 'August' => '08',
        'September' => '09', 'October' => '10', 'November' => '11', 'December' => '12',
    ];

    public function __construct(
        private readonly StaffLoanRepaymentService $loanRepaymentService,
    ) {
    }
    /**
     * Display a listing of salaries.
     */
    public function index(Request $request): View
    {
        $query = Salary::with('staff');

        $this->applyCampusFilter($query, $request->get('campus'));
        $this->applySearchFilter($query, $request->get('search'));
        
        $perPage = $request->get('per_page', 10);
        $perPage = in_array($perPage, [10, 25, 50, 100]) ? $perPage : 10;
        
        $salaries = $query->orderBy('created_at', 'desc')->paginate($perPage)->withQueryString();

        $this->syncLoanRepaymentsForSalaries($salaries->getCollection());
        
        // Calculate attendance summary for per hour teachers to show hours
        $generateController = app(\App\Http\Controllers\GenerateSalaryController::class);
        $reflection = new \ReflectionClass($generateController);
        $calculateAttendanceMethod = $reflection->getMethod('calculateAttendanceSummary');
        $calculateAttendanceMethod->setAccessible(true);
        
        // Add hours data to each salary for per hour teachers
        foreach ($salaries as $salary) {
            if ($salary->staff) {
                $salaryType = strtolower(trim($salary->staff->salary_type ?? ''));
                if ($salaryType === 'per hour') {
                    try {
                        $monthNumber = (int) (self::MONTH_NAMES[$salary->salary_month] ?? date('m'));
                        $attendanceSummary = $calculateAttendanceMethod->invoke($generateController, $salary->staff_id, (int) $salary->year, $monthNumber);
                        $totalMinutes = $attendanceSummary['total_minutes'] ?? 0;
                        $totalHours = round($totalMinutes / 60, 2);
                        $salary->total_hours = $totalHours;
                        $salary->total_classes = $attendanceSummary['present'] ?? 0;
                    } catch (\Exception $e) {
                        $salary->total_hours = 0;
                        $salary->total_classes = 0;
                    }
                } else {
                    $salary->total_hours = null;
                    $salary->total_classes = null;
                }
            }
        }
        
        $campuses = $this->campusOptionsForFilter();
        $selectedCampus = $request->get('campus');

        return view('salary-loan.manage-salaries', compact('salaries', 'campuses', 'selectedCampus'));
    }

    /**
     * Show the specified salary.
     */
    public function show(Request $request, Salary $salary)
    {
        // If request wants JSON (for modal), return JSON
        if ($request->wantsJson() || $request->ajax()) {
            $salary->load('staff');
            $this->syncPendingSalaryLoan($salary);
            $salary->refresh();
            
            // Calculate fees deductions
            $staff = $salary->staff;
            $lateCount = $salary->late ?? 0;
            $earlyExitCount = $salary->early_exit ?? 0;
            
            // Get staff individual fees or defaults
            $lateFeePerLate = $staff?->late_fees ?? 500;
            $earlyExitFeePerExit = $staff?->early_exit_fees ?? 1000;
            
            // Calculate total fees
            $lateFeesTotal = $lateFeePerLate * $lateCount;
            $earlyExitFeesTotal = $earlyExitFeePerExit * $earlyExitCount;
            
            // For absent fees, we need to calculate from attendance summary
            // Since we don't have deductible absents count directly, we'll calculate it
            $absentCount = $salary->absent ?? 0;
            $staffFreeAbsents = $staff?->free_absent ?? 0;
            $deductibleAbsents = max(0, $absentCount - $staffFreeAbsents);
            
            $absentFeePerAbsent = $staff?->absent_fees ?? null;
            if ($absentFeePerAbsent !== null && $absentFeePerAbsent >= 0) {
                $absentFeesTotal = $absentFeePerAbsent * $deductibleAbsents;
            } else {
                // Use daily rate calculation
                $daysInMonth = 30;
                try {
                    $monthNumber = $this->getMonthNumber($salary->salary_month);
                    $daysInMonth = \Carbon\Carbon::createFromDate($salary->year, $monthNumber, 1)->daysInMonth;
                } catch (\Exception $e) {
                    $daysInMonth = 30;
                }
                $dailyRate = $daysInMonth > 0 ? (($salary->basic ?? 0) / $daysInMonth) : 0;
                $absentFeesTotal = $dailyRate * $deductibleAbsents;
            }
            
            // Add fees data to response
            $salaryData = $salary->toArray();
            $salaryData['fees'] = [
                'late_fees' => $lateFeesTotal,
                'absent_fees' => $absentFeesTotal,
                'early_exit_fees' => $earlyExitFeesTotal,
            ];

            $loanRepayment = (float) ($salary->loan_repayment ?? 0);
            $grossGenerated = $salary->grossSalaryGenerated();
            $salaryData['loan_repayment'] = $loanRepayment;
            $salaryData['gross_salary_generated'] = $grossGenerated;
            $salaryData['suggested_amount_paid'] = $salary->netPayableAmount();
            
            return response()->json($salaryData);
        }
        
        // Otherwise return view (if needed in future)
        return view('salary-loan.manage-salaries', compact('salary'));
    }
    
    /**
     * Get month number from month name
     */
    private function getMonthNumber($monthName)
    {
        return self::MONTH_NAMES[$monthName] ?? date('m');
    }

    /**
     * Update salary payment.
     */
    public function updatePayment(Request $request, Salary $salary)
    {
        // Check if amount_paid already exists and is greater than 0
        if ($salary->amount_paid > 0) {
            return redirect()
                ->route('salary-loan.manage-salaries')
                ->with('error', 'Amount Paid cannot be edited once payment has been made.');
        }

        $validated = $request->validate([
            'generated_salary' => ['required', 'numeric', 'min:0'],
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

        $this->syncPendingSalaryLoan($salary);
        $salary->refresh();

        $loanRepayment = $this->loanRepaymentService->calculate($salary->staff_id, $salary->id);
        if ($loanRepayment <= 0 && isset($validated['loan_repayment'])) {
            $loanRepayment = (float) $validated['loan_repayment'];
        }

        $bonusAmount = (float) ($validated['bonus_amount'] ?? 0);
        $deductionAmount = (float) ($validated['deduction_amount'] ?? 0);

        $grossFromForm = max(0, (float) $validated['generated_salary']);
        $netSalary = max(0, $grossFromForm - $loanRepayment + $bonusAmount - $deductionAmount);
        $totalDue = $netSalary;

        $enteredAmountPaid = (float) ($validated['amount_paid'] ?? 0);
        $fullyPaid = isset($validated['fully_paid']) && ($validated['fully_paid'] == '1' || $validated['fully_paid'] === true);
        $finalAmountPaid = $fullyPaid ? $totalDue : $enteredAmountPaid;
        $status = 'Pending';
        if ($fullyPaid || $finalAmountPaid >= $totalDue) {
            $status = 'Paid';
        } elseif ($finalAmountPaid > 0) {
            $status = 'Issued';
        }

        Salary::ensurePaymentColumns();
        Salary::ensurePaidByColumns();

        $updates = [
            'amount_paid' => $finalAmountPaid,
            'loan_repayment' => $loanRepayment,
            'salary_generated' => $netSalary,
            'discount' => 0,
            'bonus_amount' => $bonusAmount,
            'deduction_amount' => $deductionAmount,
            'payment_method' => Salary::normalizePaymentMethod($validated['payment_method'] ?? null),
            'payment_date' => $validated['payment_date'] ?? null,
            'status' => $status,
        ];

        if ($finalAmountPaid > 0 || $status === 'Paid') {
            $updates = array_merge($updates, Salary::metadataForPaidAction($salary));
            $updates['payment_method'] = Salary::normalizePaymentMethod($validated['payment_method'] ?? null);
            $updates['payment_date'] = $validated['payment_date'] ?? ($updates['payment_date'] ?? null);
        }

        $previousAmountPaid = (float) ($salary->amount_paid ?? 0);
        $salary->update($updates);
        $salary->refresh();
        $this->loanRepaymentService->applyRepaymentFromSalary($salary, $previousAmountPaid);
        app(GenerateSalaryController::class)->syncPendingSalariesForStaff((int) $salary->staff_id);

        // TODO: Store bonus and deduction details if needed (may require additional table)
        // TODO: Send notification to employee if notify_employee is true

        // If status is Paid and payment was actually made, redirect back to manage-salaries with print flag
        if ($status === 'Paid' && $finalAmountPaid > 0) {
            return redirect()
                ->route('salary-loan.manage-salaries')
                ->with('success', 'Payment updated successfully. Status changed to Paid.')
                ->with('print_receipt_id', $salary->id);
        }

        return redirect()
            ->route('salary-loan.manage-salaries')
            ->with('success', 'Payment updated successfully!');
    }

    /**
     * Update salary status.
     */
    public function updateStatus(Request $request, Salary $salary)
    {
        $validated = $request->validate([
            'status' => ['required', 'in:Pending,Paid,Issued'],
        ]);

        $updates = ['status' => $validated['status']];
        if ($validated['status'] === 'Paid') {
            if ((float) ($salary->amount_paid ?? 0) <= 0) {
                $updates['amount_paid'] = $this->netPayableAmount($salary);
            }

            $finalAmount = (float) ($updates['amount_paid'] ?? $salary->amount_paid ?? 0);
            if ($finalAmount > 0) {
                $updates = array_merge($updates, Salary::metadataForPaidAction($salary));
            }
        }
        $previousAmountPaid = (float) ($salary->amount_paid ?? 0);
        $salary->update($updates);
        $salary->refresh();
        $this->loanRepaymentService->applyRepaymentFromSalary($salary, $previousAmountPaid);
        app(GenerateSalaryController::class)->syncPendingSalariesForStaff((int) $salary->staff_id);

        // Don't auto-print when status is changed manually - only print when actual payment is made
        return redirect()
            ->route('salary-loan.manage-salaries')
            ->with('success', 'Salary status updated successfully!');
    }

    /**
     * Update salary details (Present, Absent, Late, Generated Salary).
     */
    public function update(Request $request, Salary $salary)
    {
        $validated = $request->validate([
            'present' => ['required', 'integer', 'min:0'],
            'absent' => ['required', 'integer', 'min:0'],
            'late' => ['required', 'integer', 'min:0'],
            'salary_generated' => ['required', 'numeric', 'min:0'],
        ]);

        // Update the salary record
        $salary->update([
            'present' => $validated['present'],
            'absent' => $validated['absent'],
            'late' => $validated['late'],
            'salary_generated' => $validated['salary_generated'],
        ]);

        return redirect()
            ->route('salary-loan.manage-salaries')
            ->with('success', 'Salary updated successfully!');
    }

    /**
     * Remove the specified salary.
     */
    public function destroy(Salary $salary)
    {
        $salary->delete();

        return redirect()
            ->route('salary-loan.manage-salaries')
            ->with('success', 'Salary record deleted successfully!');
    }

    /**
     * Export salaries to Excel, CSV, or PDF
     */
    public function export(Request $request, string $format)
    {
        $query = Salary::with('staff');

        $this->applyCampusFilter($query, $request->get('campus'));
        $this->applySearchFilter($query, $request->get('search'));
        
        $salaries = $query->orderBy('created_at', 'desc')->get();
        $this->syncLoanRepaymentsForSalaries($salaries);
        
        switch ($format) {
            case 'excel':
                return $this->exportExcel($salaries);
            case 'csv':
                return $this->exportCSV($salaries);
            case 'pdf':
                return $this->exportPDF($salaries);
            default:
                return redirect()->route('salary-loan.manage-salaries')
                    ->with('error', 'Invalid export format!');
        }
    }

    /**
     * Export to Excel (CSV format for Excel compatibility)
     */
    private function exportExcel($salaries)
    {
        $filename = 'salaries_' . date('Y-m-d_His') . '.csv';
        
        $headers = [
            'Content-Type' => 'application/vnd.ms-excel',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($salaries) {
            $file = fopen('php://output', 'w');
            
            // Add BOM for UTF-8
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            fputcsv($file, ['ID', 'Name', 'Salary Month', 'Present', 'Absent', 'Late', 'Basic', 'Salary Generated', 'Amount Paid', 'Loan Repayment', 'Status', 'Created At']);
            
            foreach ($salaries as $salary) {
                fputcsv($file, [
                    $salary->id,
                    $salary->staff->name ?? 'N/A',
                    $salary->salary_month . ' ' . $salary->year,
                    $salary->present,
                    $salary->absent,
                    $salary->late,
                    $salary->basic,
                    $salary->grossSalaryGenerated(),
                    $salary->amount_paid,
                    $salary->loan_repayment,
                    $salary->status,
                    $salary->created_at->format('Y-m-d H:i:s'),
                ]);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export to CSV
     */
    private function exportCSV($salaries)
    {
        $filename = 'salaries_' . date('Y-m-d_His') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($salaries) {
            $file = fopen('php://output', 'w');
            
            fputcsv($file, ['ID', 'Name', 'Salary Month', 'Present', 'Absent', 'Late', 'Basic', 'Salary Generated', 'Amount Paid', 'Loan Repayment', 'Status', 'Created At']);
            
            foreach ($salaries as $salary) {
                fputcsv($file, [
                    $salary->id,
                    $salary->staff->name ?? 'N/A',
                    $salary->salary_month . ' ' . $salary->year,
                    $salary->present,
                    $salary->absent,
                    $salary->late,
                    $salary->basic,
                    $salary->grossSalaryGenerated(),
                    $salary->amount_paid,
                    $salary->loan_repayment,
                    $salary->status,
                    $salary->created_at->format('Y-m-d H:i:s'),
                ]);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export to PDF
     */
    private function exportPDF($salaries)
    {
        $html = view('salary-loan.manage-salaries-pdf', compact('salaries'))->render();
        
        return response($html)
            ->header('Content-Type', 'text/html');
    }

    /**
     * Print payment receipt
     */
    public function printReceipt(Salary $salary)
    {
        $salary->load('staff');
        
        // Calculate attendance summary for displaying hours/lectures
        $generateController = app(\App\Http\Controllers\GenerateSalaryController::class);
        $reflection = new \ReflectionClass($generateController);
        
        // Get month number
        $monthNames = [
            'January' => '01', 'February' => '02', 'March' => '03', 'April' => '04',
            'May' => '05', 'June' => '06', 'July' => '07', 'August' => '08',
            'September' => '09', 'October' => '10', 'November' => '11', 'December' => '12'
        ];
        $monthNumber = (int) ($monthNames[$salary->salary_month] ?? date('m'));
        
        // Calculate attendance summary
        $calculateAttendanceMethod = $reflection->getMethod('calculateAttendanceSummary');
        $calculateAttendanceMethod->setAccessible(true);
        $attendanceSummary = $calculateAttendanceMethod->invoke($generateController, $salary->staff_id, (int) $salary->year, $monthNumber);
        
        return view('salary-loan.print-receipt', compact('salary', 'attendanceSummary'));
    }

    /**
     * Print thermal receipt for salary payment
     */
    public function printReceiptThermal(Salary $salary)
    {
        $salary->load('staff');
        
        // Calculate attendance summary for displaying hours/lectures
        $generateController = app(\App\Http\Controllers\GenerateSalaryController::class);
        $reflection = new \ReflectionClass($generateController);
        
        // Get month number
        $monthNames = [
            'January' => '01', 'February' => '02', 'March' => '03', 'April' => '04',
            'May' => '05', 'June' => '06', 'July' => '07', 'August' => '08',
            'September' => '09', 'October' => '10', 'November' => '11', 'December' => '12'
        ];
        $monthNumber = (int) ($monthNames[$salary->salary_month] ?? date('m'));
        
        // Calculate attendance summary
        $calculateAttendanceMethod = $reflection->getMethod('calculateAttendanceSummary');
        $calculateAttendanceMethod->setAccessible(true);
        $attendanceSummary = $calculateAttendanceMethod->invoke($generateController, $salary->staff_id, (int) $salary->year, $monthNumber);
        
        return view('salary-loan.print-receipt-thermal', compact('salary', 'attendanceSummary'));
    }

    /**
     * Net amount payable (salary_generated is already net of loan repayment).
     */
    private function netPayableAmount(Salary $salary): float
    {
        return $salary->netPayableAmount();
    }

    /**
     * Sync loan repayment for pending salaries in chronological order per staff member.
     */
    private function syncLoanRepaymentsForSalaries($salaries): void
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
                $this->syncPendingSalaryLoan($salary);
            }
        }
    }

    /**
     * Apply approved loan installment to a pending salary and deduct from generated amount.
     */
    private function syncPendingSalaryLoan(Salary $salary): void
    {
        app(GenerateSalaryController::class)->syncGeneratedSalaryRecord($salary);
    }

    /**
     * Campus options for filter dropdown (staff salary campuses + Campus model).
     */
    private function campusOptionsForFilter(): Collection
    {
        return Staff::query()
            ->whereHas('salaries')
            ->whereNotNull('campus')
            ->whereRaw("TRIM(COALESCE(campus, '')) != ''")
            ->distinct()
            ->orderBy('campus')
            ->pluck('campus')
            ->map(fn ($campus) => trim((string) $campus))
            ->filter()
            ->unique(fn ($campus) => strtolower($campus))
            ->sortBy(fn ($campus) => strtolower($campus))
            ->values()
            ->map(function (string $campus) {
                $record = Campus::query()
                    ->whereRaw('LOWER(TRIM(campus_name)) = ?', [strtolower($campus)])
                    ->first();

                return (object) [
                    'campus_name' => $record?->campus_name ?? $campus,
                    'filter_value' => $campus,
                ];
            });
    }

    /**
     * Restrict salaries to staff on the selected campus.
     */
    private function applyCampusFilter(Builder $query, ?string $campus): void
    {
        $campus = $campus !== null ? trim($campus) : '';
        if ($campus === '') {
            return;
        }

        $campusKey = strtolower($campus);

        $staffIds = Staff::query()
            ->whereRaw('LOWER(TRIM(COALESCE(campus, ""))) = ?', [$campusKey])
            ->pluck('id');

        if ($staffIds->isEmpty()) {
            $query->whereRaw('1 = 0');

            return;
        }

        $query->whereIn('staff_id', $staffIds);
    }

    /**
     * Search by staff name, salary month, or year (keeps campus filter intact).
     */
    private function applySearchFilter(Builder $query, ?string $search): void
    {
        $search = $search !== null ? trim($search) : '';
        if ($search === '') {
            return;
        }

        $searchLower = strtolower($search);
        $query->where(function (Builder $searchQuery) use ($search, $searchLower) {
            $searchQuery->whereHas('staff', function (Builder $staffQuery) use ($searchLower) {
                $staffQuery->whereRaw('LOWER(name) LIKE ?', ["%{$searchLower}%"]);
            })->orWhere('salary_month', 'like', "%{$search}%")
                ->orWhere('year', 'like', "%{$search}%");
        });
    }
}

