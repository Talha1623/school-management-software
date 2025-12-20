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
                                <h3 class="text-white mb-0" style="font-size: 24px; font-weight: 700;">{{ $totalParents ?? 0 }}</h3>
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
                                <h3 class="text-white mb-0" style="font-size: 24px; font-weight: 700;">{{ $activeParents ?? 0 }}</h3>
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
                                <h3 class="text-white mb-0" style="font-size: 24px; font-weight: 700;">{{ $pendingRequests ?? 0 }}</h3>
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
                                <h3 class="text-white mb-0" style="font-size: 24px; font-weight: 700;">{{ $inactiveParents ?? 0 }}</h3>
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
                <!-- Reports List in 2 Columns -->
                <div class="row g-3">
                    <!-- Column 1 -->
                    <div class="col-md-6">
                        <div class="report-list">
                            <!-- Parents Today -->
                            <div class="report-item p-3 mb-3 rounded-8 border" style="background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%); border-color: #e9ecef !important; transition: all 0.3s ease;">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1 fw-semibold d-flex align-items-center gap-2" style="color: #003471; font-size: 14px;">
                                            <span class="material-symbols-outlined" style="font-size: 18px; color: #003471;">today</span>
                                            Parents Today
                                        </h6>
                                        <p class="mb-0 text-muted" style="font-size: 12px; margin-top: 4px;">List of parents registered today so far.</p>
                                    </div>
                                </div>
                                <button class="btn btn-sm w-100 mt-2" onclick="printReport('today')" style="background: linear-gradient(135deg, #003471 0%, #004a9f 100%); color: white; border: none; font-size: 12px; padding: 6px 12px; border-radius: 6px;">
                                    <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">print</span>
                                    Print Report
                                </button>
                            </div>
                            
                            <!-- Monthly Parents -->
                            <div class="report-item p-3 mb-3 rounded-8 border" style="background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%); border-color: #e9ecef !important; transition: all 0.3s ease;">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1 fw-semibold d-flex align-items-center gap-2" style="color: #003471; font-size: 14px;">
                                            <span class="material-symbols-outlined" style="font-size: 18px; color: #003471;">calendar_month</span>
                                            Monthly Parents
                                        </h6>
                                        <p class="mb-0 text-muted" style="font-size: 12px; margin-top: 4px;">List of total registered parents this month so far.</p>
                                    </div>
                                </div>
                                <button class="btn btn-sm w-100 mt-2" onclick="printReport('monthly')" style="background: linear-gradient(135deg, #003471 0%, #004a9f 100%); color: white; border: none; font-size: 12px; padding: 6px 12px; border-radius: 6px;">
                                    <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">print</span>
                                    Print Report
                                </button>
                            </div>
                            
                            <!-- Parents This Year -->
                            <div class="report-item p-3 mb-3 rounded-8 border" style="background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%); border-color: #e9ecef !important; transition: all 0.3s ease;">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1 fw-semibold d-flex align-items-center gap-2" style="color: #003471; font-size: 14px;">
                                            <span class="material-symbols-outlined" style="font-size: 18px; color: #003471;">event</span>
                                            Parents This Year
                                        </h6>
                                        <p class="mb-0 text-muted" style="font-size: 12px; margin-top: 4px;">List of registered parents this year so far.</p>
                                    </div>
                                </div>
                                <button class="btn btn-sm w-100 mt-2" onclick="printReport('yearly')" style="background: linear-gradient(135deg, #003471 0%, #004a9f 100%); color: white; border: none; font-size: 12px; padding: 6px 12px; border-radius: 6px;">
                                    <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">print</span>
                                    Print Report
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Column 2 -->
                    <div class="col-md-6">
                        <div class="report-list">
                            <!-- Parent Type Wise -->
                            <div class="report-item p-3 mb-3 rounded-8 border" style="background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%); border-color: #e9ecef !important; transition: all 0.3s ease;">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1 fw-semibold d-flex align-items-center gap-2" style="color: #003471; font-size: 14px;">
                                            <span class="material-symbols-outlined" style="font-size: 18px; color: #003471;">category</span>
                                            Parent Type Wise
                                        </h6>
                                        <p class="mb-0 text-muted" style="font-size: 12px; margin-top: 4px;">Parent type wise list with complete information.</p>
                                    </div>
                                </div>
                                <button class="btn btn-sm w-100 mt-2" onclick="printReport('typewise')" style="background: linear-gradient(135deg, #003471 0%, #004a9f 100%); color: white; border: none; font-size: 12px; padding: 6px 12px; border-radius: 6px;">
                                    <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">print</span>
                                    Print Report
                                </button>
                            </div>
                            
                            <!-- Complete Parent Info -->
                            <div class="report-item p-3 mb-3 rounded-8 border" style="background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%); border-color: #e9ecef !important; transition: all 0.3s ease;">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1 fw-semibold d-flex align-items-center gap-2" style="color: #003471; font-size: 14px;">
                                            <span class="material-symbols-outlined" style="font-size: 18px; color: #003471;">description</span>
                                            Complete Parent Info
                                        </h6>
                                        <p class="mb-0 text-muted" style="font-size: 12px; margin-top: 4px;">Print complete information of all parents.</p>
                                    </div>
                                </div>
                                <button class="btn btn-sm w-100 mt-2" onclick="printReport('complete')" style="background: linear-gradient(135deg, #003471 0%, #004a9f 100%); color: white; border: none; font-size: 12px; padding: 6px 12px; border-radius: 6px;">
                                    <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">print</span>
                                    Print Report
                                </button>
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
    .report-item {
        transition: all 0.3s ease;
    }
    
    .report-item:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 52, 113, 0.15) !important;
        border-color: #003471 !important;
    }
    
    .report-item .btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(0, 52, 113, 0.3);
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
