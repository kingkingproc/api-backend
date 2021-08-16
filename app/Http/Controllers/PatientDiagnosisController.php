<?php

namespace App\Http\Controllers;

use App\Models\PatientDiagnosis;
use App\Models\PatientDiagnosisTreatment;
use App\Models\PatientDiagnosisAdditional;
use App\Models\PatientDiagnosisRemoteSite;

use Illuminate\Http\Request;

class PatientDiagnosisController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return patientdiagnosis::all();
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        return patientdiagnosis::create($request->all());
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $patientdiagnosis = patientdiagnosis::find($id);
        $json1 = json_encode(['patient_diagnosis'=> $patientdiagnosis]);
        $json2 = json_encode(['cancer_type'=>patientdiagnosis::find($id)->cancer_type]);
        $json3 = json_encode(['cell_type'=>patientdiagnosis::find($id)->cell_type]);
        $json4 = json_encode(['stage'=>patientdiagnosis::find($id)->stage]);
        $json5 = json_encode(['tumor_site'=>patientdiagnosis::find($id)->tumor_site]);
        $json6 = json_encode(['tumor_size'=>patientdiagnosis::find($id)->tumor_size]);
        $json7 = json_encode(['perfomance_score'=>patientdiagnosis::find($id)->performance_score]);
        
        $array[] = json_decode($json1, true);
        $array[] = json_decode($json2, true);
        $array[] = json_decode($json3, true);
        $array[] = json_decode($json4, true);
        $array[] = json_decode($json5, true);
        $array[] = json_decode($json6, true);
        $array[] = json_decode($json7, true);
        
        $treatment = patientdiagnosis::find($id)->treatment;
        $additional = patientdiagnosis::find($id)->additional;
        $remote_site = patientdiagnosis::find($id)->remote_site;



        foreach ($treatment as $treatment_s){
            //$array[] = $treatment_s;
            $json8 = "";
            $json8 = json_encode(['treatment'=>patientdiagnosistreatment::find($treatment_s->treatment_id)->treatments]);
            $array[] = json_decode($json8, true);
        }
 
        foreach ($additional as $additional_s){
            //$array[] = $treatment_s;
            $json8 = "";
            $json8 = json_encode(['additional'=>patientdiagnosisadditional::find($additional_s->additional_id)->additionals]);
            $array[] = json_decode($json8, true);
        }

        foreach ($remote_site as $remote_site_s){
            //$array[] = $treatment_s;
            $json8 = "";
            $json8 = json_encode(['remote_site'=>patientdiagnosisremotesite::find($remote_site_s->remote_site_id)->remote_sites]);
            $array[] = json_decode($json8, true);
        }
        return $array;
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
        $patientdiagnosis = patientdiagnosis::find($id);
        $patientdiagnosis->update($request->all());
        return $patientdiagnosis;
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
