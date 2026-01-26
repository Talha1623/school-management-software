@extends('layouts.app')

@section('title', 'List of Unpaid Invoices')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-3 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0 fs-16 fw-semibold">List of Unpaid Invoices</h4>
            </div>

            <!-- Filter Form -->
            <form action="{{ route('reports.unpaid-invoices') }}" method="GET" id="filterForm">
                <div class="row g-2 mb-3 align-items-end">
                    <!-- Campus -->
                    <div class="col-xl-2 col-lg-2 col-md-4 col-sm-6">
                        <label for="filter_campus" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Campus</label>
                        <select class="form-select form-select-sm" id="filter_campus" name="filter_campus" style="height: 32px;">
                            <option value="">All Campuses</option>
                            @foreach($campuses as $campus)
                                <option value="{{ $campus }}" {{ $filterCampus == $campus ? 'selected' : '' }}>{{ $campus }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Class -->
                    <div class="col-xl-2 col-lg-2 col-md-4 col-sm-6">
                        <label for="filter_class" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Class</label>
                        <select class="form-select form-select-sm" id="filter_class" name="filter_class" data-selected-class="{{ $filterClass }}" style="height: 32px;">
                            <option value="">All Classes</option>
                            @foreach($classes as $className)
                                <option value="{{ $className }}" {{ $filterClass == $className ? 'selected' : '' }}>{{ $className }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Section -->
                    <div class="col-xl-2 col-lg-2 col-md-4 col-sm-6">
                        <label for="filter_section" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Section</label>
                        <select class="form-select form-select-sm" id="filter_section" name="filter_section" data-selected-section="{{ $filterSection }}" style="height: 32px;">
                            <option value="">All Sections</option>
                            @foreach($sections as $sectionName)
                                <option value="{{ $sectionName }}" {{ $filterSection == $sectionName ? 'selected' : '' }}>{{ $sectionName }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Type -->
                    <div class="col-xl-2 col-lg-2 col-md-4 col-sm-6">
                        <label for="filter_type" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Type</label>
                        <select class="form-select form-select-sm" id="filter_type" name="filter_student_status" style="height: 32px;">
                            <option value="">All Types</option>
                            <option value="Active" {{ ($filterStudentStatus ?? '') === 'Active' ? 'selected' : '' }}>Active</option>
                            <option value="Deactive" {{ ($filterStudentStatus ?? '') === 'Deactive' ? 'selected' : '' }}>Deactive</option>
                        </select>
                    </div>

                    <!-- Filter Button -->
                    <div class="col-xl-2 col-lg-2 col-md-4 col-sm-6">
                        <button type="submit" class="btn btn-sm py-1 px-3 rounded-8 filter-btn w-100" style="height: 32px;">
                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">filter_alt</span>
                            <span style="font-size: 12px;">Filter</span>
                        </button>
                    </div>
                </div>
            </form>

            <!-- Results Table -->
            @if(request()->hasAny(['filter_campus', 'filter_class', 'filter_section', 'filter_student_status']))
            <div class="mt-3">
                <div class="mb-2 p-2 rounded-8" style="background: linear-gradient(135deg, #003471 0%, #004a9f 100%);">
                    <h5 class="mb-0 text-white fs-15 fw-semibold d-flex align-items-center gap-2">
                        <span class="material-symbols-outlined" style="font-size: 18px;">list</span>
                        <span>List of Unpaid Invoices</span>
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
                                    <th>Campus</th>
                                    <th>Class</th>
                                    <th>Section</th>
                                    <th>Fee Type</th>
                                    <th>Expected Amount</th>
                                    <th>Paid Amount</th>
                                    <th>Unpaid Amount</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($unpaidInvoices as $index => $invoice)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $invoice['student_code'] }}</td>
                                    <td>{{ $invoice['student_name'] }}</td>
                                    <td>{{ $invoice['campus'] ?? 'N/A' }}</td>
                                    <td>{{ $invoice['class'] }}</td>
                                    <td>{{ $invoice['section'] ?? 'N/A' }}</td>
                                    <td>{{ $invoice['fee_type'] }}</td>
                                    <td>{{ number_format($invoice['expected_amount'], 2) }}</td>
                                    <td>{{ number_format($invoice['paid_amount'], 2) }}</td>
                                    <td class="fw-semibold text-danger">{{ number_format($invoice['unpaid_amount'], 2) }}</td>
                                    <td>
                                        @if($invoice['status'] == 'Partial')
                                            <span class="badge bg-warning text-dark">Partial</span>
                                        @else
                                            <span class="badge bg-danger">Unpaid</span>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="11" class="text-center py-4">
                                        <div class="d-flex flex-column align-items-center">
                                            <span class="material-symbols-outlined text-muted" style="font-size: 48px;">inbox</span>
                                            <p class="text-muted mt-2 mb-0">No unpaid invoices found</p>
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                            @if($unpaidInvoices->count() > 0)
                            <tfoot>
                                <tr class="fw-bold" style="background-color: #f8f9fa;">
                                    <td colspan="7" class="text-end">Total Expected:</td>
                                    <td>{{ number_format($unpaidInvoices->sum('expected_amount'), 2) }}</td>
                                    <td>{{ number_format($unpaidInvoices->sum('paid_amount'), 2) }}</td>
                                    <td class="text-danger">{{ number_format($unpaidInvoices->sum('unpaid_amount'), 2) }}</td>
                                    <td></td>
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
                <p class="text-muted mt-3 mb-0">Please apply filters to view unpaid invoices</p>
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

    function populateClasses(classes, selectedClass = '') {
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

        fetch(`{{ route('reports.unpaid-invoices.get-classes-by-campus') }}?${params.toString()}`)
            .then(response => response.json())
            .then(data => {
                populateClasses(data.classes || [], selectedClass);
                if (selectedClass) {
                    loadSections(selectedClass, selectedSection);
                }
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

        fetch(`{{ route('reports.unpaid-invoices.get-sections-by-class') }}?${params.toString()}`)
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
