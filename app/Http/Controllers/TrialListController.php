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

// simple function to compare elements within arrays
function strpos_arr($haystack, $needle) {
    if(!is_array($needle)) $needle = array($needle);
    foreach($needle as $what) {
        if(($pos = strpos($haystack, $what))!==false) return $pos;
    }
    return false;
}


class TrialListController extends Controller
{
    public function index()
    {

            /* find first tuesday of this month, or last month, if this month's has not passed.
               this dte is used to compare to study_first_posted column for "New" badge */
            $formerMonth = date("F", strtotime("-1 months"));
            $formerYear = date("Y", strtotime("-1 months"));
            $compareDate = date('m/d/Y', strtotime("first Tuesday of $formerMonth $formerYear"));
            $new_flag_date = $compareDate;

        

        $request = request();
        $the_object = Helper::verifyJasonToken($request);
        $patientRecord = patient::where('sub',$the_object->sub)->get();
        $patientDiagnosisRecord = patientdiagnosis::where('patient_id', $patientRecord[0]['patient_id'])->get();
        $patientBiomarkerRecord =  DB::connection('pgsql')->select("
        select lb.biomarker_synonyms from lkup_patient_diagnosis_biomarkers lb
        inner join patient_diagnosis_biomarkers b on lb.biomarker_id = b.biomarker_id
        and b.diagnosis_id = " . $patientDiagnosisRecord[0]['diagnosis_id'] . "
        ");
        $patientCancerTypeRecord = lkuppatientdiagnosiscancertype::where('cancer_type_id',$patientDiagnosisRecord[0]['cancer_type_id'])->get();
        if ($patientCancerTypeRecord[0]['cancer_type_label'] == "Melanoma") {
            $string_tableName = "melanoma";
        } else {
            $string_tableName = "nsclc";
        }       

        if ($patientDiagnosisRecord[0]['is_brain_tumor'] && $patientDiagnosisRecord[0]['is_metastatic']) {
            $patientHasBrainMets = true;
        } else{
            $patientHasBrainMets = false;
        }
        

        
        $prescreenTrialList = Helper::patientPrescreenStatus($patientRecord[0]['patient_id'],$string_tableName);


        $searchTerm = $patientCancerTypeRecord[0]['cancer_type_label'];

        if (!is_null($patientDiagnosisRecord[0]['cancer_sub_type_id'])) {
            $patientCancerSubTypeRecord = lkuppatientdiagnosiscancersubtype::where('cancer_sub_type_id',$patientDiagnosisRecord[0]['cancer_sub_type_id'])->get();
            $array_search_sub_disease = $patientCancerSubTypeRecord[0]['cancer_sub_type_synonyms'];
            $array_search_sub_not_disease = $patientCancerSubTypeRecord[0]['cancer_sub_type_antonyms'];
        } else {
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

        
        $searchEcog = $patientDiagnosisRecord[0]->performance_score_id;
        $searchStage = $patientDiagnosisRecord[0]->stage_id;

        $addressRecord = address::find($patientRecord[0]['address_id']);

        $array_disease_non_hematologic = array('non-hematologic','non hematologic','nonhematologic'); 
        $array_disease_contain = array('hematologic','lymphoid','lymphocytic','lymphoproliferative','hematological','lymphoma','hematopoietic','B-cell','B cell','NHL','MZBCL','MCL','MZL','DLBCL','LBCL','CLL','SLL','Leukemia','PTCL','CBCL','ALCL','PCBCL','ATLL');
        $array_disease_no_contain = array('hematologic','lymphoid','lymphocytic','lymphoproliferative','hematological');

        $testScoring = DB::connection('pgsql')->select(" 
                        Select  
                        scoring_group_trial_ref.nct_id,
                        scoring_criteria.variable,
                        scoring_group_criteria_ref.score
                        from scoring_group_criteria_ref
                        inner join scoring_group on scoring_group_criteria_ref.scoring_group_id = scoring_group.scoring_group_id
                        inner join scoring_criteria on scoring_group_criteria_ref.scoring_criteria_id = scoring_criteria.scoring_criteria_id
                        left join scoring_group_trial_ref on scoring_group_trial_ref.scoring_group_id = scoring_group_criteria_ref.scoring_group_id
                        where scoring_group.cancer_type = '" . $string_tableName ."'
        ");

        $testResults = DB::connection('pgsql2')->select(" 
                        with cte_lat_long as (
                            select latitude,longitude from us where zipcode = '" . $addressRecord['address_zip'] . "'
                            )
                            , cte_no_location as (
                            select trials_" . $string_tableName . "_thin_full.trial_id, MIN(
                            6371 * acos(cos(radians(cte_lat_long.latitude))
                                    * cos(radians(us.latitude)) 
                                    * cos(radians(us.longitude) - radians(cte_lat_long.longitude)) 
                                    + sin(radians(cte_lat_long.latitude)) 
                                    * sin(radians(us.latitude)))) AS distance
                            from cte_lat_long,trials_" . $string_tableName . "_thin_full inner join us on trials_" . $string_tableName . "_thin_full.postal_code = us.zipcode
                            group by trials_" . $string_tableName . "_thin_full.trial_id
                            ),
                            cte_location as (
                            select trials_" . $string_tableName . "_thin_full.trial_id, trials_" . $string_tableName . "_thin_full.location_id,
                            6371 * acos(cos(radians(cte_lat_long.latitude))
                                    * cos(radians(us.latitude)) 
                                    * cos(radians(us.longitude) - radians(cte_lat_long.longitude)) 
                                    + sin(radians(cte_lat_long.latitude)) 
                                    * sin(radians(us.latitude))) AS distance
                            from cte_lat_long,trials_" . $string_tableName . "_thin_full inner join us on trials_" . $string_tableName . "_thin_full.postal_code = us.zipcode
                            ),
                            cte_distinct_location as (
                            select cte_no_location.trial_id, cte_no_location.distance, min(cte_location.location_id) as location_id
                            from cte_no_location inner join cte_location on cte_no_location.trial_id = cte_location.trial_id
                                and cte_no_location.distance = cte_location.distance
                            group by cte_no_location.trial_id, cte_no_location.distance
                            )
                            select cte_distinct_location.trial_id, cte_distinct_location.distance, cte_distinct_location.location_id, 
                            trials_" . $string_tableName . "_thin_full.*, us.latitude, us.longitude, 0 as favorite
                            from cte_distinct_location
                            inner join trials_" . $string_tableName . "_thin_full on cte_distinct_location.trial_id = trials_" . $string_tableName . "_thin_full.trial_id
                            and cte_distinct_location.location_id = trials_" . $string_tableName . "_thin_full.location_id
                            inner join us on trials_" . $string_tableName . "_thin_full.postal_code = us.zipcode
                            order by cte_distinct_location.distance"
                );

        $favoriteResults = DB::connection('pgsql')->select("
                select type_id from patient_favorites where type='trial'
                and patient_id = " . $patientRecord[0]['patient_id'] . " and
                sub = '" . $patientRecord[0]['sub'] . "'");

        $trialList = [];


        foreach($testResults as $record) {
            /* check that the request is coming from search results and not map */
            if ($request->path() == 'api/triallist') {
                /* check that this is not a trial already processed */
                if (in_array($record->trial_id, $trialList)) {
                    continue;
                }
                array_push($trialList,$record->trial_id); 
            }

            /* check that the patient is within the trials age range */
            if ($patientRecord[0]["AGE"] > $record->eligibility_maximum_age) {
                continue;
            }
            if ($patientRecord[0]["AGE"] < $record->eligibility_minimum_age) {
                continue;
            }

            /* check if the trial is a favorite of the patient */
            foreach($favoriteResults as $favorite) {
                if ($favorite->type_id == $record->trial_id) {
                    $record->favorite = 1;
                }
            }

            /* check if the trial is new */
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

            /* check if the trial has a prescreen
               if it does, it means it is sponsored
               and need to check if the patient has taken & is eligible */
            foreach($prescreenTrialList as $prescreen) {
                
                if ($record->trial_id == $prescreen->trial_id) {
                    $record->bln_sponsored = true;
                    $record->bln_badge_travel = true;
                    $record->prescreen_flag = $prescreen->patient_eligible;

                } 
            }
            
            /* parse trial phase for display purposes */
            if ($record->phase != null) {
                $record->phase = preg_replace("/[^0-9,]/", "", $record->phase );
                $record->phase = "[" . $record->phase . "]";
            } else {
                $record->phase = "[0]";
            }

            $scoring_array = null;
            foreach ($testScoring as $score_record) {
                if ($record->trial_id == $score_record->nct_id) {
                $scoring_array[$score_record->variable] =$score_record->score;
                }
            }
            if ($scoring_array == "" || $scoring_array = null || empty($scoring_array)) {
                foreach ($testScoring as $score_record) {
                    if ($score_record->nct_id == null) {
                        $scoring_array[$score_record->variable] =$score_record->score;
                        } 
                    }
            }


            // set all matching variables to false 
            $bln_brain_mets_match = false;
            $bln_brain_mets_fail = false;
            $bln_sub_type = false;
            $bln_biomarker_inclusion = false;
            $bln_biomarker_exclusion = false;
            $bln_prior_treatment_inclusion = false;
            $bln_prior_treatment_exclusion = false;
            $bln_ecog = false;
            $bln_stage = false;
            $bln_specificity_lvl_1 = false;
            $bln_specificity_lvl_2 = false;
            $bln_specificity_lvl_3 = false;
            $bln_specificity_lvl_4 = false;
            $bln_specificity_lvl_5 = false;
            $bln_specificity_lvl_6 = false;
            $bln_specificity_lvl_7 = false;


            $record->search_result_score = 0.0;
            $record->search_result_string = "Matching-";
            
            /* large ifelse statement to find the specificity of the disease to trial */
            if (stripos(" " . $record->trial_title, $searchTerm) && stripos($record->disease_arr, $searchTerm) && $record->disease_count <= 5) {
                $bln_specificity_lvl_1 = true;
            }
            elseif ((stripos(" " . $record->trial_title, $searchTerm) || stripos($record->disease_arr, $searchTerm)) && $record->disease_count <= 5) {
                $bln_specificity_lvl_2 = true;
            }
            elseif ((stripos(" " . $record->trial_title, $searchTerm) || stripos($record->disease_arr, $searchTerm)) && $record->disease_count > 5 && $record->disease_count <= 500) {
                $bln_specificity_lvl_3 = true;
            }
            elseif ((stripos(" " . $record->trial_title, $searchTerm) || stripos($record->disease_arr, $searchTerm)) && $record->disease_count > 500) {
                $bln_specificity_lvl_4 = true;
            }
            elseif ((strpos_arr(" " . $record->trial_title, $array_disease_non_hematologic) || strpos_arr($record->disease_arr, $array_disease_non_hematologic)) && !strpos_arr(" " . $record->trial_title, $array_disease_contain) && !strpos_arr($record->disease_arr, $array_disease_contain)) {
                $bln_specificity_lvl_5 = true;
            }
            elseif ((strpos_arr(" " . $record->trial_title, $array_disease_non_hematologic) || strpos_arr($record->disease_arr, $array_disease_non_hematologic)) && !strpos_arr(" " . $record->trial_title, $array_disease_contain) && !strpos_arr($record->disease_arr, $array_disease_contain)) {
                $bln_specificity_lvl_5 = true;
            }
            elseif (!strpos_arr(" " . $record->trial_title, $array_disease_non_hematologic) && !strpos_arr($record->disease_arr, $array_disease_non_hematologic) && !strpos_arr(" " . $record->trial_title, $array_disease_no_contain) && !strpos_arr($record->disease_arr, $array_disease_no_contain)) {
                $bln_specificity_lvl_6 = true;
            }
            else {
                $bln_specificity_lvl_7 = true;
            }
            // end specificity

            /* check if cancer sub type in title or disease list */
            if (strpos_arr(" " . $record->trial_title, explode(",",$array_search_sub_disease)) || strpos_arr($record->disease_arr, explode(",",$array_search_sub_disease))) {
                $bln_sub_type = true;
            } 

            if (strpos_arr(" " . $record->trial_title, explode(",",$array_search_sub_not_disease)) || strpos_arr($record->disease_arr, explode(",",$array_search_sub_not_disease))) {
                $bln_sub_type = false;
            } 



            //stage matching
            if (!is_null($record->stage)) {
                try {
                    if (str_contains($record->stage, $searchStage)) {
                        $bln_stage = true;
                    }
                } catch (\Exception $e) {
                    
                }
            }

            //ecog matching
            if (!is_null($record->ecog)) {
                try {
                    if (str_contains($record->ecog, $searchEcog)) {
                        $bln_ecog = true;
                    }
                } catch (\Exception $e) {
                    
                }
            }

            // match brain mets inclusion & exclusion (match/fail)
            if ($patientHasBrainMets && stripos($record->inclusion_brain_mets,"true")) {
                $bln_brain_mets_match = true;
            }
            if ($patientHasBrainMets && stripos($record->exclusion_brain_mets,"true")) {
                $bln_brain_mets_fail = true;
            }

            // new check if brain mets is only exclusion an patient does not match
            if ($record->inclusion_brain_mets == null && $bln_brain_mets_fail == false) {
                $bln_brain_mets_match = true;
            }

            // check if patient has any of the trial's exclusion biomarkers
            if ($record->exclusion_biomarker != "" && $record->exclusion_biomarker != null && !empty($record->exclusion_biomarker)){
                foreach($patientBiomarkerRecord as $indivBiomarker) {
                    $pieces = explode(" ", $indivBiomarker->biomarker_synonyms);
                    foreach($pieces as $piece)
                    if (stripos($record->exclusion_biomarker, strtolower($piece))) {
                        $bln_biomarker_exclusion = true;

                    }
                }
            } 

            // check if patient has any of the trial's inclusion biomarkers
            if ($record->inclusion_biomarker != "" && $record->inclusion_biomarker != null && !empty($record->inclusion_biomarker)){
                foreach($patientBiomarkerRecord as $indivBiomarker) {
                    $pieces = explode(" ", $indivBiomarker->biomarker_synonyms);
                    foreach($pieces as $piece)
                    if (stripos($record->inclusion_biomarker, strtolower($piece))) {
                        $bln_biomarker_inclusion = true;

                    }
                }
            }

            // check if patient has any of the trial's inclusion prior treatment biomarkers
            if ($record->inclusion_prior_treatment != "" && $record->inclusion_prior_treatment != null && !empty($record->inclusion_prior_treatment)){
                foreach($patientBiomarkerRecord as $indivBiomarker) {
                    $pieces = explode(" ", $indivBiomarker->biomarker_synonyms);
                    foreach($pieces as $piece)
                    if (stripos($record->inclusion_prior_treatment, strtolower($piece))) {
                        $bln_prior_treatment_inclusion = true;
                    }
                }
            }


            /* check if heuristic variables are true, and add score */
            if ($bln_specificity_lvl_1) {
                $record->search_result_score = $record->search_result_score+$scoring_array['bln_specificity_lvl_1'];
                $record->search_result_string = $record->search_result_string . "-bln_specificity_lvl_1";    
            }
            elseif ($bln_specificity_lvl_2) {
                $record->search_result_score = $record->search_result_score+$scoring_array['bln_specificity_lvl_2'];
                $record->search_result_string = $record->search_result_string . "-bln_specificity_lvl_2";    
            }
            elseif ($bln_specificity_lvl_3) {
                $record->search_result_score = $record->search_result_score+$scoring_array['bln_specificity_lvl_3'];
                $record->search_result_string = $record->search_result_string . "-bln_specificity_lvl_3";    
            }
            elseif ($bln_specificity_lvl_4) {
                $record->search_result_score = $record->search_result_score+$scoring_array['bln_specificity_lvl_4'];
                $record->search_result_string = $record->search_result_string . "-bln_specificity_lvl_4";    
            }
            elseif ($bln_specificity_lvl_5) {
                $record->search_result_score = $record->search_result_score+$scoring_array['bln_specificity_lvl_5'];
                $record->search_result_string = $record->search_result_string . "-bln_specificity_lvl_5";    
            }
            elseif ($bln_specificity_lvl_6) {
                $record->search_result_score = $record->search_result_score+$scoring_array['bln_specificity_lvl_6'];
                $record->search_result_string = $record->search_result_string . "-bln_specificity_lvl_6";    
            }
            else {
                $record->search_result_score = $record->search_result_score+$scoring_array['bln_specificity_lvl_7'];
                $record->search_result_string = $record->search_result_string . "-bln_specificity_lvl_7";    
            }

 
            if ($bln_biomarker_exclusion) {
                $record->search_result_score = $record->search_result_score+$scoring_array['bln_biomarker_exclusion'];
                $record->search_result_string = $record->search_result_string . "-bln_biomarker_exclusion";
            }

            if ($bln_biomarker_inclusion) {
                $record->search_result_score = $record->search_result_score+$scoring_array['bln_biomarker_inclusion'];
                $record->search_result_string = $record->search_result_string . "-bln_biomarker_inclusion";
            }

                        // new check if inclusion biomarker is empty, and exclusion is not empty, but does not match
                        if ($record->inclusion_biomarker == null && $record->exclusion_biomarker != null) {
                            if (!$bln_biomarker_exclusion) {
                                $record->search_result_score = $record->search_result_score+$scoring_array['bln_biomarker_inclusion'];
                                $record->search_result_string = $record->search_result_string . "-bln_biomarker_exclusion_inclusion";
                            }
                        }

            if ($bln_prior_treatment_inclusion) {
                $record->search_result_score = $record->search_result_score+$scoring_array['bln_prior_treatment_inclusion'];
                $record->search_result_string = $record->search_result_string . "-bln_prior_treatment_inclusion";
            }

            if ($bln_prior_treatment_exclusion) {
                $record->search_result_score = $record->search_result_score+$scoring_array['bln_prior_treatment_exclusion'];
                $record->search_result_string = $record->search_result_string . "-bln_prior_treatment_exclusion";
            }

            if ($bln_sub_type) {
                $record->search_result_score = $record->search_result_score+$scoring_array['bln_sub_type'];
                $record->search_result_string = $record->search_result_string . "-bln_sub_type";
            }

            if ($bln_brain_mets_match) {
                $record->search_result_score = $record->search_result_score+$scoring_array['bln_brain_mets_match'];
                $record->search_result_string = $record->search_result_string . "-bln_brain_mets_match";
            } 

            if ($bln_brain_mets_fail) {
                $record->search_result_score = $record->search_result_score+$scoring_array['bln_brain_mets_fail'];
                $record->search_result_string = $record->search_result_string . "-bln_brain_mets_fail";
            } 

            if ($bln_stage) {
                $record->search_result_score = $record->search_result_score+$scoring_array['bln_stage'];
                $record->search_result_string = $record->search_result_string . "-bln_stage";
            }

            if ($bln_ecog) {
                $record->search_result_score = $record->search_result_score+$scoring_array['bln_ecog'];
                $record->search_result_string = $record->search_result_string . "-bln_ecog";
            }




            if ($record->search_result_score > 100) {
                $record->search_result_score = 100;
            }
            if ($record->search_result_score < 0) {
                $record->search_result_score = 0;
            }

            $record->biomarker_struct = $patientBiomarkerRecord;
            $record->is_brain_tumor = $patientDiagnosisRecord[0]['is_brain_tumor'];
            $record->is_metastatic = $patientDiagnosisRecord[0]['is_metastatic'];
            $record->primary_purpose = ucwords($record->primary_purpose);
            $record->location_postal_code = $record->postal_code;
            unset($record->biomarker_struct);
            /* unset($record->bln_inclusion_biomarker);
            unset($record->bln_exclusion_biomarker); 
            unset($record->inclusion_biomarker);
            unset($record->exclusion_biomarker); */
            unset($record->ecog);
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


        if ($request->path() == 'api/triallist') {
            array_multisort(array_column($array, 'search_result_score'), SORT_DESC,
            array_column($array, 'distance'),      SORT_ASC,
            $array);

            return array_slice($array, 0, 2000);
        } else {
            return $array;

        }
    }
}
