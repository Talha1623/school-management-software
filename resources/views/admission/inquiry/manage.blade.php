@extends('layouts.app')

@section('title', 'Manage Inquiry')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-3 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0 fs-16 fw-semibold">Manage Inquiry</h4>
                <button type="button" class="btn btn-sm py-2 px-3 d-inline-flex align-items-center gap-1 rounded-8 inquiry-add-btn" data-bs-toggle="modal" data-bs-target="#inquiryModal" onclick="resetForm()">
                    <span class="material-symbols-outlined" style="font-size: 16px;">add</span>
                    <span>Add New</span>
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
                    <strong>Error:</strong> {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <strong>Validation Errors:</strong>
                    <ul class="mb-0 mt-2">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
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
                        <a href="{{ route('admission.inquiry.export', ['format' => 'excel']) }}{{ request()->has('search') ? '?search=' . request('search') : '' }}" class="btn btn-sm px-2 py-1 export-btn excel-btn">
                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">description</span>
                            <span>Excel</span>
                        </a>
                        <a href="{{ route('admission.inquiry.export', ['format' => 'csv']) }}{{ request()->has('search') ? '?search=' . request('search') : '' }}" class="btn btn-sm px-2 py-1 export-btn csv-btn">
                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">file_present</span>
                            <span>CSV</span>
                        </a>
                        <a href="{{ route('admission.inquiry.export', ['format' => 'pdf']) }}{{ request()->has('search') ? '?search=' . request('search') : '' }}" class="btn btn-sm px-2 py-1 export-btn pdf-btn" target="_blank">
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
                            <input type="text" id="searchInput" class="form-control border-start-0 border-end-0" placeholder="Search by name, parent, phone..." value="{{ request('search') }}" onkeypress="handleSearchKeyPress(event)" oninput="handleSearchInput(event)" style="padding: 4px 8px; font-size: 13px;">
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
                    <span>Inquiries List</span>
                </h5>
            </div>

            <!-- Search Results Info -->
            @if(request('search'))
                <div class="search-results-info">
                    <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle; color: #003471;">search</span>
                    <strong>Search Results:</strong> Showing results for "<strong>{{ request('search') }}</strong>" 
                    ({{ $inquiries->total() }} {{ Str::plural('result', $inquiries->total()) }} found)
                    <a href="{{ route('admission.inquiry.manage') }}" class="text-decoration-none ms-2" style="color: #003471;">
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
                                <th>Parent</th>
                                <th>Phone</th>
                                <th>Gender</th>
                                <th>Birthday</th>
                                <th>Full Address</th>
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
                                    <td>{{ $inquiry->parent }}</td>
                                    <td>
                                        <span class="badge bg-light text-dark">
                                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">phone</span>
                                            {{ $inquiry->phone }}
                                        </span>
                                    </td>
                                    <td>
                                        @php
                                            $genderClass = match($inquiry->gender) {
                                                'male' => 'bg-info',
                                                'female' => 'bg-danger',
                                                default => 'bg-secondary'
                                            };
                                        @endphp
                                        <span class="badge {{ $genderClass }} text-white text-capitalize">
                                            {{ $inquiry->gender }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="text-muted">
                                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">calendar_today</span>
                                            {{ $inquiry->birthday ? $inquiry->birthday->format('d M Y') : 'N/A' }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="text-muted" title="{{ $inquiry->full_address }}">
                                            {{ Str::limit($inquiry->full_address, 40) }}
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <div class="d-inline-flex gap-1">
                                            <button type="button" class="btn btn-sm btn-success px-2 py-0" onclick="openAdmitModal({{ $inquiry->id }})" title="Admit Student">
                                                <span class="material-symbols-outlined" style="font-size: 14px; color: white;">person_add</span>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-primary px-2 py-0" onclick="editInquiry({{ $inquiry->id }}, '{{ addslashes($inquiry->name) }}', '{{ addslashes($inquiry->parent) }}', '{{ $inquiry->phone }}', '{{ $inquiry->gender }}', '{{ $inquiry->birthday ? $inquiry->birthday->format('Y-m-d') : '' }}', '{{ addslashes($inquiry->full_address) }}')" title="Edit">
                                                <span class="material-symbols-outlined" style="font-size: 14px; color: white;">edit</span>
                                            </button>
                                            <form action="{{ route('admission.inquiry.destroy', $inquiry) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this inquiry?');">
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
                                    <td colspan="8" class="text-center text-muted py-5">
                                        <span class="material-symbols-outlined" style="font-size: 48px; opacity: 0.3;">inbox</span>
                                        <p class="mt-2 mb-0">No inquiries found.</p>
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

<!-- Inquiry Modal -->
<div class="modal fade" id="inquiryModal" tabindex="-1" aria-labelledby="inquiryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
            <div class="modal-header text-white p-3" style="background: linear-gradient(135deg, #003471 0%, #004a9f 100%); border: none;">
                <h5 class="modal-title fs-15 fw-semibold mb-0 d-flex align-items-center gap-2" id="inquiryModalLabel" style="color: white;">
                    <span class="material-symbols-outlined" style="font-size: 20px; color: white;">person_add</span>
                    <span style="color: white;">Add New Inquiry</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" style="opacity: 0.8;"></button>
            </div>
            <form id="inquiryForm" method="POST" action="{{ route('admission.inquiry.store') }}">
                @csrf
                <div id="methodField"></div>
                <div class="modal-body p-3">
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Name <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm inquiry-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">person</span>
                                </span>
                                <input type="text" class="form-control inquiry-input" name="name" id="name" placeholder="Enter name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Parent <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm inquiry-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">family_restroom</span>
                                </span>
                                <input type="text" class="form-control inquiry-input" name="parent" id="parent" placeholder="Enter parent name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Phone <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm inquiry-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">phone</span>
                                </span>
                                <input type="text" class="form-control inquiry-input" name="phone" id="phone" placeholder="Enter phone number" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Gender <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm inquiry-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">wc</span>
                                </span>
                                <select class="form-select inquiry-input" name="gender" id="gender" required>
                                    <option value="">Select Gender</option>
                                    <option value="male">Male</option>
                                    <option value="female">Female</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Birthday <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm inquiry-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">calendar_today</span>
                                </span>
                                <input type="date" class="form-control inquiry-input" name="birthday" id="birthday" required>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Full Address <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm inquiry-input-group">
                                <span class="input-group-text align-items-start" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471; padding-top: 8px;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">location_on</span>
                                </span>
                                <textarea class="form-control inquiry-input" name="full_address" id="full_address" rows="2" placeholder="Enter full address" style="resize: vertical;" required></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer p-3" style="background-color: #f8f9fa; border-top: 1px solid #e9ecef;">
                    <button type="button" class="btn btn-sm py-2 px-4 rounded-8" data-bs-dismiss="modal" style="background-color: #6c757d; color: white; border: none; transition: all 0.3s ease;">
                        <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">close</span>
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-sm py-2 px-4 rounded-8 inquiry-submit-btn">
                        <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">save</span>
                        Save Inquiry
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Admit Student Modal -->
<div class="modal fade" id="admitStudentModal" tabindex="-1" aria-labelledby="admitStudentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
            <div class="modal-header text-white p-3" style="background: linear-gradient(135deg, #003471 0%, #004a9f 100%); border: none;">
                <h5 class="modal-title fs-15 fw-semibold mb-0 d-flex align-items-center gap-2" id="admitStudentModalLabel" style="color: white;">
                    <span class="material-symbols-outlined" style="font-size: 20px; color: white;">person_add</span>
                    <span style="color: white;">Admit Student</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" style="opacity: 0.8;"></button>
            </div>
            <form id="admitStudentForm" method="POST" action="{{ route('admission.inquiry.admit') }}" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="inquiry_id" id="admit_inquiry_id">
                <input type="hidden" name="captured_photo" id="admit_captured_photo_input">
                <div class="modal-body p-4" style="max-height: 80vh; overflow-y: auto;">
                    <!-- First Row: Student Information & Parent Information -->
                    <div class="row mb-2">
                        <!-- Student Information -->
                        <div class="col-md-6">
                            <div class="card border-0 shadow-sm rounded-3 p-2 mb-2" style="background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);">
                                <div class="d-flex align-items-center mb-2" style="background-color: #003471; padding: 8px 12px; margin: -8px -8px 8px -8px; border-radius: 8px 8px 0 0;">
                                    <span class="material-symbols-outlined me-2" style="font-size: 18px; color: #ffffff;">person</span>
                                    <h5 class="mb-0 fw-semibold fs-15" style="color: #ffffff;">Student Information</h5>
                                </div>
                                
                                <div class="mb-2">
                                    <label for="admit_student_name" class="form-label mb-0 fs-13 fw-medium">
                                        Student Name <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-light border-end-0 py-1" style="height: 32px;"><span class="material-symbols-outlined" style="font-size: 16px;">badge</span></span>
                                        <input type="text" class="form-control border-start-0 py-1" id="admit_student_name" name="student_name" required placeholder="Student Name" style="height: 32px; font-size: 13px;">
                                    </div>
                                </div>
                                
                                <div class="mb-2">
                                    <label for="admit_surname_caste" class="form-label mb-0 fs-13 fw-medium">Surname/Caste</label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-light border-end-0 py-1" style="height: 32px;"><span class="material-symbols-outlined" style="font-size: 16px;">family_history</span></span>
                                        <input type="text" class="form-control border-start-0 py-1" id="admit_surname_caste" name="surname_caste" placeholder="Surname/Caste" style="height: 32px; font-size: 13px;">
                                    </div>
                                </div>
                                
                                <div class="mb-2">
                                    <label for="admit_gender" class="form-label mb-0 fs-13 fw-medium">
                                        Gender <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-light border-end-0 py-1" style="height: 32px;"><span class="material-symbols-outlined" style="font-size: 16px;">wc</span></span>
                                        <select class="form-select border-start-0 py-1" id="admit_gender" name="gender" required style="height: 32px; font-size: 13px;">
                                            <option value="">Select Gender</option>
                                            <option value="male">Male</option>
                                            <option value="female">Female</option>
                                            <option value="other">Other</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="mb-2">
                                    <label for="admit_date_of_birth" class="form-label mb-0 fs-13 fw-medium">
                                        Date Of Birth <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-light border-end-0 py-1" style="height: 32px;"><span class="material-symbols-outlined" style="font-size: 16px;">calendar_today</span></span>
                                        <input type="date" class="form-control border-start-0 py-1" id="admit_date_of_birth" name="date_of_birth" required style="height: 32px; font-size: 13px;">
                                    </div>
                                </div>
                                
                                <div class="mb-2">
                                    <label for="admit_place_of_birth" class="form-label mb-0 fs-13 fw-medium">Place Of Birth</label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-light border-end-0 py-1" style="height: 32px;"><span class="material-symbols-outlined" style="font-size: 16px;">location_on</span></span>
                                        <input type="text" class="form-control border-start-0 py-1" id="admit_place_of_birth" name="place_of_birth" placeholder="Place Of Birth" style="height: 32px; font-size: 13px;">
                                    </div>
                                </div>
                                
                                <div class="mb-2">
                                    <label for="admit_photo" class="form-label mb-0 fs-13 fw-medium">Photo</label>
                                    <div class="input-group input-group-sm mb-1">
                                        <span class="input-group-text bg-light border-end-0 py-1" style="height: 32px;"><span class="material-symbols-outlined" style="font-size: 16px;">image</span></span>
                                        <input type="file" class="form-control border-start-0 py-1" id="admit_photo" name="photo" accept="image/*" capture="camera" style="height: 32px; font-size: 13px;">
                                    </div>
                                    <button type="button" class="btn btn-outline-primary btn-sm w-100 py-1" id="admit_live-capture-btn" onclick="startAdmitLiveCapture()" style="font-size: 12px;">
                                        <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">camera</span>
                                        Live Capture
                                    </button>
                                    <video id="admit_video" autoplay style="display: none; width: 100%; max-height: 150px; margin-top: 8px; border-radius: 8px;"></video>
                                    <canvas id="admit_canvas" style="display: none;"></canvas>
                                    <div id="admit_captured-image-container" style="display: none; margin-top: 8px; text-align: center;">
                                        <img id="admit_captured-image" style="max-width: 100%; max-height: 150px; border: 2px solid #003471; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                                        <button type="button" class="btn btn-danger btn-sm mt-1 py-1" onclick="retakeAdmitPhoto()" style="font-size: 12px;">
                                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">refresh</span>
                                            Retake
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Parent Information -->
                        <div class="col-md-6">
                            <div class="card border-0 shadow-sm rounded-3 p-2 mb-2" style="background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);">
                                <div class="d-flex align-items-center mb-2" style="background-color: #003471; padding: 8px 12px; margin: -8px -8px 8px -8px; border-radius: 8px 8px 0 0;">
                                    <span class="material-symbols-outlined me-2" style="font-size: 18px; color: #ffffff;">family_restroom</span>
                                    <h5 class="mb-0 fw-semibold fs-15" style="color: #ffffff;">Parent Information</h5>
                                </div>
                                
                                <div class="mb-2">
                                    <label for="admit_father_id_card" class="form-label mb-0 fs-13 fw-medium">Father ID Card</label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-light border-end-0 py-1" style="height: 32px;"><span class="material-symbols-outlined" style="font-size: 16px;">credit_card</span></span>
                                        <input type="text" class="form-control border-start-0 py-1" id="admit_father_id_card" name="father_id_card" placeholder="Father ID Card" style="height: 32px; font-size: 13px;">
                                    </div>
                                </div>
                                
                                <div class="mb-2">
                                    <label for="admit_father_name" class="form-label mb-0 fs-13 fw-medium">
                                        Father Name <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-light border-end-0 py-1" style="height: 32px;"><span class="material-symbols-outlined" style="font-size: 16px;">person</span></span>
                                        <input type="text" class="form-control border-start-0 py-1" id="admit_father_name" name="father_name" required placeholder="Father Name" style="height: 32px; font-size: 13px;">
                                    </div>
                                </div>
                                
                                <div class="mb-2">
                                    <label for="admit_father_email" class="form-label mb-0 fs-13 fw-medium">Father Email</label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-light border-end-0 py-1" style="height: 32px;"><span class="material-symbols-outlined" style="font-size: 16px;">email</span></span>
                                        <input type="email" class="form-control border-start-0 py-1" id="admit_father_email" name="father_email" placeholder="Father Email" style="height: 32px; font-size: 13px;">
                                    </div>
                                </div>
                                
                                <div class="mb-2">
                                    <label for="admit_create_parent_account" class="form-label mb-0 fs-13 fw-medium">Create Parent Account</label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-light border-end-0 py-1" style="height: 32px;"><span class="material-symbols-outlined" style="font-size: 16px;">account_circle</span></span>
                                        <select class="form-select border-start-0 py-1" id="admit_create_parent_account" name="create_parent_account" style="height: 32px; font-size: 13px;" onchange="toggleAdmitParentPasswordField()">
                                            <option value="">Select</option>
                                            <option value="1">Yes</option>
                                            <option value="0">No</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="mb-2" id="admit_parent_password_field" style="display: none;">
                                    <label for="admit_parent_password" class="form-label mb-0 fs-13 fw-medium">
                                        Parent Password <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-light border-end-0 py-1" style="height: 32px;"><span class="material-symbols-outlined" style="font-size: 16px;">lock</span></span>
                                        <input type="text" class="form-control border-start-0 py-1" id="admit_parent_password" name="parent_password" value="parent" placeholder="Parent Password" style="height: 32px; font-size: 13px;">
                                    </div>
                                    <small class="text-muted fs-11 mt-0 d-block">
                                        <span class="material-symbols-outlined" style="font-size: 12px; vertical-align: middle;">info</span>
                                        Default password: parent (can be changed)
                                    </small>
                                </div>
                                
                                <div class="mb-2">
                                    <label for="admit_father_phone" class="form-label mb-0 fs-13 fw-medium">Father Phone</label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-light border-end-0 py-1" style="height: 32px;"><span class="material-symbols-outlined" style="font-size: 16px;">phone</span></span>
                                        <input type="tel" class="form-control border-start-0 py-1" id="admit_father_phone" name="father_phone" placeholder="Father Phone" style="height: 32px; font-size: 13px;">
                                    </div>
                                </div>
                                
                                <div class="mb-2">
                                    <label for="admit_mother_phone" class="form-label mb-0 fs-13 fw-medium">Mother Phone</label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-light border-end-0 py-1" style="height: 32px;"><span class="material-symbols-outlined" style="font-size: 16px;">phone</span></span>
                                        <input type="tel" class="form-control border-start-0 py-1" id="admit_mother_phone" name="mother_phone" placeholder="Mother Phone" style="height: 32px; font-size: 13px;">
                                    </div>
                                </div>
                                
                                <div class="mb-2">
                                    <label for="admit_whatsapp_number" class="form-label mb-0 fs-13 fw-medium">WhatsApp Number</label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-light border-end-0 py-1" style="height: 32px;"><span class="material-symbols-outlined" style="font-size: 16px;">chat</span></span>
                                        <input type="tel" class="form-control border-start-0 py-1" id="admit_whatsapp_number" name="whatsapp_number" placeholder="WhatsApp Number" style="height: 32px; font-size: 13px;">
                                    </div>
                                </div>
                                
                                <div class="mb-2">
                                    <label for="admit_religion" class="form-label mb-0 fs-13 fw-medium">Religion</label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-light border-end-0 py-1" style="height: 32px;"><span class="material-symbols-outlined" style="font-size: 16px;">church</span></span>
                                        <input type="text" class="form-control border-start-0 py-1" id="admit_religion" name="religion" placeholder="Religion" style="height: 32px; font-size: 13px;">
                                    </div>
                                </div>
                                
                                <div class="mb-2">
                                    <label for="admit_home_address" class="form-label mb-0 fs-13 fw-medium">Home Address</label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-light border-end-0 align-items-start py-1" style="height: auto;"><span class="material-symbols-outlined" style="font-size: 16px;">home</span></span>
                                        <textarea class="form-control border-start-0 py-1" id="admit_home_address" name="home_address" rows="2" placeholder="Home Address" style="font-size: 13px; min-height: 50px;"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Second Row: Other Information & Academic Information -->
                    <div class="row mb-2">
                        <!-- Other Information -->
                        <div class="col-md-6">
                            <div class="card border-0 shadow-sm rounded-3 p-2 mb-2" style="background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);">
                                <div class="d-flex align-items-center mb-2" style="background-color: #003471; padding: 8px 12px; margin: -8px -8px 8px -8px; border-radius: 8px 8px 0 0;">
                                    <span class="material-symbols-outlined me-2" style="font-size: 18px; color: #ffffff;">info</span>
                                    <h5 class="mb-0 fw-semibold fs-15" style="color: #ffffff;">Other Information</h5>
                                </div>
                                
                                <div class="mb-2">
                                    <label for="admit_b_form_number" class="form-label mb-0 fs-13 fw-medium">Student Password</label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-light border-end-0 py-1" style="height: 32px;"><span class="material-symbols-outlined" style="font-size: 16px;">description</span></span>
                                        <input type="text" class="form-control border-start-0 py-1" id="admit_b_form_number" name="b_form_number" value="student" placeholder="Student Password" style="height: 32px; font-size: 13px;">
                                    </div>
                                </div>
                                
                                <div class="mb-2">
                                    <label for="admit_monthly_fee" class="form-label mb-0 fs-13 fw-medium">Monthly Fee</label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-light border-end-0 py-1" style="height: 32px;"><span class="material-symbols-outlined" style="font-size: 16px;">payments</span></span>
                                        <input type="number" class="form-control border-start-0 py-1" id="admit_monthly_fee" name="monthly_fee" step="0.01" placeholder="Monthly Fee" style="height: 32px; font-size: 13px;">
                                    </div>
                                </div>
                                
                                <div class="mb-2">
                                    <label for="admit_discounted_student" class="form-label mb-0 fs-13 fw-medium">Discounted Student?</label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-light border-end-0 py-1" style="height: 32px;"><span class="material-symbols-outlined" style="font-size: 16px;">percent</span></span>
                                        <select class="form-select border-start-0 py-1" id="admit_discounted_student" name="discounted_student" style="height: 32px; font-size: 13px;" onchange="toggleAdmitDiscountField()">
                                            <option value="">Select</option>
                                            <option value="1">Yes</option>
                                            <option value="0">No</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="mb-2" id="admit_discount_amount_container" style="display: none;">
                                    <label for="admit_discount_amount" class="form-label mb-0 fs-13 fw-medium">Discount Amount</label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-light border-end-0 py-1" style="height: 32px;"><span class="material-symbols-outlined" style="font-size: 16px;">payments</span></span>
                                        <input type="number" step="0.01" class="form-control border-start-0 py-1" id="admit_discount_amount" name="discount_amount" placeholder="Discount amount" style="height: 32px; font-size: 13px;">
                                    </div>
                                </div>

                                <div class="mb-2" id="admit_discount_reason_container" style="display: none;">
                                    <label for="admit_discount_reason" class="form-label mb-0 fs-13 fw-medium">Discount Reason</label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-light border-end-0 py-1" style="height: 32px;"><span class="material-symbols-outlined" style="font-size: 16px;">description</span></span>
                                        <input type="text" class="form-control border-start-0 py-1" id="admit_discount_reason" name="discount_reason" placeholder="Reason for discount" style="height: 32px; font-size: 13px;">
                                    </div>
                                </div>
                                
                                <div class="mb-2">
                                    <label for="admit_transport_route" class="form-label mb-0 fs-13 fw-medium">Transport Route</label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-light border-end-0 py-1" style="height: 32px;"><span class="material-symbols-outlined" style="font-size: 16px;">directions_bus</span></span>
                                        <select class="form-select border-start-0 py-1" id="admit_transport_route" name="transport_route" style="height: 32px; font-size: 13px;" onchange="loadAdmitTransportFare(this.value)">
                                            <option value="">Select Transport Route</option>
                                            @foreach($transportRoutes as $route)
                                                <option value="{{ $route }}">{{ $route }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="mb-2" id="admit_transport_fare_container" style="display: none;">
                                    <label for="admit_transport_fare" class="form-label mb-0 fs-13 fw-medium">Transport Fare</label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-light border-end-0 py-1" style="height: 32px;"><span class="material-symbols-outlined" style="font-size: 16px;">payments</span></span>
                                        <input type="number" step="0.01" class="form-control border-start-0 py-1" id="admit_transport_fare" name="transport_fare" placeholder="Transport fare amount" readonly style="height: 32px; font-size: 13px; background-color: #f8f9fa;">
                                    </div>
                                </div>
                                
                                <div class="mb-2">
                                    <label for="admit_admission_notification" class="form-label mb-0 fs-13 fw-medium">Admission Notification</label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-light border-end-0 py-1" style="height: 32px;"><span class="material-symbols-outlined" style="font-size: 16px;">notifications</span></span>
                                        <select class="form-select border-start-0 py-1" id="admit_admission_notification" name="admission_notification" style="height: 32px; font-size: 13px;">
                                            <option value="sms_app">SMS App</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="mb-2">
                                    <label for="admit_generate_admission_fee" class="form-label mb-0 fs-13 fw-medium">Generate Admission Fee</label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-light border-end-0 py-1" style="height: 32px;"><span class="material-symbols-outlined" style="font-size: 16px;">payments</span></span>
                                        <select class="form-select border-start-0 py-1" id="admit_generate_admission_fee" name="generate_admission_fee" style="height: 32px; font-size: 13px;" onchange="toggleAdmitAdmissionFeeAmount()">
                                            <option value="">Select</option>
                                            <option value="1">Yes</option>
                                            <option value="0">No</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="mb-2" id="admit_admission_fee_amount_container" style="display: none;">
                                    <label for="admit_admission_fee_amount" class="form-label mb-0 fs-13 fw-medium">Amount</label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-light border-end-0 py-1" style="height: 32px;"><span class="material-symbols-outlined" style="font-size: 16px;">attach_money</span></span>
                                        <input type="number" step="0.01" class="form-control border-start-0 py-1" id="admit_admission_fee_amount" name="admission_fee_amount" placeholder="admission fee amount" style="height: 32px; font-size: 13px;">
                                    </div>
                                </div>
                                
                                <div class="mb-2">
                                    <label for="admit_generate_other_fee" class="form-label mb-0 fs-13 fw-medium">Generate Other Fee</label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-light border-end-0 py-1" style="height: 32px;"><span class="material-symbols-outlined" style="font-size: 16px;">receipt</span></span>
                                        <select class="form-select border-start-0 py-1" id="admit_generate_other_fee" name="generate_other_fee" style="height: 32px; font-size: 13px;" onchange="toggleAdmitOtherFeeFields()">
                                            <option value="">Select</option>
                                            <option value="1">Yes</option>
                                            <option value="0">No</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="mb-2" id="admit_fee_type_container" style="display: none;">
                                    <label for="admit_fee_type" class="form-label mb-0 fs-13 fw-medium">Fee Type / Fee Head</label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-light border-end-0 py-1" style="height: 32px;"><span class="material-symbols-outlined" style="font-size: 16px;">category</span></span>
                                        <select class="form-select border-start-0 py-1" id="admit_fee_type" name="fee_type" style="height: 32px; font-size: 13px;" onchange="toggleAdmitOtherFeeAmount()">
                                            <option value="">Select Fee Type</option>
                                            @foreach($feeTypes as $feeType)
                                                <option value="{{ $feeType }}">{{ $feeType }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="mb-2" id="admit_other_fee_amount_container" style="display: none;">
                                    <label for="admit_other_fee_amount" class="form-label mb-0 fs-13 fw-medium">Amount</label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-light border-end-0 py-1" style="height: 32px;"><span class="material-symbols-outlined" style="font-size: 16px;">attach_money</span></span>
                                        <input type="number" step="0.01" class="form-control border-start-0 py-1" id="admit_other_fee_amount" name="other_fee_amount" placeholder="Enter amount" style="height: 32px; font-size: 13px;">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Academic Information -->
                        <div class="col-md-6">
                            <div class="card border-0 shadow-sm rounded-3 p-2 mb-2" style="background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);">
                                <div class="d-flex align-items-center mb-2" style="background-color: #003471; padding: 8px 12px; margin: -8px -8px 8px -8px; border-radius: 8px 8px 0 0;">
                                    <span class="material-symbols-outlined me-2" style="font-size: 18px; color: #ffffff;">school</span>
                                    <h5 class="mb-0 fw-semibold fs-15" style="color: #ffffff;">Academic Information</h5>
                                </div>
                                
                                <div class="mb-2">
                                    <label for="admit_student_code" class="form-label mb-0 fs-13 fw-medium">Student Code</label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-light border-end-0 py-1" style="height: 32px;"><span class="material-symbols-outlined" style="font-size: 16px;">qr_code</span></span>
                                        <input type="text" class="form-control border-start-0 py-1" id="admit_student_code" name="student_code" placeholder="Auto-generated" readonly style="height: 32px; font-size: 13px; background-color: #e9ecef; font-weight: 600;">
                                    </div>
                                    <small class="text-muted fs-11 mt-0 d-block">
                                        <span class="material-symbols-outlined" style="font-size: 12px; vertical-align: middle;">auto_awesome</span>
                                        Auto-generated code
                                    </small>
                                </div>
                                
                                <div class="mb-2">
                                    <label for="admit_gr_number" class="form-label mb-0 fs-13 fw-medium">G.R Number</label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-light border-end-0 py-1" style="height: 32px;"><span class="material-symbols-outlined" style="font-size: 16px;">confirmation_number</span></span>
                                        <input type="text" class="form-control border-start-0 py-1" id="admit_gr_number" name="gr_number" placeholder="G.R Number" style="height: 32px; font-size: 13px;">
                                    </div>
                                </div>
                                
                                <div class="mb-2">
                                    <label for="admit_campus" class="form-label mb-0 fs-13 fw-medium">Campus</label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-light border-end-0 py-1" style="height: 32px;"><span class="material-symbols-outlined" style="font-size: 16px;">business</span></span>
                                        <select class="form-select border-start-0 py-1" id="admit_campus" name="campus" style="height: 32px; font-size: 13px;" onchange="loadAdmitClassesForCampus(this.value)">
                                            <option value="">Select Campus</option>
                                            @foreach($campuses as $campus)
                                                <option value="{{ $campus }}">{{ $campus }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="mb-2">
                                    <label for="admit_class" class="form-label mb-0 fs-13 fw-medium">
                                        Class <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-light border-end-0 py-1" style="height: 32px;"><span class="material-symbols-outlined" style="font-size: 16px;">class</span></span>
                                        <select class="form-select border-start-0 py-1" id="admit_class" name="class" required style="height: 32px; font-size: 13px;" onchange="loadAdmitSectionsForClass(this.value)">
                                            <option value="">Select Class</option>
                                            @foreach($classes as $class)
                                                <option value="{{ $class }}">{{ $class }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="mb-2">
                                    <label for="admit_section" class="form-label mb-0 fs-13 fw-medium">Section</label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-light border-end-0 py-1" style="height: 32px;"><span class="material-symbols-outlined" style="font-size: 16px;">groups</span></span>
                                        <select class="form-select border-start-0 py-1" id="admit_section" name="section" style="height: 32px; font-size: 13px;">
                                            <option value="">Select Section</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="mb-2">
                                    <label for="admit_previous_school" class="form-label mb-0 fs-13 fw-medium">Previous School</label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-light border-end-0 py-1" style="height: 32px;"><span class="material-symbols-outlined" style="font-size: 16px;">history_edu</span></span>
                                        <input type="text" class="form-control border-start-0 py-1" id="admit_previous_school" name="previous_school" placeholder="Previous School" style="height: 32px; font-size: 13px;">
                                    </div>
                                </div>
                                
                                <div class="mb-2">
                                    <label for="admit_admission_date" class="form-label mb-0 fs-13 fw-medium">
                                        Admission Date <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-light border-end-0 py-1" style="height: 32px;"><span class="material-symbols-outlined" style="font-size: 16px;">event</span></span>
                                        <input type="date" class="form-control border-start-0 py-1" id="admit_admission_date" name="admission_date" value="{{ date('Y-m-d') }}" required style="height: 32px; font-size: 13px;">
                                    </div>
                                </div>
                                
                                <div class="mb-2">
                                    <label for="admit_reference_remarks" class="form-label mb-0 fs-13 fw-medium">Reference/Remarks</label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-light border-end-0 align-items-start py-1" style="height: auto;"><span class="material-symbols-outlined" style="font-size: 16px;">note</span></span>
                                        <textarea class="form-control border-start-0 py-1" id="admit_reference_remarks" name="reference_remarks" rows="2" placeholder="Reference/Remarks" style="font-size: 13px; min-height: 50px;"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer p-3" style="background-color: #f8f9fa; border-top: 1px solid #e9ecef;">
                    <button type="button" class="btn btn-sm py-2 px-4 rounded-8" data-bs-dismiss="modal" style="background-color: #6c757d; color: white; border: none; transition: all 0.3s ease;">
                        <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">close</span>
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-sm py-2 px-4 rounded-8 inquiry-submit-btn">
                        <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">check_circle</span>
                        Admit Student
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    /* Inquiry Form Styling */
    #inquiryModal .inquiry-input-group {
        height: 36px;
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
        height: 36px;
        font-size: 13px;
        padding: 0.5rem 0.75rem;
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
        height: 36px;
        padding: 0 0.75rem;
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
    
    #inquiryModal .form-label {
        margin-bottom: 0.4rem;
        line-height: 1.3;
    }
    
    #inquiryModal textarea.inquiry-input {
        min-height: 60px;
        border-left: 1px solid #e0e7ff;
        padding-top: 0.5rem;
    }
    
    #inquiryModal textarea.inquiry-input:focus {
        border-left-color: #003471;
    }
    
    #inquiryModal .inquiry-submit-btn {
        background-color: white;
        color: #003471;
        border: 2px solid #003471;
        font-weight: 500;
        transition: all 0.3s ease;
        box-shadow: 0 2px 8px rgba(0, 52, 113, 0.15);
    }
    
    #inquiryModal .inquiry-submit-btn:hover {
        background-color: #003471;
        color: white;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0, 52, 113, 0.35);
    }
    
    #inquiryModal .inquiry-submit-btn:active {
        transform: translateY(0);
    }
    
    #inquiryModal .modal-body {
        background-color: #ffffff;
    }
    
    #inquiryModal .rounded-8 {
        border-radius: 8px;
    }
    
    /* Smooth transitions for inputs */
    #inquiryModal .form-control,
    #inquiryModal .form-select {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    /* Hover effect on input groups */
    #inquiryModal .inquiry-input-group:hover {
        border-color: #003471;
    }
    
    /* Add New Button Styling */
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
    
    .inquiry-add-btn:active {
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
</style>

<script>
function resetForm() {
    document.getElementById('inquiryForm').action = '{{ route('admission.inquiry.store') }}';
    document.getElementById('inquiryForm').reset();
    document.getElementById('methodField').innerHTML = '';
    document.getElementById('inquiryModalLabel').textContent = 'Add New Inquiry';
}

function editInquiry(id, name, parent, phone, gender, birthday, fullAddress) {
    document.getElementById('inquiryForm').action = '{{ route('admission.inquiry.update', ':id') }}'.replace(':id', id);
    document.getElementById('methodField').innerHTML = '@method('PUT')';
    const modalLabel = document.getElementById('inquiryModalLabel');
    modalLabel.innerHTML = '<span class="material-symbols-outlined" style="font-size: 20px; color: white;">edit</span><span style="color: white;">Edit Inquiry</span>';
    
    document.getElementById('name').value = name || '';
    document.getElementById('parent').value = parent || '';
    document.getElementById('phone').value = phone || '';
    document.getElementById('gender').value = gender || '';
    document.getElementById('birthday').value = birthday || '';
    document.getElementById('full_address').value = fullAddress || '';
    
    new bootstrap.Modal(document.getElementById('inquiryModal')).show();
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
    
    // Reset to first page on new search
    url.searchParams.set('page', '1');
    
    // Show loading state
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
    // Auto-clear if input is empty (optional - you can remove this if you want)
    // This is just for better UX
}

function clearSearch() {
    const url = new URL(window.location.href);
    url.searchParams.delete('search');
    url.searchParams.set('page', '1');
    
    // Show loading state
    const searchInput = document.getElementById('searchInput');
    searchInput.disabled = true;
    
    window.location.href = url.toString();
}

// Update entries per page
function updateEntriesPerPage(value) {
    const url = new URL(window.location.href);
    url.searchParams.set('per_page', value);
    url.searchParams.set('page', '1'); // Reset to first page
    window.location.href = url.toString();
}

// Print table
function printTable() {
    const searchParam = document.getElementById('searchInput')?.value?.trim();
    let printUrl = '{{ route("admission.inquiry.print") }}';
    if (searchParam) {
        printUrl += '?search=' + encodeURIComponent(searchParam);
    }

    const w = window.open(printUrl, '_blank');
    // If popup is blocked, fall back to same-tab navigation
    if (!w) {
        window.location.href = printUrl;
    }
}

// Admit Student Modal Functions
let admitStream = null;
let admitCapturedPhotoBase64 = null;

function openAdmitModal(inquiryId) {
    // Fetch inquiry data
    fetch(`/admission/inquiry/${inquiryId}/data`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const inquiry = data.inquiry;
                document.getElementById('admit_inquiry_id').value = inquiryId;
                document.getElementById('admit_student_name').value = inquiry.name || '';
                document.getElementById('admit_father_name').value = inquiry.parent || '';
                document.getElementById('admit_father_phone').value = inquiry.phone || '';
                document.getElementById('admit_gender').value = inquiry.gender || '';
                document.getElementById('admit_date_of_birth').value = inquiry.birthday || '';
                document.getElementById('admit_home_address').value = inquiry.full_address || '';
                
                // Reset photo
                admitCapturedPhotoBase64 = null;
                document.getElementById('admit_captured_photo_input').value = '';
                document.getElementById('admit_captured-image-container').style.display = 'none';
                
                // Show modal
                new bootstrap.Modal(document.getElementById('admitStudentModal')).show();
            }
        })
        .catch(error => {
            console.error('Error fetching inquiry data:', error);
            alert('Error loading inquiry data. Please try again.');
        });
}

