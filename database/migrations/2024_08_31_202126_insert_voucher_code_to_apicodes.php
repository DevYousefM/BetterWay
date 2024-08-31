<?php

use App\V1\General\APICode;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class InsertVoucherCodeToApicodes extends Migration
{
    public function up()
    {
        APICode::insert([
            "ApiCodeDescription" => "Your Reward points not enough to get voucher",
        ]);
    }

    public function down()
    {
        Schema::table('apicodes', function (Blueprint $table) {
            //
        });
    }
}
