@extends('layouts.app')

@section('title', 'Manage Sale Records')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-3 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0 fs-16 fw-semibold">Manage Sale Records</h4>
            </div>

            <!-- Filter Form -->
            <form action="{{ route('stock.manage-sale-records') }}" method="GET" id="filterForm">
                <div class="row g-2 mb-3 align-items-end">
                    <!-- Month -->
                    <div class="col-md-2">
                        <label for="filter_month" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Month</label>
                        <select class="form-select form-select-sm" id="filter_month" name="filter_month" style="height: 32px;">
                            <option value="">All Months</option>
                            @foreach($months as $monthValue => $monthName)
                                <option value="{{ $monthValue }}" {{ $filterMonth == $monthValue ? 'selected' : '' }}>{{ $monthName }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Date -->
                    <div class="col-md-2">
                        <label for="filter_date" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Date</label>
                        <input type="date" class="form-control form-control-sm" id="filter_date" name="filter_date" value="{{ $filterDate }}" style="height: 32px;">
                    </div>

                    <!-- Year -->
                    <div class="col-md-2">
                        <label for="filter_year" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Year</label>
                        <select class="form-select form-select-sm" id="filter_year" name="filter_year" style="height: 32px;">
                            <option value="">All Years</option>
                            @foreach($years as $year)
                                <option value="{{ $year }}" {{ $filterYear == $year ? 'selected' : '' }}>{{ $year }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Method -->
                    <div class="col-md-2">
                        <label for="filter_method" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Method</label>
                        <select class="form-select form-select-sm" id="filter_method" name="filter_method" style="height: 32px;">
                            <option value="">All Methods</option>
                            @foreach($methods as $method)
                                <option value="{{ $method }}" {{ $filterMethod == $method ? 'selected' : '' }}>{{ $method }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Filter Button -->
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-sm py-1 px-3 rounded-8 filter-btn w-100" style="height: 32px;">
                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">filter_alt</span>
                            <span style="font-size: 12px;">Filter</span>
                        </button>
                    </div>
                </div>
            </form>

            <!-- Results Table -->
            @if(request()->hasAny(['filter_month', 'filter_date', 'filter_year', 'filter_method']))
            <div class="mt-3">
                <div class="mb-2 p-2 rounded-8" style="background: linear-gradient(135deg, #003471 0%, #004a9f 100%);">
                    <h5 class="mb-0 text-white fs-15 fw-semibold d-flex align-items-center gap-2">
                        <span class="material-symbols-outlined" style="font-size: 18px;">list</span>
                        <span>Sale Records</span>
                    </h5>
                </div>

                <div class="default-table-area" style="margin-top: 0;">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Sale Date</th>
                                    <th>Product Name</th>
                                    <th>Category</th>
                                    <th>Quantity</th>
                                    <th>Unit Price</th>
                                    <th>Total Amount</th>
                                    <th>Method</th>
                                    <th>Campus</th>
                                    <th>Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($saleRecords as $index => $record)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ date('d M Y', strtotime($record->sale_date)) }}</td>
                                    <td>
                                        <strong class="text-primary">{{ $record->product_name }}</strong>
                                    </td>
                                    <td>
                                        <span class="badge bg-warning text-dark">{{ $record->category }}</span>
                                    </td>
                                    <td>{{ $record->quantity }}</td>
                                    <td>{{ number_format($record->unit_price, 2) }}</td>
                                    <td class="fw-semibold text-success">{{ number_format($record->total_amount, 2) }}</td>
                                    <td>{{ $record->method }}</td>
                                    <td>
                                        <span class="badge bg-info text-white">{{ $record->campus }}</span>
                                    </td>
                                    <td>{{ $record->notes ? (strlen($record->notes) > 30 ? substr($record->notes, 0, 30) . '...' : $record->notes) : 'N/A' }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="10" class="text-center py-4">
                                        <div class="d-flex flex-column align-items-center">
                                            <span class="material-symbols-outlined text-muted" style="font-size: 48px;">inbox</span>
                                            <p class="text-muted mt-2 mb-0">No sale records found</p>
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                            @if($saleRecords->count() > 0)
                            <tfoot>
                                <tr class="fw-bold" style="background-color: #f8f9fa;">
                                    <td colspan="6" class="text-end">Total Sales:</td>
                                    <td class="text-success">{{ number_format($saleRecords->sum('total_amount'), 2) }}</td>
                                    <td colspan="3"></td>
                                </tr>
                                <tr class="fw-bold" style="background-color: #f8f9fa;">
                                    <td colspan="6" class="text-end">Total Quantity:</td>
                                    <td>{{ $saleRecords->sum('quantity') }}</td>
                                    <td colspan="3"></td>
                                </tr>
                            </tfoot>
                            @endif
                        </table>
                    </div>
                </div>
            </div>
            @else
            <div class="text-center py-5">
                <span class="material-symbols-outlined text-muted" style="font-size: 64px;">filter_alt</span>
                <p class="text-muted mt-3 mb-0">Please apply filters to view sale records</p>
            </div>
            @endif
        </div>
    </div>
</div>

<style>
.filter-btn {
    background: linear-gradient(135deg, #003471 0%, #004a9f 100%);
    color: white;
    border: none;
    transition: all 0.3s ease;
}

.filter-btn:hover {
    background: linear-gradient(135deg, #004a9f 0%, #003471 100%);
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0, 52, 113, 0.3);
}

.default-table-area {
    background: #fff;
    border-radius: 8px;
    overflow: hidden;
}

.default-table-area table {
    margin-bottom: 0;
}

.default-table-area thead {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
}

.default-table-area thead th {
    font-weight: 600;
    font-size: 13px;
    color: #003471;
    border-bottom: 2px solid #dee2e6;
    padding: 12px;
}

.default-table-area tbody td {
    font-size: 13px;
    padding: 12px;
    vertical-align: middle;
}

.default-table-area tbody tr:hover {
    background-color: #f8f9fa;
}

.badge {
    font-size: 11px;
    padding: 4px 8px;
}
</style>
@endsection
