<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('order', function (Blueprint $table) {
            $table->increments('id');

            $table->integer('user_id')->nullable()->unsigned()->index();
            $table->foreign('user_id')->references('id')->on('user')->onDelete('cascade');

            $table->string('code', 255)->charset('utf8')->nullable();
            $table->date('date')->nullable();
            $table->string('time',255)->charset('utf8')->nullable();
            $table->string('type', 100)->charset('utf8')->nullable();
            $table->integer('qty', 100)->default(1);
            $table->double('price', 10, 2)->default(0.00);
            $table->double('discount', 10, 2)->default(0.00);
            $table->double('total', 10, 2)->default(0.00);
            $table->boolean('status')->default(0);
            $table->boolean('payment')->default(0);
            $table->string('remark', 255)->charset('utf8')->nullable();

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
        Schema::dropIfExists('order');
    }
}
