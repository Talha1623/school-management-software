@extends('layouts.app')

@section('title', 'Fee Installments')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-3">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-3 p-3 rounded-8" style="background-color: #f8f9fa; border: 1px solid #e9ecef;">
                <div class="d-flex align-items-center gap-2">
                    <span class="material-symbols-outlined" style="font-size: 18px; color: #003471;">list</span>
                    <div>
                        <h5 class="mb-0 fs-15 fw-semibold" style="color: #003471;">Fee Installments</h5>
                        <small class="text-muted">Installment list with payment status</small>
                    </div>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <a href="javascript:void(0)" class="btn btn-sm px-2 py-1 export-btn excel-btn" onclick="exportInstallments('excel')">
                        <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">description</span>
                        <span>Excel</span>
                    </a>
                    <a href="javascript:void(0)" class="btn btn-sm px-2 py-1 export-btn csv-btn" onclick="exportInstallments('csv')">
                        <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">file_present</span>
                        <span>CSV</span>
                    </a>
                    <a href="javascript:void(0)" class="btn btn-sm px-2 py-1 export-btn pdf-btn" onclick="exportInstallments('pdf')">
                        <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">picture_as_pdf</span>
                        <span>PDF</span>
                    </a>
                    <button type="button" class="btn btn-sm px-2 py-1 export-btn print-btn" onclick="exportInstallments('print')">
                        <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">print</span>
                        <span>Print</span>
                    </button>
                </div>
            </div>
               
            <div class="mb-2 p-2 rounded-8" style="background: linear-gradient(135deg, #003471 0%, #004a9f 100%);">
                <h5 class="mb-0 text-white fs-15 fw-semibold d-flex align-items-center gap-2">
                    <span class="material-symbols-outlined" style="font-size: 18px;">list</span>
                    <span>Installments List</span>
                </h5>
            </div>

            <div class="default-table-area" style="margin-top: 0;">
                <div class="table-responsive" style="max-height: none; overflow: visible; overflow-x: auto;">
                    <table class="table table-sm table-hover" id="installmentsTable" style="margin-bottom: 0; white-space: nowrap;">
                        <thead>
                        <tr>
                            <th>Name</th>
                            <th>Parent</th>
                            <th>Class/Section</th>
                            <th>Installment Title</th>
                            <th>Fee Month</th>
                            <th>Installment Fee</th>
                            <th>Amount Paid</th>
                            <th>More</th>
                        </tr>
                        </thead>
                        <tbody id="installmentsBody">
                            <tr>
                                <td colspan="8" class="text-center text-muted">Loading...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .default-table-area thead th {
        background-color: #f8f9fa;
        font-size: 14px;
        font-weight: 600;
        padding: 12px 15px;
        border-bottom: 1px solid #e9ecef;
        color: #343a40;
    }

    .default-table-area tbody td {
        font-size: 14px;
        padding: 12px 15px;
    }

    /* Export Buttons Styling */
    .export-btn {
        border: none;
        font-weight: 500;
        transition: all 0.3s ease;
        border-radius: 6px;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        height: 32px;
        font-size: 13px;
    }

    .excel-btn {
        background-color: #28a745;
        color: white;
    }

    .excel-btn:hover {
        background-color: #218838;
        color: white;
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(40, 167, 69, 0.3);
    }

    .csv-btn {
        background-color: #ff9800;
        color: white;
    }

    .csv-btn:hover {
        background-color: #f57c00;
        color: white;
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(255, 152, 0, 0.3);
    }

    .pdf-btn {
        background-color: #dc3545;
        color: white;
    }

    .pdf-btn:hover {
        background-color: #c82333;
        color: white;
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(220, 53, 69, 0.3);
    }

    .print-btn {
        background-color: #2196f3;
        color: white;
    }

    .print-btn:hover {
        background-color: #0b7dda;
        color: white;
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(33, 150, 243, 0.3);
    }

    .export-btn:active {
        transform: translateY(0);
    }

    @media print {
        .export-buttons {
            display: none !important;
        }
        .card {
            box-shadow: none !important;
            border: none !important;
        }
        body {
            background: white !important;
        }
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    fetchInstallments();
});

function fetchInstallments() {
    fetch(`{{ route('accounting.parent-wallet.installments.data') }}`, {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        renderInstallments(data.items || []);
    })
    .catch(error => {
        console.error('Error loading installments:', error);
        renderInstallments([]);
    });
}

function renderInstallments(items) {
    const tbody = document.getElementById('installmentsBody');
    tbody.innerHTML = '';

    if (!items || items.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted">No installments found.</td></tr>';
        return;
    }

    items.forEach(item => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${escapeHtml(item.name || 'N/A')}</td>
            <td>${escapeHtml(item.parent || 'N/A')}</td>
            <td>${escapeHtml(item.class_section || 'N/A')}</td>
            <td>${escapeHtml(item.installment_title || 'N/A')}</td>
            <td>${escapeHtml(item.fee_month || 'N/A')}</td>
            <td>${formatNumber(item.installment_fee)}</td>
            <td>${formatNumber(item.amount_paid)}</td>
            <td>${item.more || ''}</td>
        `;
        tbody.appendChild(row);
    });
}

function formatNumber(value) {
    const number = parseFloat(value);
    if (Number.isNaN(number)) {
        return '0.00';
    }
    return number.toFixed(2);
}

function escapeHtml(value) {
    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function exportInstallments(type) {
    if (type === 'print' || type === 'pdf') {
        window.print();
        return;
    }

    const rows = Array.from(document.querySelectorAll('#installmentsTable tr'));
    const csv = rows.map(row => {
        const cols = Array.from(row.querySelectorAll('th,td')).map(col => {
            return `"${(col.innerText || '').replace(/"/g, '""')}"`;
        });
        return cols.join(',');
    }).join('\n');

    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = `fee-installments.${type === 'excel' ? 'csv' : 'csv'}`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);
}
</script>
@endsection

