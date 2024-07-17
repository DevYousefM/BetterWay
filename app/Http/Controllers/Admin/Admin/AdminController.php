<?php

namespace App\Http\Controllers\Admin\Admin;

header('Content-type: application/json');

use Illuminate\Support\Facades\Config;
use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\EventResource;
use App\Http\Resources\Admin\ToolResource;
use App\Http\Resources\Admin\UserResource;
use App\Http\Resources\Admin\CountryResource;
use App\Http\Resources\Admin\CityResource;
use App\Http\Resources\Admin\AreaResource;
use App\Http\Resources\Admin\CategoryResource;
use App\Http\Resources\Admin\SubCategoryResource;
use App\Http\Resources\Admin\AdvertisementResource;
use App\V1\User\User;
use App\Notification;
use App\V1\User\Role;
use App\V1\User\RoleSection;
use App\V1\User\ActionBackLog;
use App\V1\Plan\PlanNetwork;
use App\V1\Client\Client;
use App\V1\General\Section;
use App\V1\General\APICode;
use App\V1\General\Category;
use App\V1\General\Nationality;
use App\V1\General\SocialMedia;
use App\V1\General\SubCategory;
use App\V1\General\ContactUs;
use App\V1\General\Advertisement;
use App\V1\General\GeneralSetting;
use App\V1\Location\Country;
use App\V1\Location\City;
use App\V1\Location\Area;
use App\V1\Event\Event;
use App\V1\Event\EventGallery;
use App\V1\Event\EventAttendee;
use App\V1\Tool\Tool;
use App\V1\Tool\ToolGallery;
use App\V1\Tool\ClientTool;
use App\V1\Payment\CompanyLedger;
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

