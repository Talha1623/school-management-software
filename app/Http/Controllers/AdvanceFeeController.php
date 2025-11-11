<?php

namespace App\Http\Controllers;

use App\Models\AdvanceFee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdvanceFeeController extends Controller
{
    /**
     * Display a listing of advance fees.
     */
    public function index(Request $request): View
    {
        $query = AdvanceFee::query();
        
        // Search functionality
        if ($request->filled('search')) {
            $search = trim($request->search);
            if (!empty($search)) {
                $searchLower = strtolower($search);
                $query->where(function($q) use ($search, $searchLower) {
                    $q->whereRaw('LOWER(parent_id) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(name) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(email) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(phone) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(id_card_number) LIKE ?', ["%{$searchLower}%"]);
                });
            }
        }
        
        $perPage = $request->get('per_page', 10);
        $perPage = in_array($perPage, [10, 25, 50, 100]) ? $perPage : 10;
        
        $advanceFees = $query->orderBy('name')->paginate($perPage)->withQueryString();
        
        return view('accounting.manage-advance-fee', compact('advanceFees'));
    }

    /**
     * Store a newly created advance fee.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'parent_id' => ['nullable', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'id_card_number' => ['nullable', 'string', 'max:255'],
            'available_credit' => ['nullable', 'numeric', 'min:0'],
            'increase' => ['nullable', 'numeric', 'min:0'],
            'decrease' => ['nullable', 'numeric', 'min:0'],
            'childs' => ['nullable', 'integer', 'min:0'],
        ]);

        // Calculate available credit if increase/decrease is provided
        if (isset($validated['increase']) && $validated['increase'] > 0) {
            $validated['available_credit'] = ($validated['available_credit'] ?? 0) + $validated['increase'];
        }
        if (isset($validated['decrease']) && $validated['decrease'] > 0) {
            $validated['available_credit'] = max(0, ($validated['available_credit'] ?? 0) - $validated['decrease']);
        }

        AdvanceFee::create($validated);

        return redirect()
            ->route('accounting.manage-advance-fee.index')
            ->with('success', 'Advance fee record created successfully!');
    }

    /**
     * Show the specified advance fee for editing.
     */
    public function show(AdvanceFee $advanceFee)
    {
        return response()->json($advanceFee);
    }

    /**
     * Update the specified advance fee.
     */
    public function update(Request $request, AdvanceFee $advanceFee): RedirectResponse
    {
        $validated = $request->validate([
            'parent_id' => ['nullable', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'id_card_number' => ['nullable', 'string', 'max:255'],
            'available_credit' => ['nullable', 'numeric', 'min:0'],
            'increase' => ['nullable', 'numeric', 'min:0'],
            'decrease' => ['nullable', 'numeric', 'min:0'],
            'childs' => ['nullable', 'integer', 'min:0'],
        ]);

        // Handle increase/decrease
        $currentCredit = $advanceFee->available_credit;
        if (isset($validated['increase']) && $validated['increase'] > 0) {
            $validated['available_credit'] = $currentCredit + $validated['increase'];
        }
        if (isset($validated['decrease']) && $validated['decrease'] > 0) {
            $validated['available_credit'] = max(0, $currentCredit - $validated['decrease']);
        }

        $advanceFee->update($validated);

        return redirect()
            ->route('accounting.manage-advance-fee.index')
            ->with('success', 'Advance fee record updated successfully!');
    }

    /**
     * Remove the specified advance fee.
     */
    public function destroy(AdvanceFee $advanceFee): RedirectResponse
    {
        $advanceFee->delete();

        return redirect()
            ->route('accounting.manage-advance-fee.index')
            ->with('success', 'Advance fee record deleted successfully!');
    }

    /**
     * Export advance fees to Excel, CSV, or PDF
     */
    public function export(Request $request, string $format)
    {
        $query = AdvanceFee::query();
        
        // Apply search filter if present
        if ($request->has('search') && $request->search) {
            $search = trim($request->search);
            if (!empty($search)) {
                $searchLower = strtolower($search);
                $query->where(function($q) use ($search, $searchLower) {
                    $q->whereRaw('LOWER(parent_id) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(name) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(email) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(phone) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(id_card_number) LIKE ?', ["%{$searchLower}%"]);
                });
            }
        }
        
        $advanceFees = $query->orderBy('name')->get();
        
        switch ($format) {
            case 'excel':
                return $this->exportExcel($advanceFees);
            case 'csv':
                return $this->exportCSV($advanceFees);
            case 'pdf':
                return $this->exportPDF($advanceFees);
            default:
                return redirect()->route('accounting.manage-advance-fee.index')
                    ->with('error', 'Invalid export format!');
        }
    }

    /**
     * Export to Excel (CSV format for Excel compatibility)
     */
    private function exportExcel($advanceFees)
    {
        $filename = 'advance_fees_' . date('Y-m-d_His') . '.csv';
        
        $headers = [
            'Content-Type' => 'application/vnd.ms-excel',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($advanceFees) {
            $file = fopen('php://output', 'w');
            
            // Add BOM for UTF-8
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            fputcsv($file, ['ID', 'Parent ID', 'Name', 'Email', 'Phone', 'ID Card Number', 'Available Credit', 'Increase', 'Decrease', 'Childs', 'Created At']);
            
            foreach ($advanceFees as $advanceFee) {
                fputcsv($file, [
                    $advanceFee->id,
                    $advanceFee->parent_id ?? '',
                    $advanceFee->name,
                    $advanceFee->email ?? '',
                    $advanceFee->phone ?? '',
                    $advanceFee->id_card_number ?? '',
                    $advanceFee->available_credit,
                    $advanceFee->increase,
                    $advanceFee->decrease,
                    $advanceFee->childs,
                    $advanceFee->created_at->format('Y-m-d H:i:s'),
                ]);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export to CSV
     */
    private function exportCSV($advanceFees)
    {
        $filename = 'advance_fees_' . date('Y-m-d_His') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($advanceFees) {
            $file = fopen('php://output', 'w');
            
            fputcsv($file, ['ID', 'Parent ID', 'Name', 'Email', 'Phone', 'ID Card Number', 'Available Credit', 'Increase', 'Decrease', 'Childs', 'Created At']);
            
            foreach ($advanceFees as $advanceFee) {
                fputcsv($file, [
                    $advanceFee->id,
                    $advanceFee->parent_id ?? '',
                    $advanceFee->name,
                    $advanceFee->email ?? '',
                    $advanceFee->phone ?? '',
                    $advanceFee->id_card_number ?? '',
                    $advanceFee->available_credit,
                    $advanceFee->increase,
                    $advanceFee->decrease,
                    $advanceFee->childs,
                    $advanceFee->created_at->format('Y-m-d H:i:s'),
                ]);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export to PDF
     */
    private function exportPDF($advanceFees)
    {
        $html = view('accounting.manage-advance-fee-pdf', compact('advanceFees'))->render();
        
        return response($html)
            ->header('Content-Type', 'text/html');
    }
}

