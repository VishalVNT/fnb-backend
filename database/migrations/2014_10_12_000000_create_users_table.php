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
            $table->integer('type')->comment("1:admin,0:user")->default(0);
            $table->string('email');
            $table->text('read')->nullable();
            $table->text('write_module')->nullable();
            $table->text('write')->nullable();
            $table->string('created_by')->default(0);
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->integer('status')->nullable()->default(1)->comment('1:active,0:inactive');
            $table->integer('is_deleted')->nullable()->default(0)->comment('1:active,0:inactive');            
            $table->string('remember_token',100)->nullable();
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
