@extends('layouts.app')

@section('title', 'Transport Reports')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-20">
            <h3 class="mb-20">Transport Reports</h3>
            
            <!-- Report Cards -->
            <div class="row g-3">
                <!-- All Transport Report -->
                <div class="col-md-6 col-lg-3">
                    <div class="card border-0 shadow-sm h-100" style="border-radius: 8px; transition: transform 0.2s;">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center mb-3">
                                <div class="bg-primary bg-opacity-10 rounded-circle p-3 me-3">
                                    <span class="material-symbols-outlined text-primary" style="font-size: 28px;">route</span>
                                </div>
                                <div>
                                    <h6 class="mb-0 fw-semibold">Transport Routes</h6>
                                    <small class="text-muted">All Routes</small>
                                </div>
                            </div>
                            <p class="text-muted mb-3" style="font-size: 12px;">View all transport routes with details including vehicles and fare information.</p>
                            <button class="btn btn-sm btn-primary w-100" onclick="window.open('{{ route("transport.reports.print.all-transport") }}', '_blank')">
                                <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">print</span>
                                Print
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Transport Income Report -->
                <div class="col-md-6 col-lg-3">
                    <div class="card border-0 shadow-sm h-100" style="border-radius: 8px; transition: transform 0.2s;">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center mb-3">
                                <div class="bg-success bg-opacity-10 rounded-circle p-3 me-3">
                                    <span class="material-symbols-outlined text-success" style="font-size: 28px;">payments</span>
                                </div>
                                <div>
                                    <h6 class="mb-0 fw-semibold">Transport Income</h6>
                                    <small class="text-muted">Monthly Income</small>
                                </div>
                            </div>
                            <p class="text-muted mb-3" style="font-size: 12px;">View monthly transport income with payment details and statistics.</p>
                            <button class="btn btn-sm btn-success w-100" onclick="window.open('{{ route("transport.reports.print.income") }}', '_blank')">
                                <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">print</span>
                                Print
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Connected Students Report -->
                <div class="col-md-6 col-lg-3">
                    <div class="card border-0 shadow-sm h-100" style="border-radius: 8px; transition: transform 0.2s;">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center mb-3">
                                <div class="bg-info bg-opacity-10 rounded-circle p-3 me-3">
                                    <span class="material-symbols-outlined text-info" style="font-size: 28px;">people</span>
                                </div>
                                <div>
                                    <h6 class="mb-0 fw-semibold">Students Using Transport</h6>
                                    <small class="text-muted">Connected Students</small>
                                </div>
                            </div>
                            <p class="text-muted mb-3" style="font-size: 12px;">View all students using transport services grouped by routes.</p>
                            <button class="btn btn-sm btn-info w-100" onclick="window.open('{{ route("transport.reports.print.connected-students") }}', '_blank')">
                                <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">print</span>
                                Print
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Transport Passes -->
                <div class="col-md-6 col-lg-3">
                    <div class="card border-0 shadow-sm h-100" style="border-radius: 8px; transition: transform 0.2s;">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center mb-3">
                                <div class="bg-warning bg-opacity-10 rounded-circle p-3 me-3">
                                    <span class="material-symbols-outlined text-warning" style="font-size: 28px;">confirmation_number</span>
                                </div>
                                <div>
                                    <h6 class="mb-0 fw-semibold">Transport Passes</h6>
                                    <small class="text-muted">Student Passes</small>
                                </div>
                            </div>
                            <p class="text-muted mb-3" style="font-size: 12px;">Print individual transport passes for students.</p>
                            <button class="btn btn-sm btn-warning w-100" onclick="window.open('{{ route("transport.reports.print.passes") }}', '_blank')">
                                <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">print</span>
                                Print
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

