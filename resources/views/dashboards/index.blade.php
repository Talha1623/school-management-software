@extends('layouts.app')

@section('title', 'Admission Management Dashboard')

@section('content')
<!-- Combined Container with Single Border -->
<div class="mb-4" style="border: 1px solid #e0e0e0; padding: 15px; border-radius: 10px; background-color: #f8f9fa;">
    <!-- Top Action Buttons Bar -->
    <div class="mb-3">
        <div class="bg-primary p-2 rounded-10 d-flex align-items-center justify-content-center gap-2 flex-wrap" style="background-color: #1a237e !important;">
            <a href="{{ route('admission.admit-student') }}" class="btn p-0 border-0 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; background-color: #4caf50; border-radius: 8px; text-decoration: none;" title="Admit Student">
                <span class="material-symbols-outlined text-white" style="font-size: 20px;">add</span>
            </a>
            <a href="{{ route('student.information') }}" class="btn p-0 border-0 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; background-color: #f44336; border-radius: 8px; text-decoration: none;" title="Student Information">
                <span class="material-symbols-outlined text-white" style="font-size: 20px;">person</span>
            </a>
            <a href="{{ route('attendance.student') }}" class="btn p-0 border-0 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; background-color: #4caf50; border-radius: 8px; text-decoration: none;" title="Student Attendance">
                <span class="material-symbols-outlined text-white" style="font-size: 20px;">check</span>
            </a>
            <a href="{{ route('attendance.staff') }}" class="btn p-0 border-0 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; background-color: #2196f3; border-radius: 8px; text-decoration: none;" title="Staff Attendance">
                <span class="material-symbols-outlined text-white" style="font-size: 20px;">groups</span>
            </a>
            <a href="{{ route('fee-payment') }}" class="btn p-0 border-0 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; background-color: #ff9800; border-radius: 8px; text-decoration: none;" title="Fee Payment">
                <span class="material-symbols-outlined text-white" style="font-size: 20px;">payments</span>
            </a>
            <a href="{{ route('accounting.generate-monthly-fee') }}" class="btn p-0 border-0 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; background-color: #9e9e9e; border-radius: 8px; text-decoration: none;" title="Generate Monthly Fee">
                <span class="material-symbols-outlined text-white" style="font-size: 20px;">attach_money</span>
            </a>
            <!-- <a href="#" class="btn p-0 border-0 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; background-color: #4caf50; border-radius: 8px; text-decoration: none;" title="Add Management Expense">
                <span class="material-symbols-outlined text-white" style="font-size: 20px;">thumb_up</span>
            </a> -->
            <a href="{{ route('expense-management.add') }}" class="btn p-0 border-0 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; background-color: #ff9800; border-radius: 8px; text-decoration: none;" title="Add Management Expense">
                <span class="material-symbols-outlined text-white" style="font-size: 20px;">work</span>
            </a>
            <!-- <button class="btn p-0 border-0" style="width: 40px; height: 40px; background-color: #f44336; border-radius: 8px;">
                <span class="material-symbols-outlined text-white" style="font-size: 20px;">pie_chart</span>
            </button> -->
            <a href="{{ route('staff.management') }}" class="btn p-0 border-0 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; background-color: #2196f3; border-radius: 8px; text-decoration: none;" title="Staff Management">
                <span class="material-symbols-outlined text-white" style="font-size: 20px;">groups</span>
            </a>
            <!-- <a href="{{ route('parent.manage-access') }}" class="btn p-0 border-0 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; background-color: #4caf50; border-radius: 8px; text-decoration: none;" title="Parent Manage Access">
                <span class="material-symbols-outlined text-white" style="font-size: 20px;">group</span>
            </a> -->
            <!-- <button class="btn p-0 border-0" style="width: 40px; height: 40px; background-color: #9e9e9e; border-radius: 8px;">
                <span class="material-symbols-outlined text-white" style="font-size: 20px;">forum</span>
            </button> -->
            <!-- <button class="btn p-0 border-0" style="width: 40px; height: 40px; background-color: #ff9800; border-radius: 8px;">
                <span class="material-symbols-outlined text-white" style="font-size: 20px;">chat_bubble</span>
            </button> -->
            <!-- <button class="btn p-0 border-0" style="width: 40px; height: 40px; background-color: #2196f3; border-radius: 8px;">
                <span class="material-symbols-outlined text-white" style="font-size: 20px;">refresh</span>
            </button> -->
            <a href="{{ route('school.noticeboard') }}" class="btn p-0 border-0 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; background-color: #4caf50; border-radius: 8px; text-decoration: none;" title="School Noticeboard">
                <span class="material-symbols-outlined text-white" style="font-size: 20px;">notifications</span>
            </a>
            <a href="{{ route('settings.general') }}" class="btn p-0 border-0 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; background-color: #2196f3; border-radius: 8px; text-decoration: none;" title="General Settings">
                <span class="material-symbols-outlined text-white" style="font-size: 20px;">settings</span>
            </a>
            <a href="{{ route('homework-diary.manage') }}" class="btn p-0 border-0 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; background-color: #ff9800; border-radius: 8px; text-decoration: none;" title="Add & Manage Diaries">
                <span class="material-symbols-outlined text-white" style="font-size: 20px;">menu_book</span>
            </a>
        </div>
    </div>

    <!-- Metric Cards Row -->
    <div class="row g-2">
    <div class="col">
        <div class="card border-0 rounded-10 p-2 h-100" style="background-color: #32b4ee;">
            <div class="d-flex align-items-center mb-1">
                <div class="bg-white rounded-circle p-1 me-2" style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;">
                    <span class="material-symbols-outlined text-success" style="font-size: 18px;">attach_money</span>
                </div>
            </div>
            <h6 class="mb-0 text-white" style="font-size: 11px; font-weight: 500;">Package</h6>
            <h5 class="mb-0 text-white fw-medium" style="font-size: 14px;">Basic</h5>
        </div>
    </div>
    <div class="col">
        <div class="card border-0 rounded-10 p-2 h-100" style="background-color: #003471;">
            <div class="d-flex align-items-center mb-1">
                <div class="bg-white rounded-circle p-1 me-2" style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;">
                    <span class="material-symbols-outlined text-warning" style="font-size: 18px;">schedule</span>
                </div>
            </div>
            <h6 class="mb-0 text-white" style="font-size: 11px; font-weight: 500;">Days Left</h6>
            <h5 class="mb-0 text-white fw-medium" style="font-size: 14px;">24</h5>
        </div>
    </div>
    <div class="col">
        <div class="card border-0 rounded-10 p-2 h-100" style="background-color: #32b4ee;">
            <div class="d-flex align-items-center mb-1">
                <div class="bg-white rounded-circle p-1 me-2" style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;">
                    <span class="material-symbols-outlined text-danger" style="font-size: 18px;">school</span>
                </div>
            </div>
            <h6 class="mb-0 text-white" style="font-size: 11px; font-weight: 500;">Student Limit</h6>
            <h5 class="mb-0 text-white fw-medium" style="font-size: 14px;">{{ $studentLimitDisplay ?? '0 / 300' }}</h5>
        </div>
    </div>
    @if(Auth::guard('admin')->check() && Auth::guard('admin')->user()->isSuperAdmin())
    <div class="col">
        <div class="card border-0 rounded-10 p-2 h-100" style="background-color: #003471;">
            <div class="d-flex align-items-center mb-1">
                <div class="bg-white rounded-circle p-1 me-2" style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;">
                    <span class="material-symbols-outlined" style="font-size: 18px; color: #e91e63;">person</span>
                </div>
            </div>
            <h6 class="mb-0 text-white" style="font-size: 11px; font-weight: 500;">Users Limit</h6>
            <h5 class="mb-0 text-white fw-medium" style="font-size: 14px;">Unlimited</h5>
        </div>
    </div>
    @endif
    <div class="col">
        <div class="card border-0 rounded-10 p-2 h-100" style="background-color: #003471;">
            <div class="d-flex align-items-center mb-1">
                <div class="bg-white rounded-circle p-1 me-2" style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;">
                    <span class="material-symbols-outlined text-primary" style="font-size: 18px;">account_balance</span>
                </div>
            </div>
            <h6 class="mb-0 text-white" style="font-size: 11px; font-weight: 500;">Max Campuses</h6>
            <h5 class="mb-0 text-white fw-medium" style="font-size: 14px;">{{ $maxCampuses ?? 1 }}</h5>
        </div>
    </div>
    <div class="col">
        <div class="card border-0 rounded-10 p-2 h-100" style="background-color: #32b4ee;">
            <div class="d-flex align-items-center mb-1">
                <div class="bg-white rounded-circle p-1 me-2" style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;">
                    <span class="material-symbols-outlined text-purple" style="font-size: 18px; color: #9c27b0;">schedule</span>
                </div>
            </div>
            <h6 class="mb-0 text-white" style="font-size: 11px; font-weight: 500;">Current Time</h6>
            <h5 class="mb-0 text-white fw-medium" style="font-size: 14px;" id="current-time">07:08:13 PM</h5>
        </div>
    </div>
    </div>
