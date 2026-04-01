@extends('layouts.app')

@section('title', 'Manage Classes')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-3 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0 fs-16 fw-semibold">Manage Classes</h4>
                <button type="button" class="btn btn-sm py-2 px-3 d-inline-flex align-items-center gap-1 rounded-8 class-add-btn" data-bs-toggle="modal" data-bs-target="#classModal" onclick="resetForm()">
                    <span class="material-symbols-outlined" style="font-size: 16px;">add</span>
                    <span>Add New Class</span>
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
                        <a href="{{ route('classes.manage-classes.export', ['format' => 'excel']) }}{{ request()->has('search') ? '?search=' . request('search') : '' }}" class="btn btn-sm px-2 py-1 export-btn excel-btn">
                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">description</span>
                            <span>Excel</span>
                        </a>
                        <a href="{{ route('classes.manage-classes.export', ['format' => 'csv']) }}{{ request()->has('search') ? '?search=' . request('search') : '' }}" class="btn btn-sm px-2 py-1 export-btn csv-btn">
                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">file_present</span>
                            <span>CSV</span>
                        </a>
                        <a href="{{ route('classes.manage-classes.export', ['format' => 'pdf']) }}{{ request()->has('search') ? '?search=' . request('search') : '' }}" class="btn btn-sm px-2 py-1 export-btn pdf-btn" target="_blank">
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
                            <input type="text" id="searchInput" class="form-control border-start-0 border-end-0" placeholder="Search by campus, class name..." value="{{ request('search') }}" onkeypress="handleSearchKeyPress(event)" oninput="handleSearchInput(event)" style="padding: 4px 8px; font-size: 12px;">
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
                    <span>Classes List</span>
                </h5>
            </div>

            <!-- Search Results Info -->
            @if(request('search'))
                <div class="search-results-info">
                    <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle; color: #003471;">search</span>
                    <strong>Search Results:</strong> Showing results for "<strong>{{ request('search') }}</strong>"
                    @if(isset($classes))
                        ({{ $classes->total() }} {{ Str::plural('result', $classes->total()) }} found)
                    @endif
                    <a href="{{ route('classes.manage-classes') }}" class="text-decoration-none ms-2" style="color: #003471;">
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
                                <th>Campus</th>
                                <th>Class Name</th>
                                <th>Numeric No</th>
                                <th>Sections</th>
                                <th>Passout</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if(isset($classes) && $classes->count() > 0)
                                @forelse($classes as $class)
                                    <tr>
                                        <td>{{ $loop->iteration + (($classes->currentPage() - 1) * $classes->perPage()) }}</td>
                                        <td>
                                            <span class="badge bg-primary text-white">{{ $class->campus }}</span>
                                        </td>
                                        <td>
                                            <strong class="text-dark">{{ $class->class_name }}</strong>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary text-white">{{ $class->numeric_no }}</span>
                                        </td>
                                        <td>
                                            @if(isset($class->sections) && count($class->sections) > 0)
                                                <div class="d-flex flex-wrap gap-1">
                                                    @foreach($class->sections as $section)
                                                        <span class="badge bg-success text-white">{{ $section }}</span>
                                                    @endforeach
                                                </div>
                                            @else
                                                <span class="text-muted" style="font-size: 12px;">No sections</span>
                                            @endif
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-warning" title="Passout Class" onclick="passoutClass({{ $class->id }}, '{{ addslashes($class->class_name) }}', '{{ addslashes($class->campus) }}')" style="display: inline-flex; align-items: center; gap: 4px;">
                                                <span class="material-symbols-outlined" style="color: white; font-size: 14px;">school</span>
                                                <span style="font-size: 11px; color: white;">Passout</span>
                                            </button>
                                        </td>
                                        <td class="text-end">
                                            <div class="d-inline-flex gap-1 align-items-center">
                                                <button type="button" class="btn btn-sm btn-info" title="Transfer" onclick="transferClass({{ $class->id }}, '{{ addslashes($class->campus) }}')">
                                                    <span class="material-symbols-outlined" style="color: white;">swap_horiz</span>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-primary" title="Edit" onclick="editClass({{ $class->id }}, '{{ $class->campus }}', '{{ $class->class_name }}', {{ $class->numeric_no }})">
                                                    <span class="material-symbols-outlined" style="color: white;">edit</span>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-danger" title="Delete" onclick="if(confirm('Are you sure you want to delete this class?')) { document.getElementById('delete-form-{{ $class->id }}').submit(); }">
                                                    <span class="material-symbols-outlined" style="color: white;">delete</span>
                                                </button>
                                                <form id="delete-form-{{ $class->id }}" action="{{ route('classes.manage-classes.destroy', $class->id) }}" method="POST" class="d-none">
                                                    @csrf
                                                    @method('DELETE')
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-5">
                                            <span class="material-symbols-outlined" style="font-size: 48px; opacity: 0.3;">inbox</span>
                                            <p class="mt-2 mb-0">No classes found.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            @else
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-5">
                                        <span class="material-symbols-outlined" style="font-size: 48px; opacity: 0.3;">inbox</span>
                                        <p class="mt-2 mb-0">No classes found.</p>
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>

            @if(isset($classes) && $classes->hasPages())
                <div class="mt-3">
                    {{ $classes->links() }}
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Class Modal -->
<div class="modal fade" id="classModal" tabindex="-1" aria-labelledby="classModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
            <div class="modal-header text-white p-3" style="background: linear-gradient(135deg, #003471 0%, #004a9f 100%); border: none;">
                <h5 class="modal-title fs-15 fw-semibold mb-0 d-flex align-items-center gap-2" id="classModalLabel" style="color: white;">
                    <span class="material-symbols-outlined" style="font-size: 20px; color: white;">class</span>
                    <span style="color: white;">Add New Class</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" style="opacity: 0.8;"></button>
            </div>
            <form id="classForm" method="POST" action="{{ route('classes.manage-classes.store') }}">
                @csrf
                <div id="methodField"></div>
                <div class="modal-body p-3">
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Campus <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm class-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">location_on</span>
                                </span>
                                <select class="form-select class-input" name="campus" id="campus" required style="border: none; border-left: 1px solid #e0e7ff;">
                                    <option value="">Select Campus</option>
                                    @foreach($campuses as $campus)
                                        <option value="{{ $campus->campus_name ?? $campus }}">{{ $campus->campus_name ?? $campus }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Class Name <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm class-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">school</span>
                                </span>
                                <input type="text" class="form-control class-input" name="class_name" id="class_name" placeholder="Enter class name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Numeric No <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm class-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">123</span>
                                </span>
                                <input type="number" class="form-control class-input" name="numeric_no" id="numeric_no" placeholder="Enter numeric number" min="1" required>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer p-3" style="background-color: #f8f9fa; border-top: 1px solid #e9ecef;">
                    <button type="button" class="btn btn-sm py-2 px-4 rounded-8" data-bs-dismiss="modal" style="background-color: #6c757d; color: white; border: none; transition: all 0.3s ease;">
                        <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">close</span>
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-sm py-2 px-4 rounded-8 class-submit-btn">
                        <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">save</span>
                        Save Class
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Passout Verification Modal -->
<div class="modal fade" id="passoutVerificationModal" tabindex="-1" aria-labelledby="passoutVerificationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
            <div class="modal-header text-white p-3" style="background: linear-gradient(135deg, #003471 0%, #004a9f 100%); border: none;">
                <h5 class="modal-title fs-15 fw-semibold mb-0 d-flex align-items-center gap-2" id="passoutVerificationModalLabel" style="color: white;">
                    <span class="material-symbols-outlined" style="font-size: 18px;">lock</span>
                    <span>Verification Required</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" style="opacity: 0.8;"></button>
            </div>
            <div class="modal-body p-4">
                <div class="alert alert-warning mb-3" style="font-size: 12px;">
                    <strong>Security Check:</strong> Please enter your login credentials to proceed with passout action.
                </div>
                <div id="verificationError" class="alert alert-danger" style="display: none; font-size: 12px;"></div>
                <form id="verificationForm" onsubmit="event.preventDefault(); verifyAndPassout();">
                    <div class="mb-3">
                        <label for="verificationEmail" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Email</label>
                        <div class="input-group">
                            <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                <span class="material-symbols-outlined" style="font-size: 16px;">email</span>
                            </span>
                            <input type="email" class="form-control" id="verificationEmail" placeholder="Enter your email" required autocomplete="email">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="verificationPassword" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Password</label>
                        <div class="input-group">
                            <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                <span class="material-symbols-outlined" style="font-size: 16px;">lock</span>
                            </span>
                            <input type="password" class="form-control" id="verificationPassword" placeholder="Enter your password" required autocomplete="current-password" onkeypress="if(event.key === 'Enter') { event.preventDefault(); verifyAndPassout(); }">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer p-3" style="background-color: #f8f9fa; border-top: 1px solid #e9ecef;">
                <button type="button" class="btn btn-sm py-2 px-4 rounded-8" data-bs-dismiss="modal" style="background-color: #6c757d; color: white; border: none;">
                    <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">close</span>
                    Cancel
                </button>
                <button type="button" class="btn btn-sm py-2 px-4 rounded-8" id="verifyPassoutBtn" onclick="verifyAndPassout()" style="background: linear-gradient(135deg, #003471 0%, #004a9f 100%); color: white; border: none;">
                    <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">verified</span>
                    Verify & Proceed
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Transfer Modal -->
<div class="modal fade" id="transferModal" tabindex="-1" aria-labelledby="transferModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
            <div class="modal-header text-white p-3" style="background: linear-gradient(135deg, #003471 0%, #004a9f 100%); border: none;">
                <h5 class="modal-title fs-15 fw-semibold mb-0 d-flex align-items-center gap-2" id="transferModalLabel" style="color: white;">
                    <span class="material-symbols-outlined" style="font-size: 18px;">swap_horiz</span>
                    <span>Transfer Students</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" style="opacity: 0.8;"></button>
            </div>
            <form id="transferForm">
                <div class="modal-body p-4">
                    <input type="hidden" id="transferClassId" name="class_id">
                    <div class="row g-3">
                        <!-- From Campus -->
                        <div class="col-md-6">
                            <label for="fromCampus" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">From Campus <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 16px;">location_on</span>
                                </span>
                                <select class="form-select" id="fromCampus" name="from_campus" required>
                                    <option value="">Select Campus</option>
                                    @foreach($campuses as $campus)
                                        <option value="{{ $campus->campus_name ?? $campus }}">{{ $campus->campus_name ?? $campus }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <!-- To Campus -->
                        <div class="col-md-6">
                            <label for="toCampus" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">To Campus <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 16px;">location_on</span>
                                </span>
                                <select class="form-select" id="toCampus" name="to_campus" required>
                                    <option value="">Select Campus</option>
                                    @foreach($campuses as $campus)
                                        <option value="{{ $campus->campus_name ?? $campus }}">{{ $campus->campus_name ?? $campus }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <!-- Class -->
                        <div class="col-md-6">
                            <label for="transferClass" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Class</label>
                            <div class="input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 16px;">class</span>
                                </span>
                                <select class="form-select" id="transferClass" name="class">
                                    <option value="">Select Class (Optional)</option>
                                </select>
                            </div>
                        </div>

                        <!-- Section -->
                        <div class="col-md-6">
                            <label for="transferSection" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Section</label>
                            <div class="input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 16px;">view_list</span>
                                </span>
                                <select class="form-select" id="transferSection" name="section">
                                    <option value="">Select Section (Optional)</option>
                                </select>
                            </div>
                        </div>

                        <!-- Options -->
                        <div class="col-md-4">
                            <label for="moveDues" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Also Move Dues</label>
                            <div class="input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 16px;">account_balance_wallet</span>
                                </span>
                                <select class="form-select" id="moveDues" name="move_dues" required>
                                    <option value="0">No</option>
                                    <option value="1">Yes</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <label for="movePayments" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Also Move Payments</label>
                            <div class="input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 16px;">payments</span>
                                </span>
                                <select class="form-select" id="movePayments" name="move_payments" required>
                                    <option value="0">No</option>
                                    <option value="1">Yes</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <label for="notifyAdmin" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Notify Admin</label>
                            <div class="input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 16px;">notifications</span>
                                </span>
                                <select class="form-select" id="notifyAdmin" name="notify_admin" required>
                                    <option value="0">No</option>
                                    <option value="1" selected>Yes</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer p-3" style="background-color: #f8f9fa; border-top: 1px solid #e9ecef;">
                    <button type="button" class="btn btn-sm py-2 px-4 rounded-8" data-bs-dismiss="modal" style="background-color: #6c757d; color: white; border: none;">
                        <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">close</span>
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-sm py-2 px-4 rounded-8" style="background: linear-gradient(135deg, #003471 0%, #004a9f 100%); color: white; border: none;">
                        <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">swap_horiz</span>
                        Transfer Students
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    /* Class Form Styling */
    #classModal .class-input-group {
        height: 36px;
        border-radius: 8px;
        overflow: hidden;
        transition: all 0.3s ease;
        border: 1px solid #dee2e6;
    }
    
    #classModal .class-input-group:focus-within {
        box-shadow: 0 0 0 3px rgba(0, 52, 113, 0.15);
        border-color: #003471;
    }
    
    #classModal .class-input {
        height: 36px;
        font-size: 13px;
        padding: 0.5rem 0.75rem;
        border: none;
        border-left: 1px solid #e0e7ff;
        border-radius: 0 8px 8px 0;
        transition: all 0.3s ease;
    }
    
    #classModal .class-input:focus {
        border-left-color: #003471;
        box-shadow: none;
        outline: none;
    }
    
    #classModal .input-group-text {
        height: 36px;
        padding: 0 0.75rem;
        display: flex;
        align-items: center;
        border: none;
        border-right: 1px solid #e0e7ff;
        border-radius: 8px 0 0 8px;
        transition: all 0.3s ease;
    }
    
    #classModal .class-input-group:focus-within .input-group-text {
        background-color: #003471 !important;
        color: white !important;
        border-right-color: #003471;
    }
    
    #classModal .form-label {
        margin-bottom: 0.4rem;
        line-height: 1.3;
    }
    
    #classModal .class-submit-btn {
        background: linear-gradient(135deg, #003471 0%, #004a9f 100%);
        color: white;
        border: none;
        font-weight: 500;
        transition: all 0.3s ease;
        box-shadow: 0 2px 8px rgba(0, 52, 113, 0.25);
    }
    
    #classModal .class-submit-btn:hover {
        background: linear-gradient(135deg, #004a9f 0%, #003471 100%);
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0, 52, 113, 0.35);
    }
    
    #classModal .class-submit-btn:active {
        transform: translateY(0);
    }
    
    /* Add New Button Styling */
    .class-add-btn {
        background: linear-gradient(135deg, #003471 0%, #004a9f 100%);
        color: white;
        border: none;
        font-weight: 500;
        transition: all 0.3s ease;
        box-shadow: 0 2px 6px rgba(0, 52, 113, 0.2);
    }
    
    .class-add-btn:hover {
        background: linear-gradient(135deg, #004a9f 0%, #003471 100%);
        transform: translateY(-1px);
        box-shadow: 0 4px 10px rgba(0, 52, 113, 0.3);
        color: white;
    }
    
    .class-add-btn:active {
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
        font-size: 12px;
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
        font-size: 13px;
        border: 1px solid #dee2e6;
        border-collapse: separate;
        border-spacing: 0;
        border-radius: 8px;
        overflow: hidden;
        background-color: white;
    }
    
    .default-table-area table thead {
        border-bottom: 2px solid #dee2e6;
        background-color: #f8f9fa;
    }
    
    .default-table-area table thead th {
        padding: 8px 10px;
        font-size: 12px;
        font-weight: 600;
        vertical-align: middle;
        line-height: 1.3;
        white-space: nowrap;
        border: 1px solid #dee2e6;
        background-color: #f8f9fa;
        color: #495057;
        text-transform: none;
    }
    
    .default-table-area table thead th:first-child {
        border-left: 1px solid #dee2e6;
    }
    
    .default-table-area table thead th:last-child {
        border-right: 1px solid #dee2e6;
    }
    
    .default-table-area table tbody td {
        padding: 8px 10px;
        font-size: 13px;
        vertical-align: middle;
        line-height: 1.3;
        border: 1px solid #dee2e6;
        background-color: white;
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
    
    .default-table-area table tbody tr:hover {
        background-color: #f8f9fa;
    }
    
    .default-table-area table tbody tr:hover td {
        background-color: #f8f9fa;
    }
    
    .default-table-area .badge {
        font-size: 11px;
        padding: 3px 6px;
        font-weight: 600;
    }
    
    .default-table-area .badge.bg-success {
        background-color: #28a745 !important;
    }
    
    .default-table-area td {
        max-width: 200px;
    }
    
    .default-table-area td:nth-child(5) {
        max-width: 300px;
        min-width: 150px;
    }
    
    .default-table-area .material-symbols-outlined {
        font-size: 13px !important;
    }
    
    .default-table-area .btn-sm {
        font-size: 12px;
        padding: 3px 6px;
        min-width: 28px;
        height: 28px;
    }
    
    .default-table-area .btn-sm .material-symbols-outlined {
        font-size: 13px !important;
        vertical-align: middle;
    }
    
    .default-table-area .btn-primary .material-symbols-outlined,
    .default-table-area .btn-danger .material-symbols-outlined,
    .default-table-area .btn-info .material-symbols-outlined,
    .default-table-area .btn-warning .material-symbols-outlined {
        color: white !important;
    }
    
    .default-table-area .btn-warning {
        background-color: #ff9800 !important;
        border-color: #ff9800 !important;
    }
    
    .default-table-area .btn-warning:hover {
        background-color: #f57c00 !important;
        border-color: #f57c00 !important;
        color: white !important;
    }
</style>

<script>
// Reset form when opening modal for new class
function resetForm() {
    document.getElementById('classForm').reset();
    document.getElementById('classForm').action = "{{ route('classes.manage-classes.store') }}";
    document.getElementById('methodField').innerHTML = '';
    const modalLabel = document.getElementById('classModalLabel');
    modalLabel.innerHTML = `
        <span class="material-symbols-outlined" style="font-size: 20px; color: white;">class</span>
        <span style="color: white;">Add New Class</span>
    `;
    document.querySelector('.class-submit-btn').innerHTML = `
        <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">save</span>
        Save Class
    `;
}

// Edit class function
function editClass(id, campus, className, numericNo) {
    document.getElementById('campus').value = campus;
    document.getElementById('class_name').value = className;
    document.getElementById('numeric_no').value = numericNo;
    document.getElementById('classForm').action = "{{ url('classes/manage-classes') }}/" + id;
    document.getElementById('methodField').innerHTML = '@method("PUT")';
    const modalLabel = document.getElementById('classModalLabel');
    modalLabel.innerHTML = `
        <span class="material-symbols-outlined" style="font-size: 20px; color: white;">edit</span>
        <span style="color: white;">Edit Class</span>
    `;
    document.querySelector('.class-submit-btn').innerHTML = `
        <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">save</span>
        Update Class
    `;
    
    const modal = new bootstrap.Modal(document.getElementById('classModal'));
    modal.show();
}

// Transfer class function
function transferClass(id, fromCampus) {
    // Set form data
    document.getElementById('transferClassId').value = id;
    const fromCampusSelect = document.getElementById('fromCampus');
    fromCampusSelect.value = fromCampus || '';
    document.getElementById('toCampus').value = '';
    document.getElementById('transferClass').innerHTML = '<option value="">Select Class (Optional)</option>';
    document.getElementById('transferSection').innerHTML = '<option value="">Select Section (Optional)</option>';
    document.getElementById('moveDues').value = '0';
    document.getElementById('movePayments').value = '0';
    document.getElementById('notifyAdmin').value = '1';
    
    // Disable Class and Section until To Campus is selected
    document.getElementById('transferClass').disabled = true;
    document.getElementById('transferSection').disabled = true;
    
    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('transferModal'));
    modal.show();
}

// Load classes based on campus
function loadClassesForCampus(campus, targetSelectId) {
    if (!campus) {
        const selectElement = document.getElementById(targetSelectId);
        selectElement.innerHTML = '<option value="">Select Class (Optional)</option>';
        selectElement.disabled = true;
        // Also clear and disable section
        if (targetSelectId === 'transferClass') {
            document.getElementById('transferSection').innerHTML = '<option value="">Select Section (Optional)</option>';
            document.getElementById('transferSection').disabled = true;
        }
        return;
    }
    
    const selectElement = document.getElementById(targetSelectId);
    selectElement.innerHTML = '<option value="">Loading...</option>';
    selectElement.disabled = true;
    
    fetch(`{{ route('classes.manage-classes.get-classes-by-campus') }}?campus=${encodeURIComponent(campus)}`, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
        }
    })
    .then(response => response.json())
    .then(data => {
        selectElement.innerHTML = '<option value="">Select Class (Optional)</option>';
        if (data.classes && data.classes.length > 0) {
            data.classes.forEach(className => {
                const option = document.createElement('option');
                option.value = className;
                option.textContent = className;
                selectElement.appendChild(option);
            });
        }
        selectElement.disabled = false;
    })
    .catch(error => {
        console.error('Error loading classes:', error);
        selectElement.innerHTML = '<option value="">Error loading classes</option>';
        selectElement.disabled = false;
    });
}

