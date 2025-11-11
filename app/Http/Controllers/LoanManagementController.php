<?php

namespace App\Http\Controllers;

use App\Models\Loan;
use App\Models\Staff;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LoanManagementController extends Controller
{
    /**
     * Display a listing of loans.
     */
    public function index(Request $request): View
    {
        $query = Loan::with('staff');
        
        // Search functionality
        if ($request->filled('search')) {
            $search = trim($request->search);
            if (!empty($search)) {
                $searchLower = strtolower($search);
                $query->whereHas('staff', function($q) use ($search, $searchLower) {
                    $q->whereRaw('LOWER(name) LIKE ?', ["%{$searchLower}%"]);
                })->orWhere('status', 'like', "%{$search}%");
            }
        }
        
        $perPage = $request->get('per_page', 10);
        $perPage = in_array($perPage, [10, 25, 50, 100]) ? $perPage : 10;
        
        $loans = $query->orderBy('created_at', 'desc')->paginate($perPage)->withQueryString();
        
        // Get all staff for dropdown
        $staff = Staff::orderBy('name')->get();
        
        return view('salary-loan.loan-management', compact('loans', 'staff'));
    }

    /**
     * Store a newly created loan.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'staff_id' => ['required', 'exists:staff,id'],
            'requested_amount' => ['required', 'numeric', 'min:0'],
            'approved_amount' => ['nullable', 'numeric', 'min:0'],
            'repayment_instalments' => ['required', 'integer', 'min:1'],
        ]);

        $validated['status'] = 'Pending';

        Loan::create($validated);

        return redirect()
            ->route('salary-loan.loan-management')
            ->with('success', 'Loan application created successfully!');
    }

    /**
     * Update the specified loan.
     */
    public function update(Request $request, Loan $loan): RedirectResponse
    {
        $validated = $request->validate([
            'staff_id' => ['required', 'exists:staff,id'],
            'requested_amount' => ['required', 'numeric', 'min:0'],
            'approved_amount' => ['nullable', 'numeric', 'min:0'],
            'repayment_instalments' => ['required', 'integer', 'min:1'],
            'status' => ['required', 'in:Pending,Approved,Rejected,Completed'],
        ]);

        $loan->update($validated);

        return redirect()
            ->route('salary-loan.loan-management')
            ->with('success', 'Loan updated successfully!');
    }

    /**
     * Remove the specified loan.
     */
    public function destroy(Loan $loan): RedirectResponse
    {
        $loan->delete();

        return redirect()
            ->route('salary-loan.loan-management')
            ->with('success', 'Loan deleted successfully!');
    }

    /**
     * Export loans to Excel, CSV, or PDF
     */
    public function export(Request $request, string $format)
    {
        $query = Loan::with('staff');
        
        // Apply search filter if present
        if ($request->has('search') && $request->search) {
            $search = trim($request->search);
            if (!empty($search)) {
                $searchLower = strtolower($search);
                $query->whereHas('staff', function($q) use ($search, $searchLower) {
                    $q->whereRaw('LOWER(name) LIKE ?', ["%{$searchLower}%"]);
                })->orWhere('status', 'like', "%{$search}%");
            }
        }
        
        $loans = $query->orderBy('created_at', 'desc')->get();
        
        switch ($format) {
            case 'excel':
                return $this->exportExcel($loans);
            case 'csv':
                return $this->exportCSV($loans);
            case 'pdf':
                return $this->exportPDF($loans);
            default:
                return redirect()->route('salary-loan.loan-management')
                    ->with('error', 'Invalid export format!');
        }
    }

    /**
     * Export to Excel (CSV format for Excel compatibility)
     */
    private function exportExcel($loans)
    {
        $filename = 'loans_' . date('Y-m-d_His') . '.csv';
        
        $headers = [
            'Content-Type' => 'application/vnd.ms-excel',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($loans) {
            $file = fopen('php://output', 'w');
            
            // Add BOM for UTF-8
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            fputcsv($file, ['ID', 'Teacher Name', 'Requested Amount', 'Approved Amount', 'Repayment Instalments', 'Status', 'Created At']);
            
            foreach ($loans as $loan) {
                fputcsv($file, [
                    $loan->id,
                    $loan->staff->name ?? 'N/A',
                    $loan->requested_amount,
                    $loan->approved_amount ?? 'N/A',
                    $loan->repayment_instalments,
                    $loan->status,
                    $loan->created_at->format('Y-m-d H:i:s'),
                ]);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export to CSV
     */
    private function exportCSV($loans)
    {
        $filename = 'loans_' . date('Y-m-d_His') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($loans) {
            $file = fopen('php://output', 'w');
            
            fputcsv($file, ['ID', 'Teacher Name', 'Requested Amount', 'Approved Amount', 'Repayment Instalments', 'Status', 'Created At']);
            
            foreach ($loans as $loan) {
                fputcsv($file, [
                    $loan->id,
                    $loan->staff->name ?? 'N/A',
                    $loan->requested_amount,
                    $loan->approved_amount ?? 'N/A',
                    $loan->repayment_instalments,
                    $loan->status,
                    $loan->created_at->format('Y-m-d H:i:s'),
                ]);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export to PDF
     */
    private function exportPDF($loans)
    {
        $html = view('salary-loan.loan-management-pdf', compact('loans'))->render();
        
        return response($html)
            ->header('Content-Type', 'text/html');
    }
}

