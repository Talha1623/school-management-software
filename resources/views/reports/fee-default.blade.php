@extends('layouts.app')

@section('title', 'Fee Default Reports')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-3 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0 fs-16 fw-semibold">Fee Default Reports</h4>
            </div>

            <!-- Filter Form -->
            <form action="{{ route('reports.fee-default') }}" method="GET" id="filterForm">
                <div class="row g-2 mb-3 align-items-end">
                    <!-- Campus -->
                    <div class="col-md-2">
                        <label for="filter_campus" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Campus</label>
                        <select class="form-select form-select-sm" id="filter_campus" name="filter_campus" style="height: 32px;">
                            <option value="">All Campuses</option>
                            @foreach($campuses as $campus)
                                <option value="{{ $campus }}" {{ $filterCampus == $campus ? 'selected' : '' }}>{{ $campus }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Class -->
                    <div class="col-md-2">
                        <label for="filter_class" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Class</label>
                        <select class="form-select form-select-sm" id="filter_class" name="filter_class" style="height: 32px;">
                            <option value="">All Classes</option>
                            @foreach($classes as $className)
                                <option value="{{ $className }}" {{ $filterClass == $className ? 'selected' : '' }}>{{ $className }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Section -->
                    <div class="col-md-2">
                        <label for="filter_section" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Section</label>
                        <select class="form-select form-select-sm" id="filter_section" name="filter_section" style="height: 32px;">
                            <option value="">All Sections</option>
                            @foreach($sections as $sectionName)
                                <option value="{{ $sectionName }}" {{ $filterSection == $sectionName ? 'selected' : '' }}>{{ $sectionName }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Type -->
                    <div class="col-md-2">
                        <label for="filter_type" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Type</label>
                        <select class="form-select form-select-sm" id="filter_type" name="filter_type" style="height: 32px;">
                            <option value="">All Types</option>
                            @foreach($feeTypes as $feeType)
                                <option value="{{ $feeType }}" {{ $filterType == $feeType ? 'selected' : '' }}>{{ $feeType }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Status -->
                    <div class="col-md-2">
                        <label for="filter_status" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Status</label>
                        <select class="form-select form-select-sm" id="filter_status" name="filter_status" style="height: 32px;">
                            <option value="">All Status</option>
                            @foreach($statusOptions as $status)
                                <option value="{{ $status }}" {{ $filterStatus == $status ? 'selected' : '' }}>{{ $status }}</option>
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
            @if(request()->hasAny(['filter_campus', 'filter_class', 'filter_section', 'filter_type', 'filter_status']))
            <div class="mt-3">
                <div class="mb-2 p-2 rounded-8" style="background: linear-gradient(135deg, #003471 0%, #004a9f 100%);">
                    <h5 class="mb-0 text-white fs-15 fw-semibold d-flex align-items-center gap-2">
                        <span class="material-symbols-outlined" style="font-size: 18px;">list</span>
                        <span>Fee Default Reports</span>
                    </h5>
                </div>

                <div class="default-table-area" style="margin-top: 0;">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Student Code</th>
                                    <th>Student Name</th>
                                    <th>Campus</th>
                                    <th>Class</th>
                                    <th>Section</th>
                                    <th>Fee Type</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if(isset($students) && $students->count() > 0)
                                    @forelse($students as $student)
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td>
                                                <strong class="text-primary">{{ $student->student_code ?? 'N/A' }}</strong>
                                            </td>
                                            <td>
                                                <strong>{{ $student->student_name ?? 'N/A' }}</strong>
                                            </td>
                                            <td>
                                                <span class="badge bg-info text-white">{{ $student->campus ?? 'N/A' }}</span>
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary text-white">{{ $student->class ?? 'N/A' }}</span>
                                            </td>
                                            <td>
                                                <span class="badge bg-primary text-white">{{ $student->section ?? 'N/A' }}</span>
                                            </td>
                                            <td>
                                                <span class="text-muted">{{ $filterType ?? 'N/A' }}</span>
                                            </td>
                                            <td>
                                                <strong class="text-success">₹{{ number_format($student->monthly_fee ?? 0, 2) }}</strong>
                                            </td>
                                            <td>
                                                @if($filterStatus == 'Paid' || !$filterStatus)
                                                    <span class="badge bg-success text-white">Paid</span>
                                                @elseif($filterStatus == 'Pending')
                                                    <span class="badge bg-warning text-dark">Pending</span>
                                                @elseif($filterStatus == 'Default')
                                                    <span class="badge bg-danger text-white">Default</span>
                                                @elseif($filterStatus == 'Partial')
                                                    <span class="badge bg-info text-white">Partial</span>
                                                @else
                                                    <span class="badge bg-secondary text-white">{{ $filterStatus ?? 'N/A' }}</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="9" class="text-center text-muted py-5">
                                                <span class="material-symbols-outlined" style="font-size: 48px; opacity: 0.3;">inbox</span>
                                                <p class="mt-2 mb-0">No records found.</p>
                                            </td>
                                        </tr>
                                    @endforelse
                                @else
                                    <tr>
                                        <td colspan="9" class="text-center text-muted py-5">
                                            <span class="material-symbols-outlined" style="font-size: 48px; opacity: 0.3;">inbox</span>
                                            <p class="mt-2 mb-0">No records found. Please apply filters to see results.</p>
                                        </td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @else
                <div class="alert alert-info" role="alert">
                    <span class="material-symbols-outlined" style="font-size: 18px; vertical-align: middle;">info</span>
                    <strong>Please select filters and click "Apply Filter" to view fee default reports.</strong>
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
        font-weight: 500;
        transition: all 0.3s ease;
        box-shadow: 0 2px 8px rgba(0, 52, 113, 0.25);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 4px;
    }
    
    .filter-btn:hover {
        background: linear-gradient(135deg, #004a9f 0%, #003471 100%);
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0, 52, 113, 0.35);
        color: white;
    }
    
    .filter-btn:active {
        transform: translateY(0);
    }
    
    .form-select-sm {
        font-size: 12px;
    }
    
    .form-label {
        font-size: 12px;
        margin-bottom: 4px;
    }
    
    .rounded-8 {
        border-radius: 8px;
    }
    
    .rounded-10 {
        border-radius: 10px;
    }
    
    /* Table Compact Styling */
    .default-table-area table {
        margin-bottom: 0;
        border-spacing: 0;
        border-collapse: collapse;
        border: 1px solid #dee2e6;
    }
    
    .default-table-area table thead {
        border-bottom: 1px solid #dee2e6;
    }
    
    .default-table-area table thead th {
        padding: 5px 10px;
        font-size: 12px;
        font-weight: 600;
        vertical-align: middle;
        line-height: 1.3;
        height: 32px;
        white-space: nowrap;
        border: 1px solid #dee2e6;
        background-color: #f8f9fa;
    }
    
    .default-table-area table thead th:first-child {
        border-left: 1px solid #dee2e6;
    }
    
    .default-table-area table thead th:last-child {
        border-right: 1px solid #dee2e6;
    }
    
    .default-table-area table tbody td {
        padding: 5px 10px;
        font-size: 12px;
        vertical-align: middle;
        line-height: 1.4;
        border: 1px solid #dee2e6;
    }
    
    .default-table-area table tbody td:first-child {
        border-left: 1px solid #dee2e6;
    }
    
    .default-table-area table tbody td:last-child {
        border-right: 1px solid #dee2e6;
    }
    
    .default-table-area table tbody tr:last-child td {
        border-bottom: 1px solid #dee2e6;
    }
    
    .default-table-area table thead th:first-child,
    .default-table-area table tbody td:first-child {
        padding-left: 10px;
    }
    
    .default-table-area table thead th:last-child,
    .default-table-area table tbody td:last-child {
        padding-right: 10px;
    }
    
    .default-table-area table tbody tr {
        height: 36px;
    }
    
    .default-table-area table tbody tr:first-child td {
        border-top: none;
    }
    
    .default-table-area .table-responsive {
        padding: 0;
        margin-top: 0;
    }
    
    .default-table-area {
        margin-top: 0 !important;
    }
    
    .default-table-area table tbody tr:hover {
        background-color: #f8f9fa;
    }
    
    .default-table-area .badge {
        font-size: 11px;
        padding: 3px 6px;
        font-weight: 500;
    }
</style>
@endsection
