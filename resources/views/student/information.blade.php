@extends('layouts.app')

@section('title', 'Student Information')

@section('content')
<div id="student-info-content">
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
            <form method="GET" action="{{ route('student.information') }}" class="mb-3" id="student-filter-form">
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
                        <label for="filter_status" class="form-label mb-0 fs-13 fw-medium">Status</label>
                        <select class="form-select form-select-sm" id="filter_status" name="filter_status" style="height: 32px;">
                            <option value="">All Status</option>
                            <option value="active" {{ request('filter_status') == 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ request('filter_status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-sm w-100" style="background: linear-gradient(135deg, #003471 0%, #004a9f 100%); color: white; height: 32px; border: none;">
                            <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">filter_list</span>
                            Filter
                        </button>
                    </div>
                    @if(request('filter_campus') || request('filter_class') || request('filter_section') || request('filter_type') || request('filter_status'))
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
                    @if(request('filter_campus') || request('filter_class') || request('filter_section') || request('filter_type') || request('filter_status'))
                    <div class="d-flex gap-2">
                        <a href="{{ route('student.information.export', ['format' => 'excel']) }}?{{ http_build_query(request()->except(['page', 'per_page'])) }}" class="btn btn-sm px-2 py-1 export-btn excel-btn">
                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">description</span>
                            <span>Excel</span>
                        </a>
                        <a href="{{ route('student.information.export', ['format' => 'pdf']) }}?{{ http_build_query(request()->except(['page', 'per_page'])) }}" class="btn btn-sm px-2 py-1 export-btn pdf-btn" target="_blank">
                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">picture_as_pdf</span>
                            <span>PDF</span>
                        </a>
                        <form action="{{ route('student.information.delete-all') }}?{{ http_build_query(request()->except(['page', 'per_page'])) }}" method="POST" class="d-inline delete-all-form" onsubmit="return confirm('Are you sure you want to delete ALL filtered students? This action cannot be undone!');">
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
            @if(request('filter_campus') || request('filter_class') || request('filter_section') || request('filter_type') || request('filter_status'))
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
                                        <div class="d-flex gap-1 justify-content-center">
                                            <button type="button" class="btn btn-sm btn-primary px-2 py-1" onclick="viewStudent({{ $student->id }})" title="View Details">
                                                <span class="material-symbols-outlined" style="font-size: 14px; color: white;">visibility</span>
                                            </button>
                                            <a href="{{ route('student.edit', $student->id) }}" class="btn btn-sm btn-warning px-2 py-1" title="Edit Student">
                                                <span class="material-symbols-outlined" style="font-size: 14px; color: white;">edit</span>
                                            </a>
                                            <button type="button" class="btn btn-sm btn-danger px-2 py-1" onclick="deleteStudent({{ $student->id }}, '{{ $student->student_name }}', '{{ $student->student_code }}')" title="Delete Student">
                                                <span class="material-symbols-outlined" style="font-size: 14px; color: white;">delete</span>
                                            </button>
                                            <div class="dropdown student-action-dropdown">
                                                <button class="btn btn-sm btn-secondary px-2 py-1 no-caret" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="More Actions">
                                                    <span class="material-symbols-outlined" style="font-size: 14px; color: white;">pie_chart</span>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end student-action-menu">
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('student.print', $student) }}?auto_print=1" target="_blank">
                                                            Print Admission Form
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('id-card.print-student.print') }}?student_id={{ $student->id }}" target="_blank">
                                                            Generate ID Card
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('certification.student.generate', $student) }}?type=Date%20of%20Birth%20Certificate" target="_blank">
                                                            Date of Birth Certificate
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('certification.student.generate', $student) }}?type=Provisional%20Certificate" target="_blank">
                                                            Provisional Certificate
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('certification.student.generate', $student) }}?type=Character%20Certificate" target="_blank">
                                                            Character Certificate
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('certification.student.generate', $student) }}?type=School%20Leaving%20Certificate" target="_blank">
                                                            School Leaving Certificate
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('student-behavior.progress-tracking', ['search' => $student->student_code, 'auto_print' => 1]) }}" target="_blank">
                                                            Monthly Behavior Report
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('accounting.fee-voucher.print', ['student_code' => $student->student_code]) }}" target="_blank">
                                                            Print Fee Voucher
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('attendance.student-summary.print', ['student_id' => $student->id, 'auto_print' => 1]) }}" target="_blank">
                                                            Attendance Report
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
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
let studentActionDropdownBound = false;

