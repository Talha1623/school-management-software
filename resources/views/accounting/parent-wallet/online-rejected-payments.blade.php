@extends('layouts.app')

@section('title', 'Online Rejected Payments')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-3 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0 fs-16 fw-semibold">Online Rejected Payments</h4>
            </div>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
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
                    <!-- Export Buttons -->
                    <div class="d-flex gap-2">
                        <a href="{{ route('accounting.parent-wallet.online-rejected-payments.export', ['format' => 'excel']) }}{{ request()->has('search') ? '?search=' . request('search') : '' }}" class="btn btn-sm px-2 py-1 export-btn excel-btn">
                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">description</span>
                            <span>Excel</span>
                        </a>
                        <a href="{{ route('accounting.parent-wallet.online-rejected-payments.export', ['format' => 'csv']) }}{{ request()->has('search') ? '?search=' . request('search') : '' }}" class="btn btn-sm px-2 py-1 export-btn csv-btn">
                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">file_present</span>
                            <span>CSV</span>
                        </a>
                        <a href="{{ route('accounting.parent-wallet.online-rejected-payments.export', ['format' => 'pdf']) }}{{ request()->has('search') ? '?search=' . request('search') : '' }}" class="btn btn-sm px-2 py-1 export-btn pdf-btn" target="_blank">
                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">picture_as_pdf</span>
                            <span>PDF</span>
                        </a>
                    </div>
                    
                    <!-- Search -->
                    <div class="d-flex align-items-center gap-2">
                        <label for="searchInput" class="mb-0 fs-13 fw-medium text-dark">Search:</label>
                        <div class="input-group input-group-sm search-input-group" style="width: 280px;">
                            <span class="input-group-text bg-light border-end-0" style="background-color: #f0f4ff !important; border-color: #e0e7ff; padding: 4px 8px;">
                                <span class="material-symbols-outlined" style="font-size: 14px; color: #003471;">search</span>
                            </span>
                            <input type="text" id="searchInput" class="form-control border-start-0 border-end-0" placeholder="Search rejected payments..." value="{{ request('search') }}" onkeypress="handleSearchKeyPress(event)" oninput="handleSearchInput(event)" style="padding: 4px 8px; font-size: 13px;">
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
                    <span class="material-symbols-outlined" style="font-size: 18px;">payments</span>
                    <span>Online Rejected Payments</span>
                </h5>
            </div>

            <!-- Search Results Info -->
            @if(request('search'))
                <div class="search-results-info">
                    <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle; color: #003471;">search</span>
                    <strong>Search Results:</strong> Showing results for "<strong>{{ request('search') }}</strong>"
                    ({{ $payments->total() }} {{ Str::plural('result', $payments->total()) }} found)
                    <a href="{{ route('accounting.parent-wallet.online-rejected-payments') }}" class="text-decoration-none ms-2" style="color: #003471;">
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
                                <th>Payment ID</th>
                                <th>Student Code</th>
                                <th>Parent</th>
                                <th>Paid Amount</th>
                                <th>Expected Amount</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Remarks</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($payments as $index => $payment)
                                <tr>
                                    <td>{{ $index + 1 + (($payments->currentPage() - 1) * $payments->perPage()) }}</td>
                                    <td>{{ $payment->payment_id ?? $payment->id }}</td>
                                    <td>{{ $payment->student_code ?? 'N/A' }}</td>
                                    <td>{{ $payment->parent_name ?? ($payment->student->father_name ?? 'N/A') }}</td>
                                    <td>{{ number_format($payment->paid_amount ?? 0, 2) }}</td>
                                    <td>{{ number_format($payment->expected_amount ?? 0, 2) }}</td>
                                    <td>{{ $payment->payment_date ? $payment->payment_date->format('d M Y') : 'N/A' }}</td>
                                    <td>
                                        <span class="badge bg-danger">{{ $payment->status ?? 'Rejected' }}</span>
                                    </td>
                                    <td>{{ $payment->remarks ?? 'N/A' }}</td>
                                    <td class="text-end">
                                        <form action="{{ route('accounting.parent-wallet.online-rejected-payments.delete', $payment) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this record?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger px-2 py-1" title="Delete">
                                                <span class="material-symbols-outlined" style="font-size: 14px; color: white;">delete</span>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="text-center text-muted py-4">No rejected payments found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if($payments->hasPages())
                <div class="mt-3">
                    {{ $payments->links() }}
                </div>
            @endif
        </div>
    </div>
</div>

<style>
.default-table-area {
    border: 1px solid #e9ecef;
    border-radius: 8px;
    overflow: hidden;
    background-color: white;
}

.default-table-area table thead {
    background: linear-gradient(135deg, #003471 0%, #004a9f 100%);
    color: white;
}

.default-table-area table thead th {
    border: none;
    padding: 8px 12px;
    font-weight: 600;
    font-size: 13px;
    white-space: nowrap;
}

.default-table-area table tbody td {
    padding: 8px 12px;
    font-size: 13px;
    vertical-align: middle;
    border-bottom: 1px solid #e9ecef;
}

.search-results-info {
    padding: 10px 15px;
    background-color: #e7f3ff;
    border-left: 4px solid #003471;
    border-radius: 6px;
    margin-bottom: 15px;
    font-size: 13px;
    color: #003471;
}

.export-btn {
    border-radius: 6px;
    font-size: 12px;
    font-weight: 500;
    transition: all 0.3s ease;
    border: none;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    height: 32px;
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
    background-color: #ff9800;
    color: white;
}

.csv-btn:hover {
    background-color: #f57c00;
    color: white;
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(255, 152, 0, 0.3);
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
</style>

<script>
function performSearch() {
    const searchInput = document.getElementById('searchInput');
    const searchValue = searchInput.value.trim();
    const url = new URL(window.location.href);

    if (searchValue) {
        url.searchParams.set('search', searchValue);
    } else {
        url.searchParams.delete('search');
    }

    url.searchParams.set('page', '1');
    window.location.href = url.toString();
}

function handleSearchKeyPress(event) {
    if (event.key === 'Enter') {
        event.preventDefault();
        performSearch();
    }
}

function handleSearchInput() {}

function clearSearch() {
    const url = new URL(window.location.href);
    url.searchParams.delete('search');
    url.searchParams.set('page', '1');
    window.location.href = url.toString();
}

function updateEntriesPerPage(value) {
    const url = new URL(window.location.href);
    url.searchParams.set('per_page', value);
    url.searchParams.set('page', '1');
    window.location.href = url.toString();
}
</script>
@endsection

