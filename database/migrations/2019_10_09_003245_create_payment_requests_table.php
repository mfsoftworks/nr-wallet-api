<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePaymentRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payment_requests', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->decimal('amount', 11, 2);
            $table->string('currency');
            $table->unsignedBigInteger('for_user_id');
            $table->unsignedBigInteger('from_user_id');
            $table->text('description');
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
        Schema::dropIfExists('payment_requests');
    }
}
