@extends('layouts.app')

@section('title', 'Generate Salary')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-3 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0 fs-16 fw-semibold">Generate Salary</h4>
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

            <!-- Success/Error Messages -->
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <span class="material-symbols-outlined" style="font-size: 18px; vertical-align: middle;">check_circle</span>
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <span class="material-symbols-outlined" style="font-size: 18px; vertical-align: middle;">error</span>
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if(session('info'))
                <div class="alert alert-info alert-dismissible fade show" role="alert">
                    <span class="material-symbols-outlined" style="font-size: 18px; vertical-align: middle;">info</span>
                    {{ session('info') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <span class="material-symbols-outlined" style="font-size: 18px; vertical-align: middle;">error</span>
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div class="row g-3">
                <!-- Left Side - Form Fields -->
                <div class="col-md-5">
                    <form action="{{ route('salary-loan.generate-salary.store') }}" method="POST" id="generateSalaryForm">
                        @csrf
                        
                        <div class="row g-2">
                            <!-- Campus -->
                            <div class="col-12">
                                <label for="campus" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Campus <span class="text-danger">*</span></label>
                                <div class="input-group input-group-sm salary-input-group">
                                    <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                        <span class="material-symbols-outlined" style="font-size: 14px;">location_on</span>
                                    </span>
                                    <select class="form-control salary-input" name="campus" id="campus" required style="border: none; border-left: 1px solid #e0e7ff; border-radius: 0 8px 8px 0; height: 32px; font-size: 12px;">
                                        <option value="">Select Campus</option>
                                        @if(isset($campuses) && $campuses->count() > 0)
                                            @foreach($campuses as $campus)
                                                <option value="{{ $campus->campus_name ?? $campus }}">{{ $campus->campus_name ?? $campus }}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>
                            </div>

                            <!-- Month -->
                            <div class="col-12">
                                <label for="month" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Month <span class="text-danger">*</span></label>
                                <div class="input-group input-group-sm salary-input-group">
                                    <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                        <span class="material-symbols-outlined" style="font-size: 14px;">calendar_month</span>
                                    </span>
                                    <select class="form-control salary-input" name="month" id="month" required style="border: none; border-left: 1px solid #e0e7ff; border-radius: 0 8px 8px 0; height: 32px; font-size: 12px;">
                                        <option value="">Select Month</option>
                                        <option value="01" {{ $currentMonth == '01' ? 'selected' : '' }}>January</option>
                                        <option value="02" {{ $currentMonth == '02' ? 'selected' : '' }}>February</option>
                                        <option value="03" {{ $currentMonth == '03' ? 'selected' : '' }}>March</option>
                                        <option value="04" {{ $currentMonth == '04' ? 'selected' : '' }}>April</option>
                                        <option value="05" {{ $currentMonth == '05' ? 'selected' : '' }}>May</option>
                                        <option value="06" {{ $currentMonth == '06' ? 'selected' : '' }}>June</option>
                                        <option value="07" {{ $currentMonth == '07' ? 'selected' : '' }}>July</option>
                                        <option value="08" {{ $currentMonth == '08' ? 'selected' : '' }}>August</option>
                                        <option value="09" {{ $currentMonth == '09' ? 'selected' : '' }}>September</option>
                                        <option value="10" {{ $currentMonth == '10' ? 'selected' : '' }}>October</option>
                                        <option value="11" {{ $currentMonth == '11' ? 'selected' : '' }}>November</option>
                                        <option value="12" {{ $currentMonth == '12' ? 'selected' : '' }}>December</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Year -->
                            <div class="col-12">
                                <label for="year" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Year <span class="text-danger">*</span></label>
                                <div class="input-group input-group-sm salary-input-group">
                                    <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                        <span class="material-symbols-outlined" style="font-size: 14px;">calendar_today</span>
                                    </span>
                                    <select class="form-control salary-input" name="year" id="year" required style="border: none; border-left: 1px solid #e0e7ff; border-radius: 0 8px 8px 0; height: 32px; font-size: 12px;">
                                        <option value="">Select Year</option>
                                        @for($y = date('Y'); $y >= date('Y') - 5; $y--)
                                            <option value="{{ $y }}" {{ $currentYear == $y ? 'selected' : '' }}>{{ $y }}</option>
                                        @endfor
                                    </select>
                                </div>
                            </div>

                            <!-- Deduction Per Late Arrival -->
                            <div class="col-12">
                                <label for="deduction_per_late_arrival" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Deduction Per Late Arrival</label>
                                <div class="input-group input-group-sm salary-input-group">
                                    <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                        <span class="material-symbols-outlined" style="font-size: 14px;">currency_rupee</span>
                                    </span>
                                    <input type="number" class="form-control salary-input" name="deduction_per_late_arrival" id="deduction_per_late_arrival" placeholder="Enter deduction amount" step="0.01" min="0" value="0" style="height: 32px; font-size: 12px;">
                                </div>
                            </div>

                            <!-- Filter Button -->
                            <div class="col-12">
                                <button type="button" class="btn btn-sm w-100 py-2 px-3 rounded-8 filter-staff-btn" onclick="loadStaffList()" style="background: linear-gradient(135deg, #003471 0%, #004a9f 100%); color: white; border: none; font-weight: 500;">
                                    <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">filter_list</span>
                                    Filter
                                </button>
                            </div>
                        </div>

                        <!-- Generate Salary Button -->
                        <div class="mt-4">
                            <button type="submit" class="btn btn-sm w-100 py-2 px-4 rounded-8 generate-salary-btn" id="generateSalaryBtn">
                                <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">thumb_up</span>
                                Generate Salary
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Right Side - Staff List -->
                <div class="col-md-7">
                    <div class="card bg-light border-0 rounded-10 p-3" id="staff-list-card" style="display: none;">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0 fs-15 fw-semibold">Staff Code | <span style="color: #dc3545;">Name</span> | Designation</h5>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-sm btn-info px-3 py-1" onclick="selectAllStaff()" style="font-size: 12px;">
                                    Select All
                                </button>
                                <button type="button" class="btn btn-sm btn-danger px-3 py-1" onclick="selectNoneStaff()" style="font-size: 12px;">
                                    Select None
                                </button>
                            </div>
                        </div>

                        <div id="staff-list-container" style="overflow: visible;">
                            <div class="text-center text-muted py-5">
                                <span class="material-symbols-outlined" style="font-size: 48px; opacity: 0.3;">people</span>
                                <p class="mt-2 mb-0">Please select Campus, Month, and Year, then click Filter to load staff list.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Generated Salaries Table -->
            @if(isset($generatedSalaries) && $generatedSalaries->count() > 0)
            <div class="mt-4">
                <div class="card bg-white border border-white rounded-10 p-3">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0 fs-16 fw-semibold">Generated Salaries</h5>
                        @if(isset($generatedCampus) && isset($generatedMonth) && isset($generatedYear))
                        <span class="badge bg-info text-white">
                            {{ $generatedCampus }} - 
                            @php
                                $monthNames = [
                                    '01' => 'January', '02' => 'February', '03' => 'March', '04' => 'April',
                                    '05' => 'May', '06' => 'June', '07' => 'July', '08' => 'August',
                                    '09' => 'September', '10' => 'October', '11' => 'November', '12' => 'December'
                                ];
                                $monthName = $monthNames[$generatedMonth] ?? $generatedMonth;
                            @endphp
                            {{ $monthName }} {{ $generatedYear }}
                        </span>
                        @endif
                    </div>

                    <div class="default-table-area">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Photo</th>
                                        <th>Name</th>
                                        <th>Salary Month</th>
                                        <th class="text-center">Present</th>
                                        <th class="text-center">Absent</th>
                                        <th class="text-center">Late</th>
                                        <th class="text-end">Basic</th>
                                        <th class="text-end">Salary Generated</th>
                                        <th class="text-end">Amount Paid</th>
                                        <th class="text-end">Loan Repayment</th>
                                        <th>Status</th>
                                        <th class="text-center">Make Payment</th>
                                        <th class="text-center">Delete</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($generatedSalaries as $salary)
                                    <tr>
                                        <td>{{ $salary->id }}</td>
                                        <td>
                                            @if($salary->staff && $salary->staff->photo)
                                                <img src="{{ asset('storage/' . $salary->staff->photo) }}" alt="Photo" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
                                            @else
                                                <div style="width: 40px; height: 40px; border-radius: 50%; background-color: #e0e7ff; display: flex; align-items: center; justify-content: center;">
                                                    <span class="material-symbols-outlined" style="font-size: 20px; color: #003471;">person</span>
                                                </div>
                                            @endif
                                        </td>
                                        <td>
                                            <strong class="text-dark">{{ $salary->staff->name ?? 'N/A' }}</strong>
                                        </td>
                                        <td>
                                            <span class="badge bg-info text-white" style="font-size: 11px;">{{ $salary->salary_month }} {{ $salary->year }}</span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-success text-white" style="font-size: 11px;">{{ $salary->present ?? 0 }}</span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-danger text-white" style="font-size: 11px;">{{ $salary->absent ?? 0 }}</span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-warning text-dark" style="font-size: 11px;">{{ $salary->late ?? 0 }}</span>
                                        </td>
                                        <td class="text-end">
                                            <strong class="text-primary">{{ number_format($salary->basic ?? 0, 2) }}</strong>
                                            <div>
                                                <span class="badge bg-light text-dark" style="font-size: 10px;">
                                                    {{ $salary->staff->salary_type ?? 'full time' }}
                                                </span>
                                            </div>
                                        </td>
                                        <td class="text-end">
                                            <strong class="text-success">{{ number_format($salary->salary_generated ?? 0, 2) }}</strong>
                                        </td>
                                        <td class="text-end">
                                            <strong class="text-info">{{ number_format($salary->amount_paid ?? 0, 2) }}</strong>
                                        </td>
                                        <td class="text-end">
                                            <strong class="text-warning">{{ number_format($salary->loan_repayment ?? 0, 2) }}</strong>
                                        </td>
                                        <td>
                                        @if($salary->status == 'Pending')
                                            <span class="badge bg-warning text-dark" style="font-size: 11px;">Pending</span>
                                        @elseif($salary->status == 'Paid')
                                            <span class="badge bg-success text-white" style="font-size: 11px;">Paid</span>
                                        @else
                                            <span class="badge bg-info text-white" style="font-size: 11px;">Issued</span>
                                        @endif
                                        </td>
                                        <td class="text-center">
                                        @if($salary->status != 'Pending')
                                                <button type="button" class="btn btn-sm btn-success px-2 py-1" onclick="printSalarySlip({{ $salary->id }})" title="Print Slip" style="color: white; font-size: 11px;">
                                                    Print Slip
                                                </button>
                                            @else
                                                <button type="button" class="btn btn-sm btn-primary px-2 py-1" onclick="openMakePaymentModal({{ $salary->id }}, '{{ addslashes($salary->staff->campus ?? 'N/A') }}', '{{ addslashes($salary->staff->name ?? 'N/A') }}', '{{ $salary->salary_month }} {{ $salary->year }}', {{ $salary->salary_generated ?? 0 }}, {{ $salary->amount_paid ?? 0 }}, {{ $salary->loan_repayment ?? 0 }})" title="Make Payment" style="color: white; font-size: 11px;">
                                                    Make Payment
                                                </button>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-sm btn-danger px-2 py-1" onclick="deleteSalary({{ $salary->id }})" title="Delete">
                                                <span class="material-symbols-outlined" style="font-size: 16px; color: white;">delete</span>
                                            </button>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                @if($generatedSalaries->count() > 0)
                                <tfoot>
                                    <tr style="background-color: #f8f9fa; font-weight: 600;">
                                        <td colspan="8" class="text-end"><strong>Total:</strong></td>
                                        <td class="text-end"><strong class="text-primary">{{ number_format($generatedSalaries->sum('basic'), 2) }}</strong></td>
                                        <td class="text-end"><strong class="text-success">{{ number_format($generatedSalaries->sum('salary_generated'), 2) }}</strong></td>
                                        <td class="text-end"><strong class="text-info">{{ number_format($generatedSalaries->sum('amount_paid'), 2) }}</strong></td>
                                        <td class="text-end"><strong class="text-warning">{{ number_format($generatedSalaries->sum('loan_repayment'), 2) }}</strong></td>
                                        <td colspan="2"></td>
                                    </tr>
                                </tfoot>
                                @endif
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

<!-- Make Payment Modal -->
<div class="modal fade" id="makePaymentModal" tabindex="-1" aria-labelledby="makePaymentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" style="border-radius: 12px; border: none;">
            <div class="modal-header" style="background: linear-gradient(135deg, #003471 0%, #004a9f 100%); color: white; border-radius: 12px 12px 0 0;">
                <h5 class="modal-title fw-semibold" id="makePaymentModalLabel" style="color: white;">
                    <span class="material-symbols-outlined" style="font-size: 20px; vertical-align: middle; color: white;">payments</span>
                    <span style="color: white;">Make Payment</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="makePaymentForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body p-4" style="max-height: 70vh; overflow-y: auto;">
                    <div class="row g-2">
                        <!-- Campus -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold mb-1" style="color: #003471; font-size: 12px;">Campus</label>
                            <input type="text" class="form-control form-control-sm" id="payment_campus" readonly style="background-color: #f8f9fa; height: 32px; font-size: 12px;">
                        </div>
                        
                        <!-- Employee -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold mb-1" style="color: #003471; font-size: 12px;">Employee</label>
                            <input type="text" class="form-control form-control-sm" id="payment_employee" readonly style="background-color: #f8f9fa; height: 32px; font-size: 12px;">
                        </div>
                        
                        <!-- Month -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold mb-1" style="color: #003471; font-size: 12px;">Month</label>
                            <input type="text" class="form-control form-control-sm" id="payment_month" readonly style="background-color: #f8f9fa; height: 32px; font-size: 12px;">
                        </div>
                        
                        <!-- Generated Salary -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold mb-1" style="color: #003471; font-size: 12px;">Generated Salary</label>
                            <input type="text" class="form-control form-control-sm" id="payment_salary_generated" readonly style="background-color: #f8f9fa; height: 32px; font-size: 12px;">
                        </div>
                        
                        <!-- Amount Paid -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold mb-1" style="color: #003471; font-size: 12px;">Amount Paid <span class="text-danger">*</span></label>
                            <input type="number" class="form-control form-control-sm" name="amount_paid" id="payment_amount_paid" step="0.01" min="0" required style="height: 32px; font-size: 12px;">
                        </div>
                        
                        <!-- Loan Repayment -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold mb-1" style="color: #003471; font-size: 12px;">Loan Repayment</label>
                            <input type="number" class="form-control form-control-sm" name="loan_repayment" id="payment_loan_repayment" step="0.01" min="0" value="0" style="height: 32px; font-size: 12px;">
                        </div>
                        
                        <!-- Bonus Title -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold mb-1" style="color: #003471; font-size: 12px;">Bonus Title</label>
                            <input type="text" class="form-control form-control-sm" name="bonus_title" id="payment_bonus_title" placeholder="Enter bonus title" style="height: 32px; font-size: 12px;">
                        </div>
                        
                        <!-- Bonus Amount -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold mb-1" style="color: #003471; font-size: 12px;">Bonus Amount</label>
                            <input type="number" class="form-control form-control-sm" name="bonus_amount" id="payment_bonus_amount" step="0.01" min="0" value="0" style="height: 32px; font-size: 12px;">
                        </div>
                        
                        <!-- Deduction Title -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold mb-1" style="color: #003471; font-size: 12px;">Deduction Title</label>
                            <input type="text" class="form-control form-control-sm" name="deduction_title" id="payment_deduction_title" placeholder="Enter deduction title" style="height: 32px; font-size: 12px;">
                        </div>
                        
                        <!-- Deduction Amount -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold mb-1" style="color: #003471; font-size: 12px;">Deduction Amount</label>
                            <input type="number" class="form-control form-control-sm" name="deduction_amount" id="payment_deduction_amount" step="0.01" min="0" value="0" style="height: 32px; font-size: 12px;">
                        </div>
                        
                        <!-- Payment Method -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold mb-1" style="color: #003471; font-size: 12px;">Payment Method <span class="text-danger">*</span></label>
                            <select class="form-control form-control-sm" name="payment_method" id="payment_method" required style="height: 32px; font-size: 12px;">
                                <option value="">Select Payment Method</option>
                                <option value="Bank">Bank</option>
                                <option value="Wallet">Wallet</option>
                                <option value="Transfer">Transfer</option>
                                <option value="Card">Card</option>
                                <option value="Check">Check</option>
                                <option value="Deposit">Deposit</option>
                                <option value="Cash">Cash</option>
                            </select>
                        </div>
                        
                        <!-- Fully Paid? -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold mb-1" style="color: #003471; font-size: 12px;">Fully Paid?</label>
                            <select class="form-control form-control-sm" name="fully_paid" id="payment_fully_paid" style="height: 32px; font-size: 12px;">
                                <option value="0">No</option>
                                <option value="1">Yes</option>
                            </select>
                        </div>
                        
                        <!-- Payment Date -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold mb-1" style="color: #003471; font-size: 12px;">Payment Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control form-control-sm" name="payment_date" id="payment_date" required value="{{ date('Y-m-d') }}" style="height: 32px; font-size: 12px;">
                        </div>
                        
                        <!-- Notify Employee -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold mb-1" style="color: #003471; font-size: 12px;">Notify Employee</label>
                            <select class="form-control form-control-sm" name="notify_employee" id="payment_notify_employee" style="height: 32px; font-size: 12px;">
                                <option value="0">No</option>
                                <option value="1">Yes</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer" style="border-top: 1px solid #dee2e6;">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm" style="background: linear-gradient(135deg, #003471 0%, #004a9f 100%); border: none; color: white;">
                        <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle; color: white;">save</span>
                        <span style="color: white;">Save Payment</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    /* Salary Form Styling */
    .salary-input-group {
        height: 32px;
        border-radius: 6px;
        overflow: hidden;
        transition: all 0.3s ease;
        border: 1px solid #dee2e6;
    }
    
    .salary-input-group:focus-within {
        box-shadow: 0 0 0 3px rgba(0, 52, 113, 0.15);
        border-color: #003471;
    }
    
    .salary-input {
        height: 32px;
        font-size: 12px;
        padding: 0.4rem 0.6rem;
        border: none;
        border-left: 1px solid #e0e7ff;
        border-radius: 0 6px 6px 0;
        transition: all 0.3s ease;
    }
    
    .salary-input:focus {
        border-left-color: #003471;
        box-shadow: none;
        outline: none;
    }
    
    .salary-input-group .input-group-text {
        height: 32px;
        padding: 0 0.6rem;
        display: flex;
        align-items: center;
        border: none;
        border-right: 1px solid #e0e7ff;
        border-radius: 6px 0 0 6px;
        transition: all 0.3s ease;
    }
    
    .salary-input-group:focus-within .input-group-text {
        background-color: #003471 !important;
        color: white !important;
        border-right-color: #003471;
    }
    
    .salary-input-group select.salary-input {
        appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23003471' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 0.75rem center;
        padding-right: 2.5rem;
    }
    
    .form-label {
        margin-bottom: 0.4rem;
        line-height: 1.3;
    }
    
    .generate-salary-btn {
        background: linear-gradient(135deg, #003471 0%, #004a9f 100%);
        color: white;
        border: none;
        font-weight: 500;
        transition: all 0.3s ease;
        box-shadow: 0 2px 8px rgba(0, 52, 113, 0.25);
    }
    
    .generate-salary-btn:hover {
        background: linear-gradient(135deg, #004a9f 0%, #003471 100%);
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0, 52, 113, 0.35);
        color: white;
    }
    
    .generate-salary-btn:active {
        transform: translateY(0);
    }
    
    .rounded-8 {
        border-radius: 8px;
    }
    
    .rounded-10 {
        border-radius: 10px;
    }

    /* Table Styling */
    .default-table-area {
        margin-top: 0;
    }

    .default-table-area table {
        margin-bottom: 0;
        border-collapse: separate;
        border-spacing: 0;
    }

    .default-table-area table thead th {
        padding: 8px 12px;
        font-size: 13px;
        font-weight: 600;
        vertical-align: middle;
        line-height: 1.3;
        white-space: nowrap;
        border: 1px solid #dee2e6;
        background-color: #f8f9fa;
        color: #003471;
    }

    .default-table-area table tbody td {
        padding: 8px 12px;
        font-size: 13px;
        vertical-align: middle;
        line-height: 1.4;
        border: 1px solid #dee2e6;
    }

    .default-table-area table tbody tr:hover {
        background-color: #f8f9fa;
    }

    .default-table-area .badge {
        font-size: 11px;
        padding: 3px 6px;
        font-weight: 500;
    }

    .default-table-area table tfoot td {
        padding: 10px 12px;
        font-size: 13px;
        border: 1px solid #dee2e6;
    }

    /* Staff List Styling */
    #staff-list-container .list-group-item {
        transition: all 0.2s ease;
    }

    #staff-list-container .list-group-item:hover {
        background-color: #f8f9fa !important;
    }

    .staff-checkbox:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    .btn-info {
        background-color: #17a2b8;
        border-color: #17a2b8;
        color: white;
    }

    .btn-info:hover {
        background-color: #138496;
        border-color: #117a8b;
        color: white;
    }

    .filter-staff-btn:hover {
        background: linear-gradient(135deg, #004a9f 0%, #003471 100%);
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0, 52, 113, 0.35);
        color: white;
    }

    .filter-staff-btn:active {
        transform: translateY(0);
    }

    /* Remove scroll from staff list */
    #staff-list-container {
        overflow: visible !important;
        max-height: none !important;
    }
