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

 

class SpecialistListController extends Controller
{
    public function index() {
        $request = request();
        $the_object = Helper::verifyJasonToken($request);
        $patientRecord = patient::where('sub',$the_object->sub)->where('email', $the_object->email)->get();
        $diagnosisRecord = patientdiagnosis::where('patient_id', $patientRecord[0]['patient_id'])->get();
        $cancerTypeRecord = lkuppatientdiagnosiscancertype::where('cancer_type_id',$diagnosisRecord[0]['cancer_type_id'])->get();
        $searchType = $cancerTypeRecord[0]['cancer_type_label'];
        if ($searchType == "Melanoma") {
            $string_tableName = "melanoma";
        } else {
            $string_tableName = "nsclc";
        } 



        $addressRecord = address::find($patientRecord[0]['address_id']);

        $testResults = DB::connection('pgsql2')->select("
        with cte_lat_long as (
            select latitude,longitude from us where zipcode = '" . $addressRecord['address_zip'] . "'
            )
            select  
            specialists_" . $string_tableName . "_full.provider_id, 
            specialists_" . $string_tableName . "_full.npi, 
            specialists_" . $string_tableName . "_full.first_name, 
            specialists_" . $string_tableName . "_full.last_name, 
            specialists_" . $string_tableName . "_full.middle_name, 
            specialists_" . $string_tableName . "_full.suffix, 
            specialists_" . $string_tableName . "_full.gender, 
            specialists_" . $string_tableName . "_full.credential, 
            specialists_" . $string_tableName . "_full.med_sch, 
            specialists_" . $string_tableName . "_full.coe_flag, 
            specialists_" . $string_tableName . "_full.location_id, 
            specialists_" . $string_tableName . "_full.location_name, 
            specialists_" . $string_tableName . "_full.location_address_line_1, 
            specialists_" . $string_tableName . "_full.location_address_line_2, 
            specialists_" . $string_tableName . "_full.location_city, 
            specialists_" . $string_tableName . "_full.location_state, 
            specialists_" . $string_tableName . "_full.location_country, 
            specialists_" . $string_tableName . "_full.postal_code, 
            specialists_" . $string_tableName . "_full.provider_name, 
            specialists_" . $string_tableName . "_full.publications_adj, 
            specialists_" . $string_tableName . "_full.max_citations_adj, 
            specialists_" . $string_tableName . "_full.h_index_adj, 
            specialists_" . $string_tableName . "_full.trial_count_adj,            
            us.zipcode, specialists_" . $string_tableName . "_full.postal_code as location_postal_code,
            us.latitude, us.longitude, 0 as favorite,
            6371 * acos(cos(radians(cte_lat_long.latitude))
                    * cos(radians(us.latitude)) 
                    * cos(radians(us.longitude) - radians(cte_lat_long.longitude)) 
                    + sin(radians(cte_lat_long.latitude)) 
                    * sin(radians(us.latitude))) AS distance
            from cte_lat_long,specialists_" . $string_tableName . "_full inner join us on specialists_" . $string_tableName . "_full.postal_code = us.zipcode
			order by distance asc
        ");


        $favoriteResults = DB::connection('pgsql')->select("
                select type_id from patient_favorites where type='specialist'
                and patient_id = " . $patientRecord[0]['patient_id'] . " and
                sub = '" . $patientRecord[0]['sub'] . "'");

        $providerList = [];

        foreach($testResults as $record) {
            if ($request->path() == 'api/specialistlist') {
                if (in_array($record->provider_id, $providerList)) {
                    continue;
                }
                array_push($providerList,$record->provider_id);
            }

            foreach($favoriteResults as $favorite) {
                if ($favorite->type_id == $record->provider_id) {
                    $record->favorite = 1;
                }
            }
            //$record->all_location = json_decode($record->all_location);
            //$record->all_trials = json_decode($record->all_trials);
            //$record->specialties = json_decode($record->specialties);

            $record->coe_flag = var_export($record->coe_flag, true);
            if ($record->coe_flag === "true") {$record->coe_flag = "Yes";}
            if ($record->coe_flag === "false") {$record->coe_flag = "No";}

            $record->search_result_score = 0.00;
            
            $trial_count_score = 0.00;

            if ($record->trial_count_adj == 0) {
                $trial_count_score = 1.00;
            }
            if ($record->trial_count_adj > 0 && $record->trial_count_adj <= 1) {
                $trial_count_score = 2.00;
            }
            if ($record->trial_count_adj > 1 && $record->trial_count_adj <= 3) {
                $trial_count_score = 3.00;
            }
            if ($record->trial_count_adj > 3 && $record->trial_count_adj <= 6) {
                $trial_count_score = 4.00;
            }
            if ($record->trial_count_adj > 6) {
                $trial_count_score = 5.00;
            }


            $h_count_score = 0.00;

            if ($record->h_index_adj == 0) {
                $h_count_score = 1.00;
            }
            if ($record->h_index_adj > 0 && $record->h_index_adj <= 1) {
                $h_count_score = 2.00;
            }
            if ($record->h_index_adj > 1 && $record->h_index_adj <= 3) {
                $h_count_score = 3.00;
            }
            if ($record->h_index_adj > 3 && $record->h_index_adj <= 10) {
                $h_count_score = 4.00;
            }
            if ($record->h_index_adj > 10) {
                $h_count_score = 5.00;
            }
            $record->search_result_score = ($trial_count_score + $h_count_score) / 2;

            $record->pubmed_link = "https://pubmed.ncbi.nlm.nih.gov/?term=%28" . $record->last_name . "%5BAuthor+-+Last%5D%29+AND+%28" . $string_tableName . "%5BMeSH+Major+Topic%5D%29&sort=";
            $array[] =  $record;
        }
        if ($request->path() == 'api/specialistlist') {

            array_multisort(array_column($array, 'search_result_score'), SORT_DESC,
            array_column($array, 'distance'),      SORT_ASC,
            $array);

            return array_slice($array, 0, 2000);
        } else {
            return $array;
        }

    }

}
