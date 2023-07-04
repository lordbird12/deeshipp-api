<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserAddressSentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_address_sent', function (Blueprint $table) {
            $table->increments('id');

            $table->integer('user_id')->nullable()->unsigned()->index();
            $table->foreign('user_id')->references('id')->on('user')->onDelete('cascade');

            $table->integer('user_page_id')->nullable()->unsigned()->index();
            $table->foreign('user_page_id')->references('id')->on('user_page')->onDelete('cascade');

            $table->string('name')->charset('utf8')->nullable();
            $table->string('address', 255)->charset('utf8')->nullable();
            $table->string('tel', 255)->charset('utf8')->nullable();

            $table->string('remark', 255)->charset('utf8')->nullable();
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
        Schema::dropIfExists('user_address_sent');
    }
}
