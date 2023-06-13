<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDocsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('doc', function (Blueprint $table) {
            $table->increments('id');
            $table->string('format', 50)->charset('utf8');
            $table->string('name', 255)->charset('utf8');
            $table->string('gen')->charset('utf8')->nullable();
            $table->string('prefix', 50)->charset('utf8')->nullable();
            $table->string('date', 50)->charset('utf8')->nullable();
            $table->string('run_number', 50)->charset('utf8')->nullable();

            $table->string('controll_number')->charset('utf8')->nullable();

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
        Schema::dropIfExists('doc');
    }
}
