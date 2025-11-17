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
                Admin Dashboard
            </h4>
            <a href="javascript:void(0);" class="text-secondary text-decoration-none">Hide Dashboard Details</a>
        </div>
    </div>
</div>

<div class="row mb-4">
    <!-- Left Column - Awards -->
    <div class="col-lg-4 col-md-12 mb-3">
        <div class="d-flex flex-column gap-1">
            <!-- St. Monthly Attendance Award -->
            <div class="card border-0 rounded-10 p-1" style="background-color: #e3f2fd; height: 70px;">
                <div class="d-flex align-items-start h-100">
                    <div class="flex-shrink-0 me-2" style="width: 35px; height: 35px; display: flex; align-items: center; justify-content: center;">
                        <span class="material-symbols-outlined" style="font-size: 28px; color: #ffd700;">emoji_events</span>
                    </div>
                    <div class="flex-grow-1 d-flex flex-column justify-content-center">
                        <h6 class="mb-1 fw-medium text-dark" style="font-size: 12px; line-height: 1.2;">St. Monthly Attendance Award</h6>
                        <p class="mb-0 text-dark" style="font-size: 11px; line-height: 1.2;">
                            <span class="badge bg-success" style="width: 6px; height: 6px; padding: 0; border-radius: 50%; display: inline-block; margin-right: 4px;"></span>
                            C1-011 | Hamza - Presents: 1
                        </p>
                    </div>
                </div>
            </div>

            <!-- Staff Monthly Att. Award -->
            <div class="card border-0 rounded-10 p-1" style="background-color: #e8f5e9; height: 70px;">
                <div class="d-flex align-items-start h-100">
                    <div class="flex-shrink-0 me-2" style="width: 35px; height: 35px; display: flex; align-items: center; justify-content: center;">
                        <span class="material-symbols-outlined" style="font-size: 28px; color: #ffd700;">emoji_events</span>
                    </div>
                    <div class="flex-grow-1 d-flex flex-column justify-content-center">
                        <h6 class="mb-1 fw-medium text-dark" style="font-size: 12px; line-height: 1.2;">Staff Monthly Att. Award</h6>
                        <p class="mb-0 text-dark" style="font-size: 11px; line-height: 1.2;">
                            <span class="badge bg-success" style="width: 6px; height: 6px; padding: 0; border-radius: 50%; display: inline-block; margin-right: 4px;"></span>
                            N/A | N/A - Presents: 0
                        </p>
                    </div>
                </div>
            </div>

            <!-- Student with Highest Dues -->
            <div class="card border-0 rounded-10 p-1" style="background-color: #fff9c4; height: 70px;">
                <div class="d-flex align-items-start h-100">
                    <div class="flex-shrink-0 me-2" style="width: 35px; height: 35px; display: flex; align-items: center; justify-content: center;">
                        <span class="material-symbols-outlined" style="font-size: 28px; color: #000;">sentiment_very_dissatisfied</span>
                    </div>
                    <div class="flex-grow-1 d-flex flex-column justify-content-center">
                        <h6 class="mb-1 fw-medium text-dark" style="font-size: 12px; line-height: 1.2;">Student with Highest Dues</h6>
                        <p class="mb-0 text-dark" style="font-size: 11px; line-height: 1.2;">
                            <span class="badge bg-danger" style="width: 6px; height: 6px; padding: 0; border-radius: 50%; display: inline-block; margin-right: 4px;"></span>
                            N/A | N/A - Due: 0.00
                        </p>
                    </div>
                </div>
            </div>

            <!-- Best Performance (Teacher) -->
            <div class="card border-0 rounded-10 p-1" style="background-color: #fce4ec; height: 70px;">
                <div class="d-flex align-items-start h-100">
                    <div class="flex-shrink-0 me-2" style="width: 35px; height: 35px; display: flex; align-items: center; justify-content: center;">
                        <span class="material-symbols-outlined" style="font-size: 28px; color: #ffd700;">emoji_events</span>
                    </div>
                    <div class="flex-grow-1 d-flex flex-column justify-content-center">
                        <h6 class="mb-1 fw-medium text-dark" style="font-size: 12px; line-height: 1.2;">Best Performance (Teacher)</h6>
                        <p class="mb-0 text-dark" style="font-size: 11px; line-height: 1.2;">
                            <span class="badge bg-success" style="width: 6px; height: 6px; padding: 0; border-radius: 50%; display: inline-block; margin-right: 4px;"></span>
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

