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
            'totalSales',
            'totalQuantity',
            'totalRecordsInDB',
            'todayRecords'
        ));
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
}

