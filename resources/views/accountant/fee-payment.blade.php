@extends('layouts.accountant')

@section('title', 'Fee Payment - Accountant')

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
                                <div class="stat-number text-white fw-bold mb-2" style="font-size: 36px; line-height: 1;">{{ number_format($incomeToday ?? 0, 2) }}</div>
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
                                <div class="stat-number text-white fw-bold mb-2" style="font-size: 36px; line-height: 1;">{{ number_format($expenseToday ?? 0, 2) }}</div>
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
                                <div class="stat-number text-white fw-bold mb-2" style="font-size: 36px; line-height: 1;">{{ number_format($balanceToday ?? 0, 2) }}</div>
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

            <!-- Search Results Section -->
            <div id="searchResultsSection" class="mt-4" style="display: none;">
                <div class="mb-2 p-2 rounded-8" style="background-color: #fff3cd; border: 1px solid #ffc107;">
                    <h5 class="mb-0 fs-15 fw-semibold d-flex align-items-center gap-2" style="color: #856404;">
                        <span class="material-symbols-outlined" style="font-size: 18px;">search</span>
                        <span style="color: #856404;">Search Results</span>
                        <button type="button" class="btn btn-sm btn-outline-secondary ms-auto" onclick="closeSearchResults()" style="padding: 2px 10px; font-size: 12px;">
                            <span class="material-symbols-outlined" style="font-size: 16px;">close</span> Close
                        </button>
                    </h5>
                </div>

                <div class="default-table-area" style="margin-top: 0;">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead>
                                <tr>
                                    <th style="padding: 8px 12px; font-size: 13px;">Student Code</th>
                                    <th style="padding: 8px 12px; font-size: 13px;">Student</th>
                                    <th style="padding: 8px 12px; font-size: 13px;">Parent</th>
                                    <th style="padding: 8px 12px; font-size: 13px;">Fee Title</th>
                                    <th style="padding: 8px 12px; font-size: 13px;">Total</th>
                                    <th style="padding: 8px 12px; font-size: 13px;">Dis</th>
                                    <th style="padding: 8px 12px; font-size: 13px;">Late Fee</th>
                                    <th style="padding: 8px 12px; font-size: 13px;">Paid</th>
                                    <th style="padding: 8px 12px; font-size: 13px;">Due</th>
                                    <th style="padding: 8px 12px; font-size: 13px;">Actions</th>
                                    <th style="padding: 8px 12px; font-size: 13px;">More</th>
                                </tr>
                            </thead>
                            <tbody id="searchResultsBody">
                                <!-- Search results will be populated here -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Latest Payments Table -->
            <div class="mt-4" id="latestPaymentsSection">
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
                                                <strong style="color: #28a745;">{{ number_format($payment->payment_amount ?? 0, 2) }}</strong>
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

<!-- Partial Payment Modal -->
<div class="modal fade" id="partialPaymentModal" tabindex="-1" aria-labelledby="partialPaymentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" style="border-radius: 12px; border: none; box-shadow: 0 10px 40px rgba(0,0,0,0.2);">
            <div class="modal-header" style="background: linear-gradient(135deg, #003471 0%, #004a9f 100%); border-radius: 12px 12px 0 0; border: none; padding: 20px;">
                <h5 class="modal-title fs-15 fw-semibold mb-0 d-flex align-items-center gap-2" id="partialPaymentModalLabel" style="color: white !important;">
                    <span class="material-symbols-outlined" style="font-size: 20px; color: white !important;">account_balance_wallet</span>
                    <span style="color: white !important;">Partial Payment</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="partialPaymentForm" method="POST" action="{{ route('accounting.direct-payment.student.store') }}" onsubmit="handlePartialPaymentSubmit(event)">
                @csrf
                <div class="modal-body p-4" style="background-color: #f8f9fa;">
                    <div class="row g-3">
                        <!-- Campus -->
                        <div class="col-md-6">
                            <label class="form-label mb-1 fw-semibold" style="color: #003471; font-size: 13px;">Campus</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 16px;">home</span>
                                </span>
                                <input type="text" class="form-control" id="partial_campus" name="campus" readonly style="background-color: #f8f9fa; cursor: not-allowed;">
                            </div>
                        </div>

                        <!-- Student -->
                        <div class="col-md-6">
                            <label class="form-label mb-1 fw-semibold" style="color: #003471; font-size: 13px;">Student</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 16px;">person</span>
                                </span>
                                <input type="text" class="form-control" id="partial_student" readonly style="background-color: #f8f9fa; cursor: not-allowed;">
                                <input type="hidden" id="partial_student_code" name="student_code">
                                <input type="hidden" id="partial_generated_id" name="generated_id">
                            </div>
                        </div>

                        <!-- Fee Title -->
                        <div class="col-md-6">
                            <label class="form-label mb-1 fw-semibold" style="color: #003471; font-size: 13px;">Fee Title</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 16px;">receipt</span>
                                </span>
                                <input type="text" class="form-control" id="partial_fee_title" name="payment_title" placeholder="Enter Fee Title" required>
                            </div>
                        </div>

                        <!-- Due Amount -->
                        <div class="col-md-6">
                            <label class="form-label mb-1 fw-semibold" style="color: #003471; font-size: 13px;">Due Amount</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 16px;">attach_money</span>
                                </span>
                                <input type="text" class="form-control" id="partial_due_amount" readonly style="background-color: #f8f9fa; cursor: not-allowed;">
                            </div>
                        </div>

                        <!-- Payment -->
                        <div class="col-md-6">
                            <label class="form-label mb-1 fw-semibold" style="color: #003471; font-size: 13px;">Payment</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 16px;">payments</span>
                                </span>
                                <input type="number" step="0.01" min="0" class="form-control" id="partial_payment" name="payment_amount" placeholder="Enter Payment Amount" required>
                            </div>
                        </div>

                        <!-- Discount -->
                        <div class="col-md-6">
                            <label class="form-label mb-1 fw-semibold" style="color: #003471; font-size: 13px;">Discount</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 16px;">remove</span>
                                </span>
                                <input type="number" step="0.01" min="0" class="form-control" id="partial_discount" name="discount" placeholder="0" value="0">
                            </div>
                        </div>

                        <!-- Method -->
                        <div class="col-md-6">
                            <label class="form-label mb-1 fw-semibold" style="color: #003471; font-size: 13px;">Method</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 16px;">account_balance_wallet</span>
                                </span>
                                <select class="form-select" id="partial_method" name="method" required>
                                    <option value="">Select Method</option>
                                    <option value="Cash Payment" selected>Cash Payment</option>
                                    <option value="Bank Transfer">Bank Transfer</option>
                                    <option value="Cheque">Cheque</option>
                                    <option value="Online Payment">Online Payment</option>
                                    <option value="Card Payment">Card Payment</option>
                                </select>
                            </div>
                        </div>

                        <!-- Date -->
                        <div class="col-md-6">
                            <label class="form-label mb-1 fw-semibold" style="color: #003471; font-size: 13px;">Date</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 16px;">calendar_today</span>
                                </span>
                                <input type="date" class="form-control" id="partial_date" name="payment_date" value="{{ date('Y-m-d') }}" required>
                            </div>
                        </div>

                        <!-- SMS Notification -->
                        <div class="col-md-6">
                            <label class="form-label mb-1 fs-13 fw-medium">SMS Notification</label>
                            <div class="input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff;">
                                    <span class="material-symbols-outlined" style="font-size: 18px; color: #003471;">sms</span>
                                </span>
                                <select class="form-select form-select-sm" name="sms_notification" id="partial_notify" required style="height: 38px;">
                                    <option value="Yes" selected>Yes</option>
                                    <option value="No">No</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer" style="border-top: 1px solid #dee2e6; padding: 15px 20px;">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="padding: 8px 20px;">Cancel</button>
                    <button type="submit" class="btn btn-primary" style="background-color: #003471; border: none; padding: 8px 20px;">
                        <span class="material-symbols-outlined" style="font-size: 18px; vertical-align: middle; color: white;">check</span>
                        <span style="color: white;">Submit Payment</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Fee Breakdown Modal -->
