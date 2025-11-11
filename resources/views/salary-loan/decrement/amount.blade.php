@extends('layouts.app')

@section('title', 'Decrement By Amount')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-3 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0 fs-16 fw-semibold">Decrement By Amount</h4>
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

            <form action="{{ route('salary-loan.decrement.amount.store') }}" method="POST" id="decrementAmountForm">
                @csrf
                
                <div class="row g-3">
                    <!-- Campus -->
                    <div class="col-md-6">
                        <div class="card bg-light border-0 rounded-10 p-3 mb-2">
                            <h5 class="mb-2 py-2 px-3 text-white rounded-3 fw-semibold fs-15" style="margin: -12px -12px 12px -12px; background-color: #003471;">Campus</h5>
                            
                            <label for="campus" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Campus <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm decrement-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">location_on</span>
                                </span>
                                <select class="form-control decrement-input" name="campus" id="campus" required style="border: none; border-left: 1px solid #e0e7ff; border-radius: 0 8px 8px 0; height: 36px;">
                                    <option value="">Select Campus</option>
                                    @foreach($campuses as $campus)
                                        <option value="{{ $campus }}">{{ $campus }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Decrease -->
                    <div class="col-md-6">
                        <div class="card bg-light border-0 rounded-10 p-3 mb-2">
                            <h5 class="mb-2 py-2 px-3 text-white rounded-3 fw-semibold fs-15" style="margin: -12px -12px 12px -12px; background-color: #003471;">Decrease</h5>
                            
                            <label for="decrease" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Decrease (Amount) <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm decrement-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">currency_rupee</span>
                                </span>
                                <input type="number" class="form-control decrement-input" name="decrease" id="decrease" placeholder="Enter amount" step="0.01" min="0" required style="height: 36px;">
                            </div>
                        </div>
                    </div>

                    <!-- Accountant -->
                    <div class="col-md-6">
                        <div class="card bg-light border-0 rounded-10 p-3 mb-2">
                            <h5 class="mb-2 py-2 px-3 text-white rounded-3 fw-semibold fs-15" style="margin: -12px -12px 12px -12px; background-color: #003471;">Accountant</h5>
                            
                            <label for="accountant" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Accountant <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm decrement-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">person</span>
                                </span>
                                <select class="form-control decrement-input" name="accountant" id="accountant" required style="border: none; border-left: 1px solid #e0e7ff; border-radius: 0 8px 8px 0; height: 36px;">
                                    <option value="">Select Accountant</option>
                                    @if(isset($accountants) && $accountants->count() > 0)
                                        @foreach($accountants as $accountant)
                                            <option value="{{ $accountant->name }}">{{ $accountant->name }} {{ $accountant->campus ? '(' . $accountant->campus . ')' : '' }}</option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Date -->
                    <div class="col-md-6">
                        <div class="card bg-light border-0 rounded-10 p-3 mb-2">
                            <h5 class="mb-2 py-2 px-3 text-white rounded-3 fw-semibold fs-15" style="margin: -12px -12px 12px -12px; background-color: #003471;">Date</h5>
                            
                            <label for="date" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Date <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm decrement-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">calendar_today</span>
                                </span>
                                <input type="date" class="form-control decrement-input" name="date" id="date" value="{{ $currentDate }}" required style="height: 36px;">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-sm py-2 px-4 rounded-8" onclick="resetForm()" style="background-color: #6c757d; color: white; border: none; transition: all 0.3s ease;">
                                <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">refresh</span>
                                Reset
                            </button>
                            <button type="submit" class="btn btn-sm py-2 px-4 rounded-8 decrement-submit-btn">
                                <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">save</span>
                                Save Decrement
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    /* Decrement Form Styling */
    .decrement-input-group {
        height: 36px;
        border-radius: 8px;
        overflow: hidden;
        transition: all 0.3s ease;
        border: 1px solid #dee2e6;
    }
    
    .decrement-input-group:focus-within {
        box-shadow: 0 0 0 3px rgba(0, 52, 113, 0.15);
        border-color: #003471;
    }
    
    .decrement-input {
        height: 36px;
        font-size: 13px;
        padding: 0.5rem 0.75rem;
        border: none;
        border-left: 1px solid #e0e7ff;
        border-radius: 0;
        transition: all 0.3s ease;
    }
    
    .decrement-input:focus {
        border-left-color: #003471;
        box-shadow: none;
        outline: none;
    }
    
    .decrement-input-group .input-group-text {
        height: 36px;
        padding: 0 0.75rem;
        display: flex;
        align-items: center;
        border: none;
        border-right: 1px solid #e0e7ff;
        border-radius: 8px 0 0 8px;
        transition: all 0.3s ease;
    }
    
    .decrement-input-group:focus-within .input-group-text {
        background-color: #003471 !important;
        color: white !important;
        border-right-color: #003471;
    }
    
    .decrement-input-group select.decrement-input {
        appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23003471' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 0.75rem center;
        padding-right: 2.5rem;
    }
    
    .form-label {
        margin-bottom: 0.4rem;
        line-height: 1.3;
    }
    
    .decrement-submit-btn {
        background: linear-gradient(135deg, #003471 0%, #004a9f 100%);
        color: white;
        border: none;
        font-weight: 500;
        transition: all 0.3s ease;
        box-shadow: 0 2px 8px rgba(0, 52, 113, 0.25);
    }
    
    .decrement-submit-btn:hover {
        background: linear-gradient(135deg, #004a9f 0%, #003471 100%);
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0, 52, 113, 0.35);
        color: white;
    }
    
    .decrement-submit-btn:active {
        transform: translateY(0);
    }
    
    .rounded-8 {
        border-radius: 8px;
    }
    
    .rounded-10 {
        border-radius: 10px;
    }
</style>

<script>
// Reset form
function resetForm() {
    document.getElementById('decrementAmountForm').reset();
    // Set current date as default
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('date').value = today;
}
</script>
@endsection
