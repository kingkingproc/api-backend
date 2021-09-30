<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TestTrialLoadController extends Controller
{
    public function index() {
    /*
        $baseData = DB::connection('pgsql2')->select("select distinct location.postal_code,trial_location_ref.trial_id, trial_location_ref.location_id
        from location,trial_location_ref,trial_disease_ref,trial_disease
        where location.location_id = trial_location_ref.location_id
        and trial_location_ref.trial_id = trial_disease_ref.trial_id
        and trial_disease.trial_disease_id = trial_disease_ref.trial_disease_id
        and lower(trial_disease.disease_name) like '%melanoma%'
        and lower(trial_disease.disease_name) not like '%non-melanoma%'
        and lower(trial_disease.disease_name) not like '%nonmelanoma%'
        and length(location.postal_code) = 5");

        foreach($baseData as $data) {
            $zip = $data->postal_code;
            $trial = $data->trial_id;
            $location = $data->location_id;
            DB::insert('insert into trial_melanoma (postal_code, trial_id, location_id) values (?, ?, ?)',[$zip,$trial,$location]);
        //return $data->postal_code;
 
        $baseData = DB::connection('pgsql2')->select("select trial_id, brief_title, current_trial_status from trial");

        foreach($baseData as $data) {
            $trial = $data->trial_id;
            $trial_title = $data->brief_title;
            $trial_status = $data->current_trial_status;

            //$affected = DB::update(
            //    "update trial_melanoma set trial_title = '".$trial_title."', trial_status = '".$trial_status."' where trial_id = '".$trial."'");
            $affected = DB::table('trial_melanoma')
            ->where('trial_id', $trial)
            ->update(['trial_title' => $trial_title]);    
      
    $baseData = DB::connection('pgsql2')->select("select trial_id, current_trial_status from trial");

    foreach($baseData as $data) {
        $trial = $data->trial_id;
        $trial_status = $data->current_trial_status;

        $affected = DB::table('trial_melanoma')
        ->where('trial_id', $trial)
        ->update(['trial_status' => $trial_status]); 
        }
    
    $baseData = DB::connection('pgsql2')->select("select trial_id,
    json_agg(json_build_array(name,affiliation)) as professional_data
    from trial_professional, trial_professional_ref
    where trial_professional.trial_professional_id = trial_professional_ref.trial_professional_id
    group by trial_id");

    foreach($baseData as $data) {
        $trial = $data->trial_id;
        $professional_data = $data->professional_data;

        $affected = DB::table('trial_melanoma')
        ->where('trial_id', $trial)
        ->update(['professional_data' => $professional_data]); 
        }
    

    $baseData = DB::connection('pgsql2')->select("with cte_all as (
        select distinct trial_disease_ref.trial_id,
        lower(trial_disease.disease_name) as disease_name
        from trial_disease_ref, trial_disease 
        where trial_disease_ref.trial_disease_id = trial_disease.trial_disease_id
        )
        select trial_id,
        json_agg(json_build_array(disease_name)) as disease_data
        from cte_all
        group by trial_id");

    foreach($baseData as $data) {
        $trial = $data->trial_id;
        $disease_data = $data->disease_data;

        $affected = DB::table('trial_melanoma')
        ->where('trial_id', $trial)
        ->update(['disease_data' => $disease_data]); 
        }


        
        $baseData = DB::connection('pgsql2')->select("select trial_id,
        json_agg(json_build_array(agency,agency_class)) as collaborator_data
        from collaborator, trial_collaborator_ref
        where collaborator.collaborator_id = trial_collaborator_ref.collaborator_id
        group by trial_id");
    
        foreach($baseData as $data) {
            $trial = $data->trial_id;
            $collaborator_data = $data->collaborator_data;
    
            $affected = DB::table('trial_melanoma')
            ->where('trial_id', $trial)
            ->update(['collaborator_data' => $collaborator_data]); 
            }
        */
        $baseData = DB::connection('pgsql2')->select("select trial_id,
        json_agg(json_build_array(contact_name,contact_phone,contact_email)) as contact_data
        from contact, trial_contact_ref
        where contact.contact_id = trial_contact_ref.contact_id
        group by trial_id");
    
        foreach($baseData as $data) {
            $trial = $data->trial_id;
            $contact_data = $data->contact_data;
    
            $affected = DB::table('trial_melanoma')
            ->where('trial_id', $trial)
            ->update(['contact_data' => $contact_data]); 
            }
        }


}
