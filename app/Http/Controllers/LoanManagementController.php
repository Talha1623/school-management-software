<?php

namespace App\Http\Controllers;

use App\Models\Loan;
use App\Models\Staff;
use App\Models\AdminRole;
use App\Models\Message;
use App\Models\GeneralSetting;
use App\Services\StaffLoanRepaymentService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;

class LoanManagementController extends Controller
{
    private function notifyAboutNewLoan(Loan $loan): void
    {
        $publisher = Auth::guard('admin')->user();
        if (!$publisher) {
            return;
        }

        $staff = Staff::find($loan->staff_id);
        if (!$staff) {
            return;
        }

        $publisherId = (int) $publisher->id;
        $text = sprintf(
            '%s added a loan for %s. Requested: %s. Approved: %s. Instalments: %d. Status: %s.',
            $publisher->name ?? 'Admin',
            $staff->name ?? 'Staff',
            number_format((float) ($loan->requested_amount ?? 0), 2),
            number_format((float) ($loan->approved_amount ?? 0), 2),
            (int) ($loan->repayment_instalments ?? 0),
            $loan->status ?? 'Approved'
        );

        Message::create([
            'from_type' => 'admin',
            'from_id' => $publisherId,
            'to_type' => 'teacher',
            'to_id' => (int) $staff->id,
            'text' => $text,
            'attachment_path' => null,
            'attachment_type' => null,
            'read_at' => null,
        ]);

        AdminRole::query()
            ->select('id')
            ->orderBy('id')
            ->get()
            ->each(function (AdminRole $admin) use ($publisherId, $text) {
                if ((int) $admin->id === $publisherId) {
                    return;
                }

                Message::create([
                    'from_type' => 'staff_notification',
                    'from_id' => $publisherId,
                    'to_type' => 'admin',
                    'to_id' => $admin->id,
                    'text' => $text,
                    'attachment_path' => null,
                    'attachment_type' => null,
                    'read_at' => null,
                ]);
            });
    }

    /**
     * Display a listing of loans.
     */
    public function index(Request $request): View
    {
        Loan::ensureBalanceColumns();

        $staffIds = Loan::query()->distinct()->pluck('staff_id')->filter();
        $loanRepaymentService = app(StaffLoanRepaymentService::class);
        foreach ($staffIds as $staffId) {
            $loanRepaymentService->syncStaffLoanBalances((int) $staffId);
        }

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
     * Printable loans list (letterhead, same filters as list/export).
     */
    public function print(Request $request): View
    {
        $query = Loan::with('staff');

        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            if ($search !== '') {
                $searchLower = strtolower($search);
                $query->whereHas('staff', function ($q) use ($searchLower) {
                    $q->whereRaw('LOWER(name) LIKE ?', ["%{$searchLower}%"]);
                })->orWhere('status', 'like', "%{$search}%");
            }
        }

        $loans = $query->orderBy('created_at', 'desc')->get();

        $totalRequested = (float) $loans->sum('requested_amount');
        $totalApproved = (float) $loans->whereNotNull('approved_amount')->sum('approved_amount');

        return view('salary-loan.loan-management-print', [
            'loans' => $loans,
            'filterSearch' => $request->get('search'),
            'settings' => GeneralSetting::getSettings(),
            'printedAt' => Carbon::now()->format('d M Y, h:i A'),
            'totalRequested' => $totalRequested,
            'totalApproved' => $totalApproved,
        ]);
    }

    /**
     * Store a newly created loan.
     */
    public function store(Request $request): RedirectResponse
    {
        Loan::ensureBalanceColumns();

        $validated = $request->validate([
            'staff_id' => ['required', 'exists:staff,id'],
            'requested_amount' => ['required', 'numeric', 'min:0'],
            'approved_amount' => ['nullable', 'numeric', 'min:0'],
            'repayment_instalments' => ['required', 'integer', 'min:1'],
        ]);

        $validated['status'] = 'Approved';
        $requested = (float) $validated['requested_amount'];
        $approved = isset($validated['approved_amount']) ? (float) $validated['approved_amount'] : 0.0;
        if ($approved <= 0) {
            $approved = $requested;
        }
        $validated['approved_amount'] = $approved;
        if (Loan::hasInitialApprovedColumn()) {
            $validated['initial_approved_amount'] = max($requested, $approved);
        }

        $loan = Loan::create($validated);

        app(StaffLoanRepaymentService::class)->syncStaffLoanBalances((int) $validated['staff_id']);
        app(GenerateSalaryController::class)->syncPendingSalariesForStaff((int) $validated['staff_id']);

        $this->notifyAboutNewLoan($loan);

        return redirect()
            ->route('salary-loan.loan-management')
            ->with('success', 'Loan application created successfully!');
    }

    /**
     * Update the specified loan.
     */
    public function update(Request $request, Loan $loan): RedirectResponse
    {
        Loan::ensureBalanceColumns();

        $validated = $request->validate([
            'staff_id' => ['required', 'exists:staff,id'],
            'requested_amount' => ['required', 'numeric', 'min:0'],
            'approved_amount' => ['nullable', 'numeric', 'min:0'],
            'repayment_instalments' => ['required', 'integer', 'min:1'],
            'status' => ['required', 'in:Pending,Approved,Rejected,Completed'],
        ]);

        if (empty($validated['approved_amount']) && !empty($validated['requested_amount'])) {
            $validated['approved_amount'] = $validated['requested_amount'];
        }

        if (Loan::hasInitialApprovedColumn() && $loan->initial_approved_amount === null) {
            $validated['initial_approved_amount'] = $validated['approved_amount'] ?? $loan->requested_amount ?? 0;
        }

        $loan->update($validated);

        if (($validated['status'] ?? '') === 'Approved') {
            app(GenerateSalaryController::class)->syncPendingSalariesForStaff((int) $validated['staff_id']);
        }

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
            
            fputcsv($file, ['ID', 'Teacher Name', 'Requested Amount', 'Total Approved', 'Paid Amount', 'Remaining Amount', 'Repayment Instalments', 'Status', 'Created At']);
            
            foreach ($loans as $loan) {
                fputcsv($file, [
                    $loan->id,
                    $loan->staff->name ?? 'N/A',
                    $loan->requested_amount,
                    $loan->totalApprovedAmount(),
                    $loan->amountPaid(),
                    $loan->remainingAmount(),
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
            
            fputcsv($file, ['ID', 'Teacher Name', 'Requested Amount', 'Total Approved', 'Paid Amount', 'Remaining Amount', 'Repayment Instalments', 'Status', 'Created At']);
            
            foreach ($loans as $loan) {
                fputcsv($file, [
                    $loan->id,
                    $loan->staff->name ?? 'N/A',
                    $loan->requested_amount,
                    $loan->totalApprovedAmount(),
                    $loan->amountPaid(),
                    $loan->remainingAmount(),
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
        $totalRequested = (float) $loans->sum('requested_amount');
        $totalApproved = (float) $loans->whereNotNull('approved_amount')->sum('approved_amount');

        $html = view('salary-loan.loan-management-pdf', [
            'loans' => $loans,
            'settings' => GeneralSetting::getSettings(),
            'printedAt' => Carbon::now()->format('d M Y, h:i A'),
            'totalRequested' => $totalRequested,
            'totalApproved' => $totalApproved,
        ])->render();

        return response($html)
            ->header('Content-Type', 'text/html');
    }
}

