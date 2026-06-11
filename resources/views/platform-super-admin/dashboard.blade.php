@extends('layouts.platform-admin')

@section('title', 'Platform Dashboard')

@section('content')
<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm p-4" style="background: linear-gradient(135deg, #003471 0%, #004a9f 100%);">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="mb-1 text-white fw-semibold">Welcome, {{ $admin->name }}!</h4>
                    <p class="mb-0 text-white-50">Manage schools, monitor status, and track growth.</p>
                </div>
                <span class="badge bg-light text-primary px-3 py-2">Platform Super Admin</span>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm p-3 text-white" style="background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);">
            <p class="mb-1 text-white-50 fs-13">Total Schools</p>
            <h3 class="mb-0 text-white">{{ $totalSchools }}</h3>
            <small class="text-white-50">All registered schools</small>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm p-3 text-white" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);">
            <p class="mb-1 text-white-50 fs-13">Active Schools</p>
            <h3 class="mb-0 text-white">{{ $activeSchools }}</h3>
            <small class="text-white-50">Ready and online</small>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm p-3 text-white" style="background: linear-gradient(135deg, #f7971e 0%, #ff5858 100%);">
            <p class="mb-1 text-white-50 fs-13">Inactive Schools</p>
            <h3 class="mb-0 text-white">{{ $inactiveSchools }}</h3>
            <small class="text-white-50">Switched off</small>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm p-3 h-100" style="background: linear-gradient(180deg, #ffffff 0%, #f5f9ff 100%);">
            <h5 class="mb-3" style="color: #003471;">Schools Growth (Last 6 Months)</h5>
            <div id="platformSchoolsGrowthChart" style="min-height: 320px;"></div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm p-3 h-100" style="background: linear-gradient(180deg, #ffffff 0%, #fff7f0 100%);">
            <h5 class="mb-3" style="color: #8a3a00;">Recently Added</h5>
            <div class="list-group list-group-flush">
                @forelse($recentSchools as $school)
                    <div class="list-group-item px-0 d-flex justify-content-between align-items-center border-0 border-bottom">
                        <div>
                            <div class="fw-semibold">{{ $school->name }}</div>
                            <small class="text-muted">{{ $school->domain }}</small>
                        </div>
                        <span class="badge {{ $school->status === 'active' ? 'bg-success' : 'bg-secondary' }}">
                            {{ ucfirst($school->status) }}
                        </span>
                    </div>
                @empty
                    <div class="text-muted">No schools yet.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const el = document.querySelector('#platformSchoolsGrowthChart');
    if (!el || typeof ApexCharts === 'undefined') return;

    const options = {
        series: [{ name: 'Schools', data: @json($monthlyCounts) }],
        chart: { type: 'area', height: 320, toolbar: { show: false } },
        colors: ['#6a11cb'],
        dataLabels: { enabled: false },
        stroke: { curve: 'smooth', width: 3 },
        fill: {
            type: 'gradient',
            gradient: { shadeIntensity: 1, opacityFrom: 0.35, opacityTo: 0.05, stops: [0, 90, 100] }
        },
        xaxis: { categories: @json($monthlyLabels) },
        yaxis: { min: 0, forceNiceScale: true },
        grid: { borderColor: '#eef1f7' }
    };

    new ApexCharts(el, options).render();
});
</script>
@endpush
