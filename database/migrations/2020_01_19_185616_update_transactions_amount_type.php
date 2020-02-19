<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateTransactionsAmountType extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->bigInteger('amount')->change();
            $table->dropColumn('sender_fee');
            $table->dropColumn('international_fee');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->decimal('amount', 11, 2)->change();
            $table->decimal('sender_fee', 11, 2);
            $table->decimal('international_fee', 11, 2);
        });
    }
}
