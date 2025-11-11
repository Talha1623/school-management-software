@extends('layouts.app')

@section('title', 'Salary Setting')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-4 mb-4">
            <h4 class="mb-4 fs-16 fw-semibold">Salary Setting</h4>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <form action="{{ route('salary-loan.salary-setting.update') }}" method="POST" id="salarySettingForm">
                @csrf
                @method('PUT')
                
                <div class="row g-3">
                    <!-- Late Arrival Time -->
                    <div class="col-12">
                        <div class="mb-3">
                            <label for="late_arrival_time" class="form-label mb-2 fw-semibold" style="color: #003471; font-size: 14px;">Late Arrival Time</label>
                            <div class="d-flex align-items-center gap-3">
                                <div class="input-group" style="max-width: 200px;">
                                    <input type="text" class="form-control" name="late_arrival_time" id="late_arrival_time" value="{{ $settings->late_arrival_time ? (strlen($settings->late_arrival_time) == 8 ? \Carbon\Carbon::parse('2000-01-01 ' . $settings->late_arrival_time)->format('h:i A') : $settings->late_arrival_time) : '08:00 AM' }}" required>
                                    <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                        <span class="material-symbols-outlined" style="font-size: 18px;">schedule</span>
                                    </span>
                                </div>
                                <span class="text-primary" style="font-size: 13px; cursor: help;" title="Mark as late arrival on attendance after this time">
                                    Mark as late arrival on attendance after this time
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Free Absents -->
                    <div class="col-12">
                        <div class="mb-3">
                            <label for="free_absents" class="form-label mb-2 fw-semibold" style="color: #003471; font-size: 14px;">Free Absents</label>
                            <div class="d-flex align-items-center gap-3">
                                <div class="input-group" style="max-width: 200px;">
                                    <input type="number" class="form-control" name="free_absents" id="free_absents" value="{{ $settings->free_absents ?? 2 }}" min="0" required>
                                </div>
                                <span class="text-primary" style="font-size: 13px; cursor: help;" title="Do not deduct salary if absents are less or equal to this">
                                    Do not deduct salary if absents are less or equal to this
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Leave Deduction -->
                    <div class="col-12">
                        <div class="mb-3">
                            <label for="leave_deduction" class="form-label mb-2 fw-semibold" style="color: #003471; font-size: 14px;">Leave Deduction</label>
                            <div class="d-flex align-items-center gap-3">
                                <div class="input-group" style="max-width: 200px;">
                                    <select class="form-select" name="leave_deduction" id="leave_deduction" required>
                                        <option value="No" {{ ($settings->leave_deduction ?? 'No') == 'No' ? 'selected' : '' }}>No</option>
                                        <option value="Yes" {{ ($settings->leave_deduction ?? 'No') == 'Yes' ? 'selected' : '' }}>Yes</option>
                                    </select>
                                    <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                        <span class="material-symbols-outlined" style="font-size: 18px;">arrow_drop_down</span>
                                    </span>
                                </div>
                                <span class="text-primary" style="font-size: 13px; cursor: help;" title="deduct salary for the day if teacher is on leave">
                                    deduct salary for the day if teacher is on leave
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Information Message -->
                <div class="alert alert-danger mt-4 mb-4" role="alert" style="background-color: #fee; border-color: #fcc; color: #c33;">
                    <strong>Note:</strong> These settings are only useful for monthly salaries, and will not affect subject/lecture wise or hourly salary generations.
                </div>

                <!-- Save Button -->
                <div class="d-flex justify-content-end">
                    <button type="submit" class="btn btn-success d-inline-flex align-items-center gap-2" style="background-color: #28a745; border: none; padding: 10px 20px; font-weight: 500;">
                        <span>Save Changes</span>
                        <span class="material-symbols-outlined" style="font-size: 18px; color: white;">thumb_up</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    .form-control:focus,
    .form-select:focus {
        border-color: #003471;
        box-shadow: 0 0 0 0.2rem rgba(0, 52, 113, 0.25);
    }

    .input-group .form-control,
    .input-group .form-select {
        border-right: none;
    }

    .input-group-text {
        border-left: none;
    }

    .input-group:focus-within .input-group-text {
        background-color: #003471 !important;
        color: white !important;
        border-color: #003471;
    }

    .btn-success:hover {
        background-color: #218838 !important;
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(40, 167, 69, 0.3);
    }

    .btn-success:active {
        transform: translateY(0);
    }
</style>

<script>
// Time picker functionality (simple implementation)
document.getElementById('late_arrival_time').addEventListener('focus', function() {
    this.type = 'time';
});

document.getElementById('late_arrival_time').addEventListener('blur', function() {
    if (this.value) {
        // Convert 24-hour format to 12-hour format
        const time = this.value.split(':');
        const hours = parseInt(time[0]);
        const minutes = time[1];
        const ampm = hours >= 12 ? 'PM' : 'AM';
        const hours12 = hours % 12 || 12;
        this.value = `${hours12.toString().padStart(2, '0')}:${minutes} ${ampm}`;
        this.type = 'text';
    }
});

// Format time on page load
document.addEventListener('DOMContentLoaded', function() {
    const timeInput = document.getElementById('late_arrival_time');
    if (timeInput.value && !timeInput.value.includes('AM') && !timeInput.value.includes('PM')) {
        // Convert if needed
        const time = timeInput.value.split(':');
        const hours = parseInt(time[0]);
        const minutes = time[1] || '00';
        const ampm = hours >= 12 ? 'PM' : 'AM';
        const hours12 = hours % 12 || 12;
        timeInput.value = `${hours12.toString().padStart(2, '0')}:${minutes} ${ampm}`;
    }
});
</script>
@endsection
