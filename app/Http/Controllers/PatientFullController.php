<?php

namespace App\Http\Controllers;

use App\Http\Controllers\AuthController;

use Firebase\JWT\JWK;
use Firebase\JWT\JWT;

use App\Models\Patient;
use App\Models\address;
use App\Models\PatientContact;
use App\Models\PatientContactData;
use App\Models\LkupContactDataType;
use App\Models\PatientDiagnosis;
use App\Models\PatientDiagnosisTreatment;
use App\Models\PatientDiagnosisAdditional;
use App\Models\PatientDiagnosisRemoteSite;
use App\Models\SurveyStepOne;
use App\Models\LkupPatientDiagnosisRemoteSite;
use App\Models\LkupPatientDiagnosisTreatment;
use App\Models\LkupPatientDiagnosisAdditional;
use App\Models\LkupPatientDiagnosisBiomarker;

use Illuminate\Http\Request;

class PatientFullController extends Controller
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

        $request = request();
        $the_object = self::verifyJasonToken($request);
        //return $jwtPayload->sub;
        $patientRecord = Patient::where('sub',$the_object->sub)->where('email', $the_object->email)->get();
        if ($patientRecord == "[]") {
            $array['sub'] = $the_object->sub;
            //return json_encode(['patient'=>surveystepone::create(array("['sub'=>$jwtPayload->sub]"))]);
            $tempPatient = Patient::create($array);
            $patientRecord = Patient::find($tempPatient->patient_id);
            $patientRecord = array($patientRecord);
        }
        //return $patientRecord;

        $id = $patientRecord[0]['patient_id'];
        //return $id;
        $patient_array = Patient::find($id);
        $patient_address_array = Patient::find($id)->getAddresses;
        $patient_array['address']=$patient_address_array;
        //return $patient_array;

        $var_array = [];
        $patient_contacts = Patient::find($id)->getPatientContacts;

       foreach ($patient_contacts as $contact){
           //try{
                $contact_type = patientcontact::find($contact->contact_id)->getPatientContactType;
                $contact['contact_type'] = $contact_type->contact_type;
                $paitent_contact_data_col = PatientContact::find($contact->contact_id)->getPatientContactData;
                //return $paitent_contact_data;
                unset($var2_array);
                $var2_array = array();
                foreach ($paitent_contact_data_col as $paitent_contact_data) {
                $paitent_contact_data_type = LkupContactDataType::find($paitent_contact_data->contact_data_type_id);
                $paitent_contact_data['contact_data_type'] = $paitent_contact_data_type->contact_data_type;
                //$contact['contact_data'] = $paitent_contact_data;
                $var2_array[] = $paitent_contact_data;
                }
                $contact['contact_data'] = $var2_array;

                    if (!empty($contact->address_id)) {
                        $contact_address = address::find($contact->address_id);
                        $contact['address']=$contact_address;
                    }
                $var_array[] = $contact;
            //} catch(\Exception $e) {
                //return 'Error Message';
            //}
        }

        $patient_array['contacts']=$var_array;

        $var_array = [];

        $patientdiagnosis = Patient::find($id)->getDiagnosis;
        //return $patientdiagnosis;
        if ($patientdiagnosis == "") {
            return $patient_array;
        }
        $diagnosis_id = $patientdiagnosis->diagnosis_id;

        $array_patient_diagnosis = $patientdiagnosis;
        $array_cancer_type = patientdiagnosis::find($diagnosis_id)->cancer_type;
        $array_cancer_sub_type = patientdiagnosis::find($diagnosis_id)->cancer_sub_type;
        $array_cell_type = patientdiagnosis::find($diagnosis_id)->cell_type;
        $array_stage = patientdiagnosis::find($diagnosis_id)->stage;
        $array_tumor_site = patientdiagnosis::find($diagnosis_id)->tumor_site;
        $array_tumor_size = patientdiagnosis::find($diagnosis_id)->tumor_size;
        $array_perfomance_score = patientdiagnosis::find($diagnosis_id)->performance_score;

        if (!is_null($array_cancer_type)) {$array_patient_diagnosis['cancer_type_label'] = $array_cancer_type->cancer_type_label;}
        if (!is_null($array_cancer_sub_type)) {$array_patient_diagnosis['cancer_sub_type_label'] = $array_cancer_sub_type->cancer_sub_type_label;}
        if (!is_null($array_cell_type)) {$array_patient_diagnosis['cell_type_label'] = $array_cell_type->cell_type_label;}
        if (!is_null($array_stage)) {$array_patient_diagnosis['stage_label'] = $array_stage->stage_label;}
        if (!is_null($array_tumor_site)) {$array_patient_diagnosis['tumor_site_label'] = $array_tumor_site->tumor_site_label;}
        if (!is_null($array_tumor_size)) {$array_patient_diagnosis['tumor_size_label'] = $array_tumor_size->tumor_size_label;}
        if (!is_null($array_perfomance_score)) {$array_patient_diagnosis['performance_score_label'] = $array_perfomance_score->performance_score_label;}

        $patient_array['diagnosis']=$array_patient_diagnosis;
        
        $treatment = patientdiagnosis::find($diagnosis_id)->treatment;
        $additional = patientdiagnosis::find($diagnosis_id)->additional;
        $remote_site = patientdiagnosis::find($diagnosis_id)->remote_site;
        $biomarker = patientdiagnosis::find($diagnosis_id)->biomarker;

        

        foreach ($treatment as $treatment_s){
            $treatment_labels = lkuppatientdiagnosistreatment::find($treatment_s->treatment_id);
            $treatment_labels = array($treatment_labels);
            foreach ($treatment_labels as $treatment_label){
                $treatment_s['treatment_label']=$treatment_label->treatment_label;
            }
            $var_array[]=$treatment_s;
        }
        
        $patient_array['treatments']=$var_array;

        $var_array = [];
        foreach ($additional as $additional_s){
            $additional_labels = lkuppatientdiagnosisadditional::find($additional_s->additional_id);
            $additional_labels = array($additional_labels);
            foreach ($additional_labels as $additional_label){
                $additional_s['additional_label']=$additional_label->additional_label;
            }
            $var_array[]=$additional_s;
        }
        $patient_array['additionals']=$var_array;

        $var_array = [];
        
        foreach ($remote_site as $remote_site_s){
            $remote_site_labels = lkuppatientdiagnosisremotesite::find($remote_site_s->remote_site_id);
            $remote_site_labels = array($remote_site_labels);
            foreach ($remote_site_labels as $remote_site_label){
                $remote_site_s['remote_site_label']=$remote_site_label->remote_site_label;
            }
            $var_array[]=$remote_site_s;
        }
        $patient_array['remote_sites']=$var_array;

        $var_array = [];
        
        foreach ($biomarker as $biomarker_s){
            $biomarker_labels = lkuppatientdiagnosisbiomarker::find($biomarker_s->biomarker_id);
            $biomarker_labels = array($biomarker_labels);
            foreach ($biomarker_labels as $biomarker_label){
                $biomarker_s['biomarker_label']=$biomarker_label->biomarker_label;
            }
            $var_array[]=$biomarker_s;
        }
        $patient_array['biomarkers']=$var_array;

        //return Response(json_encode($patient_array, JSON_PRETTY_PRINT));
        return $patient_array;
    }


    public function show($id)
    {

    }

    public function update(Request $request, $id)
    {
        //
        $request = request();
        $token = $request->bearerToken();
        $tokenParts = explode(".", $token);  
        $tokenHeader = base64_decode($tokenParts[0]);
        $tokenPayload = base64_decode($tokenParts[1]);
        $jwtHeader = json_decode($tokenHeader);
        $jwtPayload = json_decode($tokenPayload);

        $request = $request->all();

        foreach($request as $elem)  {
                $array[] = $elem; 
         }
        foreach($array[2] as $contact){
                $contactarray[] = $contact;
                $patientcontact = patientcontact::find($contactarray[0]["id"]);
                $patientcontact->update($contactarray[0]);
                unset($contactarray);
        }
        foreach($array[3] as $contact_data){
                $contact_dataarray[] = $contact_data;
                $patientcontactdata = patientcontactdata::find($contact_dataarray[0]["id"]);
                $patientcontactdata->update($contact_dataarray[0]);
                unset($contact_dataarray);
        }    

       $patient = patient::find($array[0]["id"]);
       $patient->update($array[0]);
    
       return json_encode(['patient'=>$patient]);
    }


}
