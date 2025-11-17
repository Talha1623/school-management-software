@extends('layouts.app')

@section('title', 'Attendance Report')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-3 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0 fs-16 fw-semibold">Attendance Report</h4>
            </div>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <!-- Filter Form -->
            <form method="GET" action="{{ route('attendance.report') }}" id="filterForm">
                <div class="row g-2 mb-3 align-items-end">
                    <!-- Campus -->
                    <div class="col-md-3">
                        <label for="filter_campus" class="form-label mb-0 fs-13 fw-medium">Campus</label>
                        <div class="position-relative">
                            <select class="form-select form-select-sm" id="filter_campus" name="filter_campus" style="height: 32px; padding-right: {{ request('filter_campus') ? '30px' : '12px' }};">
                                <option value="">All Campuses</option>
                                @foreach($campuses as $campus)
                                    @php
                                        $campusName = is_object($campus) ? ($campus->campus_name ?? '') : $campus;
                                    @endphp
                                    <option value="{{ $campusName }}" {{ request('filter_campus') == $campusName ? 'selected' : '' }}>{{ $campusName }}</option>
                                @endforeach
                            </select>
                            @if(request('filter_campus'))
                                <button type="button" class="btn btn-sm position-absolute" onclick="clearFilter('filter_campus')" style="right: 25px; top: 50%; transform: translateY(-50%); padding: 0; width: 20px; height: 20px; background: transparent; border: none; color: #dc3545; z-index: 10;" title="Clear Campus">
                                    <span class="material-symbols-outlined" style="font-size: 16px;">close</span>
                                </button>
                            @endif
                        </div>
                    </div>

                    <!-- Class/Section -->
                    <div class="col-md-3">
                        <label for="filter_class_section" class="form-label mb-0 fs-13 fw-medium">Class/Section</label>
                        <div class="position-relative">
                            <select class="form-select form-select-sm" id="filter_class_section" name="filter_class_section" style="height: 32px; padding-right: {{ request('filter_class_section') ? '30px' : '12px' }};">
                                <option value="">Select Class/Section</option>
                                @foreach($classSectionOptions as $option)
                                    <option value="{{ $option['value'] }}" {{ request('filter_class_section') == $option['value'] ? 'selected' : '' }}>{{ $option['label'] }}</option>
                                @endforeach
                            </select>
                            @if(request('filter_class_section'))
                                <button type="button" class="btn btn-sm position-absolute" onclick="clearFilter('filter_class_section')" style="right: 25px; top: 50%; transform: translateY(-50%); padding: 0; width: 20px; height: 20px; background: transparent; border: none; color: #dc3545; z-index: 10;" title="Clear Class/Section">
                                    <span class="material-symbols-outlined" style="font-size: 16px;">close</span>
                                </button>
                            @endif
                        </div>
                    </div>

                    <!-- Month -->
                    <div class="col-md-2">
                        <label for="filter_month" class="form-label mb-0 fs-13 fw-medium">Month</label>
                        <div class="position-relative">
                            <select class="form-select form-select-sm" id="filter_month" name="filter_month" style="height: 32px; padding-right: {{ request('filter_month') ? '30px' : '12px' }};">
                                @foreach($months as $key => $month)
                                    <option value="{{ $key }}" {{ request('filter_month', date('m')) == $key ? 'selected' : '' }}>{{ $month }}</option>
                                @endforeach
                            </select>
                            @if(request('filter_month'))
                                <button type="button" class="btn btn-sm position-absolute" onclick="clearFilter('filter_month')" style="right: 25px; top: 50%; transform: translateY(-50%); padding: 0; width: 20px; height: 20px; background: transparent; border: none; color: #dc3545; z-index: 10;" title="Clear Month">
                                    <span class="material-symbols-outlined" style="font-size: 16px;">close</span>
                                </button>
                            @endif
                        </div>
                    </div>

                    <!-- Year -->
                    <div class="col-md-2">
                        <label for="filter_year" class="form-label mb-0 fs-13 fw-medium">Year</label>
                        <div class="position-relative">
                            <select class="form-select form-select-sm" id="filter_year" name="filter_year" style="height: 32px; padding-right: {{ request('filter_year') ? '30px' : '12px' }};">
                                @foreach($years as $year)
                                    <option value="{{ $year }}" {{ request('filter_year', date('Y')) == $year ? 'selected' : '' }}>{{ $year }}</option>
                                @endforeach
                            </select>
                            @if(request('filter_year'))
                                <button type="button" class="btn btn-sm position-absolute" onclick="clearFilter('filter_year')" style="right: 25px; top: 50%; transform: translateY(-50%); padding: 0; width: 20px; height: 20px; background: transparent; border: none; color: #dc3545; z-index: 10;" title="Clear Year">
                                    <span class="material-symbols-outlined" style="font-size: 16px;">close</span>
                                </button>
                            @endif
                        </div>
                    </div>

                    <!-- View Report Button -->
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-sm w-100 filter-btn" style="height: 32px;">
                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">assessment</span>
                            <span>View Report</span>
                        </button>
                    </div>
                </div>
            </form>

            <!-- Attendance Report - Only show when filters are applied -->
            @if(request('filter_campus') && request('filter_class_section'))
            <div class="mt-4">
                <!-- Report Header -->
                <div class="text-center mb-4" style="border-bottom: 2px solid #003471; padding-bottom: 20px;">
                    <div class="mb-3">
                        <div class="d-inline-block rounded-circle bg-success text-white d-flex align-items-center justify-content-center" style="width: 60px; height: 60px; font-size: 28px; font-weight: bold;">
                            C
                        </div>
                    </div>
                    <h2 class="mb-2 fw-bold" style="color: #003471; font-size: 28px;">ICMS</h2>
                    <h4 class="mb-3 fw-semibold" style="color: #495057; font-size: 18px;">Attendance Sheet</h4>
                    <div class="d-flex justify-content-center gap-4 flex-wrap" style="font-size: 14px; color: #6c757d;">
                        <span><strong>Campus:</strong> {{ request('filter_campus') }}</span>
                        <span><strong>Class:</strong> {{ $filterClass }}</span>
                        @if($filterSection)
                        <span><strong>Section:</strong> {{ $filterSection }}</span>
                        @endif
                        <span><strong>{{ $monthName }}, {{ $filterYear }}</strong></span>
                    </div>
                </div>

                <!-- Attendance Table -->
                @if($students->count() > 0)
                <div class="table-responsive" style="max-height: 600px; overflow-y: auto;">
                    <table class="table table-bordered table-sm" style="font-size: 12px;">
                        <thead style="background-color: #f8f9fa; position: sticky; top: 0; z-index: 10;">
                            <tr>
                                <th style="padding: 8px; text-align: center; min-width: 50px; border: 1px solid #dee2e6;">Roll</th>
                                <th style="padding: 8px; text-align: left; min-width: 150px; border: 1px solid #dee2e6;">Students</th>
                                <th style="padding: 8px; text-align: left; min-width: 150px; border: 1px solid #dee2e6;">
                                    Parent
                                    <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle; cursor: pointer;">swap_vert</span>
                                </th>
                                <th style="padding: 8px; text-align: center; border: 1px solid #dee2e6;">Date →</th>
                                @for($day = 1; $day <= $daysInMonth; $day++)
                                    <th style="padding: 4px; text-align: center; min-width: 30px; border: 1px solid #dee2e6; font-size: 11px;">{{ $day }}</th>
                                @endfor
                                <th style="padding: 8px; text-align: center; min-width: 80px; border: 1px solid #dee2e6; background-color: #e7f3ff;">Present Days</th>
                                <th style="padding: 8px; text-align: center; min-width: 80px; border: 1px solid #dee2e6; background-color: #ffe7e7;">Absent Days</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($students as $index => $student)
                                @php
                                    $presentDays = 0;
                                    $absentDays = 0;
                                    $studentAttendance = $attendanceData[$student->id] ?? [];
                                    foreach ($studentAttendance as $day => $status) {
                                        if ($status === 'P' || $status === 'p') {
                                            $presentDays++;
                                        } elseif ($status === 'A' || $status === 'a') {
                                            $absentDays++;
                                        }
                                    }
                                @endphp
                                <tr>
                                    <td style="padding: 8px; text-align: center; border: 1px solid #dee2e6;">
                                        {{ $student->student_code ?? $student->gr_number ?? ($index + 1) }}
                                    </td>
                                    <td style="padding: 8px; text-align: left; border: 1px solid #dee2e6;">
                                        <strong>{{ $student->student_name }}</strong>
                                        @if($student->surname_caste)
                                            <br><small class="text-muted">{{ $student->surname_caste }}</small>
                                        @endif
                                    </td>
                                    <td style="padding: 8px; text-align: left; border: 1px solid #dee2e6;">
                                        {{ $student->father_name ?? 'N/A' }}
                                    </td>
                                    <td style="padding: 8px; text-align: center; border: 1px solid #dee2e6; background-color: #f8f9fa;"></td>
                                    @for($day = 1; $day <= $daysInMonth; $day++)
                                        @php
                                            $attendanceStatus = $studentAttendance[$day] ?? '';
                                            $cellClass = '';
                                            if ($attendanceStatus === 'P' || $attendanceStatus === 'p') {
                                                $cellClass = 'bg-success text-white';
                                            } elseif ($attendanceStatus === 'A' || $attendanceStatus === 'a') {
                                                $cellClass = 'bg-danger text-white';
                                            }
                                        @endphp
                                        <td style="padding: 4px; text-align: center; border: 1px solid #dee2e6; {{ $cellClass ? 'background-color: ' . ($attendanceStatus === 'P' || $attendanceStatus === 'p' ? '#28a745' : '#dc3545') . '; color: white;' : '' }}">
                                            {{ $attendanceStatus ? strtoupper($attendanceStatus) : '' }}
                                        </td>
                                    @endfor
                                    <td style="padding: 8px; text-align: center; border: 1px solid #dee2e6; background-color: #e7f3ff; font-weight: bold;">
                                        {{ $presentDays }}
                                    </td>
                                    <td style="padding: 8px; text-align: center; border: 1px solid #dee2e6; background-color: #ffe7e7; font-weight: bold;">
                                        {{ $absentDays }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="text-center py-5">
                    <span class="material-symbols-outlined" style="font-size: 48px; opacity: 0.3;">school</span>
                    <p class="mt-2 mb-0">No students found for the selected filters.</p>
                </div>
                @endif
            </div>
            @else
            <!-- Message when filters are not fully applied -->
            <div class="text-center py-5 mt-3">
                <span class="material-symbols-outlined" style="font-size: 64px; color: #dee2e6; opacity: 0.5;">assessment</span>
                <h5 class="mt-3 text-muted">Apply Filters to View Report</h5>
                <p class="text-muted mb-0">Please select Campus, Class/Section, Month, and Year, then click "View Report" to generate the attendance report.</p>
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
    box-shadow: 0 2px 6px rgba(0, 52, 113, 0.2);
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

.form-select-sm {
    font-size: 13px;
    padding: 6px 12px;
    border-radius: 6px;
    border: 1px solid #dee2e6;
    transition: all 0.3s ease;
}

.form-select-sm:focus {
    border-color: #003471;
    box-shadow: 0 0 0 3px rgba(0, 52, 113, 0.15);
    outline: none;
}

/* Print Styles */
@media print {
    .card, .filter-btn, .form-select-sm, .form-label, #filterForm {
        display: none !important;
    }
    
    .table {
        font-size: 10px !important;
    }
    
    .table th, .table td {
        padding: 4px !important;
    }
}
</style>

<script>
function clearFilter(filterName) {
    const url = new URL(window.location.href);
    url.searchParams.delete(filterName);
    url.searchParams.delete('page');
    
    // If clearing class/section, reset it
    if (filterName === 'filter_class_section') {
        document.getElementById('filter_class_section').value = '';
    }
    
    // If clearing month, set to current month
    if (filterName === 'filter_month') {
        const currentMonth = new Date().getMonth() + 1;
        url.searchParams.set('filter_month', currentMonth.toString().padStart(2, '0'));
    }
    
    // If clearing year, set to current year
    if (filterName === 'filter_year') {
        url.searchParams.set('filter_year', new Date().getFullYear().toString());
    }
    
    window.location.href = url.toString();
}
</script>
@endsection
