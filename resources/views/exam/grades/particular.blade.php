@extends('layouts.app')

@section('title', 'Exam Grades - For Particular Exam')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-3 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0 fs-16 fw-semibold">Exam Grades - For Particular Exam</h4>
            </div>

            <!-- Filter Form -->
            <form action="{{ route('exam.grades.particular') }}" method="GET" id="filterForm">
                <div class="row g-2 mb-3 align-items-end">
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
                                <option value="{{ $examName }}" {{ request('filter_exam') == $examName ? 'selected' : '' }}>{{ $examName }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Session -->
                    <div class="col-md-3">
                        <label for="filter_session" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Session</label>
                        <select class="form-select form-select-sm" id="filter_session" name="filter_session" style="height: 32px;">
                            <option value="">All Sessions</option>
                            @foreach($sessions as $sessionName)
                                <option value="{{ $sessionName }}" {{ $filterSession == $sessionName ? 'selected' : '' }}>{{ $sessionName }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Filter Button -->
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-sm py-1 px-3 rounded-8 filter-btn w-100" style="height: 32px;">
                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">filter_alt</span>
                            <span style="font-size: 12px;">Filter</span>
                        </button>
                    </div>
                </div>
            </form>
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
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const campusSelect = document.getElementById('filter_campus');
    const sessionSelect = document.getElementById('filter_session');
    const examSelect = document.getElementById('filter_exam');

    function loadExams() {
        const campus = campusSelect.value;
        const session = sessionSelect.value;
        
        examSelect.innerHTML = '<option value="">Loading...</option>';
        
        const params = new URLSearchParams();
        if (campus) params.append('campus', campus);
        if (session) params.append('session', session);
        
        fetch(`{{ route('exam.grades.get-exams') }}?${params.toString()}`)
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

    campusSelect.addEventListener('change', loadExams);
    sessionSelect.addEventListener('change', loadExams);
});
</script>
@endsection
