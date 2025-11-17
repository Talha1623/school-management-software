@extends('layouts.app')

@php
use Illuminate\Support\Facades\Storage;
@endphp

@section('title', 'Staff Details')

@section('content')
<style>
    .staff-detail-card {
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        transition: all 0.3s ease;
        border: none;
    }
    
    .staff-detail-card:hover {
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
        height: 300px;
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
                    <h4 class="mb-0 fs-18 fw-bold" style="color: #003471;">Staff Details</h4>
                    <p class="text-muted mb-0" style="font-size: 13px; margin-top: 4px;">
                        <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">person</span>
                        Complete staff information
                    </p>
                </div>
                <a href="{{ route('staff.management') }}" class="back-btn">
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
                <!-- Staff Photo Card -->
                <div class="col-lg-4 col-md-5">
                    <div class="card staff-detail-card h-100">
                        <div class="photo-container">
                            @if($staff->photo)
                                <img src="{{ Storage::url($staff->photo) }}" alt="Staff Photo" class="img-fluid w-100" style="height: 300px; object-fit: cover; display: block;">
                            @else
                                <div class="photo-placeholder">
                                    <span class="material-symbols-outlined" style="font-size: 80px; color: rgba(255, 255, 255, 0.7);">person</span>
                                </div>
                            @endif
                        </div>
                        <div class="card-body text-center p-4">
                            <h4 class="mb-1 fw-bold" style="color: #003471;">{{ $staff->name }}</h4>
                            @if($staff->father_husband_name)
                                <p class="text-muted mb-3" style="font-size: 14px;">({{ $staff->father_husband_name }})</p>
                            @endif
                            @if($staff->emp_id)
                                <span class="badge bg-info text-white badge-custom mb-2">
                                    <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">badge</span>
                                    {{ $staff->emp_id }}
                                </span>
                            @endif
                            @if($staff->designation)
                                <p class="text-muted mb-0" style="font-size: 12px;">
                                    <strong>Designation:</strong> {{ $staff->designation }}
                                </p>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Staff Information Cards -->
                <div class="col-lg-8 col-md-7">
                    <!-- Personal Information -->
                    <div class="card staff-detail-card mb-3">
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
                                            @if($staff->gender)
                                                <span class="badge {{ $staff->gender == 'Male' ? 'bg-primary' : ($staff->gender == 'Female' ? 'bg-danger' : 'bg-secondary') }} text-white badge-custom">
                                                    {{ $staff->gender }}
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
                                            {{ $staff->birthday ? \Carbon\Carbon::parse($staff->birthday)->format('d M Y') : 'N/A' }}
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-item">
                                        <div class="info-label">
                                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">favorite</span>
                                            Marital Status
                                        </div>
                                        <div class="info-value">{{ $staff->marital_status ?? 'N/A' }}</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-item">
                                        <div class="info-label">
                                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">credit_card</span>
                                            CNIC
                                        </div>
                                        <div class="info-value">{{ $staff->cnic ?? 'N/A' }}</div>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="info-item">
                                        <div class="info-label">
                                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">home</span>
                                            Home Address
                                        </div>
                                        <div class="info-value">{{ $staff->home_address ?? 'N/A' }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Professional Information -->
                    <div class="card staff-detail-card mb-3">
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
                                            <span class="badge bg-primary text-white badge-custom">{{ $staff->campus ?? 'N/A' }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-item">
                                        <div class="info-label">
                                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">work</span>
                                            Designation
                                        </div>
                                        <div class="info-value">
                                            <span class="badge bg-secondary text-white badge-custom">{{ $staff->designation ?? 'N/A' }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-item">
                                        <div class="info-label">
                                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">event</span>
                                            Joining Date
                                        </div>
                                        <div class="info-value">
                                            {{ $staff->joining_date ? \Carbon\Carbon::parse($staff->joining_date)->format('d M Y') : 'N/A' }}
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-item">
                                        <div class="info-label">
                                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">school</span>
                                            Qualification
                                        </div>
                                        <div class="info-value">{{ $staff->qualification ?? 'N/A' }}</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-item">
                                        <div class="info-label">
                                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">payments</span>
                                            Salary Type
                                        </div>
                                        <div class="info-value">{{ $staff->salary_type ?? 'N/A' }}</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-item">
                                        <div class="info-label">
                                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">attach_money</span>
                                            Salary
                                        </div>
                                        <div class="info-value">
                                            <strong style="color: #28a745;">Rs. {{ number_format($staff->salary ?? 0, 2) }}</strong>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contact Information -->
            <div class="row g-4 mt-2">
                <div class="col-12">
                    <div class="card staff-detail-card">
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
                                            @if($staff->email)
                                                <a href="mailto:{{ $staff->email }}" class="text-decoration-none" style="color: #003471;">
                                                    {{ $staff->email }}
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
                                            @if($staff->phone)
                                                <a href="tel:{{ $staff->phone }}" class="text-decoration-none" style="color: #003471;">
                                                    {{ $staff->phone }}
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
                                            WhatsApp
                                        </div>
                                        <div class="info-value">
                                            @if($staff->whatsapp)
                                                <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $staff->whatsapp) }}" target="_blank" class="text-decoration-none" style="color: #25D366;">
                                                    {{ $staff->whatsapp }}
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
                                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">family_restroom</span>
                                            Father/Husband Name
                                        </div>
                                        <div class="info-value">{{ $staff->father_husband_name ?? 'N/A' }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Documents -->
            @if($staff->cv_resume)
            <div class="row g-4 mt-2">
                <div class="col-12">
                    <div class="card staff-detail-card">
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
                                            <a href="{{ Storage::url($staff->cv_resume) }}" target="_blank" class="btn btn-sm btn-primary">
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
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

