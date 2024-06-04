<?php

namespace App\V1\Location;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    protected $table = 'cities';
    protected $primaryKey = 'IDCity';

    protected $hidden = [
        'created_at',
        'updated_at',
    ];
}
