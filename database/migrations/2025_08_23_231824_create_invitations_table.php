<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('invitations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('created_by');           // user pembuat (member/admin)
            $table->string('title');                            // judul undangan
            $table->text('description')->nullable();            // deskripsi/acara
            $table->dateTime('event_datetime')->nullable();     // waktu acara (bebas diatur)
            $table->string('venue_name')->nullable();           // nama tempat
            $table->string('venue_address')->nullable();        // alamat
            $table->string('city')->nullable();                 // kota
            $table->string('theme')->default('luxury');         // tema (untuk template mewah)
            $table->string('primary_color')->nullable();        // warna utama
            $table->string('secondary_color')->nullable();      // warna sekunder
            $table->string('background_image')->nullable();     // path gambar background
            $table->string('slug')->unique();                   // slug public (dipakai di URL)
            $table->boolean('is_active')->default(true);        // aktif/nonaktif
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void {
        Schema::table('invitations', fn(Blueprint $t) => $t->dropForeign(['created_by']));
        Schema::dropIfExists('invitations');
    }
};
