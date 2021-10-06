<?php

namespace App\Http\Controllers;

use App\Helper\Helper;

use Illuminate\Http\Request;
use App\Models\Trial;
use App\Models\Patient;
use App\Models\PatientDiagnosis;
use App\Models\LkupPatientDiagnosisCancerType;
use App\Models\LkupPatientDiagnosisCancerSubType;
use App\Models\address;
use App\Models\SelectisTrial;
use App\Models\SelectisLocation;
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
        $searchTerm = $cancerTypeRecord[0]['cancer_type_label'];
        if ($searchTerm != "Melanoma") {
            return ["message"=>"Wrong Cancer Type"];
        }
        $cancerSubTypeRecord = lkuppatientdiagnosiscancersubtype::where('cancer_sub_type_id',$diagnosisRecord[0]['cancer_sub_type_id'])->get();
        $searchSubTerm = $cancerSubTypeRecord[0]['cancer_sub_type_label'];
        //$patientPhase = $patientRecord[0]['performance_score_id'];
        $addressRecord = address::find($patientRecord[0]['address_id']);
        //return $patientRecord;
        $patientPhase = DB::table('patient_diagnoses')
                    ->select('performance_score_id')
                    ->where('patient_id', '=', $patientRecord[0]['patient_id'])
                    ->get();
        $searchPhase = $patientPhase[0]->performance_score_id;

        $coordinates = DB::table('us')
                    ->where('zipcode', '=', $addressRecord['address_zip'])
                    ->get();
        
        
        //return $coordinates;
        $tempLat = $coordinates[0]->latitude;
        $tempLong = $coordinates[0]->longitude;
        
        //works

        $fRadius = (float)50;
        $fLatitude = (float)$tempLat;
        $fLongitude = (float)$tempLong;
/*
        $sXprDistance =  "SQRT(POWER(($fLatitude-latitude)*110.7,2)+POWER(($fLongitude-longitude)*75.6,2))";
        $sql = "select trials_test.*, us.zipcode, $sXprDistance AS distance  FROM us, trials_test WHERE $sXprDistance <= '$fRadius' and us.zipcode = trials_test.postal_code ORDER BY distance ASC";
        $radiusResults = DB::select($sql);

*/
$testResults = DB::table("trial_melanoma")
    ->join('us', 'us.zipcode', '=', 'trial_melanoma.postal_code')
    ->select('trial_melanoma.*', 'us.zipcode', DB::raw("6371 * acos(cos(radians(" . $fLatitude . "))
    * cos(radians(us.latitude)) 
    * cos(radians(us.longitude) - radians(" . $fLongitude . ")) 
    + sin(radians(" .$fLatitude. ")) 
    * sin(radians(us.latitude))) AS distance"))
    ->where(DB::raw("6371 * acos(cos(radians(" . $fLatitude . "))
    * cos(radians(us.latitude)) 
    * cos(radians(us.longitude) - radians(" . $fLongitude . ")) 
    + sin(radians(" .$fLatitude. ")) 
    * sin(radians(us.latitude)))"), '<=', $fRadius)
    ->orderBy('distance', 'asc')
    ->limit(100)
    ->get();

    $myArr = ["active", "available", "recruiting", "enrolling by invitation"];
/*
    if ($testResults = "[]") {
        return ["message"=>"Empty Results"];
    }
*/

    foreach($testResults as $record) {
        if (stripos($record->disease_data, $searchSubTerm)) {
            $record->search_result_score = $record->search_result_score+1;
        }
        if (in_array(strtolower($record->trial_status),$myArr)) {
            $record->search_result_score = $record->search_result_score+1;
        }
        if (stripos($record->phase, $searchPhase)) {
            $record->search_result_score = $record->search_result_score+1;
        }
        $array[] =  $record;
    }
    return $array;
    /*
    foreach($testResults as $record) {
        $trialResults = SelectisTrial::find($record->trial_id);
        //return $trialResults;
        $locationResults = SelectisLocation::find($record->location_id);
        //return $locationResults;
        //$record = array($record);
        $record->trial_title = $trialResults->brief_title;
        $record->trial_status = $trialResults->current_trial_status;
        //$record["location_data"] = array($locationResults);
        $record->location_name = $locationResults->name;
        $record->location_address_line_1 = $locationResults->address_line_1;
        $record->location_address_line_2 = $locationResults->address_line_2;
        $record->location_city = $locationResults->city;
        $record->location_state = $locationResults->state;
        $record->location_postal_code = $locationResults->postal_code;
        $record->location_country = $locationResults->country;
        $professionals = DB::connection('pgsql2')->select('select * from trial_professional, trial_professional_ref
        where trial_professional.trial_professional_id = trial_professional_ref.trial_professional_id
        and trial_professional_ref.trial_id = ?',array($record->trial_id));

        $collaborators = DB::connection('pgsql2')->select('select * from collaborator, trial_collaborator_ref
        where collaborator.collaborator_id = trial_collaborator_ref.collaborator_id
        and trial_collaborator_ref.trial_id = ?',array($record->trial_id));

        $contacts = DB::connection('pgsql2')->select('select * from contact, trial_contact_ref
        where contact.contact_id = trial_contact_ref.contact_id
        and trial_contact_ref.trial_id = ?',array($record->trial_id));

        $conditions = DB::connection('pgsql2')->select('select * from trial_disease, trial_disease_ref
        where trial_disease.trial_disease_id = trial_disease_ref.trial_disease_id
        and trial_disease_ref.trial_id = ? limit 3',array($record->trial_id));

        $record->professionals = $professionals;
        $record->collaborators = $collaborators;
        $record->contacts = $contacts;

        $array[] =  $record;
    }
    */
//return $array;
/* this works
 $testResults = DB::table("us")
     ->select("us.zipcode", DB::raw("6371 * acos(cos(radians(" . $fLatitude . "))
     * cos(radians(us.latitude)) 
     * cos(radians(us.longitude) - radians(" . $fLongitude . ")) 
     + sin(radians(" .$fLatitude. ")) 
     * sin(radians(us.latitude))) AS distance"))
     ->where(DB::raw("6371 * acos(cos(radians(" . $fLatitude . "))
     * cos(radians(us.latitude)) 
     * cos(radians(us.longitude) - radians(" . $fLongitude . ")) 
     + sin(radians(" .$fLatitude. ")) 
     * sin(radians(us.latitude)))"), '<=', $fRadius)
     ->paginate(20);

     return $testResults;
*/






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
