@extends('layouts.app')

@section('title', 'Add Bulk Products')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-3 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0 fs-16 fw-semibold">Add Bulk Products</h4>
            </div>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <pre style="white-space: pre-wrap; margin: 0;">{{ session('success') }}</pre>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if(session('errors') && is_array(session('errors')) && count(session('errors')) > 0)
                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                    <strong>Upload Errors:</strong>
                    <ul class="mb-0 mt-2" style="max-height: 200px; overflow-y: auto;">
                        @foreach(array_slice(session('errors'), 0, 20) as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                        @if(count(session('errors')) > 20)
                            <li><em>... and {{ count(session('errors')) - 20 }} more errors</em></li>
                        @endif
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <!-- Instructions Card -->
            <div class="card border-0 shadow-sm mb-4" style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);">
                <div class="card-body p-3">
                    <h5 class="fs-14 fw-semibold mb-3 d-flex align-items-center gap-2" style="color: #003471;">
                        <span class="material-symbols-outlined" style="font-size: 18px;">info</span>
                        <span>Instructions</span>
                    </h5>
                    <div class="row">
                        <div class="col-md-6">
                            <p class="mb-2 fs-13"><strong>CSV File Format:</strong></p>
                            <ul class="mb-0 fs-13" style="padding-left: 20px;">
                                <li>File must be in CSV format (.csv)</li>
                                <li>First row should contain headers</li>
                                <li>Required columns in order:</li>
                                <ol style="padding-left: 20px; margin-top: 5px;">
                                    <li>Product Name</li>
                                    <li>Category</li>
                                    <li>Purchase Price</li>
                                    <li>Sale Price</li>
                                    <li>Total Stock</li>
                                    <li>Campus</li>
                                </ol>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-2 fs-13"><strong>Available Categories:</strong></p>
                            <div class="mb-2">
                                @if($categories->count() > 0)
                                    @foreach($categories as $cat)
                                        <span class="badge bg-info text-white me-1 mb-1">{{ $cat }}</span>
                                    @endforeach
                                @else
                                    <span class="text-muted">No categories available. Please add categories first.</span>
                                @endif
                            </div>
                            <p class="mb-2 fs-13"><strong>Available Campuses:</strong></p>
                            <div>
                                @foreach($campuses as $campus)
                                    <span class="badge bg-secondary me-1 mb-1">{{ $campus }}</span>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Upload Form -->
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <form action="{{ route('stock.add-bulk-products.store') }}" method="POST" enctype="multipart/form-data" id="bulkUploadForm">
                        @csrf
                        <div class="row">
                            <div class="col-md-8">
                                <label class="form-label mb-2 fs-13 fw-semibold" style="color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">upload_file</span>
                                    Select CSV File <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <input type="file" class="form-control" name="file" id="fileInput" accept=".csv,.txt" required>
                                    <label class="input-group-text" for="fileInput" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471; cursor: pointer;">
                                        <span class="material-symbols-outlined" style="font-size: 18px;">folder_open</span>
                                    </label>
                                </div>
                                <small class="text-muted d-block mt-1">Maximum file size: 10MB. Only CSV files are allowed.</small>
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <div class="d-flex gap-2 w-100">
                                    <a href="{{ route('stock.add-bulk-products.download-template') }}" class="btn btn-sm btn-outline-primary w-50 d-flex align-items-center justify-content-center gap-1">
                                        <span class="material-symbols-outlined" style="font-size: 16px;">download</span>
                                        <span>Download Template</span>
                                    </a>
                                    <button type="submit" class="btn btn-sm w-50 d-flex align-items-center justify-content-center gap-1 bulk-upload-btn">
                                        <span class="material-symbols-outlined" style="font-size: 16px;">upload</span>
                                        <span>Upload</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Sample CSV Preview -->
            <div class="card border-0 shadow-sm mt-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0 fs-14 fw-semibold d-flex align-items-center gap-2" style="color: #003471;">
                        <span class="material-symbols-outlined" style="font-size: 18px;">table_chart</span>
                        <span>Sample CSV Format</span>
                    </h5>
                </div>
                <div class="card-body p-3">
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered mb-0">
                            <thead style="background-color: #f8f9fa;">
                                <tr>
                                    <th>Product Name</th>
                                    <th>Category</th>
                                    <th>Purchase Price</th>
                                    <th>Sale Price</th>
                                    <th>Total Stock</th>
                                    <th>Campus</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Notebook</td>
                                    <td>Stationery</td>
                                    <td>50.00</td>
                                    <td>75.00</td>
                                    <td>100</td>
                                    <td>Main Campus</td>
                                </tr>
                                <tr>
                                    <td>Pen Set</td>
                                    <td>Stationery</td>
                                    <td>30.00</td>
                                    <td>45.00</td>
                                    <td>200</td>
                                    <td>Main Campus</td>
                                </tr>
                                <tr>
                                    <td>Calculator</td>
                                    <td>Electronics</td>
                                    <td>500.00</td>
                                    <td>750.00</td>
                                    <td>50</td>
                                    <td>Branch Campus 1</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.bulk-upload-btn {
    background: linear-gradient(135deg, #003471 0%, #004a9f 100%);
    color: white;
    border: none;
    font-weight: 500;
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(0, 52, 113, 0.25);
}

.bulk-upload-btn:hover {
    background: linear-gradient(135deg, #004a9f 0%, #003471 100%);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 52, 113, 0.35);
    color: white;
}

.bulk-upload-btn:disabled {
    opacity: 0.7;
    cursor: not-allowed;
}

.card {
    border-radius: 8px;
}

.badge {
    font-size: 11px;
    padding: 4px 8px;
}

#fileInput {
    cursor: pointer;
}

#fileInput:hover {
    border-color: #003471;
}
</style>

<script>
document.getElementById('bulkUploadForm').addEventListener('submit', function(e) {
    const fileInput = document.getElementById('fileInput');
    const submitBtn = this.querySelector('button[type="submit"]');
    
    if (!fileInput.files.length) {
        e.preventDefault();
        alert('Please select a file to upload.');
        return;
    }
    
    // Disable submit button and show loading
    submitBtn.disabled = true;
    submitBtn.innerHTML = `
        <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
        <span>Uploading...</span>
    `;
});
</script>
@endsection
