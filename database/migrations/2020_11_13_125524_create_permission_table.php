<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePermissionTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('permission', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 255)->charset('utf8');
            $table->string('menu1', 255)->charset('utf8');
            $table->string('menu2', 255)->charset('utf8');
            $table->string('menu3', 255)->charset('utf8');
            $table->string('menu4', 255)->charset('utf8');
            $table->string('menu5', 255)->charset('utf8');
            $table->string('menu6', 255)->charset('utf8');
            $table->string('menu7', 255)->charset('utf8');
            $table->string('menu8', 255)->charset('utf8');
            $table->boolean('status')->default(1);
            $table->string('create_by', 100)->charset('utf8')->nullable();
            $table->string('update_by', 100)->charset('utf8')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('permission');
    }
}
