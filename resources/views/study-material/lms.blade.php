@extends('layouts.app')

@section('title', 'Study Material - LMS')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-3 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0 fs-16 fw-semibold">Study Material - LMS</h4>
                @if(request()->hasAny(['filter_campus', 'filter_class', 'filter_section', 'filter_type']))
                <button type="button" class="btn btn-sm py-2 px-3 d-inline-flex align-items-center gap-1 rounded-8 material-add-btn" data-bs-toggle="modal" data-bs-target="#materialModal" onclick="resetForm()">
                    <span class="material-symbols-outlined" style="font-size: 16px;">add</span>
                    <span>Add Study Material / Lecture</span>
                </button>
                @endif
            </div>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show success-toast" role="alert" id="successAlert">
                    <div class="d-flex align-items-center gap-2">
                        <span class="material-symbols-outlined" style="font-size: 20px;">check_circle</span>
                        <span>{{ session('success') }}</span>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show error-toast" role="alert" id="errorAlert">
                    <div class="d-flex align-items-center gap-2">
                        <span class="material-symbols-outlined" style="font-size: 20px;">error</span>
                        <span>{{ session('error') }}</span>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <!-- Filter Form -->
            <form action="{{ route('study-material.lms') }}" method="GET" id="filterForm">
                <div class="row g-2 mb-3 align-items-end">
                    <!-- Campus -->
                    <div class="col-md-3">
                        <label for="filter_campus" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Campus</label>
                        <select class="form-select form-select-sm" id="filter_campus" name="filter_campus" style="height: 32px;">
                            <option value="">All Campuses</option>
                            @foreach($campuses as $campus)
                                @php
                                    $campusName = is_object($campus) ? ($campus->campus_name ?? '') : $campus;
                                @endphp
                                <option value="{{ $campusName }}" {{ $filterCampus == $campusName ? 'selected' : '' }}>{{ $campusName }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Class -->
                    <div class="col-md-3">
                        <label for="filter_class" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Class</label>
                        <select class="form-select form-select-sm" id="filter_class" name="filter_class" style="height: 32px;">
                            <option value="">All Classes</option>
                            @foreach($classes as $className)
                                <option value="{{ $className }}" {{ $filterClass == $className ? 'selected' : '' }}>{{ $className }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Section -->
                    <div class="col-md-3">
                        <label for="filter_section" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Section</label>
                        <select class="form-select form-select-sm" id="filter_section" name="filter_section" style="height: 32px;" {{ !$filterClass ? 'disabled' : '' }}>
                            <option value="">All Sections</option>
                            @if($filterClass)
                                @foreach($sections as $sectionName)
                                    <option value="{{ $sectionName }}" {{ $filterSection == $sectionName ? 'selected' : '' }}>{{ $sectionName }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>

                    <!-- Type -->
                    <div class="col-md-3">
                        <label for="filter_type" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Type</label>
                        <select class="form-select form-select-sm" id="filter_type" name="filter_type" style="height: 32px;">
                            <option value="">All Types</option>
                            @foreach($materialTypes as $type)
                                <option value="{{ $type }}" {{ $filterType == $type ? 'selected' : '' }}>{{ ucfirst($type) }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Filter Button -->
                <div class="row">
                    <div class="col-md-12 text-end">
                        <button type="submit" class="btn btn-sm py-1 px-3 rounded-8 filter-btn" style="height: 32px;">
                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">filter_alt</span>
                            <span style="font-size: 12px;">Filter</span>
                        </button>
                    </div>
                </div>
            </form>

            <!-- Study Materials Table - Only show when filters are applied -->
            @if(request()->hasAny(['filter_campus', 'filter_class', 'filter_section', 'filter_type']))
            <div class="mt-3">
                <div class="mb-2 p-2 rounded-8" style="background: linear-gradient(135deg, #003471 0%, #004a9f 100%);">
                    <h5 class="mb-0 text-white fs-15 fw-semibold d-flex align-items-center gap-2">
                        <span class="material-symbols-outlined" style="font-size: 18px;">menu_book</span>
                        <span>Study Materials List</span>
                        <span class="badge bg-light text-dark ms-2">
                            {{ $studyMaterials->count() }} {{ $studyMaterials->count() == 1 ? 'material' : 'materials' }} found
                        </span>
                    </h5>
                </div>

                <div class="default-table-area" style="margin-top: 0;">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Title</th>
                                    <th>Description</th>
                                    <th>Campus</th>
                                    <th>Class</th>
                                    <th>Section</th>
                                    <th>Subject</th>
                                    <th>Type</th>
                                    <th>File/URL</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($studyMaterials as $index => $material)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>
                                            <strong class="text-primary">{{ $material->title }}</strong>
                                        </td>
                                        <td>{{ $material->description ? (strlen($material->description) > 50 ? substr($material->description, 0, 50) . '...' : $material->description) : 'N/A' }}</td>
                                        <td>
                                            <span class="badge bg-info text-white">{{ $material->campus }}</span>
                                        </td>
                                        <td>{{ $material->class }}</td>
                                        <td>{{ $material->section ?? 'N/A' }}</td>
                                        <td>{{ $material->subject ?? 'N/A' }}</td>
                                        <td>
                                            <span class="badge bg-secondary text-white">{{ ucfirst($material->file_type) }}</span>
                                        </td>
                                        <td>
                                            @if($material->file_type === 'video' && $material->youtube_url)
                                                <a href="{{ $material->youtube_url }}" target="_blank" class="text-primary text-decoration-none">
                                                    <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">link</span>
                                                    YouTube Link
                                                </a>
                                            @elseif($material->file_path)
                                                <a href="{{ route('study-material.view-file', $material->id) }}" target="_blank" class="text-primary text-decoration-none">
                                                    <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">file_present</span>
                                                    View File
                                                </a>
                                            @else
                                                <span class="text-muted">N/A</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            <div class="d-inline-flex gap-1">
                                                <button type="button" class="btn btn-sm btn-danger px-2 py-1" title="Delete" onclick="if(confirm('Are you sure you want to delete this study material?')) { document.getElementById('delete-form-{{ $material->id }}').submit(); }">
                                                    <span class="material-symbols-outlined">delete</span>
                                                </button>
                                                <form id="delete-form-{{ $material->id }}" action="{{ route('study-material.destroy', $material->id) }}" method="POST" class="d-none">
                                                    @csrf
                                                    @method('DELETE')
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="10" class="text-center text-muted py-5">
                                            <span class="material-symbols-outlined" style="font-size: 48px; opacity: 0.3;">inbox</span>
                                            <p class="mt-2 mb-0">No study materials found.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @else
            <div class="text-center py-5 mt-3">
                <span class="material-symbols-outlined" style="font-size: 64px; color: #dee2e6; opacity: 0.5;">filter_list</span>
                <h5 class="mt-3 text-muted">Apply Filters to View Study Materials</h5>
                <p class="text-muted mb-0">Please select filters and click Filter to view study materials.</p>
            </div>
            @endif
        </div>
    </div>
</div>

<!-- Study Material Modal -->
<div class="modal fade" id="materialModal" tabindex="-1" aria-labelledby="materialModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
            <div class="modal-header p-3" style="background: linear-gradient(135deg, #003471 0%, #004a9f 100%); border: none;">
                <h5 class="modal-title fs-15 fw-semibold mb-0 d-flex align-items-center gap-2 text-white" id="materialModalLabel" style="color: white !important;">
                    <span class="material-symbols-outlined" style="font-size: 20px; color: white;">add</span>
                    <span style="color: white;">Add Study Material / Lecture</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" style="opacity: 0.8;"></button>
            </div>
            <form id="materialForm" method="POST" action="{{ route('study-material.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-body p-3">
                    <div class="row g-2">
                        <div class="col-md-12">
                            <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Title <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm material-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">title</span>
                                </span>
                                <input type="text" class="form-control material-input" name="title" id="title" placeholder="Enter title" required>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Description</label>
                            <div class="input-group input-group-sm material-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471; align-items: start; padding-top: 8px;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">description</span>
                                </span>
                                <textarea class="form-control material-input" name="description" id="description" rows="3" placeholder="Enter description (optional)" style="border: none; border-left: 1px solid #e0e7ff; border-radius: 0 8px 8px 0; resize: vertical;"></textarea>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Campus <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm material-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">location_on</span>
                                </span>
                                <select class="form-control material-input" name="campus" id="campus" required style="border: none; border-left: 1px solid #e0e7ff; border-radius: 0 8px 8px 0;">
                                    <option value="">Select Campus</option>
                                    @foreach($campuses as $campus)
                                        @php
                                            $campusName = is_object($campus) ? ($campus->campus_name ?? '') : $campus;
                                        @endphp
                                        <option value="{{ $campusName }}">{{ $campusName }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Class <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm material-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">class</span>
                                </span>
                                <select class="form-control material-input" name="class" id="class" required style="border: none; border-left: 1px solid #e0e7ff; border-radius: 0 8px 8px 0;">
                                    <option value="">Select Class</option>
                                    @foreach($classes as $className)
                                        <option value="{{ $className }}">{{ $className }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Section</label>
                            <div class="input-group input-group-sm material-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">group</span>
                                </span>
                                <select class="form-control material-input" name="section" id="section" style="border: none; border-left: 1px solid #e0e7ff; border-radius: 0 8px 8px 0;" disabled>
                                    <option value="">Select Section</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Subject</label>
                            <div class="input-group input-group-sm material-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">menu_book</span>
                                </span>
                                <select class="form-control material-input" name="subject" id="subject" style="border: none; border-left: 1px solid #e0e7ff; border-radius: 0 8px 8px 0;" disabled>
                                    <option value="">Select Subject</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">File Type <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm material-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">category</span>
                                </span>
                                <select class="form-control material-input" name="file_type" id="file_type" required style="border: none; border-left: 1px solid #e0e7ff; border-radius: 0 8px 8px 0;">
                                    <option value="">Select File Type</option>
                                    <option value="picture">Picture</option>
                                    <option value="video">Video</option>
                                    <option value="documents">Documents</option>
                                </select>
                            </div>
                        </div>
                        <!-- YouTube URL Field (shown when video is selected) -->
                        <div class="col-md-12" id="youtubeUrlField" style="display: none;">
                            <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">YouTube URL</label>
                            <div class="input-group input-group-sm material-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">link</span>
                                </span>
                                <input type="url" class="form-control material-input" name="youtube_url" id="youtube_url" placeholder="Enter YouTube URL">
                            </div>
                        </div>
                        <!-- File Upload Field (shown when picture or documents is selected) -->
                        <div class="col-md-12" id="fileUploadField" style="display: none;">
                            <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Upload File</label>
                            <div class="input-group input-group-sm material-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">upload_file</span>
                                </span>
                                <input type="file" class="form-control material-input" name="file" id="file" accept="image/*,.pdf,.doc,.docx">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer p-3" style="background-color: #f8f9fa; border-top: 1px solid #e9ecef;">
                    <button type="button" class="btn btn-sm py-2 px-4 rounded-8" data-bs-dismiss="modal" style="background-color: #6c757d; color: white; border: none; transition: all 0.3s ease;">
                        <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">close</span>
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-sm py-2 px-4 rounded-8 material-submit-btn">
                        <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">save</span>
                        Save Material
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.material-add-btn {
    background: linear-gradient(135deg, #003471 0%, #004a9f 100%);
    color: white;
    border: none;
    font-weight: 500;
    transition: all 0.3s ease;
    box-shadow: 0 2px 6px rgba(0, 52, 113, 0.2);
}

.material-add-btn:hover {
    background: linear-gradient(135deg, #004a9f 0%, #003471 100%);
    transform: translateY(-1px);
    box-shadow: 0 4px 10px rgba(0, 52, 113, 0.3);
    color: white;
}

.filter-btn {
    background: linear-gradient(135deg, #003471 0%, #004a9f 100%);
    color: white;
    border: none;
    transition: all 0.3s ease;
}

.filter-btn:hover {
    background: linear-gradient(135deg, #004a9f 0%, #003471 100%);
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0, 52, 113, 0.3);
}

/* Material Form Styling */
#materialModal .material-input-group {
    border-radius: 8px;
    overflow: hidden;
    transition: all 0.3s ease;
    border: 1px solid #dee2e6;
}

#materialModal .material-input-group:focus-within {
    box-shadow: 0 0 0 3px rgba(0, 52, 113, 0.15);
    border-color: #003471;
}

#materialModal .material-input {
    font-size: 13px;
    padding: 0.35rem 0.65rem;
    height: 32px;
    border: none;
    border-left: 1px solid #e0e7ff;
    border-radius: 0 8px 8px 0;
    transition: all 0.3s ease;
}

#materialModal .material-input[type="file"] {
    padding: 0.25rem 0.65rem;
    height: auto;
}

#materialModal textarea.material-input {
    min-height: 32px;
    padding: 0.35rem 0.65rem;
    height: auto;
}

#materialModal select.material-input {
    appearance: none;
    -webkit-appearance: none;
    -moz-appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23003471' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 0.65rem center;
    padding-right: 2rem;
}

#materialModal .material-input:focus {
    border-left-color: #003471;
    box-shadow: none;
    outline: none;
}

#materialModal .input-group-text {
    padding: 0 0.65rem;
    height: 32px;
    display: flex;
    align-items: center;
    border: none;
    border-right: 1px solid #e0e7ff;
    border-radius: 8px 0 0 8px;
    transition: all 0.3s ease;
}

#materialModal .material-input-group:focus-within .input-group-text {
    background-color: #003471 !important;
    color: white !important;
    border-right-color: #003471;
}

