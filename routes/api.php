<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DdtController;
use App\Http\Controllers\ApiController;


Route::prefix('api')->group(function () {
    // Route pubbliche
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/check-device', [AuthController::class, 'checkDevice']);

    // Rotte protette da autenticazione (Sanctum o dispositivo)
    Route::middleware(['device.token'])->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);

        // API DDT
        Route::post('/scan-qr', [DdtController::class, 'getDdtByQrCode']);
        Route::get('/documents/{saleDocId}', [DdtController::class, 'getDocumentDetail']);
        Route::get('/documents/{saleDocId}/details/{line?}', [DdtController::class, 'getDocumentDetail']);
        Route::get('/documents/{saleDocId}/next-detail/{currentLine}', [DdtController::class, 'getNextDetail']);
        Route::post('/documents/update-quantity', [DdtController::class, 'updateQuantity']);
        Route::get('/documents/{saleDocId}/summary', [DdtController::class, 'getSummary']);
        Route::post('/documents/{saleDocId}/confirm', [DdtController::class, 'confirmDocument']);
        Route::post('/documents/{saleDocId}/packages', [DdtController::class, 'savePackages']);
        Route::get('/documents/{saleDocId}/registered-rows', [DdtController::class, 'getRegisteredRows']);
        Route::delete('/documents/{saleDocId}/registered-rows', [DdtController::class, 'deleteRegisteredRows']);

        Route::get('/document-types', [ApiController::class, 'getDocumentTypes']);
        Route::get('/customers', [ApiController::class, 'getCustomers']);
        Route::get('/packages', [ApiController::class, 'getPackages']);
    });
});
