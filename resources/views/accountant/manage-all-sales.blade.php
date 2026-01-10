@extends('layouts.accountant')

@section('title', 'Manage All Sales - Accountant')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-3 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0 fs-16 fw-semibold">Manage All Sales</h4>
            </div>

            <!-- Filter Form -->
            <form action="{{ route('accountant.manage-all-sales') }}" method="GET" id="filterForm">
                <div class="p-3 rounded-8 mb-3" style="background-color: #f8f9fa; border: 1px solid #e9ecef;">
                    <div class="row g-2 align-items-end">
                        <!-- Month -->
                        <div class="col-md-2 col-sm-6">
                            <label for="filter_month" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Month</label>
                            <select class="form-select form-select-sm filter-select" id="filter_month" name="filter_month">
                                <option value="">All Months</option>
                                @foreach($months as $monthValue => $monthName)
                                    <option value="{{ $monthValue }}" {{ $filterMonth == $monthValue ? 'selected' : '' }}>{{ $monthName }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Date -->
                        <div class="col-md-2 col-sm-6">
                            <label for="filter_date" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Date</label>
                            <input type="date" class="form-control form-control-sm filter-input" id="filter_date" name="filter_date" value="{{ $filterDate }}">
                        </div>

                        <!-- Year -->
                        <div class="col-md-2 col-sm-6">
                            <label for="filter_year" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Year</label>
                            <select class="form-select form-select-sm filter-select" id="filter_year" name="filter_year">
                                <option value="">All Years</option>
                                @foreach($years as $year)
                                    <option value="{{ $year }}" {{ $filterYear == $year ? 'selected' : '' }}>{{ $year }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Method -->
                        <div class="col-md-2 col-sm-6">
                            <label for="filter_method" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Method</label>
                            <select class="form-select form-select-sm filter-select" id="filter_method" name="filter_method">
                                <option value="">All Methods</option>
                                @foreach($methods as $method)
                                    <option value="{{ $method }}" {{ $filterMethod == $method ? 'selected' : '' }}>{{ $method }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Filter Button -->
                        <div class="col-md-2 col-sm-6">
                            <button type="submit" class="btn btn-sm py-1 px-3 rounded-8 filter-btn w-100 d-inline-flex align-items-center justify-content-center gap-1">
                                <span class="material-symbols-outlined" style="font-size: 14px;">filter_alt</span>
                                <span style="font-size: 12px; white-space: nowrap;">Filter</span>
                            </button>
                        </div>
                        
                        <!-- Clear Button -->
                        <div class="col-md-2 col-sm-6">
                            <a href="{{ route('accountant.manage-all-sales') }}" class="btn btn-sm py-1 px-3 rounded-8 clear-btn w-100 d-inline-flex align-items-center justify-content-center gap-1">
                                <span class="material-symbols-outlined" style="font-size: 14px;">close</span>
                                <span style="font-size: 12px; white-space: nowrap;">Clear</span>
                            </a>
                        </div>
                    </div>
                </div>
            </form>

            <!-- Results Table -->
            <div class="mt-3">
                <div class="mb-2 p-2 rounded-8" style="background: linear-gradient(135deg, #003471 0%, #004a9f 100%);">
                    <h5 class="mb-0 text-white fs-15 fw-semibold d-flex align-items-center gap-2">
                        <span class="material-symbols-outlined" style="font-size: 18px;">list</span>
                        <span>Sale Records 
                            @if(request()->hasAny(['filter_month', 'filter_date', 'filter_year', 'filter_method']))
                                <small style="font-size: 12px; opacity: 0.9;">(Filtered Results - {{ $saleRecords->count() }} found)</small>
                            @else
                                <small style="font-size: 12px; opacity: 0.9;">(All Records - {{ $saleRecords->count() }} total)</small>
                            @endif
                        </span>
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
                                    <th>Payment Method</th>
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
                                    <td><span class="badge bg-secondary text-white">{{ $record->quantity }}</span></td>
                                    <td>PKR {{ number_format($record->unit_price, 2) }}</td>
                                    <td class="fw-semibold text-success">PKR {{ number_format($record->total_amount, 2) }}</td>
                                    <td><span class="badge bg-info text-white">{{ $record->method }}</span></td>
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
                                            <p class="text-muted mt-2 mb-0">
                                                @if(request()->hasAny(['filter_month', 'filter_date', 'filter_year', 'filter_method']))
                                                    No sale records found for the selected filters.
                                                    @if($filterDate)
                                                        <br><small>Selected Date: {{ date('d M Y', strtotime($filterDate)) }}</small>
                                                        <br><small>Today's Date: {{ date('d M Y') }}</small>
                                                    @endif
                                                @else
                                                    No sale records found. Complete a sale from Point of Sale page to see records here.
                                                @endif
                                            </p>
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                            @if($saleRecords->count() > 0)
                            <tfoot>
                                <tr class="fw-bold" style="background-color: #f8f9fa; border-top: 2px solid #dee2e6;">
                                    <td colspan="6" class="text-end" style="font-size: 14px;">Total Sales:</td>
                                    <td class="text-success" style="font-size: 14px;">PKR {{ number_format($totalSales, 2) }}</td>
                                    <td colspan="3"></td>
                                </tr>
                                <tr class="fw-bold" style="background-color: #f8f9fa;">
                                    <td colspan="6" class="text-end" style="font-size: 14px;">Total Quantity:</td>
                                    <td style="font-size: 14px;">{{ number_format($totalQuantity, 0) }}</td>
                                    <td colspan="3"></td>
                                </tr>
                                <tr class="fw-bold" style="background-color: #e7f3ff; border-top: 2px solid #003471;">
                                    <td colspan="6" class="text-end" style="font-size: 14px; color: #003471;">Total Records:</td>
                                    <td style="font-size: 14px; color: #003471;">{{ $saleRecords->count() }}</td>
                                    <td colspan="3"></td>
                                </tr>
                            </tfoot>
                            @endif
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.filter-select,
.filter-input {
    height: 32px;
    font-size: 13px;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    transition: all 0.3s ease;
}

.filter-select:focus,
.filter-input:focus {
    border-color: #003471;
    box-shadow: 0 0 0 3px rgba(0, 52, 113, 0.15);
    outline: none;
}

.filter-btn {
    background: linear-gradient(135deg, #003471 0%, #004a9f 100%);
    color: white;
    border: none;
    transition: all 0.3s ease;
    height: 32px;
    font-weight: 500;
}

.filter-btn:hover {
    background: linear-gradient(135deg, #004a9f 0%, #003471 100%);
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0, 52, 113, 0.3);
    color: white;
}

.clear-btn {
    background-color: #6c757d;
    color: white;
    border: none;
    text-decoration: none;
    transition: all 0.3s ease;
    height: 32px;
    font-weight: 500;
}

.clear-btn:hover {
    background-color: #5a6268;
    color: white;
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(108, 117, 125, 0.3);
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    const dateInput = document.getElementById('filter_date');
    const monthSelect = document.getElementById('filter_month');
    const yearSelect = document.getElementById('filter_year');
    
    if (dateInput) {
        dateInput.addEventListener('change', function() {
            const selectedDate = this.value;
            
            if (selectedDate) {
                // Parse the date
                const date = new Date(selectedDate);
                const month = String(date.getMonth() + 1).padStart(2, '0'); // Get month (01-12)
                const year = date.getFullYear(); // Get year
                
                // Set month dropdown
                if (monthSelect) {
                    monthSelect.value = month;
                }
                
                // Set year dropdown
                if (yearSelect) {
                    yearSelect.value = year;
                }
            }
        });
    }
});
</script>
@endsection

