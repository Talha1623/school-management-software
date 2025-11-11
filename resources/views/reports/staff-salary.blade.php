@extends('layouts.app')

@section('title', 'Staff Salary Reports')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-3 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0 fs-16 fw-semibold">Staff Salary Reports</h4>
            </div>

            <!-- Filter Form -->
            <form action="{{ route('reports.staff-salary') }}" method="GET" id="filterForm">
                <div class="row g-2 mb-3 align-items-end">
                    <!-- Campus -->
                    <div class="col-md-3">
                        <label for="filter_campus" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Campus</label>
                        <select class="form-select form-select-sm" id="filter_campus" name="filter_campus" style="height: 32px;">
                            <option value="">All Campuses</option>
                            @foreach($campuses as $campus)
                                <option value="{{ $campus }}" {{ $filterCampus == $campus ? 'selected' : '' }}>{{ $campus }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Month -->
                    <div class="col-md-3">
                        <label for="filter_month" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Month</label>
                        <select class="form-select form-select-sm" id="filter_month" name="filter_month" style="height: 32px;">
                            <option value="">All Months</option>
                            @foreach($months as $monthValue => $monthName)
                                <option value="{{ $monthValue }}" {{ $filterMonth == $monthValue ? 'selected' : '' }}>{{ $monthName }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Year -->
                    <div class="col-md-3">
                        <label for="filter_year" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Year</label>
                        <select class="form-select form-select-sm" id="filter_year" name="filter_year" style="height: 32px;">
                            <option value="">All Years</option>
                            @foreach($years as $year)
                                <option value="{{ $year }}" {{ $filterYear == $year ? 'selected' : '' }}>{{ $year }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Filter Button -->
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-sm py-1 px-3 rounded-8 filter-btn w-100" style="height: 32px;">
                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">filter_alt</span>
                            <span style="font-size: 12px;">Filter</span>
                        </button>
                    </div>
                </div>
            </form>

            <!-- Results Table -->
            @if(request()->hasAny(['filter_campus', 'filter_month', 'filter_year']))
            <div class="mt-3">
                <div class="mb-2 p-2 rounded-8" style="background: linear-gradient(135deg, #003471 0%, #004a9f 100%);">
                    <h5 class="mb-0 text-white fs-15 fw-semibold d-flex align-items-center gap-2">
                        <span class="material-symbols-outlined" style="font-size: 18px;">list</span>
                        <span>Staff Salary Reports</span>
                    </h5>
                </div>

                <div class="default-table-area" style="margin-top: 0;">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Photo</th>
                                    <th>Staff Name</th>
                                    <th>Emp ID</th>
                                    <th>Campus</th>
                                    <th>Designation</th>
                                    <th>Salary Month</th>
                                    <th>Year</th>
                                    <th>Present</th>
                                    <th>Absent</th>
                                    <th>Late</th>
                                    <th>Basic</th>
                                    <th>Salary Generated</th>
                                    <th>Amount Paid</th>
                                    <th>Loan Repayment</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($salaryRecords as $index => $record)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>
                                        @if($record['photo'])
                                            <img src="{{ asset('storage/' . $record['photo']) }}" alt="{{ $record['staff_name'] }}" class="rounded-circle" style="width: 40px; height: 40px; object-fit: cover;">
                                        @else
                                            <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                                <span class="text-white fw-bold">{{ substr($record['staff_name'], 0, 1) }}</span>
                                            </div>
                                        @endif
                                    </td>
                                    <td>{{ $record['staff_name'] }}</td>
                                    <td>{{ $record['emp_id'] ?? 'N/A' }}</td>
                                    <td>{{ $record['campus'] ?? 'N/A' }}</td>
                                    <td>{{ $record['designation'] ?? 'N/A' }}</td>
                                    <td>
                                        @php
                                            $monthNames = ['01' => 'January', '02' => 'February', '03' => 'March', '04' => 'April', '05' => 'May', '06' => 'June', '07' => 'July', '08' => 'August', '09' => 'September', '10' => 'October', '11' => 'November', '12' => 'December'];
                                        @endphp
                                        {{ $monthNames[$record['salary_month']] ?? $record['salary_month'] }}
                                    </td>
                                    <td>{{ $record['year'] }}</td>
                                    <td>{{ $record['present'] }}</td>
                                    <td>{{ $record['absent'] }}</td>
                                    <td>{{ $record['late'] }}</td>
                                    <td>{{ number_format($record['basic'], 2) }}</td>
                                    <td>{{ number_format($record['salary_generated'], 2) }}</td>
                                    <td>{{ number_format($record['amount_paid'], 2) }}</td>
                                    <td>{{ number_format($record['loan_repayment'], 2) }}</td>
                                    <td>
                                        @if($record['status'] == 'Paid')
                                            <span class="badge bg-success">Paid</span>
                                        @elseif($record['status'] == 'Partial')
                                            <span class="badge bg-warning text-dark">Partial</span>
                                        @else
                                            <span class="badge bg-danger">Pending</span>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="16" class="text-center py-4">
                                        <div class="d-flex flex-column align-items-center">
                                            <span class="material-symbols-outlined text-muted" style="font-size: 48px;">inbox</span>
                                            <p class="text-muted mt-2 mb-0">No salary records found</p>
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                            @if($salaryRecords->count() > 0)
                            <tfoot>
                                <tr class="fw-bold" style="background-color: #f8f9fa;">
                                    <td colspan="11" class="text-end">Total Basic:</td>
                                    <td>{{ number_format($salaryRecords->sum('basic'), 2) }}</td>
                                    <td>{{ number_format($salaryRecords->sum('salary_generated'), 2) }}</td>
                                    <td>{{ number_format($salaryRecords->sum('amount_paid'), 2) }}</td>
                                    <td>{{ number_format($salaryRecords->sum('loan_repayment'), 2) }}</td>
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
                <p class="text-muted mt-3 mb-0">Please apply filters to view staff salary reports</p>
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
