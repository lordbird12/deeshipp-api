<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLeaveTablesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('leave_tables', function (Blueprint $table) {
            $table->increments('id');

            $table->integer('user_id')->nullable()->unsigned()->index();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            $table->integer('leave_type_id')->nullable()->unsigned()->index();
            $table->foreign('leave_type_id')->references('id')->on('leave_types')->onDelete('cascade');

            $table->date('date');
            $table->string('time_in', 255)->charset('utf8');
            $table->string('time_out', 255)->charset('utf8');

            $table->text('description')->charset('utf8')->nullable();

            $table->enum('type', ['Request', 'Reject', 'Approve'])->charset('utf8')->default('Request');
            $table->text('remark')->charset('utf8')->nullable();
            $table->string('approve_by', 255)->charset('utf8');

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
        Schema::dropIfExists('leave_tables');
    }
}
