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
