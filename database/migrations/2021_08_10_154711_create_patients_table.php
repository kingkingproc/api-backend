<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePatientsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            $table->string('email')->nullable();
            $table->integer('type')->nullable();
            $table->string('sub')->nullable();
            $table->string('name_first');
            $table->string('name_middle')->nullable();
            $table->string('name_last');
            $table->date('dob')->nullable();
            $table->string('sex')->nullable();
            $table->integer('address_id')->nullable();
            $table->integer('ethnicity_id')->nullable();
            $table->boolean('is_complete')->default(0);
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
        Schema::dropIfExists('patients');
    }
}
