<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Online Classes</title>
<style>
*{ margin:0; padding:0; box-sizing:border-box; }
:root{ --theme-blue:#003471; }
body{ font-family: Arial, sans-serif; background:#fff; color:#000; }
.print-container{ width:210mm; min-height:297mm; margin:auto; padding:15mm; }
.header{ text-align:center; border-bottom:2px solid var(--theme-blue); padding-bottom:10px; }
.school-name{ font-size:22px; font-weight:bold; color:var(--theme-blue); }
.school-info{ font-size:12px; margin-top:4px; }
.report-title{ margin-top:8px; font-size:16px; font-weight:bold; text-transform:uppercase; color:var(--theme-blue); }
.top-bar{ display:flex; justify-content:space-between; margin:10px 0; font-size:12px; }
.print-btn{ padding:5px 10px; border:1px solid var(--theme-blue); background:var(--theme-blue); color:#fff; cursor:pointer; }
table{ width:100%; border-collapse:collapse; font-size:10.5px; }
th,td{ border:1px solid var(--theme-blue); padding:5px; vertical-align:top; }
th{ background:var(--theme-blue); color:#fff; text-align:left; }
.center{ text-align:center; }
tbody tr:nth-child(even){ background:#f2f2f2; }
.footer{ margin-top:15px; border-top:2px solid var(--theme-blue); padding-top:8px; display:flex; justify-content:space-between; font-size:12px; }
@media print{ .no-print{ display:none; } @page{ size:A4; margin:10mm; } }
</style>
</head>
<body>
<div class="print-container">
  <div class="header">
    <div class="school-name">{{ $settings->school_name ?? 'School Name' }}</div>
    <div class="school-info">
      {{ $settings->address ?? '' }} |
      {{ $settings->school_phone ?? '' }} |
      {{ $settings->school_email ?? '' }}
    </div>
    <div class="report-title">Online Classes List</div>
  </div>

  <div class="top-bar">
    <div>
      Generated: {{ $printedAt ?? date('d M Y, h:i A') }}
      @if(request('search')) | Search: "{{ request('search') }}" @endif
    </div>
    <button onclick="window.print()" class="print-btn no-print">Print</button>
  </div>

  <table>
    <thead>
      <tr>
        <th style="width:40px;">#</th>
        <th>Class Topic</th>
        <th style="width:80px;">Start Time</th>
        <th style="width:80px;">Class</th>
        <th style="width:80px;">Section</th>
        <th style="width:90px;">Date</th>
        <th style="width:90px;">Timing</th>
        <th style="width:100px;">Campus</th>
      </tr>
    </thead>
    <tbody>
      @forelse($onlineClasses as $i => $class)
        <tr>
          <td class="center">{{ $i + 1 }}</td>
          <td>{{ $class->class_topic ?? 'N/A' }}</td>
          <td>{{ $class->start_time ? date('H:i', strtotime($class->start_time)) : 'N/A' }}</td>
          <td>{{ $class->class ?? 'N/A' }}</td>
          <td>{{ $class->section ?? 'N/A' }}</td>
          <td>{{ $class->start_date ? \Carbon\Carbon::parse($class->start_date)->format('d-m-Y') : 'N/A' }}</td>
          <td>{{ $class->timing ?? 'N/A' }}</td>
          <td>{{ $class->campus ?? 'N/A' }}</td>
        </tr>
      @empty
        <tr>
          <td colspan="8" class="center">No online classes found</td>
        </tr>
      @endforelse
    </tbody>
  </table>

  <div class="footer">
    <div><strong>Total:</strong> {{ $onlineClasses->count() }}</div>
    <div>System Generated Report</div>
  </div>
</div>

@if(request()->get('auto_print'))
<script>
window.onload = () => setTimeout(() => window.print(), 500);
</script>
@endif
</body>
</html>

