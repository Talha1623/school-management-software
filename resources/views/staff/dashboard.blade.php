@extends('layouts.app')

@section('title', 'Teacher Dashboard')

@section('content')
<div class="row">
    <div class="col-12">
        <!-- Dashboard Title -->
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div class="d-flex align-items-center">
                <h2 class="mb-0 fs-20 fw-semibold text-dark me-2">Teacher Dashboard</h2>
                <span class="material-symbols-outlined text-secondary" style="font-size: 20px;">arrow_forward</span>
            </div>
            @if(isset($assignedClasses) && $assignedClasses->isNotEmpty())
            <div class="d-flex align-items-center gap-2">
                <span class="text-muted" style="font-size: 13px;">Assigned Classes:</span>
                <div class="d-flex gap-1 flex-wrap">
                    @foreach($assignedClasses as $class)
                    <span class="badge bg-primary" style="font-size: 12px;">{{ $class }}</span>
                    @endforeach
                </div>
            </div>
            @endif
        </div>


        <!-- Statistics Cards -->
        <div class="row g-3 mb-4">
            <!-- Total Students Card -->
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100" style="background: linear-gradient(135deg, #0066cc 0%, #004d99 100%); border-radius: 12px; overflow: hidden;">
                    <div class="card-body p-4 position-relative">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <h2 class="text-white mb-1 fw-bold" style="font-size: 36px;">{{ $totalStudents }}</h2>
                                <p class="text-white-50 mb-0" style="font-size: 14px; font-weight: 500;">Total Students</p>
                            </div>
                            <span class="material-symbols-outlined text-white-50" style="font-size: 48px; opacity: 0.3;">groups</span>
                        </div>
                        <a href="{{ route('student-list') }}" class="text-white text-decoration-none d-flex align-items-center" style="font-size: 13px; font-weight: 500;">
                            More info
                            <span class="material-symbols-outlined ms-1" style="font-size: 16px;">arrow_forward</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Boys Card -->
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%); border-radius: 12px; overflow: hidden;">
                    <div class="card-body p-4 position-relative">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <h2 class="text-white mb-1 fw-bold" style="font-size: 36px;">{{ $boys }}</h2>
                                <p class="text-white-50 mb-0" style="font-size: 14px; font-weight: 500;">Boys</p>
                            </div>
                            <span class="material-symbols-outlined text-white-50" style="font-size: 48px; opacity: 0.3;">person</span>
                        </div>
                        <a href="{{ route('student-list') }}?gender=Male" class="text-white text-decoration-none d-flex align-items-center" style="font-size: 13px; font-weight: 500;">
                            More info
                            <span class="material-symbols-outlined ms-1" style="font-size: 16px;">arrow_forward</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Girls Card -->
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100" style="background: linear-gradient(135deg, #ff9800 0%, #f57c00 100%); border-radius: 12px; overflow: hidden;">
                    <div class="card-body p-4 position-relative">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <h2 class="text-white mb-1 fw-bold" style="font-size: 36px;">{{ $girls }}</h2>
                                <p class="text-white-50 mb-0" style="font-size: 14px; font-weight: 500;">Girls</p>
                            </div>
                            <span class="material-symbols-outlined text-white-50" style="font-size: 48px; opacity: 0.3;">person</span>
                        </div>
                        <a href="{{ route('student-list') }}?gender=Female" class="text-white text-decoration-none d-flex align-items-center" style="font-size: 13px; font-weight: 500;">
                            More info
                            <span class="material-symbols-outlined ms-1" style="font-size: 16px;">arrow_forward</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Class Attendance Card -->
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100" style="background: linear-gradient(135deg, #00bcd4 0%, #0097a7 100%); border-radius: 12px; overflow: hidden;">
                    <div class="card-body p-4 position-relative">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <h2 class="text-white mb-1 fw-bold" style="font-size: 36px;" id="attendancePercentage">{{ $attendancePercentage }}%</h2>
                                <p class="text-white-50 mb-0" style="font-size: 14px; font-weight: 500;">Class Attendance</p>
                            </div>
                            <span class="material-symbols-outlined text-white-50" style="font-size: 48px; opacity: 0.3;">bar_chart</span>
                        </div>
                        <a href="{{ route('attendance.student') }}" class="text-white text-decoration-none d-flex align-items-center" style="font-size: 13px; font-weight: 500;">
                            More info
                            <span class="material-symbols-outlined ms-1" style="font-size: 16px;">arrow_forward</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Latest Admissions and Present Vs Absent Section -->
        <div class="row g-3">
            <!-- Latest Admissions -->
            <div class="col-md-6">
                <div class="card rounded-10 p-3 h-100" style="border: 1px solid #003471; background: #f8f9fa;">
                    <h5 class="mb-2 fs-16 fw-semibold" style="color: #003471; border-bottom: 2px solid #003471; padding-bottom: 8px;">Latest Admissions</h5>
                    <div class="row g-2 mt-3">
                        @forelse($latestAdmissions as $student)
                            <div class="col-md-6">
                                <div class="d-flex align-items-center p-2" style="background: #f8f9fa; border-radius: 8px;">
                                    <div class="flex-shrink-0 me-2">
                                        <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; background: #e3f2fd;">
                                            <span class="material-symbols-outlined text-primary" style="font-size: 20px;">person</span>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="fw-medium" style="font-size: 13px; color: #333;">{{ $student->student_name }}</div>
                                        <div class="text-muted" style="font-size: 11px;">
                                            {{ $student->admission_date ? \Carbon\Carbon::parse($student->admission_date)->format('d M - Y') : 'N/A' }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="col-12">
                                <p class="text-muted text-center py-3">No admissions found</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- Present Vs Absent -->
            <div class="col-md-6">
                <div class="card rounded-10 p-3 h-100" style="border: 1px solid #003471; background: #f8f9fa;">
                    <h5 class="mb-2 fs-16 fw-semibold" style="color: #003471; border-bottom: 2px solid #003471; padding-bottom: 8px;">Present Vs Absent</h5>
                    <div class="d-flex align-items-center justify-content-center mt-3" style="min-height: 250px;">
                        <div class="position-relative" style="width: 200px; height: 200px;">
                            @php
                                $totalForChart = $totalStudents > 0 ? $totalStudents : 1;
                                $presentPercentage = ($presentToday / $totalForChart) * 100;
                                $absentPercentage = ($absentToday / $totalForChart) * 100;
                                
                                // Calculate circumference (2 * π * r, where r = 80)
                                $circumference = 2 * M_PI * 80;
                                
                                // Present segment (green) - starts at 0, shows present percentage
                                $presentDashArray = ($presentPercentage / 100) * $circumference;
                                $presentDashOffset = $circumference - $presentDashArray;
                                
                                // Absent segment (orange) - starts after present, shows absent percentage
                                $absentDashArray = ($absentPercentage / 100) * $circumference;
                                $absentDashOffset = $circumference - $absentDashArray - $presentDashArray;
                            @endphp
                            
                            <svg width="200" height="200" class="position-absolute" style="transform: rotate(-90deg);">
                                <!-- Present (Green) - drawn first so it appears on top -->
                                <circle
                                    cx="100"
                                    cy="100"
                                    r="80"
                                    fill="none"
                                    stroke="#51cf66"
                                    stroke-width="20"
                                    stroke-dasharray="{{ $presentDashArray }} {{ $circumference - $presentDashArray }}"
                                    stroke-dashoffset="0"
                                />
                                <!-- Absent (Orange/Coral) - drawn after present -->
                                <circle
                                    cx="100"
                                    cy="100"
                                    r="80"
                                    fill="none"
                                    stroke="#ff6b6b"
                                    stroke-width="20"
                                    stroke-dasharray="{{ $absentDashArray }} {{ $circumference - $absentDashArray }}"
                                    stroke-dashoffset="{{ -$presentDashArray }}"
                                />
                            </svg>
                            
                            <div class="position-absolute top-50 start-50 translate-middle text-center">
                                <div class="fw-bold" style="font-size: 14px; color: #333;">Absent Students</div>
                                <div class="fw-bold" style="font-size: 32px; color: #ff6b6b;" id="absentToday">{{ $absentToday }}</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Legend -->
                    <div class="d-flex justify-content-center gap-4 mt-3">
                        <div class="d-flex align-items-center">
                            <div style="width: 12px; height: 12px; background: #51cf66; border-radius: 2px; margin-right: 6px;"></div>
                            <span style="font-size: 12px; color: #666;">Present (<span id="presentToday">{{ $presentToday }}</span>)</span>
                        </div>
                        <div class="d-flex align-items-center">
                            <div style="width: 12px; height: 12px; background: #ff6b6b; border-radius: 2px; margin-right: 6px;"></div>
                            <span style="font-size: 12px; color: #666;">Absent (<span id="absentTodayLegend">{{ $absentToday }}</span>)</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.card:hover {
    transform: translateY(-5px) !important;
}

.btn-quick-action {
    width: 56px;
    height: 56px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    transition: all 0.3s ease;
    flex-shrink: 0;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    position: relative;
}

.btn-quick-action .material-symbols-outlined {
    font-size: 24px !important;
    color: white !important;
    line-height: 1 !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    margin: 0 !important;
    padding: 0 !important;
    width: 100% !important;
    height: 100% !important;
}

.btn-quick-action:hover {
    transform: translateY(-3px) scale(1.05);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    text-decoration: none;
}

.btn-quick-action:active {
    transform: translateY(-1px) scale(1.02);
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .btn-quick-action {
        width: 48px;
        height: 48px;
    }
    
    .btn-quick-action .material-symbols-outlined {
        font-size: 20px !important;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Function to update attendance stats
    function updateAttendanceStats() {
        fetch('{{ route("staff.dashboard.attendance-stats") }}', {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.attendancePercentage !== undefined) {
                // Update attendance percentage
                const percentageEl = document.getElementById('attendancePercentage');
                if (percentageEl) {
                    percentageEl.textContent = data.attendancePercentage + '%';
                }
                
                // Update present count
                const presentEl = document.getElementById('presentToday');
                if (presentEl) {
                    presentEl.textContent = data.presentToday;
                }
                
                // Update absent count
                const absentEl = document.getElementById('absentToday');
                if (absentEl) {
                    absentEl.textContent = data.absentToday;
                }
                
                const absentLegendEl = document.getElementById('absentTodayLegend');
                if (absentLegendEl) {
                    absentLegendEl.textContent = data.absentToday;
                }
                
                // Update chart if needed
                updateAttendanceChart(data.presentToday, data.absentToday, data.totalStudents);
            }
        })
        .catch(error => {
            console.error('Error updating attendance stats:', error);
        });
    }
    
    // Function to update attendance chart
    function updateAttendanceChart(presentToday, absentToday, totalStudents) {
        const totalForChart = totalStudents > 0 ? totalStudents : 1;
        const presentPercentage = (presentToday / totalForChart) * 100;
        const absentPercentage = (absentToday / totalForChart) * 100;
        
        const circumference = 2 * Math.PI * 80;
        const presentDashArray = (presentPercentage / 100) * circumference;
        const absentDashArray = (absentPercentage / 100) * circumference;
        
        // Update SVG circles
        const svg = document.querySelector('.position-relative svg');
        if (svg) {
            const presentCircle = svg.querySelector('circle[stroke="#51cf66"]');
            const absentCircle = svg.querySelector('circle[stroke="#ff6b6b"]');
            
            if (presentCircle) {
                presentCircle.setAttribute('stroke-dasharray', `${presentDashArray} ${circumference - presentDashArray}`);
            }
            if (absentCircle) {
                absentCircle.setAttribute('stroke-dasharray', `${absentDashArray} ${circumference - absentDashArray}`);
                absentCircle.setAttribute('stroke-dashoffset', -presentDashArray);
            }
        }
    }
    
    // Listen for attendance updates from other tabs/windows
    if (window.BroadcastChannel) {
        const channel = new BroadcastChannel('attendance-updates');
        channel.addEventListener('message', function(event) {
            if (event.data && event.data.type === 'updateAttendanceStats') {
                updateAttendanceStats();
            }
        });
    }
    
    // Also listen for postMessage from parent/opener windows
    window.addEventListener('message', function(event) {
        if (event.data && event.data.type === 'updateAttendanceStats') {
            updateAttendanceStats();
        }
    });
    
    // Auto-refresh every 30 seconds
    setInterval(updateAttendanceStats, 30000);
});
</script>
@endsection

