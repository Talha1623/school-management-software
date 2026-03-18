@extends('layouts.app')

@section('title', 'Tabulation Sheet - For Particular Exam')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-3 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0 fs-16 fw-semibold">Tabulation Sheet - For Particular Exam</h4>
            </div>

            <!-- Filter Form -->
            <form action="{{ route('exam.tabulation-sheet.particular') }}" method="GET" id="filterForm">
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
                        <select class="form-select form-select-sm" id="filter_section" name="filter_section" style="height: 32px;">
                            <option value="">All Sections</option>
                            @foreach($sections as $sectionName)
                                <option value="{{ $sectionName }}" {{ $filterSection == $sectionName ? 'selected' : '' }}>{{ $sectionName }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Exam -->
                    <div class="col-md-3">
                        <label for="filter_exam" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Exam</label>
                        <select class="form-select form-select-sm" id="filter_exam" name="filter_exam" style="height: 32px;">
                            <option value="">All Exams</option>
                            @foreach($exams as $examName)
                                <option value="{{ $examName }}" {{ $filterExam == $examName ? 'selected' : '' }}>{{ $examName }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Type -->
                    <div class="col-md-3">
                        <label for="filter_type" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Type</label>
                        <select class="form-select form-select-sm" id="filter_type" name="filter_type" style="height: 32px;">
                            <option value="">Select Type</option>
                            <option value="normal" {{ $filterType == 'normal' ? 'selected' : '' }}>Normal</option>
                            <option value="editable" {{ $filterType == 'editable' ? 'selected' : '' }}>Editable</option>
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
            <!-- Results Table -->
            @if($filterCampus && $filterClass && $filterSection && $filterExam && $filterType)
            <div class="mt-3">
                <div class="mb-2 p-2 rounded-8" style="background: linear-gradient(135deg, #003471 0%, #004a9f 100%);">
                    <h5 class="mb-0 text-white fs-15 fw-semibold d-flex align-items-center gap-2">
                        <span class="material-symbols-outlined" style="font-size: 18px;">table_chart</span>
                        <span>Tabulation Sheet</span>
                    </h5>
                </div>

                <div class="default-table-area" style="margin-top: 0;">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Sr.</th>
                                    <th>Student Code</th>
                                    <th>Name</th>
                                    <th>Parent</th>
                                    <th>Seat No. | Subjects</th>
                                    @foreach($subjects as $subject)
                                        <th>{{ $subject }}</th>
                                    @endforeach
                                    <th>Total</th>
                                    <th>Rank</th>
                                    <th>Percentage</th>
                                    <th>Grade</th>
                                    <th>GPA</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($tabulationRows as $index => $row)
                                    <tr data-student-id="{{ $row->student->id }}">
                                        <td>{{ $index + 1 }}</td>
                                        <td>{{ $row->student->student_code ?? 'N/A' }}</td>
                                        <td><strong class="text-primary">{{ $row->student->student_name }}</strong></td>
                                        <td>{{ $row->student->father_name ?? 'N/A' }}</td>
                                        <td>{{ $row->student->gr_number ?? ($row->student->student_code ?? 'N/A') }}</td>
                                        @foreach($subjects as $subject)
                                            <td class="subject-mark-cell" data-subject="{{ $subject }}" data-student-id="{{ $row->student->id }}">
                                                @if($filterType == 'editable')
                                                    <input type="number" 
                                                           class="form-control form-control-sm mark-input" 
                                                           value="{{ number_format($row->subject_scores[$subject] ?? 0, 0) }}" 
                                                           data-subject="{{ $subject }}"
                                                           data-student-id="{{ $row->student->id }}"
                                                           style="width: 80px; text-align: center; min-width: 60px;"
                                                           min="0"
                                                           step="0.01">
                                                @else
                                                    {{ number_format($row->subject_scores[$subject] ?? 0, 0) }}
                                                @endif
                                            </td>
                                        @endforeach
                                        <td class="total-marks-cell">{{ number_format($row->obtained_marks, 0) }}</td>
                                        <td class="rank-cell">{{ $row->rank }}</td>
                                        <td class="percentage-cell">{{ number_format($row->percentage, 2) }}%</td>
                                        <td class="grade-cell">{{ $row->grade }}</td>
                                        <td class="gpa-cell">{{ $row->gpa }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ 11 + $subjects->count() }}" class="text-center py-4">
                                            <div class="d-flex flex-column align-items-center">
                                                <span class="material-symbols-outlined text-muted" style="font-size: 48px;">inbox</span>
                                                <p class="text-muted mt-2 mb-0">No records found for selected filters.</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="mt-3 d-flex justify-content-between align-items-center">
                    @if($filterType == 'editable')
                        <button type="button" class="btn btn-sm px-3 py-2 btn-success" id="saveMarksBtn" onclick="saveAllMarks()">
                            <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">save</span>
                            <span>Save Changes</span>
                        </button>
                    @else
                        <div></div>
                    @endif
                    <button type="button" class="btn btn-sm px-3 py-2 export-btn print-btn" onclick="printTable()">
                        <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">print</span>
                        <span>Print</span>
                    </button>
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
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const campusSelect = document.getElementById('filter_campus');
    const examSelect = document.getElementById('filter_exam');
    const classSelect = document.getElementById('filter_class');
    const sectionSelect = document.getElementById('filter_section');
    const initialClass = "{{ $filterClass ?? '' }}";

    function loadExams() {
        const campus = campusSelect.value;
        
        examSelect.innerHTML = '<option value="">Loading...</option>';
        
        fetch(`{{ route('exam.tabulation-sheet.get-exams') }}?campus=${encodeURIComponent(campus)}`)
            .then(response => response.json())
            .then(data => {
                examSelect.innerHTML = '<option value="">All Exams</option>';
                data.forEach(exam => {
                    examSelect.innerHTML += `<option value="${exam}">${exam}</option>`;
                });
            })
            .catch(error => {
                console.error('Error loading exams:', error);
                examSelect.innerHTML = '<option value="">Error loading exams</option>';
            });
    }

    function loadSections(selectedClass) {
        if (selectedClass) {
            sectionSelect.innerHTML = '<option value="">Loading...</option>';
            const campus = campusSelect ? campusSelect.value : '';
            const params = new URLSearchParams();
            params.append('class', selectedClass);
            if (campus) {
                params.append('campus', campus);
            }
            fetch(`{{ route('exam.timetable.get-sections') }}?${params.toString()}`)
                .then(response => response.json())
                .then(data => {
                    sectionSelect.innerHTML = '<option value="">All Sections</option>';
                    data.forEach(section => {
                        sectionSelect.innerHTML += `<option value="${section}">${section}</option>`;
                    });
                })
                .catch(error => {
                    console.error('Error loading sections:', error);
                    sectionSelect.innerHTML = '<option value="">Error loading sections</option>';
                });
        } else {
            sectionSelect.innerHTML = '<option value="">All Sections</option>';
        }
    }

    function loadClasses() {
        const campus = campusSelect ? campusSelect.value : '';
        classSelect.innerHTML = '<option value="">Loading...</option>';
        classSelect.disabled = true;

        fetch(`{{ route('exam.tabulation-sheet.get-classes') }}?campus=${encodeURIComponent(campus)}`)
            .then(response => response.json())
            .then(data => {
                classSelect.innerHTML = '<option value="">All Classes</option>';
                if (data.classes && data.classes.length > 0) {
                    data.classes.forEach(className => {
                        classSelect.innerHTML += `<option value="${className}">${className}</option>`;
                    });
                }
                classSelect.disabled = false;
                if (initialClass && data.classes && data.classes.includes(initialClass)) {
                    classSelect.value = initialClass;
                    loadSections(initialClass);
                } else {
                    sectionSelect.innerHTML = '<option value="">All Sections</option>';
                }
            })
            .catch(error => {
                console.error('Error loading classes:', error);
                classSelect.innerHTML = '<option value="">Error loading classes</option>';
                classSelect.disabled = false;
            });
    }

    campusSelect.addEventListener('change', function() {
        loadExams();
        loadClasses();
    });
    classSelect.addEventListener('change', function() {
        loadSections(this.value);
    });

    loadClasses();
});

