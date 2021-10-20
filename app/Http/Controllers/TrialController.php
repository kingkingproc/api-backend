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

        //return $testResults;

        foreach($testResults as $record) {
            $trialResults = DB::connection('pgsql2')->select('select * from trial
            where trial.trial_id = ?',array($record->trial_id));

            //return $trialResults;

            $locationResults = DB::connection('pgsql2')->select('select * from location
            where location.location_id = ?',array($record->location_id));

            //return $locationResults;

            $record->trial_title = $trialResults[0]->brief_title;
            $record->trial_summary = $trialResults[0]->brief_summary;
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

            /*
            $conditions = DB::connection('pgsql2')->select('select * from trial_disease, trial_disease_ref
            where trial_disease.trial_disease_id = trial_disease_ref.trial_disease_id
            and trial_disease_ref.trial_id = ? limit 3',array($record->trial_id));
            */

            $record->professional_data = $professionals;
            $record->collaborator_data = $collaborators;
            $record->contact_data = $contacts;
            $record->disease_data = [];
            $record->related_location_data = [];
            $record->search_result_score = 2;
            
            $myArr = ["active", "available", "recruiting", "enrolling by invitation"];

            //if (stripos($record->disease_data, $searchSubTerm)) {
            //    $record->search_result_score = $record->search_result_score+1;
            //}
            if (stripos($record->trial_title, $searchTerm)) {
                $record->search_result_score = $record->search_result_score+1;
            }
            if (in_array(strtolower($record->trial_status),$myArr)) {
                $record->search_result_score = $record->search_result_score+1;
            }
            //json_decode(json_encode($record->phase), true)
            //if (stripos($record->phase, $searchPhase)) {
            if (stripos($record->phase, $searchPhase)) {
                $record->search_result_score = $record->search_result_score+1;
            }
            $record->phase = json_decode($record->phase);

            if (stripos($record->stage, $searchStage)) {
                $record->search_result_score = $record->search_result_score+1;
            }
            if (stripos($record->ecog, $searchEcog)) {
                $record->search_result_score = $record->search_result_score+1;
            }
            if ($record->search_result_score > 5) {
                $record->search_result_score = 5;
            }
            $array[] =  $record;
        }
        return $array;
    }
}
