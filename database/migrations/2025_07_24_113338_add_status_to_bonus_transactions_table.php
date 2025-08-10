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
        Schema::table('bonus_transactions', function (Blueprint $table) {
    $table->enum('status', ['paid', 'held', 'released'])->default('paid')->after('type');

});
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('bonus_transactions', function (Blueprint $table) {
            //
        });
    }
};
