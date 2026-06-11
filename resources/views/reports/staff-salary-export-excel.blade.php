<table border="1" cellpadding="6" cellspacing="0">
    <thead>
        <tr>
            <th colspan="16" style="font-weight:bold;">Staff Salary Reports</th>
        </tr>
        <tr>
            <th colspan="16">{{ $filterDescription ?? '' }}</th>
        </tr>
        <tr>
            <th>#</th>
            <th>Staff Name</th>
            <th>Emp ID</th>
            <th>Campus</th>
            <th>Designation</th>
            <th>Salary Month</th>
            <th>Year</th>
            <th>Present</th>
            <th>Absent</th>
            <th>Late</th>
            <th>Early Exit</th>
            <th>Basic</th>
            <th>Salary Generated</th>
            <th>Amount Paid</th>
            <th>Loan Repayment</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        @forelse($rows as $row)
            <tr>
                <td>{{ $row['#'] }}</td>
                <td>{{ $row['Staff Name'] }}</td>
                <td>{{ $row['Emp ID'] }}</td>
                <td>{{ $row['Campus'] }}</td>
                <td>{{ $row['Designation'] }}</td>
                <td>{{ $row['Salary Month'] }}</td>
                <td>{{ $row['Year'] }}</td>
                <td>{{ $row['Present'] }}</td>
                <td>{{ $row['Absent'] }}</td>
                <td>{{ $row['Late'] }}</td>
                <td>{{ $row['Early Exit'] }}</td>
                <td>{{ $row['Basic'] }}</td>
                <td>{{ $row['Salary Generated'] }}</td>
                <td>{{ $row['Amount Paid'] }}</td>
                <td>{{ $row['Loan Repayment'] }}</td>
                <td>{{ $row['Status'] }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="16">No salary records found.</td>
            </tr>
        @endforelse
    </tbody>
</table>
