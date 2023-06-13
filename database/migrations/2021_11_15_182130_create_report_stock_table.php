<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReportStockTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('report_stock', function (Blueprint $table) {
            $table->increments('id');

            $table->integer('doc_id')->unsigned()->index();
            $table->foreign('doc_id')->references('id')->on('doc')->onDelete('cascade');
           
        
            $table->integer('sale_order_id')->nullable()->unsigned()->index();
            $table->foreign('sale_order_id')->references('id')->on('sale_order');


            $table->string('report_id', 50)->charset('utf8')->nullable();
            $table->date('date')->nullable();
            $table->string('create_by', 100)->charset('utf8')->nullable();
            $table->enum('status', ['Open', 'Approved', 'Reject'])->charset('utf8')->default('Open');
            $table->string('status_by', 100)->charset('utf8')->nullable();
            $table->timestamp('status_at')->nullable();
            $table->string('reason')->charset('utf8')->nullable();
            $table->enum('type', ['Deposit', 'Withdraw','Movement','Adjust'])->charset('utf8')->nullable();
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
        Schema::dropIfExists('report_stock');
    }
}
