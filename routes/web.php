<?php

use App\Http\Controllers\Web\WebController;
use App\Http\Controllers\Admin\Client\ClientController;


Route::get('/', [WebController::class, 'Home'])->name('home');
