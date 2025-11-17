@extends('layouts.app')

@section('title', 'Staff Birthdays')

@section('content')
<div class="row">
    @if(session('success'))
        <div class="col-12">
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="col-12">
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
    @endif

    <!-- Staff Birthdays Column -->
    <div class="col-12 mb-4">
        <div class="card bg-white border border-white rounded-10 p-3">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0 fs-16 fw-semibold">Staff Birthdays</h4>
                <!-- Export Buttons -->
                <div class="d-flex gap-2">
                    <a href="{{ route('staff.birthday.export', ['format' => 'excel']) }}" class="btn btn-sm px-2 py-1 export-btn excel-btn">
                        <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">description</span>
                        <span>Excel</span>
                    </a>
                    <a href="{{ route('staff.birthday.export', ['format' => 'csv']) }}" class="btn btn-sm px-2 py-1 export-btn csv-btn">
                        <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">file_present</span>
                        <span>CSV</span>
                    </a>
                    <a href="{{ route('staff.birthday.export', ['format' => 'pdf']) }}" class="btn btn-sm px-2 py-1 export-btn pdf-btn" target="_blank">
                        <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">picture_as_pdf</span>
                        <span>PDF</span>
                    </a>
                    <button type="button" class="btn btn-sm px-2 py-1 export-btn print-btn" onclick="printStaffTable()">
                        <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">print</span>
                        <span>Print</span>
                    </button>
                </div>
            </div>

            <!-- Table Header -->
            <div class="mb-2 p-2 rounded-8" style="background: linear-gradient(135deg, #003471 0%, #004a9f 100%);">
                <h5 class="mb-0 text-white fs-15 fw-semibold d-flex align-items-center gap-2">
                    <span class="material-symbols-outlined" style="font-size: 18px;">cake</span>
                    <span>Staff Birthdays</span>
                </h5>
            </div>

            <div class="default-table-area" style="margin-top: 0;">
                <div class="table-responsive" style="max-height: none; overflow: visible; overflow-x: auto;">
                    <table class="table table-sm table-hover" id="staffBirthdayTable" style="margin-bottom: 0; white-space: nowrap;">
                        <thead>
                            <tr>
                                <th style="padding: 12px 15px; font-size: 14px;">Picture</th>
                                <th style="padding: 12px 15px; font-size: 14px;">Name</th>
                                <th style="padding: 12px 15px; font-size: 14px;">Father/Husband</th>
                                <th style="padding: 12px 15px; font-size: 14px;">Birthday</th>
                                <th style="padding: 12px 15px; font-size: 14px;">Status</th>
                                <th style="padding: 12px 15px; font-size: 14px;">Wish</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($staff as $index => $member)
                                <tr>
                                    <td style="padding: 12px 15px; font-size: 14px;">
                                        @if($member['picture'])
                                            <img src="{{ $member['picture'] }}" alt="Staff" class="rounded-circle" style="width: 45px; height: 45px; object-fit: cover; border: 2px solid #e9ecef;">
                                        @else
                                            <div class="rounded-circle bg-light d-inline-flex align-items-center justify-content-center" style="width: 45px; height: 45px; border: 2px solid #e9ecef;">
                                                <span class="material-symbols-outlined text-muted" style="font-size: 22px;">person</span>
                                            </div>
                                        @endif
                                    </td>
                                    <td style="padding: 12px 15px; font-size: 14px;">
                                        <strong class="text-primary">{{ $member['name'] ?? 'N/A' }}</strong>
                                    </td>
                                    <td style="padding: 12px 15px; font-size: 14px;">{{ $member['father_husband'] ?? 'N/A' }}</td>
                                    <td style="padding: 12px 15px; font-size: 14px;">
                                        <span class="text-muted">
                                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">calendar_today</span>
                                            @if(isset($member['birthday']) && $member['birthday'])
                                                {{ \Carbon\Carbon::parse($member['birthday'])->format('d M Y') }}
                                            @else
                                                N/A
                                            @endif
                                        </span>
                                    </td>
                                    <td style="padding: 12px 15px; font-size: 14px;">
                                        @php
                                            $statusClass = match($member['status'] ?? '') {
                                                'Today' => 'bg-success',
                                                'Upcoming' => 'bg-info',
                                                'Past' => 'bg-secondary',
                                                default => 'bg-secondary'
                                            };
                                        @endphp
                                        <span class="badge {{ $statusClass }} text-white" style="font-size: 11px;">{{ $member['status'] ?? 'N/A' }}</span>
                                    </td>
                                    <td style="padding: 12px 15px; font-size: 14px;">
                                        @php
                                            $wishClass = ($member['wish'] ?? '') === 'Sent' ? 'bg-success' : 'bg-warning';
                                        @endphp
                                        <span class="badge {{ $wishClass }} text-white" style="font-size: 11px;">{{ $member['wish'] ?? 'Pending' }}</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-5">
                                        <span class="material-symbols-outlined" style="font-size: 48px; opacity: 0.3;">inbox</span>
                                        <p class="mt-2 mb-0">No staff birthdays found.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .rounded-8 {
        border-radius: 8px;
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
        padding: 4px 12px;
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
    
    .export-btn:active {
        transform: translateY(0);
    }

    /* Table Styling */
    .default-table-area table {
        margin-bottom: 0;
        border-spacing: 0;
        border-collapse: collapse;
        border: 1px solid #dee2e6;
    }
    
    .default-table-area table thead {
        border-bottom: 1px solid #dee2e6;
    }
    
    .default-table-area table thead th {
        padding: 12px 15px;
        font-size: 14px;
        font-weight: 600;
        vertical-align: middle;
        line-height: 1.5;
        white-space: nowrap;
        border: 1px solid #dee2e6;
        background-color: #f8f9fa;
    }
    
    .default-table-area table thead th:first-child {
        border-left: 1px solid #dee2e6;
    }
    
    .default-table-area table thead th:last-child {
        border-right: 1px solid #dee2e6;
    }
    
    .default-table-area table tbody td {
        padding: 12px 15px;
        font-size: 14px;
        vertical-align: middle;
        line-height: 1.5;
        border: 1px solid #dee2e6;
    }
    
    .default-table-area table tbody td:first-child {
        border-left: 1px solid #dee2e6;
    }
    
    .default-table-area table tbody td:last-child {
        border-right: 1px solid #dee2e6;
    }
    
    .default-table-area table tbody tr:last-child td {
        border-bottom: 1px solid #dee2e6;
    }
    
    .default-table-area table thead th:first-child,
    .default-table-area table tbody td:first-child {
        padding-left: 15px;
    }
    
    .default-table-area table thead th:last-child,
    .default-table-area table tbody td:last-child {
        padding-right: 15px;
    }
    
    .default-table-area table tbody tr:first-child td {
        border-top: none;
    }
    
    .default-table-area .badge {
        font-size: 11px;
        padding: 4px 8px;
    }

    @media print {
        .export-btn, .rounded-8 {
            display: none !important;
        }
        
        .table {
            border: 1px solid #000;
        }
    }

    @media (max-width: 768px) {
        .col-md-6 {
            margin-bottom: 20px !important;
        }
    }
</style>

<script>
function printStaffTable() {
    const printWindow = window.open('', '_blank');
    const table = document.getElementById('staffBirthdayTable');
    const tableHTML = table.outerHTML;
    
    printWindow.document.write(`
        <html>
            <head>
                <title>Staff Birthdays - Print</title>
                <style>
                    body { font-family: Arial, sans-serif; margin: 20px; }
                    table { width: 100%; border-collapse: collapse; }
                    th { background-color: #003471; color: white; padding: 12px; text-align: left; }
                    td { padding: 10px; border-bottom: 1px solid #ddd; }
                    tr:nth-child(even) { background-color: #f8f9fa; }
                    @media print { body { margin: 0; } }
                </style>
            </head>
            <body>
                <h2>Staff Birthdays</h2>
                ${tableHTML}
            </body>
        </html>
    `);
    printWindow.document.close();
    printWindow.print();
}
</script>
@endsection
