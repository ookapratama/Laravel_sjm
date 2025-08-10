<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
        $table->id();
        $table->string('name');
         $table->string('username');
        $table->string('email')->unique();
        $table->string('password');
        $table->unsignedBigInteger('sponsor_id')->nullable();
        $table->unsignedBigInteger('upline_id')->nullable();
        $table->enum('position', ['left', 'right'])->nullable();
        $table->integer('level')->default(1);
        $table->string('tax_id')->nullable();
        $table->string('remember_token')->nullable();
         $table->string('address')->nullable();
  
        $table->json('bank_account')->nullable();
        $table->boolean('is_active')->default(true);
        $table->timestamp('joined_at')->nullable();
        $table->timestamp('email_verified_at')->nullable();
        $table->timestamps();
        $table->enum('role', ['super_admin', 'admin', 'finance', 'member']);
        $table->foreign('sponsor_id')->references('id')->on('users')->nullOnDelete();
        $table->foreign('upline_id')->references('id')->on('users')->nullOnDelete();
    });

        
    }

    public function down(): void
    {
       
    }
};
