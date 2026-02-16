<?php

namespace App\Http\Controllers;

use App\Models\SaleRecord;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SaleRecordController extends Controller
{
    /**
     * Display the sale records with filters.
     */
    public function index(Request $request): View
    {
        // Get filter values
        $filterMonth = $request->get('filter_month');
        $filterDate = $request->get('filter_date');
        $filterYear = $request->get('filter_year');
        $filterMethod = $request->get('filter_method');
        $search = $request->get('search');

        // Month options
        $months = collect([
            '01' => 'January',
            '02' => 'February',
            '03' => 'March',
            '04' => 'April',
            '05' => 'May',
            '06' => 'June',
            '07' => 'July',
            '08' => 'August',
            '09' => 'September',
            '10' => 'October',
            '11' => 'November',
            '12' => 'December',
        ]);

        // Year options (current year and previous 5 years)
        $currentYear = date('Y');
        $years = collect();
        for ($i = 0; $i < 6; $i++) {
            $years->push($currentYear - $i);
        }

        // Get payment methods from sale records
        $methods = SaleRecord::whereNotNull('method')->distinct()->pluck('method')->sort()->values();
        
        if ($methods->isEmpty()) {
            $methods = collect(['Cash', 'Bank Transfer', 'Cheque', 'Online Payment', 'Card']);
        }

        // Query sale records - show all by default, filter if provided
        $query = SaleRecord::with('product');

        // Apply search filter
        if ($search && !empty(trim($search))) {
            $searchLower = strtolower(trim($search));
            $query->where(function($q) use ($searchLower) {
                $q->whereRaw('LOWER(product_name) LIKE ?', ["%{$searchLower}%"])
                  ->orWhereRaw('LOWER(category) LIKE ?', ["%{$searchLower}%"])
                  ->orWhereRaw('LOWER(method) LIKE ?', ["%{$searchLower}%"])
                  ->orWhereRaw('LOWER(campus) LIKE ?', ["%{$searchLower}%"])
                  ->orWhereRaw('LOWER(received_by) LIKE ?', ["%{$searchLower}%"])
                  ->orWhereRaw('LOWER(notes) LIKE ?', ["%{$searchLower}%"]);
            });
        }

        if ($filterMonth) {
            $query->whereMonth('sale_date', $filterMonth);
        }
        if ($filterDate) {
            // Ensure date format is correct (YYYY-MM-DD)
            $query->whereDate('sale_date', $filterDate);
        }
        if ($filterYear) {
            $query->whereYear('sale_date', $filterYear);
        }
        if ($filterMethod) {
            $query->where('method', $filterMethod);
        }

        // Get all records (filtered or all)
        $saleRecords = $query->orderBy('sale_date', 'desc')->orderBy('created_at', 'desc')->get();
        
        // Calculate totals
        $totalSales = $saleRecords->sum('total_amount');
        $totalQuantity = $saleRecords->sum('quantity');
        
        // Debug info (for troubleshooting)
        $totalRecordsInDB = SaleRecord::count();
        $todayRecords = SaleRecord::whereDate('sale_date', now()->toDateString())->count();

        return view('stock.manage-sale-records', compact(
            'months',
            'years',
            'methods',
            'saleRecords',
            'filterMonth',
            'filterDate',
            'filterYear',
            'filterMethod',
            'search',
            'totalSales',
            'totalQuantity',
            'totalRecordsInDB',
            'todayRecords'
        ));
    }

    /**
     * Print thermal receipt for sale record.
     */
    public function print(SaleRecord $saleRecord): View
    {
        return view('stock.print-sale-receipt', compact('saleRecord'));
    }

    /**
     * Remove the specified sale record.
     */
    public function destroy(SaleRecord $saleRecord): RedirectResponse
    {
        $saleRecord->delete();

        return redirect()
            ->back()
            ->with('success', 'Sale record deleted successfully!');
    }

    /**
     * Export sale records to Excel, CSV, or PDF
     */
    public function export(Request $request, string $format)
    {
        $query = SaleRecord::with('product');
        
        // Apply search filter if present
        if ($request->has('search') && $request->search) {
            $search = trim($request->search);
            if (!empty($search)) {
                $searchLower = strtolower($search);
                $query->where(function($q) use ($searchLower) {
                    $q->whereRaw('LOWER(product_name) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(category) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(method) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(campus) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(received_by) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(notes) LIKE ?', ["%{$searchLower}%"]);
                });
            }
        }

        // Apply filters
        if ($request->has('filter_month') && $request->filter_month) {
            $query->whereMonth('sale_date', $request->filter_month);
        }
        if ($request->has('filter_date') && $request->filter_date) {
            $query->whereDate('sale_date', $request->filter_date);
        }
        if ($request->has('filter_year') && $request->filter_year) {
            $query->whereYear('sale_date', $request->filter_year);
        }
        if ($request->has('filter_method') && $request->filter_method) {
            $query->where('method', $request->filter_method);
        }
        
        $saleRecords = $query->orderBy('sale_date', 'desc')->orderBy('created_at', 'desc')->get();
        
        switch ($format) {
            case 'excel':
                return $this->exportExcel($saleRecords);
            case 'csv':
                return $this->exportCSV($saleRecords);
            case 'pdf':
                return $this->exportPDF($saleRecords);
            default:
                return redirect()->route('stock.manage-sale-records')
                    ->with('error', 'Invalid export format!');
        }
    }

    /**
     * Export to Excel (CSV format for Excel compatibility)
     */
    private function exportExcel($saleRecords)
    {
        $filename = 'sale_records_' . date('Y-m-d_His') . '.csv';
        
        $headers = [
            'Content-Type' => 'application/vnd.ms-excel',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($saleRecords) {
            $file = fopen('php://output', 'w');
            
            // Add BOM for UTF-8
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            fputcsv($file, ['#', 'Sale Date', 'Product Name', 'Category', 'Quantity', 'Unit Price', 'Total Amount', 'Payment Method', 'Campus', 'Received By', 'Notes', 'Created At']);
            
            foreach ($saleRecords as $index => $record) {
                fputcsv($file, [
                    $index + 1,
                    $record->sale_date ? date('d M Y', strtotime($record->sale_date)) : 'N/A',
                    $record->product_name,
                    $record->category,
                    $record->quantity,
                    number_format($record->unit_price, 2),
                    number_format($record->total_amount, 2),
                    $record->method,
                    $record->campus,
                    $record->received_by ?? 'N/A',
                    $record->notes ?? 'N/A',
                    $record->created_at->format('Y-m-d H:i:s'),
                ]);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export to CSV
     */
    private function exportCSV($saleRecords)
    {
        $filename = 'sale_records_' . date('Y-m-d_His') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($saleRecords) {
            $file = fopen('php://output', 'w');
            
            fputcsv($file, ['#', 'Sale Date', 'Product Name', 'Category', 'Quantity', 'Unit Price', 'Total Amount', 'Payment Method', 'Campus', 'Received By', 'Notes', 'Created At']);
            
            foreach ($saleRecords as $index => $record) {
                fputcsv($file, [
                    $index + 1,
                    $record->sale_date ? date('d M Y', strtotime($record->sale_date)) : 'N/A',
                    $record->product_name,
                    $record->category,
                    $record->quantity,
                    number_format($record->unit_price, 2),
                    number_format($record->total_amount, 2),
                    $record->method,
                    $record->campus,
                    $record->received_by ?? 'N/A',
                    $record->notes ?? 'N/A',
                    $record->created_at->format('Y-m-d H:i:s'),
                ]);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export to PDF
     */
    private function exportPDF($saleRecords)
    {
        $totalSales = $saleRecords->sum('total_amount');
        $totalQuantity = $saleRecords->sum('quantity');
        
        $html = view('stock.manage-sale-records-pdf', compact('saleRecords', 'totalSales', 'totalQuantity'))->render();
        
        return response($html)
            ->header('Content-Type', 'text/html');
    }
}

