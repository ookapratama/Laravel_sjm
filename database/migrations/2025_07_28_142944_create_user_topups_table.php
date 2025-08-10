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
        Schema::create('user_topups', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('user_id');
    $table->integer('amount');
    $table->integer('flush_cycle');
    $table->string('type')->default('auto'); // auto / manual
    $table->string('source')->nullable(); // keterangan
    $table->string('notes')->nullable();
    $table->timestamps();

    $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
});

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_topups');
    }
};
