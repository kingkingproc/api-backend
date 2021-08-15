<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePatientDiagnosesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('patient_diagnoses', function (Blueprint $table) {
            //$table->id();
            $table->bigIncrements('diagnosis_id');
            $table->integer('patient_id');
            $table->integer('cancer_type_id');
            $table->integer('cell_type_id');
            $table->integer('stage_id');
            $table->integer('tumor_size_id');
            $table->integer('tumor_site_id');
            $table->integer('performance_score_id');
            $table->string('pathology');
            $table->date('dod');
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
        Schema::dropIfExists('patient_diagnoses');
    }
}
