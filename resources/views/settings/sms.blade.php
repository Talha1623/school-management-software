@extends('layouts.app')

@section('title', 'SMS Settings')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-4 mb-4">
            <div class="d-flex align-items-center mb-4">
                <span class="material-symbols-outlined me-2" style="font-size: 28px; color: #003471;">sms</span>
                <h3 class="mb-0 fw-bold" style="color: #003471;">SMS Settings</h3>
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
                                SMS Gateway Configuration
                            </h5>
                            
                            <div class="mb-3">
                                <label class="form-label fw-medium">SMS Provider</label>
                                <select class="form-select" name="sms_provider">
                                    <option value="twilio">Twilio</option>
                                    <option value="nexmo">Nexmo</option>
                                    <option value="telenor">Telenor</option>
                                    <option value="jazz">Jazz</option>
                                    <option value="custom">Custom API</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label fw-medium">API Key</label>
                                <input type="text" class="form-control" name="api_key" placeholder="Enter API key">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label fw-medium">API Secret</label>
                                <input type="password" class="form-control" name="api_secret" placeholder="Enter API secret">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label fw-medium">Sender ID</label>
                                <input type="text" class="form-control" name="sender_id" placeholder="Enter sender ID">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label fw-medium">API URL</label>
                                <input type="url" class="form-control" name="api_url" placeholder="https://api.example.com/sms">
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 mb-4">
                        <div class="card border-0 shadow-sm rounded-10 p-4 h-100">
                            <h5 class="mb-4 fw-semibold" style="color: #003471;">
                                <span class="material-symbols-outlined" style="font-size: 20px; vertical-align: middle;">notifications_active</span>
                                SMS Notification Settings
                            </h5>
                            
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="sms_attendance" name="sms_attendance">
                                <label class="form-check-label" for="sms_attendance">
                                    Send SMS for Attendance
                                </label>
                            </div>
                            
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="sms_fee" name="sms_fee">
                                <label class="form-check-label" for="sms_fee">
                                    Send SMS for Fee Reminders
                                </label>
                            </div>
                            
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="sms_exam" name="sms_exam">
                                <label class="form-check-label" for="sms_exam">
                                    Send SMS for Exam Results
                                </label>
                            </div>
                            
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="sms_holiday" name="sms_holiday">
                                <label class="form-check-label" for="sms_holiday">
                                    Send SMS for Holidays
                                </label>
                            </div>
                            
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="sms_events" name="sms_events">
                                <label class="form-check-label" for="sms_events">
                                    Send SMS for Events
                                </label>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label fw-medium">SMS Credit Balance</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" value="0" readonly>
                                    <button type="button" class="btn btn-outline-primary">Check Balance</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <button type="button" class="btn btn-outline-secondary">Cancel</button>
                    <button type="button" class="btn btn-outline-primary">
                        <span class="material-symbols-outlined" style="font-size: 18px; vertical-align: middle;">send</span>
                        Test SMS
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
// Ensure sidebar stays open on SMS Settings page
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

