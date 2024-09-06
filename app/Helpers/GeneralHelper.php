<?php

use Illuminate\Support\Facades\Log;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Exception\RequestException;
use App\V1\General\APICode;
use App\V1\Location\Country;
use App\V1\Location\City;
use Illuminate\Http\Request;
use App\V1\General\GeneralSetting;
use App\V1\Brand\Branch;
use App\V1\Brand\BrandProductBranch;
use App\V1\Client\Client;
use App\V1\Client\ClientFriend;
use App\V1\Client\ClientLedger;
use App\V1\Client\ClientNotification;
use App\V1\Client\ClientNotificationDetail;
use App\V1\Client\ClientPointsLedger;
use App\V1\Client\Position;
use App\V1\Plan\Plan;
use App\V1\Plan\PlanNetwork;
use App\V1\Plan\PlanProduct;
use App\V1\User\User;
use App\V1\User\ActionBackLog;
use Illuminate\Support\Facades\Storage;
use LaravelFCM\Message\OptionsBuilder;
use LaravelFCM\Message\PayloadDataBuilder;
use LaravelFCM\Message\PayloadNotificationBuilder;
use Illuminate\Support\Facades\Response;
use LaravelFCM\Facades\FCM;
use App\Notification;
use App\Notifications\NotificationForClient;
use App\V1\Payment\CompanyLedger;
use Carbon\Carbon;
use Google\Client as GoogleClient;

function RespondWithBadRequest($Code, $Variable = Null)
{
    $ClientAppLanguage = LocalAppLanguage(app()->getLocale());
    $APICode = APICode::where('IDApiCode', $Code)->first();
    if ($ClientAppLanguage == "En") {
        $ApiMsg = __('apicodes.' . $APICode->IDApiCode) . $Variable;
    } else {
        $ApiMsg = $Variable . __('apicodes.' . $APICode->IDApiCode);
    }
    $response = new stdClass();
    $response_array = array(
        'Success' => false,
        'ApiMsg' => $ApiMsg,
        'ApiCode' => $APICode->IDApiCode,
        'Response' => $response,
    );
    $response_code = 200;
    $response = Response::json($response_array, $response_code);
    return $response;
}

function RespondWithSuccessRequest($Code)
{

    //bad or invalid request missing some params
    $response = new stdClass();
    $APICode = APICode::where('IDApiCode', $Code)->first();
    $response_array = array(
        'Success' => true,
        'ApiMsg' => __('apicodes.' . $APICode->IDApiCode),
        'ApiCode' => $APICode->IDApiCode,
        'Response' => $response,
    );
    $response_code = 200;
    $response = Response::json($response_array, $response_code);
    return $response;
}

function LocalAppLanguage($ClientAppLanguage)
{
    if ($ClientAppLanguage == "ar") {
        return    $ClientAppLanguage = "Ar";
    } else if ($ClientAppLanguage == "en") {
        return $ClientAppLanguage = "En";
    } else {
        return $ClientAppLanguage = "En";
    }
}

function YoutubeEmbedUrl($URL)
{
    return preg_replace(
        "/\s*[a-zA-Z\/\/:\.]*youtu(be.com\/watch\?v=|.be\/)([a-zA-Z0-9\-_]+)([a-zA-Z0-9\/\*\-\_\?\&\;\%\=\.]*)/i",
        "www.youtube.com/embed/$2\ ",
        $URL
    );
}

function AdminLanguage($AdminLanguage)
{
    if ($AdminLanguage == "ar") {
        return $AdminLanguage = "Ar";
    } else if ($AdminLanguage == "en") {
        return $AdminLanguage = "En";
    } else {
        return $AdminLanguage = "En";
    }
}

function TimeZoneAdjust($Date, $CountryZone)
{
    if (!$Date) {
        return Null;
    }
    if ($CountryZone == 0) {
        return $Date;
    }
    $Zone = $CountryZone[0];
    $Time = $CountryZone[1];
    $Time = $Time * 3600;
    $Date = new DateTime($Date);
    if ($Zone == "-") {
        $Date = $Date->sub(new DateInterval('PT' . $Time . 'S'));
    } else {
        $Date = $Date->add(new DateInterval('PT' . $Time . 'S'));
    }
    $Date = $Date->format('Y-m-d H:i:s');
    return $Date;
}