</div>

<!-- Admin Dashboard Section -->
<div class="row mb-4">
    <div class="col-12 mb-3">
        <div class="d-flex justify-content-between align-items-center">
            <h4 class="mb-0">
                <span class="material-symbols-outlined" style="vertical-align: middle; font-size: 20px;">dashboard</span>
                {{ __('dashboard.admin_dashboard') }} <small class="text-muted">({{ app()->getLocale() }})</small>
            </h4>
            <a href="javascript:void(0);" class="text-secondary text-decoration-none">Hide Dashboard Details</a>
        </div>
    </div>
</div>

<div class="row mb-4 align-items-end">
    <!-- Left Column - Awards -->
    <div class="col-lg-4 col-md-12">
        <div class="d-flex flex-column gap-1">
            <!-- St. Monthly Attendance Award -->
            <div class="card border-0 rounded-10 p-1" style="background: linear-gradient(135deg, #2196F3 0%, #03A9F4 100%); height: 70px;">
                <div class="d-flex align-items-end h-100">
                    <div class="flex-shrink-0 me-2" style="width: 35px; height: 35px; display: flex; align-items: center; justify-content: center;">
                        <span class="material-symbols-outlined" style="font-size: 28px; color: #ffd700;">emoji_events</span>
                    </div>
                    <div class="flex-grow-1 d-flex flex-column justify-content-end">
                        <h6 class="mb-1 fw-medium text-white" style="font-size: 12px; line-height: 1.2;">St. Monthly Attendance Award</h6>
                        <p class="mb-0 text-white" style="font-size: 11px; line-height: 1.2;">
                            <span class="badge bg-warning" style="width: 6px; height: 6px; padding: 0; border-radius: 50%; display: inline-block; margin-right: 4px;"></span>
                            C1-011 | Hamza - Presents: 1
                        </p>
                    </div>
                </div>
            </div>

            <!-- Staff Monthly Att. Award -->
            <div class="card border-0 rounded-10 p-1" style="background: linear-gradient(135deg, #4CAF50 0%, #66BB6A 100%); height: 70px;">
                <div class="d-flex align-items-end h-100">
                    <div class="flex-shrink-0 me-2" style="width: 35px; height: 35px; display: flex; align-items: center; justify-content: center;">
                        <span class="material-symbols-outlined" style="font-size: 28px; color: #ffd700;">emoji_events</span>
                    </div>
                    <div class="flex-grow-1 d-flex flex-column justify-content-end">
                        <h6 class="mb-1 fw-medium text-white" style="font-size: 12px; line-height: 1.2;">Staff Monthly Att. Award</h6>
                        <p class="mb-0 text-white" style="font-size: 11px; line-height: 1.2;">
                            <span class="badge bg-warning" style="width: 6px; height: 6px; padding: 0; border-radius: 50%; display: inline-block; margin-right: 4px;"></span>
                            N/A | N/A - Presents: 0
                        </p>
                    </div>
                </div>
            </div>

            <!-- Student with Highest Dues -->
            <div class="card border-0 rounded-10 p-1" style="background: linear-gradient(135deg, #FF9800 0%, #FFB74D 100%); height: 70px;">
                <div class="d-flex align-items-end h-100">
                    <div class="flex-shrink-0 me-2" style="width: 35px; height: 35px; display: flex; align-items: center; justify-content: center;">
                        <span class="material-symbols-outlined" style="font-size: 28px; color: #fff;">sentiment_very_dissatisfied</span>
                    </div>
                    <div class="flex-grow-1 d-flex flex-column justify-content-end">
                        <h6 class="mb-1 fw-medium text-white" style="font-size: 12px; line-height: 1.2;">Student with Highest Dues</h6>
                        <p class="mb-0 text-white" style="font-size: 11px; line-height: 1.2;">
                            <span class="badge bg-danger" style="width: 6px; height: 6px; padding: 0; border-radius: 50%; display: inline-block; margin-right: 4px;"></span>
                            N/A | N/A - Due: 0.00
                        </p>
                    </div>
                </div>
            </div>

            <!-- Best Performance (Teacher) -->
            <div class="card border-0 rounded-10 p-1" style="background: linear-gradient(135deg, #9C27B0 0%, #BA68C8 100%); height: 70px;">
                <div class="d-flex align-items-end h-100">
                    <div class="flex-shrink-0 me-2" style="width: 35px; height: 35px; display: flex; align-items: center; justify-content: center;">
                        <span class="material-symbols-outlined" style="font-size: 28px; color: #ffd700;">emoji_events</span>
                    </div>
                    <div class="flex-grow-1 d-flex flex-column justify-content-end">
                        <h6 class="mb-1 fw-medium text-white" style="font-size: 12px; line-height: 1.2;">Best Performance (Teacher)</h6>
                        <p class="mb-0 text-white" style="font-size: 11px; line-height: 1.2;">
                            <span class="badge bg-warning" style="width: 6px; height: 6px; padding: 0; border-radius: 50%; display: inline-block; margin-right: 4px;"></span>
                            N/A | N/A (N/A/N/A) - Pass St.: 0
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column - Financial Cards -->
    <div class="col-lg-8 col-md-12">
        <div class="row g-2">
            <!-- Row 1 -->
            <div class="col-md-6 col-lg-3">
                <div class="card border-0 rounded-10 p-2 h-100 position-relative" style="background-color: #f44336; overflow: hidden;">
                    <div class="position-absolute" style="right: -10px; top: -10px; opacity: 0.1;">
                        <span class="material-symbols-outlined" style="font-size: 80px;">calculate</span>
                    </div>
                    <h2 class="text-white mb-1 fw-bold" style="font-size: 32px;">{{ $unpaidInvoicesCount ?? 0 }}</h2>
                    <h6 class="text-white mb-2" style="font-size: 13px; font-weight: 500;">Unpaid Invoices</h6>
                    <p class="text-white mb-2" style="font-size: 12px; opacity: 0.9;">Amount: {{ number_format($unpaidInvoicesAmount ?? 0, 2) }}</p>
                    <a href="{{ route('reports.unpaid-invoices') }}" class="btn btn-sm text-white p-0 border-0" style="font-size: 12px; text-decoration: none;">More info <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">arrow_forward</span></a>
                </div>
            </div>

            <div class="col-md-6 col-lg-3">
                <div class="card border-0 rounded-10 p-2 h-100 position-relative" style="background-color: #4caf50; overflow: hidden;">
                    <div class="position-absolute" style="right: -10px; top: -10px; opacity: 0.1;">
                        <span class="material-symbols-outlined" style="font-size: 80px;">arrow_downward_circle</span>
                    </div>
                    <h2 class="text-white mb-1 fw-bold" style="font-size: 32px;">{{ number_format($incomeToday ?? 0, 2) }}</h2>
                    <h6 class="text-white mb-2" style="font-size: 13px; font-weight: 500;">Income Today</h6>
                    <p class="text-white mb-2" style="font-size: 12px; opacity: 0.9;">This Month: {{ number_format($incomeThisMonth ?? 0, 2) }}</p>
                    <a href="{{ route('reports.detailed-income', ['filter_date' => \Carbon\Carbon::now()->format('Y-m-d')]) }}" class="btn btn-sm text-white p-0 border-0" style="font-size: 12px; text-decoration: none;">More info <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">arrow_forward</span></a>
                </div>
            </div>

            <div class="col-md-6 col-lg-3">
                <div class="card border-0 rounded-10 p-2 h-100 position-relative" style="background-color: #ff9800; overflow: hidden;">
                    <div class="position-absolute" style="right: -10px; top: -10px; opacity: 0.1;">
                        <span class="material-symbols-outlined" style="font-size: 80px;">arrow_upward_circle</span>
                    </div>
                    <h2 class="text-white mb-1 fw-bold" style="font-size: 32px;">{{ number_format($expenseToday ?? 0, 2) }}</h2>
                    <h6 class="text-white mb-2" style="font-size: 13px; font-weight: 500;">Expense Today</h6>
                    <p class="text-white mb-2" style="font-size: 12px; opacity: 0.9;">This Month: {{ number_format($expenseThisMonth ?? 0, 2) }}</p>
                    <a href="{{ route('reports.detailed-expense', ['filter_date' => \Carbon\Carbon::now()->format('Y-m-d')]) }}" class="btn btn-sm text-white p-0 border-0" style="font-size: 12px; text-decoration: none;">More info <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">arrow_forward</span></a>
                </div>
            </div>

            <div class="col-md-6 col-lg-3">
                <div class="card border-0 rounded-10 p-2 h-100 position-relative" style="background-color: #03a9f4; overflow: hidden;">
                    <div class="position-absolute" style="right: -10px; top: -10px; opacity: 0.1;">
                        <span class="material-symbols-outlined" style="font-size: 80px;">payments</span>
                    </div>
                    <h2 class="text-white mb-1 fw-bold" style="font-size: 32px;">{{ number_format($profitToday ?? 0, 2) }}</h2>
                    <h6 class="text-white mb-2" style="font-size: 13px; font-weight: 500;">Profit Today</h6>
                    <p class="text-white mb-2" style="font-size: 12px; opacity: 0.9;">This Month: {{ number_format($profitThisMonth ?? 0, 2) }}</p>
                    <a href="{{ route('reports.income-expense', ['filter_from_date' => \Carbon\Carbon::now()->format('Y-m-d'), 'filter_to_date' => \Carbon\Carbon::now()->format('Y-m-d')]) }}" class="btn btn-sm text-white p-0 border-0" style="font-size: 12px; text-decoration: none;">More info <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">arrow_forward</span></a>
                </div>
            </div>

            <!-- Row 2 -->
            <div class="col-md-6 col-lg-3">
                <div class="card border-0 rounded-10 p-2 h-100 position-relative" style="background-color: #03a9f4; overflow: hidden;">
                    <div class="position-absolute" style="right: -10px; top: -10px; opacity: 0.1;">
                        <span class="material-symbols-outlined" style="font-size: 80px;">arrow_downward_circle</span>
                    </div>
                    <h2 class="text-white mb-1 fw-bold" style="font-size: 32px;">1000</h2>
                    <h6 class="text-white mb-2" style="font-size: 13px; font-weight: 500;">Yearly Income</h6>
                    <a href="{{ route('reports.income-expense', ['filter_from_date' => \Carbon\Carbon::now()->startOfYear()->format('Y-m-d'), 'filter_to_date' => \Carbon\Carbon::now()->endOfYear()->format('Y-m-d')]) }}" class="btn btn-sm text-white p-0 border-0" style="font-size: 12px; text-decoration: none;">More info <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">arrow_forward</span></a>
                </div>
            </div>

            <div class="col-md-6 col-lg-3">
                <div class="card border-0 rounded-10 p-2 h-100 position-relative" style="background-color: #f44336; overflow: hidden;">
                    <div class="position-absolute" style="right: -10px; top: -10px; opacity: 0.1;">
                        <span class="material-symbols-outlined" style="font-size: 80px;">arrow_upward_circle</span>
                    </div>
                    <h2 class="text-white mb-1 fw-bold" style="font-size: 32px;">0</h2>
                    <h6 class="text-white mb-2" style="font-size: 13px; font-weight: 500;">Yearly Expenses</h6>
                    <a href="{{ route('reports.income-expense', ['filter_from_date' => \Carbon\Carbon::now()->startOfYear()->format('Y-m-d'), 'filter_to_date' => \Carbon\Carbon::now()->endOfYear()->format('Y-m-d')]) }}" class="btn btn-sm text-white p-0 border-0" style="font-size: 12px; text-decoration: none;">More info <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">arrow_forward</span></a>
                </div>
            </div>

            <div class="col-md-6 col-lg-3">
                <div class="card border-0 rounded-10 p-2 h-100 position-relative" style="background-color: #1976d2; overflow: hidden;">
                    <div class="position-absolute" style="right: -10px; top: -10px; opacity: 0.1;">
                        <span class="material-symbols-outlined" style="font-size: 80px;">pie_chart</span>
                    </div>
                    <h2 class="text-white mb-1 fw-bold" style="font-size: 32px;">1000</h2>
                    <h6 class="text-white mb-2" style="font-size: 13px; font-weight: 500;">Profit This Year</h6>
                    <a href="{{ route('reports.income-expense', ['filter_from_date' => \Carbon\Carbon::now()->startOfYear()->format('Y-m-d'), 'filter_to_date' => \Carbon\Carbon::now()->endOfYear()->format('Y-m-d')]) }}" class="btn btn-sm text-white p-0 border-0" style="font-size: 12px; text-decoration: none;">More info <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">arrow_forward</span></a>
                </div>
            </div>

            <div class="col-md-6 col-lg-3">
                <div class="card border-0 rounded-10 p-2 h-100 position-relative" style="background-color: #9e9e9e; overflow: hidden;">
                    <div class="position-absolute" style="right: -10px; top: -10px; opacity: 0.1;">
                        <span class="material-symbols-outlined" style="font-size: 80px;">calendar_today</span>
                    </div>
                    <h2 class="text-white mb-1 fw-bold" style="font-size: 26px;">2025-2026</h2>
                    <h6 class="text-white mb-2" style="font-size: 13px; font-weight: 500;">Current Session</h6>
                    <a href="{{ route('settings.general') }}" class="btn btn-sm text-white p-0 border-0" style="font-size: 12px; text-decoration: none;">Change Session <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">arrow_forward</span></a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Admissions & Income/Expense Overview Section -->
