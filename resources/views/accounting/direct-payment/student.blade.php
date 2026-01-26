@extends('layouts.app')

@section('title', 'Student Payment')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-3">
            <h3 class="mb-3 fw-semibold" style="color: #003471;">Student Payment</h3>
            
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

            <form id="studentPaymentForm" method="POST" action="{{ route('accounting.direct-payment.student.store') }}" class="compact-form">
                @csrf
                
                <div class="payment-row mb-2 p-2 border rounded" style="background-color: #f8f9fa;">
                    <div class="row g-2">
                        <!-- Campus -->
                        <div class="col-md-6">
                            <label class="form-label mb-1 fs-13 fw-medium">Campus</label>
                            <div class="input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff;">
                                    <span class="material-symbols-outlined" style="font-size: 18px; color: #003471;">home</span>
                                </span>
                                <select class="form-select form-select-sm" name="campus" id="campus" style="height: 32px;">
                                    <option value="">Select Campus</option>
                                    @foreach($campuses as $campus)
                                        <option value="{{ $campus }}" {{ (isset($student) && $student->campus == $campus) ? 'selected' : '' }}>{{ $campus }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <!-- Student Code -->
                        <div class="col-md-6">
                            <label class="form-label mb-1 fs-13 fw-medium">Student Code</label>
                            <div class="input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff;">
                                    <span class="material-symbols-outlined" style="font-size: 18px; color: #003471;">school</span>
                                </span>
                                <input type="text" class="form-control form-control-sm" name="student_code" id="student_code" placeholder="Student Roll/Code" value="{{ $studentCode ?? '' }}" required style="height: 32px;">
                            </div>
                            <div id="studentInfo" class="mt-2" style="display: none;">
                                <small class="text-success" id="studentName"></small>
                            </div>
                        </div>

                        <!-- Payment Title -->
                        <div class="col-md-6">
                            <label class="form-label mb-1 fs-13 fw-medium">Payment Title</label>
                            <div class="input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff;">
                                    <span class="material-symbols-outlined" style="font-size: 18px; color: #003471;">receipt</span>
                                </span>
                                <input type="text" class="form-control form-control-sm" name="payment_title" id="payment_title" placeholder="Enter Payment Title" required style="height: 32px;">
                            </div>
                            <div class="mt-2">
                                <select class="form-select form-select-sm" id="generated_fee_select" style="height: 32px;" disabled>
                                    <option value="">Generated fees will appear here</option>
                                </select>
                            </div>
                        </div>

                        <!-- Payment Amount -->
                        <div class="col-md-6">
                            <label class="form-label mb-1 fs-13 fw-medium">Payment Amount</label>
                            <div class="input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff;">
                                    <span class="material-symbols-outlined" style="font-size: 18px; color: #003471;">payments</span>
                                </span>
                                <input type="number" step="0.01" min="0" class="form-control form-control-sm" name="payment_amount" id="payment_amount" placeholder="Enter Payment Amount" required style="height: 32px;">
                            </div>
                        </div>

                        <!-- Discount -->
                        <div class="col-md-6">
                            <label class="form-label mb-1 fs-13 fw-medium">Discount</label>
                            <div class="input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff;">
                                    <span class="material-symbols-outlined" style="font-size: 18px; color: #003471;">remove</span>
                                </span>
                                <input type="number" step="0.01" min="0" class="form-control form-control-sm" name="discount" id="discount" placeholder="0" value="0" style="height: 32px;">
                            </div>
                        </div>

                        <!-- Method -->
                        <div class="col-md-6">
                            <label class="form-label mb-1 fs-13 fw-medium">Method</label>
                            <div class="input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff;">
                                    <span class="material-symbols-outlined" style="font-size: 18px; color: #003471;">account_balance_wallet</span>
                                </span>
                                <select class="form-select form-select-sm" name="method" id="method" required style="height: 32px;">
                                    <option value="">Select Method</option>
                                    @foreach($methods as $method)
                                        <option value="{{ $method }}" {{ $method === 'Cash Payment' ? 'selected' : '' }}>{{ $method }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <!-- Payment Date -->
                        <div class="col-md-6">
                            <label class="form-label mb-1 fs-13 fw-medium">Payment Date</label>
                            <div class="input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff;">
                                    <span class="material-symbols-outlined" style="font-size: 18px; color: #003471;">calendar_today</span>
                                </span>
                                <input type="date" class="form-control form-control-sm" name="payment_date" id="payment_date" value="{{ date('Y-m-d') }}" required style="height: 32px;">
                            </div>
                        </div>

                        <!-- SMS Notification -->
                        <div class="col-md-6">
                            <label class="form-label mb-1 fs-13 fw-medium">SMS Notification</label>
                            <div class="input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff;">
                                    <span class="material-symbols-outlined" style="font-size: 18px; color: #003471;">sms</span>
                                </span>
                                <select class="form-select form-select-sm" name="sms_notification" id="sms_notification" required style="height: 32px;">
                                    <option value="Yes" selected>Yes</option>
                                    <option value="No">No</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="text-center mt-4">
                    <button type="submit" class="btn btn-success px-5 py-2" style="background-color: #28a745; border: none; color: white;">
                        <span class="material-symbols-outlined" style="font-size: 18px; vertical-align: middle; color: white;">thumb_up</span>
                        <span style="color: white;">Take Payment</span>
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

    .compact-form .form-label {
        margin-bottom: 2px;
    }

    .compact-form .input-group-text {
        padding: 0 8px;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const studentCode = urlParams.get('student_code');
    const studentCodeInput = document.getElementById('student_code');
    const campusSelect = document.getElementById('campus');
    const studentInfo = document.getElementById('studentInfo');
    const studentName = document.getElementById('studentName');
    const generatedFeeSelect = document.getElementById('generated_fee_select');
    const paymentTitleInput = document.getElementById('payment_title');
    const paymentAmountInput = document.getElementById('payment_amount');
    let searchTimer = null;

    function resetGeneratedFees() {
        generatedFeeSelect.innerHTML = '<option value="">Generated fees will appear here</option>';
        generatedFeeSelect.disabled = true;
    }

    function applyGeneratedFees(fees) {
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

    function fetchStudentDetails(code) {
        if (!code) {
            studentInfo.style.display = 'none';
            studentName.textContent = '';
            resetGeneratedFees();
            return;
        }

        studentName.textContent = 'Searching...';
        studentName.className = 'text-info';
        studentInfo.style.display = 'block';

        fetch(`{{ route('accounting.get-student-by-code') }}?student_code=${encodeURIComponent(code)}`, {
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

    if (studentCode && studentCodeInput && !studentCodeInput.value) {
        studentCodeInput.value = studentCode;
    }

    if (studentCodeInput && studentCodeInput.value) {
        fetchStudentDetails(studentCodeInput.value.trim());
    }

    studentCodeInput.addEventListener('input', function() {
        if (searchTimer) {
            clearTimeout(searchTimer);
        }
        const code = this.value.trim();
        searchTimer = setTimeout(() => {
            fetchStudentDetails(code);
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
</script>
@endsection
