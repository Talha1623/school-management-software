@extends('layouts.app')

@section('title', 'Point of Sale')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-2 mb-2">
            <h4 class="mb-2 fw-semibold" style="color: #003471; font-size: 16px;">Point of Sale</h4>
            
            <div class="row g-2">
                <!-- Left Column: Barcode Scan -->
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm h-100" style="border-radius: 8px; overflow: hidden;">
                        <!-- Header -->
                        <div class="card-header p-2" style="background: linear-gradient(135deg, #90EE90 0%, #7FCD7F 100%); border: none;">
                            <h6 class="mb-0 fw-semibold d-flex align-items-center gap-1" style="font-size: 14px; color: white;">
                                <span class="material-symbols-outlined" style="font-size: 18px; color: white;">qr_code_scanner</span>
                                <span style="color: white;">Barcode Scan</span>
                            </h6>
                        </div>
                        
                        <div class="card-body p-3">
                            <!-- Campus Selector -->
                            <div class="mb-2">
                                <label class="form-label fw-semibold mb-1" style="color: #003471; font-size: 10px;">
                                    <span class="material-symbols-outlined" style="font-size: 12px; vertical-align: middle;">location_on</span>
                                    Campus *
                                </label>
                                <select class="form-select form-select-sm" id="campusSelect" required style="font-size: 11px; padding: 4px 6px;">
                                    <option value="">Select Campus</option>
                                    @foreach($campuses as $campus)
                                        @php
                                            $campusName = is_object($campus) ? ($campus->campus_name ?? $campus->name ?? '') : $campus;
                                        @endphp
                                        <option value="{{ $campusName }}">{{ $campusName }}</option>
                                    @endforeach
                                </select>
                            </div>
                            
                            <!-- Barcode Input -->
                            <div class="mb-2">
                                <label class="form-label fw-semibold mb-1" style="color: #003471; font-size: 10px;">
                                    <span class="material-symbols-outlined" style="font-size: 12px; vertical-align: middle;">qr_code</span>
                                    Barcode / Product Code
                                </label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-light" style="border-right: none; padding: 4px 6px;">
                                        <span class="material-symbols-outlined" style="color: #003471; font-size: 14px;">qr_code</span>
                                    </span>
                                    <input type="text" class="form-control form-control-sm" id="barcodeInput" placeholder="Enter barcode or product code..." autofocus style="border-left: none; border-right: none; font-size: 11px; padding: 4px 6px;">
                                    <button type="button" class="btn btn-warning btn-sm px-2" id="scanBtn" onclick="scanProduct()" style="border-left: none; font-size: 11px; color: white;">
                                        <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle; color: white;">search</span>
                                        <span style="color: white;">Scan</span>
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Buyer Name Input -->
                            <div class="mb-2">
                                <label class="form-label fw-semibold mb-1" style="color: #003471; font-size: 10px;">
                                    <span class="material-symbols-outlined" style="font-size: 12px; vertical-align: middle;">person</span>
                                    Buyer Name
                                </label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-light" style="border-right: none; padding: 4px 6px;">
                                        <span class="material-symbols-outlined" style="color: #003471; font-size: 14px;">person</span>
                                    </span>
                                    <input type="text" class="form-control form-control-sm" id="buyerNameInput" placeholder="Enter buyer name..." style="border-left: none; border-right: none; font-size: 11px; padding: 4px 6px;">
                                    <select class="form-select form-select-sm" id="paymentMethod" style="border-left: none; max-width: 120px; font-size: 11px; padding: 4px 6px;">
                                        <option value="Cash">Cash</option>
                                        <option value="Bank Transfer">Bank Transfer</option>
                                        <option value="Card">Card</option>
                                        <option value="Online Payment">Online Payment</option>
                                        <option value="Cheque">Cheque</option>
                                    </select>
                                </div>
                            </div>
                            
                            <!-- Barcode Scanner Illustration -->
                            <div class="text-center mb-2">
                                <div class="position-relative d-inline-block">
                                    <div class="rounded-circle bg-danger d-flex align-items-center justify-content-center" style="width: 120px; height: 120px; margin: 0 auto;">
                                        <div class="bg-dark rounded p-2" style="width: 80px; height: 50px; position: relative;">
                                            <div class="bg-warning rounded" style="width: 20px; height: 5px; position: absolute; top: 8px; left: 8px;"></div>
                                            <div class="bg-white rounded" style="width: 50px; height: 2px; position: absolute; top: 18px; left: 15px;"></div>
                                            <div class="bg-white rounded" style="width: 40px; height: 2px; position: absolute; top: 25px; left: 15px;"></div>
                                            <div class="bg-white rounded" style="width: 45px; height: 2px; position: absolute; top: 32px; left: 15px;"></div>
                                            <div class="bg-white rounded" style="width: 35px; height: 2px; position: absolute; top: 39px; left: 15px;"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Instruction Text -->
                            <div class="text-center">
                                <p class="mb-0 fw-medium" style="color: #333; font-size: 11px;">
                                    Scan Product Barcode For Quick Processing...!
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Right Column: Basket -->
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm h-100" style="border-radius: 8px; overflow: hidden;">
                        <!-- Header -->
                        <div class="card-header p-2" style="background: linear-gradient(135deg, #90EE90 0%, #7FCD7F 100%); border: none;">
                            <h6 class="mb-0 fw-semibold d-flex align-items-center gap-1" style="font-size: 14px; color: white;">
                                <span class="material-symbols-outlined" style="font-size: 18px; color: white;">shopping_basket</span>
                                <span style="color: white;">Basket</span>
                            </h6>
                        </div>
                        
                        <div class="card-body p-3">
                            <!-- Empty Basket Illustration -->
                            <div class="text-center mb-2" id="emptyBasket">
                                <div class="position-relative d-inline-block">
                                    <!-- Cat Illustration -->
                                    <div class="position-absolute" style="top: -15px; left: 50%; transform: translateX(-50%); z-index: 1;">
                                        <div class="bg-warning rounded-circle d-flex align-items-center justify-content-center" style="width: 60px; height: 60px; border: 2px solid #ff9800;">
                                            <span class="material-symbols-outlined" style="font-size: 30px; color: #333;">pets</span>
                                        </div>
                                    </div>
                                    <!-- Basket Illustration -->
                                    <div class="bg-purple position-relative" style="width: 150px; height: 110px; margin-top: 30px; border-radius: 6px; border: 2px dashed #999; background: linear-gradient(135deg, #9c27b0 0%, #7b1fa2 100%);">
                                        <div class="position-absolute" style="top: -10px; left: 50%; transform: translateX(-50%); width: 50px; height: 15px; background: #7b1fa2; border-radius: 3px;"></div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Basket Items List -->
                            <div id="basketItems" style="display: none; max-height: 350px; overflow-y: auto;">
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered mb-2" style="font-size: 11px;">
                                        <thead style="background-color: #f8f9fa;">
                                            <tr>
                                                <th style="padding: 6px 4px; font-size: 11px; font-weight: 600;">Product</th>
                                                <th style="padding: 6px 4px; font-size: 11px; font-weight: 600; width: 80px;">Qty</th>
                                                <th style="padding: 6px 4px; font-size: 11px; font-weight: 600; width: 70px;">Price</th>
                                                <th style="padding: 6px 4px; font-size: 11px; font-weight: 600; width: 80px;">Total</th>
                                                <th style="padding: 6px 4px; font-size: 11px; font-weight: 600; width: 50px;">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody id="basketTableBody">
                                            <!-- Items will be added here dynamically -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            
                            <!-- Total Display -->
                            <div class="mt-2 p-2 rounded" style="background-color: #f8f9fa; border: 1px solid #dee2e6;">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="fw-semibold" style="color: #666; font-size: 14px;">Total:</span>
                                    <span class="fw-bold" id="basketTotal" style="color: #003471; font-size: 18px;">PKR 0.00</span>
                                </div>
                            </div>
                            
                            <!-- Action Buttons -->
                            <div class="d-flex gap-2 mt-2">
                                <button type="button" class="btn btn-warning btn-sm flex-fill" id="completeOrderBtn" onclick="completeOrder()" disabled style="font-size: 12px; padding: 6px 12px; color: white;">
                                    <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle; color: white;">check_circle</span>
                                    <span style="color: white;">Complete Order</span>
                                </button>
                                <button type="button" class="btn btn-danger btn-sm flex-fill" id="clearBasketBtn" onclick="clearBasket()" disabled style="font-size: 12px; padding: 6px 12px; color: white;">
                                    <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle; color: white;">delete</span>
                                    <span style="color: white;">Clear Basket</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Available Products Section -->
