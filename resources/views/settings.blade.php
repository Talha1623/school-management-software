@extends('layouts.app')

@section('title', 'Settings')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-4 mb-4">
            <div class="d-flex align-items-center mb-4">
                <span class="material-symbols-outlined me-2" style="font-size: 28px; color: #003471;">settings</span>
                <h3 class="mb-0 fw-bold" style="color: #003471;">Settings</h3>
            </div>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div class="row">
                <!-- Account Settings -->
                <div class="col-md-6 mb-4">
                    <div class="card border-0 shadow-sm rounded-10 p-4 h-100">
                        <h5 class="mb-4 fw-semibold" style="color: #003471;">
                            <span class="material-symbols-outlined" style="font-size: 20px; vertical-align: middle;">account_circle</span>
                            Account Settings
                        </h5>
                        
                        <div class="d-grid gap-2">
                            <a href="{{ route('change-password') }}" class="btn btn-outline-primary d-flex align-items-center justify-content-between">
                                <span>
                                    <span class="material-symbols-outlined me-2" style="font-size: 18px; vertical-align: middle;">lock</span>
                                    Change Password
                                </span>
                                <span class="material-symbols-outlined" style="font-size: 18px;">chevron_right</span>
                            </a>
                            
                            <a href="{{ route('profile') }}" class="btn btn-outline-primary d-flex align-items-center justify-content-between">
                                <span>
                                    <span class="material-symbols-outlined me-2" style="font-size: 18px; vertical-align: middle;">person</span>
                                    Edit Profile
                                </span>
                                <span class="material-symbols-outlined" style="font-size: 18px;">chevron_right</span>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Application Settings -->
                <div class="col-md-6 mb-4">
                    <div class="card border-0 shadow-sm rounded-10 p-4 h-100">
                        <h5 class="mb-4 fw-semibold" style="color: #003471;">
                            <span class="material-symbols-outlined" style="font-size: 20px; vertical-align: middle;">tune</span>
                            Application Settings
                        </h5>
                        
                        <div class="d-grid gap-2">
                            <a href="{{ route('language.switch', ['locale' => app()->getLocale() === 'ur' ? 'en' : 'ur']) }}" class="btn btn-outline-primary d-flex align-items-center justify-content-between">
                                <span>
                                    <span class="material-symbols-outlined me-2" style="font-size: 18px; vertical-align: middle;">language</span>
                                    Language: {{ app()->getLocale() === 'ur' ? 'Urdu' : 'English' }}
                                </span>
                                <span class="material-symbols-outlined" style="font-size: 18px;">chevron_right</span>
                            </a>
                            
                            <button class="btn btn-outline-primary d-flex align-items-center justify-content-between" onclick="alert('Theme settings coming soon!')">
                                <span>
                                    <span class="material-symbols-outlined me-2" style="font-size: 18px; vertical-align: middle;">palette</span>
                                    Theme Settings
                                </span>
                                <span class="material-symbols-outlined" style="font-size: 18px;">chevron_right</span>
                            </button>
                        </div>
                    </div>
                </div>

                @if(Auth::guard('admin')->check() && Auth::guard('admin')->user()->role === 'super_admin')
                <!-- Super Admin Settings -->
                <div class="col-md-6 mb-4">
                    <div class="card border-0 shadow-sm rounded-10 p-4 h-100">
                        <h5 class="mb-4 fw-semibold" style="color: #003471;">
                            <span class="material-symbols-outlined" style="font-size: 20px; vertical-align: middle;">admin_panel_settings</span>
                            Super Admin Settings
                        </h5>
                        
                        <div class="d-grid gap-2">
                            @if(Route::has('account-settings'))
                            <a href="{{ route('account-settings') }}" class="btn btn-outline-primary d-flex align-items-center justify-content-between">
                                <span>
                                    <span class="material-symbols-outlined me-2" style="font-size: 18px; vertical-align: middle;">manage_accounts</span>
                                    Account Settings
                                </span>
                                <span class="material-symbols-outlined" style="font-size: 18px;">chevron_right</span>
                            </a>
                            @endif
                            
                            @if(Route::has('thermal-printer.setting'))
                            <a href="{{ route('thermal-printer.setting') }}" class="btn btn-outline-primary d-flex align-items-center justify-content-between">
                                <span>
                                    <span class="material-symbols-outlined me-2" style="font-size: 18px; vertical-align: middle;">print</span>
                                    Thermal Printer Settings
                                </span>
                                <span class="material-symbols-outlined" style="font-size: 18px;">chevron_right</span>
                            </a>
                            @endif
                        </div>
                    </div>
                </div>
                @endif

                <!-- Privacy & Security -->
                <div class="col-md-6 mb-4">
                    <div class="card border-0 shadow-sm rounded-10 p-4 h-100">
                        <h5 class="mb-4 fw-semibold" style="color: #003471;">
                            <span class="material-symbols-outlined" style="font-size: 20px; vertical-align: middle;">security</span>
                            Privacy & Security
                        </h5>
                        
                        <div class="d-grid gap-2">
                            @if(Route::has('privacy-policy'))
                            <a href="{{ route('privacy-policy') }}" class="btn btn-outline-primary d-flex align-items-center justify-content-between">
                                <span>
                                    <span class="material-symbols-outlined me-2" style="font-size: 18px; vertical-align: middle;">privacy_tip</span>
                                    Privacy Policy
                                </span>
                                <span class="material-symbols-outlined" style="font-size: 18px;">chevron_right</span>
                            </a>
                            @endif
                            
                            @if(Route::has('terms-conditions'))
                            <a href="{{ route('terms-conditions') }}" class="btn btn-outline-primary d-flex align-items-center justify-content-between">
                                <span>
                                    <span class="material-symbols-outlined me-2" style="font-size: 18px; vertical-align: middle;">description</span>
                                    Terms & Conditions
                                </span>
                                <span class="material-symbols-outlined" style="font-size: 18px;">chevron_right</span>
                            </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

