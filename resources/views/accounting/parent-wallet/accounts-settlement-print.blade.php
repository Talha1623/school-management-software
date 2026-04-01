<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Accounts Settlement</title>

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
.right{ text-align:right; }
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
    <div class="report-title">Accounts Settlement</div>
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
        <th style="width:40px;">#</th>
        <th>User</th>
        <th style="width:70px;">Type</th>
        <th style="width:80px;">Date</th>
        <th style="width:80px;">Balance</th>
        <th style="width:80px;">Income</th>
        <th style="width:80px;">Expense</th>
        <th style="width:80px;">Total</th>
        <th style="width:85px;">Method</th>
        <th style="width:60px;">Trx#</th>
        <th>Remarks</th>
      </tr>
    </thead>
    <tbody>
      @forelse($items as $i => $item)
        <tr>
          <td class="center">{{ $i + 1 }}</td>
          <td>{{ $item['user'] ?? 'N/A' }}</td>
          <td>{{ $item['type'] ?? 'N/A' }}</td>
          <td>{{ $item['date'] ?? 'N/A' }}</td>
          <td class="right">{{ number_format((float)($item['balance'] ?? 0), 2) }}</td>
          <td class="right">{{ number_format((float)($item['income'] ?? 0), 2) }}</td>
          <td class="right">{{ number_format((float)($item['expense'] ?? 0), 2) }}</td>
          <td class="right">{{ number_format((float)($item['total'] ?? 0), 2) }}</td>
          <td>{{ $item['method'] ?? 'N/A' }}</td>
          <td>{{ $item['trx'] ?? 'N/A' }}</td>
          <td>{{ $item['remarks'] ?? 'N/A' }}</td>
        </tr>
      @empty
        <tr>
          <td colspan="11" class="center">No records found</td>
        </tr>
      @endforelse
    </tbody>
  </table>

  <div class="footer">
    <div><strong>Total:</strong> {{ $items->count() }}</div>
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

