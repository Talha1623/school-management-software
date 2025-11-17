@extends('layouts.app')

@section('title', 'Fee Payment')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-4 mb-4">
            <h3 class="mb-4 fw-semibold" style="color: #003471;">Fee Payment</h3>
            
            <!-- Statistics Cards -->
            <div class="row g-3 mb-4">
                <!-- Unpaid Invoices Card -->
                <div class="col-md-6 col-lg-3">
                    <div class="stat-card" style="background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); border-radius: 12px; padding: 20px; position: relative; overflow: hidden; box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3); min-height: 140px; display: flex; flex-direction: column; justify-content: center;">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="stat-number text-white fw-bold mb-2" style="font-size: 36px; line-height: 1;">{{ $unpaidInvoices ?? 0 }}</div>
                                <div class="stat-label text-white fw-semibold" style="font-size: 16px;">Unpaid Invoices</div>
                            </div>
                            <div class="stat-icon" style="opacity: 0.3; position: absolute; right: 15px; top: 15px;">
                                <span class="material-symbols-outlined text-white" style="font-size: 64px;">receipt_long</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Income Today Card -->
                <div class="col-md-6 col-lg-3">
                    <div class="stat-card" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%); border-radius: 12px; padding: 20px; position: relative; overflow: hidden; box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3); min-height: 140px; display: flex; flex-direction: column; justify-content: center;">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="stat-number text-white fw-bold mb-2" style="font-size: 36px; line-height: 1;">{{ $incomeToday ?? 0 }}</div>
                                <div class="stat-label text-white fw-semibold" style="font-size: 16px;">Income Today</div>
                            </div>
                            <div class="stat-icon" style="opacity: 0.3; position: absolute; right: 15px; top: 15px;">
                                <span class="material-symbols-outlined text-white" style="font-size: 64px;">trending_up</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Expense Today Card -->
                <div class="col-md-6 col-lg-3">
                    <div class="stat-card" style="background: linear-gradient(135deg, #ff9800 0%, #f57c00 100%); border-radius: 12px; padding: 20px; position: relative; overflow: hidden; box-shadow: 0 4px 12px rgba(255, 152, 0, 0.3); min-height: 140px; display: flex; flex-direction: column; justify-content: center;">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="stat-number text-white fw-bold mb-2" style="font-size: 36px; line-height: 1;">{{ $expenseToday ?? 0 }}</div>
                                <div class="stat-label text-white fw-semibold" style="font-size: 16px;">Expense Today</div>
                            </div>
                            <div class="stat-icon" style="opacity: 0.3; position: absolute; right: 15px; top: 15px;">
                                <span class="material-symbols-outlined text-white" style="font-size: 64px;">trending_down</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Balance Today Card -->
                <div class="col-md-6 col-lg-3">
                    <div class="stat-card" style="background: linear-gradient(135deg, #2196f3 0%, #1976d2 100%); border-radius: 12px; padding: 20px; position: relative; overflow: hidden; box-shadow: 0 4px 12px rgba(33, 150, 243, 0.3); min-height: 140px; display: flex; flex-direction: column; justify-content: center;">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="stat-number text-white fw-bold mb-2" style="font-size: 36px; line-height: 1;">{{ $balanceToday ?? 0 }}</div>
                                <div class="stat-label text-white fw-semibold" style="font-size: 16px;">Balance Today</div>
                            </div>
                            <div class="stat-icon" style="opacity: 0.3; position: absolute; right: 15px; top: 15px;">
                                <span class="material-symbols-outlined text-white" style="font-size: 64px;">balance</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Search Panels -->
            <div class="row g-4 mb-4">
                <!-- Search Student By Name / Code -->
                <div class="col-md-6">
                    <div class="search-panel" style="border: 2px solid #003471; border-radius: 12px; padding: 0; background: white; box-shadow: 0 2px 8px rgba(0,0,0,0.1); overflow: hidden;">
                        <!-- Header -->
                        <div class="d-flex align-items-center gap-2" style="background: #003471; padding: 12px 18px; border-bottom: 2px solid #003471;">
                            <span class="material-symbols-outlined" style="font-size: 22px; color: #fff;">add</span>
                            <h5 class="mb-0 fw-bold" style="color: #fff; font-size: 15px;">Search Student By Name / Code</h5>
                        </div>
                        
                        <div style="padding: 20px;">
                            <!-- Search Input -->
                            <div class="input-group mb-4" style="height: 38px; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                                <span class="input-group-text" style="background-color: #f8f9fa; border: 1px solid #dee2e6; border-right: none; padding: 0 15px;">
                                    <span class="material-symbols-outlined" style="font-size: 20px; color: #6c757d;">person</span>
                                </span>
                                <input type="text" class="form-control" id="searchByName" placeholder="Type Student Name or Code... i.e Umer, Ali, ST40653" style="border: 1px solid #dee2e6; border-left: none; border-right: none; font-size: 14px; padding: 0 15px;">
                                <button class="btn" type="button" onclick="searchByName()" style="background: linear-gradient(135deg, #ff9800 0%, #f57c00 100%); color: white; border: 1px solid #ff9800; border-left: none; font-weight: 600; padding: 0 24px; white-space: nowrap;">
                                    Q Search
                                </button>
                            </div>
                            
                            <!-- Illustration -->
                            <div class="text-center mb-3" style="padding: 10px 0;">
                                <div style="width: 140px; height: 140px; background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; position: relative; box-shadow: 0 4px 15px rgba(220, 53, 69, 0.4);">
                                    <span class="material-symbols-outlined" style="font-size: 72px; color: white; opacity: 0.95;">qr_code_scanner</span>
                                </div>
                            </div>
                            
                            <!-- Description -->
                            <p class="text-center mb-0" style="color: #495057; font-size: 13px; font-weight: 500; line-height: 1.5;">
                                Scan Fee Slip For Quick Payment...!
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Search Student By Parent ID / CNIC -->
                <div class="col-md-6">
                    <div class="search-panel" style="border: 2px solid #003471; border-radius: 12px; padding: 0; background: white; box-shadow: 0 2px 8px rgba(0,0,0,0.1); overflow: hidden;">
                        <!-- Header -->
                        <div class="d-flex align-items-center gap-2" style="background: #003471; padding: 12px 18px; border-bottom: 2px solid #003471;">
                            <span class="material-symbols-outlined" style="font-size: 22px; color: #fff;">add</span>
                            <h5 class="mb-0 fw-bold" style="color: #fff; font-size: 15px;">Search Student By Parent ID / CNIC</h5>
                        </div>
                        
                        <div style="padding: 20px;">
                            <!-- Search Input -->
                            <div class="input-group mb-4" style="height: 38px; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                                <span class="input-group-text" style="background-color: #f8f9fa; border: 1px solid #dee2e6; border-right: none; padding: 0 15px;">
                                    <span class="material-symbols-outlined" style="font-size: 20px; color: #6c757d;">badge</span>
                                </span>
                                <input type="text" class="form-control" id="searchByCNIC" placeholder="Type Father's CNIC / Parent ID Here..." style="border: 1px solid #dee2e6; border-left: none; border-right: none; font-size: 14px; padding: 0 15px;">
                                <button class="btn" type="button" onclick="searchByCNIC()" style="background: linear-gradient(135deg, #ff9800 0%, #f57c00 100%); color: white; border: 1px solid #ff9800; border-left: none; font-weight: 600; padding: 0 24px; white-space: nowrap;">
                                    Q Search
                                </button>
                            </div>
                            
                            <!-- Illustration -->
                            <div class="text-center mb-3" style="padding: 10px 0;">
                                <div style="width: 140px; height: 140px; background: linear-gradient(135deg, #ffc107 0%, #ffb300 100%); border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; position: relative; box-shadow: 0 4px 15px rgba(255, 193, 7, 0.4);">
                                    <span class="material-symbols-outlined" style="font-size: 72px; color: white; opacity: 0.95;">credit_card</span>
                                </div>
                            </div>
                            
                            <!-- Description -->
                            <p class="text-center mb-0" style="color: #495057; font-size: 13px; font-weight: 500; line-height: 1.5;">
                                Type Father's CNIC / Parent ID To Filter All Connected Students...!
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Latest Payments Table -->
            <div class="mt-4">
                <div class="mb-2 p-2 rounded-8" style="background-color: #d4edda; border: 1px solid #c3e6cb;">
                    <h5 class="mb-0 fs-15 fw-semibold d-flex align-items-center gap-2" style="color: #155724;">
                        <span class="material-symbols-outlined" style="font-size: 18px; color: #000;">add</span>
                        <span style="color: #155724;">Latest Payments</span>
                    </h5>
                </div>

                <div class="default-table-area" style="margin-top: 0;">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead>
                                <tr>
                                    <th style="padding: 8px 12px; font-size: 13px;">Roll</th>
                                    <th style="padding: 8px 12px; font-size: 13px;">Student</th>
                                    <th style="padding: 8px 12px; font-size: 13px;">Parent</th>
                                    <th style="padding: 8px 12px; font-size: 13px;">Class</th>
                                    <th style="padding: 8px 12px; font-size: 13px;">Title</th>
                                    <th style="padding: 8px 12px; font-size: 13px;">Paid</th>
                                    <th style="padding: 8px 12px; font-size: 13px;">Late</th>
                                    <th style="padding: 8px 12px; font-size: 13px;">Date</th>
                                    <th style="padding: 8px 12px; font-size: 13px;">Accountant</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if(isset($latestPayments) && $latestPayments->count() > 0)
                                    @foreach($latestPayments as $payment)
                                        <tr>
                                            <td style="padding: 8px 12px; font-size: 13px;">
                                                <strong>{{ $payment->student_code }}</strong>
                                            </td>
                                            <td style="padding: 8px 12px; font-size: 13px;">
                                                {{ $payment->student_name ?? 'N/A' }}
                                            </td>
                                            <td style="padding: 8px 12px; font-size: 13px;">
                                                {{ $payment->father_name ?? 'N/A' }}
                                            </td>
                                            <td style="padding: 8px 12px; font-size: 13px;">
                                                @if($payment->class && $payment->section)
                                                    {{ $payment->class }}/{{ $payment->section }}
                                                @elseif($payment->class)
                                                    {{ $payment->class }}
                                                @else
                                                    N/A
                                                @endif
                                            </td>
                                            <td style="padding: 8px 12px; font-size: 13px;">
                                                {{ $payment->payment_title }}
                                            </td>
                                            <td style="padding: 8px 12px; font-size: 13px;">
                                                <strong style="color: #28a745;">{{ number_format($payment->payment_amount, 2) }}</strong>
                                            </td>
                                            <td style="padding: 8px 12px; font-size: 13px; text-align: center;">
                                                @if($payment->late_fee && $payment->late_fee > 0)
                                                    <span style="display: inline-block; width: 12px; height: 12px; background-color: #dc3545; border-radius: 50%;"></span>
                                                @else
                                                    <span style="color: #6c757d;">-</span>
                                                @endif
                                            </td>
                                            <td style="padding: 8px 12px; font-size: 13px;">
                                                {{ $payment->payment_date ? $payment->payment_date->format('d-m-Y h:i:s A') : 'N/A' }}
                                            </td>
                                            <td style="padding: 8px 12px; font-size: 13px;">
                                                {{ $payment->accountant ?? 'N/A' }}
                                            </td>
                                        </tr>
                                    @endforeach
                                @else
                                    <tr>
                                        <td colspan="9" class="text-center py-4">
                                            <div class="d-flex flex-column align-items-center gap-2">
                                                <span class="material-symbols-outlined" style="font-size: 48px; color: #dee2e6;">payments</span>
                                                <p class="text-muted mb-0">No payments found.</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .stat-card {
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    
    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.2) !important;
    }
    
    .search-panel {
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    
    .search-panel:hover {
        transform: translateY(-3px);
        box-shadow: 0 6px 16px rgba(0, 0, 0, 0.15) !important;
    }
    
    .input-group {
        border-radius: 8px;
    }
    
    .input-group-text {
        border-radius: 8px 0 0 8px;
    }
    
    .input-group .btn {
        border-radius: 0 8px 8px 0;
    }
    
    .form-control:focus {
        border-color: #ff9800;
        box-shadow: none;
        outline: none;
    }
    
    .input-group:focus-within {
        box-shadow: 0 0 0 3px rgba(255, 152, 0, 0.2) !important;
        border-radius: 8px;
    }
    
    .btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(255, 152, 0, 0.4);
    }
    
    .btn:active {
        transform: translateY(0);
    }
    
    /* Latest Payments Table Styling */
    .default-table-area table {
        margin-bottom: 0;
        border-spacing: 0;
        border-collapse: collapse;
        border: 1px solid #dee2e6;
    }
    
    .default-table-area table thead {
        border-bottom: 2px solid #dee2e6;
        background-color: #f8f9fa;
    }
    
    .default-table-area table thead th {
        padding: 10px 12px;
        font-size: 13px;
        font-weight: 600;
        vertical-align: middle;
        line-height: 1.4;
        border: 1px solid #dee2e6;
        color: #495057;
    }
    
    .default-table-area table tbody td {
        padding: 10px 12px;
        font-size: 13px;
        vertical-align: middle;
        line-height: 1.4;
        border: 1px solid #dee2e6;
    }
    
    .default-table-area table tbody tr:hover {
        background-color: #f8f9fa;
    }
    
    .default-table-area table tbody tr:hover td {
        background-color: #f8f9fa;
    }
</style>

<script>
function searchByName() {
    const searchValue = document.getElementById('searchByName').value.trim();
    if (searchValue) {
        // TODO: Implement search functionality
        console.log('Searching by name/code:', searchValue);
        // You can add AJAX call or form submission here
    } else {
        alert('Please enter student name or code');
    }
}

function searchByCNIC() {
    const searchValue = document.getElementById('searchByCNIC').value.trim();
    if (searchValue) {
        // TODO: Implement search functionality
        console.log('Searching by CNIC/Parent ID:', searchValue);
        // You can add AJAX call or form submission here
    } else {
        alert('Please enter Father\'s CNIC or Parent ID');
    }
}

// Allow Enter key to trigger search
document.getElementById('searchByName')?.addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        searchByName();
    }
});

document.getElementById('searchByCNIC')?.addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        searchByCNIC();
    }
});
</script>
@endsection

