@extends('layouts.app')

@section('title', 'Add & Manage Diaries')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-3 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0 fs-16 fw-semibold">Add & Manage Diaries</h4>
            </div>

            <!-- Filter Form -->
            <form action="{{ route('homework-diary.manage') }}" method="GET" id="filterForm">
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

                    <!-- Class -->
                    <div class="col-md-3">
                        <label for="filter_class" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Class</label>
                        <select class="form-select form-select-sm" id="filter_class" name="filter_class" style="height: 32px;">
                            <option value="">All Classes</option>
                            @foreach($classes as $className)
                                <option value="{{ $className }}" {{ $filterClass == $className ? 'selected' : '' }}>{{ $className }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Section -->
                    <div class="col-md-3">
                        <label for="filter_section" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Section</label>
                        <select class="form-select form-select-sm" id="filter_section" name="filter_section" style="height: 32px;">
                            <option value="">All Sections</option>
                            @foreach($sections as $sectionName)
                                <option value="{{ $sectionName }}" {{ $filterSection == $sectionName ? 'selected' : '' }}>{{ $sectionName }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Date -->
                    <div class="col-md-3">
                        <label for="filter_date" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Date</label>
                        <input type="date" class="form-control form-control-sm" id="filter_date" name="filter_date" value="{{ $filterDate }}" style="height: 32px;">
                    </div>
                </div>

                <!-- Filter Button -->
                <div class="row">
                    <div class="col-md-12 text-end">
                        <button type="submit" class="btn btn-sm py-1 px-3 rounded-8 filter-btn" style="height: 32px;">
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
    const classSelect = document.getElementById('filter_class');
    const sectionSelect = document.getElementById('filter_section');

    function loadSections(selectedClass) {
        if (selectedClass) {
            sectionSelect.innerHTML = '<option value="">Loading...</option>';
            
            fetch(`{{ route('exam.timetable.get-sections') }}?class=${encodeURIComponent(selectedClass)}`)
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

    classSelect.addEventListener('change', function() {
        loadSections(this.value);
    });
});
</script>
@endsection
