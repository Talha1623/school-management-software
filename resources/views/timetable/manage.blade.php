@extends('layouts.app')

@section('title', 'Timetable Management')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-3 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0 fs-16 fw-semibold">Timetable Management</h4>
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
                    <form action="{{ route('timetable.manage') }}" method="GET" id="filterForm">
                        <div class="row g-3 align-items-end">
                            <!-- Campus Filter -->
                            <div class="col-md-3">
                                <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">
                                    Campus
                                </label>
                                <select class="form-select form-select-sm" name="filter_campus" id="filter_campus">
                                    <option value="">Select Campus</option>
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
                                    <option value="">Select Class</option>
                                    @foreach($classes as $class)
                                        <option value="{{ $class->class_name ?? $class }}" data-campus="{{ $class->campus ?? '' }}" {{ request('filter_class') == ($class->class_name ?? $class) ? 'selected' : '' }}>{{ $class->class_name ?? $class }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Section Filter -->
                            <div class="col-md-2">
                                <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">
                                    Section
                                </label>
                                <select class="form-select form-select-sm" name="filter_section" id="filter_section" {{ !request('filter_class') ? 'disabled' : '' }}>
                                    <option value="">Select Section</option>
                                    @if(request('filter_class'))
                                        @foreach($sections as $section)
                                            <option value="{{ $section }}" {{ request('filter_section') == $section ? 'selected' : '' }}>{{ $section }}</option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>

                            <!-- Day Filter -->
                            <div class="col-md-2">
                                <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">
                                    Day
                                </label>
                                <select class="form-select form-select-sm" name="filter_day" id="filter_day">
                                    <option value="">All Days</option>
                                    <option value="Monday" {{ request('filter_day') == 'Monday' ? 'selected' : '' }}>Monday</option>
                                    <option value="Tuesday" {{ request('filter_day') == 'Tuesday' ? 'selected' : '' }}>Tuesday</option>
                                    <option value="Wednesday" {{ request('filter_day') == 'Wednesday' ? 'selected' : '' }}>Wednesday</option>
                                    <option value="Thursday" {{ request('filter_day') == 'Thursday' ? 'selected' : '' }}>Thursday</option>
                                    <option value="Friday" {{ request('filter_day') == 'Friday' ? 'selected' : '' }}>Friday</option>
                                    <option value="Saturday" {{ request('filter_day') == 'Saturday' ? 'selected' : '' }}>Saturday</option>
                                    <option value="Sunday" {{ request('filter_day') == 'Sunday' ? 'selected' : '' }}>Sunday</option>
                                </select>
                            </div>

                            <!-- Filter Button -->
                            <div class="col-md-2">
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
            @if(request('filter_campus') || request('filter_class') || request('filter_section') || request('filter_day'))
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
                        <a href="{{ route('timetable.export', ['format' => 'pdf']) }}{{ request()->has('filter_campus') || request()->has('filter_class') || request()->has('filter_section') ? '?' . http_build_query(request()->only(['filter_campus', 'filter_class', 'filter_section'])) : '' }}" class="btn btn-sm px-2 py-1 export-btn pdf-btn" target="_blank">
                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">picture_as_pdf</span>
                            <span>PDF</span>
                        </a>
                        <button type="button" class="btn btn-sm px-2 py-1 export-btn print-btn" onclick="printTable()">
                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">print</span>
                            <span>Print</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Print Header (Hidden by default, shown in print) -->
            <div class="print-header" style="display: none;">
                <h3>{{ config('app.name', 'ICMS') }}</h3>
                <h4>Timetable Management</h4>
                <p>{{ config('app.address', 'Defence View') }}</p>
                <p>Phone: {{ config('app.phone', '+923316074246') }} | Email: {{ config('app.email', 'arainabdurrehman3@gmail.com') }}</p>
                @if(request('filter_campus') || request('filter_class') || request('filter_section') || request('filter_day'))
                    <p>
                        <strong>Filters:</strong>
                        @if(request('filter_campus')) Campus: {{ request('filter_campus') }} @endif
                        @if(request('filter_class')) | Class: {{ request('filter_class') }} @endif
                        @if(request('filter_section')) | Section: {{ request('filter_section') }} @endif
                        @if(request('filter_day')) | Day: {{ request('filter_day') }} @endif
                    </p>
                @endif
                <p>Generated: {{ date('d-m-Y H:i:s') }}</p>
            </div>

            <!-- Table Header -->
            <div class="mb-2 p-2 rounded-8" style="background: linear-gradient(135deg, #003471 0%, #004a9f 100%);">
                <h5 class="mb-0 text-white fs-15 fw-semibold d-flex align-items-center gap-2">
                    <span class="material-symbols-outlined" style="font-size: 18px;">list</span>
                    <span>Timetable List</span>
                </h5>
            </div>

            <div class="default-table-area" style="margin-top: 0;">
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Campus</th>
                                <th>Class</th>
                                <th>Section</th>
                                <th>Subject</th>
                                <th>Day</th>
                                <th>Starting Time</th>
                                <th>Ending Time</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if(isset($timetables) && $timetables->count() > 0)
                                @forelse($timetables as $timetable)
                                    <tr data-timetable-id="{{ $timetable->id }}">
                                        <td>{{ $loop->iteration + (($timetables->currentPage() - 1) * $timetables->perPage()) }}</td>
                                        <td>
                                            <span class="badge bg-info text-white" style="font-size: 12px; padding: 4px 8px;">{{ $timetable->campus }}</span>
                                        </td>
                                        <td>
                                            <strong class="text-dark">{{ $timetable->class }}</strong>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary text-white" style="font-size: 12px; padding: 4px 8px;">{{ $timetable->section }}</span>
                                        </td>
                                        <td>
                                            <strong class="text-dark">{{ $timetable->subject }}</strong>
                                            @if(isset($timetable->assigned_teacher) && $timetable->assigned_teacher)
                                                <div class="mt-1">
                                                    <small class="text-muted d-flex align-items-center" style="font-size: 10px;">
                                                        <span class="material-symbols-outlined me-1" style="font-size: 12px;">person</span>
                                                        <span style="color: #28a745;">{{ $timetable->assigned_teacher }}</span>
                                                    </small>
                                                </div>
                                            @elseif(!isset($timetable->assigned_teacher) && strpos($timetable->subject, '[') !== 0)
                                                <div class="mt-1">
                                                    <small class="text-muted" style="font-size: 10px; color: #dc3545;">No teacher assigned</small>
                                                </div>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge bg-success text-white" style="font-size: 12px; padding: 4px 8px;">{{ $timetable->day }}</span>
                                        </td>
                                        <td>
                                            <span class="text-muted">{{ date('H:i', strtotime($timetable->starting_time)) }}</span>
                                        </td>
                                        <td>
                                            <span class="text-muted">{{ date('H:i', strtotime($timetable->ending_time)) }}</span>
                                        </td>
                                        <td>
                                            <div class="d-flex gap-1">
                                                <a href="{{ route('timetable.edit', $timetable) }}" class="btn btn-sm btn-primary" title="Edit">
                                                    <span class="material-symbols-outlined" style="font-size: 16px; color: white;">edit</span>
                                                </a>
                                                <a href="{{ route('timetable.terminal-print', $timetable) }}" class="btn btn-sm btn-success" title="Terminal Print" target="_blank">
                                                    <span class="material-symbols-outlined" style="font-size: 16px; color: white;">print</span>
                                                </a>
                                                <form action="{{ route('timetable.destroy', $timetable) }}" method="POST" class="d-inline timetable-delete-form" data-timetable-id="{{ $timetable->id }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                                                        <span class="material-symbols-outlined" style="font-size: 16px; color: white;">delete</span>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center py-4">
                                            <div class="d-flex flex-column align-items-center gap-2">
                                                <span class="material-symbols-outlined" style="font-size: 48px; color: #dee2e6;">schedule</span>
                                                <p class="text-muted mb-0">No timetables found.</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            @else
                                <tr>
                                    <td colspan="9" class="text-center py-4">
                                        <div class="d-flex flex-column align-items-center gap-2">
                                            <span class="material-symbols-outlined" style="font-size: 48px; color: #dee2e6;">schedule</span>
                                            <p class="text-muted mb-0">No timetables found.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pagination -->
            @if(isset($timetables) && $timetables->hasPages())
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <div class="text-muted fs-13">
                        Showing {{ $timetables->firstItem() ?? 0 }} to {{ $timetables->lastItem() ?? 0 }} of {{ $timetables->total() }} entries
                    </div>
                    <div>
                        {{ $timetables->links() }}
                    </div>
                </div>
            @endif
            @else
                <!-- Empty State when no filters -->
                <div class="text-center py-5">
                    <div class="d-flex flex-column align-items-center gap-2">
                        <span class="material-symbols-outlined" style="font-size: 64px; color: #dee2e6;">filter_alt</span>
                        <h5 class="text-muted mb-2">Apply Filters to View Timetables</h5>
                        <p class="text-muted mb-0">Please select Campus, Class, or Section to view timetable data.</p>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

<style>
    .filter-btn {
        background: linear-gradient(135deg, #003471 0%, #004a9f 100%);
        color: white;
        border: none;
        transition: all 0.3s ease;
    }

    .filter-btn:hover {
        background: linear-gradient(135deg, #004a9f 0%, #0056c3 100%);
        color: white;
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(0, 52, 113, 0.3);
    }

    .form-select:focus {
        border-color: #003471;
        box-shadow: 0 0 0 0.2rem rgba(0, 52, 113, 0.25);
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
    }
    
    .btn-sm {
        padding: 4px 8px;
        font-size: 12px;
    }
    
    .btn-sm .material-symbols-outlined {
        font-size: 16px;
        vertical-align: middle;
    }
    
    .btn-primary {
        background-color: #0d6efd;
        border-color: #0d6efd;
    }
    
    .btn-primary:hover {
        background-color: #0b5ed7;
        border-color: #0a58ca;
    }
    
    .btn-danger {
        background-color: #dc3545;
        border-color: #dc3545;
    }
    
    .btn-danger:hover {
        background-color: #bb2d3b;
        border-color: #b02a37;
    }
    
    .btn-success {
        background-color: #28a745;
        border-color: #28a745;
    }
    
    .btn-success:hover {
        background-color: #218838;
        border-color: #1e7e34;
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

    @media print {
        body {
            background: white !important;
        }
        
        .card {
            border: none !important;
            box-shadow: none !important;
        }
        
        .filter-section,
        .export-buttons,
        .btn,
        .pagination,
        .d-flex.gap-1,
        .d-flex.justify-content-between,
        .alert {
            display: none !important;
        }
        
        .default-table-area {
            margin: 0 !important;
            padding: 0 !important;
        }
        
        .default-table-area table {
            width: 100% !important;
            border-collapse: collapse !important;
            page-break-inside: auto !important;
        }
        
        .default-table-area table thead {
            display: table-header-group !important;
            background-color: #f8f9fa !important;
        }
        
        .default-table-area table thead th {
            background-color: #f8f9fa !important;
            color: #000 !important;
            border: 1px solid #000 !important;
            padding: 8px !important;
            font-size: 12px !important;
        }
        
        .default-table-area table tbody tr {
            page-break-inside: avoid !important;
            page-break-after: auto !important;
        }
        
        .default-table-area table tbody td {
            border: 1px solid #000 !important;
            padding: 6px !important;
            font-size: 11px !important;
        }
        
        .badge {
            border: 1px solid #000 !important;
            padding: 2px 6px !important;
            font-size: 10px !important;
        }
        
        /* Print Header */
        .print-header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #000;
        }
        
        .print-header h3 {
            margin: 0;
            font-size: 18px;
            font-weight: bold;
        }
        
        .print-header p {
            margin: 5px 0;
            font-size: 12px;
        }
        
        /* Print Footer */
        @page {
            margin: 1cm;
        }
        
        .print-footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 10px;
            padding: 10px;
            border-top: 1px solid #000;
        }
    }
</style>

<script>
    function updateEntriesPerPage(value) {
        const url = new URL(window.location.href);
        url.searchParams.set('per_page', value);
        window.location.href = url.toString();
    }

    function printTable() {
        const params = new URLSearchParams();
        const campus = document.getElementById('filter_campus')?.value?.trim();
        const cls = document.getElementById('filter_class')?.value?.trim();
        const section = document.getElementById('filter_section')?.value?.trim();
        const day = document.getElementById('filter_day')?.value?.trim();

        params.set('auto_print', '1');
        if (campus) params.set('filter_campus', campus);
        if (cls) params.set('filter_class', cls);
        if (section) params.set('filter_section', section);
        if (day) params.set('filter_day', day);

        const url = '{{ route("timetable.manage.print") }}?' + params.toString();
        const w = window.open(url, '_blank');
        if (!w) {
            window.location.href = url;
        }
    }

    function getCsrfToken() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    function filterClassOptionsByCampus(campusValue) {
        const classSelect = document.getElementById('filter_class');
        if (!classSelect) return;
        const campusLower = (campusValue || '').toLowerCase().trim();
        Array.from(classSelect.options).forEach((option, index) => {
            if (index === 0) {
                option.hidden = false;
                option.disabled = false;
                return;
            }
            const optionCampus = (option.dataset.campus || '').toLowerCase().trim();
            const shouldShow = !campusLower || optionCampus === campusLower;
            option.hidden = !shouldShow;
            option.disabled = !shouldShow;
        });

        if (!classSelect.value || classSelect.selectedOptions[0]?.disabled) {
            classSelect.value = '';
        }
    }

    // Dynamic section loading based on class selection
    document.addEventListener('DOMContentLoaded', function() {
        const tbody = document.querySelector('.default-table-area tbody');
        const campusSelect = document.getElementById('filter_campus');
        const classSelect = document.getElementById('filter_class');
        const sectionSelect = document.getElementById('filter_section');
        
        // Function to load sections based on selected class
        function loadSections(className) {
            if (!className) {
                sectionSelect.innerHTML = '<option value="">Select Class First</option>';
                sectionSelect.disabled = true;
                return;
            }
            
            // Show loading state
            sectionSelect.innerHTML = '<option value="">Loading...</option>';
            sectionSelect.disabled = true;
            
            // Make AJAX request
            const params = new URLSearchParams();
            params.append('class', className);
            if (campusSelect && campusSelect.value) {
                params.append('campus', campusSelect.value);
            }
            fetch(`{{ route('timetable.get-sections-by-class') }}?${params.toString()}`, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                }
            })
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
                    sectionSelect.disabled = false;
                } else {
                    sectionSelect.innerHTML = '<option value="">No sections found</option>';
                    sectionSelect.disabled = true;
                }
            })
            .catch(error => {
                console.error('Error loading sections:', error);
                sectionSelect.innerHTML = '<option value="">Error loading sections</option>';
                sectionSelect.disabled = true;
            });
        }

        document.addEventListener('submit', async function (event) {
            const form = event.target;
            if (!form.classList.contains('timetable-delete-form')) return;
            event.preventDefault();

            if (!confirm('Are you sure you want to delete this timetable? This action cannot be undone.')) {
                return;
            }

            const response = await fetch(form.action, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': getCsrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
                credentials: 'same-origin',
            });

            if (!response.ok) {
                const errorText = await response.text().catch(() => '');
                alert(errorText || 'Unable to delete timetable. Please try again.');
                return;
            }

            const data = await response.json().catch(() => ({}));
            const timetableId = data && data.id ? data.id : form.dataset.timetableId;
            const row = tbody.querySelector(`tr[data-timetable-id="${timetableId}"]`);
            if (row) {
                row.remove();
            }

            if (tbody.querySelectorAll('tr').length === 0) {
                tbody.insertAdjacentHTML('beforeend', `
                    <tr>
                        <td colspan="9" class="text-center py-4">
                            <div class="d-flex flex-column align-items-center gap-2">
                                <span class="material-symbols-outlined" style="font-size: 48px; color: #dee2e6;">schedule</span>
                                <p class="text-muted mb-0">No timetables found.</p>
                            </div>
                        </td>
                    </tr>
                `);
            }
        });
        
        // Listen for class selection changes
        if (classSelect) {
            classSelect.addEventListener('change', function() {
                loadSections(this.value);
                // Clear section when class changes
                sectionSelect.value = '';
            });
        }

        if (campusSelect) {
            campusSelect.addEventListener('change', function() {
                filterClassOptionsByCampus(this.value);
                loadSections('');
                sectionSelect.value = '';
            });
        }
        
        // Load sections on page load if class is already selected
        @if(request('filter_class'))
            loadSections('{{ request('filter_class') }}');
            // Set the selected section if it was previously selected
            @if(request('filter_section'))
                setTimeout(() => {
                    sectionSelect.value = '{{ request('filter_section') }}';
                }, 500);
            @endif
        @endif

        filterClassOptionsByCampus(document.getElementById('filter_campus')?.value || '');
    });
</script>
@endsection
