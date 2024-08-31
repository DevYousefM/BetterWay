<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateClientVouchersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('clientvouchers', function (Blueprint $table) {
            $table->id('IDClientVoucher');
            $table->string('IDClient');
            $table->integer('VoucherValue');
            $table->string('VoucherCode')->unique();
            $table->enum('ClientVoucherStatus', ['ACTIVE', 'USED']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('client_vouchers');
    }
}
