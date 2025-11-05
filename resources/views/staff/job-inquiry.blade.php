@extends('layouts.app')

@section('title', 'Job Inquiry/CV Bank')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-3 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0 fs-16 fw-semibold">Job Inquiry/CV Bank</h4>
                <button type="button" class="btn btn-sm py-2 px-3 d-inline-flex align-items-center gap-1 rounded-8 inquiry-add-btn" data-bs-toggle="modal" data-bs-target="#inquiryModal" onclick="resetForm()">
                    <span class="material-symbols-outlined" style="font-size: 16px;">add</span>
                    <span>Add New Inquiry</span>
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
                        <a href="{{ route('staff.job-inquiry.export', ['format' => 'excel']) }}{{ request()->has('search') ? '?search=' . request('search') : '' }}" class="btn btn-sm px-2 py-1 export-btn excel-btn">
                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">description</span>
                            <span>Excel</span>
                        </a>
                        <a href="{{ route('staff.job-inquiry.export', ['format' => 'csv']) }}{{ request()->has('search') ? '?search=' . request('search') : '' }}" class="btn btn-sm px-2 py-1 export-btn csv-btn">
                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">file_present</span>
                            <span>CSV</span>
                        </a>
                        <a href="{{ route('staff.job-inquiry.export', ['format' => 'pdf']) }}{{ request()->has('search') ? '?search=' . request('search') : '' }}" class="btn btn-sm px-2 py-1 export-btn pdf-btn" target="_blank">
                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">picture_as_pdf</span>
                            <span>PDF</span>
                        </a>
                        <button type="button" class="btn btn-sm px-2 py-1 export-btn print-btn" onclick="printTable()">
                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">print</span>
                            <span>Print</span>
                        </button>
                        <form action="{{ route('staff.job-inquiry.delete-all') }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete ALL job inquiries? This action cannot be undone!');">
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
                            <input type="text" id="searchInput" class="form-control border-start-0 border-end-0" placeholder="Search inquiries..." value="{{ request('search') }}" onkeypress="handleSearchKeyPress(event)" style="padding: 4px 8px; font-size: 12px;">
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
                    <span>Job Inquiries List</span>
                </h5>
            </div>

            <!-- Search Results Info -->
            @if(request('search'))
                <div class="search-results-info">
                    <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle; color: #003471;">search</span>
                    <strong>Search Results:</strong> Showing results for "<strong>{{ request('search') }}</strong>" 
                    ({{ $inquiries->total() }} {{ Str::plural('result', $inquiries->total()) }} found)
                    <a href="{{ route('staff.job-inquiry') }}" class="text-decoration-none ms-2" style="color: #003471;">
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
                                <th>Name</th>
                                <th>Father/Husband</th>
                                <th>Campus</th>
                                <th>Gender</th>
                                <th>Phone</th>
                                <th>Qualification</th>
                                <th>Applied For</th>
                                <th>Email</th>
                                <th>CV/Resume</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($inquiries as $inquiry)
                                <tr>
                                    <td>{{ $loop->iteration + (($inquiries->currentPage() - 1) * $inquiries->perPage()) }}</td>
                                    <td>
                                        <strong class="text-primary">{{ $inquiry->name }}</strong>
                                    </td>
                                    <td>
                                        <span class="text-muted">{{ $inquiry->father_husband_name ?? 'N/A' }}</span>
                                    </td>
                                    <td>
                                        @if($inquiry->campus)
                                            <span class="badge bg-secondary text-white">{{ $inquiry->campus }}</span>
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($inquiry->gender)
                                            <span class="badge {{ $inquiry->gender == 'Male' ? 'bg-primary' : ($inquiry->gender == 'Female' ? 'bg-danger' : 'bg-secondary') }} text-white">
                                                {{ $inquiry->gender }}
                                            </span>
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($inquiry->phone)
                                            <span class="badge bg-light text-dark">
                                                <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">phone</span>
                                                {{ $inquiry->phone }}
                                            </span>
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($inquiry->qualification)
                                            <span class="text-muted">{{ $inquiry->qualification }}</span>
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($inquiry->applied_for_designation)
                                            <span class="badge bg-info text-white">{{ $inquiry->applied_for_designation }}</span>
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($inquiry->email)
                                            <span class="text-muted">
                                                <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">email</span>
                                                {{ Str::limit($inquiry->email, 20) }}
                                            </span>
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($inquiry->cv_resume)
                                            <a href="{{ asset('storage/' . $inquiry->cv_resume) }}" target="_blank" class="btn btn-sm btn-info px-2 py-0" title="View CV">
                                                <span class="material-symbols-outlined" style="font-size: 14px; color: white;">description</span>
                                            </a>
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <div class="d-inline-flex gap-1">
                                            <button type="button" class="btn btn-sm btn-primary px-2 py-0" onclick="editInquiry({{ $inquiry->id }})" title="Edit">
                                                <span class="material-symbols-outlined" style="font-size: 14px; color: white;">edit</span>
                                            </button>
                                            <form action="{{ route('staff.job-inquiry.destroy', $inquiry) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this job inquiry?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger px-2 py-0" title="Delete">
                                                    <span class="material-symbols-outlined" style="font-size: 14px; color: white;">delete</span>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="11" class="text-center text-muted py-5">
                                        <span class="material-symbols-outlined" style="font-size: 48px; opacity: 0.3;">inbox</span>
                                        <p class="mt-2 mb-0">No job inquiries found.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if($inquiries->hasPages())
                <div class="mt-3">
                    {{ $inquiries->links() }}
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Job Inquiry Modal -->
<div class="modal fade" id="inquiryModal" tabindex="-1" aria-labelledby="inquiryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
            <div class="modal-header text-white p-2" style="background: linear-gradient(135deg, #003471 0%, #004a9f 100%); border: none;">
                <h5 class="modal-title fw-semibold mb-0 d-flex align-items-center gap-2" id="inquiryModalLabel" style="font-size: 14px;">
                    <span class="material-symbols-outlined" style="font-size: 18px;">work</span>
                    <span>Add New Inquiry</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" style="opacity: 0.8;"></button>
            </div>
            <form id="inquiryForm" method="POST" action="{{ route('staff.job-inquiry.store') }}" enctype="multipart/form-data">
                @csrf
                <div id="methodField"></div>
                <div class="modal-body p-3" style="max-height: 70vh; overflow-y: auto;">
                    <div class="row g-2">
                        <!-- Name -->
                        <div class="col-md-6">
                            <label class="form-label mb-1 fw-semibold" style="color: #003471; font-size: 11px;">Name <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm inquiry-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 14px;">person</span>
                                </span>
                                <input type="text" class="form-control inquiry-input" name="name" id="name" placeholder="Enter name" required style="font-size: 12px;">
                            </div>
                        </div>

                        <!-- Father/Husband Name -->
                        <div class="col-md-6">
                            <label class="form-label mb-1 fw-semibold" style="color: #003471; font-size: 11px;">Father/Husband Name</label>
                            <div class="input-group input-group-sm inquiry-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 14px;">family_restroom</span>
                                </span>
                                <input type="text" class="form-control inquiry-input" name="father_husband_name" id="father_husband_name" placeholder="Enter father/husband name" style="font-size: 12px;">
                            </div>
                        </div>

                        <!-- Campus -->
                        <div class="col-md-6">
                            <label class="form-label mb-1 fw-semibold" style="color: #003471; font-size: 11px;">Campus</label>
                            <div class="input-group input-group-sm inquiry-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 14px;">business</span>
                                </span>
                                <input type="text" class="form-control inquiry-input" name="campus" id="campus" placeholder="Enter campus" style="font-size: 12px;">
                            </div>
                        </div>

                        <!-- Gender -->
                        <div class="col-md-6">
                            <label class="form-label mb-1 fw-semibold" style="color: #003471; font-size: 11px;">Gender</label>
                            <div class="input-group input-group-sm inquiry-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 14px;">people</span>
                                </span>
                                <select class="form-control inquiry-input" name="gender" id="gender" style="font-size: 12px;">
                                    <option value="">Select Gender</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                        </div>

                        <!-- Phone -->
                        <div class="col-md-6">
                            <label class="form-label mb-1 fw-semibold" style="color: #003471; font-size: 11px;">Phone</label>
                            <div class="input-group input-group-sm inquiry-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 14px;">phone</span>
                                </span>
                                <input type="text" class="form-control inquiry-input" name="phone" id="phone" placeholder="Enter phone number" style="font-size: 12px;">
                            </div>
                        </div>

                        <!-- Qualification -->
                        <div class="col-md-6">
                            <label class="form-label mb-1 fw-semibold" style="color: #003471; font-size: 11px;">Qualification</label>
                            <div class="input-group input-group-sm inquiry-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 14px;">school</span>
                                </span>
                                <input type="text" class="form-control inquiry-input" name="qualification" id="qualification" placeholder="Enter qualification" style="font-size: 12px;">
                            </div>
                        </div>

                        <!-- Birthday -->
                        <div class="col-md-6">
                            <label class="form-label mb-1 fw-semibold" style="color: #003471; font-size: 11px;">Birthday</label>
                            <div class="input-group input-group-sm inquiry-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 14px;">cake</span>
                                </span>
                                <input type="date" class="form-control inquiry-input" name="birthday" id="birthday" style="font-size: 12px;">
                            </div>
                        </div>

                        <!-- Marital Status -->
                        <div class="col-md-6">
                            <label class="form-label mb-1 fw-semibold" style="color: #003471; font-size: 11px;">Marital Status</label>
                            <div class="input-group input-group-sm inquiry-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 14px;">favorite</span>
                                </span>
                                <select class="form-control inquiry-input" name="marital_status" id="marital_status" style="font-size: 12px;">
                                    <option value="">Select Status</option>
                                    <option value="Single">Single</option>
                                    <option value="Married">Married</option>
                                    <option value="Divorced">Divorced</option>
                                    <option value="Widowed">Widowed</option>
                                </select>
                            </div>
                        </div>

                        <!-- Applied For Designation -->
                        <div class="col-md-6">
                            <label class="form-label mb-1 fw-semibold" style="color: #003471; font-size: 11px;">Applied For Designation</label>
                            <div class="input-group input-group-sm inquiry-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 14px;">work</span>
                                </span>
                                <input type="text" class="form-control inquiry-input" name="applied_for_designation" id="applied_for_designation" placeholder="Enter designation" style="font-size: 12px;">
                            </div>
                        </div>

                        <!-- Salary Type -->
                        <div class="col-md-6">
                            <label class="form-label mb-1 fw-semibold" style="color: #003471; font-size: 11px;">Salary Type</label>
                            <div class="input-group input-group-sm inquiry-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 14px;">payments</span>
                                </span>
                                <input type="text" class="form-control inquiry-input" name="salary_type" id="salary_type" placeholder="Enter salary type" style="font-size: 12px;">
                            </div>
                        </div>

                        <!-- Salary Demand -->
                        <div class="col-md-6">
                            <label class="form-label mb-1 fw-semibold" style="color: #003471; font-size: 11px;">Salary Demand</label>
                            <div class="input-group input-group-sm inquiry-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 14px;">attach_money</span>
                                </span>
                                <input type="number" step="0.01" class="form-control inquiry-input" name="salary_demand" id="salary_demand" placeholder="Enter salary demand" style="font-size: 12px;">
                            </div>
                        </div>

                        <!-- Email -->
                        <div class="col-md-6">
                            <label class="form-label mb-1 fw-semibold" style="color: #003471; font-size: 11px;">Email</label>
                            <div class="input-group input-group-sm inquiry-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 14px;">email</span>
                                </span>
                                <input type="email" class="form-control inquiry-input" name="email" id="email" placeholder="Enter email" style="font-size: 12px;">
                            </div>
                        </div>

                        <!-- Home Address -->
                        <div class="col-12">
                            <label class="form-label mb-1 fw-semibold" style="color: #003471; font-size: 11px;">Home Address</label>
                            <div class="input-group input-group-sm inquiry-input-group">
                                <span class="input-group-text align-items-start" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471; padding-top: 8px;">
                                    <span class="material-symbols-outlined" style="font-size: 14px;">location_on</span>
                                </span>
                                <textarea class="form-control inquiry-input" name="home_address" id="home_address" rows="2" placeholder="Enter home address" style="resize: vertical; font-size: 12px;"></textarea>
                            </div>
                        </div>

                        <!-- Upload CV/Resume -->
                        <div class="col-md-6">
                            <label class="form-label mb-1 fw-semibold" style="color: #003471; font-size: 11px;">Upload CV/Resume</label>
                            <div class="input-group input-group-sm inquiry-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 14px;">description</span>
                                </span>
                                <input type="file" class="form-control inquiry-input" name="cv_resume" id="cv_resume" accept=".pdf,.doc,.docx" style="font-size: 12px;">
                            </div>
                            <small class="text-muted" style="font-size: 10px;">Max size: 5MB (PDF, DOC, DOCX)</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer p-2" style="background-color: #f8f9fa; border-top: 1px solid #e9ecef;">
                    <button type="button" class="btn btn-sm py-1 px-3 rounded-8" data-bs-dismiss="modal" style="background-color: #6c757d; color: white; border: none; transition: all 0.3s ease; font-size: 12px;">
                        <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">close</span>
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-sm py-1 px-3 rounded-8 inquiry-submit-btn" style="font-size: 12px;">
                        <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">add</span>
                        Add Inquiry
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    /* Inquiry Form Styling */
    #inquiryModal .inquiry-input-group {
        height: 32px;
        border-radius: 8px;
        overflow: hidden;
        transition: all 0.3s ease;
        border: 1px solid #dee2e6;
    }
    
    #inquiryModal .inquiry-input-group:focus-within {
        box-shadow: 0 0 0 3px rgba(0, 52, 113, 0.15);
        border-color: #003471;
    }
    
    #inquiryModal .inquiry-input {
        height: 32px;
        font-size: 12px;
        padding: 0.4rem 0.65rem;
        border: none;
        border-left: 1px solid #e0e7ff;
        border-radius: 0 8px 8px 0;
        transition: all 0.3s ease;
    }
    
    #inquiryModal .inquiry-input:focus {
        border-left-color: #003471;
        box-shadow: none;
        outline: none;
    }
    
    #inquiryModal .input-group-text {
        height: 32px;
        padding: 0 0.65rem;
        display: flex;
        align-items: center;
        border: none;
        border-right: 1px solid #e0e7ff;
        border-radius: 8px 0 0 8px;
        transition: all 0.3s ease;
    }
    
    #inquiryModal .inquiry-input-group:focus-within .input-group-text {
        background-color: #003471 !important;
        color: white !important;
        border-right-color: #003471;
    }
    
    #inquiryModal textarea.inquiry-input {
        min-height: 50px;
        border-left: 1px solid #e0e7ff;
        padding-top: 0.4rem;
        font-size: 12px;
    }
    
    #inquiryModal .inquiry-submit-btn {
        background: linear-gradient(135deg, #003471 0%, #004a9f 100%);
        color: white;
        border: none;
        font-weight: 500;
        transition: all 0.3s ease;
        box-shadow: 0 2px 8px rgba(0, 52, 113, 0.25);
    }
    
    #inquiryModal .inquiry-submit-btn:hover {
        background: linear-gradient(135deg, #004a9f 0%, #003471 100%);
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0, 52, 113, 0.35);
        color: white;
    }
    
    .inquiry-add-btn {
        background: linear-gradient(135deg, #003471 0%, #004a9f 100%);
        color: white;
        border: none;
        font-weight: 500;
        transition: all 0.3s ease;
        box-shadow: 0 2px 6px rgba(0, 52, 113, 0.2);
    }
    
    .inquiry-add-btn:hover {
        background: linear-gradient(135deg, #004a9f 0%, #003471 100%);
        transform: translateY(-1px);
        box-shadow: 0 4px 10px rgba(0, 52, 113, 0.3);
        color: white;
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

    /* Search Input Group */
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

    /* Table Compact Styling */
    .default-table-area table {
        margin-bottom: 0;
        border: 1px solid #dee2e6;
    }
    
    .default-table-area table thead th {
        padding: 5px 10px;
        font-size: 12px;
        font-weight: 600;
        height: 32px;
        border: 1px solid #dee2e6;
        background-color: #f8f9fa;
    }
    
    .default-table-area table tbody td {
        padding: 5px 10px;
        font-size: 12px;
        border: 1px solid #dee2e6;
    }
    
    .default-table-area .badge {
        font-size: 11px;
        padding: 3px 6px;
    }
    
    .default-table-area .btn-sm .material-symbols-outlined {
        font-size: 14px !important;
        color: white !important;
    }
    
    #inquiryModal .form-control,
    #inquiryModal .form-select,
    #inquiryModal select {
        font-size: 12px !important;
    }
</style>

<script>
function resetForm() {
    document.getElementById('inquiryForm').action = '{{ route('staff.job-inquiry.store') }}';
    document.getElementById('inquiryForm').reset();
    document.getElementById('methodField').innerHTML = '';
    document.getElementById('inquiryModalLabel').textContent = 'Add New Inquiry';
    document.getElementById('cv_resume').value = '';
}

function editInquiry(id) {
    fetch(`{{ url('/staff/job-inquiry') }}/${id}`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('inquiryForm').action = '{{ route('staff.job-inquiry.update', ':id') }}'.replace(':id', id);
            document.getElementById('methodField').innerHTML = '@method('PUT')';
            document.getElementById('inquiryModalLabel').textContent = 'Edit Inquiry';
            
            document.getElementById('name').value = data.name || '';
            document.getElementById('father_husband_name').value = data.father_husband_name || '';
            document.getElementById('campus').value = data.campus || '';
            document.getElementById('gender').value = data.gender || '';
            document.getElementById('phone').value = data.phone || '';
            document.getElementById('qualification').value = data.qualification || '';
            document.getElementById('birthday').value = data.birthday || '';
            document.getElementById('marital_status').value = data.marital_status || '';
            document.getElementById('applied_for_designation').value = data.applied_for_designation || '';
            document.getElementById('salary_type').value = data.salary_type || '';
            document.getElementById('salary_demand').value = data.salary_demand || '';
            document.getElementById('email').value = data.email || '';
            document.getElementById('home_address').value = data.home_address || '';
            document.getElementById('cv_resume').value = '';
            
            new bootstrap.Modal(document.getElementById('inquiryModal')).show();
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error loading inquiry data');
        });
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
    
    url.searchParams.set('page', '1');
    window.location.href = url.toString();
}

function handleSearchKeyPress(event) {
    if (event.key === 'Enter') {
        event.preventDefault();
        performSearch();
    }
}

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

function printTable() {
    const printContents = document.querySelector('.default-table-area').innerHTML;
    const originalContents = document.body.innerHTML;
    
    document.body.innerHTML = `
        <div style="padding: 20px;">
            <h3 style="text-align: center; margin-bottom: 20px; color: #003471;">Job Inquiries List</h3>
            ${printContents}
        </div>
    `;
    
    window.print();
    document.body.innerHTML = originalContents;
    window.location.reload();
}
</script>
@endsection
