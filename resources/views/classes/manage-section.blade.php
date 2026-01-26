@extends('layouts.app')

@section('title', 'Manage Section')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-3 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0 fs-16 fw-semibold">Manage Section</h4>
                <button type="button" class="btn btn-sm py-2 px-3 d-inline-flex align-items-center gap-1 rounded-8 section-add-btn" data-bs-toggle="modal" data-bs-target="#sectionModal" onclick="resetForm()">
                    <span class="material-symbols-outlined" style="font-size: 16px;">add</span>
                    <span>Add New Section</span>
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
                    <form action="{{ route('classes.manage-section') }}" method="GET" id="filterForm">
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

                            <!-- Session Filter -->
                            <div class="col-md-3">
                                <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">
                                    Session
                                </label>
                                <select class="form-select form-select-sm" name="filter_session" id="filter_session">
                                    <option value="">All Sessions</option>
                                    @foreach($allSessions as $session)
                                        <option value="{{ $session }}" {{ request('filter_session') == $session ? 'selected' : '' }}>{{ $session }}</option>
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
                        @if(request('search'))
                            <input type="hidden" name="search" value="{{ request('search') }}">
                        @endif
                        @if(request('per_page'))
                            <input type="hidden" name="per_page" value="{{ request('per_page') }}">
                        @endif
                    </form>
                </div>
            </div>

            <!-- Table Toolbar - Show only when filters are applied -->
            @if(request('filter_campus') || request('filter_class') || request('filter_session') || request('search'))
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
                        @php
                            $exportParams = [];
                            if(request('filter_campus')) $exportParams['filter_campus'] = request('filter_campus');
                            if(request('filter_class')) $exportParams['filter_class'] = request('filter_class');
                            if(request('filter_session')) $exportParams['filter_session'] = request('filter_session');
                            $exportQuery = http_build_query($exportParams);
                            $exportUrl = $exportQuery ? '?' . $exportQuery : '';
                        @endphp
                        <a href="{{ route('classes.manage-section.export', ['format' => 'excel']) }}{{ $exportUrl }}" class="btn btn-sm px-2 py-1 export-btn excel-btn">
                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">description</span>
                            <span>Excel</span>
                        </a>
                        <a href="{{ route('classes.manage-section.export', ['format' => 'csv']) }}{{ $exportUrl }}" class="btn btn-sm px-2 py-1 export-btn csv-btn">
                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">file_present</span>
                            <span>CSV</span>
                        </a>
                        <a href="{{ route('classes.manage-section.export', ['format' => 'pdf']) }}{{ $exportUrl }}" class="btn btn-sm px-2 py-1 export-btn pdf-btn" target="_blank">
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
                            <input type="text" id="searchInput" class="form-control border-start-0 border-end-0" placeholder="Search by name, campus, class..." value="{{ request('search') }}" onkeypress="handleSearchKeyPress(event)" oninput="handleSearchInput(event)" style="padding: 4px 8px; font-size: 12px;">
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

            <!-- Show table only when filters are applied -->
            @if(request('filter_campus') || request('filter_class') || request('filter_session') || request('search'))
                <!-- Table Header -->
                <div class="mb-2 p-2 rounded-8" style="background: linear-gradient(135deg, #003471 0%, #004a9f 100%);">
                    <h5 class="mb-0 text-white fs-15 fw-semibold d-flex align-items-center gap-2">
                        <span class="material-symbols-outlined" style="font-size: 18px;">list</span>
                        <span>Sections List</span>
                    </h5>
                </div>

                <!-- Search Results Info -->
                <div class="search-results-info">
                    <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle; color: #003471;">filter_list</span>
                    <strong>Filtered Results:</strong>
                    @if(request('filter_campus'))
                        Campus: <strong>{{ request('filter_campus') }}</strong>
                    @endif
                    @if(request('filter_class'))
                        {{ request('filter_campus') ? ' | ' : '' }}Class: <strong>{{ request('filter_class') }}</strong>
                    @endif
                    @if(request('filter_session'))
                        {{ (request('filter_campus') || request('filter_class')) ? ' | ' : '' }}Session: <strong>{{ request('filter_session') }}</strong>
                    @endif
                    @if(request('search'))
                        {{ (request('filter_campus') || request('filter_class') || request('filter_session')) ? ' | ' : '' }}Search: <strong>{{ request('search') }}</strong>
                    @endif
                    @if(isset($sections))
                        ({{ $sections->total() }} {{ Str::plural('result', $sections->total()) }} found)
                    @endif
                    <a href="{{ route('classes.manage-section') }}" class="text-decoration-none ms-2" style="color: #003471;">
                        <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">close</span>
                        Clear All
                    </a>
                </div>

                <div class="default-table-area" style="margin-top: 0;">
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Campus</th>
                                <th>Name</th>
                                <th>Nick Name</th>
                                <th>Class</th>
                                <th>Teacher</th>
                                <th>Session</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if(isset($sections) && $sections->count() > 0)
                                @forelse($sections as $section)
                                    <tr>
                                        <td>{{ $loop->iteration + (($sections->currentPage() - 1) * $sections->perPage()) }}</td>
                                        <td>
                                            <span class="badge bg-primary text-white" style="font-size: 12px; padding: 4px 8px;">{{ $section->campus }}</span>
                                        </td>
                                        <td>
                                            <strong class="text-dark">{{ $section->name }}</strong>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary text-white" style="font-size: 12px; padding: 4px 8px;">{{ $section->nick_name ?? 'N/A' }}</span>
                                        </td>
                                        <td>
                                            <span class="badge bg-success text-white" style="font-size: 12px; padding: 4px 8px;">{{ $section->class }}</span>
                                        </td>
                                        <td>
                                            <span class="text-muted">{{ $section->teacher ?? 'N/A' }}</span>
                                        </td>
                                        <td>
                                            <span class="badge bg-warning text-dark" style="font-size: 12px; padding: 4px 8px;">{{ $section->session ?? 'N/A' }}</span>
                                        </td>
                                        <td class="text-end">
                                            <div class="d-inline-flex gap-1 align-items-center">
                                                <button type="button" class="btn btn-sm btn-primary px-2 py-1" title="Edit" onclick="editSection({{ $section->id }}, '{{ addslashes($section->campus) }}', '{{ addslashes($section->name) }}', '{{ addslashes($section->nick_name ?? '') }}', '{{ addslashes($section->class) }}', '{{ addslashes($section->teacher ?? '') }}', '{{ addslashes($section->session ?? '') }}')">
                                                    <span class="material-symbols-outlined" style="font-size: 14px; color: white;">edit</span>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-danger px-2 py-1" title="Delete" onclick="if(confirm('Are you sure you want to delete this section?')) { document.getElementById('delete-form-{{ $section->id }}').submit(); }">
                                                    <span class="material-symbols-outlined" style="font-size: 14px; color: white;">delete</span>
                                                </button>
                                                <form id="delete-form-{{ $section->id }}" action="{{ route('classes.manage-section.destroy', $section) }}" method="POST" class="d-none">
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
                                            <p class="mt-2 mb-0">No sections found.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            @else
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-5">
                                        <span class="material-symbols-outlined" style="font-size: 48px; opacity: 0.3;">inbox</span>
                                        <p class="mt-2 mb-0">No sections found.</p>
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>

                @if(isset($sections) && $sections->hasPages())
                    <div class="mt-3">
                        {{ $sections->links() }}
                    </div>
                @endif
            @else
                <!-- Message when no filters applied -->
                <div class="text-center py-5">
                    <span class="material-symbols-outlined" style="font-size: 64px; opacity: 0.3; color: #003471;">filter_list</span>
                    <p class="mt-3 mb-0 text-muted fs-15">Please apply filters to view sections</p>
                    <p class="text-muted fs-13">Select Campus, Class, or Session to filter and view sections</p>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Section Modal -->