<div class="row mb-4">
    <!-- Admissions Overview - Left Panel -->
    <div class="col-lg-6 col-md-12 mb-3">
        <div class="card bg-white p-3 rounded-10 border border-white h-100">
            <h4 class="mb-0 fw-bold" style="background: linear-gradient(135deg, #1a237e 0%, #003471 100%); color: #ffffff; padding: 6px 12px; margin: -12px -12px 12px -12px; border-radius: 8px 8px 0 0; font-size: 16px;">{{ __('dashboard.admissions_overview') }}</h4>
            <div id="admissions_overview_chart" style="min-height: 300px;"></div>
            <div class="d-flex justify-content-center flex-wrap gap-3 mt-3">
                <div class="d-flex align-items-center">
                    <div style="width: 12px; height: 12px; background-color: #9C27B0; border-radius: 2px; margin-right: 6px;"></div>
                    <span style="font-size: 12px; color: #666;">Yearly Admissions</span>
                </div>
                <div class="d-flex align-items-center">
                    <div style="width: 12px; height: 12px; background-color: #FF9800; border-radius: 2px; margin-right: 6px;"></div>
                    <span style="font-size: 12px; color: #666;">Monthly Admissions</span>
                </div>
                <div class="d-flex align-items-center">
                    <div style="width: 12px; height: 12px; background-color: #03A9F4; border-radius: 2px; margin-right: 6px;"></div>
                    <span style="font-size: 12px; color: #666;">Admissions Today</span>
                </div>
                <div class="d-flex align-items-center">
                    <div style="width: 12px; height: 12px; background-color: #F44336; border-radius: 2px; margin-right: 6px;"></div>
                    <span style="font-size: 12px; color: #666;">Pass-out Students</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Weekly/Yearly Income & Expense Overview - Right Panel -->
    <div class="col-lg-6 col-md-12 mb-3">
        <div class="card bg-white p-3 rounded-10 border border-white h-100">
            <h4 class="mb-0 fw-bold" style="background: linear-gradient(135deg, #1a237e 0%, #003471 100%); color: #ffffff; padding: 6px 12px; margin: -12px -12px 12px -12px; border-radius: 8px 8px 0 0; font-size: 16px;">{{ __('dashboard.income_expense_overview') }}</h4>
            <div id="daily_income_expense_chart" style="min-height: 150px; margin-bottom: 20px;"></div>
            <div id="monthly_income_expense_chart" style="min-height: 150px;"></div>
        </div>
    </div>