function startAdmitLiveCapture() {
    const video = document.getElementById('admit_video');
    const canvas = document.getElementById('admit_canvas');
    const liveCaptureBtn = document.getElementById('admit_live-capture-btn');
    const capturedImageContainer = document.getElementById('admit_captured-image-container');
    
    capturedImageContainer.style.display = 'none';
    
    navigator.mediaDevices.getUserMedia({ video: true })
        .then(function(mediaStream) {
            admitStream = mediaStream;
            video.srcObject = admitStream;
            video.style.display = 'block';
            liveCaptureBtn.innerHTML = '<span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">camera_alt</span> Capture Photo';
            liveCaptureBtn.onclick = captureAdmitPhoto;
        })
        .catch(function(err) {
            alert('Error accessing camera: ' + err.message);
        });
}

function captureAdmitPhoto() {
    const video = document.getElementById('admit_video');
    const canvas = document.getElementById('admit_canvas');
    const capturedImage = document.getElementById('admit_captured-image');
    const capturedImageContainer = document.getElementById('admit_captured-image-container');
    const liveCaptureBtn = document.getElementById('admit_live-capture-btn');
    
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    
    const ctx = canvas.getContext('2d');
    ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
    
    admitCapturedPhotoBase64 = canvas.toDataURL('image/jpeg', 0.8);
    capturedImage.src = admitCapturedPhotoBase64;
    capturedImageContainer.style.display = 'block';
    
    if (admitStream) {
        admitStream.getTracks().forEach(track => track.stop());
        admitStream = null;
    }
    
    video.style.display = 'none';
    liveCaptureBtn.innerHTML = '<span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">camera</span> Select Camera';
    liveCaptureBtn.onclick = startAdmitLiveCapture;
    
    document.getElementById('admit_captured_photo_input').value = admitCapturedPhotoBase64;
}

