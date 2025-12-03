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
                                <label for="b_form_number" class="form-label mb-0 fs-13 fw-medium">B-Form Number</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-light border-end-0 py-1" style="height: 32px;"><span class="material-symbols-outlined" style="font-size: 16px;">description</span></span>
                                    <input type="text" class="form-control border-start-0 py-1" id="b_form_number" name="b_form_number" placeholder="B-Form Number" style="height: 32px; font-size: 13px;">
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
                                    <select class="form-select border-start-0 py-1" id="discounted_student" name="discounted_student" style="height: 32px; font-size: 13px;">
                                        <option value="">Select</option>
                                        <option value="1" {{ old('discounted_student') == '1' ? 'selected' : '' }}>Yes</option>
                                        <option value="0" {{ old('discounted_student') == '0' ? 'selected' : '' }}>No</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="mb-2">
                                <label for="transport_route" class="form-label mb-0 fs-13 fw-medium">Transport Route</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-light border-end-0 py-1" style="height: 32px;"><span class="material-symbols-outlined" style="font-size: 16px;">directions_bus</span></span>
                                    <select class="form-select border-start-0 py-1" id="transport_route" name="transport_route" style="height: 32px; font-size: 13px;">
                                        <option value="">Select Transport Route</option>
                                        @foreach($transportRoutes as $route)
                                            <option value="{{ $route }}" {{ old('transport_route') == $route ? 'selected' : '' }}>{{ $route }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            
                            <div class="mb-2">
                                <label for="admission_notification" class="form-label mb-0 fs-13 fw-medium">Admission Notification</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-light border-end-0 py-1" style="height: 32px;"><span class="material-symbols-outlined" style="font-size: 16px;">notifications</span></span>
                                    <select class="form-select border-start-0 py-1" id="admission_notification" name="admission_notification" style="height: 32px; font-size: 13px;">
                                        <option value="">Select</option>
                                        <option value="whatsapp_only" {{ old('admission_notification') == 'whatsapp_only' ? 'selected' : '' }}>WhatsApp Only</option>
                                        <option value="sms_only" {{ old('admission_notification') == 'sms_only' ? 'selected' : '' }}>SMS Only</option>
                                        <option value="whatsapp_app" {{ old('admission_notification') == 'whatsapp_app' ? 'selected' : '' }}>WhatsApp & App</option>
                                        <option value="dont_notify" {{ old('admission_notification') == 'dont_notify' ? 'selected' : '' }}>Don't Notify</option>
                                    </select>
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
                            
                            <div class="mb-2">
                                <label for="generate_admission_fee" class="form-label mb-0 fs-13 fw-medium">Generate Admission Fee</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-light border-end-0 py-1" style="height: 32px;"><span class="material-symbols-outlined" style="font-size: 16px;">payments</span></span>
                                    <select class="form-select border-start-0 py-1" id="generate_admission_fee" name="generate_admission_fee" style="height: 32px; font-size: 13px;">
                                        <option value="">Select</option>
                                        <option value="1" {{ old('generate_admission_fee') == '1' ? 'selected' : '' }}>Yes</option>
                                        <option value="0" {{ old('generate_admission_fee') == '0' ? 'selected' : '' }}>No</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="mb-2">
                                <label for="generate_other_fee" class="form-label mb-0 fs-13 fw-medium">Generate Other Fee</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-light border-end-0 py-1" style="height: 32px;"><span class="material-symbols-outlined" style="font-size: 16px;">receipt</span></span>
                                    <select class="form-select border-start-0 py-1" id="generate_other_fee" name="generate_other_fee" style="height: 32px; font-size: 13px;">
                                        <option value="">Select</option>
                                        <option value="1" {{ old('generate_other_fee') == '1' ? 'selected' : '' }}>Yes</option>
                                        <option value="0" {{ old('generate_other_fee') == '0' ? 'selected' : '' }}>No</option>
                                    </select>
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
                                                $sectionsForClass = \App\Models\Section::where('class', old('class'))->whereNotNull('name')->distinct()->pluck('name')->sort();
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

// Load sections based on class selection
document.getElementById('class').addEventListener('change', function() {
    const classValue = this.value;
    const sectionSelect = document.getElementById('section');
    
    // Clear existing options except the first one
    sectionSelect.innerHTML = '<option value="">Select Section</option>';
    
    if (classValue) {
        // Fetch sections via AJAX
        fetch(`{{ route('admission.get-sections') }}?class=${encodeURIComponent(classValue)}`)
            .then(response => response.json())
            .then(data => {
                if (data.sections && data.sections.length > 0) {
                    data.sections.forEach(section => {
                        const option = document.createElement('option');
                        option.value = section;
                        option.textContent = section;
                        sectionSelect.appendChild(option);
                    });
                }
            })
            .catch(error => {
                console.error('Error loading sections:', error);
            });
    }
});

// Handle form submission
document.getElementById('admission-form').addEventListener('submit', function(e) {
    // Ensure captured photo is included if available
    if (capturedPhotoBase64 && !document.getElementById('photo').files.length) {
        document.getElementById('captured_photo_input').value = capturedPhotoBase64;
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
</style>
@endsection
