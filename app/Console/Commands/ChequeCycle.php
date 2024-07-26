<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\V1\Client\Client;
use App\V1\Plan\Plan;
use App\V1\Plan\PlanNetwork;
use App\V1\Plan\PlanNetworkCheque;
use App\V1\Plan\PlanNetworkChequeDetail;
use App\V1\Payment\CompanyLedger;
use Carbon\Carbon;
use DateTime;
use Illuminate\Support\Facades\Log;

class ChequeCycle extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dispatch:cheque-cycle';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'dispatch cheque cycle';

    /**
     * Create a new command instance.
     *
     * @return void
     */
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
        Log::info("Handler started");
        $CurrentTime = new DateTime('now');
        $Day = strtoupper($CurrentTime->format('l'));
        Log::info("Current Day: " . $Day);

        $Plans = Plan::where("PlanStatus", "ACTIVE")->where('ChequeEarnDay', 'like', '%' . $Day . '%')->get();
        Log::info("Number of active plans: " . count($Plans));
        foreach ($Plans as $Plan) {
            Log::info("Processing Plan ID: " . $Plan->IDPlan);

            $LeftBalanceNumber = $Plan->LeftBalanceNumber;
            $RightBalanceNumber = $Plan->RightBalanceNumber;
            $LeftMaxOutNumber = $Plan->LeftMaxOutNumber;
            $RightMaxOutNumber = $Plan->RightMaxOutNumber;
            $PlanChequeValue = $Plan->ChequeValue;
            $ChequeMaxOut = $Plan->ChequeMaxOut;

            $PlanNetwork = PlanNetwork::where("IDPlan", $Plan->IDPlan)->get();
            Log::info("PlanNetwork count for Plan ID " . $Plan->IDPlan . ": " . count($PlanNetwork));

            foreach ($PlanNetwork as $Person) {
                $IDClient = $Person->IDClient;
                $AgencyNumber = $Person->PlanNetworkAgencyNumber;
                $Counter = 1;
                $AmountGet = 0;
                $PreviousBalance = $Person->ClientBalance;

                while ($Counter <= $AgencyNumber) {
                    Log::info("Processing Agency Number: " . $Counter);

                    $LeftNetworkNumber = 0;
                    $RightNetworkNumber = 0;
                    $ChequeValue = 0;

                    $PreviousNetworkClients = PlanNetworkChequeDetail::where("IDClient", $IDClient)->pluck("IDClientNetwork")->toArray();
                    $LeftNetwork = PlanNetwork::where("IDParentClient", $IDClient)->where("PlanNetworkAgency", $Counter)->where("PlanNetworkPosition", "LEFT")->first();
                    $RightNetwork = PlanNetwork::where("IDParentClient", $IDClient)->where("PlanNetworkAgency", $Counter)->where("PlanNetworkPosition", "RIGHT")->first();

                    if ($LeftNetwork) {
                        Log::info("Left Network found for IDClient: " . $IDClient . " at Counter: " . $Counter);

                        $IDClient = $LeftNetwork->IDClient;
                        $Key = $IDClient . "-";
                        $SecondKey = $IDClient . "-";
                        $ThirdKey = "-" . $IDClient;
                        $AllNetwork = PlanNetwork::leftjoin("clients", "clients.IDClient", "plannetwork.IDClient")->leftjoin("clients as C1", "C1.IDClient", "plannetwork.IDReferralClient")->where("plannetwork.PlanNetworkAgency", $Counter)->whereNotIn("plannetwork.IDClient", $PreviousNetworkClients);
                        $AllNetwork = $AllNetwork->where(function ($query) use ($IDClient, $Key, $SecondKey, $ThirdKey) {
                            $query->where("plannetwork.PlanNetworkPath", 'like', $IDClient . '%')
                                ->orwhere("plannetwork.PlanNetworkPath", $IDClient)
                                ->orwhere("plannetwork.PlanNetworkPath", 'like', $Key . '%')
                                ->orwhere("plannetwork.PlanNetworkPath", 'like', '%' . $SecondKey . '%')
                                ->orwhere("plannetwork.PlanNetworkPath", 'like', '%' . $ThirdKey . '%');
                        });

                        $LeftNetworkNumber = $AllNetwork->count();
                        $LeftNetwork = $AllNetwork->select("plannetwork.IDClient")->get()->pluck("IDClient")->toArray();
                        if (!in_array($IDClient, $PreviousNetworkClients)) {
                            array_push($LeftNetwork, $IDClient);
                            $LeftNetworkNumber++;
                        }
                        Log::info("Left Network Number: " . $LeftNetworkNumber);
                    }

                    if ($RightNetwork) {
                        Log::info("Right Network found for IDClient: " . $IDClient . " at Counter: " . $Counter);

                        $IDClient = $RightNetwork->IDClient;
                        $Key = $IDClient . "-";
                        $SecondKey = $IDClient . "-";
                        $ThirdKey = "-" . $IDClient;
                        $AllNetwork = PlanNetwork::leftjoin("clients", "clients.IDClient", "plannetwork.IDClient")->leftjoin("clients as C1", "C1.IDClient", "plannetwork.IDReferralClient")->where("plannetwork.PlanNetworkAgency", $Counter)->whereNotIn("plannetwork.IDClient", $PreviousNetworkClients);
                        $AllNetwork = $AllNetwork->where(function ($query) use ($IDClient, $Key, $SecondKey, $ThirdKey) {
                            $query->where("plannetwork.PlanNetworkPath", 'like', $IDClient . '%')
                                ->orwhere("plannetwork.PlanNetworkPath", $IDClient)
                                ->orwhere("plannetwork.PlanNetworkPath", 'like', $Key . '%')
                                ->orwhere("plannetwork.PlanNetworkPath", 'like', '%' . $SecondKey . '%')
                                ->orwhere("plannetwork.PlanNetworkPath", 'like', '%' . $ThirdKey . '%');
                        });

                        $RightNetworkNumber = $AllNetwork->count();
                        $RightNetwork = $AllNetwork->select("plannetwork.IDClient")->get()->pluck("IDClient")->toArray();
                        if (!in_array($IDClient, $PreviousNetworkClients)) {
                            array_push($RightNetwork, $IDClient);
                            $RightNetworkNumber++;
                        }
                        Log::info("Right Network Number: " . $RightNetworkNumber);
                    }

                    if ($LeftNetworkNumber > $LeftMaxOutNumber) {
                        $LeftNetworkNumber = $LeftMaxOutNumber;
                    }
                    if ($RightNetworkNumber > $RightMaxOutNumber) {
                        $RightNetworkNumber = $RightMaxOutNumber;
                    }

                    if ($LeftBalanceNumber <= $LeftNetworkNumber && $RightBalanceNumber <= $RightNetworkNumber) {

                        $LeftNumber = intdiv($LeftNetworkNumber, $LeftBalanceNumber);
                        $RightNumber = intdiv($RightNetworkNumber, $RightBalanceNumber);
                        if ($LeftNumber <= $RightNumber) {
                            $Number = $LeftNumber;
                        }
                        if ($RightNumber <= $LeftNumber) {
                            $Number = $RightNumber;
                        }
                        $ChequeValue = $Number * $PlanChequeValue;
                        Log::info("Cheque Value: " . $ChequeValue);

                        $LeftNumber = $Number * $LeftBalanceNumber;
                        $RightNumber = $Number * $RightBalanceNumber;
                        if ($LeftNumber <= $RightNumber) {
                            $Number = $LeftNumber;
                        }
                        if ($RightNumber <= $LeftNumber) {
                            $Number = $RightNumber;
                        }
                        $IDClient = $Person->IDClient;
                        $Client = Client::find($IDClient);

                        $PlanNetworkCheque = new PlanNetworkCheque;
                        $PlanNetworkCheque->IDPlanNetwork = $Person->IDPlanNetwork;
                        $PlanNetworkCheque->ChequeLeftNumber = $Number;
                        $PlanNetworkCheque->ChequeRightNumber = $Number;
                        $PlanNetworkCheque->ChequeLeftReachedNumber = $LeftNetworkNumber;
                        $PlanNetworkCheque->ChequeRightReachedNumber = $RightNetworkNumber;
                        $PlanNetworkCheque->ChequeValue = $ChequeValue;
                        $PlanNetworkCheque->AgencyNumber = $Counter;
                        $PlanNetworkCheque->save();


                        Log::info("PlanNetworkCheque saved with ID: " . $PlanNetworkCheque->IDPlanNetworkCheque);

                        ChequesLedger($Client, $ChequeValue, 'CHEQUE', "WALLET", 'CHEQUE', GenerateBatch("CH", $Client->IDClient));

                        $CompanyLedger = new CompanyLedger;
                        $CompanyLedger->IDSubCategory = 19;
                        $CompanyLedger->CompanyLedgerAmount = $ChequeValue;
                        $CompanyLedger->CompanyLedgerDesc = "Cheque Payment to Client " . $Client->ClientName;
                        $CompanyLedger->CompanyLedgerProcess = "AUTO";
                        $CompanyLedger->CompanyLedgerType = "DEBIT";
                        $CompanyLedger->save();

                        $IDPlanNetworkCheque = $PlanNetworkCheque->IDPlanNetworkCheque;

                        for ($I = 0; $I < $Number; $I++) {
                            $PlanNetworkChequeDetail = new PlanNetworkChequeDetail;
                            $PlanNetworkChequeDetail->IDPlanNetworkCheque = $IDPlanNetworkCheque;
                            $PlanNetworkChequeDetail->IDClient = $IDClient;
                            $PlanNetworkChequeDetail->IDClientNetwork = $LeftNetwork[$I];
                            $PlanNetworkChequeDetail->save();

                            $PlanNetworkChequeDetail = new PlanNetworkChequeDetail;
                            $PlanNetworkChequeDetail->IDPlanNetworkCheque = $IDPlanNetworkCheque;
                            $PlanNetworkChequeDetail->IDClient = $IDClient;
                            $PlanNetworkChequeDetail->IDClientNetwork = $RightNetwork[$I];
                            $PlanNetworkChequeDetail->save();
                        }
                        Log::info("PlanNetworkChequeDetail records created for IDPlanNetworkCheque: " . $IDPlanNetworkCheque);
                    }

                    $Counter++;
                }
            }
        }
        Log::info("Handler completed");
        Log::info(" ");
    }
}
