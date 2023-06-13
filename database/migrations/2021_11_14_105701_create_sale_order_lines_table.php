<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSaleOrderLinesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sale_order_line', function (Blueprint $table) {
            $table->increments('id');

            $table->integer('sale_order_id')->nullable()->unsigned()->index();
            $table->foreign('sale_order_id')->references('id')->on('sale_order')->onDelete('cascade');

            

            $table->integer('item_id')->nullable()->unsigned()->index();
            $table->foreign('item_id')->references('id')->on('item')->onDelete('cascade');

            $table->string('item_name')->charset('utf8')->nullable();
    

            $table->integer('qty');
            $table->double('unit_price', 10, 2)->default(0.00);
            $table->double('discount', 10, 2)->default(0.00);
            $table->double('total', 10, 2)->default(0.00);

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
        Schema::dropIfExists('sale_order_line');
    }
}
