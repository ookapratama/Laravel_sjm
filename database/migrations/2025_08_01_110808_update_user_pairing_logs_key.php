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
        Schema::table('user_pairing_logs', function (Blueprint $table) {
    // Tambahkan kolom jika belum ada
    if (!Schema::hasColumn('user_pairing_logs', 'bagan')) {
        $table->unsignedTinyInteger('bagan')->after('user_id')->default(1);
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
        // Drop index lama
    $table->dropUnique('user_pairing_logs_user_id_level_cycle_type_unique');

    // Tambah index baru
    $table->unique(['user_id', 'bagan', 'level', 'cycle', 'type'], 'user_pairing_logs_unique_full');
    }
};
