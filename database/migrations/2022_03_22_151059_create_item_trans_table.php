<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateItemTransTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('item_trans', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('item_id')->unsigned()->index();
            $table->foreign('item_id')->references('id')->on('item')->onDelete('cascade');


            $table->integer('report_stock_id')->nullable()->unsigned()->index();
            $table->foreign('report_stock_id')->references('id')->on('report_stock')->onDelete('cascade');

            $table->integer('sale_order_id')->nullable()->unsigned()->index();
            $table->foreign('sale_order_id')->references('id')->on('sale_order');
            
            
            $table->integer('customer_id')->nullable()->unsigned()->index();
            $table->foreign('customer_id')->references('id')->on('customer');

            $table->integer('main_item_id')->nullable()->unsigned()->index();
            $table->foreign('main_item_id')->references('id')->on('item');

            $table->integer('vendor_id')->nullable()->unsigned()->index();
            $table->foreign('vendor_id')->references('id')->on('vendor');

            $table->date('date')->nullable();
            $table->integer('stock')->default(0);
            $table->integer('qty')->default(0);
            $table->integer('balance')->default(0);
            //$table->integer('exc')->nullable();
           // $table->integer('lot_maker')->nullable();
            $table->integer('adj_qa')->nullable();

            
            //location1
            $table->integer('location_1_id')->nullable()->unsigned()->index();
            $table->foreign('location_1_id')->references('id')->on('location');

            //location2
            $table->integer('location_2_id')->nullable()->unsigned()->index();
            $table->foreign('location_2_id')->references('id')->on('location');

            $table->string('po_number')->charset('utf8')->nullable();

           


            $table->integer('delevery_order_id')->nullable()->unsigned()->index();
           
            $table->enum('operation', ['booking', 'finish'])->charset('utf8');
            $table->string('remark')->charset('utf8')->nullable();
            $table->enum('type', ['Deposit', 'Withdraw', 'Adjust', 'QC', 'Mat_QC', 'Mat_Cancel'])->charset('utf8');
            $table->string('description', 255)->charset('utf8')->nullable(); //description
            $table->boolean('status')->default(0);
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
        Schema::dropIfExists('item_trans');
    }
}
