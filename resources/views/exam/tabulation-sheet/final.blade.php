@extends('layouts.app')

@section('title', 'Tabulation Sheet - For Final Result')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-3 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0 fs-16 fw-semibold">Tabulation Sheet - For Final Result</h4>
            </div>

            <!-- Filter Form -->
            <form action="{{ route('exam.tabulation-sheet.final') }}" method="GET" id="filterForm">
                <div class="row g-2 mb-3 align-items-end">
                    <!-- Campus -->
                    <div class="col-md-3">
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

                    <!-- Academic Session -->
                    <div class="col-md-3">
                        <label for="filter_session" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Academic Session</label>
                        <select class="form-select form-select-sm" id="filter_session" name="filter_session" style="height: 32px;">
                            <option value="">All Sessions</option>
                            @foreach($sessions as $sessionName)
                                <option value="{{ $sessionName }}" {{ $filterSession == $sessionName ? 'selected' : '' }}>{{ $sessionName }}</option>
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
            @if($filterCampus && $filterClass && $filterSection && $filterSession)
            <div class="mt-3">
                <div class="mb-2 p-2 rounded-8" style="background: linear-gradient(135deg, #003471 0%, #004a9f 100%);">
                    <h5 class="mb-0 text-white fs-15 fw-semibold d-flex align-items-center gap-2">
                        <span class="material-symbols-outlined" style="font-size: 18px;">table_chart</span>
                        <span>Final Tabulation Sheet</span>
                    </h5>
                </div>

                <div class="default-table-area" style="margin-top: 0;">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Sr.</th>
                                    <th>Student Code</th>
                                    <th>Name</th>
                                    <th>Parent</th>
                                    <th>| Subjects →</th>
                                    @foreach($subjects as $subject)
                                        <th>{{ $subject }}</th>
                                    @endforeach
                                    <th>Total</th>
                                    <th>Rank</th>
                                    <th>Percentage</th>
                                    <th>Grade</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($tabulationRows as $index => $row)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>{{ $row->student->student_code ?? 'N/A' }}</td>
                                        <td><strong style="color: red;">{{ $row->student->student_name }}</strong></td>
                                        <td>{{ $row->student->father_name ?? 'N/A' }}</td>
                                        <td></td>
                                        @foreach($subjects as $subject)
                                            <td>{{ number_format($row->subject_scores[$subject] ?? 0, 0) }}</td>
                                        @endforeach
                                        <td>{{ number_format($row->obtained_marks, 0) }}</td>
                                        <td>{{ $row->rank }}</td>
                                        <td>{{ $row->total_marks > 0 ? number_format($row->percentage, 2) . '%' : '0.00%' }}</td>
                                        <td>{{ $row->grade }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ 5 + $subjects->count() + 4 }}" class="text-center py-4">
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

                <div class="mt-3 text-center">
                    <button type="button" class="btn btn-success px-4 py-2 rounded-8" onclick="printFinalTabulationSheet()">
                        <span class="material-symbols-outlined" style="font-size: 18px; vertical-align: middle;">print</span>
                        <span>Print Final Tabulation Sheet</span>
                    </button>
                </div>
            </div>
            @else
            <div class="text-center py-5 mt-3">
                <span class="material-symbols-outlined text-muted" style="font-size: 64px;">filter_alt</span>
                <p class="text-muted mt-3 mb-0">Please apply filters to view final tabulation sheet</p>
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

.default-table-area table {
    border-collapse: collapse;
    width: 100%;
}

.default-table-area table th,
.default-table-area table td {
    border: 1px solid #dee2e6;
    padding: 8px 12px;
    text-align: center;
    vertical-align: middle;
}

.default-table-area table thead th {
    background-color: #f8f9fa;
    font-weight: 600;
    color: #003471;
}

.default-table-area table tbody tr:hover {
    background-color: #f8f9fa;
}

.default-table-area table tbody td strong {
    color: red;
    font-weight: 600;
}

@media print {
    .filter-btn,
    .btn-success {
        display: none;
    }
    
    .default-table-area {
        margin-top: 0;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const campusSelect = document.getElementById('filter_campus');
    const classSelect = document.getElementById('filter_class');
    const sectionSelect = document.getElementById('filter_section');
    const initialClass = "{{ $filterClass ?? '' }}";
    const initialCampus = "{{ $filterCampus ?? '' }}";

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

    function loadSections(selectedClass) {
        if (selectedClass) {
            sectionSelect.innerHTML = '<option value="">Loading...</option>';
            sectionSelect.disabled = true;
            
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
                    sectionSelect.disabled = false;
                })
                .catch(error => {
                    console.error('Error loading sections:', error);
                    sectionSelect.innerHTML = '<option value="">Error loading sections</option>';
                    sectionSelect.disabled = false;
                });
        } else {
            sectionSelect.innerHTML = '<option value="">All Sections</option>';
            sectionSelect.disabled = false;
        }
    }

    if (campusSelect) {
        campusSelect.addEventListener('change', function() {
            loadClasses();
            sectionSelect.innerHTML = '<option value="">All Sections</option>';
        });
    }

    if (classSelect) {
        classSelect.addEventListener('change', function() {
            loadSections(this.value);
        });
    }

    // Load classes on page load if campus is selected
    if (initialCampus) {
        loadClasses();
    }
});

function printFinalTabulationSheet() {
    const printContents = document.querySelector('.default-table-area').innerHTML;
    const originalContents = document.body.innerHTML;

    document.body.innerHTML = `
        <div style="padding: 20px;">
            <h3 style="text-align: center; margin-bottom: 20px; color: #003471;">Final Tabulation Sheet</h3>
            ${printContents}
        </div>
    `;

    window.print();
    document.body.innerHTML = originalContents;
    window.location.reload();
}
</script>
@endsection
