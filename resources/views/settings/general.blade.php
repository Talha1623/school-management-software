@extends('layouts.app')

@section('title', 'General Settings')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-4 mb-4">
            <div class="d-flex align-items-center mb-4">
                <span class="material-symbols-outlined me-2" style="font-size: 28px; color: #003471;">settings</span>
                <h3 class="mb-0 fw-bold" style="color: #003471;">General Settings</h3>
            </div>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <form method="POST" action="{{ route('settings.general.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="row">
                    <!-- Left Column: System Settings (2 rows) -->
                    <div class="col-md-8 mb-4">
                        <!-- System Settings - Row 1 -->
                        <div class="card border-0 shadow-sm rounded-10 p-4 mb-4">
                            <h5 class="mb-4 fw-semibold" style="color: #003471;">
                                <span class="material-symbols-outlined" style="font-size: 20px; vertical-align: middle;">settings</span>
                                System Settings
                            </h5>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-medium">School Name</label>
                                    <input type="text" class="form-control" name="school_name" placeholder="Enter school name">
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-medium">SMS Signature</label>
                                    <input type="text" class="form-control" name="sms_signature" placeholder="Enter SMS signature">
                                </div>
                                
                                <div class="col-md-12 mb-3">
                                    <label class="form-label fw-medium">Address</label>
                                    <textarea class="form-control" name="address" rows="2" placeholder="Enter school address"></textarea>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-medium">School Phone</label>
                                    <input type="text" class="form-control" name="school_phone" placeholder="Enter phone number">
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-medium">School Email</label>
                                    <input type="email" class="form-control" name="school_email" placeholder="Enter email">
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-medium">Currency</label>
                                    <select class="form-select" name="currency">
                                        <option value="PKR">PKR (₨)</option>
                                        <option value="USD">USD ($)</option>
                                        <option value="EUR">EUR (€)</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-medium">Timezone</label>
                                    <select class="form-select" name="timezone">
                                        <option value="Asia/Karachi">Asia/Karachi (PKT)</option>
                                        <option value="UTC">UTC</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- System Settings - Row 2 -->
                        <div class="card border-0 shadow-sm rounded-10 p-4 mb-4">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-medium">Two-Factor Auth</label>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="two_factor_auth" name="two_factor_auth">
                                        <label class="form-check-label" for="two_factor_auth">Enable Two-Factor Authentication</label>
                                    </div>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-medium">Running Session</label>
                                    <select class="form-select" name="running_session">
                                        <option value="2024-2025">2024-2025</option>
                                        <option value="2025-2026">2025-2026</option>
                                        <option value="2026-2027">2026-2027</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-medium">Show Class List On Dashboard</label>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="show_class_list" name="show_class_list" checked>
                                        <label class="form-check-label" for="show_class_list">Enable</label>
                                    </div>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-medium">Institution Type</label>
                                    <select class="form-select" name="institution_type">
                                        <option value="school">School</option>
                                        <option value="college">College</option>
                                        <option value="university">University</option>
                                        <option value="academy">Academy</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-medium">Roll ID Sequence</label>
                                    <select class="form-select" name="roll_id_sequence">
                                        <option value="auto">Auto</option>
                                        <option value="manual">Manual</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-medium">Barcode Att. IN/OUT Msg</label>
                                    <input type="text" class="form-control" name="barcode_msg" placeholder="Enter barcode message">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Theme Settings & Upload Logo -->
                    <div class="col-md-4 mb-4">
                        <!-- Theme Settings -->
                        <div class="card border-0 shadow-sm rounded-10 p-4 mb-4">
                            <h5 class="mb-4 fw-semibold" style="color: #003471;">
                                <span class="material-symbols-outlined" style="font-size: 20px; vertical-align: middle;">palette</span>
                                Theme Settings
                            </h5>
                            
                            <div class="mb-3">
                                <label class="form-label fw-medium mb-2">Primary Color</label>
                                <div class="d-flex gap-2 align-items-center">
                                    <input type="color" class="form-control form-control-color" name="primary_color" value="#003471" title="Choose primary color">
                                    <input type="text" class="form-control" value="#003471" readonly style="max-width: 100px;">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label fw-medium mb-2">Secondary Color</label>
                                <div class="d-flex gap-2 align-items-center">
                                    <input type="color" class="form-control form-control-color" name="secondary_color" value="#004a9f" title="Choose secondary color">
                                    <input type="text" class="form-control" value="#004a9f" readonly style="max-width: 100px;">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label fw-medium mb-2">Accent Color</label>
                                <div class="d-flex gap-2 align-items-center">
                                    <input type="color" class="form-control form-control-color" name="accent_color" value="#2196F3" title="Choose accent color">
                                    <input type="text" class="form-control" value="#2196F3" readonly style="max-width: 100px;">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label fw-medium mb-2">Success Color</label>
                                <div class="d-flex gap-2 align-items-center">
                                    <input type="color" class="form-control form-control-color" name="success_color" value="#28a745" title="Choose success color">
                                    <input type="text" class="form-control" value="#28a745" readonly style="max-width: 100px;">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label fw-medium mb-2">Warning Color</label>
                                <div class="d-flex gap-2 align-items-center">
                                    <input type="color" class="form-control form-control-color" name="warning_color" value="#ffc107" title="Choose warning color">
                                    <input type="text" class="form-control" value="#ffc107" readonly style="max-width: 100px;">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label fw-medium mb-2">Danger Color</label>
                                <div class="d-flex gap-2 align-items-center">
                                    <input type="color" class="form-control form-control-color" name="danger_color" value="#dc3545" title="Choose danger color">
                                    <input type="text" class="form-control" value="#dc3545" readonly style="max-width: 100px;">
                                </div>
                            </div>
                        </div>

                        <!-- Upload Logo & System Name -->
                        <div class="card border-0 shadow-sm rounded-10 p-4">
                            <h5 class="mb-4 fw-semibold" style="color: #003471;">
                                <span class="material-symbols-outlined" style="font-size: 20px; vertical-align: middle;">image</span>
                                Upload Logo & System Name
                            </h5>
                            
                            <!-- System Name Field -->
                            <div class="mb-3">
                                <label class="form-label fw-medium">System Name</label>
                                <input type="text" class="form-control" id="systemName" name="system_name" value="ICMS" placeholder="Enter system name" onchange="updateSystemName(this.value)">
                                <small class="text-muted">This will change the "ICMS" text in sidebar and header</small>
                            </div>
                            
                            <div class="mb-3 text-center">
                                <div class="border rounded-10 p-4" style="background-color: #f8f9fa; min-height: 200px; display: flex; align-items: center; justify-content: center; flex-direction: column;">
                                    <div class="text-center mb-3">
                                        <img id="logoPreview" src="{{ asset('assets/images/logo-icon.png') }}" alt="Logo Preview" style="max-width: 150px; max-height: 100px; display: block; margin: 0 auto;">
                                    </div>
                                    <div class="text-center">
                                        <span id="systemNamePreview" class="logo-text text-secondary fw-semibold" style="font-size: 18px;">ICMS</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label fw-medium">Upload School Logo</label>
                                <input type="file" class="form-control" id="logoUpload" name="logo" accept="image/*" onchange="previewLogo(this)">
                                <small class="text-muted">Recommended size: 200x150px (PNG, JPG)</small>
                            </div>
                            
                            <div class="d-grid">
                                <button type="button" class="btn btn-outline-primary" onclick="document.getElementById('logoUpload').click()">
                                    <span class="material-symbols-outlined" style="font-size: 18px; vertical-align: middle;">upload</span>
                                    Choose File
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <button type="button" class="btn btn-outline-secondary">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <span class="material-symbols-outlined" style="font-size: 18px; vertical-align: middle;">save</span>
                        Save Settings
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function previewLogo(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const logoUrl = e.target.result;
            // Update preview
            document.getElementById('logoPreview').src = logoUrl;
            document.getElementById('logoPreview').style.display = 'block';
            
            // Update logo in sidebar dynamically
            const sidebarLogo = document.querySelector('.sidebar-area .logo img');
            if (sidebarLogo) {
                sidebarLogo.src = logoUrl;
            }
            
            // Update logo in header if exists
            const headerLogo = document.querySelector('#header-area .logo img, .header-area .logo img');
            if (headerLogo) {
                headerLogo.src = logoUrl;
            }
            
            // Update logo in preloader
            const preloaderLogo = document.getElementById('preloaderLogo');
            if (preloaderLogo) {
                preloaderLogo.src = logoUrl;
            }
            
            // Store in localStorage for persistence
            localStorage.setItem('schoolLogo', logoUrl);
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function updateSystemName(name) {
    if (!name) name = 'ICMS';
    
    // Update preview
    document.getElementById('systemNamePreview').textContent = name;
    
    // Update system name in sidebar dynamically
    const sidebarText = document.querySelector('.sidebar-area .logo-text');
    if (sidebarText) {
        sidebarText.textContent = name;
    }
    
    // Update system name in header if exists
    const headerText = document.querySelector('#header-area .logo-text, .header-area .logo-text');
    if (headerText) {
        headerText.textContent = name;
    }
    
    // Update preloader text
    const preloaderText = document.getElementById('preloaderText');
    if (preloaderText) {
        preloaderText.innerHTML = '';
        for (let i = 0; i < name.length; i++) {
            const span = document.createElement('span');
            span.className = 'd-inline-block';
            span.textContent = name[i];
            preloaderText.appendChild(span);
        }
    }
    
    // Update page title
    document.title = name + ' Management System';
    
    // Store in localStorage for persistence
    localStorage.setItem('systemName', name);
}

// Update color text inputs when color picker changes
document.addEventListener('DOMContentLoaded', function() {
    const colorInputs = document.querySelectorAll('input[type="color"]');
    colorInputs.forEach(input => {
        const textInput = input.nextElementSibling;
        if (textInput && textInput.tagName === 'INPUT') {
            input.addEventListener('input', function() {
                textInput.value = this.value;
            });
        }
    });
});

// Ensure sidebar stays open on General Settings page
document.addEventListener('DOMContentLoaded', function() {
    document.body.setAttribute("sidebar-data-theme", "sidebar-show");
    const sidebarArea = document.getElementById('sidebar-area');
    if (sidebarArea) {
        sidebarArea.style.display = '';
        sidebarArea.classList.remove('sidebar-hide');
        sidebarArea.classList.add('sidebar-show');
    }
    window.addEventListener('resize', function() {
        if (window.innerWidth > 992) {
            document.body.setAttribute("sidebar-data-theme", "sidebar-show");
        }
    });
    
    // Load saved logo and system name from localStorage
    const savedLogo = localStorage.getItem('schoolLogo');
    const savedName = localStorage.getItem('systemName');
    
    // Load saved logo and system name from localStorage
    const savedLogo = localStorage.getItem('schoolLogo');
    const savedName = localStorage.getItem('systemName');
    
    if (savedLogo) {
        document.getElementById('logoPreview').src = savedLogo;
        const sidebarLogo = document.querySelector('.sidebar-area .logo img');
        if (sidebarLogo) sidebarLogo.src = savedLogo;
    }
    
    if (savedName) {
        document.getElementById('systemName').value = savedName;
        updateSystemName(savedName);
    }
});
</script>
@endsection
