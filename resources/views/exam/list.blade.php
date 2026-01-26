@extends('layouts.app')

@section('title', 'Exam List')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-3 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0 fs-16 fw-semibold">Exam List</h4>
                <button type="button" class="btn btn-sm py-2 px-3 d-inline-flex align-items-center gap-1 rounded-8 exam-add-btn" data-bs-toggle="modal" data-bs-target="#examModal" onclick="resetForm()">
                    <span class="material-symbols-outlined" style="font-size: 16px;">add</span>
                    <span>Add New Exam</span>
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
                        <a href="{{ route('exam.list.export', ['format' => 'excel']) }}{{ request()->hasAny(['search', 'filter_campus']) ? ('?' . http_build_query(array_filter(['search' => request('search'), 'filter_campus' => request('filter_campus')]))) : '' }}" class="btn btn-sm px-2 py-1 export-btn excel-btn">
                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">description</span>
                            <span>Excel</span>
                        </a>
                        <a href="{{ route('exam.list.export', ['format' => 'pdf']) }}{{ request()->hasAny(['search', 'filter_campus']) ? ('?' . http_build_query(array_filter(['search' => request('search'), 'filter_campus' => request('filter_campus')]))) : '' }}" class="btn btn-sm px-2 py-1 export-btn pdf-btn" target="_blank">
                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">picture_as_pdf</span>
                            <span>PDF</span>
                        </a>
                        <button type="button" class="btn btn-sm px-2 py-1 export-btn print-btn" onclick="printTable()">
                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">print</span>
                            <span>Print</span>
                        </button>
                    </div>
                    
                    <!-- Campus Filter -->
                    <div class="d-flex align-items-center gap-2">
                        <label for="filter_campus" class="mb-0 fs-13 fw-medium text-dark">Campus:</label>
                        <select id="filter_campus" class="form-select form-select-sm" style="width: auto; min-width: 140px;">
                            <option value="">All Campuses</option>
                            @foreach($campuses as $campus)
                                @php
                                    $campusName = is_object($campus) ? ($campus->campus_name ?? $campus->name ?? '') : $campus;
                                @endphp
                                <option value="{{ $campusName }}" {{ ($filterCampus ?? '') === $campusName ? 'selected' : '' }}>{{ $campusName }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Search -->
                    <div class="d-flex align-items-center gap-2">
                        <label for="searchInput" class="mb-0 fs-13 fw-medium text-dark">Search:</label>
                        <div class="input-group input-group-sm search-input-group" style="width: 280px;">
                            <span class="input-group-text bg-light border-end-0" style="background-color: #f0f4ff !important; border-color: #e0e7ff; padding: 4px 8px;">
                                <span class="material-symbols-outlined" style="font-size: 14px; color: #003471;">search</span>
                            </span>
                            <input type="text" id="searchInput" class="form-control border-start-0 border-end-0" placeholder="Search exams..." value="{{ request('search') }}" onkeypress="handleSearchKeyPress(event)" oninput="handleSearchInput(event)" style="padding: 4px 8px; font-size: 13px;">
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
                    <span>Exams List</span>
                </h5>
            </div>

            <!-- Search Results Info -->
            @if(request('search'))
                <div class="search-results-info">
                    <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle; color: #003471;">search</span>
                    <strong>Search Results:</strong> Showing results for "<strong>{{ request('search') }}</strong>"
                    @if(isset($exams))
                        ({{ $exams->total() }} {{ $exams->total() == 1 ? 'result' : 'results' }} found)
                    @endif
                    <a href="{{ route('exam.list') }}" class="text-decoration-none ms-2" style="color: #003471;">
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
                                <th>Exam Name</th>
                                <th>Description</th>
                                <th>Exam Date</th>
                                <th>Session</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if(isset($exams) && $exams->count() > 0)
                                @forelse($exams as $exam)
                                    <tr>
                                        <td>{{ $loop->iteration + (($exams->currentPage() - 1) * $exams->perPage()) }}</td>
                                        <td>
                                            <span class="badge bg-info text-white">{{ $exam->campus }}</span>
                                        </td>
                                        <td>
                                            <strong class="text-primary">{{ $exam->exam_name }}</strong>
                                        </td>
                                        <td>{{ $exam->description ? (strlen($exam->description) > 50 ? substr($exam->description, 0, 50) . '...' : $exam->description) : 'N/A' }}</td>
                                        <td>{{ $exam->exam_date->format('d M Y') }}</td>
                                        <td>{{ $exam->session }}</td>
                                        <td class="text-end">
                                            <div class="d-inline-flex gap-1">
                                                <button type="button" class="btn btn-sm btn-primary px-2 py-1" title="Edit" onclick="editExam({{ $exam->id }}, '{{ addslashes($exam->campus) }}', '{{ addslashes($exam->exam_name) }}', '{{ addslashes($exam->description ?? '') }}', '{{ $exam->exam_date->format('Y-m-d') }}', '{{ addslashes($exam->session) }}')">
                                                    <span class="material-symbols-outlined">edit</span>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-danger px-2 py-1" title="Delete" onclick="if(confirm('Are you sure you want to delete this exam?')) { document.getElementById('delete-form-{{ $exam->id }}').submit(); }">
                                                    <span class="material-symbols-outlined">delete</span>
                                                </button>
                                                <form id="delete-form-{{ $exam->id }}" action="{{ route('exam.list.destroy', $exam->id) }}" method="POST" class="d-none">
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
                                            <p class="mt-2 mb-0">No exams found.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            @else
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-5">
                                        <span class="material-symbols-outlined" style="font-size: 48px; opacity: 0.3;">inbox</span>
                                        <p class="mt-2 mb-0">No exams found.</p>
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>

            @if(isset($exams) && $exams->hasPages())
                <div class="mt-3">
                    {{ $exams->links() }}
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Exam Modal -->
<div class="modal fade" id="examModal" tabindex="-1" aria-labelledby="examModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
            <div class="modal-header p-3" style="background: linear-gradient(135deg, #003471 0%, #004a9f 100%); border: none;">
                <h5 class="modal-title fs-15 fw-semibold mb-0 d-flex align-items-center gap-2 text-white" id="examModalLabel" style="color: white !important;">
                    <span class="material-symbols-outlined" style="font-size: 20px; color: white;">quiz</span>
                    <span style="color: white;">Add New Exam</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" style="opacity: 0.8;"></button>
            </div>
            <form id="examForm" method="POST" action="{{ route('exam.list.store') }}">
                @csrf
                <div id="methodField"></div>
                <div class="modal-body p-3">
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Campus <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm exam-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">location_on</span>
                                </span>
                                <select class="form-control exam-input" name="campus" id="campus" required style="border: none; border-left: 1px solid #e0e7ff; border-radius: 0 8px 8px 0;">
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
                            <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Exam Name <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm exam-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">quiz</span>
                                </span>
                                <input type="text" class="form-control exam-input" name="exam_name" id="exam_name" placeholder="Enter exam name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Exam Date <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm exam-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">calendar_today</span>
                                </span>
                                <input type="date" class="form-control exam-input" name="exam_date" id="exam_date" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Session <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm exam-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">event</span>
                                </span>
                                <select class="form-control exam-input" name="session" id="session" required style="border: none; border-left: 1px solid #e0e7ff; border-radius: 0 8px 8px 0;">
                                    <option value="">Select Session</option>
                                    @foreach($sessions as $sessionName)
                                        <option value="{{ $sessionName }}">{{ $sessionName }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Description</label>
                            <div class="input-group input-group-sm exam-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471; align-items: start; padding-top: 8px;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">description</span>
                                </span>
                                <textarea class="form-control exam-input" name="description" id="description" rows="3" placeholder="Enter description (optional)" style="border: none; border-left: 1px solid #e0e7ff; border-radius: 0 8px 8px 0; resize: vertical;"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer p-3" style="background-color: #f8f9fa; border-top: 1px solid #e9ecef;">
                    <button type="button" class="btn btn-sm py-2 px-4 rounded-8" data-bs-dismiss="modal" style="background-color: #6c757d; color: white; border: none; transition: all 0.3s ease;">
                        <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">close</span>
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-sm py-2 px-4 rounded-8 exam-submit-btn">
                        <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">save</span>
                        Save Exam
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    /* Exam Form Styling */
    #examModal .exam-input-group {
        border-radius: 8px;
        overflow: hidden;
        transition: all 0.3s ease;
        border: 1px solid #dee2e6;
    }
    
    #examModal .exam-input-group:focus-within {
        box-shadow: 0 0 0 3px rgba(0, 52, 113, 0.15);
        border-color: #003471;
    }
    
    #examModal .exam-input {
        font-size: 13px;
        padding: 0.35rem 0.65rem;
        height: 32px;
        border: none;
        border-left: 1px solid #e0e7ff;
        border-radius: 0 8px 8px 0;
        transition: all 0.3s ease;
    }
    
    #examModal .exam-input[type="date"],
    #examModal .exam-input select {
        height: 32px;
        padding: 0.35rem 0.65rem;
    }
    
    #examModal textarea.exam-input {
        min-height: 32px;
        padding: 0.35rem 0.65rem;
    }
    
    #examModal select.exam-input {
        appearance: none;
        -webkit-appearance: none;
        -moz-appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23003471' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 0.65rem center;
        padding-right: 2rem;
    }
    
    #examModal .exam-input:focus {
        border-left-color: #003471;
        box-shadow: none;
        outline: none;
    }
    
    #examModal .input-group-text {
        padding: 0 0.65rem;
        height: 32px;
        display: flex;
        align-items: center;
        border: none;
        border-right: 1px solid #e0e7ff;
        border-radius: 8px 0 0 8px;
        transition: all 0.3s ease;
    }
    
    #examModal .exam-input-group:focus-within .input-group-text {
        background-color: #003471 !important;
        color: white !important;
        border-right-color: #003471;
    }
    
    #examModal .form-label {
        margin-bottom: 0.4rem;
        line-height: 1.3;
    }
    
    #examModal .exam-submit-btn {
        background: linear-gradient(135deg, #003471 0%, #004a9f 100%);
        color: white;
        border: none;
        font-weight: 500;
        transition: all 0.3s ease;
        box-shadow: 0 2px 8px rgba(0, 52, 113, 0.25);
    }
    
    #examModal .exam-submit-btn:hover {
        background: linear-gradient(135deg, #004a9f 0%, #003471 100%);
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0, 52, 113, 0.35);
    }
    
    .exam-add-btn {
        background: linear-gradient(135deg, #003471 0%, #004a9f 100%);
        color: white;
        border: none;
        font-weight: 500;
        transition: all 0.3s ease;
        box-shadow: 0 2px 6px rgba(0, 52, 113, 0.2);
    }
    
    .exam-add-btn:hover {
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
    document.getElementById('examForm').reset();
    document.getElementById('methodField').innerHTML = '';
    document.getElementById('examModalLabel').innerHTML = '<span class="material-symbols-outlined" style="font-size: 20px; color: white;">quiz</span><span style="color: white;">Add New Exam</span>';
}

function editExam(id, campus, examName, description, examDate, session) {
    resetForm();
    document.getElementById('methodField').innerHTML = '<input type="hidden" name="_method" value="PUT">';
    document.getElementById('examForm').action = '{{ route("exam.list.update", ":id") }}'.replace(':id', id);
    document.getElementById('campus').value = campus;
    document.getElementById('exam_name').value = examName;
    document.getElementById('description').value = description;
    document.getElementById('exam_date').value = examDate;
    document.getElementById('session').value = session;
    document.getElementById('examModalLabel').innerHTML = '<span class="material-symbols-outlined" style="font-size: 20px; color: white;">edit</span><span style="color: white;">Edit Exam</span>';
    new bootstrap.Modal(document.getElementById('examModal')).show();
}

function updateEntriesPerPage(value) {
    const url = new URL(window.location.href);
    url.searchParams.set('per_page', value);
    if (document.getElementById('filter_campus')?.value) {
        url.searchParams.set('filter_campus', document.getElementById('filter_campus').value);
    }
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
    const filterCampusValue = document.getElementById('filter_campus')?.value || '';
    if (filterCampusValue) {
        url.searchParams.set('filter_campus', filterCampusValue);
    } else {
        url.searchParams.delete('filter_campus');
    }
    url.searchParams.delete('page');
    window.location.href = url.toString();
}

function clearSearch() {
    const url = new URL(window.location.href);
    url.searchParams.delete('search');
    const filterCampusValue = document.getElementById('filter_campus')?.value || '';
    if (filterCampusValue) {
        url.searchParams.set('filter_campus', filterCampusValue);
    } else {
        url.searchParams.delete('filter_campus');
    }
    url.searchParams.delete('page');
    window.location.href = url.toString();
}

function printTable() {
    window.print();
}

// Auto-dismiss toast notifications
document.addEventListener('DOMContentLoaded', function() {
    const campusFilter = document.getElementById('filter_campus');
    if (campusFilter) {
        campusFilter.addEventListener('change', function() {
            const url = new URL(window.location.href);
            if (this.value) {
                url.searchParams.set('filter_campus', this.value);
            } else {
                url.searchParams.delete('filter_campus');
            }
            url.searchParams.delete('page');
            window.location.href = url.toString();
        });
    }

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
