@extends('layouts.app')

@section('title', 'Send Message History')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-3 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0 fs-16 fw-semibold">Send Message History</h4>
            </div>

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
                        <button type="button" class="btn btn-sm px-2 py-1 export-btn excel-btn" onclick="exportToExcel()">
                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">description</span>
                            <span>Excel</span>
                        </button>
                        <button type="button" class="btn btn-sm px-2 py-1 export-btn pdf-btn" onclick="exportToPDF()">
                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">picture_as_pdf</span>
                            <span>PDF</span>
                        </button>
                        <button type="button" class="btn btn-sm px-2 py-1 export-btn print-btn" onclick="printTable()">
                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">print</span>
                            <span>Print</span>
                        </button>
                    </div>
                    
                    <!-- Search -->
                    <div class="d-flex align-items-center gap-2">
                        <label for="searchInput" class="mb-0 fs-13 fw-medium text-dark">Search:</label>
                        <div class="input-group input-group-sm search-input-group" style="width: 280px;">
                            <span class="input-group-text bg-light border-end-0" style="background-color: #f0f4ff !important; border-color: #e0e7ff; padding: 4px 8px;">
                                <span class="material-symbols-outlined" style="font-size: 14px; color: #003471;">search</span>
                            </span>
                            <input type="text" id="searchInput" class="form-control border-start-0 border-end-0" placeholder="Search message history..." value="{{ request('search') }}" onkeypress="handleSearchKeyPress(event)" oninput="handleSearchInput(event)" style="padding: 4px 8px; font-size: 13px;">
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
                    <span class="material-symbols-outlined" style="font-size: 18px;">history</span>
                    <span>Message History</span>
                </h5>
            </div>

            <!-- Search Results Info -->
            @if(request('search'))
                <div class="search-results-info">
                    <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle; color: #003471;">search</span>
                    <strong>Search Results:</strong> Showing results for "<strong>{{ request('search') }}</strong>"
                    <a href="{{ route('whatsapp.history') }}" class="text-decoration-none ms-2" style="color: #003471;">
                        <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">close</span>
                        Clear
                    </a>
                </div>
            @endif

            <div class="default-table-area" style="margin-top: 0;">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Date & Time</th>
                                <th>Recipient Type</th>
                                <th>Message Type</th>
                                <th>Recipient</th>
                                <th>Message</th>
                                <th>Status</th>
                                <th>Characters</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if(isset($paginator) && $paginator->count() > 0)
                                @forelse($paginator as $message)
                                    <tr>
                                        <td>{{ $loop->iteration + (($paginator->currentPage() - 1) * $paginator->perPage()) }}</td>
                                        <td>{{ $message->created_at ?? 'N/A' }}</td>
                                        <td>
                                            <span class="badge bg-info text-white">{{ $message->recipient_type ?? 'N/A' }}</span>
                                        </td>
                                        <td>
                                            <span class="badge bg-primary text-white">{{ $message->message_type ?? 'N/A' }}</span>
                                        </td>
                                        <td>{{ $message->recipient ?? 'N/A' }}</td>
                                        <td>{{ $message->message ? (strlen($message->message) > 50 ? substr($message->message, 0, 50) . '...' : $message->message) : 'N/A' }}</td>
                                        <td>
                                            <span class="badge bg-success text-white">{{ $message->status ?? 'Sent' }}</span>
                                        </td>
                                        <td>{{ $message->characters ?? strlen($message->message ?? '') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-5">
                                            <span class="material-symbols-outlined" style="font-size: 48px; opacity: 0.3;">inbox</span>
                                            <p class="mt-2 mb-0">No message history found.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            @else
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-5">
                                        <span class="material-symbols-outlined" style="font-size: 48px; opacity: 0.3;">inbox</span>
                                        <p class="mt-2 mb-0">No message history found.</p>
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>

            @if(isset($paginator) && $paginator->hasPages())
                <div class="mt-3">
                    {{ $paginator->links() }}
                </div>
            @endif
        </div>
    </div>
</div>

<style>
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

.print-btn {
    background-color: #2196f3;
    color: white;
}

.print-btn:hover {
    background-color: #0b7dda;
    color: white;
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(33, 150, 243, 0.3);
}

.search-input-group {
    border-radius: 8px;
    overflow: hidden;
    border: 1px solid #dee2e6;
    transition: all 0.3s ease;
    height: 32px;
}

.search-input-group:focus-within {
    border-color: #003471;
    box-shadow: 0 0 0 3px rgba(0, 52, 113, 0.15);
}

.search-input-group .form-control {
    border: none;
    font-size: 13px;
    height: 32px;
}

.search-btn {
    background: linear-gradient(135deg, #003471 0%, #004a9f 100%);
    color: white;
    border: none;
    padding: 4px 10px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
}

.search-btn:hover {
    background: linear-gradient(135deg, #004a9f 0%, #003471 100%);
    color: white;
    transform: translateY(-1px);
    box-shadow: 0 2px 6px rgba(0, 52, 113, 0.3);
}

.search-results-info {
    padding: 8px 12px;
    background-color: #e7f3ff;
    border-left: 3px solid #003471;
    border-radius: 4px;
    margin-bottom: 15px;
    font-size: 13px;
}

.default-table-area table {
    margin-bottom: 0;
    border-spacing: 0;
    border-collapse: collapse;
    border: 1px solid #dee2e6;
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

.default-table-area table tbody td {
    padding: 5px 10px;
    font-size: 12px;
    vertical-align: middle;
    border: 1px solid #dee2e6;
    line-height: 1.3;
    height: 32px;
}

.default-table-area table tbody tr:hover {
    background-color: #f8f9fa;
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

function printTable() {
    window.print();
}

function exportToExcel() {
    // Export functionality can be added later
    alert('Excel export functionality will be implemented');
}

function exportToPDF() {
    // Export functionality can be added later
    alert('PDF export functionality will be implemented');
}
</script>
@endsection