function AdjustDateTime($Date, $Minutes, $Operation)
{
    if (!$Date) {
        return Null;
    }
    $Time = $Minutes * 60;
    $Date = new DateTime($Date);
    if ($Operation == "SUB") {
        $Date = $Date->sub(new DateInterval('PT' . $Time . 'S'));
    } else {
        $Date = $Date->add(new DateInterval('PT' . $Time . 'S'));
    }
    $Date = $Date->format('Y-m-d H:i:s');
    return $Date;
}

function DaysList($Date)
{
    $CurrentDay = strtoupper(date('l', strtotime($Date)));
    $PreviousDate = AdjustDateTime($Date, 1440, "SUB");
    $PreviousDay = strtoupper(date('l', strtotime($PreviousDate)));
    $NextDate = AdjustDateTime($Date, 1440, "ADD");
    $NextDay = strtoupper(date('l', strtotime($NextDate)));
    $PreviousDate = substr($PreviousDate, 0, 10);
    $Date = substr($Date, 0, 10);
    $NextDate = substr($NextDate, 0, 10);
    $DaysList = [$PreviousDay, $CurrentDay, $NextDay];
    $DateList = array($PreviousDay => $PreviousDate, $CurrentDay => $Date, $NextDay => $NextDate);
    $Response = array("DaysList" => $DaysList, "DateList" => $DateList);
    return $Response;
}

function LedgerBatchNumber()
{
    $NextLedgerID = DB::select('SELECT AUTO_INCREMENT FROM information_schema.TABLES WHERE  TABLE_NAME = "ledger"')[0]->AUTO_INCREMENT;
    if (!$NextLedgerID) {
        $NextLedgerID = DB::select('SELECT AUTO_INCREMENT FROM information_schema.TABLES WHERE  TABLE_NAME = "ledger"')[1]->AUTO_INCREMENT;
    }
    $TimeFormat = new DateTime('now');
    $Time = $TimeFormat->format('H');
    $Time = $Time . $TimeFormat->format('i');
    $BatchNumber = $NextLedgerID . $Time;
    return $BatchNumber;
}
function GenerateBatch($slogan, $IDClient)
{
    $BatchNumber = "#$slogan" . $IDClient;
    $TimeFormat = new DateTime('now');
    $Time = $TimeFormat->format('H');
    $Time = $Time . $TimeFormat->format('i');
    $BatchNumber = $BatchNumber . $Time;
    return $BatchNumber;
}


function CreateToken($credentials, $guard)
{
    $token = auth()->guard($guard)->attempt($credentials);
    if ($token) {
        return array(
            'accessToken' => $token,
            'tokenType' => 'bearer',
            // 'expiresIn' => auth()->factory()->getTTL() * 60,
        );
    }
    return null;
}


function GeneralSettings($GeneralSettingName)
{
    $GeneralSettingValue = GeneralSetting::where('GeneralSettingName', $GeneralSettingName)->first()->GeneralSettingValue;
    return $GeneralSettingValue;
}


///// create verification Number
function CreateVerificationCode()
{
    $chars = '123456789';
    $count = strlen($chars);
    $result = "";
    for ($i = 0; $i < 4; $i++) {
        $index = rand(0, $count - 1);
        $result .= substr($chars, $index, 1);
    }
    return $result;
}

function GetCity($Client)
{
    if (!$Client) {
        $IP = \Request::ip();
        $Data = \Location::get($IP);
        $City = City::where("CityNameEn", $Data->cityName)->where("CityActive", 1)->first();
        if (!$City) {
            $Country = Country::where("CountryCode", $Data->countryCode)->where("CountryActive", 1)->first();
            if (!$Country) {
                $Country = Country::where("CountryCode", "SA")->first();
            }
            $City = City::where("IDCountry", $Country->IDCountry)->where("CityActive", 1)->first();
        }
        return $City;
    }

    $City = City::find($Client->IDCity);
    return $City;
}

