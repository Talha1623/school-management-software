@extends('layouts.app')

@php
use Illuminate\Support\Facades\Storage;
@endphp

@section('title', 'Job Inquiry Details')

@section('content')
<style>
    .inquiry-detail-card {
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        transition: all 0.3s ease;
        border: none;
    }
    
    .inquiry-detail-card:hover {
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
                    <h4 class="mb-0 fs-18 fw-bold" style="color: #003471;">Job Inquiry Details</h4>
                    <p class="text-muted mb-0" style="font-size: 13px; margin-top: 4px;">
                        <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">work</span>
                        Complete job inquiry information
                    </p>
                </div>
                <a href="{{ route('staff.job-inquiry') }}" class="back-btn">
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
                <!-- Personal Information -->
                <div class="col-12">
                    <div class="card inquiry-detail-card mb-3">
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
                                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">person</span>
                                            Name
                                        </div>
                                        <div class="info-value">
                                            <strong style="color: #003471;">{{ $job_inquiry->name }}</strong>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-item">
                                        <div class="info-label">
                                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">family_restroom</span>
                                            Father/Husband Name
                                        </div>
                                        <div class="info-value">{{ $job_inquiry->father_husband_name ?? 'N/A' }}</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-item">
                                        <div class="info-label">
                                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">transgender</span>
                                            Gender
                                        </div>
                                        <div class="info-value">
                                            @if($job_inquiry->gender)
                                                <span class="badge {{ $job_inquiry->gender == 'Male' ? 'bg-primary' : ($job_inquiry->gender == 'Female' ? 'bg-danger' : 'bg-secondary') }} text-white badge-custom">
                                                    {{ $job_inquiry->gender }}
                                                </span>
                                            @else
                                                N/A
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-item">
                                        <div class="info-label">
                                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">calendar_today</span>
                                            Birthday
                                        </div>
                                        <div class="info-value">
                                            {{ $job_inquiry->birthday ? \Carbon\Carbon::parse($job_inquiry->birthday)->format('d M Y') : 'N/A' }}
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-item">
                                        <div class="info-label">
                                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">favorite</span>
                                            Marital Status
                                        </div>
                                        <div class="info-value">{{ $job_inquiry->marital_status ?? 'N/A' }}</div>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="info-item">
                                        <div class="info-label">
                                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">home</span>
                                            Home Address
                                        </div>
                                        <div class="info-value">{{ $job_inquiry->home_address ?? 'N/A' }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Professional Information -->
                <div class="col-12">
                    <div class="card inquiry-detail-card mb-3">
                        <div class="section-header" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%);">
                            <h5>
                                <span class="material-symbols-outlined" style="font-size: 18px;">work</span>
                                Professional Information
                            </h5>
                        </div>
                        <div class="card-body p-4">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="info-item">
                                        <div class="info-label">
                                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">business</span>
                                            Campus
                                        </div>
                                        <div class="info-value">
                                            <span class="badge bg-primary text-white badge-custom">{{ $job_inquiry->campus ?? 'N/A' }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-item">
                                        <div class="info-label">
                                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">work</span>
                                            Applied For Designation
                                        </div>
                                        <div class="info-value">
                                            <span class="badge bg-info text-white badge-custom">{{ $job_inquiry->applied_for_designation ?? 'N/A' }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-item">
                                        <div class="info-label">
                                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">school</span>
                                            Qualification
                                        </div>
                                        <div class="info-value">{{ $job_inquiry->qualification ?? 'N/A' }}</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-item">
                                        <div class="info-label">
                                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">payments</span>
                                            Salary Type
                                        </div>
                                        <div class="info-value">{{ $job_inquiry->salary_type ?? 'N/A' }}</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-item">
                                        <div class="info-label">
                                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">attach_money</span>
                                            Salary Demand
                                        </div>
                                        <div class="info-value">
                                            <strong style="color: #28a745;">Rs. {{ number_format($job_inquiry->salary_demand ?? 0, 2) }}</strong>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Contact Information -->
                <div class="col-12">
                    <div class="card inquiry-detail-card">
                        <div class="section-header" style="background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%);">
                            <h5>
                                <span class="material-symbols-outlined" style="font-size: 18px;">contact_mail</span>
                                Contact Information
                            </h5>
                        </div>
                        <div class="card-body p-4">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="info-item">
                                        <div class="info-label">
                                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">email</span>
                                            Email
                                        </div>
                                        <div class="info-value">
                                            @if($job_inquiry->email)
                                                <a href="mailto:{{ $job_inquiry->email }}" class="text-decoration-none" style="color: #003471;">
                                                    {{ $job_inquiry->email }}
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
                                            Phone
                                        </div>
                                        <div class="info-value">
                                            @if($job_inquiry->phone)
                                                <a href="tel:{{ $job_inquiry->phone }}" class="text-decoration-none" style="color: #003471;">
                                                    {{ $job_inquiry->phone }}
                                                </a>
                                            @else
                                                N/A
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Documents -->
                @if($job_inquiry->cv_resume)
                <div class="col-12">
                    <div class="card inquiry-detail-card">
                        <div class="section-header" style="background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);">
                            <h5>
                                <span class="material-symbols-outlined" style="font-size: 18px;">description</span>
                                Documents
                            </h5>
                        </div>
                        <div class="card-body p-4">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="info-item">
                                        <div class="info-label">
                                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">description</span>
                                            CV/Resume
                                        </div>
                                        <div class="info-value">
                                            <a href="{{ Storage::url($job_inquiry->cv_resume) }}" target="_blank" class="btn btn-sm btn-primary">
                                                <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">download</span>
                                                Download CV/Resume
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

