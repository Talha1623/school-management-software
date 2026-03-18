@extends('layouts.app')

@section('title', 'Tabulation Sheet - For Combine Test')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-3 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0 fs-16 fw-semibold">Tabulation Sheet - For Combine Test</h4>
            </div>

            <!-- Filter Form -->
            <form action="{{ route('test.tabulation-sheet.combine') }}" method="GET" id="filterForm">
                <div class="row g-2 mb-3 align-items-end">
                    <!-- Campus -->
                    <div class="col-md-2">
                        <label for="filter_campus" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Campus</label>
                        <select class="form-select form-select-sm" id="filter_campus" name="filter_campus" style="height: 32px;">
                            <option value="">All Campuses</option>
                            @foreach($campuses as $campus)
                                @php $campusName = is_object($campus) ? ($campus->campus_name ?? '') : $campus; @endphp
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

                    <!-- Test Type -->
                    <div class="col-md-2">
                        <label for="filter_test_type" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Test Type</label>
                        <select class="form-select form-select-sm" id="filter_test_type" name="filter_test_type" style="height: 32px;">
                            <option value="">All Test Types</option>
                            @foreach($testTypes as $type)
                                <option value="{{ $type }}" {{ $filterTestType == $type ? 'selected' : '' }}>{{ $type }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- From Date -->
                    <div class="col-md-2">
                        <label for="filter_from_date" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">From Date</label>
                        <input type="date" class="form-control form-control-sm" id="filter_from_date" name="filter_from_date" value="{{ $filterFromDate }}" style="height: 32px;">
                    </div>

                    <!-- To Date -->
                    <div class="col-md-2">
                        <label for="filter_to_date" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">To Date</label>
                        <input type="date" class="form-control form-control-sm" id="filter_to_date" name="filter_to_date" value="{{ $filterToDate }}" style="height: 32px;">
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
            @if(request()->hasAny(['filter_campus', 'filter_class', 'filter_section', 'filter_test_type', 'filter_from_date', 'filter_to_date']))
            <div class="mt-3">
                <div class="mb-2 p-2 rounded-8" style="background: linear-gradient(135deg, #003471 0%, #004a9f 100%);">
                    <h5 class="mb-0 text-white fs-15 fw-semibold d-flex align-items-center gap-2">
                        <span class="material-symbols-outlined" style="font-size: 18px;">table_chart</span>
                        <span>Tabulation Sheet</span>
                    </h5>
                </div>

                <div class="default-table-area" style="margin-top: 0;">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Student Code</th>
                                    <th>Name</th>
                                    <th>Parent</th>
                                    <th>Campus</th>
                                    @if($subjects->isNotEmpty())
                                        @foreach($subjects as $subject)
                                            @if(strtolower(trim($subject)) !== 'computer')
                                                <th>{{ $subject }}</th>
                                            @endif
                                        @endforeach
                                    @endif
                                    <th>Computer</th>
                                    <th>Total</th>
                                    <th>Rank</th>
                                    <th>Percentage</th>
                                    <th>Grade</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($students as $index => $student)
                                @php
                                    $marksBySubject = $student->marksBySubject ?? collect();
                                @endphp
                                <tr>
                                    <td>{{ $student->student_code ?? 'N/A' }}</td>
                                    <td>
                                        <strong style="color: #dc3545;">{{ $student->student_name }}</strong>
                                    </td>
                                    <td>{{ $student->father_name ?? 'N/A' }}</td>
                                    <td>{{ $student->campus ?? 'N/A' }}</td>
                                    @if($subjects->isNotEmpty())
                                        @foreach($subjects as $subject)
                                            @if(strtolower(trim($subject)) !== 'computer')
                                                @php
                                                    // Try exact match first
                                                    $subjectMarks = $marksBySubject->get($subject, collect());
                                                    // If not found, try case-insensitive match
                                                    if ($subjectMarks->isEmpty()) {
                                                        $subjectLower = strtolower(trim($subject));
                                                        foreach ($marksBySubject as $subjectName => $marks) {
                                                            if (strtolower(trim($subjectName)) === $subjectLower) {
                                                                $subjectMarks = $marks;
                                                                break;
                                                            }
                                                        }
                                                    }
                                                    $marksObtained = $subjectMarks->isNotEmpty() ? (float)($subjectMarks->first()->marks_obtained ?? 0) : 0;
                                                @endphp
                                                <td>{{ $marksObtained }}</td>
                                            @endif
                                        @endforeach
                                    @endif
                                    @php
                                        // Get Computer marks - try exact match and case-insensitive
                                        $computerMarks = $marksBySubject->get('Computer', collect());
                                        if ($computerMarks->isEmpty()) {
                                            $computerMarks = $marksBySubject->get('computer', collect());
                                        }
                                        if ($computerMarks->isEmpty()) {
                                            foreach ($marksBySubject as $subjectName => $marks) {
                                                if (strtolower(trim($subjectName)) === 'computer') {
                                                    $computerMarks = $marks;
                                                    break;
                                                }
                                            }
                                        }
                                        $computerObtained = $computerMarks->isNotEmpty() ? (float)($computerMarks->first()->marks_obtained ?? 0) : 0;
                                    @endphp
                                    <td>{{ $computerObtained }}</td>
                                    <td>{{ $student->totalObtained ?? 0 }} / {{ $student->totalMarks ?? 0 }}</td>
                                    <td>{{ $student->rank ?? '-' }}</td>
                                    <td>{{ ($student->percentage ?? 0) > 0 ? number_format($student->percentage, 2) . '%' : 'nan' }}</td>
                                    <td>
                                        @if($student->grade)
                                            <span class="badge bg-success">{{ $student->grade }}</span>
                                        @else
                                            -
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="{{ 5 + ($subjects->count() ?? 0) + 4 }}" class="text-center py-4">
                                        <div class="d-flex flex-column align-items-center">
                                            <span class="material-symbols-outlined text-muted" style="font-size: 48px;">inbox</span>
                                            <p class="text-muted mt-2 mb-0">No students found</p>
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @else
            <div class="text-center py-5">
                <span class="material-symbols-outlined text-muted" style="font-size: 64px;">filter_alt</span>
                <p class="text-muted mt-3 mb-0">Please apply filters to view tabulation sheet</p>
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
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const campusSelect = document.getElementById('filter_campus');
    const classSelect = document.getElementById('filter_class');
    const sectionSelect = document.getElementById('filter_section');
    
    // When campus changes, clear class and section so user picks again for the new campus
    if (campusSelect) {
        campusSelect.addEventListener('change', function() {
            if (classSelect) { classSelect.value = ''; }
            if (sectionSelect) {
                sectionSelect.disabled = true;
                sectionSelect.innerHTML = '<option value="">All Sections</option>';
            }
        });
    }
    
    // Function to load sections dynamically (optionally filtered by campus)
    function loadSections(selectedClass) {
        if (selectedClass) {
            sectionSelect.disabled = false;
            sectionSelect.innerHTML = '<option value="">Loading...</option>';
            const campusVal = campusSelect ? campusSelect.value : '';
            let url = `{{ route('test.tabulation-sheet.combine.get-sections') }}?class=${encodeURIComponent(selectedClass)}`;
            if (campusVal) url += `&campus=${encodeURIComponent(campusVal)}`;
            fetch(url)
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
    
    // Load sections when class changes
    if (classSelect) {
        classSelect.addEventListener('change', function() {
            loadSections(this.value);
        });
    }
    
    // Load sections on page load if class is already selected
    @if($filterClass)
    loadSections('{{ $filterClass }}');
    @endif
    
    // Function to update grade dropdowns dynamically
    function updateGradeDropdowns() {
        fetch(`{{ route('test.tabulation-sheet.combine.get-grades') }}`)
            .then(response => response.json())
            .then(data => {
                const gradeSelects = document.querySelectorAll('.grade-select');
                gradeSelects.forEach(function(select) {
                    const currentValue = select.value;
                    const optionsHtml = '<option value="">Select Grade</option>' + 
                        data.grades.map(grade => `<option value="${grade}">${grade}</option>`).join('');
                    select.innerHTML = optionsHtml;
                    // Restore selected value if it still exists
                    if (currentValue && data.grades.includes(currentValue)) {
                        select.value = currentValue;
                    }
                });
            })
            .catch(error => {
                console.error('Error loading grades:', error);
            });
    }
    
    // Update grade dropdowns on page load
    updateGradeDropdowns();
    
    // Auto-calculate grade based on marks using dynamic grades
    document.querySelectorAll('.marks-input').forEach(function(input) {
        input.addEventListener('input', function() {
            const marks = parseFloat(this.value);
            const studentId = this.getAttribute('data-student-id');
            const gradeSelect = document.querySelector('.grade-select[data-student-id="' + studentId + '"]');
            
            if (!isNaN(marks) && gradeSelect) {
                // Fetch grades dynamically to determine which grade to assign
                fetch(`{{ route('test.tabulation-sheet.combine.get-grades') }}`)
                    .then(response => response.json())
                    .then(data => {
                        // Find the appropriate grade based on percentage ranges
                        // We need to fetch the full grade data with ranges, but for now use simple logic
                        let grade = '';
                        if (marks >= 90) grade = 'A+';
                        else if (marks >= 80) grade = 'A';
                        else if (marks >= 70) grade = 'B+';
                        else if (marks >= 60) grade = 'B';
                        else if (marks >= 50) grade = 'C+';
                        else if (marks >= 40) grade = 'C';
                        else if (marks >= 33) grade = 'D';
                        else grade = 'F';
                        
                        // Check if the calculated grade exists in the dynamic grades list
                        if (data.grades.includes(grade)) {
                            gradeSelect.value = grade;
                        } else {
                            // Find the closest grade
                            const sortedGrades = data.grades.sort();
                            gradeSelect.value = sortedGrades[0] || '';
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching grades:', error);
                    });
            }
        });
    });
});
</script>
@endsection
