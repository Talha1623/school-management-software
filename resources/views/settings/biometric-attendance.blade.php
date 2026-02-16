@extends('layouts.app')

@section('title', 'Biometric Attendance Settings')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-4 mb-4">
            <!-- Header -->
            <div class="d-flex align-items-center mb-4">
                <a href="{{ route('settings') }}" class="text-decoration-none me-3">
                    <span class="material-symbols-outlined" style="font-size: 24px; color: #003471;">arrow_back</span>
                </a>
                <span class="material-symbols-outlined me-2" style="font-size: 28px; color: #003471;">fingerprint</span>
                <h3 class="mb-0 fw-bold" style="color: #003471;">Biometric Attendance Settings</h3>
            </div>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <!-- Bio Token Section -->
            <div class="card border-0 shadow-sm mb-4" style="border-radius: 8px;">
                <div class="card-body p-4">
                    <label class="form-label fw-semibold mb-2" style="color: #003471; font-size: 14px;">Bio Token</label>
                    <div class="input-group mb-2">
                        <input type="text" class="form-control" id="bioTokenInput" value="{{ $settings->bio_token ?? '' }}" readonly style="background-color: #f8f9fa; font-family: monospace;">
                        <button type="button" class="btn btn-primary" onclick="regenerateToken()">
                            <span class="material-symbols-outlined" style="font-size: 18px; vertical-align: middle;">refresh</span>
                            Regenerate
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="copyToken()">
                            <span class="material-symbols-outlined" style="font-size: 18px; vertical-align: middle;">content_copy</span>
                            Copy
                        </button>
                    </div>
                    <small class="text-muted">Use this token in BioAttendance app</small>
                </div>
            </div>

            <!-- Campus ID Section -->
            <div class="card border-0 shadow-sm mb-4" style="border-radius: 8px;">
                <div class="card-body p-4">
                    <form action="{{ route('settings.biometric-attendance.update') }}" method="POST">
                        @csrf
                        @method('PUT')
                        <label class="form-label fw-semibold mb-2" style="color: #003471; font-size: 14px;">Campus ID</label>
                        <div class="input-group mb-2">
                            <input type="number" class="form-control" name="campus_id" value="{{ $settings->campus_id ?? 1 }}" min="1" required>
                            <button type="submit" class="btn btn-primary">
                                <span class="material-symbols-outlined" style="font-size: 18px; vertical-align: middle;">save</span>
                                Save
                            </button>
                        </div>
                        <small class="text-muted">Set this Campus ID per device in app</small>
                    </form>
                </div>
            </div>

            <!-- BioAttendance App Setup Section -->
            <div class="card border-0 shadow-sm mb-4" style="border-radius: 8px; border-left: 4px solid #28a745;">
                <div class="card-header bg-light d-flex align-items-center" style="border-radius: 8px 8px 0 0;">
                    <span class="material-symbols-outlined me-2" style="color: #28a745;">info</span>
                    <h5 class="mb-0 fw-semibold" style="color: #28a745;">BioAttendance App Setup</h5>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="p-3 bg-light rounded">
                                <label class="form-label fw-semibold mb-1" style="color: #003471; font-size: 12px;">Host URL</label>
                                <p class="mb-0" style="color: #dc3545; font-family: monospace; font-size: 13px; word-break: break-all;">https://royalgrammar.ourcampus.cloud/</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3 bg-light rounded">
                                <label class="form-label fw-semibold mb-1" style="color: #003471; font-size: 12px;">Campus ID</label>
                                <p class="mb-0" style="color: #dc3545; font-family: monospace; font-size: 13px;">{{ $settings->campus_id ?? 1 }}</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3 bg-light rounded">
                                <label class="form-label fw-semibold mb-1" style="color: #003471; font-size: 12px;">Bio Token</label>
                                <p class="mb-0" style="color: #dc3545; font-family: monospace; font-size: 13px; word-break: break-all;">{{ $settings->bio_token ?? 'Not Generated' }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Steps Section -->
            <div class="card border-0 shadow-sm" style="border-radius: 8px; background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);">
                <div class="card-body p-4">
                    <h5 class="text-white mb-3 fw-semibold">Steps:</h5>
                    <ol class="text-white mb-0" style="padding-left: 20px;">
                        <li class="mb-2">Open BioAttendance app → Select "School Software"</li>
                        <li class="mb-2">Enter Host URL: <strong>https://royalgrammar.ourcampus.cloud/</strong></li>
                        <li class="mb-2">Enter Bio Token from above</li>
                        <li class="mb-0">Set Campus ID per device in Device Management</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function copyToken() {
    const tokenInput = document.getElementById('bioTokenInput');
    tokenInput.select();
    tokenInput.setSelectionRange(0, 99999); // For mobile devices
    
    navigator.clipboard.writeText(tokenInput.value).then(function() {
        // Show success message
        const btn = event.target.closest('button');
        const originalHtml = btn.innerHTML;
        btn.innerHTML = '<span class="material-symbols-outlined" style="font-size: 18px; vertical-align: middle;">check</span> Copied!';
        btn.classList.remove('btn-secondary');
        btn.classList.add('btn-success');
        
        setTimeout(function() {
            btn.innerHTML = originalHtml;
            btn.classList.remove('btn-success');
            btn.classList.add('btn-secondary');
        }, 2000);
    }).catch(function(err) {
        alert('Failed to copy token. Please select and copy manually.');
    });
}

function regenerateToken() {
    if (confirm('Are you sure you want to regenerate the Bio Token? This will invalidate the current token.')) {
        window.location.href = '{{ route("settings.biometric-attendance.regenerate-token") }}';
    }
}
</script>
@endsection