#materialModal .form-label {
    margin-bottom: 0.4rem;
    line-height: 1.3;
}

#materialModal .material-submit-btn {
    background: linear-gradient(135deg, #003471 0%, #004a9f 100%);
    color: white;
    border: none;
    font-weight: 500;
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(0, 52, 113, 0.25);
}

#materialModal .material-submit-btn:hover {
    background: linear-gradient(135deg, #004a9f 0%, #003471 100%);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 52, 113, 0.35);
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

.default-table-area .btn-sm {
    font-size: 11px;
    padding: 4px 8px;
    min-height: auto;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    line-height: 1;
    height: 28px;
    width: 28px;
    border-radius: 6px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    transition: all 0.2s ease;
}

.default-table-area .btn-sm:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
}

.default-table-area .btn-sm .material-symbols-outlined {
    font-size: 16px !important;
    vertical-align: middle;
    color: white !important;
    line-height: 1;
    display: inline-block;
}

.default-table-area .btn-danger .material-symbols-outlined {
    color: white !important;
}

/* Toast Notification Styling */
.success-toast,
.error-toast {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 9999;
    min-width: 300px;
    max-width: 400px;
    padding: 12px 16px;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    animation: slideInDown 0.3s ease-out;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
}

.success-toast {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    color: white;
    border: none;
}

