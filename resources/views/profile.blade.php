@extends('layouts.app')

@section('title', 'My Profile')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-4 mb-4">
            <div class="d-flex align-items-center mb-4">
                <span class="material-symbols-outlined me-2" style="font-size: 28px; color: #003471;">person</span>
                <h3 class="mb-0 fw-bold" style="color: #003471;">My Profile</h3>
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
                <!-- Profile Picture Section -->
                <div class="col-md-4 mb-4">
                    <div class="card border-0 shadow-sm rounded-10 p-4 text-center">
                        <div class="mb-3">
                            <div class="position-relative d-inline-block">
                                <img src="{{ asset('assets/images/default-avatar.png') }}" alt="Profile" class="rounded-circle" style="width: 150px; height: 150px; object-fit: cover; border: 4px solid #003471;">
                                <button class="btn btn-sm btn-primary position-absolute" style="bottom: 10px; right: 10px; border-radius: 50%; width: 40px; height: 40px; padding: 0;">
                                    <span class="material-symbols-outlined" style="font-size: 20px;">camera_alt</span>
                                </button>
                            </div>
                        </div>
                        <h4 class="mb-1">
                            @if(Auth::guard('admin')->check())
                                {{ Auth::guard('admin')->user()->name ?? 'Admin User' }}
                            @elseif(Auth::guard('staff')->check())
                                {{ Auth::guard('staff')->user()->name ?? 'Staff User' }}
                            @else
                                {{ Auth::user()->name ?? 'User' }}
                            @endif
                        </h4>
                        <p class="text-muted mb-0">
                            @if(Auth::guard('admin')->check())
                                Administrator
                            @elseif(Auth::guard('staff')->check())
                                Staff Member
                            @else
                                User
                            @endif
                        </p>
                    </div>
                </div>

                <!-- Profile Information -->
                <div class="col-md-8 mb-4">
                    <div class="card border-0 shadow-sm rounded-10 p-4">
                        <h5 class="mb-4 fw-semibold" style="color: #003471;">
                            <span class="material-symbols-outlined" style="font-size: 20px; vertical-align: middle;">info</span>
                            Profile Information
                        </h5>
                        
                        <div class="row mb-3">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-medium text-secondary">Full Name</label>
                                <div class="form-control bg-light border-0">
                                    @if(Auth::guard('admin')->check())
                                        {{ Auth::guard('admin')->user()->name ?? 'N/A' }}
                                    @elseif(Auth::guard('staff')->check())
                                        {{ Auth::guard('staff')->user()->name ?? 'N/A' }}
                                    @else
                                        {{ Auth::user()->name ?? 'N/A' }}
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-medium text-secondary">Email</label>
                                <div class="form-control bg-light border-0">
                                    @if(Auth::guard('admin')->check())
                                        {{ Auth::guard('admin')->user()->email ?? 'N/A' }}
                                    @elseif(Auth::guard('staff')->check())
                                        {{ Auth::guard('staff')->user()->email ?? 'N/A' }}
                                    @else
                                        {{ Auth::user()->email ?? 'N/A' }}
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-medium text-secondary">Role</label>
                                <div class="form-control bg-light border-0">
                                    @if(Auth::guard('admin')->check())
                                        Administrator
                                    @elseif(Auth::guard('staff')->check())
                                        Staff Member
                                    @else
                                        User
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-medium text-secondary">Member Since</label>
                                <div class="form-control bg-light border-0">
                                    @if(Auth::guard('admin')->check())
                                        {{ Auth::guard('admin')->user()->created_at ? Auth::guard('admin')->user()->created_at->format('M d, Y') : 'N/A' }}
                                    @elseif(Auth::guard('staff')->check())
                                        {{ Auth::guard('staff')->user()->created_at ? Auth::guard('staff')->user()->created_at->format('M d, Y') : 'N/A' }}
                                    @else
                                        {{ Auth::user()->created_at ? Auth::user()->created_at->format('M d, Y') : 'N/A' }}
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="d-flex gap-2 mt-4">
                            <a href="{{ route('change-password') }}" class="btn btn-primary">
                                <span class="material-symbols-outlined" style="font-size: 18px; vertical-align: middle;">lock</span>
                                Change Password
                            </a>
                            <a href="{{ route('settings') }}" class="btn btn-outline-primary">
                                <span class="material-symbols-outlined" style="font-size: 18px; vertical-align: middle;">settings</span>
                                Settings
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

