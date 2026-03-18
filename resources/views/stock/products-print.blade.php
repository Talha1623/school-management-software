<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Products & Stock List</title>

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
    border-bottom:2px solid #000;
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
    border:1px solid #000;
    background:#000;
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
    border:1px solid #000;
    padding:6px;
}

th{
    background:#000;
    color:#fff;
    text-align:left;
}

.center{
    text-align:center;
}

.text-right{
    text-align:right;
}

/* Zebra */
tbody tr:nth-child(even){
    background:#f2f2f2;
}

/* Stock Colors */
.stock-good{ color:green; font-weight:bold; }
.stock-low{ color:orange; font-weight:bold; }
.stock-zero{ color:red; font-weight:bold; }

/* Footer */
.footer{
    margin-top:15px;
    border-top:2px solid #000;
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
    border-top:1px solid #000;
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
        Products & Stock List
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
<th>Product Name</th>
<th>Code</th>
<th>Category</th>
<th class="text-right">Purchase</th>
<th class="text-right">Sale</th>
<th class="text-right">Stock</th>
<th>Campus</th>
</tr>
</thead>

<tbody>

@forelse($products as $i => $p)

<tr>
<td class="center">{{ $i+1 }}</td>

<td>{{ $p->product_name }}</td>

<td>{{ $p->product_code ?? '-' }}</td>

<td>{{ $p->category }}</td>

<td class="text-right">
{{ number_format($p->purchase_price,2) }}
</td>

<td class="text-right" style="color:green;font-weight:bold;">
{{ number_format($p->sale_price,2) }}
</td>

<td class="text-right
@if($p->total_stock <= 0) stock-zero
@elseif($p->total_stock <= 5) stock-low
@else stock-good
@endif
">
{{ $p->total_stock }}
</td>

<td>{{ $p->campus }}</td>

</tr>

@empty

<tr>
<td colspan="8" class="center">No products found</td>
</tr>

@endforelse

</tbody>
</table>

<!-- Footer -->
<div class="footer">
<div><strong>Total Products:</strong> {{ $products->count() }}</div>
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