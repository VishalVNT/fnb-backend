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
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->integer('company_id');
             $table->integer('category_id')->nullable();
            $table->integer('brand_id');
            $table->integer('qty')->nullable();
            $table->integer('purchase_price')->nullable();
            $table->integer('sale_price');
            $table->integer('discount')->nullable();
            $table->integer('final_amount')->nullable();
            $table->string('description')->nullable();
            $table->integer('sales_type')->default(1)->comment('1:sale,2:compli,3:combo');
            $table->integer('no_btl');
            $table->integer('no_peg');
            $table->integer('is_cocktail')->default(0)->comment('1:cocktail,0:liqour');
            $table->date('sales_date')->nullable();
            $table->integer('created_by');
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
        Schema::dropIfExists('sales');
    }
};
