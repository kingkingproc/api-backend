<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\address;
use App\Models\PatientContact;
use App\Models\PatientContactData;
use Illuminate\Http\Request;

class PatientFullController extends Controller
{
    public function show($id)
    {
        //

       $data = Patient::join('patient_contacts', 'patient_contacts.patient_id', '=', 'patients.id')
            ->join('patient_contact_data', 'patient_contact_data.contact_id', '=', 'patient_contacts.id')
            ->join('lkup_contact_data_types', 'lkup_contact_data_types.id', '=', 'patient_contact_data.contact_data_type_id')
            ->join('lkup_contact_types', 'lkup_contact_types.id', '=', 'patient_contacts.contact_type_id')
            ->get(['patient_contact_data.id', 'patient_contact_data.contact_id', 'patient_contact_data.contact_data_type_id','patient_contact_data.contact_data']);
            

        $json1 = json_encode(['patient'=>Patient::find($id)]);
        $json2 = json_encode(['patient_address'=>Patient::find($id)->getAddresses]);
        $json3 = json_encode(['patient_contact'=>Patient::find($id)->getPatientContacts]);
        $json4 = json_encode(['contact_data'=>$data]);


        $array[] = json_decode($json1, true);
        $array[] = json_decode($json2, true);
        $array[] = json_decode($json3, true);
        $array[] = json_decode($json4, true); 

        return $array;
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
    
       return $patient;
    }


}
