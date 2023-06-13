<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVendorsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('vendor', function (Blueprint $table) {
            $table->increments('id');

           // $table->integer('delivered_by_id')->unsigned()->index();
          //  $table->foreign('delivered_by_id')->references('id')->on('delivered_by')->onDelete('cascade');
            
          //  $table->integer('warehouse_id')->nullable()->unsigned()->index();
          //  $table->foreign('warehouse_id')->references('id')->on('warehouse');
            
            $table->string('name', 255)->charset('utf8')->nullable();
            $table->string('contact', 255)->charset('utf8')->nullable();
            $table->string('email', 255)->charset('utf8')->nullable();
            $table->string('phone', 255)->charset('utf8')->nullable();
            $table->string('address', 255)->charset('utf8')->nullable();
            
    

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
        Schema::dropIfExists('vendor');
    }
}
