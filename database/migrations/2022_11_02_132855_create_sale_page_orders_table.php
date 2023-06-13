<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSalePageOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sale_page_orders', function (Blueprint $table) {
            $table->increments('id');

            $table->integer('item_id')->nullable()->unsigned()->index();
            $table->foreign('item_id')->references('id')->on('item')->onDelete('cascade');

            $table->string('order_id')->charset('utf8');
            $table->string('text',255)->charset('utf8')->nullable();
            $table->enum('payment_type', ['transfer', 'COD'])->charset('utf8')->default('transfer');
            $table->string('slip_image',255)->charset('utf8')->nullable();
            $table->date('date_time')->nullable();
            $table->double('qty', 10, 2)->default(0.00);
            $table->double('price', 10, 2)->default(0.00);
            $table->double('discount', 10, 2)->default(0.00);
            $table->double('total', 10, 2)->default(0.00);
            $table->string('name',255)->charset('utf8')->nullable();
            $table->string('email',255)->charset('utf8')->nullable();
            $table->string('address',255)->charset('utf8')->nullable();
            $table->string('phone',255)->charset('utf8')->nullable();
            $table->string('description', 255)->charset('utf8')->nullable();
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
        Schema::dropIfExists('sale_page_orders');
    }
}
