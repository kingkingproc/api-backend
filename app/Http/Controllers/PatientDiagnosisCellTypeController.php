<?php

namespace App\Http\Controllers;

use App\Models\PatientDiagnosisCellType;
use Illuminate\Http\Request;

class PatientDiagnosisCellTypeController extends Controller
{
    public function index()
    {
        //
        return patientdiagnosiscelltype::all();
    }
}
