@extends('layouts.app')

@section('title', 'Barcode Attendance')

@section('content')
<div class="row g-4">
    <!-- Top Left Section: LIVE - Barcode Machine Attendance -->
    <div class="col-md-6">
        <div class="card border-0 barcode-card" style="border-radius: 12px; overflow: hidden; box-shadow: 0 2px 12px rgba(0,0,0,0.08);">
            <!-- Header -->
            <div class="barcode-header p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-2">
                        <span class="live-indicator"></span>
                        <h5 class="mb-0 fs-15 fw-semibold text-white">LIVE - Barcode Machine Attendance</h5>
                    </div>
                    <button type="button" class="btn btn-sm px-3 py-2 close-btn">
                        <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">check</span>
                        <span>Close Attendance</span>
                    </button>
                </div>
            </div>

            <!-- Content -->
            <div class="card-body p-4" style="background: linear-gradient(to bottom, #ffffff 0%, #f8f9fa 100%);">
                <!-- Input Field -->
                <div class="input-group mb-4 barcode-input-group" style="height: 52px;">
                    <span class="input-group-text barcode-input-icon">
                        <span class="material-symbols-outlined">person</span>
                    </span>
                    <input type="text" class="form-control barcode-input" id="barcodeInput" placeholder="Waiting for card scanning ....." autofocus>
                    <button type="button" class="btn barcode-search-btn" id="searchBtn">
                        <span class="material-symbols-outlined">search</span>
                    </button>
                </div>

                <!-- Barcode Scanner Graphic -->
                <div class="text-center mb-4">
                    <div class="scanner-circle">
                        <div class="scanner-pulse"></div>
                        <div class="scanner-content">
                            <div class="scanner-icon">
                                <span class="material-symbols-outlined">qr_code_scanner</span>
                            </div>
                            <div class="barcode-display">
                                <div class="barcode-lines"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Instruction Text -->
                <div class="text-center">
                    <p class="instruction-text mb-0" id="scanInstruction">
                        <span class="material-symbols-outlined" style="font-size: 18px; vertical-align: middle; margin-right: 6px;">info</span>
                        Scan Student ID Card Into Barcode Machine...!
                    </p>
                    <div id="scanMessage" class="scan-message d-none"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Right Section: Student Information -->
    <div class="col-md-6">
        <div class="card border-0 info-card" style="border-radius: 12px; overflow: hidden; box-shadow: 0 2px 12px rgba(0,0,0,0.08);">
            <!-- Header -->
            <div class="info-header p-3">
                <div class="d-flex align-items-center gap-2">
                    <span class="material-symbols-outlined header-icon">person</span>
                    <h5 class="mb-0 fs-15 fw-semibold text-white">Student Information</h5>
                </div>
            </div>

            <!-- Content -->
            <div class="card-body p-4" style="background: linear-gradient(to bottom, #ffffff 0%, #f8f9fa 100%);">
                <!-- ID Card Graphic -->
                <div class="text-center mb-4">
                    <div class="id-card-circle">
                        <div class="id-card-main">
                            <div class="id-card-content">
                                <div class="id-card-avatar"></div>
                                <div class="id-card-lines">
                                    <div class="id-line"></div>
                                    <div class="id-line" style="width: 80%;"></div>
                                    <div class="id-line" style="width: 60%;"></div>
                                </div>
                            </div>
                        </div>
                        <div class="id-card-shadow"></div>
                    </div>
                </div>

                <!-- Student Information Fields -->
                <div class="student-info-card">
                    <div class="info-row">
                        <span class="info-label">Student :</span>
                        <span class="info-value" id="studentName">-</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Roll :</span>
                        <span class="info-value" id="studentRoll">-</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Parent :</span>
                        <span class="info-value" id="studentParent">-</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Class/Section :</span>
                        <span class="info-value" id="studentClassSection">-</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Campus :</span>
                        <span class="info-value" id="studentCampus">-</span>
                    </div>
                    <div class="info-row dues-row">
                        <span class="info-label dues-label">Dues :</span>
                        <span class="info-value dues-value" id="studentDues">-</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bottom Section: Latest Entries -->
    <div class="col-12">
        <div class="card border-0 entries-card" style="border-radius: 12px; overflow: hidden; box-shadow: 0 2px 12px rgba(0,0,0,0.08);">
            <!-- Header -->
            <div class="entries-header p-3">
                <div class="d-flex align-items-center gap-2">
                    <span class="material-symbols-outlined header-icon">history</span>
                    <h5 class="mb-0 fs-15 fw-semibold text-white">Latest Entries</h5>
                    <span class="badge bg-white text-success ms-auto" id="entriesCount">0</span>
                </div>
            </div>

            <!-- Content -->
            <div class="card-body p-4" style="background: linear-gradient(to bottom, #ffffff 0%, #f8f9fa 100%); min-height: 200px;">
                <div id="latestEntries" class="entries-container">
                    <div class="empty-state">
                        <div class="empty-icon">
                            <span class="material-symbols-outlined">qr_code_scanner</span>
                        </div>
                        <p class="empty-text">No entries yet. Scan a card to see attendance records here.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    /* Card Styling */
    .barcode-card, .info-card, .entries-card {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .barcode-card:hover, .info-card:hover, .entries-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12) !important;
    }

    /* Header Styling */
    .barcode-header {
        background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        border-bottom: 2px solid rgba(255, 255, 255, 0.2);
    }

    .info-header, .entries-header {
        background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        border-bottom: 2px solid rgba(255, 255, 255, 0.2);
    }

    .header-icon {
        font-size: 20px;
        color: rgba(255, 255, 255, 0.9);
    }

    /* Live Indicator */
    .live-indicator {
        width: 10px;
        height: 10px;
        background-color: #dc3545;
        border-radius: 50%;
        display: inline-block;
        animation: pulse 2s infinite;
        box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.7);
    }

    @keyframes pulse {
        0% {
            box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.7);
        }
        70% {
            box-shadow: 0 0 0 10px rgba(220, 53, 69, 0);
        }
        100% {
            box-shadow: 0 0 0 0 rgba(220, 53, 69, 0);
        }
    }

    /* Close Button */
    .close-btn {
        background: rgba(255, 255, 255, 0.2);
        color: white;
        border: 1px solid rgba(255, 255, 255, 0.3);
        border-radius: 8px;
        font-size: 12px;
        font-weight: 500;
        transition: all 0.3s ease;
        backdrop-filter: blur(10px);
    }

    .close-btn:hover {
        background: rgba(255, 255, 255, 0.3);
        color: white;
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    }

    /* Barcode Input Group */
    .barcode-input-group {
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        transition: all 0.3s ease;
    }

    .barcode-input-group:focus-within {
        box-shadow: 0 4px 16px rgba(0, 52, 113, 0.2);
        transform: translateY(-1px);
    }

    .barcode-input-icon {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        border: none;
        border-right: 1px solid #dee2e6;
        padding: 0 16px;
        color: #6c757d;
    }

    .barcode-input-icon .material-symbols-outlined {
        font-size: 22px;
    }

    .barcode-input {
        border: none;
        border-left: 1px solid #dee2e6;
        border-right: 1px solid #dee2e6;
        font-size: 15px;
        padding: 0 16px;
        height: 52px;
        background-color: white;
        transition: all 0.3s ease;
    }

    .barcode-input:focus {
        border-color: #003471;
        box-shadow: none;
        outline: none;
        background-color: #f8f9fa;
    }

    .barcode-search-btn {
        background: linear-gradient(135deg, #ff9800 0%, #f57c00 100%);
        color: white;
        border: none;
        padding: 0 20px;
        transition: all 0.3s ease;
        border-radius: 0 12px 12px 0;
    }

    .barcode-search-btn:hover {
        background: linear-gradient(135deg, #f57c00 0%, #ff9800 100%);
        transform: scale(1.05);
        color: white;
    }

    .barcode-search-btn .material-symbols-outlined {
        font-size: 22px;
    }

    /* Scanner Circle */
    .scanner-circle {
        width: 220px;
        height: 220px;
        background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
        border-radius: 50%;
        margin: 0 auto;
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 8px 24px rgba(220, 53, 69, 0.3);
        animation: scannerGlow 3s ease-in-out infinite;
    }

    @keyframes scannerGlow {
        0%, 100% {
            box-shadow: 0 8px 24px rgba(220, 53, 69, 0.3);
        }
        50% {
            box-shadow: 0 8px 32px rgba(220, 53, 69, 0.5);
        }
    }

    .scanner-pulse {
        position: absolute;
        width: 100%;
        height: 100%;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(255, 255, 255, 0.3) 0%, transparent 70%);
        animation: pulseRing 2s ease-out infinite;
    }

    @keyframes pulseRing {
        0% {
            transform: scale(0.8);
            opacity: 1;
        }
        100% {
            transform: scale(1.2);
            opacity: 0;
        }
    }

    .scanner-content {
        position: relative;
        z-index: 1;
        color: white;
        text-align: center;
    }

    .scanner-icon {
        margin-bottom: 15px;
    }

    .scanner-icon .material-symbols-outlined {
        font-size: 64px;
        filter: drop-shadow(0 4px 8px rgba(0, 0, 0, 0.2));
    }

    .barcode-display {
        width: 110px;
        height: 65px;
        background: linear-gradient(to bottom, #ffffff 0%, #f8f9fa 100%);
        border-radius: 6px;
        margin: 0 auto;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        padding: 8px;
    }

    .barcode-lines {
        width: 90%;
        height: 85%;
        background: repeating-linear-gradient(
            90deg,
            #000 0px,
            #000 3px,
            transparent 3px,
            transparent 6px
        );
        border-radius: 3px;
    }

    /* Instruction Text */
    .instruction-text {
        font-size: 14px;
        font-weight: 500;
        color: #495057;
        padding: 12px 20px;
        background: linear-gradient(135deg, #e7f3ff 0%, #d0e7ff 100%);
        border-radius: 8px;
        border-left: 4px solid #003471;
        display: inline-block;
    }

    .instruction-text .material-symbols-outlined {
        color: #003471;
    }

    .scan-message {
        margin-top: 12px;
        padding: 8px 12px;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 600;
        display: inline-block;
    }

    .scan-message.success {
        background: #e6f4ea;
        color: #1e7e34;
        border: 1px solid #c8e6c9;
    }

    .scan-message.warning {
        background: #fff3cd;
        color: #856404;
        border: 1px solid #ffeeba;
    }

    .scan-message.error {
        background: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }

    /* ID Card Circle */
    .id-card-circle {
        width: 220px;
        height: 220px;
        background: linear-gradient(135deg, #ffc107 0%, #ffb300 100%);
        border-radius: 50%;
        margin: 0 auto;
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 8px 24px rgba(255, 193, 7, 0.3);
    }

    .id-card-main {
        width: 130px;
        height: 82px;
        background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
        border-radius: 8px;
        padding: 10px;
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.25);
        position: relative;
        z-index: 2;
        transform: rotate(-2deg);
    }

    .id-card-content {
        display: flex;
        gap: 8px;
        height: 100%;
    }

    .id-card-avatar {
        width: 35px;
        height: 45px;
        background: linear-gradient(135deg, #003471 0%, #004a9f 100%);
        border-radius: 5px;
        box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.2);
    }

    .id-card-lines {
        flex: 1;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        padding: 2px 0;
    }

    .id-line {
        height: 9px;
        background: rgba(255, 255, 255, 0.95);
        border-radius: 3px;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
    }

    .id-card-shadow {
        width: 130px;
        height: 82px;
        background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%);
        border-radius: 8px;
        position: absolute;
        top: 12px;
        left: 12px;
        z-index: 1;
        opacity: 0.5;
        transform: rotate(2deg);
    }

    /* Student Info Card */
    .student-info-card {
        background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
        padding: 20px;
        border-radius: 10px;
        border: 1px solid #e9ecef;
        box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.05);
    }

    .info-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px 0;
        border-bottom: 1px solid #f0f0f0;
        transition: all 0.2s ease;
    }

    .info-row:last-child {
        border-bottom: none;
    }

    .info-row:hover {
        background-color: rgba(0, 52, 113, 0.02);
        padding-left: 8px;
        border-radius: 6px;
    }

    .info-label {
        font-weight: 600;
        color: #212529;
        font-size: 13px;
        min-width: 120px;
    }

    .info-value {
        color: #6c757d;
        font-size: 13px;
        text-align: right;
        flex: 1;
    }

    .dues-row {
        background: linear-gradient(135deg, #fff5f5 0%, #ffe5e5 100%);
        padding: 12px 15px;
        border-radius: 8px;
        margin-top: 8px;
        border: 1px solid #ffcccc;
    }

    .dues-label, .dues-value {
        color: #dc3545 !important;
        font-weight: 600;
    }

    /* Latest Entries */
    .entries-container {
        min-height: 150px;
    }

    .empty-state {
        text-align: center;
        padding: 40px 20px;
    }

    .empty-icon {
        width: 80px;
        height: 80px;
        margin: 0 auto 20px;
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .empty-icon .material-symbols-outlined {
        font-size: 48px;
        color: #adb5bd;
    }

    .empty-text {
        color: #6c757d;
        font-size: 14px;
        margin: 0;
    }

    .entry-item {
        padding: 16px;
        border-bottom: 1px solid #e9ecef;
        transition: all 0.3s ease;
        background: white;
        border-radius: 8px;
        margin-bottom: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.04);
    }

    .entry-item:hover {
        background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
        transform: translateX(4px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        border-left: 3px solid #28a745;
    }

    .entry-item:last-child {
        margin-bottom: 0;
    }

    .badge-present {
        background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        color: white;
    }

    /* Entry Avatar */
    .entry-item > div:first-child > div:first-child {
        background: linear-gradient(135deg, #003471 0%, #004a9f 100%);
        box-shadow: 0 4px 8px rgba(0, 52, 113, 0.3);
    }

    /* Responsive */
    @media (max-width: 768px) {
        .scanner-circle, .id-card-circle {
            width: 180px;
            height: 180px;
        }

        .scanner-icon .material-symbols-outlined {
            font-size: 48px;
        }

        .barcode-display {
            width: 90px;
            height: 55px;
        }
    }
</style>

<script>
// Barcode scanning functionality
document.getElementById('barcodeInput').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        const barcode = this.value.trim();
        if (barcode) {
            processBarcode(barcode);
        }
    }
});