function printTable() {
    const printContents = document.querySelector('.default-table-area').innerHTML;
    const originalContents = document.body.innerHTML;

    document.body.innerHTML = `
        <div style="padding: 20px;">
            <h3 style="text-align: center; margin-bottom: 20px; color: #003471;">Tabulation Sheet</h3>
            ${printContents}
        </div>
    `;

    window.print();
    document.body.innerHTML = originalContents;
    window.location.reload();
}

@if($filterType == 'editable')
// Editable marks functionality
const subjects = @json($subjects);
const filterExam = "{{ $filterExam }}";
const filterCampus = "{{ $filterCampus }}";
const filterClass = "{{ $filterClass }}";
const filterSection = "{{ $filterSection }}";
const examSession = "{{ $examSession ?? '' }}";

// Grades data for calculation
@php
    $gradesArray = [];
    if (isset($grades) && $grades->isNotEmpty()) {
        $gradesArray = $grades->map(function($grade) {
            return [
                'from_percentage' => $grade->from_percentage ?? 0,
                'to_percentage' => $grade->to_percentage ?? 100,
                'name' => $grade->name ?? '-',
                'grade_points' => $grade->grade_points ?? '-'
            ];
        })->values()->toArray();
    }
@endphp
const grades = @json($gradesArray);

