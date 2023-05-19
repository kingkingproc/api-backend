<?php

namespace App\Http\Controllers;

use Firebase\JWT\JWK;
use Firebase\JWT\JWT;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;

use App\Models\Patient;
use App\Models\PatientDiagnosis;
use App\Models\PatientDiagnosisAdditional;
use App\Models\PatientDiagnosisRemoteSite;
use App\Models\PatientDiagnosisTreatment;
use App\Models\PatientDiagnosisBiomarker;

class PatientProfileDiagnosisController extends Controller
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

            //put diagnosis request data into array
            //$diagnosis_array['tumor_site_id']=$request['tumor_site'];
            //$diagnosis_array['tumor_size_id']=$request['tumor_size'];
            //$diagnosis_array['pathology']=$request['pathology'];
            //$diagnosis_array['cell_type_id']=$request['cell_type'];
            $diagnosis_array['dod_month']=$request['dod_month'];
            $diagnosis_array['dod_day']=$request['dod_day'];
            $diagnosis_array['dod_year']=$request['dod_year'];
            $diagnosis_array['performance_score_id']=$request['performance_score'];
            $diagnosis_array['stage_id']=$request['cancer_stage'];
            $diagnosis_array['cancer_type_id']=$request['cancer_type']['key'];
            $diagnosis_array['cancer_sub_type_id']=$request['cancer_sub_type']['key'];
            $diagnosis_array['is_brain_tumor']=$request['is_brain_tumor'];
            $diagnosis_array['is_metastatic']=$request['is_metastatic'];
            $diagnosis_array['is_treatment_started']=$request['is_treatment_started'];
            $diagnosis_array['is_biomarker_started']=$request['is_biomarker_started'];
            $diagnosis_array['patient_id']=$patientRecord[0]['patient_id'];

            $diagnosisRecord = patientdiagnosis::where('patient_id',$patientRecord[0]['patient_id'])->get();

            if (count($diagnosisRecord)) {
                $diagnosisRecord_id = $diagnosisRecord[0]['diagnosis_id'];
                $diagnosis = patientdiagnosis::find($diagnosisRecord_id);
                $diagnosis->update($diagnosis_array);
            }
            else {
                $diagnosis = patientdiagnosis::create($diagnosis_array);
                $diagnosisRecord_id = $diagnosis->diagnosis_id;
            }

            DB::table('patient_diagnosis_additionals')->where('diagnosis_id',$diagnosisRecord_id)->delete();
            //DB::table('patient_diagnosis_remote_sites')->where('diagnosis_id',$diagnosisRecord_id)->delete();
            DB::table('patient_diagnosis_treatments')->where('diagnosis_id',$diagnosisRecord_id)->delete();
            DB::table('patient_diagnosis_biomarkers')->where('diagnosis_id',$diagnosisRecord_id)->delete();

            //$remote_sites = $request['remote_sites'];
            //foreach ($remote_sites as $remote_site) {
            //    $tempArray = array();
            //    $tempArray['diagnosis_id'] = $diagnosisRecord_id;
            //    $tempArray['remote_site_id'] = $remote_site['key'];
            //    $tempCollection = patientdiagnosisremotesite::create($tempArray);
            //}

            $prior_treatments = $request['prior_treatments'];
            foreach ($prior_treatments as $prior_treatment) {
                $tempArray = array();
                $tempArray['diagnosis_id'] = $diagnosisRecord_id;
                $tempArray['treatment_id'] = $prior_treatment['key'];
                $tempCollection = patientdiagnosistreatment::create($tempArray);
            }

            $comorbidities = $request['comorbidities'];
            foreach ($comorbidities as $additional) {
                $tempArray = array();
                $tempArray['diagnosis_id'] = $diagnosisRecord_id;
                $tempArray['additional_id'] = $additional['key'];
                $tempCollection = patientdiagnosisadditional::create($tempArray);
            }

            $biomarkers = $request['biomarkers'];
            foreach ($biomarkers as $biomarker) {
                $tempArray = array();
                $tempArray['diagnosis_id'] = $diagnosisRecord_id;
                $tempArray['biomarker_id'] = $biomarker['key'];
                $tempCollection = patientdiagnosisbiomarker::create($tempArray);
            }

            $deleted = DB::table('prescreen_response')->where('patient_id', '=', $patientRecord[0]['patient_id'])->delete();
            $secondDeleted = DB::table('prescreen_patient_ref')->where('patient_id', '=', $patientRecord[0]['patient_id'])->delete();

            return $request;
    }

}
