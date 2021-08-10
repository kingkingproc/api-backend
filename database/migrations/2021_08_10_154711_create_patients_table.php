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
            $table->string('email');
            $table->integer('type')->nullable();
            $table->string('password');
            $table->string('name_first');
            $table->string('name_middle')->nullable();
            $table->string('name_last')->nullable();
            $table->integer('dob_month')->nullable();
            $table->integer('dob_day')->nullable();
            $table->integer('dob_year')->nullable();
            $table->string('sex')->nullable();
            $table->string('ethnicity')->nullable();
            $table->integer('primary_contact_id')->nullable();
            $table->integer('secondary_contact_id')->nullable();
            $table->integer('address_id')->nullable();
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
