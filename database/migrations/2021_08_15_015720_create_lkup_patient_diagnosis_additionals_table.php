<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLkupPatientDiagnosisAdditionalsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('lkup_patient_diagnosis_additionals', function (Blueprint $table) {
            $table->id();
            $table->integer('diagnosis_id');
            $table->integer('additional_id');
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
        Schema::dropIfExists('lkup_patient_diagnosis_additionals');
    }
}
