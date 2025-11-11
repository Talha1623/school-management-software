@extends('layouts.app')

@section('title', 'Generate Salary')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-3 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0 fs-16 fw-semibold">Generate Salary</h4>
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

            <form action="{{ route('salary-loan.generate-salary.store') }}" method="POST" id="generateSalaryForm">
                @csrf
                
                <div class="row g-3">
                    <!-- Campus -->
                    <div class="col-md-6">
                        <div class="card bg-light border-0 rounded-10 p-3 mb-2">
                            <h5 class="mb-2 py-2 px-3 text-white rounded-3 fw-semibold fs-15" style="margin: -12px -12px 12px -12px; background-color: #003471;">Campus</h5>
                            
                            <label for="campus" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Campus <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm salary-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">location_on</span>
                                </span>
                                <select class="form-control salary-input" name="campus" id="campus" required style="border: none; border-left: 1px solid #e0e7ff; border-radius: 0 8px 8px 0; height: 36px;">
                                    <option value="">Select Campus</option>
                                    @foreach($campuses as $campus)
                                        <option value="{{ $campus }}">{{ $campus }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Month -->
                    <div class="col-md-6">
                        <div class="card bg-light border-0 rounded-10 p-3 mb-2">
                            <h5 class="mb-2 py-2 px-3 text-white rounded-3 fw-semibold fs-15" style="margin: -12px -12px 12px -12px; background-color: #003471;">Month</h5>
                            
                            <label for="month" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Month <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm salary-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">calendar_month</span>
                                </span>
                                <select class="form-control salary-input" name="month" id="month" required style="border: none; border-left: 1px solid #e0e7ff; border-radius: 0 8px 8px 0; height: 36px;">
                                    <option value="">Select Month</option>
                                    <option value="01" {{ $currentMonth == '01' ? 'selected' : '' }}>January</option>
                                    <option value="02" {{ $currentMonth == '02' ? 'selected' : '' }}>February</option>
                                    <option value="03" {{ $currentMonth == '03' ? 'selected' : '' }}>March</option>
                                    <option value="04" {{ $currentMonth == '04' ? 'selected' : '' }}>April</option>
                                    <option value="05" {{ $currentMonth == '05' ? 'selected' : '' }}>May</option>
                                    <option value="06" {{ $currentMonth == '06' ? 'selected' : '' }}>June</option>
                                    <option value="07" {{ $currentMonth == '07' ? 'selected' : '' }}>July</option>
                                    <option value="08" {{ $currentMonth == '08' ? 'selected' : '' }}>August</option>
                                    <option value="09" {{ $currentMonth == '09' ? 'selected' : '' }}>September</option>
                                    <option value="10" {{ $currentMonth == '10' ? 'selected' : '' }}>October</option>
                                    <option value="11" {{ $currentMonth == '11' ? 'selected' : '' }}>November</option>
                                    <option value="12" {{ $currentMonth == '12' ? 'selected' : '' }}>December</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Year -->
                    <div class="col-md-6">
                        <div class="card bg-light border-0 rounded-10 p-3 mb-2">
                            <h5 class="mb-2 py-2 px-3 text-white rounded-3 fw-semibold fs-15" style="margin: -12px -12px 12px -12px; background-color: #003471;">Year</h5>
                            
                            <label for="year" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Year <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm salary-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">calendar_today</span>
                                </span>
                                <select class="form-control salary-input" name="year" id="year" required style="border: none; border-left: 1px solid #e0e7ff; border-radius: 0 8px 8px 0; height: 36px;">
                                    <option value="">Select Year</option>
                                    @for($y = date('Y'); $y >= date('Y') - 5; $y--)
                                        <option value="{{ $y }}" {{ $currentYear == $y ? 'selected' : '' }}>{{ $y }}</option>
                                    @endfor
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Deduction Per Late Arrival -->
                    <div class="col-md-6">
                        <div class="card bg-light border-0 rounded-10 p-3 mb-2">
                            <h5 class="mb-2 py-2 px-3 text-white rounded-3 fw-semibold fs-15" style="margin: -12px -12px 12px -12px; background-color: #003471;">Deduction Per Late Arrival</h5>
                            
                            <label for="deduction_per_late_arrival" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Deduction Per Late Arrival</label>
                            <div class="input-group input-group-sm salary-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">currency_rupee</span>
                                </span>
                                <input type="number" class="form-control salary-input" name="deduction_per_late_arrival" id="deduction_per_late_arrival" placeholder="Enter deduction amount" step="0.01" min="0" style="height: 36px;">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Generate Salary Button -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-sm py-2 px-4 rounded-8" onclick="resetForm()" style="background-color: #6c757d; color: white; border: none; transition: all 0.3s ease;">
                                <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">refresh</span>
                                Reset
                            </button>
                            <button type="submit" class="btn btn-sm py-2 px-4 rounded-8 generate-salary-btn">
                                <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">payments</span>
                                Generate Salary
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    /* Salary Form Styling */
    .salary-input-group {
        height: 36px;
        border-radius: 8px;
        overflow: hidden;
        transition: all 0.3s ease;
        border: 1px solid #dee2e6;
    }
    
    .salary-input-group:focus-within {
        box-shadow: 0 0 0 3px rgba(0, 52, 113, 0.15);
        border-color: #003471;
    }
    
    .salary-input {
        height: 36px;
        font-size: 13px;
        padding: 0.5rem 0.75rem;
        border: none;
        border-left: 1px solid #e0e7ff;
        border-radius: 0 8px 8px 0;
        transition: all 0.3s ease;
    }
    
    .salary-input:focus {
        border-left-color: #003471;
        box-shadow: none;
        outline: none;
    }
    
    .salary-input-group .input-group-text {
        height: 36px;
        padding: 0 0.75rem;
        display: flex;
        align-items: center;
        border: none;
        border-right: 1px solid #e0e7ff;
        border-radius: 8px 0 0 8px;
        transition: all 0.3s ease;
    }
    
    .salary-input-group:focus-within .input-group-text {
        background-color: #003471 !important;
        color: white !important;
        border-right-color: #003471;
    }
    
    .salary-input-group select.salary-input {
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
    
    .generate-salary-btn {
        background: linear-gradient(135deg, #003471 0%, #004a9f 100%);
        color: white;
        border: none;
        font-weight: 500;
        transition: all 0.3s ease;
        box-shadow: 0 2px 8px rgba(0, 52, 113, 0.25);
    }
    
    .generate-salary-btn:hover {
        background: linear-gradient(135deg, #004a9f 0%, #003471 100%);
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0, 52, 113, 0.35);
        color: white;
    }
    
    .generate-salary-btn:active {
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
    document.getElementById('generateSalaryForm').reset();
    // Set current month and year as defaults
    const currentMonth = '{{ $currentMonth }}';
    const currentYear = '{{ $currentYear }}';
    document.getElementById('month').value = currentMonth;
    document.getElementById('year').value = currentYear;
}
</script>
@endsection
