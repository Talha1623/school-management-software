@extends('layouts.app')

@section('title', 'Family Fee Calculator')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-3">
            <h3 class="mb-3 fw-semibold" style="color: #003471;">Family Fee Calculator</h3>
            
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
            
            @if($errors->any())
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div class="card bg-light border-0 rounded-10 p-3 mb-3">
                <form id="familyFeeCalculatorForm">
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="campus" class="form-label mb-1 fs-13 fw-medium">Campus <span class="text-danger">*</span></label>
                            <select class="form-select form-select-sm py-2" id="campus" name="campus" required style="height: 38px;">
                                <option value="">Select Campus</option>
                                @php
                                    $campuses = \App\Models\ClassModel::whereNotNull('campus')->distinct()->pluck('campus');
                                    $campusesFromSections = \App\Models\Section::whereNotNull('campus')->distinct()->pluck('campus');
                                    $allCampuses = $campuses->merge($campusesFromSections)->unique()->sort()->values();
                                    if ($allCampuses->isEmpty()) {
                                        $allCampuses = collect(['Main Campus', 'Branch Campus 1', 'Branch Campus 2']);
                                    }
                                @endphp
                                @foreach($allCampuses as $campus)
                                    <option value="{{ $campus }}">{{ $campus }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="family_id" class="form-label mb-1 fs-13 fw-medium">Family ID / Parent Name</label>
                            <input type="text" class="form-control form-control-sm py-2" id="family_id" name="family_id" placeholder="Enter Family ID or Parent Name" style="height: 38px;">
                        </div>
                        <div class="col-md-4">
                            <label for="search_students" class="form-label mb-1 fs-13 fw-medium">Search Students</label>
                            <button type="button" class="btn btn-sm w-100 py-2" id="searchStudentsBtn" style="background-color: #003471; color: white; height: 38px;">
                                <i class="fas fa-search me-1"></i> Search Students
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Students List -->
            <div id="studentsList" class="card bg-light border-0 rounded-10 p-3 mb-3" style="display: none;">
                <h5 class="mb-3 fw-semibold" style="color: #003471;">Family Students</h5>
                <div class="table-responsive">
                    <table class="table table-bordered table-sm">
                        <thead style="background-color: #003471; color: white;">
                            <tr>
                                <th>Select</th>
                                <th>Student Name</th>
                                <th>Class</th>
                                <th>Section</th>
                                <th>Admission No</th>
                            </tr>
                        </thead>
                        <tbody id="studentsTableBody">
                            <!-- Students will be populated here -->
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Fee Calculation Section -->
            <div id="feeCalculationSection" class="card bg-light border-0 rounded-10 p-3 mb-3" style="display: none;">
                <h5 class="mb-3 fw-semibold" style="color: #003471;">Fee Calculation</h5>
                
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="fee_month" class="form-label mb-1 fs-13 fw-medium">Fee Month</label>
                        <select class="form-select form-select-sm py-2" id="fee_month" name="fee_month" style="height: 38px;">
                            <option value="">Select Month</option>
                            <option value="January">January</option>
                            <option value="February">February</option>
                            <option value="March">March</option>
                            <option value="April">April</option>
                            <option value="May">May</option>
                            <option value="June">June</option>
                            <option value="July">July</option>
                            <option value="August">August</option>
                            <option value="September">September</option>
                            <option value="October">October</option>
                            <option value="November">November</option>
                            <option value="December">December</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="fee_year" class="form-label mb-1 fs-13 fw-medium">Fee Year</label>
                        <select class="form-select form-select-sm py-2" id="fee_year" name="fee_year" style="height: 38px;">
                            <option value="">Select Year</option>
                            @for($year = date('Y'); $year >= date('Y') - 5; $year--)
                                <option value="{{ $year }}" {{ $year == date('Y') ? 'selected' : '' }}>{{ $year }}</option>
                            @endfor
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="discount_percentage" class="form-label mb-1 fs-13 fw-medium">Discount (%)</label>
                        <input type="number" class="form-control form-control-sm py-2" id="discount_percentage" name="discount_percentage" placeholder="Enter discount %" min="0" max="100" value="0" style="height: 38px;">
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-sm">
                        <thead style="background-color: #003471; color: white;">
                            <tr>
                                <th>Student Name</th>
                                <th>Class</th>
                                <th>Section</th>
                                <th>Monthly Fee</th>
                                <th>Transport Fee</th>
                                <th>Custom Fee</th>
                                <th>Total Fee</th>
                                <th>Discount</th>
                                <th>Final Amount</th>
                            </tr>
                        </thead>
                        <tbody id="feeCalculationTableBody">
                            <!-- Fee calculation will be populated here -->
                        </tbody>
                        <tfoot>
                            <tr class="fw-bold" style="background-color: #f0f0f0;">
                                <td colspan="8" class="text-end">Grand Total:</td>
                                <td id="grandTotal">0.00</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="mt-3 text-end">
                    <button type="button" class="btn btn-sm px-4 py-2" id="calculateBtn" style="background-color: #003471; color: white;">
                        <i class="fas fa-calculator me-1"></i> Calculate Fee
                    </button>
                    <button type="button" class="btn btn-sm px-4 py-2 btn-success ms-2" id="printBtn" style="display: none;">
                        <i class="fas fa-print me-1"></i> Print
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchBtn = document.getElementById('searchStudentsBtn');
    const studentsList = document.getElementById('studentsList');
    const feeCalculationSection = document.getElementById('feeCalculationSection');
    const calculateBtn = document.getElementById('calculateBtn');
    const printBtn = document.getElementById('printBtn');
    
    let selectedStudents = [];

    // Search Students
    searchBtn.addEventListener('click', function() {
        const campus = document.getElementById('campus').value;
        const familyId = document.getElementById('family_id').value;

        if (!campus) {
            alert('Please select a campus');
            return;
        }

        // Simulate student search (Replace with actual API call)
        const mockStudents = [
            { id: 1, name: 'Ahmed Ali', class: 'Class 1', section: 'A', admission_no: 'ADM001' },
            { id: 2, name: 'Fatima Ali', class: 'Class 2', section: 'B', admission_no: 'ADM002' },
            { id: 3, name: 'Hassan Ali', class: 'Class 3', section: 'A', admission_no: 'ADM003' }
        ];

        displayStudents(mockStudents);
        studentsList.style.display = 'block';
    });

    function displayStudents(students) {
        const tbody = document.getElementById('studentsTableBody');
        tbody.innerHTML = '';

        students.forEach(student => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>
                    <input type="checkbox" class="form-check-input student-checkbox" value="${student.id}" data-name="${student.name}" data-class="${student.class}" data-section="${student.section}">
                </td>
                <td>${student.name}</td>
                <td>${student.class}</td>
                <td>${student.section}</td>
                <td>${student.admission_no}</td>
            `;
            tbody.appendChild(row);
        });

        // Add event listeners to checkboxes
        document.querySelectorAll('.student-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                updateSelectedStudents();
            });
        });
    }

    function updateSelectedStudents() {
        selectedStudents = [];
        document.querySelectorAll('.student-checkbox:checked').forEach(checkbox => {
            selectedStudents.push({
                id: checkbox.value,
                name: checkbox.dataset.name,
                class: checkbox.dataset.class,
                section: checkbox.dataset.section
            });
        });

        if (selectedStudents.length > 0) {
            feeCalculationSection.style.display = 'block';
            populateFeeCalculation();
        } else {
            feeCalculationSection.style.display = 'none';
        }
    }

    function populateFeeCalculation() {
        const tbody = document.getElementById('feeCalculationTableBody');
        tbody.innerHTML = '';

        selectedStudents.forEach(student => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${student.name}</td>
                <td>${student.class}</td>
                <td>${student.section}</td>
                <td><input type="number" class="form-control form-control-sm monthly-fee" value="0" min="0" step="0.01"></td>
                <td><input type="number" class="form-control form-control-sm transport-fee" value="0" min="0" step="0.01"></td>
                <td><input type="number" class="form-control form-control-sm custom-fee" value="0" min="0" step="0.01"></td>
                <td class="total-fee">0.00</td>
                <td class="discount-amount">0.00</td>
                <td class="final-amount">0.00</td>
            `;
            tbody.appendChild(row);
        });

        // Add event listeners to fee inputs
        document.querySelectorAll('.monthly-fee, .transport-fee, .custom-fee').forEach(input => {
            input.addEventListener('input', calculateFees);
        });
    }

    calculateBtn.addEventListener('click', function() {
        calculateFees();
        printBtn.style.display = 'inline-block';
    });

    function calculateFees() {
        const discountPercentage = parseFloat(document.getElementById('discount_percentage').value) || 0;
        let grandTotal = 0;

        document.querySelectorAll('#feeCalculationTableBody tr').forEach(row => {
            const monthlyFee = parseFloat(row.querySelector('.monthly-fee').value) || 0;
            const transportFee = parseFloat(row.querySelector('.transport-fee').value) || 0;
            const customFee = parseFloat(row.querySelector('.custom-fee').value) || 0;
            
            const totalFee = monthlyFee + transportFee + customFee;
            const discountAmount = (totalFee * discountPercentage) / 100;
            const finalAmount = totalFee - discountAmount;

            row.querySelector('.total-fee').textContent = totalFee.toFixed(2);
            row.querySelector('.discount-amount').textContent = discountAmount.toFixed(2);
            row.querySelector('.final-amount').textContent = finalAmount.toFixed(2);

            grandTotal += finalAmount;
        });

        document.getElementById('grandTotal').textContent = grandTotal.toFixed(2);
    }

    printBtn.addEventListener('click', function() {
        window.print();
    });
});
</script>
@endpush
@endsection

