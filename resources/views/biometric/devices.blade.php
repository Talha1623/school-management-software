@extends('layouts.app')

@section('title', 'Manage Biometric Device')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-4 mb-4">
            <!-- Title -->
            <div class="text-center mb-4">
                <h3 class="mb-3 fw-bold" style="color: #003471; font-size: 24px;">
                    <span class="material-symbols-outlined" style="font-size: 32px; vertical-align: middle; margin-right: 10px;">fingerprint</span>
                    Biometric Device Connection For Attendance
                </h3>
            </div>

            <!-- Supported Devices Section -->
            <div class="mb-4">
                <h4 class="fw-semibold mb-3" style="color: #003471; font-size: 18px;">
                    <span class="material-symbols-outlined" style="font-size: 20px; vertical-align: middle;">devices</span>
                    Supported Devices
                </h4>
                
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="card border h-100" style="border-radius: 8px;">
                            <div class="card-body text-center">
                                <h5 class="fw-bold mb-2">K40</h5>
                                <p class="mb-0 text-muted" style="font-size: 13px;">Fingerprint & RFID card time attendance terminal</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border h-100" style="border-radius: 8px;">
                            <div class="card-body text-center">
                                <h5 class="fw-bold mb-2">MB20</h5>
                                <p class="mb-0 text-muted" style="font-size: 13px;">Fingerprint with optional face recognition</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border h-100" style="border-radius: 8px;">
                            <div class="card-body text-center">
                                <h5 class="fw-bold mb-2">IN01</h5>
                                <p class="mb-0 text-muted" style="font-size: 13px;">Fingerprint & RFID card reader</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Step-by-Step Instructions -->
            <div class="mb-4">
                <h4 class="fw-semibold mb-3" style="color: #003471; font-size: 18px;">
                    <span class="material-symbols-outlined" style="font-size: 20px; vertical-align: middle;">list</span>
                    Step-by-Step Setup Guide
                </h4>

                <!-- Step 1 -->
                <div class="card mb-3" style="border-left: 4px solid #003471;">
                    <div class="card-body">
                        <h5 class="fw-semibold mb-3" style="color: #003471;">
                            <span class="badge bg-primary me-2">Step 1</span>
                            Prepare Your Device
                        </h5>
                        <ol class="mb-0 ps-3">
                            <li class="mb-2">Connect your biometric device to the same network as your computer (via cable or WiFi)</li>
                            <li class="mb-0">Ensure the device is powered on and note down the IP address from device settings</li>
                        </ol>
                    </div>
                </div>

                <!-- Step 2 -->
                <div class="card mb-3" style="border-left: 4px solid #28a745;">
                    <div class="card-body">
                        <h5 class="fw-semibold mb-3" style="color: #28a745;">
                            <span class="badge bg-success me-2">Step 2</span>
                            Install BioAttendance App
                        </h5>
                        <ol class="mb-0 ps-3">
                            <li class="mb-2">Download and extract the BioAttendance zip file</li>
                            <li class="mb-2">Run the installer and follow the setup wizard</li>
                            <li class="mb-0">Launch BioAttendance from desktop shortcut</li>
                        </ol>
                    </div>
                </div>

                <!-- Step 3 -->
                <div class="card mb-3" style="border-left: 4px solid #ffc107;">
                    <div class="card-body">
                        <h5 class="fw-semibold mb-3" style="color: #856404;">
                            <span class="badge bg-warning text-dark me-2">Step 3</span>
                            Connect to School Software
                        </h5>
                        <ol class="mb-0 ps-3">
                            <li class="mb-2">Select "School Software" on the welcome screen</li>
                            <li class="mb-2">Enter your Host URL: <strong>https://royalgrammar.ourcampus.cloud/</strong></li>
                            <li class="mb-2">Enter your Bio Token from Settings > Biometric Settings</li>
                            <li class="mb-0">Click "Verify & Connect"</li>
                        </ol>
                    </div>
                </div>

                <!-- Step 4 -->
                <div class="card mb-3" style="border-left: 4px solid #17a2b8;">
                    <div class="card-body">
                        <h5 class="fw-semibold mb-3" style="color: #17a2b8;">
                            <span class="badge bg-info me-2">Step 4</span>
                            Add Your Biometric Device
                        </h5>
                        <ol class="mb-0 ps-3">
                            <li class="mb-2">Go to Device tab and click "+ Add Device"</li>
                            <li class="mb-2">Enter device Name, IP Address, Port (usually 4370), and Comm Key (0 if none)</li>
                            <li class="mb-2">Set Campus ID for this device (each device can sync to different campus)</li>
                            <li class="mb-0">Click "Save Device" then "Connect"</li>
                        </ol>
                    </div>
                </div>

                <!-- Step 5 -->
                <div class="card mb-3" style="border-left: 4px solid #dc3545;">
                    <div class="card-body">
                        <h5 class="fw-semibold mb-3" style="color: #dc3545;">
                            <span class="badge bg-danger me-2">Step 5</span>
                            Sync Users & Attendance
                        </h5>
                        <ol class="mb-0 ps-3">
                            <li class="mb-2">Go to Members tab → Click "Get from School" to download students/teachers</li>
                            <li class="mb-2">Click "Upload to Device" to sync users to biometric machine</li>
                            <li class="mb-2">Register fingerprint/face on the device for each user</li>
                            <li class="mb-2">Users can now mark attendance on the biometric device</li>
                            <li class="mb-0">Go to Attendance tab → Click "Pull & Sync" to upload attendance to school software</li>
                        </ol>
                    </div>
                </div>
            </div>

            <!-- Multi-Campus Support -->
            <div class="card mb-4" style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); border: 1px solid #dee2e6;">
                <div class="card-body">
                    <h4 class="fw-semibold mb-3" style="color: #003471; font-size: 18px;">
                        <span class="material-symbols-outlined" style="font-size: 20px; vertical-align: middle;">apartment</span>
                        Multi-Campus Support
                    </h4>
                    <p class="mb-3"><strong>Multiple Devices, Different Campuses:</strong></p>
                    <p class="mb-3">You can add multiple biometric devices and assign each to a different campus. For example:</p>
                    <ul class="mb-3">
                        <li class="mb-2"><strong>Device "Main Building"</strong> → Campus ID: 1</li>
                        <li class="mb-2"><strong>Device "Science Block"</strong> → Campus ID: 1</li>
                        <li class="mb-0"><strong>Device "Branch Campus"</strong> → Campus ID: 2</li>
                    </ul>
                    <p class="mb-0"><strong>Each device will sync attendance to its assigned campus automatically.</strong></p>
                </div>
            </div>

            <!-- Auto-Sync Feature -->
            <div class="alert alert-info mb-0" style="border-left: 4px solid #17a2b8;">
                <div class="d-flex align-items-start">
                    <span class="material-symbols-outlined me-2" style="font-size: 24px; color: #17a2b8;">sync</span>
                    <div>
                        <h5 class="fw-semibold mb-2" style="color: #17a2b8;">Auto-Sync Feature</h5>
                        <p class="mb-0">Enable auto-sync in settings to automatically upload attendance every 10 minutes without manual intervention. The app runs in system tray.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.card {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

ol li {
    padding-left: 5px;
    margin-bottom: 8px;
}

.badge {
    font-size: 14px;
    padding: 6px 12px;
}
</style>
@endsection
