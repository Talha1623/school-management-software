@extends('layouts.app')

@section('title', 'Facial Record Attendance')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-4 mb-4" style="box-shadow: 0 2px 12px rgba(0,0,0,0.08);">
            <!-- Title -->
            <div class="text-center mb-4">
                <h3 class="mb-3 fw-bold" style="color: #003471; font-size: 24px;">
                    <span class="material-symbols-outlined" style="font-size: 32px; vertical-align: middle; margin-right: 10px;">face</span>
                    Setup Guide for Face Recognition App
                </h3>
                <p class="text-muted mb-0" style="font-size: 16px;">Easy steps to set up Face Recognition App on Android for attendance management</p>
            </div>

            <!-- Getting Started Section -->
            <div class="section-header mb-4">
                <h4 class="fw-bold mb-0" style="color: #003471; font-size: 20px;">
                    <span class="material-symbols-outlined" style="font-size: 24px; vertical-align: middle; margin-right: 8px;">play_circle</span>
                    Getting Started
                </h4>
            </div>

            <!-- Instructions Section -->
            <div class="instructions-section">
                <!-- Step 1: Download App -->
                <div class="instruction-item">
                    <div class="instruction-number">1</div>
                    <div class="instruction-content">
                        <h5 class="fw-semibold mb-2" style="color: #003471;">Step 1: Download App</h5>
                        <p class="mb-2">Search for the app (FaceSync) in play store.</p>
                        <p class="mb-3">Download and install in your phone.</p>
                        <div class="tip-box">
                            <span class="material-symbols-outlined" style="font-size: 18px; vertical-align: middle; color: #ff9800; margin-right: 6px;">lightbulb</span>
                            <strong>Tip:</strong> If you are unable to search, you can download the app from this 
                            <a href="#" onclick="alert('Download link will be provided here'); return false;" style="color: #003471; text-decoration: underline;">link</a>.
                        </div>
                    </div>
                </div>

                <!-- Step 2: Register Users & Start Managing Attendance -->
                <div class="instruction-item">
                    <div class="instruction-number">2</div>
                    <div class="instruction-content">
                        <h5 class="fw-semibold mb-2" style="color: #003471;">Step 2: Register Users & Start Managing Attendance</h5>
                        <p class="mb-2">Open the FaceSync app you just downloaded from play store, and click on continue with google, select google account.</p>
                    </div>
                </div>

                <!-- How to use it Section -->
                <div class="section-header mt-5 mb-4">
                    <h4 class="fw-bold mb-0" style="color: #003471; font-size: 20px;">
                        <span class="material-symbols-outlined" style="font-size: 24px; vertical-align: middle; margin-right: 8px;">settings</span>
                        How to use it:
                    </h4>
                </div>

                <!-- Step 3 -->
                <div class="instruction-item">
                    <div class="instruction-number">3</div>
                    <div class="instruction-content">
                        <p class="mb-0">Click on Settings Icon and add your school software link in Host Api URL field and save</p>
                    </div>
                </div>

                <!-- Step 4 -->
                <div class="instruction-item">
                    <div class="instruction-number">4</div>
                    <div class="instruction-content">
                        <p class="mb-0">Now click on Get Users From Api Button In Settings.</p>
                    </div>
                </div>

                <!-- Step 5 -->
                <div class="instruction-item">
                    <div class="instruction-number">5</div>
                    <div class="instruction-content">
                        <p class="mb-0">Now all users will be added to FaceSync App, Now Register Faces for users by clicking on Users Icon in app home screen and then click on yellow register face button for each user.</p>
                    </div>
                </div>

                <!-- Step 6 -->
                <div class="instruction-item">
                    <div class="instruction-number">6</div>
                    <div class="instruction-content">
                        <p class="mb-0">User needs to face camera on phone, then follow the steps on screen like Blink/Smile.</p>
                    </div>
                </div>

                <!-- Step 7 -->
                <div class="instruction-item">
                    <div class="instruction-number">7</div>
                    <div class="instruction-content">
                        <p class="mb-0">Register Face for all users, users can mark attendance with id card barcode/qr code if face is not registered.</p>
                    </div>
                </div>

                <!-- Step 8 -->
                <div class="instruction-item">
                    <div class="instruction-number">8</div>
                    <div class="instruction-content">
                        <p class="mb-0">Click Save Attendance icon on homescreen of FaceSync app once attendance completed, you can save attendance daily, weekly or monthly.</p>
                    </div>
                </div>

                <!-- Tip Box -->
                <div class="instruction-item tip-item">
                    <div class="instruction-icon-tip">
                        <span class="material-symbols-outlined">lightbulb</span>
                    </div>
                    <div class="instruction-content">
                        <p class="mb-0"><strong>Tip:</strong> If you don't see your device, ensure it's powered on and connected. Restart your computer if needed.</p>
                    </div>
                </div>

                <!-- Supported Devices Section -->
                <div class="section-header mt-5 mb-4">
                    <h4 class="fw-bold mb-0" style="color: #003471; font-size: 20px;">
                        <span class="material-symbols-outlined" style="font-size: 24px; vertical-align: middle; margin-right: 8px;">devices</span>
                        Supported Devices
                    </h4>
                </div>

                <div class="instruction-item info-item">
                    <div class="instruction-icon-info">
                        <span class="material-symbols-outlined">android</span>
                    </div>
                    <div class="instruction-content">
                        <p class="mb-0">Face Recognition App works with all android devices, i.e smartphones, tablets etc.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .instructions-section {
        max-width: 900px;
        margin: 0 auto;
    }

    .section-header {
        padding: 15px 20px;
        background: linear-gradient(135deg, #003471 0%, #004a9f 100%);
        border-radius: 10px;
        color: white;
        margin-bottom: 20px;
    }

    .section-header h4 {
        color: white !important;
        margin: 0;
    }

    .instruction-item {
        display: flex;
        gap: 20px;
        margin-bottom: 20px;
        padding: 20px;
        background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
        border-radius: 12px;
        border-left: 4px solid #003471;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        transition: all 0.3s ease;
    }

    .instruction-item:hover {
        transform: translateX(4px);
        box-shadow: 0 4px 16px rgba(0, 52, 113, 0.1);
        border-left-color: #004a9f;
    }

    .instruction-item:last-child {
        margin-bottom: 0;
    }

    .instruction-number {
        min-width: 40px;
        height: 40px;
        background: linear-gradient(135deg, #003471 0%, #004a9f 100%);
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 16px;
        box-shadow: 0 4px 8px rgba(0, 52, 113, 0.3);
        flex-shrink: 0;
    }

    .instruction-icon-tip {
        min-width: 40px;
        height: 40px;
        background: linear-gradient(135deg, #ff9800 0%, #f57c00 100%);
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 4px 8px rgba(255, 152, 0, 0.3);
        flex-shrink: 0;
    }

    .instruction-icon-info {
        min-width: 40px;
        height: 40px;
        background: linear-gradient(135deg, #2196f3 0%, #0b7dda 100%);
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 4px 8px rgba(33, 150, 243, 0.3);
        flex-shrink: 0;
    }

    .instruction-icon-tip .material-symbols-outlined,
    .instruction-icon-info .material-symbols-outlined {
        font-size: 22px;
    }

    .instruction-content {
        flex: 1;
        padding-top: 2px;
    }

    .instruction-content h5 {
        font-size: 16px;
        margin-bottom: 12px;
    }

    .instruction-content p {
        color: #495057;
        font-size: 15px;
        line-height: 1.7;
        margin: 0;
    }

    .tip-box {
        background: linear-gradient(135deg, #fff3e0 0%, #ffe0b2 100%);
        padding: 12px 16px;
        border-radius: 8px;
        border-left: 3px solid #ff9800;
        margin-top: 12px;
        font-size: 14px;
    }

    .tip-box strong {
        color: #e65100;
    }

    .tip-item {
        background: linear-gradient(135deg, #fff3e0 0%, #ffe0b2 100%);
        border-left-color: #ff9800;
    }

    .info-item {
        background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
        border-left-color: #2196f3;
    }

    .info-item .instruction-content p {
        color: #1565c0;
        font-weight: 500;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .instruction-item {
            flex-direction: column;
            gap: 15px;
            padding: 15px;
        }

        .instruction-number,
        .instruction-icon-tip,
        .instruction-icon-info {
            align-self: flex-start;
        }

        .section-header {
            padding: 12px 15px;
        }

        .section-header h4 {
            font-size: 18px;
        }
    }
</style>
@endsection
