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

            <form action="{{ route('admission.admit-bulk-student.store') }}" method="POST" id="bulk-admission-form" enctype="multipart/form-data">
                @csrf

                <!-- Input Method Selection -->
                <div class="card border-0 shadow-sm rounded-3 p-3 mb-4" style="background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);">
                    <div class="d-flex align-items-center mb-3" style="background-color: #003471; padding: 10px 15px; margin: -12px -12px 12px -12px; border-radius: 8px 8px 0 0;">
                        <span class="material-symbols-outlined me-2" style="font-size: 20px; color: #ffffff;">settings</span>
                        <h5 class="mb-0 fw-semibold fs-16" style="color: #ffffff;">Input Method Selection</h5>
                    </div>
                    
                    <style>
                        .method-option {
                            border: 2px solid #dee2e6 !important;
                            border-radius: 12px !important;
                            transition: all 0.3s ease !important;
                            position: relative;
                            overflow: hidden;
                        }
                        .method-option:hover {
                            transform: translateY(-2px);
                            box-shadow: 0 4px 12px rgba(0, 52, 113, 0.15) !important;
                            border-color: #003471 !important;
                        }
                        .method-option.active {
                            background: linear-gradient(135deg, #e3f2fd 0%, #ffffff 100%) !important;
                            border-color: #003471 !important;
                            box-shadow: 0 4px 16px rgba(0, 52, 113, 0.2) !important;
                        }
                        .method-option .form-check-input {
                            width: 20px;
                            height: 20px;
                            margin-top: 2px;
                            cursor: pointer;
                        }
                        .method-option .form-check-input:checked {
                            background-color: #003471;
                            border-color: #003471;
                        }
                        .method-option label {
                            width: 100%;
                            margin-left: 8px;
                        }
                        .method-option .material-symbols-outlined {
                            font-size: 32px !important;
                            padding: 12px;
                            background: rgba(0, 52, 113, 0.1);
                            border-radius: 10px;
                            margin-right: 12px;
                        }
                        .method-option.active .material-symbols-outlined {
                            background: rgba(0, 52, 113, 0.15);
                            color: #003471 !important;
                        }
                        .method-option .fw-semibold {
                            font-size: 16px;
                            color: #003471;
                            margin-bottom: 4px;
                        }
                        .method-option .text-muted {
                            font-size: 13px;
                            color: #6c757d;
                        }
                    </style>
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="form-check form-check-inline p-4 method-option active" data-method="manual" id="manual-option">
                                <input class="form-check-input" type="radio" name="input_method" id="input_method_manual" value="manual" checked>
                                <label class="form-check-label fw-medium d-flex align-items-center" for="input_method_manual">
                                    <span class="material-symbols-outlined" style="color: #003471;">edit</span>
                                    <div>
                                        <div class="fw-semibold">Manual Entry</div>
                                        <small class="text-muted">Enter student data manually</small>
                                    </div>
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check form-check-inline p-4 method-option" data-method="csv" id="csv-option">
                                <input class="form-check-input" type="radio" name="input_method" id="input_method_csv" value="csv">
                                <label class="form-check-label fw-medium d-flex align-items-center" for="input_method_csv">
                                    <span class="material-symbols-outlined" style="color: #003471;">upload_file</span>
                                    <div>
                                        <div class="fw-semibold">CSV Upload</div>
                                        <small class="text-muted">Upload CSV file with student data</small>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Class and Section Selection -->
                <div class="card border-0 shadow-sm rounded-3 p-3 mb-4" id="class-selection-section" style="background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);">
                    <div class="d-flex align-items-center mb-3" style="background-color: #003471; padding: 10px 15px; margin: -12px -12px 12px -12px; border-radius: 8px 8px 0 0;">
                        <span class="material-symbols-outlined me-2" style="font-size: 20px; color: #ffffff;">class</span>
                        <h5 class="mb-0 fw-semibold fs-16" style="color: #ffffff;">Class and Section Selection</h5>
                    </div>
                    
                    <style>
                        #class-selection-section .form-control,
                        #class-selection-section .form-select {
                            height: 32px !important;
                            padding: 0.25rem 0.5rem;
                            font-size: 0.875rem;
                        }
                    </style>
                    
                    <!-- Manual Entry Mode -->
                    <div id="manual-mode-fields">
                        <div class="row">
                            <div class="col-md-2 mb-3">
                                <label for="campus" class="form-label mb-1 fs-13 fw-medium">
                                    Campus <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" id="campus" name="campus">
                                    <option value="">Select Campus</option>
                                    @foreach($campuses as $campus)
                                        <option value="{{ $campus }}">{{ $campus }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2 mb-3">
                                <label for="class" class="form-label mb-1 fs-13 fw-medium">
                                    Select Class <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" id="class" name="class">
                                    <option value="">Select Class</option>
                                    @foreach($classes as $class)
                                        <option value="{{ $class }}">{{ $class }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2 mb-3">
                                <label for="section" class="form-label mb-1 fs-13 fw-medium">
                                    Section
                                </label>
                                <select class="form-select" id="section" name="section">
                                    <option value="">Select Section</option>
                                </select>
                            </div>
                            <div class="col-md-2 mb-3">
                                <label for="create_parent_accounts" class="form-label mb-1 fs-13 fw-medium">
                                    Create Parent Accounts
                                </label>
                                <select class="form-select" id="create_parent_accounts" name="create_parent_accounts">
                                    <option value="1">Yes</option>
                                    <option value="0">No</option>
                                </select>
                            </div>
                            <div class="col-md-2 mb-3">
                                <label for="number_of_students" class="form-label mb-1 fs-13 fw-medium">
                                    Number of Students <span class="text-danger">*</span>
                                </label>
                                <input type="number" class="form-control" id="number_of_students" name="number_of_students" min="1" max="50" value="1">
                                <small class="text-muted" style="font-size: 0.75rem;">Max 50</small>
                            </div>
                        </div>
                    </div>
                    
                    <!-- CSV Upload Mode -->
                    <div id="csv-mode-fields" style="display: none;">
                        <div class="row">
                            <div class="col-md-2 mb-3">
                                <label for="csv_campus" class="form-label mb-1 fs-13 fw-medium">
                                    Campus <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" id="csv_campus" name="csv_campus">
                                    <option value="">Select Campus</option>
                                    @foreach($campuses as $campus)
                                        <option value="{{ $campus }}">{{ $campus }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2 mb-3">
                                <label for="csv_class" class="form-label mb-1 fs-13 fw-medium">
                                    Select Class <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" id="csv_class" name="csv_class">
                                    <option value="">Select Class</option>
                                    @foreach($classes as $class)
                                        <option value="{{ $class }}">{{ $class }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2 mb-3">
                                <label for="csv_section" class="form-label mb-1 fs-13 fw-medium">
                                    Section
                                </label>
                                <select class="form-select" id="csv_section" name="csv_section">
                                    <option value="">Select Section</option>
                                </select>
                            </div>
                            <div class="col-md-2 mb-3">
                                <label for="csv_create_parent_accounts" class="form-label mb-1 fs-13 fw-medium">
                                    Create Parent Accounts
                                </label>
                                <select class="form-select" id="csv_create_parent_accounts" name="csv_create_parent_accounts">
                                    <option value="1">Yes</option>
                                    <option value="0">No</option>
                                </select>
                            </div>
                            <div class="col-md-2 mb-3">
                                <label for="csv_file" class="form-label mb-1 fs-13 fw-medium">
                                    CSV/Excel File <span class="text-danger">*</span>
                                </label>
                                <input type="file" class="form-control" id="csv_file" name="csv_file" accept=".csv,.xlsx,.xls">
                                <small class="text-muted" style="font-size: 0.75rem;">Upload CSV or Excel file</small>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-12">
                                <a href="{{ route('admission.download-csv-template') }}" class="btn btn-outline-primary btn-sm" download>
                                    <span class="material-symbols-outlined me-2" style="font-size: 18px; vertical-align: middle;">download</span>
                                    Download CSV Template
                                </a>
                                <small class="text-muted ms-2" style="font-size: 0.75rem;">Download Excel file with required fields</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Student Information Section -->
                <div class="card border-0 shadow-sm rounded-3 p-3 mb-4" id="student-information-section" style="background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);">
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
                    <button type="submit" class="btn btn-primary" id="import-all-btn" style="color: #ffffff; cursor: pointer;">
                        <span class="material-symbols-outlined me-2" style="font-size: 18px; vertical-align: middle; color: #ffffff;">upload</span>
                        <span style="color: #ffffff;">Import All Student Data</span>
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
    const csvClassSelect = document.getElementById('csv_class');
    const csvSectionSelect = document.getElementById('csv_section');
    const manualModeFields = document.getElementById('manual-mode-fields');
    const csvModeFields = document.getElementById('csv-mode-fields');
    const studentInfoSection = document.getElementById('student-information-section');
    const manualOption = document.getElementById('manual-option');
    const csvOption = document.getElementById('csv-option');
    const inputMethodManual = document.getElementById('input_method_manual');
    const inputMethodCsv = document.getElementById('input_method_csv');

    // Function to toggle between manual and CSV modes
    function toggleInputMethod() {
        const isManual = inputMethodManual.checked;
        const submitButton = document.getElementById('import-all-btn');
        
        if (isManual) {
            // Manual mode
            manualModeFields.style.display = 'block';
            csvModeFields.style.display = 'none';
            studentInfoSection.style.display = 'block';
            manualOption.classList.add('active');
            csvOption.classList.remove('active');
            
            // Set required attributes for manual mode
            if (classSelect) classSelect.setAttribute('required', 'required');
            if (numberInput) numberInput.setAttribute('required', 'required');
            if (csvClassSelect) csvClassSelect.removeAttribute('required');
            if (document.getElementById('csv_file')) document.getElementById('csv_file').removeAttribute('required');
            
            // Ensure button is enabled
            if (submitButton) {
                submitButton.disabled = false;
                submitButton.style.display = 'inline-block';
            }
        } else {
            // CSV mode
            manualModeFields.style.display = 'none';
            csvModeFields.style.display = 'block';
            studentInfoSection.style.display = 'none';
            csvOption.classList.add('active');
            manualOption.classList.remove('active');
            
            // Set required attributes for CSV mode
            if (classSelect) classSelect.removeAttribute('required');
            if (numberInput) numberInput.removeAttribute('required');
            if (document.getElementById('csv_campus')) document.getElementById('csv_campus').setAttribute('required', 'required');
            if (csvClassSelect) csvClassSelect.setAttribute('required', 'required');
            if (document.getElementById('csv_file')) document.getElementById('csv_file').setAttribute('required', 'required');
            
            // Ensure button is enabled and visible in CSV mode
            if (submitButton) {
                submitButton.disabled = false;
                submitButton.style.display = 'inline-block';
            }
        }
    }

    // Handle input method selection
    if (inputMethodManual && inputMethodCsv) {
        inputMethodManual.addEventListener('change', toggleInputMethod);
        inputMethodCsv.addEventListener('change', toggleInputMethod);
        
        // Also handle clicks on the option divs
        if (manualOption) {
            manualOption.addEventListener('click', function() {
                inputMethodManual.checked = true;
                toggleInputMethod();
            });
        }
        if (csvOption) {
            csvOption.addEventListener('click', function() {
                inputMethodCsv.checked = true;
                toggleInputMethod();
            });
        }
        
        // Initialize on page load
        toggleInputMethod();
    }

    // Function to load sections
    function loadSections(classSelectElement, sectionSelectElement) {
        if (!classSelectElement || !sectionSelectElement) return;
        
        classSelectElement.addEventListener('change', function() {
            const classValue = this.value;
            if (classValue) {
                fetch(`{{ route('admission.get-sections') }}?class=${encodeURIComponent(classValue)}`)
                    .then(response => response.json())
                    .then(data => {
                        sectionSelectElement.innerHTML = '<option value="">Select Section</option>';
                        if (data.sections && data.sections.length > 0) {
                            data.sections.forEach(section => {
                                const option = document.createElement('option');
                                option.value = section.name || section;
                                option.textContent = section.name || section;
                                sectionSelectElement.appendChild(option);
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error loading sections:', error);
                    });
            } else {
                sectionSelectElement.innerHTML = '<option value="">Select Section</option>';
            }
        });
    }

    // Load sections for both manual and CSV modes
    if (classSelect && sectionSelect) {
        loadSections(classSelect, sectionSelect);
    }
    if (csvClassSelect && csvSectionSelect) {
        loadSections(csvClassSelect, csvSectionSelect);
    }

    // Function to generate student forms (only for manual mode)
    function generateStudentForms() {
        if (!studentsContainer || !numberInput) return;
        
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

    // Auto-generate forms when number of students changes (only in manual mode)
    if (numberInput) {
        numberInput.addEventListener('input', function() {
            if (inputMethodManual && inputMethodManual.checked) {
                generateStudentForms();
            }
        });
    }

    // Auto-generate forms on page load (only if manual mode is selected)
    setTimeout(function() {
        if (inputMethodManual && inputMethodManual.checked) {
            generateStudentForms();
        }
    }, 100);

    // Ensure form can submit in both modes
    const form = document.getElementById('bulk-admission-form');
    const submitButton = document.getElementById('import-all-btn');
    const csvFileInput = document.getElementById('csv_file');
    
    // Auto-submit form when CSV file is selected (only in CSV mode)
    if (csvFileInput && form) {
        csvFileInput.addEventListener('change', function(e) {
            // Only auto-submit if CSV mode is selected
            if (inputMethodCsv && inputMethodCsv.checked) {
                const csvFile = this.files[0];
                const csvCampus = document.getElementById('csv_campus');
                const csvClass = document.getElementById('csv_class');
                
                // Check if file is selected
                if (!csvFile) {
                    return;
                }
                
                // Check if campus is selected
                if (!csvCampus || !csvCampus.value) {
                    alert('Please select a campus first before uploading the file');
                    this.value = ''; // Clear file input
                    return;
                }
                
                // Check if class is selected
                if (!csvClass || !csvClass.value) {
                    alert('Please select a class first before uploading the file');
                    this.value = ''; // Clear file input
                    return;
                }
                
                // Check if file has data (size > 0)
                if (csvFile.size === 0) {
                    alert('The selected file is empty. Please select a file with data.');
                    this.value = ''; // Clear file input
                    return;
                }
                
                // Show loading state
                if (submitButton) {
                    submitButton.disabled = true;
                    const originalHTML = submitButton.innerHTML;
                    submitButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Processing...';
                }
                
                // Auto-submit the form
                form.submit();
            }
        });
    }
    
    if (form && submitButton) {
        // Handle form submission
        form.addEventListener('submit', function(e) {
            const isManual = inputMethodManual && inputMethodManual.checked;
            const isCsv = inputMethodCsv && inputMethodCsv.checked;
            
            // Validate based on mode
            if (isCsv) {
                const csvFile = document.getElementById('csv_file');
                const csvCampus = document.getElementById('csv_campus');
                const csvClass = document.getElementById('csv_class');
                
                if (!csvCampus || !csvCampus.value) {
                    e.preventDefault();
                    e.stopPropagation();
                    alert('Please select a campus');
                    return false;
                }
                
                if (!csvClass || !csvClass.value) {
                    e.preventDefault();
                    e.stopPropagation();
                    alert('Please select a class');
                    return false;
                }
                
                if (!csvFile || !csvFile.files || csvFile.files.length === 0) {
                    e.preventDefault();
                    e.stopPropagation();
                    alert('Please select a CSV/Excel file to upload');
                    return false;
                }
            }
            
            // Show loading state
            submitButton.disabled = true;
            const originalHTML = submitButton.innerHTML;
            submitButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Processing...';
            
            // Re-enable button after 60 seconds in case of error
            setTimeout(function() {
                if (submitButton.disabled) {
                    submitButton.disabled = false;
                    submitButton.innerHTML = originalHTML;
                }
            }, 60000);
            
            // Allow form to submit
            return true;
        });
    }
});
</script>
@endsection
