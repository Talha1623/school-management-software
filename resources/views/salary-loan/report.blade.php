@extends('layouts.app')

@section('title', 'Salary and Loan Report')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-20">
            <h3 class="mb-20">Salary and Loan Report</h3>

            <div class="row g-3 mb-4">
                <div class="col-xl-3 col-lg-6">
                    <div class="report-card bg-danger text-white rounded-10 p-3 h-100">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="fs-20 fw-bold">{{ $unpaidSalaries->count() }}</div>
                                <div class="fs-14">Unpaid Salaries</div>
                            </div>
                            <span class="material-symbols-outlined opacity-75">credit_card</span>
                        </div>
                        <a href="{{ route('salary-loan.report.unpaid') }}" target="_blank" class="report-link text-white">View Report</a>
                    </div>
                </div>

                <div class="col-xl-3 col-lg-6">
                    <div class="report-card bg-warning text-white rounded-10 p-3 h-100">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="fs-20 fw-bold">{{ number_format($unpaidAmount, 2) }}</div>
                                <div class="fs-14">Unpaid Salaries Amount</div>
                            </div>
                            <span class="material-symbols-outlined opacity-75">bar_chart</span>
                        </div>
                        <a href="{{ route('salary-loan.report.unpaid') }}" target="_blank" class="report-link text-white">View Report</a>
                    </div>
                </div>

                <div class="col-xl-3 col-lg-6">
                    <div class="report-card bg-primary text-white rounded-10 p-3 h-100">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="fs-20 fw-bold">{{ $paidSalaries->count() }}</div>
                                <div class="fs-14">Paid Salaries</div>
                            </div>
                            <span class="material-symbols-outlined opacity-75">thumb_up</span>
                        </div>
                        <a href="{{ route('salary-loan.report.paid') }}" target="_blank" class="report-link text-white">Current Month</a>
                    </div>
                </div>

                <div class="col-xl-3 col-lg-6">
                    <div class="report-card bg-secondary text-white rounded-10 p-3 h-100">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="fs-20 fw-bold">{{ $loanApplications->count() }}</div>
                                <div class="fs-14">Loan Applications</div>
                            </div>
                            <span class="material-symbols-outlined opacity-75">download</span>
                        </div>
                        <a href="{{ route('salary-loan.report.loan-applications') }}" target="_blank" class="report-link text-white">View Report</a>
                    </div>
                </div>
            </div>

            <div class="card bg-white border rounded-10 p-3">
                <div class="mb-3">
                    <h5 class="mb-0 fw-semibold">Printable Salary & Loan Reports</h5>
                </div>
                <div class="row g-4 align-items-start">
                    <div class="col-lg-12">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="fw-semibold">Unpaid Salaries Report</div>
                                        <small class="text-muted">List of all unpaid salaries.</small>
                                    </div>
                                    <a href="{{ route('salary-loan.report.unpaid') }}" target="_blank" class="btn btn-outline-primary btn-sm px-3">Print</a>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="fw-semibold">Paid Salaries Report</div>
                                        <small class="text-muted">List of all paid salaries for current month.</small>
                                    </div>
                                    <a href="{{ route('salary-loan.report.paid') }}" target="_blank" class="btn btn-outline-primary btn-sm px-3">Print</a>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="fw-semibold">Loan Applications Report</div>
                                        <small class="text-muted">List of all active loan applications.</small>
                                    </div>
                                    <a href="{{ route('salary-loan.report.loan-applications') }}" target="_blank" class="btn btn-outline-primary btn-sm px-3">Print</a>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="fw-semibold">Loan Defaulter Teachers</div>
                                        <small class="text-muted">List of teachers having loan due amount.</small>
                                    </div>
                                    <a href="{{ route('salary-loan.report.loan-defaulters') }}" target="_blank" class="btn btn-outline-primary btn-sm px-3">Print</a>
                                </div>
                            </div>
                        </div>
                        <div class="text-muted mt-4" style="font-size: 12px;">
                            Note: Please ensure that all reports are printed in A4 size for optimal viewing. Adjust your printer settings accordingly.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.report-card {
    position: relative;
}

.report-link {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 12px;
    margin-top: 10px;
    text-decoration: none;
}
</style>
@endsection

