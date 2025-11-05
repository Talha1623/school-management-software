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

            <!-- Table Header -->
            <div class="mb-2 p-2 rounded-8" style="background: linear-gradient(135deg, #003471 0%, #004a9f 100%);">
                <h5 class="mb-0 text-white fs-15 fw-semibold d-flex align-items-center gap-2">
                    <span class="material-symbols-outlined" style="font-size: 18px;">list</span>
                    <span>Timetable List</span>
                </h5>
            </div>

            <div class="default-table-area" style="margin-top: 0;">
                <div class="table-responsive">
                    <table class="table">
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
                            </tr>
                        </thead>
                        <tbody>
                            @if(isset($timetables) && $timetables->count() > 0)
                                @forelse($timetables as $timetable)
                                    <tr>
                                        <td>{{ $loop->iteration + (($timetables->currentPage() - 1) * $timetables->perPage()) }}</td>
                                        <td>
                                            <span class="badge bg-info text-white">{{ $timetable->campus }}</span>
                                        </td>
                                        <td>
                                            <strong class="text-primary">{{ $timetable->class }}</strong>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary text-white">{{ $timetable->section }}</span>
                                        </td>
                                        <td>
                                            <strong>{{ $timetable->subject }}</strong>
                                        </td>
                                        <td>
                                            <span class="badge bg-success text-white">{{ $timetable->day }}</span>
                                        </td>
                                        <td>
                                            <code style="background-color: #f8f9fa; padding: 2px 6px; border-radius: 4px; font-size: 12px;">{{ date('H:i', strtotime($timetable->starting_time)) }}</code>
                                        </td>
                                        <td>
                                            <code style="background-color: #f8f9fa; padding: 2px 6px; border-radius: 4px; font-size: 12px;">{{ date('H:i', strtotime($timetable->ending_time)) }}</code>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center py-4">
                                            <div class="d-flex flex-column align-items-center gap-2">
                                                <span class="material-symbols-outlined" style="font-size: 48px; color: #dee2e6;">schedule</span>
                                                <p class="text-muted mb-0">No timetables found.</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            @else
                                <tr>
                                    <td colspan="8" class="text-center py-4">
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
        line-height: 1.4;
        border: 1px solid #dee2e6;
    }
    
    .default-table-area table tbody tr:hover {
        background-color: #f8f9fa;
    }
    
    .default-table-area .badge {
        font-size: 11px;
        padding: 3px 6px;
        font-weight: 500;
    }
</style>

<script>
    function updateEntriesPerPage(value) {
        const url = new URL(window.location.href);
        url.searchParams.set('per_page', value);
        window.location.href = url.toString();
    }
</script>
@endsection
