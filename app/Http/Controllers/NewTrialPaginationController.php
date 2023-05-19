<?php

namespace App\Http\Controllers;

use App\Helper\Helper;

use Illuminate\Http\Request;
use App\Models\Patient;
use App\Models\PatientDiagnosis;
use App\Models\LkupPatientDiagnosisCancerType;
use App\Models\LkupPatientDiagnosisCancerSubType;
use App\Models\address;
use App\Models\PatientDiagnosisBiomarker;


use Illuminate\Support\Facades\DB;

function strpos_arr($haystack, $needle) {
    if(!is_array($needle)) $needle = array($needle);
    foreach($needle as $what) {
        if(($pos = strpos($haystack, $what))!==false) return $pos;
    }
    return false;
}

class NewTrialPaginationController extends Controller
{
    public function index()
    {

         
        
        $brainMetsResults = DB::connection('pgsql2')->select("
        select distinct trial_id from eligibility_comorbidities where location = 'brain' and comorbidity_type = 'metastasis' and inclusion_indicator is not null
        and inclusion_indicator <> is_present
        and trial_id in (select trial_id from trials_melanoma_full)
        ");


            $formerMonth = date("F", strtotime("-1 months"));
            $formerYear = date("Y", strtotime("-1 months"));
            $compareDate = date('m/d/Y', strtotime("first Tuesday of $formerMonth $formerYear"));
            $new_flag_date = $compareDate;

        

        $request = request();
        $the_object = Helper::verifyJasonToken($request);
        $patientRecord = patient::where('sub',$the_object->sub)->where('email', $the_object->email)->get();
        $diagnosisRecord = patientdiagnosis::where('patient_id', $patientRecord[0]['patient_id'])->get();
        $biomarkerRecord =  DB::connection('pgsql')->select("
        select lb.biomarker_label from lkup_patient_diagnosis_biomarkers lb
        inner join patient_diagnosis_biomarkers b on lb.biomarker_id = b.biomarker_id
        and b.diagnosis_id = " . $diagnosisRecord[0]['diagnosis_id'] . "
        ");
        $cancerTypeRecord = lkuppatientdiagnosiscancertype::where('cancer_type_id',$diagnosisRecord[0]['cancer_type_id'])->get();
        if ($cancerTypeRecord[0]['cancer_type_label'] == "Melanoma") {
            $string_tableName = "melanoma_thin";
            $string_cancerType = 'melanoma';
        } else {
            $string_tableName = "nsclc_thin";
            $string_cancerType = 'nsclc';
        }       
        //return $biomarkerRecord;
        $brainMetsResults = "";
        //return $diagnosisRecord;
        if ($diagnosisRecord[0]['is_brain_tumor'] && $diagnosisRecord[0]['is_metastatic']) {
            $brainMetsResults = DB::connection('pgsql2')->select("
            select distinct trial_id from eligibility_comorbidities where location = 'brain' and comorbidity_type = 'metastasis' and inclusion_indicator is not null
            and inclusion_indicator = is_present
            and trial_id in (select trial_id from trials_" . $string_tableName . "_full)
            ");
        }
        if (!$diagnosisRecord[0]['is_brain_tumor'] && !$diagnosisRecord[0]['is_metastatic']) {
            $brainMetsResults = DB::connection('pgsql2')->select("
            select distinct trial_id from eligibility_comorbidities where location = 'brain' and comorbidity_type = 'metastasis' and inclusion_indicator is not null
            and inclusion_indicator <> is_present
            and trial_id in (select trial_id from trials_" . $string_tableName . "_full)
            ");
        }
        

        
        //$prescreenTrialList = Helper::getPrescreenTrialList();
        $prescreenTrialList = Helper::patientPrescreenStatus($patientRecord[0]['patient_id'],$string_cancerType);
        //return $prescreenTrialList;

        $searchTerm = $cancerTypeRecord[0]['cancer_type_label'];

        if (!is_null($diagnosisRecord[0]['cancer_sub_type_id'])) {
            $cancerSubTypeRecord = lkuppatientdiagnosiscancersubtype::where('cancer_sub_type_id',$diagnosisRecord[0]['cancer_sub_type_id'])->get();
            $searchSubTerm = $cancerSubTypeRecord[0]['cancer_sub_type_label'];
            $array_search_sub_disease = array($cancerSubTypeRecord[0]['cancer_sub_type_synonyms']);
            $array_search_sub_not_disease = array($cancerSubTypeRecord[0]['cancer_sub_type_antonyms']);
        } else {
            $searchSubTerm = "";
            $array_search_sub_disease = array('Holder Value');
            $array_search_sub_not_disease = array('Holder Value'); 
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

        
        $searchPhase = $diagnosisRecord[0]->performance_score_id;
        $searchEcog = $diagnosisRecord[0]->performance_score_id;
        $searchStage = $diagnosisRecord[0]->stage_id;

        $addressRecord = address::find($patientRecord[0]['address_id']);

        $array_disease_non_hematologic = array('non-hematologic','non hematologic','nonhematologic'); 
        $array_disease_contain = array('hematologic','lymphoid','lymphocytic','lymphoproliferative','hematological','lymphoma','hematopoietic','B-cell','B cell','NHL','MZBCL','MCL','MZL','DLBCL','LBCL','CLL','SLL','Leukemia','PTCL','CBCL','ALCL','PCBCL','ATLL');
        $array_disease_no_contain = array('hematologic','lymphoid','lymphocytic','lymphoproliferative','hematological');


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

        $favoriteResults = DB::connection('pgsql')->select("
                select type_id from patient_favorites where type='trial'
                and patient_id = " . $patientRecord[0]['patient_id'] . " and
                sub = '" . $patientRecord[0]['sub'] . "'");

        $trialList = [];


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
            
            $record->eligibility_comorbidities = json_decode($record->eligibility_comorbidities);
            $record->primary_purpose = ucwords($record->primary_purpose);
            $record->location_postal_code = $record->postal_code;

            $record->search_result_score = 0.0;
            $record->search_result_string = "Matching-";
            

            if (stripos(" " . $record->trial_title, $searchTerm) && stripos($record->disease_arr, $searchTerm) && $record->disease_count <= 5) {
                $record->search_result_score = $record->search_result_score+10;
                $record->search_result_string = $record->search_result_string . "-Cat1";
            }
            elseif ((stripos(" " . $record->trial_title, $searchTerm) || stripos($record->disease_arr, $searchTerm)) && $record->disease_count <= 5) {
                $record->search_result_score = $record->search_result_score+9;
                $record->search_result_string = $record->search_result_string . "-Cat2";                
            }
            elseif ((stripos(" " . $record->trial_title, $searchTerm) || stripos($record->disease_arr, $searchTerm)) && $record->disease_count > 5 && $record->disease_count <= 500) {
                $record->search_result_score = $record->search_result_score+8;
                $record->search_result_string = $record->search_result_string . "-Cat3";                
            }
            elseif ((stripos(" " . $record->trial_title, $searchTerm) || stripos($record->disease_arr, $searchTerm)) && $record->disease_count > 500) {
                $record->search_result_score = $record->search_result_score+6;
                $record->search_result_string = $record->search_result_string . "-Cat4";                
            }
            elseif ((strpos_arr(" " . $record->trial_title, $array_disease_non_hematologic) || strpos_arr($record->disease_arr, $array_disease_non_hematologic)) && !strpos_arr(" " . $record->trial_title, $array_disease_contain) && !strpos_arr($record->disease_arr, $array_disease_contain)) {
                $record->search_result_score = $record->search_result_score+6;
                $record->search_result_string = $record->search_result_string . "-Cat5";
                return $record;
            }
            elseif ((strpos_arr(" " . $record->trial_title, $array_disease_non_hematologic) || strpos_arr($record->disease_arr, $array_disease_non_hematologic)) && !strpos_arr(" " . $record->trial_title, $array_disease_contain) && !strpos_arr($record->disease_arr, $array_disease_contain)) {
                $record->search_result_score = $record->search_result_score+6;
                $record->search_result_string = $record->search_result_string . "-Cat5";
            }
            elseif (!strpos_arr(" " . $record->trial_title, $array_disease_non_hematologic) && !strpos_arr($record->disease_arr, $array_disease_non_hematologic) && !strpos_arr(" " . $record->trial_title, $array_disease_no_contain) && !strpos_arr($record->disease_arr, $array_disease_no_contain)) {
                $record->search_result_score = $record->search_result_score+6;
                $record->search_result_string = $record->search_result_string . "-Cat6";
            }
            else {
                $record->search_result_score = $record->search_result_score+3;
                $record->search_result_string = $record->search_result_string . "-Cat7";                
            }

            //cancer sub type in title or list
            if (strpos_arr(" " . $record->trial_title, $array_search_sub_disease) || strpos_arr($record->disease_arr, $array_search_sub_disease)) {
                $record->search_result_score = $record->search_result_score+25;
                $record->search_result_string = $record->search_result_string . "-Subtype";
            } 

            if (strpos_arr(" " . $record->trial_title, $array_search_sub_not_disease) || strpos_arr($record->disease_arr, $array_search_sub_not_disease)) {
                $record->search_result_score = $record->search_result_score-25;
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
                        $record->search_result_score = $record->search_result_score+5;
                        $record->search_result_string = $record->search_result_string . "-Stage";
                    }
                } catch (\Exception $e) {
                    
                }
            }
                

            //ecog matching
            if (!is_null($record->ecog)) {
                try {
                    if (str_contains($record->ecog, $searchEcog)) {
                        $record->search_result_score = $record->search_result_score+5;
                        $record->search_result_string = $record->search_result_string . "-Ecog";
                    }
                } catch (\Exception $e) {
                    
                }
            }
            
  
           $record->search_result_score = $record->search_result_score; //2;

            if (strtotime($record->study_first_posted) > strtotime($new_flag_date)) {
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

            $bln_brain = false;
            if ($brainMetsResults != "") {
                foreach($brainMetsResults as $brainTrial) {
                    if ($brainTrial->trial_id == $record->trial_id) {
                        $bln_brain = true;
                        $record->search_result_string = $record->search_result_string . "-Brain";
                    }
                }
            }

            if ($bln_brain == true) {
                $record->search_result_score = $record->search_result_score+35;
            }


            $bln_biomarker = false;
            //$record->biomarker_record = $biomarkerRecord;
            if ($record->eligibility_biomarker != "" && $record->eligibility_biomarker != null && !empty($record->eligibility_biomarker)){

                foreach($biomarkerRecord as $indivBiomarker) {
                    $pieces = explode(" ", $indivBiomarker->biomarker_label);
                    foreach($pieces as $piece)
                    if (stripos($record->eligibility_biomarker, strtolower($piece))) {
                        $bln_biomarker = true;
                        $record->search_result_string = $record->search_result_string . "-Biomarker";
                    }
                }
            } 
            if ($bln_biomarker == true) {
                $record->search_result_score = $record->search_result_score+20;
            }

            unset($record->ecog);
            unset($record->stage);
            unset($record->current_trial_status_date);
            unset($record->study_first_posted);
            unset($record->eligibility_maximum_age);
            unset($record->eligibility_minimum_age);
            unset($record->disease_count);
            unset($record->disease_arr);
            unset($record->eligibility_biomarker);
            unset($record->eligibility_comorbidities);
            $array[] = $record;
        }
        
        patient::where('sub',$the_object->sub)->update(['view_at'=>date('Y-m-d H:i:s')]);


        //array_multisort(array_column($array, 'bln_sponsored'), SORT_DESC,
        //        array_column($array, 'distance'),      SORT_ASC,
        //        $array);

        array_multisort(array_column($array, 'search_result_score'), SORT_DESC,
                array_column($array, 'distance'),      SORT_ASC,
                $array);
        return array_slice($array, 0, 500);
    }
}
