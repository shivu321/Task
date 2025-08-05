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
        Schema::create('menus', function (Blueprint $table) {
            $table->id();
            $table->string("menu", 200);
            $table->string('code', 200);
            $table->enum("menu_for", ['ADMIN'])->default("ADMIN")->nullable();
            $table->enum('can_create', ['0', '1'])->nullable();
            $table->enum('can_read', ['0', '1'])->nullable();
            $table->enum('can_update', ['0', '1'])->nullable();
            $table->enum('can_delete', ['0', '1'])->nullable();
            $table->enum('can_print', ['0', '1'])->nullable();
            $table->integer('parent_menu_id')->default('0');
            $table->integer('ordering')->default('1');
            $table->enum('status', ['ACTIVE', 'INACTIVE'])->default('ACTIVE');
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
        Schema::dropIfExists('menus');
    }
};
