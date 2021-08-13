<?php

namespace App\Http\Controllers;

use App\Models\PatientDiagnosisPerformanceScore;
use Illuminate\Http\Request;

class PatientDiagnosisPerformanceScoreController extends Controller
{
    public function index()
    {
        //
        return patientdiagnosisperformancescore::all();
    }
}
