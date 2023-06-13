<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateItemLinesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('item_lines', function (Blueprint $table) {
             
            $table->increments('id');
            $table->integer('item_id')->unsigned()->index();
            $table->foreign('item_id')->references('id')->on('item')->onDelete('cascade');

            $table->integer('main_item_id')->unsigned()->index();
            $table->foreign('main_item_id')->references('id')->on('item')->onDelete('cascade');
            
            $table->double('qty', 10, 2)->default(0.00);
            $table->double('price',  10, 2)->default(0.00);
            $table->double('total', 10, 2)->default(0.00);
            $table->enum('type', ['normal', 'promotion'])->charset('utf8');
                     
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
        Schema::dropIfExists('item_lines');
    }
}
