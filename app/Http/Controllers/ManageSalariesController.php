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
     * Update salary status.
     */
    public function updateStatus(Request $request, Salary $salary)
    {
        $validated = $request->validate([
            'status' => ['required', 'in:Pending,Paid,Partial'],
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

