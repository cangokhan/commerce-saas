<?php

namespace App\Modules\Import\Services;

use App\Modules\Product\Models\Product;
use App\Modules\Store\Models\Store;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ProductImportService
{
    protected XmlParserService $xmlParser;

    public function __construct(XmlParserService $xmlParser)
    {
        $this->xmlParser = $xmlParser;
    }

    /**
     * Import products from XML URL
     */
    public function importFromUrl(string $xmlUrl, int $storeId, ?int $tenantId = null): array
    {
        $results = [
            'success' => 0,
            'failed' => 0,
            'skipped' => 0,
            'errors' => [],
        ];

        // Fetch XML
        $xmlString = $this->xmlParser->fetchXml($xmlUrl);
        
        if (!$xmlString) {
            $results['errors'][] = 'Failed to fetch XML from URL';
            return $results;
        }

        // Parse XML
        $xmlArray = $this->xmlParser->parseXml($xmlString);
        
        if (!$xmlArray) {
            $results['errors'][] = 'Failed to parse XML';
            return $results;
        }

        // Extract products
        $products = $this->xmlParser->extractProducts($xmlArray);
        
        if (empty($products)) {
            $results['errors'][] = 'No products found in XML';
            return $results;
        }

        // Get store
        $store = Store::findOrFail($storeId);
        
        if (!$tenantId) {
            $tenantId = $store->tenant_id;
        }

        // Import each product
        foreach ($products as $xmlProduct) {
            try {
                $normalized = $this->xmlParser->normalizeProduct($xmlProduct);
                
                if (!$normalized['name']) {
                    $results['skipped']++;
                    $results['errors'][] = 'Product skipped: missing name';
                    continue;
                }

                $result = $this->importProduct($normalized, $storeId, $tenantId);
                
                if ($result['success']) {
                    $results['success']++;
                } else {
                    $results['failed']++;
                    $results['errors'][] = $result['error'];
                }
            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = 'Error importing product: ' . $e->getMessage();
                Log::error('Product import error', [
                    'error' => $e->getMessage(),
                    'product' => $xmlProduct,
                ]);
            }
        }

        return $results;
    }

    /**
     * Import single product
     */
    protected function importProduct(array $data, int $storeId, int $tenantId): array
    {
        try {
            // Generate slug
            $slug = Str::slug($data['name']);
            $baseSlug = $slug;
            $counter = 1;
            
            while (Product::where('slug', $slug)->exists()) {
                $slug = $baseSlug . '-' . $counter;
                $counter++;
            }

            // Generate SKU if not provided
            $sku = $data['sku'];
            if (!$sku) {
                $sku = 'PRD-' . strtoupper(Str::random(8));
                while (Product::where('sku', $sku)->exists()) {
                    $sku = 'PRD-' . strtoupper(Str::random(8));
                }
            }

            // Check if product with same SKU exists
            $existingProduct = Product::where('sku', $sku)
                ->where('store_id', $storeId)
                ->first();

            if ($existingProduct) {
                // Update existing product
                $existingProduct->update([
                    'name' => $data['name'],
                    'slug' => $slug,
                    'description' => $data['description'],
                    'short_description' => $data['short_description'],
                    'price' => $data['price'],
                    'compare_price' => $data['compare_price'],
                    'cost_price' => $data['cost_price'],
                    'stock_quantity' => $data['stock_quantity'],
                    'stock_status' => $data['stock_status'],
                    'weight' => $data['weight'],
                    'images' => $data['images'],
                    'is_active' => $data['is_active'],
                    'is_featured' => $data['is_featured'],
                    'meta_title' => $data['meta_title'],
                    'meta_description' => $data['meta_description'],
                ]);

                return [
                    'success' => true,
                    'action' => 'updated',
                    'product_id' => $existingProduct->id,
                ];
            }

            // Create new product
            $product = Product::create([
                'name' => $data['name'],
                'slug' => $slug,
                'sku' => $sku,
                'description' => $data['description'],
                'short_description' => $data['short_description'],
                'price' => $data['price'],
                'compare_price' => $data['compare_price'],
                'cost_price' => $data['cost_price'],
                'stock_quantity' => $data['stock_quantity'],
                'stock_status' => $data['stock_status'],
                'weight' => $data['weight'],
                'images' => $data['images'],
                'is_active' => $data['is_active'],
                'is_featured' => $data['is_featured'],
                'meta_title' => $data['meta_title'],
                'meta_description' => $data['meta_description'],
                'tenant_id' => $tenantId,
                'store_id' => $storeId,
            ]);

            return [
                'success' => true,
                'action' => 'created',
                'product_id' => $product->id,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}

