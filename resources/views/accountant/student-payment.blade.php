@extends('layouts.accountant')

@section('title', 'Student Payment - Accountant')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="d-flex align-items-center mb-4">
            <h2 class="mb-0 fs-20 fw-semibold text-dark me-2">Student Payment</h2>
            <span class="material-symbols-outlined text-secondary" style="font-size: 20px;">payments</span>
        </div>

        <div class="card bg-white border border-white rounded-10 p-3">
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
            
            <form action="{{ route('accountant.direct-payment.student.store') }}" method="POST" id="studentPaymentForm" class="compact-form">
                @csrf
                
                <!-- First Row: Campus, Student Code -->
                <div class="row mb-2 g-2">
                    <div class="col-md-6">
                        <div class="card bg-light border-0 rounded-10 p-2 mb-2">
                            <h5 class="mb-1 py-2 px-3 text-white rounded-3 fw-semibold fs-15" style="margin: -8px -8px 8px -8px; background-color: #003471;">Campus</h5>
                            
                            <div class="mb-1">
                                <label for="campus" class="form-label mb-0 fs-13 fw-medium">Campus</label>
                                <select class="form-select form-select-sm py-1" id="campus" name="campus" style="height: 30px;">
                                    <option value="">Select Campus</option>
                                    @foreach($campuses as $campus)
                                        <option value="{{ $campus->campus_name ?? $campus }}" {{ ($defaultCampus ?? '') === ($campus->campus_name ?? $campus) ? 'selected' : '' }}>
                                            {{ $campus->campus_name ?? $campus }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card bg-light border-0 rounded-10 p-2 mb-2">
                            <h5 class="mb-1 py-2 px-3 text-white rounded-3 fw-semibold fs-15" style="margin: -8px -8px 8px -8px; background-color: #003471;">Student Code</h5>
                            
                            <div class="mb-1">
                                <label for="student_code" class="form-label mb-0 fs-13 fw-medium">Student Code <span class="text-danger">*</span></label>
                                <div class="input-group input-group-sm">
                                    <input type="text" class="form-control py-1" id="student_code" name="student_code" required style="height: 30px;" placeholder="Enter Student Code">
                                    <button type="button" class="btn btn-sm" onclick="searchStudent()" style="background-color: #003471; color: white; height: 30px;">
                                        <span class="material-symbols-outlined" style="font-size: 16px;">search</span>
                                    </button>
                                </div>
                                <div id="studentInfo" class="mt-2" style="display: none;">
                                    <small class="text-success" id="studentName"></small>
                                </div>
                                <div class="mt-2">
                                    <!-- <select class="form-select form-select-sm" id="generated_fee_select" style="height: 30px;" disabled>
                                        <option value="">Generated fees will appear here</option>
                                    </select> -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Second Row: Payment Title, Payment Amount -->
                <div class="row mb-2 g-2">
                    <div class="col-md-6">
                        <div class="card bg-light border-0 rounded-10 p-2 mb-2">
                            <h5 class="mb-1 py-2 px-3 text-white rounded-3 fw-semibold fs-15" style="margin: -8px -8px 8px -8px; background-color: #003471;">Payment Title</h5>
                            
                            <div class="mb-1">
                            <label for="payment_title" class="form-label mb-0 fs-13 fw-medium">Payment Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-sm py-1" id="payment_title" name="payment_title" required style="height: 30px;" placeholder="e.g., Monthly Fee, Admission Fee">
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card bg-light border-0 rounded-10 p-2 mb-2">
                            <h5 class="mb-1 py-2 px-3 text-white rounded-3 fw-semibold fs-15" style="margin: -8px -8px 8px -8px; background-color: #003471;">Payment Amount</h5>
                            
                            <div class="mb-1">
                                <label for="payment_amount" class="form-label mb-0 fs-13 fw-medium">Payment Amount <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" class="form-control form-control-sm py-1" id="payment_amount" name="payment_amount" required style="height: 30px;" placeholder="0.00" min="0">
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Third Row: Discount, Method -->
                <div class="row mb-2 g-2">
                    <div class="col-md-6">
                        <div class="card bg-light border-0 rounded-10 p-2 mb-2">
                            <h5 class="mb-1 py-2 px-3 text-white rounded-3 fw-semibold fs-15" style="margin: -8px -8px 8px -8px; background-color: #003471;">Discount</h5>
                            
                            <div class="mb-1">
                                <label for="discount" class="form-label mb-0 fs-13 fw-medium">Discount</label>
                            <input type="number" step="0.01" class="form-control form-control-sm py-1" id="discount" name="discount" style="height: 30px;" placeholder="0.00" min="0" value="0">
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card bg-light border-0 rounded-10 p-2 mb-2">
                            <h5 class="mb-1 py-2 px-3 text-white rounded-3 fw-semibold fs-15" style="margin: -8px -8px 8px -8px; background-color: #003471;">Method</h5>
                            
                            <div class="mb-1">
                                <label for="method" class="form-label mb-0 fs-13 fw-medium">Method <span class="text-danger">*</span></label>
                            <select class="form-select form-select-sm py-1" id="method" name="method" required style="height: 30px;">
                                    <option value="">Select Payment Method</option>
                                    @foreach($methods as $method)
                                        <option value="{{ $method }}">{{ $method }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Hidden Payment Date -->
                <input type="hidden" name="payment_date" value="{{ date('Y-m-d') }}">
                
                <!-- Submit Button -->
                <div class="row mb-2">
                    <div class="col-12">
                        <div class="d-flex justify-content-end gap-2">
                            <button type="reset" class="btn btn-sm btn-secondary px-4 py-2" onclick="resetForm()">
                                <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">refresh</span>
                                Reset
                            </button>
                            <button type="submit" class="btn btn-sm px-4 py-2" style="background: linear-gradient(135deg, #003471 0%, #004a9f 100%); color: white; border: none;">
                                <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">payments</span>
                                Take Payment
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

    .compact-form .form-label {
        margin-bottom: 2px;
    }
</style>

<script>
function searchStudent() {
    const studentCodeInput = document.getElementById('student_code');
    const studentCode = studentCodeInput.value.trim();
    fetchStudentDetails(studentCode, true);
}

function resetForm() {
    document.getElementById('studentPaymentForm').reset();
    document.getElementById('studentInfo').style.display = 'none';
    document.getElementById('studentName').textContent = '';
    resetGeneratedFees();
}

function resetGeneratedFees() {
    const generatedFeeSelect = document.getElementById('generated_fee_select');
    generatedFeeSelect.innerHTML = '<option value="">Generated fees will appear here</option>';
    generatedFeeSelect.disabled = true;
}

function applyGeneratedFees(fees) {
    const generatedFeeSelect = document.getElementById('generated_fee_select');
    resetGeneratedFees();
    if (!fees || fees.length === 0) {
        return;
    }

    generatedFeeSelect.innerHTML = '<option value="">Select generated fee</option>';
    fees.forEach(fee => {
        const option = document.createElement('option');
        const amount = Number(fee.payment_amount || 0).toFixed(2);
        option.value = fee.id;
        option.textContent = `${fee.payment_title} - ${amount}`;
        option.dataset.title = fee.payment_title || '';
        option.dataset.amount = fee.payment_amount || 0;
        generatedFeeSelect.appendChild(option);
    });
    generatedFeeSelect.disabled = false;
}

function fetchStudentDetails(studentCode, showAlertOnEmpty = false) {
    const studentInfo = document.getElementById('studentInfo');
    const studentName = document.getElementById('studentName');
    const campusSelect = document.getElementById('campus');

    if (!studentCode) {
        if (showAlertOnEmpty) {
            alert('Please enter a student code');
        }
        studentInfo.style.display = 'none';
        studentName.textContent = '';
        resetGeneratedFees();
        return;
    }

    studentName.textContent = 'Searching...';
    studentName.className = 'text-info';
    studentInfo.style.display = 'block';

    fetch(`{{ route('accountant.get-student-by-code') }}?student_code=${encodeURIComponent(studentCode)}`, {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            studentName.textContent = `Student: ${data.student.student_name} (${data.student.class} - ${data.student.section})`;
            studentName.className = 'text-success';
            
            if (data.student.campus && !campusSelect.value) {
                campusSelect.value = data.student.campus;
            }

            applyGeneratedFees(data.generated_fees);
        } else {
            studentName.textContent = data.message || 'Student not found';
            studentName.className = 'text-danger';
            resetGeneratedFees();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        studentName.textContent = 'Error searching for student';
        studentName.className = 'text-danger';
        resetGeneratedFees();
    });
}

document.addEventListener('DOMContentLoaded', function() {
    const studentCodeInput = document.getElementById('student_code');
    const generatedFeeSelect = document.getElementById('generated_fee_select');
    const paymentTitleInput = document.getElementById('payment_title');
    const paymentAmountInput = document.getElementById('payment_amount');
    let searchTimer = null;

    studentCodeInput.addEventListener('input', function() {
        if (searchTimer) {
            clearTimeout(searchTimer);
        }
        const code = this.value.trim();
        searchTimer = setTimeout(() => {
            fetchStudentDetails(code, false);
        }, 500);
    });

    generatedFeeSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        if (!selectedOption || !selectedOption.dataset.title) {
            return;
        }
        paymentTitleInput.value = selectedOption.dataset.title;
        paymentAmountInput.value = selectedOption.dataset.amount || '';
    });
});

// Auto-search when student code is entered and Enter is pressed
document.getElementById('student_code').addEventListener('keypress', function(event) {
    if (event.key === 'Enter') {
        event.preventDefault();
        searchStudent();
    }
});
</script>
@endsection
