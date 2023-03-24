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

class OncoC4SurveyCompleteController extends Controller
{

    public function update(Request $request)
    {
        $request = request();

            // Get the patient record for the sub
            //$patientRecord = patient::where('sub',$request['sub'])->get();
            //if sub not found, try email
            
            $patientRecord = patient::where('email',$request['email'])->get();
            if (!empty($request['sub'])) {
                if (count($patientRecord)) {
                    $patient_array['user_type']=1; 
                    $patient_array['sub']=(isset($request['sub'])?$request['sub']:"");
                    $patient_array['patient_id']=$patientRecord[0]['patient_id'];
                    $patient = patient::find($patientRecord[0]['patient_id']);
                    $patient->update($patient_array);

                    //return json_encode([array('status' => 'success')]);
                }
            }
            
            // put patient request data into array
            
            $patient_array['user_type']=(isset($request['user_type'])?$request['user_type']:10);
            $patient_array['name_first']=(isset($request['name_first'])?$request['name_first']:"");
            $patient_array['name_last']=(isset($request['name_last'])?$request['name_last']:"");
            $patient_array['sub']=(isset($request['sub'])?$request['sub']:"");
            $patient_array['email']=(isset($request['email'])?$request['email']:"");
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
            $address_array['address_zip']=(isset($request['zip_code'])?$request['zip_code']:"");
            //put diagnosis request data into array
            if ($request['bln_diagnosis']) {
                $diagnosis_array['cancer_type_id'] = "213";
                $diagnosis_array['is_metastatic'] = true;
                $diagnosis_array['stage_id'] = "4";
            } else {
                $diagnosis_array['cancer_type_id'] = "213";
                $diagnosis_array['is_metastatic'] = false; 
            }

            if ($request['bln_mutation_kras']) {
                $biomarker_array['biomarker_id'] = 47;
            } 

            if ($request['bln_pd1']) {
                $treatment_array['biomarker_id'] = 59;
            }
            if ($request['bln_marketing']) {
                $patient_array['shareInformation'] = false;
            } else {
                $patient_array['shareInformation'] = true;
            }
            
            // Check if there is a patient that exist for the sub
            if (count($patientRecord)) {
                //patient record exist, so update the patient
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

                //create the patient
                $patient = patient::create($patient_array);
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

        if (($request['bln_age'] == "true") && ($request['bln_diagnosis'] == "true") && ($request['bln_mutation_other'] != "true") && ($request['bln_pd1'] != "false") && ($request['bln_pd1_platinum'] != "false") && ($request['bln_pd1_progressed'] != "false") && ($request['bln_pd1_time'] != "false")) {
            $firstResults = DB::connection('pgsql2')->select(" 
            with cte_lat_long as (
                select latitude,longitude from us where zipcode = '" . $request['zip_code'] . "'
                )
                , cte_no_location as (
                select trials_nsclc_thin_full.trial_id, MIN(
                6371 * acos(cos(radians(cte_lat_long.latitude))
                        * cos(radians(us.latitude)) 
                        * cos(radians(us.longitude) - radians(cte_lat_long.longitude)) 
                        + sin(radians(cte_lat_long.latitude)) 
                        * sin(radians(us.latitude)))) AS distance
                from cte_lat_long,trials_nsclc_thin_full inner join us on trials_nsclc_thin_full.postal_code = us.zipcode
                group by trials_nsclc_thin_full.trial_id
                ),
                cte_location as (
                select trials_nsclc_thin_full.trial_id, trials_nsclc_thin_full.location_id,
                6371 * acos(cos(radians(cte_lat_long.latitude))
                        * cos(radians(us.latitude)) 
                        * cos(radians(us.longitude) - radians(cte_lat_long.longitude)) 
                        + sin(radians(cte_lat_long.latitude)) 
                        * sin(radians(us.latitude))) AS distance
                from cte_lat_long,trials_nsclc_thin_full inner join us on trials_nsclc_thin_full.postal_code = us.zipcode
                ),
                cte_distinct_location as (
                select cte_no_location.trial_id, cte_no_location.distance, min(cte_location.location_id) as location_id
                from cte_no_location inner join cte_location on cte_no_location.trial_id = cte_location.trial_id
                    and cte_no_location.distance = cte_location.distance
                group by cte_no_location.trial_id, cte_no_location.distance
                )
                select cte_distinct_location.trial_id, cte_distinct_location.distance, cte_distinct_location.location_id, 
                trials_nsclc_thin_full.*, us.latitude, us.longitude, 0 as favorite
                from cte_distinct_location
                inner join trials_nsclc_thin_full on cte_distinct_location.trial_id = trials_nsclc_thin_full.trial_id
                and cte_distinct_location.location_id = trials_nsclc_thin_full.location_id
                inner join us on trials_nsclc_thin_full.postal_code = us.zipcode
                where trials_nsclc_thin_full.trial_id = '-934321243'
                order by cte_distinct_location.distance limit 1"
                );
    
            //return $firstResults;

            $testResults = DB::connection('pgsql2')->select(" 
            select * from trials_additional_data
                where trials_additional_data.trial_id = ".$firstResults[0]->trial_id."
                and trials_additional_data.location_id = ".$firstResults[0]->location_id."
                "
            );


            $trialList = [];    
            foreach($testResults as $record) {
                if (in_array($record->trial_id, $trialList)) {
                    continue;
                }
                array_push($trialList,$record->trial_id); 
    
    
                $record->professional_data = json_decode($record->professional_data);
                $record->contact_data = json_decode($record->contact_data);
                $record->primary_purpose = ucwords($record->primary_purpose);
    
                $locationResults = DB::connection('pgsql2')->select(" 
                    select location.* from
                    location inner join trials_additional_locations on location.location_id = trials_additional_locations.additional_location_id
                    where trials_additional_locations.base_trial_id = '". $record->trial_id ."'
                    and trials_additional_locations.base_location_id = '". $record->location_id ."'
                    ");
    
                if ($record->phase != null) {
                    $record->phase = preg_replace("/[^0-9,]/", "", $record->phase );
                    $record->phase = "[" . $record->phase . "]";
                } else {
                    $record->phase = "[0]";
                }
    
    
    
                //$record->additional_location_data = json_decode($record->additional_location_data, true);
                $record->additional_location_data = json_decode(json_encode($locationResults), true);
                //array_multisort(array_column($record->additional_location_data, 'distance'), SORT_ASC, $record->additional_location_data);
                //$record->additional_location_data = array_slice($record->additional_location_data, 0, 5);
                
    
    
    
    
                $array[] = $record;
            }
            return $array;
        } elseif (($request['bln_age'] == "false") || ($request['bln_diagnosis'] == "false") || ($request['bln_mutation_other'] == "true") || ($request['bln_pd1'] == "false")) {
            $array = array('status' => 'success');
            return $array;
        } else {
            $array = array('status' => 'maybe');
            return $array;
        }
    }

}
