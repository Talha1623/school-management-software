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

            <!-- Attendance Form -->
            <form method="GET" action="{{ route('attendance.student') }}" id="attendanceForm">
                <div class="row g-2 align-items-end">
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
                            <select class="form-select form-select-sm" id="filter_class" name="filter_class" style="height: 32px; padding-right: {{ request('filter_class') ? '30px' : '12px' }};">
                                <option value="">Select Class</option>
                                @foreach($classes as $class)
                                    <option value="{{ $class }}" {{ request('filter_class') == $class ? 'selected' : '' }}>{{ $class }}</option>
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
                                        <option value="{{ $section }}" {{ request('filter_section') == $section ? 'selected' : '' }}>{{ $section }}</option>
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
                    <div class="col-md-3">
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
                                                    <button type="button" class="btn btn-sm status-btn status-present {{ $attendanceStatus == 'Present' ? 'active' : '' }}" onclick="updateAttendance({{ $student->id }}, 'Present', '{{ request('filter_date', date('Y-m-d')) }}')" title="Mark as Present">
                                                        P
                                                    </button>
                                                    <button type="button" class="btn btn-sm status-btn status-absent {{ $attendanceStatus == 'Absent' ? 'active' : '' }}" onclick="updateAttendance({{ $student->id }}, 'Absent', '{{ request('filter_date', date('Y-m-d')) }}')" title="Mark as Absent">
                                                        A
                                                    </button>
                                                    <button type="button" class="btn btn-sm status-btn status-holiday {{ $attendanceStatus == 'Holiday' ? 'active' : '' }}" onclick="updateAttendance({{ $student->id }}, 'Holiday', '{{ request('filter_date', date('Y-m-d')) }}')" title="Mark as Holiday">
                                                        H
                                                    </button>
                                                    <button type="button" class="btn btn-sm status-btn status-sunday {{ $attendanceStatus == 'Sunday' ? 'active' : '' }}" onclick="updateAttendance({{ $student->id }}, 'Sunday', '{{ request('filter_date', date('Y-m-d')) }}')" title="Mark as Sunday">
                                                        S
                                                    </button>
                                                    <button type="button" class="btn btn-sm status-btn status-leave {{ $attendanceStatus == 'Leave' ? 'active' : '' }}" onclick="updateAttendance({{ $student->id }}, 'Leave', '{{ request('filter_date', date('Y-m-d')) }}')" title="Mark as Leave">
                                                        L
                                                    </button>
                                                    <button type="button" class="btn btn-sm status-btn status-na {{ $attendanceStatus == 'N/A' ? 'active' : '' }}" onclick="updateAttendance({{ $student->id }}, 'N/A', '{{ request('filter_date', date('Y-m-d')) }}')" title="Mark as N/A">
                                                        N/A
                                                    </button>
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
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const classSelect = document.getElementById('filter_class');
    const sectionSelect = document.getElementById('filter_section');
    
    // Function to load sections for selected class
    function loadSections(className) {
        if (!className) {
            sectionSelect.innerHTML = '<option value="">Select Section</option>';
            sectionSelect.disabled = true;
            return;
        }
        
        sectionSelect.innerHTML = '<option value="">Loading sections...</option>';
        sectionSelect.disabled = true;
        
        fetch(`{{ route('attendance.student.get-sections-by-class') }}?class=${encodeURIComponent(className)}`, {
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
                    // Preserve selected value if exists
                    @if(request('filter_section'))
                        if (option.value === '{{ request('filter_section') }}') {
                            option.selected = true;
                        }
                    @endif
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
    
    // Load sections on page load if class is already selected
    @if(request('filter_class'))
        loadSections('{{ request('filter_class') }}');
    @endif
    
    // Class change handler
    if (classSelect) {
        classSelect.addEventListener('change', function() {
            loadSections(this.value);
            // Clear section when class changes
            sectionSelect.value = '';
        });
    }
});

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
    
    // If clearing date, set it to today
    if (filterName === 'filter_date') {
        const today = new Date().toISOString().split('T')[0];
        url.searchParams.set('filter_date', today);
    }
    
    window.location.href = url.toString();
}

// Update attendance function
function updateAttendance(studentId, status, attendanceDate) {
    const button = event.target;
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
            status: status
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
</script>
@endsection
