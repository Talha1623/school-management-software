@extends('layouts.app')

@section('title', 'Change Password')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-4 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="mb-0 fs-16 fw-semibold">Change Password</h4>
            </div>

            <!-- Toast Notification Container -->
            <div id="toastContainer" style="position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 300px; max-width: 400px;"></div>

            @if(session('success'))
                <div class="success-toast" id="successToast">
                    <div>
                        <span class="material-symbols-outlined">check_circle</span>
                        <span>{{ session('success') }}</span>
                    </div>
                    <button type="button" class="btn-close" onclick="this.parentElement.remove()"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="error-toast" id="errorToast">
                    <div>
                        <span class="material-symbols-outlined">error</span>
                        <span>{{ session('error') }}</span>
                    </div>
                    <button type="button" class="btn-close" onclick="this.parentElement.remove()"></button>
                </div>
            @endif

            <!-- User Info Fields (Read-only) -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">
                            Name
                        </label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471; height: 32px;">
                                <span class="material-symbols-outlined" style="font-size: 16px;">person</span>
                            </span>
                            <input type="text" 
                                   class="form-control" 
                                   value="{{ $admin->name ?? 'N/A' }}" 
                                   readonly
                                   style="height: 32px; padding: 0.35rem 0.65rem; font-size: 13px; background-color: #f8f9fa; cursor: not-allowed;">
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">
                            Email
                        </label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471; height: 32px;">
                                <span class="material-symbols-outlined" style="font-size: 16px;">email</span>
                            </span>
                            <input type="email" 
                                   class="form-control" 
                                   value="{{ $admin->email ?? 'N/A' }}" 
                                   readonly
                                   style="height: 32px; padding: 0.35rem 0.65rem; font-size: 13px; background-color: #f8f9fa; cursor: not-allowed;">
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">
                            Photo
                        </label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471; height: 32px;">
                                <span class="material-symbols-outlined" style="font-size: 16px;">image</span>
                            </span>
                            <div class="form-control d-flex align-items-center justify-content-center" style="height: 32px; padding: 0.35rem 0.65rem; background-color: #f8f9fa; cursor: not-allowed; border: 1px solid #ced4da;">
                                <img src="{{ asset('assets/images/admin.png') }}" alt="Admin Photo" style="width: 24px; height: 24px; border-radius: 50%; object-fit: cover;">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Change Password Form -->
            <form method="POST" action="{{ route('change-password.update') }}" id="changePasswordForm">
                @csrf
                @method('PUT')

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="current_password" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">
                                Current Password <span class="text-danger">*</span>
                            </label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471; height: 32px;">
                                    <span class="material-symbols-outlined" style="font-size: 16px;">lock</span>
                                </span>
                                <input type="password" 
                                       class="form-control @error('current_password') is-invalid @enderror" 
                                       id="current_password" 
                                       name="current_password" 
                                       placeholder="Enter current password" 
                                       required
                                       style="height: 32px; padding: 0.35rem 0.65rem; font-size: 13px;">
                                <button type="button" class="btn btn-outline-secondary password-toggle" data-target="current_password" style="height: 32px; padding: 0 12px;">
                                    <span class="material-symbols-outlined" style="font-size: 16px;">visibility</span>
                                </button>
                            </div>
                            @error('current_password')
                                <div class="text-danger fs-11 mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="new_password" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">
                                New Password <span class="text-danger">*</span>
                            </label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471; height: 32px;">
                                    <span class="material-symbols-outlined" style="font-size: 16px;">lock</span>
                                </span>
                                <input type="password" 
                                       class="form-control @error('new_password') is-invalid @enderror" 
                                       id="new_password" 
                                       name="new_password" 
                                       placeholder="Enter new password" 
                                       required
                                       minlength="6"
                                       style="height: 32px; padding: 0.35rem 0.65rem; font-size: 13px;">
                                <button type="button" class="btn btn-outline-secondary password-toggle" data-target="new_password" style="height: 32px; padding: 0 12px;">
                                    <span class="material-symbols-outlined" style="font-size: 16px;">visibility</span>
                                </button>
                            </div>
                            <small class="text-muted" style="font-size: 11px;">Minimum 6 characters</small>
                            @error('new_password')
                                <div class="text-danger fs-11 mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="new_password_confirmation" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">
                                Confirm New Password <span class="text-danger">*</span>
                            </label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471; height: 32px;">
                                    <span class="material-symbols-outlined" style="font-size: 16px;">lock</span>
                                </span>
                                <input type="password" 
                                       class="form-control" 
                                       id="new_password_confirmation" 
                                       name="new_password_confirmation" 
                                       placeholder="Confirm new password" 
                                       required
                                       minlength="6"
                                       style="height: 32px; padding: 0.35rem 0.65rem; font-size: 13px;">
                                <button type="button" class="btn btn-outline-secondary password-toggle" data-target="new_password_confirmation" style="height: 32px; padding: 0 12px;">
                                    <span class="material-symbols-outlined" style="font-size: 16px;">visibility</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex gap-2 mt-4">
                    <button type="submit" class="btn btn-primary px-4 py-2 d-inline-flex align-items-center gap-2 rounded-8" style="color: white;">
                        <span class="material-symbols-outlined" style="font-size: 18px; color: white;">save</span>
                        <span style="color: white;">Update Password</span>
                    </button>
                    <button type="button" class="btn btn-secondary px-4 py-2 rounded-8" onclick="resetForm()">
                        Reset
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    .password-toggle {
        cursor: pointer;
        border-left: none !important;
    }

    .password-toggle:hover {
        background-color: #e9ecef;
    }

    /* Toast Notification Styling */
    .success-toast,
    .error-toast {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
        min-width: 300px;
        max-width: 400px;
        padding: 12px 16px;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        animation: slideInDown 0.3s ease-out;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
    }

    .success-toast {
        background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        color: white;
        border: none;
    }

    .error-toast {
        background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
        color: white;
        border: none;
    }

    .success-toast .btn-close,
    .error-toast .btn-close {
        filter: brightness(0) invert(1);
        opacity: 0.9;
        padding: 0;
        width: 20px;
        height: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-left: auto;
    }

    .success-toast .btn-close:hover,
    .error-toast .btn-close:hover {
        opacity: 1;
    }

    .success-toast .material-symbols-outlined,
    .error-toast .material-symbols-outlined {
        font-size: 20px;
        flex-shrink: 0;
    }

    .success-toast > div,
    .error-toast > div {
        display: flex;
        align-items: center;
        gap: 8px;
        flex: 1;
    }

    @keyframes slideInDown {
        from {
            transform: translateY(-100%);
            opacity: 0;
        }
        to {
            transform: translateY(0);
            opacity: 1;
        }
    }
