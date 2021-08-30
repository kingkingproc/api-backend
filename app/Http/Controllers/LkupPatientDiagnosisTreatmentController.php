<?php

namespace App\Http\Controllers;

use App\Models\LkupPatientDiagnosisTreatment;
use Illuminate\Http\Request;

class LkupPatientDiagnosisTreatmentController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return lkuppatientdiagnosistreatment::select('treatment_id AS key','treatment_label AS value')->get();
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

    /**
     * Search for the specified resource in storage.
     *
     * @param  str  $label
     * @return \Illuminate\Http\Response
     */
    public function search($label)
    {
        return lkuppatientdiagnosistreatment::select('treatment_id AS key','treatment_label AS value')
                                                ->where('treatment_label', 'ilike', $label.'%')
                                                ->get();
    }
}
