@extends('layouts.app')

@section('title', 'Staff Attendance')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-3 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0 fs-16 fw-semibold">Staff Attendance</h4>
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
                            <!-- Campus Field -->
                            <div class="col-md-3">
                                <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">
                                    Campus
                                </label>
                                <select class="form-select form-select-sm" name="campus" id="campus">
                                    <option value="">Select Campus</option>
                                    @php
                                        $campuses = \App\Models\Staff::whereNotNull('campus')->distinct()->pluck('campus')->sort()->values();
                                        $campusesFromClasses = \App\Models\ClassModel::whereNotNull('campus')->distinct()->pluck('campus');
                                        $campusesFromSections = \App\Models\Section::whereNotNull('campus')->distinct()->pluck('campus');
                                        $allCampuses = $campuses->merge($campusesFromClasses)->merge($campusesFromSections)->unique()->sort()->values();
                                        if ($allCampuses->isEmpty()) {
                                            $allCampuses = collect(['Main Campus', 'Branch Campus 1', 'Branch Campus 2']);
                                        }
                                    @endphp
                                    @foreach($allCampuses as $campus)
                                        <option value="{{ $campus }}">{{ $campus }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Staff Category Field -->
                            <div class="col-md-3">
                                <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">
                                    Staff Category
                                </label>
                                <select class="form-select form-select-sm" name="staff_category" id="staff_category">
                                    <option value="">Select Staff Category</option>
                                    @php
                                        $categories = \App\Models\Staff::whereNotNull('designation')->distinct()->pluck('designation')->sort()->values();
                                        if ($categories->isEmpty()) {
                                            $categories = collect(['Teacher', 'Principal', 'Vice Principal', 'Admin', 'Accountant', 'Security', 'Clerk', 'Peon', 'Driver']);
                                        }
                                    @endphp
                                    @foreach($categories as $category)
                                        <option value="{{ $category }}">{{ $category }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Type Field -->
                            <div class="col-md-2">
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

                            <!-- Date Field -->
                            <div class="col-md-2">
                                <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">
                                    Date
                                </label>
                                <input type="date" class="form-select form-select-sm" name="date" id="date" value="{{ date('Y-m-d') }}" style="height: 32px;">
                            </div>

                            <!-- Manage Attendance Button -->
                            <div class="col-md-2">
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
    
    input[type="date"].form-select-sm {
        font-size: 13px;
        padding: 6px 12px;
        border-radius: 6px;
        border: 1px solid #dee2e6;
        transition: all 0.3s ease;
        height: 32px;
    }
    
    input[type="date"].form-select-sm:focus {
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
    
    const campus = document.getElementById('campus').value;
    const staffCategory = document.getElementById('staff_category').value;
    const type = document.getElementById('type').value;
    const date = document.getElementById('date').value;
    
    // Here you can add your attendance management logic
    // For now, just show an alert
    alert('Manage Attendance for:\nCampus: ' + (campus || 'All') + '\nStaff Category: ' + (staffCategory || 'All') + '\nType: ' + (type || 'All') + '\nDate: ' + (date || 'Today'));
    
    // You can redirect or perform other actions here
    // window.location.href = '/attendance/staff/manage?campus=' + campus + '&staff_category=' + staffCategory + '&type=' + type + '&date=' + date;
});
</script>
@endsection
