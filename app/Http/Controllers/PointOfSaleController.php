<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\SaleRecord;
use App\Models\Campus;
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
        
        // Get campuses dynamically
        $campuses = Campus::orderBy('campus_name', 'asc')->get();
        if ($campuses->isEmpty()) {
            // Fallback: get from products
            $campuses = Product::whereNotNull('campus')
                ->distinct()
                ->pluck('campus')
                ->map(function($campusName) {
                    return (object)['campus_name' => $campusName];
                })
                ->sortBy('campus_name')
                ->values();
        }
        
        return view('stock.point-of-sale', compact('products', 'campuses'));
    }

    /**
     * Search product by barcode or product code.
     * Supports multiple DB rows sharing the same product_code (e.g. each unit stock = 1):
     * picks the next in-stock row, optionally skipping basket lines already at max qty.
     */
    public function searchProduct(Request $request)
    {
        $barcode = trim((string) $request->input('barcode', ''));
        $campus = $request->input('campus');
        $excludeIds = array_values(array_filter(array_map('intval', (array) $request->input('exclude_ids', []))));

        if ($barcode === '') {
            return response()->json([
                'success' => false,
                'message' => 'Please enter barcode or product code',
            ]);
        }

        $codeLower = strtolower($barcode);

        $product = $this->findPosProductByCode($codeLower, $campus, $excludeIds, true);

        if (!$product) {
            $product = $this->findPosProductByCode($codeLower, $campus, $excludeIds, false);
        }

        if (!$product) {
            $nameQuery = $this->posProductBaseQuery($campus);
            if (!empty($excludeIds)) {
                $nameQuery->whereNotIn('id', $excludeIds);
            }
            $product = $nameQuery
                ->whereRaw('LOWER(product_name) LIKE ?', ['%' . $codeLower . '%'])
                ->orderByDesc('total_stock')
                ->orderBy('id')
                ->first();
        }

        if ($product) {
            $stock = (int) ($product->total_stock ?? 0);
            if ($stock <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Out of stock for product code: ' . ($product->product_code ?: $barcode),
                ]);
            }

            return response()->json([
                'success' => true,
                'product' => [
                    'id' => $product->id,
                    'name' => $product->product_name,
                    'product_code' => $product->product_code,
                    'price' => $product->sale_price ?? 0,
                    'stock' => $stock,
                    'campus' => $product->campus ?? 'N/A',
                ],
            ]);
        }

        $hasCodeRows = $this->posProductBaseQuery($campus)
            ->whereRaw('LOWER(TRIM(product_code)) = ?', [$codeLower])
            ->exists();

        if ($hasCodeRows) {
            return response()->json([
                'success' => false,
                'message' => 'All units with this product code are out of stock or already in the basket.',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Product not found' . ($campus ? ' in selected campus' : ''),
        ]);
    }

    /**
     * @param  array<int>  $excludeIds
     */
    private function findPosProductByCode(string $codeLower, ?string $campus, array $excludeIds, bool $inStockOnly): ?Product
    {
        $query = $this->posProductBaseQuery($campus)
            ->whereRaw('LOWER(TRIM(product_code)) = ?', [$codeLower]);

        if (!empty($excludeIds)) {
            $query->whereNotIn('id', $excludeIds);
        }

        if ($inStockOnly) {
            $query->where('total_stock', '>', 0);
        }

        return $query->orderByDesc('total_stock')->orderBy('id')->first();
    }

    private function posProductBaseQuery(?string $campus)
    {
        $query = Product::query();

        if ($campus) {
            $query->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim((string) $campus))]);
        }

        if (Auth::guard('accountant')->check()) {
            $accountantCampus = Auth::guard('accountant')->user()->campus ?? null;
            if ($accountantCampus) {
                $query->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($accountantCampus))]);
            }
        }

        return $query;
    }

    /**
     * Store sale records from Point of Sale
     */
    public function storeSale(Request $request)
    {
        $validated = $request->validate([
            'buyer_name' => 'required|string|max:255',
            'payment_method' => 'required|string|max:255',
            'campus' => 'nullable|string|max:255',
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
            
            // Get logged-in user's name for "Received By" field
            $receivedBy = 'System';
            if (Auth::guard('admin')->check()) {
                $admin = Auth::guard('admin')->user();
                $receivedBy = $admin->name ?? 'Admin';
            } elseif (Auth::guard('accountant')->check()) {
                $accountant = Auth::guard('accountant')->user();
                $receivedBy = $accountant->name ?? 'Accountant';
            }
            
            // Use campus from form, fallback to user's campus
            $campus = $validated['campus'] ?? 'Main Campus';
            if (empty($campus) || $campus === 'Select Campus') {
                if (Auth::guard('admin')->check()) {
                    $campus = Auth::guard('admin')->user()->admin_of ?? 'Main Campus';
                } elseif (Auth::guard('accountant')->check()) {
                    $campus = Auth::guard('accountant')->user()->campus ?? 'Main Campus';
                } else {
                    $campus = 'Main Campus';
                }
            }

            if (Auth::guard('accountant')->check()) {
                $accountantCampus = Auth::guard('accountant')->user()->campus ?? null;
                if ($accountantCampus) {
                    $campus = $accountantCampus;
                }
            }

            $qtyByProductId = [];
            foreach ($validated['items'] as $item) {
                $pid = (int) $item['product_id'];
                $qtyByProductId[$pid] = ($qtyByProductId[$pid] ?? 0) + (int) $item['quantity'];
            }

            $savedRecords = [];
            foreach ($validated['items'] as $item) {
                $product = Product::where('id', $item['product_id'])->lockForUpdate()->first();
                if (!$product) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'Product not found for sale.',
                    ], 422);
                }

                $availableStock = (int) ($product->total_stock ?? 0);
                $requestedQty = (int) ($qtyByProductId[(int) $item['product_id']] ?? $item['quantity']);
                if ($availableStock < $requestedQty) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'Insufficient stock for ' . ($product->product_name ?? 'product') . '. Available: ' . $availableStock,
                    ], 422);
                }
                
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
                    'received_by' => $receivedBy,
                ]);
                
                $savedRecords[] = $saleRecord->id;

                // Update product stock
                $product->total_stock = $availableStock - (int) $item['quantity'];
                $product->save();
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

