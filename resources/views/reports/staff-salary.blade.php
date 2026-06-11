@extends('layouts.app')

@section('title', 'Staff Salary Reports')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-3 mb-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-3">
                <h4 class="mb-0 fs-16 fw-semibold">Staff Salary Reports</h4>
                @if(!empty($filtersApplied))
                <div class="d-flex gap-2 flex-wrap export-buttons">
                    <a href="{{ route('reports.staff-salary.export', array_merge(request()->query(), ['format' => 'excel'])) }}"
                       class="btn btn-sm px-2 py-1 export-btn excel-btn">
                        <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">description</span>
                        <span>Excel</span>
                    </a>
                    <a href="{{ route('reports.staff-salary.export', array_merge(request()->query(), ['format' => 'csv'])) }}"
                       class="btn btn-sm px-2 py-1 export-btn csv-btn">
                        <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">file_present</span>
                        <span>CSV</span>
                    </a>
                    <a href="{{ route('reports.staff-salary.export', array_merge(request()->query(), ['format' => 'pdf'])) }}"
                       class="btn btn-sm px-2 py-1 export-btn pdf-btn">
                        <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">picture_as_pdf</span>
                        <span>PDF</span>
                    </a>
                    <a href="{{ route('reports.staff-salary.print', array_merge(request()->query(), ['auto_print' => 1])) }}"
                       target="_blank"
                       class="btn btn-sm px-2 py-1 export-btn print-btn">
                        <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">print</span>
                        <span>Print</span>
                    </a>
                </div>
                @endif
            </div>

            @if(session('error'))
                <div class="alert alert-danger py-2 px-3 fs-12 mb-2">{{ session('error') }}</div>
            @endif

            <!-- Filter Form -->
            <form action="{{ route('reports.staff-salary') }}" method="GET" id="filterForm">
                <div class="row g-2 mb-3 align-items-end">
                    <!-- Campus -->
                    <div class="col-md-2">
                        <label for="filter_campus" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Campus</label>
                        <select class="form-select form-select-sm" id="filter_campus" name="filter_campus" onchange="loadStaffByCampus()" style="height: 32px;">
                            <option value="">All Campuses</option>
                            @foreach($campuses as $campus)
                                <option value="{{ $campus }}" {{ $filterCampus == $campus ? 'selected' : '' }}>{{ $campus }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Staff -->
                    <div class="col-md-2">
                        <label for="staff_id" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Staff</label>
                        <select class="form-select form-select-sm" id="staff_id" name="staff_id" style="height: 32px;">
                            <option value="">All Staff</option>
                            @if($filterCampus)
                                @php
                                    $staffList = \App\Models\Staff::whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($filterCampus))])->orderBy('name')->get(['id', 'name', 'emp_id']);
                                @endphp
                                @foreach($staffList as $staff)
                                    <option value="{{ $staff->id }}" {{ $filterStaffId == $staff->id ? 'selected' : '' }}>{{ $staff->name }} {{ $staff->emp_id ? '(' . $staff->emp_id . ')' : '' }}</option>
                                @endforeach
                            @endif
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

            @if(!empty($filtersApplied))
            <div class="mt-3" id="resultsContainer">
                <div class="mb-2 p-2 rounded-8" style="background: linear-gradient(135deg, #003471 0%, #004a9f 100%);">
                    <h5 class="mb-0 text-white fs-15 fw-semibold d-flex align-items-center gap-2">
                        <span class="material-symbols-outlined" style="font-size: 18px;">list</span>
                        <span>Staff Salary Reports</span>
                    </h5>
                </div>

                @if($salaryRecords->isNotEmpty())
                <div class="default-table-area" style="margin-top: 0;">
                    <div class="table-responsive">
                        <table class="table" id="salaryTable">
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
                                    <th>Early Exit</th>
                                    <th>Basic</th>
                                    <th>Salary Generated</th>
                                    <th>Amount Paid</th>
                                    <th>Loan Repayment</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($salaryRecords as $index => $record)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>
                                        @if(!empty($record['photo']))
                                            <img src="{{ asset('storage/' . $record['photo']) }}" alt="{{ $record['staff_name'] }}" class="rounded-circle" style="width: 40px; height: 40px; object-fit: cover;">
                                        @else
                                            <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                                <span class="text-white fw-bold">{{ strtoupper(substr($record['staff_name'], 0, 1)) }}</span>
                                            </div>
                                        @endif
                                    </td>
                                    <td>{{ $record['staff_name'] }}</td>
                                    <td>{{ $record['emp_id'] }}</td>
                                    <td>{{ $record['campus'] }}</td>
                                    <td>{{ $record['designation'] }}</td>
                                    <td>{{ $record['salary_month'] }}</td>
                                    <td>{{ $record['year'] }}</td>
                                    <td>{{ $record['present'] }}</td>
                                    <td>{{ $record['absent'] }}</td>
                                    <td>{{ $record['late'] }}</td>
                                    <td>{{ $record['early_exit'] }}</td>
                                    <td>{{ $record['basic'] }}</td>
                                    <td>{{ $record['salary_generated'] }}</td>
                                    <td>{{ $record['amount_paid'] }}</td>
                                    <td>{{ $record['loan_repayment'] }}</td>
                                    <td>
                                        @if(($record['status'] ?? '') === 'Paid')
                                            <span class="badge bg-success">Paid</span>
                                        @elseif(($record['status'] ?? '') === 'Partial')
                                            <span class="badge bg-warning text-dark">Partial</span>
                                        @else
                                            <span class="badge bg-danger">Pending</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="fw-bold" style="background-color: #f8f9fa;">
                                    <td colspan="12" class="text-end">Total:</td>
                                    <td>{{ number_format($totals['basic'] ?? 0, 2) }}</td>
                                    <td>{{ number_format($totals['salary_generated'] ?? 0, 2) }}</td>
                                    <td>{{ number_format($totals['amount_paid'] ?? 0, 2) }}</td>
                                    <td>{{ number_format($totals['loan_repayment'] ?? 0, 2) }}</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
                @else
                <div class="alert alert-warning mt-3 mb-0">No salary records found for the selected filters.</div>
                @endif
            </div>
            @else
            <div id="emptyState" class="text-center py-5">
                <span class="material-symbols-outlined text-muted" style="font-size: 64px;">filter_alt</span>
                <p class="text-muted mt-3 mb-0">Please select filters and click Filter to view staff salary reports</p>
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
    font-size: 13px;
}

