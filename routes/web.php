<?php

use App\Http\Controllers\Web\WebController;
use App\Http\Controllers\Admin\Client\ClientController;
use App\V1\Client\Client;
use Illuminate\Support\Facades\Route;

Route::get('/', [WebController::class, 'Home'])->name('home');
// Route::get('/tst', function () {
//      sendFirebaseNotification(Client::find(402), [], 'test', 'test');
//     return 'here';
// })->name('home');
