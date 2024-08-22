<?php

namespace App\Console\Commands;

use App\V1\Client\Client;
use App\V1\Payment\CompanyLedger;
use App\V1\Plan\PlanNetwork;
use App\V1\Plan\PlanProduct;
use App\V1\Plan\PlanProductUpgrade;
use DateInterval;
use DateTime;
use Illuminate\Console\Command;

class ProductRenewal extends Command
{
    protected $signature = 'product:renewal';

    protected $description = 'Command description';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $CurrentTime = new DateTime('now');
        $CurrentTime = $CurrentTime->format('Y-m-d H:i:s');

        $PlanNetwork = PlanNetwork::leftjoin("clients", "clients.IDClient", "plannetwork.IDClient")->where("plannetwork.PlanNetworkExpireDate", "<", $CurrentTime)->where("clients.ClientDeleted", 0)->where("clients.ClientStatus", "ACTIVE")->get();
        foreach ($PlanNetwork as $Network) {
            $PlanProduct = PlanProduct::find($Network->PlanProduct);
            $PlanProductPrice = $PlanProduct->PlanProductPrice;
            $UpgradePrice = 0;
            if ($Network->PlanProductUpgrade) {
                $PlanProductUpgrade = PlanProductUpgrade::find($Network->PlanProductUpgrade);
                $UpgradePrice = $PlanProductUpgrade->UpgradePrice;
            }

            $Amount = $PlanProductPrice + $UpgradePrice;
            $Client = Client::find($Network->IDClient);
            if ($Network->ClientBalance >= $Amount) {

                $BatchNumber = "#R" . $Network->IDPlanNetwork;
                $TimeFormat = new DateTime('now');
                $Time = $TimeFormat->format('H');
                $Time = $Time . $TimeFormat->format('i');
                $BatchNumber = $BatchNumber . $Time;

                AdjustLedger($Client, -$Amount, 0, 0, 0, Null, "WALLET", "PLAN_PRODUCT", "PAYMENT", $BatchNumber);

                $CompanyLedger = new CompanyLedger();
                $CompanyLedger->IDSubCategory = 25;
                $CompanyLedger->CompanyLedgerAmount = $Amount;
                $CompanyLedger->CompanyLedgerDesc = "Product Activation by Client " . $Client->ClientName;
                $CompanyLedger->CompanyLedgerProcess = "AUTO";
                $CompanyLedger->CompanyLedgerType = "CREDIT";
                $CompanyLedger->save();

                $PlanNetworkExpireDate = GeneralSettings('PlanNetworkExpireDate');
                $PlanNetworkExpireDate = $PlanNetworkExpireDate * 24 * 60 * 60;
                $Date = new DateTime('now');
                $PlanNetworkExpireDate = $Date->add(new DateInterval('PT' . $PlanNetworkExpireDate . 'S'));
                $PlanNetworkExpireDate = $PlanNetworkExpireDate->format('Y-m-d H:i:s');

                $Network->PlanNetworkExpireDate = $PlanNetworkExpireDate;
                $Network->save();
            } else {
                $Client->ClientStatus = "INACTIVE";
                $Client->save();
            }
        }
    }
}
