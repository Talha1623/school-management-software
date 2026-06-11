@extends('layouts.app')

@section('title', 'Admission Forms Report')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-3 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0 fs-16 fw-semibold">Admission Forms Report</h4>
                <a href="{{ route('admission.report.forms.print') }}?{{ http_build_query(array_merge(['auto_print' => 1], request()->only(['filter_campus', 'filter_class', 'filter_section']))) }}" target="_blank" class="btn btn-sm btn-primary" style="background: linear-gradient(135deg, #003471 0%, #004a9f 100%); border: none;">
                    <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">print</span>
                    Print
                </a>
            </div>

            <form method="GET" action="{{ route('admission.report.forms') }}" class="mb-3">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Campus</label>
                        <select name="filter_campus" class="form-select form-select-sm">
                            <option value="">All Campuses</option>
                            @foreach($campuses as $campus)
                                <option value="{{ $campus }}" {{ $filterCampus == $campus ? 'selected' : '' }}>{{ $campus }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Class</label>
                        <select name="filter_class" class="form-select form-select-sm">
                            <option value="">All Classes</option>
                            @foreach($classes as $class)
                                <option value="{{ $class }}" {{ $filterClass == $class ? 'selected' : '' }}>{{ $class }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Section</label>
                        <select name="filter_section" class="form-select form-select-sm">
                            <option value="">All Sections</option>
                            @foreach($sections as $section)
                                <option value="{{ $section }}" {{ $filterSection == $section ? 'selected' : '' }}>{{ $section }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary btn-sm w-100">Filter</button>
                    </div>
                </div>
            </form>

            <div class="default-table-area">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Sr.</th>
                                <th>Student Code</th>
                                <th>Student Name</th>
                                <th>Parent Name</th>
                                <th>Class</th>
                                <th>Section</th>
                                <th>Campus</th>
                                <th>Admission Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($students as $index => $student)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $student->student_code ?? 'N/A' }}</td>
                                <td><strong class="text-primary">{{ $student->student_name }}</strong></td>
                                <td>{{ $student->father_name ?? 'N/A' }}</td>
                                <td>{{ $student->class ?? 'N/A' }}</td>
                                <td>{{ $student->section ?? 'N/A' }}</td>
                                <td>{{ $student->campus ?? 'N/A' }}</td>
                                <td>{{ $student->admission_date ? \Carbon\Carbon::parse($student->admission_date)->format('d-m-Y') : 'N/A' }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="text-center py-4">
                                    <div class="d-flex flex-column align-items-center">
                                        <span class="material-symbols-outlined text-muted" style="font-size: 48px;">inbox</span>
                                        <p class="text-muted mt-2 mb-0">No admission forms found.</p>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-3">
                <p class="mb-0"><strong>Total Admission Forms:</strong> {{ $students->count() }}</p>
            </div>
        </div>
    </div>
</div>

<style>
.default-table-area thead {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
}
.default-table-area thead th {
    font-weight: 600;
    font-size: 13px;
    color: #003471;
}
</style>
@endsection
