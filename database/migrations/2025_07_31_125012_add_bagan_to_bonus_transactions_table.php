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
        SSchema::table('bonus_transactions', function (Blueprint $table) {
        $table->unsignedTinyInteger('bagan')->default(1)->after('user_id');
    });
    }

    /**ß
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
         Schema::table('bonus_transactions', function (Blueprint $table) {
        $table->dropColumn('bagan');
    });
    }
};
