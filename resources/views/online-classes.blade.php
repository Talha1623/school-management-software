@extends('layouts.app')

@section('title', 'Online Classes')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-3 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0 fs-16 fw-semibold">Online Classes</h4>
                <button type="button" class="btn btn-sm py-2 px-3 d-inline-flex align-items-center gap-1 rounded-8 online-class-add-btn" data-bs-toggle="modal" data-bs-target="#onlineClassModal" onclick="resetForm()">
                    <span class="material-symbols-outlined" style="font-size: 16px;">add</span>
                    <span>Add New Class</span>
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
                        <a href="{{ route('online-classes.export', ['format' => 'excel']) }}{{ request()->has('search') ? '?search=' . request('search') : '' }}" class="btn btn-sm px-2 py-1 export-btn excel-btn">
                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">description</span>
                            <span>Excel</span>
                        </a>
                        <a href="{{ route('online-classes.export', ['format' => 'csv']) }}{{ request()->has('search') ? '?search=' . request('search') : '' }}" class="btn btn-sm px-2 py-1 export-btn csv-btn">
                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">file_present</span>
                            <span>CSV</span>
                        </a>
                        <a href="{{ route('online-classes.export', ['format' => 'pdf']) }}{{ request()->has('search') ? '?search=' . request('search') : '' }}" class="btn btn-sm px-2 py-1 export-btn pdf-btn" target="_blank">
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
                            <input type="text" id="searchInput" class="form-control border-start-0 border-end-0" placeholder="Search by topic, class, section, campus..." value="{{ request('search') }}" onkeypress="handleSearchKeyPress(event)" oninput="handleSearchInput(event)" style="padding: 4px 8px; font-size: 12px;">
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
                    <span>Online Classes List</span>
                </h5>
            </div>

            <!-- Search Results Info -->
            @if(request('search'))
                <div class="search-results-info">
                    <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle; color: #003471;">search</span>
                    <strong>Search Results:</strong> Showing results for "<strong>{{ request('search') }}</strong>"
                    @if(isset($onlineClasses))
                        ({{ $onlineClasses->total() }} {{ Str::plural('result', $onlineClasses->total()) }} found)
                    @endif
                    <a href="{{ route('online-classes') }}" class="text-decoration-none ms-2" style="color: #003471;">
                        <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">close</span>
                        Clear
                    </a>
                </div>
            @endif

            <div class="default-table-area" style="margin-top: 0;">
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Class Topic</th>
                                <th>Start Time</th>
                                <th>Class</th>
                                <th>Section</th>
                                <th>Date</th>
                                <th>Timing</th>
                                <th>Link</th>
                                <th>Options</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if(isset($onlineClasses) && $onlineClasses->count() > 0)
                                @forelse($onlineClasses as $class)
                                    <tr>
                                        <td>{{ $loop->iteration + (($onlineClasses->currentPage() - 1) * $onlineClasses->perPage()) }}</td>
                                        <td>
                                            <strong class="text-dark">{{ $class->class_topic }}</strong>
                                        </td>
                                        <td>
                                            <span class="badge bg-primary text-white" style="font-size: 12px; padding: 4px 8px;">{{ $class->start_time ? date('H:i', strtotime($class->start_time)) : 'N/A' }}</span>
                                        </td>
                                        <td>
                                            <strong class="text-dark">{{ $class->class }}</strong>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary text-white" style="font-size: 12px; padding: 4px 8px;">{{ $class->section }}</span>
                                        </td>
                                        <td>
                                            <span class="badge bg-success text-white" style="font-size: 12px; padding: 4px 8px;">{{ $class->start_date->format('d-m-Y') }}</span>
                                        </td>
                                        <td>
                                            <span class="text-muted">{{ $class->timing }}</span>
                                        </td>
                                        <td>
                                            @if($class->link)
                                                <a href="{{ $class->link }}" target="_blank" class="btn btn-sm btn-info px-2 py-1" title="Open Meeting Link">
                                                    <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle; color: white;">open_in_new</span>
                                                    <span>Link</span>
                                                </a>
                                            @else
                                                <span class="text-muted" style="font-size: 12px;">No link</span>
                                            @endif
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-primary px-2 py-1 join-class-btn" onclick="joinClass({{ $class->id }}, '{{ addslashes($class->class_topic) }}', '{{ addslashes($class->password) }}', '{{ addslashes($class->link ?? '') }}')" title="Join Class">
                                                <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle; color: white;">videocam</span>
                                                <span>Join</span>
                                            </button>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-danger px-2 py-1" title="Delete" onclick="if(confirm('Are you sure you want to delete this online class?')) { document.getElementById('delete-form-{{ $class->id }}').submit(); }">
                                                <span class="material-symbols-outlined" style="font-size: 14px; color: white;">delete</span>
                                            </button>
                                            <form id="delete-form-{{ $class->id }}" action="{{ route('online-classes.destroy', $class->id) }}" method="POST" class="d-none">
                                                @csrf
                                                @method('DELETE')
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="10" class="text-center py-4">
                                            <div class="d-flex flex-column align-items-center gap-2">
                                                <span class="material-symbols-outlined" style="font-size: 48px; color: #dee2e6;">video_library</span>
                                                <p class="text-muted mb-0">No online classes found.</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            @else
                                <tr>
                                    <td colspan="10" class="text-center py-4">
                                        <div class="d-flex flex-column align-items-center gap-2">
                                            <span class="material-symbols-outlined" style="font-size: 48px; color: #dee2e6;">video_library</span>
                                            <p class="text-muted mb-0">No online classes found.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pagination -->
            @if(isset($onlineClasses) && $onlineClasses->hasPages())
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <div class="text-muted fs-13">
                        Showing {{ $onlineClasses->firstItem() ?? 0 }} to {{ $onlineClasses->lastItem() ?? 0 }} of {{ $onlineClasses->total() }} entries
                    </div>
                    <div>
                        {{ $onlineClasses->links() }}
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Online Class Modal -->
<div class="modal fade" id="onlineClassModal" tabindex="-1" aria-labelledby="onlineClassModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
            <div class="modal-header text-white p-3" style="background: linear-gradient(135deg, #003471 0%, #004a9f 100%); border: none;">
                <h5 class="modal-title fs-15 fw-semibold mb-0 d-flex align-items-center gap-2" id="onlineClassModalLabel" style="color: white;">
                    <span class="material-symbols-outlined" style="font-size: 20px; color: white;">video_library</span>
                    <span style="color: white;">Add New Class</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" style="opacity: 0.8;"></button>
            </div>
            <form id="onlineClassForm" method="POST" action="{{ route('online-classes.store') }}">
                @csrf
                <div id="methodField"></div>
                <div class="modal-body p-3">
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Campus <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm online-class-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">location_on</span>
                                </span>
                                <select class="form-select online-class-input" name="campus" id="campus" required style="border: none; border-left: 1px solid #e0e7ff;">
                                    <option value="">Select Campus</option>
                                    @foreach($campuses as $campus)
                                        <option value="{{ $campus->campus_name ?? $campus }}">{{ $campus->campus_name ?? $campus }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">For Class <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm online-class-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">school</span>
                                </span>
                                <select class="form-select online-class-input" name="class" id="class" required style="border: none; border-left: 1px solid #e0e7ff;">
                                    <option value="">Select Class</option>
                                    @foreach($classes as $classItem)
                                        <option value="{{ $classItem->class_name ?? $classItem }}">{{ $classItem->class_name ?? $classItem }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Section <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm online-class-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">group</span>
                                </span>
                                <select class="form-select online-class-input" name="section" id="section" required style="border: none; border-left: 1px solid #e0e7ff;" disabled>
                                    <option value="">Select Section</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Class Topic <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm online-class-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">topic</span>
                                </span>
                                <input type="text" class="form-control online-class-input" name="class_topic" id="class_topic" placeholder="Enter class topic" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Start Date <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm online-class-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">calendar_today</span>
                                </span>
                                <input type="date" class="form-control online-class-input" name="start_date" id="start_date" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Start Time</label>
                            <div class="input-group input-group-sm online-class-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">access_time</span>
                                </span>
                                <input type="time" class="form-control online-class-input" name="start_time" id="start_time">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Timing <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm online-class-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">schedule</span>
                                </span>
                                <input type="text" class="form-control online-class-input" name="timing" id="timing" placeholder="e.g., 10:00 AM - 11:00 AM" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Password <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm online-class-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">lock</span>
                                </span>
                                <input type="text" class="form-control online-class-input" name="password" id="password" placeholder="Enter password" required minlength="4">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Meeting Link</label>
                            <div class="input-group input-group-sm online-class-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">link</span>
                                </span>
                                <input type="url" class="form-control online-class-input" name="link" id="link" placeholder="e.g., https://zoom.us/j/123456789 or https://meet.google.com/abc-defg-hij">
                            </div>
                            <small class="text-muted" style="font-size: 11px;">Enter Zoom, Google Meet, or other meeting platform link</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer p-3" style="background-color: #f8f9fa; border-top: 1px solid #e9ecef;">
                    <button type="button" class="btn btn-sm py-2 px-4 rounded-8" data-bs-dismiss="modal" style="background-color: #6c757d; color: white; border: none; transition: all 0.3s ease;">
                        <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">close</span>
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-sm py-2 px-4 rounded-8 online-class-submit-btn">
                        <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">save</span>
                        Add Class
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    /* Online Class Form Styling */
    #onlineClassModal .online-class-input-group {
        height: 36px;
        border-radius: 8px;
        overflow: hidden;
        transition: all 0.3s ease;
        border: 1px solid #dee2e6;
    }
    
    #onlineClassModal .online-class-input-group:focus-within {
        box-shadow: 0 0 0 3px rgba(0, 52, 113, 0.15);
        border-color: #003471;
    }
    
    #onlineClassModal .online-class-input {
        height: 36px;
        font-size: 13px;
        padding: 0.5rem 0.75rem;
        border: none;
        border-left: 1px solid #e0e7ff;
        border-radius: 0 8px 8px 0;
        transition: all 0.3s ease;
    }
    
    #onlineClassModal .online-class-input:focus {
        border-left-color: #003471;
        box-shadow: none;
        outline: none;
    }
    
    #onlineClassModal .input-group-text {
        height: 36px;
        padding: 0 0.75rem;
        display: flex;
        align-items: center;
        border: none;
        border-right: 1px solid #e0e7ff;
        border-radius: 8px 0 0 8px;
        transition: all 0.3s ease;
    }
    
    #onlineClassModal .online-class-input-group:focus-within .input-group-text {
        background-color: #003471 !important;
        color: white !important;
        border-right-color: #003471;
    }
    
    #onlineClassModal select.online-class-input {
        border-left: 1px solid #e0e7ff;
    }
    
    .online-class-add-btn {
        background: linear-gradient(135deg, #003471 0%, #004a9f 100%);
        color: white;
        border: none;
        transition: all 0.3s ease;
    }
    
    .online-class-add-btn:hover {
        background: linear-gradient(135deg, #004a9f 0%, #0056c3 100%);
        color: white;
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(0, 52, 113, 0.3);
    }
    
    .online-class-submit-btn {
        background: linear-gradient(135deg, #003471 0%, #004a9f 100%);
        color: white;
        border: none;
        transition: all 0.3s ease;
    }
    
    .online-class-submit-btn:hover {
        background: linear-gradient(135deg, #004a9f 0%, #0056c3 100%);
        color: white;
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(0, 52, 113, 0.3);
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

    .join-class-btn {
        background: linear-gradient(135deg, #2196f3 0%, #0b7dda 100%);
        color: white;
        border: none;
        transition: all 0.3s ease;
        font-weight: 500;
    }

    .join-class-btn:hover {
        background: linear-gradient(135deg, #0b7dda 0%, #2196f3 100%);
        color: white;
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(33, 150, 243, 0.3);
    }
</style>

<script>
    function resetForm() {
        document.getElementById('onlineClassForm').reset();
        document.getElementById('methodField').innerHTML = '';
        const sectionSelect = document.getElementById('section');
        sectionSelect.innerHTML = '<option value="">Select Section</option>';
        sectionSelect.disabled = true;
        const modalLabel = document.getElementById('onlineClassModalLabel');
        modalLabel.innerHTML = `
            <span class="material-symbols-outlined" style="font-size: 20px; color: white;">video_library</span>
            <span style="color: white;">Add New Class</span>
        `;
        document.getElementById('onlineClassForm').action = '{{ route("online-classes.store") }}';
    }

    function editClass(id, campus, classVal, section, classTopic, startDate, startTime, timing, password, link) {
        resetForm();
        document.getElementById('campus').value = campus;
        document.getElementById('class').value = classVal;
        
        // Load sections for the selected class
        if (classVal) {
            loadSections(classVal, section);
        }
        
        document.getElementById('class_topic').value = classTopic;
        document.getElementById('start_date').value = startDate;
        document.getElementById('start_time').value = startTime || '';
        document.getElementById('timing').value = timing;
        document.getElementById('password').value = password;
        document.getElementById('link').value = link || '';
        
        const modalLabel = document.getElementById('onlineClassModalLabel');
        modalLabel.innerHTML = `
            <span class="material-symbols-outlined" style="font-size: 20px; color: white;">edit</span>
            <span style="color: white;">Edit Online Class</span>
        `;
        document.getElementById('onlineClassForm').action = '{{ route("online-classes.update", ":id") }}'.replace(':id', id);
        document.getElementById('methodField').innerHTML = '@method("PUT")';
        
        new bootstrap.Modal(document.getElementById('onlineClassModal')).show();
    }
    
    function loadSections(selectedClass, selectedSection = null) {
        const sectionSelect = document.getElementById('section');
        if (!selectedClass) {
            sectionSelect.innerHTML = '<option value="">Select Section</option>';
            sectionSelect.disabled = true;
            return;
        }
        
        sectionSelect.innerHTML = '<option value="">Loading...</option>';
        sectionSelect.disabled = true;
        
        fetch(`{{ route('online-classes.get-sections') }}?class=${encodeURIComponent(selectedClass)}`)
            .then(response => response.json())
            .then(data => {
                sectionSelect.innerHTML = '<option value="">Select Section</option>';
                if (data.sections && data.sections.length > 0) {
                    data.sections.forEach(section => {
                        const option = document.createElement('option');
                        option.value = section;
                        option.textContent = section;
                        if (selectedSection && section === selectedSection) {
                            option.selected = true;
                        }
                        sectionSelect.appendChild(option);
                    });
                }
                sectionSelect.disabled = false;
            })
            .catch(error => {
                console.error('Error loading sections:', error);
                sectionSelect.innerHTML = '<option value="">Error loading sections</option>';
                sectionSelect.disabled = false;
            });
    }
    
    // Dynamic section loading when class changes
    document.addEventListener('DOMContentLoaded', function() {
        const classSelect = document.getElementById('class');
        if (classSelect) {
            classSelect.addEventListener('change', function() {
                loadSections(this.value);
            });
        }
    });

    function joinClass(id, classTopic, password, link) {
        // If link is provided, open it directly
        if (link && link.trim() !== '') {
            const confirmJoin = confirm(`Join Meeting: ${classTopic}\n\nPassword: ${password}\n\nClick OK to open the meeting link.`);
            if (confirmJoin) {
                window.open(link, '_blank', 'width=1200,height=800');
            }
        } else {
            // Show meeting details and copy password
            const confirmJoin = confirm(`Join Meeting: ${classTopic}\n\nPassword: ${password}\n\nClick OK to copy password to clipboard.`);
            
            if (confirmJoin) {
                // Create a temporary input to copy password
                const tempInput = document.createElement('input');
                tempInput.value = password;
                document.body.appendChild(tempInput);
                tempInput.select();
                document.execCommand('copy');
                document.body.removeChild(tempInput);
                
                // Show success message
                alert(`Meeting Password copied to clipboard!\n\nClass: ${classTopic}\nPassword: ${password}\n\nPlease use this password to join the meeting.`);
            }
        }
    }

    function updateEntriesPerPage(value) {
        const url = new URL(window.location.href);
        url.searchParams.set('per_page', value);
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

    function clearSearch() {
        const url = new URL(window.location.href);
        url.searchParams.delete('search');
        url.searchParams.set('page', '1');
        
        const searchInput = document.getElementById('searchInput');
        searchInput.disabled = true;
        
        window.location.href = url.toString();
    }

    function printTable() {
        window.print();
    }
</script>
@endsection
S