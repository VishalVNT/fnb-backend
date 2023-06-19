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
        Schema::create('stocks', function (Blueprint $table) {
            $table->id();
            $table->integer('company_id');
            $table->integer('branch_id');
            $table->integer('category_id');
            $table->integer('brand_id');
            $table->integer('qty')->comment("available stock");
            $table->integer('physical_closing')->comment("available physical stock")->nullable();
            $table->integer('cost_price')->comment("mrp of liqour")->nullable();
            $table->integer('btl_selling_price')->comment("selling price of bottle")->nullable();
            $table->integer('peg_selling_price')->comment("selling price of 1 peg")->nullable();
            $table->integer('store_btl')->nullable()->default(0);
            $table->integer('store_peg')->nullable()->default(0);
            $table->integer('bar1_btl')->nullable()->default(0);
            $table->integer('bar1_peg')->nullable()->default(0);
            $table->integer('bar2_btl')->nullable()->default(0);
            $table->integer('bar2_peg')->nullable()->default(0);
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
        Schema::dropIfExists('stocks');
    }
};
