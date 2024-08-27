<?php

use App\V1\General\APICode;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class InsertDataInApicodesTable extends Migration
{
    public function up()
    {
        APICode::insert([
            "ApiCodeDescription" => "The installment payment period has ended",
        ]);
        APICode::insert([
            "ApiCodeDescription" => "The installment payment period has ended you must pay full event price ",
        ]);
    }

    public function down()
    {
        Schema::table('apicodes', function (Blueprint $table) {
            //
        });
    }
}
