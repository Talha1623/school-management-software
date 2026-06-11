<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Unpaid Invoices</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; }
        h2 { color: #003471; margin: 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #003471; padding: 4px; }
        th { background: #003471; color: #fff; }
    </style>
</head>
<body>
    <h2>{{ $settings->school_name ?? 'School' }}</h2>
    <div>List of Unpaid Invoices — {{ $printedAt }}</div>
    @if(!empty($filterCampus)) <div>Campus: {{ $filterCampus }}</div> @endif
    @if(!empty($filterType)) <div>Type: {{ $filterType }}</div> @endif
    <table>
        <thead>
            <tr>
                <th>#</th><th>Code</th><th>Name</th><th>Campus</th><th>Class</th><th>Fee Type</th><th>Expected</th><th>Paid</th><th>Unpaid</th><th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($unpaidInvoices as $i => $row)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $row['student_code'] }}</td>
                <td>{{ $row['student_name'] }}</td>
                <td>{{ $row['campus'] }}</td>
                <td>{{ $row['class'] }}</td>
                <td>{{ $row['fee_type'] }}</td>
                <td>{{ number_format($row['expected_amount'], 2) }}</td>
                <td>{{ number_format($row['paid_amount'], 2) }}</td>
                <td>{{ number_format($row['unpaid_amount'], 2) }}</td>
                <td>{{ $row['status'] }}</td>
            </tr>
            @empty
            <tr><td colspan="10">No records</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