function setupStudentActionDropdowns() {
    if (studentActionDropdownBound) return;
    studentActionDropdownBound = true;

    document.addEventListener('shown.bs.dropdown', function(event) {
        const dropdown = event.target?.classList?.contains('student-action-dropdown')
            ? event.target
            : event.target?.closest?.('.student-action-dropdown');
        if (!dropdown) return;

        const menu = dropdown.querySelector('.student-action-menu');
        const toggle = dropdown.querySelector('button');
        if (!menu || !toggle) return;

        dropdown._movedMenu = menu;
        dropdown._movedMenuOriginalParent = menu.parentElement;

        const rect = toggle.getBoundingClientRect();
        const menuWidth = menu.offsetWidth || 220;
        const left = Math.max(8, Math.min(rect.right - menuWidth, window.innerWidth - menuWidth - 8));
        const top = rect.bottom + 6;

        menu.style.position = 'fixed';
        menu.style.top = `${top}px`;
        menu.style.left = `${left}px`;
        menu.style.zIndex = '2000';
        document.body.appendChild(menu);
    });

    document.addEventListener('hidden.bs.dropdown', function(event) {
        const dropdown = event.target?.classList?.contains('student-action-dropdown')
            ? event.target
            : event.target?.closest?.('.student-action-dropdown');
        if (!dropdown || !dropdown._movedMenu) return;

        const menu = dropdown._movedMenu;
        const originalParent = dropdown._movedMenuOriginalParent;
        if (originalParent) {
            originalParent.appendChild(menu);
        }
        menu.style.position = '';
        menu.style.top = '';
        menu.style.left = '';
        menu.style.zIndex = '';
        dropdown._movedMenu = null;
        dropdown._movedMenuOriginalParent = null;
    });
}

function loadStudentInfo(url, options = {}) {
    const target = document.getElementById('student-info-content');
    if (!target) {
        window.location.href = url;
        return;
    }

    const shouldPushState = options.pushState !== false;

    fetch(url, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.text())
    .then(html => {
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');
        const newContent = doc.getElementById('student-info-content');
        if (!newContent) {
            window.location.href = url;
            return;
        }
        target.innerHTML = newContent.innerHTML;
        if (shouldPushState) {
            window.history.pushState({}, '', url);
        }
        initializeStudentInfo();
    })
    .catch(() => {
        window.location.href = url;
    });
}

function initializeStudentInfo() {
    const filterForm = document.getElementById('student-filter-form');
    if (filterForm) {
        filterForm.onsubmit = function(event) {
            event.preventDefault();
            const formData = new FormData(filterForm);
            const params = new URLSearchParams(formData);
            const url = `${filterForm.action}?${params.toString()}`;
            loadStudentInfo(url);
        };
    }

    const campusSelect = document.getElementById('filter_campus');
    const classSelect = document.getElementById('filter_class');
    const sectionSelect = document.getElementById('filter_section');

    if (classSelect) {
        classSelect.onchange = function() {
            loadSectionsForFilter(this.value);
        };
    }

    if (campusSelect) {
        campusSelect.onchange = function() {
            loadClassesForFilter(this.value);
            if (sectionSelect) {
                sectionSelect.innerHTML = '<option value="">All Sections</option>';
            }
        };
    }

    setupStudentActionDropdowns();

    if (campusSelect) {
        const selectedCampus = campusSelect.value;
        const selectedClass = classSelect ? classSelect.value : '';
        const selectedSection = '{{ request('filter_section') }}';
        loadClassesForFilter(selectedCampus, selectedClass);
        if (selectedClass) {
            loadSectionsForFilter(selectedClass, selectedSection);
        }
    }
}

