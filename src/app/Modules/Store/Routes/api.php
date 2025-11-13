<?php

use App\Modules\Store\Controllers\StoreController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Store API Routes
|--------------------------------------------------------------------------
|
| Store management routes
|
*/

Route::prefix('api/stores')->middleware(['auth:sanctum', 'tenant.scope'])->group(function () {
    Route::get('/', [StoreController::class, 'index']);
    Route::post('/', [StoreController::class, 'store']);
    Route::get('/{store}', [StoreController::class, 'show']);
    Route::put('/{store}', [StoreController::class, 'update']);
    Route::patch('/{store}', [StoreController::class, 'update']);
    Route::delete('/{store}', [StoreController::class, 'destroy'])->middleware('role:superadmin');
});

