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
            <form action="#" method="POST" id="timetableForm">
                @csrf
                <div class="row g-2 mb-3 align-items-end">
                    <!-- Class -->
                    <div class="col-md-3">
                        <label for="class" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Class</label>
                        <select class="form-select form-select-sm" id="class" name="class" style="height: 32px;">
                            <option value="">Select Class</option>
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
                        <select class="form-select form-select-sm" id="exam" name="exam" style="height: 32px;">
                            <option value="">Select Exam</option>
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
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const classSelect = document.getElementById('class');
    const sectionSelect = document.getElementById('section');
    const subjectSelect = document.getElementById('subject');

    function loadSections(selectedClass) {
        if (selectedClass) {
            sectionSelect.disabled = false;
            sectionSelect.innerHTML = '<option value="">Loading...</option>';
            
            fetch(`{{ route('exam.timetable.get-sections') }}?class=${encodeURIComponent(selectedClass)}`)
                .then(response => response.json())
                .then(data => {
                    sectionSelect.innerHTML = '<option value="">Select Section</option>';
                    data.forEach(section => {
                        sectionSelect.innerHTML += `<option value="${section}">${section}</option>`;
                    });
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
