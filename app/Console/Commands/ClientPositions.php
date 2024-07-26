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
        $Positions = Position::all();
        foreach ($Positions as $position) {
            $clients = Client::with(['referrals', 'visits' => function ($query) {
                $query->where('ClientBrandProductStatus', 'USED');
            }])
                ->whereHas('referrals', function ($query) {
                    $query->whereNotNull('IDReferral');
                })
                ->where("IDPosition", '!=', $position->IDPosition)
                ->get();
            $PositionReferralNumber = $position->PositionReferralNumber;
            $PositionReferralInterval = $position->PositionReferralInterval;

            $PositionVisitsNumber = $position->PositionVisits;
            $PositionVisitsInterval = $position->PositionVisitInterval;

            $PositionTotalPersonsNumber = $position->PositionAllNumber;

            $PositionRightPersonsNumber = $position->PositionRightNumber;
            $PositionLeftPersonsNumber = $position->PositionLeftNumber;

            $PositionTotalPersonsInterval = $position->PositionNumberInterval;

            $PositionTotalPointsNumber = $position->PositionAllPoints;

            $PositionRightPointsNumber = $position->PositionRightPoints;
            $PositionLeftPointsNumber = $position->PositionLeftPoints;

            $PositionPointsInterval = $position->PositionPointInterval;
            $lastFiltering = [];
            count($clients) > 0 && $lastFiltering = $this->getFilteredByReferral($clients, $PositionReferralInterval, $PositionReferralNumber);
            if (count($lastFiltering) > 0) {

                $lastFiltering = $this->getFilteredByVisits($lastFiltering, $PositionVisitsInterval, $PositionVisitsNumber);
                if ($PositionTotalPersonsNumber > 0) {
                    $lastFiltering = $this->getFilteredByTotalPersons($lastFiltering, $PositionTotalPersonsInterval, $PositionTotalPersonsNumber);
                } else {
                    $lastFiltering = $this->getFilteredByBalancePersons($lastFiltering, $PositionTotalPersonsInterval, $PositionRightPersonsNumber, $PositionLeftPersonsNumber);
                }
                if ($PositionTotalPointsNumber > 0) {
                    $lastFiltering = $this->getFilteredByTotalPoints($lastFiltering, $PositionPointsInterval, $PositionTotalPointsNumber);
                } else {
                    $lastFiltering = $this->getFilteredByBalancePoints($lastFiltering, $PositionPointsInterval, $PositionRightPointsNumber, $PositionLeftPointsNumber);
                }
                $simplifiedClients = $lastFiltering->map(function ($client) use ($position) {
                    return [
                        'IDClient' => $client->IDClient,
                        'IDPosition' => $position->IDPosition,
                    ];
                })->unique('IDClient')->values();
                foreach ($simplifiedClients as $clientData) {
                    PositionsForClients::firstOrCreate(
                        ['IDClient' => $clientData['IDClient'], 'IDPosition' => $clientData['IDPosition']],
                        ['Status' => 'PENDING']
                    );
                }
            }
        }
        return 0;
    }
    function getFilteredByReferral($clients, $intervalMinutes, $referralNumber)
    {
        return $clients->filter(function ($client) use ($intervalMinutes, $referralNumber) {
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
        });
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