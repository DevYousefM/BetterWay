<?php

namespace App\Http\Controllers\Admin\Client;

header('Content-type: application/json');

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\PlanResource;
use App\Http\Resources\Admin\PlanProductResource;
use App\Http\Resources\Admin\ClientLedgerResource;
use App\Http\Resources\Admin\ClientBonanzaResource;
use App\Http\Resources\Admin\BonanzaResource;
use App\Http\Resources\Admin\PositionResource;
use App\Http\Resources\Admin\ClientResource;
use App\Http\Resources\Admin\EventResource;
use App\Http\Resources\Admin\ToolResource;
use App\Http\Resources\Admin\UserResource;
use App\Http\Resources\Admin\CountryResource;
use App\Http\Resources\Admin\CityResource;
use App\Http\Resources\Admin\AreaResource;
use App\Http\Resources\Admin\BalanceTransferResource;
use App\Http\Resources\ClientContractsResource;
use App\Http\Resources\ClientPositionLog;
use App\Http\Resources\PositionsForClients;
use App\Jobs\SendBonanzaNotifications;
use App\V1\GhazalCart;
use App\V1\Brand\Brand;
use App\V1\Brand\BrandProduct;
use App\V1\User\User;
use App\V1\User\Role;
use App\V1\Client\Client;
use App\V1\Client\ClientLedger;
use App\V1\Client\Position;
use App\V1\Client\ClientChatDetail;
use App\V1\Client\ClientChat;
use App\V1\Client\PositionClient;
use App\V1\Client\PositionBrand;
use App\V1\Client\ClientBonanza;
use App\V1\Client\ClientDocument;
use App\V1\Client\ClientBrandProduct;
use App\V1\Client\PositionsForClients as ClientPositionsForClients;
use App\V1\General\APICode;
use App\V1\General\SocialMedia;
use App\V1\General\GeneralSetting;
use App\V1\Location\Country;
use App\V1\Location\City;
use App\V1\Location\Area;
use App\V1\Event\Event;
use App\V1\Event\EventAttendee;
use App\V1\Plan\Plan;
use App\V1\Plan\PlanProduct;
use App\V1\Plan\Bonanza;
use App\V1\Plan\BonanzaBrand;
use App\V1\Plan\PlanNetwork;
use App\V1\Plan\PlanProductUpgrade;
use App\V1\Plan\PlanNetworkAgency;
use App\V1\Plan\PlanProductGallery;
use App\V1\Plan\PlanProductSocialLink;
use App\V1\Tool\Tool;
use App\V1\Tool\ClientTool;
use App\V1\Payment\BalanceTransfer;
use App\V1\Payment\CompanyLedger;
use App\V1\User\ActionBackLog;
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
use Exception;
use Facade\FlareClient\Http\Exceptions\BadResponse;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Paytabscom\Laravel_paytabs\Facades\paypage;
use Mpdf\Mpdf;

