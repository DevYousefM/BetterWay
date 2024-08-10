<?php

namespace App\V1\Plan;

use Illuminate\Database\Eloquent\Model;

class PlanNetwork extends Model
{
    protected $table = 'plannetwork';
    protected $primaryKey = 'IDPlanNetwork';
    public function planProduct()
    {
        return $this->belongsTo(PlanProduct::class, 'IDPlanProduct', 'IDPlanProduct');
    }
}
