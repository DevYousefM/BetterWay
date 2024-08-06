<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\V1\Client\Client;
use App\V1\Client\Position;
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
        $Positions = Position::all();
        foreach ($Positions as $position) {
            Log::info("Position:{$position->PositionTitleEn}");

            // Log::info("HERE: " . Client::find(344)->referrals);

            $clients = Client::with(['referrals', 'visits' => function ($query) {
                $query->where('ClientBrandProductStatus', 'USED');
            }])
                ->whereHas('referrals', function ($query) {
                    $query->whereNotNull('IDReferral');
                })
                ->where("IDPosition", '<>', $position->IDPosition)
                ->where("IDClient", 344)
                ->get();

            echo $clients . "\n";
        }
        // return view('web.index');
    }
}