// Load sections based on class
function loadSectionsForClass(className, campus, targetSelectId) {
    if (!className) {
        document.getElementById(targetSelectId).innerHTML = '<option value="">Select Section (Optional)</option>';
        return;
    }
    
    const selectElement = document.getElementById(targetSelectId);
    selectElement.innerHTML = '<option value="">Loading...</option>';
    selectElement.disabled = true;
    
    const params = new URLSearchParams();
    params.append('class', className);
    if (campus) {
        params.append('campus', campus);
    }
    
    fetch(`{{ route('classes.manage-classes.get-sections-by-class') }}?${params.toString()}`, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
        }
    })
    .then(response => response.json())
    .then(data => {
        selectElement.innerHTML = '<option value="">Select Section (Optional)</option>';
        if (data.sections && data.sections.length > 0) {
            data.sections.forEach(sectionName => {
                const option = document.createElement('option');
                option.value = sectionName;
                option.textContent = sectionName;
                selectElement.appendChild(option);
            });
        }
        selectElement.disabled = false;
    })
    .catch(error => {
        console.error('Error loading sections:', error);
        selectElement.innerHTML = '<option value="">Error loading sections</option>';
        selectElement.disabled = false;
    });
}

// Handle transfer form submission
document.getElementById('transferForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const classId = formData.get('class_id');
    const fromCampus = formData.get('from_campus');
    const toCampus = formData.get('to_campus');
    
    if (!toCampus) {
        alert('Please select a destination campus.');
        return;
    }
    
    if (fromCampus === toCampus) {
        alert('From Campus and To Campus cannot be the same.');
        return;
    }
    
    if (!confirm(`Are you sure you want to transfer all students from "${fromCampus}" to "${toCampus}"?`)) {
        return;
    }
    
    // Show loading state
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalBtnText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> Transferring...';
    
    fetch(`{{ url('classes/manage-classes') }}/${classId}/transfer`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message || 'Students transferred successfully!');
            const modal = bootstrap.Modal.getInstance(document.getElementById('transferModal'));
            modal.hide();
            window.location.reload();
        } else {
            alert(data.message || 'Error: Failed to transfer students.');
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnText;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while transferring students. Please try again.');
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalBtnText;
    });
});

