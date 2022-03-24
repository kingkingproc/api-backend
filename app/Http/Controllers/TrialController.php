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
        $searchTerm = $cancerTypeRecord[0]['cancer_type_label'];
        $cancerSubTypeRecord = lkuppatientdiagnosiscancersubtype::where('cancer_sub_type_id',$diagnosisRecord[0]['cancer_sub_type_id'])->get();
        //$searchSubTerm = $cancerSubTypeRecord[0]['cancer_sub_type_label'];

        $searchPhase = $diagnosisRecord[0]->performance_score_id;
        $searchEcog = $diagnosisRecord[0]->performance_score_id;
        $searchStage = $diagnosisRecord[0]->stage_id;

        $addressRecord = address::find($patientRecord[0]['address_id']);
        $coordinates = DB::table('us')
                    ->where('zipcode', '=', $addressRecord['address_zip'])
                    ->get();
        
        
        //return $coordinates;
        $tempLat = $coordinates[0]->latitude;
        $tempLong = $coordinates[0]->longitude;

        $fRadius = (float)50;
        $fLatitude = (float)$tempLat;
        $fLongitude = (float)$tempLong;
/*  Good Query
        $testResults = DB::table("trials_melanoma")
        ->join('us', 'us.zipcode', '=', 'trials_melanoma.postal_code')
        ->select('trials_melanoma.*',
        'us.zipcode', DB::raw("6371 * acos(cos(radians(" . $fLatitude . "))
        * cos(radians(us.latitude)) 
        * cos(radians(us.longitude) - radians(" . $fLongitude . ")) 
        + sin(radians(" .$fLatitude. ")) 
        * sin(radians(us.latitude))) AS distance"))
        ->orderBy('distance', 'asc')
        ->limit(100)
        ->get();
*/