function retakeAdmitPhoto() {
    const capturedImageContainer = document.getElementById('admit_captured-image-container');
    capturedImageContainer.style.display = 'none';
    admitCapturedPhotoBase64 = null;
    document.getElementById('admit_captured_photo_input').value = '';
    startAdmitLiveCapture();
}

function toggleAdmitParentPasswordField() {
    const createParentAccount = document.getElementById('admit_create_parent_account');
    const parentPasswordField = document.getElementById('admit_parent_password_field');
    const parentPasswordInput = document.getElementById('admit_parent_password');
    
    if (createParentAccount && parentPasswordField && parentPasswordInput) {
        if (createParentAccount.value === '1') {
            parentPasswordField.style.display = 'block';
            parentPasswordInput.required = true;
            if (!parentPasswordInput.value) {
                parentPasswordInput.value = 'parent';
            }
        } else {
            parentPasswordField.style.display = 'none';
            parentPasswordInput.required = false;
            parentPasswordInput.value = '';
        }
    }
}

function loadAdmitClassesForCampus(campusValue) {
    const classSelect = document.getElementById('admit_class');
    const sectionSelect = document.getElementById('admit_section');
    const studentCodeInput = document.getElementById('admit_student_code');
    
    classSelect.innerHTML = '<option value="">Select Class</option>';
    sectionSelect.innerHTML = '<option value="">Select Section</option>';
    
    if (!campusValue) {
        if (studentCodeInput) studentCodeInput.value = '';
        return;
    }
    
    fetch(`{{ route('admission.get-classes') }}?campus=${encodeURIComponent(campusValue)}`)
        .then(response => response.json())
        .then(data => {
            if (data.classes && data.classes.length > 0) {
                data.classes.forEach(className => {
                    const option = document.createElement('option');
                    option.value = className;
                    option.textContent = className;
                    classSelect.appendChild(option);
                });
            }
        })
        .catch(error => {
            console.error('Error loading classes:', error);
        });
    
    if (studentCodeInput) {
        studentCodeInput.value = 'Loading...';
        fetch(`{{ route('admission.get-next-student-code') }}?campus=${encodeURIComponent(campusValue)}`)
            .then(response => response.json())
            .then(data => {
                if (data.code) {
                    studentCodeInput.value = data.code;
                } else {
                    studentCodeInput.value = '';
                }
            })
            .catch(error => {
                console.error('Error loading student code:', error);
                studentCodeInput.value = '';
            });
    }
}