function GetCoForClient($Client)
{
    $ClientPlanNetwork = PlanNetwork::where("IDClient", $Client->IDClient)->first();
    if ($ClientPlanNetwork) {
        $IDsInPath = explode('-', $ClientPlanNetwork->PlanNetworkPath);
        $CoForClient = null;
        foreach ($IDsInPath as $id) {
            $getClient = Client::where("IDClient", $id)->first();
            if ($getClient) {
                if ($getClient->IDPosition) {
                    $getPosition = Position::where("IDPosition", $getClient->IDPosition)->first();
                    $clientFriend = ClientFriend::where("IDClient", $getClient->IDClient)->whereNotIn("ClientFriendStatus", ["REMOVED", "REJECTED"])->first();
                    if (strcasecmp($getPosition->PositionTitleEn, "CO") === 0) {
                        $CoForClient = $getClient;
                        $CoForClient["Position"] = $getPosition;
                        $CoForClient["IDClientFriend"] = $clientFriend ? $clientFriend->IDClientFriend : null;
                        break;
                    }
                }
            }
        }
        return $CoForClient;
    } else {
        return null;
    }
}

function AdjustLedger($Client, $Amount, $RewardPoints, $ReferralPoints, $UplinePoints, $PlanNetwork, $Source, $Destination, $Type, $BatchNumber)
{
    $PlanProductPoints = 0;

    if ($Destination == "PLAN_PRODUCT" && $Type != "UPGRADE") {
        $PlanProduct = PlanProduct::find($PlanNetwork->IDPlanProduct);
        $PlanProductPoints = $PlanProduct->PlanProductPoints;
        $ChildPosition = $PlanNetwork->PlanNetworkPosition;
        $ChildIDClient = $PlanNetwork->IDClient;
    }

    if ($Amount) {
        $ClientLedger = new ClientLedger;
        $ClientLedger->IDClient = $Client->IDClient;
        $ClientLedger->ClientLedgerAmount = abs($Amount);
        $ClientLedger->ClientLedgerPoints = 0;
        $ClientLedger->ClientLedgerSource = $Source;
        $ClientLedger->ClientLedgerDestination = $Destination;
        $ClientLedger->ClientLedgerInitialeBalance = $Client->ClientBalance;
        $ClientLedger->ClientLedgerFinalBalance = $Client->ClientBalance + $Amount;
        $ClientLedger->ClientLedgerInitialePoints = 0;
        $ClientLedger->ClientLedgerFinalPoints = 0;
        $ClientLedger->ClientLedgerType = $Type;
        $ClientLedger->ClientLedgerBatchNumber = $BatchNumber;
        $ClientLedger->save();

        $Client->ClientBalance = $Client->ClientBalance + $Amount;
    }
    if ($RewardPoints) {
        $ClientLedger = new ClientLedger;
        $ClientLedger->IDClient = $Client->IDClient;
        $ClientLedger->ClientLedgerAmount = 0;
        $ClientLedger->ClientLedgerPoints = abs($RewardPoints);
        $ClientLedger->ClientLedgerSource = $Source;
        $ClientLedger->ClientLedgerDestination = $Destination;
        $ClientLedger->ClientLedgerInitialeBalance = 0;
        $ClientLedger->ClientLedgerFinalBalance = 0;
        $ClientLedger->ClientLedgerInitialePoints = $Client->ClientRewardPoints;
        $ClientLedger->ClientLedgerFinalPoints = $Client->ClientRewardPoints + $RewardPoints;
        $ClientLedger->ClientLedgerType = $Type;
        $ClientLedger->ClientLedgerBatchNumber = $BatchNumber;
        $ClientLedger->save();

        $Client->ClientRewardPoints = $Client->ClientRewardPoints + $RewardPoints;
    }

    $Client->save();

    if ($PlanNetwork) {
        if ($PlanNetwork->IDReferralClient) {
            $P_Client = Client::find($PlanNetwork->IDReferralClient);

            if ($RewardPoints) {
                $ClientLedger = new ClientLedger;
                $ClientLedger->IDClient = $P_Client->IDClient;
                $ClientLedger->ClientLedgerPoints = $ReferralPoints;
                $ClientLedger->ClientLedgerSource = $Source;
                $ClientLedger->ClientLedgerDestination = $Destination;
                $ClientLedger->ClientLedgerInitialePoints = $P_Client->ClientRewardPoints;
                $ClientLedger->ClientLedgerFinalPoints = $P_Client->ClientRewardPoints + $ReferralPoints;
                $ClientLedger->ClientLedgerType = "REFERRAL";
                $ClientLedger->ClientLedgerBatchNumber = $BatchNumber;
                $ClientLedger->save();

                $P_Client->ClientRewardPoints = $P_Client->ClientRewardPoints + $ReferralPoints;
            }

            $P_Client->save();
        }

        if ($PlanNetwork->PlanNetworkPath) {
            $IDParentClients = explode("-", $PlanNetwork->PlanNetworkPath);
            $IDParentClients = array_reverse($IDParentClients);
            foreach ($IDParentClients as $IDParentClient) {
                $Parent_Client = Client::find($IDParentClient);

                if ($RewardPoints) {
                    $ClientLedger = new ClientLedger;
                    $ClientLedger->IDClient = $Parent_Client->IDClient;
                    $ClientLedger->ClientLedgerPoints = $UplinePoints;
                    $ClientLedger->ClientLedgerSource = $Source;
                    $ClientLedger->ClientLedgerDestination = $Destination;
                    $ClientLedger->ClientLedgerInitialePoints = $Parent_Client->ClientRewardPoints;
                    $ClientLedger->ClientLedgerFinalPoints = $Parent_Client->ClientRewardPoints + $UplinePoints;
                    $ClientLedger->ClientLedgerType = "UPLINE";
                    $ClientLedger->ClientLedgerBatchNumber = $BatchNumber;
                    $ClientLedger->save();

                    $Parent_Client->ClientRewardPoints = $Parent_Client->ClientRewardPoints + $UplinePoints;
                }

                if ($PlanProductPoints) {
                    if ($ChildPosition == "LEFT") {
                        $Parent_Client->ClientLeftNumber++;
                        $Parent_Client->ClientTotalNumber++;
                        $Parent_Client->ClientLeftPoints = $Parent_Client->ClientLeftPoints + $PlanProductPoints;
                        $Parent_Client->ClientTotalPoints = $Parent_Client->ClientTotalPoints + $PlanProductPoints;
                    }
                    if ($ChildPosition == "RIGHT") {
                        $Parent_Client->ClientRightNumber++;
                        $Parent_Client->ClientTotalNumber++;
                        $Parent_Client->ClientRightPoints = $Parent_Client->ClientRightPoints + $PlanProductPoints;
                        $Parent_Client->ClientTotalPoints = $Parent_Client->ClientTotalPoints + $PlanProductPoints;
                    }
                    PointsLedger($PlanProductPoints, $Parent_Client, $ChildIDClient, $ChildPosition, $BatchNumber);
                }

                $Parent_Client->save();
                $ParentPlanNetwork = PlanNetwork::where("IDClient", $IDParentClient)->first();

                if (!$ParentPlanNetwork) {
                    return RespondWithBadRequest(35);
                }

                $ChildPosition = $ParentPlanNetwork->PlanNetworkPosition;
            }
        }
    }
}
function ChequesLedger($Client, $Amount, $Source, $Destination, $Type, $BatchNumber)
{

    $ClientLedger = new ClientLedger;
    $ClientLedger->IDClient = $Client->IDClient;
    $ClientLedger->ClientLedgerAmount = abs($Amount);
    $ClientLedger->ClientLedgerPoints = 0;
    $ClientLedger->ClientLedgerSource = $Source;
    $ClientLedger->ClientLedgerDestination = $Destination;
    $ClientLedger->ClientLedgerInitialeBalance = $Client->ClientBalance;
    $ClientLedger->ClientLedgerFinalBalance = $Client->ClientBalance + $Amount;
    $ClientLedger->ClientLedgerInitialePoints = 0;
    $ClientLedger->ClientLedgerFinalPoints = 0;
    $ClientLedger->ClientLedgerType = $Type;
    $ClientLedger->ClientLedgerBatchNumber = $BatchNumber;
    $ClientLedger->save();

    $Client->ClientBalance = $Client->ClientBalance + $Amount;
    $Client->save();
}
function PointsLedger($PlanProductPoints, $Client, $ChildIDClient, $ChildPosition, $BatchNumber)
{
    $logMessage = "Points Ledger: PlanProductPoints=$PlanProductPoints, Client=$Client, ChildIDClient=$ChildIDClient, ChildPosition=$ChildPosition, BatchNumber=$BatchNumber";
    // Log::info($logMessage);

    $ClientPointsLedger = new ClientPointsLedger;
    $ClientPointsLedger->IDClient = $Client->IDClient;
    $ClientPointsLedger->ClientLedgerPoints = $PlanProductPoints;
    $ClientPointsLedger->ClientLedgerSource = $ChildIDClient;
    $ClientPointsLedger->ClientLedgerPosition = $ChildPosition;
    $ClientPointsLedger->ClientLedgerBatchNumber = $BatchNumber;
    $ClientPointsLedger->save();
}

