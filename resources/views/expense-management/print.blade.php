<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Management Expense — Voucher</title>
    <style>
        @page { size: A4 portrait; margin: 10mm; }
        * { box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #fff; color: #111827; margin: 0; }
        .print-container { width: 100%; max-width: 210mm; margin: 0 auto; padding: 0 4px; }
        :root { --theme-blue: #003471; }
        .header-section { border-bottom: 3px solid var(--theme-blue); padding-bottom: 10px; text-align: center; }
        .school-name { font-size: 18px; font-weight: 800; color: var(--theme-blue); }
        .school-details { font-size: 11px; color: #374151; margin-top: 4px; }
        .report-title { font-size: 14px; font-weight: 800; color: var(--theme-blue); margin-top: 6px; text-transform: uppercase; }
        .meta { font-size: 11px; color: #374151; margin-top: 4px; }
        .top-bar { display: flex; justify-content: flex-end; margin: 10px 0 6px; }
        .print-btn { border: 1px solid var(--theme-blue); background: var(--theme-blue); color: #fff; padding: 6px 12px; cursor: pointer; }
        .section-label { font-size: 11px; font-weight: 700; color: var(--theme-blue); text-transform: uppercase; margin: 14px 0 6px; letter-spacing: 0.02em; }
        table.detail { width: 100%; border-collapse: collapse; font-size: 11px; }
        table.detail th, table.detail td { border: 1px solid var(--theme-blue); padding: 8px 10px; vertical-align: top; text-align: left; }
        table.detail th { background: var(--theme-blue); color: #fff; font-weight: 700; width: 22%; }
        .amount { font-size: 15px; font-weight: 800; color: var(--theme-blue); text-align: right; }
        .desc-box { border: 1px solid var(--theme-blue); padding: 10px; font-size: 11px; min-height: 48px; background: #f9fafb; }
        .receipt-wrap { margin-top: 10px; text-align: center; page-break-inside: avoid; }
        .receipt-wrap img { max-width: 100%; max-height: 320px; object-fit: contain; border: 1px solid #e5e7eb; }
        .receipt-missing { font-size: 11px; color: #6b7280; font-style: italic; }
        .footer-section { border-top: 2px solid var(--theme-blue); margin-top: 16px; padding-top: 10px; display: flex; justify-content: space-between; flex-wrap: wrap; gap: 8px; font-size: 11px; color: #374151; }
        @media print {
            .no-print { display: none !important; }
            .receipt-wrap img { max-height: 380px; }
        }
    </style>
</head>
<body>
@php
    $e = $managementExpense;
    $currency = $settings->currency ?? 'PKR';
@endphp
<div class="print-container">
    <div class="header-section">
        <div class="school-name">{{ $settings->school_name ?? 'School Name' }}</div>
        <div class="school-details">
            {{ $settings->address ?? '' }}
            @if(!empty($settings->school_phone)) | {{ $settings->school_phone }} @endif
            @if(!empty($settings->school_email)) | {{ $settings->school_email }} @endif
        </div>
        <div class="report-title">Management Expense Voucher</div>
        <div class="meta">
            Record ID: {{ $e->id }}
            &nbsp;|&nbsp; Expense date: {{ $e->date->format('d M Y') }}
            &nbsp;|&nbsp; Generated: {{ $printedAt }}
        </div>
    </div>

    <div class="top-bar no-print">
        <button type="button" class="print-btn" onclick="window.print()">Print</button>
    </div>

    <div class="section-label">Expense details</div>
    <table class="detail">
        <tbody>
        <tr>
            <th>Campus</th>
            <td>{{ $e->campus ?? '—' }}</td>
            <th>Category</th>
            <td>{{ $e->category ?? '—' }}</td>
        </tr>
        <tr>
            <th>Title</th>
            <td colspan="3">{{ $e->title ?? '—' }}</td>
        </tr>
        <tr>
            <th>Payment method</th>
            <td>{{ $e->method ?? '—' }}</td>
            <th>Notify admin</th>
            <td>{{ ($e->notify_admin ?? false) ? 'Yes' : 'No' }}</td>
        </tr>
        <tr>
            <th>Amount</th>
            <td colspan="3" class="amount">{{ $currency }} {{ number_format((float) $e->amount, 2) }}</td>
        </tr>
        <tr>
            <th>Recorded by</th>
            <td>{{ $e->created_by ?? '—' }}</td>
            <th>System time</th>
            <td>{{ $e->created_at ? $e->created_at->format('d M Y, h:i A') : '—' }}</td>
        </tr>
        <tr>
            <th>Invoice / receipt file</th>
            <td colspan="3">
                @if($hasReceiptFile)
                    Attached (see image below)
                @elseif($e->invoice_receipt)
                    Path on file: {{ $e->invoice_receipt }} (file missing from storage)
                @else
                    Not uploaded
                @endif
            </td>
        </tr>
        </tbody>
    </table>

    <div class="section-label">Description / notes</div>
    <div class="desc-box">{{ trim($e->description ?? '') !== '' ? $e->description : '—' }}</div>

    @if($hasReceiptFile)
        <div class="section-label">Invoice / receipt image</div>
        <div class="receipt-wrap">
            <img src="{{ asset('storage/' . $e->invoice_receipt) }}" alt="Invoice or receipt">
        </div>
    @endif

    <div class="footer-section">
        <div>
            <strong>Voucher #{{ $e->id }}</strong>
            &nbsp;|&nbsp; {{ $e->campus ?? '' }} — {{ $e->category ?? '' }}
        </div>
        <div>System generated document</div>
    </div>
</div>

@if(request()->get('auto_print'))
<script>
window.addEventListener('load', function () {
    setTimeout(function () { window.print(); }, 300);
});
</script>
@endif
</body>
</html>
