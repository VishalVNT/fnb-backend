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
            $table->integer('company_id');
            $table->integer('branch_id');
            $table->integer('category_id');
            $table->integer('brand_id');
            $table->float('opening');
            $table->float('closing');
            $table->float('physical_closing');
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
