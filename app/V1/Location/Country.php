<?php

namespace App\V1\Location;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    protected $table = 'countries';
    protected $primaryKey = 'IDCountry';

    protected $hidden = [
        'created_at',
        'updated_at'
    ];
}
