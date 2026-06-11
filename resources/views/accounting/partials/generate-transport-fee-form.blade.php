@php
    $defaultCampus = $defaultCampus ?? null;
    $months = $months ?? [
        'January', 'February', 'March', 'April', 'May', 'June',
        'July', 'August', 'September', 'October', 'November', 'December',
    ];
    $currentYear = $currentYear ?? (int) date('Y');
    $years = $years ?? range($currentYear - 2, $currentYear + 5);
@endphp

<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-3">
            <h3 class="mb-3 fw-semibold" style="color: #003471;">Generate Transport Fee</h3>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <form action="{{ route($transportFeeFormRoute) }}" method="POST" id="transport-fee-form">
                @csrf

                <div class="row mb-2">
                    <div class="col-md-4">
                        <div class="card bg-light border-0 rounded-10 p-2 mb-2">
                            <h5 class="mb-1 py-2 px-3 text-white rounded-3 fw-semibold fs-15" style="margin: -8px -8px 8px -8px; background-color: #003471;">Campus</h5>
                            <div class="mb-1">
                                <label for="campus" class="form-label mb-0 fs-13 fw-medium">Campus <span class="text-danger">*</span></label>
                                <select class="form-select form-select-sm py-1" id="campus" name="campus" required style="height: 32px;">
                                    <option value="">Select Campus</option>
                                    @foreach($campuses as $campus)
                                        @php
                                            $campusName = $campus->campus_name ?? $campus;
                                            $selectedCampus = old('campus', $defaultCampus);
                                        @endphp
                                        <option value="{{ $campusName }}" @selected((string) $selectedCampus === (string) $campusName)>{{ $campusName }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card bg-light border-0 rounded-10 p-2 mb-2">
                            <h5 class="mb-1 py-2 px-3 text-white rounded-3 fw-semibold fs-15" style="margin: -8px -8px 8px -8px; background-color: #003471;">Class</h5>
                            <div class="mb-1">
                                <label for="class" class="form-label mb-0 fs-13 fw-medium">Class <span class="text-danger">*</span></label>
                                <select class="form-select form-select-sm py-1" id="class" name="class" required style="height: 32px;" disabled>
                                    <option value="">Select Campus first</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card bg-light border-0 rounded-10 p-2 mb-2">
                            <h5 class="mb-1 py-2 px-3 text-white rounded-3 fw-semibold fs-15" style="margin: -8px -8px 8px -8px; background-color: #003471;">Section</h5>
                            <div class="mb-1">
                                <label for="section" class="form-label mb-0 fs-13 fw-medium">Section <span class="text-danger">*</span></label>
                                <select class="form-select form-select-sm py-1" id="section" name="section" required style="height: 32px;" disabled>
                                    <option value="">Select Class first</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mb-2">
                    <div class="col-md-6">
                        <div class="card bg-light border-0 rounded-10 p-2 mb-2">
                            <h5 class="mb-1 py-2 px-3 text-white rounded-3 fw-semibold fs-15" style="margin: -8px -8px 8px -8px; background-color: #003471;">Fee Month</h5>
                            <div class="mb-1">
                                <label for="fee_month" class="form-label mb-0 fs-13 fw-medium">Fee Month <span class="text-danger">*</span></label>
                                <select class="form-select form-select-sm py-1" id="fee_month" name="fee_month" required style="height: 32px;">
                                    <option value="">Select Month</option>
                                    @foreach($months as $month)
                                        <option value="{{ $month }}" @selected(old('fee_month', date('F')) === $month)>{{ $month }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card bg-light border-0 rounded-10 p-2 mb-2">
                            <h5 class="mb-1 py-2 px-3 text-white rounded-3 fw-semibold fs-15" style="margin: -8px -8px 8px -8px; background-color: #003471;">Fee Year</h5>
                            <div class="mb-1">
                                <label for="fee_year" class="form-label mb-0 fs-13 fw-medium">Fee Year <span class="text-danger">*</span></label>
                                <select class="form-select form-select-sm py-1" id="fee_year" name="fee_year" required style="height: 32px;">
                                    <option value="">Select Year</option>
                                    @foreach($years as $year)
                                        <option value="{{ $year }}" @selected((string) old('fee_year', $currentYear) === (string) $year)>{{ $year }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mb-3" id="student-selection-section" style="display: none;">
                    <div class="col-12">
                        <div class="card bg-light border-0 rounded-10 p-3">
                            <h5 class="mb-3 fw-semibold" style="color: #003471;">Student Selection</h5>

                            <div class="mb-2 d-flex gap-2">
                                <button type="button" class="btn btn-sm btn-success" onclick="selectAllStudents()">
                                    <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">check_box</span>
                                    Select All
                                </button>
                                <button type="button" class="btn btn-sm btn-danger" onclick="selectNoneStudents()">
                                    <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">check_box_outline_blank</span>
                                    Select None
                                </button>
                            </div>

                            <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                                <table class="table table-sm table-hover">
                                    <thead style="position: sticky; top: 0; background-color: #f8f9fa; z-index: 10;">
                                        <tr>
                                            <th style="width: 50px;">
                                                <input type="checkbox" id="select-all-checkbox" onchange="toggleAllStudents(this)">
                                            </th>
                                            <th>Roll</th>
                                            <th style="color: #dc3545;">Student</th>
                                            <th>Parent</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody id="students-list"></tbody>
                                </table>
                            </div>

                            <div id="no-students-message" class="text-center text-muted py-3" style="display: none;">
                                No transport students found for the selected criteria.
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mb-2">
                    <div class="col-12">
                        <div class="d-flex justify-content-end gap-2">
                            <button type="reset" class="btn btn-sm btn-secondary px-4 py-2" onclick="resetTransportForm()">
                                <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">refresh</span>
                                Reset
                            </button>
                            <button type="submit" class="btn btn-sm px-4 py-2" style="background: linear-gradient(135deg, #003471 0%, #004a9f 100%); color: white; border: none;" id="generate-fee-btn">
                                <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">payments</span>
                                Generate Fee
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    .card {
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease;
    }

    .card:hover {
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
    }

    .form-control:focus,
    .form-select:focus {
        border-color: #003471;
        box-shadow: 0 0 0 0.2rem rgba(0, 52, 113, 0.25);
    }

    .form-label {
        color: #495057;
    }
</style>

<script>
const transportFeeOldCampus = @json(old('campus', $defaultCampus));
const transportFeeOldClass = @json(old('class'));
const transportFeeOldSection = @json(old('section'));
const transportFeeRoutes = {
    classes: @json(route($transportFeeClassesRoute)),
    sections: @json(route($transportFeeSectionsRoute)),
    students: @json(route($transportFeeStudentsRoute)),
};

document.addEventListener('DOMContentLoaded', function () {
    const campusSelect = document.getElementById('campus');
    const classSelect = document.getElementById('class');
    const sectionSelect = document.getElementById('section');
    const feeMonthSelect = document.getElementById('fee_month');
    const feeYearSelect = document.getElementById('fee_year');

    function resetSelect(selectEl, placeholder) {
        selectEl.innerHTML = `<option value="">${placeholder}</option>`;
        selectEl.disabled = true;
    }

    function loadClasses(selectedClass = '', selectedSection = '') {
        resetSelect(classSelect, 'Loading classes...');
        resetSelect(sectionSelect, 'Select Class first');
        hideStudents();

        const campus = campusSelect.value;
        if (!campus) {
            resetSelect(classSelect, 'Select Campus first');
            return;
        }

        const params = new URLSearchParams({ campus });
        fetch(`${transportFeeRoutes.classes}?${params.toString()}`, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(response => response.json())
            .then(data => {
                const classes = Array.isArray(data) ? data : (data.classes || []);
                classSelect.innerHTML = '<option value="">Select Class</option>';

                classes.forEach(className => {
                    const option = document.createElement('option');
                    option.value = className;
                    option.textContent = className;
                    if (selectedClass && selectedClass === className) {
                        option.selected = true;
                    }
                    classSelect.appendChild(option);
                });

                classSelect.disabled = false;

                if (selectedClass) {
                    loadSections(selectedSection);
                }
            })
            .catch(() => {
                resetSelect(classSelect, 'Error loading classes');
            });
    }

    function loadSections(selectedSection = '') {
        resetSelect(sectionSelect, 'Loading sections...');
        hideStudents();

        const campus = campusSelect.value;
        const className = classSelect.value;
        if (!className) {
            resetSelect(sectionSelect, 'Select Class first');
            return;
        }

        const params = new URLSearchParams({ class: className });
        if (campus) {
            params.append('campus', campus);
        }

        fetch(`${transportFeeRoutes.sections}?${params.toString()}`, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(response => response.json())
            .then(data => {
                const sections = Array.isArray(data) ? data : (data.sections || []);
                sectionSelect.innerHTML = '<option value="">Select Section</option>';

                sections.forEach(section => {
                    const sectionName = section.name || section;
                    const option = document.createElement('option');
                    option.value = sectionName;
                    option.textContent = sectionName;
                    if (selectedSection && selectedSection === sectionName) {
                        option.selected = true;
                    }
                    sectionSelect.appendChild(option);
                });

                sectionSelect.disabled = false;
                loadStudents();
            })
            .catch(() => {
                resetSelect(sectionSelect, 'Error loading sections');
            });
    }

    function hideStudents() {
        const studentSection = document.getElementById('student-selection-section');
        const studentsList = document.getElementById('students-list');
        const noStudentsMessage = document.getElementById('no-students-message');

        studentSection.style.display = 'none';
        studentsList.innerHTML = '';
        noStudentsMessage.style.display = 'none';
    }

    window.loadTransportStudents = function () {
        loadStudents();
    };

    function loadStudents() {
        const campus = campusSelect.value;
        const className = classSelect.value;
        const section = sectionSelect.value;
        const feeMonth = feeMonthSelect.value;
        const feeYear = feeYearSelect.value;
        const studentSection = document.getElementById('student-selection-section');
        const studentsList = document.getElementById('students-list');
        const noStudentsMessage = document.getElementById('no-students-message');

        if (!campus || !className || !section || !feeMonth || !feeYear) {
            hideStudents();
            return;
        }

        studentsList.innerHTML = '<tr><td colspan="5" class="text-center">Loading students...</td></tr>';
        studentSection.style.display = 'block';
        noStudentsMessage.style.display = 'none';

        const params = new URLSearchParams({
            campus,
            class: className,
            section,
            fee_month: feeMonth,
            fee_year: feeYear,
        });

        fetch(`${transportFeeRoutes.students}?${params.toString()}`, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(response => response.json())
            .then(data => {
                studentsList.innerHTML = '';
                const students = data.students || [];

                if (!students.length) {
                    noStudentsMessage.style.display = 'block';
                    return;
                }

                students.forEach(student => {
                    const disabled = student.has_fee_generated ? 'disabled' : '';
                    const statusClass = student.has_fee_generated ? 'bg-secondary' : 'bg-success';
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>
                            <input type="checkbox" name="selected_students[]" value="${student.id}" class="student-checkbox" ${disabled}>
                        </td>
                        <td>${student.student_code || 'N/A'}</td>
                        <td style="color: #dc3545; font-weight: 500;">${student.student_name || 'N/A'}</td>
                        <td>${student.parent_name || 'N/A'}</td>
                        <td><span class="badge ${statusClass}">${student.status || 'Ready'}</span></td>
                    `;
                    studentsList.appendChild(row);
                });

                updateSelectAllCheckbox();
            })
            .catch(() => {
                studentsList.innerHTML = '<tr><td colspan="5" class="text-center text-danger">Error loading students</td></tr>';
            });
    }

    campusSelect.addEventListener('change', () => loadClasses());
    classSelect.addEventListener('change', () => loadSections());
    sectionSelect.addEventListener('change', loadStudents);
    feeMonthSelect.addEventListener('change', loadStudents);
    feeYearSelect.addEventListener('change', loadStudents);

    if (campusSelect.value) {
        loadClasses(transportFeeOldClass, transportFeeOldSection);
    }
});

function updateSelectAllCheckbox() {
    const checkboxes = document.querySelectorAll('.student-checkbox:not(:disabled)');
    const selectAllCheckbox = document.getElementById('select-all-checkbox');
    if (!selectAllCheckbox) {
        return;
    }

    selectAllCheckbox.checked = checkboxes.length > 0 && Array.from(checkboxes).every(checkbox => checkbox.checked);
}

function toggleAllStudents(source) {
    document.querySelectorAll('.student-checkbox:not(:disabled)').forEach(checkbox => {
        checkbox.checked = source.checked;
    });
}

function selectAllStudents() {
    document.querySelectorAll('.student-checkbox:not(:disabled)').forEach(checkbox => {
        checkbox.checked = true;
    });
    updateSelectAllCheckbox();
}

function selectNoneStudents() {
    document.querySelectorAll('.student-checkbox').forEach(checkbox => {
        checkbox.checked = false;
    });
    updateSelectAllCheckbox();
}

function resetTransportForm() {
    setTimeout(() => window.location.reload(), 0);
}
</script>
