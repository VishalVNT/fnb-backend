<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->integer('category_id');
            $table->integer('brand_id');
            $table->integer('company_id');
            $table->float('no_btl');
            $table->string('invoice_no',100)->nullable();
            $table->integer('mrp')->nullable();
            $table->integer('qty')->comment('in ml');
            $table->integer('court_fees')->nullable();
            $table->integer('tcs')->nullable();
            $table->string('batch_no')->nullable();
            $table->integer('vendor_id')->nullable();
            $table->integer('total_amount')->nullable();
            $table->integer('created_by')->nullable();
            $table->date('invoice_date')->nullable();
            $table->integer('status')->nullable()->default(1)->comment('1:active,0:inactive');
            $table->integer('is_deleted')->nullable()->default(0)->comment('1:active,0:inactive');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('purchases');
    }
};
