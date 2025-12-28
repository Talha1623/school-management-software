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
                                <select class="form-select form-select-sm" id="class" name="class" required style="height: 32px; font-size: 13px;" onchange="loadSections(this.value)">
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
                                <input type="text" class="form-control form-control-sm" id="transport_route" name="transport_route" value="{{ old('transport_route', $student->transport_route) }}" style="height: 32px; font-size: 13px;">
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
                        <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">save</span>
                        Update Student
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function loadSections(className) {
    const sectionSelect = document.getElementById('section');
    sectionSelect.innerHTML = '<option value="">Select Section</option>';
    
    if (className) {
        fetch(`{{ route('admission.get-sections') }}?class=${encodeURIComponent(className)}`)
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
}

// Load sections on page load if class is already selected
document.addEventListener('DOMContentLoaded', function() {
    const selectedClass = document.getElementById('class').value;
    if (selectedClass) {
        loadSections(selectedClass);
        // Set the selected section after loading
        setTimeout(() => {
            const currentSection = '{{ old('section', $student->section) }}';
            if (currentSection) {
                document.getElementById('section').value = currentSection;
            }
        }, 500);
    }
});
</script>
@endsection

