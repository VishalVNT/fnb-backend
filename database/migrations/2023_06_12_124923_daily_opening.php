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
        //
        Schema::create('daily_openings', function (Blueprint $table) {
            $table->id();
            $table->integer('company_id');
            $table->integer('brand_id');
            $table->integer('qty')->comment('in ml');
            $table->date('date')->useCurrent();
            $table->integer('status')->nullable()->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
