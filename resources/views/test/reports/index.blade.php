@extends('layouts.app')

@section('title', 'Test Reports')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-3 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0 fs-16 fw-semibold">Printable Test Reports</h4>
            </div>

            <!-- Statistics Cards -->
            <div class="row g-2 mb-3">
                <div class="col-md-3">
                    <div class="card border-0 rounded-10 p-3 h-100" style="background-color: #28a745; color: #fff;">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="fw-semibold" style="font-size: 12px;">Total Tests</div>
                                <div class="fw-bold" style="font-size: 28px;">{{ number_format($totalTests) }}</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 rounded-10 p-3 h-100" style="background-color: #dc3545; color: #fff;">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="fw-semibold" style="font-size: 12px;">Declared Results</div>
                                <div class="fw-bold" style="font-size: 28px;">{{ number_format($declaredResults) }}</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 rounded-10 p-3 h-100" style="background-color: #0d6efd; color: #fff;">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="fw-semibold" style="font-size: 12px;">Total Tests</div>
                                <div class="fw-bold" style="font-size: 28px;">{{ number_format($totalTests) }}</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 rounded-10 p-3 h-100" style="background-color: #ffc107;">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="fw-semibold" style="font-size: 12px;">Declared Results</div>
                                <div class="fw-bold" style="font-size: 28px;">{{ number_format($declaredResults) }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Print Options -->
            <div class="list-group">
                <div class="d-flex align-items-center justify-content-between py-3 border-bottom">
                    <div>
                        <strong>Blank Tabulation Sheet</strong>
                        <div class="text-muted fs-12">Print a blank tabulation sheet for organizing marks data.</div>
                    </div>
                    <a href="{{ route('test.reports.print.blank-tabulation-sheet') }}" class="btn btn-sm btn-outline-primary" target="_blank">
                        Print
                    </a>
                </div>

                <div class="d-flex align-items-center justify-content-between py-3 border-bottom">
                    <div>
                        <strong>Blank Attendance Sheet</strong>
                        <div class="text-muted fs-12">Print a blank attendance sheet to keep track of students in tests.</div>
                    </div>
                    <a href="{{ route('test.reports.print.blank-attendance-sheet') }}" class="btn btn-sm btn-outline-primary" target="_blank">
                        Print
                    </a>
                </div>

                <div class="d-flex align-items-center justify-content-between py-3">
                    <div>
                        <strong>Blank Marksheet</strong>
                        <div class="text-muted fs-12">Print a blank marksheet for organizing marks data manually.</div>
                    </div>
                    <a href="{{ route('test.reports.print.blank-marksheet') }}" class="btn btn-sm btn-outline-primary" target="_blank">
                        Print
                    </a>
                </div>
            </div>

            <!-- Note -->
            <div class="mt-3">
                <p class="text-muted fs-12 mb-0">
                    <strong>Note:</strong> Please ensure that all reports are printed in A4 size for optimal viewing. Adjust your printer settings accordingly.
                </p>
            </div>
        </div>
    </div>
</div>
@endsection
