<!DOCTYPE html>
<html>
<head>
    <title>Loan Management - PDF Export</title>
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
            font-size: 11px;
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
    </style>
</head>
<body>
    <h1>Loan Management Report</h1>
    <p><strong>Generated:</strong> {{ date('Y-m-d H:i:s') }}</p>
    <p><strong>Total Loans:</strong> {{ $loans->count() }}</p>
    
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Teacher Name</th>
                <th class="text-right">Requested Amount</th>
                <th class="text-right">Approved Amount</th>
                <th class="text-center">Repayment Instalments</th>
                <th>Status</th>
                <th>Created At</th>
            </tr>
        </thead>
        <tbody>
            @forelse($loans as $loan)
                <tr>
                    <td>{{ $loan->id }}</td>
                    <td>{{ $loan->staff->name ?? 'N/A' }}</td>
                    <td class="text-right">₹{{ number_format($loan->requested_amount, 2) }}</td>
                    <td class="text-right">{{ $loan->approved_amount ? '₹' . number_format($loan->approved_amount, 2) : 'N/A' }}</td>
                    <td class="text-center">{{ $loan->repayment_instalments }}</td>
                    <td>{{ $loan->status }}</td>
                    <td>{{ $loan->created_at->format('Y-m-d H:i:s') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align: center;">No loans found.</td>
                </tr>
            @endforelse
        </tbody>
        @if($loans->count() > 0)
        <tfoot>
            <tr style="background-color: #003471; color: white; font-weight: bold;">
                <td colspan="2" class="text-right">Total:</td>
                <td class="text-right">₹{{ number_format($loans->sum('requested_amount'), 2) }}</td>
                <td class="text-right">₹{{ number_format($loans->whereNotNull('approved_amount')->sum('approved_amount'), 2) }}</td>
                <td colspan="3"></td>
            </tr>
        </tfoot>
        @endif
    </table>
</body>
</html>

