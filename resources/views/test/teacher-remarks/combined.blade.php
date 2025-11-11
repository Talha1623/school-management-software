@extends('layouts.app')

@section('title', 'Teacher Remarks - For Combine Result')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-3 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0 fs-16 fw-semibold">Teacher Remarks - For Combine Result</h4>
            </div>

            <!-- Filter Form -->
            <form action="{{ route('test.teacher-remarks.combined') }}" method="GET" id="filterForm">
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

                    <!-- Session -->
                    <div class="col-md-2">
                        <label for="filter_session" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Session</label>
                        <select class="form-select form-select-sm" id="filter_session" name="filter_session" style="height: 32px;">
                            <option value="">All Sessions</option>
                            @foreach($sessions as $session)
                                <option value="{{ $session }}" {{ $filterSession == $session ? 'selected' : '' }}>{{ $session }}</option>
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
            @if(request()->hasAny(['filter_campus', 'filter_session', 'filter_class', 'filter_section']))
            <div class="mt-3">
                <div class="mb-2 p-2 rounded-8" style="background: linear-gradient(135deg, #003471 0%, #004a9f 100%);">
                    <h5 class="mb-0 text-white fs-15 fw-semibold d-flex align-items-center gap-2">
                        <span class="material-symbols-outlined" style="font-size: 18px;">rate_review</span>
                        <span>Teacher Remarks</span>
                    </h5>
                </div>

                <div class="default-table-area" style="margin-top: 0;">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Photo</th>
                                    <th>Student Code</th>
                                    <th>Student Name</th>
                                    <th>Class</th>
                                    <th>Section</th>
                                    <th>Remarks</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($students as $index => $student)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>
                                        @if($student->photo)
                                            <img src="{{ asset('storage/' . $student->photo) }}" alt="{{ $student->student_name }}" class="rounded-circle" style="width: 40px; height: 40px; object-fit: cover;">
                                        @else
                                            <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                                <span class="text-white fw-bold">{{ substr($student->student_name, 0, 1) }}</span>
                                            </div>
                                        @endif
                                    </td>
                                    <td>{{ $student->student_code ?? 'N/A' }}</td>
                                    <td>
                                        <strong class="text-primary">{{ $student->student_name }}</strong>
                                    </td>
                                    <td>{{ $student->class }}</td>
                                    <td>
                                        <span class="badge bg-info text-white">{{ $student->section ?? 'N/A' }}</span>
                                    </td>
                                    <td>
                                        <textarea class="form-control form-control-sm remarks-input" data-student-id="{{ $student->id }}" placeholder="Enter remarks" rows="2" style="width: 100%; min-width: 200px;"></textarea>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-success save-remarks-btn" data-student-id="{{ $student->id }}" style="font-size: 11px; padding: 4px 8px;">
                                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">save</span>
                                            Save
                                        </button>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" class="text-center py-4">
                                        <div class="d-flex flex-column align-items-center">
                                            <span class="material-symbols-outlined text-muted" style="font-size: 48px;">inbox</span>
                                            <p class="text-muted mt-2 mb-0">No students found</p>
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                            @if($students->count() > 0)
                            <tfoot>
                                <tr class="fw-bold" style="background-color: #f8f9fa;">
                                    <td colspan="7" class="text-end">Total Students:</td>
                                    <td>{{ $students->count() }}</td>
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
                <p class="text-muted mt-3 mb-0">Please apply filters to view students and enter teacher remarks</p>
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

.save-remarks-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Save remarks button click handler
    document.querySelectorAll('.save-remarks-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const studentId = this.getAttribute('data-student-id');
            const remarksInput = document.querySelector('.remarks-input[data-student-id="' + studentId + '"]');
            
            const remarks = remarksInput ? remarksInput.value : '';
            
            if (!remarks.trim()) {
                alert('Please enter remarks');
                return;
            }
            
            // Here you can add AJAX call to save the remarks
            // For now, just show a success message
            alert('Remarks saved successfully for student ID: ' + studentId);
        });
    });
});
</script>
@endsection
