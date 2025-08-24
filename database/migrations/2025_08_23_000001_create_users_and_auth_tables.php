<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('username')->unique();
                $table->string('referral_code')->nullable()->unique();
                $table->string('email');
                $table->boolean('is_locked_for_topup')->default(false);
                $table->decimal('hold_total', 15, 2)->default(0);
                $table->decimal('balance', 15, 2)->default(0);
                $table->timestamp('pairing_topup_triggered_at')->nullable();
                $table->string('password');
                $table->string('photo')->nullable();
                $table->unsignedBigInteger('sponsor_id')->nullable();
                $table->unsignedBigInteger('upline_id')->nullable();
                $table->enum('position', ['left','right'])->nullable();
                $table->unsignedInteger('pairing_count')->default(0);
                $table->unsignedInteger('pairing_level_count')->default(0);
                $table->integer('topup_level')->default(0);
                $table->integer('last_pairing_level')->default(0);
                $table->integer('last_pairing_cycle')->default(0);
                $table->unsignedInteger('pairing_point')->default(0);
                $table->integer('level')->default(1);
                $table->string('tax_id')->nullable();
                $table->rememberToken();
                $table->string('address')->nullable();
                $table->string('no_telp')->default('');
                $table->json('bank_account')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamp('joined_at')->nullable();
                $table->timestamp('email_verified_at')->nullable();
                $table->timestamps();
                $table->enum('role', ['member','admin','finance','super-admin'])->default('member');
                $table->timestamp('pairing_processed_at')->nullable();
                $table->unsignedInteger('kiri_count')->default(0);
                $table->unsignedInteger('kanan_count')->default(0);

                $table->index('sponsor_id');
                $table->index('upline_id');
                $table->index('email');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('users')) {
            Schema::drop('users');
        }
    }
};
