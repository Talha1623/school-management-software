<?php

namespace App\Http\Controllers;

use App\Models\AdmissionInquiry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;

class AdmissionInquiryController extends Controller
{
    /**
     * Display a listing of the admission inquiries.
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
        
        $inquiries = $query->latest()->paginate($perPage)->withQueryString();
        
        return view('admission.inquiry.manage', compact('inquiries'));
    }

    /**
     * Store a newly created admission inquiry.
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
            ->route('admission.inquiry.manage')
            ->with('success', 'Inquiry added successfully!');
    }

    /**
     * Update the specified admission inquiry.
     */
    public function update(Request $request, AdmissionInquiry $inquiry): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'parent' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'gender' => ['required', 'in:male,female,other'],
            'birthday' => ['required', 'date'],
            'full_address' => ['required', 'string'],
        ]);

        $inquiry->update($validated);

        return redirect()
            ->route('admission.inquiry.manage')
            ->with('success', 'Inquiry updated successfully!');
    }

    /**
     * Remove the specified admission inquiry.
     */
    public function destroy(AdmissionInquiry $inquiry): RedirectResponse
    {
        $inquiry->delete();

        return redirect()
            ->route('admission.inquiry.manage')
            ->with('success', 'Inquiry deleted successfully!');
    }

    /**
     * Export inquiries to Excel, CSV, or PDF
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
        
        $inquiries = $query->latest()->get();
        
        switch ($format) {
            case 'excel':
                return $this->exportExcel($inquiries);
            case 'csv':
                return $this->exportCSV($inquiries);
            case 'pdf':
                return $this->exportPDF($inquiries);
            default:
                return redirect()->route('admission.inquiry.manage')
                    ->with('error', 'Invalid export format!');
        }
    }

    /**
     * Export to Excel (CSV format for Excel compatibility)
     */
    private function exportExcel($inquiries)
    {
        $filename = 'inquiries_' . date('Y-m-d_His') . '.csv';
        
        $headers = [
            'Content-Type' => 'application/vnd.ms-excel',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($inquiries) {
            $file = fopen('php://output', 'w');
            
            // Add BOM for Excel UTF-8 compatibility
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Add headers
            fputcsv($file, ['ID', 'Name', 'Parent', 'Phone', 'Gender', 'Birthday', 'Full Address', 'Created At']);
            
            // Add data rows
            foreach ($inquiries as $inquiry) {
                fputcsv($file, [
                    $inquiry->id,
                    $inquiry->name,
                    $inquiry->parent,
                    $inquiry->phone,
                    ucfirst($inquiry->gender),
                    $inquiry->birthday ? $inquiry->birthday->format('Y-m-d') : 'N/A',
                    $inquiry->full_address,
                    $inquiry->created_at->format('Y-m-d H:i:s'),
                ]);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export to CSV
     */
    private function exportCSV($inquiries)
    {
        $filename = 'inquiries_' . date('Y-m-d_His') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($inquiries) {
            $file = fopen('php://output', 'w');
            
            // Add headers
            fputcsv($file, ['ID', 'Name', 'Parent', 'Phone', 'Gender', 'Birthday', 'Full Address', 'Created At']);
            
            // Add data rows
            foreach ($inquiries as $inquiry) {
                fputcsv($file, [
                    $inquiry->id,
                    $inquiry->name,
                    $inquiry->parent,
                    $inquiry->phone,
                    ucfirst($inquiry->gender),
                    $inquiry->birthday ? $inquiry->birthday->format('Y-m-d') : 'N/A',
                    $inquiry->full_address,
                    $inquiry->created_at->format('Y-m-d H:i:s'),
                ]);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export to PDF
     */
    private function exportPDF($inquiries)
    {
        // Simple HTML to PDF conversion
        $html = view('admission.inquiry.pdf', compact('inquiries'))->render();
        
        // For now, return HTML that can be printed as PDF
        // You can integrate a PDF library like dompdf or snappy later
        return response($html)
            ->header('Content-Type', 'text/html');
    }
}