// Store original marks data for each student-subject combination
const marksData = {};
const totalMarksPerSubject = {};

// Get total marks per subject from the table rows data
@if(isset($tabulationRows) && $tabulationRows->isNotEmpty())
    @foreach($subjects as $subject)
        @php
            // Get total marks for this subject from first student's mark (assuming same total for all)
            $totalMarks = 100; // Default to 100
            $firstRow = $tabulationRows->first();
            if ($firstRow && isset($firstRow->total_marks) && isset($firstRow->subject_scores)) {
                // Calculate average total marks per subject
                $subjectCount = count($subjects);
                if ($subjectCount > 0 && $firstRow->total_marks > 0) {
                    $totalMarks = round($firstRow->total_marks / $subjectCount);
                }
            }
        @endphp
        totalMarksPerSubject['{{ $subject }}'] = {{ $totalMarks }};
    @endforeach
@endif

// Initialize marks data from table
document.querySelectorAll('.mark-input').forEach(input => {
    const studentId = input.getAttribute('data-student-id');
    const subject = input.getAttribute('data-subject');
    const key = `${studentId}_${subject}`;
    marksData[key] = {
        studentId: studentId,
        subject: subject,
        marks: parseFloat(input.value) || 0,
        originalMarks: parseFloat(input.value) || 0
    };
});

// Add event listeners to mark inputs
document.querySelectorAll('.mark-input').forEach(input => {
    input.addEventListener('change', function() {
        const studentId = this.getAttribute('data-student-id');
        const subject = this.getAttribute('data-subject');
        const newMarks = parseFloat(this.value) || 0;
        
        // Update marks data
        const key = `${studentId}_${subject}`;
        if (marksData[key]) {
            marksData[key].marks = newMarks;
        }
        
        // Recalculate totals for this student
        recalculateStudentTotals(studentId);
        
        // Recalculate ranks for all students
        recalculateRanks();
    });
    
    input.addEventListener('blur', function() {
        if (this.value === '' || this.value === null) {
            this.value = 0;
        }
    });
});

