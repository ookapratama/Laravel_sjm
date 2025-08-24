<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('bonus_settings', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->string('key');
            $table->string('value');
            $table->timestamps();
        });

        Schema::create('cash_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->enum('type', ['in','out']);
            $table->string('source');
            $table->decimal('amount', 15, 2);
            $table->text('notes')->nullable();
            $table->string('payment_channel')->nullable();
            $table->string('payment_reference')->nullable();
            $table->timestamps();

            $table->index('user_id', 'cash_transactions_user_id_foreign');
        });

        Schema::create('bonus_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('from_user_id')->nullable();
            $table->unsignedBigInteger('bagan')->nullable();
            $table->enum('type', ['sponsor','pairing','reward','admin']);
            $table->enum('status', ['paid','held','released','allocated'])->default('paid');
            $table->decimal('amount', 15, 2);
            $table->decimal('tax', 15, 2)->default(0);
            $table->decimal('net_amount', 15, 2);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('user_id', 'bonus_transactions_user_id_foreign');
            $table->index('from_user_id', 'bonus_transactions_from_user_id_foreign');
        });

        // FK sesuai dump
        Schema::table('cash_transactions', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });
        Schema::table('bonus_transactions', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('from_user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void {
        Schema::table('bonus_transactions', function (Blueprint $table) {
            $table->dropForeign(['from_user_id']);
            $table->dropForeign(['user_id']);
        });
        Schema::table('cash_transactions', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });
        Schema::dropIfExists('bonus_transactions');
        Schema::dropIfExists('cash_transactions');
        Schema::dropIfExists('bonus_settings');
    }
};
