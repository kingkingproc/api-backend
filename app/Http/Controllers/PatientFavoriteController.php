<?php

namespace App\Http\Controllers;

use App\Helper\Helper;

use Illuminate\Http\Request;
use App\Models\Patient;
use App\Models\PatientFavorite;

use Illuminate\Support\Facades\DB;

class PatientFavoriteController extends Controller
{
    public function index()
    {
        $request = request();
        $the_object = Helper::verifyJasonToken($request);
        $patientRecord = patient::where('sub',$the_object->sub)->get();

        //return $request;
        //return $request["favs"];
        //return $request["type"];

        //return $patientRecord[0]['patient_id'];
        //return $patientRecord[0]['sub'];

        $array['patient_id'] = $patientRecord[0]['patient_id'];
        $array['sub'] = $patientRecord[0]['sub'];
        $array['type'] = $request["type"];

        $testResults = DB::connection('pgsql')->delete("delete from patient_favorites where 
            patient_id = " . $array['patient_id'] . "
            and sub = '" . $array['sub'] . "'");

        foreach($request["favs"] as $favorite) {
            $favorites = explode("_", $favorite);
            $array['type_id'] =  $favorites[1];
            $array['location_id'] =  $favorites[2];
            PatientFavorite::create($array);
        }

        return $request;
    }

}
