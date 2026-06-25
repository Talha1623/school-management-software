@if(isset($generatedSalaries) && $generatedSalaries->count() > 0)
<div class="mt-4">
    <div class="card bg-white border border-white rounded-10 p-3">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0 fs-16 fw-semibold">Generated Salaries</h5>
            @if(isset($generatedCampus) && isset($generatedMonth) && isset($generatedYear))
            <span class="badge bg-info text-white">
                {{ $generatedCampus }} -
                @php
                    $monthNames = [
                        '01' => 'January', '02' => 'February', '03' => 'March', '04' => 'April',
                        '05' => 'May', '06' => 'June', '07' => 'July', '08' => 'August',
                        '09' => 'September', '10' => 'October', '11' => 'November', '12' => 'December'
                    ];
                    $monthName = $monthNames[$generatedMonth] ?? $generatedMonth;
                @endphp
                {{ $monthName }} {{ $generatedYear }}
            </span>
            @endif
        </div>

        <div class="default-table-area">
            <div class="table-responsive">
                <table class="table table-sm table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Photo</th>
                            <th>Name</th>
                            <th>Salary Month</th>
                            <th class="text-center">Present</th>
                            <th class="text-center">Absent</th>
                            <th class="text-center">Late</th>
                            <th class="text-center">Early Exit</th>
                            <th class="text-end">Basic</th>
                            <th class="text-end">Salary Generated</th>
                            <th class="text-end">Amount Paid</th>
                            <th class="text-end">Loan Repayment</th>
                            <th>Status</th>
                            <th class="text-center">Make Payment</th>
                            <th class="text-center">Delete</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($generatedSalaries as $salary)
                        <tr>
                            <td>{{ $salary->id }}</td>
                            <td>
                                @if($salary->staff && $salary->staff->photo)
                                    <img src="{{ asset('storage/' . $salary->staff->photo) }}" alt="Photo" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
                                @else
                                    <div style="width: 40px; height: 40px; border-radius: 50%; background-color: #e0e7ff; display: flex; align-items: center; justify-content: center;">
                                        <span class="material-symbols-outlined" style="font-size: 20px; color: #003471;">person</span>
                                    </div>
                                @endif
                            </td>
                            <td>
                                <strong class="text-dark">{{ $salary->staff->name ?? 'N/A' }}</strong>
                            </td>
                            <td>
                                <span class="badge bg-info text-white" style="font-size: 11px;">{{ $salary->salary_month }} {{ $salary->year }}</span>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-success text-white" style="font-size: 11px;">{{ $salary->present ?? 0 }}</span>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-danger text-white" style="font-size: 11px;">{{ $salary->absent ?? 0 }}</span>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-warning text-dark" style="font-size: 11px;">{{ $salary->late ?? 0 }}</span>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-danger text-white" style="font-size: 11px;">{{ $salary->early_exit ?? 0 }}</span>
                            </td>
                            <td class="text-end">
                                <strong class="text-primary">{{ number_format($salary->basic ?? 0, 2) }}</strong>
                                <div>
                                    <span class="badge bg-light text-dark" style="font-size: 10px;">
                                        {{ $salary->staff->salary_type ?? 'full time' }}
                                    </span>
                                </div>
                            </td>
                            <td class="text-end">
                                <strong class="text-success">{{ number_format($salary->grossSalaryGenerated(), 2) }}</strong>
                            </td>
                            <td class="text-end">
                                @php
                                    $displayAmountPaid = (float) ($salary->amount_paid ?? 0);
                                    if ($displayAmountPaid <= 0 && $salary->status === 'Paid') {
                                        $displayAmountPaid = max(0, (float) ($salary->salary_generated ?? 0) + (float) ($salary->bonus_amount ?? 0) - (float) ($salary->deduction_amount ?? 0));
                                    }
                                @endphp
                                <strong class="text-info">{{ number_format($displayAmountPaid, 2) }}</strong>
                            </td>
                            <td class="text-end">
                                <strong class="text-warning">{{ number_format($salary->loan_repayment ?? 0, 2) }}</strong>
                            </td>
                            <td>
                            @if($salary->status == 'Pending')
                                <form action="{{ route('salary-loan.generate-salary.update-status', $salary->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="status" value="Paid">
                                    <button type="submit" class="btn btn-sm px-2 py-0 btn-warning" title="Click to mark as Paid and print receipt">
                                        <span class="badge bg-warning text-dark" style="font-size: 11px; cursor: pointer;">Pending</span>
                                    </button>
                                </form>
                            @elseif($salary->status == 'Paid')
                                <span class="badge bg-success text-white" style="font-size: 11px;">Paid</span>
                            @else
                                <span class="badge bg-info text-white" style="font-size: 11px;">Issued</span>
                            @endif
                            </td>
                            <td class="text-center">
                            @if($salary->status != 'Pending')
                                    <button type="button" class="btn btn-sm btn-success px-2 py-1" onclick="printSalarySlip({{ $salary->id }})" title="Print Slip" style="color: white; font-size: 11px;">
                                        Print Slip
                                    </button>
                                @else
                                    <button type="button" class="btn btn-sm btn-primary px-2 py-1" onclick="openMakePaymentModal({{ $salary->id }}, '{{ addslashes($salary->staff->campus ?? 'N/A') }}', '{{ addslashes($salary->staff->name ?? 'N/A') }}', '{{ $salary->salary_month }} {{ $salary->year }}', '{{ strtolower(trim((string) ($salary->staff->salary_type ?? ''))) }}')" title="Make Payment" style="color: white; font-size: 11px;">
                                        Make Payment
                                    </button>
                                @endif
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-danger px-2 py-1" onclick="deleteSalary({{ $salary->id }})" title="Delete">
                                    <span class="material-symbols-outlined" style="font-size: 16px; color: white;">delete</span>
                                </button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr style="background-color: #f8f9fa; font-weight: 600;">
                            <td colspan="8" class="text-end"><strong>Total:</strong></td>
                            <td class="text-end"><strong class="text-primary">{{ number_format($generatedSalaries->sum('basic'), 2) }}</strong></td>
                            <td class="text-end"><strong class="text-success">{{ number_format($generatedSalaries->sum(fn ($salary) => $salary->grossSalaryGenerated()), 2) }}</strong></td>
                            <td class="text-end"><strong class="text-info">{{ number_format($generatedSalaries->sum('amount_paid'), 2) }}</strong></td>
                            <td class="text-end"><strong class="text-warning">{{ number_format($generatedSalaries->sum('loan_repayment'), 2) }}</strong></td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>
@endif
