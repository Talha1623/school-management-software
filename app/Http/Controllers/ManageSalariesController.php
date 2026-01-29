<?php

namespace App\Http\Controllers;

use App\Models\Salary;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ManageSalariesController extends Controller
{
    /**
     * Display a listing of salaries.
     */
    public function index(Request $request): View
    {
        $query = Salary::with('staff');
        
        // Search functionality
        if ($request->filled('search')) {
            $search = trim($request->search);
            if (!empty($search)) {
                $searchLower = strtolower($search);
                $query->whereHas('staff', function($q) use ($search, $searchLower) {
                    $q->whereRaw('LOWER(name) LIKE ?', ["%{$searchLower}%"]);
                })->orWhere('salary_month', 'like', "%{$search}%")
                  ->orWhere('year', 'like', "%{$search}%");
            }
        }
        
        $perPage = $request->get('per_page', 10);
        $perPage = in_array($perPage, [10, 25, 50, 100]) ? $perPage : 10;
        
        $salaries = $query->orderBy('created_at', 'desc')->paginate($perPage)->withQueryString();
        
        return view('salary-loan.manage-salaries', compact('salaries'));
    }

    /**
     * Show the specified salary.
     */
    public function show(Request $request, Salary $salary)
    {
        // If request wants JSON (for modal), return JSON
        if ($request->wantsJson() || $request->ajax()) {
            $salary->load('staff');
            
            // Calculate fees deductions
            $staff = $salary->staff;
            $lateCount = $salary->late ?? 0;
            $earlyExitCount = $salary->early_exit ?? 0;
            
            // Get staff individual fees or defaults
            $lateFeePerLate = $staff->late_fees ?? 500;
            $earlyExitFeePerExit = $staff->early_exit_fees ?? 1000;
            
            // Calculate total fees
            $lateFeesTotal = $lateFeePerLate * $lateCount;
            $earlyExitFeesTotal = $earlyExitFeePerExit * $earlyExitCount;
            
            // For absent fees, we need to calculate from attendance summary
            // Since we don't have deductible absents count directly, we'll calculate it
            $absentCount = $salary->absent ?? 0;
            $staffFreeAbsents = $staff->free_absent ?? 0;
            $deductibleAbsents = max(0, $absentCount - $staffFreeAbsents);
            
            $absentFeePerAbsent = $staff->absent_fees ?? null;
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
        $monthNames = [
            'January' => '01', 'February' => '02', 'March' => '03', 'April' => '04',
            'May' => '05', 'June' => '06', 'July' => '07', 'August' => '08',
            'September' => '09', 'October' => '10', 'November' => '11', 'December' => '12'
        ];
        return $monthNames[$monthName] ?? date('m');
    }

    /**
     * Update salary payment.
     */
    public function updatePayment(Request $request, Salary $salary)
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
        $newSalaryGenerated = $salary->salary_generated + $bonusAmount - $deductionAmount;

        // Determine status based on fully_paid or amount_paid
        $fullyPaid = isset($validated['fully_paid']) && ($validated['fully_paid'] == '1' || $validated['fully_paid'] === true);
        $status = 'Pending';
        if ($fullyPaid || $validated['amount_paid'] >= $newSalaryGenerated) {
            $status = 'Paid';
        } elseif ($validated['amount_paid'] > 0) {
            $status = 'Issued';
        }

        // Update salary
        $salary->update([
            'amount_paid' => $validated['amount_paid'],
            'loan_repayment' => $validated['loan_repayment'] ?? 0,
            'salary_generated' => $newSalaryGenerated,
            'status' => $status,
        ]);

        // TODO: Store bonus and deduction details if needed (may require additional table)
        // TODO: Send notification to employee if notify_employee is true

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

        $salary->update($validated);

        return redirect()
            ->route('salary-loan.manage-salaries')
            ->with('success', 'Salary status updated successfully!');
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
        
        // Apply search filter if present
        if ($request->has('search') && $request->search) {
            $search = trim($request->search);
            if (!empty($search)) {
                $searchLower = strtolower($search);
                $query->whereHas('staff', function($q) use ($search, $searchLower) {
                    $q->whereRaw('LOWER(name) LIKE ?', ["%{$searchLower}%"]);
                })->orWhere('salary_month', 'like', "%{$search}%")
                  ->orWhere('year', 'like', "%{$search}%");
            }
        }
        
        $salaries = $query->orderBy('created_at', 'desc')->get();
        
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
                    $salary->salary_generated,
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
                    $salary->salary_generated,
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
}

