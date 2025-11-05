@extends('layouts.app')

@section('title', 'Parent Info Request')

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
                                <h6 class="text-white-50 mb-1" style="font-size: 12px; font-weight: 500;">Total Parents</h6>
                                <h3 class="text-white mb-0" style="font-size: 24px; font-weight: 700;">0</h3>
                            </div>
                            <span class="material-symbols-outlined text-white" style="font-size: 32px;">groups</span>
                        </div>
                        <button class="btn btn-sm btn-light text-dark" style="font-size: 11px; padding: 3px 10px;">
                            View Report
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 mb-3">
                <div class="card border-0 shadow-sm h-100" style="border-radius: 8px; overflow: hidden;">
                    <div class="card-body p-3" style="background: linear-gradient(135deg, #fd7e14 0%, #e86800 100%);">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div>
                                <h6 class="text-white-50 mb-1" style="font-size: 12px; font-weight: 500;">Active Parents</h6>
                                <h3 class="text-white mb-0" style="font-size: 24px; font-weight: 700;">0</h3>
                            </div>
                            <span class="material-symbols-outlined text-white" style="font-size: 32px;">person_check</span>
                        </div>
                        <button class="btn btn-sm btn-light text-dark" style="font-size: 11px; padding: 3px 10px;">
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
                                <h6 class="text-white-50 mb-1" style="font-size: 12px; font-weight: 500;">Pending Requests</h6>
                                <h3 class="text-white mb-0" style="font-size: 24px; font-weight: 700;">0</h3>
                            </div>
                            <span class="material-symbols-outlined text-white" style="font-size: 32px;">pending</span>
                        </div>
                        <button class="btn btn-sm btn-light text-dark" style="font-size: 11px; padding: 3px 10px;">
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
                                <h6 class="text-white-50 mb-1" style="font-size: 12px; font-weight: 500;">Inactive Parents</h6>
                                <h3 class="text-white mb-0" style="font-size: 24px; font-weight: 700;">0</h3>
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

        <!-- Printable Parent Info Reports Section -->
        <div class="card border-0 shadow-sm" style="border-radius: 8px; overflow: hidden;">
            <!-- Header -->
            <div class="p-2" style="background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%); border-bottom: 2px solid #28a745;">
                <h5 class="mb-0 fw-semibold" style="color: #155724; font-size: 14px;">
                    Printable Parent Info Reports
                </h5>
            </div>
            
            <!-- Content Area -->
            <div class="card-body p-3">
                <div class="row">
                    <div class="col-md-8">
                        <!-- Reports List -->
                        <div class="report-list">
                            <!-- Parents Today -->
                            <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                                <div class="flex-grow-1">
                                    <h6 class="mb-1 fw-semibold" style="color: #333; font-size: 14px;">Parents Today</h6>
                                    <p class="mb-0 text-muted" style="font-size: 12px;">List of parents registered today so far.</p>
                                </div>
                                <button class="btn btn-sm btn-light text-dark ms-3" onclick="printReport('today')" style="font-size: 12px; padding: 4px 12px;">
                                    <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">print</span>
                                    Print
                                </button>
                            </div>
                            
                            <!-- Monthly Parents -->
                            <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                                <div class="flex-grow-1">
                                    <h6 class="mb-1 fw-semibold" style="color: #333; font-size: 14px;">Monthly Parents</h6>
                                    <p class="mb-0 text-muted" style="font-size: 12px;">List of total registered parents this month so far.</p>
                                </div>
                                <button class="btn btn-sm btn-light text-dark ms-3" onclick="printReport('monthly')" style="font-size: 12px; padding: 4px 12px;">
                                    <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">print</span>
                                    Print
                                </button>
                            </div>
                            
                            <!-- Parents This Year -->
                            <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                                <div class="flex-grow-1">
                                    <h6 class="mb-1 fw-semibold" style="color: #333; font-size: 14px;">Parents This Year</h6>
                                    <p class="mb-0 text-muted" style="font-size: 12px;">List of registered parents this year so far.</p>
                                </div>
                                <button class="btn btn-sm btn-light text-dark ms-3" onclick="printReport('yearly')" style="font-size: 12px; padding: 4px 12px;">
                                    <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">print</span>
                                    Print
                                </button>
                            </div>
                            
                            <!-- Parent Type Wise -->
                            <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                                <div class="flex-grow-1">
                                    <h6 class="mb-1 fw-semibold" style="color: #333; font-size: 14px;">Parent Type Wise</h6>
                                    <p class="mb-0 text-muted" style="font-size: 12px;">Parent type wise list with complete information.</p>
                                </div>
                                <button class="btn btn-sm btn-light text-dark ms-3" onclick="printReport('typewise')" style="font-size: 12px; padding: 4px 12px;">
                                    <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">print</span>
                                    Print
                                </button>
                            </div>
                            
                            <!-- Complete Parent Info -->
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="flex-grow-1">
                                    <h6 class="mb-1 fw-semibold" style="color: #333; font-size: 14px;">Complete Parent Info</h6>
                                    <p class="mb-0 text-muted" style="font-size: 12px;">Print complete information of all parents.</p>
                                </div>
                                <button class="btn btn-sm btn-light text-dark ms-3" onclick="printReport('complete')" style="font-size: 12px; padding: 4px 12px;">
                                    <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">print</span>
                                    Print
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Illustration -->
                    <div class="col-md-4 d-flex align-items-center justify-content-center">
                        <div class="text-center">
                            <div>
                                <span class="material-symbols-outlined" style="font-size: 60px; color: #ddd;">family_restroom</span>
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
            url = '/parent/info-request/today';
            break;
        case 'monthly':
            url = '/parent/info-request/monthly';
            break;
        case 'yearly':
            url = '/parent/info-request/yearly';
            break;
        case 'typewise':
            url = '/parent/info-request/typewise';
            break;
        case 'complete':
            url = '/parent/info-request/complete';
            break;
    }
    
    // Open in new window for printing
    window.open(url, '_blank');
}
</script>
@endsection
