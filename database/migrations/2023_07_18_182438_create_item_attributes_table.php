<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateItemAttributesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('item_attribute', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('item_id')->unsigned()->index();
            $table->foreign('item_id')->references('id')->on('item')->onDelete('cascade');

            $table->text('image')->charset('utf8')->nullable();
            $table->string('name')->charset('utf8')->nullable();
            $table->double('unit_cost', 10, 2)->default(0.00);
            $table->double('unit_price', 10, 2)->default(0.00);
            $table->string('barcode')->charset('utf8')->nullable();
            $table->boolean('status')->default(1);

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
        Schema::dropIfExists('item_attribute');
    }
}
