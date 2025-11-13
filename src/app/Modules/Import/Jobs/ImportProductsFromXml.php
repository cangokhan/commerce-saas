<?php

namespace App\Modules\Import\Jobs;

use App\Modules\Product\Models\Product;
use App\Modules\Store\Models\Store;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ImportProductsFromXml implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 600; // 10 minutes

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $xmlUrl,
        public ?int $tenantId = null
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info('XML Import started', ['url' => $this->xmlUrl, 'tenant_id' => $this->tenantId]);

            // Fetch XML from URL
            $response = Http::timeout(300)->get($this->xmlUrl);

            if (!$response->successful()) {
                throw new \Exception('Failed to fetch XML: HTTP ' . $response->status());
            }

            $xmlContent = $response->body();

            // Parse XML
            $xml = simplexml_load_string($xmlContent);

            if ($xml === false) {
                throw new \Exception('Failed to parse XML');
            }

            // Convert XML to array for easier processing
            $products = $this->parseXmlToArray($xml);

            $imported = 0;
            $updated = 0;
            $errors = 0;

            foreach ($products as $productData) {
                try {
                    $result = $this->importProduct($productData);
                    if ($result === 'created') {
                        $imported++;
                    } elseif ($result === 'updated') {
                        $updated++;
                    }
                } catch (\Exception $e) {
                    $errors++;
                    Log::error('Product import error', [
                        'product' => $productData,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            Log::info('XML Import completed', [
                'imported' => $imported,
                'updated' => $updated,
                'errors' => $errors,
                'total' => count($products)
            ]);

        } catch (\Exception $e) {
            Log::error('XML Import failed', [
                'url' => $this->xmlUrl,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e; // Re-throw to trigger retry
        }
    }

    /**
     * Parse XML to array
     */
    private function parseXmlToArray($xml): array
    {
        $products = [];

        // XML yapısına göre parse et
        // Örnek XML yapısı:
        // <products>
        //   <product>
        //     <name>Product Name</name>
        //     <sku>SKU123</sku>
        //     <price>99.99</price>
        //     <description>Description</description>
        //     <store_id>1</store_id>
        //     <category_id>2</category_id>
        //   </product>
        // </products>

        if (isset($xml->product)) {
            foreach ($xml->product as $product) {
                $products[] = [
                    'name' => (string) $product->name,
                    'sku' => (string) ($product->sku ?? ''),
                    'description' => (string) ($product->description ?? ''),
                    'short_description' => (string) ($product->short_description ?? ''),
                    'price' => (float) ($product->price ?? 0),
                    'compare_price' => isset($product->compare_price) ? (float) $product->compare_price : null,
                    'cost_price' => isset($product->cost_price) ? (float) $product->cost_price : null,
                    'stock_quantity' => (int) ($product->stock_quantity ?? 0),
                    'stock_status' => (string) ($product->stock_status ?? 'in_stock'),
                    'weight' => isset($product->weight) ? (float) $product->weight : null,
                    'dimensions' => (string) ($product->dimensions ?? ''),
                    'images' => $this->parseImages($product),
                    'is_active' => isset($product->is_active) ? (bool) $product->is_active : true,
                    'is_featured' => isset($product->is_featured) ? (bool) $product->is_featured : false,
                    'meta_title' => (string) ($product->meta_title ?? ''),
                    'meta_description' => (string) ($product->meta_description ?? ''),
                    'store_id' => (int) ($product->store_id ?? 0),
                    'category_id' => isset($product->category_id) ? (int) $product->category_id : null,
                ];
            }
        }

        return $products;
    }

    /**
     * Parse images from XML
     */
    private function parseImages($product): array
    {
        $images = [];

        if (isset($product->images)) {
            if (isset($product->images->image)) {
                foreach ($product->images->image as $image) {
                    $images[] = (string) $image;
                }
            } elseif (is_string($product->images)) {
                // Comma separated images
                $images = array_map('trim', explode(',', (string) $product->images));
            }
        }

        return $images;
    }

    /**
     * Import or update product
     */
    private function importProduct(array $productData): string
    {
        // Validate store_id
        if (empty($productData['store_id'])) {
            throw new \Exception('Store ID is required');
        }

        $store = Store::find($productData['store_id']);
        if (!$store) {
            throw new \Exception('Store not found: ' . $productData['store_id']);
        }

        // Set tenant_id
        $tenantId = $this->tenantId ?? $store->tenant_id;

        // Find existing product by SKU or create new
        $product = null;
        if (!empty($productData['sku'])) {
            $product = Product::where('sku', $productData['sku'])
                ->where('tenant_id', $tenantId)
                ->first();
        }

        // Generate slug
        $slug = Str::slug($productData['name']);
        $baseSlug = $slug;
        $counter = 1;
        
        while (Product::where('slug', $slug)
            ->where('tenant_id', $tenantId)
            ->when($product, fn($q) => $q->where('id', '!=', $product->id))
            ->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        // Generate SKU if not provided
        if (empty($productData['sku'])) {
            $productData['sku'] = 'PRD-' . strtoupper(Str::random(8));
            while (Product::where('sku', $productData['sku'])->exists()) {
                $productData['sku'] = 'PRD-' . strtoupper(Str::random(8));
            }
        }

        if ($product) {
            // Update existing product
            $product->update([
                'name' => $productData['name'],
                'slug' => $slug,
                'description' => $productData['description'],
                'short_description' => $productData['short_description'],
                'price' => $productData['price'],
                'compare_price' => $productData['compare_price'],
                'cost_price' => $productData['cost_price'],
                'stock_quantity' => $productData['stock_quantity'],
                'stock_status' => $productData['stock_status'],
                'weight' => $productData['weight'],
                'dimensions' => $productData['dimensions'],
                'images' => $productData['images'],
                'is_active' => $productData['is_active'],
                'is_featured' => $productData['is_featured'],
                'meta_title' => $productData['meta_title'],
                'meta_description' => $productData['meta_description'],
                'category_id' => $productData['category_id'],
            ]);

            return 'updated';
        } else {
            // Create new product
            Product::create([
                'name' => $productData['name'],
                'slug' => $slug,
                'sku' => $productData['sku'],
                'description' => $productData['description'],
                'short_description' => $productData['short_description'],
                'price' => $productData['price'],
                'compare_price' => $productData['compare_price'],
                'cost_price' => $productData['cost_price'],
                'stock_quantity' => $productData['stock_quantity'],
                'stock_status' => $productData['stock_status'],
                'weight' => $productData['weight'],
                'dimensions' => $productData['dimensions'],
                'images' => $productData['images'],
                'is_active' => $productData['is_active'],
                'is_featured' => $productData['is_featured'],
                'meta_title' => $productData['meta_title'],
                'meta_description' => $productData['meta_description'],
                'tenant_id' => $tenantId,
                'store_id' => $productData['store_id'],
                'category_id' => $productData['category_id'],
            ]);

            return 'created';
        }
    }
}