<div class="row mt-3">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-2 mb-2">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h4 class="mb-0 fw-semibold" style="color: #003471; font-size: 16px;">Available Products</h4>
                <button type="button" class="btn btn-sm btn-primary" onclick="refreshProducts()" style="font-size: 11px; padding: 4px 12px;">
                    <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">refresh</span>
                    Refresh
                </button>
            </div>
            
            <div class="mb-2">
                <input type="text" class="form-control form-control-sm" id="productSearchInput" placeholder="Search products by name or code..." style="font-size: 11px; padding: 4px 8px;" onkeyup="filterProducts()">
            </div>
            
            <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                <table class="table table-bordered table-sm table-hover" style="font-size: 11px;">
                    <thead style="background-color: #f8f9fa; position: sticky; top: 0; z-index: 10;">
                        <tr>
                            <th style="padding: 6px; font-size: 11px; font-weight: 600;">Sr.</th>
                            <th style="padding: 6px; font-size: 11px; font-weight: 600;">Product Name</th>
                            <th style="padding: 6px; font-size: 11px; font-weight: 600;">Product Code</th>
                            <th style="padding: 6px; font-size: 11px; font-weight: 600;">Category</th>
                            <th style="padding: 6px; font-size: 11px; font-weight: 600;">Campus</th>
                            <th style="padding: 6px; font-size: 11px; font-weight: 600;">Sale Price</th>
                            <th style="padding: 6px; font-size: 11px; font-weight: 600;">Stock</th>
                            <th style="padding: 6px; font-size: 11px; font-weight: 600;">Action</th>
                        </tr>
                    </thead>
                    <tbody id="productsTableBody">
                        @forelse($products as $index => $product)
                        <tr class="product-row" data-name="{{ strtolower($product->product_name ?? '') }}" data-code="{{ strtolower($product->product_code ?? '') }}" data-campus="{{ strtolower($product->campus ?? '') }}">
                            <td style="padding: 6px;">{{ $index + 1 }}</td>
                            <td style="padding: 6px; font-weight: 500;">{{ $product->product_name ?? 'N/A' }}</td>
                            <td style="padding: 6px;">{{ $product->product_code ?? 'N/A' }}</td>
                            <td style="padding: 6px;">{{ $product->category ?? 'N/A' }}</td>
                            <td style="padding: 6px;">{{ $product->campus ?? 'N/A' }}</td>
                            <td style="padding: 6px; text-align: right;">PKR {{ number_format($product->sale_price ?? 0, 2) }}</td>
                            <td style="padding: 6px; text-align: center;">
                                <span class="badge {{ ($product->total_stock ?? 0) > 0 ? 'bg-success' : 'bg-danger' }}" style="font-size: 10px;">
                                    {{ $product->total_stock ?? 0 }}
                                </span>
                            </td>
                            <td style="padding: 6px; text-align: center;">
                                <button type="button" class="btn btn-sm btn-warning" onclick="addProductToBasket({{ $product->id }}, '{{ addslashes($product->product_name) }}', '{{ $product->product_code ?? '' }}', {{ $product->sale_price ?? 0 }}, {{ $product->total_stock ?? 0 }})" style="padding: 2px 8px; font-size: 10px; color: white;" {{ ($product->total_stock ?? 0) <= 0 ? 'disabled' : '' }}>
                                    <span class="material-symbols-outlined" style="font-size: 14px; color: white;">add_shopping_cart</span>
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center py-4">
                                <span class="material-symbols-outlined text-muted" style="font-size: 48px;">inventory_2</span>
                                <p class="text-muted mt-2 mb-0">No products available. Add products from Product and Stock page.</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
    .bg-purple {
        background-color: #9c27b0;
    }
    
    #basketItems table tbody tr {
        border-bottom: 1px solid #dee2e6;
    }
    
    #basketItems table tbody tr:last-child {
        border-bottom: none;
    }
    
    #basketItems table tbody td {
        padding: 6px 4px !important;
        vertical-align: middle;
        font-size: 11px;
    }
    
    #basketItems .btn-sm {
        padding: 2px 6px;
        font-size: 10px;
    }
