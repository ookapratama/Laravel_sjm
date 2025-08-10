<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPaymentMethodToPreRegistrationsTable extends Migration
{
    public function up()
    {
        Schema::table('pre_registrations', function (Blueprint $table) {
            $table->string('payment_method')->after('payment_proof')->nullable();
        });
    }

    public function down()
    {
        Schema::table('pre_registrations', function (Blueprint $table) {
            $table->dropColumn('payment_method');
        });
    }
}
