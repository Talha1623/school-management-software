<?php

namespace App\Http\Controllers;

use App\Models\ParentAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class ParentAccountController extends Controller
{
    /**
     * Display a listing of parent accounts.
     */
    public function index(Request $request): View
    {
        $query = ParentAccount::query();
        
        // Search functionality
        if ($request->filled('search')) {
            $search = trim($request->search);
            if (!empty($search)) {
                $searchLower = strtolower($search);
                $query->where(function($q) use ($search, $searchLower) {
                    $q->whereRaw('LOWER(name) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(email) LIKE ?', ["%{$searchLower}%"])
                      ->orWhere('phone', 'like', "%{$search}%")
                      ->orWhere('whatsapp', 'like', "%{$search}%")
                      ->orWhere('id_card_number', 'like', "%{$search}%")
                      ->orWhereRaw('LOWER(profession) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(address) LIKE ?', ["%{$searchLower}%"]);
                });
            }
        }
        
        $perPage = $request->get('per_page', 10);
        $perPage = in_array($perPage, [10, 25, 50, 100]) ? $perPage : 10;
        
        $parents = $query->latest()->paginate($perPage)->withQueryString();
        
        return view('parent.manage-access', compact('parents'));
    }

    /**
     * Store a newly created parent account.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:parent_accounts,email'],
            'password' => ['required', 'string', 'min:6'],
            'phone' => ['nullable', 'string', 'max:20'],
            'whatsapp' => ['nullable', 'string', 'max:20'],
            'id_card_number' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'profession' => ['nullable', 'string', 'max:255'],
        ]);

        $validated['password'] = Hash::make($validated['password']);

        ParentAccount::create($validated);

        return redirect()
            ->route('parent.manage-access')
            ->with('success', 'Parent account created successfully!');
    }

    /**
     * Update the specified parent account.
     */
    public function update(Request $request, ParentAccount $parent_account): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:parent_accounts,email,' . $parent_account->id],
            'password' => ['nullable', 'string', 'min:6'],
            'phone' => ['nullable', 'string', 'max:20'],
            'whatsapp' => ['nullable', 'string', 'max:20'],
            'id_card_number' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'profession' => ['nullable', 'string', 'max:255'],
        ]);

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $parent_account->update($validated);

        return redirect()
            ->route('parent.manage-access')
            ->with('success', 'Parent account updated successfully!');
    }

    /**
     * Remove the specified parent account.
     */
    public function destroy(ParentAccount $parent_account): RedirectResponse
    {
        $parent_account->delete();

        return redirect()
            ->route('parent.manage-access')
            ->with('success', 'Parent account deleted successfully!');
    }

    /**
     * Delete all parent accounts.
     */
    public function deleteAll(Request $request): RedirectResponse
    {
        ParentAccount::truncate();

        return redirect()
            ->route('parent.manage-access')
            ->with('success', 'All parent accounts deleted successfully!');
    }

    /**
     * Export parent accounts to Excel, CSV, or PDF
     */
    public function export(Request $request, string $format)
    {
        $query = ParentAccount::query();
        
        // Apply search filter if present
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('whatsapp', 'like', "%{$search}%")
                  ->orWhere('id_card_number', 'like', "%{$search}%")
                  ->orWhere('profession', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%");
            });
        }
        
        $parents = $query->latest()->get();
        
        switch ($format) {
            case 'excel':
                return $this->exportExcel($parents);
            case 'csv':
                return $this->exportCSV($parents);
            case 'pdf':
                return $this->exportPDF($parents);
            default:
                return redirect()->route('parent.manage-access')
                    ->with('error', 'Invalid export format!');
        }
    }

    /**
     * Export to Excel (CSV format for Excel compatibility)
     */
    private function exportExcel($parents)
    {
        $filename = 'parent_accounts_' . date('Y-m-d_His') . '.csv';
        
        $headers = [
            'Content-Type' => 'application/vnd.ms-excel',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($parents) {
            $file = fopen('php://output', 'w');
            
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            fputcsv($file, ['ID', 'Name', 'Email', 'Phone', 'WhatsApp', 'ID Card Number', 'Address', 'Profession', 'Created At']);
            
            foreach ($parents as $parent) {
                fputcsv($file, [
                    $parent->id,
                    $parent->name,
                    $parent->email,
                    $parent->phone ?? 'N/A',
                    $parent->whatsapp ?? 'N/A',
                    $parent->id_card_number ?? 'N/A',
                    $parent->address ?? 'N/A',
                    $parent->profession ?? 'N/A',
                    $parent->created_at->format('Y-m-d H:i:s'),
                ]);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export to CSV
     */
    private function exportCSV($parents)
    {
        $filename = 'parent_accounts_' . date('Y-m-d_His') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($parents) {
            $file = fopen('php://output', 'w');
            
            fputcsv($file, ['ID', 'Name', 'Email', 'Phone', 'WhatsApp', 'ID Card Number', 'Address', 'Profession', 'Created At']);
            
            foreach ($parents as $parent) {
                fputcsv($file, [
                    $parent->id,
                    $parent->name,
                    $parent->email,
                    $parent->phone ?? 'N/A',
                    $parent->whatsapp ?? 'N/A',
                    $parent->id_card_number ?? 'N/A',
                    $parent->address ?? 'N/A',
                    $parent->profession ?? 'N/A',
                    $parent->created_at->format('Y-m-d H:i:s'),
                ]);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export to PDF
     */
    private function exportPDF($parents)
    {
        $html = view('parent.manage-access-pdf', compact('parents'))->render();
        
        return response($html)
            ->header('Content-Type', 'text/html');
    }
}

