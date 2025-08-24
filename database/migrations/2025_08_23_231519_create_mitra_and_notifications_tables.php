<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('mitra_profiles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('no_ktp')->nullable();
            $table->enum('jenis_kelamin', ['pria','wanita'])->nullable();
            $table->string('tempat_lahir')->nullable();
            $table->date('tanggal_lahir')->nullable();
            $table->enum('agama', ['islam','kristen','katolik','budha','hindu','lainnya'])->nullable();
            $table->text('alamat')->nullable();
            $table->string('rt')->nullable();
            $table->string('rw')->nullable();
            $table->string('desa')->nullable();
            $table->string('kecamatan')->nullable();
            $table->string('kota')->nullable();
            $table->string('kode_pos')->nullable();
            $table->string('nama_rekening')->nullable();
            $table->string('nama_bank')->nullable();
            $table->string('nomor_rekening')->nullable();
            $table->string('nama_ahli_waris')->nullable();
            $table->string('hubungan_ahli_waris')->nullable();
            $table->string('nama_sponsor')->nullable();
            $table->timestamps();

            $table->index('user_id', 'mitra_profiles_user_id_foreign');
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('message');
            $table->string('url')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->boolean('is_read')->default(false);
            // tidak ada updated_at di dump
        });

        // FK sesuai dump
        Schema::table('mitra_profiles', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void {
        Schema::table('mitra_profiles', fn(Blueprint $t) => $t->dropForeign(['user_id']));
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('mitra_profiles');
    }
};