// Store passout data for verification
let pendingPassoutData = null;

// Passout class function
function passoutClass(id, className, campus) {
    // Store passout data
    pendingPassoutData = { id, className, campus };
    
    // Show verification modal
    const modal = new bootstrap.Modal(document.getElementById('passoutVerificationModal'));
    document.getElementById('verificationEmail').value = '';
    document.getElementById('verificationPassword').value = '';
    document.getElementById('verificationError').textContent = '';
    document.getElementById('verificationError').style.display = 'none';
    modal.show();
}

// Verify and proceed with passout
function verifyAndPassout() {
    const email = document.getElementById('verificationEmail').value.trim();
    const password = document.getElementById('verificationPassword').value;
    
    if (!email || !password) {
        document.getElementById('verificationError').textContent = 'Please enter both email and password.';
        document.getElementById('verificationError').style.display = 'block';
        return;
    }
    
    // Show loading state
    const verifyBtn = document.getElementById('verifyPassoutBtn');
    const originalBtnText = verifyBtn.innerHTML;
    verifyBtn.disabled = true;
    verifyBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> Verifying...';
    
    // Verify credentials
    fetch(`{{ url('classes/manage-classes') }}/${pendingPassoutData.id}/verify-passout`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            email: email,
            password: password
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Close modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('passoutVerificationModal'));
            modal.hide();
            
            // Proceed with passout
            proceedWithPassout();
        } else {
            document.getElementById('verificationError').textContent = data.message || 'Invalid credentials. Please try again.';
            document.getElementById('verificationError').style.display = 'block';
            verifyBtn.disabled = false;
            verifyBtn.innerHTML = originalBtnText;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        document.getElementById('verificationError').textContent = 'An error occurred during verification. Please try again.';
        document.getElementById('verificationError').style.display = 'block';
        verifyBtn.disabled = false;
        verifyBtn.innerHTML = originalBtnText;
    });
}

