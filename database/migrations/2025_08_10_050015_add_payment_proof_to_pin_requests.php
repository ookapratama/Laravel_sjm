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
    public function up(): void {
    Schema::table('pin_requests', function (Blueprint $t) {
        if (!Schema::hasColumn('pin_requests','payment_proof_path')) {
            $t->string('payment_proof_path')->nullable()->after('payment_reference');
        }
    });
}
public function down(): void {
    Schema::table('pin_requests', function (Blueprint $t) {
        if (Schema::hasColumn('pin_requests','payment_proof_path')) {
            $t->dropColumn('payment_proof_path');
        }
    });
}
};
