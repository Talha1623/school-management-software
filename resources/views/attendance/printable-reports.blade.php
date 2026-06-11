@extends('layouts.app')

@section('title', 'All Attendance Reports')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-3 mb-4">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
                <h4 class="mb-0 fs-16 fw-semibold">Printable Attendance Reports</h4>
                <div class="d-flex flex-wrap gap-3 align-items-end justify-content-end">
                    <form method="get" action="{{ route('attendance.staff-summary.print') }}" target="_blank" class="d-flex flex-wrap align-items-center gap-2">
                        <span class="fs-12 text-muted">Staff month:</span>
                        <select name="month" class="form-select form-select-sm" style="width: auto; min-width: 110px;">
                            @foreach(range(1, 12) as $m)
                                <option value="{{ $m }}" {{ (int) date('n') === $m ? 'selected' : '' }}>{{ \Carbon\Carbon::createFromDate(2000, $m, 1)->format('F') }}</option>
                            @endforeach
                        </select>
                        <select name="year" class="form-select form-select-sm" style="width: auto; min-width: 85px;">
                            @for($y = (int) date('Y'); $y >= (int) date('Y') - 5; $y--)
                                <option value="{{ $y }}" {{ (int) date('Y') === $y ? 'selected' : '' }}>{{ $y }}</option>
                            @endfor
                        </select>
                        <input type="hidden" name="auto_print" value="1">
                        <button type="submit" class="btn btn-sm btn-primary" style="background: linear-gradient(135deg, #003471 0%, #004a9f 100%); border: none;">
                            <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">print</span>
                            Staff summary
                        </button>
                    </form>
                    <form method="get" action="{{ route('attendance.student-summary.print') }}" target="_blank" class="d-flex flex-wrap align-items-center gap-2">
                        <span class="fs-12 text-muted">Student month:</span>
                        <select name="month" class="form-select form-select-sm" style="width: auto; min-width: 110px;">
                            @foreach(range(1, 12) as $m)
                                <option value="{{ $m }}" {{ (int) date('n') === $m ? 'selected' : '' }}>{{ \Carbon\Carbon::createFromDate(2000, $m, 1)->format('F') }}</option>
                            @endforeach
                        </select>
                        <select name="year" class="form-select form-select-sm" style="width: auto; min-width: 85px;">
                            @for($y = (int) date('Y'); $y >= (int) date('Y') - 5; $y--)
                                <option value="{{ $y }}" {{ (int) date('Y') === $y ? 'selected' : '' }}>{{ $y }}</option>
                            @endfor
                        </select>
                        <input type="hidden" name="auto_print" value="1">
                        <button type="submit" class="btn btn-sm btn-primary" style="background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%); border: none;">
                            <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">print</span>
                            Student summary
                        </button>
                    </form>
                    <form method="get" action="{{ route('attendance.subject-lecture-summary.print') }}" target="_blank" class="d-flex flex-wrap align-items-center gap-2">
                        <span class="fs-12 text-muted">Lecture month:</span>
                        <select name="month" class="form-select form-select-sm" style="width: auto; min-width: 110px;">
                            @foreach(range(1, 12) as $m)
                                <option value="{{ $m }}" {{ (int) date('n') === $m ? 'selected' : '' }}>{{ \Carbon\Carbon::createFromDate(2000, $m, 1)->format('F') }}</option>
                            @endforeach
                        </select>
                        <select name="year" class="form-select form-select-sm" style="width: auto; min-width: 85px;">
                            @for($y = (int) date('Y'); $y >= (int) date('Y') - 5; $y--)
                                <option value="{{ $y }}" {{ (int) date('Y') === $y ? 'selected' : '' }}>{{ $y }}</option>
                            @endfor
                        </select>
                        <select name="filter_campus" class="form-select form-select-sm" style="width: auto; min-width: 130px;" title="Campus filter">
                            <option value="">All campuses</option>
                            @foreach($lectureCampuses ?? [] as $c)
                                <option value="{{ $c->campus_name }}">{{ $c->campus_name }}</option>
                            @endforeach
                        </select>
                        <input type="hidden" name="auto_print" value="1">
                        <button type="submit" class="btn btn-sm btn-primary" style="background: linear-gradient(135deg, #003471 0%, #004a9f 100%); border: none;">
                            <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">print</span>
                            Lecture summary
                        </button>
                    </form>
                    <form method="get" action="{{ route('attendance.staff-hourly-summary.print') }}" target="_blank" class="d-flex flex-wrap align-items-center gap-2">
                        <span class="fs-12 text-muted">Hourly month:</span>
                        <select name="month" class="form-select form-select-sm" style="width: auto; min-width: 110px;">
                            @foreach(range(1, 12) as $m)
                                <option value="{{ $m }}" {{ (int) date('n') === $m ? 'selected' : '' }}>{{ \Carbon\Carbon::createFromDate(2000, $m, 1)->format('F') }}</option>
                            @endforeach
                        </select>
                        <select name="year" class="form-select form-select-sm" style="width: auto; min-width: 85px;">
                            @for($y = (int) date('Y'); $y >= (int) date('Y') - 5; $y--)
                                <option value="{{ $y }}" {{ (int) date('Y') === $y ? 'selected' : '' }}>{{ $y }}</option>
                            @endfor
                        </select>
                        <input type="hidden" name="auto_print" value="1">
                        <button type="submit" class="btn btn-sm btn-primary" style="background: linear-gradient(135deg, #003471 0%, #004a9f 100%); border: none;">
                            <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">print</span>
                            Hourly summary
                        </button>
                    </form>
                </div>
            </div>

            <div class="row g-2 mb-3">
                <div class="col-md-3">
                    <div class="card border-0 rounded-10 p-3 h-100" style="background-color: #28a745; color: #fff;">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="fw-semibold" style="font-size: 12px;">Present Staff Today</div>
                                <div class="fw-bold" style="font-size: 28px;">{{ $presentStaffToday ?? 0 }}</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 rounded-10 p-3 h-100" style="background-color: #dc3545; color: #fff;">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="fw-semibold" style="font-size: 12px;">Absent Staff Today</div>
                                <div class="fw-bold" style="font-size: 28px;">{{ $absentStaffToday ?? 0 }}</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 rounded-10 p-3 h-100" style="background-color: #0d6efd; color: #fff;">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="fw-semibold" style="font-size: 12px;">Present Students Today</div>
                                <div class="fw-bold" style="font-size: 28px;">{{ $presentStudentsToday ?? 0 }}</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 rounded-10 p-3 h-100" style="background-color: #ffc107;">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="fw-semibold" style="font-size: 12px;">Absent Students Today</div>
                                <div class="fw-bold" style="font-size: 28px;">{{ $absentStudentsToday ?? 0 }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="list-group">
                <div class="d-flex align-items-center justify-content-between py-3 border-bottom">
                    <div>
                        <strong>Student Attendance Sheet</strong>
                        <div class="text-muted fs-12">Print attendance report sheet for students class wise.</div>
                    </div>
                    <a href="{{ route('attendance.report') }}" class="btn btn-sm btn-outline-primary" target="_blank">
                        Print
                    </a>
                </div>

                <div class="d-flex align-items-center justify-content-between py-3 border-bottom">
                    <div>
                        <strong>Staff Attendance Sheet</strong>
                        <div class="text-muted fs-12">Print attendance report sheet for staff class wise.</div>
                    </div>
                    <a href="{{ route('attendance.staff-report') }}" class="btn btn-sm btn-outline-primary" target="_blank">
                        Print
                    </a>
                </div>

                <div class="d-flex align-items-center justify-content-between py-3 border-bottom">
                    <div>
                        <strong>Staff Attendance Summary</strong>
                        <div class="text-muted fs-12">Monthly summary for all active staff (Present, Absent, Leave, Late, %). Use the month/year control above or open quick print for current month.</div>
                    </div>
                    <a href="{{ route('attendance.staff-summary.print') }}?year={{ date('Y') }}&month={{ date('n') }}&auto_print=1" class="btn btn-sm btn-outline-primary" target="_blank" style="border-color: #003471; color: #003471;">
                        Print (this month)
                    </a>
                </div>

                <div class="d-flex align-items-center justify-content-between py-3 border-bottom">
                    <div>
                        <strong>Student Attendance Summary</strong>
                        <div class="text-muted fs-12">Monthly summary for all students (Present, Absent, Leave, %). Use Student month control above or quick print for current month.</div>
                    </div>
                    <a href="{{ route('attendance.student-summary.print') }}?year={{ date('Y') }}&month={{ date('n') }}&auto_print=1" class="btn btn-sm btn-outline-primary" target="_blank" style="border-color: #003471; color: #003471;">
                        Print (this month)
                    </a>
                </div>

                <div class="d-flex align-items-center justify-content-between py-3 border-bottom">
                    <div>
                        <strong>Absent Students Today</strong>
                        <div class="text-muted fs-12">Full list with class, section, and remarks; school letterhead and landscape layout. Print (today) opens the print dialog automatically.</div>
                    </div>
                    <a href="{{ route('attendance.absent-students-today.print') }}?auto_print=1" class="btn btn-sm btn-outline-primary" target="_blank" style="border-color: #003471; color: #003471;">
                        Print (today)
                    </a>
                </div>

                <div class="d-flex align-items-center justify-content-between py-3 border-bottom">
                    <div>
                        <strong>Absent Staff Today</strong>
                        <div class="text-muted fs-12">Letterhead, landscape; emp ID, contact, designation, remarks. Print (today) opens the print dialog automatically.</div>
                    </div>
                    <a href="{{ route('attendance.absent-staff-today.print') }}?auto_print=1" class="btn btn-sm btn-outline-primary" target="_blank" style="border-color: #003471; color: #003471;">
                        Print (today)
                    </a>
                </div>

                <div class="d-flex align-items-center justify-content-between py-3 border-bottom">
                    <div>
                        <strong>Subject/Lecture Attendance Summary</strong>
                        <div class="text-muted fs-12">Monthly total conducted lectures per teacher (designation contains &quot;teacher&quot;). Use Lecture month controls above or quick print for this month.</div>
                    </div>
                    <a href="{{ route('attendance.subject-lecture-summary.print') }}?year={{ date('Y') }}&month={{ date('n') }}&auto_print=1" class="btn btn-sm btn-outline-primary" target="_blank" style="border-color: #003471; color: #003471;">
                        Print (this month)
                    </a>
                </div>

                <div class="d-flex align-items-center justify-content-between py-3 border-bottom">
                    <div>
                        <strong>Staff Hourly Attendance Summary</strong>
                        <div class="text-muted fs-12">Monthly sum of in–out duration per staff member. Use Hourly month controls above or quick print for this month.</div>
                    </div>
                    <a href="{{ route('attendance.staff-hourly-summary.print') }}?year={{ date('Y') }}&month={{ date('n') }}&auto_print=1" class="btn btn-sm btn-outline-primary" target="_blank" style="border-color: #003471; color: #003471;">
                        Print (this month)
                    </a>
                </div>

                <div class="d-flex align-items-center justify-content-between py-3">
                    <div>
                        <strong>Class-wise Attendance Summary</strong>
                        <div class="text-muted fs-12">Daily snapshot by campus, class, and section (present/absent counts; pass-out excluded). Print (today) opens the print dialog automatically.</div>
                    </div>
                    <a href="{{ route('attendance.classwise-summary.print') }}?auto_print=1" class="btn btn-sm btn-outline-primary" target="_blank" style="border-color: #003471; color: #003471;">
                        Print (today)
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
