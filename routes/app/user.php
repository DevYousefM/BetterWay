<?php

use App\Http\Controllers\App\Brand\BrandController;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('userapi')->prefix('user')->group(function () {
    Route::post('/login', [BrandController::class, 'UserLogin']);
    Route::get('/logout',[BrandController::class, 'UserLogout']);
    Route::post('/language/change', [BrandController::class, 'ChangeLanguage']);

    Route::post('/qrcode/scan', [BrandController::class, 'QRCodeScan']);
    Route::post('/qrcode/use', [BrandController::class, 'QRCodeUse']);
    Route::post('/client/products', [BrandController::class, 'ClientBrandProducts']);
});
