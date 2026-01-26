@extends('layouts.app')

@section('title', 'Student Certification')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-3 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0 fs-16 fw-semibold">Student Certification</h4>
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
            <form action="{{ route('certification.student') }}" method="GET" id="filterForm">
                <div class="row g-2 mb-3 align-items-end">
                    <!-- Campus -->
                    <div class="col-md-3">
                        <label for="filter_campus" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Campus</label>
                        <select class="form-select form-select-sm" id="filter_campus" name="filter_campus" style="height: 32px;">
                            <option value="">All Campuses</option>
                            @foreach($campuses as $campus)
                                @php
                                    $campusName = is_object($campus) ? ($campus->campus_name ?? $campus->name ?? '') : $campus;
                                    $campusValue = $campusName;
                                @endphp
                                <option value="{{ $campusValue }}" {{ $filterCampus == $campusValue ? 'selected' : '' }}>{{ $campusName }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Class -->
                    <div class="col-md-3">
                        <label for="filter_class" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Class</label>
                        <select class="form-select form-select-sm" id="filter_class" name="filter_class" style="height: 32px;">
                            <option value="">All Classes</option>
                            @foreach(($filterClasses ?? $classes) as $className)
                                <option value="{{ $className }}" {{ $filterClass == $className ? 'selected' : '' }}>{{ $className }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Section -->
                    <div class="col-md-3">
                        <label for="filter_section" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Section</label>
                        <select class="form-select form-select-sm" id="filter_section" name="filter_section" style="height: 32px;" disabled>
                            <option value="">All Sections</option>
                            @foreach($sections as $sectionName)
                                <option value="{{ $sectionName }}" {{ $filterSection == $sectionName ? 'selected' : '' }}>{{ $sectionName }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Certificate Type -->
                    <div class="col-md-3">
                        <label for="filter_certificate_type" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Certificate Type</label>
                        <select class="form-select form-select-sm" id="filter_certificate_type" name="filter_certificate_type" style="height: 32px;">
                            <option value="">All Types</option>
                            <option value="Character Certificate" {{ $filterCertificateType == 'Character Certificate' ? 'selected' : '' }}>Character Certificate</option>
                            <option value="School Leaving Certificate" {{ $filterCertificateType == 'School Leaving Certificate' ? 'selected' : '' }}>School Leaving Certificate</option>
                            <option value="Date of Birth Certificate" {{ $filterCertificateType == 'Date of Birth Certificate' ? 'selected' : '' }}>Date of Birth Certificate</option>
                            <option value="Provisional Certificate" {{ $filterCertificateType == 'Provisional Certificate' ? 'selected' : '' }}>Provisional Certificate</option>
                        </select>
                    </div>
                </div>

                <!-- Filter Button -->
                <div class="row">
                    <div class="col-md-12 text-end">
                        <button type="submit" class="btn btn-sm py-1 px-3 rounded-8 filter-btn" style="height: 32px;">
                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">filter_alt</span>
                            <span style="font-size: 12px;">Filter</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

@if(isset($students) && $filterCertificateType)
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-3 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                <div class="d-flex align-items-center gap-3">
                    <h4 class="mb-0 fs-16 fw-semibold">Students List - {{ $filterCertificateType }}</h4>
                    <span class="badge bg-info">{{ $students->count() }} Student(s)</span>
                </div>
                
                <!-- Search -->
                <div class="d-flex align-items-center gap-2">
                    <label for="searchInput" class="mb-0 fs-13 fw-medium text-dark">Search:</label>
                    <div class="input-group input-group-sm search-input-group" style="width: 280px;">
                        <span class="input-group-text bg-light border-end-0" style="background-color: #f0f4ff !important; border-color: #e0e7ff; padding: 4px 8px;">
                            <span class="material-symbols-outlined" style="font-size: 14px; color: #003471;">search</span>
                        </span>
                        <input type="text" 
                               id="searchInput" 
                               class="form-control border-start-0 border-end-0" 
                               placeholder="Search by name, code..." 
                               value="{{ $search ?? '' }}" 
                               onkeypress="handleSearchKeyPress(event)" 
                               oninput="handleSearchInput(event)" 
                               style="padding: 4px 8px; font-size: 13px;">
                        @if(isset($search) && $search)
                            <button class="btn btn-outline-secondary border-start-0 border-end-0" 
                                    type="button" 
                                    onclick="clearSearch()" 
                                    title="Clear search" 
                                    style="padding: 4px 8px;">
                                <span class="material-symbols-outlined" style="font-size: 14px;">close</span>
                            </button>
                        @endif
                        <button class="btn btn-sm search-btn" 
                                type="button" 
                                onclick="performSearch()" 
                                title="Search" 
                                style="padding: 4px 10px;">
                            <span class="material-symbols-outlined" style="font-size: 14px;">search</span>
                        </button>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-sm table-hover">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Photo</th>
                            <th>Student Name</th>
                            <th>Student Code</th>
                            <th>Class</th>
                            <th>Section</th>
                            <th>Campus</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($students as $index => $student)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>
                                @if($student->photo)
                                    <img src="{{ asset('storage/' . $student->photo) }}" alt="Photo" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
                                @else
                                    <div style="width: 40px; height: 40px; border-radius: 50%; background-color: #e0e7ff; display: flex; align-items: center; justify-content: center;">
                                        <span class="material-symbols-outlined" style="font-size: 20px; color: #003471;">person</span>
                                    </div>
                                @endif
                            </td>
                            <td><strong>{{ $student->student_name }}</strong></td>
                            <td>{{ $student->student_code ?? 'N/A' }}</td>
                            <td>{{ $student->class ?? 'N/A' }}</td>
                            <td>{{ $student->section ?? 'N/A' }}</td>
                            <td>{{ $student->campus ?? 'N/A' }}</td>
                            <td class="text-end">
                                <a href="{{ route('certification.student.generate', ['student' => $student->id, 'type' => $filterCertificateType]) }}" 
                                   target="_blank"
                                   class="btn btn-sm btn-primary px-3 py-1" style="color: white !important;">
                                    <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle; color: white !important;">print</span>
                                    <span style="color: white !important;">Generate Certificate</span>
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@elseif(isset($students) && $students->isEmpty() && $filterCertificateType)
<div class="row">
    <div class="col-12">
        <div class="alert alert-info">
            <span class="material-symbols-outlined" style="vertical-align: middle;">info</span>
            No students found for the selected filters.
        </div>
    </div>
</div>
@endif

<style>
.filter-btn {
    background: linear-gradient(135deg, #003471 0%, #004a9f 100%);
    color: white;
    border: none;
    transition: all 0.3s ease;
}

.filter-btn:hover {
    background: linear-gradient(135deg, #004a9f 0%, #003471 100%);
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0, 52, 113, 0.3);
}

.search-input-group .form-control:focus {
    border-color: #e0e7ff;
    box-shadow: none;
}

.search-btn {
    background: linear-gradient(135deg, #003471 0%, #004a9f 100%);
    color: white;
    border: none;
    transition: all 0.3s ease;
}

.search-btn:hover {
    background: linear-gradient(135deg, #004a9f 0%, #003471 100%);
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0, 52, 113, 0.3);
}

.search-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const campusSelect = document.getElementById('filter_campus');
    const classSelect = document.getElementById('filter_class');
    const sectionSelect = document.getElementById('filter_section');

    // Load classes when campus changes
    campusSelect.addEventListener('change', function() {
        const selectedCampus = this.value;
        const previouslySelectedClass = classSelect.value;
        
        // Reset class and section
        classSelect.innerHTML = '<option value="">All Classes</option>';
        sectionSelect.innerHTML = '<option value="">All Sections</option>';
        sectionSelect.disabled = true;
        
        if (selectedCampus) {
            classSelect.disabled = true;
            classSelect.innerHTML = '<option value="">Loading...</option>';
            
            fetch(`{{ route('certification.student.get-classes') }}?campus=${encodeURIComponent(selectedCampus)}`)
                .then(response => response.json())
                .then(data => {
                    classSelect.innerHTML = '<option value="">All Classes</option>';
                    data.forEach(className => {
                        const selected = (className === previouslySelectedClass) ? 'selected' : '';
                        classSelect.innerHTML += `<option value="${className}" ${selected}>${className}</option>`;
                    });
                    classSelect.disabled = false;
                    
                    // If a class was previously selected and still exists, trigger section load
                    if (previouslySelectedClass && data.includes(previouslySelectedClass)) {
                        classSelect.dispatchEvent(new Event('change'));
                    }
                })
                .catch(error => {
                    console.error('Error loading classes:', error);
                    classSelect.innerHTML = '<option value="">Error loading classes</option>';
                    classSelect.disabled = false;
                });
        } else {
            classSelect.disabled = false;
            // Load all classes if no campus selected - fetch via AJAX
            fetch(`{{ route('certification.student.get-classes') }}`)
                .then(response => response.json())
                .then(data => {
                    classSelect.innerHTML = '<option value="">All Classes</option>';
                    data.forEach(className => {
                        const selected = (className === '{{ $filterClass }}') ? 'selected' : '';
                        classSelect.innerHTML += `<option value="${className}" ${selected}>${className}</option>`;
                    });
                })
                .catch(error => {
                    console.error('Error loading classes:', error);
                });
        }
    });

    // Load sections when class changes
    classSelect.addEventListener('change', function() {
        const selectedClass = this.value;
        const selectedCampus = campusSelect.value;
        const previouslySelectedSection = sectionSelect.value;
        
        sectionSelect.innerHTML = '<option value="">All Sections</option>';
        
        if (selectedClass) {
            sectionSelect.disabled = true;
            sectionSelect.innerHTML = '<option value="">Loading...</option>';
            
            let url = `{{ route('certification.student.get-sections') }}?class=${encodeURIComponent(selectedClass)}`;
            if (selectedCampus) {
                url += `&campus=${encodeURIComponent(selectedCampus)}`;
            }
            
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    sectionSelect.innerHTML = '<option value="">All Sections</option>';
                    data.forEach(section => {
                        const selected = (section === previouslySelectedSection) ? 'selected' : '';
                        sectionSelect.innerHTML += `<option value="${section}" ${selected}>${section}</option>`;
                    });
                    sectionSelect.disabled = false;
                })
                .catch(error => {
                    console.error('Error loading sections:', error);
                    sectionSelect.innerHTML = '<option value="">Error loading sections</option>';
                    sectionSelect.disabled = false;
                });
        } else {
            sectionSelect.disabled = true;
        }
    });

    // Initialize section select state
    if (!classSelect.value) {
        sectionSelect.disabled = true;
    }

    // If campus is pre-selected, load classes
    if (campusSelect.value) {
        campusSelect.dispatchEvent(new Event('change'));
        
        // If class is also pre-selected, wait a bit then load sections
        if (classSelect.value) {
            setTimeout(() => {
                classSelect.dispatchEvent(new Event('change'));
            }, 500);
        }
    } else if (classSelect.value) {
        // If only class is pre-selected (no campus filter), load sections
        classSelect.dispatchEvent(new Event('change'));
    }
});

// Search functionality
function performSearch() {
    const searchInput = document.getElementById('searchInput');
    if (!searchInput) return;
    
    const searchValue = searchInput.value.trim();
    const url = new URL(window.location.href);
    
    if (searchValue) {
        url.searchParams.set('search', searchValue);
    } else {
        url.searchParams.delete('search');
    }
    
    // Preserve filter values
    const filterCampus = '{{ $filterCampus ?? '' }}';
    const filterClass = '{{ $filterClass ?? '' }}';
    const filterSection = '{{ $filterSection ?? '' }}';
    const filterCertificateType = '{{ $filterCertificateType ?? '' }}';
    
    if (filterCampus) url.searchParams.set('filter_campus', filterCampus);
    if (filterClass) url.searchParams.set('filter_class', filterClass);
    if (filterSection) url.searchParams.set('filter_section', filterSection);
    if (filterCertificateType) url.searchParams.set('filter_certificate_type', filterCertificateType);
    
    // Show loading state
    searchInput.disabled = true;
    const searchBtn = document.querySelector('.search-btn');
    if (searchBtn) {
        searchBtn.disabled = true;
        searchBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span>';
    }
    
    window.location.href = url.toString();
}

function handleSearchKeyPress(event) {
    if (event.key === 'Enter') {
        event.preventDefault();
        performSearch();
    }
}

function handleSearchInput(event) {
    // Auto-clear if input is empty (optional - you can remove this if you want)
    // This is just for better UX
}

function clearSearch() {
    const url = new URL(window.location.href);
    url.searchParams.delete('search');
    
    // Preserve filter values
    const filterCampus = '{{ $filterCampus ?? '' }}';
    const filterClass = '{{ $filterClass ?? '' }}';
    const filterSection = '{{ $filterSection ?? '' }}';
    const filterCertificateType = '{{ $filterCertificateType ?? '' }}';
    
    if (filterCampus) url.searchParams.set('filter_campus', filterCampus);
    if (filterClass) url.searchParams.set('filter_class', filterClass);
    if (filterSection) url.searchParams.set('filter_section', filterSection);
    if (filterCertificateType) url.searchParams.set('filter_certificate_type', filterCertificateType);
    
    window.location.href = url.toString();
}
</script>
@endsection
