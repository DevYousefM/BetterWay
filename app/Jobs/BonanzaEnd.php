<?php

namespace App\Jobs;

use App\V1\Client\Client;
use App\V1\Client\ClientBonanza;
use App\V1\Client\ClientBrandProduct;
use App\V1\Plan\Bonanza;
use App\V1\Plan\BonanzaBrand;
use App\V1\Plan\PlanNetwork;
use App\V1\Payment\CompanyLedger;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use DateTime;
use DateInterval;

class BonanzaEnd implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public $tries = 3;

    protected $signature = 'log:cron';

    public function __construct() {}

    public function handle()
    {
        $CurrentTime = new DateTime('now');
        $CurrentTime = $CurrentTime->format('Y-m-d H:i:s');

        $Clients = Client::where("ClientStatus", "ACTIVE")->where("ClientDeleted", 0)->get();
        $Bonanzas = Bonanza::where('BonanzaStatus', 'ACTIVE')->where("BonanzaEndTime", "<", $CurrentTime)->get();
        foreach ($Clients as $Client) {
            $IDClient = $Client->IDClient;
            foreach ($Bonanzas as $Bonanza) {
                if ($Bonanza->BonanzaLeftPoints) {
                    if ($Client->ClientLeftPoints < $Bonanza->BonanzaLeftPoints) {
                        continue;
                    }
                }
                if ($Bonanza->BonanzaRightPoints) {
                    if ($Client->ClientRightPoints < $Bonanza->BonanzaRightPoints) {
                        continue;
                    }
                }
                if ($Bonanza->BonanzaTotalPoints) {
                    if ($Client->ClientTotalPoints < $Bonanza->BonanzaTotalPoints) {
                        continue;
                    }
                }

                $PlanNetwork = 0;
                if ($Bonanza->BonanzaReferralNumber) {
                    $PlanNetwork = PlanNetwork::where("IDReferralClient", $IDClient)->count();
                    if ($PlanNetwork < $Bonanza->BonanzaReferralNumber) {
                        continue;
                    }
                }

                $ClientProductValue = 0;
                if ($Bonanza->BonanzaProductValue) {
                    $ClientBrandProduct = ClientBrandProduct::where("IDClient", $IDClient)->where("ClientBrandProductStatus", "USED")->sum("ProductTotalAmount");
                    if ($ClientBrandProduct < $Bonanza->BonanzaProductValue) {
                        continue;
                    }
                    $ClientProductValue = $ClientBrandProduct;
                }

                $ClientVisitNumber = 0;
                $BrandVisit = 0;
                $BonanzaBrands = BonanzaBrand::where("IDBonanza", $Bonanza->IDBonanza)->where("BonanzaBrandDeleted", 0)->get();
                if (count($BonanzaBrands) || $Bonanza->BonanzaVisitNumber) {
                    if ($Bonanza->BonanzaVisitNumber) {
                        $ClientBrandProduct = ClientBrandProduct::where("IDClient", $IDClient)->where("ClientBrandProductStatus", "USED")->count();
                        if ($ClientBrandProduct < $Bonanza->BonanzaVisitNumber) {
                            continue;
                        }
                        $ClientVisitNumber = $ClientBrandProduct;
                    }

                    if (count($BonanzaBrands)) {
                        $Flag = True;
                        foreach ($BonanzaBrands as $BonanzaBrand) {
                            $ClientBrandProduct = ClientBrandProduct::leftjoin("brandproducts", "brandproducts.IDBrandProduct", "clientbrandproducts.IDBrandProduct")->where("clientbrandproducts.IDClient", $IDClient)->where("clientbrandproducts.ClientBrandProductStatus", "USED")->where("brandproducts.IDBrand", $BonanzaBrand->IDBrand)->count();
                            if ($ClientBrandProduct < $BonanzaBrand->BonanzaBrandVisitNumber) {
                                $Flag = False;
                                break;
                            }
                        }
                        if (!$Flag) {
                            continue;
                        }
                        $BrandVisit = 1;
                    }
                }

                $ClientBonanza = new ClientBonanza;
                $ClientBonanza->IDBonanza = $Bonanza->IDBonanza;
                $ClientBonanza->IDClient = $IDClient;
                $ClientBonanza->ClientLeftPoints = $Client->ClientLeftPoints;
                $ClientBonanza->ClientRightPoints = $Client->ClientRightPoints;
                $ClientBonanza->ClientTotalPoints = $Client->ClientTotalPoints;
                $ClientBonanza->ClientProductValue = $ClientProductValue;
                $ClientBonanza->BonanzaReferralNumber = $PlanNetwork;
                $ClientBonanza->ClientVisitNumber = $ClientVisitNumber;
                $ClientBonanza->BrandVisit = $BrandVisit;
                $ClientBonanza->save();

                $BatchNumber = "#B" . $ClientBonanza->IDClientBonanza;
                $TimeFormat = new DateTime('now');
                $Time = $TimeFormat->format('H');
                $Time = $Time . $TimeFormat->format('i');
                $BatchNumber = $BatchNumber . $Time;
                AdjustLedger($Client, $Bonanza->BonanzaChequeValue, $Bonanza->BonanzaRewardPoints, 0, 0, Null, "BONANZA", "WALLET", "REWARD", $BatchNumber);
                sendFirebaseNotification($Client, $Bonanza, "bonanza", 'bonanza end and you got a prize');
                
                $Bonanza->BonanzaStatus = "EXPIRED";
                $Bonanza->save();

                $CompanyLedger = new CompanyLedger;
                $CompanyLedger->IDSubCategory = 22;
                $CompanyLedger->CompanyLedgerAmount = $Bonanza->BonanzaChequeValue;
                $CompanyLedger->CompanyLedgerDesc = "Bonanza Payment to Client " . $Client->ClientName;
                $CompanyLedger->CompanyLedgerProcess = "AUTO";
                $CompanyLedger->CompanyLedgerType = "DEBIT";
                $CompanyLedger->save();
            }
        }
    }
}