function ProductBranches($IDLink, $Client, $Type)
{
    if ($Client) {
        $ClientLanguage = LocalAppLanguage($Client->ClientAppLanguage);
        $BranchAddress = "BranchAddress" . $ClientLanguage;
        $AreaName = "AreaName" . $ClientLanguage;
        $CityName = "CityName" . $ClientLanguage;
    } else {
        $BranchAddress = "BranchAddressEn";
        $AreaName = "AreaNameEn";
        $CityName = "CityNameEn";
    }

    $AllBranches = [];
    $TempList = [];
    $IDCity = 0;
    $CityNameTemp = "";
    if ($Type == "BRAND") {
        $Branches = Branch::leftjoin("areas", "areas.IDArea", "branches.IDArea")->leftjoin("cities", "cities.IDCity", "areas.IDCity")->where("branches.IDBrand", $IDLink)->where("branches.BranchStatus", "ACTIVE")->orderby("areas.IDCity")->get();
    }
    if ($Type == "PRODUCT") {
        $Branches = BrandProductBranch::leftjoin("branches", "branches.IDBranch", "brandproductbranches.IDBranch")->leftjoin("areas", "areas.IDArea", "branches.IDArea")->leftjoin("cities", "cities.IDCity", "areas.IDCity")->where("brandproductbranches.IDBrandProduct", $IDLink)->where("brandproductbranches.ProductBranchLinked", 1)->where("branches.BranchStatus", "ACTIVE")->orderby("areas.IDCity")->get();
    }
    foreach ($Branches as $Branch) {
        if ($IDCity && $IDCity != $Branch->IDCity) {
            $Temp = ["CityName" => $CityNameTemp, "Branches" => $TempList];
            array_push($AllBranches, $Temp);
        }
        $Temp = ["AreaName" => $Branch->$AreaName, "BranchAddress" => $Branch->$BranchAddress, "BranchLatitude" => $Branch->BranchLatitude, "BranchLongitude" => $Branch->BranchLongitude, "BranchPhone" => $Branch->BranchPhone];
        array_push($TempList, $Temp);
        $CityNameTemp = $Branch->$CityName;
        $IDCity = $Branch->IDCity;
    }
    if (count($TempList)) {
        $Temp = ["CityName" => $CityNameTemp, "Branches" => $TempList];
        array_push($AllBranches, $Temp);
    }
    return $AllBranches;
}