class AdminController extends Controller
{
    public function AdminLogin(Request $request)
    {
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
                    'password' => $request->Password
                ];
            } else {
                $Credentials = [
                    'UserEmail' => $UserName,
                    'UserDeleted' => 0,
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

        $Source = $request->Dashboard;
        if ($Source == "Admin") {
            if ($User->Role === "Branch" || $User->Role === "Brand") return RespondWithBadRequest(59);
        } else if ($Source == "Brand") {
            if ($User->Role === "Branch" || $User->Role === "Admin") return RespondWithBadRequest(59);
        } else {
            return RespondWithBadRequest(1);
        }
        if ($User->UserStatus == "INACTIVE") {
            return RespondWithBadRequest(6);
        }
        if ($User->UserStatus == "PENDING") {
            return RespondWithBadRequest(36);
        }
        $AdminSessionTimeout = GeneralSetting::where("GeneralSettingName", "AdminSessionTimeout")->first();
        $AdminSessionTimeout = $AdminSessionTimeout->GeneralSettingValue;

        $Success = true;
        $IDAPICode = 7;
        $response_code = 200;
        $APICode = APICode::where('IDAPICode', $IDAPICode)->first();
        $response = array('IDUser' => $User->IDUser, 'IDBrand' => $User->IDBrand, "IDBranch" => $User->IDBranch, 'UserPhone' => $User->UserPhone, 'UserName' => $User->UserName, 'UserEmail' => $User->UserEmail, 'UserStatus' => $User->UserStatus, "UserLanguage" => $User->UserLanguage, "IDRole" => $User->IDRole, "AdminSessionTimeout" => $AdminSessionTimeout, 'AccessToken' => $AccessToken);
        $response_array = array('Success' => $Success, 'ApiMsg' => trans('apicodes.' . $APICode->IDApiCode), 'ApiCode' => $APICode->IDApiCode, 'Response' => $response);
        $response = Response::json($response_array, $response_code);
        return $response;
    }

    public function Roles()
    {
        $User = auth('user')->user();
        if (!$User) {
            return RespondWithBadRequest(10);
        }

        $Roles = Role::all();

        $APICode = APICode::where('IDAPICode', 8)->first();
        $Response = array(
            'Success' => true,
            'ApiMsg' => __('apicodes.' . $APICode->IDApiCode),
            'ApiCode' => $APICode->IDApiCode,
            'Response' => $Roles,
        );
        return $Response;
    }

    public function MyRoleSections()
    {
        $User = auth('user')->user();
        if (!$User) {
            return RespondWithBadRequest(10);
        }

        $Response = [];
        $RoleSections = RoleSection::leftjoin("roles", "roles.IDRole", "rolesections.IDRole")->leftjoin("sections", "sections.IDSection", "rolesections.IDSection")->where("rolesections.IDRole", $User->IDRole)->select("rolesections.IDRoleSection", "sections.SectionNameEn", "sections.Section", "rolesections.RoleSectionStatus")->get();
        foreach ($RoleSections as $RoleSection) {
            $SectionNameEn = strtoupper(str_replace(" ", "_", $RoleSection->SectionNameEn));
            $Response[$SectionNameEn] = $RoleSection->RoleSectionStatus;
        }

        $APICode = APICode::where('IDAPICode', 8)->first();
        $Response = array(
            'Success' => true,
            'ApiMsg' => __('apicodes.' . $APICode->IDApiCode),
            'ApiCode' => $APICode->IDApiCode,
            'Response' => $Response,
        );
        return $Response;
    }

    public function RolesAdd(Request $request)
    {
        $Admin = auth('user')->user();
        if (!$Admin) {
            return RespondWithBadRequest(10);
        }

        $RoleName = $request->RoleName;
        if (!$RoleName) {
            return RespondWithBadRequest(1);
        }

        $Role = Role::where("RoleName", $RoleName)->first();
        if ($Role) {
            return RespondWithBadRequest(18);
        }

        $Role = new Role;
        $Role->RoleName = $RoleName;
        $Role->save();

        $Sections = Section::all();
        foreach ($Sections as $Section) {
            $RoleSection = new RoleSection;
            $RoleSection->IDRole = $Role->IDRole;
            $RoleSection->IDSection = $Section->IDSection;
            $RoleSection->save();
        }

        $Desc = "Role was added";
        ActionBackLog($Admin->IDUser, $Role->IDRole, "ADD_ROLE", $Desc);

        $APICode = APICode::where('IDAPICode', 8)->first();
        $Response = array(
            'Success' => true,
            'ApiMsg' => __('apicodes.' . $APICode->IDApiCode),
            'ApiCode' => $APICode->IDApiCode,
            'Response' => $Role->IDRole,
        );
        return $Response;
    }

    public function RolesEdit(Request $request)
    {
        $Admin = auth('user')->user();
        if (!$Admin) {
            return RespondWithBadRequest(10);
        }

        $IDRole = $request->IDRole;
        $RoleName = $request->RoleName;
        $RoleActive = $request->RoleActive;
        $Desc = "";
        if (!$IDRole) {
            return RespondWithBadRequest(1);
        }
        if (!$RoleName) {
            return RespondWithBadRequest(1);
        }

        $Role = Role::find($IDRole);
        if (!$Role) {
            return RespondWithBadRequest(1);
        }
        if (!$Role->RoleChange) {
            return RespondWithBadRequest(1);
        }

        if ($RoleName != $Role->RoleName) {
            $RoleRow = Role::where("RoleName", $RoleName)->where("IDRole", "<>", $IDRole)->first();
            if ($RoleRow) {
                return RespondWithBadRequest(18);
            }
            $Desc = "Role name changed from " . $Role->RoleName . " to " . $RoleName;
            $Role->RoleName = $RoleName;
        }

        if (!is_null($RoleActive)) {
            if ($RoleActive != $Role->RoleActive) {
                $Desc = ", Role status changed from " . $Role->RoleActive . " to " . $RoleActive;
                if ($RoleActive == 1) {
                    $Role->RoleActive = 1;
                }
                if ($RoleActive == 0) {
                    $Role->RoleActive = 0;
                }
            }
        }

        $Role->save();

        ActionBackLog($Admin->IDUser, $Role->IDRole, "EDIT_ROLE", $Desc);
        return RespondWithSuccessRequest(8);
    }

    public function RoleSections(Request $request)
    {
        $Admin = auth('user')->user();
        if (!$Admin) {
            return RespondWithBadRequest(10);
        }

        $IDRole = $request->IDRole;
        if (!$IDRole) {
            return RespondWithBadRequest(1);
        }

        $RoleSections = RoleSection::leftjoin("roles", "roles.IDRole", "rolesections.IDRole")->leftjoin("sections", "sections.IDSection", "rolesections.IDSection")->where("rolesections.IDRole", $IDRole)->select("rolesections.IDRoleSection", "sections.SectionNameEn", "sections.Section", "rolesections.RoleSectionStatus")->get();

        $APICode = APICode::where('IDAPICode', 8)->first();
        $Response = array(
            'Success' => true,
            'ApiMsg' => __('apicodes.' . $APICode->IDApiCode),
            'ApiCode' => $APICode->IDApiCode,
            'Response' => $RoleSections,
        );
        return $Response;
    }

    public function RoleSectionStatus($IDRoleSection)
    {
        $Admin = auth('user')->user();
        if (!$Admin) {
            return RespondWithBadRequest(10);
        }

        $RoleSection = RoleSection::find($IDRoleSection);
        if (!$RoleSection) {
            return RespondWithBadRequest(1);
        }

        $Section = Section::find($RoleSection->IDSection);

        $Desc = "Role Section " . $Section->SectionNameEn . " changed from " . $RoleSection->RoleSectionStatus . " to " . !$RoleSection->RoleSectionStatus;
        $RoleSection->RoleSectionStatus = !$RoleSection->RoleSectionStatus;
        $RoleSection->save();

        ActionBackLog($Admin->IDUser, $RoleSection->IDRole, "EDIT_ROLE", $Desc);
        return RespondWithSuccessRequest(8);
    }

    public function UserList(Request $request)
    {
        $User = auth('user')->user();
        if (!$User) {
            return RespondWithBadRequest(10);
        }

        $IDPage = $request->IDPage;
        $IDRole = $request->IDRole;
        $SearchKey = $request->SearchKey;
        $UserStatus = $request->UserStatus;
        $StartDate = $request->StartDate;
        $EndDate = $request->EndDate;
        $Action = $request->Action;
        if (!$IDPage) {
            $IDPage = 0;
        } else {
            $IDPage = ($request->IDPage - 1) * 20;
        }

        $Users = User::leftjoin("roles", "roles.IDRole", "users.IDRole")->leftjoin("brands", "brands.IDBrand", "users.IDBrand")->leftjoin("branches", "branches.IDBranch", "users.IDBranch")->where("users.UserDeleted", 0);
        if ($SearchKey) {
            $Users = $Users->where(function ($query) use ($SearchKey) {
                $query->where('users.UserName', 'like', '%' . $SearchKey . '%')
                    ->orwhere('users.UserEmail', 'like', '%' . $SearchKey . '%')
                    ->orwhere('users.UserPhone', 'like', '%' . $SearchKey . '%');
            });
        }
        if ($UserStatus) {
            $Users = $Users->where("users.UserStatus", $UserStatus);
        }
        if ($StartDate) {
            $Users = $Users->where("users.created_at", ">=", $StartDate);
        }
        if ($EndDate) {
            $Users = $Users->where("users.created_at", "<=", $EndDate);
        }
        if ($IDRole) {
            $Users = $Users->where("users.IDRole", $IDRole);
        }
        if ($User->IDRole == 2) {
            $Users = $Users->where("users.IDBrand", $User->IDBrand);
        }

        $Pages = ceil($Users->count() / 20);
        if ($Action == "Export") {
            $Users = $Users->orderby("users.IDUser", "DESC")->select("users.IDUser", "users.UserName", "users.UserEmail", "users.UserPhone", "users.UserPhoneFlag", "users.UserStatus", "roles.RoleName", "users.UserRank", "users.created_at", "brands.BrandNameEn", "brands.BrandNameAr", "branches.BranchAddressEn", "branches.BranchAddressAr", "users.IDBranch")->get();
        } else {
            $Users = $Users->orderby("users.IDUser", "DESC")->select("users.IDUser", "users.UserName", "users.UserEmail", "users.UserPhone", "users.UserPhoneFlag", "users.UserStatus", "roles.RoleName", "users.UserRank", "users.created_at", "brands.BrandNameEn", "brands.BrandNameAr", "branches.BranchAddressEn", "branches.BranchAddressAr", "users.IDBranch")->skip($IDPage)->take(20)->get();
        }
        $Users = UserResource::collection($Users);
        $Response = array("Users" => $Users, "Pages" => $Pages);

        $APICode = APICode::where('IDAPICode', 8)->first();
        $Response = array(
            'Success' => true,
            'ApiMsg' => __('apicodes.' . $APICode->IDApiCode),
            'ApiCode' => $APICode->IDApiCode,
            'Response' => $Response,
        );
        return $Response;
    }

    public function AddUser(Request $request)
    {
        $Admin = auth('user')->user();
        if (!$Admin) {
            return RespondWithBadRequest(10);
        }

        $IDBrand = $request->IDBrand;
        $IDBranch = $request->IDBranch;
        $IDRole = $request->IDRole;
        $UserName = $request->UserName;
        $UserEmail = $request->UserEmail;
        $UserPhone = $request->UserPhone;
        $UserPhoneFlag = $request->UserPhoneFlag;
        $UserPassword = $request->UserPassword;
        $UserLanguage = $request->UserLanguage;

        if (!$IDRole) {
            if ($Admin->IDRole != 2) {
                return RespondWithBadRequest(1);
            }
            $IDRole = 2;
        }
        if (!$UserName) {
            return RespondWithBadRequest(1);
        }
        if (!$UserEmail) {
            return RespondWithBadRequest(1);
        }
        if (!$UserPhone) {
            return RespondWithBadRequest(1);
        }
        if (!$UserPhoneFlag) {
            return RespondWithBadRequest(1);
        }
        if (!$UserPassword) {
            return RespondWithBadRequest(1);
        }
        if (!$UserLanguage) {
            $UserLanguage = "en";
        }

        $UserRecord = User::where("UserEmail", $UserEmail)->where("UserDeleted", 0)->first();
        if ($UserRecord) {
            return RespondWithBadRequest(2);
        }
        $UserRecord = User::where("UserPhone", $UserPhone)->where("UserDeleted", 0)->first();
        if ($UserRecord) {
            return RespondWithBadRequest(3);
        }

        $User = new User;
        $User->IDBrand = $IDBrand;
        if ($Admin->IDRole != 1) {
            $User->IDBrand = $Admin->IDBrand;
        }
        $User->IDBranch = $IDBranch;
        $User->IDRole = $IDRole;
        $User->UserName = $UserName;
        $User->UserEmail = $UserEmail;
        $User->UserPhone = $UserPhone;
        $User->UserPhoneFlag = $UserPhoneFlag;
        $User->UserLanguage = $UserLanguage;
        $User->UserStatus = "PENDING";
        $User->UserPassword = Hash::make($UserPassword);
        $User->save();

        $Desc = "Added User " . $UserName . " with phone " . $UserPhone;
        ActionBackLog($Admin->IDUser, $User->IDUser, "ADD_USER", $Desc);

        return RespondWithSuccessRequest(8);
    }

    public function UserProfile($IDUser)
    {
        $Admin = auth('user')->user();
        if (!$Admin) {
            return RespondWithBadRequest(10);
        }

        $User = User::where("IDUser", $IDUser)->where("UserDeleted", 0)->first();
        if (!$User) {
            return RespondWithBadRequest(1);
        }

        $Success = true;
        $IDAPICode = 7;
        $response_code = 200;
        $APICode = APICode::where('IDAPICode', $IDAPICode)->first();
        $response = array('IDUser' => $User->IDUser, 'UserPhone' => $User->UserPhone, 'UserPhoneFlag' => $User->UserPhoneFlag, 'UserName' => $User->UserName, 'UserEmail' => $User->UserEmail, 'UserStatus' => $User->UserStatus, 'IDRole' => $User->IDRole, 'UserRank' => $User->UserRank, 'IDBrand' => $User->IDBrand, "IDBranch" => $User->IDBranch, 'CreateDate' => $User->created_at);
        $response_array = array('Success' => $Success, 'ApiMsg' => trans('apicodes.' . $APICode->IDApiCode), 'ApiCode' => $APICode->IDApiCode, 'Response' => $response);
        $response = Response::json($response_array, $response_code);
        return $response;
    }

    public function EditUser(Request $request)
    {

        $Admin = auth('user')->user();
        if (!$Admin) {
            return RespondWithBadRequest(10);
        }
        $IDUser = $request->IDUser;
        $User = User::where("IDUser", $IDUser)->where("UserDeleted", 0)->first();
        if (!$User) {
            return RespondWithBadRequest(1);
        }

        $IDRole = $request->IDRole;
        $IDBranch = $request->IDBranch;
        $IDBrand = $request->IDBrand;
        $UserName = $request->UserName;
        $UserEmail = $request->UserEmail;
        $UserPhone = $request->UserPhone;
        $UserPhoneFlag = $request->UserPhoneFlag;
        $UserPassword = $request->UserPassword;
        $UserLanguage = $request->UserLanguage;
        $Desc = "";

        if ($IDRole) {
            $User->IDRole = $IDRole;
        }
        if ($IDBranch) {
            $User->IDBranch = $IDBranch;
        }
        if ($IDBrand) {
            $User->IDBrand = $IDBrand;
        }
        if ($UserName) {
            $Desc = $Desc . "Username changed from " . $User->UserName . " to " . $UserName;
            $User->UserName = $UserName;
        }
        if ($UserEmail) {
            $UserRecord = User::where("UserEmail", $UserEmail)->where("UserDeleted", 0)->where("IDUser", "<>", $IDUser)->first();
            if ($UserRecord) {
                return RespondWithBadRequest(2);
            }
            $Desc = $Desc . ", Email changed from " . $User->UserEmail . " to " . $UserEmail;
            $User->UserEmail = $UserEmail;
        }
        if ($UserPhone) {
            $UserRecord = User::where("UserPhone", $UserPhone)->where("UserDeleted", 0)->where("IDUser", "<>", $IDUser)->first();
            if ($UserRecord) {
                return RespondWithBadRequest(3);
            }
            $Desc = $Desc . ", Phone changed from " . $User->UserPhone . " to " . $UserPhone;
            $User->UserPhone = $UserPhone;
        }
        if ($UserPhoneFlag) {
            $User->UserPhoneFlag = $UserPhoneFlag;
        }
        if ($UserLanguage) {
            $User->UserLanguage = $UserLanguage;
        }
        if ($UserPassword) {
            $User->UserPassword = Hash::make($UserPassword);
        }
        $User->UserStatus = "PENDING";
        $User->save();

        ActionBackLog($Admin->IDUser, $User->IDUser, "EDIT_USER", $Desc);

        return RespondWithSuccessRequest(8);
    }

    public function UserLanguageChange(Request $request)
    {
        $User = auth('user')->user();
        if (!$User) {
            return RespondWithBadRequest(10);
        }

        $UserLanguage = $request->UserLanguage;
        if (!$UserLanguage) {
            return RespondWithBadRequest(1);
        }

        $User->UserLanguage = $UserLanguage;
        $User->save();

        return RespondWithSuccessRequest(8);
    }

    public function UserStatus(Request $request)
    {
        $Admin = auth('user')->user();
        if (!$Admin) {
            return RespondWithBadRequest(10);
        }

        $IDUser = $request->IDUser;
        $UserStatus = $request->UserStatus;

        if (!$IDUser) {
            return RespondWithBadRequest(1);
        }
        if (!$UserStatus) {
            return RespondWithBadRequest(1);
        }

        $User = User::where("IDUser", $IDUser)->where("UserDeleted", 0)->first();
        if (!$User) {
            return RespondWithBadRequest(1);
        }

        if ($UserStatus == "DELETED") {
            $User->UserDeleted = 1;
            $Desc = "User with name " . $User->UserName . " and phone " . $User->UserPhone . " was Deleted";
        } else {
            $Desc = "User Status changed from " . $User->UserStatus . " to " . $UserStatus;
            $User->UserStatus = $UserStatus;
        }

        ActionBackLog($Admin->IDUser, $User->IDUser, "EDIT_USER", $Desc);
        $User->save();

        return RespondWithSuccessRequest(8);
    }

    public function Countries()
    {
        $Countries = Country::where("CountryActive", 1)->get();

        $APICode = APICode::where('IDAPICode', 8)->first();
        $Response = array(
            'Success' => true,
            'ApiMsg' => __('apicodes.' . $APICode->IDApiCode),
            'ApiCode' => $APICode->IDApiCode,
            'Response' => CountryResource::collection($Countries),
        );
        return $Response;
    }

    public function Cities($IDCountry)
    {
        $Cities = City::leftjoin("countries", "countries.IDCountry", "cities.IDCountry")->where("cities.IDCountry", $IDCountry)->where("cities.CityActive", 1)->get();

        $APICode = APICode::where('IDAPICode', 8)->first();
        $Response = array(
            'Success' => true,
            'ApiMsg' => __('apicodes.' . $APICode->IDApiCode),
            'ApiCode' => $APICode->IDApiCode,
            'Response' => CityResource::collection($Cities),
        );
        return $Response;
    }

    public function Areas($IDCity)
    {
        $Areas = Area::leftjoin("cities", "cities.IDCity", "areas.IDCity")->leftjoin("countries", "countries.IDCountry", "cities.IDCountry")->where("areas.IDCity", $IDCity)->where("areas.AreaActive", 1)->get();
        $APICode = APICode::where('IDAPICode', 8)->first();
        $Response = array(
            'Success' => true,
            'ApiMsg' => __('apicodes.' . $APICode->IDApiCode),
            'ApiCode' => $APICode->IDApiCode,
            'Response' => AreaResource::collection($Areas),
        );
        return $Response;
    }

    public function CountryList(Request $request, Country $Countries)
    {
        $IDPage = $request->IDPage;
        $SearchKey = $request->SearchKey;
        $CountryStatus = $request->CountryStatus;
        if (!$IDPage) {
            $IDPage = 0;
        } else {
            $IDPage = ($request->IDPage - 1) * 20;
        }

        if ($SearchKey) {
            $Countries = $Countries->where(function ($query) use ($SearchKey) {
                $query->where('CountryNameEn', 'like', '%' . $SearchKey . '%')
                    ->orwhere('CountryNameAr', 'like', '%' . $SearchKey . '%');
            });
        }

        if ($CountryStatus) {
            if ($CountryStatus == "ACTIVE") {
                $Countries = $Countries->where("CountryActive", 1);
            } else {
                $Countries = $Countries->where("CountryActive", 0);
            }
        }

        $Pages = ceil($Countries->count() / 20);
        $Countries = $Countries->skip($IDPage)->take(20)->get();
        $Countries = CountryResource::collection($Countries);
        $Response = array("Countries" => $Countries, "Pages" => $Pages);

        $APICode = APICode::where('IDAPICode', 8)->first();
        $Response = array(
            'Success' => true,
            'ApiMsg' => __('apicodes.' . $APICode->IDApiCode),
            'ApiCode' => $APICode->IDApiCode,
            'Response' => $Response,
        );
        return $Response;
    }

    public function CountryStatus($IDCountry)
    {
        $Admin = auth('user')->user();
        $Country = Country::find($IDCountry);
        if (!$Country) {
            return RespondWithBadRequest(1);
        }
        $Country->CountryActive = !$Country->CountryActive;
        $Desc = "Country " . $Country->CountryNameEn . " status changed";
        ActionBackLog($Admin->IDUser, $IDCountry, "EDIT_COUNTRY", $Desc);
        $Country->save();

        return RespondWithSuccessRequest(8);
    }

    public function CountryAdd(Request $request)
    {
        $Admin = auth('user')->user();
        $CountryNameEn = $request->CountryNameEn;
        $CountryNameAr = $request->CountryNameAr;
        $CountryTimeZone = $request->CountryTimeZone;
        $CountryCode = $request->CountryCode;
        $CountryActive = $request->CountryActive;

        if (!$CountryNameEn) {
            return RespondWithBadRequest(1);
        }
        if (!$CountryNameAr) {
            return RespondWithBadRequest(1);
        }
        if (!$CountryTimeZone) {
            return RespondWithBadRequest(1);
        }
        if (!$CountryCode) {
            return RespondWithBadRequest(1);
        }

        $Country = Country::where("CountryNameEn", $CountryNameEn)->orwhere("CountryNameAr", $CountryNameAr)->first();
        if ($Country) {
            return RespondWithBadRequest(18);
        }

        $Country = new Country;
        $Country->CountryNameEn = $CountryNameEn;
        $Country->CountryNameAr = $CountryNameAr;
        $Country->CountryTimeZone = $CountryTimeZone;
        $Country->CountryCode = $CountryCode;
        if ($CountryActive) {
            $Country->CountryActive = 1;
        } else {
            $Country->CountryActive = 0;
        }
        $Country->save();

        $Desc = "Country " . $Country->CountryNameEn . " was added";
        ActionBackLog($Admin->IDUser, $Country->IDCountry, "ADD_COUNTRY", $Desc);

        return RespondWithSuccessRequest(8);
    }

    public function CountryEditPage($IDCountry)
    {
        $Country = Country::find($IDCountry);
        if (!$Country) {
            return RespondWithBadRequest(1);
        }

        $APICode = APICode::where('IDAPICode', 8)->first();
        $Response = array(
            'Success' => true,
            'ApiMsg' => __('apicodes.' . $APICode->IDApiCode),
            'ApiCode' => $APICode->IDApiCode,
            'Response' => $Country,
        );
        return $Response;
    }

    public function CountryEdit(Request $request)
    {
        $Admin = auth('user')->user();
        $IDCountry = $request->IDCountry;
        $CountryNameEn = $request->CountryNameEn;
        $CountryNameAr = $request->CountryNameAr;
        $CountryTimeZone = $request->CountryTimeZone;
        $CountryCode = $request->CountryCode;
        $Desc = "";

        $Country = Country::find($IDCountry);
        if (!$Country) {
            return RespondWithBadRequest(1);
        }

        if ($CountryNameEn) {
            $CountryRecord = Country::where("CountryNameEn", $CountryNameEn)->where("IDCountry", "<>", $IDCountry)->first();
            if ($CountryRecord) {
                return RespondWithBadRequest(18);
            }
            $Desc = "Country english name was changed from " . $Country->CountryNameEn . " to " . $CountryNameEn;
            $Country->CountryNameEn = $CountryNameEn;
        }
        if ($CountryNameAr) {
            $CountryRecord = Country::where("CountryNameAr", $CountryNameAr)->where("IDCountry", "<>", $IDCountry)->first();
            if ($CountryRecord) {
                return RespondWithBadRequest(18);
            }
            $Desc = $Desc . ", Country arabic name was changed from " . $Country->CountryNameAr . " to " . $CountryNameAr;
            $Country->CountryNameAr = $CountryNameAr;
        }
        if ($CountryTimeZone) {
            $Country->CountryTimeZone = $CountryTimeZone;
        }
        if ($CountryCode) {
            $Country->CountryCode = $CountryCode;
        }

        $Country->save();

        ActionBackLog($Admin->IDUser, $Country->IDCountry, "EDIT_COUNTRY", $Desc);
        return RespondWithSuccessRequest(8);
    }


    public function CityList(Request $request, City $Cities)
    {
        $IDPage = $request->IDPage;
        $IDCountry = $request->IDCountry;
        $SearchKey = $request->SearchKey;
        $CityStatus = $request->CityStatus;
        if (!$IDPage) {
            $IDPage = 0;
        } else {
            $IDPage = ($request->IDPage - 1) * 20;
        }

        $Cities = $Cities->leftjoin("countries", "countries.IDCountry", "cities.IDCountry");
        if ($IDCountry) {
            $Cities = $Cities->where("cities.IDCountry", $IDCountry);
        }

        if ($SearchKey) {
            $Cities = $Cities->where(function ($query) use ($SearchKey) {
                $query->where('cities.CityNameEn', 'like', '%' . $SearchKey . '%')
                    ->orwhere('cities.CityNameAr', 'like', '%' . $SearchKey . '%');
            });
        }

        if ($CityStatus) {
            if ($CityStatus == "ACTIVE") {
                $Cities = $Cities->where("cities.CityActive", 1);
            } else {
                $Cities = $Cities->where("cities.CityActive", 0);
            }
        }

        $Pages = ceil($Cities->count() / 20);
        $Cities = $Cities->skip($IDPage)->take(20)->get();
        $Cities = CityResource::collection($Cities);
        $Response = array("Cities" => $Cities, "Pages" => $Pages);

        $APICode = APICode::where('IDAPICode', 8)->first();
        $Response = array(
            'Success' => true,
            'ApiMsg' => __('apicodes.' . $APICode->IDApiCode),
            'ApiCode' => $APICode->IDApiCode,
            'Response' => $Response,
        );
        return $Response;
    }

    public function CityStatus($IDCity)
    {
        $Admin = auth('user')->user();
        $City = City::find($IDCity);
        if (!$City) {
            return RespondWithBadRequest(1);
        }
        $City->CityActive = !$City->CityActive;
        $Desc = "City " . $City->CityNameEn . " status changed";
        $City->save();

        ActionBackLog($Admin->IDUser, $IDCity, "EDIT_CITY", $Desc);
        return RespondWithSuccessRequest(8);
    }

    public function CityAdd(Request $request)
    {
        $Admin = auth('user')->user();
        $IDCountry = $request->IDCountry;
        $CityNameEn = $request->CityNameEn;
        $CityNameAr = $request->CityNameAr;
        $CityCode = $request->CityCode;
        $CityActive = $request->CityActive;

        if (!$IDCountry) {
            return RespondWithBadRequest(1);
        }
        if (!$CityNameEn) {
            return RespondWithBadRequest(1);
        }
        if (!$CityNameAr) {
            return RespondWithBadRequest(1);
        }
        if (!$CityCode) {
            return RespondWithBadRequest(1);
        }

        $City = City::where("IDCountry", $IDCountry)->where(function ($query) use ($CityNameEn, $CityNameAr) {
            $query->where('CityNameEn', 'like', '%' . $CityNameEn . '%')
                ->orwhere('CityNameAr', 'like', '%' . $CityNameAr . '%');
        })->first();

        if ($City) {
            return RespondWithBadRequest(18);
        }

        $City = new City;
        $City->IDCountry = $IDCountry;
        $City->CityNameEn = $CityNameEn;
        $City->CityNameAr = $CityNameAr;
        $City->CityCode = $CityCode;
        if ($CityActive) {
            $City->CityActive = 1;
        } else {
            $City->CityActive = 0;
        }
        $City->save();

        $Desc = "City " . $City->CityNameEn . " was added";
        ActionBackLog($Admin->IDUser, $City->IDCity, "ADD_CITY", $Desc);
        return RespondWithSuccessRequest(8);
    }

    public function CityEditPage($IDCity)
    {
        $City = City::find($IDCity);
        if (!$City) {
            return RespondWithBadRequest(1);
        }

        $APICode = APICode::where('IDAPICode', 8)->first();
        $Response = array(
            'Success' => true,
            'ApiMsg' => __('apicodes.' . $APICode->IDApiCode),
            'ApiCode' => $APICode->IDApiCode,
            'Response' => $City,
        );
        return $Response;
    }

    public function CityEdit(Request $request)
    {
        $Admin = auth('user')->user();
        $IDCity = $request->IDCity;
        $IDCountry = $request->IDCountry;
        $CityNameEn = $request->CityNameEn;
        $CityNameAr = $request->CityNameAr;
        $CityCode = $request->CityCode;
        $Desc = "";

        $City = City::find($IDCity);
        if (!$City) {
            return RespondWithBadRequest(1);
        }

        if ($CityNameEn) {
            $CityRecord = City::where("CityNameEn", $CityNameEn)->where("IDCity", "<>", $IDCity)->where("IDCountry", $IDCountry)->first();
            if ($CityRecord) {
                return RespondWithBadRequest(18);
            }
            $Desc = "City english name was changed from " . $City->CityNameEn . " to " . $CityNameEn;
            $City->CityNameEn = $CityNameEn;
        }
        if ($CityNameAr) {
            $CityRecord = City::where("CityNameAr", $CityNameAr)->where("IDCity", "<>", $IDCity)->where("IDCountry", $IDCountry)->first();
            if ($CityRecord) {
                return RespondWithBadRequest(18);
            }
            $Desc = $Desc . ", City arabic name was changed from " . $City->CityNameAr . " to " . $CityNameAr;
            $City->CityNameAr = $CityNameAr;
        }
        if ($CityCode) {
            $City->CityCode = $CityCode;
        }
        if ($IDCountry) {
            $City->IDCountry = $IDCountry;
        }

        $City->save();
        ActionBackLog($Admin->IDUser, $City->IDCity, "EDIT_CITY", $Desc);

        return RespondWithSuccessRequest(8);
    }

    public function AreaList(Request $request, Area $Areas)
    {
        $IDPage = $request->IDPage;
        $IDCountry = $request->IDCountry;
        $IDCity = $request->IDCity;
        $SearchKey = $request->SearchKey;
        $AreaStatus = $request->AreaStatus;
        if (!$IDPage) {
            $IDPage = 0;
        } else {
            $IDPage = ($request->IDPage - 1) * 20;
        }

        $Areas = $Areas->leftjoin("cities", "cities.IDCity", "areas.IDCity")->leftjoin("countries", "countries.IDCountry", "cities.IDCountry");
        if ($IDCountry) {
            $Areas = $Areas->where("cities.IDCountry", $IDCountry);
        }
        if ($IDCity) {
            $Areas = $Areas->where("areas.IDCity", $IDCity);
        }
        if ($SearchKey) {
            $Areas = $Areas->where(function ($query) use ($SearchKey) {
                $query->where('areas.AreaNameEn', 'like', '%' . $SearchKey . '%')
                    ->orwhere('areas.AreaNameAr', 'like', '%' . $SearchKey . '%');
            });
        }

        if ($AreaStatus) {
            if ($AreaStatus == "ACTIVE") {
                $Areas = $Areas->where("areas.AreaActive", 1);
            } else {
                $Areas = $Areas->where("areas.AreaActive", 0);
            }
        }

        $Pages = ceil($Areas->count() / 20);
        $Areas = $Areas->skip($IDPage)->take(20)->get();
        $Areas = AreaResource::collection($Areas);
        $Response = array("Areas" => $Areas, "Pages" => $Pages);

        $APICode = APICode::where('IDAPICode', 8)->first();
        $Response = array(
            'Success' => true,
            'ApiMsg' => __('apicodes.' . $APICode->IDApiCode),
            'ApiCode' => $APICode->IDApiCode,
            'Response' => $Response,
        );
        return $Response;
    }

    public function AreaStatus($IDArea)
    {
        $Admin = auth('user')->user();
        $Area = Area::find($IDArea);
        if (!$Area) {
            return RespondWithBadRequest(1);
        }
        $Area->AreaActive = !$Area->AreaActive;
        $Area->save();

        $Desc = "Area " . $Area->AreaNameEn . " status changed";
        ActionBackLog($Admin->IDUser, $Area->IDArea, "EDIT_AREA", $Desc);
        return RespondWithSuccessRequest(8);
    }

    public function AreaAdd(Request $request)
    {
        $Admin = auth('user')->user();
        $IDCity = $request->IDCity;
        $AreaNameEn = $request->AreaNameEn;
        $AreaNameAr = $request->AreaNameAr;
        $AreaActive = $request->AreaActive;

        if (!$IDCity) {
            return RespondWithBadRequest(1);
        }
        if (!$AreaNameEn) {
            return RespondWithBadRequest(1);
        }
        if (!$AreaNameAr) {
            return RespondWithBadRequest(1);
        }

        $Area = Area::where("IDCity", $IDCity)->where(function ($query) use ($AreaNameEn, $AreaNameAr) {
            $query->where('AreaNameEn', 'like', '%' . $AreaNameEn . '%')
                ->orwhere('AreaNameAr', 'like', '%' . $AreaNameAr . '%');
        })->first();

        if ($Area) {
            return RespondWithBadRequest(18);
        }

        $Area = new Area;
        $Area->IDCity = $IDCity;
        $Area->AreaNameEn = $AreaNameEn;
        $Area->AreaNameAr = $AreaNameAr;
        if ($AreaActive) {
            $Area->AreaActive = 1;
        } else {
            $Area->AreaActive = 0;
        }
        $Area->save();

        $Desc = "Area " . $Area->AreaNameEn . " was added";
        ActionBackLog($Admin->IDUser, $Area->IDArea, "ADD_AREA", $Desc);
        return RespondWithSuccessRequest(8);
    }

    public function AreaEditPage($IDArea)
    {
        $Area = Area::find($IDArea);
        if (!$Area) {
            return RespondWithBadRequest(1);
        }

        $City = City::find($Area->IDCity);
        $Area->IDCountry = $City->IDCountry;

        $APICode = APICode::where('IDAPICode', 8)->first();
        $Response = array(
            'Success' => true,
            'ApiMsg' => __('apicodes.' . $APICode->IDApiCode),
            'ApiCode' => $APICode->IDApiCode,
            'Response' => $Area,
        );
        return $Response;
    }

    public function AreaEdit(Request $request)
    {
        $Admin = auth('user')->user();
        $IDArea = $request->IDArea;
        $IDCity = $request->IDCity;
        $AreaNameEn = $request->AreaNameEn;
        $AreaNameAr = $request->AreaNameAr;
        $Desc = "";

        $Area = Area::find($IDArea);
        if (!$Area) {
            return RespondWithBadRequest(1);
        }

        if ($AreaNameEn) {
            $AreaRecord = Area::where("AreaNameEn", $AreaNameEn)->where("IDArea", "<>", $IDArea)->where("IDCity", $IDCity)->first();
            if ($AreaRecord) {
                return RespondWithBadRequest(18);
            }
            $Desc = "Area english name was changed from " . $Area->AreaNameEn . " to " . $AreaNameEn;
            $Area->AreaNameEn = $AreaNameEn;
        }
        if ($AreaNameAr) {
            $AreaRecord = Area::where("AreaNameAr", $AreaNameAr)->where("IDArea", "<>", $IDArea)->where("IDCity", $IDCity)->first();
            if ($AreaRecord) {
                return RespondWithBadRequest(18);
            }
            $Desc = $Desc . ", Area arabic name was changed from " . $Area->AreaNameAr . " to " . $AreaNameAr;
            $Area->AreaNameAr = $AreaNameAr;
        }
        if ($IDCity) {
            $Area->IDCity = $IDCity;
        }

        $Area->save();
        ActionBackLog($Admin->IDUser, $Area->IDArea, "EDIT_AREA", $Desc);
        return RespondWithSuccessRequest(8);
    }

    public function CategoryList(Request $request, Category $Categories)
    {
        $IDPage = $request->IDPage;
        $SearchKey = $request->SearchKey;
        $CategoryActive = $request->CategoryActive;
        $CategoryType = $request->CategoryType;
        $CategoryGroup = $request->CategoryGroup;
        if (!$IDPage) {
            $IDPage = 0;
        } else {
            $IDPage = ($request->IDPage - 1) * 20;
        }

        if ($SearchKey) {
            $Categories = $Categories->where(function ($query) use ($SearchKey) {
                $query->where('CategoryNameEn', 'like', '%' . $SearchKey . '%')
                    ->orwhere('CategoryNameAr', 'like', '%' . $SearchKey . '%');
            });
        }

        if ($CategoryType) {
            $Categories = $Categories->where("CategoryType", $CategoryType);
        }
        if ($CategoryGroup) {
            $Categories = $Categories->where("CategoryGroup", $CategoryGroup);
        }
        if ($CategoryActive == 1) {
            $Categories = $Categories->where("CategoryActive", 1);
        }
        if ($CategoryActive == '0') {
            $Categories = $Categories->where("CategoryActive", 0);
        }

        $Pages = ceil($Categories->count() / 20);
        $Categories = $Categories->skip($IDPage)->take(20)->get();
        $Categories = CategoryResource::collection($Categories);
        $Response = array("Categories" => $Categories, "Pages" => $Pages);

        $APICode = APICode::where('IDAPICode', 8)->first();
        $Response = array(
            'Success' => true,
            'ApiMsg' => __('apicodes.' . $APICode->IDApiCode),
            'ApiCode' => $APICode->IDApiCode,
            'Response' => $Response,
        );
        return $Response;
    }

    public function CategoryStatus($IDCategory)
    {
        $Admin = auth('user')->user();
        $Category = Category::find($IDCategory);
        if (!$Category) {
            return RespondWithBadRequest(1);
        }
        $Category->CategoryActive = !$Category->CategoryActive;
        $Category->save();

        $Desc = "Category " . $Category->CategoryNameEn . " status changed";
        ActionBackLog($Admin->IDUser, $Area->IDArea, "EDIT_CATEGORY", $Desc);
        return RespondWithSuccessRequest(8);
    }

    public function CategoryAdd(Request $request)
    {
        $Admin = auth('user')->user();
        $CategoryNameEn = $request->CategoryNameEn;
        $CategoryNameAr = $request->CategoryNameAr;
        $CategoryLogo = $request->CategoryLogo;
        $CategoryType = $request->CategoryType;
        $CategoryGroup = $request->CategoryGroup;

        if (!$CategoryNameEn) {
            return RespondWithBadRequest(1);
        }
        if (!$CategoryNameAr) {
            return RespondWithBadRequest(1);
        }
        if (!$CategoryType) {
            $CategoryType = "PROJECT";
        }
        if (!$CategoryGroup) {
            $CategoryGroup = "NONE";
        }

        $Category = Category::where("CategoryNameEn", $CategoryNameEn)->orwhere("CategoryNameAr", $CategoryNameAr)->first();
        if ($Category) {
            return RespondWithBadRequest(18);
        }

        $ImageExtArray = ["jpeg", "jpg", "png", "svg"];
        if ($request->file('CategoryLogo')) {
            if (!in_array($request->CategoryLogo->extension(), $ImageExtArray)) {
                return RespondWithBadRequest(15);
            }
            $NextIDCategory = DB::select('SELECT AUTO_INCREMENT FROM information_schema.TABLES WHERE  TABLE_NAME = "categories"')[0]->AUTO_INCREMENT;
            $CategoryLogo = SaveImage($request->file('CategoryLogo'), "categories", $NextIDCategory);
        } else {
            if ($CategoryType == "PROJECT") {
                return RespondWithBadRequest(1);
            }
        }

        $Category = new Category;
        $Category->CategoryNameEn = $CategoryNameEn;
        $Category->CategoryNameAr = $CategoryNameAr;
        $Category->CategoryLogo = $CategoryLogo;
        $Category->CategoryType = $CategoryType;
        $Category->CategoryGroup = $CategoryGroup;
        $Category->save();

        $Desc = "Category " . $Category->CategoryNameEn . " was added";
        ActionBackLog($Admin->IDUser, $Category->IDCategory, "ADD_CATEGORY", $Desc);

        return RespondWithSuccessRequest(8);
    }

    public function CategoryEditPage($IDCategory)
    {
        $Category = Category::find($IDCategory);
        if (!$Category) {
            return RespondWithBadRequest(1);
        }

        $Category->CategoryLogo =  ($Category->CategoryLogo) ? asset($Category->CategoryLogo) : '';

        $APICode = APICode::where('IDAPICode', 8)->first();
        $Response = array(
            'Success' => true,
            'ApiMsg' => __('apicodes.' . $APICode->IDApiCode),
            'ApiCode' => $APICode->IDApiCode,
            'Response' => $Category,
        );
        return $Response;
    }

    public function CategoryEdit(Request $request)
    {
        $Admin = auth('user')->user();
        $IDCategory = $request->IDCategory;
        $CategoryNameEn = $request->CategoryNameEn;
        $CategoryNameAr = $request->CategoryNameAr;
        $CategoryLogo = $request->CategoryLogo;
        $Desc = "";

        $Category = Category::find($IDCategory);
        if (!$Category) {
            return RespondWithBadRequest(1);
        }

        if ($CategoryNameEn) {
            $CategoryRecord = Category::where("CategoryNameEn", $CategoryNameEn)->where("IDCategory", "<>", $IDCategory)->first();
            if ($CategoryRecord) {
                return RespondWithBadRequest(18);
            }
            $Desc = "Category english name changed from " . $Category->CategoryNameEn . " to " . $CategoryNameEn;
            $Category->CategoryNameEn = $CategoryNameEn;
        }
        if ($CategoryNameAr) {
            $CategoryRecord = Category::where("CategoryNameAr", $CategoryNameAr)->where("IDCategory", "<>", $IDCategory)->first();
            if ($CategoryRecord) {
                return RespondWithBadRequest(18);
            }
            $Desc = $Desc . " ,Category english name changed from " . $Category->CategoryNameAr . " to " . $CategoryNameAr;
            $Category->CategoryNameAr = $CategoryNameAr;
        }

        $ImageExtArray = ["jpeg", "jpg", "png", "svg"];
        if ($request->file('CategoryLogo')) {
            if (!in_array($request->CategoryLogo->extension(), $ImageExtArray)) {
                return RespondWithBadRequest(15);
            }
            if ($Category->CategoryLogo) {
                $OldPhoto = substr($Category->CategoryLogo, 7);
                Storage::disk('uploads')->delete($OldPhoto);
            }
            $CategoryLogo = SaveImage($request->file('CategoryLogo'), "categories", $IDCategory);
            $Category->CategoryLogo = $CategoryLogo;
            $Desc = $Desc . " ,Category logo changed";
        }

        $Category->save();

        ActionBackLog($Admin->IDUser, $Category->IDCategory, "EDIT_CATEGORY", $Desc);
        return RespondWithSuccessRequest(8);
    }

    public function CategoryAjax(Request $request, Category $Categories)
    {
        $CategoryType = $request->CategoryType;
        $CategoryGroup = $request->CategoryGroup;
        if (!$CategoryType) {
            $CategoryType = "PROJECT";
        }
        $Categories = $Categories->where("CategoryActive", 1);
        if ($CategoryGroup) {
            $Categories = $Categories->where("CategoryGroup", $CategoryGroup);
        }
        $Categories = $Categories->where("CategoryType", $CategoryType);
        $Categories = $Categories->get();
        $Categories = CategoryResource::collection($Categories);

        $APICode = APICode::where('IDAPICode', 8)->first();
        $Response = array(
            'Success' => true,
            'ApiMsg' => __('apicodes.' . $APICode->IDApiCode),
            'ApiCode' => $APICode->IDApiCode,
            'Response' => $Categories,
        );
        return $Response;
    }

    public function SubCategoryList(Request $request, SubCategory $SubCategories)
    {
        $IDPage = $request->IDPage;
        $SearchKey = $request->SearchKey;
        $CategoryType = $request->CategoryType;
        $IDCategory = $request->IDCategory;
        $SubCategoryActive = $request->SubCategoryActive;
        if (!$IDPage) {
            $IDPage = 0;
        } else {
            $IDPage = ($request->IDPage - 1) * 20;
        }

        $SubCategories = $SubCategories->leftjoin("categories", "categories.IDCategory", "subcategories.IDCategory",);
        if ($SearchKey) {
            $SubCategories = $SubCategories->where(function ($query) use ($SearchKey) {
                $query->where('subcategories.SubCategoryNameEn', 'like', '%' . $SearchKey . '%')
                    ->orwhere('subcategories.SubCategoryNameAr', 'like', '%' . $SearchKey . '%');
            });
        }
        if ($CategoryType) {
            $SubCategories = $SubCategories->where("categories.CategoryType", $CategoryType);
        }
        if ($IDCategory) {
            $SubCategories = $SubCategories->where("subcategories.IDCategory", $IDCategory);
        }
        if ($SubCategoryActive == 1) {
            $SubCategories = $SubCategories->where("subcategories.SubCategoryActive", 1);
        }
        if ($SubCategoryActive == '0') {
            $SubCategories = $SubCategories->where("subcategories.SubCategoryActive", 0);
        }
        $Pages = ceil($SubCategories->count() / 20);
        $SubCategories = $SubCategories->skip($IDPage)->take(20)->get();
        $SubCategories = SubCategoryResource::collection($SubCategories);
        $Response = array("SubCategories" => $SubCategories, "Pages" => $Pages);

        $APICode = APICode::where('IDAPICode', 8)->first();
        $Response = array(
            'Success' => true,
            'ApiMsg' => __('apicodes.' . $APICode->IDApiCode),
            'ApiCode' => $APICode->IDApiCode,
            'Response' => $Response,
        );
        return $Response;
    }

    public function SubCategoryStatus($IDSubCategory)
    {
        $Admin = auth('user')->user();
        $SubCategory = SubCategory::find($IDSubCategory);
        if (!$SubCategory) {
            return RespondWithBadRequest(1);
        }
        $SubCategory->SubCategoryActive = !$SubCategory->SubCategoryActive;
        $SubCategory->save();

        $Desc = "Sub Category " . $SubCategory->SubCategoryNameEn . " status changed";
        ActionBackLog($Admin->IDUser, $SubCategory->IDSubCategory, "EDIT_SUBCATEGORY", $Desc);
        return RespondWithSuccessRequest(8);
    }

    public function SubCategoryAdd(Request $request)
    {
        $Admin = auth('user')->user();
        $IDCategory = $request->IDCategory;
        $SubCategoryNameEn = $request->SubCategoryNameEn;
        $SubCategoryNameAr = $request->SubCategoryNameAr;
        $SubCategoryLogo = $request->SubCategoryLogo;

        if (!$IDCategory) {
            return RespondWithBadRequest(1);
        }
        if (!$SubCategoryNameEn) {
            return RespondWithBadRequest(1);
        }
        if (!$SubCategoryNameAr) {
            return RespondWithBadRequest(1);
        }

        $Category = Category::find($IDCategory);

        $SubCategory = SubCategory::where("IDCategory", $IDCategory)->where(function ($query) use ($SubCategoryNameEn, $SubCategoryNameAr) {
            $query->where('SubCategoryNameEn', 'like', '%' . $SubCategoryNameEn . '%')
                ->orwhere('SubCategoryNameAr', 'like', '%' . $SubCategoryNameAr . '%');
        })->first();

        if ($SubCategory) {
            return RespondWithBadRequest(18);
        }

        $ImageExtArray = ["jpeg", "jpg", "png", "svg"];
        if ($request->file('SubCategoryLogo')) {
            if (!in_array($request->SubCategoryLogo->extension(), $ImageExtArray)) {
                return RespondWithBadRequest(15);
            }
            $NextIDSubCategory = DB::select('SELECT AUTO_INCREMENT FROM information_schema.TABLES WHERE  TABLE_NAME = "subcategories"')[0]->AUTO_INCREMENT;
            $SubCategoryLogo = SaveImage($request->file('SubCategoryLogo'), "subcategories", $NextIDSubCategory);
        } else {
            if ($Category->CategoryType == "PROJECT") {
                return RespondWithBadRequest(1);
            }
        }

        $SubCategory = new SubCategory;
        $SubCategory->IDCategory = $IDCategory;
        $SubCategory->SubCategoryNameEn = $SubCategoryNameEn;
        $SubCategory->SubCategoryNameAr = $SubCategoryNameAr;
        $SubCategory->SubCategoryLogo = $SubCategoryLogo;
        $SubCategory->save();

        $Desc = "Sub Category " . $SubCategory->SubCategoryNameEn . " was added";
        ActionBackLog($Admin->IDUser, $SubCategory->IDSubCategory, "ADD_SUBCATEGORY", $Desc);

        return RespondWithSuccessRequest(8);
    }

    public function SubCategoryEditPage($IDSubCategory)
    {
        $SubCategory = SubCategory::find($IDSubCategory);
        if (!$SubCategory) {
            return RespondWithBadRequest(1);
        }

        $SubCategory->SubCategoryLogo =  ($SubCategory->SubCategoryLogo) ? asset($SubCategory->SubCategoryLogo) : '';

        $APICode = APICode::where('IDAPICode', 8)->first();
        $Response = array(
            'Success' => true,
            'ApiMsg' => __('apicodes.' . $APICode->IDApiCode),
            'ApiCode' => $APICode->IDApiCode,
            'Response' => $SubCategory,
        );
        return $Response;
    }

    public function SubCategoryEdit(Request $request)
    {
        $Admin = auth('user')->user();
        $IDSubCategory = $request->IDSubCategory;
        $SubCategoryNameEn = $request->SubCategoryNameEn;
        $SubCategoryNameAr = $request->SubCategoryNameAr;
        $SubCategoryLogo = $request->SubCategoryLogo;
        $Desc = "";

        $SubCategory = SubCategory::find($IDSubCategory);
        if (!$SubCategory) {
            return RespondWithBadRequest(1);
        }

        if ($SubCategoryNameEn) {
            $CategoryRecord = SubCategory::where("SubCategoryNameEn", $SubCategoryNameEn)->where("IDSubCategory", "<>", $IDSubCategory)->where("IDCategory", $SubCategory->IDCategory)->first();
            if ($CategoryRecord) {
                return RespondWithBadRequest(18);
            }
            $Desc = "Sub Category english name was changed from " . $SubCategory->SubCategoryNameEn . " to " . $SubCategoryNameEn;
            $SubCategory->SubCategoryNameEn = $SubCategoryNameEn;
        }
        if ($SubCategoryNameAr) {
            $CategoryRecord = SubCategory::where("SubCategoryNameAr", $SubCategoryNameAr)->where("IDSubCategory", "<>", $IDSubCategory)->where("IDCategory", $SubCategory->IDCategory)->first();
            if ($CategoryRecord) {
                return RespondWithBadRequest(18);
            }
            $Desc = $Desc . ", Sub Category arabic name was changed from " . $SubCategory->SubCategoryNameAr . " to " . $SubCategoryNameAr;
            $SubCategory->SubCategoryNameAr = $SubCategoryNameAr;
        }

        $ImageExtArray = ["jpeg", "jpg", "png", "svg"];
        if ($request->file('SubCategoryLogo')) {
            if (!in_array($request->SubCategoryLogo->extension(), $ImageExtArray)) {
                return RespondWithBadRequest(15);
            }
            if ($SubCategory->SubCategoryLogo) {
                $OldPhoto = substr($SubCategory->SubCategoryLogo, 7);
                Storage::disk('uploads')->delete($OldPhoto);
            }
            $SubCategoryLogo = SaveImage($request->file('SubCategoryLogo'), "subcategories", $IDSubCategory);
            $SubCategory->SubCategoryLogo = $SubCategoryLogo;
            $Desc = $Desc . ", Sub Category logo changed";
        }

        $SubCategory->save();

        ActionBackLog($Admin->IDUser, $SubCategory->IDSubCategory, "EDIT_SUBCATEGORY", $Desc);

        return RespondWithSuccessRequest(8);
    }

    public function SubCategoryAjax(Request $request, SubCategory $SubCategories)
    {
        $IDCategory = $request->IDCategory;
        $CategoryType = $request->CategoryType;


        $SubCategories = $SubCategories->leftjoin("categories", "categories.IDCategory", "subcategories.IDCategory")->where("SubCategoryActive", 1);
        if ($IDCategory) {
            $SubCategories = $SubCategories->where("subcategories.IDCategory", $IDCategory);
        }
        if ($CategoryType) {
            $SubCategories = $SubCategories->where("categories.CategoryType", $CategoryType);
        }

        $SubCategories = $SubCategories->get();
        $SubCategories = SubCategoryResource::collection($SubCategories);

        $APICode = APICode::where('IDAPICode', 8)->first();
        $Response = array(
            'Success' => true,
            'ApiMsg' => __('apicodes.' . $APICode->IDApiCode),
            'ApiCode' => $APICode->IDApiCode,
            'Response' => $SubCategories,
        );
        return $Response;
    }

    public function AdvertisementList(Request $request, Advertisement $Advertisements)
    {
        $IDPage = $request->IDPage;
        $StartDate = $request->StartDate;
        $EndDate = $request->EndDate;
        $AdvertisementLocation = $request->AdvertisementLocation;
        $AdvertisementService = $request->AdvertisementService;

        if (!$IDPage) {
            $IDPage = 0;
        } else {
            $IDPage = ($request->IDPage - 1) * 20;
        }

        $Advertisements = $Advertisements->where("AdvertisementActive", 1);
        if ($AdvertisementLocation) {
            $Advertisements = $Advertisements->where("AdvertisementLocation", $AdvertisementLocation);
        }
        if ($AdvertisementService) {
            $Advertisements = $Advertisements->where("AdvertisementService", $AdvertisementService);
        }
        if ($StartDate) {
            $StartDate = $StartDate . " 00:00:00";
            $Advertisements = $Advertisements->where("AdvertisementStartDate", ">=", $StartDate);
        }
        if ($EndDate) {
            $EndDate = $EndDate . " 23:59:59";
            $Advertisements = $Advertisements->where("AdvertisementStartDate", "<", $EndDate);
        }

        $Pages = ceil($Advertisements->count() / 20);
        $Advertisements = $Advertisements->orderby("IDAdvertisement", "DESC")->skip($IDPage)->take(20)->get();
        $Advertisements = AdvertisementResource::collection($Advertisements);
        $Response = array("Advertisements" => $Advertisements, "Pages" => $Pages);

        $APICode = APICode::where('IDAPICode', 8)->first();
        $Response = array(
            'Success' => true,
            'ApiMsg' => __('apicodes.' . $APICode->IDApiCode),
            'ApiCode' => $APICode->IDApiCode,
            'Response' => $Response,
        );
        return $Response;
    }

    public function AdvertisementStatus($IDAdvertisement)
    {
        $Admin = auth('user')->user();
        $Advertisement = Advertisement::find($IDAdvertisement);
        if (!$Advertisement) {
            return RespondWithBadRequest(1);
        }
        $Advertisement->AdvertisementActive = !$Advertisement->AdvertisementActive;
        $Advertisement->save();

        $Desc = "status changed";
        ActionBackLog($Admin->IDUser, $Advertisement->IDAdvertisement, "EDIT_ADS", $Desc);
        return RespondWithSuccessRequest(8);
    }

    public function AdvertisementAdd(Request $request)
    {
        $Admin = auth('user')->user();
        if (!$Admin) {
            return RespondWithBadRequest(10);
        }
        $IDLink = $request->IDLink;
        $AdvertisementStartDate = $request->AdvertisementStartDate;
        $AdvertisementEndDate = $request->AdvertisementEndDate;
        $AdvertisementService = $request->AdvertisementService;
        $AdvertisementLocation = $request->AdvertisementLocation;

        if (!$AdvertisementLocation) {
            return RespondWithBadRequest(1);
        }
        if (!$AdvertisementService) {
            return RespondWithBadRequest(1);
        }

        if (($AdvertisementStartDate && !$AdvertisementEndDate) || ($AdvertisementEndDate && !$AdvertisementStartDate) || ($AdvertisementEndDate < $AdvertisementStartDate)) {
            return RespondWithBadRequest(1);
        }

        $ImageExtArray = ["jpeg", "jpg", "png", "svg"];
        if ($request->file('AdvertisementImage')) {
            if (!in_array($request->AdvertisementImage->extension(), $ImageExtArray)) {
                return RespondWithBadRequest(15);
            }
            $NextIDAdvertisement = DB::select('SELECT AUTO_INCREMENT FROM information_schema.TABLES WHERE  TABLE_NAME = "advertisements"')[0]->AUTO_INCREMENT;
            $AdvertisementImage = SaveImage($request->file('AdvertisementImage'), "ads", $NextIDAdvertisement);
        } else {
            return RespondWithBadRequest(1);
        }

        if ($AdvertisementEndDate) {
            $AdvertisementEndDate = $AdvertisementEndDate . " 23:59:59";
        }

        $Advertisement = new Advertisement;
        $Advertisement->AdvertisementService = $AdvertisementService;
        $Advertisement->AdvertisementLocation = $AdvertisementLocation;
        $Advertisement->AdvertisementStartDate = $AdvertisementStartDate;
        $Advertisement->AdvertisementEndDate = $AdvertisementEndDate;
        $Advertisement->IDLink = $IDLink;
        $Advertisement->AdvertisementImage = $AdvertisementImage;
        $Advertisement->save();

        $Desc = "Ad Added";
        ActionBackLog($Admin->IDUser, $Advertisement->IDAdvertisement, "ADD_ADS", $Desc);

        return RespondWithSuccessRequest(8);
    }

    public function AdvertisementEditPage($IDAdvertisement)
    {
        $Advertisement = Advertisement::find($IDAdvertisement);
        if (!$Advertisement) {
            return RespondWithBadRequest(1);
        }

        $Advertisement->AdvertisementImage = ($Advertisement->AdvertisementImage) ? asset($Advertisement->AdvertisementImage) : '';

        $APICode = APICode::where('IDAPICode', 8)->first();
        $Response = array(
            'Success' => true,
            'ApiMsg' => __('apicodes.' . $APICode->IDApiCode),
            'ApiCode' => $APICode->IDApiCode,
            'Response' => $Advertisement,
        );
        return $Response;
    }

    public function AdvertisementEdit(Request $request)
    {
        $Admin = auth('user')->user();
        $IDAdvertisement = $request->IDAdvertisement;
        $IDLink = $request->IDLink;
        $AdvertisementStartDate = $request->AdvertisementStartDate;
        $AdvertisementEndDate = $request->AdvertisementEndDate;
        $AdvertisementService = $request->AdvertisementService;
        $AdvertisementLocation = $request->AdvertisementLocation;
        $Desc = "";

        if (($AdvertisementStartDate && !$AdvertisementEndDate) || ($AdvertisementEndDate && !$AdvertisementStartDate) || ($AdvertisementEndDate < $AdvertisementStartDate)) {
            return RespondWithBadRequest(1);
        }

        $Advertisement = Advertisement::find($IDAdvertisement);
        if (!$Advertisement) {
            return RespondWithBadRequest(1);
        }

        $ImageExtArray = ["jpeg", "jpg", "png", "svg"];
        if ($request->file('AdvertisementImage')) {
            if (!in_array($request->AdvertisementImage->extension(), $ImageExtArray)) {
                return RespondWithBadRequest(15);
            }
            if ($Advertisement->AdvertisementImage) {
                $OldPhoto = substr($Advertisement->AdvertisementImage, 7);
                Storage::disk('uploads')->delete($OldPhoto);
            }
            $AdvertisementImage = SaveImage($request->file('AdvertisementImage'), "ads", $IDAdvertisement);
            $Advertisement->AdvertisementImage = $AdvertisementImage;
            $Desc = "Ad Logo Changed";
        }

        if ($AdvertisementService) {
            $Desc = $Desc . ", Ad Service Changed From " . $Advertisement->AdvertisementService . " to " . $AdvertisementService;
            $Advertisement->AdvertisementService = $AdvertisementService;
        }
        if ($AdvertisementStartDate) {
            $Desc = $Desc . ", Ad Start Date Changed From " . $Advertisement->AdvertisementStartDate . " to " . $AdvertisementStartDate;
            $Advertisement->AdvertisementStartDate = $AdvertisementStartDate;
        }
        if ($AdvertisementEndDate) {
            $AdvertisementEndDate = $AdvertisementEndDate . " 23:59:59";
            $Desc = $Desc . ", Ad End Date Changed From " . $Advertisement->AdvertisementEndDate . " to " . $AdvertisementEndDate;
            $Advertisement->AdvertisementEndDate = $AdvertisementEndDate;
        }
        if ($AdvertisementLocation) {
            $Desc = $Desc . ", Ad Location Changed From " . $Advertisement->AdvertisementLocation . " to " . $AdvertisementLocation;
            $Advertisement->AdvertisementLocation = $AdvertisementLocation;
        }
        if ($IDLink) {
            $Desc = $Desc . ", Ad Link Changed";
            $Advertisement->IDLink = $IDLink;
        }

        $Advertisement->save();

        ActionBackLog($Admin->IDUser, $Advertisement->IDAdvertisement, "EDIT_ADS", $Desc);
        return RespondWithSuccessRequest(8);
    }

    public function GeneralSettingList()
    {
        $User = auth('user')->user();
        if (!$User) {
            return RespondWithBadRequest(10);
        }

        $UserLanguage = AdminLanguage($User->UserLanguage);
        $GeneralSettingName = "GeneralSettingName" . $UserLanguage;
        $GeneralSettingDescription = "GeneralSettingDescription" . $UserLanguage;

        $GeneralSettings = GeneralSetting::where("GeneralSettingHidden", 0)->where("GeneralSettingType", "SETTING")->select("IDGeneralSetting", "GeneralSettingNameEn", "GeneralSettingNameAr", "GeneralSettingValue", "GeneralSettingDescriptionEn", "GeneralSettingDescriptionAr", "GeneralSettingName as GeneralSettingKey")->get();
        foreach ($GeneralSettings as $Setting) {
            $Setting->GeneralSettingName = $Setting->$GeneralSettingName;
            $Setting->GeneralSettingDescription = $Setting->$GeneralSettingDescription;
            unset($Setting["GeneralSettingNameEn"]);
            unset($Setting["GeneralSettingNameAr"]);
            unset($Setting["GeneralSettingDescriptionEn"]);
            unset($Setting["GeneralSettingDescriptionAr"]);
        }
        $APICode = APICode::where('IDAPICode', 8)->first();
        $Response = array(
            'Success' => true,
            'ApiMsg' => __('apicodes.' . $APICode->IDApiCode),
            'ApiCode' => $APICode->IDApiCode,
            'Response' => $GeneralSettings,
        );
        return $Response;
    }

    public function GeneralContactList()
    {
        $User = auth('user')->user();
        if (!$User) {
            return RespondWithBadRequest(10);
        }

        $UserLanguage = AdminLanguage($User->UserLanguage);
        $GeneralSettingName = "GeneralSettingName" . $UserLanguage;
        $GeneralSettingDescription = "GeneralSettingDescription" . $UserLanguage;

        $GeneralSettings = GeneralSetting::where("GeneralSettingHidden", 0)->where("GeneralSettingType", "CONTACT")->select("IDGeneralSetting", "GeneralSettingNameEn", "GeneralSettingNameAr", "GeneralSettingValue", "GeneralSettingDescriptionEn", "GeneralSettingDescriptionAr")->get();
        foreach ($GeneralSettings as $Setting) {
            $Setting->GeneralSettingName = $Setting->$GeneralSettingName;
            $Setting->GeneralSettingDescription = $Setting->$GeneralSettingDescription;
            unset($Setting["GeneralSettingNameEn"]);
            unset($Setting["GeneralSettingNameAr"]);
            unset($Setting["GeneralSettingDescriptionEn"]);
            unset($Setting["GeneralSettingDescriptionAr"]);
        }

        $APICode = APICode::where('IDAPICode', 8)->first();
        $Response = array(
            'Success' => true,
            'ApiMsg' => __('apicodes.' . $APICode->IDApiCode),
            'ApiCode' => $APICode->IDApiCode,
            'Response' => $GeneralSettings,
        );
        return $Response;
    }

    public function GeneralSettingEdit(Request $request)
    {
        $Admin = auth('user')->user();
        $User = auth('user')->user();
        if (!$User) {
            return RespondWithBadRequest(10);
        }

        $IDGeneralSetting = $request->IDGeneralSetting;
        $GeneralSettingValue = $request->GeneralSettingValue;
        if (!$IDGeneralSetting) {
            return RespondWithBadRequest(1);
        }
        if (!$GeneralSettingValue) {
            return RespondWithBadRequest(1);
        }

        $GeneralSetting = GeneralSetting::find($IDGeneralSetting);
        if (!$GeneralSetting) {
            return RespondWithBadRequest(1);
        }
        $Desc = "General Setting Value Changed From " . $GeneralSetting->GeneralSettingValue . " to " . $GeneralSettingValue;
        $GeneralSetting->GeneralSettingValue = $GeneralSettingValue;
        $GeneralSetting->save();

        ActionBackLog($Admin->IDUser, $GeneralSetting->IDGeneralSetting, "EDIT_GENERALSETTINGS", $Desc);
        return RespondWithSuccessRequest(8);
    }

    public function ContactUs(Request $request)
    {
        $User = auth('user')->user();
        if (!$User) {
            return RespondWithBadRequest(10);
        }

        $ContactApp = $request->ContactApp;
        $IDPage = $request->IDPage;
        if (!$IDPage) {
            $IDPage = 0;
        } else {
            $IDPage = ($request->IDPage - 1) * 20;
        }

        $ContactUs = ContactUs::where("ContactApp", $ContactApp);
        $Pages = ceil($ContactUs->count() / 20);
        $ContactUs = $ContactUs->orderby("IDContactUs", "DESC")->skip($IDPage)->take(20)->get();
        $Response = array("ContactUs" => $ContactUs, "Pages" => $Pages);

        $APICode = APICode::where('IDAPICode', 8)->first();
        $Response = array(
            'Success' => true,
            'ApiMsg' => __('apicodes.' . $APICode->IDApiCode),
            'ApiCode' => $APICode->IDApiCode,
            'Response' => $Response,
        );
        return $Response;
    }

    public function SocialMedia(Request $request, SocialMedia $SocialMedia)
    {
        $SocialMediaActive = $request->SocialMediaActive;
        if ($SocialMediaActive == 1) {
            $SocialMedia = $SocialMedia->where("SocialMediaActive", 1);
        }
        if ($SocialMediaActive == '0') {
            $SocialMedia = $SocialMedia->where("SocialMediaActive", 0);
        }
        $SocialMedia = $SocialMedia->get();
        foreach ($SocialMedia as $Media) {
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

    public function SocialMediaStatus($IDSocialMedia)
    {
        $Admin = auth('user')->user();
        $SocialMedia = SocialMedia::find($IDSocialMedia);
        if (!$SocialMedia) {
            return RespondWithBadRequest(1);
        }

        $SocialMedia->SocialMediaActive = !$SocialMedia->SocialMediaActive;
        $SocialMedia->save();

        $Desc = "Social Media " . $SocialMedia->SocialMediaName . " status changed";
        ActionBackLog($Admin->IDUser, $SocialMedia->IDSocialMedia, "EDIT_SOCIAL", $Desc);
        return RespondWithSuccessRequest(8);
    }

    public function SocialMediaAdd(Request $request)
    {
        $Admin = auth('user')->user();
        $SocialMediaName = $request->SocialMediaName;
        if (!$SocialMediaName) {
            return RespondWithBadRequest(1);
        }

        $SocialMedia = SocialMedia::where("SocialMediaName", $SocialMediaName)->first();
        if ($SocialMedia) {
            return RespondWithBadRequest(18);
        }

        $ImageExtArray = ["jpeg", "jpg", "png", "svg"];
        if ($request->file('SocialMediaIcon')) {
            if (!in_array($request->SocialMediaIcon->extension(), $ImageExtArray)) {
                return RespondWithBadRequest(15);
            }
            $NextIDSocialMedia = DB::select('SELECT AUTO_INCREMENT FROM information_schema.TABLES WHERE  TABLE_NAME = "socialmedia"')[0]->AUTO_INCREMENT;
            $SocialMediaIcon = SaveImage($request->file('SocialMediaIcon'), "socialmedia", $NextIDSocialMedia);
        } else {
            return RespondWithBadRequest(1);
        }

        $SocialMedia = new SocialMedia;
        $SocialMedia->SocialMediaName = $SocialMediaName;
        $SocialMedia->SocialMediaIcon = $SocialMediaIcon;
        $SocialMedia->save();

        $Desc = "Social Media " . $SocialMedia->SocialMediaName . " was added";
        ActionBackLog($Admin->IDUser, $SocialMedia->IDSocialMedia, "ADD_SOCIAL", $Desc);

        return RespondWithSuccessRequest(8);
    }

    public function SocialMediaEditPage($IDSocialMedia)
    {
        $SocialMedia = SocialMedia::find($IDSocialMedia);
        if (!$SocialMedia) {
            return RespondWithBadRequest(1);
        }
        $SocialMedia->SocialMediaIcon = ($SocialMedia->SocialMediaIcon) ? asset($SocialMedia->SocialMediaIcon) : '';

        $APICode = APICode::where('IDAPICode', 8)->first();
        $Response = array(
            'Success' => true,
            'ApiMsg' => __('apicodes.' . $APICode->IDApiCode),
            'ApiCode' => $APICode->IDApiCode,
            'Response' => $SocialMedia,
        );
        return $Response;
    }

    public function SocialMediaEdit(Request $request)
    {
        $Admin = auth('user')->user();
        $IDSocialMedia = $request->IDSocialMedia;
        $SocialMediaName = $request->SocialMediaName;
        $Desc = "";

        $SocialMedia = SocialMedia::find($IDSocialMedia);
        if (!$SocialMedia) {
            return RespondWithBadRequest(1);
        }

        if ($SocialMediaName) {
            $SocialMediaRecord = SocialMedia::where("SocialMediaName", $SocialMediaName)->where("IDSocialMedia", "<>", $IDSocialMedia)->first();
            if ($SocialMediaRecord) {
                return RespondWithBadRequest(18);
            }
            $Desc = "Social Media name changed from " . $SocialMedia->SocialMediaName . " to " . $SocialMediaName;
            $SocialMedia->SocialMediaName = $SocialMediaName;
        }


        $ImageExtArray = ["jpeg", "jpg", "png", "svg"];
        if ($request->file('SocialMediaIcon')) {
            if (!in_array($request->SocialMediaIcon->extension(), $ImageExtArray)) {
                return RespondWithBadRequest(15);
            }
            $OldPhoto = substr($SocialMedia->SocialMediaIcon, 7);
            Storage::disk('uploads')->delete($OldPhoto);
            $SocialMediaIcon = SaveImage($request->file('SocialMediaIcon'), "socialmedia", $IDSocialMedia);
            $SocialMedia->SocialMediaIcon = $SocialMediaIcon;
            $Desc = $Desc . ", Social Media icon changed";
        }

        $SocialMedia->save();

        ActionBackLog($Admin->IDUser, $SocialMedia->IDSocialMedia, "EDIT_SOCIAL", $Desc);

        return RespondWithSuccessRequest(8);
    }

    public function EventList(Request $request, Event $Events)
    {
        $IDPage = $request->IDPage;
        $SearchKey = $request->SearchKey;
        $EventStatus = $request->EventStatus;
        $EventStartTime = $request->EventStartTime;
        $EventEndTime = $request->EventEndTime;
        if (!$IDPage) {
            $IDPage = 0;
        } else {
            $IDPage = ($request->IDPage - 1) * 20;
        }

        $Events = $Events->leftjoin("areas", "areas.IDArea", "events.IDArea")->leftjoin("cities", "cities.IDCity", "areas.IDCity")->where("events.EventDeleted", 0);
        if ($SearchKey) {
            $Events = $Events->where(function ($query) use ($SearchKey) {
                $query->where('events.EventTitleEn', 'like', '%' . $SearchKey . '%')
                    ->orwhere('events.EventTitleAr', 'like', '%' . $SearchKey . '%')
                    ->orwhere('events.EventDescEn', 'like', '%' . $SearchKey . '%')
                    ->orwhere('events.EventDescAr', 'like', '%' . $SearchKey . '%');
            });
        }
        if ($EventStatus) {
            $Events = $Events->where("events.EventStatus", $EventStatus);
        }
        if ($EventStartTime) {
            $Events = $Events->where("events.EventStartTime", ">=", $EventStartTime);
        }
        if ($EventEndTime) {
            $Events = $Events->where("events.EventStartTime", "<=", $EventEndTime);
        }

        $Pages = ceil($Events->count() / 20);
        $Events = $Events->orderby("events.IDEvent", "DESC")->skip($IDPage)->take(20)->get();
        $Events = EventResource::collection($Events);
        $Response = array("Events" => $Events, "Pages" => $Pages);

        $APICode = APICode::where('IDAPICode', 8)->first();
        $Response = array(
            'Success' => true,
            'ApiMsg' => __('apicodes.' . $APICode->IDApiCode),
            'ApiCode' => $APICode->IDApiCode,
            'Response' => $Response,
        );
        return $Response;
    }

    public function EventStatus(Request $request)
    {
        $Admin = auth('user')->user();
        $IDEvent = $request->IDEvent;
        $EventStatus = $request->EventStatus;
        if (!$IDEvent) {
            return RespondWithBadRequest(1);
        }
        if (!$EventStatus) {
            return RespondWithBadRequest(1);
        }

        $Event = Event::find($IDEvent);
        if ($EventStatus == "DELETED") {
            if ($Event->EventStatus == "ONGOING") {
                return RespondWithBadRequest(1);
            }
            $EventAttendee = EventAttendee::where("EventAttendeeStatus", "<>", "CANCELLED")->where("EventAttendeeStatus", "<>", "REMOVED")->where("IDEvent", $IDEvent)->first();
            if ($EventAttendee) {
                return RespondWithBadRequest(32);
            }
            $Desc = "Event " . $Event->EventTitleEn . " was deleted";
            $Event->EventDeleted = 1;
        } else {
            $Desc = "Event changed status from " . $Event->EventStatus . " to " . $EventStatus;
            $Event->EventStatus = $EventStatus;
        }
        $Event->save();

        ActionBackLog($Admin->IDUser, $Event->IDEvent, "EDIT_EVENT", $Desc);

        return RespondWithSuccessRequest(8);
    }

    public function EventAdd(Request $request)
    {
        $Admin = auth('user')->user();
        $IDArea = $request->IDArea;
        $EventTitleEn = $request->EventTitleEn;
        $EventTitleAr = $request->EventTitleAr;
        $EventDescEn = $request->EventDescEn;
        $EventDescAr = $request->EventDescAr;
        $EventPolicyEn = $request->EventPolicyEn;
        $EventPolicyAr = $request->EventPolicyAr;
        $EventStartTime = $request->EventStartTime;
        $EventEndTime = $request->EventEndTime;
        $EventInstallmentEndDate = $request->EventInstallmentEndDate;
        $EventLatitude = $request->EventLatitude;
        $EventLongitude = $request->EventLongitude;
        $EventAddress = $request->EventAddress;
        $EventPrice = $request->EventPrice;
        $EventPoints = $request->EventPoints;
        $EventMaxNumber = $request->EventMaxNumber;
        $EventGallery = $request->EventGallery;
        $EventVideos = $request->EventVideos;
        $EventLogo = $request->EventLogo;
        if (!$IDArea) {
            return RespondWithBadRequest(1);
        }
        if (!$EventTitleEn) {
            return RespondWithBadRequest(1);
        }
        if (!$EventTitleAr) {
            return RespondWithBadRequest(1);
        }
        if (!$EventDescEn) {
            return RespondWithBadRequest(1);
        }
        if (!$EventDescAr) {
            return RespondWithBadRequest(1);
        }
        if (!$EventStartTime) {
            return RespondWithBadRequest(1);
        }
        if (!$EventEndTime) {
            return RespondWithBadRequest(1);
        }
        if (!$EventLatitude) {
            $EventLatitude = 0;
        }
        if (!$EventPoints) {
            $EventPoints = 0;
        }
        if (!$EventLongitude) {
            $EventLongitude = 0;
        }
        if (!$EventInstallmentEndDate) {
            $EventInstallmentEndDate = $EventStartTime;
        }
        if (!$EventPrice) {
            return RespondWithBadRequest(1);
        }
        if (!$EventMaxNumber) {
            return RespondWithBadRequest(1);
        }
        if (!$EventGallery) {
            return RespondWithBadRequest(1);
        }
        if (!count($EventGallery)) {
            return RespondWithBadRequest(1);
        }

        $ImageExtArray = ["jpeg", "jpg", "png", "svg"];
        foreach ($EventGallery as $Gallery) {
            if (!in_array($Gallery->extension(), $ImageExtArray)) {
                return RespondWithBadRequest(15);
            }
        }

        if ($EventLogo) {
            if (!in_array($EventLogo->extension(), $ImageExtArray)) {
                return RespondWithBadRequest(15);
            }
        }

        if ($EventInstallmentEndDate > $EventStartTime) {
            return RespondWithBadRequest(1);
        }

        $Event = new Event;
        $Event->IDArea = $IDArea;
        $Event->EventTitleEn = $EventTitleEn;
        $Event->EventTitleAr = $EventTitleAr;
        $Event->EventDescEn = $EventDescEn;
        $Event->EventDescAr = $EventDescAr;
        $Event->EventPolicyEn = $EventPolicyEn;
        $Event->EventPolicyAr = $EventPolicyAr;
        $Event->EventStartTime = $EventStartTime;
        $Event->EventEndTime = $EventEndTime;
        $Event->EventInstallmentEndDate = $EventInstallmentEndDate;
        $Event->EventLatitude = $EventLatitude;
        $Event->EventLongitude = $EventLongitude;
        $Event->EventAddress = $EventAddress;
        $Event->EventPrice = $EventPrice;
        $Event->EventPoints = $EventPoints;
        $Event->EventMaxNumber = $EventMaxNumber;
        $Event->EventStatus = "PENDING";
        $Event->save();

        if ($EventLogo) {
            $Image = SaveImage($EventLogo, "events", $Event->IDEvent);
            $EventGalleryRow = new EventGallery;
            $EventGalleryRow->IDEvent = $Event->IDEvent;
            $EventGalleryRow->EventGalleryPath = $Image;
            $EventGalleryRow->EventGalleryType = "LOGO";
            $EventGalleryRow->save();
        }

        foreach ($EventGallery as $Gallery) {
            $Image = SaveImage($Gallery, "events", $Event->IDEvent);
            $EventGalleryRow = new EventGallery;
            $EventGalleryRow->IDEvent = $Event->IDEvent;
            $EventGalleryRow->EventGalleryPath = $Image;
            $EventGalleryRow->EventGalleryType = "IMAGE";
            $EventGalleryRow->save();
        }

        if ($EventVideos) {
            if (count($EventVideos)) {
                foreach ($EventVideos as $Video) {
                    $EventVideo = YoutubeEmbedUrl($Video);
                    $EventGalleryRow = new EventGallery;
                    $EventGalleryRow->IDEvent = $Event->IDEvent;
                    $EventGalleryRow->EventGalleryPath = $EventVideo;
                    $EventGalleryRow->EventGalleryType = "VIDEO";
                    $EventGalleryRow->save();
                }
            }
        }

        $Desc = "Event " . $Event->EventTitleEn . " was added";
        ActionBackLog($Admin->IDUser, $Event->IDEvent, "ADD_EVENT", $Desc);
        return RespondWithSuccessRequest(8);
    }

    public function EventDetails(Request $request)
    {
        $IDEvent = $request->IDEvent;
        $IDPage = $request->IDPage;
        $SearchKey = $request->SearchKey;
        if (!$IDEvent) {
            return RespondWithBadRequest(1);
        }
        if (!$IDPage) {
            $IDPage = 0;
        } else {
            $IDPage = ($request->IDPage - 1) * 20;
        }

        $Event = Event::leftjoin("areas", "areas.IDArea", "events.IDArea")->leftjoin("cities", "cities.IDCity", "areas.IDCity")->where("events.IDEvent", $IDEvent)->first();
        if (!$Event) {
            return RespondWithBadRequest(1);
        }

        $EventGallery = EventGallery::where("IDEvent", $IDEvent)->where("EventGalleryDeleted", 0)->orderby("EventGalleryType")->get();
        foreach ($EventGallery as $Gallery) {
            if ($Gallery->EventGalleryType != "VIDEO") {
                $Gallery->EventGalleryPath = asset($Gallery->EventGalleryPath);
            }
        }

        $EventAttendees = EventAttendee::leftjoin("clients", "clients.IDClient", "eventattendees.IDClient")->where("eventattendees.IDEvent", $IDEvent);
        if ($SearchKey) {
            $EventAttendees = $EventAttendees->where(function ($query) use ($SearchKey) {
                $query->where('clients.ClientName', 'like', '%' . $SearchKey . '%')
                    ->orwhere('clients.ClientPhone', 'like', '%' . $SearchKey . '%');
            });
        }

        $EventAttendees = $EventAttendees->select("eventattendees.IDEventAttendee", "clients.IDClient", "clients.ClientName", "clients.ClientPhone", "eventattendees.EventAttendeePaidAmount", "eventattendees.EventAttendeeStatus", "eventattendees.created_at");
        $Pages = ceil($EventAttendees->count() / 20);
        $EventAttendees = $EventAttendees->skip($IDPage)->take(20)->get();

        $User = auth('user')->user();
        if ($User) {
            $UserLanguage = AdminLanguage($User->UserLanguage);
            $AreaName = "AreaName" . $UserLanguage;
            $CityName = "CityName" . $UserLanguage;
        } else {
            $CityName = "CityNameEn";
            $AreaName = "AreaNameEn";
        }

        $Event->CityName = $Event->$CityName;
        $Event->AreaName = $Event->$AreaName;
        $Event->EventGallery = $EventGallery;
        $Event->EventAttendees = $EventAttendees;
        $Response = array("Event" => $Event, "Pages" => $Pages);

        $APICode = APICode::where('IDAPICode', 8)->first();
        $Response = array(
            'Success' => true,
            'ApiMsg' => __('apicodes.' . $APICode->IDApiCode),
            'ApiCode' => $APICode->IDApiCode,
            'Response' => $Response,
        );
        return $Response;
    }

    public function EventEdit(Request $request)
    {
        $Admin = auth('user')->user();
        $IDEvent = $request->IDEvent;
        $IDArea = $request->IDArea;
        $EventTitleEn = $request->EventTitleEn;
        $EventTitleAr = $request->EventTitleAr;
        $EventDescEn = $request->EventDescEn;
        $EventDescAr = $request->EventDescAr;
        $EventPolicyEn = $request->EventPolicyEn;
        $EventPolicyAr = $request->EventPolicyAr;
        $EventStartTime = $request->EventStartTime;
        $EventEndTime = $request->EventEndTime;
        $EventInstallmentEndDate = $request->EventInstallmentEndDate;
        $EventLatitude = $request->EventLatitude;
        $EventLongitude = $request->EventLongitude;
        $EventAddress = $request->EventAddress;
        $EventPrice = $request->EventPrice;
        $EventPoints = $request->EventPoints;
        $EventMaxNumber = $request->EventMaxNumber;
        $EventGallery = $request->EventGallery;
        $EventVideos = $request->EventVideos;
        $EventLogo = $request->EventLogo;
        $Desc = "";

        if (!$IDEvent) {
            return RespondWithBadRequest(1);
        }

        $Event = Event::find($IDEvent);
        if ($IDArea) {
            $Event->IDArea = $IDArea;
            $Desc = "Event Area Changed";
        }
        if ($EventTitleEn) {
            $Desc = $Desc . ", Event english title changed from " . $Event->EventTitleEn . " to " . $EventTitleEn;
            $Event->EventTitleEn = $EventTitleEn;
        }
        if ($EventTitleAr) {
            $Desc = $Desc . ", Event arabic title changed from " . $Event->EventTitleAr . " to " . $EventTitleAr;
            $Event->EventTitleAr = $EventTitleAr;
        }
        if ($EventDescEn) {
            $Desc = $Desc . ", Event arabic desc changed from " . $Event->EventDescEn . " to " . $EventDescEn;
            $Event->EventDescEn = $EventDescEn;
        }
        if ($EventDescAr) {
            $Desc = $Desc . ", Event english desc changed from " . $Event->EventDescAr . " to " . $EventDescAr;
            $Event->EventDescAr = $EventDescAr;
        }
        if ($EventPolicyEn) {
            $Desc = $Desc . ", Event english policy changed from " . $Event->EventPolicyEn . " to " . $EventPolicyEn;
            $Event->EventPolicyEn = $EventPolicyEn;
        }
        if ($EventPolicyAr) {
            $Desc = $Desc . ", Event arabic policy changed from " . $Event->EventPolicyAr . " to " . $EventPolicyAr;
            $Event->EventPolicyAr = $EventPolicyAr;
        }
        if ($EventStartTime) {
            $Desc = $Desc . ", Event start date changed from " . $Event->EventStartTime . " to " . $EventStartTime;
            $Event->EventStartTime = $EventStartTime;
        }
        if ($EventEndTime) {
            $Desc = $Desc . ", Event end date changed from " . $Event->EventEndTime . " to " . $EventEndTime;
            $Event->EventEndTime = $EventEndTime;
        }
        if ($EventPrice) {
            $Desc = $Desc . ", Event price  changed from " . $Event->EventPrice . " to " . $EventPrice;
            $Event->EventPrice = $EventPrice;
        }
        if ($EventPoints) {
            $Desc = $Desc . ", Event points  changed from " . $Event->EventPoints . " to " . $EventPoints;
            $Event->EventPoints = $EventPoints;
        }
        if ($EventAddress) {
            $Desc = $Desc . ", Event address  changed from " . $Event->EventAddress . " to " . $EventAddress;
            $Event->EventAddress = $EventAddress;
        }
        if ($EventMaxNumber) {
            $Desc = $Desc . ", Event Max no. changed from " . $Event->EventMaxNumber . " to " . $EventMaxNumber;
            $Event->EventMaxNumber = $EventMaxNumber;
        }
        if ($EventInstallmentEndDate) {
            if ($EventInstallmentEndDate > $Event->EventStartTime) {
                return RespondWithBadRequest(1);
            }
            $Desc = $Desc . ", Event Installment End Date changed from " . $Event->EventInstallmentEndDate . " to " . $EventInstallmentEndDate;
            $Event->EventInstallmentEndDate = $EventInstallmentEndDate;
        }
        if ($EventLatitude) {
            $Desc = $Desc . ", Event Latitude changed from " . $Event->EventLatitude . " to " . $EventLatitude;
            $Event->EventLatitude = $EventLatitude;
        }
        if ($EventLongitude) {
            $Desc = $Desc . ", Event Longitude changed from " . $Event->EventLongitude . " to " . $EventLongitude;
            $Event->EventLongitude = $EventLongitude;
        }
        $Event->EventStatus = "PENDING";
        $Event->save();

        $ImageExtArray = ["jpeg", "jpg", "png", "svg"];
        if ($EventLogo) {
            if (!in_array($EventLogo->extension(), $ImageExtArray)) {
                return RespondWithBadRequest(15);
            }
            $EventGalleryLogo = EventGallery::where("IDEvent", $IDEvent)->where("EventGalleryType", "LOGO")->where("EventGalleryDeleted", 0)->first();
            if ($EventGalleryLogo) {
                $OldDocument = substr($EventGalleryLogo->EventGalleryPath, 7);
                Storage::disk('uploads')->delete($OldDocument);
                $EventGalleryLogo->EventGalleryDeleted = 1;
                $EventGalleryLogo->save();
            }
            $Image = SaveImage($EventLogo, "events", $yIDEvent);
            $EventGalleryRow = new EventGallery;
            $EventGalleryRow->IDEvent = $IDEvent;
            $EventGalleryRow->EventGalleryPath = $Image;
            $EventGalleryRow->EventGalleryType = "LOGO";
            $EventGalleryRow->save();
            $Desc = $Desc . ", Event Logo changed";
        }

        if ($EventGallery) {
            foreach ($EventGallery as $Gallery) {
                if (!in_array($Gallery->extension(), $ImageExtArray)) {
                    return RespondWithBadRequest(15);
                }
            }
            foreach ($EventGallery as $Gallery) {
                $Image = SaveImage($Gallery, "events", $Event->IDEvent);
                $EventGalleryRow = new EventGallery;
                $EventGalleryRow->IDEvent = $Event->IDEvent;
                $EventGalleryRow->EventGalleryPath = $Image;
                $EventGalleryRow->EventGalleryType = "IMAGE";
                $EventGalleryRow->save();
            }
            $Desc = $Desc . ", Event Images Added";
        }

        if ($EventVideos) {
            if (count($EventVideos)) {
                foreach ($EventVideos as $Video) {
                    $EventVideo = YoutubeEmbedUrl($Video);
                    $EventGallery = new EventGallery;
                    $EventGallery->IDEvent = $Event->IDEvent;
                    $EventGallery->EventGalleryPath = $EventVideo;
                    $EventGallery->EventGalleryType = "VIDEO";
                    $EventGallery->save();
                }
            }
            $Desc = $Desc . ", Event Videos Added";
        }

        ActionBackLog($Admin->IDUser, $Event->IDEvent, "EDIT_EVENT", $Desc);
        return RespondWithSuccessRequest(8);
    }

    public function EventGalleryRemove($IDEventGallery)
    {
        $Admin = auth('user')->user();
        $EventGallery = EventGallery::find($IDEventGallery);
        if (!$EventGallery) {
            return RespondWithBadRequest(1);
        }

        if ($EventGallery->EventGalleryType == "IMAGE") {
            $OldDocument = substr($EventGallery->EventGalleryPath, 7);
            Storage::disk('uploads')->delete($OldDocument);
        }

        $EventGallery->EventGalleryDeleted = 1;
        $EventGallery->save();

        $Desc = "Event Gallery Removed";
        ActionBackLog($Admin->IDUser, $EventGallery->IDEvent, "EDIT_EVENT", $Desc);
        return RespondWithSuccessRequest(8);
    }

    public function EventAttendeeRemove($IDEventAttendee)
    {
        $Admin = auth('user')->user();
        $EventAttendee = EventAttendee::find($IDEventAttendee);
        if (!$EventAttendee) {
            return RespondWithBadRequest(1);
        }

        if ($EventAttendee->EventAttendeeStatus == "REMOVED" || $EventAttendee->EventAttendeeStatus == "CANCELLED") {
            return RespondWithBadRequest(1);
        }

        $Client = Client::find($EventAttendee->IDClient);
        $PlanNetwork = PlanNetwork::where("IDClient", $Client->IDClient)->first();
        $BatchNumber = "#T" . $EventAttendee->IDEventAttendee;
        $TimeFormat = new DateTime('now');
        $Time = $TimeFormat->format('H');
        $Time = $Time . $TimeFormat->format('i');
        $BatchNumber = $BatchNumber . $Time;
        $EventPoints = 0;
        $Amount = $EventAttendee->EventAttendeePaidAmount;

        if ($EventAttendee->EventAttendeeStatus == "PAID") {
            $Event = Event::find($EventAttendee->IDEvent);
            $EventPoints = $Event->EventPoints;
        }

        AdjustLedger($Client, $Amount, $EventPoints, 0, 0, $PlanNetwork, "EVENT", "WALLET", "CANCELLATION", $BatchNumber);

        $EventAttendee->EventAttendeeStatus = "REMOVED";
        $EventAttendee->save();

        $Desc = "Client " . $Client->ClientName . " was Removed from event";
        ActionBackLog($Admin->IDUser, $EventAttendee->IDEventAttendee, "REMOVE_EVENT_ATTENDEE", $Desc);

        return RespondWithSuccessRequest(8);
    }

    public function ToolList(Request $request, Tool $Tools)
    {
        $IDPage = $request->IDPage;
        $SearchKey = $request->SearchKey;
        $ToolStatus = $request->ToolStatus;
        if (!$IDPage) {
            $IDPage = 0;
        } else {
            $IDPage = ($request->IDPage - 1) * 20;
        }

        $Tools = $Tools->where("ToolDeleted", 0);
        if ($SearchKey) {
            $Tools = $Tools->where(function ($query) use ($SearchKey) {
                $query->where('ToolTitleEn', 'like', '%' . $SearchKey . '%')
                    ->orwhere('ToolTitleAr', 'like', '%' . $SearchKey . '%')
                    ->orwhere('ToolDescEn', 'like', '%' . $SearchKey . '%')
                    ->orwhere('ToolDescAr', 'like', '%' . $SearchKey . '%');
            });
        }
        if ($ToolStatus) {
            $Tools = $Tools->where("ToolStatus", $ToolStatus);
        }

        $Pages = ceil($Tools->count() / 20);
        $Tools = $Tools->orderby("IDTool", "DESC")->skip($IDPage)->take(20)->get();
        $Tools = ToolResource::collection($Tools);
        $Response = array("Tools" => $Tools, "Pages" => $Pages);

        $APICode = APICode::where('IDAPICode', 8)->first();
        $Response = array(
            'Success' => true,
            'ApiMsg' => __('apicodes.' . $APICode->IDApiCode),
            'ApiCode' => $APICode->IDApiCode,
            'Response' => $Response,
        );
        return $Response;
    }

    public function ToolStatus(Request $request)
    {
        $Admin = auth('user')->user();
        $IDTool = $request->IDTool;
        $ToolStatus = $request->ToolStatus;
        if (!$IDTool) {
            return RespondWithBadRequest(1);
        }
        if (!$ToolStatus) {
            return RespondWithBadRequest(1);
        }

        $Tool = Tool::find($IDTool);
        if ($ToolStatus == "DELETED") {
            $ClientTool = ClientTool::where("IDTool", $IDTool)->first();
            if ($ClientTool) {
                return RespondWithBadRequest(35);
            }
            $Desc = "Tool  " . $Tool->ToolTitleEn . " was Deleted";
            $Tool->ToolDeleted = 1;
        } else {
            $Desc = "Tool status changed from " . $Tool->ToolStatus . " to " . $ToolStatus;
            $Tool->ToolStatus = $ToolStatus;
        }
        $Tool->save();

        ActionBackLog($Admin->IDUser, $Tool->IDTool, "EDIT_TOOL", $Desc);
        return RespondWithSuccessRequest(8);
    }

    public function ToolAdd(Request $request)
    {
        $Admin = auth('user')->user();
        $ToolTitleEn = $request->ToolTitleEn;
        $ToolTitleAr = $request->ToolTitleAr;
        $ToolDescEn = $request->ToolDescEn;
        $ToolDescAr = $request->ToolDescAr;
        $ToolPrice = $request->ToolPrice;
        $ToolPoints = $request->ToolPoints;
        $ToolType = $request->ToolType;
        $ToolFiles = $request->ToolFiles;
        $ToolGallery = $request->ToolGallery;
        $ToolVideos = $request->ToolVideos;
        if (!$ToolTitleEn) {
            return RespondWithBadRequest(1);
        }
        if (!$ToolTitleAr) {
            return RespondWithBadRequest(1);
        }
        if (!$ToolPrice) {
            return RespondWithBadRequest(1);
        }
        if (!$ToolPoints) {
            return RespondWithBadRequest(1);
        }
        if (!$ToolType) {
            return RespondWithBadRequest(1);
        }
        if (!$ToolGallery) {
            return RespondWithBadRequest(1);
        }
        if (!$ToolFiles) {
            return RespondWithBadRequest(1);
        }
        if (!count($ToolGallery)) {
            return RespondWithBadRequest(1);
        }
        if (!count($ToolFiles)) {
            return RespondWithBadRequest(1);
        }

        $ImageExtArray = ["jpeg", "jpg", "png", "svg"];
        $FileExtArray = ["jpeg", "jpg", "png", "svg", "pdf", "mp3"];
        foreach ($ToolGallery as $Gallery) {
            if (!in_array($Gallery->extension(), $ImageExtArray)) {
                return RespondWithBadRequest(15);
            }
        }
        foreach ($ToolFiles as $File) {
            if (!in_array($File->extension(), $FileExtArray)) {
                return RespondWithBadRequest(15);
            }
        }

        $Tool = new Tool;
        $Tool->ToolTitleEn = $ToolTitleEn;
        $Tool->ToolTitleAr = $ToolTitleAr;
        $Tool->ToolDescEn = $ToolDescEn;
        $Tool->ToolDescAr = $ToolDescAr;
        $Tool->ToolPrice = $ToolPrice;
        $Tool->ToolPoints = $ToolPoints;
        $Tool->ToolType = $ToolType;
        $Tool->ToolStatus = "PENDING";
        $Tool->save();

        foreach ($ToolFiles as $Gallery) {
            $Image = SaveImage($Gallery, "tools", $Tool->IDTool);
            $ToolGalleryRow = new ToolGallery;
            $ToolGalleryRow->IDTool = $Tool->IDTool;
            $ToolGalleryRow->ToolGalleryPath = $Image;
            $ToolGalleryRow->ToolGalleryClass = "PRODUCT";
            if (!in_array($Gallery->extension(), $ImageExtArray)) {
                $ToolGalleryRow->ToolGalleryType = "FILE";
            }
            $ToolGalleryRow->save();
        }

        foreach ($ToolGallery as $Gallery) {
            $Image = SaveImage($Gallery, "tools", $Tool->IDTool);
            $ToolGalleryRow = new ToolGallery;
            $ToolGalleryRow->IDTool = $Tool->IDTool;
            $ToolGalleryRow->ToolGalleryPath = $Image;
            $ToolGalleryRow->ToolGalleryClass = "COVER";
            $ToolGalleryRow->ToolGalleryType = "IMAGE";
            $ToolGalleryRow->save();
        }

        if ($ToolVideos) {
            if (count($ToolVideos)) {
                foreach ($ToolVideos as $Video) {
                    $ToolVideo = YoutubeEmbedUrl($Video);
                    $ToolGalleryRow = new ToolGallery;
                    $ToolGalleryRow->IDTool = $Tool->IDTool;
                    $ToolGalleryRow->ToolGalleryPath = $ToolVideo;
                    $ToolGalleryRow->ToolGalleryClass = "COVER";
                    $ToolGalleryRow->ToolGalleryType = "VIDEO";
                    $ToolGalleryRow->save();
                }
            }
        }

        $Desc = "Tool " . $Tool->ToolTitleEn . " was added";
        ActionBackLog($Admin->IDUser, $Tool->IDTool, "ADD_TOOL", $Desc);
        return RespondWithSuccessRequest(8);
    }

    public function ToolDetails(Request $request)
    {
        $IDTool = $request->IDTool;
        $IDPage = $request->IDPage;
        $SearchKey = $request->SearchKey;
        if (!$IDTool) {
            return RespondWithBadRequest(1);
        }
        if (!$IDPage) {
            $IDPage = 0;
        } else {
            $IDPage = ($request->IDPage - 1) * 20;
        }

        $Tool = Tool::find($IDTool);
        if (!$Tool) {
            return RespondWithBadRequest(1);
        }

        $ToolGallery = ToolGallery::where("IDTool", $IDTool)->where("ToolGalleryDeleted", 0)->where("ToolGalleryClass", "COVER")->orderby("ToolGalleryType")->get();
        foreach ($ToolGallery as $Gallery) {
            if ($Gallery->ToolGalleryType == "IMAGE") {
                $Gallery->ToolGalleryPath = asset($Gallery->ToolGalleryPath);
            }
        }

        $ToolFiles = ToolGallery::where("IDTool", $IDTool)->where("ToolGalleryDeleted", 0)->where("ToolGalleryClass", "PRODUCT")->orderby("ToolGalleryType")->get();
        foreach ($ToolFiles as $Gallery) {
            $Gallery->ToolGalleryPath = asset($Gallery->ToolGalleryPath);
        }

        $ClientTools = ClientTool::leftjoin("clients", "clients.IDClient", "clienttools.IDClient")->where("clienttools.IDTool", $IDTool);
        if ($SearchKey) {
            $ClientTools = $ClientTools->where(function ($query) use ($SearchKey) {
                $query->where('clients.ClientName', 'like', '%' . $SearchKey . '%')
                    ->orwhere('clients.ClientPhone', 'like', '%' . $SearchKey . '%');
            });
        }

        $ClientTools = $ClientTools->select("clients.IDClient", "clients.ClientName", "clients.ClientPhone", "clienttools.created_at");
        $Pages = ceil($ClientTools->count() / 20);
        $ClientTools = $ClientTools->skip($IDPage)->take(20)->get();

        $Tool->ToolGallery = $ToolGallery;
        $Tool->ToolFiles = $ToolFiles;
        $Tool->ClientTools = $ClientTools;
        $Response = array("Tool" => $Tool, "Pages" => $Pages);

        $APICode = APICode::where('IDAPICode', 8)->first();
        $Response = array(
            'Success' => true,
            'ApiMsg' => __('apicodes.' . $APICode->IDApiCode),
            'ApiCode' => $APICode->IDApiCode,
            'Response' => $Response,
        );
        return $Response;
    }

    public function ToolEdit(Request $request)
    {
        $Admin = auth('user')->user();
        $IDTool = $request->IDTool;
        $ToolTitleEn = $request->ToolTitleEn;
        $ToolTitleAr = $request->ToolTitleAr;
        $ToolDescEn = $request->ToolDescEn;
        $ToolDescAr = $request->ToolDescAr;
        $ToolPrice = $request->ToolPrice;
        $ToolPoints = $request->ToolPoints;
        $ToolType = $request->ToolType;
        $ToolFiles = $request->ToolFiles;
        $ToolGallery = $request->ToolGallery;
        $ToolVideos = $request->ToolVideos;
        $Desc = "";

        if (!$IDTool) {
            return RespondWithBadRequest(1);
        }

        $Tool = Tool::find($IDTool);
        if ($ToolTitleEn) {
            $Desc = "Tool english title was changed from " . $Tool->ToolTitleEn . " to " . $ToolTitleEn;
            $Tool->ToolTitleEn = $ToolTitleEn;
        }
        if ($ToolTitleAr) {
            $Desc = $Desc . ", Tool arabic title was changed from " . $Tool->ToolTitleAr . " to " . $ToolTitleAr;
            $Tool->ToolTitleAr = $ToolTitleAr;
        }
        if ($ToolDescEn) {
            $Desc = $Desc . ", Tool english desc was changed from " . $Tool->ToolDescEn . " to " . $ToolDescEn;
            $Tool->ToolDescEn = $ToolDescEn;
        }
        if ($ToolDescAr) {
            $Desc = $Desc . ", Tool arabic desc was changed from " . $Tool->ToolDescAr . " to " . $ToolDescAr;
            $Tool->ToolDescAr = $ToolDescAr;
        }
        if ($ToolPrice) {
            $Desc = $Desc . ", Tool price  was changed from " . $Tool->ToolPrice . " to " . $ToolPrice;
            $Tool->ToolPrice = $ToolPrice;
        }
        if ($ToolPoints) {
            $Desc = $Desc . ", Tool points  was changed from " . $Tool->ToolPoints . " to " . $ToolPoints;
            $Tool->ToolPoints = $ToolPoints;
        }
        if ($ToolType) {
            $Desc = $Desc . ", Tool type  was changed from " . $Tool->ToolType . " to " . $ToolType;
            $Tool->ToolType = $ToolType;
        }
        $Tool->ToolStatus = "PENDING";
        $Tool->save();

        $ImageExtArray = ["jpeg", "jpg", "png", "svg"];
        if ($ToolGallery) {
            foreach ($ToolGallery as $Gallery) {
                if (!in_array($Gallery->extension(), $ImageExtArray)) {
                    return RespondWithBadRequest(15);
                }
            }
            foreach ($ToolGallery as $Gallery) {
                $Image = SaveImage($Gallery, "tools", $Tool->IDTool);
                $ToolGalleryRow = new ToolGallery;
                $ToolGalleryRow->IDTool = $Tool->IDTool;
                $ToolGalleryRow->ToolGalleryPath = $Image;
                $ToolGalleryRow->ToolGalleryClass = "COVER";
                $ToolGalleryRow->ToolGalleryType = "IMAGE";
                $ToolGalleryRow->save();
            }
            $Desc = $Desc . ", Tool Gallery was added";
        }

        if ($ToolFiles) {
            foreach ($ToolFiles as $Gallery) {
                if (!in_array($Gallery->extension(), $ImageExtArray)) {
                    return RespondWithBadRequest(15);
                }
            }
            foreach ($ToolFiles as $Gallery) {
                $Image = SaveImage($Gallery, "tools", $Tool->IDTool);
                $ToolGalleryRow = new ToolGallery;
                $ToolGalleryRow->IDTool = $Tool->IDTool;
                $ToolGalleryRow->ToolGalleryPath = $Image;
                $ToolGalleryRow->ToolGalleryClass = "PRODUCT";
                if (!in_array($Gallery->extension(), $ImageExtArray)) {
                    $ToolGalleryRow->ToolGalleryType = "FILE";
                }
                $ToolGalleryRow->save();
            }
            $Desc = $Desc . ", Tool Files were added";
        }

        if ($ToolVideos) {
            if (count($ToolVideos)) {
                foreach ($ToolVideos as $Video) {
                    $ToolVideo = YoutubeEmbedUrl($Video);
                    $ToolGalleryRow = new ToolGallery;
                    $ToolGalleryRow->IDTool = $Tool->IDTool;
                    $ToolGalleryRow->ToolGalleryPath = $ToolVideo;
                    $ToolGalleryRow->ToolGalleryClass = "COVER";
                    $ToolGalleryRow->ToolGalleryType = "VIDEO";
                    $ToolGalleryRow->save();
                }
            }
            $Desc = $Desc . ", Tool videos were added";
        }

        ActionBackLog($Admin->IDUser, $Tool->IDTool, "EDIT_TOOL", $Desc);
        return RespondWithSuccessRequest(8);
    }

    public function ToolGalleryRemove($IDToolGallery)
    {
        $Admin = auth('user')->user();
        $ToolGallery = ToolGallery::find($IDToolGallery);
        if (!$ToolGallery) {
            return RespondWithBadRequest(1);
        }

        if ($ToolGallery->ToolGalleryType != "VIDEO") {
            $OldDocument = substr($ToolGallery->ToolGalleryPath, 7);
            Storage::disk('uploads')->delete($OldDocument);
        }

        $ToolGallery->ToolGalleryDeleted = 1;
        $ToolGallery->save();

        $Desc = "Tool gallery removed";
        ActionBackLog($Admin->IDUser, $ToolGallery->IDTool, "EDIT_TOOL", $Desc);
        return RespondWithSuccessRequest(8);
    }

    public function NationalityList(Request $request)
    {
        $Nationalities = Nationality::all();

        $APICode = APICode::where('IDAPICode', 8)->first();
        $Response = array(
            'Success' => true,
            'ApiMsg' => __('apicodes.' . $APICode->IDApiCode),
            'ApiCode' => $APICode->IDApiCode,
            'Response' => $Nationalities,
        );
        return $Response;
    }

    public function NationalityAdd(Request $request)
    {
        $Admin = auth('user')->user();
        $NationalityNameEn = $request->NationalityNameEn;
        $NationalityNameAr = $request->NationalityNameAr;

        if (!$NationalityNameEn) {
            return RespondWithBadRequest(1);
        }
        if (!$NationalityNameAr) {
            return RespondWithBadRequest(1);
        }

        $Nationality = Nationality::where("NationalityNameEn", $NationalityNameEn)->orwhere("NationalityNameAr", $NationalityNameAr)->first();
        if ($Nationality) {
            return RespondWithBadRequest(18);
        }

        $Nationality = new Nationality;
        $Nationality->NationalityNameEn = $NationalityNameEn;
        $Nationality->NationalityNameAr = $NationalityNameAr;
        $Nationality->save();


        $Desc = "Nationality " . $NationalityNameEn . " was added";
        ActionBackLog($Admin->IDUser, $Nationality->IDNationality, "ADD_Nationality", $Desc);

        return RespondWithSuccessRequest(8);
    }

    public function NationalityEditPage($IDNationality)
    {
        $Nationality = Nationality::find($IDNationality);
        if (!$Nationality) {
            return RespondWithBadRequest(1);
        }

        $APICode = APICode::where('IDAPICode', 8)->first();
        $Response = array(
            'Success' => true,
            'ApiMsg' => __('apicodes.' . $APICode->IDApiCode),
            'ApiCode' => $APICode->IDApiCode,
            'Response' => $Nationality,
        );
        return $Response;
    }

    public function NationalityEdit(Request $request)
    {
        $Admin = auth('user')->user();
        $IDNationality = $request->IDNationality;
        $NationalityNameEn = $request->NationalityNameEn;
        $NationalityNameAr = $request->NationalityNameAr;
        $Desc = "";

        $Nationality = Nationality::find($IDNationality);
        if (!$Nationality) {
            return RespondWithBadRequest(1);
        }

        if ($NationalityNameEn) {
            $NationalityRow = Nationality::where("NationalityNameEn", $NationalityNameEn)->where("IDNationality", "<>", $IDNationality)->first();
            if ($NationalityRow) {
                return RespondWithBadRequest(18);
            }
            $Desc = "Nationality english name changed from " . $Nationality->NationalityNameEn . " to " . $NationalityNameEn;
            $Nationality->NationalityNameEn = $NationalityNameEn;
        }
        if ($NationalityNameAr) {
            $NationalityRow = Nationality::where("NationalityNameAr", $NationalityNameAr)->where("IDNationality", "<>", $IDNationality)->first();
            if ($NationalityRow) {
                return RespondWithBadRequest(18);
            }
            $Desc = $Desc . ", Nationality arabic name changed from " . $Nationality->NationalityNameEn . " to " . $NationalityNameEn;
            $Nationality->NationalityNameAr = $NationalityNameAr;
        }

        $Nationality->save();

        ActionBackLog($Admin->IDUser, $Nationality->IDNationality, "EDIT_Nationality", $Desc);
        return RespondWithSuccessRequest(8);
    }

    public function CompanyLedger(Request $request, CompanyLedger $CompanyLedger)
    {
        $User = auth('user')->user();
        $UserLanguage = AdminLanguage($User->UserLanguage);
        $IDPage = $request->IDPage;
        $IDCategory = $request->IDCategory;
        $IDSubCategory = $request->IDSubCategory;
        $CompanyLedgerProcess = $request->CompanyLedgerProcess;
        $CompanyLedgerType = $request->CompanyLedgerType;
        $StartDate = $request->StartDate;
        $EndDate = $request->EndDate;
        if (!$IDPage) {
            $IDPage = 0;
        } else {
            $IDPage = ($request->IDPage - 1) * 20;
        }

        $CompanyLedger = $CompanyLedger->leftjoin("subcategories", "subcategories.IDSubCategory", "companyledger.IDSubCategory")->leftjoin("categories", "categories.IDCategory", "subcategories.IDCategory");
        if ($IDCategory) {
            $CompanyLedger = $CompanyLedger->where("categories.IDCategory", $IDCategory);
        }
        if ($IDSubCategory) {
            $CompanyLedger = $CompanyLedger->where("companyledger.IDSubCategory", $IDSubCategory);
        }
        if ($CompanyLedgerProcess) {
            $CompanyLedger = $CompanyLedger->where("companyledger.CompanyLedgerProcess", $CompanyLedgerProcess);
        }
        if ($CompanyLedgerType) {
            $CompanyLedger = $CompanyLedger->where("companyledger.CompanyLedgerType", $CompanyLedgerType);
        }
        if ($StartDate) {
            $CompanyLedger = $CompanyLedger->where("companyledger.created_at", ">=", $StartDate);
        }
        if ($EndDate) {
            $CompanyLedger = $CompanyLedger->where("companyledger.created_at", "<=", $EndDate);
        }

        $Pages = ceil($CompanyLedger->count() / 20);
        $CompanyLedger = $CompanyLedger->select("companyledger.IDCompanyLedger", "companyledger.IDSubCategory", "companyledger.CompanyLedgerAmount", "companyledger.CompanyLedgerDesc", "companyledger.CompanyLedgerProcess", "companyledger.CompanyLedgerType", "companyledger.created_at", "categories.CategoryNameEn", "categories.CategoryNameAr", "subcategories.SubCategoryNameEn", "subcategories.SubCategoryNameAr")->orderby("companyledger.IDCompanyLedger", "DESC")->skip($IDPage)->take(20)->get();

        $CategoryName = "CategoryName" . $UserLanguage;
        $SubCategoryName = "SubCategoryName" . $UserLanguage;
        foreach ($CompanyLedger as $Ledger) {
            $Ledger->CategoryName = $Ledger->$CategoryName;
            $Ledger->SubCategoryName = $Ledger->$SubCategoryName;
            unset($Ledger["CategoryNameEn"]);
            unset($Ledger["CategoryNameAr"]);
            unset($Ledger["SubCategoryNameEn"]);
            unset($Ledger["SubCategoryNameAr"]);
        }

        $Response = array("CompanyLedger" => $CompanyLedger, "Pages" => $Pages);
        $APICode = APICode::where('IDAPICode', 8)->first();
        $Response = array(
            'Success' => true,
            'ApiMsg' => __('apicodes.' . $APICode->IDApiCode),
            'ApiCode' => $APICode->IDApiCode,
            'Response' => $Response,
        );
        return $Response;
    }

    public function CompanyLedgerAdd(Request $request)
    {
        $Admin = auth('user')->user();
        $IDSubCategory = $request->IDSubCategory;
        $CompanyLedgerType = $request->CompanyLedgerType;
        $CompanyLedgerAmount = $request->CompanyLedgerAmount;
        $CompanyLedgerDesc = $request->CompanyLedgerDesc;

        if (!$IDSubCategory) {
            return RespondWithBadRequest(1);
        }
        if (!$CompanyLedgerAmount) {
            return RespondWithBadRequest(1);
        }
        if (!$CompanyLedgerType) {
            return RespondWithBadRequest(1);
        }

        $CompanyLedger = new CompanyLedger;
        $CompanyLedger->IDSubCategory = $IDSubCategory;
        $CompanyLedger->CompanyLedgerAmount = $CompanyLedgerAmount;
        $CompanyLedger->CompanyLedgerDesc = $CompanyLedgerDesc;
        $CompanyLedger->CompanyLedgerType = $CompanyLedgerType;
        $CompanyLedger->CompanyLedgerProcess = "MANUAL";
        $CompanyLedger->save();

        $Desc = "Company Ledger Added";
        ActionBackLog($Admin->IDUser, $CompanyLedger->IDCompanyLedger, "ADD_COMPANYLEDGER", $Desc);

        return RespondWithSuccessRequest(8);
    }

    public function CompanyLedgerEditPage($IDCompanyLedger)
    {
        $User = auth('user')->user();
        $UserLanguage = AdminLanguage($User->UserLanguage);
        $CompanyLedger = CompanyLedger::leftjoin("subcategories", "subcategories.IDSubCategory", "companyledger.IDSubCategory")->leftjoin("categories", "categories.IDCategory", "subcategories.IDCategory")->where("companyledger.IDCompanyLedger", $IDCompanyLedger)->select("companyledger.IDCompanyLedger", "categories.IDCategory", "companyledger.IDSubCategory", "companyledger.CompanyLedgerAmount", "companyledger.CompanyLedgerDesc", "companyledger.CompanyLedgerProcess", "companyledger.CompanyLedgerType", "companyledger.created_at", "categories.CategoryNameEn", "categories.CategoryNameAr", "subcategories.SubCategoryNameEn", "subcategories.SubCategoryNameAr")->first();
        if (!$CompanyLedger) {
            return RespondWithBadRequest(1);
        }

        $CategoryName = "CategoryName" . $UserLanguage;
        $SubCategoryName = "SubCategoryName" . $UserLanguage;
        $CompanyLedger->CategoryName = $CompanyLedger->$CategoryName;
        $CompanyLedger->SubCategoryName = $CompanyLedger->$SubCategoryName;
        unset($CompanyLedger["CategoryNameEn"]);
        unset($CompanyLedger["CategoryNameAr"]);
        unset($CompanyLedger["SubCategoryNameEn"]);
        unset($CompanyLedger["SubCategoryNameAr"]);

        $APICode = APICode::where('IDAPICode', 8)->first();
        $Response = array(
            'Success' => true,
            'ApiMsg' => __('apicodes.' . $APICode->IDApiCode),
            'ApiCode' => $APICode->IDApiCode,
            'Response' => $CompanyLedger,
        );
        return $Response;
    }

    public function CompanyLedgerEdit(Request $request)
    {
        $Admin = auth('user')->user();
        $IDCompanyLedger = $request->IDCompanyLedger;
        $IDSubCategory = $request->IDSubCategory;
        $CompanyLedgerType = $request->CompanyLedgerType;
        $CompanyLedgerAmount = $request->CompanyLedgerAmount;
        $CompanyLedgerDesc = $request->CompanyLedgerDesc;
        $Desc = "";

        $CompanyLedger = CompanyLedger::find($IDCompanyLedger);
        if (!$CompanyLedger) {
            return RespondWithBadRequest(1);
        }
        if ($CompanyLedger->CompanyLedgerProcess == "AUTO") {
            return RespondWithBadRequest(1);
        }

        if ($IDSubCategory) {
            $Desc = "Company Ledger sub category changed";
            $CompanyLedger->IDSubCategory = $IDSubCategory;
        }
        if ($CompanyLedgerAmount) {
            $Desc = $Desc . ", Company Ledger amount changed from " . $CompanyLedger->CompanyLedgerAmount . " to " . $CompanyLedgerAmount;
            $CompanyLedger->CompanyLedgerAmount = $CompanyLedgerAmount;
        }
        if ($CompanyLedgerDesc) {
            $Desc = $Desc . ", Company Ledger desc changed from " . $CompanyLedger->CompanyLedgerDesc . " to " . $CompanyLedgerDesc;
            $CompanyLedger->CompanyLedgerDesc = $CompanyLedgerDesc;
        }
        if ($CompanyLedgerType) {
            $Desc = $Desc . ", Company Ledger type changed from " . $CompanyLedger->CompanyLedgerType . " to " . $CompanyLedgerType;
            $CompanyLedger->CompanyLedgerType = $CompanyLedgerType;
        }
        $CompanyLedger->save();

        ActionBackLog($Admin->IDUser, $CompanyLedger->IDCompanyLedger, "EDIT_COMPANYLEDGER", $Desc);
        return RespondWithSuccessRequest(8);
    }

    public function ActionBackLog(Request $request, ActionBackLog $ActionBackLog)
    {
        $IDPage = $request->IDPage;
        $IDLink = $request->IDLink;
        $BackLogType = $request->BackLogType;
        if (!$IDPage) {
            $IDPage = 0;
        } else {
            $IDPage = ($request->IDPage - 1) * 20;
        }

        if (!$IDLink) {
            return RespondWithBadRequest(1);
        }
        if (!$BackLogType) {
            return RespondWithBadRequest(1);
        }

        switch ($BackLogType) {
            case "USER":
                $ActionBackLogType = ["ADD_USER", "EDIT_USER"];
                break;
            case "COUNTRY":
                $ActionBackLogType = ["ADD_COUNTRY", "EDIT_COUNTRY"];
                break;
            case "CITY":
                $ActionBackLogType = ["ADD_CITY", "EDIT_CITY"];
                break;
            case "AREA":
                $ActionBackLogType = ["ADD_AREA", "EDIT_AREA"];
                break;
            case "BRAND":
                $ActionBackLogType = ["ADD_BRAND", "EDIT_BRAND", "ADD_BRAND_CONTRACT", "EDIT_BRAND_CONTRACT", "EDIT_BRAND_RATING", "ADD_BRAND_CONTACT", "EDIT_BRAND_CONTACT", "EDIT_BRAND_SOCIAL"];
                break;
            case "BRAND_PRODUCT":
                $ActionBackLogType = ["ADD_BRAND_PRODUCT", "EDIT_BRAND_PRODUCT"];
                break;
            case "CATEGORY":
                $ActionBackLogType = ["ADD_CATEGORY", "EDIT_CATEGORY"];
                break;
            case "SUBCATEGORY":
                $ActionBackLogType = ["ADD_SUBCATEGORY", "EDIT_SUBCATEGORY"];
                break;
            case "NATIONALITY":
                $ActionBackLogType = ["ADD_NATIONALITY", "EDIT_NATIONALITY"];
                break;
            case "ADS":
                $ActionBackLogType = ["ADD_ADS", "EDIT_ADS"];
                break;
            case "SOCIAL":
                $ActionBackLogType = ["ADD_SOCIAL", "EDIT_SOCIAL"];
                break;
            case "EVENT":
                $ActionBackLogType = ["ADD_EVENT", "EDIT_EVENT", "REMOVE_EVENT_ATTENDEE"];
                break;
            case "TOOL":
                $ActionBackLogType = ["ADD_TOOL", "EDIT_TOOL"];
                break;
            case "CLIENT":
                $ActionBackLogType = ["ADD_CLIENT", "EDIT_CLIENT"];
                break;
            case "PLAN":
                $ActionBackLogType = ["ADD_PLAN", "EDIT_PLAN"];
                break;
            case "PLAN_PRODUCT":
                $ActionBackLogType = ["ADD_PLAN_PRODUCT", "EDIT_PLAN_PRODUCT", "EDIT_PLAN_PRODUCT_LINK"];
                break;
            case "POSITION":
                $ActionBackLogType = ["ADD_POSITION", "EDIT_POSITION", "EDIT_POSITION_SOCIAL"];
                break;
            case "COMPANYLEDGER":
                $ActionBackLogType = ["ADD_COMPANYLEDGER", "EDIT_COMPANYLEDGER"];
                break;
            case "BRANCH":
                $ActionBackLogType = ["ADD_BRANCH", "EDIT_BRANCH"];
                break;
            case "BONANZA":
                $ActionBackLogType = ["ADD_BONANZA", "EDIT_BONANZA", "EDIT_BONANZA_SOCIAL"];
                break;
            case "UPGRADE":
                $ActionBackLogType = ["ADD_UPGRADE", "EDIT_UPGRADE"];
                break;
            case "ADS":
                $ActionBackLogType = ["ADD_ADS", "EDIT_ADS"];
                break;
        }

        $ActionBackLog = $ActionBackLog->leftjoin("users", "users.IDUser", "actionbacklog.IDUser")->leftjoin("roles", "roles.IDRole", "users.IDRole");
        $ActionBackLog = $ActionBackLog->where("actionbacklog.IDLink", $IDLink)->whereIn("actionbacklog.ActionBackLogType", $ActionBackLogType);

        $Pages = ceil($ActionBackLog->count() / 20);
        $ActionBackLog = $ActionBackLog->select("users.IDUser", "users.UserName", "users.UserPhone", "roles.RoleName", "actionbacklog.IDActionBackLog", "actionbacklog.ActionBackLogType", "actionbacklog.ActionBackLogDesc", "actionbacklog.created_at");
        $ActionBackLog = $ActionBackLog->orderby("actionbacklog.IDActionBackLog", "DESC")->skip($IDPage)->take(20)->get();

        $Response = array("ActionBackLog" => $ActionBackLog, "Pages" => $Pages);
        $APICode = APICode::where('IDAPICode', 8)->first();
        $Response = array(
            'Success' => true,
            'ApiMsg' => __('apicodes.' . $APICode->IDApiCode),
            'ApiCode' => $APICode->IDApiCode,
            'Response' => $Response,
        );
        return $Response;
    }
    public function backup()
    {
        // Define the backup file name
        $fileName = 'backup-' . date('Y-m-d_H-i-s') . '.sql';
        // Define the full path to the backup file
        $filePath = storage_path('app/' . $fileName);

        // Run the mysqldump command to create the backup
        $command = sprintf(
            'mysqldump --user=%s --password=%s --host=%s %s > %s',
            escapeshellarg(Config::get('database.connections.mysql.username')),
            escapeshellarg(Config::get('database.connections.mysql.password')),
            escapeshellarg(Config::get('database.connections.mysql.host')),
            escapeshellarg(Config::get('database.connections.mysql.database')),
            escapeshellarg($filePath)
        );

        $returnVar = NULL;
        $output = NULL;
        exec($command . ' 2>&1', $output, $returnVar);

        if ($returnVar !== 0) {
            // Log the error for debugging
            Log::error('Backup failed', [
                'command' => $command,
                'output' => $output,
                'returnVar' => $returnVar
            ]);
            return response()->json(['error' => 'Backup failed', 'details' => $output], 500);
        }

        // Check if the file was created
        if (!file_exists($filePath)) {
            return response()->json(['error' => 'File not found'], 404);
        }

        return response()->download($filePath)->deleteFileAfterSend(true);
    }

    public function UploadContract(Request $request)
    {
        $request->validate([
            'contract' => 'required|file|mimes:pdf,doc,docx|max:2048', // Adjust file types and size as needed
        ]);

        // Find the client by ID
        $client = Client::findOrFail($request->client_id);
        // Store the file in storage/app/contract
        if ($request->hasFile('contract')) {
            // Store the file in storage/app/contract
            $document = $request->file('contract');
            $id = $request->client_id;
            $filePath = SaveContract($document, $id);
            // Save the file path in the database
            $client->update(['contract_path' => $filePath]);

            // Construct the full URL path manually
            $url = asset($filePath);

            return response()->json(['message' => 'File uploaded successfully', 'file_url' => $url], 200);
        } else {
            return response()->json(['error' => 'No file attached in the request'], 400);
        }
    }
    public function sendNotifications(Request $request)
    {
        $ids = $request->input('ids');
        $users = Client::whereIn('IDClient', $ids)->get();
        $firebaseTokens = $users->pluck('ClientDeviceToken')->toArray();
        $SERVER_API_KEY = 'AAAAlWZnFGI:APA91bGNNe4QgAf7yuPAvKZnihGD2EvtJMb-fiDy1l4RNd3Wbz6Y2P6Zo6hptcsYxUs7BfyL2ZlcS2INzQLS-4xzHA2ndRXq_Z2W2OpxmKNGT0QTCjmSyNtxjCUlCLDNPeIJ6r1wvwmb';
        $body = $request->input('body');
        $title = $request->input('title');

        $data = [
            "registration_ids" => $firebaseTokens,
            "notification" => [
                "title" => $title,
                "body" => $body,
            ]
        ];
        $dataString = json_encode($data, JSON_UNESCAPED_UNICODE);

        $headers = [
            'Authorization: key=' . $SERVER_API_KEY,
            'Content-Type: application/json; charset=UTF-8',
        ];

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $dataString);
        $response = curl_exec($ch);

        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);
            return response()->json(['error' => $error], 500);
        }

        $responseData = json_decode($response, true);

        // Handle response from FCM and store only successful notifications
        if (isset($responseData['results'])) {
            foreach ($responseData['results'] as $key => $result) {
                if (isset($result['message_id'])) {
                    // Notification sent successfully
                    $user = $users[$key];
                    Notification::create([
                        'client_id' => $user->IDClient,
                        'title' => $title,
                        'body' => $body,
                    ]);
                } elseif (isset($result['error']) && $result['error'] === 'NotRegistered') {
                    // Handle "NotRegistered" error - remove token from your database or list
                    $invalidToken = $firebaseTokens[$key];
                    Client::where('ClientDeviceToken', $invalidToken)->update(['ClientDeviceToken' => null]);
                }
            }
        }

        curl_close($ch);

        return response()->json(['response' => $responseData], 200);
    }
}
