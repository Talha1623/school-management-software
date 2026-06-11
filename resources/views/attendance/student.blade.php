@extends('layouts.app')

@section('title', 'Student Attendance')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-3 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0 fs-16 fw-semibold">Student Attendance</h4>
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

            @if(empty($isStaffAttendanceUser))
                <div class="alert alert-info mb-3 py-2" role="alert" style="font-size: 13px;">
                    <strong>Admin view:</strong> All classes in this campus are shown. To test a teacher account, logout and login at <strong>/staff/login</strong> with that teacher’s email.
                </div>
            @elseif(($classes->isEmpty() ?? true))
                <div class="alert alert-warning mb-3 py-2" role="alert" style="font-size: 13px;">
                    <strong>No class assigned to {{ $staff->name ?? 'this teacher' }}.</strong>
                    Open <strong>Classes → Manage Section</strong>, choose class <strong>Three</strong> (or your class name), set <strong>Teacher</strong> = <strong>{{ $staff->name ?? '' }}</strong> (exact name from Staff Management).
                </div>
            @else
                <div class="alert alert-success mb-3 py-2" role="alert" style="font-size: 13px;">
                    <strong>Teacher:</strong> {{ $staff->name ?? '' }} — allowed class(es): {{ $classes->implode(', ') }}
                </div>
            @endif

            <!-- Attendance Form -->
            <form method="GET" action="{{ route('attendance.student') }}" id="attendanceForm">
                <div class="row g-2 align-items-end">
                    <!-- Campus Field (wider when locked so long campus names fit) -->
                    <div class="{{ !empty($staffCampusLocked) ? 'col-md-3' : 'col-md-2' }}">
                        <label for="filter_campus" class="form-label mb-0 fs-13 fw-medium">Campus</label>
                        @if(!empty($staffCampusLocked))
                            <div class="attendance-campus-locked" title="{{ $filterCampus }}">
                                <span class="material-symbols-outlined attendance-campus-locked__icon" aria-hidden="true">apartment</span>
                                <span class="attendance-campus-locked__text">{{ $filterCampus }}</span>
                            </div>
                            <input type="hidden" name="filter_campus" id="filter_campus" value="{{ $filterCampus }}">
                        @else
                            <div class="position-relative">
                                <select class="form-select form-select-sm" id="filter_campus" name="filter_campus" style="height: 32px; padding-right: {{ request('filter_campus') ? '30px' : '12px' }};">
                                    <option value="">Select Campus</option>
                                    @foreach($campuses as $campus)
                                        <option value="{{ $campus->campus_name ?? $campus }}" {{ ($filterCampus ?? request('filter_campus')) == ($campus->campus_name ?? $campus) ? 'selected' : '' }}>{{ $campus->campus_name ?? $campus }}</option>
                                    @endforeach
                                </select>
                                @if(request('filter_campus'))
                                    <button type="button" class="btn btn-sm position-absolute" onclick="clearFilter('filter_campus')" style="right: 25px; top: 50%; transform: translateY(-50%); padding: 0; width: 20px; height: 20px; background: transparent; border: none; color: #dc3545; z-index: 10;" title="Clear Campus">
                                        <span class="material-symbols-outlined" style="font-size: 16px;">close</span>
                                    </button>
                                @endif
                            </div>
                        @endif
                    </div>

                    <!-- Type Field -->
                    <div class="col-md-2">
                        <label for="filter_type" class="form-label mb-0 fs-13 fw-medium">Type</label>
                        <div class="position-relative">
                            <select class="form-select form-select-sm" id="filter_type" name="filter_type" style="height: 32px; padding-right: {{ request('filter_type') ? '30px' : '12px' }};">
                                <option value="">Select Type</option>
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

                    <!-- Class Field -->
                    <div class="col-md-2">
                        <label for="filter_class" class="form-label mb-0 fs-13 fw-medium">Class</label>
                        <div class="position-relative">
                            <select class="form-select form-select-sm" id="filter_class" name="filter_class" style="height: 32px; padding-right: {{ request('filter_class') ? '30px' : '12px' }};" {{ ($filterCampus ?? request('filter_campus')) ? '' : 'disabled' }}>
                                <option value="">{{ ($filterCampus ?? request('filter_campus')) ? 'Select Class' : 'Select Campus First' }}</option>
                                @foreach($classes as $class)
                                    <option value="{{ $class }}" @selected(strcasecmp(trim((string) (request('filter_class') ?? '')), trim((string) $class)) === 0)>{{ $class }}</option>
                                @endforeach
                            </select>
                            @if(request('filter_class'))
                                <button type="button" class="btn btn-sm position-absolute" onclick="clearFilter('filter_class')" style="right: 25px; top: 50%; transform: translateY(-50%); padding: 0; width: 20px; height: 20px; background: transparent; border: none; color: #dc3545; z-index: 10;" title="Clear Class">
                                    <span class="material-symbols-outlined" style="font-size: 16px;">close</span>
                                </button>
                            @endif
                        </div>
                    </div>

                    <!-- Section Field -->
                    <div class="col-md-2">
                        <label for="filter_section" class="form-label mb-0 fs-13 fw-medium">Section</label>
                        <div class="position-relative">
                            <select class="form-select form-select-sm" id="filter_section" name="filter_section" style="height: 32px; padding-right: {{ request('filter_section') ? '30px' : '12px' }};" {{ !request('filter_class') ? 'disabled' : '' }}>
                                <option value="">Select Section</option>
                                @if(request('filter_class'))
                                    @foreach($sections as $section)
                                        <option value="{{ $section }}" @selected(strcasecmp(trim((string) (request('filter_section') ?? '')), trim((string) $section)) === 0)>{{ $section }}</option>
                                    @endforeach
                                @endif
                            </select>
                            @if(request('filter_section'))
                                <button type="button" class="btn btn-sm position-absolute" onclick="clearFilter('filter_section')" style="right: 25px; top: 50%; transform: translateY(-50%); padding: 0; width: 20px; height: 20px; background: transparent; border: none; color: #dc3545; z-index: 10;" title="Clear Section">
                                    <span class="material-symbols-outlined" style="font-size: 16px;">close</span>
                                </button>
                            @endif
                        </div>
                    </div>

                    <!-- Date Field -->
                    <div class="{{ !empty($staffCampusLocked) ? 'col-md-2' : 'col-md-3' }}">
                        <label for="filter_date" class="form-label mb-0 fs-13 fw-medium">Date</label>
                        <div class="position-relative">
                            <input type="date" class="form-control form-control-sm" id="filter_date" name="filter_date" value="{{ request('filter_date', date('Y-m-d')) }}" style="height: 32px; padding-right: {{ request('filter_date') ? '30px' : '12px' }};">
                            @if(request('filter_date'))
                                <button type="button" class="btn btn-sm position-absolute" onclick="clearFilter('filter_date')" style="right: 5px; top: 50%; transform: translateY(-50%); padding: 0; width: 20px; height: 20px; background: transparent; border: none; color: #dc3545; z-index: 10;" title="Clear Date">
                                    <span class="material-symbols-outlined" style="font-size: 16px;">close</span>
                                </button>
                            @endif
                        </div>
                    </div>

                    <!-- Filter Button -->
                    <div class="col-md-1">
                        <label class="form-label mb-0 fs-13 fw-medium" style="visibility: hidden;">Filter</label>
                        <button type="submit" class="btn btn-sm w-100 filter-btn d-flex align-items-center justify-content-center gap-1" style="height: 32px; white-space: nowrap;">
                            <span class="material-symbols-outlined" style="font-size: 16px;">filter_list</span>
                            <span style="font-size: 13px;">Filter</span>
                        </button>
                    </div>
                </div>
            </form>

            <!-- Students Table - Only show when filters are applied -->
            @if(request('filter_class'))
            <div class="mt-3">
                <!-- Bulk Action Buttons and Notify Section -->
                <div class="mb-2 d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <!-- Bulk Action Buttons -->
                    <div class="d-flex gap-2 justify-content-center flex-wrap">
                        <button type="button" class="btn btn-sm text-white" style="background-color: #28a745; padding: 4px 12px; font-size: 12px;" onclick="markAllAttendance('Present')" title="Mark All as Present">
                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">check_circle</span>
                            Mark All Present
                        </button>
                        <button type="button" class="btn btn-sm text-white" style="background-color: #dc3545; padding: 4px 12px; font-size: 12px;" onclick="markAllAttendance('Absent')" title="Mark All as Absent">
                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">cancel</span>
                            Mark All Absent
                        </button>
                        <button type="button" class="btn btn-sm text-white" style="background-color: #ffc107; padding: 4px 12px; font-size: 12px;" onclick="markAllAttendance('Holiday')" title="Mark All as Holiday">
                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">event</span>
                            Mark All Holiday
                        </button>
                        <button type="button" class="btn btn-sm text-white" style="background-color: #17a2b8; padding: 4px 12px; font-size: 12px;" onclick="markAllAttendance('Sunday')" title="Mark All as Sunday">
                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">calendar_today</span>
                            Mark All Sunday
                        </button>
                    </div>
                    
                    <!-- Notify Late & Absent Students Section -->
                    <div class="d-flex align-items-center gap-3">
                        <div class="d-flex align-items-center gap-2">
                            <span class="material-symbols-outlined" style="font-size: 20px; color: #003471;">notifications</span>
                            <label class="form-label mb-0 fs-14 fw-semibold" style="color: #003471;">Notify Late & Absent Students:</label>
                        </div>
                        <select class="form-select form-select-sm" id="notifyLateAbsent" name="notify_late_absent" style="width: 100px;">
                            <option value="No">No</option>
                            <option value="Yes">Yes</option>
                        </select>
                        <button type="button" class="btn btn-sm notify-save-btn" id="saveNotifyPreferenceBtn" onclick="saveNotifyPreference()">
                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">save</span>
                            Save
                        </button>
                    </div>
                </div>
                
                <div class="mb-2 p-2 rounded-8" style="background: linear-gradient(135deg, #003471 0%, #004a9f 100%);">
                    <h5 class="mb-0 text-white fs-15 fw-semibold d-flex align-items-center gap-2">
                        <span class="material-symbols-outlined" style="font-size: 18px;">list</span>
                        <span>Students List</span>
                        <span class="badge bg-light text-dark ms-2">
                            {{ $students->count() }} {{ $students->count() == 1 ? 'student' : 'students' }} found
                        </span>
                    </h5>
                </div>

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
                                    <th style="padding: 8px 12px; font-size: 13px;">Attendance Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($students as $index => $student)
                                    <tr>
                                        <td style="padding: 8px 12px; font-size: 13px;">{{ $index + 1 }}</td>
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
                                            @php
                                                $attendanceStatus = $attendanceData[$student->id] ?? 'N/A';
                                                $studentWhatsappNumber = trim((string) ($student->admit_whatsapp_number ?? $student->whatsapp_number ?? ''));
                                                $statusClass = match($attendanceStatus) {
                                                    'Present' => 'bg-success',
                                                    'Absent' => 'bg-danger',
                                                    'Holiday' => 'bg-warning text-dark',
                                                    'Sunday' => 'bg-info',
                                                    'Leave' => 'bg-secondary',
                                                    default => 'bg-light text-dark'
                                                };
                                            @endphp
                                            <div class="d-flex flex-column gap-1 align-items-center">
                                                <span class="badge {{ $statusClass }}" style="font-size: 11px; margin-bottom: 3px;">
                                                    {{ $attendanceStatus }}
                                                </span>
                                                <div class="d-flex flex-wrap gap-1 justify-content-center" style="max-width: 200px;">
                                                    <button type="button" class="btn btn-sm status-btn status-present {{ $attendanceStatus == 'Present' ? 'active' : '' }}" onclick="updateAttendance(this, {{ $student->id }}, 'Present', '{{ request('filter_date', date('Y-m-d')) }}')" title="Mark as Present">
                                                        P
                                                    </button>
                                                    <button type="button" class="btn btn-sm status-btn status-absent {{ $attendanceStatus == 'Absent' ? 'active' : '' }}" onclick="updateAttendance(this, {{ $student->id }}, 'Absent', '{{ request('filter_date', date('Y-m-d')) }}')" title="Mark as Absent">
                                                        A
                                                    </button>
                                                    <button type="button" class="btn btn-sm status-btn status-holiday {{ $attendanceStatus == 'Holiday' ? 'active' : '' }}" onclick="updateAttendance(this, {{ $student->id }}, 'Holiday', '{{ request('filter_date', date('Y-m-d')) }}')" title="Mark as Holiday">
                                                        H
                                                    </button>
                                                    <button type="button" class="btn btn-sm status-btn status-sunday {{ $attendanceStatus == 'Sunday' ? 'active' : '' }}" onclick="updateAttendance(this, {{ $student->id }}, 'Sunday', '{{ request('filter_date', date('Y-m-d')) }}')" title="Mark as Sunday">
                                                        S
                                                    </button>
                                                    <button type="button" class="btn btn-sm status-btn status-leave {{ $attendanceStatus == 'Leave' ? 'active' : '' }}" onclick="updateAttendance(this, {{ $student->id }}, 'Leave', '{{ request('filter_date', date('Y-m-d')) }}')" title="Mark as Leave">
                                                        L
                                                    </button>
                                                    <button type="button" class="btn btn-sm status-btn status-na {{ $attendanceStatus == 'N/A' ? 'active' : '' }}" onclick="updateAttendance(this, {{ $student->id }}, 'N/A', '{{ request('filter_date', date('Y-m-d')) }}')" title="Mark as N/A">
                                                        N/A
                                                    </button>
                                                </div>
                                                <div class="mt-1">
                                                    @if($studentWhatsappNumber !== '')
                                                        <button
                                                            type="button"
                                                            class="btn btn-sm whatsapp-btn"
                                                            onclick='sendAttendanceWhatsApp(@json($studentWhatsappNumber), @json($student->student_name), @json($attendanceStatus), @json(request("filter_date", date("Y-m-d"))), @json($student->class ?? "N/A"), @json($student->section ?? "N/A"))'
                                                            title="Send attendance on WhatsApp (Admit Student number)"
                                                        >
                                                            <span class="material-symbols-outlined" style="font-size: 14px;">chat</span>
                                                            WhatsApp
                                                        </button>
                                                    @else
                                                        <button type="button" class="btn btn-sm whatsapp-btn disabled" disabled title="Admit Student > WhatsApp Number not found">
                                                            <span class="material-symbols-outlined" style="font-size: 14px;">chat</span>
                                                            No WhatsApp
                                                        </button>
                                                    @endif
                                                </div>
                                            </div>
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
            </div>
            @else
            <!-- Message when no filters applied -->
            <div class="text-center py-5 mt-3">
                <span class="material-symbols-outlined" style="font-size: 64px; color: #dee2e6; opacity: 0.5;">filter_list</span>
                <h5 class="mt-3 text-muted">Apply Filters to View Students</h5>
                <p class="text-muted mb-0">Please select Class and Date, then click "Filter" to view students list.</p>
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
    
    .filter-btn .material-symbols-outlined {
        color: white !important;
    }
    
    .rounded-8 {
        border-radius: 8px;
    }

    /* Teacher dashboard: campus fixed to staff profile — read-only, multi-line safe */
    .attendance-campus-locked {
        display: flex;
        align-items: flex-start;
        gap: 8px;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
        padding: 8px 10px;
        min-height: 38px;
        font-size: 13px;
        font-weight: 500;
        color: #0f172a;
        line-height: 1.45;
        white-space: normal;
        word-wrap: break-word;
        overflow-wrap: break-word;
    }
    .attendance-campus-locked__icon {
        font-size: 20px;
        color: #64748b;
        flex-shrink: 0;
        margin-top: 1px;
    }
    .attendance-campus-locked__text {
        flex: 1;
        min-width: 0;
    }

    /* Table Styling */
    .default-table-area .table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 0;
    }

    .default-table-area .table th,
    .default-table-area .table td {
        padding: 8px 12px;
        vertical-align: middle;
        border-top: 1px solid #e9ecef;
        font-size: 13px;
    }

    .default-table-area .table thead th {
        border-bottom: 2px solid #e9ecef;
        font-weight: 600;
        color: #495057;
        background-color: #f8f9fa;
    }

    .default-table-area .table tbody tr:nth-of-type(odd) {
        background-color: #ffffff;
    }

    .default-table-area .table tbody tr:nth-of-type(even) {
        background-color: #fdfdfd;
    }
    
    .table-hover tbody tr:hover {
        background-color: #f8f9fa;
    }
    
    /* Attendance Status Buttons */
    .status-btn {
        font-size: 10px;
        padding: 2px 5px;
        min-width: 26px;
        height: 22px;
        border: 1px solid #dee2e6;
        font-weight: 600;
        transition: all 0.2s ease;
        border-radius: 3px;
        line-height: 1;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
    
    .status-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    }
    
    .status-btn:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }
    
    .status-present {
        background-color: #d4edda;
        color: #155724;
        border-color: #c3e6cb;
    }
    
    .status-present:hover,
    .status-present.active {
        background-color: #28a745;
        color: white;
        border-color: #28a745;
    }
    
    .status-absent {
        background-color: #f8d7da;
        color: #721c24;
        border-color: #f5c6cb;
    }
    
    .status-absent:hover,
    .status-absent.active {
        background-color: #dc3545;
        color: white;
        border-color: #dc3545;
    }
    
    .status-holiday {
        background-color: #fff3cd;
        color: #856404;
        border-color: #ffeaa7;
    }
    
    .status-holiday:hover,
    .status-holiday.active {
        background-color: #ffc107;
        color: #000;
        border-color: #ffc107;
    }
    
    .status-sunday {
        background-color: #d1ecf1;
        color: #0c5460;
        border-color: #bee5eb;
    }
    
    .status-sunday:hover,
    .status-sunday.active {
        background-color: #17a2b8;
        color: white;
        border-color: #17a2b8;
    }
    
    .status-leave {
        background-color: #e2e3e5;
        color: #383d41;
        border-color: #d6d8db;
    }
    
    .status-leave:hover,
    .status-leave.active {
        background-color: #6c757d;
        color: white;
        border-color: #6c757d;
    }
    
    .status-na {
        background-color: #f8f9fa;
        color: #495057;
        border-color: #dee2e6;
    }
    
    .status-na:hover,
    .status-na.active {
        background-color: #adb5bd;
        color: white;
        border-color: #adb5bd;
    }

    .whatsapp-btn {
        background: #25d366;
        border: 1px solid #25d366;
        color: #fff;
        font-size: 11px;
        padding: 3px 8px;
        line-height: 1.1;
        border-radius: 4px;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }

    .whatsapp-btn:hover {
        background: #1ebe5b;
        border-color: #1ebe5b;
        color: #fff;
    }

    .notify-save-btn {
        background: #003471;
        border: 1px solid #003471;
        color: #fff;
        font-size: 12px;
        padding: 4px 10px;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }

    .notify-save-btn:hover {
        background: #004a9f;
        border-color: #004a9f;
        color: #fff;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const campusSelect = document.getElementById('filter_campus');
    const classSelect = document.getElementById('filter_class');
    const sectionSelect = document.getElementById('filter_section');
    
    const attendanceForm = document.getElementById('attendanceForm');

    function loadClasses(campusName, selectedClass = '') {
        if (!classSelect) {
            return Promise.resolve();
        }

        if (!campusName) {
            classSelect.innerHTML = '<option value="">Select Campus First</option>';
            classSelect.disabled = true;
            sectionSelect.innerHTML = '<option value="">Select Section</option>';
            sectionSelect.disabled = true;
            return Promise.resolve();
        }

        classSelect.innerHTML = '<option value="">Loading classes...</option>';
        classSelect.disabled = true;

        return fetch(`{{ route('attendance.student.get-classes-by-campus') }}?campus=${encodeURIComponent(campusName)}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            classSelect.innerHTML = '<option value="">Select Class</option>';
            if (data.classes && data.classes.length > 0) {
                data.classes.forEach(className => {
                    const option = document.createElement('option');
                    option.value = className;
                    option.textContent = className;
                    if (selectedClass && String(selectedClass).trim().toLowerCase() === String(className).trim().toLowerCase()) {
                        option.selected = true;
                    }
                    classSelect.appendChild(option);
                });
                classSelect.disabled = false;
            } else {
                classSelect.innerHTML = '<option value="">No classes found</option>';
                classSelect.disabled = true;
            }
        })
        .catch(error => {
            console.error('Error fetching classes:', error);
            classSelect.innerHTML = '<option value="">Error loading classes</option>';
            classSelect.disabled = true;
        });
    }

    // Function to load sections for selected class
    function loadSections(className, selectedSection = '') {
        if (!sectionSelect) {
            return Promise.resolve();
        }

        if (!className) {
            sectionSelect.innerHTML = '<option value="">Select Section</option>';
            sectionSelect.disabled = true;
            return Promise.resolve();
        }

        sectionSelect.innerHTML = '<option value="">Loading sections...</option>';
        sectionSelect.disabled = true;

        const params = new URLSearchParams();
        params.append('class', className);
        if (campusSelect && campusSelect.value) {
            params.append('campus', campusSelect.value);
        }
        const saved = selectedSection == null ? '' : String(selectedSection);

        return fetch(`{{ route('attendance.student.get-sections-by-class') }}?${params.toString()}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
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
                    if (saved.trim() !== '' && option.value.trim().toLowerCase() === saved.trim().toLowerCase()) {
                        option.selected = true;
                    }
                    sectionSelect.appendChild(option);
                });
                sectionSelect.disabled = false;
            } else {
                sectionSelect.innerHTML = '<option value="">No sections found</option>';
                sectionSelect.disabled = false;
            }
        })
        .catch(error => {
            console.error('Error fetching sections:', error);
            sectionSelect.innerHTML = '<option value="">Error loading sections</option>';
            sectionSelect.disabled = false;
        });
    }

    // After classes load, load sections so class options exist before section fetch (avoids race / empty GET params)
    const initialClass = @json(request('filter_class') ?? '');
    const initialSection = @json(request('filter_section') ?? '');
    const isStaffAttendanceUser = @json(!empty($isStaffAttendanceUser));
    const staffAllowedClasses = @json(($isStaffAttendanceUser ?? false) ? $classes->values() : null);

    function applyStaffClassOptions(selectedClass = '') {
        if (!isStaffAttendanceUser || !Array.isArray(staffAllowedClasses)) {
            return false;
        }
        classSelect.innerHTML = '<option value="">Select Class</option>';
        if (staffAllowedClasses.length === 0) {
            classSelect.innerHTML = '<option value="">No class assigned — contact admin</option>';
            classSelect.disabled = true;
            return true;
        }
        staffAllowedClasses.forEach(function (className) {
            const option = document.createElement('option');
            option.value = className;
            option.textContent = className;
            if (selectedClass && String(selectedClass).trim().toLowerCase() === String(className).trim().toLowerCase()) {
                option.selected = true;
            }
            classSelect.appendChild(option);
        });
        classSelect.disabled = false;
        return true;
    }

    if (applyStaffClassOptions(initialClass)) {
        if (initialClass) {
            loadSections(initialClass, initialSection);
        }
    } else {
    loadClasses(@json($filterCampus ?? ''), initialClass).then(function () {
        if (initialClass) {
            return loadSections(initialClass, initialSection);
        }
        return Promise.resolve();
    });
    }

    // Class change handler
    if (classSelect) {
        classSelect.addEventListener('change', function() {
            if (isStaffAttendanceUser && Array.isArray(staffAllowedClasses) && this.value) {
                const selected = String(this.value).trim().toLowerCase();
                const allowed = staffAllowedClasses.map(function (c) {
                    return String(c).trim().toLowerCase();
                });
                if (!allowed.includes(selected)) {
                    alert('You can only view attendance for your assigned class.');
                    this.value = '';
                    sectionSelect.innerHTML = '<option value="">Select Section</option>';
                    sectionSelect.disabled = true;
                    return;
                }
            }
            loadSections(this.value, '');
        });
    }

    @if(empty($staffCampusLocked))
    if (campusSelect) {
        campusSelect.addEventListener('change', function() {
            if (applyStaffClassOptions('')) {
                sectionSelect.innerHTML = '<option value="">Select Section</option>';
                sectionSelect.disabled = true;
                return;
            }
            loadClasses(this.value, '').then(function () {
                sectionSelect.innerHTML = '<option value="">Select Section</option>';
                sectionSelect.disabled = true;
            });
        });
    }
    @endif

    // Disabled <select> fields are omitted from GET — re-enable before submit so filter_campus/class/section are sent
    if (attendanceForm) {
        attendanceForm.addEventListener('submit', function () {
            if (classSelect) {
                classSelect.disabled = false;
            }
            if (sectionSelect) {
                sectionSelect.disabled = false;
            }
        });
    }

    // Restore saved notification preference for this browser.
    const notifySelect = document.getElementById('notifyLateAbsent');
    const savedNotifyPreference = localStorage.getItem('notify_late_absent_preference');
    if (notifySelect && savedNotifyPreference && ['Yes', 'No'].includes(savedNotifyPreference)) {
        notifySelect.value = savedNotifyPreference;
    }
});

