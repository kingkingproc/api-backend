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


class EducationController extends Controller
{
    public function index()
    {

        $request = request();
        $the_object = Helper::verifyJasonToken($request);
        $patientRecord = patient::where('sub',$the_object->sub)->where('email', $the_object->email)->get();
        $diagnosisRecord = patientdiagnosis::where('patient_id', $patientRecord[0]['patient_id'])->get();
        //set for default values
        $searchType = 212;
        $searchStage = 1;
        
        $searchType =  $diagnosisRecord[0]['cancer_type_id'];
        $searchPhase = $diagnosisRecord[0]->performance_score_id;
        $searchEcog = $diagnosisRecord[0]->performance_score_id;
        $searchStage = $diagnosisRecord[0]->stage_id;

        if (is_null($searchStage)) {
            $searchStage = 1;
        } 
        if (is_null($searchType)) {
            $searchType = 212;
        } 



        $featured = DB::connection('pgsql')->select('
        SELECT content_featured_id,label,href,author,created_at as date FROM public.content_featured
        WHERE cancer_type_id is null and cancer_stage_id is null
        union
        SELECT content_featured_id,label,href,author,created_at as date FROM public.content_featured
        WHERE cancer_type_id = ' . $searchType . ' and cancer_stage_id is null
        union
        SELECT content_featured_id,label,href,author,created_at as date FROM public.content_featured
        WHERE cancer_type_id = ' . $searchType . ' and cancer_stage_id = ' . $searchStage . '
        ORDER BY content_featured_id ASC
        ');
        $array["FEATURED_ARTICLES"] =  $featured;

        $folders = DB::connection('pgsql')->select('
        SELECT * FROM public.content_folder
        WHERE cancer_type_id is null and cancer_stage_id is null
        union
        SELECT * FROM public.content_folder
        WHERE cancer_type_id = ' . $searchType . ' and cancer_stage_id is null
        union
        SELECT * FROM public.content_folder
        WHERE cancer_type_id = ' . $searchType . ' and cancer_stage_id = ' . $searchStage . '
        ORDER BY content_folder_id ASC
        ');

        foreach($folders as $indiv_folder) {
            $indiv_folder->links = [];
            $links_records = DB::connection('pgsql')->select('
            SELECT * FROM public.content_links
            WHERE content_folder_id = ' . $indiv_folder->content_folder_id . ' and 
            (cancer_stage_id = ' . $searchStage . ' OR cancer_stage_id is null) and
            (cancer_type_id = ' . $searchType . ' OR cancer_type_id is null)
            ORDER BY content_link_id ASC
            ');           
            $indiv_folder->links = $links_records;
            $folderarray[] = $indiv_folder;
            
        }
        $array["FOLDERS"] = $folderarray;
        //$array[] = $folderarray;
        return $array;

    }
}
