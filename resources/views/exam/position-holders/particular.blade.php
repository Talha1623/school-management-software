@extends('layouts.app')

@section('title', 'Position Holders - For Particular Exam')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-3 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0 fs-16 fw-semibold">Position Holders - For Particular Exam</h4>
            </div>

            <!-- Filter Form -->
            <form action="{{ route('exam.position-holders.particular') }}" method="GET" id="filterForm">
                <div class="row g-2 mb-3 align-items-end">
                    <!-- Campus -->
                    <div class="col-md-3">
                        <label for="filter_campus" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Campus</label>
                        <select class="form-select form-select-sm" id="filter_campus" name="filter_campus" style="height: 32px;">
                            <option value="">All Campuses</option>
                            @foreach($campuses as $campus)
                                <option value="{{ $campus }}" {{ $filterCampus == $campus ? 'selected' : '' }}>{{ $campus }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Class -->
                    <div class="col-md-3">
                        <label for="filter_class" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Class</label>
                        <select class="form-select form-select-sm" id="filter_class" name="filter_class" style="height: 32px;">
                            <option value="">All Classes</option>
                            @foreach($classes as $className)
                                <option value="{{ $className }}" {{ $filterClass == $className ? 'selected' : '' }}>{{ $className }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Section -->
                    <div class="col-md-3">
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
            @if($filterCampus && $filterClass && $filterExam)
            <div class="mt-3">
                <div class="mb-2 p-2 rounded-8" style="background: linear-gradient(135deg, #003471 0%, #004a9f 100%);">
                    <h5 class="mb-0 text-white fs-15 fw-semibold d-flex align-items-center gap-2">
                        <span class="material-symbols-outlined" style="font-size: 18px;">emoji_events</span>
                        <span>Position Holders</span>
                    </h5>
                </div>

                <div class="default-table-area" style="margin-top: 0;">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Student Code</th>
                                    <th>Name</th>
                                    <th>Parent</th>
                                    <th>Total Marks Obtained</th>
                                    <th>Position</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($positionRows as $index => $row)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>{{ $row->student->student_code ?? 'N/A' }}</td>
                                        <td><strong class="text-primary">{{ $row->student->student_name }}</strong></td>
                                        <td>{{ $row->student->father_name ?? 'N/A' }}</td>
                                        <td>{{ number_format($row->total_obtained, 0) }}</td>
                                        <td>
                                            <span class="badge bg-success">{{ $row->position }}</span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-4">
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

                <div class="mt-3 text-end">
                    <button type="button" class="btn btn-sm px-3 py-2 export-btn print-btn" onclick="printTable()">
                        <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">print</span>
                        <span>Print</span>
                    </button>
                </div>
            </div>
            @else
            <div class="text-center py-5">
                <span class="material-symbols-outlined text-muted" style="font-size: 64px;">filter_alt</span>
                <p class="text-muted mt-3 mb-0">Please apply filters to view position holders</p>
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
    border: 1px solid #dee2e6;
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
        
        fetch(`{{ route('exam.position-holders.get-exams') }}?campus=${encodeURIComponent(campus)}`)
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

        fetch(`{{ route('exam.position-holders.get-classes') }}?campus=${encodeURIComponent(campus)}`)
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
            <h3 style="text-align: center; margin-bottom: 20px; color: #003471;">Position Holders</h3>
            ${printContents}
        </div>
    `;

    window.print();
    document.body.innerHTML = originalContents;
    window.location.reload();
}
</script>
@endsection
