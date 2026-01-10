@extends('layouts.app')

@section('title', 'Accountant')

@section('content')
<div class="row">
    <!-- Summary Cards Section -->
    <div class="row mb-3">
        <div class="col-md-4 mb-2">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 8px; overflow: hidden;">
                <div class="card-body p-3" style="background: linear-gradient(135deg, #003471 0%, #004a9f 100%);">
                    <div>
                        <h6 class="text-white-50 mb-1" style="font-size: 11px; font-weight: 500;">Total Accountants</h6>
                        <h3 class="text-white mb-0" style="font-size: 24px; font-weight: 700;">{{ $totalAccountants }}</h3>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 mb-2">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 8px; overflow: hidden;">
                <div class="card-body p-3" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%);">
                    <div>
                        <h6 class="text-white mb-1" style="font-size: 11px; font-weight: 500; opacity: 0.9;">Active Accountants</h6>
                        <h3 class="text-white mb-0" style="font-size: 24px; font-weight: 700;">{{ $activeAccountants }}</h3>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 mb-2">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 8px; overflow: hidden;">
                <div class="card-body p-3" style="background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);">
                    <div>
                        <h6 class="text-white mb-1" style="font-size: 11px; font-weight: 500; opacity: 0.9;">Restricted Accountants</h6>
                        <h3 class="text-white mb-0" style="font-size: 24px; font-weight: 700;">{{ $restrictedAccountants }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-3 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0 fs-16 fw-semibold">Accountants</h4>
                <button type="button" class="btn btn-sm py-2 px-3 d-inline-flex align-items-center gap-1 rounded-8 accountant-add-btn" data-bs-toggle="modal" data-bs-target="#accountantModal" onclick="resetForm()">
                    <span class="material-symbols-outlined" style="font-size: 16px;">add</span>
                    <span>Add New Accountant</span>
                </button>
            </div>

            <!-- Toast Notification Container -->
            <div id="toastContainer" class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 9999;">
                @if(session('success'))
                    <div class="toast show success-toast" role="alert" aria-live="assertive" aria-atomic="true" data-bs-autohide="true" data-bs-delay="3000">
                        <div class="toast-header success-toast-header">
                            <span class="material-symbols-outlined me-2" style="font-size: 20px; color: white;">check_circle</span>
                            <strong class="me-auto text-white">Success</strong>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
                        </div>
                        <div class="toast-body">
                            {{ session('success') }}
                        </div>
                    </div>
                @endif

                @if(session('error'))
                    <div class="toast show error-toast" role="alert" aria-live="assertive" aria-atomic="true" data-bs-autohide="true" data-bs-delay="3000">
                        <div class="toast-header error-toast-header">
                            <span class="material-symbols-outlined me-2" style="font-size: 20px; color: white;">error</span>
                            <strong class="me-auto text-white">Error</strong>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
                        </div>
                        <div class="toast-body">
                            {{ session('error') }}
                        </div>
                    </div>
                @endif
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
                        <a href="{{ route('accountants.export', ['format' => 'excel']) }}{{ request()->has('search') ? '?search=' . request('search') : '' }}" class="btn btn-sm px-2 py-1 export-btn excel-btn">
                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">description</span>
                            <span>Excel</span>
                        </a>
                        <a href="{{ route('accountants.export', ['format' => 'csv']) }}{{ request()->has('search') ? '?search=' . request('search') : '' }}" class="btn btn-sm px-2 py-1 export-btn csv-btn">
                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">file_present</span>
                            <span>CSV</span>
                        </a>
                        <a href="{{ route('accountants.export', ['format' => 'pdf']) }}{{ request()->has('search') ? '?search=' . request('search') : '' }}" class="btn btn-sm px-2 py-1 export-btn pdf-btn" target="_blank">
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
                            <input type="text" id="searchInput" class="form-control border-start-0 border-end-0" placeholder="Search by name, email, campus..." value="{{ request('search') }}" onkeypress="handleSearchKeyPress(event)" oninput="handleSearchInput(event)" style="padding: 4px 8px; font-size: 13px;">
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
                    <span class="material-symbols-outlined" style="font-size: 18px;">account_circle</span>
                    <span>Manage Accountants</span>
                </h5>
            </div>

            <!-- Search Results Info -->
            @if(request('search'))
                <div class="search-results-info">
                    <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle; color: #003471;">search</span>
                    <strong>Search Results:</strong> Showing results for "<strong>{{ request('search') }}</strong>" 
                    ({{ $accountants->total() }} {{ Str::plural('result', $accountants->total()) }} found)
                    <a href="{{ route('accountants') }}" class="text-decoration-none ms-2" style="color: #003471;">
                        <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">close</span>
                        Clear
                    </a>
                </div>
            @endif

            <div class="default-table-area" style="margin-top: 0;">
                <div class="table-responsive">
                    <table class="table table-sm table-hover accountant-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Acc. ID</th>
                                <th>Photo</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Campus</th>
                                <th>App Login</th>
                                <th>Web Login</th>
                                <th>Options</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($accountants as $accountant)
                                <tr>
                                    <td>{{ $loop->iteration + (($accountants->currentPage() - 1) * $accountants->perPage()) }}</td>
                                    <td>{{ $accountant->id }}</td>
                                    <td>
                                        <div class="accountant-photo-placeholder">
                                            <span class="material-symbols-outlined">person</span>
                                        </div>
                                    </td>
                                    <td>
                                        <strong class="text-dark">{{ $accountant->name }}</strong>
                                    </td>
                                    <td>
                                        <span class="text-muted">{{ $accountant->email }}</span>
                                    </td>
                                    <td>
                                        @if($accountant->campus)
                                            <span class="badge bg-primary text-white" style="font-size: 12px; padding: 4px 8px;">{{ $accountant->campus }}</span>
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <label class="toggle-switch-wrapper">
                                            <input class="toggle-switch-input app-login-switch" type="checkbox" id="appLoginSwitch{{ $accountant->id }}" {{ $accountant->app_login_enabled ? 'checked' : '' }} onchange="toggleAppLogin({{ $accountant->id }})">
                                            <span class="toggle-switch-slider"></span>
                                        </label>
                                    </td>
                                    <td class="text-center">
                                        <label class="toggle-switch-wrapper">
                                            <input class="toggle-switch-input web-login-switch" type="checkbox" id="webLoginSwitch{{ $accountant->id }}" {{ $accountant->web_login_enabled ? 'checked' : '' }} onchange="toggleWebLogin({{ $accountant->id }})">
                                            <span class="toggle-switch-slider"></span>
                                        </label>
                                    </td>
                                    <td>
                                        <div class="d-inline-flex gap-1 align-items-center">
                                            <button type="button" class="btn btn-sm option-btn key-btn px-2 py-1" onclick="editAccountant({{ $accountant->id }})" title="Edit">
                                                <span class="material-symbols-outlined" style="font-size: 14px;">key</span>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-info px-2 py-1" onclick="viewAccountant({{ $accountant->id }})" title="View Details">
                                                <span class="material-symbols-outlined" style="font-size: 14px; color: white;">visibility</span>
                                            </button>
                                            <div class="dropdown">
                                                <button class="btn btn-sm option-btn dropdown-btn dropdown-toggle px-2 py-1" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="More Options">
                                                    <span class="material-symbols-outlined" style="font-size: 14px;">arrow_drop_down</span>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <a class="dropdown-item" href="#" onclick="editAccountant({{ $accountant->id }}); return false;">
                                                            <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">edit</span>
                                                            Edit
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <form action="{{ route('accountants.destroy', $accountant->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this accountant?');">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="dropdown-item text-danger">
                                                                <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">delete</span>
                                                                Delete
                                                            </button>
                                                        </form>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center text-muted py-5">
                                        <span class="material-symbols-outlined" style="font-size: 48px; opacity: 0.3;">inbox</span>
                                        <p class="mt-2 mb-0">No accountants found.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if($accountants->hasPages())
                <div class="mt-3">
                    {{ $accountants->links() }}
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Accountant Modal -->
<div class="modal fade" id="accountantModal" tabindex="-1" aria-labelledby="accountantModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
            <div class="modal-header text-white p-3" style="background: linear-gradient(135deg, #003471 0%, #004a9f 100%); border: none;">
                <h5 class="modal-title fs-15 fw-semibold mb-0 d-flex align-items-center gap-2" id="accountantModalLabel" style="color: white;">
                    <span class="material-symbols-outlined" style="font-size: 20px; color: white;">person_add</span>
                    <span style="color: white;">Add New Accountant</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" style="opacity: 0.8;"></button>
            </div>
            <form id="accountantForm" method="POST" action="{{ route('accountants.store') }}" onsubmit="handleFormSubmit(event)">
                @csrf
                <div id="methodField"></div>
                <div class="modal-body p-3">
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Name <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm accountant-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">person</span>
                                </span>
                                <input type="text" class="form-control accountant-input" name="name" id="name" placeholder="Enter name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Email <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm accountant-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">email</span>
                                </span>
                                <input type="email" class="form-control accountant-input" name="email" id="email" placeholder="Enter email" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Campus</label>
                            <div class="input-group input-group-sm accountant-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">location_on</span>
                                </span>
                                <select class="form-select accountant-input" name="campus" id="campus" style="border: none; border-left: 1px solid #e0e7ff;">
                                    <option value="">Select Campus</option>
                                    @foreach($campuses as $campus)
                                        <option value="{{ $campus->campus_name ?? $campus }}">{{ $campus->campus_name ?? $campus }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Password <span class="text-danger" id="passwordRequired">*</span></label>
                            <div class="input-group input-group-sm accountant-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">lock</span>
                                </span>
                                <input type="password" class="form-control accountant-input" name="password" id="password" placeholder="Enter password" required>
                            </div>
                            <small class="text-muted" id="passwordHint" style="font-size: 11px; display: none;">Leave blank to keep current password when editing</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer p-3" style="background-color: #f8f9fa; border-top: 1px solid #e9ecef;">
                    <button type="button" class="btn btn-sm py-2 px-4 rounded-8" data-bs-dismiss="modal" style="background-color: #6c757d; color: white; border: none; transition: all 0.3s ease;">
                        <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">close</span>
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-sm py-2 px-4 rounded-8 accountant-submit-btn">
                        <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">save</span>
                        Save Accountant
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    /* Accountant Form Styling */
    #accountantModal .accountant-input-group {
        height: 36px;
        border-radius: 8px;
        overflow: hidden;
        transition: all 0.3s ease;
        border: 1px solid #dee2e6;
    }
    
    #accountantModal .accountant-input-group:focus-within {
        box-shadow: 0 0 0 3px rgba(0, 52, 113, 0.15);
        border-color: #003471;
    }
    
    #accountantModal .accountant-input {
        height: 36px;
        font-size: 13px;
        padding: 0.5rem 0.75rem;
        border: none;
        border-left: 1px solid #e0e7ff;
        border-radius: 0 8px 8px 0;
        transition: all 0.3s ease;
    }
    
    #accountantModal .accountant-input:focus {
        border-left-color: #003471;
        box-shadow: none;
        outline: none;
    }
    
    #accountantModal .input-group-text {
        height: 36px;
        padding: 0 0.75rem;
        display: flex;
        align-items: center;
        border: none;
        border-right: 1px solid #e0e7ff;
        border-radius: 8px 0 0 8px;
        transition: all 0.3s ease;
    }
    
    #accountantModal .accountant-input-group:focus-within .input-group-text {
        background-color: #003471 !important;
        color: white !important;
        border-right-color: #003471;
    }
    
    #accountantModal .form-label {
        margin-bottom: 0.4rem;
        line-height: 1.3;
    }
    
    #accountantModal .accountant-submit-btn {
        background: linear-gradient(135deg, #003471 0%, #004a9f 100%);
        color: white;
        border: none;
        font-weight: 500;
        transition: all 0.3s ease;
        box-shadow: 0 2px 8px rgba(0, 52, 113, 0.25);
    }
    
    #accountantModal .accountant-submit-btn:hover {
        background: linear-gradient(135deg, #004a9f 0%, #003471 100%);
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0, 52, 113, 0.35);
    }
    
    .accountant-add-btn {
        background: linear-gradient(135deg, #003471 0%, #004a9f 100%);
        color: white;
        border: none;
        font-weight: 500;
        transition: all 0.3s ease;
        box-shadow: 0 2px 8px rgba(0, 52, 113, 0.25);
    }
    
    .accountant-add-btn:hover {
        background: linear-gradient(135deg, #004a9f 0%, #003471 100%);
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0, 52, 113, 0.35);
        color: white;
    }
    
    /* Action Buttons */
    .action-btn {
        width: 28px;
        height: 28px;
        padding: 0;
        border-radius: 6px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: none;
        font-size: 0;
        transition: all 0.3s ease;
    }
    
    .action-btn .material-symbols-outlined {
        font-size: 16px;
    }
    
    .edit-btn {
        background-color: #0d6efd;
        color: white;
    }
    
    .edit-btn:hover {
        background-color: #0b5ed7;
        transform: translateY(-1px);
        box-shadow: 0 2px 6px rgba(13, 110, 253, 0.4);
        color: white;
    }
    
    .delete-btn {
        background-color: #dc3545;
    }
    
    .delete-btn:hover {
        background-color: #bb2d3b;
        transform: translateY(-1px);
        box-shadow: 0 2px 6px rgba(220, 53, 69, 0.4);
    }
    
    /* Toggle Switches */
    /* Toggle Switch Design */
    .toggle-switch-wrapper {
        position: relative;
        display: inline-block;
        width: 36px;
        height: 18px;
        cursor: pointer;
    }
    
    .toggle-switch-input {
        opacity: 0;
        width: 0;
        height: 0;
        position: absolute;
    }
    
    .toggle-switch-slider {
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: #dc3545;
        transition: 0.3s;
        border-radius: 18px;
    }
    
    .toggle-switch-slider:before {
        position: absolute;
        content: "";
        height: 14px;
        width: 14px;
        left: 2px;
        bottom: 2px;
        background-color: white;
        transition: 0.3s;
        border-radius: 50%;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
    }
    
    .toggle-switch-input:checked + .toggle-switch-slider {
        background-color: #28a745;
    }
    
    .toggle-switch-input:checked + .toggle-switch-slider:before {
        transform: translateX(18px);
    }
    
    .toggle-switch-input:focus + .toggle-switch-slider {
        box-shadow: 0 0 0 3px rgba(40, 167, 69, 0.25);
    }
    
    .toggle-switch-input:checked:focus + .toggle-switch-slider {
        box-shadow: 0 0 0 3px rgba(40, 167, 69, 0.25);
    }
    
    .toggle-switch-input:not(:checked):focus + .toggle-switch-slider {
        box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.25);
    }
    
    .toggle-switch-wrapper:hover .toggle-switch-slider {
        box-shadow: 0 0 0 2px rgba(0, 0, 0, 0.1);
    }
    
    .search-results-info {
        padding: 10px 15px;
        background-color: #f0f4ff;
        border-left: 4px solid #003471;
        border-radius: 6px;
        margin-bottom: 15px;
        font-size: 13px;
        color: #495057;
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
    
    /* Table Styling */
    .accountant-table {
        margin-bottom: 0;
        font-size: 14px;
        border: 1px solid #dee2e6;
        border-collapse: separate;
        border-spacing: 0;
        border-radius: 8px;
        overflow: hidden;
        background-color: white;
    }
    
    .accountant-table thead {
        border-bottom: 2px solid #dee2e6;
        background-color: #f8f9fa;
    }
    
    .accountant-table thead th {
        padding: 12px 15px;
        font-size: 14px;
        font-weight: 600;
        vertical-align: middle;
        line-height: 1.4;
        white-space: nowrap;
        border: 1px solid #dee2e6;
        background-color: #f8f9fa;
        color: #495057;
        text-transform: none;
    }
    
    .accountant-table thead th:first-child {
        border-left: 1px solid #dee2e6;
    }
    
    .accountant-table thead th:last-child {
        border-right: 1px solid #dee2e6;
    }
    
    /* Options column width */
    .accountant-table thead th:last-child,
    .accountant-table tbody td:last-child {
        min-width: 150px;
        width: auto;
    }
    
    .accountant-table tbody td {
        padding: 12px 15px;
        font-size: 14px;
        vertical-align: middle;
        line-height: 1.4;
        border: 1px solid #dee2e6;
        background-color: white;
    }
    
    .accountant-table tbody td:first-child {
        border-left: 1px solid #dee2e6;
    }
    
    .accountant-table tbody td:last-child {
        border-right: 1px solid #dee2e6;
        position: relative;
    }
    
    .accountant-table tbody tr:last-child td {
        border-bottom: 1px solid #dee2e6;
    }
    
    .accountant-table tbody tr:hover {
        background-color: #f8f9fa;
    }
    
    .accountant-table tbody tr:hover td {
        background-color: #f8f9fa;
    }
    
    /* Photo Placeholder */
    .accountant-photo-placeholder {
        width: 45px;
        height: 45px;
        border-radius: 50%;
        background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 2px solid #e9ecef;
    }
    
    .accountant-photo-placeholder .material-symbols-outlined {
        font-size: 22px;
        color: #1976d2;
    }
    
    /* Options Buttons */
    .option-btn {
        border-radius: 6px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: none;
        transition: all 0.3s ease;
    }
    
    .option-btn .material-symbols-outlined {
        font-size: 14px;
    }
    
    .key-btn {
        background-color: #28a745;
        color: white;
    }
    
    .key-btn:hover {
        background-color: #218838;
        color: white;
        transform: translateY(-1px);
        box-shadow: 0 2px 6px rgba(40, 167, 69, 0.4);
    }
    
    
    .dropdown-btn {
        background-color: #6c757d;
        color: white;
    }
    
    .dropdown-btn:hover {
        background-color: #5a6268;
        color: white;
        transform: translateY(-1px);
        box-shadow: 0 2px 6px rgba(108, 117, 125, 0.4);
    }
    
    .dropdown-btn::after {
        display: none;
    }
    
    /* Fix dropdown overflow in table - no scroll */
    .default-table-area .table-responsive {
        overflow-x: auto;
        overflow-y: hidden !important;
        position: relative;
    }
    
    /* Ensure dropdown stays within table bounds */
    .accountant-table tbody td:last-child {
        position: relative;
        overflow: visible !important;
    }
    
    .accountant-table .dropdown {
        position: relative;
    }
    
    /* Prevent table from clipping dropdown */
    .accountant-table {
        overflow: visible !important;
    }
    
    .default-table-area {
        overflow: visible !important;
    }
    
    .dropdown-menu {
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        border: 1px solid #e9ecef;
        position: absolute !important;
        z-index: 1050 !important;
        min-width: 150px;
        right: 0;
        left: auto;
        top: auto !important;
        bottom: 100% !important;
        margin-top: 0;
        margin-bottom: 2px;
        transform: none !important;
    }
    
    /* Ensure dropdown menu always opens upward */
    .accountant-table .dropdown.show .dropdown-menu {
        top: auto !important;
        bottom: 100% !important;
        transform: none !important;
    }
    
    .dropdown-item {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 8px 16px;
        font-size: 13px;
    }
    
    .dropdown-item:hover {
        background-color: #f8f9fa;
    }
    
    .dropdown-item .material-symbols-outlined {
        font-size: 16px;
    }
    
    /* Toast Notification Styling */
    .toast-container {
        max-width: 400px;
    }
    
    .success-toast {
        background: white;
        border-radius: 12px;
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
        border: none;
        overflow: hidden;
        margin-bottom: 10px;
    }
    
    .success-toast-header {
        background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        color: white;
        border-bottom: none;
        padding: 12px 16px;
        display: flex;
        align-items: center;
    }
    
    .success-toast .toast-body {
        padding: 14px 16px;
        color: #495057;
        font-size: 14px;
        line-height: 1.5;
    }
    
    .error-toast {
        background: white;
        border-radius: 12px;
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
        border: none;
        overflow: hidden;
        margin-bottom: 10px;
    }
    
    .error-toast-header {
        background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
        color: white;
        border-bottom: none;
        padding: 12px 16px;
        display: flex;
        align-items: center;
    }
    
    .error-toast .toast-body {
        padding: 14px 16px;
        color: #495057;
        font-size: 14px;
        line-height: 1.5;
    }
    
    .toast-header .btn-close {
        margin-left: auto;
        opacity: 0.9;
    }
    
    .toast-header .btn-close:hover {
        opacity: 1;
    }
    
    @keyframes slideInRight {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    .toast.show {
        animation: slideInRight 0.3s ease-out;
    }
</style>

<script>
function resetForm() {
    document.getElementById('accountantForm').reset();
    document.getElementById('methodField').innerHTML = '';
    const modalLabel = document.getElementById('accountantModalLabel');
    modalLabel.innerHTML = `
        <span class="material-symbols-outlined" style="font-size: 20px; color: white;">person_add</span>
        <span style="color: white;">Add New Accountant</span>
    `;
    document.getElementById('password').required = true;
    document.getElementById('passwordRequired').style.display = 'inline';
    document.getElementById('passwordHint').style.display = 'none';
    document.getElementById('accountantForm').action = '{{ route('accountants.store') }}';
    // Clear any validation errors
    document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
    document.querySelectorAll('.invalid-feedback').forEach(el => el.remove());
}

function handleFormSubmit(event) {
    event.preventDefault();
    
    const form = document.getElementById('accountantForm');
    const formData = new FormData(form);
    const submitButton = form.querySelector('button[type="submit"]');
    const originalButtonText = submitButton.innerHTML;
    
    // Disable submit button
    submitButton.disabled = true;
    submitButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving...';
    
    // Determine if it's create or update
    const isUpdate = form.action.includes('/accountants/') && form.action.split('/accountants/')[1] && !form.action.split('/accountants/')[1].includes('store');
    const url = form.action;
    
    // Add method override for PUT
    if (isUpdate) {
        formData.append('_method', 'PUT');
    }
    
    fetch(url, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}'
        }
    })
    .then(response => {
        if (!response.ok) {
            return response.json().then(err => Promise.reject(err));
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
            
            if (!isUpdate) {
                // Add new row to table
                addAccountantToTable(data.accountant);
                // Update summary cards
                updateSummaryCards();
                // Reset form and close modal
                resetForm();
                const modal = bootstrap.Modal.getInstance(document.getElementById('accountantModal'));
                if (modal) {
                    modal.hide();
                }
            } else {
                // For update, reload the page to show updated data
                window.location.reload();
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        
        // Handle validation errors
        if (error.errors) {
            // Clear previous errors
            document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
            document.querySelectorAll('.invalid-feedback').forEach(el => el.remove());
            
            // Show new errors
            Object.keys(error.errors).forEach(field => {
                const input = document.getElementById(field) || document.querySelector(`[name="${field}"]`);
                if (input) {
                    input.classList.add('is-invalid');
                    const errorDiv = document.createElement('div');
                    errorDiv.className = 'invalid-feedback';
                    errorDiv.textContent = error.errors[field][0];
                    input.parentElement.appendChild(errorDiv);
                }
            });
            showToast('Please fix the validation errors', 'error');
        } else {
            showToast(error.message || 'An error occurred while saving the accountant', 'error');
        }
    })
    .finally(() => {
        // Re-enable submit button
        submitButton.disabled = false;
        submitButton.innerHTML = originalButtonText;
    });
}

function addAccountantToTable(accountant) {
    const tbody = document.querySelector('.accountant-table tbody');
    if (!tbody) return;
    
    // Get current row count
    const currentRows = tbody.querySelectorAll('tr').length;
    const rowNumber = currentRows + 1;
    
    // Create new row
    const newRow = document.createElement('tr');
    newRow.innerHTML = `
        <td>${rowNumber}</td>
        <td>${accountant.id}</td>
        <td>
            <div class="accountant-photo-placeholder">
                <span class="material-symbols-outlined">person</span>
            </div>
        </td>
        <td>
            <strong class="text-dark">${accountant.name}</strong>
        </td>
        <td>
            <span class="text-muted">${accountant.email}</span>
        </td>
        <td>
            ${accountant.campus ? `<span class="badge bg-primary text-white" style="font-size: 12px; padding: 4px 8px;">${accountant.campus}</span>` : '<span class="text-muted">N/A</span>'}
        </td>
        <td class="text-center">
            <label class="toggle-switch-wrapper">
                <input class="toggle-switch-input app-login-switch" type="checkbox" id="appLoginSwitch${accountant.id}" ${accountant.app_login_enabled ? 'checked' : ''} onchange="toggleAppLogin(${accountant.id})">
                <span class="toggle-switch-slider"></span>
            </label>
        </td>
        <td class="text-center">
            <label class="toggle-switch-wrapper">
                <input class="toggle-switch-input web-login-switch" type="checkbox" id="webLoginSwitch${accountant.id}" ${accountant.web_login_enabled ? 'checked' : ''} onchange="toggleWebLogin(${accountant.id})">
                <span class="toggle-switch-slider"></span>
            </label>
        </td>
        <td>
            <div class="d-inline-flex gap-1 align-items-center">
                <button type="button" class="btn btn-sm option-btn key-btn px-2 py-1" onclick="editAccountant(${accountant.id})" title="Edit">
                    <span class="material-symbols-outlined" style="font-size: 14px;">key</span>
                </button>
                <button type="button" class="btn btn-sm btn-info px-2 py-1" onclick="viewAccountant(${accountant.id})" title="View Details">
                    <span class="material-symbols-outlined" style="font-size: 14px; color: white;">visibility</span>
                </button>
                <div class="dropdown">
                    <button class="btn btn-sm option-btn dropdown-btn dropdown-toggle px-2 py-1" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="More Options">
                        <span class="material-symbols-outlined" style="font-size: 14px;">arrow_drop_down</span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <a class="dropdown-item" href="#" onclick="editAccountant(${accountant.id}); return false;">
                                <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">edit</span>
                                Edit
                            </a>
                        </li>
                        <li>
                            <form action="{{ url('/accountants') }}/${accountant.id}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this accountant?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="dropdown-item text-danger">
                                    <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">delete</span>
                                    Delete
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </td>
    `;
    
    // Add animation
    newRow.style.opacity = '0';
    newRow.style.transform = 'translateY(-10px)';
    tbody.insertBefore(newRow, tbody.firstChild);
    
    // Animate in
    setTimeout(() => {
        newRow.style.transition = 'all 0.3s ease';
        newRow.style.opacity = '1';
        newRow.style.transform = 'translateY(0)';
    }, 10);
}

function updateSummaryCards() {
    // Increment total accountants count
    const totalCards = document.querySelectorAll('.row.mb-3 .col-md-4 .card-body h3');
    if (totalCards.length > 0) {
        const totalCard = totalCards[0];
        const currentTotal = parseInt(totalCard.textContent.trim()) || 0;
        totalCard.textContent = currentTotal + 1;
        
        // Also increment active accountants if app_login_enabled or web_login_enabled
        if (totalCards.length > 1) {
            const activeCard = totalCards[1];
            const currentActive = parseInt(activeCard.textContent.trim()) || 0;
            activeCard.textContent = currentActive + 1;
        }
    }
}

function editAccountant(id) {
    fetch(`{{ url('/accountants') }}/${id}`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('name').value = data.name;
            document.getElementById('email').value = data.email;
            document.getElementById('campus').value = data.campus || '';
            document.getElementById('password').required = false;
            document.getElementById('passwordRequired').style.display = 'none';
            document.getElementById('passwordHint').style.display = 'block';
            document.getElementById('password').placeholder = 'Leave blank to keep current password';
            const modalLabel = document.getElementById('accountantModalLabel');
            modalLabel.innerHTML = `
                <span class="material-symbols-outlined" style="font-size: 20px; color: white;">edit</span>
                <span style="color: white;">Edit Accountant</span>
            `;
            document.getElementById('methodField').innerHTML = '<input type="hidden" name="_method" value="PUT">';
            document.getElementById('accountantForm').action = `{{ url('/accountants') }}/${id}`;
            new bootstrap.Modal(document.getElementById('accountantModal')).show();
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error fetching accountant data');
        });
}

