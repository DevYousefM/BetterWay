<?php

use App\Http\Controllers\Web\WebController;
use App\Http\Controllers\Admin\Client\ClientController;
use Illuminate\Support\Facades\Route;

Route::get('/', [WebController::class, 'Home'])->name('home');
