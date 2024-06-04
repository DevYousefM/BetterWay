<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;

class ClientLocalization
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param  Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $ClientAppLanguage = Session::get('ClientAppLanguage');
        if (!$ClientAppLanguage) {
            $Client = auth('client')->user();
            if($Client){
                $ClientAppLanguage = $Client->ClientAppLanguage;
            }else{
                $ClientAppLanguage = "ar";
            }
        }
        App::setLocale($ClientAppLanguage);
        return $next($request);
    }
}
