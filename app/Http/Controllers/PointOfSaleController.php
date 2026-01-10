<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\SaleRecord;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PointOfSaleController extends Controller
{
    /**
     * Display the Point of Sale page.
     * Accessible by Super Admin and Accountant.
     */
    public function index(): View
    {
        // Get all products for barcode scanning
        $products = Product::orderBy('product_name', 'asc')->get();
        
        return view('stock.point-of-sale', compact('products'));
    }

    /**
     * Search product by barcode or product code
     */
    public function searchProduct(Request $request)
    {
        $barcode = $request->input('barcode');
        
        if (empty($barcode)) {
            return response()->json([
                'success' => false,
                'message' => 'Please enter barcode or product code'
            ]);
        }

        // Search by product code (since barcode field doesn't exist in Product model)
        $product = Product::where('product_code', $barcode)
            ->orWhere('product_name', 'like', '%' . $barcode . '%')
            ->first();

        if ($product) {
            return response()->json([
                'success' => true,
                'product' => [
                    'id' => $product->id,
                    'name' => $product->product_name,
                    'product_code' => $product->product_code,
                    'price' => $product->sale_price ?? 0,
                    'stock' => $product->total_stock ?? 0,
                ]
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Product not found'
        ]);
    }

    /**
     * Store sale records from Point of Sale
     */
    public function storeSale(Request $request)
    {
        $validated = $request->validate([
            'buyer_name' => 'required|string|max:255',
            'payment_method' => 'required|string|max:255',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.product_name' => 'required|string',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.total_amount' => 'required|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            $saleDate = now()->toDateString();
            
            // Get campus from admin or accountant
            if (Auth::guard('admin')->check()) {
                $campus = Auth::guard('admin')->user()->admin_of ?? 'Main Campus';
            } elseif (Auth::guard('accountant')->check()) {
                $campus = Auth::guard('accountant')->user()->campus ?? 'Main Campus';
            } else {
                $campus = 'Main Campus';
            }

            $savedRecords = [];
            foreach ($validated['items'] as $item) {
                // Get product for category
                $product = Product::find($item['product_id']);
                
                $saleRecord = SaleRecord::create([
                    'product_id' => $item['product_id'],
                    'product_name' => $item['product_name'],
                    'category' => $product->category ?? 'General',
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total_amount' => $item['total_amount'],
                    'method' => $validated['payment_method'],
                    'campus' => $campus,
                    'sale_date' => $saleDate,
                    'notes' => 'Buyer: ' . $validated['buyer_name'],
                ]);
                
                $savedRecords[] = $saleRecord->id;

                // Update product stock
                if ($product) {
                    $product->total_stock = max(0, ($product->total_stock ?? 0) - $item['quantity']);
                    $product->save();
                }
            }

            DB::commit();

            // Log success for debugging
            \Log::info('Sale records saved successfully', [
                'items_count' => count($validated['items']),
                'sale_date' => $saleDate,
                'buyer_name' => $validated['buyer_name'],
                'total_records_created' => count($savedRecords),
                'record_ids' => $savedRecords
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Sale completed successfully! All records saved to database.',
                'records_saved' => count($savedRecords),
                'sale_date' => $saleDate,
                'record_ids' => $savedRecords
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            // Log the error for debugging
            \Log::error('Error saving sale record: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error saving sale: ' . $e->getMessage()
            ], 500);
        }
    }
}