</div>

<!-- Month Wise Paid Unpaid Fee Report & Statistics Cards Section -->
<div class="row mb-4">
    <!-- Month Wise Paid Unpaid Fee Report - Left Panel -->
    <div class="col-lg-8 col-md-12 mb-3">
        <div class="card bg-white p-3 rounded-10 border border-white h-100">
            <h4 class="mb-0 fw-bold" style="background: linear-gradient(135deg, #1a237e 0%, #003471 100%); color: #ffffff; padding: 6px 12px; margin: -12px -12px 12px -12px; border-radius: 8px 8px 0 0; font-size: 16px;">{{ __('dashboard.month_wise_fee_report') }}</h4>
            <div id="month_wise_fee_chart" style="min-height: 350px;"></div>
        </div>
    </div>

    <!-- Statistics Cards - Right Panel -->
    <div class="col-lg-4 col-md-12 mb-3">
        <div class="d-flex flex-column gap-2 h-100">
            <!-- Active Students Card -->
            <div class="card border-0 rounded-10 p-3" style="background: linear-gradient(135deg, #4CAF50 0%, #66BB6A 100%);">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="flex-grow-1">
            <h6 class="text-white mb-2 fw-bold" style="font-size: 12px; letter-spacing: 0.5px;">{{ __('dashboard.active_students') }}</h6>
                        <h2 class="text-white mb-1 fw-bold" style="font-size: 42px;">{{ $activeStudentsCount ?? 0 }}</h2>
                        <p class="text-white mb-0" style="font-size: 11px; opacity: 0.9;">Boys: {{ $boysCount ?? 0 }} Girls: {{ $girlsCount ?? 0 }}@if(($noGenderSetCount ?? 0) > 0) (No Gender Set: {{ $noGenderSetCount ?? 0 }})@endif</p>
            <a href="{{ route('student.info-report.print', ['type' => 'all-active', 'auto_print' => 1]) }}" target="_blank" class="btn btn-sm text-white p-0 border-0" style="font-size: 12px; text-decoration: none;">More info <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">arrow_forward</span></a>
                    </div>
                    <div class="flex-shrink-0">
                        <span class="material-symbols-outlined text-white" style="font-size: 48px; opacity: 0.3;">person</span>
                    </div>
                </div>
            </div>

            <!-- Parents Card -->
            <div class="card border-0 rounded-10 p-3" style="background: linear-gradient(135deg, #F44336 0%, #E57373 100%);">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="flex-grow-1">
                        <h6 class="text-white mb-2 fw-bold" style="font-size: 12px; letter-spacing: 0.5px;">{{ __('dashboard.parents') }}</h6>
                        <h2 class="text-white mb-1 fw-bold" style="font-size: 42px;">{{ $totalParents ?? 0 }}</h2>
                        <p class="text-white mb-0" style="font-size: 11px; opacity: 0.9;">Total Registered Parents</p>
                        <a href="{{ route('parent.manage-access.print', ['auto_print' => 1]) }}" target="_blank" class="btn btn-sm text-white p-0 border-0" style="font-size: 12px; text-decoration: none;">More info <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">arrow_forward</span></a>
                    </div>
                    <div class="flex-shrink-0">
                        <span class="material-symbols-outlined text-white" style="font-size: 48px; opacity: 0.3;">group</span>
                    </div>
                </div>
            </div>

            <!-- Staff Card -->
            <div class="card border-0 rounded-10 p-3" style="background: linear-gradient(135deg, #2196F3 0%, #64B5F6 100%);">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="flex-grow-1">
                        <h6 class="text-white mb-2 fw-bold" style="font-size: 12px; letter-spacing: 0.5px;">{{ __('dashboard.staff') }}</h6>
                        <h2 class="text-white mb-1 fw-bold" style="font-size: 42px;">{{ $totalStaff ?? 0 }}</h2>
                        <p class="text-white mb-0" style="font-size: 11px; opacity: 0.9;">Male: {{ $maleStaff ?? 0 }} Female: {{ $femaleStaff ?? 0 }}</p>
                        <a href="{{ route('staff.management.print', ['auto_print' => 1]) }}" target="_blank" class="btn btn-sm text-white p-0 border-0" style="font-size: 12px; text-decoration: none;">More info <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">arrow_forward</span></a>
                    </div>
                    <div class="flex-shrink-0">
                        <span class="material-symbols-outlined text-white" style="font-size: 48px; opacity: 0.3;">groups</span>
                    </div>
                </div>
            </div>

            <!-- Present Students Today Card -->
            <div class="card border-0 rounded-10 p-3" style="background: linear-gradient(135deg, #1a237e 0%, #003471 100%);">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="flex-grow-1">
                        <h6 class="text-white mb-2 fw-bold" style="font-size: 12px; letter-spacing: 0.5px;">{{ __('dashboard.present_students_today') }}</h6>
                        <h2 class="text-white mb-1 fw-bold" style="font-size: 42px;">{{ $presentStudentsToday ?? 0 }}</h2>
                        <p class="text-white mb-0" style="font-size: 11px; opacity: 0.9;">Attendance Percentage: {{ number_format($attendancePercentage ?? 0, 1) }}%</p>
                        <a href="{{ route('attendance.present-today.print', ['auto_print' => 1]) }}" target="_blank" class="btn btn-sm text-white p-0 border-0" style="font-size: 12px; text-decoration: none;">More info <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">arrow_forward</span></a>
                    </div>
                    <div class="flex-shrink-0">
                        <span class="material-symbols-outlined text-white" style="font-size: 48px; opacity: 0.3;">thumb_up</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Latest Admissions, Staff Attendance Chart & Tasks Overview Section -->