</style>

<script>
// Global function to load staff list
function loadStaffList() {
    const campus = document.getElementById('campus').value;
    const month = document.getElementById('month').value;
    const year = document.getElementById('year').value;
    
    if (!campus || !month || !year) {
        document.getElementById('staff-list-container').innerHTML = `
            <div class="text-center text-muted py-5">
                <span class="material-symbols-outlined" style="font-size: 48px; opacity: 0.3;">people</span>
                <p class="mt-2 mb-0">Please select Campus, Month, and Year, then click Filter to load staff list.</p>
            </div>
        `;
        // Hide staff list card if filters are not complete
        const staffListCard = document.getElementById('staff-list-card');
        if (staffListCard) {
            staffListCard.style.display = 'none';
        }
        return;
    }
    
    // Show staff list card when filter is clicked
    const staffListCard = document.getElementById('staff-list-card');
    if (staffListCard) {
        staffListCard.style.display = 'block';
    }
    
    // Show loading
    document.getElementById('staff-list-container').innerHTML = `
        <div class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2 text-muted">Loading staff list...</p>
        </div>
    `;
    
    // Fetch staff list
    fetch(`{{ route('salary-loan.generate-salary.get-staff') }}?campus=${campus}&month=${month}&year=${year}`)
        .then(response => response.json())
        .then(data => {
            renderStaffList(data.staff || []);
        })
        .catch(error => {
            console.error('Error loading staff:', error);
            document.getElementById('staff-list-container').innerHTML = `
                <div class="text-center text-danger py-5">
                    <span class="material-symbols-outlined" style="font-size: 48px; opacity: 0.3;">error</span>
                    <p class="mt-2 mb-0">Error loading staff list. Please try again.</p>
                </div>
            `;
        });
}

