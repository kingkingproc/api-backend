<?php

namespace App\Http\Controllers;

use App\Models\PatientDiagnosisTumorSite;
use Illuminate\Http\Request;

class PatientDiagnosisTumorSiteController extends Controller
{
    public function index()
    {
        //
        return patientdiagnosistumorsite::all();
    }
}
