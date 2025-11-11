<!DOCTYPE html>
<html>
<head>
    <title>Manage Salaries - PDF Export</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        h1 {
            color: #003471;
            text-align: center;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
            font-size: 10px;
        }
        th {
            background-color: #003471;
            color: white;
        }
        tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        img {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            object-fit: cover;
        }
    </style>
</head>
<body>
    <h1>Manage Salaries Report</h1>
    <p><strong>Generated:</strong> {{ date('Y-m-d H:i:s') }}</p>
    <p><strong>Total Salaries:</strong> {{ $salaries->count() }}</p>
    
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Salary Month</th>
                <th class="text-center">Present</th>
                <th class="text-center">Absent</th>
                <th class="text-center">Late</th>
                <th class="text-right">Basic</th>
                <th class="text-right">Salary Generated</th>
                <th class="text-right">Amount Paid</th>
                <th class="text-right">Loan Repayment</th>
                <th>Status</th>
                <th>Created At</th>
            </tr>
        </thead>
        <tbody>
            @forelse($salaries as $salary)
                <tr>
                    <td>{{ $salary->id }}</td>
                    <td>{{ $salary->staff->name ?? 'N/A' }}</td>
                    <td>{{ $salary->salary_month }} {{ $salary->year }}</td>
                    <td class="text-center">{{ $salary->present }}</td>
                    <td class="text-center">{{ $salary->absent }}</td>
                    <td class="text-center">{{ $salary->late }}</td>
                    <td class="text-right">₹{{ number_format($salary->basic, 2) }}</td>
                    <td class="text-right">₹{{ number_format($salary->salary_generated, 2) }}</td>
                    <td class="text-right">₹{{ number_format($salary->amount_paid, 2) }}</td>
                    <td class="text-right">₹{{ number_format($salary->loan_repayment, 2) }}</td>
                    <td>{{ $salary->status }}</td>
                    <td>{{ $salary->created_at->format('Y-m-d H:i:s') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="12" style="text-align: center;">No salaries found.</td>
                </tr>
            @endforelse
        </tbody>
        @if($salaries->count() > 0)
        <tfoot>
            <tr style="background-color: #003471; color: white; font-weight: bold;">
                <td colspan="6" class="text-right">Total:</td>
                <td class="text-right">₹{{ number_format($salaries->sum('basic'), 2) }}</td>
                <td class="text-right">₹{{ number_format($salaries->sum('salary_generated'), 2) }}</td>
                <td class="text-right">₹{{ number_format($salaries->sum('amount_paid'), 2) }}</td>
                <td class="text-right">₹{{ number_format($salaries->sum('loan_repayment'), 2) }}</td>
                <td colspan="2"></td>
            </tr>
        </tfoot>
        @endif
    </table>
</body>
</html>

