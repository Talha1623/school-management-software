@extends('layouts.app')

@section('title', 'Manage Subjects')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-3 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0 fs-16 fw-semibold">Manage Subjects</h4>
                <button type="button" class="btn btn-sm py-2 px-3 d-inline-flex align-items-center gap-1 rounded-8 subject-add-btn" data-bs-toggle="modal" data-bs-target="#subjectModal" onclick="resetForm()">
                    <span class="material-symbols-outlined" style="font-size: 16px;">add</span>
                    <span>Add Subject</span>
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

            <!-- Filter Section -->
            <div class="card border-0 shadow-sm mb-3" style="border-radius: 8px; overflow: hidden;">
                <div class="card-body p-3">
                    <form action="{{ route('manage-subjects') }}" method="GET" id="filterForm">
                        <div class="row g-3 align-items-end">
                            <!-- Campus Filter -->
                            <div class="col-md-3">
                                <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">
                                    Campus
                                </label>
                                <select class="form-select form-select-sm" name="filter_campus" id="filter_campus">
                                    <option value="">All Campus</option>
                                    @foreach($campuses as $campus)
                                        <option value="{{ $campus->campus_name ?? $campus }}" {{ request('filter_campus') == ($campus->campus_name ?? $campus) ? 'selected' : '' }}>{{ $campus->campus_name ?? $campus }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Class Filter -->
                            <div class="col-md-3">
                                <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">
                                    Class
                                </label>
                                <select class="form-select form-select-sm" name="filter_class" id="filter_class">
                                    <option value="">All Classes</option>
                                    @foreach($classes as $class)
                                        <option value="{{ $class->class_name ?? $class }}" {{ request('filter_class') == ($class->class_name ?? $class) ? 'selected' : '' }}>{{ $class->class_name ?? $class }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Section Filter -->
                            <div class="col-md-3">
                                <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">
                                    Section
                                </label>
                                <select class="form-select form-select-sm" name="filter_section" id="filter_section" disabled>
                                    <option value="">Select Class First</option>
                                </select>
                            </div>

                            <!-- Filter Button -->
                            <div class="col-md-3">
                                <button type="submit" class="btn btn-sm w-100 filter-btn">
                                    <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">filter_list</span>
                                    <span>Filter</span>
                                </button>
                            </div>
                        </div>
                        <!-- Preserve other query parameters -->
                        @if(request('per_page'))
                            <input type="hidden" name="per_page" value="{{ request('per_page') }}">
                        @endif
                    </form>
                </div>
            </div>

            <!-- Table Toolbar -->
            @if(request('filter_campus') || request('filter_class') || request('filter_section'))
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
                        <a href="{{ route('manage-subjects.export', ['format' => 'excel']) }}{{ request()->has('filter_campus') || request()->has('filter_class') || request()->has('filter_section') || request()->has('search') ? '?' . http_build_query(request()->only(['filter_campus', 'filter_class', 'filter_section', 'search'])) : '' }}" class="btn btn-sm px-2 py-1 export-btn excel-btn">
                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">description</span>
                            <span>Excel</span>
                        </a>
                        <a href="{{ route('manage-subjects.export', ['format' => 'csv']) }}{{ request()->has('filter_campus') || request()->has('filter_class') || request()->has('filter_section') || request()->has('search') ? '?' . http_build_query(request()->only(['filter_campus', 'filter_class', 'filter_section', 'search'])) : '' }}" class="btn btn-sm px-2 py-1 export-btn csv-btn">
                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">file_present</span>
                            <span>CSV</span>
                        </a>
                        <a href="{{ route('manage-subjects.export', ['format' => 'pdf']) }}{{ request()->has('filter_campus') || request()->has('filter_class') || request()->has('filter_section') || request()->has('search') ? '?' . http_build_query(request()->only(['filter_campus', 'filter_class', 'filter_section', 'search'])) : '' }}" class="btn btn-sm px-2 py-1 export-btn pdf-btn" target="_blank">
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
                            <input type="text" id="searchInput" class="form-control border-start-0 border-end-0" placeholder="Search subjects..." value="{{ request('search') }}" onkeypress="handleSearchKeyPress(event)" oninput="handleSearchInput(event)" style="padding: 4px 8px; font-size: 12px;">
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
            @endif

            <!-- Table Header -->
            @if(request('filter_campus') || request('filter_class') || request('filter_section'))
            <div class="mb-2 p-2 rounded-8" style="background: linear-gradient(135deg, #003471 0%, #004a9f 100%);">
                <h5 class="mb-0 text-white fs-15 fw-semibold d-flex align-items-center gap-2">
                    <span class="material-symbols-outlined" style="font-size: 18px;">list</span>
                    <span>Subjects List</span>
                </h5>
            </div>

            <!-- Filter Results Info -->
            <div class="search-results-info mb-3">
                @if(request('search'))
                    <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle; color: #003471;">search</span>
                    <strong>Search Results:</strong> Showing results for "<strong>{{ request('search') }}</strong>"
                    @if(isset($subjects))
                        ({{ $subjects->total() }} {{ Str::plural('result', $subjects->total()) }} found)
                    @endif
                    <a href="{{ route('manage-subjects', request()->only(['filter_campus', 'filter_class', 'filter_section', 'per_page'])) }}" class="text-decoration-none ms-2" style="color: #003471;">
                        <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">close</span>
                        Clear Search
                    </a>
                @else
                    <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle; color: #003471;">filter_list</span>
                    <strong>Filtered Results:</strong>
                    @if(request('filter_campus'))
                        Campus: <strong>{{ request('filter_campus') }}</strong>
                    @endif
                    @if(request('filter_class'))
                        {{ request('filter_campus') ? ' | ' : '' }}Class: <strong>{{ request('filter_class') }}</strong>
                    @endif
                    @if(request('filter_section'))
                        {{ (request('filter_campus') || request('filter_class')) ? ' | ' : '' }}Section: <strong>{{ request('filter_section') }}</strong>
                    @endif
                    @if(isset($subjects) && $subjects->total() > 0)
                        ({{ $subjects->total() }} {{ Str::plural('result', $subjects->total()) }} found)
                    @endif
                    <a href="{{ route('manage-subjects') }}" class="text-decoration-none ms-2" style="color: #003471;">
                        <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">close</span>
                        Clear All
                    </a>
                @endif
            </div>
            @endif

            <!-- Table -->
            @if(request('filter_campus') || request('filter_class') || request('filter_section'))
            <div class="default-table-area" style="margin-top: 0;">
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Campus</th>
                                <th>Class</th>
                                <th>Section</th>
                                <th>Subject Name</th>
                                <th>Teacher</th>
                                <th>Session</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if(isset($subjects) && $subjects->count() > 0)
                                @forelse($subjects as $subject)
                                    <tr>
                                        <td>{{ $loop->iteration + (($subjects->currentPage() - 1) * $subjects->perPage()) }}</td>
                                        <td>
                                            <span class="badge bg-primary text-white" style="font-size: 12px; padding: 4px 8px;">{{ $subject->campus }}</span>
                                        </td>
                                        <td>
                                            <span class="badge bg-success text-white" style="font-size: 12px; padding: 4px 8px;">{{ $subject->class }}</span>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary text-white" style="font-size: 12px; padding: 4px 8px;">{{ $subject->section }}</span>
                                        </td>
                                        <td>
                                            <strong class="text-dark">{{ $subject->subject_name }}</strong>
                                        </td>
                                        <td>
                                            <span class="text-muted">{{ $subject->teacher ?? 'N/A' }}</span>
                                        </td>
                                        <td>
                                            <span class="badge bg-warning text-dark" style="font-size: 12px; padding: 4px 8px;">{{ $subject->session ?? 'N/A' }}</span>
                                        </td>
                                        <td class="text-end">
                                            <div class="d-inline-flex gap-1 align-items-center">
                                                <button type="button" class="btn btn-sm btn-primary px-2 py-1" title="Edit" onclick="editSubject({{ $subject->id }}, '{{ addslashes($subject->campus) }}', '{{ addslashes($subject->subject_name) }}', '{{ addslashes($subject->class) }}', '{{ addslashes($subject->section) }}', '{{ addslashes($subject->teacher ?? '') }}', '{{ addslashes($subject->session ?? '') }}')">
                                                    <span class="material-symbols-outlined" style="font-size: 14px; color: white;">edit</span>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-danger px-2 py-1" title="Delete" onclick="if(confirm('Are you sure you want to delete this subject?')) { document.getElementById('delete-form-{{ $subject->id }}').submit(); }">
                                                    <span class="material-symbols-outlined" style="font-size: 14px; color: white;">delete</span>
                                                </button>
                                                <form id="delete-form-{{ $subject->id }}" action="{{ route('manage-subjects.destroy', $subject) }}" method="POST" class="d-none">
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
                                            <p class="mt-2 mb-0">No subjects found for the selected filters.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            @else
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-5">
                                        <span class="material-symbols-outlined" style="font-size: 48px; opacity: 0.3;">inbox</span>
                                        <p class="mt-2 mb-0">No subjects found for the selected filters.</p>
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>

            @if(isset($subjects) && $subjects->hasPages())
                <div class="mt-3">
                    {{ $subjects->links() }}
                </div>
            @endif
            @else
            <!-- Message when no filters applied -->
            <div class="text-center py-5">
                <span class="material-symbols-outlined" style="font-size: 64px; opacity: 0.3; color: #003471;">filter_list</span>
                <p class="mt-3 mb-0 fs-14 text-muted">Please select at least one filter (Campus, Class, or Section) to view subjects.</p>
            </div>
            @endif
        </div>
    </div>
</div>

<style>
    /* Filter Form Styling */
    .form-select-sm {
        font-size: 13px;
        padding: 6px 12px;
        border-radius: 6px;
        border: 1px solid #dee2e6;
        transition: all 0.3s ease;
        height: 32px;
    }
    
    .form-select-sm:focus {
        border-color: #003471;
        box-shadow: 0 0 0 3px rgba(0, 52, 113, 0.15);
        outline: none;
    }
    
    .filter-btn {
        background: linear-gradient(135deg, #003471 0%, #004a9f 100%);
        color: white;
        border: none;
        font-weight: 500;
        transition: all 0.3s ease;
        box-shadow: 0 2px 6px rgba(0, 52, 113, 0.2);
        height: 32px;
    }
    
    .filter-btn:hover {
        background: linear-gradient(135deg, #004a9f 0%, #003471 100%);
        transform: translateY(-1px);
        box-shadow: 0 4px 10px rgba(0, 52, 113, 0.3);
        color: white;
    }
    
    .filter-btn:active {
        transform: translateY(0);
    }
    
    .rounded-8 {
        border-radius: 8px;
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
        font-size: 14px;
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
        line-height: 1.4;
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
        font-size: 12px;
        padding: 4px 8px;
        font-weight: 600;
    }
    
    .default-table-area .material-symbols-outlined {
        font-size: 14px !important;
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

    /* Add New Button Styling */
    .subject-add-btn {
        background: linear-gradient(135deg, #003471 0%, #004a9f 100%);
        color: white;
        border: none;
        font-weight: 500;
        transition: all 0.3s ease;
        box-shadow: 0 2px 6px rgba(0, 52, 113, 0.2);
    }
    
    .subject-add-btn:hover {
        background: linear-gradient(135deg, #004a9f 0%, #003471 100%);
        transform: translateY(-1px);
        box-shadow: 0 4px 10px rgba(0, 52, 113, 0.3);
        color: white;
    }
    
    .subject-add-btn:active {
        transform: translateY(0);
    }

    /* Subject Form Styling */
    #subjectModal .subject-input-group {
        height: 36px;
        border-radius: 8px;
        overflow: hidden;
        transition: all 0.3s ease;
        border: 1px solid #dee2e6;
    }
    
    #subjectModal .subject-input-group:focus-within {
        box-shadow: 0 0 0 3px rgba(0, 52, 113, 0.15);
        border-color: #003471;
    }
    
    #subjectModal .subject-input {
        height: 36px;
        font-size: 13px;
        padding: 0.5rem 0.75rem;
        border: none;
        border-left: 1px solid #e0e7ff;
        border-radius: 0 8px 8px 0;
        transition: all 0.3s ease;
    }
    
    #subjectModal .subject-input:focus {
        border-left-color: #003471;
        box-shadow: none;
        outline: none;
    }
    
    #subjectModal .input-group-text {
        height: 36px;
        padding: 0 0.75rem;
        display: flex;
        align-items: center;
        border: none;
        border-right: 1px solid #e0e7ff;
        border-radius: 8px 0 0 8px;
        transition: all 0.3s ease;
    }
    
    #subjectModal .subject-input-group:focus-within .input-group-text {
        background-color: #003471 !important;
        color: white !important;
        border-right-color: #003471;
    }
    
    #subjectModal .form-label {
        margin-bottom: 0.4rem;
        line-height: 1.3;
    }
    
    #subjectModal .subject-submit-btn {
        background: linear-gradient(135deg, #003471 0%, #004a9f 100%);
        color: white;
        border: none;
        font-weight: 500;
        transition: all 0.3s ease;
        box-shadow: 0 2px 8px rgba(0, 52, 113, 0.25);
    }
    
    #subjectModal .subject-submit-btn:hover {
        background: linear-gradient(135deg, #004a9f 0%, #003471 100%);
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0, 52, 113, 0.35);
    }
    
    #subjectModal .subject-submit-btn:active {
        transform: translateY(0);
    }
