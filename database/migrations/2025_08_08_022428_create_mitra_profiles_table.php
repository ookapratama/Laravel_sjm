<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::create('mitra_profiles', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->onDelete('cascade');

        $table->string('no_ktp');
        $table->enum('jenis_kelamin', ['pria', 'wanita']);
        $table->string('tempat_lahir');
        $table->date('tanggal_lahir');
        $table->enum('agama', ['islam', 'kristen', 'katolik', 'budha', 'hindu', 'lainnya']);
        $table->text('alamat');
        $table->string('rt')->nullable();
        $table->string('rw')->nullable();
        $table->string('desa');
        $table->string('kecamatan');
        $table->string('kota');
        $table->string('kode_pos');

        // Rekening
        $table->string('nama_rekening');
        $table->string('nama_bank');
        $table->string('nomor_rekening');

        // Ahli waris
        $table->string('nama_ahli_waris');
        $table->string('hubungan_ahli_waris');
        $table->string('nama_sponsor');

        $table->timestamps();
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mitra_profiles');
    }
};
