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
                @if(!empty($filterStaffId))
                    <input type="hidden" name="staff_id" value="{{ $filterStaffId }}">
                @endif
                <div class="row g-2 mb-3 align-items-end">
                    <!-- Campus -->
                    <div class="col-md-2">
                        <label for="filter_campus" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Campus</label>
                        <select class="form-select form-select-sm" id="filter_campus" name="filter_campus" onchange="loadStaffByCampus(); loadSalaryRecords();" style="height: 32px;">
                            <option value="">All Campuses</option>
                            @foreach($campuses as $campus)
                                <option value="{{ $campus }}" {{ $filterCampus == $campus ? 'selected' : '' }}>{{ $campus }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Staff -->
                    <div class="col-md-2">
                        <label for="staff_id" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Staff</label>
                        <select class="form-select form-select-sm" id="staff_id" name="staff_id" onchange="loadSalaryRecords();" style="height: 32px;">
                            <option value="">All Staff</option>
                            @if($filterCampus)
                                @php
                                    $staffList = \App\Models\Staff::where('campus', $filterCampus)->orderBy('name')->get(['id', 'name', 'emp_id']);
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
                        <select class="form-select form-select-sm" id="filter_month" name="filter_month" onchange="loadSalaryRecords();" style="height: 32px;">
                            <option value="">All Months</option>
                            @foreach($months as $monthValue => $monthName)
                                <option value="{{ $monthValue }}" {{ $filterMonth == $monthValue ? 'selected' : '' }}>{{ $monthName }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Year -->
                    <div class="col-md-2">
                        <label for="filter_year" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Year</label>
                        <select class="form-select form-select-sm" id="filter_year" name="filter_year" onchange="loadSalaryRecords();" style="height: 32px;">
                            <option value="">All Years</option>
                            @foreach($years as $year)
                                <option value="{{ $year }}" {{ $filterYear == $year ? 'selected' : '' }}>{{ $year }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Filter Button -->
                    <div class="col-md-2">
                        <button type="button" class="btn btn-sm py-1 px-3 rounded-8 filter-btn w-100" onclick="loadSalaryRecords();" style="height: 32px;">
                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">filter_alt</span>
                            <span style="font-size: 12px;">Filter</span>
                        </button>
                    </div>
                </div>
            </form>

            <!-- Results Table -->
            <div class="mt-3" id="resultsContainer" style="display: none;">
                <div class="mb-2 p-2 rounded-8" style="background: linear-gradient(135deg, #003471 0%, #004a9f 100%);">
                    <h5 class="mb-0 text-white fs-15 fw-semibold d-flex align-items-center gap-2">
                        <span class="material-symbols-outlined" style="font-size: 18px;">list</span>
                        <span>Staff Salary Reports</span>
                    </h5>
                </div>

                <div class="default-table-area" style="margin-top: 0;">
                    <div class="table-responsive">
                        <div id="loadingSpinner" class="text-center py-4" style="display: none;">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
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
                            <tbody id="salaryTableBody">
                                <!-- Data will be loaded dynamically -->
                            </tbody>
                            <tfoot id="salaryTableFooter" style="display: none;">
                                <!-- Footer will be loaded dynamically -->
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
            <div id="emptyState" class="text-center py-5">
                <span class="material-symbols-outlined text-muted" style="font-size: 64px;">filter_alt</span>
                <p class="text-muted mt-3 mb-0">Please apply filters to view staff salary reports</p>
            </div>
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

// Load salary records dynamically
function loadSalaryRecords() {
    const campus = document.getElementById('filter_campus').value;
    const staffId = document.getElementById('staff_id').value;
    const month = document.getElementById('filter_month').value;
    const year = document.getElementById('filter_year').value;
    
    const resultsContainer = document.getElementById('resultsContainer');
    const emptyState = document.getElementById('emptyState');
    const loadingSpinner = document.getElementById('loadingSpinner');
    const tableBody = document.getElementById('salaryTableBody');
    const tableFooter = document.getElementById('salaryTableFooter');
    
    // Show loading
    loadingSpinner.style.display = 'block';
    tableBody.innerHTML = '';
    tableFooter.style.display = 'none';
    
    // Build query string
    const params = new URLSearchParams();
    if (campus) params.append('filter_campus', campus);
    if (staffId) params.append('staff_id', staffId);
    if (month) params.append('filter_month', month);
    if (year) params.append('filter_year', year);
    
    fetch(`{{ route('reports.staff-salary.get-records') }}?${params.toString()}`, {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        loadingSpinner.style.display = 'none';
        
        if (data.success && data.records && data.records.length > 0) {
            resultsContainer.style.display = 'block';
            emptyState.style.display = 'none';
            
            // Build table rows
            let html = '';
            data.records.forEach((record, index) => {
                const photoHtml = record.photo 
                    ? `<img src="/storage/${record.photo}" alt="${record.staff_name}" class="rounded-circle" style="width: 40px; height: 40px; object-fit: cover;">`
                    : `<div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;"><span class="text-white fw-bold">${record.staff_name.charAt(0)}</span></div>`;
                
                const statusBadge = record.status == 'Paid' 
                    ? '<span class="badge bg-success">Paid</span>'
                    : record.status == 'Partial'
                    ? '<span class="badge bg-warning text-dark">Partial</span>'
                    : '<span class="badge bg-danger">Pending</span>';
                
                html += `
                    <tr>
                        <td>${index + 1}</td>
                        <td>${photoHtml}</td>
                        <td>${escapeHtml(record.staff_name)}</td>
                        <td>${escapeHtml(record.emp_id)}</td>
                        <td>${escapeHtml(record.campus)}</td>
                        <td>${escapeHtml(record.designation)}</td>
                        <td>${escapeHtml(record.salary_month)}</td>
                        <td>${record.year}</td>
                        <td>${record.present}</td>
                        <td>${record.absent}</td>
                        <td>${record.late}</td>
                        <td>${record.early_exit || 0}</td>
                        <td>${record.basic}</td>
                        <td>${record.salary_generated}</td>
                        <td>${record.amount_paid}</td>
                        <td>${record.loan_repayment}</td>
                        <td>${statusBadge}</td>
                    </tr>
                `;
            });
            tableBody.innerHTML = html;
            
            // Build footer
            tableFooter.innerHTML = `
                <tr class="fw-bold" style="background-color: #f8f9fa;">
                    <td colspan="12" class="text-end">Total Basic:</td>
                    <td>${data.total_basic}</td>
                    <td>${data.total_salary_generated}</td>
                    <td>${data.total_amount_paid}</td>
                    <td>${data.total_loan_repayment}</td>
                    <td></td>
                </tr>
            `;
            tableFooter.style.display = '';
        } else {
            resultsContainer.style.display = 'none';
            emptyState.style.display = 'block';
        }
    })
    .catch(error => {
        console.error('Error loading salary records:', error);
        loadingSpinner.style.display = 'none';
        tableBody.innerHTML = '<tr><td colspan="17" class="text-center py-4"><div class="alert alert-danger">Error loading data. Please try again.</div></td></tr>';
    });
}

// Escape HTML
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Load records on page load if filters are applied
document.addEventListener('DOMContentLoaded', function() {
    @if(request()->hasAny(['filter_campus', 'filter_month', 'filter_year', 'staff_id']))
        loadSalaryRecords();
    @endif
});
</script>
@endsection
