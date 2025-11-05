@extends('layouts.app')

@section('title', 'Bio Metric Attendance')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-4 mb-4" style="box-shadow: 0 2px 12px rgba(0,0,0,0.08);">
            <!-- Title -->
            <div class="text-center mb-4">
                <h3 class="mb-3 fw-bold" style="color: #003471; font-size: 24px;">
                    <span class="material-symbols-outlined" style="font-size: 32px; vertical-align: middle; margin-right: 10px;">fingerprint</span>
                    Biometric Device Connection For Attendance
                </h3>
            </div>

            <!-- Description/Instructions -->
            <div class="instructions-section">
                <div class="instruction-item">
                    <div class="instruction-number">1</div>
                    <div class="instruction-content">
                        <p class="mb-0">Connect the biometric device to internet using a cable or wifi to the same network as your computer.</p>
                    </div>
                </div>

                <div class="instruction-item">
                    <div class="instruction-number">2</div>
                    <div class="instruction-content">
                        <p class="mb-0">Ensure that the device is powered on and in the ready state.</p>
                    </div>
                </div>

                <div class="instruction-item">
                    <div class="instruction-number">3</div>
                    <div class="instruction-content">
                        <p class="mb-2">Download OurSchoolSoftware Biometric Application from below.</p>
                        <div class="download-buttons">
                            <a href="#" class="download-btn download-btn-32bit" onclick="alert('32bit Windows System download will start...'); return false;">
                                <span class="material-symbols-outlined">download</span>
                                <span>Download for 32bit Windows System</span>
                            </a>
                            <a href="#" class="download-btn download-btn-64bit" onclick="alert('64bit Windows System download will start...'); return false;">
                                <span class="material-symbols-outlined">download</span>
                                <span>Download for 64bit Windows System</span>
                            </a>
                        </div>
                    </div>
                </div>

                <div class="instruction-item">
                    <div class="instruction-number">4</div>
                    <div class="instruction-content">
                        <p class="mb-0">First install application by following the install guide inside downloaded zip file.</p>
                    </div>
                </div>

                <div class="instruction-item">
                    <div class="instruction-number">5</div>
                    <div class="instruction-content">
                        <p class="mb-0">Once all done, create BioAttendance software shortcut in desktop.</p>
                    </div>
                </div>

                <div class="instruction-item">
                    <div class="instruction-number">6</div>
                    <div class="instruction-content">
                        <p class="mb-0">Find BioAttendance application in desktop of your computer, open it.</p>
                    </div>
                </div>

                <div class="instruction-item">
                    <div class="instruction-number">7</div>
                    <div class="instruction-content">
                        <p class="mb-0">Add device ip address, device password if any, school software URL as Host & campus id into the fields.</p>
                    </div>
                </div>

                <div class="instruction-item">
                    <div class="instruction-number">8</div>
                    <div class="instruction-content">
                        <p class="mb-0">Now click on the "Connect" button and check if it connected successfully on the bottom of application.</p>
                    </div>
                </div>

                <div class="instruction-item">
                    <div class="instruction-number">9</div>
                    <div class="instruction-content">
                        <p class="mb-0">The application should now detect and establish a connection with your biometric device.</p>
                    </div>
                </div>

                <div class="instruction-item">
                    <div class="instruction-number">10</div>
                    <div class="instruction-content">
                        <p class="mb-0">You are now ready to use the device for biometric authentication or other related tasks.</p>
                    </div>
                </div>

                <div class="instruction-item">
                    <div class="instruction-number">11</div>
                    <div class="instruction-content">
                        <p class="mb-0">Click manage users tab and now click on Upload staff/student to machine button to sync all school software users into biometric device.</p>
                    </div>
                </div>

                <div class="instruction-item">
                    <div class="instruction-number">12</div>
                    <div class="instruction-content">
                        <p class="mb-0">Now register thumb or face into machine for each user.</p>
                    </div>
                </div>

                <div class="instruction-item">
                    <div class="instruction-number">13</div>
                    <div class="instruction-content">
                        <p class="mb-0">Now users can mark their attendance through biometric machine.</p>
                    </div>
                </div>

                <div class="instruction-item">
                    <div class="instruction-number">14</div>
                    <div class="instruction-content">
                        <p class="mb-0">Sync attendance data to school software by selecting date range and clicking on sync button in Attendance tab.</p>
                    </div>
                </div>

                <div class="instruction-item">
                    <div class="instruction-number">15</div>
                    <div class="instruction-content">
                        <p class="mb-0">While in open state, all realtime punch in and out will be automatically transferred to school software.</p>
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

    .instruction-item {
        display: flex;
        gap: 20px;
        margin-bottom: 24px;
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

    .instruction-content {
        flex: 1;
        padding-top: 2px;
    }

    .instruction-content p {
        color: #495057;
        font-size: 15px;
        line-height: 1.6;
        margin: 0;
    }

    .download-buttons {
        display: flex;
        gap: 15px;
        margin-top: 15px;
        flex-wrap: wrap;
    }

    .download-btn {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        padding: 12px 24px;
        border-radius: 8px;
        font-weight: 600;
        font-size: 14px;
        text-decoration: none;
        transition: all 0.3s ease;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        border: none;
    }

    .download-btn-32bit {
        background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        color: white;
    }

    .download-btn-32bit:hover {
        background: linear-gradient(135deg, #20c997 0%, #28a745 100%);
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
    }

    .download-btn-64bit {
        background: linear-gradient(135deg, #2196f3 0%, #0b7dda 100%);
        color: white;
    }

    .download-btn-64bit:hover {
        background: linear-gradient(135deg, #0b7dda 0%, #2196f3 100%);
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(33, 150, 243, 0.3);
    }

    .download-btn .material-symbols-outlined {
        font-size: 20px;
    }

    .download-btn:active {
        transform: translateY(0);
    }

    /* Responsive */
    @media (max-width: 768px) {
        .instruction-item {
            flex-direction: column;
            gap: 15px;
            padding: 15px;
        }

        .instruction-number {
            align-self: flex-start;
        }

        .download-buttons {
            flex-direction: column;
        }

        .download-btn {
            width: 100%;
            justify-content: center;
        }
    }
</style>
@endsection
