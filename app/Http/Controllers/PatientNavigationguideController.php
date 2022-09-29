<?php

namespace App\Http\Controllers;

use App\Helper\Helper;

use Illuminate\Http\Request;
use App\Models\Patient;
use App\Models\PatientNavigationguide;

use Illuminate\Support\Facades\DB;

class PatientNavigationguideController extends Controller
{
    // get route 
    public function index()
    {
        $request = request();
        $the_object = Helper::verifyJasonToken($request);
        $patientRecord = patient::where('sub',$the_object->sub)->get();
        $navigationRecord = PatientNavigationguide::where('patient_id', $patientRecord[0]['patient_id'])->get();
        
        return $navigationRecord;
    }

    // put route
    public function update(Request $request)
    {
        //
        $request = request();
        $the_object = Helper::verifyJasonToken($request);
        $patientRecord = patient::where('sub',$the_object->sub)->get();
        $navigationRecord = PatientNavigationguide::where('patient_id', $patientRecord[0]['patient_id'])->get();
        

        
        if (empty($navigationRecord[0]['patient_id'])) {
            $request["patient_id"] = $patientRecord[0]['patient_id'];

            return PatientNavigationguide::create($request->all());
        } 
        else {

            //return $navigationRecord::update($request->all());   
            $affected = DB::table('patient_navigationguide')
                ->where('patient_id', $patientRecord[0]['patient_id'])
                ->update(array(
                    'oncologist' => $request["oncologist"],
                    'care_team' => $request["care_team"],
                    'biomarker_drugs' => $request["biomarker_drugs"],
                    'biomarker_test_results' => $request["biomarker_test_results"],
                    'treatment_options' => $request["treatment_options"],
                    'clinical_trials' => $request["clinical_trials"],
                    'cancer_stage' => $request["cancer_stage"],
                    'cancer_spread' => $request["cancer_spread"],
                    'cancer_subtype' => $request["cancer_subtype"],
                    'systemic_treatment' => $request["systemic_treatment"],
                    'received_treatment' => $request["received_treatment"],
                    'ecog_performance_status' => $request["ecog_performance_status"],
                    'hereditary_notes' => $request["hereditary_notes"],
                    'symptoms' => $request["symptoms"],
                    'check_pathology_report' => $request["check_pathology_report"],
                    'check_post_operation' => $request["check_post_operation"],
                    'check_blood_tests' => $request["check_blood_tests"],
                    'check_tissue_tests' => $request["check_tissue_tests"],
                    'check_imaging' => $request["check_imaging"],
                    'check_medications' => $request["check_medications"],
                    'check_symptoms' => $request["check_symptoms"],
                    'check_medical_records' => $request["check_medical_records"],
                    'data_collection_notes' => $request["data_collection_notes"],
                    'nccn_notes' => $request["nccn_notes"],
                    'nci_notes' => $request["nci_notes"],
                    'asco_notes' => $request["asco_notes"],
                    'pubmed_notes' => $request["pubmed_notes"],
                    'acs_notes' => $request["acs_notes"],
                    'org_notes' => $request["org_notes"],
                    'app_notes' => $request["app_notes"],
                    'case_manager' => $request["case_manager"],
                    'insurance_experts' => $request["insurance_experts"],
                    'advocacy_organizations_1' => $request["advocacy_organizations_1"],
                    'advocacy_organizations_2' => $request["advocacy_organizations_2"],
                    'cost_coverage' => $request["cost_coverage"]
                ));
            $navigationRecord = PatientNavigationguide::where('patient_id', $patientRecord[0]['patient_id'])->get();
            return $navigationRecord;    
       
        }
       

    }

}