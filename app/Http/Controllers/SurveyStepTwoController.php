<?php

namespace App\Http\Controllers;

use App\Helper\Helper;
use App\Models\address;
use App\Models\Patient;
use Illuminate\Http\Request;

class SurveyStepTwoController extends Controller
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
       $token = request();
       $the_object = Helper::verifyJasonToken($token);
       
       $request = $request->all();

       $patient_array = $request[0]["patient"];
       $address_array = $request[1]["patient_address"];

       $address = address::create($address_array);

       $patient_array["address_id"] = $address->id;
       //$patient = patient::find($id);
       $patient = patient::where('sub',$the_object->sub)->where('email', $the_object->email)->get();
       $patient->update($patient_array);

       
       return $patient;
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
