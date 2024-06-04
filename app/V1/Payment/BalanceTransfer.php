<?php

namespace App\V1\Payment;

use Illuminate\Database\Eloquent\Model;

class BalanceTransfer extends Model
{
    protected $table = 'balancetransfer';
    protected $primaryKey = 'IDBalanceTransfer';
}
