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

            $table->integer('permission_id')->nullable()->unsigned()->index();
            $table->foreign('permission_id')->references('id')->on('permission')->onDelete('cascade');

            $table->integer('user_ref_id')->nullable()->unsigned()->index();
            $table->foreign('user_ref_id')->references('id')->on('user')->onDelete('cascade');

            $table->string('user_id', 50)->unique()->charset('utf8');
            $table->string('password', 100)->charset('utf8')->nullable();
            $table->string('first_name', 255)->charset('utf8');
            $table->string('last_name', 255)->charset('utf8');
            $table->string('email', 100)->charset('utf8');
            $table->string('tel', 255)->charset('utf8');
            $table->string('tel2', 255)->charset('utf8');
            $table->string('image', 255)->charset('utf8')->nullable();

            $table->string('shop_name', 255)->charset('utf8');
            $table->string('shop_address', 255)->charset('utf8');

            $table->double('wallet', 10, 2)->default(0.00);


            $table->integer('delivered_by_id')->nullable()->unsigned()->index();
            $table->foreign('delivered_by_id')->references('id')->on('delivered_by')->onDelete('cascade');
            $table->double('delivered_fee', 10, 2)->default(0.00);



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