const studentInfoContainer = document.getElementById('student-info-content');
if (studentInfoContainer && !studentInfoContainer.dataset.bound) {
    studentInfoContainer.dataset.bound = 'true';
    studentInfoContainer.addEventListener('click', function(event) {
        const link = event.target.closest('a');
        if (link && link.closest('.pagination')) {
            event.preventDefault();
            loadStudentInfo(link.href);
        }
    });
}

window.addEventListener('popstate', function() {
    loadStudentInfo(window.location.href, { pushState: false });
});

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
    
    loadStudentInfo(url.toString());
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
    
    loadStudentInfo(url.toString());
}

// Update entries per page
function updateEntriesPerPage(value) {
    const url = new URL(window.location.href);
    url.searchParams.set('per_page', value);
    url.searchParams.set('page', '1'); // Reset to first page
    loadStudentInfo(url.toString());
}

function loadClassesForFilter(selectedCampus, selectedClass = null) {
    const classSelect = document.getElementById('filter_class');
    classSelect.innerHTML = '<option value="">All Classes</option>';

    fetch(`{{ route('student.information.classes-by-campus') }}?campus=${encodeURIComponent(selectedCampus || '')}`)
        .then(response => response.json())
        .then(data => {
            if (data.classes && data.classes.length > 0) {
                data.classes.forEach(className => {
                    const option = document.createElement('option');
                    option.value = className;
                    option.textContent = className;
                    if (selectedClass && selectedClass === className) {
                        option.selected = true;
                    }
                    classSelect.appendChild(option);
                });
            }
        })
        .catch(error => {
            console.error('Error loading classes:', error);
        });
}

// Function to load sections based on class
function loadSectionsForFilter(selectedClass, selectedSection = null) {
    const sectionSelect = document.getElementById('filter_section');
    
    // Clear existing options except the first one
    sectionSelect.innerHTML = '<option value="">All Sections</option>';
    
    if (selectedClass) {
        const selectedCampus = document.getElementById('filter_campus').value;
        // Fetch sections via AJAX
        fetch(`{{ route('student.information.sections-by-class') }}?class=${encodeURIComponent(selectedClass)}&campus=${encodeURIComponent(selectedCampus || '')}`)
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

document.addEventListener('DOMContentLoaded', function() {
    initializeStudentInfo();
});

// View student details
function viewStudent(studentId) {
    window.location.href = '{{ route("student.view", ":id") }}'.replace(':id', studentId);
}

// Delete student
function deleteStudent(studentId, studentName, studentCode) {
    if (confirm(`Are you sure you want to delete student "${studentName}" (${studentCode})? This action cannot be undone!`)) {
        fetch(`{{ route('student.delete', ':id') }}`.replace(':id', studentId), {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                loadStudentInfo(window.location.href, { pushState: false });
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while deleting the student.');
        });
    }
}

document.addEventListener('submit', function(event) {
    const form = event.target.closest('.delete-all-form');
    if (!form) return;
    event.preventDefault();
    if (!confirm('Are you sure you want to delete ALL filtered students? This action cannot be undone!')) {
        return;
    }
    const formData = new FormData(form);
    fetch(form.action, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        },
        body: formData
    })
    .then(() => {
        loadStudentInfo(window.location.href, { pushState: false });
    })
    .catch(() => {
        window.location.href = form.action;
    });
});
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

    .student-action-dropdown .no-caret::after {
        display: none !important;
    }

    .student-action-dropdown {
        position: relative;
    }

    .student-action-menu {
        min-width: 220px;
        margin-top: 6px;
        border-radius: 8px;
        border: 1px solid #e9ecef;
        box-shadow: 0 6px 18px rgba(0, 0, 0, 0.12);
        right: 0;
        left: auto;
        transform: translateX(0);
        z-index: 1050;
    }
</style>
@endsection
