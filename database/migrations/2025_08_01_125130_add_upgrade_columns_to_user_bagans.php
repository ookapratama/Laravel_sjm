<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('user_bagans', function (Blueprint $table) {
            $table->unsignedBigInteger('upgrade_cost')->default(0)->after('pairing_level_count');
            $table->unsignedBigInteger('allocated_from_bonus')->default(0)->after('upgrade_cost');
            $table->boolean('upgrade_paid_manually')->default(false)->after('allocated_from_bonus');
            $table->timestamp('upgrade_paid_at')->nullable()->after('upgrade_paid_manually');
        });
    }

    public function down(): void
    {
        Schema::table('user_bagans', function (Blueprint $table) {
            $table->dropColumn([
                'upgrade_cost',
                'allocated_from_bonus',
                'upgrade_paid_manually',
                'upgrade_paid_at'
            ]);
        });
    }
};
