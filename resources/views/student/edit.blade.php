@extends('layouts.app')

@section('title', 'Edit Student')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-4">
            <div class="d-flex align-items-center justify-content-between mb-4">
                <div class="d-flex align-items-center">
                    <span class="material-symbols-outlined me-2" style="font-size: 28px; color: #003471;">edit</span>
                    <h3 class="mb-0 fw-bold" style="color: #003471;">Edit Student</h3>
                </div>
                <a href="{{ route('student.information') }}" class="btn btn-sm btn-secondary">
                    <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">arrow_back</span>
                    Back to List
                </a>
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
            
            <form action="{{ route('student.update', $student->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                
                <!-- Student Information & Parent Information -->
                <div class="row mb-3">
                    <!-- Student Information -->
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm rounded-3 p-3 mb-3" style="background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);">
                            <div class="d-flex align-items-center mb-3" style="background-color: #003471; padding: 10px 15px; margin: -12px -12px 12px -12px; border-radius: 8px 8px 0 0;">
                                <span class="material-symbols-outlined me-2" style="font-size: 18px; color: #ffffff;">person</span>
                                <h5 class="mb-0 fw-semibold fs-15" style="color: #ffffff;">Student Information</h5>
                            </div>
                            
                            <div class="mb-2">
                                <label for="student_name" class="form-label mb-0 fs-13 fw-medium">Student Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-sm" id="student_name" name="student_name" value="{{ old('student_name', $student->student_name) }}" required style="height: 32px; font-size: 13px;">
                            </div>
                            
                            <div class="mb-2">
                                <label for="surname_caste" class="form-label mb-0 fs-13 fw-medium">Surname/Caste</label>
                                <input type="text" class="form-control form-control-sm" id="surname_caste" name="surname_caste" value="{{ old('surname_caste', $student->surname_caste) }}" style="height: 32px; font-size: 13px;">
                            </div>
                            
                            <div class="mb-2">
                                <label for="gender" class="form-label mb-0 fs-13 fw-medium">Gender <span class="text-danger">*</span></label>
                                <select class="form-select form-select-sm" id="gender" name="gender" required style="height: 32px; font-size: 13px;">
                                    <option value="">Select Gender</option>
                                    <option value="male" {{ old('gender', $student->gender) == 'male' ? 'selected' : '' }}>Male</option>
                                    <option value="female" {{ old('gender', $student->gender) == 'female' ? 'selected' : '' }}>Female</option>
                                    <option value="other" {{ old('gender', $student->gender) == 'other' ? 'selected' : '' }}>Other</option>
                                </select>
                            </div>
                            
                            <div class="mb-2">
                                <label for="date_of_birth" class="form-label mb-0 fs-13 fw-medium">Date Of Birth <span class="text-danger">*</span></label>
                                <input type="date" class="form-control form-control-sm" id="date_of_birth" name="date_of_birth" value="{{ old('date_of_birth', $student->date_of_birth ? $student->date_of_birth->format('Y-m-d') : '') }}" required style="height: 32px; font-size: 13px;">
                            </div>
                            
                            <div class="mb-2">
                                <label for="place_of_birth" class="form-label mb-0 fs-13 fw-medium">Place Of Birth</label>
                                <input type="text" class="form-control form-control-sm" id="place_of_birth" name="place_of_birth" value="{{ old('place_of_birth', $student->place_of_birth) }}" style="height: 32px; font-size: 13px;">
                            </div>
                            
                            <div class="mb-2">
                                <label for="photo" class="form-label mb-0 fs-13 fw-medium">Photo</label>
                                @if($student->photo)
                                    <div class="mb-2">
                                        <img src="{{ asset('storage/' . $student->photo) }}" alt="Current Photo" style="max-width: 100px; max-height: 100px; border-radius: 4px;">
                                    </div>
                                @endif
                                <input type="file" class="form-control form-control-sm" id="photo" name="photo" accept="image/*" style="height: 32px; font-size: 13px;">
                                <small class="text-muted">Leave empty to keep current photo</small>
                            </div>
                            
                            <div class="mb-2">
                                <label for="student_code" class="form-label mb-0 fs-13 fw-medium">Student Code</label>
                                <input type="text" class="form-control form-control-sm" id="student_code" name="student_code" value="{{ old('student_code', $student->student_code) }}" style="height: 32px; font-size: 13px;">
                            </div>
                            
                            <div class="mb-2">
                                <label for="gr_number" class="form-label mb-0 fs-13 fw-medium">GR Number</label>
                                <input type="text" class="form-control form-control-sm" id="gr_number" name="gr_number" value="{{ old('gr_number', $student->gr_number) }}" style="height: 32px; font-size: 13px;">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Parent Information -->
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm rounded-3 p-3 mb-3" style="background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);">
                            <div class="d-flex align-items-center mb-3" style="background-color: #003471; padding: 10px 15px; margin: -12px -12px 12px -12px; border-radius: 8px 8px 0 0;">
                                <span class="material-symbols-outlined me-2" style="font-size: 18px; color: #ffffff;">family_restroom</span>
                                <h5 class="mb-0 fw-semibold fs-15" style="color: #ffffff;">Parent Information</h5>
                            </div>
                            
                            <div class="mb-2">
                                <label for="father_name" class="form-label mb-0 fs-13 fw-medium">Father Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-sm" id="father_name" name="father_name" value="{{ old('father_name', $student->father_name) }}" required style="height: 32px; font-size: 13px;">
                            </div>
                            
                            <div class="mb-2">
                                <label for="father_id_card" class="form-label mb-0 fs-13 fw-medium">Father ID Card</label>
                                <input type="text" class="form-control form-control-sm" id="father_id_card" name="father_id_card" value="{{ old('father_id_card', $student->father_id_card) }}" style="height: 32px; font-size: 13px;">
                            </div>
                            
                            <div class="mb-2">
                                <label for="father_email" class="form-label mb-0 fs-13 fw-medium">Father Email</label>
                                <input type="email" class="form-control form-control-sm" id="father_email" name="father_email" value="{{ old('father_email', $student->father_email) }}" style="height: 32px; font-size: 13px;">
                            </div>
                            
                            <div class="mb-2">
                                <label for="father_phone" class="form-label mb-0 fs-13 fw-medium">Father Phone</label>
                                <input type="text" class="form-control form-control-sm" id="father_phone" name="father_phone" value="{{ old('father_phone', $student->father_phone) }}" style="height: 32px; font-size: 13px;">
                            </div>
                            
                            <div class="mb-2">
                                <label for="mother_phone" class="form-label mb-0 fs-13 fw-medium">Mother Phone</label>
                                <input type="text" class="form-control form-control-sm" id="mother_phone" name="mother_phone" value="{{ old('mother_phone', $student->mother_phone) }}" style="height: 32px; font-size: 13px;">
                            </div>
                            
                            <div class="mb-2">
                                <label for="whatsapp_number" class="form-label mb-0 fs-13 fw-medium">WhatsApp Number</label>
                                <input type="text" class="form-control form-control-sm" id="whatsapp_number" name="whatsapp_number" value="{{ old('whatsapp_number', $student->whatsapp_number) }}" style="height: 32px; font-size: 13px;">
                            </div>
                            
                            <div class="mb-2">
                                <label for="home_address" class="form-label mb-0 fs-13 fw-medium">Home Address</label>
                                <textarea class="form-control form-control-sm" id="home_address" name="home_address" rows="2" style="font-size: 13px;">{{ old('home_address', $student->home_address) }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Academic Information & Other Information -->
                <div class="row mb-3">
                    <!-- Academic Information -->
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm rounded-3 p-3 mb-3" style="background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);">
                            <div class="d-flex align-items-center mb-3" style="background-color: #003471; padding: 10px 15px; margin: -12px -12px 12px -12px; border-radius: 8px 8px 0 0;">
                                <span class="material-symbols-outlined me-2" style="font-size: 18px; color: #ffffff;">school</span>
                                <h5 class="mb-0 fw-semibold fs-15" style="color: #ffffff;">Academic Information</h5>
                            </div>
                            
                            <div class="mb-2">
                                <label for="campus" class="form-label mb-0 fs-13 fw-medium">Campus</label>
                                <select class="form-select form-select-sm" id="campus" name="campus" style="height: 32px; font-size: 13px;">
                                    <option value="">Select Campus</option>
                                    @foreach($campuses as $campus)
                                        <option value="{{ $campus }}" {{ old('campus', $student->campus) == $campus ? 'selected' : '' }}>{{ $campus }}</option>
                                    @endforeach
                                </select>
                            </div>
                            
                            <div class="mb-2">
                                <label for="class" class="form-label mb-0 fs-13 fw-medium">Class <span class="text-danger">*</span></label>
                                <select class="form-select form-select-sm" id="class" name="class" required style="height: 32px; font-size: 13px;" onchange="loadSections(this.value, document.getElementById('campus')?.value || '')">
                                    <option value="">Select Class</option>
                                    @foreach($classes as $class)
                                        <option value="{{ $class }}" {{ old('class', $student->class) == $class ? 'selected' : '' }}>{{ $class }}</option>
                                    @endforeach
                                </select>
                            </div>
                            
                            <div class="mb-2">
                                <label for="section" class="form-label mb-0 fs-13 fw-medium">Section</label>
                                <select class="form-select form-select-sm" id="section" name="section" style="height: 32px; font-size: 13px;">
                                    <option value="">Select Section</option>
                                    @foreach($sections as $section)
                                        <option value="{{ $section }}" {{ old('section', $student->section) == $section ? 'selected' : '' }}>{{ $section }}</option>
                                    @endforeach
                                </select>
                            </div>
                            
                            <div class="mb-2">
                                <label for="admission_date" class="form-label mb-0 fs-13 fw-medium">Admission Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control form-control-sm" id="admission_date" name="admission_date" value="{{ old('admission_date', $student->admission_date ? $student->admission_date->format('Y-m-d') : '') }}" required style="height: 32px; font-size: 13px;">
                            </div>
                            
                            <div class="mb-2">
                                <label for="previous_school" class="form-label mb-0 fs-13 fw-medium">Previous School</label>
                                <input type="text" class="form-control form-control-sm" id="previous_school" name="previous_school" value="{{ old('previous_school', $student->previous_school) }}" style="height: 32px; font-size: 13px;">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Other Information -->
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm rounded-3 p-3 mb-3" style="background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);">
                            <div class="d-flex align-items-center mb-3" style="background-color: #003471; padding: 10px 15px; margin: -12px -12px 12px -12px; border-radius: 8px 8px 0 0;">
                                <span class="material-symbols-outlined me-2" style="font-size: 18px; color: #ffffff;">info</span>
                                <h5 class="mb-0 fw-semibold fs-15" style="color: #ffffff;">Other Information</h5>
                            </div>
                            
                            <div class="mb-2">
                                <label for="religion" class="form-label mb-0 fs-13 fw-medium">Religion</label>
                                <input type="text" class="form-control form-control-sm" id="religion" name="religion" value="{{ old('religion', $student->religion) }}" style="height: 32px; font-size: 13px;">
                            </div>
                            
                            <div class="mb-2">
                                <label for="b_form_number" class="form-label mb-0 fs-13 fw-medium">B-Form Number</label>
                                <input type="text" class="form-control form-control-sm" id="b_form_number" name="b_form_number" value="{{ old('b_form_number', $student->b_form_number) }}" style="height: 32px; font-size: 13px;">
                            </div>
                            
                            <div class="mb-2">
                                <label for="monthly_fee" class="form-label mb-0 fs-13 fw-medium">Monthly Fee</label>
                                <input type="number" step="0.01" class="form-control form-control-sm" id="monthly_fee" name="monthly_fee" value="{{ old('monthly_fee', $student->monthly_fee) }}" style="height: 32px; font-size: 13px;">
                            </div>
                            
                            <div class="mb-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="discounted_student" name="discounted_student" value="1" {{ old('discounted_student', $student->discounted_student) ? 'checked' : '' }}>
                                    <label class="form-check-label fs-13" for="discounted_student">Discounted Student</label>
                                </div>
                            </div>
                            
                            <div class="mb-2">
                                <label for="transport_route" class="form-label mb-0 fs-13 fw-medium">Transport Route</label>
                                <select class="form-control form-control-sm" id="transport_route" name="transport_route" style="height: 32px; font-size: 13px;" onchange="loadTransportFare(this.value)">
                                    <option value="">Select Transport Route</option>
                                    @foreach($transportRoutes as $route)
                                        <option value="{{ $route }}" {{ old('transport_route', $student->transport_route) == $route ? 'selected' : '' }}>{{ $route }}</option>
                                    @endforeach
                                </select>
                            </div>
                            
                            <!-- Transport Route Fare Field (hidden by default) -->
                            <div class="mb-2" id="transport_fare_container" style="display: none;">
                                <label for="transport_fare" class="form-label mb-0 fs-13 fw-medium">Transport Fare</label>
                                <input type="number" step="0.01" class="form-control form-control-sm" id="transport_fare" name="transport_fare" placeholder="Transport fare amount" readonly style="height: 32px; font-size: 13px; background-color: #f8f9fa;" value="{{ old('transport_fare', $student->transport_fare) }}">
                            </div>
                            
                            <div class="mb-2">
                                <label for="generate_admission_fee" class="form-label mb-0 fs-13 fw-medium">Generate Admission Fee</label>
                                <select class="form-control form-control-sm" id="generate_admission_fee" name="generate_admission_fee" style="height: 32px; font-size: 13px;" onchange="toggleAdmissionFeeAmount()">
                                    <option value="">Select</option>
                                    <option value="1" {{ old('generate_admission_fee', $student->generate_admission_fee ?? '') == '1' ? 'selected' : '' }}>Yes</option>
                                    <option value="0" {{ old('generate_admission_fee', $student->generate_admission_fee ?? '') == '0' ? 'selected' : '' }}>No</option>
                                </select>
                            </div>
                            
                            <!-- Admission Fee Amount Field (hidden by default) -->
                            <div class="mb-2" id="admission_fee_amount_container" style="display: none;">
                                <label for="admission_fee_amount" class="form-label mb-0 fs-13 fw-medium">Amount</label>
                                <input type="number" step="0.01" class="form-control form-control-sm" id="admission_fee_amount" name="admission_fee_amount" placeholder="admission fee amount" style="height: 32px; font-size: 13px;" value="{{ old('admission_fee_amount', $student->admission_fee_amount) }}">
                            </div>
                            
                            <div class="mb-2">
                                <label for="generate_other_fee" class="form-label mb-0 fs-13 fw-medium">Generate Other Fee</label>
                                <select class="form-control form-control-sm" id="generate_other_fee" name="generate_other_fee" style="height: 32px; font-size: 13px;" onchange="toggleOtherFeeFields()">
                                    <option value="">Select</option>
                                    <option value="1" {{ old('generate_other_fee', $student->generate_other_fee ?? '') == '1' ? 'selected' : '' }}>Yes</option>
                                    <option value="0" {{ old('generate_other_fee', $student->generate_other_fee ?? '') == '0' ? 'selected' : '' }}>No</option>
                                </select>
                            </div>
                            
                            <!-- Fee Type / Fee Head Dropdown (hidden by default) -->
                            <div class="mb-2" id="fee_type_container" style="display: none;">
                                <label for="fee_type" class="form-label mb-0 fs-13 fw-medium">Fee Type / Fee Head</label>
                                <select class="form-control form-control-sm" id="fee_type" name="fee_type" style="height: 32px; font-size: 13px;" onchange="toggleOtherFeeAmount()">
                                    <option value="">Select Fee Type</option>
                                    @foreach($feeTypes as $feeType)
                                        <option value="{{ $feeType }}" {{ old('fee_type', $student->fee_type) == $feeType ? 'selected' : '' }}>{{ $feeType }}</option>
                                    @endforeach
                                </select>
                            </div>
                            
                            <!-- Other Fee Amount Field (hidden by default) -->
                            <div class="mb-2" id="other_fee_amount_container" style="display: none;">
                                <label for="other_fee_amount" class="form-label mb-0 fs-13 fw-medium">Amount</label>
                                <input type="number" step="0.01" class="form-control form-control-sm" id="other_fee_amount" name="other_fee_amount" placeholder="Enter amount" style="height: 32px; font-size: 13px;" value="{{ old('other_fee_amount', $student->other_fee_amount) }}">
                            </div>
                            
                            <div class="mb-2">
                                <label for="reference_remarks" class="form-label mb-0 fs-13 fw-medium">Reference Remarks</label>
                                <textarea class="form-control form-control-sm" id="reference_remarks" name="reference_remarks" rows="2" style="font-size: 13px;">{{ old('reference_remarks', $student->reference_remarks) }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Submit Buttons -->
                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('student.information') }}" class="btn btn-secondary btn-sm">
                        <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">cancel</span>
                        Cancel
                    </a>
                    <button type="submit" class="btn btn-primary btn-sm" style="background: linear-gradient(135deg, #003471 0%, #004a9f 100%); border: none;">
                        <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle; color: white;">save</span>
                        <span style="color: white;">Update Student</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Load transport route fare when route is selected
