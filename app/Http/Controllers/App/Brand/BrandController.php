<?php

namespace App\Http\Controllers\App\Brand;

header('Content-type: application/json');

use App\Http\Controllers\Controller;
use App\Http\Resources\App\BrandProductResource;
use App\Http\Resources\App\ClientBrandProductResource;
use App\V1\Brand\Brand;
use App\V1\Brand\Branch;
use App\V1\Brand\BrandRating;
use App\V1\Brand\BrandGallery;
use App\V1\Brand\BrandProduct;
use App\V1\Brand\BrandSocialMedia;
use App\V1\Brand\BrandProductGallery;
use App\V1\General\APICode;
use App\V1\General\Category;
use App\V1\General\SubCategory;
use App\V1\General\ContactUs;
use App\V1\Client\Client;
use App\V1\Client\ClientBrandProduct;
use App\V1\Location\Country;
use App\V1\Location\City;
use App\V1\Location\Area;
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

class BrandController extends Controller
{

    public function UserLogin(Request $request)
    {
        if ($request->Filled('UserAppLanguage')) {
            $UserAppLanguage = $request->UserAppLanguage;
        } else {
            $UserAppLanguage = "ar";
        }

        Session::put('ClientAppLanguage', $UserAppLanguage);
        App::setLocale($UserAppLanguage);

        $User = auth('user')->user();
        if (!$User) {
            //case 2: no token sent or token has expired or invalid
            if (!$request->filled('UserName')) {
                return RespondWithBadRequest(1);
            }
            if (!$request->filled('Password')) {
                return RespondWithBadRequest(1);
            }

            $UserName = $request->UserName;

            if ($UserName[0] == "+") {
                $Credentials = [
                    'UserPhone' => $UserName,
                    'UserDeleted' => 0,
                    'IDRole' => 2,
                    'password' => $request->Password
                ];
            } else {
                $Credentials = [
                    'UserEmail' => $UserName,
                    'UserDeleted' => 0,
                    'IDRole' => 2,
                    'password' => $request->Password
                ];
            }
            $AccessToken = CreateToken($Credentials, 'user');
            if (!$AccessToken) {
                return RespondWithBadRequest(6);
            }

            $AccessToken = $AccessToken['accessToken'];
            $User = auth('user')->user();
        } else {
            $AccessToken = $request->bearerToken();
        }

        if ($request->filled('UserDeviceToken')) {
            $User->UserDeviceToken = $request->UserDeviceToken;
        }
        if ($request->filled('UserDeviceType')) {
            $User->UserDeviceType = $request->UserDeviceType;
        }
        if ($request->filled('UserMobileService')) {
            $User->UserMobileService = $request->UserMobileService;
        }
        if ($request->filled('UserAppVersion')) {
            $User->UserAppVersion = $request->UserAppVersion;
        }
        if ($request->filled('UserAppLanguage')) {
            $User->UserLanguage = $request->UserAppLanguage;
        }

        if ($User->UserStatus == "INACTIVE") {
            return RespondWithBadRequest(17);
        }
        if ($User->UserStatus == "PENDING") {
            return RespondWithBadRequest(36);
        }

        $Success = true;
        $IDAPICode = 7;
        $response_code = 200;
        $APICode = APICode::where('IDAPICode', $IDAPICode)->first();
        $response = array('IDUser' => $User->IDUser, 'IDBrand' => $User->IDBrand, 'UserPhone' => $User->UserPhone, 'UserName' => $User->UserName, 'UserEmail' => $User->UserEmail, 'UserStatus' => $User->UserStatus, "UserLanguage" => $User->UserLanguage, "IDRole" => $User->IDRole, 'AccessToken' => $AccessToken);
        $response_array = array('Success' => $Success, 'ApiMsg' => trans('apicodes.' . $APICode->IDApiCode), 'ApiCode' => $APICode->IDApiCode, 'Response' => $response);
        $response = Response::json($response_array, $response_code);
        return $response;
    }

