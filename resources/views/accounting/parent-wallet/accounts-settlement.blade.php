@extends('layouts.app')

@section('title', 'Accounts Settlement')

@section('content')
<div class="row accounts-settlement-page">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-3 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0 fs-16 fw-semibold">Accounts Settlement</h4>
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

                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <div class="d-flex gap-2">
                        <a href="javascript:void(0)" class="btn btn-sm px-2 py-1 export-btn excel-btn" onclick="exportAccounts('excel')">
                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">description</span>
                            <span>Excel</span>
                        </a>
                        <a href="javascript:void(0)" class="btn btn-sm px-2 py-1 export-btn csv-btn" onclick="exportAccounts('csv')">
                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">file_present</span>
                            <span>CSV</span>
                        </a>
                        <a href="javascript:void(0)" class="btn btn-sm px-2 py-1 export-btn pdf-btn" onclick="exportAccounts('pdf')">
                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">picture_as_pdf</span>
                            <span>PDF</span>
                        </a>
                        <button type="button" class="btn btn-sm px-2 py-1 export-btn print-btn" onclick="exportAccounts('print')">
                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">print</span>
                            <span>Print</span>
                        </button>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <label for="searchInput" class="mb-0 fs-13 fw-medium text-dark">Search:</label>
                        <div class="input-group input-group-sm search-input-group" style="width: 280px;">
                            <span class="input-group-text bg-light border-end-0" style="background-color: #f0f4ff !important; border-color: #e0e7ff; padding: 4px 8px;">
                                <span class="material-symbols-outlined" style="font-size: 14px; color: #003471;">search</span>
                            </span>
                            <input type="text" id="searchInput" class="form-control border-start-0 border-end-0" placeholder="Search accounts..." value="{{ request('search') }}" onkeypress="handleSearchKeyPress(event)" oninput="handleSearchInput(event)" style="padding: 4px 8px; font-size: 13px;">
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

            <div class="mb-2 p-2 rounded-8" style="background: linear-gradient(135deg, #003471 0%, #004a9f 100%);">
                <h5 class="mb-0 text-white fs-15 fw-semibold d-flex align-items-center gap-2">
                    <span class="material-symbols-outlined" style="font-size: 18px;">account_balance_wallet</span>
                    <span>Accounts Settlement List</span>
                </h5>
            </div>

            <div class="default-table-area" style="margin-top: 0;">
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Type</th>
                                <th>Date</th>
                                <th>Balance</th>
                                <th>Income</th>
                                <th>Expense</th>
                                <th>Total</th>
                                <th>Method</th>
                                <th>Trx#</th>
                                <th>Remarks</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($items as $item)
                                <tr>
                                    <td><strong class="text-primary">{{ $item['user'] }}</strong></td>
                                    <td>
                                        <span class="badge {{ $item['type'] === 'Income' ? 'bg-success' : 'bg-danger' }} text-white">{{ $item['type'] }}</span>
                                    </td>
                                    <td>{{ $item['date'] ?? 'N/A' }}</td>
                                    <td><span class="text-muted">{{ number_format($item['balance'], 2) }}</span></td>
                                    <td><span class="text-success">{{ number_format($item['income'], 2) }}</span></td>
                                    <td><span class="text-danger">{{ number_format($item['expense'], 2) }}</span></td>
                                    <td><span class="text-dark">{{ number_format($item['total'], 2) }}</span></td>
                                    <td>{{ $item['method'] }}</td>
                                    <td>{{ $item['trx'] }}</td>
                                    <td>{{ $item['remarks'] }}</td>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-sm btn-info px-2 py-1" data-bs-toggle="modal" data-bs-target="#viewModal"
                                            data-user="{{ $item['user'] }}"
                                            data-type="{{ $item['type'] }}"
                                            data-date="{{ $item['date'] }}"
                                            data-balance="{{ number_format($item['balance'], 2) }}"
                                            data-income="{{ number_format($item['income'], 2) }}"
                                            data-expense="{{ number_format($item['expense'], 2) }}"
                                            data-total="{{ number_format($item['total'], 2) }}"
                                            data-method="{{ $item['method'] }}"
                                            data-trx="{{ $item['trx'] }}"
                                            data-remarks="{{ $item['remarks'] }}"
                                            onclick="openViewModal(this)">
                                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">visibility</span>
                                            View
                                        </button>
                                        <form action="{{ route('accounting.parent-wallet.accounts-settlement.delete', [$item['source_type'], $item['source_id']]) }}" method="POST" style="display: inline-block;" onsubmit="return confirm('Are you sure you want to delete this record?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger px-2 py-1">
                                                <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">delete</span>
                                                Delete
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="11" class="text-center text-muted">No records found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($items->hasPages())
                    <div class="d-flex justify-content-between align-items-center mt-3 px-3">
                        <div class="text-muted" style="font-size: 13px;">
                            Showing {{ $items->firstItem() }} to {{ $items->lastItem() }} of {{ $items->total() }} records
                        </div>
                        <div>
                            {{ $items->links() }}
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- View Modal -->
<div class="modal fade" id="viewModal" tabindex="-1" aria-labelledby="viewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #003471;">
                <h5 class="modal-title text-white" id="viewModalLabel">Record Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row g-2">
                    <div class="col-6"><strong>User:</strong> <span id="viewUser"></span></div>
                    <div class="col-6"><strong>Type:</strong> <span id="viewType"></span></div>
                    <div class="col-6"><strong>Date:</strong> <span id="viewDate"></span></div>
                    <div class="col-6"><strong>Balance:</strong> <span id="viewBalance"></span></div>
                    <div class="col-6"><strong>Income:</strong> <span id="viewIncome"></span></div>
                    <div class="col-6"><strong>Expense:</strong> <span id="viewExpense"></span></div>
                    <div class="col-6"><strong>Total:</strong> <span id="viewTotal"></span></div>
                    <div class="col-6"><strong>Method:</strong> <span id="viewMethod"></span></div>
                    <div class="col-6"><strong>Trx#:</strong> <span id="viewTrx"></span></div>
                    <div class="col-12"><strong>Remarks:</strong> <span id="viewRemarks"></span></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Close</button>
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

    .accounts-settlement-page .swiper-button-next,
    .accounts-settlement-page .swiper-button-prev,
    .accounts-settlement-page .carousel-control-next,
    .accounts-settlement-page .carousel-control-prev {
        display: none !important;
    }
