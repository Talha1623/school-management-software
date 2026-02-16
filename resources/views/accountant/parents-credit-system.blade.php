@extends('layouts.accountant')

@section('title', 'Parents Credit System - Accountant')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-3 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0 fs-16 fw-semibold d-flex align-items-center gap-2">
                    <span class="material-symbols-outlined" style="font-size: 20px; color: #003471;">account_balance_wallet</span>
                    <span>Parents Credit System</span>
                </h4>
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
                        <a href="{{ route('accountant.parents-credit-system.export', ['format' => 'excel']) }}{{ request()->has('search') ? '?search=' . request('search') : '' }}" class="btn btn-sm px-2 py-1 export-btn excel-btn">
                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">description</span>
                            <span>Excel</span>
                        </a>
                        <a href="{{ route('accountant.parents-credit-system.export', ['format' => 'csv']) }}{{ request()->has('search') ? '?search=' . request('search') : '' }}" class="btn btn-sm px-2 py-1 export-btn csv-btn">
                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">file_present</span>
                            <span>CSV</span>
                        </a>
                        <a href="{{ route('accountant.parents-credit-system.export', ['format' => 'pdf']) }}{{ request()->has('search') ? '?search=' . request('search') : '' }}" class="btn btn-sm px-2 py-1 export-btn pdf-btn" target="_blank">
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
                            <input type="text" id="searchInput" class="form-control border-start-0 border-end-0" placeholder="Search parents..." value="{{ request('search') }}" onkeypress="handleSearchKeyPress(event)" oninput="handleSearchInput(event)" style="padding: 4px 8px; font-size: 13px;">
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
                    <span>Parents Credit List</span>
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
                    <a href="{{ route('accountant.parents-credit-system') }}" class="text-decoration-none ms-2" style="color: #003471;">
                        <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">close</span>
                        Clear
                    </a>
                </div>
            @endif

            <div class="default-table-area" style="margin-top: 0;">
                <div class="table-responsive">
                    <table class="table table-sm table-hover" id="parentsCreditTable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Parent ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>ID Card Number</th>
                                <th class="text-end">Available Credit</th>
                                <th class="text-end">Increase</th>
                                <th class="text-end">Decrease</th>
                                <th class="text-center">Children</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if(isset($advanceFees) && $advanceFees->count() > 0)
                                @forelse($advanceFees as $fee)
                                    <tr>
                                        <td>{{ $loop->iteration + (($advanceFees->currentPage() - 1) * $advanceFees->perPage()) }}</td>
                                        <td>
                                            <span class="badge" style="background-color: #6c757d; color: white; font-size: 11px; padding: 3px 6px;">{{ $fee->parent_id ?? 'N/A' }}</span>
                                        </td>
                                        <td>
                                            <strong class="text-dark">{{ $fee->name ?? 'N/A' }}</strong>
                                        </td>
                                        <td>{{ $fee->email ?? 'N/A' }}</td>
                                        <td>{{ $fee->phone ?? 'N/A' }}</td>
                                        <td>{{ $fee->id_card_number ?? 'N/A' }}</td>
                                        <td class="text-end">
                                            <span class="badge" style="background-color: #28a745; color: white; font-size: 11px; padding: 4px 8px;">
                                                Rs. {{ number_format((float)($fee->available_credit ?? 0), 2) }}
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <span class="badge" style="background-color: #17a2b8; color: white; font-size: 11px; padding: 4px 8px;">
                                                Rs. {{ number_format((float)($fee->increase ?? 0), 2) }}
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <span class="badge" style="background-color: #ffc107; color: #000; font-size: 11px; padding: 4px 8px;">
                                                Rs. {{ number_format((float)($fee->decrease ?? 0), 2) }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge" style="background-color: #003471; color: white; font-size: 11px; padding: 4px 8px;">
                                                {{ $fee->children_count ?? 0 }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <div class="d-inline-flex gap-1">
                                                <button type="button" class="btn btn-sm btn-success px-2 py-1" title="Increase Credit" onclick="openIncreaseModal({{ $fee->id }})">
                                                    <span class="material-symbols-outlined" style="font-size: 14px; color: white;">add</span>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-warning px-2 py-1" title="Decrease Credit" onclick="openDecreaseModal({{ $fee->id }})">
                                                    <span class="material-symbols-outlined" style="font-size: 14px; color: white;">remove</span>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-info px-2 py-1" title="View Children" onclick="openChildrenModal({{ $fee->id }})">
                                                    <span class="material-symbols-outlined" style="font-size: 14px; color: white;">people</span>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="11" class="text-center py-4">
                                            <div class="d-flex flex-column align-items-center">
                                                <span class="material-symbols-outlined" style="font-size: 48px; color: #ccc; margin-bottom: 10px;">inbox</span>
                                                <p class="text-muted mb-0">No parents credit records found.</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            @else
                                <tr>
                                    <td colspan="11" class="text-center py-4">
                                        <div class="d-flex flex-column align-items-center">
                                            <span class="material-symbols-outlined" style="font-size: 48px; color: #ccc; margin-bottom: 10px;">inbox</span>
                                            <p class="text-muted mb-0">No parents credit records found.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                @if(isset($advanceFees) && $advanceFees->hasPages())
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <div class="text-muted fs-13">
                            Showing {{ $advanceFees->firstItem() }} to {{ $advanceFees->lastItem() }} of {{ $advanceFees->total() }} entries
                        </div>
                        <div>
                            {{ $advanceFees->links() }}
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Decrease Credit Modal -->
<div class="modal fade" id="decreaseCreditModal" tabindex="-1" aria-labelledby="decreaseCreditModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
            <div class="modal-header text-white p-3" style="background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%); border: none;">
                <h5 class="modal-title fs-15 fw-semibold mb-0 d-flex align-items-center gap-2" style="color: white !important;">
                    <span class="material-symbols-outlined" style="font-size: 20px; color: white !important;">trending_down</span>
                    <span style="color: white !important;">Decrease Credit</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" style="opacity: 0.8;"></button>
            </div>
            <form id="decreaseCreditForm" method="POST" action="">
                @csrf
                @method('PUT')
                <div class="modal-body p-4" style="background-color: #f8f9fa;">
                    <div class="row g-3">
                        <!-- Name -->
                        <div class="col-md-12">
                            <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Name</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">person</span>
                                </span>
                                <input type="text" class="form-control" id="decrease_name" readonly style="background-color: #f8f9fa; cursor: not-allowed;">
                            </div>
                        </div>

                        <!-- ID Card Number -->
                        <div class="col-md-12">
                            <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">ID Card Number</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">credit_card</span>
                                </span>
                                <input type="text" class="form-control" id="decrease_id_card" readonly style="background-color: #f8f9fa; cursor: not-allowed;">
                            </div>
                        </div>

                        <!-- Current Credit -->
                        <div class="col-md-12">
                            <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Current Credit</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">account_balance</span>
                                </span>
                                <input type="text" class="form-control" id="decrease_current_credit" readonly style="background-color: #f8f9fa; cursor: not-allowed;">
                            </div>
                        </div>

                        <!-- Minus Credit Amount -->
                        <div class="col-md-12">
                            <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Minus Credit Amount <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">remove</span>
                                </span>
                                <input type="number" step="0.01" min="0" class="form-control" name="decrease" id="decrease_amount" placeholder="Enter amount to decrease" required oninput="calculateDecreasedCredit()">
                            </div>
                        </div>

                        <!-- New Credit Total -->
                        <div class="col-md-12">
                            <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">New Credit Total</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text" style="background-color: #fff3cd; border-color: #ffc107; color: #856404;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">account_balance_wallet</span>
                                </span>
                                <input type="text" class="form-control" id="decrease_new_credit" readonly style="background-color: #fff3cd; font-weight: 600; color: #856404;">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer p-3" style="background-color: #f8f9fa; border-top: 1px solid #e9ecef;">
                    <button type="button" class="btn btn-sm py-2 px-4 rounded-8" data-bs-dismiss="modal" style="background-color: #6c757d; color: white; border: none; transition: all 0.3s ease;">
                        <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">close</span>
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-sm py-2 px-4 rounded-8" style="background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%); color: white; border: none; font-weight: 500; transition: all 0.3s ease; box-shadow: 0 2px 8px rgba(255, 193, 7, 0.25);">
                        <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">check</span>
                        Decrease
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Children Modal -->
<div class="modal fade" id="childrenModal" tabindex="-1" aria-labelledby="childrenModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
            <div class="modal-header text-white p-3" style="background: linear-gradient(135deg, #003471 0%, #004a9f 100%); border: none;">
                <h5 class="modal-title fs-15 fw-semibold mb-0 d-flex align-items-center gap-2" style="color: white !important;">
                    <span class="material-symbols-outlined" style="font-size: 20px; color: white !important;">people</span>
                    <span style="color: white !important;">Connected Students</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" style="opacity: 0.8;"></button>
            </div>
            <div class="modal-body p-3">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Student Name</th>
                                <th>Student Code</th>
                                <th>Class</th>
                                <th>Section</th>
                                <th>Campus</th>
                            </tr>
                        </thead>
                        <tbody id="childrenModalBody">
                            <tr>
                                <td colspan="6" class="text-center text-muted py-3">Loading...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Increase Credit Modal -->
<div class="modal fade" id="increaseCreditModal" tabindex="-1" aria-labelledby="increaseCreditModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
            <div class="modal-header text-white p-3" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%); border: none;">
                <h5 class="modal-title fs-15 fw-semibold mb-0 d-flex align-items-center gap-2" style="color: white !important;">
                    <span class="material-symbols-outlined" style="font-size: 20px; color: white !important;">trending_up</span>
                    <span style="color: white !important;">Increase Credit</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" style="opacity: 0.8;"></button>
            </div>
            <form id="increaseCreditForm" method="POST" action="">
                @csrf
                @method('PUT')
                <div class="modal-body p-4" style="background-color: #f8f9fa;">
                    <div class="row g-3">
                        <!-- Name -->
                        <div class="col-md-12">
                            <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Name</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">person</span>
                                </span>
                                <input type="text" class="form-control" id="increase_name" readonly style="background-color: #f8f9fa; cursor: not-allowed;">
                            </div>
                        </div>

                        <!-- ID Card Number -->
                        <div class="col-md-12">
                            <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">ID Card Number</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">credit_card</span>
                                </span>
                                <input type="text" class="form-control" id="increase_id_card" readonly style="background-color: #f8f9fa; cursor: not-allowed;">
                            </div>
                        </div>

                        <!-- Current Credit -->
                        <div class="col-md-12">
                            <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Current Credit</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">account_balance</span>
                                </span>
                                <input type="text" class="form-control" id="increase_current_credit" readonly style="background-color: #f8f9fa; cursor: not-allowed;">
                            </div>
                        </div>

                        <!-- New Credit Amount -->
                        <div class="col-md-12">
                            <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">New Credit Amount <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">add</span>
                                </span>
                                <input type="number" step="0.01" min="0" class="form-control" name="increase" id="increase_amount" placeholder="Enter amount to increase" required oninput="calculateNewCredit()">
                            </div>
                        </div>

                        <!-- New Credit Total -->
                        <div class="col-md-12">
                            <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">New Credit Total</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text" style="background-color: #e7f3ff; border-color: #b3d9ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">account_balance_wallet</span>
                                </span>
                                <input type="text" class="form-control" id="increase_new_credit" readonly style="background-color: #e7f3ff; font-weight: 600; color: #28a745;">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer p-3" style="background-color: #f8f9fa; border-top: 1px solid #e9ecef;">
                    <button type="button" class="btn btn-sm py-2 px-4 rounded-8" data-bs-dismiss="modal" style="background-color: #6c757d; color: white; border: none; transition: all 0.3s ease;">
                        <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">close</span>
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-sm py-2 px-4 rounded-8" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; border: none; font-weight: 500; transition: all 0.3s ease; box-shadow: 0 2px 8px rgba(40, 167, 69, 0.25);">
                        <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">check</span>
                        Increase
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    .search-results-info {
        background-color: #e7f3ff;
        border: 1px solid #b3d9ff;
        border-radius: 6px;
        padding: 10px 15px;
        margin-bottom: 15px;
        font-size: 13px;
        color: #003471;
    }
    
    .search-input-group .form-control {
        border: none;
        font-size: 13px;
        height: 32px;
        line-height: 1.4;
    }
    
    .search-input-group .form-control:focus {
        box-shadow: none;
        border: none;
    }
    
    .search-input-group .input-group-text {
        height: 32px;
        padding: 4px 8px;
        display: flex;
        align-items: center;
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
    
    .search-btn:disabled {
        opacity: 0.7;
        cursor: not-allowed;
    }
    
    /* Export Buttons */
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
        background-color: #6c757d;
        color: white;
    }
    
    .print-btn:hover {
        background-color: #5a6268;
        color: white;
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(108, 117, 125, 0.3);
    }
    
    .table thead th {
        background-color: #f8f9fa;
        border-bottom: 2px solid #dee2e6;
        font-weight: 600;
        color: #003471;
        font-size: 13px;
        padding: 12px 8px;
    }
    
    .table tbody td {
        padding: 10px 8px;
        font-size: 13px;
        vertical-align: middle;
    }
    
    .table tbody tr:hover {
        background-color: #f8f9fa;
    }
    
    @media print {
        .search-input-group,
        .export-btn,
        .search-results-info,
        .pagination {
            display: none !important;
        }
        
        .table {
            font-size: 10px;
        }
        
        .table thead th {
            background-color: #f0f0f0 !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
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
    // Optional: Add debounce or live search functionality
}

function performSearch() {
    const searchValue = document.getElementById('searchInput').value.trim();
    const url = new URL(window.location.href);
    
    if (searchValue) {
        url.searchParams.set('search', searchValue);
    } else {
        url.searchParams.delete('search');
    }
    
    url.searchParams.delete('page'); // Reset to first page on new search
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

function fetchAdvanceFeeData(id) {
    return fetch(`{{ route('accountant.parents-credit-system.show', ':id') }}`.replace(':id', id))
        .then(response => {
            if (!response.ok) {
                throw new Error('Failed to fetch data');
            }
            return response.json();
        });
}

function openIncreaseModal(id) {
    fetchAdvanceFeeData(id)
        .then(data => {
            document.getElementById('increaseCreditForm').action = '{{ route('accountant.parents-credit-system.update', ':id') }}'.replace(':id', id);
            document.getElementById('increase_name').value = data.name || 'N/A';
            document.getElementById('increase_id_card').value = data.id_card_number || 'N/A';
            const current = parseFloat(data.available_credit || 0);
            document.getElementById('increase_current_credit').value = 'Rs. ' + current.toFixed(2);
            document.getElementById('increase_amount').value = '';
            document.getElementById('increase_new_credit').value = 'Rs. ' + current.toFixed(2);

            const modal = new bootstrap.Modal(document.getElementById('increaseCreditModal'));
            modal.show();
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error loading parent credit data. Please try again.');
        });
}

function calculateNewCredit() {
    const currentCredit = parseFloat(document.getElementById('increase_current_credit').value.replace('Rs. ', '').replace(',', '') || 0);
    const increaseAmount = parseFloat(document.getElementById('increase_amount').value || 0);
    const newCredit = currentCredit + increaseAmount;
    document.getElementById('increase_new_credit').value = 'Rs. ' + newCredit.toFixed(2);
}

function openDecreaseModal(id) {
    fetchAdvanceFeeData(id)
        .then(data => {
            document.getElementById('decreaseCreditForm').action = '{{ route('accountant.parents-credit-system.update', ':id') }}'.replace(':id', id);
            document.getElementById('decrease_name').value = data.name || 'N/A';
            document.getElementById('decrease_id_card').value = data.id_card_number || 'N/A';
            const current = parseFloat(data.available_credit || 0);
            document.getElementById('decrease_current_credit').value = 'Rs. ' + current.toFixed(2);
            document.getElementById('decrease_amount').value = '';
            document.getElementById('decrease_new_credit').value = 'Rs. ' + current.toFixed(2);

            const modal = new bootstrap.Modal(document.getElementById('decreaseCreditModal'));
            modal.show();
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error loading parent credit data. Please try again.');
        });
}

function calculateDecreasedCredit() {
    const currentCredit = parseFloat(document.getElementById('decrease_current_credit').value.replace('Rs. ', '').replace(',', '') || 0);
    const decreaseAmount = parseFloat(document.getElementById('decrease_amount').value || 0);
    const newCredit = Math.max(0, currentCredit - decreaseAmount);
    document.getElementById('decrease_new_credit').value = 'Rs. ' + newCredit.toFixed(2);
}

function openChildrenModal(id) {
    fetchAdvanceFeeData(id)
        .then(data => {
            const parentId = data.parent_id;
            const idCardNumber = data.id_card_number;
            
            // Fetch connected students
            fetch(`{{ route('accountant.parents-credit-system.connected-students', ':id') }}`.replace(':id', id))
                .then(response => response.json())
                .then(result => {
                    const tbody = document.getElementById('childrenModalBody');
                    if (result.students && result.students.length > 0) {
                        tbody.innerHTML = result.students.map((student, index) => `
                            <tr>
                                <td>${index + 1}</td>
                                <td><strong>${student.student_name || 'N/A'}</strong></td>
                                <td><span class="badge bg-secondary">${student.student_code || 'N/A'}</span></td>
                                <td>${student.class || 'N/A'}</td>
                                <td>${student.section || 'N/A'}</td>
                                <td>${student.campus || 'N/A'}</td>
                            </tr>
                        `).join('');
                    } else {
                        tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-3">No students connected to this parent.</td></tr>';
                    }
                    
                    const modal = new bootstrap.Modal(document.getElementById('childrenModal'));
                    modal.show();
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('childrenModalBody').innerHTML = '<tr><td colspan="6" class="text-center text-danger py-3">Error loading students. Please try again.</td></tr>';
                    const modal = new bootstrap.Modal(document.getElementById('childrenModal'));
                    modal.show();
                });
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error loading parent data. Please try again.');
        });
}
</script>
@endsection