function loadAdmitSectionsForClass(classValue) {
    const sectionSelect = document.getElementById('admit_section');
    const campusSelect = document.getElementById('admit_campus');
    const campusValue = campusSelect ? campusSelect.value : '';
    
    sectionSelect.innerHTML = '<option value="">Select Section</option>';
    
    if (!classValue) {
        return;
    }
    
    const query = `class=${encodeURIComponent(classValue)}&campus=${encodeURIComponent(campusValue || '')}`;
    fetch(`{{ route('admission.get-sections') }}?${query}`)
        .then(response => response.json())
        .then(data => {
            if (data.sections && data.sections.length > 0) {
                data.sections.forEach(section => {
                    const option = document.createElement('option');
                    option.value = section;
                    option.textContent = section;
                    sectionSelect.appendChild(option);
                });
            }
        })
        .catch(error => {
            console.error('Error loading sections:', error);
        });
}

// Handle form submission
document.getElementById('admitStudentForm')?.addEventListener('submit', function(e) {
    if (admitCapturedPhotoBase64 && !document.getElementById('admit_photo').files.length) {
        document.getElementById('admit_captured_photo_input').value = admitCapturedPhotoBase64;
    }
});

// Toggle discount fields
function toggleAdmitDiscountField() {
    const discountedStudent = document.getElementById('admit_discounted_student');
    const discountAmountContainer = document.getElementById('admit_discount_amount_container');
    const discountReasonContainer = document.getElementById('admit_discount_reason_container');
    
    if (discountedStudent && discountAmountContainer && discountReasonContainer) {
        if (discountedStudent.value === '1') {
            discountAmountContainer.style.display = 'block';
            discountReasonContainer.style.display = 'block';
        } else {
            discountAmountContainer.style.display = 'none';
            discountReasonContainer.style.display = 'none';
            document.getElementById('admit_discount_amount').value = '';
            document.getElementById('admit_discount_reason').value = '';
        }
    }
}

