<?php

use App\Http\Controllers\Web\WebController;
use App\Http\Controllers\Admin\Client\ClientController;
use App\Http\Resources\Admin\ClientAgencyResource;
use App\Http\Resources\Admin\ClientResource;
use App\V1\Client\Client;
use Illuminate\Support\Facades\Route;

Route::get('/', [WebController::class, 'Home'])->name('home');
// Route::get('/tst', function () {
//     return ClientAgencyResource::collection(Client::where("ClientPhone", '+201234566789')->first()->ClientAgencies);
// })->name('home');
