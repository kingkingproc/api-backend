<?php

namespace App\Http\Controllers;

use App\Helper\Helper;

use Illuminate\Http\Request;
use App\Models\Patient;
use App\Models\PatientDiagnosis;
use App\Models\PatientDiagnosisTreatment;
use App\Models\PatientDiagnosisBiomarker;
use App\Models\PatientFavorite;
use Illuminate\Support\Facades\DB;


class PrescreenController extends Controller
{
    public function index()
    {

        $request = request();
        $the_object = Helper::verifyJasonToken($request);
        $patientRecord = patient::where('sub',$the_object->sub)->where('email', $the_object->email)->get();
        $diagnosisRecord = patientdiagnosis::where('patient_id', $patientRecord[0]['patient_id'])->get();

        

        //place trial & patient int a favorite array
        $fav_array['patient_id'] = $patientRecord[0]['patient_id'];
        $fav_array['sub'] = $patientRecord[0]['sub'];
        $fav_array['type'] = "trial";
        $fav_array['type_id'] = $request["trial_id"];

        //remove any existing favorite record for patient trial
        $testResults = DB::connection('pgsql')->delete("delete from patient_favorites where 
            patient_id = " . $fav_array['patient_id'] . "
            and sub = '" . $fav_array['sub'] . "'
            and type = '" . $fav_array['type'] . "'
            and type_id = '" . $fav_array['type_id'] . "'");

        //create new patient trial favorite record
        PatientFavorite::create($fav_array);


        $testResults = DB::connection('pgsql')->select("
            select 
            id,
            question_text,
            question_variable,
            question_sequence,
            question_correct as question_default 
            from  prescreen_questions
                inner join prescreen_trial_ref on prescreen_questions.prescreen_id = prescreen_trial_ref.prescreen_id
            where prescreen_trial_ref.trial_id = '" . $request["trial_id"] . "'
        ");

        if (!count($testResults)) {
            return array('status' => 'error','message' => 'No questions exist for the provided trial ID');
        }
        // logic for patient over 18
        if (!is_null($patientRecord[0]["dob_day"])) {
            $patientRecord[0]["DOB"] = $patientRecord[0]["dob_day"] . "-" . $patientRecord[0]["dob_month"] . "-" . $patientRecord[0]["dob_year"];
            $today = date("Y-m-d");
            $diff = date_diff(date_create($patientRecord[0]["DOB"]), date_create($today));
            $patientRecord[0]["AGE"] = $diff->format('%y');
        } else {
            $patientRecord[0]["AGE"] = "17";
        }
        if ($patientRecord[0]["AGE"] > 17) {
            $bln_is_eighteen = "true";
        } else {
            $bln_is_eighteen = "false";
        }

        // get the patients biomarkers
        if ($diagnosisRecord[0]["is_biomarker_started"]) {
            $bln_is_biomarker_started = "true";
        } else {
            $bln_is_biomarker_started = "false";
        }
        $biomarkerSynonyms = DB::connection('pgsql')->select("
        select l.biomarker_synonyms from patient_diagnosis_biomarkers p
        inner join lkup_patient_diagnosis_biomarkers l on p.biomarker_id = l.biomarker_id
        where p.diagnosis_id = '" . $diagnosisRecord[0]["diagnosis_id"] . "'
        ");


        // get the patients prior treatments
        if ($diagnosisRecord[0]["is_treatment_started"]) {
            $bln_is_treatment_started = "true";
        } else {
            $bln_is_treatment_started = "false";
        }

        $treatmentSynonyms = DB::connection('pgsql')->select("
        select l.treatment_synonyms from patient_diagnosis_treatments p
        inner join lkup_patient_diagnosis_treatments l on p.treatment_id = l.treatment_id
        where p.diagnosis_id = '" . $diagnosisRecord[0]["diagnosis_id"] . "'
        ");
        

        foreach($testResults as $record) {
            if ($record->question_variable == "bln_age") {
                $record->question_default = $bln_is_eighteen;
            }
            
            if ($record->question_variable == "bln_mutation") {
                $record->question_default = $bln_is_biomarker_started;
            }

            if ($record->question_variable == "bln_mutation_kras"){
                foreach($biomarkerSynonyms as $synonym){
                    if ($synonym->biomarker_synonyms == "kras"){
                        $record->question_default = "true";
                    }
                }
            }

            
            if ($record->question_variable == "bln_mutation_other"){
                foreach($biomarkerSynonyms as $synonym){
                    if ($synonym->biomarker_synonyms <> "kras" && $synonym->biomarker_synonyms <> "pd-1" && $synonym->biomarker_synonyms <> "pd-l1"){
                        $record->question_default = "true";
                    }
                }
            }

            if ($record->question_variable == "bln_pd1"){
                $found_right_treatment = "false";
                foreach ($treatmentSynonyms as $synonym) {
                    //return $synonym->treatment_synonyms;
                    if (stripos($synonym->treatment_synonyms,"pd-1") || stripos($synonym->treatment_synonyms,"pdl-1")) {
                        $found_right_treatment = "true";
                    }
                }
                $record->question_default = $found_right_treatment;
            }

            if ($record->question_variable == "bln_pd1_platinum"){
                $record->question_default = "false";
            }
            if ($record->question_variable == "bln_pd1_progressed"){
                $record->question_default = "false";
            }
            if ($record->question_variable == "bln_pd1_time"){
                $record->question_default = "false";
            }

            $subTestResults = DB::connection('pgsql')->select("
            select option_text,option_value,option_sequence from  prescreen_questions_options 
            where question_variable = '" . $record->question_variable . "'
            and question_id = '" . $record->id . "'
            ");

            $record->options = $subTestResults;

            $array[] = $record;
        }
        return $array;
    }

    public function update(Request $request)
    {
        $request = request();
        $the_object = Helper::verifyJasonToken($request);
        $patientRecord = patient::where('sub',$the_object->sub)->where('email', $the_object->email)->get();
        $diagnosisRecord = patientdiagnosis::where('patient_id', $patientRecord[0]['patient_id'])->get();
        $prescreenRecord = DB::connection('pgsql')->select("
        select * from prescreen_trial_ref where prescreen_trial_ref.trial_id = '" . $request["trial_id"] . "'
        ");

        foreach($prescreenRecord as $prescreen) {
                $var_prescreen_id = $prescreen->prescreen_id;
                $deleted = DB::connection('pgsql')->delete("
                delete from prescreen_response 
                where prescreen_response.patient_id = '" . $patientRecord[0]['patient_id'] . "'
                and prescreen_response.prescreen_id = '" . $prescreen->prescreen_id . "'
                ");
                $deleted = DB::connection('pgsql')->delete("
                delete from prescreen_patient_ref 
                where prescreen_patient_ref.patient_id = '" . $patientRecord[0]['patient_id'] . "'
                and prescreen_patient_ref.prescreen_id = '" . $prescreen->prescreen_id . "'
                ");
        }
        if ($var_prescreen_id == "3") {
            $insertData = [
                ['patient_id'=>$patientRecord[0]['patient_id'], 'prescreen_id'=>$var_prescreen_id, 'question_variable'=>'bln_age', 'patient_response'=>$request['bln_age']],
                ['patient_id'=>$patientRecord[0]['patient_id'], 'prescreen_id'=>$var_prescreen_id, 'question_variable'=>'bln_diagnosis', 'patient_response'=>$request['bln_diagnosis']],
                ['patient_id'=>$patientRecord[0]['patient_id'], 'prescreen_id'=>$var_prescreen_id, 'question_variable'=>'bln_mutation', 'patient_response'=>$request['bln_mutation']],
                ['patient_id'=>$patientRecord[0]['patient_id'], 'prescreen_id'=>$var_prescreen_id, 'question_variable'=>'bln_mutation_kras', 'patient_response'=>$request['bln_mutation_kras']],
                ['patient_id'=>$patientRecord[0]['patient_id'], 'prescreen_id'=>$var_prescreen_id, 'question_variable'=>'bln_mutation_other', 'patient_response'=>$request['bln_mutation_other']],
                ['patient_id'=>$patientRecord[0]['patient_id'], 'prescreen_id'=>$var_prescreen_id, 'question_variable'=>'bln_pd1', 'patient_response'=>$request['bln_pd1']],
                ['patient_id'=>$patientRecord[0]['patient_id'], 'prescreen_id'=>$var_prescreen_id, 'question_variable'=>'bln_pd1_platinum', 'patient_response'=>$request['bln_pd1_platinum']],
                ['patient_id'=>$patientRecord[0]['patient_id'], 'prescreen_id'=>$var_prescreen_id, 'question_variable'=>'bln_pd1_progressed', 'patient_response'=>$request['bln_pd1_progressed']],
                ['patient_id'=>$patientRecord[0]['patient_id'], 'prescreen_id'=>$var_prescreen_id, 'question_variable'=>'bln_pd1_time', 'patient_response'=>$request['bln_pd1_time']],
            ];
        }else{

            $insertData = [
                ['patient_id'=>$patientRecord[0]['patient_id'], 'prescreen_id'=>$var_prescreen_id, 'question_variable'=>'sel_melanoma_subtype', 'patient_response'=>$request['sel_melanoma_subtype']],
                ['patient_id'=>$patientRecord[0]['patient_id'], 'prescreen_id'=>$var_prescreen_id, 'question_variable'=>'sel_melanoma_stage', 'patient_response'=>$request['sel_melanoma_stage']],
                ['patient_id'=>$patientRecord[0]['patient_id'], 'prescreen_id'=>$var_prescreen_id, 'question_variable'=>'bln_brain_spinal', 'patient_response'=>$request['bln_brain_spinal']],
                ['patient_id'=>$patientRecord[0]['patient_id'], 'prescreen_id'=>$var_prescreen_id, 'question_variable'=>'sel_melanoma_ecog', 'patient_response'=>$request['sel_melanoma_ecog']],
                ['patient_id'=>$patientRecord[0]['patient_id'], 'prescreen_id'=>$var_prescreen_id, 'question_variable'=>'sel_melanoma_treatment', 'patient_response'=>$request['sel_melanoma_treatment']],
                ['patient_id'=>$patientRecord[0]['patient_id'], 'prescreen_id'=>$var_prescreen_id, 'question_variable'=>'sel_melanoma_biomarker', 'patient_response'=>$request['sel_melanoma_biomarker']],
            ];
        }
        DB::table('prescreen_response')->insert($insertData);

        $compareRecord = DB::connection('pgsql')->select("
        select
        prescreen_questions.question_variable,
        prescreen_questions.question_correct,
        prescreen_response.patient_response
        from prescreen_questions
            inner join prescreen_response on prescreen_questions.prescreen_id = prescreen_response.prescreen_id and prescreen_questions.question_variable = prescreen_response.question_variable
        where 
            prescreen_response.patient_id = '" . $patientRecord[0]['patient_id'] . "' 
            and prescreen_questions.prescreen_id = '" . $var_prescreen_id . "' 
        order by prescreen_questions.question_variable
        ");
        
        $bln_qualified = true;
        foreach($compareRecord as $compareLine) {
            if ($compareLine->patient_response != $compareLine->question_correct && $compareLine->patient_response != "unknown") {
                $bln_qualified = false;
            }
        }
        
        if ($var_prescreen_id == "3") {

                    if (($request['bln_age'] == "true") && ($request['bln_diagnosis'] == "true") && ($request['bln_mutation_other'] == "false") && ($request['bln_pd1'] == "true") && ($request['bln_pd1_platinum'] == "true") && ($request['bln_pd1_progressed']  == "true") && ($request['bln_pd1_time']  == "true")) {

                        $insertData = [
                            ['patient_id'=>$patientRecord[0]['patient_id'], 'prescreen_id'=>$var_prescreen_id, 'patient_eligible'=>'user_eligible'],
                        ];
                        DB::table('prescreen_patient_ref')->insert($insertData);

                        $array = array('status' => 'user_eligible');
                        return $array;
                    } elseif (($request['bln_age'] == "false") || ($request['bln_diagnosis'] == "false") || ($request['bln_mutation_other'] == "true") || ($request['bln_pd1'] == "false") || ($request['bln_pd1_platinum'] == "false")) {

                        $insertData = [
                            ['patient_id'=>$patientRecord[0]['patient_id'], 'prescreen_id'=>$var_prescreen_id, 'patient_eligible'=>'user_ineligible'],
                        ];
                        DB::table('prescreen_patient_ref')->insert($insertData);

                        $array = array('status' => 'user_ineligible');
                        return $array;
                    } else {

                        $insertData = [
                            ['patient_id'=>$patientRecord[0]['patient_id'], 'prescreen_id'=>$var_prescreen_id, 'patient_eligible'=>'user_maybe'],
                        ];
                        DB::table('prescreen_patient_ref')->insert($insertData);

                        $array = array('status' => 'user_maybe');
                        return $array;
                    }
        } else {
                if ($bln_qualified) {
                    $insertData = [
                        ['patient_id'=>$patientRecord[0]['patient_id'], 'prescreen_id'=>$var_prescreen_id, 'patient_eligible'=>'user_eligible'],
                    ];
                    DB::table('prescreen_patient_ref')->insert($insertData);
                    return array('status' => 'user_eligible');
                } else {
                    $insertData = [
                        ['patient_id'=>$patientRecord[0]['patient_id'], 'prescreen_id'=>$var_prescreen_id, 'patient_eligible'=>'user_ineligible'],
                    ];
                    DB::table('prescreen_patient_ref')->insert($insertData);
                    return array('status' => 'user_ineligible');
                }
        }
        
    }
}