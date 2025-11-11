@extends('layouts.app')

@section('title', 'Student Vouchers')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-3">
            <h3 class="mb-3 fw-semibold" style="color: #003471;">Student Vouchers</h3>
            
            <!-- Filter Form -->
            <div class="card bg-light border-0 rounded-10 p-3 mb-3">
                <form method="GET" action="{{ route('accounting.fee-voucher.student') }}" id="filterForm">
                    <div class="row g-3">
                        <!-- Type -->
                        <div class="col-md-3">
                            <label class="form-label mb-1 fs-13 fw-medium">Type</label>
                            <div class="input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff;">
                                    <span class="material-symbols-outlined" style="font-size: 18px; color: #003471;">category</span>
                                </span>
                                <select class="form-select form-select-sm" name="type" id="type" style="height: 38px;">
                                    <option value="">All Types</option>
                                    <option value="Monthly Fee" {{ request('type') == 'Monthly Fee' ? 'selected' : '' }}>Monthly Fee</option>
                                    <option value="Transport Fee" {{ request('type') == 'Transport Fee' ? 'selected' : '' }}>Transport Fee</option>
                                    <option value="Custom Fee" {{ request('type') == 'Custom Fee' ? 'selected' : '' }}>Custom Fee</option>
                                    <option value="Other" {{ request('type') == 'Other' ? 'selected' : '' }}>Other</option>
                                </select>
                            </div>
                        </div>

                        <!-- Class -->
                        <div class="col-md-3">
                            <label class="form-label mb-1 fs-13 fw-medium">Class</label>
                            <div class="input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff;">
                                    <span class="material-symbols-outlined" style="font-size: 18px; color: #003471;">class</span>
                                </span>
                                <select class="form-select form-select-sm" name="class" id="class" style="height: 38px;">
                                    <option value="">All Classes</option>
                                    @php
                                        $classes = \App\Models\ClassModel::whereNotNull('class_name')->distinct()->pluck('class_name')->sort();
                                        if ($classes->isEmpty()) {
                                            $classes = collect(['Nursery', 'KG', '1st', '2nd', '3rd', '4th', '5th', '6th', '7th', '8th', '9th', '10th', '11th', '12th']);
                                        }
                                    @endphp
                                    @foreach($classes as $className)
                                        <option value="{{ $className }}" {{ request('class') == $className ? 'selected' : '' }}>{{ $className }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <!-- Section -->
                        <div class="col-md-3">
                            <label class="form-label mb-1 fs-13 fw-medium">Section</label>
                            <div class="input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff;">
                                    <span class="material-symbols-outlined" style="font-size: 18px; color: #003471;">group</span>
                                </span>
                                <select class="form-select form-select-sm" name="section" id="section" style="height: 38px;">
                                    <option value="">All Sections</option>
                                    @php
                                        $sections = \App\Models\Section::whereNotNull('name')->distinct()->pluck('name')->sort();
                                        if ($sections->isEmpty()) {
                                            $sections = collect(['A', 'B', 'C', 'D', 'E']);
                                        }
                                    @endphp
                                    @foreach($sections as $sectionName)
                                        <option value="{{ $sectionName }}" {{ request('section') == $sectionName ? 'selected' : '' }}>{{ $sectionName }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <!-- Vouchers For -->
                        <div class="col-md-3">
                            <label class="form-label mb-1 fs-13 fw-medium">Vouchers For?</label>
                            <div class="input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff;">
                                    <span class="material-symbols-outlined" style="font-size: 18px; color: #003471;">receipt</span>
                                </span>
                                <select class="form-select form-select-sm" name="vouchers_for" id="vouchers_for" style="height: 38px;">
                                    <option value="">All</option>
                                    <option value="Monthly" {{ request('vouchers_for') == 'Monthly' ? 'selected' : '' }}>Monthly</option>
                                    <option value="Quarterly" {{ request('vouchers_for') == 'Quarterly' ? 'selected' : '' }}>Quarterly</option>
                                    <option value="Half Yearly" {{ request('vouchers_for') == 'Half Yearly' ? 'selected' : '' }}>Half Yearly</option>
                                    <option value="Yearly" {{ request('vouchers_for') == 'Yearly' ? 'selected' : '' }}>Yearly</option>
                                    <option value="Custom" {{ request('vouchers_for') == 'Custom' ? 'selected' : '' }}>Custom</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mt-3">
                        <div class="col-12">
                            <button type="submit" class="btn btn-sm px-4 py-2" style="background-color: #003471; color: white;">
                                <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">filter_list</span>
                                Filter
                            </button>
                            <a href="{{ route('accounting.fee-voucher.student') }}" class="btn btn-sm btn-outline-secondary px-4 py-2 ms-2">
                                <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">refresh</span>
                                Reset
                            </a>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Results Table -->
            @if(isset($students) && $students->count() > 0)
                <div class="card bg-light border-0 rounded-10 p-3">
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm">
                            <thead style="background-color: #003471; color: white;">
                                <tr>
                                    <th>#</th>
                                    <th>Student Name</th>
                                    <th>Student Code</th>
                                    <th>Class</th>
                                    <th>Section</th>
                                    <th>Type</th>
                                    <th>Vouchers For</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($students as $index => $student)
                                    <tr>
                                        <td>{{ $index + 1 + (($students->currentPage() - 1) * $students->perPage()) }}</td>
                                        <td>{{ $student->student_name ?? 'N/A' }}</td>
                                        <td>{{ $student->student_code ?? 'N/A' }}</td>
                                        <td>{{ $student->class ?? 'N/A' }}</td>
                                        <td>{{ $student->section ?? 'N/A' }}</td>
                                        <td>
                                            <span class="badge bg-info">{{ request('type') ?: 'N/A' }}</span>
                                        </td>
                                        <td>
                                            <span class="badge bg-success">{{ request('vouchers_for') ?: 'N/A' }}</span>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-primary" onclick="generateVoucher({{ $student->id }})">
                                                <span class="material-symbols-outlined" style="font-size: 14px;">print</span>
                                                Generate Voucher
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    
                    @if($students->hasPages())
                        <div class="mt-3">
                            {{ $students->links() }}
                        </div>
                    @endif
                </div>
            @else
                <div class="alert alert-info">
                    <span class="material-symbols-outlined" style="font-size: 20px; vertical-align: middle;">info</span>
                    No students found. Please adjust your filters.
                </div>
            @endif
        </div>
    </div>
</div>

<style>
    .input-group-text {
        border-right: none;
    }
    
    .form-select,
    .form-control {
        border-left: none;
    }
    
    .form-select:focus,
    .form-control:focus {
        border-color: #003471;
        box-shadow: 0 0 0 0.2rem rgba(0, 52, 113, 0.25);
    }
    
    .input-group:focus-within .input-group-text {
        background-color: #003471 !important;
        color: white !important;
        border-color: #003471;
    }
    
    .input-group:focus-within .material-symbols-outlined {
        color: white !important;
    }
    
    table thead th {
        font-size: 13px;
        font-weight: 600;
        padding: 12px 15px;
    }
    
    table tbody td {
        font-size: 13px;
        padding: 12px 15px;
        vertical-align: middle;
    }
    
    .badge {
        font-size: 11px;
        padding: 4px 8px;
    }
</style>

<script>
function generateVoucher(studentId) {
    // Add voucher generation logic here
    alert('Generate voucher for student ID: ' + studentId);
    // You can redirect to a voucher generation page or open a modal
}
</script>
@endsection
