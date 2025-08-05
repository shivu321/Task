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
        Schema::create('admin_users', function (Blueprint $table) {
            $table->id();
            $table->uuid();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->enum("status",['ACTIVE','INACTIVE'])->default('ACTIVE');
             $table->string('profile_image')->nullable();
            $table->tinyInteger('is_admin')->default(0)->comment('0 = no 1 = yes');
            $table->enum('user_type',['ADMIN','HOTEL_MANAGER'])->default('ADMIN');
            $table->rememberToken();
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
        Schema::dropIfExists('admin_users');
    }
};
