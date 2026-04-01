<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Job Inquiry / CV Bank List</title>

<style>
*{
    margin:0;
    padding:0;
    box-sizing:border-box;
}

:root{
    --theme-blue: #003471;
}

body{
    font-family: Arial, sans-serif;
    background:#fff;
    color:#000;
}

/* A4 Page */
.print-container{
    width:210mm;
    min-height:297mm;
    margin:auto;
    padding:15mm;
}

/* Header */
.header{
    text-align:center;
    border-bottom:2px solid var(--theme-blue);
    padding-bottom:10px;
}

.school-name{
    font-size:22px;
    font-weight:bold;
}

.school-info{
    font-size:12px;
    margin-top:4px;
}

.report-title{
    margin-top:8px;
    font-size:16px;
    font-weight:bold;
    text-transform:uppercase;
}

/* Top Bar */
.top-bar{
    display:flex;
    justify-content:space-between;
    margin:10px 0;
    font-size:12px;
}

.print-btn{
    padding:5px 10px;
    border:1px solid var(--theme-blue);
    background:var(--theme-blue);
    color:#fff;
    cursor:pointer;
}

/* Table */
table{
    width:100%;
    border-collapse:collapse;
    font-size:12px;
}

th, td{
    border:1px solid var(--theme-blue);
    padding:6px;
}

th{
    background:var(--theme-blue);
    color:#fff;
    text-align:left;
}

.center{
    text-align:center;
}

/* Zebra */
tbody tr:nth-child(even){
    background:#f2f2f2;
}

/* Footer */
.footer{
    margin-top:15px;
    border-top:2px solid var(--theme-blue);
    padding-top:8px;
    display:flex;
    justify-content:space-between;
    font-size:12px;
}

/* Signature */
.signature{
    margin-top:40px;
    display:flex;
    justify-content:space-between;
    font-size:12px;
}

.signature div{
    text-align:center;
    width:200px;
    border-top:1px solid var(--theme-blue);
    padding-top:5px;
}

/* Print */
@media print{
    .no-print{ display:none; }

    @page{
        size:A4;
        margin:10mm;
    }
}
</style>

</head>

<body>

<div class="print-container">

<!-- Header -->
<div class="header">
    <div class="school-name">
        {{ $settings->school_name ?? 'School Name' }}
    </div>

    <div class="school-info">
        {{ $settings->address ?? '' }} |
        {{ $settings->school_phone ?? '' }} |
        {{ $settings->school_email ?? '' }}
    </div>

    <div class="report-title">
        Job Inquiry / CV Bank
    </div>
</div>

<!-- Top Bar -->
<div class="top-bar">
    <div>
        Generated: {{ date('d M Y, h:i A') }}
        @if(request('search'))
            | Search: "{{ request('search') }}"
        @endif
    </div>

    <button onclick="window.print()" class="print-btn no-print">
        Print
    </button>
</div>

<!-- Table -->
<table>
<thead>
<tr>
    <th>#</th>
    <th>Name</th>
    <th>Father/Husband</th>
    <th>Campus</th>
    <th>Gender</th>
    <th>Phone</th>
    <th>Qualification</th>
    <th>Applied For</th>
    <th>Email</th>
</tr>
</thead>

<tbody>
@forelse($inquiries as $i => $inq)
<tr>
    <td class="center">{{ $i + 1 }}</td>
    <td>{{ $inq->name ?? 'N/A' }}</td>
    <td>{{ $inq->father_husband_name ?? 'N/A' }}</td>
    <td>{{ $inq->campus ?? 'N/A' }}</td>
    <td>{{ $inq->gender ?? 'N/A' }}</td>
    <td>{{ $inq->phone ?? 'N/A' }}</td>
    <td>{{ $inq->qualification ?? 'N/A' }}</td>
    <td>{{ $inq->applied_for_designation ?? 'N/A' }}</td>
    <td>{{ $inq->email ?? 'N/A' }}</td>
</tr>
@empty
<tr>
    <td colspan="9" class="center">No job inquiries found</td>
</tr>
@endforelse
</tbody>
</table>

<!-- Footer -->
<div class="footer">
    <div><strong>Total Inquiries:</strong> {{ $inquiries->count() }}</div>
    <div>System Generated Report</div>
</div>

<!-- Signatures -->
<div class="signature">
    <div>Prepared By</div>
    <div>Checked By</div>
    <div>Approved By</div>
</div>

</div>

@if(request()->get('auto_print'))
<script>
window.onload = () => setTimeout(() => window.print(), 500);
</script>
@endif

</body>
</html>

