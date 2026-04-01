<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Subjects</title>

<style>
*{
    margin:0;
    padding:0;
    box-sizing:border-box;
}

body{
    font-family: Arial, sans-serif;
    background:#fff;
    color:#000;
}

/* A4 Layout */
.print-container{
    width:210mm;
    min-height:297mm;
    margin:auto;
    padding:15mm;
}

/* Header */
.header{
    text-align:center;
    border-bottom:2px solid #003471;
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
    color:#003471;
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
    border:1px solid #003471;
    background:#003471;
    color:#fff;
    cursor:pointer;
}

/* Table */
table{
    width:100%;
    border-collapse:collapse;
    font-size:12px;
    border:2px solid #003471; /* only outer border */
}

th{
    background:#003471;
    color:#fff;
    padding:8px;
    text-align:left;
    border-bottom:2px solid #003471;
}

td{
    padding:8px;
}

/* Row lines only */
tbody tr{
    border-bottom:1px solid #ccc;
}

tbody tr:last-child{
    border-bottom:none;
}

/* Zebra style */
tbody tr:nth-child(even){
    background:#f5f5f5;
}

/* Alignments */
.center{
    text-align:center;
}

/* Badge (simple) */
.badge{
    padding:2px 6px;
    border:1px solid #003471;
    font-size:11px;
    font-weight:bold;
}

/* Footer */
.footer{
    margin-top:15px;
    border-top:2px solid #003471;
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
    width:200px;
    text-align:center;
    border-top:1px solid #003471;
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
        Manage Subjects Report
    </div>
</div>

<!-- Top Bar -->
<div class="top-bar">
    <div>
        Generated: {{ date('d M Y, h:i A') }}
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
<th>Campus</th>
<th>Class</th>
<th>Section</th>
<th>Subject</th>
<th>Teacher</th>
<th>Session</th>
</tr>
</thead>

<tbody>

@forelse($subjects as $i => $s)

<tr>
<td class="center">{{ $i+1 }}</td>

<td><span class="badge">{{ $s->campus }}</span></td>

<td><span class="badge">{{ $s->class }}</span></td>

<td><span class="badge">{{ $s->section }}</span></td>

<td><strong>{{ $s->subject_name }}</strong></td>

<td>{{ $s->teacher ?? '-' }}</td>

<td><span class="badge">{{ $s->session ?? '-' }}</span></td>

</tr>

@empty

<tr>
<td colspan="7" class="center">No subjects found</td>
</tr>

@endforelse

</tbody>

</table>

<!-- Footer -->
<div class="footer">
<div><strong>Total Subjects:</strong> {{ $subjects->count() }}</div>
<div>System Generated Report</div>
</div>

<!-- Signature -->
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