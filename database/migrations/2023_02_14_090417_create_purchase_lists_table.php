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
        Schema::create('purchase_lists', function (Blueprint $table) {
            $table->id();
            $table->integer('company_id');
            $table->integer('invoice_no');
            $table->integer('court_fees')->nullable();
            $table->integer('tcs')->nullable();
            $table->integer('total_amount')->nullable();
            $table->integer('batch_no')->nullable();
            $table->integer('discount')->nullable();
            $table->integer('vat')->nullable();
            $table->integer('vendor_id')->nullable();
            $table->integer('total_item')->nullable();
            $table->date('invoice_date')->nullable();
            $table->integer('isInvoice')->nullable();
            $table->integer('created_by')->nullable();
            $table->integer('status')->nullable()->default(1)->comment('1:active,0:inactive');
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