function viewAccountant(id) {
    fetch(`{{ url('/accountants') }}/${id}`)
        .then(response => response.json())
        .then(data => {
            alert('Accountant Details:\n\nName: ' + data.name + '\nEmail: ' + data.email + '\nCampus: ' + (data.campus || 'N/A'));
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error fetching accountant data');
        });
}

function toggleAppLogin(id) {
    const checkbox = document.getElementById('appLoginSwitch' + id);
    const originalState = checkbox.checked;
    
    fetch(`{{ url('/accountants') }}/${id}/toggle-app-login`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            checkbox.checked = data.app_login_enabled;
            // Show success toast
            showToast(data.message, 'success');
        } else {
            checkbox.checked = originalState;
            alert('Error updating app login status');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        checkbox.checked = originalState;
        alert('Error updating app login status');
    });
}

function toggleWebLogin(id) {
    const checkbox = document.getElementById('webLoginSwitch' + id);
    const originalState = checkbox.checked;
    
    fetch(`{{ url('/accountants') }}/${id}/toggle-web-login`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            checkbox.checked = data.web_login_enabled;
            // Show success toast
            showToast(data.message, 'success');
            
            // If web login is enabled, open accountant login page in new tab
            if (data.web_login_enabled && data.email) {
                const loginUrl = `{{ url('/accountant/login') }}?email=${encodeURIComponent(data.email)}`;
                window.open(loginUrl, '_blank');
            }
        } else {
            checkbox.checked = originalState;
            alert('Error updating web login status');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        checkbox.checked = originalState;
        alert('Error updating web login status');
    });
}

