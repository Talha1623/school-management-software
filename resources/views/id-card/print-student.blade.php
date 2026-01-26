@extends('layouts.app')

@section('title', 'Print Student Card')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-3 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0 fs-16 fw-semibold">Print Student Card</h4>
            </div>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <!-- Filter Form -->
            <div class="card border-0 shadow-sm" style="border-radius: 8px; overflow: hidden;">
                <div class="card-body p-4">
                    <form action="#" method="POST" id="studentCardForm">
                        @csrf
                        <div class="row g-3 align-items-end">
                            <!-- Campus -->
                            <div class="col-md-3">
                                <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">
                                    Campus
                                </label>
                                <select class="form-select form-select-sm" name="campus" id="campus">
                                    <option value="">All Campuses</option>
                                    @foreach($campuses as $campus)
                                        <option value="{{ $campus->campus_name ?? $campus }}" {{ request('campus') == ($campus->campus_name ?? $campus) ? 'selected' : '' }}>
                                            {{ $campus->campus_name ?? $campus }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Type -->
                            <div class="col-md-3">
                                <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">
                                    Type
                                </label>
                                <select class="form-select form-select-sm" name="type" id="type">
                                    <option value="">All Types</option>
                                    @foreach($types as $type)
                                        <option value="{{ $type }}" {{ request('type') == $type ? 'selected' : '' }}>{{ $type }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Class -->
                            <div class="col-md-2">
                                <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">
                                    Class
                                </label>
                                <select class="form-select form-select-sm" name="class" id="class">
                                    <option value="">All Classes</option>
                                    @foreach($classes as $class)
                                        <option value="{{ $class }}" {{ request('class') == $class ? 'selected' : '' }}>{{ $class }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Section -->
                            <div class="col-md-2">
                                <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">
                                    Section
                                </label>
                                <select class="form-select form-select-sm" name="section" id="section">
                                    <option value="">All Sections</option>
                                    @foreach($sections as $section)
                                        <option value="{{ $section }}" {{ request('section') == $section ? 'selected' : '' }}>{{ $section }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- For Session -->
                            <div class="col-md-2">
                                <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">
                                    For Session
                                </label>
                                <select class="form-select form-select-sm" name="session" id="session">
                                    <option value="">All Sessions</option>
                                    @foreach($sessions as $session)
                                        <option value="{{ $session }}" {{ request('session') == $session ? 'selected' : '' }}>{{ $session }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Filter Data Button -->
                            <div class="col-md-3">
                                <button type="submit" class="btn btn-primary w-100 filter-btn">
                                    <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">filter_list</span>
                                    <span style="font-size: 12px;">Filter Data</span>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .rounded-8 {
        border-radius: 8px;
    }
    
    /* Form Styling */
    .form-select-sm {
        font-size: 13px;
        padding: 6px 12px;
        border-radius: 6px;
        border: 1px solid #dee2e6;
        transition: all 0.3s ease;
        height: 32px;
    }
    
    .form-select-sm:focus {
        border-color: #003471;
        box-shadow: 0 0 0 3px rgba(0, 52, 113, 0.15);
        outline: none;
    }
    
    .form-label {
        margin-bottom: 0.4rem;
        line-height: 1.3;
    }

    /* Filter Button Styling */
    .filter-btn {
        background: linear-gradient(135deg, #003471 0%, #004a9f 100%);
        color: white;
        border: none;
        font-weight: 500;
        padding: 4px 12px;
        font-size: 12px;
        height: 32px;
        line-height: 1.4;
        transition: all 0.3s ease;
        box-shadow: 0 2px 6px rgba(0, 52, 113, 0.2);
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 4px;
    }
    
    .filter-btn:hover {
        background: linear-gradient(135deg, #004a9f 0%, #003471 100%);
        transform: translateY(-1px);
        box-shadow: 0 4px 10px rgba(0, 52, 113, 0.3);
        color: white;
    }
    
    .filter-btn:active {
        transform: translateY(0);
    }

    /* Card Styling */
    .card {
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }
</style>

<script>
function loadClassesByCampus(campusValue, selectedClass = '') {
    const classSelect = document.getElementById('class');
    const sectionSelect = document.getElementById('section');
    if (!classSelect) return;

    classSelect.innerHTML = '<option value="">Loading...</option>';
    classSelect.disabled = true;
    if (sectionSelect) {
        sectionSelect.innerHTML = '<option value="">All Sections</option>';
    }

    const params = new URLSearchParams();
    if (campusValue) {
        params.append('campus', campusValue);
    }

    fetch(`{{ route('student.information.classes-by-campus') }}?${params.toString()}`, {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        const classes = Array.isArray(data.classes) ? data.classes : [];
        classSelect.innerHTML = '<option value="">All Classes</option>';
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

// Function to load sections for a class
function loadSectionsForClass(classValue, campusValue = '') {
    const sectionSelect = document.getElementById('section');
    const currentSection = sectionSelect.value; // Preserve current selection
    
    // Clear existing options except "All Sections"
    sectionSelect.innerHTML = '<option value="">All Sections</option>';
    
    if (classValue) {
        // Fetch sections for selected class
        const params = new URLSearchParams();
        params.append('class', classValue);
        if (campusValue) {
            params.append('campus', campusValue);
        }
        fetch(`{{ route('student.information.sections-by-class') }}?${params.toString()}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.sections && data.sections.length > 0) {
                data.sections.forEach(section => {
                    const option = document.createElement('option');
                    option.value = section;
                    option.textContent = section;
                    if (section === currentSection) {
                        option.selected = true;
                    }
                    sectionSelect.appendChild(option);
                });
            }
        })
        .catch(error => {
            console.error('Error loading sections:', error);
        });
    } else {
        // If no class selected, load all sections
        const params = new URLSearchParams();
        if (campusValue) {
            params.append('campus', campusValue);
        }
        fetch(`{{ route('student.information.sections-by-class') }}?${params.toString()}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.sections && data.sections.length > 0) {
                data.sections.forEach(section => {
                    const option = document.createElement('option');
                    option.value = section;
                    option.textContent = section;
                    if (section === currentSection) {
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
}

// Load sections when class changes
document.getElementById('class').addEventListener('change', function() {
    const campusValue = document.getElementById('campus')?.value || '';
    loadSectionsForClass(this.value, campusValue);
});

// Load classes/sections on page load
document.addEventListener('DOMContentLoaded', function() {
    const campusSelect = document.getElementById('campus');
    const classSelect = document.getElementById('class');
    const selectedCampus = campusSelect ? campusSelect.value : '';
    const selectedClass = classSelect ? classSelect.value : '';

    loadClassesByCampus(selectedCampus, selectedClass);
    if (selectedClass) {
        loadSectionsForClass(selectedClass, selectedCampus);
    }

    if (campusSelect) {
        campusSelect.addEventListener('change', function() {
            loadClassesByCampus(this.value);
        });
    }
});

// Form submission - open print view in new tab
document.getElementById('studentCardForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const params = new URLSearchParams();
    
    // Add form values to params (only filter values)
    for (const [key, value] of formData.entries()) {
        if (value && key !== '_token') { // Skip CSRF token
            params.append(key, value);
        }
    }
    
    // Open print view in new tab with filter parameters
    const printUrl = `{{ route('id-card.print-student.print') }}?${params.toString()}`;
    window.open(printUrl, '_blank');
});

</script>
@endsection

