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
    $table->boolean('is_locked_for_topup')->default(false)->after('email');
    $table->decimal('hold_total', 15, 2)->default(0)->after('is_locked_for_topup');
    $table->decimal('balance', 15, 2)->default(0)->after('hold_total');
    $table->timestamp('pairing_topup_triggered_at')->nullable()->after('balance');
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
