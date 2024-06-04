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

class GhazalController extends Controller
{

    public function RequestPayment(Request $request){
        $IDReservation = $request->IDReservation;
        $Name = $request->Name;
        $Phone = $request->Phone;
        $Email = $request->Email;
        $Amount = $request->Amount;
        if(!$IDReservation){
            return "ERROR";
        }
        if(!$Amount){
            return "ERROR";
        }
        $cart_id = $IDReservation;
        $cart_amount = $Amount;
        $cart_description = "Test";
        $name = $Name;
        $phone = $Phone;
        $email = $Email;
        $street1 = "Cairo";
        $city = "Cairo";
        $state = "C";
        $country = "EG";
        $zip = Null;
        $ip = "156.204.119.218";
        $same_as_billing = Null;
        $return = "https://alghazal.sa/payment";
        $callback = "https://backend.giveyapp.com/api/admin/test2";
        $language = "en";
        $payment_method = "all";
        $transaction_type = "sale";

        $pay = paypage::sendPaymentCode('all')
                        ->sendTransaction('sale','Ecom')
                        ->sendCart($cart_id, $cart_amount, $cart_description)
                        ->sendCustomerDetails($name, $email, $phone, $street1, $city, $state, $country, $zip, $ip)
                        ->sendShippingDetails($same_as_billing, $name = null, $email = null, $phone = null, $street1= null, $city = null, $state = null, $country = null, $zip = null, $ip = null)
                        ->sendHideShipping($on = true)
                        ->sendURLs($return, $callback)
                        ->sendLanguage($language)
                        ->sendFramed($on = false)
                        ->create_pay_page(); // to initiate payment page


        return view('test',compact('pay'));
    }

    public function PaymentCallBack(Request $request){
        log::info($request);
        $GhazalCart = new GhazalCart;
        $GhazalCart->IDCart = $request->cart_id;
        $GhazalCart->respCode = $request->payment_result['response_code'];
        $GhazalCart->tranRef = $request->tran_ref;
        $GhazalCart->respMessage = $request->payment_result['response_message'];
        $GhazalCart->save();
        return $request->respMessage;
    }

    public function PaymentInvoice($IDCart){
        $GhazalCart = GhazalCart::where("IDCart",$IDCart)->orderby("IDGhazalCart","DESC")->first();
        if(!$GhazalCart){
            return "NOT_FOUND";
        }
        return $GhazalCart->respMessage;
    }

}
