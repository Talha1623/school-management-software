@extends('layouts.app')

@section('title', 'Teacher Dashboard')

@section('content')
<div class="row">
    <div class="col-12">
        <!-- Dashboard Title -->
        <div class="d-flex align-items-center mb-4">
            <h2 class="mb-0 fs-20 fw-semibold text-dark me-2">Teacher Dashboard</h2>
            <span class="material-symbols-outlined text-secondary" style="font-size: 20px;">arrow_forward</span>
        </div>

        <!-- Quick Action Buttons -->
        <div class="mb-4">
            <div class="d-flex gap-2 flex-wrap" style="background: linear-gradient(135deg, #003471 0%, #004a9f 100%); padding: 16px; border-radius: 12px; overflow-x: auto;">
                <!-- Dashboard Button -->
                <a href="{{ route('staff.dashboard') }}" class="btn-quick-action" style="background-color: #20c997;">
                    <span class="material-symbols-outlined text-white">speed</span>
                </a>        
                
                <!-- Student List Button -->
                <a href="{{ route('student-list') }}" class="btn-quick-action" style="background-color: #f44336;">
                    <span class="material-symbols-outlined text-white">groups</span>
                </a>
                
                <!-- Attendance Report Button -->
                <a href="{{ route('attendance.report') }}" class="btn-quick-action" style="background-color: #28a745;">
                    <span class="material-symbols-outlined text-white">bar_chart</span>
                </a>
                
                <!-- Manage Attendance Button -->
                <a href="{{ route('attendance.student') }}" class="btn-quick-action" style="background-color: #03a9f4;">
                    <span class="material-symbols-outlined text-white">event_available</span>
                </a>
                
                <!-- Test Management Button -->
                <a href="{{ route('test.list') }}" class="btn-quick-action" style="background-color: #ff9800;">
                    <span class="material-symbols-outlined text-white">quiz</span>
                </a>
                
                <!-- Exam Management Button -->
                <a href="{{ route('exam.list') }}" class="btn-quick-action" style="background-color: #6c757d;">
                    <span class="material-symbols-outlined text-white">assignment</span>
                </a>
                
                <!-- Marks Entry Button -->
                <a href="{{ route('test.marks-entry') }}" class="btn-quick-action" style="background-color: #20c997;">
                    <span class="material-symbols-outlined text-white">edit_note</span>
                </a>
                
                <!-- Daily Diary Button -->
                <a href="{{ route('homework-diary.manage') }}" class="btn-quick-action" style="background-color: #ff9800;">
                    <span class="material-symbols-outlined text-white">menu_book</span>
                </a>
                
                <!-- Student Behavior Button -->
                <a href="{{ route('student-behavior.recording') }}" class="btn-quick-action" style="background-color: #f44336;">
                    <span class="material-symbols-outlined text-white">psychology</span>
                </a>
                
                <!-- Study Materials Button -->
                <a href="#" class="btn-quick-action" style="background-color: #28a745;">
                    <span class="material-symbols-outlined text-white">library_books</span>
                </a>
                
                <!-- Online Class Button -->
                <a href="#" class="btn-quick-action" style="background-color: #03a9f4;">
                    <span class="material-symbols-outlined text-white">videocam</span>
                </a>
                
                <!-- Task Management Button -->
                <a href="#" class="btn-quick-action" style="background-color: #ff9800;">
                    <span class="material-symbols-outlined text-white">task</span>
                </a>
                
                <!-- Calendar Button -->
                <a href="#" class="btn-quick-action" style="background-color: #20c997;">
                    <span class="material-symbols-outlined text-white">calendar_month</span>
                </a>
                
                <!-- Noticeboard Button -->
                <a href="{{ route('school.noticeboard') }}" class="btn-quick-action" style="background-color: #ff9800;">
                    <span class="material-symbols-outlined text-white">notifications</span>
                </a>
                
                <!-- Notifications Button -->
                <a href="#" class="btn-quick-action" style="background-color: #03a9f4;">
                    <span class="material-symbols-outlined text-white">notifications_active</span>
                </a>
            </div>
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
                                <h2 class="text-white mb-1 fw-bold" style="font-size: 36px;">{{ $attendancePercentage }}%</h2>
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
                                <div class="fw-bold" style="font-size: 32px; color: #ff6b6b;">{{ $absentToday }}</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Legend -->
                    <div class="d-flex justify-content-center gap-4 mt-3">
                        <div class="d-flex align-items-center">
                            <div style="width: 12px; height: 12px; background: #51cf66; border-radius: 2px; margin-right: 6px;"></div>
                            <span style="font-size: 12px; color: #666;">Present ({{ $presentToday }})</span>
                        </div>
                        <div class="d-flex align-items-center">
                            <div style="width: 12px; height: 12px; background: #ff6b6b; border-radius: 2px; margin-right: 6px;"></div>
                            <span style="font-size: 12px; color: #666;">Absent ({{ $absentToday }})</span>
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
@endsection

