@extends('layouts.app')

@section('title', 'Behavior Recording')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-3 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0 fs-16 fw-semibold">Behavior Recording</h4>
            </div>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <!-- Filter Form -->
            <form method="GET" action="{{ route('student-behavior.recording') }}" id="filterForm">
                <div class="p-3 rounded-8 mb-3" style="background-color: #f8f9fa; border: 1px solid #e9ecef;">
                    <div class="row g-2 align-items-end">
                        <!-- Type -->
                        <div class="col-md-2 col-sm-6">
                            <label for="filter_type" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Type</label>
                            <select class="form-select form-select-sm filter-select" id="filter_type" name="filter_type">
                                <option value="">Select Type</option>
                                @foreach($types as $key => $label)
                                    <option value="{{ $key }}" {{ $filterType == $key ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Class -->
                        <div class="col-md-2 col-sm-6">
                            <label for="filter_class" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Class</label>
                            <select class="form-select form-select-sm filter-select" id="filter_class" name="filter_class">
                                <option value="">Select Class</option>
                                @foreach($classes as $class)
                                    <option value="{{ $class }}" {{ $filterClass == $class ? 'selected' : '' }}>{{ $class }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Section -->
                        <div class="col-md-2 col-sm-6">
                            <label for="filter_section" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Section</label>
                            <select class="form-select form-select-sm filter-select" id="filter_section" name="filter_section" {{ !$filterClass ? 'disabled' : '' }}>
                                <option value="">Select Section</option>
                                @if($filterClass && $sections->count() > 0)
                                    @foreach($sections as $section)
                                        <option value="{{ $section }}" {{ $filterSection == $section ? 'selected' : '' }}>{{ $section }}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>

                        <!-- Date -->
                        <div class="col-md-2 col-sm-6">
                            <label for="filter_date" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Date</label>
                            <input type="date" class="form-control form-control-sm filter-input" id="filter_date" name="filter_date" value="{{ $filterDate }}">
                        </div>

                        <!-- Filter Button -->
                        <div class="col-md-4 col-sm-6">
                            <button type="submit" class="btn btn-sm w-100 filter-btn d-inline-flex align-items-center justify-content-center gap-1">
                                <span class="material-symbols-outlined" style="font-size: 14px;">filter_alt</span>
                                <span style="font-size: 12px; white-space: nowrap;">Filter</span>
                            </button>
                        </div>
                    </div>
                </div>
            </form>

            <!-- Behavior Recording Interface - Only show when filters are applied -->
            @if($filterType && $filterClass)
            <div class="mt-4">
                <!-- Header Card -->
                <div class="card mb-3" style="background-color: #f8f9fa; border: 1px solid #e9ecef; border-radius: 8px;">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-center align-items-center">
                            <div class="text-center">
                                <h5 class="mb-1 fw-semibold" style="color: #495057; font-size: 16px;">Manage Behaviour - Class: {{ $filterClass }}</h5>
                                <div class="d-flex gap-3 flex-wrap justify-content-center" style="font-size: 13px; color: #6c757d;">
                                    <span><strong>Section:</strong> {{ $filterSection ?? 'N/A' }}</span>
                                    <span><strong>{{ $campusName ?? 'Main Campus' }}</strong></span>
                                    <span><strong>{{ $filterDate ? \Carbon\Carbon::parse($filterDate)->format('d F - Y') : date('d F - Y') }}</strong></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Mark All Buttons -->
                <div class="d-flex gap-2 mb-3 flex-wrap">
                    <button type="button" class="btn btn-sm mark-all-btn" data-points="-2" style="background-color: #dc3545; color: white; border: none; padding: 8px 16px; font-size: 13px; font-weight: 500;">
                        Mark All -2 Points
                    </button>
                    <button type="button" class="btn btn-sm mark-all-btn" data-points="-1" style="background-color: #fd7e14; color: white; border: none; padding: 8px 16px; font-size: 13px; font-weight: 500;">
                        Mark All -1 Point
                    </button>
                    <button type="button" class="btn btn-sm mark-all-btn" data-points="0" style="background-color: #6c757d; color: white; border: none; padding: 8px 16px; font-size: 13px; font-weight: 500;">
                        Mark All 0 Point
                    </button>
                    <button type="button" class="btn btn-sm mark-all-btn" data-points="1" style="background-color: #0d6efd; color: white; border: none; padding: 8px 16px; font-size: 13px; font-weight: 500;">
                        Mark All +1 Point
                    </button>
                    <button type="button" class="btn btn-sm mark-all-btn" data-points="2" style="background-color: #198754; color: white; border: none; padding: 8px 16px; font-size: 13px; font-weight: 500;">
                        Mark All +2 Points
                    </button>
                </div>

                <!-- Search Input -->
                <div class="mb-3">
                    <div class="input-group search-input-group" style="max-width: 350px;">
                        <span class="input-group-text search-icon-wrapper">
                            <span class="material-symbols-outlined search-icon">search</span>
                        </span>
                        <input type="text" id="searchInput" class="form-control search-input" placeholder="Search by Name / Student Code ...">
                        <button class="btn search-clear-btn" type="button" onclick="clearSearch()" title="Clear search">
                            <span class="material-symbols-outlined">close</span>
                        </button>
                    </div>
                </div>

                <!-- Students List -->
                @if($students->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover behavior-table" id="studentsList">
                        <thead>
                            <tr>
                                <th style="width: 5%;">#</th>
                                <th style="width: 25%;">Student Code</th>
                                <th style="width: 25%;">Student Name</th>
                                <th style="width: 25%;">Parent Name</th>
                                <th style="width: 20%;" class="text-center">Behavior Points</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($students as $student)
                                @php
                                    $studentId = $student->student_code ?? $student->gr_number ?? ($loop->iteration + 2000);
                                    $parentName = $student->father_name ?? 'N/A';
                                @endphp
                                <tr class="student-item" data-student-id="{{ $student->id }}" data-student-name="{{ strtolower($student->student_name) }}" data-student-code="{{ strtolower($student->student_code ?? '') }}">
                                    <td>{{ $loop->iteration }}</td>
                                    <td><strong>{{ $studentId }}</strong></td>
                                    <td>{{ $student->student_name }}</td>
                                    <td>{{ $parentName }}</td>
                                    <td>
                                        <div class="d-flex gap-2 justify-content-center align-items-center">
                                            <button type="button" class="btn btn-sm behavior-btn behavior-btn-minus-2" data-student-id="{{ $student->id }}" data-points="-2" data-type="{{ $filterType }}" data-class="{{ $filterClass }}" data-section="{{ $filterSection ?? '' }}" data-campus="{{ $campusName ?? 'Main Campus' }}" data-date="{{ $filterDate }}">&minus;2</button>
                                            <button type="button" class="btn btn-sm behavior-btn behavior-btn-minus-1" data-student-id="{{ $student->id }}" data-points="-1" data-type="{{ $filterType }}" data-class="{{ $filterClass }}" data-section="{{ $filterSection ?? '' }}" data-campus="{{ $campusName ?? 'Main Campus' }}" data-date="{{ $filterDate }}">&minus;1</button>
                                            <button type="button" class="btn btn-sm behavior-btn behavior-btn-zero" data-student-id="{{ $student->id }}" data-points="0" data-type="{{ $filterType }}" data-class="{{ $filterClass }}" data-section="{{ $filterSection ?? '' }}" data-campus="{{ $campusName ?? 'Main Campus' }}" data-date="{{ $filterDate }}">0</button>
                                            <button type="button" class="btn btn-sm behavior-btn behavior-btn-plus-1" data-student-id="{{ $student->id }}" data-points="1" data-type="{{ $filterType }}" data-class="{{ $filterClass }}" data-section="{{ $filterSection ?? '' }}" data-campus="{{ $campusName ?? 'Main Campus' }}" data-date="{{ $filterDate }}">&plus;1</button>
                                            <button type="button" class="btn btn-sm behavior-btn behavior-btn-plus-2" data-student-id="{{ $student->id }}" data-points="2" data-type="{{ $filterType }}" data-class="{{ $filterClass }}" data-section="{{ $filterSection ?? '' }}" data-campus="{{ $campusName ?? 'Main Campus' }}" data-date="{{ $filterDate }}">&plus;2</button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="text-center py-5">
                    <span class="material-symbols-outlined" style="font-size: 48px; opacity: 0.3;">school</span>
                    <p class="mt-2 mb-0">No students found for the selected filters.</p>
                </div>
                @endif

                <!-- Save Button -->
                @if($students->count() > 0)
                <div class="mt-4 text-center">
                    <button type="button" class="btn btn-lg save-behavior-btn" id="saveAllBehaviorBtn">
                        <span class="material-symbols-outlined" style="font-size: 20px; vertical-align: middle;">save</span>
                        <span style="font-size: 16px; font-weight: 600; margin-left: 8px;">Save Behaviour</span>
                    </button>
                </div>
                @endif
            </div>
            @else
            <!-- Message when filters are not fully applied -->
            <div class="text-center py-5 mt-3">
                <span class="material-symbols-outlined" style="font-size: 64px; color: #dee2e6; opacity: 0.5;">filter_list</span>
                <h5 class="mt-3 text-muted">Apply Filters to View Students</h5>
                <p class="text-muted mb-0">Please select Type, Class/Section, and Date, then click Filter to view students list.</p>
            </div>
            @endif
        </div>
    </div>
</div>

<style>
.filter-btn {
    background: linear-gradient(135deg, #003471 0%, #004a9f 100%);
    color: white;
    border: none;
    transition: all 0.3s ease;
    box-shadow: 0 2px 6px rgba(0, 52, 113, 0.2);
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

.filter-btn .material-symbols-outlined {
    color: white !important;
}

.filter-select,
.filter-input {
    height: 32px;
    font-size: 13px;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    transition: all 0.3s ease;
}

.filter-select:focus,
.filter-input:focus {
    border-color: #003471;
    box-shadow: 0 0 0 3px rgba(0, 52, 113, 0.15);
    outline: none;
}

/* Search Input Styling */
.search-input-group {
    height: 32px;
    border-radius: 6px;
    overflow: hidden;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    transition: all 0.3s ease;
}

.search-input-group:focus-within {
    box-shadow: 0 0 0 3px rgba(0, 52, 113, 0.15);
    border: 1px solid #003471;
}

.search-icon-wrapper {
    background-color: #f0f4ff !important;
    border: 1px solid #e0e7ff;
    border-right: none;
    padding: 4px 10px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.search-icon {
    font-size: 18px;
    color: #003471;
}

.search-input {
    border: 1px solid #dee2e6;
    border-left: none;
    border-right: none;
    padding: 4px 12px;
    font-size: 13px;
    height: 32px;
    transition: all 0.3s ease;
}

.search-input:focus {
    border-color: #003471;
    box-shadow: none;
    outline: none;
}

.search-clear-btn {
    background-color: #f8f9fa;
    border: 1px solid #dee2e6;
    border-left: none;
    padding: 4px 10px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #6c757d;
    transition: all 0.2s ease;
}

.search-clear-btn:hover {
    background-color: #e9ecef;
    color: #495057;
}

.search-clear-btn .material-symbols-outlined {
    font-size: 18px;
}


.mark-all-btn:hover {
    opacity: 0.9;
    transform: translateY(-1px);
}

.behavior-btn {
    color: white;
    border: none;
    width: 40px;
    height: 32px;
    font-size: 13px;
    font-weight: bold;
    border-radius: 6px;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.behavior-btn-minus-2 {
    background-color: #dc3545;
}

.behavior-btn-minus-1 {
    background-color: #fd7e14;
}

.behavior-btn-zero {
    background-color: #6c757d;
}

.behavior-btn-plus-1 {
    background-color: #0d6efd;
}

.behavior-btn-plus-2 {
    background-color: #198754;
}

.behavior-btn:hover {
    opacity: 0.9;
    transform: scale(1.05);
    box-shadow: 0 3px 6px rgba(0, 0, 0, 0.2);
}

.behavior-btn:active {
    transform: scale(0.98);
}

/* Table Styling */
.behavior-table {
    background-color: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    border: 1px solid #dee2e6;
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
}

.behavior-table thead {
    background-color: #f8f9fa;
}

.behavior-table thead th {
    border-bottom: 2px solid #dee2e6;
    border-right: 1px solid #dee2e6;
    color: #003471;
    font-weight: 600;
    font-size: 14px;
    padding: 12px 15px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.behavior-table thead th:last-child {
    border-right: none;
}

.behavior-table tbody tr {
    transition: all 0.2s ease;
    border-bottom: 1px solid #dee2e6;
}

.behavior-table tbody tr:hover {
    background-color: #f8f9fa !important;
}

.behavior-table tbody tr:last-child {
    border-bottom: none;
}

.behavior-table tbody td {
    padding: 15px;
    vertical-align: middle;
    border-right: 1px solid #dee2e6;
    border-bottom: 1px solid #dee2e6;
    font-size: 14px;
    color: #495057;
}

.behavior-table tbody td:last-child {
    border-right: none;
}

.behavior-table tbody tr:last-child td {
    border-bottom: none;
}

.save-behavior-btn {
    background: linear-gradient(135deg, #003471 0%, #004a9f 100%);
    color: white;
    border: none;
    padding: 12px 32px;
    border-radius: 8px;
    font-weight: 600;
    transition: all 0.3s ease;
    box-shadow: 0 2px 6px rgba(0, 52, 113, 0.2);
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.save-behavior-btn:hover {
    background: linear-gradient(135deg, #004a9f 0%, #003471 100%);
    transform: translateY(-1px);
    box-shadow: 0 4px 10px rgba(0, 52, 113, 0.3);
    color: white;
}

.save-behavior-btn:active {
    transform: translateY(0);
}

.save-behavior-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
}
</style>

<script>
// Load sections dynamically when class changes
document.getElementById('filter_class')?.addEventListener('change', function() {
    const classValue = this.value;
    const sectionSelect = document.getElementById('filter_section');
    
    if (!classValue) {
        sectionSelect.innerHTML = '<option value="">Select Section</option>';
        sectionSelect.disabled = true;
        return;
    }
    
    // Show loading state
    sectionSelect.disabled = true;
    sectionSelect.innerHTML = '<option value="">Loading...</option>';
    
    // Fetch sections via AJAX
    fetch(`{{ route('student-behavior.recording.get-sections-by-class') }}?class=${encodeURIComponent(classValue)}`)
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
                sectionSelect.disabled = false;
            }
        })
        .catch(error => {
            console.error('Error loading sections:', error);
            sectionSelect.innerHTML = '<option value="">Error loading sections</option>';
            sectionSelect.disabled = false;
        });
});

function clearSearch() {
    document.getElementById('searchInput').value = '';
    filterStudents();
}

function filterStudents() {
    const searchTerm = document.getElementById('searchInput').value.toLowerCase().trim();
    const studentItems = document.querySelectorAll('.student-item');
    
    studentItems.forEach(item => {
        const studentName = item.getAttribute('data-student-name') || '';
        const studentCode = item.getAttribute('data-student-code') || '';
        
        if (searchTerm === '' || studentName.includes(searchTerm) || studentCode.includes(searchTerm)) {
            item.style.display = 'table-row';
        } else {
            item.style.display = 'none';
        }
    });
}

// Search functionality
document.getElementById('searchInput')?.addEventListener('input', filterStudents);

// Mark All functionality
document.querySelectorAll('.mark-all-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const points = this.getAttribute('data-points');
        const behaviorBtns = document.querySelectorAll(`.behavior-btn[data-points="${points}"]`);
        
        // Mark all buttons
        behaviorBtns.forEach(behaviorBtn => {
            markBehavior(behaviorBtn);
        });
    });
});

// Store behavior data temporarily
let behaviorData = {};

// Individual behavior button click - store data temporarily
function markBehavior(button) {
    const studentId = button.getAttribute('data-student-id');
    const points = parseInt(button.getAttribute('data-points'));
    const type = button.getAttribute('data-type');
    const classValue = button.getAttribute('data-class');
    const section = button.getAttribute('data-section');
    const campus = button.getAttribute('data-campus');
    const date = button.getAttribute('data-date');
    
    // Store behavior data
    behaviorData[studentId] = {
        student_id: studentId,
        type: type,
        points: points,
        class: classValue,
        section: section,
        campus: campus,
        date: date
    };
    
    // Visual feedback - highlight selected button
    const studentItem = button.closest('.student-item');
    const allButtons = studentItem.querySelectorAll('.behavior-btn');
    allButtons.forEach(btn => {
        btn.style.opacity = '0.5';
        btn.style.transform = 'scale(1)';
    });
    button.style.opacity = '1';
    button.style.transform = 'scale(1.1)';
    button.style.boxShadow = '0 4px 8px rgba(0, 0, 0, 0.2)';
}

// Save all behavior records
function saveAllBehaviorRecords() {
    const saveBtn = document.getElementById('saveAllBehaviorBtn');
    const behaviorRecords = Object.values(behaviorData);
    
    if (behaviorRecords.length === 0) {
        alert('Please mark behavior for at least one student before saving.');
        return;
    }
    
    // Show loading state
    const originalText = saveBtn.innerHTML;
    saveBtn.disabled = true;
    saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" style="margin-right: 8px;"></span><span>Saving...</span>';
    
    // Get CSRF token
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    
    // Save all records
    let savedCount = 0;
    let errorCount = 0;
    
    Promise.all(behaviorRecords.map((record, index) => {
        console.log('Saving record:', index + 1, record);
        return fetch('{{ route("student-behavior.recording.store") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify(record)
        })
        .then(response => {
            if (!response.ok) {
                return response.json().then(err => {
                    console.error('Server error response:', err);
                    throw new Error(err.message || 'Failed to save');
                });
            }
            return response.json();
        })
        .then(data => {
            console.log('Save response:', data);
            if (data.success) {
                savedCount++;
            } else {
                console.error('Save failed:', data.message, data.errors);
                errorCount++;
            }
        })
        .catch(error => {
            console.error('Error saving behavior record:', error, record);
            errorCount++;
        });
    }))
    .then(() => {
        if (errorCount === 0) {
            // Success feedback
            saveBtn.innerHTML = '<span class="material-symbols-outlined" style="font-size: 20px; vertical-align: middle;">check_circle</span><span style="font-size: 16px; font-weight: 600; margin-left: 8px;">Saved Successfully!</span>';
            saveBtn.style.background = 'linear-gradient(135deg, #198754 0%, #20c997 100%)';
            
            // Clear behavior data
            behaviorData = {};
            
            // Reset button styles
            setTimeout(() => {
                saveBtn.innerHTML = originalText;
                saveBtn.disabled = false;
            }, 2000);
        } else {
            alert(`Saved ${savedCount} records. ${errorCount} records failed to save.`);
            saveBtn.innerHTML = originalText;
            saveBtn.disabled = false;
        }
    });
}

// Attach event listeners to behavior buttons
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.behavior-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            markBehavior(this);
        });
    });
    
    // Attach event listener to Save button
    const saveBtn = document.getElementById('saveAllBehaviorBtn');
    if (saveBtn) {
        saveBtn.addEventListener('click', function() {
            saveAllBehaviorRecords();
        });
    }
});
</script>
@endsection

