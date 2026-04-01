<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Advance Fee</title>

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
table{ width:100%; border-collapse:collapse; font-size:11px; }
th,td{ border:1px solid var(--theme-blue); padding:6px; vertical-align:top; }
th{ background:var(--theme-blue); color:#fff; text-align:left; }
.center{ text-align:center; }
.right{ text-align:right; }
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
    <div class="report-title">Advance Fee Records</div>
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
        <th style="width:45px;">#</th>
        <th style="width:70px;">Parent ID</th>
        <th>Name</th>
        <th>Email</th>
        <th style="width:95px;">Phone</th>
        <th style="width:120px;">ID Card</th>
        <th style="width:95px;">Available</th>
        <th style="width:85px;">Increase</th>
        <th style="width:85px;">Decrease</th>
        <th style="width:60px;">Childs</th>
      </tr>
    </thead>
    <tbody>
      @forelse($advanceFees as $i => $r)
        <tr>
          <td class="center">{{ $i + 1 }}</td>
          <td>{{ $r->parent_id ?? 'N/A' }}</td>
          <td>{{ $r->name ?? 'N/A' }}</td>
          <td>{{ $r->email ?? 'N/A' }}</td>
          <td>{{ $r->phone ?? 'N/A' }}</td>
          <td>{{ $r->id_card_number ?? 'N/A' }}</td>
          <td class="right">{{ number_format((float)($r->available_credit ?? 0), 2) }}</td>
          <td class="right">{{ number_format((float)($r->increase ?? 0), 2) }}</td>
          <td class="right">{{ number_format((float)($r->decrease ?? 0), 2) }}</td>
          <td class="center">{{ $r->childs ?? 0 }}</td>
        </tr>
      @empty
        <tr>
          <td colspan="10" class="center">No records found</td>
        </tr>
      @endforelse
    </tbody>
  </table>

  <div class="footer">
    <div><strong>Total:</strong> {{ $advanceFees->count() }}</div>
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