function renderStaffList(staffList) {
    const container = document.getElementById('staff-list-container');
    const staffListCard = document.getElementById('staff-list-card');
    
    // Show staff list card
    if (staffListCard) {
        staffListCard.style.display = 'block';
    }
    
    if (staffList.length === 0) {
        container.innerHTML = `
            <div class="text-center text-muted py-5">
                <span class="material-symbols-outlined" style="font-size: 48px; opacity: 0.3;">people</span>
                <p class="mt-2 mb-0">No staff found for the selected criteria.</p>
            </div>
        `;
        return;
    }
    
    let html = '<div class="list-group">';
    staffList.forEach(staff => {
        const isGenerated = staff.is_generated;
        const statusClass = isGenerated ? 'text-success' : 'text-warning';
        const statusText = isGenerated ? 'Generated' : 'Ready';
        const rowBg = isGenerated ? 'background-color: #fff3cd;' : '';
        
        html += `
            <div class="list-group-item d-flex justify-content-between align-items-center" style="${rowBg} border: 1px solid #dee2e6; margin-bottom: 8px; border-radius: 6px; padding: 12px;">
                <div class="d-flex align-items-center gap-3" style="flex: 1;">
                    <input type="checkbox" 
                           name="selected_staff[]" 
                           value="${staff.id}" 
                           class="staff-checkbox form-check-input" 
                           ${isGenerated ? 'disabled' : ''}
                           data-staff-id="${staff.id}"
                           style="width: 18px; height: 18px; margin-top: 0;">
                    <div>
                        <div style="font-size: 13px;">
                            <strong>${staff.emp_id}</strong> | 
                            <span style="color: #dc3545; font-weight: 500;">${staff.name}</span> | 
                            ${staff.designation}
                        </div>
                    </div>
                </div>
                <div class="text-end">
                    ${isGenerated ? `
                        <span class="badge bg-success" style="font-size: 11px;">${statusText}</span>
                    ` : `
                        <span class="badge bg-warning text-dark" style="font-size: 11px;">${statusText}</span>
                    `}
                </div>
            </div>
        `;
    });
    html += '</div>';
    
    container.innerHTML = html;
}

