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

class PatientProfileDoctersController extends Controller
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
            $patientRecord = patient::where('sub',$the_object->sub)->get();

        $patientId = $patientRecord[0]['patient_id'];

        $contacts = DB::table('patient_contacts')
                ->where('patient_id', '=', $patientId)
                ->whereIn('contact_type_id', [3,4,5,6])
                ->get();


        foreach ($contacts as $contact) {
            DB::table('patient_contact_data')->where('contact_id', '=', $contact->contact_id)->delete();
            DB::table('addresses')->where('address_id', '=', $contact->address_id)->delete();
            DB::table('patient_contacts')->where('contact_id', '=', $contact->contact_id)->delete();
        }
        
        //looping through the request doctors
        $doctor_array = $request->all();
        foreach ($doctor_array as $doctor) {
            //first create the address record
            //put address request data into array
            $address_array['address_1']=$doctor['address_1'];
            $address_array['address_2']=$doctor['address_2'];
            $address_array['address_city']=$doctor['city'];
            $address_array['address_state']=$doctor['state'];
            $address_array['address_zip']=$doctor['zip_code'];
            
            $addressRecord = address::create($address_array);

            //now create contact record... populate with address id just created
            $contact_array['address_id'] = $addressRecord->address_id;
            $contact_array['patient_id'] = $patientId;
            $contact_array['contact_name'] = $doctor['name'];
            $contact_array['contact_type_id'] = 3;

            $doctorContact = patientcontact::create($contact_array);
            $doctorContact_id = $doctorContact->contact_id;

            $contact_data_array['contact_id'] = $doctorContact_id;
            $contact_data_array['contact_data_type_id'] = 5;
            $contact_data_array['contact_data'] = $doctor['email'];

            $doctorContactData = patientcontactdata::create($contact_data_array);

            //check for primary contact data in request
            if ($doctor['primary_contact_data_type'] || $doctor['primary_contact_data']) {
                unset($contact_array);
                unset($contact_data_array);
                $contact_array['patient_id'] = $patientId;
                $contact_array['contact_type_id'] = 4;
                $primaryContact = patientcontact::create($contact_array);
                $primaryContact_id = $primaryContact->contact_id;

                $contact_data_array['contact_id'] = $primaryContact_id;
                $contact_data_array['contact_data_type_id'] = $doctor['primary_contact_data_type'];
                $contact_data_array['contact_data'] = $doctor['primary_contact_data'];

                $primaryContactData = patientcontactdata::create($contact_data_array);
            }

            //check for secondary contact data in request
            if ($doctor['secondary_contact_data_type'] || $doctor['secondary_contact_data']) {
                unset($contact_array);
                unset($contact_data_array);
                $contact_array['patient_id'] = $patientId;
                $contact_array['contact_type_id'] = 5;
                $secondaryContact = patientcontact::create($contact_array);
                $secondaryContact_id = $secondaryContact->contact_id;

                $contact_data_array['contact_id'] = $secondaryContact_id;
                $contact_data_array['contact_data_type_id'] = $doctor['secondary_contact_data_type'];
                $contact_data_array['contact_data'] = $doctor['secondary_contact_data'];

                $secondaryContactData = patientcontactdata::create($contact_data_array);
            }

            //check for other contact data in request
            if ($doctor['other_contact_data_type'] || $doctor['other_contact_data']) {
                unset($contact_array);
                unset($contact_data_array);
                $contact_array['patient_id'] = $patientId;
                $contact_array['contact_type_id'] = 6;
                $additionalContact = patientcontact::create($contact_array);
                $additionalContact_id = $additionalContact->contact_id;

                $contact_data_array['contact_id'] = $additionalContact_id;
                $contact_data_array['contact_data_type_id'] = $doctor['other_contact_data_type'];
                $contact_data_array['contact_data'] = $doctor['other_contact_data'];

                $additionalContactData = patientcontactdata::create($contact_data_array);
            }
        }

    return $doctor_array;    
    }

}
