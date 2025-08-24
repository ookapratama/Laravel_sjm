<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('topups', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->decimal('amount', 15, 2);
            $table->string('type')->default('manual');
            $table->integer('for_cycle')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();

            $table->index('user_id', 'topups_user_id_foreign');
        });

        Schema::create('user_topups', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->integer('amount');
            $table->integer('flush_cycle');
            $table->string('type')->default('auto');
            $table->string('source')->nullable();
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->index('user_id', 'user_topups_user_id_foreign');
        });

        Schema::create('withdrawals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->decimal('amount', 15, 2);
            $table->decimal('tax', 15, 2)->default(0);
            $table->enum('status', ['pending','approved','rejected','menunggu'])->default('pending');
            $table->string('transfer_reference')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->text('admin_notes')->nullable();
            $table->timestamps();
            $table->timestamp('approved_at')->nullable();
            $table->string('type')->nullable();

            $table->index('user_id', 'withdrawals_user_id_foreign');
        });
    }

    public function down(): void {
        Schema::dropIfExists('withdrawals');
        Schema::dropIfExists('user_topups');
        Schema::dropIfExists('topups');
    }
};
