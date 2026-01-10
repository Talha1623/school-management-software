@extends('layouts.app')

@php
use Illuminate\Support\Facades\Storage;
@endphp

@section('title', 'Student Details')

@section('content')
<style>
    .student-detail-card {
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        transition: all 0.3s ease;
        border: none;
    }
    
    .student-detail-card:hover {
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.12);
    }
    
    .info-label {
        font-size: 12px;
        font-weight: 600;
        color: #6c757d;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 6px;
    }
    
    .info-value {
        font-size: 14px;
        font-weight: 500;
        color: #212529;
        margin-bottom: 0;
    }
    
    .section-header {
        background: linear-gradient(135deg, #003471 0%, #0056b3 100%);
        border-radius: 8px 8px 0 0;
        padding: 12px 20px;
        margin: 0;
    }
    
    .section-header h5 {
        margin: 0;
        font-size: 15px;
        font-weight: 600;
        color: white;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .photo-container {
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    }
    
    .photo-placeholder {
        background: linear-gradient(135deg, #003471 0%, #0056b3 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        height: 250px;
    }
    
    .info-item {
        padding: 12px 0;
        border-bottom: 1px solid #f0f0f0;
    }
    
    .info-item:last-child {
        border-bottom: none;
    }
    
    .badge-custom {
        padding: 6px 12px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 600;
    }
    
    .back-btn {
        background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%);
        border: none;
        border-radius: 8px;
        padding: 8px 16px;
        color: white;
        font-weight: 500;
        transition: all 0.3s ease;
    }
    
    .back-btn:hover {
        background: linear-gradient(135deg, #5a6268 0%, #495057 100%);
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        color: white;
    }
</style>

<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-4 mb-4">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h4 class="mb-0 fs-18 fw-bold" style="color: #003471;">Student Details</h4>
                    <p class="text-muted mb-0" style="font-size: 13px; margin-top: 4px;">
                        <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">person</span>
                        Complete student information
                    </p>
                </div>
                <a href="{{ route('student.information') }}" class="back-btn">
                    <span class="material-symbols-outlined" style="font-size: 18px; vertical-align: middle;">arrow_back</span>
                    <span>Back to List</span>
                </a>
            </div>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert" style="border-radius: 8px;">
                    <span class="material-symbols-outlined" style="font-size: 18px; vertical-align: middle; margin-right: 8px;">check_circle</span>
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <div class="row g-4">
                <!-- Student Photo Card -->
                <div class="col-lg-4 col-md-5">
                    <div class="card student-detail-card h-100">
                        <div class="photo-container">
                            @if($student->photo)
                                <img src="{{ Storage::url($student->photo) }}" alt="Student Photo" class="img-fluid w-100" style="height: 300px; object-fit: cover; display: block;">
                            @else
                                <div class="photo-placeholder">
                                    <span class="material-symbols-outlined" style="font-size: 80px; color: rgba(255, 255, 255, 0.7);">person</span>
                                </div>
                            @endif
                        </div>
                        <div class="card-body text-center p-4">
                            <h4 class="mb-1 fw-bold" style="color: #003471;">{{ $student->student_name }}</h4>
                            @if($student->surname_caste)
                                <p class="text-muted mb-3" style="font-size: 14px;">({{ $student->surname_caste }})</p>
                            @endif
                            @if($student->student_code)
                                <span class="badge bg-info text-white badge-custom mb-2">
                                    <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">badge</span>
                                    {{ $student->student_code }}
                                </span>
                            @endif
                            @if($student->gr_number)
                                <p class="text-muted mb-0" style="font-size: 12px;">
                                    <strong>GR:</strong> {{ $student->gr_number }}
                                </p>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Student Information Cards -->
                <div class="col-lg-8 col-md-7">
                    <!-- Personal Information -->
                    <div class="card student-detail-card mb-3">
                        <div class="section-header">
                            <h5>
                                <span class="material-symbols-outlined" style="font-size: 18px;">person</span>
                                Personal Information
                            </h5>
                        </div>
                        <div class="card-body p-4">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="info-item">
                                        <div class="info-label">
                                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">transgender</span>
                                            Gender
                                        </div>
                                        <div class="info-value">
                                            @php
                                                $genderClass = match($student->gender) {
                                                    'male' => 'bg-info',
                                                    'female' => 'bg-danger',
                                                    default => 'bg-secondary'
                                                };
                                            @endphp
                                            <span class="badge {{ $genderClass }} text-white text-capitalize badge-custom">
                                                {{ $student->gender ?? 'N/A' }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-item">
                                        <div class="info-label">
                                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">calendar_today</span>
                                            Date of Birth
                                        </div>
                                        <div class="info-value">
                                            {{ $student->date_of_birth ? $student->date_of_birth->format('d M Y') : 'N/A' }}
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-item">
                                        <div class="info-label">
                                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">location_on</span>
                                            Place of Birth
                                        </div>
                                        <div class="info-value">{{ $student->place_of_birth ?? 'N/A' }}</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-item">
                                        <div class="info-label">
                                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">church</span>
                                            Religion
                                        </div>
                                        <div class="info-value">{{ $student->religion ?? 'N/A' }}</div>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="info-item">
                                        <div class="info-label">
                                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">home</span>
                                            Home Address
                                        </div>
                                        <div class="info-value">{{ $student->home_address ?? 'N/A' }}</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-item">
                                        <div class="info-label">
                                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">description</span>
                                            B-Form Number
                                        </div>
                                        <div class="info-value">{{ $student->b_form_number ?? 'N/A' }}</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-item">
                                        <div class="info-label">
                                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">school</span>
                                            Previous School
                                        </div>
                                        <div class="info-value">{{ $student->previous_school ?? 'N/A' }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Academic Information -->
                    <div class="card student-detail-card mb-3">
                        <div class="section-header" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%);">
                            <h5>
                                <span class="material-symbols-outlined" style="font-size: 18px;">school</span>
                                Academic Information
                            </h5>
                        </div>
                        <div class="card-body p-4">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <div class="info-item">
                                        <div class="info-label">
                                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">business</span>
                                            Campus
                                        </div>
                                        <div class="info-value">
                                            <span class="badge bg-primary text-white badge-custom">{{ $student->campus ?? 'N/A' }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="info-item">
                                        <div class="info-label">
                                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">class</span>
                                            Class
                                        </div>
                                        <div class="info-value">
                                            <span class="badge bg-info text-white badge-custom">{{ $student->class ?? 'N/A' }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="info-item">
                                        <div class="info-label">
                                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">groups</span>
                                            Section
                                        </div>
                                        <div class="info-value">
                                            <span class="badge bg-secondary text-white badge-custom">{{ $student->section ?? 'N/A' }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-item">
                                        <div class="info-label">
                                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">event</span>
                                            Admission Date
                                        </div>
                                        <div class="info-value">
                                            {{ $student->admission_date ? $student->admission_date->format('d M Y') : 'N/A' }}
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-item">
                                        <div class="info-label">
                                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">payments</span>
                                            Monthly Fee
                                        </div>
                                        <div class="info-value">
                                            <strong style="color: #28a745;">Rs. {{ number_format($student->monthly_fee ?? 0, 2) }}</strong>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-item">
                                        <div class="info-label">
                                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">discount</span>
                                            Discounted Student
                                        </div>
                                        <div class="info-value">
                                            @if($student->discounted_student)
                                                <span class="badge bg-success badge-custom">Yes</span>
                                            @else
                                                <span class="badge bg-secondary badge-custom">No</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-item">
                                        <div class="info-label">
                                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">directions_bus</span>
                                            Transport Route
                                        </div>
                                        <div class="info-value">{{ $student->transport_route ?? 'N/A' }}</div>
                                    </div>
                                </div>
                                @if($student->transport_fare)
                                <div class="col-md-6">
                                    <div class="info-item">
                                        <div class="info-label">
                                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">payments</span>
                                            Transport Fare
                                        </div>
                                        <div class="info-value">{{ number_format($student->transport_fare, 2) }}</div>
                                    </div>
                                </div>
                                @endif
                                <div class="col-md-6">
                                    <div class="info-item">
                                        <div class="info-label">
                                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">receipt_long</span>
                                            Generate Admission Fee
                                        </div>
                                        <div class="info-value">
                                            @if($student->generate_admission_fee == '1')
                                                <span class="badge bg-success badge-custom">Yes</span>
                                            @else
                                                <span class="badge bg-secondary badge-custom">No</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                @if($student->admission_fee_amount)
                                <div class="col-md-6">
                                    <div class="info-item">
                                        <div class="info-label">
                                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">attach_money</span>
                                            Admission Fee Amount
                                        </div>
                                        <div class="info-value">{{ number_format($student->admission_fee_amount, 2) }}</div>
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Parent/Guardian Information -->
            <div class="row g-4 mt-2">
                <div class="col-12">
                    <div class="card student-detail-card">
                        <div class="section-header" style="background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%);">
                            <h5>
                                <span class="material-symbols-outlined" style="font-size: 18px;">family_restroom</span>
                                Parent/Guardian Information
                            </h5>
                        </div>
                        <div class="card-body p-4">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="info-item">
                                        <div class="info-label">
                                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">person</span>
                                            Father Name
                                        </div>
                                        <div class="info-value">{{ $student->father_name ?? 'N/A' }}</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-item">
                                        <div class="info-label">
                                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">email</span>
                                            Father Email
                                        </div>
                                        <div class="info-value">{{ $student->father_email ?? 'N/A' }}</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-item">
                                        <div class="info-label">
                                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">phone</span>
                                            Father Phone
                                        </div>
                                        <div class="info-value">
                                            @if($student->father_phone)
                                                <a href="tel:{{ $student->father_phone }}" class="text-decoration-none" style="color: #003471;">
                                                    {{ $student->father_phone }}
                                                </a>
                                            @else
                                                N/A
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-item">
                                        <div class="info-label">
                                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">phone</span>
                                            Mother Phone
                                        </div>
                                        <div class="info-value">
                                            @if($student->mother_phone)
                                                <a href="tel:{{ $student->mother_phone }}" class="text-decoration-none" style="color: #003471;">
                                                    {{ $student->mother_phone }}
                                                </a>
                                            @else
                                                N/A
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-item">
                                        <div class="info-label">
                                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">chat</span>
                                            WhatsApp Number
                                        </div>
                                        <div class="info-value">
                                            @if($student->whatsapp_number)
                                                <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $student->whatsapp_number) }}" target="_blank" class="text-decoration-none" style="color: #25D366;">
                                                    {{ $student->whatsapp_number }}
                                                    <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">open_in_new</span>
                                                </a>
                                            @else
                                                N/A
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-item">
                                        <div class="info-label">
                                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">badge</span>
                                            Father ID Card
                                        </div>
                                        <div class="info-value">{{ $student->father_id_card ?? 'N/A' }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Additional Information -->
            <div class="row g-4 mt-2">
                <div class="col-12">
                    <div class="card student-detail-card">
                        <div class="section-header" style="background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);">
                            <h5>
                                <span class="material-symbols-outlined" style="font-size: 18px;">info</span>
                                Additional Information
                            </h5>
                        </div>
                        <div class="card-body p-4">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <div class="info-item">
                                        <div class="info-label">
                                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">notifications</span>
                                            Admission Notification
                                        </div>
                                        <div class="info-value">
                                            @if($student->admission_notification)
                                                <span class="badge bg-success badge-custom">Enabled</span>
                                            @else
                                                <span class="badge bg-secondary badge-custom">Disabled</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="info-item">
                                        <div class="info-label">
                                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">account_circle</span>
                                            Parent Account Created
                                        </div>
                                        <div class="info-value">
                                            @if($student->create_parent_account)
                                                <span class="badge bg-success badge-custom">Yes</span>
                                            @else
                                                <span class="badge bg-secondary badge-custom">No</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="info-item">
                                        <div class="info-label">
                                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">receipt</span>
                                            Generate Other Fee
                                        </div>
                                        <div class="info-value">
                                            @if($student->generate_other_fee == '1')
                                                <span class="badge bg-success badge-custom">Yes</span>
                                            @else
                                                <span class="badge bg-secondary badge-custom">No</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                @if($student->fee_type)
                                <div class="col-md-4">
                                    <div class="info-item">
                                        <div class="info-label">
                                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">category</span>
                                            Fee Type / Fee Head
                                        </div>
                                        <div class="info-value">{{ $student->fee_type }}</div>
                                    </div>
                                </div>
                                @endif
                                @if($student->other_fee_amount)
                                <div class="col-md-4">
                                    <div class="info-item">
                                        <div class="info-label">
                                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">attach_money</span>
                                            Other Fee Amount
                                        </div>
                                        <div class="info-value">{{ number_format($student->other_fee_amount, 2) }}</div>
                                    </div>
                                </div>
                                @endif
                                @if($student->reference_remarks)
                                <div class="col-md-12">
                                    <div class="info-item">
                                        <div class="info-label">
                                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">note</span>
                                            Reference Remarks
                                        </div>
                                        <div class="info-value">{{ $student->reference_remarks }}</div>
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
