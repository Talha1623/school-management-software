@extends('layouts.app')

@section('title', 'Print Marksheet - For Final Result')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-3 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0 fs-16 fw-semibold">Print Marksheet - For Final Result</h4>
            </div>

            <form action="{{ route('exam.print-marksheet.final') }}" method="GET" id="filterForm">
                <div class="row g-2 mb-3 align-items-end">
                    <div class="col-md-3">
                        <label for="filter_campus" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Campus</label>
                        <select class="form-select form-select-sm" id="filter_campus" name="filter_campus" style="height: 32px;">
                            <option value="">All Campuses</option>
                            @foreach($campuses as $campus)
                                @php
                                    $campusName = is_object($campus) ? ($campus->campus_name ?? $campus->name ?? '') : $campus;
                                @endphp
                                <option value="{{ $campusName }}" {{ $filterCampus == $campusName ? 'selected' : '' }}>{{ $campusName }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label for="filter_class" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Class</label>
                        <select class="form-select form-select-sm" id="filter_class" name="filter_class" style="height: 32px;">
                            <option value="">All Classes</option>
                            @foreach(($filterClasses ?? $classes) as $className)
                                <option value="{{ $className }}" {{ $filterClass == $className ? 'selected' : '' }}>{{ $className }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label for="filter_section" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Section</label>
                        <select class="form-select form-select-sm" id="filter_section" name="filter_section" style="height: 32px;">
                            <option value="">All Sections</option>
                            @foreach($sections as $sectionName)
                                <option value="{{ $sectionName }}" {{ $filterSection == $sectionName ? 'selected' : '' }}>{{ $sectionName }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label for="filter_session" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Academic Session</label>
                        <select class="form-select form-select-sm" id="filter_session" name="filter_session" style="height: 32px;">
                            <option value="">All Sessions</option>
                            @foreach($sessions as $session)
                                <option value="{{ $session }}" {{ $filterSession == $session ? 'selected' : '' }}>{{ $session }}</option>
                            @endforeach
                        </select>
                    </div>

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

@if($filterCampus && $filterClass && $filterSection && $filterSession)
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-3 mb-4">
            <div class="mb-2 p-2 rounded-8" style="background: linear-gradient(135deg, #003471 0%, #004a9f 100%);">
                <h5 class="mb-0 text-white fs-15 fw-semibold d-flex align-items-center gap-2">
                    <span class="material-symbols-outlined" style="font-size: 18px;">table_chart</span>
                    <span>Final Result</span>
                </h5>
            </div>

            <div class="default-table-area" style="margin-top: 0;">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Student Code</th>
                                <th>Name</th>
                                <th>Parent</th>
                                <th>Total</th>
                                <th>Rank</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($students as $index => $student)
                                @php
                                    $summary = $studentSummaries->get($student->id, []);
                                    $totalObtained = $summary['total_obtained'] ?? 0;
                                    $rank = $summary['rank'] ?? '-';
                                @endphp
                                <tr>
                                    <td>{{ $student->student_code ?? 'N/A' }}</td>
                                    <td><strong class="text-primary">{{ $student->student_name }}</strong></td>
                                    <td>{{ $student->father_name ?? 'N/A' }}</td>
                                    <td>{{ number_format($totalObtained, 0) }}</td>
                                    <td>{{ $rank }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-4">
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
        </div>
    </div>
</div>
@else
<div class="text-center py-5 mt-3">
    <span class="material-symbols-outlined text-muted" style="font-size: 64px;">filter_alt</span>
    <p class="text-muted mt-3 mb-0">Please apply filters to view final result</p>
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

.default-table-area {
    background: #fff;
    border-radius: 8px;
    overflow: hidden;
    border: 1px solid #dee2e6;
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
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const campusSelect = document.getElementById('filter_campus');
    const classSelect = document.getElementById('filter_class');
    const sectionSelect = document.getElementById('filter_section');
    const sessionSelect = document.getElementById('filter_session');

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

    if (classSelect) {
        classSelect.addEventListener('change', function() {
            loadSections(this.value);
        });
    }
});
</script>
@endsection
