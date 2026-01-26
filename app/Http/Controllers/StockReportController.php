<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\SaleRecord;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StockReportController extends Controller
{
    private const LOW_STOCK_THRESHOLD = 5;

    public function index(Request $request): View
    {
        $today = Carbon::today();
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        $totalProducts = Product::count();
        $outOfStockProducts = Product::where('total_stock', '<=', 0)->count();
        $salesToday = SaleRecord::whereDate('sale_date', $today)->sum('total_amount');
        $salesThisMonth = SaleRecord::whereBetween('sale_date', [$startOfMonth, $endOfMonth])->sum('total_amount');

        return view('stock.sale-reports', compact(
            'totalProducts',
            'outOfStockProducts',
            'salesToday',
            'salesThisMonth'
        ));
    }

    public function printReport(string $reportType): View|RedirectResponse
    {
        $today = Carbon::today();
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        $viewData = [
            'reportType' => $reportType,
            'generatedAt' => Carbon::now(),
            'lowStockThreshold' => self::LOW_STOCK_THRESHOLD,
        ];

        switch ($reportType) {
            case 'total-products':
                $viewData['products'] = Product::orderBy('product_name')->get();
                break;
            case 'out-of-stock':
                $viewData['products'] = Product::where('total_stock', '<=', 0)
                    ->orderBy('product_name')
                    ->get();
                break;
            case 'low-stock':
                $viewData['products'] = Product::where('total_stock', '<=', self::LOW_STOCK_THRESHOLD)
                    ->orderBy('total_stock')
                    ->orderBy('product_name')
                    ->get();
                break;
            case 'monthly-sales-profit':
                $viewData['saleRecords'] = SaleRecord::with('product')
                    ->whereBetween('sale_date', [$startOfMonth, $endOfMonth])
                    ->orderBy('sale_date', 'asc')
                    ->get();
                $viewData['salesTotal'] = $viewData['saleRecords']->sum('total_amount');
                $viewData['profitTotal'] = $viewData['saleRecords']->sum(function ($record) {
                    $purchasePrice = $record->product?->purchase_price ?? 0;
                    return ($record->unit_price - $purchasePrice) * $record->quantity;
                });
                $viewData['monthLabel'] = $startOfMonth->format('F Y');
                break;
            default:
                return redirect()
                    ->route('stock.sale-reports')
                    ->with('error', 'Invalid report type.');
        }

        return view('stock.sale-reports-print', $viewData);
    }
}