</style>

<script>
// Password toggle functionality
document.querySelectorAll('.password-toggle').forEach(button => {
    button.addEventListener('click', function() {
        const targetId = this.getAttribute('data-target');
        const input = document.getElementById(targetId);
        const icon = this.querySelector('.material-symbols-outlined');
        
        if (input.type === 'password') {
            input.type = 'text';
            icon.textContent = 'visibility_off';
        } else {
            input.type = 'password';
            icon.textContent = 'visibility';
        }
    });
});

// Auto-dismiss toast notifications
document.addEventListener('DOMContentLoaded', function() {
    const successToast = document.getElementById('successToast');
    const errorToast = document.getElementById('errorToast');

    if (successToast) {
        setTimeout(() => {
            successToast.style.animation = 'slideOutUp 0.3s ease-out';
            setTimeout(() => successToast.remove(), 300);
        }, 3000);
    }

    if (errorToast) {
        setTimeout(() => {
            errorToast.style.animation = 'slideOutUp 0.3s ease-out';
            setTimeout(() => errorToast.remove(), 300);
        }, 4000);
    }
});

// Reset form
function resetForm() {
    document.getElementById('changePasswordForm').reset();
    document.querySelectorAll('.password-toggle .material-symbols-outlined').forEach(icon => {
        icon.textContent = 'visibility';
    });
    document.querySelectorAll('input[type="password"], input[type="text"]').forEach(input => {
        if (input.id !== 'new_password_confirmation') {
            input.type = 'password';
        }
    });
}

// Form validation
document.getElementById('changePasswordForm').addEventListener('submit', function(e) {
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = document.getElementById('new_password_confirmation').value;

    if (newPassword !== confirmPassword) {
        e.preventDefault();
        alert('New password and confirm password do not match!');
        return false;
    }

    if (newPassword.length < 6) {
        e.preventDefault();
        alert('New password must be at least 6 characters long!');
        return false;
    }
});
</script>
@endsection

