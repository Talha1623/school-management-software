@extends('layouts.app')

@section('title', 'Student Information')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-3 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0 fs-16 fw-semibold">Student Information</h4>
            </div>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <!-- Filter Form -->
            <form method="GET" action="{{ route('student.information') }}" class="mb-3">
                <div class="row g-2 align-items-end">
                    <div class="col-md-2">
                        <label for="filter_campus" class="form-label mb-0 fs-13 fw-medium">Campus</label>
                        <select class="form-select form-select-sm" id="filter_campus" name="filter_campus" style="height: 32px;">
                            <option value="">All Campuses</option>
                            @foreach($campuses as $campus)
                                <option value="{{ $campus }}" {{ request('filter_campus') == $campus ? 'selected' : '' }}>{{ $campus }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="filter_class" class="form-label mb-0 fs-13 fw-medium">Class</label>
                        <select class="form-select form-select-sm" id="filter_class" name="filter_class" style="height: 32px;">
                            <option value="">All Classes</option>
                            @foreach($classes as $class)
                                <option value="{{ $class }}" {{ request('filter_class') == $class ? 'selected' : '' }}>{{ $class }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="filter_section" class="form-label mb-0 fs-13 fw-medium">Section</label>
                        <select class="form-select form-select-sm" id="filter_section" name="filter_section" style="height: 32px;">
                            <option value="">All Sections</option>
                            @foreach($sections as $section)
                                <option value="{{ $section }}" {{ request('filter_section') == $section ? 'selected' : '' }}>{{ $section }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="filter_type" class="form-label mb-0 fs-13 fw-medium">Type</label>
                        <select class="form-select form-select-sm" id="filter_type" name="filter_type" style="height: 32px;">
                            <option value="">All Types</option>
                            @foreach($types as $type)
                                <option value="{{ $type }}" {{ request('filter_type') == $type ? 'selected' : '' }}>{{ ucfirst($type) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-sm w-100" style="background: linear-gradient(135deg, #003471 0%, #004a9f 100%); color: white; height: 32px; border: none;">
                            <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">filter_list</span>
                            Filter
                        </button>
                    </div>
                    @if(request('filter_campus') || request('filter_class') || request('filter_section') || request('filter_type'))
                    <div class="col-md-2">
                        <a href="{{ route('student.information') }}" class="btn btn-sm btn-outline-secondary w-100" style="height: 32px;">
                            <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">clear</span>
                            Clear
                        </a>
                    </div>
                    @endif
                </div>
                <!-- Preserve search and per_page in filter form -->
                @if(request('search'))
                    <input type="hidden" name="search" value="{{ request('search') }}">
                @endif
                @if(request('per_page'))
                    <input type="hidden" name="per_page" value="{{ request('per_page') }}">
                @endif
            </form>

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
                    <!-- Export Buttons - Only show when filters are applied -->
                    @if(request('filter_campus') || request('filter_class') || request('filter_section') || request('filter_type'))
                    <div class="d-flex gap-2">
                        <a href="{{ route('student.information.export', ['format' => 'excel']) }}?{{ http_build_query(request()->except(['page', 'per_page'])) }}" class="btn btn-sm px-2 py-1 export-btn excel-btn">
                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">description</span>
                            <span>Excel</span>
                        </a>
                        <a href="{{ route('student.information.export', ['format' => 'pdf']) }}?{{ http_build_query(request()->except(['page', 'per_page'])) }}" class="btn btn-sm px-2 py-1 export-btn pdf-btn" target="_blank">
                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">picture_as_pdf</span>
                            <span>PDF</span>
                        </a>
                        <form action="{{ route('student.information.delete-all') }}?{{ http_build_query(request()->except(['page', 'per_page'])) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete ALL filtered students? This action cannot be undone!');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm px-2 py-1 export-btn delete-all-btn">
                                <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">delete_sweep</span>
                                <span>Delete All</span>
                            </button>
                        </form>
                    </div>
                    @endif
                    
                    <!-- Search -->
                    <div class="d-flex align-items-center gap-2">
                        <label for="searchInput" class="mb-0 fs-13 fw-medium text-dark">Search:</label>
                        <div class="input-group input-group-sm search-input-group" style="width: 280px;">
                            <span class="input-group-text bg-light border-end-0" style="background-color: #f0f4ff !important; border-color: #e0e7ff; padding: 4px 8px;">
                                <span class="material-symbols-outlined" style="font-size: 14px; color: #003471;">search</span>
                            </span>
                            <input type="text" id="searchInput" class="form-control border-start-0 border-end-0" placeholder="Search by name, code, class..." value="{{ request('search') }}" onkeypress="handleSearchKeyPress(event)" oninput="handleSearchInput(event)" style="padding: 4px 8px; font-size: 13px;">
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

            <!-- Table Header and Table - Only show when filters are applied -->
            @if(request('filter_campus') || request('filter_class') || request('filter_section') || request('filter_type'))
            <div class="mb-2 p-2 rounded-8" style="background: linear-gradient(135deg, #003471 0%, #004a9f 100%);">
                <h5 class="mb-0 text-white fs-15 fw-semibold d-flex align-items-center gap-2">
                    <span class="material-symbols-outlined" style="font-size: 18px;">list</span>
                    <span>Students List</span>
                    <span class="badge bg-light text-dark ms-2">
                        {{ $students->total() }} {{ Str::plural('student', $students->total()) }} found
                    </span>
                </h5>
            </div>

            <!-- Search Results Info -->
            @if(request('search'))
                <div class="search-results-info">
                    <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle; color: #003471;">search</span>
                    <strong>Search Results:</strong> Showing results for "<strong>{{ request('search') }}</strong>" 
                    ({{ $students->total() }} {{ Str::plural('result', $students->total()) }} found)
                    <a href="{{ route('student.information') }}" class="text-decoration-none ms-2" style="color: #003471;">
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
                                <th style="padding: 8px 12px; font-size: 13px;">#</th>
                                <th style="padding: 8px 12px; font-size: 13px;">Student Name</th>
                                <th style="padding: 8px 12px; font-size: 13px;">Student Code</th>
                                <th style="padding: 8px 12px; font-size: 13px;">Father Name</th>
                                <th style="padding: 8px 12px; font-size: 13px;">Phone</th>
                                <th style="padding: 8px 12px; font-size: 13px;">Class</th>
                                <th style="padding: 8px 12px; font-size: 13px;">Section</th>
                                <th style="padding: 8px 12px; font-size: 13px;">Gender</th>
                                <th style="padding: 8px 12px; font-size: 13px;">Date of Birth</th>
                                <th style="padding: 8px 12px; font-size: 13px;">Admission Date</th>
                                <th style="padding: 8px 12px; font-size: 13px; text-align: center;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($students as $student)
                                <tr>
                                    <td style="padding: 8px 12px; font-size: 13px;">{{ $loop->iteration + (($students->currentPage() - 1) * $students->perPage()) }}</td>
                                    <td style="padding: 8px 12px; font-size: 13px;">
                                        <strong class="text-primary">{{ $student->student_name }}</strong>
                                        @if($student->surname_caste)
                                            <span class="text-muted">({{ $student->surname_caste }})</span>
                                        @endif
                                    </td>
                                    <td style="padding: 8px 12px; font-size: 13px;">
                                        @if($student->student_code)
                                            <span class="badge bg-info text-white" style="font-size: 11px;">{{ $student->student_code }}</span>
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td style="padding: 8px 12px; font-size: 13px;">{{ $student->father_name ?? 'N/A' }}</td>
                                    <td style="padding: 8px 12px; font-size: 13px;">
                                        <span class="badge bg-light text-dark" style="font-size: 11px;">
                                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">phone</span>
                                            {{ $student->father_phone ?? $student->whatsapp_number ?? 'N/A' }}
                                        </span>
                                    </td>
                                    <td style="padding: 8px 12px; font-size: 13px;">
                                        <span class="badge bg-primary text-white" style="font-size: 11px;">{{ $student->class ?? 'N/A' }}</span>
                                    </td>
                                    <td style="padding: 8px 12px; font-size: 13px;">
                                        @if($student->section)
                                            <span class="badge bg-secondary text-white" style="font-size: 11px;">{{ $student->section }}</span>
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td style="padding: 8px 12px; font-size: 13px;">
                                        @php
                                            $genderClass = match($student->gender) {
                                                'male' => 'bg-info',
                                                'female' => 'bg-danger',
                                                default => 'bg-secondary'
                                            };
                                        @endphp
                                        <span class="badge {{ $genderClass }} text-white text-capitalize" style="font-size: 11px;">
                                            {{ $student->gender ?? 'N/A' }}
                                        </span>
                                    </td>
                                    <td style="padding: 8px 12px; font-size: 13px;">
                                        <span class="text-muted">
                                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">calendar_today</span>
                                            {{ $student->date_of_birth ? $student->date_of_birth->format('d M Y') : 'N/A' }}
                                        </span>
                                    </td>
                                    <td style="padding: 8px 12px; font-size: 13px;">
                                        <span class="text-muted">
                                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">event</span>
                                            {{ $student->admission_date ? $student->admission_date->format('d M Y') : 'N/A' }}
                                        </span>
                                    </td>
                                    <td style="padding: 8px 12px; font-size: 13px; text-align: center;">
                                        <button type="button" class="btn btn-sm btn-primary px-2 py-1" onclick="viewStudent({{ $student->id }})" title="View Details">
                                            <span class="material-symbols-outlined" style="font-size: 14px; color: white;">visibility</span>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="11" class="text-center text-muted py-5">
                                        <span class="material-symbols-outlined" style="font-size: 48px; opacity: 0.3;">school</span>
                                        <p class="mt-2 mb-0">No students found.</p>
                                        <p class="mt-1 mb-0" style="font-size: 13px;">Admit students from Admission Management → Admit Student</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if($students->hasPages())
                <div class="mt-3">
                    {{ $students->links() }}
                </div>
            @endif
            @else
            <!-- Message when no filters applied -->
            <div class="text-center py-5">
                <span class="material-symbols-outlined" style="font-size: 64px; color: #dee2e6; opacity: 0.5;">filter_list</span>
                <h5 class="mt-3 text-muted">Apply Filters to View Students</h5>
                <p class="text-muted mb-0">Please select Campus, Class, Section, or Type and click Filter to view students list.</p>
            </div>
            @endif
        </div>
    </div>
</div>

<style>
    .rounded-8 {
        border-radius: 8px;
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
        font-size: 13px;
        padding: 5px 10px;
        font-weight: 500;
    }
    
    .default-table-area .material-symbols-outlined {
        font-size: 16px !important;
    }
</style>

<script>
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

// Function to load sections based on class
function loadSectionsForFilter(selectedClass, selectedSection = null) {
    const sectionSelect = document.getElementById('filter_section');
    
    // Clear existing options except the first one
    sectionSelect.innerHTML = '<option value="">All Sections</option>';
    
    if (selectedClass) {
        // Fetch sections via AJAX
        fetch(`{{ route('admission.get-sections') }}?class=${encodeURIComponent(selectedClass)}`)
            .then(response => response.json())
            .then(data => {
                if (data.sections && data.sections.length > 0) {
                    data.sections.forEach(section => {
                        const option = document.createElement('option');
                        option.value = section;
                        option.textContent = section;
                        // Preserve selected value if it matches
                        if (selectedSection && selectedSection === section) {
                            option.selected = true;
                        }
                        sectionSelect.appendChild(option);
                    });
                }
            })
            .catch(error => {
                console.error('Error loading sections:', error);
            });
    }
}

// Load sections based on class selection in filter
document.getElementById('filter_class').addEventListener('change', function() {
    loadSectionsForFilter(this.value);
});

// Load sections on page load if class is already selected
document.addEventListener('DOMContentLoaded', function() {
    const selectedClass = document.getElementById('filter_class').value;
    const selectedSection = '{{ request('filter_section') }}';
    if (selectedClass) {
        loadSectionsForFilter(selectedClass, selectedSection);
    }
});

// View student details
function viewStudent(studentId) {
    window.location.href = '{{ route("student.view", ":id") }}'.replace(':id', studentId);
}
</script>

<style>
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
    
    .export-btn:active {
        transform: translateY(0);
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
</style>
@endsection
