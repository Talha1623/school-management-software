@extends('layouts.app')

@section('title', 'Fee Calculator')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-3">
            <h3 class="mb-3 fw-semibold" style="color: #003471;">Fee Calculator</h3>
            
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

            <!-- Search Section -->
            <div class="card bg-light border-0 rounded-10 p-4 mb-4" style="text-align: center;">
                <h4 class="mb-3 fw-semibold" style="color: #003471;">Search Unpaid Invoices Via Father Id Card</h4>
                
                <form id="fatherIdCardForm" class="mb-4">
                    <div class="row justify-content-center">
                        <div class="col-md-8">
                            <div class="input-group input-group-lg">
                                <input type="text" 
                                       class="form-control form-control-lg" 
                                       id="father_id_card" 
                                       name="father_id_card" 
                                       placeholder="Type Father ID Card Number..." 
                                       style="height: 50px; font-size: 16px;">
                                <button type="submit" 
                                        class="btn btn-primary" 
                                        id="calculateBtn" 
                                        style="background-color: #003471; border-color: #003471; color: white; height: 50px; padding: 0 30px;">
                                    <span class="material-symbols-outlined me-2" style="font-size: 20px; vertical-align: middle; color: white;">search</span>
                                    <span style="color: white;">Calculate</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </form>

                <!-- ID Card Graphic -->
                <div class="mb-4" style="display: flex; justify-content: center; align-items: center;">
                    <div style="position: relative; width: 300px; height: 300px;">
                        <!-- Yellow Circle Background -->
                        <div style="position: absolute; width: 100%; height: 100%; background: #FFD700; border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 15px rgba(0,0,0,0.2);">
                            <!-- White Cards -->
                            <div style="position: absolute; width: 80px; height: 100px; background: white; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.15); left: 20%; top: 20%; transform: rotate(-15deg);"></div>
                            <div style="position: absolute; width: 80px; height: 100px; background: white; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.15); right: 20%; top: 20%; transform: rotate(15deg);"></div>
                            
                            <!-- Red ID Card -->
                            <div style="position: absolute; width: 120px; height: 150px; background: #DC143C; border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.3); display: flex; align-items: center; justify-content: space-between; padding: 15px;">
                                <!-- Person Icon -->
                                <div style="width: 40px; height: 40px; background: #1a1a2e; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                    <div style="width: 30px; height: 30px; background: #1a1a2e; border-radius: 50%; border: 2px solid white;"></div>
                                </div>
                                <!-- Lines -->
                                <div style="flex: 1; margin-left: 10px;">
                                    <div style="width: 100%; height: 3px; background: white; margin-bottom: 5px; border-radius: 2px;"></div>
                                    <div style="width: 80%; height: 3px; background: white; margin-bottom: 5px; border-radius: 2px;"></div>
                                    <div style="width: 90%; height: 3px; background: white; border-radius: 2px;"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <p class="text-muted mb-0" style="font-size: 14px;">Scan Father ID Card For Quick Calculations...!</p>
            </div>

            <!-- Loading State -->
            <div id="loadingState" class="text-center" style="display: none;">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2 text-muted">Searching for father and students...</p>
            </div>

            <!-- Error Message -->
            <div id="errorMessage" class="alert alert-danger" style="display: none;">
                <span id="errorText"></span>
            </div>

            <!-- Results Section -->
            <div id="resultsSection" style="display: none;">
                <!-- Father Information -->
                <div class="card bg-light border-0 rounded-10 p-3 mb-3">
                    <h5 class="mb-3 fw-semibold" style="color: #003471;">Father Information</h5>
                    <div class="row">
                        <div class="col-md-6">
                            <p class="mb-2"><strong>Name:</strong> <span id="fatherName"></span></p>
                            <p class="mb-2"><strong>ID Card:</strong> <span id="fatherIdCard"></span></p>
                            <p class="mb-2"><strong>Phone:</strong> <span id="fatherPhone"></span></p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-2"><strong>Email:</strong> <span id="fatherEmail"></span></p>
                            <p class="mb-2"><strong>Address:</strong> <span id="fatherAddress"></span></p>
                        </div>
                    </div>
                </div>

                <!-- Students List -->
                <div class="card bg-light border-0 rounded-10 p-3 mb-3">
                    <h5 class="mb-3 fw-semibold" style="color: #003471;">Children Information</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm">
                            <thead style="background-color: #003471; color: white;">
                                <tr>
                                    <th>#</th>
                                    <th>Student Name</th>
                                    <th>Student Code</th>
                                    <th>Class</th>
                                    <th>Section</th>
                                    <th>Campus</th>
                                    <th>Monthly Fee</th>
                                </tr>
                            </thead>
                            <tbody id="studentsTableBody">
                                <!-- Students will be populated here -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .input-group-text {
        border-right: none;
    }
    
    .form-control:focus {
        border-color: #003471;
        box-shadow: 0 0 0 0.2rem rgba(0, 52, 113, 0.25);
    }
    
    table thead th {
        font-size: 13px;
        font-weight: 600;
        padding: 12px 15px;
    }
    
    table tbody td {
        font-size: 13px;
        padding: 12px 15px;
        vertical-align: middle;
    }