function RandomPassword()
{
    $min = 1;
    $max = 9;
    $random_number1 = rand($min, $max);
    //first capital
    $length = 1;
    $chars = 'ABCDEFGHJKLMNOPQRSTUVWXYZ';
    $count = strlen($chars);
    for ($i = 0, $result = ''; $i < $length; $i++) {
        $index = rand(0, $count - 1);
        $result .= substr($chars, $index, 1);
    }
    //second  capital
    $chars1 = 'ABCDEFGHJKLMNOPQRSTUVWXYZ';
    $count = strlen($chars1);
    for ($i = 0, $result1 = ''; $i < $length; $i++) {
        $index = rand(0, $count - 1);
        $result1 .= substr($chars1, $index, 1);
    }
    //first small
    $smallch = 'abcdefghijkmnopqrstuvwxyz';
    $counts = strlen($smallch);
    for ($i = 0, $smallchar = ''; $i < $length; $i++) {
        $index = rand(0, $counts - 1);
        $smallchar .= substr($smallch, $index, 1);
    }
    //second small
    $smallch2 = 'abcdefghijkmnopqrstuvwxyz';
    $counts2 = strlen($smallch2);
    for ($i = 0, $smallchar2 = ''; $i < $length; $i++) {
        $index = rand(0, $counts - 1);
        $smallchar2 .= substr($smallch2, $index, 1);
    }
    $special = array("0", "7");
    $spe_random = rand(0, 1);
    $spe = $special[$spe_random];
    $rnd = $random_number1;
    $main_no = "";
    if ($random_number1 % 2 == 0) {
        if ($random_number1 == 2) {

            $main_no = $result . $smallchar . $rnd . $smallchar2 . $spe . $result1;
        }
        if ($random_number1 == 4) {
            $main_no = $smallchar . $rnd . $smallchar2 . $spe . $result1 . $result;
        }

        if ($random_number1 == 6) {
            $main_no = $rnd . $smallchar2 . $spe . $result1 . $result . $smallchar;
        }
        if ($random_number1 == 8) {
            $main_no = $smallchar2 . $spe . $result1 . $result . $smallchar . $rnd;
        }
    }
    if ($random_number1 % 2 != 0) {
        if ($random_number1 == 1) {
            $main_no = $spe . $result1 . $result . $smallchar . $rnd . $smallchar2;
        }
        if ($random_number1 == 3) {
            $main_no = $result1 . $result . $smallchar . $rnd . $smallchar2 . $spe;
        }
        if ($random_number1 == 5) {
            $main_no = $result . $smallchar . $rnd . $smallchar2 . $spe . $result1;
        }
        if ($random_number1 == 7) {
            $main_no = $smallchar . $rnd . $smallchar2 . $spe . $result1 . $result;
        }
        if ($random_number1 == 9) {
            $main_no = $rnd . $smallchar2 . $spe . $result1 . $result . $smallchar;
        }
    }
    return $main_no;
}

