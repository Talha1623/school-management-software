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

            <form action="{{ route('salary-loan.generate-salary.store') }}" method="POST" id="generateSalaryForm">
                @csrf
                
                <div class="row g-3">
                    <!-- Campus -->
                    <div class="col-md-6">
                        <div class="card bg-light border-0 rounded-10 p-3 mb-2">
                            <h5 class="mb-2 py-2 px-3 text-white rounded-3 fw-semibold fs-15" style="margin: -12px -12px 12px -12px; background-color: #003471;">Campus</h5>
                            
                            <label for="campus" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Campus <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm salary-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">location_on</span>
                                </span>
                                <select class="form-control salary-input" name="campus" id="campus" required style="border: none; border-left: 1px solid #e0e7ff; border-radius: 0 8px 8px 0; height: 36px;">
                                    <option value="">Select Campus</option>
                                    @if(isset($campuses) && $campuses->count() > 0)
                                        @foreach($campuses as $campus)
                                            <option value="{{ $campus->campus_name ?? $campus }}">{{ $campus->campus_name ?? $campus }}</option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Month -->
                    <div class="col-md-6">
                        <div class="card bg-light border-0 rounded-10 p-3 mb-2">
                            <h5 class="mb-2 py-2 px-3 text-white rounded-3 fw-semibold fs-15" style="margin: -12px -12px 12px -12px; background-color: #003471;">Month</h5>
                            
                            <label for="month" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Month <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm salary-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">calendar_month</span>
                                </span>
                                <select class="form-control salary-input" name="month" id="month" required style="border: none; border-left: 1px solid #e0e7ff; border-radius: 0 8px 8px 0; height: 36px;">
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
                    </div>

                    <!-- Year -->
                    <div class="col-md-6">
                        <div class="card bg-light border-0 rounded-10 p-3 mb-2">
                            <h5 class="mb-2 py-2 px-3 text-white rounded-3 fw-semibold fs-15" style="margin: -12px -12px 12px -12px; background-color: #003471;">Year</h5>
                            
                            <label for="year" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Year <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm salary-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">calendar_today</span>
                                </span>
                                <select class="form-control salary-input" name="year" id="year" required style="border: none; border-left: 1px solid #e0e7ff; border-radius: 0 8px 8px 0; height: 36px;">
                                    <option value="">Select Year</option>
                                    @for($y = date('Y'); $y >= date('Y') - 5; $y--)
                                        <option value="{{ $y }}" {{ $currentYear == $y ? 'selected' : '' }}>{{ $y }}</option>
                                    @endfor
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Deduction Per Late Arrival -->
                    <div class="col-md-6">
                        <div class="card bg-light border-0 rounded-10 p-3 mb-2">
                            <h5 class="mb-2 py-2 px-3 text-white rounded-3 fw-semibold fs-15" style="margin: -12px -12px 12px -12px; background-color: #003471;">Deduction Per Late Arrival</h5>
                            
                            <label for="deduction_per_late_arrival" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Deduction Per Late Arrival</label>
                            <div class="input-group input-group-sm salary-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">currency_rupee</span>
                                </span>
                                <input type="number" class="form-control salary-input" name="deduction_per_late_arrival" id="deduction_per_late_arrival" placeholder="Enter deduction amount" step="0.01" min="0" style="height: 36px;">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Generate Salary Button -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-sm py-2 px-4 rounded-8" onclick="resetForm()" style="background-color: #6c757d; color: white; border: none; transition: all 0.3s ease;">
                                <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">refresh</span>
                                Reset
                            </button>
                            <button type="submit" class="btn btn-sm py-2 px-4 rounded-8 generate-salary-btn">
                                <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">payments</span>
                                Generate Salary
                            </button>
                        </div>
                    </div>
                </div>
            </form>

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
                                        <th class="text-end">Action</th>
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
                                            <strong class="text-primary">₹{{ number_format($salary->basic ?? 0, 2) }}</strong>
                                        </td>
                                        <td class="text-end">
                                            <strong class="text-success">₹{{ number_format($salary->salary_generated ?? 0, 2) }}</strong>
                                        </td>
                                        <td class="text-end">
                                            <strong class="text-info">₹{{ number_format($salary->amount_paid ?? 0, 2) }}</strong>
                                        </td>
                                        <td class="text-end">
                                            <strong class="text-warning">₹{{ number_format($salary->loan_repayment ?? 0, 2) }}</strong>
                                        </td>
                                        <td>
                                            @if($salary->status == 'Pending')
                                                <span class="badge bg-warning text-dark" style="font-size: 11px;">Pending</span>
                                            @elseif($salary->status == 'Paid')
                                                <span class="badge bg-success text-white" style="font-size: 11px;">Paid</span>
                                            @else
                                                <span class="badge bg-info text-white" style="font-size: 11px;">Partial</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            <a href="{{ route('salary-loan.manage-salaries') }}?search={{ $salary->staff->name ?? '' }}" class="btn btn-sm btn-primary px-2 py-1" title="View/Edit Payment">
                                                <span class="material-symbols-outlined" style="font-size: 14px; color: white;">payments</span>
                                            </a>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                @if($generatedSalaries->count() > 0)
                                <tfoot>
                                    <tr style="background-color: #f8f9fa; font-weight: 600;">
                                        <td colspan="8" class="text-end"><strong>Total:</strong></td>
                                        <td class="text-end"><strong class="text-primary">₹{{ number_format($generatedSalaries->sum('basic'), 2) }}</strong></td>
                                        <td class="text-end"><strong class="text-success">₹{{ number_format($generatedSalaries->sum('salary_generated'), 2) }}</strong></td>
                                        <td class="text-end"><strong class="text-info">₹{{ number_format($generatedSalaries->sum('amount_paid'), 2) }}</strong></td>
                                        <td class="text-end"><strong class="text-warning">₹{{ number_format($generatedSalaries->sum('loan_repayment'), 2) }}</strong></td>
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

<style>
    /* Salary Form Styling */
    .salary-input-group {
        height: 36px;
        border-radius: 8px;
        overflow: hidden;
        transition: all 0.3s ease;
        border: 1px solid #dee2e6;
    }
    
    .salary-input-group:focus-within {
        box-shadow: 0 0 0 3px rgba(0, 52, 113, 0.15);
        border-color: #003471;
    }
    
    .salary-input {
        height: 36px;
        font-size: 13px;
        padding: 0.5rem 0.75rem;
        border: none;
        border-left: 1px solid #e0e7ff;
        border-radius: 0 8px 8px 0;
        transition: all 0.3s ease;
    }
    
    .salary-input:focus {
        border-left-color: #003471;
        box-shadow: none;
        outline: none;
    }
    
    .salary-input-group .input-group-text {
        height: 36px;
        padding: 0 0.75rem;
        display: flex;
        align-items: center;
        border: none;
        border-right: 1px solid #e0e7ff;
        border-radius: 8px 0 0 8px;
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
</style>

<script>
// Reset form
function resetForm() {
    document.getElementById('generateSalaryForm').reset();
    // Set current month and year as defaults
    const currentMonth = '{{ $currentMonth }}';
    const currentYear = '{{ $currentYear }}';
    document.getElementById('month').value = currentMonth;
    document.getElementById('year').value = currentYear;
}
</script>
@endsection