    public function ChangeLanguage(Request $request)
    {
        $User = auth('user')->user();
        if (!$User) {
            return RespondWithBadRequest(10);
        }

        $UserAppLanguage = $request->UserAppLanguage;
        if (!$UserAppLanguage) {
            return RespondWithBadRequest(1);
        }

        $User->UserLanguage = $UserAppLanguage;
        $User->save();

        Session::put('ClientAppLanguage', $UserAppLanguage);
        App::setLocale($UserAppLanguage);

        return RespondWithSuccessRequest(8);
    }

    public function UserLogout()
    {
        $User = auth('user')->user();
        if (!$User) {
            return RespondWithBadRequest(10);
        }
        JWTAuth::invalidate(JWTAuth::getToken());
        return RespondWithSuccessRequest(8);
    }

    public function QRCodeScan(Request $request)
    {
        $User = auth('user')->user();
        if (!$User) {
            return RespondWithBadRequest(10);
        }

        $ClientBrandProductSerial = $request->ClientBrandProductSerial;
        if (!$ClientBrandProductSerial) {
            return RespondWithBadRequest(1);
        }

        $ClientBrandProduct = ClientBrandProduct::leftjoin("brandproducts", "brandproducts.IDBrandProduct", "clientbrandproducts.IDBrandProduct")->leftjoin("subcategories", "subcategories.IDSubCategory", "brandproducts.IDSubCategory")->leftjoin("brands", "brands.IDBrand", "brandproducts.IDBrand");
        $ClientBrandProduct = $ClientBrandProduct->where("clientbrandproducts.ClientBrandProductSerial", $ClientBrandProductSerial);
        $ClientBrandProduct = $ClientBrandProduct->where("brandproducts.IDBrand", $User->IDBrand);
        $ClientBrandProduct = $ClientBrandProduct->select("clientbrandproducts.IDClientBrandProduct", "clientbrandproducts.ClientBrandProductSerial", "clientbrandproducts.ClientBrandProductStatus", "clientbrandproducts.created_at", "clientbrandproducts.updated_at", "brandproducts.IDBrandProduct", "brandproducts.IDBrand", "brandproducts.BrandProductTitleEn", "brandproducts.BrandProductTitleAr", "brandproducts.BrandProductDescEn", "brandproducts.BrandProductDescAr", "brandproducts.BrandProductPrice", "brandproducts.BrandProductDiscount", "brandproducts.BrandProductPoints", "brandproducts.BrandProductStatus", "brandproducts.BrandProductStartDate", "brandproducts.BrandProductEndDate", "brandproducts.created_at", "brands.BrandNameEn", "brands.BrandNameAr", "brands.BrandLogo", "brands.BrandRating", "subcategories.SubCategoryNameEn", "subcategories.SubCategoryNameAr");
        $ClientBrandProduct = $ClientBrandProduct->first();
        return $ClientBrandProduct;
        if (!$ClientBrandProduct) {
            return RespondWithBadRequest(1);
        }

        if ($ClientBrandProduct->ClientBrandProductStatus == "USED") {
            return RespondWithBadRequest(21);
        }
        if ($ClientBrandProduct->ClientBrandProductStatus == "EXPIRED") {
            return RespondWithBadRequest(22);
        }

        $BrandProductGallery = BrandProductGallery::where("IDBrandProduct", $ClientBrandProduct->IDBrandProduct)->where("BrandProductDeleted", 0)->select("BrandProductPath", "BrandProductType")->get();
        foreach ($BrandProductGallery as $Gallery) {
            if ($Gallery->BrandProductType == "IMAGE") {
                $Gallery->BrandProductPath = ($Gallery->BrandProductPath) ? asset($Gallery->BrandProductPath) : '';
            }
        }
        $ClientBrandProduct->BrandProductGallery = $BrandProductGallery;

        $ClientBrandProduct = ClientBrandProductResource::collection([$ClientBrandProduct])[0];

        $APICode = APICode::where('IDAPICode', 8)->first();
        $Response = array(
            'Success' => true,
            'ApiMsg' => __('apicodes.' . $APICode->IDApiCode),
            'ApiCode' => $APICode->IDApiCode,
            'Response' => $ClientBrandProduct
        );
        return $Response;
    }

