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
 public function up(): void
{
    // 1) Buat pin_requests kalau belum ada
    if (!Schema::hasTable('pin_requests')) {
        Schema::create('pin_requests', function (Blueprint $t) {
            // ... isi sama seperti sebelumnya ...
        });

        Schema::table('pin_requests', function (Blueprint $t) {
            $t->foreign('finance_id', 'fk_pin_requests_finance')
              ->references('id')->on('users')->nullOnDelete();
            $t->foreign('admin_id', 'fk_pin_requests_admin')
              ->references('id')->on('users')->nullOnDelete();
        });
    }

    // 2) Buat activation_pins kalau belum ada
    if (!Schema::hasTable('activation_pins')) {
        Schema::create('activation_pins', function (Blueprint $t) {
            $t->id();
            $t->string('code', 32)->unique();
            $t->enum('status',['unused','reserved','used','void'])->default('unused')->index();
            $t->tinyInteger('bagan')->default(1);
            $t->unsignedBigInteger('price')->default(1500000);
            $t->foreignId('purchased_by')->nullable();
            $t->foreignId('pin_request_id')->nullable();
            $t->foreignId('used_by')->nullable();
            $t->timestamp('used_at')->nullable();
            $t->timestamps();
        });
    }

    // 3) Tambahkan FK di activation_pins (hanya jika kolom ada & FK belum ada)
    Schema::table('activation_pins', function (Blueprint $t) {
        if (Schema::hasColumn('activation_pins','purchased_by')) {
            $t->foreign('purchased_by', 'fk_activation_pins_owner')
              ->references('id')->on('users')->nullOnDelete();
        }
        if (Schema::hasColumn('activation_pins','pin_request_id') && Schema::hasTable('pin_requests')) {
            $t->foreign('pin_request_id', 'fk_activation_pins_request')
              ->references('id')->on('pin_requests')->nullOnDelete();
        }
        if (Schema::hasColumn('activation_pins','used_by')) {
            $t->foreign('used_by', 'fk_activation_pins_usedby')
              ->references('id')->on('users')->nullOnDelete();
        }
    });
}


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('pin_tables');
    }
};
