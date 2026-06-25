@extends('layouts.accountant')

@section('title', 'Family Vouchers - Accountant')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-3">
            <h3 class="mb-3 fw-semibold" style="color: #003471;">Family Vouchers</h3>
            <p class="text-muted mb-3" style="font-size: 13px;">Same filters and fee data as Student Vouchers. One combined voucher per family.</p>

            <div class="card bg-light border-0 rounded-10 p-3 mb-3">
                <form method="GET" action="{{ route('accountant.fee-voucher.family') }}" id="filterForm">
                    <div class="row g-3">
                        <div class="col-md-2">
                            <label class="form-label mb-1 fs-13 fw-medium">Campus</label>
                            <div class="input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff;">
                                    <span class="material-symbols-outlined" style="font-size: 18px; color: #003471;">location_on</span>
                                </span>
                                <select class="form-select form-select-sm" name="campus" id="campus" style="height: 38px;" {{ !empty($filterCampus) ? 'disabled' : '' }}>
                                    <option value="">Select Campus</option>
                                    @foreach($campuses as $campus)
                                        <option value="{{ $campus->campus_name ?? $campus }}" {{ ($filterCampus ?? '') === ($campus->campus_name ?? $campus) ? 'selected' : '' }}>
                                            {{ $campus->campus_name ?? $campus }}
                                        </option>
                                    @endforeach
                                </select>
                                @if(!empty($filterCampus))
                                    <input type="hidden" name="campus" value="{{ $filterCampus }}">
                                @endif
                            </div>
                        </div>

                        <div class="col-md-2">
                            <label class="form-label mb-1 fs-13 fw-medium">Type</label>
                            <div class="input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff;">
                                    <span class="material-symbols-outlined" style="font-size: 18px; color: #003471;">category</span>
                                </span>
                                <select class="form-select form-select-sm" name="type" id="type" style="height: 38px;">
                                    <option value="">Select Type</option>
                                    <option value="three_copies" {{ request('type') == 'three_copies' ? 'selected' : '' }}>Three Copies</option>
                                    <option value="two_copies" {{ request('type') == 'two_copies' ? 'selected' : '' }}>Two Copies</option>
                                    <option value="thermal_copies" {{ request('type') == 'thermal_copies' ? 'selected' : '' }}>Thermal Copies</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-2">
                            <label class="form-label mb-1 fs-13 fw-medium">Class</label>
                            <div class="input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff;">
                                    <span class="material-symbols-outlined" style="font-size: 18px; color: #003471;">class</span>
                                </span>
                                <select class="form-select form-select-sm" name="class" id="class" style="height: 38px;" {{ empty($filterCampus) ? 'disabled' : '' }}>
                                    @if(empty($filterCampus))
                                        <option value="">Select Campus First</option>
                                    @else
                                        <option value="">All Classes</option>
                                        @foreach($classes as $class)
                                            <option value="{{ $class->class_name }}" {{ request('class') == $class->class_name ? 'selected' : '' }}>{{ $class->class_name }}</option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                        </div>

                        <div class="col-md-2">
                            <label class="form-label mb-1 fs-13 fw-medium">Section</label>
                            <div class="input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff;">
                                    <span class="material-symbols-outlined" style="font-size: 18px; color: #003471;">group</span>
                                </span>
                                <select class="form-select form-select-sm" name="section" id="section" style="height: 38px;">
                                    <option value="">All Sections</option>
                                    @foreach($sections as $section)
                                        <option value="{{ $section->name }}" {{ request('section') == $section->name ? 'selected' : '' }}>{{ $section->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="col-md-2">
                            <label class="form-label mb-1 fs-13 fw-medium">Vouchers For?</label>
                            <div class="input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff;">
                                    <span class="material-symbols-outlined" style="font-size: 18px; color: #003471;">receipt</span>
                                </span>
                                <select class="form-select form-select-sm" name="vouchers_for" id="vouchers_for" style="height: 38px;">
                                    <option value="">Select Month</option>
                                    @foreach(['January','February','March','April','May','June','July','August','September','October','November','December'] as $month)
                                        <option value="{{ $month }}" {{ request('vouchers_for') == $month ? 'selected' : '' }}>{{ $month }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-sm w-100 px-4 py-2" style="background-color: #003471; color: white;">
                                <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">filter_list</span>
                                Filter
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            @if(isset($families) && $families->count() > 0)
                <div class="card bg-light border-0 rounded-10 p-3">
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm">
                            <thead style="background-color: #003471; color: white;">
                                <tr>
                                    <th>#</th>
                                    <th>Parent Name</th>
                                    <th>Students</th>
                                    <th>Student Codes</th>
                                    <th>Classes</th>
                                    <th>Sections</th>
                                    <th>Campus</th>
                                    <th>Type</th>
                                    <th>Vouchers For</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($families as $index => $family)
                                    <tr>
                                        <td>{{ $index + 1 + (($families->currentPage() - 1) * $families->perPage()) }}</td>
                                        <td><strong>{{ $family->parent_name ?? 'Unknown' }}</strong></td>
                                        <td>{{ $family->student_names ?? 'N/A' }}</td>
                                        <td>{{ $family->student_codes ?? 'N/A' }}</td>
                                        <td>{{ $family->classes ?? 'N/A' }}</td>
                                        <td>{{ $family->sections ?? 'N/A' }}</td>
                                        <td>{{ $family->campus ?? 'N/A' }}</td>
                                        <td>
                                            <span class="badge bg-info">{{ request('type') ?: 'Three Copies' }}</span>
                                        </td>
                                        <td>
                                            <span class="badge bg-success">{{ request('vouchers_for') ?: date('F') }}</span>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-primary generate-family-voucher-btn" data-family-key="{{ $family->family_key }}">
                                                <span class="material-symbols-outlined" style="font-size: 14px;">print</span>
                                                Generate Voucher
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if($families->hasPages())
                        <div class="mt-3">
                            {{ $families->links() }}
                        </div>
                    @endif
                </div>
            @elseif(request()->hasAny(['campus', 'class', 'section', 'vouchers_for', 'type']))
                <div class="alert alert-info">
                    <span class="material-symbols-outlined" style="font-size: 20px; vertical-align: middle;">info</span>
                    No families found. Please adjust your filters or connect students to a parent account.
                </div>
            @endif
        </div>
    </div>
</div>

<style>
    .input-group-text { border-right: none; }
    .form-select, .form-control { border-left: none; }
    .form-select:focus, .form-control:focus {
        border-color: #003471;
        box-shadow: 0 0 0 0.2rem rgba(0, 52, 113, 0.25);
    }
    .input-group:focus-within .input-group-text {
        background-color: #003471 !important;
        color: white !important;
        border-color: #003471;
    }
    .input-group:focus-within .material-symbols-outlined { color: white !important; }

    table thead th {
        font-size: 13px;
        font-weight: 600;
        padding: 12px 15px;
    }

    table tbody td {
        font-size: 13px;
        padding: 12px 15px;
        vertical-align: middle;
    }

    .badge {
        font-size: 11px;
        padding: 4px 8px;
    }
</style>

<script>
function generateFamilyVoucher(familyKey) {
    if (!familyKey) {
        return;
    }

    const params = new URLSearchParams(window.location.search);
    params.delete('parent_id');
    params.delete('page');
    params.set('family_key', familyKey);

    if (!params.get('type')) {
        params.set('type', 'three_copies');
    }

    const url = '{{ route('accountant.fee-voucher.family.print') }}' + '?' + params.toString();
    window.open(url, '_blank');
}

document.addEventListener('click', function (event) {
    const button = event.target.closest('.generate-family-voucher-btn');
    if (!button) {
        return;
    }

    generateFamilyVoucher(button.getAttribute('data-family-key'));
});

const campusSelect = document.getElementById('campus');
const classSelect = document.getElementById('class');
const selectedClass = @json(request('class'));
const selectedSection = @json(request('section'));

function resetClassAndSection() {
    if (!classSelect) return;
    classSelect.innerHTML = '<option value="">Select Campus First</option>';
    classSelect.disabled = true;
    const sectionSelect = document.getElementById('section');
    sectionSelect.innerHTML = '<option value="">All Sections</option>';
    sectionSelect.disabled = true;
}

function loadClassesForCampus(preserveSelection) {
    if (!campusSelect || campusSelect.disabled) return;
    const campus = campusSelect.value;
    resetClassAndSection();
    if (!campus) return;

    classSelect.innerHTML = '<option value="">Loading...</option>';
    fetch(`{{ route('accountant.fee-voucher.get-classes-by-campus') }}?campus=${encodeURIComponent(campus)}`)
        .then(response => response.json())
        .then(data => {
            populateClassOptions(data.classes || [], preserveSelection ? selectedClass : '');
            if (preserveSelection && selectedClass) {
                classSelect.dispatchEvent(new Event('change'));
            }
        })
        .catch(() => {
            populateClassOptions([], preserveSelection ? selectedClass : '');
        });
}

function populateClassOptions(classes, selectedValue) {
    classSelect.innerHTML = '<option value="">All Classes</option>';
    (classes || []).forEach(className => {
        const option = document.createElement('option');
        option.value = className;
        option.textContent = className;
        if (selectedValue && selectedValue === className) {
            option.selected = true;
        }
        classSelect.appendChild(option);
    });
    classSelect.disabled = false;
}

if (campusSelect && !campusSelect.disabled) {
    campusSelect.addEventListener('change', function () {
        loadClassesForCampus(false);
    });
} else if (campusSelect && classSelect && !classSelect.disabled && classSelect.options.length <= 1 && '{{ $filterCampus ?? '' }}' !== '') {
    loadClassesForCampus(true);
}

document.getElementById('class').addEventListener('change', function() {
    const classValue = this.value;
    const sectionSelect = document.getElementById('section');
    const campus = campusSelect ? (campusSelect.disabled ? '{{ $filterCampus ?? '' }}' : campusSelect.value) : '';
    sectionSelect.innerHTML = '<option value="">All Sections</option>';

    if (classValue) {
        sectionSelect.disabled = true;
        sectionSelect.innerHTML = '<option value="">Loading...</option>';
        fetch(`{{ route('accountant.fee-voucher.get-sections-by-class') }}?class=${encodeURIComponent(classValue)}&campus=${encodeURIComponent(campus)}`)
            .then(response => response.json())
            .then(data => {
                sectionSelect.innerHTML = '<option value="">All Sections</option>';
                if (data.sections && data.sections.length > 0) {
                    data.sections.forEach(section => {
                        const option = document.createElement('option');
                        option.value = section.name;
                        option.textContent = section.name;
                        if (selectedSection && selectedSection === section.name) {
                            option.selected = true;
                        }
                        sectionSelect.appendChild(option);
                    });
                }
                sectionSelect.disabled = false;
            })
            .catch(() => {
                sectionSelect.innerHTML = '<option value="">All Sections</option>';
                sectionSelect.disabled = false;
            });
    } else {
        sectionSelect.disabled = false;
    }
});
</script>
@endsection
