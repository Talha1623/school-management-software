@extends('layouts.app')

@section('title', 'All Attendance Reports')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-3 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0 fs-16 fw-semibold">Printable Attendance Reports</h4>
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
                        <div class="text-muted fs-12">Print monthly attendance summary report for staff.</div>
                    </div>
                    <a href="{{ route('attendance.staff-summary.print') }}" class="btn btn-sm btn-outline-primary" target="_blank">
                        Print
                    </a>
                </div>

                <div class="d-flex align-items-center justify-content-between py-3 border-bottom">
                    <div>
                        <strong>Student Attendance Summary</strong>
                        <div class="text-muted fs-12">Print monthly attendance summary report for students.</div>
                    </div>
                    <a href="{{ route('attendance.student-summary.print') }}" class="btn btn-sm btn-outline-primary" target="_blank">
                        Print
                    </a>
                </div>

                <div class="d-flex align-items-center justify-content-between py-3 border-bottom">
                    <div>
                        <strong>Absent Students Today</strong>
                        <div class="text-muted fs-12">Print list of absent students on current date.</div>
                    </div>
                    <a href="{{ route('attendance.absent-students-today.print') }}" class="btn btn-sm btn-outline-primary" target="_blank">
                        Print
                    </a>
                </div>

                <div class="d-flex align-items-center justify-content-between py-3 border-bottom">
                    <div>
                        <strong>Absent Staff Today</strong>
                        <div class="text-muted fs-12">Print list of absent staff on current date.</div>
                    </div>
                    <a href="{{ route('attendance.absent-staff-today.print') }}" class="btn btn-sm btn-outline-primary" target="_blank">
                        Print
                    </a>
                </div>

                <div class="d-flex align-items-center justify-content-between py-3 border-bottom">
                    <div>
                        <strong>Subject/Lecture Attendance Summary</strong>
                        <div class="text-muted fs-12">Print monthly lecture/subject based attendance summary report.</div>
                    </div>
                    <a href="{{ route('attendance.subject-lecture-summary.print') }}" class="btn btn-sm btn-outline-primary" target="_blank">
                        Print
                    </a>
                </div>

                <div class="d-flex align-items-center justify-content-between py-3 border-bottom">
                    <div>
                        <strong>Staff Hourly Attendance Summary</strong>
                        <div class="text-muted fs-12">Print monthly hourly attendance summary report for staff.</div>
                    </div>
                    <a href="{{ route('attendance.staff-hourly-summary.print') }}" class="btn btn-sm btn-outline-primary" target="_blank">
                        Print
                    </a>
                </div>

                <div class="d-flex align-items-center justify-content-between py-3">
                    <div>
                        <strong>Class-wise Attendance Summary</strong>
                        <div class="text-muted fs-12">Print monthly class wise attendance summary report.</div>
                    </div>
                    <a href="{{ route('attendance.classwise-summary.print') }}" class="btn btn-sm btn-outline-primary" target="_blank">
                        Print
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
