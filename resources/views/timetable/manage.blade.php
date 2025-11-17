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
                                        <option value="{{ $class->class_name ?? $class }}" {{ request('filter_class') == ($class->class_name ?? $class) ? 'selected' : '' }}>{{ $class->class_name ?? $class }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Section Filter -->
                            <div class="col-md-3">
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
                                    <tr>
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
                                                    <span class="material-symbols-outlined" style="font-size: 16px;">edit</span>
                                                </a>
                                                <form action="{{ route('timetable.destroy', $timetable) }}" method="POST" class="d-inline" onsubmit="return confirmDelete(event)">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                                                        <span class="material-symbols-outlined" style="font-size: 16px;">delete</span>
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
        .filter-section,
        .export-buttons {
            display: none !important;
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
        window.print();
    }

    function confirmDelete(event) {
        event.preventDefault();
        if (confirm('Are you sure you want to delete this timetable? This action cannot be undone.')) {
            event.target.closest('form').submit();
        }
        return false;
    }

    // Dynamic section loading based on class selection
    document.addEventListener('DOMContentLoaded', function() {
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
            fetch(`{{ route('timetable.get-sections-by-class') }}?class=${encodeURIComponent(className)}`, {
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
        
        // Listen for class selection changes
        if (classSelect) {
            classSelect.addEventListener('change', function() {
                loadSections(this.value);
                // Clear section when class changes
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
    });
</script>
@endsection
