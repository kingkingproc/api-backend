<?php

namespace App\Http\Controllers;

use App\Helper\Helper;

use Illuminate\Http\Request;
use App\Models\Patient;
use App\Models\PatientDiagnosis;
use App\Models\LkupPatientDiagnosisCancerType;
use App\Models\LkupPatientDiagnosisCancerSubType;
use App\Models\address;


use Illuminate\Support\Facades\DB;

function strpos_arr($haystack, $needle) {
    if(!is_array($needle)) $needle = array($needle);
    foreach($needle as $what) {
        if(($pos = strpos($haystack, $what))!==false) return $pos;
    }
    return false;
}

class TrialDetailController extends Controller
{
    public function index()
    {

        $request = request();
        $the_object = Helper::verifyJasonToken($request);

        $favoriteWhole = $request["payload"];

        foreach($favoriteWhole as $favorite) {
            $favorites = explode("_", $favorite);
            $trialArray[] =  $favorites[1];
            $locationArray[] =  $favorites[2];
        }

        $testResults = DB::connection('pgsql2')->select(" 
                        select * from trials_additional_data
                            where trials_additional_data.trial_id IN('".implode("','",$trialArray)."')
                            and trials_additional_data.location_id  IN('".implode("','",$locationArray)."')
                            "
                );
      

        $trialList = [];


        foreach($testResults as $record) {
            if (in_array($record->trial_id, $trialList)) {
                continue;
            }
            array_push($trialList,$record->trial_id); 


            $record->professional_data = json_decode($record->professional_data);
            $record->contact_data = json_decode($record->contact_data);
            $record->primary_purpose = ucwords($record->primary_purpose);

            $locationResults = DB::connection('pgsql2')->select(" 
                select 
                location.location_id,
                COALESCE(s.location_standard_name,location.location_name) location_name,
                COALESCE(s.location_standard_address_line_1,location.address_line_1) address_line_1,
                location.address_line_2,
                COALESCE(s.location_city,location.city) city,
                location.state,
                COALESCE(s.location_zip_code,location.postal_code) postal_code,
                location.country,
                location.location_source,
                location.location_contact_email,
                location.location_contact_last_name,
                location.location_contact_phone,
                location.location_contact_phone_ext,
                location.location_contact_backup_email,
                location.location_contact_backup_last_name
                from
                location inner join trials_additional_locations 
                    on location.location_id = trials_additional_locations.additional_location_id
                left join location_location_standard_ref r 
                    on cast(trials_additional_locations.additional_location_id as integer) = r.location_id
                left join location_standard s 
                    on s.location_standard_id = r.location_standard_id
                where trials_additional_locations.base_trial_id = '". $record->trial_id ."'
                and trials_additional_locations.base_location_id = '". $record->location_id ."'
                ");

            if ($record->phase != null) {
                $record->phase = preg_replace("/[^0-9,]/", "", $record->phase );
                $record->phase = "[" . $record->phase . "]";
            } else {
                $record->phase = "[0]";
            }



            //$record->additional_location_data = json_decode($record->additional_location_data, true);
            $record->additional_location_data = json_decode(json_encode($locationResults), true);
            //array_multisort(array_column($record->additional_location_data, 'distance'), SORT_ASC, $record->additional_location_data);
            //$record->additional_location_data = array_slice($record->additional_location_data, 0, 5);
            




            $array[] = $record;
        }
        return $array;
    }
}
