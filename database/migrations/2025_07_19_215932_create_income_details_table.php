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
      Schema::create('income_details', function (Blueprint $table) {
    $table->id();
    $table->date('date');
    $table->decimal('pendaftaran_member', 15, 2)->default(0);
    $table->decimal('produk', 15, 2)->default(0);
    $table->decimal('manajemen', 15, 2)->default(0);
    $table->decimal('pairing_bonus', 15, 2)->default(0);
    $table->decimal('ro_bonus', 15, 2)->default(0);
    $table->decimal('reward_poin', 15, 2)->default(0);
    $table->decimal('withdraw', 15, 2)->default(0);
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
        Schema::dropIfExists('income_details');
    }
};
