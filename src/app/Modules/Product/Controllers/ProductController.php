<?php

namespace App\Modules\Product\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Product\Models\Product;
use App\Modules\Product\Requests\ProductRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    /**
     * Display a listing of products.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Product::with(['store', 'category', 'tenant']);

        // Tenant scope
        if ($request->user()->tenant_id) {
            $query->where('tenant_id', $request->user()->tenant_id);
        }

        // Store scope - vendors can only see products from their stores
        if ($request->user()->hasRole('vendor')) {
            $storeIds = $request->user()->stores->pluck('id');
            $query->whereIn('store_id', $storeIds);
        }

        // Filter by store_id
        if ($request->has('store_id')) {
            $query->where('store_id', $request->store_id);
        }

        // Filter by category_id
        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Filter by is_active
        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        // Filter by is_featured
        if ($request->has('is_featured')) {
            $query->where('is_featured', $request->is_featured);
        }

        // Search by name or SKU
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        // Sort
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $products = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'products' => $products,
        ]);
    }

    /**
     * Store a newly created product.
     */
    public function store(ProductRequest $request): JsonResponse
    {
        $user = $request->user();

        // Generate slug from name
        $slug = Str::slug($request->name);
        $baseSlug = $slug;
        $counter = 1;
        
        while (Product::where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        // Generate SKU if not provided
        $sku = $request->sku;
        if (!$sku) {
            $sku = 'PRD-' . strtoupper(Str::random(8));
            while (Product::where('sku', $sku)->exists()) {
                $sku = 'PRD-' . strtoupper(Str::random(8));
            }
        }

        // Validate store_id - vendor can only create products in their stores
        $storeId = $request->store_id;
        if ($user->hasRole('vendor')) {
            $userStoreIds = $user->stores->pluck('id')->toArray();
            if (!in_array($storeId, $userStoreIds)) {
                return response()->json([
                    'message' => 'You can only create products in your own stores',
                ], 403);
            }
        }

        $product = Product::create([
            'name' => $request->name,
            'slug' => $slug,
            'sku' => $sku,
            'description' => $request->description,
            'short_description' => $request->short_description,
            'price' => $request->price,
            'compare_price' => $request->compare_price,
            'cost_price' => $request->cost_price,
            'stock_quantity' => $request->stock_quantity ?? 0,
            'stock_status' => $request->stock_status ?? 'in_stock',
            'weight' => $request->weight,
            'dimensions' => $request->dimensions,
            'images' => $request->images ?? [],
            'is_active' => $request->is_active ?? true,
            'is_featured' => $request->is_featured ?? false,
            'meta_title' => $request->meta_title,
            'meta_description' => $request->meta_description,
            'tenant_id' => $user->tenant_id ?? $request->tenant_id,
            'store_id' => $storeId,
            'category_id' => $request->category_id,
        ]);

        return response()->json([
            'message' => 'Product created successfully',
            'product' => $product->load(['store', 'category', 'tenant']),
        ], 201);
    }

    /**
     * Display the specified product.
     */
    public function show(Request $request, Product $product): JsonResponse
    {
        // Check authorization
        $user = $request->user();
        
        if ($user->tenant_id && $product->tenant_id !== $user->tenant_id) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        if ($user->hasRole('vendor')) {
            $userStoreIds = $user->stores->pluck('id')->toArray();
            if (!in_array($product->store_id, $userStoreIds)) {
                return response()->json([
                    'message' => 'Unauthorized',
                ], 403);
            }
        }

        return response()->json([
            'product' => $product->load(['store', 'category', 'tenant']),
        ]);
    }

    /**
     * Update the specified product.
     */
    public function update(ProductRequest $request, Product $product): JsonResponse
    {
        // Check authorization
        $user = $request->user();
        
        if ($user->tenant_id && $product->tenant_id !== $user->tenant_id) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        if ($user->hasRole('vendor')) {
            $userStoreIds = $user->stores->pluck('id')->toArray();
            if (!in_array($product->store_id, $userStoreIds)) {
                return response()->json([
                    'message' => 'Unauthorized',
                ], 403);
            }
        }

        // Update slug if name changed
        if ($request->has('name') && $request->name !== $product->name) {
            $slug = Str::slug($request->name);
            $baseSlug = $slug;
            $counter = 1;
            
            while (Product::where('slug', $slug)->where('id', '!=', $product->id)->exists()) {
                $slug = $baseSlug . '-' . $counter;
                $counter++;
            }
            
            $product->slug = $slug;
        }

        $product->update($request->validated());

        return response()->json([
            'message' => 'Product updated successfully',
            'product' => $product->load(['store', 'category', 'tenant']),
        ]);
    }

    /**
     * Remove the specified product.
     */
    public function destroy(Request $request, Product $product): JsonResponse
    {
        // Check authorization
        $user = $request->user();
        
        if ($user->tenant_id && $product->tenant_id !== $user->tenant_id) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        if ($user->hasRole('vendor')) {
            $userStoreIds = $user->stores->pluck('id')->toArray();
            if (!in_array($product->store_id, $userStoreIds)) {
                return response()->json([
                    'message' => 'Unauthorized',
                ], 403);
            }
        }

        $product->delete();

        return response()->json([
            'message' => 'Product deleted successfully',
        ]);
    }
}
