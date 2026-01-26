@extends('layouts.app')

@section('title', 'Decrement By Amount')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-3">
            <h3 class="mb-3 fw-semibold" style="color: #003471;">Decrement By Amount</h3>
            
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

            <form id="feeDecrementAmountForm" method="POST" action="{{ route('accounting.fee-document.decrement-amount.store') }}">
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
                                    @foreach($campuses as $campus)
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
document.addEventListener('DOMContentLoaded', function() {
    const campusSelect = document.getElementById('campus');
    const classSelect = document.getElementById('class');
    const sectionSelect = document.getElementById('section');
    const accountantSelect = document.getElementById('accountant');

    function resetSelect(selectEl, placeholder) {
        selectEl.innerHTML = `<option value="">${placeholder}</option>`;
    }

    function loadClassesByCampus(campus) {
        resetSelect(classSelect, 'Select Class');
        resetSelect(sectionSelect, 'Select Section');

        if (!campus) {
            return;
        }

        fetch(`{{ route('accounting.fee-document.decrement-amount.get-classes-by-campus') }}?campus=${encodeURIComponent(campus)}`)
            .then(response => response.json())
            .then(classes => {
                classes.forEach(className => {
                    const option = document.createElement('option');
                    option.value = className;
                    option.textContent = className;
                    classSelect.appendChild(option);
                });
            })
            .catch(() => {});
    }

    function loadSectionsByClass(campus, className) {
        resetSelect(sectionSelect, 'Select Section');

        if (!className) {
            return;
        }

        const params = new URLSearchParams();
        if (campus) {
            params.append('campus', campus);
        }
        params.append('class', className);

        fetch(`{{ route('accounting.fee-document.decrement-amount.get-sections-by-class') }}?${params.toString()}`)
            .then(response => response.json())
            .then(sections => {
                sections.forEach(sectionName => {
                    const option = document.createElement('option');
                    option.value = sectionName;
                    option.textContent = sectionName;
                    sectionSelect.appendChild(option);
                });
            })
            .catch(() => {});
    }

    function resetAccountants() {
        accountantSelect.innerHTML = '<option value="">Select Accountant</option>';
    }

    function loadAccountantsByCampus(campus) {
        resetAccountants();
        const params = new URLSearchParams();
        if (campus) {
            params.append('campus', campus);
        }
        fetch(`{{ route('accounting.fee-document.decrement-amount.get-accountants') }}?${params.toString()}`)
            .then(response => response.json())
            .then(accountants => {
                accountants.forEach(accountant => {
                    const option = document.createElement('option');
                    option.value = accountant.name;
                    option.textContent = accountant.name;
                    accountantSelect.appendChild(option);
                });
            })
            .catch(() => {});
    }

    campusSelect.addEventListener('change', function() {
        loadClassesByCampus(this.value);
        loadAccountantsByCampus(this.value);
    });

    classSelect.addEventListener('change', function() {
        loadSectionsByClass(campusSelect.value, this.value);
    });

    loadAccountantsByCampus(campusSelect.value);
});
</script>
@endsection
