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
            @if(isset($staffList) && $staffList->count() > 0)
                <form action="{{ route('attendance.staff.store') }}" method="POST" id="saveAttendanceForm">
                    @csrf
                    <input type="hidden" name="date" value="{{ $date }}">
                    <input type="hidden" name="campus" value="{{ $campus }}">
                    <input type="hidden" name="staff_category" value="{{ $staffCategory }}">
                    <input type="hidden" name="type" value="{{ $type }}">

                    <!-- Late Arrival Time - Single Input for All -->
                    <div class="card border-0 shadow-sm mb-3" style="border-radius: 8px; overflow: hidden;">
                        <div class="card-body p-3">
                            <div class="row align-items-center">
                                <div class="col-md-3">
                                    <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">
                                        Late Arrival Time
                                    </label>
                                    <input type="time" name="late_arrival_time" id="late_arrival_time" class="form-control form-control-sm" value="09:00" style="height: 32px;">
                                </div>
                                <div class="col-md-9">
                                    <small class="text-muted">This time will be used to calculate late arrival for all staff members.</small>
                                </div>
                            </div>
                        </div>
                    </div>

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
                                            <th style="padding: 8px 12px; font-size: 13px; font-weight: 600;">Attendance Status</th>
                                            <th style="padding: 8px 12px; font-size: 13px; font-weight: 600;">Arrival Timing</th>
                                            <th style="padding: 8px 12px; font-size: 13px; font-weight: 600;">Exit Timing</th>
                                            <th style="padding: 8px 12px; font-size: 13px; font-weight: 600;">Late Arrival</th>
                                            <th style="padding: 8px 12px; font-size: 13px; font-weight: 600;">Leave Deduction</th>
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
                                            @endphp
                                            <tr style="height: 60px;">
                                                <td style="padding: 8px 12px; font-size: 13px; height: 60px; vertical-align: middle;">{{ $staff->emp_id ?? 'N/A' }}</td>
                                                <td style="padding: 8px 12px; font-size: 13px; height: 60px; vertical-align: middle;"><strong>{{ $staff->name }}</strong></td>
                                                <td style="padding: 8px 12px; font-size: 13px; height: 60px; vertical-align: middle;">{{ $staff->father_husband_name ?? 'N/A' }}</td>
                                                <td style="padding: 8px 12px; font-size: 13px; height: 60px; vertical-align: middle;">{{ $staff->designation ?? 'N/A' }}</td>
                                                <td style="padding: 8px 12px; font-size: 13px; height: 60px; vertical-align: middle;">
                                                    <select name="attendance[{{ $staff->id }}][status]" class="form-select form-select-sm attendance-status" style="min-width: 120px;">
                                                        <option value="">Select Status</option>
                                                        <option value="Present" {{ $status == 'Present' ? 'selected' : '' }}>Present</option>
                                                        <option value="Absent" {{ $status == 'Absent' ? 'selected' : '' }}>Absent</option>
                                                        <option value="Leave" {{ $status == 'Leave' ? 'selected' : '' }}>Leave</option>
                                                        <option value="Half Day" {{ $status == 'Half Day' ? 'selected' : '' }}>Half Day</option>
                                                    </select>
                                                    <input type="hidden" name="attendance[{{ $staff->id }}][staff_id]" value="{{ $staff->id }}">
                                                </td>
                                                <td style="padding: 8px 12px; font-size: 13px; height: 60px; vertical-align: middle;">
                                                    <input type="time" name="attendance[{{ $staff->id }}][start_time]" class="form-control form-control-sm arrival-time" value="{{ $startTime ? date('H:i', strtotime($startTime)) : '' }}" style="min-width: 100px;">
                                                </td>
                                                <td style="padding: 8px 12px; font-size: 13px; height: 60px; vertical-align: middle;">
                                                    <input type="time" name="attendance[{{ $staff->id }}][end_time]" class="form-control form-control-sm exit-time" value="{{ $endTime ? date('H:i', strtotime($endTime)) : '' }}" style="min-width: 100px;">
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
                                                    <select name="attendance[{{ $staff->id }}][leave_deduction]" class="form-select form-select-sm" style="min-width: 100px;">
                                                        <option value="No" {{ (isset($attendance['leave_deduction']) && $attendance['leave_deduction'] == 'No') ? 'selected' : '' }}>No</option>
                                                        <option value="Yes" {{ (isset($attendance['leave_deduction']) && $attendance['leave_deduction'] == 'Yes') ? 'selected' : '' }}>Yes</option>
                                                    </select>
                                                </td>
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
            @elseif(request()->has('campus') || request()->has('staff_category') || request()->has('date'))
                <div class="alert alert-info">
                    <span class="material-symbols-outlined" style="vertical-align: middle;">info</span>
                    No staff found with the selected filters.
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
            // Get late arrival time from the input field at the top
            const lateArrivalTimeInput = document.getElementById('late_arrival_time');
            const standardTime = lateArrivalTimeInput ? lateArrivalTimeInput.value : '09:00';
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