</style>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('fatherIdCardForm');
    const fatherIdCardInput = document.getElementById('father_id_card');
    const loadingState = document.getElementById('loadingState');
    const errorMessage = document.getElementById('errorMessage');
    const errorText = document.getElementById('errorText');
    const resultsSection = document.getElementById('resultsSection');
    const studentsTableBody = document.getElementById('studentsTableBody');

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const fatherIdCard = fatherIdCardInput.value.trim();
        
        if (!fatherIdCard) {
            errorText.textContent = 'Please enter Father ID Card Number';
            errorMessage.style.display = 'block';
            resultsSection.style.display = 'none';
            return;
        }

        // Show loading state
        loadingState.style.display = 'block';
        errorMessage.style.display = 'none';
        resultsSection.style.display = 'none';

        // Make AJAX call to search by Father ID Card
        fetch(`{{ route('accounting.family-fee-calculator.search-by-id-card') }}?father_id_card=${encodeURIComponent(fatherIdCard)}`, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            loadingState.style.display = 'none';
            
            if (data.success) {
                if (data.found) {
                    // Display father information
                    document.getElementById('fatherName').textContent = data.father.name || 'N/A';
                    document.getElementById('fatherIdCard').textContent = data.father.id_card_number || 'N/A';
                    document.getElementById('fatherPhone').textContent = data.father.phone || 'N/A';
                    document.getElementById('fatherEmail').textContent = data.father.email || 'N/A';
                    document.getElementById('fatherAddress').textContent = data.father.address || 'N/A';

                    // Display students
                    if (data.students && data.students.length > 0) {
                        studentsTableBody.innerHTML = '';
                        data.students.forEach((student, index) => {
                            const row = document.createElement('tr');
                            row.innerHTML = `
                                <td>${index + 1}</td>
                                <td>${student.student_name || 'N/A'}</td>
                                <td>${student.student_code || 'N/A'}</td>
                                <td>${student.class || 'N/A'}</td>
                                <td>${student.section || 'N/A'}</td>
                                <td>${student.campus || 'N/A'}</td>
                                <td>${student.monthly_fee ? parseFloat(student.monthly_fee).toFixed(2) : '0.00'}</td>
                            `;
                            studentsTableBody.appendChild(row);
                        });
                    } else {
                        studentsTableBody.innerHTML = '<tr><td colspan="7" class="text-center text-muted py-3">No students found for this father.</td></tr>';
                    }

                    resultsSection.style.display = 'block';
                } else {
                    errorText.textContent = data.message || 'No father found with this ID Card Number';
                    errorMessage.style.display = 'block';
                }
            } else {
                errorText.textContent = data.message || 'An error occurred. Please try again.';
                errorMessage.style.display = 'block';
            }
        })
        .catch(error => {
            loadingState.style.display = 'none';
            console.error('Error:', error);
            errorText.textContent = 'An error occurred while searching. Please try again.';
            errorMessage.style.display = 'block';
        });
    });
});
</script>
@endpush
@endsection
