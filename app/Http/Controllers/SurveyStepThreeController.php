<?php

namespace App\Http\Controllers;

use App\Models\PatientDiagnosis;
use App\Models\LkupPatientDiagnosisAdditional;
use App\Models\LkupPatientDiagnosisTreatment;
use Illuminate\Http\Request;

class SurveyStepThreeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
        $request = $request->all();
        foreach($request as $key => $value)  {
            if ($key == 'additional_id') : $additional_array = $value;
            elseif ($key == 'treatment_id') : $treatment_array = $value;
            elseif ($key == 'cancer_type_id') : $int_cancer_type = $value;
            elseif ($key == 'cancer_stage_id') : $int_cancer_stage = $value;
        endif;
        }
        $patientdiagnosis_array = ['patient_id'=>$id,'cancer_type_id'=>$int_cancer_type,'stage_id'=>$int_cancer_stage];

        $PatientDiagnosis = PatientDiagnosis::create($patientdiagnosis_array);
        $PatientDiagnosisId = $PatientDiagnosis->diagnosis_id;

        $array[] = $PatientDiagnosis;

        foreach($additional_array as $add_id) {
            $new_array = ['diagnosis_id'=>$id,'additional_id'=>$add_id];
            $LkupPatientDiagnosisAdditional = LkupPatientDiagnosisAdditional::create($new_array);
            $array[] = $LkupPatientDiagnosisAdditional;
        }

        foreach($treatment_array as $treat_id) {
            $new_array = ['diagnosis_id'=>$id,'treatment_id'=>$treat_id];
            $LkupPatientDiagnosisTreatment = LkupPatientDiagnosisTreatment::create($new_array);
            $array[] = $LkupPatientDiagnosisTreatment;
        }

        return $array;
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
