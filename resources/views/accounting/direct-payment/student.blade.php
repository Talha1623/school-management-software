@extends('layouts.app')

@section('title', 'Student Payment')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-3">
            <h3 class="mb-3 fw-semibold" style="color: #003471;">Student Payment</h3>
            
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
            
            @if($errors->any())
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @php
                $campuses = \App\Models\ClassModel::whereNotNull('campus')->distinct()->pluck('campus');
                $campusesFromSections = \App\Models\Section::whereNotNull('campus')->distinct()->pluck('campus');
                $allCampuses = $campuses->merge($campusesFromSections)->unique()->sort()->values();
                if ($allCampuses->isEmpty()) {
                    $allCampuses = collect(['Main Campus', 'Branch Campus 1', 'Branch Campus 2']);
                }
            @endphp

            <form id="studentPaymentForm" method="POST" action="{{ route('accounting.direct-payment.student.store') }}">
                @csrf
                
                <div class="payment-row mb-3 p-3 border rounded" style="background-color: #f8f9fa;">
                    <div class="row g-3">
                        <!-- Campus -->
                        <div class="col-md-6">
                            <label class="form-label mb-1 fs-13 fw-medium">Campus</label>
                            <div class="input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff;">
                                    <span class="material-symbols-outlined" style="font-size: 18px; color: #003471;">home</span>
                                </span>
                                <select class="form-select form-select-sm" name="campus" id="campus" style="height: 38px;">
                                    <option value="">Select Campus</option>
                                    @foreach($allCampuses as $campus)
                                        <option value="{{ $campus }}">{{ $campus }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <!-- Student Code -->
                        <div class="col-md-6">
                            <label class="form-label mb-1 fs-13 fw-medium">Student Code</label>
                            <div class="input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff;">
                                    <span class="material-symbols-outlined" style="font-size: 18px; color: #003471;">school</span>
                                </span>
                                <input type="text" class="form-control form-control-sm" name="student_code" id="student_code" placeholder="Student Roll/Code" required style="height: 38px;">
                            </div>
                        </div>

                        <!-- Payment Title -->
                        <div class="col-md-6">
                            <label class="form-label mb-1 fs-13 fw-medium">Payment Title</label>
                            <div class="input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff;">
                                    <span class="material-symbols-outlined" style="font-size: 18px; color: #003471;">receipt</span>
                                </span>
                                <input type="text" class="form-control form-control-sm" name="payment_title" id="payment_title" placeholder="Enter Payment Title" required style="height: 38px;">
                            </div>
                        </div>

                        <!-- Payment Amount -->
                        <div class="col-md-6">
                            <label class="form-label mb-1 fs-13 fw-medium">Payment Amount</label>
                            <div class="input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff;">
                                    <span class="material-symbols-outlined" style="font-size: 18px; color: #003471;">payments</span>
                                </span>
                                <input type="number" step="0.01" min="0" class="form-control form-control-sm" name="payment_amount" id="payment_amount" placeholder="Enter Payment Amount" required style="height: 38px;">
                            </div>
                        </div>

                        <!-- Discount -->
                        <div class="col-md-6">
                            <label class="form-label mb-1 fs-13 fw-medium">Discount</label>
                            <div class="input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff;">
                                    <span class="material-symbols-outlined" style="font-size: 18px; color: #003471;">remove</span>
                                </span>
                                <input type="number" step="0.01" min="0" class="form-control form-control-sm" name="discount" id="discount" placeholder="0" value="0" style="height: 38px;">
                            </div>
                        </div>

                        <!-- Method -->
                        <div class="col-md-6">
                            <label class="form-label mb-1 fs-13 fw-medium">Method</label>
                            <div class="input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff;">
                                    <span class="material-symbols-outlined" style="font-size: 18px; color: #003471;">account_balance_wallet</span>
                                </span>
                                <select class="form-select form-select-sm" name="method" id="method" required style="height: 38px;">
                                    <option value="">Select Method</option>
                                    <option value="Cash Payment" selected>Cash Payment</option>
                                    <option value="Bank Transfer">Bank Transfer</option>
                                    <option value="Cheque">Cheque</option>
                                    <option value="Online Payment">Online Payment</option>
                                    <option value="Card Payment">Card Payment</option>
                                </select>
                            </div>
                        </div>

                        <!-- Payment Date -->
                        <div class="col-md-6">
                            <label class="form-label mb-1 fs-13 fw-medium">Payment Date</label>
                            <div class="input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff;">
                                    <span class="material-symbols-outlined" style="font-size: 18px; color: #003471;">calendar_today</span>
                                </span>
                                <input type="date" class="form-control form-control-sm" name="payment_date" id="payment_date" value="{{ date('Y-m-d') }}" required style="height: 38px;">
                            </div>
                        </div>

                        <!-- SMS Notification -->
                        <div class="col-md-6">
                            <label class="form-label mb-1 fs-13 fw-medium">SMS Notification</label>
                            <div class="input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff;">
                                    <span class="material-symbols-outlined" style="font-size: 18px; color: #003471;">sms</span>
                                </span>
                                <select class="form-select form-select-sm" name="sms_notification" id="sms_notification" required style="height: 38px;">
                                    <option value="Yes" selected>Yes</option>
                                    <option value="No">No</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="text-center mt-4">
                    <button type="submit" class="btn btn-success px-5 py-2" style="background-color: #28a745; border: none; color: white;">
                        <span class="material-symbols-outlined" style="font-size: 18px; vertical-align: middle; color: white;">thumb_up</span>
                        <span style="color: white;">Take Payment</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    .payment-row {
        border: 1px solid #dee2e6 !important;
        transition: all 0.3s ease;
    }
    
    .payment-row:hover {
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }
    
    .input-group-text {
        border-right: none;
    }
    
    .form-select,
    .form-control {
        border-left: none;
    }
    
    .form-select:focus,
    .form-control:focus {
        border-color: #003471;
        box-shadow: 0 0 0 0.2rem rgba(0, 52, 113, 0.25);
    }
    
    .input-group:focus-within .input-group-text {
        background-color: #003471 !important;
        color: white !important;
        border-color: #003471;
    }
    
    .input-group:focus-within .material-symbols-outlined {
        color: white !important;
    }
    
    .btn-success {
        color: white !important;
        font-weight: 500;
        transition: all 0.3s ease;
    }
    
    .btn-success .material-symbols-outlined {
        color: white !important;
    }
    
    .btn-success:hover {
        background-color: #218838 !important;
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(40, 167, 69, 0.3);
    }
</style>

<script>
// Form is now connected to database, no need for preventDefault
// Form will submit normally to the backend
</script>
@endsection
