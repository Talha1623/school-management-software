<?php

namespace App\Http\Controllers;

use App\Models\AttendanceAccount;
use App\Models\ClassModel;
use App\Models\Campus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AttendanceAccountController extends Controller
{
    /**
     * Display a listing of attendance accounts.
     */
    public function index(Request $request): View
    {
        $query = AttendanceAccount::query();
        
        // Search functionality
        if ($request->filled('search')) {
            $search = trim($request->search);
            if (!empty($search)) {
                $searchLower = strtolower($search);
                $query->where(function($q) use ($search, $searchLower) {
                    $q->whereRaw('LOWER(user_name) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(user_id_card) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(campus) LIKE ?', ["%{$searchLower}%"]);
                });
            }
        }
        
        $perPage = $request->get('per_page', 10);
        $perPage = in_array($perPage, [10, 25, 50, 100]) ? $perPage : 10;
        
        $accounts = $query->orderBy('created_at', 'desc')->paginate($perPage)->withQueryString();
        
        // Get campuses from Campus model
        $campuses = Campus::orderBy('campus_name', 'asc')->get();
        
        // If no campuses found, get from classes or attendance accounts
        if ($campuses->isEmpty()) {
            $campusesFromClasses = ClassModel::whereNotNull('campus')
                ->distinct()
                ->pluck('campus')
                ->sort()
                ->values();
            
            $campusesFromAccounts = AttendanceAccount::whereNotNull('campus')
                ->distinct()
                ->pluck('campus')
                ->sort()
                ->values();
            
            $allCampuses = $campusesFromClasses->merge($campusesFromAccounts)->unique()->sort()->values();
            
            // Convert to collection of objects with campus_name property
            $campuses = collect();
            foreach ($allCampuses as $campusName) {
                $campuses->push((object)['campus_name' => $campusName]);
            }
        }
        
        return view('attendance.account', compact('accounts', 'campuses'));
    }

    /**
     * Store a newly created attendance account.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'user_name' => ['required', 'string', 'max:255'],
            'user_id_card' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:6'],
            'campus' => ['required', 'string', 'max:255'],
        ]);

        AttendanceAccount::create($validated);

        return redirect()
            ->route('attendance.account')
            ->with('success', 'Attendance account created successfully!');
    }

    /**
     * Update the specified attendance account.
     */
    public function update(Request $request, AttendanceAccount $attendance_account): RedirectResponse
    {
        $validated = $request->validate([
            'user_name' => ['required', 'string', 'max:255'],
            'user_id_card' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:6'],
            'campus' => ['required', 'string', 'max:255'],
        ]);

        $attendance_account->update($validated);

        return redirect()
            ->route('attendance.account')
            ->with('success', 'Attendance account updated successfully!');
    }

    /**
     * Remove the specified attendance account.
     */
    public function destroy(AttendanceAccount $attendance_account): RedirectResponse
    {
        $attendance_account->delete();

        return redirect()
            ->route('attendance.account')
            ->with('success', 'Attendance account deleted successfully!');
    }

    /**
     * Export attendance accounts to Excel, CSV, or PDF
     */
    public function export(Request $request, string $format)
    {
        $query = AttendanceAccount::query();
        
        // Apply search filter if present
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('user_name', 'like', "%{$search}%")
                  ->orWhere('user_id_card', 'like', "%{$search}%")
                  ->orWhere('campus', 'like', "%{$search}%");
            });
        }
        
        $accounts = $query->orderBy('created_at', 'desc')->get();
        
        switch ($format) {
            case 'excel':
                return $this->exportExcel($accounts);
            case 'csv':
                return $this->exportCSV($accounts);
            case 'pdf':
                return $this->exportPDF($accounts);
            default:
                return redirect()->route('attendance.account')
                    ->with('error', 'Invalid export format!');
        }
    }

    /**
     * Export to Excel (CSV format for Excel compatibility)
     */
    private function exportExcel($accounts)
    {
        $filename = 'attendance_accounts_' . date('Y-m-d_His') . '.csv';
        
        $headers = [
            'Content-Type' => 'application/vnd.ms-excel',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($accounts) {
            $file = fopen('php://output', 'w');
            
            // Add BOM for UTF-8
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            fputcsv($file, ['ID', 'User Name', 'User ID Card', 'Password', 'Campus', 'Created At']);
            
            foreach ($accounts as $account) {
                fputcsv($file, [
                    $account->id,
                    $account->user_name,
                    $account->user_id_card,
                    $account->password,
                    $account->campus,
                    $account->created_at->format('Y-m-d H:i:s'),
                ]);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export to CSV
     */
    private function exportCSV($accounts)
    {
        $filename = 'attendance_accounts_' . date('Y-m-d_His') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($accounts) {
            $file = fopen('php://output', 'w');
            
            fputcsv($file, ['ID', 'User Name', 'User ID Card', 'Password', 'Campus', 'Created At']);
            
            foreach ($accounts as $account) {
                fputcsv($file, [
                    $account->id,
                    $account->user_name,
                    $account->user_id_card,
                    $account->password,
                    $account->campus,
                    $account->created_at->format('Y-m-d H:i:s'),
                ]);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export to PDF
     */
    private function exportPDF($accounts)
    {
        $html = view('attendance.account-pdf', compact('accounts'))->render();
        
        return response($html)
            ->header('Content-Type', 'text/html');
    }
}
