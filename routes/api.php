<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\App;

Route::get('/set-locale', function (Request $request) {
    $locale = $request->input('locale');

    App::setLocale($locale);
    
    return response()->json(['locale' => LocalAppLanguage()], 200);
    
});
