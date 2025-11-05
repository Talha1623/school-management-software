<?php

namespace App\Http\Controllers;

use App\Models\AdmissionInquiry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;

class AdmissionRequestController extends Controller
{
    /**
     * Display a listing of the admission requests.
     */
    public function index(Request $request): View
    {
        $query = AdmissionInquiry::query();
        
        // Search functionality - case insensitive and trim whitespace
        if ($request->filled('search')) {
            $search = trim($request->search);
            if (!empty($search)) {
                $searchLower = strtolower($search);
                $query->where(function($q) use ($search, $searchLower) {
                    $q->whereRaw('LOWER(name) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(parent) LIKE ?', ["%{$searchLower}%"])
                      ->orWhere('phone', 'like', "%{$search}%")
                      ->orWhereRaw('LOWER(full_address) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(gender) LIKE ?', ["%{$searchLower}%"]);
                });
            }
        }
        
        $perPage = $request->get('per_page', 10);
        // Validate per_page to prevent invalid values
        $perPage = in_array($perPage, [10, 25, 50, 100]) ? $perPage : 10;
        
        $requests = $query->latest()->paginate($perPage)->withQueryString();
        
        return view('admission.request', compact('requests'));
    }

    /**
     * Store a newly created admission request.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'parent' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'gender' => ['required', 'in:male,female,other'],
            'birthday' => ['required', 'date'],
            'full_address' => ['required', 'string'],
        ]);

        AdmissionInquiry::create($validated);

        return redirect()
            ->route('admission.request')
            ->with('success', 'Request added successfully!');
    }

    /**
     * Update the specified admission request.
     */
    public function update(Request $request, AdmissionInquiry $admission_request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'parent' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'gender' => ['required', 'in:male,female,other'],
            'birthday' => ['required', 'date'],
            'full_address' => ['required', 'string'],
        ]);

        $admission_request->update($validated);

        return redirect()
            ->route('admission.request')
            ->with('success', 'Request updated successfully!');
    }

    /**
     * Remove the specified admission request.
     */
    public function destroy(AdmissionInquiry $admission_request): RedirectResponse
    {
        $admission_request->delete();

        return redirect()
            ->route('admission.request')
            ->with('success', 'Request deleted successfully!');
    }

    /**
     * Export requests to Excel, CSV, or PDF
     */
    public function export(Request $request, string $format)
    {
        $query = AdmissionInquiry::query();
        
        // Apply search filter if present
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('parent', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('full_address', 'like', "%{$search}%")
                  ->orWhere('gender', 'like', "%{$search}%");
            });
        }
        
        $requests = $query->latest()->get();
        
        switch ($format) {
            case 'excel':
                return $this->exportExcel($requests);
            case 'csv':
                return $this->exportCSV($requests);
            case 'pdf':
                return $this->exportPDF($requests);
            default:
                return redirect()->route('admission.request')
                    ->with('error', 'Invalid export format!');
        }
    }

    /**
     * Export to Excel (CSV format for Excel compatibility)
     */
    private function exportExcel($requests)
    {
        $filename = 'admission_requests_' . date('Y-m-d_His') . '.csv';
        
        $headers = [
            'Content-Type' => 'application/vnd.ms-excel',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($requests) {
            $file = fopen('php://output', 'w');
            
            // Add BOM for Excel UTF-8 compatibility
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Add headers
            fputcsv($file, ['ID', 'Name', 'Parent', 'Phone', 'Gender', 'Birthday', 'Full Address', 'Created At']);
            
            // Add data rows
            foreach ($requests as $req) {
                fputcsv($file, [
                    $req->id,
                    $req->name,
                    $req->parent,
                    $req->phone,
                    ucfirst($req->gender),
                    $req->birthday ? $req->birthday->format('Y-m-d') : 'N/A',
                    $req->full_address,
                    $req->created_at->format('Y-m-d H:i:s'),
                ]);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export to CSV
     */
    private function exportCSV($requests)
    {
        $filename = 'admission_requests_' . date('Y-m-d_His') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($requests) {
            $file = fopen('php://output', 'w');
            
            // Add headers
            fputcsv($file, ['ID', 'Name', 'Parent', 'Phone', 'Gender', 'Birthday', 'Full Address', 'Created At']);
            
            // Add data rows
            foreach ($requests as $req) {
                fputcsv($file, [
                    $req->id,
                    $req->name,
                    $req->parent,
                    $req->phone,
                    ucfirst($req->gender),
                    $req->birthday ? $req->birthday->format('Y-m-d') : 'N/A',
                    $req->full_address,
                    $req->created_at->format('Y-m-d H:i:s'),
                ]);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export to PDF
     */
    private function exportPDF($requests)
    {
        // Simple HTML to PDF conversion
        $html = view('admission.request-pdf', compact('requests'))->render();
        
        // For now, return HTML that can be printed as PDF
        // You can integrate a PDF library like dompdf or snappy later
        return response($html)
            ->header('Content-Type', 'text/html');
    }
}

