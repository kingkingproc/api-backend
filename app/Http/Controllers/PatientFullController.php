<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use Illuminate\Http\Request;

class PatientFullController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //

  /*      $data = Patient::join('patient_contacts', 'patient_contacts.patient_id', '=', 'patients.id')
            ->join('patient_contact_data', 'patient_contact_data.contact_id', '=', 'patient_contacts.id')
            ->join('lkup_contact_data_types', 'lkup_contact_data_types.id', '=', 'patient_contact_data.contact_data_type_id')
            ->join('lkup_contact_types', 'lkup_contact_types.id', '=', 'patient_contacts.contact_type_id')

            ->get(['patients.id', 'patients.email', 'patients.sub','lkup_contact_types.contact_type', 'lkup_contact_data_types.contact_data_type', 'patient_contact_data.contact_data']);
  */          

        //return Patient::find($id)->getPatientContacts;
        //return Patient::find($id);

        $json1 = Patient::find($id)->getPatientContacts;
        $json2 = Patient::find($id);

        $array[] = json_decode($json2, true);
        $array[] = json_decode($json1, true);

        return $array;
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
