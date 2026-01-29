@extends('layouts.app')

@section('title', 'Staff Attendance')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-3 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0 fs-16 fw-semibold">Staff Attendance</h4>
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

            <!-- Attendance Form -->
            <div class="card border-0 shadow-sm mb-3" style="border-radius: 8px; overflow: hidden;">
                <div class="card-body p-3">
                    <form action="{{ route('attendance.staff') }}" method="GET" id="attendanceForm">
                        <div class="row g-3 align-items-end">
                            <!-- Campus Field -->
                            <div class="col-md-3">
                                <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">
                                    Campus
                                </label>
                                <select class="form-select form-select-sm" name="campus" id="campus">
                                    <option value="">Select Campus</option>
                                    @if(isset($campuses) && $campuses->count() > 0)
                                        @foreach($campuses as $campusOption)
                                            <option value="{{ $campusOption }}" {{ (isset($campus) && $campus == $campusOption) ? 'selected' : '' }}>{{ $campusOption }}</option>
                                        @endforeach
                                    @else
                                        {{-- Fallback: Get campuses only from Campus model (Manage Campuses page) --}}
                                        @php
                                            $campuses = \App\Models\Campus::whereNotNull('campus_name')
                                                ->orderBy('campus_name', 'asc')
                                                ->pluck('campus_name')
                                                ->unique()
                                                ->values();
                                        @endphp
                                        @foreach($campuses as $campusOption)
                                            <option value="{{ $campusOption }}" {{ (isset($campus) && $campus == $campusOption) ? 'selected' : '' }}>{{ $campusOption }}</option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>

                            <!-- Staff Category Field -->
                            <div class="col-md-3">
                                <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">
                                    Staff Category
                                </label>
                                <select class="form-select form-select-sm" name="staff_category" id="staff_category">
                                    <option value="">Select Staff Category</option>
                                    @php
                                        $categories = \App\Models\Staff::whereNotNull('designation')->distinct()->pluck('designation')->sort()->values();
                                        if ($categories->isEmpty()) {
                                            $categories = collect(['Teacher', 'Principal', 'Vice Principal', 'Admin', 'Accountant', 'Security', 'Clerk', 'Peon', 'Driver']);
                                        }
                                    @endphp
                                    @foreach($categories as $category)
                                        <option value="{{ $category }}" {{ (isset($staffCategory) && $staffCategory == $category) ? 'selected' : '' }}>{{ $category }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Type Field -->
                            <div class="col-md-2">
                                <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">
                                    Type
                                </label>
                                <select class="form-select form-select-sm" name="type" id="type">
                                    <option value="">Select Type</option>
                                    <option value="Normal Attendance" {{ (isset($type) && $type == 'Normal Attendance') ? 'selected' : '' }}>Normal Attendance</option>
                                    <option value="Subject Attendance" {{ (isset($type) && $type == 'Subject Attendance') ? 'selected' : '' }}>Subject Attendance</option>
                                    <option value="Biometric Attendance" {{ (isset($type) && $type == 'Biometric Attendance') ? 'selected' : '' }}>Biometric Attendance</option>
                                </select>
                            </div>

                            <!-- Date Field -->
                            <div class="col-md-2">
                                <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">
                                    Date
                                </label>
                                <input type="date" class="form-select form-select-sm" name="date" id="date" value="{{ isset($date) ? $date : date('Y-m-d') }}" style="height: 32px;">
                            </div>

                            <!-- Filter Button -->
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-sm w-100 filter-btn">
                                    <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">filter_list</span>
                                    <span>Filter</span>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Staff Attendance Table -->
            @if(isset($campus) && $campus)
                <div class="mb-2">
                    <span class="badge bg-primary">Campus: {{ $campus }}</span>
                </div>
                @if(isset($staffList) && $staffList->count() > 0)
                <form action="{{ route('attendance.staff.store') }}" method="POST" id="saveAttendanceForm">
                    @csrf
                    <input type="hidden" name="date" value="{{ $date }}">
                    <input type="hidden" name="campus" value="{{ $campus }}">
                    <input type="hidden" name="staff_category" value="{{ $staffCategory }}">
                    <input type="hidden" name="type" value="{{ $type }}">

                    @if(!isset($type) || $type !== 'Subject Attendance')
                        <div class="d-flex flex-wrap justify-content-center gap-2 mb-3">
                            <button type="button" class="btn btn-sm btn-success text-white px-2 py-1" onclick="markAllStaffStatus('Present')">
                                <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">check</span>
                                <span style="font-size: 12px;">Mark All Present</span>
                            </button>
                            <button type="button" class="btn btn-sm btn-danger text-white px-2 py-1" onclick="markAllStaffStatus('Absent')">
                                <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">close</span>
                                <span style="font-size: 12px;">Mark All Absent</span>
                            </button>
                            <button type="button" class="btn btn-sm btn-warning text-white px-2 py-1" onclick="markAllStaffStatus('Holiday')">
                                <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">event</span>
                                <span style="font-size: 12px;">Mark All Holiday</span>
                            </button>
                            <button type="button" class="btn btn-sm btn-primary text-white px-2 py-1" onclick="markAllStaffStatus('Sunday')">
                                <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">event_available</span>
                                <span style="font-size: 12px;">Mark All Sunday</span>
                            </button>
                        </div>
                    @endif

                    <div class="card border-0 shadow-sm mb-3" style="border-radius: 8px; overflow: hidden;">
                        <div class="card-body p-3">
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered table-hover" style="white-space: nowrap;">
                                    <thead style="background: linear-gradient(135deg, #003471 0%, #004a9f 100%); color: white;">
                                        <tr>
                                            <th style="padding: 8px 12px; font-size: 13px; font-weight: 600;">Emp. ID</th>
                                            <th style="padding: 8px 12px; font-size: 13px; font-weight: 600;">Name</th>
                                            <th style="padding: 8px 12px; font-size: 13px; font-weight: 600;">Father/Husband</th>
                                            <th style="padding: 8px 12px; font-size: 13px; font-weight: 600;">Designation</th>
                                            @if(isset($type) && $type === 'Subject Attendance')
                                                <th style="padding: 8px 12px; font-size: 13px; font-weight: 600;">Assigned Subjects ({{ $dateLabel ?? '' }})</th>
                                                <th style="padding: 8px 12px; font-size: 13px; font-weight: 600;">Conducted Lectures ({{ $dateLabel ?? '' }})</th>
                                                <th style="padding: 8px 12px; font-size: 13px; font-weight: 600;">Late Arrival</th>
                                            @else
                                                <th style="padding: 8px 12px; font-size: 13px; font-weight: 600;">Attendance Status</th>
                                                <th style="padding: 8px 12px; font-size: 13px; font-weight: 600;">Arrival Timing</th>
                                                <th style="padding: 8px 12px; font-size: 13px; font-weight: 600;">Exit Timing</th>
                                                <th style="padding: 8px 12px; font-size: 13px; font-weight: 600;">Late Arrival</th>
                                                <th style="padding: 8px 12px; font-size: 13px; font-weight: 600;">Early Exit</th>
                                                <th style="padding: 8px 12px; font-size: 13px; font-weight: 600;">Leave Deduction</th>
                                            @endif
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($staffList as $staff)
                                            @php
                                                $attendance = $attendanceData[$staff->id] ?? null;
                                                $status = $attendance['status'] ?? '';
                                                $startTime = $attendance['start_time'] ?? '';
                                                $endTime = $attendance['end_time'] ?? '';
                                                $lateArrival = $attendance['late_arrival'] ?? null;
                                                $earlyExit = $attendance['early_exit'] ?? null;
                                                $conductedLectures = $attendance['conducted_lectures'] ?? '';
                                                $assignedSubjects = $assignedSubjectsByStaff[$staff->id] ?? [];
                                            @endphp
                                            <tr style="height: 60px;">
                                                <td style="padding: 8px 12px; font-size: 13px; height: 60px; vertical-align: middle;">{{ $staff->emp_id ?? 'N/A' }}</td>
                                                <td style="padding: 8px 12px; font-size: 13px; height: 60px; vertical-align: middle;"><strong>{{ $staff->name }}</strong></td>
                                                <td style="padding: 8px 12px; font-size: 13px; height: 60px; vertical-align: middle;">{{ $staff->father_husband_name ?? 'N/A' }}</td>
                                                <td style="padding: 8px 12px; font-size: 13px; height: 60px; vertical-align: middle;">{{ $staff->designation ?? 'N/A' }}</td>
                                                <input type="hidden" name="attendance[{{ $staff->id }}][staff_id]" value="{{ $staff->id }}">
                                                @if(isset($type) && $type === 'Subject Attendance')
                                                    <td style="padding: 8px 12px; font-size: 13px; height: 60px; vertical-align: middle; min-width: 220px;">
                                                        <span class="badge bg-info text-white" style="font-size: 12px;">
                                                            {{ !empty($assignedSubjects) ? count($assignedSubjects) : 0 }}
                                                        </span>
                                                    </td>
                                                    <td style="padding: 8px 12px; font-size: 13px; height: 60px; vertical-align: middle;">
                                                        <input type="number" min="0" class="form-control form-control-sm" name="attendance[{{ $staff->id }}][conducted_lectures]" value="{{ $conductedLectures !== null ? $conductedLectures : '' }}" style="width: 120px;">
                                                    </td>
                                                    <td style="padding: 8px 12px; font-size: 13px; height: 60px; vertical-align: middle;">
                                                        <select name="attendance[{{ $staff->id }}][late_arrival]" class="form-select form-select-sm" style="min-width: 90px;">
                                                            <option value="Auto">Auto</option>
                                                            <option value="Yes">Yes</option>
                                                            <option value="No">No</option>
                                                        </select>
                                                    </td>
                                                @else
                                                    <td style="padding: 8px 12px; font-size: 13px; height: 60px; vertical-align: middle;">
                                                        <select name="attendance[{{ $staff->id }}][status]" class="form-select form-select-sm attendance-status" style="min-width: 120px;">
                                                            <option value="">Select Status</option>
                                                            <option value="Present" {{ $status == 'Present' ? 'selected' : '' }}>Present</option>
                                                            <option value="Absent" {{ $status == 'Absent' ? 'selected' : '' }}>Absent</option>
                                                            <option value="Holiday" {{ $status == 'Holiday' ? 'selected' : '' }}>Holiday</option>
                                                            <option value="Sunday" {{ $status == 'Sunday' ? 'selected' : '' }}>Sunday</option>
                                                            <option value="Leave" {{ $status == 'Leave' ? 'selected' : '' }}>Leave</option>
                                                            <option value="Half Day" {{ $status == 'Half Day' ? 'selected' : '' }}>Half Day</option>
                                                        </select>
                                                    </td>
                                                    <td style="padding: 8px 12px; font-size: 13px; height: 60px; vertical-align: middle;">
                                                        <input type="time" name="attendance[{{ $staff->id }}][start_time]" class="form-control form-control-sm arrival-time" value="{{ $startTime ? date('H:i', strtotime($startTime)) : '' }}" style="min-width: 100px;">
                                                    </td>
                                                    <td style="padding: 8px 12px; font-size: 13px; height: 60px; vertical-align: middle;">
                                                        @php
                                                            $exitTimeValue = '';
                                                            if ($endTime) {
                                                                if (is_object($endTime) && method_exists($endTime, 'format')) {
                                                                    $exitTimeValue = $endTime->format('H:i');
                                                                } elseif (is_string($endTime)) {
                                                                    try {
                                                                        $exitTimeValue = date('H:i', strtotime($endTime));
                                                                    } catch (\Exception $e) {
                                                                        $exitTimeValue = $endTime;
                                                                    }
                                                                }
                                                            }
                                                        @endphp
                                                        <input type="time" name="attendance[{{ $staff->id }}][end_time]" class="form-control form-control-sm exit-time" value="{{ $exitTimeValue }}" style="min-width: 100px;" placeholder="HH:MM" data-early-exit-time="{{ !empty($earlyExitTime) ? $earlyExitTime : '' }}">
                                                    </td>
                                                    <td style="padding: 8px 12px; font-size: 13px; height: 60px; vertical-align: middle;">
                                                        <div class="d-flex flex-column gap-2">
                                                            <select name="attendance[{{ $staff->id }}][auto_late_arrival]" class="form-select form-select-sm auto-late-arrival" style="min-width: 80px;">
                                                                <option value="Auto" selected>Auto</option>
                                                                <option value="Yes">Yes</option>
                                                                <option value="No">No</option>
                                                            </select>
                                                            <div class="late-arrival-display" style="min-height: 20px;">
                                                                @if($lateArrival)
                                                                    <span class="badge bg-warning text-dark">{{ $lateArrival }}</span>
                                                                @else
                                                                    <span class="text-muted">-</span>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td style="padding: 8px 12px; font-size: 13px; height: 60px; vertical-align: middle;">
                                                        <div class="d-flex flex-column gap-2">
                                                            <select name="attendance[{{ $staff->id }}][auto_early_exit]" class="form-select form-select-sm auto-early-exit" style="min-width: 80px;">
                                                                <option value="Auto" selected>Auto</option>
                                                                <option value="Yes">Yes</option>
                                                                <option value="No">No</option>
                                                            </select>
                                                            <div class="early-exit-display" style="min-height: 20px;">
                                                                @if($earlyExit)
                                                                    <span class="badge bg-danger text-white">{{ $earlyExit }}</span>
                                                                @else
                                                                    <span class="text-muted">-</span>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td style="padding: 8px 12px; font-size: 13px; height: 60px; vertical-align: middle;">
                                                        <select name="attendance[{{ $staff->id }}][leave_deduction]" class="form-select form-select-sm" style="min-width: 100px;">
                                                            <option value="No" {{ (isset($attendance['leave_deduction']) && $attendance['leave_deduction'] == 'No') ? 'selected' : '' }}>No</option>
                                                            <option value="Yes" {{ (isset($attendance['leave_deduction']) && $attendance['leave_deduction'] == 'Yes') ? 'selected' : '' }}>Yes</option>
                                                        </select>
                                                    </td>
                                                @endif
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Save Button -->
                    <div class="d-flex justify-content-end mb-3">
                        <button type="submit" class="btn btn-lg save-btn">
                            <span class="material-symbols-outlined" style="font-size: 18px; vertical-align: middle;">save</span>
                            <span>Save Attendance</span>
                        </button>
                    </div>
                </form>
                @else
                    <div class="alert alert-info">
                        <span class="material-symbols-outlined" style="vertical-align: middle;">info</span>
                        No staff found with the selected filters.
                    </div>
                @endif
            @else
                <div class="alert alert-info">
                    <span class="material-symbols-outlined" style="vertical-align: middle;">info</span>
                    Select a campus and click Filter to view staff attendance.
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
    
    input[type="date"].form-select-sm {
        font-size: 13px;
        padding: 6px 12px;
        border-radius: 6px;
        border: 1px solid #dee2e6;
        transition: all 0.3s ease;
        height: 32px;
    }
    
    input[type="date"].form-select-sm:focus {
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

    /* Table Styling */
    .table thead th {
        border: none;
        white-space: nowrap;
    }

    .table tbody td {
        vertical-align: middle;
    }

    .table .form-select-sm,
    .table .form-control-sm {
        font-size: 12px;
        padding: 4px 8px;
        height: 28px;
    }

    /* Save Button Styling */
    .save-btn {
        background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        color: white;
        border: none;
        font-weight: 600;
        padding: 12px 30px;
        border-radius: 8px;
        transition: all 0.3s ease;
        box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
        font-size: 16px;
    }

    .save-btn:hover {
        background: linear-gradient(135deg, #20c997 0%, #28a745 100%);
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(40, 167, 69, 0.4);
        color: white;
    }

    .save-btn:active {
        transform: translateY(0);
    }

    .save-btn .material-symbols-outlined {
        color: white !important;
    }
</style>

<script>
function markAllStaffStatus(status) {
    const statusSelects = document.querySelectorAll('.attendance-status');
    statusSelects.forEach(select => {
        select.value = status;
    });
}

// Calculate late arrival when arrival time changes (only if Auto is Yes)
document.addEventListener('DOMContentLoaded', function() {
    const arrivalTimeInputs = document.querySelectorAll('.arrival-time');
    
    function calculateLateArrival(input) {
        const row = input.closest('tr');
        const autoSelect = row.querySelector('.auto-late-arrival');
        const lateArrivalDisplay = row.querySelector('.late-arrival-display');
        
        if (!autoSelect || !lateArrivalDisplay) {
            return;
        }
        
        // If Auto is No, don't calculate
        if (autoSelect.value === 'No') {
            lateArrivalDisplay.innerHTML = '<span class="text-muted">-</span>';
            return;
        }
        
        // If Auto is "Auto" or "Yes", calculate automatically
        if (autoSelect.value !== 'Auto' && autoSelect.value !== 'Yes') {
            lateArrivalDisplay.innerHTML = '<span class="text-muted">-</span>';
            return;
        }
        
        const time = input.value;
        if (time) {
            // Get late arrival time from Salary Setting (passed from controller)
            const standardTime = '{{ $lateArrivalTime ?? "09:00" }}';
            const [hours, minutes] = time.split(':');
            const [stdHours, stdMinutes] = standardTime.split(':');
            
            const timeInMinutes = parseInt(hours) * 60 + parseInt(minutes);
            const stdTimeInMinutes = parseInt(stdHours) * 60 + parseInt(stdMinutes);
            
            if (timeInMinutes > stdTimeInMinutes) {
                const diff = timeInMinutes - stdTimeInMinutes;
                const lateHours = Math.floor(diff / 60);
                const lateMinutes = diff % 60;
                const lateArrival = String(lateHours).padStart(2, '0') + ':' + String(lateMinutes).padStart(2, '0');
                
                // Update late arrival display in Auto column
                lateArrivalDisplay.innerHTML = '<span class="badge bg-warning text-dark">' + lateArrival + '</span>';
            } else {
                lateArrivalDisplay.innerHTML = '<span class="text-muted">-</span>';
            }
        } else {
            lateArrivalDisplay.innerHTML = '<span class="text-muted">-</span>';
        }
    }
    
    arrivalTimeInputs.forEach(input => {
        input.addEventListener('change', function() {
            calculateLateArrival(this);
        });
    });
    
    // Calculate early exit when exit time changes
    function calculateEarlyExit(input) {
        if (!input) {
            return;
        }
        
        const row = input.closest('tr');
        if (!row) {
            return;
        }
        
        const autoSelect = row.querySelector('.auto-early-exit');
        const earlyExitDisplay = row.querySelector('.early-exit-display');
        
        if (!earlyExitDisplay) {
            return;
        }
        
        // If Auto dropdown exists and is set to No, don't calculate
        if (autoSelect && autoSelect.value === 'No') {
            earlyExitDisplay.innerHTML = '<span class="text-muted">-</span>';
            return;
        }
        
        // If Auto dropdown exists, check if it's Auto or Yes
        if (autoSelect && autoSelect.value !== 'Auto' && autoSelect.value !== 'Yes') {
            earlyExitDisplay.innerHTML = '<span class="text-muted">-</span>';
            return;
        }
        
        const time = input.value;
        // Get early exit time from data attribute first
        let earlyExitTimeSetting = input.getAttribute('data-early-exit-time');
        
        // If data attribute is empty or invalid, use the global variable from controller
        if (!earlyExitTimeSetting || earlyExitTimeSetting === '' || earlyExitTimeSetting === 'null' || earlyExitTimeSetting.trim() === '') {
            // Get from blade variable (formatted time from controller)
            const bladeEarlyExitTime = '{{ !empty($earlyExitTime) ? $earlyExitTime : "" }}';
            if (bladeEarlyExitTime && bladeEarlyExitTime !== '') {
                earlyExitTimeSetting = bladeEarlyExitTime;
            }
        }
        
        // Trim whitespace
        if (earlyExitTimeSetting) {
            earlyExitTimeSetting = earlyExitTimeSetting.trim();
        }
        
        // Check if exit time is provided (time input returns HH:MM format)
        if (!time || time === '' || time === '--:--' || time.length < 5) {
            earlyExitDisplay.innerHTML = '<span class="text-muted">-</span>';
            return;
        }
        
        // Check if early exit time is set in salary settings
        if (!earlyExitTimeSetting || earlyExitTimeSetting === '' || earlyExitTimeSetting === 'null' || earlyExitTimeSetting === 'undefined') {
            // Early exit time not set in salary settings
            earlyExitDisplay.innerHTML = '<span class="text-muted">-</span>';
            return;
        }
        
        // Debug: Log for troubleshooting (can be removed later)
        console.log('Early Exit Calculation:', {
            exitTime: time,
            earlyExitTimeSetting: earlyExitTimeSetting,
            hasSetting: earlyExitTimeSetting && earlyExitTimeSetting !== ''
        });
        
        try {
            // Parse exit time (format: HH:MM from time input)
            const timeParts = time.split(':');
            if (timeParts.length < 2) {
                earlyExitDisplay.innerHTML = '<span class="text-muted">-</span>';
                return;
            }
            
            const hours = parseInt(timeParts[0], 10);
            const minutes = parseInt(timeParts[1], 10);
            
            if (isNaN(hours) || isNaN(minutes) || hours < 0 || hours > 23 || minutes < 0 || minutes > 59) {
                earlyExitDisplay.innerHTML = '<span class="text-muted">-</span>';
                return;
            }
            
            // Parse early exit time setting (format: HH:MM in 24-hour format)
            const settingParts = earlyExitTimeSetting.split(':');
            if (settingParts.length < 2) {
                earlyExitDisplay.innerHTML = '<span class="text-muted">-</span>';
                return;
            }
            
            let stdHours = parseInt(settingParts[0], 10);
            const stdMinutes = parseInt(settingParts[1], 10);
            
            // Handle 12-hour format if needed (should already be converted by controller, but double-check)
            // If hours is 1-12 and no indication of 24-hour format, check if it might be PM
            // But since controller already converts, this should be 24-hour format (0-23)
            
            if (isNaN(stdHours) || isNaN(stdMinutes) || stdHours < 0 || stdHours > 23 || stdMinutes < 0 || stdMinutes > 59) {
                earlyExitDisplay.innerHTML = '<span class="text-muted">-</span>';
                return;
            }
            
            // Convert to minutes for comparison
            const timeInMinutes = hours * 60 + minutes;
            const stdTimeInMinutes = stdHours * 60 + stdMinutes;
            
            // Check if exit time is before early exit time setting
            // Both times are now in 24-hour format (0-23 hours)
            console.log('Time Comparison:', {
                exitTime: time,
                exitTimeHours: hours,
                exitTimeMinutes: timeInMinutes,
                earlyExitTimeSetting: earlyExitTimeSetting,
                earlyExitTimeHours: stdHours,
                earlyExitTimeMinutes: stdTimeInMinutes,
                isEarly: timeInMinutes < stdTimeInMinutes,
                difference: stdTimeInMinutes - timeInMinutes
            });
            
            if (timeInMinutes < stdTimeInMinutes) {
                const diff = stdTimeInMinutes - timeInMinutes;
                const earlyHours = Math.floor(diff / 60);
                const earlyMinutes = diff % 60;
                const earlyExit = String(earlyHours).padStart(2, '0') + ':' + String(earlyMinutes).padStart(2, '0');
                
                console.log('Early Exit Calculated:', earlyExit);
                
                // Update early exit display immediately
                earlyExitDisplay.innerHTML = '<span class="badge bg-danger text-white">' + earlyExit + '</span>';
            } else {
                // Exit time is on or after the early exit time, so no early exit
                console.log('No early exit - exit time is on or after early exit time');
                earlyExitDisplay.innerHTML = '<span class="text-muted">-</span>';
            }
        } catch (e) {
            earlyExitDisplay.innerHTML = '<span class="text-muted">-</span>';
        }
    }
    
    // Also calculate when Exit Time changes (recalculate late arrival and early exit)
    const exitTimeInputs = document.querySelectorAll('.exit-time');
    
    // Calculate early exit on page load for existing values (with delay to ensure DOM is ready)
    setTimeout(function() {
        exitTimeInputs.forEach(input => {
            if (input && input.value && input.value.length >= 5) {
                calculateEarlyExit(input);
            }
        });
    }, 500);
    
    exitTimeInputs.forEach(input => {
        // Calculate on change event - immediate calculation
        input.addEventListener('change', function() {
            const row = this.closest('tr');
            const arrivalInput = row.querySelector('.arrival-time');
            if (arrivalInput && arrivalInput.value) {
                calculateLateArrival(arrivalInput);
            }
            // Immediately calculate early exit when exit time changes
            calculateEarlyExit(this);
        });
        
        // Also calculate on input event for real-time updates - immediate
        input.addEventListener('input', function() {
            calculateEarlyExit(this);
        });
        
        // Calculate when field gets focus (if value exists)
        input.addEventListener('focus', function() {
            if (this.value) {
                calculateEarlyExit(this);
            }
        });
        
        // Calculate when field loses focus - immediate
        input.addEventListener('blur', function() {
            calculateEarlyExit(this);
        });
        
        // Also listen for keyup to catch manual time entry
        input.addEventListener('keyup', function() {
            if (this.value && this.value.length >= 5) {
                calculateEarlyExit(this);
            }
        });
    });
    
    // Also calculate when Auto dropdown changes for early exit
    const autoEarlyExitSelects = document.querySelectorAll('.auto-early-exit');
    autoEarlyExitSelects.forEach(select => {
        // Calculate on page load if exit time exists
        setTimeout(() => {
            const row = select.closest('tr');
            const exitInput = row ? row.querySelector('.exit-time') : null;
            if (exitInput && exitInput.value) {
                calculateEarlyExit(exitInput);
            }
        }, 350);
        
        select.addEventListener('change', function() {
            const row = this.closest('tr');
            const exitInput = row ? row.querySelector('.exit-time') : null;
            // Immediately calculate when dropdown changes
            calculateEarlyExit(exitInput);
        });
    });
    
    // Also calculate when Auto dropdown changes
    const autoSelects = document.querySelectorAll('.auto-late-arrival');
    autoSelects.forEach(select => {
        select.addEventListener('change', function() {
            const row = this.closest('tr');
            const arrivalInput = row.querySelector('.arrival-time');
            if (arrivalInput && arrivalInput.value) {
                calculateLateArrival(arrivalInput);
            }
        });
    });

    // Handle save form submission
    const saveForm = document.getElementById('saveAttendanceForm');
    if (saveForm) {
        saveForm.addEventListener('submit', function(e) {
            const typeSelect = document.getElementById('type');
            const isSubjectAttendance = typeSelect && typeSelect.value === 'Subject Attendance';

            if (isSubjectAttendance) {
                const lectureInputs = document.querySelectorAll('input[name$="[conducted_lectures]"]');
                let hasLectureValue = false;
                lectureInputs.forEach(input => {
                    if (input.value !== '') {
                        hasLectureValue = true;
                    }
                });

                if (!hasLectureValue) {
                    e.preventDefault();
                    alert('Please enter conducted lectures before saving.');
                    return false;
                }
                return;
            }

            // Validate that at least one attendance status is selected
            const statusSelects = document.querySelectorAll('.attendance-status');
            let hasSelection = false;
            
            statusSelects.forEach(select => {
                if (select.value) {
                    hasSelection = true;
                }
            });

            if (!hasSelection) {
                e.preventDefault();
                alert('Please select at least one attendance status before saving.');
                return false;
            }
            
            // Allow form to submit - only entries with status will be saved
        });
    }
});
</script>
@endsection
