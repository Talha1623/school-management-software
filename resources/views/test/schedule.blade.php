@extends('layouts.app')

@section('title', 'Test Schedule')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-3 mb-4">
            <h4 class="mb-3 fs-16 fw-semibold" style="color: #003471;">Test Schedule</h4>

            <!-- Filter Form -->
            <form id="filterForm" class="mb-3">
                <div class="row g-2 align-items-end">
                    <!-- Class -->
                    <div class="col-md-2">
                        <label for="filter_class" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Class</label>
                        <select class="form-select form-select-sm" id="filter_class" name="filter_class" style="height: 36px; border-radius: 6px; border: 1px solid #dee2e6; font-size: 13px;">
                            <option value="">All Classes</option>
                            @foreach($classes as $className)
                                <option value="{{ $className }}">{{ $className }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Section -->
                    <div class="col-md-2">
                        <label for="filter_section" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Section</label>
                        <select class="form-select form-select-sm" id="filter_section" name="filter_section" style="height: 36px; border-radius: 6px; border: 1px solid #dee2e6; font-size: 13px;">
                            <option value="">All Sections</option>
                        </select>
                    </div>

                    <!-- Test Type -->
                    <div class="col-md-2">
                        <label for="filter_test_type" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Test Type</label>
                        <select class="form-select form-select-sm" id="filter_test_type" name="filter_test_type" style="height: 36px; border-radius: 6px; border: 1px solid #dee2e6; font-size: 13px;">
                            <option value="">All Test Types</option>
                            @foreach($testTypes as $type)
                                <option value="{{ $type }}">{{ $type }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- From Date -->
                    <div class="col-md-2">
                        <label for="filter_from_date" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">From Date</label>
                        <input type="date" class="form-control form-control-sm" id="filter_from_date" name="filter_from_date" style="height: 36px; border-radius: 6px; border: 1px solid #dee2e6; font-size: 13px;">
                    </div>

                    <!-- To Date -->
                    <div class="col-md-2">
                        <label for="filter_to_date" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">To Date</label>
                        <input type="date" class="form-control form-control-sm" id="filter_to_date" name="filter_to_date" style="height: 36px; border-radius: 6px; border: 1px solid #dee2e6; font-size: 13px;">
                    </div>

                    <!-- Filter Button -->
                    <div class="col-md-2">
                        <button type="button" id="filterBtn" class="btn btn-sm filter-btn w-100" style="height: 36px; border-radius: 6px;">
                            <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">filter_alt</span>
                            <span style="font-size: 13px; vertical-align: middle; margin-left: 5px;">Filter</span>
                        </button>
                    </div>
                </div>
            </form>

            <!-- Loading Indicator -->
            <div id="loadingIndicator" class="text-center py-5" style="display: none;">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2 mb-0" style="color: #666; font-size: 13px;">Loading tests...</p>
            </div>

            <!-- Results Table -->
            <div id="resultsContainer">
                <!-- Print Header (only visible when printing) -->
                <div class="print-header" style="display: none;">
                    <h2 style="text-align: center; color: #003471; margin-bottom: 10px;">Test Schedule</h2>
                    <p id="printDateRange" style="text-align: center; color: #666; margin-bottom: 20px;"></p>
                </div>

                <div class="mb-2 p-2 rounded-8" style="background: linear-gradient(135deg, #003471 0%, #004a9f 100%);">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 text-white fs-15 fw-semibold d-flex align-items-center gap-2">
                            <span class="material-symbols-outlined" style="font-size: 18px;">list</span>
                            <span>Test Schedule</span>
                            <span id="totalCount" class="ms-3 badge bg-light text-dark">{{ $tests->count() }}</span>
                        </h5>
                        <button type="button" id="printBtn" class="btn btn-sm btn-light print-btn-header" onclick="printSchedule()" style="display: {{ $tests->count() > 0 ? 'inline-flex' : 'none' }};">
                            <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">print</span>
                            <span style="font-size: 13px; vertical-align: middle; margin-left: 5px;">Print</span>
                        </button>
                    </div>
                </div>

                <div class="default-table-area" style="margin-top: 0;">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Day</th>
                                    <th>Type</th>
                                    <th>Class</th>
                                    <th>Section</th>
                                    <th>Subject</th>
                                </tr>
                            </thead>
                            <tbody id="testsTableBody">
                                @forelse($tests as $index => $test)
                                <tr>
                                    <td>
                                        <span class="fw-semibold">{{ $test->date ? date('d M Y', strtotime($test->date)) : 'N/A' }}</span>
                                    </td>
                                    <td>{{ $test->date ? date('l', strtotime($test->date)) : 'N/A' }}</td>
                                    <td>
                                        <span class="badge bg-warning text-dark">{{ $test->test_type }}</span>
                                    </td>
                                    <td>{{ $test->for_class }}</td>
                                    <td>
                                        <span class="badge bg-secondary text-white">{{ $test->section }}</span>
                                    </td>
                                    <td>{{ $test->subject }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4">
                                        <div class="d-flex flex-column align-items-center">
                                            <span class="material-symbols-outlined text-muted" style="font-size: 48px;">inbox</span>
                                            <p class="text-muted mt-2 mb-0">No tests found</p>
                                        </div>
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
</div>

<style>
.filter-btn {
    background: linear-gradient(135deg, #003471 0%, #004a9f 100%);
    color: white;
    border: none;
    font-weight: 500;
    transition: all 0.3s ease;
    box-shadow: 0 2px 6px rgba(0, 52, 113, 0.2);
}

.filter-btn:hover {
    background: linear-gradient(135deg, #004a9f 0%, #003471 100%);
    transform: translateY(-1px);
    box-shadow: 0 4px 10px rgba(0, 52, 113, 0.3);
    color: white;
}

.filter-btn:active {
    transform: translateY(0);
}

#filter_class:focus,
#filter_section:focus,
#filter_test_type:focus,
#filter_from_date:focus,
#filter_to_date:focus {
    border-color: #003471;
    box-shadow: 0 0 0 3px rgba(0, 52, 113, 0.15);
    outline: none;
}

.default-table-area {
    background: #fff;
    border-radius: 8px;
    overflow: hidden;
}

.default-table-area table {
    margin-bottom: 0;
}

.default-table-area thead {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
}

.default-table-area thead th {
    font-weight: 600;
    font-size: 13px;
    color: #003471;
    border-bottom: 2px solid #dee2e6;
    padding: 12px;
}

.default-table-area tbody td {
    font-size: 13px;
    padding: 12px;
    vertical-align: middle;
}

.default-table-area tbody tr:hover {
    background-color: #f8f9fa;
}

.badge {
    font-size: 11px;
    padding: 4px 8px;
}

.print-btn-header {
    background: white;
    color: #003471;
    border: none;
    font-weight: 500;
    transition: all 0.3s ease;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.print-btn-header:hover {
    background: #f8f9fa;
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
    color: #003471;
}

@media print {
    /* Hide all non-essential elements */
    .sidebar-area,
    #sidebar-area,
    .sidebar,
    .navbar,
    .navbar-area,
    .filter-btn,
    .print-btn-header,
    #filterForm,
    .card-header,
    .no-print,
    #loadingIndicator,
    .mb-2.p-2.rounded-8,
    header,
    footer,
    .main-header,
    .header-navbar {
        display: none !important;
    }
    
    /* Show only the table and print header */
    .print-header {
        display: block !important;
        margin-bottom: 20px;
        page-break-after: avoid;
    }
    
    /* Reset body and container styles */
    body {
        background: white !important;
        padding: 20px !important;
        margin: 0 !important;
    }
    
    .main-content {
        margin: 0 !important;
        padding: 0 !important;
        width: 100% !important;
    }
    
    .row {
        margin: 0 !important;
    }
    
    .col-12 {
        padding: 0 !important;
    }
    
    .card {
        border: none !important;
        box-shadow: none !important;
        padding: 0 !important;
        margin: 0 !important;
        background: white !important;
    }
    
    /* Hide card title */
    h4.mb-3 {
        display: none !important;
    }
    
    /* Table styles */
    .default-table-area {
        border: none;
        margin: 0;
    }
    
    .default-table-area table {
        width: 100%;
        border-collapse: collapse;
        page-break-inside: auto;
        margin: 0;
    }
    
    .default-table-area thead {
        display: table-header-group;
    }
    
    .default-table-area tbody {
        display: table-row-group;
    }
    
    .default-table-area tr {
        page-break-inside: avoid;
        page-break-after: auto;
    }
    
    .default-table-area th,
    .default-table-area td {
        border: 1px solid #ddd;
        padding: 8px;
        text-align: left;
    }
    
    .default-table-area th {
        background-color: #003471 !important;
        color: white !important;
        font-weight: bold;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    
    /* Ensure badges print properly */
    .badge {
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    
    .default-table-area thead {
        display: table-header-group;
    }
    
    .default-table-area tfoot {
        display: table-footer-group;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const classSelect = document.getElementById('filter_class');
    const sectionSelect = document.getElementById('filter_section');
    const filterBtn = document.getElementById('filterBtn');
    const loadingIndicator = document.getElementById('loadingIndicator');
    const resultsContainer = document.getElementById('resultsContainer');
    const testsTableBody = document.getElementById('testsTableBody');
    const totalCount = document.getElementById('totalCount');

    // Load sections when class changes
    if (classSelect) {
        classSelect.addEventListener('change', function() {
            loadSections(this.value);
        });
    }

    // Handle filter button click
    if (filterBtn) {
        filterBtn.addEventListener('click', function() {
            applyFilters();
        });
    }

    // Handle Enter key in date inputs
    document.getElementById('filter_from_date')?.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            applyFilters();
        }
    });

    document.getElementById('filter_to_date')?.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            applyFilters();
        }
    });

    function loadSections(selectedClass) {
        if (!selectedClass) {
            sectionSelect.innerHTML = '<option value="">All Sections</option>';
            sectionSelect.disabled = false;
            return;
        }
        
        sectionSelect.innerHTML = '<option value="">Loading...</option>';
        sectionSelect.disabled = true;
        
        fetch(`{{ route('test.schedule.get-sections') }}?class=${encodeURIComponent(selectedClass)}`)
            .then(response => response.json())
            .then(data => {
                sectionSelect.innerHTML = '<option value="">All Sections</option>';
                if (data.sections && data.sections.length > 0) {
                    data.sections.forEach(section => {
                        const option = document.createElement('option');
                        option.value = section;
                        option.textContent = section;
                        sectionSelect.appendChild(option);
                    });
                }
                sectionSelect.disabled = false;
            })
            .catch(error => {
                console.error('Error loading sections:', error);
                sectionSelect.innerHTML = '<option value="">Error loading sections</option>';
                sectionSelect.disabled = false;
            });
    }

    function applyFilters() {
        const formData = new FormData(document.getElementById('filterForm'));
        const params = new URLSearchParams();
        
        for (const [key, value] of formData.entries()) {
            if (value) {
                params.append(key, value);
            }
        }

        // Show loading, hide results
        loadingIndicator.style.display = 'block';
        resultsContainer.style.display = 'none';
        filterBtn.disabled = true;

        fetch(`{{ route('test.schedule.get-filtered-tests') }}?${params.toString()}`, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            loadingIndicator.style.display = 'none';
            resultsContainer.style.display = 'block';
            filterBtn.disabled = false;

            if (data.success) {
                displayTests(data.tests, data.total);
            } else {
                alert('Error: ' + (data.message || 'Failed to fetch tests'));
                testsTableBody.innerHTML = '<tr><td colspan="6" class="text-center py-4"><p class="text-muted">Error loading tests</p></td></tr>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            loadingIndicator.style.display = 'none';
            resultsContainer.style.display = 'block';
            filterBtn.disabled = false;
            alert('Error fetching tests. Please try again.');
            testsTableBody.innerHTML = '<tr><td colspan="6" class="text-center py-4"><p class="text-muted">Error loading tests</p></td></tr>';
        });
    }

    function displayTests(tests, total) {
        totalCount.textContent = total;
        
        // Show print button when results are displayed
        const printBtn = document.getElementById('printBtn');
        if (printBtn) {
            printBtn.style.display = 'inline-flex';
        }
        
        if (tests.length === 0) {
            testsTableBody.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center py-4">
                        <div class="d-flex flex-column align-items-center">
                            <span class="material-symbols-outlined text-muted" style="font-size: 48px;">inbox</span>
                            <p class="text-muted mt-2 mb-0">No tests found</p>
                        </div>
                    </td>
                </tr>
            `;
            // Hide print button if no results
            if (printBtn) {
                printBtn.style.display = 'none';
            }
            return;
        }

        // Helper function to get day name from date
        function getDayName(dateString) {
            if (!dateString || dateString === 'N/A') return 'N/A';
            try {
                // Try parsing the date string (could be 'd M Y' format or 'Y-m-d' format)
                let date;
                if (dateString.includes('-')) {
                    // Format: Y-m-d
                    date = new Date(dateString);
                } else {
                    // Format: d M Y (e.g., "23 Jan 2026")
                    const parts = dateString.split(' ');
                    if (parts.length === 3) {
                        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                        const monthIndex = months.indexOf(parts[1]);
                        if (monthIndex !== -1) {
                            date = new Date(parseInt(parts[2]), monthIndex, parseInt(parts[0]));
                        } else {
                            date = new Date(dateString);
                        }
                    } else {
                        date = new Date(dateString);
                    }
                }
                
                if (isNaN(date.getTime())) return 'N/A';
                
                const days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                return days[date.getDay()];
            } catch (e) {
                return 'N/A';
            }
        }

        let html = '';
        tests.forEach((test, index) => {
            const dayName = getDayName(test.date_raw || test.date);
            html += `
                <tr>
                    <td>
                        <span class="fw-semibold">${test.date || 'N/A'}</span>
                    </td>
                    <td>${dayName}</td>
                    <td>
                        <span class="badge bg-warning text-dark">${test.test_type || 'N/A'}</span>
                    </td>
                    <td>${test.for_class || 'N/A'}</td>
                    <td>
                        <span class="badge bg-secondary text-white">${test.section || 'N/A'}</span>
                    </td>
                    <td>${test.subject || 'N/A'}</td>
                </tr>
            `;
        });

        testsTableBody.innerHTML = html;
    }
    
});
</script>

<script>
    // Print function - defined globally so onclick can access it
    function printSchedule() {
        // Get current filter values for print header
        const filterClass = document.getElementById('filter_class')?.value || '';
        const filterSection = document.getElementById('filter_section')?.value || '';
        const filterTestType = document.getElementById('filter_test_type')?.value || '';
        const filterFromDate = document.getElementById('filter_from_date')?.value || '';
        const filterToDate = document.getElementById('filter_to_date')?.value || '';
        
        // Build filter text for print header
        let filterText = '';
        const filters = [];
        
        if (filterClass) filters.push(`Class: ${filterClass}`);
        if (filterSection) filters.push(`Section: ${filterSection}`);
        if (filterTestType) filters.push(`Type: ${filterTestType}`);
        if (filterFromDate) filters.push(`From: ${filterFromDate}`);
        if (filterToDate) filters.push(`To: ${filterToDate}`);
        
        if (filters.length > 0) {
            filterText = filters.join(' | ');
        } else {
            filterText = 'All Tests';
        }
        
        // Update print header
        const printDateRange = document.getElementById('printDateRange');
        if (printDateRange) {
            printDateRange.textContent = filterText;
        }
        
        // Trigger print
        window.print();
    }
</script>
@endsection
