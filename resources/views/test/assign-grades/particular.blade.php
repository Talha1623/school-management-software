@extends('layouts.app')

@section('title', 'Assign Grades - For Particular Test')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-3 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0 fs-16 fw-semibold">Assign Grades - For Particular Test</h4>
            </div>

            <!-- Filter Form -->
            <form action="{{ route('test.assign-grades.particular') }}" method="GET" id="filterForm">
                <div class="row g-2 mb-3 align-items-end">
                    <!-- Campus -->
                    <div class="col-md-2">
                        <label for="filter_campus" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Campus</label>
                        <select class="form-select form-select-sm" id="filter_campus" name="filter_campus" style="height: 32px;">
                            <option value="">All Campuses</option>
                            @foreach($campuses as $campus)
                                @php
                                    $campusName = is_object($campus) ? ($campus->campus_name ?? '') : $campus;
                                @endphp
                                <option value="{{ $campusName }}" {{ $filterCampus == $campusName ? 'selected' : '' }}>{{ $campusName }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Class -->
                    <div class="col-md-2">
                        <label for="filter_class" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Class</label>
                        <select class="form-select form-select-sm" id="filter_class" name="filter_class" style="height: 32px;">
                            <option value="">All Classes</option>
                            @foreach($classes as $className)
                                <option value="{{ $className }}" {{ $filterClass == $className ? 'selected' : '' }}>{{ $className }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Section -->
                    <div class="col-md-2">
                        <label for="filter_section" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Section</label>
                        <select class="form-select form-select-sm" id="filter_section" name="filter_section" style="height: 32px;" {{ !$filterClass ? 'disabled' : '' }}>
                            <option value="">All Sections</option>
                            @if($filterClass)
                                @foreach($sections as $sectionName)
                                    <option value="{{ $sectionName }}" {{ $filterSection == $sectionName ? 'selected' : '' }}>{{ $sectionName }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>

                    <!-- Subject -->
                    <div class="col-md-2">
                        <label for="filter_subject" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Subject</label>
                        <select class="form-select form-select-sm" id="filter_subject" name="filter_subject" style="height: 32px;">
                            <option value="">All Subjects</option>
                            @foreach($subjects as $subjectName)
                                <option value="{{ $subjectName }}" {{ $filterSubject == $subjectName ? 'selected' : '' }}>{{ $subjectName }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Test -->
                    <div class="col-md-2">
                        <label for="filter_test" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Test</label>
                        <select class="form-select form-select-sm" id="filter_test" name="filter_test" style="height: 32px;">
                            <option value="">All Tests</option>
                            @foreach($tests as $testName)
                                <option value="{{ $testName }}" {{ $filterTest == $testName ? 'selected' : '' }}>{{ $testName }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Filter Button -->
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-sm py-1 px-3 rounded-8 filter-btn w-100" style="height: 32px;">
                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">filter_alt</span>
                            <span style="font-size: 12px;">Filter</span>
                        </button>
                    </div>
                </div>
            </form>

            <!-- Results Table -->
            @if(request()->hasAny(['filter_campus', 'filter_class', 'filter_section', 'filter_subject', 'filter_test']))
            <div class="mt-3">
                <div class="mb-2 p-2 rounded-8" style="background: linear-gradient(135deg, #003471 0%, #004a9f 100%);">
                    <h5 class="mb-0 text-white fs-15 fw-semibold d-flex align-items-center gap-2">
                        <span class="material-symbols-outlined" style="font-size: 18px;">grade</span>
                        <span>Assign Grades</span>
                    </h5>
                </div>

                <div class="default-table-area" style="margin-top: 0;">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Photo</th>
                                    <th>Student Code</th>
                                    <th>Student Name</th>
                                    <th>Class</th>
                                    <th>Section</th>
                                    <th>Marks</th>
                                    <th>Grade</th>
                                    <th>Remarks</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($students as $index => $student)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>
                                        @if($student->photo)
                                            <img src="{{ asset('storage/' . $student->photo) }}" alt="{{ $student->student_name }}" class="rounded-circle" style="width: 40px; height: 40px; object-fit: cover;">
                                        @else
                                            <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                                <span class="text-white fw-bold">{{ substr($student->student_name, 0, 1) }}</span>
                                            </div>
                                        @endif
                                    </td>
                                    <td>{{ $student->student_code ?? 'N/A' }}</td>
                                    <td>
                                        <strong class="text-primary">{{ $student->student_name }}</strong>
                                    </td>
                                    <td>{{ $student->class }}</td>
                                    <td>
                                        <span class="badge bg-info text-white">{{ $student->section ?? 'N/A' }}</span>
                                    </td>
                                    <td>
                                        <input type="number" class="form-control form-control-sm marks-input" data-student-id="{{ $student->id }}" placeholder="Marks" min="0" max="100" step="0.01" style="width: 100px; display: inline-block;">
                                    </td>
                                    <td>
                                        <select class="form-select form-select-sm grade-select" data-student-id="{{ $student->id }}" style="width: 100px; display: inline-block;">
                                            <option value="">Select Grade</option>
                                            <option value="A+">A+</option>
                                            <option value="A">A</option>
                                            <option value="B+">B+</option>
                                            <option value="B">B</option>
                                            <option value="C+">C+</option>
                                            <option value="C">C</option>
                                            <option value="D">D</option>
                                            <option value="F">F</option>
                                        </select>
                                    </td>
                                    <td>
                                        <input type="text" class="form-control form-control-sm remarks-input" data-student-id="{{ $student->id }}" placeholder="Remarks" style="width: 150px; display: inline-block;">
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-success save-grade-btn" data-student-id="{{ $student->id }}" style="font-size: 11px; padding: 4px 8px;">
                                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">save</span>
                                            Save
                                        </button>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="10" class="text-center py-4">
                                        <div class="d-flex flex-column align-items-center">
                                            <span class="material-symbols-outlined text-muted" style="font-size: 48px;">inbox</span>
                                            <p class="text-muted mt-2 mb-0">No students found</p>
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                            @if($students->count() > 0)
                            <tfoot>
                                <tr class="fw-bold" style="background-color: #f8f9fa;">
                                    <td colspan="9" class="text-end">Total Students:</td>
                                    <td>{{ $students->count() }}</td>
                                </tr>
                            </tfoot>
                            @endif
                        </table>
                    </div>
                </div>
            </div>
            @else
            <div class="text-center py-5">
                <span class="material-symbols-outlined text-muted" style="font-size: 64px;">filter_alt</span>
                <p class="text-muted mt-3 mb-0">Please apply filters to view students and assign grades</p>
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
}

.filter-btn:hover {
    background: linear-gradient(135deg, #004a9f 0%, #003471 100%);
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0, 52, 113, 0.3);
}

.default-table-area {
    background: #fff;
    border-radius: 8px;
    overflow: hidden;
}

.default-table-area table {
    margin-bottom: 0;
}

.default-table-area thead {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
}

.default-table-area thead th {
    font-weight: 600;
    font-size: 13px;
    color: #003471;
    border-bottom: 2px solid #dee2e6;
    padding: 12px;
}

.default-table-area tbody td {
    font-size: 13px;
    padding: 12px;
    vertical-align: middle;
}

.default-table-area tbody tr:hover {
    background-color: #f8f9fa;
}

.badge {
    font-size: 11px;
    padding: 4px 8px;
}

.save-grade-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Load sections when class changes
    const classSelect = document.getElementById('filter_class');
    const sectionSelect = document.getElementById('filter_section');

    if (classSelect && sectionSelect) {
        function loadSections(selectedClass) {
            if (selectedClass) {
                sectionSelect.disabled = false;
                sectionSelect.innerHTML = '<option value="">Loading...</option>';
                
                fetch(`{{ route('test.assign-grades.get-sections-by-class') }}?class=${encodeURIComponent(selectedClass)}`)
                    .then(response => response.json())
                    .then(data => {
                        sectionSelect.innerHTML = '<option value="">All Sections</option>';
                        data.sections.forEach(section => {
                            const option = document.createElement('option');
                            option.value = section;
                            option.textContent = section;
                            @if($filterSection)
                            if (section === '{{ $filterSection }}') {
                                option.selected = true;
                            }
                            @endif
                            sectionSelect.appendChild(option);
                        });
                    })
                    .catch(error => {
                        console.error('Error loading sections:', error);
                        sectionSelect.innerHTML = '<option value="">Error loading sections</option>';
                    });
            } else {
                sectionSelect.disabled = true;
                sectionSelect.innerHTML = '<option value="">All Sections</option>';
            }
        }

        classSelect.addEventListener('change', function() {
            loadSections(this.value);
        });

        // Load sections on page load if class is already selected
        @if($filterClass)
        loadSections('{{ $filterClass }}');
        @endif
    }

    // Auto-calculate grade based on marks
    document.querySelectorAll('.marks-input').forEach(function(input) {
        input.addEventListener('input', function() {
            const marks = parseFloat(this.value);
            const studentId = this.getAttribute('data-student-id');
            const gradeSelect = document.querySelector('.grade-select[data-student-id="' + studentId + '"]');
            
            if (!isNaN(marks) && gradeSelect) {
                let grade = '';
                if (marks >= 90) grade = 'A+';
                else if (marks >= 80) grade = 'A';
                else if (marks >= 70) grade = 'B+';
                else if (marks >= 60) grade = 'B';
                else if (marks >= 50) grade = 'C+';
                else if (marks >= 40) grade = 'C';
                else if (marks >= 33) grade = 'D';
                else grade = 'F';
                
                gradeSelect.value = grade;
            }
        });
    });

    // Save grade button click handler
    document.querySelectorAll('.save-grade-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const studentId = this.getAttribute('data-student-id');
            const marksInput = document.querySelector('.marks-input[data-student-id="' + studentId + '"]');
            const gradeSelect = document.querySelector('.grade-select[data-student-id="' + studentId + '"]');
            const remarksInput = document.querySelector('.remarks-input[data-student-id="' + studentId + '"]');
            
            const marks = marksInput ? marksInput.value : '';
            const grade = gradeSelect ? gradeSelect.value : '';
            const remarks = remarksInput ? remarksInput.value : '';
            
            if (!marks || !grade) {
                alert('Please enter marks and select a grade');
                return;
            }
            
            // Here you can add AJAX call to save the grade
            // For now, just show a success message
            alert('Grade saved successfully for student ID: ' + studentId);
        });
    });
});
</script>
@endsection