<div class="row mb-4">
    <!-- Latest Admissions - Left Panel -->
    <div class="col-lg-4 col-md-12 mb-3">
        <div class="card bg-white p-3 rounded-10 border border-white h-100">
            <h4 class="mb-0 fw-bold" style="background: linear-gradient(135deg, #1a237e 0%, #003471 100%); color: #ffffff; padding: 6px 12px; margin: -12px -12px 12px -12px; border-radius: 8px 8px 0 0; font-size: 16px;">{{ __('dashboard.latest_admissions') }}</h4>
            <div class="row g-2 mt-2">
                @forelse($latestAdmissions ?? [] as $student)
                <div class="col-6">
                    <a href="{{ route('student.print', $student) }}" target="_blank" style="text-decoration: none; display: block;">
                        <div class="card border-0 rounded-10 p-2" style="background-color: #f5f5f5;">
                            <div class="d-flex flex-column align-items-center text-center">
                                <div class="bg-primary rounded-circle p-2 mb-2" style="width: 50px; height: 50px; display: flex; align-items: center; justify-content: center; background-color: #2196F3 !important;">
                                    <span class="material-symbols-outlined text-white" style="font-size: 24px;">person</span>
                                </div>
                                <h6 class="mb-1 fw-bold text-dark" style="font-size: 12px;">{{ $student->student_name ?? 'N/A' }}</h6>
                                <p class="mb-1 text-dark" style="font-size: 10px; opacity: 0.7;">{{ $student->student_code ?? 'N/A' }}</p>
                                @php
                                    $admissionDate = $student->admission_date ?? $student->created_at;
                                    $formattedDate = $admissionDate ? \Carbon\Carbon::parse($admissionDate)->format('d M - Y') : 'N/A';
                                @endphp
                                <span class="badge bg-danger text-white" style="font-size: 9px; padding: 4px 8px;">{{ $formattedDate }}</span>
                            </div>
                        </div>
                    </a>
                </div>
                @empty
                <div class="col-12">
                    <p class="text-center text-muted" style="font-size: 12px; padding: 20px;">No admissions found</p>
                </div>
                @endforelse
            </div>
            <p class="text-dark mb-0 mt-3 text-center" style="font-size: 12px;">{{ __('dashboard.total_admissions_month') }}: {{ $totalAdmissionsThisMonth ?? 0 }}</p>
        </div>
    </div>

    <!-- Staff Attendance Chart - Middle Panel -->
    <div class="col-lg-4 col-md-12 mb-3">
        <div class="card bg-white p-3 rounded-10 border border-white h-100">
            <h4 class="mb-0 fw-bold" style="background: linear-gradient(135deg, #1a237e 0%, #003471 100%); color: #ffffff; padding: 6px 12px; margin: -12px -12px 12px -12px; border-radius: 8px 8px 0 0; font-size: 16px;">{{ __('dashboard.staff_attendance_chart') }}</h4>
            <div id="staff_attendance_chart" style="min-height: 400px;"></div>
        </div>
    </div>

    <!-- Tasks Overview - Right Panel -->
    <div class="col-lg-4 col-md-12 mb-3">
        <div class="card bg-white p-3 rounded-10 border border-white h-100">
            <h4 class="mb-0 fw-bold" style="background: linear-gradient(135deg, #1a237e 0%, #003471 100%); color: #ffffff; padding: 6px 12px; margin: -12px -12px 12px -12px; border-radius: 8px 8px 0 0; font-size: 16px;">{{ __('dashboard.tasks_overview') }}</h4>
            <div class="d-flex flex-column gap-2 mt-2">
                @forelse($latestTasks ?? [] as $task)
                @php
                    // Define gradient colors array (cycling through different colors)
                    $gradientColors = [
                        ['#F44336', '#E57373'], // Red
                        ['#03A9F4', '#64B5F6'], // Blue
                        ['#FF9800', '#FFB74D'], // Orange
                        ['#2196F3', '#64B5F6'], // Light Blue
                        ['#9C27B0', '#BA68C8'], // Purple
                        ['#4CAF50', '#81C784'], // Green
                    ];
                    $colorIndex = $loop->index % count($gradientColors);
                    $gradient = $gradientColors[$colorIndex];
                    
                    // Map status to badge class and text
                    $statusMap = [
                        'Completed' => ['class' => 'bg-success', 'text' => 'Completed'],
                        'Pending' => ['class' => 'bg-warning', 'text' => 'Pending'],
                        'Accepted' => ['class' => 'bg-info', 'text' => 'Accepted'],
                        'Returned' => ['class' => 'bg-danger', 'text' => 'Returned'],
                    ];
                    $statusInfo = $statusMap[$task->status ?? 'Pending'] ?? $statusMap['Pending'];
                @endphp
                <div class="card border-0 rounded-10 p-2" style="background: linear-gradient(135deg, {{ $gradient[0] }} 0%, {{ $gradient[1] }} 100%);">
                    <div class="d-flex align-items-center justify-content-between">
                        <h6 class="text-white mb-0 fw-bold" style="font-size: 13px;">{{ $task->task_title ?? 'N/A' }}</h6>
                        <span class="badge {{ $statusInfo['class'] }} text-white" style="font-size: 10px; padding: 4px 8px;">{{ $statusInfo['text'] }}</span>
                    </div>
                </div>
                @empty
                <div class="text-center text-muted" style="padding: 20px;">
                    <p class="mb-0" style="font-size: 12px;">No tasks available</p>
                </div>
                @endforelse
            </div>
        </div>
    </div>
