<?php

namespace App\Http\Controllers;

use Firebase\JWT\JWK;
use Firebase\JWT\JWT;

use Illuminate\Http\Request;
use App\Models\Patient;
use App\Models\address;
use App\Models\PatientContact;
use App\Models\PatientContactData;
use App\Models\LkupContactDataType;
use App\Models\PatientDiagnosis;
use App\Models\PatientDiagnosisTreatment;
use App\Models\PatientDiagnosisBiomarker;
use App\Models\PatientDiagnosisAdditional;
use App\Models\PatientDiagnosisRemoteSite;

use Illuminate\Support\Facades\DB;
use ActiveCampaign;

class OncoC4SurveyCompleteController extends Controller
{

    public function update(Request $request)
    {
        $request = request();
<<<<<<< HEAD

            // Get the patient record for the sub
            //$patientRecord = patient::where('sub',$request['sub'])->get();
            //if sub not found, try email
            
            $patientRecord = patient::where('email',$request['email'])->get();
            if (!empty($request['sub'])) {
                if (count($patientRecord)) {
                    $patient_array['user_type']=10; 
                    $patient_array['sub']=$request['sub'];
                    $patient_array['patient_id']=$patientRecord[0]['patient_id'];
                    $patient = patient::find($patientRecord[0]['patient_id']);
                    $patient->update($patient_array);

                    //return json_encode([array('status' => 'success')]);
                }
            }
=======
        if (!empty($request['sub'])) {
            //sub is passed in, so should be new user
            $patient_array['email']=$request['email'];
            $patient_array['sub']=$request['sub'];
        } else {
            //no sub, so it should be existing email
            $patient_array['email']=$request['email'];
        }
>>>>>>> 288f5c808dcd37af5b33c554afe6e875962c746a
            
            // put patient request data into array
            
            
            $patient_array['user_type']=(isset($request['user_type'])?$request['user_type']:10);
            $patient_array['name_first']=(isset($request['name_first'])?$request['name_first']:"");
            $patient_array['name_last']=(isset($request['name_last'])?$request['name_last']:"");
<<<<<<< HEAD
            $patient_array['name_middle']=(isset($request['name_middle'])?$request['name_middle']:"");
            $patient_array['sex']=(isset($request['sex'])?$request['sex']:"");
            $patient_array['ethnicity_id']=(isset($request['ethnicity'])?$request['ethnicity']:0);
            $patient_array['education_level']=(isset($request['education_level'])?$request['education_level']:0);
            $patient_array['is_medicaid_patient']=(isset($request['is_medicaid_patient'])?$request['is_medicaid_patient']:0);
            $patient_array['is_complete'] = (isset($request['is_complete'])?$request['is_complete']:0);
            $patient_array['termsAgreement'] = (isset($request['termsAgreement'])?$request['termsAgreement']:0);
            $patient_array['shareInformation'] = (isset($request['shareInformation'])?$request['shareInformation']:0);
            $patient_array['sendInformation'] = (isset($request['sendInformation'])?$request['sendInformation']:0);
            //put address request data into array
            $address_array['address_city']=(isset($request['city'])?$request['city']:"");
            $address_array['address_state']=(isset($request['state'])?$request['state']:"");
=======
>>>>>>> 288f5c808dcd37af5b33c554afe6e875962c746a
            $address_array['address_zip']=(isset($request['zip_code'])?$request['zip_code']:"");
            //put diagnosis request data into array
            if ($request['bln_diagnosis'] == "true") {
                $diagnosis_array['cancer_type_id'] = "213";
                $diagnosis_array['is_metastatic'] = true;
                $diagnosis_array['stage_id'] = "4";
            } else {
                $diagnosis_array['cancer_type_id'] = "213";
                $diagnosis_array['is_metastatic'] = false; 
            }
            if ($request['bln_mutation'] == "false") {
                $diagnosis_array['is_biomarker_started'] = false; 
            } else {
                $diagnosis_array['is_biomarker_started'] = true;
            }

            if ($request['bln_mutation_kras'] == "true") {
                $biomarker_array['biomarker_id'] = 47;
            } 

            if ($request['bln_pd1'] == "true") {
                $diagnosis_array['is_treatment_started'] = true;
            }
            //if ($request['bln_pd1'] == "true") {
            //    $treatment_array['biomarker_id'] = 59;
            //}
            //if ($request['bln_marketing']) {
            //    $patient_array['shareInformation'] = false;
            //} else {
            //    $patient_array['shareInformation'] = true;
            //}
            
            // Check if there is a sub
            if (empty($request['sub'])) {
                //patient record for the
                $patientRecord = patient::where('email',$request['email'])->get();
                
                //first check if there is an address for the patient
                if (empty($patientRecord[0]['address_id'])) {
                    //address id is empty, so create the address
                    $address = address::create($address_array);
                } else {
                    //address id is not empty, so update that address
                    $address = address::find($patientRecord[0]['address_id']);
                    $address->update($address_array);
                }
                //place the new or updated address_id into the patient array
                $patient_array['address_id']=$address['address_id'];
                $patient_array['patient_id']=$patientRecord[0]['patient_id'];
                $patient = patient::find($patientRecord[0]['patient_id']);
                $patient->update($patient_array);
            } else {
                // patient record does not exist, so create patient
                //first create the address
                $address = address::create($address_array);

                //place the new address_id into the patient array
                $patient_array['address_id']=$address['address_id'];
                $patient_array['sub']=$request['sub'];
                $patient_array['email']=$request['email'];

                //create the patient
                $patient = patient::create($patient_array);
                $patientRecord = patient::where('email',$request['email'])->get();
            }
        
            //after creating or updating the patient record, sent the contact to ActiveCampaign
            $activeCampaign = app(ActiveCampaign::class);

            $data = [
                'email' => $patient_array['email'],
                'first_name' => $patient_array['name_first'],
                'last_name' => $patient_array['name_last'],
                'p[4]' => '4',
            ];
            
            $results = $activeCampaign->api('contact/add', $data);
            if (!(int)$results->success) {
                // means active campaign failed, not sure what to do with it.
            }

            //put diagnosis request data into array
            $diagnosis_array['patient_id']=$patient['patient_id'];

            // get the diagnosis record for the patient
            $diagnosisRecord = PatientDiagnosis::where('patient_id',$patient['patient_id'])->get();
           
            // Check if there is a dianosis  tha exist for the patient
            if (count($diagnosisRecord)) {
                // diagnosis exists, so update
                $diagnosis = PatientDiagnosis::find($diagnosisRecord[0]['diagnosis_id']);
                $diagnosis->update($diagnosis_array);
            } else{
                // diagnosis does not exist so create
                $diagnosis = PatientDiagnosis::create($diagnosis_array);
            }

            $diagnosis_id = $diagnosis['diagnosis_id'];

            $biomarkers_to_remove = PatientDiagnosisBiomarker::where('diagnosis_id',$diagnosis_id)->get();
            foreach ($biomarkers_to_remove as $to_remove) {
                PatientDiagnosisBiomarker::destroy($to_remove['id']);
            }

            if (!empty($biomarker_array['biomarker_id'])) {
                $biomarker_array['diagnosis_id']=$diagnosis_id;
                PatientDiagnosisBiomarker::create($biomarker_array);
            }

            if (!empty($treatment_array['biomarker_id'])) {
                $treatment_array['diagnosis_id']=$diagnosis_id;
                PatientDiagnosisBiomarker::create($treatment_array);
            }

            $var_prescreen_id = 3;

            $testResults = DB::connection('pgsql')->select(" 
            delete from  prescreen_response
            where prescreen_id = '" . $var_prescreen_id . "'
            and patient_id = '" . $patientRecord[0]['patient_id'] . "'
            ");
 
            $testResults = DB::connection('pgsql')->select(" 
            delete from  prescreen_patient_ref
            where prescreen_id = '" . $var_prescreen_id . "'
            and patient_id = '" . $patientRecord[0]['patient_id'] . "'
            ");

            $insertData = [
                ['patient_id'=>$patientRecord[0]['patient_id'], 'prescreen_id'=>$var_prescreen_id, 'question_variable'=>'bln_age', 'patient_response'=>$request['bln_age']],
                ['patient_id'=>$patientRecord[0]['patient_id'], 'prescreen_id'=>$var_prescreen_id, 'question_variable'=>'bln_diagnosis', 'patient_response'=>$request['bln_diagnosis']],
                ['patient_id'=>$patientRecord[0]['patient_id'], 'prescreen_id'=>$var_prescreen_id, 'question_variable'=>'bln_mutation', 'patient_response'=>$request['bln_mutation']],
                ['patient_id'=>$patientRecord[0]['patient_id'], 'prescreen_id'=>$var_prescreen_id, 'question_variable'=>'bln_mutation_kras', 'patient_response'=>$request['bln_mutation_kras']],
                ['patient_id'=>$patientRecord[0]['patient_id'], 'prescreen_id'=>$var_prescreen_id, 'question_variable'=>'bln_mutation_other', 'patient_response'=>$request['bln_mutation_other']],
                ['patient_id'=>$patientRecord[0]['patient_id'], 'prescreen_id'=>$var_prescreen_id, 'question_variable'=>'bln_pd1', 'patient_response'=>$request['bln_pd1']],
                ['patient_id'=>$patientRecord[0]['patient_id'], 'prescreen_id'=>$var_prescreen_id, 'question_variable'=>'bln_pd1_platinum', 'patient_response'=>$request['bln_pd1_platinum']],
                ['patient_id'=>$patientRecord[0]['patient_id'], 'prescreen_id'=>$var_prescreen_id, 'question_variable'=>'bln_pd1_progressed', 'patient_response'=>$request['bln_pd1_progressed']],
                ['patient_id'=>$patientRecord[0]['patient_id'], 'prescreen_id'=>$var_prescreen_id, 'question_variable'=>'bln_pd1_time', 'patient_response'=>$request['bln_pd1_time']],
            ];
            
            DB::table('prescreen_response')->insert($insertData);

        if (($request['bln_age'] == "true") && ($request['bln_diagnosis'] == "true") && ($request['bln_mutation_other'] == "false") && ($request['bln_pd1'] == "true") && ($request['bln_pd1_platinum'] == "true") && ($request['bln_pd1_progressed']  == "true") && ($request['bln_pd1_time']  == "true")) {

            $insertData = [
                ['patient_id'=>$patientRecord[0]['patient_id'], 'prescreen_id'=>$var_prescreen_id, 'patient_eligible'=>'user_eligible'],
            ];
            DB::table('prescreen_patient_ref')->insert($insertData);

            $array = array('status' => 'eligible');
            return $array;
        } elseif (($request['bln_age'] == "false") || ($request['bln_diagnosis'] == "false") || ($request['bln_mutation_other'] == "true") || ($request['bln_pd1'] == "false" || ($request['bln_pd1_platinum'] == "false"))) {

            $insertData = [
                ['patient_id'=>$patientRecord[0]['patient_id'], 'prescreen_id'=>$var_prescreen_id, 'patient_eligible'=>'user_ineligible'],
            ];
            DB::table('prescreen_patient_ref')->insert($insertData);

            $array = array('status' => 'ineligible');
            return $array;
        } else {

            $insertData = [
                ['patient_id'=>$patientRecord[0]['patient_id'], 'prescreen_id'=>$var_prescreen_id, 'patient_eligible'=>'user_maybe'],
            ];
            DB::table('prescreen_patient_ref')->insert($insertData);

            $array = array('status' => 'maybe');
            return $array;
        }
    }


}
