<?php

namespace App\Modules\Import\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Import\Jobs\ImportProductsFromXml;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ImportController extends Controller
{
    /**
     * Trigger XML import manually
     */
    public function importXml(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'xml_url' => ['required', 'url'],
            'tenant_id' => ['nullable', 'exists:tenants,id'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();

        // Check permission
        if (!$user->hasPermissionTo('import products')) {
            return response()->json([
                'message' => 'You do not have permission to import products',
            ], 403);
        }

        // Use tenant_id from user if not provided
        $tenantId = $request->tenant_id ?? $user->tenant_id;

        // Dispatch job
        ImportProductsFromXml::dispatch($request->xml_url, $tenantId);

        return response()->json([
            'message' => 'XML import job has been queued',
            'xml_url' => $request->xml_url,
            'tenant_id' => $tenantId,
        ], 202);
    }

    /**
     * Get import status
     */
    public function status(Request $request): JsonResponse
    {
        // This would typically check queue status or import logs
        // For now, return basic info
        
        return response()->json([
            'message' => 'Import status endpoint',
            'note' => 'Check queue worker logs for detailed status',
        ]);
    }
}
