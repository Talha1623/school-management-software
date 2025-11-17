@extends('layouts.app')

@section('title', 'Print Staff Card')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-3 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0 fs-16 fw-semibold">Print Staff Card</h4>
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
                    <form action="#" method="POST" id="staffCardForm">
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
                                        <option value="{{ $campus->campus_name ?? $campus }}" {{ request('campus') == ($campus->campus_name ?? $campus) ? 'selected' : '' }}>{{ $campus->campus_name ?? $campus }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Staff Type -->
                            <div class="col-md-3">
                                <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">
                                    Staff Type
                                </label>
                                <select class="form-select form-select-sm" name="staff_type" id="staff_type">
                                    <option value="">All Types</option>
                                    @foreach($staffTypes as $type)
                                        <option value="{{ $type }}" {{ request('staff_type') == $type ? 'selected' : '' }}>{{ $type }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- For Session -->
                            <div class="col-md-2">
                                <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">
                                    For Session
                                </label>
                                <select class="form-select form-select-sm" name="session" id="session">
                                    <option value="">All Sessions</option>
                                    @foreach($sessions as $session)
                                        <option value="{{ $session }}" {{ request('session') == $session ? 'selected' : '' }}>{{ $session }}</option>
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

                            <!-- Filter Button -->
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary w-100 filter-btn">
                                    <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">filter_list</span>
                                    <span style="font-size: 12px;">Filter</span>
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
// Form submission - open print view in new tab
document.getElementById('staffCardForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const params = new URLSearchParams();
    
    // Add form values to params
    for (const [key, value] of formData.entries()) {
        if (value) {
            params.append(key, value);
        }
    }
    
    // Open print view in new tab
    const printUrl = `{{ route('id-card.print-staff.print') }}?${params.toString()}`;
    window.open(printUrl, '_blank');
});
</script>
@endsection