function loadTransportFare(routeName) {
    const transportFareContainer = document.getElementById('transport_fare_container');
    const transportFareInput = document.getElementById('transport_fare');
    const campusValue = document.getElementById('campus')?.value;
    
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
    const params = new URLSearchParams();
    params.append('route', routeName);
    if (campusValue) {
        params.append('campus', campusValue);
    }
    fetch(`{{ route('admission.get-route-fare') }}?${params.toString()}`)
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
    const transportRouteSelect = document.getElementById('transport_route');
    const transportFareContainer = document.getElementById('transport_fare_container');
    const transportFareInput = document.getElementById('transport_fare');
    if (!transportRouteSelect) return;

    if (!campusValue) {
        transportRouteSelect.innerHTML = '<option value="">Select Campus First</option>';
        transportRouteSelect.disabled = true;
        if (transportFareContainer) {
            transportFareContainer.style.display = 'none';
        }
        if (transportFareInput) {
            transportFareInput.value = '';
        }
        return;
    }

    transportRouteSelect.innerHTML = '<option value="">Loading...</option>';
    transportRouteSelect.disabled = true;

    fetch(`{{ route('admission.get-transport-routes') }}?campus=${encodeURIComponent(campusValue)}`)
        .then(response => response.json())
        .then(data => {
            const routes = Array.isArray(data.routes) ? data.routes : [];
            transportRouteSelect.innerHTML = '<option value="">Select Transport Route</option>';
            routes.forEach(route => {
                const option = document.createElement('option');
                option.value = route;
                option.textContent = route;
                if (selectedRoute && route.toLowerCase().trim() === selectedRoute.toLowerCase().trim()) {
                    option.selected = true;
                }
                transportRouteSelect.appendChild(option);
            });
            transportRouteSelect.disabled = routes.length === 0;

            if (selectedRoute && transportRouteSelect.value) {
                loadTransportFare(transportRouteSelect.value);
            } else {
                if (transportFareContainer) {
                    transportFareContainer.style.display = 'none';
                }
                if (transportFareInput) {
                    transportFareInput.value = '';
                }
            }
        })
        .catch(error => {
            console.error('Error loading transport routes:', error);
            transportRouteSelect.innerHTML = '<option value="">Error loading routes</option>';
            transportRouteSelect.disabled = true;
        });
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

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    toggleAdmissionFeeAmount();
    toggleOtherFeeFields();
    
    // Load transport fare if route is already selected
    const transportRoute = document.getElementById('transport_route');
    const transportFareContainer = document.getElementById('transport_fare_container');
    const transportFareInput = document.getElementById('transport_fare');
    
    if (transportRoute && transportRoute.value) {
        // If route is selected, show fare field
        if (transportFareContainer) {
            transportFareContainer.style.display = 'block';
        }
        // If fare value already exists, keep it; otherwise fetch from route
        if (transportFareInput && !transportFareInput.value) {
            loadTransportFare(transportRoute.value);
        }
    } else {
        // Hide transport fare field if no route selected
        if (transportFareContainer) {
            transportFareContainer.style.display = 'none';
        }
    }
    
    // Show fee type and amount fields if they have values
    const feeTypeInput = document.getElementById('fee_type');
    const otherFeeAmountInput = document.getElementById('other_fee_amount');
    if (feeTypeInput && feeTypeInput.value) {
        const feeTypeContainer = document.getElementById('fee_type_container');
        if (feeTypeContainer) {
            feeTypeContainer.style.display = 'block';
        }
        toggleOtherFeeAmount();
    }
    if (otherFeeAmountInput && otherFeeAmountInput.value) {
        const otherFeeAmountContainer = document.getElementById('other_fee_amount_container');
        if (otherFeeAmountContainer) {
            otherFeeAmountContainer.style.display = 'block';
        }
    }
});

