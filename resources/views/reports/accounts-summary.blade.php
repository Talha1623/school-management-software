@extends('layouts.app')

@section('title', 'Accounts Summary Reports')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-3 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h4 class="mb-0 fs-16 fw-semibold">Accounts Summary Reports</h4>
                    <small class="text-muted">Income: cash received + discount (partial payments included)</small>
                </div>
            </div>

            <!-- Filter Form -->
            <form action="{{ route('reports.accounts-summary') }}" method="GET" id="filterForm">
                <div class="row g-2 mb-3 align-items-end">
                    <!-- Campus -->
                    <div class="col-md-2">
                        <label for="filter_campus" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Campus</label>
                        <select class="form-select form-select-sm" id="filter_campus" name="filter_campus" style="height: 32px;">
                            <option value="">All Campuses</option>
                            @foreach($campuses as $campus)
                                <option value="{{ $campus->campus_name ?? $campus }}" data-campus-id="{{ $campus->id ?? '' }}" {{ $filterCampus == ($campus->campus_name ?? $campus) ? 'selected' : '' }}>{{ $campus->campus_name ?? $campus }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Type -->
                    <div class="col-md-2">
                        <label for="filter_type" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Type</label>
                        <select class="form-select form-select-sm" id="filter_type" name="filter_type" style="height: 32px;">
                            <option value="day_by_day" {{ ($filterType ?? 'day_by_day') == 'day_by_day' ? 'selected' : '' }}>Day By Day</option>
                            <option value="month_by_month" {{ ($filterType ?? '') == 'month_by_month' ? 'selected' : '' }}>Month By Month</option>
                        </select>
                    </div>

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
            @if(request()->hasAny(['filter_campus', 'filter_month', 'filter_year', 'filter_type']))
            <div class="mt-3">
                <div class="mb-2 p-2 rounded-8" style="background: linear-gradient(135deg, #003471 0%, #004a9f 100%);">
                    <h5 class="mb-0 text-white fs-15 fw-semibold d-flex align-items-center gap-2">
                        <span class="material-symbols-outlined" style="font-size: 18px;">list</span>
                        <span>Accounts Summary Reports</span>
                    </h5>
                </div>

                <div class="d-flex justify-content-end mb-2">
                    <div class="d-flex gap-2 flex-wrap">
                        <a href="{{ route('reports.accounts-summary.export', ['format' => 'csv']) }}?{{ http_build_query(request()->except(['page'])) }}" class="btn btn-sm px-2 py-1 export-btn csv-btn">
                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">table_view</span>
                            <span>CSV</span>
                        </a>
                        <a href="{{ route('reports.accounts-summary.export', ['format' => 'pdf']) }}?{{ http_build_query(request()->except(['page'])) }}" class="btn btn-sm px-2 py-1 export-btn pdf-btn" target="_blank">
                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">picture_as_pdf</span>
                            <span>PDF</span>
                        </a>
                        <a href="{{ route('reports.accounts-summary.print') }}?{{ http_build_query(array_merge(request()->except(['page']), ['auto_print' => 1])) }}" class="btn btn-sm px-2 py-1 export-btn print-btn" target="_blank">
                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">print</span>
                            <span>Print</span>
                        </a>
                    </div>
                </div>

                <div class="default-table-area" style="margin-top: 0;">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Campus</th>
                                    <th>Month</th>
                                    @if(($filterType ?? 'day_by_day') == 'day_by_day')
                                    <th>Date</th>
                                    @endif
                                    <th>Cash Income</th>
                                    <th>Discount</th>
                                    <th>Total Expense</th>
                                    <th>Profit/Lose</th>
                                    <th>Year</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($summaryRecords as $index => $record)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $record['campus'] }}</td>
                                    <td>{{ $record['month'] }}</td>
                                    @if(($filterType ?? 'day_by_day') == 'day_by_day')
                                    <td>{{ $record['date'] ?? 'N/A' }}</td>
                                    @endif
                                    <td class="text-success">{{ number_format($record['total_income'], 2) }}</td>
                                    <td class="text-warning">{{ number_format($record['total_discount'] ?? 0, 2) }}</td>
                                    <td>{{ number_format($record['total_expense'], 2) }}</td>
                                    <td class="fw-semibold">
                                        @if($record['profit_loss'] >= 0)
                                            <span class="text-success">{{ number_format($record['profit_loss'], 2) }}</span>
                                        @else
                                            <span class="text-danger">{{ number_format($record['profit_loss'], 2) }}</span>
                                        @endif
                                    </td>
                                    <td>{{ $record['year'] }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="{{ ($filterType ?? 'day_by_day') == 'day_by_day' ? '9' : '8' }}" class="text-center py-4">
                                        <div class="d-flex flex-column align-items-center">
                                            <span class="material-symbols-outlined text-muted" style="font-size: 48px;">inbox</span>
                                            <p class="text-muted mt-2 mb-0">No records found</p>
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                            @if($summaryRecords->count() > 0)
                            <tfoot>
                                @php
                                    $colSpanLabel = ($filterType ?? 'day_by_day') == 'day_by_day' ? 4 : 3;
                                    $totals = $summaryTotals ?? [
                                        'income' => $summaryRecords->sum('total_income'),
                                        'discount' => $summaryRecords->sum('total_discount'),
                                        'expense' => $summaryRecords->sum('total_expense'),
                                        'profit' => $summaryRecords->sum('profit_loss'),
                                    ];
                                @endphp
                                <tr class="fw-bold" style="background-color: #f8f9fa;">
                                    <td colspan="{{ $colSpanLabel }}" class="text-end">Total</td>
                                    <td class="text-success">{{ number_format($totals['income'] ?? 0, 2) }}</td>
                                    <td class="text-warning">{{ number_format($totals['discount'] ?? 0, 2) }}</td>
                                    <td>{{ number_format($totals['expense'] ?? 0, 2) }}</td>
                                    <td>
                                        @if(($totals['profit'] ?? 0) >= 0)
                                            <span class="text-success">+{{ number_format($totals['profit'] ?? 0, 2) }}</span>
                                        @else
                                            <span class="text-danger">{{ number_format($totals['profit'] ?? 0, 2) }}</span>
                                        @endif
                                    </td>
                                    <td></td>
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
                <p class="text-muted mt-3 mb-0">Please apply filters to view accounts summary</p>
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

.export-btn {
    border: none;
    font-weight: 500;
    transition: all 0.3s ease;
    border-radius: 6px;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    height: 32px;
    font-size: 12px;
}
.csv-btn { background-color: #17a2b8; color: white; }
.csv-btn:hover { background-color: #138496; color: white; }
.pdf-btn { background-color: #dc3545; color: white; }
.pdf-btn:hover { background-color: #c82333; color: white; }
.print-btn { background-color: #6c757d; color: white; }
.print-btn:hover { background-color: #5a6268; color: white; }
</style>
@endsection
