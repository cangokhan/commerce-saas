<?php

namespace App\Modules\Import\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use SimpleXMLElement;

class XmlParserService
{
    /**
     * Fetch XML from remote URL
     */
    public function fetchXml(string $url): ?string
    {
        try {
            $response = Http::timeout(30)->get($url);
            
            if ($response->successful()) {
                return $response->body();
            }
            
            Log::error('Failed to fetch XML', [
                'url' => $url,
                'status' => $response->status(),
            ]);
            
            return null;
        } catch (\Exception $e) {
            Log::error('Exception while fetching XML', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);
            
            return null;
        }
    }

    /**
     * Parse XML string to array
     */
    public function parseXml(string $xmlString): ?array
    {
        try {
            // Suppress warnings for malformed XML
            libxml_use_internal_errors(true);
            
            $xml = simplexml_load_string($xmlString);
            
            if ($xml === false) {
                $errors = libxml_get_errors();
                libxml_clear_errors();
                
                Log::error('Failed to parse XML', [
                    'errors' => array_map(fn($error) => $error->message, $errors),
                ]);
                
                return null;
            }
            
            // Convert XML to array
            $array = $this->xmlToArray($xml);
            
            return $array;
        } catch (\Exception $e) {
            Log::error('Exception while parsing XML', [
                'error' => $e->getMessage(),
            ]);
            
            return null;
        }
    }

    /**
     * Convert SimpleXMLElement to array
     */
    protected function xmlToArray(SimpleXMLElement $xml): array
    {
        $array = [];
        
        // Get attributes
        foreach ($xml->attributes() as $key => $value) {
            $array['@' . $key] = (string) $value;
        }
        
        // Get children
        foreach ($xml->children() as $key => $child) {
            $value = (string) $child;
            
            if (count($child->children()) > 0) {
                $value = $this->xmlToArray($child);
            }
            
            // Handle multiple children with same name
            if (isset($array[$key])) {
                if (!is_array($array[$key]) || !isset($array[$key][0])) {
                    $array[$key] = [$array[$key]];
                }
                $array[$key][] = $value;
            } else {
                $array[$key] = $value;
            }
        }
        
        // Get text content if no children
        if (count($xml->children()) === 0) {
            $text = trim((string) $xml);
            if ($text !== '') {
                if (count($array) > 0) {
                    $array['_text'] = $text;
                } else {
                    return $text;
                }
            }
        }
        
        return $array;
    }

    /**
     * Extract products from XML array
     * 
     * This method expects XML structure like:
     * <products>
     *   <product>
     *     <name>...</name>
     *     <sku>...</sku>
     *     <price>...</price>
     *     ...
     *   </product>
     * </products>
     */
    public function extractProducts(array $xmlArray): array
    {
        $products = [];
        
        // Try different possible XML structures
        if (isset($xmlArray['product'])) {
            $products = is_array($xmlArray['product']) && isset($xmlArray['product'][0])
                ? $xmlArray['product']
                : [$xmlArray['product']];
        } elseif (isset($xmlArray['products']['product'])) {
            $products = is_array($xmlArray['products']['product']) && isset($xmlArray['products']['product'][0])
                ? $xmlArray['products']['product']
                : [$xmlArray['products']['product']];
        } elseif (isset($xmlArray['items']['item'])) {
            $products = is_array($xmlArray['items']['item']) && isset($xmlArray['items']['item'][0])
                ? $xmlArray['items']['item']
                : [$xmlArray['items']['item']];
        }
        
        return $products;
    }

    /**
     * Normalize product data from XML
     */
    public function normalizeProduct(array $xmlProduct): array
    {
        return [
            'name' => $xmlProduct['name'] ?? $xmlProduct['title'] ?? $xmlProduct['product_name'] ?? null,
            'sku' => $xmlProduct['sku'] ?? $xmlProduct['code'] ?? $xmlProduct['product_code'] ?? null,
            'description' => $xmlProduct['description'] ?? $xmlProduct['long_description'] ?? null,
            'short_description' => $xmlProduct['short_description'] ?? $xmlProduct['summary'] ?? null,
            'price' => $this->parsePrice($xmlProduct['price'] ?? $xmlProduct['cost'] ?? $xmlProduct['amount'] ?? 0),
            'compare_price' => isset($xmlProduct['compare_price']) ? $this->parsePrice($xmlProduct['compare_price']) : null,
            'cost_price' => isset($xmlProduct['cost_price']) ? $this->parsePrice($xmlProduct['cost_price']) : null,
            'stock_quantity' => isset($xmlProduct['stock_quantity']) ? (int) $xmlProduct['stock_quantity'] : (isset($xmlProduct['stock']) ? (int) $xmlProduct['stock'] : 0),
            'stock_status' => $this->parseStockStatus($xmlProduct['stock_status'] ?? $xmlProduct['availability'] ?? null),
            'weight' => isset($xmlProduct['weight']) ? (float) $xmlProduct['weight'] : null,
            'images' => $this->parseImages($xmlProduct),
            'is_active' => $this->parseBoolean($xmlProduct['is_active'] ?? $xmlProduct['active'] ?? true),
            'is_featured' => $this->parseBoolean($xmlProduct['is_featured'] ?? $xmlProduct['featured'] ?? false),
            'meta_title' => $xmlProduct['meta_title'] ?? null,
            'meta_description' => $xmlProduct['meta_description'] ?? null,
        ];
    }

    /**
     * Parse price value
     */
    protected function parsePrice($value): float
    {
        if (is_numeric($value)) {
            return (float) $value;
        }
        
        // Remove currency symbols and spaces
        $cleaned = preg_replace('/[^\d.,]/', '', (string) $value);
        $cleaned = str_replace(',', '.', $cleaned);
        
        return (float) $cleaned;
    }

    /**
     * Parse stock status
     */
    protected function parseStockStatus($value): string
    {
        if (!$value) {
            return 'in_stock';
        }
        
        $value = strtolower((string) $value);
        
        if (in_array($value, ['in_stock', 'out_of_stock', 'backorder'])) {
            return $value;
        }
        
        if (in_array($value, ['in stock', 'available', 'yes', '1', 'true'])) {
            return 'in_stock';
        }
        
        if (in_array($value, ['out of stock', 'unavailable', 'no', '0', 'false'])) {
            return 'out_of_stock';
        }
        
        return 'in_stock';
    }

    /**
     * Parse images from XML
     */
    protected function parseImages(array $xmlProduct): array
    {
        $images = [];
        
        // Try different possible image fields
        if (isset($xmlProduct['image'])) {
            $images = is_array($xmlProduct['image']) ? $xmlProduct['image'] : [$xmlProduct['image']];
        } elseif (isset($xmlProduct['images']['image'])) {
            $images = is_array($xmlProduct['images']['image']) ? $xmlProduct['images']['image'] : [$xmlProduct['images']['image']];
        } elseif (isset($xmlProduct['gallery']['image'])) {
            $images = is_array($xmlProduct['gallery']['image']) ? $xmlProduct['gallery']['image'] : [$xmlProduct['gallery']['image']];
        }
        
        // Filter out empty values
        return array_filter(array_map('trim', $images));
    }

    /**
     * Parse boolean value
     */
    protected function parseBoolean($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        
        $value = strtolower((string) $value);
        
        return in_array($value, ['1', 'true', 'yes', 'on', 'active', 'enabled']);
    }
}

