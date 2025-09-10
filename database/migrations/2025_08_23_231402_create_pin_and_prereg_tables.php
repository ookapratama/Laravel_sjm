<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // PIN REQUESTS
        if (!Schema::hasTable('pin_requests')) {
            Schema::create('pin_requests', function (Blueprint $table) {
                $table->id();
                $table->foreignId('requester_id')->constrained('users')->cascadeOnDelete();
                $table->unsignedInteger('qty');
                $table->unsignedBigInteger('unit_price')->default(1500000);
                $table->unsignedBigInteger('total_price');
                $table->unsignedBigInteger('generated_count')->default(0);
                $table->enum('status', ['requested', 'finance_approved', 'finance_rejected', 'generated'])->default('requested');
                $table->string('payment_method')->nullable();
                $table->string('payment_reference')->nullable();
                $table->string('payment_proof')->nullable();
                $table->text('finance_notes')->nullable();
                $table->foreignId('finance_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('finance_approved_at')->nullable();
                $table->timestamp('finance_at')->nullable();
                $table->foreignId('admin_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('admin_approved_at')->nullable();
                $table->text('admin_notes')->nullable();
                $table->timestamp('generated_at')->nullable();
                $table->timestamps();
            });
        }

        // ACTIVATION PINS
        if (!Schema::hasTable('activation_pins')) {
            Schema::create('activation_pins', function (Blueprint $table) {
                $table->id();
                $table->string('code', 32)->unique();
                $table->enum('status', ['unused', 'reserved', 'used', 'void', 'transferred'])->default('unused');
                $table->tinyInteger('bagan')->default(1);
                $table->unsignedBigInteger('price')->default(1500000);

                $table->foreignId('purchased_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('pin_request_id')->nullable()->constrained('pin_requests')->nullOnDelete();
                $table->foreignId('used_by')->nullable()->constrained('users')->nullOnDelete();
                // $table->foreignId('product_package_id')->nullable()->constrained('product_packages')->nullOnDelete();
                $table->timestamp('used_at')->nullable();

                // <-- Kolom & FK yang benar untuk transfer
                $table->foreignId('transferred_to')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('transferred_date')->nullable();
                $table->text('transferred_notes')->nullable();

                $table->timestamps();

                $table->index(['pin_request_id', 'status']);
            });
        }

        // PRE-REGISTRATIONS
        if (!Schema::hasTable('pre_registrations')) {
            Schema::create('pre_registrations', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email');
                $table->string('phone');
                $table->string('payment_proof')->nullable();
                $table->string('payment_method')->nullable();
                $table->enum('status', ['pending', 'approved', 'payment_verified', 'rejected'])->default('pending');
                $table->foreignId('sponsor_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }


    }

    public function down(): void
    {
        // Drop sesuai urutan dependensi
        Schema::dropIfExists('pre_registrations');
        Schema::dropIfExists('activation_pins');
        Schema::dropIfExists('pin_requests');
    }
};
