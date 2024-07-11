<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('physical_histories', function (Blueprint $table) {
            $table->id();
            $table->integer('company_id');
            $table->integer('brand_id');
            $table->string('qty');
            $table->text('date');
            $table->integer('status')->nullable()->default(1)->comment('1:active,0:inactive');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('physical_histories');
    }
};
