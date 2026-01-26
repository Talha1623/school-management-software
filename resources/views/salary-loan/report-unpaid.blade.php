<!DOCTYPE html>
<html>
<head>
    <title>Unpaid Salaries Report</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; color: #222; }
        h2 { margin: 0 0 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 6px; text-align: left; }
        th { background: #f5f5f5; }
        .text-end { text-align: right; }
    </style>
</head>
<body>
    <h2>Unpaid Salaries Report</h2>
    <div>Printed at: {{ now()->format('d M Y H:i') }}</div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Staff</th>
                <th>Emp ID</th>
                <th>Campus</th>
                <th>Month</th>
                <th>Year</th>
                <th class="text-end">Salary</th>
                <th class="text-end">Paid</th>
                <th class="text-end">Due</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
        @forelse($unpaidSalaries as $index => $salary)
            @php
                $monthNames = ['01' => 'January','02' => 'February','03' => 'March','04' => 'April','05' => 'May','06' => 'June','07' => 'July','08' => 'August','09' => 'September','10' => 'October','11' => 'November','12' => 'December'];
                $generated = $salary->salary_generated ?? 0;
                $paid = $salary->amount_paid ?? 0;
                $due = max(0, $generated - $paid);
            @endphp
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $salary->staff->name ?? 'N/A' }}</td>
                <td>{{ $salary->staff->emp_id ?? 'N/A' }}</td>
                <td>{{ $salary->staff->campus ?? 'N/A' }}</td>
                <td>{{ $monthNames[$salary->salary_month] ?? $salary->salary_month }}</td>
                <td>{{ $salary->year }}</td>
                <td class="text-end">{{ number_format($generated, 2) }}</td>
                <td class="text-end">{{ number_format($paid, 2) }}</td>
                <td class="text-end">{{ number_format($due, 2) }}</td>
                <td>{{ $salary->status ?? 'N/A' }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="10">No unpaid salaries found.</td>
            </tr>
        @endforelse
        </tbody>
    </table>

    <script>
        window.print();
    </script>
</body>
</html>
