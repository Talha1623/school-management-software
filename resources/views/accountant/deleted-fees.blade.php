@extends('layouts.accountant')

@section('title', 'Deleted Fees - Accountant')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-3 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0 fs-16 fw-semibold">Deleted Fees</h4>
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
                    <div class="d-flex align-items-center gap-2">
                        <label for="filter_campus" class="mb-0 fs-13 fw-medium text-dark">Campus:</label>
                        <select id="filter_campus" class="form-select form-select-sm" style="width: auto; min-width: 180px;" onchange="updateCampusFilter(this.value)">
                            <option value="">All Campuses</option>
                            @foreach($campuses as $campus)
                                @php($campusName = $campus->campus_name ?? $campus)
                                <option value="{{ $campusName }}" {{ ($filterCampus ?? '') === $campusName ? 'selected' : '' }}>
                                    {{ $campusName }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Right Side -->
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <!-- Search -->
                    <div class="d-flex align-items-center gap-2">
                        <label for="searchInput" class="mb-0 fs-13 fw-medium text-dark">Search:</label>
                        <div class="input-group input-group-sm search-input-group" style="width: 280px;">
                            <span class="input-group-text bg-light border-end-0" style="background-color: #f0f4ff !important; border-color: #e0e7ff; padding: 4px 8px;">
                                <span class="material-symbols-outlined" style="font-size: 14px; color: #003471;">search</span>
                            </span>
                            <input type="text" id="searchInput" class="form-control border-start-0 border-end-0" placeholder="Search deleted fees..." value="{{ request('search') }}" onkeypress="handleSearchKeyPress(event)" oninput="handleSearchInput(event)" style="padding: 4px 8px; font-size: 13px;">
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

            <!-- Table Header -->
            <div class="mb-2 p-2 rounded-8" style="background: linear-gradient(135deg, #003471 0%, #004a9f 100%);">
                <h5 class="mb-0 text-white fs-15 fw-semibold d-flex align-items-center gap-2">
                    <span class="material-symbols-outlined" style="font-size: 18px;">delete</span>
                    <span>Deleted Fees List</span>
                </h5>
            </div>

            <!-- Search Results Info -->
            @if(request('search'))
                <div class="search-results-info">
                    <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle; color: #003471;">search</span>
                    <strong>Search Results:</strong> Found {{ $deletedFees->total() }} deleted fee(s) matching "{{ request('search') }}"
                </div>
            @endif

            <div class="default-table-area" style="margin-top: 0;">
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Roll</th>
                                <th>Student</th>
                                <th>Parent</th>
                                <th>Fee Title</th>
                                <th>Amount</th>
                                <th>Deleted By</th>
                                <th>Reason</th>
                                <th>Campus</th>
                                <th>Deleted On</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($deletedFees as $deletedFee)
                                <tr>
                                    <td>{{ $loop->iteration + (($deletedFees->currentPage() - 1) * $deletedFees->perPage()) }}</td>
                                    <td>
                                        <span class="badge bg-info text-white">{{ $deletedFee->student_code }}</span>
                                    </td>
                                    <td>
                                        <strong class="text-primary">{{ $deletedFee->student_name }}</strong>
                                    </td>
                                    <td>
                                        <span class="text-muted">{{ $deletedFee->parent_name ?? 'N/A' }}</span>
                                    </td>
                                    <td>
                                        <span class="text-dark">{{ $deletedFee->payment_title }}</span>
                                    </td>
                                    <td>
                                        <strong class="text-success">₹{{ number_format($deletedFee->payment_amount, 2) }}</strong>
                                    </td>
                                    <td>
                                        <span class="badge bg-warning text-dark">{{ $deletedFee->deleted_by ?? 'N/A' }}</span>
                                    </td>
                                    <td>
                                        <span class="text-muted" style="font-size: 11px;">{{ $deletedFee->reason ? (strlen($deletedFee->reason) > 30 ? substr($deletedFee->reason, 0, 30) . '...' : $deletedFee->reason) : 'N/A' }}</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary text-white">{{ $deletedFee->campus ?? 'N/A' }}</span>
                                    </td>
                                    <td>
                                        <span class="text-muted" style="font-size: 11px;">{{ $deletedFee->deleted_at ? $deletedFee->deleted_at->format('Y-m-d H:i') : 'N/A' }}</span>
                                    </td>
                                    <td class="text-end">
                                        <form action="{{ route('accountant.deleted-fees.restore', $deletedFee->id) }}" method="POST" style="display: inline-block;" onsubmit="return confirm('Are you sure you want to restore this fee?');">
                                            @csrf
                                            @method('POST')
                                            <button type="submit" class="btn btn-sm btn-success px-2 py-1" title="Restore">
                                                <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">restore</span>
                                                <span>Restore</span>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="11" class="text-center py-4">
                                        <div class="d-flex flex-column align-items-center justify-content-center gap-2">
                                            <span class="material-symbols-outlined" style="font-size: 48px; color: #ccc;">delete_outline</span>
                                            <p class="text-muted mb-0">No deleted fees found.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                @if($deletedFees->hasPages())
                    <div class="d-flex justify-content-between align-items-center mt-3 px-3">
                        <div class="text-muted" style="font-size: 13px;">
                            Showing {{ $deletedFees->firstItem() }} to {{ $deletedFees->lastItem() }} of {{ $deletedFees->total() }} deleted fees
                        </div>
                        <div>
                            {{ $deletedFees->links() }}
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<style>
    .search-btn {
        background-color: #003471;
        border-color: #003471;
        color: white;
    }
    
    .search-btn:hover {
        background-color: #004a9f;
        border-color: #004a9f;
        color: white;
    }
    
    .search-input-group .form-control:focus {
        border-color: #003471;
        box-shadow: 0 0 0 0.2rem rgba(0, 52, 113, 0.25);
    }
    
    .default-table-area table {
        margin-bottom: 0;
        border-spacing: 0;
        border-collapse: collapse;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        overflow: hidden;
    }
    
    .default-table-area table thead {
        border-bottom: 1px solid #dee2e6;
        background-color: #f8f9fa;
    }
    
    .default-table-area table thead th {
        padding: 8px 12px;
        font-size: 13px;
        font-weight: 600;
        vertical-align: middle;
        line-height: 1.3;
        white-space: nowrap;
        border: 1px solid #dee2e6;
        background-color: #f8f9fa;
    }
    
    .default-table-area table tbody td {
        padding: 8px 12px;
        font-size: 13px;
        vertical-align: middle;
        line-height: 1.4;
        border: 1px solid #dee2e6;
    }
    
    .default-table-area table tbody tr:hover {
        background-color: #f8f9fa;
    }
    
    .default-table-area .badge {
        font-size: 11px;
        padding: 3px 6px;
        font-weight: 500;
        border-radius: 4px;
    }
    
    .default-table-area .btn-sm {
        font-size: 11px;
        padding: 4px 8px;
        min-height: auto;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        line-height: 1;
    }
    
    .search-results-info {
        padding: 8px 12px;
        background-color: #e7f3ff;
        border-left: 3px solid #003471;
        border-radius: 4px;
        margin-bottom: 15px;
        font-size: 13px;
    }
</style>

<script>
function updateEntriesPerPage(value) {
    const url = new URL(window.location.href);
    url.searchParams.set('per_page', value);
    url.searchParams.delete('page');
    window.location.href = url.toString();
}

function updateCampusFilter(value) {
    const url = new URL(window.location.href);
    if (value) {
        url.searchParams.set('filter_campus', value);
    } else {
        url.searchParams.delete('filter_campus');
    }
    url.searchParams.delete('page');
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
    const searchInput = document.getElementById('searchInput');
    const searchValue = searchInput.value.trim();
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
</script>
@endsection
