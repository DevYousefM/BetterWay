<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\V1\Client\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
use DateTime;
use Response;

class WebController extends Controller
{

    public function Home()
    {
        return $clients = Client::with(['referrals', 'visits' => function ($query) {
            $query->where('ClientBrandProductStatus', 'USED');
        }])
            ->whereHas('referrals', function ($query) {
                $query->whereNotNull('IDReferral');
            })
            ->get();
        // return view('web.index');
    }
}
