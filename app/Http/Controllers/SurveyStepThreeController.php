<?php

namespace App\Http\Controllers;

use App\Helper\Helper;
use App\Models\Patient;
use App\Models\PatientDiagnosis;
use App\Models\PatientDiagnosisAdditional;
use App\Models\PatientDiagnosisTreatment;
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
        $token = request();
        $the_object = Helper::verifyJasonToken($token);

        $patient = patient::where('sub',$the_object->sub)->where('email', $the_object->email)->get();

        $request = $request->all();
        foreach($request as $key => $value)  {
            if ($key == 'additional_id') : $additional_array = $value;
            elseif ($key == 'treatment_id') : $treatment_array = $value;
            elseif ($key == 'cancer_type_id') : $int_cancer_type = $value;
            elseif ($key == 'cancer_stage_id') : $int_cancer_stage = $value;
        endif;
        }
        $patientdiagnosis_array = ['patient_id'=>$patient[0]['patient_id'],'cancer_type_id'=>$int_cancer_type,'stage_id'=>$int_cancer_stage];

        $PatientDiagnosis = PatientDiagnosis::create($patientdiagnosis_array);
        $PatientDiagnosisId = $PatientDiagnosis->diagnosis_id;

        $array[] = $PatientDiagnosis;

        foreach($additional_array as $add_id) {
            $new_array = ['diagnosis_id'=>$PatientDiagnosisId,'additional_id'=>$add_id];
            $LkupPatientDiagnosisAdditional = PatientDiagnosisAdditional::create($new_array);
            $array[] = $LkupPatientDiagnosisAdditional;
        }

        foreach($treatment_array as $treat_id) {
            $new_array = ['diagnosis_id'=>$PatientDiagnosisId,'treatment_id'=>$treat_id];
            $LkupPatientDiagnosisTreatment = PatientDiagnosisTreatment::create($new_array);
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
