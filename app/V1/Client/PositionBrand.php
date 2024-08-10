<?php

namespace App\V1\Client;

use App\V1\Brand\Brand;
use Illuminate\Database\Eloquent\Model;

class PositionBrand extends Model
{
    protected $table = 'positionbrands';
    protected $primaryKey = 'IDPositionBrand';
    protected $guarded = [];
    public function brand()
    {
        return $this->belongsTo(Brand::class, "IDBrand");
    }
}