<div class="row">
    <div class="col-lg-6">
        <div class="card bg-white p-20 rounded-10 border border-white mb-4">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <h3>Total Sales</h3>

                <div class="dropdown select-dropdown without-border">
                    <button class="dropdown-toggle bg-transparent text-secondary fs-15" data-bs-toggle="dropdown" aria-expanded="false">
                        Year 2025
                    </button>
                
                    <ul class="dropdown-menu dropdown-menu-end bg-white border-0 box-shadow rounded-10" data-simplebar>
                        <li>
                            <button class="dropdown-item text-secondary">Year 2025</button>
                        </li>
                        <li>
                            <button class="dropdown-item text-secondary">Year 2024</button>
                        </li>
                        <li>
                            <button class="dropdown-item text-secondary">Year 2023</button>
                        </li>
                    </ul>
                </div>
            </div>

            <div id="total_sales_chart" style="margin-bottom: -16px; margin-top: -1.5px;"></div>
        </div>
    </div>

    <div class="col-lg-6 col-xxl-3 col-xxxl-6">
        <div class="row">
            <div class="col-md-6 col-lg-12">
                <div class="card bg-white p-20 rounded-10 border border-white mb-4">
                    <div class="d-flex">
                        <div class="flex-grow-1">
                            <h3 class="mb-10">Total Orders</h3>
                            <h2 class="fs-26 fw-medium mb-0 lh-1">20,705</h2>
                        </div>
                        <div class="flex-shrink-0 ms-3">
                            <div class="bg-primary text-white text-center rounded-circle d-block" style="width: 75px; height: 75px; line-height: 105px;">
                                <i class="material-symbols-outlined fs-40">shopping_basket</i>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center" style="margin-top: 21px;">
                        <p class="mb-0 fs-14">4.75% Increase in orders last week</p>
                        <span class="d-flex align-content-center gap-1 bg-success bg-opacity-10 border border-success" style="padding: 3px 5px;">
                            <i class="material-symbols-outlined fs-14 text-success">trending_up</i>
                            <span class="lh-1 fs-14 text-success">4.75%</span>
                        </span>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-12">
                <div class="card bg-white p-20 rounded-10 border border-white mb-4">
                    <div class="d-flex">
                        <div class="flex-grow-1">
                            <h3 class="mb-10">Total Customers</h3>
                            <h2 class="fs-26 fw-medium mb-0 lh-1">84,127</h2>
                        </div>
                        <div class="flex-shrink-0 ms-3">
                            <div class="bg-info text-white text-center rounded-circle d-block" style="width: 75px; height: 75px; line-height: 105px;">
                                <i class="material-symbols-outlined fs-40">diversity_2</i>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center" style="margin-top: 21px;">
                        <p class="mb-0 fs-14">Total visitors decreased by 1.25%</p>
                        <span class="d-flex align-content-center gap-1 bg-danger bg-opacity-10 border border-danger" style="padding: 3px 5px;">
                            <i class="material-symbols-outlined fs-14 text-danger">trending_down</i>
                            <span class="lh-1 fs-14 text-danger">1.25%</span>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-12 col-xxl-3 col-xxxl-12">
        <div class="row">
            <div class="col-md-6 col-xxxl-6 col-xxl-12">
                <div class="card bg-white p-20 rounded-10 border border-white mb-4">
                    <div class="d-flex">
                        <div class="flex-grow-1">
                            <h3 class="mb-10">Total Revenue</h3>
                            <h2 class="fs-26 fw-medium mb-0 lh-1">$15,278</h2>
                        </div>
                        <div class="flex-shrink-0 ms-3">
                            <div class="bg-warning text-white text-center rounded-circle d-block" style="width: 75px; height: 75px; line-height: 116px;">
                                <i class="material-symbols-outlined fs-50">attach_money</i>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center" style="margin-top: 23px;">
                        <p class="mb-0 fs-14">Revenue increases this month</p>
                        <span class="d-flex align-content-center gap-1 bg-success bg-opacity-10 border border-success" style="padding: 3px 5px;">
                            <i class="material-symbols-outlined fs-14 text-success">trending_up</i>
                            <span class="lh-1 fs-14 text-success">3.15%</span>
                        </span>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xxxl-6 col-xxl-12">
                <div class="bg-primary-50 p-20 border rounded-10 border-primary-50 mb-4">
                    <h3 class="text-white mb-12">Sales Overview</h3>
                    <div class="d-flex flex-wrap gap-2 justify-content-between mb-14">
                        <div>
                            <span class="fs-14 text-white mb-1 d-block">Total Sales</span>
                            <h2 class="fs-20 fw-medium lh-1 text-white mb-0">9,586</h2>
                        </div>
                        <div>
                            <span class="fs-14 text-white mb-1 d-block">Monthly Sales</span>
                            <h2 class="fs-20 fw-medium lh-1 text-white mb-0">3,507</h2>
                        </div>
                        <div>
                            <span class="fs-14 text-white mb-1 d-block">Today's Sales</span>
                            <h2 class="fs-20 fw-medium lh-1 text-white mb-0">357</h2>
                        </div>
                    </div>
                    <div class="progress rounded-0 mb-6" role="progressbar" aria-label="Basic example" aria-valuenow="80" aria-valuemin="0" aria-valuemax="100" style="height: 3px; background-color: #6258cc;">
                        <div class="progress-bar rounded-0 bg-white" style="width: 80%; height: 3px;"></div>
                    </div>
                    <span class="fs-14 text-white d-block" style="margin-bottom: -6px;">20% Increase in last month</span>
                </div>
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
</script>
@endsection


