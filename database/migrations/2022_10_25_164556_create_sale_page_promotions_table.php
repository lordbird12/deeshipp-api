<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSalePagePromotionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sale_page_promotions', function (Blueprint $table) {
            $table->increments('id');
           
            $table->integer('sale_pages_id')->nullable()->unsigned()->index();
            $table->foreign('sale_pages_id')->references('id')->on('sale_pages')->onDelete('cascade');
            
            //$table->integer('main_sale_pages_id')->unsigned()->index();
            //$table->foreign('main_sale_pages_id')->references('id')->on('sale_pages')->onDelete('cascade');

            $table->string('name', 255)->charset('utf8')->nullable();
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
        Schema::dropIfExists('sale_page_promotions');
    }
}
