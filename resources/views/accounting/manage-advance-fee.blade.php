@extends('layouts.app')

@section('title', 'Manage Advance Fee')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-3 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0 fs-16 fw-semibold">Manage Advance Fee</h4>
                <button type="button" class="btn btn-sm py-2 px-3 d-inline-flex align-items-center gap-1 rounded-8 advance-fee-add-btn" data-bs-toggle="modal" data-bs-target="#advanceFeeModal" onclick="resetForm()">
                    <span class="material-symbols-outlined" style="font-size: 16px;">add</span>
                    <span>Add New Record</span>
                </button>
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
                    <!-- Export Buttons -->
                    <div class="d-flex gap-2">
                        <a href="{{ route('accounting.manage-advance-fee.export', ['format' => 'excel']) }}{{ request()->has('search') ? '?search=' . request('search') : '' }}" class="btn btn-sm px-2 py-1 export-btn excel-btn">
                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">description</span>
                            <span>Excel</span>
                        </a>
                        <a href="{{ route('accounting.manage-advance-fee.export', ['format' => 'csv']) }}{{ request()->has('search') ? '?search=' . request('search') : '' }}" class="btn btn-sm px-2 py-1 export-btn csv-btn">
                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">file_present</span>
                            <span>CSV</span>
                        </a>
                        <a href="{{ route('accounting.manage-advance-fee.export', ['format' => 'pdf']) }}{{ request()->has('search') ? '?search=' . request('search') : '' }}" class="btn btn-sm px-2 py-1 export-btn pdf-btn" target="_blank">
                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">picture_as_pdf</span>
                            <span>PDF</span>
                        </a>
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
                            <input type="text" id="searchInput" class="form-control border-start-0 border-end-0" placeholder="Search records..." value="{{ request('search') }}" onkeypress="handleSearchKeyPress(event)" oninput="handleSearchInput(event)" style="padding: 4px 8px; font-size: 13px;">
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
                    <span class="material-symbols-outlined" style="font-size: 18px;">account_balance_wallet</span>
                    <span>Advance Fee Records</span>
                </h5>
            </div>

            <!-- Search Results Info -->
            @if(request('search'))
                <div class="search-results-info">
                    <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle; color: #003471;">search</span>
                    <strong>Search Results:</strong> Showing results for "<strong>{{ request('search') }}</strong>"
                    @if(isset($advanceFees))
                        ({{ $advanceFees->total() }} {{ Str::plural('result', $advanceFees->total()) }} found)
                    @endif
                    <a href="{{ route('accounting.manage-advance-fee.index') }}" class="text-decoration-none ms-2" style="color: #003471;">
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
                                <th>Parent ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>ID Card Number</th>
                                <th>Available Credit</th>
                                <th>Increase</th>
                                <th>Decrease</th>
                                <th>Childs</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if(isset($advanceFees) && $advanceFees->count() > 0)
                                @forelse($advanceFees as $advanceFee)
                                    <tr>
                                        <td>{{ $loop->iteration + (($advanceFees->currentPage() - 1) * $advanceFees->perPage()) }}</td>
                                        <td>
                                            <strong class="text-primary" style="font-size: 13px;">{{ $advanceFee->parent_id ?? 'N/A' }}</strong>
                                        </td>
                                        <td>
                                            <strong class="text-dark" style="font-size: 13px;">{{ $advanceFee->name }}</strong>
                                        </td>
                                        <td style="font-size: 13px;">{{ $advanceFee->email ?? 'N/A' }}</td>
                                        <td style="font-size: 13px;">{{ $advanceFee->phone ?? 'N/A' }}</td>
                                        <td style="font-size: 13px;">{{ $advanceFee->id_card_number ?? 'N/A' }}</td>
                                        <td>
                                            <span class="badge bg-success text-white" style="font-size: 11px; padding: 3px 6px;">{{ number_format($advanceFee->available_credit, 2) }}</span>
                                        </td>
                                        <td>
                                            <span class="badge bg-info text-white" style="font-size: 11px; padding: 3px 6px;">{{ number_format($advanceFee->increase, 2) }}</span>
                                        </td>
                                        <td>
                                            <span class="badge bg-warning text-white" style="font-size: 11px; padding: 3px 6px;">{{ number_format($advanceFee->decrease, 2) }}</span>
                                        </td>
                                        <td>
                                            <span class="badge bg-primary text-white" style="font-size: 11px; padding: 3px 6px;">{{ $advanceFee->childs }}</span>
                                        </td>
                                        <td class="text-end">
                                            <div class="d-inline-flex gap-1">
                                                <button type="button" class="btn btn-sm btn-primary px-2 py-1" title="Edit" onclick="editAdvanceFee({{ $advanceFee->id }})">
                                                    <span class="material-symbols-outlined" style="font-size: 14px; color: white;">edit</span>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-danger px-2 py-1" title="Delete" onclick="if(confirm('Are you sure you want to delete this record?')) { document.getElementById('delete-form-{{ $advanceFee->id }}').submit(); }">
                                                    <span class="material-symbols-outlined" style="font-size: 14px; color: white;">delete</span>
                                                </button>
                                                <form id="delete-form-{{ $advanceFee->id }}" action="{{ route('accounting.manage-advance-fee.destroy', $advanceFee) }}" method="POST" class="d-none">
                                                    @csrf
                                                    @method('DELETE')
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="11" class="text-center text-muted py-5">
                                            <span class="material-symbols-outlined" style="font-size: 48px; opacity: 0.3;">inbox</span>
                                            <p class="mt-2 mb-0">No advance fee records found.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            @else
                                <tr>
                                    <td colspan="11" class="text-center text-muted py-5">
                                        <span class="material-symbols-outlined" style="font-size: 48px; opacity: 0.3;">inbox</span>
                                        <p class="mt-2 mb-0">No advance fee records found.</p>
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>

            @if(isset($advanceFees) && $advanceFees->hasPages())
                <div class="mt-3">
                    {{ $advanceFees->links() }}
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Advance Fee Modal -->
<div class="modal fade" id="advanceFeeModal" tabindex="-1" aria-labelledby="advanceFeeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
            <div class="modal-header text-white p-3" style="background: linear-gradient(135deg, #003471 0%, #004a9f 100%); border: none;">
                <h5 class="modal-title fs-15 fw-semibold mb-0 d-flex align-items-center gap-2" id="advanceFeeModalLabel" style="color: white !important;">
                    <span class="material-symbols-outlined" style="font-size: 20px; color: white !important;">account_balance_wallet</span>
                    <span style="color: white !important;">Add New Advance Fee Record</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" style="opacity: 0.8;"></button>
            </div>
            <form id="advanceFeeForm" method="POST" action="{{ route('accounting.manage-advance-fee.store') }}">
                @csrf
                <div id="methodField"></div>
                <div class="modal-body p-3">
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Parent ID</label>
                            <div class="input-group input-group-sm advance-fee-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">badge</span>
                                </span>
                                <input type="text" class="form-control advance-fee-input" name="parent_id" id="parent_id" placeholder="Enter parent ID">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Name <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm advance-fee-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">person</span>
                                </span>
                                <input type="text" class="form-control advance-fee-input" name="name" id="name" placeholder="Enter name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Email</label>
                            <div class="input-group input-group-sm advance-fee-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">email</span>
                                </span>
                                <input type="email" class="form-control advance-fee-input" name="email" id="email" placeholder="Enter email">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Phone</label>
                            <div class="input-group input-group-sm advance-fee-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">phone</span>
                                </span>
                                <input type="text" class="form-control advance-fee-input" name="phone" id="phone" placeholder="Enter phone">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">ID Card Number</label>
                            <div class="input-group input-group-sm advance-fee-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">credit_card</span>
                                </span>
                                <input type="text" class="form-control advance-fee-input" name="id_card_number" id="id_card_number" placeholder="Enter ID card number">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Available Credit</label>
                            <div class="input-group input-group-sm advance-fee-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">account_balance</span>
                                </span>
                                <input type="number" step="0.01" min="0" class="form-control advance-fee-input" name="available_credit" id="available_credit" placeholder="0.00" value="0">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Increase</label>
                            <div class="input-group input-group-sm advance-fee-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">trending_up</span>
                                </span>
                                <input type="number" step="0.01" min="0" class="form-control advance-fee-input" name="increase" id="increase" placeholder="0.00" value="0">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Decrease</label>
                            <div class="input-group input-group-sm advance-fee-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">trending_down</span>
                                </span>
                                <input type="number" step="0.01" min="0" class="form-control advance-fee-input" name="decrease" id="decrease" placeholder="0.00" value="0">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Childs</label>
                            <div class="input-group input-group-sm advance-fee-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">family_restroom</span>
                                </span>
                                <input type="number" min="0" class="form-control advance-fee-input" name="childs" id="childs" placeholder="0" value="0">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer p-3" style="background-color: #f8f9fa; border-top: 1px solid #e9ecef;">
                    <button type="button" class="btn btn-sm py-2 px-4 rounded-8" data-bs-dismiss="modal" style="background-color: #6c757d; color: white; border: none; transition: all 0.3s ease;">
                        <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">close</span>
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-sm py-2 px-4 rounded-8 advance-fee-submit-btn">
                        <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">save</span>
                        Save Record
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    /* Advance Fee Form Styling */
    #advanceFeeModal .advance-fee-input-group {
        height: 36px;
        border-radius: 8px;
        overflow: hidden;
        transition: all 0.3s ease;
        border: 1px solid #dee2e6;
    }
    
    #advanceFeeModal .advance-fee-input-group:focus-within {
        box-shadow: 0 0 0 3px rgba(0, 52, 113, 0.15);
        border-color: #003471;
    }
    
    #advanceFeeModal .advance-fee-input {
        height: 36px;
        font-size: 13px;
        padding: 0.5rem 0.75rem;
        border: none;
        border-left: 1px solid #e0e7ff;
        border-radius: 0 8px 8px 0;
        transition: all 0.3s ease;
    }
    
    #advanceFeeModal .advance-fee-input:focus {
        border-left-color: #003471;
        box-shadow: none;
        outline: none;
    }
    
    #advanceFeeModal .input-group-text {
        height: 36px;
        padding: 0 0.75rem;
        display: flex;
        align-items: center;
        border: none;
        border-right: 1px solid #e0e7ff;
        border-radius: 8px 0 0 8px;
        transition: all 0.3s ease;
    }
    
    #advanceFeeModal .advance-fee-input-group:focus-within .input-group-text {
        background-color: #003471 !important;
        color: white !important;
        border-right-color: #003471;
    }
    
    #advanceFeeModal .form-label {
        margin-bottom: 0.4rem;
        line-height: 1.3;
    }
    
    #advanceFeeModal .advance-fee-submit-btn {
        background: linear-gradient(135deg, #003471 0%, #004a9f 100%);
        color: white;
        border: none;
        font-weight: 500;
        transition: all 0.3s ease;
        box-shadow: 0 2px 8px rgba(0, 52, 113, 0.25);
    }
    
    #advanceFeeModal .advance-fee-submit-btn:hover {
        background: linear-gradient(135deg, #004a9f 0%, #003471 100%);
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0, 52, 113, 0.35);
    }
    
    #advanceFeeModal .advance-fee-submit-btn:active {
        transform: translateY(0);
    }
    
    /* Add New Button Styling */
    .advance-fee-add-btn {
        background: linear-gradient(135deg, #003471 0%, #004a9f 100%);
        color: white;
        border: none;
        font-weight: 500;
        transition: all 0.3s ease;
        box-shadow: 0 2px 6px rgba(0, 52, 113, 0.2);
    }
    
    .advance-fee-add-btn:hover {
        background: linear-gradient(135deg, #004a9f 0%, #003471 100%);
        transform: translateY(-1px);
        box-shadow: 0 4px 10px rgba(0, 52, 113, 0.3);
        color: white;
    }
    
    .advance-fee-add-btn:active {
        transform: translateY(0);
    }
    
    .rounded-8 {
        border-radius: 8px;
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
    
    .export-btn:active {
        transform: translateY(0);
    }
    
    .default-table-area {
        border: 1px solid #e9ecef;
        border-radius: 8px;
        overflow: hidden;
        background-color: white;
    }
    
    .default-table-area table {
        margin-bottom: 0;
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
        text-transform: uppercase;
        letter-spacing: 0.5px;
        white-space: nowrap;
    }
    
    .default-table-area table tbody td {
        padding: 8px 12px;
        font-size: 13px;
        vertical-align: middle;
        border-bottom: 1px solid #e9ecef;
        line-height: 1.4;
    }
    
    .default-table-area table tbody tr:hover {
        background-color: #f8f9fa;
    }
    
    .default-table-area .badge {
        font-size: 11px;
        padding: 3px 6px;
        font-weight: 500;
    }
    
    .default-table-area .material-symbols-outlined {
        font-size: 13px !important;
    }
    
    .default-table-area .btn-sm {
        font-size: 11px;
        padding: 3px 6px;
        min-height: auto;
    }
    
    .default-table-area .btn-sm .material-symbols-outlined {
        font-size: 14px !important;
        vertical-align: middle;
        color: white !important;
    }
    
    .default-table-area .btn-primary .material-symbols-outlined,
    .default-table-area .btn-danger .material-symbols-outlined {
        color: white !important;
    }
    
    .default-table-area table tbody td strong {
        font-size: 13px;
        font-weight: 600;
    }
</style>

<script>
// Reset form when opening modal for new record
function resetForm() {
    document.getElementById('advanceFeeForm').reset();
    document.getElementById('advanceFeeForm').action = "{{ route('accounting.manage-advance-fee.store') }}";
    document.getElementById('methodField').innerHTML = '';
    document.getElementById('advanceFeeModalLabel').innerHTML = `
        <span class="material-symbols-outlined" style="font-size: 20px; color: white !important;">account_balance_wallet</span>
        <span style="color: white !important;">Add New Advance Fee Record</span>
    `;
    document.querySelector('.advance-fee-submit-btn').innerHTML = `
        <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">save</span>
        Save Record
    `;
}

// Edit advance fee function
function editAdvanceFee(id) {
    fetch(`{{ url('/accounting/manage-advance-fee') }}/${id}`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('advanceFeeForm').action = '{{ route('accounting.manage-advance-fee.update', ':id') }}'.replace(':id', id);
            document.getElementById('methodField').innerHTML = '@method("PUT")';
            document.getElementById('advanceFeeModalLabel').innerHTML = `
                <span class="material-symbols-outlined" style="font-size: 20px; color: white !important;">edit</span>
                <span style="color: white !important;">Edit Advance Fee Record</span>
            `;
            document.querySelector('.advance-fee-submit-btn').innerHTML = `
                <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">save</span>
                Update Record
            `;
            
            document.getElementById('parent_id').value = data.parent_id || '';
            document.getElementById('name').value = data.name || '';
            document.getElementById('email').value = data.email || '';
            document.getElementById('phone').value = data.phone || '';
            document.getElementById('id_card_number').value = data.id_card_number || '';
            document.getElementById('available_credit').value = data.available_credit || 0;
            document.getElementById('increase').value = data.increase || 0;
            document.getElementById('decrease').value = data.decrease || 0;
            document.getElementById('childs').value = data.childs || 0;
            
            new bootstrap.Modal(document.getElementById('advanceFeeModal')).show();
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error loading advance fee data');
        });
}

// Search functionality
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
    
    searchInput.disabled = true;
    const searchBtn = document.querySelector('.search-btn');
    if (searchBtn) {
        searchBtn.disabled = true;
        searchBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span>';
    }
    
    window.location.href = url.toString();
}

function handleSearchKeyPress(event) {
    if (event.key === 'Enter') {
        event.preventDefault();
        performSearch();
    }
}

function handleSearchInput(event) {
    // Auto-clear if input is empty
}

function clearSearch() {
    const url = new URL(window.location.href);
    url.searchParams.delete('search');
    url.searchParams.set('page', '1');
    
    const searchInput = document.getElementById('searchInput');
    searchInput.disabled = true;
    
    window.location.href = url.toString();
}

// Update entries per page
function updateEntriesPerPage(value) {
    const url = new URL(window.location.href);
    url.searchParams.set('per_page', value);
    url.searchParams.set('page', '1');
    window.location.href = url.toString();
}

// Print table
function printTable() {
    const printContents = document.querySelector('.default-table-area').innerHTML;
    const originalContents = document.body.innerHTML;
    
    document.body.innerHTML = `
        <div style="padding: 20px;">
            <h3 style="text-align: center; margin-bottom: 20px; color: #003471;">Advance Fee Records</h3>
            ${printContents}
        </div>
    `;
    
    window.print();
    document.body.innerHTML = originalContents;
    window.location.reload();
}
</script>
@endsection

