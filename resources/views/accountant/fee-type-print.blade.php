<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Fee Type / Fee Head</title>

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
table{ width:100%; border-collapse:collapse; font-size:12px; }
th,td{ border:1px solid var(--theme-blue); padding:6px; }
th{ background:var(--theme-blue); color:#fff; text-align:left; }
.center{ text-align:center; }
tbody tr:nth-child(even){ background:#f2f2f2; }
.footer{ margin-top:15px; border-top:2px solid var(--theme-blue); padding-top:8px; display:flex; justify-content:space-between; font-size:12px; }
@media print{
  .no-print{ display:none; }
  @page{ size:A4; margin:10mm; }
}
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
    <div class="report-title">Fee Type / Fee Head</div>
  </div>

  <div class="top-bar">
    <div>
      Generated: {{ $printedAt ?? date('d M Y, h:i A') }}
      @if(request('search'))
        | Search: "{{ request('search') }}"
      @endif
    </div>
    <button onclick="window.print()" class="print-btn no-print">Print</button>
  </div>

  <table>
    <thead>
      <tr>
        <th style="width:60px;">#</th>
        <th>Fee Name</th>
        <th style="width:180px;">Campus</th>
      </tr>
    </thead>
    <tbody>
      @forelse($feeTypes as $i => $feeType)
        <tr>
          <td class="center">{{ $i + 1 }}</td>
          <td>{{ $feeType->fee_name ?? 'N/A' }}</td>
          <td>{{ $feeType->campus ?? 'N/A' }}</td>
        </tr>
      @empty
        <tr>
          <td colspan="3" class="center">No fee types found</td>
        </tr>
      @endforelse
    </tbody>
  </table>

  <div class="footer">
    <div><strong>Total:</strong> {{ $feeTypes->count() }}</div>
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

