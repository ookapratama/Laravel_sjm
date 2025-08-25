<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pin_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('requester_id');
            $table->unsignedInteger('qty');
            $table->unsignedBigInteger('unit_price')->default(1500000);
            $table->unsignedBigInteger('total_price');
            $table->unsignedBigInteger('generated_count')->default(0);
            $table->enum('status', ['requested', 'finance_approved', 'finance_rejected', 'generated'])->default('requested');
            $table->string('payment_method')->nullable();
            $table->string('payment_reference')->nullable();
            $table->string('payment_proof')->nullable();
            $table->text('finance_notes')->nullable();
            $table->unsignedBigInteger('finance_id')->nullable();
            $table->timestamp('finance_approved_at')->nullable();
            $table->timestamp('finance_at')->nullable();
            $table->unsignedBigInteger('admin_id')->nullable();
            $table->timestamp('admin_approved_at')->nullable();
            $table->text('admin_notes')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();

            $table->index('requester_id', 'pin_requests_requester_id_foreign');
            $table->index('finance_id', 'fk_pin_requests_finance');
            $table->index('admin_id', 'fk_pin_requests_admin');
        });

        Schema::create('activation_pins', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32);
            $table->enum('status', ['unused', 'reserved', 'used', 'void', 'transferred'])->default('unused');
            $table->tinyInteger('bagan')->default(1);
            $table->unsignedBigInteger('price')->default(1500000);
            $table->unsignedBigInteger('purchased_by')->nullable();
            $table->unsignedBigInteger('pin_request_id')->nullable();
            $table->unsignedBigInteger('used_by')->nullable();
            $table->timestamp('used_at')->nullable();
            $table->unsignedBigInteger('transferred_to')->nullable();
            $table->timestamp('transferred_date')->nullable();
            $table->text('transferred_notes')->nullable();
            $table->timestamps();

            $table->index('transferred_to', 'pin_requests_transferred_to_foreign');
            $table->index('purchased_by', 'fk_activation_pins_owner');
            $table->index('pin_request_id', 'fk_activation_pins_request');
            $table->index('used_by', 'fk_activation_pins_usedby');
        });

        Schema::create('pre_registrations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('phone');
            $table->string('payment_proof')->nullable();
            $table->string('payment_method')->nullable();
            $table->enum('status', ['pending', 'approved', 'payment_verified', 'rejected'])->default('pending');
            $table->unsignedBigInteger('sponsor_id')->nullable();
            $table->timestamps();
            $table->unsignedBigInteger('user_id')->nullable();

            $table->index('sponsor_id', 'pre_registrations_sponsor_id_foreign');
            $table->index('user_id', 'pre_registrations_user_id_foreign');
        });

        // FK sesuai dump
        Schema::table('pin_requests', function (Blueprint $table) {
            $table->foreign('requester_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('finance_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('admin_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('transferred_to')->references('id')->on('users')->nullOnDelete();
        });
        Schema::table('activation_pins', function (Blueprint $table) {
            $table->foreign('purchased_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('pin_request_id')->references('id')->on('pin_requests')->nullOnDelete();
            $table->foreign('used_by')->references('id')->on('users')->nullOnDelete();
        });
        Schema::table('pre_registrations', function (Blueprint $table) {
            $table->foreign('sponsor_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pre_registrations', fn(Blueprint $t) => $t->dropForeign(['sponsor_id', 'user_id']));
        Schema::table('activation_pins', fn(Blueprint $t) => $t->dropForeign(['purchased_by', 'pin_request_id', 'used_by']));
        Schema::table('pin_requests', fn(Blueprint $t) => $t->dropForeign(['requester_id', 'finance_id', 'admin_id']));

        Schema::dropIfExists('pre_registrations');
        Schema::dropIfExists('activation_pins');
        Schema::dropIfExists('pin_requests');
    }
};
