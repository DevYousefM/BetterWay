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
use App\V1\Client\ClientLedger;
use App\V1\Client\ClientNotification;
use App\V1\Client\ClientNotificationDetail;
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
// use Location;
// use DB;
// use DateTime;
// use DateInterval;

function RespondWithBadRequest($Code, $Variable = Null)
{
    $ClientAppLanguage = LocalAppLanguage();
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

function LocalAppLanguage()
{
    $ClientAppLanguage = app()->getLocale();
    if ($ClientAppLanguage == "ar") {
        $ClientAppLanguage = "Ar";
    }
    if ($ClientAppLanguage == "en") {
        $ClientAppLanguage = "En";
    }
    return $ClientAppLanguage;
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
        $AdminLanguage = "Ar";
    }
    if ($AdminLanguage == "en") {
        $AdminLanguage = "En";
    }
    return $AdminLanguage;
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
    $IDsInPath = explode('-', $ClientPlanNetwork->PlanNetworkPath);
    $CoForClient = null;
    foreach ($IDsInPath as $id) {
        $getClient = Client::where("IDClient", $id)->first();
        if ($getClient->IDPosition) {
            $getPosition = Position::where("IDPosition", $getClient->IDPosition)->first();
            if (strcasecmp($getPosition->PositionTitleEn, "CO") === 0) {
                $CoForClient = $getClient;
                break;
            }
        }
    }
    return $CoForClient;
}

function AdjustLedger($Client, $Amount, $RewardPoints, $ReferralPoints, $UplinePoints, $PlanNetwork, $Source, $Destination, $Type, $BatchNumber)
{
    $PlanProductPoints = 0;
    if ($Destination == "PLAN_PRODUCT" && $Type != "UPGRADE") {
        $PlanProduct = PlanProduct::find($PlanNetwork->IDPlanProduct);
        $PlanProductPoints = $PlanProduct->PlanProductPoints;
        $ChildPosition = $PlanNetwork->PlanNetworkPosition;
    }

    if ($Amount || $RewardPoints) {
        $ClientLedger = new ClientLedger;
        $ClientLedger->IDClient = $Client->IDClient;
        $ClientLedger->ClientLedgerAmount = abs($Amount);
        $ClientLedger->ClientLedgerPoints = abs($RewardPoints);
        $ClientLedger->ClientLedgerSource = $Source;
        $ClientLedger->ClientLedgerDestination = $Destination;
        $ClientLedger->ClientLedgerInitialeBalance = $Client->ClientBalance;
        $ClientLedger->ClientLedgerFinalBalance = $Client->ClientBalance + $Amount;
        $ClientLedger->ClientLedgerInitialePoints = $Client->ClientRewardPoints;
        $ClientLedger->ClientLedgerFinalPoints = $Client->ClientRewardPoints + $RewardPoints;
        $ClientLedger->ClientLedgerType = $Type;
        $ClientLedger->ClientLedgerBatchNumber = $BatchNumber;
        $ClientLedger->save();

        $Client->ClientBalance = $Client->ClientBalance + $Amount;
        $Client->ClientRewardPoints = $Client->ClientRewardPoints + $RewardPoints;
    }

    $Client->save();

    if ($PlanNetwork) {
        if ($PlanNetwork->IDReferralClient) {
            $Client = Client::find($PlanNetwork->IDReferralClient);

            if ($Amount && $RewardPoints) {
                $ClientLedger = new ClientLedger;
                $ClientLedger->IDClient = $Client->IDClient;
                $ClientLedger->ClientLedgerPoints = $ReferralPoints;
                $ClientLedger->ClientLedgerSource = $Source;
                $ClientLedger->ClientLedgerDestination = $Destination;
                $ClientLedger->ClientLedgerInitialePoints = $Client->ClientRewardPoints;
                $ClientLedger->ClientLedgerFinalPoints = $Client->ClientRewardPoints + $ReferralPoints;
                $ClientLedger->ClientLedgerType = "REFERRAL";
                $ClientLedger->ClientLedgerBatchNumber = $BatchNumber;
                $ClientLedger->save();

                $Client->ClientRewardPoints = $Client->ClientRewardPoints + $ReferralPoints;
            }

            $Client->save();
        }

        if ($PlanNetwork->PlanNetworkPath) {
            $IDParentClients = explode("-", $PlanNetwork->PlanNetworkPath);
            $IDParentClients = array_reverse($IDParentClients);
            foreach ($IDParentClients as $IDParentClient) {
                $Client = Client::find($IDParentClient);

                if ($Amount && $RewardPoints) {
                    $ClientLedger = new ClientLedger;
                    $ClientLedger->IDClient = $Client->IDClient;
                    $ClientLedger->ClientLedgerPoints = $UplinePoints;
                    $ClientLedger->ClientLedgerSource = $Source;
                    $ClientLedger->ClientLedgerDestination = $Destination;
                    $ClientLedger->ClientLedgerInitialePoints = $Client->ClientRewardPoints;
                    $ClientLedger->ClientLedgerFinalPoints = $Client->ClientRewardPoints + $UplinePoints;
                    $ClientLedger->ClientLedgerType = "UPLINE";
                    $ClientLedger->ClientLedgerBatchNumber = $BatchNumber;
                    $ClientLedger->save();

                    $Client->ClientRewardPoints = $Client->ClientRewardPoints + $UplinePoints;
                }

                if ($PlanProductPoints) {
                    if ($ChildPosition == "LEFT") {
                        $Client->ClientLeftNumber++;
                        $Client->ClientTotalNumber++;
                        $Client->ClientLeftPoints = $Client->ClientLeftPoints + $PlanProductPoints;
                        $Client->ClientTotalPoints = $Client->ClientTotalPoints + $PlanProductPoints;
                    }
                    if ($ChildPosition == "RIGHT") {
                        $Client->ClientRightNumber++;
                        $Client->ClientTotalNumber++;
                        $Client->ClientRightPoints = $Client->ClientRightPoints + $PlanProductPoints;
                        $Client->ClientTotalPoints = $Client->ClientTotalPoints + $PlanProductPoints;
                    }
                }

                $Client->save();
                $ChildPosition = PlanNetwork::where("IDClient", $IDParentClient)->first()->PlanNetworkPosition;
            }
        }
    }
}

