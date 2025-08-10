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
      Schema::create('bonus_settings', function (Blueprint $table) {
    $table->id();
    $table->string('type'); // contoh: pairing, sponsor, reward, cashback, dsb
    $table->string('key');  // contoh: percentage, fixed_amount, max_per_day
    $table->string('value'); // bisa berupa angka atau JSON
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
        Schema::dropIfExists('bonus_settings');
    }
};
