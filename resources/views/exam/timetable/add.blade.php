@extends('layouts.app')

@section('title', 'Add Exam Timetable')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-3 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0 fs-16 fw-semibold">Add Exam Timetable</h4>
            </div>

            <!-- Form -->
            <form action="{{ route('exam.timetable.store') }}" method="POST" id="timetableForm">
                @csrf
                <div class="row g-2 mb-3 align-items-end">
                    <!-- Campus -->
                    <div class="col-md-3">
                        <label for="campus" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Campus</label>
                        <select class="form-select form-select-sm" id="campus" name="campus" style="height: 32px;">
                            <option value="">Select Campus</option>
                            @foreach($campuses as $campus)
                                @php
                                    $campusName = is_object($campus) ? ($campus->campus_name ?? '') : $campus;
                                @endphp
                                <option value="{{ $campusName }}">{{ $campusName }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <!-- Class -->
                    <div class="col-md-3">
                        <label for="class" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Class</label>
                        <select class="form-select form-select-sm" id="class" name="class" style="height: 32px;" disabled>
                            <option value="">Select Campus First</option>
                            @foreach($classes as $className)
                                <option value="{{ $className }}">{{ $className }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Section -->
                    <div class="col-md-3">
                        <label for="section" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Section</label>
                        <select class="form-select form-select-sm" id="section" name="section" style="height: 32px;" disabled>
                            <option value="">Select a class first</option>
                        </select>
                    </div>

                    <!-- Subject -->
                    <div class="col-md-3">
                        <label for="subject" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Subject</label>
                        <select class="form-select form-select-sm" id="subject" name="subject" style="height: 32px;" disabled>
                            <option value="">Select a class first</option>
                        </select>
                    </div>

                    <!-- Exam -->
                    <div class="col-md-3">
                        <label for="exam" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Exam</label>
                        <select class="form-select form-select-sm" id="exam" name="exam" style="height: 32px;" disabled>
                            <option value="">Select Campus First</option>
                            @foreach($exams as $examName)
                                <option value="{{ $examName }}">{{ $examName }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Date -->
                    <div class="col-md-3">
                        <label for="date" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Date</label>
                        <input type="date" class="form-control form-control-sm" id="date" name="date" style="height: 32px;">
                    </div>

                    <!-- Starting Time -->
                    <div class="col-md-3">
                        <label for="starting_time" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Starting Time</label>
                        <input type="time" class="form-control form-control-sm" id="starting_time" name="starting_time" style="height: 32px;">
                    </div>

                    <!-- Ending Time -->
                    <div class="col-md-3">
                        <label for="ending_time" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Ending Time</label>
                        <input type="time" class="form-control form-control-sm" id="ending_time" name="ending_time" style="height: 32px;">
                    </div>

                    <!-- Room/Block -->
                    <div class="col-md-3">
                        <label for="room_block" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Room/Block</label>
                        <input type="text" class="form-control form-control-sm" id="room_block" name="room_block" placeholder="Enter Room/Block" style="height: 32px;">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12 text-end">
                        <button type="submit" class="btn btn-sm py-2 px-4 rounded-8" style="background: linear-gradient(135deg, #003471 0%, #004a9f 100%); color: white; border: none;">
                            <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">save</span>
                            <span style="font-size: 12px;">Save Timetable</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const campusSelect = document.getElementById('campus');
    const classSelect = document.getElementById('class');
    const sectionSelect = document.getElementById('section');
    const subjectSelect = document.getElementById('subject');
    const examSelect = document.getElementById('exam');

    function loadClasses(selectedCampus) {
        if (!classSelect) return;

        if (!selectedCampus) {
            classSelect.innerHTML = '<option value="">Select Campus First</option>';
            classSelect.disabled = true;
            sectionSelect.disabled = true;
            sectionSelect.innerHTML = '<option value="">Select a class first</option>';
            subjectSelect.disabled = true;
            subjectSelect.innerHTML = '<option value="">Select a class first</option>';
            examSelect.disabled = true;
            examSelect.innerHTML = '<option value="">Select Campus First</option>';
            return;
        }

        classSelect.innerHTML = '<option value="">Loading...</option>';
        classSelect.disabled = true;
        fetch(`{{ route('exam.timetable.get-classes') }}?campus=${encodeURIComponent(selectedCampus)}`)
            .then(response => response.json())
            .then(data => {
                classSelect.innerHTML = '<option value="">Select Class</option>';
                if (data.classes && data.classes.length > 0) {
                    data.classes.forEach(className => {
                        classSelect.innerHTML += `<option value="${className}">${className}</option>`;
                    });
                }
                classSelect.disabled = false;
            })
            .catch(error => {
                console.error('Error loading classes:', error);
                classSelect.innerHTML = '<option value="">Error loading classes</option>';
                classSelect.disabled = false;
            });

        loadExams(selectedCampus);
    }

    function loadExams(selectedCampus) {
        if (!examSelect) return;

        if (!selectedCampus) {
            examSelect.disabled = true;
            examSelect.innerHTML = '<option value="">Select Campus First</option>';
            return;
        }

        examSelect.disabled = true;
        examSelect.innerHTML = '<option value="">Loading...</option>';

        fetch(`{{ route('exam.timetable.get-exams') }}?campus=${encodeURIComponent(selectedCampus)}`)
            .then(response => response.json())
            .then(data => {
                examSelect.innerHTML = '<option value="">Select Exam</option>';
                data.forEach(exam => {
                    examSelect.innerHTML += `<option value="${exam}">${exam}</option>`;
                });
                examSelect.disabled = false;
            })
            .catch(error => {
                console.error('Error loading exams:', error);
                examSelect.innerHTML = '<option value="">Error loading exams</option>';
                examSelect.disabled = false;
            });
    }

    function loadSections(selectedClass) {
        if (selectedClass) {
            sectionSelect.disabled = false;
            sectionSelect.innerHTML = '<option value="">Loading...</option>';

            const params = new URLSearchParams();
            params.append('class', selectedClass);
            if (campusSelect && campusSelect.value) {
                params.append('campus', campusSelect.value);
            }
            
            fetch(`{{ route('exam.timetable.get-sections') }}?${params.toString()}`)
                .then(response => response.json())
                .then(data => {
                    sectionSelect.innerHTML = '<option value="">Select Section</option>';
                    if (data && data.length > 0) {
                        data.forEach(section => {
                            sectionSelect.innerHTML += `<option value="${section}">${section}</option>`;
                        });
                    } else {
                        sectionSelect.innerHTML = '<option value="">No sections found</option>';
                    }
                })
                .catch(error => {
                    console.error('Error loading sections:', error);
                    sectionSelect.innerHTML = '<option value="">Error loading sections</option>';
                });
        } else {
            sectionSelect.disabled = true;
            sectionSelect.innerHTML = '<option value="">Select a class first</option>';
            subjectSelect.disabled = true;
            subjectSelect.innerHTML = '<option value="">Select a class first</option>';
        }
    }

    function loadSubjects(selectedClass, selectedSection) {
        if (selectedClass) {
            subjectSelect.disabled = false;
            subjectSelect.innerHTML = '<option value="">Loading...</option>';
            
            const params = new URLSearchParams();
            if (selectedClass) params.append('class', selectedClass);
            if (selectedSection) params.append('section', selectedSection);
            if (campusSelect && campusSelect.value) params.append('campus', campusSelect.value);
            
            fetch(`{{ route('exam.timetable.get-subjects') }}?${params.toString()}`)
                .then(response => response.json())
                .then(data => {
                    subjectSelect.innerHTML = '<option value="">Select Subject</option>';
                    data.forEach(subject => {
                        subjectSelect.innerHTML += `<option value="${subject}">${subject}</option>`;
                    });
                })
                .catch(error => {
                    console.error('Error loading subjects:', error);
                    subjectSelect.innerHTML = '<option value="">Error loading subjects</option>';
                });
        } else {
            subjectSelect.disabled = true;
            subjectSelect.innerHTML = '<option value="">Select a class first</option>';
        }
    }

    if (campusSelect) {
        campusSelect.addEventListener('change', function() {
            loadClasses(this.value);
        });
    }

    classSelect.addEventListener('change', function() {
        loadSections(this.value);
        loadSubjects(this.value, sectionSelect.value);
    });

    sectionSelect.addEventListener('change', function() {
        loadSubjects(classSelect.value, this.value);
    });
});
</script>
@endsection
