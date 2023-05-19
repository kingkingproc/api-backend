<?php

namespace App\Http\Controllers;

use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\Patient;
use App\Models\address;
use App\Models\PatientContact;
use App\Models\PatientContactData;
use App\Models\LkupContactDataType;

class PatientProfileController extends Controller
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
<<<<<<< HEAD

            $patient_array['email']=$the_object->email;
=======
            $patient_array['email'] = $the_object->email;
            $patient_array['sub'] = $the_object->sub;
            // $patient_array['email']=(isset($request['email'])?$request['email']:"");
>>>>>>> 288f5c808dcd37af5b33c554afe6e875962c746a
            $patient_array['sendInformation']=(isset($request['sendInformation'])?$request['sendInformation']:'false');

            //$patient_array['is_complete'] = (isset($request['is_complete'])?$request['is_complete']:0);

            //put address request data into array
            $address_array['address_1']=$request['address_1'];
            $address_array['address_2']=$request['address_2'];
            $address_array['address_city']=$request['city'];
            $address_array['address_state']=$request['state'];
            $address_array['address_zip']=$request['zip_code'];

            $address = address::find($patientRecord[0]['address_id']);
            if ($address)
                {
                    $address->update($address_array);
                }
            else
                {
                    $address = address::create($address_array);
                    $patient_array['address_id'] = $address->address_id;
                }
            
            $patient = patient::find($patientRecord[0]['patient_id']);
            $patient->update($patient_array);


            //primary contact
            if ($request['primary_contact_data'] == "") {
                //delete any primary contact in DB
                //$deleted = DB::delete('delete from users');
            } else {
                $primaryContact = patientcontact::where('contact_type_id', '=', '1')
                                                ->where('patient_id', '=', $patientRecord[0]['patient_id'])->get();
                
                if ($primaryContact->isEmpty())
                    {
                        $contact_array['contact_type_id']='1';
                        $contact_array['patient_id']=$patientRecord[0]['patient_id'];
                        $primaryContact = patientcontact::create($contact_array);
                        $primaryContact_id = $primaryContact->contact_id;
                    }
                else
                    {
                        $primaryContact_id = $primaryContact[0]['contact_id'];
                    }
                
                $contact_data_array['contact_id'] = $primaryContact_id;
                $contact_data_array['contact_data_type_id'] = $request['primary_contact_data_type'];
                $contact_data_array['contact_data'] = $request['primary_contact_data'];

                $primaryContactData = patientcontactdata::where('contact_id', '=', $primaryContact_id)->get();

                if ($primaryContactData->isEmpty())
                    {
                        $primaryContactData = patientcontactdata::create($contact_data_array);
                    }
                else
                    {
                        $tempdata = patientcontactdata::find($primaryContactData[0]['patient_contact_data_id']);
                        $tempdata->update($contact_data_array);
                    }
            }
            //secondary contact
            if ($request['secondary_contact_data'] == "") {
                // delete any secondary contact info from DB
            } else {
                $secondaryContact = patientcontact::where('contact_type_id', '=', '2')
                                                ->where('patient_id', '=', $patientRecord[0]['patient_id'])->get();
                
                if ($secondaryContact->isEmpty())
                    {
                        $contact_array['contact_type_id']='2';
                        $contact_array['patient_id']=$patientRecord[0]['patient_id'];
                        $secondaryContact = patientcontact::create($contact_array);
                        $secondaryContact_id = $secondaryContact->contact_id;
                    }
                else
                    {
                        $secondaryContact_id = $secondaryContact[0]['contact_id'];
                    }
                
                $contact_data_array['contact_id'] = $secondaryContact_id;
                $contact_data_array['contact_data_type_id'] = $request['secondary_contact_data_type'];
                $contact_data_array['contact_data'] = $request['secondary_contact_data'];

                $secondaryContactData = patientcontactdata::where('contact_id', '=', $secondaryContact_id)->get();
                
                if ($secondaryContactData->isEmpty())
                    {
                        $secondaryContactData = patientcontactdata::create($contact_data_array);
                    }
                else
                    {
                        $tempdata = patientcontactdata::find($secondaryContactData[0]['patient_contact_data_id']);
                        $tempdata->update($contact_data_array);
                    }
            }
        return $request;
    }

}