.error-toast {
    background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
    color: white;
    border: none;
}

.success-toast .btn-close,
.error-toast .btn-close {
    filter: brightness(0) invert(1);
    opacity: 0.9;
    padding: 0;
    width: 20px;
    height: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-left: auto;
}

.success-toast .btn-close:hover,
.error-toast .btn-close:hover {
    opacity: 1;
}

.success-toast .material-symbols-outlined,
.error-toast .material-symbols-outlined {
    font-size: 20px;
    flex-shrink: 0;
}

.success-toast > div,
.error-toast > div {
    display: flex;
    align-items: center;
    gap: 8px;
    flex: 1;
}

@keyframes slideInDown {
    from {
        transform: translateY(-100%);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

@keyframes slideOutUp {
    from {
        transform: translateY(0);
        opacity: 1;
    }
    to {
        transform: translateY(-100%);
        opacity: 0;
    }
}

.success-toast.fade-out,
.error-toast.fade-out {
    animation: slideOutUp 0.3s ease-out forwards;
}
</style>

<script>
function resetForm() {
    document.getElementById('materialForm').reset();
    document.getElementById('materialModalLabel').innerHTML = '<span class="material-symbols-outlined" style="font-size: 20px; color: white;">add</span><span style="color: white;">Add Study Material / Lecture</span>';
    document.getElementById('youtubeUrlField').style.display = 'none';
    document.getElementById('fileUploadField').style.display = 'none';
    document.getElementById('section').innerHTML = '<option value="">Select Section</option>';
    document.getElementById('section').disabled = true;
    document.getElementById('subject').innerHTML = '<option value="">Select Subject</option>';
    document.getElementById('subject').disabled = true;
}

// Dynamic section and subject loading
document.addEventListener('DOMContentLoaded', function() {
    const classSelect = document.getElementById('class');
    const sectionSelect = document.getElementById('section');
    const subjectSelect = document.getElementById('subject');
    const fileTypeSelect = document.getElementById('file_type');
    const youtubeUrlField = document.getElementById('youtubeUrlField');
    const fileUploadField = document.getElementById('fileUploadField');

    // Load sections when class changes
    if (classSelect && sectionSelect) {
        classSelect.addEventListener('change', function() {
            const selectedClass = this.value;
            if (selectedClass) {
                loadSections(selectedClass);
                // Also load subjects
                loadSubjects(selectedClass, sectionSelect.value);
            } else {
                sectionSelect.innerHTML = '<option value="">Select Section</option>';
                sectionSelect.disabled = true;
                subjectSelect.innerHTML = '<option value="">Select Subject</option>';
                subjectSelect.disabled = true;
            }
        });
    }

    // Load subjects when section changes
    if (sectionSelect && subjectSelect) {
        sectionSelect.addEventListener('change', function() {
            const selectedSection = this.value;
            const selectedClass = classSelect ? classSelect.value : '';
            if (selectedClass) {
                loadSubjects(selectedClass, selectedSection);
            }
        });
    }

    // Show/hide file upload or YouTube URL based on file type
    if (fileTypeSelect) {
        fileTypeSelect.addEventListener('change', function() {
            const fileType = this.value;
            if (fileType === 'video') {
                youtubeUrlField.style.display = 'block';
                fileUploadField.style.display = 'none';
                document.getElementById('youtube_url').required = true;
                document.getElementById('file').required = false;
            } else if (fileType === 'picture' || fileType === 'documents') {
                youtubeUrlField.style.display = 'none';
                fileUploadField.style.display = 'block';
                document.getElementById('youtube_url').required = false;
                document.getElementById('file').required = true;
            } else {
                youtubeUrlField.style.display = 'none';
                fileUploadField.style.display = 'none';
                document.getElementById('youtube_url').required = false;
                document.getElementById('file').required = false;
            }
        });
    }

    function loadSections(selectedClass) {
        if (!sectionSelect) return;
        
        sectionSelect.innerHTML = '<option value="">Loading...</option>';
        sectionSelect.disabled = true;
        
        fetch(`{{ route('study-material.get-sections-by-class') }}?class=${encodeURIComponent(selectedClass)}`)
            .then(response => response.json())
            .then(data => {
                sectionSelect.innerHTML = '<option value="">Select Section</option>';
                if (data.sections && data.sections.length > 0) {
                    data.sections.forEach(section => {
                        const option = document.createElement('option');
                        option.value = section;
                        option.textContent = section;
                        sectionSelect.appendChild(option);
                    });
                }
                sectionSelect.disabled = false;
            })
            .catch(error => {
                console.error('Error loading sections:', error);
                sectionSelect.innerHTML = '<option value="">Error loading sections</option>';
                sectionSelect.disabled = false;
            });
    }

    function loadSubjects(selectedClass, selectedSection) {
        if (!subjectSelect) return;
        
        if (!selectedClass) {
            subjectSelect.innerHTML = '<option value="">Select Subject</option>';
            subjectSelect.disabled = true;
            return;
        }
        
        subjectSelect.innerHTML = '<option value="">Loading...</option>';
        subjectSelect.disabled = true;
        
        const params = new URLSearchParams();
        params.append('class', selectedClass);
        if (selectedSection) {
            params.append('section', selectedSection);
        }
        
        fetch(`{{ route('study-material.get-subjects-by-class-section') }}?${params.toString()}`)
            .then(response => response.json())
            .then(data => {
                subjectSelect.innerHTML = '<option value="">Select Subject</option>';
                if (data.subjects && data.subjects.length > 0) {
                    data.subjects.forEach(subject => {
                        const option = document.createElement('option');
                        option.value = subject;
                        option.textContent = subject;
                        subjectSelect.appendChild(option);
                    });
                }
                subjectSelect.disabled = false;
            })
            .catch(error => {
                console.error('Error loading subjects:', error);
                subjectSelect.innerHTML = '<option value="">Error loading subjects</option>';
                subjectSelect.disabled = false;
            });
    }
});

// Filter form section loading
document.addEventListener('DOMContentLoaded', function() {
    const filterClassSelect = document.getElementById('filter_class');
    const filterSectionSelect = document.getElementById('filter_section');

    if (filterClassSelect && filterSectionSelect) {
        filterClassSelect.addEventListener('change', function() {
            const selectedClass = this.value;
            if (selectedClass) {
                filterSectionSelect.innerHTML = '<option value="">Loading...</option>';
                filterSectionSelect.disabled = true;
                
                fetch(`{{ route('study-material.get-sections-by-class') }}?class=${encodeURIComponent(selectedClass)}`)
                    .then(response => response.json())
                    .then(data => {
                        filterSectionSelect.innerHTML = '<option value="">All Sections</option>';
                        if (data.sections && data.sections.length > 0) {
                            data.sections.forEach(section => {
                                const option = document.createElement('option');
                                option.value = section;
                                option.textContent = section;
                                filterSectionSelect.appendChild(option);
                            });
                        }
                        filterSectionSelect.disabled = false;
                    })
                    .catch(error => {
                        console.error('Error loading sections:', error);
                        filterSectionSelect.innerHTML = '<option value="">Error loading sections</option>';
                        filterSectionSelect.disabled = false;
                    });
            } else {
                filterSectionSelect.innerHTML = '<option value="">All Sections</option>';
                filterSectionSelect.disabled = true;
            }
        });

        // Initial load if class is already selected
        if (filterClassSelect.value) {
            filterClassSelect.dispatchEvent(new Event('change'));
        }
    }
});

// Auto-dismiss toast notifications
document.addEventListener('DOMContentLoaded', function() {
    const successAlert = document.getElementById('successAlert');
    const errorAlert = document.getElementById('errorAlert');
    
    function dismissToast(toast) {
        if (toast) {
            toast.classList.add('fade-out');
            setTimeout(() => {
                toast.remove();
            }, 300);
        }
    }
    
    if (successAlert) {
        setTimeout(() => {
            dismissToast(successAlert);
        }, 5000);
    }
    
    if (errorAlert) {
        setTimeout(() => {
            dismissToast(errorAlert);
        }, 5000);
    }
});
</script>
@endsection
