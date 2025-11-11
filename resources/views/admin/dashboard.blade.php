@extends('layouts.app')

@section('title', 'Super Admin Dashboard')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-3 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h4 class="mb-0 fs-16 fw-semibold">Welcome, {{ $admin->name }}!</h4>
                    <p class="mb-0 text-muted fs-13 mt-1">Super Admin Dashboard</p>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-success text-white">Super Admin</span>
                    <form action="{{ route('admin.logout') }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-danger">
                            <i class="ri-logout-box-r-line"></i> Logout
                        </button>
                    </form>
                </div>
            </div>

            <div class="row g-3 mt-2">
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm p-3" style="background: linear-gradient(135deg, #003471 0%, #004a9f 100%);">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <p class="mb-1 text-white-50 fs-12">Total Campuses</p>
                                <h3 class="mb-0 text-white">{{ \App\Models\Campus::count() }}</h3>
                            </div>
                            <i class="ri-building-line text-white" style="font-size: 32px; opacity: 0.7;"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm p-3" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%);">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <p class="mb-1 text-white-50 fs-12">Total Admins</p>
                                <h3 class="mb-0 text-white">{{ \App\Models\AdminRole::count() }}</h3>
                            </div>
                            <i class="ri-user-line text-white" style="font-size: 32px; opacity: 0.7;"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm p-3" style="background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <p class="mb-1 text-white-50 fs-12">Transport Routes</p>
                                <h3 class="mb-0 text-white">{{ \App\Models\Transport::count() }}</h3>
                            </div>
                            <i class="ri-roadster-line text-white" style="font-size: 32px; opacity: 0.7;"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm p-3" style="background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%);">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <p class="mb-1 text-white-50 fs-12">Super Admins</p>
                                <h3 class="mb-0 text-white">{{ \App\Models\AdminRole::where('super_admin', true)->count() }}</h3>
                            </div>
                            <i class="ri-shield-star-line text-white" style="font-size: 32px; opacity: 0.7;"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-3 mt-2">
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm p-3">
                        <h5 class="mb-3 fs-15 fw-semibold">Quick Actions</h5>
                        <div class="d-flex flex-column gap-2">
                            <a href="{{ route('manage.campuses') }}" class="btn btn-sm btn-outline-primary text-start">
                                <i class="ri-building-line me-2"></i> Manage Campuses
                            </a>
                            <a href="{{ route('admin.roles-management') }}" class="btn btn-sm btn-outline-primary text-start">
                                <i class="ri-user-settings-line me-2"></i> Manage Admin Roles
                            </a>
                            <a href="{{ route('transport.manage') }}" class="btn btn-sm btn-outline-primary text-start">
                                <i class="ri-roadster-line me-2"></i> Manage Transport
                            </a>
                            <a href="{{ route('school.noticeboard') }}" class="btn btn-sm btn-outline-primary text-start">
                                <i class="ri-notification-line me-2"></i> School Noticeboard
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm p-3">
                        <h5 class="mb-3 fs-15 fw-semibold">Admin Information</h5>
                        <div class="d-flex flex-column gap-2">
                            <div>
                                <small class="text-muted">Name:</small>
                                <p class="mb-0 fw-medium">{{ $admin->name }}</p>
                            </div>
                            <div>
                                <small class="text-muted">Email:</small>
                                <p class="mb-0 fw-medium">{{ $admin->email }}</p>
                            </div>
                            @if($admin->phone)
                            <div>
                                <small class="text-muted">Phone:</small>
                                <p class="mb-0 fw-medium">{{ $admin->phone }}</p>
                            </div>
                            @endif
                            <div>
                                <small class="text-muted">Role:</small>
                                <p class="mb-0">
                                    <span class="badge bg-success">Super Admin</span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

