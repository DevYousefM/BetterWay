<?php

namespace App\V1\General;

use Illuminate\Database\Eloquent\Model;

class Nationality extends Model
{
    protected $table = 'nationalities';
    protected $primaryKey = 'IDNationality';
}