class ClientController extends Controller
{
    public function ClientRegister(Request $request)
    {
        $Admin = auth('user')->user();
        if ($request->Filled('ClientAppLanguage')) {
            $ClientAppLanguage = $request->ClientAppLanguage;
        } else {
            $ClientAppLanguage = "ar";
        }
        if ($request->Filled('ClientGender')) {
            $ClientGender = $request->ClientGender;
        } else {
            $ClientGender = "PRIVATE";
        }

        $ClientEmail = Null;
        $ClientPassport = Null;
        $response_code = 200;

        $IDArea = 1;
        if ($request->Filled('IDArea')) {
            $IDArea = $request->IDArea;
        } else {
            return RespondWithBadRequest(39);
        }
        if ($request->Filled('LoginBy')) {
            $LoginBy = $request->LoginBy;
        } else {
            $LoginBy = "MANUAL";
        }

        $ClientNationalID = null;
        if ($request->Filled('ClientNationalID')) {
            $ClientNationalID = $request->ClientNationalID;
        } else if ($request->Filled('ClientPassport')) {
            $ClientPassport = $request->ClientPassport;
        } else {
            return RespondWithBadRequest(40);
        }
        if ($request->Filled('ClientBirthDate')) {

            $ClientBirthDate = $request->ClientBirthDate;
        } else {
            return RespondWithBadRequest(41);
        }

        $IDNationality = 1;
        if ($request->Filled('IDNationality')) {
            $IDNationality = $request->IDNationality;
        } else {
            return RespondWithBadRequest(43);
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

            return RespondWithBadRequest(51);
        }

        if ($request->Filled('ClientPhoneFlag')) {
            $ClientPhoneFlag = $request->ClientPhoneFlag;
        } else {
            return RespondWithBadRequest(52);
        }

        if ($request->Filled('ClientPassword')) {
            $ClientPassword = $request->ClientPassword;
        } else {
            return RespondWithBadRequest(53);
        }

        if ($request->Filled('ClientName')) {
            $ClientName = $request->ClientName;
        } else {
            return RespondWithBadRequest(54);
        }
        if ($request->Filled('ClientNameArabic')) {
            $ClientNameArabic = $request->ClientNameArabic;
        } else {

            return RespondWithBadRequest(42);
        }
        if ($request->Filled('ClientPassport')) {
            $ClientPassport = $request->ClientPassport;
        }
        if ($request->Filled('ClientIDAddress')) {
            $ClientIDAddress = $request->ClientIDAddress;
        } else {
            return RespondWithBadRequest(45);
        }
        if ($request->Filled('ClientCurrentAddress')) {
            $ClientCurrentAddress = $request->ClientCurrentAddress;
        } else {
            return RespondWithBadRequest(44);
        }
        if ($request->Filled('ClientPrivacy')) {
            $ClientPrivacy = $request->ClientPrivacy;
        } else {
            $ClientPrivacy = 1;
        }

        $ClientRecord = Client::where('ClientPhone', $ClientPhone)->where("ClientDeleted", 0)->first();
        if ($ClientRecord) {
            return RespondWithBadRequest(3);
        }

        if ($ClientNationalID) {
            $ClientRecord = Client::where('ClientNationalID', $ClientNationalID)->where("ClientDeleted", 0)->first();
            if ($ClientRecord) {
                return RespondWithBadRequest(20);
            }
        }

        $NextIDClient = DB::select('SELECT AUTO_INCREMENT FROM information_schema.TABLES WHERE  TABLE_NAME = "clients"')[0]->AUTO_INCREMENT;
        $ImageExtArray = ["jpeg", "jpg", "png", "svg"];
        if ($request->Filled('ClientNationalID')) {
            if ($request->file('ClientNationalIDImage')) {
                if (!in_array($request->ClientNationalIDImage->extension(), $ImageExtArray)) {
                    return RespondWithBadRequest(15);
                }
                $ClientNationalIDImage = SaveImage($request->file('ClientNationalIDImage'), "clients", $NextIDClient);
            } else {

                return RespondWithBadRequest(48);
            }
            if ($request->file('ClientNationalIDImageBack')) {
                if (!in_array($request->ClientNationalIDImageBack->extension(), $ImageExtArray)) {
                    return RespondWithBadRequest(15);
                }
                $ClientNationalIDImageBack = SaveImage($request->file('ClientNationalIDImageBack'), "clients", $NextIDClient);
            } else {
                return RespondWithBadRequest(49);
            }
        }
        $ClientPicture = Null;
        $ClientPassportImage = Null;
        if ($request->Filled('ClientPassport')) {
            if ($request->file('ClientPassportImage')) {
                if (!in_array($request->ClientPassportImage->extension(), $ImageExtArray)) {
                    return RespondWithBadRequest(15);
                }
                $ClientPassportImage = SaveImage($request->file('ClientPassportImage'), "clients", $NextIDClient);
            } else {
                return RespondWithBadRequest(50);
            }
        }

        if ($request->file('ClientPicture')) {
            if (!in_array($request->ClientPicture->extension(), $ImageExtArray)) {
                return RespondWithBadRequest(15);
            }
            $ClientPicture = SaveImage($request->file('ClientPicture'), "clients", $NextIDClient);
        }

        $PlanNetwork = NULL;
        $IDPreviousClient = $request->IDPreviousClient;
        if ($IDPreviousClient) {
            $PreviousClient = Client::find($IDPreviousClient);
            if (!$PreviousClient) {
                return RespondWithBadRequest(1);
            }
            $PlanNetwork = PlanNetwork::where("IDClient", $IDPreviousClient)->first();
            if (!$PlanNetwork) {
                return RespondWithBadRequest(1);
            }
        }

        if ($IDPreviousClient) {
            $ClientAppID = $PreviousClient->ClientAppID;
        } else {
            $TimeFormat = new DateTime('now');
            $Time = $TimeFormat->format('H');
            $Time = $Time . $TimeFormat->format('i');
            $ClientAppID = "0" . $NextIDClient . $Time;
        }

        $Client = new Client;
        $Client->IDNationality = $IDNationality;
        $Client->ClientAppID = $ClientAppID;
        $Client->ClientEmail = $ClientEmail;
        $Client->IDArea = $IDArea;
        $Client->ClientPhone = $ClientPhone;
        $Client->ClientPhoneFlag = $ClientPhoneFlag;
        $Client->LoginBy = $LoginBy;
        if ($LoginBy != "MANUAL") {
            $Client->ClientSocialUniqueID = $ClientPassword;
        }
        $Client->ClientPassword = Hash::make($ClientPassword);
        $Client->ClientName = $ClientName;
        $Client->ClientNameArabic = $ClientNameArabic;
        if ($ClientPrivacy == 0) {
            $Client->ClientPrivacy = 0;
        } else {
            $Client->ClientPrivacy = 1;
        }
        $Client->ClientBirthDate = $ClientBirthDate;
        $Client->ClientNationalID = $ClientNationalID;
        $Client->ClientAppLanguage = $ClientAppLanguage;
        $Client->ClientPicture = $ClientPicture;
        $Client->ClientGender = $ClientGender;
        $Client->ClientPassport = $ClientPassport;
        $Client->ClientIDAddress = $ClientIDAddress;
        $Client->ClientCurrentAddress = $ClientCurrentAddress;
        $Client->ClientSecondPhone = $request->ClientSecondPhone;
        $Client->VerificationCode = CreateVerificationCode();
        if ($IDPreviousClient) {
            $Client->ClientLeftNumber = $PreviousClient->ClientLeftNumber;
            $Client->ClientRightNumber = $PreviousClient->ClientRightNumber;
        }
        $Client->save();

        if ($ClientNationalID) {

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
        }

        if ($ClientPassportImage) {
            $ClientDocument = new ClientDocument;
            $ClientDocument->IDClient = $Client->IDClient;
            $ClientDocument->ClientDocumentPath = $ClientPassportImage;
            $ClientDocument->ClientDocumentType = "PASSPORT";
            $ClientDocument->save();
        }


        $Desc = "Client " . $Client->ClientName . " was added";
        if ($IDPreviousClient) {
            $PreviousClient->ClientDeleted = 1;
            $PreviousClient->save();
            $PlanNetwork->IDClient = $Client->IDClient;
            $PlanNetwork->save();
            $Desc = "Client " . $Client->ClientName . " replaced client " . $PreviousClient->ClientName;
        }

        ActionBackLog($Admin->IDUser, $Client->IDClient, "ADD_CLIENT", $Desc);

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
            "ClientGender" => $ClientGender,
            'ClientStatus' => "INACTIVE"
        );
        $response_array = array('Success' => true, 'ApiMsg' => trans('apicodes.' . $APICode->IDApiCode), 'ApiCode' => $APICode->IDApiCode, 'Response' => $response);
        $response = Response::json($response_array, $response_code);
        return $response;
    }

    public function test(Request $request)
    {

        // $Referral = $request->Referral;


        // if ($request->Filled('Upline')) {
        //     $Upline = $request->Upline;
        // } else {
        //     $Upline = NULL;
        // }

        // $PlanNetworkPosition = $request->Position;

        // if ($Upline) {
        //     if ($Upline[0] == "0") {
        //         $Upline = "+2" . $Upline;
        //     }
        // }



        // if ($Upline) {
        //     $ParentClient = Client::where("ClientDeleted", 0)->where(function ($query) use ($Upline) {
        //         $query->where('ClientAppID', $Upline)
        //             ->orwhere('ClientEmail', $Upline)
        //             ->orwhere('ClientPhone', $Upline);
        //     })->first();

        //     if (!$ParentClient) {

        //         return RespondWithBadRequest(23);
        //     }
        // } else {
        //     $IDParentClient = Null;
        // }

        // $ReferralClient = Client::where("ClientDeleted", 0)->where(function ($query) use ($Referral) {
        //     $query->where('ClientAppID', $Referral)
        //         ->orwhere('ClientEmail', $Referral)
        //         ->orwhere('ClientPhone', $Referral[0] == "0"
        //             ? $Referral = "+2" . $Referral : $Referral);
        // })->first();
        // // return $ReferralClient;
        // if (!$ReferralClient) {
        //     return RespondWithBadRequest(23);
        // }

        // $IDReferralClient = $ReferralClient->IDClient;

        // // if ($Upline) {
        // // $ParentPlanNetwork = PlanNetwork::where("IDClient", $ParentClient->IDClient)->first();
        // // $IDParentClient = $ParentClient->IDClient;
        // // $PlanNetworkPath = $ParentPlanNetwork->PlanNetworkPath;
        // // $PlanNetworkPath = explode("-", $PlanNetworkPath);
        // // if (!in_array($ReferralClient->IDClient, $PlanNetworkPath) && $IDParentClient != $IDReferralClient) {
        // //     return "adf";
        // // }

        // // $ParentNetwork = PlanNetwork::where("IDParentClient", $ParentClient->IDClient)->count();
        // // $ParentPositionNetwork = PlanNetwork::where("IDParentClient", $ParentClient->IDClient)->where("PlanNetworkPosition", $PlanNetworkPosition)->count();
        // // $ChildNumber = $ParentPlanNetwork->PlanNetworkAgencyNumber * 2;
        // // if ($ParentNetwork == $ChildNumber) {
        // //     return RespondWithBadRequest(24);
        // // }
        // // if ($ParentPositionNetwork == $ParentPlanNetwork->PlanNetworkAgencyNumber) {
        // //     return RespondWithBadRequest(34);
        // // }
        // // }
        // $current = $ReferralClient->IDClient;
        // $lastPlanNetwork = null;

        // while (true) {
        //     $PlanNetwork = PlanNetwork::where("IDParentClient", $current)
        //         ->where("PlanNetworkPosition", "RIGHT")
        //         ->first();

        //     if ($PlanNetwork) {
        //         $current = $PlanNetwork->IDClient;
        //         $lastPlanNetwork = $PlanNetwork;
        //     } else {
        //         break;
        //     }
        // }

        // return $lastPlanNetwork;



        // $CurrentTime = new DateTime('now');
        // $CurrentTime = $CurrentTime->format('Y-m-d H:i:s');

        // $Clients = Client::where("ClientStatus", "ACTIVE")->where("ClientDeleted", 0)->get();
        // $Bonanzas = Bonanza::where('BonanzaStatus', 'ACTIVE')->where("BonanzaEndTime", "<", $CurrentTime)->get();
        // foreach ($Clients as $Client) {
        //     $IDClient = $Client->IDClient;
        //     foreach ($Bonanzas as $Bonanza) {
        //         $StartDate = $Bonanza->BonanzaStartTime;
        //         $EndDate = $Bonanza->BonanzaEndTime;

        //         $BonanzaLeftPoints = $Bonanza->BonanzaLeftPoints;
        //         $BonanzaRightPoints = $Bonanza->BonanzaRightPoints;

        //         if ($BonanzaLeftPoints > 0 && $BonanzaRightPoints > 0) {
        //             if (!$this->checkBalancePoints($Client, $BonanzaLeftPoints, $BonanzaRightPoints, $StartDate, $EndDate)) {
        //                 break;
        //             }
        //         }

        //         $BonanzaTotalPoints = $Bonanza->BonanzaTotalPoints;
        //         if ($BonanzaTotalPoints > 0) {
        //             if (!$this->checkTotalPoints($Client, $BonanzaTotalPoints, $StartDate, $EndDate)) {
        //                 break;
        //             }
        //         }

        //         $BonanzaVisitNumber = $Bonanza->BonanzaVisitNumber;

        //         if ($BonanzaVisitNumber > 0) {
        //             if (!$this->checkVisitsNumber($Client, $BonanzaVisitNumber, $StartDate, $EndDate)) {
        //                 break;
        //             }
        //         }

        //         $IsBonanzaUniqueVisits = $Bonanza->IsBonanzaUniqueVisits;
        //         if ($IsBonanzaUniqueVisits) {
        //             if (!$this->checkUniqueVisits($Client, $Bonanza, $StartDate, $EndDate)) {
        //                 break;
        //             }
        //         }

        //         $BonanzaReferralNumber = $Bonanza->BonanzaReferralNumber;
        //         if ($BonanzaReferralNumber > 0) {
        //             if (!$this->checkReferralsNumber($Client, $BonanzaReferralNumber, $StartDate, $EndDate)) {
        //                 break;
        //             }
        //         }


        //         $ClientBonanza = new ClientBonanza;
        //         $ClientBonanza->IDBonanza = $Bonanza->IDBonanza;
        //         $ClientBonanza->IDClient = $IDClient;
        //         if ($BonanzaLeftPoints > 0 && $BonanzaRightPoints > 0) {
        //             $ClientBonanza->ClientLeftPoints =   $this->getFilteredLeftPoints($Client, $StartDate, $EndDate)->sum('ClientLedgerPoints');
        //             $ClientBonanza->ClientRightPoints = $this->getFilteredRightPoints($Client, $StartDate, $EndDate)->sum('ClientLedgerPoints');
        //         }
        //         if ($BonanzaTotalPoints > 0) {
        //             $ClientBonanza->ClientTotalPoints = $this->getFilteredTotalPoints($Client, $StartDate, $EndDate)->sum('ClientLedgerPoints');
        //         }
        //         if ($BonanzaVisitNumber > 0) $ClientBonanza->ClientVisitNumber =  $this->getFilteredVisits($Client, $StartDate, $EndDate)->count();
        //         if ($BonanzaReferralNumber > 0) $ClientBonanza->BonanzaReferralNumber = $this->getFilteredReferral($Client, $StartDate, $EndDate)->count();
        //         $ClientBonanza->BrandVisit = 0;

        //         $ClientBonanza->save();

        //         $BatchNumber = "#B" . $ClientBonanza->IDClientBonanza;
        //         $TimeFormat = new DateTime('now');
        //         $Time = $TimeFormat->format('H');
        //         $Time = $Time . $TimeFormat->format('i');
        //         $BatchNumber = $BatchNumber . $Time;

        //         if ($Bonanza->BonanzaChequeValue > 0) {
        //             ChequesLedger($Client, $Bonanza->BonanzaChequeValue, 'BONANZA', "REWARD", 'WALLET', $BatchNumber);
        //         }
        //         if ($Bonanza->BonanzaRewardPoints > 0) {
        //             AdjustLedger($Client, 0, $Bonanza->BonanzaRewardPoints, 0, 0, Null, "BONANZA", "WALLET", "REWARD", $BatchNumber);
        //         }
        //         $Bonanza->BonanzaStatus = "EXPIRED";
        //         $Bonanza->save();
        //         if ($Bonanza->BonanzaChequeValue > 0) {
        //             $CompanyLedger = new CompanyLedger;
        //             $CompanyLedger->IDSubCategory = 22;
        //             $CompanyLedger->CompanyLedgerAmount = $Bonanza->BonanzaChequeValue;
        //             $CompanyLedger->CompanyLedgerDesc = "Bonanza Payment to Client " . $Client->ClientName;
        //             $CompanyLedger->CompanyLedgerProcess = "AUTO";
        //             $CompanyLedger->CompanyLedgerType = "DEBIT";
        //             $CompanyLedger->save();
        //         }
        //     }
        // }
    }

    public function ClientNetworkAdd(Request $request)
    {
        $Admin = auth('user')->user();
        $IDClient = $request->IDClient;
        $IDPlanProduct = $request->IDPlanProduct;

        $PlanNetworkPosition = $request->PlanNetworkPosition;
        if (!$IDClient) {
            return RespondWithBadRequest(505);
        }
        if (!$IDPlanProduct) {
            return RespondWithBadRequest(506);
        }
        if (!$PlanNetworkPosition) {
            return RespondWithBadRequest(507);
        }
        if ($request->Filled('Referral')) {
            $Referral = $request->Referral;
        } else {
            return RespondWithBadRequest(55);
        }

        if ($request->Filled('Upline')) {
            $Upline = $request->Upline;
        } else {
            $Upline = NULL;
        }

        if ($request->Filled('PlanNetworkPosition')) {
            $PlanNetworkPosition = $request->PlanNetworkPosition;
        } else {
            $PlanNetworkPosition = "LEFT";
        }

        if ($Upline) {
            if ($Upline[0] == "0") {
                $Upline = "+2" . $Upline;
            }
        }

        if ($Referral[0] == "0") {
            $Referral = "+2" . $Referral;
        }

        if ($Upline) {
            $ParentClient = Client::where("ClientDeleted", 0)->where(function ($query) use ($Upline) {
                $query->where('ClientAppID', $Upline)
                    ->orwhere('ClientEmail', $Upline)
                    ->orwhere(
                        'ClientPhone',
                        $Upline[0] == "0" ? "+2" . $Upline : $Upline
                    );
            })->first();

            if (!$ParentClient) {
                return RespondWithBadRequest(23);
            }
        } else {
            $IDParentClient = Null;
        }


        $ReferralClient = Client::where("ClientDeleted", 0)->where(function ($query) use ($Referral) {
            $query->where('ClientAppID', $Referral)
                ->orwhere('ClientEmail', $Referral)
                ->orwhere(
                    'ClientPhone',
                    $Referral[0] == "0" ? "+2" . $Referral : $Referral
                );
        })->first();

        if (!$ReferralClient) {
            return RespondWithBadRequest(23);
        }

        $IDReferralClient = $ReferralClient->IDClient;
        $Client = Client::find($IDClient);

        if ($Upline) {
            $ParentPlanNetwork = PlanNetwork::where("IDClient", $ParentClient->IDClient)->first();
            $IDParentClient = $ParentClient->IDClient;
            $PlanNetworkPath = $ParentPlanNetwork->PlanNetworkPath;
            $PlanNetworkPath = explode("-", $PlanNetworkPath);
            if (in_array($ParentClient->IDClient, $PlanNetworkPath) && $IDParentClient != $IDReferralClient) {
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
        $PlanProduct = PlanProduct::find($IDPlanProduct);
        if (!$PlanProduct) {
            return RespondWithBadRequest(203);
        }

        $IDPlan = $PlanProduct->IDPlan;

        if ($ReferralClient[0] == "0") {
            $ReferralClient = "+2" . $ReferralClient;
        }


        $IDReferral = $ReferralClient->IDClient;

        if ($Upline) {
            $ParentClient = Client::where("ClientDeleted", 0)->where(function ($query) use ($Upline) {
                $query->where('ClientAppID', $Upline)
                    ->orwhere('ClientEmail', $Upline)
                    ->orwhere(
                        'ClientPhone',
                        $Upline[0] == "0" ? "+2" . $Upline : $Upline
                    );
            })->first();
            if (!$Upline) {
                return RespondWithBadRequest(23);
            }
        } else {
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
                $ParentClient = Client::find($IDParentClient);
            }
        }

        $ClientPlanNetwork = PlanNetwork::where("IDClient", $IDClient)->first();
        if ($ClientPlanNetwork) {
            return RespondWithBadRequest(25);
        }

        $ParentPlanNetwork = PlanNetwork::where("IDClient", $ParentClient->IDClient)->first();
        $IDParentClient = $ParentClient->IDClient;
        $IDReferralClient = $ReferralClient->IDClient;

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


        $Client->IDReferral = $IDReferralClient;
        $Client->IDUpline = $IDParentClient;
        $Client->NetworkPosition = $PlanNetworkPosition;
        $Client->ClientStatus = "ACTIVE";

        $BatchNumber = "#PN" . $PlanNetwork->IDPlanNetwork;
        $TimeFormat = new DateTime('now');
        $Time = $TimeFormat->format('H');
        $Time = $Time . $TimeFormat->format('i');
        $BatchNumber = $BatchNumber . $Time;
        AdjustLedger($Client, 0, 0, 0, 0, $PlanNetwork, "WALLET", "PLAN_PRODUCT", "PAYMENT", $BatchNumber);

        $Desc = "Client Network was added";
        ActionBackLog($Admin->IDUser, $IDClient, "EDIT_CLIENT", $Desc);

        return RespondWithSuccessRequest(8);
    }

    public function ClientList(Request $request, Client $Clients)
    {
        $User = auth('user')->user();
        if (!$User) {
            return RespondWithBadRequest(10);
        }
        //  $IDPage = $request->IDPage;
        $IDCity = $request->IDCity;
        $IDArea = $request->IDArea;
        $IDPlan = $request->IDPlan;
        $IDPosition = $request->IDPosition;
        $IDPlanProduct = $request->IDProduct;
        $StartDate = $request->StartDate;
        $EndDate = $request->EndDate;
        $SearchKey = $request->SearchKey;
        $UplineSearchKey = $request->UplineSearchKey;
        $ReferralSearchKey = $request->ReferralSearchKey;
        $ClientStatus = $request->ClientStatus;
        $ClientDeleted = $request->ClientDeleted;

        $CashFrom = $request->CashFrom;
        $CashTo = $request->CashTo;

        $RewardPointsFrom = $request->RewardPointsFrom;
        $RewardPointsTo = $request->RewardPointsTo;

        $ClientContractCompleted = $request->ClientContractCompleted;

        $BalanceSort = $request->BalanceSort;
        // if (!$IDPage) {
        //     $IDPage = 0;
        // } else {
        //     $IDPage = ($request->IDPage - 1) * 20;
        // }

        $Clients = $Clients
            ->leftjoin("positions", "positions.IDPosition", "clients.IDPosition")
            ->leftjoin("areas", "areas.IDArea", "clients.IDArea")
            ->leftjoin("cities", "cities.IDCity", "areas.IDCity")
            ->leftjoin("plannetwork", "plannetwork.IDClient", "clients.IDClient")
            ->leftjoin("clients as C2", "C2.IDClient", "plannetwork.IDParentClient")
            ->leftjoin("clients as C3", "C3.IDClient", "plannetwork.IDReferralClient")
            ->leftjoin("plans", "plans.IDPlan", "plannetwork.IDPlan")->where("clients.ClientDeleted", 0);

        if ($SearchKey != "") {
            $Clients = $Clients->where(function ($query) use ($SearchKey) {
                $query->where('clients.ClientName', 'like', '%' . $SearchKey . '%')
                    ->orwhere('clients.ClientAppID', 'like', '%' . $SearchKey . '%')
                    ->orwhere('clients.ClientPhone', 'like', '%' . $SearchKey . '%');
            });
        }
        if ($UplineSearchKey != "") {
            $Clients = $Clients->where(function ($query) use ($UplineSearchKey) {
                $query->where('C2.ClientName', 'like', '%' . $UplineSearchKey . '%')
                    ->orwhere('C2.ClientAppID', 'like', '%' . $UplineSearchKey . '%')
                    ->orwhere('C2.ClientPhone', 'like', '%' . $UplineSearchKey . '%');
            });
        }
        if ($ReferralSearchKey != "") {
            $Clients = $Clients->where(function ($query) use ($ReferralSearchKey) {
                $query->where('C3.ClientName', 'like', '%' . $ReferralSearchKey . '%')
                    ->orwhere('C3.ClientAppID', 'like', '%' . $ReferralSearchKey . '%')
                    ->orwhere('C3.ClientPhone', 'like', '%' . $ReferralSearchKey . '%');
            });
        }

        if ($ClientStatus) {
            $Clients = $Clients->where("clients.ClientStatus", $ClientStatus);
        }
        if ($ClientContractCompleted) {
            $Clients = $Clients->whereHas("clientdocuments", function ($query) {
                $query->where('ClientDocumentType', 'CONTRACT');
            });
        }
        if ($ClientContractCompleted == 0 && !is_null($ClientContractCompleted)) {
            $Clients = $Clients->whereDoesntHave("clientdocuments", function ($query) {
                $query->where('ClientDocumentType', 'CONTRACT');
            });
        }
        if ($IDCity) {
            $Clients = $Clients->where("areas.IDCity", $IDCity);
        }
        if ($IDArea) {
            $Clients = $Clients->where("areas.IDArea", $IDArea);
        }
        if ($IDPlan) {
            $Clients = $Clients->where("plannetwork.IDPlan", $IDPlan);
        }
        if ($IDPosition) {
            $Clients = $Clients->where("clients.IDPosition", $IDPosition);
        }
        if ($IDPlanProduct) {
            $Clients = $Clients->where("plannetwork.IDPlanProduct", $IDPlanProduct);
        }
        if ($StartDate) {
            $Clients = $Clients->where("clients.created_at", ">=", $StartDate);
        }
        if ($EndDate) {
            $Clients = $Clients->where("clients.created_at", "<=", $EndDate . ' 23:59:59');
        }
        if ($CashFrom && $CashTo) {
            if ($CashFrom == $CashTo) {
                $Clients = $Clients->where("clients.ClientBalance", $CashFrom);
            } else {
                $Clients = $Clients->whereBetween("clients.ClientBalance", [$CashFrom, $CashTo]);
            }
        } elseif ($CashFrom) {
            $Clients = $Clients->where("clients.ClientBalance", ">=", $CashFrom);
        } elseif ($CashTo) {
            $Clients = $Clients->where("clients.ClientBalance", "<=", $CashTo);
        }

        if ($RewardPointsFrom && $RewardPointsTo) {
            if ($RewardPointsFrom == $RewardPointsTo) {
                $Clients = $Clients->where("clients.ClientRewardPoints", $RewardPointsFrom);
            } else {
                $Clients = $Clients->whereBetween("clients.ClientRewardPoints", [$RewardPointsFrom, $RewardPointsTo]);
            }
        } elseif ($RewardPointsFrom) {
            $Clients = $Clients->where("clients.ClientRewardPoints", ">=", $RewardPointsFrom);
        } elseif ($RewardPointsTo) {
            $Clients = $Clients->where("clients.ClientRewardPoints", "<=", $RewardPointsTo);
        }

        $ClientNumber = $Clients->count();
        $ClientTotalRewardPoints = $Clients->sum("clients.ClientRewardPoints");

        $Client = Client::first();
        if ($Client) {
            $ClientTotalPoints = $Client->ClientTotalPoints;
        } else {
            $ClientTotalPoints = 0;
        }

        $ActiveNetworkers = Client::where("ClientStatus", "ACTIVE")->where("ClientDeleted", 0)->count();

        // $Pages = ceil($Clients->count() / 20);
        if ($BalanceSort) {
            $Clients = $Clients->orderby("clients.ClientBalance", $BalanceSort);
        } else {
            $Clients = $Clients->orderby("clients.IDClient", "DESC");
        }
        $Clients = $Clients->select("clients.IDClient", "clients.ClientName", "clients.ClientEmail", "clients.ClientPhone", "clients.ClientSecondPhone", "clients.ClientAppID", "clients.ClientStatus", "clients.ClientPicture", "clients.ClientBalance", "clients.ClientContractCompleted", "clients.ClientGender", "clients.ClientBirthDate", "clients.ClientNationalID", "clients.ClientRewardPoints", "clients.ClientLeftPoints", "clients.ClientRightPoints", "clients.ClientLeftNumber", "clients.ClientRightNumber", "clients.ClientPrivacy", "clients.ClientNameArabic", "clients.ClientDeleted", "clients.ClientCurrentAddress", "clients.ClientIDAddress", "clients.ClientPassport", "clients.ClientNationality", "areas.AreaNameEn", "areas.AreaNameAr", "cities.CityNameEn", "cities.CityNameAr", "clients.created_at", "positions.PositionTitleEn", "positions.PositionTitleAr", "plannetwork.IDParentClient", "clients.IDNationality", "plannetwork.IDReferralClient", "plans.PlanNameEn", "plans.PlanNameAr", "C2.ClientName as ParentName", "C2.ClientPhone as ParentPhone", "C3.ClientName as ReferralName", "C3.ClientPhone as ReferralPhone")->get();
        $Clients = ClientResource::collection($Clients);
        $Response = array("Clients" => $Clients, "ClientNumber" => $ClientNumber, "ClientTotalRewardPoints" => $ClientTotalRewardPoints, "ClientTotalPoints" => $ClientTotalPoints, "ActiveNetworkers" => $ActiveNetworkers);

        $APICode = APICode::where('IDAPICode', 8)->first();
        $Response = array(
            'Success' => true,
            'ApiMsg' => __('apicodes.' . $APICode->IDApiCode),
            'ApiCode' => $APICode->IDApiCode,
            'Response' => $Response,
        );
        return $Response;
    }

    public function ClientDetails($IDClient)
    {
        $User = auth('user')->user();
        if (!$User) {
            return RespondWithBadRequest(10);
        }
        $Client = Client::leftjoin("areas", "areas.IDArea", "clients.IDArea")->leftjoin("cities", "cities.IDCity", "areas.IDCity")->where("clients.IDClient", $IDClient)->leftjoin("plannetwork", "plannetwork.IDClient", "clients.IDClient")->leftjoin("clients as C2", "C2.IDClient", "plannetwork.IDParentClient")->leftjoin("clients as C3", "C3.IDClient", "plannetwork.IDReferralClient")->leftjoin("plans", "plans.IDPlan", "plannetwork.IDPlan");
        $Client = $Client->select("clients.IDClient", "clients.IDNationality", "clients.ClientName", "clients.ClientEmail", "clients.ClientPhone", "clients.ClientAppID", "clients.ClientStatus", "clients.ClientPicture", "clients.ClientBalance", "clients.ClientDeleted", "clients.ClientGender", "clients.ClientBirthDate", "clients.ClientNationalID", "clients.ClientRewardPoints", "clients.ClientLeftPoints", "clients.ClientRightPoints", "clients.ClientLeftNumber", "clients.ClientRightNumber", "clients.ClientPrivacy", "clients.ClientNameArabic", "clients.ClientCurrentAddress", "clients.ClientIDAddress", "clients.ClientPassport", "clients.ClientNationality", "areas.AreaNameEn", "areas.AreaNameAr", "cities.CityNameEn", "cities.CityNameAr", "clients.created_at", "plannetwork.IDParentClient", "plannetwork.IDReferralClient", "plans.PlanNameEn", "plans.PlanNameAr", "C2.ClientName as ParentName", "C2.ClientPhone as ParentPhone", "C3.ClientName as ReferralName", "C3.ClientPhone as ReferralPhone")->first();
        if (!$Client) {
            return RespondWithBadRequest(1);
        }

        $ClientDocuments = ClientDocument::where("IDClient", $Client->IDClient)->where("ClientDocumentDeleted", 0)->whereIn("ClientDocumentType", ["PASSPORT", "NATIONAL_ID"])->get();
        $ClientGallery = ClientDocument::where("IDClient", $Client->IDClient)->where("ClientDocumentDeleted", 0)->whereIn("ClientDocumentType", ["IMAGE", "VIDEO"])->orderby("ClientDocumentType")->get();
        foreach ($ClientDocuments as $Document) {
            $Document->ClientDocumentPath = ($Document->ClientDocumentPath) ? asset($Document->ClientDocumentPath) : '';
        }
        foreach ($ClientGallery as $Gallery) {
            if ($Gallery->ClientDocumentType == "IMAGE") {
                $Gallery->ClientDocumentPath = ($Gallery->ClientDocumentPath) ? asset($Gallery->ClientDocumentPath) : '';
            }
        }

        $Client->ClientDocuments = $ClientDocuments;
        $Client->ClientGallery = $ClientGallery;

        $Client = ClientResource::collection([$Client])[0];
        $APICode = APICode::where('IDAPICode', 8)->first();
        $Response = array(
            'Success' => true,
            'ApiMsg' => __('apicodes.' . $APICode->IDApiCode),
            'ApiCode' => $APICode->IDApiCode,
            'Response' => $Client,
        );
        return $Response;
    }

    public function ClientStatus(Request $request)
    {
        $Admin = auth('user')->user();
        $IDClient = $request->IDClient;
        $ClientStatus = $request->ClientStatus;
        if (!$IDClient) {
            return RespondWithBadRequest(1);
        }
        if (!$ClientStatus) {
            return RespondWithBadRequest(1);
        }

        $Client = Client::find($IDClient);
        $Desc = "Client status changed from  " . $Client->ClientStatus . " to " . $ClientStatus;
        $Client->ClientStatus = $ClientStatus;
        $Client->save();

        ActionBackLog($Admin->IDUser, $Client->IDClient, "EDIT_CLIENT", $Desc);

        return RespondWithSuccessRequest(8);
    }

    public function ClientBalanceSet(Request $request)
    {
        $Admin = auth('user')->user();
        $IDClient = $request->IDClient;
        $Amount = $request->NewBalance;
        if (!$IDClient) {
            return RespondWithBadRequest(1);
        }
        if (!$Amount) {
            return RespondWithBadRequest(1);
        }

        $Client = Client::find($IDClient);


        if ($Client) {
            $firstClient = Client::orderBy('IDClient', 'asc')->first();
            if ($Client->IDClient === $firstClient->IDClient) {
                $BatchNumber = "#SA" . $IDClient;
                $TimeFormat = new DateTime('now');
                $Time = $TimeFormat->format('H');
                $Time = $Time . $TimeFormat->format('i');
                $BatchNumber = $BatchNumber . $Time;
                $Desc = "Client balance changed from  " . $Client->ClientBalance . " to " . $Client->ClientBalance + $Amount;
                if ($Amount >= 0) {
                    AdjustLedger($Client, $Amount, 0, 0, 0, Null, "ADMIN", "WALLET", "ADJUST", $BatchNumber);
                } else {
                    AdjustLedger($Client, $Amount, 0, 0, 0, Null, "WALLET", "ADMIN", "ADJUST", $BatchNumber);
                }
            } else {
                if ($firstClient->ClientBalance >= $Amount) {
                    // Give Client
                    $BatchNumber = "#SA" . $IDClient;
                    $TimeFormat = new DateTime('now');
                    $Time = $TimeFormat->format('H');
                    $Time = $Time . $TimeFormat->format('i');
                    $BatchNumber = $BatchNumber . $Time;
                    $Desc = "Client balance changed from  " . $Client->ClientBalance . " to " . $Client->ClientBalance + $Amount;
                    if ($Amount >= 0) {
                        AdjustLedger($Client, $Amount, 0, 0, 0, Null, "ADMIN", "WALLET", "ADJUST", $BatchNumber);
                    } else {
                        AdjustLedger($Client, $Amount, 0, 0, 0, Null, "WALLET", "ADMIN", "ADJUST", $BatchNumber);
                    }

                    // Decrease First Client Balance
                    $BatchNumber = "#SA" . $firstClient->IDClient;
                    $TimeFormat = new DateTime('now');
                    $Time = $TimeFormat->format('H');
                    $Time = $Time . $TimeFormat->format('i');
                    $BatchNumber = $BatchNumber . $Time;
                    $Desc = "Client balance changed from  " . $firstClient->ClientBalance . " to " . $firstClient->ClientBalance - $Amount;
                    if ($Amount >= 0) {
                        AdjustLedger($firstClient, -$Amount, 0, 0, 0, Null, "ADMIN", "Client " . $Client->ClientName . " WALLET", "ADJUST", $BatchNumber);
                    } else {
                        AdjustLedger($firstClient, -$Amount, 0, 0, 0, Null, "WALLET", "ADMIN", "ADJUST", $BatchNumber);
                    }
                } else {
                    return RespondWithBadRequest(26);
                }
            }
        } else {
            return RespondWithBadRequest(23);
        }

        $CompanyLedger = new CompanyLedger;
        $CompanyLedger->IDSubCategory = 21;
        $CompanyLedger->CompanyLedgerAmount = $Amount;
        $CompanyLedger->CompanyLedgerDesc = "Amount added to client " . $Client->ClientName;
        $CompanyLedger->CompanyLedgerProcess = "AUTO";
        $CompanyLedger->CompanyLedgerType = "DEBIT";
        $CompanyLedger->save();

        ActionBackLog($Admin->IDUser, $Client->IDClient, "EDIT_CLIENT", $Desc);
        return RespondWithSuccessRequest(8);
    }

    public function ClientRewardPointSet(Request $request)
    {
        $Admin = auth('user')->user();
        $IDClient = $request->IDClient;
        $Amount = $request->NewPoints;
        if (!$IDClient) {
            return RespondWithBadRequest(1);
        }
        if (!$Amount) {
            return RespondWithBadRequest(1);
        }

        $Client = Client::find($IDClient);
        $BatchNumber = "#SA" . $IDClient;
        $TimeFormat = new DateTime('now');
        $Time = $TimeFormat->format('H');
        $Time = $Time . $TimeFormat->format('i');
        $BatchNumber = $BatchNumber . $Time;
        $Desc = "Client points changed from  " . $Client->ClientRewardPoints . " to " . $Client->ClientRewardPoints + $Amount;
        if ($Amount >= 0) {
            AdjustLedger($Client, 0, $Amount, 0, 0, Null, "ADMIN", "WALLET", "ADJUST", $BatchNumber);
        } else {
            AdjustLedger($Client, 0, $Amount, 0, 0, Null, "WALLET", "ADMIN", "ADJUST", $BatchNumber);
        }

        ActionBackLog($Admin->IDUser, $Client->IDClient, "EDIT_CLIENT", $Desc);
        return RespondWithSuccessRequest(8);
    }

    public function BalanceTransfer(Request $request, BalanceTransfer $BalanceTransfer)
    {
        $IDClient = $request->IDClient;
        $IDPage = $request->IDPage;
        $TransferStatus = $request->TransferStatus;
        $StartDate = $request->StartDate;
        $EndDate = $request->EndDate;
        if (!$IDPage) {
            $IDPage = 0;
        } else {
            $IDPage = ($request->IDPage - 1) * 20;
        }

        if (!$IDClient) {
            return RespondWithBadRequest(1);
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
        foreach ($BalanceTransfer as $Transfer) {
            $MyClient = 0;
            if ($IDClient == $Transfer->IDSender) {
                $MyClient = 1;
            }
            $Transfer->MyClient = $MyClient;
        }

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

    public function ClientEvents(Request $request)
    {
        $User = auth('user')->user();
        $UserLanguage = AdminLanguage($User->UserLanguage);
        $EventTitle = "EventTitle" . $UserLanguage;

        $IDClient = $request->IDClient;
        $IDPage = $request->IDPage;
        if (!$IDPage) {
            $IDPage = 0;
        } else {
            $IDPage = ($request->IDPage - 1) * 20;
        }
        if (!$IDClient) {
            return RespondWithBadRequest(1);
        }

        $Events = EventAttendee::leftjoin("events", "events.IDEvent", "eventattendees.IDEvent")->where("eventattendees.IDClient", $IDClient)->orderby("eventattendees.IDEventAttendee", "DESC")->select("events.EventTitleEn", "events.EventTitleAr", "events.EventStartTime", "events.EventPrice", "eventattendees.EventAttendeePaidAmount", "eventattendees.EventAttendeeStatus", "eventattendees.created_at");
        $EventNumber = EventAttendee::where("IDClient", $IDClient)->where("EventAttendeeStatus", "PAID")->count();
        $Pages = ceil($Events->count() / 20);
        $Events = $Events->skip($IDPage)->take(20)->get();
        foreach ($Events as $Event) {
            $Event->EventTitle = $Event->$EventTitle;
            unset($Event["EventTitleEn"]);
            unset($Event["EventTitleAr"]);
        }

        $Response = array("Events" => $Events, "EventNumber" => $EventNumber, "Pages" => $Pages);

        $APICode = APICode::where('IDAPICode', 8)->first();
        $Response = array(
            'Success' => true,
            'ApiMsg' => __('apicodes.' . $APICode->IDApiCode),
            'ApiCode' => $APICode->IDApiCode,
            'Response' => $Response
        );
        return $Response;
    }

    public function ClientTools(Request $request)
    {
        $User = auth('user')->user();
        $UserLanguage = AdminLanguage($User->UserLanguage);
        $ToolTitle = "ToolTitle" . $UserLanguage;

        $IDClient = $request->IDClient;
        $IDPage = $request->IDPage;
        if (!$IDPage) {
            $IDPage = 0;
        } else {
            $IDPage = ($request->IDPage - 1) * 20;
        }
        if (!$IDClient) {
            return RespondWithBadRequest(1);
        }

        $Tools = ClientTool::leftjoin("tools", "tools.IDTool", "clienttools.IDTool")->where("clienttools.IDClient", $IDClient)->orderby("clienttools.IDClientTool", "DESC")->select("tools.ToolTitleEn", "tools.ToolTitleAr", "tools.ToolType", "tools.ToolPrice", "clienttools.ClientToolPrice", "clienttools.ClientToolDownloaded", "clienttools.created_at");
        $ToolNumber = $Tools->count();
        $Pages = ceil($Tools->count() / 20);
        $Tools = $Tools->skip($IDPage)->take(20)->get();
        foreach ($Tools as $Tool) {
            $Tool->ToolTitle = $Tool->$ToolTitle;
            unset($Tool["ToolTitleEn"]);
            unset($Tool["ToolTitleAr"]);
        }

        $Response = array("Tools" => $Tools, "ToolNumber" => $ToolNumber, "Pages" => $Pages);

        $APICode = APICode::where('IDAPICode', 8)->first();
        $Response = array(
            'Success' => true,
            'ApiMsg' => __('apicodes.' . $APICode->IDApiCode),
            'ApiCode' => $APICode->IDApiCode,
            'Response' => $Response
        );
        return $Response;
    }

    public function ClientBrandProducts(Request $request)
    {
        $User = auth('user')->user();
        $UserLanguage = AdminLanguage($User->UserLanguage);
        $BrandProductTitle = "BrandProductTitle" . $UserLanguage;
        $BrandName = "BrandName" . $UserLanguage;

        $IDClient = $request->IDClient;
        $IDPage = $request->IDPage;
        if (!$IDPage) {
            $IDPage = 0;
        } else {
            $IDPage = ($request->IDPage - 1) * 20;
        }
        if (!$IDClient) {
            return RespondWithBadRequest(1);
        }

        $ClientBrandProducts = ClientBrandProduct::leftjoin("brandproducts", "brandproducts.IDBrandProduct", "clientbrandproducts.IDBrandProduct")->leftjoin("brands", "brands.IDBrand", "brandproducts.IDBrand")->where("clientbrandproducts.IDClient", $IDClient)->orderby("clientbrandproducts.IDClientBrandProduct", "DESC")->select("brandproducts.BrandProductTitleEn", "brandproducts.BrandProductTitleAr", "brandproducts.BrandProductPrice", "brands.BrandNameEn", "brands.BrandNameAr", "clientbrandproducts.ClientBrandProductSerial", "clientbrandproducts.ClientBrandProductStatus", "clientbrandproducts.created_at");
        $ProductNumber = ClientBrandProduct::where("IDClient", $IDClient)->where("ClientBrandProductStatus", "USED")->count();
        $Pages = ceil($ClientBrandProducts->count() / 20);
        $ClientBrandProducts = $ClientBrandProducts->skip($IDPage)->take(20)->get();
        foreach ($ClientBrandProducts as $Product) {
            $Product->BrandProductTitle = $Product->$BrandProductTitle;
            $Product->BrandName = $Product->$BrandName;
            unset($Product["BrandNameEn"]);
            unset($Product["BrandNameAr"]);
            unset($Product["BrandProductTitleEn"]);
            unset($Product["BrandProductTitleAr"]);
        }

        $MoneySaved = 0;
        $UsedProducts = ClientBrandProduct::leftjoin("brandproducts", "brandproducts.IDBrandProduct", "clientbrandproducts.IDBrandProduct")->where("clientbrandproducts.IDClient", $IDClient)->where("clientbrandproducts.ClientBrandProductStatus", "USED")->select("brandproducts.BrandProductDiscountType", "brandproducts.BrandProductPrice", "brandproducts.BrandProductDiscount")->get();
        foreach ($UsedProducts as $Product) {
            if ($Product->BrandProductDiscountType == "VALUE") {
                $MoneySaved = $MoneySaved + $Product->BrandProductDiscount;
            } else {
                $MoneySaved = $MoneySaved + ($Product->BrandProductDiscount * $Product->BrandProductPrice);
            }
        }

        $Response = array("ClientBrandProducts" => $ClientBrandProducts, "ProductNumber" => $ProductNumber, "MoneySaved" => $MoneySaved, "Pages" => $Pages);

        $APICode = APICode::where('IDAPICode', 8)->first();
        $Response = array(
            'Success' => true,
            'ApiMsg' => __('apicodes.' . $APICode->IDApiCode),
            'ApiCode' => $APICode->IDApiCode,
            'Response' => $Response
        );
        return $Response;
    }

    public function ClientPositionUpdate(Request $request)
    {
        $Admin = auth('user')->user();

        if (!$Admin) {
            return RespondWithBadRequest(1);
        }

        $IDClient = $request->IDClient;
        $IDPosition = $request->IDPosition;

        if (!$IDClient) {
            return RespondWithBadRequest(1);
        }

        if (!$IDPosition) {
            return RespondWithBadRequest(1);
        }

        $Position = Position::find($IDPosition);

        $Client = Client::find($IDClient);
        $Desc = $Position->PositionTitleEn;
        $Client->IDPosition = $IDPosition;
        $Client->save();

        ActionBackLog($Admin->IDUser, $Client->IDClient, "EDIT_CLIENT_POSITION", $Desc);
        return RespondWithSuccessRequest(8);
    }

    public function ClientPositionLog(Request $request)
    {
       $User = auth('user')->user();
        $IDClient = $request->IDClient;
        
        $Client = Client::find($IDClient);
        $clientPositionLogs = ActionBackLog::where('IDLink', $IDClient)
            ->where("ActionBackLogType", "EDIT_CLIENT_POSITION")
            ->get();
        
        $newObject = [
            'Position' => 'Networker',
            'Date' => $Client->created_at->format('Y-m-d'),
        ];
        
        if ($clientPositionLogs->isEmpty()) {
            $clientPositionLogs = collect([$newObject]);
        } else {
            $newObject = (object) $newObject;
            $clientPositionLogs->prepend($newObject);
        }
        
        $clientPositionLogsCollection = $clientPositionLogs->map(function ($item) {
            return (object) $item; // Convert each item to an object
        });
        
        $APICode = APICode::where('IDAPICode', 8)->first();
        
        $Response = [
            'Success' => true,
            'ApiMsg' => __('apicodes.' . $APICode->IDApiCode),
            'ApiCode' => $APICode->IDApiCode,
            'Response' => ClientPositionLog::collection($clientPositionLogsCollection)
        ];
        
        return $Response;
    }
    public function ClientLedger(Request $request, ClientLedger $ClientLedger)
    {
        $IDPage = $request->IDPage;
        $IDClient = $request->IDClient;
        $StartDate = $request->StartDate;
        $EndDate = $request->EndDate;
        $ClientLedgerSource = $request->ClientLedgerSource;
        $ClientLedgerType = $request->ClientLedgerType;
        if (!$IDClient) {
            return RespondWithBadRequest(1);
        }
        if (!$IDPage) {
            $IDPage = 0;
        } else {
            $IDPage = ($request->IDPage - 1) * 20;
        }

        $ClientLedger = $ClientLedger->where("IDClient", $IDClient);
        if ($StartDate) {
            $ClientLedger = $ClientLedger->where("created_at", ">=", $StartDate);
        }
        if ($EndDate) {
            $ClientLedger = $ClientLedger->where("created_at", "<=", $EndDate);
        }
        if ($ClientLedgerType) {
            $ClientLedger = $ClientLedger->where("created_at", $ClientLedgerType);
        }
        if ($ClientLedgerType) {
            $ClientLedger = $ClientLedger->where(function ($query) use ($ClientLedgerType) {
                $query->where('ClientLedgerSource', $ClientLedgerType)
                    ->orwhere('ClientLedgerDestination', $ClientLedgerType);
            });
        }

        $Pages = ceil($ClientLedger->count() / 20);
        $ClientLedger = $ClientLedger->orderby("IDClientLedger", "DESC")->skip($IDPage)->take(20)->get();
        $ClientLedger = ClientLedgerResource::collection($ClientLedger);
        $Response = array("ClientLedger" => $ClientLedger, "Pages" => $Pages);

        $APICode = APICode::where('IDAPICode', 8)->first();
        $Response = array(
            'Success' => true,
            'ApiMsg' => __('apicodes.' . $APICode->IDApiCode),
            'ApiCode' => $APICode->IDApiCode,
            'Response' => $Response,
        );
        return $Response;
    }

    public function PositionList(Request $request, Position $Positions)
    {
        $IDPage = $request->IDPage;
        $SearchKey = $request->SearchKey;
        $PositionStatus = $request->PositionStatus;
        if (!$IDPage) {
            $IDPage = 0;
        } else {
            $IDPage = ($request->IDPage - 1) * 20;
        }

        $Positions = $Positions->where("PositionStatus", "<>", "DELETED");
        if ($SearchKey) {
            $Positions = $Positions->where(function ($query) use ($SearchKey) {
                $query->where('PositionTitleEn', 'like', '%' . $SearchKey . '%')
                    ->orwhere('PositionTitleAr', 'like', '%' . $SearchKey . '%');
            });
        }
        if ($PositionStatus) {
            $Positions = $Positions->where("PositionStatus", $PositionStatus);
        }

        $Pages = ceil($Positions->count() / 20);
        $Positions = $Positions->orderby("IDPosition", "DESC")->skip($IDPage)->take(20)->get();
        $Positions = PositionResource::collection($Positions);
        $Response = array("Positions" => $Positions, "Pages" => $Pages);

        $APICode = APICode::where('IDAPICode', 8)->first();
        $Response = array(
            'Success' => true,
            'ApiMsg' => __('apicodes.' . $APICode->IDApiCode),
            'ApiCode' => $APICode->IDApiCode,
            'Response' => $Response,
        );
        return $Response;
    }

    public function PositionStatus(Request $request)
    {
        $Admin = auth('user')->user();
        $IDPosition = $request->IDPosition;
        $PositionStatus = $request->PositionStatus;
        if (!$IDPosition) {
            return RespondWithBadRequest(1);
        }
        if (!$PositionStatus) {
            return RespondWithBadRequest(1);
        }

        $Position = Position::find($IDPosition);
        $Desc = "Position status changed from " . $Position->PositionStatus . " to " . $PositionStatus;
        $Position->PositionStatus = $PositionStatus;
        $Position->save();

        ActionBackLog($Admin->IDUser, $Position->IDPosition, "EDIT_CLIENT_POSITION", $Desc);
        return RespondWithSuccessRequest(8);
    }

    public function PositionAdd(Request $request)
    {
        $Admin = auth('user')->user();
        $PositionTitleEn = $request->PositionTitleEn;
        $PositionTitleAr = $request->PositionTitleAr;
        $PositionReferralNumber = $request->PositionReferralNumber;
        $PositionReferralInterval = $request->PositionReferralInterval;
        $PositionLeftNumber = $request->PositionLeftNumber;
        $PositionRightNumber = $request->PositionRightNumber;
        $PositionAllNumber = $request->PositionAllNumber;
        $PositionNumberInterval = $request->PositionNumberInterval;
        $PositionLeftPoints = $request->PositionLeftPoints;
        $PositionRightPoints = $request->PositionRightPoints;
        $PositionAllPoints = $request->PositionAllPoints;
        $PositionPointInterval = $request->PositionPointInterval;
        $PositionVisits = $request->PositionVisits;
        $PositionVisitInterval = $request->PositionVisitInterval;

        $PositionUniqueVisitsInterval = $request->PositionUniqueVisitsInterval;
        $IsPositionUniqueVisits = $request->IsPositionUniqueVisits;

        $PositionChequeValue = $request->PositionChequeValue;
        $PositionChequeInterval = $request->PositionChequeInterval;

        if (!$PositionTitleEn) {
            return RespondWithBadRequest(1);
        }
        if (!$PositionTitleAr) {
            return RespondWithBadRequest(1);
        }
        if (!$PositionReferralNumber) {
            $PositionReferralNumber = 0;
        }
        if (!$PositionReferralInterval) {
            $PositionReferralInterval = 0;
        }
        if (!$PositionLeftNumber) {
            $PositionLeftNumber = 0;
        }
        if (!$PositionRightNumber) {
            $PositionRightNumber = 0;
        }
        if (!$PositionAllNumber) {
            $PositionAllNumber = 0;
        }
        if (!$PositionNumberInterval) {
            $PositionNumberInterval = 0;
        }
        if (!$PositionLeftPoints) {
            $PositionLeftPoints = 0;
        }
        if (!$PositionRightPoints) {
            $PositionRightPoints = 0;
        }
        if (!$PositionAllPoints) {
            $PositionAllPoints = 0;
        }
        if (!$PositionPointInterval) {
            $PositionPointInterval = 0;
        }
        if (!$PositionVisits) {
            $PositionVisits = 0;
        }
        if (!$IsPositionUniqueVisits) {
            $PositionUniqueVisitsInterval = 0;
        }
        if (!$PositionChequeValue) {
            $PositionChequeValue = 0;
        }
        if (!$PositionChequeInterval) {
            $PositionChequeInterval = 0;
        }

        $Position = Position::where("PositionTitleEn", $PositionTitleEn)->orwhere("PositionTitleAr", $PositionTitleAr)->first();
        if ($Position) {
            return RespondWithBadRequest(18);
        }

        $Position = new Position;
        $Position->PositionTitleEn = $PositionTitleEn;
        $Position->PositionTitleAr = $PositionTitleAr;
        $Position->PositionReferralNumber = $PositionReferralNumber;
        $Position->PositionReferralInterval = $PositionReferralInterval;


        if ($request->PositionBalancePerson === 'Total') {
            $Position->PositionLeftNumber = 0;
            $Position->PositionRightNumber = 0;
            $Position->PositionAllNumber = $PositionAllNumber;
        }
        if ($request->PositionBalancePerson === 'Balance') {
            $Position->PositionLeftNumber = $PositionLeftNumber;
            $Position->PositionRightNumber = $PositionRightNumber;
            $Position->PositionAllNumber = 0;
        }


        if ($request->PositionBalancePoints == 'Total') {
            $Position->PositionLeftPoints = 0;
            $Position->PositionRightPoints = 0;
            $Position->PositionAllPoints = $PositionAllPoints;
        }
        if ($request->PositionBalancePoints === 'Balance') {
            $Position->PositionLeftPoints = $PositionLeftPoints;
            $Position->PositionRightPoints = $PositionRightPoints;
            $Position->PositionAllPoints = 0;
        }



        $Position->PositionNumberInterval = $PositionNumberInterval;
        $Position->PositionPointInterval = $PositionPointInterval;
        $Position->PositionVisits = $PositionVisits;
        $Position->PositionVisitInterval = $PositionVisitInterval;
        $Position->IsPositionUniqueVisits = $IsPositionUniqueVisits;
        $Position->PositionUniqueVisitsInterval = $PositionUniqueVisitsInterval;
        $Position->PositionChequeValue = $PositionChequeValue;
        $Position->PositionChequeInterval = $PositionChequeInterval;
        $Position->PositionStatus = "PENDING";
        $Position->save();

        $Desc = "Position " . $PositionTitleEn . " was added";
        ActionBackLog($Admin->IDUser, $Position->IDPosition, "ADD_POSITION", $Desc);

        $APICode = APICode::where('IDAPICode', 8)->first();
        $Response = array(
            'Success' => true,
            'ApiMsg' => __('apicodes.' . $APICode->IDApiCode),
            'ApiCode' => $APICode->IDApiCode,
            'Response' => $Position->IDPosition,
        );
        return $Response;
    }

    public function PositionEditPage($IDPosition)
    {
        $Position = Position::find($IDPosition);
        if (!$Position) {
            return RespondWithBadRequest(1);
        }

        $APICode = APICode::where('IDAPICode', 8)->first();
        $Response = array(
            'Success' => true,
            'ApiMsg' => __('apicodes.' . $APICode->IDApiCode),
            'ApiCode' => $APICode->IDApiCode,
            'Response' => $Position,
        );
        return $Response;
    }

    public function PositionEdit(Request $request)
    {
        $Admin = auth('user')->user();
        $IDPosition = $request->IDPosition;
        $PositionTitleEn = $request->PositionTitleEn;
        $PositionTitleAr = $request->PositionTitleAr;
        $PositionReferralNumber = $request->PositionReferralNumber;
        $PositionReferralInterval = $request->PositionReferralInterval;

        $PositionNumberInterval = $request->PositionNumberInterval;

        $PositionLeftPoints = $request->PositionLeftPoints;
        $PositionRightPoints = $request->PositionRightPoints;
        $PositionAllPoints = $request->PositionAllPoints;

        $PositionPointInterval = $request->PositionPointInterval;
        $PositionVisits = $request->PositionVisits;
        $PositionVisitInterval = $request->PositionVisitInterval;

        $IsPositionUniqueVisits = $request->IsPositionUniqueVisits;
        $PositionUniqueVisitsInterval = $request->PositionUniqueVisitsInterval;

        $PositionChequeValue = $request->PositionChequeValue;
        $PositionChequeInterval = $request->PositionChequeInterval;
        $PositionAllNumber = $request->PositionAllNumber;
        $PositionLeftNumber = $request->PositionLeftNumber;
        $PositionRightNumber = $request->PositionRightNumber;
        $Desc = "";


        $Position = Position::find($IDPosition);
        if (!$Position) {
            return RespondWithBadRequest(1);
        }
        if ($PositionTitleEn) {
            $PositionRow = Position::where("PositionTitleEn", $PositionTitleEn)->where("IDPosition", "<>", $IDPosition)->first();
            if ($PositionRow) {
                return RespondWithBadRequest(18);
            }
            $Desc = "Position english title changed from " . $Position->PositionTitleEn . " to " . $PositionTitleEn;
            $Position->PositionTitleEn = $PositionTitleEn;
        }
        if ($PositionTitleAr) {
            $PositionRow = Position::where("PositionTitleAr", $PositionTitleAr)->where("IDPosition", "<>", $IDPosition)->first();
            if ($PositionRow) {
                return RespondWithBadRequest(18);
            }
            $Desc = $Desc . ", Position arabic title changed from " . $Position->PositionTitleEn . " to " . $PositionTitleEn;
            $Position->PositionTitleAr = $PositionTitleAr;
        }
        if ($PositionReferralNumber) {
            $Desc = $Desc . ", Position arabic title changed from " . $Position->PositionReferralNumber . " to " . $PositionReferralNumber;
            $Position->PositionReferralNumber = $PositionReferralNumber;
        }
        if ($PositionReferralInterval) {
            $Desc = $Desc . ", Position referral interval changed from " . $Position->PositionReferralInterval . " to " . $PositionReferralInterval;
            $Position->PositionReferralInterval = $PositionReferralInterval;
        }

        if ($request->PositionBalancePerson === 'Total') {

            $Desc = $Desc . ", Position left number changed from " . $Position->PositionLeftNumber . " to " . 0;
            $Position->PositionLeftNumber = 0;

            $Desc = $Desc . ", Position right number changed from " . $Position->PositionRightNumber . " to " . 0;
            $Position->PositionRightNumber = 0;

            if ($PositionAllNumber) {
                $Desc = $Desc . ", Position all number changed from " . $Position->PositionAllNumber . " to " . $PositionAllNumber;
                $Position->PositionAllNumber = $PositionAllNumber;
            }
        }
        if ($request->PositionBalancePerson === 'Balance') {
            $Desc = $Desc . ", Position all number changed from " . $Position->PositionAllNumber . " to " . 0;
            $Position->PositionAllNumber = 0;

            if ($PositionRightNumber) {
                $Desc = $Desc . ", Position right number changed from " . $Position->PositionRightNumber . " to " . $PositionRightNumber;
                $Position->PositionRightNumber = $PositionRightNumber;
            }
            if ($PositionLeftNumber) {
                $Desc = $Desc . ", Position right number changed from " . $Position->PositionLeftNumber . " to " . $PositionLeftNumber;
                $Position->PositionLeftNumber = $PositionLeftNumber;
            }
        }
        if ($request->PositionBalancePoints == 'Total') {

            $Desc = $Desc . ", Position left points changed from " . $Position->PositionLeftPoints . " to " . 0;
            $Position->PositionLeftPoints = 0;
            $Desc = $Desc . ", Position right points changed from " . $Position->PositionRightPoints . " to " . 0;
            $Position->PositionRightPoints = 0;

            if ($PositionAllPoints) {
                $Desc = $Desc . ", Position all points changed from " . $Position->PositionAllPoints . " to " . $PositionAllPoints;
                $Position->PositionAllPoints = $PositionAllPoints;
            }
        }
        if ($request->PositionBalancePoints === 'Balance') {

            $Desc = $Desc . ", Position all points changed from " . $Position->PositionAllPoints . " to " . 0;
            $Position->PositionAllPoints = 0;

            if ($PositionLeftPoints) {
                $Desc = $Desc . ", Position left points changed from " . $Position->PositionLeftPoints . " to " . $PositionLeftPoints;
                $Position->PositionLeftPoints = $PositionLeftPoints;
            }
            if ($PositionRightPoints) {
                $Desc = $Desc . ", Position right points changed from " . $Position->PositionRightPoints . " to " . $PositionRightPoints;
                $Position->PositionRightPoints = $PositionRightPoints;
            }
        }



        if ($PositionNumberInterval) {
            $Desc = $Desc . ", Position number interval changed from " . $Position->PositionNumberInterval . " to " . $PositionNumberInterval;
            $Position->PositionNumberInterval = $PositionNumberInterval;
        }
        if ($PositionVisits) {
            $Desc = $Desc . ", Position visits changed from " . $Position->PositionVisits . " to " . $PositionVisits;
            $Position->PositionVisits = $PositionVisits;
        }
        if ($PositionVisitInterval) {
            $Desc = $Desc . ", Position visit interval changed from " . $Position->PositionVisitInterval . " to " . $PositionVisitInterval;
            $Position->PositionVisitInterval = $PositionVisitInterval;
        }
        if ($IsPositionUniqueVisits) {
            $Desc = $Desc . ", Position unique visit interval changed from " . $Position->PositionUniqueVisitsInterval . " to " . $PositionUniqueVisitsInterval;
            $Position->PositionUniqueVisitsInterval = $PositionUniqueVisitsInterval;
            $Position->IsPositionUniqueVisits = $IsPositionUniqueVisits;
        } else {
            $Desc = $Desc . ", Position unique visit interval changed from " . $Position->PositionUniqueVisitInterval . " to " . 0;
            $Position->PositionUniqueVisitsInterval = 0;
        }

        if ($PositionPointInterval) {
            $Desc = $Desc . ", Position point interval changed from " . $Position->PositionPointInterval . " to " . $PositionPointInterval;
            $Position->PositionPointInterval = $PositionPointInterval;
        }
        if ($PositionChequeValue) {
            $Desc = $Desc . ", Position cheque value changed from " . $Position->PositionChequeValue . " to " . $PositionChequeValue;
            $Position->PositionChequeValue = $PositionChequeValue;
        }
        if ($PositionChequeInterval) {
            $Desc = $Desc . ", Position cheque interval changed from " . $Position->PositionChequeInterval . " to " . $PositionChequeInterval;
            $Position->PositionChequeInterval = $PositionChequeInterval;
        }

        $Position->PositionStatus = "PENDING";

        $Position->save();

        ActionBackLog($Admin->IDUser, $Position->IDPosition, "EDIT_POSITION", $Desc);
        return RespondWithSuccessRequest(8);
    }

    public function PositionAjax(Request $request)
    {

        $Positions = Position::where("PositionStatus", "ACTIVE")->get();
        $Positions = PositionResource::collection($Positions);

        $APICode = APICode::where('IDAPICode', 8)->first();
        $Response = array(
            'Success' => true,
            'ApiMsg' => __('apicodes.' . $APICode->IDApiCode),
            'ApiCode' => $APICode->IDApiCode,
            'Response' => $Positions,
        );
        return $Response;
    }

    public function PositionBrandList(Request $request)
    {
        $User = auth('user')->user();
        $IDPosition = $request->IDPosition;
        $IDCategory = $request->IDCategory;
        $IDSubCategory = $request->IDSubCategory;
        $UserLanguage = AdminLanguage($User->UserLanguage);
        $BrandName = "BrandName" . $UserLanguage;
        if (!$IDPosition) {
            return RespondWithBadRequest(1);
        }

        if (!$IDCategory && !$IDSubCategory) {
            $Brands = Brand::where("BrandStatus", "ACTIVE")->select("IDBrand", "BrandNameEn", "BrandNameAr", "BrandLogo")->get();
        }
        if ($IDCategory && !$IDSubCategory) {
            $Brands = Brand::leftjoin("brandproducts", "brandproducts.IDBrand", "brands.IDBrand")->leftjoin("subcategories", "subcategories.IDSubCategory", "brandproducts.IDSubCategory")->where("brands.BrandStatus", "ACTIVE")->where("brandproducts.BrandProductStatus", "ACTIVE")->where("subcategories.IDCategory", $IDCategory)->select("brands.IDBrand", "brands.BrandNameEn", "brands.BrandNameAr", "brands.BrandLogo")->groupby("brands.IDBrand")->get();
        }
        if ($IDSubCategory) {
            $Brands = Brand::leftjoin("brandproducts", "brandproducts.IDBrand", "brands.IDBrand")->where("brands.BrandStatus", "ACTIVE")->where("brandproducts.IDSubCategory", $IDSubCategory)->where("brandproducts.BrandProductStatus", "ACTIVE")->select("brands.IDBrand", "brands.BrandNameEn", "brands.BrandNameAr", "brands.BrandLogo")->groupby("brands.IDBrand")->get();
        }
        foreach ($Brands as $Brand) {
            $PositionBrand = PositionBrand::where("IDPosition", $IDPosition)->where("IDBrand", $Brand->IDBrand)->select("PositionBrandDeleted", "PositionBrandVisitNumber")->first();
            if ($PositionBrand) {
                $Brand->PositionBrandDeleted = $PositionBrand->PositionBrandDeleted;
                $Brand->PositionBrandVisitNumber = $PositionBrand->PositionBrandVisitNumber;
            } else {
                $Brand->PositionBrandDeleted = 1;
                $Brand->PositionBrandVisitNumber = 0;
            }
            $Brand->BrandLogo = ($Brand->BrandLogo) ? asset($Brand->BrandLogo) : '';
            $Brand->BrandName = $Brand->$BrandName;
            unset($Brand["BrandNameEn"]);
            unset($Brand["BrandNameAr"]);
        }

        $APICode = APICode::where('IDAPICode', 8)->first();
        $Response = array(
            'Success' => true,
            'ApiMsg' => __('apicodes.' . $APICode->IDApiCode),
            'ApiCode' => $APICode->IDApiCode,
            'Response' => $Brands,
        );
        return $Response;
    }

    public function PositionBrandStatus(Request $request)
    {
        $Admin = auth('user')->user();
        $IDBrand = $request->IDBrand;
        $IDPosition = $request->IDPosition;
        $PositionStatus = $request->PositionStatus;
        $PositionBrandVisitNumber = $request->PositionBrandVisitNumber;
        $Desc = "";
        if (!$IDBrand) {
            return RespondWithBadRequest(1);
        }
        if (!$IDPosition) {
            return RespondWithBadRequest(1);
        }

        $PositionBrand = PositionBrand::where("IDBrand", $IDBrand)->where("IDPosition", $IDPosition)->first();
        if ($PositionBrand) {
            if ($PositionStatus) {
                $PositionBrand->PositionBrandDeleted = !$PositionBrand->PositionBrandDeleted;
            }
            if ($PositionBrandVisitNumber) {
                $PositionBrand->PositionBrandVisitNumber = $PositionBrandVisitNumber;
            }
            if ($PositionBrandVisitNumber == 0) {
                $PositionBrand->PositionBrandVisitNumber = 0;
            }
            $Desc = "adjusted Brand status with visit number " . $PositionBrandVisitNumber;
        } else {
            $PositionBrand = new PositionBrand;
            $PositionBrand->IDBrand = $IDBrand;
            $PositionBrand->IDPosition = $IDPosition;
            if (!$PositionBrandVisitNumber) {
                $PositionBrand->PositionBrandVisitNumber = 0;
            } else {
                $PositionBrand->PositionBrandVisitNumber = $PositionBrandVisitNumber;
            }
            $Desc = "Added new Brand with visit number " . $PositionBrandVisitNumber;
        }

        $PositionBrand->save();

        ActionBackLog($Admin->IDUser, $PositionBrand->IDPosition, "EDIT_POSITION", $Desc);
        return RespondWithSuccessRequest(8);
    }

    public function PlanList(Request $request, Plan $Plans)
    {
        $IDPage = $request->IDPage;
        $SearchKey = $request->SearchKey;
        $PlanStatus = $request->PlanStatus;
        if (!$IDPage) {
            $IDPage = 0;
        } else {
            $IDPage = ($request->IDPage - 1) * 20;
        }

        if ($SearchKey) {
            $Plans = $Plans->where(function ($query) use ($SearchKey) {
                $query->where('PlanNameEn', 'like', '%' . $SearchKey . '%')
                    ->orwhere('PlanNameAr', 'like', '%' . $SearchKey . '%')
                    ->orwhere('PlanDescEn', 'like', '%' . $SearchKey . '%')
                    ->orwhere('PlanDescAr', 'like', '%' . $SearchKey . '%');
            });
        }
        if ($PlanStatus) {
            $Plans = $Plans->where("PlanStatus", $PlanStatus);
        }

        $Pages = ceil($Plans->count() / 20);
        $Plans = $Plans->orderby("IDPlan", "DESC")->skip($IDPage)->take(20)->get();
        $Plans = PlanResource::collection($Plans);
        $Response = array("Plans" => $Plans, "Pages" => $Pages);

        $APICode = APICode::where('IDAPICode', 8)->first();
        $Response = array(
            'Success' => true,
            'ApiMsg' => __('apicodes.' . $APICode->IDApiCode),
            'ApiCode' => $APICode->IDApiCode,
            'Response' => $Response,
        );
        return $Response;
    }

    public function PlanStatus(Request $request)
    {
        $Admin = auth('user')->user();
        $IDPlan = $request->IDPlan;
        $PlanStatus = $request->PlanStatus;
        if (!$IDPlan) {
            return RespondWithBadRequest(1);
        }
        if (!$PlanStatus) {
            return RespondWithBadRequest(1);
        }

        $Plan = Plan::find($IDPlan);
        $Desc = "Plan status changed from " . $Plan->PlanStatus . " to " . $PlanStatus;
        $Plan->PlanStatus = $PlanStatus;
        $Plan->save();

        ActionBackLog($Admin->IDUser, $Plan->IDPlan, "EDIT_PLAN", $Desc);
        return RespondWithSuccessRequest(8);
    }

    public function PlanAdd(Request $request)
    {
        $Admin = auth('user')->user();
        $PlanNameEn = $request->PlanNameEn;
        $PlanNameAr = $request->PlanNameAr;
        $PlanDescEn = $request->PlanDescEn;
        $PlanDescAr = $request->PlanDescAr;
        $LeftBalanceNumber = $request->LeftBalanceNumber;
        $RightBalanceNumber = $request->RightBalanceNumber;
        $ChequeValue = $request->ChequeValue;
        $LeftMaxOutNumber = $request->LeftMaxOutNumber;
        $RightMaxOutNumber = $request->RightMaxOutNumber;
        $ChequeMaxOut = $request->ChequeMaxOut;
        $ChequeEarnDay = $request->ChequeEarnDay;

        if (!$PlanNameEn) {
            return RespondWithBadRequest(1);
        }
        if (!$PlanNameAr) {
            return RespondWithBadRequest(1);
        }
        if (!$LeftBalanceNumber) {
            return RespondWithBadRequest(1);
        }
        if (!$RightBalanceNumber) {
            return RespondWithBadRequest(1);
        }
        if (!$ChequeValue) {
            return RespondWithBadRequest(1);
        }
        if (!$ChequeEarnDay) {
            return RespondWithBadRequest(1);
        }

        $Plan = new Plan;
        $Plan->PlanNameEn = $PlanNameEn;
        $Plan->PlanNameAr = $PlanNameAr;
        $Plan->PlanDescEn = $PlanDescEn;
        $Plan->PlanDescAr = $PlanDescAr;
        $Plan->LeftBalanceNumber = $LeftBalanceNumber;
        $Plan->RightBalanceNumber = $RightBalanceNumber;
        $Plan->ChequeValue = $ChequeValue;
        $Plan->ChequeMaxOut = $ChequeMaxOut;
        $Plan->LeftMaxOutNumber = $LeftMaxOutNumber;
        $Plan->RightMaxOutNumber = $RightMaxOutNumber;
        $Plan->ChequeEarnDay = $ChequeEarnDay;
        $Plan->PlanStatus = "PENDING";
        $Plan->save();

        $Desc = "Plan " . $PlanNameEn . " was added";
        ActionBackLog($Admin->IDUser, $Plan->IDPlan, "ADD_PLAN", $Desc);
        return RespondWithSuccessRequest(8);
    }

    public function PlanEditPage($IDPlan)
    {
        $Plan = Plan::find($IDPlan);
        if (!$Plan) {
            return RespondWithBadRequest(1);
        }

        $APICode = APICode::where('IDAPICode', 8)->first();
        $Response = array(
            'Success' => true,
            'ApiMsg' => __('apicodes.' . $APICode->IDApiCode),
            'ApiCode' => $APICode->IDApiCode,
            'Response' => $Plan,
        );
        return $Response;
    }

    public function PlanEdit(Request $request)
    {
        $Admin = auth('user')->user();
        $IDPlan = $request->IDPlan;
        $PlanNameEn = $request->PlanNameEn;
        $PlanNameAr = $request->PlanNameAr;
        $PlanDescEn = $request->PlanDescEn;
        $PlanDescAr = $request->PlanDescAr;
        $ChequeValue = $request->ChequeValue;
        $ChequeMaxOut = $request->ChequeMaxOut;
        $ChequeEarnDay = $request->ChequeEarnDay;
        $Desc = "";

        $Plan = Plan::find($IDPlan);
        if (!$Plan) {
            return RespondWithBadRequest(1);
        }

        if ($PlanNameEn) {
            $Desc = "Plan english name was changed from " . $Plan->PlanNameEn . " to " . $PlanNameEn;
            $Plan->PlanNameEn = $PlanNameEn;
        }
        if ($PlanNameAr) {
            $Desc = $Desc . ", Plan arabic name was changed from " . $Plan->PlanNameEn . " to " . $PlanNameEn;
            $Plan->PlanNameAr = $PlanNameAr;
        }
        if ($PlanDescEn) {
            $Desc = $Desc . ", Plan english desc was changed from " . $Plan->PlanDescEn . " to " . $PlanDescEn;
            $Plan->PlanDescEn = $PlanDescEn;
        }
        if ($PlanDescAr) {
            $Desc = $Desc . ", Plan arabic desc was changed from " . $Plan->PlanDescAr . " to " . $PlanDescAr;
            $Plan->PlanDescAr = $PlanDescAr;
        }
        if ($ChequeValue) {
            $Desc = $Desc . ", Plan cheque value was changed from " . $Plan->ChequeValue . " to " . $ChequeValue;
            $Plan->ChequeValue = $ChequeValue;
        }
        if ($ChequeMaxOut) {
            $Desc = $Desc . ", Plan cheque max out was changed from " . $Plan->ChequeMaxOut . " to " . $ChequeMaxOut;
            $Plan->ChequeMaxOut = $ChequeMaxOut;
        }
        if ($ChequeEarnDay) {
            $Desc = $Desc . ", Plan cheque earn day was changed from " . $Plan->ChequeEarnDay . " to " . $ChequeEarnDay;
            $Plan->ChequeEarnDay = $ChequeEarnDay;
        }
        $Plan->PlanStatus = "PENDING";
        $Plan->save();

        ActionBackLog($Admin->IDUser, $Plan->IDPlan, "EDIT_PLAN", $Desc);
        return RespondWithSuccessRequest(8);
    }

    public function PlanAjax(Request $request, Plan $Plans)
    {
        $SearchKey = $request->SearchKey;
        $PlanStatus = $request->PlanStatus;
        if ($SearchKey) {
            $Plans = $Plans->where(function ($query) use ($SearchKey) {
                $query->where('PlanNameEn', 'like', '%' . $SearchKey . '%')
                    ->orwhere('PlanNameAr', 'like', '%' . $SearchKey . '%')
                    ->orwhere('PlanDescEn', 'like', '%' . $SearchKey . '%')
                    ->orwhere('PlanDescAr', 'like', '%' . $SearchKey . '%');
            });
        }
        if ($PlanStatus) {
            $Plans = $Plans->where("PlanStatus", $PlanStatus);
        }

        $Pages = ceil($Plans->count() / 20);
        $Plans = $Plans->get();
        $Plans = PlanResource::collection($Plans);

        $APICode = APICode::where('IDAPICode', 8)->first();
        $Response = array(
            'Success' => true,
            'ApiMsg' => __('apicodes.' . $APICode->IDApiCode),
            'ApiCode' => $APICode->IDApiCode,
            'Response' => $Plans,
        );
        return $Response;
    }

    public function PlanProductList(Request $request, PlanProduct $PlanProducts)
    {
        $IDPage = $request->IDPage;
        $SearchKey = $request->SearchKey;
        $PlanProductStatus = $request->PlanProductStatus;
        if (!$IDPage) {
            $IDPage = 0;
        } else {
            $IDPage = ($request->IDPage - 1) * 20;
        }

        $PlanProducts = $PlanProducts->leftjoin("plans", "plans.IDPlan", "planproducts.IDPlan");
        if ($SearchKey) {
            $PlanProducts = $PlanProducts->where(function ($query) use ($SearchKey) {
                $query->where('planproducts.PlanProductNameEn', 'like', '%' . $SearchKey . '%')
                    ->orwhere('planproducts.PlanProductNameAr', 'like', '%' . $SearchKey . '%')
                    ->orwhere('planproducts.PlanProductDescEn', 'like', '%' . $SearchKey . '%')
                    ->orwhere('planproducts.PlanProductDescAr', 'like', '%' . $SearchKey . '%');
            });
        }
        if ($PlanProductStatus) {
            $PlanProducts = $PlanProducts->where("planproducts.PlanProductStatus", $PlanProductStatus);
        }

        $Pages = ceil($PlanProducts->count() / 20);
        $PlanProducts = $PlanProducts->select("planproducts.IDPlanProduct", "plans.IDPlan", "plans.PlanNameEn", "plans.PlanNameAr", "planproducts.PlanProductNameEn", "planproducts.PlanProductNameAr", "planproducts.PlanProductDescEn", "planproducts.PlanProductDescAr", "planproducts.PlanProductAddressEn", "planproducts.PlanProductAddressAr", "planproducts.PlanProductPhone", "planproducts.PlanProductStatus", "planproducts.PlanProductPrice", "planproducts.PlanProductRewardPoints", "planproducts.PlanProductPoints", "planproducts.PlanProductReferralPoints", "planproducts.PlanProductLatitude", "planproducts.PlanProductLongitude", "planproducts.PlanProductUplinePoints", "planproducts.AgencyNumber", "planproducts.CardNumber", "planproducts.created_at")->orderby("planproducts.IDPlanProduct", "DESC")->skip($IDPage)->take(20)->get();
        $PlanProducts = PlanProductResource::collection($PlanProducts);
        $Response = array("PlanProducts" => $PlanProducts, "Pages" => $Pages);

        $APICode = APICode::where('IDAPICode', 8)->first();
        $Response = array(
            'Success' => true,
            'ApiMsg' => __('apicodes.' . $APICode->IDApiCode),
            'ApiCode' => $APICode->IDApiCode,
            'Response' => $Response,
        );
        return $Response;
    }

    public function PlanProductStatus(Request $request)
    {
        $Admin = auth('user')->user();
        $IDPlanProduct = $request->IDPlanProduct;
        $PlanProductStatus = $request->PlanProductStatus;
        if (!$IDPlanProduct) {
            return RespondWithBadRequest(1);
        }
        if (!$PlanProductStatus) {
            return RespondWithBadRequest(1);
        }

        $PlanProduct = PlanProduct::find($IDPlanProduct);
        $Desc = "Plan Product status changed from " . $PlanProduct->PlanProductStatus . " to " . $PlanProductStatus;
        $PlanProduct->PlanProductStatus = $PlanProductStatus;
        $PlanProduct->save();

        ActionBackLog($Admin->IDUser, $PlanProduct->IDPlanProduct, "EDIT_PLAN_PRODUCT", $Desc);
        return RespondWithSuccessRequest(8);
    }

    public function PlanProductAdd(Request $request)
    {
        $Admin = auth('user')->user();
        if (!$Admin) {
            return RespondWithBadRequest(10);
        }
        $IDPlan = $request->IDPlan;
        $PlanProductNameEn = $request->PlanProductNameEn;
        $PlanProductNameAr = $request->PlanProductNameAr;
        $PlanProductDescEn = $request->PlanProductDescEn;
        $PlanProductDescAr = $request->PlanProductDescAr;
        $PlanProductAddressEn = $request->PlanProductAddressEn;
        $PlanProductAddressAr = $request->PlanProductAddressAr;
        $PlanProductPhone = $request->PlanProductPhone;
        $PlanProductPrice = $request->PlanProductPrice;
        $PlanProductPoints = $request->PlanProductPoints;
        $PlanProductRewardPoints = $request->PlanProductRewardPoints;
        $PlanProductReferralPoints = $request->PlanProductReferralPoints;
        $PlanProductUplinePoints = $request->PlanProductUplinePoints;
        $CardNumber = $request->CardNumber;
        $AgencyNumber = $request->AgencyNumber;
        $PlanProductLatitude = $request->PlanProductLatitude;
        $PlanProductLongitude = $request->PlanProductLongitude;
        $PlanProductGallery = $request->PlanProductGallery;
        $PlanProductVideos = $request->PlanProductVideos;

        if (!$PlanProductNameEn) {
            return RespondWithBadRequest(1);
        }
        if (!$PlanProductNameAr) {
            return RespondWithBadRequest(1);
        }
        if (!$PlanProductPrice) {
            return RespondWithBadRequest(1);
        }
        if (!$AgencyNumber) {
            return RespondWithBadRequest(1);
        }
        if (!$CardNumber) {
            return RespondWithBadRequest(1);
        }
        if (!$PlanProductRewardPoints) {
            $PlanProductRewardPoints = 0;
        }
        if (!$PlanProductReferralPoints) {
            $PlanProductReferralPoints = 0;
        }
        if (!$PlanProductUplinePoints) {
            $PlanProductUplinePoints = 0;
        }

        $ImageExtArray = ["jpeg", "jpg", "png", "svg"];
        if ($PlanProductGallery) {
            foreach ($PlanProductGallery as $Photo) {
                if (!in_array($Photo->extension(), $ImageExtArray)) {
                    return RespondWithBadRequest(15);
                }
            }
        }

        $PlanProduct = new PlanProduct;
        $PlanProduct->IDPlan = $IDPlan;
        $PlanProduct->PlanProductNameEn = $PlanProductNameEn;
        $PlanProduct->PlanProductNameAr = $PlanProductNameAr;
        $PlanProduct->PlanProductDescEn = $PlanProductDescEn;
        $PlanProduct->PlanProductDescAr = $PlanProductDescAr;
        $PlanProduct->PlanProductAddressEn = $PlanProductAddressEn;
        $PlanProduct->PlanProductAddressAr = $PlanProductAddressAr;
        $PlanProduct->PlanProductPhone = $PlanProductPhone;
        $PlanProduct->PlanProductPrice = $PlanProductPrice;
        $PlanProduct->AgencyNumber = $AgencyNumber;
        $PlanProduct->PlanProductRewardPoints = $PlanProductRewardPoints;
        $PlanProduct->PlanProductPoints = $PlanProductPoints;
        $PlanProduct->PlanProductReferralPoints = $PlanProductReferralPoints;
        $PlanProduct->PlanProductUplinePoints = $PlanProductUplinePoints;
        $PlanProduct->PlanProductLatitude = $PlanProductLatitude;
        $PlanProduct->PlanProductLongitude = $PlanProductLongitude;
        $PlanProduct->CardNumber = $CardNumber;
        $PlanProduct->PlanProductStatus = "PENDING";
        $PlanProduct->save();

        if ($PlanProductGallery) {
            foreach ($PlanProductGallery as $Photo) {
                $Image = SaveImage($Photo, "planproducts", $PlanProduct->IDPlanProduct);
                $PlanProductGalleryRow = new PlanProductGallery;
                $PlanProductGalleryRow->IDPlanProduct = $PlanProduct->IDPlanProduct;
                $PlanProductGalleryRow->PlanProductGalleryPath = $Image;
                $PlanProductGalleryRow->PlanProductGalleryType = "IMAGE";
                $PlanProductGalleryRow->save();
            }
        }

        if ($PlanProductVideos) {
            if (count($PlanProductVideos)) {
                foreach ($PlanProductVideos as $Video) {
                    $YouTubeVideo = YoutubeEmbedUrl($Video);
                    $PlanProductGalleryRow = new PlanProductGallery;
                    $PlanProductGalleryRow->IDPlanProduct = $PlanProduct->IDPlanProduct;
                    $PlanProductGalleryRow->PlanProductGalleryPath = $YouTubeVideo;
                    $PlanProductGalleryRow->PlanProductGalleryType = "VIDEO";
                    $PlanProductGalleryRow->save();
                }
            }
        }

        $Desc = "Plan Product" . $PlanProductNameEn . " was added";
        ActionBackLog($Admin->IDUser, $PlanProduct->IDPlanProduct, "ADD_PLAN_PRODUCT", $Desc);
        return RespondWithSuccessRequest(8);
    }

    public function PlanProductEditPage($IDPlanProduct)
    {
        $PlanProduct = PlanProduct::find($IDPlanProduct);
        if (!$PlanProduct) {
            return RespondWithBadRequest(1);
        }

        $PlanProductGallery = PlanProductGallery::where("IDPlanProduct", $IDPlanProduct)->where("PlanProductGalleryDeleted", 0)->orderby("PlanProductGalleryType")->get();
        foreach ($PlanProductGallery as $Gallery) {
            if ($Gallery->PlanProductGalleryType == "IMAGE") {
                $Gallery->PlanProductGalleryPath = ($Gallery->PlanProductGalleryPath) ? asset($Gallery->PlanProductGalleryPath) : '';
            }
        }

        $PlanProduct->PlanProductGallery = $PlanProductGallery;

        $APICode = APICode::where('IDAPICode', 8)->first();
        $Response = array(
            'Success' => true,
            'ApiMsg' => __('apicodes.' . $APICode->IDApiCode),
            'ApiCode' => $APICode->IDApiCode,
            'Response' => $PlanProduct,
        );
        return $Response;
    }

    public function PlanProductEdit(Request $request)
    {
        $Admin = auth('user')->user();
        $IDPlanProduct = $request->IDPlanProduct;
        $IDPlan = $request->IDPlan;
        $PlanProductNameEn = $request->PlanProductNameEn;
        $PlanProductNameAr = $request->PlanProductNameAr;
        $PlanProductDescEn = $request->PlanProductDescEn;
        $PlanProductDescAr = $request->PlanProductDescAr;
        $PlanProductAddressEn = $request->PlanProductAddressEn;
        $PlanProductAddressAr = $request->PlanProductAddressAr;
        $PlanProductPhone = $request->PlanProductPhone;
        $PlanProductPrice = $request->PlanProductPrice;
        $PlanProductRewardPoints = $request->PlanProductRewardPoints;
        $PlanProductPoints = $request->PlanProductPoints;
        $PlanProductReferralPoints = $request->PlanProductReferralPoints;
        $PlanProductUplinePoints = $request->PlanProductUplinePoints;
        $CardNumber = $request->CardNumber;
        $PlanProductLatitude = $request->PlanProductLatitude;
        $PlanProductLongitude = $request->PlanProductLongitude;
        $PlanProductGallery = $request->PlanProductGallery;
        $PlanProductVideos = $request->PlanProductVideos;
        $Desc = "";

        $PlanProduct = PlanProduct::find($IDPlanProduct);
        if (!$PlanProduct) {
            return RespondWithBadRequest(1);
        }

        $ImageExtArray = ["jpeg", "jpg", "png", "svg"];
        if ($PlanProductGallery) {
            foreach ($PlanProductGallery as $Photo) {
                if (!in_array($Photo->extension(), $ImageExtArray)) {
                    return RespondWithBadRequest(15);
                }
            }
        }

        if ($IDPlan) {
            $Desc = "Plan Product Plan Changed";
            $PlanProduct->IDPlan = $IDPlan;
        }
        if ($PlanProductNameEn) {
            $Desc = $Desc . ", Plan Product english name changed from " . $PlanProduct->PlanProductNameEn . " to " . $PlanProductNameEn;
            $PlanProduct->PlanProductNameEn = $PlanProductNameEn;
        }
        if ($PlanProductNameAr) {
            $Desc = $Desc . ", Plan Product arabic name changed from " . $PlanProduct->PlanProductNameAr . " to " . $PlanProductNameAr;
            $PlanProduct->PlanProductNameAr = $PlanProductNameAr;
        }
        if ($PlanProductDescEn) {
            $Desc = $Desc . ", Plan Product english desc changed from " . $PlanProduct->PlanProductDescEn . " to " . $PlanProductDescEn;
            $PlanProduct->PlanProductDescEn = $PlanProductDescEn;
        }
        if ($PlanProductDescAr) {
            $Desc = $Desc . ", Plan Product arabic desc changed from " . $PlanProduct->PlanProductDescAr . " to " . $PlanProductDescAr;
            $PlanProduct->PlanProductDescAr = $PlanProductDescAr;
        }
        if ($PlanProductAddressEn) {
            $Desc = $Desc . ", Plan Product english address changed from " . $PlanProduct->PlanProductAddressEn . " to " . $PlanProductAddressEn;
            $PlanProduct->PlanProductAddressEn = $PlanProductAddressEn;
        }
        if ($PlanProductAddressAr) {
            $Desc = $Desc . ", Plan Product arabic address changed from " . $PlanProduct->PlanProductAddressAr . " to " . $PlanProductAddressAr;
            $PlanProduct->PlanProductAddressAr = $PlanProductAddressAr;
        }
        if ($PlanProductPhone) {
            $Desc = $Desc . ", Plan Product phone changed from " . $PlanProduct->PlanProductPhone . " to " . $PlanProductPhone;
            $PlanProduct->PlanProductPhone = $PlanProductPhone;
        }
        if ($PlanProductPrice) {
            $Desc = $Desc . ", Plan Product price changed from " . $PlanProduct->PlanProductPrice . " to " . $PlanProductPrice;
            $PlanProduct->PlanProductPrice = $PlanProductPrice;
        }
        if ($PlanProductPoints) {
            $Desc = $Desc . ", Plan Product points changed from " . $PlanProduct->PlanProductPoints . " to " . $PlanProductPoints;
            $PlanProduct->PlanProductPoints = $PlanProductPoints;
        }
        if ($PlanProductRewardPoints) {
            $Desc = $Desc . ", Plan Product reward points changed from " . $PlanProduct->PlanProductRewardPoints . " to " . $PlanProductRewardPoints;
            $PlanProduct->PlanProductRewardPoints = $PlanProductRewardPoints;
        }
        if ($PlanProductLatitude) {
            $Desc = $Desc . ", Plan Product latitude changed from " . $PlanProduct->PlanProductLatitude . " to " . $PlanProductLatitude;
            $PlanProduct->PlanProductLatitude = $PlanProductLatitude;
        }
        if ($PlanProductLongitude) {
            $Desc = $Desc . ", Plan Product longitude changed from " . $PlanProduct->PlanProductLongitude . " to " . $PlanProductLongitude;
            $PlanProduct->PlanProductLongitude = $PlanProductLongitude;
        }
        if ($PlanProductUplinePoints) {
            $Desc = $Desc . ", Plan Product upline points changed from " . $PlanProduct->PlanProductUplinePoints . " to " . $PlanProductUplinePoints;
            $PlanProduct->PlanProductUplinePoints = $PlanProductUplinePoints;
        }
        if ($PlanProductReferralPoints) {
            $Desc = $Desc . ", Plan Product referral points changed from " . $PlanProduct->PlanProductReferralPoints . " to " . $PlanProductReferralPoints;
            $PlanProduct->PlanProductReferralPoints = $PlanProductReferralPoints;
        }
        if ($CardNumber) {
            $Desc = $Desc . ", Plan Product card number changed from " . $PlanProduct->CardNumber . " to " . $CardNumber;
            $PlanProduct->CardNumber = $CardNumber;
        }
        $PlanProduct->PlanProductStatus = "PENDING";
        $PlanProduct->save();

        if ($PlanProductGallery) {
            foreach ($PlanProductGallery as $Photo) {
                $Image = SaveImage($Photo, "planproducts", $PlanProduct->IDPlanProduct);
                $PlanProductGalleryRow = new PlanProductGallery;
                $PlanProductGalleryRow->IDPlanProduct = $PlanProduct->IDPlanProduct;
                $PlanProductGalleryRow->PlanProductGalleryPath = $Image;
                $PlanProductGalleryRow->PlanProductGalleryType = "IMAGE";
                $PlanProductGalleryRow->save();
            }
            $Desc = $Desc . ", Plan Product gallery added";
        }

        if ($PlanProductVideos) {
            if (count($PlanProductVideos)) {
                foreach ($PlanProductVideos as $Video) {
                    $YouTubeVideo = YoutubeEmbedUrl($Video);
                    $PlanProductGalleryRow = new PlanProductGallery;
                    $PlanProductGalleryRow->IDPlanProduct = $PlanProduct->IDPlanProduct;
                    $PlanProductGalleryRow->PlanProductGalleryPath = $YouTubeVideo;
                    $PlanProductGalleryRow->PlanProductGalleryType = "VIDEO";
                    $PlanProductGalleryRow->save();
                }
            }
            $Desc = $Desc . ", Plan Product videos added";
        }

        ActionBackLog($Admin->IDUser, $PlanProduct->IDPlanProduct, "EDIT_PLAN_PRODUCT", $Desc);
        return RespondWithSuccessRequest(8);
    }

    public function PlanProductGalleryRemove($IDPlanProductGallery)
    {
        $Admin = auth('user')->user();
        $PlanProductGallery = PlanProductGallery::find($IDPlanProductGallery);
        if (!$PlanProductGallery) {
            return RespondWithBadRequest(1);
        }

        if ($PlanProductGallery->PlanProductGalleryType == "IMAGE") {
            $OldDocument = substr($PlanProductGallery->PlanProductGalleryPath, 7);
            Storage::disk('uploads')->delete($OldDocument);
        }

        $PlanProductGallery->PlanProductGalleryDeleted = 1;
        $PlanProductGallery->save();

        $Desc = "Plan Product Gallery Removed";
        ActionBackLog($Admin->IDUser, $PlanProductGallery->IDPlanProduct, "EDIT_PLAN_PRODUCT", $Desc);
        return RespondWithSuccessRequest(8);
    }

    public function PlanProductSocialList(Request $request)
    {
        $IDPlanProduct = $request->IDPlanProduct;
        if (!$IDPlanProduct) {
            return RespondWithBadRequest(1);
        }

        $SocialMedia = SocialMedia::where("SocialMediaActive", 1)->select("IDSocialMedia", "SocialMediaName", "SocialMediaIcon")->get();
        foreach ($SocialMedia as $Media) {
            $PlanProductSocialLink = PlanProductSocialLink::where("IDPlanProduct", $IDPlanProduct)->where("IDSocialMedia", $Media->IDSocialMedia)->select("SocialLinkDeleted", "SocialLink")->first();
            if ($PlanProductSocialLink) {
                $Media->SocialLinkDeleted = $PlanProductSocialLink->SocialLinkDeleted;
                $Media->SocialLink = $PlanProductSocialLink->SocialLink;
            } else {
                $Media->SocialLink = "";
                $Media->SocialLinkDeleted = 1;
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

    public function PlanProductSocialStatus(Request $request)
    {
        $Admin = auth('user')->user();
        $IDPlanProduct = $request->IDPlanProduct;
        $IDSocialMedia = $request->IDSocialMedia;
        $SocialLink = $request->SocialLink;
        if (!$IDPlanProduct) {
            return RespondWithBadRequest(1);
        }
        if (!$IDSocialMedia) {
            return RespondWithBadRequest(1);
        }

        $PlanProductSocialLink = PlanProductSocialLink::where("IDPlanProduct", $IDPlanProduct)->where("IDSocialMedia", $IDSocialMedia)->first();
        if ($PlanProductSocialLink) {
            $PlanProductSocialLink->SocialLinkDeleted = !$PlanProductSocialLink->SocialLinkDeleted;
            $PlanProductSocialLink->save();
            $Desc = "Brand Stauts changed";
        } else {
            if (!$SocialLink) {
                return RespondWithBadRequest(1);
            }
            $PlanProductSocialLink = new PlanProductSocialLink;
            $PlanProductSocialLink->IDPlanProduct = $IDPlanProduct;
            $PlanProductSocialLink->IDSocialMedia = $IDSocialMedia;
            $PlanProductSocialLink->SocialLink = $SocialLink;
            $PlanProductSocialLink->save();
            $Desc = "New Brand Added to Plan Product with link " . $SocialLink;
        }

        ActionBackLog($Admin->IDUser, $PlanProductSocialLink->IDPlanProduct, "EDIT_PLAN_PRODUCT", $Desc);
        return RespondWithSuccessRequest(8);
    }

    public function PlanProductAjax(Request $request)
    {
        $IDPlan = $request->IDPlan;
        $PlanProducts = PlanProduct::leftjoin("plans", "plans.IDPlan", "planproducts.IDPlan")->where("planproducts.PlanProductStatus", "ACTIVE");
        if ($IDPlan) {
            $PlanProducts = $PlanProducts->where("planproducts.IDPlan", $IDPlan);
        }
        $PlanProducts = $PlanProducts->select("planproducts.IDPlanProduct", "plans.IDPlan", "plans.PlanNameEn", "plans.PlanNameAr", "planproducts.PlanProductNameEn", "planproducts.PlanProductNameAr", "planproducts.PlanProductDescEn", "planproducts.PlanProductDescAr", "planproducts.PlanProductStatus", "planproducts.PlanProductPrice", "planproducts.PlanProductRewardPoints", "planproducts.PlanProductPoints", "planproducts.PlanProductReferralPoints", "planproducts.PlanProductLatitude", "planproducts.PlanProductLongitude", "planproducts.PlanProductUplinePoints", "planproducts.AgencyNumber", "planproducts.CardNumber", "planproducts.created_at")->get();
        $PlanProducts = PlanProductResource::collection($PlanProducts);

        $APICode = APICode::where('IDAPICode', 8)->first();
        $Response = array(
            'Success' => true,
            'ApiMsg' => __('apicodes.' . $APICode->IDApiCode),
            'ApiCode' => $APICode->IDApiCode,
            'Response' => $PlanProducts,
        );
        return $Response;
    }

    public function PlanProductUpgrades(Request $request)
    {
        $User = auth('user')->user();
        $UpgradeActive = $request->UpgradeActive;

        if ($UpgradeActive) {
            $PlanProductUpgrades = PlanProductUpgrade::where("UpgradeActive", 1)->get();
        } else {
            $PlanProductUpgrades = PlanProductUpgrade::all();
        }

        $UserLanguage = AdminLanguage($User->UserLanguage);
        $UpgradeName = "UpgradeName" . $UserLanguage;
        foreach ($PlanProductUpgrades as $Upgrade) {
            $Upgrade->UpgradeName = $Upgrade->$UpgradeName;
            unset($Upgrade['UpgradeNameEn']);
            unset($Upgrade['UpgradeNameAr']);
            unset($Upgrade['updated_at']);
        }

        $APICode = APICode::where('IDAPICode', 8)->first();
        $Response = array(
            'Success' => true,
            'ApiMsg' => __('apicodes.' . $APICode->IDApiCode),
            'ApiCode' => $APICode->IDApiCode,
            'Response' => $PlanProductUpgrades,
        );
        return $Response;
    }

    public function PlanProductUpgradeStatus($IDPlanProductUpgrade)
    {
        $User = auth('user')->user();
        $PlanProductUpgrade = PlanProductUpgrade::find($IDPlanProductUpgrade);
        if (!$PlanProductUpgrade) {
            return RespondWithBadRequest(1);
        }

        $Desc = "Upgrade status changed from " . $PlanProductUpgrade->UpgradeActive . " to " . !$PlanProductUpgrade->UpgradeActive;
        $PlanProductUpgrade->UpgradeActive = !$PlanProductUpgrade->UpgradeActive;
        $PlanProductUpgrade->save();

        ActionBackLog($User->IDUser, $IDPlanProductUpgrade, "EDIT_UPGRADE", $Desc);
        return RespondWithSuccessRequest(8);
    }

    public function PlanProductUpgradeAdd(Request $request)
    {
        $User = auth('user')->user();

        $UpgradeNameEn = $request->UpgradeNameEn;
        $UpgradeNameAr = $request->UpgradeNameAr;
        $UpgradeAgencyNumber = $request->UpgradeAgencyNumber;
        $UpgradePrice = $request->UpgradePrice;
        if (!$UpgradeNameEn) {
            return RespondWithBadRequest(1);
        }
        if (!$UpgradeNameAr) {
            return RespondWithBadRequest(1);
        }
        if (!$UpgradeAgencyNumber) {
            return RespondWithBadRequest(1);
        }
        if (!$UpgradePrice) {
            return RespondWithBadRequest(1);
        }

        $PlanProductUpgrade = new PlanProductUpgrade;
        $PlanProductUpgrade->UpgradeNameEn = $UpgradeNameEn;
        $PlanProductUpgrade->UpgradeNameAr = $UpgradeNameAr;
        $PlanProductUpgrade->UpgradeAgencyNumber = $UpgradeAgencyNumber;
        $PlanProductUpgrade->UpgradePrice = $UpgradePrice;
        $PlanProductUpgrade->save();

        $Desc = "Upgrade Added";
        ActionBackLog($User->IDUser, $PlanProductUpgrade->IDPlanProductUpgrade, "ADD_UPGRADE", $Desc);
        return RespondWithSuccessRequest(8);
    }

    public function PlanProductUpgradeEditPage($IDPlanProductUpgrade)
    {
        $User = auth('user')->user();
        $PlanProductUpgrade = PlanProductUpgrade::find($IDPlanProductUpgrade);
        if (!$PlanProductUpgrade) {
            return RespondWithBadRequest(1);
        }

        $APICode = APICode::where('IDAPICode', 8)->first();
        $Response = array(
            'Success' => true,
            'ApiMsg' => __('apicodes.' . $APICode->IDApiCode),
            'ApiCode' => $APICode->IDApiCode,
            'Response' => $PlanProductUpgrade,
        );
        return $Response;
    }

    public function PlanProductUpgradeEdit(Request $request)
    {
        $User = auth('user')->user();

        $IDPlanProductUpgrade = $request->IDPlanProductUpgrade;
        $UpgradeNameEn = $request->UpgradeNameEn;
        $UpgradeNameAr = $request->UpgradeNameAr;
        $UpgradeAgencyNumber = $request->UpgradeAgencyNumber;
        $UpgradePrice = $request->UpgradePrice;
        $Desc = "";

        $PlanProductUpgrade = PlanProductUpgrade::find($IDPlanProductUpgrade);
        if (!$PlanProductUpgrade) {
            return RespondWithBadRequest(1);
        }

        if ($UpgradeNameEn) {
            $Desc = $Desc . "Upgrade English name changed from " . $PlanProductUpgrade->UpgradeNameEn . " to " . $UpgradeNameEn;
            $PlanProductUpgrade->UpgradeNameEn = $UpgradeNameEn;
        }
        if ($UpgradeNameAr) {
            $Desc = $Desc . " ,Upgrade Arabic name changed from " . $PlanProductUpgrade->UpgradeNameAr . " to " . $UpgradeNameAr;
            $PlanProductUpgrade->UpgradeNameAr = $UpgradeNameAr;
        }
        if ($UpgradeAgencyNumber) {
            $Desc = $Desc . " ,Upgrade agency Number changed from " . $PlanProductUpgrade->UpgradeAgencyNumber . " to " . $UpgradeAgencyNumber;
            $PlanProductUpgrade->UpgradeAgencyNumber = $UpgradeAgencyNumber;
        }
        if ($UpgradePrice) {
            $Desc = $Desc . " ,Upgrade price changed from " . $PlanProductUpgrade->UpgradePrice . " to " . $UpgradePrice;
            $PlanProductUpgrade->UpgradePrice = $UpgradePrice;
        }

        $PlanProductUpgrade->save();

        ActionBackLog($User->IDUser, $IDPlanProductUpgrade, "EDIT_UPGRADE", $Desc);
        return RespondWithSuccessRequest(8);
    }

    public function BonanzaList(Request $request, Bonanza $Bonanzas)
    {
        $IDPage = $request->IDPage;
        $SearchKey = $request->SearchKey;
        $BonanzaStatus = $request->BonanzaStatus;
        if (!$IDPage) {
            $IDPage = 0;
        } else {
            $IDPage = ($request->IDPage - 1) * 20;
        }

        if ($SearchKey) {
            $Bonanzas = $Bonanzas->where(function ($query) use ($SearchKey) {
                $query->where('BonanzaTitleEn', 'like', '%' . $SearchKey . '%')
                    ->orwhere('BonanzaTitleAr', 'like', '%' . $SearchKey . '%');
            });
        }
        if ($BonanzaStatus) {
            $Bonanzas = $Bonanzas->where("BonanzaStatus", $BonanzaStatus);
        }

        $Pages = ceil($Bonanzas->count() / 20);
        $Bonanzas = $Bonanzas->orderby("IDBonanza", "DESC")->skip($IDPage)->take(20)->get();
        $Bonanzas = BonanzaResource::collection($Bonanzas);
        $Response = array("Bonanzas" => $Bonanzas, "Pages" => $Pages);

        $APICode = APICode::where('IDAPICode', 8)->first();
        $Response = array(
            'Success' => true,
            'ApiMsg' => __('apicodes.' . $APICode->IDApiCode),
            'ApiCode' => $APICode->IDApiCode,
            'Response' => $Response,
        );
        return $Response;
    }

    public function BonanzaStatus(Request $request)
    {
        $Admin = auth('user')->user();
        $IDBonanza = $request->IDBonanza;
        $BonanzaStatus = $request->BonanzaStatus;
        if (!$IDBonanza) {
            return RespondWithBadRequest(1);
        }
        if (!$BonanzaStatus) {
            return RespondWithBadRequest(1);
        }

        $Bonanza = Bonanza::find($IDBonanza);
        $Desc = "Bonanza status changed from " . $Bonanza->BonanzaStatus . " to " . $BonanzaStatus;
        $Bonanza->BonanzaStatus = $BonanzaStatus;
        $Bonanza->save();

        ActionBackLog($Admin->IDUser, $Bonanza->IDBonanza, "EDIT_BONANZA", $Desc);
        return RespondWithSuccessRequest(8);
    }

    public function BonanzaAdd(Request $request)
    {
        $Admin = auth('user')->user();
        $BonanzaTitleEn = $request->BonanzaTitleEn;
        $BonanzaTitleAr = $request->BonanzaTitleAr;
        $BonanzaLeftPoints = $request->BonanzaLeftPoints;
        $BonanzaRightPoints = $request->BonanzaRightPoints;
        $BonanzaTotalPoints = $request->BonanzaTotalPoints;

        $BonanzaLeftPersons = $request->BonanzaLeftPersons;
        $BonanzaRightPersons = $request->BonanzaRightPersons;
        $BonanzaTotalPersons = $request->BonanzaTotalPersons;

        $BonanzaPrize = $request->BonanzaPrize; // Points OR Cheque

        $BonanzaVisitNumber = $request->BonanzaVisitNumber;
        $BonanzaReferralNumber = $request->BonanzaReferralNumber;
        $BonanzaStartTime = $request->BonanzaStartTime;
        $BonanzaEndTime = $request->BonanzaEndTime;
        $BonanzaRewardPoints = $request->BonanzaRewardPoints;
        $BonanzaChequeValue = $request->BonanzaChequeValue;

        if (!$BonanzaTitleEn) {
            return RespondWithBadRequest(1);
        }
        if (!$BonanzaTitleAr) {
            return RespondWithBadRequest(1);
        }
        if (!$BonanzaStartTime) {
            return RespondWithBadRequest(1);
        }
        if (!$BonanzaEndTime) {
            return RespondWithBadRequest(1);
        }
        if (!$BonanzaRewardPoints && !$BonanzaChequeValue) {
            return RespondWithBadRequest(1);
        }
        if (!$BonanzaRewardPoints) {
            $BonanzaRewardPoints = 0;
        }
        if (!$BonanzaChequeValue) {
            $BonanzaChequeValue = 0;
        }
        if (!$BonanzaVisitNumber) {
            $BonanzaVisitNumber = 0;
        }
        if (!$BonanzaLeftPoints) {
            $BonanzaLeftPoints = 0;
        }
        if (!$BonanzaRightPoints) {
            $BonanzaRightPoints = 0;
        }
        if (!$BonanzaTotalPoints) {
            $BonanzaTotalPoints = 0;
        }
        if (!$BonanzaLeftPersons) {
            $BonanzaLeftPersons = 0;
        }
        if (!$BonanzaRightPersons) {
            $BonanzaRightPersons = 0;
        }
        if (!$BonanzaTotalPersons) {
            $BonanzaTotalPersons = 0;
        }
        if (!$BonanzaReferralNumber) {
            $BonanzaReferralNumber = 0;
        }

        $Bonanza = new Bonanza;
        $Bonanza->BonanzaTitleEn = $BonanzaTitleEn;
        $Bonanza->BonanzaTitleAr = $BonanzaTitleAr;
        $Bonanza->BonanzaStartTime = $BonanzaStartTime;
        $Bonanza->BonanzaEndTime = $BonanzaEndTime;
        if ($BonanzaPrize == "Points") {
            $Bonanza->BonanzaRewardPoints = $BonanzaRewardPoints;
        } else if ($BonanzaPrize == "Cheque") {
            $Bonanza->BonanzaChequeValue = $BonanzaChequeValue;
        }
        if ($request->BonanzaPoints == "Total") {
            $Bonanza->BonanzaLeftPoints = 0;
            $Bonanza->BonanzaRightPoints = 0;
            $Bonanza->BonanzaTotalPoints = $BonanzaTotalPoints;
        } else if ($request->BonanzaPoints == "Balance") {
            $Bonanza->BonanzaLeftPoints = $BonanzaLeftPoints;
            $Bonanza->BonanzaRightPoints = $BonanzaRightPoints;
            $Bonanza->BonanzaTotalPoints = 0;
        }
        if ($request->BonanzaPersons == "Total") {
            $Bonanza->BonanzaLeftPersons = 0;
            $Bonanza->BonanzaRightPersons = 0;
            $Bonanza->BonanzaTotalPersons = $BonanzaTotalPersons;
        } else if ($request->BonanzaPersons == "Balance") {
            $Bonanza->BonanzaLeftPersons = $BonanzaLeftPersons;
            $Bonanza->BonanzaRightPersons = $BonanzaRightPersons;
            $Bonanza->BonanzaTotalPersons = 0;
        }
        if ($request->IsBonanzaUniqueVisits) {
            $Bonanza->IsBonanzaUniqueVisits = $request->IsBonanzaUniqueVisits;
            $Bonanza->BonanzaVisitNumber = 0;
        } else {
            $Bonanza->IsBonanzaUniqueVisits = $request->IsBonanzaUniqueVisits;
            $Bonanza->BonanzaVisitNumber = $BonanzaVisitNumber;
        }
        $Bonanza->BonanzaReferralNumber = $BonanzaReferralNumber;
        $Bonanza->BonanzaStatus = "PENDING";
        $Bonanza->save();

        $Desc = "Bonanza " . $BonanzaTitleEn . " was added";
        ActionBackLog($Admin->IDUser, $Bonanza->IDBonanza, "ADD_BONANZA", $Desc);

        SendBonanzaNotifications::dispatch($Bonanza->IDBonanza);

        $APICode = APICode::where('IDAPICode', 8)->first();
        $Response = array(
            'Success' => true,
            'ApiMsg' => __('apicodes.' . $APICode->IDApiCode),
            'ApiCode' => $APICode->IDApiCode,
            'Response' => $Bonanza->IDBonanza,
        );
        return $Response;
    }

    public function BonanzaEditPage($IDBonanza)
    {
        $Bonanza = Bonanza::find($IDBonanza);
        if (!$Bonanza) {
            return RespondWithBadRequest(1);
        }

        $APICode = APICode::where('IDAPICode', 8)->first();
        $Response = array(
            'Success' => true,
            'ApiMsg' => __('apicodes.' . $APICode->IDApiCode),
            'ApiCode' => $APICode->IDApiCode,
            'Response' => $Bonanza,
        );
        return $Response;
    }

    public function BonanzaEdit(Request $request)
    {
        $Admin = auth('user')->user();
        $IDBonanza = $request->IDBonanza;
        $BonanzaTitleEn = $request->BonanzaTitleEn;
        $BonanzaTitleAr = $request->BonanzaTitleAr;
        $BonanzaLeftPoints = $request->BonanzaLeftPoints;
        $BonanzaRightPoints = $request->BonanzaRightPoints;
        $BonanzaTotalPoints = $request->BonanzaTotalPoints;
        $BonanzaLeftPersons = $request->BonanzaLeftPersons;
        $BonanzaRightPersons = $request->BonanzaRightPersons;
        $BonanzaTotalPersons = $request->BonanzaTotalPersons;
        $BonanzaVisitNumber = $request->BonanzaVisitNumber;
        $BonanzaReferralNumber = $request->BonanzaReferralNumber;
        $BonanzaStartTime = $request->BonanzaStartTime;
        $BonanzaEndTime = $request->BonanzaEndTime;
        $BonanzaRewardPoints = $request->BonanzaRewardPoints;
        $BonanzaPrize = $request->BonanzaPrize; // Points OR Cheque
        $BonanzaChequeValue = $request->BonanzaChequeValue;
        $Desc = "";

        $Bonanza = Bonanza::find($IDBonanza);
        if (!$Bonanza) {
            return RespondWithBadRequest(1);
        }
        if ($BonanzaTitleEn) {
            $Desc = "Bonanza english title changed from " . $Bonanza->BonanzaTitleEn . " to " . $BonanzaTitleEn;
            $Bonanza->BonanzaTitleEn = $BonanzaTitleEn;
        }
        if ($BonanzaTitleAr) {
            $Desc = $Desc . ", Bonanza arabic title changed from " . $Bonanza->BonanzaTitleAr . " to " . $BonanzaTitleAr;
            $Bonanza->BonanzaTitleAr = $BonanzaTitleAr;
        }

        if ($request->BonanzaPoints == "Total") {
            if ($BonanzaTotalPoints) {
                $Desc = $Desc . ", Bonanza left points changed from " . $Bonanza->BonanzaLeftPoints . " to " . 0;
                $Bonanza->BonanzaLeftPoints = 0;

                $Desc = $Desc . ", Bonanza right points changed from " . $Bonanza->BonanzaRightPoints . " to " . 0;
                $Bonanza->BonanzaRightPoints = 0;

                $Desc = $Desc . ", Bonanza total points changed from " . $Bonanza->BonanzaTotalPoints . " to " . $BonanzaTotalPoints;
                $Bonanza->BonanzaTotalPoints = $BonanzaTotalPoints;
            }
        } else if ($request->BonanzaPoints == "Balance") {
            if ($BonanzaLeftPoints && $BonanzaRightPoints) {
                $Desc = $Desc . ", Bonanza left points changed from " . $Bonanza->BonanzaLeftPoints . " to " . $BonanzaLeftPoints;
                $Bonanza->BonanzaLeftPoints = $BonanzaLeftPoints;
                $Desc = $Desc . ", Bonanza right points changed from " . $Bonanza->BonanzaRightPoints . " to " . $BonanzaRightPoints;
                $Bonanza->BonanzaRightPoints = $BonanzaRightPoints;

                $Desc = $Desc . ", Bonanza total points changed from " . $Bonanza->BonanzaTotalPoints . " to " . 0;
                $Bonanza->BonanzaTotalPoints = 0;
            }
        }
        if ($request->BonanzaPersons == "Total") {
            if ($BonanzaTotalPersons) {
                $Desc = $Desc . ", Bonanza left persons changed from " . $Bonanza->BonanzaLeftPersons . " to " . 0;
                $Bonanza->BonanzaLeftPersons = 0;

                $Desc = $Desc . ", Bonanza right persons changed from " . $Bonanza->BonanzaRightPersons . " to " . 0;
                $Bonanza->BonanzaRightPersons = 0;

                $Desc = $Desc . ", Bonanza total persons changed from " . $Bonanza->BonanzaTotalPersons . " to " . $BonanzaTotalPersons;
                $Bonanza->BonanzaTotalPersons = $BonanzaTotalPersons;
            }
        } else if ($request->BonanzaPersons == "Balance") {
            if ($BonanzaLeftPersons && $BonanzaRightPersons) {
                $Desc = $Desc . ", Bonanza left persons changed from " . $Bonanza->BonanzaLeftPersons . " to " . $BonanzaLeftPersons;
                $Bonanza->BonanzaLeftPersons = $BonanzaLeftPersons;
                $Desc = $Desc . ", Bonanza right persons changed from " . $Bonanza->BonanzaRightPersons . " to " . $BonanzaRightPersons;
                $Bonanza->BonanzaRightPersons = $BonanzaRightPersons;

                $Desc = $Desc . ", Bonanza total persons changed from " . $Bonanza->BonanzaTotalPersons . " to " . 0;
                $Bonanza->BonanzaTotalPersons = 0;
            }
        }

        if ($request->IsBonanzaUniqueVisits) {
            $Bonanza->IsBonanzaUniqueVisits = $request->IsBonanzaUniqueVisits;
            $Bonanza->BonanzaVisitNumber = 0;
        } else {
            $Bonanza->IsBonanzaUniqueVisits = $request->IsBonanzaUniqueVisits;
            $Desc = $Desc . ", Bonanza visit number changed from " . $Bonanza->BonanzaVisitNumber . " to " . $BonanzaVisitNumber;
            $Bonanza->BonanzaVisitNumber = $BonanzaVisitNumber;
        }

        if ($BonanzaReferralNumber) {
            $Desc = $Desc . ", Bonanza referral number changed from " . $Bonanza->BonanzaReferralNumber . " to " . $BonanzaReferralNumber;
            $Bonanza->BonanzaReferralNumber = $BonanzaReferralNumber;
        }
        if ($BonanzaStartTime) {
            $Desc = $Desc . ", Bonanza start time changed from " . $Bonanza->BonanzaStartTime . " to " . $BonanzaStartTime;
            $Bonanza->BonanzaStartTime = $BonanzaStartTime;
        }
        if ($BonanzaEndTime) {
            $Desc = $Desc . ", Bonanza end time changed from " . $Bonanza->BonanzaEndTime . " to " . $BonanzaEndTime;
            $Bonanza->BonanzaEndTime = $BonanzaEndTime;
        }
        if ($BonanzaPrize == "Points") {
            $Desc = $Desc . ", Bonanza cheque value changed from " . $Bonanza->BonanzaChequeValue . " to " . 0;
            $Bonanza->BonanzaChequeValue = 0;
            if ($BonanzaRewardPoints) {
                $Desc = $Desc . ", Bonanza reward points changed from " . $Bonanza->BonanzaRewardPoints . " to " . $BonanzaRewardPoints;
                $Bonanza->BonanzaRewardPoints = $BonanzaRewardPoints;
            }
        } else if ($BonanzaPrize == "Cheque") {
            $Desc = $Desc . ", Bonanza points changed from " . $Bonanza->BonanzaRewardPoints . " to " . 0;
            $Bonanza->BonanzaRewardPoints = 0;
            if ($BonanzaChequeValue) {
                $Desc = $Desc . ", Bonanza cheque value changed from " . $Bonanza->BonanzaChequeValue . " to " . $BonanzaChequeValue;
                $Bonanza->BonanzaChequeValue = $BonanzaChequeValue;
            }
        }
        $Bonanza->BonanzaStatus = "PENDING";
        $Bonanza->save();

        ActionBackLog($Admin->IDUser, $Bonanza->IDBonanza, "EDIT_BONANZA", $Desc);
        return RespondWithSuccessRequest(8);
    }

    public function BonanzaBrandList(Request $request)
    {
        $User = auth('user')->user();
        $IDBonanza = $request->IDBonanza;
        $IDCategory = $request->IDCategory;
        $IDSubCategory = $request->IDSubCategory;
        $UserLanguage = AdminLanguage($User->UserLanguage);
        $BrandName = "BrandName" . $UserLanguage;
        if (!$IDBonanza) {
            return RespondWithBadRequest(1);
        }

        if (!$IDCategory && !$IDSubCategory) {
            $Brands = Brand::where("BrandStatus", "ACTIVE")->select("IDBrand", "BrandNameEn", "BrandNameAr", "BrandLogo")->get();
        }
        if ($IDCategory && !$IDSubCategory) {
            $Brands = Brand::leftjoin("brandproducts", "brandproducts.IDBrand", "brands.IDBrand")->leftjoin("subcategories", "subcategories.IDSubCategory", "brandproducts.IDSubCategory")->where("brands.BrandStatus", "ACTIVE")->where("brandproducts.BrandProductStatus", "ACTIVE")->where("subcategories.IDCategory", $IDCategory)->select("brands.IDBrand", "brands.BrandNameEn", "brands.BrandNameAr", "brands.BrandLogo")->groupby("brands.IDBrand")->get();
        }
        if ($IDSubCategory) {
            $Brands = Brand::leftjoin("brandproducts", "brandproducts.IDBrand", "brands.IDBrand")->where("brands.BrandStatus", "ACTIVE")->where("brandproducts.IDSubCategory", $IDSubCategory)->where("brandproducts.BrandProductStatus", "ACTIVE")->select("brands.IDBrand", "brands.BrandNameEn", "brands.BrandNameAr", "brands.BrandLogo")->groupby("brands.IDBrand")->get();
        }
        foreach ($Brands as $Brand) {
            $BonanzaBrand = BonanzaBrand::where("IDBonanza", $IDBonanza)->where("IDBrand", $Brand->IDBrand)->select("BonanzaBrandDeleted", "BonanzaBrandVisitNumber")->first();
            if ($BonanzaBrand) {
                $Brand->BonanzaBrandDeleted = $BonanzaBrand->BonanzaBrandDeleted;
                $Brand->BonanzaBrandVisitNumber = $BonanzaBrand->BonanzaBrandVisitNumber;
            } else {
                $Brand->BonanzaBrandDeleted = 1;
                $Brand->BonanzaBrandVisitNumber = 0;
            }
            $Brand->BrandLogo = ($Brand->BrandLogo) ? asset($Brand->BrandLogo) : '';
            $Brand->BrandName = $Brand->$BrandName;
            unset($Brand["BrandNameEn"]);
            unset($Brand["BrandNameAr"]);
        }

        $APICode = APICode::where('IDAPICode', 8)->first();
        $Response = array(
            'Success' => true,
            'ApiMsg' => __('apicodes.' . $APICode->IDApiCode),
            'ApiCode' => $APICode->IDApiCode,
            'Response' => $Brands,
        );
        return $Response;
    }

    public function BonanzaBrandStatus(Request $request)
    {
        $Admin = auth('user')->user();
        $IDBrand = $request->IDBrand;
        $IDBonanza = $request->IDBonanza;
        $BonanzaStatus = $request->BonanzaStatus;
        $BonanzaBrandVisitNumber = $request->BonanzaBrandVisitNumber;
        if (!$IDBrand) {
            return RespondWithBadRequest(1);
        }
        if (!$IDBonanza) {
            return RespondWithBadRequest(1);
        }

        $BonanzaBrand = BonanzaBrand::where("IDBrand", $IDBrand)->where("IDBonanza", $IDBonanza)->first();
        if ($BonanzaBrand) {
            if ($BonanzaStatus) {
                $BonanzaBrand->BonanzaBrandDeleted = !$BonanzaBrand->BonanzaBrandDeleted;
            }
            if ($BonanzaBrandVisitNumber) {
                $BonanzaBrand->BonanzaBrandVisitNumber = $BonanzaBrandVisitNumber;
            }
            if ($BonanzaBrandVisitNumber == 0) {
                $BonanzaBrand->BonanzaBrandVisitNumber = 0;
            }
            $Desc = "brand status in bonanza changed with visit number " . $BonanzaBrand->BonanzaBrandVisitNumber;
            $BonanzaBrand->save();
        } else {
            $BonanzaBrand = new BonanzaBrand;
            $BonanzaBrand->IDBrand = $IDBrand;
            $BonanzaBrand->IDBonanza = $IDBonanza;
            if (!$BonanzaBrandVisitNumber) {
                $BonanzaBrand->BonanzaBrandVisitNumber = 0;
            } else {
                $BonanzaBrand->BonanzaBrandVisitNumber = $BonanzaBrandVisitNumber;
            }
            $Desc = "New brand added to bonanza with visit number " . $BonanzaBrandVisitNumber;
            $BonanzaBrand->save();
        }

        ActionBackLog($Admin->IDUser, $BonanzaBrand->IDBonanza, "EDIT_BONANZA", $Desc);
        return RespondWithSuccessRequest(8);
    }

    public function BonanzaClients(Request $request, ClientBonanza $ClientBonanza)
    {
        $IDPage = $request->IDPage;
        $IDClient = $request->IDClient;
        $IDBonanza = $request->IDBonanza;
        if (!$IDPage) {
            $IDPage = 0;
        } else {
            $IDPage = ($request->IDPage - 1) * 20;
        }

        $ClientBonanza = $ClientBonanza->leftjoin("clients", "clients.IDClient", "clientbonanza.IDClient")->leftjoin("bonanza", "bonanza.IDBonanza", "clientbonanza.IDBonanza");
        if ($IDBonanza) {
            $ClientBonanza = $ClientBonanza->where("clientbonanza.IDBonanza", $IDBonanza);
        }
        if ($IDClient) {
            $ClientBonanza = $ClientBonanza->where("clientbonanza.IDClient", $IDClient);
        }

        $ClientBonanzaNumber = $ClientBonanza->count();
        $Pages = ceil($ClientBonanza->count() / 20);
        $ClientBonanza = $ClientBonanza->orderby("clientbonanza.IDBonanza", "DESC")->select("clients.IDClient", "clients.ClientName", "clients.ClientPhone", "bonanza.BonanzaTitleEn", "bonanza.BonanzaTitleAr", "clientbonanza.ClientLeftPoints", "clientbonanza.ClientRightPoints", "clientbonanza.ClientTotalPoints", "clientbonanza.ClientVisitNumber", "clientbonanza.BrandVisit", "clientbonanza.BonanzaReferralNumber")->skip($IDPage)->take(20)->get();
        $ClientBonanza = ClientBonanzaResource::collection($ClientBonanza);
        $Response = array("ClientBonanza" => $ClientBonanza, "ClientBonanzaNumber" => $ClientBonanzaNumber, "Pages" => $Pages);

        $APICode = APICode::where('IDAPICode', 8)->first();
        $Response = array(
            'Success' => true,
            'ApiMsg' => __('apicodes.' . $APICode->IDApiCode),
            'ApiCode' => $APICode->IDApiCode,
            'Response' => $Response,
        );
        return $Response;
    }

    public function PositionClients(Request $request)
    {
        $IDPosition = $request->IDPosition;
        $IDPage = $request->IDPage;
        if (!$IDPosition) {
            return RespondWithBadRequest(1);
        }

        if (!$IDPage) {
            $IDPage = 0;
        } else {
            $IDPage = ($request->IDPage - 1) * 20;
        }

        $IDPosition = $request->IDPosition;
        $IDPage = $request->IDPage;
        if (!$IDPosition) {
            return RespondWithBadRequest(1);
        }

        if (!$IDPage) {
            $IDPage = 0;
        } else {
            $IDPage = ($request->IDPage - 1) * 20;
        }

        $position = Position::findOrFail($IDPosition);
        $FullData = $position->positionforclients()->with(["client", "position"])->get();

        $APICode = APICode::where('IDAPICode', 8)->first();
        $Response = array(
            'Success' => true,
            'ApiMsg' => __('apicodes.' . $APICode->IDApiCode),
            'ApiCode' => $APICode->IDApiCode,
            'Response' => PositionsForClients::collection($FullData)
        );
        return $Response;
    }
    public function PositionClientsReject($id)
    {
        $PositionForClient = ClientPositionsForClients::where("IDPositionForClient", $id)->first();

        $PositionForClient->update([
            'Status' => "REJECTED",
        ]);

        $APICode = APICode::where('IDAPICode', 8)->first();
        $Response = array(
            'Success' => true,
            'ApiMsg' => __('apicodes.' . $APICode->IDApiCode),
            'ApiCode' => $APICode->IDApiCode,
            'Response' => null,
        );
        return $Response;
    }

    public function ClientChatList(Request $request)
    {
        $IDClient = $request->IDClient;
        $Client = Client::find($IDClient);
        if (!$Client) {
            return RespondWithBadRequest(1);
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

        foreach ($ClientChat as $Chat) {
            if ($IDClient == $Chat->IDClient) {
                $FriendName = $Chat->FriendName;
                $FriendPicture = $Chat->FriendPicture;
                if ($Chat->FriendPrivacy) {
                    $FriendPicture = Null;
                }
            } else {
                $FriendName = $Chat->ClientName;
                $FriendPicture = $Chat->ClientPicture;
                if ($Chat->ClientPrivacy) {
                    $FriendPicture = Null;
                }
            }
            $FriendPicture = ($FriendPicture) ? asset($FriendPicture) : '';
            $Chat->FriendName = $FriendName;
            $Chat->FriendPicture = $FriendPicture;
            unset($Chat['IDClient']);
            unset($Chat['IDFriend']);
            unset($Chat['ClientName']);
            unset($Chat['ClientPicture']);
            unset($Chat['ClientPrivacy']);
            unset($Chat['FriendPrivacy']);
        }

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

    public function ClientChatDetails(Request $request)
    {
        $IDClient = $request->IDClient;
        $IDClientChat = $request->IDClientChat;
        $IDPage = $request->IDPage;
        if (!$IDPage) {
            $IDPage = 0;
        } else {
            $IDPage = ($request->IDPage - 1) * 20;
        }

        $ClientChat = ClientChat::find($IDClientChat);
        if (!$IDClientChat) {
            return RespondWithBadRequest(1);
        }
        if (!$IDClient) {
            return RespondWithBadRequest(1);
        }

        $ClientChatDetails = ClientChatDetail::leftjoin("clients", "clients.IDClient", "clientchatdetails.IDSender")->where("clientchatdetails.IDClientChat", $IDClientChat);
        $ClientChatDetails = $ClientChatDetails->select("clientchatdetails.IDClientChatDetails", "clientchatdetails.IDSender", "clientchatdetails.Message", "clientchatdetails.MessageType", "clientchatdetails.MessageStatus", "clientchatdetails.created_at", "clientchatdetails.updated_at", "clients.ClientName", "clients.ClientPicture", "clients.ClientPrivacy")->orderby("clientchatdetails.IDClientChatDetails", "DESC");
        $Pages = ceil($ClientChatDetails->count() / 20);
        $ClientChatDetails = $ClientChatDetails->skip($IDPage)->take(20)->get();

        foreach ($ClientChatDetails as $Chat) {
            $Sender = "CLIENT";
            if ($IDClient != $Chat->IDSender) {
                $Sender = "FRIEND";
                if ($Chat->FriendPrivacy) {
                    $Chat->ClientPicture = Null;
                }
            }
            $ClientPicture = ($Chat->ClientPicture) ? asset($Chat->ClientPicture) : '';
            $Chat->ClientPicture = $ClientPicture;
            $Chat->Sender = $Sender;
            if ($Chat->MessageType != "TEXT") {
                $Chat->MessageType = ($Chat->MessageType) ? asset($Chat->MessageType) : '';
            }
        }

        $Response = array("ClientChatDetails" => $ClientChatDetails, "Pages" => $Pages);

        $APICode = APICode::where('IDAPICode', 8)->first();
        $Response = array(
            'Success' => true,
            'ApiMsg' => __('apicodes.' . $APICode->IDApiCode),
            'ApiCode' => $APICode->IDApiCode,
            'Response' => $Response
        );
        return $Response;
    }
    public function ClientCheck(Request $request)
    {
        $Type = $request->Type;
        $UserName = $request->UserName;
        $IDReferral = $request->IDReferral;
        if (!$UserName) {
            $Clients = Client::select("IDClient", "ClientName", "ClientPicture", "ClientPhone", "ClientAppID", "ClientPrivacy")->get();
            foreach ($Clients as $Client) {
                if ($Client->ClientPrivacy) {
                    $Client->ClientPicture = '';
                } else {
                    $Client->ClientPicture = ($Client->ClientPicture) ? asset($Client->ClientPicture) : '';
                }
            }
        } else {

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
    public function UploadClientContract(Request $request)
    {
        $Client = Client::find($request->client_id);

        if (!$Client) {
            return RespondWithBadRequest(23);
        }

        if ($request->hasFile('contract')) {

            $document = $request->file('contract');
            $id = $request->client_id;

            $filePath = SaveContract($document, $id);

            $ClientDocument = new ClientDocument;
            $ClientDocument->IDClient = $Client->IDClient;
            $ClientDocument->ClientDocumentPath = $filePath;
            $ClientDocument->ClientDocumentType = "CONTRACT";
            $ClientDocument->save();

            $url = asset("uploads/" . $filePath);

            $APICode = APICode::where('IDAPICode', 58)->first();
            $Response = array(
                'Success' => true,
                'ApiMsg' => __('apicodes.' . $APICode->IDApiCode),
                'ApiCode' => $APICode->IDApiCode,
                'Response' => $url
            );
            return $Response;
        } else {
            return response()->json(['error' => 'No file attached in the request'], 400);
        }
    }
    public function ClientContracts(Request $request)
    {
        $Client = Client::find($request->client_id);

        if (!$Client) {
            return RespondWithBadRequest(23);
        }

        $contracts = ClientDocument::where("IDClient", $Client->IDClient)->where("ClientDocumentType", "CONTRACT")->get();

        $APICode = APICode::where('IDAPICode', 8)->first();
        $Response = array(
            'Success' => true,
            'ApiMsg' => __('apicodes.' . $APICode->IDApiCode),
            'ApiCode' => $APICode->IDApiCode,
            'Response' => ClientContractsResource::collection($contracts)
        );
        return $Response;
    }
    public function ClientDeleteContract($id)
    {
        $Contract = ClientDocument::find($id);
        $Contract && DeleteContract($Contract->ClientDocumentPath);
        $Contract->delete();
        $APICode = APICode::where('IDAPICode', 8)->first();
        $Response = array(
            'Success' => true,
            'ApiMsg' => __('apicodes.' . $APICode->IDApiCode),
            'ApiCode' => $APICode->IDApiCode,
            'Response' => null
        );
        return $Response;
    }
}
