@extends('layouts.accountant')

@section('title', 'Accountant Dashboard')

@section('content')
<div class="row">
    <div class="col-12">
        <!-- Dashboard Title -->
        <div class="d-flex align-items-center mb-4">
            <h2 class="mb-0 fs-20 fw-semibold text-dark me-2">Accountant Dashboard</h2>
            <span class="text-muted me-2">-</span>
            <h2 class="mb-0 fs-20 fw-semibold text-primary me-2">{{ $accountant->name }}</h2>
            <span class="material-symbols-outlined text-secondary" style="font-size: 20px;">dashboard</span>
        </div>

        <!-- Statistics Cards -->
        <div class="row g-2">
            <!-- Income Today Card -->
            <div class="col-md-2">
                <div class="card border-0 shadow-sm" style="background: #0066cc; border-radius: 8px; overflow: hidden;">
                    <div class="card-body p-3 position-relative">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h2 class="text-white mb-0 fw-bold" style="font-size: 24px; line-height: 1.2;">{{ number_format($incomeToday, 2) }}</h2>
                                <p class="text-white mb-0 mt-1" style="font-size: 11px; font-weight: 500;">Income Today</p>
                            </div>
                            <span class="material-symbols-outlined text-white" style="font-size: 40px; opacity: 0.3;">trending_down</span>
                        </div>
                        <div class="mt-2">
                            <small class="text-white" style="opacity: 0.8; font-size: 10px;">By: {{ $accountant->name }}</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Expense Today Card -->
            <div class="col-md-2">
                <div class="card border-0 shadow-sm" style="background: #dc3545; border-radius: 8px; overflow: hidden;">
                    <div class="card-body p-3 position-relative">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h2 class="text-white mb-0 fw-bold" style="font-size: 24px; line-height: 1.2;">{{ number_format($expenseToday, 2) }}</h2>
                                <p class="text-white mb-0 mt-1" style="font-size: 11px; font-weight: 500;">Expense Today</p>
                            </div>
                            <span class="material-symbols-outlined text-white" style="font-size: 40px; opacity: 0.3;">trending_up</span>
                        </div>
                        <div class="mt-2">
                            <small class="text-white" style="opacity: 0.8; font-size: 10px;">By: {{ $accountant->name }}</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Balance Today Card -->
            <div class="col-md-2">
                <div class="card border-0 shadow-sm" style="background: #17a2b8; border-radius: 8px; overflow: hidden;">
                    <div class="card-body p-3 position-relative">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h2 class="text-white mb-0 fw-bold" style="font-size: 24px; line-height: 1.2;">{{ number_format($balanceToday, 2) }}</h2>
                                <p class="text-white mb-0 mt-1" style="font-size: 11px; font-weight: 500;">Balance Today</p>
                            </div>
                            <span class="material-symbols-outlined text-white" style="font-size: 40px; opacity: 0.3;">pie_chart</span>
                        </div>
                        <div class="mt-2">
                            <small class="text-white" style="opacity: 0.8; font-size: 10px;">By: {{ $accountant->name }}</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Income This Month Card -->
            <div class="col-md-2">
                <div class="card border-0 shadow-sm" style="background: #28a745; border-radius: 8px; overflow: hidden;">
                    <div class="card-body p-3 position-relative">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h2 class="text-white mb-0 fw-bold" style="font-size: 24px; line-height: 1.2;">{{ number_format($incomeThisMonth, 2) }}</h2>
                                <p class="text-white mb-0 mt-1" style="font-size: 11px; font-weight: 500;">Income This Month</p>
                            </div>
                            <span class="material-symbols-outlined text-white" style="font-size: 40px; opacity: 0.3;">trending_down</span>
                        </div>
                        <div class="mt-2">
                            <small class="text-white" style="opacity: 0.8; font-size: 10px;">By: {{ $accountant->name }}</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Expense This Month Card -->
            <div class="col-md-2">
                <div class="card border-0 shadow-sm" style="background: #ff9800; border-radius: 8px; overflow: hidden;">
                    <div class="card-body p-3 position-relative">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h2 class="text-white mb-0 fw-bold" style="font-size: 24px; line-height: 1.2;">{{ number_format($expenseThisMonth, 2) }}</h2>
                                <p class="text-white mb-0 mt-1" style="font-size: 11px; font-weight: 500;">Expense This Month</p>
                            </div>
                            <span class="material-symbols-outlined text-white" style="font-size: 40px; opacity: 0.3;">trending_up</span>
                        </div>
                        <div class="mt-2">
                            <small class="text-white" style="opacity: 0.8; font-size: 10px;">By: {{ $accountant->name }}</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Balance This Month Card -->
            <div class="col-md-2">
                <div class="card border-0 shadow-sm" style="background: #6c757d; border-radius: 8px; overflow: hidden;">
                    <div class="card-body p-3 position-relative">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h2 class="text-white mb-0 fw-bold" style="font-size: 24px; line-height: 1.2;">{{ number_format($balanceThisMonth, 2) }}</h2>
                                <p class="text-white mb-0 mt-1" style="font-size: 11px; font-weight: 500;">Balance This Month</p>
                            </div>
                            <span class="material-symbols-outlined text-white" style="font-size: 40px; opacity: 0.3;">bar_chart</span>
                        </div>
                        <div class="mt-2">
                            <small class="text-white" style="opacity: 0.8; font-size: 10px;">By: {{ $accountant->name }}</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="row g-4 mt-2">
            <!-- Monthly Income Report Chart -->
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0">
                        <h5 class="mb-0 fw-semibold" style="color: #333;">Monthly Income Report</h5>
                    </div>
                    <div class="card-body">
                        <div id="monthly-income-chart" style="min-height: 300px;"></div>
                    </div>
                </div>
            </div>

            <!-- Income Vs Expenses Chart -->
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0">
                        <h5 class="mb-0 fw-semibold" style="color: #333;">Income Vs Expenses</h5>
                    </div>
                    <div class="card-body">
                        <div id="income-expense-chart" style="min-height: 300px;"></div>
                        <div class="mt-3 d-flex justify-content-between align-items-center">
                            <div class="text-center">
                                <h4 class="mb-0 text-primary">{{ number_format($incomeToday, 2) }}</h4>
                                <small class="text-muted">Income Today</small>
                            </div>
                            <div class="text-center">
                                <h4 class="mb-0 text-danger">{{ number_format($expenseToday, 2) }}</h4>
                                <small class="text-muted">Expense Today</small>
                            </div>
                            <div class="text-center">
                                <h4 class="mb-0 text-success">Balance: {{ number_format($balanceToday, 2) }}</h4>
                                <small class="text-muted">Balance</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Monthly Income Report Chart (Area Chart)
    const monthlyIncomeChart = document.getElementById('monthly-income-chart');
    if (monthlyIncomeChart) {
        var monthlyOptions = {
            series: [{
                name: 'Monthly Income',
                data: @json($monthlyIncomeData)
            }],
            chart: {
                type: 'area',
                height: 300,
                toolbar: {
                    show: false
                }
            },
            colors: ['#03A9F4'],
            stroke: {
                width: 2,
                curve: 'smooth'
            },
            fill: {
                type: 'gradient',
                gradient: {
                    shadeIntensity: 1,
                    opacityFrom: 0.7,
                    opacityTo: 0.3,
                    stops: [0, 90, 100]
                }
            },
            xaxis: {
                categories: @json($monthLabels),
                labels: {
                    style: {
                        fontSize: '12px',
                        colors: '#666'
                    }
                }
            },
            yaxis: {
                labels: {
                    style: {
                        fontSize: '12px',
                        colors: '#666'
                    },
                    formatter: function(val) {
                        return val.toFixed(0);
                    }
                }
            },
            grid: {
                strokeDashArray: 3,
                borderColor: '#e0e0e0'
            },
            tooltip: {
                y: {
                    formatter: function(val) {
                        return val.toFixed(2);
                    }
                }
            }
        };
        var monthlyChart = new ApexCharts(monthlyIncomeChart, monthlyOptions);
        monthlyChart.render();
    }

    // Income Vs Expenses Chart (Line Chart)
    const incomeExpenseChart = document.getElementById('income-expense-chart');
    if (incomeExpenseChart) {
        var incomeExpenseOptions = {
            series: [
                {
                    name: 'Income',
                    data: @json($monthlyIncomeData)
                },
                {
                    name: 'Expenses',
                    data: @json($monthlyExpenseData)
                }
            ],
            chart: {
                type: 'line',
                height: 250,
                toolbar: {
                    show: false
                }
            },
            colors: ['#28a745', '#dc3545'],
            stroke: {
                width: 2,
                curve: 'smooth'
            },
            markers: {
                size: 4
            },
            xaxis: {
                categories: @json($monthLabels),
                labels: {
                    style: {
                        fontSize: '12px',
                        colors: '#666'
                    }
                }
            },
            yaxis: {
                labels: {
                    style: {
                        fontSize: '12px',
                        colors: '#666'
                    },
                    formatter: function(val) {
                        return val.toFixed(0);
                    }
                }
            },
            grid: {
                strokeDashArray: 3,
                borderColor: '#e0e0e0'
            },
            legend: {
                show: true,
                position: 'top',
                fontSize: '12px'
            },
            tooltip: {
                y: {
                    formatter: function(val) {
                        return val.toFixed(2);
                    }
                }
            }
        };
        var incomeExpenseChartInstance = new ApexCharts(incomeExpenseChart, incomeExpenseOptions);
        incomeExpenseChartInstance.render();
    }
});
</script>
@endpush
@endsection

