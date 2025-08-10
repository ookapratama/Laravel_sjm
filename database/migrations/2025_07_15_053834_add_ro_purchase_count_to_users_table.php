<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRoPurchaseCountToUsersTable extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
    $table->unsignedInteger('ro_purchase_count')->default(0);
});
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('ro_purchase_count');
        });
    }
}
