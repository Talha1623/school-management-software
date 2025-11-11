@extends('layouts.app')

@section('title', 'Increment By Amount')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-3">
            <h3 class="mb-3 fw-semibold" style="color: #003471;">Increment By Amount</h3>
            
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
                
                $classes = \App\Models\ClassModel::whereNotNull('class_name')->distinct()->pluck('class_name')->sort();
                if ($classes->isEmpty()) {
                    $classes = collect(['Nursery', 'KG', '1st', '2nd', '3rd', '4th', '5th', '6th', '7th', '8th', '9th', '10th', '11th', '12th']);
                }
                
                $sections = \App\Models\Section::whereNotNull('name')->distinct()->pluck('name')->sort();
                if ($sections->isEmpty()) {
                    $sections = collect(['A', 'B', 'C', 'D', 'E']);
                }
            @endphp

            <form id="feeIncrementAmountForm" method="POST" action="{{ route('accounting.fee-increment.amount.store') }}">
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

                        <!-- Class -->
                        <div class="col-md-6">
                            <label class="form-label mb-1 fs-13 fw-medium">Class</label>
                            <div class="input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff;">
                                    <span class="material-symbols-outlined" style="font-size: 18px; color: #003471;">class</span>
                                </span>
                                <select class="form-select form-select-sm" name="class" id="class" style="height: 38px;">
                                    <option value="">Select Class</option>
                                    @foreach($classes as $className)
                                        <option value="{{ $className }}">{{ $className }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <!-- Section -->
                        <div class="col-md-6">
                            <label class="form-label mb-1 fs-13 fw-medium">Section</label>
                            <div class="input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff;">
                                    <span class="material-symbols-outlined" style="font-size: 18px; color: #003471;">group</span>
                                </span>
                                <select class="form-select form-select-sm" name="section" id="section" style="height: 38px;">
                                    <option value="">Select Section</option>
                                    @foreach($sections as $sectionName)
                                        <option value="{{ $sectionName }}">{{ $sectionName }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <!-- Amount -->
                        <div class="col-md-6">
                            <label class="form-label mb-1 fs-13 fw-medium">Amount</label>
                            <div class="input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff;">
                                    <span class="material-symbols-outlined" style="font-size: 18px; color: #003471;">payments</span>
                                </span>
                                <input type="number" step="0.01" min="0" class="form-control form-control-sm" name="amount" id="amount" placeholder="Enter Amount" required style="height: 38px;">
                            </div>
                        </div>

                        <!-- Accountant -->
                        <div class="col-md-6">
                            <label class="form-label mb-1 fs-13 fw-medium">Accountant</label>
                            <div class="input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff;">
                                    <span class="material-symbols-outlined" style="font-size: 18px; color: #003471;">person</span>
                                </span>
                                <select class="form-select form-select-sm" name="accountant" id="accountant" style="height: 38px;">
                                    <option value="">Select Accountant</option>
                                    @foreach($accountants as $accountant)
                                        <option value="{{ $accountant->name }}">{{ $accountant->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <!-- Date -->
                        <div class="col-md-6">
                            <label class="form-label mb-1 fs-13 fw-medium">Date</label>
                            <div class="input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff;">
                                    <span class="material-symbols-outlined" style="font-size: 18px; color: #003471;">calendar_today</span>
                                </span>
                                <input type="date" class="form-control form-control-sm" name="date" id="date" value="{{ date('Y-m-d') }}" required style="height: 38px;">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="text-center mt-4">
                    <button type="submit" class="btn btn-success px-5 py-2" style="background-color: #28a745; border: none; color: white;">
                        <span class="material-symbols-outlined" style="font-size: 18px; vertical-align: middle; color: white;">thumb_up</span>
                        <span style="color: white;">Submit</span>
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