function BaseUrl()
{
    $myUrl = "";
    if (isset($_SERVER['HTTPS'])) $myUrl .= "https://";
    else $myUrl .= "http://";
    if ($_SERVER['SERVER_NAME'] == "127.0.0.1") return "http://127.0.0.1:8000";
    return $myUrl . $_SERVER['SERVER_NAME'];
}

function SplitForwardList($entities)
{
    //split into arabic and english lists
    //only for passengers but drivers can used it to fake format thier tokens (they are by default arabic anyways)
    $forward = [];
    $forwardTokenEn = [];
    $forwardTokenAr = [];
    $forwardTokenHMSEn = [];
    $forwardTokenHMSAr = [];
    $Clients = $entities;
    foreach ($Clients as $Client) {
        if ($Client) {
            $forward[$Client->IDClient] = $Client->ClientDeviceToken;
            if ($Client->ClientMobileService == 'HMS') {
                if ($Client->ClientAppLanguage == 'EN') {
                    array_push($forwardTokenHMSEn, $Client->ClientDeviceToken);
                } else {
                    array_push($forwardTokenHMSAr, $Client->ClientDeviceToken);
                }
            } else {
                if ($Client->ClientAppLanguage == 'EN') {
                    array_push($forwardTokenEn, $Client->ClientDeviceToken);
                } else {
                    array_push($forwardTokenAr, $Client->ClientDeviceToken);
                }
            }
        }
    }
    $forwardList = [
        'forwardList' => $forward,
        'forwardTokenEn' => $forwardTokenEn,
        'forwardTokenAr' => $forwardTokenAr,
        'forwardTokenHMSEn' => $forwardTokenHMSEn,
        'forwardTokenHMSAr' => $forwardTokenHMSAr
    ];
    return $forwardList;
}

function SaveImage($File, $FolderName, $ID)
{
    return "uploads/" . Storage::disk('uploads')->put($FolderName . "/" . $ID, $File);
}
function SaveContract($document, $id)
{
    return Storage::disk('uploads')->put('contract' . "/" . $id, $document);
}
function DeleteContract($filePath)
{
    Storage::disk('uploads')->delete($filePath);
}

