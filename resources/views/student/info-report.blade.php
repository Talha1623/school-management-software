@extends('layouts.app')

@section('title', 'Student Info Report')

@section('content')
<div class="row">
    <div class="col-12">
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
    const url = `/student/info-report/print?type=${encodeURIComponent(type)}`;
    window.open(url, '_blank');
}
</script>
@endsection
