@extends('layouts.app')

@section('title', 'Student Attendance')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-3 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0 fs-16 fw-semibold">Student Attendance</h4>
            </div>

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

            <!-- Attendance Form -->
            <div class="card border-0 shadow-sm mb-3" style="border-radius: 8px; overflow: hidden;">
                <div class="card-body p-3">
                    <form action="#" method="POST" id="attendanceForm">
                        @csrf
                        <div class="row g-3 align-items-end">
                            <!-- Type Field -->
                            <div class="col-md-3">
                                <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">
                                    Type
                                </label>
                                <select class="form-select form-select-sm" name="type" id="type">
                                    <option value="">Select Type</option>
                                    <option value="Daily">Daily</option>
                                    <option value="Weekly">Weekly</option>
                                    <option value="Monthly">Monthly</option>
                                    <option value="Custom">Custom</option>
                                </select>
                            </div>

                            <!-- Class Field -->
                            <div class="col-md-3">
                                <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">
                                    Class
                                </label>
                                <select class="form-select form-select-sm" name="class" id="class">
                                    <option value="">Select Class</option>
                                    @php
                                        $classes = \App\Models\ClassModel::distinct()->pluck('class_name')->sort()->values();
                                        if ($classes->isEmpty()) {
                                            $classes = collect(['Nursery', 'KG', '1st', '2nd', '3rd', '4th', '5th', '6th', '7th', '8th', '9th', '10th', '11th', '12th']);
                                        }
                                    @endphp
                                    @foreach($classes as $class)
                                        <option value="{{ $class }}">{{ $class }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Section Field -->
                            <div class="col-md-3">
                                <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">
                                    Section
                                </label>
                                <select class="form-select form-select-sm" name="section" id="section">
                                    <option value="">Select Section</option>
                                    @php
                                        $sections = \App\Models\Section::whereNotNull('name')->distinct()->pluck('name')->sort()->values();
                                    @endphp
                                    @foreach($sections as $section)
                                        <option value="{{ $section }}">{{ $section }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Manage Attendance Button -->
                            <div class="col-md-3">
                                <button type="submit" class="btn btn-sm w-100 filter-btn">
                                    <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">manage_accounts</span>
                                    <span>Manage Attendance</span>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    /* Filter Form Styling */
    .form-select-sm {
        font-size: 13px;
        padding: 6px 12px;
        border-radius: 6px;
        border: 1px solid #dee2e6;
        transition: all 0.3s ease;
        height: 32px;
    }
    
    .form-select-sm:focus {
        border-color: #003471;
        box-shadow: 0 0 0 3px rgba(0, 52, 113, 0.15);
        outline: none;
    }
    
    .filter-btn {
        background: linear-gradient(135deg, #003471 0%, #004a9f 100%);
        color: white;
        border: none;
        font-weight: 500;
        transition: all 0.3s ease;
        box-shadow: 0 2px 6px rgba(0, 52, 113, 0.2);
        height: 32px;
    }
    
    .filter-btn:hover {
        background: linear-gradient(135deg, #004a9f 0%, #003471 100%);
        transform: translateY(-1px);
        box-shadow: 0 4px 10px rgba(0, 52, 113, 0.3);
        color: white;
    }
    
    .filter-btn:active {
        transform: translateY(0);
    }
    
    .filter-btn .material-symbols-outlined {
        color: white !important;
    }
    
    .rounded-8 {
        border-radius: 8px;
    }
</style>

<script>
// Handle form submission
document.getElementById('attendanceForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const type = document.getElementById('type').value;
    const classValue = document.getElementById('class').value;
    const section = document.getElementById('section').value;
    
    if (!type || !classValue || !section) {
        alert('Please fill all required fields');
        return;
    }
    
    // Here you can add your attendance management logic
    // For now, just show an alert
    alert('Manage Attendance for:\nType: ' + type + '\nClass: ' + classValue + '\nSection: ' + section);
    
    // You can redirect or perform other actions here
    // window.location.href = '/attendance/manage?type=' + type + '&class=' + classValue + '&section=' + section;
});
</script>
@endsection
