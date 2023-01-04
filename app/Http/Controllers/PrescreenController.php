<?php

namespace App\Http\Controllers;

use App\Helper\Helper;

use Illuminate\Http\Request;
use App\Models\Patient;
use App\Models\PatientDiagnosis;
use App\Models\PatientDiagnosisTreatment;
use App\Models\PatientDiagnosisBiomarker;
use Illuminate\Support\Facades\DB;

class PrescreenController extends Controller
{
    public function index()
    {
        $request = request();
        $the_object = Helper::verifyJasonToken($request);
        $patientRecord = patient::where('sub',$the_object->sub)->get();
        $diagnosisRecord = patientdiagnosis::where('patient_id', $patientRecord[0]['patient_id'])->get();

        $testResults = DB::connection('pgsql')->select("
            select 
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

        // logic for immunotherapy
        $treatment_records = PatientDiagnosisTreatment::where('diagnosis_id',$diagnosisRecord[0]["diagnosis_id"])->get();
        $bln_prior_treatment = "false";
        foreach($treatment_records as $treatment_record) {
            if ($treatment_record->treatment_id == 4) {
                $bln_prior_treatment = "true";
            }
        }

        // logic for biomarkers
        $biomarker_records = PatientDiagnosisBiomarker::where('diagnosis_id',$diagnosisRecord[0]["diagnosis_id"])->get();
        $bln_biomarker = "false";
        foreach($biomarker_records as $biomarker_record) {
            if ($biomarker_record->biomarker_id == 34 || 
                $biomarker_record->biomarker_id == 3 ||
                $biomarker_record->biomarker_id == 61 ||
                $biomarker_record->biomarker_id == 12 ||
                $biomarker_record->biomarker_id == 54
                ) {
                $bln_biomarker = "true";
            }
        }

        $bln_diagnosis = "false";
        if ($diagnosisRecord[0]["is_metastatic"]) {
            $bln_diagnosis = "true";
        }

        $bln_brain = "false";
        if($diagnosisRecord[0]["is_brain_tumor"]){
            $bln_brain = "true";
        }

        foreach($testResults as $record) {
            if ($record->question_variable == "bln_age") {
                $record->question_default = $bln_is_eighteen;
            }
            if ($record->question_variable == "bln_diagnosis") {
                $record->question_default = $bln_diagnosis;
            }
            if ($record->question_variable == "bln_brain") {
                $record->question_default = $bln_brain;
            }
            if ($record->question_variable == "bln_immunotherapy") {
                $record->question_default = $bln_prior_treatment;
            }
            if ($record->question_variable == "bln_mutation") {
                $record->question_default = $bln_biomarker;
            }            
            $subTestResults = DB::connection('pgsql')->select("
            select option_text,option_value,option_sequence from  prescreen_questions_options 
            where question_variable = '" . $record->question_variable . "'
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
        $patientRecord = patient::where('sub',$the_object->sub)->get();
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
        $insertData = [
            ['patient_id'=>$patientRecord[0]['patient_id'], 'prescreen_id'=>$var_prescreen_id, 'question_variable'=>'bln_age', 'patient_response'=>$request['bln_age']],
            ['patient_id'=>$patientRecord[0]['patient_id'], 'prescreen_id'=>$var_prescreen_id, 'question_variable'=>'bln_diagnosis', 'patient_response'=>$request['bln_diagnosis']],
            ['patient_id'=>$patientRecord[0]['patient_id'], 'prescreen_id'=>$var_prescreen_id, 'question_variable'=>'bln_brain', 'patient_response'=>$request['bln_brain']],
            ['patient_id'=>$patientRecord[0]['patient_id'], 'prescreen_id'=>$var_prescreen_id, 'question_variable'=>'bln_mutation', 'patient_response'=>$request['bln_mutation']],
            ['patient_id'=>$patientRecord[0]['patient_id'], 'prescreen_id'=>$var_prescreen_id, 'question_variable'=>'bln_immunotherapy', 'patient_response'=>$request['bln_immunotherapy']],
            ['patient_id'=>$patientRecord[0]['patient_id'], 'prescreen_id'=>$var_prescreen_id, 'question_variable'=>'bln_progressed', 'patient_response'=>$request['bln_progressed']],
        ];
        
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
            if ($compareLine->patient_response != $compareLine->question_correct) {
                $bln_qualified = false;
            }
        }

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