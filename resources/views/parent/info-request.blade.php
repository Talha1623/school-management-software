@extends('layouts.app')

@section('title', 'Parent Info Request')

@section('content')
<div class="row">
    <div class="col-12">
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
                            <!-- All Parents -->
                            <div class="report-item p-3 mb-3 rounded-8 border" style="background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%); border-color: #e9ecef !important; transition: all 0.3s ease;">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1 fw-semibold d-flex align-items-center gap-2" style="color: #003471; font-size: 14px;">
                                            <span class="material-symbols-outlined" style="font-size: 18px; color: #003471;">groups</span>
                                            All Parents
                                        </h6>
                                        <p class="mb-0 text-muted" style="font-size: 12px; margin-top: 4px;">Complete list of parents with linked students.</p>
                                    </div>
                                </div>
                                <a href="{{ route('parent.info-request.all-parents.print', ['auto_print' => 1]) }}" target="_blank" class="btn btn-sm w-100 mt-2" style="background: linear-gradient(135deg, #003471 0%, #004a9f 100%); color: white; border: none; font-size: 12px; padding: 6px 12px; border-radius: 6px;">
                                    <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">print</span>
                                    Print Report
                                </a>
                            </div>
                            
                            <!-- Parent Credit Report -->
                            <div class="report-item p-3 mb-3 rounded-8 border" style="background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%); border-color: #e9ecef !important; transition: all 0.3s ease;">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1 fw-semibold d-flex align-items-center gap-2" style="color: #003471; font-size: 14px;">
                                            <span class="material-symbols-outlined" style="font-size: 18px; color: #003471;">payments</span>
                                            Parent Credit Report
                                        </h6>
                                        <p class="mb-0 text-muted" style="font-size: 12px; margin-top: 4px;">Total paid amounts grouped by parent.</p>
                                    </div>
                                </div>
                                <a href="{{ route('parent.info-request.parent-credit.print', ['auto_print' => 1]) }}" target="_blank" class="btn btn-sm w-100 mt-2" style="background: linear-gradient(135deg, #003471 0%, #004a9f 100%); color: white; border: none; font-size: 12px; padding: 6px 12px; border-radius: 6px;">
                                    <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">print</span>
                                    Print Report
                                </a>
                            </div>
                            
                            <!-- Family Tree Report -->
                            <div class="report-item p-3 mb-3 rounded-8 border" style="background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%); border-color: #e9ecef !important; transition: all 0.3s ease;">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1 fw-semibold d-flex align-items-center gap-2" style="color: #003471; font-size: 14px;">
                                            <span class="material-symbols-outlined" style="font-size: 18px; color: #003471;">account_tree</span>
                                            Family Tree Report
                                        </h6>
                                        <p class="mb-0 text-muted" style="font-size: 12px; margin-top: 4px;">Parent with linked students list.</p>
                                    </div>
                                </div>
                                <a href="{{ route('parent.info-request.family-tree.print', ['auto_print' => 1]) }}" target="_blank" class="btn btn-sm w-100 mt-2" style="background: linear-gradient(135deg, #003471 0%, #004a9f 100%); color: white; border: none; font-size: 12px; padding: 6px 12px; border-radius: 6px;">
                                    <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">print</span>
                                    Print Report
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Column 2 -->
                    <div class="col-md-6">
                        <div class="report-list">
                            <!-- Defaulter Parents Report -->
                            <div class="report-item p-3 mb-3 rounded-8 border" style="background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%); border-color: #e9ecef !important; transition: all 0.3s ease;">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1 fw-semibold d-flex align-items-center gap-2" style="color: #003471; font-size: 14px;">
                                            <span class="material-symbols-outlined" style="font-size: 18px; color: #003471;">report</span>
                                            Defaulter Parents Report
                                        </h6>
                                        <p class="mb-0 text-muted" style="font-size: 12px; margin-top: 4px;">Parents with outstanding dues.</p>
                                    </div>
                                </div>
                                <a href="{{ route('parent.info-request.defaulter-parents.print', ['auto_print' => 1]) }}" target="_blank" class="btn btn-sm w-100 mt-2" style="background: linear-gradient(135deg, #003471 0%, #004a9f 100%); color: white; border: none; font-size: 12px; padding: 6px 12px; border-radius: 6px;">
                                    <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">print</span>
                                    Print Report
                                </a>
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

@endsection