<div class="modal fade" id="feeBreakdownModal" tabindex="-1" aria-labelledby="feeBreakdownModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" style="border-radius: 12px; border: none; box-shadow: 0 10px 40px rgba(0,0,0,0.2);">
            <div class="modal-header" style="background: linear-gradient(135deg, #003471 0%, #004a9f 100%); border-radius: 12px 12px 0 0; border: none; padding: 18px 20px;">
                <h5 class="modal-title fs-15 fw-semibold mb-0 d-flex align-items-center gap-2" id="feeBreakdownModalLabel" style="color: white !important;">
                    <span class="material-symbols-outlined" style="font-size: 20px; color: white !important;">receipt_long</span>
                    <span style="color: white !important;">Fee Breakdown</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4" style="background-color: #f8f9fa;">
                <div class="mb-3">
                    <strong id="feeBreakdownStudent" style="color: #003471;"></strong>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead>
                            <tr>
                                <th>Fee Title</th>
                                <th class="text-end">Amount</th>
                                <th class="text-end">Late Fee</th>
                                <th class="text-end">Total</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody id="feeBreakdownBody">
                            <tr><td colspan="5" class="text-center text-muted">No pending fees.</td></tr>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="3" class="text-end">Total Due</th>
                                <th class="text-end" id="feeBreakdownTotal">0.00</th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
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
        
    /* Dropdown Button Styling */
    .btn-group {
        position: static;
    }
    
    .btn-group .dropdown-toggle::after {
        margin-left: 5px;
    }
    
    .dropdown-menu {
        min-width: 200px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 4px 0;
        z-index: 1050 !important;
        position: absolute !important;
    }
    
    .dropdown-item {
        padding: 8px 16px;
        font-size: 13px;
        display: flex;
        align-items: center;
        transition: background-color 0.2s ease;
    }
    
    .dropdown-item:hover {
        background-color: #f8f9fa;
        color: #003471;
    }
    
    .dropdown-item .material-symbols-outlined {
        margin-right: 8px;
        font-size: 18px;
    }
    
    .dropdown-divider {
        margin: 4px 0;
    }
    
    /* Fix table overflow for dropdown */
    .table-responsive {
        overflow: visible !important;
    }
    
    .default-table-area {
        overflow: visible !important;
    }
    
    /* Ensure dropdown appears above table */
    table tbody td {
        position: relative;
        overflow: visible;
    }
</style>

