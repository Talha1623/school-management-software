@extends('layouts.app')

@section('title', 'Progress Tracking')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-4 mb-4">
            <h4 class="mb-4 fs-18 fw-semibold" style="color: #003471;">Track Student Behavior</h4>
            
            <!-- Search Form -->
            <form action="{{ route('student-behavior.progress-tracking') }}" method="GET" class="mb-4">
                <div class="row g-2 align-items-end">
                    <div class="col-md-10">
                        <label for="searchInput" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Search Student</label>
                        <input type="text" 
                               id="searchInput" 
                               name="search" 
                               class="form-control form-control-lg" 
                               placeholder="Type Student Name / Roll Number..." 
                               value="{{ request('search') }}"
                               style="height: 48px; border-radius: 8px; border: 1px solid #dee2e6; font-size: 14px;">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-lg w-100 track-behavior-btn" style="height: 48px; border-radius: 8px;">
                            <span class="material-symbols-outlined" style="font-size: 20px; vertical-align: middle;">search</span>
                            <span style="font-size: 14px; vertical-align: middle;">Track Behavior</span>
                        </button>
                    </div>
                </div>
            </form>

            <!-- Illustration Section -->
            <div class="text-center mb-4">
                <div class="d-inline-block position-relative" style="width: 300px; height: 300px;">
                    <!-- Yellow Circle Background -->
                    <div class="position-absolute" style="width: 300px; height: 300px; background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%); border-radius: 50%; top: 0; left: 0; box-shadow: 0 8px 24px rgba(255, 215, 0, 0.3);"></div>
                    
                    <!-- ID Cards -->
                    <div class="position-absolute" style="top: 50%; left: 50%; transform: translate(-50%, -50%);">
                        <!-- Back Card (White) -->
                        <div class="position-relative" style="width: 180px; height: 120px; background: white; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); transform: rotate(-5deg) translate(10px, 10px);">
                            <div style="padding: 15px;">
                                <div style="width: 50px; height: 50px; background: #e9ecef; border-radius: 8px; margin-bottom: 8px;"></div>
                                <div style="height: 8px; background: #e9ecef; border-radius: 4px; margin-bottom: 6px;"></div>
                                <div style="height: 8px; background: #e9ecef; border-radius: 4px; width: 80%;"></div>
                            </div>
                        </div>
                        
                        <!-- Front Card (Red) -->
                        <div class="position-relative" style="width: 180px; height: 120px; background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); border-radius: 12px; box-shadow: 0 6px 16px rgba(220, 53, 69, 0.4); transform: rotate(2deg); z-index: 2;">
                            <div style="padding: 15px; display: flex; align-items: center; gap: 12px; height: 100%;">
                                <!-- Person Silhouette -->
                                <div style="width: 50px; height: 50px; background: #003471; border-radius: 50%; position: relative;">
                                    <div style="position: absolute; top: 12px; left: 50%; transform: translateX(-50%); width: 20px; height: 20px; background: white; border-radius: 50%;"></div>
                                    <div style="position: absolute; bottom: 8px; left: 50%; transform: translateX(-50%); width: 30px; height: 20px; background: white; border-radius: 15px 15px 0 0;"></div>
                                </div>
                                
                                <!-- Text Lines -->
                                <div style="flex: 1;">
                                    <div style="height: 6px; background: white; border-radius: 3px; margin-bottom: 6px; width: 90%;"></div>
                                    <div style="height: 6px; background: white; border-radius: 3px; margin-bottom: 6px; width: 75%;"></div>
                                    <div style="height: 6px; background: white; border-radius: 3px; width: 60%;"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <p class="mt-4 mb-0" style="color: #666; font-size: 14px; font-weight: 500;">
                    Scan Student ID Card For Quick Behavior Tracking...!
                </p>
            </div>

            <!-- Student Results -->
            @if(request()->has('search'))
                @if($student)
                    <div class="mt-4">
                        <div class="card border-0 shadow-sm mb-3" style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);">
                            <div class="card-body p-3">
                                <div class="row align-items-center">
                                    <div class="col-md-2 text-center">
                                        @if($student->photo)
                                            <img src="{{ asset('storage/' . $student->photo) }}" alt="{{ $student->student_name }}" class="rounded-circle" style="width: 80px; height: 80px; object-fit: cover; border: 3px solid #003471;">
                                        @else
                                            <div class="rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 80px; height: 80px; background: #003471; color: white; font-size: 32px; font-weight: bold;">
                                                {{ strtoupper(substr($student->student_name, 0, 1)) }}
                                            </div>
                                        @endif
                                    </div>
                                    <div class="col-md-10">
                                        <h5 class="mb-1 fw-bold" style="color: #003471;">{{ $student->student_name }}</h5>
                                        <p class="mb-1 text-muted" style="font-size: 13px;">
                                            <strong>Code:</strong> {{ $student->student_code ?? 'N/A' }} | 
                                            <strong>GR Number:</strong> {{ $student->gr_number ?? 'N/A' }} | 
                                            <strong>Class:</strong> {{ $student->class }} | 
                                            <strong>Section:</strong> {{ $student->section ?? 'N/A' }}
                                        </p>
                                        <p class="mb-0 text-muted" style="font-size: 13px;">
                                            <strong>Campus:</strong> {{ $student->campus ?? 'N/A' }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Behavior Records -->
                        @if($behaviorRecords->count() > 0)
                            <div class="card border-0 shadow-sm">
                                <div class="card-header bg-white border-bottom">
                                    <h5 class="mb-0 fs-15 fw-semibold d-flex align-items-center gap-2" style="color: #003471;">
                                        <span class="material-symbols-outlined" style="font-size: 18px;">history</span>
                                        <span>Behavior History</span>
                                    </h5>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-hover mb-0">
                                            <thead style="background-color: #f8f9fa;">
                                                <tr>
                                                    <th style="font-size: 12px; padding: 10px;">Date</th>
                                                    <th style="font-size: 12px; padding: 10px;">Type</th>
                                                    <th style="font-size: 12px; padding: 10px;">Description</th>
                                                    <th style="font-size: 12px; padding: 10px;">Recorded By</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($behaviorRecords as $record)
                                                <tr>
                                                    <td style="font-size: 12px; padding: 10px;">{{ date('d M Y', strtotime($record->date)) }}</td>
                                                    <td style="font-size: 12px; padding: 10px;">
                                                        @if($record->type == 'Positive' || $record->type == 'Excellent')
                                                            <span class="badge bg-success">{{ $record->type }}</span>
                                                        @elseif($record->type == 'Negative' || $record->type == 'Warning')
                                                            <span class="badge bg-danger">{{ $record->type }}</span>
                                                        @else
                                                            <span class="badge bg-warning text-dark">{{ $record->type }}</span>
                                                        @endif
                                                    </td>
                                                    <td style="font-size: 12px; padding: 10px;">{{ $record->description ?? 'N/A' }}</td>
                                                    <td style="font-size: 12px; padding: 10px;">{{ $record->recorded_by ?? 'N/A' }}</td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="alert alert-info mb-0">
                                <span class="material-symbols-outlined" style="font-size: 18px; vertical-align: middle;">info</span>
                                <span>No behavior records found for this student.</span>
                            </div>
                        @endif
                    </div>
                @else
                    <div class="alert alert-warning mt-4">
                        <span class="material-symbols-outlined" style="font-size: 18px; vertical-align: middle;">warning</span>
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
    box-shadow: 0 4px 12px rgba(0, 52, 113, 0.25);
}

.track-behavior-btn:hover {
    background: linear-gradient(135deg, #004a9f 0%, #003471 100%);
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(0, 52, 113, 0.35);
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

.badge {
    font-size: 11px;
    padding: 4px 8px;
}
</style>
@endsection
