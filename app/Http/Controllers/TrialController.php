<?php

namespace App\Http\Controllers;

use App\Helper\Helper;

use Illuminate\Http\Request;
use App\Models\Patient;
use App\Models\PatientDiagnosis;
use App\Models\LkupPatientDiagnosisCancerType;
use App\Models\LkupPatientDiagnosisCancerSubType;
use App\Models\address;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;

function strpos_arr($haystack, $needle) {
    if(!is_array($needle)) $needle = array($needle);
    foreach($needle as $what) {
        if(($pos = strpos($haystack, $what))!==false) return $pos;
    }
    return false;
}

class TrialController extends Controller
{
    public function index()
    {

        $request = request();
        $the_object = Helper::verifyJasonToken($request);
        $patientRecord = patient::where('sub',$the_object->sub)->get();
        $diagnosisRecord = patientdiagnosis::where('patient_id', $patientRecord[0]['patient_id'])->get();
        $cancerTypeRecord = lkuppatientdiagnosiscancertype::where('cancer_type_id',$diagnosisRecord[0]['cancer_type_id'])->get();
        $searchTerm = $cancerTypeRecord[0]['cancer_type_label'];
        $cancerSubTypeRecord = lkuppatientdiagnosiscancersubtype::where('cancer_sub_type_id',$diagnosisRecord[0]['cancer_sub_type_id'])->get();
        $searchSubTerm = $cancerSubTypeRecord[0]['cancer_sub_type_label'];
        
        if ($searchTerm == "Melanoma") {
            $string_tableName = "melanoma";
        } else {
            $string_tableName = "nsclc";
        }


        $addressRecord = address::find($patientRecord[0]['address_id']);

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
                            trials_" . $string_tableName . "_thin_full.*, us.latitude, us.longitude
                            from cte_distinct_location
                            inner join trials_" . $string_tableName . "_thin_full on cte_distinct_location.trial_id = trials_" . $string_tableName . "_thin_full.trial_id
                            and cte_distinct_location.location_id = trials_" . $string_tableName . "_thin_full.location_id
                            inner join us on trials_" . $string_tableName . "_thin_full.postal_code = us.zipcode
                            order by cte_distinct_location.distance"
                );
        
        $trialList = [];

        //$testResults = $testResults->sortBy('trial_id');

        foreach($testResults as $record) {

            $record->location_postal_code = $record->postal_code;
            //Thin out record, so JSON is smaller:
            unset($record->professional_data);
            unset($record->collaborator_data);
            unset($record->contact_data);
            unset($record->disease_arr);
            unset($record->drug_arr);
            unset($record->trial_summary);
            unset($record->distance);
            unset($record->location_id);
            unset($record->postal_code);
            unset($record->phase);
            unset($record->ecog);
            unset($record->primary_purpose);
            unset($record->stage);
            unset($record->trial_status);
            //unset($record->nci_id);
            //unset($record->nct_id);
            unset($record->eligibility_maximum_age);
            unset($record->eligibility_minimum_age);
            unset($record->study_type);
            unset($record->additional_location_data);
            
            $array[] = $record;
        }
        
        return $array;
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