    public function QRCodeUse(Request $request)
    {
        $User = auth('user')->user();
        if (!$User) {
            return RespondWithBadRequest(10);
        }

        $ClientBrandProductSerial = $request->ClientBrandProductSerial;
        if (!$ClientBrandProductSerial) {
            return RespondWithBadRequest(1);
        }

        $ClientBrandProduct = ClientBrandProduct::leftjoin("brandproducts", "brandproducts.IDBrandProduct", "clientbrandproducts.IDBrandProduct")->leftjoin("subcategories", "subcategories.IDSubCategory", "brandproducts.IDSubCategory")->leftjoin("brands", "brands.IDBrand", "brandproducts.IDBrand");
        $ClientBrandProduct = $ClientBrandProduct->where("clientbrandproducts.ClientBrandProductSerial", $ClientBrandProductSerial);
        $ClientBrandProduct = $ClientBrandProduct->where("brandproducts.IDBrand", $User->IDBrand);
        $ClientBrandProduct = $ClientBrandProduct->select("clientbrandproducts.IDClientBrandProduct","clientbrandproducts.IDClient", "clientbrandproducts.ClientBrandProductSerial", "clientbrandproducts.ClientBrandProductStatus", "clientbrandproducts.created_at", "clientbrandproducts.updated_at", "brandproducts.IDBrandProduct", "brandproducts.IDBrand", "brandproducts.BrandProductTitleEn", "brandproducts.BrandProductTitleAr", "brandproducts.BrandProductDescEn", "brandproducts.BrandProductDescAr", "brandproducts.BrandProductPrice", "brandproducts.BrandProductDiscount", "brandproducts.BrandProductPoints", "brandproducts.BrandProductStatus", "brandproducts.BrandProductStartDate", "brandproducts.BrandProductEndDate", "brandproducts.created_at", "brands.BrandNameEn", "brands.BrandNameAr", "brands.BrandLogo", "brands.BrandRating", "subcategories.SubCategoryNameEn", "subcategories.SubCategoryNameAr");
        $ClientBrandProduct = $ClientBrandProduct->first();

        if (!$ClientBrandProduct) {
            return RespondWithBadRequest(1);
        }

        if ($ClientBrandProduct->ClientBrandProductStatus == "USED") {
            return RespondWithBadRequest(21);
        }
        if ($ClientBrandProduct->ClientBrandProductStatus == "EXPIRED") {
            return RespondWithBadRequest(22);
        }

        $ClientBrandProduct->IDUser = $User->IDUser;
        $ClientBrandProduct->ClientBrandProductStatus = "USED";
        // return  $ClientBrandProduct;
        $ClientBrandProduct->save();

        $Client = Client::where("IDClient", $ClientBrandProduct->IDClient)->where("ClientDeleted",0)->first();
        if (!$Client) {
            return RespondWithBadRequest(10);
        }
        $Today = new DateTime('now');
        $Today = $Today->format('Y-m-d H:i:s');
        $BrandProduct = BrandProduct::where("IDBrandProduct", $ClientBrandProduct->IDBrandProduct)->where("BrandProductStatus", "ACTIVE")->where("BrandProductStartDate", "<=", $Today)->where("BrandProductEndDate", ">", $Today)->first();
        if (!$BrandProduct) {
            return RespondWithBadRequest(1);
        }
        $BatchNumber = "#BP" . $ClientBrandProduct->IDClientBrandProduct;
        $TimeFormat = new DateTime('now');
        $Time = $TimeFormat->format('H');
        $Time = $Time . $TimeFormat->format('i');
        $BatchNumber = $BatchNumber . $Time;
        AdjustLedger($Client, 0, $BrandProduct->BrandProductPoints, $BrandProduct->BrandProductReferralPoints, $BrandProduct->BrandProductUplinePoints, Null, "BRAND_PRODUCT", "CASH", "PAYMENT", $BatchNumber);
        return RespondWithSuccessRequest(8);
    }

