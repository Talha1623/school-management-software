@extends('layouts.app')

@section('title', 'Automation Settings')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-4 mb-4">
            <div class="d-flex align-items-center mb-4">
                <span class="material-symbols-outlined me-2" style="font-size: 28px; color: #003471;">auto_awesome</span>
                <h3 class="mb-0 fw-bold" style="color: #003471;">Automation Settings</h3>
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
                                <span class="material-symbols-outlined" style="font-size: 20px; vertical-align: middle;">schedule</span>
                                Attendance Automation
                            </h5>
                            
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="auto_attendance" name="auto_attendance">
                                <label class="form-check-label" for="auto_attendance">
                                    Enable Automatic Attendance Marking
                                </label>
                            </div>
                            
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="auto_absent" name="auto_absent">
                                <label class="form-check-label" for="auto_absent">
                                    Auto-mark Absent After Time Limit
                                </label>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label fw-medium">Attendance Time Limit (minutes)</label>
                                <input type="number" class="form-control" name="attendance_time_limit" placeholder="30" min="0">
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 mb-4">
                        <div class="card border-0 shadow-sm rounded-10 p-4 h-100">
                            <h5 class="mb-4 fw-semibold" style="color: #003471;">
                                <span class="material-symbols-outlined" style="font-size: 20px; vertical-align: middle;">notifications</span>
                                Notification Automation
                            </h5>
                            
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="auto_notify_absent" name="auto_notify_absent">
                                <label class="form-check-label" for="auto_notify_absent">
                                    Auto-notify Parents for Absence
                                </label>
                            </div>
                            
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="auto_notify_fee" name="auto_notify_fee">
                                <label class="form-check-label" for="auto_notify_fee">
                                    Auto-notify Fee Due Reminders
                                </label>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label fw-medium">Fee Reminder Days Before Due</label>
                                <input type="number" class="form-control" name="fee_reminder_days" placeholder="7" min="0">
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 mb-4">
                        <div class="card border-0 shadow-sm rounded-10 p-4 h-100">
                            <h5 class="mb-4 fw-semibold" style="color: #003471;">
                                <span class="material-symbols-outlined" style="font-size: 20px; vertical-align: middle;">receipt</span>
                                Fee Automation
                            </h5>
                            
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="auto_generate_fee" name="auto_generate_fee">
                                <label class="form-check-label" for="auto_generate_fee">
                                    Auto-generate Monthly Fees
                                </label>
                            </div>
                            
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="auto_late_fee" name="auto_late_fee">
                                <label class="form-check-label" for="auto_late_fee">
                                    Auto-apply Late Fee
                                </label>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label fw-medium">Late Fee Amount (%)</label>
                                <input type="number" class="form-control" name="late_fee_percentage" placeholder="5" min="0" step="0.1">
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 mb-4">
                        <div class="card border-0 shadow-sm rounded-10 p-4 h-100">
                            <h5 class="mb-4 fw-semibold" style="color: #003471;">
                                <span class="material-symbols-outlined" style="font-size: 20px; vertical-align: middle;">backup</span>
                                Backup Automation
                            </h5>
                            
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="auto_backup" name="auto_backup">
                                <label class="form-check-label" for="auto_backup">
                                    Enable Automatic Backup
                                </label>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label fw-medium">Backup Frequency</label>
                                <select class="form-select" name="backup_frequency">
                                    <option value="daily">Daily</option>
                                    <option value="weekly">Weekly</option>
                                    <option value="monthly">Monthly</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label fw-medium">Backup Time</label>
                                <input type="time" class="form-control" name="backup_time" value="02:00">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <button type="button" class="btn btn-outline-secondary">Cancel</button>
                    <button type="submit" class="btn btn-primary" style="background: #003471; border-color: #003471; color: white;">
                        <span class="material-symbols-outlined" style="font-size: 18px; vertical-align: middle; color: white;">save</span>
                        <span style="color: white;">Save Settings</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Ensure sidebar stays open on Automation Settings page
document.addEventListener('DOMContentLoaded', function() {
    // Force sidebar to show state
    document.body.setAttribute("sidebar-data-theme", "sidebar-show");
    
    // Ensure sidebar is visible
    const sidebarArea = document.getElementById('sidebar-area');
    if (sidebarArea) {
        sidebarArea.style.display = '';
        sidebarArea.classList.remove('sidebar-hide');
        sidebarArea.classList.add('sidebar-show');
    }
    
    // Prevent auto-close on window resize
    window.addEventListener('resize', function() {
        if (window.innerWidth > 992) { // Only on desktop
            document.body.setAttribute("sidebar-data-theme", "sidebar-show");
        }
    });
});
</script>
@endsection

