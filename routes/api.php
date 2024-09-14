<?php

use App\Http\Controllers\Web\WebController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\App;

Route::post('/contact', [WebController::class, 'Contact']);