// Select All - only select non-generated staff
function selectAllStaff() {
    const checkboxes = document.querySelectorAll('.staff-checkbox:not(:disabled)');
    checkboxes.forEach(checkbox => {
        checkbox.checked = true;
    });
}

// Select None
function selectNoneStaff() {
    const checkboxes = document.querySelectorAll('.staff-checkbox');
    checkboxes.forEach(checkbox => {
        checkbox.checked = false;
    });
}

// Form submission validation - wait for DOM to be ready
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('generateSalaryForm');
    if (!form) return;
    
    form.addEventListener('submit', function(e) {
        e.preventDefault(); // Prevent default submission
        
        // Get all enabled checked checkboxes (checkboxes are outside the form)
        let enabledCheckedCheckboxes = document.querySelectorAll('input[name="selected_staff[]"]:checked:not(:disabled)');
        let allCheckedCheckboxes = document.querySelectorAll('input[name="selected_staff[]"]:checked');
        
        // Debug log
        console.log('Enabled checked checkboxes:', enabledCheckedCheckboxes.length);
        console.log('All checked checkboxes:', allCheckedCheckboxes.length);
        
        // Check if staff list is loaded
        const staffListCard = document.getElementById('staff-list-card');
        const isStaffListVisible = staffListCard && staffListCard.style.display !== 'none';
        
        // Remove any existing hidden inputs for selected_staff
        form.querySelectorAll('input[name="selected_staff[]"]').forEach(function(input) {
            input.remove();
        });
        
        // If no enabled checked checkboxes
        if (enabledCheckedCheckboxes.length === 0) {
            if (!isStaffListVisible) {
                alert('Please first click the Filter button to load staff list, then select staff members.');
                return false;
            }
            // If staff list is visible, allow form submission
            // Server will check for existing salaries for the selected month/year
            // If existing salaries exist, they will be shown in table
            // If no existing salaries, server will show error
            // Don't add any hidden inputs, form will submit with empty selected_staff array
        } else {
            // Add selected staff IDs as hidden inputs to the form (only enabled ones)
            enabledCheckedCheckboxes.forEach(function(checkbox) {
                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = 'selected_staff[]';
                hiddenInput.value = checkbox.value;
                form.appendChild(hiddenInput);
            });
        }
        
        // Show loading indicator
        const submitBtn = document.getElementById('generateSalaryBtn');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Generating...';
        }
        
        // Submit the form
        form.submit();
    });
});

