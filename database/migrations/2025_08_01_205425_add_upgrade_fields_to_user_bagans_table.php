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
      Schema::table('user_bagans', function (Blueprint $table) {
            $table->string('bukti_transfer')->nullable()->after('upgrade_paid_manually');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending')->after('bukti_transfer');
            $table->unsignedBigInteger('approved_by_admin')->nullable()->after('status');
            $table->unsignedBigInteger('approved_by_finance')->nullable()->after('approved_by_admin');

            // Opsional: jika ingin relasi FK (jika users.id pasti selalu valid)
             $table->foreign('approved_by_admin')->references('id')->on('users')->nullOnDelete();
             $table->foreign('approved_by_finance')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_bagans', function (Blueprint $table) {
            $table->dropColumn([
                'bukti_transfer',
                'status',
                'approved_by_admin',
                'approved_by_finance',
            ]);
        });
    }
};
