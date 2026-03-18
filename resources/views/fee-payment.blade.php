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
                                    <th style="padding: 8px 12px; font-size: 13px;">Fee Type</th>
                                    <th style="padding: 8px 12px; font-size: 13px;">Total</th>
                                    <th style="padding: 8px 12px; font-size: 13px;">Dis</th>
                                    <th style="padding: 8px 12px; font-size: 13px;">Late Fee</th>
                                    <th style="padding: 8px 12px; font-size: 13px;">Paid</th>
                                    <th style="padding: 8px 12px; font-size: 13px;">Due</th>
                                    <th style="padding: 8px 12px; font-size: 13px;">Generated Fee</th>
                                    <th style="padding: 8px 12px; font-size: 13px;">Status</th>
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

                <div id="latestPaymentsContainer">
                    <div class="default-table-area" style="margin-top: 0;">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <tbody>
                                    <tr>
                                        <td colspan="12" class="text-center py-4" id="latestPaymentsEmpty">
                                            <div class="d-flex flex-column align-items-center gap-2">
                                                <span class="material-symbols-outlined" style="font-size: 48px; color: #dee2e6;">payments</span>
                                                <p class="text-muted mb-0">No payments found.</p>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
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
                                <select class="form-select" id="partial_campus" name="campus" required>
                                    <option value="">Select Campus</option>
                                    @foreach($campuses as $campus)
                                        <option value="{{ $campus->campus_name ?? $campus }}">{{ $campus->campus_name ?? $campus }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <!-- Student -->
                        <div class="col-md-6">
                            <label class="form-label mb-1 fw-semibold" style="color: #003471; font-size: 13px;">Student</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 16px;">person</span>
                                </span>
                                <select class="form-select" id="partial_student" required>
                                    <option value="">Select Student</option>
                                </select>
                                <input type="hidden" id="partial_student_code" name="student_code">
                            </div>
                        </div>

                        <!-- Fee Title -->
                        <div class="col-md-6">
                            <label class="form-label mb-1 fw-semibold" style="color: #003471; font-size: 13px;">Fee Title</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 16px;">receipt</span>
                                </span>
                                <input type="text" class="form-control" id="partial_fee_title" name="payment_title" placeholder="Enter Fee Title" readonly style="background-color: #f8f9fa; cursor: not-allowed;" required>
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
                                    <option value="Wallet">Wallet</option>
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
                                <select class="form-select form-select-sm" name="sms_notification" id="partial_notify" required>
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

<!-- Make Installments Modal -->
<div class="modal fade" id="makeInstallmentModal" tabindex="-1" aria-labelledby="makeInstallmentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" style="border-radius: 12px; border: none; box-shadow: 0 10px 40px rgba(0,0,0,0.2);">
            <div class="modal-header" style="background: linear-gradient(135deg, #003471 0%, #004a9f 100%); border-radius: 12px 12px 0 0; border: none; padding: 20px;">
                <h5 class="modal-title fs-15 fw-semibold mb-0 d-flex align-items-center gap-2" id="makeInstallmentModalLabel" style="color: white !important;">
                    <span class="material-symbols-outlined" style="font-size: 20px; color: white !important;">account_balance</span>
                    <span style="color: white !important;">Make Installments</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="makeInstallmentForm" method="POST" action="{{ route('accounting.direct-payment.student.store') }}" onsubmit="handleInstallmentSubmit(event)">
                @csrf
                <div class="modal-body p-4" style="background-color: #f8f9fa;">
                    <div class="row g-3">
                        <!-- Student -->
                        <div class="col-md-6">
                            <label class="form-label mb-1 fw-semibold" style="color: #003471; font-size: 13px;">Student</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 16px;">person</span>
                                </span>
                                <input type="text" class="form-control" id="installment_student" readonly style="background-color: #f8f9fa; cursor: not-allowed;">
                                <input type="hidden" id="installment_student_code" name="student_code">
                            </div>
                        </div>

                        <!-- Fee Title -->
                        <div class="col-md-6">
                            <label class="form-label mb-1 fw-semibold" style="color: #003471; font-size: 13px;">Fee Title <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 16px;">receipt</span>
                                </span>
                                <select class="form-select" id="installment_fee_title" name="payment_title" required>
                                    <option value="">Select Fee Title</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Fee Cards (will be populated dynamically) -->
                        <div class="col-12" id="feeCardsContainer" style="display: none;">
                            <label class="form-label mb-2 fw-semibold" style="color: #003471; font-size: 13px;">Quick Select Fee:</label>
                            <div class="row g-2" id="feeCardsRow">
                                <!-- Fee cards will be dynamically added here -->
                            </div>
                        </div>

                        <!-- Total Amount -->
                        <div class="col-md-6">
                            <label class="form-label mb-1 fw-semibold" style="color: #003471; font-size: 13px;">Total Amount</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 16px;">attach_money</span>
                                </span>
                                <input type="text" class="form-control" id="installment_total_amount" readonly style="background-color: #f8f9fa; cursor: not-allowed;">
                            </div>
                        </div>

                        <!-- Amount Paid -->
                        <div class="col-md-6">
                            <label class="form-label mb-1 fw-semibold" style="color: #003471; font-size: 13px;">Amount Paid</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 16px;">payments</span>
                                </span>
                                <input type="text" class="form-control" id="installment_amount_paid" readonly style="background-color: #f8f9fa; cursor: not-allowed;">
                            </div>
                        </div>

                        <!-- Discount -->
                        <div class="col-md-6">
                            <label class="form-label mb-1 fw-semibold" style="color: #003471; font-size: 13px;">Discount</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 16px;">remove</span>
                                </span>
                                <input type="text" class="form-control" id="installment_discount" readonly style="background-color: #f8f9fa; cursor: not-allowed;">
                            </div>
                        </div>

                        <!-- Remaining Amount -->
                        <div class="col-md-6">
                            <label class="form-label mb-1 fw-semibold" style="color: #003471; font-size: 13px;">Remaining Amount</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 16px;">account_balance_wallet</span>
                                </span>
                                <input type="text" class="form-control" id="installment_remaining_amount" readonly style="background-color: #f8f9fa; cursor: not-allowed;">
                            </div>
                        </div>

                        <!-- Total Installments -->
                        <div class="col-md-6">
                            <label class="form-label mb-1 fw-semibold" style="color: #003471; font-size: 13px;">Total Installments <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 16px;">calendar_view_month</span>
                                </span>
                                <input type="number" step="1" min="1" max="12" class="form-control" id="installment_total_installments" name="total_installments" placeholder="Enter number of installments" required>
                            </div>
                            <small class="text-muted">Enter how many installments you want to split the remaining amount</small>
                        </div>

                        <!-- Installment Amount (Auto-calculated) -->
                        <div class="col-md-6">
                            <label class="form-label mb-1 fw-semibold" style="color: #003471; font-size: 13px;">Per Installment Amount</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 16px;">calculate</span>
                                </span>
                                <input type="text" class="form-control" id="installment_per_installment" readonly style="background-color: #e7f3ff; cursor: not-allowed; font-weight: 600;">
                            </div>
                        </div>

                        <!-- Payment Method -->
                        <div class="col-md-6">
                            <label class="form-label mb-1 fw-semibold" style="color: #003471; font-size: 13px;">Payment Method <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 16px;">payment</span>
                                </span>
                                <select class="form-select" id="installment_payment_method" name="payment_method" required>
                                    <option value="Cash Payment">Cash Payment</option>
                                    <option value="Bank Transfer">Bank Transfer</option>
                                    <option value="Cheque">Cheque</option>
                                    <option value="Online Payment">Online Payment</option>
                                    <option value="Card Payment">Card Payment</option>
                                    <option value="Wallet">Wallet</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer" style="border-top: 1px solid #dee2e6; padding: 15px 20px;">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="padding: 8px 20px;">Cancel</button>
                    <button type="submit" class="btn btn-primary" style="background-color: #003471; border: none; padding: 8px 20px;">
                        <span class="material-symbols-outlined" style="font-size: 18px; vertical-align: middle; color: white;">check</span>
                        <span style="color: white;">Create Installments</span>
                    </button>
                </div>
            </form>
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

    #partialPaymentModal .input-group-text,
    #partialPaymentModal .form-control,
    #partialPaymentModal .form-select,
    #makeInstallmentModal .input-group-text,
    #makeInstallmentModal .form-control,
    #makeInstallmentModal .form-select {
        height: 32px;
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
function renderStatusCell(due, paidForStatus, studentCode, studentName, isInstallment = false) {
    // For installments, always show as Installment regardless of payment status
    if (isInstallment) {
        return `
            <button class="btn btn-sm btn-info" style="padding: 4px 12px; font-size: 12px; color: white !important; background-color: #0dcaf0;" title="Installment payment">
                <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle; color: white;">payments</span>
                <span style="color: white;">Installment</span>
            </button>
        `;
    }
    if (due <= 0) {
        return `
            <button class="btn btn-sm btn-success" style="padding: 4px 12px; font-size: 12px; color: white !important;" title="Payment completed">
                <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle; color: white;">check_circle</span>
                <span style="color: white;">Paid</span>
            </button>
        `;
    }
    if (paidForStatus > 0) {
        return `
            <button class="btn btn-sm btn-warning" style="padding: 4px 12px; font-size: 12px; color: white !important;" title="Partial payment">
                <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle; color: white;">hourglass_top</span>
                <span style="color: white;">Partial</span>
            </button>
        `;
    }
    return `
        <button class="btn btn-sm btn-danger" onclick="viewUnpaid('${studentCode}', '${studentName}', ${due})" style="padding: 4px 12px; font-size: 12px; color: white !important;" title="Unpaid Amount: ${due.toFixed(2)}">
            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle; color: white;">warning</span>
            <span style="color: white;">Unpaid</span>
        </button>
    `;
}
function searchByName() {
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
    latestPaymentsSection.style.display = 'block';
    searchResultsBody.innerHTML = '<tr><td colspan="13" class="text-center py-4"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div><p class="mt-2 text-muted">Searching...</p></td></tr>';

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
            const grandTotals = { total: 0, discount: 0, late: 0, paid: 0, due: 0, generated: 0 };
            data.students.forEach((student) => {
                const feeRows = Array.isArray(student.fee_rows) && student.fee_rows.length > 0
                    ? student.fee_rows
                    : [{
                        title: 'No Fee Generated',
                        total: 0,
                        discount: 0,
                        late_fee: 0,
                        paid: 0,
                        due: 0,
                        is_empty: true,
                    }];

                feeRows.forEach((fee) => {
                    const feeTitleSafe = (fee.title || '').replace(/'/g, "\\'");
                    const total = parseFloat(fee.total || 0);
                    const discount = parseFloat(fee.discount || 0);
                    const lateFee = parseFloat(fee.late_fee || 0);
                    const paid = parseFloat(fee.paid || 0);
                    const due = parseFloat(fee.due || 0);
                    // Generated fee = total - discount (fee after discount is applied)
                    const generatedFee = parseFloat(fee.generated_fee || (total - discount));
                    const isEmptyFee = !!fee.is_empty;
                    const isInstallment = !!fee.is_installment;
                    const paidForStatus = paid;
                    const paidDisplay = paid;
                    grandTotals.total += total;
                    grandTotals.discount += discount;
                    grandTotals.late += lateFee;
                    grandTotals.paid += paid;
                    grandTotals.due += due;
                    grandTotals.generated += generatedFee;
                    
                    // Add installment badge to title if it's an installment
                    const feeTitleDisplay = isInstallment 
                        ? `${fee.title || 'N/A'} <span class="badge bg-info text-white ms-2" style="font-size: 10px; padding: 2px 6px;">Installment</span>`
                        : (fee.title || 'N/A');
                    
                    const row = document.createElement('tr');
                    // Add background color for installment rows
                    if (isInstallment) {
                        row.style.backgroundColor = '#f0f8ff';
                    }
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
                            ${feeTitleDisplay}
                        </td>
                        <td style="padding: 8px 12px; font-size: 13px;">
                            ${total.toFixed(2)}
                        </td>
                        <td style="padding: 8px 12px; font-size: 13px;">
                            ${discount.toFixed(2)}
                        </td>
                        <td style="padding: 8px 12px; font-size: 13px;">
                            ${lateFee.toFixed(2)}
                        </td>
                        <td style="padding: 8px 12px; font-size: 13px;">
                            ${paidDisplay.toFixed(2)}
                        </td>
                        <td style="padding: 8px 12px; font-size: 13px;">
                            ${due.toFixed(2)}
                        </td>
                        <td style="padding: 8px 12px; font-size: 13px;">
                            ${generatedFee.toFixed(2)}
                        </td>
                        <td style="padding: 8px 12px; font-size: 13px;" class="status-cell">
                            ${isEmptyFee ? '<span class="badge bg-secondary">N/A</span>' : renderStatusCell(due, paidForStatus, student.student_code, student.student_name, isInstallment)}
                        </td>
                        <td style="padding: 8px 12px; font-size: 13px; position: relative; overflow: visible;">
                            <div class="btn-group" style="position: static;">
                                <button type="button" class="btn btn-sm btn-primary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" style="padding: 4px 12px; font-size: 12px; color: white !important;">
                                    <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle; color: white;">payments</span>
                                    <span style="color: white;">Take Payment</span>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end" style="position: absolute; z-index: 1050;">
                                    <li><a class="dropdown-item" href="#" onclick="takePayment('${student.student_code}', '${student.student_name}', 'full', {payment_title: '${feeTitleSafe}', generated_id: ${fee.generated_id ? fee.generated_id : 'null'}, payment_id: ${fee.payment_id ? fee.payment_id : 'null'}, is_installment: ${isInstallment ? 'true' : 'false'}}); return false;">
                                        <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle; margin-right: 5px;">check_circle</span>
                                        Full Payment
                                    </a></li>
                                    <li><a class="dropdown-item" href="#" onclick="takePayment('${student.student_code}', '${student.student_name}', 'partial', {student_code: '${student.student_code}', student_name: '${student.student_name}', campus: '${student.campus || ''}', monthly_fee: ${student.monthly_fee || 0}, fee_title: '${feeTitleSafe}', fee_due: ${due}, generated_fee: ${generatedFee}, payment_id: ${fee.payment_id ? fee.payment_id : 'null'}, is_installment: ${isInstallment ? 'true' : 'false'}}); return false;">
                                        <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle; margin-right: 5px;">account_balance_wallet</span>
                                        Partial Payment
                                    </a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="#" onclick="takePayment('${student.student_code}', '${student.student_name}', 'without_late_fee', {payment_title: '${feeTitleSafe}', generated_id: ${fee.generated_id ? fee.generated_id : 'null'}, payment_id: ${fee.payment_id ? fee.payment_id : 'null'}, is_installment: ${isInstallment ? 'true' : 'false'}}); return false;">
                                        <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle; margin-right: 5px;">remove_circle</span>
                                        Pay without late fee
                                    </a></li>
                                </ul>
                            </div>
                        </td>
                        <td style="padding: 8px 12px; font-size: 13px; position: relative; overflow: visible;">
                            <div class="btn-group" style="position: static;">
                                <button type="button" class="btn btn-sm" data-bs-toggle="dropdown" aria-expanded="false" style="padding: 2px 6px; border: none; background: #000; color: #fff; border-radius: 4px;" title="More Options">
                                    <span class="material-symbols-outlined" style="font-size: 18px; vertical-align: middle; color: #fff;">arrow_drop_down</span>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end" style="position: absolute; z-index: 1050;">
                                    <li><a class="dropdown-item" href="#" onclick="printVoucher('${student.student_code}', '${student.student_name}'); return false;">
                                        <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle; margin-right: 5px;">print</span>
                                        Print Voucher
                                    </a></li>
                                    ${!isInstallment ? `<li><a class="dropdown-item" href="#" onclick="makeInstallment('${student.student_code}', '${student.student_name}', {title: '${feeTitleSafe}', total: ${total}, paid: ${paid}, discount: ${discount}, due: ${due}}); return false;">
                                        <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle; margin-right: 5px;">account_balance</span>
                                        Make Installments
                                    </a></li>
                                    ${fee.payment_id ? `<li><a class="dropdown-item text-danger" href="#" onclick="deleteFeePayment(${fee.payment_id}, '${student.student_code}', '${feeTitleSafe}'); return false;">
                                        <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle; margin-right: 5px;">delete</span>
                                        Delete Payment
                                    </a></li>` : ''}
                                    ${fee.generated_id ? `<li><a class="dropdown-item text-danger" href="#" onclick="deleteFeePayment(${fee.generated_id}, '${student.student_code}', '${feeTitleSafe}'); return false;">
                                        <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle; margin-right: 5px;">delete</span>
                                        Delete Generated Fee
                                    </a></li>` : ''}` : ''}
                                    ${isInstallment ? (fee.generated_id ? `<li><a class="dropdown-item text-danger" href="#" onclick="deleteFeePayment(${fee.generated_id}, '${student.student_code}', '${feeTitleSafe}'); return false;">
                                        <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle; margin-right: 5px;">delete</span>
                                        Delete Installment
                                    </a></li>` : (fee.payment_id ? `<li><a class="dropdown-item text-danger" href="#" onclick="deleteFeePayment(${fee.payment_id}, '${student.student_code}', '${feeTitleSafe}'); return false;">
                                        <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle; margin-right: 5px;">delete</span>
                                        Delete Installment
                                    </a></li>` : '')) : ''}
                                </ul>
                            </div>
                        </td>
                    `;
                    searchResultsBody.appendChild(row);
                });
            });
            const totalRow = document.createElement('tr');
            totalRow.innerHTML = `
                <td colspan="4" style="padding: 8px 12px; font-size: 13px;" class="text-end fw-semibold">Total</td>
                <td style="padding: 8px 12px; font-size: 13px;" class="fw-semibold">${grandTotals.total.toFixed(2)}</td>
                <td style="padding: 8px 12px; font-size: 13px;" class="fw-semibold">${grandTotals.discount.toFixed(2)}</td>
                <td style="padding: 8px 12px; font-size: 13px;" class="fw-semibold">${grandTotals.late.toFixed(2)}</td>
                <td style="padding: 8px 12px; font-size: 13px;" class="fw-semibold">${grandTotals.paid.toFixed(2)}</td>
                <td style="padding: 8px 12px; font-size: 13px;" class="fw-semibold">${grandTotals.due.toFixed(2)}</td>
                <td style="padding: 8px 12px; font-size: 13px;" class="fw-semibold">${grandTotals.generated.toFixed(2)}</td>
                <td colspan="3"></td>
            `;
            searchResultsBody.appendChild(totalRow);
            renderLatestPaymentsForStudents(data.students);
        } else {
            searchResultsBody.innerHTML = '<tr><td colspan="13" class="text-center py-4 text-muted">No students found matching your search.</td></tr>';
            renderLatestPaymentsForStudents([]);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        searchResultsBody.innerHTML = '<tr><td colspan="13" class="text-center py-4 text-danger">An error occurred while searching. Please try again.</td></tr>';
    });
}

function closeSearchResults() {
    document.getElementById('searchResultsSection').style.display = 'none';
    document.getElementById('latestPaymentsSection').style.display = 'block';
    document.getElementById('searchByName').value = '';
}

function viewUnpaid(studentCode, studentName, unpaidAmount) {
    // Fetch student fee data to show fee cards
    fetch(`{{ route('fee-payment.search-student') }}?search=${encodeURIComponent(studentCode)}`, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
        },
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.students && data.students.length > 0) {
            const student = data.students[0];
            
            // Create or get unpaid fees modal
            let unpaidModal = document.getElementById('unpaidFeesModal');
            if (!unpaidModal) {
                // Create modal if it doesn't exist
                unpaidModal = document.createElement('div');
                unpaidModal.id = 'unpaidFeesModal';
                unpaidModal.className = 'modal fade';
                unpaidModal.setAttribute('tabindex', '-1');
                unpaidModal.innerHTML = `
                    <div class="modal-dialog modal-lg modal-dialog-centered">
                        <div class="modal-content" style="border-radius: 12px; border: none; box-shadow: 0 10px 40px rgba(0,0,0,0.2);">
                            <div class="modal-header" style="background: linear-gradient(135deg, #003471 0%, #004a9f 100%); border-radius: 12px 12px 0 0; border: none; padding: 20px;">
                                <h5 class="modal-title fs-15 fw-semibold mb-0 d-flex align-items-center gap-2" style="color: white !important;">
                                    <span class="material-symbols-outlined" style="font-size: 20px; color: white !important;">receipt_long</span>
                                    <span style="color: white !important;">Unpaid Fees - ${studentName || studentCode}</span>
                                </h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body p-4" style="background-color: #f8f9fa;">
                                <div class="row g-3" id="unpaidFeeCardsRow">
                                    <!-- Fee cards will be dynamically added here -->
                                </div>
                            </div>
                            <div class="modal-footer" style="border-top: 1px solid #dee2e6; padding: 15px 20px;">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="padding: 8px 20px;">Close</button>
                            </div>
                        </div>
                    </div>
                `;
                document.body.appendChild(unpaidModal);
            }
            
            // Clear and populate fee cards
            const feeCardsRow = document.getElementById('unpaidFeeCardsRow');
            feeCardsRow.innerHTML = '';
            
            if (student.fee_rows && student.fee_rows.length > 0) {
                student.fee_rows.forEach((fee, index) => {
                    const remaining = parseFloat(fee.due || 0);
                    const total = parseFloat(fee.total || 0);
                    // Use generated_fee if available, otherwise calculate it
                    const generatedFee = parseFloat(fee.generated_fee || (total - parseFloat(fee.discount || 0)));
                    
                    if (remaining > 0) {
                        const cardCol = document.createElement('div');
                        cardCol.className = 'col-md-4 col-sm-6';
                        cardCol.innerHTML = `
                            <div class="fee-card-clickable" style="border: 2px solid #e0e7ff; border-radius: 8px; padding: 15px; background: white; cursor: pointer; transition: all 0.3s; margin-bottom: 8px; position: relative;" 
                                 onmouseover="this.style.borderColor='#003471'; this.style.boxShadow='0 2px 8px rgba(0,52,113,0.2)'"
                                 onmouseout="this.style.borderColor='#e0e7ff'; this.style.boxShadow='none'">
                                <div class="fw-semibold" style="color: #003471; font-size: 14px; margin-bottom: 6px;">${fee.title}</div>
                                <div style="color: #6c757d; font-size: 12px; margin-bottom: 4px;">Total: Rs. ${total.toFixed(2)}</div>
                                <div style="color: #dc3545; font-size: 14px; font-weight: 600; margin-bottom: 10px;">Due: Rs. ${remaining.toFixed(2)}</div>
                                <button class="btn btn-sm btn-warning w-100" onclick="openPartialPaymentFromCard('${studentCode}', '${studentName || ''}', '${fee.title.replace(/'/g, "\\'")}', ${remaining}, '${student.campus || ''}', ${fee.generated_id || 'null'}, ${generatedFee})" style="padding: 6px 12px; font-size: 12px; color: white !important;">
                                    <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle; color: white;">account_balance_wallet</span>
                                    <span style="color: white;">Partial Payment</span>
                                </button>
                            </div>
                        `;
                        feeCardsRow.appendChild(cardCol);
                    }
                });
            } else {
                feeCardsRow.innerHTML = '<div class="col-12 text-center text-muted py-4">No unpaid fees found.</div>';
            }
            
            // Show modal
            const modal = new bootstrap.Modal(unpaidModal);
            modal.show();
        } else {
            alert('Student not found or no fee data available.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error loading student fee data. Please try again.');
    });
}

// Function to open Partial Payment modal from fee card
function openPartialPaymentFromCard(studentCode, studentName, feeTitle, feeDue, campus, generatedId = null, generatedFee = null) {
    // Close unpaid fees modal
    const unpaidModal = bootstrap.Modal.getInstance(document.getElementById('unpaidFeesModal'));
    if (unpaidModal) {
        unpaidModal.hide();
    }
    
    // Prepare studentData with specific fee information
    // feeDue here should be the remaining Due Amount, not the Generated Fee
    const studentData = {
        student_code: studentCode,
        student_name: studentName,
        campus: campus || 'N/A',
        fee_title: feeTitle,
        fee_due: feeDue, // This is the remaining Due Amount
        generated_fee: generatedFee || feeDue, // Generated Fee (after discount) for reference
        monthly_fee: 0, // Not using monthly_fee for card-based payment
        generated_id: generatedId
    };
    
    // Open Partial Payment modal with card-specific data
    openPartialPaymentModal(studentCode, studentName, studentData);
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
                student_code: studentCode,
                payment_title: studentData && studentData.payment_title ? studentData.payment_title : null,
                generated_id: studentData && studentData.generated_id ? studentData.generated_id : null
            })
        })
        .then(response => response.json())
        .then(data => {
            document.body.removeChild(loadingMsg);
            
            if (data.success) {
                // Update button status from Unpaid to Paid
                updatePaymentStatus(studentCode, false);
                
                // Add payment directly to Latest Payments if available
                if (data.data && data.data.payment) {
                    addLatestPaymentRow(data.data.payment);
                } else {
                    // Fallback: refresh from server
                    refreshLatestPaymentsForStudent(studentCode);
                }
                
                refreshSearchResultsAfterPayment();
                alert('Payment recorded successfully!');
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
    
    // For without late fee, create payment automatically (without late fees)
    if (paymentType === 'without_late_fee') {
        // Confirm before processing
        if (!confirm(`Are you sure you want to process payment without late fee for ${studentName} (${studentCode})?`)) {
            return;
        }
        
        // Show loading
        const loadingMsg = document.createElement('div');
        loadingMsg.id = 'payment-loading';
        loadingMsg.style.cssText = 'position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.3); z-index: 9999;';
        loadingMsg.innerHTML = '<div class="spinner-border text-primary" role="status"></div><p class="mt-2 mb-0">Processing payment without late fee...</p>';
        document.body.appendChild(loadingMsg);
        
        // Make AJAX call to create payment without late fee
        fetch('{{ route("fee-payment.payment-without-late-fee") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                student_code: studentCode,
                payment_title: studentData && studentData.payment_title ? studentData.payment_title : null,
                generated_id: studentData && studentData.generated_id ? studentData.generated_id : null
            })
        })
        .then(response => response.json())
        .then(data => {
            document.body.removeChild(loadingMsg);
            
            if (data.success) {
                // Update button status from Unpaid to Paid
                updatePaymentStatus(studentCode, false);
                
                // Add payment directly to Latest Payments if available
                if (data.data && data.data.payment) {
                    addLatestPaymentRow(data.data.payment);
                } else {
                    // Fallback: refresh from server
                    refreshLatestPaymentsForStudent(studentCode);
                }
                
                refreshSearchResultsAfterPayment();
                alert('Payment recorded successfully without late fee!');
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
}

function openPartialPaymentModal(studentCode, studentName, studentData) {
    // Store studentData globally to preserve it
    window.partialPaymentStudentData = studentData;
    
    // Populate modal fields
    const campusValue = studentData.campus || 'N/A';
    document.getElementById('partial_campus').value = campusValue;
    
    // Get the actual due amount (remaining amount to be paid)
    // fee_due should be the remaining due amount, not the generated fee
    const dueAmount = (studentData.fee_due !== undefined && studentData.fee_due !== null)
        ? parseFloat(studentData.fee_due || 0)
        : 0;
    
    // Get generated fee (for reference/calculation if needed)
    const generatedFee = (studentData.generated_fee !== undefined && studentData.generated_fee !== null)
        ? parseFloat(studentData.generated_fee || 0)
        : (studentData.fee_due !== undefined && studentData.fee_due !== null)
            ? parseFloat(studentData.fee_due || 0)
            : parseFloat(studentData.monthly_fee || 0);
    
    // Store generated fee globally for dynamic calculation
    window.partialPaymentGeneratedFee = generatedFee;
    
    // Set initial due amount to the actual remaining due amount
    document.getElementById('partial_due_amount').value = 'Rs. ' + dueAmount.toFixed(2);
    document.getElementById('partial_fee_title').value = studentData.fee_title || '';
    document.getElementById('partial_student_code').value = studentCode;
    
    // Load students for the selected campus
    if (campusValue && campusValue !== 'N/A') {
        loadStudentsForCampus(campusValue, function() {
            // After students are loaded, select the student
            const studentSelect = document.getElementById('partial_student');
            const option = Array.from(studentSelect.options).find(opt => opt.value === studentCode);
            if (option) {
                studentSelect.value = studentCode;
                // Don't trigger change event to avoid overwriting due amount
                // The due amount is already set from studentData above
            } else {
                // If student not found in dropdown, set hidden field directly
                document.getElementById('partial_student_code').value = studentCode;
            }
        });
    }
    
    // If paymentAmount is provided from Fee Calculator, pre-fill it
    if (studentData.paymentAmount !== undefined && studentData.paymentAmount !== null) {
        const paymentAmount = parseFloat(studentData.paymentAmount || 0);
        document.getElementById('partial_payment').value = paymentAmount.toFixed(2);
        // Update due amount: Current Due Amount - Payment Amount
        const remainingDue = Math.max(0, dueAmount - paymentAmount);
        document.getElementById('partial_due_amount').value = 'Rs. ' + remainingDue.toFixed(2);
    } else {
        document.getElementById('partial_payment').value = '';
    }
    
    document.getElementById('partial_discount').value = '0';
    document.getElementById('partial_method').value = 'Cash Payment';
    document.getElementById('partial_date').value = new Date().toISOString().split('T')[0];
    document.getElementById('partial_notify').value = 'Yes';
    
    // Add event listener to payment input to update due amount dynamically
    const paymentInput = document.getElementById('partial_payment');
    if (paymentInput) {
        // Remove existing listeners by cloning and replacing
        const newPaymentInput = paymentInput.cloneNode(true);
        paymentInput.parentNode.replaceChild(newPaymentInput, paymentInput);
        
        // Add new event listener
        newPaymentInput.addEventListener('input', function() {
            const paymentAmount = parseFloat(this.value || 0);
            // Get current due amount from the field (initial due amount)
            const currentDueAmount = parseFloat(document.getElementById('partial_due_amount').value.replace('Rs. ', '').replace(/,/g, '') || dueAmount || 0);
            // Calculate remaining due: Current Due - Payment Amount
            const remainingDue = Math.max(0, currentDueAmount - paymentAmount);
            document.getElementById('partial_due_amount').value = 'Rs. ' + remainingDue.toFixed(2);
        });
    }
    
    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('partialPaymentModal'));
    modal.show();
}

// Load students by campus for Partial Payment modal
function loadStudentsForCampus(campus, callback) {
    const studentSelect = document.getElementById('partial_student');
    studentSelect.innerHTML = '<option value="">Loading...</option>';
    
    fetch(`{{ url('/accounting/get-students-by-campus') }}?campus=${encodeURIComponent(campus)}`)
        .then(response => response.json())
        .then(data => {
            studentSelect.innerHTML = '<option value="">Select Student</option>';
            if (data.success && data.students && data.students.length > 0) {
                data.students.forEach(student => {
                    const option = document.createElement('option');
                    option.value = student.student_code;
                    option.textContent = `${student.student_name} (${student.student_code})`;
                    studentSelect.appendChild(option);
                });
            }
            if (callback) callback();
        })
        .catch(error => {
            console.error('Error loading students:', error);
            studentSelect.innerHTML = '<option value="">Error loading students</option>';
            if (callback) callback();
        });
}

// Handle campus change in Partial Payment modal
document.addEventListener('DOMContentLoaded', function() {
    const partialCampusSelect = document.getElementById('partial_campus');
    const partialStudentSelect = document.getElementById('partial_student');
    
    if (partialCampusSelect) {
        partialCampusSelect.addEventListener('change', function() {
            const campus = this.value;
            if (campus) {
                loadStudentsForCampus(campus);
                // Clear student-related fields
                partialStudentSelect.value = '';
                document.getElementById('partial_student_code').value = '';
                document.getElementById('partial_fee_title').value = '';
                document.getElementById('partial_due_amount').value = '';
            } else {
                partialStudentSelect.innerHTML = '<option value="">Select Student</option>';
                document.getElementById('partial_student_code').value = '';
                document.getElementById('partial_fee_title').value = '';
                document.getElementById('partial_due_amount').value = '';
            }
        });
    }
    
    if (partialStudentSelect) {
        partialStudentSelect.addEventListener('change', function() {
            const studentCode = this.value;
            if (studentCode) {
                const campus = document.getElementById('partial_campus').value;
                document.getElementById('partial_student_code').value = studentCode;
                
                // Check if we have stored studentData (from openPartialPaymentModal)
                // If yes, use that data instead of making AJAX call
                if (window.partialPaymentStudentData && window.partialPaymentStudentData.fee_due !== undefined) {
                    // Get the actual due amount (remaining amount to be paid)
                    const dueAmount = (window.partialPaymentStudentData.fee_due !== undefined && window.partialPaymentStudentData.fee_due !== null)
                        ? parseFloat(window.partialPaymentStudentData.fee_due || 0)
                        : 0;
                    
                    // Get generated fee (for reference if needed)
                    const generatedFee = (window.partialPaymentStudentData.generated_fee !== undefined && window.partialPaymentStudentData.generated_fee !== null)
                        ? parseFloat(window.partialPaymentStudentData.generated_fee || 0)
                        : dueAmount;
                    
                    // Store generated fee for dynamic calculation
                    window.partialPaymentGeneratedFee = generatedFee;
                    
                    // Get current payment amount
                    const paymentAmount = parseFloat(document.getElementById('partial_payment').value || 0);
                    // Calculate remaining due: Current Due Amount - Payment Amount
                    const remainingDue = Math.max(0, dueAmount - paymentAmount);
                    
                    document.getElementById('partial_due_amount').value = 'Rs. ' + remainingDue.toFixed(2);
                    if (window.partialPaymentStudentData.fee_title) {
                        document.getElementById('partial_fee_title').value = window.partialPaymentStudentData.fee_title;
                    }
                    // Clear stored data after using it
                    window.partialPaymentStudentData = null;
                    return;
                }
                
                // Load student details via AJAX only if no stored data
                fetch(`{{ url('/accounting/get-student-by-code') }}?student_code=${encodeURIComponent(studentCode)}&campus=${encodeURIComponent(campus)}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && data.student) {
                            // Auto-populate Fee Title and Due Amount
                            if (data.fee_title) {
                                document.getElementById('partial_fee_title').value = data.fee_title;
                            }
                            
                            // Get the due amount (remaining amount to be paid)
                            // fee_due from AJAX response should be the remaining due amount
                            let dueAmount = 0;
                            if (data.fee_due !== undefined && data.fee_due !== null && data.fee_due > 0) {
                                dueAmount = parseFloat(data.fee_due);
                            }
                            
                            // Get generated fee (for reference/calculation if needed)
                            let generatedFee = 0;
                            if (data.generated_fee !== undefined && data.generated_fee !== null && data.generated_fee > 0) {
                                generatedFee = parseFloat(data.generated_fee);
                            } else if (data.student.monthly_fee) {
                                generatedFee = parseFloat(data.student.monthly_fee);
                            } else {
                                generatedFee = dueAmount; // Fallback to due amount if no generated fee
                            }
                            
                            // Store generated fee for dynamic calculation
                            window.partialPaymentGeneratedFee = generatedFee;
                            
                            // Set due amount (this is the remaining amount to be paid)
                            document.getElementById('partial_due_amount').value = 'Rs. ' + dueAmount.toFixed(2);
                            
                            // Get current payment amount and calculate remaining due
                            const paymentAmount = parseFloat(document.getElementById('partial_payment').value || 0);
                            if (paymentAmount > 0) {
                                const remainingDue = Math.max(0, dueAmount - paymentAmount);
                                document.getElementById('partial_due_amount').value = 'Rs. ' + remainingDue.toFixed(2);
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Error loading student details:', error);
                    });
            } else {
                document.getElementById('partial_student_code').value = '';
                document.getElementById('partial_fee_title').value = '';
                document.getElementById('partial_due_amount').value = '';
                window.partialPaymentStudentData = null;
            }
        });
    }
    
    // Reset form when modal is closed
    const partialPaymentModal = document.getElementById('partialPaymentModal');
    if (partialPaymentModal) {
        partialPaymentModal.addEventListener('hidden.bs.modal', function() {
            document.getElementById('partial_campus').value = '';
            partialStudentSelect.innerHTML = '<option value="">Select Student</option>';
            document.getElementById('partial_student_code').value = '';
            document.getElementById('partial_fee_title').value = '';
            document.getElementById('partial_due_amount').value = '';
            document.getElementById('partial_payment').value = '';
            document.getElementById('partial_discount').value = '0';
            document.getElementById('partial_method').value = 'Cash Payment';
            document.getElementById('partial_date').value = new Date().toISOString().split('T')[0];
            document.getElementById('partial_notify').value = 'Yes';
            // Clear stored studentData
            window.partialPaymentStudentData = null;
        });
    }
});