</style>

<script>
function updateEntriesPerPage(value) {
    const url = new URL(window.location.href);
    url.searchParams.set('per_page', value);
    url.searchParams.delete('page');
    window.location.href = url.toString();
}

function handleSearchInput(event) {
    if (event.target.value === '') {
        const url = new URL(window.location.href);
        url.searchParams.delete('search');
        url.searchParams.delete('page');
        window.location.href = url.toString();
    }
}

function handleSearchKeyPress(event) {
    if (event.key === 'Enter') {
        performSearch();
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

function exportAccounts(type) {
    if (type === 'print' || type === 'pdf') {
        window.print();
        return;
    }

    const rows = Array.from(document.querySelectorAll('.default-table-area table tr'));
    const csv = rows.map(row => {
        const cols = Array.from(row.querySelectorAll('th,td')).map(col => {
            return `"${(col.innerText || '').replace(/"/g, '""')}"`;
        });
        return cols.join(',');
    }).join('\n');

    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = `accounts-settlement.${type === 'excel' ? 'csv' : 'csv'}`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);
}

function openViewModal(button) {
    document.getElementById('viewUser').textContent = button.dataset.user || '';
    document.getElementById('viewType').textContent = button.dataset.type || '';
    document.getElementById('viewDate').textContent = button.dataset.date || '';
    document.getElementById('viewBalance').textContent = button.dataset.balance || '';
    document.getElementById('viewIncome').textContent = button.dataset.income || '';
    document.getElementById('viewExpense').textContent = button.dataset.expense || '';
    document.getElementById('viewTotal').textContent = button.dataset.total || '';
    document.getElementById('viewMethod').textContent = button.dataset.method || '';
    document.getElementById('viewTrx').textContent = button.dataset.trx || '';
    document.getElementById('viewRemarks').textContent = button.dataset.remarks || '';
}
</script>
@endsection