// Proceed with passout after verification
function proceedWithPassout() {
    if (!pendingPassoutData) return;
    
    const { id, className, campus } = pendingPassoutData;
    
    if (!confirm(`Are you sure you want to passout all students from class "${className}" in campus "${campus}"?\n\nThis will mark all students in this class as "Passout" and clear their sections.`)) {
        pendingPassoutData = null;
        return;
    }
    
    // Show loading state
    const btn = document.querySelector(`button[onclick*="passoutClass(${id}"]`);
    if (btn) {
        const originalHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> Processing...';
        
        // Make AJAX request
        fetch(`{{ url('classes/manage-classes') }}/${id}/passout`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message || 'Students passed out successfully!');
                window.location.reload();
            } else {
                alert(data.message || 'Error: ' + (data.error || 'Failed to passout students'));
                btn.disabled = false;
                btn.innerHTML = originalHtml;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while passing out students. Please try again.');
            btn.disabled = false;
            btn.innerHTML = originalHtml;
        });
    }
    
    pendingPassoutData = null;
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
    const searchParam = document.getElementById('searchInput')?.value?.trim();
    let url = '{{ route("classes.manage-classes.print") }}?auto_print=1';
    if (searchParam) url += '&search=' + encodeURIComponent(searchParam);

    const w = window.open(url, '_blank');
    if (!w) {
        window.location.href = url;
    }
}

// Add event listeners for dynamic dropdowns in transfer modal
document.addEventListener('DOMContentLoaded', function() {
    const toCampusSelect = document.getElementById('toCampus');
    const transferClassSelect = document.getElementById('transferClass');
    
    // When To Campus changes, load classes (since we're transferring TO that campus)
    if (toCampusSelect) {
        toCampusSelect.addEventListener('change', function() {
            const campus = this.value;
            loadClassesForCampus(campus, 'transferClass');
            // Clear section when campus changes
            document.getElementById('transferSection').innerHTML = '<option value="">Select Section (Optional)</option>';
        });
    }
    
    // When Class changes, load sections based on To Campus
    if (transferClassSelect) {
        transferClassSelect.addEventListener('change', function() {
            const className = this.value;
            const campus = toCampusSelect ? toCampusSelect.value : '';
            loadSectionsForClass(className, campus, 'transferSection');
        });
    }
});
</script>
@endsection
