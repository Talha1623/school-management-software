<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Timetable Print</title>
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

.print-container{
    width:210mm;
    min-height:297mm;
    margin:auto;
    padding:15mm;
}

.header{
    text-align:center;
    border-bottom:2px solid var(--theme-blue);
    padding-bottom:10px;
}

.school-logo{
    max-height:60px;
    max-width:120px;
    margin-bottom:6px;
    object-fit:contain;
}

.school-name{
    font-size:22px;
    font-weight:bold;
    color:var(--theme-blue);
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
    color:var(--theme-blue);
}

.top-bar{
    display:flex;
    justify-content:space-between;
    align-items:center;
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

table{
    width:100%;
    border-collapse:collapse;
    font-size:12px;
}

th, td{
    border:1px solid var(--theme-blue);
    padding:8px 10px;
    vertical-align:top;
}

th{
    background:var(--theme-blue);
    color:#fff;
    text-align:left;
    width:30%;
}

td{
    width:70%;
}

.footer{
    margin-top:15px;
    border-top:2px solid var(--theme-blue);
    padding-top:8px;
    display:flex;
    justify-content:space-between;
    font-size:12px;
}

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

<div class="header">
    @if(!empty($settings->logo))
        <img src="{{ asset('storage/' . ltrim($settings->logo, '/')) }}" alt="Logo" class="school-logo">
    @endif
    <div class="school-name">{{ $settings->school_name ?? 'School Name' }}</div>
    <div class="school-info">
        {{ $settings->address ?? '' }}
        @if(!empty($settings->school_phone)) | {{ $settings->school_phone }} @endif
        @if(!empty($settings->school_email)) | {{ $settings->school_email }} @endif
    </div>
    <div class="report-title">Timetable Details</div>
</div>

<div class="top-bar">
    <div>Generated: {{ $printedAt ?? date('d M Y, h:i A') }}</div>
    <button type="button" onclick="window.print()" class="print-btn no-print">Print</button>
</div>

<table>
<tbody>
<tr>
    <th>Campus</th>
    <td>{{ $timetable->campus ?? 'N/A' }}</td>
</tr>
<tr>
    <th>Class</th>
    <td>{{ $timetable->class ?? 'N/A' }}</td>
</tr>
<tr>
    <th>Section</th>
    <td>{{ $timetable->section ?? 'N/A' }}</td>
</tr>
<tr>
    <th>Subject</th>
    <td>{{ $timetable->subject ?? 'N/A' }}</td>
</tr>
<tr>
    <th>Teacher</th>
    <td>{{ $timetable->assigned_teacher ?? 'N/A' }}</td>
</tr>
<tr>
    <th>Day</th>
    <td>{{ $timetable->day ?? 'N/A' }}</td>
</tr>
<tr>
    <th>Starting Time</th>
    <td>{{ $timetable->starting_time ? date('H:i', strtotime($timetable->starting_time)) : 'N/A' }}</td>
</tr>
<tr>
    <th>Ending Time</th>
    <td>{{ $timetable->ending_time ? date('H:i', strtotime($timetable->ending_time)) : 'N/A' }}</td>
</tr>
</tbody>
</table>

<div class="footer">
    <div>System Generated Report</div>
    <div>{{ $printedAt ?? date('d M Y, h:i A') }}</div>
</div>

<div class="signature">
    <div>Prepared By</div>
    <div>Checked By</div>
    <div>Approved By</div>
</div>

</div>

<script>
window.onload = function () {
    setTimeout(function () {
        window.print();
    }, 400);
};
</script>
</body>
</html>
