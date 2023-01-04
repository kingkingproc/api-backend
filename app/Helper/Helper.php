<?php

namespace App\Helper;

use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class Helper
{
    public static function verifyJasonToken(Request $request) {
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


    public static function patientPrescreenStatus($patient_id) {
        $testResults = DB::connection('pgsql')->select("
        Select 
        ptr.trial_id,
        COALESCE((select ppr.patient_eligible from prescreen_patient_ref ppr where ptr.prescreen_id = ppr.prescreen_id and ppr.patient_id = '" . $patient_id . "'),'show_prescreen') as patient_eligible
        from prescreen_trial_ref ptr
        ");

        return $testResults;
    }

    public static function getPrescreenTrialList() {
        $testResults = DB::connection('pgsql')->select("
            select distinct trial_id from prescreen_trial_ref
        ");
        foreach ($testResults as $trialId) {
            $array[] = $trialId->trial_id;
        }

        return $array;
    }
}