@extends('layouts.app')

@section('title', 'Manage Exam Timetable')

@section('content')
<div class="row no-print">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-3 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0 fs-16 fw-semibold">Manage Exam Timetable</h4>
            </div>

            <!-- Filter Form -->
            <div class="card border-0 shadow-sm mb-3" style="border-radius: 8px; overflow: hidden;">
                <div class="card-body p-3">
                    <form action="{{ route('exam.timetable.manage') }}" method="GET" id="filterForm">
                        <div class="row g-3 align-items-end">
                            <!-- Campus -->
                            <div class="col-md-3">
                                <label for="filter_campus" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Campus</label>
                                <select class="form-select form-select-sm" id="filter_campus" name="filter_campus" style="height: 32px;">
                                    <option value="">All Campuses</option>
                                    @foreach($campuses as $campus)
                                        <option value="{{ $campus }}" {{ $filterCampus == $campus ? 'selected' : '' }}>{{ $campus }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Exam -->
                            <div class="col-md-3">
                                <label for="filter_exam" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Exam</label>
                                <select class="form-select form-select-sm" id="filter_exam" name="filter_exam" style="height: 32px;">
                                    <option value="">All Exams</option>
                                    @foreach($exams as $examName)
                                        <option value="{{ $examName }}" {{ $filterExam == $examName ? 'selected' : '' }}>{{ $examName }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Class -->
                            <div class="col-md-2">
                                <label for="filter_class" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Class</label>
                                <select class="form-select form-select-sm" id="filter_class" name="filter_class" style="height: 32px;">
                                    <option value="">All Classes</option>
                                    @foreach(($filterClasses ?? $classes) as $className)
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

                            <!-- Filter Button -->
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-sm w-100 filter-btn" style="height: 32px;">
                                    <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">filter_list</span>
                                    <span>Filter</span>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@if($filterCampus || $filterExam || $filterClass || $filterSection)
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-3 mb-4">
            <div class="mb-2 p-2 rounded-8 d-flex align-items-center justify-content-between" style="background: linear-gradient(135deg, #003471 0%, #004a9f 100%);">
                <h5 class="mb-0 text-white fs-15 fw-semibold d-flex align-items-center gap-2">
                    <span class="material-symbols-outlined" style="font-size: 18px;">list</span>
                    <span>Exam Timetable</span>
                </h5>
                <button type="button" class="btn btn-sm btn-light no-print" onclick="printTimetable()" style="height: 28px;">
                    <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">print</span>
                    <span style="font-size: 11px;">Print</span>
                </button>
            </div>
            <div class="card border-0 shadow-sm mb-3" style="border-radius: 8px; overflow: hidden;">
                <div class="card-body p-3">
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered table-hover" style="white-space: nowrap;">
                            <thead style="background: linear-gradient(135deg, #003471 0%, #004a9f 100%); color: white;">
                                <tr>
                                    <th style="padding: 8px 12px; font-size: 13px; font-weight: 600;">Date</th>
                                    <th style="padding: 8px 12px; font-size: 13px; font-weight: 600;">Day</th>
                                    <th style="padding: 8px 12px; font-size: 13px; font-weight: 600;">Subject</th>
                                    <th style="padding: 8px 12px; font-size: 13px; font-weight: 600;">Starts At</th>
                                    <th style="padding: 8px 12px; font-size: 13px; font-weight: 600;">Ends At</th>
                                    <th style="padding: 8px 12px; font-size: 13px; font-weight: 600;">Total Time</th>
                                    <th style="padding: 8px 12px; font-size: 13px; font-weight: 600;">Room/Block</th>
                                    <th class="no-print" style="padding: 8px 12px; font-size: 13px; font-weight: 600;">Actions</th>
                                </tr>
                            </thead>
                        <tbody>
                            @forelse($timetables as $item)
                                @php
                                    $start = \Carbon\Carbon::createFromFormat('H:i:s', $item->starting_time);
                                    $end = \Carbon\Carbon::createFromFormat('H:i:s', $item->ending_time);
                                    if ($end->lessThan($start)) {
                                        $end = $end->addDay();
                                    }
                                    $diff = $start->diff($end);
                                    $totalTime = sprintf('%02d:%02d', ($diff->h + ($diff->d * 24)), $diff->i);
                                @endphp
                                <tr data-timetable-id="{{ $item->id }}" style="height: 60px;">
                                    <td style="padding: 8px 12px; font-size: 13px; height: 60px; vertical-align: middle;">{{ \Carbon\Carbon::parse($item->exam_date)->format('d M Y') }}</td>
                                    <td style="padding: 8px 12px; font-size: 13px; height: 60px; vertical-align: middle;">{{ \Carbon\Carbon::parse($item->exam_date)->format('l') }}</td>
                                    <td style="padding: 8px 12px; font-size: 13px; height: 60px; vertical-align: middle;">
                                        <strong>{{ $item->subject }}</strong>
                                    </td>
                                    <td style="padding: 8px 12px; font-size: 13px; height: 60px; vertical-align: middle;">{{ \Carbon\Carbon::createFromFormat('H:i:s', $item->starting_time)->format('H:i') }}</td>
                                    <td style="padding: 8px 12px; font-size: 13px; height: 60px; vertical-align: middle;">{{ \Carbon\Carbon::createFromFormat('H:i:s', $item->ending_time)->format('H:i') }}</td>
                                    <td style="padding: 8px 12px; font-size: 13px; height: 60px; vertical-align: middle;">{{ $totalTime }}</td>
                                    <td style="padding: 8px 12px; font-size: 13px; height: 60px; vertical-align: middle;">{{ $item->room_block ?? 'N/A' }}</td>
                                    <td class="no-print" style="padding: 8px 12px; font-size: 13px; height: 60px; vertical-align: middle;">
                                        <div class="d-flex gap-1">
                                            <button type="button" class="btn btn-sm btn-outline-primary edit-timetable-btn" 
                                                    data-id="{{ $item->id }}"
                                                    data-date="{{ \Carbon\Carbon::parse($item->exam_date)->format('Y-m-d') }}"
                                                    data-starting-time="{{ \Carbon\Carbon::createFromFormat('H:i:s', $item->starting_time)->format('H:i') }}"
                                                    data-ending-time="{{ \Carbon\Carbon::createFromFormat('H:i:s', $item->ending_time)->format('H:i') }}"
                                                    data-room-block="{{ $item->room_block ?? '' }}"
                                                    title="Edit"
                                                    style="font-size: 12px; padding: 4px 8px;">
                                                <span class="material-symbols-outlined" style="font-size: 14px;">edit</span>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-danger delete-timetable-btn" 
                                                    data-id="{{ $item->id }}"
                                                    data-subject="{{ $item->subject }}"
                                                    title="Delete"
                                                    style="font-size: 12px; padding: 4px 8px;">
                                                <span class="material-symbols-outlined" style="font-size: 14px; color: white;">delete</span>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">No timetable entries found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Notify to Parent Section -->
            <div class="d-flex justify-content-between align-items-center mb-3">
                <!-- Notify to Parent Section -->
                <div class="d-flex align-items-center gap-3">
                    <div class="d-flex align-items-center gap-2">
                        <span class="material-symbols-outlined" style="font-size: 20px; color: #003471;">notifications</span>
                        <label class="form-label mb-0 fs-14 fw-semibold" style="color: #003471;">Notify to Parent:</label>
                    </div>
                    <select class="form-select form-select-sm" id="notifyToParent" name="notify_to_parent" style="width: 100px;">
                        <option value="No" {{ (isset($notifyToParent) && $notifyToParent == 'No') ? 'selected' : 'selected' }}>No</option>
                        <option value="Yes" {{ (isset($notifyToParent) && $notifyToParent == 'Yes') ? 'selected' : '' }}>Yes</option>
                    </select>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

<!-- Edit Timetable Modal -->
<div class="modal fade" id="editTimetableModal" tabindex="-1" aria-labelledby="editTimetableModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #003471 0%, #004a9f 100%);">
                <h5 class="modal-title text-white" id="editTimetableModalLabel">Edit Timetable</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editTimetableForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_date" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Date</label>
                        <input type="date" class="form-control form-control-sm" id="edit_date" name="date" required style="height: 32px;">
                    </div>
                    <div class="mb-3">
                        <label for="edit_starting_time" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Starting Time</label>
                        <input type="time" class="form-control form-control-sm" id="edit_starting_time" name="starting_time" required style="height: 32px;">
                    </div>
                    <div class="mb-3">
                        <label for="edit_ending_time" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Ending Time</label>
                        <input type="time" class="form-control form-control-sm" id="edit_ending_time" name="ending_time" required style="height: 32px;">
                    </div>
                    <div class="mb-3">
                        <label for="edit_room_block" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Room/Block</label>
                        <input type="text" class="form-control form-control-sm" id="edit_room_block" name="room_block" style="height: 32px;">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
/* Filter Form Styling */
.form-select-sm {
    font-size: 13px;
    padding: 6px 12px;
    border-radius: 6px;
    border: 1px solid #dee2e6;
    transition: all 0.3s ease;
    height: 32px;
}

.form-select-sm:focus {
    border-color: #003471;
    box-shadow: 0 0 0 3px rgba(0, 52, 113, 0.15);
    outline: none;
}

.filter-btn {
    background: linear-gradient(135deg, #003471 0%, #004a9f 100%);
    color: white;
    border: none;
    font-weight: 500;
    transition: all 0.3s ease;
    box-shadow: 0 2px 6px rgba(0, 52, 113, 0.2);
    height: 32px;
}

.filter-btn:hover {
    background: linear-gradient(135deg, #004a9f 0%, #003471 100%);
    transform: translateY(-1px);
    box-shadow: 0 4px 10px rgba(0, 52, 113, 0.3);
    color: white;
}

.filter-btn:active {
    transform: translateY(0);
}

.filter-btn .material-symbols-outlined {
    color: white !important;
}

.rounded-8 {
    border-radius: 8px;
}

/* Table Styling */
.table thead th {
    border: none;
    white-space: nowrap;
}

.table tbody td {
    vertical-align: middle;
}

.no-print {
    display: block;
}

@media print {
    .no-print {
        display: none !important;
    }
    .sidebar-area,
    .header-area,
    .main-content .header-area,
    .main-content-container ~ .flex-grow-1,
    .main-content-container ~ .footer-area,
    .main-content-container ~ footer,
    .theme-settings-area,
    .theme-settings,
    .settings-btn,
    .btn {
        display: none !important;
    }
    .container-fluid,
    .main-content,
    .main-content-container {
        margin: 0 !important;
        padding: 0 !important;
    }
    body {
        background: #fff !important;
    }
    .card {
        border: none !important;
        box-shadow: none !important;
    }
}
</style>

<script>
function printTimetable() {
    window.print();
}

document.addEventListener('DOMContentLoaded', function() {
    // Restore notify to parent preference
    const savedPreference = localStorage.getItem('examTimetableNotifyParent');
    if (savedPreference) {
        const notifySelect = document.getElementById('notifyToParent');
        if (notifySelect) {
            notifySelect.value = savedPreference === 'yes' ? 'Yes' : 'No';
        }
    }
    
    // Save preference when dropdown changes
    const notifySelect = document.getElementById('notifyToParent');
    if (notifySelect) {
        notifySelect.addEventListener('change', function() {
            const value = this.value.toLowerCase();
            localStorage.setItem('examTimetableNotifyParent', value);
        });
    }
    
    const campusSelect = document.getElementById('filter_campus');
    const examSelect = document.getElementById('filter_exam');
    const classSelect = document.getElementById('filter_class');
    const sectionSelect = document.getElementById('filter_section');
    const allClassOptions = classSelect ? classSelect.innerHTML : '';

    function loadExams() {
        const campus = campusSelect.value;
        
        examSelect.innerHTML = '<option value="">Loading...</option>';
        
        fetch(`{{ route('exam.timetable.get-exams-manage') }}?campus=${encodeURIComponent(campus)}`)
            .then(response => response.json())
            .then(data => {
                examSelect.innerHTML = '<option value="">All Exams</option>';
                data.forEach(exam => {
                    examSelect.innerHTML += `<option value="${exam}">${exam}</option>`;
                });
            })
            .catch(error => {
                console.error('Error loading exams:', error);
                examSelect.innerHTML = '<option value="">Error loading exams</option>';
            });
    }

    function loadSections(selectedClass) {
        if (selectedClass) {
            sectionSelect.innerHTML = '<option value="">Loading...</option>';

            const params = new URLSearchParams();
            params.append('class', selectedClass);
            if (campusSelect.value) {
                params.append('campus', campusSelect.value);
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

    function loadClasses() {
        const campus = campusSelect.value;

        if (!campus) {
            classSelect.innerHTML = allClassOptions;
            classSelect.value = '';
            sectionSelect.innerHTML = '<option value="">All Sections</option>';
            return;
        }

        classSelect.innerHTML = '<option value="">Loading...</option>';
        fetch(`{{ route('exam.timetable.get-classes') }}?campus=${encodeURIComponent(campus)}`)
            .then(response => response.json())
            .then(data => {
                classSelect.innerHTML = '<option value="">All Classes</option>';
                if (data.classes && data.classes.length > 0) {
                    data.classes.forEach(className => {
                        classSelect.innerHTML += `<option value="${className}">${className}</option>`;
                    });
                }
                @if($filterClass)
                if (data.classes && data.classes.includes('{{ $filterClass }}')) {
                    classSelect.value = '{{ $filterClass }}';
                    loadSections(classSelect.value);
                }
                @endif
            })
            .catch(error => {
                console.error('Error loading classes:', error);
                classSelect.innerHTML = '<option value="">Error loading classes</option>';
            });
    }

    campusSelect.addEventListener('change', function() {
        loadExams();
        loadClasses();
    });
    classSelect.addEventListener('change', function() {
        loadSections(this.value);
    });

    if (campusSelect.value) {
        loadExams();
        loadClasses();
    }

    // Edit Timetable functionality
    const editModal = new bootstrap.Modal(document.getElementById('editTimetableModal'));
    const editForm = document.getElementById('editTimetableForm');
    let currentTimetableId = null;

    document.querySelectorAll('.edit-timetable-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            currentTimetableId = this.getAttribute('data-id');
            const date = this.getAttribute('data-date');
            const startingTime = this.getAttribute('data-starting-time');
            const endingTime = this.getAttribute('data-ending-time');
            const roomBlock = this.getAttribute('data-room-block');

            document.getElementById('edit_date').value = date;
            document.getElementById('edit_starting_time').value = startingTime;
            document.getElementById('edit_ending_time').value = endingTime;
            document.getElementById('edit_room_block').value = roomBlock || '';

            editForm.action = `/exam/timetable/${currentTimetableId}`;
            editModal.show();
        });
    });

    editForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || 
                         document.querySelector('input[name="_token"]')?.value;

        fetch(this.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            }
        })
        .then(response => {
            if (response.ok) {
                return response.json();
            }
            return response.json().then(err => Promise.reject(err));
        })
        .then(data => {
            editModal.hide();
            location.reload();
        })
        .catch(error => {
            console.error('Error:', error);
            const errorMessage = error.message || 'Error updating timetable. Please try again.';
            alert(errorMessage);
        });
    });

    // Delete Timetable functionality
    document.querySelectorAll('.delete-timetable-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const timetableId = this.getAttribute('data-id');
            const subject = this.getAttribute('data-subject');
            
            if (!confirm(`Are you sure you want to delete this exam timetable entry?\n\nSubject: ${subject}\n\nThis action cannot be undone.`)) {
                return;
            }

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || 
                             document.querySelector('input[name="_token"]')?.value;
            
            fetch(`{{ route('exam.timetable.destroy', ':id') }}`.replace(':id', timetableId), {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(err => Promise.reject(err));
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Remove the row from table
                    const row = btn.closest('tr');
                    if (row) {
                        row.remove();
                    }
                    
                    // Check if table is empty
                    const tbody = document.querySelector('tbody');
                    if (tbody) {
                        const remainingRows = tbody.querySelectorAll('tr[data-timetable-id]');
                        if (remainingRows.length === 0) {
                            tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted py-4">No timetable entries found.</td></tr>';
                        }
                    }
                    
                    // Show success message
                    alert(data.message || 'Exam timetable deleted successfully!');
                } else {
                    alert(data.message || 'Error deleting exam timetable.');
                }
            })
            .catch(error => {
                console.error('Error deleting timetable:', error);
                const errorMessage = error.message || 'Error deleting exam timetable. Please try again.';
                alert(errorMessage);
            });
        });
    });
});
</script>
@endsection
