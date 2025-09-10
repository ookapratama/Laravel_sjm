<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('sku');
            $table->decimal('price', 15, 2);
            $table->decimal('pv', 10, 2)->default(0);
            $table->unsignedInteger('stock')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('product_sales', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            // $table->unsignedBigInteger('product_packages_id');
            $table->integer('quantity');
            $table->decimal('total_price', 15, 2);
            $table->string('payment_type')->nullable();
            $table->string('source')->nullable();
            $table->timestamps();

            $table->index('user_id', 'product_sales_user_id_foreign');
            // $table->index('product_packages_id', 'product_sales_product_packages_id_foreign');
        });

        Schema::create('user_products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('product_id');
            $table->integer('quantity')->default(1);
            $table->string('source')->nullable();
            $table->timestamps();

            $table->index('user_id', 'user_products_user_id_foreign');
        });

        // FK sesuai dump
        Schema::table('product_sales', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            // $table->foreign('product_packages_id')->references('id')->on('product_packages')->cascadeOnDelete();
        });
    }

    public function down(): void {
        Schema::table('product_sales', function (Blueprint $table) {
            $table->dropForeign(['product_packages_id']);
            $table->dropForeign(['user_id']);
        });
        Schema::dropIfExists('user_products');
        Schema::dropIfExists('product_sales');
        Schema::dropIfExists('products');
    }
};
