@extends('layouts.app')

@section('title', 'Payment Settings')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-4 mb-4">
            <div class="d-flex align-items-center mb-4">
                <span class="material-symbols-outlined me-2" style="font-size: 28px; color: #003471;">payment</span>
                <h3 class="mb-0 fw-bold" style="color: #003471;">Payment Settings</h3>
            </div>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <form method="POST" action="#">
                @csrf
                <div class="row">
                    <div class="col-md-6 mb-4">
                        <div class="card border-0 shadow-sm rounded-10 p-4 h-100">
                            <h5 class="mb-4 fw-semibold" style="color: #003471;">
                                <span class="material-symbols-outlined" style="font-size: 20px; vertical-align: middle;">account_balance</span>
                                Payment Gateway Configuration
                            </h5>
                            
                            <div class="mb-3">
                                <label class="form-label fw-medium">Payment Gateway</label>
                                <select class="form-select" name="payment_gateway">
                                    <option value="stripe">Stripe</option>
                                    <option value="paypal">PayPal</option>
                                    <option value="razorpay">Razorpay</option>
                                    <option value="easypaisa">EasyPaisa</option>
                                    <option value="jazzcash">JazzCash</option>
                                    <option value="bank">Bank Transfer</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label fw-medium">Merchant ID / API Key</label>
                                <input type="text" class="form-control" name="merchant_id" placeholder="Enter merchant ID">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label fw-medium">Secret Key</label>
                                <input type="password" class="form-control" name="secret_key" placeholder="Enter secret key">
                            </div>
                            
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="sandbox_mode" name="sandbox_mode">
                                <label class="form-check-label" for="sandbox_mode">
                                    Enable Sandbox/Test Mode
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 mb-4">
                        <div class="card border-0 shadow-sm rounded-10 p-4 h-100">
                            <h5 class="mb-4 fw-semibold" style="color: #003471;">
                                <span class="material-symbols-outlined" style="font-size: 20px; vertical-align: middle;">settings</span>
                                Payment Settings
                            </h5>
                            
                            <div class="mb-3">
                                <label class="form-label fw-medium">Default Currency</label>
                                <select class="form-select" name="currency">
                                    <option value="PKR">PKR (₨)</option>
                                    <option value="USD">USD ($)</option>
                                    <option value="EUR">EUR (€)</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label fw-medium">Transaction Fee (%)</label>
                                <input type="number" class="form-control" name="transaction_fee" placeholder="2.5" step="0.1" min="0">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label fw-medium">Minimum Payment Amount</label>
                                <input type="number" class="form-control" name="min_payment" placeholder="100" min="0">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label fw-medium">Maximum Payment Amount</label>
                                <input type="number" class="form-control" name="max_payment" placeholder="100000" min="0">
                            </div>
                            
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="auto_receipt" name="auto_receipt">
                                <label class="form-check-label" for="auto_receipt">
                                    Auto-generate Receipt
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 mb-4">
                        <div class="card border-0 shadow-sm rounded-10 p-4 h-100">
                            <h5 class="mb-4 fw-semibold" style="color: #003471;">
                                <span class="material-symbols-outlined" style="font-size: 20px; vertical-align: middle;">account_balance_wallet</span>
                                Bank Account Details
                            </h5>
                            
                            <div class="mb-3">
                                <label class="form-label fw-medium">Bank Name</label>
                                <input type="text" class="form-control" name="bank_name" placeholder="Enter bank name">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label fw-medium">Account Number</label>
                                <input type="text" class="form-control" name="account_number" placeholder="Enter account number">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label fw-medium">Account Holder Name</label>
                                <input type="text" class="form-control" name="account_holder" placeholder="Enter account holder name">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label fw-medium">IBAN / Swift Code</label>
                                <input type="text" class="form-control" name="iban" placeholder="Enter IBAN or Swift code">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <button type="button" class="btn btn-outline-secondary">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <span class="material-symbols-outlined" style="font-size: 18px; vertical-align: middle;">save</span>
                        Save Settings
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Ensure sidebar stays open on Payment Settings page
document.addEventListener('DOMContentLoaded', function() {
    document.body.setAttribute("sidebar-data-theme", "sidebar-show");
    const sidebarArea = document.getElementById('sidebar-area');
    if (sidebarArea) {
        sidebarArea.style.display = '';
        sidebarArea.classList.remove('sidebar-hide');
        sidebarArea.classList.add('sidebar-show');
    }
    window.addEventListener('resize', function() {
        if (window.innerWidth > 992) {
            document.body.setAttribute("sidebar-data-theme", "sidebar-show");
        }
    });
});
</script>
@endsection