function getSavedNotifyPreference() {
    const saved = localStorage.getItem('notify_late_absent_preference');
    return (saved === 'Yes' || saved === 'No') ? saved : 'No';
}

function getCurrentNotifyPreference() {
    const notifySelect = document.getElementById('notifyLateAbsent');
    const current = notifySelect ? notifySelect.value : null;
    if (current === 'Yes' || current === 'No') {
        return current;
    }
    return getSavedNotifyPreference();
}

function saveNotifyPreference() {
    const notifySelect = document.getElementById('notifyLateAbsent');
    const value = notifySelect ? notifySelect.value : 'No';
    localStorage.setItem('notify_late_absent_preference', value);
    alert(`Notify Late & Absent preference saved: ${value}`);
}

function clearFilter(filterName) {
    const url = new URL(window.location.href);
    url.searchParams.delete(filterName);
    url.searchParams.delete('page');
    
    // If clearing class, also clear section
    if (filterName === 'filter_class') {
        url.searchParams.delete('filter_section');
        const sectionSelect = document.getElementById('filter_section');
        if (sectionSelect) {
            sectionSelect.innerHTML = '<option value="">Select Section</option>';
            sectionSelect.disabled = true;
        }
    }

    if (filterName === 'filter_campus') {
        url.searchParams.delete('filter_class');
        url.searchParams.delete('filter_section');
        const classSelect = document.getElementById('filter_class');
        const sectionSelect = document.getElementById('filter_section');
        if (classSelect) {
            classSelect.innerHTML = '<option value="">Select Campus First</option>';
            classSelect.disabled = true;
        }
        if (sectionSelect) {
            sectionSelect.innerHTML = '<option value="">Select Section</option>';
            sectionSelect.disabled = true;
        }
    }
    
    // If clearing date, set it to today
    if (filterName === 'filter_date') {
        const today = new Date().toISOString().split('T')[0];
        url.searchParams.set('filter_date', today);
    }
    
    window.location.href = url.toString();
}

