@extends('layouts.app')

@section('title', 'School Noticeboard')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-3 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0 fs-16 fw-semibold">School Noticeboard</h4>
                <button type="button" class="btn btn-sm py-2 px-3 d-inline-flex align-items-center gap-1 rounded-8 noticeboard-add-btn" data-bs-toggle="modal" data-bs-target="#noticeboardModal" onclick="resetForm()">
                    <span class="material-symbols-outlined" style="font-size: 16px;">add</span>
                    <span>Add New Notice</span>
                </button>
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
                        <a href="{{ route('school.noticeboard.export', ['format' => 'excel']) }}{{ request()->has('search') ? '?search=' . request('search') : '' }}" class="btn btn-sm px-2 py-1 export-btn excel-btn">
                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">description</span>
                            <span>Excel</span>
                        </a>
                        <a href="{{ route('school.noticeboard.export', ['format' => 'pdf']) }}{{ request()->has('search') ? '?search=' . request('search') : '' }}" class="btn btn-sm px-2 py-1 export-btn pdf-btn" target="_blank">
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
                            <input type="text" id="searchInput" class="form-control border-start-0 border-end-0" placeholder="Search noticeboards..." value="{{ request('search') }}" onkeypress="handleSearchKeyPress(event)" oninput="handleSearchInput(event)" style="padding: 4px 8px; font-size: 13px;">
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
                    <span>Noticeboards List</span>
                </h5>
            </div>

            <!-- Search Results Info -->
            @if(request('search'))
                <div class="search-results-info">
                    <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle; color: #003471;">search</span>
                    <strong>Search Results:</strong> Showing results for "<strong>{{ request('search') }}</strong>"
                    @if(isset($noticeboards))
                        ({{ $noticeboards->total() }} {{ $noticeboards->total() == 1 ? 'result' : 'results' }} found)
                    @endif
                    <a href="{{ route('school.noticeboard') }}" class="text-decoration-none ms-2" style="color: #003471;">
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
                                <th>Campus</th>
                                <th>Title</th>
                                <th>Notice</th>
                                <th>Date</th>
                                <th>Image</th>
                                <th>Show On</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if(isset($noticeboards) && $noticeboards->count() > 0)
                                @forelse($noticeboards as $noticeboard)
                                    <tr>
                                        <td>{{ $loop->iteration + (($noticeboards->currentPage() - 1) * $noticeboards->perPage()) }}</td>
                                        <td>
                                            <span class="badge bg-info text-white">{{ $noticeboard->campus ?? 'N/A' }}</span>
                                        </td>
                                        <td>
                                            <strong class="text-primary">{{ $noticeboard->title }}</strong>
                                        </td>
                                        <td>{{ $noticeboard->notice ? (strlen($noticeboard->notice) > 50 ? substr($noticeboard->notice, 0, 50) . '...' : $noticeboard->notice) : 'N/A' }}</td>
                                        <td>{{ $noticeboard->date->format('d M Y') }}</td>
                                        <td>
                                            @if($noticeboard->image)
                                                <img src="{{ asset('storage/' . $noticeboard->image) }}" alt="Notice Image" style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;">
                                            @else
                                                <span class="text-muted">No Image</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($noticeboard->show_on === 'Yes')
                                                <span class="badge bg-success text-white">Yes</span>
                                            @else
                                                <span class="badge bg-secondary text-white">No</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            <div class="d-inline-flex gap-1">
                                                <button type="button" class="btn btn-sm btn-primary px-2 py-1" title="Edit" onclick="editNoticeboard({{ $noticeboard->id }}, '{{ addslashes($noticeboard->campus ?? '') }}', '{{ addslashes($noticeboard->title) }}', '{{ addslashes($noticeboard->notice ?? '') }}', '{{ $noticeboard->date->format('Y-m-d') }}', '{{ $noticeboard->show_on ?? '' }}', '{{ $noticeboard->image ? asset('storage/' . $noticeboard->image) : '' }}')">
                                                    <span class="material-symbols-outlined">edit</span>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-danger px-2 py-1" title="Delete" onclick="if(confirm('Are you sure you want to delete this noticeboard?')) { document.getElementById('delete-form-{{ $noticeboard->id }}').submit(); }">
                                                    <span class="material-symbols-outlined">delete</span>
                                                </button>
                                                <form id="delete-form-{{ $noticeboard->id }}" action="{{ route('school.noticeboard.destroy', $noticeboard->id) }}" method="POST" class="d-none">
                                                    @csrf
                                                    @method('DELETE')
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-5">
                                            <span class="material-symbols-outlined" style="font-size: 48px; opacity: 0.3;">inbox</span>
                                            <p class="mt-2 mb-0">No noticeboards found.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            @else
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-5">
                                        <span class="material-symbols-outlined" style="font-size: 48px; opacity: 0.3;">inbox</span>
                                        <p class="mt-2 mb-0">No noticeboards found.</p>
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>

            @if(isset($noticeboards) && $noticeboards->hasPages())
                <div class="mt-3">
                    {{ $noticeboards->links() }}
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Noticeboard Modal -->
<div class="modal fade" id="noticeboardModal" tabindex="-1" aria-labelledby="noticeboardModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
            <div class="modal-header p-3" style="background: linear-gradient(135deg, #003471 0%, #004a9f 100%); border: none;">
                <h5 class="modal-title fs-15 fw-semibold mb-0 d-flex align-items-center gap-2 text-white" id="noticeboardModalLabel" style="color: white !important;">
                    <span class="material-symbols-outlined" style="font-size: 20px; color: white;">note</span>
                    <span style="color: white;">Add New Notice</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" style="opacity: 0.8;"></button>
            </div>
            <form id="noticeboardForm" method="POST" action="{{ route('school.noticeboard.store') }}" enctype="multipart/form-data">
                @csrf
                <div id="methodField"></div>
                <div class="modal-body p-3">
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Campus</label>
                            <div class="input-group input-group-sm noticeboard-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">apartment</span>
                                </span>
                                <select class="form-control noticeboard-input" name="campus" id="campus" style="border: none; border-left: 1px solid #e0e7ff; border-radius: 0 8px 8px 0;">
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
                            <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Date <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm noticeboard-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">calendar_today</span>
                                </span>
                                <input type="date" class="form-control noticeboard-input" name="date" id="date" required>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Title <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm noticeboard-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">title</span>
                                </span>
                                <input type="text" class="form-control noticeboard-input" name="title" id="title" placeholder="Enter notice title" required>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Notice</label>
                            <div class="input-group input-group-sm noticeboard-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471; align-items: start; padding-top: 8px;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">description</span>
                                </span>
                                <textarea class="form-control noticeboard-input" name="notice" id="notice" rows="4" placeholder="Enter notice content (optional)" style="border: none; border-left: 1px solid #e0e7ff; border-radius: 0 8px 8px 0; resize: vertical;"></textarea>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Image</label>
                            <div class="input-group input-group-sm noticeboard-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">image</span>
                                </span>
                                <input type="file" class="form-control noticeboard-input" name="image" id="image" accept="image/*" style="border: none; border-left: 1px solid #e0e7ff; border-radius: 0 8px 8px 0; padding: 0.5rem 0.75rem;">
                            </div>
                            <div id="imagePreview" class="mt-2"></div>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Show On</label>
                            <div class="input-group input-group-sm noticeboard-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">visibility</span>
                                </span>
                                <select class="form-control noticeboard-input" name="show_on" id="show_on" style="border: none; border-left: 1px solid #e0e7ff; border-radius: 0 8px 8px 0;">
                                    <option value="No">No</option>
                                    <option value="Yes">Yes</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer p-3" style="background-color: #f8f9fa; border-top: 1px solid #e9ecef;">
                    <button type="button" class="btn btn-sm py-2 px-4 rounded-8" data-bs-dismiss="modal" style="background-color: #6c757d; color: white; border: none; transition: all 0.3s ease;">
                        <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">close</span>
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-sm py-2 px-4 rounded-8 noticeboard-submit-btn">
                        <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">save</span>
                        Save Notice
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    /* Noticeboard Form Styling */
    #noticeboardModal .noticeboard-input-group {
        border-radius: 8px;
        overflow: hidden;
        transition: all 0.3s ease;
        border: 1px solid #dee2e6;
    }
    
    #noticeboardModal .noticeboard-input-group:focus-within {
        box-shadow: 0 0 0 3px rgba(0, 52, 113, 0.15);
        border-color: #003471;
    }
    
    #noticeboardModal .noticeboard-input {
        font-size: 13px;
        padding: 0.35rem 0.65rem;
        height: 32px;
        border: none;
        border-left: 1px solid #e0e7ff;
        border-radius: 0 8px 8px 0;
        transition: all 0.3s ease;
    }
    
    #noticeboardModal .noticeboard-input[type="date"],
    #noticeboardModal .noticeboard-input[type="file"] {
        height: 32px;
        padding: 0.35rem 0.65rem;
    }
    
    #noticeboardModal textarea.noticeboard-input {
        min-height: 32px;
        padding: 0.35rem 0.65rem;
        height: auto;
    }
    
    #noticeboardModal select.noticeboard-input {
        appearance: none;
        -webkit-appearance: none;
        -moz-appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23003471' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 0.65rem center;
        padding-right: 2rem;
    }
    
    #noticeboardModal .noticeboard-input:focus {
        border-left-color: #003471;
        box-shadow: none;
        outline: none;
    }
    
    #noticeboardModal .input-group-text {
        padding: 0 0.65rem;
        height: 32px;
        display: flex;
        align-items: center;
        border: none;
        border-right: 1px solid #e0e7ff;
        border-radius: 8px 0 0 8px;
        transition: all 0.3s ease;
    }
    
    #noticeboardModal .noticeboard-input-group:focus-within .input-group-text {
        background-color: #003471 !important;
        color: white !important;
        border-right-color: #003471;
    }
    
    #noticeboardModal .form-label {
        margin-bottom: 0.4rem;
        line-height: 1.3;
    }
    
    #noticeboardModal .noticeboard-submit-btn {
        background: linear-gradient(135deg, #003471 0%, #004a9f 100%);
        color: white;
        border: none;
        font-weight: 500;
        transition: all 0.3s ease;
        box-shadow: 0 2px 8px rgba(0, 52, 113, 0.25);
    }
    
    #noticeboardModal .noticeboard-submit-btn:hover {
        background: linear-gradient(135deg, #004a9f 0%, #003471 100%);
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0, 52, 113, 0.35);
    }
    
    .noticeboard-add-btn {
        background: linear-gradient(135deg, #003471 0%, #004a9f 100%);
        color: white;
        border: none;
        font-weight: 500;
        transition: all 0.3s ease;
        box-shadow: 0 2px 6px rgba(0, 52, 113, 0.2);
    }
    
    .noticeboard-add-btn:hover {
        background: linear-gradient(135deg, #004a9f 0%, #003471 100%);
        transform: translateY(-1px);
        box-shadow: 0 4px 10px rgba(0, 52, 113, 0.3);
        color: white;
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
    
    .search-results-info {
        padding: 8px 12px;
        background-color: #e7f3ff;
        border-left: 3px solid #003471;
        border-radius: 4px;
        margin-bottom: 15px;
        font-size: 13px;
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
    
    .default-table-area .btn-primary .material-symbols-outlined,
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
    document.getElementById('noticeboardForm').reset();
    document.getElementById('methodField').innerHTML = '';
    document.getElementById('imagePreview').innerHTML = '';
    document.getElementById('noticeboardModalLabel').innerHTML = '<span class="material-symbols-outlined" style="font-size: 20px; color: white;">note</span><span style="color: white;">Add New Notice</span>';
    document.getElementById('noticeboardForm').action = '{{ route("school.noticeboard.store") }}';
    document.getElementById('noticeboardForm').enctype = 'multipart/form-data';
}

function editNoticeboard(id, campus, title, notice, date, showOn, imageUrl) {
    resetForm();
    document.getElementById('methodField').innerHTML = '<input type="hidden" name="_method" value="PUT">';
    document.getElementById('noticeboardForm').action = '{{ route("school.noticeboard.update", ":id") }}'.replace(':id', id);
    document.getElementById('campus').value = campus;
    document.getElementById('title').value = title;
    document.getElementById('notice').value = notice;
    document.getElementById('date').value = date;
    
    // Set show_on dropdown based on showOn value
    if (showOn) {
        // If showOn has any value (not empty), set to "Yes", otherwise "No"
        document.getElementById('show_on').value = showOn.trim() !== '' ? 'Yes' : 'No';
    } else {
        document.getElementById('show_on').value = 'No';
    }
    
    // Show existing image if available
    if (imageUrl) {
        document.getElementById('imagePreview').innerHTML = '<img src="' + imageUrl + '" alt="Current Image" style="max-width: 200px; max-height: 200px; border-radius: 4px; margin-top: 8px;">';
    }
    
    document.getElementById('noticeboardModalLabel').innerHTML = '<span class="material-symbols-outlined" style="font-size: 20px; color: white;">edit</span><span style="color: white;">Edit Notice</span>';
    new bootstrap.Modal(document.getElementById('noticeboardModal')).show();
}

// Image preview
document.getElementById('image').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('imagePreview').innerHTML = '<img src="' + e.target.result + '" alt="Preview" style="max-width: 200px; max-height: 200px; border-radius: 4px; margin-top: 8px;">';
        };
        reader.readAsDataURL(file);
    } else {
        document.getElementById('imagePreview').innerHTML = '';
    }
});

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
    if (event.target.value === '') {
        clearSearch();
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

function printTable() {
    window.print();
}

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
