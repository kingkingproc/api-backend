<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\address;
use App\Models\PatientContact;
use App\Models\PatientContactData;
use App\Models\LkupContactDataType;
use App\Models\PatientDiagnosis;
use App\Models\PatientDiagnosisTreatment;
use App\Models\PatientDiagnosisAdditional;
use App\Models\PatientDiagnosisRemoteSite;
use Illuminate\Http\Request;

class PatientFullController extends Controller
{
    public function show($id)
    {

        $json1 = json_encode(['patient'=>Patient::find($id)]);
        $json2 = json_encode(['patient_address'=>Patient::find($id)->getAddresses]);
       // $json3 = json_encode(['patient_contact'=>Patient::find($id)->getPatientContacts]);
       // $json4 = json_encode(['contact_data'=>$data]);


        $array[] = json_decode($json1, true);
        $array[] = json_decode($json2, true);
       // $array[] = json_decode($json3, true);
       // $array[] = json_decode($json4, true); 

       $patient_contacts = Patient::find($id)->getPatientContacts;
       foreach ($patient_contacts as $contact){
        //$array[] = $treatment_s;
        $json8 = "";
        $json9 = "";
        $json10 = "";
        $json11 = "";
        $json8 = json_encode(['paitent_contact'=>$contact]);
        $array[] = json_decode($json8, true);
        if (!empty($contact->address_id)) {
            $json12 = json_encode(['contact_address'=>address::find($contact->address_id)]);
            $array[] = json_decode($json12, true);
        }
        $json9 = json_encode(['paitent_contact_type'=>patientcontact::find($contact->contact_id)->getPatientContactType]);
        $paitent_contact_data = PatientContact::find($contact->contact_id)->getPatientContactData;
        $json10 = json_encode(['paitent_contact_data'=>$paitent_contact_data]);
        $json11 = json_encode(['paitent_contact_data_type'=>LkupContactDataType::find($paitent_contact_data->contact_data_type_id)]);
        
        
        $array[] = json_decode($json9, true);
        $array[] = json_decode($json10, true);
        $array[] = json_decode($json11, true);
        }

        $patientdiagnosis = Patient::find($id)->getDiagnosis;
        $diagnosis_id = $patientdiagnosis->diagnosis_id;

        $json21 = json_encode(['patient_diagnosis'=> $patientdiagnosis]);
        $json22 = json_encode(['cancer_type'=>patientdiagnosis::find($diagnosis_id)->cancer_type]);
        $json23 = json_encode(['cell_type'=>patientdiagnosis::find($diagnosis_id)->cell_type]);
        $json24 = json_encode(['stage'=>patientdiagnosis::find($diagnosis_id)->stage]);
        $json25 = json_encode(['tumor_site'=>patientdiagnosis::find($diagnosis_id)->tumor_site]);
        $json26 = json_encode(['tumor_size'=>patientdiagnosis::find($diagnosis_id)->tumor_size]);
        $json27 = json_encode(['perfomance_score'=>patientdiagnosis::find($diagnosis_id)->performance_score]);
        
        $array[] = json_decode($json21, true);
        $array[] = json_decode($json22, true);
        $array[] = json_decode($json23, true);
        $array[] = json_decode($json24, true);
        $array[] = json_decode($json25, true);
        $array[] = json_decode($json26, true);
        $array[] = json_decode($json27, true);
        
        $treatment = patientdiagnosis::find($diagnosis_id)->treatment;
        $additional = patientdiagnosis::find($diagnosis_id)->additional;
        $remote_site = patientdiagnosis::find($diagnosis_id)->remote_site;



        foreach ($treatment as $treatment_s){
            //$array[] = $treatment_s;
            $json28 = "";
            $json28 = json_encode(['treatment'=>patientdiagnosistreatment::find($treatment_s->treatment_id)->treatments]);
            $array[] = json_decode($json28, true);
        }
 
        foreach ($additional as $additional_s){
            //$array[] = $treatment_s;
            $json28 = "";
            $json28 = json_encode(['additional'=>patientdiagnosisadditional::find($additional_s->additional_id)->additionals]);
            $array[] = json_decode($json28, true);
        }

        foreach ($remote_site as $remote_site_s){
            //$array[] = $treatment_s;
            $json28 = "";
            $json28 = json_encode(['remote_site'=>patientdiagnosisremotesite::find($remote_site_s->remote_site_id)->remote_sites]);
            $array[] = json_decode($json28, true);
        }
        return json_encode($array, JSON_PRETTY_PRINT);
    }

    public function update(Request $request, $id)
    {
        //
        $request = $request->all();

        foreach($request as $elem)  {
                $array[] = $elem; 
         }
        foreach($array[2] as $contact){
                $contactarray[] = $contact;
                $patientcontact = patientcontact::find($contactarray[0]["id"]);
                $patientcontact->update($contactarray[0]);
                unset($contactarray);
        }
        foreach($array[3] as $contact_data){
                $contact_dataarray[] = $contact_data;
                $patientcontactdata = patientcontactdata::find($contact_dataarray[0]["id"]);
                $patientcontactdata->update($contact_dataarray[0]);
                unset($contact_dataarray);
        }    

       $patient = patient::find($array[0]["id"]);
       $patient->update($array[0]);
    
       return json_encode(['patient'=>$patient]);
    }


}
