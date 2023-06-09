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
        $patientRecord = patient::where('sub',$the_object->sub)->where('email', $the_object->email)->get();
        $patientDiagnosisRecord = patientdiagnosis::where('patient_id', $patientRecord[0]['patient_id'])->get();
        $patientBiomarkerRecord =  DB::connection('pgsql')->select("
        select lb.biomarker_synonyms from lkup_patient_diagnosis_biomarkers lb
        inner join patient_diagnosis_biomarkers b on lb.biomarker_id = b.biomarker_id
        and b.diagnosis_id = " . $patientDiagnosisRecord[0]['diagnosis_id'] . "
        ");
        $patientTreatmentRecord =  DB::connection('pgsql')->select("
        select lt.treatment_synonyms from lkup_patient_diagnosis_treatments lt
        inner join patient_diagnosis_treatments t on lt.treatment_id = t.treatment_id
        and t.diagnosis_id = " . $patientDiagnosisRecord[0]['diagnosis_id'] . "
        ");
        $patientCancerTypeRecord = lkuppatientdiagnosiscancertype::where('cancer_type_id',$patientDiagnosisRecord[0]['cancer_type_id'])->get();
        if ($patientCancerTypeRecord[0]['cancer_type_label'] == "Melanoma") {
            $string_tableName = "melanoma";
        } else {
            $string_tableName = "nsclc";
        }       

        
        $all_subtype_sysnonyms =  DB::connection('pgsql')->select("
        select lk.cancer_sub_type_synonyms from lkup_patient_diagnosis_cancer_sub_types lk
        where lk.cancer_type_id = " . $patientDiagnosisRecord[0]['cancer_type_id'] . "
        ");

        $subtypeArray = array();
        if (!empty($all_subtype_sysnonyms)) {
            foreach($all_subtype_sysnonyms as $synonym) {
                $subtypeArray[] = $synonym->cancer_sub_type_synonyms;
            }
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
            $array_search_sub_disease = 'Holder,Value';
            $array_search_sub_not_disease = 'Holder,Value'; 
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
        $searchEcog = $searchEcog-1;
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
                            --where trials_" . $string_tableName . "_thin_full.nct_id = 'NCT05671510'
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
            $bln_any_sub_type = false;
            $bln_biomarker_inclusion = false;
            $bln_biomarker_inclusion_0 = false;
            $bln_biomarker_inclusion_70 = false;
            $bln_biomarker_inclusion_90 = false;
            $bln_biomarker_inclusion_100 = false;
            $bln_biomarker_exclusion = false;
            $bln_prior_treatment_inclusion = false;
            $bln_prior_treatment_exclusion = false;
            $bln_ecog = false;
            $bln_ecog_inclusion = false;
            $bln_ecog_exclusion = false;
            $bln_stage = false;
            $bln_stage_inclusion = false;
            $bln_stage_exclusion = false;
            $bln_specificity_lvl_1 = false;
            $bln_specificity_lvl_2 = false;
            $bln_specificity_lvl_3 = false;
            $bln_specificity_lvl_4 = false;
            $bln_specificity_lvl_5 = false;
            $bln_specificity_lvl_6 = false;
            $bln_specificity_lvl_7 = false;


            $record->search_result_score = 0.0;
            $record->search_result_string = "Matching ";
            
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
            if (!empty($all_subtype_sysnonyms)) {
            foreach($subtypeArray as $subtype){
                    if (strpos($record->trial_title,$subtype) || strpos_arr($record->disease_arr,$subtype)) {
                        $bln_any_sub_type = true;
                    }
                }
            }

            if (strpos_arr(" " . $record->trial_title, explode(",",$array_search_sub_disease)) || strpos_arr($record->disease_arr, explode(",",$array_search_sub_disease))) {
                $bln_sub_type = true;
            } 

            if (strpos_arr(" " . $record->trial_title, explode(",",$array_search_sub_not_disease)) || strpos_arr($record->disease_arr, explode(",",$array_search_sub_not_disease))) {
                $bln_sub_type = false;
            } 

            //stage matching
            $record->patient_stage = $searchStage;
            if (!is_null($record->stage) && !is_null($searchStage)) {
                $bln_stage = true;
            }
            if ($bln_stage) {
                    if (str_contains($record->stage, $searchStage)) {
                        $bln_stage_inclusion = true;
                    } else {
                        $bln_stage_exclusion = true;
                    }
            }
            

            //ecog matching
            if (!is_null($record->ecog) && !is_null($searchEcog)) {
                $bln_ecog = true;
            }
            if ($bln_ecog) {
                    if (str_contains($record->ecog, $searchEcog)) {
                        $bln_ecog_inclusion = true;
                    } else {
                        $bln_ecog_exclusion = true;
                    }
            }

            $record->patient_has_brain_mets = $patientHasBrainMets;
            // match brain mets inclusion & exclusion (match/fail)
            if ($patientHasBrainMets && $record->inclusion_brain_mets == "true") {
                $bln_brain_mets_match = true;
            }
            if ($patientHasBrainMets && $record->exclusion_brain_mets == "true") {
                $bln_brain_mets_fail = true;
            }
            // new check if brain mets is only exclusion an patient does not match
            if ($record->inclusion_brain_mets == null && $bln_brain_mets_fail == false) {
                $bln_brain_mets_match = true;
            }

            

            //check if the patient has even filled in biomarker drop down
            $record->patient_biomarkers = $patientBiomarkerRecord;
            
            if (empty($patientBiomarkerRecord)) {
                $bln_biomarker_inclusion_0 = true;
            }


            
            if ( //patient & both inclusion and exclusion are all empty
                ($bln_biomarker_inclusion_0) &&
                ($record->exclusion_biomarker == "" || $record->exclusion_biomarker == null || empty($record->exclusion_biomarker)) &&
                ($record->inclusion_biomarker == "" || $record->inclusion_biomarker == null || empty($record->inclusion_biomarker))
                ) {
                    $bln_biomarker_inclusion_70 = true;
                }

            if ( //patient & both inclusion are both empty, but exclusion is not
                ($bln_biomarker_inclusion_0) &&
                ($record->inclusion_biomarker == "" || $record->inclusion_biomarker == null || empty($record->inclusion_biomarker)) &&
                ($record->exclusion_biomarker <> "" && $record->exclusion_biomarker <> null && !empty($record->exclusion_biomarker))
                ) {
                    $bln_biomarker_inclusion_90 = true;
                }                
    
            if (!$bln_biomarker_inclusion_0){
                // check if patient has any of the trial's exclusion biomarkers
                
                if ($record->exclusion_biomarker != "" && $record->exclusion_biomarker != null && !empty($record->exclusion_biomarker)){
                
                    foreach($patientBiomarkerRecord as $indivBiomarker) {
                        $pieces = explode(",", $indivBiomarker->biomarker_synonyms);
                        foreach($pieces as $piece)
                        if (stripos(" ".$record->exclusion_biomarker, $piece)) {
                            $bln_biomarker_exclusion = true;
                        }
                    }
                } 
            }
            
            if (!$bln_biomarker_inclusion_0 && !$bln_biomarker_exclusion) {
                
                // check if patient has all of the trial's inclusion biomarkers
                if ($record->inclusion_biomarker != "" && $record->inclusion_biomarker != null && !empty($record->inclusion_biomarker)){
                    
                    $bln_biomarker_inclusion = true;
                    // clean and make an array of the trials inclusion biomarkers
                    $inclusion_biomarker_list = str_replace("{", "", $record->inclusion_biomarker);
                    $inclusion_biomarker_list = str_replace("}", "", $inclusion_biomarker_list); 
                    $trial_inclusion_biomarker_array = explode (",", $inclusion_biomarker_list);

                    // make an array of the patients biomarkers synonyms
                    unset($patient_inclusion_biomarker_array);
                    foreach($patientBiomarkerRecord as $indivBiomarker) {
                        $patient_inclusion_biomarker_array[] = $indivBiomarker->biomarker_synonyms;
                    }
                    
                    
                    $record->user_inclusion_biomarker = $patient_inclusion_biomarker_array;
                    // make sure each & all of the trial biomarkers are in the patients biomarkers array
                    $bln_biomarker_inclusion = false;
                    foreach($trial_inclusion_biomarker_array as $individual_inclusion_biomarker) {
                            //if any trial biomarker is in the patients array, then inclusion is true
                            if (stripos(json_encode($patient_inclusion_biomarker_array), strtolower($individual_inclusion_biomarker))) {
                                $bln_biomarker_inclusion = true;
                            }
                    }
                //else if trials biomarker inclusion is empty
                
                } else {
                    
                    if ($record->exclusion_biomarker == "" || $record->exclusion_biomarker == null || empty($record->exclusion_biomarker)){
                        $bln_biomarker_inclusion_70 = true;
                    } else {
                        $bln_biomarker_inclusion = true;
                    }
                }

                if ($bln_biomarker_inclusion) {
                    foreach($patientBiomarkerRecord as $indivBiomarker) {
                        if (strpos(strtolower($record->trial_title),strtolower($indivBiomarker->biomarker_synonyms))) {
                            $bln_biomarker_inclusion_100 = true;
                        }
                    }
                    
                    if (!$bln_biomarker_inclusion_100) {
                        $bln_biomarker_inclusion_90 = true;
                    }
                }
            

            }
            
            if ( //patient & both inclusion are both empty, but exclusion is not
                ($bln_biomarker_inclusion_0) &&
                ($record->inclusion_biomarker <> "" && $record->inclusion_biomarker <> null && !empty($record->inclusion_biomarker))
                ) {  
                    $bln_biomarker_inclusion = false;
                    $bln_biomarker_inclusion_0 = false;
                }
            
                // check if patient has any of the trial's inclusion prior treatment biomarkers
            $record->patient_treatments = $patientTreatmentRecord;
            $record->bln_treatment_naive = "false";

            if (empty($patientTreatmentRecord) || $patientDiagnosisRecord[0]["is_treatment_started"] == "false") {
                if ($record->is_naive_to_all_treatment) {
                    $record->bln_treatment_naive = "true";
                }
            }

            if (!empty($patientTreatmentRecord)) {
                if ($record->is_naive_to_all_treatment) {
                    $record->bln_treatment_naive = "mismatch";
                }
            }

            $bln_treatment_target_inclusion = false;
            $record->bln_treatment_target_inclusion = false;
            if ($record->inclusion_treatment_targets_arr != "" && $record->inclusion_treatment_targets_arr != null && !empty($record->inclusion_treatment_targets_arr)){
                foreach($patientTreatmentRecord as $indivTreatment) {
                    $pieces = explode(",", $indivTreatment->treatment_synonyms);
                    foreach($pieces as $piece)
                    if (stripos(" ".$record->inclusion_treatment_targets_arr, strtolower($piece))) {
                        $bln_treatment_target_inclusion = true;
                        $record->bln_treatment_target_inclusion = true;

                    }
                }
            } else {
                // trial record does not have inclusion prior target, so patient has matched regardless
                $bln_treatment_target_inclusion = true;
                $record->bln_treatment_target_inclusion = true;
            }  

            $bln_treatment_drug_inclusion = false;
            $record->bln_treatment_drug_inclusion = false;
            if ($record->inclusion_treatment_drug_arr != "" && $record->inclusion_treatment_drug_arr != null && !empty($record->inclusion_treatment_drug_arr)){
                foreach($patientTreatmentRecord as $indivTreatment) {
                    $pieces = explode(",", $indivTreatment->treatment_synonyms);
                    foreach($pieces as $piece)
                    if (stripos(" ".$record->inclusion_treatment_drug_arr, strtolower($piece))) {
                        $bln_treatment_drug_inclusion = true;
                        $record->bln_treatment_drug_inclusion = true;

                    }
                }
            } else {
                // trial record does not have inclusion prior target, so patient has matched regardless
                $bln_treatment_drug_inclusion = true;
                $record->bln_treatment_drug_inclusion = true;
            }

            $bln_treatment_target_exclusion = false;
            $record->bln_treatment_target_exclusion = false;
            if ($record->exclusion_treatment_targets_arr != "" && $record->exclusion_treatment_targets_arr != null && !empty($record->exclusion_treatment_targets_arr)){
                foreach($patientTreatmentRecord as $indivTreatment) {
                    $pieces = explode(",", $indivTreatment->treatment_synonyms);
                    foreach($pieces as $piece)
                    if (stripos(" ".$record->exclusion_treatment_targets_arr, strtolower($piece))) {
                        $bln_treatment_target_exclusion = true;
                        $record->bln_treatment_target_exclusion = true;
                        
                    }
                }
            } 

            $bln_treatment_drug_exclusion = false;
            $record->bln_treatment_drug_exclusion = false;
            if ($record->exclusion_treatment_drug_arr != "" && $record->exclusion_treatment_drug_arr != null && !empty($record->exclusion_treatment_drug_arr)){
                foreach($patientTreatmentRecord as $indivTreatment) {
                    $pieces = explode(",", $indivTreatment->treatment_synonyms);
                    foreach($pieces as $piece)
                    if (stripos(" ".$record->exclusion_treatment_drug_arr, strtolower($piece))) {
                        $bln_treatment_drug_exclusion = true;
                        $record->bln_treatment_drug_exclusion = true;
                        
                    }
                }
            } 

            if ($record->bln_treatment_naive == "true") {
                $record->search_result_score = $record->search_result_score+$scoring_array['bln_prior_treatment_inclusion'];
                $record->search_result_string = $record->search_result_string . " Prior Treatment Naive Match "; 
            } elseif ($record->bln_treatment_naive == "mismatch") {
                $record->search_result_score = $record->search_result_score+$scoring_array['bln_prior_treatment_exclusion'];
                $record->search_result_string = $record->search_result_string . " Prior Treatment Naive Mis-Match ";
            } elseif (!$bln_treatment_drug_exclusion && !$bln_treatment_target_exclusion && $bln_treatment_drug_inclusion && $bln_treatment_target_inclusion) {
                $record->search_result_score = $record->search_result_score+$scoring_array['bln_prior_treatment_inclusion'];
                $record->search_result_string = $record->search_result_string . " Prior Treatment Match "; 
            } else {
                $record->search_result_score = $record->search_result_score+$scoring_array['bln_prior_treatment_exclusion'];
                $record->search_result_string = $record->search_result_string . " Prior Treatment Mis-Match "; 
            }
            

            /* check if heuristic variables are true, and add score */
            if ($bln_specificity_lvl_1) {
                $record->search_result_score = $record->search_result_score+$scoring_array['bln_specificity_lvl_1'];
                $record->search_result_string = $record->search_result_string . " specificity_lvl_1 ";    
            }
            elseif ($bln_specificity_lvl_2) {
                $record->search_result_score = $record->search_result_score+$scoring_array['bln_specificity_lvl_2'];
                $record->search_result_string = $record->search_result_string . " specificity_lvl_2 ";    
            }
            elseif ($bln_specificity_lvl_3) {
                $record->search_result_score = $record->search_result_score+$scoring_array['bln_specificity_lvl_3'];
                $record->search_result_string = $record->search_result_string . " specificity_lvl_3 ";    
            }
            elseif ($bln_specificity_lvl_4) {
                $record->search_result_score = $record->search_result_score+$scoring_array['bln_specificity_lvl_4'];
                $record->search_result_string = $record->search_result_string . " specificity_lvl_4 ";    
            }
            elseif ($bln_specificity_lvl_5) {
                $record->search_result_score = $record->search_result_score+$scoring_array['bln_specificity_lvl_5'];
                $record->search_result_string = $record->search_result_string . " specificity_lvl_5 ";    
            }
            elseif ($bln_specificity_lvl_6) {
                $record->search_result_score = $record->search_result_score+$scoring_array['bln_specificity_lvl_6'];
                $record->search_result_string = $record->search_result_string . " specificity_lvl_6 ";    
            }
            elseif ($bln_specificity_lvl_7) {
                $record->search_result_score = $record->search_result_score+$scoring_array['bln_specificity_lvl_7'];
                $record->search_result_string = $record->search_result_string . " specificity_lvl_7 ";    
            }

 
            if ($bln_biomarker_exclusion) {
                $record->search_result_score = $record->search_result_score+$scoring_array['bln_biomarker_exclusion'];
                $record->search_result_string = $record->search_result_string . " biomarker_exclusion ";
            } elseif ($bln_biomarker_inclusion_100) {
                $record->search_result_score = $record->search_result_score+$scoring_array['bln_biomarker_inclusion_100'];
                $record->search_result_string = $record->search_result_string . " biomarker_inclusion_100 ";
            } elseif ($bln_biomarker_inclusion_90) {
                $record->search_result_score = $record->search_result_score+$scoring_array['bln_biomarker_inclusion_90'];
                $record->search_result_string = $record->search_result_string . " biomarker_inclusion_90 ";
            } elseif ($bln_biomarker_inclusion_70) {
                $record->search_result_score = $record->search_result_score+$scoring_array['bln_biomarker_inclusion_70'];
                $record->search_result_string = $record->search_result_string . " biomarker_inclusion_70 ";
            } elseif ($bln_biomarker_inclusion_0) {
                $record->search_result_score = $record->search_result_score+$scoring_array['bln_biomarker_inclusion_0'];
                $record->search_result_string = $record->search_result_string . " biomarker_inclusion_0 ";
            } elseif (!$bln_biomarker_inclusion) {
                $record->search_result_score = $record->search_result_score+$scoring_array['bln_biomarker_exclusion'];
                $record->search_result_string = $record->search_result_string . " biomarker_no_inclusion ";
            }


            if ($bln_any_sub_type && !$bln_sub_type) {
                $record->search_result_score = $record->search_result_score+$scoring_array['bln_sub_type_exclusion'];
                $record->search_result_string = $record->search_result_string . " sub_type_exclusion ";
            }
            if ($bln_sub_type) {
                $record->search_result_score = $record->search_result_score+$scoring_array['bln_sub_type_inclusion'];
                $record->search_result_string = $record->search_result_string . " sub_type_inclusion ";
            }

            if ($bln_brain_mets_match) {
                $record->search_result_score = $record->search_result_score+$scoring_array['bln_brain_mets_match'];
                $record->search_result_string = $record->search_result_string . " brain_mets_match ";
            } 

            if ($bln_brain_mets_fail) {
                $record->search_result_score = $record->search_result_score+$scoring_array['bln_brain_mets_fail'];
                $record->search_result_string = $record->search_result_string . " brain_mets_fail ";
            } 

            if ($bln_stage) {
                if($bln_stage_inclusion) {
                    $record->search_result_score = $record->search_result_score+$scoring_array['bln_stage_inclusion'];
                    $record->search_result_string = $record->search_result_string . " stage_inclusion ";
                } else {
                    if ($searchStage == "4") {
                        $record->search_result_score = $record->search_result_score-100;
                        $record->search_result_string = $record->search_result_string . " stage_full_exclusion ";
                    } else {
                        $record->search_result_score = $record->search_result_score+$scoring_array['bln_stage_exclusion'];
                        $record->search_result_string = $record->search_result_string . " stage_exclusion ";
                    }    
                }
            }

            if ($bln_ecog) {
                if($bln_ecog_inclusion) {
                    $record->search_result_score = $record->search_result_score+$scoring_array['bln_ecog_inclusion'];
                    $record->search_result_string = $record->search_result_string . " ecog_inclusion ";
                } else {
                    $record->search_result_score = $record->search_result_score+$scoring_array['bln_ecog_exclusion'];
                    $record->search_result_string = $record->search_result_string . " ecog_exclusion ";
                }
            }




            if ($record->search_result_score > 100) {
                $record->search_result_score = 100;
            }
            if ($record->search_result_score < 0) {
                $record->search_result_score = 0;
            }

            $record->search_result_string = $record->search_result_string . " -" . $record->search_result_score . "-";

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
            unset($record->inclusion_prior_treatment);
            unset($record->exclusion_prior_treatment);
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
