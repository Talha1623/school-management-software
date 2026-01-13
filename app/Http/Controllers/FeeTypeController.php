<?php

namespace App\Http\Controllers;

use App\Models\FeeType;
use App\Models\Campus;
use App\Models\ClassModel;
use App\Models\Section;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FeeTypeController extends Controller
{
    /**
     * Display a listing of fee types.
     */
    public function index(Request $request): View
    {
        $query = FeeType::query();
        
        // Search functionality
        if ($request->filled('search')) {
            $search = trim($request->search);
            if (!empty($search)) {
                $searchLower = strtolower($search);
                $query->where(function($q) use ($search, $searchLower) {
                    $q->whereRaw('LOWER(fee_name) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(campus) LIKE ?', ["%{$searchLower}%"]);
                });
            }
        }
        
        $perPage = $request->get('per_page', 10);
        $perPage = in_array($perPage, [10, 25, 50, 100]) ? $perPage : 10;
        
        $feeTypes = $query->orderBy('fee_name')->paginate($perPage)->withQueryString();
        
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
        
        return view('accounting.fee-type', compact('feeTypes', 'campuses'));
    }

    /**
     * Store a newly created fee type.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'fee_name' => ['required', 'string', 'max:255'],
            'campus' => ['required', 'string', 'max:255'],
        ]);

        FeeType::create($validated);

        // Redirect based on which route was used (accountant or accounting)
        $redirectRoute = request()->route()->getName() === 'accountant.fee-type.store' 
            ? 'accountant.fee-type' 
            : 'accounting.fee-type';

        return redirect()
            ->route($redirectRoute)
            ->with('success', 'Fee type created successfully!');
    }

    /**
     * Show the specified fee type for editing.
     */
    public function show(FeeType $feeType)
    {
        return response()->json($feeType);
    }

    /**
     * Update the specified fee type.
     */
    public function update(Request $request, FeeType $feeType): RedirectResponse
    {
        $validated = $request->validate([
            'fee_name' => ['required', 'string', 'max:255'],
            'campus' => ['required', 'string', 'max:255'],
        ]);

        $feeType->update($validated);

        // Redirect based on which route was used (accountant or accounting)
        $redirectRoute = request()->route()->getName() === 'accountant.fee-type.update' 
            ? 'accountant.fee-type' 
            : 'accounting.fee-type';

        return redirect()
            ->route($redirectRoute)
            ->with('success', 'Fee type updated successfully!');
    }

    /**
     * Remove the specified fee type.
     */
    public function destroy(FeeType $feeType): RedirectResponse
    {
        $feeType->delete();

        // Redirect based on which route was used (accountant or accounting)
        $redirectRoute = request()->route()->getName() === 'accountant.fee-type.destroy' 
            ? 'accountant.fee-type' 
            : 'accounting.fee-type';

        return redirect()
            ->route($redirectRoute)
            ->with('success', 'Fee type deleted successfully!');
    }

    /**
     * Export fee types to Excel, CSV, or PDF
     */
    public function export(Request $request, string $format)
    {
        $query = FeeType::query();
        
        // Apply search filter if present
        if ($request->has('search') && $request->search) {
            $search = trim($request->search);
            if (!empty($search)) {
                $searchLower = strtolower($search);
                $query->where(function($q) use ($search, $searchLower) {
                    $q->whereRaw('LOWER(fee_name) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(campus) LIKE ?', ["%{$searchLower}%"]);
                });
            }
        }
        
        $feeTypes = $query->orderBy('fee_name')->get();
        
        // Determine redirect route based on current route
        $redirectRoute = request()->route()->getName() === 'accountant.fee-type.export' 
            ? 'accountant.fee-type' 
            : 'accounting.fee-type';
        
        switch ($format) {
            case 'excel':
                return $this->exportExcel($feeTypes);
            case 'csv':
                return $this->exportCSV($feeTypes);
            case 'pdf':
                return $this->exportPDF($feeTypes);
            default:
                return redirect()->route($redirectRoute)
                    ->with('error', 'Invalid export format!');
        }
    }

    /**
     * Export to Excel (CSV format for Excel compatibility)
     */
    private function exportExcel($feeTypes)
    {
        $filename = 'fee_types_' . date('Y-m-d_His') . '.csv';
        
        $headers = [
            'Content-Type' => 'application/vnd.ms-excel',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($feeTypes) {
            $file = fopen('php://output', 'w');
            
            // Add BOM for UTF-8
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            fputcsv($file, ['ID', 'Fee Name', 'Campus', 'Created At']);
            
            foreach ($feeTypes as $feeType) {
                fputcsv($file, [
                    $feeType->id,
                    $feeType->fee_name,
                    $feeType->campus,
                    $feeType->created_at->format('Y-m-d H:i:s'),
                ]);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export to CSV
     */
    private function exportCSV($feeTypes)
    {
        $filename = 'fee_types_' . date('Y-m-d_His') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($feeTypes) {
            $file = fopen('php://output', 'w');
            
            fputcsv($file, ['ID', 'Fee Name', 'Campus', 'Created At']);
            
            foreach ($feeTypes as $feeType) {
                fputcsv($file, [
                    $feeType->id,
                    $feeType->fee_name,
                    $feeType->campus,
                    $feeType->created_at->format('Y-m-d H:i:s'),
                ]);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export to PDF
     */
    private function exportPDF($feeTypes)
    {
        $html = view('accounting.fee-type-pdf', compact('feeTypes'))->render();
        
        return response($html)
            ->header('Content-Type', 'text/html');
    }
}

