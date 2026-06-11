<table border="1" cellpadding="6" cellspacing="0">
    <thead>
        <tr>
            <th>#</th>
            <th>Date</th>
            <th>Campus</th>
            <th>User</th>
            <th>Method</th>
            <th>Transaction ID</th>
            <th>Total Payment</th>
            <th>Remarks</th>
        </tr>
    </thead>
    <tbody>
        @forelse($rows as $row)
            <tr>
                <td>{{ $row['#'] }}</td>
                <td>{{ $row['Date'] }}</td>
                <td>{{ $row['Campus'] }}</td>
                <td>{{ $row['User'] }}</td>
                <td>{{ $row['Method'] }}</td>
                <td>{{ $row['Transaction ID'] }}</td>
                <td>{{ $row['Total Payment'] }}</td>
                <td>{{ $row['Remarks'] }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="8">No settlement records found.</td>
            </tr>
        @endforelse
    </tbody>
</table>
