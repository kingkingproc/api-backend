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

class TrialController extends Controller
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
            select latitude,longitude from us where zipcode = '" . $addressRecord['address_zip'] . "'
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
            ),
			cte_distinct_location as (
            select cte_no_location.trial_id, cte_no_location.distance, min(cte_location.location_id) as location_id
            from cte_no_location inner join cte_location on cte_no_location.trial_id = cte_location.trial_id
                and cte_no_location.distance = cte_location.distance
			group by cte_no_location.trial_id, cte_no_location.distance
            )
            select cte_distinct_location.trial_id, cte_distinct_location.distance, cte_distinct_location.location_id, 
            trial.brief_title as trial_title, trial.phase, trial.ecog_values as ecog, 
            trial.brief_summary as trial_summary,
            trial.stages as stage, trial.status_mapped as trial_status, trial.nci_id, trial.nct_id,
            location.location_name, location.address_line_1 as location_address_line_1,
            location.address_line_2 as location_address_line_2, location.city as location_city,
            location.state as location_state, location.postal_code as location_postal_code,
            location.country as location_country
            from cte_distinct_location
            inner join trial on cte_distinct_location.trial_id = trial.trial_id
            inner join location on cte_distinct_location.location_id = location.location_id
            order by cte_distinct_location.distance");

            
            $professionals = DB::connection('pgsql2')->select('select * from trial_professional, trial_professional_ref
            where trial_professional.trial_professional_id = trial_professional_ref.trial_professional_id
            and trial_professional_ref.trial_id in (select trial_id from trials_melanoma)');

            $collaborators = DB::connection('pgsql2')->select('select * from collaborator, trial_collaborator_ref
            where collaborator.collaborator_id = trial_collaborator_ref.collaborator_id
            and trial_collaborator_ref.trial_id in (select trial_id from trials_melanoma)');

            
            $contacts = DB::connection('pgsql2')->select("select * from (
                select contact.contact_id, contact_name, contact_phone, contact_email, trial_id,nci_id, nct_id,
                    ROW_NUMBER() OVER (PARTITION BY trial_id Order by contact_email ASC) AS Sno
                    from contact, trial_contact_ref
                where contact.contact_id = trial_contact_ref.contact_id
                and trial_contact_ref.trial_id in (select trial_id from trials_melanoma)
                and contact.contact_name = 'Site Public Contact'
                    ) as foo where Sno <= 3
                union
                select contact.contact_id, contact_name, contact_phone, contact_email, trial_id,nci_id, nct_id,
                    0 as Sno
                from contact, trial_contact_ref
                where contact.contact_id = trial_contact_ref.contact_id
                and trial_contact_ref.trial_id in (select trial_id from trials_melanoma)
                and contact.contact_name <> 'Site Public Contact'");

/*
            $contacts = DB::connection('pgsql2')->select('select * from contact, trial_contact_ref
            where contact.contact_id = trial_contact_ref.contact_id
            and trial_contact_ref.trial_id in (select trial_id from trials_melanoma)');
*/            
                



    foreach($testResults as $record) {

        $record->disease_count = [];
        $record->professional_data = [];
        $record->collaborator_data = [];
        $record->contact_data = [];
        $record->disease_data = [];
        $record->related_location_data = [];
        $record->search_result_score = 4.0;
        $record->search_result_string = "Matching-";
        $record->phase = json_decode($record->phase);


            foreach($contacts as $contact_record) {
                foreach($contact_record as $single) {
                    if ($single == $record->trial_id) {
                                $record->contact_data[] = $contact_record;
                    }
                        
                }
            }

            foreach($collaborators as $collaborators_record) {
                foreach($collaborators_record as $single) {
                    if ($single == $record->trial_id) {
                                $record->collaborator_data[] = $collaborators_record;
                    }
                        
                }
            }

            foreach($professionals as $professionals_record) {
                foreach($professionals_record as $single) {
                    if ($single == $record->trial_id) {
                                $record->professional_data[] = $professionals_record;
                    }
                        
                }
            }

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
