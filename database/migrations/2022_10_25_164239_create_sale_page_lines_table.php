<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSalePageLinesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sale_page_lines', function (Blueprint $table) {
            $table->increments('id');

            $table->integer('sale_pages_id')->nullable()->unsigned()->index();
            $table->foreign('sale_pages_id')->references('id')->on('sale_pages')->onDelete('cascade');

            $table->string('text', 255)->charset('utf8')->nullable();
            $table->string('image', 255)->charset('utf8')->nullable();
            $table->string('link_vido', 255)->charset('utf8')->nullable();
            $table->string('link_line', 255)->charset('utf8')->nullable();
            $table->string('link_facebook', 255)->charset('utf8')->nullable();
            $table->string('phone', 255)->charset('utf8')->nullable();
            $table->string('shopee_link', 255)->charset('utf8')->nullable();
            $table->string('lasada_link', 255)->charset('utf8')->nullable();
            $table->string('button_title', 255)->charset('utf8')->nullable();
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
        Schema::dropIfExists('sale_page_lines');
    }
}
