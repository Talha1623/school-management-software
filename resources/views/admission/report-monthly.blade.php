@extends('layouts.app')

@section('title', 'Monthly Admissions Report')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-3 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0 fs-16 fw-semibold">Monthly Admissions Report ({{ $reportMonthLabel ?? now()->format('F Y') }})</h4>
                <a href="{{ route('admission.report.monthly.print') }}?auto_print=1" target="_blank" class="btn btn-sm btn-primary" style="background: linear-gradient(135deg, #003471 0%, #004a9f 100%); border: none;">
                    <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">print</span>
                    Print
                </a>
            </div>

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
                                <td>{{ $student->admission_date ? \Carbon\Carbon::parse($student->admission_date)->format('d-m-Y') : ($student->created_at ? $student->created_at->format('d-m-Y') : 'N/A') }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="text-center py-4">
                                    <div class="d-flex flex-column align-items-center">
                                        <span class="material-symbols-outlined text-muted" style="font-size: 48px;">inbox</span>
                                        <p class="text-muted mt-2 mb-0">No admissions found for this month.</p>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-3">
                <p class="mb-0"><strong>Total Admissions in {{ $reportMonthLabel ?? now()->format('F Y') }}:</strong> {{ $students->count() }}</p>
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
