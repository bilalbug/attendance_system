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
        Schema::create('timewith_i_p_s', function (Blueprint $table) {
            $table->id();
            $table->string('ip_address');
            $table->string('intime')->nullable();
            $table->string('outtime')->nullable();
            $table->integer('working_minutes')->nullable();
            $table->string('location')->nullable();
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
        Schema::dropIfExists('timewith_i_p_s');
    }
};
