<?php

namespace App\V1\Plan;

use Illuminate\Database\Eloquent\Model;

class Bonanza extends Model
{
    protected $table = 'bonanza';
    protected $primaryKey = 'IDBonanza';

    public function bonanza_brands()
    {
        return $this->hasMany(BonanzaBrand::class, 'IDBonanza');
    }
}