// Update attendance function
function updateAttendance(button, studentId, status, attendanceDate) {
    const originalText = button.innerHTML;
    const originalClass = button.className;
    
    // Show loading state
    button.disabled = true;
    button.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span>';
    
    // Get CSRF token
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || 
                      document.querySelector('input[name="_token"]')?.value;
    
    // Make AJAX request
    fetch('{{ route("attendance.student.store") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
            student_id: studentId,
            attendance_date: attendanceDate,
            status: status,
            notify_late_absent: getCurrentNotifyPreference()
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update dashboard if it's open in another tab/window
            updateDashboardStats();
            
            // Reload the page to show updated status
            window.location.reload();
        } else {
            alert('Error: ' + (data.message || 'Failed to update attendance'));
            button.disabled = false;
            button.innerHTML = originalText;
            button.className = originalClass;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error updating attendance. Please try again.');
        button.disabled = false;
        button.innerHTML = originalText;
        button.className = originalClass;
    });
}

// Function to update dashboard stats (for real-time updates)
function updateDashboardStats() {
    // Try to update dashboard if it's open in another tab/window
    if (window.opener && !window.opener.closed) {
        try {
            window.opener.postMessage({ type: 'updateAttendanceStats' }, '*');
        } catch(e) {
            console.log('Could not update parent window');
        }
    }
    
    // Also broadcast to same-origin windows
    if (window.BroadcastChannel) {
        const channel = new BroadcastChannel('attendance-updates');
        channel.postMessage({ type: 'updateAttendanceStats' });
    }
}

