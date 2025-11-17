@extends('layouts.app')

@section('title', 'Print Gate Passes')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-3 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0 fs-16 fw-semibold">Print Gate Passes</h4>
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

            <!-- Filter Form -->
            <div class="card border-0 shadow-sm" style="border-radius: 8px; overflow: hidden;">
                <div class="card-body p-4">
                    <form action="{{ route('parent.print-gate-passes.filter') }}" method="POST" id="gatePassForm">
                        @csrf
                        <div class="row g-3 align-items-end">
                            <!-- Campus -->
                            <div class="col-md-3">
                                <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">
                                    Campus
                                </label>
                                <select class="form-select form-select-sm" name="campus" id="campus">
                                    <option value="">All Campus</option>
                                    @foreach($campuses as $campus)
                                        <option value="{{ $campus->campus_name }}" {{ request('campus') == $campus->campus_name ? 'selected' : '' }}>{{ $campus->campus_name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Parent Type -->
                            <div class="col-md-3">
                                <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">
                                    Parent Type
                                </label>
                                <select class="form-select form-select-sm" name="parent_type" id="parent_type">
                                    <option value="">All Types</option>
                                    @foreach($parentTypes as $parentType)
                                        <option value="{{ $parentType }}" {{ request('parent_type') == $parentType ? 'selected' : '' }}>{{ $parentType }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Pass Validity -->
                            <div class="col-md-2">
                                <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">
                                    Pass Validity
                                </label>
                                <select class="form-select form-select-sm" name="pass_validity" id="pass_validity">
                                    <option value="">All</option>
                                    @foreach($passValidities as $passValidity)
                                        <option value="{{ $passValidity }}" {{ request('pass_validity') == $passValidity ? 'selected' : '' }}>{{ $passValidity }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Card Type -->
                            <div class="col-md-2">
                                <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">
                                    Card Type
                                </label>
                                <select class="form-select form-select-sm" name="card_type" id="card_type">
                                    <option value="">All Types</option>
                                    @foreach($cardTypes as $cardType)
                                        <option value="{{ $cardType }}" {{ request('card_type') == $cardType ? 'selected' : '' }}>{{ $cardType }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Filter By Data Button -->
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary w-100 filter-btn">
                                    <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">filter_list</span>
                                    <span style="font-size: 12px;">Filter By Data</span>
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
    .rounded-8 {
        border-radius: 8px;
    }
    
    /* Form Styling */
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
    
    .form-label {
        margin-bottom: 0.4rem;
        line-height: 1.3;
    }

    /* Filter Button Styling */
    .filter-btn {
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
    
    .filter-btn:hover {
        background: linear-gradient(135deg, #004a9f 0%, #003471 100%);
        transform: translateY(-1px);
        box-shadow: 0 4px 10px rgba(0, 52, 113, 0.3);
        color: white;
    }
    
    .filter-btn:active {
        transform: translateY(0);
    }

    /* Card Styling */
    .card {
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }
</style>

<script>
// Form submission - redirect to print page in new tab
document.getElementById('gatePassForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    // Get form data
    const formData = new FormData(this);
    const params = new URLSearchParams();
    
    // Add form values to params
    for (const [key, value] of formData.entries()) {
        if (value) {
            params.append(key, value);
        }
    }
    
    // Open print page in new tab
    const printUrl = '{{ route("parent.print-gate-passes.print") }}?' + params.toString();
    window.open(printUrl, '_blank');
});
</script>
@endsection
