@extends('layouts.app')

@section('title', 'Subject/Lecture Attendance Summary')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-3 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0 fs-16 fw-semibold">Subject/Lecture Attendance Summary</h4>
            </div>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <!-- Filter Form -->
            <form method="GET" action="{{ route('attendance.subject-lecture-summary') }}" id="filterForm">
                <div class="row g-2 align-items-end">
                    <!-- Campus Field -->
                    <div class="col-md-3">
                        <label for="filter_campus" class="form-label mb-0 fs-13 fw-medium">Campus</label>
                        <div class="position-relative">
                            <select class="form-select form-select-sm" id="filter_campus" name="filter_campus" style="height: 32px;">
                                <option value="">All Campuses</option>
                                @foreach($campuses as $campus)
                                    <option value="{{ $campus->campus_name ?? $campus }}" {{ $filterCampus == ($campus->campus_name ?? $campus) ? 'selected' : '' }}>{{ $campus->campus_name ?? $campus }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- Month Field -->
                    <div class="col-md-3">
                        <label for="filter_month" class="form-label mb-0 fs-13 fw-medium">Month</label>
                        <select class="form-select form-select-sm" id="filter_month" name="filter_month" style="height: 32px;">
                            @for($i = 1; $i <= 12; $i++)
                                <option value="{{ $i }}" {{ $filterMonth == $i ? 'selected' : '' }}>{{ date('F', mktime(0, 0, 0, $i, 1)) }}</option>
                            @endfor
                        </select>
                    </div>

                    <!-- Year Field -->
                    <div class="col-md-3">
                        <label for="filter_year" class="form-label mb-0 fs-13 fw-medium">Year</label>
                        <select class="form-select form-select-sm" id="filter_year" name="filter_year" style="height: 32px;">
                            @for($year = date('Y'); $year >= date('Y') - 5; $year--)
                                <option value="{{ $year }}" {{ $filterYear == $year ? 'selected' : '' }}>{{ $year }}</option>
                            @endfor
                        </select>
                    </div>

                    <!-- Filter Button -->
                    <div class="col-md-3">
                        <label class="form-label mb-0 fs-13 fw-medium" style="visibility: hidden;">Filter</label>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-sm w-100 filter-btn d-flex align-items-center justify-content-center gap-1" style="height: 32px;">
                                <span class="material-symbols-outlined" style="font-size: 16px;">filter_list</span>
                                <span style="font-size: 13px;">Filter</span>
                            </button>
                            <a href="{{ route('attendance.subject-lecture-summary.print') }}" target="_blank" class="btn btn-sm btn-outline-primary d-flex align-items-center justify-content-center" style="height: 32px;">
                                <span class="material-symbols-outlined" style="font-size: 16px;">print</span>
                            </a>
                        </div>
                    </div>
                </div>
            </form>

            <!-- Summary Table -->
            @if(request()->has('filter_month') || request()->has('filter_campus'))
            <div class="mt-3">
                <div class="mb-2 p-2 rounded-8" style="background: linear-gradient(135deg, #003471 0%, #004a9f 100%);">
                    <h5 class="mb-0 text-white fs-15 fw-semibold d-flex align-items-center gap-2">
                        <span class="material-symbols-outlined" style="font-size: 18px;">list</span>
                        <span>Summary for {{ $monthLabel }}</span>
                        <span class="badge bg-light text-dark ms-2">
                            {{ $summary->count() }} {{ $summary->count() == 1 ? 'teacher' : 'teachers' }} found
                        </span>
                    </h5>
                </div>

                <div class="default-table-area" style="margin-top: 0;">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead>
                                <tr>
                                    <th style="padding: 8px 12px; font-size: 13px;">#</th>
                                    <th style="padding: 8px 12px; font-size: 13px;">Emp. ID</th>
                                    <th style="padding: 8px 12px; font-size: 13px;">Name</th>
                                    <th style="padding: 8px 12px; font-size: 13px;">Designation</th>
                                    <th style="padding: 8px 12px; font-size: 13px;">Campus</th>
                                    <th style="padding: 8px 12px; font-size: 13px;">Total Lectures</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($summary as $index => $row)
                                    @php $staff = $row['staff']; @endphp
                                    <tr>
                                        <td style="padding: 8px 12px; font-size: 13px;">{{ $index + 1 }}</td>
                                        <td style="padding: 8px 12px; font-size: 13px;">
                                            @if($staff->emp_id)
                                                <span class="badge bg-info text-white" style="font-size: 11px;">{{ $staff->emp_id }}</span>
                                            @else
                                                <span class="text-muted">N/A</span>
                                            @endif
                                        </td>
                                        <td style="padding: 8px 12px; font-size: 13px;">
                                            <strong class="text-primary">{{ $staff->name ?? 'N/A' }}</strong>
                                        </td>
                                        <td style="padding: 8px 12px; font-size: 13px;">
                                            <span class="badge bg-secondary text-white" style="font-size: 11px;">{{ $staff->designation ?? 'N/A' }}</span>
                                        </td>
                                        <td style="padding: 8px 12px; font-size: 13px;">
                                            <span class="badge bg-primary text-white" style="font-size: 11px;">{{ $staff->campus ?? 'N/A' }}</span>
                                        </td>
                                        <td style="padding: 8px 12px; font-size: 13px;">
                                            <span class="badge bg-success text-white" style="font-size: 11px;">{{ $row['total_lectures'] ?? 0 }}</span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-5">
                                            <span class="material-symbols-outlined" style="font-size: 48px; opacity: 0.3;">school</span>
                                            <p class="mt-2 mb-0">No lecture data found for the selected filters.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @else
            <!-- Message when no filters applied -->
            <div class="text-center py-5 mt-3">
                <span class="material-symbols-outlined" style="font-size: 64px; color: #dee2e6; opacity: 0.5;">filter_list</span>
                <h5 class="mt-3 text-muted">Apply Filters to View Summary</h5>
                <p class="text-muted mb-0">Please select Month and Year, then click "Filter" to view the summary.</p>
            </div>
            @endif
        </div>
    </div>
</div>

<style>
    .form-select-sm {
        font-size: 13px;
        padding: 6px 12px;
        border-radius: 6px;
        border: 1px solid #dee2e6;
        transition: all 0.3s ease;
        height: 32px;
    }
    
    .form-select-sm:focus {
        border-color: #003471;
        box-shadow: 0 0 0 3px rgba(0, 52, 113, 0.15);
        outline: none;
    }
    
    .filter-btn {
        background: linear-gradient(135deg, #003471 0%, #004a9f 100%);
        color: white;
        border: none;
        font-weight: 500;
        transition: all 0.3s ease;
        box-shadow: 0 2px 6px rgba(0, 52, 113, 0.2);
        height: 32px;
    }
    
    .filter-btn:hover {
        background: linear-gradient(135deg, #004a9f 0%, #003471 100%);
        transform: translateY(-1px);
        box-shadow: 0 4px 10px rgba(0, 52, 113, 0.3);
        color: white;
    }
    
    .filter-btn:active {
        transform: translateY(0);
    }
    
    .filter-btn .material-symbols-outlined {
        color: white !important;
    }
    
    .rounded-8 {
        border-radius: 8px;
    }

    .default-table-area .table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 0;
    }

    .default-table-area .table th,
    .default-table-area .table td {
        padding: 8px 12px;
        vertical-align: middle;
        border-top: 1px solid #e9ecef;
        font-size: 13px;
    }

    .default-table-area .table thead th {
        border-bottom: 2px solid #e9ecef;
        font-weight: 600;
        color: #495057;
        background-color: #f8f9fa;
    }

    .default-table-area .table tbody tr:nth-of-type(odd) {
        background-color: #ffffff;
    }

    .default-table-area .table tbody tr:nth-of-type(even) {
        background-color: #fdfdfd;
    }
    
    .table-hover tbody tr:hover {
        background-color: #f8f9fa;
    }
</style>
@endsection