// Bulk attendance marking function
function markAllAttendance(status) {
    if (!confirm(`Are you sure you want to mark all students as "${status}"?`)) {
        return;
    }
    
    // Get all student IDs from the table
    const studentRows = document.querySelectorAll('tbody tr');
    const studentIds = [];
    
    studentRows.forEach(row => {
        const buttons = row.querySelectorAll('.status-btn');
        if (buttons.length > 0) {
            // Extract student ID from the onclick attribute
            const onclickAttr = buttons[0].getAttribute('onclick');
            if (onclickAttr) {
                const match = onclickAttr.match(/updateAttendance\([^,]+,\s*(\d+),/);
                if (match && match[1]) {
                    studentIds.push(parseInt(match[1]));
                }
            }
        }
    });
    
    if (studentIds.length === 0) {
        alert('No students found to update.');
        return;
    }
    
    // Get attendance date
    const attendanceDate = document.getElementById('filter_date').value || '{{ request('filter_date', date('Y-m-d')) }}';
    
    // Show loading state
    const buttons = document.querySelectorAll('.btn-sm');
    buttons.forEach(btn => btn.disabled = true);
    
    // Get CSRF token
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || 
                      document.querySelector('input[name="_token"]')?.value;
    
    // Make bulk update request
    fetch('{{ route("attendance.student.bulk-update") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
            student_ids: studentIds,
            attendance_date: attendanceDate,
            status: status,
            notify_late_absent: getCurrentNotifyPreference()
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update dashboard if it's open in another tab/window
            updateDashboardStats();
            
            // Reload the page to show updated status
            window.location.reload();
        } else {
            alert('Error: ' + (data.message || 'Failed to update attendance'));
            buttons.forEach(btn => btn.disabled = false);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error updating attendance. Please try again.');
        buttons.forEach(btn => btn.disabled = false);
    });
}

function sendAttendanceWhatsApp(rawPhone, studentName, attendanceStatus, attendanceDate, className, sectionName) {
    const phone = (rawPhone || '').replace(/\D/g, '');
    if (!phone) {
        alert('WhatsApp number not available for this student.');
        return;
    }

    const message =
`Assalam o Alaikum,
Student Attendance Update

Name: ${studentName}
Class: ${className}
Section: ${sectionName}
Date: ${attendanceDate}
Status: ${attendanceStatus}

Regards,
School Administration`;

    const waUrl = `https://wa.me/${phone}?text=${encodeURIComponent(message)}`;
    window.open(waUrl, '_blank');
}
</script>
@endsection
