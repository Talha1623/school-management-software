<?php

namespace App\Http\Controllers;

use App\Models\ParentAccountRequest;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ParentAccountRequestController extends Controller
{
    /**
     * Display a listing of parent account requests.
     */
    public function index(Request $request): View
    {
        $query = ParentAccountRequest::query();
        
        // Search functionality
        if ($request->filled('search')) {
            $search = trim($request->search);
            if (!empty($search)) {
                $searchLower = strtolower($search);
                $query->where(function($q) use ($search, $searchLower) {
                    $q->whereRaw('LOWER(request_by) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(email) LIKE ?', ["%{$searchLower}%"])
                      ->orWhere('phone', 'like', "%{$search}%")
                      ->orWhere('id_card', 'like', "%{$search}%")
                      ->orWhere('parent_id', 'like', "%{$search}%")
                      ->orWhereRaw('LOWER(request_status) LIKE ?', ["%{$searchLower}%"]);
                });
            }
        }
        
        $perPage = $request->get('per_page', 10);
        $perPage = in_array($perPage, [10, 25, 50, 100]) ? $perPage : 10;
        
        $requests = $query->latest()->paginate($perPage)->withQueryString();
        
        return view('parent.account-request', compact('requests'));
    }

    /**
     * Export parent account requests to Excel, CSV, or PDF
     */
    public function export(Request $request, string $format)
    {
        $query = ParentAccountRequest::query();
        
        // Apply search filter if present
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('parent_id', 'like', "%{$search}%")
                  ->orWhere('request_by', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('id_card', 'like', "%{$search}%")
                  ->orWhere('request_status', 'like', "%{$search}%");
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
                return redirect()->route('parent.account-request')
                    ->with('error', 'Invalid export format!');
        }
    }

    /**
     * Export to Excel (CSV format for Excel compatibility)
     */
    private function exportExcel($requests)
    {
        $filename = 'parent_account_requests_' . date('Y-m-d_His') . '.csv';
        
        $headers = [
            'Content-Type' => 'application/vnd.ms-excel',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($requests) {
            $file = fopen('php://output', 'w');
            
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            fputcsv($file, ['Parent ID', 'Request By', 'Email', 'Phone', 'ID Card', 'Request Status', 'Created At']);
            
            foreach ($requests as $request) {
                fputcsv($file, [
                    $request->parent_id ?? 'N/A',
                    $request->request_by ?? 'N/A',
                    $request->email ?? 'N/A',
                    $request->phone ?? 'N/A',
                    $request->id_card ?? 'N/A',
                    $request->request_status ?? 'N/A',
                    $request->created_at->format('Y-m-d H:i:s'),
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
        $filename = 'parent_account_requests_' . date('Y-m-d_His') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($requests) {
            $file = fopen('php://output', 'w');
            
            fputcsv($file, ['Parent ID', 'Request By', 'Email', 'Phone', 'ID Card', 'Request Status', 'Created At']);
            
            foreach ($requests as $request) {
                fputcsv($file, [
                    $request->parent_id ?? 'N/A',
                    $request->request_by ?? 'N/A',
                    $request->email ?? 'N/A',
                    $request->phone ?? 'N/A',
                    $request->id_card ?? 'N/A',
                    $request->request_status ?? 'N/A',
                    $request->created_at->format('Y-m-d H:i:s'),
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
        $html = view('parent.account-request-pdf', compact('requests'))->render();
        
        return response($html)
            ->header('Content-Type', 'text/html');
    }
}

