@extends('layouts.app')

@section('title', 'Salary Setting')

@section('content')
@php
    // Format late arrival time for display
    $lateArrivalDisplay = '08:00 AM';
    if ($settings->late_arrival_time) {
        if (strlen($settings->late_arrival_time) == 8 && strpos($settings->late_arrival_time, ':') !== false) {
            // Format: HH:MM:SS
            $lateArrivalDisplay = \Carbon\Carbon::parse('2000-01-01 ' . $settings->late_arrival_time)->format('h:i A');
        } elseif (strpos($settings->late_arrival_time, 'AM') !== false || strpos($settings->late_arrival_time, 'PM') !== false) {
            // Already in 12-hour format
            $lateArrivalDisplay = $settings->late_arrival_time;
        } else {
            // Try to parse as time
            try {
                $lateArrivalDisplay = \Carbon\Carbon::parse('2000-01-01 ' . $settings->late_arrival_time)->format('h:i A');
            } catch (\Exception $e) {
                $lateArrivalDisplay = $settings->late_arrival_time;
            }
        }
    }
    
    // Format early exit time for display
    $earlyExitDisplay = '';
    if ($settings->early_exit_time) {
        if (strlen($settings->early_exit_time) == 8 && strpos($settings->early_exit_time, ':') !== false) {
            // Format: HH:MM:SS
            $earlyExitDisplay = \Carbon\Carbon::parse('2000-01-01 ' . $settings->early_exit_time)->format('h:i A');
        } elseif (strpos($settings->early_exit_time, 'AM') !== false || strpos($settings->early_exit_time, 'PM') !== false) {
            // Already in 12-hour format
            $earlyExitDisplay = $settings->early_exit_time;
        } else {
            // Try to parse as time
            try {
                $earlyExitDisplay = \Carbon\Carbon::parse('2000-01-01 ' . $settings->early_exit_time)->format('h:i A');
            } catch (\Exception $e) {
                $earlyExitDisplay = $settings->early_exit_time;
            }
        }
    }
@endphp
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
                    <div class="col-md-6">
                        <div class="mb-3" style="min-height: 90px;">
                            <label for="late_arrival_time" class="form-label mb-2 fw-semibold" style="color: #003471; font-size: 14px;">Late Arrival Time</label>
                            <div class="input-group" style="height: 38px;">
                                <input type="text" class="form-control" name="late_arrival_time" id="late_arrival_time" value="{{ $lateArrivalDisplay }}" required style="height: 38px;">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471; height: 38px;">
                                    <span class="material-symbols-outlined" style="font-size: 18px;">schedule</span>
                                </span>
                            </div>
                            <small class="text-muted" style="font-size: 12px; cursor: help; display: block; margin-top: 4px;" title="Mark as late arrival on attendance after this time">
                                Mark as late arrival on attendance after this time
                            </small>
                        </div>
                    </div>

                    <!-- Early Exit Time -->
                    <div class="col-md-6">
                        <div class="mb-3" style="min-height: 90px;">
                            <label for="early_exit_time" class="form-label mb-2 fw-semibold" style="color: #003471; font-size: 14px;">Early Exit Time</label>
                            <div class="input-group" style="height: 38px;">
                                <input type="text" class="form-control" name="early_exit_time" id="early_exit_time" value="{{ $earlyExitDisplay }}" style="height: 38px;">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471; height: 38px;">
                                    <span class="material-symbols-outlined" style="font-size: 18px;">logout</span>
                                </span>
                            </div>
                            <small class="text-muted" style="font-size: 12px; cursor: help; display: block; margin-top: 4px;" title="Mark as early exit on attendance before this time">
                                Mark as early exit on attendance before this time
                            </small>
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
                        <span style="color: white;">Save Changes</span>
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
// Time picker functionality (improved implementation)
function setupTimePicker(inputId) {
    const input = document.getElementById(inputId);
    if (!input) return;
    
    // Store original value
    let originalValue = input.value;
    
    // Function to convert 12-hour to 24-hour format
    function convertTo24Hour(time12h) {
        if (!time12h) return '';
        const timeMatch = time12h.match(/(\d{1,2}):(\d{2})\s*(AM|PM)/i);
        if (timeMatch) {
            let hours = parseInt(timeMatch[1]);
            const minutes = timeMatch[2];
            const ampm = timeMatch[3].toUpperCase();
            
            if (ampm === 'PM' && hours !== 12) {
                hours += 12;
            } else if (ampm === 'AM' && hours === 12) {
                hours = 0;
            }
            
            return `${hours.toString().padStart(2, '0')}:${minutes}`;
        }
        return '';
    }
    
    // Function to convert 24-hour to 12-hour format
    function convertTo12Hour(time24h) {
        if (!time24h) return '';
        const timeMatch = time24h.match(/(\d{1,2}):(\d{2})/);
        if (timeMatch) {
            const hours = parseInt(timeMatch[1]);
            const minutes = timeMatch[2];
            const ampm = hours >= 12 ? 'PM' : 'AM';
            const hours12 = hours % 12 || 12;
            return `${hours12.toString().padStart(2, '0')}:${minutes} ${ampm}`;
        }
        return '';
    }
    
    input.addEventListener('focus', function() {
        // Save current value before switching to time type
        if (this.type === 'text' && this.value) {
            originalValue = this.value;
        }
        
        // Convert to time type
        this.type = 'time';
        
        // If we have a value in 12-hour format, convert it to 24-hour for time input
        if (this.value && (this.value.includes('AM') || this.value.includes('PM'))) {
            const time24h = convertTo24Hour(this.value);
            if (time24h) {
                this.value = time24h;
            }
        } else if (this.value && !this.value.includes(':')) {
            // If value is empty or invalid, use original value
            if (originalValue) {
                const time24h = convertTo24Hour(originalValue);
                if (time24h) {
                    this.value = time24h;
                }
            }
        }
    });

    input.addEventListener('change', function() {
        // When time is selected, immediately convert to 12-hour format and store
        if (this.value && this.type === 'time') {
            const formattedTime = convertTo12Hour(this.value);
            if (formattedTime) {
                // Store in both data attribute and directly update value
                this.setAttribute('data-time-value', formattedTime);
                // Switch back to text and set the formatted value
                this.type = 'text';
                this.value = formattedTime;
            }
        }
    });

    input.addEventListener('blur', function(e) {
        // Use a longer delay to ensure time picker has closed
        setTimeout(() => {
            // If we have a stored formatted value, use it
            const storedValue = this.getAttribute('data-time-value');
            if (storedValue) {
                this.value = storedValue;
                this.removeAttribute('data-time-value');
                this.type = 'text';
            } else if (this.value && this.type === 'time') {
                // Convert 24-hour format to 12-hour format
                const formattedTime = convertTo12Hour(this.value);
                if (formattedTime) {
                    this.value = formattedTime;
                }
                this.type = 'text';
            } else if (this.type === 'time') {
                // If no value but still time type, switch back to text
                this.type = 'text';
                // Restore original value if available
                if (originalValue) {
                    this.value = originalValue;
                }
            }
        }, 300);
    });
    
    // Handle input event to capture time selection
    input.addEventListener('input', function() {
        // Store the value when user types or selects
        if (this.type === 'time' && this.value) {
            const formattedTime = convertTo12Hour(this.value);
            if (formattedTime) {
                this.setAttribute('data-time-value', formattedTime);
            }
        }
    });
}