<div class="modal fade" id="sectionModal" tabindex="-1" aria-labelledby="sectionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
            <div class="modal-header text-white p-3" style="background: linear-gradient(135deg, #003471 0%, #004a9f 100%); border: none;">
                <h5 class="modal-title fs-15 fw-semibold mb-0 d-flex align-items-center gap-2" id="sectionModalLabel" style="color: white;">
                    <span class="material-symbols-outlined" style="font-size: 20px; color: white;">group</span>
                    <span style="color: white;">Add New Section</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" style="opacity: 0.8;"></button>
            </div>
            <form id="sectionForm" method="POST" action="{{ route('classes.manage-section.store') }}">
                @csrf
                <div id="methodField"></div>
                <div class="modal-body p-3">
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Campus <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm section-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">location_on</span>
                                </span>
                                <select class="form-select section-input" name="campus" id="campus" required style="border: none; border-left: 1px solid #e0e7ff;">
                                    <option value="">Select Campus</option>
                                    @foreach($campuses as $campus)
                                        <option value="{{ $campus->campus_name ?? $campus }}">{{ $campus->campus_name ?? $campus }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Name <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm section-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">label</span>
                                </span>
                                <input type="text" class="form-control section-input" name="name" id="name" placeholder="Enter section name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Nick Name</label>
                            <div class="input-group input-group-sm section-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">alternate_email</span>
                                </span>
                                <input type="text" class="form-control section-input" name="nick_name" id="nick_name" placeholder="Enter nick name">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Class <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm section-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">school</span>
                                </span>
                                <select class="form-select section-input" name="class" id="class" required style="border: none; border-left: 1px solid #e0e7ff;">
                                    <option value="">Select Class</option>
                                    @foreach($classes as $class)
                                        <option value="{{ $class->class_name ?? $class }}" data-campus="{{ $class->campus ?? '' }}">{{ $class->class_name ?? $class }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Teacher</label>
                            <div class="input-group input-group-sm section-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">person</span>
                                </span>
                                <select class="form-select section-input" name="teacher" id="teacher" style="border: none; border-left: 1px solid #e0e7ff;">
                                    <option value="">Select Teacher</option>
                                    @foreach($teachers as $teacher)
                                        <option value="{{ $teacher->name }}" data-campus="{{ $teacher->campus ?? '' }}">{{ $teacher->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Session</label>
                            <div class="input-group input-group-sm section-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">calendar_today</span>
                                </span>
                                <select class="form-control section-input" name="session" id="session" style="border: none; border-left: 1px solid #e0e7ff;">
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
                    <button type="submit" class="btn btn-sm py-2 px-4 rounded-8 section-submit-btn">
                        <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">save</span>
                        Save Section
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    /* Section Form Styling */
    #sectionModal .section-input-group {
        height: 36px;
        border-radius: 8px;
        overflow: hidden;
        transition: all 0.3s ease;
        border: 1px solid #dee2e6;
    }
    
    #sectionModal .section-input-group:focus-within {
        box-shadow: 0 0 0 3px rgba(0, 52, 113, 0.15);
        border-color: #003471;
    }
    
    #sectionModal .section-input {
        height: 36px;
        font-size: 13px;
        padding: 0.5rem 0.75rem;
        border: none;
        border-left: 1px solid #e0e7ff;
        border-radius: 0 8px 8px 0;
        transition: all 0.3s ease;
    }
    
    #sectionModal .section-input:focus {
        border-left-color: #003471;
        box-shadow: none;
        outline: none;
    }
    
    #sectionModal .input-group-text {
        height: 36px;
        padding: 0 0.75rem;
        display: flex;
        align-items: center;
        border: none;
        border-right: 1px solid #e0e7ff;
        border-radius: 8px 0 0 8px;
        transition: all 0.3s ease;
    }
    
    #sectionModal .section-input-group:focus-within .input-group-text {
        background-color: #003471 !important;
        color: white !important;
        border-right-color: #003471;
    }
    
    #sectionModal .form-label {
        margin-bottom: 0.4rem;
        line-height: 1.3;
    }
    
    #sectionModal .section-submit-btn {
        background: linear-gradient(135deg, #003471 0%, #004a9f 100%);
        color: white;
        border: none;
        font-weight: 500;
        transition: all 0.3s ease;
        box-shadow: 0 2px 8px rgba(0, 52, 113, 0.25);
    }
    
    #sectionModal .section-submit-btn:hover {
        background: linear-gradient(135deg, #004a9f 0%, #003471 100%);
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0, 52, 113, 0.35);
    }
    
    #sectionModal .section-submit-btn:active {
        transform: translateY(0);
    }
    
    /* Add New Button Styling */
    .section-add-btn {
        background: linear-gradient(135deg, #003471 0%, #004a9f 100%);
        color: white;
        border: none;
        font-weight: 500;
        transition: all 0.3s ease;
        box-shadow: 0 2px 6px rgba(0, 52, 113, 0.2);
    }
    
    .section-add-btn:hover {
        background: linear-gradient(135deg, #004a9f 0%, #003471 100%);
        transform: translateY(-1px);
        box-shadow: 0 4px 10px rgba(0, 52, 113, 0.3);
        color: white;
    }
    
    .section-add-btn:active {
        transform: translateY(0);
    }
    
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
    
    .default-table-area .btn-sm {
        font-size: 13px;
        padding: 4px 8px;
    }
    
    .default-table-area .btn-sm .material-symbols-outlined {
        font-size: 14px !important;
        vertical-align: middle;
    }
    
    .default-table-area .btn-primary .material-symbols-outlined,
    .default-table-area .btn-danger .material-symbols-outlined {
        color: white !important;
    }
</style>

<script>
function loadClassesByCampus(targetSelectId, campusValue, selectedValue = '') {
    const selectEl = document.getElementById(targetSelectId);
    if (!selectEl) return;

    const placeholder = targetSelectId === 'filter_class' ? 'All Classes' : 'Select Class';
    selectEl.innerHTML = `<option value="">${placeholder}</option>`;

    fetch(`{{ route('classes.manage-section.classes-by-campus') }}?campus=${encodeURIComponent(campusValue || '')}`, {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.classes && data.classes.length > 0) {
            data.classes.forEach(className => {
                const option = document.createElement('option');
                option.value = className;
                option.textContent = className;
                selectEl.appendChild(option);
            });
        }
        if (selectedValue) {
            selectEl.value = selectedValue;
        }
    })
    .catch(() => {
        // Keep placeholder on error
    });
}

function filterClassOptions(campusValue, selectedClass = '') {
    const classSelect = document.getElementById('class');
    if (!classSelect) return;

    const campusLower = (campusValue || '').toLowerCase().trim();
    const options = Array.from(classSelect.options);
    options.forEach((option, index) => {
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

    if (selectedClass) {
        classSelect.value = selectedClass;
    }

    if (!classSelect.value || classSelect.selectedOptions[0]?.disabled) {
        classSelect.value = '';
    }
}

function filterTeacherOptions(campusValue, selectedTeacher = '') {
    const teacherSelect = document.getElementById('teacher');
    if (!teacherSelect) return;

    const campusLower = (campusValue || '').toLowerCase().trim();
    const options = Array.from(teacherSelect.options);
    options.forEach((option, index) => {
        if (index === 0) {
            option.hidden = false;
            option.disabled = false;
            return;
        }
        const optionCampus = (option.dataset.campus || '').toLowerCase().trim();
        const shouldShow = !campusLower || optionCampus === campusLower || optionCampus === '';
        option.hidden = !shouldShow;
        option.disabled = !shouldShow;
    });

    if (selectedTeacher) {
        teacherSelect.value = selectedTeacher;
    }

    if (!teacherSelect.value || teacherSelect.selectedOptions[0]?.disabled) {
        teacherSelect.value = '';
    }
}

// Reset form when opening modal for new section
function resetForm() {
    document.getElementById('sectionForm').reset();
    document.getElementById('sectionForm').action = "{{ route('classes.manage-section.store') }}";
    document.getElementById('methodField').innerHTML = '';
    const modalLabel = document.getElementById('sectionModalLabel');
    modalLabel.innerHTML = `
        <span class="material-symbols-outlined" style="font-size: 20px; color: white;">group</span>
        <span style="color: white;">Add New Section</span>
    `;
    document.querySelector('.section-submit-btn').innerHTML = `
        <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">save</span>
        Save Section
    `;

    loadClassesByCampus('class', '');
    filterTeacherOptions('');
}

// Edit section function
function editSection(id, campus, name, nickName, className, teacher, session) {
    document.getElementById('campus').value = campus;
    document.getElementById('name').value = name;
    document.getElementById('nick_name').value = nickName || '';
    loadClassesByCampus('class', campus, className);
    filterTeacherOptions(campus, teacher || '');
    document.getElementById('session').value = session || '';
    document.getElementById('sectionForm').action = "{{ url('classes/manage-section') }}/" + id;
    document.getElementById('methodField').innerHTML = '@method("PUT")';
    const modalLabel = document.getElementById('sectionModalLabel');
    modalLabel.innerHTML = `
        <span class="material-symbols-outlined" style="font-size: 20px; color: white;">edit</span>
        <span style="color: white;">Edit Section</span>
    `;
    document.querySelector('.section-submit-btn').innerHTML = `
        <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">save</span>
        Update Section
    `;
    
    const modal = new bootstrap.Modal(document.getElementById('sectionModal'));
    modal.show();
}

document.addEventListener('DOMContentLoaded', function() {
    const filterCampusSelect = document.getElementById('filter_campus');
    const filterClassSelect = document.getElementById('filter_class');
    if (filterCampusSelect && filterClassSelect) {
        loadClassesByCampus('filter_class', filterCampusSelect.value, filterClassSelect.value);
        filterCampusSelect.addEventListener('change', function() {
            loadClassesByCampus('filter_class', this.value);
        });
    }

    const campusSelect = document.getElementById('campus');
    if (campusSelect) {
        loadClassesByCampus('class', campusSelect.value);
        campusSelect.addEventListener('change', function() {
            loadClassesByCampus('class', this.value);
            filterTeacherOptions(this.value);
        });
    }
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
    
    url.searchParams.set('page', '1');
    
    // Preserve filter parameters
    if (document.getElementById('filter_campus').value) {
        url.searchParams.set('filter_campus', document.getElementById('filter_campus').value);
    }
    if (document.getElementById('filter_class').value) {
        url.searchParams.set('filter_class', document.getElementById('filter_class').value);
    }
    if (document.getElementById('filter_session').value) {
        url.searchParams.set('filter_session', document.getElementById('filter_session').value);
    }
    
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
    
    // Preserve filter parameters
    if (document.getElementById('filter_campus').value) {
        url.searchParams.set('filter_campus', document.getElementById('filter_campus').value);
    }
    if (document.getElementById('filter_class').value) {
        url.searchParams.set('filter_class', document.getElementById('filter_class').value);
    }
    if (document.getElementById('filter_session').value) {
        url.searchParams.set('filter_session', document.getElementById('filter_session').value);
    }
    
    const searchInput = document.getElementById('searchInput');
    searchInput.disabled = true;
    
    window.location.href = url.toString();
}

// Update entries per page
function updateEntriesPerPage(value) {
    const url = new URL(window.location.href);
    url.searchParams.set('per_page', value);
    url.searchParams.set('page', '1');
    window.location.href = url.toString();
}

// Print table
function printTable() {
    const printContents = document.querySelector('.default-table-area').innerHTML;
    const originalContents = document.body.innerHTML;
    
    document.body.innerHTML = `
        <div style="padding: 20px;">
            <h3 style="text-align: center; margin-bottom: 20px; color: #003471;">Sections List</h3>
            ${printContents}
        </div>
    `;
    
    window.print();
    document.body.innerHTML = originalContents;
    window.location.reload();
}
</script>
@endsection
