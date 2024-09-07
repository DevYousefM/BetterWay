<?php

namespace App\Console\Commands;

use App\V1\Client\Client;
use App\V1\Client\ClientBonanza;
use App\V1\Client\ClientBrandProduct;
use App\V1\Payment\CompanyLedger;
use App\V1\Plan\Bonanza;
use App\V1\Plan\BonanzaBrand;
use App\V1\Plan\PlanNetwork;
use Carbon\Carbon;
use DateTime;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class BonanzaEnd extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bonanza:end';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Bonanza End';

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
        Log::info("Bonanza End started");
        $CurrentTime = new DateTime('now');
        $formattedTime = $CurrentTime->format('Y-m-d H:i:s');
        Log::info($formattedTime);

        $Clients = Client::where("ClientStatus", "ACTIVE")->where("ClientDeleted", 0)->get();
        $Bonanzas = Bonanza::where('BonanzaStatus', 'ACTIVE')->where("BonanzaEndTime", "<", $CurrentTime)->with(['bonanza_brands', 'bonanza_brands.brand'])->get();

        foreach ($Bonanzas as $Bonanza) {
            $Bonanza->BonanzaStatus = "EXPIRED";
            $Bonanza->save();
            foreach ($Clients as $Client) {
                $IDClient = $Client->IDClient;

                $StartDate = $Bonanza->BonanzaStartTime;
                $EndDate = $Bonanza->BonanzaEndTime;
                Log::info("BON: $Bonanza->BonanzaTitleEn");
                Log::info("C: $Client->ClientName");

                $BonanzaLeftPoints = $Bonanza->BonanzaLeftPoints;
                $BonanzaRightPoints = $Bonanza->BonanzaRightPoints;
                if ($BonanzaLeftPoints > 0 && $BonanzaRightPoints > 0) {
                    if (!$this->checkBalancePoints($Client, $BonanzaLeftPoints, $BonanzaRightPoints, $StartDate, $EndDate)) {
                        Log::info("B Balance Points");
                        Log::info("----------------------------------------");

                        continue;
                    }
                }

                $BonanzaTotalPoints = $Bonanza->BonanzaTotalPoints;
                if ($BonanzaTotalPoints > 0) {
                    if (!$this->checkTotalPoints($Client, $BonanzaTotalPoints, $StartDate, $EndDate)) {
                        Log::info("B Total Points");
                        Log::info("----------------------------------------");

                        continue;
                    }
                }

                $BonanzaLeftPersons = $Bonanza->BonanzaLeftPersons;
                $BonanzaRightPersons = $Bonanza->BonanzaRightPersons;
                if ($BonanzaLeftPersons > 0 && $BonanzaRightPersons > 0) {
                    if (!$this->checkBalancePersons($Client, $BonanzaLeftPersons, $BonanzaRightPersons, $StartDate, $EndDate)) {
                        Log::info("B Balance Persons");
                        Log::info("----------------------------------------");

                        continue;
                    }
                }

                $BonanzaTotalPersons = $Bonanza->BonanzaTotalPersons;
                if ($BonanzaTotalPersons > 0) {
                    if (!$this->checkTotalPersons($Client, $BonanzaTotalPersons, $StartDate, $EndDate)) {
                        Log::info("B Total Persons");
                        Log::info("----------------------------------------");

                        continue;
                    }
                }

                $BonanzaVisitNumber = $Bonanza->BonanzaVisitNumber;

                if ($BonanzaVisitNumber > 0) {
                    if (!$this->checkVisitsNumber($Client, $BonanzaVisitNumber, $StartDate, $EndDate)) {
                        Log::info("B Visits Number");
                        Log::info("----------------------------------------");

                        continue;
                    }
                }

                $IsBonanzaUniqueVisits = $Bonanza->IsBonanzaUniqueVisits;
                if ($IsBonanzaUniqueVisits) {
                    if (!$this->checkUniqueVisits($Client, $Bonanza, $StartDate, $EndDate)) {
                        Log::info("B Unique Visits");
                        Log::info("----------------------------------------");

                        continue;
                    }
                }

                $BonanzaReferralNumber = $Bonanza->BonanzaReferralNumber;
                if ($BonanzaReferralNumber > 0) {
                    if (!$this->checkReferralsNumber($Client, $BonanzaReferralNumber, $StartDate, $EndDate)) {
                        Log::info("B Referrals Number");
                        Log::info("----------------------------------------");

                        continue;
                    }
                }

                if ($Client->ClientStatus !== "ACTIVE") {
                    continue;
                }

                Log::info("Arrived");
                Log::info("Arrived:" . $Client->ClientName);
                Log::info("----------------------------------------");
                $ClientBonanza = new ClientBonanza;
                $ClientBonanza->IDBonanza = $Bonanza->IDBonanza;
                $ClientBonanza->IDClient = $IDClient;
                if ($BonanzaLeftPoints > 0 || $BonanzaRightPoints > 0) {
                    $ClientBonanza->ClientLeftPoints =   $this->getFilteredLeftPoints($Client, $StartDate, $EndDate)->sum('ClientLedgerPoints');
                    $ClientBonanza->ClientRightPoints = $this->getFilteredRightPoints($Client, $StartDate, $EndDate)->sum('ClientLedgerPoints');
                }
                if ($BonanzaTotalPoints > 0) {
                    $ClientBonanza->ClientTotalPoints = $this->getFilteredTotalPoints($Client, $StartDate, $EndDate)->sum('ClientLedgerPoints');
                }

                if ($BonanzaLeftPersons > 0 || $BonanzaRightPersons > 0) {
                    $ClientBonanza->ClientLeftPersons =  $this->getFilteredLeftPersons($Client, $StartDate, $EndDate)->count();
                    $ClientBonanza->ClientRightPersons = $this->getFilteredRightPersons($Client, $StartDate, $EndDate)->count();
                }

                if ($BonanzaTotalPersons > 0) {
                    $ClientBonanza->ClientTotalPersons = $this->getFilteredTotalPersons($Client, $StartDate, $EndDate)->count();
                }

                if ($BonanzaVisitNumber > 0) $ClientBonanza->ClientVisitNumber =  $this->getFilteredVisits($Client, $StartDate, $EndDate)->count();
                if ($BonanzaReferralNumber > 0) $ClientBonanza->BonanzaReferralNumber = $this->getFilteredReferral($Client, $StartDate, $EndDate)->count();
                $ClientBonanza->BrandVisit = 0;

                $ClientBonanza->save();

                $BatchNumber = "#B" . $ClientBonanza->IDClientBonanza;
                $TimeFormat = new DateTime('now');
                $Time = $TimeFormat->format('H');
                $Time = $Time . $TimeFormat->format('i');
                $BatchNumber = $BatchNumber . $Time;

                if ($Bonanza->BonanzaChequeValue > 0) {
                    ChequesLedger($Client, $Bonanza->BonanzaChequeValue, 'BONANZA',  'WALLET', "REWARD", $BatchNumber);
                }
                if ($Bonanza->BonanzaRewardPoints > 0) {
                    AdjustLedger($Client, 0, $Bonanza->BonanzaRewardPoints, 0, 0, Null, "BONANZA", "WALLET", "REWARD", $BatchNumber);
                }
                sendFirebaseNotification($Client, $Bonanza, "Congrats you have got the bonanza!", ' ');

                if ($Bonanza->BonanzaChequeValue > 0) {
                    CompanyLedger(22, $Bonanza->BonanzaChequeValue, "Bonanza Payment to Client " . $Client->ClientName, "AUTO", "DEBIT");
                }
            }
        }
        Log::info("Bonanza End End");
        Log::info("----------------------------------------");

        return 0;
    }

    function checkBalancePoints($client, $rightPointsNumber, $leftPointsNumber, $StartDate, $EndDate)
    {
        $startDate = Carbon::parse($StartDate);
        $endDate = Carbon::parse($EndDate);

        $rightPointsCount = $this->getFilteredRightPoints($client, $startDate, $endDate)->sum('ClientLedgerPoints');

        $leftPointsCount = $this->getFilteredLeftPoints($client, $startDate, $endDate)->sum('ClientLedgerPoints');
        Log::info("Points Right: $rightPointsCount, Points Left: $leftPointsCount");

        return $rightPointsCount >= $rightPointsNumber && $leftPointsCount >= $leftPointsNumber;
    }
    function getFilteredRightPoints($client, $startDate, $endDate)
    {
        $filteredPointsHistory = $client->points_history->filter(function ($point) use ($startDate, $endDate) {
            $createdAt = Carbon::parse($point->created_at);
            return $createdAt->between($startDate, $endDate);
        });

        return $filteredPointsHistory->filter(function ($point) {
            return $point->ClientLedgerPosition === 'RIGHT';
        });
    }
    function getFilteredLeftPoints($client, $startDate, $endDate)
    {
        $filteredPointsHistory = $client->points_history->filter(function ($point) use ($startDate, $endDate) {
            $createdAt = Carbon::parse($point->created_at);
            return $createdAt->between($startDate, $endDate);
        });
        return $filteredPointsHistory->filter(function ($point) {
            return $point->ClientLedgerPosition === 'LEFT';
        });
    }

    function checkTotalPoints($client, $totalPoints, $StartDate, $EndDate)
    {
        $startDate = Carbon::parse($StartDate);
        $endDate = Carbon::parse($EndDate);

        $filteredPointsHistory = $this->getFilteredTotalPoints($client, $startDate, $endDate);
        Log::info("Total Points: " . $filteredPointsHistory->count());
        $totalPointsCount = $filteredPointsHistory->sum('ClientLedgerPoints');
        return $totalPointsCount >= $totalPoints;
    }
    function getFilteredTotalPoints($client, $startDate, $endDate)
    {
        return $client->points_history->filter(function ($point) use ($startDate, $endDate) {
            $createdAt = Carbon::parse($point->created_at);
            return $createdAt->between($startDate, $endDate);
        });
    }
    function checkTotalPersons($client, $totalPersons, $StartDate, $EndDate)
    {
        $startDate = Carbon::parse($StartDate);
        $endDate = Carbon::parse($EndDate);

        $recentPersons = $this->getFilteredTotalPersons($client, $startDate, $endDate);
        Log::info("recentPersons: " . $recentPersons->count());
        $personsCount = $recentPersons->count();
        return $personsCount >= $totalPersons;
    }
    function getFilteredTotalPersons($client, $startDate, $endDate)
    {
        return $client->persons->filter(function ($person) use ($startDate, $endDate) {
            $created_at = Carbon::parse($person->created_at);
            return $created_at->between($startDate, $endDate);
        });
    }
    function checkBalancePersons($client, $rightPersonsNumber, $leftPersonsNumber, $StartDate, $EndDate)
    {
        $startDate = Carbon::parse($StartDate);
        $endDate = Carbon::parse($EndDate);

        $rightPersonsCount = $this->getFilteredRightPersons($client, $startDate, $endDate)->count();

        $leftPersonsCount = $this->getFilteredLeftPersons($client, $startDate, $endDate)->count();

        Log::info("Persons Right: $rightPersonsCount, Persons Left: $leftPersonsCount");
        return $rightPersonsCount >= $rightPersonsNumber && $leftPersonsCount >= $leftPersonsNumber;
    }

    function getFilteredRightPersons($client, $startDate, $endDate)
    {
        return $client->right_persons->filter(function ($person) use ($startDate, $endDate) {
            $createdAt = Carbon::parse($person->created_at);
            return $createdAt->between($startDate, $endDate);
        });
    }
    function getFilteredLeftPersons($client, $startDate, $endDate)
    {
        return $client->left_persons->filter(function ($person) use ($startDate, $endDate) {
            $createdAt = Carbon::parse($person->created_at);
            return $createdAt->between($startDate, $endDate);
        });
    }
    function checkVisitsNumber($client, $visitsNumber, $StartDate, $EndDate)
    {
        $filteredVisits = $this->getFilteredVisits($client, $StartDate, $EndDate);

        return $filteredVisits->count() >= $visitsNumber;
    }
    function getFilteredVisits($client, $startDate, $endDate)
    {
        return $client->visits->filter(function ($visit) use ($startDate, $endDate) {
            $used_at = Carbon::parse($visit->UsedAt);
            return $used_at->between($startDate, $endDate);
        });
    }
    function checkUniqueVisits($client, $bonanza, $StartDate, $EndDate)
    {
        $bonanza_brands = $bonanza->bonanza_brands()->where('BonanzaBrandDeleted', 0)->with('brand')->get();
        $isValid = true;

        foreach ($bonanza_brands as $bonanza_brand) {
            $brandId = $bonanza_brand->IDBrand;
            $expectedVisitNumber = $bonanza_brand->BonanzaBrandVisitNumber;

            $visitCount = $client->visits()
                ->where("ClientBrandProductStatus", 'USED')
                ->whereHas('brandproduct', function ($query) use ($brandId) {
                    $query->where('IDBrand', $brandId);
                })
                ->whereBetween('UsedAt', [$StartDate, $EndDate])
                ->count();
            if ($visitCount < $expectedVisitNumber) {
                $isValid = false;
                continue;
            }
        }
        return $isValid;
    }
    function checkReferralsNumber($client, $referralNumber, $StartDate, $EndDate)
    {
        if ($referralNumber > 0) {
            $recentReferrals = $this->getFilteredReferral($client, $StartDate, $EndDate);
            $referralCount = $recentReferrals->count();
            Log::info("referralCount: " . $referralCount);
            return $referralCount >= $referralNumber;
        } else return true;
    }
    function getFilteredReferral($client, $startDate, $endDate)
    {
        return $client->referrals->filter(function ($referral) use ($startDate, $endDate) {
            return Carbon::parse($referral->created_at)->between($startDate, $endDate);
        });
    }
}
