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
    Schema::table('withdrawals', function (Illuminate\Database\Schema\Blueprint $table) {
        if (!Schema::hasColumn('withdrawals', 'approved_at')) {
            $table->timestamp('approved_at')->nullable()->after('created_at');
        }
    });
}

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('withdrawals', function (Blueprint $table) {
            //
        });
    }
};
