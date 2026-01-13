@extends('layouts.accountant')

@section('title', 'Custom Payment - Accountant')

@php
    use Illuminate\Support\Facades\Auth;
@endphp

@section('content')
<div class="row">
    <div class="col-12">
        <div class="d-flex align-items-center mb-4">
            <h2 class="mb-0 fs-20 fw-semibold text-dark me-2">Custom Payment</h2>
            <span class="material-symbols-outlined text-secondary" style="font-size: 20px;">receipt</span>
        </div>

        <div class="card bg-white border border-white rounded-10 p-3">
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
            
            <form action="{{ route('accountant.direct-payment.custom.store') }}" method="POST" id="customPaymentForm">
                @csrf
                
                <!-- First Row: Campus, Payment Title -->
                <div class="row mb-2">
                    <div class="col-md-6">
                        <div class="card bg-light border-0 rounded-10 p-2 mb-2">
                            <h5 class="mb-1 py-2 px-3 text-white rounded-3 fw-semibold fs-15" style="margin: -8px -8px 8px -8px; background-color: #003471;">Campus</h5>
                            
                            <div class="mb-1">
                                <label for="campus" class="form-label mb-0 fs-13 fw-medium">Campus</label>
                                <select class="form-select form-select-sm py-1" id="campus" name="campus" style="height: 32px;">
                                    <option value="">Select Campus</option>
                                    @foreach($campuses as $campus)
                                        <option value="{{ $campus->campus_name ?? $campus }}">{{ $campus->campus_name ?? $campus }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card bg-light border-0 rounded-10 p-2 mb-2">
                            <h5 class="mb-1 py-2 px-3 text-white rounded-3 fw-semibold fs-15" style="margin: -8px -8px 8px -8px; background-color: #003471;">Payment Title</h5>
                            
                            <div class="mb-1">
                                <label for="payment_title" class="form-label mb-0 fs-13 fw-medium">Payment Title <span class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-sm py-1" id="payment_title" name="payment_title" required style="height: 32px;" placeholder="e.g., Office Supplies, Maintenance">
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Second Row: Payment Amount, Accountant -->
                <div class="row mb-2">
                    <div class="col-md-6">
                        <div class="card bg-light border-0 rounded-10 p-2 mb-2">
                            <h5 class="mb-1 py-2 px-3 text-white rounded-3 fw-semibold fs-15" style="margin: -8px -8px 8px -8px; background-color: #003471;">Payment Amount</h5>
                            
                            <div class="mb-1">
                                <label for="payment_amount" class="form-label mb-0 fs-13 fw-medium">Payment Amount <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" class="form-control form-control-sm py-1" id="payment_amount" name="payment_amount" required style="height: 32px;" placeholder="0.00" min="0">
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card bg-light border-0 rounded-10 p-2 mb-2">
                            <h5 class="mb-1 py-2 px-3 text-white rounded-3 fw-semibold fs-15" style="margin: -8px -8px 8px -8px; background-color: #003471;">Accountant</h5>
                            
                            <div class="mb-1">
                                <label for="accountant" class="form-label mb-0 fs-13 fw-medium">Accountant</label>
                                <input type="text" class="form-control form-control-sm py-1" id="accountant" name="accountant" value="{{ Auth::guard('accountant')->check() ? (Auth::guard('accountant')->user()->name ?? '') : '' }}" readonly style="height: 32px; background-color: #f8f9fa; cursor: not-allowed;">
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Third Row: Method -->
                <div class="row mb-2">
                    <div class="col-md-6">
                        <div class="card bg-light border-0 rounded-10 p-2 mb-2">
                            <h5 class="mb-1 py-2 px-3 text-white rounded-3 fw-semibold fs-15" style="margin: -8px -8px 8px -8px; background-color: #003471;">Method</h5>
                            
                            <div class="mb-1">
                                <label for="method" class="form-label mb-0 fs-13 fw-medium">Method <span class="text-danger">*</span></label>
                                <select class="form-select form-select-sm py-1" id="method" name="method" required style="height: 32px;">
                                    <option value="">Select Payment Method</option>
                                    @foreach($methods as $method)
                                        <option value="{{ $method }}">{{ $method }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Hidden Payment Date -->
                <input type="hidden" name="payment_date" value="{{ date('Y-m-d') }}">
                
                <!-- Submit Button -->
                <div class="row mb-2">
                    <div class="col-12">
                        <div class="d-flex justify-content-end gap-2">
                            <button type="reset" class="btn btn-sm btn-secondary px-4 py-2" onclick="resetForm()">
                                <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">refresh</span>
                                Reset
                            </button>
                            <button type="submit" class="btn btn-sm px-4 py-2" style="background: linear-gradient(135deg, #003471 0%, #004a9f 100%); color: white; border: none;">
                                <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">payments</span>
                                Take Payment
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    .card {
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        transition: all 0.3s ease;
    }
    
    .card:hover {
        box-shadow: 0 4px 8px rgba(0,0,0,0.15);
    }
    
    .form-control:focus,
    .form-select:focus {
        border-color: #003471;
        box-shadow: 0 0 0 0.2rem rgba(0, 52, 113, 0.25);
    }
    
    .form-label {
        color: #495057;
    }
</style>

<script>
function resetForm() {
    document.getElementById('customPaymentForm').reset();
}
</script>
@endsection