function updateEntriesPerPage(value) {
    const url = new URL(window.location.href);
    url.searchParams.set('per_page', value);
    url.searchParams.set('page', '1');
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
        performSearch();
    }
}

function performSearch() {
    const searchValue = document.getElementById('searchInput').value;
    const url = new URL(window.location.href);
    if (searchValue) {
        url.searchParams.set('search', searchValue);
    } else {
        url.searchParams.delete('search');
    }
    url.searchParams.set('page', '1');
    window.location.href = url.toString();
}

function clearSearch() {
    document.getElementById('searchInput').value = '';
    performSearch();
}

// Toast notification function
function showToast(message, type = 'success') {
    const toastContainer = document.getElementById('toastContainer');
    if (!toastContainer) {
        // Create container if it doesn't exist
        const container = document.createElement('div');
        container.id = 'toastContainer';
        container.className = 'toast-container position-fixed top-0 end-0 p-3';
        container.style.zIndex = '9999';
        document.body.appendChild(container);
    }
    
    const toastId = 'toast-' + Date.now();
    const icon = type === 'success' ? 'check_circle' : 'error';
    const headerClass = type === 'success' ? 'success-toast-header' : 'error-toast-header';
    const toastClass = type === 'success' ? 'success-toast' : 'error-toast';
    const title = type === 'success' ? 'Success' : 'Error';
    
    const toastHTML = `
        <div id="${toastId}" class="toast show ${toastClass}" role="alert" aria-live="assertive" aria-atomic="true" data-bs-autohide="true" data-bs-delay="3000">
            <div class="toast-header ${headerClass}">
                <span class="material-symbols-outlined me-2" style="font-size: 20px; color: white;">${icon}</span>
                <strong class="me-auto text-white">${title}</strong>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body">
                ${message}
            </div>
        </div>
    `;
    
    const toastContainerEl = document.getElementById('toastContainer');
    toastContainerEl.insertAdjacentHTML('beforeend', toastHTML);
    
    const toastElement = document.getElementById(toastId);
    const toast = new bootstrap.Toast(toastElement);
    toast.show();
    
    // Remove toast element after it's hidden
    toastElement.addEventListener('hidden.bs.toast', function() {
        toastElement.remove();
    });
}