</style>

<script>
let basket = [];

// Scan product function
function scanProduct() {
    const barcode = document.getElementById('barcodeInput').value.trim();
    const campus = document.getElementById('campusSelect').value;
    
    if (!campus) {
        alert('Please select campus first');
        return;
    }
    
    if (!barcode) {
        alert('Please enter barcode or product code');
        return;
    }
    
    // Show loading
    const scanBtn = document.getElementById('scanBtn');
    const originalText = scanBtn.innerHTML;
    scanBtn.disabled = true;
    scanBtn.innerHTML = '<span class="spinner-border spinner-border-sm" style="width: 12px; height: 12px;"></span> Scanning...';
    
    // Search product
    fetch('{{ route("stock.point-of-sale.search-product") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
        },
        body: JSON.stringify({ barcode: barcode, campus: campus })
    })
    .then(response => response.json())
    .then(data => {
        scanBtn.disabled = false;
        scanBtn.innerHTML = originalText;
        
        if (data.success && data.product) {
            addToBasket(data.product);
            document.getElementById('barcodeInput').value = '';
            document.getElementById('barcodeInput').focus();
        } else {
            alert(data.message || 'Product not found');
        }
    })
    .catch(error => {
        scanBtn.disabled = false;
        scanBtn.innerHTML = originalText;
        console.error('Error:', error);
        alert('Error scanning product. Please try again.');
    });
}

