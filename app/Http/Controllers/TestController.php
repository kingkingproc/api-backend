<?php

namespace App\Http\Controllers;

use Firebase\JWT\JWK;
use Firebase\JWT\JWT;

use Illuminate\Http\Request;
use App\Models\Trial;
use App\Models\Patient;
use App\Models\PatientDiagnosis;
use App\Models\LkupPatientDiagnosisCancerType;
use Illuminate\Support\Facades\DB;

class TestController extends Controller
{
    public function verifyJasonToken(Request $request) {
        $the_publicKey = config('services.aws.COGNITO_PUBLIC_KEY');
        $token = $request->bearerToken();
        $tokenParts = explode(".", $token);  
        $tokenHeader = base64_decode($tokenParts[0]);
        $tokenPayload = base64_decode($tokenParts[1]);
        $jwtHeader = json_decode($tokenHeader);
        $jwtPayload = json_decode($tokenPayload);

        $the_object = JWT::decode($token,$the_publicKey,['RS256', 'RS256']);

       
        if ( ($the_object->iss=="https://cognito-idp.".config('services.aws.COGNITO_REGION').".amazonaws.com/".config('services.aws.COGNITO_USER_POOL_ID'))
            && ($the_object->aud==config('services.aws.COGNITO_CLIENT_ID'))
            && ($the_object->token_use==config('services.aws.COGNITO_TOKEN'))
            ) {
                return $the_object;
            } else {
                return ["message"=>"Bad Token"];
            }
    }

    public function index()
    {
/*
        $request = request();
        //$the_object = self::verifyJasonToken($request);
            // Get the patient record for the sub
            $token = $request->bearerToken();
            $tokenParts = explode(".", $token);  
            $tokenHeader = base64_decode($tokenParts[0]);
            $tokenPayload = base64_decode($tokenParts[1]);
            $jwtPayload = json_decode($tokenPayload);
            //return $tokenPayload;
        //$patientRecord = patient::where('sub',$the_object->sub)->get();
        $patientRecord = patient::where('sub',$jwtPayload->sub)->get();
        $diagnosisRecord = patientdiagnosis::where('patient_id', $patientRecord[0]['patient_id'])->get();
        $cancerTypeRecord = lkuppatientdiagnosiscancertype::where('cancer_type_id',$diagnosisRecord[0]['cancer_type_id'])->get();
        $searchTerm = $cancerTypeRecord[0]['cancer_type_label'];
        //return $searchTerm;
        //$trials = Trial::where([['brief_summary','LIKE',"%".$searchTerm."%"]])
        //        ->orderBy('brief_title', 'asc')->paginate(20);
        //return array($trials);
*/
        $searchTerm = "Aerodigestive Precancerous Lesions and Malignancies";
        $trials = DB::connection('pgsql2')->select('select disease_name,  trial.trial_id, brief_title, central_contact_name, primary_purpose, 
        current_trial_status, current_trial_status_date, start_date, completion_date,
        eligibility_gender, eligibility_accepts_healthy_volunteers, eligibility_maximum_age,
        eligibility_minimum_age, location.name, address_line_1, address_line_2, city,
        location.state, postal_code, country
        from trial_disease , trial_disease_ref , trial, trial_location_ref, location
        where trial_disease.disease_name = ?
        and trial_disease_ref.trial_disease_id = trial_disease.trial_disease_id
        and trial.trial_id = trial_disease_ref.trial_id
        and trial.trial_id = trial_location_ref.trial_id
        and trial_location_ref.location_id = location.location_id', [$searchTerm]);

        return array($trials);



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
