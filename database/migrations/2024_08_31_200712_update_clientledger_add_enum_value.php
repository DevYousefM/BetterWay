<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UpdateClientledgerAddEnumValue extends Migration
{
    public function up()
    {
        DB::statement("ALTER TABLE `clientledger` CHANGE `ClientLedgerSource` `ClientLedgerSource` ENUM('WALLET','VOUCHER','CREDIT','TOOL','EVENT','BRAND_PRODUCT','PLAN_PRODUCT','ADMIN','BONANZA','CHEQUE') CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL;");
        DB::statement("ALTER TABLE `clientledger` CHANGE `ClientLedgerDestination` `ClientLedgerDestination` ENUM('WALLET','VOUCHER','CREDIT','TOOL','EVENT','BRAND_PRODUCT','PLAN_PRODUCT','ADMIN','BONANZA','CHEQUE') CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL");
    }


    public function down()
    {
        //
    }
}