// Search button click
document.getElementById('searchBtn').addEventListener('click', function() {
    const barcode = document.getElementById('barcodeInput').value.trim();
    if (barcode) {
        processBarcode(barcode);
    }
});

function processBarcode(barcode) {
    const input = document.getElementById('barcodeInput');
    const searchBtn = document.getElementById('searchBtn');
    const scanMessage = document.getElementById('scanMessage');

    setScanMessage('Scanning...', 'warning');
    input.disabled = true;
    searchBtn.disabled = true;

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ||
        document.querySelector('input[name="_token"]')?.value;

    fetch('{{ route('attendance.barcode.scan') }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ barcode })
    })
    .then(response => response.json().then(data => ({ ok: response.ok, data })))
    .then(({ ok, data }) => {
        if (!ok || !data.success) {
            const message = data.message || 'Scan failed. Please try again.';
            setScanMessage(message, 'error');
            return;
        }

        const student = data.data.student;
        const attendance = data.data.attendance;

        updateStudentInfo(student);

        if (!attendance.already_marked) {
            const badgeInfo = getBadgeInfo(attendance);
            addToLatestEntries({
                name: student.name,
                roll: student.roll,
                classSection: student.class_section,
                time: attendance.time,
                statusLabel: badgeInfo.label,
                statusClass: badgeInfo.className
            });
        }

        const message = attendance.already_marked
            ? 'Good bye! Attendance already marked today.'
            : 'Attendance marked as Present.';
        setScanMessage(message, attendance.already_marked ? 'warning' : 'success');
    })
    .catch(() => {
        setScanMessage('Scan failed. Please try again.', 'error');
    })
    .finally(() => {
        input.value = '';
        input.disabled = false;
        searchBtn.disabled = false;
        input.focus();
    });
}

