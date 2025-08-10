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
         Schema::create('bonus_transactions', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->onDelete('cascade');
        $table->foreignId('from_user_id')->nullable()->constrained('users')->onDelete('set null');
        $table->enum('type', ['sponsor', 'pairing', 'reward', 'admin']);
        $table->decimal('amount', 15, 2);
        $table->decimal('tax', 15, 2)->default(0);
        $table->decimal('net_amount', 15, 2);
        $table->text('notes')->nullable();
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
        Schema::dropIfExists('bonus_transactions');
    }
};
