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
        Schema::create('admin_otps', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('admin_id')->index()->unsigned()->nullable();
            $table->foreign('admin_id')->references('id')->on('admin_users')->onDelete('cascade');
            $table->integer('otp')->nullable();
            $table->timestamp('expired_at')->nullable();
            $table->string("ref_no")->nullable();
            $table->enum("otp_type", ['RESET_PASSWORD', 'CHANGE_PASSWORD'])->default("RESET_PASSWORD");
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
        Schema::dropIfExists('admin_otps');
    }
};