<script>
function searchByName(onComplete) {
    const searchValue = document.getElementById('searchByName').value.trim();
    if (!searchValue) {
        alert('Please enter student name or code');
        return;
    }
    window.lastFeeSearch = { type: 'name', value: searchValue };

    // Show loading state
    const searchResultsSection = document.getElementById('searchResultsSection');
    const searchResultsBody = document.getElementById('searchResultsBody');
    const latestPaymentsSection = document.getElementById('latestPaymentsSection');
    
    searchResultsSection.style.display = 'block';
    latestPaymentsSection.style.display = 'none';
    searchResultsBody.innerHTML = '<tr><td colspan="7" class="text-center py-4"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div><p class="mt-2 text-muted">Searching...</p></td></tr>';

    // Make AJAX call to search students
    fetch(`{{ route('fee-payment.search-student') }}?search=${encodeURIComponent(searchValue)}`, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
        }
    })
    .then(response => response.json())
    .then(data => {
        searchResultsBody.innerHTML = '';
        
        if (data.success && data.students && data.students.length > 0) {
            let totals = { total: 0, discount: 0, late: 0, paid: 0, due: 0 };
            data.students.forEach((student) => {
                const feeRows = (student.fee_rows && student.fee_rows.length > 0) ? student.fee_rows : [{
                    title: 'N/A',
                    total: 0,
                    discount: 0,
                    late_fee: 0,
                    paid: 0,
                    due: 0,
                    amount: 0,
                    remaining_late: 0,
                }];

                feeRows.forEach((fee, index) => {
                    const row = document.createElement('tr');
                    const feeTitleSafe = (fee.title || '').replace(/'/g, "\\'");
                    const studentNameSafe = (student.student_name || '').replace(/'/g, "\\'");
                    const campusSafe = (student.campus || '').replace(/'/g, "\\'");
                    const dueAmount = parseFloat(fee.due || 0);
                    const feeAmount = parseFloat(fee.amount || 0);
                    const generatedId = fee.generated_id || null;
                    const generatedId = fee.generated_id || null;
                    const lateAmount = parseFloat(fee.remaining_late || 0);
                    totals.total += parseFloat(fee.total || 0);
                    totals.discount += parseFloat(fee.discount || 0);
                    totals.late += parseFloat(fee.late_fee || 0);
                    totals.paid += parseFloat(fee.paid || 0);
                    totals.due += dueAmount;
                    row.innerHTML = `
                        <td style="padding: 8px 12px; font-size: 13px;">
                            <strong>${student.student_code || 'N/A'}</strong>
                        </td>
                        <td style="padding: 8px 12px; font-size: 13px;">
                            ${student.student_name || 'N/A'}
                        </td>
                        <td style="padding: 8px 12px; font-size: 13px;">
                            ${student.father_name || 'N/A'}
                        </td>
                        <td style="padding: 8px 12px; font-size: 13px;">
                            ${fee.title || 'N/A'}
                        </td>
                        <td style="padding: 8px 12px; font-size: 13px;">
                            ${parseFloat(fee.total || 0).toFixed(2)}
                        </td>
                        <td style="padding: 8px 12px; font-size: 13px;">
                            ${parseFloat(fee.discount || 0).toFixed(2)}
                        </td>
                        <td style="padding: 8px 12px; font-size: 13px;">
                            ${parseFloat(fee.late_fee || 0).toFixed(2)}
                        </td>
                        <td style="padding: 8px 12px; font-size: 13px;">
                            ${parseFloat(fee.paid || 0).toFixed(2)}
                        </td>
                        <td style="padding: 8px 12px; font-size: 13px;">
                            ${dueAmount.toFixed(2)}
                        </td>
                        <td style="padding: 8px 12px; font-size: 13px; position: relative; overflow: visible;">
                            <div class="d-flex gap-2 flex-wrap" style="position: relative;">
                                ${dueAmount > 0 ? `
                                    <button class="btn btn-sm btn-danger" onclick="openFeePaymentModal('${student.student_code}', '${studentNameSafe}', '${campusSafe}', '${feeTitleSafe}', ${dueAmount.toFixed(2)}, ${generatedId ? generatedId : 'null'})" style="padding: 4px 12px; font-size: 12px; color: white !important;">
                                        <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle; color: white;">warning</span>
                                        <span style="color: white;">Unpaid</span>
                                    </button>
                                ` : `
                                    <button class="btn btn-sm btn-success" style="padding: 4px 12px; font-size: 12px; color: white !important;">
                                        <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle; color: white;">check_circle</span>
                                        <span style="color: white;">Paid</span>
                                    </button>
                                `}
                                <div class="btn-group" style="position: static;">
                                    <button type="button" class="btn btn-sm btn-primary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" style="padding: 4px 12px; font-size: 12px; color: white !important;">
                                        <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle; color: white;">payments</span>
                                        <span style="color: white;">Take Payment</span>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end" style="position: absolute; z-index: 1050;">
                                        <li><a class="dropdown-item" href="#" onclick="openFeePaymentModal('${student.student_code}', '${studentNameSafe}', '${campusSafe}', '${feeTitleSafe}', ${dueAmount.toFixed(2)}, ${generatedId ? generatedId : 'null'}); return false;">
                                            <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle; margin-right: 5px;">check_circle</span>
                                            Pay This Fee
                                        </a></li>
                                        <li><a class="dropdown-item" href="#" onclick="openFeePaymentModal('${student.student_code}', '${studentNameSafe}', '${campusSafe}', '${feeTitleSafe}', ${feeAmount.toFixed(2)}, ${generatedId ? generatedId : 'null'}); return false;">
                                            <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle; margin-right: 5px;">remove_circle</span>
                                            Pay without late fee
                                        </a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item" href="#" onclick="takePayment('${student.student_code}', '${studentNameSafe}', 'full'); return false;">
                                            <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle; margin-right: 5px;">payments</span>
                                            Pay All
                                        </a></li>
                                    </ul>
                                </div>
                            </div>
                        </td>
                        <td style="padding: 8px 12px; font-size: 13px; position: relative; overflow: visible;">
                            <div class="d-flex gap-2 flex-wrap align-items-center" style="position: relative;">
                                <button class="btn btn-sm btn-warning" onclick="editStudent(${student.id}, '${student.student_code}', '${studentNameSafe}')" style="padding: 4px 10px; font-size: 12px; color: white !important;" title="Edit Student">
                                    <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle; color: white;">edit</span>
                                </button>
                                <div class="btn-group" style="position: static;">
                                    <button type="button" class="btn btn-sm btn-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" style="padding: 4px 10px; font-size: 12px; color: white !important;" title="More Options">
                                        <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle; color: white;">arrow_drop_down</span>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end" style="position: absolute; z-index: 1050;">
                                        <li><a class="dropdown-item" href="#" onclick="payAll('${student.student_code}', '${studentNameSafe}'); return false;">
                                            <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle; margin-right: 5px;">payments</span>
                                            Pay All
                                        </a></li>
                                        <li><a class="dropdown-item" href="#" onclick="printVoucher('${student.student_code}', '${studentNameSafe}'); return false;">
                                            <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle; margin-right: 5px;">print</span>
                                            Print Voucher
                                        </a></li>
                                        <li><a class="dropdown-item" href="#" onclick="makeInstallment('${student.student_code}', '${studentNameSafe}'); return false;">
                                            <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle; margin-right: 5px;">schedule</span>
                                            Make Installment
                                        </a></li>
                                        <li><a class="dropdown-item" href="#" onclick="particularReceipt('${student.student_code}', '${studentNameSafe}'); return false;">
                                            <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle; margin-right: 5px;">receipt</span>
                                            Particular Receipt
                                        </a></li>
                                    </ul>
                                </div>
                            </div>
                        </td>
                    `;
                    searchResultsBody.appendChild(row);
                });
            });

            const totalRow = document.createElement('tr');
            totalRow.innerHTML = `
                <td colspan="4" class="text-end fw-semibold">Total</td>
                <td class="fw-semibold">${totals.total.toFixed(2)}</td>
                <td class="fw-semibold">${totals.discount.toFixed(2)}</td>
                <td class="fw-semibold">${totals.late.toFixed(2)}</td>
                <td class="fw-semibold">${totals.paid.toFixed(2)}</td>
                <td class="fw-semibold">${totals.due.toFixed(2)}</td>
                <td colspan="2"></td>
            `;
            searchResultsBody.appendChild(totalRow);
        } else {
            searchResultsBody.innerHTML = '<tr><td colspan="8" class="text-center py-4 text-muted">No students found matching your search.</td></tr>';
        }
        if (typeof onComplete === 'function') {
            onComplete();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        searchResultsBody.innerHTML = '<tr><td colspan="7" class="text-center py-4 text-danger">An error occurred while searching. Please try again.</td></tr>';
        if (typeof onComplete === 'function') {
            onComplete();
        }
    });
}

function closeSearchResults() {
    document.getElementById('searchResultsSection').style.display = 'none';
    document.getElementById('latestPaymentsSection').style.display = 'block';
    document.getElementById('searchByName').value = '';
}

let currentFeeStudent = null;

function getFeeTypeLabel(title) {
    const normalized = (title || '').toLowerCase();
    if (normalized.includes('transport fee')) {
        return 'Transport Fee';
    }
    if (normalized.includes('admission fee')) {
        return 'Admission Fee';
    }
    if (normalized.includes('monthly fee')) {
        return 'Monthly Fee';
    }
    return 'Title Fee';
}

function viewUnpaid(studentCode, studentName, encodedFees, unpaidAmount, campus) {
    currentFeeStudent = {
        student_code: studentCode,
        student_name: studentName,
        campus: campus || ''
    };

    let fees = [];
    try {
        fees = JSON.parse(decodeURIComponent(encodedFees || '')) || [];
    } catch (e) {
        fees = [];
    }

    const searchResultsBody = document.getElementById('searchResultsBody');
    const existingRow = searchResultsBody.querySelector(`tr.fee-breakdown-row[data-student-code="${studentCode}"]`);
    if (existingRow) {
        existingRow.remove();
        return;
    }

    // Remove any other open breakdown rows
    searchResultsBody.querySelectorAll('tr.fee-breakdown-row').forEach(row => row.remove());

    const detailRow = document.createElement('tr');
    detailRow.className = 'fee-breakdown-row';
    detailRow.dataset.studentCode = studentCode;

    let rowsHtml = '';
    let totalDue = 0;
    if (fees.length === 0) {
        rowsHtml = '<tr><td colspan="5" class="text-center text-muted">No pending fees.</td></tr>';
    } else {
        rowsHtml = fees.map(fee => {
            const amount = parseFloat(fee.amount || 0);
            const lateFee = parseFloat(fee.late_fee || 0);
            const total = parseFloat(fee.total || 0);
            totalDue += total;
            const safeTitle = (fee.title || '').replace(/'/g, "\\'");
            return `
                <tr>
                    <td>${getFeeTypeLabel(fee.title)}</td>
                    <td>${fee.title || 'N/A'}</td>
                    <td class="text-end">${amount.toFixed(2)}</td>
                    <td class="text-end">${lateFee.toFixed(2)}</td>
                    <td class="text-end fw-semibold">${total.toFixed(2)}</td>
                    <td class="text-end">
                        <button type="button" class="btn btn-sm btn-primary" onclick="openPartialPaymentFromBreakdown('${safeTitle}', ${total.toFixed(2)})">
                            Pay
                        </button>
                    </td>
                </tr>
            `;
        }).join('');
    }

    detailRow.innerHTML = `
        <td colspan="8" style="padding: 0;">
            <div style="padding: 12px 16px; background: #f8f9fa; border: 1px solid #e9ecef;">
                <div class="mb-2 fw-semibold" style="color: #003471;">${studentName} (${studentCode})</div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Type</th>
                                <th>Fee Title</th>
                                <th class="text-end">Amount</th>
                                <th class="text-end">Late Fee</th>
                                <th class="text-end">Total</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${rowsHtml}
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="4" class="text-end">Total Due</th>
                                <th class="text-end">${(fees.length ? totalDue : 0).toFixed(2)}</th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </td>
    `;

    const targetRow = Array.from(searchResultsBody.querySelectorAll('tr')).find(row => {
        const codeCell = row.querySelector('td:first-child strong');
        return codeCell && codeCell.textContent.trim() === studentCode;
    });
    if (targetRow) {
        targetRow.insertAdjacentElement('afterend', detailRow);
    }
}

function takePayment(studentCode, studentName, paymentType = 'full', studentData = null) {
    // If partial payment, show modal
    if (paymentType === 'partial') {
        // Fetch student data if not provided
        if (!studentData) {
            fetch(`{{ route('fee-payment.search-student') }}?search=${encodeURIComponent(studentCode)}`, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.students && data.students.length > 0) {
                    const student = data.students[0];
                    openPartialPaymentModal(studentCode, studentName, student);
                } else {
                    alert('Student not found');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error loading student data');
            });
        } else {
            openPartialPaymentModal(studentCode, studentName, studentData);
        }
        return;
    }
    
    // If full payment, create payment automatically
    if (paymentType === 'full') {
        // Confirm before processing
        if (!confirm(`Are you sure you want to process full payment for ${studentName} (${studentCode})?`)) {
            return;
        }
        
        // Show loading
        const loadingMsg = document.createElement('div');
        loadingMsg.id = 'payment-loading';
        loadingMsg.style.cssText = 'position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.3); z-index: 9999;';
        loadingMsg.innerHTML = '<div class="spinner-border text-primary" role="status"></div><p class="mt-2 mb-0">Processing payment...</p>';
        document.body.appendChild(loadingMsg);
        
        // Make AJAX call to create full payment
        fetch('{{ route("fee-payment.full-payment") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                student_code: studentCode
            })
        })
        .then(response => response.json())
        .then(data => {
            document.body.removeChild(loadingMsg);
            
            if (data.success) {
                // Update button status from Unpaid to Paid
                updatePaymentStatus(studentCode, false);
                alert('Payment recorded successfully!');
                // Reload page to update history
                window.location.reload();
            } else {
                alert(data.message || 'Error processing payment');
            }
        })
        .catch(error => {
            document.body.removeChild(loadingMsg);
            console.error('Error:', error);
            alert('Error processing payment. Please try again.');
        });
        return;
    }
    
    // For without late fee, redirect to payment page
    if (paymentType === 'without_late_fee') {
        let url = '{{ route("accounting.direct-payment.student") }}?student_code=' + studentCode + '&payment_type=without_late_fee';
        window.location.href = url;
        return;
    }
}

