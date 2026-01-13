@extends('layouts.accountant')

@section('title', 'Academic / Holiday Calendar - Accountant')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-4 mb-4" style="box-shadow: 0 2px 10px rgba(0,0,0,0.08);">
            <!-- Header Section -->
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-4 gap-3">
                <div class="calendar-header" style="background: linear-gradient(135deg, #ff9800 0%, #ff6b9d 100%); padding: 12px 24px; border-radius: 8px; width: 100%; box-shadow: 0 4px 15px rgba(255, 152, 0, 0.3);">
                    <h2 class="mb-0 text-white fw-bold text-center" style="font-size: 20px; text-shadow: 0 2px 4px rgba(0,0,0,0.2);">
                        <span class="material-symbols-outlined" style="font-size: 22px; vertical-align: middle; margin-right: 8px;">calendar_month</span>
                        Academic Calendar {{ $year ?? date('Y') }}
                    </h2>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('academic-calendar.manage-events') }}" class="btn btn-sm px-3 py-2" style="background: linear-gradient(135deg, #003471 0%, #004a9f 100%); color: white; border: none; white-space: nowrap; box-shadow: 0 2px 8px rgba(0, 52, 113, 0.3);">
                        <span class="material-symbols-outlined" style="font-size: 18px; vertical-align: middle;">add</span>
                        Manage Events
                    </a>
                    <button type="button" class="btn btn-sm px-3 py-2" onclick="window.print()" style="background-color: white; color: #003471; border: 1px solid #003471; white-space: nowrap; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);">
                        <span class="material-symbols-outlined" style="font-size: 18px; vertical-align: middle;">print</span>
                        Print Calendar
                    </button>
                    <!-- Add Campus Button -->
                    <button type="button" class="btn btn-sm px-3 py-2 rounded-8" style="background: linear-gradient(135deg, #003471 0%, #004a9f 100%); color: white; border: none; white-space: nowrap; box-shadow: 0 2px 8px rgba(0, 52, 113, 0.3);" data-bs-toggle="modal" data-bs-target="#addCampusModal">
                        <span class="material-symbols-outlined" style="font-size: 18px; vertical-align: middle;">add</span>
                        Add Campus
                    </button>
                </div>
            </div>

            <!-- Year Selector -->
            <div class="mb-4 text-center">
                <div class="d-inline-flex align-items-center gap-2 p-2 rounded-8" style="background-color: #f8f9fa; border: 1px solid #e9ecef;">
                    <label for="year" class="mb-0 fw-semibold" style="color: #003471;">Select Year:</label>
                    <form method="GET" action="{{ route('accountant.academic-calendar') }}" class="d-inline">
                        <select name="year" id="year" class="form-select form-select-sm" style="width: auto; min-width: 100px; border-color: #003471;" onchange="this.form.submit()">
                            @for($y = date('Y') - 2; $y <= date('Y') + 5; $y++)
                                <option value="{{ $y }}" {{ ($year ?? date('Y')) == $y ? 'selected' : '' }}>{{ $y }}</option>
                            @endfor
                        </select>
                    </form>
                </div>
            </div>

            <!-- Calendar Grid -->
            <div class="calendar-grid" style="display: grid; grid-template-columns: repeat(6, 1fr); gap: 20px;">
                @php
                    $months = [
                        1 => ['name' => 'Jan', 'full' => 'January'],
                        2 => ['name' => 'Feb', 'full' => 'February'],
                        3 => ['name' => 'Mar', 'full' => 'March'],
                        4 => ['name' => 'Apr', 'full' => 'April'],
                        5 => ['name' => 'May', 'full' => 'May'],
                        6 => ['name' => 'Jun', 'full' => 'June'],
                        7 => ['name' => 'Jul', 'full' => 'July'],
                        8 => ['name' => 'Aug', 'full' => 'August'],
                        9 => ['name' => 'Sep', 'full' => 'September'],
                        10 => ['name' => 'Oct', 'full' => 'October'],
                        11 => ['name' => 'Nov', 'full' => 'November'],
                        12 => ['name' => 'Dec', 'full' => 'December']
                    ];
                @endphp

                @foreach($months as $monthNum => $monthData)
                    @php
                        $monthEvents = $eventsByMonth[$monthNum] ?? [];
                        $eventCount = count($monthEvents);
                    @endphp
                    <div class="month-box" style="border: 2px solid #28a745; border-radius: 8px; overflow: hidden; min-height: 180px; display: flex; flex-direction: column; background: white; box-shadow: 0 2px 8px rgba(0,0,0,0.1); transition: all 0.3s ease;">
                        <!-- Month Header -->
                        <div class="month-header" style="background: linear-gradient(135deg, #a8e6cf 0%, #88d8a3 100%); padding: 8px 12px; text-align: center; border-bottom: 2px solid #28a745;">
                            <h5 class="mb-0 fw-bold" style="color: #155724; font-size: 14px; text-shadow: 0 1px 2px rgba(255,255,255,0.5);">
                                {{ $monthData['name'] }}
                                @if($eventCount > 0)
                                    <span class="badge bg-danger ms-2" style="font-size: 9px; padding: 2px 5px;">{{ $eventCount }}</span>
                                @endif
                            </h5>
                        </div>
                        
                        <!-- Month Content -->
                        <div class="month-content" style="background: linear-gradient(to bottom, #e7f3ff 0%, #d6e9f5 100%); padding: 8px; flex: 1; min-height: 130px; overflow-y: auto; max-height: 250px;">
                            @if(count($monthEvents) > 0)
                                @foreach($monthEvents as $event)
                                    <div class="event-item mb-2" style="background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); border-radius: 6px; padding: 6px 8px; color: white; box-shadow: 0 2px 6px rgba(220, 53, 69, 0.4); border-left: 3px solid #ff9800;">
                                        <div class="event-title" style="font-weight: 700; font-size: 11px; margin-bottom: 4px; line-height: 1.3;">
                                            <span class="material-symbols-outlined" style="font-size: 12px; vertical-align: middle; margin-right: 3px;">event</span>
                                            {{ $event->event_title }}
                                        </div>
                                        @if($event->event_details)
                                            <div class="event-details" style="font-size: 10px; opacity: 0.95; margin-bottom: 4px; line-height: 1.3;">
                                                {{ Str::limit($event->event_details, 30) }}
                                            </div>
                                        @endif
                                        @if($event->event_type)
                                            <div class="event-type mb-1" style="display: inline-block;">
                                                <span class="badge" style="background-color: rgba(255,255,255,0.3); color: white; font-size: 8px; padding: 1px 5px;">
                                                    {{ $event->event_type }}
                                                </span>
                                            </div>
                                        @endif
                                        <div class="event-date" style="background: linear-gradient(135deg, #ff9800 0%, #ff6b00 100%); border-radius: 4px; padding: 3px 8px; display: inline-block; font-size: 9px; font-weight: 700; color: #000; box-shadow: 0 2px 4px rgba(255, 152, 0, 0.3);">
                                            <span class="material-symbols-outlined" style="font-size: 10px; vertical-align: middle; margin-right: 2px;">calendar_today</span>
                                            {{ $event->event_date->format('d-M-Y') }}
                                        </div>
                                    </div>
                                @endforeach
                            @else
                                <div class="text-center text-muted d-flex flex-column align-items-center justify-content-center" style="padding-top: 30px; font-size: 11px; min-height: 130px;">
                                    <span class="material-symbols-outlined" style="font-size: 24px; opacity: 0.3; margin-bottom: 6px;">event_busy</span>
                                    <span>No events</span>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Summary Section -->
            @php
                $totalEvents = count($eventsByMonth) > 0 ? array_sum(array_map('count', $eventsByMonth)) : 0;
            @endphp
            @if($totalEvents > 0)
                <div class="mt-4 p-3 rounded-8 text-center" style="background: linear-gradient(135deg, #f0f4ff 0%, #e0e7ff 100%); border: 1px solid #c2cada;">
                    <p class="mb-0 fw-semibold" style="color: #003471;">
                        <span class="material-symbols-outlined" style="font-size: 20px; vertical-align: middle; margin-right: 5px;">info</span>
                        Total Events in {{ $year ?? date('Y') }}: <strong style="color: #dc3545;">{{ $totalEvents }}</strong>
                    </p>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Add Campus Modal -->
<div class="modal fade" id="addCampusModal" tabindex="-1" aria-labelledby="addCampusModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addCampusModalLabel">Add New Campus</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addCampusForm">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="campus_name" class="form-label">Campus Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="campus_name" name="campus_name" required>
                        <div class="text-danger mt-1" id="campus_name_error"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn" style="background: linear-gradient(135deg, #003471 0%, #004a9f 100%); color: white; border: none;">Add Campus</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    /* Print Styles */
    @media print {
        .btn, .calendar-header button, .calendar-header a, .year-selector {
            display: none !important;
        }
        
        .month-box {
            page-break-inside: avoid;
            break-inside: avoid;
        }
        
        .calendar-grid {
            gap: 10px !important;
        }

        .calendar-header {
            margin-bottom: 15px !important;
        }

        .month-content {
            max-height: none !important;
        }
    }

    /* Responsive Design */
    @media (max-width: 1400px) {
        .calendar-grid {
            grid-template-columns: repeat(4, 1fr) !important;
        }
    }

    @media (max-width: 992px) {
        .calendar-grid {
            grid-template-columns: repeat(3, 1fr) !important;
        }
        
        .calendar-header {
            padding: 10px 20px !important;
        }
        
        .calendar-header h2 {
            font-size: 18px !important;
        }
        
        .calendar-header h2 .material-symbols-outlined {
            font-size: 20px !important;
        }
    }

    @media (max-width: 768px) {
        .calendar-grid {
            grid-template-columns: repeat(2, 1fr) !important;
            gap: 15px !important;
        }
        
        .calendar-header {
            padding: 10px 16px !important;
        }
        
        .calendar-header h2 {
            font-size: 16px !important;
        }

        .calendar-header h2 .material-symbols-outlined {
            font-size: 18px !important;
        }
    }

    @media (max-width: 576px) {
        .calendar-grid {
            grid-template-columns: repeat(2, 1fr) !important;
            gap: 12px !important;
        }

        .month-box {
            min-height: 150px !important;
        }

        .month-content {
            min-height: 110px !important;
        }
    }

    /* Hover Effects */
    .month-box {
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        cursor: pointer;
    }

    .month-box:hover {
        transform: translateY(-5px);
        box-shadow: 0 6px 20px rgba(0,0,0,0.15) !important;
        border-color: #20c997 !important;
    }

    .event-item {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .event-item:hover {
        transform: scale(1.03);
        box-shadow: 0 4px 12px rgba(220, 53, 69, 0.5) !important;
    }

    /* Scrollbar Styling */
    .month-content::-webkit-scrollbar {
        width: 6px;
    }

    .month-content::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 10px;
    }

    .month-content::-webkit-scrollbar-thumb {
        background: #28a745;
        border-radius: 10px;
    }

    .month-content::-webkit-scrollbar-thumb:hover {
        background: #20c997;
    }

    /* Animation */
    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .month-box {
        animation: fadeIn 0.5s ease-out;
    }

    .month-box:nth-child(1) { animation-delay: 0.05s; }
    .month-box:nth-child(2) { animation-delay: 0.1s; }
    .month-box:nth-child(3) { animation-delay: 0.15s; }
    .month-box:nth-child(4) { animation-delay: 0.2s; }
    .month-box:nth-child(5) { animation-delay: 0.25s; }
    .month-box:nth-child(6) { animation-delay: 0.3s; }
    .month-box:nth-child(7) { animation-delay: 0.35s; }
    .month-box:nth-child(8) { animation-delay: 0.4s; }
    .month-box:nth-child(9) { animation-delay: 0.45s; }
    .month-box:nth-child(10) { animation-delay: 0.5s; }
    .month-box:nth-child(11) { animation-delay: 0.55s; }
    .month-box:nth-child(12) { animation-delay: 0.6s; }

    /* Button Hover Effects */
    .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.2) !important;
    }

    /* Year Selector Styling */
    #year {
        transition: all 0.3s ease;
    }

    #year:focus {
        border-color: #003471 !important;
        box-shadow: 0 0 0 0.2rem rgba(0, 52, 113, 0.25) !important;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add Campus Form Submission
    document.getElementById('addCampusForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const errorDiv = document.getElementById('campus_name_error');
        errorDiv.textContent = '';
        
        fetch('{{ route("accountant.academic-calendar.campus.store") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
            },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Close modal and reset form
                const modal = bootstrap.Modal.getInstance(document.getElementById('addCampusModal'));
                modal.hide();
                this.reset();
                
                // Show success message
                alert('Campus added successfully!');
                // Reload page to refresh campus list
                location.reload();
            } else {
                if (data.errors && data.errors.campus_name) {
                    errorDiv.textContent = data.errors.campus_name[0];
                } else {
                    errorDiv.textContent = data.message || 'Error adding campus';
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            errorDiv.textContent = 'An error occurred. Please try again.';
        });
    });
});
</script>
@endsection