// Add product to basket
function addToBasket(product) {
    // Check if product already exists in basket
    const existingItem = basket.find(item => item.id === product.id);
    
    const availableStock = parseInt(product.stock ?? 0, 10);
    if (availableStock <= 0) {
        alert('Out of stock');
        return;
    }

    if (existingItem) {
        if (existingItem.quantity + 1 > existingItem.stock) {
            alert('Stock limit reached. Available: ' + existingItem.stock);
            return;
        }
        existingItem.quantity += 1;
        // Update stock display in products table
        updateProductStockDisplay(product.id, 1);
    } else {
        basket.push({
            id: product.id,
            name: product.name,
            product_code: product.product_code || '',
            price: parseFloat(product.price) || 0,
            quantity: 1,
            stock: availableStock
        });
        // Update stock display in products table
        updateProductStockDisplay(product.id, 1);
    }
    
    updateBasketDisplay();
}

// Remove item from basket
function removeFromBasket(productId) {
    const item = basket.find(item => item.id === productId);
    if (item) {
        const quantityToRestore = item.quantity;
        basket = basket.filter(item => item.id !== productId);
        // Restore stock display in products table
        updateProductStockDisplay(productId, -quantityToRestore);
    }
    updateBasketDisplay();
}

// Update quantity
function updateQuantity(productId, change) {
    const item = basket.find(item => item.id === productId);
    if (item) {
        if (change > 0 && item.quantity + change > item.stock) {
            alert('Stock limit reached. Available: ' + item.stock);
            return;
        }
        const oldQuantity = item.quantity;
        item.quantity += change;
        if (item.quantity <= 0) {
            removeFromBasket(productId);
            return;
        }
        // Update stock display in products table
        const quantityChange = item.quantity - oldQuantity;
        updateProductStockDisplay(productId, quantityChange);
        updateBasketDisplay();
    }
}

