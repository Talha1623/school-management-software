<?php

namespace App\Http\Controllers;

use App\Models\Campus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CampusController extends Controller
{
    /**
     * Display a listing of campuses.
     */
    public function index(Request $request): View
    {
        $query = Campus::query();
        
        // Search functionality
        if ($request->filled('search')) {
            $search = trim($request->search);
            if (!empty($search)) {
                $searchLower = strtolower($search);
                $query->where(function($q) use ($search, $searchLower) {
                    $q->whereRaw('LOWER(campus_name) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(campus_address) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(description) LIKE ?', ["%{$searchLower}%"]);
                });
            }
        }
        
        $perPage = $request->get('per_page', 10);
        $perPage = in_array($perPage, [10, 25, 50, 100]) ? $perPage : 10;
        
        $campuses = $query->orderBy('campus_name', 'asc')->paginate($perPage)->withQueryString();

        return view('campus.manage', compact('campuses'));
    }

    /**
     * Store a newly created campus.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'campus_name' => ['required', 'string', 'max:255'],
            'campus_address' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
        ]);

        Campus::create($validated);

        return redirect()
            ->route('manage.campuses')
            ->with('success', 'Campus created successfully!');
    }

    /**
     * Update the specified campus.
     */
    public function update(Request $request, Campus $campus): RedirectResponse
    {
        $validated = $request->validate([
            'campus_name' => ['required', 'string', 'max:255'],
            'campus_address' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
        ]);

        $campus->update($validated);

        return redirect()
            ->route('manage.campuses')
            ->with('success', 'Campus updated successfully!');
    }

    /**
     * Remove the specified campus.
     */
    public function destroy(Campus $campus): RedirectResponse
    {
        $campus->delete();

        return redirect()
            ->route('manage.campuses')
            ->with('success', 'Campus deleted successfully!');
    }

    /**
     * Export campuses to Excel, CSV, or PDF
     */
    public function export(Request $request, string $format)
    {
        $query = Campus::query();
        
        // Apply search filter if present
        if ($request->has('search') && $request->search) {
            $search = trim($request->search);
            if (!empty($search)) {
                $searchLower = strtolower($search);
                $query->where(function($q) use ($search, $searchLower) {
                    $q->whereRaw('LOWER(campus_name) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(campus_address) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(description) LIKE ?', ["%{$searchLower}%"]);
                });
            }
        }
        
        $campuses = $query->orderBy('campus_name', 'asc')->get();
        
        switch ($format) {
            case 'excel':
                return $this->exportExcel($campuses);
            case 'pdf':
                return $this->exportPDF($campuses);
            default:
                return redirect()->route('manage.campuses')
                    ->with('error', 'Invalid export format!');
        }
    }

    /**
     * Export to Excel (CSV format for Excel compatibility)
     */
    private function exportExcel($campuses)
    {
        $filename = 'campuses_' . date('Y-m-d_His') . '.csv';
        
        $headers = [
            'Content-Type' => 'application/vnd.ms-excel',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($campuses) {
            $file = fopen('php://output', 'w');
            
            // Add BOM for UTF-8
            fputs($file, "\xEF\xBB\xBF");
            
            // Headers
            fputcsv($file, ['#', 'Campus Name', 'Campus Address', 'Description']);
            
            // Data
            foreach ($campuses as $index => $campus) {
                fputcsv($file, [
                    $index + 1,
                    $campus->campus_name,
                    $campus->campus_address ?? 'N/A',
                    $campus->description ? (strlen($campus->description) > 50 ? substr($campus->description, 0, 50) . '...' : $campus->description) : 'N/A',
                ]);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export to PDF
     */
    private function exportPDF($campuses)
    {
        $html = view('campus.manage-pdf', compact('campuses'))->render();
        
        // Simple PDF generation (you can use DomPDF or similar package)
        return response($html)
            ->header('Content-Type', 'text/html');
    }
}
