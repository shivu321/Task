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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->double('tax', 18, 2)->default(0);
            $table->double('discount', 18, 2)->default(0);
            $table->double('sub_total', 18, 2)->default(0);
            $table->double('grand_total', 18, 2)->default(0);
            $table->enum('status', ['PENDING', 'SUCCESS', 'REFUND', 'INCOMPLETE'])->default('PENDING');
            $table->string('payment_intent', 100)->nullable();
            $table->string('client_secrete', 100)->nullable();
            $table->string('charges_intent', 100)->nullable();
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
        Schema::dropIfExists('transactions');
    }
};