// Setup time pickers when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // Format time on page load
    function formatTimeOnLoad(inputId) {
        const timeInput = document.getElementById(inputId);
        if (timeInput && timeInput.value) {
            // If value doesn't have AM/PM, convert it
            if (!timeInput.value.includes('AM') && !timeInput.value.includes('PM')) {
                // Convert if needed (HH:MM:SS or HH:MM format)
                const timeMatch = timeInput.value.match(/(\d{1,2}):(\d{2})(?::\d{2})?/);
                if (timeMatch) {
                    const hours = parseInt(timeMatch[1]);
                    const minutes = timeMatch[2];
                    const ampm = hours >= 12 ? 'PM' : 'AM';
                    const hours12 = hours % 12 || 12;
                    timeInput.value = `${hours12.toString().padStart(2, '0')}:${minutes} ${ampm}`;
                }
            }
        }
    }
    
    formatTimeOnLoad('late_arrival_time');
    formatTimeOnLoad('early_exit_time');
    
    // Setup time pickers after formatting
    setupTimePicker('late_arrival_time');
    setupTimePicker('early_exit_time');
});

// Ensure values are properly formatted before form submission
document.getElementById('salarySettingForm').addEventListener('submit', function(e) {
    const lateArrivalInput = document.getElementById('late_arrival_time');
    const earlyExitInput = document.getElementById('early_exit_time');
    
    // Function to ensure proper format
    function ensureProperFormat(input) {
        if (!input) return;
        
        // Check for stored value first
        const storedValue = input.getAttribute('data-time-value');
        if (storedValue) {
            input.value = storedValue;
            input.type = 'text';
            input.removeAttribute('data-time-value');
            return;
        }
        
        // If input is time type, convert to 12-hour format
        if (input.type === 'time' && input.value) {
            const time = input.value.split(':');
            const hours = parseInt(time[0]);
            const minutes = time[1];
            const ampm = hours >= 12 ? 'PM' : 'AM';
            const hours12 = hours % 12 || 12;
            input.value = `${hours12.toString().padStart(2, '0')}:${minutes} ${ampm}`;
            input.type = 'text';
        } else if (input.type === 'time') {
            // If time type but no value, switch back to text
            input.type = 'text';
        }
    }
    
    ensureProperFormat(lateArrivalInput);
    ensureProperFormat(earlyExitInput);
});
</script>
@endsection
