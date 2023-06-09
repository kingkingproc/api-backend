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

class SurveyCompleteController extends Controller
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
        $the_object = self::verifyJasonToken($request);
            // Get the patient record for the sub
            $patientRecord = patient::where('sub',$the_object->sub)->where('email', $the_object->email)->get();

            // put patient request data into array
            $patient_array = $request->only(['user_type', 'name_first', 'name_last', 'name_middle', 'dob_month', 'dob_day', 'dob_year', 'sex']);
            $patient_array['ethnicity_id']=$request['ethnicity'];
            $patient_array['education_level']=$request['education_level'];
            $patient_array['is_medicaid_patient']=$request['is_medicaid_patient'];
            $patient_array['email'] = $the_object->email;
<<<<<<< HEAD
=======
            $patient_array['sub'] = $the_object->sub;
>>>>>>> 288f5c808dcd37af5b33c554afe6e875962c746a
            $patient_array['is_complete'] = (isset($request['is_complete'])?$request['is_complete']:0);
            $patient_array['termsAgreement'] = (isset($request['termsAgreement'])?$request['termsAgreement']:0);
            $patient_array['shareInformation'] = (isset($request['shareInformation'])?$request['shareInformation']:0);
            $patient_array['sendInformation'] = (isset($request['sendInformation'])?$request['sendInformation']:0);
            $patient_array['oncologist'] = (isset($request['oncologist'])?$request['oncologist']:'');
            //put address request data into array
            $address_array['address_city']=$request['city'];
            $address_array['address_state']=$request['state'];
            $address_array['address_zip']=$request['zip_code'];

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
                $patient_array['address_id']=$address['address_id'];

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
