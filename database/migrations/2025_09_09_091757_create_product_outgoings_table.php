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
            $table->unsignedInteger('quantity'); // Jumlah barang keluar
            $table->decimal('unit_price', 15, 2); // Harga saat keluar (dari products.price)
            $table->decimal('total_price', 15, 2); // quantity * unit_price
            $table->decimal('unit_pv', 10, 2)->default(0); // PV per unit
            $table->decimal('total_pv', 10, 2)->default(0); // total PV
            $table->string('notes')->nullable(); // Keterangan (opsional)
            $table->string('reference_code')->nullable(); // Referensi (PIN code, dll)
            $table->foreignId('created_by')->constrained('users'); // Admin yang input
            $table->timestamps();
            
            $table->index(['created_at', 'product_id']);
            $table->index('reference_code');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('product_outgoings');
    }
};
