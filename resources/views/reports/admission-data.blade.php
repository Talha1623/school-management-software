@extends('layouts.app')

@section('title', 'Admission Data Reports')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-3 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0 fs-16 fw-semibold">Admission Data Reports</h4>
            </div>

            <!-- Filter Form -->
            <form action="{{ route('reports.admission-data') }}" method="GET" id="filterForm">
                <div class="row g-2 mb-3 align-items-end">
                    <!-- Campus -->
                    <div class="col-md-2">
                        <label for="filter_campus" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Campus</label>
                        <select class="form-select form-select-sm" id="filter_campus" name="filter_campus" style="height: 32px;">
                            <option value="">All Campuses</option>
                            @foreach($campuses as $campus)
                                <option value="{{ $campus }}" {{ $filterCampus == $campus ? 'selected' : '' }}>{{ $campus }}</option>
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

                    <!-- Year -->
                    <div class="col-md-3">
                        <label for="filter_year" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Year</label>
                        <div class="d-flex align-items-center gap-2">
                            <select class="form-select form-select-sm" id="filter_year" name="filter_year[]" multiple style="height: 70px;">
                                @foreach($years as $year)
                                    <option value="{{ $year }}" {{ in_array($year, $filterYears ?? []) ? 'selected' : '' }}>{{ $year }}</option>
                                @endforeach
                            </select>
                            <div class="form-check mb-0">
                                <input class="form-check-input" type="checkbox" id="filter_year_all">
                                <label class="form-check-label" for="filter_year_all" style="font-size: 11px;">Select All</label>
                            </div>
                        </div>
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
            @if(request()->hasAny(['filter_campus', 'filter_class', 'filter_section', 'filter_year']))
            <div class="mt-3">
                <div class="mb-2 p-2 rounded-8" style="background: linear-gradient(135deg, #003471 0%, #004a9f 100%);">
                    <h5 class="mb-0 text-white fs-15 fw-semibold d-flex align-items-center gap-2">
                        <span class="material-symbols-outlined" style="font-size: 18px;">list</span>
                        <span>Admission Data Reports</span>
                    </h5>
                </div>

                <div class="default-table-area" style="margin-top: 0;">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Student Code</th>
                                    <th>Student</th>
                                    <th>Parent</th>
                                    <th>Class/Section</th>
                                    <th>Admission Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($admissionRecords as $index => $record)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $record['student_code'] ?? 'N/A' }}</td>
                                    <td>{{ $record['student_name'] }} {{ $record['surname_caste'] ?? '' }}</td>
                                    <td>{{ $record['father_name'] ?? 'N/A' }}</td>
                                    <td>{{ $record['class'] }}{{ $record['section'] ? ' / ' . $record['section'] : '' }}</td>
                                    <td>{{ $record['admission_date'] ? date('d M Y', strtotime($record['admission_date'])) : 'N/A' }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4">
                                        <div class="d-flex flex-column align-items-center">
                                            <span class="material-symbols-outlined text-muted" style="font-size: 48px;">inbox</span>
                                            <p class="text-muted mt-2 mb-0">No admission records found</p>
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                            @if($admissionRecords->count() > 0)
                            <tfoot>
                                <tr class="fw-bold" style="background-color: #f8f9fa;">
                                    <td colspan="5" class="text-end">Total Students:</td>
                                    <td>{{ $admissionRecords->count() }}</td>
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
                <p class="text-muted mt-3 mb-0">Please apply filters to view admission data reports</p>
            </div>
            @endif
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const campusSelect = document.getElementById('filter_campus');
    const classSelect = document.getElementById('filter_class');
    const sectionSelect = document.getElementById('filter_section');
    const yearSelect = document.getElementById('filter_year');
    const yearSelectAll = document.getElementById('filter_year_all');
    const filterForm = document.getElementById('filterForm');

    function resetSelect(selectEl, placeholder) {
        selectEl.innerHTML = `<option value="">${placeholder}</option>`;
    }

    function loadClassesByCampus(campus) {
        resetSelect(classSelect, 'All Classes');
        resetSelect(sectionSelect, 'All Sections');

        if (!campus) {
            return;
        }

        fetch(`{{ route('reports.admission-data.get-classes-by-campus') }}?campus=${encodeURIComponent(campus)}`)
            .then(response => response.json())
            .then(classes => {
                classes.forEach(className => {
                    const option = document.createElement('option');
                    option.value = className;
                    option.textContent = className;
                    classSelect.appendChild(option);
                });
            })
            .catch(() => {});
    }

    function loadSectionsByClass(campus, className) {
        resetSelect(sectionSelect, 'All Sections');

        if (!className) {
            return;
        }

        const params = new URLSearchParams();
        if (campus) {
            params.append('campus', campus);
        }
        params.append('class', className);

        fetch(`{{ route('reports.admission-data.get-sections-by-class') }}?${params.toString()}`)
            .then(response => response.json())
            .then(sections => {
                sections.forEach(sectionName => {
                    const option = document.createElement('option');
                    option.value = sectionName;
                    option.textContent = sectionName;
                    sectionSelect.appendChild(option);
                });
            })
            .catch(() => {});
    }

    campusSelect.addEventListener('change', function () {
        loadClassesByCampus(this.value);
    });

    classSelect.addEventListener('change', function () {
        loadSectionsByClass(campusSelect.value, this.value);
    });

    if (yearSelect && yearSelectAll) {
        const updateSelectAllState = () => {
            const totalOptions = yearSelect.options.length;
            const selectedOptions = yearSelect.selectedOptions.length;
            yearSelectAll.checked = totalOptions > 0 && selectedOptions === totalOptions;
        };

        yearSelectAll.addEventListener('change', function () {
            const shouldSelectAll = this.checked;
            Array.from(yearSelect.options).forEach(option => {
                option.selected = shouldSelectAll;
            });
            if (filterForm) {
                filterForm.submit();
            }
        });

        yearSelect.addEventListener('change', function () {
            updateSelectAllState();
            if (filterForm) {
                filterForm.submit();
            }
        });
        updateSelectAllState();
    }
});
</script>

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
@endsection
