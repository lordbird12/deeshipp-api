<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('item', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('item_type_id')->unsigned()->index();
            $table->foreign('item_type_id')->references('id')->on('item_type')->onDelete('cascade');
           // $table->enum('type', ['normal', 'choice', 'set'])->charset('utf8');
            
            $table->string('item_id', 50)->charset('utf8');
            $table->string('name', 255)->charset('utf8');
            $table->string('barcode', 255)->charset('utf8')->nullable();
            $table->string('brand', 255)->charset('utf8')->nullable();
            $table->double('unit_cost', 10, 2)->default(0.00);
            $table->double('unit_price', 10, 2)->default(0.00);
          // $table->double('qty', 10, 2)->default(0.00);
            $table->double('total_price', 10, 2)->default(0.00);
        

            $table->string('image', 255)->charset('utf8')->nullable();
           
            $table->text('description')->charset('utf8')->nullable();
            $table->enum('set_type', ['normal', 'set_products'])->charset('utf8');
//$table->enum('setprice_type', ['item', 'set'])->charset('utf8');
    

            //location
            $table->integer('location_id')->nullable()->unsigned()->index();
            $table->foreign('location_id')->references('id')->on('location');

            //vendor
            $table->integer('vendor_id')->nullable()->unsigned()->index();
            $table->foreign('vendor_id')->references('id')->on('vendor');

           
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
        Schema::dropIfExists('item');
    }
}
