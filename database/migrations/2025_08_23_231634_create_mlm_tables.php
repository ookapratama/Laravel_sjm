<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('user_bagans', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('upline_id')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->tinyInteger('bagan');
            $table->unsignedInteger('level')->default(1);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('pairing_level_count')->default(0);
            $table->unsignedBigInteger('upgrade_cost')->default(0);
            $table->unsignedBigInteger('allocated_from_bonus')->default(0);
            $table->boolean('upgrade_paid_manually')->default(false);
            $table->string('bukti_transfer')->nullable();
            $table->enum('status', ['pending','approved','rejected'])->nullable();
            $table->unsignedBigInteger('approved_by_admin')->nullable();
            $table->unsignedBigInteger('approved_by_finance')->nullable();
            $table->timestamp('upgrade_paid_at')->nullable();
            $table->unsignedBigInteger('held_bonus')->default(0);
            $table->timestamp('activated_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id','bagan'], 'user_bagans_user_id_bagan_unique');
            $table->index('upline_id', 'user_bagans_upline_id_foreign');
            $table->index('approved_by_admin', 'user_bagans_approved_by_admin_foreign');
            $table->index('approved_by_finance', 'user_bagans_approved_by_finance_foreign');
        });

        Schema::create('user_pairing_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedTinyInteger('bagan')->default(1);
            $table->unsignedTinyInteger('level');
            $table->unsignedTinyInteger('cycle')->default(0)->comment('untuk pairing cycle jika digunakan');
            $table->enum('type', ['pairing'])->default('pairing');
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->unique(['user_id','bagan','level','cycle','type'], 'user_pairing_logs_user_id_bagan_level_cycle_type_unique');
        });
    }

    public function down(): void {
        Schema::dropIfExists('user_pairing_logs');
        Schema::dropIfExists('user_bagans');
    }
};
