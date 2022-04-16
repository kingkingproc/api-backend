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

class TestController extends Controller
{
    public function index()
    {
        $request = request();
        $the_object = Helper::verifyJasonToken($request);
        $patientRecord = patient::where('sub',$the_object->sub)->get();
        $diagnosisRecord = patientdiagnosis::where('patient_id', $patientRecord[0]['patient_id'])->get();
        $cancerTypeRecord = lkuppatientdiagnosiscancertype::where('cancer_type_id',$diagnosisRecord[0]['cancer_type_id'])->get();
        $searchType = $cancerTypeRecord[0]['cancer_type_label'];
        //$cancerSubTypeRecord = lkuppatientdiagnosiscancersubtype::where('cancer_sub_type_id',$diagnosisRecord[0]['cancer_sub_type_id'])->get();
        //$searchSubType = $cancerSubTypeRecord[0]['cancer_sub_type_label'];

        $searchPhase = $diagnosisRecord[0]->performance_score_id;
        $searchEcog = $diagnosisRecord[0]->performance_score_id;
        $searchStage = $diagnosisRecord[0]->stage_id;

        $addressRecord = address::find($patientRecord[0]['address_id']);

        $testResults = DB::connection('pgsql2')->select("
        with cte_lat_long as (
            select latitude,longitude from us where zipcode = '". $addressRecord['address_zip'] ."'
            )
            , cte_no_location as (
            select trials_melanoma.trial_id, MIN(
            6371 * acos(cos(radians(cte_lat_long.latitude))
                    * cos(radians(us.latitude)) 
                    * cos(radians(us.longitude) - radians(cte_lat_long.longitude)) 
                    + sin(radians(cte_lat_long.latitude)) 
                    * sin(radians(us.latitude)))) AS distance
            from cte_lat_long,trials_melanoma inner join us on trials_melanoma.postal_code = us.zipcode
            group by trials_melanoma.trial_id
            ),
            cte_location as (
            select trials_melanoma.trial_id, trials_melanoma.location_id,
            6371 * acos(cos(radians(cte_lat_long.latitude))
                    * cos(radians(us.latitude)) 
                    * cos(radians(us.longitude) - radians(cte_lat_long.longitude)) 
                    + sin(radians(cte_lat_long.latitude)) 
                    * sin(radians(us.latitude))) AS distance
            from cte_lat_long,trials_melanoma inner join us on trials_melanoma.postal_code = us.zipcode
            )
            select cte_no_location.trial_id, cte_no_location.distance, cte_location.location_id, 
            trial.brief_title as trial_title, trial.phase, trial.ecog_values as ecog, 
            trial.stages as stage, trial.status_mapped as trial_status, trial.nci_id, trial.nct_id,
            location.location_name
            from cte_no_location inner join cte_location on cte_no_location.trial_id = cte_location.trial_id
                and cte_no_location.distance = cte_location.distance
            inner join trial on cte_no_location.trial_id = trial.trial_id
            inner join location on cte_location.location_id = location.location_id
            order by cte_no_location.distance");

    foreach($testResults as $record) {

        $record->trial_summary = "";

        $record->location_address_line_1 = "";
        $record->location_address_line_2 = "";
        $record->location_city = "";
        $record->location_state = "";
        $record->location_postal_code = "";
        $record->location_country = "";

        $record->disease_count = [];
        $record->professional_data = [];
        $record->collaborator_data = [];
        $record->contact_data = [];
        //$record->contact_data = [];
        $record->disease_data = [];
        $record->related_location_data = [];
        $record->search_result_score = 4.0;
        $record->search_result_string = "Matching-";
        $record->phase = json_decode($record->phase);

        if (stripos($record->trial_title, $searchType)) {
            $record->search_result_score = $record->search_result_score+2.0;
            $record->search_result_string = $record->search_result_string . "-Title";
        }
        if (stripos($record->stage, $searchStage)) {
            $record->search_result_score = $record->search_result_score+2.0;
            $record->search_result_string = $record->search_result_string . "-Stage";
        }
        if (stripos($record->ecog, $searchEcog)) {
            $record->search_result_score = $record->search_result_score+2.0;
            $record->search_result_string = $record->search_result_string . "-Ecog";
        }

        $record->search_result_score = $record->search_result_score/2;
        if ($record->search_result_score > 5) {
            $record->search_result_score = 5;
        }
        $array[] =  $record;
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
