<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransferMoneyTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transfer_money', function (Blueprint $table) {
          

            $table->increments('id');

           // $table->integer('sale_order_id')->nullable()->unsigned()->index();
           // $table->foreign('sale_order_id')->references('id')->on('sale_order')->onDelete('cascade');

             $table->string('image',255)->charset('utf8')->nullable();
             $table->integer('bank_id')->unsigned()->index();
             $table->foreign('bank_id')->references('id')->on('banks')->onDelete('cascade');
    
           
             $table->date('date_time')->nullable();
             $table->string('qty',255)->charset('utf8')->nullable();
             $table->string('account_number')->charset('utf8')->nullable();
             
            
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
        Schema::dropIfExists('transfer_money');
    }
}