// Update basket display
function updateBasketDisplay() {
    const basketItems = document.getElementById('basketItems');
    const emptyBasket = document.getElementById('emptyBasket');
    const basketTableBody = document.getElementById('basketTableBody');
    const basketTotal = document.getElementById('basketTotal');
    const completeOrderBtn = document.getElementById('completeOrderBtn');
    const clearBasketBtn = document.getElementById('clearBasketBtn');
    
    if (basket.length === 0) {
        basketItems.style.display = 'none';
        emptyBasket.style.display = 'block';
        basketTotal.textContent = 'PKR 0.00';
        completeOrderBtn.disabled = true;
        clearBasketBtn.disabled = true;
        
    } else {
        basketItems.style.display = 'block';
        emptyBasket.style.display = 'none';
        completeOrderBtn.disabled = false;
        clearBasketBtn.disabled = false;
        
        // Clear table
        basketTableBody.innerHTML = '';
        
        // Calculate total
        let total = 0;
        
        // Add items to basket table
        basket.forEach((item, index) => {
            const itemTotal = item.price * item.quantity;
            total += itemTotal;
            
            const row = document.createElement('tr');
            row.innerHTML = `
                <td style="font-size: 11px;">${item.name}${item.product_code ? ' (' + item.product_code + ')' : ''}</td>
                <td>
                    <div class="d-flex align-items-center gap-1 justify-content-center">
                        <button class="btn btn-sm btn-outline-secondary" onclick="updateQuantity(${item.id}, -1)" style="padding: 1px 4px; font-size: 10px; min-width: 24px;">-</button>
                        <span style="min-width: 20px; text-align: center; font-weight: 600;">${item.quantity}</span>
                        <button class="btn btn-sm btn-outline-secondary" onclick="updateQuantity(${item.id}, 1)" style="padding: 1px 4px; font-size: 10px; min-width: 24px;">+</button>
                    </div>
                </td>
                <td style="font-size: 11px; text-align: right;">PKR ${item.price.toFixed(2)}</td>
                <td style="font-size: 11px; text-align: right; font-weight: 600;">PKR ${itemTotal.toFixed(2)}</td>
                <td style="text-align: center;">
                    <button class="btn btn-sm btn-danger" onclick="removeFromBasket(${item.id})" style="padding: 2px 6px; font-size: 10px; color: white;">
                        <span class="material-symbols-outlined" style="font-size: 14px; color: white;">delete</span>
                    </button>
                </td>
            `;
            basketTableBody.appendChild(row);
        });
        
        basketTotal.textContent = `PKR ${total.toFixed(2)}`;
    }
}

// Clear basket
function clearBasket() {
    if (confirm('Are you sure you want to clear the basket?')) {
        // Restore stock for all items in basket
        basket.forEach(item => {
            updateProductStockDisplay(item.id, -item.quantity);
        });
        basket = [];
        updateBasketDisplay();
    }
}

// Complete order
function completeOrder() {
    if (basket.length === 0) {
        alert('Basket is empty');
        return;
    }
    
    const buyerName = document.getElementById('buyerNameInput').value.trim();
    const paymentMethod = document.getElementById('paymentMethod').value;
    const campus = document.getElementById('campusSelect').value;
    const total = basket.reduce((sum, item) => sum + (item.price * item.quantity), 0);
    
    if (!campus) {
        alert('Please select campus');
        return;
    }
    
    if (!buyerName) {
        alert('Please enter buyer name');
        return;
    }
    
    if (confirm(`Complete order for ${buyerName}?\nCampus: ${campus}\nTotal: PKR ${total.toFixed(2)}\nPayment Method: ${paymentMethod}`)) {
        // Prepare order data
        const orderData = {
            buyer_name: buyerName,
            payment_method: paymentMethod,
            campus: campus,
            items: basket.map(item => ({
                product_id: item.id,
                product_name: item.name,
                quantity: item.quantity,
                unit_price: item.price,
                total_amount: item.price * item.quantity
            }))
        };
        
        // Show loading
        const completeOrderBtn = document.getElementById('completeOrderBtn');
        const originalText = completeOrderBtn.innerHTML;
        completeOrderBtn.disabled = true;
        completeOrderBtn.innerHTML = '<span class="spinner-border spinner-border-sm" style="width: 12px; height: 12px;"></span> Processing...';
        
        // Send order to server
        fetch('{{ route("stock.point-of-sale.store-sale") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            },
            body: JSON.stringify(orderData)
        })
        .then(response => {
            if (!response.ok) {
                return response.text().then(text => {
                    try {
                        const err = JSON.parse(text);
                        throw new Error(err.message || 'Server error');
                    } catch (e) {
                        throw new Error('Server error: ' + response.status);
                    }
                });
            }
            return response.json();
        })
        .then(data => {
            completeOrderBtn.disabled = false;
            completeOrderBtn.innerHTML = originalText;
            
            if (data.success) {
                console.log('Sale saved successfully:', data);
                
                // Update stock in products table for sold items
                const soldItems = basket.map(item => ({
                    id: item.id,
                    quantity: item.quantity
                }));
                
                // Update stock values in the products table
                soldItems.forEach(soldItem => {
                    const productRow = document.querySelector(`button[onclick*="addProductToBasket(${soldItem.id}"]`)?.closest('tr');
                    if (productRow) {
                        const stockCell = productRow.querySelector('td:nth-child(6)');
                        const stockBadge = stockCell?.querySelector('.badge');
                        if (stockBadge) {
                            const currentStock = parseInt(stockBadge.textContent.trim()) || 0;
                            const newStock = Math.max(0, currentStock - soldItem.quantity);
                            stockBadge.textContent = newStock;
                            stockBadge.className = `badge ${newStock > 0 ? 'bg-success' : 'bg-danger'}`;
                            
                            // Disable add button if stock is 0
                            const addButton = productRow.querySelector('button[onclick*="addProductToBasket"]');
                            if (addButton) {
                                if (newStock <= 0) {
                                    addButton.disabled = true;
                                } else {
                                    addButton.disabled = false;
                                }
                            }
                        }
                    }
                });
                
                alert('Order completed successfully!\n\n' + 
                      'Records saved: ' + (data.records_saved || basket.length) + '\n' +
                      'Sale Date: ' + (data.sale_date || 'Today') + '\n\n' +
                      'Stock has been updated automatically.');
                basket = [];
                document.getElementById('buyerNameInput').value = '';
                document.getElementById('barcodeInput').value = '';
                updateBasketDisplay();
            } else {
                console.error('Sale save failed:', data);
                alert('Error: ' + (data.message || 'Failed to save sale records'));
            }
        })
        .catch(error => {
            completeOrderBtn.disabled = false;
            completeOrderBtn.innerHTML = originalText;
            console.error('Error saving sale:', error);
            alert('Error saving sale: ' + error.message + '\n\nPlease check browser console for details.');
        });
    }
}

