<?php

namespace App\V1\Location;

use Illuminate\Database\Eloquent\Model;

class Area extends Model
{
    protected $table = 'areas';
    protected $primaryKey = 'IDArea';

    protected $hidden = [
        'created_at',
        'updated_at',
    ];
}
