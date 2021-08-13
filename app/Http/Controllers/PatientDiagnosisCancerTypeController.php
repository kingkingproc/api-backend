<?php

namespace App\Http\Controllers;

use App\Models\PatientDiagnosisCancerType;
use Illuminate\Http\Request;

class PatientDiagnosisCancerTypeController extends Controller
{
    public function index()
    {
        //
        return patientdiagnosiscancertype::all();
    }
}
