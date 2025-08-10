<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserPairingLogsTable extends Migration
{
    public function up()
    {
        Schema::create('user_pairing_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedTinyInteger('level'); // level 1–∞
            $table->unsignedTinyInteger('cycle')->default(0)->comment('untuk pairing cycle jika digunakan');
            $table->enum('type', ['PAIR', 'RO'])->default('PAIR');
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'level', 'cycle', 'type']);
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_pairing_logs');
    }
}

