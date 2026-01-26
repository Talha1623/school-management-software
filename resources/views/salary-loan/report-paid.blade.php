<!DOCTYPE html>
<html>
<head>
    <title>Paid Salaries Report</title>
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
    @php
        $monthNames = ['01' => 'January','02' => 'February','03' => 'March','04' => 'April','05' => 'May','06' => 'June','07' => 'July','08' => 'August','09' => 'September','10' => 'October','11' => 'November','12' => 'December'];
    @endphp
    <h2>Paid Salaries Report ({{ $monthNames[$currentMonth] ?? $currentMonth }} {{ $currentYear }})</h2>
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
                <th class="text-end">Amount Paid</th>
            </tr>
        </thead>
        <tbody>
        @forelse($paidSalaries as $index => $salary)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $salary->staff->name ?? 'N/A' }}</td>
                <td>{{ $salary->staff->emp_id ?? 'N/A' }}</td>
                <td>{{ $salary->staff->campus ?? 'N/A' }}</td>
                <td>{{ $monthNames[$salary->salary_month] ?? $salary->salary_month }}</td>
                <td>{{ $salary->year }}</td>
                <td class="text-end">{{ number_format($salary->amount_paid ?? 0, 2) }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="7">No paid salaries found.</td>
            </tr>
        @endforelse
        </tbody>
    </table>

    <script>
        window.print();
    </script>
</body>
</html>