function updatePaymentStatus(studentCode, hasUnpaid) {
    // Find all rows with this student code and update the button
    const rows = document.querySelectorAll('#searchResultsBody tr');
    rows.forEach(row => {
        const codeCell = row.querySelector('td:first-child strong');
        if (codeCell && codeCell.textContent.trim() === studentCode) {
            const statusCell = row.querySelector('.status-cell');
            if (statusCell) {
                // Find existing status button
                const statusButton = statusCell.querySelector('.btn-danger, .btn-success, .btn-warning');
                if (statusButton) {
                    if (!hasUnpaid) {
                        // Change to Paid button
                        statusButton.className = 'btn btn-sm btn-success';
                        statusButton.innerHTML = '<span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle; color: white;">check_circle</span><span style="color: white;">Paid</span>';
                        statusButton.onclick = null;
                        statusButton.title = 'Payment completed';
                    }
                } else {
                    statusCell.innerHTML = '<button class="btn btn-sm btn-success" style="padding: 4px 12px; font-size: 12px; color: white !important;" title="Payment completed"><span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle; color: white;">check_circle</span><span style="color: white;">Paid</span></button>';
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
            const statusCell = row.querySelector('.status-cell');
            if (statusCell) {
                // Find existing status button
                const statusButton = statusCell.querySelector('.btn-danger, .btn-success, .btn-warning');
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
                    if (!hasUnpaid || unpaidAmount <= 0) {
                        statusCell.innerHTML = '<button class="btn btn-sm btn-success" style="padding: 4px 12px; font-size: 12px; color: white !important;" title="Payment completed"><span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle; color: white;">check_circle</span><span style="color: white;">Paid</span></button>';
                    } else {
                        statusCell.innerHTML = '<button class="btn btn-sm btn-danger" style="padding: 4px 12px; font-size: 12px; color: white !important;" title="Unpaid Amount: ' + parseFloat(unpaidAmount || 0).toFixed(2) + '"><span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle; color: white;">warning</span><span style="color: white;">Unpaid</span></button>';
                    }
                }
            }
        }
    });
}

function parsePaymentDateTime(dateTime) {
    if (!dateTime) {
        return { date: 'N/A', time: 'N/A' };
    }
    const parts = String(dateTime).split(' ');
    const date = parts[0] || 'N/A';
    const time = parts.length > 1 ? parts.slice(1).join(' ') : 'N/A';
    return { date, time };
}

function buildLatestPaymentActionDropdown(payment) {
    const paymentId = payment.id || '';
    const studentCode = payment.student_code || '';
    const studentName = (payment.student_name || '').replace(/'/g, "\\'");
    const feeTitle = (payment.payment_title || '').replace(/'/g, "\\'");
    return `
        <div class="btn-group" style="position: static;">
            <button type="button" class="btn btn-sm" data-bs-toggle="dropdown" aria-expanded="false" style="padding: 2px 6px; border: none; background: #000; color: #fff; border-radius: 4px;" title="Actions">
                <span class="material-symbols-outlined" style="font-size: 18px; vertical-align: middle; color: #fff;">arrow_drop_down</span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end" style="position: absolute; z-index: 1050;">
                <li><a class="dropdown-item" href="#" onclick="particularReceipt('${studentCode}', '${studentName}'); return false;">
                    <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle; margin-right: 5px;">print</span>
                    Print
                </a></li>
                <li><a class="dropdown-item" href="#" onclick="editPayment('${studentCode}', '${feeTitle}'); return false;">
                    <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle; margin-right: 5px;">edit</span>
                    Edit
                </a></li>
                <li><a class="dropdown-item text-danger" href="#" onclick="deletePayment('${paymentId}'); return false;">
                    <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle; margin-right: 5px;">delete</span>
                    Delete
                </a></li>
            </ul>
        </div>
    `;
}

function addLatestPaymentRow(payment) {
    // Skip only unpaid installments (method = 'Generated' or 'Installment')
    // Paid installments should appear in Latest Payments
    const paymentTitle = (payment.payment_title || '').toLowerCase();
    const paymentMethod = (payment.method || '').toLowerCase();
    const isInstallment = paymentTitle.includes('installment') || paymentTitle.match(/\/\d+$/);
    const isUnpaidInstallment = isInstallment && (paymentMethod === 'generated' || paymentMethod === 'installment');
    
    if (isUnpaidInstallment) {
        return;
    }
    
    const container = document.getElementById('latestPaymentsContainer');
    if (!container) return;

    const emptyRow = document.getElementById('latestPaymentsEmpty');
    if (emptyRow) {
        emptyRow.closest('.default-table-area')?.remove();
    }

    const studentCode = payment.student_code || 'N/A';
    const key = [
        payment.student_code,
        payment.payment_title,
        payment.payment_date,
        payment.payment_amount,
        payment.discount,
        payment.late_fee,
    ].join('|');

    let tbody = document.getElementById(`latestPaymentsBody_${studentCode}`);
    if (!tbody) {
        const studentName = payment.student_name || 'N/A';
        const fatherName = payment.father_name || 'N/A';
        const wrapper = document.createElement('div');
        wrapper.className = 'mb-3';
        wrapper.id = `latestPaymentsStudent_${studentCode}`;
        wrapper.innerHTML = `
            <div class="fw-semibold mb-2" style="color: #003471;">
                ${studentName} (${studentCode}) - ${fatherName}
            </div>
            <div class="default-table-area" style="margin-top: 0;">
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead>
                            <tr>
                                <th style="padding: 8px 12px; font-size: 13px;">Student Code</th>
                                <th style="padding: 8px 12px; font-size: 13px;">Student</th>
                                <th style="padding: 8px 12px; font-size: 13px;">Parent</th>
                                <th style="padding: 8px 12px; font-size: 13px;">Title</th>
                                <th style="padding: 8px 12px; font-size: 13px;">Amount Paid</th>
                                <th style="padding: 8px 12px; font-size: 13px;">Late Fee</th>
                                <th style="padding: 8px 12px; font-size: 13px;">Discount</th>
                                <th style="padding: 8px 12px; font-size: 13px;">Date</th>
                                <th style="padding: 8px 12px; font-size: 13px;">Time</th>
                                <th style="padding: 8px 12px; font-size: 13px;">Received By</th>
                                <th style="padding: 8px 12px; font-size: 13px;">Status</th>
                                <th style="padding: 8px 12px; font-size: 13px;">Action</th>
                            </tr>
                        </thead>
                        <tbody id="latestPaymentsBody_${studentCode}" data-student-code="${studentCode}"></tbody>
                        <tfoot>
                            <tr>
                                <td colspan="4" class="text-end fw-semibold">Total</td>
                                <td class="fw-semibold" data-student-total="${studentCode}">0.00</td>
                                <td colspan="7"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        `;
        container.prepend(wrapper);
        tbody = document.getElementById(`latestPaymentsBody_${studentCode}`);
    }

    if (!tbody || tbody.querySelector(`tr[data-key="${key}"]`)) {
        return;
    }

    const { date, time } = parsePaymentDateTime(payment.payment_date);
    const row = document.createElement('tr');
    row.setAttribute('data-key', key);
    row.setAttribute('data-payment-id', payment.id || '');
    row.setAttribute('data-student-code', studentCode);
    row.setAttribute('data-amount', parseFloat(payment.payment_amount || 0).toFixed(2));
    row.innerHTML = `
        <td style="padding: 8px 12px; font-size: 13px;">
            <strong>${payment.student_code || 'N/A'}</strong>
        </td>
        <td style="padding: 8px 12px; font-size: 13px;">
            ${payment.student_name || 'N/A'}
        </td>
        <td style="padding: 8px 12px; font-size: 13px;">
            ${payment.father_name || 'N/A'}
        </td>
        <td style="padding: 8px 12px; font-size: 13px;">
            ${payment.payment_title || 'N/A'}
        </td>
        <td style="padding: 8px 12px; font-size: 13px;">
            <strong style="color: #28a745;">${parseFloat(payment.payment_amount || 0).toFixed(2)}</strong>
        </td>
        <td style="padding: 8px 12px; font-size: 13px;">
            ${parseFloat(payment.late_fee || 0) > 0 ? `<span style="color: #dc3545; font-weight: 600;">${parseFloat(payment.late_fee || 0).toFixed(2)}</span>` : '<span style="color: #6c757d;">0.00</span>'}
        </td>
        <td style="padding: 8px 12px; font-size: 13px;">
            ${parseFloat(payment.discount || 0) > 0 ? `<span style="color: #ff9800; font-weight: 600;">${parseFloat(payment.discount || 0).toFixed(2)}</span>` : '<span style="color: #6c757d;">0.00</span>'}
        </td>
        <td style="padding: 8px 12px; font-size: 13px;">
            ${date}
        </td>
        <td style="padding: 8px 12px; font-size: 13px;">
            ${time}
        </td>
        <td style="padding: 8px 12px; font-size: 13px;">
            ${payment.accountant || payment.received_by || 'N/A'}
        </td>
        <td style="padding: 8px 12px; font-size: 13px;">
            ${(payment.method && payment.method === 'Installment') || (payment.payment_title && /\/\d+$/.test(payment.payment_title)) ? 
                '<span class="badge bg-info text-white" style="background-color: #0dcaf0 !important;"><span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">payments</span> Installment</span>' : 
                '<span class="badge bg-success">Paid</span>'}
        </td>
        <td style="padding: 8px 12px; font-size: 13px;">
            ${buildLatestPaymentActionDropdown(payment)}
        </td>
    `;
    tbody.insertBefore(row, tbody.firstChild);

    const totalCell = container.querySelector(`[data-student-total="${studentCode}"]`);
    if (totalCell) {
        const currentTotal = parseFloat(totalCell.textContent || '0') || 0;
        const newTotal = currentTotal + parseFloat(payment.payment_amount || 0);
        totalCell.textContent = newTotal.toFixed(2);
    }
}

function refreshLatestPaymentsForStudent(studentCode) {
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
        if (data.success && Array.isArray(data.payments)) {
            // Filter out only unpaid installments (method = 'Generated' or 'Installment')
            // Paid installments should appear in Latest Payments
            const nonInstallmentPayments = data.payments.filter(payment => {
                const paymentTitle = (payment.payment_title || '').toLowerCase();
                const paymentMethod = (payment.method || '').toLowerCase();
                const isInstallment = paymentTitle.includes('installment') || paymentTitle.match(/\/\d+$/);
                const isUnpaidInstallment = isInstallment && (paymentMethod === 'generated' || paymentMethod === 'installment');
                return !isUnpaidInstallment;
            });
            
            // Get existing payment IDs to avoid duplicates
            const container = document.getElementById('latestPaymentsContainer');
            const existingPaymentIds = new Set();
            if (container) {
                container.querySelectorAll('[data-payment-id]').forEach(row => {
                    const paymentId = row.getAttribute('data-payment-id');
                    if (paymentId) {
                        existingPaymentIds.add(paymentId);
                    }
                });
            }
            
            // Add only new payments (not already in the list)
            for (let i = nonInstallmentPayments.length - 1; i >= 0; i -= 1) {
                const payment = nonInstallmentPayments[i];
                if (!existingPaymentIds.has(String(payment.id))) {
                    addLatestPaymentRow(payment);
                }
            }
        }
    })
    .catch(error => {
        console.error('Error loading payment history:', error);
    });
}

function renderLatestPaymentsForStudents(students) {
    const container = document.getElementById('latestPaymentsContainer');
    if (!container) return;

    container.innerHTML = '';
    const studentCodes = Array.from(new Set((students || [])
        .map((student) => student.student_code)
        .filter((code) => code)));

    if (studentCodes.length === 0) {
        container.innerHTML = `
            <div class="default-table-area" style="margin-top: 0;">
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <tbody>
                            <tr>
                                <td colspan="12" class="text-center py-4" id="latestPaymentsEmpty">
                                    <div class="d-flex flex-column align-items-center gap-2">
                                        <span class="material-symbols-outlined" style="font-size: 48px; color: #dee2e6;">payments</span>
                                        <p class="text-muted mb-0">No payments found.</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        `;
        return;
    }

    Promise.all(studentCodes.map((code) => fetch(`{{ route('fee-payment.history') }}?student_code=${encodeURIComponent(code)}`, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
        }
    }).then(response => response.json()).catch(() => null)))
    .then((results) => {
        let hasPayments = false;
        results.forEach((data) => {
            if (data && data.success && Array.isArray(data.payments)) {
                data.payments.forEach((payment) => {
                    // Exclude only unpaid installments from latest payments
                    // Paid installments should appear
                    const paymentTitle = (payment.payment_title || '').toLowerCase();
                    const paymentMethod = (payment.method || '').toLowerCase();
                    const isInstallment = paymentTitle.includes('installment') || paymentTitle.match(/\/\d+$/);
                    const isUnpaidInstallment = isInstallment && (paymentMethod === 'generated' || paymentMethod === 'installment');
                    
                    if (!isUnpaidInstallment) {
                        addLatestPaymentRow(payment);
                        hasPayments = true;
                    }
                });
            }
        });

        if (!hasPayments) {
            container.innerHTML = `
                <div class="default-table-area" style="margin-top: 0;">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <tbody>
                                <tr>
                                    <td colspan="12" class="text-center py-4" id="latestPaymentsEmpty">
                                        <div class="d-flex flex-column align-items-center gap-2">
                                            <span class="material-symbols-outlined" style="font-size: 48px; color: #dee2e6;">payments</span>
                                            <p class="text-muted mb-0">No payments found.</p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            `;
        }
    })
    .catch((error) => {
        console.error('Error loading payment history:', error);
    });
}

