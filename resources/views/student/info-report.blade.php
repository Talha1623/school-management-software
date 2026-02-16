@extends('layouts.app')

@section('title', 'Student Info Report')

@section('content')
<div class="row">
    <div class="col-12">
        <!-- Summary Cards Section -->
        <div class="row mb-3">
            <div class="col-md-3 mb-3">
                <div class="card border-0 shadow-sm h-100" style="border-radius: 8px; overflow: hidden;">
                    <div class="card-body p-3" style="background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div>
                                <h6 class="text-white-50 mb-1" style="font-size: 12px; font-weight: 500;">Total Students</h6>
                                <h3 class="text-white mb-0" style="font-size: 24px; font-weight: 700;">{{ $totalStudents ?? 0 }}</h3>
                            </div>
                            <span class="material-symbols-outlined text-white" style="font-size: 32px;">groups</span>
                        </div>
                        <button class="btn btn-sm btn-light text-dark" onclick="printReport('all-active')" style="font-size: 11px; padding: 3px 10px;">
                            View Report
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 mb-3">
                <div class="card border-0 shadow-sm h-100" style="border-radius: 8px; overflow: hidden;">
                    <div class="card-body p-3" style="background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div>
                                <h6 class="text-white-50 mb-1" style="font-size: 12px; font-weight: 500;">Male Students</h6>
                                <h3 class="text-white mb-0" style="font-size: 24px; font-weight: 700;">{{ $maleStudents ?? 0 }}</h3>
                            </div>
                            <span class="material-symbols-outlined text-white" style="font-size: 32px;">man</span>
                        </div>
                        <button class="btn btn-sm btn-light text-dark" onclick="printReport('gender-wise')" style="font-size: 11px; padding: 3px 10px;">
                            View Report
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 mb-3">
                <div class="card border-0 shadow-sm h-100" style="border-radius: 8px; overflow: hidden;">
                    <div class="card-body p-3" style="background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div>
                                <h6 class="text-white-50 mb-1" style="font-size: 12px; font-weight: 500;">Female Students</h6>
                                <h3 class="text-white mb-0" style="font-size: 24px; font-weight: 700;">{{ $femaleStudents ?? 0 }}</h3>
                            </div>
                            <span class="material-symbols-outlined text-white" style="font-size: 32px;">woman</span>
                        </div>
                        <button class="btn btn-sm btn-light text-dark" onclick="printReport('gender-wise')" style="font-size: 11px; padding: 3px 10px;">
                            View Report
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 mb-3">
                <div class="card border-0 shadow-sm h-100" style="border-radius: 8px; overflow: hidden;">
                    <div class="card-body p-3" style="background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%);">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div>
                                <h6 class="text-white-50 mb-1" style="font-size: 12px; font-weight: 500;">Pass-out Students</h6>
                                <h3 class="text-white mb-0" style="font-size: 24px; font-weight: 700;">{{ $passoutStudents ?? 0 }}</h3>
                            </div>
                            <span class="material-symbols-outlined text-white" style="font-size: 32px;">school</span>
                        </div>
                        <button class="btn btn-sm btn-light text-dark" onclick="printReport('all-passout')" style="font-size: 11px; padding: 3px 10px;">
                            View Report
                        </button>
                    </div>
                </div>
            </div>
        </div>

        @php
            $reportItems = [
                [
                    'type' => 'all-active',
                    'title' => 'All Active Students',
                    'description' => 'List of all active students.',
                ],
                [
                    'type' => 'all-inactive',
                    'title' => 'All Inactive Students',
                    'description' => 'List of all inactive students.',
                ],
                [
                    'type' => 'class-wise',
                    'title' => 'Class Wise Student Report',
                    'description' => 'Class wise list of students with complete information.',
                ],
                [
                    'type' => 'all-passout',
                    'title' => 'All Passout Students',
                    'description' => 'List of all passout students.',
                ],
                [
                    'type' => 'free-students',
                    'title' => 'Free Students Report',
                    'description' => 'List of students with free fees.',
                ],
                [
                    'type' => 'monthly-passout',
                    'title' => 'Monthly Passout Students Report',
                    'description' => 'Passout students report for the current month.',
                ],
                [
                    'type' => 'daily-passout',
                    'title' => 'Daily Passout Students Report',
                    'description' => 'Passout students report for today.',
                ],
                [
                    'type' => 'gender-wise',
                    'title' => 'Gender Wise Student Report',
                    'description' => 'Gender wise list of students.',
                ],
            ];
        @endphp

        <!-- Printable Student Info Reports Section -->
        <div class="card border-0 shadow-sm" style="border-radius: 8px; overflow: hidden;">
            <!-- Header -->
            <div class="p-2" style="background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%); border-bottom: 2px solid #28a745;">
                <h5 class="mb-0 fw-semibold" style="color: #155724; font-size: 14px;">
                    Printable Student Info Reports
                </h5>
            </div>
            
            <!-- Content Area -->
            <div class="card-body p-3">
                <div class="row">
                    <div class="col-md-8">
                        <!-- Reports List -->
                        <div class="report-list">
                            @foreach($reportItems as $index => $report)
                                <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1 fw-semibold" style="color: #333; font-size: 14px;">
                                            {{ $report['title'] }}
                                        </h6>
                                        <p class="mb-0 text-muted" style="font-size: 12px;">
                                            {{ $report['description'] }}
                                        </p>
                                    </div>
                                    <button class="btn btn-sm btn-light text-dark ms-3" onclick="printReport('{{ $report['type'] }}')" style="font-size: 12px; padding: 4px 12px;">
                                        <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">print</span>
                                        Print
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    
                    <!-- Illustration -->
                    <div class="col-md-4 d-flex align-items-center justify-content-center">
                        <div class="text-center">
                            <div>
                                <span class="material-symbols-outlined" style="font-size: 60px; color: #ddd;">groups</span>
                                <span class="material-symbols-outlined" style="font-size: 60px; color: #0d6efd; margin-left: -15px;">print</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Note -->
                <div class="mt-3 pt-2 border-top">
                    <p class="text-muted mb-0" style="font-size: 11px;">
                        <strong>Note:</strong> Please ensure that all reports are printed in A4 size for optimal viewing. Adjust your printer settings accordingly.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .report-list .btn-light {
        transition: all 0.3s ease;
        border: 1px solid #dee2e6;
    }
    
    .report-list .btn-light:hover {
        background-color: #f8f9fa !important;
        border-color: #adb5bd;
        transform: translateY(-1px);
    }
    
    .card {
        transition: transform 0.2s ease;
    }
    
    .card:hover {
        transform: translateY(-2px);
    }