function updateStudentInfo(student) {
    document.getElementById('studentName').textContent = student.name || '-';
    document.getElementById('studentRoll').textContent = student.roll || '-';
    document.getElementById('studentParent').textContent = student.parent || '-';
    document.getElementById('studentClassSection').textContent = student.class_section || '-';
    document.getElementById('studentCampus').textContent = student.campus || '-';
    document.getElementById('studentDues').textContent = formatCurrency(student.dues);
}

function setScanMessage(text, type) {
    const scanMessage = document.getElementById('scanMessage');
    scanMessage.textContent = text;
    scanMessage.classList.remove('d-none', 'success', 'warning', 'error');
    scanMessage.classList.add(type);
}

function formatCurrency(amount) {
    if (amount === null || amount === undefined) {
        return '-';
    }
    const numeric = Number(amount);
    if (Number.isNaN(numeric)) {
        return amount;
    }
    return 'Rs. ' + numeric.toLocaleString();
}

function getBadgeInfo(attendance) {
    if (attendance.already_marked) {
        return { label: 'Present', className: 'badge-present' };
    }
    const status = (attendance.status || 'Present').toLowerCase();
    if (status === 'present') {
        return { label: 'Present', className: 'badge-present' };
    }
    return { label: attendance.status || 'Present', className: 'badge-present' };
}

