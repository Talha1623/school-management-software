@extends('layouts.app')

@section('title', 'Admit Student')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-20">
            <h3 class="mb-20">Admit Student</h3>
            
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
                        <div class="card bg-light border-0 rounded-10 p-2 mb-2">
                            <h5 class="mb-1 py-2 px-3 text-white rounded-3 fw-semibold fs-15" style="margin: -8px -8px 8px -8px; background-color: #003471;">Student Information</h5>
                            
                            <div class="mb-1">
                                <label for="student_name" class="form-label mb-0 fs-13 fw-medium">Student Name</label>
                                <input type="text" class="form-control form-control-sm py-1" id="student_name" name="student_name" required style="height: 32px;">
                            </div>
                            
                            <div class="mb-1">
                                <label for="surname_caste" class="form-label mb-0 fs-13 fw-medium">Surname/Caste</label>
                                <input type="text" class="form-control form-control-sm py-1" id="surname_caste" name="surname_caste" style="height: 32px;">
                            </div>
                            
                            <div class="mb-1">
                                <label for="gender" class="form-label mb-0 fs-13 fw-medium">Gender</label>
                                <select class="form-select form-select-sm py-1" id="gender" name="gender" required style="height: 32px;">
                                    <option value="">Select Gender</option>
                                    <option value="male">Male</option>
                                    <option value="female">Female</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            
                            <div class="mb-1">
                                <label for="date_of_birth" class="form-label mb-0 fs-13 fw-medium">Date Of Birth</label>
                                <input type="date" class="form-control form-control-sm py-1" id="date_of_birth" name="date_of_birth" required style="height: 32px;">
                            </div>
                            
                            <div class="mb-1">
                                <label for="place_of_birth" class="form-label mb-0 fs-13 fw-medium">Place Of Birth</label>
                                <input type="text" class="form-control form-control-sm py-1" id="place_of_birth" name="place_of_birth" style="height: 32px;">
                            </div>
                            
                            <div class="mb-1">
                                <label for="photo" class="form-label mb-0 fs-13 fw-medium">Photo</label>
                                <input type="file" class="form-control form-control-sm py-1" id="photo" name="photo" accept="image/*" capture="camera" style="height: 32px;">
                                <small class="text-muted fs-11">Select Camera</small>
                            </div>
                            <div class="mb-1">
                                <button type="button" class="btn btn-sm btn-outline-primary w-100" id="live-capture-btn" onclick="startLiveCapture()">
                                    <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">camera</span>
                                    Live Capture
                                </button>
                                <video id="video" autoplay style="display: none; width: 100%; max-height: 200px; margin-top: 8px;"></video>
                                <canvas id="canvas" style="display: none;"></canvas>
                                <div id="captured-image-container" style="display: none; margin-top: 8px;">
                                    <img id="captured-image" style="max-width: 100%; max-height: 200px; border: 1px solid #ddd; border-radius: 5px;">
                                    <button type="button" class="btn btn-sm btn-danger mt-1" onclick="retakePhoto()">Retake</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Parent Information -->
                    <div class="col-md-6">
                        <div class="card bg-light border-0 rounded-10 p-2 mb-2">
                            <h5 class="mb-1 py-2 px-3 text-white rounded-3 fw-semibold fs-15" style="margin: -8px -8px 8px -8px; background-color: #003471;">Parent Information</h5>
                            
                            <div class="mb-1">
                                <label for="father_id_card" class="form-label mb-0 fs-13 fw-medium">Father ID Card</label>
                                <input type="text" class="form-control form-control-sm py-1" id="father_id_card" name="father_id_card" style="height: 32px;">
                            </div>
                            
                            <div class="mb-1">
                                <label for="father_name" class="form-label mb-0 fs-13 fw-medium">Father Name</label>
                                <input type="text" class="form-control form-control-sm py-1" id="father_name" name="father_name" required style="height: 32px;">
                            </div>
                            
                            <div class="mb-1">
                                <label for="father_email" class="form-label mb-0 fs-13 fw-medium">Father Email</label>
                                <input type="email" class="form-control form-control-sm py-1" id="father_email" name="father_email" style="height: 32px;">
                            </div>
                            
                            <div class="mb-1">
                                <label for="father_phone" class="form-label mb-0 fs-13 fw-medium">Father Phone</label>
                                <input type="tel" class="form-control form-control-sm py-1" id="father_phone" name="father_phone" style="height: 32px;">
                            </div>
                            
                            <div class="mb-1">
                                <label for="mother_phone" class="form-label mb-0 fs-13 fw-medium">Mother Phone</label>
                                <input type="tel" class="form-control form-control-sm py-1" id="mother_phone" name="mother_phone" style="height: 32px;">
                            </div>
                            
                            <div class="mb-1">
                                <label for="whatsapp_number" class="form-label mb-0 fs-13 fw-medium">WhatsApp Number</label>
                                <input type="tel" class="form-control form-control-sm py-1" id="whatsapp_number" name="whatsapp_number" style="height: 32px;">
                            </div>
                            
                            <div class="mb-1">
                                <label for="religion" class="form-label mb-0 fs-13 fw-medium">Religion</label>
                                <input type="text" class="form-control form-control-sm py-1" id="religion" name="religion" style="height: 32px;">
                            </div>
                            
                            <div class="mb-1">
                                <label for="home_address" class="form-label mb-0 fs-13 fw-medium">Home Address</label>
                                <textarea class="form-control form-control-sm py-1" id="home_address" name="home_address" rows="2" style="min-height: 50px;"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Second Row: Other Information & Academic Information -->
                <div class="row mb-2">
                    <!-- Other Information -->
                    <div class="col-md-6">
                        <div class="card bg-light border-0 rounded-10 p-2 mb-2">
                            <h5 class="mb-1 py-2 px-3 text-white rounded-3 fw-semibold fs-15" style="margin: -8px -8px 8px -8px; background-color: #003471;">Other Information</h5>
                            
                            <div class="mb-1">
                                <label for="b_form_number" class="form-label mb-0 fs-13 fw-medium">B-Form Number</label>
                                <input type="text" class="form-control form-control-sm py-1" id="b_form_number" name="b_form_number" style="height: 32px;">
                            </div>
                            
                            <div class="mb-1">
                                <label for="monthly_fee" class="form-label mb-0 fs-13 fw-medium">Monthly Fee</label>
                                <input type="number" class="form-control form-control-sm py-1" id="monthly_fee" name="monthly_fee" step="0.01" style="height: 32px;">
                            </div>
                            
                            <div class="mb-1">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="discounted_student" name="discounted_student" value="1">
                                    <label class="form-check-label fs-13 fw-medium" for="discounted_student">
                                        Discounted Student?
                                    </label>
                                </div>
                            </div>
                            
                            <div class="mb-1">
                                <label for="transport_route" class="form-label mb-0 fs-13 fw-medium">Transport Route</label>
                                <input type="text" class="form-control form-control-sm py-1" id="transport_route" name="transport_route" style="height: 32px;">
                            </div>
                            
                            <div class="mb-1">
                                <label for="admission_notification" class="form-label mb-0 fs-13 fw-medium">Admission Notification</label>
                                <input type="text" class="form-control form-control-sm py-1" id="admission_notification" name="admission_notification" style="height: 32px;">
                            </div>
                            
                            <div class="mb-1">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="create_parent_account" name="create_parent_account" value="1">
                                    <label class="form-check-label fs-13 fw-medium" for="create_parent_account">
                                        Create Parent Account
                                    </label>
                                </div>
                            </div>
                            
                            <div class="mb-1">
                                <label for="generate_other_fee" class="form-label mb-0 fs-13 fw-medium">Generate Other Fee</label>
                                <input type="text" class="form-control form-control-sm py-1" id="generate_other_fee" name="generate_other_fee" style="height: 32px;">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Academic Information -->
                    <div class="col-md-6">
                        <div class="card bg-light border-0 rounded-10 p-2 mb-2">
                            <h5 class="mb-1 py-2 px-3 text-white rounded-3 fw-semibold fs-15" style="margin: -8px -8px 8px -8px; background-color: #003471;">Academic Information</h5>
                            
                            <div class="mb-1">
                                <label for="student_code" class="form-label mb-0 fs-13 fw-medium">Student Code</label>
                                <input type="text" class="form-control form-control-sm py-1" id="student_code" name="student_code" value="{{ $nextStudentCode ?? '' }}" readonly style="height: 32px; background-color: #f8f9fa;" placeholder="Auto-generated">
                                <small class="text-muted fs-11">Auto-generated code</small>
                            </div>
                            
                            <div class="mb-1">
                                <label for="gr_number" class="form-label mb-0 fs-13 fw-medium">G.R Number</label>
                                <input type="text" class="form-control form-control-sm py-1" id="gr_number" name="gr_number" style="height: 32px;">
                            </div>
                            
                            <div class="mb-1">
                                <label for="campus" class="form-label mb-0 fs-13 fw-medium">Campus</label>
                                <select class="form-select form-select-sm py-1" id="campus" name="campus" style="height: 32px;">
                                    <option value="">Select Campus</option>
                                    @foreach($campuses as $campus)
                                        <option value="{{ $campus }}" {{ old('campus') == $campus ? 'selected' : '' }}>{{ $campus }}</option>
                                    @endforeach
                                </select>
                            </div>
                            
                            <div class="mb-1">
                                <label for="class" class="form-label mb-0 fs-13 fw-medium">Class</label>
                                <select class="form-select form-select-sm py-1" id="class" name="class" required style="height: 32px;">
                                    <option value="">Select Class</option>
                                    @foreach($classes as $class)
                                        <option value="{{ $class }}" {{ old('class') == $class ? 'selected' : '' }}>{{ $class }}</option>
                                    @endforeach
                                </select>
                            </div>
                            
                            <div class="mb-1">
                                <label for="section" class="form-label mb-0 fs-13 fw-medium">Section</label>
                                <select class="form-select form-select-sm py-1" id="section" name="section" style="height: 32px;">
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
                            
                            <div class="mb-1">
                                <label for="previous_school" class="form-label mb-0 fs-13 fw-medium">Previous School</label>
                                <input type="text" class="form-control form-control-sm py-1" id="previous_school" name="previous_school" style="height: 32px;">
                            </div>
                            
                            <div class="mb-1">
                                <label for="admission_date" class="form-label mb-0 fs-13 fw-medium">Admission Date</label>
                                <input type="date" class="form-control form-control-sm py-1" id="admission_date" name="admission_date" required style="height: 32px;">
                            </div>
                            
                            <div class="mb-1">
                                <label for="reference_remarks" class="form-label mb-0 fs-13 fw-medium">Reference/Remarks</label>
                                <textarea class="form-control form-control-sm py-1" id="reference_remarks" name="reference_remarks" rows="2" style="min-height: 50px;"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Submit Button -->
                <div class="row">
                    <div class="col-12">
                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary px-5 py-2" style="color: white;">
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
@endsection