function ActionBackLog($IDUser, $IDLink, $ActionBackLogType, $ActionBackLogDesc)
{
    $ActionBackLog = new ActionBackLog();
    $ActionBackLog->IDUser                  = $IDUser;
    $ActionBackLog->IDLink                  = $IDLink;
    $ActionBackLog->ActionBackLogType       = $ActionBackLogType;
    $ActionBackLog->ActionBackLogDesc       = $ActionBackLogDesc;
    $ActionBackLog->save();
}


function my_random6_number()
{
    $min = 1;
    $max = 9;
    $random_number1 = rand($min, $max);
    //first capital
    $length = 1;
    $chars = 'ABCDEFGHJKLMNOPQRSTUVWXYZ';
    $count = strlen($chars);
    for ($i = 0, $result = ''; $i < $length; $i++) {
        $index = rand(0, $count - 1);
        $result .= substr($chars, $index, 1);
    }
    //second  capital
    $chars1 = 'ABCDEFGHJKLMNOPQRSTUVWXYZ';
    $count = strlen($chars1);
    for ($i = 0, $result1 = ''; $i < $length; $i++) {
        $index = rand(0, $count - 1);
        $result1 .= substr($chars1, $index, 1);
    }
    //first small
    $smallch = 'abcdefghijkmnopqrstuvwxyz';
    $counts = strlen($smallch);
    for ($i = 0, $smallchar = ''; $i < $length; $i++) {
        $index = rand(0, $counts - 1);
        $smallchar .= substr($smallch, $index, 1);
    }
    //second small
    $smallch2 = 'abcdefghijkmnopqrstuvwxyz';
    $counts2 = strlen($smallch2);
    for ($i = 0, $smallchar2 = ''; $i < $length; $i++) {
        $index = rand(0, $counts - 1);
        $smallchar2 .= substr($smallch2, $index, 1);
    }
    $special = array("0", "7");
    $spe_random = rand(0, 1);
    $spe = $special[$spe_random];
    $rnd = $random_number1;
    $main_no = "";
    if ($random_number1 % 2 == 0) {
        if ($random_number1 == 2) {

            $main_no = $result . $smallchar . $rnd . $smallchar2 . $spe . $result1;
        }
        if ($random_number1 == 4) {
            $main_no = $smallchar . $rnd . $smallchar2 . $spe . $result1 . $result;
        }

        if ($random_number1 == 6) {
            $main_no = $rnd . $smallchar2 . $spe . $result1 . $result . $smallchar;
        }
        if ($random_number1 == 8) {
            $main_no = $smallchar2 . $spe . $result1 . $result . $smallchar . $rnd;
        }
    }
    if ($random_number1 % 2 != 0) {
        if ($random_number1 == 1) {
            $main_no = $spe . $result1 . $result . $smallchar . $rnd . $smallchar2;
        }
        if ($random_number1 == 3) {
            $main_no = $result1 . $result . $smallchar . $rnd . $smallchar2 . $spe;
        }
        if ($random_number1 == 5) {
            $main_no = $result . $smallchar . $rnd . $smallchar2 . $spe . $result1;
        }
        if ($random_number1 == 7) {
            $main_no = $smallchar . $rnd . $smallchar2 . $spe . $result1 . $result;
        }
        if ($random_number1 == 9) {
            $main_no = $rnd . $smallchar2 . $spe . $result1 . $result . $smallchar;
        }
    }
    return $main_no;
}

function EnumValues($Table, $Column)
{
    $Type = DB::select(DB::raw("SHOW COLUMNS FROM " . $Table . " WHERE Field = '" . $Column . "'"))[0]->Type;
    preg_match('/^enum\((.*)\)$/', $Type, $Matches);
    $Enum = array();
    foreach (explode(',', $Matches[1]) as $Value) {
        $V = trim($Value, "'");
        array_push($Enum, $V);
    }
    return $Enum;
}


