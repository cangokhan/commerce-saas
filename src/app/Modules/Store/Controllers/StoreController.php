<?php

namespace App\Modules\Store\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Store\Models\Store;
use App\Modules\Store\Requests\StoreRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class StoreController extends Controller
{
    /**
     * Display a listing of stores.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Store::with(['tenant', 'vendor']);

        // Tenant scope - only show stores from user's tenant
        if ($request->user()->tenant_id) {
            $query->where('tenant_id', $request->user()->tenant_id);
        }

        // Vendor scope - vendors can only see their own stores
        if ($request->user()->hasRole('vendor')) {
            $query->where('vendor_id', $request->user()->id);
        }

        // Superadmin can see all stores
        // (no additional filtering)

        $stores = $query->paginate(15);

        return response()->json([
            'stores' => $stores,
        ]);
    }

    /**
     * Store a newly created store.
     */
    public function store(StoreRequest $request): JsonResponse
    {
        $user = $request->user();

        // Generate slug from name
        $slug = Str::slug($request->name);
        $baseSlug = $slug;
        $counter = 1;
        
        // Ensure unique slug
        while (Store::where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        $store = Store::create([
            'name' => $request->name,
            'slug' => $slug,
            'description' => $request->description,
            'email' => $request->email,
            'phone' => $request->phone,
            'address' => $request->address,
            'city' => $request->city,
            'state' => $request->state,
            'country' => $request->country,
            'postal_code' => $request->postal_code,
            'logo' => $request->logo,
            'is_active' => $request->is_active ?? true,
            'tenant_id' => $user->tenant_id ?? $request->tenant_id,
            'vendor_id' => $user->hasRole('vendor') ? $user->id : ($request->vendor_id ?? $user->id),
        ]);

        return response()->json([
            'message' => 'Store created successfully',
            'store' => $store->load(['tenant', 'vendor']),
        ], 201);
    }

    /**
     * Display the specified store.
     */
    public function show(Request $request, Store $store): JsonResponse
    {
        // Check authorization
        $user = $request->user();
        
        if ($user->tenant_id && $store->tenant_id !== $user->tenant_id) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        if ($user->hasRole('vendor') && $store->vendor_id !== $user->id) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        return response()->json([
            'store' => $store->load(['tenant', 'vendor']),
        ]);
    }

    /**
     * Update the specified store.
     */
    public function update(StoreRequest $request, Store $store): JsonResponse
    {
        // Check authorization
        $user = $request->user();
        
        if ($user->tenant_id && $store->tenant_id !== $user->tenant_id) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        if ($user->hasRole('vendor') && $store->vendor_id !== $user->id) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        // Update slug if name changed
        if ($request->has('name') && $request->name !== $store->name) {
            $slug = Str::slug($request->name);
            $baseSlug = $slug;
            $counter = 1;
            
            while (Store::where('slug', $slug)->where('id', '!=', $store->id)->exists()) {
                $slug = $baseSlug . '-' . $counter;
                $counter++;
            }
            
            $store->slug = $slug;
        }

        $store->update($request->validated());

        return response()->json([
            'message' => 'Store updated successfully',
            'store' => $store->load(['tenant', 'vendor']),
        ]);
    }

    /**
     * Remove the specified store.
     */
    public function destroy(Request $request, Store $store): JsonResponse
    {
        // Check authorization
        $user = $request->user();
        
        if ($user->tenant_id && $store->tenant_id !== $user->tenant_id) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        if ($user->hasRole('vendor') && $store->vendor_id !== $user->id) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        // Only superadmin can delete stores
        if (!$user->hasRole('superadmin')) {
            return response()->json([
                'message' => 'Only superadmin can delete stores',
            ], 403);
        }

        $store->delete();

        return response()->json([
            'message' => 'Store deleted successfully',
        ]);
    }
}
