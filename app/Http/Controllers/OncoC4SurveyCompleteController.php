<?php

namespace App\Http\Controllers;

use Firebase\JWT\JWK;
use Firebase\JWT\JWT;

use Illuminate\Http\Request;
use App\Models\Patient;
use App\Models\address;
use App\Models\PatientContact;
use App\Models\PatientContactData;
use App\Models\LkupContactDataType;
use App\Models\PatientDiagnosis;
use App\Models\PatientDiagnosisTreatment;
use App\Models\PatientDiagnosisAdditional;
use App\Models\PatientDiagnosisRemoteSite;

use Illuminate\Support\Facades\DB;

class OncoC4SurveyCompleteController extends Controller
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
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {




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
    public function update(Request $request)
    {
        $request = request();

        $testResults = DB::connection('pgsql2')->select(" 
        with cte_lat_long as (
            select latitude,longitude from us where zipcode = '" . $request['zip_code'] . "'
            )
            , cte_no_location as (
            select trials_nsclc_full.trial_id, MIN(
            6371 * acos(cos(radians(cte_lat_long.latitude))
                    * cos(radians(us.latitude)) 
                    * cos(radians(us.longitude) - radians(cte_lat_long.longitude)) 
                    + sin(radians(cte_lat_long.latitude)) 
                    * sin(radians(us.latitude)))) AS distance
            from cte_lat_long,trials_nsclc_full inner join us on trials_nsclc_full.postal_code = us.zipcode
            group by trials_nsclc_full.trial_id
            ),
            cte_location as (
            select trials_nsclc_full.trial_id, trials_nsclc_full.location_id,
            6371 * acos(cos(radians(cte_lat_long.latitude))
                    * cos(radians(us.latitude)) 
                    * cos(radians(us.longitude) - radians(cte_lat_long.longitude)) 
                    + sin(radians(cte_lat_long.latitude)) 
                    * sin(radians(us.latitude))) AS distance
            from cte_lat_long,trials_nsclc_full inner join us on trials_nsclc_full.postal_code = us.zipcode
            ),
            cte_distinct_location as (
            select cte_no_location.trial_id, cte_no_location.distance, min(cte_location.location_id) as location_id
            from cte_no_location inner join cte_location on cte_no_location.trial_id = cte_location.trial_id
                and cte_no_location.distance = cte_location.distance
            group by cte_no_location.trial_id, cte_no_location.distance
            )
            select cte_distinct_location.trial_id, cte_distinct_location.distance, cte_distinct_location.location_id, 
            trials_nsclc_full.*, us.latitude, us.longitude, 0 as favorite
            from cte_distinct_location
            inner join trials_nsclc_full on cte_distinct_location.trial_id = trials_nsclc_full.trial_id
            and cte_distinct_location.location_id = trials_nsclc_full.location_id
            inner join us on trials_nsclc_full.postal_code = us.zipcode
            where trials_nsclc_full.trial_id = '1807853588'
            order by cte_distinct_location.distance limit 1"
            );

        //return $testResults;

        foreach($testResults as $record) {
            $record->professional_data = json_decode($record->professional_data);
            $record->collaborator_data = json_decode($record->collaborator_data);
            $record->contact_data = json_decode($record->contact_data);
        
            $record->additional_location_data = json_decode($record->additional_location_data, true);
            
            array_multisort(array_column($record->additional_location_data, 'distance'), SORT_ASC, $record->additional_location_data);
            $record->additional_location_data = array_slice($record->additional_location_data, 0, 6);
            $array[] = $record;
        }
        return $array;




        $the_object = self::verifyJasonToken($request);
            // Get the patient record for the sub
            $patientRecord = patient::where('sub',$the_object->sub)->get();

            // put patient request data into array
            // $patient_array = $request->only(['user_type', 'name_first', 'name_last', 'name_middle', 'dob_month', 'dob_day', 'dob_year', 'sex']);
            
            $patient_array['user_type']=(isset($request['user_type'])?$request['user_type']:10);
            $patient_array['name_first']=(isset($request['name_first'])?$request['name_first']:"");
            $patient_array['name_last']=(isset($request['name_last'])?$request['name_last']:"");
            $patient_array['name_middle']=(isset($request['name_middle'])?$request['name_middle']:"");
            $patient_array['dob_month']=(isset($request['dob_month'])?$request['dob_month']:0);
            $patient_array['dob_day']=(isset($request['dob_day'])?$request['dob_day']:0);
            $patient_array['dob_year']=(isset($request['dob_year'])?$request['dob_year']:0);
            $patient_array['sex']=(isset($request['sex'])?$request['sex']:"");
            
            $patient_array['ethnicity_id']=(isset($request['ethnicity'])?$request['ethnicity']:0);
            $patient_array['education_level']=(isset($request['education_level'])?$request['education_level']:0);
            $patient_array['is_medicaid_patient']=(isset($request['is_medicaid_patient'])?$request['is_medicaid_patient']:0);
            $patient_array['is_complete'] = (isset($request['is_complete'])?$request['is_complete']:0);
            $patient_array['termsAgreement'] = (isset($request['termsAgreement'])?$request['termsAgreement']:0);
            $patient_array['shareInformation'] = (isset($request['shareInformation'])?$request['shareInformation']:0);
            $patient_array['sendInformation'] = (isset($request['sendInformation'])?$request['sendInformation']:0);
            //put address request data into array
            $address_array['address_city']=(isset($request['city'])?$request['city']:"");
            $address_array['address_state']=(isset($request['state'])?$request['state']:"");
            $address_array['address_zip']=(isset($request['zip_code'])?$request['zip_code']:"");

            // Check if there is a patient that exist for the sub
            if (count($patientRecord)) {
                //patient record exist, so update the patient
                //first check if there is an address for the patient
                if (empty($patientRecord[0]['address_id'])) {
                    //address id is empty, so create the address
                    $address = address::create($address_array);
                } else {
                    //address id is not empty, so update that address
                    $address = address::find($patientRecord[0]['address_id']);
                    $address->update($address_array);
                }
                //place the new or updated address_id into the patient array
                $patient_array['address_id']=$address['address_id'];
                $patient_array['patient_id']=$patientRecord[0]['patient_id'];
                $patient = patient::find($patientRecord[0]['patient_id']);
                $patient->update($patient_array);
            } else {
                // patient record does not exist, so create patient
                //first create the address
                $address = address::create($address_array);

                //place the new address_id into the patient array
                $patient_array['address_id']=$address[0]['address_id'];

                //create the patient
                $patient = patient::create($patient_array);
            }
            
            //put diagnosis request data into array
            $diagnosis_array['patient_id']=$patient['patient_id'];
            $diagnosis_array['cancer_type_id']=$request['diagnosis'];
            $diagnosis_array['stage_id']=$request['stage'];

            if (is_array($request['diagnosis_sub'])) {
                $diagnosis_array['cancer_sub_type_id']=$request['diagnosis_sub']['key'];
            } else {
                $diagnosis_array['cancer_sub_type_id']= NULL;
            }

            $diagnosis_array['performance_score_id']=$request['score'];

            // get the diagnosis record for the patient
            $diagnosisRecord = PatientDiagnosis::where('patient_id',$patient['patient_id'])->get();
           
            // Check if there is a dianosis  tha exist for the patient
            if (count($diagnosisRecord)) {
                // diagnosis exists, so update
                $diagnosis = PatientDiagnosis::find($diagnosisRecord[0]['diagnosis_id']);
                $diagnosis->update($diagnosis_array);
            } else{
                // diagnosis does not exist so create
                $diagnosis = PatientDiagnosis::create($diagnosis_array);
            }

            $diagnosis_id = $diagnosis['diagnosis_id'];

            $additionals_to_remove = PatientDiagnosisAdditional::where('diagnosis_id',$diagnosis_id)->get();
            foreach ($additionals_to_remove as $to_remove) {
                PatientDiagnosisAdditional::destroy($to_remove['id']);
            }

            foreach ($request['comorbidities'] as $to_add) {
                //PatientDiagnosisAdditional::destroy($to_remove['id']);
                $additional_array['diagnosis_id']=$diagnosis_id;
                $additional_array['additional_id']=$to_add;
                PatientDiagnosisAdditional::create($additional_array);
            }
            return $request;
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