function printTable() {
    const printContent = `
        <!DOCTYPE html>
        <html>
        <head>
            <title>Accountants List - Print</title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    margin: 20px;
                    color: #333;
                }
                h1 {
                    color: #003471;
                    text-align: center;
                    border-bottom: 3px solid #003471;
                    padding-bottom: 10px;
                    margin-bottom: 20px;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-top: 20px;
                }
                th {
                    background-color: #003471;
                    color: white;
                    padding: 12px;
                    text-align: left;
                    font-weight: bold;
                    border: 1px solid #ddd;
                }
                td {
                    padding: 10px;
                    border: 1px solid #ddd;
                }
                tr:nth-child(even) {
                    background-color: #f8f9fa;
                }
                .footer {
                    margin-top: 30px;
                    text-align: center;
                    color: #666;
                    font-size: 12px;
                }
                @media print {
                    body {
                        margin: 0;
                    }
                    @page {
                        margin: 1cm;
                    }
                }
            </style>
        </head>
        <body>
            <h1>Accountants List</h1>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Acc. ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Campus</th>
                        <th>App Login</th>
                        <th>Web Login</th>
                    </tr>
                </thead>
                <tbody>
                    ${Array.from(document.querySelectorAll('.accountant-table tbody tr')).map((row, index) => {
                        const cells = row.querySelectorAll('td');
                        const accId = cells[1]?.textContent.trim() || '';
                        const name = cells[3]?.textContent.trim() || '';
                        const email = cells[4]?.textContent.trim() || '';
                        const campus = cells[5]?.textContent.trim() || '';
                        const appLoginSwitch = cells[6]?.querySelector('.toggle-switch-input.app-login-switch');
                        const webLoginSwitch = cells[7]?.querySelector('.toggle-switch-input.web-login-switch');
                        return `
                            <tr>
                                <td>${index + 1}</td>
                                <td>${accId}</td>
                                <td>${name}</td>
                                <td>${email}</td>
                                <td>${campus}</td>
                                <td>${appLoginSwitch?.checked ? 'Enabled' : 'Disabled'}</td>
                                <td>${webLoginSwitch?.checked ? 'Enabled' : 'Disabled'}</td>
                            </tr>
                        `;
                    }).join('')}
                </tbody>
            </table>
            <div class="footer">
                <p>Generated on: ${new Date().toLocaleString()}</p>
            </div>
        </body>
        </html>
    `;
    
    const printWindow = window.open('', '_blank');
    printWindow.document.write(printContent);
    printWindow.document.close();
    printWindow.onload = function() {
        printWindow.print();
    };
}