// Open Make Payment Modal
function openMakePaymentModal(salaryId, campus, employeeName, month, salaryGenerated, amountPaid, loanRepayment) {
    // Populate readonly fields
    document.getElementById('payment_campus').value = campus;
    document.getElementById('payment_employee').value = employeeName;
    document.getElementById('payment_month').value = month;
    document.getElementById('payment_salary_generated').value = '₹' + parseFloat(salaryGenerated).toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    
    // Populate editable fields
    document.getElementById('payment_amount_paid').value = amountPaid || 0;
    document.getElementById('payment_loan_repayment').value = loanRepayment || 0;
    document.getElementById('payment_bonus_title').value = '';
    document.getElementById('payment_bonus_amount').value = 0;
    document.getElementById('payment_deduction_title').value = '';
    document.getElementById('payment_deduction_amount').value = 0;
    document.getElementById('payment_method').value = '';
    document.getElementById('payment_fully_paid').value = '0';
    document.getElementById('payment_date').value = '{{ date('Y-m-d') }}';
    document.getElementById('payment_notify_employee').value = '0';
    
    // Update form action
    document.getElementById('makePaymentForm').action = `/salary-loan/generate-salary/${salaryId}/payment`;
    
    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('makePaymentModal'));
    modal.show();
}

// Print Salary Slip
function printSalarySlip(salaryId) {
    // Open print slip in new window
    window.open(`/salary-loan/generate-salary/${salaryId}/print-slip`, '_blank');
}

