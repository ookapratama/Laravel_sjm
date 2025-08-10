<?php 
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPaymentFieldsToCashTransactionsTable extends Migration
{
    public function up()
    {
        Schema::table('cash_transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('cash_transactions', 'payment_channel')) {
                $table->string('payment_channel')->nullable()->after('notes');
            }
            if (!Schema::hasColumn('cash_transactions', 'payment_reference')) {
                $table->string('payment_reference')->nullable()->after('payment_channel');
            }
        });
    }

    public function down()
    {
        Schema::table('cash_transactions', function (Blueprint $table) {
            $table->dropColumn(['payment_channel', 'payment_reference']);
        });
    }
}
