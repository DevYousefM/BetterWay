<?php

namespace App\V1\Client;

use App\V1\Brand\Brand;
use App\V1\Brand\BrandProduct;
use Illuminate\Database\Eloquent\Model;

class ClientBrandProduct extends Model
{
    protected $table = 'clientbrandproducts';
    protected $primaryKey = 'IDClientBrandProduct';

    public function getBrandAttribute()
    {
        return $this->brandproduct->brand;
    }
    public function brandproduct()
    {
        return $this->belongsTo(BrandProduct::class, 'IDBrandProduct');
    }
}
