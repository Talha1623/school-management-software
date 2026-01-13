@extends('layouts.app')

@section('title', 'Classes to Show')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-3 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0 fs-16 fw-semibold">Classes to Show</h4>
                <button type="button" class="btn btn-sm py-2 px-3 d-inline-flex align-items-center gap-1 rounded-8 class-add-btn" data-bs-toggle="modal" data-bs-target="#classModal" onclick="resetForm()">
                    <span class="material-symbols-outlined" style="font-size: 16px;">add</span>
                    <span>Add New Class</span>
                </button>
            </div>

            <!-- Success/Error Messages -->
            <div id="alertContainer"></div>

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
                            <span class="input-group-text bg-light border-end-0" style="background-color: #f0f4ff !important; border-color: #e0e7ff; padding: 4px 8px;">
                                <span class="material-symbols-outlined" style="font-size: 14px; color: #003471;">search</span>
                            </span>
                            <input type="text" id="searchInput" class="form-control border-start-0 border-end-0" placeholder="Search classes..." value="{{ request('search') }}" onkeypress="handleSearchKeyPress(event)" style="padding: 4px 8px; font-size: 12px;">
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
                    <span class="material-symbols-outlined" style="font-size: 18px;">school</span>
                    <span>Classes List</span>
                </h5>
            </div>

            <!-- Search Results Info -->
            @if(request('search'))
                <div class="search-results-info">
                    <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle; color: #003471;">search</span>
                    <strong>Search Results:</strong> Showing results for "<strong>{{ request('search') }}</strong>" 
                    ({{ $classes->total() }} {{ Str::plural('result', $classes->total()) }} found)
                    <a href="{{ route('website-management.classes-show') }}" class="text-decoration-none ms-2" style="color: #003471;">
                        <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">close</span>
                        Clear
                    </a>
                </div>
            @endif

            <div class="default-table-area" style="margin-top: 0;">
                <div class="table-responsive" style="max-height: none; overflow: visible; overflow-x: auto;">
                    <table class="table table-sm table-hover" style="margin-bottom: 0; white-space: nowrap;">
                        <thead>
                            <tr>
                                <th style="padding: 12px 15px; font-size: 14px;">#</th>
                                <th style="padding: 12px 15px; font-size: 14px;">Campus</th>
                                <th style="padding: 12px 15px; font-size: 14px;">Class</th>
                                <th style="padding: 12px 15px; font-size: 14px;">Timings</th>
                                <th style="padding: 12px 15px; font-size: 14px;">Age Limit</th>
                                <th style="padding: 12px 15px; font-size: 14px;">Tuition Fee</th>
                                <th style="padding: 12px 15px; font-size: 14px;">Show on Website</th>
                                <th style="padding: 12px 15px; font-size: 14px; text-align: center;">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="classesTableBody">
                            @forelse($classes as $class)
                                <tr data-id="{{ $class->id }}">
                                    <td style="padding: 12px 15px; font-size: 14px;">{{ $loop->iteration + (($classes->currentPage() - 1) * $classes->perPage()) }}</td>
                                    <td style="padding: 12px 15px; font-size: 14px;">
                                        <span class="badge bg-secondary text-white" style="font-size: 11px;">{{ $class->campus ?? 'N/A' }}</span>
                                    </td>
                                    <td style="padding: 12px 15px; font-size: 14px;">
                                        <strong class="text-primary">{{ $class->class ?? 'N/A' }}</strong>
                                    </td>
                                    <td style="padding: 12px 15px; font-size: 14px;">
                                        @if($class->class_timing_from || $class->class_timing_to)
                                            {{ $class->class_timing_from ?? 'N/A' }} - {{ $class->class_timing_to ?? 'N/A' }}
                                        @else
                                            N/A
                                        @endif
                                    </td>
                                    <td style="padding: 12px 15px; font-size: 14px;">
                                        @if($class->student_age_limit_from || $class->student_age_limit_to)
                                            {{ $class->student_age_limit_from ?? 'N/A' }} - {{ $class->student_age_limit_to ?? 'N/A' }}
                                        @else
                                            N/A
                                        @endif
                                    </td>
                                    <td style="padding: 12px 15px; font-size: 14px;">
                                        Rs. {{ number_format($class->class_tuition_fee ?? 0, 2) }}
                                    </td>
                                    <td style="padding: 12px 15px; font-size: 14px;">
                                        <span class="badge {{ $class->show_on_website_main_page == 'Yes' ? 'bg-success' : 'bg-danger' }} text-white" style="font-size: 11px;">
                                            {{ $class->show_on_website_main_page }}
                                        </span>
                                    </td>
                                    <td style="padding: 12px 15px; font-size: 14px; text-align: center;">
                                        <div class="d-inline-flex gap-1">
                                            <button type="button" class="btn btn-sm btn-primary px-2 py-1" onclick="editClass({{ $class->id }})" title="Edit">
                                                <span class="material-symbols-outlined" style="font-size: 14px; color: white;">edit</span>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-danger px-2 py-1" onclick="deleteClass({{ $class->id }})" title="Delete">
                                                <span class="material-symbols-outlined" style="font-size: 14px; color: white;">delete</span>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-5">
                                        <span class="material-symbols-outlined" style="font-size: 48px; opacity: 0.3;">school</span>
                                        <p class="mt-2 mb-0">No classes to show found.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if($classes->hasPages())
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
            <div class="modal-header p-3" style="background: linear-gradient(135deg, #003471 0%, #004a9f 100%); border: none;">
                <h5 class="modal-title fs-15 fw-semibold mb-0 d-flex align-items-center gap-2" id="classModalLabel" style="color: white !important;">
                    <span class="material-symbols-outlined" style="font-size: 20px; color: white !important;">school</span>
                    <span>Add New Class</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" style="opacity: 0.8;"></button>
            </div>
            <form id="classForm" method="POST" action="{{ route('website-management.classes-show.store') }}">
                @csrf
                <div id="methodField"></div>
                <div class="modal-body p-3" style="max-height: 70vh; overflow-y: auto;">
                    <div class="row g-2">
                        <!-- Campus -->
                        <div class="col-md-6">
                            <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Campus</label>
                            <div class="input-group input-group-sm class-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">apartment</span>
                                </span>
                                <select class="form-control class-input" name="campus" id="campus" style="border: none; border-left: 1px solid #e0e7ff; border-radius: 0 8px 8px 0;">
                                    <option value="">Select Campus</option>
                                    @foreach($campuses as $campus)
                                        <option value="{{ $campus->campus_name ?? $campus }}">{{ $campus->campus_name ?? $campus }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="text-danger error-message" id="campus-error"></div>
                        </div>

                        <!-- Class -->
                        <div class="col-md-6">
                            <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Class</label>
                            <div class="input-group input-group-sm class-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">school</span>
                                </span>
                                <select class="form-control class-input" name="class" id="class" style="border: none; border-left: 1px solid #e0e7ff; border-radius: 0 8px 8px 0;">
                                    <option value="">Select Class</option>
                                    @foreach($allClasses as $className)
                                        <option value="{{ $className }}">{{ $className }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="text-danger error-message" id="class-error"></div>
                        </div>

                        <!-- Class Timings From -->
                        <div class="col-md-6">
                            <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Class Timing (From)</label>
                            <div class="input-group input-group-sm class-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">schedule</span>
                                </span>
                                <input type="time" class="form-control class-input" name="class_timing_from" id="class_timing_from" style="border: none; border-left: 1px solid #e0e7ff; border-radius: 0 8px 8px 0;">
                            </div>
                            <div class="text-danger error-message" id="class_timing_from-error"></div>
                        </div>

                        <!-- Class Timings To -->
                        <div class="col-md-6">
                            <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Class Timing (To)</label>
                            <div class="input-group input-group-sm class-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">schedule</span>
                                </span>
                                <input type="time" class="form-control class-input" name="class_timing_to" id="class_timing_to" style="border: none; border-left: 1px solid #e0e7ff; border-radius: 0 8px 8px 0;">
                            </div>
                            <div class="text-danger error-message" id="class_timing_to-error"></div>
                        </div>

                        <!-- Student Age Limit From -->
                        <div class="col-md-6">
                            <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Student Age Limit (From)</label>
                            <div class="input-group input-group-sm class-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">person_apron</span>
                                </span>
                                <input type="number" class="form-control class-input" name="student_age_limit_from" id="student_age_limit_from" placeholder="e.g., 5" min="0" style="border: none; border-left: 1px solid #e0e7ff; border-radius: 0 8px 8px 0;">
                            </div>
                            <div class="text-danger error-message" id="student_age_limit_from-error"></div>
                        </div>

                        <!-- Student Age Limit To -->
                        <div class="col-md-6">
                            <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Student Age Limit (To)</label>
                            <div class="input-group input-group-sm class-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">person_apron</span>
                                </span>
                                <input type="number" class="form-control class-input" name="student_age_limit_to" id="student_age_limit_to" placeholder="e.g., 10" min="0" style="border: none; border-left: 1px solid #e0e7ff; border-radius: 0 8px 8px 0;">
                            </div>
                            <div class="text-danger error-message" id="student_age_limit_to-error"></div>
                        </div>

                        <!-- Class Tuition Fee -->
                        <div class="col-md-6">
                            <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Class Tuition Fee</label>
                            <div class="input-group input-group-sm class-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">attach_money</span>
                                </span>
                                <input type="number" step="0.01" class="form-control class-input" name="class_tuition_fee" id="class_tuition_fee" placeholder="Enter tuition fee" min="0" style="border: none; border-left: 1px solid #e0e7ff; border-radius: 0 8px 8px 0;">
                            </div>
                            <div class="text-danger error-message" id="class_tuition_fee-error"></div>
                        </div>

                        <!-- Show On Website Main Page -->
                        <div class="col-md-6">
                            <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Show On Website Main Page <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm class-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">visibility</span>
                                </span>
                                <select class="form-control class-input" name="show_on_website_main_page" id="show_on_website_main_page" required style="border: none; border-left: 1px solid #e0e7ff; border-radius: 0 8px 8px 0;">
                                    <option value="No">No</option>
                                    <option value="Yes">Yes</option>
                                </select>
                            </div>
                            <div class="text-danger error-message" id="show_on_website_main_page-error"></div>
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
                        <span id="submitBtnText">Add Class</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    /* Class Form Styling */
    #classModal .class-input-group {
        border-radius: 8px;
        overflow: hidden;
        transition: all 0.3s ease;
        border: 1px solid #dee2e6;
        height: 32px;
    }
    
    #classModal .class-input-group:focus-within {
        box-shadow: 0 0 0 3px rgba(0, 52, 113, 0.15);
        border-color: #003471;
    }
    
    #classModal .class-input {
        font-size: 13px;
        padding: 0.35rem 0.65rem;
        border: none;
        border-left: 1px solid #e0e7ff;
        border-radius: 0 8px 8px 0;
        transition: all 0.3s ease;
        height: 32px;
    }
    
    #classModal .class-input:focus {
        border-left-color: #003471;
        box-shadow: none;
        outline: none;
    }
    
    #classModal .input-group-text {
        padding: 0.35rem 0.65rem;
        display: flex;
        align-items: center;
        border: none;
        border-right: 1px solid #e0e7ff;
        border-radius: 8px 0 0 8px;
        transition: all 0.3s ease;
        height: 32px;
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
    
    .class-add-btn, .class-submit-btn {
        background: linear-gradient(135deg, #003471 0%, #004a9f 100%);
        color: white;
        border: none;
        font-weight: 500;
        transition: all 0.3s ease;
        box-shadow: 0 2px 8px rgba(0, 52, 113, 0.25);
    }
    
    .class-add-btn:hover, .class-submit-btn:hover {
        background: linear-gradient(135deg, #004a9f 0%, #003471 100%);
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0, 52, 113, 0.35);
        color: white;
    }

    /* Table Styling */
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
        padding: 12px 15px;
        font-size: 14px;
        font-weight: 600;
        vertical-align: middle;
        line-height: 1.5;
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
        padding: 12px 15px;
        font-size: 14px;
        vertical-align: middle;
        line-height: 1.5;
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
        padding-left: 15px;
    }
    
    .default-table-area table thead th:last-child,
    .default-table-area table tbody td:last-child {
        padding-right: 15px;
    }
    
    .default-table-area table tbody tr:first-child td {
        border-top: none;
    }
    
    .default-table-area .badge {
        font-size: 11px;
        padding: 4px 8px;
    }
    
    .default-table-area .btn-sm .material-symbols-outlined {
        font-size: 14px !important;
        color: white !important;
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
    
    .search-btn {
        background: linear-gradient(135deg, #003471 0%, #004a9f 100%);
        color: white;
        border: none;
    }
    
    .search-results-info {
        padding: 8px 12px;
        background-color: #e7f3ff;
        border-left: 3px solid #003471;
        border-radius: 4px;
        margin-bottom: 15px;
        font-size: 13px;
    }
    
    .error-message {
        font-size: 11px;
        margin-top: 2px;
    }
</style>

<script>
let editMode = false;
let currentEditId = null;

// Function to reset the form for adding a new class
window.resetForm = function() {
    editMode = false;
    currentEditId = null;
    document.getElementById('classForm').action = '{{ route('website-management.classes-show.store') }}';
    document.getElementById('classForm').reset();
    document.getElementById('methodField').innerHTML = '';
    document.getElementById('classModalLabel').innerHTML = '<span class="material-symbols-outlined" style="font-size: 20px; color: white !important;">school</span><span>Add New Class</span>';
    document.getElementById('submitBtnText').textContent = 'Add Class';
    clearErrors();
};

// Function to clear previous validation errors
function clearErrors() {
    document.querySelectorAll('.error-message').forEach(el => el.textContent = '');
    document.querySelectorAll('.class-input').forEach(el => el.classList.remove('is-invalid'));
}

// Handle form submission via AJAX
document.getElementById('classForm').addEventListener('submit', function(e) {
    e.preventDefault();
    clearErrors();

    const form = e.target;
    const formData = new FormData(form);
    const url = editMode 
        ? `/website-management/classes-show/${currentEditId}`
        : '/website-management/classes-show';
    
    // Add _method for PUT request
    if (editMode) {
        formData.append('_method', 'PUT');
    }
    
    fetch(url, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json',
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.errors) {
            // Display validation errors
            for (const [key, value] of Object.entries(data.errors)) {
                const errorElement = document.getElementById(`${key}-error`);
                if (errorElement) {
                    errorElement.textContent = value[0];
                }
                const inputElement = document.getElementById(key);
                if (inputElement) {
                    inputElement.classList.add('is-invalid');
                }
            }
        } else if (data.success) {
            showAlert('success', data.message);
            const modal = bootstrap.Modal.getInstance(document.getElementById('classModal'));
            if (modal) modal.hide();
            
            if (editMode) {
                updateTableRow(data.class);
                resetForm();
            } else {
                addTableRow(data.class);
                resetForm();
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('danger', 'An error occurred. Please try again.');
    });
});

// Function to edit a class
window.editClass = function(id) {
    clearErrors();
    fetch(`/website-management/classes-show/${id}`, {
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const classData = data.class;
            editMode = true;
            currentEditId = id;
            
            document.getElementById('classForm').action = `/website-management/classes-show/${id}`;
            document.getElementById('methodField').innerHTML = '<input type="hidden" name="_method" value="PUT">';
            document.getElementById('classModalLabel').innerHTML = '<span class="material-symbols-outlined" style="font-size: 20px; color: white !important;">school</span><span>Edit Class</span>';
            document.getElementById('submitBtnText').textContent = 'Update Class';

            document.getElementById('campus').value = classData.campus || '';
            document.getElementById('class').value = classData.class || '';
            document.getElementById('class_timing_from').value = classData.class_timing_from || '';
            document.getElementById('class_timing_to').value = classData.class_timing_to || '';
            document.getElementById('student_age_limit_from').value = classData.student_age_limit_from || '';
            document.getElementById('student_age_limit_to').value = classData.student_age_limit_to || '';
            document.getElementById('class_tuition_fee').value = classData.class_tuition_fee || '';
            document.getElementById('show_on_website_main_page').value = classData.show_on_website_main_page || 'No';

            new bootstrap.Modal(document.getElementById('classModal')).show();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('danger', 'Error loading class data. Please try again.');
    });
};

// Function to delete a class
window.deleteClass = function(id) {
    if (!confirm('Are you sure you want to delete this class?')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('_method', 'DELETE');
    
    fetch(`/website-management/classes-show/${id}`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json',
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('success', data.message);
            const row = document.querySelector(`tr[data-id="${id}"]`);
            if (row) {
                row.remove();
                
                // Check if table is empty
                const tbody = document.getElementById('classesTableBody');
                if (tbody.children.length === 0) {
                    tbody.innerHTML = `
                        <tr>
                            <td colspan="8" class="text-center text-muted py-5">
                                <span class="material-symbols-outlined" style="font-size: 48px; opacity: 0.3;">school</span>
                                <p class="mt-2 mb-0">No classes to show found.</p>
                            </td>
                        </tr>
                    `;
                }
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('danger', 'Error deleting class. Please try again.');
    });
};

function addTableRow(classData) {
    const tbody = document.getElementById('classesTableBody');
    
    // Remove empty message if exists
    const emptyRow = tbody.querySelector('tr td[colspan]');
    if (emptyRow) {
        emptyRow.closest('tr').remove();
    }
    
    const rowCount = tbody.querySelectorAll('tr').length;
    const newRow = document.createElement('tr');
    newRow.setAttribute('data-id', classData.id);
    newRow.innerHTML = `
        <td style="padding: 12px 15px; font-size: 14px;">${rowCount + 1}</td>
        <td style="padding: 12px 15px; font-size: 14px;">
            <span class="badge bg-secondary text-white" style="font-size: 11px;">${classData.campus || 'N/A'}</span>
        </td>
        <td style="padding: 12px 15px; font-size: 14px;">
            <strong class="text-primary">${classData.class || 'N/A'}</strong>
        </td>
        <td style="padding: 12px 15px; font-size: 14px;">
            ${(classData.class_timing_from || classData.class_timing_to) ? 
                `${classData.class_timing_from || 'N/A'} - ${classData.class_timing_to || 'N/A'}` : 
                'N/A'}
        </td>
        <td style="padding: 12px 15px; font-size: 14px;">
            ${(classData.student_age_limit_from || classData.student_age_limit_to) ? 
                `${classData.student_age_limit_from || 'N/A'} - ${classData.student_age_limit_to || 'N/A'}` : 
                'N/A'}
        </td>
        <td style="padding: 12px 15px; font-size: 14px;">
            Rs. ${parseFloat(classData.class_tuition_fee || 0).toFixed(2)}
        </td>
        <td style="padding: 12px 15px; font-size: 14px;">
            <span class="badge ${classData.show_on_website_main_page == 'Yes' ? 'bg-success' : 'bg-danger'} text-white" style="font-size: 11px;">
                ${classData.show_on_website_main_page}
            </span>
        </td>
        <td style="padding: 12px 15px; font-size: 14px; text-align: center;">
            <div class="d-inline-flex gap-1">
                <button type="button" class="btn btn-sm btn-primary px-2 py-1" onclick="editClass(${classData.id})" title="Edit">
                    <span class="material-symbols-outlined" style="font-size: 14px; color: white;">edit</span>
                </button>
                <button type="button" class="btn btn-sm btn-danger px-2 py-1" onclick="deleteClass(${classData.id})" title="Delete">
                    <span class="material-symbols-outlined" style="font-size: 14px; color: white;">delete</span>
                </button>
            </div>
        </td>
    `;
    tbody.insertBefore(newRow, tbody.firstChild);
}

function updateTableRow(classData) {
    const row = document.querySelector(`tr[data-id="${classData.id}"]`);
    if (row) {
        row.innerHTML = `
            <td style="padding: 12px 15px; font-size: 14px;">${Array.from(row.parentNode.children).indexOf(row) + 1}</td>
            <td style="padding: 12px 15px; font-size: 14px;">
                <span class="badge bg-secondary text-white" style="font-size: 11px;">${classData.campus || 'N/A'}</span>
            </td>
            <td style="padding: 12px 15px; font-size: 14px;">
                <strong class="text-primary">${classData.class || 'N/A'}</strong>
            </td>
            <td style="padding: 12px 15px; font-size: 14px;">
                ${(classData.class_timing_from || classData.class_timing_to) ? 
                    `${classData.class_timing_from || 'N/A'} - ${classData.class_timing_to || 'N/A'}` : 
                    'N/A'}
            </td>
            <td style="padding: 12px 15px; font-size: 14px;">
                ${(classData.student_age_limit_from || classData.student_age_limit_to) ? 
                    `${classData.student_age_limit_from || 'N/A'} - ${classData.student_age_limit_to || 'N/A'}` : 
                    'N/A'}
            </td>
            <td style="padding: 12px 15px; font-size: 14px;">
                Rs. ${parseFloat(classData.class_tuition_fee || 0).toFixed(2)}
            </td>
            <td style="padding: 12px 15px; font-size: 14px;">
                <span class="badge ${classData.show_on_website_main_page == 'Yes' ? 'bg-success' : 'bg-danger'} text-white" style="font-size: 11px;">
                    ${classData.show_on_website_main_page}
                </span>
            </td>
            <td style="padding: 12px 15px; font-size: 14px; text-align: center;">
                <div class="d-inline-flex gap-1">
                    <button type="button" class="btn btn-sm btn-primary px-2 py-1" onclick="editClass(${classData.id})" title="Edit">
                        <span class="material-symbols-outlined" style="font-size: 14px; color: white;">edit</span>
                    </button>
                    <button type="button" class="btn btn-sm btn-danger px-2 py-1" onclick="deleteClass(${classData.id})" title="Delete">
                        <span class="material-symbols-outlined" style="font-size: 14px; color: white;">delete</span>
                    </button>
                </div>
            </td>
        `;
    }
}

function showAlert(type, message) {
    const alertContainer = document.getElementById('alertContainer');
    const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
    const icon = type === 'success' ? 'check_circle' : 'error';
    
    alertContainer.innerHTML = `
        <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
            <div class="d-flex align-items-center gap-2">
                <span class="material-symbols-outlined" style="font-size: 20px;">${icon}</span>
                <span>${message}</span>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    `;
    
    // Auto hide after 3 seconds
    setTimeout(() => {
        const alert = alertContainer.querySelector('.alert');
        if (alert) {
            alert.remove();
        }
    }, 3000);
}

// Search functionality
window.performSearch = function() {
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
};

window.handleSearchKeyPress = function(event) {
    if (event.key === 'Enter') {
        event.preventDefault();
        performSearch();
    }
};

window.clearSearch = function() {
    const url = new URL(window.location.href);
    url.searchParams.delete('search');
    url.searchParams.set('page', '1');
    window.location.href = url.toString();
};

window.updateEntriesPerPage = function(value) {
    const url = new URL(window.location.href);
    url.searchParams.set('per_page', value);
    url.searchParams.set('page', '1');
    window.location.href = url.toString();
};
</script>
@endsection
