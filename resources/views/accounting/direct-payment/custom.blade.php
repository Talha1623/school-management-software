@extends('layouts.app')

@section('title', 'Custom Payment')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-3">
            <h3 class="mb-3 fw-semibold" style="color: #003471;">Custom Payment</h3>
            
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

            <form id="customPaymentForm" method="POST" action="{{ route('accounting.direct-payment.custom.store') }}" class="compact-form">
                @csrf
                
                <div class="payment-row mb-2 p-2 border rounded" style="background-color: #f8f9fa;">
                    <div class="row g-2">
                        <!-- Campus -->
                        <div class="col-md-6">
                            <label class="form-label mb-1 fs-13 fw-medium">Campus</label>
                            <div class="input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff;">
                                    <span class="material-symbols-outlined" style="font-size: 18px; color: #003471;">home</span>
                                </span>
                                <select class="form-select form-select-sm" name="campus" id="campus" style="height: 32px;">
                                    <option value="">Select Campus</option>
                                    @foreach($campuses as $campus)
                                        <option value="{{ $campus }}">{{ $campus }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <!-- Payment Title -->
                        <div class="col-md-6">
                            <label class="form-label mb-1 fs-13 fw-medium">Payment Title</label>
                            <div class="input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff;">
                                    <span class="material-symbols-outlined" style="font-size: 18px; color: #003471;">receipt</span>
                                </span>
                                <input type="text" class="form-control form-control-sm" name="payment_title" id="payment_title" placeholder="Enter Payment Title" required style="height: 32px;">
                            </div>
                        </div>

                        <!-- Payment Amount -->
                        <div class="col-md-6">
                            <label class="form-label mb-1 fs-13 fw-medium">Payment Amount</label>
                            <div class="input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff;">
                                    <span class="material-symbols-outlined" style="font-size: 18px; color: #003471;">payments</span>
                                </span>
                                <input type="number" step="0.01" min="0" class="form-control form-control-sm" name="payment_amount" id="payment_amount" placeholder="Enter Payment Amount" required style="height: 32px;">
                            </div>
                        </div>

                        <!-- Accountant -->
                        <div class="col-md-6">
                            <label class="form-label mb-1 fs-13 fw-medium">Accountant</label>
                            <div class="input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff;">
                                    <span class="material-symbols-outlined" style="font-size: 18px; color: #003471;">person</span>
                                </span>
                                <select class="form-select form-select-sm" name="accountant" id="accountant" style="height: 32px;">
                                    <option value="">Select Accountant</option>
                                </select>
                            </div>
                        </div>

                        <!-- Method -->
                        <div class="col-md-6">
                            <label class="form-label mb-1 fs-13 fw-medium">Method</label>
                            <div class="input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff;">
                                    <span class="material-symbols-outlined" style="font-size: 18px; color: #003471;">account_balance_wallet</span>
                                </span>
                                <select class="form-select form-select-sm" name="method" id="method" required style="height: 32px;">
                                    <option value="">Select Method</option>
                                    @foreach($methods as $method)
                                        <option value="{{ $method }}" {{ $method === 'Cash Payment' ? 'selected' : '' }}>{{ $method }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <!-- Notify Admin -->
                        <div class="col-md-6">
                            <label class="form-label mb-1 fs-13 fw-medium">Notify Admin</label>
                            <div class="input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff;">
                                    <span class="material-symbols-outlined" style="font-size: 18px; color: #003471;">notifications</span>
                                </span>
                                <select class="form-select form-select-sm" name="notify_admin" id="notify_admin" required style="height: 32px;">
                                    <option value="Yes" selected>Yes</option>
                                    <option value="No">No</option>
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
                                <input type="date" class="form-control form-control-sm" name="payment_date" id="payment_date" value="{{ date('Y-m-d') }}" required style="height: 32px;">
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

    .compact-form .form-label {
        margin-bottom: 2px;
    }

    .compact-form .input-group-text {
        padding: 0 8px;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const campusSelect = document.getElementById('campus');
    const accountantSelect = document.getElementById('accountant');

    function resetAccountants() {
        accountantSelect.innerHTML = '<option value="">Select Accountant</option>';
    }

    function loadAccountants() {
        resetAccountants();
        const params = new URLSearchParams();
        if (campusSelect.value) {
            params.append('campus', campusSelect.value);
        }

        fetch(`{{ route('accounting.direct-payment.custom.get-accountants') }}?${params.toString()}`)
            .then(response => response.json())
            .then(accountants => {
                accountants.forEach(accountant => {
                    const option = document.createElement('option');
                    option.value = accountant.name;
                    option.textContent = accountant.name;
                    accountantSelect.appendChild(option);
                });
            })
            .catch(() => {});
    }

    if (campusSelect && accountantSelect) {
        campusSelect.addEventListener('change', loadAccountants);
        loadAccountants();
    }
});
</script>
@endsection