function ProductBranches($IDLink, $Client, $Type)
{
    if ($Client) {
        $ClientLanguage = LocalAppLanguage($Client->ClientLanguage);
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
        'forwardTokenEn' => $forwardTokenEn, 'forwardTokenAr' => $forwardTokenAr, 'forwardTokenHMSEn' => $forwardTokenHMSEn, 'forwardTokenHMSAr' => $forwardTokenHMSAr
    ];
    return $forwardList;
}

function FirebaseDownStreamNotify($forwardList, $payload)
{

    if (!empty($forwardList['forwardList'])) {
        if ($payload['IDTemp'] == 0) {
            //private notification (diff for each iteration) needs to create one in ClientNotification table
            $ClientNotification = new ClientNotification;
            $ClientNotification->TitleAr = $payload['notifyTitleAr'];
            $ClientNotification->MessageAr = $payload['notifyBodyAr'];
            $ClientNotification->TitleEn = $payload['notifyTitleEn'];
            $ClientNotification->MessageEn = $payload['notifyBodyEn'];
            $ClientNotification->NotificationType = 1;
            if (array_key_exists('IDAgent', $payload)) {
                $ClientNotification->IDAgent = $payload['IDAgent'];
            }
            $ClientNotification->save();
        }
    }

    $optionBuilder = new OptionsBuilder();
    $optionBuilder->setTimeToLive(60 * 20);
    $optionBuilder->setContentAvailable(1);
    $option = $optionBuilder->build();

    $FilePath = Null;
    if (array_key_exists('FilePath', $payload)) {
        $FilePath = $payload['FilePath'];
    }

    $dataBuilder = new PayloadDataBuilder();
    $dataBuilder->addData(['NotificationType' => $payload['NotificationType'], 'Screen' => $payload['Screen'], 'IDData' => $payload['IDData'], 'DataType' => $payload['DataType'], 'FilePath' => $FilePath, 'Message' => $payload['notifyBodyEn']]);
    $data = $dataBuilder->build();

    $notificationEn = null;
    $notificationAr = null;
    if ($payload['notifyAllowed']) {
        $notificationBuilderEn = new PayloadNotificationBuilder($payload['notifyTitleEn']);
        $notificationBuilderEn->setBody($payload['notifyBodyEn'])
            ->setSound($payload['notifySound']);
        $notificationBuilderAr = new PayloadNotificationBuilder($payload['notifyTitleAr']);
        $notificationBuilderAr->setBody($payload['notifyBodyAr'])
            ->setSound($payload['notifySound']);
        $notificationEn = $notificationBuilderEn->build();
        $notificationAr = $notificationBuilderAr->build();
    }
    $downstreamResponseEn = null;
    $downstreamResponseAr = null;
    if (count($forwardList['forwardTokenEn']) != 0) {
        $downstreamResponseEn = FCM::sendTo($forwardList['forwardTokenEn'], $option, $notificationEn, $data);
    }
    if (count($forwardList['forwardTokenAr']) != 0) {
        $downstreamResponseAr = FCM::sendTo($forwardList['forwardTokenAr'], $option, $notificationAr, $data);
    }

    if ($payload['notifyAllowed']) {

        $forwardListResponseFail = [];
        if ($downstreamResponseEn) {
            foreach ($downstreamResponseEn->tokensToDelete() as $token) {
                $forwardListResponseFail[$token] = true;
            }
            foreach ($downstreamResponseEn->tokensToRetry() as $token) {
                $forwardListResponseFail[$token] = true;
            }
            foreach ($downstreamResponseEn->tokensToModify() as $token) {
                $forwardListResponseFail[$token] = true;
            }
            foreach (array_keys($downstreamResponseEn->tokensWithError()) as $token) {
                $forwardListResponseFail[$token] = true;
            }
        }
        if ($downstreamResponseAr) {
            foreach ($downstreamResponseAr->tokensToDelete() as $token) {
                $forwardListResponseFail[$token] = true;
            }
            foreach ($downstreamResponseAr->tokensToRetry() as $token) {
                $forwardListResponseFail[$token] = true;
            }
            foreach ($downstreamResponseAr->tokensToModify() as $token) {
                $forwardListResponseFail[$token] = true;
            }
            foreach (array_keys($downstreamResponseAr->tokensWithError()) as $token) {
                $forwardListResponseFail[$token] = true;
            }
        }
    }

    if (!empty($forwardList['forwardList'])) {
        foreach ($forwardList['forwardList'] as $IDClient => $Token) {
            $ClientNotificationDetail = new ClientNotificationDetail;
            $ClientNotificationDetail->IDClientNotification = $ClientNotification->IDClientNotification;
            $ClientNotificationDetail->IDClient = $IDClient;
            if ($Token) {
                $ClientNotificationDetail->NotificationDetailStatus = !isset($forwardListResponseFail[$Token]);
            } else {
                $ClientNotificationDetail->NotificationDetailStatus = 0;
            }
            $ClientNotificationDetail->save();
        }
    }
}


function SaveImage($File, $FolderName, $ID)
{
    return "uploads/" . Storage::disk('uploads')->put($FolderName . "/" . $ID, $File);
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

    log::info($response);
}
