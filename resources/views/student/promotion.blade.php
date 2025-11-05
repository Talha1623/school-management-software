@extends('layouts.app')

@section('title', 'Student Promotion')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-3 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0 fs-16 fw-semibold">Student Promotion</h4>
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

            <!-- Promotion Form -->
            <div class="card border-0 shadow-sm" style="border-radius: 8px; overflow: hidden;">
                <div class="card-body p-4">
                    <form action="{{ route('student.promotion.promote') }}" method="POST" id="promotionForm">
                        @csrf
                        <div class="row g-3 align-items-end">
                            <!-- Campus -->
                            <div class="col-md-2">
                                <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">
                                    Campus
                                </label>
                                <select class="form-select form-select-sm" name="campus" id="campus">
                                    <option value="">All Campus</option>
                                    @foreach($campuses as $campus)
                                        <option value="{{ $campus }}">{{ $campus }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Promotion From Class -->
                            <div class="col-md-2">
                                <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">
                                    Promotion From Class <span class="text-danger">*</span>
                                </label>
                                <select class="form-select form-select-sm" name="from_class" id="from_class" required onchange="updateFromSections()">
                                    <option value="">Select Class</option>
                                    @foreach($classes as $class)
                                        <option value="{{ $class }}">{{ $class }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- From Section -->
                            <div class="col-md-2">
                                <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">
                                    Section
                                </label>
                                <select class="form-select form-select-sm" name="from_section" id="from_section">
                                    <option value="">All Sections</option>
                                </select>
                            </div>

                            <!-- Promotion To Class -->
                            <div class="col-md-2">
                                <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">
                                    Promotion To Class <span class="text-danger">*</span>
                                </label>
                                <select class="form-select form-select-sm" name="to_class" id="to_class" required onchange="updateToSections()">
                                    <option value="">Select Class</option>
                                    @foreach($classes as $class)
                                        <option value="{{ $class }}">{{ $class }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- To Section -->
                            <div class="col-md-2">
                                <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">
                                    Section
                                </label>
                                <select class="form-select form-select-sm" name="to_section" id="to_section">
                                    <option value="">All Sections</option>
                                </select>
                            </div>

                            <!-- Manage Promotion Button -->
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary w-100 promotion-btn">
                                    <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">trending_up</span>
                                    <span style="font-size: 12px;">Manage Promotion</span>
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
    .form-select-sm {
        font-size: 13px;
        padding: 6px 12px;
        border-radius: 6px;
        border: 1px solid #dee2e6;
        transition: all 0.3s ease;
    }
    
    .form-select-sm:focus {
        border-color: #003471;
        box-shadow: 0 0 0 3px rgba(0, 52, 113, 0.15);
        outline: none;
    }
    
    .promotion-btn {
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
    
    .promotion-btn:hover {
        background: linear-gradient(135deg, #004a9f 0%, #003471 100%);
        transform: translateY(-1px);
        box-shadow: 0 4px 10px rgba(0, 52, 113, 0.3);
        color: white;
    }
    
    .promotion-btn:active {
        transform: translateY(0);
    }
    
    .form-label {
        margin-bottom: 0.4rem;
        line-height: 1.3;
    }
</style>

<script>
// Get all sections from students
const allSections = @json($sections);

function updateFromSections() {
    const fromClass = document.getElementById('from_class').value;
    const fromSectionSelect = document.getElementById('from_section');
    
    // Clear existing options except "All Sections"
    fromSectionSelect.innerHTML = '<option value="">All Sections</option>';
    
    if (fromClass) {
        // Filter sections based on selected class
        // For now, show all sections - you can filter by class if needed
        allSections.forEach(section => {
            const option = document.createElement('option');
            option.value = section;
            option.textContent = section;
            fromSectionSelect.appendChild(option);
        });
    }
}

function updateToSections() {
    const toClass = document.getElementById('to_class').value;
    const toSectionSelect = document.getElementById('to_section');
    
    // Clear existing options except "All Sections"
    toSectionSelect.innerHTML = '<option value="">All Sections</option>';
    
    if (toClass) {
        // Filter sections based on selected class
        // For now, show all sections - you can filter by class if needed
        allSections.forEach(section => {
            const option = document.createElement('option');
            option.value = section;
            option.textContent = section;
            toSectionSelect.appendChild(option);
        });
    }
}

// Form validation
document.getElementById('promotionForm').addEventListener('submit', function(e) {
    const fromClass = document.getElementById('from_class').value;
    const toClass = document.getElementById('to_class').value;
    
    if (!fromClass || !toClass) {
        e.preventDefault();
        alert('Please select both Promotion From Class and Promotion To Class');
        return false;
    }
    
    if (fromClass === toClass) {
        e.preventDefault();
        alert('Promotion From Class and Promotion To Class cannot be the same');
        return false;
    }
    
    if (!confirm('Are you sure you want to promote students? This action cannot be undone.')) {
        e.preventDefault();
        return false;
    }
});
</script>
@endsection
