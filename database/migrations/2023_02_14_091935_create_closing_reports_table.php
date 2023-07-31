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
        Schema::create('closing_reports', function (Blueprint $table) {
            $table->id();
            $table->integer('company_id')->nullable();
            $table->integer('branch_id')->nullable();
            $table->integer('category_id')->nullable();
            $table->integer('brand_id')->nullable();
            $table->float('opening_balance');
            $table->float('closing_balance');
            $table->float('physical_closing')->nullable()->comment('opening for next day');
            $table->integer('selling_price')->nullable()->comment('peg price');
            $table->integer('variance_sales')->nullable()->comment('total sale price');
            $table->integer('variance_in_cost')->nullable()->comment('difference in tp & sale');
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
        Schema::dropIfExists('closing_reports');
    }
};
