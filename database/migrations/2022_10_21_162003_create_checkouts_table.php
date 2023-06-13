<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCheckoutsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('checkouts', function (Blueprint $table) {
            $table->increments('id');
           
            $table->integer('sale_pages_id')->nullable()->unsigned()->index();
            $table->foreign('sale_pages_id')->references('id')->on('sale_pages')->onDelete('cascade');
            $table->integer('item_id')->nullable()->unsigned()->index();
            $table->foreign('item_id')->references('id')->on('item')->onDelete('cascade');
         
            $table->string('text', 255)->charset('utf8')->nullable();
            $table->double('discount', 10, 2)->default(0.00);
            $table->double('qty', 10, 2)->default(0.00);
            $table->double('price',  10, 2)->default(0.00);
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
        Schema::dropIfExists('checkouts');
    }
}
