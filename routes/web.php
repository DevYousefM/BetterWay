<?php

use App\Http\Controllers\Web\WebController;
use App\Http\Controllers\Admin\Client\ClientController;
use App\V1\Client\Client;
use Illuminate\Support\Facades\Route;

Route::get('/', [WebController::class, 'Home'])->name('home');
Route::get('/testing', function () {
    return $clients = Client::with(['referrals', 'visits' => function ($query) {
        $query->where('ClientBrandProductStatus', 'USED');
    }])
        ->whereHas('referrals', function ($query) {
            $query->whereNotNull('IDReferral');
        })
        ->get();
});