function recalculateStudentTotals(studentId) {
    const row = document.querySelector(`tr[data-student-id="${studentId}"]`);
    if (!row) return;
    
    let totalMarks = 0;
    let totalObtained = 0;
    
    // Get all marks for this student
    row.querySelectorAll('.mark-input').forEach(input => {
        const marks = parseFloat(input.value) || 0;
        const subject = input.getAttribute('data-subject');
        totalObtained += marks;
        // Get total marks for this subject (default to 100 if not found)
        const subjectTotal = totalMarksPerSubject[subject] || 100;
        totalMarks += subjectTotal;
    });
    
    const percentage = totalMarks > 0 ? (totalObtained / totalMarks) * 100 : 0;
    
    // Update total, percentage cells
    row.querySelector('.total-marks-cell').textContent = Math.round(totalObtained);
    row.querySelector('.percentage-cell').textContent = percentage.toFixed(2) + '%';
    
    // Update grade and GPA
    updateGradeAndGPA(row, percentage);
}

function updateGradeAndGPA(row, percentage) {
    let grade = '-';
    let gpa = '-';
    
    // Use grades from backend if available
    if (grades && grades.length > 0) {
        const matchedGrade = grades.find(g => {
            return percentage >= g.from_percentage && percentage <= g.to_percentage;
        });
        if (matchedGrade) {
            grade = matchedGrade.name;
            gpa = matchedGrade.grade_points;
        }
    } else {
        // Fallback to basic grading scale if no grades available
        if (percentage >= 90) {
            grade = 'A+';
            gpa = '4.0';
        } else if (percentage >= 80) {
            grade = 'A';
            gpa = '3.7';
        } else if (percentage >= 70) {
            grade = 'B';
            gpa = '3.0';
        } else if (percentage >= 60) {
            grade = 'C';
            gpa = '2.0';
        } else if (percentage >= 50) {
            grade = 'D';
            gpa = '1.0';
        } else {
            grade = 'F';
            gpa = '0.0';
        }
    }
    
    row.querySelector('.grade-cell').textContent = grade;
    row.querySelector('.gpa-cell').textContent = gpa;
}

function recalculateRanks() {
    const rows = Array.from(document.querySelectorAll('tbody tr[data-student-id]'));
    
    // Get totals for each row
    const rowTotals = rows.map(row => {
        const totalText = row.querySelector('.total-marks-cell').textContent;
        return {
            row: row,
            total: parseFloat(totalText) || 0
        };
    });
    
    // Sort by total descending
    rowTotals.sort((a, b) => b.total - a.total);
    
    // Update ranks
    rowTotals.forEach((item, index) => {
        item.row.querySelector('.rank-cell').textContent = index + 1;
    });
}

async function saveAllMarks() {
    const saveBtn = document.getElementById('saveMarksBtn');
    const originalText = saveBtn.innerHTML;
    saveBtn.disabled = true;
    saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving...';
    
    // Collect all marks to save
    const marksToSave = [];
    document.querySelectorAll('.mark-input').forEach(input => {
        const studentId = input.getAttribute('data-student-id');
        const subject = input.getAttribute('data-subject');
        const marks = parseFloat(input.value) || 0;
        
        marksToSave.push({
            student_id: studentId,
            subject: subject,
            marks_obtained: marks,
            exam_name: filterExam,
            campus: filterCampus,
            class: filterClass,
            section: filterSection
        });
    });
    
    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ||
                         document.querySelector('input[name="_token"]')?.value;
        
        const response = await fetch('{{ route("exam.tabulation-sheet.save-marks") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                marks: marksToSave,
                exam_name: filterExam,
                campus: filterCampus,
                class: filterClass,
                section: filterSection,
                session: examSession
            })
        });
        
        const data = await response.json();
        
        if (response.ok && data.success) {
            alert('Marks saved successfully!');
            // Optionally reload the page to reflect saved changes
            // window.location.reload();
        } else {
            throw new Error(data.message || 'Error saving marks');
        }
    } catch (error) {
        console.error('Error saving marks:', error);
        alert('Error saving marks: ' + error.message);
    } finally {
        saveBtn.disabled = false;
        saveBtn.innerHTML = originalText;
    }
}
@endif
</script>
@endsection