function loadClassesByCampus(campusValue, selectedClass = '') {
    const classSelect = document.getElementById('class');
    const sectionSelect = document.getElementById('section');
    if (!classSelect) return Promise.resolve();

    classSelect.innerHTML = '<option value="">Loading classes...</option>';
    classSelect.disabled = true;
    if (sectionSelect) {
        sectionSelect.innerHTML = '<option value="">Select Section</option>';
    }

    const params = new URLSearchParams();
    if (campusValue) {
        params.append('campus', campusValue);
    }

    return fetch(`{{ route('student.information.classes-by-campus') }}?${params.toString()}`)
        .then(response => response.json())
        .then(data => {
            const classes = Array.isArray(data.classes) ? data.classes : [];
            classSelect.innerHTML = '<option value="">Select Class</option>';
            classes.forEach(className => {
                const option = document.createElement('option');
                option.value = className;
                option.textContent = className;
                if (selectedClass && className.toLowerCase().trim() === selectedClass.toLowerCase().trim()) {
                    option.selected = true;
                }
                classSelect.appendChild(option);
            });
            classSelect.disabled = classes.length === 0;
        })
        .catch(error => {
            console.error('Error loading classes:', error);
            classSelect.innerHTML = '<option value="">Error loading classes</option>';
            classSelect.disabled = true;
        });
}

