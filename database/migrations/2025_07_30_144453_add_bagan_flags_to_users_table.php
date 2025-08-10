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
        $table->boolean('is_active_bagan_1')->default(true);
        $table->boolean('is_active_bagan_2')->default(false);
        $table->boolean('is_active_bagan_3')->default(false);
        $table->boolean('is_active_bagan_4')->default(false);
        $table->boolean('is_active_bagan_5')->default(false);
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
            //
        });
    }
};