</style>

<!-- Subject Modal -->
<div class="modal fade" id="subjectModal" tabindex="-1" aria-labelledby="subjectModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
            <div class="modal-header text-white p-3" style="background: linear-gradient(135deg, #003471 0%, #004a9f 100%); border: none;">
                <h5 class="modal-title fs-15 fw-semibold mb-0 d-flex align-items-center gap-2" id="subjectModalLabel" style="color: white;">
                    <span class="material-symbols-outlined" style="font-size: 20px; color: white;">book</span>
                    <span style="color: white;">Add New Subject</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" style="opacity: 0.8;"></button>
            </div>
            <form id="subjectForm" method="POST" action="{{ route('manage-subjects.store') }}">
                @csrf
                <div id="methodField"></div>
                <div class="modal-body p-3">
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Campus <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm subject-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">location_on</span>
                                </span>
                                <select class="form-select subject-input" name="campus" id="campus" required style="border: none; border-left: 1px solid #e0e7ff;">
                                    <option value="">Select Campus</option>
                                    @foreach($campuses as $campus)
                                        <option value="{{ $campus->campus_name ?? $campus }}">{{ $campus->campus_name ?? $campus }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Name <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm subject-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">label</span>
                                </span>
                                <input type="text" class="form-control subject-input" name="subject_name" id="subject_name" placeholder="Enter subject name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Class <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm subject-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">school</span>
                                </span>
                                <select class="form-select subject-input" name="class" id="class" required style="border: none; border-left: 1px solid #e0e7ff;">
                                    <option value="">Select Class</option>
                                    @foreach($classes as $class)
                                        <option value="{{ $class->class_name ?? $class }}">{{ $class->class_name ?? $class }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Section <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm subject-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">group</span>
                                </span>
                                <select class="form-select subject-input" name="section" id="section" required style="border: none; border-left: 1px solid #e0e7ff;" disabled>
                                    <option value="">Select Class First</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Teacher</label>
                            <div class="input-group input-group-sm subject-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">person</span>
                                </span>
                                <select class="form-select subject-input" name="teacher" id="teacher" style="border: none; border-left: 1px solid #e0e7ff;">
                                    <option value="">Select Teacher</option>
                                    @foreach($teachers as $id => $teacherName)
                                        <option value="{{ $teacherName }}">{{ $teacherName }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Session</label>
                            <div class="input-group input-group-sm subject-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">calendar_today</span>
                                </span>
                                <select class="form-select subject-input" name="session" id="session" style="border: none; border-left: 1px solid #e0e7ff;">
                                    <option value="">Select Session</option>
                                    @foreach($allSessions as $session)
                                        <option value="{{ $session }}">{{ $session }}</option>
                                    @endforeach
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
                    <button type="submit" class="btn btn-sm py-2 px-4 rounded-8 subject-submit-btn">
                        <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">save</span>
                        Save Subject
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // For filter form
    const filterClassSelect = document.getElementById('filter_class');
    const filterSectionSelect = document.getElementById('filter_section');
    
    // For modal form
    const modalClassSelect = document.getElementById('class');
    const modalSectionSelect = document.getElementById('section');
    
    // Function to load sections for filter form
    function loadFilterSections(className) {
        if (!className) {
            filterSectionSelect.innerHTML = '<option value="">Select Class First</option>';
            filterSectionSelect.disabled = true;
            return;
        }
        
        filterSectionSelect.innerHTML = '<option value="">Loading sections...</option>';
        filterSectionSelect.disabled = true;
        
        fetch(`{{ route('manage-subjects.get-sections-by-class') }}?class=${encodeURIComponent(className)}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            filterSectionSelect.innerHTML = '<option value="">All Sections</option>';
            
            if (data.sections && data.sections.length > 0) {
                data.sections.forEach(section => {
                    const option = document.createElement('option');
                    option.value = section.name;
                    option.textContent = section.name;
                    // Preserve selected value if exists
                    @if(request('filter_section'))
                        if (option.value === '{{ request('filter_section') }}') {
                            option.selected = true;
                        }
                    @endif
                    filterSectionSelect.appendChild(option);
                });
                filterSectionSelect.disabled = false;
            } else {
                filterSectionSelect.innerHTML = '<option value="">No sections found</option>';
            }
        })
        .catch(error => {
            console.error('Error fetching sections:', error);
            filterSectionSelect.innerHTML = '<option value="">Error loading sections</option>';
        });
    }
    
    // Function to load sections for modal form
    function loadModalSections(className) {
        if (!className) {
            modalSectionSelect.innerHTML = '<option value="">Select Class First</option>';
            modalSectionSelect.disabled = true;
            return;
        }
        
        modalSectionSelect.innerHTML = '<option value="">Loading sections...</option>';
        modalSectionSelect.disabled = true;
        
        fetch(`{{ route('manage-subjects.get-sections-by-class') }}?class=${encodeURIComponent(className)}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            modalSectionSelect.innerHTML = '<option value="">Select Section</option>';
            
            if (data.sections && data.sections.length > 0) {
                data.sections.forEach(section => {
                    const option = document.createElement('option');
                    option.value = section.name;
                    option.textContent = section.name;
                    modalSectionSelect.appendChild(option);
                });
                modalSectionSelect.disabled = false;
            } else {
                modalSectionSelect.innerHTML = '<option value="">No sections found</option>';
            }
        })
        .catch(error => {
            console.error('Error fetching sections:', error);
            modalSectionSelect.innerHTML = '<option value="">Error loading sections</option>';
        });
    }
    
    // Filter form class change handler
    if (filterClassSelect) {
        // Load sections on page load if class is already selected
        @if(request('filter_class'))
            loadFilterSections('{{ request('filter_class') }}');
        @endif
        
        filterClassSelect.addEventListener('change', function() {
            loadFilterSections(this.value);
        });
    }
    
    // Modal form class change handler
    if (modalClassSelect) {
        modalClassSelect.addEventListener('change', function() {
            loadModalSections(this.value);
        });
    }
});

