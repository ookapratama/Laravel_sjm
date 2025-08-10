<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
         Schema::table('users', function (Blueprint $table) {
            $table->string('no_telp')->after('address');
            $table->unsignedInteger('pairing_count')->default(0)->after('position');
            $table->unsignedInteger('pairing_level_count')->default(0)->after('pairing_count');
            $table->unsignedInteger('pairing_point')->default(0)->after('pairing_level_count');
            $table->unsignedBigInteger('bonus_voucher')->default(7500000)->after('pairing_point');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'pairing_count',
                'pairing_level_count',
                'pairing_point',
                'bonus_voucher'
            ]);
        });
    }
};