function openPartialPaymentModal(studentCode, studentName, studentData, feeTitle = '', feeAmount = null, generatedId = null) {
    // Populate modal fields
    document.getElementById('partial_campus').value = studentData.campus || 'N/A';
    document.getElementById('partial_student').value = studentName + ' (' + studentCode + ')';
    document.getElementById('partial_student_code').value = studentCode;
    document.getElementById('partial_generated_id').value = generatedId || '';
    const dueAmountValue = feeAmount !== null ? feeAmount : (studentData.monthly_fee || 0);
    document.getElementById('partial_due_amount').value = 'Rs. ' + parseFloat(dueAmountValue || 0).toFixed(2);
    document.getElementById('partial_fee_title').value = feeTitle || '';
    document.getElementById('partial_payment').value = feeAmount !== null ? parseFloat(feeAmount).toFixed(2) : '';
    document.getElementById('partial_discount').value = '0';
    document.getElementById('partial_method').value = 'Cash Payment';
    document.getElementById('partial_date').value = new Date().toISOString().split('T')[0];
    document.getElementById('partial_notify').value = 'Yes';
    
    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('partialPaymentModal'));
    modal.show();
}

function openPartialPaymentFromBreakdown(feeTitle, feeAmount) {
    if (!currentFeeStudent) {
        return;
    }
    openPartialPaymentModal(
        currentFeeStudent.student_code,
        currentFeeStudent.student_name,
        currentFeeStudent,
        feeTitle,
        feeAmount,
        null
    );
}

