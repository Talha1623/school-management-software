<?php

namespace App\Http\Controllers;

use App\Models\Accountant;
use App\Models\Campus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AccountantController extends Controller
{
    /**
     * Display a listing of accountants.
     */
    public function index(Request $request): View
    {
        $query = Accountant::query();

        // Search functionality
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('campus', 'like', "%{$search}%");
            });
        }

        // Pagination
        $perPage = $request->get('per_page', 10);
        $perPage = in_array($perPage, [10, 25, 50, 100]) ? $perPage : 10;
        
        $accountants = $query->latest()->paginate($perPage)->withQueryString();

        // Summary statistics
        $totalAccountants = Accountant::count();
        $activeAccountants = Accountant::where('app_login_enabled', true)
            ->where('web_login_enabled', true)
            ->count();
        $restrictedAccountants = Accountant::where(function($q) {
            $q->where('app_login_enabled', false)
              ->orWhere('web_login_enabled', false);
        })->count();

        // Get campuses for dropdown
        $campuses = Campus::orderBy('campus_name', 'asc')->get();
        
        // If no campuses found, get from accountants
        if ($campuses->isEmpty()) {
            $campusesFromAccountants = Accountant::whereNotNull('campus')
                ->distinct()
                ->pluck('campus')
                ->sort()
                ->values();
            
            // Convert to collection of objects with campus_name property
            $campuses = collect();
            foreach ($campusesFromAccountants as $campusName) {
                $campuses->push((object)['campus_name' => $campusName]);
            }
        }

        return view('accountant', compact('accountants', 'totalAccountants', 'activeAccountants', 'restrictedAccountants', 'campuses'));
    }

    /**
     * Store a newly created accountant.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:accountants,email', 'max:255'],
            'campus' => ['nullable', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:6'],
        ]);

        // Password will be hashed automatically by the model's setPasswordAttribute mutator
        $validated['app_login_enabled'] = true;
        $validated['web_login_enabled'] = true;

        Accountant::create($validated);

        return redirect()
            ->route('accountants')
            ->with('success', 'Accountant created successfully!');
    }

    /**
     * Display the specified accountant.
     */
    public function show(Accountant $accountant)
    {
        return response()->json([
            'id' => $accountant->id,
            'name' => $accountant->name,
            'email' => $accountant->email,
            'campus' => $accountant->campus,
        ]);
    }

    /**
     * Update the specified accountant.
     */
    public function update(Request $request, Accountant $accountant)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:accountants,email,' . $accountant->id, 'max:255'],
            'campus' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'min:6'],
        ]);

        // Password will be hashed automatically by the model's setPasswordAttribute mutator
        if (empty($validated['password'])) {
            unset($validated['password']);
        }

        $accountant->update($validated);

        return redirect()
            ->route('accountants')
            ->with('success', 'Accountant updated successfully!');
    }

    /**
     * Remove the specified accountant.
     */
    public function destroy(Accountant $accountant)
    {
        $accountant->delete();

        return redirect()
            ->route('accountants')
            ->with('success', 'Accountant deleted successfully!');
    }

    /**
     * Toggle app login status.
     */
    public function toggleAppLogin(Accountant $accountant)
    {
        $accountant->app_login_enabled = !$accountant->app_login_enabled;
        $accountant->save();

        return response()->json([
            'success' => true,
            'app_login_enabled' => $accountant->app_login_enabled,
            'message' => 'App login status updated successfully!'
        ]);
    }

    /**
     * Toggle web login status.
     */
    public function toggleWebLogin(Accountant $accountant)
    {
        $accountant->web_login_enabled = !$accountant->web_login_enabled;
        $accountant->save();

        return response()->json([
            'success' => true,
            'web_login_enabled' => $accountant->web_login_enabled,
            'message' => 'Web login status updated successfully!'
        ]);
    }

    /**
     * Export accountants to Excel, CSV, or PDF
     */
    public function export(Request $request, string $format)
    {
        $query = Accountant::query();
        
        // Apply search filter if present
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('campus', 'like', "%{$search}%");
            });
        }
        
        $accountants = $query->latest()->get();
        
        switch ($format) {
            case 'excel':
                return $this->exportExcel($accountants);
            case 'csv':
                return $this->exportCSV($accountants);
            case 'pdf':
                return $this->exportPDF($accountants);
            default:
                return redirect()->route('accountants')
                    ->with('error', 'Invalid export format!');
        }
    }

    /**
     * Export to Excel (CSV format for Excel compatibility)
     */
    private function exportExcel($accountants)
    {
        $filename = 'accountants_' . date('Y-m-d_His') . '.csv';
        
        $headers = [
            'Content-Type' => 'application/vnd.ms-excel',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($accountants) {
            $file = fopen('php://output', 'w');
            
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            fputcsv($file, ['ID', 'Name', 'Email', 'Campus', 'App Login', 'Web Login', 'Created At']);
            
            foreach ($accountants as $accountant) {
                fputcsv($file, [
                    $accountant->id,
                    $accountant->name,
                    $accountant->email,
                    $accountant->campus ?? 'N/A',
                    $accountant->app_login_enabled ? 'Enabled' : 'Disabled',
                    $accountant->web_login_enabled ? 'Enabled' : 'Disabled',
                    $accountant->created_at->format('Y-m-d H:i:s'),
                ]);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export to CSV
     */
    private function exportCSV($accountants)
    {
        $filename = 'accountants_' . date('Y-m-d_His') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($accountants) {
            $file = fopen('php://output', 'w');
            
            fputcsv($file, ['ID', 'Name', 'Email', 'Campus', 'App Login', 'Web Login', 'Created At']);
            
            foreach ($accountants as $accountant) {
                fputcsv($file, [
                    $accountant->id,
                    $accountant->name,
                    $accountant->email,
                    $accountant->campus ?? 'N/A',
                    $accountant->app_login_enabled ? 'Enabled' : 'Disabled',
                    $accountant->web_login_enabled ? 'Enabled' : 'Disabled',
                    $accountant->created_at->format('Y-m-d H:i:s'),
                ]);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export to PDF
     */
    private function exportPDF($accountants)
    {
        $html = view('accountant-pdf', compact('accountants'))->render();
        
        return response($html)
            ->header('Content-Type', 'text/html');
    }

    /**
     * Accountant Pages - Task Management
     */
    public function taskManagement(): View
    {
        return view('accountant.task-management');
    }

    /**
     * Accountant Pages - Fee Payment
     */
    public function feePayment(): View
    {
        return view('accountant.fee-payment');
    }

    /**
     * Accountant Pages - Family Fee Calculator
     */
    public function familyFeeCalculator(): View
    {
        return view('accountant.family-fee-calculator');
    }

    /**
     * Accountant Pages - Generate Monthly Fee
     */
    public function generateMonthlyFee(): View
    {
        return view('accountant.generate-monthly-fee');
    }

    /**
     * Accountant Pages - Generate Custom Fee
     */
    public function generateCustomFee(): View
    {
        return view('accountant.generate-custom-fee');
    }

    /**
     * Accountant Pages - Generate Transport Fee
     */
    public function generateTransportFee(): View
    {
        return view('accountant.generate-transport-fee');
    }

    /**
     * Accountant Pages - Fee Type
     */
    public function feeType(): View
    {
        return view('accountant.fee-type');
    }

    /**
     * Accountant Pages - Parents Credit System
     */
    public function parentsCreditSystem(): View
    {
        return view('accountant.parents-credit-system');
    }

    /**
     * Accountant Pages - Direct Payment
     */
    public function directPayment(): View
    {
        return view('accountant.direct-payment');
    }

    /**
     * Accountant Pages - Student Payment
     */
    public function studentPayment(): View
    {
        return view('accountant.student-payment');
    }

    /**
     * Accountant Pages - Custom Payment
     */
    public function customPayment(): View
    {
        return view('accountant.custom-payment');
    }

    /**
     * Accountant Pages - SMS to Fee Defaulters
     */
    public function smsFeeDefaulters(): View
    {
        return view('accountant.sms-fee-defaulters');
    }

    /**
     * Accountant Pages - Deleted Fees
     */
    public function deletedFees(): View
    {
        return view('accountant.deleted-fees');
    }

    /**
     * Accountant Pages - Print Fee Vouchers
     */
    public function printFeeVouchers(): View
    {
        return view('accountant.print-fee-vouchers');
    }

    /**
     * Accountant Pages - Print Balance Sheet
     */
    public function printBalanceSheet(): View
    {
        return view('accountant.print-balance-sheet');
    }

    /**
     * Accountant Pages - Expense Management
     */
    public function expenseManagement(): View
    {
        return view('accountant.expense-management');
    }

    /**
     * Accountant Pages - Reporting Area
     */
    public function reportingArea(): View
    {
        return view('accountant.reporting-area');
    }

    /**
     * Accountant Pages - Academic Calendar
     */
    public function academicCalendar(): View
    {
        return view('accountant.academic-calendar');
    }

    /**
     * Accountant Pages - Stock & Inventory
     */
    public function stockInventory(): View
    {
        return view('accountant.stock-inventory');
    }
}
