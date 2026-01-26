@extends('layouts.app')

@php
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
@endphp

@section('title', 'Staff Management')

@section('content')
<div class="row">
    <div class="col-12">
        <!-- Summary Cards Section -->
        <div class="row mb-2">
            <div class="col-md-4 mb-2">
                <div class="card border-0 shadow-sm h-100" style="border-radius: 8px; overflow: hidden;">
                    <div class="card-body p-4" style="background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-white-50 mb-2" style="font-size: 13px; font-weight: 500;">Total Teachers</h6>
                                <h3 class="text-white mb-0" style="font-size: 32px; font-weight: 700;">{{ $totalTeachers }}</h3>
                            </div>
                            <span class="material-symbols-outlined text-white" style="font-size: 36px;">groups</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 mb-2">
                <div class="card border-0 shadow-sm h-100" style="border-radius: 8px; overflow: hidden;">
                    <div class="card-body p-4" style="background: linear-gradient(135deg, #28a745 0%, #218838 100%);">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-white-50 mb-2" style="font-size: 13px; font-weight: 500;">Present Today</h6>
                                <h3 class="text-white mb-0" style="font-size: 32px; font-weight: 700;">{{ $presentToday }}</h3>
                            </div>
                            <span class="material-symbols-outlined text-white" style="font-size: 36px;">person_check</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 mb-2">
                <div class="card border-0 shadow-sm h-100" style="border-radius: 8px; overflow: hidden;">
                    <div class="card-body p-4" style="background: linear-gradient(135deg, #fd7e14 0%, #e86800 100%);">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-white-50 mb-2" style="font-size: 13px; font-weight: 500;">Absent Today</h6>
                                <h3 class="text-white mb-0" style="font-size: 32px; font-weight: 700;">{{ $absentToday }}</h3>
                            </div>
                            <span class="material-symbols-outlined text-white" style="font-size: 36px;">person_off</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card bg-white border border-white rounded-10 p-3 mb-4">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <h4 class="mb-0 fs-16 fw-semibold">Staff Management</h4>
                <div class="d-flex flex-column gap-2 align-items-end">
                    <button type="button" class="btn btn-sm py-2 px-3 d-inline-flex align-items-center gap-1 rounded-8 staff-add-btn" data-bs-toggle="modal" data-bs-target="#staffModal" onclick="resetForm()">
                        <span class="material-symbols-outlined" style="font-size: 16px;">add</span>
                        <span>Add New Staff</span>
                    </button>
                    <a href="{{ route('staff.attendance.overview') }}" target="_blank" class="btn btn-sm py-2 px-3 d-inline-flex align-items-center gap-1 rounded-8 staff-attendance-overview-btn">
                        <span class="material-symbols-outlined" style="font-size: 16px;">calendar_month</span>
                        <span>Staff Attendance Overview</span>
                    </a>
                    <a href="{{ route('reports.staff-salary') }}" target="_blank" class="btn btn-sm py-2 px-3 d-inline-flex align-items-center gap-1 rounded-8 staff-salary-report-btn">
                        <span class="material-symbols-outlined" style="font-size: 16px;">receipt_long</span>
                        <span>Staff Salary Report</span>
                    </a>
                    <a href="{{ route('reports.staff-salary-summarized') }}" target="_blank" class="btn btn-sm py-2 px-3 d-inline-flex align-items-center gap-1 rounded-8 staff-salary-report-btn">
                        <span class="material-symbols-outlined" style="font-size: 16px;">receipt_long</span>
                        <span>Staff Salary Report (All Staff)</span>
                    </a>
                </div>
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
                        <a href="{{ route('staff.management.export', ['format' => 'excel']) }}{{ request()->has('search') ? '?search=' . request('search') : '' }}" class="btn btn-sm px-2 py-1 export-btn excel-btn">
                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">description</span>
                            <span>Excel</span>
                        </a>
                        <a href="{{ route('staff.management.export', ['format' => 'csv']) }}{{ request()->has('search') ? '?search=' . request('search') : '' }}" class="btn btn-sm px-2 py-1 export-btn csv-btn">
                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">file_present</span>
                            <span>CSV</span>
                        </a>
                        <a href="{{ route('staff.management.export', ['format' => 'pdf']) }}{{ request()->has('search') ? '?search=' . request('search') : '' }}" class="btn btn-sm px-2 py-1 export-btn pdf-btn" target="_blank">
                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">picture_as_pdf</span>
                            <span>PDF</span>
                        </a>
                        <button type="button" class="btn btn-sm px-2 py-1 export-btn print-btn" onclick="printTable()">
                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">print</span>
                            <span>Print</span>
                        </button>
                        <form action="{{ route('staff.management.delete-all') }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete ALL staff members? This action cannot be undone!');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm px-2 py-1 export-btn delete-all-btn">
                                <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">delete_sweep</span>
                                <span>Delete All</span>
                            </button>
                        </form>
                    </div>
                    
                    <!-- Search -->
                    <div class="d-flex align-items-center gap-2">
                        <label for="searchInput" class="mb-0 fs-13 fw-medium text-dark">Search:</label>
                        <div class="input-group input-group-sm search-input-group" style="width: 280px;">
                            <span class="input-group-text bg-light border-end-0" style="background-color: #f0f4ff !important; border-color: #e0e7ff; padding: 4px 8px;">
                                <span class="material-symbols-outlined" style="font-size: 14px; color: #003471;">search</span>
                            </span>
                            <input type="text" id="searchInput" class="form-control border-start-0 border-end-0" placeholder="Search by name, email, phone, emp ID..." value="{{ request('search') }}" onkeypress="handleSearchKeyPress(event)" style="padding: 4px 8px; font-size: 12px;">
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
                    <span>Staff Members List</span>
                </h5>
            </div>

            <!-- Search Results Info -->
            @if(request('search'))
                <div class="search-results-info">
                    <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle; color: #003471;">search</span>
                    <strong>Search Results:</strong> Showing results for "<strong>{{ request('search') }}</strong>" 
                    ({{ $staff->total() }} {{ Str::plural('result', $staff->total()) }} found)
                    <a href="{{ route('staff.management') }}" class="text-decoration-none ms-2" style="color: #003471;">
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
                                <th style="padding: 12px 15px; font-size: 14px;">#</th>
                                <th style="padding: 12px 15px; font-size: 14px;">Photo</th>
                                <th style="padding: 12px 15px; font-size: 14px;">Name</th>
                                <th style="padding: 12px 15px; font-size: 14px;">Emp. ID</th>
                                <th style="padding: 12px 15px; font-size: 14px;">Designation</th>
                                <th style="padding: 12px 15px; font-size: 14px;">Campus</th>
                                <th style="padding: 12px 15px; font-size: 14px;">Email</th>
                                <th style="padding: 12px 15px; font-size: 14px;">Phone</th>
                                <th style="padding: 12px 15px; font-size: 14px;">Gender</th>
                                @if(Auth::guard('admin')->check() && Auth::guard('admin')->user()->isSuperAdmin())
                                <th style="padding: 12px 15px; font-size: 14px; text-align: center;">Status</th>
                                @endif
                                <th style="padding: 12px 15px; font-size: 14px; text-align: center;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($staff as $member)
                                <tr data-staff-id="{{ $member->id }}">
                                    <td style="padding: 12px 15px; font-size: 14px;">{{ $loop->iteration + (($staff->currentPage() - 1) * $staff->perPage()) }}</td>
                                    <td style="padding: 12px 15px; font-size: 14px;">
                                        @if($member->photo)
                                            <img src="{{ Storage::url($member->photo) }}" alt="Staff" class="rounded-circle" style="width: 45px; height: 45px; object-fit: cover; border: 2px solid #e9ecef;">
                                        @else
                                            <div class="rounded-circle bg-light d-inline-flex align-items-center justify-content-center" style="width: 45px; height: 45px; border: 2px solid #e9ecef;">
                                                <span class="material-symbols-outlined text-muted" style="font-size: 22px;">person</span>
                                            </div>
                                        @endif
                                    </td>
                                    <td style="padding: 12px 15px; font-size: 14px;">
                                        <strong class="text-primary">{{ $member->name }}</strong>
                                        @if($member->father_husband_name)
                                            <br><small class="text-muted" style="font-size: 12px;">{{ $member->father_husband_name }}</small>
                                        @endif
                                    </td>
                                    <td style="padding: 12px 15px; font-size: 14px;">
                                        @if($member->emp_id)
                                            <span class="badge bg-info text-white" style="font-size: 11px;">{{ $member->emp_id }}</span>
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td style="padding: 12px 15px; font-size: 14px;">
                                        @if($member->designation)
                                            <span class="badge bg-secondary text-white" style="font-size: 11px;">{{ $member->designation }}</span>
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td style="padding: 12px 15px; font-size: 14px;">
                                        @if($member->campus)
                                            <span class="text-muted">{{ $member->campus }}</span>
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td style="padding: 12px 15px; font-size: 14px;">
                                        @if($member->email)
                                            <span class="text-muted">
                                                <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">email</span>
                                                {{ $member->email }}
                                            </span>
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td style="padding: 12px 15px; font-size: 14px;">
                                        @if($member->phone)
                                            <span class="badge bg-light text-dark" style="font-size: 11px;">
                                                <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">phone</span>
                                                {{ $member->phone }}
                                            </span>
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td style="padding: 12px 15px; font-size: 14px;">
                                        @if($member->gender)
                                            <span class="badge {{ $member->gender == 'Male' ? 'bg-primary' : ($member->gender == 'Female' ? 'bg-danger' : 'bg-secondary') }} text-white" style="font-size: 11px;">
                                                {{ $member->gender }}
                                            </span>
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    @if(Auth::guard('admin')->check() && Auth::guard('admin')->user()->isSuperAdmin())
                                    <td style="padding: 12px 15px; font-size: 14px; text-align: center;">
                                        <div class="form-check form-switch d-inline-block">
                                            <input class="form-check-input status-switch" type="checkbox" 
                                                   data-staff-id="{{ $member->id }}"
                                                   id="statusSwitch{{ $member->id }}"
                                                   {{ ($member->status ?? 'Active') === 'Active' ? 'checked' : '' }}
                                                   style="cursor: pointer; width: 3rem; height: 1.5rem;">
                                        </div>
                                    </td>
                                    @endif
                                    <td style="padding: 12px 15px; font-size: 14px; text-align: center;">
                                        <div class="d-inline-flex gap-1">
                                            <button type="button" class="btn btn-sm btn-info px-2 py-1" title="View" onclick="viewStaff({{ $member->id }})">
                                                <span class="material-symbols-outlined" style="font-size: 14px; color: white;">visibility</span>
                                            </button>
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-warning px-2 py-1" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Reports">
                                                    <span class="material-symbols-outlined" style="font-size: 14px; color: white;">pie_chart</span>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('id-card.print-staff.print', ['staff_id' => $member->id]) }}" target="_blank">
                                                            Print ID Card
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('certification.staff.generate', ['staff' => $member->id, 'type' => 'Experience Certificate']) }}" target="_blank">
                                                            Experience Certificate
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('certification.staff.generate', ['staff' => $member->id, 'type' => 'Appreciation Certificate']) }}" target="_blank">
                                                            Appreciation Certificate
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('reports.staff-salary', ['staff_id' => $member->id]) }}" target="_blank">
                                                            Salary Report
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('attendance.staff-report', ['staff_id' => $member->id]) }}" target="_blank">
                                                            Attendance Report
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('attendance.staff-report', ['staff_id' => $member->id, 'report_type' => 'performance']) }}" target="_blank">
                                                            Teacher Performance Report
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                            <button type="button" class="btn btn-sm btn-primary px-2 py-1" onclick="editStaff({{ $member->id }})" title="Edit">
                                                <span class="material-symbols-outlined" style="font-size: 14px; color: white;">edit</span>
                                            </button>
                                            <form action="{{ route('staff.management.destroy', $member) }}" method="POST" class="d-inline staff-delete-form" data-staff-id="{{ $member->id }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger px-2 py-1" title="Delete">
                                                    <span class="material-symbols-outlined" style="font-size: 14px; color: white;">delete</span>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ Auth::guard('admin')->check() && Auth::guard('admin')->user()->isSuperAdmin() ? '11' : '10' }}" class="text-center text-muted py-5">
                                        <span class="material-symbols-outlined" style="font-size: 48px; opacity: 0.3;">inbox</span>
                                        <p class="mt-2 mb-0">No staff members found.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if($staff->hasPages())
                <div class="mt-3">
                    {{ $staff->links() }}
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Staff Modal -->
<div class="modal fade" id="staffModal" tabindex="-1" aria-labelledby="staffModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
            <div class="modal-header text-white p-2" style="background: linear-gradient(135deg, #003471 0%, #004a9f 100%); border: none;">
                <h5 class="modal-title fw-semibold mb-0 d-flex align-items-center gap-2" id="staffModalLabel" style="font-size: 14px; color: white;">
                    <span class="material-symbols-outlined" style="font-size: 18px; color: white;">person_add</span>
                    <span style="color: white;">Add New Staff</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" style="opacity: 0.8;"></button>
            </div>
            <form id="staffForm" method="POST" action="{{ route('staff.management.store') }}" enctype="multipart/form-data">
                @csrf
                <div id="methodField"></div>
                <div class="modal-body p-3" style="max-height: 70vh; overflow-y: auto;">
                    <div class="row g-2">
                        <!-- Name -->
                        <div class="col-md-6">
                            <label class="form-label mb-1 fw-semibold" style="color: #003471; font-size: 11px;">Name <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm staff-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 14px;">person</span>
                                </span>
                                <input type="text" class="form-control staff-input" name="name" id="name" placeholder="Enter name" required style="font-size: 12px;">
                            </div>
                        </div>

                        <!-- Father/Husband Name -->
                        <div class="col-md-6">
                            <label class="form-label mb-1 fw-semibold" style="color: #003471; font-size: 11px;">Father/Husband Name</label>
                            <div class="input-group input-group-sm staff-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 14px;">family_restroom</span>
                                </span>
                                <input type="text" class="form-control staff-input" name="father_husband_name" id="father_husband_name" placeholder="Enter father/husband name">
                            </div>
                        </div>

                        <!-- Campus -->
                        <div class="col-md-6">
                            <label class="form-label mb-1 fw-semibold" style="color: #003471; font-size: 11px;">Campus</label>
                            <div class="input-group input-group-sm staff-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 14px;">business</span>
                                </span>
                                <select class="form-control staff-input" name="campus" id="campus" style="font-size: 12px;">
                                    <option value="">Select Campus</option>
                                    @foreach($campuses as $campus)
                                        <option value="{{ $campus->campus_name }}">{{ $campus->campus_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <!-- Designation -->
                        <div class="col-md-6">
                            <label class="form-label mb-1 fw-semibold" style="color: #003471; font-size: 11px;">Designation</label>
                            <div class="input-group input-group-sm staff-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 14px;">work</span>
                                </span>
                                <input type="text" class="form-control staff-input" name="designation" id="designation" placeholder="Enter designation">
                            </div>
                        </div>

                        <!-- Gender -->
                        <div class="col-md-6">
                            <label class="form-label mb-1 fw-semibold" style="color: #003471; font-size: 11px;">Gender</label>
                            <div class="input-group input-group-sm staff-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 14px;">people</span>
                                </span>
                                <select class="form-control staff-input" name="gender" id="gender">
                                    <option value="">Select Gender</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                        </div>

                        <!-- Emp. ID -->
                        <div class="col-md-6">
                            <label class="form-label mb-1 fw-semibold" style="color: #003471; font-size: 11px;">Emp. ID <span class="text-muted" style="font-size: 10px;">(Auto-generated)</span></label>
                            <div class="input-group input-group-sm staff-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 14px;">badge</span>
                                </span>
                                <input type="text" class="form-control staff-input" name="emp_id" id="emp_id" placeholder="Auto-generated Employee ID" readonly style="background-color: #f8f9fa; cursor: not-allowed;">
                            </div>
                        </div>

                        <!-- Phone -->
                        <div class="col-md-6">
                            <label class="form-label mb-1 fw-semibold" style="color: #003471; font-size: 11px;">Phone</label>
                            <div class="input-group input-group-sm staff-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 14px;">phone</span>
                                </span>
                                <input type="text" class="form-control staff-input" name="phone" id="phone" placeholder="Enter phone number">
                            </div>
                        </div>

                        <!-- WhatsApp -->
                        <div class="col-md-6">
                            <label class="form-label mb-1 fw-semibold" style="color: #003471; font-size: 11px;">WhatsApp</label>
                            <div class="input-group input-group-sm staff-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 14px;">chat</span>
                                </span>
                                <input type="text" class="form-control staff-input" name="whatsapp" id="whatsapp" placeholder="Enter WhatsApp number">
                            </div>
                        </div>

                        <!-- CNIC -->
                        <div class="col-md-6">
                            <label class="form-label mb-1 fw-semibold" style="color: #003471; font-size: 11px;">CNIC</label>
                            <div class="input-group input-group-sm staff-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 14px;">credit_card</span>
                                </span>
                                <input type="text" class="form-control staff-input" name="cnic" id="cnic" placeholder="Enter CNIC">
                            </div>
                        </div>

                        <!-- Qualification -->
                        <div class="col-md-6">
                            <label class="form-label mb-1 fw-semibold" style="color: #003471; font-size: 11px;">Qualification</label>
                            <div class="input-group input-group-sm staff-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 14px;">school</span>
                                </span>
                                <input type="text" class="form-control staff-input" name="qualification" id="qualification" placeholder="Enter qualification">
                            </div>
                        </div>

                        <!-- Birthday -->
                        <div class="col-md-6">
                            <label class="form-label mb-1 fw-semibold" style="color: #003471; font-size: 11px;">Birthday</label>
                            <div class="input-group input-group-sm staff-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 14px;">cake</span>
                                </span>
                                <input type="date" class="form-control staff-input" name="birthday" id="birthday">
                            </div>
                        </div>

                        <!-- Joining Date -->
                        <div class="col-md-6">
                            <label class="form-label mb-1 fw-semibold" style="color: #003471; font-size: 11px;">Joining Date</label>
                            <div class="input-group input-group-sm staff-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 14px;">event</span>
                                </span>
                                <input type="date" class="form-control staff-input" name="joining_date" id="joining_date">
                            </div>
                        </div>

                        <!-- Marital Status -->
                        <div class="col-md-6">
                            <label class="form-label mb-1 fw-semibold" style="color: #003471; font-size: 11px;">Marital Status</label>
                            <div class="input-group input-group-sm staff-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 14px;">favorite</span>
                                </span>
                                <select class="form-control staff-input" name="marital_status" id="marital_status">
                                    <option value="">Select Status</option>
                                    <option value="Single">Single</option>
                                    <option value="Married">Married</option>
                                    <option value="Divorced">Divorced</option>
                                    <option value="Widowed">Widowed</option>
                                </select>
                            </div>
                        </div>

                        <!-- Salary Type -->
                        <div class="col-md-6">
                            <label class="form-label mb-1 fw-semibold" style="color: #003471; font-size: 11px;">Salary Type</label>
                            <div class="input-group input-group-sm staff-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 14px;">payments</span>
                                </span>
                                <select class="form-control staff-input" name="salary_type" id="salary_type">
                                    <option value="">Select Salary Type</option>
                                    <option value="full time">Full Time</option>
                                    <option value="per hour">Per Hour</option>
                                    <option value="lecture">Per Lecture</option>
                                </select>
                            </div>
                        </div>

                        <!-- Salary -->
                        <div class="col-md-6">
                            <label class="form-label mb-1 fw-semibold" style="color: #003471; font-size: 11px;">Salary</label>
                            <div class="input-group input-group-sm staff-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 14px;">attach_money</span>
                                </span>
                                <input type="number" step="0.01" class="form-control staff-input" name="salary" id="salary" placeholder="Enter salary">
                            </div>
                        </div>

                        <!-- Email -->
                        <div class="col-md-6">
                            <label class="form-label mb-1 fw-semibold" style="color: #003471; font-size: 11px;">Email</label>
                            <div class="input-group input-group-sm staff-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 14px;">email</span>
                                </span>
                                <input type="email" class="form-control staff-input" name="email" id="email" placeholder="Enter email">
                            </div>
                        </div>

                        <!-- Password -->
                        <div class="col-md-6">
                            <label class="form-label mb-1 fw-semibold" style="color: #003471; font-size: 11px;">Password <span class="text-danger" id="passwordRequired">*</span></label>
                            <div class="input-group input-group-sm staff-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 14px;">lock</span>
                                </span>
                                <input type="text" class="form-control staff-input" name="password" id="password" value="staff" placeholder="Enter password" required>
                            </div>
                            <small class="text-muted" id="passwordHint" style="font-size: 10px; display: none;">Leave blank to keep current password when editing</small>
                        </div>

                        <!-- Home Address -->
                        <div class="col-12">
                            <label class="form-label mb-1 fw-semibold" style="color: #003471; font-size: 11px;">Home Address</label>
                            <div class="input-group input-group-sm staff-input-group">
                                <span class="input-group-text align-items-start" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471; padding-top: 8px;">
                                    <span class="material-symbols-outlined" style="font-size: 14px;">location_on</span>
                                </span>
                                <textarea class="form-control staff-input" name="home_address" id="home_address" rows="2" placeholder="Enter home address" style="resize: vertical;"></textarea>
                            </div>
                        </div>

                        <!-- Photo -->
                        <div class="col-md-6">
                            <label class="form-label mb-1 fw-semibold" style="color: #003471; font-size: 11px;">Photo</label>
                            <div class="input-group input-group-sm staff-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 14px;">image</span>
                                </span>
                                <input type="file" class="form-control staff-input" name="photo" id="photo" accept="image/*" style="font-size: 12px;">
                            </div>
                            <small class="text-muted" style="font-size: 10px;">Max size: 2MB (JPEG, JPG, PNG)</small>
                        </div>

                        <!-- Upload CV/Resume -->
                        <div class="col-md-6">
                            <label class="form-label mb-1 fw-semibold" style="color: #003471; font-size: 11px;">Upload CV/Resume</label>
                            <div class="input-group input-group-sm staff-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 14px;">description</span>
                                </span>
                                <input type="file" class="form-control staff-input" name="cv_resume" id="cv_resume" accept=".pdf,.doc,.docx" style="font-size: 12px;">
                            </div>
                            <small class="text-muted" style="font-size: 10px;">Max size: 5MB (PDF, DOC, DOCX)</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer p-2" style="background-color: #f8f9fa; border-top: 1px solid #e9ecef;">
                    <button type="button" class="btn btn-sm py-1 px-3 rounded-8" data-bs-dismiss="modal" style="background-color: #6c757d; color: white; border: none; transition: all 0.3s ease; font-size: 12px;">
                        <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">close</span>
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-sm py-1 px-3 rounded-8 staff-submit-btn" style="font-size: 12px;">
                        <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">save</span>
                        Save Staff
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Staff View Modal -->
<div class="modal fade" id="staffViewModal" tabindex="-1" aria-labelledby="staffViewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
            <div class="modal-header text-white p-2" style="background: linear-gradient(135deg, #003471 0%, #004a9f 100%); border: none;">
                <h5 class="modal-title fw-semibold mb-0 d-flex align-items-center gap-2" id="staffViewModalLabel" style="font-size: 14px; color: white;">
                    <span class="material-symbols-outlined" style="font-size: 18px; color: white;">visibility</span>
                    <span style="color: white;">Staff Details</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" style="opacity: 0.8;"></button>
            </div>
            <div class="modal-body p-3">
                <div class="d-flex flex-column gap-2" id="staffViewContent"></div>
            </div>
        </div>
    </div>
</div>

<style>
    /* Staff Form Styling */
    #staffModal .staff-input-group {
        height: 32px;
        border-radius: 8px;
        overflow: hidden;
        transition: all 0.3s ease;
        border: 1px solid #dee2e6;
    }
    
    #staffModal .staff-input-group:focus-within {
        box-shadow: 0 0 0 3px rgba(0, 52, 113, 0.15);
        border-color: #003471;
    }
    
    #staffModal .staff-input {
        height: 32px;
        font-size: 12px;
        padding: 0.4rem 0.65rem;
        border: none;
        border-left: 1px solid #e0e7ff;
        border-radius: 0 8px 8px 0;
        transition: all 0.3s ease;
    }
    
    #staffModal .staff-input:focus {
        border-left-color: #003471;
        box-shadow: none;
        outline: none;
    }
    
    #staffModal .input-group-text {
        height: 32px;
        padding: 0 0.65rem;
        display: flex;
        align-items: center;
        border: none;
        border-right: 1px solid #e0e7ff;
        border-radius: 8px 0 0 8px;
        transition: all 0.3s ease;
    }
    
    #staffModal .staff-input-group:focus-within .input-group-text {
        background-color: #003471 !important;
        color: white !important;
        border-right-color: #003471;
    }
    
    #staffModal textarea.staff-input {
        min-height: 50px;
        border-left: 1px solid #e0e7ff;
        padding-top: 0.4rem;
        font-size: 12px;
    }
    
    #staffModal .form-control,
    #staffModal .form-select,
    #staffModal select {
        font-size: 12px !important;
    }
    
    #staffModal .staff-submit-btn {
        background: linear-gradient(135deg, #003471 0%, #004a9f 100%);
        color: white;
        border: none;
        font-weight: 500;
        transition: all 0.3s ease;
        box-shadow: 0 2px 8px rgba(0, 52, 113, 0.25);
    }
    
    #staffModal .staff-submit-btn:hover {
        background: linear-gradient(135deg, #004a9f 0%, #003471 100%);
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0, 52, 113, 0.35);
    }
    
    .staff-add-btn {
        background: linear-gradient(135deg, #003471 0%, #004a9f 100%);
        color: white;
        border: none;
        font-weight: 500;
        transition: all 0.3s ease;
        box-shadow: 0 2px 6px rgba(0, 52, 113, 0.2);
    }
    
    .staff-add-btn:hover {
        background: linear-gradient(135deg, #004a9f 0%, #003471 100%);
        transform: translateY(-1px);
        box-shadow: 0 4px 10px rgba(0, 52, 113, 0.3);
        color: white;
    }
    
    .staff-salary-report-btn {
        background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        color: white;
        border: none;
        font-weight: 500;
        transition: all 0.3s ease;
        box-shadow: 0 2px 6px rgba(40, 167, 69, 0.2);
        text-decoration: none;
    }
    
    .staff-salary-report-btn:hover {
        background: linear-gradient(135deg, #20c997 0%, #28a745 100%);
        transform: translateY(-1px);
        box-shadow: 0 4px 10px rgba(40, 167, 69, 0.3);
        color: white;
        text-decoration: none;
    }


    .staff-attendance-overview-btn {
        background: linear-gradient(135deg, #17a2b8 0%, #0dcaf0 100%);
        color: white;
        border: none;
        font-weight: 500;
        transition: all 0.3s ease;
        box-shadow: 0 2px 6px rgba(23, 162, 184, 0.2);
        text-decoration: none;
    }

    .staff-attendance-overview-btn:hover {
        background: linear-gradient(135deg, #0dcaf0 0%, #17a2b8 100%);
        transform: translateY(-1px);
        box-shadow: 0 4px 10px rgba(23, 162, 184, 0.3);
        color: white;
        text-decoration: none;
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

    /* Search Input Group */
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
    
    .search-btn {
        background: linear-gradient(135deg, #003471 0%, #004a9f 100%);
        color: white;
        border: none;
    }
    
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
    
    .default-table-area .badge {
        font-size: 11px;
        padding: 4px 8px;
    }
    
    .default-table-area .btn-sm .material-symbols-outlined {
        font-size: 14px !important;
        color: white !important;
    }
</style>

<script>
function resetForm() {
    document.getElementById('staffForm').action = '{{ route('staff.management.store') }}';
    document.getElementById('staffForm').reset();
    document.getElementById('methodField').innerHTML = '';
    const modalLabel = document.getElementById('staffModalLabel');
    modalLabel.innerHTML = '<span class="material-symbols-outlined" style="font-size: 18px; color: white;">person_add</span><span style="color: white;">Add New Staff</span>';
    document.getElementById('password').required = true;
    document.getElementById('password').value = 'staff';
    document.getElementById('passwordRequired').style.display = 'inline';
    document.getElementById('passwordHint').style.display = 'none';
    document.getElementById('photo').value = '';
    document.getElementById('cv_resume').value = '';
    document.getElementById('campus').value = '';
    
    // Make Emp. ID readonly and fetch next Employee ID
    const empIdField = document.getElementById('emp_id');
    empIdField.setAttribute('readonly', 'readonly');
    empIdField.style.backgroundColor = '#f8f9fa';
    empIdField.style.cursor = 'not-allowed';
    
    // Fetch next Employee ID
    fetch('{{ route('staff.management.next-emp-id') }}', {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.emp_id) {
            empIdField.value = data.emp_id;
        }
    })
    .catch(error => {
        console.error('Error fetching Employee ID:', error);
    });
}

function editStaff(id) {
    fetch(`{{ url('/staff/management') }}/${id}`, {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            document.getElementById('staffForm').action = '{{ route('staff.management.update', ':id') }}'.replace(':id', id);
            document.getElementById('methodField').innerHTML = '@method('PUT')';
            const modalLabel = document.getElementById('staffModalLabel');
            modalLabel.innerHTML = '<span class="material-symbols-outlined" style="font-size: 18px; color: white;">person_add</span><span style="color: white;">Edit Staff</span>';
            
            document.getElementById('name').value = data.name || '';
            document.getElementById('father_husband_name').value = data.father_husband_name || '';
            document.getElementById('campus').value = data.campus || '';
            document.getElementById('designation').value = data.designation || '';
            document.getElementById('gender').value = data.gender || '';
            
            // Keep Emp. ID readonly in edit mode (cannot be changed)
            const empIdField = document.getElementById('emp_id');
            empIdField.value = data.emp_id || '';
            empIdField.setAttribute('readonly', 'readonly');
            empIdField.style.backgroundColor = '#f8f9fa';
            empIdField.style.cursor = 'not-allowed';
            
            document.getElementById('phone').value = data.phone || '';
            document.getElementById('whatsapp').value = data.whatsapp || '';
            document.getElementById('cnic').value = data.cnic || '';
            document.getElementById('qualification').value = data.qualification || '';
            document.getElementById('birthday').value = data.birthday || '';
            document.getElementById('joining_date').value = data.joining_date || '';
            document.getElementById('marital_status').value = data.marital_status || '';
            document.getElementById('salary_type').value = data.salary_type || '';
            document.getElementById('salary').value = data.salary || '';
            document.getElementById('email').value = data.email || '';
            document.getElementById('home_address').value = data.home_address || '';
            document.getElementById('password').value = '';
            document.getElementById('password').required = false;
            document.getElementById('passwordRequired').style.display = 'none';
            document.getElementById('passwordHint').style.display = 'block';
            
            new bootstrap.Modal(document.getElementById('staffModal')).show();
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error loading staff data');
        });
}

function viewStaff(id) {
    fetch(`{{ url('/staff/management') }}/${id}`, {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
        .then(response => response.json())
        .then(data => {
            const content = document.getElementById('staffViewContent');
            content.innerHTML = `
                <div><strong>Name:</strong> ${escapeHtml(data.name)}</div>
                <div><strong>Emp. ID:</strong> ${escapeHtml(data.emp_id || 'N/A')}</div>
                <div><strong>Campus:</strong> ${escapeHtml(data.campus || 'N/A')}</div>
                <div><strong>Designation:</strong> ${escapeHtml(data.designation || 'N/A')}</div>
                <div><strong>Email:</strong> ${escapeHtml(data.email || 'N/A')}</div>
                <div><strong>Phone:</strong> ${escapeHtml(data.phone || 'N/A')}</div>
                <div><strong>WhatsApp:</strong> ${escapeHtml(data.whatsapp || 'N/A')}</div>
                <div><strong>CNIC:</strong> ${escapeHtml(data.cnic || 'N/A')}</div>
                <div><strong>Gender:</strong> ${escapeHtml(data.gender || 'N/A')}</div>
            `;
            new bootstrap.Modal(document.getElementById('staffViewModal')).show();
        })
        .catch(() => alert('Error loading staff data'));
}

function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;',
    };
    return String(text || '').replace(/[&<>"']/g, function(m) { return map[m]; });
}

function getCsrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') : '';
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
    window.location.href = url.toString();
}

function handleSearchKeyPress(event) {
    if (event.key === 'Enter') {
        event.preventDefault();
        performSearch();
    }
}

function clearSearch() {
    const url = new URL(window.location.href);
    url.searchParams.delete('search');
    url.searchParams.set('page', '1');
    window.location.href = url.toString();
}

function updateEntriesPerPage(value) {
    const url = new URL(window.location.href);
    url.searchParams.set('per_page', value);
    url.searchParams.set('page', '1');
    window.location.href = url.toString();
}

function printTable() {
    const printContents = document.querySelector('.default-table-area').innerHTML;
    const originalContents = document.body.innerHTML;
    
    document.body.innerHTML = `
        <div style="padding: 20px;">
            <h3 style="text-align: center; margin-bottom: 20px; color: #003471;">Staff Members List</h3>
            ${printContents}
        </div>
    `;
    
    window.print();
    document.body.innerHTML = originalContents;
    window.location.reload();
}

function attachStatusSwitch(switchElement) {
    if (!switchElement) return;
    switchElement.addEventListener('change', function() {
        const staffId = this.getAttribute('data-staff-id');
        const isChecked = this.checked;
        const switchRef = this;
        this.disabled = true;

        const csrfToken = document.querySelector('meta[name="csrf-token"]');
        if (!csrfToken) {
            switchRef.checked = !isChecked;
            switchRef.disabled = false;
            alert('CSRF token not found. Please refresh the page.');
            return;
        }

        const formData = new FormData();
        formData.append('_token', csrfToken.getAttribute('content'));

        fetch(`/staff/management/${staffId}/toggle-status`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken.getAttribute('content'),
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: formData,
            credentials: 'same-origin'
        })
        .then(response => {
            if (!response.ok) {
                return response.text().then(text => {
                    try {
                        const err = JSON.parse(text);
                        throw new Error(err.message || 'Network response was not ok');
                    } catch (e) {
                        throw new Error('Network response was not ok: ' + response.status);
                    }
                });
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                switchRef.checked = data.status === 'Active';
                const alertDiv = document.createElement('div');
                alertDiv.className = 'alert alert-success alert-dismissible fade show';
                alertDiv.style.marginBottom = '15px';
                alertDiv.innerHTML = `
                    <strong>Success!</strong> ${data.message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                `;
                const cardElement = document.querySelector('.card.bg-white');
                if (cardElement) {
                    const firstChild = cardElement.firstElementChild;
                    if (firstChild && firstChild.classList.contains('alert')) {
                        firstChild.remove();
                    }
                    cardElement.insertBefore(alertDiv, cardElement.firstChild);
                }
                setTimeout(() => {
                    if (alertDiv.parentNode) {
                        alertDiv.remove();
                    }
                }, 4000);
            } else {
                switchRef.checked = !isChecked;
                alert(data.message || 'Failed to update status');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            switchRef.checked = !isChecked;
            alert('An error occurred while updating status: ' + (error.message || 'Please try again.'));
        })
        .finally(() => {
            switchRef.disabled = false;
        });
    });
}

function removeEmptyStaffRowIfExists(tbody) {
    const emptyRow = tbody.querySelector('tr td[colspan]');
    if (emptyRow) {
        emptyRow.closest('tr').remove();
    }
}

function buildStaffRow(staff, photoUrl, isSuperAdmin) {
    const deleteUrl = '{{ route('staff.management.destroy', ':id') }}'.replace(':id', staff.id);
    const idCardUrl = "{{ route('id-card.print-staff.print', ['staff_id' => ':id']) }}".replace(':id', staff.id);
    const experienceUrl = "{{ route('certification.staff.generate', ['staff' => ':id', 'type' => 'Experience Certificate']) }}".replace(':id', staff.id);
    const appreciationUrl = "{{ route('certification.staff.generate', ['staff' => ':id', 'type' => 'Appreciation Certificate']) }}".replace(':id', staff.id);
    const salaryUrl = "{{ route('reports.staff-salary', ['staff_id' => ':id']) }}".replace(':id', staff.id);
    const attendanceUrl = "{{ route('attendance.staff-report', ['staff_id' => ':id']) }}".replace(':id', staff.id);
    const performanceUrl = "{{ route('attendance.staff-report', ['staff_id' => ':id', 'report_type' => 'performance']) }}".replace(':id', staff.id);
    const statusColumn = isSuperAdmin ? `
        <td style="padding: 12px 15px; font-size: 14px; text-align: center;">
            <div class="form-check form-switch d-inline-block">
                <input class="form-check-input status-switch" type="checkbox"
                       data-staff-id="${staff.id}"
                       id="statusSwitch${staff.id}"
                       checked
                       style="cursor: pointer; width: 3rem; height: 1.5rem;">
            </div>
        </td>
    ` : '';

    const photoHtml = photoUrl
        ? `<img src="${photoUrl}" alt="Staff" class="rounded-circle" style="width: 45px; height: 45px; object-fit: cover; border: 2px solid #e9ecef;">`
        : `<div class="rounded-circle bg-light d-inline-flex align-items-center justify-content-center" style="width: 45px; height: 45px; border: 2px solid #e9ecef;">
                <span class="material-symbols-outlined text-muted" style="font-size: 22px;">person</span>
           </div>`;

    const genderBadge = staff.gender
        ? `<span class="badge ${staff.gender === 'Male' ? 'bg-primary' : (staff.gender === 'Female' ? 'bg-danger' : 'bg-secondary')} text-white" style="font-size: 11px;">${escapeHtml(staff.gender)}</span>`
        : `<span class="text-muted">N/A</span>`;

    return `
        <tr data-staff-id="${staff.id}">
            <td style="padding: 12px 15px; font-size: 14px;">NEW</td>
            <td style="padding: 12px 15px; font-size: 14px;">${photoHtml}</td>
            <td style="padding: 12px 15px; font-size: 14px;">
                <strong class="text-primary">${escapeHtml(staff.name)}</strong>
                ${staff.father_husband_name ? `<br><small class="text-muted" style="font-size: 12px;">${escapeHtml(staff.father_husband_name)}</small>` : ''}
            </td>
            <td style="padding: 12px 15px; font-size: 14px;">
                ${staff.emp_id ? `<span class="badge bg-info text-white" style="font-size: 11px;">${escapeHtml(staff.emp_id)}</span>` : '<span class="text-muted">N/A</span>'}
            </td>
            <td style="padding: 12px 15px; font-size: 14px;">
                ${staff.designation ? `<span class="badge bg-secondary text-white" style="font-size: 11px;">${escapeHtml(staff.designation)}</span>` : '<span class="text-muted">N/A</span>'}
            </td>
            <td style="padding: 12px 15px; font-size: 14px;">
                ${staff.campus ? `<span class="text-muted">${escapeHtml(staff.campus)}</span>` : '<span class="text-muted">N/A</span>'}
            </td>
            <td style="padding: 12px 15px; font-size: 14px;">
                ${staff.email ? `<span class="text-muted"><span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">email</span> ${escapeHtml(staff.email)}</span>` : '<span class="text-muted">N/A</span>'}
            </td>
            <td style="padding: 12px 15px; font-size: 14px;">
                ${staff.phone ? `<span class="badge bg-light text-dark" style="font-size: 11px;"><span class="material-symbols-outlined" style="font-size: 12px; vertical-align: middle;">phone</span> ${escapeHtml(staff.phone)}</span>` : '<span class="text-muted">N/A</span>'}
            </td>
            <td style="padding: 12px 15px; font-size: 14px;">${genderBadge}</td>
            ${statusColumn}
            <td style="padding: 12px 15px; font-size: 14px; text-align: center;">
                <div class="d-inline-flex gap-1">
                    <button type="button" class="btn btn-sm btn-info px-2 py-1" title="View" onclick="viewStaff(${staff.id})">
                        <span class="material-symbols-outlined" style="font-size: 14px; color: white;">visibility</span>
                    </button>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-warning px-2 py-1" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Reports">
                            <span class="material-symbols-outlined" style="font-size: 14px; color: white;">pie_chart</span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <a class="dropdown-item" href="${idCardUrl}" target="_blank">Print ID Card</a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="${experienceUrl}" target="_blank">Experience Certificate</a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="${appreciationUrl}" target="_blank">Appreciation Certificate</a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="${salaryUrl}" target="_blank">Salary Report</a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="${attendanceUrl}" target="_blank">Attendance Report</a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="${performanceUrl}" target="_blank">Teacher Performance Report</a>
                            </li>
                        </ul>
                    </div>
                    <button type="button" class="btn btn-sm btn-primary px-2 py-1" onclick="editStaff(${staff.id})" title="Edit">
                        <span class="material-symbols-outlined" style="font-size: 14px; color: white;">edit</span>
                    </button>
                    <form action="${deleteUrl}" method="POST" class="d-inline staff-delete-form" data-staff-id="${staff.id}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-danger px-2 py-1" title="Delete">
                            <span class="material-symbols-outlined" style="font-size: 14px; color: white;">delete</span>
                        </button>
                    </form>
                </div>
            </td>
        </tr>
    `;
}

document.addEventListener('DOMContentLoaded', function() {
    const statusSwitches = document.querySelectorAll('.status-switch');
    statusSwitches.forEach(attachStatusSwitch);

    const staffForm = document.getElementById('staffForm');
    const tbody = document.querySelector('.default-table-area tbody');
    const isSuperAdmin = {{ Auth::guard('admin')->check() && Auth::guard('admin')->user()->isSuperAdmin() ? 'true' : 'false' }};

    if (staffForm) {
        staffForm.addEventListener('submit', async function (event) {
            const methodField = document.getElementById('methodField');
            if (methodField && methodField.innerHTML.includes('PUT')) {
                return;
            }
            event.preventDefault();

            const formData = new FormData(staffForm);
            const response = await fetch(staffForm.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': getCsrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
                body: formData,
                credentials: 'same-origin',
            });

            if (!response.ok) {
                const data = await response.json().catch(() => null);
                if (data && data.errors) {
                    const firstError = Object.values(data.errors)[0];
                    alert(Array.isArray(firstError) ? firstError[0] : firstError);
                } else {
                    alert('Unable to add staff. Please check the form.');
                }
                return;
            }

            const data = await response.json();
            if (data && data.staff) {
                removeEmptyStaffRowIfExists(tbody);
                tbody.insertAdjacentHTML('afterbegin', buildStaffRow(data.staff, data.photo_url, isSuperAdmin));
                const newSwitch = tbody.querySelector(`tr[data-staff-id="${data.staff.id}"] .status-switch`);
                if (newSwitch) {
                    attachStatusSwitch(newSwitch);
                }
            }

            const modalEl = document.getElementById('staffModal');
            const modalInstance = bootstrap.Modal.getInstance(modalEl);
            if (modalInstance) {
                modalInstance.hide();
            }
            staffForm.reset();
            document.getElementById('password').value = 'staff';
        });
    }

    document.addEventListener('submit', async function (event) {
        const form = event.target;
        if (!form.classList.contains('staff-delete-form')) return;
        event.preventDefault();

        if (!confirm('Are you sure you want to delete this staff member?')) {
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
            alert(errorText || 'Unable to delete staff. Please try again.');
            return;
        }

        const data = await response.json().catch(() => ({}));
        const staffId = data && data.id ? data.id : form.dataset.staffId;
        const row = tbody.querySelector(`tr[data-staff-id="${staffId}"]`);
        if (row) {
            row.remove();
        }

        if (tbody.querySelectorAll('tr').length === 0) {
            tbody.insertAdjacentHTML('beforeend', `
                <tr>
                    <td colspan="${isSuperAdmin ? 11 : 10}" class="text-center text-muted py-5">
                        <span class="material-symbols-outlined" style="font-size: 48px; opacity: 0.3;">inbox</span>
                        <p class="mt-2 mb-0">No staff members found.</p>
                    </td>
                </tr>
            `);
        }
    });
});
</script>
@endsection
