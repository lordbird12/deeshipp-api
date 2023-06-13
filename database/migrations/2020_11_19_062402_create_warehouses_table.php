<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWarehousesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('warehouse', function (Blueprint $table) {
            $table->increments('id');
            $table->string('code', 255)->charset('utf8')->nullable();
            $table->string('name', 255)->charset('utf8')->nullable();
            $table->string('wh_address', 255)->charset('utf8')->nullable();
            $table->string('wh_telephone', 255)->charset('utf8')->nullable();
            $table->string('wh_description', 255)->charset('utf8')->nullable();
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
        Schema::dropIfExists('warehouse');
    }
}
