<?php

namespace App\Http\Controllers;

use App\Models\Transport;
use App\Models\Campus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TransportController extends Controller
{
    /**
     * Display a listing of transports.
     */
    public function index(Request $request): View
    {
        $query = Transport::query();
        
        // Search functionality
        if ($request->filled('search')) {
            $search = trim($request->search);
            if (!empty($search)) {
                $searchLower = strtolower($search);
                $query->where(function($q) use ($search, $searchLower) {
                    $q->whereRaw('LOWER(campus) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(route_name) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(description) LIKE ?', ["%{$searchLower}%"]);
                });
            }
        }
        
        $perPage = $request->get('per_page', 10);
        $perPage = in_array($perPage, [10, 25, 50, 100]) ? $perPage : 10;
        
        $transports = $query->orderBy('route_name', 'asc')->paginate($perPage)->withQueryString();

        // Get campuses for dropdown
        $campuses = Campus::orderBy('campus_name', 'asc')->get();

        return view('transport.manage', compact('transports', 'campuses'));
    }

    /**
     * Store a newly created transport.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'campus' => ['nullable', 'string', 'max:255'],
            'route_name' => ['required', 'string', 'max:255'],
            'number_of_vehicle' => ['required', 'integer', 'min:0'],
            'description' => ['nullable', 'string'],
            'route_fare' => ['required', 'numeric', 'min:0'],
        ]);

        Transport::create($validated);

        return redirect()
            ->route('transport.manage')
            ->with('success', 'Transport route created successfully!');
    }

    /**
     * Update the specified transport.
     */
    public function update(Request $request, Transport $transport): RedirectResponse
    {
        $validated = $request->validate([
            'campus' => ['nullable', 'string', 'max:255'],
            'route_name' => ['required', 'string', 'max:255'],
            'number_of_vehicle' => ['required', 'integer', 'min:0'],
            'description' => ['nullable', 'string'],
            'route_fare' => ['required', 'numeric', 'min:0'],
        ]);

        $transport->update($validated);

        return redirect()
            ->route('transport.manage')
            ->with('success', 'Transport route updated successfully!');
    }

    /**
     * Remove the specified transport.
     */
    public function destroy(Transport $transport): RedirectResponse
    {
        $transport->delete();

        return redirect()
            ->route('transport.manage')
            ->with('success', 'Transport route deleted successfully!');
    }

    /**
     * Export transports to Excel, CSV, or PDF
     */
    public function export(Request $request, string $format)
    {
        $query = Transport::query();
        
        // Apply search filter if present
        if ($request->has('search') && $request->search) {
            $search = trim($request->search);
            if (!empty($search)) {
                $searchLower = strtolower($search);
                $query->where(function($q) use ($search, $searchLower) {
                    $q->whereRaw('LOWER(campus) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(route_name) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(description) LIKE ?', ["%{$searchLower}%"]);
                });
            }
        }
        
        $transports = $query->orderBy('route_name', 'asc')->get();
        
        switch ($format) {
            case 'excel':
                return $this->exportExcel($transports);
            case 'pdf':
                return $this->exportPDF($transports);
            default:
                return redirect()->route('transport.manage')
                    ->with('error', 'Invalid export format!');
        }
    }

    /**
     * Export to Excel (CSV format for Excel compatibility)
     */
    private function exportExcel($transports)
    {
        $filename = 'transports_' . date('Y-m-d_His') . '.csv';
        
        $headers = [
            'Content-Type' => 'application/vnd.ms-excel',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($transports) {
            $file = fopen('php://output', 'w');
            
            // Add BOM for UTF-8
            fputs($file, "\xEF\xBB\xBF");
            
            // Headers
            fputcsv($file, ['#', 'Campus', 'Route Name', 'Number Of Vehicle', 'Description', 'Route Fare']);
            
            // Data
            foreach ($transports as $index => $transport) {
                fputcsv($file, [
                    $index + 1,
                    $transport->campus ?? 'N/A',
                    $transport->route_name,
                    $transport->number_of_vehicle,
                    $transport->description ? (strlen($transport->description) > 50 ? substr($transport->description, 0, 50) . '...' : $transport->description) : 'N/A',
                    number_format($transport->route_fare, 2),
                ]);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export to PDF
     */
    private function exportPDF($transports)
    {
        $html = view('transport.manage-pdf', compact('transports'))->render();
        
        // Simple PDF generation (you can use DomPDF or similar package)
        return response($html)
            ->header('Content-Type', 'text/html');
    }
}