function deletePayment(paymentId) {
    if (!paymentId) return;
    if (!confirm('Are you sure you want to delete this fee?')) {
        return;
    }
    const deleteUrl = '{{ route("fee-payment.payment.delete", ":id") }}'.replace(':id', paymentId);
    fetch(deleteUrl, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
        }
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) {
            alert(data.message || 'Failed to delete fee.');
            return;
        }
        const row = document.querySelector(`tr[data-payment-id="${paymentId}"]`);
        if (!row) return;
        const studentCode = row.getAttribute('data-student-code');
        const amount = parseFloat(row.getAttribute('data-amount') || '0') || 0;
        row.remove();

        const totalCell = document.querySelector(`[data-student-total="${studentCode}"]`);
        if (totalCell) {
            const currentTotal = parseFloat(totalCell.textContent || '0') || 0;
            const newTotal = Math.max(0, currentTotal - amount);
            totalCell.textContent = newTotal.toFixed(2);
        }

        const tbody = document.getElementById(`latestPaymentsBody_${studentCode}`);
        if (tbody && tbody.children.length === 0) {
            document.getElementById(`latestPaymentsStudent_${studentCode}`)?.remove();
        }
    })
    .catch(error => {
        console.error('Error deleting fee:', error);
        alert('Error deleting fee. Please try again.');
    });
}

