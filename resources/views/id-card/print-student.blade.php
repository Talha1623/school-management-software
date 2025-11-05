@extends('layouts.app')

@section('title', 'Print Student Card')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-3 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0 fs-16 fw-semibold">Print Student Card</h4>
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
                    <form action="#" method="POST" id="studentCardForm">
                        @csrf
                        <div class="row g-3 align-items-end">
                            <!-- Type -->
                            <div class="col-md-3">
                                <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">
                                    Type
                                </label>
                                <select class="form-select form-select-sm" name="type" id="type">
                                    <option value="">All Types</option>
                                    <option value="Regular">Regular</option>
                                    <option value="Scholarship">Scholarship</option>
                                    <option value="Merit">Merit</option>
                                    <option value="VIP">VIP</option>
                                </select>
                            </div>

                            <!-- Class -->
                            <div class="col-md-2">
                                <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">
                                    Class
                                </label>
                                <select class="form-select form-select-sm" name="class" id="class">
                                    <option value="">All Classes</option>
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

                            <!-- Section -->
                            <div class="col-md-2">
                                <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">
                                    Section
                                </label>
                                <select class="form-select form-select-sm" name="section" id="section">
                                    <option value="">All Sections</option>
                                    <option value="A">A</option>
                                    <option value="B">B</option>
                                    <option value="C">C</option>
                                    <option value="D">D</option>
                                    <option value="E">E</option>
                                    <option value="F">F</option>
                                </select>
                            </div>

                            <!-- For Session -->
                            <div class="col-md-2">
                                <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">
                                    For Session
                                </label>
                                <select class="form-select form-select-sm" name="session" id="session">
                                    <option value="">All Sessions</option>
                                    <option value="2024-2025">2024-2025</option>
                                    <option value="2025-2026">2025-2026</option>
                                    <option value="2026-2027">2026-2027</option>
                                    <option value="2027-2028">2027-2028</option>
                                </select>
                            </div>

                            <!-- Filter Data Button -->
                            <div class="col-md-3">
                                <button type="submit" class="btn btn-primary w-100 filter-btn">
                                    <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">filter_list</span>
                                    <span style="font-size: 12px;">Filter Data</span>
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
// Form submission
document.getElementById('studentCardForm').addEventListener('submit', function(e) {
    e.preventDefault();
    // Filter logic will be implemented here
    alert('Filter functionality will be implemented here');
});
</script>
@endsection

