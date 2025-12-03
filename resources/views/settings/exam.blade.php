@extends('layouts.app')

@section('title', 'Exam Settings')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-4 mb-4">
            <div class="d-flex align-items-center mb-4">
                <span class="material-symbols-outlined me-2" style="font-size: 28px; color: #003471;">quiz</span>
                <h3 class="mb-0 fw-bold" style="color: #003471;">Exam Settings</h3>
            </div>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <form method="POST" action="#">
                @csrf
                <div class="row">
                    <div class="col-md-6 mb-4">
                        <div class="card border-0 shadow-sm rounded-10 p-4 h-100">
                            <h5 class="mb-4 fw-semibold" style="color: #003471;">
                                <span class="material-symbols-outlined" style="font-size: 20px; vertical-align: middle;">grading</span>
                                Grading System
                            </h5>
                            
                            <div class="mb-3">
                                <label class="form-label fw-medium">Grading System</label>
                                <select class="form-select" name="grading_system">
                                    <option value="percentage">Percentage</option>
                                    <option value="letter">Letter Grade (A-F)</option>
                                    <option value="gpa">GPA (0-4)</option>
                                    <option value="custom">Custom</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label fw-medium">Passing Percentage</label>
                                <input type="number" class="form-control" name="passing_percentage" placeholder="50" min="0" max="100">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label fw-medium">Maximum Marks</label>
                                <input type="number" class="form-control" name="max_marks" placeholder="100" min="0">
                            </div>
                            
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="show_rank" name="show_rank">
                                <label class="form-check-label" for="show_rank">
                                    Show Student Rank
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 mb-4">
                        <div class="card border-0 shadow-sm rounded-10 p-4 h-100">
                            <h5 class="mb-4 fw-semibold" style="color: #003471;">
                                <span class="material-symbols-outlined" style="font-size: 20px; vertical-align: middle;">event</span>
                                Exam Schedule Settings
                            </h5>
                            
                            <div class="mb-3">
                                <label class="form-label fw-medium">Default Exam Duration (minutes)</label>
                                <input type="number" class="form-control" name="exam_duration" placeholder="90" min="0">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label fw-medium">Exam Start Time</label>
                                <input type="time" class="form-control" name="exam_start_time" value="09:00">
                            </div>
                            
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="auto_publish" name="auto_publish">
                                <label class="form-check-label" for="auto_publish">
                                    Auto-publish Results
                                </label>
                            </div>
                            
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="allow_recheck" name="allow_recheck">
                                <label class="form-check-label" for="allow_recheck">
                                    Allow Result Recheck Request
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 mb-4">
                        <div class="card border-0 shadow-sm rounded-10 p-4 h-100">
                            <h5 class="mb-4 fw-semibold" style="color: #003471;">
                                <span class="material-symbols-outlined" style="font-size: 20px; vertical-align: middle;">assignment</span>
                                Exam Types
                            </h5>
                            
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="enable_quiz" name="enable_quiz">
                                <label class="form-check-label" for="enable_quiz">
                                    Enable Quiz/Test
                                </label>
                            </div>
                            
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="enable_midterm" name="enable_midterm">
                                <label class="form-check-label" for="enable_midterm">
                                    Enable Mid-term Exams
                                </label>
                            </div>
                            
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="enable_final" name="enable_final">
                                <label class="form-check-label" for="enable_final">
                                    Enable Final Exams
                                </label>
                            </div>
                            
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="enable_assignment" name="enable_assignment">
                                <label class="form-check-label" for="enable_assignment">
                                    Enable Assignments
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 mb-4">
                        <div class="card border-0 shadow-sm rounded-10 p-4 h-100">
                            <h5 class="mb-4 fw-semibold" style="color: #003471;">
                                <span class="material-symbols-outlined" style="font-size: 20px; vertical-align: middle;">notifications</span>
                                Notification Settings
                            </h5>
                            
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="notify_exam_schedule" name="notify_exam_schedule">
                                <label class="form-check-label" for="notify_exam_schedule">
                                    Notify Exam Schedule
                                </label>
                            </div>
                            
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="notify_results" name="notify_results">
                                <label class="form-check-label" for="notify_results">
                                    Notify Results Release
                                </label>
                            </div>
                            
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="notify_reminder" name="notify_reminder">
                                <label class="form-check-label" for="notify_reminder">
                                    Send Exam Reminders
                                </label>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label fw-medium">Reminder Days Before Exam</label>
                                <input type="number" class="form-control" name="reminder_days" placeholder="1" min="0">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <button type="button" class="btn btn-outline-secondary">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <span class="material-symbols-outlined" style="font-size: 18px; vertical-align: middle;">save</span>
                        Save Settings
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Ensure sidebar stays open on Exam Settings page
document.addEventListener('DOMContentLoaded', function() {
    document.body.setAttribute("sidebar-data-theme", "sidebar-show");
    const sidebarArea = document.getElementById('sidebar-area');
    if (sidebarArea) {
        sidebarArea.style.display = '';
        sidebarArea.classList.remove('sidebar-hide');
        sidebarArea.classList.add('sidebar-show');
    }
    window.addEventListener('resize', function() {
        if (window.innerWidth > 992) {
            document.body.setAttribute("sidebar-data-theme", "sidebar-show");
        }
    });
});
</script>
@endsection