function deleteFeePayment(paymentId, studentCode, feeTitle) {
    if (!paymentId) {
        alert('Payment ID not found. Cannot delete this payment.');
        return;
    }
    
    // Determine if this is a generated fee, installment, or paid fee based on context
    const isGeneratedFee = feeTitle && feeTitle.toLowerCase().includes('generated');
    const isInstallment = feeTitle && (feeTitle.match(/\/\d+$/) || feeTitle.toLowerCase().includes('installment'));
    const message = isInstallment
        ? `Are you sure you want to delete this installment?\n\nStudent: ${studentCode}\nFee: ${feeTitle}\n\nNote: This will delete the installment record.`
        : (isGeneratedFee 
            ? `Are you sure you want to delete this generated fee?\n\nStudent: ${studentCode}\nFee: ${feeTitle}\n\nNote: This will delete the generated fee record. Any payments made against this fee will also be affected.`
            : `Are you sure you want to delete this payment?\n\nStudent: ${studentCode}\nFee: ${feeTitle}\n\nNote: Only the payment record will be deleted. The student record will remain intact.`);
    
    if (!confirm(message)) {
        return;
    }
    
    const deleteUrl = '{{ route("fee-payment.payment.delete", ":id") }}'.replace(':id', paymentId);
    fetch(deleteUrl, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
        }
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) {
            alert(data.message || 'Failed to delete payment.');
            return;
        }
        
        // Show success message
        alert(data.message || 'Payment deleted successfully.');
        
        // Refresh search results
        const searchInput = document.getElementById('searchByName');
        const searchByCnicInput = document.getElementById('searchByCNIC');
        
        if (searchInput && searchInput.value.trim()) {
            searchByName();
        } else if (searchByCnicInput && searchByCnicInput.value.trim()) {
            searchByCNIC();
        }
    })
    .catch(error => {
        console.error('Error deleting payment:', error);
        alert('Error deleting payment. Please try again.');
    });
}

