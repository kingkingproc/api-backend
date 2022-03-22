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

class SpecialistController extends Controller
{
    public function index()
    {

        $request = request();
        $the_object = Helper::verifyJasonToken($request);
        $patientRecord = patient::where('sub',$the_object->sub)->get();
        $addressRecord = address::find($patientRecord[0]['address_id']);
        $coordinates = DB::table('us')
                    ->where('zipcode', '=', $addressRecord['address_zip'])
                    //->where('zipcode', '=', '53534')
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
        'specialists_melanoma.trial_count_adj', 'specialists_melanoma.coe_flag',
        'us.zipcode', DB::raw("6371 * acos(cos(radians(" . $fLatitude . "))
        * cos(radians(us.latitude)) 
        * cos(radians(us.longitude) - radians(" . $fLongitude . ")) 
        + sin(radians(" .$fLatitude. ")) 
        * sin(radians(us.latitude))) AS distance"))
        ->orderBy('distance', 'asc')
        ->limit(100)
        ->get();

        //return $testResults;
        $providerList = [];

        foreach($testResults as $record) {
            if (in_array($record->provider_id, $providerList)) {
                continue;
            }
            array_push($providerList,$record->provider_id);
            $provider = DB::connection('pgsql2')->select('select last_name from provider
            where provider.provider_id = ?',array($record->provider_id));

            $location = DB::connection('pgsql2')->select('select * from location
            where location.location_id = ?',array($record->location_id));

            $all_location = DB::connection('pgsql2')->select('select * from location
            where location.location_id in (select location_id
                from provider_location_ref where provider_id = ?
                and location_id <> ?)',array($record->provider_id,$record->location_id));

            $all_open_trials = DB::connection('pgsql2')->select ("select t.nci_id, t.nct_id, tpr.role, t.status_mapped from
            trial t
            inner join trial_professional_ref tpr on tpr.trial_id = t.trial_id
            inner join provider_trial_professional_ref ptpr on ptpr.trial_professional_id = tpr.trial_professional_id
            inner join provider p on p.provider_id = ptpr.provider_id
            where p.provider_id = ?",array($record->provider_id));
            
            $specialties = DB::connection('pgsql2')->select("select 'pri_specialty' as spec_type, specialty_name from provider_primary_specialty_ref as ppsr
            inner join specialty as s on ppsr.specialty_id = s.specialty_id
            where ppsr.provider_id = ?
            union
            select 'sec_specialty' as spec_type, specialty_name from provider_secondary_specialty_ref as pssr
            inner join specialty as s on pssr.specialty_id = s.specialty_id
            where pssr.provider_id = ?",array($record->provider_id,$record->provider_id));

           // $metrics = DB::connection('pgsql2')->select('select * from provider_metric_view
           // where provider_metric_view.provider_id = ?',array($record->provider_id));

           $record->coe_flag = var_export($record->coe_flag, true);
           if ($record->coe_flag === "true") {$record->coe_flag = "Yes";}
           if ($record->coe_flag === "false") {$record->coe_flag = "No";}
          //  $record->provider = $provider;
            $record->specialties = $specialties;
         //   $record->provider['metrics'] = $metrics;
         //   $record->location = $location;
            $record->location_name = $location[0]->location_name;
            $record->location_address_line_1 = $location[0]->address_line_1;
            $record->location_address_line_2 = $location[0]->address_line_2;
            $record->location_city = $location[0]->city;
            $record->location_state = $location[0]->state;
            $record->location_postal_code = $location[0]->postal_code;
            $record->location_country = $location[0]->country;
            $record->last_name = $provider[0]->last_name;
            $record->search_result_score = 0.00;
            $record->all_location = $all_location;
            $record->all_trials = $all_open_trials;

            $trial_count_score = 0.00;
            $h_count_score = 0.00;

            if ($record->trial_count_adj == 0) {
                $trial_count_score = 1.00;
            }
            if ($record->trial_count_adj == 1) {
                $trial_count_score = 2.00;
            }
            if ($record->trial_count_adj == 2) {
                $trial_count_score = 3.00;
            }
            if ($record->trial_count_adj == 3) {
                $trial_count_score = 3.00;
            }
            if ($record->trial_count_adj == 4) {
                $trial_count_score = 4.00;
            }
            if ($record->trial_count_adj == 5) {
                $trial_count_score = 4.00;
            }
            if ($record->trial_count_adj == 6) {
                $trial_count_score = 4.00;
            }
            if ($record->trial_count_adj > 6) {
                $trial_count_score = 5.00;
            }
            if ($record->h_index_adj <= 2.00) {
                $h_count_score = 1.00;
            }
            if ($record->h_index_adj > 2.00 and $record->h_index_adj <= 6.00) {
                $h_count_score = 2.00;
            }
            if ($record->h_index_adj > 6.00 and $record->h_index_adj <= 20.00) {
                $h_count_score = 3.00;
            }
            if ($record->h_index_adj > 20.00 and $record->h_index_adj <= 63.00) {
                $h_count_score = 4.00;
            }
            if ($record->h_index_adj > 63.00) {
                $h_count_score = 5.00;
            }

            $record->search_result_score = ($trial_count_score + $h_count_score)/2;
            //$record->search_result_score = ''<img src="bar.png">'';
            $array[] =  $record;
        }

        return $array;
    }
}
