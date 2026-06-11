@extends('layouts.app')

@section('title', 'List of Unpaid Invoices')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-3 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h4 class="mb-0 fs-16 fw-semibold">List of Unpaid Invoices</h4>
                    <small class="text-muted">Unpaid / partial invoices from generated fees — filter by type like Fee Default Reports</small>
                </div>
            </div>

            <form action="{{ route('reports.unpaid-invoices') }}" method="GET" id="filterForm">
                <div class="row g-2 mb-3 align-items-end">
                    <div class="col-md-2">
                        <label for="filter_campus" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Campus</label>
                        <select class="form-select form-select-sm" id="filter_campus" name="filter_campus" style="height: 32px;">
                            <option value="">All Campuses</option>
                            @foreach($campuses as $campus)
                                <option value="{{ $campus }}" {{ ($filterCampus ?? '') == $campus ? 'selected' : '' }}>{{ $campus }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label for="filter_class" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Class</label>
                        <select class="form-select form-select-sm" id="filter_class" name="filter_class" data-selected-class="{{ $filterClass ?? '' }}" style="height: 32px;">
                            <option value="">All Classes</option>
                            @foreach($classes as $className)
                                <option value="{{ $className }}" {{ ($filterClass ?? '') == $className ? 'selected' : '' }}>{{ $className }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label for="filter_section" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Section</label>
                        <select class="form-select form-select-sm" id="filter_section" name="filter_section" data-selected-section="{{ $filterSection ?? '' }}" style="height: 32px;">
                            <option value="">All Sections</option>
                            @foreach($sections as $sectionName)
                                <option value="{{ $sectionName }}" {{ ($filterSection ?? '') == $sectionName ? 'selected' : '' }}>{{ $sectionName }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label for="filter_type" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Type</label>
                        <select class="form-select form-select-sm" id="filter_type" name="filter_type" data-selected-type="{{ $filterType ?? '' }}" style="height: 32px;">
                            <option value="">All Types</option>
                            @foreach($typeOptions as $key => $label)
                                <option value="{{ $key }}" {{ ($filterType ?? '') == $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label for="filter_student_status" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Student Status</label>
                        <select class="form-select form-select-sm" id="filter_student_status" name="filter_student_status" style="height: 32px;">
                            <option value="">All Status</option>
                            <option value="active" {{ ($filterStudentStatus ?? '') == 'active' ? 'selected' : '' }}>Active</option>
                            <option value="deactive" {{ ($filterStudentStatus ?? '') == 'deactive' ? 'selected' : '' }}>Deactive</option>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <button type="submit" class="btn btn-sm py-1 px-3 rounded-8 filter-btn w-100" style="height: 32px;">
                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">filter_alt</span>
                            <span style="font-size: 12px;">Filter</span>
                        </button>
                    </div>
                </div>
            </form>

            @if(request()->hasAny(['filter_campus', 'filter_class', 'filter_section', 'filter_type', 'filter_student_status']))
            <div class="mt-3">
                <div class="mb-2 p-2 rounded-8" style="background: linear-gradient(135deg, #003471 0%, #004a9f 100%);">
                    <h5 class="mb-0 text-white fs-15 fw-semibold d-flex align-items-center gap-2">
                        <span class="material-symbols-outlined" style="font-size: 18px;">receipt_long</span>
                        <span>Unpaid Invoices</span>
                        @if($filterType)
                            <span class="badge bg-light text-dark ms-1">{{ $filterType }}</span>
                        @endif
                    </h5>
                </div>

                <div class="d-flex justify-content-end mb-2">
                    <div class="d-flex gap-2 flex-wrap">
                        <a href="{{ route('reports.unpaid-invoices.export', ['format' => 'excel']) }}?{{ http_build_query(request()->except(['page'])) }}" class="btn btn-sm px-2 py-1 export-btn excel-btn">
                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">description</span>
                            <span>Excel</span>
                        </a>
                        <a href="{{ route('reports.unpaid-invoices.export', ['format' => 'csv']) }}?{{ http_build_query(request()->except(['page'])) }}" class="btn btn-sm px-2 py-1 export-btn csv-btn">
                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">table_view</span>
                            <span>CSV</span>
                        </a>
                        <a href="{{ route('reports.unpaid-invoices.export', ['format' => 'pdf']) }}?{{ http_build_query(request()->except(['page'])) }}" class="btn btn-sm px-2 py-1 export-btn pdf-btn" target="_blank">
                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">picture_as_pdf</span>
                            <span>PDF</span>
                        </a>
                        <a href="{{ route('reports.unpaid-invoices.print') }}?{{ http_build_query(array_merge(request()->except(['page']), ['auto_print' => 1])) }}" class="btn btn-sm px-2 py-1 export-btn print-btn" target="_blank">
                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">print</span>
                            <span>Print</span>
                        </a>
                    </div>
                </div>

                <div class="default-table-area">
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
                                    <th>Expected</th>
                                    <th>Paid</th>
                                    <th>Unpaid</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($unpaidInvoices as $index => $row)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $row['student_code'] }}</td>
                                    <td>{{ $row['student_name'] }}</td>
                                    <td>{{ $row['campus'] ?? 'N/A' }}</td>
                                    <td>{{ $row['class'] ?? 'N/A' }}</td>
                                    <td>{{ $row['section'] ?? 'N/A' }}</td>
                                    <td>{{ $row['fee_type'] }}</td>
                                    <td>{{ number_format($row['expected_amount'], 2) }}</td>
                                    <td class="text-success">{{ number_format($row['paid_amount'], 2) }}</td>
                                    <td class="text-danger fw-semibold">{{ number_format($row['unpaid_amount'], 2) }}</td>
                                    <td>
                                        @if($row['status'] === 'Partial')
                                            <span class="badge bg-warning text-dark">Partial</span>
                                        @else
                                            <span class="badge bg-danger">Unpaid</span>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="11" class="text-center py-4 text-muted">No unpaid invoices found for selected filters.</td>
                                </tr>
                                @endforelse
                            </tbody>
                            @if($unpaidInvoices->count() > 0)
                            <tfoot>
                                <tr class="fw-bold" style="background-color: #f8f9fa;">
                                    <td colspan="7" class="text-end">Totals ({{ $unpaidInvoices->count() }} invoices):</td>
                                    <td>{{ number_format($unpaidInvoices->sum('expected_amount'), 2) }}</td>
                                    <td class="text-success">{{ number_format($unpaidInvoices->sum('paid_amount'), 2) }}</td>
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
}
.default-table-area thead th {
    font-weight: 600;
    font-size: 12px;
    color: #003471;
}
.export-btn { border: none; border-radius: 6px; height: 30px; font-size: 12px; color: #fff; display: inline-flex; align-items: center; gap: 4px; }
.excel-btn { background: #28a745; }
.csv-btn { background: #17a2b8; }
.pdf-btn { background: #dc3545; }
.print-btn { background: #6c757d; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const campusSelect = document.getElementById('filter_campus');
    const classSelect = document.getElementById('filter_class');
    const sectionSelect = document.getElementById('filter_section');
    const typeSelect = document.getElementById('filter_type');

    function populateFeeTypes(types, selectedType = '') {
        typeSelect.innerHTML = '<option value="">All Types</option>';
        (types || []).forEach(item => {
            const option = document.createElement('option');
            option.value = item.value;
            option.textContent = item.label;
            if (selectedType && selectedType === item.value) option.selected = true;
            typeSelect.appendChild(option);
        });
    }

    function loadFeeTypes(campus, selectedType = '') {
        const params = new URLSearchParams();
        if (campus) params.append('campus', campus);
        fetch(`{{ route('reports.unpaid-invoices.get-fee-types-by-campus') }}?${params}`)
            .then(r => r.json())
            .then(data => populateFeeTypes(data.types || [], selectedType))
            .catch(e => console.error(e));
    }

    function resetSections() {
        sectionSelect.innerHTML = '<option value="">All Sections</option>';
        sectionSelect.disabled = true;
    }

    function populateClasses(classes, selectedClass = '') {
        classSelect.innerHTML = '<option value="">All Classes</option>';
        (classes || []).forEach(name => {
            const o = document.createElement('option');
            o.value = name;
            o.textContent = name;
            if (selectedClass === name) o.selected = true;
            classSelect.appendChild(o);
        });
        classSelect.disabled = false;
    }

    function populateSections(sections, selectedSection = '') {
        sectionSelect.innerHTML = '<option value="">All Sections</option>';
        (sections || []).forEach(name => {
            const o = document.createElement('option');
            o.value = name;
            o.textContent = name;
            if (selectedSection === name) o.selected = true;
            sectionSelect.appendChild(o);
        });
        sectionSelect.disabled = false;
    }

    function loadClassesByCampus(campus, selectedClass = '', selectedSection = '') {
        classSelect.disabled = true;
        classSelect.innerHTML = '<option value="">Loading...</option>';
        resetSections();
        const params = new URLSearchParams();
        if (campus) params.append('campus', campus);
        fetch(`{{ route('reports.unpaid-invoices.get-classes-by-campus') }}?${params}`)
            .then(r => r.json())
            .then(data => {
                populateClasses(data.classes || [], selectedClass);
                if (selectedClass) loadSections(selectedClass, selectedSection);
            });
    }

    function loadSections(selectedClass, selectedSection = '') {
        if (!selectedClass) { resetSections(); return; }
        const params = new URLSearchParams({ class: selectedClass });
        if (campusSelect.value) params.append('campus', campusSelect.value);
        fetch(`{{ route('reports.unpaid-invoices.get-sections-by-class') }}?${params}`)
            .then(r => r.json())
            .then(data => populateSections(data.sections || [], selectedSection));
    }

    campusSelect.addEventListener('change', function() {
        loadClassesByCampus(this.value);
        loadFeeTypes(this.value);
    });
    classSelect.addEventListener('change', function() { loadSections(this.value); });

    loadClassesByCampus(
        campusSelect.value,
        classSelect.dataset.selectedClass || '',
        sectionSelect.dataset.selectedSection || ''
    );
    loadFeeTypes(campusSelect.value, typeSelect.dataset.selectedType || '');
});
</script>
@endsection
