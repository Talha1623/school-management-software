@extends('layouts.app')

@section('title', 'Stock and Sale Reports')

@section('content')
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="card border-0 rounded-10 text-white" style="background: #e14c3c;">
            <div class="card-body pb-2">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h3 class="mb-1">{{ $totalProducts ?? 0 }}</h3>
                        <p class="mb-0">Total Products</p>
                    </div>
                    <span class="material-symbols-outlined" style="font-size: 40px; opacity: 0.25;">inventory_2</span>
                </div>
            </div>
            <div class="card-footer border-0 bg-transparent pt-0">
                <a href="#printableReports" class="text-white text-decoration-none">View Report</a>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card border-0 rounded-10 text-white" style="background: #f39b12;">
            <div class="card-body pb-2">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h3 class="mb-1">{{ $outOfStockProducts ?? 0 }}</h3>
                        <p class="mb-0">Out Of Stock Products</p>
                    </div>
                    <span class="material-symbols-outlined" style="font-size: 40px; opacity: 0.25;">trending_down</span>
                </div>
            </div>
            <div class="card-footer border-0 bg-transparent pt-0">
                <a href="#printableReports" class="text-white text-decoration-none">View Report</a>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card border-0 rounded-10 text-white" style="background: #0b72b9;">
            <div class="card-body pb-2">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h3 class="mb-1">{{ number_format($salesToday ?? 0, 0) }}</h3>
                        <p class="mb-0">Sales Today</p>
                    </div>
                    <span class="material-symbols-outlined" style="font-size: 40px; opacity: 0.25;">north</span>
                </div>
            </div>
            <div class="card-footer border-0 bg-transparent pt-0">
                <a href="#printableReports" class="text-white text-decoration-none">View Report</a>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card border-0 rounded-10 text-white" style="background: #8c8c8c;">
            <div class="card-body pb-2">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h3 class="mb-1">{{ number_format($salesThisMonth ?? 0, 0) }}</h3>
                        <p class="mb-0">Sales This Month</p>
                    </div>
                    <span class="material-symbols-outlined" style="font-size: 40px; opacity: 0.25;">south</span>
                </div>
            </div>
            <div class="card-footer border-0 bg-transparent pt-0">
                <a href="#printableReports" class="text-white text-decoration-none">View Report</a>
            </div>
        </div>
    </div>
</div>

<div class="row" id="printableReports">
    <div class="col-12">
        <div class="card border border-white rounded-10 mb-4">
            <div class="card-header border-0" style="background: #daf0dc;">
                <h5 class="mb-0">Printable Stock & Inventory Reports</h5>
            </div>
            <div class="card-body">
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1">Total Products Report</h6>
                                <small class="text-muted">List of all products added.</small>
                            </div>
                            <a href="{{ route('stock.sale-reports.print', 'total-products') }}" class="btn btn-outline-primary btn-sm" target="_blank">
                                <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">print</span>
                                Print
                            </a>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1">Out Of Stock Products Report</h6>
                                <small class="text-muted">List of total out of stock products.</small>
                            </div>
                            <a href="{{ route('stock.sale-reports.print', 'out-of-stock') }}" class="btn btn-outline-primary btn-sm" target="_blank">
                                <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">print</span>
                                Print
                            </a>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1">Monthly Sales & Profit Report</h6>
                                <small class="text-muted">Current month sales and profit report.</small>
                            </div>
                            <a href="{{ route('stock.sale-reports.print', 'monthly-sales-profit') }}" class="btn btn-outline-primary btn-sm" target="_blank">
                                <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">print</span>
                                Print
                            </a>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1">Low Stock Products Report</h6>
                                <small class="text-muted">Products with low stock quantity.</small>
                            </div>
                            <a href="{{ route('stock.sale-reports.print', 'low-stock') }}" class="btn btn-outline-primary btn-sm" target="_blank">
                                <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">print</span>
                                Print
                            </a>
                        </div>
                    </div>
                </div>
                <div class="mt-4">
                    <small class="text-muted">Note: Please ensure that all reports are printed in A4 size for optimal viewing.</small>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

