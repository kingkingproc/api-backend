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

class SpecialistDetailController extends Controller
{
    public function index()
    {

        $request = request();
        $the_object = Helper::verifyJasonToken($request);
        $patientRecord = patient::where('sub',$the_object->sub)->get();
        $diagnosisRecord = patientdiagnosis::where('patient_id', $patientRecord[0]['patient_id'])->get();
        $cancerTypeRecord = lkuppatientdiagnosiscancertype::where('cancer_type_id',$diagnosisRecord[0]['cancer_type_id'])->get();
        $searchType = $cancerTypeRecord[0]['cancer_type_label'];
        if ($searchType == "Melanoma") {
            $string_tableName = "melanoma";
        } else {
            $string_tableName = "nsclc";
        } 

        $favoriteWhole = $request["payload"];

        foreach($favoriteWhole as $favorite) {
            $favorites = explode("_", $favorite);
            $trialArray[] =  $favorites[1];
            $locationArray[] =  $favorites[2];
        }

        $testResults = DB::connection('pgsql2')->select(" 
                        select * from specialists_" . $string_tableName . "_full
                            where provider_id IN('".implode("','",$trialArray)."')
                            and location_id  IN('".implode("','",$locationArray)."')
                            "
                );
      

        $providerList = [];

        foreach($testResults as $record) {
            if (in_array($record->provider_id, $providerList)) {
                continue;
            }
            array_push($providerList,$record->provider_id);


            
            $record->all_location = json_decode($record->all_location);
            $record->all_trials = json_decode($record->all_trials);
            $record->specialties = json_decode($record->specialties);

            $record->location_postal_code = $record->postal_code;

            $record->coe_flag = var_export($record->coe_flag, true);
            if ($record->coe_flag === "true") {$record->coe_flag = "Yes";}
            if ($record->coe_flag === "false") {$record->coe_flag = "No";}

            $record->pubmed_link = "https://pubmed.ncbi.nlm.nih.gov/?term=%28" . $record->last_name . "%5BAuthor+-+Last%5D%29+AND+%28" . $string_tableName . "%5BMeSH+Major+Topic%5D%29&sort=";

            $array[] = $record;
        }
        return $array;
    }
}
