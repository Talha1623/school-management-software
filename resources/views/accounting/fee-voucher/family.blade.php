@extends('layouts.app')

@section('title', 'Family Vouchers')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-3">
            <h3 class="mb-3 fw-semibold" style="color: #003471;">Family Vouchers</h3>
            
            <!-- Filter Form -->
            <div class="card bg-light border-0 rounded-10 p-3 mb-3">
                <form method="GET" action="{{ route('accounting.fee-voucher.family') }}" id="filterForm">
                    <div class="row g-3">
                        <!-- Type -->
                        <div class="col-md-4">
                            <label class="form-label mb-1 fs-13 fw-medium">Type</label>
                            <div class="input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff;">
                                    <span class="material-symbols-outlined" style="font-size: 18px; color: #003471;">category</span>
                                </span>
                                <select class="form-select form-select-sm" name="type" id="type" style="height: 38px;">
                                    <option value="">All Types</option>
                                    @foreach($copyTypes as $copyTypeValue => $copyTypeLabel)
                                        <option value="{{ $copyTypeValue }}" {{ request('type') == $copyTypeValue ? 'selected' : '' }}>{{ $copyTypeLabel }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <!-- Campus -->
                        <div class="col-md-4">
                            <label class="form-label mb-1 fs-13 fw-medium">Campus</label>
                            <div class="input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff;">
                                    <span class="material-symbols-outlined" style="font-size: 18px; color: #003471;">home</span>
                                </span>
                                <select class="form-select form-select-sm" name="campus" id="campus" style="height: 38px;">
                                    <option value="">All Campuses</option>
                                    @php
                                        $campuses = \App\Models\ClassModel::whereNotNull('campus')->distinct()->pluck('campus');
                                        $campusesFromSections = \App\Models\Section::whereNotNull('campus')->distinct()->pluck('campus');
                                        $allCampuses = $campuses->merge($campusesFromSections)->unique()->sort()->values();
                                        if ($allCampuses->isEmpty()) {
                                            $allCampuses = collect(['Main Campus', 'Branch Campus 1', 'Branch Campus 2']);
                                        }
                                    @endphp
                                    @foreach($allCampuses as $campus)
                                        <option value="{{ $campus }}" {{ request('campus') == $campus ? 'selected' : '' }}>{{ $campus }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <!-- Vouchers For -->
                        <div class="col-md-4">
                            <label class="form-label mb-1 fs-13 fw-medium">Vouchers For?</label>
                            <div class="input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff;">
                                    <span class="material-symbols-outlined" style="font-size: 18px; color: #003471;">receipt</span>
                                </span>
                                <select class="form-select form-select-sm" name="vouchers_for" id="vouchers_for" style="height: 38px;">
                                    <option value="">All</option>
                                    @foreach($months as $month)
                                        <option value="{{ $month }}" {{ request('vouchers_for') == $month ? 'selected' : '' }}>{{ $month }}</option>
                                    @endforeach
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
                            <a href="{{ route('accounting.fee-voucher.family') }}" class="btn btn-sm btn-outline-secondary px-4 py-2 ms-2">
                                <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">refresh</span>
                                Reset
                            </a>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Results Table -->
            @if(isset($families) && $families->count() > 0)
                <div class="card bg-light border-0 rounded-10 p-3">
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm">
                            <thead style="background-color: #003471; color: white;">
                                <tr>
                                    <th>#</th>
                                    <th>Parent Name</th>
                                    <th>Students</th>
                                    <th>Student Codes</th>
                                    <th>Classes</th>
                                    <th>Sections</th>
                                    <th>Campus</th>
                                    <th>Type</th>
                                    <th>Vouchers For</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($families as $index => $family)
                                    <tr>
                                        <td>{{ $index + 1 + (($families->currentPage() - 1) * $families->perPage()) }}</td>
                                        <td><strong>{{ $family->parent_name ?? 'Unknown' }}</strong></td>
                                        <td>{{ $family->student_names ?? 'N/A' }}</td>
                                        <td>{{ $family->student_codes ?? 'N/A' }}</td>
                                        <td>{{ $family->classes ?? 'N/A' }}</td>
                                        <td>{{ $family->sections ?? 'N/A' }}</td>
                                        <td>{{ $family->campus ?? 'N/A' }}</td>
                                        <td>
                                            <span class="badge bg-info">{{ request('type') ?: 'N/A' }}</span>
                                        </td>
                                        <td>
                                            <span class="badge bg-success">{{ request('vouchers_for') ?: 'N/A' }}</span>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-primary" onclick="generateFamilyVoucher('{{ $family->parent_name }}')">
                                                <span class="material-symbols-outlined" style="font-size: 14px;">print</span>
                                                Generate Voucher
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    
                    @if($families->hasPages())
                        <div class="mt-3">
                            {{ $families->links() }}
                        </div>
                    @endif
                </div>
            @else
                <div class="alert alert-info">
                    <span class="material-symbols-outlined" style="font-size: 20px; vertical-align: middle;">info</span>
                    No families found. Please adjust your filters.
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
function generateFamilyVoucher(parentName) {
    const params = new URLSearchParams(window.location.search);
    if (parentName) {
        params.set('parent_name', parentName);
    }
    const url = '{{ route('accounting.fee-voucher.family.print') }}' + '?' + params.toString();
    window.open(url, '_blank');
}
</script>
@endsection
