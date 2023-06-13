<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');

           
            $table->integer('branch_id')->unsigned()->index();
            $table->foreign('branch_id')->references('id')->on('branch')->onDelete('cascade');

            
            $table->integer('department_id')->unsigned()->index();
            $table->foreign('department_id')->references('id')->on('department')->onDelete('cascade');

            $table->integer('position_id')->unsigned()->index();
            $table->foreign('position_id')->references('id')->on('position')->onDelete('cascade');

            $table->integer('permission_id')->unsigned()->index();
            $table->foreign('permission_id')->references('id')->on('permission')->onDelete('cascade');

           // $table->string('code_id',50)->charset('utf8')->nullable();
           

            
            $table->string('user_id', 50)->unique()->charset('utf8');
            $table->string('password', 100)->charset('utf8')->nullable();
            $table->string('first_name', 255)->charset('utf8');
            $table->string('last_name', 255)->charset('utf8');
            $table->string('email', 100)->charset('utf8');
            $table->string('image', 255)->charset('utf8')->nullable();
            $table->string('image_signature', 255)->charset('utf8')->nullable();
            //$table->enum('status', ['Yes', 'No', 'Request'])->charset('utf8')->default('No');
            $table->boolean('status')->default(1);
            
            // $table->enum('type', ['admin', 'agency_command', 'sub_agency_command', 'affiliation'])->charset('utf8')->default('admin');
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
        Schema::dropIfExists('users');
    }
}
