@extends('layouts.app')

@section('title', 'Reporting and Analysis')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-3 mb-4">
            <h4 class="mb-3 fs-16 fw-semibold" style="color: #003471;">Reporting and Analysis</h4>
            
            <!-- Filter Form -->
            <form action="{{ route('student-behavior.reporting-analysis.report') }}" method="GET" target="_blank">
                <div class="row g-2 align-items-end">
                    <div class="col-xl-2 col-lg-2 col-md-4 col-sm-6">
                        <label for="filter_campus" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Campus *</label>
                        <select id="filter_campus" name="campus" class="form-select form-select-sm" required style="height: 32px; border-radius: 6px; border: 1px solid #dee2e6; font-size: 12px;">
                            <option value="">Select Campus</option>
                            @foreach($campuses as $campus)
                                @php
                                    $campusName = is_object($campus) ? ($campus->campus_name ?? $campus->name ?? '') : $campus;
                                @endphp
                                <option value="{{ $campusName }}" {{ ($filterCampus ?? '') == $campusName ? 'selected' : '' }}>{{ $campusName }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-xl-2 col-lg-2 col-md-4 col-sm-6">
                        <label for="filter_class" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Class *</label>
                        <select id="filter_class" name="class" class="form-select form-select-sm" required style="height: 32px; border-radius: 6px; border: 1px solid #dee2e6; font-size: 12px;">
                            <option value="">Select Class</option>
                            @foreach($classes as $classItem)
                                <option value="{{ $classItem }}">{{ $classItem }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-xl-2 col-lg-2 col-md-4 col-sm-6">
                        <label for="filter_section" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Section</label>
                        <select id="filter_section" name="section" class="form-select form-select-sm" style="height: 32px; border-radius: 6px; border: 1px solid #dee2e6; font-size: 12px;">
                            <option value="">Select Section (Optional)</option>
                        </select>
                    </div>
                    <div class="col-xl-2 col-lg-2 col-md-4 col-sm-6">
                        <label for="filter_report_type" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Report Type *</label>
                        <select id="filter_report_type" name="report_type" class="form-select form-select-sm" required style="height: 32px; border-radius: 6px; border: 1px solid #dee2e6; font-size: 12px;">
                            <option value="">Select Report Type</option>
                            @foreach($reportTypes as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-xl-2 col-lg-2 col-md-4 col-sm-6">
                        <label for="filter_year" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Year *</label>
                        <select id="filter_year" name="year" class="form-select form-select-sm" required style="height: 32px; border-radius: 6px; border: 1px solid #dee2e6; font-size: 12px;">
                            <option value="">Select Year</option>
                            @foreach($years as $yearItem)
                                <option value="{{ $yearItem }}" {{ $yearItem == date('Y') ? 'selected' : '' }}>{{ $yearItem }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-xl-2 col-lg-2 col-md-4 col-sm-6">
                        <button type="submit" class="btn btn-sm w-100 generate-report-btn" style="height: 32px; border-radius: 6px; padding: 0 10px;">
                            <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">print</span>
                            <span style="font-size: 12px; vertical-align: middle; margin-left: 5px;">Generate & Print</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.generate-report-btn {
    background: linear-gradient(135deg, #003471 0%, #004a9f 100%);
    color: white;
    border: none;
    font-weight: 500;
    transition: all 0.3s ease;
    box-shadow: 0 2px 6px rgba(0, 52, 113, 0.2);
}

.generate-report-btn:hover {
    background: linear-gradient(135deg, #004a9f 0%, #003471 100%);
    transform: translateY(-1px);
    box-shadow: 0 4px 10px rgba(0, 52, 113, 0.3);
    color: white;
}

.generate-report-btn:active {
    transform: translateY(0);
}

#filter_class:focus,
#filter_section:focus,
#filter_report_type:focus,
#filter_year:focus {
    border-color: #003471;
    box-shadow: 0 0 0 3px rgba(0, 52, 113, 0.15);
    outline: none;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const campusSelect = document.getElementById('filter_campus');
    const classSelect = document.getElementById('filter_class');
    const sectionSelect = document.getElementById('filter_section');

    function resetSections() {
        sectionSelect.innerHTML = '<option value="">Select Section (Optional)</option>';
        sectionSelect.disabled = true;
    }

    function populateClasses(classes) {
        classSelect.innerHTML = '<option value="">Select Class</option>';
        if (classes && classes.length > 0) {
            classes.forEach(className => {
                const option = document.createElement('option');
                option.value = className;
                option.textContent = className;
                classSelect.appendChild(option);
            });
            classSelect.disabled = false;
        } else {
            classSelect.innerHTML = '<option value="">No classes found</option>';
            classSelect.disabled = false;
        }
    }

    function loadClassesByCampus(selectedCampus) {
        classSelect.disabled = true;
        classSelect.innerHTML = '<option value="">Loading...</option>';
        resetSections();

        const params = new URLSearchParams();
        if (selectedCampus) {
            params.append('campus', selectedCampus);
        }

        fetch(`{{ route('student-behavior.reporting-analysis.get-classes') }}?${params.toString()}`)
            .then(response => response.json())
            .then(data => {
                populateClasses(data.classes || []);
            })
            .catch(error => {
                console.error('Error loading classes:', error);
                classSelect.innerHTML = '<option value="">Error loading classes</option>';
                classSelect.disabled = false;
            });
    }

    // Load classes when campus changes
    if (campusSelect) {
        campusSelect.addEventListener('change', function() {
            loadClassesByCampus(this.value);
        });
    }

    // Load sections when class changes
    if (classSelect) {
        classSelect.addEventListener('change', function() {
            loadSections(this.value);
        });
    }

    function loadSections(selectedClass) {
        const selectedCampus = campusSelect ? campusSelect.value : '';
        if (!selectedClass) {
            resetSections();
            return;
        }
        
        sectionSelect.innerHTML = '<option value="">Loading...</option>';
        sectionSelect.disabled = true;
        
        const params = new URLSearchParams();
        params.append('class', selectedClass);
        if (selectedCampus) {
            params.append('campus', selectedCampus);
        }
        fetch(`{{ route('student-behavior.reporting-analysis.get-sections') }}?${params.toString()}`)
            .then(response => response.json())
            .then(data => {
                sectionSelect.innerHTML = '<option value="">Select Section (Optional)</option>';
                if (data.sections && data.sections.length > 0) {
                    data.sections.forEach(section => {
                        const option = document.createElement('option');
                        option.value = section;
                        option.textContent = section;
                        sectionSelect.appendChild(option);
                    });
                }
                sectionSelect.disabled = false;
            })
            .catch(error => {
                console.error('Error loading sections:', error);
                sectionSelect.innerHTML = '<option value="">Error loading sections</option>';
                sectionSelect.disabled = false;
            });
    }

    if (campusSelect) {
        loadClassesByCampus(campusSelect.value);
    }
});
</script>
@endsection
