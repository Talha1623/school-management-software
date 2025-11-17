@extends('layouts.app')

@section('title', 'Progress Tracking')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-3 mb-4">
            <h4 class="mb-3 fs-16 fw-semibold" style="color: #003471;">Track Student Behavior</h4>
            
            <!-- Search Form -->
            <form action="{{ route('student-behavior.progress-tracking') }}" method="GET" class="mb-3">
                <div class="row g-2 align-items-end">
                    <div class="col-md-10">
                        <label for="searchInput" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Search Student</label>
                        <input type="text" 
                               id="searchInput" 
                               name="search" 
                               class="form-control form-control-sm" 
                               placeholder="Type Student Name / Roll Number..." 
                               value="{{ request('search') }}"
                               style="height: 36px; border-radius: 6px; border: 1px solid #dee2e6; font-size: 13px;">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-sm w-100 track-behavior-btn" style="height: 36px; border-radius: 6px;">
                            <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">search</span>
                            <span style="font-size: 13px; vertical-align: middle;">Track</span>
                        </button>
                    </div>
                </div>
            </form>

            <!-- Illustration Section (only show when no search) -->
            @if(!request()->has('search'))
            <div class="text-center mb-3">
                <div class="d-inline-block position-relative" style="width: 250px; height: 250px;">
                    <!-- Yellow Circle Background -->
                    <div class="position-absolute" style="width: 250px; height: 250px; background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%); border-radius: 50%; top: 0; left: 0; box-shadow: 0 8px 24px rgba(255, 215, 0, 0.3);"></div>
                    
                    <!-- ID Cards -->
                    <div class="position-absolute" style="top: 50%; left: 50%; transform: translate(-50%, -50%);">
                        <!-- Back Card (White) -->
                        <div class="position-relative" style="width: 150px; height: 100px; background: white; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); transform: rotate(-5deg) translate(10px, 10px);">
                            <div style="padding: 12px;">
                                <div style="width: 40px; height: 40px; background: #e9ecef; border-radius: 8px; margin-bottom: 6px;"></div>
                                <div style="height: 6px; background: #e9ecef; border-radius: 4px; margin-bottom: 4px;"></div>
                                <div style="height: 6px; background: #e9ecef; border-radius: 4px; width: 80%;"></div>
                            </div>
                        </div>
                        
                        <!-- Front Card (Red) -->
                        <div class="position-relative" style="width: 150px; height: 100px; background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); border-radius: 12px; box-shadow: 0 6px 16px rgba(220, 53, 69, 0.4); transform: rotate(2deg); z-index: 2;">
                            <div style="padding: 12px; display: flex; align-items: center; gap: 10px; height: 100%;">
                                <!-- Person Silhouette -->
                                <div style="width: 40px; height: 40px; background: #003471; border-radius: 50%; position: relative;">
                                    <div style="position: absolute; top: 10px; left: 50%; transform: translateX(-50%); width: 16px; height: 16px; background: white; border-radius: 50%;"></div>
                                    <div style="position: absolute; bottom: 6px; left: 50%; transform: translateX(-50%); width: 24px; height: 16px; background: white; border-radius: 12px 12px 0 0;"></div>
                                </div>
                                
                                <!-- Text Lines -->
                                <div style="flex: 1;">
                                    <div style="height: 5px; background: white; border-radius: 3px; margin-bottom: 5px; width: 90%;"></div>
                                    <div style="height: 5px; background: white; border-radius: 3px; margin-bottom: 5px; width: 75%;"></div>
                                    <div style="height: 5px; background: white; border-radius: 3px; width: 60%;"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <p class="mt-3 mb-0" style="color: #666; font-size: 13px; font-weight: 500;">
                    Scan Student ID Card For Quick Behavior Tracking...!
                </p>
            </div>
            @endif

            <!-- Behavior Report -->
            @if(request()->has('search'))
                @if($student)
                    <div id="behaviorReport" class="behavior-report">
                        <!-- Report Header -->
                        <div class="report-header mb-3">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <h3 class="mb-1 fw-bold" style="color: #e91e63; font-size: 20px;">{{ config('app.name', 'ICMS') }}</h3>
                                    <h4 class="mb-0 fw-bold" style="color: #000; font-size: 16px;">{{ $student->campus ?? 'Main Campus' }}</h4>
                                </div>
                                <div class="text-end">
                                    <p class="mb-0" style="font-size: 11px; color: #666;">{{ date('d-m-Y H:i:s') }}</p>
                                </div>
                            </div>
                            <div class="mb-2" style="font-size: 11px; color: #666;">
                                @if($campus && $campus->campus_address)
                                    <span>{{ $campus->campus_address }}</span>
                                @elseif($student->campus)
                                    <span>{{ $student->campus }}</span>
                                @else
                                    <span>{{ config('app.address', 'Defence View') }}</span>
                                @endif
                            </div>
                            <div class="mb-2" style="font-size: 11px; color: #666;">
                                <span>{{ $campus && $campus->phone ? $campus->phone : config('app.phone', '+923316074246') }}</span> | 
                                <span>{{ $campus && $campus->email ? $campus->email : config('app.email', 'arainabdurrehman3@gmail.com') }}</span>
                            </div>
                            <div class="mt-2 pt-2 border-top">
                                <h5 class="mb-0 fw-bold" style="color: #000; font-size: 14px;">Behavior Report | {{ $student->student_name }}</h5>
                                <div style="border-top: 2px dotted #ccc; margin-top: 5px;"></div>
                            </div>
                        </div>

                        <!-- Graph Section -->
                        <div class="graph-section mb-3">
                            <div class="graph-container" style="background: white; border: 1px solid #ddd; border-radius: 6px; padding: 15px; min-height: 300px; position: relative;">
                                <!-- Y-axis labels -->
                                <div style="position: absolute; left: 10px; top: 20px; bottom: 40px; display: flex; flex-direction: column-reverse; justify-content: space-between; font-size: 11px; color: #666;">
                                    @for($i = 0; $i <= 10; $i++)
                                        <span>{{ number_format($i / 10, 1) }}</span>
                                    @endfor
                                </div>
                                
                                <!-- Graph area -->
                                <div style="margin-left: 40px; margin-right: 20px; margin-top: 20px; margin-bottom: 40px; height: 240px; position: relative; border-left: 1px solid #ddd; border-bottom: 1px solid #ddd;">
                                    <!-- Graph will be rendered here -->
                                    <canvas id="behaviorChart" width="600" height="240"></canvas>
                                </div>
                                
                                <!-- X-axis label -->
                                <div style="margin-left: 40px; text-align: center; font-size: 11px; color: #666; margin-top: 5px;">
                                    {{ $student->student_name }}
                                </div>
                                
                                <!-- Legend -->
                                <div style="position: absolute; top: 15px; right: 20px; display: flex; gap: 15px; font-size: 11px;">
                                    <div class="d-flex align-items-center gap-2">
                                        <div style="width: 20px; height: 12px; background: #87ceeb; border: 1px solid #666;"></div>
                                        <span>Current Year</span>
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        <div style="width: 20px; height: 12px; background: #ffb6c1; border: 1px solid #666;"></div>
                                        <span>Last Year</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Behavior Summary Table -->
                        <div class="summary-table mb-3">
                            <table class="table table-bordered mb-0" style="font-size: 12px;">
                                <thead>
                                    <tr>
                                        <th style="padding: 8px; text-align: center; background-color: #f8f9fa; border: 1px solid #ddd;">#</th>
                                        <th style="padding: 8px; text-align: left; background-color: #f8f9fa; border: 1px solid #ddd;">Behavior Type</th>
                                        <th style="padding: 8px; text-align: center; background-color: #f8f9fa; border: 1px solid #ddd;">Points</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if(count($behaviorSummary) > 0)
                                        @foreach($behaviorSummary as $index => $summary)
                                        <tr>
                                            <td style="padding: 8px; text-align: center; border: 1px solid #ddd;">{{ $index + 1 }}</td>
                                            <td style="padding: 8px; text-align: left; border: 1px solid #ddd;">{{ $summary['type'] }}</td>
                                            <td style="padding: 8px; text-align: center; border: 1px solid #ddd;">{{ $summary['points'] }}</td>
                                        </tr>
                                        @endforeach
                                    @else
                                        <tr>
                                            <td style="padding: 8px; text-align: center; border: 1px solid #ddd;">1</td>
                                            <td style="padding: 8px; text-align: left; border: 1px solid #ddd;">{{ $student->student_name }}</td>
                                            <td style="padding: 8px; text-align: center; border: 1px solid #ddd;">0</td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>

                        <!-- Information Note -->
                        <div class="info-note mb-3" style="background: #fff3cd; border: 1px solid #ffc107; border-radius: 6px; padding: 10px; font-size: 12px; color: #856404;">
                            Points are calculated based on all available records for the student.
                        </div>

                        <!-- Print Button -->
                        <div class="text-center">
                            <button type="button" class="btn btn-primary print-btn" onclick="printReport()" style="background: #2196f3; border: none; padding: 8px 20px; border-radius: 6px;">
                                <span class="material-symbols-outlined" style="font-size: 18px; vertical-align: middle;">print</span>
                                <span style="font-size: 13px; vertical-align: middle; margin-left: 5px;">Print Result</span>
                            </button>
                        </div>
                    </div>
                @else
                    <div class="alert alert-warning mt-3 mb-0" style="padding: 10px; font-size: 13px;">
                        <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">warning</span>
                        <span>No student found with the given search criteria. Please try again with a different name or roll number.</span>
                    </div>
                @endif
            @endif
        </div>
    </div>
