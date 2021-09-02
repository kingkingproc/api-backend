<?php

namespace App\Http\Controllers;

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
        //Put request
            //Code to get the sub from a JWT (need to figure out how to verify against Cognito)
            $request = request();
            $token = $request->bearerToken();
            $tokenParts = explode(".", $token);  
            $tokenHeader = base64_decode($tokenParts[0]);
            $tokenPayload = base64_decode($tokenParts[1]);
            $jwtHeader = json_decode($tokenHeader);
            $jwtPayload = json_decode($tokenPayload);


/*
            if ( 
            ($jwtPayload->sub==$jwtPayload->username)
            && ($jwtPayload->iss=="https://cognito-idp.".$_ENV["AWS_COGNITO_REGION"].".amazonaws.com/".$_ENV["AWS_COGNITO_USER_POOL_ID"])
            && ($jwtPayload->client_id==$_ENV["AWS_COGNITO_CLIENT_ID"])
            && ($jwtPayload->token_use==$_ENV["AWS_COGNITO_TOKEN"])
            && ($jwtPayload->scope==$_ENV["AWS_COGNITO_SCOPE"])
            && in_array($jwtHeader->kid,json_decode($_ENV["AWS_COGNITO_KEYS"]))
            ) {
                //return ["message"=>"Good Token"];
            } else {
                return ["message"=>"Bad Token"];
            }
*/
            // END Code to get the sub from a JWT

            // Get the patient record for the sub
            $patientRecord = patient::where('sub',$jwtPayload->sub)->get();

            // put patient request data into array
            $patient_array = $request->only(['user_type', 'name_first', 'name_last', 'name_middle', 'dob_month', 'dob_day', 'dob_year', 'sex']);
            $patient_array['ethnicity_id']=$request['ethnicity'];
            $patient_array['is_complete']=$request['is_complete'];
            $patient_array['is_complete'] = (isset($request['is_complete'])?$request['is_complete']:0);

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
                $patient_array['address_id']=$address[0]['address_id'];

                //create the patient
                $patient = patient::create($patient_array);
            }
            
            //put diagnosis request data into array
            $diagnosis_array['cancer_type_id']=$request['diagnosis'];
            $diagnosis_array['stage_id']=$request['stage']; 

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
