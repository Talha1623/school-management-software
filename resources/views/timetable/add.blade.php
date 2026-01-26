@extends('layouts.app')

@section('title', 'Add Timetable')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-3">
            <h3 class="mb-3 fw-semibold" style="color: #003471;">Timetable</h3>
            
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

            <div id="ajax-alert-container"></div>
            
            <form action="{{ route('timetable.store') }}" method="POST" id="timetable-form">
                @csrf
                
                <!-- First Row: Campus, Class, Section -->
                <div class="row mb-2">
                    <div class="col-md-4">
                        <div class="card bg-light border-0 rounded-10 p-2 mb-2">
                            <h5 class="mb-1 py-2 px-3 text-white rounded-3 fw-semibold fs-15" style="margin: -8px -8px 8px -8px; background-color: #003471;">Campus</h5>
                            
                            <div class="mb-1">
                                <label for="campus" class="form-label mb-0 fs-13 fw-medium">Campus <span class="text-danger">*</span></label>
                                <select class="form-select form-select-sm py-1" id="campus" name="campus" required style="height: 32px;">
                                    <option value="">Select Campus</option>
                                    @foreach($campuses as $campus)
                                        <option value="{{ $campus->campus_name ?? $campus }}">{{ $campus->campus_name ?? $campus }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card bg-light border-0 rounded-10 p-2 mb-2">
                            <h5 class="mb-1 py-2 px-3 text-white rounded-3 fw-semibold fs-15" style="margin: -8px -8px 8px -8px; background-color: #003471;">Class</h5>
                            
                            <div class="mb-1">
                                <label for="class" class="form-label mb-0 fs-13 fw-medium">Class <span class="text-danger">*</span></label>
                                <select class="form-select form-select-sm py-1" id="class" name="class" required style="height: 32px;">
                                    <option value="">Select Class</option>
                                    @foreach($classes as $classItem)
                                        <option value="{{ $classItem->class_name ?? $classItem }}" data-campus="{{ $classItem->campus ?? '' }}">{{ $classItem->class_name ?? $classItem }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card bg-light border-0 rounded-10 p-2 mb-2">
                            <h5 class="mb-1 py-2 px-3 text-white rounded-3 fw-semibold fs-15" style="margin: -8px -8px 8px -8px; background-color: #003471;">Section</h5>
                            
                            <div class="mb-1">
                                <label for="section" class="form-label mb-0 fs-13 fw-medium">Section <span class="text-danger">*</span></label>
                                <select class="form-select form-select-sm py-1" id="section" name="section" required style="height: 32px;" disabled>
                                    <option value="">Select Class First</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    </div>
                </div>
                
                <!-- Second Row: Subject, Day -->
                <div class="row mb-2">
                    <div class="col-md-4">
                        <div class="card bg-light border-0 rounded-10 p-2 mb-2">
                            <h5 class="mb-1 py-2 px-3 text-white rounded-3 fw-semibold fs-15" style="margin: -8px -8px 8px -8px; background-color: #003471;">Subject</h5>
                            
                            @php
                                $staticSubjects = [
                                    '[Assembly]',
                                    '[Lunch Break]',
                                    '[Free Time]',
                                    '[Lab Active]',
                                    '[physicial/sports/activity]',
                                    '[singing class]',
                                    '[material arts class]',
                                    '[Library Activity]',
                                    '[chilligraphy class]',
                                    '[other fun activities]',
                                ];
                                $allSubjects = collect($subjects ?? [])->merge($staticSubjects)->unique()->values();
                            @endphp
                            <div class="mb-1">
                                <label for="subject" class="form-label mb-0 fs-13 fw-medium">Subject <span class="text-danger">*</span></label>
                                <select class="form-select form-select-sm py-1" id="subject" name="subject" required style="height: 32px;">
                                    <option value="">Select Subject</option>
                                    @foreach($allSubjects as $subject)
                                        <option value="{{ $subject }}">{{ $subject }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card bg-light border-0 rounded-10 p-2 mb-2">
                            <h5 class="mb-1 py-2 px-3 text-white rounded-3 fw-semibold fs-15" style="margin: -8px -8px 8px -8px; background-color: #003471;">Day</h5>
                            
                            <div class="mb-1">
                                <label for="day" class="form-label mb-0 fs-13 fw-medium">Day <span class="text-danger">*</span></label>
                                <select class="form-select form-select-sm py-1" id="day" name="day" required style="height: 32px;">
                                    <option value="">Select Day</option>
                                    @foreach($days as $day)
                                        <option value="{{ $day }}">{{ $day }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Third Row: Starting Time, Ending Time -->
                <div class="row mb-2">
                    <div class="col-md-6">
                        <div class="card bg-light border-0 rounded-10 p-2 mb-2">
                            <h5 class="mb-1 py-2 px-3 text-white rounded-3 fw-semibold fs-15" style="margin: -8px -8px 8px -8px; background-color: #003471;">Starting Time</h5>
                            
                            <div class="mb-1">
                                <label for="starting_time" class="form-label mb-0 fs-13 fw-medium">Starting Time <span class="text-danger">*</span></label>
                                <input type="time" class="form-control form-control-sm py-1" id="starting_time" name="starting_time" required style="height: 32px;">
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card bg-light border-0 rounded-10 p-2 mb-2">
                            <h5 class="mb-1 py-2 px-3 text-white rounded-3 fw-semibold fs-15" style="margin: -8px -8px 8px -8px; background-color: #003471;">Ending Time</h5>
                            
                            <div class="mb-1">
                                <label for="ending_time" class="form-label mb-0 fs-13 fw-medium">Ending Time <span class="text-danger">*</span></label>
                                <input type="time" class="form-control form-control-sm py-1" id="ending_time" name="ending_time" required style="height: 32px;">
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Submit Button -->
                <div class="row mb-2">
                    <div class="col-12">
                        <div class="d-flex justify-content-end gap-2">
                            <button type="reset" class="btn btn-sm btn-secondary px-4 py-2">
                                <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">refresh</span>
                                Reset
                            </button>
                            <button type="submit" class="btn btn-sm px-4 py-2" style="background: linear-gradient(135deg, #003471 0%, #004a9f 100%); color: white; border: none;">
                                <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">save</span>
                                Save Timetable
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    .card {
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        transition: all 0.3s ease;
    }
    
    .card:hover {
        box-shadow: 0 4px 8px rgba(0,0,0,0.15);
    }
    
    .form-control:focus,
    .form-select:focus {
        border-color: #003471;
        box-shadow: 0 0 0 0.2rem rgba(0, 52, 113, 0.25);
    }
    
    .form-label {
        color: #495057;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const campusSelect = document.getElementById('campus');
    const classSelect = document.getElementById('class');
    const sectionSelect = document.getElementById('section');
    const form = document.getElementById('timetable-form');
    const alertContainer = document.getElementById('ajax-alert-container');

    function filterClassOptions(campusValue) {
        if (!classSelect) return;
        const campusLower = (campusValue || '').toLowerCase().trim();
        Array.from(classSelect.options).forEach((option, index) => {
            if (index === 0) {
                option.hidden = false;
                option.disabled = false;
                return;
            }
            const optionCampus = (option.dataset.campus || '').toLowerCase().trim();
            const shouldShow = !campusLower || optionCampus === campusLower;
            option.hidden = !shouldShow;
            option.disabled = !shouldShow;
        });
        if (!classSelect.value || classSelect.selectedOptions[0]?.disabled) {
            classSelect.value = '';
        }
    }
    
    // Function to load sections based on selected class
    function loadSections(className) {
        if (!className) {
            sectionSelect.innerHTML = '<option value="">Select Class First</option>';
            sectionSelect.disabled = true;
            return;
        }
        
        // Show loading state
        sectionSelect.innerHTML = '<option value="">Loading...</option>';
        sectionSelect.disabled = true;
        
        // Make AJAX request
        const params = new URLSearchParams();
        params.append('class', className);
        if (campusSelect && campusSelect.value) {
            params.append('campus', campusSelect.value);
        }
        fetch(`{{ route('timetable.get-sections-by-class') }}?${params.toString()}`, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            sectionSelect.innerHTML = '<option value="">Select Section</option>';
            
            if (data.sections && data.sections.length > 0) {
                data.sections.forEach(section => {
                    const option = document.createElement('option');
                    option.value = section;
                    option.textContent = section;
                    sectionSelect.appendChild(option);
                });
                sectionSelect.disabled = false;
            } else {
                sectionSelect.innerHTML = '<option value="">No sections found</option>';
                sectionSelect.disabled = true;
            }
        })
        .catch(error => {
            console.error('Error loading sections:', error);
            sectionSelect.innerHTML = '<option value="">Error loading sections</option>';
            sectionSelect.disabled = true;
        });
    }
    
    // Listen for class selection changes
    if (classSelect) {
        classSelect.addEventListener('change', function() {
            loadSections(this.value);
            // Clear section when class changes
            sectionSelect.value = '';
        });
    }

    if (campusSelect) {
        campusSelect.addEventListener('change', function() {
            filterClassOptions(this.value);
            loadSections('');
            sectionSelect.value = '';
        });
    }
    
    // Load sections on page load if class is already selected (for form validation errors)
    @if(old('class'))
        loadSections('{{ old('class') }}');
        filterClassOptions(document.getElementById('campus')?.value || '');
        // Set the selected section if it was previously selected
        @if(old('section'))
            setTimeout(() => {
                sectionSelect.value = '{{ old('section') }}';
            }, 500);
        @endif
    @endif

    filterClassOptions(document.getElementById('campus')?.value || '');

    if (form) {
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalSubmitHtml = submitBtn ? submitBtn.innerHTML : '';

        form.addEventListener('submit', function(event) {
            event.preventDefault();

            if (alertContainer) {
                alertContainer.innerHTML = '';
            }

            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> Saving...';
            }

            const formData = new FormData(form);

            fetch(form.action, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: formData
            })
            .then(async (response) => {
                const data = await response.json().catch(() => null);
                if (!response.ok) {
                    const error = new Error('Request failed');
                    error.status = response.status;
                    error.data = data;
                    throw error;
                }
                return data;
            })
            .then((data) => {
                if (alertContainer) {
                    alertContainer.innerHTML = `
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            ${data?.message || 'Timetable created successfully!'}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    `;
                }

                // Keep current selections after save
            })
            .catch((error) => {
                let message = 'Something went wrong. Please try again.';
                if (error.status === 422 && error.data?.errors) {
                    const errors = Object.values(error.data.errors).flat();
                    message = `<ul class="mb-0">${errors.map(err => `<li>${err}</li>`).join('')}</ul>`;
                }
                if (alertContainer) {
                    alertContainer.innerHTML = `
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            ${message}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    `;
                }
            })
            .finally(() => {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalSubmitHtml;
                }
            });
        });
    }
});
</script>
@endsection