.excel-btn { background-color: #28a745; color: white; }
.excel-btn:hover { background-color: #218838; color: white; transform: translateY(-1px); }

.csv-btn { background-color: #ff9800; color: white; }
.csv-btn:hover { background-color: #f57c00; color: white; transform: translateY(-1px); }

.pdf-btn { background-color: #dc3545; color: white; }
.pdf-btn:hover { background-color: #c82333; color: white; transform: translateY(-1px); }

.print-btn { background-color: #2196f3; color: white; }
.print-btn:hover { background-color: #0b7dda; color: white; transform: translateY(-1px); }
</style>

<script>
// Load staff by campus
function loadStaffByCampus() {
    const campus = document.getElementById('filter_campus').value;
    const staffSelect = document.getElementById('staff_id');
    
    // Clear existing options except "All Staff"
    staffSelect.innerHTML = '<option value="">All Staff</option>';
    
    if (!campus) {
        return;
    }
    
    fetch(`{{ route('reports.staff-salary.get-staff-by-campus') }}?campus=${encodeURIComponent(campus)}`, {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.staff && data.staff.length > 0) {
            data.staff.forEach(staff => {
                const option = document.createElement('option');
                option.value = staff.id;
                option.textContent = `${staff.name}${staff.emp_id ? ' (' + staff.emp_id + ')' : ''}`;
                staffSelect.appendChild(option);
            });
        }
    })
    .catch(error => {
        console.error('Error loading staff:', error);
    });
}

document.getElementById('filterForm').addEventListener('submit', function (e) {
    const campus = document.getElementById('filter_campus').value;
    const staffId = document.getElementById('staff_id').value;
    const month = document.getElementById('filter_month').value;
    const year = document.getElementById('filter_year').value;
    if (!campus && !staffId && !month && !year) {
        e.preventDefault();
        alert('Please select at least one filter (Campus, Staff, Month, or Year).');
    }
});
</script>
@endsection
