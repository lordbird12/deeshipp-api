<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransectionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transection', function (Blueprint $table) {
            $table->increments('id');

            $table->integer('user_id')->nullable()->unsigned()->index();
            $table->foreign('user_id')->references('id')->on('user')->onDelete('cascade');

            $table->integer('order_id')->nullable()->unsigned()->index();
            $table->foreign('order_id')->references('id')->on('order')->onDelete('cascade');


            $table->date('date')->nullable();
            $table->string('time')->charset('utf8')->nullable();
            $table->string('refNo', 255)->charset('utf8')->nullable();

            $table->string('merchantId', 255)->charset('utf8')->nullable();
            $table->string('cardtype', 255)->charset('utf8')->nullable();
            $table->string('cc', 255)->charset('utf8')->nullable();
            $table->string('qrcode', 255)->charset('utf8')->nullable();
            $table->double('price', 10, 2)->default(0.00);
            $table->double('fee', 10, 2)->default(0.00);
            $table->double('total', 10, 2)->default(0.00);

            $table->double('pre_wallet', 10, 2)->default(0.00);
            $table->double('new_wallet', 10, 2)->default(0.00);


            $table->string('type', 100)->charset('utf8')->nullable();
            $table->boolean('status')->default(0);
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
        Schema::dropIfExists('transection');
    }
}