function SMSMsegat($ClientPhone, $Message)
{

    $SMSMsegatUserName = GeneralSettings("SMSMsegatUserName");
    $SMSMsegatAPIKey = GeneralSettings("SMSMsegatAPIKey");
    $SMSMsegatSender = GeneralSettings("SMSMsegatSender");

    $Fields = '{"userName": "' . $SMSMsegatUserName . '", "apiKey": "' . $SMSMsegatAPIKey . '", "userSender": "' . $SMSMsegatSender . '", "numbers": "' . $ClientPhone . '", "msg": "' . $Message . '"}';

    $Headers = array();
    $Headers[] = 'Cache-control: no-cache';
    $Headers[] = 'content-type: application/json';

    $curl = curl_init();

    curl_setopt($curl, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13");
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($curl, CURLOPT_POSTFIELDS, $Fields);
    curl_setopt($curl, CURLOPT_TIMEOUT, 80);
    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 30);
    curl_setopt($curl, CURLOPT_URL, "https://www.msegat.com/gw/sendsms.php");
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $Headers);
    $response = curl_exec($curl);

    curl_close($curl);

    // log::info($response);
}
function extractIDClientsFromJson($jsonString)
{
    $data = json_decode($jsonString, true);

    $filteredArray = array_map(function ($item) {
        $mainIDClient = $item['IDClient'];

        $referralIDClients = array_map(function ($referral) {
            return $referral['IDClient'];
        }, $item['referrals']);

        $visitIDClients = array_map(function ($visit) {
            return $visit['IDClient'];
        }, $item['visits']);

        return [
            'IDClient' => $mainIDClient,
            'referrals' => $referralIDClients,
            'visits' => $visitIDClients,
        ];
    }, $data);

    return json_encode($filteredArray);
}
function sendFirebaseNotification($Client, $dataPayload, $title, $body)
{
    Log::info('HERE');
    $data = [
        "title" => $title,
        "body" => $body,
        "data" => $dataPayload,
    ];
    $Client->notify(new NotificationForClient($title, $data));

    // try {
    //     $fcm = $Client->ClientDeviceToken;

    //     if (!$fcm) {
    //         Log::info('No FCM token found for the client.');
    //         return;
    //     }

    //     $projectId = config('services.fcm.project_id');
    //     $credentialsFilePath = Storage::path(env("FIREBASE_CREDENTIALS"));
    //     Log::info('Credentials file path: ' . $credentialsFilePath);

    //     $GoogleClient = new GoogleClient();
    //     $GoogleClient->setAuthConfig($credentialsFilePath);
    //     $GoogleClient->addScope('https://www.googleapis.com/auth/firebase.messaging');
    //     $GoogleClient->fetchAccessTokenWithAssertion();
    //     $token = $GoogleClient->getAccessToken();

    //     $access_token = $token['access_token'];

    //     $headers = [
    //         "Authorization: Bearer $access_token",
    //         'Content-Type: application/json'
    //     ];

    //     $data = [
    //         "message" => [
    //             "token" => $fcm,
    //             "notification" => [
    //                 "title" => $title,
    //                 "body" => $body,
    //             ],
    //         ]
    //     ];
    //     $payload = json_encode($data);

    //     Log::debug('FCM payload:', ['payload' => $payload]);

    //     $ch = curl_init();
    //     curl_setopt($ch, CURLOPT_URL, "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send");
    //     curl_setopt($ch, CURLOPT_POST, true);
    //     curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    //     curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    //     curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    //     curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    //     curl_setopt($ch, CURLOPT_VERBOSE, true);

    //     $response = curl_exec($ch);
    //     $err = curl_error($ch);

    //     Log::debug('FCM response:', ['response' => $response]);
    //     if ($err) {
    //         Log::error('cURL error:', ['error' => $err]);
    //     }

    //     curl_close($ch);

    //     Log::info('-------FCM notification sent successfully---------');
    // } catch (Exception $e) {
    //     Log::error('Exception occurred:', ['exception' => $e->getMessage()]);
    // }

}
function CompanyLedger($IDSubCategory, $Amount, $Description, $Process, $Type)
{
    $CompanyLedger = new CompanyLedger;
    $CompanyLedger->IDSubCategory = $IDSubCategory;
    $CompanyLedger->CompanyLedgerAmount = $Amount;
    $CompanyLedger->CompanyLedgerDesc = $Description;
    $CompanyLedger->CompanyLedgerProcess = $Process;
    $CompanyLedger->CompanyLedgerType = $Type;
    $CompanyLedger->save();

    return $CompanyLedger;
}
