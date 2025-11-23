@extends('layouts.accountant')

@section('title', 'Accountant Dashboard')

@section('content')
<div class="row">
    <div class="col-12">
        <!-- Dashboard Title -->
        <div class="d-flex align-items-center mb-4">
            <h2 class="mb-0 fs-20 fw-semibold text-dark me-2">Accountant Dashboard</h2>
            <span class="material-symbols-outlined text-secondary" style="font-size: 20px;">dashboard</span>
        </div>

        <!-- Statistics Cards -->
        <div class="row g-2">
            <!-- Income Today Card -->
            <div class="col-md-2">
                <div class="card border-0 shadow-sm" style="background: #0066cc; border-radius: 8px; overflow: hidden;">
                    <div class="card-body p-3 position-relative">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h2 class="text-white mb-0 fw-bold" style="font-size: 24px; line-height: 1.2;">{{ number_format($incomeToday, 2) }}</h2>
                                <p class="text-white mb-0 mt-1" style="font-size: 11px; font-weight: 500;">Income Today</p>
                            </div>
                            <span class="material-symbols-outlined text-white" style="font-size: 40px; opacity: 0.3;">trending_down</span>
                        </div>
                        <div class="mt-2">
                            <small class="text-white" style="opacity: 0.8; font-size: 10px;">By: {{ $accountant->name }}</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Expense Today Card -->
            <div class="col-md-2">
                <div class="card border-0 shadow-sm" style="background: #dc3545; border-radius: 8px; overflow: hidden;">
                    <div class="card-body p-3 position-relative">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h2 class="text-white mb-0 fw-bold" style="font-size: 24px; line-height: 1.2;">{{ number_format($expenseToday, 2) }}</h2>
                                <p class="text-white mb-0 mt-1" style="font-size: 11px; font-weight: 500;">Expense Today</p>
                            </div>
                            <span class="material-symbols-outlined text-white" style="font-size: 40px; opacity: 0.3;">trending_up</span>
                        </div>
                        <div class="mt-2">
                            <small class="text-white" style="opacity: 0.8; font-size: 10px;">By: {{ $accountant->name }}</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Balance Today Card -->
            <div class="col-md-2">
                <div class="card border-0 shadow-sm" style="background: #17a2b8; border-radius: 8px; overflow: hidden;">
                    <div class="card-body p-3 position-relative">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h2 class="text-white mb-0 fw-bold" style="font-size: 24px; line-height: 1.2;">{{ number_format($balanceToday, 2) }}</h2>
                                <p class="text-white mb-0 mt-1" style="font-size: 11px; font-weight: 500;">Balance Today</p>
                            </div>
                            <span class="material-symbols-outlined text-white" style="font-size: 40px; opacity: 0.3;">pie_chart</span>
                        </div>
                        <div class="mt-2">
                            <small class="text-white" style="opacity: 0.8; font-size: 10px;">By: {{ $accountant->name }}</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Income This Month Card -->
            <div class="col-md-2">
                <div class="card border-0 shadow-sm" style="background: #28a745; border-radius: 8px; overflow: hidden;">
                    <div class="card-body p-3 position-relative">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h2 class="text-white mb-0 fw-bold" style="font-size: 24px; line-height: 1.2;">{{ number_format($incomeThisMonth, 2) }}</h2>
                                <p class="text-white mb-0 mt-1" style="font-size: 11px; font-weight: 500;">Income This Month</p>
                            </div>
                            <span class="material-symbols-outlined text-white" style="font-size: 40px; opacity: 0.3;">trending_down</span>
                        </div>
                        <div class="mt-2">
                            <small class="text-white" style="opacity: 0.8; font-size: 10px;">By: {{ $accountant->name }}</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Expense This Month Card -->
            <div class="col-md-2">
                <div class="card border-0 shadow-sm" style="background: #ff9800; border-radius: 8px; overflow: hidden;">
                    <div class="card-body p-3 position-relative">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h2 class="text-white mb-0 fw-bold" style="font-size: 24px; line-height: 1.2;">{{ number_format($expenseThisMonth, 2) }}</h2>
                                <p class="text-white mb-0 mt-1" style="font-size: 11px; font-weight: 500;">Expense This Month</p>
                            </div>
                            <span class="material-symbols-outlined text-white" style="font-size: 40px; opacity: 0.3;">trending_up</span>
                        </div>
                        <div class="mt-2">
                            <small class="text-white" style="opacity: 0.8; font-size: 10px;">By: {{ $accountant->name }}</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Balance This Month Card -->
            <div class="col-md-2">
                <div class="card border-0 shadow-sm" style="background: #6c757d; border-radius: 8px; overflow: hidden;">
                    <div class="card-body p-3 position-relative">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h2 class="text-white mb-0 fw-bold" style="font-size: 24px; line-height: 1.2;">{{ number_format($balanceThisMonth, 2) }}</h2>
                                <p class="text-white mb-0 mt-1" style="font-size: 11px; font-weight: 500;">Balance This Month</p>
                            </div>
                            <span class="material-symbols-outlined text-white" style="font-size: 40px; opacity: 0.3;">bar_chart</span>
                        </div>
                        <div class="mt-2">
                            <small class="text-white" style="opacity: 0.8; font-size: 10px;">By: {{ $accountant->name }}</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

