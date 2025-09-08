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
        Schema::create('pos_sessions', function (Blueprint $table) {
            $table->id();

            // Foreign Keys
            $table->foreignId('activation_pin_id')->constrained('activation_pins'); // Assuming you're using activation_pins
            $table->foreignId('member_id')->constrained('users');
            $table->foreignId('admin_id')->nullable()->constrained('users')->nullOnDelete();

            // Session Data
            $table->enum('session_status', ['pending', 'active', 'completed', 'cancelled'])
                ->default('pending');

            // Budget Tracking
            $table->decimal('total_budget', 15, 2); // Budget dari PIN
            $table->decimal('used_budget', 15, 2)->default(0); // Budget yang sudah dipakai
            $table->decimal('remaining_budget', 15, 2); // Sisa budget

            // Product Limits
            $table->unsignedInteger('max_products')->nullable(); // Limit produk untuk sesi ini
            $table->unsignedInteger('products_count')->default(0); // Jumlah produk yang sudah dipilih

            // Additional Info
            $table->text('notes')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('session_status', 'idx_session_status');
            $table->index('member_id', 'idx_member_id');
            $table->index('admin_id', 'idx_admin_id');
        });

        Schema::create('pos_items', function (Blueprint $table) {
            $table->id();
            
            // Foreign Keys
            $table->foreignId('session_id')->constrained('pos_sessions')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products');
            $table->foreignId('added_by')->constrained('users'); // Admin yang add item ini
            
            // Item Data
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('unit_price', 15, 2); // Harga saat dipilih
            $table->decimal('total_price', 15, 2); // quantity * unit_price
            
            // PV Tracking (optional - jika perlu)
            $table->decimal('unit_pv', 10, 2)->default(0); // PV per unit
            $table->decimal('total_pv', 10, 2)->default(0); // quantity * unit_pv
            
            $table->timestamp('added_at')->useCurrent(); // Kapan item ditambahkan
            $table->timestamps();
            
            // Indexes
            $table->index('session_id', 'idx_session_id');
            $table->index('product_id', 'idx_product_id');
            $table->index('added_by', 'idx_added_by');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('product_packages');
        Schema::dropIfExists('packages');
        Schema::dropIfExists('pos_sessions');
        Schema::dropIfExists('pos_items');
    }
};
