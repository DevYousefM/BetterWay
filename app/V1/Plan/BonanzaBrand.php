<?php

namespace App\V1\Plan;

use App\V1\Brand\Brand;
use Illuminate\Database\Eloquent\Model;

class BonanzaBrand extends Model
{
    protected $table = 'bonanzabrands';
    protected $primaryKey = 'IDBonanzaBrand';

    public function brand()
    {
        return $this->belongsTo(Brand::class, "IDBrand");
    }
}