// Load transport fare
function loadAdmitTransportFare(routeValue) {
    const transportFareContainer = document.getElementById('admit_transport_fare_container');
    const transportFareInput = document.getElementById('admit_transport_fare');
    const campusSelect = document.getElementById('admit_campus');
    const campusValue = campusSelect ? campusSelect.value : '';
    
    if (!routeValue) {
        if (transportFareContainer) transportFareContainer.style.display = 'none';
        if (transportFareInput) transportFareInput.value = '';
        return;
    }
    
    const query = `route=${encodeURIComponent(routeValue)}&campus=${encodeURIComponent(campusValue || '')}`;
    fetch(`{{ route('admission.get-route-fare') }}?${query}`)
        .then(response => response.json())
        .then(data => {
            if (data.fare && data.fare > 0) {
                if (transportFareContainer) transportFareContainer.style.display = 'block';
                if (transportFareInput) transportFareInput.value = data.fare;
            } else {
                if (transportFareContainer) transportFareContainer.style.display = 'none';
                if (transportFareInput) transportFareInput.value = '';
            }
        })
        .catch(error => {
            console.error('Error loading transport fare:', error);
            if (transportFareContainer) transportFareContainer.style.display = 'none';
            if (transportFareInput) transportFareInput.value = '';
        });
}