</div>

<style>
.track-behavior-btn {
    background: linear-gradient(135deg, #003471 0%, #004a9f 100%);
    color: white;
    border: none;
    font-weight: 500;
    transition: all 0.3s ease;
    box-shadow: 0 2px 6px rgba(0, 52, 113, 0.2);
}

.track-behavior-btn:hover {
    background: linear-gradient(135deg, #004a9f 0%, #003471 100%);
    transform: translateY(-1px);
    box-shadow: 0 4px 10px rgba(0, 52, 113, 0.3);
    color: white;
}

.track-behavior-btn:active {
    transform: translateY(0);
}

#searchInput:focus {
    border-color: #003471;
    box-shadow: 0 0 0 3px rgba(0, 52, 113, 0.15);
    outline: none;
}

.behavior-report {
    background: white;
    padding: 20px;
    border-radius: 8px;
    border: 1px solid #ddd;
}

.report-header {
    border-bottom: 2px solid #003471;
    padding-bottom: 10px;
}

.print-btn:hover {
    background: #0b7dda !important;
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(33, 150, 243, 0.3);
}

@media print {
    .track-behavior-btn,
    .print-btn,
    .sidebar,
    .navbar,
    .card-header {
        display: none !important;
    }
    
    .behavior-report {
        border: none;
        padding: 0;
    }
    
    body {
        background: white;
    }
}
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
@if(request()->has('search') && $student && isset($currentYearPoints) && isset($lastYearPoints))
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('behaviorChart');
    if (ctx) {
        const currentYearValue = Math.min(Math.abs({{ $currentYearPoints }}) / 100, 1.0);
        const lastYearValue = Math.min(Math.abs({{ $lastYearPoints }}) / 100, 1.0);
        
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['{{ $student->student_name }}'],
                datasets: [
                    {
                        label: 'Current Year',
                        data: [currentYearValue],
                        backgroundColor: 'rgba(135, 206, 235, 0.6)',
                        borderColor: 'rgba(135, 206, 235, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'Last Year',
                        data: [lastYearValue],
                        backgroundColor: 'rgba(255, 182, 193, 0.6)',
                        borderColor: 'rgba(255, 182, 193, 1)',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 1.0,
                        ticks: {
                            stepSize: 0.1,
                            callback: function(value) {
                                return value.toFixed(1);
                            }
                        }
                    },
                    x: {
                        display: false
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    }
});
@endif

function printReport() {
    window.print();
}
</script>
@endsection