// Add product directly to basket from products table
function addProductToBasket(productId, productName, productCode, price, stock) {
    const product = {
        id: productId,
        name: productName,
        product_code: productCode,
        price: parseFloat(price) || 0,
        stock: parseInt(stock) || 0
    };
    
    addToBasket(product);
}

// Filter products by search and campus
function filterProducts() {
    const searchInput = document.getElementById('productSearchInput');
    const campusSelect = document.getElementById('campusSelect');
    const filter = searchInput.value.toLowerCase();
    const selectedCampus = campusSelect ? campusSelect.value.toLowerCase() : '';
    const rows = document.querySelectorAll('.product-row');
    
    rows.forEach(row => {
        const name = row.getAttribute('data-name');
        const code = row.getAttribute('data-code');
        const campus = row.getAttribute('data-campus') || '';
        
        const matchesSearch = !filter || name.includes(filter) || code.includes(filter);
        const matchesCampus = !selectedCampus || campus === selectedCampus;
        
        if (matchesSearch && matchesCampus) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

// Filter products when campus changes
document.addEventListener('DOMContentLoaded', function() {
    const campusSelect = document.getElementById('campusSelect');
    if (campusSelect) {
        campusSelect.addEventListener('change', function() {
            filterProducts();
        });
    }
});

// Refresh products list
function refreshProducts() {
    // Reload the page to get latest products and stock
    window.location.reload();
}

// Update stock display in products table after adding to basket
function updateProductStockDisplay(productId, quantityChange) {
    const productRow = document.querySelector(`button[onclick*="addProductToBasket(${productId}"]`)?.closest('tr');
    if (productRow) {
        const stockCell = productRow.querySelector('td:nth-child(6)');
        const stockBadge = stockCell?.querySelector('.badge');
        if (stockBadge) {
            const currentStock = parseInt(stockBadge.textContent.trim()) || 0;
            const newStock = Math.max(0, currentStock - quantityChange);
            stockBadge.textContent = newStock;
            stockBadge.className = `badge ${newStock > 0 ? 'bg-success' : 'bg-danger'}`;
            
            // Disable add button if stock is 0
            const addButton = productRow.querySelector('button[onclick*="addProductToBasket"]');
            if (addButton) {
                if (newStock <= 0) {
                    addButton.disabled = true;
                } else {
                    addButton.disabled = false;
                }
            }
        }
    }
}

// Allow Enter key to scan
document.addEventListener('DOMContentLoaded', function() {
    const barcodeInput = document.getElementById('barcodeInput');
    if (barcodeInput) {
        barcodeInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                scanProduct();
            }
        });
    }
});
</script>

@endsection
