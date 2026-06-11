@extends('layouts.accountant')

@section('title', 'Accounts Summary Reports')

@section('content')
@php
    $defaultCampus = $defaultCampus ?? null;
    $summaryRoute = 'accountant.accounts-summary';
    $exportRoute = 'accountant.accounts-summary.export';
    $printRoute = 'accountant.accounts-summary.print';
@endphp
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-3 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h4 class="mb-0 fs-16 fw-semibold">Accounts Summary Reports</h4>
                    <small class="text-muted">Income: cash received + discount (partial payments included)</small>
                </div>
            </div>

            <form action="{{ route($summaryRoute) }}" method="GET" id="filterForm">
                <div class="row g-2 mb-3 align-items-end">
                    <div class="col-md-2">
                        <label for="filter_campus" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Campus</label>
                        <select class="form-select form-select-sm" id="filter_campus" name="filter_campus" style="height: 32px;" @if(!empty($defaultCampus)) disabled @endif>
                            @if(empty($defaultCampus))
                            <option value="">All Campuses</option>
                            @endif
                            @foreach($campuses as $campus)
                                @php $campusName = $campus->campus_name ?? $campus; @endphp
                                <option value="{{ $campusName }}" {{ ($filterCampus ?? $defaultCampus) == $campusName ? 'selected' : '' }}>{{ $campusName }}</option>
                            @endforeach
                        </select>
                        @if(!empty($defaultCampus))
                        <input type="hidden" name="filter_campus" value="{{ $defaultCampus }}">
                        @endif
                    </div>
                    <div class="col-md-2">
                        <label for="filter_type" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Type</label>
                        <select class="form-select form-select-sm" id="filter_type" name="filter_type" style="height: 32px;">
                            <option value="day_by_day" {{ ($filterType ?? 'day_by_day') == 'day_by_day' ? 'selected' : '' }}>Day By Day</option>
                            <option value="month_by_month" {{ ($filterType ?? '') == 'month_by_month' ? 'selected' : '' }}>Month By Month</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="filter_month" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Month</label>
                        <select class="form-select form-select-sm" id="filter_month" name="filter_month" style="height: 32px;">
                            <option value="">All Months</option>
                            @foreach($months as $monthValue => $monthName)
                                <option value="{{ $monthValue }}" {{ $filterMonth == $monthValue ? 'selected' : '' }}>{{ $monthName }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="filter_year" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Year</label>
                        <select class="form-select form-select-sm" id="filter_year" name="filter_year" style="height: 32px;">
                            <option value="">All Years</option>
                            @foreach($years as $year)
                                <option value="{{ $year }}" {{ $filterYear == $year ? 'selected' : '' }}>{{ $year }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-sm py-1 px-3 rounded-8 filter-btn w-100" style="height: 32px;">
                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">filter_alt</span>
                            <span style="font-size: 12px;">Filter</span>
                        </button>
                    </div>
                </div>
            </form>

            @if(request()->hasAny(['filter_campus', 'filter_month', 'filter_year', 'filter_type']))
            <div class="mt-3">
                <div class="d-flex justify-content-end mb-2">
                    <div class="d-flex gap-2 flex-wrap">
                        <a href="{{ route($exportRoute, ['format' => 'csv']) }}?{{ http_build_query(request()->except(['page'])) }}" class="btn btn-sm px-2 py-1 export-btn csv-btn">CSV</a>
                        <a href="{{ route($exportRoute, ['format' => 'pdf']) }}?{{ http_build_query(request()->except(['page'])) }}" class="btn btn-sm px-2 py-1 export-btn pdf-btn" target="_blank">PDF</a>
                        <a href="{{ route($printRoute) }}?{{ http_build_query(array_merge(request()->except(['page']), ['auto_print' => 1])) }}" class="btn btn-sm px-2 py-1 export-btn print-btn" target="_blank">Print</a>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>#</th><th>Campus</th><th>Month</th>
                                @if(($filterType ?? 'day_by_day') == 'day_by_day')<th>Date</th>@endif
                                <th>Cash Income</th><th>Discount</th><th>Total Expense</th><th>Profit/Lose</th><th>Year</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($summaryRecords as $index => $record)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $record['campus'] }}</td>
                                <td>{{ $record['month'] }}</td>
                                @if(($filterType ?? 'day_by_day') == 'day_by_day')<td>{{ $record['date'] ?? 'N/A' }}</td>@endif
                                <td class="text-success">{{ number_format($record['total_income'], 2) }}</td>
                                <td class="text-warning">{{ number_format($record['total_discount'] ?? 0, 2) }}</td>
                                <td>{{ number_format($record['total_expense'], 2) }}</td>
                                <td class="fw-semibold">
                                    @if($record['profit_loss'] >= 0)<span class="text-success">{{ number_format($record['profit_loss'], 2) }}</span>
                                    @else<span class="text-danger">{{ number_format($record['profit_loss'], 2) }}</span>@endif
                                </td>
                                <td>{{ $record['year'] }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="{{ ($filterType ?? 'day_by_day') == 'day_by_day' ? 9 : 8 }}" class="text-center py-4 text-muted">No records found</td></tr>
                            @endforelse
                        </tbody>
                        @if($summaryRecords->count() > 0)
                        @php
                            $colSpanLabel = ($filterType ?? 'day_by_day') == 'day_by_day' ? 4 : 3;
                            $totals = $summaryTotals ?? ['income' => 0, 'discount' => 0, 'expense' => 0, 'profit' => 0];
                        @endphp
                        <tfoot>
                            <tr class="fw-bold" style="background-color: #f8f9fa;">
                                <td colspan="{{ $colSpanLabel }}" class="text-end">Total</td>
                                <td class="text-success">{{ number_format($totals['income'] ?? 0, 2) }}</td>
                                <td class="text-warning">{{ number_format($totals['discount'] ?? 0, 2) }}</td>
                                <td>{{ number_format($totals['expense'] ?? 0, 2) }}</td>
                                <td>@if(($totals['profit'] ?? 0) >= 0)<span class="text-success">+{{ number_format($totals['profit'] ?? 0, 2) }}</span>@else<span class="text-danger">{{ number_format($totals['profit'] ?? 0, 2) }}</span>@endif</td>
                                <td></td>
                            </tr>
                        </tfoot>
                        @endif
                    </table>
                </div>
            </div>
            @else
            <div class="text-center py-5 text-muted">Please apply filters to view accounts summary</div>
            @endif
        </div>
    </div>
</div>
<style>
.filter-btn { background: linear-gradient(135deg, #003471 0%, #004a9f 100%); color: white; border: none; }
.export-btn { border: none; border-radius: 6px; color: #fff; padding: 4px 10px; font-size: 12px; }
.csv-btn { background: #17a2b8; } .pdf-btn { background: #dc3545; } .print-btn { background: #6c757d; }
</style>
@endsection