// Delete Salary
function deleteSalary(salaryId) {
    if (confirm('Are you sure you want to delete this salary record? This action cannot be undone.')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/salary-loan/generate-salary/${salaryId}`;
        
        const csrfToken = document.createElement('input');
        csrfToken.type = 'hidden';
        csrfToken.name = '_token';
        csrfToken.value = '{{ csrf_token() }}';
        form.appendChild(csrfToken);
        
        const methodField = document.createElement('input');
        methodField.type = 'hidden';
        methodField.name = '_method';
        methodField.value = 'DELETE';
        form.appendChild(methodField);
        
        document.body.appendChild(form);
        form.submit();
    }
}

// Reset form
function resetForm() {
    document.getElementById('generateSalaryForm').reset();
    // Set current month and year as defaults
    const currentMonth = '{{ $currentMonth }}';
    const currentYear = '{{ $currentYear }}';
    document.getElementById('month').value = currentMonth;
    document.getElementById('year').value = currentYear;
    // Clear staff list
    document.getElementById('staff-list-container').innerHTML = `
        <div class="text-center text-muted py-5">
            <span class="material-symbols-outlined" style="font-size: 48px; opacity: 0.3;">people</span>
            <p class="mt-2 mb-0">Please select Campus, Month, and Year to load staff list.</p>
        </div>
    `;
    // Hide staff list card
    const staffListCard = document.getElementById('staff-list-card');
    if (staffListCard) {
        staffListCard.style.display = 'none';
    }
}
</script>
@endsection