    public function ClientBrandProducts(Request $request)
    {
        $User = auth('user')->user();
        if (!$User) {
            return RespondWithBadRequest(10);
        }

        $UserName = $request->UserName;
        if (!$UserName) {
            return RespondWithBadRequest(1);
        }

        $Client = Client::where("ClientPhone", $UserName)->orwhere("ClientAppID", $UserName)->first();
        if (!$Client) {
            return RespondWithBadRequest(23);
        }

        $ClientBrandProducts = ClientBrandProduct::leftjoin("brandproducts", "brandproducts.IDBrandProduct", "clientbrandproducts.IDBrandProduct")->leftjoin("subcategories", "subcategories.IDSubCategory", "brandproducts.IDSubCategory")->leftjoin("brands", "brands.IDBrand", "brandproducts.IDBrand")->leftjoin("clients", "clients.IDClient", "clientbrandproducts.IDClient");
        $ClientBrandProducts = $ClientBrandProducts->where("clientbrandproducts.ClientBrandProductStatus", "ACTIVE");
        $ClientBrandProducts = $ClientBrandProducts->where(function ($query) use ($UserName) {
            $query->where('clients.ClientPhone', $UserName)->orwhere('clients.ClientAppID', $UserName);
        });
        $ClientBrandProducts = $ClientBrandProducts->where("brandproducts.IDBrand", $User->IDBrand);
        $ClientBrandProducts = $ClientBrandProducts->select("clientbrandproducts.IDClientBrandProduct", "clientbrandproducts.ClientBrandProductSerial", "clientbrandproducts.ClientBrandProductStatus", "clientbrandproducts.created_at", "clientbrandproducts.updated_at", "brandproducts.IDBrandProduct", "brandproducts.IDBrand", "brandproducts.BrandProductTitleEn", "brandproducts.BrandProductTitleAr", "brandproducts.BrandProductDescEn", "brandproducts.BrandProductDescAr", "brandproducts.BrandProductPrice", "brandproducts.BrandProductDiscount", "brandproducts.BrandProductPoints", "brandproducts.BrandProductStatus", "brandproducts.BrandProductStartDate", "brandproducts.BrandProductEndDate", "brandproducts.created_at", "brands.BrandNameEn", "brands.BrandNameAr", "brands.BrandLogo", "brands.BrandRating", "subcategories.SubCategoryNameEn", "subcategories.SubCategoryNameAr");
        $ClientBrandProducts = $ClientBrandProducts->get();

        foreach ($ClientBrandProducts as $Product) {
            $BrandProductGallery = BrandProductGallery::where("IDBrandProduct", $Product->IDBrandProduct)->where("BrandProductDeleted", 0)->select("BrandProductPath", "BrandProductType")->get();
            foreach ($BrandProductGallery as $Gallery) {
                if ($Gallery->BrandProductType == "IMAGE") {
                    $Gallery->BrandProductPath = ($Gallery->BrandProductPath) ? asset($Gallery->BrandProductPath) : '';
                }
            }
            $Product->BrandProductGallery = $BrandProductGallery;
        }

        $ClientBrandProducts = ClientBrandProductResource::collection($ClientBrandProducts);

        $APICode = APICode::where('IDAPICode', 8)->first();
        $Response = array(
            'Success' => true,
            'ApiMsg' => __('apicodes.' . $APICode->IDApiCode),
            'ApiCode' => $APICode->IDApiCode,
            'Response' => $ClientBrandProducts
        );
        return $Response;
    }
}
