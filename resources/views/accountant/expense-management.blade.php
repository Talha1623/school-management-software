@extends('layouts.accountant')

@section('title', 'Expense Management - Accountant')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="d-flex align-items-center mb-4">
            <h2 class="mb-0 fs-20 fw-semibold text-dark me-2">Expense Management</h2>
            <span class="material-symbols-outlined text-secondary" style="font-size: 20px;">receipt_long</span>
        </div>

        <p class="text-muted mb-4 fs-14">Record and classify school management expenses (campus, category, attachments). Open <strong>Add Management Expense</strong> to filter, add, export, or print vouchers.</p>

        <div class="row g-3">
            <div class="col-md-6 col-lg-5">
                <div class="card border border-white rounded-10 h-100 shadow-sm">
                    <div class="card-body p-4 d-flex flex-column">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="material-symbols-outlined" style="font-size: 28px; color: #003471;">add_circle</span>
                            <h3 class="fs-16 fw-semibold mb-0">Add Management Expense</h3>
                        </div>
                        <p class="text-muted fs-13 flex-grow-1 mb-3">List expenses by campus, category, and month; add new entries, view, edit, delete, export, and print.</p>
                        <a href="{{ route('accountant.add-manage-expense') }}" class="btn btn-sm align-self-start px-3 py-2 rounded-8 text-white" style="background: linear-gradient(135deg, #003471 0%, #004a9f 100%); border: none;">
                            <span class="material-symbols-outlined align-middle" style="font-size: 18px; vertical-align: middle;">arrow_forward</span>
                            Open
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-5">
                <div class="card border border-white rounded-10 h-100 shadow-sm">
                    <div class="card-body p-4 d-flex flex-column">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="material-symbols-outlined" style="font-size: 28px; color: #003471;">category</span>
                            <h3 class="fs-16 fw-semibold mb-0">Expense Categories</h3>
                        </div>
                        <p class="text-muted fs-13 flex-grow-1 mb-3">Maintain categories used when posting management expenses.</p>
                        <a href="{{ route('accountant.expense-categories') }}" class="btn btn-sm btn-outline-primary align-self-start px-3 py-2 rounded-8" style="border-color: #003471; color: #003471;">
                            <span class="material-symbols-outlined align-middle" style="font-size: 18px; vertical-align: middle;">arrow_forward</span>
                            Open
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
