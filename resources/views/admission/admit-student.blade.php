@extends('layouts.app')

@section('title', 'Admit Student')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-4">
            <div class="d-flex align-items-center mb-4">
                <span class="material-symbols-outlined me-2" style="font-size: 28px; color: #003471;">person_add</span>
                <h3 class="mb-0 fw-bold" style="color: #003471;">{{ __('admission.admit_student') }}</h3>
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
            
            <form action="{{ route('admission.admit-student.store') }}" method="POST" enctype="multipart/form-data" id="admission-form">
                @csrf
                <input type="hidden" name="captured_photo" id="captured_photo_input">
                
                <!-- First Row: Student Information & Parent Information -->
                <div class="row mb-2">
                    <!-- Student Information -->
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm rounded-3 p-2 mb-2" style="background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);">
                            <div class="d-flex align-items-center mb-2" style="background-color: #003471; padding: 8px 12px; margin: -8px -8px 8px -8px; border-radius: 8px 8px 0 0;">
                                <span class="material-symbols-outlined me-2" style="font-size: 18px; color: #ffffff;">person</span>
                                <h5 class="mb-0 fw-semibold fs-15" style="color: #ffffff;">{{ __('admission.student_information') }}</h5>
                            </div>
                            
                            <div class="mb-2">
                                <label for="student_name" class="form-label mb-0 fs-13 fw-medium">
                                    Student Name <span class="text-danger">*</span>
                                </label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-light border-end-0 py-1" style="height: 32px;"><span class="material-symbols-outlined" style="font-size: 16px;">badge</span></span>
                                    <input type="text" class="form-control border-start-0 py-1" id="student_name" name="student_name" required placeholder="Student Name" style="height: 32px; font-size: 13px;">
                                </div>
                            </div>
                            
                            <div class="mb-2">
                                <label for="surname_caste" class="form-label mb-0 fs-13 fw-medium">Surname/Caste</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-light border-end-0 py-1" style="height: 32px;"><span class="material-symbols-outlined" style="font-size: 16px;">family_history</span></span>
                                    <input type="text" class="form-control border-start-0 py-1" id="surname_caste" name="surname_caste" placeholder="Surname/Caste" style="height: 32px; font-size: 13px;">
                                </div>
                            </div>
                            
                            <div class="mb-2">
                                <label for="gender" class="form-label mb-0 fs-13 fw-medium">
                                    Gender <span class="text-danger">*</span>
                                </label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-light border-end-0 py-1" style="height: 32px;"><span class="material-symbols-outlined" style="font-size: 16px;">wc</span></span>
                                    <select class="form-select border-start-0 py-1" id="gender" name="gender" required style="height: 32px; font-size: 13px;">
                                        <option value="">Select Gender</option>
                                        <option value="male">Male</option>
                                        <option value="female">Female</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="mb-2">
                                <label for="date_of_birth" class="form-label mb-0 fs-13 fw-medium">
                                    Date Of Birth <span class="text-danger">*</span>
                                </label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-light border-end-0 py-1" style="height: 32px;"><span class="material-symbols-outlined" style="font-size: 16px;">calendar_today</span></span>
                                    <input type="date" class="form-control border-start-0 py-1" id="date_of_birth" name="date_of_birth" required style="height: 32px; font-size: 13px;">
                                </div>
                            </div>
                            
                            <div class="mb-2">
                                <label for="place_of_birth" class="form-label mb-0 fs-13 fw-medium">Place Of Birth</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-light border-end-0 py-1" style="height: 32px;"><span class="material-symbols-outlined" style="font-size: 16px;">location_on</span></span>
                                    <input type="text" class="form-control border-start-0 py-1" id="place_of_birth" name="place_of_birth" placeholder="Place Of Birth" style="height: 32px; font-size: 13px;">
                                </div>
                            </div>
                            
                            <div class="mb-2">
                                <label for="photo" class="form-label mb-0 fs-13 fw-medium">Photo</label>
                                <div class="input-group input-group-sm mb-1">
                                    <span class="input-group-text bg-light border-end-0 py-1" style="height: 32px;"><span class="material-symbols-outlined" style="font-size: 16px;">image</span></span>
                                    <input type="file" class="form-control border-start-0 py-1" id="photo" name="photo" accept="image/*" capture="camera" style="height: 32px; font-size: 13px;">
                                </div>
                                <button type="button" class="btn btn-outline-primary btn-sm w-100 py-1" id="live-capture-btn" onclick="startLiveCapture()" style="font-size: 12px;">
                                    <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">camera</span>
                                    Live Capture
                                </button>
                                <video id="video" autoplay style="display: none; width: 100%; max-height: 150px; margin-top: 8px; border-radius: 8px;"></video>
                                <canvas id="canvas" style="display: none;"></canvas>
                                <div id="captured-image-container" style="display: none; margin-top: 8px; text-align: center;">
                                    <img id="captured-image" style="max-width: 100%; max-height: 150px; border: 2px solid #003471; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                                    <button type="button" class="btn btn-danger btn-sm mt-1 py-1" onclick="retakePhoto()" style="font-size: 12px;">
                                        <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">refresh</span>
                                        Retake
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Parent Information -->
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm rounded-3 p-2 mb-2" style="background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);">
                            <div class="d-flex align-items-center mb-2" style="background-color: #003471; padding: 8px 12px; margin: -8px -8px 8px -8px; border-radius: 8px 8px 0 0;">
                                <span class="material-symbols-outlined me-2" style="font-size: 18px; color: #ffffff;">family_restroom</span>
                                <h5 class="mb-0 fw-semibold fs-15" style="color: #ffffff;">{{ __('admission.parent_information') }}</h5>
                            </div>
                            
                            <div class="mb-2">
                                <label for="father_id_card" class="form-label mb-0 fs-13 fw-medium">Father ID Card</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-light border-end-0 py-1" style="height: 32px;"><span class="material-symbols-outlined" style="font-size: 16px;">credit_card</span></span>
                                    <input type="text" class="form-control border-start-0 py-1" id="father_id_card" name="father_id_card" placeholder="Father ID Card" style="height: 32px; font-size: 13px;">
                                </div>
                                <div id="parent-found-message" class="mt-1" style="display: none;"></div>
                            </div>
                            
                            <div class="mb-2">
                                <label for="father_name" class="form-label mb-0 fs-13 fw-medium">
                                    Father Name <span class="text-danger">*</span>
                                </label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-light border-end-0 py-1" style="height: 32px;"><span class="material-symbols-outlined" style="font-size: 16px;">person</span></span>
                                    <input type="text" class="form-control border-start-0 py-1" id="father_name" name="father_name" required placeholder="Father Name" style="height: 32px; font-size: 13px;">
                                </div>
                            </div>
                            
                            <div class="mb-2">
                                <label for="father_email" class="form-label mb-0 fs-13 fw-medium">Father Email</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-light border-end-0 py-1" style="height: 32px;"><span class="material-symbols-outlined" style="font-size: 16px;">email</span></span>
                                    <input type="email" class="form-control border-start-0 py-1" id="father_email" name="father_email" placeholder="Father Email" style="height: 32px; font-size: 13px;">
                                </div>
                            </div>
                            
                            <div class="mb-2">
                                <label for="create_parent_account" class="form-label mb-0 fs-13 fw-medium">Create Parent Account</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-light border-end-0 py-1" style="height: 32px;"><span class="material-symbols-outlined" style="font-size: 16px;">account_circle</span></span>
                                    <select class="form-select border-start-0 py-1" id="create_parent_account" name="create_parent_account" style="height: 32px; font-size: 13px;">
                                        <option value="">Select</option>
                                        <option value="1" {{ old('create_parent_account') == '1' ? 'selected' : '' }}>Yes</option>
                                        <option value="0" {{ old('create_parent_account') == '0' ? 'selected' : '' }}>No</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="mb-2" id="parent_password_field" style="display: none;">
                                <label for="parent_password" class="form-label mb-0 fs-13 fw-medium">
                                    Parent Password <span class="text-danger">*</span>
                                </label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-light border-end-0 py-1" style="height: 32px;"><span class="material-symbols-outlined" style="font-size: 16px;">lock</span></span>
                                    <input type="password" class="form-control border-start-0 py-1" id="parent_password" name="parent_password" placeholder="Parent Password" style="height: 32px; font-size: 13px;">
                                </div>
                                <small class="text-muted fs-11 mt-0 d-block">
                                    <span class="material-symbols-outlined" style="font-size: 12px; vertical-align: middle;">info</span>
                                    Password for parent account login
                                </small>
                            </div>
                            
                            <div class="mb-2">
                                <label for="father_phone" class="form-label mb-0 fs-13 fw-medium">Father Phone</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-light border-end-0 py-1" style="height: 32px;"><span class="material-symbols-outlined" style="font-size: 16px;">phone</span></span>
                                    <input type="tel" class="form-control border-start-0 py-1" id="father_phone" name="father_phone" placeholder="Father Phone" style="height: 32px; font-size: 13px;">
                                </div>
                            </div>
                            
                            <div class="mb-2">
                                <label for="mother_phone" class="form-label mb-0 fs-13 fw-medium">Mother Phone</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-light border-end-0 py-1" style="height: 32px;"><span class="material-symbols-outlined" style="font-size: 16px;">phone</span></span>
                                    <input type="tel" class="form-control border-start-0 py-1" id="mother_phone" name="mother_phone" placeholder="Mother Phone" style="height: 32px; font-size: 13px;">
                                </div>
                            </div>
                            
                            <div class="mb-2">
                                <label for="whatsapp_number" class="form-label mb-0 fs-13 fw-medium">WhatsApp Number</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-light border-end-0 py-1" style="height: 32px;"><span class="material-symbols-outlined" style="font-size: 16px;">chat</span></span>
                                    <input type="tel" class="form-control border-start-0 py-1" id="whatsapp_number" name="whatsapp_number" placeholder="WhatsApp Number" style="height: 32px; font-size: 13px;">
                                </div>
                            </div>
                            
                            <div class="mb-2">
                                <label for="religion" class="form-label mb-0 fs-13 fw-medium">Religion</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-light border-end-0 py-1" style="height: 32px;"><span class="material-symbols-outlined" style="font-size: 16px;">church</span></span>
                                    <input type="text" class="form-control border-start-0 py-1" id="religion" name="religion" placeholder="Religion" style="height: 32px; font-size: 13px;">
                                </div>
                            </div>
                            
                            <div class="mb-2">
                                <label for="home_address" class="form-label mb-0 fs-13 fw-medium">Home Address</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-light border-end-0 align-items-start py-1" style="height: auto;"><span class="material-symbols-outlined" style="font-size: 16px;">home</span></span>
                                    <textarea class="form-control border-start-0 py-1" id="home_address" name="home_address" rows="2" placeholder="Home Address" style="font-size: 13px; min-height: 50px;"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Second Row: Other Information & Academic Information -->
                <div class="row mb-2">
                    <!-- Other Information -->
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm rounded-3 p-2 mb-2" style="background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);">
                            <div class="d-flex align-items-center mb-2" style="background-color: #003471; padding: 8px 12px; margin: -8px -8px 8px -8px; border-radius: 8px 8px 0 0;">
                                <span class="material-symbols-outlined me-2" style="font-size: 18px; color: #ffffff;">info</span>
                                <h5 class="mb-0 fw-semibold fs-15" style="color: #ffffff;">{{ __('admission.other_information') }}</h5>
                            </div>
                            
                            <div class="mb-2">
                                <label for="b_form_number" class="form-label mb-0 fs-13 fw-medium">Student Password</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-light border-end-0 py-1" style="height: 32px;"><span class="material-symbols-outlined" style="font-size: 16px;">description</span></span>
                                    <input type="text" class="form-control border-start-0 py-1" id="b_form_number" name="b_form_number" value="{{ old('b_form_number', 'student') }}" placeholder="Student Password" style="height: 32px; font-size: 13px;">
                                </div>
                            </div>
                            
                            <div class="mb-2">
                                <label for="monthly_fee" class="form-label mb-0 fs-13 fw-medium">Monthly Fee</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-light border-end-0 py-1" style="height: 32px;"><span class="material-symbols-outlined" style="font-size: 16px;">payments</span></span>
                                    <input type="number" class="form-control border-start-0 py-1" id="monthly_fee" name="monthly_fee" step="0.01" placeholder="Monthly Fee" style="height: 32px; font-size: 13px;">
                                </div>
                            </div>
                            
                            <div class="mb-2">
                                <label for="discounted_student" class="form-label mb-0 fs-13 fw-medium">Discounted Student?</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-light border-end-0 py-1" style="height: 32px;"><span class="material-symbols-outlined" style="font-size: 16px;">percent</span></span>
                                    <select class="form-select border-start-0 py-1" id="discounted_student" name="discounted_student" style="height: 32px; font-size: 13px;" onchange="toggleDiscountField()">
                                        <option value="">Select</option>
                                        <option value="1" {{ old('discounted_student') == '1' ? 'selected' : '' }}>Yes</option>
                                        <option value="0" {{ old('discounted_student') == '0' ? 'selected' : '' }}>No</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Discount Amount Field (hidden by default) -->
                            <div class="mb-2" id="discount_amount_container" style="display: none;">
                                <label for="discount_amount" class="form-label mb-0 fs-13 fw-medium">Discount Amount</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-light border-end-0 py-1" style="height: 32px;"><span class="material-symbols-outlined" style="font-size: 16px;">payments</span></span>
                                    <input type="number" step="0.01" class="form-control border-start-0 py-1" id="discount_amount" name="discount_amount" placeholder="Discount amount" style="height: 32px; font-size: 13px;" value="{{ old('discount_amount') }}">
                                </div>
                            </div>

                            <!-- Discount Reason Field (hidden by default) -->
                            <div class="mb-2" id="discount_reason_container" style="display: none;">
                                <label for="discount_reason" class="form-label mb-0 fs-13 fw-medium">Discount Reason</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-light border-end-0 py-1" style="height: 32px;"><span class="material-symbols-outlined" style="font-size: 16px;">description</span></span>
                                    <input type="text" class="form-control border-start-0 py-1" id="discount_reason" name="discount_reason" placeholder="Reason for discount" style="height: 32px; font-size: 13px;" value="{{ old('discount_reason') }}">
                                </div>
                            </div>
                            
                            <div class="mb-2">
                                <label for="transport_route" class="form-label mb-0 fs-13 fw-medium">Transport Route</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-light border-end-0 py-1" style="height: 32px;"><span class="material-symbols-outlined" style="font-size: 16px;">directions_bus</span></span>
                                    <select class="form-select border-start-0 py-1" id="transport_route" name="transport_route" style="height: 32px; font-size: 13px;" onchange="loadTransportFare(this.value)">
                                        <option value="">Select Transport Route</option>
                                        @foreach($transportRoutes as $route)
                                            <option value="{{ $route }}" {{ old('transport_route') == $route ? 'selected' : '' }}>{{ $route }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            
                            <!-- Transport Route Fare Field (hidden by default) -->
                            <div class="mb-2" id="transport_fare_container" style="display: none;">
                                <label for="transport_fare" class="form-label mb-0 fs-13 fw-medium">Transport Fare</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-light border-end-0 py-1" style="height: 32px;"><span class="material-symbols-outlined" style="font-size: 16px;">payments</span></span>
                                    <input type="number" step="0.01" class="form-control border-start-0 py-1" id="transport_fare" name="transport_fare" placeholder="Transport fare amount" readonly style="height: 32px; font-size: 13px; background-color: #f8f9fa;" value="{{ old('transport_fare') }}">
                                </div>
                            </div>
                            
                            <div class="mb-2">
                                <label for="admission_notification" class="form-label mb-0 fs-13 fw-medium">Admission Notification</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-light border-end-0 py-1" style="height: 32px;"><span class="material-symbols-outlined" style="font-size: 16px;">notifications</span></span>
                                    <select class="form-select border-start-0 py-1" id="admission_notification" name="admission_notification" style="height: 32px; font-size: 13px;">
                                        <option value="sms_app" {{ old('admission_notification') == 'sms_app' ? 'selected' : '' }}>SMS App</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="mb-2">
                                <label for="generate_admission_fee" class="form-label mb-0 fs-13 fw-medium">Generate Admission Fee</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-light border-end-0 py-1" style="height: 32px;"><span class="material-symbols-outlined" style="font-size: 16px;">payments</span></span>
                                    <select class="form-select border-start-0 py-1" id="generate_admission_fee" name="generate_admission_fee" style="height: 32px; font-size: 13px;" onchange="toggleAdmissionFeeAmount()">
                                        <option value="">Select</option>
                                        <option value="1" {{ old('generate_admission_fee') == '1' ? 'selected' : '' }}>Yes</option>
                                        <option value="0" {{ old('generate_admission_fee') == '0' ? 'selected' : '' }}>No</option>
                                    </select>
                                </div>
                            </div>
                            
                            <!-- Admission Fee Amount Field (hidden by default) -->
                            <div class="mb-2" id="admission_fee_amount_container" style="display: none;">
                                <label for="admission_fee_amount" class="form-label mb-0 fs-13 fw-medium">Amount</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-light border-end-0 py-1" style="height: 32px;"><span class="material-symbols-outlined" style="font-size: 16px;">attach_money</span></span>
                                    <input type="number" step="0.01" class="form-control border-start-0 py-1" id="admission_fee_amount" name="admission_fee_amount" placeholder="admission fee amount" style="height: 32px; font-size: 13px;" value="{{ old('admission_fee_amount') }}">
                                </div>
                            </div>
                            
                            <div class="mb-2">
                                <label for="generate_other_fee" class="form-label mb-0 fs-13 fw-medium">Generate Other Fee</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-light border-end-0 py-1" style="height: 32px;"><span class="material-symbols-outlined" style="font-size: 16px;">receipt</span></span>
                                    <select class="form-select border-start-0 py-1" id="generate_other_fee" name="generate_other_fee" style="height: 32px; font-size: 13px;" onchange="toggleOtherFeeFields()">
                                        <option value="">Select</option>
                                        <option value="1" {{ old('generate_other_fee') == '1' ? 'selected' : '' }}>Yes</option>
                                        <option value="0" {{ old('generate_other_fee') == '0' ? 'selected' : '' }}>No</option>
                                    </select>
                                </div>
                            </div>
                            
                            <!-- Fee Type / Fee Head Dropdown (hidden by default) -->
                            <div class="mb-2" id="fee_type_container" style="display: none;">
                                <label for="fee_type" class="form-label mb-0 fs-13 fw-medium">Fee Type / Fee Head</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-light border-end-0 py-1" style="height: 32px;"><span class="material-symbols-outlined" style="font-size: 16px;">category</span></span>
                                    <select class="form-select border-start-0 py-1" id="fee_type" name="fee_type" style="height: 32px; font-size: 13px;" onchange="toggleOtherFeeAmount()">
                                        <option value="">Select Fee Type</option>
                                        @foreach($feeTypes as $feeType)
                                            <option value="{{ $feeType }}" {{ old('fee_type') == $feeType ? 'selected' : '' }}>{{ $feeType }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            
                            <!-- Other Fee Amount Field (hidden by default) -->
                            <div class="mb-2" id="other_fee_amount_container" style="display: none;">
                                <label for="other_fee_amount" class="form-label mb-0 fs-13 fw-medium">Amount</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-light border-end-0 py-1" style="height: 32px;"><span class="material-symbols-outlined" style="font-size: 16px;">attach_money</span></span>
                                    <input type="number" step="0.01" class="form-control border-start-0 py-1" id="other_fee_amount" name="other_fee_amount" placeholder="Enter amount" style="height: 32px; font-size: 13px;" value="{{ old('other_fee_amount') }}">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Academic Information -->
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm rounded-3 p-2 mb-2" style="background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);">
                            <div class="d-flex align-items-center mb-2" style="background-color: #003471; padding: 8px 12px; margin: -8px -8px 8px -8px; border-radius: 8px 8px 0 0;">
                                <span class="material-symbols-outlined me-2" style="font-size: 18px; color: #ffffff;">school</span>
                                <h5 class="mb-0 fw-semibold fs-15" style="color: #ffffff;">{{ __('admission.academic_information') }}</h5>
                            </div>
                            
                            <div class="mb-2">
                                <label for="student_code" class="form-label mb-0 fs-13 fw-medium">Student Code</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-light border-end-0 py-1" style="height: 32px;"><span class="material-symbols-outlined" style="font-size: 16px;">qr_code</span></span>
                                    <input type="text" class="form-control border-start-0 py-1" id="student_code" name="student_code" value="{{ $nextStudentCode ?? '' }}" readonly style="height: 32px; font-size: 13px; background-color: #e9ecef; font-weight: 600;">
                                </div>
                                <small class="text-muted fs-11 mt-0 d-block">
                                    <span class="material-symbols-outlined" style="font-size: 12px; vertical-align: middle;">auto_awesome</span>
                                    Auto-generated code
                                </small>
                            </div>
                            
                            <div class="mb-2">
                                <label for="gr_number" class="form-label mb-0 fs-13 fw-medium">G.R Number</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-light border-end-0 py-1" style="height: 32px;"><span class="material-symbols-outlined" style="font-size: 16px;">confirmation_number</span></span>
                                    <input type="text" class="form-control border-start-0 py-1" id="gr_number" name="gr_number" placeholder="G.R Number" style="height: 32px; font-size: 13px;">
                                </div>
                            </div>
                            
                            <div class="mb-2">
                                <label for="campus" class="form-label mb-0 fs-13 fw-medium">Campus</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-light border-end-0 py-1" style="height: 32px;"><span class="material-symbols-outlined" style="font-size: 16px;">business</span></span>
                                    <select class="form-select border-start-0 py-1" id="campus" name="campus" style="height: 32px; font-size: 13px;">
                                        <option value="">Select Campus</option>
                                        @foreach($campuses as $campus)
                                            <option value="{{ $campus }}" {{ old('campus') == $campus ? 'selected' : '' }}>{{ $campus }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            
                            <div class="mb-2">
                                <label for="class" class="form-label mb-0 fs-13 fw-medium">
                                    Class <span class="text-danger">*</span>
                                </label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-light border-end-0 py-1" style="height: 32px;"><span class="material-symbols-outlined" style="font-size: 16px;">class</span></span>
                                    <select class="form-select border-start-0 py-1" id="class" name="class" required style="height: 32px; font-size: 13px;">
                                        <option value="">Select Class</option>
                                        @foreach($classes as $class)
                                            <option value="{{ $class }}" {{ old('class') == $class ? 'selected' : '' }}>{{ $class }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            
                            <div class="mb-2">
                                <label for="section" class="form-label mb-0 fs-13 fw-medium">Section</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-light border-end-0 py-1" style="height: 32px;"><span class="material-symbols-outlined" style="font-size: 16px;">groups</span></span>
                                    <select class="form-select border-start-0 py-1" id="section" name="section" style="height: 32px; font-size: 13px;">
                                        <option value="">Select Section</option>
                                        @if(old('class'))
                                            @php
                                                $sectionsQuery = \App\Models\Section::where('class', old('class'))->whereNotNull('name');
                                                if (old('campus')) {
                                                    $sectionsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim(old('campus'))) ]);
                                                }
                                                $sectionsForClass = $sectionsQuery->distinct()->pluck('name')->sort();
                                            @endphp
                                            @foreach($sectionsForClass as $section)
                                                <option value="{{ $section }}" {{ old('section') == $section ? 'selected' : '' }}>{{ $section }}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>
                            </div>
                            
                            <div class="mb-2">
                                <label for="previous_school" class="form-label mb-0 fs-13 fw-medium">Previous School</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-light border-end-0 py-1" style="height: 32px;"><span class="material-symbols-outlined" style="font-size: 16px;">history_edu</span></span>
                                    <input type="text" class="form-control border-start-0 py-1" id="previous_school" name="previous_school" placeholder="Previous School" style="height: 32px; font-size: 13px;">
                                </div>
                            </div>
                            
                            <div class="mb-2">
                                <label for="admission_date" class="form-label mb-0 fs-13 fw-medium">
                                    Admission Date <span class="text-danger">*</span>
                                </label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-light border-end-0 py-1" style="height: 32px;"><span class="material-symbols-outlined" style="font-size: 16px;">event</span></span>
                                    <input type="date" class="form-control border-start-0 py-1" id="admission_date" name="admission_date" required style="height: 32px; font-size: 13px;">
                                </div>
                            </div>
                            
                            <div class="mb-2">
                                <label for="reference_remarks" class="form-label mb-0 fs-13 fw-medium">Reference/Remarks</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-light border-end-0 align-items-start py-1" style="height: auto;"><span class="material-symbols-outlined" style="font-size: 16px;">note</span></span>
                                    <textarea class="form-control border-start-0 py-1" id="reference_remarks" name="reference_remarks" rows="2" placeholder="Reference/Remarks" style="font-size: 13px; min-height: 50px;"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Submit Button -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn px-5 py-2 fw-semibold" style="background-color: #003471; color: #ffffff; border: 2px solid #003471; box-shadow: 0 4px 12px rgba(0, 52, 113, 0.2);">
                                <span class="material-symbols-outlined" style="font-size: 18px; vertical-align: middle; color: #ffffff;">check_circle</span>
                                Admit Student
                            </button>
                        </div>
                    </div>
                </div>

                <div class="row mt-2" id="printAdmitContainer" style="display: {{ (session('print_url') || session('fee_voucher_url')) ? 'block' : 'none' }};">
                    <div class="col-12">
                        <div class="d-flex justify-content-end gap-2 flex-wrap">
                            <button type="button" class="btn btn-outline-secondary" id="printAdmitBtn" data-print-url="{{ session('print_url') }}">
                                <span class="material-symbols-outlined" style="font-size: 18px; vertical-align: middle;">print</span>
                                Print Student Details
                            </button>
                            <button type="button" class="btn btn-outline-primary" id="printFeeVoucherBtn" data-fee-url="{{ session('fee_voucher_url') }}">
                                <span class="material-symbols-outlined" style="font-size: 18px; vertical-align: middle;">receipt</span>
                                Print Fee Voucher
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let stream = null;
let capturedPhotoBase64 = null;

