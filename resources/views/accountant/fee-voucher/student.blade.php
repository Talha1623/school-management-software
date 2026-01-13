@extends('layouts.accountant')

@section('title', 'Student Vouchers - Accountant')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-3">
            <h3 class="mb-3 fw-semibold" style="color: #003471;">Student Vouchers</h3>
            
            <!-- Filter Form -->
            <div class="card bg-light border-0 rounded-10 p-3 mb-3">
                <form method="GET" action="{{ route('accountant.fee-voucher.print') }}" id="filterForm" target="_blank">
                    <div class="row g-3">
                        <!-- Type -->
                        <div class="col-md-3">
                            <label class="form-label mb-1 fs-13 fw-medium">Type</label>
                            <div class="input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff;">
                                    <span class="material-symbols-outlined" style="font-size: 18px; color: #003471;">category</span>
                                </span>
                                <select class="form-select form-select-sm" name="type" id="type" style="height: 38px;">
                                    <option value="">All Types</option>
                                    <option value="Monthly Fee" {{ request('type') == 'Monthly Fee' ? 'selected' : '' }}>Monthly Fee</option>
                                    <option value="Transport Fee" {{ request('type') == 'Transport Fee' ? 'selected' : '' }}>Transport Fee</option>
                                    <option value="Custom Fee" {{ request('type') == 'Custom Fee' ? 'selected' : '' }}>Custom Fee</option>
                                    <option value="Other" {{ request('type') == 'Other' ? 'selected' : '' }}>Other</option>
                                </select>
                            </div>
                        </div>

                        <!-- Class -->
                        <div class="col-md-3">
                            <label class="form-label mb-1 fs-13 fw-medium">Class</label>
                            <div class="input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff;">
                                    <span class="material-symbols-outlined" style="font-size: 18px; color: #003471;">class</span>
                                </span>
                                <select class="form-select form-select-sm" name="class" id="class" style="height: 38px;">
                                    <option value="">All Classes</option>
                                    @foreach($classes as $class)
                                        <option value="{{ $class->class_name }}" {{ request('class') == $class->class_name ? 'selected' : '' }}>{{ $class->class_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <!-- Section -->
                        <div class="col-md-3">
                            <label class="form-label mb-1 fs-13 fw-medium">Section</label>
                            <div class="input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff;">
                                    <span class="material-symbols-outlined" style="font-size: 18px; color: #003471;">group</span>
                                </span>
                                <select class="form-select form-select-sm" name="section" id="section" style="height: 38px;">
                                    <option value="">All Sections</option>
                                    @foreach($sections as $section)
                                        <option value="{{ $section->name }}" {{ request('section') == $section->name ? 'selected' : '' }}>{{ $section->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <!-- Vouchers For -->
                        <div class="col-md-3">
                            <label class="form-label mb-1 fs-13 fw-medium">Vouchers For?</label>
                            <div class="input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff;">
                                    <span class="material-symbols-outlined" style="font-size: 18px; color: #003471;">receipt</span>
                                </span>
                                <select class="form-select form-select-sm" name="vouchers_for" id="vouchers_for" style="height: 38px;">
                                    <option value="">Select Month</option>
                                    <option value="January" {{ request('vouchers_for') == 'January' ? 'selected' : '' }}>January</option>
                                    <option value="February" {{ request('vouchers_for') == 'February' ? 'selected' : '' }}>February</option>
                                    <option value="March" {{ request('vouchers_for') == 'March' ? 'selected' : '' }}>March</option>
                                    <option value="April" {{ request('vouchers_for') == 'April' ? 'selected' : '' }}>April</option>
                                    <option value="May" {{ request('vouchers_for') == 'May' ? 'selected' : '' }}>May</option>
                                    <option value="June" {{ request('vouchers_for') == 'June' ? 'selected' : '' }}>June</option>
                                    <option value="July" {{ request('vouchers_for') == 'July' ? 'selected' : '' }}>July</option>
                                    <option value="August" {{ request('vouchers_for') == 'August' ? 'selected' : '' }}>August</option>
                                    <option value="September" {{ request('vouchers_for') == 'September' ? 'selected' : '' }}>September</option>
                                    <option value="October" {{ request('vouchers_for') == 'October' ? 'selected' : '' }}>October</option>
                                    <option value="November" {{ request('vouchers_for') == 'November' ? 'selected' : '' }}>November</option>
                                    <option value="December" {{ request('vouchers_for') == 'December' ? 'selected' : '' }}>December</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mt-3">
                        <div class="col-12">
                            <button type="submit" class="btn btn-sm px-4 py-2" style="background-color: #003471; color: white;">
                                <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">filter_list</span>
                                Filter
                            </button>
                            <a href="{{ route('accountant.fee-voucher.student') }}" class="btn btn-sm btn-outline-secondary px-4 py-2 ms-2">
                                <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">refresh</span>
                                Reset
                            </a>
                        </div>
                    </div>
                </form>
            </div>

        </div>
    </div>
</div>

<style>
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
    
    table thead th {
        font-size: 13px;
        font-weight: 600;
        padding: 12px 15px;
    }
    
    table tbody td {
        font-size: 13px;
        padding: 12px 15px;
        vertical-align: middle;
    }
    
    .badge {
        font-size: 11px;
        padding: 4px 8px;
    }
</style>

<script>
// Load sections when class is selected
document.getElementById('class').addEventListener('change', function() {
    const classValue = this.value;
    const sectionSelect = document.getElementById('section');
    
    // Clear existing options except "All Sections"
    sectionSelect.innerHTML = '<option value="">All Sections</option>';
    
    if (classValue) {
        // Show loading state
        sectionSelect.disabled = true;
        sectionSelect.innerHTML = '<option value="">Loading...</option>';
        
        // Fetch sections via AJAX
        fetch(`{{ route('accountant.fee-voucher.get-sections-by-class') }}?class=${encodeURIComponent(classValue)}`)
            .then(response => response.json())
            .then(data => {
                sectionSelect.innerHTML = '<option value="">All Sections</option>';
                
                if (data.sections && data.sections.length > 0) {
                    data.sections.forEach(section => {
                        const option = document.createElement('option');
                        option.value = section.name;
                        option.textContent = section.name;
                        sectionSelect.appendChild(option);
                    });
                }
                
                sectionSelect.disabled = false;
            })
            .catch(error => {
                console.error('Error loading sections:', error);
                sectionSelect.innerHTML = '<option value="">All Sections</option>';
                sectionSelect.disabled = false;
            });
    } else {
        sectionSelect.disabled = false;
    }
});

function generateVoucher(studentId) {
    // Add voucher generation logic here
    alert('Generate voucher for student ID: ' + studentId);
    // You can redirect to a voucher generation page or open a modal
}
</script>
@endsection