function openFeePaymentModal(studentCode, studentName, campus, feeTitle, feeAmount, generatedId = null) {
    openPartialPaymentModal(
        studentCode,
        studentName,
        { campus: campus || '' },
        feeTitle,
        feeAmount,
        generatedId
    );
}

function updatePaymentStatus(studentCode, hasUnpaid) {
    // Find all rows with this student code and update the button
    const rows = document.querySelectorAll('#searchResultsBody tr');
    rows.forEach(row => {
        const codeCell = row.querySelector('td:first-child strong');
        if (codeCell && codeCell.textContent.trim() === studentCode) {
            const actionsCell = row.querySelector('td:last-child');
            if (actionsCell) {
                // Find either Unpaid (btn-danger) or Paid (btn-success) button
                const statusButton = actionsCell.querySelector('.btn-danger, .btn-success');
                if (statusButton) {
                    if (!hasUnpaid) {
                        // Change Unpaid button to Paid button
                        statusButton.className = 'btn btn-sm btn-success';
                        statusButton.innerHTML = '<span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle; color: white;">check_circle</span><span style="color: white;">Paid</span>';
                        statusButton.onclick = null;
                        statusButton.title = 'Payment completed';
                    }
                } else {
                    // If button doesn't exist, create Paid button
                    const buttonContainer = actionsCell.querySelector('.d-flex');
                    if (buttonContainer) {
                        const paidButton = document.createElement('button');
                        paidButton.className = 'btn btn-sm btn-success';
                        paidButton.style.cssText = 'padding: 4px 12px; font-size: 12px; color: white !important;';
                        paidButton.title = 'Payment completed';
                        paidButton.innerHTML = '<span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle; color: white;">check_circle</span><span style="color: white;">Paid</span>';
                        buttonContainer.insertBefore(paidButton, buttonContainer.firstChild);
                    }
                }
            }
        }
    });
}

function updatePaymentStatusWithData(studentCode, hasUnpaid, unpaidAmount) {
    // Find all rows with this student code and update the button
    const rows = document.querySelectorAll('#searchResultsBody tr');
    rows.forEach(row => {
        const codeCell = row.querySelector('td:first-child strong');
        if (codeCell && codeCell.textContent.trim() === studentCode) {
            const actionsCell = row.querySelector('td:last-child');
            if (actionsCell) {
                // Find either Unpaid (btn-danger) or Paid (btn-success) button
                const statusButton = actionsCell.querySelector('.btn-danger, .btn-success');
                if (statusButton) {
                    if (!hasUnpaid || unpaidAmount <= 0) {
                        // Change to Paid button
                        statusButton.className = 'btn btn-sm btn-success';
                        statusButton.innerHTML = '<span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle; color: white;">check_circle</span><span style="color: white;">Paid</span>';
                        statusButton.onclick = null;
                        statusButton.title = 'Payment completed';
                    } else {
                        // Update Unpaid button with new amount
                        statusButton.className = 'btn btn-sm btn-danger';
                        statusButton.innerHTML = '<span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle; color: white;">warning</span><span style="color: white;">Unpaid</span>';
                        statusButton.onclick = function() { viewUnpaid(studentCode, '', unpaidAmount); };
                        statusButton.title = 'Unpaid Amount: ' + parseFloat(unpaidAmount || 0).toFixed(2);
                    }
                } else {
                    // If button doesn't exist, create appropriate button
                    const buttonContainer = actionsCell.querySelector('.d-flex');
                    if (buttonContainer) {
                        if (!hasUnpaid || unpaidAmount <= 0) {
                            // Create Paid button
                            const paidButton = document.createElement('button');
                            paidButton.className = 'btn btn-sm btn-success';
                            paidButton.style.cssText = 'padding: 4px 12px; font-size: 12px; color: white !important;';
                            paidButton.title = 'Payment completed';
                            paidButton.innerHTML = '<span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle; color: white;">check_circle</span><span style="color: white;">Paid</span>';
                            buttonContainer.insertBefore(paidButton, buttonContainer.firstChild);
                        } else {
                            // Create Unpaid button
                            const unpaidButton = document.createElement('button');
                            unpaidButton.className = 'btn btn-sm btn-danger';
                            unpaidButton.style.cssText = 'padding: 4px 12px; font-size: 12px; color: white !important;';
                            unpaidButton.title = 'Unpaid Amount: ' + parseFloat(unpaidAmount || 0).toFixed(2);
                            unpaidButton.onclick = function() { viewUnpaid(studentCode, '', unpaidAmount); };
                            unpaidButton.innerHTML = '<span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle; color: white;">warning</span><span style="color: white;">Unpaid</span>';
                            buttonContainer.insertBefore(unpaidButton, buttonContainer.firstChild);
                        }
                    }
                }
            }
        }
    });
}

