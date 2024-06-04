<?php

namespace App\Http\Controllers\External;

header('Content-type: application/json');

use App\Http\Controllers\Controller;
use App\V1\GhazalCart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\support\Facades\Input;
use Intervention\Image\ImageManagerStatic as Image;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\App;
use Tymon\JWTAuth\Facades\JWTAuth;
use Carbon\Carbon;
use Location;
use DateTime;
use DateInterval;
use Response;
use Cookie;
use DB;
use Paytabscom\Laravel_paytabs\Facades\paypage;

class ChatBotController extends Controller
{


  public function ReceiveMessage(){
    log::info(12345);
    $Data = file_get_contents("php://input");
    $Event = json_decode($Data, true);
    if(isset($Event)){
        $To = $Event['Data']['from'];
        $Message = "Hello, how can we help you?";
        SendMessage($To,$Message);
    }
  }

  function SendMessage($To,$Message){
      $curl = curl_init();

      curl_setopt_array($curl, array(
        CURLOPT_URL => "https://api.ultramsg.com/instance73086/messages/chat",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_SSL_VERIFYPEER => 0,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => '{
          "token": "hrnjpqasenbv43ht",
          "to": '.$To.',
          "body": '.$Message.'
      }',
        CURLOPT_HTTPHEADER => array(
          "content-type: application/json"
        ),
      ));
      
      $response = curl_exec($curl);
      $err = curl_error($curl);
      
      curl_close($curl);
      
      if ($err) {
          return "cURL Error #:" . $err;
      } else {
        return $response;
      }
  }
}
