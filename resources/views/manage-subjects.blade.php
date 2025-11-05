@extends('layouts.app')

@section('title', 'Manage Subjects')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-3 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0 fs-16 fw-semibold">Manage Subjects</h4>
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
                                    <option value="">Select Campus</option>
                                    @foreach($campuses as $campus)
                                        <option value="{{ $campus }}" {{ request('filter_campus') == $campus ? 'selected' : '' }}>{{ $campus }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Class Filter -->
                            <div class="col-md-3">
                                <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">
                                    Class
                                </label>
                                <select class="form-select form-select-sm" name="filter_class" id="filter_class">
                                    <option value="">Select Class</option>
                                    @foreach($classes as $class)
                                        <option value="{{ $class }}" {{ request('filter_class') == $class ? 'selected' : '' }}>{{ $class }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Section Filter -->
                            <div class="col-md-3">
                                <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">
                                    Section
                                </label>
                                <select class="form-select form-select-sm" name="filter_section" id="filter_section">
                                    <option value="">Select Section</option>
                                    @foreach($sections as $section)
                                        <option value="{{ $section }}" {{ request('filter_section') == $section ? 'selected' : '' }}>{{ $section }}</option>
                                    @endforeach
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
            </div>
            @endif

            <!-- Table -->
            @if(request('filter_campus') || request('filter_class') || request('filter_section'))
            <div class="default-table-area" style="margin-top: 0;">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Campus</th>
                                <th>Class</th>
                                <th>Section</th>
                                <th>Subject Name</th>
                                <th>Teacher</th>
                                <th>Session</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if(isset($subjects) && $subjects->count() > 0)
                                @forelse($subjects as $subject)
                                    <tr>
                                        <td>{{ $loop->iteration + (($subjects->currentPage() - 1) * $subjects->perPage()) }}</td>
                                        <td>
                                            <span class="badge bg-info text-white">{{ $subject->campus }}</span>
                                        </td>
                                        <td>
                                            <span class="badge bg-success text-white">{{ $subject->class }}</span>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary text-white">{{ $subject->section }}</span>
                                        </td>
                                        <td>
                                            <strong class="text-primary">{{ $subject->subject_name }}</strong>
                                        </td>
                                        <td>
                                            <span class="text-muted">{{ $subject->teacher ?? 'N/A' }}</span>
                                        </td>
                                        <td>
                                            <span class="badge bg-warning text-dark">{{ $subject->session ?? 'N/A' }}</span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-5">
                                            <span class="material-symbols-outlined" style="font-size: 48px; opacity: 0.3;">inbox</span>
                                            <p class="mt-2 mb-0">No subjects found for the selected filters.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            @else
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-5">
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
</style>

<script>
// Update entries per page
function updateEntriesPerPage(value) {
    const url = new URL(window.location.href);
    url.searchParams.set('per_page', value);
    url.searchParams.set('page', '1');
    window.location.href = url.toString();
}
</script>
@endsection
