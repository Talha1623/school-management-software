@extends('layouts.app')

@section('title', 'Admit Bulk Student')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-4">
            <div class="d-flex align-items-center mb-4">
                <span class="material-symbols-outlined me-2" style="font-size: 28px; color: #003471;">group_add</span>
                <h3 class="mb-0 fw-bold" style="color: #003471;">Admit Bulk Student</h3>
            </div>
            
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if(session('errors') && is_array(session('errors')) && count(session('errors')) > 0)
                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                    <strong>Errors:</strong>
                    <ul class="mb-0 mt-2" style="max-height: 200px; overflow-y: auto;">
                        @foreach(array_slice(session('errors'), 0, 10) as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
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

            <form action="{{ route('admission.admit-bulk-student.store') }}" method="POST" id="bulk-admission-form">
                @csrf

                <!-- Input Method Selection - 3 sections in 1 row -->
                <div class="card border-0 shadow-sm rounded-3 p-3 mb-4" style="background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);">
                    <div class="d-flex align-items-center mb-3" style="background-color: #003471; padding: 10px 15px; margin: -12px -12px 12px -12px; border-radius: 8px 8px 0 0;">
                        <span class="material-symbols-outlined me-2" style="font-size: 20px; color: #ffffff;">settings</span>
                        <h5 class="mb-0 fw-semibold fs-16" style="color: #ffffff;">Input Method Selection</h5>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-check form-check-inline p-3 border rounded" style="cursor: pointer; background: #f8f9fa;">
                                <input class="form-check-input" type="radio" name="input_method" id="input_method_manual" value="manual" checked>
                                <label class="form-check-label fw-medium" for="input_method_manual" style="cursor: pointer;">
                                    <span class="material-symbols-outlined me-1" style="font-size: 18px; vertical-align: middle;">edit</span>
                                    Manual Entry
                                </label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-check form-check-inline p-3 border rounded" style="cursor: pointer; background: #f8f9fa;">
                                <input class="form-check-input" type="radio" name="input_method" id="input_method_csv" value="csv" disabled>
                                <label class="form-check-label fw-medium text-muted" for="input_method_csv" style="cursor: not-allowed;">
                                    <span class="material-symbols-outlined me-1" style="font-size: 18px; vertical-align: middle;">upload_file</span>
                                    CSV Upload
                                    <small class="d-block text-muted" style="font-size: 11px;">(Coming Soon)</small>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Class and Section Selection -->
                <div class="card border-0 shadow-sm rounded-3 p-3 mb-4" style="background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);">
                    <div class="d-flex align-items-center mb-3" style="background-color: #003471; padding: 10px 15px; margin: -12px -12px 12px -12px; border-radius: 8px 8px 0 0;">
                        <span class="material-symbols-outlined me-2" style="font-size: 20px; color: #ffffff;">class</span>
                        <h5 class="mb-0 fw-semibold fs-16" style="color: #ffffff;">Class and Section Selection</h5>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label for="campus" class="form-label mb-1 fs-13 fw-medium">
                                Campus <span class="text-danger">*</span>
                            </label>
                            <select class="form-select" id="campus" name="campus" required>
                                <option value="">Select Campus</option>
                                @foreach($campuses as $campus)
                                    <option value="{{ $campus }}">{{ $campus }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="class" class="form-label mb-1 fs-13 fw-medium">
                                Select Class <span class="text-danger">*</span>
                            </label>
                            <select class="form-select" id="class" name="class" required>
                                <option value="">Select Class</option>
                                @foreach($classes as $class)
                                    <option value="{{ $class }}">{{ $class }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="section" class="form-label mb-1 fs-13 fw-medium">
                                Section
                            </label>
                            <select class="form-select" id="section" name="section">
                                <option value="">Select Section</option>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="create_parent_accounts" class="form-label mb-1 fs-13 fw-medium">
                                Create Parent Accounts
                            </label>
                            <select class="form-select" id="create_parent_accounts" name="create_parent_accounts" required>
                                <option value="1">Yes</option>
                                <option value="0">No</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label for="number_of_students" class="form-label mb-1 fs-13 fw-medium">
                                Number of Students <span class="text-danger">*</span>
                            </label>
                            <input type="number" class="form-control" id="number_of_students" name="number_of_students" min="1" max="50" value="1" required>
                            <small class="text-muted">Maximum 50 students at a time</small>
                        </div>
                    </div>
                </div>

                <!-- Student Information Section -->
                <div class="card border-0 shadow-sm rounded-3 p-3 mb-4" style="background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);">
                    <div class="row mb-3">
                        <div class="col-12">
                            <div class="d-flex align-items-center" style="background-color: #003471; padding: 10px 15px; border-radius: 8px;">
                                <span class="material-symbols-outlined me-2" style="font-size: 20px; color: #ffffff;">person</span>
                                <h5 class="mb-0 fw-semibold fs-16" style="color: #ffffff;">Student Information</h5>
                            </div>
                        </div>
                    </div>

                    <!-- Student Forms Container -->
                    <div id="students-container">
                        <style>
                            #students-container .form-control,
                            #students-container .form-select {
                                height: 32px !important;
                                padding: 0.25rem 0.5rem;
                                font-size: 0.875rem;
                            }
                        </style>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="d-flex justify-content-end mt-4">
                    <button type="submit" class="btn btn-primary" id="import-all-btn">
                        <span class="material-symbols-outlined me-2" style="font-size: 18px; vertical-align: middle;">upload</span>
                        Import All Student Data
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const studentsContainer = document.getElementById('students-container');
    const classSelect = document.getElementById('class');
    const sectionSelect = document.getElementById('section');
    const numberInput = document.getElementById('number_of_students');

    // Check if all required elements exist
    if (!studentsContainer || !classSelect || !sectionSelect || !numberInput) {
        console.error('Required elements not found');
        return;
    }

    // Function to generate student forms
    function generateStudentForms() {
        const numberOfStudents = parseInt(numberInput.value) || 1;
        if (numberOfStudents < 1 || numberOfStudents > 50) {
            alert('Number of students must be between 1 and 50');
            numberInput.value = 1;
            return;
        }

        studentsContainer.innerHTML = '';
        
        for (let i = 1; i <= numberOfStudents; i++) {
            const studentCard = document.createElement('div');
            studentCard.className = 'mb-4 p-3 border rounded';
            studentCard.style.background = '#f8f9fa';
            studentCard.innerHTML = `
                <div class="row mb-3">
                    <div class="col-12">
                        <div class="d-flex align-items-center" style="background-color: #003471; padding: 10px 15px; border-radius: 8px;">
                            <span class="material-symbols-outlined me-2" style="font-size: 20px; color: #ffffff;">person</span>
                            <h5 class="mb-0 fw-semibold fs-16" style="color: #ffffff;">Student ${i} Information</h5>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-2 mb-3">
                        <label class="form-label mb-1 fs-13 fw-medium">Student Code</label>
                        <input type="text" class="form-control form-control-sm" name="students[${i-1}][student_code]" placeholder="Auto-generated" style="height: 32px;">
                    </div>
                    <div class="col-md-2 mb-3">
                        <label class="form-label mb-1 fs-13 fw-medium">Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-sm" name="students[${i-1}][student_name]" required style="height: 32px;">
                    </div>
                    <div class="col-md-2 mb-3">
                        <label class="form-label mb-1 fs-13 fw-medium">Gender <span class="text-danger">*</span></label>
                        <select class="form-select form-select-sm" name="students[${i-1}][gender]" required style="height: 32px;">
                            <option value="">Select</option>
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="col-md-2 mb-3">
                        <label class="form-label mb-1 fs-13 fw-medium">Father Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-sm" name="students[${i-1}][father_name]" required style="height: 32px;">
                    </div>
                    <div class="col-md-2 mb-3">
                        <label class="form-label mb-1 fs-13 fw-medium">Father CNIC</label>
                        <input type="text" class="form-control form-control-sm" name="students[${i-1}][father_id_card]" placeholder="CNIC" style="height: 32px;">
                    </div>
                    <div class="col-md-2 mb-3">
                        <label class="form-label mb-1 fs-13 fw-medium">Father Phone</label>
                        <input type="text" class="form-control form-control-sm" name="students[${i-1}][father_phone]" placeholder="Phone" style="height: 32px;">
                    </div>
                    <div class="col-md-2 mb-3">
                        <label class="form-label mb-1 fs-13 fw-medium">Mother Phone</label>
                        <input type="text" class="form-control form-control-sm" name="students[${i-1}][mother_phone]" placeholder="Phone" style="height: 32px;">
                    </div>
                    <div class="col-md-2 mb-3">
                        <label class="form-label mb-1 fs-13 fw-medium">Birthday <span class="text-danger">*</span></label>
                        <input type="date" class="form-control form-control-sm" name="students[${i-1}][date_of_birth]" required style="height: 32px;">
                    </div>
                    <div class="col-md-2 mb-3">
                        <label class="form-label mb-1 fs-13 fw-medium">Home Address</label>
                        <input type="text" class="form-control form-control-sm" name="students[${i-1}][home_address]" placeholder="Address" style="height: 32px;">
                    </div>
                    <div class="col-md-2 mb-3">
                        <label class="form-label mb-1 fs-13 fw-medium">Monthly Fee</label>
                        <input type="number" class="form-control form-control-sm" name="students[${i-1}][monthly_fee]" step="0.01" min="0" placeholder="0.00" style="height: 32px;">
                    </div>
                    <div class="col-md-2 mb-3">
                        <label class="form-label mb-1 fs-13 fw-medium">Arrears</label>
                        <input type="number" class="form-control form-control-sm" name="students[${i-1}][arrears]" step="0.01" min="0" placeholder="0.00" style="height: 32px;">
                    </div>
                </div>
            `;
            studentsContainer.appendChild(studentCard);
        }
    }

    // Load sections when class changes
    classSelect.addEventListener('change', function() {
        const classValue = this.value;
        if (classValue) {
            fetch(`{{ route('admission.get-sections') }}?class=${encodeURIComponent(classValue)}`)
                .then(response => response.json())
                .then(data => {
                    sectionSelect.innerHTML = '<option value="">Select Section</option>';
                    if (data.sections && data.sections.length > 0) {
                        data.sections.forEach(section => {
                            const option = document.createElement('option');
                            option.value = section.name || section;
                            option.textContent = section.name || section;
                            sectionSelect.appendChild(option);
                        });
                    }
                })
                .catch(error => {
                    console.error('Error loading sections:', error);
                });
        } else {
            sectionSelect.innerHTML = '<option value="">Select Section</option>';
        }
    });

    // Auto-generate forms when number of students changes
    numberInput.addEventListener('input', function() {
        generateStudentForms();
    });

    // Auto-generate forms on page load
    setTimeout(function() {
        generateStudentForms();
    }, 100);
});
</script>
@endsection
