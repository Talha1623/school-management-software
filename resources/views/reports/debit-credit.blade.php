@extends('layouts.app')

@section('title', 'Debit & Credit Statement')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-3 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0 fs-16 fw-semibold">Debit & Credit Statement</h4>
            </div>

            <!-- Filter Form -->
            <form action="{{ route('reports.debit-credit') }}" method="GET" id="filterForm">
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
            @if(request()->hasAny(['filter_campus', 'filter_class', 'filter_section', 'filter_from_date', 'filter_to_date']))
            <div class="mt-3">
                <div class="mb-2 p-2 rounded-8" style="background: linear-gradient(135deg, #003471 0%, #004a9f 100%);">
                    <h5 class="mb-0 text-white fs-15 fw-semibold d-flex align-items-center gap-2">
                        <span class="material-symbols-outlined" style="font-size: 18px;">list</span>
                        <span>Debit & Credit Statement</span>
                    </h5>
                    <small class="text-white opacity-75 d-block mt-1">Credit: cash received + discount (separate) · Debit: paid expenses by category (including staff salary)</small>
                </div>

                <div class="d-flex justify-content-end mb-2">
                    <div class="d-flex gap-2 flex-wrap">
                        <a href="{{ route('reports.debit-credit.export', ['format' => 'csv']) }}?{{ http_build_query(request()->except(['page'])) }}" class="btn btn-sm px-2 py-1 export-btn csv-btn">
                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">table_view</span>
                            <span>CSV</span>
                        </a>
                        <a href="{{ route('reports.debit-credit.export', ['format' => 'pdf']) }}?{{ http_build_query(request()->except(['page'])) }}" class="btn btn-sm px-2 py-1 export-btn pdf-btn" target="_blank">
                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">picture_as_pdf</span>
                            <span>PDF</span>
                        </a>
                        <a href="{{ route('reports.debit-credit.print') }}?{{ http_build_query(array_merge(request()->except(['page']), ['auto_print' => 1])) }}" class="btn btn-sm px-2 py-1 export-btn print-btn" target="_blank">
                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">print</span>
                            <span>Print</span>
                        </a>
                    </div>
                </div>

                <div class="default-table-area" style="margin-top: 0;">
                    <div class="row g-2">
                        <div class="col-lg-6">
                            <div class="table-responsive mb-0">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Fee Head</th>
                                            <th class="text-center">Paid Txns</th>
                                            <th class="text-end">Cash</th>
                                            <th class="text-end">Discount</th>
                                            <th class="text-end">Paid Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($feeHeadSummary as $row)
                                        <tr>
                                            <td>{{ $row['head'] }}</td>
                                            <td class="text-center">{{ $row['transactions'] }}</td>
                                            <td class="text-end">{{ number_format($row['cash'] ?? 0, 2) }}</td>
                                            <td class="text-end text-warning">{{ number_format($row['discount'] ?? 0, 2) }}</td>
                                            <td class="text-end amount-credit fw-semibold">{{ number_format($row['amount'], 2) }}</td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="5" class="text-center py-3 text-muted">No paid fee collections found for selected filters</td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                    @if($feeHeadSummary->count() > 0)
                                    <tfoot>
                                        <tr class="fw-bold" style="background-color: #e3f2fd;">
                                            <td class="text-end">Total</td>
                                            <td class="text-center">{{ $feeTotals['transactions'] ?? 0 }}</td>
                                            <td class="text-end">{{ number_format($feeTotals['cash'] ?? 0, 2) }}</td>
                                            <td class="text-end text-warning">{{ number_format($feeTotals['discount'] ?? 0, 2) }}</td>
                                            <td class="text-end amount-credit">{{ number_format($feeTotals['amount'] ?? 0, 2) }}</td>
                                        </tr>
                                    </tfoot>
                                    @endif
                                </table>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="table-responsive mb-0">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Expense Category</th>
                                            <th class="text-center">Paid Txns</th>
                                            <th class="text-end">Paid Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($expenseHeadSummary as $row)
                                        <tr>
                                            <td>{{ $row['head'] }}</td>
                                            <td class="text-center">{{ $row['transactions'] }}</td>
                                            <td class="text-end amount-debit">{{ number_format($row['amount'], 2) }}</td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="3" class="text-center py-3 text-muted">No paid expenses or staff salaries found for selected filters</td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                    @if($expenseHeadSummary->count() > 0)
                                    <tfoot>
                                        <tr class="fw-bold" style="background-color: #fde8e8;">
                                            <td class="text-end">Total</td>
                                            <td class="text-center">{{ $expenseTotals['transactions'] ?? 0 }}</td>
                                            <td class="text-end amount-debit">{{ number_format($expenseTotals['amount'] ?? 0, 2) }}</td>
                                        </tr>
                                    </tfoot>
                                    @endif
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @else
            <div class="text-center py-5">
                <span class="material-symbols-outlined text-muted" style="font-size: 64px;">filter_alt</span>
                <p class="text-muted mt-3 mb-0">Please apply filters to view statement</p>
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

.amount-credit {
    background-color: #8dd194 !important;
    color: #000;
    font-weight: 600;
}

.amount-debit {
    background-color: #e8b5b5 !important;
    color: #000;
    font-weight: 600;
}

.export-btn {
    border: none;
    font-weight: 500;
    transition: all 0.3s ease;
    border-radius: 6px;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    height: 32px;
    font-size: 12px;
}
.csv-btn { background-color: #17a2b8; color: white; }
.csv-btn:hover { background-color: #138496; color: white; }
.pdf-btn { background-color: #dc3545; color: white; }
.pdf-btn:hover { background-color: #c82333; color: white; }
.print-btn { background-color: #6c757d; color: white; }
.print-btn:hover { background-color: #5a6268; color: white; }
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

        fetch(`{{ route('reports.debit-credit.get-classes-by-campus') }}?${params.toString()}`)
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

        fetch(`{{ route('reports.debit-credit.get-sections-by-class') }}?${params.toString()}`)
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
