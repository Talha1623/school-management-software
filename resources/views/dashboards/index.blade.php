@extends('layouts.app')

@section('title', 'Admission Management Dashboard')

@section('content')
<!-- Combined Container with Single Border -->
<div class="mb-4" style="border: 1px solid #e0e0e0; padding: 15px; border-radius: 10px; background-color: #f8f9fa;">
    <!-- Top Action Buttons Bar -->
    <div class="mb-3">
        <div class="bg-primary p-2 rounded-10 d-flex align-items-center justify-content-center gap-2 flex-wrap" style="background-color: #1a237e !important;">
            <button class="btn p-0 border-0" style="width: 40px; height: 40px; background-color: #4caf50; border-radius: 8px;">
                <span class="material-symbols-outlined text-white" style="font-size: 20px;">add</span>
            </button>
            <button class="btn p-0 border-0" style="width: 40px; height: 40px; background-color: #f44336; border-radius: 8px;">
                <span class="material-symbols-outlined text-white" style="font-size: 20px;">person</span>
            </button>
            <button class="btn p-0 border-0" style="width: 40px; height: 40px; background-color: #4caf50; border-radius: 8px;">
                <span class="material-symbols-outlined text-white" style="font-size: 20px;">check</span>
            </button>
            <button class="btn p-0 border-0" style="width: 40px; height: 40px; background-color: #2196f3; border-radius: 8px;">
                <span class="material-symbols-outlined text-white" style="font-size: 20px;">groups</span>
            </button>
            <button class="btn p-0 border-0" style="width: 40px; height: 40px; background-color: #ff9800; border-radius: 8px;">
                <span class="material-symbols-outlined text-white" style="font-size: 20px;">payments</span>
            </button>
            <button class="btn p-0 border-0" style="width: 40px; height: 40px; background-color: #9e9e9e; border-radius: 8px;">
                <span class="material-symbols-outlined text-white" style="font-size: 20px;">attach_money</span>
            </button>
            <button class="btn p-0 border-0" style="width: 40px; height: 40px; background-color: #4caf50; border-radius: 8px;">
                <span class="material-symbols-outlined text-white" style="font-size: 20px;">thumb_up</span>
            </button>
            <button class="btn p-0 border-0" style="width: 40px; height: 40px; background-color: #ff9800; border-radius: 8px;">
                <span class="material-symbols-outlined text-white" style="font-size: 20px;">work</span>
            </button>
            <button class="btn p-0 border-0" style="width: 40px; height: 40px; background-color: #f44336; border-radius: 8px;">
                <span class="material-symbols-outlined text-white" style="font-size: 20px;">pie_chart</span>
            </button>
            <button class="btn p-0 border-0" style="width: 40px; height: 40px; background-color: #2196f3; border-radius: 8px;">
                <span class="material-symbols-outlined text-white" style="font-size: 20px;">groups</span>
            </button>
            <button class="btn p-0 border-0" style="width: 40px; height: 40px; background-color: #4caf50; border-radius: 8px;">
                <span class="material-symbols-outlined text-white" style="font-size: 20px;">group</span>
            </button>
            <button class="btn p-0 border-0" style="width: 40px; height: 40px; background-color: #9e9e9e; border-radius: 8px;">
                <span class="material-symbols-outlined text-white" style="font-size: 20px;">forum</span>
            </button>
            <button class="btn p-0 border-0" style="width: 40px; height: 40px; background-color: #ff9800; border-radius: 8px;">
                <span class="material-symbols-outlined text-white" style="font-size: 20px;">chat_bubble</span>
            </button>
            <button class="btn p-0 border-0" style="width: 40px; height: 40px; background-color: #2196f3; border-radius: 8px;">
                <span class="material-symbols-outlined text-white" style="font-size: 20px;">refresh</span>
            </button>
            <button class="btn p-0 border-0" style="width: 40px; height: 40px; background-color: #4caf50; border-radius: 8px;">
                <span class="material-symbols-outlined text-white" style="font-size: 20px;">notifications</span>
            </button>
            <button class="btn p-0 border-0" style="width: 40px; height: 40px; background-color: #2196f3; border-radius: 8px;">
                <span class="material-symbols-outlined text-white" style="font-size: 20px;">settings</span>
            </button>
            <button class="btn p-0 border-0" style="width: 40px; height: 40px; background-color: #ff9800; border-radius: 8px;">
                <span class="material-symbols-outlined text-white" style="font-size: 20px;">menu_book</span>
            </button>
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
            <h5 class="mb-0 text-white fw-medium" style="font-size: 14px;">35/ 300</h5>
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
        <div class="card border-0 rounded-10 p-2 h-100" style="background-color: #32b4ee;">
            <div class="d-flex align-items-center mb-1">
                <div class="bg-white rounded-circle p-1 me-2" style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;">
                    <span class="material-symbols-outlined" style="font-size: 18px; color: #e91e63;">chat</span>
                </div>
            </div>
            <h6 class="mb-0 text-white" style="font-size: 11px; font-weight: 500;">WhatsApp Left</h6>
            <h5 class="mb-0">
                <span class="text-white fw-medium" style="font-size: 14px;">0</span>
                <span class="text-white ms-1" style="font-size: 10px; opacity: 0.8;">(Buy Now)</span>
            </h5>
        </div>
    </div>
    <div class="col">
        <div class="card border-0 rounded-10 p-2 h-100" style="background-color: #003471;">
            <div class="d-flex align-items-center mb-1">
                <div class="bg-white rounded-circle p-1 me-2" style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;">
                    <span class="material-symbols-outlined text-primary" style="font-size: 18px;">account_balance</span>
                </div>
            </div>
            <h6 class="mb-0 text-white" style="font-size: 11px; font-weight: 500;">Max Campuses</h6>
            <h5 class="mb-0 text-white fw-medium" style="font-size: 14px;">1</h5>
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
                    <h2 class="text-white mb-1 fw-bold" style="font-size: 32px;">52</h2>
                    <h6 class="text-white mb-2" style="font-size: 13px; font-weight: 500;">Unpaid Invoices</h6>
                    <p class="text-white mb-2" style="font-size: 12px; opacity: 0.9;">Amount: 64300.00</p>
                    <button class="btn btn-sm text-white p-0 border-0" style="font-size: 12px;">More info <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">arrow_forward</span></button>
                </div>
            </div>

            <div class="col-md-6 col-lg-3">
                <div class="card border-0 rounded-10 p-2 h-100 position-relative" style="background-color: #4caf50; overflow: hidden;">
                    <div class="position-absolute" style="right: -10px; top: -10px; opacity: 0.1;">
                        <span class="material-symbols-outlined" style="font-size: 80px;">arrow_downward_circle</span>
                    </div>
                    <h2 class="text-white mb-1 fw-bold" style="font-size: 32px;">0</h2>
                    <h6 class="text-white mb-2" style="font-size: 13px; font-weight: 500;">Income Today</h6>
                    <p class="text-white mb-2" style="font-size: 12px; opacity: 0.9;">This Month: 0</p>
                    <button class="btn btn-sm text-white p-0 border-0" style="font-size: 12px;">More info <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">arrow_forward</span></button>
                </div>
            </div>

            <div class="col-md-6 col-lg-3">
                <div class="card border-0 rounded-10 p-2 h-100 position-relative" style="background-color: #ff9800; overflow: hidden;">
                    <div class="position-absolute" style="right: -10px; top: -10px; opacity: 0.1;">
                        <span class="material-symbols-outlined" style="font-size: 80px;">arrow_upward_circle</span>
                    </div>
                    <h2 class="text-white mb-1 fw-bold" style="font-size: 32px;">0</h2>
                    <h6 class="text-white mb-2" style="font-size: 13px; font-weight: 500;">Expense Today</h6>
                    <p class="text-white mb-2" style="font-size: 12px; opacity: 0.9;">This Month: 0</p>
                    <button class="btn btn-sm text-white p-0 border-0" style="font-size: 12px;">More info <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">arrow_forward</span></button>
                </div>
            </div>

            <div class="col-md-6 col-lg-3">
                <div class="card border-0 rounded-10 p-2 h-100 position-relative" style="background-color: #03a9f4; overflow: hidden;">
                    <div class="position-absolute" style="right: -10px; top: -10px; opacity: 0.1;">
                        <span class="material-symbols-outlined" style="font-size: 80px;">payments</span>
                    </div>
                    <h2 class="text-white mb-1 fw-bold" style="font-size: 32px;">0</h2>
                    <h6 class="text-white mb-2" style="font-size: 13px; font-weight: 500;">Profit Today</h6>
                    <p class="text-white mb-2" style="font-size: 12px; opacity: 0.9;">This Month: 0</p>
                    <button class="btn btn-sm text-white p-0 border-0" style="font-size: 12px;">More info <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">arrow_forward</span></button>
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
                    <button class="btn btn-sm text-white p-0 border-0" style="font-size: 12px;">More info <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">arrow_forward</span></button>
                </div>
            </div>

            <div class="col-md-6 col-lg-3">
                <div class="card border-0 rounded-10 p-2 h-100 position-relative" style="background-color: #f44336; overflow: hidden;">
                    <div class="position-absolute" style="right: -10px; top: -10px; opacity: 0.1;">
                        <span class="material-symbols-outlined" style="font-size: 80px;">arrow_upward_circle</span>
                    </div>
                    <h2 class="text-white mb-1 fw-bold" style="font-size: 32px;">0</h2>
                    <h6 class="text-white mb-2" style="font-size: 13px; font-weight: 500;">Yearly Expenses</h6>
                    <button class="btn btn-sm text-white p-0 border-0" style="font-size: 12px;">More info <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">arrow_forward</span></button>
                </div>
            </div>

            <div class="col-md-6 col-lg-3">
                <div class="card border-0 rounded-10 p-2 h-100 position-relative" style="background-color: #1976d2; overflow: hidden;">
                    <div class="position-absolute" style="right: -10px; top: -10px; opacity: 0.1;">
                        <span class="material-symbols-outlined" style="font-size: 80px;">pie_chart</span>
                    </div>
                    <h2 class="text-white mb-1 fw-bold" style="font-size: 32px;">1000</h2>
                    <h6 class="text-white mb-2" style="font-size: 13px; font-weight: 500;">Profit This Year</h6>
                    <button class="btn btn-sm text-white p-0 border-0" style="font-size: 12px;">More info <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">arrow_forward</span></button>
                </div>
            </div>

            <div class="col-md-6 col-lg-3">
                <div class="card border-0 rounded-10 p-2 h-100 position-relative" style="background-color: #9e9e9e; overflow: hidden;">
                    <div class="position-absolute" style="right: -10px; top: -10px; opacity: 0.1;">
                        <span class="material-symbols-outlined" style="font-size: 80px;">calendar_today</span>
                    </div>
                    <h2 class="text-white mb-1 fw-bold" style="font-size: 26px;">2025-2026</h2>
                    <h6 class="text-white mb-2" style="font-size: 13px; font-weight: 500;">Current Session</h6>
                    <button class="btn btn-sm text-white p-0 border-0" style="font-size: 12px;">Change Session <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">arrow_forward</span></button>
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
                        <h2 class="text-white mb-1 fw-bold" style="font-size: 42px;">38</h2>
                        <p class="text-white mb-0" style="font-size: 11px; opacity: 0.9;">Boys: 7 Girls: 0 (No Gender Set: 31)</p>
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
                        <h2 class="text-white mb-1 fw-bold" style="font-size: 42px;">18</h2>
                        <p class="text-white mb-0" style="font-size: 11px; opacity: 0.9;">Total Registered Parents</p>
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
                        <h2 class="text-white mb-1 fw-bold" style="font-size: 42px;">5</h2>
                        <p class="text-white mb-0" style="font-size: 11px; opacity: 0.9;">Male: 5 Female: 0</p>
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
                        <h2 class="text-white mb-1 fw-bold" style="font-size: 42px;">0</h2>
                        <p class="text-white mb-0" style="font-size: 11px; opacity: 0.9;">Attendance Percentage: 0%</p>
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
                <!-- Admission Card 1 -->
                <div class="col-6">
                    <div class="card border-0 rounded-10 p-2" style="background-color: #f5f5f5;">
                        <div class="d-flex flex-column align-items-center text-center">
                            <div class="bg-primary rounded-circle p-2 mb-2" style="width: 50px; height: 50px; display: flex; align-items: center; justify-content: center; background-color: #2196F3 !important;">
                                <span class="material-symbols-outlined text-white" style="font-size: 24px;">person</span>
                            </div>
                            <h6 class="mb-1 fw-bold text-dark" style="font-size: 12px;">Zubair Javed</h6>
                            <p class="mb-1 text-dark" style="font-size: 10px; opacity: 0.7;">C3-012</p>
                            <span class="badge bg-danger text-white" style="font-size: 9px; padding: 4px 8px;">21 Nov - 2025</span>
                        </div>
                    </div>
                </div>
                <!-- Admission Card 2 -->
                <div class="col-6">
                    <div class="card border-0 rounded-10 p-2" style="background-color: #f5f5f5;">
                        <div class="d-flex flex-column align-items-center text-center">
                            <div class="bg-primary rounded-circle p-2 mb-2" style="width: 50px; height: 50px; display: flex; align-items: center; justify-content: center; background-color: #2196F3 !important;">
                                <span class="material-symbols-outlined text-white" style="font-size: 24px;">person</span>
                            </div>
                            <h6 class="mb-1 fw-bold text-dark" style="font-size: 12px;">korban</h6>
                            <p class="mb-1 text-dark" style="font-size: 10px; opacity: 0.7;">ST0001-12</p>
                            <span class="badge bg-danger text-white" style="font-size: 9px; padding: 4px 8px;">20 Nov - 2025</span>
                        </div>
                    </div>
                </div>
                <!-- Admission Card 3 -->
                <div class="col-6">
                    <div class="card border-0 rounded-10 p-2" style="background-color: #f5f5f5;">
                        <div class="d-flex flex-column align-items-center text-center">
                            <div class="bg-primary rounded-circle p-2 mb-2" style="width: 50px; height: 50px; display: flex; align-items: center; justify-content: center; background-color: #2196F3 !important;">
                                <span class="material-symbols-outlined text-white" style="font-size: 24px;">person</span>
                            </div>
                            <h6 class="mb-1 fw-bold text-dark" style="font-size: 12px;">Muhammad</h6>
                            <p class="mb-1 text-dark" style="font-size: 10px; opacity: 0.7;">ST0001-2</p>
                            <span class="badge bg-danger text-white" style="font-size: 9px; padding: 4px 8px;">12 Nov - 2025</span>
                        </div>
                    </div>
                </div>
                <!-- Admission Card 4 -->
                <div class="col-6">
                    <div class="card border-0 rounded-10 p-2" style="background-color: #f5f5f5;">
                        <div class="d-flex flex-column align-items-center text-center">
                            <div class="bg-primary rounded-circle p-2 mb-2" style="width: 50px; height: 50px; display: flex; align-items: center; justify-content: center; background-color: #2196F3 !important;">
                                <span class="material-symbols-outlined text-white" style="font-size: 24px;">person</span>
                            </div>
                            <h6 class="mb-1 fw-bold text-dark" style="font-size: 12px;">Azhar</h6>
                            <p class="mb-1 text-dark" style="font-size: 10px; opacity: 0.7;">ST0001-1</p>
                            <span class="badge bg-danger text-white" style="font-size: 9px; padding: 4px 8px;">07 Oct - 2025</span>
                        </div>
                    </div>
                </div>
                <!-- Admission Card 5 -->
                <div class="col-6">
                    <div class="card border-0 rounded-10 p-2" style="background-color: #f5f5f5;">
                        <div class="d-flex flex-column align-items-center text-center">
                            <div class="bg-primary rounded-circle p-2 mb-2" style="width: 50px; height: 50px; display: flex; align-items: center; justify-content: center; background-color: #2196F3 !important;">
                                <span class="material-symbols-outlined text-white" style="font-size: 24px;">person</span>
                            </div>
                            <h6 class="mb-1 fw-bold text-dark" style="font-size: 12px;">Aliyan Imran</h6>
                            <p class="mb-1 text-dark" style="font-size: 10px; opacity: 0.7;">C3-011</p>
                            <span class="badge bg-danger text-white" style="font-size: 9px; padding: 4px 8px;">10 Jan - 2025</span>
                        </div>
                    </div>
                </div>
                <!-- Admission Card 6 -->
                <div class="col-6">
                    <div class="card border-0 rounded-10 p-2" style="background-color: #f5f5f5;">
                        <div class="d-flex flex-column align-items-center text-center">
                            <div class="bg-primary rounded-circle p-2 mb-2" style="width: 50px; height: 50px; display: flex; align-items: center; justify-content: center; background-color: #2196F3 !important;">
                                <span class="material-symbols-outlined text-white" style="font-size: 24px;">person</span>
                            </div>
                            <h6 class="mb-1 fw-bold text-dark" style="font-size: 12px;">Hassam</h6>
                            <p class="mb-1 text-dark" style="font-size: 10px; opacity: 0.7;">C2-011</p>
                            <span class="badge bg-danger text-white" style="font-size: 9px; padding: 4px 8px;">01 Oct - 2025</span>
                        </div>
                    </div>
                </div>
            </div>
            <p class="text-dark mb-0 mt-3 text-center" style="font-size: 12px;">{{ __('dashboard.total_admissions_month') }}: 3</p>
        </div>
    </div>

    <!-- Staff Attendance Chart - Middle Panel -->
    <div class="col-lg-4 col-md-12 mb-3">
        <div class="card bg-white p-3 rounded-10 border border-white h-100">
            <h4 class="mb-0 fw-bold" style="background: linear-gradient(135deg, #1a237e 0%, #003471 100%); color: #ffffff; padding: 6px 12px; margin: -12px -12px 12px -12px; border-radius: 8px 8px 0 0; font-size: 16px;">{{ __('dashboard.staff_attendance_chart') }}</h4>
            <div id="staff_attendance_chart" style="min-height: 400px; display: flex; align-items: center; justify-content: center;">
                <p class="text-muted mb-0" style="font-size: 14px;">No attendance data available.</p>
            </div>
        </div>
    </div>

    <!-- Tasks Overview - Right Panel -->
    <div class="col-lg-4 col-md-12 mb-3">
        <div class="card bg-white p-3 rounded-10 border border-white h-100">
            <h4 class="mb-0 fw-bold" style="background: linear-gradient(135deg, #1a237e 0%, #003471 100%); color: #ffffff; padding: 6px 12px; margin: -12px -12px 12px -12px; border-radius: 8px 8px 0 0; font-size: 16px;">{{ __('dashboard.tasks_overview') }}</h4>
            <div class="d-flex flex-column gap-2 mt-2">
                <!-- Task 1 -->
                <div class="card border-0 rounded-10 p-2" style="background: linear-gradient(135deg, #F44336 0%, #E57373 100%);">
                    <div class="d-flex align-items-center justify-content-between">
                        <h6 class="text-white mb-0 fw-bold" style="font-size: 13px;">test</h6>
                        <span class="badge bg-success text-white" style="font-size: 10px; padding: 4px 8px;">Completed</span>
                    </div>
                </div>
                <!-- Task 2 -->
                <div class="card border-0 rounded-10 p-2" style="background: linear-gradient(135deg, #03A9F4 0%, #64B5F6 100%);">
                    <div class="d-flex align-items-center justify-content-between">
                        <h6 class="text-white mb-0 fw-bold" style="font-size: 13px;">Chomu</h6>
                        <span class="badge bg-warning text-white" style="font-size: 10px; padding: 4px 8px;">Pending</span>
                    </div>
                </div>
                <!-- Task 3 -->
                <div class="card border-0 rounded-10 p-2" style="background: linear-gradient(135deg, #FF9800 0%, #FFB74D 100%);">
                    <div class="d-flex align-items-center justify-content-between">
                        <h6 class="text-white mb-0 fw-bold" style="font-size: 13px;">Public Holiday</h6>
                        <span class="badge bg-warning text-white" style="font-size: 10px; padding: 4px 8px;">Pending</span>
                    </div>
                </div>
                <!-- Task 4 -->
                <div class="card border-0 rounded-10 p-2" style="background: linear-gradient(135deg, #2196F3 0%, #64B5F6 100%);">
                    <div class="d-flex align-items-center justify-content-between">
                        <h6 class="text-white mb-0 fw-bold" style="font-size: 13px;">Public Holiday</h6>
                        <span class="badge bg-warning text-white" style="font-size: 10px; padding: 4px 8px;">Pending</span>
                    </div>
                </div>
                <!-- Task 5 -->
                <div class="card border-0 rounded-10 p-2" style="background: linear-gradient(135deg, #9C27B0 0%, #BA68C8 100%);">
                    <div class="d-flex align-items-center justify-content-between">
                        <h6 class="text-white mb-0 fw-bold" style="font-size: 13px;">Meeting</h6>
                        <span class="badge bg-success text-white" style="font-size: 10px; padding: 4px 8px;">Completed</span>
                    </div>
                </div>
                <!-- Task 6 -->
                <div class="card border-0 rounded-10 p-2" style="background: linear-gradient(135deg, #F44336 0%, #E57373 100%);">
                    <div class="d-flex align-items-center justify-content-between">
                        <h6 class="text-white mb-0 fw-bold" style="font-size: 13px;">Exam Result Announcements</h6>
                        <span class="badge text-white" style="font-size: 10px; padding: 4px 8px; background-color: #03A9F4;">Processing</span>
                    </div>
                </div>
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
                        <!-- Row 1: Class One -->
                        <tr>
                            <td class="fw-bold" style="padding: 12px;">One</td>
                            <td style="padding: 12px;">
                                <div class="d-flex gap-1 flex-wrap">
                                    <span class="badge bg-secondary text-white rounded-pill" style="padding: 6px 10px; font-size: 11px;">
                                        <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">groups</span> A: 2
                                    </span>
                                    <span class="badge bg-secondary text-white rounded-pill" style="padding: 6px 10px; font-size: 11px;">
                                        <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">groups</span> I: 0
                                    </span>
                                </div>
                            </td>
                            <td style="padding: 12px;">
                                <span class="badge bg-success text-white rounded-pill" style="padding: 6px 10px; font-size: 11px;">
                                    <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">check</span> 0
                                </span>
                            </td>
                            <td style="padding: 12px;">
                                <span class="badge text-white rounded-pill" style="padding: 6px 10px; font-size: 11px; background-color: #1a237e;">
                                    <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">close</span> 0
                                </span>
                            </td>
                            <td style="padding: 12px;">
                                <span class="badge bg-warning text-white rounded-pill" style="padding: 6px 10px; font-size: 11px;">
                                    <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">credit_card</span> 0
                                </span>
                            </td>
                            <td style="padding: 12px;">
                                <span class="badge bg-danger text-white rounded-pill" style="padding: 6px 10px; font-size: 11px;">
                                    <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">account_balance_wallet</span> 2234
                                </span>
                            </td>
                            <td style="padding: 12px;">
                                <span class="badge bg-danger text-white rounded-pill" style="padding: 6px 10px; font-size: 11px;">
                                    <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">account_balance_wallet</span> 1200
                                </span>
                            </td>
                            <td style="padding: 12px;">
                                <span class="badge bg-success text-white rounded-pill" style="padding: 6px 10px; font-size: 11px;">
                                    <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">check</span> 1200
                                </span>
                            </td>
                            <td style="padding: 12px;">
                                <span class="badge bg-success text-white rounded-pill" style="padding: 6px 10px; font-size: 11px;">
                                    <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">check</span> 0
                                </span>
                            </td>
                        </tr>
                        <!-- Row 2: Class Two -->
                        <tr>
                            <td class="fw-bold" style="padding: 12px;">Two</td>
                            <td style="padding: 12px;">
                                <span class="badge bg-secondary text-white rounded-pill" style="padding: 6px 10px; font-size: 11px;">
                                    <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">groups</span> A: 1
                                </span>
                            </td>
                            <td style="padding: 12px;">
                                <span class="badge bg-success text-white rounded-pill" style="padding: 6px 10px; font-size: 11px;">
                                    <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">check</span> 0
                                </span>
                            </td>
                            <td style="padding: 12px;">
                                <span class="badge text-white rounded-pill" style="padding: 6px 10px; font-size: 11px; background-color: #1a237e;">
                                    <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">close</span> 0
                                </span>
                            </td>
                            <td style="padding: 12px;">
                                <span class="badge bg-warning text-white rounded-pill" style="padding: 6px 10px; font-size: 11px;">
                                    <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">credit_card</span> 0
                                </span>
                            </td>
                            <td style="padding: 12px;">
                                <span class="badge bg-danger text-white rounded-pill" style="padding: 6px 10px; font-size: 11px;">
                                    <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">account_balance_wallet</span> 650
                                </span>
                            </td>
                            <td style="padding: 12px;">
                                <span class="badge bg-danger text-white rounded-pill" style="padding: 6px 10px; font-size: 11px;">
                                    <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">account_balance_wallet</span> 0
                                </span>
                            </td>
                            <td style="padding: 12px;">
                                <span class="badge bg-success text-white rounded-pill" style="padding: 6px 10px; font-size: 11px;">
                                    <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">check</span> 0
                                </span>
                            </td>
                            <td style="padding: 12px;">
                                <span class="badge bg-success text-white rounded-pill" style="padding: 6px 10px; font-size: 11px;">
                                    <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">check</span> 0
                                </span>
                            </td>
                        </tr>
                        <!-- Row 3: Class Three -->
                        <tr>
                            <td class="fw-bold" style="padding: 12px;">Three</td>
                            <td style="padding: 12px;">
                                <span class="badge bg-secondary text-white rounded-pill" style="padding: 6px 10px; font-size: 11px;">
                                    <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">groups</span> A: 35
                                </span>
                            </td>
                            <td style="padding: 12px;">
                                <span class="badge bg-success text-white rounded-pill" style="padding: 6px 10px; font-size: 11px;">
                                    <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">check</span> 0
                                </span>
                            </td>
                            <td style="padding: 12px;">
                                <span class="badge text-white rounded-pill" style="padding: 6px 10px; font-size: 11px; background-color: #1a237e;">
                                    <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">close</span> 0
                                </span>
                            </td>
                            <td style="padding: 12px;">
                                <span class="badge bg-warning text-white rounded-pill" style="padding: 6px 10px; font-size: 11px;">
                                    <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">credit_card</span> 0
                                </span>
                            </td>
                            <td style="padding: 12px;">
                                <span class="badge bg-danger text-white rounded-pill" style="padding: 6px 10px; font-size: 11px;">
                                    <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">account_balance_wallet</span> 48400
                                </span>
                            </td>
                            <td style="padding: 12px;">
                                <span class="badge bg-danger text-white rounded-pill" style="padding: 6px 10px; font-size: 11px;">
                                    <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">account_balance_wallet</span> 15400
                                </span>
                            </td>
                            <td style="padding: 12px;">
                                <span class="badge bg-success text-white rounded-pill" style="padding: 6px 10px; font-size: 11px;">
                                    <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">check</span> 0
                                </span>
                            </td>
                            <td style="padding: 12px;">
                                <span class="badge bg-success text-white rounded-pill" style="padding: 6px 10px; font-size: 11px;">
                                    <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">check</span> 15400
                                </span>
                            </td>
                        </tr>
                        <!-- Total Row -->
                        <tr style="background-color: #ffebee;">
                            <td class="fw-bold text-dark" style="padding: 12px;">Total</td>
                            <td class="text-dark" style="padding: 12px;">38</td>
                            <td class="text-dark" style="padding: 12px;">0</td>
                            <td class="text-dark" style="padding: 12px;">0</td>
                            <td class="text-dark" style="padding: 12px;">0</td>
                            <td class="text-dark" style="padding: 12px;">51284</td>
                            <td class="text-dark" style="padding: 12px;">16600</td>
                            <td class="text-dark" style="padding: 12px;">1200</td>
                            <td class="text-dark" style="padding: 12px;">15400</td>
                        </tr>
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

    // Daily Income & Expense Chart
    const dailyChartId = document.getElementById('daily_income_expense_chart');
    if (dailyChartId) {
        var options = {
            series: [
                {
                    name: 'Daily Income',
                    data: [0, 0, 0, 0, 0, 0, 0]
                },
                {
                    name: 'Daily Expenses',
                    data: [0, 0, 0, 0, 0, 0, 0]
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
                categories: ['Tue 18', 'Wed 19', 'Thu 20', 'Fri 21', 'Sat 22', 'Sun 23', 'Mon 24'],
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

    // Monthly Income & Expense Chart
    const monthlyChartId = document.getElementById('monthly_income_expense_chart');
    if (monthlyChartId) {
        var options = {
            series: [
                {
                    name: 'Monthly Income',
                    data: [0, 0, 0, 0, 0, 0, 0, 0, 0, 2000, 2200, 2400]
                },
                {
                    name: 'Monthly Expenses',
                    data: [0, 0, 0, 0, 0, 0, 0, 0, 0, 1800, 2000, 2100]
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
                categories: ['Dec 2024', 'Jan 2025', 'Feb 2025', 'Mar 2025', 'Apr 2025', 'May 2025', 'Jun 2025', 'Jul 2025', 'Aug 2025', 'Sep 2025', 'Oct 2025', 'Nov 2025'],
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
                    data: [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 1]
                },
                {
                    name: 'Unpaid Fee',
                    data: [0, 0, 1, 0, 0, 0, 0, 0, 0, 6, 35, 11]
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
                categories: ['Dec-24', 'Jan-25', 'Feb-25', 'Mar-25', 'Apr-25', 'May-25', 'Jun-25', 'Jul-25', 'Aug-25', 'Sep-25', 'Oct-25', 'Nov-25'],
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


