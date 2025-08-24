<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('financial_reports', function (Blueprint $table) {
            $table->id();
            // Dump tidak mendeskripsikan kolom selain id+timestamps
            $table->timestamps();
        });

        Schema::create('income_details', function (Blueprint $table) {
            $table->id();
            // Dump tidak mendeskripsikan kolom selain id+timestamps
            $table->timestamps();
        });

        Schema::create('temp_user_ids', function (Blueprint $table) {
            // Mengikuti dump: engine & collation berbeda tidak perlu disetel di Laravel
            $table->integer('id');
            $table->integer('level');
        });
    }

    public function down(): void {
        Schema::dropIfExists('temp_user_ids');
        Schema::dropIfExists('income_details');
        Schema::dropIfExists('financial_reports');
    }
};
