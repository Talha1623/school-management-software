@extends('layouts.app')

@section('title', 'Monthly Passout Students Report - Filter')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card border-0 shadow-sm" style="border-radius: 8px; overflow: hidden;">
            <!-- Header -->
            <div class="p-3" style="background: linear-gradient(135deg, #003471 0%, #002855 100%);">
                <h5 class="mb-0 fw-semibold text-white" style="font-size: 16px;">
                    <span class="material-symbols-outlined" style="font-size: 20px; vertical-align: middle;">filter_list</span>
                    Monthly Passout Students Report - Filter
                </h5>
            </div>
            
            <!-- Content Area -->
            <div class="card-body p-3">
                <form id="filterForm" method="GET" action="{{ route('student.info-report.print') }}" onsubmit="return openPrintFromForm(event)">
                    <input type="hidden" name="type" value="monthly-passout">
                    
                    <!-- Compact Single Row Layout -->
                    <div class="row g-2 align-items-end">
                        <!-- Campus Filter -->
                        <div class="col-md-2">
                            <label for="filter_campus" class="form-label mb-1 fw-semibold" style="color: #003471; font-size: 12px;">
                                <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">location_on</span>
                                Campus
                            </label>
                            <select class="form-select form-select-sm" id="filter_campus" name="filter_campus" style="border: 1px solid #e0e7ff;">
                                <option value="">All Campuses</option>
                                @foreach($campuses as $campus)
                                    <option value="{{ $campus->campus_name ?? $campus }}">{{ $campus->campus_name ?? $campus }}</option>
                                @endforeach
                            </select>
                        </div>
                        
                        <!-- Class Filter -->
                        <div class="col-md-2">
                            <label for="filter_class" class="form-label mb-1 fw-semibold" style="color: #003471; font-size: 12px;">
                                <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">class</span>
                                Class
                            </label>
                            <select class="form-select form-select-sm" id="filter_class" name="filter_class" style="border: 1px solid #e0e7ff;">
                                <option value="">All Classes</option>
                            </select>
                        </div>
                        
                        <!-- Section Filter -->
                        <div class="col-md-2">
                            <label for="filter_section" class="form-label mb-1 fw-semibold" style="color: #003471; font-size: 12px;">
                                <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">groups</span>
                                Section
                            </label>
                            <select class="form-select form-select-sm" id="filter_section" name="filter_section" style="border: 1px solid #e0e7ff;">
                                <option value="">All Sections</option>
                            </select>
                        </div>
                        
                        <!-- Action Buttons -->
                        <div class="col-md-6">
                            <div class="d-flex gap-2 justify-content-end">
                                <button type="button" class="btn btn-sm btn-secondary" onclick="window.history.back()">
                                    <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">arrow_back</span>
                                    Back
                                </button>
                                <button type="button" class="btn btn-sm btn-light" onclick="resetFilters()">
                                    <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">refresh</span>
                                    Reset
                                </button>
                                <button type="submit" class="btn btn-sm text-white" style="background: linear-gradient(135deg, #003471 0%, #002855 100%);">
                                    <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">print</span>
                                    Print Report
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
    .form-select:focus {
        border-color: #003471;
        box-shadow: 0 0 0 0.2rem rgba(0, 52, 113, 0.25);
    }
    
    .btn:hover {
        transform: translateY(-1px);
        transition: all 0.3s ease;
    }
</style>

<script>
function openPrintFromForm(event) {
    event.preventDefault();
    const form = document.getElementById('filterForm');
    if (!form) return true;

    const params = new URLSearchParams(new FormData(form));
    const url = `${form.action}?${params.toString()}`;
    const w = window.open(url, '_blank');
    if (!w) {
        window.location.href = url;
    }
    return false;
}

