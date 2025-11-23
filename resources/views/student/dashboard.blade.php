@extends('layouts.student')

@section('title', 'Student Dashboard')

@section('content')
<div class="row">
    <div class="col-12">
        <!-- Dashboard Title -->
        <div class="d-flex align-items-center mb-4">
            <h2 class="mb-0 fs-20 fw-semibold text-dark me-2">Student Dashboard</h2>
            <span class="material-symbols-outlined text-secondary" style="font-size: 20px;">dashboard</span>
        </div>

        <!-- Welcome Card -->
        <div class="card border-0 shadow-sm mb-4" style="background: linear-gradient(135deg, #003471 0%, #004a9f 100%); border-radius: 12px;">
            <div class="card-body p-4">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h3 class="text-white mb-2 fw-bold">Welcome, {{ $student->student_name }}!</h3>
                        <p class="text-white mb-0" style="opacity: 0.9;">
                            <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">school</span>
                            {{ $student->class }} @if($student->section) - {{ $student->section }} @endif
                        </p>
                    </div>
                    <div class="col-md-4 text-end">
                        @if($student->photo)
                            <img src="{{ Storage::url($student->photo) }}" alt="Student Photo" class="rounded-circle" style="width: 100px; height: 100px; object-fit: cover; border: 3px solid rgba(255,255,255,0.3);">
                        @else
                            <div class="rounded-circle d-inline-flex align-items-center justify-content-center bg-white bg-opacity-20" style="width: 100px; height: 100px; border: 3px solid rgba(255,255,255,0.3);">
                                <span class="material-symbols-outlined text-white" style="font-size: 48px;">person</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row g-3">
            <!-- Student Code Card -->
            <div class="col-md-3">
                <div class="card border-0 shadow-sm" style="background: #0066cc; border-radius: 8px; overflow: hidden;">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <p class="text-white mb-1" style="font-size: 11px; font-weight: 500;">Student Code</p>
                                <h4 class="text-white mb-0 fw-bold" style="font-size: 18px;">{{ $student->student_code ?? 'N/A' }}</h4>
                            </div>
                            <span class="material-symbols-outlined text-white" style="font-size: 32px; opacity: 0.3;">qr_code</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Class Card -->
            <div class="col-md-3">
                <div class="card border-0 shadow-sm" style="background: #28a745; border-radius: 8px; overflow: hidden;">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <p class="text-white mb-1" style="font-size: 11px; font-weight: 500;">Class</p>
                                <h4 class="text-white mb-0 fw-bold" style="font-size: 18px;">{{ $student->class ?? 'N/A' }}</h4>
                            </div>
                            <span class="material-symbols-outlined text-white" style="font-size: 32px; opacity: 0.3;">class</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section Card -->
            <div class="col-md-3">
                <div class="card border-0 shadow-sm" style="background: #17a2b8; border-radius: 8px; overflow: hidden;">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <p class="text-white mb-1" style="font-size: 11px; font-weight: 500;">Section</p>
                                <h4 class="text-white mb-0 fw-bold" style="font-size: 18px;">{{ $student->section ?? 'N/A' }}</h4>
                            </div>
                            <span class="material-symbols-outlined text-white" style="font-size: 32px; opacity: 0.3;">groups</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Campus Card -->
            <div class="col-md-3">
                <div class="card border-0 shadow-sm" style="background: #ff9800; border-radius: 8px; overflow: hidden;">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <p class="text-white mb-1" style="font-size: 11px; font-weight: 500;">Campus</p>
                                <h4 class="text-white mb-0 fw-bold" style="font-size: 18px;">{{ $student->campus ?? 'N/A' }}</h4>
                            </div>
                            <span class="material-symbols-outlined text-white" style="font-size: 32px; opacity: 0.3;">business</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Student Information -->
        <div class="row mt-4">
            <div class="col-md-6">
                <div class="card border-0 shadow-sm rounded-3">
                    <div class="card-header bg-transparent border-0 pb-0">
                        <h5 class="mb-0 fw-semibold fs-15" style="color: #003471;">
                            <span class="material-symbols-outlined" style="font-size: 18px; vertical-align: middle;">person</span>
                            Personal Information
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="text-muted small mb-1">Full Name</label>
                            <p class="mb-0 fw-medium">{{ $student->student_name }} @if($student->surname_caste) {{ $student->surname_caste }} @endif</p>
                        </div>
                        <div class="mb-3">
                            <label class="text-muted small mb-1">Gender</label>
                            <p class="mb-0 fw-medium text-capitalize">{{ $student->gender ?? 'N/A' }}</p>
                        </div>
                        <div class="mb-3">
                            <label class="text-muted small mb-1">Date of Birth</label>
                            <p class="mb-0 fw-medium">{{ $student->date_of_birth ? $student->date_of_birth->format('d M Y') : 'N/A' }}</p>
                        </div>
                        @if($student->b_form_number)
                        <div class="mb-3">
                            <label class="text-muted small mb-1">B-Form Number</label>
                            <p class="mb-0 fw-medium">{{ $student->b_form_number }}</p>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card border-0 shadow-sm rounded-3">
                    <div class="card-header bg-transparent border-0 pb-0">
                        <h5 class="mb-0 fw-semibold fs-15" style="color: #003471;">
                            <span class="material-symbols-outlined" style="font-size: 18px; vertical-align: middle;">family_restroom</span>
                            Parent Information
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="text-muted small mb-1">Father Name</label>
                            <p class="mb-0 fw-medium">{{ $student->father_name ?? 'N/A' }}</p>
                        </div>
                        @if($student->father_phone)
                        <div class="mb-3">
                            <label class="text-muted small mb-1">Father Phone</label>
                            <p class="mb-0 fw-medium">{{ $student->father_phone }}</p>
                        </div>
                        @endif
                        @if($student->mother_phone)
                        <div class="mb-3">
                            <label class="text-muted small mb-1">Mother Phone</label>
                            <p class="mb-0 fw-medium">{{ $student->mother_phone }}</p>
                        </div>
                        @endif
                        @if($student->whatsapp_number)
                        <div class="mb-3">
                            <label class="text-muted small mb-1">WhatsApp Number</label>
                            <p class="mb-0 fw-medium">{{ $student->whatsapp_number }}</p>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .card {
        transition: all 0.3s ease;
    }
    
    .card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1) !important;
    }
</style>
@endpush

