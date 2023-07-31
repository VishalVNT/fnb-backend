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
            $table->string('invoice_no',100);
            $table->integer('court_fees')->default(0)->nullable();
            $table->integer('tcs')->default(0)->nullable();
            $table->integer('total_amount')->default(0)->nullable();
            $table->string('batch_no',100)->nullable();
            $table->integer('discount')->default(0)->nullable();
            $table->integer('vat')->default(0)->nullable();
            $table->integer('vendor_id');
            $table->integer('total_item')->nullable();
            $table->date('invoice_date');
            $table->integer('isInvoice')->default(0)->nullable()->comment("0:TP,1:invoice");
            $table->integer('created_by');
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