// Force dropdown menu to always open upward (top mein)
document.addEventListener('DOMContentLoaded', function() {
    // Get all dropdown toggles in the accountant table
    const dropdownToggles = document.querySelectorAll('.accountant-table .dropdown-toggle');
    
    dropdownToggles.forEach(function(toggle) {
        toggle.addEventListener('show.bs.dropdown', function(e) {
            const dropdownMenu = this.nextElementSibling;
            if (dropdownMenu && dropdownMenu.classList.contains('dropdown-menu')) {
                // Force dropdown to open upward (top mein)
                dropdownMenu.style.top = 'auto';
                dropdownMenu.style.bottom = '100%';
                dropdownMenu.style.transform = 'none';
                dropdownMenu.style.marginTop = '0';
                dropdownMenu.style.marginBottom = '2px';
            }
        });
        
        // Also handle when dropdown is shown
        toggle.addEventListener('shown.bs.dropdown', function(e) {
            const dropdownMenu = this.nextElementSibling;
            if (dropdownMenu && dropdownMenu.classList.contains('dropdown-menu')) {
                // Ensure it stays upward (top mein)
                dropdownMenu.style.top = 'auto';
                dropdownMenu.style.bottom = '100%';
                dropdownMenu.style.transform = 'none';
            }
        });
    });
    
    // Prevent scroll on dropdown open
    const tableResponsive = document.querySelector('.default-table-area .table-responsive');
    if (tableResponsive) {
        tableResponsive.style.overflowY = 'hidden';
    }
});
</script>
@endsection
