<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBanksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('banks', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 255)->charset('utf8');
             $table->string('image', 255)->charset('utf8')->nullable();
             $table->string('first_name', 255)->charset('utf8');
             $table->string('last_name', 255)->charset('utf8');
             $table->string('account_number')->charset('utf8')->nullable();
             $table->string('create_by', 100)->charset('utf8')->nullable();
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
        Schema::dropIfExists('banks');
    }
}
