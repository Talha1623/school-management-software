@extends('layouts.app')

@section('title', 'Student List')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-3 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0 fs-16 fw-semibold">Student List</h4>
            </div>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <!-- Filter Form -->
            <form method="GET" action="{{ route('student-list') }}" class="mb-3" id="filterForm">
                <div class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label for="filter_campus" class="form-label mb-0 fs-13 fw-medium">Campus</label>
                        <div class="position-relative">
                            <select class="form-select form-select-sm" id="filter_campus" name="filter_campus" style="height: 32px; padding-right: {{ request('filter_campus') ? '30px' : '12px' }};">
                                <option value="">All Campuses</option>
                                @foreach($campuses as $campus)
                                    @php
                                        $campusName = is_object($campus) ? ($campus->campus_name ?? '') : $campus;
                                    @endphp
                                    <option value="{{ $campusName }}" {{ request('filter_campus') == $campusName ? 'selected' : '' }}>{{ $campusName }}</option>
                                @endforeach
                            </select>
                            @if(request('filter_campus'))
                                <button type="button" class="btn btn-sm position-absolute" onclick="clearFilter('filter_campus')" style="right: 25px; top: 50%; transform: translateY(-50%); padding: 0; width: 20px; height: 20px; background: transparent; border: none; color: #dc3545; z-index: 10;" title="Clear Campus">
                                    <span class="material-symbols-outlined" style="font-size: 16px;">close</span>
                                </button>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label for="filter_class" class="form-label mb-0 fs-13 fw-medium">Class</label>
                        <div class="position-relative">
                            <select class="form-select form-select-sm" id="filter_class" name="filter_class" style="height: 32px; padding-right: {{ request('filter_class') ? '30px' : '12px' }};">
                                <option value="">All Classes</option>
                                @foreach($classes as $class)
                                    <option value="{{ $class }}" {{ request('filter_class') == $class ? 'selected' : '' }}>{{ $class }}</option>
                                @endforeach
                            </select>
                            @if(request('filter_class'))
                                <button type="button" class="btn btn-sm position-absolute" onclick="clearFilter('filter_class')" style="right: 25px; top: 50%; transform: translateY(-50%); padding: 0; width: 20px; height: 20px; background: transparent; border: none; color: #dc3545; z-index: 10;" title="Clear Class">
                                    <span class="material-symbols-outlined" style="font-size: 16px;">close</span>
                                </button>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label for="filter_section" class="form-label mb-0 fs-13 fw-medium">Section</label>
                        <div class="position-relative">
                            <select class="form-select form-select-sm" id="filter_section" name="filter_section" style="height: 32px; padding-right: {{ request('filter_section') ? '30px' : '12px' }};">
                                <option value="">All Sections</option>
                                @foreach($sections as $section)
                                    <option value="{{ $section }}" {{ request('filter_section') == $section ? 'selected' : '' }}>{{ $section }}</option>
                                @endforeach
                            </select>
                            @if(request('filter_section'))
                                <button type="button" class="btn btn-sm position-absolute" onclick="clearFilter('filter_section')" style="right: 25px; top: 50%; transform: translateY(-50%); padding: 0; width: 20px; height: 20px; background: transparent; border: none; color: #dc3545; z-index: 10;" title="Clear Section">
                                    <span class="material-symbols-outlined" style="font-size: 16px;">close</span>
                                </button>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-2">
                        <label for="filter_type" class="form-label mb-0 fs-13 fw-medium">Type</label>
                        <div class="position-relative">
                            <select class="form-select form-select-sm" id="filter_type" name="filter_type" style="height: 32px; padding-right: {{ request('filter_type') ? '30px' : '12px' }};">
                                <option value="">All Types</option>
                                @foreach($types as $key => $label)
                                    <option value="{{ $key }}" {{ request('filter_type') == $key ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            @if(request('filter_type'))
                                <button type="button" class="btn btn-sm position-absolute" onclick="clearFilter('filter_type')" style="right: 25px; top: 50%; transform: translateY(-50%); padding: 0; width: 20px; height: 20px; background: transparent; border: none; color: #dc3545; z-index: 10;" title="Clear Type">
                                    <span class="material-symbols-outlined" style="font-size: 16px;">close</span>
                                </button>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-1">
                        <button type="submit" class="btn btn-sm w-100" style="background: linear-gradient(135deg, #003471 0%, #004a9f 100%); color: white; height: 32px; border: none;">
                            <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">filter_list</span>
                            Filter
                        </button>
                    </div>
                </div>
                <!-- Preserve search and per_page in filter form -->
                @if(request('search'))
                    <input type="hidden" name="search" value="{{ request('search') }}">
                @endif
                @if(request('per_page'))
                    <input type="hidden" name="per_page" value="{{ request('per_page') }}">
                @endif
            </form>

            <!-- Table Toolbar - Only show when filters are applied -->
            @if(request('filter_campus') || request('filter_class') || request('filter_section') || request('filter_type'))
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-3 p-3 rounded-8" style="background-color: #f8f9fa; border: 1px solid #e9ecef;">
                <!-- Left Side -->
                <div class="d-flex align-items-center gap-3 flex-wrap">
                    <div class="d-flex align-items-center gap-2">
                        <label for="entriesPerPage" class="mb-0 fs-13 fw-medium text-dark">Show:</label>
                        <select id="entriesPerPage" class="form-select form-select-sm" style="width: auto; min-width: 70px; height: 32px;" onchange="updateEntriesPerPage(this.value)">
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
                            <span class="input-group-text bg-light border-end-0" style="background-color: #f0f4ff !important; border-color: #e0e7ff; padding: 4px 8px; height: 32px;">
                                <span class="material-symbols-outlined" style="font-size: 14px; color: #003471;">search</span>
                            </span>
                            <input type="text" id="searchInput" class="form-control border-start-0 border-end-0" placeholder="Search by name, code, class..." value="{{ request('search') }}" onkeypress="handleSearchKeyPress(event)" oninput="handleSearchInput(event)" style="padding: 4px 8px; font-size: 13px; height: 32px;">
                            @if(request('search'))
                                <button class="btn btn-outline-secondary border-start-0 border-end-0" type="button" onclick="clearSearch()" title="Clear search" style="padding: 4px 8px; height: 32px;">
                                    <span class="material-symbols-outlined" style="font-size: 14px;">close</span>
                                </button>
                            @endif
                            <button class="btn btn-sm search-btn" type="button" onclick="performSearch()" title="Search" style="padding: 4px 10px; height: 32px;">
                                <span class="material-symbols-outlined" style="font-size: 14px;">search</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            @endif

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
                    <a href="{{ route('student-list') }}?{{ http_build_query(request()->except(['search', 'page'])) }}" class="text-decoration-none ms-2" style="color: #003471;">
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
                                <th style="padding: 8px 12px; font-size: 13px;">Class</th>
                                <th style="padding: 8px 12px; font-size: 13px;">Section</th>
                                <th style="padding: 8px 12px; font-size: 13px;">Gender</th>
                                <th style="padding: 8px 12px; font-size: 13px;">Admission Date</th>
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
                                            $genderClass = match(strtolower($student->gender ?? '')) {
                                                'male' => 'bg-info',
                                                'female' => 'bg-danger',
                                                default => 'bg-secondary'
                                            };
                                        @endphp
                                        <span class="badge {{ $genderClass }} text-white text-capitalize" style="font-size: 11px;">
                                            {{ ucfirst($student->gender ?? 'N/A') }}
                                        </span>
                                    </td>
                                    <td style="padding: 8px 12px; font-size: 13px;">
                                        <span class="text-muted">
                                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">event</span>
                                            {{ $student->admission_date ? $student->admission_date->format('d M Y') : 'N/A' }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-5">
                                        <span class="material-symbols-outlined" style="font-size: 48px; opacity: 0.3;">school</span>
                                        <p class="mt-2 mb-0">No students found.</p>
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
                <p class="text-muted mb-0">Please select Campus, Class/Section, or Type and click Filter to view students list.</p>
            </div>
            @endif
        </div>
    </div>
</div>

<style>
    .rounded-8 {
        border-radius: 8px;
    }
    
    .search-results-info {
        padding: 8px 12px;
        background-color: #f0f4ff;
        border-left: 3px solid #003471;
        margin-bottom: 12px;
        font-size: 13px;
        border-radius: 4px;
    }
    
    .search-input-group .form-control:focus {
        border-color: #003471;
        box-shadow: 0 0 0 0.2rem rgba(0, 52, 113, 0.15);
    }
    
    .search-btn {
        background: linear-gradient(135deg, #003471 0%, #004a9f 100%);
        color: white;
        border: none;
    }
    
    .search-btn:hover {
        background: linear-gradient(135deg, #004a9f 0%, #003471 100%);
        color: white;
    }
    
    .table th {
        background-color: #f8f9fa;
        font-weight: 600;
        border-bottom: 2px solid #dee2e6;
    }
    
    .table td {
        vertical-align: middle;
    }
    
    .table-hover tbody tr:hover {
        background-color: #f8f9fa;
    }
</style>

<script>
function updateEntriesPerPage(value) {
    const url = new URL(window.location.href);
    url.searchParams.set('per_page', value);
    url.searchParams.delete('page'); // Reset to first page
    window.location.href = url.toString();
}

function performSearch() {
    const searchValue = document.getElementById('searchInput').value.trim();
    const url = new URL(window.location.href);
    
    if (searchValue) {
        url.searchParams.set('search', searchValue);
    } else {
        url.searchParams.delete('search');
    }
    url.searchParams.delete('page'); // Reset to first page
    
    window.location.href = url.toString();
}

function handleSearchKeyPress(event) {
    if (event.key === 'Enter') {
        event.preventDefault();
        performSearch();
    }
}

function handleSearchInput(event) {
    // Optional: Auto-search on input (debounced)
    // For now, just allow manual search on button click or Enter
}

function clearSearch() {
    const url = new URL(window.location.href);
    url.searchParams.delete('search');
    url.searchParams.delete('page');
    window.location.href = url.toString();
}

function clearFilter(filterName) {
    const url = new URL(window.location.href);
    url.searchParams.delete(filterName);
    url.searchParams.delete('page');
    
    // If clearing class, also clear section
    if (filterName === 'filter_class') {
        url.searchParams.delete('filter_section');
        document.getElementById('filter_section').innerHTML = '<option value="">All Sections</option>';
    }
    
    window.location.href = url.toString();
}

// Load sections when class changes
document.getElementById('filter_class')?.addEventListener('change', function() {
    const classValue = this.value;
    const sectionSelect = document.getElementById('filter_section');
    
    if (!classValue) {
        sectionSelect.innerHTML = '<option value="">All Sections</option>';
        return;
    }
    
    // Fetch sections via AJAX
    fetch(`{{ route('student-list.get-sections') }}?class=${encodeURIComponent(classValue)}`)
        .then(response => response.json())
        .then(data => {
            sectionSelect.innerHTML = '<option value="">All Sections</option>';
            data.sections.forEach(section => {
                const option = document.createElement('option');
                option.value = section;
                option.textContent = section;
                sectionSelect.appendChild(option);
            });
        })
        .catch(error => {
            console.error('Error fetching sections:', error);
        });
});
</script>
@endsection