function loadSections(className, campusValue = '', selectedSectionOverride = '') {
    const sectionSelect = document.getElementById('section');
    const currentSection = selectedSectionOverride || '{{ old('section', $student->section) }}';
    
    // Clear existing options but keep current section value
    sectionSelect.innerHTML = '<option value="">Select Section</option>';
    
    if (className) {
        const params = new URLSearchParams();
        params.append('class', className);
        if (campusValue) {
            params.append('campus', campusValue);
        }
        fetch(`{{ route('student.information.sections-by-class') }}?${params.toString()}`)
            .then(response => response.json())
            .then(data => {
                if (data.sections && data.sections.length > 0) {
                    data.sections.forEach(section => {
                        const option = document.createElement('option');
                        option.value = section;
                        option.textContent = section;
                        // Mark as selected if it matches current section (case-insensitive)
                        if (currentSection && section.toLowerCase().trim() === currentSection.toLowerCase().trim()) {
                            option.selected = true;
                        }
                        sectionSelect.appendChild(option);
                    });
                }
                // Don't add default sections - only show sections that actually exist for this class
                
                // If current section still not set, try to set it
                if (currentSection && !sectionSelect.value) {
                    // Try exact match first
                    const options = Array.from(sectionSelect.options);
                    for (let opt of options) {
                        if (opt.value.toLowerCase().trim() === currentSection.toLowerCase().trim()) {
                            opt.selected = true;
                            break;
                        }
                    }
                }
            })
            .catch(error => {
                console.error('Error loading sections:', error);
            });
    } else {
        // If no class selected, clear sections
        sectionSelect.innerHTML = '<option value="">Select Section</option>';
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const campusSelect = document.getElementById('campus');
    const classSelect = document.getElementById('class');
    const selectedClass = '{{ old('class', $student->class) }}';
    const selectedSection = '{{ old('section', $student->section) }}';
    const selectedRoute = '{{ old('transport_route', $student->transport_route) }}';
    const campusValue = campusSelect ? campusSelect.value : '';

    loadClassesByCampus(campusValue, selectedClass).then(() => {
        const classValue = classSelect ? classSelect.value : '';
        if (classValue) {
            loadSections(classValue, campusValue, selectedSection);
        }
    });
    loadTransportRoutesByCampus(campusValue, selectedRoute);

    if (campusSelect) {
        campusSelect.addEventListener('change', function() {
            loadClassesByCampus(this.value).then(() => {
                const classValue = classSelect ? classSelect.value : '';
                if (classValue) {
                    loadSections(classValue, this.value);
                }
            });
            loadTransportRoutesByCampus(this.value);
        });
    }
});
</script>
@endsection

