<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateItemReturnsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('item_returns', function (Blueprint $table) {
            $table->increments('id');
            $table->string('order_id', 255)->charset('utf8')->nullable();
            $table->string('customer_phone', 255)->charset('utf8')->nullable();
            $table->string('image', 255)->charset('utf8')->nullable();
            $table->text('description', 255)->charset('utf8')->nullable();

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
        Schema::dropIfExists('item_returns');
    }
}
