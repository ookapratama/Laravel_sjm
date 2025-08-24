<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('guest_entries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('invitation_id');          // relasi ke undangan
            $table->unsignedBigInteger('referrer_user_id')->nullable(); // user pemilik referral
            $table->string('referral_code')->nullable();          // kode referral pada QR
            $table->string('name');                               // nama tamu
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('notes')->nullable();
            $table->enum('attend_status', ['confirmed','maybe','declined','checked_in'])->default('confirmed');
            $table->timestamp('check_in_at')->nullable();         // waktu check-in saat scan
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();

            $table->foreign('invitation_id')->references('id')->on('invitations')->cascadeOnDelete();
            $table->foreign('referrer_user_id')->references('id')->on('users')->nullOnDelete();

            $table->index(['referral_code']);
        });
    }

    public function down(): void {
        Schema::table('guest_entries', function (Blueprint $table) {
            $table->dropForeign(['invitation_id']);
            $table->dropForeign(['referrer_user_id']);
        });
        Schema::dropIfExists('guest_entries');
    }
};
