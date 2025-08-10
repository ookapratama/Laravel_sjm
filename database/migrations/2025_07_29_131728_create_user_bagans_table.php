<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserBagansTable extends Migration
{
    public function up(): void
    {
        Schema::create('user_bagans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->tinyInteger('bagan'); // 1-5
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('pairing_level_count')->default(0);
            $table->unsignedBigInteger('held_bonus')->default(0);
            $table->timestamp('activated_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'bagan']); // unik per user per bagan
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_bagans');
    }
}
