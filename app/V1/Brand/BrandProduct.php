<?php

namespace App\V1\Brand;

use Illuminate\Database\Eloquent\Model;

class BrandProduct extends Model
{
    protected $table = 'brandproducts';
    protected $primaryKey = 'IDBrandProduct';

    public function brand(){
        return $this->belongsTo(Brand::class, "IDBrand");
    }
}
