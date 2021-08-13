<?php

namespace App\Http\Controllers;

use App\Models\PatientDiagnosisCancerStage;
use Illuminate\Http\Request;

class PatientDiagnosisCancerStageController extends Controller
{
    public function index()
    {
        //
        return patientdiagnosiscancerstage::all();
    }
}
