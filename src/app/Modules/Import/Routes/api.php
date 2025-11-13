<?php

use App\Modules\Import\Controllers\ImportController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Import API Routes
|--------------------------------------------------------------------------
|
| Import management routes
|
*/

Route::prefix('api/import')->middleware(['auth:sanctum', 'tenant.scope', 'role:superadmin,vendor'])->group(function () {
    Route::post('/xml', [ImportController::class, 'importXml']);
    Route::get('/status', [ImportController::class, 'status']);
});
