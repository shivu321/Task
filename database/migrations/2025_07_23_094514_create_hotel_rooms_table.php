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
        Schema::create('hotel_rooms', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('hotel_id')->unsigned()->index()->nullable();
            $table->foreign('hotel_id')->references('id')->on('hotels')->onDelete('cascade');
            $table->integer("no_of_guest")->nullable();
            $table->string('title')->nullable();
            $table->string('description')->nullable();
            $table->double('price',18,2)->default("0.00");
            $table->double("disc_price",18,2)->default("0.00");
            $table->double("tax",18,2)->default("0.00");
            $table->enum("status", ["OCCUPIED","VACANT"])->default("VACANT");
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
        Schema::dropIfExists('hotel_rooms');
    }
};
