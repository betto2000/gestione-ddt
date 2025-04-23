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

    // Route accessibili da dispositivi certificati
    Route::middleware('verified.device')->group(function () {
        // DDT e dettagli
        Route::post('/scan-qr', [DdtController::class, 'getDdtByQrCode']);
        Route::get('/documents/{saleDocId}', [DdtController::class, 'getDocumentDetail']);
        Route::get('/documents/{saleDocId}/details/{line?}', [DdtController::class, 'getDocumentDetail']);
        Route::put('/documents/update-quantity', [DdtController::class, 'updateQuantity']);
        Route::get('/documents/{saleDocId}/next-detail/{currentLine}', [DdtController::class, 'getNextDetail']);
        Route::get('/documents/{saleDocId}/summary', [DdtController::class, 'getSummary']);
        Route::post('/documents/{saleDocId}/confirm', [DdtController::class, 'confirmDocument']);

        // API di supporto
        Route::get('/document-types', [ApiController::class, 'getDocumentTypes']);
        Route::get('/customers', [ApiController::class, 'getCustomers']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });
});