function startLiveCapture() {
    const video = document.getElementById('video');
    const canvas = document.getElementById('canvas');
    const liveCaptureBtn = document.getElementById('live-capture-btn');
    const capturedImageContainer = document.getElementById('captured-image-container');
    
    // Hide captured image if visible
    capturedImageContainer.style.display = 'none';
    
    navigator.mediaDevices.getUserMedia({ video: true })
        .then(function(mediaStream) {
            stream = mediaStream;
            video.srcObject = stream;
            video.style.display = 'block';
            liveCaptureBtn.innerHTML = '<span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">camera_alt</span> Capture Photo';
            liveCaptureBtn.onclick = capturePhoto;
        })
        .catch(function(err) {
            alert('Error accessing camera: ' + err.message);
        });
}

function capturePhoto() {
    const video = document.getElementById('video');
    const canvas = document.getElementById('canvas');
    const capturedImage = document.getElementById('captured-image');
    const capturedImageContainer = document.getElementById('captured-image-container');
    const liveCaptureBtn = document.getElementById('live-capture-btn');
    
    // Set canvas dimensions
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    
    // Draw video frame to canvas
    const ctx = canvas.getContext('2d');
    ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
    
    // Convert canvas to base64
    capturedPhotoBase64 = canvas.toDataURL('image/jpeg', 0.8);
    
    // Show captured image
    capturedImage.src = capturedPhotoBase64;
    capturedImageContainer.style.display = 'block';
    
    // Stop video stream
    if (stream) {
        stream.getTracks().forEach(track => track.stop());
        stream = null;
    }
    
    // Hide video
    video.style.display = 'none';
    
    // Update button
    liveCaptureBtn.innerHTML = '<span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">camera</span> Live Capture';
    liveCaptureBtn.onclick = startLiveCapture;
    
    // Set hidden input value
    document.getElementById('captured_photo_input').value = capturedPhotoBase64;
}

