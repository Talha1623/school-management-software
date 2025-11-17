<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StockCategory;
use App\Models\Campus;
use App\Models\ClassModel;
use App\Models\Section;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    /**
     * Display a listing of products.
     */
    public function index(Request $request): View
    {
        $query = Product::query();
        
        // Search functionality
        if ($request->filled('search')) {
            $search = trim($request->search);
            if (!empty($search)) {
                $searchLower = strtolower($search);
                $query->where(function($q) use ($search, $searchLower) {
                    $q->whereRaw('LOWER(product_name) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(category) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(campus) LIKE ?', ["%{$searchLower}%"]);
                });
            }
        }
        
        $perPage = $request->get('per_page', 10);
        $perPage = in_array($perPage, [10, 25, 50, 100]) ? $perPage : 10;
        
        $products = $query->orderBy('product_name')->paginate($perPage)->withQueryString();

        // Get categories for dropdown
        $categories = StockCategory::whereNotNull('category_name')->distinct()->pluck('category_name')->sort()->values();

        // Get campuses from Campus model
        $campuses = Campus::orderBy('campus_name', 'asc')->get();
        
        // If no campuses found, get from classes or sections
        if ($campuses->isEmpty()) {
            $campusesFromClasses = ClassModel::whereNotNull('campus')
                ->distinct()
                ->pluck('campus')
                ->sort()
                ->values();
            
            $campusesFromSections = Section::whereNotNull('campus')
                ->distinct()
                ->pluck('campus')
                ->sort()
                ->values();
            
            $allCampuses = $campusesFromClasses->merge($campusesFromSections)->unique()->sort()->values();
            
            // Convert to collection of objects with campus_name property
            $campuses = collect();
            foreach ($allCampuses as $campusName) {
                $campuses->push((object)['campus_name' => $campusName]);
            }
        }
        
        return view('stock.products', compact('products', 'categories', 'campuses'));
    }

    /**
     * Store a newly created product.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'product_name' => ['required', 'string', 'max:255'],
            'product_code' => ['nullable', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:255'],
            'purchase_price' => ['required', 'numeric', 'min:0'],
            'sale_price' => ['required', 'numeric', 'min:0'],
            'total_stock' => ['required', 'integer', 'min:0'],
            'campus' => ['required', 'string', 'max:255'],
        ]);

        Product::create($validated);

        return redirect()
            ->route('stock.products')
            ->with('success', 'Product created successfully!');
    }

    /**
     * Update the specified product.
     */
    public function update(Request $request, Product $product): RedirectResponse
    {
        $validated = $request->validate([
            'product_name' => ['required', 'string', 'max:255'],
            'product_code' => ['nullable', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:255'],
            'purchase_price' => ['required', 'numeric', 'min:0'],
            'sale_price' => ['required', 'numeric', 'min:0'],
            'total_stock' => ['required', 'integer', 'min:0'],
            'campus' => ['required', 'string', 'max:255'],
        ]);

        $product->update($validated);

        return redirect()
            ->route('stock.products')
            ->with('success', 'Product updated successfully!');
    }

    /**
     * Remove the specified product.
     */
    public function destroy(Product $product): RedirectResponse
    {
        $product->delete();

        return redirect()
            ->route('stock.products')
            ->with('success', 'Product deleted successfully!');
    }

    /**
     * Export products to Excel, CSV, or PDF
     */
    public function export(Request $request, string $format)
    {
        $query = Product::query();
        
        // Apply search filter if present
        if ($request->has('search') && $request->search) {
            $search = trim($request->search);
            if (!empty($search)) {
                $searchLower = strtolower($search);
                $query->where(function($q) use ($search, $searchLower) {
                    $q->whereRaw('LOWER(product_name) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(product_code) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(category) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(campus) LIKE ?', ["%{$searchLower}%"]);
                });
            }
        }
        
        $products = $query->orderBy('product_name')->get();
        
        switch ($format) {
            case 'excel':
                return $this->exportExcel($products);
            case 'csv':
                return $this->exportCSV($products);
            case 'pdf':
                return $this->exportPDF($products);
            default:
                return redirect()->route('stock.products')
                    ->with('error', 'Invalid export format!');
        }
    }

    /**
     * Export to Excel (CSV format for Excel compatibility)
     */
    private function exportExcel($products)
    {
        $filename = 'products_' . date('Y-m-d_His') . '.csv';
        
        $headers = [
            'Content-Type' => 'application/vnd.ms-excel',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($products) {
            $file = fopen('php://output', 'w');
            
            // Add BOM for UTF-8
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            fputcsv($file, ['ID', 'Product Name', 'Category', 'Purchase Price', 'Sale Price', 'Total Stock', 'Campus', 'Created At']);
            
            foreach ($products as $product) {
                fputcsv($file, [
                    $product->id,
                    $product->product_name,
                    $product->product_code ?? 'N/A',
                    $product->category,
                    $product->purchase_price,
                    $product->sale_price,
                    $product->total_stock,
                    $product->campus,
                    $product->created_at->format('Y-m-d H:i:s'),
                ]);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export to CSV
     */
    private function exportCSV($products)
    {
        $filename = 'products_' . date('Y-m-d_His') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($products) {
            $file = fopen('php://output', 'w');
            
            fputcsv($file, ['ID', 'Product Name', 'Category', 'Purchase Price', 'Sale Price', 'Total Stock', 'Campus', 'Created At']);
            
            foreach ($products as $product) {
                fputcsv($file, [
                    $product->id,
                    $product->product_name,
                    $product->product_code ?? 'N/A',
                    $product->category,
                    $product->purchase_price,
                    $product->sale_price,
                    $product->total_stock,
                    $product->campus,
                    $product->created_at->format('Y-m-d H:i:s'),
                ]);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export to PDF
     */
    private function exportPDF($products)
    {
        $html = view('stock.products-pdf', compact('products'))->render();
        
        return response($html)
            ->header('Content-Type', 'text/html');
    }
}