$testResults = DB::select(DB::raw(" 
                with cte_no_location as (
                    select trials_melanoma.trial_id, MIN(
                    6371 * acos(cos(radians(" . $fLatitude . "))
                            * cos(radians(us.latitude)) 
                            * cos(radians(us.longitude) - radians(" . $fLongitude . ")) 
                            + sin(radians(" . $fLatitude . ")) 
                            * sin(radians(us.latitude)))) AS distance
                    from trials_melanoma inner join us on trials_melanoma.postal_code = us.zipcode
                    group by trials_melanoma.trial_id
                    ),
                    cte_location as (
                    select trials_melanoma.trial_id, trials_melanoma.location_id,
                    6371 * acos(cos(radians(" . $fLatitude . "))
                            * cos(radians(us.latitude)) 
                            * cos(radians(us.longitude) - radians(" . $fLongitude . ")) 
                            + sin(radians(" . $fLatitude . ")) 
                            * sin(radians(us.latitude))) AS distance
                    from trials_melanoma inner join us on trials_melanoma.postal_code = us.zipcode
                    )
                    select cte_no_location.trial_id, cte_no_location.distance, cte_location.location_id
                    from cte_no_location inner join cte_location on cte_no_location.trial_id = cte_location.trial_id
                        and cte_no_location.distance = cte_location.distance
                    order by cte_no_location.distance
                    limit 200"
                ));
        //return $testResults;
        $trialList = [];

        //$testResults = $testResults->sortBy('trial_id');

        foreach($testResults as $record) {
           // if (in_array($record->trial_id, $trialList)) {
           //     continue;
           // }
           // array_push($trialList,$record->trial_id);

            $trialResults = DB::connection('pgsql2')->select('select * from trial
            where trial.trial_id = ?',array($record->trial_id));

            //return $trialResults;

            $locationResults = DB::connection('pgsql2')->select('select * from location
            where location.location_id = ?',array($record->location_id));

            //return $locationResults;

            $record->trial_title = $trialResults[0]->brief_title;
            $record->trial_summary = ""; //$trialResults[0]->brief_summary;
            $record->trial_status = $trialResults[0]->status_mapped;
            $record->nci_id = $trialResults[0]->nci_id;
            $record->nct_id = $trialResults[0]->nct_id;
            $record->phase = $trialResults[0]->phase;
            $record->stage = $trialResults[0]->stages;
            $record->ecog = $trialResults[0]->ecog_values;

            $record->location_name = $locationResults[0]->location_name;
            $record->location_address_line_1 = $locationResults[0]->address_line_1;
            $record->location_address_line_2 = $locationResults[0]->address_line_2;
            $record->location_city = $locationResults[0]->city;
            $record->location_state = $locationResults[0]->state;
            $record->location_postal_code = $locationResults[0]->postal_code;
            $record->location_country = $locationResults[0]->country;

            $professionals = DB::connection('pgsql2')->select('select * from trial_professional, trial_professional_ref
            where trial_professional.trial_professional_id = trial_professional_ref.trial_professional_id
            and trial_professional_ref.trial_id = ?',array($record->trial_id));
    
            $collaborators = DB::connection('pgsql2')->select('select * from collaborator, trial_collaborator_ref
            where collaborator.collaborator_id = trial_collaborator_ref.collaborator_id
            and trial_collaborator_ref.trial_id = ?',array($record->trial_id));
    
            $contacts = DB::connection('pgsql2')->select('select * from contact, trial_contact_ref
            where contact.contact_id = trial_contact_ref.contact_id
            and trial_contact_ref.trial_id = ?',array($record->trial_id));

            
            //$conditions = DB::connection('pgsql')->select('select disease_count  from trial_disease_count
            //where trial_disease_count.trial_id = ?',array($record->trial_id));
            
            //$record->disease_count = $conditions[0]->disease_count;
            $record->disease_count = [];
            $record->professional_data = $professionals;
            $record->collaborator_data = $collaborators;
            $record->contact_data = $contacts;
            //$record->contact_data = [];
            $record->disease_data = [];
            $record->related_location_data = [];
            $record->search_result_score = 4.0;
            $record->search_result_string = "Matching-";
            
            $myArr = ["open", "active", "available", "recruiting", "enrolling by invitation"];

            //if (stripos($record->disease_data, $searchSubTerm)) {
            //    $record->search_result_score = $record->search_result_score+1;
            //}
            if (stripos($record->trial_title, $searchTerm)) {
                $record->search_result_score = $record->search_result_score+2.0;
                $record->search_result_string = $record->search_result_string . "-Title";
            }
            //if (stripos($record->trial_summary, $searchTerm)) {
            //    $record->search_result_score = $record->search_result_score+2.0;
            //    $record->search_result_string = $record->search_result_string . "-Body";
            //}

            //if (in_array(strtolower($record->trial_status),$myArr)) {
            //    $record->search_result_score = $record->search_result_score+1.0;
            //    $record->search_result_string = $record->search_result_string . "-Status";
            //}
            //json_decode(json_encode($record->phase), true)
            
            //if (stripos($record->phase, $searchPhase)) {
            //    $record->search_result_score = $record->search_result_score+1.0;
            //    $record->search_result_string = $record->search_result_string . "-Phase";
            //}
            $record->phase = json_decode($record->phase);

            if (stripos($record->stage, $searchStage)) {
                $record->search_result_score = $record->search_result_score+2.0;
                $record->search_result_string = $record->search_result_string . "-Stage";
            }
            if (stripos($record->ecog, $searchEcog)) {
                $record->search_result_score = $record->search_result_score+2.0;
                $record->search_result_string = $record->search_result_string . "-Ecog";
            }
            /*
            if ($record->disease_count < 500) {
            $record->search_result_score = $record->search_result_score+1.0;
            $record->search_result_string = $record->search_result_string . "-Count";
            }
            if ($record->disease_count < 100) {
                $record->search_result_score = $record->search_result_score+1.0;
                $record->search_result_string = $record->search_result_string . "-LowCount";
                }  
            */  
           $record->search_result_score = $record->search_result_score/2;
            if ($record->search_result_score > 5) {
                $record->search_result_score = 5;
            }
            $array[] =  $record;
        }
        return $array;
    }
}
