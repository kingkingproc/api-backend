<?php

namespace App\Http\Controllers;

use App\Helper\Helper;

use Illuminate\Http\Request;
use App\Models\Patient;
use App\Models\PatientDiagnosis;
use App\Models\LkupPatientDiagnosisCancerType;
use App\Models\LkupPatientDiagnosisCancerSubType;
use App\Models\address;


use Illuminate\Support\Facades\DB;

function strpos_arr($haystack, $needle) {
    if(!is_array($needle)) $needle = array($needle);
    foreach($needle as $what) {
        if(($pos = strpos($haystack, $what))!==false) return $pos;
    }
    return false;
}

class NewTrialController extends Controller
{
    public function index()
    {

        $request = request();
        $the_object = Helper::verifyJasonToken($request);
        $patientRecord = patient::where('sub',$the_object->sub)->get();
        $diagnosisRecord = patientdiagnosis::where('patient_id', $patientRecord[0]['patient_id'])->get();
        $cancerTypeRecord = lkuppatientdiagnosiscancertype::where('cancer_type_id',$diagnosisRecord[0]['cancer_type_id'])->get();
        if ($cancerTypeRecord[0]['cancer_type_label'] == "Melanoma") {
            $string_tableName = "melanoma";
        } else {
            $string_tableName = "nsclc";
        }       
        
        
        //$prescreenTrialList = Helper::getPrescreenTrialList();
        $prescreenTrialList = Helper::patientPrescreenStatus($patientRecord[0]['patient_id']);
        //return $prescreenTrialList;

        $searchTerm = $cancerTypeRecord[0]['cancer_type_label'];

        if (!is_null($diagnosisRecord[0]['cancer_sub_type_id'])) {
            $cancerSubTypeRecord = lkuppatientdiagnosiscancersubtype::where('cancer_sub_type_id',$diagnosisRecord[0]['cancer_sub_type_id'])->get();
            $searchSubTerm = $cancerSubTypeRecord[0]['cancer_sub_type_label'];
        } else {
            $searchSubTerm = ""; 
        }
        //Patient record for DOB to figure out age req.
        if (!is_null($patientRecord[0]["dob_day"])) {
            $patientRecord[0]["DOB"] = $patientRecord[0]["dob_day"] . "-" . $patientRecord[0]["dob_month"] . "-" . $patientRecord[0]["dob_year"];
            $today = date("Y-m-d");
            $diff = date_diff(date_create($patientRecord[0]["DOB"]), date_create($today));
            $patientRecord[0]["AGE"] = $diff->format('%y');
        } else {
            $patientRecord[0]["AGE"] = "50";
        }

        $array_search_sub_disease = array('Holder Value');
        $array_search_sub_not_disease = array('Holder Value');

        if ($searchSubTerm == "Acral Lentiginous Melanoma (ALM)") {
            $array_search_sub_disease = array('Acral Lentiginous Melanoma', 'ALM', 'Acral Melanoma');
            $array_search_sub_not_disease = array('Excluding Acral Lentiginous Melanoma');
        }
        if ($searchSubTerm == "Cutaneous Melanoma") {
            $array_search_sub_disease = array('Cutaneous Melanoma', 'Melanoma of the Skin', 'Skin Melanoma', 'Skin Cancer');
            $array_search_sub_not_disease = array('Excluding Cutaneous Melanoma');
        }
        if ($searchSubTerm == "Mucosal Melanoma") {
            $array_search_sub_disease = array('Mucosal Melanoma');
            $array_search_sub_not_disease = array('Mucosal Melanoma');
        }
        if ($searchSubTerm == "Ocular Melanoma") {
            $array_search_sub_disease = array('Ocular Melanoma', 'Eye Melanoma', 'Uveal Melanoma', 'Intraocular Melanoma', 'Choroidal Melanoma', 'Iris Melanoma');
            $array_search_sub_not_disease = array('Excluding Uveal Melanoma', 'Excluding Ocular Melanoma');
        }
        if ($searchSubTerm == "Pediatric Melanoma") {
            $array_search_sub_disease = array('Pediatric Melanoma');
            $array_search_sub_not_disease = array('Excluding Pediatric Melanoma');
        }
        

        $searchPhase = $diagnosisRecord[0]->performance_score_id;
        $searchEcog = $diagnosisRecord[0]->performance_score_id;
        $searchStage = $diagnosisRecord[0]->stage_id;

        $addressRecord = address::find($patientRecord[0]['address_id']);

        $array_disease_contain = array('hematologic','lymphoid','lymphocytic','lymphoproliferative','hematological','lymphoma','hematopoietic','B-cell','B cell','NHL','MZBCL','MCL','MZL','DLBCL','LBCL','CLL','SLL','Leukemia','PTCL','CBCL','ALCL','PCBCL','ATLL');
        $array_disease_no_contain = array('non-hematologic','nonhematologic','non hematologic');

        $testResults = DB::connection('pgsql2')->select(" 
                        with cte_lat_long as (
                            select latitude,longitude from us where zipcode = '" . $addressRecord['address_zip'] . "'
                            )
                            , cte_no_location as (
                            select trials_" . $string_tableName . "_full.trial_id, MIN(
                            6371 * acos(cos(radians(cte_lat_long.latitude))
                                    * cos(radians(us.latitude)) 
                                    * cos(radians(us.longitude) - radians(cte_lat_long.longitude)) 
                                    + sin(radians(cte_lat_long.latitude)) 
                                    * sin(radians(us.latitude)))) AS distance
                            from cte_lat_long,trials_" . $string_tableName . "_full inner join us on trials_" . $string_tableName . "_full.postal_code = us.zipcode
                            group by trials_" . $string_tableName . "_full.trial_id
                            ),
                            cte_location as (
                            select trials_" . $string_tableName . "_full.trial_id, trials_" . $string_tableName . "_full.location_id,
                            6371 * acos(cos(radians(cte_lat_long.latitude))
                                    * cos(radians(us.latitude)) 
                                    * cos(radians(us.longitude) - radians(cte_lat_long.longitude)) 
                                    + sin(radians(cte_lat_long.latitude)) 
                                    * sin(radians(us.latitude))) AS distance
                            from cte_lat_long,trials_" . $string_tableName . "_full inner join us on trials_" . $string_tableName . "_full.postal_code = us.zipcode
                            ),
                            cte_distinct_location as (
                            select cte_no_location.trial_id, cte_no_location.distance, min(cte_location.location_id) as location_id
                            from cte_no_location inner join cte_location on cte_no_location.trial_id = cte_location.trial_id
                                and cte_no_location.distance = cte_location.distance
                            group by cte_no_location.trial_id, cte_no_location.distance
                            )
                            select cte_distinct_location.trial_id, cte_distinct_location.distance, cte_distinct_location.location_id, 
                            trials_" . $string_tableName . "_full.*, us.latitude, us.longitude, 0 as favorite
                            from cte_distinct_location
                            inner join trials_" . $string_tableName . "_full on cte_distinct_location.trial_id = trials_" . $string_tableName . "_full.trial_id
                            and cte_distinct_location.location_id = trials_" . $string_tableName . "_full.location_id
                            inner join us on trials_" . $string_tableName . "_full.postal_code = us.zipcode
                            order by cte_distinct_location.distance"
                );
        //return $testResults;
        $favoriteResults = DB::connection('pgsql')->select("
                select type_id from patient_favorites where type='trial'
                and patient_id = " . $patientRecord[0]['patient_id'] . " and
                sub = '" . $patientRecord[0]['sub'] . "'");

        $trialList = [];

        //$testResults = $testResults->sortBy('trial_id');

        foreach($testResults as $record) {
            if (in_array($record->trial_id, $trialList)) {
                continue;
            }
            array_push($trialList,$record->trial_id); 

            foreach($favoriteResults as $favorite) {
                if ($favorite->type_id == $record->trial_id) {
                    $record->favorite = 1;
                }
            }

            if ($patientRecord[0]["AGE"] > $record->eligibility_maximum_age) {
                continue;
            }
            if ($patientRecord[0]["AGE"] < $record->eligibility_minimum_age) {
                continue;
            }
            //$record->disease_count = [];
            $record->professional_data = json_decode($record->professional_data);
            $record->collaborator_data = json_decode($record->collaborator_data);
            $record->contact_data = json_decode($record->contact_data);

            $record->eligibility_biomarker = json_decode($record->eligibility_biomarker);
            $record->eligibility_comorbidities = json_decode($record->eligibility_comorbidities);
            //$record->phase = json_decode($record->phase);
            $record->primary_purpose = ucwords($record->primary_purpose);

            $record->search_result_score = 0.0;
            $record->search_result_string = "Matching-";
            
            $myArr = ["open", "active", "available", "recruiting", "enrolling by invitation"];

            //disease in title
            if (stripos(" " . $record->trial_title, $searchTerm)) {
                $record->search_result_score = $record->search_result_score+1.0;
                $record->search_result_string = $record->search_result_string . "-Title";
            }
            $record->search_result_score = $record->search_result_score+1.0;
            $record->search_result_string = $record->search_result_string . "-Title";
            //disease in list
            if (stripos($record->disease_arr, $searchTerm)) {
                $record->search_result_score = $record->search_result_score+1.0;
                $record->search_result_string = $record->search_result_string . "-List";
            }
            $record->search_result_score = $record->search_result_score+1.0;
            $record->search_result_string = $record->search_result_string . "-List";


            //cancer sub type in title or list
            if (strpos_arr(" " . $record->trial_title, $array_search_sub_disease) || strpos_arr($record->disease_arr, $array_search_sub_disease)) {
                $record->search_result_score = $record->search_result_score+2;
                $record->search_result_string = $record->search_result_string . "-Subtype";
            } 

            if (strpos_arr(" " . $record->trial_title, $array_search_sub_not_disease) || strpos_arr($record->disease_arr, $array_search_sub_not_disease)) {
                $record->search_result_score = $record->search_result_score-3;
                $record->search_result_string = $record->search_result_string . "-ExcluseSubtype";
            }

            if ($record->phase != null) {
                $record->phase = preg_replace("/[^0-9,]/", "", $record->phase );
                $record->phase = "[" . $record->phase . "]";
            } else {
                $record->phase = "[0]";
            }

            //stage matching
            if (!is_null($record->stage)) {
                try {
                    if (str_contains($record->stage, $searchStage)) {
                        $record->search_result_score = $record->search_result_score+1.0;
                        $record->search_result_string = $record->search_result_string . "-Stage";
                    }
                } catch (\Exception $e) {
                    
                }
            }
                

            //ecog matching
            if (!is_null($record->ecog)) {
                try {
                    if (str_contains($record->ecog, $searchEcog)) {
                        $record->search_result_score = $record->search_result_score+1.0;
                        $record->search_result_string = $record->search_result_string . "-Ecog";
                    }
                } catch (\Exception $e) {
                    
                }
            }
            
            //disease count
            if ($record->disease_count > 5) {
                $record->search_result_score = $record->search_result_score-1.0;
                $record->search_result_string = $record->search_result_string . "-Count5";
            }
  
            //new rule for matching list of terms
            if (strpos_arr($record->trial_title, $array_disease_contain) || strpos_arr($record->disease_arr, $array_disease_contain)) {
                //$record->search_result_score = $record->search_result_score+5.0;
                //$record->search_result_string = $record->search_result_string . "-FIRSTSTEPNEWRULE";

                if(!strpos_arr($record->trial_title, $array_disease_no_contain) && !strpos_arr($record->disease_arr, $array_disease_no_contain)) {
                    //$record->search_result_score = $record->search_result_score+5.0;
                    //$record->search_result_string = $record->search_result_string . "-SECONDSTEPNEWRULE";

                    if (stripos($record->trial_title, 'solid tumor') || stripos($record->trial_title, 'solid-tumor')) {
                        $record->search_result_score = $record->search_result_score-1.0;
                        $record->search_result_string = $record->search_result_string . "-NEWRULE";
                    }  
                }
            }

            $record->additional_location_data = json_decode($record->additional_location_data, true);
            
            array_multisort(array_column($record->additional_location_data, 'distance'), SORT_ASC, $record->additional_location_data);
            $record->additional_location_data = array_slice($record->additional_location_data, 0, 3);
            
           $record->search_result_score = $record->search_result_score; //2;
            if ($record->search_result_score > 5) {
                $record->search_result_score = 5;
            }
            if ($record->search_result_score < 0) {
                $record->search_result_score = 0;
            }
            if ($record->current_trial_status_date > $patientRecord[0]["view_at"]) {
                $record->bln_new = true;
            } else {
                $record->bln_new = false;
            }
            $record->bln_badge_location = false;
            $record->bln_badge_travel = false;
            $record->bln_badge_lodging = false;
            $record->bln_sponsored = false;
            $record->prescreen_flag = null;

            foreach($prescreenTrialList as $prescreen) {
                
                if ($record->trial_id == $prescreen->trial_id) {
                    $record->bln_sponsored = true;
                    $record->bln_badge_travel = true;
                    $record->prescreen_flag = $prescreen->patient_eligible;

                } 
            }


            $array[] = $record;
        }
        patient::where('sub',$the_object->sub)->update(['view_at'=>date('Y-m-d H:i:s')]);


        array_multisort(array_column($array, 'bln_sponsored'), SORT_DESC,
                array_column($array, 'distance'),      SORT_ASC,
                $array);
        return array_slice($array, 0, 500);
    }
}
