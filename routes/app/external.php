<?php

use App\Http\Controllers\External\GhazalController;
use App\Http\Controllers\External\ChatBotController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('externalapi')->prefix('external')->group(function () {
    Route::post('/payment/request', [GhazalController::class, 'RequestPayment']);
    Route::post('/payment/callback', [GhazalController::class, 'PaymentCallBack']);
    Route::get('/payment/invoice/{id}', [GhazalController::class, 'PaymentInvoice']);
});