function retakePhoto() {
    const capturedImageContainer = document.getElementById('captured-image-container');
    const capturedImage = document.getElementById('captured-image');
    capturedImageContainer.style.display = 'none';
    capturedPhotoBase64 = null;
    document.getElementById('captured_photo_input').value = '';
    startLiveCapture();
}

// Show/hide parent password field based on "Create Parent Account" selection
function toggleParentPasswordField() {
    const createParentAccount = document.getElementById('create_parent_account');
    const parentPasswordField = document.getElementById('parent_password_field');
    const parentPasswordInput = document.getElementById('parent_password');
    
    if (createParentAccount && parentPasswordField && parentPasswordInput) {
        if (createParentAccount.value === '1') {
            parentPasswordField.style.display = 'block';
            parentPasswordInput.required = true;
        } else {
            parentPasswordField.style.display = 'none';
            parentPasswordInput.required = false;
            parentPasswordInput.value = '';
        }
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    toggleParentPasswordField();
    
    // Add event listener for "Create Parent Account" dropdown
    const createParentAccount = document.getElementById('create_parent_account');
    if (createParentAccount) {
        createParentAccount.addEventListener('change', toggleParentPasswordField);
    }
    
    // Add event listener for Father ID Card field to auto-fill parent details
    const fatherIdCardInput = document.getElementById('father_id_card');
    if (fatherIdCardInput) {
        let searchTimeout;
        
        fatherIdCardInput.addEventListener('input', function() {
            const fatherIdCard = this.value.trim();
            const parentFoundMessage = document.getElementById('parent-found-message');
            
            // Clear previous timeout
            clearTimeout(searchTimeout);
            
            // Hide message if input is empty
            if (!fatherIdCard) {
                parentFoundMessage.style.display = 'none';
                parentFoundMessage.innerHTML = '';
                return;
            }
            
            // Show loading state
            parentFoundMessage.style.display = 'block';
            parentFoundMessage.innerHTML = '<small class="text-info"><span class="material-symbols-outlined" style="font-size: 12px; vertical-align: middle;">search</span> Searching for parent account...</small>';
            
            // Debounce: Wait 800ms after user stops typing
            searchTimeout = setTimeout(function() {
                // Fetch parent details by ID Card
                fetch(`{{ route('admission.get-parent-by-id-card') }}?father_id_card=${encodeURIComponent(fatherIdCard)}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && data.found) {
                            // Parent found - auto-fill form fields
                            const parent = data.parent;
                            
                            // Fill parent details
                            const fatherNameInput = document.getElementById('father_name');
                            const fatherEmailInput = document.getElementById('father_email');
                            const fatherPhoneInput = document.getElementById('father_phone');
                            const whatsappInput = document.getElementById('whatsapp_number');
                            const addressInput = document.getElementById('home_address');
                            
                            if (fatherNameInput && parent.name) {
                                fatherNameInput.value = parent.name;
                            }
                            if (fatherEmailInput && parent.email) {
                                fatherEmailInput.value = parent.email;
                            }
                            if (fatherPhoneInput && parent.phone) {
                                fatherPhoneInput.value = parent.phone;
                            }
                            if (whatsappInput && parent.whatsapp) {
                                whatsappInput.value = parent.whatsapp;
                            }
                            if (addressInput && parent.address) {
                                addressInput.value = parent.address;
                            }
                            
                            // Disable "Create Parent Account" dropdown and set to "No"
                            const createParentAccountSelect = document.getElementById('create_parent_account');
                            if (createParentAccountSelect) {
                                createParentAccountSelect.value = '0';
                                createParentAccountSelect.disabled = true;
                                toggleParentPasswordField();
                            }
                            
                            // Show success message
                            parentFoundMessage.innerHTML = `<small class="text-success"><span class="material-symbols-outlined" style="font-size: 12px; vertical-align: middle;">check_circle</span> ${data.message}</small>`;
                            parentFoundMessage.className = 'mt-1';
                            
                            // Highlight filled fields
                            [fatherNameInput, fatherEmailInput, fatherPhoneInput, whatsappInput, addressInput].forEach(input => {
                                if (input && input.value) {
                                    input.style.backgroundColor = '#e8f5e9';
                                    setTimeout(() => {
                                        input.style.backgroundColor = '';
                                    }, 2000);
                                }
                            });
                        } else {
                            // Parent not found
                            parentFoundMessage.innerHTML = `<small class="text-muted"><span class="material-symbols-outlined" style="font-size: 12px; vertical-align: middle;">info</span> ${data.message}</small>`;
                            parentFoundMessage.className = 'mt-1';
                            
                            // Enable "Create Parent Account" dropdown
                            const createParentAccountSelect = document.getElementById('create_parent_account');
                            if (createParentAccountSelect) {
                                createParentAccountSelect.disabled = false;
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching parent details:', error);
                        parentFoundMessage.innerHTML = '<small class="text-danger"><span class="material-symbols-outlined" style="font-size: 12px; vertical-align: middle;">error</span> Error searching for parent account. Please try again.</small>';
                        parentFoundMessage.className = 'mt-1';
                    });
            }, 800);
        });
    }
});

function loadClassesForCampus(campusValue, selectedClass = '') {
    const classSelect = document.getElementById('class');
    const sectionSelect = document.getElementById('section');
    const studentCodeInput = document.getElementById('student_code');

    classSelect.innerHTML = '<option value="">Select Class</option>';
    sectionSelect.innerHTML = '<option value="">Select Section</option>';

    if (!campusValue) {
        return;
    }

    fetch(`{{ route('admission.get-classes') }}?campus=${encodeURIComponent(campusValue)}`)
        .then(response => response.json())
        .then(data => {
            if (data.classes && data.classes.length > 0) {
                data.classes.forEach(className => {
                    const option = document.createElement('option');
                    option.value = className;
                    option.textContent = className;
                    if (selectedClass && selectedClass === className) {
                        option.selected = true;
                    }
                    classSelect.appendChild(option);
                });
            }
        })
        .catch(error => {
            console.error('Error loading classes:', error);
        });

    if (studentCodeInput) {
        if (!campusValue) {
            studentCodeInput.value = '';
            return;
        }

        fetch(`{{ route('admission.get-next-student-code') }}?campus=${encodeURIComponent(campusValue)}`)
            .then(response => response.json())
            .then(data => {
                studentCodeInput.value = data.code || '';
            })
            .catch(error => {
                console.error('Error loading student code:', error);
            });
    }
}

function loadSectionsForClass(classValue, campusValue, selectedSection = '') {
    const sectionSelect = document.getElementById('section');
    sectionSelect.innerHTML = '<option value="">Select Section</option>';

    if (!classValue) {
        return;
    }

    const query = `class=${encodeURIComponent(classValue)}&campus=${encodeURIComponent(campusValue || '')}`;
    fetch(`{{ route('admission.get-sections') }}?${query}`)
        .then(response => response.json())
        .then(data => {
            if (data.sections && data.sections.length > 0) {
                data.sections.forEach(section => {
                    const option = document.createElement('option');
                    option.value = section;
                    option.textContent = section;
                    if (selectedSection && selectedSection === section) {
                        option.selected = true;
                    }
                    sectionSelect.appendChild(option);
                });
            }
        })
        .catch(error => {
            console.error('Error loading sections:', error);
        });
}

document.addEventListener('DOMContentLoaded', function() {
    const campusSelect = document.getElementById('campus');
    const classSelect = document.getElementById('class');

    if (campusSelect) {
        campusSelect.addEventListener('change', function() {
            loadClassesForCampus(this.value);
            loadTransportRoutesByCampus(this.value);
            loadTransportFare('');
        });
    }

    if (classSelect) {
        classSelect.addEventListener('change', function() {
            const campusValue = campusSelect ? campusSelect.value : '';
            loadSectionsForClass(this.value, campusValue);
        });
    }

    const initialCampus = campusSelect ? campusSelect.value : '';
    const initialClass = `{{ old('class') ?? '' }}`;
    const initialSection = `{{ old('section') ?? '' }}`;

    if (initialCampus) {
        loadClassesForCampus(initialCampus, initialClass);
        if (initialClass) {
            loadSectionsForClass(initialClass, initialCampus, initialSection);
        }
    }
});

// Toast notification function
function showToast(message, type = 'success') {
    // Create toast container if it doesn't exist
    let toastContainer = document.getElementById('toastContainer');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.id = 'toastContainer';
        toastContainer.className = 'toast-container position-fixed top-0 end-0 p-3';
        toastContainer.style.zIndex = '9999';
        document.body.appendChild(toastContainer);
    }
    
    const toastId = 'toast-' + Date.now();
    const icon = type === 'success' ? 'check_circle' : 'error';
    const headerClass = type === 'success' ? 'success-toast-header' : 'error-toast-header';
    const toastClass = type === 'success' ? 'success-toast' : 'error-toast';
    const title = type === 'success' ? 'Success' : 'Error';
    
    const toastHTML = `
        <div id="${toastId}" class="toast show ${toastClass}" role="alert" aria-live="assertive" aria-atomic="true" data-bs-autohide="true" data-bs-delay="5000">
            <div class="toast-header ${headerClass}">
                <span class="material-symbols-outlined me-2" style="font-size: 20px; color: white;">${icon}</span>
                <strong class="me-auto text-white">${title}</strong>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body">
                ${message}
            </div>
        </div>
    `;
    
    toastContainer.insertAdjacentHTML('beforeend', toastHTML);
    
    const toastElement = document.getElementById(toastId);
    if (typeof bootstrap !== 'undefined' && bootstrap.Toast) {
        const toast = new bootstrap.Toast(toastElement);
        toast.show();
        
        // Remove toast element after it's hidden
        toastElement.addEventListener('hidden.bs.toast', function() {
            toastElement.remove();
        });
    } else {
        // Fallback if Bootstrap is not available
        setTimeout(() => {
            toastElement.remove();
        }, 5000);
    }
}

function setPrintButtons(printUrl, feeVoucherUrl) {
    const container = document.getElementById('printAdmitContainer');
    const admitButton = document.getElementById('printAdmitBtn');
    const feeButton = document.getElementById('printFeeVoucherBtn');
    if (!container) {
        return;
    }

    if (admitButton) {
        if (printUrl) {
            admitButton.dataset.printUrl = printUrl;
            admitButton.onclick = function() {
                window.open(printUrl, '_blank');
            };
            admitButton.style.display = 'inline-flex';
        } else {
            admitButton.style.display = 'none';
        }
    }

    if (feeButton) {
        if (feeVoucherUrl) {
            feeButton.dataset.feeUrl = feeVoucherUrl;
            feeButton.onclick = function() {
                window.open(feeVoucherUrl, '_blank');
            };
            feeButton.style.display = 'inline-flex';
        } else {
            feeButton.style.display = 'none';
        }
    }

    container.style.display = (printUrl || feeVoucherUrl) ? 'block' : 'none';
}

// Handle form submission via AJAX
document.getElementById('admission-form').addEventListener('submit', function(e) {
    e.preventDefault(); // Prevent default form submission
    
    // Ensure captured photo is included if available
    if (capturedPhotoBase64 && !document.getElementById('photo').files.length) {
        document.getElementById('captured_photo_input').value = capturedPhotoBase64;
    }
    
    // Get form data
    const formData = new FormData(this);
    const submitButton = this.querySelector('button[type="submit"]');
    const originalButtonText = submitButton.innerHTML;
    
    // Disable submit button and show loading state
    submitButton.disabled = true;
    submitButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Processing...';
    
    // Hide previous error messages
    const errorAlert = document.querySelector('.alert-danger');
    if (errorAlert) {
        errorAlert.remove();
    }
    
    // Submit form via AJAX
    fetch(this.action, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || document.querySelector('input[name="_token"]')?.value
        }
    })
    .then(response => response.json())
    .then(data => {
        // Re-enable submit button
        submitButton.disabled = false;
        submitButton.innerHTML = originalButtonText;
        
        if (data.success) {
            // Show success toast
            showToast(data.message, 'success');
            setPrintButtons(data.print_url, data.fee_voucher_url);
            
            // Reset form after 1 second
            setTimeout(() => {
                document.getElementById('admission-form').reset();
                // Clear captured photo
                capturedPhotoBase64 = null;
                document.getElementById('captured_photo_input').value = '';
                // Reset photo preview if exists
                const photoPreview = document.getElementById('photo-preview');
                if (photoPreview) {
                    photoPreview.src = '';
                    photoPreview.style.display = 'none';
                }
                // Reset video capture if exists
                const video = document.getElementById('video');
                if (video && stream) {
                    stream.getTracks().forEach(track => track.stop());
                    video.srcObject = null;
                    stream = null;
                }
            }, 1000);
        } else {
            // Show error toast
            showToast(data.message || 'An error occurred. Please try again.', 'error');
        }
    })
    .catch(error => {
        // Re-enable submit button
        submitButton.disabled = false;
        submitButton.innerHTML = originalButtonText;
        
        // Handle validation errors
        if (error.response) {
            error.response.json().then(data => {
                if (data.errors) {
                    // Display validation errors
                    let errorMessages = '<ul class="mb-0">';
                    Object.keys(data.errors).forEach(key => {
                        data.errors[key].forEach(msg => {
                            errorMessages += `<li>${msg}</li>`;
                        });
                    });
                    errorMessages += '</ul>';
                    
                    // Show error alert
                    const errorAlert = document.createElement('div');
                    errorAlert.className = 'alert alert-danger alert-dismissible fade show';
                    errorAlert.setAttribute('role', 'alert');
                    errorAlert.innerHTML = errorMessages + '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
                    
                    const form = document.getElementById('admission-form');
                    form.insertBefore(errorAlert, form.firstChild);
                } else {
                    showToast(data.message || 'An error occurred. Please try again.', 'error');
                }
            }).catch(() => {
                showToast('An error occurred. Please try again.', 'error');
            });
        } else {
            showToast('An error occurred. Please try again.', 'error');
        }
    });
});

// Load transport route fare when route is selected
function loadTransportFare(routeName) {
    const transportFareContainer = document.getElementById('transport_fare_container');
    const transportFareInput = document.getElementById('transport_fare');
    const campusSelect = document.getElementById('campus');
    const campusValue = campusSelect ? campusSelect.value : '';
    
    if (!routeName) {
        // Hide transport fare field if no route selected
        if (transportFareContainer) {
            transportFareContainer.style.display = 'none';
        }
        if (transportFareInput) {
            transportFareInput.value = '';
        }
        return;
    }
    
    // Show transport fare field
    if (transportFareContainer) {
        transportFareContainer.style.display = 'block';
    }
    
    // Fetch route fare via AJAX
    fetch(`{{ route('admission.get-route-fare') }}?route=${encodeURIComponent(routeName)}&campus=${encodeURIComponent(campusValue || '')}`)
        .then(response => response.json())
        .then(data => {
            if (transportFareInput) {
                if (data.fare && data.fare > 0) {
                    // Set fare amount in transport fare field (don't add to monthly fee)
                    transportFareInput.value = parseFloat(data.fare).toFixed(2);
                } else {
                    transportFareInput.value = '';
                }
            }
        })
        .catch(error => {
            console.error('Error loading transport fare:', error);
            if (transportFareInput) {
                transportFareInput.value = '';
            }
        });
}

function loadTransportRoutesByCampus(campusValue, selectedRoute = '') {
    const transportSelect = document.getElementById('transport_route');
    if (!transportSelect) return;

    transportSelect.innerHTML = '<option value="">Loading...</option>';

    fetch(`{{ route('admission.get-transport-routes') }}?campus=${encodeURIComponent(campusValue || '')}`)
        .then(response => response.json())
        .then(data => {
            transportSelect.innerHTML = '<option value="">Select Transport Route</option>';
            if (data.routes && data.routes.length > 0) {
                data.routes.forEach(route => {
                    const option = document.createElement('option');
                    option.value = route;
                    option.textContent = route;
                    if (selectedRoute && selectedRoute === route) {
                        option.selected = true;
                    }
                    transportSelect.appendChild(option);
                });
            }
        })
        .catch(error => {
            console.error('Error loading transport routes:', error);
            transportSelect.innerHTML = '<option value="">Select Transport Route</option>';
        });
}

// Toggle discount amount field based on "Discounted Student" selection
function toggleDiscountField() {
    const discountedStudent = document.getElementById('discounted_student');
    const discountAmountContainer = document.getElementById('discount_amount_container');
    const discountAmountInput = document.getElementById('discount_amount');
    const discountReasonContainer = document.getElementById('discount_reason_container');
    const discountReasonInput = document.getElementById('discount_reason');

    if (!discountedStudent || !discountAmountContainer) {
        return;
    }

    if (discountedStudent.value === '1') {
        discountAmountContainer.style.display = 'block';
        if (discountReasonContainer) {
            discountReasonContainer.style.display = 'block';
        }
        if (discountReasonInput) {
            discountReasonInput.required = true;
        }
    } else {
        discountAmountContainer.style.display = 'none';
        if (discountAmountInput) {
            discountAmountInput.value = '';
        }
        if (discountReasonContainer) {
            discountReasonContainer.style.display = 'none';
        }
        if (discountReasonInput) {
            discountReasonInput.required = false;
            discountReasonInput.value = '';
        }
    }
}

// Toggle admission fee amount field based on "Generate Admission Fee" selection
function toggleAdmissionFeeAmount() {
    const generateAdmissionFee = document.getElementById('generate_admission_fee');
    const admissionFeeAmountContainer = document.getElementById('admission_fee_amount_container');
    const admissionFeeAmountInput = document.getElementById('admission_fee_amount');
    
    if (generateAdmissionFee && admissionFeeAmountContainer) {
        if (generateAdmissionFee.value === '1') {
            admissionFeeAmountContainer.style.display = 'block';
            if (admissionFeeAmountInput) {
                admissionFeeAmountInput.required = true;
            }
        } else {
            admissionFeeAmountContainer.style.display = 'none';
            if (admissionFeeAmountInput) {
                admissionFeeAmountInput.required = false;
                admissionFeeAmountInput.value = '';
            }
        }
    }
}

// Toggle other fee fields (Fee Type and Amount) based on "Generate Other Fee" selection
function toggleOtherFeeFields() {
    const generateOtherFee = document.getElementById('generate_other_fee');
    const feeTypeContainer = document.getElementById('fee_type_container');
    const feeTypeInput = document.getElementById('fee_type');
    const otherFeeAmountContainer = document.getElementById('other_fee_amount_container');
    const otherFeeAmountInput = document.getElementById('other_fee_amount');
    
    if (generateOtherFee && feeTypeContainer) {
        if (generateOtherFee.value === '1') {
            feeTypeContainer.style.display = 'block';
            if (feeTypeInput) {
                feeTypeInput.required = true;
            }
            // Check if fee type is already selected, then show amount field
            if (feeTypeInput && feeTypeInput.value) {
                toggleOtherFeeAmount();
            }
        } else {
            feeTypeContainer.style.display = 'none';
            otherFeeAmountContainer.style.display = 'none';
            if (feeTypeInput) {
                feeTypeInput.required = false;
                feeTypeInput.value = '';
            }
            if (otherFeeAmountInput) {
                otherFeeAmountInput.required = false;
                otherFeeAmountInput.value = '';
            }
        }
    }
}

// Toggle other fee amount field based on "Fee Type" selection
function toggleOtherFeeAmount() {
    const feeTypeInput = document.getElementById('fee_type');
    const otherFeeAmountContainer = document.getElementById('other_fee_amount_container');
    const otherFeeAmountInput = document.getElementById('other_fee_amount');
    
    if (feeTypeInput && otherFeeAmountContainer) {
        if (feeTypeInput.value) {
            otherFeeAmountContainer.style.display = 'block';
            if (otherFeeAmountInput) {
                otherFeeAmountInput.required = true;
            }
        } else {
            otherFeeAmountContainer.style.display = 'none';
            if (otherFeeAmountInput) {
                otherFeeAmountInput.required = false;
                otherFeeAmountInput.value = '';
            }
        }
    }
}

// Initialize admission fee amount field visibility on page load
document.addEventListener('DOMContentLoaded', function() {
    toggleAdmissionFeeAmount();
    toggleOtherFeeFields();
    toggleDiscountField();
    
    // Load transport fare if route is already selected (for form validation errors)
    const transportRoute = document.getElementById('transport_route');
    const campusSelect = document.getElementById('campus');
    const selectedCampus = campusSelect ? campusSelect.value : '';
    const selectedRoute = transportRoute ? transportRoute.value : '';

    loadTransportRoutesByCampus(selectedCampus, selectedRoute);
    if (selectedRoute) {
        loadTransportFare(selectedRoute);
    } else {
        // Hide transport fare field if no route selected
        const transportFareContainer = document.getElementById('transport_fare_container');
        if (transportFareContainer) {
            transportFareContainer.style.display = 'none';
        }
    }

    const printButton = document.getElementById('printAdmitBtn');
    const feeButton = document.getElementById('printFeeVoucherBtn');
    const printUrl = printButton?.dataset.printUrl || '';
    const feeUrl = feeButton?.dataset.feeUrl || '';
    if (printUrl || feeUrl) {
        setPrintButtons(printUrl, feeUrl);
    }
});
</script>

<style>
/* Compact Form Improvements */
.card {
    transition: all 0.3s ease;
}

.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1) !important;
}

.form-control:focus,
.form-select:focus {
    border-color: #003471;
    box-shadow: 0 0 0 0.2rem rgba(0, 52, 113, 0.15);
}

.input-group-text {
    transition: all 0.3s ease;
    padding: 0.25rem 0.5rem;
}

.form-control:hover,
.form-select:hover {
    border-color: #003471;
}

.btn-primary:hover,
button[type="submit"]:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(0, 52, 113, 0.4) !important;
    background-color: #0056b3 !important;
    border-color: #0056b3 !important;
    color: #ffffff !important;
}

button[type="submit"]:hover .material-symbols-outlined {
    color: #ffffff !important;
}

.btn-outline-primary:hover {
    background-color: #003471;
    border-color: #003471;
    color: white;
}

.form-check-input:checked {
    background-color: #003471;
    border-color: #003471;
}

.form-check-input:focus {
    border-color: #003471;
    box-shadow: 0 0 0 0.2rem rgba(0, 52, 113, 0.15);
}

/* Compact spacing */
.form-label {
    margin-bottom: 0.25rem;
}

.input-group-sm .form-control,
.input-group-sm .form-select {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
}

.input-group-sm .input-group-text {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
}

/* Placeholder styling - matching labels */
.form-control::placeholder,
.form-select option:first-child {
    color: #6c757d;
    opacity: 0.7;
    font-weight: 400;
}

.form-control:focus::placeholder {
    opacity: 0.5;
}

/* Responsive improvements */
@media (max-width: 768px) {
    .card {
        margin-bottom: 0.75rem;
    }
    
    .d-flex.justify-content-end {
        flex-direction: column;
    }
    
    .d-flex.justify-content-end .btn {
        width: 100%;
        margin-top: 0.5rem;
    }
}

/* Animation for required fields */
.form-label .text-danger {
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% {
        opacity: 1;
    }
    50% {
        opacity: 0.7;
    }
}

/* Toast Notification Styling */
.success-toast,
.error-toast {
    min-width: 300px;
    max-width: 400px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    animation: slideInDown 0.3s ease-out;
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

.success-toast-header {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    color: white;
    border-bottom: 1px solid rgba(255, 255, 255, 0.2);
}

.error-toast-header {
    background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
    color: white;
    border-bottom: 1px solid rgba(255, 255, 255, 0.2);
}

.success-toast .btn-close,
.error-toast .btn-close {
    filter: brightness(0) invert(1);
    opacity: 0.9;
}

.success-toast .btn-close:hover,
.error-toast .btn-close:hover {
    opacity: 1;
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
@endsection
