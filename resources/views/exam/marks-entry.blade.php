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
                    Select <strong>Campus</strong>, then <strong>Class</strong>, <strong>Section</strong>, <strong>Exam</strong>, and <strong>Subject</strong>. You can save marks only for subjects marked <strong>(Edit)</strong>.
                </div>
            @endif

            <!-- Filter Form - Flow: Campus → Class → Section → Exam → Subject -->
            <form action="{{ route('exam.marks-entry') }}" method="GET" id="filterForm">
                <div class="row g-2 mb-3 align-items-end">
                    <!-- 1. Campus -->
                    <div class="col-md-2">
                        <label for="filter_campus" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Campus</label>
                        <select class="form-select form-select-sm" id="filter_campus" name="filter_campus" style="height: 32px;">
                            <option value="">Select Campus</option>
                            @foreach($campuses as $campus)
                                @php
                                    $campusName = is_object($campus) ? ($campus->campus_name ?? $campus->name ?? '') : $campus;
                                @endphp
                                <option value="{{ $campusName }}" {{ $filterCampus == $campusName ? 'selected' : '' }}>{{ $campusName }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- 2. Class (loads when campus selected) -->
                    <div class="col-md-2">
                        <label for="filter_class" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Class</label>
                        <select class="form-select form-select-sm" id="filter_class" name="filter_class" style="height: 32px;" {{ !$filterCampus ? 'disabled' : '' }}>
                            <option value="">Select Class</option>
                            @if($filterCampus && ($filterClasses ?? $classes)->isNotEmpty())
                                @foreach($filterClasses ?? $classes as $className)
                                    <option value="{{ $className }}" {{ $filterClass == $className ? 'selected' : '' }}>{{ $className }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>

                    <!-- 3. Section (loads when class selected) -->
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

                    <!-- 4. Exam (loads when campus selected) -->
                    <div class="col-md-2">
                        <label for="filter_exam" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Exam</label>
                        <select class="form-select form-select-sm" id="filter_exam" name="filter_exam" style="height: 32px;" {{ !$filterCampus ? 'disabled' : '' }}>
                            <option value="">Select Exam</option>
                            @if($filterCampus && isset($exams) && $exams->count() > 0)
                                @foreach($exams as $examName)
                                    <option value="{{ $examName }}" {{ $filterExam == $examName ? 'selected' : '' }}>{{ $examName }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>

                    <!-- 5. Subject (loads when class + section selected) -->
                    <div class="col-md-2">
                        <label for="filter_subject" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Subject</label>
                        <select class="form-select form-select-sm" id="filter_subject" name="filter_subject" style="height: 32px;" {{ !$filterClass || !$filterSection ? 'disabled' : '' }}>
                            <option value="">Select Subject</option>
                            @if($filterClass && $filterSection && $subjects->isNotEmpty())
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
            @if($filterClass && $filterSection)
            <div class="mt-3">
                @if(!empty($isStaffMarksUser))
                    @if($filterSubject)
                        @if(!empty($canUploadMarks))
                            <div class="alert alert-info py-2 mb-3" style="font-size: 13px;">
                                <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">edit</span>
                                You can enter marks for <strong>{{ $filterSubject }}</strong> (assigned to you in Manage Subjects).
                            </div>
                        @else
                            <div class="alert alert-warning py-2 mb-3" style="font-size: 13px;">
                                <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">visibility</span>
                                View only: <strong>{{ $filterSubject }}</strong> is not assigned to you. Marks cannot be changed.
                            </div>
                        @endif
                    @else
                        <div class="alert alert-info py-2 mb-3" style="font-size: 13px;">
                            Select a subject. You can save marks only for subjects marked <strong>(Edit)</strong> in the dropdown.
                        </div>
                    @endif
                @endif

                @php $marksReadonly = isset($canUploadMarks) && !$canUploadMarks; @endphp

                <!-- Context Card -->
                <div class="card mb-3" style="background-color: #f8f9fa; border: 1px solid #e9ecef;">
                    <div class="card-body p-3">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <div class="d-flex flex-column gap-1">
                                    @if($filterExam)
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="fw-semibold" style="color: #003471;">Exam:</span>
                                            <span style="color: #495057;">{{ $filterExam }}</span>
                                        </div>
                                    @endif
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="fw-semibold" style="color: #003471;">Class:</span>
                                        <span style="color: #495057;">{{ $filterClass }}</span>
                                        @if($filterSection)
                                            <span class="fw-semibold" style="color: #003471; margin-left: 10px;">Section:</span>
                                            <span style="color: #495057;">{{ $filterSection }}</span>
                                        @endif
                                    </div>
                                    @if($filterSubject)
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="fw-semibold" style="color: #003471;">Subject:</span>
                                            <span style="color: #495057;">{{ $filterSubject }}</span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-4 text-end">
                                <span class="material-symbols-outlined" style="font-size: 48px; color: #dee2e6; opacity: 0.5;">bar_chart</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Navigation Guide Banner -->
                <div class="alert alert-warning mb-3 d-flex align-items-center gap-2" style="background-color: #fff3cd; border-color: #ffc107; color: #856404;">
                    <span class="material-symbols-outlined" style="font-size: 20px;">info</span>
                    <span style="font-size: 13px;"><strong>Navigation Guide:</strong> Use ← (Left Arrow), → (Right Arrow), ↑ (Up Arrow), and ↓ (Down Arrow) to navigate between input fields.</span>
                </div>
                
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
                        <form id="marksForm" method="POST" action="{{ route('exam.marks-entry.save') }}">
                            @csrf
                            <input type="hidden" name="campus" value="{{ request('filter_campus') ?? $filterCampus ?? '' }}" required>
                            <input type="hidden" name="exam_name" value="{{ request('filter_exam') ?? $filterExam ?? '' }}" required>
                            <input type="hidden" name="class" value="{{ request('filter_class') ?? $filterClass ?? '' }}" required>
                            <input type="hidden" name="section" value="{{ request('filter_section') ?? $filterSection ?? '' }}">
                            <input type="hidden" name="subject" value="{{ request('filter_subject') ?? $filterSubject ?? '' }}" required>
                            
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
                                    <tr class="student-row" data-student-id="{{ $student->id }}" data-student-name="{{ strtolower($student->student_name) }}" data-student-code="{{ strtolower($student->student_code ?? '') }}">
                                        <td>{{ $index + 1 }}</td>
                                        <td>{{ $student->student_code ?? $student->gr_number ?? 'N/A' }}</td>
                                        <td>
                                            <strong class="text-primary">{{ $student->student_name }}</strong>
                                        </td>
                                        <td>{{ $student->father_name ?? 'N/A' }}</td>
                                        <td>
                                            <input type="number" name="marks[{{ $student->id }}][obtained]" class="form-control form-control-sm marks-obtained" placeholder="0" min="0" step="1" value="{{ $student->mark && $student->mark->marks_obtained !== null ? $student->mark->marks_obtained : '' }}" style="width: 100px;" @if($marksReadonly) readonly disabled @endif>
                                        </td>
                                        <td>
                                            <input type="number" name="marks[{{ $student->id }}][total]" class="form-control form-control-sm marks-total" placeholder="0" min="0" step="1" value="{{ $student->mark && $student->mark->total_marks !== null ? $student->mark->total_marks : '' }}" style="width: 100px;" @if($marksReadonly) readonly disabled @endif>
                                        </td>
                                        <td>
                                            <input type="number" name="marks[{{ $student->id }}][passing]" class="form-control form-control-sm marks-passing" placeholder="0" min="0" step="1" value="{{ $student->mark && $student->mark->passing_marks !== null ? $student->mark->passing_marks : '' }}" style="width: 100px;" @if($marksReadonly) readonly disabled @endif>
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
                <p class="text-muted mt-3 mb-0">Please select Class and Section to view students for marks entry</p>
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
    border: 1px solid #dee2e6;
}

.default-table-area table {
    margin-bottom: 0;
    border-spacing: 0;
    border-collapse: collapse;
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

.default-table-area table tbody td {
    padding: 8px 12px;
    font-size: 13px;
    vertical-align: middle;
    line-height: 1.4;
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
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const isStaffMarksUser = {{ !empty($isStaffMarksUser) ? 'true' : 'false' }};
    const campusSelect = document.getElementById('filter_campus');
    const classSelect = document.getElementById('filter_class');
    const sectionSelect = document.getElementById('filter_section');
    const subjectSelect = document.getElementById('filter_subject');
    const examSelect = document.getElementById('filter_exam');
    const preservedClass = @json($filterClass ?? '');
    const preservedSection = @json($filterSection ?? '');
    const preservedSubject = @json($filterSubject ?? '');
    const preservedExam = @json($filterExam ?? '');

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

    // Flow: Campus → Class → Section → Exam → Subject
    function loadClasses(selectedClassToKeep) {
        if (!classSelect) return;
        const campus = campusSelect ? campusSelect.value : '';
        if (!campus) {
            classSelect.disabled = true;
            classSelect.innerHTML = '<option value="">Select Class</option>';
            if (sectionSelect) {
                sectionSelect.disabled = true;
                sectionSelect.innerHTML = '<option value="">Select Section</option>';
            }
            if (subjectSelect) {
                subjectSelect.disabled = true;
                subjectSelect.innerHTML = '<option value="">Select Subject</option>';
            }
            if (examSelect) {
                examSelect.disabled = true;
                examSelect.innerHTML = '<option value="">Select Exam</option>';
            }
            return;
        }
        classSelect.disabled = false;
        classSelect.innerHTML = '<option value="">Loading...</option>';
        marksFetch(`{{ route('exam.marks-entry.get-classes') }}?campus=${encodeURIComponent(campus)}`)
            .then(data => {
                classSelect.innerHTML = '<option value="">Select Class</option>';
                (data.classes || []).forEach(className => {
                    const opt = document.createElement('option');
                    opt.value = className;
                    opt.textContent = className;
                    if (selectedClassToKeep && className === selectedClassToKeep) {
                        opt.selected = true;
                    }
                    classSelect.appendChild(opt);
                });
                classSelect.disabled = !(data.classes && data.classes.length > 0);
                if (selectedClassToKeep && classSelect.value === selectedClassToKeep) {
                    loadSections(selectedClassToKeep, preservedSection);
                }
            })
            .catch(() => {
                classSelect.innerHTML = '<option value="">Select Class</option>';
            });
    }

    function loadExams() {
        if (!examSelect) return;
        const campus = campusSelect ? campusSelect.value : '';
        if (!campus) {
            examSelect.disabled = true;
            examSelect.innerHTML = '<option value="">Select Exam</option>';
            return;
        }
        examSelect.disabled = false;
        examSelect.innerHTML = '<option value="">Loading...</option>';
        marksFetch(`{{ route('exam.marks-entry.get-exams') }}?campus=${encodeURIComponent(campus)}`)
            .then(data => {
                examSelect.innerHTML = '<option value="">Select Exam</option>';
                (data.exams || []).forEach(exam => {
                    const option = document.createElement('option');
                    option.value = exam;
                    option.textContent = exam;
                    if (preservedExam && exam === preservedExam) {
                        option.selected = true;
                    }
                    examSelect.appendChild(option);
                });
            })
            .catch(() => {
                examSelect.innerHTML = '<option value="">Select Exam</option>';
            });
    }

    // Class change → load sections, clear section & subject
    if (classSelect) {
        classSelect.addEventListener('change', function() {
            const selectedClass = this.value;
            if (sectionSelect) {
                sectionSelect.disabled = !selectedClass;
                sectionSelect.innerHTML = '<option value="">Select Section</option>';
            }
            if (subjectSelect) {
                subjectSelect.disabled = true;
                subjectSelect.innerHTML = '<option value="">Select Subject</option>';
            }
            if (selectedClass) loadSections(selectedClass);
        });
    }

    // Section change → enable subject, load subjects
    if (sectionSelect) {
        sectionSelect.addEventListener('change', function() {
            const hasSection = this.value && classSelect && classSelect.value;
            if (subjectSelect) {
                subjectSelect.disabled = !hasSection;
                if (!hasSection) subjectSelect.innerHTML = '<option value="">Select Subject</option>';
                else loadSubjects();
            }
        });
    }

    // Exam change → reload subjects (for exam timetable filter)
    if (examSelect) {
        examSelect.addEventListener('change', function() {
            if (classSelect && classSelect.value && sectionSelect && sectionSelect.value) loadSubjects();
        });
    }

    // Campus change → load classes & exams, clear class/section/subject
    if (campusSelect) {
        campusSelect.addEventListener('change', function() {
            loadClasses();
            loadExams();
            if (classSelect) {
                classSelect.value = '';
                if (sectionSelect) {
                    sectionSelect.innerHTML = '<option value="">Select Section</option>';
                    sectionSelect.disabled = true;
                }
                if (subjectSelect) {
                    subjectSelect.innerHTML = '<option value="">Select Subject</option>';
                    subjectSelect.disabled = true;
                }
            }
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
        marksFetch(`{{ route('exam.marks-entry.get-sections') }}?${params.toString()}`)
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
                if (selectedSectionToKeep && sectionSelect.value === selectedSectionToKeep) {
                    loadSubjects(preservedSubject);
                }
            })
                .catch(error => {
                console.error('Error loading sections:', error);
                sectionSelect.innerHTML = '<option value="">Error loading sections</option>';
                sectionSelect.disabled = true;
            });
    }

    function loadSubjects(selectedSubjectToKeep) {
        if (!subjectSelect) return;
        
        const classValue = classSelect ? classSelect.value : '';
        const section = sectionSelect ? sectionSelect.value : '';
        const exam = examSelect ? examSelect.value : '';
        
        if (!classValue || !section) {
            subjectSelect.innerHTML = '<option value="">Select Subject</option>';
            return;
        }
        
        subjectSelect.innerHTML = '<option value="">Loading...</option>';
        
        const params = new URLSearchParams();
        if (classValue) params.append('class', classValue);
        if (section) params.append('section', section);
        if (campusSelect && campusSelect.value) params.append('campus', campusSelect.value);
        if (exam) params.append('exam', exam);

        marksFetch(`{{ route('exam.marks-entry.get-subjects') }}?${params.toString()}`)
            .then(data => {
                subjectSelect.innerHTML = '<option value="">Select Subject</option>';
                if (data.subjects && data.subjects.length > 0) {
                    const uploadableKeys = (data.uploadable_subjects || data.subjects || [])
                        .map(s => String(s).toLowerCase().trim());
                    data.subjects.forEach(subject => {
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
                } else {
                    // If no subjects found, show a message
                    const option = document.createElement('option');
                    option.value = '';
                    option.textContent = 'No subjects found';
                    option.disabled = true;
                    subjectSelect.appendChild(option);
                }
            })
            .catch(error => {
                console.error('Error loading subjects:', error);
                subjectSelect.innerHTML = '<option value="">Error loading subjects</option>';
            });
    }

    if (campusSelect && campusSelect.value) {
        if (classSelect && classSelect.options.length <= 1) {
            loadClasses(preservedClass);
        }
        if (examSelect && examSelect.options.length <= 1) {
            loadExams();
        }
        if (preservedClass && sectionSelect && sectionSelect.options.length <= 1) {
            loadSections(preservedClass, preservedSection);
        } else if (preservedClass && preservedSection && subjectSelect && subjectSelect.options.length <= 1) {
            loadSubjects(preservedSubject);
        }
    } else {
        if (sectionSelect) sectionSelect.disabled = true;
        if (subjectSelect) subjectSelect.disabled = true;
        if (examSelect) examSelect.disabled = true;
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

    // Form submission handler
    const marksForm = document.getElementById('marksForm');
    if (marksForm) {
        marksForm.addEventListener('submit', function(e) {
            // Check if exam_name, class, and subject are filled
            const examName = this.querySelector('input[name="exam_name"]').value;
            const className = this.querySelector('input[name="class"]').value;
            const subject = this.querySelector('input[name="subject"]').value;
            
            if (!examName || !className || !subject) {
                e.preventDefault();
                alert('Please select Exam Name, Class, and Subject before saving marks.');
                return false;
            }
            
            // Check if at least one mark is entered
            const marksInputs = this.querySelectorAll('input[name*="[obtained]"], input[name*="[total]"], input[name*="[passing]"]');
            let hasMarks = false;
            marksInputs.forEach(input => {
                if (input.value && input.value.trim() !== '' && parseFloat(input.value) >= 0) {
                    hasMarks = true;
                }
            });
            
            if (!hasMarks) {
                e.preventDefault();
                alert('Please enter at least one mark (Obtained, Total, or Passing) for at least one student.');
                return false;
            }
            
            // Show loading state
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...';
            }
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

        // Normalize initial values to remove trailing .00
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
});
</script>
@endsection
