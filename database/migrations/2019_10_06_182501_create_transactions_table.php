<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->decimal('amount', 11, 2)->nullable();
            $table->string('currency')->nullable();
            $table->string('status')->nullable();
            $table->string('type')->nullable();
            $table->unsignedBigInteger('for_user_id');
            $table->unsignedBigInteger('from_user_id');
            $table->string('stripe_transaction_id')->nullable();
            $table->decimal('sender_fee', 11, 2)->nullable();
            $table->decimal('international_fee', 11, 2)->nullable();
            $table->text('description')->nullable();
            $table->timestamps();

            $table->foreign('for_user_id')
                ->references('id')
                ->on('users');

            $table->foreign('from_user_id')
                ->references('id')
                ->on('users');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('transactions');
    }
}
