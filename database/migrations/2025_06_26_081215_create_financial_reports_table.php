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
       Schema::create('financial_reports', function (Blueprint $table) {
        $table->id();
        $table->string('period'); // Contoh: "2025-06"
        $table->decimal('total_bonus', 15, 2)->default(0);
        $table->decimal('total_tax', 15, 2)->default(0);
        $table->decimal('total_sales', 15, 2)->default(0);
        $table->decimal('total_withdraw', 15, 2)->default(0);
        $table->timestamp('generated_at')->nullable();
        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('financial_reports');
    }
};
