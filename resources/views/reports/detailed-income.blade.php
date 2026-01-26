@extends('layouts.app')

@section('title', 'Detailed Income Reports')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-3 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0 fs-16 fw-semibold">Detailed Income Reports</h4>
            </div>

            <!-- Filter Form -->
            <form action="{{ route('reports.detailed-income') }}" method="GET" id="filterForm">
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
                        <select class="form-select form-select-sm" id="filter_class" name="filter_class" data-selected-class="{{ $filterClass }}" style="height: 32px;">
                            <option value="">All Classes</option>
                            @foreach($classes as $className)
                                <option value="{{ $className }}" {{ $filterClass == $className ? 'selected' : '' }}>{{ $className }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Section -->
                    <div class="col-md-2">
                        <label for="filter_section" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Section</label>
                        <select class="form-select form-select-sm" id="filter_section" name="filter_section" data-selected-section="{{ $filterSection }}" style="height: 32px;">
                            <option value="">All Sections</option>
                            @foreach($sections as $sectionName)
                                <option value="{{ $sectionName }}" {{ $filterSection == $sectionName ? 'selected' : '' }}>{{ $sectionName }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Month -->
                    <div class="col-md-2">
                        <label for="filter_month" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Month</label>
                        <select class="form-select form-select-sm" id="filter_month" name="filter_month" style="height: 32px;">
                            <option value="">All Months</option>
                            @foreach($months as $monthValue => $monthName)
                                <option value="{{ $monthValue }}" {{ $filterMonth == $monthValue ? 'selected' : '' }}>{{ $monthName }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Date -->
                    <div class="col-md-2">
                        <label for="filter_date" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Date</label>
                        <input type="date" class="form-control form-control-sm" id="filter_date" name="filter_date" value="{{ $filterDate }}" style="height: 32px;">
                    </div>

                    <!-- Year -->
                    <div class="col-md-2">
                        <label for="filter_year" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Year</label>
                        <select class="form-select form-select-sm" id="filter_year" name="filter_year" style="height: 32px;">
                            <option value="">All Years</option>
                            @foreach($years as $year)
                                <option value="{{ $year }}" {{ $filterYear == $year ? 'selected' : '' }}>{{ $year }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Method -->
                    <div class="col-md-2">
                        <label for="filter_method" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Method</label>
                        <select class="form-select form-select-sm" id="filter_method" name="filter_method" style="height: 32px;">
                            <option value="">All Methods</option>
                            @foreach($methods as $method)
                                <option value="{{ $method }}" {{ $filterMethod == $method ? 'selected' : '' }}>{{ $method }}</option>
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
            @if(request()->hasAny(['filter_campus', 'filter_class', 'filter_section', 'filter_month', 'filter_date', 'filter_year', 'filter_method']))
            <div class="mt-3">
                <div class="mb-2 p-2 rounded-8" style="background: linear-gradient(135deg, #003471 0%, #004a9f 100%);">
                    <h5 class="mb-0 text-white fs-15 fw-semibold d-flex align-items-center gap-2">
                        <span class="material-symbols-outlined" style="font-size: 18px;">list</span>
                        <span>Detailed Income Reports</span>
                    </h5>
                </div>

                <div class="default-table-area" style="margin-top: 0;">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Code</th>
                                    <th>Student</th>
                                    <th>Parent</th>
                                    <th>Class</th>
                                    <th>Title</th>
                                    <th>Amount Paid</th>
                                    <th>Dis</th>
                                    <th>Method</th>
                                    <th>Received By</th>
                                    <th>Payment Date/Time</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($incomeRecords as $index => $record)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $record['student_code'] }}</td>
                                    <td>{{ $record['student_name'] }}</td>
                                    <td>{{ $record['parent_name'] ?? 'N/A' }}</td>
                                    <td>{{ $record['class'] }}</td>
                                    <td>{{ $record['payment_title'] }}</td>
                                    <td>{{ number_format($record['payment_amount'], 2) }}</td>
                                    <td>{{ number_format($record['discount'], 2) }}</td>
                                    <td>{{ $record['method'] }}</td>
                                    <td>{{ $record['received_by'] ?? 'N/A' }}</td>
                                    <td>{{ $record['payment_date'] ? \Carbon\Carbon::parse($record['payment_date'])->format('d M Y h:i A') : 'N/A' }}</td>
                                    <td><span class="text-muted">N/A</span></td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="11" class="text-center py-4">
                                        <div class="d-flex flex-column align-items-center">
                                            <span class="material-symbols-outlined text-muted" style="font-size: 48px;">inbox</span>
                                            <p class="text-muted mt-2 mb-0">No income records found</p>
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                            @if($incomeRecords->count() > 0)
                            <tfoot>
                                <tr class="fw-bold" style="background-color: #f8f9fa;">
                                    <td colspan="5" class="text-end">Total Payment Amount:</td>
                                    <td>{{ number_format($incomeRecords->sum('payment_amount'), 2) }}</td>
                                    <td>{{ number_format($incomeRecords->sum('discount'), 2) }}</td>
                                    <td colspan="4"></td>
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
                <p class="text-muted mt-3 mb-0">Please apply filters to view detailed income reports</p>
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

    function resetSections() {
        sectionSelect.innerHTML = '<option value="">All Sections</option>';
        sectionSelect.disabled = true;
    }

    function populateClasses(classes, selectedClass = '', selectedSection = '') {
        classSelect.innerHTML = '<option value="">All Classes</option>';
        if (classes && classes.length > 0) {
            classes.forEach(className => {
                const option = document.createElement('option');
                option.value = className;
                option.textContent = className;
                if (selectedClass && selectedClass === className) {
                    option.selected = true;
                }
                classSelect.appendChild(option);
            });
        }
        classSelect.disabled = false;
        if (selectedClass) {
            loadSections(selectedClass, selectedSection);
        }
    }

    function populateSections(sections, selectedSection = '') {
        sectionSelect.innerHTML = '<option value="">All Sections</option>';
        if (sections && sections.length > 0) {
            sections.forEach(sectionName => {
                const option = document.createElement('option');
                option.value = sectionName;
                option.textContent = sectionName;
                if (selectedSection && selectedSection === sectionName) {
                    option.selected = true;
                }
                sectionSelect.appendChild(option);
            });
        }
        sectionSelect.disabled = false;
    }

    function loadClassesByCampus(campus, selectedClass = '', selectedSection = '') {
        classSelect.disabled = true;
        classSelect.innerHTML = '<option value="">Loading...</option>';
        resetSections();

        const params = new URLSearchParams();
        if (campus) {
            params.append('campus', campus);
        }

        fetch(`{{ route('reports.detailed-income.get-classes-by-campus') }}?${params.toString()}`)
            .then(response => response.json())
            .then(data => {
                populateClasses(data.classes || [], selectedClass, selectedSection);
            })
            .catch(error => {
                console.error('Error loading classes:', error);
                classSelect.innerHTML = '<option value="">All Classes</option>';
                classSelect.disabled = false;
            });
    }

    function loadSections(selectedClass, selectedSection = '') {
        const campus = campusSelect.value;
        if (!selectedClass) {
            resetSections();
            return;
        }

        sectionSelect.innerHTML = '<option value="">Loading...</option>';
        sectionSelect.disabled = true;

        const params = new URLSearchParams();
        params.append('class', selectedClass);
        if (campus) {
            params.append('campus', campus);
        }

        fetch(`{{ route('reports.detailed-income.get-sections-by-class') }}?${params.toString()}`)
            .then(response => response.json())
            .then(data => {
                populateSections(data.sections || [], selectedSection);
            })
            .catch(error => {
                console.error('Error loading sections:', error);
                sectionSelect.innerHTML = '<option value="">All Sections</option>';
                sectionSelect.disabled = false;
            });
    }

    campusSelect.addEventListener('change', function() {
        loadClassesByCampus(this.value);
    });

    classSelect.addEventListener('change', function() {
        loadSections(this.value);
    });

    const selectedClass = classSelect.dataset.selectedClass || '';
    const selectedSection = sectionSelect.dataset.selectedSection || '';
    loadClassesByCampus(campusSelect.value, selectedClass, selectedSection);
});
</script>
@endsection
