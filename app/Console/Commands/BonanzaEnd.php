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
        $CurrentTime = new DateTime('now');
        $CurrentTime = $CurrentTime->format('Y-m-d H:i:s');

        $Clients = Client::where("ClientStatus", "ACTIVE")->where("ClientDeleted", 0)->get();
        $Bonanzas = Bonanza::where('BonanzaStatus', 'ACTIVE')->where("BonanzaEndTime", "<", $CurrentTime)->get();
        foreach ($Clients as $Client) {
            $IDClient = $Client->IDClient;
            foreach ($Bonanzas as $Bonanza) {
                $BonanzaLeftPoints = $Bonanza->BonanzaLeftPoints;

                $BonanzaLeftPoints > 0 && $this->getFilteredByReferral($Client, $Bonanza->BonanzaReferralNumber, $BonanzaLeftPoints);

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

        return 0;
    }
    function getFilteredByReferral($client, $intervalMinutes, $referralNumber)
    {
        if ($referralNumber > 0) {
            $now = Carbon::now();
            $recentReferrals = $client->referrals->filter(function ($referral) use ($now, $intervalMinutes) {
                return Carbon::parse($referral->created_at)->diffInMinutes($now) <= $intervalMinutes;
            });

            $sortedReferrals = $recentReferrals->sortBy('created_at');

            foreach ($sortedReferrals as $referral) {
                $currentReferralTime = Carbon::parse($referral->created_at);

                $referralCount = $sortedReferrals->filter(function ($r) use ($currentReferralTime, $intervalMinutes) {
                    return Carbon::parse($r->created_at)->diffInMinutes($currentReferralTime) <= $intervalMinutes;
                })->count();

                if ($referralCount >= $referralNumber) {
                    return true;
                }
            }

            return false;
        } else return true;
    }
    function getFilteredByVisits($clients, $intervalMinutes, $visitsNumber)
    {
        return $clients->filter(function ($client) use ($intervalMinutes, $visitsNumber) {
            $now = Carbon::now();
            $recentVisits = $client->visits->filter(function ($visit) use ($now, $intervalMinutes) {
                return Carbon::parse($visit->UsedAt)->diffInMinutes($now) <= $intervalMinutes;
            });

            $sortedVisits = $recentVisits->sortBy('UsedAt');
            foreach ($sortedVisits as $visit) {
                $currentVisitTime = Carbon::parse($visit->UsedAt);
                $visitCount = $sortedVisits->filter(function ($v) use ($currentVisitTime, $intervalMinutes) {
                    return Carbon::parse($v->UsedAt)->diffInMinutes($currentVisitTime) <= $intervalMinutes;
                })->count();
                if ($visitCount >= $visitsNumber) {
                    return true;
                }
            }
            return false;
        });
    }
    function getFilteredByUniqueVisits($clients, $position)
    {
        return $clients->filter(function ($client) use ($position) {
            $interval = $position->PositionUniqueVisitInterval;
            $position_brands = $position->position_brands()->with('brand')->get();
            $isValid = true;

            foreach ($position_brands as $position_brand) {
                $brandId = $position_brand->IDBrand;
                $expectedVisitNumber = $position_brand->PositionBrandVisitNumber;

                $visitCount = $client->visits()
                    ->where("ClientBrandProductStatus", 'USED')
                    ->whereHas('brandproduct', function ($query) use ($brandId) {
                        $query->where('IDBrand', $brandId);
                    })
                    ->where('UsedAt', '>=', Carbon::now()->subMinutes($interval))
                    ->count();
                if ($visitCount < $expectedVisitNumber) {
                    $isValid = false;
                    break;
                }
            }

            if ($isValid) {
                return true;
            }

            return false;
        });
    }
    function getFilteredByTotalPersons($clients, $intervalMinutes, $personsNumber)
    {
        return $clients->filter(function ($client) use ($intervalMinutes, $personsNumber) {
            $now = Carbon::now();

            $recentPersons = $client->persons->filter(function ($person) use ($now, $intervalMinutes) {
                return Carbon::parse($person->created_at)->diffInMinutes($now) <= $intervalMinutes;
            });

            $sortedPersons = $recentPersons->sortBy('created_at');

            foreach ($sortedPersons as $person) {
                $currentPersonTime = Carbon::parse($person->created_at);

                $personsCount = $sortedPersons->filter(function ($p) use ($currentPersonTime, $intervalMinutes) {
                    return Carbon::parse($p->created_at)->diffInMinutes($currentPersonTime) <= $intervalMinutes;
                })->count();

                if ($personsCount >= $personsNumber) {
                    return true;
                }
            }

            return false;
        });
    }
    function getFilteredByBalancePersons($clients, $intervalMinutes, $rightPersonsNumber, $leftPersonsNumber)
    {
        return $clients->filter(function ($client) use ($intervalMinutes, $rightPersonsNumber, $leftPersonsNumber) {
            $now = Carbon::now();

            $recentRightPersons = $client->right_persons->filter(function ($person) use ($now, $intervalMinutes) {
                return Carbon::parse($person->created_at)->diffInMinutes($now) <= $intervalMinutes;
            });

            $recentLeftPersons = $client->left_persons->filter(function ($person) use ($now, $intervalMinutes) {
                return Carbon::parse($person->created_at)->diffInMinutes($now) <= $intervalMinutes;
            });

            if ($recentRightPersons->count() >= $rightPersonsNumber && $recentLeftPersons->count() >= $leftPersonsNumber) {
                return true;
            }

            return false;
        });
    }
    function getFilteredByTotalPoints($clients, $intervalMinutes, $pointsNumber)
    {
        return $clients->filter(function ($client) use ($intervalMinutes, $pointsNumber) {
            $now = Carbon::now();
            $recentPointsHistory = $client->points_history->filter(function ($point) use ($now, $intervalMinutes, $client) {
                return Carbon::parse($point->created_at)->diffInMinutes($now) <= $intervalMinutes;
            });

            $pointsCount = $recentPointsHistory->sum('ClientLedgerPoints');

            return $pointsCount >= $pointsNumber;
        });
    }
    function getFilteredByCheques($clients, $intervalMinutes, $chequesValue)
    {
        return $clients->filter(function ($client) use ($intervalMinutes, $chequesValue) {
            $now = Carbon::now();
            $recentChequesHistory = $client->ChequesHistory->filter(function ($cheque) use ($now, $intervalMinutes, $client) {
                return Carbon::parse($cheque->created_at)->diffInMinutes($now) <= $intervalMinutes;
            });

            $chequesCount = $recentChequesHistory->sum('ClientLedgerAmount');

            return $chequesCount >= $chequesValue;
        });
    }
    function getFilteredByBalancePoints($clients, $intervalMinutes, $rightPointsNumber, $leftPointsNumber)
    {
        return $clients->filter(function ($client) use ($intervalMinutes, $rightPointsNumber, $leftPointsNumber) {
            $now = Carbon::now();

            $recentPointsHistory = $client->points_history->filter(function ($point) use ($now, $intervalMinutes) {
                return Carbon::parse($point->created_at)->diffInMinutes($now) <= $intervalMinutes;
            });

            $sortedPointsHistory = $recentPointsHistory->sortBy('created_at');

            foreach ($sortedPointsHistory as $point) {
                $currentPointsTime = Carbon::parse($point->created_at);

                $intervalEnd = $currentPointsTime->copy()->addMinutes($intervalMinutes);

                $pointsInInterval = $sortedPointsHistory->filter(function ($r) use ($currentPointsTime, $intervalEnd) {
                    $createdTime = Carbon::parse($r->created_at);
                    return $createdTime->between($currentPointsTime, $intervalEnd);
                });

                $rightPointsCount = $pointsInInterval->filter(function ($r) {
                    return $r->ClientLedgerPosition === 'RIGHT';
                })->sum('ClientLedgerPoints');

                $leftPointsCount = $pointsInInterval->filter(function ($r) {
                    return $r->ClientLedgerPosition === 'LEFT';
                })->sum('ClientLedgerPoints');
                if ($rightPointsCount >= $rightPointsNumber && $leftPointsCount >= $leftPointsNumber) {
                    return true;
                }
            }

            return false;
        });
    }
}
