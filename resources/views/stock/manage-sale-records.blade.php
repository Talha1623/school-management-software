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
            <form action="{{ route('stock.manage-sale-records') }}" method="GET" id="filterForm" class="mb-3">
                @if(request('search'))
                    <input type="hidden" name="search" value="{{ request('search') }}">
                @endif
                <div class="row g-2 align-items-end">
                    <!-- Month -->
                    <div class="col-md-2">
                        <label for="filter_month" class="form-label mb-0 fs-13 fw-medium">Month</label>
                        <select class="form-select form-select-sm" id="filter_month" name="filter_month" style="height: 32px;">
                            <option value="">All Months</option>
                            @foreach($months as $monthValue => $monthName)
                                <option value="{{ $monthValue }}" {{ $filterMonth == $monthValue ? 'selected' : '' }}>{{ $monthName }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Date -->
                    <div class="col-md-2">
                        <label for="filter_date" class="form-label mb-0 fs-13 fw-medium">Date</label>
                        <input type="date" class="form-control form-control-sm" id="filter_date" name="filter_date" value="{{ $filterDate }}" style="height: 32px;">
                    </div>

                    <!-- Year -->
                    <div class="col-md-2">
                        <label for="filter_year" class="form-label mb-0 fs-13 fw-medium">Year</label>
                        <select class="form-select form-select-sm" id="filter_year" name="filter_year" style="height: 32px;">
                            <option value="">All Years</option>
                            @foreach($years as $year)
                                <option value="{{ $year }}" {{ $filterYear == $year ? 'selected' : '' }}>{{ $year }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Method -->
                    <div class="col-md-2">
                        <label for="filter_method" class="form-label mb-0 fs-13 fw-medium">Method</label>
                        <select class="form-select form-select-sm" id="filter_method" name="filter_method" style="height: 32px;">
                            <option value="">All Methods</option>
                            @foreach($methods as $method)
                                <option value="{{ $method }}" {{ $filterMethod == $method ? 'selected' : '' }}>{{ $method }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Filter Button -->
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-sm w-100" style="background: linear-gradient(135deg, #003471 0%, #004a9f 100%); color: white; height: 32px; border: none;">
                            <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">filter_list</span>
                            Filter
                        </button>
                    </div>
                    
                    <!-- Clear Button -->
                    @if(request('filter_month') || request('filter_date') || request('filter_year') || request('filter_method'))
                    <div class="col-md-2">
                        <a href="{{ route('stock.manage-sale-records') }}{{ request('search') ? '?search=' . request('search') : '' }}" class="btn btn-sm btn-outline-secondary w-100" style="height: 32px;">
                            <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">clear</span>
                            Clear
                        </a>
                    </div>
                    @endif
                </div>
            </form>

            <!-- Table Toolbar -->
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-3 p-3 rounded-8" style="background-color: #f8f9fa; border: 1px solid #e9ecef;">
                <!-- Right Side -->
                <div class="d-flex align-items-center gap-2 flex-wrap ms-auto">
                    <!-- Export Buttons -->
                    <div class="d-flex gap-2">
                        <a href="{{ route('stock.manage-sale-records.export', ['format' => 'excel']) }}{{ request()->hasAny(['search', 'filter_month', 'filter_date', 'filter_year', 'filter_method']) ? '?' . http_build_query(request()->only(['search', 'filter_month', 'filter_date', 'filter_year', 'filter_method'])) : '' }}" class="btn btn-sm px-2 py-1 export-btn excel-btn">
                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">description</span>
                            <span>Excel</span>
                        </a>
                        <a href="{{ route('stock.manage-sale-records.export', ['format' => 'csv']) }}{{ request()->hasAny(['search', 'filter_month', 'filter_date', 'filter_year', 'filter_method']) ? '?' . http_build_query(request()->only(['search', 'filter_month', 'filter_date', 'filter_year', 'filter_method'])) : '' }}" class="btn btn-sm px-2 py-1 export-btn csv-btn">
                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">file_present</span>
                            <span>CSV</span>
                        </a>
                        <a href="{{ route('stock.manage-sale-records.export', ['format' => 'pdf']) }}{{ request()->hasAny(['search', 'filter_month', 'filter_date', 'filter_year', 'filter_method']) ? '?' . http_build_query(request()->only(['search', 'filter_month', 'filter_date', 'filter_year', 'filter_method'])) : '' }}" class="btn btn-sm px-2 py-1 export-btn pdf-btn" target="_blank">
                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">picture_as_pdf</span>
                            <span>PDF</span>
                        </a>
                        <button type="button" class="btn btn-sm px-2 py-1 export-btn print-btn" onclick="printTable()">
                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">print</span>
                            <span>Print</span>
                        </button>
                    </div>
                    
                    <!-- Search -->
                    <div class="d-flex align-items-center gap-2">
                        <label for="searchInput" class="mb-0 fs-13 fw-medium text-dark">Search:</label>
                        <div class="input-group input-group-sm search-input-group" style="width: 280px;">
                            <span class="input-group-text bg-light border-end-0" style="background-color: #f0f4ff !important; border-color: #e0e7ff; padding: 4px 8px;">
                                <span class="material-symbols-outlined" style="font-size: 14px; color: #003471;">search</span>
                            </span>
                            <input type="text" id="searchInput" class="form-control border-start-0 border-end-0" placeholder="Search sale records..." value="{{ request('search') }}" onkeypress="handleSearchKeyPress(event)" oninput="handleSearchInput(event)" style="padding: 4px 8px; font-size: 13px;">
                            @if(request('search'))
                                <button class="btn btn-outline-secondary border-start-0 border-end-0" type="button" onclick="clearSearch()" title="Clear search" style="padding: 4px 8px;">
                                    <span class="material-symbols-outlined" style="font-size: 14px;">close</span>
                                </button>
                            @endif
                            <button class="btn btn-sm search-btn" type="button" onclick="performSearch()" title="Search" style="padding: 4px 10px;">
                                <span class="material-symbols-outlined" style="font-size: 14px;">search</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Results Table -->
            <div class="mt-3">
                <div class="mb-2 p-2 rounded-8" style="background: linear-gradient(135deg, #003471 0%, #004a9f 100%);">
                    <h5 class="mb-0 text-white fs-15 fw-semibold d-flex align-items-center gap-2">
                        <span class="material-symbols-outlined" style="font-size: 18px;">list</span>
                        <span>Sale Records 
                            @if(request()->hasAny(['filter_month', 'filter_date', 'filter_year', 'filter_method', 'search']))
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
                                    <th>Received By</th>
                                    <th>Notes</th>
                                    <th class="text-end">Action</th>
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
                                    <td>
                                        <span class="badge bg-success text-white">{{ $record->received_by ?? 'N/A' }}</span>
                                    </td>
                                    <td>{{ $record->notes ? (strlen($record->notes) > 30 ? substr($record->notes, 0, 30) . '...' : $record->notes) : 'N/A' }}</td>
                                    <td class="text-end">
                                        <div class="d-flex gap-1 justify-content-end">
                                            <a href="{{ route('stock.manage-sale-records.print', $record->id) }}" target="_blank" class="btn btn-sm btn-primary action-btn px-2 py-1" title="Print Receipt">
                                                <span class="material-symbols-outlined action-icon text-white" style="font-size: 16px;">print</span>
                                            </a>
                                            <button type="button" class="btn btn-sm btn-danger action-btn px-2 py-1" title="Delete" onclick="if(confirm('Are you sure you want to delete this sale record?')) { document.getElementById('delete-sale-record-{{ $record->id }}').submit(); }">
                                                <span class="material-symbols-outlined action-icon text-white" style="font-size: 16px;">delete</span>
                                            </button>
                                        </div>
                                        <form id="delete-sale-record-{{ $record->id }}" action="{{ route('stock.manage-sale-records.destroy', $record->id) }}" method="POST" class="d-none">
                                            @csrf
                                            @method('DELETE')
                                        </form>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="12" class="text-center py-4">
                                        <div class="d-flex flex-column align-items-center">
                                            <span class="material-symbols-outlined text-muted" style="font-size: 48px;">inbox</span>
                                            <p class="text-muted mt-2 mb-0">
                                                @if(request()->hasAny(['filter_month', 'filter_date', 'filter_year', 'filter_method', 'search']))
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
                                    <td colspan="5"></td>
                                </tr>
                                <tr class="fw-bold" style="background-color: #f8f9fa;">
                                    <td colspan="6" class="text-end" style="font-size: 14px;">Total Quantity:</td>
                                    <td style="font-size: 14px;">{{ number_format($totalQuantity, 0) }}</td>
                                    <td colspan="5"></td>
                                </tr>
                                <tr class="fw-bold" style="background-color: #e7f3ff; border-top: 2px solid #003471;">
                                    <td colspan="6" class="text-end" style="font-size: 14px; color: #003471;">Total Records:</td>
                                    <td style="font-size: 14px; color: #003471;">{{ $saleRecords->count() }}</td>
                                    <td colspan="5"></td>
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
.rounded-8 {
    border-radius: 8px;
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

/* Export Buttons Styling */
.export-btn {
    border: none;
    font-weight: 500;
    transition: all 0.3s ease;
    border-radius: 6px;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    height: 32px;
    font-size: 13px;
}

.excel-btn {
    background-color: #28a745;
    color: white;
}

.excel-btn:hover {
    background-color: #218838;
    color: white;
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(40, 167, 69, 0.3);
}

.csv-btn {
    background-color: #17a2b8;
    color: white;
}

.csv-btn:hover {
    background-color: #138496;
    color: white;
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(23, 162, 184, 0.3);
}

.pdf-btn {
    background-color: #dc3545;
    color: white;
}

.pdf-btn:hover {
    background-color: #c82333;
    color: white;
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(220, 53, 69, 0.3);
}

.print-btn {
    background-color: #6c757d;
    color: white;
}

.print-btn:hover {
    background-color: #5a6268;
    color: white;
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(108, 117, 125, 0.3);
}

.export-btn:active {
    transform: translateY(0);
}

/* Search Input Group Styling */
.search-input-group {
    border-radius: 8px;
    overflow: hidden;
    border: 1px solid #dee2e6;
    transition: all 0.3s ease;
    height: 32px;
}

.search-input-group:focus-within {
    border-color: #003471;
    box-shadow: 0 0 0 3px rgba(0, 52, 113, 0.15);
}

.search-input-group .form-control {
    border: none;
    font-size: 13px;
    height: 32px;
    line-height: 1.4;
}

.search-input-group .form-control:focus {
    box-shadow: none;
    border: none;
}

.search-input-group .input-group-text {
    height: 32px;
    padding: 4px 8px;
    display: flex;
    align-items: center;
}

.search-btn {
    background: linear-gradient(135deg, #003471 0%, #004a9f 100%);
    color: white;
    border: none;
    padding: 4px 10px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
}

.search-btn:hover {
    background: linear-gradient(135deg, #004a9f 0%, #003471 100%);
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0, 52, 113, 0.3);
}

@media print {
    .export-btn, .search-input-group, .filter-btn, .clear-btn, .action-btn {
        display: none !important;
    }
}
</style>

<script>
function handleSearchKeyPress(event) {
    if (event.key === 'Enter') {
        event.preventDefault();
        performSearch();
    }
}

function handleSearchInput(event) {
    if (event.target.value === '') {
        clearSearch();
    }
}

function performSearch() {
    const searchValue = document.getElementById('searchInput').value.trim();
    const url = new URL(window.location.href);
    if (searchValue) {
        url.searchParams.set('search', searchValue);
    } else {
        url.searchParams.delete('search');
    }
    url.searchParams.delete('page');
    window.location.href = url.toString();
}

function clearSearch() {
    const url = new URL(window.location.href);
    url.searchParams.delete('search');
    url.searchParams.delete('page');
    window.location.href = url.toString();
}

function printTable() {
    window.print();
}

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
    
    // Also handle when month/year is changed, clear date if needed
    if (monthSelect) {
        monthSelect.addEventListener('change', function() {
            // Optional: Clear date if month is changed manually
            // Uncomment if you want this behavior
            // if (dateInput && dateInput.value) {
            //     const date = new Date(dateInput.value);
            //     const selectedMonth = String(date.getMonth() + 1).padStart(2, '0');
            //     if (selectedMonth !== this.value) {
            //         dateInput.value = '';
            //     }
            // }
        });
    }
    
    if (yearSelect) {
        yearSelect.addEventListener('change', function() {
            // Optional: Clear date if year is changed manually
            // Uncomment if you want this behavior
            // if (dateInput && dateInput.value) {
            //     const date = new Date(dateInput.value);
            //     const selectedYear = date.getFullYear();
            //     if (selectedYear != this.value) {
            //         dateInput.value = '';
            //     }
            // }
        });
    }
});
</script>
@endsection