document.addEventListener('DOMContentLoaded', function() {
    const campusSelect = document.getElementById('filter_campus');
    const classSelect = document.getElementById('filter_class');
    const sectionSelect = document.getElementById('filter_section');
    
    // Load all classes initially
    loadAllClasses();
    
    // Load classes when campus changes
    if (campusSelect) {
        campusSelect.addEventListener('change', function() {
            const campus = this.value;
            if (campus) {
                loadClasses(campus);
            } else {
                loadAllClasses();
            }
            // Reset section when campus changes
            sectionSelect.innerHTML = '<option value="">All Sections</option>';
            sectionSelect.disabled = false;
        });
    }
    
    // Load sections when class changes
    if (classSelect) {
        classSelect.addEventListener('change', function() {
            const className = this.value;
            const selectedCampus = campusSelect ? campusSelect.value : '';
            if (className) {
                loadSections(className, selectedCampus);
            } else {
                sectionSelect.innerHTML = '<option value="">All Sections</option>';
                sectionSelect.disabled = false;
            }
        });
    }
    
    function loadAllClasses() {
        classSelect.innerHTML = '<option value="">Loading...</option>';
        classSelect.disabled = true;
        
        fetch(`{{ route('student.info-report.get-classes') }}`)
            .then(response => response.json())
            .then(data => {
                classSelect.innerHTML = '<option value="">All Classes</option>';
                if (data.classes && data.classes.length > 0) {
                    data.classes.forEach(className => {
                        const option = document.createElement('option');
                        option.value = className;
                        option.textContent = className;
                        classSelect.appendChild(option);
                    });
                }
                classSelect.disabled = false;
            })
            .catch(error => {
                console.error('Error loading classes:', error);
                classSelect.innerHTML = '<option value="">All Classes</option>';
                classSelect.disabled = false;
            });
    }
    
    function loadClasses(campus) {
        if (!campus) {
            loadAllClasses();
            return;
        }
        
        classSelect.innerHTML = '<option value="">Loading...</option>';
        classSelect.disabled = true;
        
        const params = new URLSearchParams();
        params.append('campus', campus);
        
        fetch(`{{ route('student.info-report.get-classes') }}?${params.toString()}`)
            .then(response => response.json())
            .then(data => {
                classSelect.innerHTML = '<option value="">All Classes</option>';
                if (data.classes && data.classes.length > 0) {
                    data.classes.forEach(className => {
                        const option = document.createElement('option');
                        option.value = className;
                        option.textContent = className;
                        classSelect.appendChild(option);
                    });
                }
                classSelect.disabled = false;
                
                // Reset section dropdown when classes change
                sectionSelect.innerHTML = '<option value="">All Sections</option>';
                sectionSelect.disabled = false;
            })
            .catch(error => {
                console.error('Error loading classes:', error);
                classSelect.innerHTML = '<option value="">All Classes</option>';
                classSelect.disabled = false;
            });
    }
    
    function loadSections(className, campus) {
        if (!className) {
            sectionSelect.innerHTML = '<option value="">All Sections</option>';
            sectionSelect.disabled = false;
            return;
        }
        
        sectionSelect.innerHTML = '<option value="">Loading...</option>';
        sectionSelect.disabled = true;
        
        const params = new URLSearchParams();
        params.append('class', className);
        if (campus) {
            params.append('campus', campus);
        }
        
        fetch(`{{ route('student.info-report.get-sections') }}?${params.toString()}`)
            .then(response => response.json())
            .then(data => {
                sectionSelect.innerHTML = '<option value="">All Sections</option>';
                if (data.sections && data.sections.length > 0) {
                    data.sections.forEach(sectionName => {
                        const option = document.createElement('option');
                        option.value = sectionName;
                        option.textContent = sectionName;
                        sectionSelect.appendChild(option);
                    });
                }
                sectionSelect.disabled = false;
            })
            .catch(error => {
                console.error('Error loading sections:', error);
                sectionSelect.innerHTML = '<option value="">All Sections</option>';
                sectionSelect.disabled = false;
            });
    }
});

function resetFilters() {
    document.getElementById('filter_campus').value = '';
    document.getElementById('filter_class').value = '';
    document.getElementById('filter_section').value = '';
    
    // Reload all classes
    const classSelect = document.getElementById('filter_class');
    classSelect.innerHTML = '<option value="">Loading...</option>';
    classSelect.disabled = true;
    
    fetch(`{{ route('student.info-report.get-classes') }}`)
        .then(response => response.json())
        .then(data => {
            classSelect.innerHTML = '<option value="">All Classes</option>';
            if (data.classes && data.classes.length > 0) {
                data.classes.forEach(className => {
                    const option = document.createElement('option');
                    option.value = className;
                    option.textContent = className;
                    classSelect.appendChild(option);
                });
            }
            classSelect.disabled = false;
        })
        .catch(error => {
            console.error('Error loading classes:', error);
            classSelect.innerHTML = '<option value="">All Classes</option>';
            classSelect.disabled = false;
        });
    
    // Reset section dropdown
    const sectionSelect = document.getElementById('filter_section');
    sectionSelect.innerHTML = '<option value="">All Sections</option>';
    sectionSelect.disabled = false;
}
</script>
@endsection
