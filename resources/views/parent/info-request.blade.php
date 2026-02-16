@extends('layouts.app')

@section('title', 'Parent Info Request')

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
                                <h6 class="text-white-50 mb-1" style="font-size: 12px; font-weight: 500;">Total Parents</h6>
                                <h3 class="text-white mb-0" style="font-size: 24px; font-weight: 700;">{{ $totalParents ?? 0 }}</h3>
                            </div>
                            <span class="material-symbols-outlined text-white" style="font-size: 32px;">groups</span>
                        </div>
                        <a href="{{ route('parent.info-request.all-parents.print', ['auto_print' => 1]) }}" target="_blank" class="btn btn-sm btn-light text-dark" style="font-size: 11px; padding: 3px 10px; text-decoration: none;">
                            View Report
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 mb-3">
                <div class="card border-0 shadow-sm h-100" style="border-radius: 8px; overflow: hidden;">
                    <div class="card-body p-3" style="background: linear-gradient(135deg, #28a745 0%, #218838 100%);">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div>
                                <h6 class="text-white-50 mb-1" style="font-size: 12px; font-weight: 500;">Parents with Credit</h6>
                                <h3 class="text-white mb-0" style="font-size: 24px; font-weight: 700;">{{ $parentsWithCredit ?? 0 }}</h3>
                            </div>
                            <span class="material-symbols-outlined text-white" style="font-size: 32px;">payments</span>
                        </div>
                        <a href="{{ route('parent.info-request.parent-credit.print', ['auto_print' => 1]) }}" target="_blank" class="btn btn-sm btn-light text-dark" style="font-size: 11px; padding: 3px 10px; text-decoration: none;">
                            View Report
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 mb-3">
                <div class="card border-0 shadow-sm h-100" style="border-radius: 8px; overflow: hidden;">
                    <div class="card-body p-3" style="background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div>
                                <h6 class="text-white-50 mb-1" style="font-size: 12px; font-weight: 500;">Defaulter Parents</h6>
                                <h3 class="text-white mb-0" style="font-size: 24px; font-weight: 700;">{{ $defaulterParents ?? 0 }}</h3>
                            </div>
                            <span class="material-symbols-outlined text-white" style="font-size: 32px;">report</span>
                        </div>
                        <a href="{{ route('parent.info-request.defaulter-parents.print', ['auto_print' => 1]) }}" target="_blank" class="btn btn-sm btn-light text-dark" style="font-size: 11px; padding: 3px 10px; text-decoration: none;">
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
                                <h6 class="text-white-50 mb-1" style="font-size: 12px; font-weight: 500;">Linked Students</h6>
                                <h3 class="text-white mb-0" style="font-size: 24px; font-weight: 700;">{{ $totalLinkedStudents ?? 0 }}</h3>
                            </div>
                            <span class="material-symbols-outlined text-white" style="font-size: 32px;">account_tree</span>
                        </div>
                        <a href="{{ route('parent.info-request.family-tree.print', ['auto_print' => 1]) }}" target="_blank" class="btn btn-sm btn-light text-dark" style="font-size: 11px; padding: 3px 10px; text-decoration: none;">
                            View Report
                        </a>
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
                            <!-- All Parents -->
                            <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                                <div class="flex-grow-1">
                                    <h6 class="mb-1 fw-semibold" style="color: #333; font-size: 14px;">All Parents</h6>
                                    <p class="mb-0 text-muted" style="font-size: 12px;">Complete list of parents with linked students.</p>
                                </div>
                                <a href="{{ route('parent.info-request.all-parents.print', ['auto_print' => 1]) }}" target="_blank" class="btn btn-sm btn-light text-dark ms-3" style="font-size: 12px; padding: 4px 12px;">
                                    <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">print</span>
                                    Print
                                </a>
                            </div>
                            
                            <!-- Parent Credit Report -->
                            <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                                <div class="flex-grow-1">
                                    <h6 class="mb-1 fw-semibold" style="color: #333; font-size: 14px;">Parent Credit Report</h6>
                                    <p class="mb-0 text-muted" style="font-size: 12px;">Total paid amounts grouped by parent.</p>
                                </div>
                                <a href="{{ route('parent.info-request.parent-credit.print', ['auto_print' => 1]) }}" target="_blank" class="btn btn-sm btn-light text-dark ms-3" style="font-size: 12px; padding: 4px 12px;">
                                    <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">print</span>
                                    Print
                                </a>
                            </div>
                            
                            <!-- Family Tree Report -->
                            <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                                <div class="flex-grow-1">
                                    <h6 class="mb-1 fw-semibold" style="color: #333; font-size: 14px;">Family Tree Report</h6>
                                    <p class="mb-0 text-muted" style="font-size: 12px;">Parent with linked students list.</p>
                                </div>
                                <a href="{{ route('parent.info-request.family-tree.print', ['auto_print' => 1]) }}" target="_blank" class="btn btn-sm btn-light text-dark ms-3" style="font-size: 12px; padding: 4px 12px;">
                                    <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">print</span>
                                    Print
                                </a>
                            </div>
                            
                            <!-- Defaulter Parents Report -->
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="flex-grow-1">
                                    <h6 class="mb-1 fw-semibold" style="color: #333; font-size: 14px;">Defaulter Parents Report</h6>
                                    <p class="mb-0 text-muted" style="font-size: 12px;">Parents with outstanding dues.</p>
                                </div>
                                <a href="{{ route('parent.info-request.defaulter-parents.print', ['auto_print' => 1]) }}" target="_blank" class="btn btn-sm btn-light text-dark ms-3" style="font-size: 12px; padding: 4px 12px;">
                                    <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">print</span>
                                    Print
                                </a>
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

@endsection
