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
            @if(request()->hasAny(['filter_class', 'filter_section', 'filter_test_type', 'filter_from_date', 'filter_to_date']))
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
                                    <th>#</th>
                                    <th>Student Code</th>
                                    <th>Student Name</th>
                                    <th>Class</th>
                                    <th>Section</th>
                                    <th>Marks</th>
                                    <th>Grade</th>
                                    <th>Remarks</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($students as $index => $student)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
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
                                        <textarea class="form-control form-control-sm remarks-input" data-student-id="{{ $student->id }}" placeholder="Remarks" rows="1" style="width: 150px; min-width: 150px;"></textarea>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" class="text-center py-4">
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
                                    <td colspan="7" class="text-end">Total Students:</td>
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
});
</script>
@endsection