function handlePartialPaymentSubmit(event) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    const studentCode = formData.get('student_code');
    
    // Show loading
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> Processing...';
    
    // Submit form
    fetch(form.action, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
        }
    })
    .then(response => {
        // Check if response is OK
        if (!response.ok) {
            return response.text().then(text => {
                throw new Error(`HTTP error! status: ${response.status}, body: ${text}`);
            });
        }
        
        // Check if response is JSON or HTML redirect
        const contentType = response.headers.get('content-type');
        if (contentType && contentType.includes('application/json')) {
            return response.json().then(data => ({ data, isJson: true, status: 'success' }));
        } else {
            // It's a redirect (HTML response), consider it success
            return { data: { success: true }, isJson: false, status: 'success' };
        }
    })
    .then(result => {
        if (result.data.success || !result.isJson || result.status === 'success') {
            // Close modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('partialPaymentModal'));
            if (modal) modal.hide();

            if (result.data && result.data.payment) {
                prependPaymentHistoryRow(result.data.payment);
            }

            refreshSearchResults(() => renderStudentHistory(studentCode));
            alert('Payment recorded successfully!');
        } else {
            const errorMsg = result.data.message || result.data.error || 'Error processing payment';
            alert(errorMsg);
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
        
        // Check if it's a validation error
        if (error.message && error.message.includes('422')) {
            alert('Validation error: Please check all required fields are filled correctly.');
        } else if (error.message && error.message.includes('500')) {
            alert('Server error: Please try again or contact administrator.');
        } else {
            alert('Error processing payment: ' + (error.message || 'Unknown error. Please try again.'));
        }
    });
}

function refreshSearchResults(onComplete) {
    if (!window.lastFeeSearch || !window.lastFeeSearch.value) {
        return;
    }
    if (window.lastFeeSearch.type === 'name') {
        document.getElementById('searchByName').value = window.lastFeeSearch.value;
        searchByName(onComplete);
    } else if (window.lastFeeSearch.type === 'cnic') {
        document.getElementById('searchByCNIC').value = window.lastFeeSearch.value;
        searchByCNIC(onComplete);
    }
}

function prependPaymentHistoryRow(payment) {
    const tbody = document.querySelector('#latestPaymentsSection table tbody');
    if (!tbody || !payment) return;

    const totalAmount = (parseFloat(payment.payment_amount || 0) + parseFloat(payment.late_fee || 0)).toFixed(2);
    const discount = parseFloat(payment.discount || 0).toFixed(2);
    const lateFee = parseFloat(payment.late_fee || 0).toFixed(2);
    const paid = parseFloat(payment.payment_amount || 0).toFixed(2);
    const due = '0.00';
    const dateText = payment.payment_date || 'N/A';

    const row = document.createElement('tr');
    row.innerHTML = `
        <td style="padding: 8px 12px; font-size: 13px;"><strong>${payment.student_code || 'N/A'}</strong></td>
        <td style="padding: 8px 12px; font-size: 13px;">${payment.student_name || 'N/A'}</td>
        <td style="padding: 8px 12px; font-size: 13px;">${payment.father_name || 'N/A'}</td>
        <td style="padding: 8px 12px; font-size: 13px;">${payment.payment_title || 'N/A'}</td>
        <td style="padding: 8px 12px; font-size: 13px;"><strong style="color: #28a745;">${totalAmount}</strong></td>
        <td style="padding: 8px 12px; font-size: 13px;">${discount}</td>
        <td style="padding: 8px 12px; font-size: 13px;">${lateFee}</td>
        <td style="padding: 8px 12px; font-size: 13px;"><strong style="color: #28a745;">${paid}</strong></td>
        <td style="padding: 8px 12px; font-size: 13px;">${due}</td>
        <td style="padding: 8px 12px; font-size: 13px;">${dateText}</td>
        <td style="padding: 8px 12px; font-size: 13px;">${payment.accountant || 'N/A'}</td>
    `;

    const totalRow = Array.from(tbody.querySelectorAll('tr')).find(r => r.textContent.includes('Total'));
    if (totalRow) {
        tbody.insertBefore(row, totalRow);
        const cells = totalRow.querySelectorAll('td');
        if (cells.length >= 9) {
            cells[1].textContent = (parseFloat(cells[1].textContent || 0) + parseFloat(totalAmount)).toFixed(2);
            cells[2].textContent = (parseFloat(cells[2].textContent || 0) + parseFloat(discount)).toFixed(2);
            cells[3].textContent = (parseFloat(cells[3].textContent || 0) + parseFloat(lateFee)).toFixed(2);
            cells[4].textContent = (parseFloat(cells[4].textContent || 0) + parseFloat(paid)).toFixed(2);
            cells[5].textContent = (parseFloat(cells[5].textContent || 0) + parseFloat(due)).toFixed(2);
        }
    } else {
        tbody.prepend(row);
    }
}

function renderStudentHistory(studentCode) {
    if (!studentCode) return;
    fetch(`{{ route('fee-payment.history') }}?student_code=${encodeURIComponent(studentCode)}`, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
        }
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) return;
        const searchResultsBody = document.getElementById('searchResultsBody');
        if (!searchResultsBody) return;

        // Remove existing history row for this student
        searchResultsBody.querySelectorAll(`tr.student-history-row[data-student-code="${studentCode}"]`).forEach(row => row.remove());

        const rows = Array.from(searchResultsBody.querySelectorAll('tr'));
        const studentRows = rows.filter(row => {
            const codeCell = row.querySelector('td:first-child strong');
            return codeCell && codeCell.textContent.trim() === studentCode;
        });
        if (studentRows.length === 0) return;

        const lastRow = studentRows[studentRows.length - 1];
        const historyRow = document.createElement('tr');
        historyRow.className = 'student-history-row';
        historyRow.dataset.studentCode = studentCode;

        const paymentRows = (data.payments || []).map(payment => {
            const totalAmount = (parseFloat(payment.payment_amount || 0) + parseFloat(payment.late_fee || 0)).toFixed(2);
            return `
                <tr>
                    <td>${payment.payment_title || 'N/A'}</td>
                    <td class="text-end">${totalAmount}</td>
                    <td class="text-end">${parseFloat(payment.discount || 0).toFixed(2)}</td>
                    <td class="text-end">${parseFloat(payment.late_fee || 0).toFixed(2)}</td>
                    <td class="text-end">${parseFloat(payment.payment_amount || 0).toFixed(2)}</td>
                    <td class="text-end">0.00</td>
                    <td>${payment.payment_date || 'N/A'}</td>
                    <td>${payment.accountant || 'N/A'}</td>
                </tr>
            `;
        }).join('');

        historyRow.innerHTML = `
            <td colspan="11" style="padding: 0;">
                <div style="padding: 12px 16px; background: #f8f9fa; border: 1px solid #e9ecef;">
                    <div class="mb-2 fw-semibold" style="color: #003471;">Payment History</div>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Fee Title</th>
                                    <th class="text-end">Total</th>
                                    <th class="text-end">Dis</th>
                                    <th class="text-end">Late Fee</th>
                                    <th class="text-end">Paid</th>
                                    <th class="text-end">Due</th>
                                    <th>Date</th>
                                    <th>Accountant</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${paymentRows || '<tr><td colspan="8" class="text-center text-muted">No history found.</td></tr>'}
                            </tbody>
                        </table>
                    </div>
                </div>
            </td>
        `;

        lastRow.insertAdjacentElement('afterend', historyRow);
    })
    .catch(() => {});
}

