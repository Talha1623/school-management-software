@extends('layouts.app')

@section('title', 'Marks Entry')

@section('content')
@php
    $isStaffMarksUser = $isStaffMarksUser ?? false;
    $uploadableSubjects = collect($uploadableSubjects ?? []);
    $canUploadMarks = $canUploadMarks ?? true;
@endphp
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-3 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0 fs-16 fw-semibold">Marks Entry</h4>
            </div>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if(!empty($isStaffMarksUser) && isset($campuses) && $campuses->isEmpty())
                <div class="alert alert-warning py-2 mb-3" style="font-size: 13px;">
                    No campus is assigned to your profile or Manage Subjects. Please contact the administrator.
                </div>
            @elseif(!empty($isStaffMarksUser))
                <div class="alert alert-info py-2 mb-3" style="font-size: 13px;">
                    Select <strong>Campus</strong>, then <strong>Class</strong>, <strong>Section</strong>, <strong>Subject</strong>, and <strong>Test</strong>. You can save marks only for subjects marked <strong>(Edit)</strong>.
                </div>
            @endif

            <!-- Filter Form - Flow: Campus → Class → Section → Subject → Test -->
            <form action="{{ route('test.marks-entry') }}" method="GET" id="filterForm">
                <div class="row g-2 mb-3 align-items-end">
                    <!-- Campus -->
                    <div class="col-md-2">
                        <label for="filter_campus" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Campus</label>
                        <select class="form-select form-select-sm" id="filter_campus" name="filter_campus" style="height: 32px;">
                            <option value="">Select Campus</option>
                            @foreach($campuses as $campus)
                                @php
                                    $campusName = is_object($campus) ? ($campus->campus_name ?? '') : $campus;
                                @endphp
                                <option value="{{ $campusName }}" {{ $filterCampus == $campusName ? 'selected' : '' }}>{{ $campusName }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Class -->
                    <div class="col-md-2">
                        <label for="filter_class" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Class</label>
                        <select class="form-select form-select-sm" id="filter_class" name="filter_class" style="height: 32px;" {{ !$filterCampus ? 'disabled' : '' }}>
                            <option value="">Select Class</option>
                            @if($filterCampus && $classes->isNotEmpty())
                                @foreach($classes as $className)
                                    <option value="{{ $className }}" {{ $filterClass == $className ? 'selected' : '' }}>{{ $className }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>

                    <!-- Section -->
                    <div class="col-md-2">
                        <label for="filter_section" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Section</label>
                        <select class="form-select form-select-sm" id="filter_section" name="filter_section" style="height: 32px;" {{ !$filterClass ? 'disabled' : '' }}>
                            <option value="">Select Section</option>
                            @if($filterClass && $sections->isNotEmpty())
                                @foreach($sections as $sectionName)
                                    <option value="{{ $sectionName }}" {{ $filterSection == $sectionName ? 'selected' : '' }}>{{ $sectionName }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>

                    <!-- Subject -->
                    <div class="col-md-2">
                        <label for="filter_subject" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Subject</label>
                        <select class="form-select form-select-sm" id="filter_subject" name="filter_subject" style="height: 32px;" {{ !$filterClass ? 'disabled' : '' }}>
                            <option value="">Select Subject</option>
                            @if($filterClass && $subjects->isNotEmpty())
                                @php
                                    $uploadableSubjectKeys = collect($uploadableSubjects ?? [])->map(fn ($s) => strtolower(trim((string) $s)));
                                @endphp
                                @foreach($subjects as $subjectName)
                                    @php
                                        $subjectCanEdit = empty($isStaffMarksUser) || $uploadableSubjectKeys->contains(strtolower(trim((string) $subjectName)));
                                        $subjectLabel = $subjectName . (!empty($isStaffMarksUser) ? ($subjectCanEdit ? ' (Edit)' : ' (View only)') : '');
                                    @endphp
                                    <option value="{{ $subjectName }}" {{ $filterSubject == $subjectName ? 'selected' : '' }}>{{ $subjectLabel }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>

                    <!-- Test -->
                    <div class="col-md-2">
                        <label for="filter_test" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Test</label>
                        <select class="form-select form-select-sm" id="filter_test" name="filter_test" style="height: 32px;" {{ !$filterClass ? 'disabled' : '' }}>
                            <option value="">Select Test</option>
                            @if($filterClass && $tests->isNotEmpty())
                                @foreach($tests as $testName)
                                    <option value="{{ $testName }}" {{ $filterTest == $testName ? 'selected' : '' }}>{{ $testName }}</option>
                                @endforeach
                            @endif
                        </select>
                        <small id="testInfoMsg" class="text-muted d-block mt-1" style="font-size: 11px; display: none;">
                            <span class="material-symbols-outlined" style="font-size: 12px; vertical-align: middle;">info</span>
                            No tests found for selected filters.
                        </small>
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
            @if(request()->hasAny(['filter_campus', 'filter_class', 'filter_section', 'filter_test', 'filter_subject']))
            <div class="mt-3">
                @if(!empty($isStaffMarksUser))
                    <div id="marksStaffEditBanner" class="alert alert-info py-2 mb-3" style="font-size: 13px; {{ !empty($canUploadMarks) && $filterSubject ? '' : 'display:none;' }}">
                        <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">edit</span>
                        You can enter marks for <strong id="marksStaffEditSubject">{{ $filterSubject }}</strong> (assigned to you in Manage Subjects).
                    </div>
                    <div id="marksStaffViewBanner" class="alert alert-warning py-2 mb-3" style="font-size: 13px; {{ empty($canUploadMarks) && $filterSubject ? '' : 'display:none;' }}">
                        <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">visibility</span>
                        View only: <strong id="marksStaffViewSubject">{{ $filterSubject }}</strong> is not assigned to you. Marks cannot be changed.
                    </div>
                    @if(!$filterSubject)
                        <div class="alert alert-info py-2 mb-3" style="font-size: 13px;">
                            Select a subject. You can save marks only for subjects marked <strong>(Edit)</strong> in the dropdown.
                        </div>
                    @endif
                @endif

                @php $marksReadonly = isset($canUploadMarks) && !$canUploadMarks; @endphp

                <!-- Search Bar -->
                <div class="mb-3">
                    <div class="input-group input-group-sm search-input-group" style="max-width: 400px;">
                        <span class="input-group-text bg-light border-end-0" style="background-color: #f0f4ff !important; border-color: #e0e7ff; padding: 4px 8px; height: 32px;">
                            <span class="material-symbols-outlined" style="font-size: 14px; color: #003471;">search</span>
                        </span>
                        <input type="text" id="searchInput" class="form-control border-start-0 border-end-0" placeholder="Search by Name / Student Code ..." style="padding: 4px 8px; font-size: 13px; height: 32px;">
                        <button class="btn btn-outline-secondary border-start-0" type="button" id="clearSearchBtn" style="padding: 4px 8px; height: 32px; display: none;">
                            <span class="material-symbols-outlined" style="font-size: 14px;">close</span>
                        </button>
                    </div>
                </div>

                <div class="default-table-area" style="margin-top: 0;">
                    <div class="table-responsive">
                        <form id="marksForm" method="POST" action="{{ route('test.marks-entry.save') }}">
                            @csrf
                            <input type="hidden" name="test_id" value="{{ request('filter_test') }}">
                            <input type="hidden" name="campus" value="{{ request('filter_campus') }}">
                            <input type="hidden" name="class" value="{{ request('filter_class') }}">
                            <input type="hidden" name="section" value="{{ request('filter_section') }}">
                            <input type="hidden" name="subject" value="{{ request('filter_subject') }}">
                            
                            <table class="table table-sm table-hover">
                                <thead>
                                    <tr>
                                        <th>NO#</th>
                                        <th>Roll</th>
                                        <th>Name</th>
                                        <th>Parent</th>
                                        <th>Marks Obtained</th>
                                        <th>Total Marks</th>
                                        <th>Passing Marks</th>
                                    </tr>
                                </thead>
                                <tbody id="studentsTableBody">
                                    @forelse($students as $index => $student)
                                    @php
                                        $existingMark = $existingMarks->get($student->id);
                                    @endphp
                                    <tr class="student-row" data-student-id="{{ $student->id }}" data-student-name="{{ strtolower($student->student_name) }}" data-student-code="{{ strtolower($student->student_code ?? '') }}">
                                        <td>{{ $index + 1 }}</td>
                                        <td>{{ $student->student_code ?? $student->gr_number ?? 'N/A' }}</td>
                                        <td>
                                            <strong class="text-primary">{{ $student->student_name }}</strong>
                                        </td>
                                        <td>{{ $student->father_name ?? 'N/A' }}</td>
                                        <td>
                                            <input type="number" name="marks[{{ $student->id }}][obtained]" class="form-control form-control-sm marks-obtained" placeholder="0" min="0" step="1" style="width: 100px;" value="{{ $existingMark ? ($existingMark->marks_obtained ?? '') : '' }}" @if($marksReadonly) readonly disabled @endif>
                                        </td>
                                        <td>
                                            <input type="number" name="marks[{{ $student->id }}][total]" class="form-control form-control-sm marks-total" placeholder="0" min="0" step="1" style="width: 100px;" value="{{ $existingMark ? ($existingMark->total_marks ?? '') : '' }}" @if($marksReadonly) readonly disabled @endif>
                                        </td>
                                        <td>
                                            <input type="number" name="marks[{{ $student->id }}][passing]" class="form-control form-control-sm marks-passing" placeholder="0" min="0" step="1" style="width: 100px;" value="{{ $existingMark ? ($existingMark->passing_marks ?? '') : '' }}" @if($marksReadonly) readonly disabled @endif>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-4">
                                            <div class="d-flex flex-column align-items-center">
                                                <span class="material-symbols-outlined text-muted" style="font-size: 48px;">inbox</span>
                                                <p class="text-muted mt-2 mb-0">No students found. Please apply filters to view students.</p>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                            
                            @if($students->count() > 0 && empty($marksReadonly))
                            <div class="text-center mt-3">
                                <button type="submit" class="btn btn-success px-4 py-2" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%); border: none; font-weight: 500;">
                                    <span class="material-symbols-outlined" style="font-size: 18px; vertical-align: middle; color: white;">thumb_up</span>
                                    <span style="color: white; font-size: 14px;">Save Result</span>
                                </button>
                            </div>
                            @endif
                        </form>
                    </div>
                </div>
            </div>
            @else
            <div class="text-center py-5">
                <span class="material-symbols-outlined text-muted" style="font-size: 64px;">filter_alt</span>
                <p class="text-muted mt-3 mb-0">Please apply filters to view students for marks entry</p>
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

.default-table-area table {
    margin-bottom: 0;
    border-spacing: 0;
    border-collapse: collapse;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    overflow: hidden;
}

.default-table-area table thead {
    border-bottom: 1px solid #dee2e6;
    background-color: #f8f9fa;
}

.default-table-area table thead th {
    padding: 8px 12px;
    font-size: 13px;
    font-weight: 600;
    vertical-align: middle;
    line-height: 1.3;
    white-space: nowrap;
    border: 1px solid #dee2e6;
    background-color: #f8f9fa;
}

.default-table-area tbody td {
    font-size: 13px;
    padding: 8px 12px;
    vertical-align: middle;
    border: 1px solid #dee2e6;
}

.default-table-area tbody tr:hover {
    background-color: #f8f9fa;
}

.default-table-area .form-control-sm {
    font-size: 12px;
    padding: 4px 8px;
    height: 32px;
}

.search-input-group {
    border-radius: 8px;
    overflow: hidden;
    border: 1px solid #dee2e6;
    transition: all 0.3s ease;
    height: 32px;
}

.search-input-group:focus-within {
    border-color: #003471;
    box-shadow: 0 0 0 3px rgba(0, 52, 113, 0.15);
}

.search-input-group .form-control {
    border: none;
    font-size: 13px;
    height: 32px;
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
    const testSelect = document.getElementById('filter_test');
    const subjectSelect = document.getElementById('filter_subject');
    const isStaffMarksUser = {{ !empty($isStaffMarksUser) ? 'true' : 'false' }};
    const preservedClass = @json($filterClass ?? '');
    const preservedSection = @json($filterSection ?? '');
    const preservedSubject = @json($filterSubject ?? '');
    const preservedTest = @json($filterTest ?? '');
    let marksUploadableKeys = @json(collect($uploadableSubjects ?? [])->map(fn ($s) => strtolower(trim((string) $s)))->values());

    function marksFetch(url) {
        return fetch(url, {
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        }).then(async response => {
            const contentType = response.headers.get('content-type') || '';
            if (!contentType.includes('application/json')) {
                throw new Error('Invalid response from server');
            }
            const data = await response.json();
            if (!response.ok) {
                throw new Error(data.message || ('Server returned ' + response.status));
            }
            return data;
        });
    }

    function applyMarksEditMode() {
        const subject = subjectSelect ? String(subjectSelect.value || '').toLowerCase().trim() : '';
        const canEdit = !isStaffMarksUser || (subject !== '' && marksUploadableKeys.includes(subject));

        document.querySelectorAll('.marks-obtained, .marks-total, .marks-passing').forEach(function(input) {
            input.readOnly = !canEdit;
            input.disabled = !canEdit;
        });

        const saveBtn = document.querySelector('#marksForm button[type="submit"]');
        if (saveBtn) {
            saveBtn.style.display = canEdit ? '' : 'none';
        }

        const bannerEdit = document.getElementById('marksStaffEditBanner');
        const bannerView = document.getElementById('marksStaffViewBanner');
        if (bannerEdit) bannerEdit.style.display = (isStaffMarksUser && canEdit && subject) ? '' : 'none';
        if (bannerView) bannerView.style.display = (isStaffMarksUser && !canEdit && subject) ? '' : 'none';
    }

    function resetDownstream(from) {
        if (from === 'campus' || from === 'all') {
            if (classSelect) {
                classSelect.innerHTML = '<option value="">Select Class</option>';
                classSelect.disabled = true;
                classSelect.value = '';
            }
        }
        if (from === 'campus' || from === 'class' || from === 'all') {
            if (sectionSelect) {
                sectionSelect.innerHTML = '<option value="">Select Section</option>';
                sectionSelect.disabled = true;
                sectionSelect.value = '';
            }
        }
        if (from === 'campus' || from === 'class' || from === 'section' || from === 'all') {
            if (subjectSelect) {
                subjectSelect.innerHTML = '<option value="">Select Subject</option>';
                subjectSelect.disabled = true;
                subjectSelect.value = '';
            }
            if (testSelect) {
                testSelect.innerHTML = '<option value="">Select Test</option>';
                testSelect.disabled = true;
                testSelect.value = '';
            }
            const testInfoMsg = document.getElementById('testInfoMsg');
            if (testInfoMsg) testInfoMsg.style.display = 'none';
        }
    }

    function loadClasses(selectedClassToKeep) {
        if (!classSelect) return;

        const campus = campusSelect ? campusSelect.value : '';
        if (!campus) {
            resetDownstream('campus');
            return;
        }

        classSelect.disabled = false;
        classSelect.innerHTML = '<option value="">Loading...</option>';

        marksFetch(`{{ route('test.marks-entry.get-classes-by-campus') }}?campus=${encodeURIComponent(campus)}`)
            .then(data => {
                classSelect.innerHTML = '<option value="">Select Class</option>';
                (data.classes || []).forEach(className => {
                    const option = document.createElement('option');
                    option.value = className;
                    option.textContent = className;
                    if (selectedClassToKeep && className === selectedClassToKeep) {
                        option.selected = true;
                    }
                    classSelect.appendChild(option);
                });
                classSelect.disabled = !(data.classes && data.classes.length > 0);
                if (selectedClassToKeep && classSelect.value === selectedClassToKeep) {
                    loadSections(selectedClassToKeep, preservedSection);
                    loadSubjects(preservedSubject);
                    loadTests(preservedTest);
                }
            })
            .catch(error => {
                console.error('Error loading classes:', error);
                classSelect.innerHTML = '<option value="">Select Class</option>';
                classSelect.disabled = true;
            });
    }

    function loadSections(selectedClass, selectedSectionToKeep) {
        if (!sectionSelect) return;

        if (!selectedClass) {
            sectionSelect.innerHTML = '<option value="">Select Section</option>';
            sectionSelect.disabled = true;
            return;
        }

        const campus = campusSelect ? campusSelect.value : '';
        if (!campus) {
            sectionSelect.innerHTML = '<option value="">Select campus first</option>';
            sectionSelect.disabled = true;
            return;
        }

        sectionSelect.disabled = false;
        sectionSelect.innerHTML = '<option value="">Loading...</option>';

        const params = new URLSearchParams({ class: selectedClass, campus: campus });
        marksFetch(`{{ route('test.marks-entry.get-sections') }}?${params.toString()}`)
            .then(data => {
                sectionSelect.innerHTML = '<option value="">Select Section</option>';
                (data.sections || []).forEach(section => {
                    const option = document.createElement('option');
                    option.value = section;
                    option.textContent = section;
                    if (selectedSectionToKeep && section === selectedSectionToKeep) {
                        option.selected = true;
                    }
                    sectionSelect.appendChild(option);
                });
                sectionSelect.disabled = !(data.sections && data.sections.length > 0);
            })
            .catch(error => {
                console.error('Error loading sections:', error);
                sectionSelect.innerHTML = '<option value="">Error loading sections</option>';
                sectionSelect.disabled = true;
            });
    }

    function loadTests(selectedTestToKeep) {
        if (!testSelect) return;

        const campus = campusSelect ? campusSelect.value : '';
        const classValue = classSelect ? classSelect.value : '';
        const section = sectionSelect ? sectionSelect.value : '';
        const subject = subjectSelect ? subjectSelect.value : '';

        if (!classValue) {
            testSelect.disabled = true;
            testSelect.innerHTML = '<option value="">Select Test</option>';
            const testInfoMsg = document.getElementById('testInfoMsg');
            if (testInfoMsg) testInfoMsg.style.display = 'none';
            return;
        }

        testSelect.disabled = false;
        testSelect.innerHTML = '<option value="">Loading...</option>';

        const params = new URLSearchParams();
        if (campus) params.append('campus', campus);
        if (classValue) params.append('class', classValue);
        if (section) params.append('section', section);
        if (subject) params.append('subject', subject);

        marksFetch(`{{ route('test.marks-entry.get-tests') }}?${params.toString()}`)
            .then(data => {
                testSelect.innerHTML = '<option value="">Select Test</option>';
                const testInfoMsg = document.getElementById('testInfoMsg');
                if (data.tests && data.tests.length > 0) {
                    data.tests.forEach(test => {
                        const option = document.createElement('option');
                        option.value = test;
                        option.textContent = test;
                        if (selectedTestToKeep && test === selectedTestToKeep) {
                            option.selected = true;
                        }
                        testSelect.appendChild(option);
                    });
                    if (testInfoMsg) testInfoMsg.style.display = 'none';
                } else if (testInfoMsg) {
                    testInfoMsg.style.display = 'block';
                }
            })
            .catch(error => {
                console.error('Error loading tests:', error);
                testSelect.innerHTML = '<option value="">Error loading tests</option>';
            });
    }

    function loadSubjects(selectedSubjectToKeep) {
        if (!subjectSelect) return;

        const campus = campusSelect ? campusSelect.value : '';
        const classValue = classSelect ? classSelect.value : '';
        const section = sectionSelect ? sectionSelect.value : '';

        if (!classValue) {
            subjectSelect.disabled = true;
            subjectSelect.innerHTML = '<option value="">Select Subject</option>';
            return;
        }

        subjectSelect.disabled = false;
        subjectSelect.innerHTML = '<option value="">Loading...</option>';

        const params = new URLSearchParams();
        if (campus) params.append('campus', campus);
        if (classValue) params.append('class', classValue);
        if (section) params.append('section', section);

        marksFetch(`{{ route('test.marks-entry.get-subjects') }}?${params.toString()}`)
            .then(data => {
                subjectSelect.innerHTML = '<option value="">Select Subject</option>';
                const subjectList = Array.isArray(data.subjects)
                    ? data.subjects
                    : (data.subjects ? Object.values(data.subjects) : []);
                if (subjectList.length > 0) {
                    const uploadableKeys = (Array.isArray(data.uploadable_subjects)
                        ? data.uploadable_subjects
                        : (data.uploadable_subjects ? Object.values(data.uploadable_subjects) : subjectList))
                        .map(s => String(s).toLowerCase().trim());
                    subjectList.forEach(subject => {
                        const option = document.createElement('option');
                        option.value = subject;
                        if (isStaffMarksUser) {
                            const canEdit = uploadableKeys.includes(String(subject).toLowerCase().trim());
                            option.textContent = subject + (canEdit ? ' (Edit)' : ' (View only)');
                        } else {
                            option.textContent = subject;
                        }
                        if (selectedSubjectToKeep && subject === selectedSubjectToKeep) {
                            option.selected = true;
                        }
                        subjectSelect.appendChild(option);
                    });
                    marksUploadableKeys = uploadableKeys;
                    applyMarksEditMode();
                }
            })
            .catch(error => {
                console.error('Error loading subjects:', error);
                subjectSelect.innerHTML = '<option value="">Select Subject</option>';
            });
    }

    if (campusSelect) {
        campusSelect.addEventListener('change', function() {
            resetDownstream('campus');
            if (this.value) {
                loadClasses();
            }
        });
    }

    if (classSelect) {
        classSelect.addEventListener('change', function() {
            const selectedClass = this.value;
            resetDownstream('class');
            if (selectedClass) {
                if (sectionSelect) sectionSelect.disabled = false;
                if (subjectSelect) subjectSelect.disabled = false;
                if (testSelect) testSelect.disabled = false;
                loadSections(selectedClass);
                loadSubjects();
                loadTests();
            }
        });
    }

    if (sectionSelect) {
        sectionSelect.addEventListener('change', function() {
            loadSubjects();
            loadTests();
        });
    }

    if (subjectSelect) {
        subjectSelect.addEventListener('change', function() {
            applyMarksEditMode();
            loadTests();
        });
    }

    // Initial / restored filters: load classes when campus is set
    if (campusSelect && campusSelect.value) {
        if (classSelect && classSelect.options.length <= 1) {
            loadClasses(preservedClass);
        } else if (preservedClass) {
            loadSubjects(preservedSubject);
            loadTests(preservedTest);
        }
    }

    // Search functionality
    const searchInput = document.getElementById('searchInput');
    const clearSearchBtn = document.getElementById('clearSearchBtn');
    const studentsTableBody = document.getElementById('studentsTableBody');

    if (searchInput && studentsTableBody) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const studentRows = studentsTableBody.querySelectorAll('.student-row');
            
            if (searchTerm.length > 0) {
                clearSearchBtn.style.display = 'inline-block';
            } else {
                clearSearchBtn.style.display = 'none';
            }

            studentRows.forEach(row => {
                const studentName = row.dataset.studentName || '';
                const studentCode = row.dataset.studentCode || '';
                
                if (studentName.includes(searchTerm) || studentCode.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });

        clearSearchBtn.addEventListener('click', function() {
            searchInput.value = '';
            searchInput.dispatchEvent(new Event('input'));
        });
    }

    function normalizeNumberInput(value) {
        if (value === null || value === undefined || value === '') {
            return '';
        }
        const parsed = parseFloat(value);
        if (Number.isNaN(parsed)) {
            return value;
        }
        if (Number.isInteger(parsed)) {
            return parsed.toString();
        }
        return parsed.toString();
    }

    function setupBulkMarksSync(inputClass) {
        const inputs = Array.from(document.querySelectorAll(inputClass));
        if (!inputs.length) return;

        inputs.forEach((input) => {
            input.value = normalizeNumberInput(input.value);
        });

        const masterInput = inputs[0];
        masterInput.addEventListener('input', function () {
            const normalized = normalizeNumberInput(this.value);
            this.value = normalized;
            inputs.forEach((input, index) => {
                if (index === 0) return;
                input.value = normalized;
            });
        });
    }

    setupBulkMarksSync('.marks-total');
    setupBulkMarksSync('.marks-passing');

    const obtainedInputs = Array.from(document.querySelectorAll('.marks-obtained'));
    obtainedInputs.forEach((input) => {
        input.value = normalizeNumberInput(input.value);
        input.addEventListener('input', function () {
            this.value = normalizeNumberInput(this.value);
        });
    });

    applyMarksEditMode();
});
</script>
@endsection
