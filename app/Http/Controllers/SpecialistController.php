<?php

namespace App\Http\Controllers;

use Firebase\JWT\JWK;
use Firebase\JWT\JWT;

use Illuminate\Http\Request;
use App\Models\Patient;
use App\Models\PatientDiagnosis;
use App\Models\LkupPatientDiagnosisCancerType;
use App\Models\LkupPatientDiagnosisCancerSubType;
use App\Models\address;

use Illuminate\Support\Facades\DB;

class SpecialistController extends Controller
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
        //$the_object = self::verifyJasonToken($request);
        //$patientRecord = patient::where('sub',$the_object->sub)->get();
        //$addressRecord = address::find($patientRecord[0]['address_id']);
        $coordinates = DB::table('us')
                    //->where('zipcode', '=', $addressRecord['address_zip'])
                    ->where('zipcode', '=', '53534')
                    ->get();
        
        
        //return $coordinates;
        $tempLat = $coordinates[0]->latitude;
        $tempLong = $coordinates[0]->longitude;
        
        //works

        $fRadius = (float)50;
        $fLatitude = (float)$tempLat;
        $fLongitude = (float)$tempLong;

        $testResults = DB::table("specialists_melanoma")
        ->join('us', 'us.zipcode', '=', 'specialists_melanoma.postal_code')
        ->select('specialists_melanoma.provider_id', 'specialists_melanoma.location_id', 
        'specialists_melanoma.provider_name', 'specialists_melanoma.publications_adj',
        'specialists_melanoma.max_citations_adj', 'specialists_melanoma.h_index_adj',
        'specialists_melanoma.trial_count_adj',
        'us.zipcode', DB::raw("6371 * acos(cos(radians(" . $fLatitude . "))
        * cos(radians(us.latitude)) 
        * cos(radians(us.longitude) - radians(" . $fLongitude . ")) 
        + sin(radians(" .$fLatitude. ")) 
        * sin(radians(us.latitude))) AS distance"))
        ->orderBy('distance', 'asc')
        ->limit(100)
        ->get();

        //return $testResults;

        foreach($testResults as $record) {
            //$provider = DB::connection('pgsql2')->select('select * from provider
            //where provider.provider_id = ?',array($record->provider_id));

            $location = DB::connection('pgsql2')->select('select * from location
            where location.location_id = ?',array($record->location_id));

            $specialties = DB::connection('pgsql2')->select("select 'pri_specialty' as spec_type, specialty_name from provider_primary_specialty_ref as ppsr
            inner join specialty as s on ppsr.specialty_id = s.specialty_id
            where ppsr.provider_id = ?
            union
            select 'sec_specialty' as spec_type, specialty_name from provider_secondary_specialty_ref as pssr
            inner join specialty as s on pssr.specialty_id = s.specialty_id
            where pssr.provider_id = ?",array($record->provider_id,$record->provider_id));

           // $metrics = DB::connection('pgsql2')->select('select * from provider_metric_view
           // where provider_metric_view.provider_id = ?',array($record->provider_id));


          //  $record->provider = $provider;
            $record->specialties = $specialties;
         //   $record->provider['metrics'] = $metrics;
            $record->location = $location;
            $array[] =  $record;
        }

        return $array;
    }
}
