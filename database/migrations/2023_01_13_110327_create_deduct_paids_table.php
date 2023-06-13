<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDeductPaidsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('deduct_paids', function (Blueprint $table) {
            $table->increments('id');

            $table->integer('user_id')->nullable()->unsigned()->index();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            $table->integer('deduct_type_id')->nullable()->unsigned()->index();
            $table->foreign('deduct_type_id')->references('id')->on('deduct_types')->onDelete('cascade');

            $table->string('price', 255)->charset('utf8');
            $table->text('description')->charset('utf8')->nullable();

            $table->enum('type', ['Once', 'All Month', 'All Year'])->charset('utf8')->default('Once');

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
        Schema::dropIfExists('deduct_paids');
    }
}
