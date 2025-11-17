@extends('layouts.app')

@section('title', 'Edit Timetable')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-3">
            <h3 class="mb-3 fw-semibold" style="color: #003471;">Edit Timetable</h3>
            
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
            
            <form action="{{ route('timetable.update', $timetable) }}" method="POST" id="timetable-form">
                @csrf
                @method('PUT')
                
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
                                        <option value="{{ $campus->campus_name ?? $campus }}" {{ ($timetable->campus == ($campus->campus_name ?? $campus)) ? 'selected' : '' }}>{{ $campus->campus_name ?? $campus }}</option>
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
                                        <option value="{{ $classItem->class_name ?? $classItem }}" {{ ($timetable->class == ($classItem->class_name ?? $classItem)) ? 'selected' : '' }}>{{ $classItem->class_name ?? $classItem }}</option>
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
                                <select class="form-select form-select-sm py-1" id="section" name="section" required style="height: 32px;">
                                    <option value="">Select Section</option>
                                    @foreach($sections as $section)
                                        <option value="{{ $section }}" {{ ($timetable->section == $section) ? 'selected' : '' }}>{{ $section }}</option>
                                    @endforeach
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
                            
                            <div class="mb-1">
                                <label for="subject" class="form-label mb-0 fs-13 fw-medium">Subject <span class="text-danger">*</span></label>
                                <select class="form-select form-select-sm py-1" id="subject" name="subject" required style="height: 32px;">
                                    <option value="">Select Subject</option>
                                    @foreach($subjects as $subject)
                                        <option value="{{ $subject }}" {{ ($timetable->subject == $subject) ? 'selected' : '' }}>{{ $subject }}</option>
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
                                        <option value="{{ $day }}" {{ ($timetable->day == $day) ? 'selected' : '' }}>{{ $day }}</option>
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
                                <input type="time" class="form-control form-control-sm py-1" id="starting_time" name="starting_time" value="{{ date('H:i', strtotime($timetable->starting_time)) }}" required style="height: 32px;">
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card bg-light border-0 rounded-10 p-2 mb-2">
                            <h5 class="mb-1 py-2 px-3 text-white rounded-3 fw-semibold fs-15" style="margin: -8px -8px 8px -8px; background-color: #003471;">Ending Time</h5>
                            
                            <div class="mb-1">
                                <label for="ending_time" class="form-label mb-0 fs-13 fw-medium">Ending Time <span class="text-danger">*</span></label>
                                <input type="time" class="form-control form-control-sm py-1" id="ending_time" name="ending_time" value="{{ date('H:i', strtotime($timetable->ending_time)) }}" required style="height: 32px;">
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Submit Button -->
                <div class="row mb-2">
                    <div class="col-12">
                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('timetable.manage') }}" class="btn btn-sm btn-secondary px-4 py-2">
                                <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">cancel</span>
                                Cancel
                            </a>
                            <button type="submit" class="btn btn-sm px-4 py-2" style="background: linear-gradient(135deg, #003471 0%, #004a9f 100%); color: white; border: none;">
                                <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">save</span>
                                Update Timetable
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
    const classSelect = document.getElementById('class');
    const sectionSelect = document.getElementById('section');
    
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
        fetch(`{{ route('timetable.get-sections-by-class') }}?class=${encodeURIComponent(className)}`, {
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
                    // Select the current section if it matches
                    if (section === '{{ $timetable->section }}') {
                        option.selected = true;
                    }
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
            // Clear section when class changes (unless it's the initial load)
            if (this.value !== '{{ $timetable->class }}') {
                sectionSelect.value = '';
            }
        });
    }
    
    // Load sections on page load if class is already selected
    @if($timetable->class)
        loadSections('{{ $timetable->class }}');
    @endif
});
</script>
@endsection

