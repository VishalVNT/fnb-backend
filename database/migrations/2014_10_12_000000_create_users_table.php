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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('company_id')->nullable();
            $table->string('name',100);
            $table->string('mobile',12);
            $table->integer('type')->comment("1:admin,0:client")->default(0);
            $table->string('email')->unique();
            $table->string('read')->nullable();
            $table->string('write_module')->nullable();
            $table->string('write')->nullable();
            $table->string('created_by')->default(0);
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->integer('status')->nullable()->default(1)->comment('1:active,0:inactive');
            $table->integer('is_deleted')->nullable()->default(0)->comment('1:active,0:inactive');            
            $table->rememberToken();
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
        Schema::dropIfExists('users');
    }
};
