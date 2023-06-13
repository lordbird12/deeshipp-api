<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('product', function (Blueprint $table) {
            $table->increments('id');
            $table->string('code', 255)->charset('utf8')->nullable();
            $table->string('name', 255)->charset('utf8')->nullable();
            $table->string('image', 255)->charset('utf8')->nullable();
            $table->string('barcode', 50)->charset('utf8')->nullable();
            $table->string('brand', 255)->charset('utf8')->nullable();
            $table->float('price')->default(0)->nullable();
            $table->float('weight')->default(0)->nullable();
            $table->float('length')->default(0)->nullable();
            $table->float('width')->default(0)->nullable();
            $table->float('heigth')->default(0)->nullable();
            $table->text('description', 255)->charset('utf8')->nullable();

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
        Schema::dropIfExists('product');
    }
}
