<?php

namespace App\Http\Controllers;

use App\Models\PatientDiagnosisTumorSize;
use Illuminate\Http\Request;

class PatientDiagnosisTumorSizeController extends Controller
{
    public function index()
    {
        //
        return patientdiagnosistumorsize::all();
    }
}
