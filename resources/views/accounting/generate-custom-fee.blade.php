@extends('layouts.app')

@section('title', 'Generate Custom Fee')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-3">
            <h3 class="mb-3 fw-semibold" style="color: #003471;">Generate Custom Fee</h3>
            
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
            
            <form action="{{ route('accounting.generate-custom-fee.store') }}" method="POST" id="custom-fee-form">
                @csrf
                
                <!-- First Row: Campus, Class, Section -->
                <div class="row mb-2">
                    <div class="col-md-4">
                        <div class="card bg-light border-0 rounded-10 p-2 mb-2">
                            <h5 class="mb-1 py-2 px-3 text-white rounded-3 fw-semibold fs-15" style="margin: -8px -8px 8px -8px; background-color: #003471;">Campus</h5>
                            
                            <div class="mb-1">
                                <label for="campus" class="form-label mb-0 fs-13 fw-medium">Campus <span class="text-danger">*</span></label>
                                <select class="form-select form-select-sm py-1" id="campus" name="campus" required style="height: 32px;">
                                    <option value="">Select Campus</option>
                                    @php
                                        $campuses = \App\Models\ClassModel::whereNotNull('campus')->distinct()->pluck('campus');
                                        $campusesFromSections = \App\Models\Section::whereNotNull('campus')->distinct()->pluck('campus');
                                        $allCampuses = $campuses->merge($campusesFromSections)->unique()->sort()->values();
                                        if ($allCampuses->isEmpty()) {
                                            $allCampuses = collect(['Main Campus', 'Branch Campus 1', 'Branch Campus 2']);
                                        }
                                    @endphp
                                    @foreach($allCampuses as $campus)
                                        <option value="{{ $campus }}">{{ $campus }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card bg-light border-0 rounded-10 p-2 mb-2">
                            <h5 class="mb-1 py-2 px-3 text-white rounded-3 fw-semibold fs-15" style="margin: -8px -8px 8px -8px; background-color: #003471;">Class</h5>
                            
                            <div class="mb-1">
                                <label for="class" class="form-label mb-0 fs-13 fw-medium">Class <span class="text-danger">*</span></label>
                                <select class="form-select form-select-sm py-1" id="class" name="class" required style="height: 32px;">
                                    <option value="">Select Class</option>
                                    @php
                                        $classes = \App\Models\ClassModel::whereNotNull('class_name')->distinct()->pluck('class_name')->sort();
                                        if ($classes->isEmpty()) {
                                            $classes = collect(['Nursery', 'KG', '1st', '2nd', '3rd', '4th', '5th', '6th', '7th', '8th', '9th', '10th', '11th', '12th']);
                                        }
                                    @endphp
                                    @foreach($classes as $className)
                                        <option value="{{ $className }}">{{ $className }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card bg-light border-0 rounded-10 p-2 mb-2">
                            <h5 class="mb-1 py-2 px-3 text-white rounded-3 fw-semibold fs-15" style="margin: -8px -8px 8px -8px; background-color: #003471;">Section</h5>
                            
                            <div class="mb-1">
                                <label for="section" class="form-label mb-0 fs-13 fw-medium">Section <span class="text-danger">*</span></label>
                                <select class="form-select form-select-sm py-1" id="section" name="section" required style="height: 32px;">
                                    <option value="">Select Section</option>
                                    @php
                                        $sections = \App\Models\Section::whereNotNull('name')->distinct()->pluck('name')->sort();
                                        if ($sections->isEmpty()) {
                                            $sections = collect(['A', 'B', 'C', 'D', 'E']);
                                        }
                                    @endphp
                                    @foreach($sections as $sectionName)
                                        <option value="{{ $sectionName }}">{{ $sectionName }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Second Row: Fee Type, Amount -->
                <div class="row mb-2">
                    <div class="col-md-6">
                        <div class="card bg-light border-0 rounded-10 p-2 mb-2">
                            <h5 class="mb-1 py-2 px-3 text-white rounded-3 fw-semibold fs-15" style="margin: -8px -8px 8px -8px; background-color: #003471;">Fee Type</h5>
                            
                            <div class="mb-1">
                                <label for="fee_type" class="form-label mb-0 fs-13 fw-medium">Fee Type <span class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-sm py-1" id="fee_type" name="fee_type" placeholder="Enter fee type (e.g., Library Fee, Sports Fee, etc.)" required style="height: 32px;">
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card bg-light border-0 rounded-10 p-2 mb-2">
                            <h5 class="mb-1 py-2 px-3 text-white rounded-3 fw-semibold fs-15" style="margin: -8px -8px 8px -8px; background-color: #003471;">Amount</h5>
                            
                            <div class="mb-1">
                                <label for="amount" class="form-label mb-0 fs-13 fw-medium">Amount <span class="text-danger">*</span></label>
                                <input type="number" class="form-control form-control-sm py-1" id="amount" name="amount" placeholder="Enter amount" step="0.01" min="0" required style="height: 32px;">
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Submit Button -->
                <div class="row mb-2">
                    <div class="col-12">
                        <div class="d-flex justify-content-end gap-2">
                            <button type="reset" class="btn btn-sm btn-secondary px-4 py-2">
                                <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">refresh</span>
                                Reset
                            </button>
                            <button type="submit" class="btn btn-sm px-4 py-2" style="background: linear-gradient(135deg, #003471 0%, #004a9f 100%); color: white; border: none;">
                                <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">payments</span>
                                Generate Fee
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
@endsection
