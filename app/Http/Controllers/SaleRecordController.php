<?php

namespace App\Http\Controllers;

use App\Models\SaleRecord;
use App\Models\Product;
use Illuminate\Http\Request;
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

        // Query sale records
        $query = SaleRecord::with('product');

        if ($filterMonth) {
            $query->whereMonth('sale_date', $filterMonth);
        }
        if ($filterDate) {
            $query->whereDate('sale_date', $filterDate);
        }
        if ($filterYear) {
            $query->whereYear('sale_date', $filterYear);
        }
        if ($filterMethod) {
            $query->where('method', $filterMethod);
        }

        $saleRecords = $query->orderBy('sale_date', 'desc')->get();

        return view('stock.manage-sale-records', compact(
            'months',
            'years',
            'methods',
            'saleRecords',
            'filterMonth',
            'filterDate',
            'filterYear',
            'filterMethod'
        ));
    }
}