// Toggle admission fee amount field
function toggleAdmitAdmissionFeeAmount() {
    const generateAdmissionFee = document.getElementById('admit_generate_admission_fee');
    const admissionFeeAmountContainer = document.getElementById('admit_admission_fee_amount_container');
    
    if (generateAdmissionFee && admissionFeeAmountContainer) {
        if (generateAdmissionFee.value === '1') {
            admissionFeeAmountContainer.style.display = 'block';
        } else {
            admissionFeeAmountContainer.style.display = 'none';
            document.getElementById('admit_admission_fee_amount').value = '';
        }
    }
}

// Toggle other fee fields
function toggleAdmitOtherFeeFields() {
    const generateOtherFee = document.getElementById('admit_generate_other_fee');
    const feeTypeContainer = document.getElementById('admit_fee_type_container');
    const otherFeeAmountContainer = document.getElementById('admit_other_fee_amount_container');
    
    if (generateOtherFee && feeTypeContainer && otherFeeAmountContainer) {
        if (generateOtherFee.value === '1') {
            feeTypeContainer.style.display = 'block';
        } else {
            feeTypeContainer.style.display = 'none';
            otherFeeAmountContainer.style.display = 'none';
            document.getElementById('admit_fee_type').value = '';
            document.getElementById('admit_other_fee_amount').value = '';
        }
    }
}

// Toggle other fee amount field
function toggleAdmitOtherFeeAmount() {
    const feeType = document.getElementById('admit_fee_type');
    const otherFeeAmountContainer = document.getElementById('admit_other_fee_amount_container');
    
    if (feeType && otherFeeAmountContainer) {
        if (feeType.value) {
            otherFeeAmountContainer.style.display = 'block';
        } else {
            otherFeeAmountContainer.style.display = 'none';
            document.getElementById('admit_other_fee_amount').value = '';
        }
    }
}
</script>
@endsection