function editPayment(studentCode, paymentTitle) {
    const url = '{{ route("accounting.direct-payment.student") }}'
        + `?student_code=${encodeURIComponent(studentCode)}`
        + `&payment_title=${encodeURIComponent(paymentTitle || '')}`;
    window.location.href = url;
}

function refreshSearchResultsAfterPayment() {
    if (!window.lastFeeSearch || !window.lastFeeSearch.value) {
        return;
    }
    if (window.lastFeeSearch.type === 'name') {
        const input = document.getElementById('searchByName');
        if (input) input.value = window.lastFeeSearch.value;
        searchByName();
    } else if (window.lastFeeSearch.type === 'cnic') {
        const input = document.getElementById('searchByCNIC');
        if (input) input.value = window.lastFeeSearch.value;
        searchByCNIC();
    }
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

            if (result.isJson && result.data && result.data.payment) {
                addLatestPaymentRow(result.data.payment);
                if (!result.data.payment.accountant) {
                    refreshLatestPaymentsForStudent(studentCode);
                }
            } else {
                refreshLatestPaymentsForStudent(studentCode);
            }
            refreshSearchResultsAfterPayment();
            
            // Fetch updated student data to check unpaid amount
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
                    // Update payment status based on actual unpaid amount
                    updatePaymentStatusWithData(studentCode, student.has_unpaid, student.unpaid_amount);
                } else {
                    // If can't fetch data, just update status
                    updatePaymentStatus(studentCode, false);
                }
            })
            .catch(error => {
                console.error('Error fetching updated data:', error);
                // If error, just update status
                updatePaymentStatus(studentCode, false);
            });
            
            alert('Payment recorded successfully!');
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
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