</div>

<!-- Class Wise Attendance & Financial Table Section -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card bg-white p-3 rounded-10 border border-white">
            <div class="table-responsive">
                <table class="table table-bordered mb-0" style="font-size: 13px;">
                    <thead>
                        <tr>
                            <th class="text-white fw-bold" style="padding: 10px; background: linear-gradient(135deg, #1a237e 0%, #003471 100%);">Class</th>
                            <th class="text-white fw-bold" style="padding: 10px; background: linear-gradient(135deg, #1a237e 0%, #003471 100%);">Section Strength</th>
                            <th class="text-white fw-bold" style="padding: 10px; background: linear-gradient(135deg, #1a237e 0%, #003471 100%);">Present Today</th>
                            <th class="text-white fw-bold" style="padding: 10px; background: linear-gradient(135deg, #1a237e 0%, #003471 100%);">Absent Today</th>
                            <th class="text-white fw-bold" style="padding: 10px; background: linear-gradient(135deg, #1a237e 0%, #003471 100%);">On Leave</th>
                            <th class="text-white fw-bold" style="padding: 10px; background: linear-gradient(135deg, #1a237e 0%, #003471 100%);">Expected</th>
                            <th class="text-white fw-bold" style="padding: 10px; background: linear-gradient(135deg, #1a237e 0%, #003471 100%);">Generated</th>
                            <th class="text-white fw-bold" style="padding: 10px; background: linear-gradient(135deg, #1a237e 0%, #003471 100%);">Paid Amount</th>
                            <th class="text-white fw-bold" style="padding: 10px; background: linear-gradient(135deg, #1a237e 0%, #003471 100%);">Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($classSectionData ?? [] as $row)
                        <tr>
                            <td class="fw-bold" style="padding: 12px;">{{ $row['class'] }}</td>
                            <td style="padding: 12px;">
                                <span class="badge bg-secondary text-white rounded-pill" style="padding: 6px 10px; font-size: 11px;">
                                    <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">groups</span> {{ $row['section'] != 'N/A' ? $row['section'] : 'N/A' }}: {{ $row['section_strength'] }}
                                </span>
                            </td>
                            <td style="padding: 12px;">
                                <span class="badge bg-success text-white rounded-pill" style="padding: 6px 10px; font-size: 11px;">
                                    <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">check</span> {{ number_format($row['present_today']) }}
                                </span>
                            </td>
                            <td style="padding: 12px;">
                                <span class="badge text-white rounded-pill" style="padding: 6px 10px; font-size: 11px; background-color: #1a237e;">
                                    <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">close</span> {{ number_format($row['absent_today']) }}
                                </span>
                            </td>
                            <td style="padding: 12px;">
                                <span class="badge bg-warning text-white rounded-pill" style="padding: 6px 10px; font-size: 11px;">
                                    <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">credit_card</span> {{ number_format($row['on_leave']) }}
                                </span>
                            </td>
                            <td style="padding: 12px;">
                                <span class="badge bg-danger text-white rounded-pill" style="padding: 6px 10px; font-size: 11px;">
                                    <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">account_balance_wallet</span> {{ number_format($row['expected']) }}
                                </span>
                            </td>
                            <td style="padding: 12px;">
                                <span class="badge bg-danger text-white rounded-pill" style="padding: 6px 10px; font-size: 11px;">
                                    <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">account_balance_wallet</span> {{ number_format($row['generated'], 2) }}
                                </span>
                            </td>
                            <td style="padding: 12px;">
                                <span class="badge {{ $row['paid_amount'] > 0 ? 'bg-success' : 'bg-danger' }} text-white rounded-pill" style="padding: 6px 10px; font-size: 11px;">
                                    <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">check</span> {{ number_format($row['paid_amount'], 2) }}
                                </span>
                            </td>
                            <td style="padding: 12px;">
                                <span class="badge {{ $row['balance'] <= 0 ? 'bg-success' : 'bg-danger' }} text-white rounded-pill" style="padding: 6px 10px; font-size: 11px;">
                                    <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">check</span> {{ number_format($row['balance'], 2) }}
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center" style="padding: 20px;">No data available</td>
                        </tr>
                        @endforelse
                        <!-- Total Row -->
                        @if(isset($classSectionData) && count($classSectionData) > 0)
                        <tr style="background-color: #ffebee;">
                            <td class="fw-bold text-dark" style="padding: 12px;">Total</td>
                            <td class="text-dark fw-bold" style="padding: 12px;">{{ number_format($totalSectionStrength ?? 0) }}</td>
                            <td class="text-dark fw-bold" style="padding: 12px;">{{ number_format($totalPresentToday ?? 0) }}</td>
                            <td class="text-dark fw-bold" style="padding: 12px;">{{ number_format($totalAbsentToday ?? 0) }}</td>
                            <td class="text-dark fw-bold" style="padding: 12px;">{{ number_format($totalOnLeave ?? 0) }}</td>
                            <td class="text-dark fw-bold" style="padding: 12px;">{{ number_format($totalExpected ?? 0) }}</td>
                            <td class="text-dark fw-bold" style="padding: 12px;">{{ number_format($totalGenerated ?? 0, 2) }}</td>
                            <td class="text-dark fw-bold" style="padding: 12px;">{{ number_format($totalPaidAmount ?? 0, 2) }}</td>
                            <td class="text-dark fw-bold" style="padding: 12px;">{{ number_format($totalBalance ?? 0, 2) }}</td>
                        </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