function addToLatestEntries(entry) {
    const entriesContainer = document.getElementById('latestEntries');
    
    // Clear empty state if exists
    if (entriesContainer.querySelector('.empty-state')) {
        entriesContainer.innerHTML = '';
    }
    
    // Create entry element with animation
    const entryDiv = document.createElement('div');
    entryDiv.className = 'entry-item d-flex justify-content-between align-items-center';
    entryDiv.style.opacity = '0';
    entryDiv.style.transform = 'translateX(-20px)';
    entryDiv.innerHTML = `
        <div class="d-flex align-items-center gap-3">
            <div style="width: 48px; height: 48px; background: linear-gradient(135deg, #003471 0%, #004a9f 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 18px; box-shadow: 0 4px 8px rgba(0, 52, 113, 0.3);">
                ${entry.name.charAt(0).toUpperCase()}
            </div>
            <div>
                <div class="fw-semibold" style="color: #212529; font-size: 15px; margin-bottom: 4px;">${entry.name}</div>
                <div style="color: #6c757d; font-size: 12px;">Roll: ${entry.roll} | ${entry.classSection}</div>
            </div>
        </div>
        <div class="text-end">
            <span class="badge ${entry.statusClass}" style="padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: 600; margin-bottom: 4px; display: inline-block;">
                ${entry.statusLabel}
            </span>
            <div style="color: #6c757d; font-size: 12px; font-weight: 500;">${entry.time}</div>
        </div>
    `;
    
    // Insert at the top
    entriesContainer.insertBefore(entryDiv, entriesContainer.firstChild);
    
    // Animate in
    setTimeout(() => {
        entryDiv.style.transition = 'all 0.4s ease';
        entryDiv.style.opacity = '1';
        entryDiv.style.transform = 'translateX(0)';
    }, 10);
    
    // Update entries count
    const entriesCount = entriesContainer.querySelectorAll('.entry-item').length;
    document.getElementById('entriesCount').textContent = entriesCount;
    
    // Limit to 10 entries
    while (entriesContainer.querySelectorAll('.entry-item').length > 10) {
        const lastEntry = entriesContainer.querySelector('.entry-item:last-child');
        lastEntry.style.transition = 'all 0.3s ease';
        lastEntry.style.opacity = '0';
        lastEntry.style.transform = 'translateX(20px)';
        setTimeout(() => {
            lastEntry.remove();
            document.getElementById('entriesCount').textContent = entriesContainer.querySelectorAll('.entry-item').length;
        }, 300);
    }
}

// Auto-focus on input when page loads
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('barcodeInput').focus();
});
</script>
@endsection