function searchByCNIC(onComplete) {
    const searchValue = document.getElementById('searchByCNIC').value.trim();
    if (!searchValue) {
        alert('Please enter Father\'s CNIC or Parent ID');
        return;
    }
    window.lastFeeSearch = { type: 'cnic', value: searchValue };

    // Show loading state
    const searchResultsSection = document.getElementById('searchResultsSection');
    const searchResultsBody = document.getElementById('searchResultsBody');
    const latestPaymentsSection = document.getElementById('latestPaymentsSection');
    
    searchResultsSection.style.display = 'block';
    latestPaymentsSection.style.display = 'none';
    searchResultsBody.innerHTML = '<tr><td colspan="7" class="text-center py-4"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div><p class="mt-2 text-muted">Searching...</p></td></tr>';

    // Make AJAX call to search students by CNIC
    fetch(`{{ route('fee-payment.search-by-cnic') }}?cnic=${encodeURIComponent(searchValue)}`, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
        }
    })
    .then(response => response.json())
    .then(data => {
        searchResultsBody.innerHTML = '';
        
        if (data.success && data.students && data.students.length > 0) {
            let totals = { total: 0, discount: 0, late: 0, paid: 0, due: 0 };
            data.students.forEach((student) => {
                const feeRows = (student.fee_rows && student.fee_rows.length > 0) ? student.fee_rows : [{
                    title: 'N/A',
                    total: 0,
                    discount: 0,
                    late_fee: 0,
                    paid: 0,
                    due: 0,
                    amount: 0,
                    remaining_late: 0,
                }];

                feeRows.forEach((fee) => {
                    const row = document.createElement('tr');
                    const feeTitleSafe = (fee.title || '').replace(/'/g, "\\'");
                    const studentNameSafe = (student.student_name || '').replace(/'/g, "\\'");
                    const campusSafe = (student.campus || '').replace(/'/g, "\\'");
                    const dueAmount = parseFloat(fee.due || 0);
                    const feeAmount = parseFloat(fee.amount || 0);
                    totals.total += parseFloat(fee.total || 0);
                    totals.discount += parseFloat(fee.discount || 0);
                    totals.late += parseFloat(fee.late_fee || 0);
                    totals.paid += parseFloat(fee.paid || 0);
                    totals.due += dueAmount;
                    row.innerHTML = `
                        <td style="padding: 8px 12px; font-size: 13px;">
                            <strong>${student.student_code || 'N/A'}</strong>
                        </td>
                        <td style="padding: 8px 12px; font-size: 13px;">
                            ${student.student_name || 'N/A'}
                        </td>
                        <td style="padding: 8px 12px; font-size: 13px;">
                            ${student.father_name || 'N/A'}
                        </td>
                        <td style="padding: 8px 12px; font-size: 13px;">
                            ${fee.title || 'N/A'}
                        </td>
                        <td style="padding: 8px 12px; font-size: 13px;">
                            ${parseFloat(fee.total || 0).toFixed(2)}
                        </td>
                        <td style="padding: 8px 12px; font-size: 13px;">
                            ${parseFloat(fee.discount || 0).toFixed(2)}
                        </td>
                        <td style="padding: 8px 12px; font-size: 13px;">
                            ${parseFloat(fee.late_fee || 0).toFixed(2)}
                        </td>
                        <td style="padding: 8px 12px; font-size: 13px;">
                            ${parseFloat(fee.paid || 0).toFixed(2)}
                        </td>
                        <td style="padding: 8px 12px; font-size: 13px;">
                            ${dueAmount.toFixed(2)}
                        </td>
                        <td style="padding: 8px 12px; font-size: 13px; position: relative; overflow: visible;">
                            <div class="d-flex gap-2 flex-wrap" style="position: relative;">
                                ${dueAmount > 0 ? `
                                    <button class="btn btn-sm btn-danger" onclick="openFeePaymentModal('${student.student_code}', '${studentNameSafe}', '${campusSafe}', '${feeTitleSafe}', ${dueAmount.toFixed(2)}, ${generatedId ? generatedId : 'null'})" style="padding: 4px 12px; font-size: 12px; color: white !important;">
                                        <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle; color: white;">warning</span>
                                        <span style="color: white;">Unpaid</span>
                                    </button>
                                ` : `
                                    <button class="btn btn-sm btn-success" style="padding: 4px 12px; font-size: 12px; color: white !important;">
                                        <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle; color: white;">check_circle</span>
                                        <span style="color: white;">Paid</span>
                                    </button>
                                `}
                                <div class="btn-group" style="position: static;">
                                    <button type="button" class="btn btn-sm btn-primary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" style="padding: 4px 12px; font-size: 12px; color: white !important;">
                                        <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle; color: white;">payments</span>
                                        <span style="color: white;">Take Payment</span>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end" style="position: absolute; z-index: 1050;">
                                        <li><a class="dropdown-item" href="#" onclick="openFeePaymentModal('${student.student_code}', '${studentNameSafe}', '${campusSafe}', '${feeTitleSafe}', ${dueAmount.toFixed(2)}, ${generatedId ? generatedId : 'null'}); return false;">
                                            <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle; margin-right: 5px;">check_circle</span>
                                            Pay This Fee
                                        </a></li>
                                        <li><a class="dropdown-item" href="#" onclick="openFeePaymentModal('${student.student_code}', '${studentNameSafe}', '${campusSafe}', '${feeTitleSafe}', ${feeAmount.toFixed(2)}, ${generatedId ? generatedId : 'null'}); return false;">
                                            <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle; margin-right: 5px;">remove_circle</span>
                                            Pay without late fee
                                        </a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item" href="#" onclick="takePayment('${student.student_code}', '${studentNameSafe}', 'full'); return false;">
                                            <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle; margin-right: 5px;">payments</span>
                                            Pay All
                                        </a></li>
                                    </ul>
                                </div>
                            </div>
                        </td>
                        <td style="padding: 8px 12px; font-size: 13px; position: relative; overflow: visible;">
                            <div class="d-flex gap-2 flex-wrap align-items-center" style="position: relative;">
                                <button class="btn btn-sm btn-warning" onclick="editStudent(${student.id}, '${student.student_code}', '${studentNameSafe}')" style="padding: 4px 10px; font-size: 12px; color: white !important;" title="Edit Student">
                                    <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle; color: white;">edit</span>
                                </button>
                                <button class="btn btn-sm btn-danger" onclick="deleteStudent(${student.id}, '${student.student_code}', '${studentNameSafe}')" style="padding: 4px 10px; font-size: 12px; color: white !important;" title="Delete Student">
                                    <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle; color: white;">delete</span>
                                </button>
                                <div class="btn-group" style="position: static;">
                                    <button type="button" class="btn btn-sm btn-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" style="padding: 4px 10px; font-size: 12px; color: white !important;" title="More Options">
                                        <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle; color: white;">arrow_drop_down</span>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end" style="position: absolute; z-index: 1050;">
                                        <li><a class="dropdown-item" href="#" onclick="payAll('${student.student_code}', '${studentNameSafe}'); return false;">
                                            <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle; margin-right: 5px;">payments</span>
                                            Pay All
                                        </a></li>
                                        <li><a class="dropdown-item" href="#" onclick="printVoucher('${student.student_code}', '${studentNameSafe}'); return false;">
                                            <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle; margin-right: 5px;">receipt</span>
                                            Print Voucher
                                        </a></li>
                                        <li><a class="dropdown-item" href="#" onclick="makeInstallment('${student.student_code}', '${studentNameSafe}'); return false;">
                                            <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle; margin-right: 5px;">calendar_month</span>
                                            Make Installment
                                        </a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item" href="#" onclick="particularReceipt('${student.student_code}', '${studentNameSafe}'); return false;">
                                            <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle; margin-right: 5px;">description</span>
                                            Particular Receipt
                                        </a></li>
                                    </ul>
                                </div>
                            </div>
                        </td>
                    `;
                    searchResultsBody.appendChild(row);
                });
            });

            const totalRow = document.createElement('tr');
            totalRow.innerHTML = `
                <td colspan="4" class="text-end fw-semibold">Total sab se nechi</td>
                <td class="fw-semibold">${totals.total.toFixed(2)}</td>
                <td class="fw-semibold">${totals.discount.toFixed(2)}</td>
                <td class="fw-semibold">${totals.late.toFixed(2)}</td>
                <td class="fw-semibold">${totals.paid.toFixed(2)}</td>
                <td class="fw-semibold">${totals.due.toFixed(2)}</td>
                <td colspan="2"></td>
            `;
            searchResultsBody.appendChild(totalRow);
        } else {
            searchResultsBody.innerHTML = '<tr><td colspan="8" class="text-center py-4 text-muted">No students found matching this CNIC / Parent ID.</td></tr>';
        }
        if (typeof onComplete === 'function') {
            onComplete();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        searchResultsBody.innerHTML = '<tr><td colspan="8" class="text-center py-4 text-danger">An error occurred while searching. Please try again.</td></tr>';
        if (typeof onComplete === 'function') {
            onComplete();
        }
    });
}

// More column functions
function editStudent(studentId, studentCode, studentName) {
    // Redirect to student view page using student ID
    window.location.href = '{{ route("student.view", ":id") }}'.replace(':id', studentId);
}

function payAll(studentCode, studentName) {
    // Redirect to pay all fees page or trigger full payment
    takePayment(studentCode, studentName, 'full');
}

function printVoucher(studentCode, studentName) {
    // Redirect to print voucher page
    window.open(`{{ route('accounting.fee-voucher.print', ['student_code' => '']) }}${studentCode}`, '_blank');
}

function makeInstallment(studentCode, studentName) {
    // Redirect to make installment page
    window.location.href = `{{ route('accounting.make-installment', ['student_code' => '']) }}${studentCode}`;
}

function particularReceipt(studentCode, studentName) {
    // Redirect to particular receipt page
    window.open(`{{ route('accounting.particular-receipt', ['student_code' => '']) }}${studentCode}`, '_blank');
}

// Allow Enter key to trigger search
function setupAutoSearch(inputId, handler) {
    const input = document.getElementById(inputId);
    if (!input) return;

    let debounceTimer = null;
    input.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            handler();
        }
    });
    input.addEventListener('input', function() {
        clearTimeout(debounceTimer);
        const value = input.value.trim();
        if (!value) return;
        debounceTimer = setTimeout(() => {
            handler();
        }, 400);
    });
}

setupAutoSearch('searchByName', searchByName);
setupAutoSearch('searchByCNIC', searchByCNIC);

// Barcode/scan fallback: capture fast key bursts and trigger search
let scanBuffer = '';
let scanResetTimer = null;
document.addEventListener('keydown', function(e) {
    const active = document.activeElement;
    const isTypingField = active && (active.tagName === 'INPUT' || active.tagName === 'TEXTAREA');
    if (e.key === 'Enter') {
        if (!isTypingField && scanBuffer.trim()) {
            const input = document.getElementById('searchByName');
            if (input) {
                input.value = scanBuffer.trim();
            }
            searchByName();
        }
        scanBuffer = '';
        clearTimeout(scanResetTimer);
        return;
    }
    if (!isTypingField && e.key.length === 1) {
        scanBuffer += e.key;
        clearTimeout(scanResetTimer);
        scanResetTimer = setTimeout(() => {
            scanBuffer = '';
        }, 200);
    }
});

document.getElementById('searchByName')?.focus();
</script>
@endsection

