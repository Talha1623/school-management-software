@extends('layouts.app')

@section('title', 'Management Expense Details')

@section('content')
<style>
    .expense-detail-card {
        border-radius: 10px;
        border: 1px solid #e9ecef;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
    }
    .expense-detail-header {
        background: linear-gradient(135deg, #003471 0%, #004a9f 100%);
        color: #fff;
        border-radius: 10px 10px 0 0;
        padding: 12px 16px;
    }
    .detail-label {
        font-size: 12px;
        color: #6c757d;
        text-transform: uppercase;
        letter-spacing: 0.4px;
    }
    .detail-value {
        font-size: 14px;
        font-weight: 600;
        color: #212529;
    }
    .detail-box {
        padding: 10px 12px;
        border: 1px solid #f0f0f0;
        border-radius: 8px;
        background: #fafbfc;
    }
</style>
<div class="row">
    <div class="col-12">
        <div class="card bg-white expense-detail-card mb-4">
            <div class="expense-detail-header d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2">
                    <span class="material-symbols-outlined">receipt_long</span>
                    <h4 class="mb-0 fs-16 fw-semibold">Management Expense Details</h4>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-light" onclick="history.back()">
                        <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">arrow_back</span>
                        Back
                    </button>
                    <a href="{{ route(request()->route()->getName() === 'accountant.add-manage-expense.show' ? 'accountant.add-manage-expense.print' : 'expense-management.add.print', $managementExpense->id) }}" target="_blank" class="btn btn-sm btn-dark">
                        <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">print</span>
                        Print
                    </a>
                </div>
            </div>

            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="detail-box">
                            <div class="detail-label">Campus</div>
                            <div class="detail-value">{{ $managementExpense->campus }}</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="detail-box">
                            <div class="detail-label">Category</div>
                            <div class="detail-value">{{ $managementExpense->category }}</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="detail-box">
                            <div class="detail-label">Date</div>
                            <div class="detail-value">{{ $managementExpense->date->format('d M Y') }}</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="detail-box">
                            <div class="detail-label">Title</div>
                            <div class="detail-value">{{ $managementExpense->title }}</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="detail-box">
                            <div class="detail-label">Amount</div>
                            <div class="detail-value">₹{{ number_format($managementExpense->amount, 2) }}</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="detail-box">
                            <div class="detail-label">Method</div>
                            <div class="detail-value">{{ $managementExpense->method }}</div>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="detail-box">
                            <div class="detail-label">Description</div>
                            <div class="detail-value">{{ $managementExpense->description ?? 'N/A' }}</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="detail-box">
                            <div class="detail-label">Notify Admin</div>
                            <div class="detail-value">{{ $managementExpense->notify_admin ? 'Yes' : 'No' }}</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="detail-box">
                            <div class="detail-label">Invoice/Receipt</div>
                            @if($managementExpense->invoice_receipt)
                                <div class="detail-value">
                                    <a href="{{ Storage::url($managementExpense->invoice_receipt) }}" target="_blank">View Attachment</a>
                                </div>
                            @else
                                <div class="detail-value">No</div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
