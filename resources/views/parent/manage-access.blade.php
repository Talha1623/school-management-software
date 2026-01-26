@extends('layouts.app')

@section('title', 'Manage Access')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-3 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <button type="button" class="btn btn-sm py-2 px-3 d-inline-flex align-items-center gap-1 rounded-8 parent-add-btn" data-bs-toggle="modal" data-bs-target="#parentModal" onclick="resetForm()">
                    <span class="material-symbols-outlined" style="font-size: 16px;">add</span>
                    <span>Add New Parent</span>
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
                        <a href="{{ route('parent.manage-access.export', ['format' => 'excel']) }}{{ request()->has('search') ? '?search=' . request('search') : '' }}" class="btn btn-sm px-2 py-1 export-btn excel-btn">
                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">description</span>
                            <span>Excel</span>
                        </a>
                        <a href="{{ route('parent.manage-access.export', ['format' => 'csv']) }}{{ request()->has('search') ? '?search=' . request('search') : '' }}" class="btn btn-sm px-2 py-1 export-btn csv-btn">
                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">file_present</span>
                            <span>CSV</span>
                        </a>
                        <a href="{{ route('parent.manage-access.export', ['format' => 'pdf']) }}{{ request()->has('search') ? '?search=' . request('search') : '' }}" class="btn btn-sm px-2 py-1 export-btn pdf-btn" target="_blank">
                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">picture_as_pdf</span>
                            <span>PDF</span>
                        </a>
                        <button type="button" class="btn btn-sm px-2 py-1 export-btn print-btn" onclick="printTable()">
                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">print</span>
                            <span>Print</span>
                        </button>
                        <form action="{{ route('parent.manage-access.delete-all') }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete ALL parent accounts? This action cannot be undone!');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm px-2 py-1 export-btn delete-all-btn">
                                <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">delete_sweep</span>
                                <span>Delete All</span>
                            </button>
                        </form>
                    </div>
                    
                    <!-- Search -->
                    <div class="d-flex align-items-center gap-2">
                        <label for="searchInput" class="mb-0 fs-13 fw-medium text-dark">Search:</label>
                        <div class="input-group input-group-sm search-input-group" style="width: 280px;">
                            <span class="input-group-text bg-light border-end-0" style="background-color: #f0f4ff !important; border-color: #e0e7ff; padding: 4px 8px;">
                                <span class="material-symbols-outlined" style="font-size: 14px; color: #003471;">search</span>
                            </span>
                            <input type="text" id="searchInput" class="form-control border-start-0 border-end-0" placeholder="Search by name, email, phone..." value="{{ request('search') }}" onkeypress="handleSearchKeyPress(event)" oninput="handleSearchInput(event)" style="padding: 4px 8px; font-size: 13px;">
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
                    <span class="material-symbols-outlined" style="font-size: 18px;">list</span>
                    <span>Parent Accounts List</span>
                </h5>
            </div>

            <!-- Search Results Info -->
            @if(request('search'))
                <div class="search-results-info">
                    <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle; color: #003471;">search</span>
                    <strong>Search Results:</strong> Showing results for "<strong>{{ request('search') }}</strong>" 
                    ({{ $parents->total() }} {{ Str::plural('result', $parents->total()) }} found)
                    <a href="{{ route('parent.manage-access') }}" class="text-decoration-none ms-2" style="color: #003471;">
                        <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">close</span>
                        Clear
                    </a>
                </div>
            @endif

            <div class="default-table-area" style="margin-top: 0;">
                <div class="table-responsive" style="overflow-x: auto;">
                    <table class="table table-sm table-hover" style="white-space: nowrap;">
                        <thead>
                            <tr>
                                <th style="padding: 8px 12px; font-size: 13px; font-weight: 600;">#</th>
                                <th style="padding: 8px 12px; font-size: 13px; font-weight: 600;">Name</th>
                                <th style="padding: 8px 12px; font-size: 13px; font-weight: 600;">Email</th>
                                <th style="padding: 8px 12px; font-size: 13px; font-weight: 600;">Phone</th>
                                <th style="padding: 8px 12px; font-size: 13px; font-weight: 600;">WhatsApp</th>
                                <th style="padding: 8px 12px; font-size: 13px; font-weight: 600;">ID Card Number</th>
                                <th style="padding: 8px 12px; font-size: 13px; font-weight: 600;">Address</th>
                                <th style="padding: 8px 12px; font-size: 13px; font-weight: 600;">Profession</th>
                                <th class="text-end" style="padding: 8px 12px; font-size: 13px; font-weight: 600;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($parents as $parent)
                                <tr data-parent-id="{{ $parent->id }}">
                                    <td style="padding: 8px 12px; font-size: 13px;">{{ $loop->iteration + (($parents->currentPage() - 1) * $parents->perPage()) }}</td>
                                    <td style="padding: 8px 12px; font-size: 13px;">
                                        <strong class="text-primary">{{ $parent->name }}</strong>
                                    </td>
                                    <td style="padding: 8px 12px; font-size: 13px;">
                                        <span class="text-muted">
                                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">email</span>
                                            {{ $parent->email }}
                                        </span>
                                    </td>
                                    <td style="padding: 8px 12px; font-size: 13px;">
                                        @if($parent->phone)
                                            <span class="badge bg-light text-dark" style="font-size: 11px;">
                                                <span class="material-symbols-outlined" style="font-size: 12px; vertical-align: middle;">phone</span>
                                                {{ $parent->phone }}
                                            </span>
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td style="padding: 8px 12px; font-size: 13px;">
                                        @if($parent->whatsapp)
                                            <span class="badge bg-success text-white" style="font-size: 11px;">
                                                <span class="material-symbols-outlined" style="font-size: 12px; vertical-align: middle;">chat</span>
                                                {{ $parent->whatsapp }}
                                            </span>
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td style="padding: 8px 12px; font-size: 13px;">
                                        @if($parent->id_card_number)
                                            <span class="badge bg-info text-white" style="font-size: 11px;">{{ $parent->id_card_number }}</span>
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td style="padding: 8px 12px; font-size: 13px;">
                                        <span class="text-muted" title="{{ $parent->address }}">
                                            {{ $parent->address ? Str::limit($parent->address, 30) : 'N/A' }}
                                        </span>
                                    </td>
                                    <td style="padding: 8px 12px; font-size: 13px;">
                                        @if($parent->profession)
                                            <span class="badge bg-secondary text-white" style="font-size: 11px;">{{ $parent->profession }}</span>
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td class="text-end" style="padding: 8px 12px; font-size: 13px; text-align: center;">
                                        <div class="d-inline-flex gap-1">
                                            <button type="button" class="btn btn-sm btn-warning px-2 py-1" onclick="resetPassword({{ $parent->id }}, '{{ addslashes($parent->name) }}')" title="Reset Password">
                                                <span class="material-symbols-outlined" style="font-size: 14px; color: white;">lock_reset</span>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-info px-2 py-1" onclick="connectStudent({{ $parent->id }}, '{{ addslashes($parent->name) }}', '{{ $parent->id_card_number ?? '' }}', '{{ $parent->email ?? '' }}')" title="Connect Student">
                                                <span class="material-symbols-outlined" style="font-size: 14px; color: white;">link</span>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-primary px-2 py-1" onclick="editParent({{ $parent->id }}, '{{ addslashes($parent->name) }}', '{{ addslashes($parent->email) }}', '{{ $parent->phone ?? '' }}', '{{ $parent->whatsapp ?? '' }}', '{{ $parent->id_card_number ?? '' }}', '{{ addslashes($parent->address ?? '') }}', '{{ addslashes($parent->profession ?? '') }}')" title="Edit">
                                                <span class="material-symbols-outlined" style="font-size: 14px; color: white;">edit</span>
                                            </button>
                                            <form action="{{ route('parent.manage-access.destroy', $parent) }}" method="POST" class="d-inline parent-delete-form" data-parent-id="{{ $parent->id }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger px-2 py-1" title="Delete">
                                                    <span class="material-symbols-outlined" style="font-size: 14px; color: white;">delete</span>
                                                </button>
                                            </form>
                                            <div class="dropdown parent-action-dropdown">
                                                <button class="btn btn-sm btn-dark px-2 py-1 no-caret" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Print Actions">
                                                    <span class="material-symbols-outlined" style="font-size: 14px; color: white;">pie_chart</span>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end parent-action-menu">
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('accounting.fee-voucher.print', ['parent_id' => $parent->id, 'auto_print' => 1]) }}" target="_blank">
                                                            Parent Voucher
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('parent.dues-report.print', ['parent_account' => $parent->id, 'auto_print' => 1]) }}" target="_blank">
                                                            Dues Report
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('parent.print-gate-passes.print', ['parent_id' => $parent->id, 'auto_print' => 1]) }}" target="_blank">
                                                            Gate Pass
                                                        </a>
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
                                        <p class="mt-2 mb-0">No parent accounts found.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if($parents->hasPages())
                <div class="d-flex justify-content-center align-items-center mt-3 pt-3 border-top">
                    <div class="pagination-wrapper">
                        {{ $parents->links() }}
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Parent Modal -->
<div class="modal fade" id="parentModal" tabindex="-1" aria-labelledby="parentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
            <div class="modal-header text-white p-3" style="background: linear-gradient(135deg, #003471 0%, #004a9f 100%); border: none;">
                <h5 class="modal-title fs-15 fw-semibold mb-0 d-flex align-items-center gap-2" id="parentModalLabel">
                    <span class="material-symbols-outlined" style="font-size: 20px;">person_add</span>
                    <span>Add New Parent</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" style="opacity: 0.8;"></button>
            </div>
            <form id="parentForm" method="POST" action="{{ route('parent.manage-access.store') }}">
                @csrf
                <div id="methodField"></div>
                <div class="modal-body p-3">
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Name <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm parent-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">person</span>
                                </span>
                                <input type="text" class="form-control parent-input" name="name" id="name" placeholder="Enter name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Email <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm parent-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">email</span>
                                </span>
                                <input type="email" class="form-control parent-input" name="email" id="email" placeholder="Enter email" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Password <span class="text-danger" id="passwordRequired">*</span></label>
                            <div class="input-group input-group-sm parent-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">lock</span>
                                </span>
                                <input type="text" class="form-control parent-input" name="password" id="password" value="parent" placeholder="Enter password" required>
                            </div>
                            <small class="text-muted" id="passwordHint" style="font-size: 11px;">Leave blank to keep current password when editing</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Phone</label>
                            <div class="input-group input-group-sm parent-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">phone</span>
                                </span>
                                <input type="text" class="form-control parent-input" name="phone" id="phone" placeholder="Enter phone number">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">WhatsApp</label>
                            <div class="input-group input-group-sm parent-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">chat</span>
                                </span>
                                <input type="text" class="form-control parent-input" name="whatsapp" id="whatsapp" placeholder="Enter WhatsApp number">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">ID Card Number</label>
                            <div class="input-group input-group-sm parent-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">badge</span>
                                </span>
                                <input type="text" class="form-control parent-input" name="id_card_number" id="id_card_number" placeholder="Enter ID card number">
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Address</label>
                            <div class="input-group input-group-sm parent-input-group">
                                <span class="input-group-text align-items-start" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471; padding-top: 8px;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">location_on</span>
                                </span>
                                <textarea class="form-control parent-input" name="address" id="address" rows="2" placeholder="Enter address" style="resize: vertical;"></textarea>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Profession</label>
                            <div class="input-group input-group-sm parent-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">work</span>
                                </span>
                                <input type="text" class="form-control parent-input" name="profession" id="profession" placeholder="Enter profession">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer p-3" style="background-color: #f8f9fa; border-top: 1px solid #e9ecef;">
                    <button type="button" class="btn btn-sm py-2 px-4 rounded-8" data-bs-dismiss="modal" style="background-color: #6c757d; color: white; border: none; transition: all 0.3s ease;">
                        <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">close</span>
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-sm py-2 px-4 rounded-8 parent-submit-btn" style="color: white;">
                        <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">save</span>
                        Save Parent
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reset Password Modal -->
<div class="modal fade" id="resetPasswordModal" tabindex="-1" aria-labelledby="resetPasswordModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
            <div class="modal-header text-white p-3" style="background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%); border: none;">
                <h5 class="modal-title fs-15 fw-semibold mb-0 d-flex align-items-center gap-2" id="resetPasswordModalLabel">
                    <span class="material-symbols-outlined" style="font-size: 20px;">lock_reset</span>
                    <span>Reset Password</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" style="opacity: 0.8;"></button>
            </div>
            <form id="resetPasswordForm" method="POST" action="">
                @csrf
                @method('PUT')
                <div class="modal-body p-3">
                    <div class="mb-3">
                        <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Parent Name</label>
                        <input type="text" class="form-control form-control-sm" id="reset_parent_name" readonly style="background-color: #f8f9fa;">
                    </div>
                    <div class="mb-3">
                        <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">New Password <span class="text-danger">*</span></label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                <span class="material-symbols-outlined" style="font-size: 15px;">lock</span>
                            </span>
                            <input type="text" class="form-control" name="password" id="reset_password" value="parent" placeholder="Enter new password" required minlength="6">
                        </div>
                        <small class="text-muted" style="font-size: 11px;">Minimum 6 characters</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Confirm Password <span class="text-danger">*</span></label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                <span class="material-symbols-outlined" style="font-size: 15px;">lock</span>
                            </span>
                            <input type="text" class="form-control" name="password_confirmation" id="reset_password_confirmation" value="parent" placeholder="Confirm new password" required minlength="6">
                        </div>
                    </div>
                </div>
                <div class="modal-footer p-3" style="background-color: #f8f9fa; border-top: 1px solid #e9ecef;">
                    <button type="button" class="btn btn-sm py-2 px-4 rounded-8" data-bs-dismiss="modal" style="background-color: #6c757d; color: white; border: none;">
                        <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">close</span>
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-sm py-2 px-4 rounded-8" style="background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%); color: white; border: none;">
                        <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">lock_reset</span>
                        Reset Password
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Connect Student Modal -->
<div class="modal fade" id="connectStudentModal" tabindex="-1" aria-labelledby="connectStudentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
            <div class="modal-header text-white p-3" style="background: linear-gradient(135deg, #17a2b8 0%, #138496 100%); border: none;">
                <h5 class="modal-title fs-15 fw-semibold mb-0 d-flex align-items-center gap-2" id="connectStudentModalLabel">
                    <span class="material-symbols-outlined" style="font-size: 20px;">link</span>
                    <span>Connect Student to Parent</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" style="opacity: 0.8;"></button>
            </div>
            <form id="connectStudentForm" method="POST" action="">
                @csrf
                <div class="modal-body p-3">
                    <div class="mb-3">
                        <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Parent Name</label>
                        <input type="text" class="form-control form-control-sm" id="connect_parent_name" readonly style="background-color: #f8f9fa;">
                        <input type="hidden" id="connect_parent_id">
                    </div>
                    
                    <!-- Connected Students Section -->
                    <div class="mb-3" id="connectedStudentsSection" style="display: none;">
                        <div class="card border border-info" style="background-color: #e7f3ff;">
                            <div class="card-body p-2">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <strong class="text-primary fs-13">
                                        <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">link</span>
                                        Connected Students (<span id="connected_students_count">0</span>)
                                    </strong>
                                </div>
                                <div class="table-responsive" style="max-height: 150px; overflow-y: auto;">
                                    <table class="table table-sm table-bordered mb-0" style="font-size: 11px; background-color: white;">
                                        <thead>
                                            <tr>
                                                <th style="padding: 4px 6px; font-size: 11px;">Code</th>
                                                <th style="padding: 4px 6px; font-size: 11px;">Name</th>
                                                <th style="padding: 4px 6px; font-size: 11px;">Class</th>
                                                <th style="padding: 4px 6px; font-size: 11px;">Section</th>
                                                <th style="padding: 4px 6px; font-size: 11px; text-align: center;">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody id="connectedStudentsTableBody">
                                            <!-- Connected students will be loaded here -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Search Bar -->
                    <div class="mb-3">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                <span class="material-symbols-outlined" style="font-size: 15px;">search</span>
                            </span>
                            <input type="text" class="form-control" id="search_student_table" placeholder="Search by student code, name, class, section..." autocomplete="off" onkeyup="filterStudentTable(this.value)">
                        </div>
                    </div>
                    
                    <!-- Students Table -->
                    <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                        <table class="table table-sm table-hover" id="studentsTable" style="white-space: nowrap;">
                            <thead style="position: sticky; top: 0; background-color: #f8f9fa; z-index: 10;">
                                <tr>
                                    <th style="padding: 8px 12px; font-size: 13px; font-weight: 600;">Select</th>
                                    <th style="padding: 8px 12px; font-size: 13px; font-weight: 600;">Code</th>
                                    <th style="padding: 8px 12px; font-size: 13px; font-weight: 600;">Name</th>
                                    <th style="padding: 8px 12px; font-size: 13px; font-weight: 600;">Class</th>
                                    <th style="padding: 8px 12px; font-size: 13px; font-weight: 600;">Section</th>
                                    <th style="padding: 8px 12px; font-size: 13px; font-weight: 600;">Father Name</th>
                                    <th style="padding: 8px 12px; font-size: 13px; font-weight: 600;">Father Email</th>
                                </tr>
                            </thead>
                            <tbody id="studentsTableBody">
                                <!-- Students will be loaded here -->
                            </tbody>
                        </table>
                    </div>
                    <input type="hidden" name="student_id" id="selected_student_id">
                </div>
                <div class="modal-footer p-3" style="background-color: #f8f9fa; border-top: 1px solid #e9ecef;">
                    <button type="button" class="btn btn-sm py-2 px-4 rounded-8" data-bs-dismiss="modal" style="background-color: #6c757d; color: white; border: none;">
                        <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">close</span>
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-sm py-2 px-4 rounded-8" style="background: linear-gradient(135deg, #17a2b8 0%, #138496 100%); color: white; border: none;">
                        <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">link</span>
                        Connect Student
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    /* Parent Form Styling */
    #parentModal .parent-input-group {
        height: 36px;
        border-radius: 8px;
        overflow: hidden;
        transition: all 0.3s ease;
        border: 1px solid #dee2e6;
    }
    
    #parentModal .parent-input-group:focus-within {
        box-shadow: 0 0 0 3px rgba(0, 52, 113, 0.15);
        border-color: #003471;
    }
    
    #parentModal .parent-input {
        height: 36px;
        font-size: 13px;
        padding: 0.5rem 0.75rem;
        border: none;
        border-left: 1px solid #e0e7ff;
        border-radius: 0 8px 8px 0;
        transition: all 0.3s ease;
    }
    
    #parentModal .parent-input:focus {
        border-left-color: #003471;
        box-shadow: none;
        outline: none;
    }
    
    #parentModal .input-group-text {
        height: 36px;
        padding: 0 0.75rem;
        display: flex;
        align-items: center;
        border: none;
        border-right: 1px solid #e0e7ff;
        border-radius: 8px 0 0 8px;
        transition: all 0.3s ease;
    }
    
    #parentModal .parent-input-group:focus-within .input-group-text {
        background-color: #003471 !important;
        color: white !important;
        border-right-color: #003471;
    }
    
    #parentModal .form-label {
        margin-bottom: 0.4rem;
        line-height: 1.3;
    }
    
    #parentModal textarea.parent-input {
        min-height: 60px;
        border-left: 1px solid #e0e7ff;
        padding-top: 0.5rem;
    }
    
    #parentModal textarea.parent-input:focus {
        border-left-color: #003471;
    }
    
    #parentModal .parent-submit-btn {
        background: linear-gradient(135deg, #003471 0%, #004a9f 100%);
        color: white;
        border: none;
        font-weight: 500;
        transition: all 0.3s ease;
        box-shadow: 0 2px 8px rgba(0, 52, 113, 0.25);
    }
    
    #parentModal .parent-submit-btn:hover {
        background: linear-gradient(135deg, #004a9f 0%, #003471 100%);
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0, 52, 113, 0.35);
    }

    .parent-action-dropdown .no-caret::after {
        display: none !important;
    }

    .parent-action-menu {
        min-width: 200px;
        border-radius: 8px;
        border: 1px solid #e9ecef;
        box-shadow: 0 6px 18px rgba(0, 0, 0, 0.12);
        z-index: 1050;
    }
    
    #parentModal .parent-submit-btn:active {
        transform: translateY(0);
    }
    
    #parentModal .modal-body {
        background-color: #ffffff;
    }
    
    #parentModal .rounded-8 {
        border-radius: 8px;
    }
    
    #parentModal .form-control,
    #parentModal .form-select {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    #parentModal .parent-input-group:hover {
        border-color: #003471;
    }
    
    /* Add New Button Styling */
    .parent-add-btn {
        background: linear-gradient(135deg, #003471 0%, #004a9f 100%);
        color: white;
        border: none;
        font-weight: 500;
        transition: all 0.3s ease;
        box-shadow: 0 2px 6px rgba(0, 52, 113, 0.2);
    }
    
    .parent-add-btn:hover {
        background: linear-gradient(135deg, #004a9f 0%, #003471 100%);
        transform: translateY(-1px);
        box-shadow: 0 4px 10px rgba(0, 52, 113, 0.3);
        color: white;
    }
    
    .parent-add-btn:active {
        transform: translateY(0);
    }
    
    .rounded-8 {
        border-radius: 8px;
    }
    
    /* Export Buttons Styling */
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
    
    .delete-all-btn {
        background-color: #dc3545;
        color: white;
    }
    
    .delete-all-btn:hover {
        background-color: #c82333;
        color: white;
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(220, 53, 69, 0.3);
    }
    
    .export-btn:active {
        transform: translateY(0);
    }
    
    /* Search Input Group Styling */
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
    
    /* Search Results Info */
    .search-results-info {
        padding: 8px 12px;
        background-color: #e7f3ff;
        border-left: 3px solid #003471;
        border-radius: 4px;
        margin-bottom: 15px;
        font-size: 13px;
    }
    
    /* Table Compact Styling */
    .default-table-area table {
        margin-bottom: 0;
        border-spacing: 0;
        border-collapse: collapse;
        border: 1px solid #dee2e6;
    }
    
    .default-table-area table thead {
        border-bottom: 1px solid #dee2e6;
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
    
    .default-table-area table thead th:first-child {
        border-left: 1px solid #dee2e6;
    }
    
    .default-table-area table thead th:last-child {
        border-right: 1px solid #dee2e6;
    }
    
    .default-table-area table tbody td {
        padding: 5px 10px;
        font-size: 12px;
        vertical-align: middle;
        line-height: 1.4;
        border: 1px solid #dee2e6;
    }
    
    .default-table-area table tbody td:first-child {
        border-left: 1px solid #dee2e6;
    }
    
    .default-table-area table tbody td:last-child {
        border-right: 1px solid #dee2e6;
    }
    
    .default-table-area table tbody tr:last-child td {
        border-bottom: 1px solid #dee2e6;
    }
    
    .default-table-area table thead th:first-child,
    .default-table-area table tbody td:first-child {
        padding-left: 10px;
    }
    
    .default-table-area table thead th:last-child,
    .default-table-area table tbody td:last-child {
        padding-right: 10px;
    }
    
    .default-table-area table tbody tr {
        height: 36px;
    }
    
    .default-table-area table tbody tr:first-child td {
        border-top: none;
    }
    
    .default-table-area .table-responsive {
        padding: 0;
        margin-top: 0;
    }
    
    .default-table-area {
        margin-top: 0 !important;
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
        line-height: 1.2;
        min-height: 26px;
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
    
    /* Pagination Styling - Only Show Next/Previous */
    .pagination-wrapper {
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .pagination-wrapper .pagination {
        margin-bottom: 0;
        gap: 8px;
    }
    
    .pagination-wrapper .pagination .page-link {
        color: #003471;
        background-color: #fff;
        border: 1px solid #dee2e6;
        padding: 0.5rem 1rem;
        font-size: 14px;
        border-radius: 6px;
        transition: all 0.3s ease;
        text-decoration: none;
        font-weight: 500;
    }
    
    .pagination-wrapper .pagination .page-link:hover {
        color: #fff;
        background-color: #003471;
        border-color: #003471;
        transform: translateY(-1px);
        box-shadow: 0 2px 4px rgba(0, 52, 113, 0.2);
    }
    
    .pagination-wrapper .pagination .page-item.disabled .page-link {
        color: #6c757d;
        background-color: #f8f9fa;
        border-color: #dee2e6;
        cursor: not-allowed;
        opacity: 0.6;
    }
    
    .pagination-wrapper .pagination .page-item.disabled .page-link:hover {
        transform: none;
        box-shadow: none;
        background-color: #f8f9fa;
        color: #6c757d;
    }
    
    .pagination-wrapper .pagination .page-link:focus {
        box-shadow: 0 0 0 3px rgba(0, 52, 113, 0.15);
        outline: none;
    }
    
    /* Hide numbered page buttons - only show Previous/Next */
    .pagination-wrapper .pagination .page-item:not(:first-child):not(:last-child) {
        display: none !important;
    }
    
    /* Show only Previous and Next buttons */
    .pagination-wrapper .pagination .page-item:first-child,
    .pagination-wrapper .pagination .page-item:last-child {
        display: block !important;
    }
    
    /* Hide arrow symbols from Previous/Next buttons */
    .pagination-wrapper .pagination .page-link span:first-child,
    .pagination-wrapper .pagination .page-link span:last-child {
        display: none !important;
    }
    
    /* Remove arrow symbols (», «) from text */
    .pagination-wrapper .pagination .page-link::before,
    .pagination-wrapper .pagination .page-link::after {
        content: none !important;
    }
    
    /* Hide active page number button */
    .pagination-wrapper .pagination .page-item.active:not(:first-child):not(:last-child) {
        display: none !important;
    }
    
</style>

<script>
// Hide numbered page buttons and arrows, only show Previous/Next text
function hideNumberedPaginationButtons() {
    const paginations = document.querySelectorAll('.pagination-wrapper .pagination, .pagination');
    
    paginations.forEach(pagination => {
        if (pagination) {
            const pageItems = pagination.querySelectorAll('.page-item');
            pageItems.forEach((item, index) => {
                const link = item.querySelector('.page-link');
                if (link) {
                    const text = link.textContent.trim();
                    const ariaLabel = link.getAttribute('aria-label');
                    
                    // Check if it's Previous or Next
                    const isPrevious = ariaLabel === 'Previous' || text.includes('Previous');
                    const isNext = ariaLabel === 'Next' || text.includes('Next');
                    
                    if (isPrevious || isNext) {
                        // Remove arrow symbols from Previous/Next buttons
                        link.innerHTML = link.innerHTML
                            .replace(/«/g, '')
                            .replace(/»/g, '')
                            .replace(/&laquo;/g, '')
                            .replace(/&raquo;/g, '')
                            .replace(/Previous/g, 'Previous')
                            .replace(/Next/g, 'Next')
                            .trim();
                        
                        // Keep only the text, remove any span elements with arrows
                        const spans = link.querySelectorAll('span');
                        spans.forEach(span => {
                            const spanText = span.textContent.trim();
                            if (spanText === '«' || spanText === '»' || spanText === '&laquo;' || spanText === '&raquo;') {
                                span.remove();
                            }
                        });
                        
                        // Set clean text
                        if (isPrevious) {
                            link.textContent = 'Previous';
                        } else if (isNext) {
                            link.textContent = 'Next';
                        }
                    } else {
                        // Hide numbered buttons
                        item.style.display = 'none';
                    }
                } else {
                    // Hide if no link found and it's not first/last
                    if (index !== 0 && index !== pageItems.length - 1) {
                        item.style.display = 'none';
                    }
                }
            });
        }
    });
}

// Run on page load
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', hideNumberedPaginationButtons);
} else {
    hideNumberedPaginationButtons();
}

// Also run after delays to catch dynamically loaded content
setTimeout(hideNumberedPaginationButtons, 100);
setTimeout(hideNumberedPaginationButtons, 500);

// Watch for DOM changes
const observer = new MutationObserver(hideNumberedPaginationButtons);
observer.observe(document.body, { childList: true, subtree: true });

function resetForm() {
    document.getElementById('parentForm').action = '{{ route('parent.manage-access.store') }}';
    document.getElementById('parentForm').reset();
    document.getElementById('methodField').innerHTML = '';
    document.getElementById('parentModalLabel').textContent = 'Add New Parent';
    document.getElementById('password').required = true;
    document.getElementById('password').value = 'parent';
    document.getElementById('passwordRequired').style.display = 'inline';
    document.getElementById('passwordHint').style.display = 'none';
}

function editParent(id, name, email, phone, whatsapp, idCard, address, profession) {
    document.getElementById('parentForm').action = '{{ route('parent.manage-access.update', ':id') }}'.replace(':id', id);
    document.getElementById('methodField').innerHTML = '@method('PUT')';
    document.getElementById('parentModalLabel').textContent = 'Edit Parent';
    
    document.getElementById('name').value = name || '';
    document.getElementById('email').value = email || '';
    document.getElementById('phone').value = phone || '';
    document.getElementById('whatsapp').value = whatsapp || '';
    document.getElementById('id_card_number').value = idCard || '';
    document.getElementById('address').value = address || '';
    document.getElementById('profession').value = profession || '';
    document.getElementById('password').value = '';
    document.getElementById('password').required = false;
    document.getElementById('passwordRequired').style.display = 'none';
    document.getElementById('passwordHint').style.display = 'block';
    
    new bootstrap.Modal(document.getElementById('parentModal')).show();
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
            <h3 style="text-align: center; margin-bottom: 20px; color: #003471;">Parent Accounts List</h3>
            ${printContents}
        </div>
    `;
    
    window.print();
    document.body.innerHTML = originalContents;
    window.location.reload();
}

// Reset Password
function resetPassword(parentId, parentName) {
    document.getElementById('resetPasswordForm').action = '{{ route('parent.manage-access.reset-password', ':id') }}'.replace(':id', parentId);
    document.getElementById('reset_parent_name').value = parentName;
    document.getElementById('reset_password').value = 'parent';
    document.getElementById('reset_password_confirmation').value = 'parent';
    new bootstrap.Modal(document.getElementById('resetPasswordModal')).show();
}

function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;',
    };
    return String(text || '').replace(/[&<>"']/g, function(m) { return map[m]; });
}

function getCsrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') : '';
}

function buildParentRow(parent) {
    const deleteUrl = '{{ route('parent.manage-access.destroy', ':id') }}'.replace(':id', parent.id);
    const editUrl = '{{ route('parent.manage-access.update', ':id') }}'.replace(':id', parent.id);
    const voucherUrl = "{{ route('accounting.fee-voucher.print', ['parent_id' => ':id', 'auto_print' => 1]) }}".replace(':id', parent.id);
    const duesUrl = "{{ route('parent.dues-report.print', ['parent_account' => ':id', 'auto_print' => 1]) }}".replace(':id', parent.id);
    const gatePassUrl = "{{ route('parent.print-gate-passes.print', ['parent_id' => ':id', 'auto_print' => 1]) }}".replace(':id', parent.id);

    return `
        <tr data-parent-id="${parent.id}">
            <td style="padding: 8px 12px; font-size: 13px;">NEW</td>
            <td style="padding: 8px 12px; font-size: 13px;">
                <strong class="text-primary">${escapeHtml(parent.name)}</strong>
            </td>
            <td style="padding: 8px 12px; font-size: 13px;">
                <span class="text-muted">
                    <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">email</span>
                    ${escapeHtml(parent.email)}
                </span>
            </td>
            <td style="padding: 8px 12px; font-size: 13px;">
                ${parent.phone ? `<span class="badge bg-light text-dark" style="font-size: 11px;"><span class="material-symbols-outlined" style="font-size: 12px; vertical-align: middle;">phone</span> ${escapeHtml(parent.phone)}</span>` : '<span class="text-muted">N/A</span>'}
            </td>
            <td style="padding: 8px 12px; font-size: 13px;">
                ${parent.whatsapp ? `<span class="badge bg-success text-white" style="font-size: 11px;"><span class="material-symbols-outlined" style="font-size: 12px; vertical-align: middle;">chat</span> ${escapeHtml(parent.whatsapp)}</span>` : '<span class="text-muted">N/A</span>'}
            </td>
            <td style="padding: 8px 12px; font-size: 13px;">
                ${parent.id_card_number ? `<span class="badge bg-info text-white" style="font-size: 11px;">${escapeHtml(parent.id_card_number)}</span>` : '<span class="text-muted">N/A</span>'}
            </td>
            <td style="padding: 8px 12px; font-size: 13px;">
                <span class="text-muted" title="${escapeHtml(parent.address)}">
                    ${parent.address ? escapeHtml(parent.address).slice(0, 30) : 'N/A'}
                </span>
            </td>
            <td style="padding: 8px 12px; font-size: 13px;">
                ${parent.profession ? `<span class="badge bg-secondary text-white" style="font-size: 11px;">${escapeHtml(parent.profession)}</span>` : '<span class="text-muted">N/A</span>'}
            </td>
            <td class="text-end" style="padding: 8px 12px; font-size: 13px; text-align: center;">
                <div class="d-inline-flex gap-1">
                    <button type="button" class="btn btn-sm btn-warning px-2 py-1" onclick="resetPassword(${parent.id}, '${escapeHtml(parent.name)}')" title="Reset Password">
                        <span class="material-symbols-outlined" style="font-size: 14px; color: white;">lock_reset</span>
                    </button>
                    <button type="button" class="btn btn-sm btn-info px-2 py-1" onclick="connectStudent(${parent.id}, '${escapeHtml(parent.name)}', '${escapeHtml(parent.id_card_number)}', '${escapeHtml(parent.email)}')" title="Connect Student">
                        <span class="material-symbols-outlined" style="font-size: 14px; color: white;">link</span>
                    </button>
                    <button type="button" class="btn btn-sm btn-primary px-2 py-1" onclick="editParent(${parent.id}, '${escapeHtml(parent.name)}', '${escapeHtml(parent.email)}', '${escapeHtml(parent.phone)}', '${escapeHtml(parent.whatsapp)}', '${escapeHtml(parent.id_card_number)}', '${escapeHtml(parent.address)}', '${escapeHtml(parent.profession)}')" title="Edit">
                        <span class="material-symbols-outlined" style="font-size: 14px; color: white;">edit</span>
                    </button>
                    <form action="${deleteUrl}" method="POST" class="d-inline parent-delete-form" data-parent-id="${parent.id}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-danger px-2 py-1" title="Delete">
                            <span class="material-symbols-outlined" style="font-size: 14px; color: white;">delete</span>
                        </button>
                    </form>
                    <div class="dropdown parent-action-dropdown">
                        <button class="btn btn-sm btn-dark px-2 py-1 no-caret" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Print Actions">
                            <span class="material-symbols-outlined" style="font-size: 14px; color: white;">pie_chart</span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end parent-action-menu">
                            <li>
                                <a class="dropdown-item" href="${voucherUrl}" target="_blank">Parent Voucher</a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="${duesUrl}" target="_blank">Dues Report</a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="${gatePassUrl}" target="_blank">Gate Pass</a>
                            </li>
                        </ul>
                    </div>
                </div>
            </td>
        </tr>
    `;
}

function removeEmptyRowIfExists(tbody) {
    const emptyRow = tbody.querySelector('tr td[colspan]');
    if (emptyRow) {
        emptyRow.closest('tr').remove();
    }
}

document.addEventListener('DOMContentLoaded', function () {
    const parentForm = document.getElementById('parentForm');
    const tbody = document.querySelector('.default-table-area tbody');

    if (parentForm) {
        parentForm.addEventListener('submit', async function (event) {
            const methodField = document.getElementById('methodField');
            if (methodField && methodField.innerHTML.includes('PUT')) {
                return;
            }
            event.preventDefault();

            const formData = new FormData(parentForm);
            const response = await fetch(parentForm.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': getCsrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
                body: formData,
                credentials: 'same-origin',
            });

            if (!response.ok) {
                const data = await response.json().catch(() => null);
                if (data && data.errors) {
                    const firstError = Object.values(data.errors)[0];
                    alert(Array.isArray(firstError) ? firstError[0] : firstError);
                } else {
                    alert('Unable to add parent. Please check the form.');
                }
                return;
            }

            const data = await response.json();
            if (data && data.parent) {
                removeEmptyRowIfExists(tbody);
                tbody.insertAdjacentHTML('afterbegin', buildParentRow(data.parent));
            }

            const modalEl = document.getElementById('parentModal');
            const modalInstance = bootstrap.Modal.getInstance(modalEl);
            if (modalInstance) {
                modalInstance.hide();
            }
            parentForm.reset();
            document.getElementById('password').value = 'parent';
        });
    }

    document.addEventListener('submit', async function (event) {
        const form = event.target;
        if (!form.classList.contains('parent-delete-form')) return;
        event.preventDefault();

        if (!confirm('Are you sure you want to delete this parent account?')) {
            return;
        }

        const response = await fetch(form.action, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': getCsrfToken(),
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
            credentials: 'same-origin',
        });

        if (!response.ok) {
            const errorText = await response.text().catch(() => '');
            alert(errorText || 'Unable to delete parent. Please try again.');
            return;
        }

        const data = await response.json().catch(() => ({}));
        const parentId = data && data.id ? data.id : form.dataset.parentId;
        const row = tbody.querySelector(`tr[data-parent-id="${parentId}"]`);
        if (row) {
            row.remove();
        }

        if (tbody.querySelectorAll('tr').length === 0) {
            tbody.insertAdjacentHTML('beforeend', `
                <tr>
                    <td colspan="9" class="text-center text-muted py-5">
                        <span class="material-symbols-outlined" style="font-size: 48px; opacity: 0.3;">inbox</span>
                        <p class="mt-2 mb-0">No parent accounts found.</p>
                    </td>
                </tr>
            `);
        }
    });
});

let allStudents = [];

// Connect Student
function connectStudent(parentId, parentName, idCard, email) {
    document.getElementById('connectStudentForm').action = '{{ route('parent.manage-access.connect-student', ':id') }}'.replace(':id', parentId);
    document.getElementById('connect_parent_name').value = parentName;
    document.getElementById('connect_parent_id').value = parentId;
    document.getElementById('selected_student_id').value = '';
    document.getElementById('search_student_table').value = '';
    
    // Load all students and connected students
    loadAllStudents(parentId);
    
    new bootstrap.Modal(document.getElementById('connectStudentModal')).show();
}

// Load all students
function loadAllStudents(parentId) {
    const tbody = document.getElementById('studentsTableBody');
    const connectedTbody = document.getElementById('connectedStudentsTableBody');
    tbody.innerHTML = '<tr><td colspan="7" class="text-center"><div class="spinner-border spinner-border-sm" role="status"></div> Loading students...</td></tr>';
    
    fetch(`{{ route('parent.manage-access.get-all-students') }}?parent_id=${parentId}`)
        .then(response => response.json())
        .then(data => {
            allStudents = data.students || [];
            renderStudentsTable(allStudents);
            
            // Render connected students
            if (data.connected_students && data.connected_students.length > 0) {
                renderConnectedStudents(data.connected_students, parentId);
            } else {
                document.getElementById('connectedStudentsSection').style.display = 'none';
            }
        })
        .catch(error => {
            console.error('Error loading students:', error);
            tbody.innerHTML = '<tr><td colspan="7" class="text-center text-danger">Error loading students. Please try again.</td></tr>';
        });
}

// Render connected students
function renderConnectedStudents(connectedStudents, parentId) {
    const section = document.getElementById('connectedStudentsSection');
    const tbody = document.getElementById('connectedStudentsTableBody');
    const countSpan = document.getElementById('connected_students_count');
    
    if (connectedStudents.length === 0) {
        section.style.display = 'none';
        return;
    }
    
    section.style.display = 'block';
    countSpan.textContent = connectedStudents.length;
    
    const disconnectRoute = '{{ route('parent.manage-access.disconnect-student', ':id') }}'.replace(':id', parentId);
    
    tbody.innerHTML = connectedStudents.map(student => `
        <tr>
            <td style="padding: 4px 6px; font-size: 11px;">${student.code}</td>
            <td style="padding: 4px 6px; font-size: 11px;"><strong>${student.name}</strong></td>
            <td style="padding: 4px 6px; font-size: 11px;">${student.class}</td>
            <td style="padding: 4px 6px; font-size: 11px;">${student.section}</td>
            <td style="padding: 4px 6px; font-size: 11px; text-align: center;">
                <form action="${disconnectRoute}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to disconnect this student?');">
                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                    <input type="hidden" name="student_id" value="${student.id}">
                    <button type="submit" class="btn btn-sm btn-outline-danger px-1 py-0" title="Disconnect" style="font-size: 10px;">
                        <span class="material-symbols-outlined" style="font-size: 12px;">link_off</span>
                    </button>
                </form>
            </td>
        </tr>
    `).join('');
}

// Render students table
function renderStudentsTable(students) {
    const tbody = document.getElementById('studentsTableBody');
    const parentId = parseInt(document.getElementById('connect_parent_id').value);
    
    if (students.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted">No students found.</td></tr>';
        return;
    }
    
    tbody.innerHTML = students.map(student => {
        const isConnected = student.parent_account_id == parentId;
        const rowStyle = isConnected ? 'background-color: #e7f3ff; opacity: 0.7;' : 'cursor: pointer;';
        const disabledAttr = isConnected ? 'disabled' : '';
        const connectedBadge = isConnected ? '<span class="badge bg-info text-white ms-1" style="font-size: 10px;">Connected</span>' : '';
        
        return `
        <tr class="student-row" data-student-id="${student.id}" style="${rowStyle}">
            <td style="padding: 8px 12px; font-size: 13px; text-align: center;">
                <input type="radio" name="student_radio" value="${student.id}" onchange="selectStudent(${student.id})" ${disabledAttr}>
            </td>
            <td style="padding: 8px 12px; font-size: 13px;">${student.code}</td>
            <td style="padding: 8px 12px; font-size: 13px;"><strong class="text-primary">${student.name}</strong>${connectedBadge}</td>
            <td style="padding: 8px 12px; font-size: 13px;">${student.class}</td>
            <td style="padding: 8px 12px; font-size: 13px;">${student.section}</td>
            <td style="padding: 8px 12px; font-size: 13px;">${student.father_name}</td>
            <td style="padding: 8px 12px; font-size: 13px;">${student.father_email}</td>
        </tr>
        `;
    }).join('');
    
    // Add click event to rows
    document.querySelectorAll('.student-row').forEach(row => {
        const studentId = row.getAttribute('data-student-id');
        const radio = row.querySelector('input[type="radio"]');
        const isDisabled = radio && radio.disabled;
        
        if (!isDisabled) {
            row.addEventListener('click', function(e) {
                if (e.target.type !== 'radio') {
                    if (radio) {
                        radio.checked = true;
                        selectStudent(studentId);
                    }
                }
            });
            
            row.addEventListener('mouseenter', function() {
                this.style.backgroundColor = '#f8f9fa';
            });
            
            row.addEventListener('mouseleave', function() {
                if (!radio || !radio.checked) {
                    this.style.backgroundColor = '';
                }
            });
        }
    });
}

// Select student
function selectStudent(studentId) {
    const parentId = parseInt(document.getElementById('connect_parent_id').value);
    const student = allStudents.find(s => s.id == studentId);
    
    // Check if student is already connected
    if (student && student.parent_account_id == parentId) {
        alert('This student is already connected to this parent. Please select another student.');
        document.getElementById('selected_student_id').value = '';
        return;
    }
    
    document.getElementById('selected_student_id').value = studentId;
    
    // Highlight selected row
    document.querySelectorAll('.student-row').forEach(row => {
        const rowStudentId = row.getAttribute('data-student-id');
        const radio = row.querySelector('input[type="radio"]');
        const isConnected = radio && radio.disabled;
        
        if (rowStudentId == studentId) {
            row.style.backgroundColor = '#e7f3ff';
        } else if (!isConnected) {
            row.style.backgroundColor = '';
        }
    });
}

// Filter student table
function filterStudentTable(query) {
    if (!query || query.trim() === '') {
        renderStudentsTable(allStudents);
        return;
    }
    
    const searchLower = query.toLowerCase();
    const filtered = allStudents.filter(student => 
        (student.code && student.code.toLowerCase().includes(searchLower)) ||
        (student.name && student.name.toLowerCase().includes(searchLower)) ||
        (student.class && student.class.toLowerCase().includes(searchLower)) ||
        (student.section && student.section.toLowerCase().includes(searchLower)) ||
        (student.father_name && student.father_name.toLowerCase().includes(searchLower)) ||
        (student.father_email && student.father_email.toLowerCase().includes(searchLower))
    );
    
    renderStudentsTable(filtered);
}

// Form validation for reset password
document.getElementById('resetPasswordForm')?.addEventListener('submit', function(e) {
    const password = document.getElementById('reset_password').value;
    const confirmPassword = document.getElementById('reset_password_confirmation').value;
    
    if (password !== confirmPassword) {
        e.preventDefault();
        alert('Passwords do not match!');
        return false;
    }
    
    if (password.length < 6) {
        e.preventDefault();
        alert('Password must be at least 6 characters long!');
        return false;
    }
});

// Form validation for connect student
document.getElementById('connectStudentForm')?.addEventListener('submit', function(e) {
    const studentId = document.getElementById('selected_student_id').value;
    
    if (!studentId) {
        e.preventDefault();
        alert('Please select a student from the suggestions!');
        return false;
    }
});
</script>
@endsection
