<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\App;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;

class LocalizationController extends Controller
{
    /**
     * Set Language.
     *
     * @param $locale
     * @return RedirectResponse
     */
    public function index($locale): RedirectResponse
    {
        App::setlocale($locale);
        session()->put('locale', $locale);
        return redirect()->back();
    }
}
