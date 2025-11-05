@extends('layouts.app')

@section('title', 'Student Transfer')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-3 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0 fs-16 fw-semibold">Student Transfer</h4>
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

            <!-- Transfer Form -->
            <div class="card border-0 shadow-sm" style="border-radius: 8px; overflow: hidden;">
                <div class="card-body p-4">
                    <form action="#" method="POST" id="transferForm">
                        @csrf
                        <div class="row g-3 align-items-end">
                            <!-- From Campus -->
                            <div class="col-md-3">
                                <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">
                                    From Campus
                                </label>
                                <select class="form-select form-select-sm" name="from_campus" id="from_campus">
                                    <option value="">Select Campus</option>
                                    <option value="Main Campus">Main Campus</option>
                                    <option value="Branch Campus 1">Branch Campus 1</option>
                                    <option value="Branch Campus 2">Branch Campus 2</option>
                                </select>
                            </div>

                            <!-- To Campus -->
                            <div class="col-md-3">
                                <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">
                                    To Campus <span class="text-danger">*</span>
                                </label>
                                <select class="form-select form-select-sm" name="to_campus" id="to_campus" required>
                                    <option value="">Select Campus</option>
                                    <option value="Main Campus">Main Campus</option>
                                    <option value="Branch Campus 1">Branch Campus 1</option>
                                    <option value="Branch Campus 2">Branch Campus 2</option>
                                </select>
                            </div>

                            <!-- Class -->
                            <div class="col-md-3">
                                <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">
                                    Class <span class="text-danger">*</span>
                                </label>
                                <select class="form-select form-select-sm" name="class" id="class" required>
                                    <option value="">Select Class</option>
                                    <option value="Nursery">Nursery</option>
                                    <option value="KG">KG</option>
                                    <option value="1st">1st</option>
                                    <option value="2nd">2nd</option>
                                    <option value="3rd">3rd</option>
                                    <option value="4th">4th</option>
                                    <option value="5th">5th</option>
                                    <option value="6th">6th</option>
                                    <option value="7th">7th</option>
                                    <option value="8th">8th</option>
                                    <option value="9th">9th</option>
                                    <option value="10th">10th</option>
                                    <option value="11th">11th</option>
                                    <option value="12th">12th</option>
                                </select>
                            </div>

                            <!-- Student Code -->
                            <div class="col-md-3">
                                <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">
                                    Student Code <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control form-control-sm" name="student_code" id="student_code" placeholder="Enter Student Code" required>
                            </div>
                        </div>

                        <!-- Additional Options Row -->
                        <div class="row g-3 mt-2 align-items-end">
                            <!-- Also Move Dues -->
                            <div class="col-md-4">
                                <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">
                                    Also Move Dues
                                </label>
                                <select class="form-select form-select-sm" name="move_dues" id="move_dues">
                                    <option value="no">No</option>
                                    <option value="yes">Yes</option>
                                </select>
                            </div>

                            <!-- Also Move Payments -->
                            <div class="col-md-4">
                                <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">
                                    Also Move Payments
                                </label>
                                <select class="form-select form-select-sm" name="move_payments" id="move_payments">
                                    <option value="no">No</option>
                                    <option value="yes">Yes</option>
                                </select>
                            </div>

                            <!-- Notify Parent -->
                            <div class="col-md-4">
                                <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">
                                    Notify Parent
                                </label>
                                <select class="form-select form-select-sm" name="notify_parent" id="notify_parent">
                                    <option value="yes">Yes</option>
                                    <option value="no">No</option>
                                </select>
                            </div>
                        </div>

                        <!-- Transfer Button -->
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="d-flex justify-content-end">
                                    <button type="submit" class="btn btn-primary transfer-btn">
                                        <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">swap_horiz</span>
                                        <span style="font-size: 12px;">Transfer Student</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .rounded-8 {
        border-radius: 8px;
    }
    
    /* Form Styling */
    .form-select-sm,
    .form-control-sm {
        font-size: 13px;
        padding: 6px 12px;
        border-radius: 6px;
        border: 1px solid #dee2e6;
        transition: all 0.3s ease;
        height: 32px;
    }
    
    .form-select-sm:focus,
    .form-control-sm:focus {
        border-color: #003471;
        box-shadow: 0 0 0 3px rgba(0, 52, 113, 0.15);
        outline: none;
    }
    
    .form-label {
        margin-bottom: 0.4rem;
        line-height: 1.3;
    }

    /* Transfer Button Styling */
    .transfer-btn {
        background: linear-gradient(135deg, #003471 0%, #004a9f 100%);
        color: white;
        border: none;
        font-weight: 500;
        padding: 4px 12px;
        font-size: 12px;
        height: 32px;
        line-height: 1.4;
        transition: all 0.3s ease;
        box-shadow: 0 2px 6px rgba(0, 52, 113, 0.2);
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 4px;
    }
    
    .transfer-btn:hover {
        background: linear-gradient(135deg, #004a9f 0%, #003471 100%);
        transform: translateY(-1px);
        box-shadow: 0 4px 10px rgba(0, 52, 113, 0.3);
        color: white;
    }
    
    .transfer-btn:active {
        transform: translateY(0);
    }

    /* Card Styling */
    .card {
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }
</style>

<script>
// Form validation
document.getElementById('transferForm').addEventListener('submit', function(e) {
    const toCampus = document.getElementById('to_campus').value;
    const classValue = document.getElementById('class').value;
    const studentCode = document.getElementById('student_code').value;
    
    if (!toCampus || !classValue || !studentCode) {
        e.preventDefault();
        alert('Please fill in all required fields (marked with *)');
        return false;
    }
    
    if (!confirm('Are you sure you want to transfer this student? This action cannot be undone.')) {
        e.preventDefault();
        return false;
    }
});
</script>
@endsection