// Reset form when opening modal for new subject
function resetForm() {
    document.getElementById('subjectForm').reset();
    document.getElementById('subjectForm').action = "{{ route('manage-subjects.store') }}";
    document.getElementById('methodField').innerHTML = '';
    const modalLabel = document.getElementById('subjectModalLabel');
    modalLabel.innerHTML = `
        <span class="material-symbols-outlined" style="font-size: 20px; color: white;">book</span>
        <span style="color: white;">Add New Subject</span>
    `;
    document.querySelector('.subject-submit-btn').innerHTML = `
        <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">save</span>
        Save Subject
    `;
    
    // Reset section dropdown
    const modalSectionSelect = document.getElementById('section');
    if (modalSectionSelect) {
        modalSectionSelect.innerHTML = '<option value="">Select Class First</option>';
        modalSectionSelect.disabled = true;
    }
}

// Edit subject function
function editSubject(id, campus, subjectName, className, section, teacher, session) {
    document.getElementById('campus').value = campus;
    document.getElementById('subject_name').value = subjectName;
    document.getElementById('class').value = className;
    document.getElementById('teacher').value = teacher || '';
    document.getElementById('session').value = session || '';
    
    // Load sections for the class
    const modalSectionSelect = document.getElementById('section');
    if (className) {
        fetch(`{{ route('manage-subjects.get-sections-by-class') }}?class=${encodeURIComponent(className)}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            modalSectionSelect.innerHTML = '<option value="">Select Section</option>';
            
            if (data.sections && data.sections.length > 0) {
                data.sections.forEach(sec => {
                    const option = document.createElement('option');
                    option.value = sec.name;
                    option.textContent = sec.name;
                    if (option.value === section) {
                        option.selected = true;
                    }
                    modalSectionSelect.appendChild(option);
                });
                modalSectionSelect.disabled = false;
            } else {
                modalSectionSelect.innerHTML = '<option value="">No sections found</option>';
            }
        })
        .catch(error => {
            console.error('Error fetching sections:', error);
            modalSectionSelect.innerHTML = '<option value="">Error loading sections</option>';
        });
    }
    
    document.getElementById('subjectForm').action = "{{ url('manage-subjects') }}/" + id;
    document.getElementById('methodField').innerHTML = '@method("PUT")';
    const modalLabel = document.getElementById('subjectModalLabel');
    modalLabel.innerHTML = `
        <span class="material-symbols-outlined" style="font-size: 20px; color: white;">edit</span>
        <span style="color: white;">Edit Subject</span>
    `;
    document.querySelector('.subject-submit-btn').innerHTML = `
        <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">save</span>
        Update Subject
    `;
    
    const modal = new bootstrap.Modal(document.getElementById('subjectModal'));
    modal.show();
}

// Update entries per page
function updateEntriesPerPage(value) {
    const url = new URL(window.location.href);
    url.searchParams.set('per_page', value);
    url.searchParams.set('page', '1');
    window.location.href = url.toString();
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

// Print table
function printTable() {
    const printContents = document.querySelector('.default-table-area').innerHTML;
    const originalContents = document.body.innerHTML;
    
    document.body.innerHTML = `
        <div style="padding: 20px;">
            <h3 style="text-align: center; margin-bottom: 20px; color: #003471;">Subjects List</h3>
            ${printContents}
        </div>
    `;
    
    window.print();
    document.body.innerHTML = originalContents;
    window.location.reload();
}
</script>
@endsection
