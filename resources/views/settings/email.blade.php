@extends('layouts.app')

@section('title', 'Email Settings')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-4 mb-4">
            <div class="d-flex align-items-center mb-4">
                <span class="material-symbols-outlined me-2" style="font-size: 28px; color: #003471;">mail</span>
                <h3 class="mb-0 fw-bold" style="color: #003471;">Email Settings</h3>
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
                                <span class="material-symbols-outlined" style="font-size: 20px; vertical-align: middle;">settings</span>
                                SMTP Configuration
                            </h5>
                            
                            <div class="mb-3">
                                <label class="form-label fw-medium">Mail Driver</label>
                                <select class="form-select" name="mail_driver">
                                    <option value="smtp">SMTP</option>
                                    <option value="sendmail">Sendmail</option>
                                    <option value="mailgun">Mailgun</option>
                                    <option value="ses">Amazon SES</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label fw-medium">SMTP Host</label>
                                <input type="text" class="form-control" name="smtp_host" placeholder="smtp.gmail.com">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label fw-medium">SMTP Port</label>
                                <input type="number" class="form-control" name="smtp_port" placeholder="587" value="587">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label fw-medium">Encryption</label>
                                <select class="form-select" name="encryption">
                                    <option value="tls">TLS</option>
                                    <option value="ssl">SSL</option>
                                    <option value="">None</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label fw-medium">SMTP Username</label>
                                <input type="text" class="form-control" name="smtp_username" placeholder="Enter SMTP username">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label fw-medium">SMTP Password</label>
                                <input type="password" class="form-control" name="smtp_password" placeholder="Enter SMTP password">
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 mb-4">
                        <div class="card border-0 shadow-sm rounded-10 p-4 h-100">
                            <h5 class="mb-4 fw-semibold" style="color: #003471;">
                                <span class="material-symbols-outlined" style="font-size: 20px; vertical-align: middle;">email</span>
                                Email Settings
                            </h5>
                            
                            <div class="mb-3">
                                <label class="form-label fw-medium">From Email Address</label>
                                <input type="email" class="form-control" name="from_email" placeholder="noreply@school.com">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label fw-medium">From Name</label>
                                <input type="text" class="form-control" name="from_name" placeholder="School Name">
                            </div>
                            
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="email_attendance" name="email_attendance">
                                <label class="form-check-label" for="email_attendance">
                                    Send Email for Attendance
                                </label>
                            </div>
                            
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="email_fee" name="email_fee">
                                <label class="form-check-label" for="email_fee">
                                    Send Email for Fee Reminders
                                </label>
                            </div>
                            
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="email_exam" name="email_exam">
                                <label class="form-check-label" for="email_exam">
                                    Send Email for Exam Results
                                </label>
                            </div>
                            
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="email_newsletter" name="email_newsletter">
                                <label class="form-check-label" for="email_newsletter">
                                    Send Newsletter Emails
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <button type="button" class="btn btn-outline-secondary">Cancel</button>
                    <button type="button" class="btn btn-outline-primary">
                        <span class="material-symbols-outlined" style="font-size: 18px; vertical-align: middle;">send</span>
                        Test Email
                    </button>
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
// Ensure sidebar stays open on Email Settings page
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

