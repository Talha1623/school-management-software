<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StockCategory;
use App\Models\ClassModel;
use App\Models\Section;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Validator;

class BulkProductController extends Controller
{
    /**
     * Display the bulk products upload page.
     */
    public function index(): View
    {
        // Get categories for reference
        $categories = StockCategory::whereNotNull('category_name')->distinct()->pluck('category_name')->sort()->values();

        // Get campuses for reference
        $campusesFromClasses = ClassModel::whereNotNull('campus')->distinct()->pluck('campus');
        $campusesFromSections = Section::whereNotNull('campus')->distinct()->pluck('campus');
        $campuses = $campusesFromClasses->merge($campusesFromSections)->unique()->sort()->values();
        
        if ($campuses->isEmpty()) {
            $campuses = collect(['Main Campus', 'Branch Campus 1', 'Branch Campus 2']);
        }

        return view('stock.add-bulk-products', compact('categories', 'campuses'));
    }

    /**
     * Handle bulk product upload from CSV/Excel file.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:10240'], // 10MB max
        ]);

        $file = $request->file('file');
        $filePath = $file->getRealPath();
        
        $successCount = 0;
        $errorCount = 0;
        $errors = [];

        // Read CSV file
        if (($handle = fopen($filePath, 'r')) !== false) {
            // Skip header row
            $header = fgetcsv($handle);
            
            $rowNumber = 1;
            while (($data = fgetcsv($handle)) !== false) {
                $rowNumber++;
                
                // Expected format: Product Name, Product Code, Campus, Category, Purchase Price, Sale Price, Total Stock
                if (count($data) < 7) {
                    $errors[] = "Row {$rowNumber}: Insufficient columns. Expected 7 columns.";
                    $errorCount++;
                    continue;
                }

                $productData = [
                    'product_name' => trim($data[0]),
                    'product_code' => trim($data[1]),
                    'campus' => trim($data[2]),
                    'category' => trim($data[3]),
                    'purchase_price' => trim($data[4]),
                    'sale_price' => trim($data[5]),
                    'total_stock' => trim($data[6]),
                ];

                // Validate row data
                $validator = Validator::make($productData, [
                    'product_name' => ['required', 'string', 'max:255'],
                    'product_code' => ['nullable', 'string', 'max:255'],
                    'campus' => ['required', 'string', 'max:255'],
                    'category' => ['required', 'string', 'max:255'],
                    'purchase_price' => ['required', 'numeric', 'min:0'],
                    'sale_price' => ['required', 'numeric', 'min:0'],
                    'total_stock' => ['required', 'integer', 'min:0'],
                ]);

                if ($validator->fails()) {
                    $errors[] = "Row {$rowNumber}: " . implode(', ', $validator->errors()->all());
                    $errorCount++;
                    continue;
                }

                try {
                    // Ensure category exists for the campus
                    $categoryName = $productData['category'];
                    $campusName = $productData['campus'];
                    $existingCategory = StockCategory::whereRaw('LOWER(TRIM(category_name)) = ?', [strtolower(trim($categoryName))])
                        ->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campusName))])
                        ->first();

                    if (!$existingCategory) {
                        StockCategory::create([
                            'category_name' => $categoryName,
                            'description' => null,
                            'campus' => $campusName,
                        ]);
                    }

                    Product::create($productData);
                    $successCount++;
                } catch (\Exception $e) {
                    $errors[] = "Row {$rowNumber}: " . $e->getMessage();
                    $errorCount++;
                }
            }
            
            fclose($handle);
        }

        $message = "Bulk upload completed! Success: {$successCount}, Errors: {$errorCount}";
        
        if ($errorCount > 0 && count($errors) > 0) {
            $message .= "\n\nErrors:\n" . implode("\n", array_slice($errors, 0, 10));
            if (count($errors) > 10) {
                $message .= "\n... and " . (count($errors) - 10) . " more errors.";
            }
        }

        return redirect()
            ->route('stock.add-bulk-products')
            ->with('success', $message)
            ->with('errors', $errors);
    }

    /**
     * Download sample CSV template.
     */
    public function downloadTemplate()
    {
        $filename = 'bulk_products_template.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() {
            $file = fopen('php://output', 'w');
            
            // Add BOM for UTF-8
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Header row
            fputcsv($file, ['Product Name', 'Product Code', 'Campus', 'Category', 'Purchase Price', 'Sale Price', 'Total Stock']);
            
            // Sample data row
            fputcsv($file, ['Sample Product 1', 'PRD-001', 'Main Campus', 'Electronics', '100.00', '150.00', '50']);
            fputcsv($file, ['Sample Product 2', 'PRD-002', 'Main Campus', 'Stationery', '20.00', '30.00', '100']);
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}

