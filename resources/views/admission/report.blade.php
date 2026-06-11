@extends('layouts.app')

@section('title', 'Admission Report')

@section('content')
<div class="row">
    <div class="col-12">
        <!-- Summary Cards Section -->
        <div class="row mb-3">
            <div class="col-md-3 mb-3">
                <div class="card border-0 shadow-sm h-100" style="border-radius: 8px; overflow: hidden;">
                    <div class="card-body p-3" style="background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div>
                                <h6 class="text-white-50 mb-1" style="font-size: 12px; font-weight: 500;">Admissions Today</h6>
                                <h3 class="text-white mb-0" style="font-size: 24px; font-weight: 700;">{{ $admissionsToday ?? 0 }}</h3>
                            </div>
                            <span class="material-symbols-outlined text-white" style="font-size: 32px;">today</span>
                        </div>
                        <a href="{{ route('admission.report.today') }}" class="btn btn-sm btn-light text-dark" style="font-size: 11px; padding: 3px 10px; text-decoration: none;">
                            View Report
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 mb-3">
                <div class="card border-0 shadow-sm h-100" style="border-radius: 8px; overflow: hidden;">
                    <div class="card-body p-3" style="background: linear-gradient(135deg, #fd7e14 0%, #e86800 100%);">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div>
                                <h6 class="text-white-50 mb-1" style="font-size: 12px; font-weight: 500;">Admissions This Month ({{ $reportMonthLabel ?? now()->format('F Y') }})</h6>
                                <h3 class="text-white mb-0" style="font-size: 24px; font-weight: 700;">{{ $admissionsThisMonth ?? 0 }}</h3>
                            </div>
                            <span class="material-symbols-outlined text-white" style="font-size: 32px;">calendar_month</span>
                        </div>
                        <a href="{{ route('admission.report.monthly') }}" class="btn btn-sm btn-light text-dark" style="font-size: 11px; padding: 3px 10px; text-decoration: none;">
                            View Report
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 mb-3">
                <div class="card border-0 shadow-sm h-100" style="border-radius: 8px; overflow: hidden;">
                    <div class="card-body p-3" style="background: linear-gradient(135deg, #198754 0%, #146c43 100%);">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div>
                                <h6 class="text-white-50 mb-1" style="font-size: 12px; font-weight: 500;">Admissions This Year ({{ $reportYear ?? now()->year }})</h6>
                                <h3 class="text-white mb-0" style="font-size: 24px; font-weight: 700;">{{ $admissionsThisYear ?? 0 }}</h3>
                            </div>
                            <span class="material-symbols-outlined text-white" style="font-size: 32px;">calendar_today</span>
                        </div>
                        <a href="{{ route('admission.report.yearly') }}" class="btn btn-sm btn-light text-dark" style="font-size: 11px; padding: 3px 10px; text-decoration: none;">
                            View Report
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 mb-3">
                <div class="card border-0 shadow-sm h-100" style="border-radius: 8px; overflow: hidden;">
                    <div class="card-body p-3" style="background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%);">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div>
                                <h6 class="text-white-50 mb-1" style="font-size: 12px; font-weight: 500;">Deactivated Students</h6>
                                <h3 class="text-white mb-0" style="font-size: 24px; font-weight: 700;">{{ $deactivatedStudents ?? 0 }}</h3>
                            </div>
                            <span class="material-symbols-outlined text-white" style="font-size: 32px;">person_off</span>
                        </div>
                        <button class="btn btn-sm btn-light text-dark" style="font-size: 11px; padding: 3px 10px;">
                            View Report
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Printable Admission Reports Section -->
        <div class="card border-0 shadow-sm" style="border-radius: 8px; overflow: hidden;">
            <!-- Header -->
            <div class="p-2" style="background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%); border-bottom: 2px solid #28a745;">
                <h5 class="mb-0 fw-semibold" style="color: #155724; font-size: 14px;">
                    Printable Admission Reports
                </h5>
            </div>
            
            <!-- Content Area -->
            <div class="card-body p-3">
                <div class="row">
                    <div class="col-md-8">
                        <!-- Reports List -->
                        <div class="report-list">
                            <!-- Admissions Today -->
                            <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                                <div class="flex-grow-1">
                                    <h6 class="mb-1 fw-semibold" style="color: #333; font-size: 14px;">Admissions Today</h6>
                                    <p class="mb-0 text-muted" style="font-size: 12px;">List of newly admitted students today so far.</p>
                                </div>
                                <div class="d-flex align-items-center gap-2 ms-3 flex-shrink-0">
                                    <a href="{{ route('admission.report.today') }}" class="btn btn-sm btn-light text-dark" style="font-size: 12px; padding: 4px 12px; text-decoration: none;">View</a>
                                    <button type="button" class="btn btn-sm btn-light text-dark" onclick="printReport('today')" style="font-size: 12px; padding: 4px 12px;">
                                        <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">print</span>
                                        Print
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Monthly Admissions -->
                            <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                                <div class="flex-grow-1">
                                    <h6 class="mb-1 fw-semibold" style="color: #333; font-size: 14px;">Monthly Admissions ({{ $reportMonthLabel ?? now()->format('F Y') }})</h6>
                                    <p class="mb-0 text-muted" style="font-size: 12px;">List of all students admitted in {{ $reportMonthLabel ?? now()->format('F Y') }}, including bulk admissions.</p>
                                </div>
                                <div class="d-flex align-items-center gap-2 ms-3 flex-shrink-0">
                                    <a href="{{ route('admission.report.monthly') }}" class="btn btn-sm btn-light text-dark" style="font-size: 12px; padding: 4px 12px; text-decoration: none;">View</a>
                                    <button type="button" class="btn btn-sm btn-light text-dark" onclick="printReport('monthly')" style="font-size: 12px; padding: 4px 12px;">
                                        <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">print</span>
                                        Print
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Admissions This Year -->
                            <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                                <div class="flex-grow-1">
                                    <h6 class="mb-1 fw-semibold" style="color: #333; font-size: 14px;">Admissions This Year ({{ $reportYear ?? now()->year }})</h6>
                                    <p class="mb-0 text-muted" style="font-size: 12px;">List of all students admitted in {{ $reportYear ?? now()->year }}, including bulk admissions.</p>
                                </div>
                                <div class="d-flex align-items-center gap-2 ms-3 flex-shrink-0">
                                    <a href="{{ route('admission.report.yearly') }}" class="btn btn-sm btn-light text-dark" style="font-size: 12px; padding: 4px 12px; text-decoration: none;">View</a>
                                    <button type="button" class="btn btn-sm btn-light text-dark" onclick="printReport('yearly')" style="font-size: 12px; padding: 4px 12px;">
                                        <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">print</span>
                                        Print
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Admission Forms -->
                            <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                                <div class="flex-grow-1">
                                    <h6 class="mb-1 fw-semibold" style="color: #333; font-size: 14px;">Admission Forms</h6>
                                    <p class="mb-0 text-muted" style="font-size: 12px;">Class wise list of printables & information filled admission forms.</p>
                                </div>
                                <div class="d-flex align-items-center gap-2 ms-3 flex-shrink-0">
                                    <a href="{{ route('admission.report.forms') }}" class="btn btn-sm btn-light text-dark" style="font-size: 12px; padding: 4px 12px; text-decoration: none;">View</a>
                                    <button type="button" class="btn btn-sm btn-light text-dark" onclick="printReport('forms')" style="font-size: 12px; padding: 4px 12px;">
                                        <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">print</span>
                                        Print
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Blank Adm. Form -->
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="flex-grow-1">
                                    <h6 class="mb-1 fw-semibold" style="color: #333; font-size: 14px;">Blank Adm. Form</h6>
                                    <p class="mb-0 text-muted" style="font-size: 12px;">Print a blank copy of admission form.</p>
                                </div>
                                <div class="d-flex align-items-center gap-2 ms-3 flex-shrink-0">
                                    <a href="{{ route('admission.report.blank') }}" class="btn btn-sm btn-light text-dark" style="font-size: 12px; padding: 4px 12px; text-decoration: none;">View</a>
                                    <button type="button" class="btn btn-sm btn-light text-dark" onclick="printReport('blank')" style="font-size: 12px; padding: 4px 12px;">
                                        <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">print</span>
                                        Print
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Illustration -->
                    <div class="col-md-4 d-flex align-items-center justify-content-center">
                        <div class="text-center">
                            <div>
                                <span class="material-symbols-outlined" style="font-size: 60px; color: #ddd;">person</span>
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
    // This will be implemented based on your backend routes
    let url = '';
    
    switch(type) {
        case 'today':
            url = '{{ route("admission.report.today.print") }}?auto_print=1';
            break;
        case 'monthly':
            url = '{{ route("admission.report.monthly.print") }}?auto_print=1';
            break;
        case 'yearly':
            url = '{{ route("admission.report.yearly.print") }}?auto_print=1';
            break;
        case 'forms':
            url = '{{ route("admission.report.forms.print") }}?auto_print=1';
            break;
        case 'blank':
            url = '{{ route("admission.report.blank.print") }}?auto_print=1';
            break;
    }
    
    // Open in new window for printing
    if (url) {
        const w = window.open(url, '_blank');
        // If popup is blocked, fall back to same-tab navigation
        if (!w) {
            window.location.href = url;
        }
    }
}
</script>
@endsection
