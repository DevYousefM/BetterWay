<?php

namespace App\Http\Controllers\Admin\Brand;

header('Content-type: application/json');

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\BrandResource;
use App\Http\Resources\Admin\BrandRatingResource;
use App\Http\Resources\Admin\BranchResource;
use App\Http\Resources\Admin\BrandProductResource;
use App\Http\Resources\Admin\BrandSocialMediaResource;
use App\V1\User\User;
use App\V1\User\Role;
use App\V1\Brand\Brand;
use App\V1\Brand\Branch;
use App\V1\Brand\BrandRating;
use App\V1\Brand\BrandContact;
use App\V1\Brand\BrandContactUs;
use App\V1\Brand\BrandGallery;
use App\V1\Brand\BrandContract;
use App\V1\Brand\BrandDocument;
use App\V1\Brand\BrandProduct;
use App\V1\Brand\BrandProductBranch;
use App\V1\Brand\BrandProductGallery;
use App\V1\Brand\BrandSocialMedia;
use App\V1\Brand\BrandContractDocument;
use App\V1\General\APICode;
use App\V1\General\SocialMedia;
use App\V1\General\GeneralSetting;
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
    public function BrandList(Request $request, Brand $Brands)
    {
        $User = auth('user')->user();
        $IDPage = $request->IDPage;
        $SearchKey = $request->SearchKey;
        $BrandStatus = $request->BrandStatus;
        $StartDate = $request->StartDate;
        $EndDate = $request->EndDate;
        if (!$IDPage) {
            $IDPage = 0;
        } else {
            $IDPage = ($request->IDPage - 1) * 20;
        }

        $Brands = $Brands->leftjoin("users", "users.IDUser", "brands.IDUser");
        if ($SearchKey) {
            $Brands = $Brands->where(function ($query) use ($SearchKey) {
                $query->where('brands.BrandNameEn', 'like', '%' . $SearchKey . '%')
                    ->orwhere('brands.BrandNameAr', 'like', '%' . $SearchKey . '%')
                    ->orwhere('brands.BrandNumber', 'like', '%' . $SearchKey . '%')
                    ->orwhere('brands.BrandEmail', 'like', '%' . $SearchKey . '%');
            });
        }

        if ($BrandStatus) {
            $Brands = $Brands->where("brands.BrandStatus", $BrandStatus);
        }
        if ($StartDate) {
            $Brands = $Brands->where("brands.created_at", ">=", $StartDate);
        }
        if ($EndDate) {
            $Brands = $Brands->where("brands.created_at", "<=", $EndDate);
        }
        if ($User->IDRole == 2) {
            $Brands = $Brands->where("brands.IDBrand", $User->IDBrand);
        }

        $Brands = $Brands->select("brands.IDBrand", "brands.IDUser", "brands.BrandNameEn", "brands.BrandNameAr", "brands.BrandDescEn", "brands.BrandDescAr", "brands.BrandPolicyEn", "brands.BrandPolicyAr", "brands.BrandLogo", "brands.BrandNumber", "brands.BrandEmail", "brands.BrandRating", "brands.BrandStatus", "brands.created_at", "users.UserName", "users.UserPhone");
        $Pages = ceil($Brands->count() / 20);
        $Brands = $Brands->skip($IDPage)->take(20)->get();
        $Brands = BrandResource::collection($Brands);
        $Response = array("Brands" => $Brands, "Pages" => $Pages);

        $APICode = APICode::where('IDAPICode', 8)->first();
        $Response = array(
            'Success' => true,
            'ApiMsg' => __('apicodes.' . $APICode->IDApiCode),
            'ApiCode' => $APICode->IDApiCode,
            'Response' => $Response,
        );
        return $Response;
    }

    public function BrandAdd(Request $request)
    {
        $Admin = auth('user')->user();
        $IDUser = $request->IDUser;
        $BrandNameEn = $request->BrandNameEn;
        $BrandNameAr = $request->BrandNameAr;
        $BrandDescEn = $request->BrandDescEn;
        $BrandDescAr = $request->BrandDescAr;
        $BrandPolicyEn = $request->BrandPolicyEn;
        $BrandPolicyAr = $request->BrandPolicyAr;
        $BrandLogo = $request->BrandLogo;
        $BrandNumber = $request->BrandNumber;
        $BrandEmail = $request->BrandEmail;
        $BrandDocuments = $request->BrandDocuments;

        if (!$BrandNameEn) {
            return RespondWithBadRequest(1);
        }
        if (!$BrandNameAr) {
            return RespondWithBadRequest(1);
        }
        if (!$BrandNumber) {
            return RespondWithBadRequest(1);
        }

        $ImageExtArray = ["jpeg", "jpg", "png", "svg"];
        if ($request->file('BrandLogo')) {
            if (!in_array($request->BrandLogo->extension(), $ImageExtArray)) {
                return RespondWithBadRequest(15);
            }
            $NextIDBrand = DB::select('SELECT AUTO_INCREMENT FROM information_schema.TABLES WHERE  TABLE_NAME = "brands"')[0]->AUTO_INCREMENT;
            $BrandLogo = SaveImage($request->file('BrandLogo'), "brands", $NextIDBrand);
        } else {
            return RespondWithBadRequest(1);
        }

        if ($BrandDocuments) {
            foreach ($BrandDocuments as $Document) {
                if (!in_array($Document->extension(), $ImageExtArray)) {
                    return RespondWithBadRequest(15);
                }
            }
        }

        $BrandRecord = Brand::where('BrandNameEn', $BrandNameEn)->orwhere("BrandNameAr", $BrandNameAr)->first();
        if ($BrandRecord) {
            return RespondWithBadRequest(18);
        }
        if ($BrandEmail) {
            $BrandRecord = Brand::where('BrandEmail', $BrandEmail)->first();
            if ($BrandRecord) {
                return RespondWithBadRequest(2);
            }
        }
        $BrandRecord = Brand::where('BrandNumber', $BrandNumber)->first();
        if ($BrandRecord) {
            return RespondWithBadRequest(3);
        }

        $Brand = new Brand;
        $Brand->IDUser = $IDUser;
        $Brand->BrandNameEn = $BrandNameEn;
        $Brand->BrandNameAr = $BrandNameAr;
        $Brand->BrandDescEn = $BrandDescEn;
        $Brand->BrandDescAr = $BrandDescAr;
        $Brand->BrandPolicyEn = $BrandPolicyEn;
        $Brand->BrandPolicyAr = $BrandPolicyAr;
        $Brand->BrandNumber = $BrandNumber;
        $Brand->BrandEmail = $BrandEmail;
        $Brand->BrandLogo = $BrandLogo;
        $Brand->BrandStatus = "PENDING";
        $Brand->save();

        if ($BrandDocuments) {
            foreach ($BrandDocuments as $Document) {
                $Image = SaveImage($Document, "brands", $Brand->IDBrand);
                $BrandDocument = new BrandDocument;
                $BrandDocument->IDBrand = $Brand->IDBrand;
                $BrandDocument->BrandDocumentPath = $Image;
                $BrandDocument->save();
            }
        }

        $Desc = "Brand " . $BrandNameEn . " was added";
        ActionBackLog($Admin->IDUser, $Brand->IDBrand, "ADD_BRAND", $Desc);
        return RespondWithSuccessRequest(8);
    }

    public function BrandStatus(Request $request)
    {
        $Admin = auth('user')->user();
        $IDBrand = $request->IDBrand;
        $BrandStatus = $request->BrandStatus;


        if (!$IDBrand) {
            return RespondWithBadRequest(1);
        }
        if (!$BrandStatus) {
            return RespondWithBadRequest(1);
        }

        $Brand = Brand::find($IDBrand);
        if (!$Brand) {
            return RespondWithBadRequest(1);
        }
        $Desc = "Brand status changed from " . $Brand->BrandStatus . " to " . $BrandStatus;
        $Brand->BrandStatus = $BrandStatus;
        $Brand->save();

        ActionBackLog($Admin->IDUser, $Brand->IDBrand, "EDIT_BRAND", $Desc);
        return RespondWithSuccessRequest(8);
    }

    public function BrandEditPage($IDBrand)
    {
        $Brand = Brand::leftjoin("users", "users.IDUser", "brands.IDUser")->where("brands.IDBrand", $IDBrand)->select("brands.IDBrand", "brands.IDUser", "brands.BrandNameEn", "brands.BrandNameAr", "brands.BrandDescEn", "brands.BrandDescAr", "brands.BrandPolicyEn", "brands.BrandPolicyAr", "brands.BrandLogo", "brands.BrandNumber", "brands.BrandEmail", "brands.BrandRating", "brands.BrandStatus", "brands.created_at", "users.UserName", "users.UserPhone")->first();
        if (!$Brand) {
            return RespondWithBadRequest(1);
        }

        $BrandContract = BrandContract::where("IDBrand", $IDBrand)->where("BrandContractStatus", "ACTIVE")->first();

        $Brand->BrandLogo = ($Brand->BrandLogo) ? asset($Brand->BrandLogo) : '';
        $Brand->SalesName = ($Brand->UserName) ? $Brand->UserName : '';
        $Brand->SalesPhone = ($Brand->UserPhone) ? $Brand->UserPhone : '';
        $Brand->BrandContract = $BrandContract;
        unset($Brand['UserName']);
        unset($Brand['UserPhone']);

        $APICode = APICode::where('IDAPICode', 8)->first();
        $Response = array(
            'Success' => true,
            'ApiMsg' => __('apicodes.' . $APICode->IDApiCode),
            'ApiCode' => $APICode->IDApiCode,
            'Response' => $Brand,
        );
        return $Response;
    }

    public function BrandEdit(Request $request)
    {
        $Admin = auth('user')->user();
        $IDUser = $request->IDUser;
        $IDBrand = $request->IDBrand;
        $BrandNameEn = $request->BrandNameEn;
        $BrandNameAr = $request->BrandNameAr;
        $BrandDescEn = $request->BrandDescEn;
        $BrandDescAr = $request->BrandDescAr;
        $BrandPolicyEn = $request->BrandPolicyEn;
        $BrandPolicyAr = $request->BrandPolicyAr;
        $BrandLogo = $request->BrandLogo;
        $BrandNumber = $request->BrandNumber;
        $BrandEmail = $request->BrandEmail;
        $BrandDocuments = $request->BrandDocuments;
        $Desc = "";

        $Brand = Brand::find($IDBrand);
        if (!$Brand) {
            return RespondWithBadRequest(1);
        }

        if ($BrandNameEn) {
            $BrandRecord = Brand::where("BrandNameEn", $BrandNameEn)->where("IDBrand", "<>", $IDBrand)->first();
            if ($BrandRecord) {
                return RespondWithBadRequest(18);
            }
            $Desc = "Brand english name changed from " . $Brand->BrandNameEn . " to " . $BrandNameEn;
            $Brand->BrandNameEn = $BrandNameEn;
        }
        if ($BrandNameAr) {
            $BrandRecord = Brand::where("BrandNameAr", $BrandNameAr)->where("IDBrand", "<>", $IDBrand)->first();
            if ($BrandRecord) {
                return RespondWithBadRequest(18);
            }
            $Desc = $Desc . ", Brand arabic name changed from " . $Brand->BrandNameAr . " to " . $BrandNameAr;
            $Brand->BrandNameAr = $BrandNameAr;
        }
        if ($BrandEmail) {
            $BrandRecord = Brand::where("BrandEmail", $BrandEmail)->where("IDBrand", "<>", $IDBrand)->first();
            if ($BrandRecord) {
                return RespondWithBadRequest(2);
            }
            $Desc = $Desc . ", Brand email changed from " . $Brand->BrandEmail . " to " . $BrandEmail;
            $Brand->BrandEmail = $BrandEmail;
        }
        if ($BrandNumber) {
            $BrandRecord = Brand::where("BrandNumber", $BrandNumber)->where("IDBrand", "<>", $IDBrand)->first();
            if ($BrandRecord) {
                return RespondWithBadRequest(3);
            }
            $Desc = $Desc . ", Brand phone changed from " . $Brand->BrandNumber . " to " . $BrandNumber;
            $Brand->BrandNumber = $BrandNumber;
        }
        if ($BrandDescEn) {
            $Desc = $Desc . ", Brand english desc changed from " . $Brand->BrandDescEn . " to " . $BrandDescEn;
            $Brand->BrandDescEn = $BrandDescEn;
        }
        if ($BrandDescAr) {
            $Desc = $Desc . ", Brand arabic desc changed from " . $Brand->BrandDescAr . " to " . $BrandDescAr;
            $Brand->BrandDescAr = $BrandDescAr;
        }
        if ($BrandPolicyEn) {
            $Desc = $Desc . ", Brand english policy changed from " . $Brand->BrandPolicyEn . " to " . $BrandPolicyEn;
            $Brand->BrandPolicyEn = $BrandPolicyEn;
        }
        if ($BrandPolicyAr) {
            $Desc = $Desc . ", Brand arabic policy changed from " . $Brand->BrandPolicyAr . " to " . $BrandPolicyAr;
            $Brand->BrandPolicyAr = $BrandPolicyAr;
        }
        if ($IDUser) {
            $Desc = $Desc . ", Brand sales user changed from " . $Brand->IDUser . " to " . $IDUser;
            $Brand->IDUser = $IDUser;
        }

        $ImageExtArray = ["jpeg", "jpg", "png", "svg"];
        if ($request->file('BrandLogo')) {
            if (!in_array($request->BrandLogo->extension(), $ImageExtArray)) {
                return RespondWithBadRequest(15);
            }
            if ($Brand->BrandLogo) {
                $OldPhoto = substr($Brand->BrandLogo, 7);
                Storage::disk('uploads')->delete($OldPhoto);
            }
            $BrandLogo = SaveImage($request->file('BrandLogo'), "brands", $IDBrand);
            $Brand->BrandLogo = $BrandLogo;
            $Desc = $Desc . ", Brand Logo changed";
        }

        $Brand->BrandStatus = "PENDING";
        $Brand->save();

        if ($BrandDocuments) {
            foreach ($BrandDocuments as $Document) {
                if (!in_array($Document->extension(), $ImageExtArray)) {
                    return RespondWithBadRequest(15);
                }
            }
            foreach ($BrandDocuments as $Document) {
                $Image = SaveImage($Document, "brands", $Brand->IDBrand);
                $BrandDocument = new BrandDocument;
                $BrandDocument->IDBrand = $Brand->IDBrand;
                $BrandDocument->BrandDocumentPath = $Image;
                $BrandDocument->save();
            }
            $Desc = $Desc . ", Brand gallery added";
        }

        ActionBackLog($Admin->IDUser, $Brand->IDBrand, "EDIT_BRAND", $Desc);
        return RespondWithSuccessRequest(8);
    }

    public function BrandAjax(Request $request, Brand $Brands)
    {
        $Brands = Brand::leftjoin("users", "users.IDUser", "brands.IDUser")->where("brands.BrandStatus", "ACTIVE")->select("brands.IDBrand", "brands.BrandNameEn", "brands.BrandNameAr", "brands.BrandDescEn", "brands.BrandDescAr", "brands.BrandPolicyEn", "brands.BrandPolicyAr", "brands.BrandLogo", "brands.BrandNumber", "brands.BrandEmail", "brands.BrandRating", "brands.BrandStatus", "brands.created_at", "users.UserName", "users.UserPhone")->get();
        $Brands = BrandResource::collection($Brands);

        $APICode = APICode::where('IDAPICode', 8)->first();
        $Response = array(
            'Success' => true,
            'ApiMsg' => __('apicodes.' . $APICode->IDApiCode),
            'ApiCode' => $APICode->IDApiCode,
            'Response' => $Brands,
        );
        return $Response;
    }

    public function BrandDocuments($IDBrand)
    {
        $Brand = Brand::find($IDBrand);
        if (!$Brand) {
            return RespondWithBadRequest(1);
        }

        $BrandDocuments = BrandDocument::where("IDBrand", $IDBrand)->where("BrandDocumentDeleted", 0)->get();
        foreach ($BrandDocuments as $Document) {
            $Document->BrandDocumentPath = asset($Document->BrandDocumentPath);
        }

        $APICode = APICode::where('IDAPICode', 8)->first();
        $Response = array(
            'Success' => true,
            'ApiMsg' => __('apicodes.' . $APICode->IDApiCode),
            'ApiCode' => $APICode->IDApiCode,
            'Response' => $BrandDocuments,
        );
        return $Response;
    }

    public function BrandDocumentRemove($IDBrandDocument)
    {
        $Admin = auth('user')->user();
        $BrandDocument = BrandDocument::find($IDBrandDocument);
        if (!$BrandDocument) {
            return RespondWithBadRequest(1);
        }

        $OldDocument = substr($BrandDocument->BrandDocumentPath, 7);
        Storage::disk('uploads')->delete($OldDocument);

        $BrandDocument->BrandDocumentDeleted = 1;
        $BrandDocument->save();

        $Desc = "Brand Document Removed";
        ActionBackLog($Admin->IDUser, $BrandDocument->IDBrand, "EDIT_BRAND", $Desc);
        return RespondWithSuccessRequest(8);
    }

    public function BrandContactList(Request $request)
    {
        $IDBrand = $request->IDBrand;
        $SearchKey = $request->SearchKey;
        if (!$IDBrand) {
            return RespondWithBadRequest(1);
        }

        $Brand = Brand::leftjoin("users", "users.IDUser", "brands.IDUser")->where("brands.IDBrand", $IDBrand)->first();
        $SalesName = $Brand->UserName ? $Brand->UserName : '';
        $SalesPhone = $Brand->UserPhone ? $Brand->UserPhone : '';
        $Sales = ["SalesName" => $SalesName, "SalesPhone" => $SalesPhone];

        $BrandContacts = BrandContact::where("IDBrand", $IDBrand)->where("BrandContactDeleted", 0);
        if ($SearchKey) {
            $Branches = $Branches->where(function ($query) use ($SearchKey) {
                $query->where('BrandContactName', 'like', '%' . $SearchKey . '%')
                    ->orwhere('BrandContactPhone', 'like', '%' . $SearchKey . '%')
                    ->orwhere('BrandContactTitle', 'like', '%' . $SearchKey . '%');
            });
        }
        $BrandContacts = $BrandContacts->get();

        $Response = ["BrandContacts" => $BrandContacts, "Sales" => $Sales];
        $APICode = APICode::where('IDAPICode', 8)->first();
        $Response = array(
            'Success' => true,
            'ApiMsg' => __('apicodes.' . $APICode->IDApiCode),
            'ApiCode' => $APICode->IDApiCode,
            'Response' => $Response
        );
        return $Response;
    }

    public function BrandContactDelete($IDBrandContact)
    {
        $Admin = auth('user')->user();
        $BrandContact = BrandContact::find($IDBrandContact);
        if (!$BrandContact) {
            return RespondWithBadRequest(1);
        }

        $BrandContact->BrandContactDeleted = 1;
        $BrandContact->save();

        $Desc = "Brand Contact Deleted";
        ActionBackLog($Admin->IDUser, $BrandContact->IDBrandContact, "EDIT_BRAND_CONTACT", $Desc);
        return RespondWithSuccessRequest(8);
    }

    public function BrandContactAdd(Request $request)
    {
        $Admin = auth('user')->user();
        $IDBrand = $request->IDBrand;
        $BrandContactName = $request->BrandContactName;
        $BrandContactPhone = $request->BrandContactPhone;
        $BrandContactTitle = $request->BrandContactTitle;
        if (!$IDBrand) {
            return RespondWithBadRequest(1);
        }
        if (!$BrandContactName) {
            return RespondWithBadRequest(1);
        }
        if (!$BrandContactPhone) {
            return RespondWithBadRequest(1);
        }
        if (!$BrandContactTitle) {
            return RespondWithBadRequest(1);
        }

        $BrandContact = BrandContact::where("IDBrand", $IDBrand)->where("BrandContactPhone", $BrandContactPhone)->where("BrandContactDeleted", 0)->first();
        if ($BrandContact) {
            return RespondWithBadRequest(3);
        }

        $BrandContact = new BrandContact;
        $BrandContact->IDBrand = $IDBrand;
        $BrandContact->BrandContactPhone = $BrandContactPhone;
        $BrandContact->BrandContactTitle = $BrandContactTitle;
        $BrandContact->BrandContactName = $BrandContactName;
        $BrandContact->save();

        $Desc = "Brand Contact " . $BrandContactPhone . " was added";
        ActionBackLog($Admin->IDUser, $BrandContact->IDBrandContact, "ADD_BRAND_CONTACT", $Desc);
        return RespondWithSuccessRequest(8);
    }

    public function BrandContactEditPage($IDBrandContact)
    {
        $BrandContact = BrandContact::where("IDBrandContact", $IDBrandContact)->where("BrandContactDeleted", 0)->first();
        if (!$BrandContact) {
            return RespondWithBadRequest(1);
        }

        $APICode = APICode::where('IDAPICode', 8)->first();
        $Response = array(
            'Success' => true,
            'ApiMsg' => __('apicodes.' . $APICode->IDApiCode),
            'ApiCode' => $APICode->IDApiCode,
            'Response' => $BrandContact,
        );
        return $Response;
    }

    public function BrandContactEdit(Request $request)
    {
        $Admin = auth('user')->user();
        $IDBrandContact = $request->IDBrandContact;
        $BrandContactName = $request->BrandContactName;
        $BrandContactPhone = $request->BrandContactPhone;
        $BrandContactTitle = $request->BrandContactTitle;
        $Desc = "";
        if (!$IDBrandContact) {
            return RespondWithBadRequest(1);
        }

        $BrandContact = BrandContact::find($IDBrandContact);
        if (!$BrandContact) {
            return RespondWithBadRequest(1);
        }

        if ($BrandContactName) {
            $Desc = "Brand contact name changed from " . $BrandContact->BrandContactName . " to " . $BrandContactName;
            $BrandContact->BrandContactName = $BrandContactName;
        }
        if ($BrandContactTitle) {
            $Desc = $Desc . ", Brand contact title changed from " . $BrandContact->BrandContactTitle . " to " . $BrandContactTitle;
            $BrandContact->BrandContactTitle = $BrandContactTitle;
        }
        if ($BrandContactPhone) {
            $BrandContactRow = BrandContact::where("IDBrand", $BrandContact->IDBrand)->where("IDBrandContact", "<>", $IDBrandContact)->where("BrandContactPhone", $BrandContactPhone)->where("BrandContactDeleted", 0)->first();
            if ($BrandContactRow) {
                return RespondWithBadRequest(3);
            }
            $Desc = $Desc . ", Brand contact phone changed from " . $BrandContact->BrandContactPhone . " to " . $BrandContactPhone;
            $BrandContact->BrandContactPhone = $BrandContactPhone;
        }
        $BrandContact->save();

        ActionBackLog($Admin->IDUser, $BrandContact->IDBrandContact, "EDIT_BRAND_CONTACT", $Desc);
        return RespondWithSuccessRequest(8);
    }

    public function BrandGallery(Request $request, BrandGallery $BrandGallery)
    {
        $IDBrand = $request->IDBrand;
        if (!$IDBrand) {
            return RespondWithBadRequest(1);
        }

        $BrandGallery = $BrandGallery->where("IDBrand", $IDBrand)->where("BrandGalleryDeleted", 0)->get();
        foreach ($BrandGallery as $Gallery) {
            if ($Gallery->BrandGalleryType == "IMAGE") {
                $Gallery->BrandGalleryPath = ($Gallery->BrandGalleryPath) ? asset($Gallery->BrandGalleryPath) : '';
            }
        }

        $APICode = APICode::where('IDAPICode', 8)->first();
        $Response = array(
            'Success' => true,
            'ApiMsg' => __('apicodes.' . $APICode->IDApiCode),
            'ApiCode' => $APICode->IDApiCode,
            'Response' => $BrandGallery,
        );
        return $Response;
    }

    public function BrandGalleryRemove($IDBrandGallery)
    {
        $Admin = auth('user')->user();
        $BrandGallery = BrandGallery::find($IDBrandGallery);
        if (!$BrandGallery) {
            return RespondWithBadRequest(1);
        }

        if ($BrandGallery->BrandGalleryType == "IMAGE") {
            $OldDocument = substr($BrandGallery->BrandGalleryPath, 7);
            Storage::disk('uploads')->delete($OldDocument);
        }

        $BrandGallery->BrandGalleryDeleted = 1;
        $BrandGallery->save();

        $Desc = "Brand Gallery Removed";
        ActionBackLog($Admin->IDUser, $BrandGallery->IDBrand, "EDIT_BRAND", $Desc);
        return RespondWithSuccessRequest(8);
    }


    public function BrandGalleryAdd(Request $request)
    {
        $Admin = auth('user')->user();
        $IDBrand = $request->IDBrand;
        $BrandPhotos = $request->BrandPhotos;
        $BrandVideos = $request->BrandVideos;

        if (!$IDBrand) {
            return RespondWithBadRequest(1);
        }

        $ImageExtArray = ["jpeg", "jpg", "png", "svg"];
        if ($BrandPhotos) {
            foreach ($BrandPhotos as $Photo) {
                if (!in_array($Photo->extension(), $ImageExtArray)) {
                    return RespondWithBadRequest(15);
                }
            }
        }

        if ($BrandPhotos) {
            foreach ($BrandPhotos as $Photo) {
                $Image = SaveImage($Photo, "brands", $IDBrand);
                $BrandGallery = new BrandGallery;
                $BrandGallery->IDBrand = $IDBrand;
                $BrandGallery->BrandGalleryPath = $Image;
                $BrandGallery->BrandGalleryType = "IMAGE";
                $BrandGallery->save();
            }
        }

        if ($BrandVideos) {
            if (count($BrandVideos)) {
                foreach ($BrandVideos as $Video) {
                    $BrandVideo = YoutubeEmbedUrl($Video);
                    $BrandGallery = new BrandGallery;
                    $BrandGallery->IDBrand = $IDBrand;
                    $BrandGallery->BrandGalleryPath = $BrandVideo;
                    $BrandGallery->BrandGalleryType = "VIDEO";
                    $BrandGallery->save();
                }
            }
        }

        $Desc = "Brand Gallery Added";
        ActionBackLog($Admin->IDUser, $IDBrand, "EDIT_BRAND", $Desc);
        return RespondWithSuccessRequest(8);
    }

    public function BrandContractList(Request $request, BrandContract $BrandContracts)
    {
        $User = auth('user')->user();
        $IDBrand = $request->IDBrand;
        $IDPage = $request->IDPage;
        $BrandContractStartDate = $request->BrandContractStartDate;
        $BrandContractEndDate = $request->BrandContractEndDate;
        $BrandContractStatus = $request->BrandContractStatus;

        if (!$User->IDBrand && !$IDBrand) {
            return RespondWithBadRequest(1);
        }

        $BrandContracts = $BrandContracts;
        if (!$IDPage) {
            $IDPage = 0;
        } else {
            $IDPage = ($request->IDPage - 1) * 20;
        }
        if ($BrandContractStartDate) {
            $BrandContracts = $BrandContracts->where("BrandContractStartDate", ">=", $BrandContractStartDate);
        }
        if ($BrandContractEndDate) {
            $BrandContracts = $BrandContracts->where("BrandContractStartDate", "<=", $BrandContractEndDate);
        }
        if ($BrandContractStatus) {
            $BrandContracts = $BrandContracts->where("BrandContractStatus", $BrandContractStatus);
        }
        if ($User->IDBrand) {
            $BrandContracts = $BrandContracts->where("IDBrand", $User->IDBrand);
        }
        if ($IDBrand) {
            $BrandContracts = $BrandContracts->where("IDBrand", $IDBrand);
        }

        $Pages = ceil($BrandContracts->count() / 20);
        $BrandContracts = $BrandContracts->skip($IDPage)->take(20)->get();
        $Response = array("Brands" => $BrandContracts, "Pages" => $Pages);

        $APICode = APICode::where('IDAPICode', 8)->first();
        $Response = array(
            'Success' => true,
            'ApiMsg' => __('apicodes.' . $APICode->IDApiCode),
            'ApiCode' => $APICode->IDApiCode,
            'Response' => $Response,
        );
        return $Response;
    }

    public function BrandContractAdd(Request $request)
    {
        $Admin = auth('user')->user();
        $IDBrand = $request->IDBrand;
        $BrandContractStartDate = $request->BrandContractStartDate;
        $BrandContractEndDate = $request->BrandContractEndDate;
        $BrandContractAmount = $request->BrandContractAmount;
        $BrandContractMonths = $request->BrandContractMonths;
        $BrandContractDocuments = $request->BrandContractDocuments;

        if (!$IDBrand) {
            return RespondWithBadRequest(1);
        }
        if (!$BrandContractStartDate) {
            return RespondWithBadRequest(1);
        }
        if (!$BrandContractEndDate) {
            return RespondWithBadRequest(1);
        }
        if (!$BrandContractAmount) {
            return RespondWithBadRequest(1);
        }

        $ImageExtArray = ["jpeg", "jpg", "png", "svg"];
        if ($BrandContractDocuments) {
            foreach ($BrandContractDocuments as $Document) {
                if (!in_array($Document->extension(), $ImageExtArray)) {
                    return RespondWithBadRequest(15);
                }
            }
        }

        // $BrandContract = BrandContract::where('BrandContractStartDate',"<=",$BrandContractStartDate)->where("BrandContractEndDate",">=",$BrandContractStartDate)->whereIn("BrandContractStatus",["ACTIVE","PENDING"])->first();
        // if($BrandContract){
        //     return RespondWithBadRequest(19);
        // }
        // $BrandContract = BrandContract::where('BrandContractStartDate',">=",$BrandContractStartDate)->where("BrandContractStartDate","<=",$BrandContractEndDate)->whereIn("BrandContractStatus",["ACTIVE","PENDING"])->first();
        // if($BrandContract){
        //     return RespondWithBadRequest(19);
        // }

        $BrandContractStatus = "PENDING";
        $Today = new DateTime('now');
        $Today = $Today->format('Y-m-d H:i:s');
        if ($Today >= $BrandContractStartDate) {
            $BrandContractStatus = "ACTIVE";
            $Brand = Brand::find($IDBrand);
            $Brand->BrandStatus = "ACTIVE";
            $Brand->save();
        }

        $BrandContract = new BrandContract;
        $BrandContract->IDBrand = $IDBrand;
        $BrandContract->BrandContractStartDate = $BrandContractStartDate;
        $BrandContract->BrandContractEndDate = $BrandContractEndDate;
        $BrandContract->BrandContractMonths = $BrandContractMonths;
        $BrandContract->BrandContractAmount = $BrandContractAmount;
        $BrandContract->BrandContractStatus = $BrandContractStatus;
        $BrandContract->save();

        if ($BrandContractDocuments) {
            foreach ($BrandContractDocuments as $Document) {
                $Image = SaveImage($Document, "brands", $IDBrand);
                $BrandContractDocument = new BrandContractDocument;
                $BrandContractDocument->IDBrandContract = $BrandContract->IDBrandContract;
                $BrandContractDocument->BrandContractDocumentPath = $Image;
                $BrandContractDocument->save();
            }
        }

        $Desc = "Brand Contract Added";
        ActionBackLog($Admin->IDUser, $BrandContract->IDBrandContract, "ADD_BRAND_CONTRACT", $Desc);
        return RespondWithSuccessRequest(8);
    }

    public function BrandContractEditPage($IDBrandContract)
    {
        $BrandContract = BrandContract::find($IDBrandContract);
        if (!$BrandContract) {
            return RespondWithBadRequest(1);
        }

        $APICode = APICode::where('IDAPICode', 8)->first();
        $Response = array(
            'Success' => true,
            'ApiMsg' => __('apicodes.' . $APICode->IDApiCode),
            'ApiCode' => $APICode->IDApiCode,
            'Response' => $BrandContract,
        );
        return $Response;
    }

    public function BrandContractEdit(Request $request)
    {
        $Admin = auth('user')->user();
        $IDBrandContract = $request->IDBrandContract;
        $BrandContractStartDate = $request->BrandContractStartDate;
        $BrandContractEndDate = $request->BrandContractEndDate;
        $BrandContractAmount = $request->BrandContractAmount;
        $BrandContractMonths = $request->BrandContractMonths;
        $BrandContractDocuments = $request->BrandContractDocuments;
        $Desc = "";

        $BrandContract = BrandContract::find($IDBrandContract);
        if (!$BrandContract) {
            return RespondWithBadRequest(1);
        }

        if ($BrandContractStartDate && $BrandContractEndDate) {
            // $BrandContractRow = BrandContract::where('BrandContractStartDate',"<=",$BrandContractStartDate)->where("BrandContractEndDate",">=",$BrandContractStartDate)->whereIn("BrandContractStatus",["ACTIVE","PENDING"])->where("IDBrandContract","<>",$IDBrandContract)->first();
            // if($BrandContractRow){
            //     return RespondWithBadRequest(19);
            // }
            // $BrandContractRow = BrandContract::where('BrandContractStartDate',">=",$BrandContractStartDate)->where("BrandContractStartDate","<=",$BrandContractEndDate)->whereIn("BrandContractStatus",["ACTIVE","PENDING"])->where("IDBrandContract","<>",$IDBrandContract)->first();
            // if($BrandContractRow){
            //     return RespondWithBadRequest(19);
            // }

            $Desc = "Brand Contract start date changed from " . $BrandContract->BrandContractStartDate . " to " . $BrandContractStartDate;
            $Desc = $Desc . ", Brand Contract end date changed from " . $BrandContract->BrandContractEndDate . " to " . $BrandContractEndDate;

            $BrandContract->BrandContractStartDate = $BrandContractStartDate;
            $BrandContract->BrandContractEndDate = $BrandContractEndDate;


            $Brand = Brand::find($BrandContract->IDBrand);
            $BrandContractStatus = "PENDING";
            $Today = new DateTime('now');
            $Today = $Today->format('Y-m-d H:i:s');
            $OldBrandContractStatus = $BrandContract->BrandContractStatus;
            if ($Today >= $BrandContractStartDate) {
                $BrandContract->BrandContractStatus = "ACTIVE";
                $Brand->BrandStatus = "ACTIVE";
            }
            if ($OldBrandContractStatus == "ACTIVE" && $BrandContractStatus == "PENDING") {
                $Brand->BrandStatus = "INACTIVE";
            }
            $Brand->save();
        }

        if ($BrandContractAmount) {
            $Desc = $Desc . ", Brand Contract amount changed from " . $BrandContract->BrandContractAmount . " to " . $BrandContractAmount;
            $BrandContract->BrandContractAmount = $BrandContractAmount;
        }
        if ($BrandContractMonths) {
            $Desc = $Desc . ", Brand Contract months changed from " . $BrandContract->BrandContractMonths . " to " . $BrandContractMonths;
            $BrandContract->BrandContractMonths = $BrandContractMonths;
        }
        $BrandContract->save();

        $ImageExtArray = ["jpeg", "jpg", "png", "svg"];
        if ($BrandContractDocuments) {
            foreach ($BrandContractDocuments as $Document) {
                if (!in_array($Document->extension(), $ImageExtArray)) {
                    return RespondWithBadRequest(15);
                }
            }
            foreach ($BrandContractDocuments as $Document) {
                $Image = SaveImage($Document, "brands", $BrandContract->IDBrand);
                $BrandContractDocument = new BrandContractDocument;
                $BrandContractDocument->IDBrandContract = $IDBrandContract;
                $BrandContractDocument->BrandContractDocumentPath = $Image;
                $BrandContractDocument->save();
            }
            $Desc = $Desc . ", Brand Contract docs added";
        }

        ActionBackLog($Admin->IDUser, $BrandContract->IDBrandContract, "EDIT_BRAND_CONTRACT", $Desc);
        return RespondWithSuccessRequest(8);
    }

    public function BrandContractDocuments($IDBrandContract)
    {
        $BrandContract = BrandContract::find($IDBrandContract);
        if (!$BrandContract) {
            return RespondWithBadRequest(1);
        }

        $BrandContractDocuments = BrandContractDocument::where("IDBrandContract", $IDBrandContract)->where("BrandContractDocumentDeleted", 0)->get();
        foreach ($BrandContractDocuments as $Document) {
            $Document->BrandContractDocumentPath = asset($Document->BrandContractDocumentPath);
        }

        $APICode = APICode::where('IDAPICode', 8)->first();
        $Response = array(
            'Success' => true,
            'ApiMsg' => __('apicodes.' . $APICode->IDApiCode),
            'ApiCode' => $APICode->IDApiCode,
            'Response' => $BrandContractDocuments,
        );
        return $Response;
    }

    public function BrandContractDocumentRemove($IDBrandContractDocument)
    {
        $Admin = auth('user')->user();
        $BrandContractDocument = BrandContractDocument::find($IDBrandContractDocument);
        if (!$BrandContractDocument) {
            return RespondWithBadRequest(1);
        }

        $OldDocument = substr($BrandContractDocument->BrandContractDocumentPath, 7);
        Storage::disk('uploads')->delete($OldDocument);

        $BrandContractDocument->BrandContractDocumentDeleted = 1;
        $BrandContractDocument->save();

        $Desc = "Brand contract document removed";
        ActionBackLog($Admin->IDUser, $BrandContractDocument->IDBrandContract, "EDIT_BRAND_CONTRACT", $Desc);
        return RespondWithSuccessRequest(8);
    }

    public function BranchList(Request $request, Branch $Branches)
    {
        $User = auth('user')->user();
        $IDPage = $request->IDPage;
        $SearchKey = $request->SearchKey;
        $IDCity = $request->IDCity;
        $IDArea = $request->IDArea;
        $IDBrand = $request->IDBrand;
        $BranchStatus = $request->BranchStatus;
        if (!$IDPage) {
            $IDPage = 0;
        } else {
            $IDPage = ($request->IDPage - 1) * 20;
        }

        $Branches = $Branches->leftjoin("areas", "areas.IDArea", "branches.IDArea")->leftjoin("cities", "cities.IDCity", "areas.IDCity")->leftjoin("brands", "brands.IDBrand", "branches.IDBrand");
        if ($SearchKey) {
            $Branches = $Branches->where(function ($query) use ($SearchKey) {
                $query->where('branches.BranchAddressEn', 'like', '%' . $SearchKey . '%')
                    ->orwhere('branches.BranchAddressAr', 'like', '%' . $SearchKey . '%')
                    ->orwhere('branches.BranchPhone', 'like', '%' . $SearchKey . '%');
            });
        }
        if ($IDBrand) {
            $Branches = $Branches->where("branches.IDBrand", $IDBrand);
        }
        if ($User->IDRole == 2) {
            $Branches = $Branches->where("branches.IDBrand", $User->IDBrand);
        }
        if ($IDCity) {
            $Branches = $Branches->where("branches.IDCity", $IDCity);
        }
        if ($IDArea) {
            $Branches = $Branches->where("branches.IDArea", $IDArea);
        }
        if ($BranchStatus) {
            $Branches = $Branches->where("branches.BranchStatus", $BranchStatus);
        }

        $Pages = ceil($Branches->count() / 20);
        $Branches = $Branches->skip($IDPage)->take(20)->get();
        $Branches = BranchResource::collection($Branches);
        $Response = array("Branches" => $Branches, "Pages" => $Pages);

        $APICode = APICode::where('IDAPICode', 8)->first();
        $Response = array(
            'Success' => true,
            'ApiMsg' => __('apicodes.' . $APICode->IDApiCode),
            'ApiCode' => $APICode->IDApiCode,
            'Response' => $Response,
        );
        return $Response;
    }

    public function BranchAdd(Request $request)
    {
        $Admin = auth('user')->user();
        $IDBrand = $request->IDBrand;
        $IDArea = $request->IDArea;
        $BranchAddressEn = $request->BranchAddressEn;
        $BranchAddressAr = $request->BranchAddressAr;
        $BranchLatitude = $request->BranchLatitude;
        $BranchLongitude = $request->BranchLongitude;
        $BranchPhone = $request->BranchPhone;

        $UserName = $request->UserName;
        $UserPassword = $request->UserPassword;
        $UserPhone = $request->UserPhone;
        $UserPhoneFlag = $request->UserPhoneFlag;

        if (!$IDBrand) {
            return RespondWithBadRequest(1);
        }
        if (!$IDArea) {
            return RespondWithBadRequest(1);
        }
        if (!$BranchAddressEn) {
            return RespondWithBadRequest(1);
        }
        if (!$BranchAddressAr) {
            return RespondWithBadRequest(1);
        }
        if (!$BranchLatitude) {
            return RespondWithBadRequest(1);
        }
        if (!$BranchLongitude) {
            return RespondWithBadRequest(1);
        }
        if (!$BranchPhone) {
            return RespondWithBadRequest(1);
        }

        if ($UserName) {
            if (!$UserPhone) {
                return RespondWithBadRequest(1);
            }
            if (!$UserPhoneFlag) {
                return RespondWithBadRequest(1);
            }
            if (!$UserPassword) {
                return RespondWithBadRequest(1);
            }
            $UserRecord = User::where("UserPhone", $UserPhone)->where("UserDeleted", 0)->first();
            if ($UserRecord) {
                return RespondWithBadRequest(3);
            }
        }

        $Branch = new Branch;
        $Branch->IDBrand = $IDBrand;
        $Branch->IDArea = $IDArea;
        $Branch->BranchAddressEn = $BranchAddressEn;
        $Branch->BranchAddressAr = $BranchAddressAr;
        $Branch->BranchLatitude = $BranchLatitude;
        $Branch->BranchLongitude = $BranchLongitude;
        $Branch->BranchPhone = $BranchPhone;
        $Branch->BranchStatus = "PENDING";
        $Branch->save();

        if ($UserName) {
            $User = new User;
            $User->IDBrand = $IDBrand;
            $User->IDBranch = $Branch->IDBranch;
            $User->IDRole = 2;
            $User->UserName = $UserName;
            $User->UserPhone = $UserPhone;
            $User->UserPhoneFlag = $UserPhoneFlag;
            $User->UserLanguage = "ar";
            $User->UserStatus = "PENDING";
            $User->UserPassword = Hash::make($UserPassword);
            $User->save();
        }

        $Desc = "Branch " . $BranchAddressEn . " was added";
        ActionBackLog($Admin->IDUser, $Branch->IDBranch, "ADD_BRANCH", $Desc);
        return RespondWithSuccessRequest(8);
    }

    public function BranchStatus(Request $request)
    {
        $Admin = auth('user')->user();
        $IDBranch = $request->IDBranch;
        $BranchStatus = $request->BranchStatus;


        if (!$IDBranch) {
            return RespondWithBadRequest(1);
        }
        if (!$BranchStatus) {
            return RespondWithBadRequest(1);
        }

        $Branch = Branch::find($IDBranch);
        if (!$Branch) {
            return RespondWithBadRequest(1);
        }
        $Desc = "Branch status changed from " . $Branch->BranchStatus . " to " . $BranchStatus;
        $Branch->BranchStatus = $BranchStatus;
        $Branch->save();

        $BranchUser = User::where("IDBranch", $IDBranch)->first();
        if ($BranchUser) {
            $BranchUser->UserStatus = $BranchStatus;
            $BranchUser->save();
        }

        ActionBackLog($Admin->IDUser, $Branch->IDBranch, "EDIT_BRANCH", $Desc);
        return RespondWithSuccessRequest(8);
    }

    public function BranchEditPage($IDBranch)
    {
        $Branch = Branch::leftjoin("areas", "areas.IDArea", "branches.IDArea")->leftjoin("cities", "cities.IDCity", "areas.IDCity")->leftjoin("brands", "brands.IDBrand", "branches.IDBrand")->where("branches.IDBranch", $IDBranch)->first();
        if (!$Branch) {
            return RespondWithBadRequest(1);
        }

        $BranchUser = User::where("IDBranch", $IDBranch)->first();

        $User = auth('user')->user();
        if ($User) {
            $UserLanguage = AdminLanguage($User->UserLanguage);
            $AreaName = "AreaName" . $UserLanguage;
            $CityName = "CityName" . $UserLanguage;
            $BrandName = "BrandName" . $UserLanguage;
        } else {
            $CityName = "CityNameEn";
            $AreaName = "AreaNameEn";
            $BrandName = "BrandNameEn";
        }

        $Branch->BrandName = $Branch->$BrandName;
        $Branch->CityName = $Branch->$CityName;
        $Branch->AreaName = $Branch->$AreaName;
        $Branch->BranchUser = $BranchUser;

        unset($Branch['CityNameEn']);
        unset($Branch['CityNameAr']);
        unset($Branch['AreaNameEn']);
        unset($Branch['AreaNameAr']);
        unset($Branch['BrandNameEn']);
        unset($Branch['BrandNameAr']);
        unset($Branch['BrandDescEn']);
        unset($Branch['BrandDescAr']);
        unset($Branch['BrandLogo']);
        unset($Branch['BrandNumber']);
        unset($Branch['BrandEmail']);
        unset($Branch['BrandContactName']);
        unset($Branch['BrandRating']);
        unset($Branch['BrandStatus']);
        unset($Branch['CityCode']);
        unset($Branch['CityActive']);
        unset($Branch['IDCountry']);
        unset($Branch['AreaActive']);

        $APICode = APICode::where('IDAPICode', 8)->first();
        $Response = array(
            'Success' => true,
            'ApiMsg' => __('apicodes.' . $APICode->IDApiCode),
            'ApiCode' => $APICode->IDApiCode,
            'Response' => $Branch,
        );
        return $Response;
    }

    public function BranchEdit(Request $request)
    {
        $Admin = auth('user')->user();
        $IDBranch = $request->IDBranch;
        $IDArea = $request->IDArea;
        $BranchAddressEn = $request->BranchAddressEn;
        $BranchAddressAr = $request->BranchAddressAr;
        $BranchLatitude = $request->BranchLatitude;
        $BranchLongitude = $request->BranchLongitude;
        $BranchPhone = $request->BranchPhone;
        $Desc = "";

        $UserName = $request->UserName;
        $UserPassword = $request->UserPassword;
        $UserPhone = $request->UserPhone;
        $UserPhoneFlag = $request->UserPhoneFlag;

        $Branch = Branch::find($IDBranch);
        if (!$Branch) {
            return RespondWithBadRequest(1);
        }

        $BranchUser = User::where("IDBranch", $IDBranch)->first();

        if ($IDArea) {
            $Desc = "Branch area changed";
            $Branch->IDArea = $IDArea;
        }
        if ($BranchAddressEn) {
            $Desc = $Desc . ", Branch english address changed from " . $Branch->BranchAddressEn . " to " . $BranchAddressEn;
            $Branch->BranchAddressEn = $BranchAddressEn;
        }
        if ($BranchAddressAr) {
            $Desc = $Desc . ", Branch arabic address changed from " . $Branch->BranchAddressAr . " to " . $BranchAddressAr;
            $Branch->BranchAddressAr = $BranchAddressAr;
        }
        if ($BranchLatitude) {
            $Desc = $Desc . ", Branch latitude changed from " . $Branch->BranchLatitude . " to " . $BranchLatitude;
            $Branch->BranchLatitude = $BranchLatitude;
        }
        if ($BranchLongitude) {
            $Desc = $Desc . ", Branch longitude changed from " . $Branch->BranchLongitude . " to " . $BranchLongitude;
            $Branch->BranchLongitude = $BranchLongitude;
        }
        if ($BranchPhone) {
            $Desc = $Desc . ", Branch phone changed from " . $Branch->BranchPhone . " to " . $BranchPhone;
            $Branch->BranchPhone = $BranchPhone;
        }

        if ($BranchUser) {
            if ($UserPhone) {
                $UserRecord = User::where("UserPhone", $UserPhone)->where("UserDeleted", 0)->where("IDUser", "<>", $BranchUser->IDUser)->first();
                if ($UserRecord) {
                    return RespondWithBadRequest(3);
                }
                $BranchUser->UserPhone = $UserPhone;
            }
            if ($UserPhoneFlag) {
                $BranchUser->UserPhoneFlag = $UserPhoneFlag;
            }
            if ($UserName) {
                $BranchUser->UserName = $UserName;
            }
            if ($UserPassword) {
                $BranchUser->UserPassword = Hash::make($UserPassword);
            }
            $BranchUser->UserStatus = "PENDING";
            $BranchUser->save();
        }

        $Branch->BranchStatus = "PENDING";
        $Branch->save();

        ActionBackLog($Admin->IDUser, $Branch->IDBranch, "EDIT_BRANCH", $Desc);
        return RespondWithSuccessRequest(8);
    }

    public function BranchAjax(Request $request, Branch $Branches)
    {
        $IDBrand = $request->IDBrand;
        $Branches = $Branches->leftjoin("areas", "areas.IDArea", "branches.IDArea")->leftjoin("cities", "cities.IDCity", "areas.IDCity")->leftjoin("brands", "brands.IDBrand", "branches.IDBrand")->where("branches.BranchStatus", "ACTIVE");
        if ($IDBrand) {
            $Branches = $Branches->where("branches.IDBrand", $IDBrand);
        }
        $Branches = $Branches->get();
        $Branches = BranchResource::collection($Branches);

        $APICode = APICode::where('IDAPICode', 8)->first();
        $Response = array(
            'Success' => true,
            'ApiMsg' => __('apicodes.' . $APICode->IDApiCode),
            'ApiCode' => $APICode->IDApiCode,
            'Response' => $Branches,
        );
        return $Response;
    }

    public function BrandSocialMedia(Request $request)
    {
        $IDBrand = $request->IDBrand;
        if (!$IDBrand) {
            return RespondWithBadRequest(1);
        }

        $SocialMedia = SocialMedia::where("SocialMediaActive", 1)->select("IDSocialMedia", "SocialMediaName", "SocialMediaIcon")->get();
        foreach ($SocialMedia as $Media) {
            $BrandSocialMedia = BrandSocialMedia::where("IDBrand", $IDBrand)->where("IDSocialMedia", $Media->IDSocialMedia)->select("BrandSocialMediaLinked", "BrandSocialMediaLink")->first();
            if ($BrandSocialMedia) {
                $Media->BrandSocialMediaLinked = $BrandSocialMedia->BrandSocialMediaLinked;
                $Media->BrandSocialMediaLink = $BrandSocialMedia->BrandSocialMediaLink;
            } else {
                $Media->BrandSocialMediaLinked = 0;
                $Media->BrandSocialMediaLink = "";
            }
            $Media->SocialMediaIcon = ($Media->SocialMediaIcon) ? asset($Media->SocialMediaIcon) : '';
        }

        $APICode = APICode::where('IDAPICode', 8)->first();
        $Response = array(
            'Success' => true,
            'ApiMsg' => __('apicodes.' . $APICode->IDApiCode),
            'ApiCode' => $APICode->IDApiCode,
            'Response' => $SocialMedia,
        );
        return $Response;
    }

    public function BrandSocialMediaStatus(Request $request)
    {
        $Admin = auth('user')->user();
        $IDBrand = $request->IDBrand;
        $IDSocialMedia = $request->IDSocialMedia;
        $BrandSocialMediaLink = $request->BrandSocialMediaLink;
        if (!$IDBrand) {
            return RespondWithBadRequest(1);
        }
        if (!$IDSocialMedia) {
            return RespondWithBadRequest(1);
        }

        $BrandSocialMedia = BrandSocialMedia::where("IDBrand", $IDBrand)->where("IDSocialMedia", $IDSocialMedia)->first();
        if ($BrandSocialMedia) {
            $BrandSocialMedia->BrandSocialMediaLinked = !$BrandSocialMedia->BrandSocialMediaLinked;
            $BrandSocialMedia->save();
            $Desc = "social media status changed ";
        } else {
            $BrandSocialMedia = new BrandSocialMedia;
            $BrandSocialMedia->IDBrand = $IDBrand;
            $BrandSocialMedia->IDSocialMedia = $IDSocialMedia;
            $BrandSocialMedia->BrandSocialMediaLink = $BrandSocialMediaLink;
            $BrandSocialMedia->save();
            $Desc = "social media added to brand with link " . $BrandSocialMediaLink;
        }

        ActionBackLog($Admin->IDUser, $BrandSocialMedia->IDBrand, "EDIT_BRAND_SOCIAL", $Desc);
        return RespondWithSuccessRequest(8);
    }

    public function BrandProductList(Request $request, BrandProduct $BrandProducts)
    {
        $User = auth('user')->user();
        $IDPage = $request->IDPage;
        $SearchKey = $request->SearchKey;
        $IDSubCategory = $request->IDSubCategory;
        $IDBrand = $request->IDBrand;
        $BrandProductStatus = $request->BrandProductStatus;
        $BrandProductStartDate = $request->BrandProductStartDate;
        $BrandProductEndDate = $request->BrandProductEndDate;

        if (!$IDPage) {
            $IDPage = 0;
        } else {
            $IDPage = ($request->IDPage - 1) * 20;
        }

        $BrandProducts = $BrandProducts->leftjoin("subcategories", "subcategories.IDSubCategory", "brandproducts.IDSubCategory")->leftjoin("brands", "brands.IDBrand", "brandproducts.IDBrand");
        if ($SearchKey) {
            $BrandProducts = $BrandProducts->where(function ($query) use ($SearchKey) {
                $query->where('brandproducts.BrandProductTitleEn', 'like', '%' . $SearchKey . '%')
                    ->orwhere('brandproducts.BrandProductTitleAr', 'like', '%' . $SearchKey . '%')
                    ->orwhere('brandproducts.BrandProductDescEn', 'like', '%' . $SearchKey . '%')
                    ->orwhere('brandproducts.BrandProductDescAr', 'like', '%' . $SearchKey . '%');
            });
        }
        if ($IDBrand) {
            $BrandProducts = $BrandProducts->where("brandproducts.IDBrand", $IDBrand);
        }
        if ($IDSubCategory) {
            $BrandProducts = $BrandProducts->where("brandproducts.IDSubCategory", $IDSubCategory);
        }
        if ($BrandProductStatus) {
            $BrandProducts = $BrandProducts->where("brandproducts.BrandProductStatus", $BrandProductStatus);
        }
        if ($BrandProductStartDate) {
            $BrandProducts = $BrandProducts->where("brandproducts.BrandProductStartDate", ">=", $BrandProductStartDate);
        }
        if ($BrandProductEndDate) {
            $BrandProducts = $BrandProducts->where("brandproducts.BrandProductStartDate", "<=", $BrandProductEndDate);
        }
        if ($User->IDRole == 2) {
            $BrandProducts = $BrandProducts->where("brands.IDBrand", $User->IDBrand);
        }

        $BrandProducts = $BrandProducts->select("brandproducts.IDBrandProduct", "brandproducts.BrandProductTitleEn", "brandproducts.BrandProductTitleAr", "brandproducts.BrandProductDescEn", "brandproducts.BrandProductDescAr", "brandproducts.BrandProductPrice", "brandproducts.BrandProductDiscount", "brandproducts.BrandProductDiscountType", "brandproducts.BrandProductPoints", "brandproducts.BrandProductUplinePoints", "brandproducts.BrandProductReferralPoints", "brandproducts.BrandProductStatus", "brandproducts.BrandProductStartDate", "brandproducts.BrandProductEndDate", "brandproducts.created_at", "brands.BrandNameEn", "brands.BrandNameAr", "subcategories.SubCategoryNameEn", "subcategories.SubCategoryNameAr");

        $Pages = ceil($BrandProducts->count() / 20);
        $BrandProducts = $BrandProducts->skip($IDPage)->take(20)->get();
        $BrandProducts = BrandProductResource::collection($BrandProducts);
        $Response = array("BrandProducts" => $BrandProducts, "Pages" => $Pages);

        $APICode = APICode::where('IDAPICode', 8)->first();
        $Response = array(
            'Success' => true,
            'ApiMsg' => __('apicodes.' . $APICode->IDApiCode),
            'ApiCode' => $APICode->IDApiCode,
            'Response' => $Response,
        );
        return $Response;
    }

    public function BrandProductAdd(Request $request)
    {
        $User = auth('user')->user();
        $IDBrand = $request->IDBrand;
        $IDSubCategory = $request->IDSubCategory;
        $IDBranch = $request->IDBranch;
        $BrandProductTitleEn = $request->BrandProductTitleEn;
        $BrandProductTitleAr = $request->BrandProductTitleAr;
        $BrandProductDescEn = $request->BrandProductDescEn;
        $BrandProductDescAr = $request->BrandProductDescAr;

        $BrandProductPrice = $request->BrandProductPrice;
        $BrandProductDiscount = $request->BrandProductDiscount;
        $BrandProductDiscountType = $request->BrandProductDiscountType;

        $BrandProductPoints = $request->BrandProductPoints;
        $BrandProductUplinePoints = $request->BrandProductUplinePoints;
        $BrandProductReferralPoints = $request->BrandProductReferralPoints;
        $BrandProductStartDate = $request->BrandProductStartDate;
        $BrandProductEndDate = $request->BrandProductEndDate;
        $BrandProductGallery = $request->BrandProductGallery;

        $BrandProductInvoiceMin = $request->BrandProductInvoiceMin;
        $BrandProductMaxDiscount = $request->BrandProductMaxDiscount;

        // if ($User->IDRole != 1) {
        //     $IDBrand = $User->IDBrand;
        // }
        if (!$IDBrand) {
            return RespondWithBadRequest(1);
        }
        if (!$IDSubCategory) {
            return RespondWithBadRequest(1);
        }
        if (!$BrandProductTitleEn) {
            return RespondWithBadRequest(1);
        }
        if (!$BrandProductTitleAr) {
            return RespondWithBadRequest(1);
        }
        if ($BrandProductDiscountType === "INVOICE") {
            $BrandProductPrice = 0;
        } else {
            if (!$BrandProductPrice) {
                return RespondWithBadRequest(1);
            }
        }
        if (!$BrandProductDiscount) {
            return RespondWithBadRequest(1);
        }
        if (!$BrandProductDiscountType) {
            return RespondWithBadRequest(1);
        }
        if ($BrandProductDiscountType === "INVOICE") {
            if (!$BrandProductInvoiceMin) {
                return RespondWithBadRequest(1);
            }
            if (!$BrandProductMaxDiscount) {
                return RespondWithBadRequest(1);
            }
        }
        if (!$BrandProductPoints) {
            return RespondWithBadRequest(1);
        }
        if (!$BrandProductReferralPoints) {
            $BrandProductReferralPoints = 0;
        }
        if (!$BrandProductUplinePoints) {
            $BrandProductUplinePoints = 0;
        }

        $ImageExtArray = ["jpeg", "jpg", "png", "svg"];
        if ($BrandProductGallery) {
            foreach ($BrandProductGallery as $Photo) {
                if (!in_array($Photo->extension(), $ImageExtArray)) {
                    return RespondWithBadRequest(15);
                }
            }
        }

        $BrandContract = BrandContract::where("IDBrand", $IDBrand)->where("BrandContractStatus", "ACTIVE")->first();

        $BrandProduct = new BrandProduct;
        $BrandProduct->IDBrand = $IDBrand;
        $BrandProduct->IDSubCategory = $IDSubCategory;
        $BrandProduct->BrandProductTitleEn = $BrandProductTitleEn;
        $BrandProduct->BrandProductTitleAr = $BrandProductTitleAr;
        $BrandProduct->BrandProductDescEn = $BrandProductDescEn;
        $BrandProduct->BrandProductDescAr = $BrandProductDescAr;
        $BrandProduct->BrandProductPrice = $BrandProductPrice;
        $BrandProduct->BrandProductDiscount = $BrandProductDiscount;
        $BrandProduct->BrandProductDiscountType = $BrandProductDiscountType;

        if ($BrandProductDiscountType === "INVOICE") {
            $BrandProduct->BrandProductMaxDiscount = $BrandProductMaxDiscount;
            $BrandProduct->BrandProductInvoiceMin = $BrandProductInvoiceMin;
        }
        $BrandProduct->BrandProductPoints = $BrandProductPoints;
        $BrandProduct->BrandProductUplinePoints = $BrandProductUplinePoints;
        $BrandProduct->BrandProductReferralPoints = $BrandProductReferralPoints;
        $BrandProduct->BrandProductStartDate = $BrandProductStartDate;
        $BrandProduct->BrandProductEndDate = $BrandProductEndDate;
        $BrandProduct->BrandProductStatus = "PENDING";
        $BrandProduct->save();

        if ($BrandProductGallery) {
            foreach ($BrandProductGallery as $Photo) {
                $Image = SaveImage($Photo, "brandproducts", $BrandProduct->IDBrandProduct);
                $BrandProductGallery = new BrandProductGallery;
                $BrandProductGallery->IDBrandProduct = $BrandProduct->IDBrandProduct;
                $BrandProductGallery->BrandProductPath = $Image;
                $BrandProductGallery->BrandProductType = "IMAGE";
                $BrandProductGallery->save();
            }
        }

        $Desc = "Brand product " . $BrandProductTitleEn . " was added";
        ActionBackLog($User->IDUser, $BrandProduct->IDBrandProduct, "ADD_BRAND_PRODUCT", $Desc);
        return RespondWithSuccessRequest(8);
    }

    public function BrandProductStatus(Request $request)
    {
        $Admin = auth('user')->user();
        $IDBrandProduct = $request->IDBrandProduct;
        $BrandProductStatus = $request->BrandProductStatus;

        if (!$IDBrandProduct) {
            return RespondWithBadRequest(1);
        }
        if (!$BrandProductStatus) {
            return RespondWithBadRequest(1);
        }

        $BrandProduct = BrandProduct::find($IDBrandProduct);
        if (!$BrandProduct) {
            return RespondWithBadRequest(1);
        }
        $Desc = "Brand Product status changed from " . $BrandProduct->BrandProductStatus . " to " . $BrandProductStatus;
        $BrandProduct->BrandProductStatus = $BrandProductStatus;
        $BrandProduct->save();

        ActionBackLog($Admin->IDUser, $BrandProduct->IDBrandProduct, "EDIT_BRAND_PRODUCT", $Desc);
        return RespondWithSuccessRequest(8);
    }

    public function BrandProductEditPage($IDBrandProduct)
    {
        $User = auth('user')->user();
        $BrandProduct = BrandProduct::leftjoin("subcategories", "subcategories.IDSubCategory", "brandproducts.IDSubCategory")->leftjoin("brands", "brands.IDBrand", "brandproducts.IDBrand")->where("brandproducts.IDBrandProduct", $IDBrandProduct);
        if ($User->IDBrand && $User->IDBrand != 1) {
            $BrandProduct = $BrandProduct->where("brandproducts.IDBrand", $User->IDBrand);
        }
        $BrandProduct = $BrandProduct->select("brandproducts.IDBrandProduct", "brandproducts.BrandProductTitleEn", "brandproducts.BrandProductDiscountType", "brandproducts.BrandProductInvoiceMin", "brandproducts.BrandProductMaxDiscount", "brandproducts.BrandProductTitleAr", "brandproducts.BrandProductDescEn", "brandproducts.BrandProductDescAr", "brandproducts.BrandProductPrice", "brandproducts.BrandProductDiscount", "brandproducts.BrandProductDiscountType", "brandproducts.BrandProductPoints", "brandproducts.BrandProductUplinePoints", "brandproducts.BrandProductReferralPoints", "brandproducts.BrandProductStatus", "brandproducts.BrandProductStartDate", "brandproducts.BrandProductEndDate", "brandproducts.created_at", "brands.BrandNameEn", "brands.BrandNameAr", "subcategories.SubCategoryNameEn", "subcategories.SubCategoryNameAr")->first();
        if (!$BrandProduct) {
            return RespondWithBadRequest(1);
        }

        $BrandProductGallery = BrandProductGallery::where("IDBrandProduct", $IDBrandProduct)->where("BrandProductDeleted", 0)->get();
        foreach ($BrandProductGallery as $Gallery) {
            if ($Gallery->BrandProductType == "IMAGE") {
                $Gallery->BrandProductPath = ($Gallery->BrandProductPath) ? asset($Gallery->BrandProductPath) : '';
            }
        }

        $BrandProduct->BrandProductGallery = $BrandProductGallery;

        $APICode = APICode::where('IDAPICode', 8)->first();
        $Response = array(
            'Success' => true,
            'ApiMsg' => __('apicodes.' . $APICode->IDApiCode),
            'ApiCode' => $APICode->IDApiCode,
            'Response' => $BrandProduct,
        );
        return $Response;
    }

    public function BrandProductGalleryRemove($IDBrandProductGallery)
    {
        $Admin = auth('user')->user();
        $BrandProductGallery = BrandProductGallery::find($IDBrandProductGallery);
        if (!$BrandProductGallery) {
            return RespondWithBadRequest(1);
        }

        if ($BrandProductGallery->BrandProductType == "IMAGE") {
            $OldDocument = substr($BrandProductGallery->BrandProductPath, 7);
            Storage::disk('uploads')->delete($OldDocument);
        }

        $BrandProductGallery->BrandProductDeleted = 1;
        $BrandProductGallery->save();

        $Desc = "Brand Product Gallery Removed";
        ActionBackLog($Admin->IDUser, $BrandProductGallery->IDBrandProduct, "EDIT_BRAND_PRODUCT", $Desc);
        return RespondWithSuccessRequest(8);
    }

    public function BrandProductAjax(Request $request)
    {
        $User = auth('user')->user();
        $SearchKey = $request->SearchKey;
        $IDSubCategory = $request->IDSubCategory;
        $IDBrand = $request->IDBrand;

        $BrandProducts = BrandProduct::leftjoin("subcategories", "subcategories.IDSubCategory", "brandproducts.IDSubCategory")->leftjoin("brands", "brands.IDBrand", "brandproducts.IDBrand");
        if ($SearchKey) {
            $BrandProducts = $BrandProducts->where(function ($query) use ($SearchKey) {
                $query->where('brandproducts.BrandProductTitleEn', 'like', '%' . $SearchKey . '%')
                    ->orwhere('brandproducts.BrandProductTitleAr', 'like', '%' . $SearchKey . '%')
                    ->orwhere('brandproducts.BrandProductDescEn', 'like', '%' . $SearchKey . '%')
                    ->orwhere('brandproducts.BrandProductDescAr', 'like', '%' . $SearchKey . '%');
            });
        }
        if ($IDBrand) {
            $BrandProducts = $BrandProducts->where("brandproducts.IDBrand", $IDBrand);
        }
        if ($IDSubCategory) {
            $BrandProducts = $BrandProducts->where("brandproducts.IDSubCategory", $IDSubCategory);
        }
        $BrandProducts = $BrandProducts->where("brandproducts.BrandProductStatus", "ACTIVE");
        $BrandProducts = $BrandProducts->select("brandproducts.IDBrandProduct", "brandproducts.BrandProductTitleEn", "brandproducts.BrandProductTitleAr", "brandproducts.BrandProductDescEn", "brandproducts.BrandProductDescAr", "brandproducts.BrandProductPrice", "brandproducts.BrandProductDiscount", "brandproducts.BrandProductDiscountType", "brandproducts.BrandProductPoints", "brandproducts.BrandProductUplinePoints", "brandproducts.BrandProductReferralPoints", "brandproducts.BrandProductStatus", "brandproducts.BrandProductStartDate", "brandproducts.BrandProductEndDate", "brandproducts.created_at", "brands.BrandNameEn", "brands.BrandNameAr", "subcategories.SubCategoryNameEn", "subcategories.SubCategoryNameAr");
        $BrandProducts = $BrandProducts->get();
        $BrandProducts = BrandProductResource::collection($BrandProducts);

        $APICode = APICode::where('IDAPICode', 8)->first();
        $Response = array(
            'Success' => true,
            'ApiMsg' => __('apicodes.' . $APICode->IDApiCode),
            'ApiCode' => $APICode->IDApiCode,
            'Response' => $BrandProducts,
        );
        return $Response;
    }

    public function BrandProductBranches(Request $request)
    {
        $User = auth('user')->user();
        $UserLanguage = AdminLanguage($User->UserLanguage);
        $IDBrandProduct = $request->IDBrandProduct;
        if (!$IDBrandProduct) {
            return RespondWithBadRequest(1);
        }

        $BrandProduct = BrandProduct::find($IDBrandProduct);
        if (!$BrandProduct) {
            return RespondWithBadRequest(1);
        }

        $BrandProductBranches = [];
        $AreaName = "AreaName" . $UserLanguage;
        $BranchAddress = "BranchAddress" . $UserLanguage;
        $Branches = Branch::leftjoin("areas", "areas.IDArea", "branches.IDArea")->where("branches.IDBrand", $BrandProduct->IDBrand)->where("branches.BranchStatus", "ACTIVE")->get();
        foreach ($Branches as $Branch) {
            $ProductBranchStatus = 0;
            $BrandProductBranch = BrandProductBranch::where("IDBrandProduct", $IDBrandProduct)->where("IDBranch", $Branch->IDBranch)->first();
            if ($BrandProductBranch) {
                if ($BrandProductBranch->ProductBranchLinked) {
                    $ProductBranchStatus = 1;
                }
            }

            $ProductBranch = ["IDBranch" => $Branch->IDBranch, "AreaName" => $Branch->$AreaName, "BranchAddress" => $Branch->$BranchAddress, "ProductBranchStatus" => $ProductBranchStatus];
            array_push($BrandProductBranches, $ProductBranch);
        }

        $APICode = APICode::where('IDAPICode', 8)->first();
        $Response = array(
            'Success' => true,
            'ApiMsg' => __('apicodes.' . $APICode->IDApiCode),
            'ApiCode' => $APICode->IDApiCode,
            'Response' => $BrandProductBranches,
        );
        return $Response;
    }

    public function BrandProductBranchStatus(Request $request)
    {
        $User = auth('user')->user();
        $IDBranch = $request->IDBranch;
        $IDBrandProduct = $request->IDBrandProduct;
        if (!$IDBrandProduct) {
            return RespondWithBadRequest(1);
        }
        if (!$IDBranch) {
            return RespondWithBadRequest(1);
        }

        $BrandProductBranch = BrandProductBranch::where("IDBrandProduct", $IDBrandProduct)->where("IDBranch", $IDBranch)->first();
        if ($BrandProductBranch) {
            $BrandProductBranch->ProductBranchLinked = !$BrandProductBranch->ProductBranchLinked;
            $BrandProductBranch->save();
            return RespondWithSuccessRequest(8);
        }

        $BrandProductBranch = new BrandProductBranch;
        $BrandProductBranch->IDBranch = $IDBranch;
        $BrandProductBranch->IDBrandProduct = $IDBrandProduct;
        $BrandProductBranch->save();

        return RespondWithSuccessRequest(8);
    }

    public function BrandRatingList(Request $request, BrandRating $BrandRatings)
    {
        $IDBrand = $request->IDBrand;
        $IDPage = $request->IDPage;
        $BrandRatingStatus = $request->BrandRatingStatus;
        if (!$IDPage) {
            $IDPage = 0;
        } else {
            $IDPage = ($request->IDPage - 1) * 20;
        }

        $BrandRatings = $BrandRatings->leftjoin("clients", "clients.IDClient", "brandratings.IDClient")->leftjoin("brands", "brands.IDBrand", "brandratings.IDBrand");
        if ($BrandRatingStatus) {
            $BrandRatings = $BrandRatings->where("brandratings.BrandRatingStatus", $BrandRatingStatus);
        }
        if ($IDBrand) {
            $BrandRatings = $BrandRatings->where("brandratings.IDBrand", $IDBrand);
        }

        $BrandRatings = $BrandRatings->select("brandratings.IDBrandRating", "brandratings.IDBrand", "brandratings.BrandRating", "brandratings.BrandReview", "brandratings.BrandRatingStatus", "brandratings.created_at", "clients.ClientName", "clients.ClientPhone", "brands.BrandNameEn", "brands.BrandNameAr");

        $Pages = ceil($BrandRatings->count() / 20);
        $BrandRatings = $BrandRatings->orderby("brandratings.IDBrandRating", "DESC")->skip($IDPage)->take(20)->get();
        $BrandRatings = BrandRatingResource::collection($BrandRatings);
        $Response = array("BrandRatings" => $BrandRatings, "Pages" => $Pages);

        $APICode = APICode::where('IDAPICode', 8)->first();
        $Response = array(
            'Success' => true,
            'ApiMsg' => __('apicodes.' . $APICode->IDApiCode),
            'ApiCode' => $APICode->IDApiCode,
            'Response' => $Response,
        );
        return $Response;
    }

    public function BrandRatingStatus(Request $request)
    {
        $Admin = auth('user')->user();
        $IDBrandRating = $request->IDBrandRating;
        $BrandRatingStatus = $request->BrandRatingStatus;
        if (!$IDBrandRating) {
            return RespondWithBadRequest(1);
        }
        if (!$BrandRatingStatus) {
            return RespondWithBadRequest(1);
        }

        $BrandRating = BrandRating::find($IDBrandRating);
        $Desc = "Brand rating changed from " . $BrandRating->BrandRatingStatus . " to " . $BrandRatingStatus;
        $BrandRating->BrandRatingStatus = $BrandRatingStatus;
        $BrandRating->save();

        ActionBackLog($Admin->IDUser, $BrandRating->IDBrandRating, "EDIT_BRAND_RATING", $Desc);
        return RespondWithSuccessRequest(8);
    }

    public function BrandProductEdit(Request $request)
    {
        $Admin = auth('user')->user();
        $IDBrandProduct = $request->IDBrandProduct;
        $BrandProductTitleEn = $request->BrandProductTitleEn;
        $BrandProductTitleAr = $request->BrandProductTitleAr;
        $BrandProductDescEn = $request->BrandProductDescEn;
        $BrandProductDescAr = $request->BrandProductDescAr;

        $BrandProductPrice = $request->BrandProductPrice;
        $BrandProductDiscount = $request->BrandProductDiscount;
        $BrandProductDiscountType = $request->BrandProductDiscountType;

        $BrandProductPoints = $request->BrandProductPoints;
        $BrandProductUplinePoints = $request->BrandProductUplinePoints;
        $BrandProductReferralPoints = $request->BrandProductReferralPoints;
        $BrandProductStartDate = $request->BrandProductStartDate;
        $BrandProductEndDate = $request->BrandProductEndDate;
        $BrandProductGallery = $request->BrandProductGallery;
        $Desc = "";

        $BrandProduct = BrandProduct::find($IDBrandProduct);
        if (!$BrandProduct) {
            return RespondWithBadRequest(1);
        }

        $BrandProductInvoiceMin = $request->BrandProductInvoiceMin;
        $BrandProductMaxDiscount = $request->BrandProductMaxDiscount;

        if ($BrandProductDiscountType === "INVOICE") {
            $BrandProduct->BrandProductPrice = 0;
        } else {
            if (!$BrandProductPrice) {
                return RespondWithBadRequest(1);
            }
        }
        if ($BrandProductDiscountType === "INVOICE") {
            if (!$BrandProductInvoiceMin) {
                return RespondWithBadRequest(1);
            }
            if (!$BrandProductMaxDiscount) {
                return RespondWithBadRequest(1);
            }
        }
        if ($BrandProductDiscountType === "INVOICE") {
            $BrandProduct->BrandProductMaxDiscount = $BrandProductMaxDiscount;
            $BrandProduct->BrandProductInvoiceMin = $BrandProductInvoiceMin;
        } else {
            $BrandProduct->BrandProductMaxDiscount = null;
            $BrandProduct->BrandProductInvoiceMin = null;
        }

        $ImageExtArray = ["jpeg", "jpg", "png", "svg"];
        if ($BrandProductGallery) {
            foreach ($BrandProductGallery as $Photo) {
                if (!in_array($Photo->extension(), $ImageExtArray)) {
                    return RespondWithBadRequest(15);
                }
            }
        }

        if ($BrandProductTitleEn) {
            $Desc = "Brand Product english title changed from " . $BrandProduct->BrandProductTitleEn . " to " . $BrandProductTitleEn;
            $BrandProduct->BrandProductTitleEn = $BrandProductTitleEn;
        }
        if ($BrandProductTitleAr) {
            $Desc = $Desc . ", Brand Product arabic title changed from " . $BrandProduct->BrandProductTitleAr . " to " . $BrandProductTitleAr;
            $BrandProduct->BrandProductTitleAr = $BrandProductTitleAr;
        }
        if ($BrandProductDescEn) {
            $Desc = $Desc . ", Brand Product english desc changed from " . $BrandProduct->BrandProductDescEn . " to " . $BrandProductDescEn;
            $BrandProduct->BrandProductDescEn = $BrandProductDescEn;
        }
        if ($BrandProductDescAr) {
            $Desc = $Desc . ", Brand Product arabic desc changed from " . $BrandProduct->BrandProductDescAr . " to " . $BrandProductDescAr;
            $BrandProduct->BrandProductDescAr = $BrandProductDescAr;
        }
        if ($BrandProductPoints) {
            $Desc = $Desc . ", Brand Product points changed from " . $BrandProduct->BrandProductPoints . " to " . $BrandProductPoints;
            $BrandProduct->BrandProductPoints = $BrandProductPoints;
        }
        if ($BrandProductUplinePoints) {
            $Desc = $Desc . ", Brand Product upline points changed from " . $BrandProduct->BrandProductUplinePoints . " to " . $BrandProductUplinePoints;
            $BrandProduct->BrandProductUplinePoints = $BrandProductUplinePoints;
        }
        if ($BrandProductReferralPoints) {
            $Desc = $Desc . ", Brand Product referral points changed from " . $BrandProduct->BrandProductReferralPoints . " to " . $BrandProductReferralPoints;
            $BrandProduct->BrandProductReferralPoints = $BrandProductReferralPoints;
        }
        if ($BrandProductDiscountType !== "INVOICE") {
            if ($BrandProductPrice) {
                $Desc = $Desc . ", Brand Product price changed from " . $BrandProduct->BrandProductPrice . " to " . $BrandProductPrice;
                $BrandProduct->BrandProductPrice = $BrandProductPrice;
            }
        }
        if ($BrandProductDiscount || $BrandProductDiscount == 0) {
            $Desc = $Desc . ", Brand Product discount changed from " . $BrandProduct->BrandProductDiscount . " to " . $BrandProductDiscount;
            $BrandProduct->BrandProductDiscount = $BrandProductDiscount;
        }
        if ($BrandProductDiscountType) {
            $Desc = $Desc . ", Brand Product discount type changed from " . $BrandProduct->BrandProductDiscountType . " to " . $BrandProductDiscountType;
            $BrandProduct->BrandProductDiscountType = $BrandProductDiscountType;
        }
        if ($BrandProductStartDate) {
            $BrandContract = BrandContract::where("IDBrand", $BrandProduct->IDBrand)->whereIn("BrandContractStatus", ["ACTIVE", "PENDING"])->where("BrandContractStartDate", "<=", $BrandProductStartDate)->where("BrandContractEndDate", ">=", $BrandProductStartDate)->first();
            if (!$BrandContract) {
                return RespondWithBadRequest(31);
            }
            $Desc = $Desc . ", Brand Product start date changed from " . $BrandProduct->BrandProductStartDate . " to " . $BrandProductStartDate;
            $BrandProduct->BrandProductStartDate = $BrandProductStartDate;
        }
        if ($BrandProductEndDate) {
            $BrandContract = BrandContract::where("IDBrand", $BrandProduct->IDBrand)->whereIn("BrandContractStatus", ["ACTIVE", "PENDING"])->where("BrandContractStartDate", "<=", $BrandProductEndDate)->where("BrandContractEndDate", ">=", $BrandProductEndDate)->first();
            if (!$BrandContract) {
                return RespondWithBadRequest(31);
            }
            $Desc = $Desc . ", Brand Product end date changed from " . $BrandProduct->BrandProductEndDate . " to " . $BrandProductEndDate;
            $BrandProduct->BrandProductEndDate = $BrandProductEndDate;
        }

        $BrandProduct->BrandProductStatus = "PENDING";
        $BrandProduct->save();

        if ($BrandProductGallery) {
            foreach ($BrandProductGallery as $Photo) {
                $Image = SaveImage($Photo, "brandproducts", $IDBrandProduct);
                $BrandProductGallery = new BrandProductGallery;
                $BrandProductGallery->IDBrandProduct = $IDBrandProduct;
                $BrandProductGallery->BrandProductPath = $Image;
                $BrandProductGallery->BrandProductType = "IMAGE";
                $BrandProductGallery->save();
            }
            $Desc = $Desc . ", Brand Product gallery added";
        }

        ActionBackLog($Admin->IDUser, $BrandProduct->IDBrandProduct, "EDIT_BRAND_PRODUCT", $Desc);
        return RespondWithSuccessRequest(8);
    }

    public function BrandContactUsList(Request $request, BrandContactUs $BrandContactUs)
    {
        $IDPage = $request->IDPage;
        if (!$IDPage) {
            $IDPage = 0;
        } else {
            $IDPage = ($request->IDPage - 1) * 20;
        }

        $BrandContactUs = $BrandContactUs->leftjoin("users", "users.IDUser", "brandcontactus.IDUser");
        $BrandContactUs = $BrandContactUs->select("brandcontactus.IDBrandContactUs", "brandcontactus.BrandName", "brandcontactus.ClientName", "brandcontactus.Phone", "brandcontactus.Email", "brandcontactus.City", "brandcontactus.Area", "brandcontactus.Address", "brandcontactus.Category", "brandcontactus.Message", "brandcontactus.Latitude", "brandcontactus.Longitude", "brandcontactus.Status", "users.UserName", "brandcontactus.created_at");

        $Pages = ceil($BrandContactUs->count() / 20);
        $BrandContactUs = $BrandContactUs->orderby("IDBrandContactUs", "DESC")->skip($IDPage)->take(20)->get();
        $Response = array("BrandContactUs" => $BrandContactUs, "Pages" => $Pages);

        $APICode = APICode::where('IDAPICode', 8)->first();
        $Response = array(
            'Success' => true,
            'ApiMsg' => __('apicodes.' . $APICode->IDApiCode),
            'ApiCode' => $APICode->IDApiCode,
            'Response' => $Response,
        );
        return $Response;
    }

    public function BrandContactUsStatus($IDBrandContactUs)
    {
        $Admin = auth('user')->user();
        $BrandContactUs = BrandContactUs::find($IDBrandContactUs);
        if (!$BrandContactUs) {
            return RespondWithBadRequest(1);
        }
        if ($BrandContactUs->Status == "READ") {
            return RespondWithBadRequest(1);
        }

        $BrandContactUs->IDUser = $Admin->IDUser;
        $BrandContactUs->Status = "READ";
        $BrandContactUs->save();

        $Desc = "Seen & Read";
        ActionBackLog($Admin->IDUser, $BrandContactUs->IDBrandContactUs, "EDIT_BRAND_CONTACTUS", $Desc);
        return RespondWithSuccessRequest(8);
    }
}
