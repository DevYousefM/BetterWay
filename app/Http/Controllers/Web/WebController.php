<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\V1\General\APICode;
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
        return view('web.index');
    }

    public function Contact(Request $request)
    {
        $FullName = $request->FullName;
        $Email = $request->Email;
        $Phone = $request->Phone;
        $Message = $request->Message;

        if (!$FullName) {
            return RespondWithBadRequest(1);
        }
        if (!$Email) {
            return RespondWithBadRequest(1);
        }
        if (!$Phone) {
            return RespondWithBadRequest(1);
        }
        if (!$Message) {
            return RespondWithBadRequest(1);
        }
        Contact::create([
            'full_name' => $FullName,
            'email' => $Email,
            'phone' => $Phone,
            'message' => $Message
        ]);

        return RespondWithSuccessRequest(8);
    }
}