// Update Current Time
function updateCurrentTime() {
    const now = new Date();
    const hours = now.getHours();
    const minutes = String(now.getMinutes()).padStart(2, '0');
    const seconds = String(now.getSeconds()).padStart(2, '0');
    const ampm = hours >= 12 ? 'PM' : 'AM';
    const displayHours = hours % 12 || 12;
    const timeString = `${String(displayHours).padStart(2, '0')}:${minutes}:${seconds} ${ampm}`;
    const element = document.getElementById('current-time');
    if (element) {
        element.textContent = timeString;
    }
}

// Update time every second
setInterval(updateCurrentTime, 1000);
updateCurrentTime();

// Initialize Charts when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // Wait for ApexCharts to be available
    if (typeof ApexCharts !== 'undefined') {
        initializeCharts();
    } else {
        // If ApexCharts is not loaded yet, wait a bit
        setTimeout(function() {
            if (typeof ApexCharts !== 'undefined') {
                initializeCharts();
            }
        }, 500);
    }
});

// Month-wise fee data from PHP
@php
    $paidFeeData = $monthWisePaidFee ?? [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
    $unpaidFeeData = $monthWiseUnpaidFee ?? [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
    $labelsData = $monthLabels ?? ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    
    // Weekly income/expense data
    $weeklyIncomeData = $weeklyIncome ?? [0, 0, 0, 0, 0, 0, 0];
    $weeklyExpenseData = $weeklyExpense ?? [0, 0, 0, 0, 0, 0, 0];
    $weeklyLabelsData = $weeklyLabels ?? ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
    
    // Staff attendance chart data (last 7 days)
    $staffAttendanceLabels = $staffAttendanceLabels ?? ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
    $staffPresentData = $staffPresentData ?? [0, 0, 0, 0, 0, 0, 0];
    $staffAbsentData = $staffAbsentData ?? [0, 0, 0, 0, 0, 0, 0];
    $staffLeaveData = $staffLeaveData ?? [0, 0, 0, 0, 0, 0, 0];
    
    // Monthly income/expense data
    $monthlyIncomeData = $monthlyIncome ?? [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
    $monthlyExpenseData = $monthlyExpense ?? [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
    $monthlyLabelsData = $monthlyLabels ?? ['Jan 2025', 'Feb 2025', 'Mar 2025', 'Apr 2025', 'May 2025', 'Jun 2025', 'Jul 2025', 'Aug 2025', 'Sep 2025', 'Oct 2025', 'Nov 2025', 'Dec 2025'];
@endphp
var monthWisePaidFeeData = @json($paidFeeData);
var monthWiseUnpaidFeeData = @json($unpaidFeeData);
var monthLabelsData = @json($labelsData);
var weeklyIncomeData = @json($weeklyIncomeData);
var weeklyExpenseData = @json($weeklyExpenseData);
var weeklyLabelsData = @json($weeklyLabelsData);
var staffAttendanceLabels = @json($staffAttendanceLabels);
var staffPresentData = @json($staffPresentData);
var staffAbsentData = @json($staffAbsentData);
var staffLeaveData = @json($staffLeaveData);
var monthlyIncomeData = @json($monthlyIncomeData);
var monthlyExpenseData = @json($monthlyExpenseData);
var monthlyLabelsData = @json($monthlyLabelsData);

function initializeCharts() {
    // Admissions Overview Pie Chart
    const admissionsChartId = document.getElementById('admissions_overview_chart');
    if (admissionsChartId) {
        var options = {
            series: [85, 12, 2, 1],
            chart: {
                type: 'pie',
                height: 300
            },
            labels: ['Yearly Admissions', 'Monthly Admissions', 'Admissions Today', 'Pass-out Students'],
            colors: ['#9C27B0', '#FF9800', '#03A9F4', '#F44336'],
            legend: {
                show: false
            },
            dataLabels: {
                enabled: true,
                formatter: function(val) {
                    return val.toFixed(0) + "%";
                }
            },
            tooltip: {
                y: {
                    formatter: function(val) {
                        return val + "%";
                    }
                }
            }
        };
        var chart = new ApexCharts(document.querySelector("#admissions_overview_chart"), options);
        chart.render();
    }

    // Daily Income & Expense Chart (Weekly - Last 7 Days)
    const dailyChartId = document.getElementById('daily_income_expense_chart');
    if (dailyChartId) {
        var options = {
            series: [
                {
                    name: 'Daily Income',
                    data: weeklyIncomeData
                },
                {
                    name: 'Daily Expenses',
                    data: weeklyExpenseData
                }
            ],
            chart: {
                type: 'line',
                height: 150,
                toolbar: {
                    show: false
                }
            },
            colors: ['#00BCD4', '#2196F3'],
            stroke: {
                width: 2,
                curve: 'smooth'
            },
            xaxis: {
                categories: weeklyLabelsData,
                labels: {
                    style: {
                        fontSize: '10px',
                        colors: '#666'
                    }
                }
            },
            yaxis: {
                labels: {
                    style: {
                        fontSize: '10px',
                        colors: '#666'
                    }
                }
            },
            legend: {
                show: true,
                position: 'top',
                fontSize: '11px',
                offsetY: -5
            },
            grid: {
                strokeDashArray: 3,
                borderColor: '#e0e0e0'
            },
            tooltip: {
                y: {
                    formatter: function(val) {
                        return val.toFixed(1);
                    }
                }
            }
        };
        var chart = new ApexCharts(document.querySelector("#daily_income_expense_chart"), options);
        chart.render();
    }

    // Staff Attendance Chart (Last 7 Days)
    const staffChartId = document.getElementById('staff_attendance_chart');
    if (staffChartId) {
        const totalStaffAttendance = [...staffPresentData, ...staffAbsentData, ...staffLeaveData]
            .reduce((sum, val) => sum + Number(val || 0), 0);
        if (totalStaffAttendance === 0) {
            staffChartId.innerHTML = '<p class="text-muted mb-0" style="font-size: 14px; text-align: center; padding-top: 150px;">No attendance data available.</p>';
        } else {
            var options = {
                series: [
                    { name: 'Present', data: staffPresentData },
                    { name: 'Absent', data: staffAbsentData },
                    { name: 'Leave', data: staffLeaveData }
                ],
                chart: {
                    type: 'bar',
                    height: 300,
                    stacked: true,
                    toolbar: { show: false }
                },
                colors: ['#4CAF50', '#F44336', '#FF9800'],
                plotOptions: {
                    bar: {
                        horizontal: false,
                        columnWidth: '55%',
                        endingShape: 'rounded'
                    }
                },
                dataLabels: { enabled: false },
                xaxis: {
                    categories: staffAttendanceLabels,
                    labels: { style: { fontSize: '11px', colors: '#666' } }
                },
                yaxis: {
                    labels: { style: { fontSize: '11px', colors: '#666' } }
                },
                legend: {
                    show: true,
                    position: 'top',
                    fontSize: '11px',
                    offsetY: -5
                },
                grid: {
                    strokeDashArray: 3,
                    borderColor: '#e0e0e0'
                },
                tooltip: {
                    y: { formatter: function(val) { return val; } }
                }
            };
            var chart = new ApexCharts(document.querySelector("#staff_attendance_chart"), options);
            chart.render();
        }
    }

    // Monthly Income & Expense Chart (Last 12 Months)
    const monthlyChartId = document.getElementById('monthly_income_expense_chart');
    if (monthlyChartId) {
        var options = {
            series: [
                {
                    name: 'Monthly Income',
                    data: monthlyIncomeData
                },
                {
                    name: 'Monthly Expenses',
                    data: monthlyExpenseData
                }
            ],
            chart: {
                type: 'area',
                height: 150,
                toolbar: {
                    show: false
                },
                stacked: false
            },
            colors: ['#03A9F4', '#E91E63'],
            stroke: {
                width: 2,
                curve: 'smooth'
            },
            fill: {
                type: 'gradient',
                gradient: {
                    opacityFrom: 0.6,
                    opacityTo: 0.1
                }
            },
            xaxis: {
                categories: monthlyLabelsData,
                labels: {
                    rotate: -45,
                    style: {
                        fontSize: '9px',
                        colors: '#666'
                    }
                }
            },
            yaxis: {
                labels: {
                    style: {
                        fontSize: '10px',
                        colors: '#666'
                    }
                }
            },
            legend: {
                show: true,
                position: 'top',
                fontSize: '11px',
                offsetY: -5
            },
            grid: {
                strokeDashArray: 3,
                borderColor: '#e0e0e0'
            },
            tooltip: {
                y: {
                    formatter: function(val) {
                        return val.toFixed(0);
                    }
                }
            }
        };
        var chart = new ApexCharts(document.querySelector("#monthly_income_expense_chart"), options);
        chart.render();
    }

    // Month Wise Paid Unpaid Fee Report Chart
    const feeChartId = document.getElementById('month_wise_fee_chart');
    if (feeChartId) {
        var options = {
            series: [
                {
                    name: 'Paid Fee',
                    data: monthWisePaidFeeData
                },
                {
                    name: 'Unpaid Fee',
                    data: monthWiseUnpaidFeeData
                }
            ],
            chart: {
                type: 'bar',
                height: 350,
                toolbar: {
                    show: false
                },
                stacked: false
            },
            colors: ['#4CAF50', '#FF9800'],
            plotOptions: {
                bar: {
                    horizontal: false,
                    columnWidth: '55%',
                    endingShape: 'rounded'
                }
            },
            dataLabels: {
                enabled: false
            },
            stroke: {
                show: true,
                width: 2,
                colors: ['transparent']
            },
            xaxis: {
                categories: monthLabelsData,
                labels: {
                    style: {
                        fontSize: '11px',
                        colors: '#666'
                    }
                }
            },
            yaxis: {
                labels: {
                    style: {
                        fontSize: '11px',
                        colors: '#666'
                    }
                }
            },
            fill: {
                opacity: 1
            },
            legend: {
                show: true,
                position: 'top',
                fontSize: '12px',
                offsetY: -5
            },
            grid: {
                strokeDashArray: 3,
                borderColor: '#e0e0e0'
            },
            tooltip: {
                y: {
                    formatter: function(val) {
                        return val;
                    }
                }
            }
        };
        var chart = new ApexCharts(document.querySelector("#month_wise_fee_chart"), options);
        chart.render();
    }
}
</script>
@endsection


