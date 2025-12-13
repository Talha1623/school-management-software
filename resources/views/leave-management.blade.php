@extends('layouts.app')

@section('title', 'Leave Management')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-3 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0 fs-16 fw-semibold">Leave Management</h4>
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

            <!-- Table Toolbar -->
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-3 p-3 rounded-8" style="background-color: #f8f9fa; border: 1px solid #e9ecef;">
                <!-- Left Side -->
                <div class="d-flex align-items-center gap-3 flex-wrap">
                    <div class="d-flex align-items-center gap-2">
                        <label for="entriesPerPage" class="mb-0 fs-13 fw-medium text-dark">Show:</label>
                        <select id="entriesPerPage" class="form-select form-select-sm" style="width: auto; min-width: 70px;" onchange="updateEntriesPerPage(this.value)">
                            <option value="10" {{ request('per_page', 10) == 10 ? 'selected' : '' }}>10</option>
                            <option value="25" {{ request('per_page', 25) == 25 ? 'selected' : '' }}>25</option>
                            <option value="50" {{ request('per_page', 50) == 50 ? 'selected' : '' }}>50</option>
                            <option value="100" {{ request('per_page', 100) == 100 ? 'selected' : '' }}>100</option>
                        </select>
                    </div>
                </div>

                <!-- Right Side -->
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <!-- Search -->
                    <div class="d-flex align-items-center gap-2">
                        <label for="searchInput" class="mb-0 fs-13 fw-medium text-dark">Search:</label>
                        <div class="input-group input-group-sm search-input-group" style="width: 280px;">
                            <span class="input-group-text bg-light border-end-0" style="background-color: #f0f4ff !important; border-color: #e0e7ff; padding: 2px 8px; height: 28px;">
                                <span class="material-symbols-outlined" style="font-size: 14px; color: #003471;">search</span>
                            </span>
                            <input type="text" id="searchInput" class="form-control border-start-0 border-end-0" placeholder="Search leaves..." value="{{ request('search') }}" onkeypress="handleSearchKeyPress(event)" oninput="handleSearchInput(event)" style="padding: 2px 8px; font-size: 13px; height: 28px;">
                            @if(request('search'))
                                <button class="btn btn-outline-secondary border-start-0 border-end-0" type="button" onclick="clearSearch()" title="Clear search" style="padding: 2px 8px; height: 28px;">
                                    <span class="material-symbols-outlined" style="font-size: 14px;">close</span>
                                </button>
                            @endif
                            <button class="btn btn-sm search-btn" type="button" onclick="performSearch()" title="Search" style="padding: 2px 10px; height: 28px;">
                                <span class="material-symbols-outlined" style="font-size: 14px;">search</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Table Header -->
            <div class="mb-2 p-2 rounded-8" style="background: linear-gradient(135deg, #003471 0%, #004a9f 100%);">
                <h5 class="mb-0 text-white fs-15 fw-semibold d-flex align-items-center gap-2">
                    <span class="material-symbols-outlined" style="font-size: 18px;">list</span>
                    <span>Leaves List</span>
                </h5>
            </div>

            <!-- Search Results Info -->
            @if(request('search'))
                <div class="search-results-info">
                    <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle; color: #003471;">search</span>
                    <strong>Search Results:</strong> Showing results for "<strong>{{ request('search') }}</strong>"
                    @if(isset($leaves))
                        ({{ $leaves->total() }} {{ Str::plural('result', $leaves->total()) }} found)
                    @endif
                    <a href="{{ route('leave-management') }}" class="text-decoration-none ms-2" style="color: #003471;">
                        <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">close</span>
                        Clear
                    </a>
                </div>
            @endif

            <div class="default-table-area" style="margin-top: 0;">
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Type</th>
                                <th>Name</th>
                                <th>Leave Reason</th>
                                <th>From Date</th>
                                <th>To Date</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if(isset($leaves) && $leaves->count() > 0)
                                @forelse($leaves as $leave)
                                    <tr>
                                        <td>{{ $loop->iteration + (($leaves->currentPage() - 1) * $leaves->perPage()) }}</td>
                                        <td>
                                            @if($leave->student_id)
                                                <span class="badge bg-info text-white">Student</span>
                                            @else
                                                <span class="badge bg-secondary text-white">Staff</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($leave->student_id)
                                                <strong class="text-success">
                                                    {{ $leave->student->student_name ?? 'N/A' }}
                                                    @if($leave->student)
                                                        <br><small class="text-muted">{{ $leave->student->student_code ?? '' }} - {{ $leave->student->class ?? '' }} {{ $leave->student->section ?? '' }}</small>
                                                    @endif
                                                </strong>
                                            @else
                                                <strong class="text-primary">{{ $leave->staff->name ?? 'N/A' }}</strong>
                                            @endif
                                        </td>
                                        <td>{{ $leave->leave_reason }}</td>
                                        <td>{{ $leave->from_date->format('d M Y') }}</td>
                                        <td>{{ $leave->to_date->format('d M Y') }}</td>
                                        <td>
                                            @if($leave->status == 'Pending')
                                                <span class="badge bg-warning text-dark">{{ $leave->status }}</span>
                                            @elseif($leave->status == 'Approved')
                                                <span class="badge bg-success text-white">{{ $leave->status }}</span>
                                            @elseif($leave->status == 'Rejected')
                                                <span class="badge bg-danger text-white">{{ $leave->status }}</span>
                                            @else
                                                <span class="badge bg-info text-white">{{ $leave->status }}</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            <div class="d-inline-flex gap-1">
                                                @if($leave->status == 'Pending')
                                                    <button type="button" class="btn btn-sm btn-success px-2 py-0" title="Approve" onclick="updateStatus({{ $leave->id }}, 'Approved')">
                                                        <span class="material-symbols-outlined" style="font-size: 14px; color: white;">check</span>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-danger px-2 py-0" title="Reject" onclick="updateStatus({{ $leave->id }}, 'Rejected')">
                                                        <span class="material-symbols-outlined" style="font-size: 14px; color: white;">close</span>
                                                    </button>
                                                @endif
                                                <button type="button" class="btn btn-sm btn-danger px-2 py-0" title="Delete" onclick="if(confirm('Are you sure you want to delete this leave application?')) { document.getElementById('delete-form-{{ $leave->id }}').submit(); }">
                                                    <span class="material-symbols-outlined" style="font-size: 14px; color: white;">delete</span>
                                                </button>
                                                <form id="delete-form-{{ $leave->id }}" action="{{ route('leave-management.destroy', $leave->id) }}" method="POST" class="d-none">
                                                    @csrf
                                                    @method('DELETE')
                                                </form>
                                                <form id="status-form-{{ $leave->id }}" action="{{ route('leave-management.update', $leave->id) }}" method="POST" class="d-none">
                                                    @csrf
                                                    @method('PUT')
                                                    <input type="hidden" name="staff_id" value="{{ $leave->staff_id }}">
                                                    <input type="hidden" name="student_id" value="{{ $leave->student_id }}">
                                                    <input type="hidden" name="leave_reason" value="{{ $leave->leave_reason }}">
                                                    <input type="hidden" name="from_date" value="{{ $leave->from_date->format('Y-m-d') }}">
                                                    <input type="hidden" name="to_date" value="{{ $leave->to_date->format('Y-m-d') }}">
                                                    <input type="hidden" name="status" id="status-{{ $leave->id }}" value="">
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-5">
                                            <span class="material-symbols-outlined" style="font-size: 48px; opacity: 0.3;">inbox</span>
                                            <p class="mt-2 mb-0">No leaves found.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            @else
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-5">
                                        <span class="material-symbols-outlined" style="font-size: 48px; opacity: 0.3;">inbox</span>
                                        <p class="mt-2 mb-0">No leaves found.</p>
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>

            @if(isset($leaves) && $leaves->hasPages())
                <div class="mt-3">
                    {{ $leaves->links() }}
                </div>
            @endif
        </div>
    </div>
</div>

<style>
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

.search-input-group {
    border-radius: 8px;
    overflow: hidden;
    border: 1px solid #dee2e6;
    transition: all 0.3s ease;
}

.search-input-group:focus-within {
    border-color: #003471;
    box-shadow: 0 0 0 3px rgba(0, 52, 113, 0.15);
}

.search-results-info {
    padding: 8px 12px;
    background-color: #e7f3ff;
    border-left: 4px solid #003471;
    border-radius: 4px;
    margin-bottom: 15px;
    font-size: 13px;
    color: #003471;
}
</style>

<script>
function updateEntriesPerPage(value) {
    const url = new URL(window.location.href);
    url.searchParams.set('per_page', value);
    window.location.href = url.toString();
}

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

function updateStatus(leaveId, status) {
    if (confirm(`Are you sure you want to ${status.toLowerCase()} this leave request?`)) {
        document.getElementById('status-' + leaveId).value = status;
        document.getElementById('status-form-' + leaveId).submit();
    }
}
</script>
@endsection
