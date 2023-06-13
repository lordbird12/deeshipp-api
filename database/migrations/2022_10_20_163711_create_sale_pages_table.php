<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSalePagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sale_pages', function (Blueprint $table) {

            $table->increments('id');
            
            
            $table->integer('select_product_id')->nullable()->unsigned()->index();
            $table->foreign('select_product_id')->references('id')->on('item')->onDelete('cascade');
            
            $table->integer('delivery_id')->nullable()->unsigned()->index();
            $table->foreign('delivery_id')->references('id')->on('delivered_by')->onDelete('cascade');

            $table->integer('bank_id')->nullable()->unsigned()->index();
            $table->foreign('bank_id')->references('id')->on('banks')->onDelete('cascade');

             $table->string('name',255)->charset('utf8')->nullable();
             $table->string('sale_pages_url',255)->charset('utf8')->nullable();
             $table->string('thank_you_url', 255)->charset('utf8')->nullable();
             $table->string('link_line', 255)->charset('utf8')->nullable();
             $table->string('link_facebook', 255)->charset('utf8')->nullable();

             $table->string('create_by', 100)->charset('utf8')->nullable();
             $table->string('update_by', 100)->charset('utf8')->nullable();
        
           
            // $table->date('date_time')->nullable();
         //    $table->string('qty',255)->charset('utf8')->nullable();
          //   $table->string('account_number')->charset('utf8')->nullable();
             
            
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
        Schema::dropIfExists('sale_pages');



    }
}
