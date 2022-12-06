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
use Illuminate\Pagination\LengthAwarePaginator;

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
        
        //set filters for pagination
        $filter_match = $request['filter_match'];
        $filter_distance = $request['filter_distance'];
        $filter_type = $request['filter_type'];
        $filter_phase = $request['filter_phase'];
        $filter_status = $request['filter_status'];
        //Recruiting = Open
        if ($filter_status == "Recruiting") {
            $filter_status = "Open";
        }
        $filter_sort = $request['filter_sort'];
        $filter_sort_direction = $request['filter_sort_direction'];
        $filter_size = $request['filter_size'];
        if ($filter_distance == 0) {
            $filter_distance = 100000;
        }
        $string_where_clause = " Where 1=1 ";

        if ($filter_status <> "All") {
            $string_where_clause = $string_where_clause." and trials_" . $string_tableName . "_full.trial_status like '%" . $filter_status . "%'";
        }

        if ($filter_type <> "All") {
            $string_where_clause = $string_where_clause." and trials_" . $string_tableName . "_full.study_type like '%" . $filter_type . "%'";
        }


        if ($filter_phase <> 0) {
            $string_where_clause = $string_where_clause." and trials_" . $string_tableName . "_full.phase like '%" . $filter_phase . "%'";
        }           

        $searchTerm = $cancerTypeRecord[0]['cancer_type_label'];
        $cancerSubTypeRecord = lkuppatientdiagnosiscancersubtype::where('cancer_sub_type_id',$diagnosisRecord[0]['cancer_sub_type_id'])->get();
        $searchSubTerm = $cancerSubTypeRecord[0]['cancer_sub_type_label'];

        //Patient record for DOB to figure out age req.
        $patientRecord[0]["DOB"] = $patientRecord[0]["dob_day"] . "-" . $patientRecord[0]["dob_month"] . "-" . $patientRecord[0]["dob_year"];
        $today = date("Y-m-d");
        $diff = date_diff(date_create($patientRecord[0]["DOB"]), date_create($today));
        $patientRecord[0]["AGE"] = $diff->format('%y');
        //return $patientRecord;

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
            $array_search_sub_not_disease = array('Excluding Mucosal Melanoma');
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
                            where 6371 * acos(cos(radians(cte_lat_long.latitude))
                            * cos(radians(us.latitude)) 
                            * cos(radians(us.longitude) - radians(cte_lat_long.longitude)) 
                            + sin(radians(cte_lat_long.latitude)) 
                            * sin(radians(us.latitude)))  <= " . $filter_distance . "
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
                            where 6371 * acos(cos(radians(cte_lat_long.latitude))
                            * cos(radians(us.latitude)) 
                            * cos(radians(us.longitude) - radians(cte_lat_long.longitude)) 
                            + sin(radians(cte_lat_long.latitude)) 
                            * sin(radians(us.latitude)))  <= " . $filter_distance . "
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
                            " . $string_where_clause . "
                            order by cte_distinct_location.distance"
                );
        //return $testResults;
        $favoriteResults = DB::connection('pgsql')->select("
                select type_id from patient_favorites where type='trial'
                and patient_id = " . $patientRecord[0]['patient_id'] . " and
                sub = '" . $patientRecord[0]['sub'] . "'");

        $trialList = [];
        $array = [];
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
            if (!empty($record->stage) && !empty($searchStage)) {
                if (stripos($record->stage, strval($searchStage))) {
                    $record->search_result_score = $record->search_result_score+1.0;
                    $record->search_result_string = $record->search_result_string . "-Stage";
                }
            }
            //ecog matching
            if (!empty($record->ecog) && !empty($searchEcog)) {
                if (stripos($record->ecog, strval($searchEcog))) {
                    $record->search_result_score = $record->search_result_score+1.0;
                    $record->search_result_string = $record->search_result_string . "-Ecog";
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



            $array[] = $record;
        }

        if (empty($array)) {
            return $array;
        }
        
                //filter the array according to the request variables
                if ($filter_distance <> 0) {
                    $array = array_filter($array, function ($item) use ($filter_distance) {
                        return $item->distance <= $filter_distance;
                    });
                }
        
                if ($filter_match <> 0) {
                    $array = array_filter($array, function ($item) use ($filter_match) {
                        return $item->search_result_score >= $filter_match;
                    });
                }

                if ($filter_status <> "All") {
                    $array = array_filter($array, function ($item) use ($filter_status) {
                        return $item->trial_status === $filter_status;
                    });
                }

                if ($filter_type <> "All") {
                    $array = array_filter($array, function ($item) use ($filter_type) {
                        return $item->study_type === $filter_type;
                    });
                }


                if ($filter_phase <> 0) {
                    $array = array_filter($array, function ($item) use ($filter_phase) {
                        return (stripos($item->phase, strval($filter_phase)) !== false);
                    });
                }             

                if ($filter_sort_direction == "desc") {
                    $array = collect($array)->sortBy($filter_sort)->reverse()->toArray();
                } else {
                    $array = collect($array)->sortBy($filter_sort)->toArray();
                }

        // paginate the filtered array and return
        $page = LengthAwarePaginator::resolveCurrentPage();
        $perPage = $filter_size;
        $results = $array;
        $items = array_slice($array, ($page - 1) * $perPage, $perPage);
        $posts = new LengthAwarePaginator($items, count($results), $perPage, $page);
        return $posts;
    }
}
