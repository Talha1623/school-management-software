@php
    $columnCount = 2 + ($feeHeads->count() * 2) + 2;
@endphp
<table border="1" cellpadding="6" cellspacing="0">
    <thead>
        <tr>
            <th colspan="{{ $columnCount }}">Head Wise Dues Summary</th>
        </tr>
        <tr>
            <th colspan="{{ $columnCount }}">{{ $filterDescription ?? '' }}</th>
        </tr>
        <tr>
            <th>Campus</th>
            <th>Class</th>
            @foreach($feeHeads as $head)
                <th>{{ $head }} Paid</th>
                <th>{{ $head }} Due</th>
            @endforeach
            <th>Total Paid</th>
            <th>Total Due</th>
        </tr>
    </thead>
    <tbody>
        @forelse($rows as $row)
            <tr>
                <td>{{ $row['Campus'] }}</td>
                <td>{{ $row['Class'] }}</td>
                @foreach($feeHeads as $head)
                    <td>{{ $row[$head . ' Paid'] ?? '0.00' }}</td>
                    <td>{{ $row[$head . ' Due'] ?? '0.00' }}</td>
                @endforeach
                <td>{{ $row['Total Paid'] ?? '0.00' }}</td>
                <td>{{ $row['Total Due'] ?? '0.00' }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="{{ $columnCount }}">No data found.</td>
            </tr>
        @endforelse
    </tbody>
</table>