function searchByCNIC() {
    const searchValue = document.getElementById('searchByCNIC')?.value.trim() || '';
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
    latestPaymentsSection.style.display = 'block';
    searchResultsBody.innerHTML = '<tr><td colspan="13" class="text-center py-4"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div><p class="mt-2 text-muted">Searching...</p></td></tr>';

    // Make AJAX call to search students by CNIC
    fetch(`{{ route('fee-payment.search-by-cnic') }}?cnic=${encodeURIComponent(searchValue)}`, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
        }
    })
    .then(response => {
        // Check if response is ok
        if (!response.ok) {
            return response.text().then(text => {
                throw new Error(`Server error: ${response.status} ${response.statusText}`);
            });
        }
        // Check if response is JSON
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            return response.text().then(text => {
                throw new Error('Server returned non-JSON response');
            });
        }
        return response.json();
    })
    .then(data => {
        searchResultsBody.innerHTML = '';
        
        if (data.success && data.students && data.students.length > 0) {
            const grandTotals = { total: 0, discount: 0, late: 0, paid: 0, due: 0, generated: 0 };
            data.students.forEach((student) => {
                const feeRows = Array.isArray(student.fee_rows) && student.fee_rows.length > 0
                    ? student.fee_rows
                    : [{
                        title: 'No Fee Generated',
                        total: 0,
                        discount: 0,
                        late_fee: 0,
                        paid: 0,
                        due: 0,
                        is_empty: true,
                    }];

                feeRows.forEach((fee) => {
                    const feeTitleSafe = (fee.title || '').replace(/'/g, "\\'");
                    const total = parseFloat(fee.total || 0);
                    const discount = parseFloat(fee.discount || 0);
                    const lateFee = parseFloat(fee.late_fee || 0);
                    const paid = parseFloat(fee.paid || 0);
                    const due = parseFloat(fee.due || 0);
                    // Generated fee = total - discount (fee after discount is applied)
                    const generatedFee = parseFloat(fee.generated_fee || (total - discount));
                    const isEmptyFee = !!fee.is_empty;
                    const isInstallment = !!fee.is_installment;
                    const paidForStatus = paid;
                    const paidDisplay = paid;
                    grandTotals.total += total;
                    grandTotals.discount += discount;
                    grandTotals.late += lateFee;
                    grandTotals.paid += paid;
                    grandTotals.due += due;
                    grandTotals.generated += generatedFee;
                    
                    // Add installment badge to title if it's an installment
                    const feeTitleDisplay = isInstallment 
                        ? `${fee.title || 'N/A'} <span class="badge bg-info text-white ms-2" style="font-size: 10px; padding: 2px 6px;">Installment</span>`
                        : (fee.title || 'N/A');
                    
                    const row = document.createElement('tr');
                    // Add background color for installment rows
                    if (isInstallment) {
                        row.style.backgroundColor = '#f0f8ff';
                    }
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
                            ${feeTitleDisplay}
                        </td>
                        <td style="padding: 8px 12px; font-size: 13px;">
                            ${total.toFixed(2)}
                        </td>
                        <td style="padding: 8px 12px; font-size: 13px;">
                            ${discount.toFixed(2)}
                        </td>
                        <td style="padding: 8px 12px; font-size: 13px;">
                            ${lateFee.toFixed(2)}
                        </td>
                        <td style="padding: 8px 12px; font-size: 13px;">
                            ${paidDisplay.toFixed(2)}
                        </td>
                        <td style="padding: 8px 12px; font-size: 13px;">
                            ${due.toFixed(2)}
                        </td>
                        <td style="padding: 8px 12px; font-size: 13px;" class="status-cell">
                            ${isEmptyFee ? '<span class="badge bg-secondary">N/A</span>' : renderStatusCell(due, paidForStatus, student.student_code, student.student_name, isInstallment)}
                        </td>
                        <td style="padding: 8px 12px; font-size: 13px; position: relative; overflow: visible;">
                            <div class="btn-group" style="position: static;">
                                <button type="button" class="btn btn-sm btn-primary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" style="padding: 4px 12px; font-size: 12px; color: white !important;">
                                    <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle; color: white;">payments</span>
                                    <span style="color: white;">Take Payment</span>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end" style="position: absolute; z-index: 1050;">
                                    <li><a class="dropdown-item" href="#" onclick="takePayment('${student.student_code}', '${student.student_name}', 'full', {payment_title: '${feeTitleSafe}', generated_id: ${fee.generated_id ? fee.generated_id : 'null'}, payment_id: ${fee.payment_id ? fee.payment_id : 'null'}, is_installment: ${isInstallment ? 'true' : 'false'}}); return false;">
                                        <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle; margin-right: 5px;">check_circle</span>
                                        Full Payment
                                    </a></li>
                                    <li><a class="dropdown-item" href="#" onclick="takePayment('${student.student_code}', '${student.student_name}', 'partial', {student_code: '${student.student_code}', student_name: '${student.student_name}', campus: '${student.campus || ''}', monthly_fee: ${student.monthly_fee || 0}, fee_title: '${feeTitleSafe}', fee_due: ${due || 0}, generated_fee: ${generatedFee || 0}, payment_id: ${fee.payment_id ? fee.payment_id : 'null'}, is_installment: ${isInstallment ? 'true' : 'false'}}); return false;">
                                        <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle; margin-right: 5px;">account_balance_wallet</span>
                                        Partial Payment
                                    </a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="#" onclick="takePayment('${student.student_code}', '${student.student_name}', 'without_late_fee', {payment_title: '${feeTitleSafe}', generated_id: ${fee.generated_id ? fee.generated_id : 'null'}, payment_id: ${fee.payment_id ? fee.payment_id : 'null'}, is_installment: ${isInstallment ? 'true' : 'false'}}); return false;">
                                        <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle; margin-right: 5px;">remove_circle</span>
                                        Pay without late fee
                                    </a></li>
                                </ul>
                            </div>
                        </td>
                        <td style="padding: 8px 12px; font-size: 13px; position: relative; overflow: visible;">
                            <div class="btn-group" style="position: static;">
                                <button type="button" class="btn btn-sm" data-bs-toggle="dropdown" aria-expanded="false" style="padding: 2px 6px; border: none; background: #000; color: #fff; border-radius: 4px;" title="More Options">
                                    <span class="material-symbols-outlined" style="font-size: 18px; vertical-align: middle; color: #fff;">arrow_drop_down</span>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end" style="position: absolute; z-index: 1050;">
                                    <li><a class="dropdown-item" href="#" onclick="printVoucher('${student.student_code}', '${student.student_name}'); return false;">
                                        <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle; margin-right: 5px;">print</span>
                                        Print Voucher
                                    </a></li>
                                    ${!isInstallment ? `<li><a class="dropdown-item" href="#" onclick="makeInstallment('${student.student_code}', '${student.student_name}', {title: '${feeTitleSafe}', total: ${total}, paid: ${paid}, discount: ${discount}, due: ${due}}); return false;">
                                        <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle; margin-right: 5px;">account_balance</span>
                                        Make Installments
                                    </a></li>
                                    ${fee.payment_id ? `<li><a class="dropdown-item text-danger" href="#" onclick="deleteFeePayment(${fee.payment_id}, '${student.student_code}', '${feeTitleSafe}'); return false;">
                                        <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle; margin-right: 5px;">delete</span>
                                        Delete Payment
                                    </a></li>` : ''}
                                    ${fee.generated_id ? `<li><a class="dropdown-item text-danger" href="#" onclick="deleteFeePayment(${fee.generated_id}, '${student.student_code}', '${feeTitleSafe}'); return false;">
                                        <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle; margin-right: 5px;">delete</span>
                                        Delete Generated Fee
                                    </a></li>` : ''}` : ''}
                                    ${isInstallment ? (fee.generated_id ? `<li><a class="dropdown-item text-danger" href="#" onclick="deleteFeePayment(${fee.generated_id}, '${student.student_code}', '${feeTitleSafe}'); return false;">
                                        <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle; margin-right: 5px;">delete</span>
                                        Delete Installment
                                    </a></li>` : (fee.payment_id ? `<li><a class="dropdown-item text-danger" href="#" onclick="deleteFeePayment(${fee.payment_id}, '${student.student_code}', '${feeTitleSafe}'); return false;">
                                        <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle; margin-right: 5px;">delete</span>
                                        Delete Installment
                                    </a></li>` : '')) : ''}
                                </ul>
                            </div>
                        </td>
                    `;
                    searchResultsBody.appendChild(row);
                });
            });
            const totalRow = document.createElement('tr');
            totalRow.innerHTML = `
                <td colspan="4" style="padding: 8px 12px; font-size: 13px;" class="text-end fw-semibold">Total</td>
                <td style="padding: 8px 12px; font-size: 13px;" class="fw-semibold">${grandTotals.total.toFixed(2)}</td>
                <td style="padding: 8px 12px; font-size: 13px;" class="fw-semibold">${grandTotals.discount.toFixed(2)}</td>
                <td style="padding: 8px 12px; font-size: 13px;" class="fw-semibold">${grandTotals.late.toFixed(2)}</td>
                <td style="padding: 8px 12px; font-size: 13px;" class="fw-semibold">${grandTotals.paid.toFixed(2)}</td>
                <td style="padding: 8px 12px; font-size: 13px;" class="fw-semibold">${grandTotals.due.toFixed(2)}</td>
                <td style="padding: 8px 12px; font-size: 13px;" class="fw-semibold">${grandTotals.generated.toFixed(2)}</td>
                <td colspan="3"></td>
            `;
            searchResultsBody.appendChild(totalRow);
            renderLatestPaymentsForStudents(data.students);
        } else {
            searchResultsBody.innerHTML = '<tr><td colspan="13" class="text-center py-4 text-muted">No students found matching this CNIC / Parent ID.</td></tr>';
            renderLatestPaymentsForStudents([]);
        }
    })
    .catch(error => {
        console.error('Error searching by CNIC:', error);
        let errorMessage = 'An error occurred while searching. Please try again.';
        if (error.message) {
            errorMessage += '<br><small class="text-muted">' + error.message + '</small>';
        }
        searchResultsBody.innerHTML = `<tr><td colspan="13" class="text-center py-4 text-danger">${errorMessage}</td></tr>`;
    });
}

// More column functions
function editStudent(studentId, studentCode, studentName) {
    // Redirect to student view page using student ID
    window.location.href = '{{ route("student.view", ":id") }}'.replace(':id', studentId);
}

function deleteStudent(studentId, studentCode, studentName) {
    if (confirm(`Are you sure you want to delete student ${studentName} (${studentCode})? This action cannot be undone.`)) {
        // Implement delete functionality
        fetch('{{ route("student.delete", ":id") }}'.replace(':id', studentId), {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Student deleted successfully!');
                location.reload();
            } else {
                alert('Error: ' + (data.message || 'Failed to delete student'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error deleting student. Please try again.');
        });
    }
}

function payAll(studentCode, studentName) {
    // Redirect to pay all fees page or trigger full payment
    takePayment(studentCode, studentName, 'full');
}

function printVoucher(studentCode, studentName) {
    // Redirect to print voucher page with student_code as query parameter
    const url = `{{ route('accounting.fee-voucher.print') }}?student_code=${encodeURIComponent(studentCode)}`;
    window.open(url, '_blank');
}

function makeInstallment(studentCode, studentName, selectedFeeData = null) {
    // Fetch student fee data
    fetch(`{{ route('fee-payment.search-student') }}?search=${encodeURIComponent(studentCode)}`, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
        },
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.students && data.students.length > 0) {
            const student = data.students[0];
            
            // Populate student info
            document.getElementById('installment_student').value = `${studentName} (${studentCode})`;
            document.getElementById('installment_student_code').value = studentCode;
            
            // Clear and populate fee title dropdown
            const feeTitleSelect = document.getElementById('installment_fee_title');
            feeTitleSelect.innerHTML = '<option value="">Select Fee Title</option>';
            
            // Remove existing change listener to avoid duplicates
            const newSelect = feeTitleSelect.cloneNode(true);
            feeTitleSelect.parentNode.replaceChild(newSelect, feeTitleSelect);
            const updatedSelect = document.getElementById('installment_fee_title');
            
            let selectedFeeIndex = 0;
            
            // Create fee cards container
            const feeCardsContainer = document.getElementById('feeCardsContainer');
            const feeCardsRow = document.getElementById('feeCardsRow');
            feeCardsRow.innerHTML = ''; // Clear existing cards
            
            if (student.fee_rows && student.fee_rows.length > 0) {
                student.fee_rows.forEach((fee, index) => {
                    const option = document.createElement('option');
                    option.value = fee.title;
                    option.textContent = fee.title;
                    option.dataset.totalAmount = fee.total || 0;
                    option.dataset.amountPaid = fee.paid || 0;
                    option.dataset.discount = fee.discount || 0;
                    option.dataset.remaining = fee.due || 0;
                    updatedSelect.appendChild(option);
                    
                    // Create fee card
                    const cardCol = document.createElement('div');
                    cardCol.className = 'col-md-4 col-sm-6';
                    const remaining = parseFloat(fee.due || 0);
                    const total = parseFloat(fee.total || 0);
                    cardCol.innerHTML = `
                        <div class="fee-card-clickable" style="border: 2px solid #e0e7ff; border-radius: 8px; padding: 12px; background: white; cursor: pointer; transition: all 0.3s; margin-bottom: 8px;" 
                             onclick="selectFeeCard('${fee.title}', ${index + 1})"
                             onmouseover="this.style.borderColor='#003471'; this.style.boxShadow='0 2px 8px rgba(0,52,113,0.2)'"
                             onmouseout="this.style.borderColor='#e0e7ff'; this.style.boxShadow='none'">
                            <div class="fw-semibold" style="color: #003471; font-size: 13px; margin-bottom: 4px;">${fee.title}</div>
                            <div style="color: #6c757d; font-size: 11px;">Total: Rs. ${total.toFixed(2)}</div>
                            <div style="color: #dc3545; font-size: 12px; font-weight: 600;">Due: Rs. ${remaining.toFixed(2)}</div>
                        </div>
                    `;
                    feeCardsRow.appendChild(cardCol);
                    
                    // If selectedFeeData matches this fee, remember its index
                    if (selectedFeeData && selectedFeeData.title === fee.title) {
                        selectedFeeIndex = index + 1; // +1 because first option is "Select Fee Title"
                    }
                });
                
                // Show fee cards container
                feeCardsContainer.style.display = 'block';
            } else {
                // If no fees, show default monthly fee
                const option = document.createElement('option');
                const currentMonth = new Date().toLocaleString('default', { month: 'long' });
                const currentYear = new Date().getFullYear();
                option.value = `Monthly Fee - ${currentMonth} ${currentYear}`;
                option.textContent = option.value;
                option.dataset.totalAmount = student.monthly_fee || 0;
                option.dataset.amountPaid = 0;
                option.dataset.discount = 0;
                option.dataset.remaining = student.monthly_fee || 0;
                updatedSelect.appendChild(option);
            }
            
            // Update fee details when fee title changes
            updatedSelect.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                if (selectedOption && selectedOption.dataset.totalAmount) {
                    const totalAmount = parseFloat(selectedOption.dataset.totalAmount || 0);
                    const amountPaid = parseFloat(selectedOption.dataset.amountPaid || 0);
                    const discount = parseFloat(selectedOption.dataset.discount || 0);
                    const remaining = parseFloat(selectedOption.dataset.remaining || 0);
                    
                    document.getElementById('installment_total_amount').value = totalAmount.toFixed(2);
                    document.getElementById('installment_amount_paid').value = amountPaid.toFixed(2);
                    document.getElementById('installment_discount').value = discount.toFixed(2);
                    document.getElementById('installment_remaining_amount').value = remaining.toFixed(2);
                    
                    // Calculate per installment amount
                    calculatePerInstallment();
                }
            });
            
            // Calculate per installment when total installments changes
            const totalInstallmentsInput = document.getElementById('installment_total_installments');
            const newInput = totalInstallmentsInput.cloneNode(true);
            totalInstallmentsInput.parentNode.replaceChild(newInput, totalInstallmentsInput);
            document.getElementById('installment_total_installments').addEventListener('input', calculatePerInstallment);
            
            // Reset form fields
            document.getElementById('installment_total_amount').value = '';
            document.getElementById('installment_amount_paid').value = '';
            document.getElementById('installment_discount').value = '';
            document.getElementById('installment_remaining_amount').value = '';
            document.getElementById('installment_per_installment').value = '';
            document.getElementById('installment_total_installments').value = '';
            
            // Show modal
            const modal = new bootstrap.Modal(document.getElementById('makeInstallmentModal'));
            modal.show();
            
            // If selectedFeeData is provided, pre-select and populate that fee
            if (selectedFeeData && selectedFeeIndex > 0) {
                updatedSelect.selectedIndex = selectedFeeIndex;
                updatedSelect.dispatchEvent(new Event('change'));
            } else if (updatedSelect.options.length > 1) {
                // Otherwise, select first fee by default
                updatedSelect.selectedIndex = 1;
                updatedSelect.dispatchEvent(new Event('change'));
            }
        } else {
            alert('Student not found or no fee data available.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error loading student fee data. Please try again.');
    });
}

function selectFeeCard(feeTitle, optionIndex) {
    const feeTitleSelect = document.getElementById('installment_fee_title');
    feeTitleSelect.selectedIndex = optionIndex;
    feeTitleSelect.dispatchEvent(new Event('change'));
}

function calculatePerInstallment() {
    const remainingAmount = parseFloat(document.getElementById('installment_remaining_amount').value || 0);
    const totalInstallments = parseInt(document.getElementById('installment_total_installments').value || 1);
    
    if (totalInstallments > 0 && remainingAmount > 0) {
        const perInstallment = remainingAmount / totalInstallments;
        document.getElementById('installment_per_installment').value = perInstallment.toFixed(2);
    } else {
        document.getElementById('installment_per_installment').value = '0.00';
    }
}

function handleInstallmentSubmit(event) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    const studentCode = formData.get('student_code');
    const feeTitle = formData.get('payment_title');
    const totalInstallments = parseInt(formData.get('total_installments') || 1);
    const remainingAmount = parseFloat(document.getElementById('installment_remaining_amount').value || 0);
    
    if (!studentCode || !feeTitle || totalInstallments < 1) {
        alert('Please fill all required fields.');
        return;
    }
    
    if (remainingAmount <= 0) {
        alert('Remaining amount must be greater than 0 to create installments.');
        return;
    }
    
    // Get total amount and discount to divide proportionally
    const totalAmount = parseFloat(document.getElementById('installment_total_amount').value || 0);
    const totalDiscount = parseFloat(document.getElementById('installment_discount').value || 0);
    
    // Calculate per installment:
    // - Total Amount per installment = Total Amount / Number of Installments (this is payment_amount)
    // - Discount per installment = Total Discount / Number of Installments (divided equally)
    // - Generated Fee per installment = (Total Amount - Discount) / Number of Installments
    // Example: 5000 fee, 1000 discount, 2 installments
    //   Total Amount per installment = 5000 / 2 = 2500 (payment_amount)
    //   Discount per installment = 1000 / 2 = 500
    //   Generated Fee per installment = (5000 - 1000) / 2 = 2000 (calculated by backend)
    const perInstallmentAmount = totalAmount / totalInstallments; // Total amount per installment (2500 in example)
    const perInstallmentDiscountBase = totalDiscount / totalInstallments; // Base discount per installment (500 in example)
    
    // Show loading
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> Creating...';
    
    // Create installments
    const installmentAmount = perInstallmentAmount.toFixed(2);
    const promises = [];
    
    // Get payment method from form (default to Cash Payment)
    const paymentMethod = document.getElementById('installment_payment_method')?.value || 'Cash Payment';
    
    // Calculate discount for each installment, ensuring total equals original discount
    let totalDiscountUsed = 0;
    for (let i = 1; i <= totalInstallments; i++) {
        let installmentDiscount;
        if (i === totalInstallments) {
            // Last installment gets the remaining discount to ensure total equals original
            installmentDiscount = (totalDiscount - totalDiscountUsed).toFixed(2);
        } else {
            installmentDiscount = perInstallmentDiscountBase.toFixed(2);
            totalDiscountUsed += parseFloat(installmentDiscount);
        }
        
        const installmentFormData = new FormData();
        installmentFormData.append('_token', formData.get('_token'));
        installmentFormData.append('student_code', studentCode);
        installmentFormData.append('payment_title', `${feeTitle}/${i}`);
        installmentFormData.append('payment_amount', installmentAmount);
        installmentFormData.append('discount', installmentDiscount); // Divide discount proportionally across installments (500 per installment in example)
        installmentFormData.append('method', paymentMethod); // Use selected payment method instead of 'Generated'
        installmentFormData.append('payment_date', new Date().toISOString().split('T')[0]);
        installmentFormData.append('sms_notification', 'Yes');
        
        promises.push(
            fetch(form.action, {
                method: 'POST',
                body: installmentFormData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                }
            })
        );
    }
    
    // Execute all installment creation requests
    Promise.all(promises)
    .then(responses => {
        return Promise.all(responses.map(async (response) => {
            if (!response.ok) {
                // Try to parse error response
                let errorData = { success: false, message: 'Request failed' };
                try {
                    const contentType = response.headers.get('content-type');
                    if (contentType && contentType.includes('application/json')) {
                        errorData = await response.json();
                    } else {
                        // Try to get text response for debugging
                        const textResponse = await response.text();
                        errorData.message = `Server error: ${response.status} ${response.statusText}`;
                        if (textResponse) {
                            console.error('Non-JSON error response:', textResponse.substring(0, 200));
                        }
                    }
                } catch (e) {
                    errorData.message = `Server error: ${response.status} ${response.statusText}. ${e.message}`;
                }
                return errorData;
            }
            try {
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    const textResponse = await response.text();
                    console.error('Non-JSON response received:', textResponse.substring(0, 200));
                    return { success: false, message: 'Server returned non-JSON response' };
                }
                return await response.json();
            } catch (e) {
                console.error('Error parsing JSON:', e);
                return { success: false, message: 'Invalid response from server: ' + e.message };
            }
        }));
    })
    .then(results => {
        const successCount = results.filter(r => r.success !== false).length;
        const failedResults = results.filter(r => r.success === false);
        
        if (successCount === totalInstallments) {
            alert(`Successfully created ${totalInstallments} installment(s) for ${feeTitle}!`);
            const modal = bootstrap.Modal.getInstance(document.getElementById('makeInstallmentModal'));
            modal.hide();
            // Refresh search results
            if (window.lastFeeSearch && window.lastFeeSearch.value) {
                if (window.lastFeeSearch.type === 'name') {
                    document.getElementById('searchByName').value = window.lastFeeSearch.value;
                    searchByName();
                } else if (window.lastFeeSearch.type === 'cnic') {
                    document.getElementById('searchByCNIC').value = window.lastFeeSearch.value;
                    searchByCNIC();
                }
            }
        } else {
            let errorMsg = `Created ${successCount} out of ${totalInstallments} installments.`;
            if (failedResults.length > 0) {
                const firstError = failedResults[0];
                if (firstError.message) {
                    errorMsg += `\n\nError: ${firstError.message}`;
                    if (firstError.errors) {
                        const errorDetails = Object.values(firstError.errors).flat().join(', ');
                        errorMsg += `\nDetails: ${errorDetails}`;
                    }
                }
            }
            alert(errorMsg);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error creating installments. Please try again.\n\n' + (error.message || 'Unknown error occurred'));
    })
    .finally(() => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    });
}

function particularReceipt(studentCode, studentName) {
    // Redirect to thermal print particular receipt page
    const url = `{{ url('/fee-payment/particular-receipt-thermal') }}/${encodeURIComponent(studentCode)}`;
    window.open(url, '_blank');
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

// Check for Fee Calculator partial payment data on page load
document.addEventListener('DOMContentLoaded', function() {
    const feeCalculatorPayment = sessionStorage.getItem('feeCalculatorPayment');
    
    if (feeCalculatorPayment) {
        try {
            const paymentData = JSON.parse(feeCalculatorPayment);
            
            if (paymentData.students && paymentData.students.length > 0 && paymentData.paymentType === 'partial') {
                // Show notification
                const notification = document.createElement('div');
                notification.className = 'alert alert-info alert-dismissible fade show';
                notification.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
                notification.innerHTML = `
                    <strong>Fee Calculator Payment:</strong> Processing partial payment for ${paymentData.students.length} student(s). Total Amount: ${paymentData.totalAmount.toFixed(2)}, Payment Amount: ${paymentData.paymentAmount.toFixed(2)}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                `;
                document.body.appendChild(notification);
                
                // Store payment data globally for use in partial payment modal
                window.feeCalculatorPaymentData = paymentData;
                
                // Search by Parent ID / CNIC instead of student name/code
                if (paymentData.father_id_card) {
                    // Set the CNIC value and trigger search
                    document.getElementById('searchByCNIC').value = paymentData.father_id_card;
                    searchByCNIC();
                    
                    // After search completes, the students will be displayed in the search results
                    // User can then select and make partial payment for each student
                } else {
                    // Fallback: if no father_id_card, use first student code
                    const firstStudent = paymentData.students[0];
                    const studentCode = firstStudent.student_code || firstStudent.code;
                    
                    if (studentCode) {
                        document.getElementById('searchByName').value = studentCode;
                        searchByName();
                    }
                }
                
                // Clear sessionStorage after processing
                setTimeout(function() {
                    sessionStorage.removeItem('feeCalculatorPayment');
                }, 1000);
            }
        } catch (error) {
            console.error('Error parsing fee calculator payment data:', error);
            sessionStorage.removeItem('feeCalculatorPayment');
        }
    }
});
</script>
@endsection 