@extends('layouts.app')

@section('title', 'Products & Stock')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-3 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0 fs-16 fw-semibold">Products & Stock</h4>
                <button type="button" class="btn btn-sm py-2 px-3 d-inline-flex align-items-center gap-1 rounded-8 product-add-btn" data-bs-toggle="modal" data-bs-target="#productModal" onclick="resetForm()">
                    <span class="material-symbols-outlined" style="font-size: 16px;">add</span>
                    <span>Add New Product</span>
                </button>
            </div>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <!-- Table Toolbar -->
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-3 p-3 rounded-8" style="background-color: #f8f9fa; border: 1px solid #e9ecef;">
                <!-- Left Side -->
                <div class="d-flex align-items-center gap-3 flex-wrap">
                    <div class="d-flex align-items-center gap-2">
                        <label for="entriesPerPage" class="mb-0 fs-13 fw-medium text-dark">Show:</label>
                        <select id="entriesPerPage" class="form-select form-select-sm" style="width: auto; min-width: 70px;" onchange="updateEntriesPerPage(this.value)">
                            <option value="10" {{ request('per_page', 10) == 10 ? 'selected' : '' }}>10</option>
                            <option value="25" {{ request('per_page', 25) == 25 ? 'selected' : '' }}>25</option>
                            <option value="50" {{ request('per_page', 50) == 50 ? 'selected' : '' }}>50</option>
                            <option value="100" {{ request('per_page', 100) == 100 ? 'selected' : '' }}>100</option>
                        </select>
                    </div>
                </div>

                <!-- Right Side -->
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <!-- Export Buttons -->
                    <div class="d-flex gap-2">
                        <a href="{{ route('stock.products.export', ['format' => 'excel']) }}{{ request()->has('search') ? '?search=' . request('search') : '' }}" class="btn btn-sm px-2 py-1 export-btn excel-btn">
                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">description</span>
                            <span>Excel</span>
                        </a>
                        <a href="{{ route('stock.products.export', ['format' => 'pdf']) }}{{ request()->has('search') ? '?search=' . request('search') : '' }}" class="btn btn-sm px-2 py-1 export-btn pdf-btn" target="_blank">
                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">picture_as_pdf</span>
                            <span>PDF</span>
                        </a>
                        <button type="button" class="btn btn-sm px-2 py-1 export-btn print-btn" onclick="printTable()">
                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">print</span>
                            <span>Print</span>
                        </button>
                    </div>
                    
                    <!-- Search -->
                    <div class="d-flex align-items-center gap-2">
                        <label for="searchInput" class="mb-0 fs-13 fw-medium text-dark">Search:</label>
                        <div class="input-group input-group-sm search-input-group" style="width: 280px;">
                            <span class="input-group-text bg-light border-end-0" style="background-color: #f0f4ff !important; border-color: #e0e7ff; padding: 4px 8px;">
                                <span class="material-symbols-outlined" style="font-size: 14px; color: #003471;">search</span>
                            </span>
                            <input type="text" id="searchInput" class="form-control border-start-0 border-end-0" placeholder="Search products..." value="{{ request('search') }}" onkeypress="handleSearchKeyPress(event)" oninput="handleSearchInput(event)" style="padding: 4px 8px; font-size: 13px;">
                            @if(request('search'))
                                <button class="btn btn-outline-secondary border-start-0 border-end-0" type="button" onclick="clearSearch()" title="Clear search" style="padding: 4px 8px;">
                                    <span class="material-symbols-outlined" style="font-size: 14px;">close</span>
                                </button>
                            @endif
                            <button class="btn btn-sm search-btn" type="button" onclick="performSearch()" title="Search" style="padding: 4px 10px;">
                                <span class="material-symbols-outlined" style="font-size: 14px;">search</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Table Header -->
            <div class="mb-2 p-2 rounded-8" style="background: linear-gradient(135deg, #003471 0%, #004a9f 100%);">
                <h5 class="mb-0 text-white fs-15 fw-semibold d-flex align-items-center gap-2">
                    <span class="material-symbols-outlined" style="font-size: 18px;">list</span>
                    <span>Products List</span>
                </h5>
            </div>

            <!-- Search Results Info -->
            @if(request('search'))
                <div class="search-results-info">
                    <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle; color: #003471;">search</span>
                    <strong>Search Results:</strong> Showing results for "<strong>{{ request('search') }}</strong>"
                    @if(isset($products))
                        ({{ $products->total() }} {{ $products->total() == 1 ? 'result' : 'results' }} found)
                    @endif
                    <a href="{{ route('stock.products') }}" class="text-decoration-none ms-2" style="color: #003471;">
                        <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">close</span>
                        Clear
                    </a>
                </div>
            @endif

            <div class="default-table-area" style="margin-top: 0;">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Product Name</th>
                                <th>Category</th>
                                <th>Purchase Price</th>
                                <th>Sale Price</th>
                                <th>Total Stock</th>
                                <th>Campus</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if(isset($products) && $products->count() > 0)
                                @forelse($products as $product)
                                    <tr>
                                        <td>{{ $loop->iteration + (($products->currentPage() - 1) * $products->perPage()) }}</td>
                                        <td>
                                            <strong class="text-primary">{{ $product->product_name }}</strong>
                                        </td>
                                        <td>
                                            <span class="badge bg-warning text-dark">{{ $product->category }}</span>
                                        </td>
                                        <td>{{ number_format($product->purchase_price, 2) }}</td>
                                        <td class="text-success fw-semibold">{{ number_format($product->sale_price, 2) }}</td>
                                        <td>
                                            @if($product->total_stock > 0)
                                                <span class="badge bg-success">{{ $product->total_stock }}</span>
                                            @else
                                                <span class="badge bg-danger">Out of Stock</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge bg-info text-white">{{ $product->campus }}</span>
                                        </td>
                                        <td class="text-end">
                                            <div class="d-inline-flex gap-1">
                                                <button type="button" class="btn btn-sm btn-primary px-2 py-0" title="Edit" onclick="editProduct({{ $product->id }}, '{{ addslashes($product->product_name) }}', '{{ addslashes($product->category) }}', {{ $product->purchase_price }}, {{ $product->sale_price }}, {{ $product->total_stock }}, '{{ addslashes($product->campus) }}')">
                                                    <span class="material-symbols-outlined" style="font-size: 14px; color: white;">edit</span>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-danger px-2 py-0" title="Delete" onclick="if(confirm('Are you sure you want to delete this product?')) { document.getElementById('delete-form-{{ $product->id }}').submit(); }">
                                                    <span class="material-symbols-outlined" style="font-size: 14px; color: white;">delete</span>
                                                </button>
                                                <form id="delete-form-{{ $product->id }}" action="{{ route('stock.products.destroy', $product->id) }}" method="POST" class="d-none">
                                                    @csrf
                                                    @method('DELETE')
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-5">
                                            <span class="material-symbols-outlined" style="font-size: 48px; opacity: 0.3;">inbox</span>
                                            <p class="mt-2 mb-0">No products found.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            @else
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-5">
                                        <span class="material-symbols-outlined" style="font-size: 48px; opacity: 0.3;">inbox</span>
                                        <p class="mt-2 mb-0">No products found.</p>
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>

            @if(isset($products) && $products->hasPages())
                <div class="mt-3">
                    {{ $products->links() }}
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Product Modal -->
<div class="modal fade" id="productModal" tabindex="-1" aria-labelledby="productModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
            <div class="modal-header text-white p-3" style="background: linear-gradient(135deg, #003471 0%, #004a9f 100%); border: none;">
                <h5 class="modal-title fs-15 fw-semibold mb-0 d-flex align-items-center gap-2" id="productModalLabel">
                    <span class="material-symbols-outlined" style="font-size: 20px;">inventory_2</span>
                    <span>Add New Product</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" style="opacity: 0.8;"></button>
            </div>
            <form id="productForm" method="POST" action="{{ route('stock.products.store') }}">
                @csrf
                <div id="methodField"></div>
                <div class="modal-body p-3">
                    <div class="row g-2">
                        <div class="col-md-12">
                            <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Product Name <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm product-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">inventory_2</span>
                                </span>
                                <input type="text" class="form-control product-input" name="product_name" id="product_name" placeholder="Enter product name" required>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Category <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm product-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">category</span>
                                </span>
                                <select class="form-control product-input" name="category" id="category" required style="border: none; border-left: 1px solid #e0e7ff; border-radius: 0 8px 8px 0;">
                                    <option value="">Select Category</option>
                                    @foreach($categories as $cat)
                                        <option value="{{ $cat }}">{{ $cat }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Purchase Price <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm product-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">payments</span>
                                </span>
                                <input type="number" step="0.01" class="form-control product-input" name="purchase_price" id="purchase_price" placeholder="0.00" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Sale Price <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm product-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">sell</span>
                                </span>
                                <input type="number" step="0.01" class="form-control product-input" name="sale_price" id="sale_price" placeholder="0.00" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Total Stock <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm product-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">inventory</span>
                                </span>
                                <input type="number" class="form-control product-input" name="total_stock" id="total_stock" placeholder="0" required min="0">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Campus <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm product-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">location_on</span>
                                </span>
                                <select class="form-control product-input" name="campus" id="campus" required style="border: none; border-left: 1px solid #e0e7ff; border-radius: 0 8px 8px 0;">
                                    <option value="">Select Campus</option>
                                    @foreach($campuses as $campus)
                                        <option value="{{ $campus }}">{{ $campus }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer p-3" style="background-color: #f8f9fa; border-top: 1px solid #e9ecef;">
                    <button type="button" class="btn btn-sm py-2 px-4 rounded-8" data-bs-dismiss="modal" style="background-color: #6c757d; color: white; border: none; transition: all 0.3s ease;">
                        <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">close</span>
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-sm py-2 px-4 rounded-8 product-submit-btn">
                        <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">save</span>
                        Save Product
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    /* Product Form Styling */
    #productModal .product-input-group {
        border-radius: 8px;
        overflow: hidden;
        transition: all 0.3s ease;
        border: 1px solid #dee2e6;
    }
    
    #productModal .product-input-group:focus-within {
        box-shadow: 0 0 0 3px rgba(0, 52, 113, 0.15);
        border-color: #003471;
    }
    
    #productModal .product-input {
        font-size: 13px;
        padding: 0.5rem 0.75rem;
        border: none;
        border-left: 1px solid #e0e7ff;
        border-radius: 0 8px 8px 0;
        transition: all 0.3s ease;
    }
    
    #productModal .product-input:focus {
        border-left-color: #003471;
        box-shadow: none;
        outline: none;
    }
    
    #productModal .input-group-text {
        padding: 0 0.75rem;
        display: flex;
        align-items: center;
        border: none;
        border-right: 1px solid #e0e7ff;
        border-radius: 8px 0 0 8px;
        transition: all 0.3s ease;
    }
    
    #productModal .product-input-group:focus-within .input-group-text {
        background-color: #003471 !important;
        color: white !important;
        border-right-color: #003471;
    }
    
    #productModal .form-label {
        margin-bottom: 0.4rem;
        line-height: 1.3;
    }
    
    #productModal .product-submit-btn {
        background: linear-gradient(135deg, #003471 0%, #004a9f 100%);
        color: white;
        border: none;
        font-weight: 500;
        transition: all 0.3s ease;
        box-shadow: 0 2px 8px rgba(0, 52, 113, 0.25);
    }
    
    #productModal .product-submit-btn:hover {
        background: linear-gradient(135deg, #004a9f 0%, #003471 100%);
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0, 52, 113, 0.35);
    }
    
    /* Add New Button Styling */
    .product-add-btn {
        background: linear-gradient(135deg, #003471 0%, #004a9f 100%);
        color: white;
        border: none;
        font-weight: 500;
        transition: all 0.3s ease;
        box-shadow: 0 2px 8px rgba(0, 52, 113, 0.25);
    }
    
    .product-add-btn:hover {
        background: linear-gradient(135deg, #004a9f 0%, #003471 100%);
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0, 52, 113, 0.35);
        color: white;
    }
    
    /* Export Buttons */
    .export-btn {
        border: none;
        font-weight: 500;
        transition: all 0.3s ease;
        font-size: 12px;
    }
    
    .excel-btn {
        background-color: #28a745;
        color: white;
    }
    
    .excel-btn:hover {
        background-color: #218838;
        color: white;
        transform: translateY(-1px);
    }
    
    .pdf-btn {
        background-color: #dc3545;
        color: white;
    }
    
    .pdf-btn:hover {
        background-color: #c82333;
        color: white;
        transform: translateY(-1px);
    }
    
    .print-btn {
        background-color: #6c757d;
        color: white;
    }
    
    .print-btn:hover {
        background-color: #5a6268;
        color: white;
        transform: translateY(-1px);
    }
    
    .search-input-group {
        border-radius: 8px;
        overflow: hidden;
        border: 1px solid #dee2e6;
    }
    
    .search-input-group .form-control {
        border: none;
        font-size: 13px;
    }
    
    .search-input-group .input-group-text {
        height: 32px;
        padding: 4px 8px;
        display: flex;
        align-items: center;
    }
    
    .search-btn {
        background: linear-gradient(135deg, #003471 0%, #004a9f 100%);
        color: white;
        border: none;
        padding: 4px 10px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s ease;
    }
    
    .search-btn:hover {
        background: linear-gradient(135deg, #004a9f 0%, #003471 100%);
        color: white;
        transform: translateY(-1px);
        box-shadow: 0 2px 6px rgba(0, 52, 113, 0.3);
    }
    
    .search-results-info {
        padding: 8px 12px;
        background-color: #e7f3ff;
        border-left: 3px solid #003471;
        border-radius: 4px;
        margin-bottom: 15px;
        font-size: 13px;
    }
    
    .default-table-area table {
        margin-bottom: 0;
        border-spacing: 0;
        border-collapse: collapse;
        border: 1px solid #dee2e6;
    }
    
    .default-table-area table thead th {
        padding: 5px 10px;
        font-size: 12px;
        font-weight: 600;
        vertical-align: middle;
        line-height: 1.3;
        height: 32px;
        white-space: nowrap;
        border: 1px solid #dee2e6;
        background-color: #f8f9fa;
    }
    
    .default-table-area table tbody td {
        padding: 5px 10px;
        font-size: 12px;
        vertical-align: middle;
        line-height: 1.4;
        border: 1px solid #dee2e6;
    }
    
    .default-table-area table tbody tr:hover {
        background-color: #f8f9fa;
    }
    
    .default-table-area .badge {
        font-size: 11px;
        padding: 3px 6px;
        font-weight: 500;
    }
</style>

<script>
// Reset form when opening modal for new product
function resetForm() {
    document.getElementById('productForm').reset();
    document.getElementById('productForm').action = "{{ route('stock.products.store') }}";
    document.getElementById('methodField').innerHTML = '';
    document.getElementById('productModalLabel').innerHTML = `
        <span class="material-symbols-outlined" style="font-size: 20px;">inventory_2</span>
        <span>Add New Product</span>
    `;
    document.querySelector('.product-submit-btn').innerHTML = `
        <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">save</span>
        Save Product
    `;
}

// Edit product function
function editProduct(id, productName, category, purchasePrice, salePrice, totalStock, campus) {
    document.getElementById('product_name').value = productName;
    document.getElementById('category').value = category;
    document.getElementById('purchase_price').value = purchasePrice;
    document.getElementById('sale_price').value = salePrice;
    document.getElementById('total_stock').value = totalStock;
    document.getElementById('campus').value = campus;
    document.getElementById('productForm').action = "{{ url('stock/products') }}/" + id;
    document.getElementById('methodField').innerHTML = '@method("PUT")';
    document.getElementById('productModalLabel').innerHTML = `
        <span class="material-symbols-outlined" style="font-size: 20px;">edit</span>
        <span>Edit Product</span>
    `;
    document.querySelector('.product-submit-btn').innerHTML = `
        <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">save</span>
        Update Product
    `;
    
    const modal = new bootstrap.Modal(document.getElementById('productModal'));
    modal.show();
}

// Search functionality
function performSearch() {
    const searchInput = document.getElementById('searchInput');
    const searchValue = searchInput.value.trim();
    const url = new URL(window.location.href);
    
    if (searchValue) {
        url.searchParams.set('search', searchValue);
    } else {
        url.searchParams.delete('search');
    }
    
    url.searchParams.set('page', '1');
    
    searchInput.disabled = true;
    const searchBtn = document.querySelector('.search-btn');
    if (searchBtn) {
        searchBtn.disabled = true;
        searchBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span>';
    }
    
    window.location.href = url.toString();
}

function handleSearchKeyPress(event) {
    if (event.key === 'Enter') {
        event.preventDefault();
        performSearch();
    }
}

function clearSearch() {
    const url = new URL(window.location.href);
    url.searchParams.delete('search');
    url.searchParams.set('page', '1');
    
    const searchInput = document.getElementById('searchInput');
    searchInput.disabled = true;
    
    window.location.href = url.toString();
}

// Update entries per page
function updateEntriesPerPage(value) {
    const url = new URL(window.location.href);
    url.searchParams.set('per_page', value);
    url.searchParams.set('page', '1');
    window.location.href = url.toString();
}

// Print table
function printTable() {
    const printContents = document.querySelector('.default-table-area').innerHTML;
    const originalContents = document.body.innerHTML;
    
    document.body.innerHTML = `
        <div style="padding: 20px;">
            <h3 style="text-align: center; margin-bottom: 20px; color: #003471;">Products & Stock List</h3>
            ${printContents}
        </div>
    `;
    
    window.print();
    document.body.innerHTML = originalContents;
    window.location.reload();
}
</script>
@endsection

