<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSaleOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sale_order', function (Blueprint $table) {





            $table->increments('id');
            $table->integer('customer_id')->nullable()->unsigned()->index();
            $table->foreign('customer_id')->references('id')->on('customer')->onDelete('cascade');

            $table->integer('delivery_by_id')->nullable()->unsigned()->index();
            $table->foreign('delivery_by_id')->references('id')->on('delivered_by')->onDelete('cascade');

            $table->integer('sale_id')->nullable()->unsigned()->index();
            $table->foreign('sale_id')->references('id')->on('users')->onDelete('cascade');

            $table->date('date_time')->nullable();
            $table->string('order_id')->charset('utf8');
            $table->string('description', 255)->charset('utf8')->nullable();
            $table->string('name')->charset('utf8')->nullable();
            $table->string('telephone')->charset('utf8')->nullable();
            $table->string('email')->charset('utf8')->nullable();
            $table->text('address')->charset('utf8')->nullable();
            $table->double('shipping_price', 10, 2)->default(0.00);

            $table->double('cod_price_surcharge', 10, 2)->default(0.00);
            $table->double('main_discount', 10, 2)->default(0.00);
            $table->decimal('vat', 10, 2)->default(0.00);
            $table->decimal('total', 10, 2)->default(0.00);




            $table->enum('channal', ['facebook', 'line', 'tiktok', 'other', 'SP'])->charset('utf8')->default('facebook');
            $table->string('channal_remark')->charset('utf8')->nullable();

            $table->enum('payment_type', ['transfer', 'COD'])->charset('utf8')->default('transfer');

            $table->enum('status', ['order', 'paid', 'confirm', 'packing', 'delivery', 'finish', 'failed', 'only_item', 'only_delivery'])->charset('utf8')->default('order');

            //transfer

            $table->string('image_slip', 255)->charset('utf8')->nullable();

            $table->dateTime('payment_date')->nullable();
            $table->integer('bank_id')->unsigned()->index()->nullable();
            $table->foreign('bank_id')->references('id')->on('banks')->onDelete('cascade');



            $table->double('payment_qty', 10, 2)->default(0.00)->nullable();
            $table->string('account_number')->charset('utf8')->nullable();

            $table->string('create_by', 100)->charset('utf8')->nullable();
            $table->string('update_by', 100)->charset('utf8')->nullable();

            $table->string('page_id')->charset('utf8')->nullable();
            $table->string('fb_user_id')->charset('utf8')->nullable();
            $table->string('fb_comment_id')->charset('utf8')->nullable();

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
        Schema::dropIfExists('sale_order');
    }
}
