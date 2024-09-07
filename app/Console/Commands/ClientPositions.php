<?php

namespace App\Console\Commands;

use App\V1\Client\Client;
use App\V1\Client\Position;
use App\V1\Client\PositionsForClients;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ClientPositions extends Command
{
    protected $signature = 'clients:positions';

    protected $description = 'Get Client Position After Check';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        Log::info("Start Positions Check");
        $Positions = Position::all();
        foreach ($Positions as $position) {

            $clients = Client::with(['referrals', 'visits' => function ($query) {
                $query->where('ClientBrandProductStatus', 'USED');
            }])
                ->where(function ($query) use ($position) {
                    $query->where("IDPosition", '!=', $position->IDPosition)
                        ->orWhereNull("IDPosition");
                })->get();
            $PositionReferralNumber = $position->PositionReferralNumber;
            $PositionReferralInterval = $position->PositionReferralInterval;

            $PositionVisitsNumber = $position->PositionVisits;
            $PositionVisitsInterval = $position->PositionVisitInterval;

            $IsPositionUniqueVisits = $position->IsPositionUniqueVisits;

            $PositionTotalPersonsNumber = $position->PositionAllNumber;

            $PositionRightPersonsNumber = $position->PositionRightNumber;
            $PositionLeftPersonsNumber = $position->PositionLeftNumber;

            $PositionTotalPersonsInterval = $position->PositionNumberInterval;

            $PositionTotalPointsNumber = $position->PositionAllPoints;

            $PositionRightPointsNumber = $position->PositionRightPoints;
            $PositionLeftPointsNumber = $position->PositionLeftPoints;

            $PositionPointsInterval = $position->PositionPointInterval;

            $PositionChequeInterval = $position->PositionChequeInterval;
            $PositionChequeValue = $position->PositionChequeValue;

            $lastFiltering = [];
            count($clients) > 0 && $lastFiltering = $this->getFilteredByReferral($clients, $PositionReferralInterval, $PositionReferralNumber);
            if (count($lastFiltering) > 0) {

                if ($PositionVisitsNumber > 0 && $PositionVisitsInterval) $lastFiltering = $this->getFilteredByVisits($lastFiltering, $PositionVisitsInterval, $PositionVisitsNumber);

                if ($IsPositionUniqueVisits) $lastFiltering = $this->getFilteredByUniqueVisits($lastFiltering, $position);

                if ($PositionTotalPersonsNumber > 0) {
                    $lastFiltering = $this->getFilteredByTotalPersons($lastFiltering, $PositionTotalPersonsInterval, $PositionTotalPersonsNumber);
                }
                if ($PositionRightPersonsNumber && $PositionLeftPersonsNumber && $PositionRightPersonsNumber != 0 && $PositionLeftPersonsNumber != 0) {
                    $lastFiltering = $this->getFilteredByBalancePersons($lastFiltering, $PositionTotalPersonsInterval, $PositionRightPersonsNumber, $PositionLeftPersonsNumber);
                }
                if ($PositionTotalPointsNumber > 0) {
                    $lastFiltering = $this->getFilteredByTotalPoints($lastFiltering, $PositionPointsInterval, $PositionTotalPointsNumber);
                }
                if ($PositionRightPointsNumber && $PositionLeftPointsNumber && $PositionRightPointsNumber != 0 && $PositionLeftPointsNumber != 0) {
                    $lastFiltering = $this->getFilteredByBalancePoints($lastFiltering, $PositionPointsInterval, $PositionRightPointsNumber, $PositionLeftPointsNumber);
                }
                if ($PositionChequeValue && $PositionChequeValue > 0) $lastFiltering = $this->getFilteredByCheques($lastFiltering, $PositionChequeInterval, $PositionChequeValue);
                $simplifiedClients = $lastFiltering->map(function ($client) use ($position) {
                    return [
                        'IDClient' => $client->IDClient,
                        'IDPosition' => $position->IDPosition,
                    ];
                })->unique('IDClient')->values();
                // Log::info($simplifiedClients);

                foreach ($simplifiedClients as $clientData) {
                    PositionsForClients::firstOrCreate(
                        ['IDClient' => $clientData['IDClient'], 'IDPosition' => $clientData['IDPosition']],
                        ['Status' => 'PENDING']
                    );
                }
            }
        }
        Log::info("End Positions Check");
        return 0;
    }
    function getFilteredByReferral($clients, $intervalMinutes, $referralNumber)
    {
        if ($referralNumber > 0) {

            return $clients->filter(function ($client) use ($intervalMinutes, $referralNumber) {
                // Log::info($client->ClientName);
                $now = Carbon::now();
                $recentReferrals = $client->referrals->filter(function ($referral) use ($now, $intervalMinutes) {
                    return Carbon::parse($referral->created_at)->diffInMinutes($now) <= $intervalMinutes;
                });
                // Log::info("recentReferrals: " . $recentReferrals->count());
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
            });
        } else return $clients;
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
            // Log::info($client->ClientName);
            $recentPersons = $client->persons->filter(function ($person) use ($now, $intervalMinutes) {
                return Carbon::parse($person->created_at)->diffInMinutes($now) <= $intervalMinutes;
            });
            // Log::info("recentPersons: " . $recentPersons->count());

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
            // Log::info($client->ClientName);
            $recentRightPersons = !empty($client->right_persons)
                ? $client->right_persons->filter(function ($person) use ($now, $intervalMinutes) {
                    return Carbon::parse($person->created_at)->diffInMinutes($now) <= $intervalMinutes;
                }) : collect();

            $recentLeftPersons = !empty($client->left_persons) ? collect($client->left_persons)->filter(function ($person) use ($now, $intervalMinutes) {
                return Carbon::parse($person->created_at)->diffInMinutes($now) <= $intervalMinutes;
            }) : collect();
            // Log::info("recentRightPersons: " . $recentRightPersons->count());
            // Log::info("recentLeftPersons: " . $recentLeftPersons->count());
            if ($recentRightPersons->count() >= $rightPersonsNumber && $recentLeftPersons->count() >= $leftPersonsNumber) {
                return true;
            }

            return false;
        });
    }
    function getFilteredByTotalPoints($clients, $intervalMinutes, $pointsNumber)
    {
        return $clients->filter(function ($client) use ($intervalMinutes, $pointsNumber) {
            // Log::info($client->ClientName);
            $now = Carbon::now();
            $recentPointsHistory = $client->points_history->filter(function ($point) use ($now, $intervalMinutes, $client) {
                return Carbon::parse($point->created_at)->diffInMinutes($now) <= $intervalMinutes;
            });
            // Log::info("recentPointsHistory: " . $recentPointsHistory->count());
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
            // Log::info($client->ClientName);

            $recentPointsHistory = $client->points_history->filter(function ($point) use ($now, $intervalMinutes) {
                return Carbon::parse($point->created_at)->diffInMinutes($now) <= $intervalMinutes;
            });
            // Log::info("recentPointsHistory: " . $recentPointsHistory->count());
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