</style>

<script>
function printReport(type) {
    if (type === 'class-wise') {
        // Redirect to filter page for class-wise report
        window.location.href = '{{ route("student.info-report.class-wise.filter") }}';
    } else if (type === 'all-active') {
        // Redirect to filter page for all-active report
        window.location.href = '{{ route("student.info-report.all-active.filter") }}';
    } else if (type === 'all-inactive') {
        // Redirect to filter page for all-inactive report
        window.location.href = '{{ route("student.info-report.all-inactive.filter") }}';
    } else if (type === 'all-passout') {
        // Redirect to filter page for all-passout report
        window.location.href = '{{ route("student.info-report.all-passout.filter") }}';
    } else if (type === 'free-students') {
        // Redirect to filter page for free-students report
        window.location.href = '{{ route("student.info-report.free-students.filter") }}';
    } else if (type === 'monthly-passout') {
        // Redirect to filter page for monthly-passout report
        window.location.href = '{{ route("student.info-report.monthly-passout.filter") }}';
    } else if (type === 'daily-passout') {
        // Redirect to filter page for daily-passout report
        window.location.href = '{{ route("student.info-report.daily-passout.filter") }}';
    } else if (type === 'gender-wise') {
        // Redirect to filter page for gender-wise report
        window.location.href = '{{ route("student.info-report.gender-wise.filter") }}';
    } else {
        const url = `/student/info-report/print?type=${encodeURIComponent(type)}`;
        window.open(url, '_blank');
    }
}
</script>
@endsection
