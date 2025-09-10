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
        Schema::create('product_outgoing', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products');
            $table->string('transaction_group')->nullable();
            $table->unsignedInteger('quantity'); // Jumlah barang keluar
            $table->unsignedInteger('refunded_quantity')->default(0); // Untuk track refund
            $table->decimal('unit_price', 15, 2); // Harga saat keluar (dari products.price)
            $table->decimal('total_price', 15, 2); // quantity * unit_price
            $table->decimal('unit_pv', 10, 2)->default(0); // PV per unit
            $table->decimal('total_pv', 10, 2)->default(0); // total PV
            $table->date('transaction_date')->default(now()); // total PV
            $table->string('notes')->nullable(); // Keterangan (opsional)
            $table->foreignId('created_by')->constrained('users'); // Admin yang input
            $table->enum('status', ['active', 'partial_refunded', 'fully_refunded'])->default('active');
            $table->timestamps();
            
            // Index untuk performa
            
            $table->index('transaction_group');
            $table->index(['transaction_group', 'status']);
            $table->index(['transaction_date', 'status']);
            $table->index(['created_at', 'product_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('product_outgoing', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->dropIndex(['transaction_group']);
            $table->dropIndex(['transaction_group', 'status']);
            $table->dropIndex(['transaction_date', 'status']);
            $table->dropColumn(['transaction_group', 'refunded_quantity', 'status']);
        });
        Schema::dropIfExists('product_outgoings');
    }
};
