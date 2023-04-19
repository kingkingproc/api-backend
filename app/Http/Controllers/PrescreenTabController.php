<?php

namespace App\Http\Controllers;

use App\Helper\Helper;

use Illuminate\Http\Request;
use App\Models\Patient;
use Illuminate\Support\Facades\DB;


class PrescreenTabController extends Controller
{
    public function index()
    {

        $request = request();
        $the_object = Helper::verifyJasonToken($request);
        $patientRecord = patient::where('sub',$the_object->sub)->get();

        
        $prescreenResults = DB::connection('pgsql')->select("
                select
                    ppr.patient_id,
                    ppr.prescreen_id,
                    ptr.prescreen_title
                from prescreen_patient_ref ppr inner join prescreen_trial_ref ptr
                    on ppr.prescreen_id = ptr.prescreen_id
                where ppr.patient_id = '" . $patientRecord[0]['patient_id'] . "'
        ");

        foreach ($prescreenResults as $prescreen) {
            $returnArray["prescreen_id"] = $prescreen->prescreen_id;
            $returnArray["patient_id"] = $prescreen->patient_id;
            $returnArray["prescreen_title"] = $prescreen->prescreen_title;
            $testResults = DB::connection('pgsql')->select("
                select 
                id,
                question_text,
                question_variable,
                question_sequence,
                question_correct as question_default 
                from  prescreen_questions
                where prescreen_questions.prescreen_id = '" . $prescreen->prescreen_id . "'
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



            foreach($testResults as $record) {
                if ($record->question_variable == "bln_age") {
                    $record->question_default = $bln_is_eighteen;
                }
            
                $subTestResults = DB::connection('pgsql')->select("
                select option_text,option_value,option_sequence from  prescreen_questions_options 
                where question_variable = '" . $record->question_variable . "'
                and question_id = '" . $record->id . "'
                ");

                $record->options = $subTestResults;

                $array[] = $record;
            }

            $returnArray["prescreen_questions"] = $array;
            $responseResults = DB::connection('pgsql')->select("
            select * from prescreen_response where patient_id = '" . $patientRecord[0]['patient_id'] . "'
            ");
            $returnArray["patient_response"] = $responseResults;
            
        }

        if (isset($returnArray)) {
            return [$returnArray];
        } else {
            return array();
        }
    }

    public function update(Request $request)
    {
        $request = request();
        $the_object = Helper::verifyJasonToken($request);
        $patientRecord = patient::where('sub',$the_object->sub)->get();

                $deleted = DB::connection('pgsql')->delete("
                delete from prescreen_response 
                where prescreen_response.patient_id = '" . $patientRecord[0]['patient_id'] . "'
                and prescreen_response.prescreen_id = '" . $request["prescreen_id"] . "'
                ");
                $deleted = DB::connection('pgsql')->delete("
                delete from prescreen_patient_ref 
                where prescreen_patient_ref.patient_id = '" . $patientRecord[0]['patient_id'] . "'
                and prescreen_patient_ref.prescreen_id = '" . $request["prescreen_id"] . "'
                ");

        $insertData = [
            ['patient_id'=>$patientRecord[0]['patient_id'], 'prescreen_id'=>$request["prescreen_id"], 'question_variable'=>'bln_age', 'patient_response'=>$request['bln_age']],
            ['patient_id'=>$patientRecord[0]['patient_id'], 'prescreen_id'=>$request["prescreen_id"], 'question_variable'=>'bln_diagnosis', 'patient_response'=>$request['bln_diagnosis']],
            ['patient_id'=>$patientRecord[0]['patient_id'], 'prescreen_id'=>$request["prescreen_id"], 'question_variable'=>'bln_mutation', 'patient_response'=>$request['bln_mutation']],
            ['patient_id'=>$patientRecord[0]['patient_id'], 'prescreen_id'=>$request["prescreen_id"], 'question_variable'=>'bln_mutation_kras', 'patient_response'=>$request['bln_mutation_kras']],
            ['patient_id'=>$patientRecord[0]['patient_id'], 'prescreen_id'=>$request["prescreen_id"], 'question_variable'=>'bln_mutation_other', 'patient_response'=>$request['bln_mutation_other']],
            ['patient_id'=>$patientRecord[0]['patient_id'], 'prescreen_id'=>$request["prescreen_id"], 'question_variable'=>'bln_pd1', 'patient_response'=>$request['bln_pd1']],
            ['patient_id'=>$patientRecord[0]['patient_id'], 'prescreen_id'=>$request["prescreen_id"], 'question_variable'=>'bln_pd1_platinum', 'patient_response'=>$request['bln_pd1_platinum']],
            ['patient_id'=>$patientRecord[0]['patient_id'], 'prescreen_id'=>$request["prescreen_id"], 'question_variable'=>'bln_pd1_progressed', 'patient_response'=>$request['bln_pd1_progressed']],
            ['patient_id'=>$patientRecord[0]['patient_id'], 'prescreen_id'=>$request["prescreen_id"], 'question_variable'=>'bln_pd1_time', 'patient_response'=>$request['bln_pd1_time']],
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
            and prescreen_questions.prescreen_id = '" . $request["prescreen_id"] . "' 
        order by prescreen_questions.question_variable
        ");
        
        $bln_qualified = true;
        foreach($compareRecord as $compareLine) {
            if ($compareLine->patient_response != $compareLine->question_correct && $compareLine->patient_response != "unknown") {
                $bln_qualified = false;
            }
        }

        if ($bln_qualified) {
            $insertData = [
                ['patient_id'=>$patientRecord[0]['patient_id'], 'prescreen_id'=>$request["prescreen_id"], 'patient_eligible'=>'user_eligible'],
            ];
            DB::table('prescreen_patient_ref')->insert($insertData);
            return array('status' => 'user_eligible');
        } else {
            $insertData = [
                ['patient_id'=>$patientRecord[0]['patient_id'], 'prescreen_id'=>$request["prescreen_id"], 'patient_eligible'=>'user_ineligible'],
            ];
            DB::table('prescreen_patient_ref')->insert($insertData);
            return array('status' => 'user_ineligible');
        }
        
    }
}